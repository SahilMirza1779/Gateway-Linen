<?php
session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$activeMenu = 'coupons';
$pageTitle  = 'GatewayLinen | Coupons';

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] = $_SESSION['admin_username'] ?? 'GatewayLinen Administrator';
}
if (!isset($_SESSION['admin_role'])) {
    $_SESSION['admin_role'] = 'Administrator';
}

if (empty($_SESSION['coupon_delete_token'])) {
    $_SESSION['coupon_delete_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['coupon_delete_token'];

$actionMessage = trim((string)($_GET['success'] ?? ''));
$actionError   = trim((string)($_GET['error'] ?? ''));

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function dateValue($value, $format = 'd M Y'): string
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

/* --------------------------------------------------------------------------
   LOAD COUPONS
   -------------------------------------------------------------------------- */
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
    $queryError = 'Unable to load coupons right now.';
}

$totalCoupons = count($allCoupons);
$activeCoupons = 0;
$inactiveCoupons = 0;
$wholesaleCoupons = 0;

foreach ($allCoupons as $coupon) {
    if (!empty($coupon['IsActive'])) $activeCoupons++;
    else $inactiveCoupons++;
    if (!empty($coupon['IsForWholesaleOnly'])) $wholesaleCoupons++;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<style>
.coupon-page,
.coupon-page * { box-sizing: border-box; }

.coupon-page {
    width: 100%;
    max-width: 1600px;
    margin: 0 auto;
    padding: 0;
    font-size: 13px;

    --coup-page: #f3f6fa;
    --coup-card: #ffffff;
    --coup-card-alt: #f8fafc;
    --coup-input: #ffffff;
    --coup-border: #dce4ec;
    --coup-border-soft: #e8edf3;
    --coup-text: #162334;
    --coup-body: #536579;
    --coup-muted: #7b8da1;
    --coup-green: #059669;
    --coup-green-soft: rgba(5,150,105,.10);
    --coup-red: #dc2626;
    --coup-red-soft: rgba(220,38,38,.09);
    --coup-blue: #0284c7;
    --coup-blue-soft: rgba(2,132,199,.09);
    --coup-shadow: 0 5px 18px rgba(15,23,42,.05);
}

html[data-theme="dark"] .coupon-page,
body[data-theme="dark"] .coupon-page,
html.dark .coupon-page,
body.dark .coupon-page,
html.dark-mode .coupon-page,
body.dark-mode .coupon-page {
    --coup-page: #0a1119;
    --coup-card: #111b26;
    --coup-card-alt: #0f1823;
    --coup-input: #0d1620;
    --coup-border: #1e2d3d;
    --coup-border-soft: #182636;
    --coup-text: #f0f4f8;
    --coup-body: #a8b8c8;
    --coup-muted: #6f8295;
    --coup-green: #10b981;
    --coup-green-soft: rgba(16,185,129,.12);
    --coup-red: #ef4444;
    --coup-red-soft: rgba(239,68,68,.12);
    --coup-blue: #38bdf8;
    --coup-blue-soft: rgba(56,189,248,.12);
    --coup-shadow: none;
}

.coupon-page { background: var(--coup-page); color: var(--coup-body); }

.coupon-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 28px;
    margin: 0 0 18px;
    padding: 0 0 16px;
    border-bottom: 1px solid var(--coup-border);
}

.coupon-breadcrumb {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 7px;
    color: var(--coup-muted);
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .45px;
}
.coupon-breadcrumb .current { color: var(--coup-green); }

.coupon-page-header h1 { margin: 0; color: var(--coup-text); font-size: 30px; font-weight: 900; letter-spacing: -.5px; }
.coupon-page-header p { margin: 7px 0 0; color: var(--coup-muted); font-size: 13px; font-weight: 600; }

.header-actions { display: flex; align-items: center; gap: 10px; }

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 44px;
    padding: 0 15px;
    border: 1px solid var(--coup-border);
    border-radius: 9px;
    background: var(--coup-card);
    color: var(--coup-text) !important;
    font-size: 13px;
    font-weight: 900;
    text-decoration: none;
    cursor: pointer;
    transition: .16s ease;
}
.btn:hover { border-color: var(--coup-green); background: var(--coup-green-soft); color: var(--coup-green) !important; }

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
body.dark .btn-primary {
    background: #111b26;
    border: 1px solid #1e2d3d;
    color: #f0f4f8 !important;
    box-shadow: none;
}
html[data-theme="dark"] .btn-primary:hover,
body[data-theme="dark"] .btn-primary:hover,
html.dark .btn-primary:hover,
body.dark .btn-primary:hover {
    border-color: #10b981;
    background: rgba(16,185,129,.12);
    color: #10b981 !important;
}

.key-hint, .btn small {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 21px; height: 21px; padding: 0 4px;
    border: 1px solid currentColor; border-radius: 4px; font: 800 9px/1 monospace; opacity: .9;
}

.coupon-stats { display: grid; grid-template-columns: repeat(4,minmax(0,1fr)); gap: 14px; margin-bottom: 16px; }
.coupon-stat-item {
    display: flex; align-items: center; gap: 13px; min-height: 82px; padding: 14px 17px;
    background: var(--coup-card); border: 1px solid var(--coup-border); border-radius: 11px; box-shadow: var(--coup-shadow);
}
.coupon-stat-icon {
    display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; flex: 0 0 40px;
    border-radius: 10px; background: var(--coup-green-soft); color: var(--coup-green); font-size: 17px; font-weight: 900;
}
.coupon-stat-label { color: var(--coup-muted); font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .45px; }
.coupon-stat-value { margin-top: 4px; color: var(--coup-text); font-size: 24px; font-weight: 900; line-height: 1; }

.notice { margin-bottom: 12px; padding: 12px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; }
.notice-success { border: 1px solid rgba(5,150,105,.25); background: var(--coup-green-soft); color: var(--coup-green); }
.notice-error { border: 1px solid rgba(220,38,38,.25); background: var(--coup-red-soft); color: var(--coup-red); }

.coupon-content { background: var(--coup-card); border: 1px solid var(--coup-border); border-radius: 12px; overflow: hidden; box-shadow: var(--coup-shadow); }
.coupon-content-header { display: flex; align-items: center; justify-content: space-between; gap: 28px; padding: 20px 24px; min-height: 104px; border-bottom: 1px solid var(--coup-border); }
.coupon-content-title h2 { margin: 0; color: var(--coup-text); font-size: 22px; font-weight: 900; }
.coupon-content-title p { margin: 6px 0 0; color: var(--coup-muted); font-size: 11px; font-weight: 600; line-height: 1.5; }

.coupon-filters { display: flex; align-items: center; gap: 10px; flex: 1 1 auto; justify-content: flex-end; }
.coupon-search-wrap { position: relative; width: min(420px, 100%); flex: 1 1 300px; }
.coupon-search-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--coup-muted); pointer-events: none; font-size: 17px; }

.coupon-search, .coupon-status-filter {
    height: 54px; border: 1px solid var(--coup-border); border-radius: 10px; outline: none; background: var(--coup-input); color: var(--coup-text); font-size: 14px; font-weight: 600;
}
.coupon-search { width: 100%; padding: 0 16px 0 46px; }
.coupon-status-filter { min-width: 170px; padding: 0 14px; }
.coupon-search::placeholder { color: var(--coup-muted); font-size: 14px; font-weight: 500; }
.coupon-search:focus, .coupon-status-filter:focus { border-color: var(--coup-green); box-shadow: 0 0 0 3px var(--coup-green-soft); }

.coupon-table-summary { display: flex; align-items: center; justify-content: space-between; padding: 12px 24px; border-bottom: 1px solid var(--coup-border); }
.coupon-result-text { color: var(--coup-muted); font-size: 11px; font-weight: 700; }
.coupon-result-text strong { color: var(--coup-text); }

.coupon-table-wrapper { width: 100%; overflow-x: auto; }
.coupon-table { width: 100%; border-collapse: collapse; min-width: 1000px; }
.coupon-table th {
    height: 48px; padding: 0 16px; background: var(--coup-card-alt); border-bottom: 1px solid var(--coup-border);
    color: var(--coup-muted); font-size: 10px; font-weight: 900; text-align: left; text-transform: uppercase; letter-spacing: .55px; white-space: nowrap;
}
.coupon-table td { padding: 15px 16px; background: transparent; border-bottom: 1px solid var(--coup-border-soft); color: var(--coup-body); font-size: 12px; line-height: 1.45; vertical-align: middle; }
.coupon-table tbody tr { cursor: pointer; }
.coupon-table tbody tr:hover { background: var(--coup-green-soft); }
.coupon-table tbody tr.keyboard-selected { outline: 2px solid var(--coup-green); outline-offset: -2px; background: var(--coup-green-soft); }

.order-box {
    display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 30px; padding: 0 8px;
    border-radius: 7px; background: var(--coup-input); border: 1px solid var(--coup-border); color: var(--coup-green); font-size: 11px; font-weight: 900;
}
.coupon-code-badge {
    display: inline-block; font-family: monospace; font-size: 13px; font-weight: 900; color: var(--coup-green);
    background: var(--coup-green-soft); padding: 5px 10px; border-radius: 7px; border: 1px dashed rgba(5,150,105,0.4); letter-spacing: .5px;
}
.discount-type-tag {
    display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 10px; font-weight: 800; text-transform: uppercase;
    background: var(--coup-blue-soft); color: var(--coup-blue);
}
.wholesale-tag {
    display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 9px; font-weight: 800; text-transform: uppercase;
    background: rgba(168,85,247,.12); color: #a855f7; border: 1px solid rgba(168,85,247,.25);
}

.coupon-status { display: inline-flex; align-items: center; gap: 6px; min-height: 28px; padding: 0 10px; border-radius: 20px; font-size: 10px; font-weight: 800; white-space: nowrap; }
.coupon-status-dot { width: 6px; height: 6px; border-radius: 50%; }
.coupon-status-active { background: var(--coup-green-soft); color: var(--coup-green); }
.coupon-status-active .coupon-status-dot { background: var(--coup-green); box-shadow: 0 0 6px var(--coup-green); }
.coupon-status-inactive { background: var(--coup-red-soft); color: var(--coup-red); }
.coupon-status-inactive .coupon-status-dot { background: var(--coup-red); }

.coupon-actions { display: flex; align-items: center; gap: 5px; flex-wrap: nowrap; }
.coupon-action {
    display: inline-flex; align-items: center; justify-content: center; gap: 3px; width: 36px; height: 36px;
    border: 1px solid var(--coup-border); border-radius: 8px; background: var(--coup-card); color: var(--coup-body) !important;
    text-decoration: none; cursor: pointer; font-size: 13px;
}
.coupon-action:hover { border-color: var(--coup-green); background: var(--coup-green-soft); color: var(--coup-green) !important; }
.coupon-action-delete:hover { border-color: rgba(220,38,38,.4); background: var(--coup-red-soft); color: var(--coup-red) !important; }
.coupon-action .key-hint { min-width: 16px; height: 16px; font-size: 8px; }

.coupon-empty, .coupon-no-result { padding: 65px 20px; text-align: center; }
.coupon-empty-icon, .coupon-no-result-icon { margin-bottom: 12px; color: var(--coup-green); font-size: 32px; }
.coupon-empty h3, .coupon-no-result h3 { margin: 0; color: var(--coup-text); font-size: 16px; font-weight: 900; }
.coupon-empty p, .coupon-no-result p { margin: 6px 0 0; color: var(--coup-muted); font-size: 11px; }

.coupon-pagination-bar { display: flex; align-items: center; justify-content: space-between; gap: 15px; padding: 14px 18px; background: var(--coup-card); border-top: 1px solid var(--coup-border); min-height: 68px; }
.coupon-page-info { color: var(--coup-muted); font-size: 11px; font-weight: 700; }
.coupon-page-controls { display: flex; align-items: center; gap: 6px; }
.page-size-wrap { display: flex; align-items: center; gap: 8px; margin-right: 10px; color: var(--coup-muted); font-size: 10px; font-weight: 800; }
.page-size-select, .pagination-btn { height: 42px; border: 1px solid var(--coup-border); border-radius: 8px; background: var(--coup-card); color: var(--coup-text); font-size: 12px; font-weight: 800; outline: none; }
.page-size-select { min-width: 78px; padding: 0 10px; }
.pagination-btn { min-width: 42px; padding: 0 10px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
.pagination-btn:hover:not(:disabled), .pagination-btn.active { background: var(--coup-green); border-color: var(--coup-green); color: #fff !important; }
.pagination-btn:disabled { opacity: .4; cursor: not-allowed; }
.pagination-ellipsis { min-width: 26px; text-align: center; color: var(--coup-muted); font-size: 13px; }
.dataset-note { margin-top: 4px; color: var(--coup-muted); font-size: 10px; }

/* ==========================================================================
   MODAL DIALOG STYLING
   ========================================================================== */
.modal-backdrop {
    position: fixed; inset: 0; z-index: 99999; display: none; align-items: center; justify-content: center;
    padding: 20px; background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(4px);
}
.modal-backdrop.show { display: flex; }

.coupon-modal {
    width: min(750px, 100%); max-height: 90vh; overflow-y: auto; 
    background: #ffffff !important; color: #162334 !important;
    border: 1px solid #dce4ec; border-radius: 14px; box-shadow: 0 25px 75px rgba(0, 0, 0, 0.50);
    display: flex; flex-direction: column;
}

.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 18px 24px; border-bottom: 1px solid #dce4ec; background: #f8fafc !important; }
.modal-header h3 { margin: 0; color: #162334 !important; font-size: 18px; font-weight: 900; }
.modal-close { border: 0; background: transparent; color: #7b8da1; font-size: 26px; font-weight: 700; cursor: pointer; transition: color .15s; }
.modal-close:hover { color: #dc2626; }

.modal-body { padding: 28px; overflow-y: auto; background: #ffffff !important; color: #162334 !important; }
.detail-info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
.detail-item { display: flex; flex-direction: column; gap: 5px; }
.detail-item.full-width { grid-column: 1 / -1; }
.detail-item label { color: #7b8da1 !important; font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: .5px; }
.detail-item .val { color: #162334 !important; font-size: 13px; font-weight: 700; word-break: break-word; }

.modal-footer { display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding: 14px 24px; border-top: 1px solid #dce4ec; background: #f8fafc !important; }
.modal-footer .btn { background: #ffffff !important; color: #162334 !important; border-color: #dce4ec !important; }
.modal-footer .btn:hover { background: rgba(5,150,105,.10) !important; color: #059669 !important; border-color: #059669 !important; }

@media print {
    body * { visibility: hidden; }
    .coupon-content, .coupon-content *, .coupon-table, .coupon-table * { visibility: visible; }
    .coupon-content { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none !important; border: none !important; }
    .coupon-content-header, .coupon-filters, .coupon-table-summary, .coupon-pagination-bar, .coupon-actions th:last-child, .coupon-actions td:last-child, .key-hint { display: none !important; }
}
</style>

<main class="main">
    <section class="content">
        <div class="coupon-page">

            <div class="coupon-page-header">
                <div>
                    <div class="coupon-breadcrumb">
                        <span>Marketing</span><span>/</span><span class="current">Coupons</span>
                    </div>
                    <h1>Coupons Management</h1>
                    <p>Manage promo discount codes, limitations, wholesale restrictions, and status.</p>
                </div>

                <div class="header-actions">
                    <button type="button" class="btn" id="printBtn" title="Print (P)">🖨 Print <small>P</small></button>
                    <button type="button" class="btn" id="pdfBtn" title="PDF (V)">↓ PDF <small>V</small></button>
                    <button type="button" class="btn" id="excelBtn" title="Excel (X)">↓ Excel <small>X</small></button>
                    <a href="add.php" class="btn btn-primary" id="addCouponBtn" title="Add Coupon (A)">＋ Add Coupon <small>A</small></a>
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

            <div class="coupon-stats">
                <div class="coupon-stat-item"><div class="coupon-stat-icon">#</div><div><div class="coupon-stat-label">Total Coupons</div><div class="coupon-stat-value"><?= $totalCoupons ?></div></div></div>
                <div class="coupon-stat-item"><div class="coupon-stat-icon">✓</div><div><div class="coupon-stat-label">Active</div><div class="coupon-stat-value"><?= $activeCoupons ?></div></div></div>
                <div class="coupon-stat-item"><div class="coupon-stat-icon">○</div><div><div class="coupon-stat-label">Inactive</div><div class="coupon-stat-value"><?= $inactiveCoupons ?></div></div></div>
                <div class="coupon-stat-item"><div class="coupon-stat-icon">★</div><div><div class="coupon-stat-label">Wholesale Only</div><div class="coupon-stat-value"><?= $wholesaleCoupons ?></div></div></div>
            </div>

            <div class="coupon-content">
                <div class="coupon-content-header">
                    <div class="coupon-content-title">
                        <h2>Coupon List</h2>
                        <p>Code, discount type, value, minimum order, usage counts, and validity dates.</p>
                    </div>

                    <div class="coupon-filters">
                        <div class="coupon-search-wrap">
                            <span class="coupon-search-icon">⌕</span>
                            <input type="search" id="couponSearch" class="coupon-search" placeholder="Search coupon code... (B)" autocomplete="off">
                        </div>
                        <select id="couponTypeFilter" class="coupon-status-filter">
                            <option value="all">All Types</option>
                            <option value="percentage">Percentage</option>
                            <option value="fixed">Fixed Amount</option>
                        </select>
                        <select id="couponStatusFilter" class="coupon-status-filter">
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="coupon-table-summary">
                    <div class="coupon-result-text">Showing <strong id="visibleCouponCount"><?= $totalCoupons ?></strong> coupons</div>
                    <div class="coupon-result-text">Total: <strong><?= $totalCoupons ?></strong></div>
                </div>

                <div class="coupon-table-wrapper">
                    <?php if (empty($allCoupons)): ?>
                        <div class="coupon-empty">
                            <div class="coupon-empty-icon">🏷️</div>
                            <h3>No Coupons Found</h3>
                            <p>Create your first discount coupon code to incentivize customers.</p>
                            <a href="add.php" class="btn btn-primary" style="margin-top:14px">＋ Add First Coupon</a>
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
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php $no = 1; foreach ($allCoupons as $coupon): ?>
                                <?php
                                $couponId      = (int)$coupon['CouponId'];
                                $code          = (string)$coupon['CouponCode'];
                                $discountType  = (string)($coupon['DiscountType'] ?? 'Percentage');
                                $discountValue = (float)($coupon['DiscountValue'] ?? 0);
                                $minOrder      = (float)($coupon['MinOrderAmount'] ?? 0);
                                $maxDiscount   = !empty($coupon['MaxDiscountAmount']) ? (float)$coupon['MaxDiscountAmount'] : null;
                                $startDate     = dateValue($coupon['StartDate'] ?? null, 'd M Y');
                                $endDate       = dateValue($coupon['EndDate'] ?? null, 'd M Y');
                                $usageLimit    = !empty($coupon['UsageLimit']) ? (int)$coupon['UsageLimit'] : 'Unlimited';
                                $timesUsed     = (int)($coupon['TimesUsed'] ?? 0);
                                $isWholesale   = !empty($coupon['IsForWholesaleOnly']);
                                $isActive      = !empty($coupon['IsActive']);
                                $createdAt     = dateValue($coupon['CreatedAt'] ?? null, 'd M Y, h:i A');

                                $discountFormatted = (strtolower($discountType) === 'percentage') 
                                    ? number_format($discountValue, 1) . '%' 
                                    : '$' . number_format($discountValue, 2);
                                $currentNo = $no++;
                                ?>
                                <tr class="coupon-row" data-id="<?= $couponId ?>" data-status="<?= $isActive ? 'active' : 'inactive' ?>" data-type="<?= e(strtolower($discountType)) ?>" data-code="<?= e(strtolower($code)) ?>">
                                    <td><span class="order-box"><?= $currentNo ?></span></td>
                                    <td><span class="coupon-code-badge"><?= e($code) ?></span></td>
                                    <td><strong><?= $discountFormatted ?></strong></td>
                                    <td><span class="discount-type-tag"><?= e($discountType) ?></span></td>
                                    <td><?= $minOrder > 0 ? '$' . number_format($minOrder, 2) : '—' ?></td>
                                    <td>
                                        <div style="font-size:11px;">
                                            <div>From: <?= $startDate ?></div>
                                            <div style="color:var(--coup-muted);">To: <?= $endDate ?></div>
                                        </div>
                                    </td>
                                    <td><strong><?= $timesUsed ?></strong> / <span style="color:var(--coup-muted);"><?= $usageLimit ?></span></td>
                                    <td>
                                        <?php if ($isWholesale): ?>
                                            <span class="wholesale-tag">Wholesale Only</span>
                                        <?php else: ?>
                                            <span style="color:var(--coup-muted);font-size:11px;">General</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isActive): ?>
                                            <span class="coupon-status coupon-status-active"><span class="coupon-status-dot"></span>Active</span>
                                        <?php else: ?>
                                            <span class="coupon-status coupon-status-inactive"><span class="coupon-status-dot"></span>Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="coupon-actions">
                                            <button type="button" class="coupon-action detail-btn" title="View details"
                                                data-code="<?= e($code) ?>"
                                                data-type="<?= e($discountType) ?>"
                                                data-value="<?= e($discountFormatted) ?>"
                                                data-min="<?= $minOrder > 0 ? '$' . number_format($minOrder, 2) : 'None' ?>"
                                                data-max="<?= $maxDiscount !== null ? '$' . number_format($maxDiscount, 2) : 'None' ?>"
                                                data-validity="<?= e($startDate) ?> to <?= e($endDate) ?>"
                                                data-usage="<?= $timesUsed ?> / <?= $usageLimit ?>"
                                                data-target="<?= $isWholesale ? 'Wholesale Accounts Only' : 'General & Wholesale' ?>"
                                                data-status="<?= $isActive ? 'Active' : 'Inactive' ?>"
                                                data-created="<?= e($createdAt) ?>">◉</button>

                                            <a href="edit.php?id=<?= $couponId ?>" class="coupon-action edit-btn" title="Edit Coupon (E)">✎<span class="key-hint">E</span></a>

                                            <form method="POST" action="delete.php" class="delete-form" style="display:inline">
                                                <input type="hidden" name="coupon_id" value="<?= $couponId ?>">
                                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                <button type="submit" class="coupon-action coupon-action-delete delete-coupon-btn" title="Delete Coupon (D)">×<span class="key-hint">D</span></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div id="couponNoResult" class="coupon-no-result" style="display:none">
                            <div class="coupon-no-result-icon">⌕</div>
                            <h3>No matching coupons</h3>
                            <p>Try changing your search or status filter.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="coupon-pagination-bar" id="couponPaginationBar">
                    <div>
                        <div class="coupon-page-info" id="couponPageInfo">Showing 0-0 of 0 coupons</div>
                        <div class="dataset-note">Use Search and page controls to manage large records.</div>
                    </div>

                    <div class="coupon-page-controls">
                        <div class="page-size-wrap">
                            <span>Show</span>
                            <select id="couponPageSize" class="page-size-select">
                                <option value="25">25</option>
                                <option value="50" selected>50</option>
                                <option value="100">100</option>
                                <option value="200">200</option>
                            </select>
                            <span>coupons</span>
                        </div>

                        <button type="button" class="pagination-btn" id="couponFirstPage" title="First page">«</button>
                        <button type="button" class="pagination-btn" id="couponPrevPage" title="Previous page">‹</button>
                        <span id="couponPageNumbers"></span>
                        <button type="button" class="pagination-btn" id="couponNextPage" title="Next page">›</button>
                        <button type="button" class="pagination-btn" id="couponLastPage" title="Last page">»</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- DETAILS MODAL -->
<div class="modal-backdrop" id="couponModal" aria-hidden="true">
    <div class="coupon-modal" role="dialog" aria-modal="true" aria-labelledby="couponModalTitle">
        <div class="modal-header">
            <h3 id="couponModalTitle">Coupon Details</h3>
            <button type="button" class="modal-close" id="modalCloseBtn">×</button>
        </div>
        <div class="modal-body">
            <div class="detail-info-grid">
                <div class="detail-item">
                    <label>Coupon Code</label>
                    <div class="val" id="detailCode" style="font-family:monospace;font-weight:900;color:var(--coup-green);font-size:15px;">—</div>
                </div>
                <div class="detail-item">
                    <label>Discount Value</label>
                    <div class="val" id="detailValue">—</div>
                </div>
                <div class="detail-item">
                    <label>Discount Type</label>
                    <div class="val" id="detailType">—</div>
                </div>
                <div class="detail-item">
                    <label>Status</label>
                    <div class="val" id="detailStatus">—</div>
                </div>
                <div class="detail-item">
                    <label>Minimum Order Amount</label>
                    <div class="val" id="detailMin">—</div>
                </div>
                <div class="detail-item">
                    <label>Maximum Discount Cap</label>
                    <div class="val" id="detailMax">—</div>
                </div>
                <div class="detail-item">
                    <label>Validity Period</label>
                    <div class="val" id="detailValidity">—</div>
                </div>
                <div class="detail-item">
                    <label>Usage Tracking</label>
                    <div class="val" id="detailUsage">—</div>
                </div>
                <div class="detail-item full-width">
                    <label>Audience Target</label>
                    <div class="val" id="detailTarget">—</div>
                </div>
                <div class="detail-item full-width">
                    <label>Created Date</label>
                    <div class="val" id="detailCreated">—</div>
                </div>
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
        const searchInput   = document.getElementById('couponSearch'); 
        const typeFilter    = document.getElementById('couponTypeFilter');
        const statusFilter  = document.getElementById('couponStatusFilter'); 
        const table         = document.getElementById('couponTable'); 
        const countElement  = document.getElementById('visibleCouponCount'); 
        const noResult      = document.getElementById('couponNoResult'); 
        const modal         = document.getElementById('couponModal'); 
 
        const pageInfo      = document.getElementById('couponPageInfo'); 
        const pageNumbers   = document.getElementById('couponPageNumbers'); 
        const pageSizeSelect= document.getElementById('couponPageSize'); 
        const firstPageBtn  = document.getElementById('couponFirstPage'); 
        const prevPageBtn   = document.getElementById('couponPrevPage'); 
        const nextPageBtn   = document.getElementById('couponNextPage'); 
        const lastPageBtn   = document.getElementById('couponLastPage'); 
 
        let currentPage = 1; 
        let pageSize = parseInt(pageSizeSelect?.value || '50', 10); 
 
        function rows(){ 
            return table ? Array.from(table.querySelectorAll('tbody .coupon-row')) : []; 
        } 
 
        function visibleRows(){ 
            return rows().filter(row => row.style.display !== 'none'); 
        } 
 
        function matchingRows(){
            const q = (searchInput?.value || '').toLowerCase().trim();
            const type = typeFilter?.value || 'all';
            const status = statusFilter?.value || 'all';

            return rows().filter(row => {
                const codeMatch = !q || (row.dataset.code || '').includes(q);
                const typeMatch = type === 'all' || row.dataset.type === type;
                const statusMatch = status === 'all' || row.dataset.status === status;
                return codeMatch && typeMatch && statusMatch;
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
                first.type = 'button'; first.className = 'pagination-btn'; first.textContent = '1'; 
                first.addEventListener('click', () => { currentPage = 1; renderPagination(); }); 
                pageNumbers.appendChild(first); 
                if (start > 2) { 
                    const dots = document.createElement('span'); dots.className = 'pagination-ellipsis'; dots.textContent = '…'; 
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
                    const dots = document.createElement('span'); dots.className = 'pagination-ellipsis'; dots.textContent = '…'; 
                    pageNumbers.appendChild(dots); 
                } 
                const last = document.createElement('button'); 
                last.type = 'button'; last.className = 'pagination-btn'; last.textContent = String(totalPages); 
                last.addEventListener('click', () => { currentPage = totalPages; renderPagination(); }); 
                pageNumbers.appendChild(last); 
            } 
        } 
 
        function renderPagination(){ 
            if (!table) return; 
            const matched = matchingRows(); 
            const total = matched.length; 
            const totalPages = Math.max(1, Math.ceil(total / pageSize)); 
 
            if (currentPage > totalPages) currentPage = totalPages; 
            if (currentPage < 1) currentPage = 1; 
 
            rows().forEach(r => { r.style.display = 'none'; }); 
 
            const startIndex = (currentPage - 1) * pageSize; 
            const pageRows = matched.slice(startIndex, startIndex + pageSize); 
 
            pageRows.forEach(row => { row.style.display = ''; }); 
 
            const firstItem = total === 0 ? 0 : startIndex + 1; 
            const lastItem = Math.min(startIndex + pageSize, total); 
 
            if (pageInfo) pageInfo.textContent = 'Showing ' + firstItem + '–' + lastItem + ' of ' + total + ' coupons'; 
            if (countElement) countElement.textContent = total; 
            if (noResult) noResult.style.display = total === 0 ? 'block' : 'none'; 
 
            if (firstPageBtn) firstPageBtn.disabled = currentPage <= 1; 
            if (prevPageBtn) prevPageBtn.disabled = currentPage <= 1; 
            if (nextPageBtn) nextPageBtn.disabled = currentPage >= totalPages || total === 0; 
            if (lastPageBtn) lastPageBtn.disabled = currentPage >= totalPages || total === 0; 
 
            renderPageNumbers(totalPages); 
 
            rows().forEach(r => r.classList.remove('keyboard-selected')); 
            const firstVisible = pageRows[0]; 
            if (firstVisible) firstVisible.classList.add('keyboard-selected'); 
        } 
 
        function filterCoupons(resetPage){ 
            if (resetPage !== false) currentPage = 1; 
            renderPagination(); 
        } 
 
        function printCoupons(){ window.print(); } 
        function excelCoupons(){ 
            const data = matchingRows().map(row => ({ 
                'Coupon Code': row.querySelector('.coupon-code-badge')?.innerText.trim() || '', 
                'Discount': row.querySelector('td:nth-child(3)')?.innerText.trim() || '', 
                'Type': row.querySelector('.discount-type-tag')?.innerText.trim() || '', 
                'Min Order': row.querySelector('td:nth-child(5)')?.innerText.trim() || '', 
                'Status': row.dataset.status === 'active' ? 'Active' : 'Inactive' 
            })); 
            if (!window.XLSX) return alert('Excel library is not loaded.'); 
            const ws = XLSX.utils.json_to_sheet(data); 
            const wb = XLSX.utils.book_new(); 
            XLSX.utils.book_append_sheet(wb, ws, 'Coupons'); 
            XLSX.writeFile(wb, 'coupons-' + new Date().toISOString().slice(0,10) + '.xlsx'); 
        } 
 
        function pdfCoupons(){ 
            if (!window.jspdf || !window.jspdf.jsPDF) return alert('PDF library is not loaded.'); 
            const body = matchingRows().map(row => [ 
                row.querySelector('.order-box')?.innerText.trim() || '', 
                row.querySelector('.coupon-code-badge')?.innerText.trim() || '', 
                row.querySelector('td:nth-child(3)')?.innerText.trim() || '', 
                row.querySelector('.discount-type-tag')?.innerText.trim() || '', 
                row.querySelector('td:nth-child(5)')?.innerText.trim() || '', 
                row.dataset.status === 'active' ? 'Active' : 'Inactive' 
            ]); 
 
            const doc = new jspdf.jsPDF({orientation:'landscape',unit:'mm',format:'a4'}); 
            doc.setFontSize(15); doc.text('GatewayLinen - Coupons',14,14); 
            if (typeof doc.autoTable === 'function') { 
                doc.autoTable({startY:22,head:[['#','Coupon Code','Discount','Type','Min Order','Status']],body:body,styles:{fontSize:8,cellPadding:2},headStyles:{fillColor:[5,150,105]}}); 
            } 
            doc.save('coupons-' + new Date().toISOString().slice(0,10) + '.pdf'); 
        } 
 
        document.getElementById('printBtn')?.addEventListener('click', printCoupons); 
        document.getElementById('pdfBtn')?.addEventListener('click', pdfCoupons); 
        document.getElementById('excelBtn')?.addEventListener('click', excelCoupons); 
        searchInput?.addEventListener('input', () => filterCoupons()); 
        typeFilter?.addEventListener('change', () => filterCoupons()); 
        statusFilter?.addEventListener('change', () => filterCoupons()); 
 
        function openDetails(btn){ 
            document.getElementById('detailCode').textContent = btn.dataset.code || '—'; 
            document.getElementById('detailValue').textContent = btn.dataset.value || '—'; 
            document.getElementById('detailType').textContent = btn.dataset.type || '—'; 
            document.getElementById('detailStatus').textContent = btn.dataset.status || '—'; 
            document.getElementById('detailMin').textContent = btn.dataset.min || 'None'; 
            document.getElementById('detailMax').textContent = btn.dataset.max || 'None'; 
            document.getElementById('detailValidity').textContent = btn.dataset.validity || '—'; 
            document.getElementById('detailUsage').textContent = btn.dataset.usage || '—'; 
            document.getElementById('detailTarget').textContent = btn.dataset.target || '—'; 
            document.getElementById('detailCreated').textContent = btn.dataset.created || '—'; 
 
            modal.classList.add('show'); 
            modal.setAttribute('aria-hidden','false'); 
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
 
        document.querySelectorAll('.delete-coupon-btn').forEach(btn => { 
            btn.addEventListener('click', function(e){ 
                e.stopPropagation(); 
                const code = btn.closest('.coupon-row')?.querySelector('.coupon-code-badge')?.innerText.trim() || 'this coupon'; 
                if (!confirm('Delete coupon "' + code + '"?\n\nThis action cannot be undone.')) e.preventDefault(); 
            }); 
        }); 
 
        document.querySelectorAll('.coupon-row').forEach(row => { 
            row.addEventListener('click', function(e){ 
                if (e.target.closest('button') || e.target.closest('a') || e.target.closest('form')) return; 
                rows().forEach(r => r.classList.remove('keyboard-selected')); 
                this.classList.add('keyboard-selected'); 
            }); 
        }); 

        document.addEventListener('keydown', function(e){ 
            const tag = (e.target?.tagName || '').toLowerCase(); 
            const typing = tag === 'input' || tag === 'textarea' || tag === 'select' || e.target?.isContentEditable; 
            if (typing) { 
                if (e.key === 'Escape') { 
                    if (modal?.classList.contains('show')) closeModal(); 
                    else if (searchInput?.value) { searchInput.value=''; filterCoupons(); } 
                    searchInput?.blur(); typeFilter?.blur(); statusFilter?.blur(); 
                } 
                return; 
            } 
 
            const key = (e.key || '').toUpperCase(); 
            if (['A','B','C','D','E','P','V','X'].includes(key)) e.preventDefault(); 
 
            if (key === 'A') document.getElementById('addCouponBtn')?.click(); 
            else if (key === 'B') { searchInput?.focus(); searchInput?.select(); } 
            else if (key === 'C') typeFilter?.focus(); 
            else if (key === 'D') { 
                const selected = document.querySelector('.coupon-row.keyboard-selected') || visibleRows()[0]; 
                selected?.querySelector('.delete-coupon-btn')?.click(); 
            } 
            else if (key === 'E') { 
                const selected = document.querySelector('.coupon-row.keyboard-selected') || visibleRows()[0]; 
                selected?.querySelector('.edit-btn')?.click(); 
            } 
            else if (key === 'P') printCoupons(); 
            else if (key === 'V') pdfCoupons(); 
            else if (key === 'X') excelCoupons(); 
            else if (key === 'ESCAPE') { 
                if (modal?.classList.contains('show')) closeModal(); 
            } 
        }, true); 
 
        firstPageBtn?.addEventListener('click', () => { currentPage = 1; renderPagination(); }); 
        prevPageBtn?.addEventListener('click', () => { if (currentPage > 1) { currentPage--; renderPagination(); } }); 
        nextPageBtn?.addEventListener('click', () => { 
            const total = matchingRows().length; 
            const totalPages = Math.max(1, Math.ceil(total / pageSize)); 
            if (currentPage < totalPages) { currentPage++; renderPagination(); } 
        }); 
        lastPageBtn?.addEventListener('click', () => { 
            const total = matchingRows().length; 
            currentPage = Math.max(1, Math.ceil(total / pageSize)); 
            renderPagination(); 
        }); 
        pageSizeSelect?.addEventListener('change', function(){ 
            pageSize = parseInt(this.value || '50', 10); 
            currentPage = 1; 
            renderPagination(); 
        }); 
 
        filterCoupons(); 
    }); 
})(); 
</script> 
 
<?php require_once __DIR__ . '/../includes/footer.php'; ?>