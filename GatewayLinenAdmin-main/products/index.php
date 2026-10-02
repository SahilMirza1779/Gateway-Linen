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

$activeMenu = 'products';
$pageTitle  = 'GatewayLinen | Products';

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
| CSRF TOKEN FOR DELETE
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['product_delete_token'])) {
    $_SESSION['product_delete_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['product_delete_token'];

/*
|--------------------------------------------------------------------------
| SUCCESS / ERROR MESSAGES
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
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
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

    return trim((string)$value);
}

/*
|--------------------------------------------------------------------------
| PRODUCT IMAGE URL HELPER
|--------------------------------------------------------------------------
*/

function productImageUrl($value): string
{
    $value = trim((string)$value);

    if ($value === '') {
        return '';
    }

    if (preg_match('~^(https?:)?//|^data:image/~i', $value)) {
        return $value;
    }

    $value = str_replace('\\', '/', $value);
    $path = parse_url($value, PHP_URL_PATH);
    $file = basename($path ?: $value);

    if ($file === '' || $file === '.' || $file === '..') {
        return '';
    }

    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/products/index.php');
    $appRoot = dirname(dirname($script));
    $appRoot = trim(str_replace('\\', '/', $appRoot), '/');

    $prefix = ($appRoot === '' || $appRoot === '.') ? '' : '/' . $appRoot;

    return $prefix . '/uploads/products/' . rawurlencode($file);
}

/*
|--------------------------------------------------------------------------
| FETCH MAIN CATEGORIES & SUBCATEGORIES FOR FILTER DROPDOWN
|--------------------------------------------------------------------------
*/

$mainCategories = [];
$mainCatSql = "SELECT CategoryId, Name FROM dbo.Categories WHERE ParentCategoryId IS NULL ORDER BY Name ASC";
$mainCatStmt = sqlsrv_query($conn, $mainCatSql);

if ($mainCatStmt !== false) {
    while ($row = sqlsrv_fetch_array($mainCatStmt, SQLSRV_FETCH_ASSOC)) {
        $mainCatId = $row['CategoryId'];
        $mainCatName = $row['Name'];

        $subCats = [];
        $subSql = "SELECT CategoryId, Name FROM dbo.Categories WHERE ParentCategoryId = ? ORDER BY Name ASC";
        $subStmt = sqlsrv_query($conn, $subSql, [$mainCatId]);
        if ($subStmt !== false) {
            while ($subRow = sqlsrv_fetch_array($subStmt, SQLSRV_FETCH_ASSOC)) {
                $subCats[] = $subRow;
            }
            sqlsrv_free_stmt($subStmt);
        }

        $mainCategories[] = [
            'id' => $mainCatId,
            'name' => $mainCatName,
            'subs' => $subCats
        ];
    }
    sqlsrv_free_stmt($mainCatStmt);
}

/*
|--------------------------------------------------------------------------
| SEARCH, FILTER & PAGINATION PARAMETERS
|--------------------------------------------------------------------------
*/

$searchQuery    = trim((string)($_GET['q'] ?? ''));
$categoryFilter = trim((string)($_GET['category'] ?? 'all'));
$statusFilter   = trim((string)($_GET['status'] ?? 'all'));
$page           = max(1, (int)($_GET['page'] ?? 1));
$perPage        = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
if (!in_array($perPage, [25, 50, 100, 200])) {
    $perPage = 50;
}
$offset         = ($page - 1) * $perPage;

/*
|--------------------------------------------------------------------------
| BUILD SQL QUERY WITH FILTERS & PAGINATION
|--------------------------------------------------------------------------
*/

$whereClauses = ["1=1"];
$params = [];

if ($searchQuery !== '') {
    $whereClauses[] = "(p.Name LIKE ? OR p.Slug LIKE ? OR p.Specifications LIKE ? OR p.Description LIKE ?)";
    $like = '%' . $searchQuery . '%';
    array_push($params, $like, $like, $like, $like);
}

if ($categoryFilter !== 'all') {
    if (strpos($categoryFilter, 'main-') === 0) {
        $mainCatId = (int)str_replace('main-', '', $categoryFilter);
        $whereClauses[] = "c.ParentCategoryId = ?";
        $params[] = $mainCatId;
    } else {
        $whereClauses[] = "LOWER(c.Name) = ?";
        $params[] = strtolower($categoryFilter);
    }
}

if ($statusFilter === 'active') {
    $whereClauses[] = "p.IsActive = 1";
} elseif ($statusFilter === 'inactive') {
    $whereClauses[] = "p.IsActive = 0";
}

$whereSql = implode(" AND ", $whereClauses);

// 1. Count Total Matching Products
$countSql = "SELECT COUNT(*) AS Total FROM dbo.Products p LEFT JOIN dbo.Categories c ON p.CategoryId = c.CategoryId WHERE $whereSql";
$countStmt = sqlsrv_query($conn, $countSql, $params);
$totalProducts = 0;
if ($countStmt !== false) {
    $countRow = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC);
    $totalProducts = (int)($countRow['Total'] ?? 0);
    sqlsrv_free_stmt($countStmt);
}

$totalPages = max(1, ceil($totalProducts / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

// 2. Fetch Paginated Products using SQL Server OFFSET ... FETCH
$sql = "
    SELECT
        p.ProductId,
        p.CategoryId,
        p.Name,
        p.Slug,
        p.ShortDescription,
        p.Description,
        p.CareInstructions,
        p.Specifications,
        p.BasePrice,
        p.GstPercentage,
        p.PstPercentage,
        p.MetaTitle,
        p.MetaDescription,
        p.IsFeatured,
        p.IsNewArrival,
        p.IsBestSeller,
        p.IsActive,
        p.CreatedAt,
        c.Name AS CategoryName,
        c.ParentCategoryId,
        img.ImageUrl AS MainImage,
        (
            SELECT ImageUrl + '|' 
            FROM dbo.ProductImages 
            WHERE ProductId = p.ProductId 
            ORDER BY IsMain DESC, DisplayOrder ASC 
            FOR XML PATH('')
        ) AS AllImages
    FROM dbo.Products p
    LEFT JOIN dbo.Categories c ON p.CategoryId = c.CategoryId
    OUTER APPLY (
        SELECT TOP 1 ImageUrl 
        FROM dbo.ProductImages 
        WHERE ProductId = p.ProductId 
        ORDER BY IsMain DESC, DisplayOrder ASC
    ) img
    WHERE $whereSql
    ORDER BY p.ProductId ASC
    OFFSET ? ROWS FETCH NEXT ? ROWS ONLY
";

$queryParams = $params;
$queryParams[] = $offset;
$queryParams[] = $perPage;

$stmt = sqlsrv_query($conn, $sql, $queryParams);
$allProducts = [];
$queryError = '';

if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $allProducts[] = $row;
    }
    sqlsrv_free_stmt($stmt);
} else {
    $queryError = 'Unable to load products from database.';
}

/*
|--------------------------------------------------------------------------
| GLOBAL STATISTICS
|--------------------------------------------------------------------------
*/
$statSql = "SELECT 
    COUNT(*) AS Total,
    SUM(CASE WHEN IsActive = 1 THEN 1 ELSE 0 END) AS ActiveCount,
    SUM(CASE WHEN IsActive = 0 THEN 1 ELSE 0 END) AS InactiveCount,
    SUM(CASE WHEN IsFeatured = 1 THEN 1 ELSE 0 END) AS FeaturedCount
    FROM dbo.Products";
$statStmt = sqlsrv_query($conn, $statSql);
$stats = ['Total' => 0, 'ActiveCount' => 0, 'InactiveCount' => 0, 'FeaturedCount' => 0];
if ($statStmt !== false) {
    $stats = sqlsrv_fetch_array($statStmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($statStmt);
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
    .category-page,
    .category-page * { box-sizing: border-box; }

    .category-page {
        width: 100%;
        max-width: 1600px;
        margin: 0 auto;
        padding: 0;
        font-size: 13px;

        --cat-page: #f3f6fa;
        --cat-card: #ffffff;
        --cat-card-alt: #f8fafc;
        --cat-input: #ffffff;
        --cat-border: #dce4ec;
        --cat-border-soft: #e8edf3;
        --cat-text: #162334;
        --cat-body: #536579;
        --cat-muted: #7b8da1;
        --cat-green: #059669;
        --cat-green-soft: rgba(5,150,105,.10);
        --cat-red: #dc2626;
        --cat-red-soft: rgba(220,38,38,.09);
        --cat-blue: #0284c7;
        --cat-blue-soft: rgba(2,132,199,.09);
        --cat-amber: #d97706;
        --cat-amber-soft: rgba(217,119,6,.10);
        --cat-purple: #7c3aed;
        --cat-purple-soft: rgba(124,58,237,.10);
        --cat-shadow: 0 5px 18px rgba(15,23,42,.05);
    }

    html[data-theme="dark"] .category-page,
    body[data-theme="dark"] .category-page,
    html.dark .category-page,
    body.dark .category-page,
    html.dark-mode .category-page,
    body.dark-mode .category-page {
        --cat-page: #0a1119;
        --cat-card: #111b26;
        --cat-card-alt: #0f1823;
        --cat-input: #0d1620;
        --cat-border: #1e2d3d;
        --cat-border-soft: #182636;
        --cat-text: #f0f4f8;
        --cat-body: #a8b8c8;
        --cat-muted: #6f8295;
        --cat-green: #10b981;
        --cat-green-soft: rgba(16,185,129,.12);
        --cat-red: #ef4444;
        --cat-red-soft: rgba(239,68,68,.12);
        --cat-blue: #38bdf8;
        --cat-blue-soft: rgba(56,189,248,.12);
        --cat-amber: #f59e0b;
        --cat-amber-soft: rgba(245,158,11,.15);
        --cat-purple: #a855f7;
        --cat-purple-soft: rgba(168,85,247,.15);
        --cat-shadow: none;
    }

    .category-page { background: var(--cat-page); color: var(--cat-body); }

    .category-page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 28px;
        margin: 0 0 18px;
        padding: 0 0 16px;
        border-bottom: 1px solid var(--cat-border);
    }

    .category-breadcrumb {
        display: flex;
        align-items: center;
        gap: 9px;
        margin-bottom: 7px;
        color: var(--cat-muted);
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .45px;
    }
    .category-breadcrumb .current { color: var(--cat-green); }

    .category-page-header h1 { margin: 0; color: var(--cat-text); font-size: 30px; font-weight: 900; letter-spacing: -.5px; }
    .category-page-header p { margin: 7px 0 0; color: var(--cat-muted); font-size: 13px; font-weight: 600; }

    .header-actions { display: flex; align-items: center; gap: 10px; }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-height: 40px;
        padding: 0 14px;
        border: 1px solid var(--cat-border);
        border-radius: 9px;
        background: var(--cat-card);
        color: var(--cat-text) !important;
        font-size: 12px;
        font-weight: 800;
        text-decoration: none;
        cursor: pointer;
        transition: .16s ease;
        position: relative;
    }
    .btn:hover { border-color: var(--cat-green); background: var(--cat-green-soft); color: var(--cat-green) !important; }

    .kbd-badge {
        font-size: 9px;
        background: rgba(0,0,0,0.08);
        border: 1px solid rgba(0,0,0,0.12);
        padding: 1px 5px;
        border-radius: 4px;
        color: inherit;
        margin-left: 4px;
        font-family: monospace;
    }

    .btn-primary { 
        border-color: transparent; 
        background: linear-gradient(135deg,#059669,#10b981); 
        color: #fff !important; 
        box-shadow: 0 5px 14px rgba(16,185,129,.16); 
    }
    .btn-primary:hover { 
        background: linear-gradient(135deg,#047857,#059669); 
        color: #fff !important; 
    }
    .btn-primary .kbd-badge {
        background: rgba(255,255,255,0.2);
        border-color: rgba(255,255,255,0.3);
        color: #fff;
    }

    .category-stats { display: grid; grid-template-columns: repeat(4,minmax(0,1fr)); gap: 14px; margin-bottom: 16px; }
    .category-stat-item {
        display: flex; align-items: center; gap: 13px; min-height: 82px; padding: 14px 17px;
        background: var(--cat-card); border: 1px solid var(--cat-border); border-radius: 11px; box-shadow: var(--cat-shadow);
    }
    .category-stat-icon {
        display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; flex: 0 0 40px;
        border-radius: 10px; background: var(--cat-green-soft); color: var(--cat-green); font-size: 17px; font-weight: 900;
    }
    .category-stat-label { color: var(--cat-muted); font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .45px; }
    .category-stat-value { margin-top: 4px; color: var(--cat-text); font-size: 24px; font-weight: 900; line-height: 1; }

    .notice { margin-bottom: 12px; padding: 12px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; }
    .notice-success { border: 1px solid rgba(5,150,105,.25); background: var(--cat-green-soft); color: var(--cat-green); }
    .notice-error { border: 1px solid rgba(220,38,38,.25); background: var(--cat-red-soft); color: var(--cat-red); }

    .category-content { background: var(--cat-card); border: 1px solid var(--cat-border); border-radius: 12px; overflow: hidden; box-shadow: var(--cat-shadow); }
    .category-content-header { display: flex; align-items: center; justify-content: space-between; gap: 28px; padding: 20px 24px; min-height: 94px; border-bottom: 1px solid var(--cat-border); }
    .category-content-title h2 { margin: 0; color: var(--cat-text); font-size: 20px; font-weight: 900; }
    .category-content-title p { margin: 5px 0 0; color: var(--cat-muted); font-size: 11px; font-weight: 600; line-height: 1.5; }

    .category-filters { display: flex; align-items: center; gap: 10px; flex: 1 1 auto; justify-content: flex-end; }
    .category-search-wrap { position: relative; width: min(420px, 100%); flex: 1 1 320px; }
    .category-search-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--cat-muted); pointer-events: none; font-size: 17px; }

    .category-search, .category-status-filter {
        height: 46px; border: 1px solid var(--cat-border); border-radius: 9px; outline: none; background: var(--cat-input); color: var(--cat-text); font-size: 13px; font-weight: 600;
    }
    .category-search { width: 100%; padding: 0 45px 0 44px; }
    .category-status-filter { min-width: 170px; padding: 0 14px; }
    .category-search::placeholder { color: var(--cat-muted); font-size: 13px; font-weight: 500; }
    .category-search:focus, .category-status-filter:focus { border-color: var(--cat-green); box-shadow: 0 0 0 3px var(--cat-green-soft); }

    .search-kbd-hint {
        position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
        font-size: 10px; color: var(--cat-muted); background: var(--cat-card-alt); border: 1px solid var(--cat-border);
        padding: 2px 5px; border-radius: 4px; pointer-events: none; font-family: monospace;
    }

    .category-table-summary { display: flex; align-items: center; justify-content: space-between; padding: 12px 24px; border-bottom: 1px solid var(--cat-border); }
    .category-result-text { color: var(--cat-muted); font-size: 11px; font-weight: 700; }
    .category-result-text strong { color: var(--cat-text); }

    .category-table-wrapper { width: 100%; overflow-x: visible; }
    .category-table { width: 100%; border-collapse: collapse; }
    .category-table th {
        height: 46px; padding: 0 16px; background: var(--cat-card-alt); border-bottom: 1px solid var(--cat-border);
        color: var(--cat-muted); font-size: 10px; font-weight: 900; text-align: left; text-transform: uppercase; letter-spacing: .55px; white-space: nowrap;
    }
    .category-table td { padding: 14px 16px; background: transparent; border-bottom: 1px solid var(--cat-border-soft); color: var(--cat-body); font-size: 12px; line-height: 1.45; vertical-align: middle; }
    .category-table tbody tr { cursor: pointer; }
    .category-table tbody tr:hover { background: var(--cat-green-soft); }

    .order-box {
        display: inline-flex; align-items: center; justify-content: center; min-width: 34px; height: 28px; padding: 0 8px;
        border-radius: 7px; background: var(--cat-input); border: 1px solid var(--cat-border); color: var(--cat-green); font-size: 11px; font-weight: 900;
    }

    .category-main { display: flex; align-items: center; gap: 11px; width: 100%; }
    .category-image {
        display: flex; align-items: center; justify-content: center; width: 52px; height: 52px; flex: 0 0 52px;
        overflow: hidden; border: 1px solid var(--cat-border); border-radius: 9px; background: var(--cat-card-alt);
    }
    .category-image img { width: 100%; height: 100%; display: block; object-fit: cover; }
    .category-image-placeholder { display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; color: var(--cat-muted); font-size: 15px; }
    .category-name { color: var(--cat-text); font-size: 13px; font-weight: 900; display: flex; align-items: center; }
    .category-slug { margin-top: 3px; color: var(--cat-muted); font-size: 10px; font-family: monospace; }
    
    .cat-badge {
        display: inline-block; padding: 4px 9px; border-radius: 6px; background: var(--cat-blue-soft); color: var(--cat-blue); font-size: 11px; font-weight: 800; white-space: nowrap;
    }

    .flags-cell { display: flex; gap: 5px; flex-wrap: wrap; }
    .badge-tag { font-size: 9px; font-weight: 900; padding: 3px 7px; border-radius: 5px; text-transform: uppercase; letter-spacing: .3px; }
    .tag-featured { background: var(--cat-amber-soft); color: var(--cat-amber); border: 1px solid rgba(217,119,6,.25); }
    .tag-new { background: var(--cat-blue-soft); color: var(--cat-blue); border: 1px solid rgba(2,132,199,.25); }
    .tag-bestseller { background: var(--cat-purple-soft); color: var(--cat-purple); border: 1px solid rgba(124,58,237,.25); }

    .price-value { color: var(--cat-text); font-weight: 900; font-size: 12px; }

    .category-status { display: inline-flex; align-items: center; gap: 6px; min-height: 26px; padding: 0 10px; border-radius: 20px; font-size: 10px; font-weight: 800; white-space: nowrap; }
    .category-status-dot { width: 5px; height: 5px; border-radius: 50%; }
    .category-status-active { background: var(--cat-green-soft); color: var(--cat-green); }
    .category-status-active .category-status-dot { background: var(--cat-green); box-shadow: 0 0 6px var(--cat-green); }
    .category-status-inactive { background: var(--cat-red-soft); color: var(--cat-red); }
    .category-status-inactive .category-status-dot { background: var(--cat-red); }

    .category-date { color: var(--cat-muted); font-size: 10px; line-height: 1.45; white-space: nowrap; }

    .category-actions { display: flex; align-items: center; gap: 4px; flex-wrap: nowrap; }
    .category-action {
        display: inline-flex; align-items: center; justify-content: center; gap: 3px; width: 32px; height: 32px;
        border: 1px solid var(--cat-border); border-radius: 7px; background: var(--cat-card); color: var(--cat-body) !important;
        text-decoration: none; cursor: pointer; font-size: 12px;
    }
    .category-action:hover { border-color: var(--cat-green); background: var(--cat-green-soft); color: var(--cat-green) !important; }
    .category-action-delete:hover { border-color: rgba(220,38,38,.4); background: var(--cat-red-soft); color: var(--cat-red) !important; }

    .category-empty { padding: 65px 20px; text-align: center; }
    .category-empty-icon { margin-bottom: 12px; color: var(--cat-green); font-size: 32px; }
    .category-empty h3 { margin: 0; color: var(--cat-text); font-size: 16px; font-weight: 900; }
    .category-empty p { margin: 6px 0 0; color: var(--cat-muted); font-size: 11px; }

    /* PAGINATION BAR */
    .pagination-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 24px;
        border-top: 1px solid var(--cat-border);
        background: var(--cat-card-alt);
    }
    .pagination-limit-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        font-weight: 700;
        color: var(--cat-muted);
    }
    .pagination-limit-select {
        height: 36px;
        padding: 0 10px;
        border: 1px solid var(--cat-border);
        border-radius: 8px;
        background: var(--cat-card);
        color: var(--cat-text);
        font-size: 12px;
        font-weight: 800;
        outline: none;
        cursor: pointer;
    }
    .pagination-limit-select:focus {
        border-color: var(--cat-green);
        box-shadow: 0 0 0 3px var(--cat-green-soft);
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
        padding: 0 9px;
        border: 1px solid var(--cat-border);
        border-radius: 6px;
        background: var(--cat-card);
        color: var(--cat-text);
        font-weight: 700;
        text-decoration: none;
        font-size: 11px;
    }
    .page-link:hover, .page-link.active {
        background: var(--cat-green);
        border-color: var(--cat-green);
        color: #fff !important;
    }

    /* MODAL */
    .modal-backdrop {
        position: fixed; inset: 0; z-index: 99999; display: none; align-items: center; justify-content: center;
        padding: 20px; background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(4px);
    }
    .modal-backdrop.show { display: flex; }

    .category-modal {
        width: min(850px, 100%); max-height: 90vh; overflow-y: auto; 
        background: #ffffff !important; color: #162334 !important;
        border: 1px solid #dce4ec; border-radius: 14px; box-shadow: 0 25px 75px rgba(0, 0, 0, 0.50);
        display: flex; flex-direction: column;
    }

    .modal-header { display: flex; align-items: center; justify-content: space-between; padding: 18px 24px; border-bottom: 1px solid #dce4ec; background: #f8fafc !important; }
    .modal-header h3 { margin: 0; color: #162334 !important; font-size: 18px; font-weight: 900; }
    .modal-close { border: 0; background: transparent; color: #7b8da1; font-size: 26px; font-weight: 700; cursor: pointer; transition: color .15s; }
    .modal-close:hover { color: #dc2626; }

    .modal-body { padding: 28px; overflow-y: auto; background: #ffffff !important; color: #162334 !important; }

    .detail-grid { display: grid; grid-template-columns: 240px 1fr; gap: 24px; align-items: flex-start; }
    .detail-image-box { display: flex; flex-direction: column; gap: 12px; }
    .detail-main-image {
        width: 240px; height: 240px; border-radius: 12px; overflow: hidden; border: 1px solid #dce4ec;
        background: #f8fafc; display: flex; align-items: center; justify-content: center; color: #7b8da1; font-size: 40px;
    }
    .detail-main-image img { width: 100%; height: 100%; object-fit: cover; }
    .detail-thumbnails { display: flex; gap: 8px; flex-wrap: wrap; max-height: 90px; overflow-y: auto; }
    .thumb-img {
        width: 52px; height: 52px; border-radius: 8px; border: 1px solid #dce4ec; object-fit: cover; cursor: pointer; opacity: 0.6; transition: 0.2s;
    }
    .thumb-img:hover, .thumb-img.active { opacity: 1; border-color: #059669; }

    .detail-info-grid { display: flex; flex-direction: column; gap: 14px; }
    .detail-item { display: flex; flex-direction: column; gap: 5px; }
    .detail-item.full-width { grid-column: 1 / -1; }
    .detail-item label { color: #7b8da1 !important; font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: .5px; }
    .detail-item .val { color: #162334 !important; font-size: 13px; font-weight: 700; word-break: break-word; }

    .detail-meta { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 4px; }
    .detail-card { padding: 10px 12px; border: 1px solid #e8edf3; border-radius: 8px; background: #f8fafc; }

    .modal-footer { display: flex; align-items: center; justify-content: space-between; padding: 14px 24px; border-top: 1px solid #dce4ec; background: #f8fafc !important; }
    .modal-footer .btn { background: #ffffff !important; color: #162334 !important; border-color: #dce4ec !important; }
    .modal-footer .btn:hover { background: rgba(5,150,105,.10) !important; color: #059669 !important; border-color: #059669 !important; }

    @media(max-width: 768px) {
        .detail-grid { grid-template-columns: 1fr; }
        .detail-main-image { width: 100%; height: 240px; }
        .detail-meta { grid-template-columns: 1fr; }
    }
</style>

<main class="main">
    <section class="content">
        <div class="category-page">

            <!-- PAGE HEADER WITH SHORTCUT BADGES -->
            <div class="category-page-header">
                <div>
                    <div class="category-breadcrumb">
                        <span>Catalog</span><span>/</span><span class="current">Products</span>
                    </div>
                    <h1>Products Management</h1>
                    <p>Manage product details, inventory, status and reports with shortcut keys.</p>
                </div>

                <div class="header-actions">
                    <button type="button" class="btn" id="printBtn" title="Print Catalog">🖨 Print <span class="kbd-badge">P</span></button>
                    <button type="button" class="btn" id="pdfBtn" title="Export Current View to PDF">↓ PDF <span class="kbd-badge">V</span></button>
                    <button type="button" class="btn" id="excelBtn" title="Export Current View to Excel">↓ Excel <span class="kbd-badge">X</span></button>
                    <a href="add.php" class="btn btn-primary" id="addProductBtn" title="Add New Product">＋ Add Product <span class="kbd-badge">A</span></a>
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

            <!-- STATISTICS CARDS -->
            <div class="category-stats">
                <div class="category-stat-item"><div class="category-stat-icon">#</div><div><div class="category-stat-label">Total Inventory</div><div class="category-stat-value"><?= (int)$stats['Total'] ?></div></div></div>
                <div class="category-stat-item"><div class="category-stat-icon">✓</div><div><div class="category-stat-label">Active</div><div class="category-stat-value"><?= (int)$stats['ActiveCount'] ?></div></div></div>
                <div class="category-stat-item"><div class="category-stat-icon">○</div><div><div class="category-stat-label">Inactive</div><div class="category-stat-value"><?= (int)$stats['InactiveCount'] ?></div></div></div>
                <div class="category-stat-item"><div class="category-stat-icon">★</div><div><div class="category-stat-label">Featured</div><div class="category-stat-value"><?= (int)$stats['FeaturedCount'] ?></div></div></div>
            </div>

            <!-- PRODUCT CONTENT & TABLE -->
            <div class="category-content">
                <div class="category-content-header">
                    <div class="category-content-title">
                        <h2>Products List</h2>
                        <p>Name, slug, specifications, pricing, status and created date.</p>
                    </div>

                    <div class="category-filters">
                        <form method="GET" action="" id="filterForm" style="display: flex; gap: 10px; width: 100%; justify-content: flex-end; align-items: center;">
                            <input type="hidden" name="limit" id="limitInput" value="<?= $perPage ?>">
                            <div class="category-search-wrap">
                                <span class="category-search-icon">⌕</span>
                                <input type="search" name="q" id="productSearch" class="category-search" placeholder="Search name, slug, specs... (B)" value="<?= e($searchQuery) ?>" autocomplete="off">
                                <span class="search-kbd-hint">B</span>
                            </div>

                            <select name="category" id="categoryFilter" class="category-status-filter" title="Filter by Category" onchange="document.getElementById('filterForm').submit();">
                                <option value="all">All Categories</option>
                                <?php foreach ($mainCategories as $main): ?>
                                    <option value="main-<?= (int)$main['id'] ?>" <?= $categoryFilter === 'main-' . $main['id'] ? 'selected' : '' ?> style="font-weight: bold;">📁 <?= e($main['name']) ?></option>
                                    <?php foreach ($main['subs'] as $sub): ?>
                                        <option value="<?= e(strtolower($sub['Name'])) ?>" <?= $categoryFilter === strtolower($sub['Name']) ? 'selected' : '' ?>>&nbsp;&nbsp;&nbsp;&nbsp;— <?= e($sub['Name']) ?></option>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </select>

                            <select name="status" id="productStatusFilter" class="category-status-filter" onchange="document.getElementById('filterForm').submit();">
                                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Status</option>
                                <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </form>
                    </div>
                </div>

                <!-- TABLE SUMMARY -->
                <div class="category-table-summary">
                    <div class="category-result-text">Showing items <strong><?= $totalProducts > 0 ? $offset + 1 : 0 ?></strong> to <strong><?= min($offset + $perPage, $totalProducts) ?></strong> of <strong><?= $totalProducts ?></strong> results</div>
                    <div class="category-result-text">Total Catalog: <strong><?= $totalProducts ?></strong></div>
                </div>

                <!-- TABLE WRAPPER -->
                <div class="category-table-wrapper">
                    <?php if (empty($allProducts)): ?>
                        <div class="category-empty">
                            <div class="category-empty-icon">📦</div>
                            <h3>No Products Found</h3>
                            <p>Try clearing your search query or adjusting your filters.</p>
                            <a href="index.php" class="btn btn-primary" style="margin-top:16px">Reset Filters</a>
                        </div>
                    <?php else: ?>
                        <table class="category-table" id="productTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Price (Base + Tax)</th>
                                    <th>Tax Breakdown</th>
                                    <th>Badges</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php 
                            $displayId = $offset + 1;
                            foreach ($allProducts as $product): 
                                $productId    = (int)($product['ProductId'] ?? 0);
                                $name         = (string)($product['Name'] ?? '');
                                $slug         = (string)($product['Slug'] ?? '');
                                $catName      = (string)($product['CategoryName'] ?? 'Uncategorized');
                                $shortDesc    = trim((string)($product['ShortDescription'] ?? ''));
                                $desc         = trim((string)($product['Description'] ?? ''));
                                $specs        = trim((string)($product['Specifications'] ?? ''));
                                $care         = trim((string)($product['CareInstructions'] ?? ''));
                                $basePrice    = (float)($product['BasePrice'] ?? 0);
                                $gstPercent   = (float)($product['GstPercentage'] ?? 0);
                                $pstPercent   = (float)($product['PstPercentage'] ?? 0);
                                
                                $gstAmount    = $basePrice * ($gstPercent / 100);
                                $pstAmount    = $basePrice * ($pstPercent / 100);
                                $totalPrice   = $basePrice + $gstAmount + $pstAmount;

                                $metaTitle    = (string)($product['MetaTitle'] ?? '');
                                $metaDesc     = (string)($product['MetaDescription'] ?? '');
                                $isActive     = !empty($product['IsActive']);
                                $isFeatured   = !empty($product['IsFeatured']);
                                $isNew        = !empty($product['IsNewArrival']);
                                $isBestSeller = !empty($product['IsBestSeller']);
                                $image        = productImageUrl($product['MainImage'] ?? '');
                                
                                $rawImages = explode('|', (string)($product['AllImages'] ?? ''));
                                $formattedImages = [];
                                foreach ($rawImages as $imgFile) {
                                    $url = productImageUrl($imgFile);
                                    if ($url !== '') {
                                        $formattedImages[] = $url;
                                    }
                                }
                                $allImagesJson = htmlspecialchars(json_encode($formattedImages), ENT_QUOTES, 'UTF-8');
                                $createdAt    = dateValue($product['CreatedAt'] ?? '');
                                ?>
                                <tr class="category-row product-row"
                                    data-status="<?= $isActive ? 'active' : 'inactive' ?>"
                                    data-slug="<?= e(strtolower($slug)) ?>">

                                    <td><span class="order-box">#<?= $displayId++ ?></span></td>
                                    <td>
                                        <div class="category-main">
                                            <div class="category-image" title="<?= e($name) ?>">
                                                <?php if ($image !== ''): ?>
                                                    <img src="<?= e($image) ?>" alt="<?= e($name) ?>" loading="lazy" onerror="this.onerror=null;this.style.display='none';this.parentElement.querySelector('.category-image-placeholder').style.display='flex';">
                                                    <span class="category-image-placeholder" style="display:none">📦</span>
                                                <?php else: ?><span class="category-image-placeholder">📦</span><?php endif; ?>
                                            </div>
                                            <div>
                                                <div class="category-name"><?= e($name) ?></div>
                                                <?php if ($slug !== ''): ?><div class="category-slug">/<?= e($slug) ?></div><?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="cat-badge"><?= e($catName) ?></span></td>
                                    <td>
                                        <div class="price-value">$<?= number_format($totalPrice, 2) ?></div>
                                        <div style="font-size:10px; color:var(--cat-muted);">Base: $<?= number_format($basePrice, 2) ?></div>
                                    </td>
                                    <td>
                                        <div style="font-size:11px; line-height:1.4;">
                                            <div>GST (<?= number_format($gstPercent, 1) ?>%): +$<?= number_format($gstAmount, 2) ?></div>
                                            <div style="color:var(--cat-muted);">PST (<?= number_format($pstPercent, 1) ?>%): +$<?= number_format($pstAmount, 2) ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flags-cell">
                                            <?php if ($isFeatured): ?><span class="badge-tag tag-featured">Featured</span><?php endif; ?>
                                            <?php if ($isNew): ?><span class="badge-tag tag-new">New</span><?php endif; ?>
                                            <?php if ($isBestSeller): ?><span class="badge-tag tag-bestseller">Best Seller</span><?php endif; ?>
                                            <?php if (!$isFeatured && !$isNew && !$isBestSeller): ?><span style="color:var(--cat-muted);">—</span><?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($isActive): ?>
                                            <span class="category-status category-status-active"><span class="category-status-dot"></span>Active</span>
                                        <?php else: ?>
                                            <span class="category-status category-status-inactive"><span class="category-status-dot"></span>Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><div class="category-date"><?= e($createdAt) ?></div></td>
                                    <td style="text-align:right;">
                                        <div class="category-actions" style="justify-content:flex-end;">
                                            <button type="button" class="category-action detail-btn" title="View details"
                                                data-id="<?= $productId ?>"
                                                data-name="<?= e($name) ?>"
                                                data-slug="<?= e($slug) ?>"
                                                data-category="<?= e($catName) ?>"
                                                data-price="$<?= number_format($totalPrice, 2) ?> (Base: $<?= number_format($basePrice, 2) ?>)"
                                                data-gst="<?= number_format($gstPercent, 1) ?>% (+$<?= number_format($gstAmount, 2) ?>)"
                                                data-pst="<?= number_format($pstPercent, 1) ?>% (+$<?= number_format($pstAmount, 2) ?>)"
                                                data-shortdesc="<?= e($shortDesc) ?>"
                                                data-description="<?= e($desc) ?>"
                                                data-specs="<?= e($specs) ?>"
                                                data-care="<?= e($care) ?>"
                                                data-metatitle="<?= e($metaTitle) ?>"
                                                data-metadesc="<?= e($metaDesc) ?>"
                                                data-status="<?= $isActive ? 'Active' : 'Inactive' ?>"
                                                data-flags="<?= trim(($isFeatured ? 'Featured ' : '') . ($isNew ? 'NewArrival ' : '') . ($isBestSeller ? 'BestSeller' : '')) ?>"
                                                data-created="<?= e($createdAt) ?>"
                                                data-images="<?= $allImagesJson ?>">◉</button>

                                            <a href="edit.php?id=<?= $productId ?>" class="category-action edit-btn" title="Edit Product">✎</a>

                                            <form method="POST" action="delete.php" class="delete-form" style="display:inline">
                                                <input type="hidden" name="product_id" value="<?= $productId ?>">
                                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                <button type="submit" class="category-action category-action-delete delete-product-btn" title="Delete Product">×</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- PAGINATION FOOTER WITH 'SHOW X PRODUCTS' DROPDOWN -->
                <div class="pagination-bar">
                    <div class="pagination-limit-wrap">
                        <span>Show</span>
                        <select id="perPageSelect" class="pagination-limit-select" onchange="changePerPage(this.value);">
                            <option value="25" <?= $perPage === 25 ? 'selected' : '' ?>>25</option>
                            <option value="50" <?= $perPage === 50 ? 'selected' : '' ?>>50</option>
                            <option value="100" <?= $perPage === 100 ? 'selected' : '' ?>>100</option>
                            <option value="200" <?= $perPage === 200 ? 'selected' : '' ?>>200</option>
                        </select>
                        <span>products</span>
                    </div>

                    <div class="pagination-links">
                        <?php 
                        $queryString = $_GET;
                        if ($page > 1) {
                            $queryString['page'] = 1;
                            echo '<a href="?' . http_build_query($queryString) . '" class="page-link">«</a>';
                            $queryString['page'] = $page - 1;
                            echo '<a href="?' . http_build_query($queryString) . '" class="page-link">‹</a>';
                        } else {
                            echo '<span class="page-link" style="opacity:0.4; pointer-events:none;">«</span>';
                            echo '<span class="page-link" style="opacity:0.4; pointer-events:none;">‹</span>';
                        }

                        if ($page < $totalPages) {
                            $queryString['page'] = $page + 1;
                            echo '<a href="?' . http_build_query($queryString) . '" class="page-link">›</a>';
                            $queryString['page'] = $totalPages;
                            echo '<a href="?' . http_build_query($queryString) . '" class="page-link">»</a>';
                        } else {
                            echo '<span class="page-link" style="opacity:0.4; pointer-events:none;">›</span>';
                            echo '<span class="page-link" style="opacity:0.4; pointer-events:none;">»</span>';
                        }
                        ?>
                    </div>
                </div>
            </div>

        </div>
    </section>
</main>

<!-- PRODUCT DETAILS MODAL -->
<div class="modal-backdrop" id="productModal" aria-hidden="true">
    <div class="category-modal" role="dialog" aria-modal="true" aria-labelledby="productModalTitle">
        <div class="modal-header">
            <h3 id="productModalTitle">Product Details</h3>
            <button type="button" class="modal-close" id="modalCloseBtn">×</button>
        </div>

        <div class="modal-body">
            <div class="detail-grid">
                <div class="detail-image-box">
                    <div class="detail-main-image" id="modalImageBox">📦</div>
                    <div class="detail-thumbnails" id="modalThumbnails"></div>
                </div>

                <div class="detail-info-grid">
                    <div class="detail-item">
                        <label>Product Name</label>
                        <div class="val" id="modalName" style="font-size:15px; font-weight:900;">—</div>
                    </div>

                    <div class="detail-item">
                        <label>Slug / Route</label>
                        <div class="val" id="modalSlug" style="font-family:monospace; color:#7b8da1;">—</div>
                    </div>

                    <div class="detail-meta">
                        <div class="detail-card">
                            <div class="detail-item">
                                <label>Category</label>
                                <div class="val" id="modalCategory" style="color:#0284c7; font-weight:800;">—</div>
                            </div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-item">
                                <label>Price (With Tax)</label>
                                <div class="val" id="modalPrice" style="color:#059669; font-weight:900;">—</div>
                            </div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-item">
                                <label>Status</label>
                                <div class="val" id="modalStatus">—</div>
                            </div>
                        </div>
                    </div>

                    <div class="detail-meta">
                        <div class="detail-card">
                            <div class="detail-item">
                                <label>GST / PST Breakdown</label>
                                <div class="val" id="modalTaxes">—</div>
                            </div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-item">
                                <label>Badges</label>
                                <div class="val" id="modalFlags">—</div>
                            </div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-item">
                                <label>Created Date</label>
                                <div class="val" id="modalCreated">—</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="detail-item detail-full" style="margin-top:10px;">
                    <label>Short Description</label>
                    <div class="val" id="modalShortDesc">—</div>
                </div>

                <div class="detail-item detail-full">
                    <label>Full Description</label>
                    <div class="val" id="modalDesc" style="white-space:pre-line;">—</div>
                </div>

                <div class="detail-item detail-full">
                    <label>Specifications</label>
                    <div class="val" id="modalSpecs" style="white-space:pre-line;">—</div>
                </div>

                <div class="detail-item detail-full">
                    <label>Care Instructions</label>
                    <div class="val" id="modalCare">—</div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <span style="font-size: 11px; color: var(--cat-muted);">Tip: Press <kbd class="kbd-badge">Esc</kbd> to close</span>
            <button type="button" class="btn" id="modalCloseBtn2">Close</button>
        </div>
    </div>
</div>

<!-- LIBRARIES FOR PDF & EXCEL -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.4/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
    function changePerPage(val) {
        const form = document.getElementById('filterForm');
        document.getElementById('limitInput').value = val;
        form.submit();
    }

    (function() {
        'use strict';

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('productSearch');
            const table = document.getElementById('productTable');
            const modal = document.getElementById('productModal');

            function rows() {
                return table ? Array.from(table.querySelectorAll('tbody .product-row')) : [];
            }

            function visibleRows() {
                return rows();
            }

            function excelExport() {
                const data = visibleRows().map(function(row) {
                    return {
                        'Product ID': row.querySelector('.order-box')?.innerText.trim() || '',
                        'Product Name': row.querySelector('.category-name')?.innerText.trim() || '',
                        'Slug': row.dataset.slug || '',
                        'Category': row.querySelector('.cat-badge')?.innerText.trim() || '',
                        'Base Price': row.querySelector('.price-value')?.innerText.trim() || '',
                        'Status': row.dataset.status === 'active' ? 'Active' : 'Inactive',
                        'Created At': row.querySelector('.category-date')?.innerText.trim() || ''
                    };
                });

                if (window.XLSX) {
                    const ws = XLSX.utils.json_to_sheet(data);
                    const wb = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(wb, ws, 'Products');
                    XLSX.writeFile(wb, 'products-page-' + new Date().toISOString().slice(0, 10) + '.xlsx');
                }
            }

            function pdfExport() {
                if (!window.jspdf || !window.jspdf.jsPDF) {
                    alert('PDF library not available.');
                    return;
                }

                const body = visibleRows().map(function(row) {
                    return [
                        row.querySelector('.order-box')?.innerText.trim() || '',
                        row.querySelector('.category-name')?.innerText.trim() || '',
                        row.querySelector('.cat-badge')?.innerText.trim() || '',
                        row.querySelector('.price-value')?.innerText.trim() || '',
                        row.dataset.status === 'active' ? 'Active' : 'Inactive',
                        row.querySelector('.category-date')?.innerText.trim() || ''
                    ];
                });

                const doc = new jspdf.jsPDF({
                    orientation: 'landscape',
                    unit: 'mm',
                    format: 'a4'
                });
                doc.setFontSize(16);
                doc.text('GatewayLinen - Products Catalog (Page View)', 14, 14);
                if (typeof doc.autoTable === 'function') {
                    doc.autoTable({
                        startY: 22,
                        head: [['ID', 'Product Name', 'Category', 'Base Price', 'Status', 'Created']],
                        body: body,
                        styles: { fontSize: 7, cellPadding: 2 },
                        headStyles: { fillColor: [5, 150, 105] }
                    });
                }
                doc.save('products-page-' + new Date().toISOString().slice(0, 10) + '.pdf');
            }

            function openModal(btn) {
                document.getElementById('modalName').textContent = btn.dataset.name || '—';
                document.getElementById('modalSlug').textContent = '/' + (btn.dataset.slug || '—');
                document.getElementById('modalCategory').textContent = btn.dataset.category || '—';
                document.getElementById('modalPrice').textContent = btn.dataset.price || '—';
                document.getElementById('modalStatus').textContent = btn.dataset.status || '—';
                document.getElementById('modalTaxes').textContent = 'GST: ' + btn.dataset.gst + ' | PST: ' + btn.dataset.pst;
                document.getElementById('modalFlags').textContent = btn.dataset.flags || 'None';
                document.getElementById('modalCreated').textContent = btn.dataset.created || '—';
                document.getElementById('modalShortDesc').textContent = btn.dataset.shortdesc || '—';
                document.getElementById('modalDesc').textContent = btn.dataset.description || 'No description provided.';
                document.getElementById('modalSpecs').textContent = btn.dataset.specs || 'No specifications listed.';
                document.getElementById('modalCare').textContent = btn.dataset.care || 'Standard care instructions.';

                const mainBox = document.getElementById('modalImageBox');
                const thumbBox = document.getElementById('modalThumbnails');
                mainBox.innerHTML = '📦';
                thumbBox.innerHTML = '';

                let images = [];
                try {
                    images = JSON.parse(btn.dataset.images || '[]');
                } catch (e) {
                    images = [];
                }

                if (images.length > 0) {
                    const mainImg = document.createElement('img');
                    mainImg.src = images[0];
                    mainImg.alt = btn.dataset.name;
                    mainImg.onerror = () => mainBox.textContent = '📦';
                    mainBox.innerHTML = '';
                    mainBox.appendChild(mainImg);

                    images.forEach((imgUrl, idx) => {
                        const thumb = document.createElement('img');
                        thumb.src = imgUrl;
                        thumb.className = 'thumb-img' + (idx === 0 ? ' active' : '');
                        thumb.addEventListener('click', () => {
                            mainImg.src = imgUrl;
                            thumbBox.querySelectorAll('.thumb-img').forEach(t => t.classList.remove('active'));
                            thumb.classList.add('active');
                        });
                        thumbBox.appendChild(thumb);
                    });
                }

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

            document.getElementById('modalCloseBtn')?.addEventListener('click', closeModal);
            document.getElementById('modalCloseBtn2')?.addEventListener('click', closeModal);
            modal?.addEventListener('click', e => {
                if (e.target === modal) closeModal();
            });

            document.querySelectorAll('.delete-product-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    const row = btn.closest('.product-row');
                    const name = row?.querySelector('.category-name')?.innerText.trim() || 'this product';
                    const confirmed = confirm('Delete "' + name + '"?\n\nThis will remove the product and its gallery images permanently.');
                    if (!confirmed) e.preventDefault();
                });
            });

            document.getElementById('printBtn')?.addEventListener('click', () => window.print());
            document.getElementById('pdfBtn')?.addEventListener('click', pdfExport);
            document.getElementById('excelBtn')?.addEventListener('click', excelExport);

            // =========================================================================
            // KEYBOARD SHORTCUTS HANDLER
            // =========================================================================
            document.addEventListener('keydown', function(e) {
                const activeTag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
                const isTyping = (activeTag === 'input' || activeTag === 'textarea' || activeTag === 'select');

                if (e.key === 'Escape') {
                    if (modal.classList.contains('show')) {
                        closeModal();
                    }
                }

                if ((e.key.toLowerCase() === 'b' && !isTyping)) {
                    e.preventDefault();
                    if (searchInput) {
                        searchInput.focus();
                        searchInput.select();
                    }
                }

                if (e.key.toLowerCase() === 'a' && !isTyping) {
                    e.preventDefault();
                    const addBtn = document.getElementById('addProductBtn');
                    if (addBtn) {
                        window.location.href = addBtn.href;
                    }
                }

                if (e.key.toLowerCase() === 'p' && !isTyping) {
                    e.preventDefault();
                    window.print();
                }

                if (e.key.toLowerCase() === 'v' && !isTyping) {
                    e.preventDefault();
                    pdfExport();
                }

                if (e.key.toLowerCase() === 'x' && !isTyping) {
                    e.preventDefault();
                    excelExport();
                }
            });
        });
    })();
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>