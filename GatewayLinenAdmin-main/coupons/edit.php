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
            $date = new DateTime((string)$value);
            return $date->format('Y-m-d');
        } catch (Exception $e) {
            return '';
        }
    }

    return '';
}

function validateDateValue(string $date): bool
{
    if ($date === '') {
        return false;
    }

    $dateObject = DateTime::createFromFormat('!Y-m-d', $date);

    if (!$dateObject) {
        return false;
    }

    $errors = DateTime::getLastErrors();

    if ($errors !== false) {
        if ($errors['warning_count'] > 0 || $errors['error_count'] > 0) {
            return false;
        }
    }

    return $dateObject->format('Y-m-d') === $date;
}

/*
|--------------------------------------------------------------------------
| GET COUPON ID
|--------------------------------------------------------------------------
*/

$couponId = (int)(
    $_GET['id']
    ?? $_POST['coupon_id']
    ?? 0
);

if ($couponId <= 0) {
    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| LOAD COUPON
|--------------------------------------------------------------------------
*/

$loadSql = "
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
    WHERE CouponId = ?
";

$loadStmt = sqlsrv_query(
    $conn,
    $loadSql,
    [$couponId]
);

if ($loadStmt === false) {
    die('Unable to load coupon.');
}

$coupon = sqlsrv_fetch_array(
    $loadStmt,
    SQLSRV_FETCH_ASSOC
);

sqlsrv_free_stmt($loadStmt);

if (!$coupon) {
    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| DEFAULT VALUES
|--------------------------------------------------------------------------
*/

$couponCode = (string)(
    $coupon['CouponCode'] ?? ''
);

$discountType = (string)(
    $coupon['DiscountType'] ?? 'Percentage'
);

if (!in_array($discountType, ['Percentage', 'Fixed'], true)) {
    $discountType = 'Percentage';
}

$discountValue = (
    $coupon['DiscountValue'] !== null
    && $coupon['DiscountValue'] !== ''
)
    ? (string)$coupon['DiscountValue']
    : '';

$minOrderAmount = (
    $coupon['MinOrderAmount'] !== null
    && $coupon['MinOrderAmount'] !== ''
)
    ? (string)$coupon['MinOrderAmount']
    : '';

$maxDiscountAmount = (
    $coupon['MaxDiscountAmount'] !== null
    && $coupon['MaxDiscountAmount'] !== ''
)
    ? (string)$coupon['MaxDiscountAmount']
    : '';

$startDate = formatDateInput(
    $coupon['StartDate'] ?? null
);

$endDate = formatDateInput(
    $coupon['EndDate'] ?? null
);

$usageLimit = (
    $coupon['UsageLimit'] !== null
    && $coupon['UsageLimit'] !== ''
)
    ? (string)$coupon['UsageLimit']
    : '';

$timesUsed = (int)(
    $coupon['TimesUsed'] ?? 0
);

$isForWholesaleOnly = !empty(
    $coupon['IsForWholesaleOnly']
);

$isActive = !empty(
    $coupon['IsActive']
);

$errorMessage = '';

/*
|--------------------------------------------------------------------------
| UPDATE COUPON
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'update_coupon'
) {

    /*
    |--------------------------------------------------------------------------
    | CSRF CHECK
    |--------------------------------------------------------------------------
    */

    $postedToken = (string)(
        $_POST['csrf_token'] ?? ''
    );

    if (
        empty($_SESSION['csrf_token'])
        || !hash_equals(
            $_SESSION['csrf_token'],
            $postedToken
        )
    ) {

        $errorMessage =
            'Security verification failed. Please refresh the page and try again.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | GET POST DATA
        |--------------------------------------------------------------------------
        */

        $couponCode = strtoupper(
            trim(
                (string)(
                    $_POST['coupon_code'] ?? ''
                )
            )
        );

        $discountType = trim(
            (string)(
                $_POST['discount_type'] ?? 'Percentage'
            )
        );

        $discountValue = trim(
            (string)(
                $_POST['discount_value'] ?? ''
            )
        );

        $minOrderAmount = trim(
            (string)(
                $_POST['min_order_amount'] ?? ''
            )
        );

        $maxDiscountAmount = trim(
            (string)(
                $_POST['max_discount_amount'] ?? ''
            )
        );

        $startDate = trim(
            (string)(
                $_POST['start_date'] ?? ''
            )
        );

        $endDate = trim(
            (string)(
                $_POST['end_date'] ?? ''
            )
        );

        $usageLimit = trim(
            (string)(
                $_POST['usage_limit'] ?? ''
            )
        );

        $isForWholesaleOnly =
            isset($_POST['is_for_wholesale_only'])
            && $_POST['is_for_wholesale_only'] === '1';

        $isActive =
            isset($_POST['is_active'])
            && $_POST['is_active'] === '1';

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        if ($couponCode === '') {

            $errorMessage =
                'Coupon code is required.';

        } elseif (mb_strlen($couponCode) > 50) {

            $errorMessage =
                'Coupon code cannot exceed 50 characters.';

        } elseif (
            !preg_match(
                '/^[A-Z0-9_-]+$/',
                $couponCode
            )
        ) {

            $errorMessage =
                'Coupon code must contain only letters, numbers, dashes and underscores.';

        } elseif (
            !in_array(
                $discountType,
                ['Percentage', 'Fixed'],
                true
            )
        ) {

            $errorMessage =
                'Invalid discount type selected.';

        } elseif (
            $discountValue === ''
            || !is_numeric($discountValue)
            || (float)$discountValue <= 0
        ) {

            $errorMessage =
                'Please enter a valid discount value greater than 0.';

        } elseif (
            $discountType === 'Percentage'
            && (float)$discountValue > 100
        ) {

            $errorMessage =
                'Percentage discount cannot be greater than 100%.';

        } elseif (
            $minOrderAmount !== ''
            && (
                !is_numeric($minOrderAmount)
                || (float)$minOrderAmount < 0
            )
        ) {

            $errorMessage =
                'Minimum order amount must be 0 or greater.';

        } elseif (
            $maxDiscountAmount !== ''
            && (
                !is_numeric($maxDiscountAmount)
                || (float)$maxDiscountAmount < 0
            )
        ) {

            $errorMessage =
                'Maximum discount cap must be 0 or greater.';

        } elseif (
            $usageLimit !== ''
            && (
                !ctype_digit($usageLimit)
                || (int)$usageLimit <= 0
            )
        ) {

            $errorMessage =
                'Usage limit must be a positive whole number.';

        } elseif (
            !validateDateValue($startDate)
        ) {

            $errorMessage =
                'Please enter a valid start date.';

        } elseif (
            !validateDateValue($endDate)
        ) {

            $errorMessage =
                'Please enter a valid end date.';

        } else {

            /*
            |--------------------------------------------------------------------------
            | DATE COMPARISON
            |--------------------------------------------------------------------------
            */

            $startDateObject = DateTime::createFromFormat(
                '!Y-m-d',
                $startDate
            );

            $endDateObject = DateTime::createFromFormat(
                '!Y-m-d',
                $endDate
            );

            if (
                $startDateObject
                && $endDateObject
                && $endDateObject < $startDateObject
            ) {

                $errorMessage =
                    'End date cannot be earlier than start date.';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | USAGE LIMIT CANNOT BE LOWER THAN ALREADY USED
        |--------------------------------------------------------------------------
        */

        if (
            $errorMessage === ''
            && $usageLimit !== ''
            && (int)$usageLimit < $timesUsed
        ) {

            $errorMessage =
                "Usage limit cannot be lower than the current redeemed count ({$timesUsed}).";
        }

        /*
        |--------------------------------------------------------------------------
        | FIXED DISCOUNT DOES NOT USE MAX CAP
        |--------------------------------------------------------------------------
        */

        if (
            $errorMessage === ''
            && $discountType === 'Fixed'
        ) {

            $maxDiscountAmount = '';
        }

        /*
        |--------------------------------------------------------------------------
        | DUPLICATE COUPON CHECK
        |--------------------------------------------------------------------------
        */

        if ($errorMessage === '') {

            $checkSql = "
                SELECT TOP 1 CouponId
                FROM dbo.Coupons
                WHERE CouponId <> ?
                AND CouponCode = ?
            ";

            $checkStmt = sqlsrv_query(
                $conn,
                $checkSql,
                [
                    $couponId,
                    $couponCode
                ]
            );

            if ($checkStmt === false) {

                $errorMessage =
                    'Unable to verify coupon code availability.';

            } else {

                $duplicateCoupon = sqlsrv_fetch_array(
                    $checkStmt,
                    SQLSRV_FETCH_ASSOC
                );

                sqlsrv_free_stmt($checkStmt);

                if ($duplicateCoupon) {

                    $errorMessage =
                        "Another coupon with code '{$couponCode}' already exists.";
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE DATABASE
        |--------------------------------------------------------------------------
        */

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

                $minOrderAmount !== ''
                    ? (float)$minOrderAmount
                    : null,

                $maxDiscountAmount !== ''
                    ? (float)$maxDiscountAmount
                    : null,

                $startDate,

                $endDate,

                $usageLimit !== ''
                    ? (int)$usageLimit
                    : null,

                $isForWholesaleOnly ? 1 : 0,

                $isActive ? 1 : 0,

                $couponId
            ];

            $updateStmt = sqlsrv_query(
                $conn,
                $updateSql,
                $params
            );

            if ($updateStmt === false) {

                $errors = sqlsrv_errors();

                $errorMessage =
                    $errors[0]['message']
                    ?? 'Coupon could not be updated. Please try again.';

            } else {

                sqlsrv_free_stmt($updateStmt);

                /*
                |--------------------------------------------------------------------------
                | REFRESH CSRF TOKEN
                |--------------------------------------------------------------------------
                */

                $_SESSION['csrf_token'] =
                    bin2hex(random_bytes(32));

                header(
                    'Location: index.php?success=' .
                    urlencode(
                        "Coupon '{$couponCode}' updated successfully."
                    )
                );

                exit;
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<style>

/* ==========================================================================
   GATEWAYLINEN COUPON EDIT PAGE
   ========================================================================== */

:root {
    --bg-page: #0a1119;
    --bg-card: #111b26;
    --bg-card-alt: #0f1823;
    --bg-input: #0d1620;
    --bg-hover: #16222e;

    --border: #1e2d3d;
    --border-soft: #182636;

    --text-hi: #f0f4f8;
    --text-body: #a8b8c8;
    --text-mute: #71859a;

    --green: #10b981;
    --green-dark: #059669;
    --green-soft: rgba(16, 185, 129, .12);

    --blue: #3b82f6;
    --blue-soft: rgba(59, 130, 246, .12);

    --purple: #8b5cf6;
    --purple-soft: rgba(139, 92, 246, .12);

    --amber: #f59e0b;
    --amber-soft: rgba(245, 158, 11, .12);

    --red: #ef4444;
    --red-soft: rgba(239, 68, 68, .12);

    --shadow: 0 15px 40px rgba(0, 0, 0, .22);
    --focus-ring: rgba(16, 185, 129, .13);

    --radius: 12px;
}

html[data-theme="light"] {
    --bg-page: #f4f7fb;
    --bg-card: #ffffff;
    --bg-card-alt: #f8fafc;
    --bg-input: #ffffff;
    --bg-hover: #eef2f7;

    --border: #d7e0ea;
    --border-soft: #e6ecf2;

    --text-hi: #172033;
    --text-body: #475569;
    --text-mute: #64748b;

    --green: #059669;
    --green-dark: #047857;
    --green-soft: rgba(16, 185, 129, .11);

    --blue: #3b82f6;
    --blue-soft: rgba(59, 130, 246, .10);

    --purple: #8b5cf6;
    --purple-soft: rgba(139, 92, 246, .10);

    --amber: #f59e0b;
    --amber-soft: rgba(245, 158, 11, .12);

    --red: #ef4444;
    --red-soft: rgba(239, 68, 68, .10);

    --shadow: 0 15px 40px rgba(15, 23, 42, .08);
    --focus-ring: rgba(16, 185, 129, .15);
}

/* ==========================================================================
   BASE
   ========================================================================== */

* {
    box-sizing: border-box;
}

html,
body,
.main,
.content {
    background: var(--bg-page) !important;
    color: var(--text-body) !important;
}

body {
    transition:
        background-color .18s ease,
        color .18s ease;
}

button,
input,
select {
    font-family: inherit;
}

/* ==========================================================================
   PAGE
   ========================================================================== */

.coupon-edit-page {
    width: 100%;
    max-width: 1250px;
    margin: 0 auto;
    padding: 0 0 35px;
}

/* ==========================================================================
   PAGE HEADER
   ========================================================================== */

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
    align-items: center;
    flex-wrap: wrap;
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
    line-height: 1.2;
    font-weight: 800;
}

.page-header p {
    margin: 6px 0 0;

    color: var(--text-mute);

    font-size: 12px;
    line-height: 1.6;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

/* ==========================================================================
   BUTTONS
   ========================================================================== */

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

    transition:
        border-color .18s ease,
        background .18s ease,
        color .18s ease,
        transform .18s ease,
        opacity .18s ease;
}

.btn:hover {
    border-color: var(--green);
    background: var(--green-soft);
    color: var(--green) !important;
}

.btn:focus-visible {
    outline: none;
    border-color: var(--green);
    box-shadow: 0 0 0 3px var(--focus-ring);
}

.btn-primary {
    border-color: transparent;

    background:
        linear-gradient(
            135deg,
            var(--green-dark),
            var(--green)
        );

    color: #ffffff !important;

    box-shadow:
        0 7px 20px rgba(16, 185, 129, .18);
}

.btn-primary:hover {
    color: #ffffff !important;
    transform: translateY(-1px);
}

.btn:disabled {
    cursor: not-allowed;
    opacity: .65;
    transform: none !important;
}

/* ==========================================================================
   ERROR NOTICE
   ========================================================================== */

.notice {
    margin-bottom: 15px;
    padding: 12px 14px;

    border-radius: 9px;

    font-size: 12px;
    font-weight: 700;
    line-height: 1.5;
}

.notice-error {
    border: 1px solid rgba(239, 68, 68, .30);
    background: var(--red-soft);
    color: #fca5a5;
}

html[data-theme="light"] .notice-error {
    color: #b91c1c;
}

/* ==========================================================================
   LAYOUT
   ========================================================================== */

.edit-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 380px;
    gap: 16px;

    align-items: start;
}

/* ==========================================================================
   CARDS
   ========================================================================== */

.card {
    background: var(--bg-card);

    border: 1px solid var(--border);
    border-radius: var(--radius);

    overflow: hidden;

    margin-bottom: 16px;

    box-shadow: var(--shadow);
}

.card-header {
    padding: 16px 18px;

    border-bottom: 1px solid var(--border);
}

.card-header h2 {
    margin: 0;

    color: var(--text-hi);

    font-size: 15px;
    line-height: 1.3;
    font-weight: 800;
}

.card-header p {
    margin: 5px 0 0;

    color: var(--text-mute);

    font-size: 11px;
    line-height: 1.5;
}

.card-body {
    padding: 20px;
}

/* ==========================================================================
   FORM
   ========================================================================== */

.form-group {
    margin-bottom: 17px;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;

    margin-bottom: 7px;

    color: var(--text-body);

    font-size: 11px;
    line-height: 1.4;
    font-weight: 800;
}

.required {
    color: #f87171;
}

.input,
.select {
    width: 100%;
    height: 42px;

    padding: 0 12px;

    border: 1px solid var(--border);
    border-radius: 8px;

    outline: none;

    background: var(--bg-input);
    color: var(--text-hi);

    font-family: inherit;
    font-size: 12px;

    transition:
        border-color .18s ease,
        box-shadow .18s ease,
        background .18s ease;
}

.input::placeholder {
    color: var(--text-mute);
}

.input:focus,
.select:focus {
    border-color: var(--green);

    box-shadow:
        0 0 0 3px var(--focus-ring);
}

.select {
    appearance: none;

    background-image:
        url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%235f7488' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");

    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 14px;

    padding-right: 36px;

    cursor: pointer;
}

.select option {
    background: var(--bg-card);
    color: var(--text-hi);
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
    line-height: 1.5;
}

/* ==========================================================================
   INPUT ACTION
   ========================================================================== */

.input-with-action {
    display: flex;
    align-items: stretch;
    gap: 8px;
}

.input-with-action .input {
    min-width: 0;
    flex: 1;
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

    transition: all .2s ease;
}

.btn-action-input:hover {
    border-color: var(--green);
    color: var(--green);
    background: var(--green-soft);
}

.btn-action-input:focus-visible {
    outline: none;
    border-color: var(--green);
    box-shadow: 0 0 0 3px var(--focus-ring);
}

/* ==========================================================================
   STATUS SWITCH
   ========================================================================== */

.status-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;

    padding: 13px 14px;

    border: 1px solid var(--border);
    border-radius: 9px;

    background: var(--bg-input);

    margin-bottom: 12px;

    transition:
        border-color .18s ease,
        background .18s ease;
}

.status-box:last-child {
    margin-bottom: 0;
}

.status-box:hover {
    border-color: var(--green);
    background: var(--bg-hover);
}

.status-info {
    min-width: 0;
}

.status-info strong {
    display: block;

    color: var(--text-hi);

    font-size: 12px;
    line-height: 1.4;
}

.status-info span {
    display: block;

    margin-top: 3px;

    color: var(--text-mute);

    font-size: 10px;
    line-height: 1.5;
}

.switch {
    position: relative;

    flex: 0 0 auto;

    width: 44px;
    height: 24px;
}

.switch input {
    position: absolute;

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

.slider::before {
    content: "";

    position: absolute;

    width: 18px;
    height: 18px;

    left: 3px;
    top: 3px;

    border-radius: 50%;

    background: #ffffff;

    transition: .2s;
}

.switch input:checked + .slider {
    background: var(--green);
}

.switch input:checked + .slider::before {
    transform: translateX(20px);
}

.switch input:focus-visible + .slider {
    box-shadow:
        0 0 0 3px var(--focus-ring);
}

/* ==========================================================================
   USAGE SUMMARY
   ========================================================================== */

.usage-summary-card {
    padding: 15px;

    border: 1px solid var(--border-soft);
    border-radius: 8px;

    background: var(--bg-input);
}

.usage-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;

    margin-bottom: 9px;

    color: var(--text-body);

    font-size: 11.5px;
}

.usage-row:last-child {
    margin-bottom: 0;
}

.usage-row strong {
    color: var(--text-hi);
    text-align: right;
}

/* ==========================================================================
   ACTION FOOTER
   ========================================================================== */

.action-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;

    padding: 15px 20px;

    border-top: 1px solid var(--border);
}

/* ==========================================================================
   SHORTCUTS
   ========================================================================== */

.shortcut-box {
    margin-top: 16px;
    padding: 15px 18px;

    border: 1px solid var(--border);
    border-radius: 12px;

    background: var(--bg-card);

    box-shadow: var(--shadow);
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

    min-width: 0;

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
    min-height: 24px;

    padding: 0 6px;

    border: 1px solid var(--border);
    border-radius: 5px;

    background: var(--bg-card-alt);
    color: var(--green);

    font-family: monospace;
    font-size: 9px;
    font-weight: 800;

    white-space: nowrap;
}

.key-text {
    color: var(--text-body);

    font-size: 10px;
    font-weight: 600;
}

/* ==========================================================================
   RESPONSIVE
   ========================================================================== */

@media (max-width: 1100px) {

    .edit-layout {
        grid-template-columns: minmax(0, 1fr) 330px;
    }
}

@media (max-width: 950px) {

    .edit-layout {
        grid-template-columns: 1fr;
    }

    .page-header {
        align-items: flex-start;
    }
}

@media (max-width: 650px) {

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

    .input-with-action {
        flex-direction: column;
    }

    .btn-action-input {
        width: 100%;
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

    .card-body {
        padding: 15px;
    }

    .card-header {
        padding: 14px 15px;
    }
}

@media (max-width: 420px) {

    .page-header h1 {
        font-size: 22px;
    }

    .header-actions {
        flex-direction: column;
    }

    .header-actions .btn {
        width: 100%;
        flex: none;
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

            <p>
                Update coupon configuration, validity dates,
                order criteria and limits.
            </p>

        </div>

        <div class="header-actions">

            <a
                href="index.php"
                class="btn"
                title="Cancel (Esc)"
            >
                ← Back
            </a>

            <button
                type="submit"
                form="couponEditForm"
                class="btn btn-primary"
                title="Save (Ctrl + S)"
            >
                ✓ Save Changes
            </button>

        </div>

    </div>

    <!-- ERROR -->
    <?php if ($errorMessage !== ''): ?>

        <div class="notice notice-error">
            <?= e($errorMessage) ?>
        </div>

    <?php endif; ?>

    <!-- FORM -->
    <form
        method="post"
        id="couponEditForm"
        autocomplete="off"
    >

        <input
            type="hidden"
            name="action"
            value="update_coupon"
        >

        <input
            type="hidden"
            name="coupon_id"
            value="<?= $couponId ?>"
        >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e($csrfToken) ?>"
        >

        <div class="edit-layout">

            <!-- =========================================================
                 LEFT COLUMN
                 ========================================================= -->

            <div>

                <!-- COUPON RULES -->
                <div class="card">

                    <div class="card-header">

                        <h2>
                            Coupon &amp; Discount Rules
                        </h2>

                        <p>
                            Discount calculation and identification code.
                        </p>

                    </div>

                    <div class="card-body">

                        <!-- COUPON CODE -->
                        <div class="form-group">

                            <label
                                class="form-label"
                                for="couponCode"
                            >
                                <span>
                                    Coupon Code
                                    <span class="required">*</span>
                                </span>
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
                                    spellcheck="false"
                                    style="
                                        text-transform:uppercase;
                                        font-family:monospace;
                                        font-weight:700;
                                    "
                                >

                                <button
                                    type="button"
                                    class="btn-action-input"
                                    id="btnGenCode"
                                    title="Generate a new coupon code"
                                >
                                    ⚡ Generate
                                </button>

                            </div>

                            <div class="help-text">
                                Letters, numbers, dashes and underscores only.
                                Stored in uppercase.
                            </div>

                        </div>

                        <!-- DISCOUNT TYPE + VALUE -->
                        <div class="two-column">

                            <div class="form-group">

                                <label
                                    class="form-label"
                                    for="discountType"
                                >
                                    <span>
                                        Discount Type
                                        <span class="required">*</span>
                                    </span>
                                </label>

                                <select
                                    name="discount_type"
                                    id="discountType"
                                    class="select"
                                    required
                                >

                                    <option
                                        value="Percentage"
                                        <?= $discountType === 'Percentage' ? 'selected' : '' ?>
                                    >
                                        Percentage (%)
                                    </option>

                                    <option
                                        value="Fixed"
                                        <?= $discountType === 'Fixed' ? 'selected' : '' ?>
                                    >
                                        Fixed Amount ($)
                                    </option>

                                </select>

                            </div>

                            <div class="form-group">

                                <label
                                    class="form-label"
                                    for="discountValue"
                                >
                                    <span>
                                        Discount Value
                                        <span class="required">*</span>
                                        <span id="valUnitText">
                                            <?= $discountType === 'Fixed' ? '($)' : '(%)' ?>
                                        </span>
                                    </span>
                                </label>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    <?= $discountType === 'Percentage' ? 'max="100"' : '' ?>
                                    name="discount_value"
                                    id="discountValue"
                                    class="input"
                                    value="<?= e($discountValue) ?>"
                                    placeholder="<?= $discountType === 'Fixed' ? 'e.g. 25.00' : 'e.g. 15.00' ?>"
                                    required
                                >

                            </div>

                        </div>

                        <!-- MAX DISCOUNT -->
                        <div
                            class="form-group"
                            id="maxDiscountGroup"
                            style="<?= $discountType === 'Fixed' ? 'display:none;' : '' ?>"
                        >

                            <label
                                class="form-label"
                                for="maxDiscountAmount"
                            >

                                <span>
                                    Maximum Discount Cap ($)
                                    <small style="color:var(--text-mute);">
                                        (Optional)
                                    </small>
                                </span>

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
                                <?= $discountType === 'Fixed' ? 'disabled' : '' ?>
                            >

                            <div class="help-text">
                                Maximum amount a percentage coupon can deduct.
                            </div>

                        </div>

                    </div>

                </div>

                <!-- ORDER + VALIDITY -->
                <div class="card">

                    <div class="card-header">

                        <h2>
                            Order Criteria &amp; Validity Period
                        </h2>

                        <p>
                            Minimum spends, usage limits and active dates.
                        </p>

                    </div>

                    <div class="card-body">

                        <!-- AMOUNTS -->
                        <div class="two-column">

                            <div class="form-group">

                                <label
                                    class="form-label"
                                    for="minOrderAmount"
                                >

                                    <span>
                                        Minimum Order Amount ($)
                                        <small style="color:var(--text-mute);">
                                            (Optional)
                                        </small>
                                    </span>

                                </label>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="min_order_amount"
                                    id="minOrderAmount"
                                    class="input"
                                    value="<?= e($minOrderAmount) ?>"
                                    placeholder="0.00"
                                >

                            </div>

                            <div class="form-group">

                                <label
                                    class="form-label"
                                    for="usageLimit"
                                >

                                    <span>
                                        Total Usage Limit
                                        <small style="color:var(--text-mute);">
                                            (Optional)
                                        </small>
                                    </span>

                                </label>

                                <input
                                    type="number"
                                    step="1"
                                    min="<?= max(1, $timesUsed) ?>"
                                    name="usage_limit"
                                    id="usageLimit"
                                    class="input"
                                    value="<?= e($usageLimit) ?>"
                                    placeholder="Unlimited"
                                >

                                <?php if ($timesUsed > 0): ?>

                                    <div class="help-text">
                                        Already redeemed:
                                        <strong>
                                            <?= $timesUsed ?>
                                        </strong>
                                    </div>

                                <?php endif; ?>

                            </div>

                        </div>

                        <!-- DATES -->
                        <div class="two-column">

                            <div class="form-group">

                                <label
                                    class="form-label"
                                    for="startDate"
                                >
                                    <span>
                                        Start Date
                                        <span class="required">*</span>
                                    </span>
                                </label>

                                <input
                                    type="date"
                                    name="start_date"
                                    id="startDate"
                                    class="input"
                                    value="<?= e($startDate) ?>"
                                    required
                                >

                            </div>

                            <div class="form-group">

                                <label
                                    class="form-label"
                                    for="endDate"
                                >
                                    <span>
                                        End Date
                                        <span class="required">*</span>
                                    </span>
                                </label>

                                <input
                                    type="date"
                                    name="end_date"
                                    id="endDate"
                                    class="input"
                                    value="<?= e($endDate) ?>"
                                    required
                                >

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <!-- =========================================================
                 RIGHT COLUMN
                 ========================================================= -->

            <div>

                <!-- USAGE TRACKING -->
                <div class="card">

                    <div class="card-header">

                        <h2>
                            Usage Tracking
                        </h2>

                        <p>
                            Current redemption information.
                        </p>

                    </div>

                    <div class="card-body">

                        <div class="usage-summary-card">

                            <div class="usage-row">

                                <span style="color:var(--text-mute);">
                                    Times Redeemed:
                                </span>

                                <strong
                                    style="
                                        color:var(--green);
                                        font-size:14px;
                                    "
                                >
                                    <?= $timesUsed ?>
                                </strong>

                            </div>

                            <div class="usage-row">

                                <span style="color:var(--text-mute);">
                                    Usage Capacity:
                                </span>

                                <strong>
                                    <?= $usageLimit !== ''
                                        ? e($usageLimit)
                                        : 'Unlimited'
                                    ?>
                                </strong>

                            </div>

                            <div class="usage-row">

                                <span style="color:var(--text-mute);">
                                    Remaining:
                                </span>

                                <strong id="remainingUsage">
                                    <?php
                                    if ($usageLimit !== '') {
                                        $remaining =
                                            max(
                                                0,
                                                (int)$usageLimit - $timesUsed
                                            );

                                        echo $remaining;
                                    } else {
                                        echo 'Unlimited';
                                    }
                                    ?>
                                </strong>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- VISIBILITY -->
                <div class="card">

                    <div class="card-header">

                        <h2>
                            Visibility &amp; Restrictions
                        </h2>

                        <p>
                            Customer targeting and availability.
                        </p>

                    </div>

                    <div class="card-body">

                        <!-- ACTIVE -->
                        <div class="status-box">

                            <div class="status-info">

                                <strong id="statusText">
                                    <?= $isActive ? 'Active' : 'Inactive' ?>
                                </strong>

                                <span id="statusDescription">
                                    <?= $isActive
                                        ? 'Coupon can be applied at checkout'
                                        : 'Coupon is currently disabled'
                                    ?>
                                </span>

                            </div>

                            <label class="switch">

                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    id="activeSwitch"
                                    <?= $isActive ? 'checked' : '' ?>
                                >

                                <span class="slider"></span>

                            </label>

                        </div>

                        <!-- WHOLESALE -->
                        <div class="status-box">

                            <div class="status-info">

                                <strong>
                                    Wholesale Only
                                </strong>

                                <span>
                                    Restricted to approved wholesale accounts
                                </span>

                            </div>

                            <label class="switch">

                                <input
                                    type="checkbox"
                                    name="is_for_wholesale_only"
                                    value="1"
                                    <?= $isForWholesaleOnly ? 'checked' : '' ?>
                                >

                                <span class="slider"></span>

                            </label>

                        </div>

                    </div>

                    <!-- FOOTER -->
                    <div class="action-footer">

                        <a
                            href="index.php"
                            class="btn"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary"
                            id="updateCouponButton"
                        >
                            ✓ Update Coupon
                        </button>

                    </div>

                </div>

            </div>

        </div>

    </form>

    <!-- KEYBOARD SHORTCUTS -->
    <div class="shortcut-box">

        <div class="shortcut-title">
            ⌨ Keyboard Shortcuts
        </div>

        <div class="shortcut-grid">

            <div class="shortcut">

                <span class="key">
                    Ctrl+S
                </span>

                <span class="key-text">
                    Save Changes
                </span>

            </div>

            <div class="shortcut">

                <span class="key">
                    Esc
                </span>

                <span class="key-text">
                    Cancel / Back
                </span>

            </div>

            <div class="shortcut">

                <span class="key">
                    G
                </span>

                <span class="key-text">
                    Generate Code
                </span>

            </div>

        </div>

    </div>

</div>

</section>

</main>

<script>

document.addEventListener('DOMContentLoaded', function () {

    'use strict';

    /* ================================================================
       ELEMENTS
       ================================================================ */

    const form = document.getElementById('couponEditForm');

    const codeInput = document.getElementById('couponCode');

    const typeSelect = document.getElementById('discountType');

    const valueInput = document.getElementById('discountValue');

    const valueUnitText = document.getElementById('valUnitText');

    const maxGroup = document.getElementById('maxDiscountGroup');

    const maxInput = document.getElementById('maxDiscountAmount');

    const generateButton = document.getElementById('btnGenCode');

    const activeSwitch = document.getElementById('activeSwitch');

    const statusText = document.getElementById('statusText');

    const statusDescription =
        document.getElementById('statusDescription');

    const startDate =
        document.getElementById('startDate');

    const endDate =
        document.getElementById('endDate');

    const usageLimit =
        document.getElementById('usageLimit');

    const remainingUsage =
        document.getElementById('remainingUsage');

    const updateButton =
        document.getElementById('updateCouponButton');

    /* ================================================================
       DISCOUNT TYPE
       ================================================================ */

    function syncDiscountType() {

        if (!typeSelect || !valueInput) {
            return;
        }

        if (typeSelect.value === 'Percentage') {

            if (valueUnitText) {
                valueUnitText.textContent = '(%)';
            }

            valueInput.max = '100';
            valueInput.placeholder = 'e.g. 15.00';

            if (maxGroup) {
                maxGroup.style.display = 'block';
            }

            if (maxInput) {
                maxInput.disabled = false;
            }

        } else {

            if (valueUnitText) {
                valueUnitText.textContent = '($)';
            }

            valueInput.removeAttribute('max');
            valueInput.placeholder = 'e.g. 25.00';

            if (maxGroup) {
                maxGroup.style.display = 'none';
            }

            if (maxInput) {
                maxInput.disabled = true;
            }
        }
    }

    if (typeSelect) {
        typeSelect.addEventListener(
            'change',
            syncDiscountType
        );

        syncDiscountType();
    }

    /* ================================================================
       COUPON CODE
       ================================================================ */

    if (codeInput) {

        codeInput.addEventListener(
            'input',
            function () {

                this.value = this.value
                    .toUpperCase()
                    .replace(/[^A-Z0-9_-]/g, '')
                    .substring(0, 50);
            }
        );

    }

    /* ================================================================
       GENERATE COUPON
       ================================================================ */

    function generateCouponCode() {

        const prefixes = [
            'PROMO',
            'SAVE',
            'OFF',
            'LINEN',
            'SPECIAL',
            'VIP',
            'DEAL'
        ];

        const characters =
            'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        const prefix =
            prefixes[
                Math.floor(
                    Math.random() * prefixes.length
                )
            ];

        const number =
            Math.floor(
                10 + Math.random() * 90
            );

        let suffix = '';

        for (let i = 0; i < 4; i++) {

            suffix += characters.charAt(
                Math.floor(
                    Math.random() * characters.length
                )
            );

        }

        return prefix + number + suffix;
    }

    if (generateButton) {

        generateButton.addEventListener(
            'click',
            function () {

                if (!codeInput) {
                    return;
                }

                codeInput.value =
                    generateCouponCode();

                codeInput.dispatchEvent(
                    new Event(
                        'input',
                        { bubbles: true }
                    )
                );

                codeInput.focus();

                codeInput.select();
            }
        );

    }

    /* ================================================================
       ACTIVE SWITCH
       ================================================================ */

    function syncActiveStatus() {

        if (!activeSwitch) {
            return;
        }

        const active =
            activeSwitch.checked;

        if (statusText) {
            statusText.textContent =
                active
                    ? 'Active'
                    : 'Inactive';
        }

        if (statusDescription) {
            statusDescription.textContent =
                active
                    ? 'Coupon can be applied at checkout'
                    : 'Coupon is currently disabled';
        }
    }

    if (activeSwitch) {

        activeSwitch.addEventListener(
            'change',
            syncActiveStatus
        );

        syncActiveStatus();
    }

    /* ================================================================
       REMAINING USAGE
       ================================================================ */

    const timesUsed =
        <?= (int)$timesUsed ?>;

    function updateRemainingUsage() {

        if (!usageLimit || !remainingUsage) {
            return;
        }

        const value =
            usageLimit.value.trim();

        if (value === '') {

            remainingUsage.textContent =
                'Unlimited';

            return;
        }

        const limit =
            parseInt(value, 10);

        if (
            Number.isNaN(limit)
            || limit < 0
        ) {

            remainingUsage.textContent =
                '—';

            return;
        }

        remainingUsage.textContent =
            Math.max(
                0,
                limit - timesUsed
            );
    }

    if (usageLimit) {

        usageLimit.addEventListener(
            'input',
            updateRemainingUsage
        );

        updateRemainingUsage();
    }

    /* ================================================================
       DATE VALIDATION
       ================================================================ */

    function validateDateOrder() {

        if (
            !startDate
            || !endDate
            || !startDate.value
            || !endDate.value
        ) {
            return true;
        }

        const start =
            new Date(
                startDate.value + 'T00:00:00'
            );

        const end =
            new Date(
                endDate.value + 'T00:00:00'
            );

        if (end < start) {

            endDate.setCustomValidity(
                'End date cannot be earlier than start date.'
            );

            return false;
        }

        endDate.setCustomValidity('');

        return true;
    }

    if (startDate) {
        startDate.addEventListener(
            'change',
            validateDateOrder
        );
    }

    if (endDate) {
        endDate.addEventListener(
            'change',
            validateDateOrder
        );
    }

    /* ================================================================
       FORM VALIDATION
       ================================================================ */

    if (form) {

        form.addEventListener(
            'submit',
            function (event) {

                if (!validateDateOrder()) {

                    event.preventDefault();

                    if (endDate) {
                        endDate.reportValidity();
                        endDate.focus();
                    }

                    return;
                }

                if (
                    typeSelect
                    && valueInput
                    && typeSelect.value === 'Percentage'
                    && parseFloat(valueInput.value) > 100
                ) {

                    event.preventDefault();

                    valueInput.setCustomValidity(
                        'Percentage discount cannot be greater than 100%.'
                    );

                    valueInput.reportValidity();

                    valueInput.focus();

                    return;

                } else if (valueInput) {

                    valueInput.setCustomValidity('');
                }

                /*
                 * Double submit protection
                 */

                if (form.dataset.submitting === '1') {

                    event.preventDefault();

                    return;
                }

                form.dataset.submitting = '1';

                if (updateButton) {

                    updateButton.disabled = true;

                    updateButton.innerHTML =
                        '⏳ Updating...';
                }

                if (generateButton) {
                    generateButton.disabled = true;
                }

            }
        );

    }

    /* ================================================================
       KEYBOARD SHORTCUTS
       ================================================================ */

    document.addEventListener(
        'keydown',
        function (event) {

            /*
             * Ctrl + S
             */

            if (
                event.ctrlKey
                && !event.shiftKey
                && event.key.toLowerCase() === 's'
            ) {

                event.preventDefault();

                if (form) {
                    form.requestSubmit();
                }

                return;
            }

            /*
             * G = Generate Coupon
             *
             * Do not trigger while typing in a form field.
             */

            if (
                event.key.toLowerCase() === 'g'
                && !event.ctrlKey
                && !event.altKey
                && !event.metaKey
            ) {

                const target =
                    event.target;

                const tag =
                    target
                    && target.tagName
                    ? target.tagName.toLowerCase()
                    : '';

                if (
                    tag !== 'input'
                    && tag !== 'textarea'
                    && tag !== 'select'
                ) {

                    event.preventDefault();

                    if (generateButton) {
                        generateButton.click();
                    }

                    return;
                }
            }

            /*
             * Escape
             */

            if (event.key === 'Escape') {

                const target =
                    event.target;

                const tag =
                    target
                    && target.tagName
                    ? target.tagName.toLowerCase()
                    : '';

                if (
                    tag === 'input'
                    || tag === 'textarea'
                    || tag === 'select'
                ) {

                    target.blur();

                    return;
                }

                window.location.href =
                    'index.php';
            }

        }
    );

    /* ================================================================
       GATEWAYLINEN THEME SYNC
       ================================================================ */

    const THEME_KEY =
        'gatewaylinen-theme';

    function normalizeTheme(theme) {

        return (
            theme === 'light'
            || theme === 'dark'
        )
            ? theme
            : 'dark';
    }

    function applyPageTheme(theme) {

        theme =
            normalizeTheme(theme);

        document.documentElement
            .setAttribute(
                'data-theme',
                theme
            );

        if (document.body) {

            document.body
                .setAttribute(
                    'data-theme',
                    theme
                );

            document.body.classList.toggle(
                'dark-mode',
                theme === 'dark'
            );

            document.body.classList.toggle(
                'light-mode',
                theme === 'light'
            );
        }
    }

    try {

        const savedTheme =
            localStorage.getItem(
                THEME_KEY
            );

        applyPageTheme(
            normalizeTheme(savedTheme)
        );

    } catch (error) {

        applyPageTheme('dark');
    }

    /*
     * Keep this page synchronized if another
     * GatewayLinen page/control changes theme.
     */

    window.addEventListener(
        'storage',
        function (event) {

            if (
                event.key === THEME_KEY
            ) {

                applyPageTheme(
                    normalizeTheme(
                        event.newValue
                    )
                );
            }
        }
    );

    /*
     * Support shared GatewayLinen theme system.
     */

    document.addEventListener(
        'gatewayThemeChanged',
        function (event) {

            if (
                event.detail
                && event.detail.theme
            ) {

                applyPageTheme(
                    event.detail.theme
                );
            }
        }
    );

});

</script>

<?php

require_once __DIR__ . '/../includes/footer.php';

?>