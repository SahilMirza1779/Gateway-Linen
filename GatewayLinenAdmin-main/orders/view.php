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
        $notes = trim($_POST['status_notes'] ?? '');

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

        // Check if shipment already exists
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
| FETCH ORDER DETAILS
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

/*
|--------------------------------------------------------------------------
| FETCH ORDER ITEMS
|--------------------------------------------------------------------------
*/
$itemsSql = "SELECT * FROM dbo.OrderItems WHERE OrderId = ? ORDER BY OrderItemId ASC";
$itemsStmt = sqlsrv_query($conn, $itemsSql, [$orderId]);
$orderItems = [];
if ($itemsStmt !== false) {
    while ($row = sqlsrv_fetch_array($itemsStmt, SQLSRV_FETCH_ASSOC)) {
        $orderItems[] = $row;
    }
    sqlsrv_free_stmt($itemsStmt);
}

/*
|--------------------------------------------------------------------------
| FETCH SHIPMENT INFO
|--------------------------------------------------------------------------
*/
$shipSql = "SELECT TOP 1 * FROM dbo.OrderShipments WHERE OrderId = ? ORDER BY ShipmentId DESC";
$shipStmt = sqlsrv_query($conn, $shipSql, [$orderId]);
$shipment = ($shipStmt !== false) ? sqlsrv_fetch_array($shipStmt, SQLSRV_FETCH_ASSOC) : null;
if ($shipStmt !== false) sqlsrv_free_stmt($shipStmt);

/*
|--------------------------------------------------------------------------
| FETCH STATUS HISTORY
|--------------------------------------------------------------------------
*/
$histListSql = "SELECT * FROM dbo.OrderStatusHistory WHERE OrderId = ? ORDER BY CreatedAt DESC";
$histListStmt = sqlsrv_query($conn, $histListSql, [$orderId]);
$statusHistory = [];
if ($histListStmt !== false) {
    while ($hRow = sqlsrv_fetch_array($histListStmt, SQLSRV_FETCH_ASSOC)) {
        $statusHistory[] = $hRow;
    }
    sqlsrv_free_stmt($histListStmt);
}

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
    --purple: #8b5cf6;
    --purple-soft: rgba(139,92,246,.12);
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
    padding: 0 0 40px;
}

/* HEADER */
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

.page-title {
    margin: 0;
    color: var(--text-hi);
    font-size: 26px;
    font-weight: 800;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 16px;
    border-radius: 8px;
    font-size: 11.5px;
    font-weight: 700;
    text-decoration: none;
    border: 1px solid var(--border);
    background: var(--bg-input);
    color: var(--text-body);
    cursor: pointer;
    transition: .15s ease;
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

.btn-blue { color: var(--blue) !important; }

/* NOTICES */
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

/* LAYOUT */
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
    font-size: 14px;
    font-weight: 800;
    color: var(--text-hi);
    text-transform: uppercase;
    letter-spacing: .5px;
}

.card-body {
    padding: 20px;
}

/* TABLE */
.items-table {
    width: 100%;
    border-collapse: collapse;
}

.items-table th {
    background: var(--bg-header);
    padding: 10px 14px;
    color: var(--text-mute);
    font-size: 10px;
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

.items-table tfoot td {
    font-weight: 700;
    color: var(--text-hi);
    border-bottom: none;
    padding-top: 14px;
}

/* DETAILS LIST */
.info-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid var(--border-soft);
    font-size: 12px;
}
.info-row:last-child { border-bottom: none; }
.info-label { color: var(--text-mute); font-weight: 600; }
.info-val { color: var(--text-hi); font-weight: 700; text-align: right; }

/* STATUS BADGE */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
}

.form-control {
    width: 100%;
    height: 38px;
    background: var(--bg-input);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text-hi);
    padding: 0 12px;
    margin-bottom: 12px;
    font-size: 12px;
}

.timeline {
    list-style: none;
    padding: 0;
    margin: 0;
}
.timeline-item {
    position: relative;
    padding-left: 20px;
    margin-bottom: 12px;
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

@media (max-width: 950px) {
    .order-layout { grid-template-columns: 1fr; }
}

@media print {
    .header-actions, .sidebar, .card-actions, #adminSidebar, .btn { display: none !important; }
    .main-content, .main { margin-left: 0 !important; padding: 0 !important; }
}
</style>

<main class="main">
    <section class="content">
        <div class="order-detail-page">

            <!-- HEADER -->
            <div class="page-header">
                <div>
                    <div class="breadcrumb">
                        <span>Management</span> / <a href="index.php" style="color:var(--text-mute); text-decoration:none;">Orders</a> / <span class="current"><?= e($order['OrderNumber'] ?? 'Order Details') ?></span>
                    </div>
                    <h1 class="page-title">Order #<?= e($order['OrderNumber'] ?? $order['OrderId']) ?></h1>
                    <span style="font-size:12px; color:var(--text-mute);">Placed on <?= formatDate($order['CreatedAt'] ?? '') ?></span>
                </div>
                <div class="header-actions">
                    <button type="button" class="btn btn-blue" onclick="window.print();">🖨 Print Invoice</button>
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
                            <span class="status-badge" style="background:var(--green-soft); color:var(--green);">
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
                                    <?php 
                                    $subTotal = 0;
                                    $totalTax = 0;
                                    foreach ($orderItems as $item): 
                                        $price = (float)($item['UnitPrice'] ?? 0);
                                        $qty = (int)($item['Quantity'] ?? 1);
                                        $line = (float)($item['LineTotal'] ?? ($price * $qty));
                                        $gst = (float)($item['GstAmount'] ?? 0);
                                        $pst = (float)($item['PstAmount'] ?? 0);
                                        $subTotal += $line;
                                        $totalTax += ($gst + $pst);
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
                                <tfoot>
                                    <tr>
                                        <td colspan="4" style="text-align:right;">Subtotal:</td>
                                        <td style="text-align:right;">$<?= number_format($subTotal, 2) ?></td>
                                    </tr>
                                    <?php if ($totalTax > 0): ?>
                                    <tr>
                                        <td colspan="4" style="text-align:right; color:var(--text-mute);">Estimated Taxes:</td>
                                        <td style="text-align:right; color:var(--text-mute);">$<?= number_format($totalTax, 2) ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (!empty($order['CouponCode'])): ?>
                                    <tr>
                                        <td colspan="4" style="text-align:right; color:var(--green);">Discount (<?= e($order['CouponCode']) ?>):</td>
                                        <td style="text-align:right; color:var(--green);">- $<?= number_format((float)($order['DiscountAmount'] ?? 0), 2) ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td colspan="4" style="text-align:right; font-size:14px; color:var(--green);">Grand Total:</td>
                                        <td style="text-align:right; font-size:15px; color:var(--green); font-weight:800;">
                                            $<?= number_format((float)($order['TotalAmount'] ?? $order['GrandTotal'] ?? $subTotal), 2) ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
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
                                <label class="form-label">Change State:</label>
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
                                <label class="form-label">Courier Name:</label>
                                <input type="text" name="courier_name" class="form-control" placeholder="e.g. DHL, FedEx" value="<?= e($shipment['CourierName'] ?? '') ?>">
                                <label class="form-label">Tracking Number:</label>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>