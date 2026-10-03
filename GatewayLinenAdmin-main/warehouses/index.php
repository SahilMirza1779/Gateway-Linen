<?php

session_start();

/*
|--------------------------------------------------------------------------
| GatewayLinen Admin - Warehouses Management
|--------------------------------------------------------------------------
| File:
| GatewayLinenadmin/warehouses/index.php
|--------------------------------------------------------------------------
*/

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
| ADMIN INFORMATION
|--------------------------------------------------------------------------
*/

$adminName = $_SESSION['admin_name']
    ?? $_SESSION['admin_username']
    ?? 'GatewayLinen Administrator';

$adminRole = $_SESSION['admin_role']
    ?? 'Administrator';

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
| URL MESSAGES
|--------------------------------------------------------------------------
*/

$actionMessage = trim((string)($_GET['success'] ?? ''));
$actionError   = trim((string)($_GET['error'] ?? ''));

/*
|--------------------------------------------------------------------------
| HELPER - ESCAPE
|--------------------------------------------------------------------------
*/

function warehouse_index_escape($value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

/*
|--------------------------------------------------------------------------
| FETCH WAREHOUSES
|--------------------------------------------------------------------------
*/

$warehousesList = [];
$queryError = '';

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
        IsActive
    FROM dbo.Warehouses
    ORDER BY WarehouseId DESC
";

$stmt = sqlsrv_query($conn, $sql);

if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array(
        $stmt,
        SQLSRV_FETCH_ASSOC
    )) {
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

$totalWarehouses    = count($warehousesList);
$activeWarehouses   = 0;
$inactiveWarehouses = 0;
$primaryWarehouses  = 0;

foreach ($warehousesList as $warehouse) {
    if ((int)($warehouse['IsActive'] ?? 0) === 1) {
        $activeWarehouses++;
    } else {
        $inactiveWarehouses++;
    }

    if ((int)($warehouse['IsPrimary'] ?? 0) === 1) {
        $primaryWarehouses++;
    }
}

/*
|--------------------------------------------------------------------------
| HEADER + SIDEBAR
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<style>
:root {
    --wh-page: #0a1119;
    --wh-card: #111b26;
    --wh-card-alt: #0f1823;
    --wh-card-hover: #16222e;
    --wh-header: #0d1620;
    --wh-input: #0d1620;
    --wh-border: #243446;
    --wh-border-soft: #1b2a3a;
    --wh-text: #f0f4f8;
    --wh-text-body: #a8b8c8;
    --wh-text-muted: #71859a;
    --wh-green: #10b981;
    --wh-green-dark: #059669;
    --wh-green-soft: rgba(16, 185, 129, .12);
    --wh-red: #ef4444;
    --wh-red-soft: rgba(239, 68, 68, .12);
    --wh-blue: #38bdf8;
    --wh-blue-soft: rgba(56, 189, 248, .12);
    --wh-white: #ffffff;
    --wh-shadow: 0 15px 40px rgba(0, 0, 0, .20);
    --wh-modal-overlay: rgba(0, 0, 0, .72);
    --wh-radius: 12px;
}

html[data-theme="light"] {
    --wh-page: #f4f7fb;
    --wh-card: #ffffff;
    --wh-card-alt: #f8fafc;
    --wh-card-hover: #eef2f7;
    --wh-header: #f8fafc;
    --wh-input: #ffffff;
    --wh-border: #d7e0ea;
    --wh-border-soft: #e6ecf2;
    --wh-text: #172033;
    --wh-text-body: #475569;
    --wh-text-muted: #64748b;
    --wh-green: #059669;
    --wh-green-dark: #047857;
    --wh-green-soft: rgba(5, 150, 105, .10);
    --wh-red: #dc2626;
    --wh-red-soft: rgba(220, 38, 38, .10);
    --wh-blue: #0284c7;
    --wh-blue-soft: rgba(2, 132, 199, .10);
    --wh-white: #ffffff;
    --wh-shadow: 0 15px 40px rgba(15, 23, 42, .08);
    --wh-modal-overlay: rgba(15, 23, 42, .48);
}

html, body {
    min-height: 100%;
    background: var(--wh-page) !important;
    color: var(--wh-text-body) !important;
}

main, .main, .content, .main-content, .content-wrapper, .page-content, .dashboard-content, .admin-content {
    background: var(--wh-page) !important;
    color: var(--wh-text-body) !important;
}

.warehouse-page {
    width: 100%;
    max-width: 1600px;
    margin: 0 auto;
    padding: 0 0 30px;
}

.warehouse-page *, .warehouse-page *::before, .warehouse-page *::after {
    box-sizing: border-box;
}

.warehouse-page-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 25px;
    margin-bottom: 20px;
    padding-bottom: 18px;
    border-bottom: 1px solid var(--wh-border);
}

.warehouse-breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    color: var(--wh-text-muted);
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
}

.warehouse-breadcrumb .current {
    color: var(--wh-green);
}

.warehouse-page-header h1 {
    margin: 0;
    color: var(--wh-text);
    font-size: 26px;
    font-weight: 800;
}

.warehouse-page-header p {
    margin: 7px 0 0;
    color: var(--wh-text-muted);
    font-size: 12px;
}

.header-actions, .warehouse-actions, .warehouse-filters, .export-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.header-actions {
    justify-content: flex-end;
}

.btn {
    min-height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 13px;
    border: 1px solid var(--wh-border);
    border-radius: 8px;
    background: var(--wh-input);
    color: var(--wh-text-body) !important;
    font-size: 11px;
    font-weight: 800;
    text-decoration: none;
    cursor: pointer;
}

.btn:hover {
    border-color: var(--wh-green);
    background: var(--wh-green-soft);
    color: var(--wh-green) !important;
}

.btn-primary {
    border-color: transparent;
    background: var(--wh-green);
    color: #ffffff !important;
}

.btn-blue {
    color: var(--wh-blue) !important;
}

.warehouse-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 20px;
}

.warehouse-stat-item {
    min-height: 84px;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px 17px;
    background: var(--wh-card);
    border: 1px solid var(--wh-border);
    border-radius: var(--wh-radius);
    box-shadow: var(--wh-shadow);
}

.warehouse-stat-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 9px;
    background: var(--wh-green-soft);
    color: var(--wh-green);
    font-weight: 800;
}

.warehouse-stat-label {
    color: var(--wh-text-muted);
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
}

.warehouse-stat-value {
    margin-top: 3px;
    color: var(--wh-text);
    font-size: 21px;
    font-weight: 800;
}

.notice {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 14px;
    padding: 12px 14px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
}

.notice-success {
    border: 1px solid rgba(16, 185, 129, .30);
    background: var(--wh-green-soft);
    color: var(--wh-green);
}

.notice-error {
    border: 1px solid rgba(239, 68, 68, .30);
    background: var(--wh-red-soft);
    color: var(--wh-red);
}

.warehouse-content {
    overflow: hidden;
    background: var(--wh-card);
    border: 1px solid var(--wh-border);
    border-radius: var(--wh-radius);
    box-shadow: var(--wh-shadow);
}

.warehouse-content-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 17px 20px;
    border-bottom: 1px solid var(--wh-border);
}

.warehouse-content-title h2 {
    margin: 0;
    color: var(--wh-text);
    font-size: 17px;
    font-weight: 800;
}

.warehouse-content-title p {
    margin: 5px 0 0;
    color: var(--wh-text-muted);
    font-size: 11px;
}

.warehouse-search-wrap {
    position: relative;
    width: 300px;
}

.warehouse-search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--wh-text-muted);
}

.warehouse-search, .warehouse-status-filter {
    height: 38px;
    border: 1px solid var(--wh-border);
    border-radius: 8px;
    outline: none;
    background: var(--wh-input);
    color: var(--wh-text);
    font-size: 12px;
}

.warehouse-search {
    width: 100%;
    padding: 0 12px 0 35px;
}

.warehouse-status-filter {
    min-width: 140px;
    padding: 0 10px;
    cursor: pointer;
}

.export-bar {
    min-height: 48px;
    padding: 9px 20px;
    border-bottom: 1px solid var(--wh-border);
    background: var(--wh-card-alt);
}

.export-label {
    margin-right: auto;
    color: var(--wh-text-muted);
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
}

.warehouse-table-summary {
    min-height: 43px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 20px;
    border-bottom: 1px solid var(--wh-border);
}

.warehouse-result-text {
    color: var(--wh-text-muted);
    font-size: 11px;
    font-weight: 600;
}

.warehouse-result-text strong {
    color: var(--wh-text);
}

.warehouse-table-wrapper {
    width: 100%;
    overflow-x: auto;
}

.warehouse-table {
    width: 100%;
    min-width: 1050px;
    border-collapse: collapse;
}

.warehouse-table th {
    height: 44px;
    padding: 0 16px;
    background: var(--wh-header);
    border-bottom: 1px solid var(--wh-border);
    color: var(--wh-text-muted);
    font-size: 10px;
    font-weight: 800;
    text-align: left;
    text-transform: uppercase;
}

.warehouse-table td {
    padding: 13px 16px;
    border-bottom: 1px solid var(--wh-border-soft);
    color: var(--wh-text-body);
    font-size: 12px;
    vertical-align: middle;
}

.warehouse-table tbody tr:hover {
    background: var(--wh-card-hover);
}

.order-box {
    min-width: 42px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 9px;
    border-radius: 7px;
    background: var(--wh-input);
    border: 1px solid var(--wh-border);
    color: var(--wh-green);
    font-size: 11px;
    font-weight: 800;
}

.warehouse-name {
    color: var(--wh-text);
    font-size: 13px;
    font-weight: 700;
}

.warehouse-code {
    margin-top: 3px;
    color: var(--wh-blue);
    font-size: 10px;
    font-family: Consolas, monospace;
}

.warehouse-address {
    max-width: 340px;
    line-height: 1.5;
}

.primary-badge {
    display: inline-flex;
    align-items: center;
    margin-left: 6px;
    padding: 3px 7px;
    border-radius: 5px;
    background: var(--wh-blue-soft);
    color: var(--wh-blue);
    font-size: 8px;
    font-weight: 800;
    text-transform: uppercase;
}

.warehouse-status {
    min-height: 25px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 0 10px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 800;
}

.warehouse-status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
}

.warehouse-status-active {
    background: var(--wh-green-soft);
    color: var(--wh-green);
}

.warehouse-status-active .warehouse-status-dot {
    background: var(--wh-green);
    box-shadow: 0 0 6px var(--wh-green);
}

.warehouse-status-inactive {
    background: var(--wh-red-soft);
    color: var(--wh-red);
}

.warehouse-status-inactive .warehouse-status-dot {
    background: var(--wh-red);
}

.warehouse-actions {
    display: flex;
    gap: 6px;
}

.warehouse-action {
    width: 35px;
    height: 35px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--wh-border);
    border-radius: 8px;
    background: var(--wh-input);
    color: var(--wh-text-body) !important;
    text-decoration: none;
    cursor: pointer;
}

.warehouse-action:hover {
    border-color: var(--wh-green);
    background: var(--wh-green-soft);
    color: var(--wh-green) !important;
}

.warehouse-action-delete:hover {
    border-color: var(--wh-red);
    background: var(--wh-red-soft);
    color: var(--wh-red) !important;
}

.warehouse-empty, .warehouse-no-result {
    display: none;
    min-height: 250px;
    padding: 50px 20px;
    text-align: center;
}

.warehouse-no-result.show {
    display: block;
}

.modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 99999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: var(--wh-modal-overlay);
}

.modal-backdrop.show {
    display: flex;
}

.warehouse-modal {
    width: min(700px, 100%);
    max-height: 90vh;
    overflow: auto;
    background: var(--wh-card);
    border: 1px solid var(--wh-border);
    border-radius: 14px;
}

.modal-header, .modal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 18px;
    border-bottom: 1px solid var(--wh-border);
}

.modal-footer {
    border-top: 1px solid var(--wh-border);
    border-bottom: none;
    justify-content: flex-end;
}

.modal-body {
    padding: 20px;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 17px;
}

.detail-full {
    grid-column: 1 / -1;
}
</style>

<main class="main">
    <section class="content">
        <div class="warehouse-page">

            <div class="warehouse-page-header">
                <div>
                    <div class="warehouse-breadcrumb">
                        <span>Inventory</span>
                        <span>/</span>
                        <span class="current">Warehouses</span>
                    </div>
                    <h1>Warehouses Management</h1>
                    <p>Manage warehouse locations, fulfillment centers, inventory hubs and warehouse status.</p>
                </div>
                <div class="header-actions">
                    <button type="button" class="btn btn-blue" id="printBtn">🖨 Print <small>P</small></button>
                    <button type="button" class="btn" id="pdfBtn">↓ PDF <small>V</small></button>
                    <button type="button" class="btn" id="excelBtn">↓ Excel <small>X</small></button>
                    <a href="add.php" class="btn btn-primary" id="addWarehouseBtn">＋ Add Warehouse <small>A</small></a>
                </div>
            </div>

            <?php if ($actionMessage !== ''): ?>
                <div class="notice notice-success"><span>✓</span><span><?= warehouse_index_escape($actionMessage) ?></span></div>
            <?php endif; ?>

            <?php if ($actionError !== '' || $queryError !== ''): ?>
                <div class="notice notice-error"><span>!</span><span><?= warehouse_index_escape($actionError !== '' ? $actionError : $queryError) ?></span></div>
            <?php endif; ?>

            <div class="warehouse-stats">
                <div class="warehouse-stat-item">
                    <div class="warehouse-stat-icon">#</div>
                    <div>
                        <div class="warehouse-stat-label">Total Warehouses</div>
                        <div class="warehouse-stat-value"><?= number_format($totalWarehouses) ?></div>
                    </div>
                </div>
                <div class="warehouse-stat-item">
                    <div class="warehouse-stat-icon">✓</div>
                    <div>
                        <div class="warehouse-stat-label">Active Warehouses</div>
                        <div class="warehouse-stat-value"><?= number_format($activeWarehouses) ?></div>
                    </div>
                </div>
                <div class="warehouse-stat-item">
                    <div class="warehouse-stat-icon">○</div>
                    <div>
                        <div class="warehouse-stat-label">Inactive Warehouses</div>
                        <div class="warehouse-stat-value"><?= number_format($inactiveWarehouses) ?></div>
                    </div>
                </div>
                <div class="warehouse-stat-item">
                    <div class="warehouse-stat-icon">★</div>
                    <div>
                        <div class="warehouse-stat-label">Primary Hubs</div>
                        <div class="warehouse-stat-value"><?= number_format($primaryWarehouses) ?></div>
                    </div>
                </div>
            </div>

            <div class="warehouse-content">
                <div class="warehouse-content-header">
                    <div class="warehouse-content-title">
                        <h2>Warehouse List</h2>
                        <p>View and manage all GatewayLinen warehouse locations.</p>
                    </div>
                    <div class="warehouse-filters">
                        <div class="warehouse-search-wrap">
                            <span class="warehouse-search-icon">⌕</span>
                            <input type="search" id="warehouseSearch" class="warehouse-search" placeholder="Search warehouse, code, city..." autocomplete="off">
                        </div>
                        <select id="warehouseStatusFilter" class="warehouse-status-filter">
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="export-bar">
                    <span class="export-label">Reports & Export</span>
                    <button type="button" class="btn" id="printBtn2">🖨 Print</button>
                    <button type="button" class="btn" id="pdfBtn2">↓ PDF</button>
                    <button type="button" class="btn" id="excelBtn2">↓ Excel</button>
                </div>

                <div class="warehouse-table-summary">
                    <div class="warehouse-result-text">Showing <strong id="visibleWarehouseCount"><?= number_format($totalWarehouses) ?></strong> warehouses</div>
                    <div class="warehouse-result-text">Total: <strong><?= number_format($totalWarehouses) ?></strong></div>
                </div>

                <div class="warehouse-table-wrapper">
                    <?php if (empty($warehousesList)): ?>
                        <div class="warehouse-empty" style="display: flex;">
                            <div class="warehouse-empty-icon">◈</div>
                            <h3>No Warehouses Found</h3>
                            <p>Create your first warehouse location to start managing inventory.</p>
                            <a href="add.php" class="btn btn-primary" style="margin-top:16px;">＋ Add First Warehouse</a>
                        </div>
                    <?php else: ?>
                        <table class="warehouse-table" id="warehouseTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Warehouse</th>
                                    <th>Address / Location</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $orderNo = 1;
                            foreach ($warehousesList as $warehouse):
                                $warehouseId = (int)($warehouse['WarehouseId'] ?? 0);
                                $warehouseCode = trim((string)($warehouse['WarehouseCode'] ?? ''));
                                $warehouseName = trim((string)($warehouse['WarehouseName'] ?? ''));
                                $address = trim((string)($warehouse['Address'] ?? ''));
                                $city = trim((string)($warehouse['City'] ?? ''));
                                $state = trim((string)($warehouse['StateProvince'] ?? ''));
                                $postal = trim((string)($warehouse['PostalCode'] ?? ''));
                                $isPrimary = ((int)($warehouse['IsPrimary'] ?? 0) === 1);
                                $isActive = ((int)($warehouse['IsActive'] ?? 0) === 1);
                                $currentOrder = $orderNo++;

                                $addressParts = [];
                                if ($address !== '') { $addressParts[] = $address; }
                                if ($city !== '') { $addressParts[] = $city; }
                                if ($state !== '') { $addressParts[] = $state; }
                                if ($postal !== '') { $addressParts[] = $postal; }
                                $fullAddress = implode(', ', $addressParts);

                                $searchData = strtolower(implode(' ', [$warehouseName, $warehouseCode, $address, $city, $state, $postal]));
                            ?>
                                <tr class="warehouse-row"
                                    data-id="<?= $warehouseId ?>"
                                    data-order="<?= $currentOrder ?>"
                                    data-status="<?= $isActive ? 'active' : 'inactive' ?>"
                                    data-search="<?= warehouse_index_escape($searchData) ?>"
                                    data-code="<?= warehouse_index_escape($warehouseCode) ?>"
                                    data-name="<?= warehouse_index_escape($warehouseName) ?>">
                                    <td><span class="order-box"><?= $currentOrder ?></span></td>
                                    <td>
                                        <div class="warehouse-name">
                                            <?= warehouse_index_escape($warehouseName !== '' ? $warehouseName : 'Unnamed Warehouse') ?>
                                            <?php if ($isPrimary): ?><span class="primary-badge">Primary</span><?php endif; ?>
                                        </div>
                                        <?php if ($warehouseCode !== ''): ?>
                                            <div class="warehouse-code">CODE: <?= warehouse_index_escape($warehouseCode) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="warehouse-address"><?= $fullAddress !== '' ? warehouse_index_escape($fullAddress) : '—' ?></div>
                                    </td>
                                    <td>
                                        <?php if ($isActive): ?>
                                            <span class="warehouse-status warehouse-status-active"><span class="warehouse-status-dot"></span>Active</span>
                                        <?php else: ?>
                                            <span class="warehouse-status warehouse-status-inactive"><span class="warehouse-status-dot"></span>Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="warehouse-actions">
                                            <button type="button" class="warehouse-action detail-btn" title="View Warehouse" data-id="<?= $warehouseId ?>" data-name="<?= warehouse_index_escape($warehouseName) ?>" data-code="<?= warehouse_index_escape($warehouseCode) ?>" data-address="<?= warehouse_index_escape($fullAddress) ?>" data-status="<?= $isActive ? 'Active' : 'Inactive' ?>" data-primary="<?= $isPrimary ? 'Yes' : 'No' ?>" data-order="<?= $currentOrder ?>">◉</button>
                                            <a href="edit.php?id=<?= $warehouseId ?>" class="warehouse-action edit-btn" title="Edit Warehouse">✎</a>
                                            <form method="POST" action="delete.php" class="delete-form">
                                                <input type="hidden" name="warehouse_id" value="<?= $warehouseId ?>">
                                                <input type="hidden" name="csrf_token" value="<?= warehouse_index_escape($csrfToken) ?>">
                                                <button type="submit" class="warehouse-action warehouse-action-delete delete-warehouse-btn" title="Delete Warehouse">×</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div id="warehouseNoResult" class="warehouse-no-result">
                            <div class="warehouse-no-result-icon">⌕</div>
                            <h3>No Matching Warehouses</h3>
                            <p>Try changing your search or status filter.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </section>
</main>

<div class="modal-backdrop" id="warehouseModal" aria-hidden="true">
    <div class="warehouse-modal" role="dialog" aria-modal="true">
        <div class="modal-header">
            <h3>Warehouse Details</h3>
            <button type="button" class="modal-close" id="modalCloseBtn">×</button>
        </div>
        <div class="modal-body">
            <div class="detail-grid">
                <div class="detail-item"><label>Warehouse Name</label><div id="detailName">—</div></div>
                <div class="detail-item"><label>Warehouse Code</label><div id="detailCode">—</div></div>
                <div class="detail-item"><label>Status</label><div id="detailStatus">—</div></div>
                <div class="detail-item"><label>Primary Hub</label><div id="detailPrimary">—</div></div>
                <div class="detail-item"><label>Warehouse Number</label><div id="detailOrder">—</div></div>
                <div class="detail-item detail-full"><label>Address / Location</label><div id="detailAddress">—</div></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn" id="modalCloseBtn2">Close</button>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.4/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('warehouseSearch');
        const statusFilter = document.getElementById('warehouseStatusFilter');
        const table = document.getElementById('warehouseTable');
        const countElement = document.getElementById('visibleWarehouseCount');
        const noResult = document.getElementById('warehouseNoResult');
        const modal = document.getElementById('warehouseModal');

        function getRows() {
            if (!table) return [];
            return Array.from(table.querySelectorAll('tbody .warehouse-row'));
        }

        function getVisibleRows() {
            return getRows().filter(row => row.style.display !== 'none');
        }

        function filterWarehouses() {
            const rows = getRows();
            const query = (searchInput?.value || '').trim().toLowerCase();
            const status = statusFilter?.value || 'all';
            let visible = 0;

            rows.forEach(row => {
                const text = (row.dataset.search || '').toLowerCase();
                const rowStatus = row.dataset.status || 'active';
                const searchMatch = query === '' || text.includes(query);
                const statusMatch = status === 'all' || rowStatus === status;
                const shouldShow = searchMatch && statusMatch;

                row.style.display = shouldShow ? '' : 'none';
                if (shouldShow) visible++;
            });

            if (countElement) countElement.textContent = visible.toLocaleString();
            if (noResult) noResult.classList.toggle('show', rows.length > 0 && visible === 0);
        }

        if (searchInput) searchInput.addEventListener('input', filterWarehouses);
        if (statusFilter) statusFilter.addEventListener('change', filterWarehouses);

        function openModal(button) {
            if (!modal) return;
            document.getElementById('detailName').textContent = button.dataset.name || '—';
            document.getElementById('detailCode').textContent = button.dataset.code || '—';
            document.getElementById('detailStatus').textContent = button.dataset.status || '—';
            document.getElementById('detailPrimary').textContent = button.dataset.primary || '—';
            document.getElementById('detailOrder').textContent = button.dataset.order || '—';
            document.getElementById('detailAddress').textContent = button.dataset.address || '—';
            modal.classList.add('show');
        }

        function closeModal() {
            if (!modal) return;
            modal.classList.remove('show');
        }

        document.querySelectorAll('.detail-btn').forEach(button => {
            button.addEventListener('click', function (event) {
                event.stopPropagation();
                openModal(this);
            });
        });

        document.getElementById('modalCloseBtn')?.addEventListener('click', closeModal);
        document.getElementById('modalCloseBtn2')?.addEventListener('click', closeModal);
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>