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
$activeMenu = 'warehouses';
$pageTitle  = 'GatewayLinen | Warehouses Management';

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
if (empty($_SESSION['warehouse_delete_token'])) {
    $_SESSION['warehouse_delete_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['warehouse_delete_token'];

/*
|--------------------------------------------------------------------------
| SUCCESS / ERROR MESSAGE
|--------------------------------------------------------------------------
*/
$actionMessage = trim((string)($_GET['success'] ?? ''));
$actionError   = trim((string)($_GET['error'] ?? ''));

/*
|--------------------------------------------------------------------------
| ESCAPE FUNCTION
|--------------------------------------------------------------------------
*/
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| DATE FORMAT
|--------------------------------------------------------------------------
*/
function dateValue($value): string
{
    if ($value instanceof DateTimeInterface) {
        return $value->format('d M Y, h:i A');
    }
    return !empty($value) ? date('d M Y, h:i A', strtotime($value)) : '—';
}

/*
|--------------------------------------------------------------------------
| FETCH WAREHOUSES
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        WarehouseId,
        WarehouseCode,
        WarehouseName,
        Address,
        City,
        StateProvince,
        PostalCode,
        IsPrimary,
        IsActive,
        CreatedAt
    FROM dbo.Warehouses
    ORDER BY WarehouseId DESC
";

$stmt = sqlsrv_query($conn, $sql);
$warehousesList = [];
$queryError = '';

if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $warehousesList[] = $row;
    }
    sqlsrv_free_stmt($stmt);
} else {
    $queryError = 'Unable to load warehouses right now.';
}

/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/
$totalWarehouses = count($warehousesList);
$activeWarehouses = 0;
$inactiveWarehouses = 0;
$primaryWarehouses = 0;

foreach ($warehousesList as $w) {
    if (!empty($w['IsActive'])) {
        $activeWarehouses++;
    } else {
        $inactiveWarehouses++;
    }

    if (!empty($w['IsPrimary'])) {
        $primaryWarehouses++;
    }
}

/*
|--------------------------------------------------------------------------
| HEADER / SIDEBAR
|--------------------------------------------------------------------------
*/
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

        --red: #ef4444;
        --red-soft: rgba(239, 68, 68, .12);

        --blue: #38bdf8;
        --blue-soft: rgba(56, 189, 248, .15);

        --radius: 10px;
    }

    html, body, .main, .content {
        background: var(--bg-page) !important;
        color: var(--text-body) !important;
    }

    .warehouse-page {
        width: 100%;
        max-width: 1600px;
        margin: 0 auto;
        padding: 0;
    }

    .warehouse-page * { box-sizing: border-box; }

    /* HEADER */
    .warehouse-page-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 24px;
        margin-bottom: 20px;
        padding-bottom: 18px;
        border-bottom: 1px solid var(--border);
    }

    .warehouse-breadcrumb {
        display: flex;
        gap: 8px;
        margin-bottom: 8px;
        color: var(--text-mute);
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .3px;
    }
    .warehouse-breadcrumb .current { color: var(--green); }

    .warehouse-page-header h1 {
        margin: 0;
        color: var(--text-hi);
        font-size: 26px;
        font-weight: 800;
    }

    .warehouse-page-header p {
        margin: 6px 0 0;
        color: var(--text-mute);
        font-size: 12px;
    }

    /* BUTTONS */
    .header-actions, .warehouse-actions, .warehouse-filters, .export-bar {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .header-actions { justify-content: flex-end; }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 38px;
        padding: 0 13px;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--bg-input);
        color: var(--text-body) !important;
        font-size: 11px;
        font-weight: 800;
        text-decoration: none;
        cursor: pointer;
        transition: .18s;
    }
    .btn:hover {
        border-color: var(--green);
        background: var(--green-soft);
        color: var(--green) !important;
    }
    .btn-primary {
        border-color: transparent;
        background: linear-gradient(135deg, #059669, #10b981);
        color: #fff !important;
        box-shadow: 0 6px 16px rgba(16, 185, 129, .2);
    }
    .btn-blue { color: var(--blue) !important; }

    /* STATS */
    .warehouse-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }
    .warehouse-stat-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px 17px;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
    }
    .warehouse-stat-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 9px;
        background: var(--green-soft);
        color: var(--green);
        font-size: 15px;
        font-weight: 800;
    }
    .warehouse-stat-label {
        color: var(--text-mute);
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
    }
    .warehouse-stat-value {
        margin-top: 2px;
        color: var(--text-hi);
        font-size: 20px;
        font-weight: 800;
    }

    /* NOTICE */
    .notice {
        margin-bottom: 14px;
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

    /* CONTENT */
    .warehouse-content {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
    }
    .warehouse-content-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 17px 20px;
        border-bottom: 1px solid var(--border);
    }
    .warehouse-content-title h2 { margin: 0; color: var(--text-hi); font-size: 16px; }
    .warehouse-content-title p { margin: 4px 0 0; color: var(--text-mute); font-size: 11px; }

    /* SEARCH */
    .warehouse-search-wrap {
        position: relative;
        width: 300px;
    }
    .warehouse-search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-mute);
        pointer-events: none;
    }
    .warehouse-search, .warehouse-status-filter {
        height: 36px;
        border: 1px solid var(--border);
        border-radius: 8px;
        outline: none;
        background: var(--bg-input);
        color: var(--text-hi);
        font-size: 12px;
    }
    .warehouse-search { width: 100%; padding: 0 12px 0 34px; }
    .warehouse-status-filter { min-width: 125px; padding: 0 10px; }
    .warehouse-search:focus, .warehouse-status-filter:focus {
        border-color: var(--green);
        box-shadow: 0 0 0 3px rgba(16, 185, 129, .1);
    }

    /* EXPORT */
    .export-bar {
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

    /* SUMMARY */
    .warehouse-table-summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 11px 20px;
        border-bottom: 1px solid var(--border);
    }
    .warehouse-result-text { color: var(--text-mute); font-size: 11px; font-weight: 600; }
    .warehouse-result-text strong { color: var(--text-hi); }

    /* TABLE */
    .warehouse-table-wrapper { width: 100%; overflow-x: auto; }
    .warehouse-table { width: 100%; min-width: 1100px; border-collapse: collapse; }
    .warehouse-table th {
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
    .warehouse-table td {
        padding: 12px 16px;
        background: transparent;
        border-bottom: 1px solid var(--border-soft);
        color: var(--text-body);
        font-size: 12px;
        vertical-align: middle;
    }
    .warehouse-table tbody tr:hover { background: var(--bg-hover); }
    .warehouse-table tbody tr.keyboard-selected {
        outline: 2px solid var(--green);
        outline-offset: -2px;
        background: var(--green-soft);
    }

    .order-box {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 42px;
        height: 28px;
        padding: 0 9px;
        border-radius: 7px;
        background: var(--bg-input);
        border: 1px solid var(--border);
        color: var(--green);
        font-size: 11px;
        font-weight: 800;
    }

    .warehouse-name { color: var(--text-hi); font-size: 13px; font-weight: 700; }
    .warehouse-code { margin-top: 3px; color: var(--blue); font-size: 10px; font-family: monospace; }
    .warehouse-date { color: var(--text-mute); font-size: 10px; }

    /* STATUS */
    .warehouse-status {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-height: 25px;
        padding: 0 10px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
    }
    .warehouse-status-dot { width: 6px; height: 6px; border-radius: 50%; }
    .warehouse-status-active { background: var(--green-soft); color: var(--green); }
    .warehouse-status-active .warehouse-status-dot { background: var(--green); box-shadow: 0 0 6px var(--green); }
    .warehouse-status-inactive { background: var(--red-soft); color: #f87171; }
    .warehouse-status-inactive .warehouse-status-dot { background: #f87171; }
    .primary-badge {
        display: inline-block; font-size: 9px; font-weight: 800; padding: 2px 6px;
        border-radius: 4px; background: var(--blue-soft); color: var(--blue); margin-left: 6px; text-transform: uppercase;
    }

    /* ACTION */
    .warehouse-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--bg-input);
        color: var(--text-body) !important;
        text-decoration: none;
        cursor: pointer;
    }
    .warehouse-action:hover {
        border-color: var(--green);
        background: var(--green-soft);
        color: var(--green) !important;
    }
    .warehouse-action-delete:hover {
        border-color: rgba(239, 68, 68, .5);
        background: var(--red-soft);
        color: var(--red) !important;
    }

    /* EMPTY */
    .warehouse-empty, .warehouse-no-result { padding: 65px 20px; text-align: center; }
    .warehouse-empty-icon, .warehouse-no-result-icon { margin-bottom: 12px; color: var(--green); font-size: 28px; }
    .warehouse-empty h3, .warehouse-no-result h3 { margin: 0; color: var(--text-hi); font-size: 16px; }
    .warehouse-empty p, .warehouse-no-result p { margin: 6px 0 0; color: var(--text-mute); font-size: 12px; }

    /* SHORTCUT */
    .shortcut-help-box {
        margin-top: 16px;
        padding: 16px 20px;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: var(--bg-card);
    }
    .shortcut-help-box.hidden { display: none; }
    .shortcut-help-title {
        display: flex; align-items: center; gap: 9px; margin-bottom: 12px; color: var(--text-hi); font-size: 13px; font-weight: 800;
    }
    .shortcut-help-title small { margin-left: auto; color: var(--text-mute); font: 600 10px monospace; }
    .shortcut-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
    .shortcut-item {
        display: flex; align-items: center; gap: 9px; padding: 8px 10px; border: 1px solid var(--border-soft); border-radius: 8px; background: var(--bg-input);
    }
    .shortcut-key {
        display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 25px; padding: 0 7px; border-radius: 5px;
        background: #0a1119; border: 1px solid var(--border); color: var(--green); font: 800 10px monospace;
    }
    .shortcut-desc { color: var(--text-body); font-size: 11px; font-weight: 600; }

    /* MODAL */
    .modal-backdrop {
        position: fixed; inset: 0; z-index: 9999; display: none; align-items: center; justify-content: center; padding: 20px; background: rgba(0, 0, 0, .72);
    }
    .modal-backdrop.show { display: flex; }
    .warehouse-modal {
        width: min(700px, 100%); max-height: 90vh; overflow: auto; background: var(--bg-card); border: 1px solid var(--border); border-radius: 14px; box-shadow: 0 24px 80px rgba(0, 0, 0, .5);
    }
    .modal-header { display: flex; align-items: center; justify-content: space-between; padding: 15px 18px; border-bottom: 1px solid var(--border); }
    .modal-header h3 { margin: 0; color: var(--text-hi); font-size: 15px; }
    .modal-close { border: 0; background: transparent; color: var(--text-mute); font-size: 22px; cursor: pointer; }
    .modal-body { padding: 18px; }
    .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .detail-item label { display: block; margin-bottom: 4px; color: var(--text-mute); font-size: 9px; font-weight: 800; text-transform: uppercase; }
    .detail-item div { color: var(--text-hi); font-size: 12px; line-height: 1.5; }
    .detail-full { grid-column: 1/-1; }
    .modal-footer { display: flex; justify-content: flex-end; gap: 8px; padding: 14px 18px; border-top: 1px solid var(--border); }

    @media print {
        @page { size: landscape; margin: 10mm; }
        html, body, .main, .content { background: #fff !important; color: #111 !important; width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .warehouse-page { max-width: none !important; width: 100% !important; }
        .warehouse-page-header, .warehouse-stats, .warehouse-filters, .export-bar, .warehouse-actions,
        .shortcut-help-box, .notice, .modal-backdrop, th:last-child, td:last-child { display: none !important; }
        .warehouse-content { border: 0 !important; background: none !important; box-shadow: none !important; }
        .warehouse-table-wrapper { overflow: visible !important; }
        .warehouse-table { width: 100% !important; min-width: 0 !important; border-collapse: collapse !important; }
        .warehouse-table th, .warehouse-table td { color: #111 !important; background: #fff !important; border: 1px solid #ddd !important; padding: 8px !important; font-size: 11px !important; }
        .warehouse-table th { background: #f2f2f2 !important; }
    }
</style>

<main class="main">
    <section class="content">
        <div class="warehouse-page">

            <!-- PAGE HEADER -->
            <div class="warehouse-page-header">
                <div>
                    <div class="warehouse-breadcrumb">
                        <span>Inventory</span>
                        <span>/</span>
                        <span class="current">Warehouses</span>
                    </div>
                    <h1>Warehouses Management</h1>
                    <p>Manage fulfillment centers, storage locations, status, reports and keyboard shortcuts.</p>
                </div>

                <div class="header-actions">
                    <button type="button" class="btn btn-blue" id="printBtn">🖨 Print <small>P</small></button>
                    <button type="button" class="btn" id="pdfBtn">↓ PDF <small>V</small></button>
                    <button type="button" class="btn" id="excelBtn">↓ Excel <small>X</small></button>
                    <a href="add.php" class="btn btn-primary" id="addWarehouseBtn">＋ Add Warehouse <small>A</small></a>
                </div>
            </div>

            <!-- MESSAGES -->
            <?php if ($actionMessage !== ''): ?>
                <div class="notice notice-success"><?= e($actionMessage) ?></div>
            <?php endif; ?>

            <?php if ($actionError !== '' || $queryError !== ''): ?>
                <div class="notice notice-error"><?= e($actionError ?: $queryError) ?></div>
            <?php endif; ?>

            <!-- STATISTICS -->
            <div class="warehouse-stats">
                <div class="warehouse-stat-item">
                    <div class="warehouse-stat-icon">#</div>
                    <div>
                        <div class="warehouse-stat-label">Total Warehouses</div>
                        <div class="warehouse-stat-value"><?= $totalWarehouses ?></div>
                    </div>
                </div>

                <div class="warehouse-stat-item">
                    <div class="warehouse-stat-icon">✓</div>
                    <div>
                        <div class="warehouse-stat-label">Active</div>
                        <div class="warehouse-stat-value"><?= $activeWarehouses ?></div>
                    </div>
                </div>

                <div class="warehouse-stat-item">
                    <div class="warehouse-stat-icon">○</div>
                    <div>
                        <div class="warehouse-stat-label">Inactive</div>
                        <div class="warehouse-stat-value"><?= $inactiveWarehouses ?></div>
                    </div>
                </div>

                <div class="warehouse-stat-item">
                    <div class="warehouse-stat-icon">★</div>
                    <div>
                        <div class="warehouse-stat-label">Primary Hubs</div>
                        <div class="warehouse-stat-value"><?= $primaryWarehouses ?></div>
                    </div>
                </div>
            </div>

            <!-- WAREHOUSE CONTENT -->
            <div class="warehouse-content">
                <div class="warehouse-content-header">
                    <div class="warehouse-content-title">
                        <h2>Warehouse List</h2>
                        <p>Code, name, address, status, and created date are visible here.</p>
                    </div>

                    <div class="warehouse-filters">
                        <div class="warehouse-search-wrap">
                            <span class="warehouse-search-icon">⌕</span>
                            <input
                                type="search"
                                id="warehouseSearch"
                                class="warehouse-search"
                                placeholder="Search warehouse, code, city..."
                                autocomplete="off">
                        </div>

                        <select id="warehouseStatusFilter" class="warehouse-status-filter">
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <!-- EXPORT BAR -->
                <div class="export-bar">
                    <span class="export-label">Reports & Export</span>
                    <button type="button" class="btn" id="printBtn2">🖨 Print</button>
                    <button type="button" class="btn" id="pdfBtn2">↓ PDF</button>
                    <button type="button" class="btn" id="excelBtn2">↓ Excel</button>
                </div>

                <!-- TABLE SUMMARY -->
                <div class="warehouse-table-summary">
                    <div class="warehouse-result-text">
                        Showing <strong id="visibleWarehouseCount"><?= count($warehousesList) ?></strong> warehouses
                    </div>
                    <div class="warehouse-result-text">
                        Total: <strong><?= $totalWarehouses ?></strong>
                    </div>
                </div>

                <!-- TABLE -->
                <div class="warehouse-table-wrapper">
                    <?php if (empty($warehousesList)): ?>
                        <div class="warehouse-empty">
                            <div class="warehouse-empty-icon">◈</div>
                            <h3>No Warehouses Found</h3>
                            <p>Create your first warehouse location to start managing stock.</p>
                            <a href="add.php" class="btn btn-primary" style="margin-top:16px">＋ Add First Warehouse</a>
                        </div>
                    <?php else: ?>
                        <table class="warehouse-table" id="warehouseTable">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Warehouse</th>
                                    <th>Address Location</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $orderNo = 1;
                                foreach ($warehousesList as $w): 
                                    $wId = (int)($w['WarehouseId'] ?? 0);
                                    $wCode = (string)($w['WarehouseCode'] ?? '');
                                    $wName = (string)($w['WarehouseName'] ?? '');
                                    $wAddress = (string)($w['Address'] ?? '');
                                    $wCity = (string)($w['City'] ?? '');
                                    $wState = (string)($w['StateProvince'] ?? '');
                                    $wPostal = (string)($w['PostalCode'] ?? '');
                                    $isPrimary = !empty($w['IsPrimary']);
                                    $isActive = !empty($w['IsActive']);
                                    $createdAt = dateValue($w['CreatedAt'] ?? '');
                                    $currentOrder = $orderNo++;

                                    $fullAddress = trim($wAddress . ', ' . $wCity . ', ' . $wState . ' ' . $wPostal, ', ');
                                ?>
                                    <tr
                                        class="warehouse-row"
                                        data-id="<?= $wId ?>"
                                        data-order="<?= $currentOrder ?>"
                                        data-status="<?= $isActive ? 'active' : 'inactive' ?>"
                                        data-name="<?= e(strtolower($wName)) ?>"
                                        data-code="<?= e(strtolower($wCode)) ?>"
                                        data-address="<?= e(strtolower($fullAddress)) ?>">

                                        <td>
                                            <span class="order-box"><?= $currentOrder ?></span>
                                        </td>

                                        <td>
                                            <div>
                                                <div class="warehouse-name">
                                                    <?= e($wName) ?>
                                                    <?php if ($isPrimary): ?>
                                                        <span class="primary-badge">Primary</span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($wCode !== ''): ?>
                                                    <div class="warehouse-code">CODE: <?= e($wCode) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </td>

                                        <td>
                                            <div style="max-width:320px; white-space:normal; line-height:1.4;">
                                                <?= $fullAddress !== '' ? e($fullAddress) : '—' ?>
                                            </div>
                                        </td>

                                        <td>
                                            <?php if ($isActive): ?>
                                                <span class="warehouse-status warehouse-status-active">
                                                    <span class="warehouse-status-dot"></span> Active
                                                </span>
                                            <?php else: ?>
                                                <span class="warehouse-status warehouse-status-inactive">
                                                    <span class="warehouse-status-dot"></span> Inactive
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <div class="warehouse-date"><?= e($createdAt) ?></div>
                                        </td>

                                        <td>
                                            <div class="warehouse-actions">
                                                <button
                                                    type="button"
                                                    class="warehouse-action detail-btn"
                                                    title="View full details"
                                                    data-id="<?= $wId ?>"
                                                    data-name="<?= e($wName) ?>"
                                                    data-code="<?= e($wCode) ?>"
                                                    data-address="<?= e($fullAddress) ?>"
                                                    data-status="<?= $isActive ? 'Active' : 'Inactive' ?>"
                                                    data-primary="<?= $isPrimary ? 'Yes' : 'No' ?>"
                                                    data-created="<?= e($createdAt) ?>"
                                                    data-order="<?= $currentOrder ?>">
                                                    ◉
                                                </button>

                                                <a href="edit.php?id=<?= $wId ?>" class="warehouse-action edit-btn" title="Edit Warehouse">✎</a>

                                                <form method="POST" action="delete.php" class="delete-form" style="display:inline">
                                                    <input type="hidden" name="warehouse_id" value="<?= $wId ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                    <button type="submit" class="warehouse-action warehouse-action-delete delete-warehouse-btn" title="Delete Warehouse">×</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div id="warehouseNoResult" class="warehouse-no-result" style="display:none">
                            <div class="warehouse-no-result-icon">⌕</div>
                            <h3>No matching warehouses</h3>
                            <p>Try changing your search or status filter.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- SHORTCUTS -->
            <div class="shortcut-help-box" id="shortcutHelpBox">
                <div class="shortcut-help-title">
                    <span>⌨</span>
                    <span>Keyboard Shortcuts</span>
                    <small>A B C D E P V X H • Esc</small>
                </div>

                <div class="shortcut-grid">
                    <div class="shortcut-item"><span class="shortcut-key">A</span><span class="shortcut-desc">Add Warehouse</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">B</span><span class="shortcut-desc">Focus search</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">C</span><span class="shortcut-desc">Status filter</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">D</span><span class="shortcut-desc">Delete selected</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">E</span><span class="shortcut-desc">Edit selected</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">P</span><span class="shortcut-desc">Print</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">V</span><span class="shortcut-desc">Download PDF</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">X</span><span class="shortcut-desc">Download Excel</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">H</span><span class="shortcut-desc">Toggle shortcuts</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">Esc</span><span class="shortcut-desc">Close / Clear</span></div>
                </div>
            </div>

        </div>
    </section>
</main>

<!-- DETAILS MODAL -->
<div class="modal-backdrop" id="warehouseModal" aria-hidden="true">
    <div class="warehouse-modal" role="dialog" aria-modal="true" aria-labelledby="warehouseModalTitle">
        <div class="modal-header">
            <h3 id="warehouseModalTitle">Warehouse Details</h3>
            <button type="button" class="modal-close" id="modalCloseBtn">×</button>
        </div>

        <div class="modal-body">
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Warehouse Name</label>
                    <div id="detailName">—</div>
                </div>

                <div class="detail-item">
                    <label>Warehouse Code</label>
                    <div id="detailCode">—</div>
                </div>

                <div class="detail-item">
                    <label>Status</label>
                    <div id="detailStatus">—</div>
                </div>

                <div class="detail-item">
                    <label>Primary Hub</label>
                    <div id="detailPrimary">—</div>
                </div>

                <div class="detail-item">
                    <label>Warehouse No.</label>
                    <div id="detailOrder">—</div>
                </div>

                <div class="detail-item">
                    <label>Created Date</label>
                    <div id="detailCreated">—</div>
                </div>

                <div class="detail-item detail-full">
                    <label>Address Location</label>
                    <div id="detailAddress">—</div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn" id="modalCloseBtn2">Close</button>
        </div>
    </div>
</div>

<!-- PDF / EXCEL LIBRARIES -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.4/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
    (function() {
        'use strict';

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput  = document.getElementById('warehouseSearch');
            const statusFilter = document.getElementById('warehouseStatusFilter');
            const table        = document.getElementById('warehouseTable');
            const countElement = document.getElementById('visibleWarehouseCount');
            const noResult     = document.getElementById('warehouseNoResult');
            const shortcutBox  = document.getElementById('shortcutHelpBox');
            const modal        = document.getElementById('warehouseModal');

            function rows() {
                return table ? Array.from(table.querySelectorAll('tbody .warehouse-row')) : [];
            }

            function visibleRows() {
                return rows().filter(row => row.style.display !== 'none');
            }

            function selectFirstVisible(scroll) {
                rows().forEach(r => r.classList.remove('keyboard-selected'));
                const first = visibleRows()[0];
                if (first) {
                    first.classList.add('keyboard-selected');
                    if (scroll) first.scrollIntoView({ block: 'nearest' });
                }
            }

            function filterWarehouses() {
                if (!table) return;

                const q = (searchInput?.value || '').toLowerCase().trim();
                const status = statusFilter?.value || 'all';

                let visibleCount = 0;

                rows().forEach(function(row) {
                    const text = [
                        row.dataset.name,
                        row.dataset.code,
                        row.dataset.address,
                        row.innerText
                    ].join(' ').toLowerCase();

                    const statusMatch = (status === 'all' || row.dataset.status === status);
                    const queryMatch = (!q || text.includes(q));

                    if (queryMatch && statusMatch) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (countElement) countElement.textContent = visibleCount;
                if (noResult) noResult.style.display = visibleCount === 0 ? 'block' : 'none';

                selectFirstVisible(false);
            }

            function printWarehouses() { window.print(); }

            function excelWarehouses() {
                const data = visibleRows().map(function(row) {
                    return {
                        'Order': row.querySelector('.order-box')?.innerText.trim() || '',
                        'Warehouse Name': row.querySelector('.warehouse-name')?.innerText.trim() || '',
                        'Code': row.dataset.code || '',
                        'Address': row.querySelector('td:nth-child(3)')?.innerText.trim() || '',
                        'Status': row.dataset.status === 'active' ? 'Active' : 'Inactive',
                        'Created': row.querySelector('.warehouse-date')?.innerText.trim() || ''
                    };
                });

                if (window.XLSX) {
                    const ws = XLSX.utils.json_to_sheet(data);
                    ws['!cols'] = [{ wch: 10 }, { wch: 28 }, { wch: 15 }, { wch: 45 }, { wch: 12 }, { wch: 24 }];
                    const wb = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(wb, ws, 'Warehouses');
                    XLSX.writeFile(wb, 'warehouses-' + new Date().toISOString().slice(0, 10) + '.xlsx');
                }
            }

            function pdfWarehouses() {
                if (!window.jspdf || !window.jspdf.jsPDF) {
                    alert('PDF library is not loaded. Use Print and choose Save as PDF.');
                    return;
                }

                const body = visibleRows().map(function(row) {
                    return [
                        row.querySelector('.order-box')?.innerText.trim() || '',
                        row.querySelector('.warehouse-name')?.innerText.trim() || '',
                        row.dataset.code || '',
                        row.querySelector('td:nth-child(3)')?.innerText.trim() || '—',
                        row.dataset.status === 'active' ? 'Active' : 'Inactive',
                        row.querySelector('.warehouse-date')?.innerText.trim() || ''
                    ];
                });

                const doc = new jspdf.jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
                doc.setFontSize(16);
                doc.text('GatewayLinen - Warehouses Management', 14, 14);
                doc.setFontSize(9);
                doc.text('Generated: ' + new Date().toLocaleString(), 14, 20);

                if (typeof doc.autoTable === 'function') {
                    doc.autoTable({
                        startY: 26,
                        head: [['Order', 'Warehouse Name', 'Code', 'Address Location', 'Status', 'Created']],
                        body: body,
                        styles: { fontSize: 8, cellPadding: 3 },
                        headStyles: { fillColor: [16, 185, 129] }
                    });
                }

                doc.save('warehouses-' + new Date().toISOString().slice(0, 10) + '.pdf');
            }

            document.getElementById('printBtn')?.addEventListener('click', printWarehouses);
            document.getElementById('printBtn2')?.addEventListener('click', printWarehouses);
            document.getElementById('pdfBtn')?.addEventListener('click', pdfWarehouses);
            document.getElementById('pdfBtn2')?.addEventListener('click', pdfWarehouses);
            document.getElementById('excelBtn')?.addEventListener('click', excelWarehouses);
            document.getElementById('excelBtn2')?.addEventListener('click', excelWarehouses);

            searchInput?.addEventListener('input', filterWarehouses);
            statusFilter?.addEventListener('change', filterWarehouses);

            function openDetails(btn) {
                document.getElementById('detailName').textContent = btn.dataset.name || '—';
                document.getElementById('detailCode').textContent = btn.dataset.code || '—';
                document.getElementById('detailStatus').textContent = btn.dataset.status || '—';
                document.getElementById('detailPrimary').textContent = btn.dataset.primary || '—';
                document.getElementById('detailOrder').textContent = btn.dataset.order || '—';
                document.getElementById('detailCreated').textContent = btn.dataset.created || '—';
                document.getElementById('detailAddress').textContent = btn.dataset.address || '—';

                modal.classList.add('show');
                modal.setAttribute('aria-hidden', 'false');
            }

            document.querySelectorAll('.detail-btn').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    openDetails(this);
                });
            });

            function closeModal() {
                if (!modal) return;
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
            }

            document.getElementById('modalCloseBtn')?.addEventListener('click', closeModal);
            document.getElementById('modalCloseBtn2')?.addEventListener('click', closeModal);

            modal?.addEventListener('click', function(e) {
                if (e.target === modal) closeModal();
            });

            document.querySelectorAll('.delete-warehouse-btn').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    const form = btn.closest('.delete-form');
                    if (!form) return;
                    const wName = btn.closest('.warehouse-row')?.querySelector('.warehouse-name')?.innerText.trim() || 'this warehouse';
                    const confirmed = confirm('Delete "' + wName + '"?\n\nThis action cannot be undone.');
                    if (!confirmed) e.preventDefault();
                });
            });

            document.addEventListener('keydown', function(e) {
                const tag = (e.target?.tagName || '').toLowerCase();
                const typing = tag === 'input' || tag === 'textarea' || tag === 'select' || e.target?.isContentEditable;

                if (typing) return;

                const key = (e.key || '').toUpperCase();

                if (['A', 'B', 'C', 'D', 'E', 'P', 'V', 'X', 'H'].includes(key)) {
                    e.preventDefault();
                    e.stopPropagation();
                }

                if (key === 'A') {
                    document.getElementById('addWarehouseBtn')?.click();
                } else if (key === 'B') {
                    searchInput?.focus();
                    searchInput?.select();
                } else if (key === 'C') {
                    statusFilter?.focus();
                } else if (key === 'D') {
                    const selected = document.querySelector('.warehouse-row.keyboard-selected') || visibleRows()[0];
                    selected?.querySelector('.delete-warehouse-btn')?.click();
                } else if (key === 'E') {
                    const selected = document.querySelector('.warehouse-row.keyboard-selected') || visibleRows()[0];
                    selected?.querySelector('.edit-btn')?.click();
                } else if (key === 'P') {
                    printWarehouses();
                } else if (key === 'V') {
                    pdfWarehouses();
                } else if (key === 'X') {
                    excelWarehouses();
                } else if (key === 'H') {
                    shortcutBox?.classList.toggle('hidden');
                } else if (key === 'ESCAPE') {
                    if (modal?.classList.contains('show')) {
                        closeModal();
                    } else if (searchInput?.value) {
                        searchInput.value = '';
                        filterWarehouses();
                    }
                    searchInput?.blur();
                    statusFilter?.blur();
                }
            }, true);

            rows().forEach(function(row) {
                row.addEventListener('click', function(e) {
                    if (e.target.closest('button, a, form')) return;
                    rows().forEach(r => r.classList.remove('keyboard-selected'));
                    row.classList.add('keyboard-selected');
                });
            });

            filterWarehouses();
        });
    })();
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>