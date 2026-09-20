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

$activeMenu = 'categories';
$pageTitle  = 'GatewayLinen | Categories';

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

if (empty($_SESSION['category_delete_token'])) {
    $_SESSION['category_delete_token'] =
        bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['category_delete_token'];

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
| CATEGORY IMAGE URL
|--------------------------------------------------------------------------
*/

function categoryImageUrl($value): string
{
    $value = trim((string)$value);

    if ($value === '') {
        return '';
    }

    /*
    |----------------------------------------------------------------------
    | External image
    |----------------------------------------------------------------------
    */

    if (
        preg_match(
            '~^(https?:)?//|^data:image/~i',
            $value
        )
    ) {
        return $value;
    }

    /*
    |----------------------------------------------------------------------
    | Normalize path
    |----------------------------------------------------------------------
    */

    $value = str_replace(
        '\\',
        '/',
        $value
    );

    $path = parse_url(
        $value,
        PHP_URL_PATH
    );

    $file = basename(
        $path ?: $value
    );

    if (
        $file === '' ||
        $file === '.' ||
        $file === '..'
    ) {
        return '';
    }

    /*
    |----------------------------------------------------------------------
    | Application root
    |----------------------------------------------------------------------
    |
    | Current:
    | /GatewayLinenadmin/categories/index.php
    |
    | Image:
    | /GatewayLinenadmin/uploads/categories/file.jpg
    |
    */

    $script = str_replace(
        '\\',
        '/',
        $_SERVER['SCRIPT_NAME'] ??
            '/categories/index.php'
    );

    $appRoot = dirname(
        dirname($script)
    );

    $appRoot = trim(
        str_replace(
            '\\',
            '/',
            $appRoot
        ),
        '/'
    );

    $prefix =
        ($appRoot === '' || $appRoot === '.')
        ? ''
        : '/' . $appRoot;

    return
        $prefix .
        '/uploads/categories/' .
        rawurlencode($file);
}

/*
|--------------------------------------------------------------------------
| FETCH CATEGORIES
|--------------------------------------------------------------------------
*/

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

        (
            SELECT COUNT(*)
            FROM dbo.Products pr
            WHERE pr.CategoryId = c.CategoryId
        ) AS ProductCount

    FROM dbo.Categories c

    ORDER BY
        c.DisplayOrder ASC,
        c.Name ASC,
        c.CategoryId ASC
";

$stmt = sqlsrv_query(
    $conn,
    $sql
);

$categories = [];
$queryError = '';

if ($stmt !== false) {

    while (
        $row = sqlsrv_fetch_array(
            $stmt,
            SQLSRV_FETCH_ASSOC
        )
    ) {
        $categories[] = $row;
    }

    sqlsrv_free_stmt($stmt);
} else {

    $queryError =
        'Unable to load categories right now.';
}

/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$totalCategories   = count($categories);
$activeCategories  = 0;
$inactiveCategories = 0;
$totalProducts     = 0;

foreach ($categories as $category) {

    if (!empty($category['IsActive'])) {
        $activeCategories++;
    } else {
        $inactiveCategories++;
    }

    $totalProducts +=
        (int)($category['ProductCount'] ?? 0);
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

        background:
            linear-gradient(135deg,
                #059669,
                #10b981);

        color: #fff !important;

        box-shadow:
            0 6px 16px rgba(16, 185, 129, .2);
    }

    .btn-blue {
        color: var(--blue) !important;
    }

    .btn-danger {
        color: #f87171 !important;
    }

    /* STATS */

    .category-stats {
        display: grid;
        grid-template-columns:
            repeat(4, minmax(0, 1fr));

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

    /* SEARCH */

    .category-search-wrap {
        position: relative;
        width: 300px;
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
        min-width: 125px;
        padding: 0 10px;
    }

    .category-search:focus,
    .category-status-filter:focus {
        border-color: var(--green);

        box-shadow:
            0 0 0 3px rgba(16, 185, 129, .1);
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

    /* ORDER */

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

    /* CATEGORY */

    .category-main {
        display: flex;

        align-items: center;

        gap: 11px;

        min-width: 330px;
    }

    .category-image {
        display: flex;

        align-items: center;
        justify-content: center;

        width: 58px;
        height: 58px;

        flex: 0 0 58px;

        overflow: hidden;

        border: 1px solid var(--border);

        border-radius: 9px;

        background:
            linear-gradient(135deg,
                #16222e,
                #0f1823);
    }

    .category-image img {
        width: 100%;
        height: 100%;

        display: block;

        object-fit: cover;
    }

    .category-image.image-missing {
        background: var(--bg-input);
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

    .category-description {
        max-width: 310px;

        white-space: normal;

        line-height: 1.4;

        color: var(--text-body);
    }

    .category-date {
        color: var(--text-mute);

        font-size: 10px;

        line-height: 1.4;
    }

    /* PRODUCT COUNT */

    .product-count {
        display: inline-flex;

        align-items: center;

        gap: 6px;

        min-width: 40px;
        height: 28px;

        padding: 0 9px;

        border-radius: 7px;

        background: var(--green-soft);

        color: var(--green);

        font-size: 11px;

        font-weight: 800;
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

        box-shadow:
            0 0 6px var(--green);
    }

    .category-status-inactive {
        background: var(--red-soft);

        color: #f87171;
    }

    .category-status-inactive .category-status-dot {
        background: #f87171;
    }

    /* ACTION */

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
        border-color:
            rgba(239, 68, 68, .5);

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

    /* SHORTCUT */

    .shortcut-help-box {
        margin-top: 16px;

        padding: 16px 20px;

        border: 1px solid var(--border);

        border-radius: 12px;

        background: var(--bg-card);
    }

    .shortcut-help-box.hidden {
        display: none;
    }

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

        grid-template-columns:
            repeat(3, minmax(0, 1fr));

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

        background: rgba(0, 0, 0, .72);
    }

    .modal-backdrop.show {
        display: flex;
    }

    .category-modal {
        width: min(760px, 100%);

        max-height: 90vh;

        overflow: auto;

        background: var(--bg-card);

        border: 1px solid var(--border);

        border-radius: 14px;

        box-shadow:
            0 24px 80px rgba(0, 0, 0, .5);
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
        padding: 18px;
    }

    .detail-grid {
        display: grid;

        grid-template-columns: 180px 1fr;

        gap: 16px;
    }

    .detail-image {
        width: 180px;
        height: 180px;

        border-radius: 10px;

        overflow: hidden;

        border: 1px solid var(--border);

        background: var(--bg-input);

        display: flex;

        align-items: center;
        justify-content: center;

        color: var(--text-mute);

        font-size: 30px;
    }

    .detail-image img {
        width: 100%;
        height: 100%;

        object-fit: cover;
    }

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

    .detail-full {
        grid-column: 1/-1;
    }

    .detail-meta {
        display: grid;

        grid-template-columns:
            repeat(3, 1fr);

        gap: 12px;

        margin-top: 14px;
    }

    .detail-card {
        padding: 12px;

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

    /* RESPONSIVE */

    @media(max-width:1100px) {

        .category-stats {
            grid-template-columns:
                repeat(2, 1fr);
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
            grid-template-columns:
                repeat(2, 1fr);
        }
    }

    @media(max-width:700px) {

        .category-page-header {
            flex-direction: column;

            align-items: flex-start;
        }

        .header-actions {
            width: 100%;

            justify-content: flex-start;
        }

        .category-stats {
            grid-template-columns:
                1fr 1fr;
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

    /* PRINT */

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

        .category-image {
            border-color: #ccc;
        }
    }
</style>

<main class="main">

    <section class="content">

        <div class="category-page">

            <!-- ============================================================
         PAGE HEADER
    ============================================================= -->

            <div class="category-page-header">

                <div>

                    <div class="category-breadcrumb">

                        <span>Products</span>

                        <span>/</span>

                        <span class="current">
                            Categories
                        </span>

                    </div>

                    <h1>
                        Categories
                    </h1>

                    <p>
                        Manage category image, details, products,
                        status, reports and keyboard shortcuts.
                    </p>

                </div>

                <div class="header-actions">

                    <button
                        type="button"
                        class="btn btn-blue"
                        id="printBtn">
                        🖨 Print
                        <small>P</small>
                    </button>

                    <button
                        type="button"
                        class="btn"
                        id="pdfBtn">
                        ↓ PDF
                        <small>V</small>
                    </button>

                    <button
                        type="button"
                        class="btn"
                        id="excelBtn">
                        ↓ Excel
                        <small>X</small>
                    </button>

                    <a
                        href="add.php"
                        class="btn btn-primary"
                        id="addCategoryBtn">
                        ＋ Add Category
                        <small>A</small>
                    </a>

                </div>

            </div>


            <!-- ============================================================
         MESSAGES
    ============================================================= -->

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


            <!-- ============================================================
         STATISTICS
    ============================================================= -->

            <div class="category-stats">

                <div class="category-stat-item">

                    <div class="category-stat-icon">
                        #
                    </div>

                    <div>

                        <div class="category-stat-label">
                            Total Categories
                        </div>

                        <div class="category-stat-value">
                            <?= $totalCategories ?>
                        </div>

                    </div>

                </div>


                <div class="category-stat-item">

                    <div class="category-stat-icon">
                        ✓
                    </div>

                    <div>

                        <div class="category-stat-label">
                            Active
                        </div>

                        <div class="category-stat-value">
                            <?= $activeCategories ?>
                        </div>

                    </div>

                </div>


                <div class="category-stat-item">

                    <div class="category-stat-icon">
                        ○
                    </div>

                    <div>

                        <div class="category-stat-label">
                            Inactive
                        </div>

                        <div class="category-stat-value">
                            <?= $inactiveCategories ?>
                        </div>

                    </div>

                </div>


                <div class="category-stat-item">

                    <div class="category-stat-icon">
                        P
                    </div>

                    <div>

                        <div class="category-stat-label">
                            Products
                        </div>

                        <div class="category-stat-value">
                            <?= $totalProducts ?>
                        </div>

                    </div>

                </div>

            </div>


            <!-- ============================================================
         CATEGORY CONTENT
    ============================================================= -->

            <div class="category-content">

                <div class="category-content-header">

                    <div class="category-content-title">

                        <h2>
                            Category List
                        </h2>

                        <p>
                            Image, name, slug, description,
                            product count, status and created date
                            are visible here.
                        </p>

                    </div>


                    <div class="category-filters">

                        <div class="category-search-wrap">

                            <span class="category-search-icon">
                                ⌕
                            </span>

                            <input
                                type="search"
                                id="categorySearch"
                                class="category-search"
                                placeholder="Search category, slug, description..."
                                autocomplete="off">

                        </div>


                        <select
                            id="categoryStatusFilter"
                            class="category-status-filter">

                            <option value="all">
                                All Status
                            </option>

                            <option value="active">
                                Active
                            </option>

                            <option value="inactive">
                                Inactive
                            </option>

                        </select>

                    </div>

                </div>


                <!-- ========================================================
             EXPORT BAR
        ========================================================= -->

                <div class="export-bar">

                    <span class="export-label">
                        Reports & Export
                    </span>

                    <button
                        type="button"
                        class="btn"
                        id="printBtn2">
                        🖨 Print
                    </button>

                    <button
                        type="button"
                        class="btn"
                        id="pdfBtn2">
                        ↓ PDF
                    </button>

                    <button
                        type="button"
                        class="btn"
                        id="excelBtn2">
                        ↓ Excel
                    </button>

                </div>


                <!-- ========================================================
             TABLE SUMMARY
        ========================================================= -->

                <div class="category-table-summary">

                    <div class="category-result-text">

                        Showing

                        <strong id="visibleCategoryCount">
                            <?= $totalCategories ?>
                        </strong>

                        categories

                    </div>

                    <div class="category-result-text">

                        Total:

                        <strong>
                            <?= $totalCategories ?>
                        </strong>

                    </div>

                </div>


                <!-- ========================================================
             TABLE
        ========================================================= -->

                <div class="category-table-wrapper">

                    <?php if (empty($categories)): ?>

                        <div class="category-empty">

                            <div class="category-empty-icon">
                                ◈
                            </div>

                            <h3>
                                No Categories Found
                            </h3>

                            <p>
                                Create your first product category
                                to start organizing products.
                            </p>

                            <a
                                href="add.php"
                                class="btn btn-primary"
                                style="margin-top:16px">
                                ＋ Add First Category
                            </a>

                        </div>

                    <?php else: ?>

                        <table
                            class="category-table"
                            id="categoryTable">

                            <thead>

                                <tr>

                                    <th>
                                        Order
                                    </th>

                                    <th>
                                        Category
                                    </th>

                                    <th>
                                        Description
                                    </th>

                                    <th>
                                        Products
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Created
                                    </th>

                                    <th>
                                        Actions
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach ($categories as $category): ?>

                                    <?php

                                    $categoryId =
                                        (int)(
                                            $category['CategoryId'] ??
                                            0
                                        );

                                    $categoryName =
                                        (string)(
                                            $category['Name'] ??
                                            ''
                                        );

                                    $slug =
                                        (string)(
                                            $category['Slug'] ??
                                            ''
                                        );

                                    $description =
                                        trim(
                                            (string)(
                                                $category['Description'] ??
                                                ''
                                            )
                                        );

                                    $productCount =
                                        (int)(
                                            $category['ProductCount'] ??
                                            0
                                        );

                                    $displayOrder =
                                        (int)(
                                            $category['DisplayOrder'] ??
                                            0
                                        );

                                    $isActive =
                                        !empty($category['IsActive']);

                                    $image =
                                        categoryImageUrl(
                                            $category['ImageUrl'] ??
                                                ''
                                        );

                                    $createdAt =
                                        dateValue(
                                            $category['CreatedAt'] ??
                                                ''
                                        );

                                    ?>

                                    <tr
                                        class="category-row"

                                        data-id="<?= $categoryId ?>"

                                        data-order="<?= $displayOrder ?>"

                                        data-status="<?= $isActive ? 'active' : 'inactive' ?>"

                                        data-name="<?= e(strtolower($categoryName)) ?>"

                                        data-slug="<?= e(strtolower($slug)) ?>"

                                        data-description="<?= e(strtolower($description)) ?>">

                                        <!-- ORDER -->

                                        <td>

                                            <span class="order-box">
                                                <?= $displayOrder ?>
                                            </span>

                                        </td>


                                        <!-- CATEGORY -->

                                        <td>

                                            <div class="category-main">

                                                <div
                                                    class="category-image"
                                                    title="<?= e($categoryName) ?>">

                                                    <?php if ($image !== ''): ?>

                                                        <img
                                                            src="<?= e($image) ?>"
                                                            alt="<?= e($categoryName) ?>"
                                                            loading="lazy"

                                                            onerror="
                                            this.onerror=null;
                                            this.style.display='none';
                                            this.parentElement.classList.add('image-missing');
                                            this.parentElement.querySelector('.category-image-placeholder').style.display='flex';
                                        ">

                                                        <span
                                                            class="category-image-placeholder"
                                                            style="display:none">
                                                            ◈
                                                        </span>

                                                    <?php else: ?>

                                                        <span class="category-image-placeholder">
                                                            ◈
                                                        </span>

                                                    <?php endif; ?>

                                                </div>


                                                <div>

                                                    <div class="category-name">
                                                        <?= e($categoryName) ?>
                                                    </div>

                                                    <?php if ($slug !== ''): ?>

                                                        <div class="category-slug">
                                                            /
                                                            <?= e($slug) ?>
                                                        </div>

                                                    <?php endif; ?>

                                                </div>

                                            </div>

                                        </td>


                                        <!-- DESCRIPTION -->

                                        <td>

                                            <div class="category-description">

                                                <?=
                                                $description !== ''
                                                    ? e($description)
                                                    : '—'
                                                ?>

                                            </div>

                                        </td>


                                        <!-- PRODUCTS -->

                                        <td>

                                            <span class="product-count">

                                                ▣

                                                <?= $productCount ?>

                                            </span>

                                        </td>


                                        <!-- STATUS -->

                                        <td>

                                            <?php if ($isActive): ?>

                                                <span
                                                    class="
                                    category-status
                                    category-status-active
                                ">

                                                    <span
                                                        class="category-status-dot"></span>

                                                    Active

                                                </span>

                                            <?php else: ?>

                                                <span
                                                    class="
                                    category-status
                                    category-status-inactive
                                ">

                                                    <span
                                                        class="category-status-dot"></span>

                                                    Inactive

                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <!-- CREATED -->

                                        <td>

                                            <div class="category-date">
                                                <?= e($createdAt) ?>
                                            </div>

                                        </td>


                                        <!-- ACTIONS -->

                                        <td>

                                            <div class="category-actions">

                                                <!-- VIEW -->

                                                <button
                                                    type="button"

                                                    class="
                                        category-action
                                        detail-btn
                                    "

                                                    title="View full details"

                                                    data-id="<?= $categoryId ?>"

                                                    data-name="<?= e($categoryName) ?>"

                                                    data-slug="<?= e($slug) ?>"

                                                    data-description="<?= e($description) ?>"

                                                    data-products="<?= $productCount ?>"

                                                    data-status="<?= $isActive ? 'Active' : 'Inactive' ?>"

                                                    data-created="<?= e($createdAt) ?>"

                                                    data-order="<?= $displayOrder ?>"

                                                    data-image="<?= e($image) ?>">
                                                    ◉
                                                </button>


                                                <!-- EDIT -->

                                                <a
                                                    href="edit.php?id=<?= $categoryId ?>"
                                                    class="
                                        category-action
                                        edit-btn
                                    "
                                                    title="Edit Category">
                                                    ✎
                                                </a>


                                                <!-- DELETE -->

                                                <?php if ($productCount === 0): ?>

                                                    <form
                                                        method="POST"
                                                        action="delete.php"
                                                        class="delete-form"
                                                        style="display:inline">

                                                        <input
                                                            type="hidden"
                                                            name="category_id"
                                                            value="<?= $categoryId ?>">

                                                        <input
                                                            type="hidden"
                                                            name="csrf_token"
                                                            value="<?= e($csrfToken) ?>">

                                                        <button
                                                            type="submit"
                                                            class="
                                                category-action
                                                category-action-delete
                                                delete-category-btn
                                            "
                                                            title="Delete Category">
                                                            ×
                                                        </button>

                                                    </form>

                                                <?php else: ?>

                                                    <button
                                                        type="button"

                                                        class="
                                            category-action
                                            category-action-delete
                                        "

                                                        title="Cannot delete: category has products"

                                                        data-cannot-delete="<?= $productCount ?>">
                                                        ×
                                                    </button>

                                                <?php endif; ?>

                                            </div>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>


                        <!-- NO SEARCH RESULT -->

                        <div
                            id="categoryNoResult"
                            class="category-no-result"
                            style="display:none">

                            <div class="category-no-result-icon">
                                ⌕
                            </div>

                            <h3>
                                No matching categories
                            </h3>

                            <p>
                                Try changing your search or status filter.
                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <!-- ============================================================
         SHORTCUTS
    ============================================================= -->

            <div
                class="shortcut-help-box"
                id="shortcutHelpBox">

                <div class="shortcut-help-title">

                    <span>⌨</span>

                    <span>
                        Keyboard Shortcuts
                    </span>

                    <small>
                        A B C D E P V X H • Esc
                    </small>

                </div>


                <div class="shortcut-grid">

                    <div class="shortcut-item">
                        <span class="shortcut-key">A</span>
                        <span class="shortcut-desc">
                            Add Category
                        </span>
                    </div>

                    <div class="shortcut-item">
                        <span class="shortcut-key">B</span>
                        <span class="shortcut-desc">
                            Search / focus search
                        </span>
                    </div>

                    <div class="shortcut-item">
                        <span class="shortcut-key">C</span>
                        <span class="shortcut-desc">
                            Status filter
                        </span>
                    </div>

                    <div class="shortcut-item">
                        <span class="shortcut-key">D</span>
                        <span class="shortcut-desc">
                            Edit selected category
                        </span>
                    </div>

                    <div class="shortcut-item">
                        <span class="shortcut-key">E</span>
                        <span class="shortcut-desc">
                            Delete selected category
                        </span>
                    </div>

                    <div class="shortcut-item">
                        <span class="shortcut-key">P</span>
                        <span class="shortcut-desc">
                            Print
                        </span>
                    </div>

                    <div class="shortcut-item">
                        <span class="shortcut-key">V</span>
                        <span class="shortcut-desc">
                            Download PDF
                        </span>
                    </div>

                    <div class="shortcut-item">
                        <span class="shortcut-key">X</span>
                        <span class="shortcut-desc">
                            Download Excel
                        </span>
                    </div>

                    <div class="shortcut-item">
                        <span class="shortcut-key">H</span>
                        <span class="shortcut-desc">
                            Show / hide shortcuts
                        </span>
                    </div>

                    <div class="shortcut-item">
                        <span class="shortcut-key">Esc</span>
                        <span class="shortcut-desc">
                            Close details / clear search
                        </span>
                    </div>

                </div>

            </div>

        </div>

    </section>

</main>


<!-- ================================================================
     DETAILS MODAL
================================================================ -->

<div
    class="modal-backdrop"
    id="categoryModal"
    aria-hidden="true">

    <div
        class="category-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="categoryModalTitle">

        <div class="modal-header">

            <h3 id="categoryModalTitle">
                Category Details
            </h3>

            <button
                type="button"
                class="modal-close"
                id="modalCloseBtn">
                ×
            </button>

        </div>


        <div class="modal-body">

            <div class="detail-grid">

                <div
                    class="detail-image"
                    id="detailImageBox">
                    ◈
                </div>


                <div>

                    <div class="detail-item">

                        <label>
                            Name
                        </label>

                        <div id="detailName">
                            —
                        </div>

                    </div>


                    <div
                        class="detail-item"
                        style="margin-top:13px">

                        <label>
                            Slug
                        </label>

                        <div id="detailSlug">
                            —
                        </div>

                    </div>


                    <div
                        class="detail-item"
                        style="margin-top:13px">

                        <label>
                            Status
                        </label>

                        <div id="detailStatus">
                            —
                        </div>

                    </div>


                    <div class="detail-meta">

                        <div class="detail-card">

                            <div class="detail-item">

                                <label>
                                    Category No.
                                </label>

                                <div id="detailOrder">
                                    —
                                </div>

                            </div>

                        </div>


                        <div class="detail-card">

                            <div class="detail-item">

                                <label>
                                    Products
                                </label>

                                <div id="detailProducts">
                                    —
                                </div>

                            </div>

                        </div>


                        <div class="detail-card">

                            <div class="detail-item">

                                <label>
                                    Created
                                </label>

                                <div id="detailCreated">
                                    —
                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <div
                    class="
                        detail-item
                        detail-full
                    ">

                    <label>
                        Description
                    </label>

                    <div id="detailDescription">
                        —
                    </div>

                </div>

            </div>

        </div>


        <div class="modal-footer">

            <button
                type="button"
                class="btn"
                id="modalCloseBtn2">
                Close
            </button>

        </div>

    </div>

</div>


<!-- ================================================================
     PDF / EXCEL LIBRARIES
================================================================ -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.4/jspdf.plugin.autotable.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>


<script>
    (function() {

        'use strict';


        document.addEventListener(
            'DOMContentLoaded',
            function() {

                const searchInput =
                    document.getElementById(
                        'categorySearch'
                    );

                const statusFilter =
                    document.getElementById(
                        'categoryStatusFilter'
                    );

                const table =
                    document.getElementById(
                        'categoryTable'
                    );

                const countElement =
                    document.getElementById(
                        'visibleCategoryCount'
                    );

                const noResult =
                    document.getElementById(
                        'categoryNoResult'
                    );

                const shortcutBox =
                    document.getElementById(
                        'shortcutHelpBox'
                    );

                const modal =
                    document.getElementById(
                        'categoryModal'
                    );


                /*
                |--------------------------------------------------------------------------
                | ROWS
                |--------------------------------------------------------------------------
                */

                function rows() {

                    return table ?
                        Array.from(
                            table.querySelectorAll(
                                'tbody .category-row'
                            )
                        ) : [];

                }


                function visibleRows() {

                    return rows().filter(
                        function(row) {

                            return row.style.display !== 'none';

                        }
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | FIRST SELECTED ROW
                |--------------------------------------------------------------------------
                */

                function selectFirstVisible(
                    scroll
                ) {

                    const all = rows();

                    all.forEach(
                        function(row) {

                            row.classList.remove(
                                'keyboard-selected'
                            );

                        }
                    );

                    const first =
                        visibleRows()[0];

                    if (first) {

                        first.classList.add(
                            'keyboard-selected'
                        );

                        if (scroll) {

                            first.scrollIntoView({
                                block: 'nearest'
                            });

                        }

                    }

                }


                /*
                |--------------------------------------------------------------------------
                | FILTER
                |--------------------------------------------------------------------------
                */

                function filterCategories() {

                    if (!table) {
                        return;
                    }

                    const q =
                        (
                            searchInput?.value ||
                            ''
                        )
                        .toLowerCase()
                        .trim();

                    const status =
                        statusFilter?.value ||
                        'all';

                    let visible = 0;


                    rows().forEach(
                        function(row) {

                            const text = [

                                    row.dataset.name,

                                    row.dataset.slug,

                                    row.dataset.description,

                                    row.innerText

                                ]
                                .join(' ')
                                .toLowerCase();


                            const show =
                                (!q || text.includes(q)) &&
                                (
                                    status === 'all' ||
                                    row.dataset.status === status
                                );


                            row.style.display =
                                show ?
                                '' :
                                'none';


                            if (show) {
                                visible++;
                            }

                        }
                    );


                    if (countElement) {

                        countElement.textContent =
                            visible;

                    }


                    if (noResult) {

                        noResult.style.display =
                            visible === 0 ?
                            'block' :
                            'none';

                    }


                    selectFirstVisible(false);

                }


                /*
                |--------------------------------------------------------------------------
                | PRINT
                |--------------------------------------------------------------------------
                */

                function printCategories() {

                    window.print();

                }


                /*
                |--------------------------------------------------------------------------
                | EXCEL
                |--------------------------------------------------------------------------
                */

                function excelCategories() {

                    const data =
                        visibleRows().map(
                            function(row) {

                                return {

                                    'Order': row.querySelector(
                                            '.order-box'
                                        )?.innerText.trim() ||
                                        '',

                                    'Category': row.querySelector(
                                            '.category-name'
                                        )?.innerText.trim() ||
                                        '',

                                    'Slug': row.dataset.slug ||
                                        '',

                                    'Description': row.dataset.description ||
                                        '',

                                    'Products': row.querySelector(
                                            '.product-count'
                                        )?.innerText
                                        .replace(
                                            /[^\d]/g,
                                            ''
                                        ) ||
                                        '0',

                                    'Status': row.dataset.status === 'active' ?
                                        'Active' : 'Inactive',

                                    'Created': row.querySelector(
                                            '.category-date'
                                        )?.innerText.trim() ||
                                        ''

                                };

                            }
                        );


                    if (window.XLSX) {

                        const ws =
                            XLSX.utils.json_to_sheet(
                                data
                            );

                        ws['!cols'] = [

                            {
                                wch: 10
                            },
                            {
                                wch: 28
                            },
                            {
                                wch: 30
                            },
                            {
                                wch: 50
                            },
                            {
                                wch: 12
                            },
                            {
                                wch: 14
                            },
                            {
                                wch: 24
                            }

                        ];


                        const wb =
                            XLSX.utils.book_new();


                        XLSX.utils.book_append_sheet(
                            wb,
                            ws,
                            'Categories'
                        );


                        XLSX.writeFile(
                            wb,
                            'categories-' +
                            new Date()
                            .toISOString()
                            .slice(0, 10) +
                            '.xlsx'
                        );

                    }

                }


                /*
                |--------------------------------------------------------------------------
                | PDF
                |--------------------------------------------------------------------------
                */

                function pdfCategories() {

                    if (
                        !window.jspdf ||
                        !window.jspdf.jsPDF
                    ) {

                        alert(
                            'PDF library is not loaded. Use Print and choose Save as PDF.'
                        );

                        return;
                    }


                    const body =
                        visibleRows().map(
                            function(row) {

                                return [

                                    row.querySelector(
                                        '.order-box'
                                    )?.innerText.trim() ||
                                    '',

                                    row.querySelector(
                                        '.category-name'
                                    )?.innerText.trim() ||
                                    '',

                                    row.dataset.slug ||
                                    '',

                                    row.dataset.description ||
                                    '—',

                                    row.querySelector(
                                        '.product-count'
                                    )?.innerText.trim() ||
                                    '0',

                                    row.dataset.status === 'active' ?
                                    'Active' :
                                    'Inactive',

                                    row.querySelector(
                                        '.category-date'
                                    )?.innerText.trim() ||
                                    ''

                                ];

                            }
                        );


                    const doc =
                        new jspdf.jsPDF({
                            orientation: 'landscape',
                            unit: 'mm',
                            format: 'a4'
                        });


                    doc.setFontSize(16);

                    doc.text(
                        'GatewayLinen - Categories',
                        14,
                        14
                    );


                    doc.setFontSize(9);

                    doc.text(
                        'Generated: ' +
                        new Date().toLocaleString(),
                        14,
                        20
                    );


                    if (
                        typeof doc.autoTable ===
                        'function'
                    ) {

                        doc.autoTable({

                            startY: 26,

                            head: [
                                [
                                    'Order',
                                    'Category',
                                    'Slug',
                                    'Description',
                                    'Products',
                                    'Status',
                                    'Created'
                                ]
                            ],

                            body: body,

                            styles: {
                                fontSize: 7,
                                cellPadding: 2
                            },

                            headStyles: {
                                fontSize: 7
                            }

                        });

                    }


                    doc.save(
                        'categories-' +
                        new Date()
                        .toISOString()
                        .slice(0, 10) +
                        '.pdf'
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | BUTTON EVENTS
                |--------------------------------------------------------------------------
                */

                document
                    .getElementById('printBtn')
                    ?.addEventListener(
                        'click',
                        printCategories
                    );


                document
                    .getElementById('printBtn2')
                    ?.addEventListener(
                        'click',
                        printCategories
                    );


                document
                    .getElementById('pdfBtn')
                    ?.addEventListener(
                        'click',
                        pdfCategories
                    );


                document
                    .getElementById('pdfBtn2')
                    ?.addEventListener(
                        'click',
                        pdfCategories
                    );


                document
                    .getElementById('excelBtn')
                    ?.addEventListener(
                        'click',
                        excelCategories
                    );


                document
                    .getElementById('excelBtn2')
                    ?.addEventListener(
                        'click',
                        excelCategories
                    );


                searchInput
                    ?.addEventListener(
                        'input',
                        filterCategories
                    );


                statusFilter
                    ?.addEventListener(
                        'change',
                        filterCategories
                    );


                /*
                |--------------------------------------------------------------------------
                | DETAILS MODAL
                |--------------------------------------------------------------------------
                */

                function openDetails(btn) {

                    document.getElementById(
                            'detailName'
                        ).textContent =
                        btn.dataset.name ||
                        '—';


                    document.getElementById(
                            'detailSlug'
                        ).textContent =
                        btn.dataset.slug ?
                        '/' + btn.dataset.slug :
                        '—';


                    document.getElementById(
                            'detailStatus'
                        ).textContent =
                        btn.dataset.status ||
                        '—';


                    document.getElementById(
                            'detailOrder'
                        ).textContent =
                        btn.dataset.order ||
                        '—';


                    document.getElementById(
                            'detailProducts'
                        ).textContent =
                        btn.dataset.products ||
                        '0';


                    document.getElementById(
                            'detailCreated'
                        ).textContent =
                        btn.dataset.created ||
                        '—';


                    document.getElementById(
                            'detailDescription'
                        ).textContent =
                        btn.dataset.description ||
                        'No description available.';


                    const box =
                        document.getElementById(
                            'detailImageBox'
                        );


                    box.innerHTML = '';


                    if (btn.dataset.image) {

                        const img =
                            document.createElement(
                                'img'
                            );

                        img.src =
                            btn.dataset.image;

                        img.alt =
                            btn.dataset.name ||
                            'Category';


                        img.onerror =
                            function() {

                                box.textContent =
                                    '◈';

                            };


                        box.appendChild(img);

                    } else {

                        box.textContent =
                            '◈';

                    }


                    modal.classList.add('show');

                    modal.setAttribute(
                        'aria-hidden',
                        'false'
                    );

                }


                document
                    .querySelectorAll('.detail-btn')
                    .forEach(
                        function(btn) {

                            btn.addEventListener(
                                'click',
                                function() {

                                    openDetails(
                                        this
                                    );

                                }
                            );

                        }
                    );


                /*
                |--------------------------------------------------------------------------
                | CLOSE MODAL
                |--------------------------------------------------------------------------
                */

                function closeModal() {

                    if (!modal) {
                        return;
                    }

                    modal.classList.remove(
                        'show'
                    );

                    modal.setAttribute(
                        'aria-hidden',
                        'true'
                    );

                }


                document
                    .getElementById('modalCloseBtn')
                    ?.addEventListener(
                        'click',
                        closeModal
                    );


                document
                    .getElementById('modalCloseBtn2')
                    ?.addEventListener(
                        'click',
                        closeModal
                    );


                modal?.addEventListener(
                    'click',
                    function(e) {

                        if (e.target === modal) {

                            closeModal();

                        }

                    }
                );


                /*
                |--------------------------------------------------------------------------
                | DELETE CONFIRMATION
                |--------------------------------------------------------------------------
                */

                document
                    .querySelectorAll(
                        '.delete-category-btn'
                    )
                    .forEach(
                        function(btn) {

                            btn.addEventListener(
                                'click',
                                function(e) {

                                    const form =
                                        btn.closest(
                                            '.delete-form'
                                        );

                                    if (!form) {
                                        return;
                                    }


                                    const categoryName =
                                        btn
                                        .closest(
                                            '.category-row'
                                        )
                                        ?.querySelector(
                                            '.category-name'
                                        )
                                        ?.innerText
                                        .trim() ||
                                        'this category';


                                    const confirmed =
                                        confirm(
                                            'Delete "' +
                                            categoryName +
                                            '"?\n\n' +
                                            'This action cannot be undone.\n\n' +
                                            'Remaining category numbers will automatically become 1, 2, 3...'
                                        );


                                    if (!confirmed) {

                                        e.preventDefault();

                                        return;

                                    }

                                }
                            );

                        }
                    );


                /*
                |--------------------------------------------------------------------------
                | CANNOT DELETE
                |--------------------------------------------------------------------------
                */

                document
                    .querySelectorAll(
                        '[data-cannot-delete]'
                    )
                    .forEach(
                        function(btn) {

                            btn.addEventListener(
                                'click',
                                function() {

                                    alert(
                                        'This category has ' +
                                        btn.dataset.cannotDelete +
                                        ' product(s).\n\n' +
                                        'Move or remove those products first.'
                                    );

                                }
                            );

                        }
                    );


                /*
                |--------------------------------------------------------------------------
                | KEYBOARD SHORTCUTS
                |--------------------------------------------------------------------------
                */

                document.addEventListener(
                    'keydown',
                    function(e) {

                        const tag =
                            (
                                e.target?.tagName ||
                                ''
                            ).toLowerCase();


                        const typing =
                            tag === 'input' ||
                            tag === 'textarea' ||
                            tag === 'select' ||
                            e.target?.isContentEditable;


                        if (typing) {
                            return;
                        }


                        const key =
                            (
                                e.key ||
                                ''
                            ).toUpperCase();


                        if (
                            [
                                'A',
                                'B',
                                'C',
                                'D',
                                'E',
                                'P',
                                'V',
                                'X',
                                'H'
                            ].includes(key)
                        ) {

                            e.preventDefault();

                            e.stopPropagation();

                        }


                        /*
                        |--------------------------------------------------------------
                        | A = ADD
                        |--------------------------------------------------------------
                        */

                        if (key === 'A') {

                            document
                                .getElementById(
                                    'addCategoryBtn'
                                )
                                ?.click();

                            return;

                        }


                        /*
                        |--------------------------------------------------------------
                        | B = SEARCH
                        |--------------------------------------------------------------
                        */

                        if (key === 'B') {

                            searchInput?.focus();

                            searchInput?.select();

                            return;

                        }


                        /*
                        |--------------------------------------------------------------
                        | C = STATUS
                        |--------------------------------------------------------------
                        */

                        if (key === 'C') {

                            statusFilter?.focus();

                            return;

                        }


                        /*
                        |--------------------------------------------------------------
                        | D = EDIT
                        |--------------------------------------------------------------
                        */

                        if (key === 'D') {

                            const selected =
                                document.querySelector(
                                    '.category-row.keyboard-selected'
                                ) ||
                                visibleRows()[0];


                            const edit =
                                selected?.querySelector(
                                    '.edit-btn'
                                );


                            if (edit) {

                                edit.click();

                            } else {

                                alert(
                                    'No category available to edit.'
                                );

                            }

                            return;

                        }


                        /*
                        |--------------------------------------------------------------
                        | E = DELETE
                        |--------------------------------------------------------------
                        */

                        if (key === 'E') {

                            const selected =
                                document.querySelector(
                                    '.category-row.keyboard-selected'
                                ) ||
                                visibleRows()[0];


                            const del =
                                selected?.querySelector(
                                    '.delete-category-btn'
                                );


                            if (del) {

                                del.click();

                            } else if (selected) {

                                alert(
                                    'This category cannot be deleted because it contains products.'
                                );

                            } else {

                                alert(
                                    'No category available to delete.'
                                );

                            }

                            return;

                        }


                        /*
                        |--------------------------------------------------------------
                        | P = PRINT
                        |--------------------------------------------------------------
                        */

                        if (key === 'P') {

                            printCategories();

                            return;

                        }


                        /*
                        |--------------------------------------------------------------
                        | V = PDF
                        |--------------------------------------------------------------
                        */

                        if (key === 'V') {

                            pdfCategories();

                            return;

                        }


                        /*
                        |--------------------------------------------------------------
                        | X = EXCEL
                        |--------------------------------------------------------------
                        */

                        if (key === 'X') {

                            excelCategories();

                            return;

                        }


                        /*
                        |--------------------------------------------------------------
                        | H = HELP
                        |--------------------------------------------------------------
                        */

                        if (key === 'H') {

                            shortcutBox
                                ?.classList.toggle(
                                    'hidden'
                                );

                            return;

                        }


                        /*
                        |--------------------------------------------------------------
                        | ESC
                        |--------------------------------------------------------------
                        */

                        if (key === 'ESCAPE') {

                            if (
                                modal?.classList.contains(
                                    'show'
                                )
                            ) {

                                closeModal();

                                return;

                            }


                            if (
                                searchInput?.value
                            ) {

                                searchInput.value =
                                    '';

                                filterCategories();

                            }


                            searchInput?.blur();

                            statusFilter?.blur();

                        }

                    },
                    true
                );


                /*
                |--------------------------------------------------------------------------
                | CLICK ROW = SELECT
                |--------------------------------------------------------------------------
                */

                rows().forEach(
                    function(row) {

                        row.addEventListener(
                            'click',
                            function(e) {

                                if (
                                    e.target.closest(
                                        'button,a,form'
                                    )
                                ) {

                                    return;

                                }


                                rows().forEach(
                                    function(r) {

                                        r.classList.remove(
                                            'keyboard-selected'
                                        );

                                    }
                                );


                                row.classList.add(
                                    'keyboard-selected'
                                );

                            }
                        );

                    }
                );


                /*
                |--------------------------------------------------------------------------
                | INITIAL FILTER
                |--------------------------------------------------------------------------
                */

                filterCategories();

            }

        );

    })();
</script>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>