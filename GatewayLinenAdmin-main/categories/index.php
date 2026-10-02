<?php
session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$activeMenu = 'categories';
$pageTitle  = 'GatewayLinen | Categories';

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] = $_SESSION['admin_username'] ?? 'GatewayLinen Administrator';
}
if (!isset($_SESSION['admin_role'])) {
    $_SESSION['admin_role'] = 'Administrator';
}

if (empty($_SESSION['category_delete_token'])) {
    $_SESSION['category_delete_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['category_delete_token'];

$actionMessage = trim((string)($_GET['success'] ?? ''));
$actionError   = trim((string)($_GET['error'] ?? ''));

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function dateValue($value): string
{
    if ($value instanceof DateTimeInterface) {
        return $value->format('d M Y, h:i A');
    }
    return trim((string)$value);
}

function categoryImageUrl($value): string
{
    $value = trim((string)$value);
    if ($value === '') return '';

    if (preg_match('~^(https?:)?//|^data:image/~i', $value)) {
        return $value;
    }

    $value = str_replace('\\', '/', $value);
    $path  = parse_url($value, PHP_URL_PATH);
    $file  = basename($path ?: $value);

    if ($file === '' || $file === '.' || $file === '..') return '';

    $script  = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/categories/index.php');
    $appRoot = dirname(dirname($script));
    $appRoot = trim(str_replace('\\', '/', $appRoot), '/');
    $prefix  = ($appRoot === '' || $appRoot === '.') ? '' : '/' . $appRoot;

    return $prefix . '/uploads/categories/' . rawurlencode($file);
}

/* --------------------------------------------------------------------------
   LOAD CATEGORIES
   -------------------------------------------------------------------------- */
$sql = "
    SELECT
        c.CategoryId,
        c.Name,
        c.Slug,
        c.Description,
        c.ImageUrl,
        c.DisplayOrder,
        c.IsActive,
        c.CreatedAt,
        ISNULL(c.ParentCategoryId, 0) AS ParentCategoryId,
        (
            SELECT COUNT(*)
            FROM dbo.Products pr
            WHERE pr.CategoryId = c.CategoryId
        ) AS ProductCount
    FROM dbo.Categories c
    ORDER BY c.DisplayOrder ASC, c.Name ASC, c.CategoryId ASC
";

$stmt = sqlsrv_query($conn, $sql);
$allCategories = [];
$queryError = '';

if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $allCategories[] = $row;
    }
    sqlsrv_free_stmt($stmt);
} else {
    $queryError = 'Unable to load categories right now.';
}

$parentCategories = [];
$childCategoriesMap = [];

foreach ($allCategories as $cat) {
    $parentId = (int)($cat['ParentCategoryId'] ?? 0);
    if ($parentId > 0) {
        $childCategoriesMap[$parentId][] = $cat;
    } else {
        $parentCategories[] = $cat;
    }
}

$totalCategories = count($allCategories);
$activeCategories = 0;
$inactiveCategories = 0;
$totalProducts = 0;

foreach ($allCategories as $category) {
    if (!empty($category['IsActive'])) $activeCategories++;
    else $inactiveCategories++;
    $totalProducts += (int)($category['ProductCount'] ?? 0);
}

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
    min-height: 44px;
    padding: 0 15px;
    border: 1px solid var(--cat-border);
    border-radius: 9px;
    background: var(--cat-card);
    color: var(--cat-text) !important;
    font-size: 13px;
    font-weight: 900;
    text-decoration: none;
    cursor: pointer;
    transition: .16s ease;
}
.btn:hover { border-color: var(--cat-green); background: var(--cat-green-soft); color: var(--cat-green) !important; }

/* Add category button theme adjustment */
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

html[data-theme="dark"] .btn-primary,
body[data-theme="dark"] .btn-primary,
html.dark .btn-primary,
body.dark .btn-primary,
html.dark-mode .btn-primary,
body.dark-mode .btn-primary {
    background: #111b26;
    border: 1px solid #1e2d3d;
    color: #f0f4f8 !important;
    box-shadow: none;
}
html[data-theme="dark"] .btn-primary:hover,
body[data-theme="dark"] .btn-primary:hover,
html.dark .btn-primary:hover,
body.dark .btn-primary:hover,
html.dark-mode .btn-primary:hover,
body.dark-mode .btn-primary:hover {
    border-color: #10b981;
    background: rgba(16,185,129,.12);
    color: #10b981 !important;
}

.btn-blue { color: var(--cat-blue) !important; }

.key-hint, .btn small {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 21px; height: 21px; padding: 0 4px;
    border: 1px solid currentColor; border-radius: 4px; font: 800 9px/1 monospace; opacity: .9;
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
.category-content-header { display: flex; align-items: center; justify-content: space-between; gap: 28px; padding: 20px 24px; min-height: 104px; border-bottom: 1px solid var(--cat-border); }
.category-content-title h2 { margin: 0; color: var(--cat-text); font-size: 22px; font-weight: 900; }
.category-content-title p { margin: 6px 0 0; color: var(--cat-muted); font-size: 11px; font-weight: 600; line-height: 1.5; }

.category-filters { display: flex; align-items: center; gap: 10px; flex: 1 1 auto; justify-content: flex-end; }
.category-search-wrap { position: relative; width: min(720px, 100%); flex: 1 1 620px; }
.category-search-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--cat-muted); pointer-events: none; font-size: 17px; }

.category-search, .category-status-filter {
    height: 54px; border: 1px solid var(--cat-border); border-radius: 10px; outline: none; background: var(--cat-input); color: var(--cat-text); font-size: 14px; font-weight: 600;
}
.category-search { width: 100%; padding: 0 16px 0 46px; }
.category-status-filter { min-width: 190px; padding: 0 14px; }
.category-search::placeholder { color: var(--cat-muted); font-size: 14px; font-weight: 500; }
.category-search:focus, .category-status-filter:focus { border-color: var(--cat-green); box-shadow: 0 0 0 3px var(--cat-green-soft); }

.category-table-summary { display: flex; align-items: center; justify-content: space-between; padding: 12px 24px; border-bottom: 1px solid var(--cat-border); }
.category-result-text { color: var(--cat-muted); font-size: 11px; font-weight: 700; }
.category-result-text strong { color: var(--cat-text); }

.category-table-wrapper { width: 100%; overflow-x: visible; }
.category-table { width: 100%; border-collapse: collapse; }
.category-table th {
    height: 48px; padding: 0 16px; background: var(--cat-card-alt); border-bottom: 1px solid var(--cat-border);
    color: var(--cat-muted); font-size: 10px; font-weight: 900; text-align: left; text-transform: uppercase; letter-spacing: .55px; white-space: nowrap;
}
.category-table td { padding: 15px 16px; background: transparent; border-bottom: 1px solid var(--cat-border-soft); color: var(--cat-body); font-size: 12px; line-height: 1.45; vertical-align: middle; }
.category-table tbody tr { cursor: pointer; }
.category-table tbody tr:hover { background: var(--cat-green-soft); }
.category-table tbody tr.keyboard-selected { outline: 2px solid var(--cat-green); outline-offset: -2px; background: var(--cat-green-soft); }

.parent-toggle-btn {
    display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 6px;
    background: var(--cat-card-alt); color: var(--cat-text); border: 1px solid var(--cat-border); cursor: pointer; margin-right: 8px; font-size: 9px; transition: .18s ease;
}
.parent-toggle-btn:hover { background: var(--cat-green-soft); color: var(--cat-green); border-color: var(--cat-green); }
.parent-toggle-btn.expanded { transform: rotate(90deg); background: var(--cat-green); color: #fff; border-color: var(--cat-green); }
.child-row { display: none; background: var(--cat-card-alt) !important; }
.child-row.show { display: table-row; }
.child-indent { padding-left: 35px !important; }

.sub-badge, .children-count-badge { display: inline-flex; align-items: center; margin-left: 6px; padding: 3px 7px; border-radius: 10px; font-size: 8px; font-weight: 900; }
.sub-badge { background: var(--cat-blue-soft); color: var(--cat-blue); text-transform: uppercase; }
.children-count-badge { background: var(--cat-card-alt); color: var(--cat-muted); border: 1px solid var(--cat-border); }

.order-box {
    display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 30px; padding: 0 8px;
    border-radius: 7px; background: var(--cat-input); border: 1px solid var(--cat-border); color: var(--cat-green); font-size: 11px; font-weight: 900;
}
.order-box.child-order-box { color: var(--cat-blue); border-color: rgba(2,132,199,.25); background: var(--cat-blue-soft); }

.category-main { display: flex; align-items: center; gap: 11px; width: 100%; }
.category-image {
    display: flex; align-items: center; justify-content: center; width: 58px; height: 58px; flex: 0 0 58px;
    overflow: hidden; border: 1px solid var(--cat-border); border-radius: 9px; background: var(--cat-card-alt);
}
.category-image img { width: 100%; height: 100%; display: block; object-fit: cover; }
.category-image-placeholder { display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; color: var(--cat-muted); font-size: 16px; }
.category-name { color: var(--cat-text); font-size: 14px; font-weight: 900; display: flex; align-items: center; }
.category-slug { margin-top: 4px; color: var(--cat-muted); font-size: 10px; font-family: monospace; }
.category-description { width: 100%; white-space: normal; line-height: 1.5; color: var(--cat-body); font-size: 11px; }
.category-date { color: var(--cat-muted); font-size: 10px; line-height: 1.45; white-space: nowrap; }

.product-count {
    display: inline-flex; align-items: center; gap: 4px; min-width: 42px; height: 30px; padding: 0 10px;
    border-radius: 7px; background: var(--cat-green-soft); color: var(--cat-green); font-size: 11px; font-weight: 900; white-space: nowrap;
}

.category-status { display: inline-flex; align-items: center; gap: 6px; min-height: 28px; padding: 0 10px; border-radius: 20px; font-size: 10px; font-weight: 800; white-space: nowrap; }
.category-status-dot { width: 6px; height: 6px; border-radius: 50%; }
.category-status-active { background: var(--cat-green-soft); color: var(--cat-green); }
.category-status-active .category-status-dot { background: var(--cat-green); box-shadow: 0 0 6px var(--cat-green); }
.category-status-inactive { background: var(--cat-red-soft); color: var(--cat-red); }
.category-status-inactive .category-status-dot { background: var(--cat-red); }

.category-actions { display: flex; align-items: center; gap: 5px; flex-wrap: nowrap; }
.category-action {
    display: inline-flex; align-items: center; justify-content: center; gap: 3px; width: 36px; height: 36px;
    border: 1px solid var(--cat-border); border-radius: 8px; background: var(--cat-card); color: var(--cat-body) !important;
    text-decoration: none; cursor: pointer; font-size: 13px;
}
.category-action:hover { border-color: var(--cat-green); background: var(--cat-green-soft); color: var(--cat-green) !important; }
.category-action-delete:hover { border-color: rgba(220,38,38,.4); background: var(--cat-red-soft); color: var(--cat-red) !important; }
.category-action .key-hint { min-width: 16px; height: 16px; font-size: 8px; }

.category-empty, .category-no-result { padding: 65px 20px; text-align: center; }
.category-empty-icon, .category-no-result-icon { margin-bottom: 12px; color: var(--cat-green); font-size: 32px; }
.category-empty h3, .category-no-result h3 { margin: 0; color: var(--cat-text); font-size: 16px; font-weight: 900; }
.category-empty p, .category-no-result p { margin: 6px 0 0; color: var(--cat-muted); font-size: 11px; }

.category-pagination-bar { display: flex; align-items: center; justify-content: space-between; gap: 15px; padding: 14px 18px; background: var(--cat-card); border-top: 1px solid var(--cat-border); min-height: 68px; }
.category-page-info { color: var(--cat-muted); font-size: 11px; font-weight: 700; }
.category-page-controls { display: flex; align-items: center; gap: 6px; }
.page-size-wrap { display: flex; align-items: center; gap: 8px; margin-right: 10px; color: var(--cat-muted); font-size: 10px; font-weight: 800; }
.page-size-select, .pagination-btn { height: 42px; border: 1px solid var(--cat-border); border-radius: 8px; background: var(--cat-card); color: var(--cat-text); font-size: 12px; font-weight: 800; outline: none; }
.page-size-select { min-width: 78px; padding: 0 10px; }
.pagination-btn { min-width: 42px; padding: 0 10px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
.pagination-btn:hover:not(:disabled), .pagination-btn.active { background: var(--cat-green); border-color: var(--cat-green); color: #fff !important; }
.pagination-btn:disabled { opacity: .4; cursor: not-allowed; }
.pagination-ellipsis { min-width: 26px; text-align: center; color: var(--cat-muted); font-size: 13px; }
.dataset-note { margin-top: 4px; color: var(--cat-muted); font-size: 10px; }

/* ==========================================================================
   FIXED PURE WHITE BACKGROUND & CLEAR VISIBLE TEXT MODAL
   ========================================================================== */
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

.detail-main-layout { display: flex; gap: 28px; align-items: flex-start; }
.detail-image-box {
    width: 240px; height: 240px; flex: 0 0 240px; border-radius: 12px; overflow: hidden; border: 1px solid #dce4ec;
    background: #f8fafc; display: flex; align-items: center; justify-content: center; color: #7b8da1; font-size: 40px;
}
.detail-image-box img { width: 100%; height: 100%; object-fit: cover; }

.detail-info-grid { flex: 1; display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
.detail-item { display: flex; flex-direction: column; gap: 5px; }
.detail-item.full-width { grid-column: 1 / -1; }
.detail-item label { color: #7b8da1 !important; font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: .5px; }
.detail-item .val { color: #162334 !important; font-size: 13px; font-weight: 700; word-break: break-word; }

.detail-subcategories-section { margin-top: 24px; padding-top: 18px; border-top: 1px dashed #dce4ec; }
.detail-subcategories-section h4 { margin: 0 0 12px; color: #162334 !important; font-size: 13px; font-weight: 900; }

.subcat-chips-list { display: flex; flex-wrap: wrap; gap: 8px; }
.subcat-chip {
    display: inline-flex; align-items: center; gap: 8px; padding: 7px 14px; border-radius: 8px;
    background: #f8fafc; border: 1px solid #dce4ec; font-size: 12px; font-weight: 700; color: #162334 !important;
}
.subcat-chip span.badge { padding: 2px 8px; border-radius: 6px; background: rgba(5,150,105,.10); color: #059669; font-size: 10px; font-weight: 800; }

.modal-footer { display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding: 14px 24px; border-top: 1px solid #dce4ec; background: #f8fafc !important; }
.modal-footer .btn { background: #ffffff !important; color: #162334 !important; border-color: #dce4ec !important; }
.modal-footer .btn:hover { background: rgba(5,150,105,.10) !important; color: #059669 !important; border-color: #059669 !important; }

@media(max-width: 768px) {
    .detail-main-layout { flex-direction: column; align-items: center; text-align: center; }
    .detail-image-box { width: 100%; height: 240px; }
    .detail-info-grid { grid-template-columns: 1fr; width: 100%; text-align: left; }
}

@media print {
    body * { visibility: hidden; }
    .category-content, .category-content *, .category-table, .category-table * { visibility: visible; }
    .category-content { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none !important; border: none !important; }
    .category-content-header, .category-filters, .category-table-summary, .category-pagination-bar, .category-actions th:last-child, .category-actions td:last-child, .key-hint { display: none !important; }
}
</style>
<main class="main">
    <section class="content">
        <div class="category-page">

            <div class="category-page-header">
                <div>
                    <div class="category-breadcrumb">
                        <span>Products</span><span>/</span><span class="current">Categories</span>
                    </div>
                    <h1>Categories</h1>
                    <p>Manage category details, products, status and reports.</p>
                </div>

                <div class="header-actions">
                    <button type="button" class="btn" id="printBtn" title="Print (P)">🖨 Print <small>P</small></button>
                    <button type="button" class="btn" id="pdfBtn" title="PDF (V)">↓ PDF <small>V</small></button>
                    <button type="button" class="btn" id="excelBtn" title="Excel (X)">↓ Excel <small>X</small></button>
                    <a href="add.php" class="btn btn-primary" id="addCategoryBtn" title="Add Category (A)">＋ Add Category <small>A</small></a>
                </div>
            </div>

            <?php if ($actionMessage !== ''): ?>
                <div class="notice notice-success"><?= e($actionMessage) ?></div>
            <?php endif; ?>
            <?php if ($actionError !== ''): ?>
                <div class="notice notice-error"><?= e($actionError) ?></div>
            <?php endif; ?>
            <?php if ($queryError !== ''): ?>
                <div class="notice notice-error"><?= e($queryError) ?></div>
            <?php endif; ?>

            <div class="category-stats">
                <div class="category-stat-item"><div class="category-stat-icon">#</div><div><div class="category-stat-label">Total Categories</div><div class="category-stat-value"><?= $totalCategories ?></div></div></div>
                <div class="category-stat-item"><div class="category-stat-icon">✓</div><div><div class="category-stat-label">Active</div><div class="category-stat-value"><?= $activeCategories ?></div></div></div>
                <div class="category-stat-item"><div class="category-stat-icon">○</div><div><div class="category-stat-label">Inactive</div><div class="category-stat-value"><?= $inactiveCategories ?></div></div></div>
                <div class="category-stat-item"><div class="category-stat-icon">P</div><div><div class="category-stat-label">Products</div><div class="category-stat-value"><?= $totalProducts ?></div></div></div>
            </div>

            <div class="category-content">
                <div class="category-content-header">
                    <div class="category-content-title">
                        <h2>Category List</h2>
                        <p>Name, slug, description, product count, status and created date.</p>
                    </div>

                    <div class="category-filters">
                        <div class="category-search-wrap">
                            <span class="category-search-icon">⌕</span>
                            <input type="search" id="categorySearch" class="category-search" placeholder="Search category, slug, description...  (B)" autocomplete="off">
                        </div>
                        <select id="categoryStatusFilter" class="category-status-filter">
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="category-table-summary">
                    <div class="category-result-text">Showing <strong id="visibleCategoryCount"><?= count($parentCategories) ?></strong> main categories</div>
                    <div class="category-result-text">Total: <strong><?= $totalCategories ?></strong></div>
                </div>

                <div class="category-table-wrapper">
                    <?php if (empty($parentCategories)): ?>
                        <div class="category-empty">
                            <div class="category-empty-icon">◈</div>
                            <h3>No Categories Found</h3>
                            <p>Create your first product category to start organizing products.</p>
                            <a href="add.php" class="btn btn-primary" style="margin-top:14px">＋ Add First Category</a>
                        </div>
                    <?php else: ?>
                        <table class="category-table" id="categoryTable">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Products</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php $mainOrderNo = 1; foreach ($parentCategories as $category): ?>
                                <?php
                                $categoryId   = (int)$category['CategoryId'];
                                $categoryName = (string)$category['Name'];
                                $slug         = (string)$category['Slug'];
                                $description  = trim((string)$category['Description']);
                                $productCount = (int)$category['ProductCount'];
                                $displayOrder = (int)$category['DisplayOrder'];
                                $currentMainOrder = $mainOrderNo++;
                                $isActive     = !empty($category['IsActive']);
                                $image        = categoryImageUrl($category['ImageUrl']);
                                $createdAt    = dateValue($category['CreatedAt']);
                                $hasChildren  = isset($childCategoriesMap[$categoryId]) && count($childCategoriesMap[$categoryId]) > 0;
                                $childCount   = $hasChildren ? count($childCategoriesMap[$categoryId]) : 0;
                                
                                $subcatsJson = [];
                                if ($hasChildren) {
                                    foreach ($childCategoriesMap[$categoryId] as $sub) {
                                        $subcatsJson[] = [
                                            'name' => $sub['Name'],
                                            'products' => $sub['ProductCount']
                                        ];
                                    }
                                }
                                ?>
                                <tr class="category-row parent-row" data-id="<?= $categoryId ?>" data-status="<?= $isActive ? 'active' : 'inactive' ?>" data-name="<?= e(strtolower($categoryName)) ?>" data-slug="<?= e(strtolower($slug)) ?>" data-description="<?= e(strtolower($description)) ?>">
                                    <td>
                                        <span class="order-box"><?= $currentMainOrder ?></span>
                                    </td>
                                    <td>
                                        <div class="category-main">
                                            <?php if ($hasChildren): ?>
                                                <button type="button" class="parent-toggle-btn" id="toggle-btn-<?= $categoryId ?>" data-toggle-id="<?= $categoryId ?>" title="Expand / Collapse subcategories">▶</button>
                                            <?php else: ?><span style="display:inline-block;width:22px;margin-right:8px"></span><?php endif; ?>

                                            <div class="category-image">
                                                <?php if ($image !== ''): ?>
                                                    <img src="<?= e($image) ?>" alt="<?= e($categoryName) ?>" loading="lazy" onerror="this.onerror=null;this.style.display='none';this.parentElement.querySelector('.category-image-placeholder').style.display='flex';">
                                                    <span class="category-image-placeholder" style="display:none">◈</span>
                                                <?php else: ?><span class="category-image-placeholder">◈</span><?php endif; ?>
                                            </div>

                                            <div>
                                                <div class="category-name">
                                                    <?= e($categoryName) ?>
                                                    <?php if ($hasChildren): ?><span class="children-count-badge"><?= $childCount ?> sub</span><?php endif; ?>
                                                </div>
                                                <?php if ($slug !== ''): ?><div class="category-slug">/<?= e($slug) ?></div><?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><div class="category-description"><?= $description !== '' ? e($description) : '—' ?></div></td>
                                    <td><span class="product-count">▣ <?= $productCount ?></span></td>
                                    <td>
                                        <?php if ($isActive): ?><span class="category-status category-status-active"><span class="category-status-dot"></span>Active</span>
                                        <?php else: ?><span class="category-status category-status-inactive"><span class="category-status-dot"></span>Inactive</span><?php endif; ?>
                                    </td>
                                    <td><div class="category-date"><?= e($createdAt) ?></div></td>
                                    <td>
                                        <div class="category-actions">
                                            <button type="button" class="category-action detail-btn" title="View details"
                                                data-name="<?= e($categoryName) ?>"
                                                data-slug="<?= e($slug) ?>"
                                                data-description="<?= e($description) ?>"
                                                data-products="<?= $productCount ?>"
                                                data-status="<?= $isActive ? 'Active' : 'Inactive' ?>"
                                                data-created="<?= e($createdAt) ?>"
                                                data-order="<?= $displayOrder ?>"
                                                data-type="Main Category"
                                                data-image="<?= e($image) ?>"
                                                data-subcats='<?= e(json_encode($subcatsJson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'>◉</button>

                                            <a href="edit.php?id=<?= $categoryId ?>" class="category-action edit-btn" title="Edit Category (E)">✎<span class="key-hint">E</span></a>
                                            <?php if ($productCount === 0 && !$hasChildren): ?>
                                                <form method="POST" action="delete.php" class="delete-form" style="display:inline">
                                                    <input type="hidden" name="category_id" value="<?= $categoryId ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                    <button type="submit" class="category-action category-action-delete delete-category-btn" title="Delete Category (D)">×<span class="key-hint">D</span></button>
                                                </form>
                                            <?php else: ?>
                                                <button type="button" class="category-action category-action-delete" title="Cannot delete: has products or subcategories" data-cannot-delete="<?= $productCount ?: 'sub-categories' ?>">×</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>

                                <?php if ($hasChildren): $childOrderNo = 1; foreach ($childCategoriesMap[$categoryId] as $child): ?>
                                    <?php
                                    $childId         = (int)$child['CategoryId'];
                                    $childName       = (string)$child['Name'];
                                    $childSlug       = (string)$child['Slug'];
                                    $childDescription = trim((string)$child['Description']);
                                    $childProdCount   = (int)$child['ProductCount'];
                                    $childDisplayOrder= (int)$child['DisplayOrder'];
                                    $currentChildOrder= $childOrderNo++;
                                    $childIsActive    = !empty($child['IsActive']);
                                    $childImage       = categoryImageUrl($child['ImageUrl']);
                                    $childCreatedAt   = dateValue($child['CreatedAt']);
                                    ?>
                                    <tr class="category-row child-row child-of-<?= $categoryId ?>" data-parent="<?= $categoryId ?>" data-id="<?= $childId ?>" data-status="<?= $childIsActive ? 'active' : 'inactive' ?>" data-name="<?= e(strtolower($childName)) ?>" data-slug="<?= e(strtolower($childSlug)) ?>" data-description="<?= e(strtolower($childDescription)) ?>">
                                        <td><span class="order-box child-order-box"><?= $currentChildOrder ?></span></td>
                                        <td class="child-indent">
                                            <div class="category-main">
                                                <span style="color:var(--cat-muted);font-family:monospace;margin-right:2px">└─</span>
                                                <div class="category-image" style="width:44px;height:44px;flex-basis:44px">
                                                    <?php if ($childImage !== ''): ?>
                                                        <img src="<?= e($childImage) ?>" alt="<?= e($childName) ?>" loading="lazy" onerror="this.onerror=null;this.style.display='none';this.parentElement.querySelector('.category-image-placeholder').style.display='flex';">
                                                        <span class="category-image-placeholder" style="display:none">◈</span>
                                                    <?php else: ?><span class="category-image-placeholder">◈</span><?php endif; ?>
                                                </div>
                                                <div>
                                                    <div class="category-name"><?= e($childName) ?><span class="sub-badge">Sub</span></div>
                                                    <?php if ($childSlug !== ''): ?><div class="category-slug">/<?= e($childSlug) ?></div><?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><div class="category-description"><?= $childDescription !== '' ? e($childDescription) : '—' ?></div></td>
                                        <td><span class="product-count">▣ <?= $childProdCount ?></span></td>
                                        <td>
                                            <?php if ($childIsActive): ?><span class="category-status category-status-active"><span class="category-status-dot"></span>Active</span>
                                            <?php else: ?><span class="category-status category-status-inactive"><span class="category-status-dot"></span>Inactive</span><?php endif; ?>
                                        </td>
                                        <td><div class="category-date"><?= e($childCreatedAt) ?></div></td>
                                        <td>
                                            <div class="category-actions">
                                                <button type="button" class="category-action detail-btn" title="View details"
                                                    data-name="<?= e($childName) ?>"
                                                    data-slug="<?= e($childSlug) ?>"
                                                    data-description="<?= e($childDescription) ?>"
                                                    data-products="<?= $childProdCount ?>"
                                                    data-status="<?= $childIsActive ? 'Active' : 'Inactive' ?>"
                                                    data-created="<?= e($childCreatedAt) ?>"
                                                    data-order="<?= $childDisplayOrder ?>"
                                                    data-type="Subcategory (Parent: <?= e($categoryName) ?>)"
                                                    data-image="<?= e($childImage) ?>"
                                                    data-subcats='<?= e(json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'>◉</button>

                                                <a href="edit.php?id=<?= $childId ?>" class="category-action edit-btn" title="Edit Subcategory (E)">✎<span class="key-hint">E</span></a>
                                                <?php if ($childProdCount === 0): ?>
                                                    <form method="POST" action="delete.php" class="delete-form" style="display:inline">
                                                        <input type="hidden" name="category_id" value="<?= $childId ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                        <button type="submit" class="category-action category-action-delete delete-category-btn" title="Delete Subcategory (D)">×<span class="key-hint">D</span></button>
                                                    </form>
                                                <?php else: ?>
                                                    <button type="button" class="category-action category-action-delete" title="Cannot delete: subcategory has products" data-cannot-delete="<?= $childProdCount ?>">×</button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div id="categoryNoResult" class="category-no-result" style="display:none">
                            <div class="category-no-result-icon">⌕</div>
                            <h3>No matching categories</h3>
                            <p>Try changing your search or status filter.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="category-pagination-bar" id="categoryPaginationBar">
                    <div>
                        <div class="category-page-info" id="categoryPageInfo">Showing 0-0 of 0 categories</div>
                        <div class="dataset-note">Use Search and page controls to manage large catalogs.</div>
                    </div>

                    <div class="category-page-controls">
                        <div class="page-size-wrap">
                            <span>Show</span>
                            <select id="categoryPageSize" class="page-size-select">
                                <option value="25">25</option>
                                <option value="50" selected>50</option>
                                <option value="100">100</option>
                                <option value="200">200</option>
                            </select>
                            <span>categories</span>
                        </div>

                        <button type="button" class="pagination-btn" id="categoryFirstPage" title="First page">«</button>
                        <button type="button" class="pagination-btn" id="categoryPrevPage" title="Previous page">‹</button>
                        <span id="categoryPageNumbers"></span>
                        <button type="button" class="pagination-btn" id="categoryNextPage" title="Next page">›</button>
                        <button type="button" class="pagination-btn" id="categoryLastPage" title="Last page">»</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- FIXED PURE WHITE BACKGROUND DETAILS MODAL -->
<div class="modal-backdrop" id="categoryModal" aria-hidden="true">
    <div class="category-modal" role="dialog" aria-modal="true" aria-labelledby="categoryModalTitle">
        <div class="modal-header">
            <h3 id="categoryModalTitle">Category Details</h3>
            <button type="button" class="modal-close" id="modalCloseBtn">×</button>
        </div>
        <div class="modal-body">
            <div class="detail-main-layout">
                <div class="detail-image-box" id="detailImageBox">◈</div>
                <div class="detail-info-grid">
                    <div class="detail-item">
                        <label>Category Name</label>
                        <div class="val" id="detailName">—</div>
                    </div>
                    <div class="detail-item">
                        <label>Slug</label>
                        <div class="val" id="detailSlug">—</div>
                    </div>
                    <div class="detail-item">
                        <label>Category Type</label>
                        <div class="val" id="detailType">—</div>
                    </div>
                    <div class="detail-item">
                        <label>Status</label>
                        <div class="val" id="detailStatus">—</div>
                    </div>
                    <div class="detail-item">
                        <label>Display Order</label>
                        <div class="val" id="detailOrder">—</div>
                    </div>
                    <div class="detail-item">
                        <label>Total Products</label>
                        <div class="val" id="detailProducts">—</div>
                    </div>
                    <div class="detail-item">
                        <label>Created Date</label>
                        <div class="val" id="detailCreated">—</div>
                    </div>
                    <div class="detail-item full-width">
                        <label>Description</label>
                        <div class="val" id="detailDescription" style="font-weight: normal; line-height: 1.5;">—</div>
                    </div>
                </div>
            </div>

            <!-- Subcategories Section Inside Modal -->
            <div class="detail-subcategories-section" id="detailSubcatsSection" style="display: none;">
                <h4>Subcategories & Products Count</h4>
                <div class="subcat-chips-list" id="detailSubcatsList"></div>
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
(function(){ 
    'use strict'; 
 
    document.addEventListener('DOMContentLoaded', function(){ 
        const searchInput  = document.getElementById('categorySearch'); 
        const statusFilter = document.getElementById('categoryStatusFilter'); 
        const table        = document.getElementById('categoryTable'); 
        const countElement = document.getElementById('visibleCategoryCount'); 
        const noResult     = document.getElementById('categoryNoResult'); 
        const modal        = document.getElementById('categoryModal'); 
 
        const paginationBar = document.getElementById('categoryPaginationBar'); 
        const pageInfo      = document.getElementById('categoryPageInfo'); 
        const pageNumbers   = document.getElementById('categoryPageNumbers'); 
        const pageSizeSelect= document.getElementById('categoryPageSize'); 
        const firstPageBtn  = document.getElementById('categoryFirstPage'); 
        const prevPageBtn   = document.getElementById('categoryPrevPage'); 
        const nextPageBtn   = document.getElementById('categoryNextPage'); 
        const lastPageBtn   = document.getElementById('categoryLastPage'); 
 
        let currentPage = 1; 
        let pageSize = parseInt(pageSizeSelect?.value || '50', 10); 
 
        function rows(){ 
            return table ? Array.from(table.querySelectorAll('tbody .category-row')) : []; 
        } 
 
        function visibleRows(){ 
            return rows().filter(row => row.style.display !== 'none'); 
        } 
 
        function categoryGroups(){ 
            if (!table) return []; 
            const groups = []; 
            const parentRows = Array.from(table.querySelectorAll('tbody .parent-row')); 
            parentRows.forEach(function(parentRow){ 
                const parentId = parentRow.dataset.id; 
                const children = Array.from(table.querySelectorAll('.child-of-' + parentId)); 
                groups.push({ parent: parentRow, children: children }); 
            }); 
            return groups; 
        } 
 
        function matchingGroups(){ 
            return categoryGroups().filter(group => group.parent.dataset.match === '1'); 
        } 
 
        function hideAllRows(){ 
            rows().forEach(row => { 
                row.style.display = 'none'; 
                if (row.classList.contains('child-row')) row.classList.remove('show'); 
            }); 
        } 
 
        function renderPageNumbers(totalPages){ 
            if (!pageNumbers) return; 
            pageNumbers.innerHTML = ''; 
            if (totalPages <= 1) return; 
 
            const maxButtons = 7; 
            let start = Math.max(1, currentPage - 3); 
            let end = Math.min(totalPages, start + maxButtons - 1); 
 
            if ((end - start + 1) < maxButtons) { 
                start = Math.max(1, end - maxButtons + 1); 
            } 
 
            if (start > 1) { 
                const first = document.createElement('button'); 
                first.type = 'button'; 
                first.className = 'pagination-btn'; 
                first.textContent = '1'; 
                first.addEventListener('click', () => { currentPage = 1; renderPagination(); }); 
                pageNumbers.appendChild(first); 
                if (start > 2) { 
                    const dots = document.createElement('span'); 
                    dots.className = 'pagination-ellipsis'; 
                    dots.textContent = '…'; 
                    pageNumbers.appendChild(dots); 
                } 
            } 
 
            for (let page = start; page <= end; page++) { 
                const btn = document.createElement('button'); 
                btn.type = 'button'; 
                btn.className = 'pagination-btn' + (page === currentPage ? ' active' : ''); 
                btn.textContent = String(page); 
                btn.addEventListener('click', () => { currentPage = page; renderPagination(); }); 
                pageNumbers.appendChild(btn); 
            } 
 
            if (end < totalPages) { 
                if (end < totalPages - 1) { 
                    const dots = document.createElement('span'); 
                    dots.className = 'pagination-ellipsis'; 
                    dots.textContent = '…'; 
                    pageNumbers.appendChild(dots); 
                } 
                const last = document.createElement('button'); 
                last.type = 'button'; 
                last.className = 'pagination-btn'; 
                last.textContent = String(totalPages); 
                last.addEventListener('click', () => { currentPage = totalPages; renderPagination(); }); 
                pageNumbers.appendChild(last); 
            } 
        } 
 
        function renderPagination(){ 
            if (!table) return; 
            const groups = matchingGroups(); 
            const total = groups.length; 
            const totalPages = Math.max(1, Math.ceil(total / pageSize)); 
 
            if (currentPage > totalPages) currentPage = totalPages; 
            if (currentPage < 1) currentPage = 1; 
 
            hideAllRows(); 
 
            const startIndex = (currentPage - 1) * pageSize; 
            const pageGroups = groups.slice(startIndex, startIndex + pageSize); 
 
            pageGroups.forEach(group => { 
                group.parent.style.display = ''; 
                const shouldShowChildren = group.parent.dataset.expanded === '1'; 
                group.children.forEach(child => { 
                    if (shouldShowChildren) { 
                        child.style.display = ''; 
                        child.classList.add('show'); 
                    } 
                }); 
            }); 
 
            const firstItem = total === 0 ? 0 : startIndex + 1; 
            const lastItem = Math.min(startIndex + pageSize, total); 
 
            if (pageInfo) pageInfo.textContent = 'Showing ' + firstItem + '–' + lastItem + ' of ' + total + ' main categories'; 
            if (countElement) countElement.textContent = total; 
            if (noResult) noResult.style.display = total === 0 ? 'block' : 'none'; 
 
            if (firstPageBtn) firstPageBtn.disabled = currentPage <= 1; 
            if (prevPageBtn) prevPageBtn.disabled = currentPage <= 1; 
            if (nextPageBtn) nextPageBtn.disabled = currentPage >= totalPages || total === 0; 
            if (lastPageBtn) lastPageBtn.disabled = currentPage >= totalPages || total === 0; 
 
            renderPageNumbers(totalPages); 
 
            rows().forEach(r => r.classList.remove('keyboard-selected')); 
            const firstVisible = pageGroups[0]?.parent; 
            if (firstVisible) firstVisible.classList.add('keyboard-selected'); 
        } 
 
        window.toggleCategoryAccordion = function(parentId){ 
            const childRows = document.querySelectorAll('.child-of-' + parentId); 
            const toggleBtn = document.getElementById('toggle-btn-' + parentId); 
            const parentRow = document.querySelector('.parent-row[data-id="' + parentId + '"]'); 
 
            if (!childRows.length) return; 
            const isExpanded = parentRow?.dataset.expanded === '1'; 
            const newState = !isExpanded; 
 
            if (parentRow) parentRow.dataset.expanded = newState ? '1' : '0'; 
            childRows.forEach(row => { 
                row.classList.toggle('show', newState); 
                row.style.display = newState ? '' : 'none'; 
            }); 
            if (toggleBtn) toggleBtn.classList.toggle('expanded', newState); 
        }; 
 
        function filterCategories(resetPage){ 
            if (!table) return; 
            if (resetPage !== false) currentPage = 1; 
 
            const q = (searchInput?.value || '').toLowerCase().trim(); 
            const status = statusFilter?.value || 'all'; 
 
            document.querySelectorAll('.parent-row').forEach(parentRow => { 
                const pId = parentRow.dataset.id; 
                const childRows = document.querySelectorAll('.child-of-' + pId); 
 
                const pText = [parentRow.dataset.name, parentRow.dataset.slug, parentRow.dataset.description, parentRow.innerText].join(' ').toLowerCase(); 
                const pMatch = (status === 'all' || parentRow.dataset.status === status) && (!q || pText.includes(q)); 
 
                let childMatch = false; 
                childRows.forEach(cRow => { 
                    const cText = [cRow.dataset.name, cRow.dataset.slug, cRow.dataset.description, cRow.innerText].join(' ').toLowerCase(); 
                    const match = (status === 'all' || cRow.dataset.status === status) && (!q || cText.includes(q)); 
                    if (match) childMatch = true; 
                }); 
 
                parentRow.dataset.match = (pMatch || childMatch) ? '1' : '0'; 
 
                if (q && childMatch) { 
                    parentRow.dataset.expanded = '1'; 
                    const toggleBtn = document.getElementById('toggle-btn-' + pId); 
                    if (toggleBtn) toggleBtn.classList.add('expanded'); 
                } 
            }); 
 
            renderPagination(); 
        } 
 
        function printCategories(){ window.print(); } 
        function excelCategories(){ 
            const data = rows().filter(row => { 
                if (row.classList.contains('parent-row')) return row.dataset.match === '1'; 
                const parent = document.querySelector('.parent-row[data-id="' + row.dataset.parent + '"]'); 
                return parent && parent.dataset.match === '1'; 
            }).map(row => ({ 
                'Order': row.querySelector('.order-box')?.innerText.trim() || '', 
                'Category': row.querySelector('.category-name')?.innerText.trim() || '', 
                'Slug': row.dataset.slug || '', 
                'Description': row.dataset.description || '', 
                'Products': row.querySelector('.product-count')?.innerText.replace(/[^\d]/g,'') || '0', 
                'Status': row.dataset.status === 'active' ? 'Active' : 'Inactive', 
                'Created': row.querySelector('.category-date')?.innerText.trim() || '' 
            })); 
            if (!window.XLSX) return alert('Excel library is not loaded.'); 
            const ws = XLSX.utils.json_to_sheet(data); 
            const wb = XLSX.utils.book_new(); 
            XLSX.utils.book_append_sheet(wb, ws, 'Categories'); 
            XLSX.writeFile(wb, 'categories-' + new Date().toISOString().slice(0,10) + '.xlsx'); 
        } 
 
        function pdfCategories(){ 
            if (!window.jspdf || !window.jspdf.jsPDF) return alert('PDF library is not loaded.'); 
            const body = rows().filter(row => { 
                if (row.classList.contains('parent-row')) return row.dataset.match === '1'; 
                const parent = document.querySelector('.parent-row[data-id="' + row.dataset.parent + '"]'); 
                return parent && parent.dataset.match === '1'; 
            }).map(row => [ 
                row.querySelector('.order-box')?.innerText.trim() || '', 
                row.querySelector('.category-name')?.innerText.trim() || '', 
                row.dataset.slug || '', 
                row.dataset.description || '—', 
                row.querySelector('.product-count')?.innerText.trim() || '0', 
                row.dataset.status === 'active' ? 'Active' : 'Inactive', 
                row.querySelector('.category-date')?.innerText.trim() || '' 
            ]); 
 
            const doc = new jspdf.jsPDF({orientation:'landscape',unit:'mm',format:'a4'}); 
            doc.setFontSize(15); doc.text('GatewayLinen - Categories',14,14); 
            if (typeof doc.autoTable === 'function') { 
                doc.autoTable({startY:22,head:[['Order','Category','Slug','Description','Products','Status','Created']],body:body,styles:{fontSize:7,cellPadding:2},headStyles:{fillColor:[5,150,105]}}); 
            } 
            doc.save('categories-' + new Date().toISOString().slice(0,10) + '.pdf'); 
        } 
 
        document.getElementById('printBtn')?.addEventListener('click', printCategories); 
        document.getElementById('pdfBtn')?.addEventListener('click', pdfCategories); 
        document.getElementById('excelBtn')?.addEventListener('click', excelCategories); 
        searchInput?.addEventListener('input', () => filterCategories()); 
        statusFilter?.addEventListener('change', () => filterCategories()); 
 
        function openDetails(btn){ 
            document.getElementById('detailName').textContent = btn.dataset.name || '—'; 
            document.getElementById('detailSlug').textContent = btn.dataset.slug ? '/' + btn.dataset.slug : '—'; 
            document.getElementById('detailType').textContent = btn.dataset.type || 'Category'; 
            document.getElementById('detailStatus').textContent = btn.dataset.status || '—'; 
            document.getElementById('detailOrder').textContent = btn.dataset.order || '—'; 
            document.getElementById('detailProducts').textContent = btn.dataset.products || '0'; 
            document.getElementById('detailCreated').textContent = btn.dataset.created || '—'; 
            document.getElementById('detailDescription').textContent = btn.dataset.description || 'No description available.'; 
 
            const box = document.getElementById('detailImageBox'); 
            box.innerHTML = ''; 
            if (btn.dataset.image) { 
                const img = document.createElement('img'); 
                img.src = btn.dataset.image; 
                img.alt = btn.dataset.name || 'Category'; 
                img.onerror = () => { box.textContent = '◈'; }; 
                box.appendChild(img); 
            } else { 
                box.textContent = '◈'; 
            } 
 
            const subcatsSec = document.getElementById('detailSubcatsSection');
            const subcatsList = document.getElementById('detailSubcatsList');
            if (subcatsSec && subcatsList) subcatsList.innerHTML = ''; 
 
            try { 
                const subcats = JSON.parse(btn.dataset.subcats || '[]'); 
                if (subcats.length > 0) { 
                    if (subcatsSec) subcatsSec.style.display = 'block'; 
                    subcats.forEach(sub => { 
                        const chip = document.createElement('div'); 
                        chip.className = 'subcat-chip'; 
                        chip.innerHTML = `${escapeHtml(sub.name)} <span class="badge">📦 ${sub.products} prods</span>`; 
                        if (subcatsList) subcatsList.appendChild(chip); 
                    }); 
                } else { 
                    if (subcatsSec) subcatsSec.style.display = 'none'; 
                } 
            } catch(e) { 
                if (subcatsSec) subcatsSec.style.display = 'none'; 
            } 
 
            modal.classList.add('show'); 
            modal.setAttribute('aria-hidden','false'); 
        } 
 
        function escapeHtml(text) { 
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }; 
            return text.replace(/[&<>"']/g, m => map[m]); 
        } 
 
        document.querySelectorAll('.detail-btn').forEach(btn => { 
            btn.addEventListener('click', e => { e.stopPropagation(); openDetails(btn); }); 
        }); 
 
        function closeModal(){ 
            if (!modal) return; 
            modal.classList.remove('show'); 
            modal.setAttribute('aria-hidden','true'); 
        } 
 
        document.getElementById('modalCloseBtn')?.addEventListener('click', closeModal); 
        document.getElementById('modalCloseBtn2')?.addEventListener('click', closeModal); 
        modal?.addEventListener('click', e => { if (e.target === modal) closeModal(); }); 
 
        document.querySelectorAll('.delete-category-btn').forEach(btn => { 
            btn.addEventListener('click', function(e){ 
                e.stopPropagation(); 
                const name = btn.closest('.category-row')?.querySelector('.category-name')?.innerText.trim() || 'this category'; 
                if (!confirm('Delete "' + name + '"?\n\nThis action cannot be undone.')) e.preventDefault(); 
            }); 
        }); 
 
        document.querySelectorAll('[data-cannot-delete]').forEach(btn => { 
            btn.addEventListener('click', e => { 
                e.stopPropagation(); 
                alert('This category has ' + btn.dataset.cannotDelete + ' attached.\n\nMove or remove items first.'); 
            }); 
        }); 
 
        document.querySelectorAll('.parent-toggle-btn').forEach(btn => { 
            btn.addEventListener('click', function(e){ 
                e.stopPropagation(); 
                window.toggleCategoryAccordion(this.dataset.toggleId); 
            }); 
        }); 
 
        document.querySelectorAll('.parent-row').forEach(row => { 
            row.addEventListener('click', function(e){ 
                if (e.target.closest('button') || e.target.closest('a') || e.target.closest('form')) return; 
                const parentId = this.dataset.id; 
                if (parentId) { 
                    window.toggleCategoryAccordion(parentId); 
                } 
            }); 
        }); 

        document.addEventListener('keydown', function(e){ 
            const tag = (e.target?.tagName || '').toLowerCase(); 
            const typing = tag === 'input' || tag === 'textarea' || tag === 'select' || e.target?.isContentEditable; 
            if (typing) { 
                if (e.key === 'Escape') { 
                    if (modal?.classList.contains('show')) closeModal(); 
                    else if (searchInput?.value) { searchInput.value=''; filterCategories(); } 
                    searchInput?.blur(); statusFilter?.blur(); 
                } 
                return; 
            } 
 
            const key = (e.key || '').toUpperCase(); 
            if (['A','B','C','D','E','P','V','X'].includes(key)) e.preventDefault(); 
 
            if (key === 'A') document.getElementById('addCategoryBtn')?.click(); 
            else if (key === 'B') { searchInput?.focus(); searchInput?.select(); } 
            else if (key === 'C') statusFilter?.focus(); 
            else if (key === 'D') { 
                const selected = document.querySelector('.category-row.keyboard-selected') || visibleRows()[0]; 
                selected?.querySelector('.delete-category-btn')?.click(); 
            } 
            else if (key === 'E') { 
                const selected = document.querySelector('.category-row.keyboard-selected') || visibleRows()[0]; 
                selected?.querySelector('.edit-btn')?.click(); 
            } 
            else if (key === 'P') printCategories(); 
            else if (key === 'V') pdfCategories(); 
            else if (key === 'X') excelCategories(); 
            else if (key === 'ESCAPE') { 
                if (modal?.classList.contains('show')) closeModal(); 
            } 
        }, true); 
 
        firstPageBtn?.addEventListener('click', () => { currentPage = 1; renderPagination(); }); 
        prevPageBtn?.addEventListener('click', () => { if (currentPage > 1) { currentPage--; renderPagination(); } }); 
        nextPageBtn?.addEventListener('click', () => { 
            const total = matchingGroups().length; 
            const totalPages = Math.max(1, Math.ceil(total / pageSize)); 
            if (currentPage < totalPages) { currentPage++; renderPagination(); } 
        }); 
        lastPageBtn?.addEventListener('click', () => { 
            const total = matchingGroups().length; 
            currentPage = Math.max(1, Math.ceil(total / pageSize)); 
            renderPagination(); 
        }); 
        pageSizeSelect?.addEventListener('change', function(){ 
            pageSize = parseInt(this.value || '50', 10); 
            currentPage = 1; 
            renderPagination(); 
        }); 
 
        filterCategories(); 
    }); 
})(); 
</script> 
 
<?php require_once __DIR__ . '/../includes/footer.php'; ?>