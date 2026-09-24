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
| FETCH CATEGORIES (FOR DROPDOWN FILTER)
|--------------------------------------------------------------------------
*/

$categoriesList = [];
$catSql = "SELECT CategoryId, Name FROM dbo.Categories ORDER BY Name ASC";
$catStmt = sqlsrv_query($conn, $catSql);

if ($catStmt !== false) {
    while ($row = sqlsrv_fetch_array($catStmt, SQLSRV_FETCH_ASSOC)) {
        $categoriesList[] = $row;
    }
    sqlsrv_free_stmt($catStmt);
}

/*
|--------------------------------------------------------------------------
| FETCH PRODUCTS WITH CATEGORY & MAIN IMAGE
|--------------------------------------------------------------------------
*/

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
        img.ImageUrl AS MainImage
    FROM dbo.Products p
    LEFT JOIN dbo.Categories c ON p.CategoryId = c.CategoryId
    OUTER APPLY (
        SELECT TOP 1 ImageUrl 
        FROM dbo.ProductImages 
        WHERE ProductId = p.ProductId 
        ORDER BY IsMain DESC, DisplayOrder ASC
    ) img
    ORDER BY p.ProductId DESC
";

$stmt = sqlsrv_query($conn, $sql);
$allProducts = [];
<<<<<<< HEAD
$productIds = [];
=======
>>>>>>> 58e85e28c54716508cb3e1be4a0cc7db074ee410
$queryError = '';

if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $allProducts[] = $row;
<<<<<<< HEAD
        $productIds[] = (int)$row['ProductId'];
=======
>>>>>>> 58e85e28c54716508cb3e1be4a0cc7db074ee410
    }
    sqlsrv_free_stmt($stmt);
} else {
    $queryError = 'Unable to load products from database.';
}

/*
|--------------------------------------------------------------------------
<<<<<<< HEAD
| FETCH ALL IMAGES FOR POPUP MODAL (GROUPED BY PRODUCT ID)
|--------------------------------------------------------------------------
*/

$productAllImagesMap = [];

if (!empty($productIds)) {
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $gallerySql = "
        SELECT ProductId, ImageUrl, IsMain
        FROM dbo.ProductImages
        WHERE ProductId IN ($placeholders)
        ORDER BY IsMain DESC, DisplayOrder ASC, ImageId ASC
    ";
    $galleryStmt = sqlsrv_query($conn, $gallerySql, $productIds);
    if ($galleryStmt !== false) {
        while ($gRow = sqlsrv_fetch_array($galleryStmt, SQLSRV_FETCH_ASSOC)) {
            $pId = (int)$gRow['ProductId'];
            $productAllImagesMap[$pId][] = productImageUrl($gRow['ImageUrl']);
        }
        sqlsrv_free_stmt($galleryStmt);
    }
}

/*
|--------------------------------------------------------------------------
=======
>>>>>>> 58e85e28c54716508cb3e1be4a0cc7db074ee410
| STATISTICS
|--------------------------------------------------------------------------
*/

<<<<<<< HEAD
$totalProducts    = count($allProducts);
$activeProducts   = 0;
=======
$totalProducts   = count($allProducts);
$activeProducts  = 0;
>>>>>>> 58e85e28c54716508cb3e1be4a0cc7db074ee410
$inactiveProducts = 0;
$featuredProducts = 0;

foreach ($allProducts as $product) {
    if (!empty($product['IsActive'])) {
        $activeProducts++;
    } else {
        $inactiveProducts++;
    }

    if (!empty($product['IsFeatured'])) {
        $featuredProducts++;
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

        --amber: #f59e0b;
        --amber-soft: rgba(245, 158, 11, .15);

        --purple: #a855f7;
        --purple-soft: rgba(168, 85, 247, .15);

        --radius: 10px;
    }

    html,
    body,
    .main,
    .content {
        background: var(--bg-page) !important;
        color: var(--text-body) !important;
    }

    .category-page {
        width: 100%;
        max-width: 1600px;
        margin: 0 auto;
        padding: 0;
    }

    .category-page * {
        box-sizing: border-box;
    }

    /* HEADER */
    .category-page-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 24px;
        margin-bottom: 20px;
        padding-bottom: 18px;
        border-bottom: 1px solid var(--border);
    }

    .category-breadcrumb {
        display: flex;
        gap: 8px;
        margin-bottom: 8px;
        color: var(--text-mute);
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .3px;
    }

    .category-breadcrumb .current {
        color: var(--green);
    }

    .category-page-header h1 {
        margin: 0;
        color: var(--text-hi);
        font-size: 26px;
        font-weight: 800;
    }

    .category-page-header p {
        margin: 6px 0 0;
        color: var(--text-mute);
        font-size: 12px;
    }

    /* BUTTONS */
    .header-actions,
    .category-actions,
    .category-filters,
    .export-bar {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .header-actions {
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

    .btn-blue {
        color: var(--blue) !important;
    }

    /* STATS */
    .category-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }

    .category-stat-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px 17px;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
    }

    .category-stat-icon {
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

    .category-stat-label {
        color: var(--text-mute);
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .category-stat-value {
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
    .category-content {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
    }

    .category-content-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 17px 20px;
        border-bottom: 1px solid var(--border);
    }

    .category-content-title h2 {
        margin: 0;
        color: var(--text-hi);
        font-size: 16px;
    }

    .category-content-title p {
        margin: 4px 0 0;
        color: var(--text-mute);
        font-size: 11px;
    }

    /* FILTERS */
    .category-search-wrap {
        position: relative;
        width: 270px;
    }

    .category-search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-mute);
        pointer-events: none;
    }

    .category-search,
    .category-status-filter {
        height: 36px;
        border: 1px solid var(--border);
        border-radius: 8px;
        outline: none;
        background: var(--bg-input);
        color: var(--text-hi);
        font-size: 12px;
    }

    .category-search {
        width: 100%;
        padding: 0 12px 0 34px;
    }

    .category-status-filter {
        min-width: 135px;
        padding: 0 10px;
    }

    .category-search:focus,
    .category-status-filter:focus {
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
    .category-table-summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 11px 20px;
        border-bottom: 1px solid var(--border);
    }

    .category-result-text {
        color: var(--text-mute);
        font-size: 11px;
        font-weight: 600;
    }

    .category-result-text strong {
        color: var(--text-hi);
    }

    /* TABLE */
    .category-table-wrapper {
        width: 100%;
        overflow-x: auto;
    }

    .category-table {
        width: 100%;
        min-width: 1200px;
        border-collapse: collapse;
    }

    .category-table th {
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

    .category-table td {
        padding: 12px 16px;
        background: transparent;
        border-bottom: 1px solid var(--border-soft);
        color: var(--text-body);
        font-size: 12px;
        vertical-align: middle;
    }

    .category-table tbody tr:hover {
        background: var(--bg-hover);
    }

    .category-table tbody tr.keyboard-selected {
        outline: 2px solid var(--green);
        outline-offset: -2px;
        background: var(--green-soft);
    }

    .category-table tbody tr:last-child td {
        border-bottom: none;
    }

    /* PRODUCT ROW DETAILS */
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

    .category-main {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 320px;
    }

    .category-image {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 54px;
        height: 54px;
        flex: 0 0 54px;
        overflow: hidden;
        border: 1px solid var(--border);
        border-radius: 9px;
        background: #0d1620;
    }

    .category-image img {
        width: 100%;
        height: 100%;
        display: block;
        object-fit: cover;
    }

    .category-image-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        color: var(--text-mute);
        font-size: 18px;
    }

    .category-name {
        color: var(--text-hi);
        font-size: 13px;
        font-weight: 700;
    }

    .category-slug {
        margin-top: 3px;
        color: var(--text-mute);
        font-size: 10px;
        font-family: monospace;
    }

    .cat-badge {
        display: inline-block;
        padding: 3px 9px;
        border-radius: 6px;
        background: rgba(56, 189, 248, .12);
        color: var(--blue);
        font-size: 11px;
        font-weight: 700;
    }

    /* FLAG BADGES */
    .flags-cell {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }

    .badge-tag {
        font-size: 9px;
        font-weight: 800;
        padding: 2px 6px;
        border-radius: 4px;
        text-transform: uppercase;
        letter-spacing: .3px;
    }

<<<<<<< HEAD
    .tag-featured { background: var(--amber-soft); color: var(--amber); border: 1px solid rgba(245, 158, 11, .25); }
    .tag-new { background: var(--blue-soft); color: var(--blue); border: 1px solid rgba(56, 189, 248, .25); }
    .tag-bestseller { background: var(--purple-soft); color: var(--purple); border: 1px solid rgba(168, 85, 247, .25); }
=======
    .tag-featured {
        background: var(--amber-soft);
        color: var(--amber);
        border: 1px solid rgba(245, 158, 11, .25);
    }

    .tag-new {
        background: var(--blue-soft);
        color: var(--blue);
        border: 1px solid rgba(56, 189, 248, .25);
    }

    .tag-bestseller {
        background: var(--purple-soft);
        color: var(--purple);
        border: 1px solid rgba(168, 85, 247, .25);
    }
>>>>>>> 58e85e28c54716508cb3e1be4a0cc7db074ee410

    .price-value {
        color: var(--text-hi);
        font-weight: 800;
        font-size: 13px;
    }

    /* STATUS */
    .category-status {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-height: 25px;
        padding: 0 10px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
    }

    .category-status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }

    .category-status-active {
        background: var(--green-soft);
        color: var(--green);
    }

    .category-status-active .category-status-dot {
        background: var(--green);
        box-shadow: 0 0 6px var(--green);
    }

    .category-status-inactive {
        background: var(--red-soft);
        color: #f87171;
    }

    .category-status-inactive .category-status-dot {
        background: #f87171;
    }

    /* ACTIONS */
    .category-action {
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

    .category-action:hover {
        border-color: var(--green);
        background: var(--green-soft);
        color: var(--green) !important;
    }

    .category-action-delete:hover {
        border-color: rgba(239, 68, 68, .5);
        background: var(--red-soft);
        color: var(--red) !important;
    }

    /* EMPTY */
    .category-empty,
    .category-no-result {
        padding: 65px 20px;
        text-align: center;
    }

    .category-empty-icon,
    .category-no-result-icon {
        margin-bottom: 12px;
        color: var(--green);
        font-size: 28px;
    }

    .category-empty h3,
    .category-no-result h3 {
        margin: 0;
        color: var(--text-hi);
        font-size: 16px;
    }

    .category-empty p,
    .category-no-result p {
        margin: 6px 0 0;
        color: var(--text-mute);
        font-size: 12px;
    }

    /* SHORTCUTS */
    .shortcut-help-box {
        margin-top: 16px;
        padding: 16px 20px;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: var(--bg-card);
    }

<<<<<<< HEAD
    .shortcut-help-box.hidden { display: none; }
=======
    .shortcut-help-box.hidden {
        display: none;
    }
>>>>>>> 58e85e28c54716508cb3e1be4a0cc7db074ee410

    .shortcut-help-title {
        display: flex;
        align-items: center;
        gap: 9px;
        margin-bottom: 12px;
        color: var(--text-hi);
        font-size: 13px;
        font-weight: 800;
    }

    .shortcut-help-title small {
        margin-left: auto;
        color: var(--text-mute);
        font: 600 10px monospace;
    }

    .shortcut-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
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
        padding: 0 7px;
        border-radius: 5px;
        background: #0a1119;
        border: 1px solid var(--border);
        color: var(--green);
        font: 800 10px monospace;
    }

    .shortcut-desc {
        color: var(--text-body);
        font-size: 11px;
        font-weight: 600;
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

<<<<<<< HEAD
    .modal-backdrop.show { display: flex; }

    .category-modal {
        width: min(880px, 100%);
=======
    .modal-backdrop.show {
        display: flex;
    }

    .category-modal {
        width: min(840px, 100%);
>>>>>>> 58e85e28c54716508cb3e1be4a0cc7db074ee410
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

    .modal-header h3 {
        margin: 0;
        color: var(--text-hi);
        font-size: 15px;
    }

    .modal-close {
        border: 0;
        background: transparent;
        color: var(--text-mute);
        font-size: 22px;
        cursor: pointer;
    }

    .modal-body {
        padding: 20px;
    }

    .detail-grid {
        display: grid;
<<<<<<< HEAD
        grid-template-columns: 240px 1fr;
        gap: 20px;
    }

    .modal-images-col {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .detail-image {
        width: 100%;
        height: 210px;
=======
        grid-template-columns: 220px 1fr;
        gap: 18px;
    }

    .detail-image {
        width: 220px;
        height: 220px;
>>>>>>> 58e85e28c54716508cb3e1be4a0cc7db074ee410
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid var(--border);
        background: var(--bg-input);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-mute);
        font-size: 32px;
    }

    .detail-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

<<<<<<< HEAD
    .modal-gallery-strip {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding: 4px 2px;
    }

    .modal-thumb-item {
        position: relative;
        width: 52px;
        height: 52px;
        flex: 0 0 52px;
        border-radius: 7px;
        overflow: hidden;
        border: 2px solid var(--border);
        cursor: pointer;
        background: var(--bg-input);
        transition: all 0.2s ease;
    }

    .modal-thumb-item:hover {
        border-color: var(--green);
        transform: translateY(-2px);
    }

    .modal-thumb-item.active {
        border-color: var(--green);
        box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.4);
    }

    .modal-thumb-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

=======
>>>>>>> 58e85e28c54716508cb3e1be4a0cc7db074ee410
    .detail-item label {
        display: block;
        margin-bottom: 4px;
        color: var(--text-mute);
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .detail-item div {
        color: var(--text-hi);
        font-size: 12px;
        line-height: 1.5;
    }

<<<<<<< HEAD
    .detail-full { grid-column: 1 / -1; }
=======
    .detail-full {
        grid-column: 1 / -1;
    }
>>>>>>> 58e85e28c54716508cb3e1be4a0cc7db074ee410

    .detail-meta {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        margin-top: 14px;
    }

    .detail-card {
        padding: 10px 12px;
        border: 1px solid var(--border-soft);
        border-radius: 8px;
        background: var(--bg-input);
    }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        padding: 14px 18px;
        border-top: 1px solid var(--border);
    }

    @media (max-width: 1100px) {
<<<<<<< HEAD
        .category-stats { grid-template-columns: repeat(2, 1fr); }
        .category-content-header { flex-direction: column; align-items: stretch; }
        .category-filters { width: 100%; }
        .category-search-wrap { width: 100%; }
        .shortcut-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 700px) {
        .category-page-header { flex-direction: column; align-items: flex-start; }
        .header-actions { width: 100%; justify-content: flex-start; }
        .category-stats { grid-template-columns: 1fr 1fr; }
        .category-filters { display: grid; grid-template-columns: 1fr; }
        .category-status-filter { width: 100%; }
        .shortcut-grid { grid-template-columns: 1fr; }
        .detail-grid { grid-template-columns: 1fr; }
        .detail-meta { grid-template-columns: 1fr; }
    }

    @media print {
        html, body, .main, .content { background: #fff !important; color: #111 !important; }
        .category-page { max-width: none; }
        .category-page-header, .category-stats, .category-filters, .export-bar, .category-actions,
        .shortcut-help-box, .notice, .modal-backdrop, .no-print { display: none !important; }
        .category-content { border: 0; }
        .category-table { min-width: 0; }
        .category-table th, .category-table td { color: #111 !important; background: #fff !important; border-color: #ccc !important; }
    }
</style>

<main class="main">
    <section class="content">
        <div class="category-page">

            <!-- PAGE HEADER -->
            <div class="category-page-header">
                <div>
                    <div class="category-breadcrumb">
                        <span>Catalog</span>
                        <span>/</span>
                        <span class="current">Products</span>
                    </div>
                    <h1>Products Management</h1>
                    <p>Manage product catalog, pricing, tax rates, images, flags, reports and keyboard shortcuts.</p>
                </div>

                <div class="header-actions">
                    <button type="button" class="btn btn-blue" id="printBtn">
                        🖨 Print <small>P</small>
                    </button>
                    <button type="button" class="btn" id="pdfBtn">
                        ↓ PDF <small>V</small>
                    </button>
                    <button type="button" class="btn" id="excelBtn">
                        ↓ Excel <small>X</small>
                    </button>
                    <a href="add.php" class="btn btn-primary" id="addProductBtn">
                        ＋ Add Product <small>A</small>
                    </a>
                </div>
            </div>

            <!-- SUCCESS/ERROR MESSAGES -->
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

            <!-- STATISTICS -->
            <div class="category-stats">
                <div class="category-stat-item">
                    <div class="category-stat-icon">#</div>
                    <div>
                        <div class="category-stat-label">Total Products</div>
                        <div class="category-stat-value"><?= $totalProducts ?></div>
                    </div>
                </div>

                <div class="category-stat-item">
                    <div class="category-stat-icon">✓</div>
                    <div>
                        <div class="category-stat-label">Active</div>
                        <div class="category-stat-value"><?= $activeProducts ?></div>
                    </div>
                </div>

                <div class="category-stat-item">
                    <div class="category-stat-icon">○</div>
                    <div>
                        <div class="category-stat-label">Inactive</div>
                        <div class="category-stat-value"><?= $inactiveProducts ?></div>
                    </div>
                </div>

                <div class="category-stat-item">
                    <div class="category-stat-icon">★</div>
                    <div>
                        <div class="category-stat-label">Featured</div>
                        <div class="category-stat-value"><?= $featuredProducts ?></div>
                    </div>
                </div>
            </div>

            <!-- PRODUCT CONTENT & TABLE -->
            <div class="category-content">
                <div class="category-content-header">
                    <div class="category-content-title">
                        <h2>Products List</h2>
                        <p>Image, title, SKU/slug, category, base price, tax configurations, badges and dates.</p>
                    </div>

                    <div class="category-filters">
                        <!-- REAL-TIME INSTANT SEARCH -->
                        <div class="category-search-wrap">
                            <span class="category-search-icon">⌕</span>
                            <input
                                type="search"
                                id="productSearch"
                                class="category-search"
                                placeholder="Search product name, slug, specs..."
                                autocomplete="off">
                        </div>

                        <!-- CATEGORY FILTER -->
                        <select id="categoryFilter" class="category-status-filter">
                            <option value="all">All Categories</option>
                            <?php foreach ($categoriesList as $cat): ?>
                                <option value="<?= e(strtolower($cat['Name'])) ?>">
                                    <?= e($cat['Name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- STATUS FILTER -->
                        <select id="productStatusFilter" class="category-status-filter">
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
                <div class="category-table-summary">
                    <div class="category-result-text">
                        Showing <strong id="visibleProductCount"><?= $totalProducts ?></strong> products
                    </div>
                    <div class="category-result-text">
                        Total: <strong><?= $totalProducts ?></strong>
                    </div>
                </div>

                <!-- TABLE WRAPPER -->
                <div class="category-table-wrapper">
                    <?php if (empty($allProducts)): ?>
                        <div class="category-empty">
                            <div class="category-empty-icon">📦</div>
                            <h3>No Products Found</h3>
                            <p>Get started by adding your first product to the catalog.</p>
                            <a href="add.php" class="btn btn-primary" style="margin-top:16px">
                                ＋ Add First Product
                            </a>
                        </div>
                    <?php else: ?>
                        <table class="category-table" id="productTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Base Price</th>
                                    <th>Tax (GST/PST)</th>
                                    <th>Badges</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $serialNo = 1; 
                                foreach ($allProducts as $product): 
                                ?>
                                    <?php
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
                                    $metaTitle    = (string)($product['MetaTitle'] ?? '');
                                    $metaDesc     = (string)($product['MetaDescription'] ?? '');
                                    $isActive     = !empty($product['IsActive']);
                                    $isFeatured   = !empty($product['IsFeatured']);
                                    $isNew        = !empty($product['IsNewArrival']);
                                    $isBestSeller = !empty($product['IsBestSeller']);
                                    $image        = productImageUrl($product['MainImage'] ?? '');
                                    $createdAt    = dateValue($product['CreatedAt'] ?? '');

                                    // Saari gallery images fetch karke JSON format me pass karna
                                    $allImgsList = $productAllImagesMap[$productId] ?? [];
                                    if (empty($allImgsList) && $image !== '') {
                                        $allImgsList[] = $image;
                                    }
                                    $allImgsJson = json_encode($allImgsList);
                                    ?>
                                    <tr
                                        class="category-row product-row"
                                        data-id="<?= $productId ?>"
                                        data-status="<?= $isActive ? 'active' : 'inactive' ?>"
                                        data-category="<?= e(strtolower($catName)) ?>"
                                        data-name="<?= e(strtolower($name)) ?>"
                                        data-slug="<?= e(strtolower($slug)) ?>"
                                        data-specs="<?= e(strtolower($specs)) ?>"
                                        data-description="<?= e(strtolower($desc)) ?>">

                                        <!-- CONTINUOUS SERIAL NUMBER (HAMESHA 1, 2, 3...) -->
                                        <td>
                                            <span class="order-box">#<?= $serialNo++ ?></span>
                                        </td>

                                        <!-- PRODUCT & IMAGE (TABLE VIEW EXACT JAISE IMAGE 2 MEIN HAI) -->
                                        <td>
                                            <div class="category-main">
                                                <div class="category-image" title="<?= e($name) ?>">
                                                    <?php if ($image !== ''): ?>
                                                        <img
                                                            src="<?= e($image) ?>"
                                                            alt="<?= e($name) ?>"
                                                            loading="lazy"
                                                            onerror="
                                                                this.onerror=null;
                                                                this.style.display='none';
                                                                this.parentElement.querySelector('.category-image-placeholder').style.display='flex';
                                                            ">
                                                        <span class="category-image-placeholder" style="display:none">📦</span>
                                                    <?php else: ?>
                                                        <span class="category-image-placeholder">📦</span>
                                                    <?php endif; ?>
                                                </div>

                                                <div>
                                                    <div class="category-name"><?= e($name) ?></div>
                                                    <?php if ($slug !== ''): ?>
                                                        <div class="category-slug">/<?= e($slug) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- CATEGORY -->
                                        <td>
                                            <span class="cat-badge"><?= e($catName) ?></span>
                                        </td>

                                        <!-- BASE PRICE -->
                                        <td>
                                            <span class="price-value">$<?= number_format($basePrice, 2) ?></span>
                                        </td>

                                        <!-- TAXES -->
                                        <td>
                                            <div style="font-size:11px; line-height:1.4;">
                                                <div>GST: <?= number_format($gstPercent, 1) ?>%</div>
                                                <div style="color:var(--text-mute);">PST: <?= number_format($pstPercent, 1) ?>%</div>
                                            </div>
                                        </td>

                                        <!-- BADGES -->
                                        <td>
                                            <div class="flags-cell">
                                                <?php if ($isFeatured): ?>
                                                    <span class="badge-tag tag-featured">Featured</span>
                                                <?php endif; ?>
                                                <?php if ($isNew): ?>
                                                    <span class="badge-tag tag-new">New</span>
                                                <?php endif; ?>
                                                <?php if ($isBestSeller): ?>
                                                    <span class="badge-tag tag-bestseller">Best Seller</span>
                                                <?php endif; ?>
                                                <?php if (!$isFeatured && !$isNew && !$isBestSeller): ?>
                                                    <span style="color:var(--text-mute);">—</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>

                                        <!-- STATUS -->
                                        <td>
                                            <?php if ($isActive): ?>
                                                <span class="category-status category-status-active">
                                                    <span class="category-status-dot"></span> Active
                                                </span>
                                            <?php else: ?>
                                                <span class="category-status category-status-inactive">
                                                    <span class="category-status-dot"></span> Inactive
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- CREATED -->
                                        <td>
                                            <div class="category-date"><?= e($createdAt) ?></div>
                                        </td>

                                        <!-- ACTIONS -->
                                        <td style="text-align:right;">
                                            <div class="category-actions" style="justify-content:flex-end;">
                                                <!-- VIEW (SAARI IMAGES KE SAATH) -->
                                                <button
                                                    type="button"
                                                    class="category-action detail-btn"
                                                    title="View Product Details"
                                                    data-id="<?= $productId ?>"
                                                    data-name="<?= e($name) ?>"
                                                    data-slug="<?= e($slug) ?>"
                                                    data-category="<?= e($catName) ?>"
                                                    data-price="$<?= number_format($basePrice, 2) ?>"
                                                    data-gst="<?= number_format($gstPercent, 1) ?>%"
                                                    data-pst="<?= number_format($pstPercent, 1) ?>%"
                                                    data-shortdesc="<?= e($shortDesc) ?>"
                                                    data-description="<?= e($desc) ?>"
                                                    data-specs="<?= e($specs) ?>"
                                                    data-care="<?= e($care) ?>"
                                                    data-metatitle="<?= e($metaTitle) ?>"
                                                    data-metadesc="<?= e($metaDesc) ?>"
                                                    data-status="<?= $isActive ? 'Active' : 'Inactive' ?>"
                                                    data-flags="<?= trim(($isFeatured ? 'Featured ' : '') . ($isNew ? 'NewArrival ' : '') . ($isBestSeller ? 'BestSeller' : '')) ?>"
                                                    data-created="<?= e($createdAt) ?>"
                                                    data-images='<?= e($allImgsJson) ?>'>
                                                    ◉
                                                </button>

                                                <!-- EDIT -->
                                                <a
                                                    href="edit.php?id=<?= $productId ?>"
                                                    class="category-action edit-btn"
                                                    title="Edit Product">
                                                    ✎
                                                </a>

                                                <!-- DELETE -->
                                                <form
                                                    method="POST"
                                                    action="delete.php"
                                                    class="delete-form"
                                                    style="display:inline">
                                                    <input type="hidden" name="product_id" value="<?= $productId ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                    <button
                                                        type="submit"
                                                        class="category-action category-action-delete delete-product-btn"
                                                        title="Delete Product">
                                                        ×
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- NO SEARCH RESULTS -->
                        <div id="productNoResult" class="category-no-result" style="display:none">
                            <div class="category-no-result-icon">⌕</div>
                            <h3>No matching products found</h3>
                            <p>Try clearing your search query or adjusting your filters.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- KEYBOARD SHORTCUTS -->
            <div class="shortcut-help-box" id="shortcutHelpBox">
                <div class="shortcut-help-title">
                    <span>⌨</span>
                    <span>Keyboard Shortcuts</span>
                    <small>A B C D E P V X H • Esc</small>
                </div>
                <div class="shortcut-grid">
                    <div class="shortcut-item"><span class="shortcut-key">A</span><span class="shortcut-desc">Add Product</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">B</span><span class="shortcut-desc">Search / Focus search field</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">C</span><span class="shortcut-desc">Filter Status / Categories</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">D</span><span class="shortcut-desc">Edit selected product</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">E</span><span class="shortcut-desc">Delete selected product</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">P</span><span class="shortcut-desc">Print view</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">V</span><span class="shortcut-desc">Download PDF Report</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">X</span><span class="shortcut-desc">Download Excel (.xlsx)</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">H</span><span class="shortcut-desc">Toggle Shortcuts panel</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">Esc</span><span class="shortcut-desc">Close details / clear search</span></div>
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
                <!-- ALL GALLERY IMAGES INSIDE MODAL -->
                <div class="modal-images-col">
                    <div class="detail-image" id="modalImageBox">📦</div>
                    <div class="modal-gallery-strip" id="modalGalleryStrip"></div>
                </div>

                <div>
                    <div class="detail-item">
                        <label>Product Name</label>
                        <div id="modalName" style="font-size:14px; font-weight:800;">—</div>
                    </div>

                    <div class="detail-item" style="margin-top:10px;">
                        <label>Slug / Route</label>
                        <div id="modalSlug" style="font-family:monospace; color:var(--text-mute);">—</div>
                    </div>

                    <div class="detail-meta">
                        <div class="detail-card">
                            <div class="detail-item">
                                <label>Category</label>
                                <div id="modalCategory" style="color:var(--blue); font-weight:700;">—</div>
                            </div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-item">
                                <label>Base Price</label>
                                <div id="modalPrice" style="color:var(--green); font-weight:800;">—</div>
                            </div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-item">
                                <label>Status</label>
                                <div id="modalStatus">—</div>
                            </div>
                        </div>
                    </div>

                    <div class="detail-meta" style="margin-top:10px;">
                        <div class="detail-card">
                            <div class="detail-item">
                                <label>GST / PST</label>
                                <div id="modalTaxes">—</div>
                            </div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-item">
                                <label>Badges</label>
                                <div id="modalFlags">—</div>
                            </div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-item">
                                <label>Created Date</label>
                                <div id="modalCreated">—</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="detail-item detail-full">
                    <label>Short Description</label>
                    <div id="modalShortDesc">—</div>
                </div>

                <div class="detail-item detail-full">
                    <label>Full Description</label>
                    <div id="modalDesc" style="white-space:pre-line;">—</div>
                </div>

                <div class="detail-item detail-full">
                    <label>Specifications</label>
                    <div id="modalSpecs" style="white-space:pre-line;">—</div>
                </div>

                <div class="detail-item detail-full">
                    <label>Care Instructions</label>
                    <div id="modalCare">—</div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn" id="modalCloseBtn2">Close</button>
        </div>
    </div>
</div>

<!-- LIBRARIES FOR PDF & EXCEL -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.4/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        const searchInput    = document.getElementById('productSearch');
        const categoryFilter = document.getElementById('categoryFilter');
        const statusFilter   = document.getElementById('productStatusFilter');
        const table          = document.getElementById('productTable');
        const countElement   = document.getElementById('visibleProductCount');
        const noResult       = document.getElementById('productNoResult');
        const shortcutBox    = document.getElementById('shortcutHelpBox');
        const modal          = document.getElementById('productModal');

        function rows() {
            return table ? Array.from(table.querySelectorAll('tbody .product-row')) : [];
        }

        function visibleRows() {
            return rows().filter(r => r.style.display !== 'none');
        }

        function selectFirstVisible(scroll) {
            rows().forEach(r => r.classList.remove('keyboard-selected'));
            const first = visibleRows()[0];
            if (first) {
                first.classList.add('keyboard-selected');
                if (scroll) first.scrollIntoView({ block: 'nearest' });
            }
        }

        /*
        |--------------------------------------------------------------------------
        | INSTANT REAL-TIME FILTER
        |--------------------------------------------------------------------------
        */
        function filterProducts() {
            if (!table) return;

            const q = (searchInput?.value || '').toLowerCase().trim();
            const cat = (categoryFilter?.value || 'all');
            const status = (statusFilter?.value || 'all');

            let count = 0;

            rows().forEach(function(row) {
                const name   = row.dataset.name || '';
                const slug   = row.dataset.slug || '';
                const specs  = row.dataset.specs || '';
                const desc   = row.dataset.description || '';
                const rowCat = row.dataset.category || '';
                const rowStatus = row.dataset.status || '';

                const textMatch   = (!q || name.includes(q) || slug.includes(q) || specs.includes(q) || desc.includes(q));
                const catMatch    = (cat === 'all' || rowCat === cat);
                const statusMatch = (status === 'all' || rowStatus === status);

                if (textMatch && catMatch && statusMatch) {
                    row.style.display = '';
                    count++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (countElement) countElement.textContent = count;
            if (noResult) noResult.style.display = (count === 0) ? 'block' : 'none';

            selectFirstVisible(false);
        }

        /*
        |--------------------------------------------------------------------------
        | EXPORT TO EXCEL
        |--------------------------------------------------------------------------
        */
        function excelExport() {
            const data = visibleRows().map(function(row) {
                return {
                    '#': row.querySelector('.order-box')?.innerText.trim() || '',
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
                ws['!cols'] = [{wch: 8}, {wch: 32}, {wch: 28}, {wch: 18}, {wch: 14}, {wch: 12}, {wch: 22}];
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Products');
                XLSX.writeFile(wb, 'products-' + new Date().toISOString().slice(0, 10) + '.xlsx');
            }
        }

        /*
        |--------------------------------------------------------------------------
        | EXPORT TO PDF
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
                    row.querySelector('.category-name')?.innerText.trim() || '',
                    row.querySelector('.cat-badge')?.innerText.trim() || '',
                    row.querySelector('.price-value')?.innerText.trim() || '',
                    row.dataset.status === 'active' ? 'Active' : 'Inactive',
                    row.querySelector('.category-date')?.innerText.trim() || ''
                ];
            });

            const doc = new jspdf.jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
            doc.setFontSize(16);
            doc.text('GatewayLinen - Products Catalog', 14, 14);
            doc.setFontSize(9);
            doc.text('Generated: ' + new Date().toLocaleString(), 14, 20);

            if (typeof doc.autoTable === 'function') {
                doc.autoTable({
                    startY: 25,
                    head: [['#', 'Product Name', 'Category', 'Base Price', 'Status', 'Created']],
                    body: body,
                    styles: { fontSize: 8, cellPadding: 3 },
                    headStyles: { fontSize: 8 }
                });
            }

            doc.save('products-' + new Date().toISOString().slice(0, 10) + '.pdf');
        }

        /*
        |--------------------------------------------------------------------------
        | MODAL HANDLING (ALL IMAGES DISPLAY & SWITCHER)
        |--------------------------------------------------------------------------
        */
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

            const box = document.getElementById('modalImageBox');
            const galleryStrip = document.getElementById('modalGalleryStrip');
            box.innerHTML = '';
            galleryStrip.innerHTML = '';

            let images = [];
            try {
                images = JSON.parse(btn.dataset.images || '[]');
            } catch(e) {
                images = [];
            }

            if (images.length > 0) {
                const bigImg = document.createElement('img');
                bigImg.src = images[0];
                bigImg.alt = btn.dataset.name;
                box.appendChild(bigImg);

                images.forEach((imgUrl, idx) => {
                    const thumb = document.createElement('div');
                    thumb.className = 'modal-thumb-item' + (idx === 0 ? ' active' : '');
                    thumb.innerHTML = `<img src="${imgUrl}" alt="Thumbnail">`;
                    
                    thumb.addEventListener('click', function() {
                        bigImg.src = imgUrl;
                        document.querySelectorAll('.modal-thumb-item').forEach(t => t.classList.remove('active'));
                        thumb.classList.add('active');
                    });

                    galleryStrip.appendChild(thumb);
                });
            } else {
                box.textContent = '📦';
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
        modal?.addEventListener('click', e => { if (e.target === modal) closeModal(); });

        /*
        |--------------------------------------------------------------------------
        | DELETE CONFIRMATION
        |--------------------------------------------------------------------------
        */
        document.querySelectorAll('.delete-product-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const row = btn.closest('.product-row');
                const name = row?.querySelector('.category-name')?.innerText.trim() || 'this product';
                const confirmed = confirm('Delete "' + name + '"?\n\nThis will remove the product and its gallery images permanently.');
                if (!confirmed) e.preventDefault();
            });
        });

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

        searchInput?.addEventListener('input', filterProducts);
        categoryFilter?.addEventListener('change', filterProducts);
        statusFilter?.addEventListener('change', filterProducts);

        /*
        |--------------------------------------------------------------------------
        | ROW SELECTION CLICK
        |--------------------------------------------------------------------------
        */
        rows().forEach(row => {
            row.addEventListener('click', function(e) {
                if (e.target.closest('button, a, form')) return;
                rows().forEach(r => r.classList.remove('keyboard-selected'));
                row.classList.add('keyboard-selected');
            });
        });

        /*
        |--------------------------------------------------------------------------
        | KEYBOARD SHORTCUTS
        |--------------------------------------------------------------------------
        */
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
                document.getElementById('addProductBtn')?.click();
            } else if (key === 'B') {
                searchInput?.focus();
                searchInput?.select();
            } else if (key === 'C') {
                categoryFilter?.focus();
            } else if (key === 'D') {
                const sel = document.querySelector('.product-row.keyboard-selected') || visibleRows()[0];
                sel?.querySelector('.edit-btn')?.click();
            } else if (key === 'E') {
                const sel = document.querySelector('.product-row.keyboard-selected') || visibleRows()[0];
                sel?.querySelector('.delete-product-btn')?.click();
            } else if (key === 'P') {
                window.print();
            } else if (key === 'V') {
                pdfExport();
            } else if (key === 'X') {
                excelExport();
            } else if (key === 'H') {
                shortcutBox?.classList.toggle('hidden');
            } else if (key === 'ESCAPE') {
                if (modal?.classList.contains('show')) {
                    closeModal();
                } else if (searchInput?.value) {
                    searchInput.value = '';
                    filterProducts();
                }
                searchInput?.blur();
            }
        }, true);

        filterProducts();
    });
})();
</script>

=======
        .category-stats {
            grid-template-columns: repeat(2, 1fr);
        }

        .category-content-header {
            flex-direction: column;
            align-items: stretch;
        }

        .category-filters {
            width: 100%;
        }

        .category-search-wrap {
            width: 100%;
        }

        .shortcut-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 700px) {
        .category-page-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .header-actions {
            width: 100%;
            justify-content: flex-start;
        }

        .category-stats {
            grid-template-columns: 1fr 1fr;
        }

        .category-filters {
            display: grid;
            grid-template-columns: 1fr;
        }

        .category-status-filter {
            width: 100%;
        }

        .shortcut-grid {
            grid-template-columns: 1fr;
        }

        .detail-grid {
            grid-template-columns: 1fr;
        }

        .detail-image {
            width: 100%;
            height: 220px;
        }

        .detail-meta {
            grid-template-columns: 1fr;
        }
    }

    @media print {

        html,
        body,
        .main,
        .content {
            background: #fff !important;
            color: #111 !important;
        }

        .category-page {
            max-width: none;
        }

        .category-page-header,
        .category-stats,
        .category-filters,
        .export-bar,
        .category-actions,
        .shortcut-help-box,
        .notice,
        .modal-backdrop,
        .no-print {
            display: none !important;
        }

        .category-content {
            border: 0;
        }

        .category-table {
            min-width: 0;
        }

        .category-table th,
        .category-table td {
            color: #111 !important;
            background: #fff !important;
            border-color: #ccc !important;
        }
    }
</style>

<main class="main">
    <section class="content">
        <div class="category-page">

            <!-- PAGE HEADER -->
            <div class="category-page-header">
                <div>
                    <div class="category-breadcrumb">
                        <span>Catalog</span>
                        <span>/</span>
                        <span class="current">Products</span>
                    </div>
                    <h1>Products Management</h1>
                    <p>Manage product catalog, pricing, tax rates, images, flags, reports and keyboard shortcuts.</p>
                </div>

                <div class="header-actions">
                    <button type="button" class="btn btn-blue" id="printBtn">
                        🖨 Print <small>P</small>
                    </button>
                    <button type="button" class="btn" id="pdfBtn">
                        ↓ PDF <small>V</small>
                    </button>
                    <button type="button" class="btn" id="excelBtn">
                        ↓ Excel <small>X</small>
                    </button>
                    <a href="add.php" class="btn btn-primary" id="addProductBtn">
                        ＋ Add Product <small>A</small>
                    </a>
                </div>
            </div>

            <!-- SUCCESS/ERROR MESSAGES -->
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

            <!-- STATISTICS -->
            <div class="category-stats">
                <div class="category-stat-item">
                    <div class="category-stat-icon">#</div>
                    <div>
                        <div class="category-stat-label">Total Products</div>
                        <div class="category-stat-value"><?= $totalProducts ?></div>
                    </div>
                </div>

                <div class="category-stat-item">
                    <div class="category-stat-icon">✓</div>
                    <div>
                        <div class="category-stat-label">Active</div>
                        <div class="category-stat-value"><?= $activeProducts ?></div>
                    </div>
                </div>

                <div class="category-stat-item">
                    <div class="category-stat-icon">○</div>
                    <div>
                        <div class="category-stat-label">Inactive</div>
                        <div class="category-stat-value"><?= $inactiveProducts ?></div>
                    </div>
                </div>

                <div class="category-stat-item">
                    <div class="category-stat-icon">★</div>
                    <div>
                        <div class="category-stat-label">Featured</div>
                        <div class="category-stat-value"><?= $featuredProducts ?></div>
                    </div>
                </div>
            </div>

            <!-- PRODUCT CONTENT & TABLE -->
            <div class="category-content">
                <div class="category-content-header">
                    <div class="category-content-title">
                        <h2>Products List</h2>
                        <p>Image, title, SKU/slug, category, base price, tax configurations, badges and dates.</p>
                    </div>

                    <div class="category-filters">
                        <!-- REAL-TIME INSTANT SEARCH -->
                        <div class="category-search-wrap">
                            <span class="category-search-icon">⌕</span>
                            <input
                                type="search"
                                id="productSearch"
                                class="category-search"
                                placeholder="Search product name, slug, specs..."
                                autocomplete="off">
                        </div>

                        <!-- CATEGORY FILTER -->
                        <select id="categoryFilter" class="category-status-filter">
                            <option value="all">All Categories</option>
                            <?php foreach ($categoriesList as $cat): ?>
                                <option value="<?= e(strtolower($cat['Name'])) ?>">
                                    <?= e($cat['Name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <!-- STATUS FILTER -->
                        <select id="productStatusFilter" class="category-status-filter">
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
                <div class="category-table-summary">
                    <div class="category-result-text">
                        Showing <strong id="visibleProductCount"><?= $totalProducts ?></strong> products
                    </div>
                    <div class="category-result-text">
                        Total: <strong><?= $totalProducts ?></strong>
                    </div>
                </div>

                <!-- TABLE WRAPPER -->
                <div class="category-table-wrapper">
                    <?php if (empty($allProducts)): ?>
                        <div class="category-empty">
                            <div class="category-empty-icon">📦</div>
                            <h3>No Products Found</h3>
                            <p>Get started by adding your first product to the catalog.</p>
                            <a href="add.php" class="btn btn-primary" style="margin-top:16px">
                                ＋ Add First Product
                            </a>
                        </div>
                    <?php else: ?>
                        <table class="category-table" id="productTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Base Price</th>
                                    <th>Tax (GST/PST)</th>
                                    <th>Badges</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allProducts as $product): ?>
                                    <?php
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
                                    $metaTitle    = (string)($product['MetaTitle'] ?? '');
                                    $metaDesc     = (string)($product['MetaDescription'] ?? '');
                                    $isActive     = !empty($product['IsActive']);
                                    $isFeatured   = !empty($product['IsFeatured']);
                                    $isNew        = !empty($product['IsNewArrival']);
                                    $isBestSeller = !empty($product['IsBestSeller']);
                                    $image        = productImageUrl($product['MainImage'] ?? '');
                                    $createdAt    = dateValue($product['CreatedAt'] ?? '');
                                    ?>
                                    <tr
                                        class="category-row product-row"
                                        data-id="<?= $productId ?>"
                                        data-status="<?= $isActive ? 'active' : 'inactive' ?>"
                                        data-category="<?= e(strtolower($catName)) ?>"
                                        data-name="<?= e(strtolower($name)) ?>"
                                        data-slug="<?= e(strtolower($slug)) ?>"
                                        data-specs="<?= e(strtolower($specs)) ?>"
                                        data-description="<?= e(strtolower($desc)) ?>">

                                        <!-- ID -->
                                        <td>
                                            <span class="order-box">#<?= $productId ?></span>
                                        </td>

                                        <!-- PRODUCT & IMAGE -->
                                        <td>
                                            <div class="category-main">
                                                <div class="category-image" title="<?= e($name) ?>">
                                                    <?php if ($image !== ''): ?>
                                                        <img
                                                            src="<?= e($image) ?>"
                                                            alt="<?= e($name) ?>"
                                                            loading="lazy"
                                                            onerror="
                                                                this.onerror=null;
                                                                this.style.display='none';
                                                                this.parentElement.querySelector('.category-image-placeholder').style.display='flex';
                                                            ">
                                                        <span class="category-image-placeholder" style="display:none">📦</span>
                                                    <?php else: ?>
                                                        <span class="category-image-placeholder">📦</span>
                                                    <?php endif; ?>
                                                </div>

                                                <div>
                                                    <div class="category-name"><?= e($name) ?></div>
                                                    <?php if ($slug !== ''): ?>
                                                        <div class="category-slug">/<?= e($slug) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- CATEGORY -->
                                        <td>
                                            <span class="cat-badge"><?= e($catName) ?></span>
                                        </td>

                                        <!-- BASE PRICE -->
                                        <td>
                                            <span class="price-value">$<?= number_format($basePrice, 2) ?></span>
                                        </td>

                                        <!-- TAXES -->
                                        <td>
                                            <div style="font-size:11px; line-height:1.4;">
                                                <div>GST: <?= number_format($gstPercent, 1) ?>%</div>
                                                <div style="color:var(--text-mute);">PST: <?= number_format($pstPercent, 1) ?>%</div>
                                            </div>
                                        </td>

                                        <!-- BADGES -->
                                        <td>
                                            <div class="flags-cell">
                                                <?php if ($isFeatured): ?>
                                                    <span class="badge-tag tag-featured">Featured</span>
                                                <?php endif; ?>
                                                <?php if ($isNew): ?>
                                                    <span class="badge-tag tag-new">New</span>
                                                <?php endif; ?>
                                                <?php if ($isBestSeller): ?>
                                                    <span class="badge-tag tag-bestseller">Best Seller</span>
                                                <?php endif; ?>
                                                <?php if (!$isFeatured && !$isNew && !$isBestSeller): ?>
                                                    <span style="color:var(--text-mute);">—</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>

                                        <!-- STATUS -->
                                        <td>
                                            <?php if ($isActive): ?>
                                                <span class="category-status category-status-active">
                                                    <span class="category-status-dot"></span> Active
                                                </span>
                                            <?php else: ?>
                                                <span class="category-status category-status-inactive">
                                                    <span class="category-status-dot"></span> Inactive
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- CREATED -->
                                        <td>
                                            <div class="category-date"><?= e($createdAt) ?></div>
                                        </td>

                                        <!-- ACTIONS -->
                                        <td style="text-align:right;">
                                            <div class="category-actions" style="justify-content:flex-end;">
                                                <!-- VIEW -->
                                                <button
                                                    type="button"
                                                    class="category-action detail-btn"
                                                    title="View Product Details"
                                                    data-id="<?= $productId ?>"
                                                    data-name="<?= e($name) ?>"
                                                    data-slug="<?= e($slug) ?>"
                                                    data-category="<?= e($catName) ?>"
                                                    data-price="$<?= number_format($basePrice, 2) ?>"
                                                    data-gst="<?= number_format($gstPercent, 1) ?>%"
                                                    data-pst="<?= number_format($pstPercent, 1) ?>%"
                                                    data-shortdesc="<?= e($shortDesc) ?>"
                                                    data-description="<?= e($desc) ?>"
                                                    data-specs="<?= e($specs) ?>"
                                                    data-care="<?= e($care) ?>"
                                                    data-metatitle="<?= e($metaTitle) ?>"
                                                    data-metadesc="<?= e($metaDesc) ?>"
                                                    data-status="<?= $isActive ? 'Active' : 'Inactive' ?>"
                                                    data-flags="<?= trim(($isFeatured ? 'Featured ' : '') . ($isNew ? 'NewArrival ' : '') . ($isBestSeller ? 'BestSeller' : '')) ?>"
                                                    data-created="<?= e($createdAt) ?>"
                                                    data-image="<?= e($image) ?>">
                                                    ◉
                                                </button>

                                                <!-- EDIT -->
                                                <a
                                                    href="edit.php?id=<?= $productId ?>"
                                                    class="category-action edit-btn"
                                                    title="Edit Product">
                                                    ✎
                                                </a>

                                                <!-- DELETE -->
                                                <form
                                                    method="POST"
                                                    action="delete.php"
                                                    class="delete-form"
                                                    style="display:inline">
                                                    <input type="hidden" name="product_id" value="<?= $productId ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                    <button
                                                        type="submit"
                                                        class="category-action category-action-delete delete-product-btn"
                                                        title="Delete Product">
                                                        ×
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- NO SEARCH RESULTS -->
                        <div id="productNoResult" class="category-no-result" style="display:none">
                            <div class="category-no-result-icon">⌕</div>
                            <h3>No matching products found</h3>
                            <p>Try clearing your search query or adjusting your filters.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- KEYBOARD SHORTCUTS -->
            <div class="shortcut-help-box" id="shortcutHelpBox">
                <div class="shortcut-help-title">
                    <span>⌨</span>
                    <span>Keyboard Shortcuts</span>
                    <small>A B C D E P V X H • Esc</small>
                </div>
                <div class="shortcut-grid">
                    <div class="shortcut-item"><span class="shortcut-key">A</span><span class="shortcut-desc">Add Product</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">B</span><span class="shortcut-desc">Search / Focus search field</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">C</span><span class="shortcut-desc">Filter Status / Categories</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">D</span><span class="shortcut-desc">Edit selected product</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">E</span><span class="shortcut-desc">Delete selected product</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">P</span><span class="shortcut-desc">Print view</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">V</span><span class="shortcut-desc">Download PDF Report</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">X</span><span class="shortcut-desc">Download Excel (.xlsx)</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">H</span><span class="shortcut-desc">Toggle Shortcuts panel</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">Esc</span><span class="shortcut-desc">Close details / clear search</span></div>
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
                <div class="detail-image" id="modalImageBox">📦</div>

                <div>
                    <div class="detail-item">
                        <label>Product Name</label>
                        <div id="modalName" style="font-size:14px; font-weight:800;">—</div>
                    </div>

                    <div class="detail-item" style="margin-top:10px;">
                        <label>Slug / Route</label>
                        <div id="modalSlug" style="font-family:monospace; color:var(--text-mute);">—</div>
                    </div>

                    <div class="detail-meta">
                        <div class="detail-card">
                            <div class="detail-item">
                                <label>Category</label>
                                <div id="modalCategory" style="color:var(--blue); font-weight:700;">—</div>
                            </div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-item">
                                <label>Base Price</label>
                                <div id="modalPrice" style="color:var(--green); font-weight:800;">—</div>
                            </div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-item">
                                <label>Status</label>
                                <div id="modalStatus">—</div>
                            </div>
                        </div>
                    </div>

                    <div class="detail-meta" style="margin-top:10px;">
                        <div class="detail-card">
                            <div class="detail-item">
                                <label>GST / PST</label>
                                <div id="modalTaxes">—</div>
                            </div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-item">
                                <label>Badges</label>
                                <div id="modalFlags">—</div>
                            </div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-item">
                                <label>Created Date</label>
                                <div id="modalCreated">—</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="detail-item detail-full">
                    <label>Short Description</label>
                    <div id="modalShortDesc">—</div>
                </div>

                <div class="detail-item detail-full">
                    <label>Full Description</label>
                    <div id="modalDesc" style="white-space:pre-line;">—</div>
                </div>

                <div class="detail-item detail-full">
                    <label>Specifications</label>
                    <div id="modalSpecs" style="white-space:pre-line;">—</div>
                </div>

                <div class="detail-item detail-full">
                    <label>Care Instructions</label>
                    <div id="modalCare">—</div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn" id="modalCloseBtn2">Close</button>
        </div>
    </div>
</div>

<!-- LIBRARIES FOR PDF & EXCEL -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.4/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
    (function() {
        'use strict';

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('productSearch');
            const categoryFilter = document.getElementById('categoryFilter');
            const statusFilter = document.getElementById('productStatusFilter');
            const table = document.getElementById('productTable');
            const countElement = document.getElementById('visibleProductCount');
            const noResult = document.getElementById('productNoResult');
            const shortcutBox = document.getElementById('shortcutHelpBox');
            const modal = document.getElementById('productModal');

            function rows() {
                return table ? Array.from(table.querySelectorAll('tbody .product-row')) : [];
            }

            function visibleRows() {
                return rows().filter(r => r.style.display !== 'none');
            }

            function selectFirstVisible(scroll) {
                rows().forEach(r => r.classList.remove('keyboard-selected'));
                const first = visibleRows()[0];
                if (first) {
                    first.classList.add('keyboard-selected');
                    if (scroll) first.scrollIntoView({
                        block: 'nearest'
                    });
                }
            }

            /*
            |--------------------------------------------------------------------------
            | INSTANT REAL-TIME FILTER
            |--------------------------------------------------------------------------
            */
            function filterProducts() {
                if (!table) return;

                const q = (searchInput?.value || '').toLowerCase().trim();
                const cat = (categoryFilter?.value || 'all');
                const status = (statusFilter?.value || 'all');

                let count = 0;

                rows().forEach(function(row) {
                    const name = row.dataset.name || '';
                    const slug = row.dataset.slug || '';
                    const specs = row.dataset.specs || '';
                    const desc = row.dataset.description || '';
                    const rowCat = row.dataset.category || '';
                    const rowStatus = row.dataset.status || '';

                    const textMatch = (!q || name.includes(q) || slug.includes(q) || specs.includes(q) || desc.includes(q));
                    const catMatch = (cat === 'all' || rowCat === cat);
                    const statusMatch = (status === 'all' || rowStatus === status);

                    if (textMatch && catMatch && statusMatch) {
                        row.style.display = '';
                        count++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (countElement) countElement.textContent = count;
                if (noResult) noResult.style.display = (count === 0) ? 'block' : 'none';

                selectFirstVisible(false);
            }

            /*
            |--------------------------------------------------------------------------
            | EXPORT TO EXCEL
            |--------------------------------------------------------------------------
            */
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
                    ws['!cols'] = [{
                        wch: 10
                    }, {
                        wch: 32
                    }, {
                        wch: 28
                    }, {
                        wch: 18
                    }, {
                        wch: 14
                    }, {
                        wch: 12
                    }, {
                        wch: 22
                    }];
                    const wb = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(wb, ws, 'Products');
                    XLSX.writeFile(wb, 'products-' + new Date().toISOString().slice(0, 10) + '.xlsx');
                }
            }

            /*
            |--------------------------------------------------------------------------
            | EXPORT TO PDF
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
                doc.text('GatewayLinen - Products Catalog', 14, 14);
                doc.setFontSize(9);
                doc.text('Generated: ' + new Date().toLocaleString(), 14, 20);

                if (typeof doc.autoTable === 'function') {
                    doc.autoTable({
                        startY: 25,
                        head: [
                            ['ID', 'Product Name', 'Category', 'Base Price', 'Status', 'Created']
                        ],
                        body: body,
                        styles: {
                            fontSize: 8,
                            cellPadding: 3
                        },
                        headStyles: {
                            fontSize: 8
                        }
                    });
                }

                doc.save('products-' + new Date().toISOString().slice(0, 10) + '.pdf');
            }

            /*
            |--------------------------------------------------------------------------
            | MODAL HANDLING
            |--------------------------------------------------------------------------
            */
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

                const box = document.getElementById('modalImageBox');
                box.innerHTML = '';
                if (btn.dataset.image) {
                    const img = document.createElement('img');
                    img.src = btn.dataset.image;
                    img.alt = btn.dataset.name;
                    img.onerror = () => box.textContent = '📦';
                    box.appendChild(img);
                } else {
                    box.textContent = '📦';
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

            /*
            |--------------------------------------------------------------------------
            | DELETE CONFIRMATION
            |--------------------------------------------------------------------------
            */
            document.querySelectorAll('.delete-product-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    const row = btn.closest('.product-row');
                    const name = row?.querySelector('.category-name')?.innerText.trim() || 'this product';
                    const confirmed = confirm('Delete "' + name + '"?\n\nThis will remove the product and its gallery images permanently.');
                    if (!confirmed) e.preventDefault();
                });
            });

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

            searchInput?.addEventListener('input', filterProducts);
            categoryFilter?.addEventListener('change', filterProducts);
            statusFilter?.addEventListener('change', filterProducts);

            /*
            |--------------------------------------------------------------------------
            | ROW SELECTION CLICK
            |--------------------------------------------------------------------------
            */
            rows().forEach(row => {
                row.addEventListener('click', function(e) {
                    if (e.target.closest('button, a, form')) return;
                    rows().forEach(r => r.classList.remove('keyboard-selected'));
                    row.classList.add('keyboard-selected');
                });
            });

            /*
            |--------------------------------------------------------------------------
            | KEYBOARD SHORTCUTS
            |--------------------------------------------------------------------------
            */
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
                    document.getElementById('addProductBtn')?.click();
                } else if (key === 'B') {
                    searchInput?.focus();
                    searchInput?.select();
                } else if (key === 'C') {
                    categoryFilter?.focus();
                } else if (key === 'D') {
                    const sel = document.querySelector('.product-row.keyboard-selected') || visibleRows()[0];
                    sel?.querySelector('.edit-btn')?.click();
                } else if (key === 'E') {
                    const sel = document.querySelector('.product-row.keyboard-selected') || visibleRows()[0];
                    sel?.querySelector('.delete-product-btn')?.click();
                } else if (key === 'P') {
                    window.print();
                } else if (key === 'V') {
                    pdfExport();
                } else if (key === 'X') {
                    excelExport();
                } else if (key === 'H') {
                    shortcutBox?.classList.toggle('hidden');
                } else if (key === 'ESCAPE') {
                    if (modal?.classList.contains('show')) {
                        closeModal();
                    } else if (searchInput?.value) {
                        searchInput.value = '';
                        filterProducts();
                    }
                    searchInput?.blur();
                }
            }, true);

            filterProducts();
        });
    })();
</script>

>>>>>>> 58e85e28c54716508cb3e1be4a0cc7db074ee410
<?php
require_once __DIR__ . '/../includes/footer.php';
?>