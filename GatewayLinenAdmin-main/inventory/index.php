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

$activeMenu = 'inventory';
$pageTitle  = 'GatewayLinen | Inventory Management';

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] = $_SESSION['admin_username'] ?? 'GatewayLinen Administrator';
}

if (!isset($_SESSION['admin_role'])) {
    $_SESSION['admin_role'] = 'Administrator';
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
| HELPERS
|--------------------------------------------------------------------------
*/

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function dateValue($value, $format = 'd/m/Y, h:i A'): string
{
    if ($value instanceof DateTimeInterface) {
        return $value->format($format);
    }
    if (!empty($value)) {
        try {
            $dt = new DateTime((string)$value);
            return $dt->format($format);
        } catch (Exception $ex) {
            return trim((string)$value);
        }
    }
    return '—';
}

/*
|--------------------------------------------------------------------------
| FETCH WAREHOUSES FOR FILTER DROPDOWN
|--------------------------------------------------------------------------
*/

$warehousesList = [];
$wSql = "SELECT WarehouseId, Name FROM dbo.Warehouses ORDER BY Name ASC";
$wStmt = sqlsrv_query($conn, $wSql);
if ($wStmt !== false) {
    while ($wRow = sqlsrv_fetch_array($wStmt, SQLSRV_FETCH_ASSOC)) {
        $warehousesList[] = $wRow;
    }
    sqlsrv_free_stmt($wStmt);
}

/*
|--------------------------------------------------------------------------
| FETCH INVENTORY RECORDS WITH PRODUCT & VARIANT DETAILS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT 
        i.InventoryId,
        i.WarehouseId,
        i.VariantId,
        i.StockQty,
        i.ReservedQty,
        i.BinLocation,
        i.LastRestockedAt,
        w.Name AS WarehouseName,
        pv.Sku,
        pv.Color,
        pv.Size,
        p.Name AS ProductName,
        p.ProductId,
        img.ImageUrl AS ProductImage
    FROM dbo.Inventory i
    LEFT JOIN dbo.Warehouses w ON i.WarehouseId = w.WarehouseId
    LEFT JOIN dbo.ProductVariants pv ON i.VariantId = pv.VariantId
    LEFT JOIN dbo.Products p ON pv.ProductId = p.ProductId
    OUTER APPLY (
        SELECT TOP 1 ImageUrl 
        FROM dbo.ProductImages 
        WHERE ProductId = p.ProductId 
        ORDER BY IsMain DESC, DisplayOrder ASC
    ) img
    ORDER BY i.InventoryId DESC
";

$stmt = sqlsrv_query($conn, $sql);
$allInventory = [];
$queryError = '';

if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $allInventory[] = $row;
    }
    sqlsrv_free_stmt($stmt);
} else {
    $queryError = 'Unable to load inventory data from database.';
}

/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$totalItems       = count($allInventory);
$totalStockUnits  = 0;
$totalReserved    = 0;
$lowStockCount    = 0;
$outOfStockCount  = 0;

foreach ($allInventory as $inv) {
    $stock    = (int)($inv['StockQty'] ?? 0);
    $reserved = (int)($inv['ReservedQty'] ?? 0);
    $avail    = $stock - $reserved;

    $totalStockUnits += $stock;
    $totalReserved   += $reserved;

    if ($avail <= 0) {
        $outOfStockCount++;
    } elseif ($avail <= 10) {
        $lowStockCount++;
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

        --red: #ef4444;
        --red-soft: rgba(239, 68, 68, .12);

        --blue: #38bdf8;
        --blue-soft: rgba(56, 189, 248, .15);

        --amber: #f59e0b;
        --amber-soft: rgba(245, 158, 11, .15);

        --purple: #a855f7;
        --purple-soft: rgba(168, 85, 247, .15);

        --radius: 10px;
    }

    html, body, .main, .content {
        background: var(--bg-page) !important;
        color: var(--text-body) !important;
    }

    .inventory-page {
        width: 100%;
        max-width: 1600px;
        margin: 0 auto;
        padding: 0;
    }

    .inventory-page * { box-sizing: border-box; }

    /* HEADER */
    .page-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 24px;
        margin-bottom: 20px;
        padding-bottom: 18px;
        border-bottom: 1px solid var(--border);
    }

    .breadcrumb {
        display: flex;
        gap: 8px;
        margin-bottom: 8px;
        color: var(--text-mute);
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .3px;
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
        justify-content: flex-end;
    }

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

    .btn-amber {
        color: var(--amber) !important;
        border-color: rgba(245, 158, 11, .3);
    }
    .btn-amber:hover {
        border-color: var(--amber);
        background: var(--amber-soft);
        color: var(--amber) !important;
    }

    .btn-blue { color: var(--blue) !important; }

    /* STATS */
    .inventory-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }

    .stat-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px 17px;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
    }

    .stat-icon {
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

    .stat-label {
        color: var(--text-mute);
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .stat-value {
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

    /* CONTENT BOX */
    .inventory-content {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
    }

    .content-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 17px 20px;
        border-bottom: 1px solid var(--border);
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

    .inventory-filters {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .search-wrap {
        position: relative;
        width: 270px;
    }

    .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-mute);
        pointer-events: none;
    }

    .search-input, .select-filter {
        height: 36px;
        border: 1px solid var(--border);
        border-radius: 8px;
        outline: none;
        background: var(--bg-input);
        color: var(--text-hi);
        font-size: 12px;
    }

    .search-input { width: 100%; padding: 0 12px 0 34px; }
    .select-filter { min-width: 140px; padding: 0 10px; }

    .search-input:focus, .select-filter:focus {
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

    .inv-table {
        width: 100%;
        min-width: 1250px;
        border-collapse: collapse;
    }

    .inv-table th {
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

    .inv-table td {
        padding: 12px 16px;
        border-bottom: 1px solid var(--border-soft);
        color: var(--text-body);
        font-size: 12px;
        vertical-align: middle;
    }

    .inv-table tbody tr:hover { background: var(--bg-hover); }

    .order-box {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 44px;
        height: 28px;
        padding: 0 8px;
        border-radius: 7px;
        background: var(--bg-input);
        border: 1px solid var(--border);
        color: var(--text-mute);
        font-size: 11px;
        font-weight: 800;
    }

    .product-meta-cell {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .product-thumb {
        width: 46px;
        height: 46px;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid var(--border);
        background: #0d1620;
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 46px;
    }

    .product-thumb img { width: 100%; height: 100%; object-fit: cover; }

    .product-title {
        color: var(--text-hi);
        font-weight: 700;
        font-size: 13px;
    }

    .product-sku {
        font-family: monospace;
        color: var(--text-mute);
        font-size: 10px;
        margin-top: 2px;
    }

    .badge-tag {
        font-size: 9px;
        font-weight: 800;
        padding: 2px 7px;
        border-radius: 4px;
        text-transform: uppercase;
        display: inline-block;
    }

    .tag-blue { background: var(--blue-soft); color: var(--blue); }
    .tag-purple { background: var(--purple-soft); color: var(--purple); }
    .tag-rack {
        font-family: monospace;
        background: #080e15;
        border: 1px solid var(--border);
        color: var(--green);
        padding: 2px 6px;
        border-radius: 4px;
        font-weight: 700;
    }

    .stock-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-height: 24px;
        padding: 0 9px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 700;
    }

    .pill-green { background: var(--green-soft); color: var(--green); }
    .pill-amber { background: var(--amber-soft); color: var(--amber); }
    .pill-red { background: var(--red-soft); color: #f87171; }

    .table-actions {
        display: flex;
        justify-content: flex-end;
        gap: 6px;
    }

    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border: 1px solid var(--border);
        border-radius: 7px;
        background: var(--bg-input);
        color: var(--text-body) !important;
        text-decoration: none;
        cursor: pointer;
    }

    .action-btn:hover {
        border-color: var(--green);
        background: var(--green-soft);
        color: var(--green) !important;
    }

    /* SHORTCUTS HELP */
    .shortcut-help-box {
        margin-top: 16px;
        padding: 16px 20px;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: var(--bg-card);
    }
    .shortcut-help-box.hidden { display: none; }
    .shortcut-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
        margin-top: 10px;
    }
    .shortcut-item {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 8px 10px;
        border: 1px solid var(--border-soft);
        border-radius: 8px;
        background: var(--bg-input);
    }
    .shortcut-key {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        height: 25px;
        border-radius: 5px;
        background: #0a1119;
        border: 1px solid var(--border);
        color: var(--green);
        font: 800 10px monospace;
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
    .inv-modal {
        width: min(700px, 100%);
        max-height: 90vh;
        overflow: auto;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 14px;
        box-shadow: 0 24px 80px rgba(0, 0, 0, .5);
    }
    .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 15px 18px;
        border-bottom: 1px solid var(--border);
    }
    .modal-header h3 { margin: 0; color: var(--text-hi); font-size: 15px; }
    .modal-close { border: 0; background: transparent; color: var(--text-mute); font-size: 22px; cursor: pointer; }
    .modal-body { padding: 20px; }
    .modal-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }
    .modal-card {
        padding: 12px;
        border: 1px solid var(--border-soft);
        border-radius: 8px;
        background: var(--bg-input);
    }
    .modal-card label {
        display: block;
        margin-bottom: 4px;
        color: var(--text-mute);
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
    }
    .modal-card div { color: var(--text-hi); font-size: 12px; }
    .modal-full { grid-column: 1 / -1; }
    .modal-footer {
        display: flex;
        justify-content: flex-end;
        padding: 14px 18px;
        border-top: 1px solid var(--border);
    }

    @media (max-width: 1000px) {
        .inventory-stats { grid-template-columns: repeat(2, 1fr); }
        .content-header { flex-direction: column; align-items: stretch; }
        .search-wrap { width: 100%; }
        .shortcut-grid { grid-template-columns: repeat(2, 1fr); }
    }
</style>

<main class="main">
    <section class="content">
        <div class="inventory-page">

            <!-- PAGE HEADER -->
            <div class="page-header">
                <div>
                    <div class="breadcrumb">
                        <span>Operations</span>
                        <span>/</span>
                        <span class="current">Inventory</span>
                    </div>
                    <h1>Inventory & Stock Control</h1>
                    <p>Track warehouse inventory quantities, reserved orders, bin rack locations and stock movements.</p>
                </div>

                <div class="header-actions">
                    <button type="button" class="btn btn-blue" id="printBtn">🖨 Print <small>P</small></button>
                    <button type="button" class="btn" id="pdfBtn">↓ PDF <small>V</small></button>
                    <button type="button" class="btn" id="excelBtn">↓ Excel <small>X</small></button>
                    <a href="stock-in.php" class="btn btn-primary" id="stockInBtn">＋ Stock In <small>A</small></a>
                    <a href="stock-out.php" class="btn btn-amber" id="stockOutBtn">− Stock Out</a>
                </div>
            </div>

            <!-- SUCCESS/ERROR MESSAGES -->
            <?php if ($actionMessage !== ''): ?>
                <div class="notice notice-success"><?= e($actionMessage) ?></div>
            <?php endif; ?>
            <?php if ($actionError !== ''): ?>
                <div class="notice notice-error"><?= e($actionError) ?></div>
            <?php endif; ?>
            <?php if ($queryError !== ''): ?>
                <div class="notice notice-error"><?= e($queryError) ?></div>
            <?php endif; ?>

            <!-- STATS COUNTERS -->
            <div class="inventory-stats">
                <div class="stat-item">
                    <div class="stat-icon">📦</div>
                    <div>
                        <div class="stat-label">Total Units on Hand</div>
                        <div class="stat-value"><?= number_format($totalStockUnits) ?></div>
                    </div>
                </div>

                <div class="stat-item">
                    <div class="stat-icon" style="background:var(--blue-soft); color:var(--blue);">🔒</div>
                    <div>
                        <div class="stat-label">Reserved (Orders)</div>
                        <div class="stat-value"><?= number_format($totalReserved) ?></div>
                    </div>
                </div>

                <div class="stat-item">
                    <div class="stat-icon" style="background:var(--amber-soft); color:var(--amber);">⚠️</div>
                    <div>
                        <div class="stat-label">Low Stock (≤ 10)</div>
                        <div class="stat-value"><?= $lowStockCount ?></div>
                    </div>
                </div>

                <div class="stat-item">
                    <div class="stat-icon" style="background:var(--red-soft); color:var(--red);">⛔</div>
                    <div>
                        <div class="stat-label">Out of Stock</div>
                        <div class="stat-value"><?= $outOfStockCount ?></div>
                    </div>
                </div>
            </div>

            <!-- INVENTORY CONTENT -->
            <div class="inventory-content">
                <div class="content-header">
                    <div class="content-title">
                        <h2>Stock Roster</h2>
                        <p>Real-time physical stock counts, reservations, warehouse allocation and rack details.</p>
                    </div>

                    <div class="inventory-filters">
                        <!-- REAL-TIME SEARCH -->
                        <div class="search-wrap">
                            <span class="search-icon">⌕</span>
                            <input
                                type="search"
                                id="invSearch"
                                class="search-input"
                                placeholder="Search product, SKU, bin rack..."
                                autocomplete="off"
                            >
                        </div>

                        <!-- WAREHOUSE FILTER -->
                        <select id="warehouseFilter" class="select-filter">
                            <option value="all">All Warehouses</option>
                            <?php foreach ($warehousesList as $wh): ?>
                                <option value="<?= e(strtolower($wh['Name'])) ?>"><?= e($wh['Name']) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <!-- STOCK LEVEL FILTER -->
                        <select id="stockLevelFilter" class="select-filter">
                            <option value="all">All Stock Levels</option>
                            <option value="in_stock">In Stock (> 10)</option>
                            <option value="low_stock">Low Stock (1-10)</option>
                            <option value="out_of_stock">Out of Stock (0)</option>
                        </select>
                    </div>
                </div>

                <!-- EXPORT BAR -->
                <div class="export-bar">
                    <span class="export-label">Reports & Downloads</span>
                    <button type="button" class="btn" id="printBtn2">🖨 Print</button>
                    <button type="button" class="btn" id="pdfBtn2">↓ PDF</button>
                    <button type="button" class="btn" id="excelBtn2">↓ Excel</button>
                    <a href="low-stock.php" class="btn btn-amber">⚠️ View Low Stock Report</a>
                </div>

                <!-- TABLE SUMMARY -->
                <div class="table-summary">
                    <div>Showing <strong id="visibleCount"><?= $totalItems ?></strong> inventory line items</div>
                    <div>Total Records: <strong><?= $totalItems ?></strong></div>
                </div>

                <!-- TABLE -->
                <div class="table-wrapper">
                    <?php if (empty($allInventory)): ?>
                        <div style="padding:65px 20px; text-align:center; color:var(--text-mute);">
                            <div style="font-size:32px; margin-bottom:10px;">📦</div>
                            <h3 style="color:var(--text-hi); margin:0 0 6px 0;">No Inventory Records Found</h3>
                            <p style="font-size:12px; margin:0;">Receive stock or map products to warehouse locations.</p>
                            <a href="stock-in.php" class="btn btn-primary" style="margin-top:16px;">＋ Receive Initial Stock</a>
                        </div>
                    <?php else: ?>
                        <table class="inv-table" id="invTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Product / Sku</th>
                                    <th>Warehouse</th>
                                    <th>Bin / Rack</th>
                                    <th>Physical Stock</th>
                                    <th>Reserved</th>
                                    <th>Available</th>
                                    <th>Status</th>
                                    <th>Last Restocked</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $serialNo = 1;
                                foreach ($allInventory as $inv): 
                                    $invId      = (int)($inv['InventoryId'] ?? 0);
                                    $pName      = (string)($inv['ProductName'] ?? 'Unknown Product');
                                    $sku        = (string)($inv['Sku'] ?? 'NO-SKU');
                                    $color      = (string)($inv['Color'] ?? '');
                                    $size       = (string)($inv['Size'] ?? '');
                                    $wName      = (string)($inv['WarehouseName'] ?? 'Main Warehouse');
                                    $bin        = (string)($inv['BinLocation'] ?? '—');
                                    $stock      = (int)($inv['StockQty'] ?? 0);
                                    $reserved   = (int)($inv['ReservedQty'] ?? 0);
                                    $avail      = $stock - $reserved;
                                    $restocked  = dateValue($inv['LastRestockedAt'] ?? null);
                                    $imgUrl     = !empty($inv['ProductImage']) ? '../uploads/products/' . rawurlencode(basename($inv['ProductImage'])) : '';

                                    // Status Badge Determination
                                    if ($avail <= 0) {
                                        $statusClass = 'pill-red';
                                        $statusText  = 'Out of Stock';
                                        $filterLevel = 'out_of_stock';
                                    } elseif ($avail <= 10) {
                                        $statusClass = 'pill-amber';
                                        $statusText  = 'Low Stock';
                                        $filterLevel = 'low_stock';
                                    } else {
                                        $statusClass = 'pill-green';
                                        $statusText  = 'In Stock';
                                        $filterLevel = 'in_stock';
                                    }
                                ?>
                                    <tr 
                                        class="inv-row" 
                                        data-id="<?= $invId ?>"
                                        data-product="<?= e(strtolower($pName)) ?>"
                                        data-sku="<?= e(strtolower($sku)) ?>"
                                        data-warehouse="<?= e(strtolower($wName)) ?>"
                                        data-bin="<?= e(strtolower($bin)) ?>"
                                        data-level="<?= $filterLevel ?>"
                                    >
                                        <!-- SERIAL NO -->
                                        <td><span class="order-box">#<?= $serialNo++ ?></span></td>

                                        <!-- PRODUCT & SKU -->
                                        <td>
                                            <div class="product-meta-cell">
                                                <div class="product-thumb">
                                                    <?php if ($imgUrl !== ''): ?>
                                                        <img src="<?= e($imgUrl) ?>" alt="Product" onerror="this.style.display='none';">
                                                    <?php else: ?>
                                                        <span style="font-size:16px;">📦</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <div class="product-title"><?= e($pName) ?></div>
                                                    <div class="product-sku">
                                                        SKU: <?= e($sku) ?>
                                                        <?php if ($color !== ''): ?><span class="badge-tag tag-blue" style="margin-left:4px;"><?= e($color) ?></span><?php endif; ?>
                                                        <?php if ($size !== ''): ?><span class="badge-tag tag-purple" style="margin-left:4px;"><?= e($size) ?></span><?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- WAREHOUSE -->
                                        <td><span style="font-weight:700; color:var(--text-hi);"><?= e($wName) ?></span></td>

                                        <!-- BIN LOCATION -->
                                        <td><span class="tag-rack"><?= e($bin !== '' ? $bin : 'UNASSIGNED') ?></span></td>

                                        <!-- PHYSICAL STOCK -->
                                        <td><strong style="color:var(--text-hi); font-size:13px;"><?= number_format($stock) ?></strong></td>

                                        <!-- RESERVED -->
                                        <td><span style="color:var(--blue); font-weight:700;"><?= number_format($reserved) ?></span></td>

                                        <!-- AVAILABLE -->
                                        <td><strong style="color:<?= $avail > 0 ? 'var(--green)' : '#f87171' ?>; font-size:13px;"><?= number_format($avail) ?></strong></td>

                                        <!-- STATUS PILL -->
                                        <td>
                                            <span class="stock-status-pill <?= $statusClass ?>">
                                                <span>●</span> <?= $statusText ?>
                                            </span>
                                        </td>

                                        <!-- LAST RESTOCKED -->
                                        <td><span style="font-size:11px; color:var(--text-mute);"><?= $restocked ?></span></td>

                                        <!-- ACTIONS -->
                                        <td style="text-align:right;">
                                            <div class="table-actions">
                                                <button
                                                    type="button"
                                                    class="action-btn detail-btn"
                                                    title="View Details"
                                                    data-product="<?= e($pName) ?>"
                                                    data-sku="<?= e($sku) ?>"
                                                    data-warehouse="<?= e($wName) ?>"
                                                    data-bin="<?= e($bin) ?>"
                                                    data-stock="<?= $stock ?>"
                                                    data-reserved="<?= $reserved ?>"
                                                    data-avail="<?= $avail ?>"
                                                    data-status="<?= $statusText ?>"
                                                    data-restocked="<?= $restocked ?>"
                                                >
                                                    ◉
                                                </button>
                                                <a href="stock-in.php?id=<?= $invId ?>" class="action-btn" title="Add Stock (Stock-In)">＋</a>
                                                <a href="stock-out.php?id=<?= $invId ?>" class="action-btn" title="Reduce Stock (Stock-Out)">−</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div id="noResult" style="display:none; padding:55px 20px; text-align:center; color:var(--text-mute);">
                            <div style="font-size:26px; margin-bottom:8px;">⌕</div>
                            <h3 style="color:var(--text-hi); margin:0;">No matching inventory line items</h3>
                            <p style="font-size:12px; margin-top:5px;">Try adjusting your search terms or warehouse filters.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- KEYBOARD SHORTCUTS HELP BOX -->
            <div class="shortcut-help-box" id="shortcutBox">
                <div style="display:flex; align-items:center; gap:8px; color:var(--text-hi); font-size:12px; font-weight:800;">
                    <span>⌨</span><span>Keyboard Shortcuts</span>
                    <small style="margin-left:auto; color:var(--text-mute);">Press H to toggle</small>
                </div>
                <div class="shortcut-grid">
                    <div class="shortcut-item"><span class="shortcut-key">A</span><span style="font-size:11px;">Stock In (Receive Maal)</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">B</span><span style="font-size:11px;">Focus Search Box</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">C</span><span style="font-size:11px;">Filter Warehouses</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">P</span><span style="font-size:11px;">Print Roster</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">V</span><span style="font-size:11px;">Download PDF</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">X</span><span style="font-size:11px;">Download Excel (.xlsx)</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">Esc</span><span style="font-size:11px;">Clear Search / Close Popup</span></div>
                </div>
            </div>

        </div>
    </section>
</main>

<!-- INVENTORY DETAILS MODAL -->
<div class="modal-backdrop" id="invModal" aria-hidden="true">
    <div class="inv-modal" role="dialog" aria-modal="true" aria-labelledby="invModalTitle">
        <div class="modal-header">
            <h3 id="invModalTitle">Inventory Item Details</h3>
            <button type="button" class="modal-close" id="modalClose">×</button>
        </div>
        <div class="modal-body">
            <div class="modal-grid">
                <div class="modal-card modal-full">
                    <label>Product Name</label>
                    <div id="mProduct" style="font-weight:800; font-size:14px; color:var(--text-hi);">—</div>
                </div>
                <div class="modal-card">
                    <label>SKU Code</label>
                    <div id="mSku" style="font-family:monospace; color:var(--green); font-weight:800;">—</div>
                </div>
                <div class="modal-card">
                    <label>Warehouse</label>
                    <div id="mWarehouse">—</div>
                </div>
                <div class="modal-card">
                    <label>Rack / Bin Location</label>
                    <div id="mBin" style="font-family:monospace;">—</div>
                </div>
                <div class="modal-card">
                    <label>Stock Status</label>
                    <div id="mStatus">—</div>
                </div>
                <div class="modal-card">
                    <label>Physical Stock (Total)</label>
                    <div id="mStock" style="font-weight:800; color:var(--text-hi);">—</div>
                </div>
                <div class="modal-card">
                    <label>Reserved (In Orders)</label>
                    <div id="mReserved" style="color:var(--blue); font-weight:800;">—</div>
                </div>
                <div class="modal-card">
                    <label>Available for Sale</label>
                    <div id="mAvail" style="color:var(--green); font-weight:800;">—</div>
                </div>
                <div class="modal-card">
                    <label>Last Restocked Timestamp</label>
                    <div id="mRestocked">—</div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn" id="modalClose2">Close</button>
        </div>
    </div>
</div>

<!-- LIBRARIES FOR EXPORT -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.4/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        const searchInput     = document.getElementById('invSearch');
        const warehouseFilter = document.getElementById('warehouseFilter');
        const stockFilter     = document.getElementById('stockLevelFilter');
        const table           = document.getElementById('invTable');
        const countElement    = document.getElementById('visibleCount');
        const noResult        = document.getElementById('noResult');
        const shortcutBox     = document.getElementById('shortcutBox');
        const modal           = document.getElementById('invModal');

        function rows() {
            return table ? Array.from(table.querySelectorAll('tbody .inv-row')) : [];
        }

        function visibleRows() {
            return rows().filter(r => r.style.display !== 'none');
        }

        /*
        |--------------------------------------------------------------------------
        | INSTANT REAL-TIME FILTER
        |--------------------------------------------------------------------------
        */
        function filterInventory() {
            if (!table) return;

            const q  = (searchInput?.value || '').toLowerCase().trim();
            const wh = (warehouseFilter?.value || 'all');
            const st = (stockFilter?.value || 'all');

            let count = 0;

            rows().forEach(function(row) {
                const prod = row.dataset.product || '';
                const sku  = row.dataset.sku || '';
                const bin  = row.dataset.bin || '';
                const rWh  = row.dataset.warehouse || '';
                const rSt  = row.dataset.level || '';

                const qMatch  = (!q || prod.includes(q) || sku.includes(q) || bin.includes(q));
                const whMatch = (wh === 'all' || rWh === wh);
                const stMatch = (st === 'all' || rSt === st);

                if (qMatch && whMatch && stMatch) {
                    row.style.display = '';
                    count++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (countElement) countElement.textContent = count;
            if (noResult) noResult.style.display = (count === 0) ? 'block' : 'none';
        }

        /*
        |--------------------------------------------------------------------------
        | EXCEL EXPORT
        |--------------------------------------------------------------------------
        */
        function excelExport() {
            const data = visibleRows().map(function(row) {
                return {
                    '#': row.querySelector('.order-box')?.innerText.trim() || '',
                    'Product': row.querySelector('.product-title')?.innerText.trim() || '',
                    'Warehouse': row.dataset.warehouse || '',
                    'Rack/Bin': row.dataset.bin || '',
                    'Physical Stock': row.cells[4]?.innerText.trim() || '0',
                    'Reserved': row.cells[5]?.innerText.trim() || '0',
                    'Available': row.cells[6]?.innerText.trim() || '0',
                    'Status': row.querySelector('.stock-status-pill')?.innerText.trim() || '',
                    'Last Restocked': row.cells[8]?.innerText.trim() || ''
                };
            });

            if (window.XLSX) {
                const ws = XLSX.utils.json_to_sheet(data);
                ws['!cols'] = [{wch: 8}, {wch: 30}, {wch: 18}, {wch: 14}, {wch: 14}, {wch: 12}, {wch: 12}, {wch: 16}, {wch: 20}];
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Inventory');
                XLSX.writeFile(wb, 'inventory-' + new Date().toISOString().slice(0, 10) + '.xlsx');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | PDF EXPORT
        |--------------------------------------------------------------------------
        */
        function pdfExport() {
            if (!window.jspdf || !window.jspdf.jsPDF) {
                alert('PDF library not available. Please print and choose Save as PDF.');
                return;
            }

            const body = visibleRows().map(function(row) {
                return [
                    row.querySelector('.order-box')?.innerText.trim() || '',
                    row.querySelector('.product-title')?.innerText.trim() || '',
                    row.dataset.warehouse || '',
                    row.dataset.bin || '',
                    row.cells[4]?.innerText.trim() || '0',
                    row.cells[5]?.innerText.trim() || '0',
                    row.cells[6]?.innerText.trim() || '0',
                    row.querySelector('.stock-status-pill')?.innerText.trim() || ''
                ];
            });

            const doc = new jspdf.jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
            doc.setFontSize(16);
            doc.text('GatewayLinen - Inventory Stock Report', 14, 14);
            doc.setFontSize(9);
            doc.text('Generated: ' + new Date().toLocaleString(), 14, 20);

            if (typeof doc.autoTable === 'function') {
                doc.autoTable({
                    startY: 25,
                    head: [['#', 'Product', 'Warehouse', 'Bin', 'Stock', 'Reserved', 'Available', 'Status']],
                    body: body,
                    styles: { fontSize: 8, cellPadding: 3 },
                    headStyles: { fontSize: 8 }
                });
            }

            doc.save('inventory-' + new Date().toISOString().slice(0, 10) + '.pdf');
        }

        /*
        |--------------------------------------------------------------------------
        | MODAL DETAILS
        |--------------------------------------------------------------------------
        */
        function openModal(btn) {
            document.getElementById('mProduct').textContent   = btn.dataset.product || '—';
            document.getElementById('mSku').textContent       = btn.dataset.sku || '—';
            document.getElementById('mWarehouse').textContent = btn.dataset.warehouse || '—';
            document.getElementById('mBin').textContent       = btn.dataset.bin || 'Unassigned';
            document.getElementById('mStock').textContent     = btn.dataset.stock || '0';
            document.getElementById('mReserved').textContent  = btn.dataset.reserved || '0';
            document.getElementById('mAvail').textContent     = btn.dataset.avail || '0';
            document.getElementById('mStatus').textContent    = btn.dataset.status || '—';
            document.getElementById('mRestocked').textContent = btn.dataset.restocked || '—';

            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
        }

        function closeModal() {
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
        }

        document.querySelectorAll('.detail-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                openModal(this);
            });
        });

        document.getElementById('modalClose')?.addEventListener('click', closeModal);
        document.getElementById('modalClose2')?.addEventListener('click', closeModal);
        modal?.addEventListener('click', e => { if (e.target === modal) closeModal(); });

        /*
        |--------------------------------------------------------------------------
        | BUTTON BINDINGS
        |--------------------------------------------------------------------------
        */
        document.getElementById('printBtn')?.addEventListener('click', () => window.print());
        document.getElementById('printBtn2')?.addEventListener('click', () => window.print());
        document.getElementById('pdfBtn')?.addEventListener('click', pdfExport);
        document.getElementById('pdfBtn2')?.addEventListener('click', pdfExport);
        document.getElementById('excelBtn')?.addEventListener('click', excelExport);
        document.getElementById('excelBtn2')?.addEventListener('click', excelExport);

        searchInput?.addEventListener('input', filterInventory);
        warehouseFilter?.addEventListener('change', filterInventory);
        stockFilter?.addEventListener('change', filterInventory);

        /*
        |--------------------------------------------------------------------------
        | KEYBOARD SHORTCUTS
        |--------------------------------------------------------------------------
        */
        document.addEventListener('keydown', function(e) {
            const tag = (e.target?.tagName || '').toLowerCase();
            const isTyping = tag === 'input' || tag === 'textarea' || tag === 'select';

            if (isTyping) return;

            const key = (e.key || '').toUpperCase();

            if (key === 'A') {
                e.preventDefault();
                document.getElementById('stockInBtn')?.click();
            } else if (key === 'B') {
                e.preventDefault();
                searchInput?.focus();
                searchInput?.select();
            } else if (key === 'C') {
                e.preventDefault();
                warehouseFilter?.focus();
            } else if (key === 'P') {
                e.preventDefault();
                window.print();
            } else if (key === 'V') {
                e.preventDefault();
                pdfExport();
            } else if (key === 'X') {
                e.preventDefault();
                excelExport();
            } else if (key === 'H') {
                e.preventDefault();
                shortcutBox?.classList.toggle('hidden');
            } else if (key === 'ESCAPE') {
                if (modal?.classList.contains('show')) {
                    closeModal();
                } else if (searchInput?.value) {
                    searchInput.value = '';
                    filterInventory();
                }
                searchInput?.blur();
            }
        });

        filterInventory();
    });
})();
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>