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

$activeMenu = 'coupons';
$pageTitle  = 'GatewayLinen | Coupons';

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

if (empty($_SESSION['coupon_delete_token'])) {
    $_SESSION['coupon_delete_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['coupon_delete_token'];

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
| DATE FORMAT (DEFAULT DD/MM/YYYY)
|--------------------------------------------------------------------------
*/

function dateValue($value, $format = 'd/m/Y'): string
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
| FETCH ALL COUPONS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        CouponId,
        CouponCode,
        DiscountType,
        DiscountValue,
        MinOrderAmount,
        MaxDiscountAmount,
        StartDate,
        EndDate,
        UsageLimit,
        TimesUsed,
        IsForWholesaleOnly,
        IsActive,
        CreatedAt
    FROM dbo.Coupons
    ORDER BY CouponId DESC
";

$stmt = sqlsrv_query($conn, $sql);
$allCoupons = [];
$queryError = '';

if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $allCoupons[] = $row;
    }
    sqlsrv_free_stmt($stmt);
} else {
    $queryError = 'Unable to load coupons from database.';
}

/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$totalCoupons    = count($allCoupons);
$activeCoupons   = 0;
$inactiveCoupons = 0;
$wholesaleCoupons = 0;

foreach ($allCoupons as $coupon) {
    if (!empty($coupon['IsActive'])) {
        $activeCoupons++;
    } else {
        $inactiveCoupons++;
    }

    if (!empty($coupon['IsForWholesaleOnly'])) {
        $wholesaleCoupons++;
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

    .coupon-page {
        width: 100%;
        max-width: 1600px;
        margin: 0 auto;
        padding: 0;
    }

    .coupon-page * {
        box-sizing: border-box;
    }

    /* HEADER */
    .coupon-page-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 24px;
        margin-bottom: 20px;
        padding-bottom: 18px;
        border-bottom: 1px solid var(--border);
    }

    .coupon-breadcrumb {
        display: flex;
        gap: 8px;
        margin-bottom: 8px;
        color: var(--text-mute);
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .3px;
    }

    .coupon-breadcrumb .current {
        color: var(--green);
    }

    .coupon-page-header h1 {
        margin: 0;
        color: var(--text-hi);
        font-size: 26px;
        font-weight: 800;
    }

    .coupon-page-header p {
        margin: 6px 0 0;
        color: var(--text-mute);
        font-size: 12px;
    }

    /* BUTTONS */
    .header-actions,
    .coupon-actions,
    .coupon-filters,
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
    .coupon-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }

    .coupon-stat-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px 17px;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
    }

    .coupon-stat-icon {
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

    .coupon-stat-label {
        color: var(--text-mute);
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .coupon-stat-value {
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
    .coupon-content {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
    }

    .coupon-content-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 17px 20px;
        border-bottom: 1px solid var(--border);
    }

    .coupon-content-title h2 {
        margin: 0;
        color: var(--text-hi);
        font-size: 16px;
    }

    .coupon-content-title p {
        margin: 4px 0 0;
        color: var(--text-mute);
        font-size: 11px;
    }

    /* FILTERS */
    .coupon-search-wrap {
        position: relative;
        width: 270px;
    }

    .coupon-search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-mute);
        pointer-events: none;
    }

    .coupon-search,
    .coupon-select-filter {
        height: 36px;
        border: 1px solid var(--border);
        border-radius: 8px;
        outline: none;
        background: var(--bg-input);
        color: var(--text-hi);
        font-size: 12px;
    }

    .coupon-search {
        width: 100%;
        padding: 0 12px 0 34px;
    }

    .coupon-select-filter {
        min-width: 135px;
        padding: 0 10px;
    }

    .coupon-search:focus,
    .coupon-select-filter:focus {
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
    .coupon-table-summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 11px 20px;
        border-bottom: 1px solid var(--border);
    }

    .coupon-result-text {
        color: var(--text-mute);
        font-size: 11px;
        font-weight: 600;
    }

    .coupon-result-text strong {
        color: var(--text-hi);
    }

    /* TABLE */
    .coupon-table-wrapper {
        width: 100%;
        overflow-x: auto;
    }

    .coupon-table {
        width: 100%;
        min-width: 1200px;
        border-collapse: collapse;
    }

    .coupon-table th {
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

    .coupon-table td {
        padding: 12px 16px;
        background: transparent;
        border-bottom: 1px solid var(--border-soft);
        color: var(--text-body);
        font-size: 12px;
        vertical-align: middle;
    }

    .coupon-table tbody tr:hover {
        background: var(--bg-hover);
    }

    .coupon-table tbody tr.keyboard-selected {
        outline: 2px solid var(--green);
        outline-offset: -2px;
        background: var(--green-soft);
    }

    .coupon-table tbody tr:last-child td {
        border-bottom: none;
    }

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

    .coupon-code-badge {
        display: inline-block;
        font-family: monospace;
        font-size: 13px;
        font-weight: 800;
        color: var(--green);
        background: var(--green-soft);
        padding: 5px 11px;
        border-radius: 7px;
        border: 1px dashed rgba(16, 185, 129, 0.4);
        letter-spacing: .8px;
    }

    .discount-val {
        color: var(--text-hi);
        font-weight: 800;
        font-size: 13px;
    }

    .discount-type-tag {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 5px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        background: var(--blue-soft);
        color: var(--blue);
    }

    .wholesale-tag {
        display: inline-block;
        padding: 2px 7px;
        border-radius: 4px;
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
        background: var(--purple-soft);
        color: var(--purple);
        border: 1px solid rgba(168, 85, 247, .25);
    }

    /* STATUS */
    .coupon-status {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-height: 25px;
        padding: 0 10px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
    }

    .coupon-status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }

    .coupon-status-active {
        background: var(--green-soft);
        color: var(--green);
    }

    .coupon-status-active .coupon-status-dot {
        background: var(--green);
        box-shadow: 0 0 6px var(--green);
    }

    .coupon-status-inactive {
        background: var(--red-soft);
        color: #f87171;
    }

    .coupon-status-inactive .coupon-status-dot {
        background: #f87171;
    }

    /* ACTIONS */
    .coupon-action {
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

    .coupon-action:hover {
        border-color: var(--green);
        background: var(--green-soft);
        color: var(--green) !important;
    }

    .coupon-action-delete:hover {
        border-color: rgba(239, 68, 68, .5);
        background: var(--red-soft);
        color: var(--red) !important;
    }

    /* EMPTY */
    .coupon-empty,
    .coupon-no-result {
        padding: 65px 20px;
        text-align: center;
    }

    .coupon-empty-icon,
    .coupon-no-result-icon {
        margin-bottom: 12px;
        color: var(--green);
        font-size: 28px;
    }

    .coupon-empty h3,
    .coupon-no-result h3 {
        margin: 0;
        color: var(--text-hi);
        font-size: 16px;
    }

    .coupon-empty p,
    .coupon-no-result p {
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

    .shortcut-help-box.hidden { display: none; }

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

    .modal-backdrop.show { display: flex; }

    .coupon-modal {
        width: min(720px, 100%);
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
        grid-template-columns: 1fr 1fr;
        gap: 16px;
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
        grid-column: 1 / -1;
    }

    .detail-card {
        padding: 12px 14px;
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
        .coupon-stats { grid-template-columns: repeat(2, 1fr); }
        .coupon-content-header { flex-direction: column; align-items: stretch; }
        .coupon-filters { width: 100%; }
        .coupon-search-wrap { width: 100%; }
        .shortcut-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 700px) {
        .coupon-page-header { flex-direction: column; align-items: flex-start; }
        .header-actions { width: 100%; justify-content: flex-start; }
        .coupon-stats { grid-template-columns: 1fr 1fr; }
        .coupon-filters { display: grid; grid-template-columns: 1fr; }
        .coupon-select-filter { width: 100%; }
        .shortcut-grid { grid-template-columns: 1fr; }
        .detail-grid { grid-template-columns: 1fr; }
    }

    @media print {
        html, body, .main, .content { background: #fff !important; color: #111 !important; }
        .coupon-page { max-width: none; }
        .coupon-page-header, .coupon-stats, .coupon-filters, .export-bar, .coupon-actions,
        .shortcut-help-box, .notice, .modal-backdrop, .no-print { display: none !important; }
        .coupon-content { border: 0; }
        .coupon-table { min-width: 0; }
        .coupon-table th, .coupon-table td { color: #111 !important; background: #fff !important; border-color: #ccc !important; }
    }
</style>

<main class="main">
    <section class="content">
        <div class="coupon-page">

            <!-- PAGE HEADER -->
            <div class="coupon-page-header">
                <div>
                    <div class="coupon-breadcrumb">
                        <span>Marketing</span>
                        <span>/</span>
                        <span class="current">Coupons</span>
                    </div>
                    <h1>Coupons Management</h1>
                    <p>Manage promo discounts, usage limits, wholesale restrictions, dates and reports.</p>
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
                    <a href="add.php" class="btn btn-primary" id="addCouponBtn">
                        ＋ Add Coupon <small>A</small>
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
            <div class="coupon-stats">
                <div class="coupon-stat-item">
                    <div class="coupon-stat-icon">#</div>
                    <div>
                        <div class="coupon-stat-label">Total Coupons</div>
                        <div class="coupon-stat-value"><?= $totalCoupons ?></div>
                    </div>
                </div>

                <div class="coupon-stat-item">
                    <div class="coupon-stat-icon">✓</div>
                    <div>
                        <div class="coupon-stat-label">Active</div>
                        <div class="coupon-stat-value"><?= $activeCoupons ?></div>
                    </div>
                </div>

                <div class="coupon-stat-item">
                    <div class="coupon-stat-icon">○</div>
                    <div>
                        <div class="coupon-stat-label">Inactive</div>
                        <div class="coupon-stat-value"><?= $inactiveCoupons ?></div>
                    </div>
                </div>

                <div class="coupon-stat-item">
                    <div class="coupon-stat-icon">★</div>
                    <div>
                        <div class="coupon-stat-label">Wholesale Only</div>
                        <div class="coupon-stat-value"><?= $wholesaleCoupons ?></div>
                    </div>
                </div>
            </div>

            <!-- COUPON CONTENT & TABLE -->
            <div class="coupon-content">
                <div class="coupon-content-header">
                    <div class="coupon-content-title">
                        <h2>Coupon List</h2>
                        <p>Coupon codes, discount values, minimum order amounts, limits and validity periods.</p>
                    </div>

                    <div class="coupon-filters">
                        <!-- REAL-TIME INSTANT SEARCH -->
                        <div class="coupon-search-wrap">
                            <span class="coupon-search-icon">⌕</span>
                            <input
                                type="search"
                                id="couponSearch"
                                class="coupon-search"
                                placeholder="Search coupon code or type..."
                                autocomplete="off">
                        </div>

                        <!-- DISCOUNT TYPE FILTER -->
                        <select id="typeFilter" class="coupon-select-filter">
                            <option value="all">All Types</option>
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed">Fixed Amount ($)</option>
                        </select>

                        <!-- STATUS FILTER -->
                        <select id="couponStatusFilter" class="coupon-select-filter">
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
                <div class="coupon-table-summary">
                    <div class="coupon-result-text">
                        Showing <strong id="visibleCouponCount"><?= $totalCoupons ?></strong> coupons
                    </div>
                    <div class="coupon-result-text">
                        Total: <strong><?= $totalCoupons ?></strong>
                    </div>
                </div>

                <!-- TABLE WRAPPER -->
                <div class="coupon-table-wrapper">
                    <?php if (empty($allCoupons)): ?>
                        <div class="coupon-empty">
                            <div class="coupon-empty-icon">🏷️</div>
                            <h3>No Coupons Found</h3>
                            <p>Create your first discount coupon code to incentivize customers.</p>
                            <a href="add.php" class="btn btn-primary" style="margin-top:16px">
                                ＋ Add First Coupon
                            </a>
                        </div>
                    <?php else: ?>
                        <table class="coupon-table" id="couponTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Coupon Code</th>
                                    <th>Discount</th>
                                    <th>Type</th>
                                    <th>Min Order</th>
                                    <th>Validity</th>
                                    <th>Usage</th>
                                    <th>Target</th>
                                    <th>Status</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $serialNo = 1; 
                                foreach ($allCoupons as $coupon): 
                                ?>
                                    <?php
                                    $couponId         = (int)($coupon['CouponId'] ?? 0);
                                    $code             = (string)($coupon['CouponCode'] ?? '');
                                    $discountType     = (string)($coupon['DiscountType'] ?? 'Percentage');
                                    $discountValue    = (float)($coupon['DiscountValue'] ?? 0);
                                    $minOrder         = (float)($coupon['MinOrderAmount'] ?? 0);
                                    $maxDiscount      = !empty($coupon['MaxDiscountAmount']) ? (float)$coupon['MaxDiscountAmount'] : null;
                                    
                                    // DD/MM/YYYY FORMAT FOR START & END DATE
                                    $startDate        = dateValue($coupon['StartDate'] ?? null, 'd/m/Y');
                                    $endDate          = dateValue($coupon['EndDate'] ?? null, 'd/m/Y');
                                    
                                    $usageLimit       = !empty($coupon['UsageLimit']) ? (int)$coupon['UsageLimit'] : 'Unlimited';
                                    $timesUsed        = (int)($coupon['TimesUsed'] ?? 0);
                                    $isWholesale      = !empty($coupon['IsForWholesaleOnly']);
                                    $isActive         = !empty($coupon['IsActive']);
                                    
                                    // DD/MM/YYYY FORMAT FOR CREATED AT
                                    $createdAt        = dateValue($coupon['CreatedAt'] ?? null, 'd/m/Y, h:i A');

                                    $discountFormatted = (strtolower($discountType) === 'percentage') 
                                        ? number_format($discountValue, 1) . '%' 
                                        : '$' . number_format($discountValue, 2);
                                    ?>
                                    <tr
                                        class="category-row coupon-row"
                                        data-id="<?= $couponId ?>"
                                        data-code="<?= e(strtolower($code)) ?>"
                                        data-type="<?= e(strtolower($discountType)) ?>"
                                        data-status="<?= $isActive ? 'active' : 'inactive' ?>">

                                        <!-- SERIAL NO -->
                                        <td>
                                            <span class="order-box">#<?= $serialNo++ ?></span>
                                        </td>

                                        <!-- COUPON CODE -->
                                        <td>
                                            <span class="coupon-code-badge"><?= e($code) ?></span>
                                        </td>

                                        <!-- DISCOUNT VALUE -->
                                        <td>
                                            <span class="discount-val"><?= $discountFormatted ?></span>
                                        </td>

                                        <!-- DISCOUNT TYPE -->
                                        <td>
                                            <span class="discount-type-tag"><?= e($discountType) ?></span>
                                        </td>

                                        <!-- MIN ORDER -->
                                        <td>
                                            <?= $minOrder > 0 ? '$' . number_format($minOrder, 2) : 'No Min' ?>
                                        </td>

                                        <!-- VALIDITY (DD/MM/YYYY) -->
                                        <td>
                                            <div style="font-size:11px; line-height:1.4;">
                                                <div>From: <?= $startDate ?></div>
                                                <div style="color:var(--text-mute);">To: <?= $endDate ?></div>
                                            </div>
                                        </td>

                                        <!-- USAGE -->
                                        <td>
                                            <div style="font-size:11px;">
                                                <strong><?= $timesUsed ?></strong> / <span style="color:var(--text-mute);"><?= $usageLimit ?></span>
                                            </div>
                                        </td>

                                        <!-- WHOLESALE TAG -->
                                        <td>
                                            <?php if ($isWholesale): ?>
                                                <span class="wholesale-tag">Wholesale Only</span>
                                            <?php else: ?>
                                                <span style="color:var(--text-mute); font-size:11px;">All Customers</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- STATUS -->
                                        <td>
                                            <?php if ($isActive): ?>
                                                <span class="coupon-status coupon-status-active">
                                                    <span class="coupon-status-dot"></span> Active
                                                </span>
                                            <?php else: ?>
                                                <span class="coupon-status coupon-status-inactive">
                                                    <span class="coupon-status-dot"></span> Inactive
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- ACTIONS -->
                                        <td style="text-align:right;">
                                            <div class="coupon-actions" style="justify-content:flex-end;">
                                                <!-- VIEW DETAILS -->
                                                <button
                                                    type="button"
                                                    class="coupon-action detail-btn"
                                                    title="View Coupon Details"
                                                    data-id="<?= $couponId ?>"
                                                    data-code="<?= e($code) ?>"
                                                    data-type="<?= e($discountType) ?>"
                                                    data-val="<?= $discountFormatted ?>"
                                                    data-min="<?= $minOrder > 0 ? '$' . number_format($minOrder, 2) : 'None' ?>"
                                                    data-max="<?= $maxDiscount !== null ? '$' . number_format($maxDiscount, 2) : 'None' ?>"
                                                    data-start="<?= $startDate ?>"
                                                    data-end="<?= $endDate ?>"
                                                    data-limit="<?= $usageLimit ?>"
                                                    data-used="<?= $timesUsed ?>"
                                                    data-target="<?= $isWholesale ? 'Wholesale Accounts Only' : 'General & Wholesale' ?>"
                                                    data-status="<?= $isActive ? 'Active' : 'Inactive' ?>"
                                                    data-created="<?= e($createdAt) ?>">
                                                    ◉
                                                </button>

                                                <!-- EDIT -->
                                                <a
                                                    href="edit.php?id=<?= $couponId ?>"
                                                    class="coupon-action edit-btn"
                                                    title="Edit Coupon">
                                                    ✎
                                                </a>

                                                <!-- DELETE -->
                                                <form
                                                    method="POST"
                                                    action="delete.php"
                                                    class="delete-form"
                                                    style="display:inline">
                                                    <input type="hidden" name="coupon_id" value="<?= $couponId ?>">
                                                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                    <button
                                                        type="submit"
                                                        class="coupon-action coupon-action-delete delete-coupon-btn"
                                                        title="Delete Coupon">
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
                        <div id="couponNoResult" class="category-no-result" style="display:none">
                            <div class="category-no-result-icon">⌕</div>
                            <h3>No matching coupons found</h3>
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
                    <div class="shortcut-item"><span class="shortcut-key">A</span><span class="shortcut-desc">Add Coupon</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">B</span><span class="shortcut-desc">Search / Focus search field</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">C</span><span class="shortcut-desc">Filter Status / Types</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">D</span><span class="shortcut-desc">Delete selected coupon</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">E</span><span class="shortcut-desc">Edit selected coupon</span></div>
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

<!-- COUPON DETAILS MODAL -->
<div class="modal-backdrop" id="couponModal" aria-hidden="true">
    <div class="coupon-modal" role="dialog" aria-modal="true" aria-labelledby="couponModalTitle">
        <div class="modal-header">
            <h3 id="couponModalTitle">Coupon Details</h3>
            <button type="button" class="modal-close" id="modalCloseBtn">×</button>
        </div>

        <div class="modal-body">
            <div class="detail-grid">
                <div class="detail-card">
                    <div class="detail-item">
                        <label>Coupon Code</label>
                        <div id="modalCode" style="font-family:monospace; font-weight:800; font-size:16px; color:var(--green);">—</div>
                    </div>
                </div>

                <div class="detail-card">
                    <div class="detail-item">
                        <label>Discount Value</label>
                        <div id="modalValue" style="font-weight:800; font-size:16px; color:var(--text-hi);">—</div>
                    </div>
                </div>

                <div class="detail-card">
                    <div class="detail-item">
                        <label>Discount Type</label>
                        <div id="modalType">—</div>
                    </div>
                </div>

                <div class="detail-card">
                    <div class="detail-item">
                        <label>Status</label>
                        <div id="modalStatus">—</div>
                    </div>
                </div>

                <div class="detail-card">
                    <div class="detail-item">
                        <label>Minimum Order Amount</label>
                        <div id="modalMin">—</div>
                    </div>
                </div>

                <div class="detail-card">
                    <div class="detail-item">
                        <label>Maximum Discount Cap</label>
                        <div id="modalMax">—</div>
                    </div>
                </div>

                <div class="detail-card">
                    <div class="detail-item">
                        <label>Validity Period (d/m/Y)</label>
                        <div id="modalValidity">—</div>
                    </div>
                </div>

                <div class="detail-card">
                    <div class="detail-item">
                        <label>Usage Statistics</label>
                        <div id="modalUsage">—</div>
                    </div>
                </div>

                <div class="detail-card detail-full">
                    <div class="detail-item">
                        <label>Audience Restriction</label>
                        <div id="modalTarget">—</div>
                    </div>
                </div>

                <div class="detail-card detail-full">
                    <div class="detail-item">
                        <label>Created Timestamp</label>
                        <div id="modalCreated">—</div>
                    </div>
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
        const searchInput    = document.getElementById('couponSearch');
        const typeFilter     = document.getElementById('typeFilter');
        const statusFilter   = document.getElementById('couponStatusFilter');
        const table          = document.getElementById('couponTable');
        const countElement   = document.getElementById('visibleCouponCount');
        const noResult       = document.getElementById('couponNoResult');
        const shortcutBox    = document.getElementById('shortcutHelpBox');
        const modal          = document.getElementById('couponModal');

        function rows() {
            return table ? Array.from(table.querySelectorAll('tbody .coupon-row')) : [];
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
        function filterCoupons() {
            if (!table) return;

            const q = (searchInput?.value || '').toLowerCase().trim();
            const type = (typeFilter?.value || 'all');
            const status = (statusFilter?.value || 'all');

            let count = 0;

            rows().forEach(function(row) {
                const code    = row.dataset.code || '';
                const rowType = row.dataset.type || '';
                const rowStatus = row.dataset.status || '';

                const textMatch   = (!q || code.includes(q) || rowType.includes(q));
                const typeMatch   = (type === 'all' || rowType === type);
                const statusMatch = (status === 'all' || rowStatus === status);

                if (textMatch && typeMatch && statusMatch) {
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
                    'Coupon Code': row.querySelector('.coupon-code-badge')?.innerText.trim() || '',
                    'Discount': row.querySelector('.discount-val')?.innerText.trim() || '',
                    'Type': row.querySelector('.discount-type-tag')?.innerText.trim() || '',
                    'Status': row.dataset.status === 'active' ? 'Active' : 'Inactive'
                };
            });

            if (window.XLSX) {
                const ws = XLSX.utils.json_to_sheet(data);
                ws['!cols'] = [{wch: 8}, {wch: 24}, {wch: 16}, {wch: 16}, {wch: 14}];
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Coupons');
                XLSX.writeFile(wb, 'coupons-' + new Date().toISOString().slice(0, 10) + '.xlsx');
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
                    row.querySelector('.coupon-code-badge')?.innerText.trim() || '',
                    row.querySelector('.discount-val')?.innerText.trim() || '',
                    row.querySelector('.discount-type-tag')?.innerText.trim() || '',
                    row.dataset.status === 'active' ? 'Active' : 'Inactive'
                ];
            });

            const doc = new jspdf.jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
            doc.setFontSize(16);
            doc.text('GatewayLinen - Coupons Directory', 14, 14);
            doc.setFontSize(9);
            doc.text('Generated: ' + new Date().toLocaleString(), 14, 20);

            if (typeof doc.autoTable === 'function') {
                doc.autoTable({
                    startY: 25,
                    head: [['#', 'Coupon Code', 'Discount', 'Type', 'Status']],
                    body: body,
                    styles: { fontSize: 8, cellPadding: 3 },
                    headStyles: { fontSize: 8 }
                });
            }

            doc.save('coupons-' + new Date().toISOString().slice(0, 10) + '.pdf');
        }

        /*
        |--------------------------------------------------------------------------
        | MODAL DETAILS
        |--------------------------------------------------------------------------
        */
        function openModal(btn) {
            document.getElementById('modalCode').textContent = btn.dataset.code || '—';
            document.getElementById('modalValue').textContent = btn.dataset.val || '—';
            document.getElementById('modalType').textContent = btn.dataset.type || '—';
            document.getElementById('modalStatus').textContent = btn.dataset.status || '—';
            document.getElementById('modalMin').textContent = btn.dataset.min || 'None';
            document.getElementById('modalMax').textContent = btn.dataset.max || 'None';
            document.getElementById('modalValidity').textContent = btn.dataset.start + ' to ' + btn.dataset.end;
            document.getElementById('modalUsage').textContent = btn.dataset.used + ' times used (Limit: ' + btn.dataset.limit + ')';
            document.getElementById('modalTarget').textContent = btn.dataset.target || 'All';
            document.getElementById('modalCreated').textContent = btn.dataset.created || '—';

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
        document.querySelectorAll('.delete-coupon-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const row = btn.closest('.coupon-row');
                const code = row?.querySelector('.coupon-code-badge')?.innerText.trim() || 'this coupon';
                const confirmed = confirm('Delete coupon code "' + code + '"?\n\nThis action cannot be undone.');
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

        searchInput?.addEventListener('input', filterCoupons);
        typeFilter?.addEventListener('change', filterCoupons);
        statusFilter?.addEventListener('change', filterCoupons);

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
                document.getElementById('addCouponBtn')?.click();
            } else if (key === 'B') {
                searchInput?.focus();
                searchInput?.select();
            } else if (key === 'C') {
                typeFilter?.focus();
            } else if (key === 'D') {
                const sel = document.querySelector('.coupon-row.keyboard-selected') || visibleRows()[0];
                sel?.querySelector('.delete-coupon-btn')?.click();
            } else if (key === 'E') {
                const sel = document.querySelector('.coupon-row.keyboard-selected') || visibleRows()[0];
                sel?.querySelector('.edit-btn')?.click();
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
                    filterCoupons();
                }
                searchInput?.blur();
            }
        }, true);

        filterCoupons();
    });
})();
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>