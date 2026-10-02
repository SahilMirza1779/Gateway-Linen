<?php
session_start();

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

/* ---------------------------------------------------------
   UPDATE ORDER STATUS
--------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'update_status') {
        $newStatus     = trim($_POST['order_status'] ?? '');
        $currentStatus = trim($_POST['current_status'] ?? '');

        if (!empty($newStatus) && $newStatus !== $currentStatus) {
            sqlsrv_begin_transaction($conn);

            $upSql = "UPDATE dbo.Orders SET OrderStatus = ? WHERE OrderId = ?";
            $upStmt = sqlsrv_query($conn, $upSql, [$newStatus, $orderId]);

            $histSql = "INSERT INTO dbo.OrderStatusHistory
                        (OrderId, FromStatus, ToStatus, ChangedBy, NotifiedCustomer, CreatedAt)
                        VALUES (?, ?, ?, ?, 1, GETDATE())";
            $histStmt = sqlsrv_query(
                $conn,
                $histSql,
                [$orderId, $currentStatus, $newStatus, $_SESSION['admin_name']]
            );

            if ($upStmt !== false && $histStmt !== false) {
                sqlsrv_commit($conn);
                $message = 'Order status updated successfully.';
            } else {
                sqlsrv_rollback($conn);
                $error = 'Failed to update order status.';
            }
        }
    }

    /* ---------------------------------------------------------
       UPDATE SHIPMENT
    --------------------------------------------------------- */
    if ($_POST['action'] === 'update_shipment') {
        $courierName    = trim($_POST['courier_name'] ?? '');
        $trackingNumber = trim($_POST['tracking_number'] ?? '');

        $chkShip = sqlsrv_query(
            $conn,
            "SELECT ShipmentId FROM dbo.OrderShipments WHERE OrderId = ?",
            [$orderId]
        );

        if ($chkShip !== false && $shipRow = sqlsrv_fetch_array($chkShip, SQLSRV_FETCH_ASSOC)) {
            $shipSql = "UPDATE dbo.OrderShipments
                        SET CourierName = ?, TrackingNumber = ?, ShippedDate = GETDATE()
                        WHERE OrderId = ?";
            $shipParams = [$courierName, $trackingNumber, $orderId];
        } else {
            $shipSql = "INSERT INTO dbo.OrderShipments
                        (OrderId, CourierName, TrackingNumber, ShippedDate)
                        VALUES (?, ?, ?, GETDATE())";
            $shipParams = [$orderId, $courierName, $trackingNumber];
        }

        $res = sqlsrv_query($conn, $shipSql, $shipParams);

        if ($res !== false) {
            $message = 'Shipment details updated successfully.';
        } else {
            $error = 'Failed to update shipment details.';
        }
    }
}

/* ---------------------------------------------------------
   FETCH ORDER
--------------------------------------------------------- */
$orderStmt = sqlsrv_query(
    $conn,
    "SELECT * FROM dbo.Orders WHERE OrderId = ?",
    [$orderId]
);

$order = ($orderStmt !== false)
    ? sqlsrv_fetch_array($orderStmt, SQLSRV_FETCH_ASSOC)
    : null;

if ($orderStmt !== false) {
    sqlsrv_free_stmt($orderStmt);
}

if (!$order) {
    header('Location: index.php?error=' . urlencode('Order not found.'));
    exit;
}

/* ---------------------------------------------------------
   FETCH ITEMS
--------------------------------------------------------- */
$orderItems = [];

$itemsStmt = sqlsrv_query(
    $conn,
    "SELECT * FROM dbo.OrderItems WHERE OrderId = ? ORDER BY OrderItemId ASC",
    [$orderId]
);

if ($itemsStmt !== false) {
    while ($row = sqlsrv_fetch_array($itemsStmt, SQLSRV_FETCH_ASSOC)) {
        $orderItems[] = $row;
    }
    sqlsrv_free_stmt($itemsStmt);
}

/* ---------------------------------------------------------
   FETCH SHIPMENT
--------------------------------------------------------- */
$shipment = null;

$shipStmt = sqlsrv_query(
    $conn,
    "SELECT TOP 1 * FROM dbo.OrderShipments
     WHERE OrderId = ?
     ORDER BY ShipmentId DESC",
    [$orderId]
);

if ($shipStmt !== false) {
    $shipment = sqlsrv_fetch_array($shipStmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($shipStmt);
}

/* ---------------------------------------------------------
   FETCH STATUS HISTORY
--------------------------------------------------------- */
$statusHistory = [];

$histListStmt = sqlsrv_query(
    $conn,
    "SELECT * FROM dbo.OrderStatusHistory
     WHERE OrderId = ?
     ORDER BY CreatedAt DESC",
    [$orderId]
);

if ($histListStmt !== false) {
    while ($hRow = sqlsrv_fetch_array($histListStmt, SQLSRV_FETCH_ASSOC)) {
        $statusHistory[] = $hRow;
    }
    sqlsrv_free_stmt($histListStmt);
}

/* ---------------------------------------------------------
   FINANCIAL CALCULATIONS
--------------------------------------------------------- */
$calcSubTotal = 0;
$calcTax = 0;

foreach ($orderItems as $it) {
    $p = (float)($it['UnitPrice'] ?? 0);
    $q = (int)($it['Quantity'] ?? 1);

    $calcSubTotal += (float)($it['LineTotal'] ?? ($p * $q));
    $calcTax += (float)($it['GstAmount'] ?? 0);
    $calcTax += (float)($it['PstAmount'] ?? 0);
}

$discountAmount = (float)($order['DiscountAmount'] ?? 0);

$grandTotal = (float)(
    $order['TotalAmount']
    ?? $order['GrandTotal']
    ?? ($calcSubTotal + $calcTax - $discountAmount)
);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<style>
/* =========================================================
   GATEWAYLINEN ORDER DETAILS
   SAME LIGHT / DARK THEME SYSTEM AS CATEGORIES PAGE
========================================================= */

:root {
    --page-bg: #f4f7fb;
    --card-bg: #ffffff;
    --card-alt: #f8fafc;
    --input-bg: #ffffff;
    --border: #d9e2ec;
    --border-soft: #e7edf3;

    --text-main: #0f2742;
    --text-body: #526b84;
    --text-muted: #7890a6;

    --primary: #00a878;
    --primary-dark: #008f69;
    --primary-soft: #e7f7f2;

    --blue: #2563eb;
    --blue-soft: #eff6ff;

    --amber: #f59e0b;
    --amber-soft: #fff7e6;

    --red: #dc2626;
    --red-soft: #fff1f2;

    --shadow: 0 8px 24px rgba(15, 39, 66, .06);
    --shadow-lg: 0 18px 45px rgba(15, 39, 66, .14);
}

/* Dark mode */
html[data-theme="dark"] {
    --page-bg: #0b121a;
    --card-bg: #111b26;
    --card-alt: #0e1822;
    --input-bg: #0d1620;
    --border: #243548;
    --border-soft: #1a2a3a;

    --text-main: #f3f7fb;
    --text-body: #a9b9c9;
    --text-muted: #71879a;

    --primary: #10b981;
    --primary-dark: #059669;
    --primary-soft: rgba(16,185,129,.12);

    --blue: #60a5fa;
    --blue-soft: rgba(59,130,246,.12);

    --amber-soft: rgba(245,158,11,.12);
    --red-soft: rgba(239,68,68,.12);

    --shadow: 0 8px 24px rgba(0,0,0,.18);
    --shadow-lg: 0 18px 45px rgba(0,0,0,.35);
}

html,
body,
.main,
.content {
    background: var(--page-bg) !important;
    color: var(--text-body) !important;
    transition: background .2s ease, color .2s ease;
}

.order-detail-page {
    width: 100%;
    max-width: 1330px;
    margin: 0 auto;
    padding: 0 0 45px;
}

/* =========================================================
   PAGE HEADER
========================================================= */
.page-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    padding-bottom: 18px;
    margin-bottom: 18px;
    border-bottom: 1px solid var(--border);
    flex-wrap: wrap;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 7px;
    margin-bottom: 8px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .35px;
    color: var(--text-muted);
}

.breadcrumb a {
    color: var(--primary) !important;
    text-decoration: none;
}

.breadcrumb .current {
    color: var(--primary);
}

.page-title {
    margin: 0 0 4px;
    color: var(--text-main);
    font-size: 25px;
    line-height: 1.1;
    font-weight: 850;
}

.page-subtitle {
    color: var(--text-muted);
    font-size: 11px;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

/* =========================================================
   BUTTONS
========================================================= */
.btn {
    min-height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 13px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--card-bg);
    color: var(--text-main);
    font-size: 11px;
    font-weight: 750;
    text-decoration: none;
    cursor: pointer;
    transition: .15s ease;
    box-sizing: border-box;
}

.btn:hover {
    border-color: var(--primary);
    color: var(--primary);
    background: var(--primary-soft);
}

.btn-primary {
    background: var(--primary);
    border-color: var(--primary);
    color: #fff !important;
    box-shadow: 0 5px 14px rgba(0,168,120,.16);
}

.btn-primary:hover {
    background: var(--primary-dark);
    border-color: var(--primary-dark);
    color: #fff !important;
}

.btn-blue {
    background: var(--blue-soft);
    border-color: rgba(37,99,235,.22);
    color: var(--blue) !important;
}

.btn-blue:hover {
    background: var(--blue);
    color: #fff !important;
    border-color: var(--blue);
}

.btn-theme {
    min-width: 92px;
}

/* =========================================================
   NOTICES
========================================================= */
.notice-success,
.notice-error {
    padding: 11px 14px;
    margin-bottom: 16px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
}

.notice-success {
    background: var(--primary-soft);
    border: 1px solid rgba(0,168,120,.25);
    color: var(--primary);
}

.notice-error {
    background: var(--red-soft);
    border: 1px solid rgba(220,38,38,.22);
    color: var(--red);
}

/* =========================================================
   TWO COLUMN DETAIL LAYOUT
========================================================= */
.order-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 350px;
    gap: 18px;
}

/* =========================================================
   CARDS
========================================================= */
.card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 10px;
    box-shadow: var(--shadow);
    overflow: hidden;
    margin-bottom: 18px;
    transition: background .2s ease, border .2s ease, box-shadow .2s ease;
}

.card:hover {
    box-shadow: 0 10px 28px rgba(15,39,66,.08);
}

html[data-theme="dark"] .card:hover {
    box-shadow: 0 12px 30px rgba(0,0,0,.22);
}

.card-header {
    min-height: 52px;
    padding: 0 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    border-bottom: 1px solid var(--border);
    box-sizing: border-box;
}

.card-header h2 {
    margin: 0;
    color: var(--text-main);
    font-size: 12px;
    font-weight: 850;
    letter-spacing: .25px;
}

.card-header small {
    color: var(--text-muted);
    font-size: 10px;
}

.card-body {
    padding: 18px;
}

/* =========================================================
   STATUS BADGES
========================================================= */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 9px;
    border-radius: 20px;
    background: var(--primary-soft);
    color: var(--primary);
    font-size: 9px;
    font-weight: 850;
    text-transform: uppercase;
}

.status-badge::before {
    content: "";
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: currentColor;
}

/* =========================================================
   ITEMS TABLE
========================================================= */
.items-table {
    width: 100%;
    border-collapse: collapse;
}

.items-table th {
    padding: 10px 13px;
    background: var(--card-alt);
    border-bottom: 1px solid var(--border);
    color: var(--text-muted);
    font-size: 9px;
    font-weight: 850;
    letter-spacing: .35px;
    text-transform: uppercase;
    text-align: left;
}

.items-table td {
    padding: 12px 13px;
    border-bottom: 1px solid var(--border-soft);
    color: var(--text-body);
    font-size: 11px;
    vertical-align: middle;
}

.items-table tbody tr:last-child td {
    border-bottom: 0;
}

.items-table tbody tr:hover {
    background: var(--primary-soft);
}

.product-name {
    display: block;
    margin-bottom: 2px;
    color: var(--text-main);
    font-size: 11.5px;
    font-weight: 800;
}

.sku {
    color: var(--text-muted);
    font-size: 9.5px;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
}

/* =========================================================
   FINANCIAL SUMMARY
========================================================= */
.financial-box {
    padding: 18px;
    background: var(--card-alt);
    border-top: 1px solid var(--border);
}

.info-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 8px 0;
    border-bottom: 1px solid var(--border-soft);
    font-size: 11px;
}

.info-row:last-child {
    border-bottom: 0;
}

.info-label {
    color: var(--text-muted);
    font-weight: 650;
}

.info-val {
    color: var(--text-main);
    font-weight: 750;
    text-align: right;
}

.total-row {
    margin-top: 5px;
    padding-top: 12px;
    border-top: 1px solid var(--border);
    border-bottom: 0;
}

.total-row .info-label,
.total-row .info-val {
    color: var(--primary);
    font-size: 14px;
    font-weight: 850;
}

/* =========================================================
   FORMS
========================================================= */
.form-label {
    display: block;
    margin: 0 0 6px;
    color: var(--text-muted);
    font-size: 10px;
    font-weight: 800;
}

.form-control {
    width: 100%;
    height: 38px;
    margin-bottom: 12px;
    padding: 0 11px;
    box-sizing: border-box;
    border: 1px solid var(--border);
    border-radius: 7px;
    outline: none;
    background: var(--input-bg);
    color: var(--text-main);
    font-size: 11px;
    transition: .15s ease;
}

.form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px var(--primary-soft);
}

select.form-control option {
    background: var(--card-bg);
    color: var(--text-main);
}

/* =========================================================
   TIMELINE
========================================================= */
.timeline {
    list-style: none;
    padding: 0;
    margin: 0;
}

.timeline-item {
    position: relative;
    margin: 0 0 15px 5px;
    padding: 0 0 0 17px;
    border-left: 1px solid var(--border);
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-item::before {
    content: "";
    position: absolute;
    left: -4px;
    top: 3px;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--primary);
    box-shadow: 0 0 0 3px var(--primary-soft);
}

.timeline-status {
    color: var(--text-main);
    font-size: 11px;
    font-weight: 800;
}

.timeline-meta {
    margin-top: 3px;
    color: var(--text-muted);
    font-size: 9.5px;
}

/* =========================================================
   PRINT MODAL
========================================================= */
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 99999;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(4,10,17,.65);
    backdrop-filter: blur(8px);
}

html[data-theme="dark"] .modal-overlay {
    background: rgba(0,0,0,.82);
}

.modal-overlay.active {
    display: flex;
}

.modal-interactive {
    width: 100%;
    max-width: 900px;
    max-height: 92vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    box-shadow: var(--shadow-lg);
}

.modal-header {
    min-height: 62px;
    padding: 12px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--border);
}

.modal-title {
    margin: 0;
    color: var(--text-main);
    font-size: 14px;
    font-weight: 850;
}

.modal-sub {
    display: block;
    margin-top: 3px;
    color: var(--text-muted);
    font-size: 10px;
}

.close-btn {
    width: 32px;
    height: 32px;
    border: 1px solid var(--border);
    border-radius: 7px;
    background: var(--card-bg);
    color: var(--text-muted);
    font-size: 20px;
    cursor: pointer;
}

.close-btn:hover {
    color: var(--red);
    border-color: var(--red);
}

.modal-body-split {
    height: 500px;
    display: grid;
    grid-template-columns: 1fr 365px;
    overflow: hidden;
}

.paper-options-list {
    padding: 15px;
    overflow-y: auto;
    border-right: 1px solid var(--border);
    background: var(--card-alt);
}

.category-label {
    margin: 8px 4px 6px;
    color: var(--text-muted);
    font-size: 9px;
    font-weight: 850;
    text-transform: uppercase;
    letter-spacing: .6px;
}

.paper-card {
    display: flex;
    align-items: center;
    gap: 10px;
    min-height: 53px;
    margin-bottom: 7px;
    padding: 8px 10px;
    box-sizing: border-box;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--card-bg);
    cursor: pointer;
    transition: .15s ease;
}

.paper-card:hover {
    border-color: var(--primary);
    background: var(--primary-soft);
}

.paper-card.active {
    border-color: var(--primary);
    background: var(--primary-soft);
}

.paper-card-icon {
    width: 31px;
    height: 31px;
    flex: 0 0 31px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    border: 1px solid var(--border);
    background: var(--card-alt);
    font-size: 15px;
}

.paper-card-info {
    min-width: 0;
    flex: 1;
}

.paper-card-info strong {
    display: block;
    color: var(--text-main);
    font-size: 11px;
    font-weight: 800;
}

.paper-card-info span {
    display: block;
    margin-top: 2px;
    color: var(--text-muted);
    font-size: 9px;
}

.key-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 19px;
    height: 19px;
    padding: 0 5px;
    box-sizing: border-box;
    border: 1px solid var(--border);
    border-radius: 4px;
    background: var(--card-alt);
    color: var(--text-muted);
    font-family: monospace;
    font-size: 9px;
    font-weight: 800;
}

.badge-tag {
    padding: 3px 7px;
    border-radius: 20px;
    background: var(--primary-soft);
    color: var(--primary);
    font-size: 8px;
    font-weight: 850;
    text-transform: uppercase;
}

.badge-custom {
    background: var(--blue-soft);
    color: var(--blue);
}

.preview-stage-container {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 18px;
    overflow-y: auto;
    background: var(--page-bg);
}

.preview-stage {
    min-height: 225px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.paper-sheet-mockup {
    width: 150px;
    height: 212px;
    padding: 12px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    background: #fff;
    border-radius: 4px;
    box-shadow: 0 15px 35px rgba(0,0,0,.22);
}

.mockup-header {
    width: 45%;
    height: 10px;
    margin-bottom: 10px;
    border-radius: 2px;
    background: #0f172a;
}

.mockup-line {
    height: 4px;
    margin-bottom: 4px;
    border-radius: 2px;
    background: #cbd5e1;
}

.mockup-divider {
    height: 1px;
    margin: 8px 0;
    background: #e2e8f0;
}

.mockup-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 4px;
}

.mockup-row span {
    width: 32%;
    height: 4px;
    border-radius: 2px;
    background: #e2e8f0;
}

.mockup-row span:last-child {
    width: 20%;
    background: #cbd5e1;
}

.mockup-total {
    width: 42%;
    height: 6px;
    margin-top: auto;
    margin-left: auto;
    border-radius: 2px;
    background: #10b981;
}

.paper-sheet-mockup.thermal-mode {
    border-bottom: 4px dashed #94a3b8;
    border-radius: 2px 2px 0 0;
}

.custom-inputs-box {
    display: none;
    padding: 10px;
    margin-bottom: 10px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--card-bg);
}

.custom-inputs-box.active {
    display: block;
}

.custom-row {
    display: flex;
    gap: 7px;
    margin-top: 6px;
}

.custom-row .form-control {
    margin-bottom: 0;
}

.preview-details {
    padding-top: 12px;
    border-top: 1px solid var(--border);
}

.preview-details h4 {
    margin: 0;
    color: var(--text-main);
    font-size: 13px;
    font-weight: 850;
}

.preview-dim {
    margin: 4px 0 7px;
    color: var(--primary);
    font-family: monospace;
    font-size: 10px;
    font-weight: 800;
}

.preview-desc {
    margin: 0;
    color: var(--text-body);
    font-size: 10px;
    line-height: 1.5;
}

.modal-footer {
    min-height: 58px;
    padding: 10px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    border-top: 1px solid var(--border);
}

.modal-footer-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

/* =========================================================
   PRINT ENGINE
========================================================= */
#printableInvoice {
    display: none;
}

@media print {
    body * {
        visibility: hidden !important;
    }

    #printableInvoice,
    #printableInvoice * {
        visibility: visible !important;
    }

    #printableInvoice {
        display: block !important;
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        padding: 15px;
        box-sizing: border-box;
        background: #fff !important;
        color: #111 !important;
        font-family: Arial, sans-serif !important;
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
        padding: 2mm !important;
        font-size: 11px !important;
    }

    body.paper-pos58 {
        @page { size: 58mm auto; margin: 2mm; }
    }

    body.paper-pos58 #printableInvoice {
        width: 52mm !important;
        padding: 1mm !important;
        font-size: 9.5px !important;
    }

    body.paper-pos80 .hide-receipt,
    body.paper-pos58 .hide-receipt {
        display: none !important;
    }
}

/* =========================================================
   RESPONSIVE
========================================================= */
@media (max-width: 1050px) {
    .order-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 800px) {
    .modal-body-split {
        grid-template-columns: 1fr;
        height: auto;
        max-height: 72vh;
    }

    .preview-stage-container {
        display: none;
    }

    .page-header {
        align-items: flex-start;
    }
}

@media (max-width: 600px) {
    .order-detail-page {
        padding-left: 10px;
        padding-right: 10px;
    }

    .items-table {
        min-width: 650px;
    }

    .card-body {
        overflow-x: auto;
    }

    .header-actions {
        width: 100%;
    }

    .header-actions .btn {
        flex: 1;
    }

    .modal-footer {
        align-items: flex-start;
        flex-direction: column;
    }

    .modal-footer-actions {
        width: 100%;
    }
}
</style>

<main class="main">
    <section class="content">
        <div class="order-detail-page">

            <!-- =====================================================
                 PAGE HEADER
            ====================================================== -->
            <div class="page-header">
                <div>
                    <div class="breadcrumb">
                        <span>Products</span>
                        <span>/</span>
                        <a href="index.php">Orders</a>
                        <span>/</span>
                        <span class="current">
                            <?= e($order['OrderNumber'] ?? 'Order Details') ?>
                        </span>
                    </div>

                    <h1 class="page-title">
                        Order #<?= e($order['OrderNumber'] ?? $order['OrderId']) ?>
                    </h1>

                    <div class="page-subtitle">
                        Order details, customer information, shipment and status history.
                        Placed on <?= formatDate($order['CreatedAt'] ?? '') ?>
                    </div>
                </div>

                <div class="header-actions">
                    <button type="button"
                            class="btn btn-theme"
                            id="themeToggle"
                            onclick="toggleTheme()">
                        ☾ Dark
                    </button>

                    <button type="button"
                            class="btn btn-blue"
                            onclick="openPrintModal()"
                            title="Ctrl + P">
                        🖨 Print
                        <span class="key-badge">P</span>
                    </button>

                    <a href="index.php" class="btn">
                        ← Back to Orders
                    </a>
                </div>
            </div>

            <?php if ($message !== ''): ?>
                <div class="notice-success">
                    ✓ <?= e($message) ?>
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="notice-error">
                    ! <?= e($error) ?>
                </div>
            <?php endif; ?>

            <!-- =====================================================
                 MAIN ORDER LAYOUT
            ====================================================== -->
            <div class="order-layout">

                <!-- =================================================
                     LEFT
                ================================================== -->
                <div>

                    <!-- ORDER ITEMS -->
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h2>Order Items</h2>
                                <small><?= count($orderItems) ?> product(s) in this order</small>
                            </div>

                            <span class="status-badge">
                                <?= e($order['OrderStatus'] ?? 'Pending') ?>
                            </span>
                        </div>

                        <div class="card-body" style="padding:0; overflow-x:auto;">
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
                                <?php if (!empty($orderItems)): ?>
                                    <?php foreach ($orderItems as $item):
                                        $price = (float)($item['UnitPrice'] ?? 0);
                                        $qty   = (int)($item['Quantity'] ?? 1);
                                        $line  = (float)($item['LineTotal'] ?? ($price * $qty));
                                    ?>
                                        <tr>
                                            <td>
                                                <span class="product-name">
                                                    <?= e($item['ProductName'] ?? 'Product') ?>
                                                </span>
                                                <span class="sku">
                                                    <?= e($item['SKU'] ?? '—') ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?= e($item['VariantDetails'] ?? 'Standard') ?>
                                            </td>

                                            <td>
                                                $<?= number_format($price, 2) ?>
                                            </td>

                                            <td>
                                                <?= $qty ?>
                                            </td>

                                            <td style="text-align:right;">
                                                <strong style="color:var(--text-main);">
                                                    $<?= number_format($line, 2) ?>
                                                </strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5"
                                            style="padding:30px; text-align:center; color:var(--text-muted);">
                                            No order items found.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>

                            <!-- FINANCIALS -->
                            <div class="financial-box">
                                <div class="info-row">
                                    <span class="info-label">Subtotal</span>
                                    <span class="info-val">
                                        $<?= number_format($calcSubTotal, 2) ?>
                                    </span>
                                </div>

                                <?php if ($calcTax > 0): ?>
                                    <div class="info-row">
                                        <span class="info-label">Taxes (GST/PST)</span>
                                        <span class="info-val">
                                            $<?= number_format($calcTax, 2) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($order['CouponCode'])): ?>
                                    <div class="info-row">
                                        <span class="info-label" style="color:var(--primary);">
                                            Discount (<?= e($order['CouponCode']) ?>)
                                        </span>
                                        <span class="info-val" style="color:var(--primary);">
                                            - $<?= number_format($discountAmount, 2) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <div class="info-row total-row">
                                    <span class="info-label">Grand Total</span>
                                    <span class="info-val">
                                        $<?= number_format($grandTotal, 2) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SHIPPING DESTINATION -->
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h2>Shipping Destination</h2>
                                <small>Customer delivery information</small>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="info-row">
                                <span class="info-label">Recipient</span>
                                <span class="info-val">
                                    <?= e($order['CustomerFullName'] ?? '—') ?>
                                </span>
                            </div>

                            <div class="info-row">
                                <span class="info-label">Company</span>
                                <span class="info-val">
                                    <?= e($order['CompanyName'] ?? '—') ?>
                                </span>
                            </div>

                            <div class="info-row">
                                <span class="info-label">Phone</span>
                                <span class="info-val">
                                    <?= e($order['CustomerPhone'] ?? $order['Phone'] ?? '—') ?>
                                </span>
                            </div>

                            <div class="info-row">
                                <span class="info-label">Address 1</span>
                                <span class="info-val">
                                    <?= e($order['ShippingAddressLine1'] ?? '—') ?>
                                </span>
                            </div>

                            <div class="info-row">
                                <span class="info-label">Address 2</span>
                                <span class="info-val">
                                    <?= e($order['ShippingAddressLine2'] ?? '—') ?>
                                </span>
                            </div>

                            <div class="info-row">
                                <span class="info-label">City / State / ZIP</span>
                                <span class="info-val">
                                    <?= e($order['ShippingCity'] ?? '') ?>,
                                    <?= e($order['ShippingStateProvince'] ?? '') ?>
                                    <?= e($order['ShippingPostalCode'] ?? '') ?>
                                </span>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- =================================================
                     RIGHT
                ================================================== -->
                <div>

                    <!-- UPDATE STATUS -->
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h2>Update Status</h2>
                                <small>Change order state</small>
                            </div>
                        </div>

                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden"
                                       name="action"
                                       value="update_status">

                                <input type="hidden"
                                       name="current_status"
                                       value="<?= e($order['OrderStatus'] ?? '') ?>">

                                <label class="form-label">
                                    Change State
                                </label>

                                <select name="order_status" class="form-control">
                                    <?php
                                    $statuses = [
                                        'Pending',
                                        'Processing',
                                        'Shipped',
                                        'Delivered',
                                        'Cancelled',
                                        'Refunded'
                                    ];

                                    foreach ($statuses as $st):
                                    ?>
                                        <option value="<?= e($st) ?>"
                                            <?= strtolower($st) === strtolower($order['OrderStatus'] ?? '')
                                                ? 'selected'
                                                : '' ?>>
                                            <?= e($st) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <button type="submit"
                                        class="btn btn-primary"
                                        style="width:100%;">
                                    ✓ Update Status
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- SHIPMENT -->
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h2>Shipment Details</h2>
                                <small>Courier & tracking information</small>
                            </div>
                        </div>

                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden"
                                       name="action"
                                       value="update_shipment">

                                <label class="form-label">
                                    Courier Name
                                </label>

                                <input type="text"
                                       name="courier_name"
                                       class="form-control"
                                       placeholder="e.g. DHL, FedEx"
                                       value="<?= e($shipment['CourierName'] ?? '') ?>">

                                <label class="form-label">
                                    Tracking Number
                                </label>

                                <input type="text"
                                       name="tracking_number"
                                       class="form-control"
                                       placeholder="e.g. TRK123456789"
                                       value="<?= e($shipment['TrackingNumber'] ?? '') ?>">

                                <button type="submit"
                                        class="btn btn-blue"
                                        style="width:100%;">
                                    Save Tracking
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- STATUS HISTORY -->
                    <?php if (!empty($statusHistory)): ?>
                        <div class="card">
                            <div class="card-header">
                                <div>
                                    <h2>Status History</h2>
                                    <small>Order activity timeline</small>
                                </div>
                            </div>

                            <div class="card-body">
                                <ul class="timeline">
                                    <?php foreach ($statusHistory as $hist): ?>
                                        <li class="timeline-item">
                                            <div class="timeline-status">
                                                <?= e($hist['ToStatus'] ?? 'Updated') ?>
                                            </div>

                                            <div class="timeline-meta">
                                                By <?= e($hist['ChangedBy'] ?? 'Admin') ?>
                                                • <?= formatDate($hist['CreatedAt'] ?? '') ?>
                                            </div>
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
     PRINT MODAL
========================================================= -->
<div class="modal-overlay" id="printModal">
    <div class="modal-interactive">

        <div class="modal-header">
            <div>
                <h3 class="modal-title">
                    Select Print Format & Paper Size
                </h3>

                <span class="modal-sub">
                    Press <strong>1–8</strong> to select,
                    <strong>Enter</strong> to print,
                    <strong>Esc</strong> to close.
                </span>
            </div>

            <button type="button"
                    class="close-btn"
                    onclick="closePrintModal()"
                    title="Esc">
                &times;
            </button>
        </div>

        <div class="modal-body-split">

            <!-- OPTIONS -->
            <div class="paper-options-list">

                <div class="category-label">
                    Standard Office Paper Sizes
                </div>

                <div class="paper-card active"
                     data-key="1"
                     data-size="a4"
                     data-name="A4 Standard Sheet"
                     data-dim="210 × 297 mm (8.27 × 11.69 in)"
                     data-desc="Standard invoice format for detailed order invoices and desktop printers."
                     data-ratio="0.707"
                     data-type="sheet"
                     onclick="selectPaper(this)">

                    <div class="paper-card-icon">📄</div>

                    <div class="paper-card-info">
                        <strong>A4 Standard</strong>
                        <span>210 × 297 mm • Standard Invoicing</span>
                    </div>

                    <span class="key-badge">1</span>
                    <span class="badge-tag">Default</span>
                </div>

                <div class="paper-card"
                     data-key="2"
                     data-size="a5"
                     data-name="A5 Compact Sheet"
                     data-dim="148 × 210 mm (5.83 × 8.27 in)"
                     data-desc="Compact half-page format for delivery challans and mini billing."
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

                <div class="paper-card"
                     data-key="3"
                     data-size="letter"
                     data-name="US Letter"
                     data-dim="215.9 × 279.4 mm (8.5 × 11.0 in)"
                     data-desc="Standard US business paper format."
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

                <div class="paper-card"
                     data-key="4"
                     data-size="legal"
                     data-name="US Legal"
                     data-dim="215.9 × 355.6 mm (8.5 × 14.0 in)"
                     data-desc="Long page format for larger invoices and order reports."
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

                <div class="paper-card"
                     data-key="5"
                     data-size="a3"
                     data-name="A3 Large Ledger"
                     data-dim="297 × 420 mm (11.69 × 16.54 in)"
                     data-desc="Large format for master dispatch and warehouse reports."
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

                <div class="category-label" style="margin-top:12px;">
                    Thermal POS Slips
                </div>

                <div class="paper-card"
                     data-key="6"
                     data-size="pos80"
                     data-name="80mm Thermal Receipt"
                     data-dim="80mm Roll (3.15 in, Continuous)"
                     data-desc="Compact retail counter receipt format."
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

                <div class="paper-card"
                     data-key="7"
                     data-size="pos58"
                     data-name="58mm Mini Pocket Slip"
                     data-dim="58mm Roll (2.28 in, Continuous)"
                     data-desc="Ultra compact handheld POS receipt format."
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

                <div class="category-label" style="margin-top:12px;">
                    Customization
                </div>

                <div class="paper-card"
                     data-key="8"
                     data-size="custom"
                     data-name="Custom User-Defined Size"
                     data-dim="Admin Defined Width × Height"
                     data-desc="Set any custom page size for labels, vouchers or special receipts."
                     data-ratio="0.7"
                     data-type="custom"
                     onclick="selectPaper(this)">

                    <div class="paper-card-icon">⚙️</div>

                    <div class="paper-card-info">
                        <strong>Custom Page Size</strong>
                        <span>Set Width & Height manually</span>
                    </div>

                    <span class="key-badge">8</span>
                    <span class="badge-tag badge-custom">Manual</span>
                </div>

            </div>

            <!-- PREVIEW -->
            <div class="preview-stage-container">

                <div class="custom-inputs-box" id="customControls">

                    <span style="font-size:10px; font-weight:800; color:var(--text-main);">
                        Enter Dimensions
                    </span>

                    <div class="custom-row">
                        <input type="number"
                               id="customWidth"
                               class="form-control"
                               placeholder="Width"
                               value="100"
                               min="20"
                               max="600"
                               oninput="updateCustomDimensions()">

                        <input type="number"
                               id="customHeight"
                               class="form-control"
                               placeholder="Height"
                               value="150"
                               min="20"
                               max="800"
                               oninput="updateCustomDimensions()">

                        <select id="customUnit"
                                class="form-control"
                                style="width:75px;"
                                onchange="updateCustomDimensions()">
                            <option value="mm">mm</option>
                            <option value="in">in</option>
                            <option value="cm">cm</option>
                        </select>
                    </div>
                </div>

                <div class="preview-stage">
                    <div class="paper-sheet-mockup"
                         id="previewMockup">

                        <div class="mockup-header"></div>

                        <div class="mockup-line"
                             style="width:45%;"></div>

                        <div class="mockup-line"
                             style="width:70%; margin-bottom:8px;"></div>

                        <div class="mockup-divider"></div>

                        <div class="mockup-row">
                            <span></span><span></span>
                        </div>

                        <div class="mockup-row">
                            <span></span><span></span>
                        </div>

                        <div class="mockup-row">
                            <span></span><span></span>
                        </div>

                        <div class="mockup-divider"></div>

                        <div class="mockup-total"></div>
                    </div>
                </div>

                <div class="preview-details">
                    <h4 id="previewTitle">
                        A4 Standard Sheet
                    </h4>

                    <div class="preview-dim" id="previewDim">
                        210 × 297 mm (8.27 × 11.69 in)
                    </div>

                    <p class="preview-desc" id="previewDesc">
                        Standard invoice format for detailed order invoices and desktop printers.
                    </p>
                </div>
            </div>
        </div>

        <div class="modal-footer">

            <span style="font-size:10px; color:var(--text-muted);">
                Selected:
                <strong id="selectedLabel"
                        style="color:var(--text-main);">
                    A4 Standard Sheet
                </strong>
            </span>

            <div class="modal-footer-actions">

                <span style="font-size:9px; color:var(--text-muted);">
                    Esc Close
                    &nbsp; • &nbsp;
                    Enter Print
                </span>

                <button type="button"
                        class="btn"
                        onclick="closePrintModal()">
                    Cancel
                </button>

                <button type="button"
                        class="btn btn-primary"
                        onclick="executePrint()">
                    🖨 Confirm & Print
                </button>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================
     PRINTABLE INVOICE
========================================================= -->
<div id="printableInvoice">

    <div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #222;padding-bottom:12px;margin-bottom:15px;">
        <div>
            <h1 style="margin:0;font-size:22px;font-weight:900;letter-spacing:1px;">
                GATEWAYLINEN
            </h1>
            <p style="margin:3px 0 0;font-size:11px;color:#555;">
                Commercial Textile & Linen Supply
            </p>
        </div>

        <div style="text-align:right;">
            <h2 style="margin:0;font-size:18px;color:#111;">
                INVOICE
            </h2>

            <div style="font-size:11px;margin-top:3px;">
                <strong>Order #:</strong>
                <?= e($order['OrderNumber'] ?? $order['OrderId']) ?><br>

                <strong>Date:</strong>
                <?= formatDate($order['CreatedAt'] ?? '') ?>
            </div>
        </div>
    </div>

    <div style="display:flex;justify-content:space-between;margin-bottom:18px;font-size:11px;line-height:1.4;">

        <div style="width:48%;">
            <strong style="text-transform:uppercase;color:#666;font-size:10px;display:block;margin-bottom:4px;">
                Billed / Shipped To
            </strong>

            <strong style="font-size:12px;">
                <?= e($order['CustomerFullName'] ?? 'Customer') ?>
            </strong><br>

            <?php if (!empty($order['CompanyName'])): ?>
                <?= e($order['CompanyName']) ?><br>
            <?php endif; ?>

            <?= e($order['ShippingAddressLine1'] ?? '') ?>
            <?= e($order['ShippingAddressLine2'] ?? '') ?><br>

            <?= e($order['ShippingCity'] ?? '') ?>,
            <?= e($order['ShippingStateProvince'] ?? '') ?>
            <?= e($order['ShippingPostalCode'] ?? '') ?><br>

            Phone:
            <?= e($order['CustomerPhone'] ?? $order['Phone'] ?? '—') ?>
        </div>

        <div style="width:48%;text-align:right;" class="hide-receipt">
            <strong style="text-transform:uppercase;color:#666;font-size:10px;display:block;margin-bottom:4px;">
                Shipment Info
            </strong>

            Status:
            <strong><?= e($order['OrderStatus'] ?? 'Pending') ?></strong><br>

            Courier:
            <?= e($shipment['CourierName'] ?? 'Standard Ground') ?><br>

            Tracking:
            <?= e($shipment['TrackingNumber'] ?? 'Pending Assignment') ?>
        </div>
    </div>

    <table style="width:100%;border-collapse:collapse;margin-bottom:15px;font-size:11px;">
        <thead>
            <tr style="border-top:1px solid #000;border-bottom:1px solid #000;background:#f9f9f9;">
                <th style="padding:6px;text-align:left;">Item</th>
                <th style="padding:6px;text-align:left;" class="hide-receipt">Details</th>
                <th style="padding:6px;text-align:right;">Price</th>
                <th style="padding:6px;text-align:center;">Qty</th>
                <th style="padding:6px;text-align:right;">Total</th>
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
                    <strong><?= e($item['ProductName'] ?? 'Product') ?></strong>

                    <div style="font-size:9.5px;color:#666;" class="hide-receipt">
                        SKU: <?= e($item['SKU'] ?? '—') ?>
                    </div>
                </td>

                <td style="padding:6px;" class="hide-receipt">
                    <?= e($item['VariantDetails'] ?? '—') ?>
                </td>

                <td style="padding:6px;text-align:right;">
                    $<?= number_format($p, 2) ?>
                </td>

                <td style="padding:6px;text-align:center;">
                    <?= $q ?>
                </td>

                <td style="padding:6px;text-align:right;">
                    $<?= number_format($tot, 2) ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div style="display:flex;justify-content:flex-end;">
        <div style="width:240px;font-size:11px;">

            <div style="display:flex;justify-content:space-between;padding:3px 0;">
                <span>Subtotal:</span>
                <span>$<?= number_format($calcSubTotal, 2) ?></span>
            </div>

            <?php if ($calcTax > 0): ?>
                <div style="display:flex;justify-content:space-between;padding:3px 0;color:#555;">
                    <span>Taxes:</span>
                    <span>$<?= number_format($calcTax, 2) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($order['CouponCode'])): ?>
                <div style="display:flex;justify-content:space-between;padding:3px 0;color:#059669;">
                    <span>Discount:</span>
                    <span>-$<?= number_format($discountAmount, 2) ?></span>
                </div>
            <?php endif; ?>

            <div style="display:flex;justify-content:space-between;border-top:1.5px solid #000;padding:6px 0;font-size:13px;font-weight:800;margin-top:4px;">
                <span>Total Amount:</span>
                <span>$<?= number_format($grandTotal, 2) ?></span>
            </div>
        </div>
    </div>

    <div class="hide-receipt"
         style="margin-top:40px;border-top:1px dashed #ccc;padding-top:15px;font-size:10px;color:#666;display:flex;justify-content:space-between;align-items:flex-end;">

        <div>
            Thank you for your business!<br>
            For support and inquiries: support@gatewaylinen.com
        </div>

        <div style="text-align:center;">
            <div style="width:160px;border-bottom:1px solid #000;margin-bottom:4px;"></div>
            Authorized Signature
        </div>
    </div>
</div>

<style id="customPrintStyle"></style>

<script>
/* =========================================================
   THEME
========================================================= */
(function () {
    const savedTheme = localStorage.getItem('gatewaylinen-theme');

    if (savedTheme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
    } else {
        document.documentElement.setAttribute('data-theme', 'light');
    }
})();

function updateThemeButton() {
    const button = document.getElementById('themeToggle');

    if (!button) return;

    const isDark =
        document.documentElement.getAttribute('data-theme') === 'dark';

    button.innerHTML = isDark
        ? '☀ Light'
        : '☾ Dark';
}

function toggleTheme() {
    const html = document.documentElement;

    const isDark = html.getAttribute('data-theme') === 'dark';
    const nextTheme = isDark ? 'light' : 'dark';

    html.setAttribute('data-theme', nextTheme);
    localStorage.setItem('gatewaylinen-theme', nextTheme);

    updateThemeButton();
}

document.addEventListener('DOMContentLoaded', function () {
    updateThemeButton();
});

/* =========================================================
   PRINT MODAL
========================================================= */
let currentSelectedSize = 'a4';

function openPrintModal() {
    const modal = document.getElementById('printModal');

    if (!modal) return;

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closePrintModal() {
    const modal = document.getElementById('printModal');

    if (!modal) return;

    modal.classList.remove('active');
    document.body.style.overflow = '';
}

function selectPaper(cardElement) {
    if (!cardElement) return;

    document.querySelectorAll('.paper-card').forEach(function (card) {
        card.classList.remove('active');
    });

    cardElement.classList.add('active');

    const size = cardElement.getAttribute('data-size');
    const name = cardElement.getAttribute('data-name');
    const dim = cardElement.getAttribute('data-dim');
    const desc = cardElement.getAttribute('data-desc');
    const type = cardElement.getAttribute('data-type');
    const ratio = parseFloat(cardElement.getAttribute('data-ratio')) || .707;

    currentSelectedSize = size;

    const customBox = document.getElementById('customControls');

    if (size === 'custom') {
        customBox.classList.add('active');
        updateCustomDimensions();

        setTimeout(function () {
            document.getElementById('customWidth').focus();
        }, 50);

        return;
    }

    customBox.classList.remove('active');

    document.getElementById('previewTitle').innerText = name;
    document.getElementById('previewDim').innerText = dim;
    document.getElementById('previewDesc').innerText = desc;
    document.getElementById('selectedLabel').innerText = name;

    const mockup = document.getElementById('previewMockup');

    const baseHeight = 210;
    let targetWidth = Math.round(baseHeight * ratio);

    if (targetWidth > 180) targetWidth = 180;
    if (targetWidth < 60) targetWidth = 60;

    mockup.style.width = targetWidth + 'px';
    mockup.style.height =
        type === 'thermal'
            ? '220px'
            : baseHeight + 'px';

    if (type === 'thermal') {
        mockup.classList.add('thermal-mode');
    } else {
        mockup.classList.remove('thermal-mode');
    }
}

function updateCustomDimensions() {
    const w =
        parseFloat(document.getElementById('customWidth').value) || 100;

    const h =
        parseFloat(document.getElementById('customHeight').value) || 150;

    const unit =
        document.getElementById('customUnit').value;

    const ratio = w / h;
    const mockup = document.getElementById('previewMockup');

    let targetH = 200;
    let targetW = Math.round(targetH * ratio);

    if (targetW > 220) {
        targetW = 220;
        targetH = Math.round(targetW / ratio);
    }

    if (targetW < 50) {
        targetW = 50;
    }

    mockup.style.width = targetW + 'px';
    mockup.style.height = targetH + 'px';
    mockup.classList.remove('thermal-mode');

    document.getElementById('previewTitle').innerText =
        'Custom Paper (' + w + unit + ' × ' + h + unit + ')';

    document.getElementById('previewDim').innerText =
        w + ' ' + unit +
        ' × ' + h + ' ' + unit +
        ' (Aspect Ratio: ' + ratio.toFixed(2) + ')';

    document.getElementById('previewDesc').innerText =
        'Custom user-defined page size.';

    document.getElementById('selectedLabel').innerText =
        'Custom: ' + w + unit + ' × ' + h + unit;
}

/* Hover preview */
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.paper-card').forEach(function (card) {

        card.addEventListener('mouseenter', function () {
            selectPaper(card);
        });

    });

    const modal = document.getElementById('printModal');

    if (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closePrintModal();
            }
        });
    }
});

/* =========================================================
   KEYBOARD SHORTCUTS
========================================================= */
window.addEventListener('keydown', function (e) {

    const modal = document.getElementById('printModal');

    const isModalOpen =
        modal && modal.classList.contains('active');

    /* Ctrl + P */
    if ((e.ctrlKey || e.metaKey) &&
        e.key.toLowerCase() === 'p') {

        e.preventDefault();
        openPrintModal();
        return;
    }

    if (!isModalOpen) return;

    /* Escape */
    if (e.key === 'Escape') {
        e.preventDefault();
        closePrintModal();
        return;
    }

    /* Enter */
    if (e.key === 'Enter') {

        if (['INPUT', 'SELECT', 'TEXTAREA'].includes(
            document.activeElement.tagName
        )) {
            return;
        }

        e.preventDefault();
        executePrint();
        return;
    }

    /* Number shortcuts */
    if (['INPUT', 'SELECT', 'TEXTAREA'].includes(
        document.activeElement.tagName
    )) {
        return;
    }

    if (e.key >= '1' && e.key <= '8') {

        const targetCard =
            document.querySelector(
                '.paper-card[data-key="' + e.key + '"]'
            );

        if (targetCard) {
            e.preventDefault();
            selectPaper(targetCard);
        }
    }
});

/* =========================================================
   EXECUTE PRINT
========================================================= */
function executePrint() {

    const styleHolder =
        document.getElementById('customPrintStyle');

    styleHolder.innerHTML = '';

    document.body.classList.remove(
        'paper-a3',
        'paper-a4',
        'paper-a5',
        'paper-letter',
        'paper-legal',
        'paper-pos80',
        'paper-pos58',
        'paper-custom'
    );

    if (currentSelectedSize === 'custom') {

        const w =
            parseFloat(document.getElementById('customWidth').value) || 100;

        const h =
            parseFloat(document.getElementById('customHeight').value) || 150;

        const unit =
            document.getElementById('customUnit').value;

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

        document.body.classList.add(
            'paper-' + currentSelectedSize
        );
    }

    closePrintModal();

    setTimeout(function () {
        window.print();
    }, 250);
}

/* =========================================================
   DEFAULT PRINT OPTION
========================================================= */
document.addEventListener('DOMContentLoaded', function () {

    const defaultCard =
        document.querySelector('.paper-card[data-size="a4"]');

    if (defaultCard) {
        selectPaper(defaultCard);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
