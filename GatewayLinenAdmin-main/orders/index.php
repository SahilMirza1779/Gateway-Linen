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

/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| PAGE SETTINGS
|--------------------------------------------------------------------------
*/
$activeMenu = 'orders';
$pageTitle  = 'GatewayLinen | Orders Management';

/*
|--------------------------------------------------------------------------
| ADMIN INFO
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] = $_SESSION['admin_username'] ?? 'GatewayLinen Administrator';
}

if (!isset($_SESSION['admin_role'])) {
    $_SESSION['admin_role'] = 'Administrator';
}

/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/
if (empty($_SESSION['order_csrf_token'])) {
    $_SESSION['order_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['order_csrf_token'];

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/
function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function formatDate($value): string {
    if ($value instanceof DateTimeInterface) {
        return $value->format('d M Y, h:i A');
    }
    return !empty($value) ? date('d M Y, h:i A', strtotime($value)) : '—';
}

/*
|--------------------------------------------------------------------------
| MESSAGES
|--------------------------------------------------------------------------
*/
$actionMessage = trim((string)($_GET['success'] ?? ''));
$actionError   = trim((string)($_GET['error'] ?? ''));

/*
|--------------------------------------------------------------------------
| FETCH ORDERS FROM DATABASE
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT 
        o.*,
        (SELECT COUNT(*) FROM dbo.OrderItems oi WHERE oi.OrderId = o.OrderId) AS TotalItems,
        ISNULL(s.TrackingNumber, '') AS TrackingNumber,
        ISNULL(s.CourierName, '') AS CourierName
    FROM dbo.Orders o
    OUTER APPLY (
        SELECT TOP 1 TrackingNumber, CourierName 
        FROM dbo.OrderShipments os 
        WHERE os.OrderId = o.OrderId
    ) s
    ORDER BY o.OrderId DESC
";

$stmt = sqlsrv_query($conn, $sql);
$allOrders = [];
$queryError = '';

if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $allOrders[] = $row;
    }
    sqlsrv_free_stmt($stmt);
} else {
    $errors = sqlsrv_errors();
    $queryError = $errors[0]['message'] ?? 'Unable to load orders from database.';
}

/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/
$totalOrders = count($allOrders);
$pendingOrders = 0;
$processingOrders = 0;
$completedOrders = 0;
$totalRevenue = 0.00;

foreach ($allOrders as $order) {
    $status = strtolower($order['OrderStatus'] ?? $order['Status'] ?? 'pending');
    $amount = (float)($order['TotalAmount'] ?? $order['Total'] ?? $order['GrandTotal'] ?? 0);
    $totalRevenue += $amount;

    if ($status === 'pending' || $status === 'payment pending') {
        $pendingOrders++;
    } elseif ($status === 'processing' || $status === 'shipped') {
        $processingOrders++;
    } elseif ($status === 'completed' || $status === 'delivered') {
        $completedOrders++;
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<style>
    :root {
        --bg-page: #0a1119;
        --bg-card: #111b26;
        --bg-card-alt: #0f1823;
        --bg-header: #0d1620;
        --bg-hover: #16222e;
        --bg-input: #0d1620;

        --border: #1e2d3d;
        --border-soft: #182636;

        --text-hi: #f0f4f8;
        --text-body: #a8b8c8;
        --text-mute: #5f7488;

        --green: #10b981;
        --green-soft: rgba(16, 185, 129, .12);

        --blue: #38bdf8;
        --blue-soft: rgba(56, 189, 248, .15);

        --amber: #f59e0b;
        --amber-soft: rgba(245, 158, 11, .15);

        --purple: #a855f7;
        --purple-soft: rgba(168, 85, 247, .15);

        --red: #ef4444;
        --red-soft: rgba(239, 68, 68, .15);

        --radius: 12px;
    }

    html, body, .main, .content {
        background: var(--bg-page) !important;
        color: var(--text-body) !important;
    }

    .orders-page {
        width: 100%;
        max-width: 1600px;
        margin: 0 auto;
        padding: 0 0 40px;
    }

    .orders-page * { box-sizing: border-box; }

    .page-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 20px;
        padding-bottom: 18px;
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
        letter-spacing: .4px;
    }

    .breadcrumb .current { color: var(--green); }

    .page-header h1 {
        margin: 0;
        color: var(--text-hi);
        font-size: 26px;
        font-weight: 800;
    }

    .page-header p {
        margin: 6px 0 0;
        color: var(--text-mute);
        font-size: 12px;
    }

    .header-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 38px;
        padding: 0 14px;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--bg-input);
        color: var(--text-body) !important;
        font-size: 11px;
        font-weight: 800;
        text-decoration: none;
        cursor: pointer;
        transition: .18s ease;
    }

    .btn kbd {
        display: inline-block;
        padding: 1px 5px;
        font-size: 9px;
        font-family: monospace;
        color: var(--text-mute);
        background: rgba(255,255,255,0.06);
        border: 1px solid var(--border);
        border-radius: 4px;
    }

    .btn:hover {
        border-color: var(--green);
        background: var(--green-soft);
        color: var(--green) !important;
    }

    .btn-blue { color: var(--blue) !important; }

    /* STATS GRID */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }

    .stat-card {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 16px 18px;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
    }

    .stat-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 9px;
        font-size: 16px;
        font-weight: 800;
    }

    .icon-total { background: var(--blue-soft); color: var(--blue); }
    .icon-pending { background: var(--amber-soft); color: var(--amber); }
    .icon-processing { background: var(--purple-soft); color: var(--purple); }
    .icon-revenue { background: var(--green-soft); color: var(--green); }

    .stat-label {
        color: var(--text-mute);
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .stat-value {
        margin-top: 3px;
        color: var(--text-hi);
        font-size: 20px;
        font-weight: 800;
    }

    /* NOTICES */
    .notice {
        margin-bottom: 15px;
        padding: 12px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
    }
    .notice-success {
        border: 1px solid rgba(16, 185, 129, .3);
        background: var(--green-soft);
        color: #6ee7b7;
    }
    .notice-error {
        border: 1px solid rgba(239, 68, 68, .3);
        background: var(--red-soft);
        color: #fca5a5;
    }

    /* CARD WRAPPER */
    .content-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
    }

    .content-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 18px 20px;
        border-bottom: 1px solid var(--border);
        flex-wrap: wrap;
    }

    .content-title h2 {
        margin: 0;
        color: var(--text-hi);
        font-size: 16px;
    }

    .content-title p {
        margin: 4px 0 0;
        color: var(--text-mute);
        font-size: 11px;
    }

    .filter-group {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .search-wrap {
        position: relative;
        width: 320px;
    }

    .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-mute);
        pointer-events: none;
    }

    .input-search, .filter-select {
        height: 38px;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--bg-input);
        color: var(--text-hi);
        font-size: 12px;
        outline: none;
        transition: border-color .18s;
    }

    .input-search {
        width: 100%;
        padding: 0 12px 0 34px;
    }

    .filter-select {
        min-width: 150px;
        padding: 0 12px;
        cursor: pointer;
    }

    .input-search:focus, .filter-select:focus {
        border-color: var(--green);
        box-shadow: 0 0 0 3px rgba(16, 185, 129, .1);
    }

    .export-bar {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border-bottom: 1px solid var(--border);
        background: var(--bg-card-alt);
    }

    .export-label {
        margin-right: auto;
        color: var(--text-mute);
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .table-summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 11px 20px;
        border-bottom: 1px solid var(--border);
        font-size: 11px;
        color: var(--text-mute);
    }

    .table-summary strong { color: var(--text-hi); }

    /* TABLE */
    .table-wrapper {
        width: 100%;
        overflow-x: auto;
    }

    table.data-table {
        width: 100%;
        min-width: 1150px;
        border-collapse: collapse;
    }

    table.data-table th {
        height: 44px;
        padding: 0 16px;
        background: var(--bg-header);
        border-bottom: 1px solid var(--border);
        color: var(--text-mute);
        font-size: 10px;
        font-weight: 800;
        text-align: left;
        text-transform: uppercase;
        letter-spacing: .5px;
        white-space: nowrap;
    }

    table.data-table td {
        padding: 14px 16px;
        border-bottom: 1px solid var(--border-soft);
        color: var(--text-body);
        font-size: 12px;
        vertical-align: middle;
    }

    table.data-table tbody tr {
        transition: background .12s ease;
    }

    table.data-table tbody tr:hover {
        background: var(--bg-hover);
    }

    table.data-table tbody tr.keyboard-selected {
        background: rgba(16, 185, 129, 0.12) !important;
        outline: 1px solid var(--green);
    }

    /* BADGES */
    .order-number {
        font-weight: 800;
        color: var(--text-hi);
        text-decoration: none;
        font-family: monospace;
        font-size: 13px;
    }
    .order-number:hover { color: var(--green); }

    .customer-info strong {
        display: block;
        color: var(--text-hi);
        font-weight: 700;
    }

    .customer-info span {
        color: var(--text-mute);
        font-size: 11px;
    }

    .items-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 2px 8px;
        border-radius: 12px;
        background: var(--bg-input);
        border: 1px solid var(--border);
        color: var(--text-hi);
        font-weight: 700;
        font-size: 11px;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .3px;
    }
    .status-dot { width: 6px; height: 6px; border-radius: 50%; }

    .badge-pending { background: var(--amber-soft); color: var(--amber); }
    .badge-pending .status-dot { background: var(--amber); box-shadow: 0 0 6px var(--amber); }

    .badge-processing { background: var(--purple-soft); color: var(--purple); }
    .badge-processing .status-dot { background: var(--purple); }

    .badge-shipped { background: var(--blue-soft); color: var(--blue); }
    .badge-shipped .status-dot { background: var(--blue); }

    .badge-delivered, .badge-completed { background: var(--green-soft); color: var(--green); }
    .badge-delivered .status-dot, .badge-completed .status-dot { background: var(--green); box-shadow: 0 0 6px var(--green); }

    .badge-cancelled, .badge-failed { background: var(--red-soft); color: var(--red); }
    .badge-cancelled .status-dot { background: var(--red); }

    /* ACTION BUTTONS */
    .actions-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
        justify-content: flex-end;
    }

    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: var(--bg-input);
        border: 1px solid var(--border);
        color: var(--text-body);
        text-decoration: none;
        cursor: pointer;
        transition: .15s ease;
    }
    .action-btn:hover {
        border-color: var(--green);
        background: var(--green-soft);
        color: var(--green);
        transform: translateY(-1px);
    }
    .action-btn-del:hover {
        border-color: var(--red);
        background: var(--red-soft);
        color: var(--red);
    }

    /* SHORTCUT BAR / FLOATING WIDGET */
    .shortcut-box {
        margin-top: 24px;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 16px 20px;
    }
    .shortcut-box.hidden { display: none; }
    .shortcut-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 800;
        font-size: 12px;
        color: var(--text-hi);
        margin-bottom: 12px;
    }
    .shortcut-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 10px;
    }
    .shortcut-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 11px;
    }
    .shortcut-key {
        padding: 2px 7px;
        border-radius: 4px;
        background: var(--bg-header);
        border: 1px solid var(--border);
        font-family: monospace;
        font-size: 10px;
        color: var(--green);
        font-weight: 800;
        box-shadow: 0 1px 2px rgba(0,0,0,0.4);
    }

    /* MODAL */
    .modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(0, 0, 0, .75);
    }
    .modal-backdrop.show { display: flex; }
    .order-modal {
        width: min(720px, 100%);
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 24px 80px rgba(0, 0, 0, .6);
    }
    .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid var(--border);
    }
    .modal-header h3 { margin: 0; color: var(--text-hi); font-size: 16px; }
    .modal-close {
        border: 0; background: transparent; color: var(--text-mute); font-size: 24px; cursor: pointer;
    }
    .modal-body { padding: 20px; }
    .modal-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    .modal-item label {
        display: block;
        margin-bottom: 4px;
        color: var(--text-mute);
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
    }
    .modal-item div {
        color: var(--text-hi);
        font-size: 13px;
    }
    .modal-full { grid-column: 1 / -1; }
    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        padding: 14px 20px;
        border-top: 1px solid var(--border);
        background: var(--bg-card-alt);
    }

    @media (max-width: 900px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }
</style>

<main class="main">
    <section class="content">
        <div class="orders-page">

            <!-- HEADER -->
            <div class="page-header">
                <div>
                    <div class="breadcrumb">
                        <span>Management</span> / <span class="current">Orders</span>
                    </div>
                    <h1>Customer Orders</h1>
                    <p>Track, manage, view invoice and update fulfillment status for customer orders.</p>
                </div>
                <div class="header-actions">
                    <button type="button" class="btn btn-blue" id="printBtn">🖨 Print <kbd>P</kbd></button>
                    <button type="button" class="btn" id="pdfBtn">↓ PDF <kbd>D</kbd></button>
                    <button type="button" class="btn" id="excelBtn">↓ Excel <kbd>E</kbd></button>
                    <button type="button" class="btn" id="toggleShortcutsBtn">⌨ Keys <kbd>?</kbd></button>
                </div>
            </div>

            <!-- NOTICES -->
            <?php if ($actionMessage !== ''): ?>
                <div class="notice notice-success">✓ <?= e($actionMessage) ?></div>
            <?php endif; ?>
            <?php if ($actionError !== ''): ?>
                <div class="notice notice-error">! <?= e($actionError) ?></div>
            <?php endif; ?>
            <?php if ($queryError !== ''): ?>
                <div class="notice notice-error">! <?= e($queryError) ?></div>
            <?php endif; ?>

            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon icon-total">🛍</div>
                    <div>
                        <div class="stat-label">Total Orders</div>
                        <div class="stat-value"><?= $totalOrders ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon icon-pending">⏳</div>
                    <div>
                        <div class="stat-label">Pending / Unpaid</div>
                        <div class="stat-value"><?= $pendingOrders ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon icon-processing">🚚</div>
                    <div>
                        <div class="stat-label">In Transit / Shipped</div>
                        <div class="stat-value"><?= $processingOrders ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon icon-revenue">$</div>
                    <div>
                        <div class="stat-label">Total Volume</div>
                        <div class="stat-value">$<?= number_format($totalRevenue, 2) ?></div>
                    </div>
                </div>
            </div>

            <!-- TABLE CONTAINER -->
            <div class="content-card">
                <div class="content-header">
                    <div class="content-title">
                        <h2>All Orders List</h2>
                        <p>Real-time orders with customer details, order value, and tracking.</p>
                    </div>

                    <div class="filter-group">
                        <div class="search-wrap">
                            <span class="search-icon">⌕</span>
                            <input type="search" id="orderSearch" class="input-search" placeholder="Search Order #, customer name, phone... (Press '/' or 'B')" autocomplete="off">
                        </div>

                        <select id="statusFilter" class="filter-select" title="Shortcut: 'F'">
                            <option value="all">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered / Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                <div class="export-bar">
                    <span class="export-label">Reports & Exports</span>
                    <button type="button" class="btn" id="printBtn2">🖨 Print</button>
                    <button type="button" class="btn" id="pdfBtn2">↓ PDF</button>
                    <button type="button" class="btn" id="excelBtn2">↓ Excel</button>
                </div>

                <div class="table-summary">
                    <div>Showing <strong id="visibleOrderCount"><?= $totalOrders ?></strong> orders</div>
                    <div>Total Orders in Database: <strong><?= $totalOrders ?></strong></div>
                </div>

                <div class="table-wrapper">
                    <?php if (empty($allOrders)): ?>
                        <div class="category-empty" style="text-align:center; padding: 60px 20px;">
                            <div style="font-size:32px; color:var(--text-mute); margin-bottom:10px;">🛍</div>
                            <h3 style="color:var(--text-hi); margin:0;">No Orders Placed Yet</h3>
                            <p style="color:var(--text-mute); margin-top:5px; font-size:12px;">Customer orders will appear here automatically when placed through the store.</p>
                        </div>
                    <?php else: ?>
                        <table class="data-table" id="ordersTable">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allOrders as $ord): ?>
                                    <?php 
                                    $orderId     = (int)($ord['OrderId'] ?? 0);
                                    $orderNum    = (string)($ord['OrderNumber'] ?? ('ORD-' . $orderId));
                                    $custName    = (string)($ord['CustomerFullName'] ?? 'Guest');
                                    $custEmail   = (string)($ord['CustomerEmail'] ?? '—');
                                    $custPhone   = (string)($ord['CustomerPhone'] ?? '—');
                                    $company     = (string)($ord['CompanyName'] ?? '');
                                    $itemsCount  = (int)($ord['TotalItems'] ?? 0);
                                    $totalAmount = (float)($ord['TotalAmount'] ?? $ord['GrandTotal'] ?? $ord['Total'] ?? 0);
                                    $status      = (string)($ord['OrderStatus'] ?? $ord['Status'] ?? 'Pending');
                                    $dateStr     = formatDate($ord['CreatedAt'] ?? '');

                                    $address = trim(($ord['ShippingAddressLine1'] ?? '') . ' ' . ($ord['ShippingAddressLine2'] ?? '') . ', ' . ($ord['ShippingCity'] ?? '') . ' ' . ($ord['ShippingStateProvince'] ?? '') . ' ' . ($ord['ShippingPostalCode'] ?? ''));

                                    $statusKey = strtolower($status);
                                    $badgeClass = 'badge-pending';
                                    if (strpos($statusKey, 'process') !== false) $badgeClass = 'badge-processing';
                                    elseif (strpos($statusKey, 'ship') !== false) $badgeClass = 'badge-shipped';
                                    elseif (strpos($statusKey, 'deliver') !== false || strpos($statusKey, 'complete') !== false) $badgeClass = 'badge-delivered';
                                    elseif (strpos($statusKey, 'cancel') !== false || strpos($statusKey, 'refund') !== false) $badgeClass = 'badge-cancelled';
                                    ?>
                                    <tr 
                                        class="order-row"
                                        tabindex="0"
                                        data-id="<?= $orderId ?>"
                                        data-ordernum="<?= e(strtolower($orderNum)) ?>"
                                        data-customer="<?= e(strtolower($custName)) ?>"
                                        data-email="<?= e(strtolower($custEmail)) ?>"
                                        data-phone="<?= e(strtolower($custPhone)) ?>"
                                        data-status="<?= e($statusKey) ?>"
                                    >
                                        <td>
                                            <a href="view.php?id=<?= $orderId ?>" class="order-number"><?= e($orderNum) ?></a>
                                            <?php if (!empty($ord['TrackingNumber'])): ?>
                                                <div style="font-size:10px; color:var(--blue); font-family:monospace;">
                                                    <?= e($ord['CourierName']) ?>: <?= e($ord['TrackingNumber']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="customer-info">
                                                <strong><?= e($custName) ?></strong>
                                                <span><?= e($custPhone) ?> | <?= e($custEmail) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="items-pill"><?= $itemsCount ?> items</span>
                                        </td>
                                        <td>
                                            <strong style="color:var(--text-hi); font-size:13px;">
                                                $<?= number_format($totalAmount, 2) ?>
                                            </strong>
                                            <?php if (!empty($ord['CouponCode'])): ?>
                                                <div style="font-size:10px; color:var(--green);">Code: <?= e($ord['CouponCode']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $badgeClass ?>">
                                                <span class="status-dot"></span>
                                                <?= e($status) ?>
                                            </span>
                                        </td>
                                        <td style="color:var(--text-mute); font-size:11px;">
                                            <?= e($dateStr) ?>
                                        </td>
                                        <td style="text-align:right;">
                                            <div class="actions-wrap">
                                                <!-- QUICK MODAL VIEW -->
                                                <button 
                                                    type="button" 
                                                    class="action-btn quick-view-btn"
                                                    title="Quick View Details"
                                                    data-ordernum="<?= e($orderNum) ?>"
                                                    data-customer="<?= e($custName) ?>"
                                                    data-email="<?= e($custEmail) ?>"
                                                    data-phone="<?= e($custPhone) ?>"
                                                    data-company="<?= e($company) ?>"
                                                    data-amount="$<?= number_format($totalAmount, 2) ?>"
                                                    data-status="<?= e($status) ?>"
                                                    data-date="<?= e($dateStr) ?>"
                                                    data-address="<?= e($address) ?>"
                                                    data-tracking="<?= e($ord['TrackingNumber'] ?? '') ?>"
                                                    data-courier="<?= e($ord['CourierName'] ?? '') ?>"
                                                >
                                                    ◉
                                                </button>

                                                <!-- FULL ORDER VIEW / INVOICE -->
                                                <a href="view.php?id=<?= $orderId ?>" class="action-btn full-view-btn" title="View Order & Invoice">
                                                    📄
                                                </a>

                                                <!-- DELETE ORDER -->
                                                <form method="POST" action="delete.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete order <?= e($orderNum) ?>?');">
                                                    <input type="hidden" name="order_id" value="<?= $orderId ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                    <button type="submit" class="action-btn action-btn-del" title="Delete Order">✕</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div id="noResults" class="category-empty" style="display:none; text-align:center; padding:50px 20px;">
                            <div style="font-size:30px; color:var(--text-mute); margin-bottom:8px;">⌕</div>
                            <h3 style="color:var(--text-hi); margin:0;">No matching orders found</h3>
                            <p style="color:var(--text-mute); margin-top:5px; font-size:12px;">Try clearing your search query or choosing another status filter.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- KEYBOARD SHORTCUTS INFO -->
            <div class="shortcut-box" id="shortcutBox">
                <div class="shortcut-title">
                    <span>⌨</span>
                    <span>Keyboard Shortcuts Guide</span>
                    <small style="margin-left:auto; color:var(--text-mute); font-weight:normal;">Press <kbd class="shortcut-key">?</kbd> to hide/show</small>
                </div>
                <div class="shortcut-grid">
                    <div class="shortcut-item"><span class="shortcut-key">/</span> or <span class="shortcut-key">B</span> Search Orders</div>
                    <div class="shortcut-item"><span class="shortcut-key">F</span> Filter Status</div>
                    <div class="shortcut-item"><span class="shortcut-key">↓ / J</span> Next Row</div>
                    <div class="shortcut-item"><span class="shortcut-key">↑ / K</span> Previous Row</div>
                    <div class="shortcut-item"><span class="shortcut-key">Enter</span> Quick View Selected</div>
                    <div class="shortcut-item"><span class="shortcut-key">O</span> Open Invoice Selected</div>
                    <div class="shortcut-item"><span class="shortcut-key">P</span> Print Table</div>
                    <div class="shortcut-item"><span class="shortcut-key">D</span> Download PDF</div>
                    <div class="shortcut-item"><span class="shortcut-key">E</span> Export Excel</div>
                    <div class="shortcut-item"><span class="shortcut-key">Esc</span> Close / Reset</div>
                </div>
            </div>

        </div>
    </section>
</main>

<!-- ORDER DETAILS POPUP MODAL -->
<div class="modal-backdrop" id="orderModal" role="dialog" aria-modal="true">
    <div class="order-modal">
        <div class="modal-header">
            <h3 id="modalOrderTitle">Order Details</h3>
            <button type="button" class="modal-close" id="modalClose" aria-label="Close modal">×</button>
        </div>
        <div class="modal-body">
            <div class="modal-grid">
                <div class="modal-item">
                    <label>Customer Name</label>
                    <div id="mCustomer">—</div>
                </div>
                <div class="modal-item">
                    <label>Status</label>
                    <div id="mStatus">—</div>
                </div>
                <div class="modal-item">
                    <label>Phone Number</label>
                    <div id="mPhone">—</div>
                </div>
                <div class="modal-item">
                    <label>Email Address</label>
                    <div id="mEmail">—</div>
                </div>
                <div class="modal-item">
                    <label>Total Amount</label>
                    <div id="mAmount" style="font-weight:800; color:var(--green); font-size:15px;">—</div>
                </div>
                <div class="modal-item">
                    <label>Order Date</label>
                    <div id="mDate">—</div>
                </div>
                <div class="modal-item modal-full">
                    <label>Shipping Address</label>
                    <div id="mAddress" style="color:var(--text-body); font-size:12px;">—</div>
                </div>
                <div class="modal-item modal-full" id="mTrackingRow" style="display:none;">
                    <label>Shipping & Tracking</label>
                    <div id="mTracking" style="color:var(--blue); font-family:monospace;">—</div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn" id="modalCloseBtn">Close <kbd>Esc</kbd></button>
        </div>
    </div>
</div>

<!-- LIBRARIES FOR EXPORT -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.4/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById("orderSearch");
    const statusFilter = document.getElementById("statusFilter");
    const rows = Array.from(document.querySelectorAll("#ordersTable tbody .order-row"));
    const countElement = document.getElementById("visibleOrderCount");
    const noResults = document.getElementById("noResults");
    const modal = document.getElementById("orderModal");
    const shortcutBox = document.getElementById("shortcutBox");
    const toggleShortcutsBtn = document.getElementById("toggleShortcutsBtn");

    let selectedRowIndex = -1;

    function getVisibleRows() {
        return rows.filter(r => r.style.display !== 'none');
    }

    function selectRow(index) {
        const visible = getVisibleRows();
        if (visible.length === 0) return;

        visible.forEach(r => r.classList.remove("keyboard-selected"));

        if (index < 0) index = 0;
        if (index >= visible.length) index = visible.length - 1;

        selectedRowIndex = index;
        const targetRow = visible[selectedRowIndex];
        targetRow.classList.add("keyboard-selected");
        targetRow.scrollIntoView({ block: "nearest", behavior: "smooth" });
    }

    // Real-time Search & Filter
    function filterOrders() {
        const query = (searchInput?.value || '').toLowerCase().trim();
        const selectedStatus = (statusFilter?.value || 'all').toLowerCase();
        let visibleCount = 0;

        rows.forEach(row => {
            const orderNum = row.dataset.ordernum || '';
            const customer = row.dataset.customer || '';
            const email = row.dataset.email || '';
            const phone = row.dataset.phone || '';
            const rowStatus = row.dataset.status || '';

            const matchesSearch = !query || orderNum.includes(query) || customer.includes(query) || email.includes(query) || phone.includes(query);
            const matchesStatus = (selectedStatus === 'all') || rowStatus.includes(selectedStatus);

            if (matchesSearch && matchesStatus) {
                row.style.display = "";
                visibleCount++;
            } else {
                row.style.display = "none";
                row.classList.remove("keyboard-selected");
            }
        });

        selectedRowIndex = -1;
        if (countElement) countElement.textContent = visibleCount;
        if (noResults) noResults.style.display = (visibleCount === 0) ? "block" : "none";
    }

    searchInput?.addEventListener("input", filterOrders);
    statusFilter?.addEventListener("change", filterOrders);

    // Modal Details Populate
    function openModalWithData(btn) {
        document.getElementById("modalOrderTitle").textContent = "Order #" + (btn.dataset.ordernum || '');
        document.getElementById("mCustomer").textContent = btn.dataset.customer || '—';
        document.getElementById("mStatus").textContent = btn.dataset.status || '—';
        document.getElementById("mPhone").textContent = btn.dataset.phone || '—';
        document.getElementById("mEmail").textContent = btn.dataset.email || '—';
        document.getElementById("mAmount").textContent = btn.dataset.amount || '$0.00';
        document.getElementById("mDate").textContent = btn.dataset.date || '—';
        document.getElementById("mAddress").textContent = btn.dataset.address || 'No address provided';

        const trackRow = document.getElementById("mTrackingRow");
        if (btn.dataset.tracking) {
            trackRow.style.display = "block";
            document.getElementById("mTracking").textContent = (btn.dataset.courier ? btn.dataset.courier + ': ' : '') + btn.dataset.tracking;
        } else {
            trackRow.style.display = "none";
        }

        modal.classList.add("show");
    }

    document.querySelectorAll(".quick-view-btn").forEach(btn => {
        btn.addEventListener("click", function(e) {
            e.stopPropagation();
            openModalWithData(this);
        });
    });

    function closeModal() {
        modal.classList.remove("show");
    }

    document.getElementById("modalClose")?.addEventListener("click", closeModal);
    document.getElementById("modalCloseBtn")?.addEventListener("click", closeModal);
    modal?.addEventListener("click", e => { if (e.target === modal) closeModal(); });

    toggleShortcutsBtn?.addEventListener("click", () => {
        shortcutBox?.classList.toggle("hidden");
    });

    // Exports
    const doPrint = () => window.print();
    document.getElementById("printBtn")?.addEventListener("click", doPrint);
    document.getElementById("printBtn2")?.addEventListener("click", doPrint);

    const doExcel = () => {
        const visibleRows = getVisibleRows();
        const data = visibleRows.map(r => ({
            'Order #': r.querySelector('.order-number')?.innerText.trim() || '',
            'Customer': r.querySelector('.customer-info strong')?.innerText.trim() || '',
            'Contact': r.querySelector('.customer-info span')?.innerText.trim() || '',
            'Items': r.querySelector('.items-pill')?.innerText.trim() || '',
            'Total Amount': r.querySelector('td:nth-child(4) strong')?.innerText.trim() || '',
            'Status': r.querySelector('.status-badge')?.innerText.trim() || '',
            'Date': r.querySelector('td:nth-child(6)')?.innerText.trim() || ''
        }));

        if (window.XLSX) {
            const ws = XLSX.utils.json_to_sheet(data);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Orders");
            XLSX.writeFile(wb, "orders-" + new Date().toISOString().slice(0, 10) + ".xlsx");
        }
    };
    document.getElementById("excelBtn")?.addEventListener("click", doExcel);
    document.getElementById("excelBtn2")?.addEventListener("click", doExcel);

    const doPdf = () => {
        if (!window.jspdf || !window.jspdf.jsPDF) {
            alert('PDF library not ready. Please use Print option.');
            return;
        }
        const visibleRows = getVisibleRows();
        const body = visibleRows.map(r => [
            r.querySelector('.order-number')?.innerText.trim() || '',
            r.querySelector('.customer-info strong')?.innerText.trim() || '',
            r.querySelector('.items-pill')?.innerText.trim() || '',
            r.querySelector('td:nth-child(4) strong')?.innerText.trim() || '',
            r.querySelector('.status-badge')?.innerText.trim() || '',
            r.querySelector('td:nth-child(6)')?.innerText.trim() || ''
        ]);

        const doc = new jspdf.jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
        doc.setFontSize(16);
        doc.text('GatewayLinen - Customer Orders', 14, 14);
        doc.setFontSize(9);
        doc.text('Export Date: ' + new Date().toLocaleString(), 14, 20);

        if (typeof doc.autoTable === 'function') {
            doc.autoTable({
                startY: 25,
                head: [['Order #', 'Customer', 'Items', 'Total Amount', 'Status', 'Order Date']],
                body: body,
                styles: { fontSize: 8, cellPadding: 3 },
                headStyles: { fillColor: [16, 185, 129] }
            });
        }
        doc.save('orders-' + new Date().toISOString().slice(0, 10) + '.pdf');
    };
    document.getElementById("pdfBtn")?.addEventListener("click", doPdf);
    document.getElementById("pdfBtn2")?.addEventListener("click", doPdf);

    // Advanced Keyboard Shortcuts
    document.addEventListener("keydown", function(e) {
        const activeElem = document.activeElement;
        const tag = (activeElem?.tagName || '').toLowerCase();
        const isTyping = tag === 'input' || tag === 'textarea' || tag === 'select' || activeElem?.isContentEditable;

        // Escape works globally
        if (e.key === 'Escape') {
            if (modal?.classList.contains('show')) {
                closeModal();
                return;
            }
            if (isTyping) {
                activeElem.blur();
                return;
            }
            if (searchInput?.value) {
                searchInput.value = '';
                filterOrders();
            }
            rows.forEach(r => r.classList.remove("keyboard-selected"));
            selectedRowIndex = -1;
            return;
        }

        // Skip other keys while typing
        if (isTyping) return;

        const key = e.key;
        const upper = key.toUpperCase();

        if (key === '/' || upper === 'B') {
            e.preventDefault();
            searchInput?.focus();
            searchInput?.select();
        } else if (upper === 'F') {
            e.preventDefault();
            statusFilter?.focus();
        } else if (upper === 'P') {
            e.preventDefault();
            doPrint();
        } else if (upper === 'E' || upper === 'X') {
            e.preventDefault();
            doExcel();
        } else if (upper === 'D' || upper === 'V') {
            e.preventDefault();
            doPdf();
        } else if (key === '?' || (e.ctrlKey && key === '/')) {
            e.preventDefault();
            shortcutBox?.classList.toggle('hidden');
        } else if (key === 'ArrowDown' || upper === 'J') {
            e.preventDefault();
            selectRow(selectedRowIndex + 1);
        } else if (key === 'ArrowUp' || upper === 'K') {
            e.preventDefault();
            selectRow(selectedRowIndex - 1);
        } else if (key === 'Enter') {
            const visible = getVisibleRows();
            if (selectedRowIndex >= 0 && visible[selectedRowIndex]) {
                e.preventDefault();
                const qBtn = visible[selectedRowIndex].querySelector('.quick-view-btn');
                if (qBtn) openModalWithData(qBtn);
            }
        } else if (upper === 'O') {
            const visible = getVisibleRows();
            if (selectedRowIndex >= 0 && visible[selectedRowIndex]) {
                e.preventDefault();
                const link = visible[selectedRowIndex].querySelector('a.order-number');
                if (link) window.location.href = link.href;
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>