<?php
session_start();

/*
|--------------------------------------------------------------------------
| ADMIN LOGIN CHECK
|--------------------------------------------------------------------------
*/
if (empty($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$activeMenu = 'orders';
$pageTitle  = 'GatewayLinen | Order Details';

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] = $_SESSION['admin_username'] ?? 'GatewayLinen Administrator';
}

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function formatDate($value): string {
    if ($value instanceof DateTimeInterface) {
        return $value->format('d M Y, h:i A');
    }
    return !empty($value) ? date('d M Y, h:i A', strtotime($value)) : '—';
}

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($orderId <= 0) {
    header('Location: index.php?error=' . urlencode('Invalid order ID selected.'));
    exit;
}

$message = '';
$error = '';

/*
|--------------------------------------------------------------------------
| UPDATE ORDER STATUS & SHIPMENT
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $newStatus = trim($_POST['order_status'] ?? '');
        $currentStatus = trim($_POST['current_status'] ?? '');

        if (!empty($newStatus) && $newStatus !== $currentStatus) {
            sqlsrv_begin_transaction($conn);

            // Update Orders table
            $upSql = "UPDATE dbo.Orders SET OrderStatus = ? WHERE OrderId = ?";
            $upStmt = sqlsrv_query($conn, $upSql, [$newStatus, $orderId]);

            // Insert into OrderStatusHistory
            $histSql = "INSERT INTO dbo.OrderStatusHistory (OrderId, FromStatus, ToStatus, ChangedBy, NotifiedCustomer, CreatedAt) 
                        VALUES (?, ?, ?, ?, 1, GETDATE())";
            $histStmt = sqlsrv_query($conn, $histSql, [$orderId, $currentStatus, $newStatus, $_SESSION['admin_name']]);

            if ($upStmt !== false && $histStmt !== false) {
                sqlsrv_commit($conn);
                $message = "Order status updated to " . e($newStatus) . " successfully.";
            } else {
                sqlsrv_rollback($conn);
                $error = "Failed to update order status.";
            }
        }
    }

    if ($_POST['action'] === 'update_shipment') {
        $courierName    = trim($_POST['courier_name'] ?? '');
        $trackingNumber = trim($_POST['tracking_number'] ?? '');

        $chkShip = sqlsrv_query($conn, "SELECT ShipmentId FROM dbo.OrderShipments WHERE OrderId = ?", [$orderId]);
        if ($chkShip !== false && $shipRow = sqlsrv_fetch_array($chkShip, SQLSRV_FETCH_ASSOC)) {
            $shipSql = "UPDATE dbo.OrderShipments SET CourierName = ?, TrackingNumber = ?, ShippedDate = GETDATE() WHERE OrderId = ?";
            $shipParams = [$courierName, $trackingNumber, $orderId];
        } else {
            $shipSql = "INSERT INTO dbo.OrderShipments (OrderId, CourierName, TrackingNumber, ShippedDate) VALUES (?, ?, ?, GETDATE())";
            $shipParams = [$orderId, $courierName, $trackingNumber];
        }

        $res = sqlsrv_query($conn, $shipSql, $shipParams);
        if ($res !== false) {
            $message = "Shipment details updated successfully.";
        } else {
            $error = "Failed to update shipment details.";
        }
    }
}

/*
|--------------------------------------------------------------------------
| FETCH ORDER DETAILS & ITEMS
|--------------------------------------------------------------------------
*/
$orderSql = "SELECT * FROM dbo.Orders WHERE OrderId = ?";
$orderStmt = sqlsrv_query($conn, $orderSql, [$orderId]);
$order = ($orderStmt !== false) ? sqlsrv_fetch_array($orderStmt, SQLSRV_FETCH_ASSOC) : null;
if ($orderStmt !== false) sqlsrv_free_stmt($orderStmt);

if (!$order) {
    header('Location: index.php?error=' . urlencode('Order not found.'));
    exit;
}

$itemsSql = "SELECT * FROM dbo.OrderItems WHERE OrderId = ? ORDER BY OrderItemId ASC";
$itemsStmt = sqlsrv_query($conn, $itemsSql, [$orderId]);
$orderItems = [];
if ($itemsStmt !== false) {
    while ($row = sqlsrv_fetch_array($itemsStmt, SQLSRV_FETCH_ASSOC)) {
        $orderItems[] = $row;
    }
    sqlsrv_free_stmt($itemsStmt);
}

$shipSql = "SELECT TOP 1 * FROM dbo.OrderShipments WHERE OrderId = ? ORDER BY ShipmentId DESC";
$shipStmt = sqlsrv_query($conn, $shipSql, [$orderId]);
$shipment = ($shipStmt !== false) ? sqlsrv_fetch_array($shipStmt, SQLSRV_FETCH_ASSOC) : null;
if ($shipStmt !== false) sqlsrv_free_stmt($shipStmt);

$histListSql = "SELECT * FROM dbo.OrderStatusHistory WHERE OrderId = ? ORDER BY CreatedAt DESC";
$histListStmt = sqlsrv_query($conn, $histListSql, [$orderId]);
$statusHistory = [];
if ($histListStmt !== false) {
    while ($hRow = sqlsrv_fetch_array($histListStmt, SQLSRV_FETCH_ASSOC)) {
        $statusHistory[] = $hRow;
    }
    sqlsrv_free_stmt($histListStmt);
}

// Financial calculations
$calcSubTotal = 0;
$calcTax = 0;
foreach ($orderItems as $it) {
    $p = (float)($it['UnitPrice'] ?? 0);
    $q = (int)($it['Quantity'] ?? 1);
    $calcSubTotal += (float)($it['LineTotal'] ?? ($p * $q));
    $calcTax += (float)($it['GstAmount'] ?? 0) + (float)($it['PstAmount'] ?? 0);
}
$discountAmount = (float)($order['DiscountAmount'] ?? 0);
$grandTotal = (float)($order['TotalAmount'] ?? $order['GrandTotal'] ?? ($calcSubTotal + $calcTax - $discountAmount));

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<style>
:root {
    --bg-page: #0a1119;
    --bg-card: #111b26;
    --bg-card-alt: #0f1823;
    --bg-input: #0d1620;
    --border: #1e2d3d;
    --border-soft: #182636;
    --text-hi: #f0f4f8;
    --text-body: #a8b8c8;
    --text-mute: #5f7488;
    --green: #10b981;
    --green-dark: #059669;
    --green-soft: rgba(16,185,129,.12);
    --blue: #3b82f6;
    --blue-soft: rgba(59,130,246,.12);
    --amber: #f59e0b;
    --amber-soft: rgba(245,158,11,.12);
    --red: #ef4444;
    --red-soft: rgba(239,68,68,.12);
}

html, body, .main, .content {
    background: var(--bg-page) !important;
    color: var(--text-body) !important;
}

.order-detail-page {
    width: 100%;
    max-width: 1350px;
    margin: 0 auto;
    padding: 0 0 50px;
}

.page-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border);
    flex-wrap: wrap;
}

.breadcrumb {
    display: flex;
    gap: 8px;
    margin-bottom: 8px;
    color: var(--text-mute);
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
}

.breadcrumb .current { color: var(--green); }
.page-title { margin: 0; color: var(--text-hi); font-size: 26px; font-weight: 800; }

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 16px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    border: 1px solid var(--border);
    background: var(--bg-input);
    color: var(--text-body);
    cursor: pointer;
    transition: all .15s ease;
}

.btn:hover {
    border-color: var(--green);
    background: var(--green-soft);
    color: var(--green);
}

.btn-primary {
    background: linear-gradient(135deg, var(--green-dark), var(--green));
    border-color: transparent;
    color: #fff !important;
}

.btn-blue {
    background: var(--blue-soft);
    border-color: rgba(59,130,246,.3);
    color: #60a5fa !important;
}
.btn-blue:hover {
    background: #2563eb;
    color: #fff !important;
    border-color: transparent;
}

.notice-success {
    padding: 12px 16px;
    background: var(--green-soft);
    border: 1px solid rgba(16,185,129,.3);
    border-radius: 8px;
    color: #6ee7b7;
    margin-bottom: 20px;
    font-size: 12px;
    font-weight: 600;
}

.notice-error {
    padding: 12px 16px;
    background: var(--red-soft);
    border: 1px solid rgba(239,68,68,.3);
    border-radius: 8px;
    color: #fca5a5;
    margin-bottom: 20px;
    font-size: 12px;
    font-weight: 600;
}

.order-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 380px;
    gap: 20px;
}

.card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 20px;
}

.card-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.card-header h2 {
    margin: 0;
    font-size: 13px;
    font-weight: 800;
    color: var(--text-hi);
    text-transform: uppercase;
    letter-spacing: .5px;
}

.card-body { padding: 20px; }

.items-table { width: 100%; border-collapse: collapse; }
.items-table th {
    background: var(--bg-card-alt);
    padding: 10px 14px;
    color: var(--text-mute);
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    border-bottom: 1px solid var(--border);
    text-align: left;
}
.items-table td {
    padding: 12px 14px;
    border-bottom: 1px solid var(--border-soft);
    color: var(--text-body);
    font-size: 12px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 9px 0;
    border-bottom: 1px solid var(--border-soft);
    font-size: 12px;
}
.info-row:last-child { border-bottom: none; }
.info-label { color: var(--text-mute); font-weight: 600; }
.info-val { color: var(--text-hi); font-weight: 700; text-align: right; }

.form-control {
    width: 100%;
    height: 40px;
    background: var(--bg-input);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text-hi);
    padding: 0 12px;
    margin-bottom: 12px;
    font-size: 12px;
    box-sizing: border-box;
}

.timeline { list-style: none; padding: 0; margin: 0; }
.timeline-item {
    position: relative;
    padding-left: 20px;
    margin-bottom: 14px;
    border-left: 2px solid var(--border);
}
.timeline-item:before {
    content: '';
    position: absolute;
    left: -5px;
    top: 4px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--green);
}

/* =========================================================
   INTERACTIVE PRINT MODAL STYLING
========================================================= */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(4, 8, 14, 0.85);
    backdrop-filter: blur(8px);
    z-index: 99999;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.modal-overlay.active { display: flex; }

.modal-interactive {
    width: 100%;
    max-width: 900px;
    background: #0f1823;
    border: 1px solid var(--border);
    border-radius: 16px;
    box-shadow: 0 25px 50px -10px rgba(0, 0, 0, 0.85);
    display: flex;
    flex-direction: column;
    max-height: 92vh;
    overflow: hidden;
}

.modal-header {
    padding: 18px 24px;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #111b26;
}
.modal-title { margin: 0; font-size: 16px; font-weight: 800; color: var(--text-hi); }
.modal-sub { font-size: 11.5px; color: var(--text-mute); display: block; margin-top: 3px; }
.close-btn {
    background: transparent;
    border: none;
    color: var(--text-mute);
    font-size: 26px;
    cursor: pointer;
    line-height: 1;
    transition: 0.15s ease;
}
.close-btn:hover { color: #fff; }

.modal-body-split {
    display: grid;
    grid-template-columns: 1fr 370px;
    overflow: hidden;
    height: 510px;
}

/* Options List (Left) */
.paper-options-list {
    padding: 16px 20px;
    overflow-y: auto;
    border-right: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    gap: 8px;
    background: #0b131c;
}
.paper-options-list::-webkit-scrollbar { width: 5px; }
.paper-options-list::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }

.category-label {
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .8px;
    color: var(--text-mute);
    margin: 8px 4px 4px;
}

.paper-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    background: #111b26;
    border: 1px solid var(--border);
    border-radius: 10px;
    cursor: pointer;
    transition: all .2s ease;
    user-select: none;
}
.paper-card:hover {
    border-color: rgba(59, 130, 246, 0.6);
    background: rgba(59, 130, 246, 0.08);
    transform: translateX(4px);
}
.paper-card.active {
    border-color: var(--green);
    background: rgba(16, 185, 129, 0.1);
}

.paper-card-icon {
    font-size: 18px;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #0d1620;
    border-radius: 6px;
    border: 1px solid var(--border-soft);
}
.paper-card-info {
    flex: 1;
    display: flex;
    flex-direction: column;
}
.paper-card-info strong {
    font-size: 12.5px;
    color: var(--text-hi);
    font-weight: 700;
}
.paper-card-info span {
    font-size: 10.5px;
    color: var(--text-mute);
    margin-top: 1px;
}

/* Key shortcut badges */
.key-badge {
    font-size: 10px;
    font-family: monospace;
    font-weight: 800;
    padding: 2px 6px;
    border-radius: 4px;
    background: #1e2d3d;
    border: 1px solid rgba(255,255,255,0.1);
    color: #cbd5e1;
    box-shadow: 0 1px 3px rgba(0,0,0,0.4);
}

.badge-tag {
    font-size: 9.5px;
    padding: 2px 8px;
    background: var(--green-soft);
    color: var(--green);
    border-radius: 20px;
    font-weight: 700;
    text-transform: uppercase;
}
.badge-custom {
    background: rgba(59,130,246,0.15);
    color: #60a5fa;
}

/* Live Preview Stage (Right) */
.preview-stage-container {
    padding: 20px;
    background: #090e15;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    overflow-y: auto;
}
.preview-stage {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 200px;
}

/* Paper Mockup Shape */
.paper-sheet-mockup {
    background: #ffffff;
    border-radius: 4px;
    box-shadow: 0 14px 35px rgba(0, 0, 0, 0.7);
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    padding: 12px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    width: 150px;
    height: 212px;
}
.mockup-header { height: 10px; width: 45%; background: #0f172a; margin-bottom: 10px; border-radius: 2px; }
.mockup-line { height: 4px; background: #cbd5e1; margin-bottom: 4px; border-radius: 2px; }
.mockup-divider { height: 1px; background: #e2e8f0; margin: 8px 0; }
.mockup-row { display: flex; justify-content: space-between; margin-bottom: 4px; }
.mockup-row span { height: 4px; width: 32%; background: #e2e8f0; border-radius: 2px; }
.mockup-row span:last-child { width: 20%; background: #cbd5e1; }
.mockup-total { height: 6px; width: 42%; background: #10b981; margin-left: auto; margin-top: auto; border-radius: 2px; }

.paper-sheet-mockup.thermal-mode {
    border-bottom: 4px dashed #94a3b8;
    border-radius: 2px 2px 0 0;
}

/* Custom Size Input Controls */
.custom-inputs-box {
    display: none;
    background: #111b26;
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 12px;
    margin-bottom: 12px;
}
.custom-inputs-box.active { display: block; }
.custom-row {
    display: flex;
    gap: 8px;
    margin-top: 6px;
}
.custom-row .form-control {
    margin-bottom: 0;
    height: 36px;
    padding: 0 8px;
}

.preview-details {
    padding-top: 12px;
    border-top: 1px solid var(--border);
}
.preview-details h4 {
    margin: 0;
    font-size: 14px;
    font-weight: 800;
    color: var(--text-hi);
}
.preview-dim {
    font-size: 11px;
    font-weight: 700;
    color: var(--green);
    margin: 4px 0 8px;
    font-family: monospace;
}
.preview-desc {
    margin: 0;
    font-size: 11.5px;
    line-height: 1.5;
    color: var(--text-body);
}

.modal-footer {
    padding: 14px 24px;
    border-top: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #111b26;
}

@media (max-width: 950px) {
    .order-layout { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .modal-body-split { grid-template-columns: 1fr; height: auto; max-height: 70vh; }
    .preview-stage-container { display: none; }
}

/* =========================================================
   DEDICATED PRINT ENGINE FORMATTING
========================================================= */
#printableInvoice {
    display: none;
}

@media print {
    body * {
        visibility: hidden !important;
    }
    #printableInvoice, #printableInvoice * {
        visibility: visible !important;
    }
    #printableInvoice {
        display: block !important;
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        background: #fff !important;
        color: #111 !important;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif !important;
        padding: 15px;
        box-sizing: border-box;
    }

    body.paper-a3 { @page { size: A3 portrait; margin: 15mm; } }
    body.paper-a4 { @page { size: A4 portrait; margin: 12mm; } }
    body.paper-a5 { @page { size: A5 portrait; margin: 8mm; } }
    body.paper-letter { @page { size: letter portrait; margin: 12mm; } }
    body.paper-legal { @page { size: legal portrait; margin: 12mm; } }
    
    body.paper-pos80 {
        @page { size: 80mm auto; margin: 3mm; }
    }
    body.paper-pos80 #printableInvoice {
        width: 74mm !important;
        font-size: 11px !important;
        padding: 2mm !important;
    }
    body.paper-pos58 {
        @page { size: 58mm auto; margin: 2mm; }
    }
    body.paper-pos58 #printableInvoice {
        width: 52mm !important;
        font-size: 9.5px !important;
        padding: 1mm !important;
    }
    body.paper-pos80 .hide-receipt, body.paper-pos58 .hide-receipt {
        display: none !important;
    }
}
</style>

<main class="main">
    <section class="content">
        <div class="order-detail-page">

            <!-- PAGE HEADER -->
            <div class="page-header">
                <div>
                    <div class="breadcrumb">
                        <span>Management</span> / <a href="index.php" style="color:var(--text-mute); text-decoration:none;">Orders</a> / <span class="current"><?= e($order['OrderNumber'] ?? 'Order Details') ?></span>
                    </div>
                    <h1 class="page-title">Order #<?= e($order['OrderNumber'] ?? $order['OrderId']) ?></h1>
                    <span style="font-size:12px; color:var(--text-mute);">Placed on <?= formatDate($order['CreatedAt'] ?? '') ?></span>
                </div>
                <div class="header-actions">
                    <button type="button" class="btn btn-blue" onclick="openPrintModal();" title="Press Ctrl + P">
                        🖨 Print Invoice / Slip <span class="key-badge" style="margin-left:4px; font-size:9.5px;">Ctrl+P</span>
                    </button>
                    <a href="index.php" class="btn">← Back to Orders</a>
                </div>
            </div>

            <?php if ($message !== ''): ?>
                <div class="notice-success">✓ <?= e($message) ?></div>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
                <div class="notice-error">! <?= e($error) ?></div>
            <?php endif; ?>

            <div class="order-layout">

                <!-- LEFT SIDE: ITEMS & FINANCIALS -->
                <div>
                    <div class="card">
                        <div class="card-header">
                            <h2>Order Items (<?= count($orderItems) ?>)</h2>
                            <span style="background:var(--green-soft); color:var(--green); font-size:11px; font-weight:800; padding:4px 10px; border-radius:20px; text-transform:uppercase;">
                                <?= e($order['OrderStatus'] ?? 'Pending') ?>
                            </span>
                        </div>
                        <div class="card-body" style="padding:0;">
                            <table class="items-table">
                                <thead>
                                    <tr>
                                        <th>Product / SKU</th>
                                        <th>Details</th>
                                        <th>Price</th>
                                        <th>Qty</th>
                                        <th style="text-align:right;">Line Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderItems as $item): 
                                        $price = (float)($item['UnitPrice'] ?? 0);
                                        $qty = (int)($item['Quantity'] ?? 1);
                                        $line = (float)($item['LineTotal'] ?? ($price * $qty));
                                    ?>
                                        <tr>
                                            <td>
                                                <strong style="color:var(--text-hi); display:block;"><?= e($item['ProductName']) ?></strong>
                                                <span style="font-size:10.5px; color:var(--text-mute); font-family:monospace;"><?= e($item['SKU'] ?? '—') ?></span>
                                            </td>
                                            <td><?= e($item['VariantDetails'] ?? 'Standard') ?></td>
                                            <td>$<?= number_format($price, 2) ?></td>
                                            <td><?= $qty ?></td>
                                            <td style="text-align:right; font-weight:700; color:var(--text-hi);">$<?= number_format($line, 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                            <div style="padding: 20px; background: var(--bg-card-alt); border-top:1px solid var(--border);">
                                <div class="info-row"><span class="info-label">Subtotal</span> <span class="info-val">$<?= number_format($calcSubTotal, 2) ?></span></div>
                                <?php if ($calcTax > 0): ?>
                                    <div class="info-row"><span class="info-label">Taxes (GST/PST)</span> <span class="info-val">$<?= number_format($calcTax, 2) ?></span></div>
                                <?php endif; ?>
                                <?php if (!empty($order['CouponCode'])): ?>
                                    <div class="info-row" style="color:var(--green);"><span class="info-label" style="color:var(--green);">Discount (<?= e($order['CouponCode']) ?>)</span> <span class="info-val" style="color:var(--green);">- $<?= number_format($discountAmount, 2) ?></span></div>
                                <?php endif; ?>
                                <div class="info-row" style="border-top: 1px solid var(--border); padding-top:12px;">
                                    <span class="info-label" style="font-size:14px; color:var(--green);">Grand Total</span>
                                    <span class="info-val" style="font-size:16px; color:var(--green); font-weight:800;">$<?= number_format($grandTotal, 2) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CUSTOMER SHIPPING ADDRESS -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Shipping Destination</h2>
                        </div>
                        <div class="card-body">
                            <div class="info-row"><span class="info-label">Recipient:</span> <span class="info-val"><?= e($order['CustomerFullName'] ?? '—') ?></span></div>
                            <div class="info-row"><span class="info-label">Company:</span> <span class="info-val"><?= e($order['CompanyName'] ?? '—') ?></span></div>
                            <div class="info-row"><span class="info-label">Phone:</span> <span class="info-val"><?= e($order['CustomerPhone'] ?? $order['Phone'] ?? '—') ?></span></div>
                            <div class="info-row"><span class="info-label">Address 1:</span> <span class="info-val"><?= e($order['ShippingAddressLine1'] ?? '—') ?></span></div>
                            <div class="info-row"><span class="info-label">Address 2:</span> <span class="info-val"><?= e($order['ShippingAddressLine2'] ?? '—') ?></span></div>
                            <div class="info-row"><span class="info-label">City, State & Zip:</span> <span class="info-val"><?= e($order['ShippingCity'] ?? '') ?>, <?= e($order['ShippingStateProvince'] ?? '') ?> <?= e($order['ShippingPostalCode'] ?? '') ?></span></div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT SIDE: UPDATE STATUS & SHIPMENT -->
                <div>
                    <!-- STATUS UPDATE BOX -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Update Status</h2>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="current_status" value="<?= e($order['OrderStatus'] ?? '') ?>">
                                <label class="form-label" style="font-size:11px; font-weight:700; color:var(--text-mute); display:block; margin-bottom:6px;">Change State:</label>
                                <select name="order_status" class="form-control">
                                    <?php 
                                    $statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled', 'Refunded'];
                                    foreach ($statuses as $st): ?>
                                        <option value="<?= $st ?>" <?= (strtolower($st) === strtolower($order['OrderStatus'] ?? '')) ? 'selected' : '' ?>>
                                            <?= $st ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-primary" style="width:100%;">Update Status</button>
                            </form>
                        </div>
                    </div>

                    <!-- SHIPMENT TRACKING -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Shipment Details</h2>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_shipment">
                                <label class="form-label" style="font-size:11px; font-weight:700; color:var(--text-mute); display:block; margin-bottom:6px;">Courier Name:</label>
                                <input type="text" name="courier_name" class="form-control" placeholder="e.g. DHL, FedEx" value="<?= e($shipment['CourierName'] ?? '') ?>">
                                <label class="form-label" style="font-size:11px; font-weight:700; color:var(--text-mute); display:block; margin-bottom:6px;">Tracking Number:</label>
                                <input type="text" name="tracking_number" class="form-control" placeholder="e.g. TRK123456789" value="<?= e($shipment['TrackingNumber'] ?? '') ?>">
                                <button type="submit" class="btn btn-blue" style="width:100%;">Save Tracking</button>
                            </form>
                        </div>
                    </div>

                    <!-- ORDER TIMELINE -->
                    <?php if (!empty($statusHistory)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h2>Status History</h2>
                        </div>
                        <div class="card-body">
                            <ul class="timeline">
                                <?php foreach ($statusHistory as $hist): ?>
                                    <li class="timeline-item">
                                        <strong style="color:var(--text-hi); font-size:12px;"><?= e($hist['ToStatus']) ?></strong>
                                        <div style="font-size:10px; color:var(--text-mute);">By <?= e($hist['ChangedBy']) ?> • <?= formatDate($hist['CreatedAt']) ?></div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </section>
</main>

<!-- =========================================================
     INTERACTIVE PRINT SELECTION MODAL WITH SHORTCUT KEYS
========================================================= -->
<div class="modal-overlay" id="printModal">
    <div class="modal-box modal-interactive">
        <div class="modal-header">
            <div>
                <h3 class="modal-title">Select Print Format & Paper Size</h3>
                <span class="modal-sub">Mouse se hover karein ya keyboard numbers <strong style="color:#60a5fa;">[1-8]</strong> dabayein, print ke liye <strong style="color:var(--green);">[Enter]</strong> dabayein</span>
            </div>
            <button type="button" class="close-btn" onclick="closePrintModal();" title="Press Esc">&times;</button>
        </div>

        <div class="modal-body-split">
            <!-- LEFT: PAPER OPTIONS LIST -->
            <div class="paper-options-list">
                
                <div class="category-label">Standard Office Paper Sizes</div>
                
                <!-- 1: A4 -->
                <div class="paper-card active" 
                     data-key="1"
                     data-size="a4" 
                     data-name="A4 Standard Sheet" 
                     data-dim="210 × 297 mm (8.27 × 11.69 in)"
                     data-desc="Sabse zyada use hone wala standard format. Full detailed GST tax invoice aur desktop printers ke liye best hai."
                     data-ratio="0.707"
                     data-type="sheet"
                     onclick="selectPaper(this)">
                    <div class="paper-card-icon">📄</div>
                    <div class="paper-card-info">
                        <strong>A4 Standard</strong>
                        <span>210 × 297 mm • Standard Invoicing</span>
                    </div>
                    <span class="key-badge">1</span>
                    <div class="badge-tag">Default</div>
                </div>

                <!-- 2: A5 -->
                <div class="paper-card" 
                     data-key="2"
                     data-size="a5" 
                     data-name="A5 Compact Sheet" 
                     data-dim="148 × 210 mm (5.83 × 8.27 in)"
                     data-desc="A4 ka aadha (half) size. Delivery challan, packaging slip aur mini billing ke liye perfect hai."
                     data-ratio="0.707"
                     data-type="sheet"
                     onclick="selectPaper(this)">
                    <div class="paper-card-icon">📑</div>
                    <div class="paper-card-info">
                        <strong>A5 Compact</strong>
                        <span>148 × 210 mm • Challan & Mini Slip</span>
                    </div>
                    <span class="key-badge">2</span>
                </div>

                <!-- 3: US Letter -->
                <div class="paper-card" 
                     data-key="3"
                     data-size="letter" 
                     data-name="US Letter" 
                     data-dim="215.9 × 279.4 mm (8.5 × 11.0 in)"
                     data-desc="North America ka standard business paper size. US/Canada buyers aur export invoices ke liye recommended hai."
                     data-ratio="0.772"
                     data-type="sheet"
                     onclick="selectPaper(this)">
                    <div class="paper-card-icon">📋</div>
                    <div class="paper-card-info">
                        <strong>US Letter</strong>
                        <span>8.5 × 11.0 in • International Standard</span>
                    </div>
                    <span class="key-badge">3</span>
                </div>

                <!-- 4: US Legal -->
                <div class="paper-card" 
                     data-key="4"
                     data-size="legal" 
                     data-name="US Legal" 
                     data-dim="215.9 × 355.6 mm (8.5 × 14.0 in)"
                     data-desc="Lamba vertical sheet. Zyada items ya lambi terms ko single page me print karne ke liye best hai."
                     data-ratio="0.607"
                     data-type="sheet"
                     onclick="selectPaper(this)">
                    <div class="paper-card-icon">📜</div>
                    <div class="paper-card-info">
                        <strong>US Legal</strong>
                        <span>8.5 × 14.0 in • Long Inventory Sheet</span>
                    </div>
                    <span class="key-badge">4</span>
                </div>

                <!-- 5: A3 -->
                <div class="paper-card" 
                     data-key="5"
                     data-size="a3" 
                     data-name="A3 Large Ledger" 
                     data-dim="297 × 420 mm (11.69 × 16.54 in)"
                     data-desc="A4 se double bada size. Warehouse packing aur master dispatch reports ke liye use hota hai."
                     data-ratio="0.707"
                     data-type="sheet"
                     onclick="selectPaper(this)">
                    <div class="paper-card-icon">📑</div>
                    <div class="paper-card-info">
                        <strong>A3 Large Sheet</strong>
                        <span>297 × 420 mm • Warehouse Manifest</span>
                    </div>
                    <span class="key-badge">5</span>
                </div>

                <div class="category-label" style="margin-top:12px;">Thermal POS Slips (Chhote Size)</div>

                <!-- 6: 80mm POS -->
                <div class="paper-card" 
                     data-key="6"
                     data-size="pos80" 
                     data-name="80mm Thermal Receipt (POS)" 
                     data-dim="80mm Roll (Width: 3.15 in, Continuous)"
                     data-desc="Standard retail billing counter printer (Epson/TVS/Citizen). Layout compact receipt format me adjust ho jata hai."
                     data-ratio="0.48"
                     data-type="thermal"
                     onclick="selectPaper(this)">
                    <div class="paper-card-icon">🧾</div>
                    <div class="paper-card-info">
                        <strong>80mm Thermal POS</strong>
                        <span>80 mm Roll • Counter Slip</span>
                    </div>
                    <span class="key-badge">6</span>
                </div>

                <!-- 7: 58mm POS -->
                <div class="paper-card" 
                     data-key="7"
                     data-size="pos58" 
                     data-name="58mm Mini Pocket Slip" 
                     data-dim="58mm Roll (Width: 2.28 in, Continuous)"
                     data-desc="Handheld Bluetooth POS delivery printer format. Extra compression ke sath chhota slip print karta hai."
                     data-ratio="0.35"
                     data-type="thermal"
                     onclick="selectPaper(this)">
                    <div class="paper-card-icon">🏷️</div>
                    <div class="paper-card-info">
                        <strong>58mm Mini Slip</strong>
                        <span>58 mm Roll • Ultra-Compact POS</span>
                    </div>
                    <span class="key-badge">7</span>
                </div>

                <div class="category-label" style="margin-top:12px;">Custom Customization</div>

                <!-- 8: CUSTOM SIZE -->
                <div class="paper-card" 
                     data-key="8"
                     data-size="custom" 
                     data-name="Custom User-Defined Size" 
                     data-dim="Admin Defined Width × Height"
                     data-desc="Apni pasand ka koi bhi chhota ya bada page size daalein (labels, stickers, special vouchers, ya unique rolls)."
                     data-ratio="0.7"
                     data-type="custom"
                     onclick="selectPaper(this)">
                    <div class="paper-card-icon">⚙️</div>
                    <div class="paper-card-info">
                        <strong>Custom Page Size</strong>
                        <span>Apni marzi ki Width & Height set karein</span>
                    </div>
                    <span class="key-badge">8</span>
                    <div class="badge-tag badge-custom">Manual</div>
                </div>

            </div>

            <!-- RIGHT: LIVE PREVIEW & DETAILS -->
            <div class="preview-stage-container">
                
                <!-- CUSTOM SIZE INPUTS -->
                <div class="custom-inputs-box" id="customControls">
                    <span style="font-size:11px; font-weight:700; color:var(--text-hi); text-transform:uppercase; letter-spacing:0.5px;">Enter Dimensions:</span>
                    <div class="custom-row">
                        <input type="number" id="customWidth" class="form-control" placeholder="Width" value="100" min="20" max="600" oninput="updateCustomDimensions()">
                        <input type="number" id="customHeight" class="form-control" placeholder="Height" value="150" min="20" max="800" oninput="updateCustomDimensions()">
                        <select id="customUnit" class="form-control" style="width:80px;" onchange="updateCustomDimensions()">
                            <option value="mm" selected>mm</option>
                            <option value="in">in</option>
                            <option value="cm">cm</option>
                        </select>
                    </div>
                </div>

                <!-- PREVIEW STAGE -->
                <div class="preview-stage">
                    <div class="paper-sheet-mockup" id="previewMockup">
                        <div class="mockup-header"></div>
                        <div class="mockup-line" style="width: 45%;"></div>
                        <div class="mockup-line" style="width: 70%; margin-bottom: 8px;"></div>
                        <div class="mockup-divider"></div>
                        <div class="mockup-row"><span></span><span></span></div>
                        <div class="mockup-row"><span></span><span></span></div>
                        <div class="mockup-row"><span></span><span></span></div>
                        <div class="mockup-divider"></div>
                        <div class="mockup-total"></div>
                    </div>
                </div>

                <div class="preview-details">
                    <h4 id="previewTitle">A4 Standard Sheet</h4>
                    <div class="preview-dim" id="previewDim">210 × 297 mm (8.27 × 11.69 in)</div>
                    <p class="preview-desc" id="previewDesc">
                        Sabse zyada use hone wala standard format. Full detailed GST tax invoice aur desktop printers ke liye best hai.
                    </p>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <span style="font-size:12px; color:var(--text-mute);">
                Selected Size: <strong id="selectedLabel" style="color:var(--text-hi);">A4 Standard Sheet</strong>
            </span>
            <div style="display:flex; gap:10px; align-items:center;">
                <span style="font-size:11px; color:var(--text-mute); display:flex; gap:4px; align-items:center;">
                    <span class="key-badge">Esc</span> Close
                    <span class="key-badge" style="margin-left:6px;">Enter</span> Print
                </span>
                <button type="button" class="btn" onclick="closePrintModal();">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="executePrint();">
                    🖨 Confirm & Print
                </button>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================
     CLEAN DEDICATED INVOICE PRINT TEMPLATE (WHITE PAPER)
========================================================= -->
<div id="printableInvoice">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #222; padding-bottom:12px; margin-bottom:15px;">
        <div>
            <h1 style="margin:0; font-size:22px; font-weight:900; letter-spacing:1px; text-transform:uppercase;">GATEWAYLINEN</h1>
            <p style="margin:3px 0 0; font-size:11px; color:#555;">Commercial Textile & Linen Supply</p>
        </div>
        <div style="text-align:right;">
            <h2 style="margin:0; font-size:18px; color:#111;">INVOICE</h2>
            <div style="font-size:11px; margin-top:3px;">
                <strong>Order #:</strong> <?= e($order['OrderNumber'] ?? $order['OrderId']) ?><br>
                <strong>Date:</strong> <?= formatDate($order['CreatedAt'] ?? '') ?>
            </div>
        </div>
    </div>

    <!-- ADDRESSES -->
    <div style="display:flex; justify-content:space-between; margin-bottom:18px; font-size:11px; line-height:1.4;">
        <div style="width:48%;">
            <strong style="text-transform:uppercase; color:#666; font-size:10px; display:block; margin-bottom:4px;">Billed / Shipped To:</strong>
            <strong style="font-size:12px;"><?= e($order['CustomerFullName'] ?? 'Customer') ?></strong><br>
            <?php if(!empty($order['CompanyName'])): ?><?= e($order['CompanyName']) ?><br><?php endif; ?>
            <?= e($order['ShippingAddressLine1'] ?? '') ?> <?= e($order['ShippingAddressLine2'] ?? '') ?><br>
            <?= e($order['ShippingCity'] ?? '') ?>, <?= e($order['ShippingStateProvince'] ?? '') ?> <?= e($order['ShippingPostalCode'] ?? '') ?><br>
            Phone: <?= e($order['CustomerPhone'] ?? $order['Phone'] ?? '—') ?>
        </div>
        <div style="width:48%; text-align:right;" class="hide-receipt">
            <strong style="text-transform:uppercase; color:#666; font-size:10px; display:block; margin-bottom:4px;">Shipment Info:</strong>
            Status: <strong><?= e($order['OrderStatus'] ?? 'Pending') ?></strong><br>
            Courier: <?= e($shipment['CourierName'] ?? 'Standard Ground') ?><br>
            Tracking: <?= e($shipment['TrackingNumber'] ?? 'Pending Assignment') ?>
        </div>
    </div>

    <!-- TABLE -->
    <table style="width:100%; border-collapse:collapse; margin-bottom:15px; font-size:11px;">
        <thead>
            <tr style="border-top:1px solid #000; border-bottom:1px solid #000; background:#f9f9f9;">
                <th style="padding:6px; text-align:left;">Item</th>
                <th style="padding:6px; text-align:left;" class="hide-receipt">Details</th>
                <th style="padding:6px; text-align:right;">Price</th>
                <th style="padding:6px; text-align:center;">Qty</th>
                <th style="padding:6px; text-align:right;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orderItems as $item): 
                $p = (float)($item['UnitPrice'] ?? 0);
                $q = (int)($item['Quantity'] ?? 1);
                $tot = (float)($item['LineTotal'] ?? ($p * $q));
            ?>
            <tr style="border-bottom:1px solid #e5e5e5;">
                <td style="padding:6px;">
                    <strong><?= e($item['ProductName']) ?></strong>
                    <div style="font-size:9.5px; color:#666;" class="hide-receipt">SKU: <?= e($item['SKU'] ?? '—') ?></div>
                </td>
                <td style="padding:6px;" class="hide-receipt"><?= e($item['VariantDetails'] ?? '—') ?></td>
                <td style="padding:6px; text-align:right;">$<?= number_format($p, 2) ?></td>
                <td style="padding:6px; text-align:center;"><?= $q ?></td>
                <td style="padding:6px; text-align:right;">$<?= number_format($tot, 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- TOTALS -->
    <div style="display:flex; justify-content:flex-end;">
        <div style="width:240px; font-size:11px;">
            <div style="display:flex; justify-content:space-between; padding:3px 0;">
                <span>Subtotal:</span>
                <span>$<?= number_format($calcSubTotal, 2) ?></span>
            </div>
            <?php if ($calcTax > 0): ?>
            <div style="display:flex; justify-content:space-between; padding:3px 0; color:#555;">
                <span>Taxes:</span>
                <span>$<?= number_format($calcTax, 2) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($order['CouponCode'])): ?>
            <div style="display:flex; justify-content:space-between; padding:3px 0; color:#059669;">
                <span>Discount:</span>
                <span>-$<?= number_format($discountAmount, 2) ?></span>
            </div>
            <?php endif; ?>
            <div style="display:flex; justify-content:space-between; border-top:1.5px solid #000; padding:6px 0; font-size:13px; font-weight:800; margin-top:4px;">
                <span>Total Amount:</span>
                <span>$<?= number_format($grandTotal, 2) ?></span>
            </div>
        </div>
    </div>

    <!-- SIGNATURE FOOTER -->
    <div class="hide-receipt" style="margin-top:40px; border-top:1px dashed #ccc; padding-top:15px; font-size:10px; color:#666; display:flex; justify-content:space-between; align-items:flex-end;">
        <div>
            Thank you for your business!<br>
            For support and inquiries: support@gatewaylinen.com
        </div>
        <div style="text-align:center;">
            <div style="width:160px; border-bottom:1px solid #000; margin-bottom:4px;"></div>
            Authorized Signature
        </div>
    </div>
</div>

<!-- DYNAMIC PRINT STYLE HOLDER -->
<style id="customPrintStyle"></style>

<script>
let currentSelectedSize = 'a4';

function openPrintModal() {
    document.getElementById('printModal').classList.add('active');
}

function closePrintModal() {
    document.getElementById('printModal').classList.remove('active');
}

function selectPaper(cardElement) {
    document.querySelectorAll('.paper-card').forEach(c => c.classList.remove('active'));
    cardElement.classList.add('active');

    const size = cardElement.getAttribute('data-size');
    const name = cardElement.getAttribute('data-name');
    const dim = cardElement.getAttribute('data-dim');
    const desc = cardElement.getAttribute('data-desc');
    const type = cardElement.getAttribute('data-type');
    const ratio = parseFloat(cardElement.getAttribute('data-ratio'));

    currentSelectedSize = size;

    // Toggle custom inputs
    const customBox = document.getElementById('customControls');
    if (size === 'custom') {
        customBox.classList.add('active');
        document.getElementById('customWidth').focus();
        updateCustomDimensions();
        return;
    } else {
        customBox.classList.remove('active');
    }

    // Info Labels Update
    document.getElementById('previewTitle').innerText = name;
    document.getElementById('previewDim').innerText = dim;
    document.getElementById('previewDesc').innerText = desc;
    document.getElementById('selectedLabel').innerText = name;

    // Live Paper Mockup Morph
    const mockup = document.getElementById('previewMockup');
    const baseHeight = 210;
    let targetWidth = Math.round(baseHeight * ratio);
    
    if (targetWidth > 180) targetWidth = 180;
    if (targetWidth < 60) targetWidth = 60;

    mockup.style.width = targetWidth + 'px';
    mockup.style.height = (type === 'thermal') ? '220px' : baseHeight + 'px';

    if (type === 'thermal') {
        mockup.classList.add('thermal-mode');
    } else {
        mockup.classList.remove('thermal-mode');
    }
}

// Live update custom dimension inputs
function updateCustomDimensions() {
    const w = parseFloat(document.getElementById('customWidth').value) || 100;
    const h = parseFloat(document.getElementById('customHeight').value) || 150;
    const unit = document.getElementById('customUnit').value;

    const ratio = w / h;
    const mockup = document.getElementById('previewMockup');

    // Auto-scale mockup on screen
    let targetH = 200;
    let targetW = Math.round(targetH * ratio);

    if (targetW > 220) {
        targetW = 220;
        targetH = Math.round(targetW / ratio);
    }
    if (targetW < 50) targetW = 50;

    mockup.style.width = targetW + 'px';
    mockup.style.height = targetH + 'px';
    mockup.classList.remove('thermal-mode');

    // Label updates
    document.getElementById('previewTitle').innerText = 'Custom Paper (' + w + unit + ' × ' + h + unit + ')';
    document.getElementById('previewDim').innerText = w + ' ' + unit + ' × ' + h + ' ' + unit + ' (Aspect Ratio: ' + ratio.toFixed(2) + ')';
    document.getElementById('previewDesc').innerText = 'User custom sizing. Browser print window me automatically yehi size pass hoga.';
    document.getElementById('selectedLabel').innerText = 'Custom: ' + w + unit + ' × ' + h + unit;
}

// Cursor hover hone par real-time interactive preview
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.paper-card').forEach(card => {
        card.addEventListener('mouseenter', () => {
            selectPaper(card);
        });
    });
});

/* =========================================================
   KEYBOARD SHORTCUTS INTEGRATION
========================================================= */
window.addEventListener('keydown', (e) => {
    const modal = document.getElementById('printModal');
    const isModalOpen = modal.classList.contains('active');

    // 1. Ctrl + P or Cmd + P to open print modal directly
    if ((e.ctrlKey || e.metaKey) && (e.key.toLowerCase() === 'p')) {
        e.preventDefault();
        openPrintModal();
        return;
    }

    // Modal ke band hone par baki shortcuts execute na hon
    if (!isModalOpen) return;

    // 2. Escape to close modal
    if (e.key === 'Escape') {
        e.preventDefault();
        closePrintModal();
        return;
    }

    // 3. Enter to Confirm & Print
    if (e.key === 'Enter') {
        e.preventDefault();
        executePrint();
        return;
    }

    // 4. Input field me focus hone par numbers shortcut block karein (taaki custom size type ho sake)
    if (['INPUT', 'SELECT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
        return;
    }

    // 5. Number keys 1-8 to switch sizes
    if (e.key >= '1' && e.key <= '8') {
        const targetCard = document.querySelector(`.paper-card[data-key="${e.key}"]`);
        if (targetCard) {
            e.preventDefault();
            selectPaper(targetCard);
        }
    }
});

function executePrint() {
    const styleHolder = document.getElementById('customPrintStyle');
    styleHolder.innerHTML = '';

    // Remove previous sizing classes
    document.body.classList.remove(
        'paper-a3', 'paper-a4', 'paper-a5', 
        'paper-letter', 'paper-legal', 
        'paper-pos80', 'paper-pos58', 'paper-custom'
    );
    
    if (currentSelectedSize === 'custom') {
        const w = parseFloat(document.getElementById('customWidth').value) || 100;
        const h = parseFloat(document.getElementById('customHeight').value) || 150;
        const unit = document.getElementById('customUnit').value;

        // Dynamic @page rule for custom dimensions
        styleHolder.innerHTML = `
            @media print {
                @page {
                    size: ${w}${unit} ${h}${unit};
                    margin: 5mm;
                }
                #printableInvoice {
                    width: 100% !important;
                }
            }
        `;
        document.body.classList.add('paper-custom');
    } else {
        document.body.classList.add('paper-' + currentSelectedSize);
    }
    
    closePrintModal();
    
    setTimeout(() => {
        window.print();
    }, 250);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>