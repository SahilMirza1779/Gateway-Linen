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
$pageTitle  = 'GatewayLinen | Edit Coupon';

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] =
        $_SESSION['admin_username'] ?? 'GatewayLinen Administrator';
}

if (!isset($_SESSION['admin_role'])) {
    $_SESSION['admin_role'] = 'Administrator';
}

/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

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

function formatDateInput($value): string
{
    if ($value instanceof DateTimeInterface) {
        return $value->format('Y-m-d');
    }

    if (!empty($value)) {
        try {
            $dt = new DateTime((string)$value);
            return $dt->format('Y-m-d');
        } catch (Exception $e) {
            return '';
        }
    }

    return '';
}

/*
|--------------------------------------------------------------------------
| GET COUPON ID & LOAD DATA
|--------------------------------------------------------------------------
*/

$couponId = (int)($_GET['id'] ?? $_POST['coupon_id'] ?? 0);

if ($couponId <= 0) {
    header('Location: index.php');
    exit;
}

$loadSql = "SELECT * FROM dbo.Coupons WHERE CouponId = ?";
$loadStmt = sqlsrv_query($conn, $loadSql, [$couponId]);

if ($loadStmt === false) {
    die('Unable to load coupon.');
}

$coupon = sqlsrv_fetch_array($loadStmt, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($loadStmt);

if (!$coupon) {
    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| DEFAULT VALUES FROM DATABASE
|--------------------------------------------------------------------------
*/

$couponCode         = (string)($coupon['CouponCode'] ?? '');
$discountType       = (string)($coupon['DiscountType'] ?? 'Percentage');
$discountValue      = (string)($coupon['DiscountValue'] ?? '0.00');
$minOrderAmount     = !empty($coupon['MinOrderAmount']) ? (string)$coupon['MinOrderAmount'] : '';
$maxDiscountAmount  = !empty($coupon['MaxDiscountAmount']) ? (string)$coupon['MaxDiscountAmount'] : '';
$startDate          = formatDateInput($coupon['StartDate'] ?? null);
$endDate            = formatDateInput($coupon['EndDate'] ?? null);
$usageLimit         = !empty($coupon['UsageLimit']) ? (string)$coupon['UsageLimit'] : '';
$timesUsed          = (int)($coupon['TimesUsed'] ?? 0);

$isForWholesaleOnly = !empty($coupon['IsForWholesaleOnly']);
$isActive           = !empty($coupon['IsActive']);

$errorMessage = '';

/*
|--------------------------------------------------------------------------
| UPDATE COUPON
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_coupon') {
    $postedToken = (string)($_POST['csrf_token'] ?? '');

    if (!hash_equals($_SESSION['csrf_token'], $postedToken)) {
        $errorMessage = 'Security verification failed. Please refresh the page and try again.';
    } else {
        $couponCode         = strtoupper(trim((string)($_POST['coupon_code'] ?? '')));
        $discountType       = trim((string)($_POST['discount_type'] ?? 'Percentage'));
        $discountValue      = trim((string)($_POST['discount_value'] ?? ''));
        $minOrderAmount     = trim((string)($_POST['min_order_amount'] ?? ''));
        $maxDiscountAmount  = trim((string)($_POST['max_discount_amount'] ?? ''));
        $startDate          = trim((string)($_POST['start_date'] ?? ''));
        $endDate            = trim((string)($_POST['end_date'] ?? ''));
        $usageLimit         = trim((string)($_POST['usage_limit'] ?? ''));

        $isForWholesaleOnly = isset($_POST['is_for_wholesale_only']) && $_POST['is_for_wholesale_only'] === '1';
        $isActive           = isset($_POST['is_active']) && $_POST['is_active'] === '1';

        // Validations
        if ($couponCode === '') {
            $errorMessage = 'Coupon code is required.';
        } elseif (mb_strlen($couponCode) > 50) {
            $errorMessage = 'Coupon code cannot exceed 50 characters.';
        } elseif (!preg_match("/^[A-Z0-9_\-]+$/", $couponCode)) {
            $errorMessage = 'Coupon code must contain only letters, numbers, dashes and underscores.';
        } elseif (!is_numeric($discountValue) || (float)$discountValue <= 0) {
            $errorMessage = 'Please enter a valid discount value greater than 0.';
        } elseif ($discountType === 'Percentage' && (float)$discountValue > 100) {
            $errorMessage = 'Percentage discount cannot be greater than 100%.';
        } elseif ($minOrderAmount !== '' && (!is_numeric($minOrderAmount) || (float)$minOrderAmount < 0)) {
            $errorMessage = 'Minimum order amount must be a positive number.';
        } elseif ($maxDiscountAmount !== '' && (!is_numeric($maxDiscountAmount) || (float)$maxDiscountAmount < 0)) {
            $errorMessage = 'Maximum discount cap must be a positive number.';
        } elseif ($usageLimit !== '' && (!ctype_digit($usageLimit) || (int)$usageLimit <= 0)) {
            $errorMessage = 'Usage limit must be a positive integer.';
        } elseif (!empty($startDate) && !empty($endDate) && strtotime($endDate) < strtotime($startDate)) {
            $errorMessage = 'End date cannot be earlier than start date.';
        }

        // Duplicate Code Check
        if ($errorMessage === '') {
            $checkSql = "SELECT TOP 1 CouponId FROM dbo.Coupons WHERE CouponId <> ? AND CouponCode = ?";
            $checkStmt = sqlsrv_query($conn, $checkSql, [$couponId, $couponCode]);

            if ($checkStmt !== false) {
                if (sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC)) {
                    $errorMessage = "Another coupon with code '{$couponCode}' already exists.";
                }
                sqlsrv_free_stmt($checkStmt);
            } else {
                $errorMessage = 'Unable to verify coupon code availability.';
            }
        }

        // Update Database Record
        if ($errorMessage === '') {
            $updateSql = "
                UPDATE dbo.Coupons
                SET
                    CouponCode = ?,
                    DiscountType = ?,
                    DiscountValue = ?,
                    MinOrderAmount = ?,
                    MaxDiscountAmount = ?,
                    StartDate = ?,
                    EndDate = ?,
                    UsageLimit = ?,
                    IsForWholesaleOnly = ?,
                    IsActive = ?
                WHERE CouponId = ?
            ";

            $params = [
                $couponCode,
                $discountType,
                (float)$discountValue,
                $minOrderAmount !== '' ? (float)$minOrderAmount : null,
                $maxDiscountAmount !== '' ? (float)$maxDiscountAmount : null,
                !empty($startDate) ? $startDate : null,
                !empty($endDate) ? $endDate : null,
                $usageLimit !== '' ? (int)$usageLimit : null,
                $isForWholesaleOnly ? 1 : 0,
                $isActive ? 1 : 0,
                $couponId
            ];

            $updateStmt = sqlsrv_query($conn, $updateSql, $params);

            if ($updateStmt === false) {
                $errors = sqlsrv_errors();
                $errorMessage = $errors[0]['message'] ?? 'Coupon could not be updated. Please try again.';
            } else {
                sqlsrv_free_stmt($updateStmt);

                header('Location: index.php?success=' . urlencode("Coupon '{$couponCode}' updated successfully."));
                exit;
            }
        }
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
    --bg-input: #0d1620;
    --border: #1e2d3d;
    --border-soft: #182636;

    --text-hi: #f0f4f8;
    --text-body: #a8b8c8;
    --text-mute: #6b7f91;

    --green: #10b981;
    --green-dark: #059669;
    --green-soft: rgba(16,185,129,.12);

    --red: #ef4444;
    --red-soft: rgba(239,68,68,.12);

    --blue: #38bdf8;
    --amber: #f59e0b;
    --purple: #a855f7;

    --radius: 12px;
}

* {
    box-sizing: border-box;
}

html, body, .main, .content {
    background: var(--bg-page) !important;
    color: var(--text-body) !important;
}

.coupon-edit-page {
    width: 100%;
    max-width: 1250px;
    margin: 0 auto;
    padding: 0 0 35px;
}

.page-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    padding-bottom: 18px;
    margin-bottom: 20px;
    border-bottom: 1px solid var(--border);
}

.breadcrumb {
    display: flex;
    gap: 8px;
    margin-bottom: 8px;
    color: var(--text-mute);
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .4px;
}

.breadcrumb .current {
    color: var(--green);
}

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
    gap: 8px;
    flex-wrap: wrap;
}

.btn {
    min-height: 39px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 14px;
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
    background: linear-gradient(135deg, var(--green-dark), var(--green));
    color: #fff !important;
    box-shadow: 0 7px 20px rgba(16,185,129,.18);
}

.btn-primary:hover {
    color: #fff !important;
    transform: translateY(-1px);
}

.notice {
    margin-bottom: 15px;
    padding: 12px 14px;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 700;
}

.notice-error {
    border: 1px solid rgba(239,68,68,.3);
    background: var(--red-soft);
    color: #fca5a5;
}

.edit-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 380px;
    gap: 16px;
}

.card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    margin-bottom: 16px;
}

.card-header {
    padding: 16px 18px;
    border-bottom: 1px solid var(--border);
}

.card-header h2 {
    margin: 0;
    color: var(--text-hi);
    font-size: 15px;
    font-weight: 800;
}

.card-header p {
    margin: 5px 0 0;
    color: var(--text-mute);
    font-size: 11px;
}

.card-body {
    padding: 20px;
}

.form-group {
    margin-bottom: 17px;
}

.form-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 7px;
    color: var(--text-body);
    font-size: 11px;
    font-weight: 800;
}

.required {
    color: #f87171;
}

.input, .select {
    width: 100%;
    border: 1px solid var(--border);
    border-radius: 8px;
    outline: none;
    background: var(--bg-input);
    color: var(--text-hi);
    font-family: inherit;
    font-size: 12px;
    transition: .18s;
    height: 42px;
    padding: 0 12px;
}

.input:focus, .select:focus {
    border-color: var(--green);
    box-shadow: 0 0 0 3px rgba(16,185,129,.1);
}

.select {
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%235f7488' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 14px;
    padding-right: 36px;
    cursor: pointer;
}

.two-column {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.help-text {
    margin-top: 6px;
    color: var(--text-mute);
    font-size: 10px;
}

.input-with-action {
    display: flex;
    gap: 8px;
}

.btn-action-input {
    height: 42px;
    padding: 0 14px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--bg-input);
    color: var(--text-body);
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s ease;
}

.btn-action-input:hover {
    border-color: var(--green);
    color: var(--green);
    background: var(--green-soft);
}

/* SWITCHES */
.status-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 13px 14px;
    border: 1px solid var(--border);
    border-radius: 9px;
    background: var(--bg-input);
    margin-bottom: 12px;
}

.status-info strong {
    display: block;
    color: var(--text-hi);
    font-size: 12px;
}

.status-info span {
    display: block;
    margin-top: 3px;
    color: var(--text-mute);
    font-size: 10px;
}

.switch {
    position: relative;
    width: 44px;
    height: 24px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    inset: 0;
    cursor: pointer;
    border-radius: 30px;
    background: #263544;
    transition: .2s;
}

.slider:before {
    content: "";
    position: absolute;
    width: 18px;
    height: 18px;
    left: 3px;
    top: 3px;
    border-radius: 50%;
    background: #fff;
    transition: .2s;
}

.switch input:checked + .slider {
    background: var(--green);
}

.switch input:checked + .slider:before {
    transform: translateX(20px);
}

/* USAGE SUMMARY BOX */
.usage-summary-card {
    padding: 15px;
    border: 1px solid var(--border-soft);
    border-radius: 8px;
    background: var(--bg-input);
    margin-bottom: 16px;
}

.usage-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 11.5px;
    margin-bottom: 6px;
}

.usage-row:last-child {
    margin-bottom: 0;
}

.action-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding: 15px 20px;
    border-top: 1px solid var(--border);
}

.shortcut-box {
    margin-top: 16px;
    padding: 15px 18px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: var(--bg-card);
}

.shortcut-title {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text-hi);
    font-size: 12px;
    font-weight: 800;
}

.shortcut-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
    margin-top: 12px;
}

.shortcut {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    border: 1px solid var(--border-soft);
    border-radius: 7px;
    background: var(--bg-input);
}

.key {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 38px;
    height: 24px;
    padding: 0 6px;
    border: 1px solid var(--border);
    border-radius: 5px;
    background: #080e15;
    color: var(--green);
    font-family: monospace;
    font-size: 9px;
    font-weight: 800;
}

.key-text {
    color: var(--text-body);
    font-size: 10px;
    font-weight: 600;
}

@media(max-width: 950px) {
    .edit-layout {
        grid-template-columns: 1fr;
    }
}

@media(max-width: 650px) {
    .coupon-edit-page {
        padding: 0 10px 25px;
    }
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .header-actions {
        width: 100%;
    }
    .header-actions .btn {
        flex: 1;
    }
    .two-column {
        grid-template-columns: 1fr;
    }
    .shortcut-grid {
        grid-template-columns: 1fr;
    }
    .action-footer {
        flex-direction: column-reverse;
    }
    .action-footer .btn {
        width: 100%;
    }
}
</style>

<main class="main">
<section class="content">
<div class="coupon-edit-page">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <div class="breadcrumb">
                <span>Marketing</span>
                <span>/</span>
                <span>Coupons</span>
                <span>/</span>
                <span class="current">Edit</span>
            </div>
            <h1>Edit Coupon</h1>
            <p>Update coupon configuration, validity dates, order criteria and limits.</p>
        </div>

        <div class="header-actions">
            <a href="index.php" class="btn" title="Cancel (Esc)">← Back</a>
            <button type="submit" form="couponEditForm" class="btn btn-primary" title="Save (Ctrl + S)">
                ✓ Save Changes
            </button>
        </div>
    </div>

    <!-- ERROR NOTICE -->
    <?php if ($errorMessage !== ''): ?>
        <div class="notice notice-error"><?= e($errorMessage) ?></div>
    <?php endif; ?>

    <!-- FORM -->
    <form method="post" id="couponEditForm" autocomplete="off">
        <input type="hidden" name="action" value="update_coupon">
        <input type="hidden" name="coupon_id" value="<?= $couponId ?>">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

        <div class="edit-layout">

            <!-- LEFT COLUMN: MAIN CONFIGURATION -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <h2>Coupon & Discount Rules</h2>
                        <p>Discount calculation and identification code.</p>
                    </div>

                    <div class="card-body">
                        <!-- COUPON CODE -->
                        <div class="form-group">
                            <label class="form-label">
                                <span>Coupon Code <span class="required">*</span></span>
                            </label>
                            <div class="input-with-action">
                                <input 
                                    type="text" 
                                    name="coupon_code" 
                                    id="couponCode" 
                                    class="input" 
                                    value="<?= e($couponCode) ?>" 
                                    maxlength="50" 
                                    required 
                                    style="text-transform: uppercase; font-family: monospace; font-weight: 700;"
                                >
                                <button type="button" class="btn-action-input" id="btnGenCode" title="Generate fresh random code">
                                    ⚡ Generate
                                </button>
                            </div>
                            <div class="help-text">Letters, numbers, and dashes only. Stored in uppercase.</div>
                        </div>

                        <!-- DISCOUNT TYPE & VALUE -->
                        <div class="two-column">
                            <div class="form-group">
                                <label class="form-label">
                                    <span>Discount Type <span class="required">*</span></span>
                                </label>
                                <select name="discount_type" id="discountType" class="select" required>
                                    <option value="Percentage" <?= $discountType === 'Percentage' ? 'selected' : '' ?>>Percentage (%)</option>
                                    <option value="Fixed" <?= $discountType === 'Fixed' ? 'selected' : '' ?>>Fixed Amount ($)</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <span>Discount Value <span class="required">*</span> <span id="valUnitText">(%)</span></span>
                                </label>
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    min="0.01" 
                                    name="discount_value" 
                                    id="discountValue" 
                                    class="input" 
                                    value="<?= e($discountValue) ?>" 
                                    required
                                >
                            </div>
                        </div>

                        <!-- MAX DISCOUNT CAP (FOR PERCENTAGE) -->
                        <div class="form-group" id="maxDiscountGroup">
                            <label class="form-label">
                                <span>Maximum Discount Cap ($) <small style="color:var(--text-mute);">(Optional)</small></span>
                            </label>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                name="max_discount_amount" 
                                id="maxDiscountAmount" 
                                class="input" 
                                value="<?= e($maxDiscountAmount) ?>" 
                                placeholder="e.g. 50.00"
                            >
                            <div class="help-text">Upper limit in dollars when using percentage discount.</div>
                        </div>
                    </div>
                </div>

                <!-- ORDER RESTRICTIONS & VALIDITY -->
                <div class="card">
                    <div class="card-header">
                        <h2>Order Criteria & Validity Period</h2>
                        <p>Minimum spends, overall usage limits and active calendar dates.</p>
                    </div>

                    <div class="card-body">
                        <div class="two-column">
                            <div class="form-group">
                                <label class="form-label">
                                    <span>Minimum Order Amount ($) <small style="color:var(--text-mute);">(Optional)</small></span>
                                </label>
                                <input 
                                    type="number" 
                                    step="0.01" 
                                    min="0" 
                                    name="min_order_amount" 
                                    class="input" 
                                    value="<?= e($minOrderAmount) ?>" 
                                    placeholder="0.00"
                                >
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <span>Total Usage Limit <small style="color:var(--text-mute);">(Optional)</small></span>
                                </label>
                                <input 
                                    type="number" 
                                    step="1" 
                                    min="1" 
                                    name="usage_limit" 
                                    class="input" 
                                    value="<?= e($usageLimit) ?>" 
                                    placeholder="Unlimited"
                                >
                            </div>
                        </div>

                        <div class="two-column">
                            <div class="form-group">
                                <label class="form-label"><span>Start Date</span></label>
                                <input type="date" name="start_date" class="input" value="<?= e($startDate) ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label"><span>End Date</span></label>
                                <input type="date" name="end_date" class="input" value="<?= e($endDate) ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: STATUS & USAGE METRICS -->
            <div>
                <!-- USAGE METRICS -->
                <div class="card">
                    <div class="card-header">
                        <h2>Usage Tracking</h2>
                        <p>Total redemptions logged for this coupon.</p>
                    </div>

                    <div class="card-body">
                        <div class="usage-summary-card">
                            <div class="usage-row">
                                <span style="color:var(--text-mute);">Times Redeemed:</span>
                                <strong style="color:var(--green); font-size:14px;"><?= $timesUsed ?></strong>
                            </div>
                            <div class="usage-row">
                                <span style="color:var(--text-mute);">Usage Capacity:</span>
                                <strong><?= $usageLimit !== '' ? e($usageLimit) : 'Unlimited' ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STATUS & WHOLESALE SETTINGS -->
                <div class="card">
                    <div class="card-header">
                        <h2>Visibility & Restrictions</h2>
                        <p>Customer target and availability switch.</p>
                    </div>

                    <div class="card-body">
                        <div class="status-box">
                            <div class="status-info">
                                <strong id="statusText"><?= $isActive ? 'Active' : 'Inactive' ?></strong>
                                <span>Coupon can be applied at checkout</span>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="is_active" value="1" id="activeSwitch" <?= $isActive ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                        </div>

                        <div class="status-box">
                            <div class="status-info">
                                <strong>Wholesale Only</strong>
                                <span>Restricted to approved wholesale accounts</span>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="is_for_wholesale_only" value="1" <?= $isForWholesaleOnly ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <div class="action-footer">
                        <a href="index.php" class="btn">Cancel</a>
                        <button type="submit" class="btn btn-primary">✓ Update Coupon</button>
                    </div>
                </div>
            </div>

        </div>
    </form>

    <!-- KEYBOARD SHORTCUTS -->
    <div class="shortcut-box">
        <div class="shortcut-title">⌨ Keyboard Shortcuts</div>
        <div class="shortcut-grid">
            <div class="shortcut">
                <span class="key">Ctrl+S</span>
                <span class="key-text">Save Changes</span>
            </div>
            <div class="shortcut">
                <span class="key">Ctrl+Shift+S</span>
                <span class="key-text">Save Changes</span>
            </div>
            <div class="shortcut">
                <span class="key">Esc</span>
                <span class="key-text">Cancel / Back</span>
            </div>
        </div>
    </div>

</div>
</section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const form        = document.getElementById('couponEditForm');
    const codeInput   = document.getElementById('couponCode');
    const typeSelect  = document.getElementById('discountType');
    const valInput    = document.getElementById('discountValue');
    const valUnitText = document.getElementById('valUnitText');
    const maxGroup    = document.getElementById('maxDiscountGroup');
    const btnGen      = document.getElementById('btnGenCode');
    const activeSwitch= document.getElementById('activeSwitch');
    const statusText  = document.getElementById('statusText');

    // Percentage vs Fixed format toggle
    function syncDiscountType() {
        if (typeSelect.value === 'Percentage') {
            valUnitText.textContent = '(%)';
            valInput.max = '100';
            valInput.placeholder = 'e.g. 15.00';
            if (maxGroup) maxGroup.style.display = 'block';
        } else {
            valUnitText.textContent = '($)';
            valInput.removeAttribute('max');
            valInput.placeholder = 'e.g. 25.00';
            if (maxGroup) maxGroup.style.display = 'none';
        }
    }

    typeSelect.addEventListener('change', syncDiscountType);
    syncDiscountType();

    // Auto-uppercase coupon code
    codeInput.addEventListener('input', function(){
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9_\-]/g, '');
    });

    // Random generator
    btnGen.addEventListener('click', function(e){
        e.preventDefault();
        const prefixes = ["PROMO", "SAVE", "OFF", "LINEN", "SPECIAL", "VIP"];
        const prefix = prefixes[Math.floor(Math.random() * prefixes.length)];
        const num = Math.floor(10 + Math.random() * 90);
        const chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
        let suffix = "";
        for (let i = 0; i < 3; i++) {
            suffix += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        codeInput.value = prefix + num + suffix;
        codeInput.focus();
    });

    // Active status toggle text
    activeSwitch.addEventListener('change', function(){
        statusText.textContent = this.checked ? 'Active' : 'Inactive';
    });

    // Keyboard Shortcuts (Ctrl+S & Esc)
    document.addEventListener('keydown', function(e){
        if (e.ctrlKey && e.key.toLowerCase() === 's') {
            e.preventDefault();
            form.requestSubmit();
            return;
        }

        if (e.key === 'Escape') {
            const tag = e.target?.tagName?.toLowerCase();
            if (['input', 'textarea', 'select'].includes(tag) && e.target.value) {
                e.target.blur();
                return;
            }
            window.location.href = 'index.php';
        }
    });

    // Double Submission Protection
    let submitting = false;
    form.addEventListener('submit', function(){
        if (submitting) return;
        submitting = true;
        const buttons = form.querySelectorAll('button[type="submit"]');
        buttons.forEach(button => {
            button.disabled = true;
            button.innerHTML = '⏳ Saving...';
        });
    });
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>