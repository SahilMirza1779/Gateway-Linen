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

$activeMenu = 'variants';
$pageTitle  = 'GatewayLinen | Product Variants';

/*
|--------------------------------------------------------------------------
| ADMIN INFO
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] =
        $_SESSION['admin_username'] ??
        'GatewayLinen Administrator';
}

if (!isset($_SESSION['admin_role'])) {
    $_SESSION['admin_role'] = 'Administrator';
}

/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['variant_delete_token'])) {
    $_SESSION['variant_delete_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['variant_delete_token'];

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function e($value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function dateValue($value): string
{
    if ($value instanceof DateTimeInterface) {
        return $value->format('d M Y, h:i A');
    }

    return trim((string)$value);
}

/*
|--------------------------------------------------------------------------
| SUCCESS / ERROR MESSAGES
|--------------------------------------------------------------------------
*/

$actionMessage = trim((string)($_GET['success'] ?? ''));
$actionError   = trim((string)($_GET['error'] ?? ''));

/*
|--------------------------------------------------------------------------
| SEARCH / FILTER / PAGINATION
|--------------------------------------------------------------------------
*/

$searchQuery   = trim((string)($_GET['q'] ?? ''));
$productFilter = trim((string)($_GET['product'] ?? 'all'));
$statusFilter  = trim((string)($_GET['status'] ?? 'all'));

$page = max(
    1,
    (int)($_GET['page'] ?? 1)
);

$perPage = isset($_GET['limit'])
    ? (int)$_GET['limit']
    : 50;

if (!in_array($perPage, [25, 50, 100, 200], true)) {
    $perPage = 50;
}

$offset = ($page - 1) * $perPage;

/*
|--------------------------------------------------------------------------
| PRODUCT DROPDOWN
|--------------------------------------------------------------------------
*/

$productsForFilter = [];

$productFilterSql = "
    SELECT
        p.ProductId,
        p.Name,
        c.Name AS CategoryName
    FROM dbo.Products p
    LEFT JOIN dbo.Categories c
        ON p.CategoryId = c.CategoryId
    ORDER BY p.Name ASC
";

$productFilterStmt = sqlsrv_query(
    $conn,
    $productFilterSql
);

if ($productFilterStmt !== false) {

    while ($row = sqlsrv_fetch_array(
        $productFilterStmt,
        SQLSRV_FETCH_ASSOC
    )) {

        $productsForFilter[] = $row;
    }

    sqlsrv_free_stmt($productFilterStmt);
}

/*
|--------------------------------------------------------------------------
| BUILD WHERE CONDITIONS
|--------------------------------------------------------------------------
*/

$whereClauses = [
    '1 = 1'
];

$params = [];

/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
|
| Searches:
| - Product Name
| - SKU
|
*/

if ($searchQuery !== '') {

    $whereClauses[] = "
        (
            p.Name LIKE ?
            OR v.SKU LIKE ?
        )
    ";

    $like = '%' . $searchQuery . '%';

    $params[] = $like;
    $params[] = $like;
}

/*
|--------------------------------------------------------------------------
| PRODUCT FILTER
|--------------------------------------------------------------------------
*/

if ($productFilter !== 'all') {

    $productIdFilter = (int)$productFilter;

    if ($productIdFilter > 0) {

        $whereClauses[] = 'v.ProductId = ?';

        $params[] = $productIdFilter;
    }
}

/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

if ($statusFilter === 'active') {

    $whereClauses[] = 'v.IsActive = 1';

} elseif ($statusFilter === 'inactive') {

    $whereClauses[] = 'v.IsActive = 0';
}

$whereSql = implode(
    ' AND ',
    $whereClauses
);

/*
|--------------------------------------------------------------------------
| TOTAL MATCHING VARIANTS
|--------------------------------------------------------------------------
*/

$countSql = "
    SELECT COUNT(*) AS Total
    FROM dbo.ProductVariants v
    INNER JOIN dbo.Products p
        ON v.ProductId = p.ProductId
    LEFT JOIN dbo.Categories c
        ON p.CategoryId = c.CategoryId
    WHERE $whereSql
";

$countStmt = sqlsrv_query(
    $conn,
    $countSql,
    $params
);

$totalVariants = 0;

if ($countStmt !== false) {

    $countRow = sqlsrv_fetch_array(
        $countStmt,
        SQLSRV_FETCH_ASSOC
    );

    $totalVariants = (int)(
        $countRow['Total'] ?? 0
    );

    sqlsrv_free_stmt($countStmt);
}

/*
|--------------------------------------------------------------------------
| TOTAL PAGES
|--------------------------------------------------------------------------
*/

$totalPages = max(
    1,
    (int)ceil($totalVariants / $perPage)
);

if ($page > $totalPages) {

    $page = $totalPages;

    $offset = ($page - 1) * $perPage;
}

/*
|--------------------------------------------------------------------------
| FETCH VARIANTS
|--------------------------------------------------------------------------
*/

$variants = [];
$queryError = '';

$variantSql = "
    SELECT
        v.VariantId,
        v.ProductId,
        v.SKU,
        v.Price,
        v.IsActive,

        p.Name AS ProductName,
        p.Slug AS ProductSlug,
        p.BasePrice,
        p.IsFeatured,
        p.IsNewArrival,
        p.IsBestSeller,

        c.CategoryId,
        c.Name AS CategoryName,
        c.ParentCategoryId,

        pcat.Name AS ParentCategoryName

    FROM dbo.ProductVariants v

    INNER JOIN dbo.Products p
        ON v.ProductId = p.ProductId

    LEFT JOIN dbo.Categories c
        ON p.CategoryId = c.CategoryId

    LEFT JOIN dbo.Categories pcat
        ON c.ParentCategoryId = pcat.CategoryId

    WHERE $whereSql

    ORDER BY
        v.VariantId DESC

    OFFSET ? ROWS
    FETCH NEXT ? ROWS ONLY
";

$queryParams = $params;

$queryParams[] = $offset;
$queryParams[] = $perPage;

$variantStmt = sqlsrv_query(
    $conn,
    $variantSql,
    $queryParams
);

if ($variantStmt !== false) {

    while ($row = sqlsrv_fetch_array(
        $variantStmt,
        SQLSRV_FETCH_ASSOC
    )) {

        $variants[] = $row;
    }

    sqlsrv_free_stmt($variantStmt);

} else {

    $queryError =
        'Unable to load product variants from database.';
}

/*
|--------------------------------------------------------------------------
| GLOBAL STATISTICS
|--------------------------------------------------------------------------
*/

$statSql = "
    SELECT
        COUNT(*) AS Total,

        SUM(
            CASE
                WHEN IsActive = 1
                THEN 1
                ELSE 0
            END
        ) AS ActiveCount,

        SUM(
            CASE
                WHEN IsActive = 0
                THEN 1
                ELSE 0
            END
        ) AS InactiveCount

    FROM dbo.ProductVariants
";

$statStmt = sqlsrv_query(
    $conn,
    $statSql
);

$stats = [
    'Total' => 0,
    'ActiveCount' => 0,
    'InactiveCount' => 0
];

if ($statStmt !== false) {

    $statsRow = sqlsrv_fetch_array(
        $statStmt,
        SQLSRV_FETCH_ASSOC
    );

    if ($statsRow !== false) {
        $stats = array_merge(
            $stats,
            $statsRow
        );
    }

    sqlsrv_free_stmt($statStmt);
}

/*
|--------------------------------------------------------------------------
| FEATURED VARIANTS
|--------------------------------------------------------------------------
|
| ProductVariants does not appear to have IsFeatured.
| Therefore Featured count is calculated from parent Products.
|
*/

$featuredSql = "
    SELECT COUNT(*) AS Total
    FROM dbo.ProductVariants v
    INNER JOIN dbo.Products p
        ON v.ProductId = p.ProductId
    WHERE p.IsFeatured = 1
";

$featuredStmt = sqlsrv_query(
    $conn,
    $featuredSql
);

$featuredCount = 0;

if ($featuredStmt !== false) {

    $featuredRow = sqlsrv_fetch_array(
        $featuredStmt,
        SQLSRV_FETCH_ASSOC
    );

    $featuredCount = (int)(
        $featuredRow['Total'] ?? 0
    );

    sqlsrv_free_stmt($featuredStmt);
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

/* ==========================================================================
   VARIANT PAGE
   ========================================================================== */

.variant-page,
.variant-page * {
    box-sizing: border-box;
}

.variant-page {

    width: 100%;
    max-width: 1600px;

    margin: 0 auto;

    padding: 0;

    font-size: 13px;

    --vp-page: #f3f6fa;
    --vp-card: #ffffff;
    --vp-card-alt: #f8fafc;
    --vp-input: #ffffff;

    --vp-border: #dce4ec;
    --vp-border-soft: #e8edf3;

    --vp-text: #162334;
    --vp-body: #536579;
    --vp-muted: #7b8da1;

    --vp-green: #059669;
    --vp-green-soft: rgba(5,150,105,.10);

    --vp-red: #dc2626;
    --vp-red-soft: rgba(220,38,38,.09);

    --vp-blue: #0284c7;
    --vp-blue-soft: rgba(2,132,199,.09);

    --vp-amber: #d97706;
    --vp-amber-soft: rgba(217,119,6,.10);

    --vp-purple: #7c3aed;
    --vp-purple-soft: rgba(124,58,237,.10);

    --vp-shadow:
        0 5px 18px rgba(15,23,42,.05);
}


/* ==========================================================================
   DARK MODE
   ========================================================================== */

html[data-theme="dark"] .variant-page,
body[data-theme="dark"] .variant-page,
html.dark .variant-page,
body.dark .variant-page,
html.dark-mode .variant-page,
body.dark-mode .variant-page {

    --vp-page: #0a1119;
    --vp-card: #111b26;
    --vp-card-alt: #0f1823;
    --vp-input: #0d1620;

    --vp-border: #1e2d3d;
    --vp-border-soft: #182636;

    --vp-text: #f0f4f8;
    --vp-body: #a8b8c8;
    --vp-muted: #6f8295;

    --vp-green: #10b981;
    --vp-green-soft: rgba(16,185,129,.12);

    --vp-red: #ef4444;
    --vp-red-soft: rgba(239,68,68,.12);

    --vp-blue: #38bdf8;
    --vp-blue-soft: rgba(56,189,248,.12);

    --vp-amber: #f59e0b;
    --vp-amber-soft: rgba(245,158,11,.15);

    --vp-purple: #a855f7;
    --vp-purple-soft: rgba(168,85,247,.15);

    --vp-shadow: none;
}

.variant-page {
    background: var(--vp-page);
    color: var(--vp-body);
}


/* ==========================================================================
   HEADER
   ========================================================================== */

.variant-page-header {

    display: flex;

    align-items: center;
    justify-content: space-between;

    gap: 28px;

    margin: 0 0 18px;

    padding: 0 0 16px;

    border-bottom:
        1px solid var(--vp-border);
}

.variant-breadcrumb {

    display: flex;

    align-items: center;

    gap: 9px;

    margin-bottom: 7px;

    color: var(--vp-muted);

    font-size: 12px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .45px;
}

.variant-breadcrumb .current {
    color: var(--vp-green);
}

.variant-page-header h1 {

    margin: 0;

    color: var(--vp-text);

    font-size: 30px;

    font-weight: 900;

    letter-spacing: -.5px;
}

.variant-page-header p {

    margin: 7px 0 0;

    color: var(--vp-muted);

    font-size: 13px;

    font-weight: 600;
}


/* ==========================================================================
   HEADER ACTIONS
   ========================================================================== */

.header-actions {

    display: flex;

    align-items: center;

    gap: 10px;
}

.btn {

    display: inline-flex;

    align-items: center;
    justify-content: center;

    gap: 6px;

    min-height: 40px;

    padding: 0 14px;

    border:
        1px solid var(--vp-border);

    border-radius: 9px;

    background: var(--vp-card);

    color: var(--vp-text) !important;

    font-size: 12px;

    font-weight: 800;

    text-decoration: none;

    cursor: pointer;

    transition: .16s ease;

    position: relative;
}

.btn:hover {

    border-color: var(--vp-green);

    background: var(--vp-green-soft);

    color: var(--vp-green) !important;
}

.btn-primary {

    border-color: transparent;

    background:
        linear-gradient(
            135deg,
            #059669,
            #10b981
        );

    color: #fff !important;

    box-shadow:
        0 5px 14px
        rgba(16,185,129,.16);
}

.btn-primary:hover {

    background:
        linear-gradient(
            135deg,
            #047857,
            #059669
        );

    color: #fff !important;
}

.kbd-badge {

    font-size: 9px;

    background:
        rgba(0,0,0,.08);

    border:
        1px solid rgba(0,0,0,.12);

    padding: 1px 5px;

    border-radius: 4px;

    color: inherit;

    margin-left: 4px;

    font-family: monospace;
}

.btn-primary .kbd-badge {

    background:
        rgba(255,255,255,.20);

    border-color:
        rgba(255,255,255,.30);

    color: #fff;
}


/* ==========================================================================
   NOTICES
   ========================================================================== */

.notice {

    margin-bottom: 12px;

    padding: 12px 14px;

    border-radius: 8px;

    font-size: 12px;

    font-weight: 700;
}

.notice-success {

    border:
        1px solid
        rgba(5,150,105,.25);

    background:
        var(--vp-green-soft);

    color:
        var(--vp-green);
}

.notice-error {

    border:
        1px solid
        rgba(220,38,38,.25);

    background:
        var(--vp-red-soft);

    color:
        var(--vp-red);
}


/* ==========================================================================
   STATISTICS
   ========================================================================== */

.variant-stats {

    display: grid;

    grid-template-columns:
        repeat(4,minmax(0,1fr));

    gap: 14px;

    margin-bottom: 16px;
}

.variant-stat-item {

    display: flex;

    align-items: center;

    gap: 13px;

    min-height: 82px;

    padding: 14px 17px;

    background: var(--vp-card);

    border:
        1px solid var(--vp-border);

    border-radius: 11px;

    box-shadow:
        var(--vp-shadow);
}

.variant-stat-icon {

    display: flex;

    align-items: center;
    justify-content: center;

    width: 40px;
    height: 40px;

    flex: 0 0 40px;

    border-radius: 10px;

    background:
        var(--vp-green-soft);

    color:
        var(--vp-green);

    font-size: 17px;

    font-weight: 900;
}

.variant-stat-label {

    color:
        var(--vp-muted);

    font-size: 10px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .45px;
}

.variant-stat-value {

    margin-top: 4px;

    color:
        var(--vp-text);

    font-size: 24px;

    font-weight: 900;

    line-height: 1;
}


/* ==========================================================================
   MAIN CONTENT CARD
   ========================================================================== */

.variant-content {

    background:
        var(--vp-card);

    border:
        1px solid var(--vp-border);

    border-radius: 12px;

    overflow: hidden;

    box-shadow:
        var(--vp-shadow);
}

.variant-content-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 28px;

    padding: 20px 24px;

    min-height: 94px;

    border-bottom:
        1px solid var(--vp-border);
}

.variant-content-title h2 {

    margin: 0;

    color:
        var(--vp-text);

    font-size: 20px;

    font-weight: 900;
}

.variant-content-title p {

    margin: 5px 0 0;

    color:
        var(--vp-muted);

    font-size: 11px;

    font-weight: 600;

    line-height: 1.5;
}


/* ==========================================================================
   FILTERS
   ========================================================================== */

.variant-filters {

    display: flex;

    align-items: center;

    gap: 10px;

    flex: 1 1 auto;

    justify-content: flex-end;
}

.variant-search-wrap {

    position: relative;

    width:
        min(420px,100%);

    flex:
        1 1 320px;
}

.variant-search-icon {

    position: absolute;

    left: 16px;

    top: 50%;

    transform:
        translateY(-50%);

    color:
        var(--vp-muted);

    pointer-events: none;

    font-size: 17px;
}

.variant-search,
.variant-filter {

    height: 46px;

    border:
        1px solid var(--vp-border);

    border-radius: 9px;

    outline: none;

    background:
        var(--vp-input);

    color:
        var(--vp-text);

    font-size: 13px;

    font-weight: 600;
}

.variant-search {

    width: 100%;

    padding:
        0 45px
        0 44px;
}

.variant-filter {

    min-width: 170px;

    padding:
        0 14px;
}

.variant-search::placeholder {

    color:
        var(--vp-muted);

    font-size: 13px;

    font-weight: 500;
}

.variant-search:focus,
.variant-filter:focus {

    border-color:
        var(--vp-green);

    box-shadow:
        0 0 0 3px
        var(--vp-green-soft);
}

.search-kbd-hint {

    position: absolute;

    right: 12px;

    top: 50%;

    transform:
        translateY(-50%);

    font-size: 10px;

    color:
        var(--vp-muted);

    background:
        var(--vp-card-alt);

    border:
        1px solid var(--vp-border);

    padding: 2px 5px;

    border-radius: 4px;

    pointer-events: none;

    font-family: monospace;
}


/* ==========================================================================
   SUMMARY
   ========================================================================== */

.variant-table-summary {

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 12px 24px;

    border-bottom:
        1px solid var(--vp-border);
}

.variant-result-text {

    color:
        var(--vp-muted);

    font-size: 11px;

    font-weight: 700;
}

.variant-result-text strong {

    color:
        var(--vp-text);
}


/* ==========================================================================
   TABLE
   ========================================================================== */

.variant-table-wrapper {

    width: 100%;

    overflow-x: auto;
}

.variant-table {

    width: 100%;

    border-collapse: collapse;

    min-width: 1050px;
}

.variant-table th {

    height: 46px;

    padding: 0 16px;

    background:
        var(--vp-card-alt);

    border-bottom:
        1px solid var(--vp-border);

    color:
        var(--vp-muted);

    font-size: 10px;

    font-weight: 900;

    text-align: left;

    text-transform: uppercase;

    letter-spacing: .55px;

    white-space: nowrap;
}

.variant-table td {

    padding: 14px 16px;

    background: transparent;

    border-bottom:
        1px solid var(--vp-border-soft);

    color:
        var(--vp-body);

    font-size: 12px;

    line-height: 1.45;

    vertical-align: middle;
}

.variant-table tbody tr {

    cursor: pointer;

    transition:
        background .15s ease;
}

.variant-table tbody tr:hover {

    background:
        var(--vp-green-soft);
}


/* ==========================================================================
   ID
   ========================================================================== */

.order-box {

    display: inline-flex;

    align-items: center;
    justify-content: center;

    min-width: 40px;

    height: 30px;

    padding: 0 8px;

    border-radius: 7px;

    background:
        var(--vp-input);

    border:
        1px solid var(--vp-border);

    color:
        var(--vp-green);

    font-size: 11px;

    font-weight: 900;
}


/* ==========================================================================
   PRODUCT DETAILS
   ========================================================================== */

.variant-product-main {

    display: flex;

    align-items: center;

    gap: 12px;

    min-width: 230px;
}

.variant-product-icon {

    display: flex;

    align-items: center;
    justify-content: center;

    width: 52px;
    height: 52px;

    flex:
        0 0 52px;

    border-radius: 9px;

    background:
        var(--vp-card-alt);

    border:
        1px solid var(--vp-border);

    font-size: 20px;
}

.variant-product-name {

    color:
        var(--vp-text);

    font-size: 14px;

    font-weight: 900;
}

.variant-sku {

    margin-top: 4px;

    color:
        var(--vp-muted);

    font-size: 10px;

    font-family: monospace;
}


/* ==========================================================================
   CATEGORY
   ========================================================================== */

.category-badge {

    display: inline-flex;

    align-items: center;

    gap: 5px;

    padding: 5px 9px;

    border-radius: 6px;

    background:
        var(--vp-blue-soft);

    color:
        var(--vp-blue);

    font-size: 10px;

    font-weight: 800;

    white-space: nowrap;
}

.parent-category {

    display: block;

    margin-top: 4px;

    color:
        var(--vp-muted);

    font-size: 9px;

    font-weight: 600;
}


/* ==========================================================================
   PRICE
   ========================================================================== */

.price-value {

    color:
        var(--vp-text);

    font-size: 14px;

    font-weight: 900;
}

.base-price {

    margin-top: 3px;

    color:
        var(--vp-muted);

    font-size: 10px;
}


/* ==========================================================================
   PRODUCT FLAGS
   ========================================================================== */

.flags-cell {

    display: flex;

    gap: 5px;

    flex-wrap: wrap;

    min-width: 100px;
}

.badge-tag {

    font-size: 9px;

    font-weight: 900;

    padding: 3px 7px;

    border-radius: 5px;

    text-transform: uppercase;

    letter-spacing: .3px;
}

.tag-featured {

    background:
        var(--vp-amber-soft);

    color:
        var(--vp-amber);

    border:
        1px solid
        rgba(217,119,6,.25);
}

.tag-new {

    background:
        var(--vp-blue-soft);

    color:
        var(--vp-blue);

    border:
        1px solid
        rgba(2,132,199,.25);
}

.tag-bestseller {

    background:
        var(--vp-purple-soft);

    color:
        var(--vp-purple);

    border:
        1px solid
        rgba(124,58,237,.25);
}


/* ==========================================================================
   STATUS
   ========================================================================== */

.variant-status {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    min-height: 26px;

    padding: 0 10px;

    border-radius: 20px;

    font-size: 10px;

    font-weight: 800;

    white-space: nowrap;
}

.variant-status-dot {

    width: 5px;
    height: 5px;

    border-radius: 50%;
}

.variant-status-active {

    background:
        var(--vp-green-soft);

    color:
        var(--vp-green);
}

.variant-status-active
.variant-status-dot {

    background:
        var(--vp-green);

    box-shadow:
        0 0 6px
        var(--vp-green);
}

.variant-status-inactive {

    background:
        var(--vp-red-soft);

    color:
        var(--vp-red);
}

.variant-status-inactive
.variant-status-dot {

    background:
        var(--vp-red);
}


/* ==========================================================================
   ACTIONS
   ========================================================================== */

.variant-actions {

    display: flex;

    align-items: center;

    justify-content: flex-end;

    gap: 5px;

    flex-wrap: nowrap;
}

.variant-action {

    display: inline-flex;

    align-items: center;
    justify-content: center;

    width: 34px;
    height: 34px;

    border:
        1px solid var(--vp-border);

    border-radius: 7px;

    background:
        var(--vp-card);

    color:
        var(--vp-body) !important;

    text-decoration: none;

    cursor: pointer;

    font-size: 13px;

    transition:
        .16s ease;
}

.variant-action:hover {

    border-color:
        var(--vp-green);

    background:
        var(--vp-green-soft);

    color:
        var(--vp-green) !important;
}

.variant-action-delete:hover {

    border-color:
        rgba(220,38,38,.4);

    background:
        var(--vp-red-soft);

    color:
        var(--vp-red) !important;
}


/* ==========================================================================
   EMPTY
   ========================================================================== */

.variant-empty {

    padding:
        65px 20px;

    text-align: center;
}

.variant-empty-icon {

    margin-bottom: 12px;

    color:
        var(--vp-green);

    font-size: 32px;
}

.variant-empty h3 {

    margin: 0;

    color:
        var(--vp-text);

    font-size: 16px;

    font-weight: 900;
}

.variant-empty p {

    margin: 6px 0 0;

    color:
        var(--vp-muted);

    font-size: 11px;
}


/* ==========================================================================
   PAGINATION
   ========================================================================== */

.pagination-bar {

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 14px 24px;

    border-top:
        1px solid var(--vp-border);

    background:
        var(--vp-card-alt);
}

.pagination-limit-wrap {

    display: flex;

    align-items: center;

    gap: 8px;

    font-size: 12px;

    font-weight: 700;

    color:
        var(--vp-muted);
}

.pagination-limit-select {

    height: 36px;

    padding:
        0 10px;

    border:
        1px solid var(--vp-border);

    border-radius: 8px;

    background:
        var(--vp-card);

    color:
        var(--vp-text);

    font-size: 12px;

    font-weight: 800;

    outline: none;

    cursor: pointer;
}

.pagination-limit-select:focus {

    border-color:
        var(--vp-green);

    box-shadow:
        0 0 0 3px
        var(--vp-green-soft);
}

.pagination-links {

    display: flex;

    gap: 5px;
}

.page-link {

    display: inline-flex;

    align-items: center;
    justify-content: center;

    min-width: 32px;

    height: 32px;

    padding:
        0 9px;

    border:
        1px solid var(--vp-border);

    border-radius: 6px;

    background:
        var(--vp-card);

    color:
        var(--vp-text);

    font-weight: 700;

    text-decoration: none;

    font-size: 11px;
}

.page-link:hover,
.page-link.active {

    background:
        var(--vp-green);

    border-color:
        var(--vp-green);

    color:
        #fff !important;
}


/* ==========================================================================
   MODAL
   ========================================================================== */

.variant-modal-backdrop {

    position: fixed;

    inset: 0;

    z-index: 99999;

    display: none;

    align-items: center;

    justify-content: center;

    padding: 20px;

    background:
        rgba(0,0,0,.75);

    backdrop-filter:
        blur(4px);
}

.variant-modal-backdrop.show {

    display: flex;
}

.variant-modal {

    width:
        min(900px,100%);

    max-height:
        90vh;

    overflow-y: auto;

    background:
        #ffffff !important;

    color:
        #162334 !important;

    border:
        1px solid #dce4ec;

    border-radius: 14px;

    box-shadow:
        0 25px 75px
        rgba(0,0,0,.50);

    display: flex;

    flex-direction: column;
}

.variant-modal-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding:
        18px 24px;

    border-bottom:
        1px solid #dce4ec;

    background:
        #f8fafc !important;
}

.variant-modal-header h3 {

    margin: 0;

    color:
        #162334 !important;

    font-size: 18px;

    font-weight: 900;
}

.variant-modal-close {

    border: 0;

    background: transparent;

    color:
        #7b8da1;

    font-size: 26px;

    font-weight: 700;

    cursor: pointer;
}

.variant-modal-close:hover {

    color:
        #dc2626;
}

.variant-modal-body {

    padding:
        28px;

    overflow-y: auto;

    background:
        #ffffff !important;

    color:
        #162334 !important;
}


/* ==========================================================================
   MODAL PRODUCT HEADER
   ========================================================================== */

.variant-detail-product {

    display: flex;

    align-items: center;

    gap: 18px;

    padding-bottom: 20px;

    border-bottom:
        1px solid #e8edf3;
}

.variant-detail-icon {

    width: 80px;
    height: 80px;

    flex:
        0 0 80px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 12px;

    border:
        1px solid #dce4ec;

    background:
        #f8fafc;

    font-size: 30px;
}

.variant-detail-product-name {

    color:
        #162334;

    font-size: 20px;

    font-weight: 900;
}

.variant-detail-sku {

    margin-top: 5px;

    color:
        #7b8da1;

    font-size: 11px;

    font-family: monospace;
}


/* ==========================================================================
   MODAL GRID
   ========================================================================== */

.variant-detail-grid {

    display: grid;

    grid-template-columns:
        repeat(3,1fr);

    gap: 12px;

    margin-top: 20px;
}

.variant-detail-card {

    padding:
        13px;

    border:
        1px solid #e8edf3;

    border-radius: 8px;

    background:
        #f8fafc;
}

.variant-detail-label {

    color:
        #7b8da1 !important;

    font-size: 10px;

    font-weight: 900;

    text-transform: uppercase;

    letter-spacing: .5px;
}

.variant-detail-value {

    margin-top: 5px;

    color:
        #162334 !important;

    font-size: 13px;

    font-weight: 800;

    word-break: break-word;
}

.variant-detail-section {

    margin-top: 20px;

    padding:
        15px;

    border:
        1px solid #e8edf3;

    border-radius: 9px;

    background:
        #f8fafc;
}

.variant-detail-section-title {

    margin-bottom: 7px;

    color:
        #7b8da1;

    font-size: 10px;

    font-weight: 900;

    text-transform: uppercase;

    letter-spacing: .5px;
}

.variant-detail-section-value {

    color:
        #162334;

    font-size: 13px;

    font-weight: 700;

    line-height: 1.6;
}


/* ==========================================================================
   MODAL FOOTER
   ========================================================================== */

.variant-modal-footer {

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding:
        14px 24px;

    border-top:
        1px solid #dce4ec;

    background:
        #f8fafc !important;
}

.variant-modal-footer .btn {

    background:
        #ffffff !important;

    color:
        #162334 !important;

    border-color:
        #dce4ec !important;
}

.variant-modal-footer .btn:hover {

    background:
        rgba(5,150,105,.10) !important;

    color:
        #059669 !important;

    border-color:
        #059669 !important;
}


/* ==========================================================================
   DELETE MODAL
   ========================================================================== */

.delete-modal {

    width:
        min(470px,100%);

    background:
        #ffffff;

    border-radius:
        14px;

    border:
        1px solid #dce4ec;

    overflow: hidden;

    box-shadow:
        0 25px 75px
        rgba(0,0,0,.50);
}

.delete-modal-header {

    padding:
        18px 22px;

    background:
        #f8fafc;

    border-bottom:
        1px solid #dce4ec;

    color:
        #162334;

    font-size: 17px;

    font-weight: 900;

    display: flex;

    justify-content: space-between;

    align-items: center;
}

.delete-modal-body {

    padding:
        24px 22px;

    color:
        #536579;

    font-size: 13px;

    line-height: 1.6;
}

.delete-modal-footer {

    display: flex;

    justify-content: flex-end;

    gap: 10px;

    padding:
        14px 22px;

    border-top:
        1px solid #dce4ec;

    background:
        #f8fafc;
}

.btn-delete-confirm {

    border: 0;

    background:
        #dc2626;

    color: #fff;

    padding:
        9px 18px;

    border-radius: 8px;

    font-size: 12px;

    font-weight: 800;

    cursor: pointer;
}

.btn-delete-confirm:hover {

    background:
        #b91c1c;
}


/* ==========================================================================
   RESPONSIVE
   ========================================================================== */

@media(max-width: 1200px) {

    .variant-page-header {

        align-items: flex-start;

        flex-direction: column;
    }

    .header-actions {

        width: 100%;

        flex-wrap: wrap;
    }

    .variant-content-header {

        align-items: flex-start;

        flex-direction: column;
    }

    .variant-filters {

        width: 100%;

        justify-content: flex-start;
    }

    .variant-stats {

        grid-template-columns:
            repeat(2,minmax(0,1fr));
    }
}

@media(max-width: 768px) {

    .variant-stats {

        grid-template-columns: 1fr;
    }

    .variant-filters {

        flex-direction: column;

        align-items: stretch;
    }

    .variant-search-wrap {

        width: 100%;

        flex: none;
    }

    .variant-filter {

        width: 100%;
    }

    .variant-table-summary {

        gap: 10px;

        align-items: flex-start;

        flex-direction: column;
    }

    .pagination-bar {

        gap: 15px;

        align-items: flex-start;

        flex-direction: column;
    }

    .pagination-links {

        width: 100%;
    }

    .variant-detail-grid {

        grid-template-columns: 1fr;
    }

    .variant-modal-body {

        padding: 20px;
    }

    .variant-modal-footer {

        align-items: flex-start;

        gap: 12px;

        flex-direction: column;
    }
}


/* ==========================================================================
   PRINT
   ========================================================================== */

@media print {

    .variant-page-header .header-actions,
    .variant-filters,
    .variant-actions,
    .pagination-bar,
    .variant-stat-item,
    .variant-modal-backdrop {

        display: none !important;
    }

    .variant-page {

        max-width: none;

        background: #fff !important;

        color: #000 !important;
    }

    .variant-content {

        border: 0 !important;

        box-shadow: none !important;
    }

    .variant-table {

        min-width: 0;
    }

    .variant-table th,
    .variant-table td {

        color: #000 !important;

        background: #fff !important;
    }
}

</style>


<main class="main">

<section class="content">

<div class="variant-page">


<!-- ==========================================================================
     PAGE HEADER
     ========================================================================== -->

<div class="variant-page-header">

    <div>

        <div class="variant-breadcrumb">

            <span>Catalog</span>

            <span>/</span>

            <span>Products</span>

            <span>/</span>

            <span class="current">
                Product Variants
            </span>

        </div>

        <h1>
            Product Variants Management
        </h1>

        <p>
            Manage product variants, SKU codes, pricing, status and product relationships.
        </p>

    </div>


    <div class="header-actions">

        <button
            type="button"
            class="btn"
            id="printBtn"
            title="Print Variants"
        >
            🖨 Print
            <span class="kbd-badge">P</span>
        </button>


        <button
            type="button"
            class="btn"
            id="pdfBtn"
            title="Export PDF"
        >
            ↓ PDF
            <span class="kbd-badge">V</span>
        </button>


        <button
            type="button"
            class="btn"
            id="excelBtn"
            title="Export Excel"
        >
            ↓ Excel
            <span class="kbd-badge">X</span>
        </button>


        <a
            href="add.php"
            class="btn btn-primary"
            id="addVariantBtn"
            title="Add Variant"
        >
            ＋ Add Variant
            <span class="kbd-badge">A</span>
        </a>

    </div>

</div>


<!-- ==========================================================================
     NOTICES
     ========================================================================== -->

<?php if ($actionMessage !== ''): ?>

    <div class="notice notice-success">

        <?= e($actionMessage) ?>

    </div>

<?php endif; ?>


<?php if ($actionError !== ''): ?>

    <div class="notice notice-error">

        <?= e($actionError) ?>

    </div>

<?php endif; ?>


<?php if ($queryError !== ''): ?>

    <div class="notice notice-error">

        <?= e($queryError) ?>

    </div>

<?php endif; ?>


<!-- ==========================================================================
     STATISTICS
     ========================================================================== -->

<div class="variant-stats">


    <div class="variant-stat-item">

        <div class="variant-stat-icon">
            #
        </div>

        <div>

            <div class="variant-stat-label">
                Total Variants
            </div>

            <div class="variant-stat-value">
                <?= (int)$stats['Total'] ?>
            </div>

        </div>

    </div>


    <div class="variant-stat-item">

        <div class="variant-stat-icon">
            ✓
        </div>

        <div>

            <div class="variant-stat-label">
                Active
            </div>

            <div class="variant-stat-value">
                <?= (int)$stats['ActiveCount'] ?>
            </div>

        </div>

    </div>


    <div class="variant-stat-item">

        <div
            class="variant-stat-icon"
            style="
                color:#64748b;
                background:rgba(100,116,139,.10);
            "
        >
            ○
        </div>

        <div>

            <div class="variant-stat-label">
                Inactive
            </div>

            <div class="variant-stat-value">
                <?= (int)$stats['InactiveCount'] ?>
            </div>

        </div>

    </div>


    <div class="variant-stat-item">

        <div
            class="variant-stat-icon"
            style="
                color:#d97706;
                background:rgba(217,119,6,.10);
            "
        >
            ★
        </div>

        <div>

            <div class="variant-stat-label">
                Featured Product
            </div>

            <div class="variant-stat-value">
                <?= (int)$featuredCount ?>
            </div>

        </div>

    </div>

</div>


<!-- ==========================================================================
     CONTENT
     ========================================================================== -->

<div class="variant-content">


<!-- CONTENT HEADER -->

<div class="variant-content-header">

    <div class="variant-content-title">

        <h2>
            Product Variants List
        </h2>

        <p>
            Product name, SKU, category, price, product badges, status and actions.
        </p>

    </div>


    <div class="variant-filters">

        <form
            method="GET"
            action=""
            id="filterForm"
            style="
                display:flex;
                gap:10px;
                width:100%;
                justify-content:flex-end;
                align-items:center;
            "
        >

            <input
                type="hidden"
                name="limit"
                id="limitInput"
                value="<?= (int)$perPage ?>"
            >


            <!-- SEARCH -->

            <div class="variant-search-wrap">

                <span class="variant-search-icon">
                    ⌕
                </span>

                <input
                    type="search"
                    name="q"
                    id="variantSearch"
                    class="variant-search"
                    placeholder="Search product name or SKU... (B)"
                    value="<?= e($searchQuery) ?>"
                    autocomplete="off"
                >

                <span class="search-kbd-hint">
                    B
                </span>

            </div>


            <!-- PRODUCT FILTER -->

            <select
                name="product"
                id="productFilter"
                class="variant-filter"
                title="Filter by Product"
                onchange="document.getElementById('filterForm').submit();"
            >

                <option value="all">
                    All Products
                </option>

                <?php foreach ($productsForFilter as $filterProduct): ?>

                    <option
                        value="<?= (int)$filterProduct['ProductId'] ?>"
                        <?= $productFilter === (string)$filterProduct['ProductId'] ? 'selected' : '' ?>
                    >

                        <?= e($filterProduct['Name']) ?>

                    </option>

                <?php endforeach; ?>

            </select>


            <!-- STATUS -->

            <select
                name="status"
                id="variantStatusFilter"
                class="variant-filter"
                onchange="document.getElementById('filterForm').submit();"
            >

                <option
                    value="all"
                    <?= $statusFilter === 'all' ? 'selected' : '' ?>
                >
                    All Status
                </option>

                <option
                    value="active"
                    <?= $statusFilter === 'active' ? 'selected' : '' ?>
                >
                    Active
                </option>

                <option
                    value="inactive"
                    <?= $statusFilter === 'inactive' ? 'selected' : '' ?>
                >
                    Inactive
                </option>

            </select>

        </form>

    </div>

</div>


<!-- SUMMARY -->

<div class="variant-table-summary">

    <div class="variant-result-text">

        Showing items

        <strong>
            <?= $totalVariants > 0 ? $offset + 1 : 0 ?>
        </strong>

        to

        <strong>
            <?= min($offset + $perPage, $totalVariants) ?>
        </strong>

        of

        <strong>
            <?= $totalVariants ?>
        </strong>

        results

    </div>


    <div class="variant-result-text">

        Total Variants:

        <strong>
            <?= $totalVariants ?>
        </strong>

    </div>

</div>


<!-- ==========================================================================
     TABLE
     ========================================================================== -->

<div class="variant-table-wrapper">

<?php if (empty($variants)): ?>

    <div class="variant-empty">

        <div class="variant-empty-icon">
            📦
        </div>

        <h3>
            No Product Variants Found
        </h3>

        <p>
            Try clearing your search or filters, or add a new variant.
        </p>

        <a
            href="index.php"
            class="btn btn-primary"
            style="margin-top:16px;"
        >
            Reset Filters
        </a>

    </div>

<?php else: ?>


<table
    class="variant-table"
    id="variantTable"
>

<thead>

<tr>

    <th>
        ID
    </th>

    <th>
        Product / SKU
    </th>

    <th>
        Category
    </th>

    <th>
        Variant Price
    </th>

    <th>
        Product Badges
    </th>

    <th>
        Status
    </th>

    <th style="text-align:right;">
        Actions
    </th>

</tr>

</thead>


<tbody>


<?php

$displayId = $offset + 1;

foreach ($variants as $variant):

    $variantId =
        (int)($variant['VariantId'] ?? 0);

    $productId =
        (int)($variant['ProductId'] ?? 0);

    $productName =
        trim((string)($variant['ProductName'] ?? ''));

    $productSlug =
        trim((string)($variant['ProductSlug'] ?? ''));

    $sku =
        trim((string)($variant['SKU'] ?? ''));

    $categoryName =
        trim((string)($variant['CategoryName'] ?? 'Uncategorized'));

    $parentCategoryName =
        trim((string)($variant['ParentCategoryName'] ?? ''));

    $price =
        (float)($variant['Price'] ?? 0);

    $basePrice =
        (float)($variant['BasePrice'] ?? 0);

    $isActive =
        !empty($variant['IsActive']);

    $isFeatured =
        !empty($variant['IsFeatured']);

    $isNew =
        !empty($variant['IsNewArrival']);

    $isBestSeller =
        !empty($variant['IsBestSeller']);

    $statusText =
        $isActive
            ? 'Active'
            : 'Inactive';

    $flags = [];

    if ($isFeatured) {
        $flags[] = 'Featured';
    }

    if ($isNew) {
        $flags[] = 'New Arrival';
    }

    if ($isBestSeller) {
        $flags[] = 'Best Seller';
    }

    $flagsText =
        !empty($flags)
            ? implode(' • ', $flags)
            : 'None';

?>


<tr
    class="variant-row"
    data-status="<?= $isActive ? 'active' : 'inactive' ?>"
>


<!-- ID -->

<td>

    <span class="order-box">

        #<?= $displayId++ ?>

    </span>

</td>


<!-- PRODUCT -->

<td>

    <div class="variant-product-main">

        <div class="variant-product-icon">
            📦
        </div>

        <div>

            <div class="variant-product-name">

                <?= e($productName) ?>

            </div>

            <div class="variant-sku">

                SKU:
                <?= e($sku !== '' ? $sku : 'N/A') ?>

            </div>

        </div>

    </div>

</td>


<!-- CATEGORY -->

<td>

    <span class="category-badge">

        📁

        <?= e($categoryName) ?>

    </span>

    <?php if ($parentCategoryName !== ''): ?>

        <span class="parent-category">

            Main:
            <?= e($parentCategoryName) ?>

        </span>

    <?php endif; ?>

</td>


<!-- PRICE -->

<td>

    <div class="price-value">

        $<?= number_format($price, 2) ?>

    </div>

    <div class="base-price">

        Product Base:
        $<?= number_format($basePrice, 2) ?>

    </div>

</td>


<!-- BADGES -->

<td>

    <div class="flags-cell">

        <?php if ($isFeatured): ?>

            <span class="badge-tag tag-featured">
                Featured
            </span>

        <?php endif; ?>


        <?php if ($isNew): ?>

            <span class="badge-tag tag-new">
                New
            </span>

        <?php endif; ?>


        <?php if ($isBestSeller): ?>

            <span class="badge-tag tag-bestseller">
                Best Seller
            </span>

        <?php endif; ?>


        <?php if (!$isFeatured && !$isNew && !$isBestSeller): ?>

            <span style="color:var(--vp-muted);">
                —
            </span>

        <?php endif; ?>

    </div>

</td>


<!-- STATUS -->

<td>

    <?php if ($isActive): ?>

        <span class="variant-status variant-status-active">

            <span class="variant-status-dot"></span>

            Active

        </span>

    <?php else: ?>

        <span class="variant-status variant-status-inactive">

            <span class="variant-status-dot"></span>

            Inactive

        </span>

    <?php endif; ?>

</td>


<!-- ACTIONS -->

<td style="text-align:right;">

    <div class="variant-actions">


        <!-- VIEW -->

        <button
            type="button"
            class="variant-action detail-btn"
            title="View Variant Details"

            data-id="<?= $variantId ?>"

            data-productid="<?= $productId ?>"

            data-product="<?= e($productName) ?>"

            data-slug="<?= e($productSlug) ?>"

            data-sku="<?= e($sku) ?>"

            data-category="<?= e($categoryName) ?>"

            data-parentcategory="<?= e($parentCategoryName) ?>"

            data-price="<?= number_format($price, 2) ?>"

            data-baseprice="<?= number_format($basePrice, 2) ?>"

            data-status="<?= e($statusText) ?>"

            data-featured="<?= $isFeatured ? 'Yes' : 'No' ?>"

            data-new="<?= $isNew ? 'Yes' : 'No' ?>"

            data-bestseller="<?= $isBestSeller ? 'Yes' : 'No' ?>"

            data-flags="<?= e($flagsText) ?>"
        >

            ◉

        </button>


        <!-- EDIT -->

        <a
            href="edit.php?id=<?= $variantId ?>"
            class="variant-action"
            title="Edit Variant"
        >
            ✎
        </a>


        <!-- DELETE -->

        <form
            method="POST"
            action="delete.php"
            class="delete-form"
            style="display:inline;"
        >

            <input
                type="hidden"
                name="variant_id"
                value="<?= $variantId ?>"
            >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($csrfToken) ?>"
            >

            <button
                type="submit"
                class="variant-action variant-action-delete delete-variant-btn"
                title="Delete Variant"
            >
                ×
            </button>

        </form>


    </div>

</td>


</tr>


<?php endforeach; ?>


</tbody>

</table>


<?php endif; ?>

</div>


<!-- ==========================================================================
     PAGINATION
     ========================================================================== -->

<div class="pagination-bar">


    <div class="pagination-limit-wrap">

        <span>
            Show
        </span>

        <select
            id="perPageSelect"
            class="pagination-limit-select"
            onchange="changePerPage(this.value);"
        >

            <option
                value="25"
                <?= $perPage === 25 ? 'selected' : '' ?>
            >
                25
            </option>

            <option
                value="50"
                <?= $perPage === 50 ? 'selected' : '' ?>
            >
                50
            </option>

            <option
                value="100"
                <?= $perPage === 100 ? 'selected' : '' ?>
            >
                100
            </option>

            <option
                value="200"
                <?= $perPage === 200 ? 'selected' : '' ?>
            >
                200
            </option>

        </select>

        <span>
            variants
        </span>

    </div>


    <div class="pagination-links">

<?php

$queryString = $_GET;

/*
|--------------------------------------------------------------------------
| FIRST / PREVIOUS
|--------------------------------------------------------------------------
*/

if ($page > 1) {

    $queryString['page'] = 1;

    echo '<a href="?' .
        http_build_query($queryString) .
        '" class="page-link">«</a>';

    $queryString['page'] =
        $page - 1;

    echo '<a href="?' .
        http_build_query($queryString) .
        '" class="page-link">‹</a>';

} else {

    echo '<span class="page-link" style="opacity:.4;pointer-events:none;">«</span>';

    echo '<span class="page-link" style="opacity:.4;pointer-events:none;">‹</span>';
}


/*
|--------------------------------------------------------------------------
| PAGE NUMBER
|--------------------------------------------------------------------------
*/

if ($totalPages <= 5) {

    for (
        $i = 1;
        $i <= $totalPages;
        $i++
    ) {

        $queryString['page'] = $i;

        echo '<a href="?' .
            http_build_query($queryString) .
            '" class="page-link ' .
            ($page === $i ? 'active' : '') .
            '">' .
            $i .
            '</a>';
    }

} else {

    $start =
        max(
            1,
            min(
                $page - 2,
                $totalPages - 4
            )
        );

    $end =
        min(
            $totalPages,
            $start + 4
        );

    for (
        $i = $start;
        $i <= $end;
        $i++
    ) {

        $queryString['page'] = $i;

        echo '<a href="?' .
            http_build_query($queryString) .
            '" class="page-link ' .
            ($page === $i ? 'active' : '') .
            '">' .
            $i .
            '</a>';
    }
}


/*
|--------------------------------------------------------------------------
| NEXT / LAST
|--------------------------------------------------------------------------
*/

if ($page < $totalPages) {

    $queryString['page'] =
        $page + 1;

    echo '<a href="?' .
        http_build_query($queryString) .
        '" class="page-link">›</a>';

    $queryString['page'] =
        $totalPages;

    echo '<a href="?' .
        http_build_query($queryString) .
        '" class="page-link">»</a>';

} else {

    echo '<span class="page-link" style="opacity:.4;pointer-events:none;">›</span>';

    echo '<span class="page-link" style="opacity:.4;pointer-events:none;">»</span>';
}

?>

    </div>

</div>


</div>
<!-- END CONTENT -->

</div>
<!-- END VARIANT PAGE -->

</section>

</main>


<!-- ==========================================================================
     VIEW VARIANT MODAL
     ========================================================================== -->

<div
    class="variant-modal-backdrop"
    id="variantModal"
    aria-hidden="true"
>

    <div
        class="variant-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="variantModalTitle"
    >


        <div class="variant-modal-header">

            <h3 id="variantModalTitle">
                Product Variant Details
            </h3>

            <button
                type="button"
                class="variant-modal-close"
                id="variantModalClose"
            >
                ×
            </button>

        </div>


        <div class="variant-modal-body">


            <!-- PRODUCT -->

            <div class="variant-detail-product">

                <div class="variant-detail-icon">
                    📦
                </div>

                <div>

                    <div
                        class="variant-detail-product-name"
                        id="modalProductName"
                    >
                        —
                    </div>

                    <div
                        class="variant-detail-sku"
                        id="modalSku"
                    >
                        SKU: —
                    </div>

                </div>

            </div>


            <!-- DETAIL CARDS -->

            <div class="variant-detail-grid">


                <div class="variant-detail-card">

                    <div class="variant-detail-label">
                        Variant ID
                    </div>

                    <div
                        class="variant-detail-value"
                        id="modalVariantId"
                    >
                        —
                    </div>

                </div>


                <div class="variant-detail-card">

                    <div class="variant-detail-label">
                        Product ID
                    </div>

                    <div
                        class="variant-detail-value"
                        id="modalProductId"
                    >
                        —
                    </div>

                </div>


                <div class="variant-detail-card">

                    <div class="variant-detail-label">
                        Status
                    </div>

                    <div
                        class="variant-detail-value"
                        id="modalStatus"
                    >
                        —
                    </div>

                </div>


                <div class="variant-detail-card">

                    <div class="variant-detail-label">
                        Variant Price
                    </div>

                    <div
                        class="variant-detail-value"
                        id="modalPrice"
                        style="color:#059669 !important;"
                    >
                        —
                    </div>

                </div>


                <div class="variant-detail-card">

                    <div class="variant-detail-label">
                        Product Base Price
                    </div>

                    <div
                        class="variant-detail-value"
                        id="modalBasePrice"
                    >
                        —
                    </div>

                </div>


                <div class="variant-detail-card">

                    <div class="variant-detail-label">
                        Category
                    </div>

                    <div
                        class="variant-detail-value"
                        id="modalCategory"
                    >
                        —
                    </div>

                </div>


            </div>


            <!-- CATEGORY -->

            <div class="variant-detail-section">

                <div class="variant-detail-section-title">
                    Category Structure
                </div>

                <div
                    class="variant-detail-section-value"
                    id="modalCategoryStructure"
                >
                    —
                </div>

            </div>


            <!-- PRODUCT FLAGS -->

            <div class="variant-detail-section">

                <div class="variant-detail-section-title">
                    Product Badges
                </div>

                <div
                    class="variant-detail-section-value"
                    id="modalFlags"
                >
                    —
                </div>

            </div>


            <!-- SLUG -->

            <div class="variant-detail-section">

                <div class="variant-detail-section-title">
                    Product Slug / Route
                </div>

                <div
                    class="variant-detail-section-value"
                    id="modalSlug"
                    style="font-family:monospace;"
                >
                    —
                </div>

            </div>


        </div>


        <div class="variant-modal-footer">

            <span
                style="
                    font-size:11px;
                    color:#7b8da1;
                "
            >
                Tip: Press
                <kbd class="kbd-badge">
                    Esc
                </kbd>
                to close
            </span>


            <button
                type="button"
                class="btn"
                id="variantModalClose2"
            >
                Close
            </button>

        </div>


    </div>

</div>


<!-- ==========================================================================
     DELETE MODAL
     ========================================================================== -->

<div
    class="variant-modal-backdrop"
    id="deleteModal"
    aria-hidden="true"
>

    <div class="delete-modal">


        <div class="delete-modal-header">

            <span>
                Delete Product Variant
            </span>

            <button
                type="button"
                class="variant-modal-close"
                id="deleteModalClose"
            >
                ×
            </button>

        </div>


        <div
            class="delete-modal-body"
            id="deleteModalText"
        >
            Are you sure you want to delete this variant?
        </div>


        <div class="delete-modal-footer">

            <button
                type="button"
                class="btn"
                id="deleteCancelBtn"
            >
                Cancel
            </button>


            <form
                method="POST"
                action="delete.php"
                id="deleteConfirmForm"
                style="margin:0;"
            >

                <input
                    type="hidden"
                    name="variant_id"
                    id="deleteVariantId"
                >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($csrfToken) ?>"
                >

                <button
                    type="submit"
                    class="btn-delete-confirm"
                >
                    Yes, Delete
                </button>

            </form>

        </div>

    </div>

</div>


<!-- ==========================================================================
     LIBRARIES
     ========================================================================== -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.4/jspdf.plugin.autotable.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>


<script>

/* ==========================================================================
   PAGINATION
   ========================================================================== */

function changePerPage(value)
{
    const form =
        document.getElementById('filterForm');

    const limitInput =
        document.getElementById('limitInput');

    if (!form || !limitInput) {
        return;
    }

    limitInput.value = value;

    form.submit();
}


/* ==========================================================================
   MAIN SCRIPT
   ========================================================================== */

(function () {

    'use strict';


    document.addEventListener(
        'DOMContentLoaded',
        function () {


            const searchInput =
                document.getElementById(
                    'variantSearch'
                );


            const table =
                document.getElementById(
                    'variantTable'
                );


            const variantModal =
                document.getElementById(
                    'variantModal'
                );


            const deleteModal =
                document.getElementById(
                    'deleteModal'
                );


            /*
            |--------------------------------------------------------------------------
            | TABLE ROWS
            |--------------------------------------------------------------------------
            */

            function rows()
            {
                return table
                    ? Array.from(
                        table.querySelectorAll(
                            'tbody .variant-row'
                        )
                    )
                    : [];
            }


            /*
            |--------------------------------------------------------------------------
            | EXCEL EXPORT
            |--------------------------------------------------------------------------
            */

            function excelExport()
            {

                const data =
                    rows().map(
                        function (row)
                        {

                            return {

                                'Variant ID':
                                    row.querySelector(
                                        '.order-box'
                                    )?.innerText.trim() || '',

                                'Product':
                                    row.querySelector(
                                        '.variant-product-name'
                                    )?.innerText.trim() || '',

                                'SKU':
                                    row.querySelector(
                                        '.variant-sku'
                                    )?.innerText
                                    .replace(/^SKU:\s*/i, '')
                                    .trim() || '',

                                'Category':
                                    row.querySelector(
                                        '.category-badge'
                                    )?.innerText.trim() || '',

                                'Price':
                                    row.querySelector(
                                        '.price-value'
                                    )?.innerText.trim() || '',

                                'Status':
                                    row.dataset.status === 'active'
                                        ? 'Active'
                                        : 'Inactive'

                            };

                        }
                    );


                if (!window.XLSX) {

                    alert(
                        'Excel library not available.'
                    );

                    return;
                }


                const worksheet =
                    XLSX.utils.json_to_sheet(
                        data
                    );


                const workbook =
                    XLSX.utils.book_new();


                XLSX.utils.book_append_sheet(
                    workbook,
                    worksheet,
                    'Variants'
                );


                XLSX.writeFile(
                    workbook,
                    'product-variants-' +
                    new Date()
                        .toISOString()
                        .slice(0,10) +
                    '.xlsx'
                );

            }


            /*
            |--------------------------------------------------------------------------
            | PDF EXPORT
            |--------------------------------------------------------------------------
            */

            function pdfExport()
            {

                if (
                    !window.jspdf ||
                    !window.jspdf.jsPDF
                ) {

                    alert(
                        'PDF library not available.'
                    );

                    return;
                }


                const body =
                    rows().map(
                        function (row)
                        {

                            return [

                                row.querySelector(
                                    '.order-box'
                                )?.innerText.trim() || '',

                                row.querySelector(
                                    '.variant-product-name'
                                )?.innerText.trim() || '',

                                row.querySelector(
                                    '.variant-sku'
                                )?.innerText
                                .replace(/^SKU:\s*/i, '')
                                .trim() || '',

                                row.querySelector(
                                    '.category-badge'
                                )?.innerText.trim() || '',

                                row.querySelector(
                                    '.price-value'
                                )?.innerText.trim() || '',

                                row.dataset.status === 'active'
                                    ? 'Active'
                                    : 'Inactive'

                            ];

                        }
                    );


                const doc =
                    new jspdf.jsPDF({

                        orientation:
                            'landscape',

                        unit:
                            'mm',

                        format:
                            'a4'

                    });


                doc.setFontSize(16);

                doc.text(
                    'GatewayLinen - Product Variants',
                    14,
                    14
                );


                if (
                    typeof doc.autoTable ===
                    'function'
                ) {

                    doc.autoTable({

                        startY: 22,

                        head: [[

                            'ID',
                            'Product',
                            'SKU',
                            'Category',
                            'Price',
                            'Status'

                        ]],

                        body: body,

                        styles: {

                            fontSize: 7,

                            cellPadding: 2

                        },

                        headStyles: {

                            fillColor:
                                [5,150,105]

                        }

                    });

                }


                doc.save(
                    'product-variants-' +
                    new Date()
                        .toISOString()
                        .slice(0,10) +
                    '.pdf'
                );

            }


            /*
            |--------------------------------------------------------------------------
            | VIEW MODAL
            |--------------------------------------------------------------------------
            */

            function openVariantModal(button)
            {

                document.getElementById(
                    'modalProductName'
                ).textContent =
                    button.dataset.product || '—';


                document.getElementById(
                    'modalSku'
                ).textContent =
                    'SKU: ' +
                    (
                        button.dataset.sku ||
                        '—'
                    );


                document.getElementById(
                    'modalVariantId'
                ).textContent =
                    '#' +
                    (
                        button.dataset.id ||
                        '—'
                    );


                document.getElementById(
                    'modalProductId'
                ).textContent =
                    '#' +
                    (
                        button.dataset.productid ||
                        '—'
                    );


                document.getElementById(
                    'modalStatus'
                ).textContent =
                    button.dataset.status ||
                    '—';


                document.getElementById(
                    'modalPrice'
                ).textContent =
                    '$' +
                    (
                        button.dataset.price ||
                        '0.00'
                    );


                document.getElementById(
                    'modalBasePrice'
                ).textContent =
                    '$' +
                    (
                        button.dataset.baseprice ||
                        '0.00'
                    );


                document.getElementById(
                    'modalCategory'
                ).textContent =
                    button.dataset.category ||
                    '—';


                let categoryStructure =
                    button.dataset.category || '—';


                if (
                    button.dataset.parentcategory
                ) {

                    categoryStructure =
                        button.dataset.parentcategory +
                        ' → ' +
                        categoryStructure;
                }


                document.getElementById(
                    'modalCategoryStructure'
                ).textContent =
                    categoryStructure;


                document.getElementById(
                    'modalFlags'
                ).textContent =
                    button.dataset.flags ||
                    'None';


                document.getElementById(
                    'modalSlug'
                ).textContent =
                    '/' +
                    (
                        button.dataset.slug ||
                        '—'
                    );


                variantModal.classList.add(
                    'show'
                );


                variantModal.setAttribute(
                    'aria-hidden',
                    'false'
                );

            }


            /*
            |--------------------------------------------------------------------------
            | CLOSE VIEW MODAL
            |--------------------------------------------------------------------------
            */

            function closeVariantModal()
            {

                variantModal.classList.remove(
                    'show'
                );


                variantModal.setAttribute(
                    'aria-hidden',
                    'true'
                );

            }


            /*
            |--------------------------------------------------------------------------
            | DELETE MODAL
            |--------------------------------------------------------------------------
            */

            function openDeleteModal(
                variantId,
                productName
            )
            {

                document.getElementById(
                    'deleteVariantId'
                ).value =
                    variantId;


                document.getElementById(
                    'deleteModalText'
                ).innerHTML =

                    'Are you sure you want to delete the variant of ' +

                    '<strong style="color:#162334;">' +

                    escapeHtml(
                        productName
                    ) +

                    '</strong>?' +

                    '<br><br>' +

                    'This action cannot be undone.';


                deleteModal.classList.add(
                    'show'
                );


                deleteModal.setAttribute(
                    'aria-hidden',
                    'false'
                );

            }


            /*
            |--------------------------------------------------------------------------
            | CLOSE DELETE MODAL
            |--------------------------------------------------------------------------
            */

            function closeDeleteModal()
            {

                deleteModal.classList.remove(
                    'show'
                );


                deleteModal.setAttribute(
                    'aria-hidden',
                    'true'
                );

            }


            /*
            |--------------------------------------------------------------------------
            | HTML ESCAPE
            |--------------------------------------------------------------------------
            */

            function escapeHtml(value)
            {

                const div =
                    document.createElement(
                        'div'
                    );

                div.textContent =
                    value;

                return div.innerHTML;

            }


            /*
            |--------------------------------------------------------------------------
            | VIEW BUTTONS
            |--------------------------------------------------------------------------
            */

            document
                .querySelectorAll(
                    '.detail-btn'
                )
                .forEach(
                    function(button)
                    {

                        button.addEventListener(
                            'click',
                            function(event)
                            {

                                event.stopPropagation();

                                openVariantModal(
                                    this
                                );

                            }
                        );

                    }
                );


            /*
            |--------------------------------------------------------------------------
            | DELETE BUTTONS
            |--------------------------------------------------------------------------
            */

            document
                .querySelectorAll(
                    '.delete-variant-btn'
                )
                .forEach(
                    function(button)
                    {

                        button.addEventListener(
                            'click',
                            function(event)
                            {

                                event.preventDefault();

                                const row =
                                    this.closest(
                                        '.variant-row'
                                    );


                                const name =
                                    row
                                    ?.querySelector(
                                        '.variant-product-name'
                                    )
                                    ?.innerText
                                    .trim() ||
                                    'this product';


                                const id =
                                    this
                                    .closest(
                                        '.delete-form'
                                    )
                                    ?.querySelector(
                                        'input[name="variant_id"]'
                                    )
                                    ?.value;


                                openDeleteModal(
                                    id,
                                    name
                                );

                            }
                        );

                    }
                );


            /*
            |--------------------------------------------------------------------------
            | CLOSE BUTTONS
            |--------------------------------------------------------------------------
            */

            document
                .getElementById(
                    'variantModalClose'
                )
                ?.addEventListener(
                    'click',
                    closeVariantModal
                );


            document
                .getElementById(
                    'variantModalClose2'
                )
                ?.addEventListener(
                    'click',
                    closeVariantModal
                );


            document
                .getElementById(
                    'deleteModalClose'
                )
                ?.addEventListener(
                    'click',
                    closeDeleteModal
                );


            document
                .getElementById(
                    'deleteCancelBtn'
                )
                ?.addEventListener(
                    'click',
                    closeDeleteModal
                );


            /*
            |--------------------------------------------------------------------------
            | OUTSIDE CLICK
            |--------------------------------------------------------------------------
            */

            variantModal
                ?.addEventListener(
                    'click',
                    function(event)
                    {

                        if (
                            event.target ===
                            variantModal
                        ) {

                            closeVariantModal();

                        }

                    }
                );


            deleteModal
                ?.addEventListener(
                    'click',
                    function(event)
                    {

                        if (
                            event.target ===
                            deleteModal
                        ) {

                            closeDeleteModal();

                        }

                    }
                );


            /*
            |--------------------------------------------------------------------------
            | BUTTONS
            |--------------------------------------------------------------------------
            */

            document
                .getElementById(
                    'printBtn'
                )
                ?.addEventListener(
                    'click',
                    function()
                    {
                        window.print();
                    }
                );


            document
                .getElementById(
                    'pdfBtn'
                )
                ?.addEventListener(
                    'click',
                    pdfExport
                );


            document
                .getElementById(
                    'excelBtn'
                )
                ?.addEventListener(
                    'click',
                    excelExport
                );


            /*
            |--------------------------------------------------------------------------
            | KEYBOARD SHORTCUTS
            |--------------------------------------------------------------------------
            */

            document.addEventListener(
                'keydown',
                function(event)
                {

                    const activeTag =
                        document.activeElement
                            ? document.activeElement.tagName.toLowerCase()
                            : '';


                    const isTyping =
                        activeTag === 'input' ||
                        activeTag === 'textarea' ||
                        activeTag === 'select';


                    /*
                    | ESC
                    */

                    if (
                        event.key === 'Escape'
                    ) {

                        if (
                            variantModal.classList.contains(
                                'show'
                            )
                        ) {

                            closeVariantModal();

                        }


                        if (
                            deleteModal.classList.contains(
                                'show'
                            )
                        ) {

                            closeDeleteModal();

                        }

                    }


                    /*
                    | B = SEARCH
                    */

                    if (
                        event.key.toLowerCase() === 'b' &&
                        !isTyping
                    ) {

                        event.preventDefault();

                        if (searchInput) {

                            searchInput.focus();

                            searchInput.select();

                        }

                    }


                    /*
                    | A = ADD
                    */

                    if (
                        event.key.toLowerCase() === 'a' &&
                        !isTyping
                    ) {

                        event.preventDefault();

                        const addBtn =
                            document.getElementById(
                                'addVariantBtn'
                            );

                        if (addBtn) {

                            window.location.href =
                                addBtn.href;

                        }

                    }


                    /*
                    | P = PRINT
                    */

                    if (
                        event.key.toLowerCase() === 'p' &&
                        !isTyping
                    ) {

                        event.preventDefault();

                        window.print();

                    }


                    /*
                    | V = PDF
                    */

                    if (
                        event.key.toLowerCase() === 'v' &&
                        !isTyping
                    ) {

                        event.preventDefault();

                        pdfExport();

                    }


                    /*
                    | X = EXCEL
                    */

                    if (
                        event.key.toLowerCase() === 'x' &&
                        !isTyping
                    ) {

                        event.preventDefault();

                        excelExport();

                    }

                }
            );

        }
    );

})();

</script>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>