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
$pageTitle  = 'GatewayLinen | Low Stock Alert';

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] = $_SESSION['admin_username'] ?? 'GatewayLinen Administrator';
}

if (!isset($_SESSION['admin_role'])) {
    $_SESSION['admin_role'] = 'Administrator';
}

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
| FETCH LOW STOCK INVENTORY RECORDS (Available Stock <= 10)
|--------------------------------------------------------------------------
*/

$threshold = isset($_GET['threshold']) ? (int)$_GET['threshold'] : 10;
if ($threshold < 0) $threshold = 10;

$sql = "
    SELECT 
        i.InventoryId,
        i.WarehouseId,
        i.VariantId,
        i.StockQty,
        i.ReservedQty,
        i.BinLocation,
        i.LastRestockedAt,
        (i.StockQty - i.ReservedQty) AS AvailableQty,
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
    WHERE (i.StockQty - i.ReservedQty) <= ?
    ORDER BY (i.StockQty - i.ReservedQty) ASC, i.StockQty ASC
";

$stmt = sqlsrv_query($conn, $sql, [$threshold]);
$lowStockItems = [];
$queryError = '';

if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $lowStockItems[] = $row;
    }
    sqlsrv_free_stmt($stmt);
} else {
    $queryError = 'Unable to load low stock inventory report.';
}

/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$totalCritical = count($lowStockItems);
$outOfStockCount = 0;
$urgentLowCount  = 0;

foreach ($lowStockItems as $item) {
    $avail = (int)($item['AvailableQty'] ?? 0);
    if ($avail <= 0) {
        $outOfStockCount++;
    } else {
        $urgentLowCount++;
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

        --radius: 10px;
    }

    html, body, .main, .content {
        background: var(--bg-page) !important;
        color: var(--text-body) !important;
    }

    .low-stock-page {
        width: 100%;
        max-width: 1600px;
        margin: 0 auto;
        padding: 0 0 35px;
    }

    .low-stock-page * { box-sizing: border-box; }

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

    .breadcrumb .current { color: var(--amber); }

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
        border-color: var(--amber);
        background: var(--amber-soft);
        color: var(--amber) !important;
    }

    .btn-primary {
        border-color: transparent;
        background: linear-gradient(135deg, #059669, #10b981);
        color: #fff !important;
        box-shadow: 0 6px 16px rgba(16, 185, 129, .2);
    }

    .btn-blue { color: var(--blue) !important; }

    /* STATS CARDS */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }

    .stat-card {
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
        width: 40px;
        height: 40px;
        border-radius: 9px;
        font-size: 18px;
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

    /* CONTENT BOX */
    .content-box {
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

    .content-title h2 { margin: 0; color: var(--text-hi); font-size: 16px; }
    .content-title p { margin: 4px 0 0; color: var(--text-mute); font-size: 11px; }

    .filters-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .search-wrap {
        position: relative;
        width: 260px;
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
        border-color: var(--amber);
        box-shadow: 0 0 0 3px rgba(245, 158, 11, .1);
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

    .low-table {
        width: 100%;
        min-width: 1250px;
        border-collapse: collapse;
    }

    .low-table th {
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

    .low-table td {
        padding: 12px 16px;
        border-bottom: 1px solid var(--border-soft);
        color: var(--text-body);
        font-size: 12px;
        vertical-align: middle;
    }

    .low-table tbody tr:hover { background: var(--bg-hover); }

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

    .tag-rack {
        font-family: monospace;
        background: #080e15;
        border: 1px solid var(--border);
        color: var(--green);
        padding: 2px 6px;
        border-radius: 4px;
        font-weight: 700;
    }

    .stock-badge-danger {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 20px;
        background: var(--red-soft);
        color: #f87171;
        font-weight: 800;
        font-size: 11px;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .stock-badge-warning {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 20px;
        background: var(--amber-soft);
        color: var(--amber);
        font-weight: 800;
        font-size: 11px;
        border: 1px solid rgba(245, 158, 11, 0.3);
    }

    .btn-restock-action {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: linear-gradient(135deg, #059669 0%, #10b981 100%);
        color: #fff !important;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        text-decoration: none;
        transition: transform .18s ease;
    }
    .btn-restock-action:hover {
        transform: translateY(-2px);
        filter: brightness(1.1);
    }

    @media (max-width: 900px) {
        .stats-grid { grid-template-columns: 1fr; }
        .content-header { flex-direction: column; align-items: stretch; }
        .search-wrap { width: 100%; }
    }
</style>

<main class="main">
    <section class="content">
        <div class="low-stock-page">

            <!-- PAGE HEADER -->
            <div class="page-header">
                <div>
                    <div class="breadcrumb">
                        <span>Inventory</span>
                        <span>/</span>
                        <span class="current">Low Stock Alert</span>
                    </div>
                    <h1>Low Stock & Critical Inventory</h1>
                    <p>Immediate restock monitoring for items running critically low or completely out of stock.</p>
                </div>

                <div class="header-actions">
                    <a href="index.php" class="btn">← Back to Inventory</a>
                    <button type="button" class="btn btn-blue" id="printBtn">🖨 Print <small>P</small></button>
                    <button type="button" class="btn" id="pdfBtn">↓ PDF <small>V</small></button>
                    <button type="button" class="btn" id="excelBtn">↓ Excel <small>X</small></button>
                    <a href="stock-in.php" class="btn btn-primary">＋ Receive Stock</a>
                </div>
            </div>

            <!-- STATS COUNTERS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--red-soft); color:var(--red);">⛔</div>
                    <div>
                        <div class="stat-label">Completely Out of Stock</div>
                        <div class="stat-value" style="color:#f87171;"><?= $outOfStockCount ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--amber-soft); color:var(--amber);">⚠️</div>
                    <div>
                        <div class="stat-label">Low Stock (≤ <?= $threshold ?> Units)</div>
                        <div class="stat-value" style="color:var(--amber);"><?= $urgentLowCount ?></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background:var(--blue-soft); color:var(--blue);">📋</div>
                    <div>
                        <div class="stat-label">Total Items Requiring Restock</div>
                        <div class="stat-value"><?= $totalCritical ?></div>
                    </div>
                </div>
            </div>

            <!-- CONTENT -->
            <div class="content-box">
                <div class="content-header">
                    <div class="content-title">
                        <h2>Critical Stock Items</h2>
                        <p>Items sorted with the lowest available quantities first for immediate purchasing action.</p>
                    </div>

                    <div class="filters-wrap">
                        <!-- REAL-TIME SEARCH -->
                        <div class="search-wrap">
                            <span class="search-icon">⌕</span>
                            <input
                                type="search"
                                id="lowSearch"
                                class="search-input"
                                placeholder="Search product, SKU..."
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

                        <!-- THRESHOLD SELECTOR -->
                        <select id="thresholdFilter" class="select-filter" onchange="location.href='low-stock.php?threshold=' + this.value;">
                            <option value="5" <?= $threshold === 5 ? 'selected' : '' ?>>Limit ≤ 5 Units</option>
                            <option value="10" <?= $threshold === 10 ? 'selected' : '' ?>>Limit ≤ 10 Units</option>
                            <option value="20" <?= $threshold === 20 ? 'selected' : '' ?>>Limit ≤ 20 Units</option>
                            <option value="0" <?= $threshold === 0 ? 'selected' : '' ?>>Out of Stock Only (0)</option>
                        </select>
                    </div>
                </div>

                <!-- EXPORT BAR -->
                <div class="export-bar">
                    <span class="export-label">Export Critical List</span>
                    <button type="button" class="btn" id="printBtn2">🖨 Print</button>
                    <button type="button" class="btn" id="pdfBtn2">↓ PDF</button>
                    <button type="button" class="btn" id="excelBtn2">↓ Excel</button>
                </div>

                <!-- TABLE SUMMARY -->
                <div class="table-summary">
                    <div>Showing <strong id="visibleCount"><?= $totalCritical ?></strong> items requiring urgent replenishment</div>
                    <div>Threshold Limit: <strong>≤ <?= $threshold ?> units</strong></div>
                </div>

                <!-- TABLE -->
                <div class="table-wrapper">
                    <?php if (empty($lowStockItems)): ?>
                        <div style="padding:65px 20px; text-align:center; color:var(--text-mute);">
                            <div style="font-size:32px; margin-bottom:10px;">🎉</div>
                            <h3 style="color:var(--text-hi); margin:0;">All Stock Levels Healthy!</h3>
                            <p style="font-size:12px; margin-top:5px;">No products are currently under the limit of <?= $threshold ?> units.</p>
                            <a href="index.php" class="btn btn-primary" style="margin-top:16px;">View Full Inventory</a>
                        </div>
                    <?php else: ?>
                        <table class="low-table" id="lowTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Product / SKU</th>
                                    <th>Warehouse</th>
                                    <th>Rack Location</th>
                                    <th>Physical On Hand</th>
                                    <th>Reserved Orders</th>
                                    <th>Available to Sell</th>
                                    <th>Stock Level Status</th>
                                    <th>Last Received</th>
                                    <th style="text-align:right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $serialNo = 1;
                                foreach ($lowStockItems as $item): 
                                    $invId     = (int)($item['InventoryId'] ?? 0);
                                    $pName     = (string)($item['ProductName'] ?? 'Unknown Item');
                                    $sku       = (string)($item['Sku'] ?? 'NO-SKU');
                                    $wName     = (string)($item['WarehouseName'] ?? 'Main Warehouse');
                                    $bin       = (string)($item['BinLocation'] ?? '—');
                                    $stock     = (int)($item['StockQty'] ?? 0);
                                    $reserved  = (int)($item['ReservedQty'] ?? 0);
                                    $avail     = (int)($item['AvailableQty'] ?? 0);
                                    $restocked = dateValue($item['LastRestockedAt'] ?? null);
                                    $imgUrl    = !empty($item['ProductImage']) ? '../uploads/products/' . rawurlencode(basename($item['ProductImage'])) : '';
                                ?>
                                    <tr 
                                        class="low-row" 
                                        data-product="<?= e(strtolower($pName)) ?>"
                                        data-sku="<?= e(strtolower($sku)) ?>"
                                        data-warehouse="<?= e(strtolower($wName)) ?>"
                                    >
                                        <td><span class="order-box">#<?= $serialNo++ ?></span></td>
                                        <td>
                                            <div class="product-meta-cell">
                                                <div class="product-thumb">
                                                    <?php if ($imgUrl !== ''): ?>
                                                        <img src="<?= e($imgUrl) ?>" alt="Product">
                                                    <?php else: ?>
                                                        <span>📦</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <div class="product-title"><?= e($pName) ?></div>
                                                    <div class="product-sku">SKU: <?= e($sku) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><strong style="color:var(--text-hi);"><?= e($wName) ?></strong></td>
                                        <td><span class="tag-rack"><?= e($bin !== '' ? $bin : 'UNASSIGNED') ?></span></td>
                                        <td><strong><?= number_format($stock) ?></strong></td>
                                        <td><span style="color:var(--blue); font-weight:700;"><?= number_format($reserved) ?></span></td>
                                        <td>
                                            <strong style="font-size:14px; color:<?= $avail <= 0 ? '#f87171' : 'var(--amber)' ?>;">
                                                <?= number_format($avail) ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <?php if ($avail <= 0): ?>
                                                <span class="stock-badge-danger">⛔ OUT OF STOCK</span>
                                            <?php else: ?>
                                                <span class="stock-badge-warning">⚠️ ONLY <?= $avail ?> LEFT</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span style="font-size:11px; color:var(--text-mute);"><?= $restocked ?></span></td>
                                        <td style="text-align:right;">
                                            <a href="stock-in.php?id=<?= $invId ?>" class="btn-restock-action" title="Add More Stock">
                                                ＋ Restock
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div id="noMatch" style="display:none; padding:55px 20px; text-align:center; color:var(--text-mute);">
                            <div style="font-size:26px; margin-bottom:8px;">⌕</div>
                            <h3 style="color:var(--text-hi); margin:0;">No matching items found</h3>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </section>
</main>

<!-- EXPORT LIBRARIES -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.4/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        const searchInput     = document.getElementById('lowSearch');
        const warehouseFilter = document.getElementById('warehouseFilter');
        const table           = document.getElementById('lowTable');
        const countElement    = document.getElementById('visibleCount');
        const noMatch         = document.getElementById('noMatch');

        function rows() {
            return table ? Array.from(table.querySelectorAll('tbody .low-row')) : [];
        }

        function visibleRows() {
            return rows().filter(r => r.style.display !== 'none');
        }

        function filterList() {
            if (!table) return;

            const q  = (searchInput?.value || '').toLowerCase().trim();
            const wh = (warehouseFilter?.value || 'all');
            let count = 0;

            rows().forEach(function(row) {
                const prod = row.dataset.product || '';
                const sku  = row.dataset.sku || '';
                const rWh  = row.dataset.warehouse || '';

                const qMatch  = (!q || prod.includes(q) || sku.includes(q));
                const whMatch = (wh === 'all' || rWh === wh);

                if (qMatch && whMatch) {
                    row.style.display = '';
                    count++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (countElement) countElement.textContent = count;
            if (noMatch) noMatch.style.display = (count === 0) ? 'block' : 'none';
        }

        // EXCEL EXPORT
        function exportExcel() {
            const data = visibleRows().map(function(row) {
                return {
                    '#': row.querySelector('.order-box')?.innerText.trim() || '',
                    'Product Name': row.querySelector('.product-title')?.innerText.trim() || '',
                    'SKU': row.querySelector('.product-sku')?.innerText.replace('SKU:', '').trim() || '',
                    'Warehouse': row.cells[2]?.innerText.trim() || '',
                    'Rack Location': row.cells[3]?.innerText.trim() || '',
                    'On Hand': row.cells[4]?.innerText.trim() || '0',
                    'Reserved': row.cells[5]?.innerText.trim() || '0',
                    'Available': row.cells[6]?.innerText.trim() || '0',
                    'Status': row.cells[7]?.innerText.trim() || ''
                };
            });

            if (window.XLSX) {
                const ws = XLSX.utils.json_to_sheet(data);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Low Stock');
                XLSX.writeFile(wb, 'low-stock-alert-' + new Date().toISOString().slice(0, 10) + '.xlsx');
            }
        }

        // PDF EXPORT
        function exportPDF() {
            if (!window.jspdf || !window.jspdf.jsPDF) {
                window.print();
                return;
            }

            const body = visibleRows().map(function(row) {
                return [
                    row.querySelector('.order-box')?.innerText.trim() || '',
                    row.querySelector('.product-title')?.innerText.trim() || '',
                    row.querySelector('.product-sku')?.innerText.replace('SKU:', '').trim() || '',
                    row.cells[2]?.innerText.trim() || '',
                    row.cells[6]?.innerText.trim() || '0',
                    row.cells[7]?.innerText.trim() || ''
                ];
            });

            const doc = new jspdf.jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
            doc.setFontSize(16);
            doc.text('GatewayLinen - Low Stock Report', 14, 14);
            doc.setFontSize(9);
            doc.text('Generated on: ' + new Date().toLocaleString(), 14, 20);

            if (typeof doc.autoTable === 'function') {
                doc.autoTable({
                    startY: 25,
                    head: [['#', 'Product', 'SKU', 'Warehouse', 'Available', 'Status']],
                    body: body,
                    styles: { fontSize: 8, cellPadding: 3 },
                    headStyles: { fontSize: 8 }
                });
            }

            doc.save('low-stock-' + new Date().toISOString().slice(0, 10) + '.pdf');
        }

        document.getElementById('printBtn')?.addEventListener('click', () => window.print());
        document.getElementById('printBtn2')?.addEventListener('click', () => window.print());
        document.getElementById('pdfBtn')?.addEventListener('click', exportPDF);
        document.getElementById('pdfBtn2')?.addEventListener('click', exportPDF);
        document.getElementById('excelBtn')?.addEventListener('click', exportExcel);
        document.getElementById('excelBtn2')?.addEventListener('click', exportExcel);

        searchInput?.addEventListener('input', filterList);
        warehouseFilter?.addEventListener('change', filterList);
    });
})();
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>