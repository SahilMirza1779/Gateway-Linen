<?php

session_start();

/*
|--------------------------------------------------------------------------
| GatewayLinen Admin - Add Coupon
|--------------------------------------------------------------------------
| File:
| GatewayLinenadmin/coupons/add.php
|
| Requires:
| ../config/database.php
| ../includes/header.php
| ../includes/sidebar.php
| ../includes/footer.php
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/

if (empty($_SESSION["admin_id"])) {
    header("Location: ../index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../config/database.php";

/*
|--------------------------------------------------------------------------
| PAGE SETTINGS
|--------------------------------------------------------------------------
*/

$activeMenu = "coupons";
$pageTitle  = "GatewayLinen | Add Coupon";

/*
|--------------------------------------------------------------------------
| ADMIN INFORMATION
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["admin_name"])) {
    $_SESSION["admin_name"] =
        $_SESSION["admin_username"]
        ?? "GatewayLinen Administrator";
}

if (!isset($_SESSION["admin_role"])) {
    $_SESSION["admin_role"] = "Administrator";
}

/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

// Convert DD/MM/YYYY input to SQL Server Y-m-d format
function toSqlDate($dateStr)
{
    $dateStr = trim((string)$dateStr);
    if ($dateStr === '') {
        return null;
    }

    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateStr, $matches)) {
        $day   = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
        $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
        $year  = $matches[3];

        if (checkdate((int)$month, (int)$day, (int)$year)) {
            return "{$year}-{$month}-{$day}";
        }
    }

    $timestamp = strtotime($dateStr);
    return $timestamp ? date('Y-m-d', $timestamp) : null;
}

/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION["coupon_csrf_token"])) {
    $_SESSION["coupon_csrf_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["coupon_csrf_token"];

/*
|--------------------------------------------------------------------------
| FORM VARIABLES (DD/MM/YYYY DEFAULTS)
|--------------------------------------------------------------------------
*/

$couponCode         = "";
$discountType       = "Percentage";
$discountValue      = "";
$minOrderAmount     = "";
$maxDiscountAmount  = "";
$startDate          = date('d/m/Y');
$endDate            = date('d/m/Y', strtotime('+30 days'));
$usageLimit         = "";
$isForWholesaleOnly = 0;
$isActive           = 1;

$error = "";

/*
|--------------------------------------------------------------------------
| SAVE COUPON
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /*
    |--------------------------------------------------------------------------
    | CSRF VALIDATION
    |--------------------------------------------------------------------------
    */
    $postedToken = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["coupon_csrf_token"], $postedToken)) {
        $error = "Security verification failed. Please refresh the page and try again.";
    }

    /*
    |--------------------------------------------------------------------------
    | GET FORM DATA
    |--------------------------------------------------------------------------
    */
    if ($error === "") {
        $couponCode         = strtoupper(trim($_POST["coupon_code"] ?? ""));
        $discountType       = trim($_POST["discount_type"] ?? "Percentage");
        $discountValue      = trim($_POST["discount_value"] ?? "");
        $minOrderAmount     = trim($_POST["min_order_amount"] ?? "");
        $maxDiscountAmount  = trim($_POST["max_discount_amount"] ?? "");
        $startDate          = trim($_POST["start_date"] ?? "");
        $endDate            = trim($_POST["end_date"] ?? "");
        $usageLimit         = trim($_POST["usage_limit"] ?? "");

        $isForWholesaleOnly = isset($_POST["is_for_wholesale_only"]) ? 1 : 0;
        $isActive           = isset($_POST["is_active"]) ? 1 : 0;
    }

    /*
    |--------------------------------------------------------------------------
    | BASIC VALIDATIONS
    |--------------------------------------------------------------------------
    */
    if ($error === "" && $couponCode === "") {
        $error = "Coupon code is required.";
    }

    if ($error === "" && mb_strlen($couponCode) > 50) {
        $error = "Coupon code cannot be longer than 50 characters.";
    }

    if ($error === "" && !preg_match("/^[A-Z0-9_\-]+$/", $couponCode)) {
        $error = "Coupon code must contain only letters, numbers, dashes and underscores.";
    }

    if ($error === "" && (!is_numeric($discountValue) || (float)$discountValue <= 0)) {
        $error = "Please enter a valid discount value greater than 0.";
    }

    if ($error === "" && $discountType === "Percentage" && (float)$discountValue > 100) {
        $error = "Percentage discount cannot be greater than 100%.";
    }

    if ($error === "" && $minOrderAmount !== "" && (!is_numeric($minOrderAmount) || (float)$minOrderAmount < 0)) {
        $error = "Minimum order amount must be a positive number.";
    }

    if ($error === "" && $maxDiscountAmount !== "" && (!is_numeric($maxDiscountAmount) || (float)$maxDiscountAmount < 0)) {
        $error = "Maximum discount amount must be a positive number.";
    }

    if ($error === "" && $usageLimit !== "" && (!ctype_digit($usageLimit) || (int)$usageLimit <= 0)) {
        $error = "Usage limit must be a positive integer.";
    }

    $sqlStartDate = toSqlDate($startDate);
    $sqlEndDate   = toSqlDate($endDate);

    if ($error === "" && (!empty($startDate) && empty($sqlStartDate))) {
        $error = "Invalid start date format. Please use DD/MM/YYYY.";
    }

    if ($error === "" && (!empty($endDate) && empty($sqlEndDate))) {
        $error = "Invalid end date format. Please use DD/MM/YYYY.";
    }

    if ($error === "" && (!empty($sqlStartDate) && !empty($sqlEndDate))) {
        if (strtotime($sqlEndDate) < strtotime($sqlStartDate)) {
            $error = "End date cannot be earlier than start date.";
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DUPLICATE COUPON CODE CHECK
    |--------------------------------------------------------------------------
    */
    if ($error === "") {
        $checkSql = "SELECT TOP 1 CouponId FROM dbo.Coupons WHERE CouponCode = ?";
        $checkStmt = sqlsrv_query($conn, $checkSql, [$couponCode]);

        if ($checkStmt !== false) {
            if (sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC)) {
                $error = "Coupon code '{$couponCode}' already exists. Please choose a different code.";
            }
            sqlsrv_free_stmt($checkStmt);
        } else {
            $error = "Unable to verify coupon code availability.";
        }
    }

    /*
    |--------------------------------------------------------------------------
    | INSERT COUPON INTO DATABASE
    |--------------------------------------------------------------------------
    */
    if ($error === "") {
        $insertSql = "
            INSERT INTO dbo.Coupons (
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
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, GETDATE()
            )
        ";

        $params = [
            $couponCode,
            $discountType,
            (float) $discountValue,
            $minOrderAmount !== "" ? (float) $minOrderAmount : null,
            $maxDiscountAmount !== "" ? (float) $maxDiscountAmount : null,
            $sqlStartDate,
            $sqlEndDate,
            $usageLimit !== "" ? (int) $usageLimit : null,
            $isForWholesaleOnly,
            $isActive
        ];

        $stmt = sqlsrv_query($conn, $insertSql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            $error = $errors[0]["message"] ?? "Failed to save the coupon.";
        } else {
            sqlsrv_free_stmt($stmt);

            $_SESSION["coupon_csrf_token"] = bin2hex(random_bytes(32));

            header("Location: index.php?success=" . urlencode("Coupon '{$couponCode}' created successfully."));
            exit;
        }
    }
}

/*
|--------------------------------------------------------------------------
| HEADER + SIDEBAR
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../includes/header.php";
require_once __DIR__ . "/../includes/sidebar.php";

?>

<!-- FLATPICKR CALENDAR CSS (DD/MM/YYYY PICKER SUPPORT) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://npmcdn.com/flatpickr/dist/themes/dark.css">

<style>
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
    --text-mute: #5f7488;
    --green: #10b981;
    --green-dark: #059669;
    --green-soft: rgba(16,185,129,.12);
    --blue: #3b82f6;
    --blue-soft: rgba(59,130,246,.12);
    --purple: #8b5cf6;
    --purple-soft: rgba(139,92,246,.12);
    --amber: #f59e0b;
    --amber-soft: rgba(245,158,11,.12);
    --red: #ef4444;
    --red-soft: rgba(239,68,68,.12);
}

html, body {
    background: #0a1119 !important;
    color: #a8b8c8 !important;
}

.main, .content {
    background: var(--bg-page) !important;
}

.add-category-page {
    width: 100%;
    max-width: 1180px;
    margin: 0 auto;
    padding: 20px 20px 45px;
    box-sizing: border-box;
    color: var(--text-body);
}

.add-category-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border);
}

.add-category-breadcrumb {
    display: flex;
    align-items: center;
    gap: 7px;
    margin-bottom: 8px;
    color: var(--text-mute);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .4px;
    text-transform: uppercase;
}

.add-category-breadcrumb .current { color: var(--green); }
.add-category-title {
    margin: 0;
    color: var(--text-hi);
    font-size: 27px;
    line-height: 1.2;
    font-weight: 800;
}
.add-category-subtitle {
    margin: 7px 0 0;
    color: var(--text-mute);
    font-size: 12px;
}

.add-category-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 40px;
    padding: 0 16px;
    border: 1px solid var(--border);
    border-radius: 9px;
    background: var(--bg-input);
    color: var(--text-body) !important;
    font-size: 11px;
    font-weight: 700;
    text-decoration: none;
    transition: .2s ease;
}
.add-category-back:hover {
    border-color: var(--green);
    background: var(--green-soft);
    color: var(--green) !important;
}

.add-category-error {
    display: flex;
    align-items: flex-start;
    gap: 11px;
    margin-bottom: 20px;
    padding: 14px 16px;
    border: 1px solid rgba(239,68,68,.3);
    border-left: 4px solid var(--red);
    border-radius: 9px;
    background: var(--red-soft);
    color: #fca5a5;
    font-size: 12px;
    font-weight: 600;
}

.add-category-form {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 15px 40px rgba(0,0,0,.18);
}

.add-category-form-body {
    padding: 24px 22px;
}

.add-category-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px 24px;
}

.add-category-section {
    grid-column: 1 / -1;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 10px;
    padding: 10px 14px;
    border-radius: 8px;
    background: var(--green-soft);
    border-left: 3px solid var(--green);
}
.add-category-section:first-child { margin-top: 0; }
.add-category-section.sec-blue { background: var(--blue-soft); border-left-color: var(--blue); }
.add-category-section.sec-purple { background: var(--purple-soft); border-left-color: var(--purple); }
.add-category-section.sec-amber { background: var(--amber-soft); border-left-color: var(--amber); }

.add-category-section-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 7px;
    background: rgba(16,185,129,.18);
    color: var(--green);
    font-weight: 900;
}
.sec-blue .add-category-section-icon { background: rgba(59,130,246,.18); color: var(--blue); }
.sec-purple .add-category-section-icon { background: rgba(139,92,246,.18); color: var(--purple); }
.sec-amber .add-category-section-icon { background: rgba(245,158,11,.18); color: var(--amber); }

.add-category-section-title {
    color: var(--text-hi);
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .8px;
}

.add-form-group { min-width: 0; }
.add-form-group-full { grid-column: 1 / -1; }

.add-form-label {
    display: block;
    margin-bottom: 7px;
    color: var(--text-body);
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .3px;
}
.add-form-required { color: var(--red); }

.add-form-input, .add-form-select {
    width: 100%;
    box-sizing: border-box;
    border: 1px solid var(--border);
    border-radius: 9px;
    background: var(--bg-input);
    color: var(--text-hi);
    font-family: inherit;
    font-size: 12px;
    transition: all .18s ease;
    height: 42px;
    padding: 0 13px;
}

.add-form-input:focus, .add-form-select:focus {
    outline: none;
    border-color: var(--green);
    box-shadow: 0 0 0 3px rgba(16,185,129,.13);
}

.add-form-select {
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%235f7488' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 14px;
    padding-right: 36px;
    cursor: pointer;
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

/* Date input styling */
.date-picker-input {
    letter-spacing: .5px;
    font-weight: 600;
    cursor: pointer;
}

/* Switches */
.flags-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px;
}
.add-active-box {
    display: flex;
    align-items: center;
    min-height: 42px;
    padding: 0 14px;
    border: 1px solid var(--border);
    border-radius: 9px;
    background: var(--bg-input);
}
.add-active-label {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    color: var(--text-body);
    font-size: 11.5px;
    font-weight: 600;
    cursor: pointer;
}
.add-active-checkbox {
    width: 17px;
    height: 17px;
    accent-color: var(--green);
    cursor: pointer;
}

.add-category-form-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 16px 22px;
    border-top: 1px solid var(--border);
    background: var(--bg-card-alt);
}

.add-category-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 42px;
    padding: 0 22px;
    border-radius: 9px;
    font-family: inherit;
    font-size: 11.5px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
}
.add-category-cancel {
    border: 1px solid var(--border);
    background: var(--bg-input);
    color: var(--text-body) !important;
}
.add-category-cancel:hover { background: var(--bg-hover); color: var(--text-hi) !important; }
.add-category-save {
    border: 1px solid var(--green-dark);
    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
    color: #fff;
}
.add-category-save:hover { filter: brightness(1.08); }

/* Shortcuts Box */
.shortcut-help-box {
    margin-top: 22px;
    padding: 16px 20px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: var(--bg-card);
}
.shortcut-help-box.hidden { display: none; }
.shortcut-help-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    color: var(--text-hi);
    font-size: 12.5px;
    font-weight: 700;
}
.shortcut-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 10px;
}
.shortcut-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 10px;
    border: 1px solid var(--border-soft);
    border-radius: 8px;
    background: var(--bg-input);
}
.shortcut-key {
    background: #0a1119;
    border: 1px solid var(--border);
    color: var(--green);
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 5px;
    font-family: monospace;
    font-size: 11px;
}
.shortcut-desc { font-size: 11px; font-weight: 600; color: var(--text-body); }

/* Flatpickr dark styling override */
.flatpickr-calendar.dark {
    background: #111b26 !important;
    border: 1px solid #1e2d3d !important;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5) !important;
}
.flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange {
    background: #10b981 !important;
    border-color: #10b981 !important;
}

@media (max-width: 860px) {
    .add-category-grid { grid-template-columns: 1fr; }
    .add-form-group-full { grid-column: auto; }
}
</style>

<main class="main">
    <section class="content">
        <div class="add-category-page">

            <!-- PAGE HEADER -->
            <div class="add-category-header">
                <div>
                    <div class="add-category-breadcrumb">
                        <span>Dashboard</span>
                        <span>›</span>
                        <span>Marketing</span>
                        <span>›</span>
                        <span class="current">Add Coupon</span>
                    </div>
                    <h1 class="add-category-title">Create Discount Coupon</h1>
                    <p class="add-category-subtitle">
                        Configure coupon codes, discount rates, order constraints, validity periods (DD/MM/YYYY) and limits.
                    </p>
                </div>
                <a href="index.php" class="add-category-back">
                    <span>←</span>
                    <span>Back to Coupons</span>
                </a>
            </div>

            <!-- ERROR NOTIFICATION -->
            <?php if ($error !== ""): ?>
                <div class="add-category-error">
                    <span style="font-weight:900;">!</span>
                    <div><?= e($error) ?></div>
                </div>
            <?php endif; ?>

            <!-- FORM -->
            <form method="POST" autocomplete="off" class="add-category-form" id="addCouponForm">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

                <div class="add-category-form-body">
                    <div class="add-category-grid">

                        <!-- SECTION: COUPON IDENTITY & DISCOUNT -->
                        <div class="add-category-section">
                            <div class="add-category-section-icon">🏷️</div>
                            <div class="add-category-section-title">Coupon & Discount Configuration</div>
                        </div>

                        <!-- COUPON CODE -->
                        <div class="add-form-group">
                            <label for="couponCode" class="add-form-label">
                                Coupon Code <span class="add-form-required">*</span>
                            </label>
                            <div class="input-with-action">
                                <input 
                                    type="text" 
                                    id="couponCode" 
                                    name="coupon_code" 
                                    class="add-form-input" 
                                    value="<?= e($couponCode) ?>" 
                                    placeholder="e.g. SUMMER25" 
                                    maxlength="50" 
                                    style="text-transform: uppercase; font-family: monospace; font-weight: 700;" 
                                    required
                                >
                                <button type="button" class="btn-action-input" id="btnGenCode" title="Generate random coupon code">
                                    ⚡ Generate
                                </button>
                            </div>
                            <div style="font-size:10px; color:var(--text-mute); margin-top:5px;">
                                Letters, numbers, and dashes only. Automatically converted to UPPERCASE.
                            </div>
                        </div>

                        <!-- DISCOUNT TYPE -->
                        <div class="add-form-group">
                            <label for="discountType" class="add-form-label">
                                Discount Type <span class="add-form-required">*</span>
                            </label>
                            <select id="discountType" name="discount_type" class="add-form-select" required>
                                <option value="Percentage" <?= $discountType === "Percentage" ? "selected" : "" ?>>Percentage (%)</option>
                                <option value="Fixed" <?= $discountType === "Fixed" ? "selected" : "" ?>>Fixed Amount ($)</option>
                            </select>
                        </div>

                        <!-- DISCOUNT VALUE -->
                        <div class="add-form-group">
                            <label for="discountValue" class="add-form-label">
                                Discount Value <span class="add-form-required">*</span> <span id="valUnitText">(%)</span>
                            </label>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0.01" 
                                id="discountValue" 
                                name="discount_value" 
                                class="add-form-input" 
                                value="<?= e($discountValue) ?>" 
                                placeholder="e.g. 15.00" 
                                required
                            >
                        </div>

                        <!-- MAX DISCOUNT AMOUNT (FOR PERCENTAGE DISCOUNTS) -->
                        <div class="add-form-group" id="maxDiscountGroup">
                            <label for="maxDiscountAmount" class="add-form-label">
                                Maximum Discount Cap ($) <small style="color:var(--text-mute);">(Optional)</small>
                            </label>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                id="maxDiscountAmount" 
                                name="max_discount_amount" 
                                class="add-form-input" 
                                value="<?= e($maxDiscountAmount) ?>" 
                                placeholder="e.g. 50.00"
                            >
                            <div style="font-size:10px; color:var(--text-mute); margin-top:5px;">
                                Highest dollar discount allowed when using percentage.
                            </div>
                        </div>

                        <!-- SECTION: RESTRICTIONS & VALIDITY -->
                        <div class="add-category-section sec-amber">
                            <div class="add-category-section-icon">📅</div>
                            <div class="add-category-section-title">Order Criteria & Validity Period (DD/MM/YYYY)</div>
                        </div>

                        <!-- MIN ORDER AMOUNT -->
                        <div class="add-form-group">
                            <label for="minOrderAmount" class="add-form-label">
                                Minimum Order Amount ($) <small style="color:var(--text-mute);">(Optional)</small>
                            </label>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                id="minOrderAmount" 
                                name="min_order_amount" 
                                class="add-form-input" 
                                value="<?= e($minOrderAmount) ?>" 
                                placeholder="0.00"
                            >
                            <div style="font-size:10px; color:var(--text-mute); margin-top:5px;">
                                Subtotal needed before this coupon can be applied.
                            </div>
                        </div>

                        <!-- USAGE LIMIT -->
                        <div class="add-form-group">
                            <label for="usageLimit" class="add-form-label">
                                Total Usage Limit <small style="color:var(--text-mute);">(Optional)</small>
                            </label>
                            <input 
                                type="number" 
                                step="1" 
                                min="1" 
                                id="usageLimit" 
                                name="usage_limit" 
                                class="add-form-input" 
                                value="<?= e($usageLimit) ?>" 
                                placeholder="Leave blank for unlimited"
                            >
                            <div style="font-size:10px; color:var(--text-mute); margin-top:5px;">
                                How many total times this coupon can be redeemed across all customers.
                            </div>
                        </div>

                        <!-- START DATE (DD/MM/YYYY) -->
                        <div class="add-form-group">
                            <label for="startDate" class="add-form-label">Start Date (DD/MM/YYYY)</label>
                            <input 
                                type="text" 
                                id="startDate" 
                                name="start_date" 
                                class="add-form-input date-picker-input" 
                                value="<?= e($startDate) ?>"
                                placeholder="DD/MM/YYYY"
                                maxlength="10"
                                required
                            >
                        </div>

                        <!-- END DATE (DD/MM/YYYY) -->
                        <div class="add-form-group">
                            <label for="endDate" class="add-form-label">End Date (DD/MM/YYYY)</label>
                            <input 
                                type="text" 
                                id="endDate" 
                                name="end_date" 
                                class="add-form-input date-picker-input" 
                                value="<?= e($endDate) ?>"
                                placeholder="DD/MM/YYYY"
                                maxlength="10"
                                required
                            >
                        </div>

                        <!-- SECTION: TARGET & STATUS -->
                        <div class="add-category-section sec-purple">
                            <div class="add-category-section-icon">✓</div>
                            <div class="add-category-section-title">Audience & Status</div>
                        </div>

                        <div class="add-form-group add-form-group-full">
                            <div class="flags-container">
                                <div class="add-active-box">
                                    <label class="add-active-label">
                                        <input type="checkbox" name="is_active" value="1" class="add-active-checkbox" <?= $isActive ? "checked" : "" ?>>
                                        <span>Active (Can be redeemed immediately)</span>
                                    </label>
                                </div>

                                <div class="add-active-box">
                                    <label class="add-active-label">
                                        <input type="checkbox" name="is_for_wholesale_only" value="1" class="add-active-checkbox" <?= $isForWholesaleOnly ? "checked" : "" ?>>
                                        <span>Wholesale Accounts Only</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- FOOTER BUTTONS -->
                <div class="add-category-form-footer">
                    <a href="index.php" class="add-category-btn add-category-cancel">Cancel</a>
                    <button type="submit" class="add-category-btn add-category-save" id="saveCouponBtn">
                        <span>✓</span>
                        <span>Save Coupon</span>
                    </button>
                </div>
            </form>

            <!-- KEYBOARD SHORTCUTS -->
            <div class="shortcut-help-box" id="shortcutHelpBox">
                <div class="shortcut-help-title">
                    <span>⌨ Keyboard Shortcuts</span>
                    <small style="margin-left:auto; color:var(--text-mute);">Press H to show/hide</small>
                </div>
                <div class="shortcut-grid">
                    <div class="shortcut-item"><span class="shortcut-key">A</span><span class="shortcut-desc">Save Coupon</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">B</span><span class="shortcut-desc">Back to Coupons</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">C</span><span class="shortcut-desc">Focus Coupon Code</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">V</span><span class="shortcut-desc">Focus Discount Value</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">G</span><span class="shortcut-desc">Generate Random Code</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">Esc</span><span class="shortcut-desc">Blur Current Field</span></div>
                </div>
            </div>

        </div>
    </section>
</main>

<!-- FLATPICKR DATEPICKER JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const codeInput     = document.getElementById("couponCode");
    const typeSelect    = document.getElementById("discountType");
    const valUnitText   = document.getElementById("valUnitText");
    const valInput      = document.getElementById("discountValue");
    const maxGroup      = document.getElementById("maxDiscountGroup");
    const btnGen        = document.getElementById("btnGenCode");
    const form          = document.getElementById("addCouponForm");
    const saveBtn       = document.getElementById("saveCouponBtn");
    const shortcutBox   = document.getElementById("shortcutHelpBox");
    const startInput    = document.getElementById("startDate");
    const endInput      = document.getElementById("endDate");

    // Flatpickr Calendar in DD/MM/YYYY format
    if (window.flatpickr) {
        flatpickr(startInput, {
            dateFormat: "d/m/Y",
            allowInput: true,
            theme: "dark"
        });

        flatpickr(endInput, {
            dateFormat: "d/m/Y",
            allowInput: true,
            theme: "dark"
        });
    }

    // Auto-mask slashes on manual typing for DD/MM/YYYY
    function setupDateAutoSlash(input) {
        input.addEventListener("input", function(e) {
            let v = this.value.replace(/\D/g, '').slice(0, 8);
            if (v.length >= 5) {
                this.value = v.slice(0, 2) + '/' + v.slice(2, 4) + '/' + v.slice(4);
            } else if (v.length >= 3) {
                this.value = v.slice(0, 2) + '/' + v.slice(2);
            } else {
                this.value = v;
            }
        });
    }

    setupDateAutoSlash(startInput);
    setupDateAutoSlash(endInput);

    // Dynamic label and fields based on Percentage vs Fixed
    function syncDiscountType() {
        if (typeSelect.value === "Percentage") {
            valUnitText.textContent = "(%)";
            valInput.max = "100";
            valInput.placeholder = "e.g. 15.00";
            if (maxGroup) maxGroup.style.display = "block";
        } else {
            valUnitText.textContent = "($)";
            valInput.removeAttribute("max");
            valInput.placeholder = "e.g. 25.00";
            if (maxGroup) maxGroup.style.display = "none";
        }
    }

    typeSelect.addEventListener("change", syncDiscountType);
    syncDiscountType();

    // Auto-uppercase code while typing
    codeInput.addEventListener("input", function() {
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9_\-]/g, "");
    });

    // Random Coupon Code Generator
    function generateCouponCode() {
        const prefixes = ["SALE", "SAVE", "LINEN", "HOTEL", "DEAL", "GIFT", "VIP"];
        const prefix = prefixes[Math.floor(Math.random() * prefixes.length)];
        const num = Math.floor(10 + Math.random() * 90);
        const chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
        let suffix = "";
        for (let i = 0; i < 3; i++) {
            suffix += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return prefix + num + suffix;
    }

    btnGen.addEventListener("click", function(e) {
        e.preventDefault();
        codeInput.value = generateCouponCode();
        codeInput.focus();
    });

    // Double Submit Protection
    let isSubmitting = false;
    if (form && saveBtn) {
        form.addEventListener("submit", function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return;
            }
            isSubmitting = true;
            saveBtn.disabled = true;
            saveBtn.style.opacity = "0.7";
            saveBtn.innerHTML = "<span>✓</span><span>Saving Coupon...</span>";
        });
    }

    // Keyboard Shortcuts
    let helpVisible = true;
    document.addEventListener("keydown", function(e) {
        const key = e.key.toLowerCase();
        const active = document.activeElement;
        const isTyping = active && (active.tagName === "INPUT" || active.tagName === "TEXTAREA" || active.tagName === "SELECT");

        if (key === "h" && !e.ctrlKey && !e.altKey && !e.metaKey && !isTyping) {
            e.preventDefault();
            helpVisible = !helpVisible;
            shortcutBox.classList.toggle("hidden", !helpVisible);
            return;
        }

        if (e.key === "Escape" && active && typeof active.blur === "function") {
            active.blur();
            return;
        }

        if (isTyping || e.ctrlKey || e.altKey || e.metaKey) return;

        if (key === "a") {
            e.preventDefault();
            form.requestSubmit ? form.requestSubmit() : form.submit();
        } else if (key === "b") {
            e.preventDefault();
            window.location.href = "index.php";
        } else if (key === "c" && codeInput) {
            e.preventDefault();
            codeInput.focus();
            codeInput.select();
        } else if (key === "v" && valInput) {
            e.preventDefault();
            valInput.focus();
            valInput.select();
        } else if (key === "g" && btnGen) {
            e.preventDefault();
            btnGen.click();
        }
    });

    if (codeInput && window.innerWidth > 768) {
        setTimeout(() => codeInput.focus(), 150);
    }
});
</script>

<?php
require_once __DIR__ . "/../includes/footer.php";
?>