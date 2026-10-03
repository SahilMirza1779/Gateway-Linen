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
| ADMIN LOGIN CHECK
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
| HELPER
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}


/*
|--------------------------------------------------------------------------
| DATE CONVERTER
| DD/MM/YYYY -> YYYY-MM-DD
|--------------------------------------------------------------------------
*/

function toSqlDate($dateStr)
{
    $dateStr = trim((string)$dateStr);

    if ($dateStr === "") {
        return null;
    }

    if (
        preg_match(
            '/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/',
            $dateStr,
            $matches
        )
    ) {
        $day   = str_pad($matches[1], 2, "0", STR_PAD_LEFT);
        $month = str_pad($matches[2], 2, "0", STR_PAD_LEFT);
        $year  = $matches[3];

        if (
            checkdate(
                (int)$month,
                (int)$day,
                (int)$year
            )
        ) {
            return "{$year}-{$month}-{$day}";
        }
    }

    $timestamp = strtotime($dateStr);

    return $timestamp
        ? date("Y-m-d", $timestamp)
        : null;
}


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION["coupon_csrf_token"])) {
    $_SESSION["coupon_csrf_token"] =
        bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["coupon_csrf_token"];


/*
|--------------------------------------------------------------------------
| DEFAULT FORM VALUES
|--------------------------------------------------------------------------
*/

$couponCode         = "";
$discountType       = "Percentage";
$discountValue      = "";
$minOrderAmount     = "";
$maxDiscountAmount  = "";
$startDate          = date("d/m/Y");
$endDate            = date(
    "d/m/Y",
    strtotime("+30 days")
);
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
    | CSRF
    |--------------------------------------------------------------------------
    */

    $postedToken =
        $_POST["csrf_token"] ?? "";

    if (
        empty($_SESSION["coupon_csrf_token"]) ||
        !hash_equals(
            $_SESSION["coupon_csrf_token"],
            $postedToken
        )
    ) {
        $error =
            "Security verification failed. Please refresh the page and try again.";
    }


    /*
    |--------------------------------------------------------------------------
    | GET FORM DATA
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        $couponCode = strtoupper(
            trim($_POST["coupon_code"] ?? "")
        );

        $discountType =
            trim(
                $_POST["discount_type"]
                ?? "Percentage"
            );

        $discountValue =
            trim(
                $_POST["discount_value"]
                ?? ""
            );

        $minOrderAmount =
            trim(
                $_POST["min_order_amount"]
                ?? ""
            );

        $maxDiscountAmount =
            trim(
                $_POST["max_discount_amount"]
                ?? ""
            );

        $startDate =
            trim(
                $_POST["start_date"]
                ?? ""
            );

        $endDate =
            trim(
                $_POST["end_date"]
                ?? ""
            );

        $usageLimit =
            trim(
                $_POST["usage_limit"]
                ?? ""
            );

        $isForWholesaleOnly =
            isset($_POST["is_for_wholesale_only"])
                ? 1
                : 0;

        $isActive =
            isset($_POST["is_active"])
                ? 1
                : 0;
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATE COUPON CODE
    |--------------------------------------------------------------------------
    */

    if (
        $error === "" &&
        $couponCode === ""
    ) {
        $error =
            "Coupon code is required.";
    }

    if (
        $error === "" &&
        mb_strlen($couponCode) > 50
    ) {
        $error =
            "Coupon code cannot be longer than 50 characters.";
    }

    if (
        $error === "" &&
        !preg_match(
            "/^[A-Z0-9_-]+$/",
            $couponCode
        )
    ) {
        $error =
            "Coupon code can contain only letters, numbers, dashes and underscores.";
    }


    /*
    |--------------------------------------------------------------------------
    | DISCOUNT TYPE
    |--------------------------------------------------------------------------
    */

    if (
        $error === "" &&
        !in_array(
            $discountType,
            ["Percentage", "Fixed"],
            true
        )
    ) {
        $error =
            "Invalid discount type selected.";
    }


    /*
    |--------------------------------------------------------------------------
    | DISCOUNT VALUE
    |--------------------------------------------------------------------------
    */

    if (
        $error === "" &&
        (
            $discountValue === "" ||
            !is_numeric($discountValue) ||
            (float)$discountValue <= 0
        )
    ) {
        $error =
            "Please enter a valid discount value greater than 0.";
    }

    if (
        $error === "" &&
        $discountType === "Percentage" &&
        (float)$discountValue > 100
    ) {
        $error =
            "Percentage discount cannot be greater than 100%.";
    }


    /*
    |--------------------------------------------------------------------------
    | MIN ORDER
    |--------------------------------------------------------------------------
    */

    if (
        $error === "" &&
        $minOrderAmount !== "" &&
        (
            !is_numeric($minOrderAmount) ||
            (float)$minOrderAmount < 0
        )
    ) {
        $error =
            "Minimum order amount must be 0 or greater.";
    }


    /*
    |--------------------------------------------------------------------------
    | MAX DISCOUNT
    |--------------------------------------------------------------------------
    */

    if (
        $error === "" &&
        $maxDiscountAmount !== "" &&
        (
            !is_numeric($maxDiscountAmount) ||
            (float)$maxDiscountAmount < 0
        )
    ) {
        $error =
            "Maximum discount amount must be 0 or greater.";
    }


    /*
    |--------------------------------------------------------------------------
    | USAGE LIMIT
    |--------------------------------------------------------------------------
    */

    if (
        $error === "" &&
        $usageLimit !== "" &&
        (
            !ctype_digit($usageLimit) ||
            (int)$usageLimit <= 0
        )
    ) {
        $error =
            "Usage limit must be a positive whole number.";
    }


    /*
    |--------------------------------------------------------------------------
    | DATES
    |--------------------------------------------------------------------------
    */

    $sqlStartDate = toSqlDate($startDate);
    $sqlEndDate   = toSqlDate($endDate);

    if (
        $error === "" &&
        empty($sqlStartDate)
    ) {
        $error =
            "Invalid start date. Please use DD/MM/YYYY.";
    }

    if (
        $error === "" &&
        empty($sqlEndDate)
    ) {
        $error =
            "Invalid end date. Please use DD/MM/YYYY.";
    }

    if (
        $error === "" &&
        !empty($sqlStartDate) &&
        !empty($sqlEndDate)
    ) {
        if (
            strtotime($sqlEndDate) <
            strtotime($sqlStartDate)
        ) {
            $error =
                "End date cannot be earlier than start date.";
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DUPLICATE COUPON CHECK
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        $checkSql = "
            SELECT TOP 1 CouponId
            FROM dbo.Coupons
            WHERE CouponCode = ?
        ";

        $checkStmt =
            sqlsrv_query(
                $conn,
                $checkSql,
                [$couponCode]
            );

        if ($checkStmt === false) {

            $errors = sqlsrv_errors();

            $error =
                $errors[0]["message"]
                ?? "Unable to verify coupon code availability.";

        } else {

            $existingCoupon =
                sqlsrv_fetch_array(
                    $checkStmt,
                    SQLSRV_FETCH_ASSOC
                );

            if ($existingCoupon) {
                $error =
                    "Coupon code '{$couponCode}' already exists. Please choose another code.";
            }

            sqlsrv_free_stmt($checkStmt);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | INSERT COUPON
    |--------------------------------------------------------------------------
    */

    if ($error === "") {

        $insertSql = "

            INSERT INTO dbo.Coupons
            (
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
            )

            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                0,
                ?,
                ?,
                GETDATE()
            )

        ";

        $params = [

            $couponCode,

            $discountType,

            (float)$discountValue,

            $minOrderAmount !== ""
                ? (float)$minOrderAmount
                : null,

            $maxDiscountAmount !== ""
                ? (float)$maxDiscountAmount
                : null,

            $sqlStartDate,

            $sqlEndDate,

            $usageLimit !== ""
                ? (int)$usageLimit
                : null,

            $isForWholesaleOnly,

            $isActive
        ];


        $stmt =
            sqlsrv_query(
                $conn,
                $insertSql,
                $params
            );


        if ($stmt === false) {

            $errors = sqlsrv_errors();

            $error =
                $errors[0]["message"]
                ?? "Failed to save the coupon.";

        } else {

            sqlsrv_free_stmt($stmt);

            $_SESSION["coupon_csrf_token"] =
                bin2hex(random_bytes(32));

            header(
                "Location: index.php?success=" .
                urlencode(
                    "Coupon '{$couponCode}' created successfully."
                )
            );

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


<!-- ================================================================
     GLOBAL THEME BOOTSTRAP
================================================================ -->

<script>

(function () {

    try {

        var savedTheme =
            localStorage.getItem("gatewaylinen-theme");

        var theme =
            (
                savedTheme === "light" ||
                savedTheme === "dark"
            )
            ? savedTheme
            : "dark";

        document.documentElement.setAttribute(
            "data-theme",
            theme
        );

    } catch (e) {

        document.documentElement.setAttribute(
            "data-theme",
            "dark"
        );

    }

})();

</script>


<!-- ================================================================
     FLATPICKR
================================================================ -->

<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css"
>


<style>

/* ================================================================
   GATEWAYLINEN COUPON PAGE
================================================================ */

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
    --green-soft: rgba(16,185,129,.12);

    --blue: #3b82f6;
    --blue-soft: rgba(59,130,246,.12);

    --purple: #8b5cf6;
    --purple-soft: rgba(139,92,246,.12);

    --amber: #f59e0b;
    --amber-soft: rgba(245,158,11,.12);

    --red: #ef4444;
    --red-soft: rgba(239,68,68,.12);

    --shadow:
        0 15px 40px rgba(0,0,0,.22);

    --focus-ring:
        rgba(16,185,129,.13);

    --shortcut-key-bg:
        #0a1119;
}


/* ================================================================
   LIGHT THEME
================================================================ */

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
    --green-soft: rgba(16,185,129,.11);

    --blue-soft: rgba(59,130,246,.10);
    --purple-soft: rgba(139,92,246,.10);
    --amber-soft: rgba(245,158,11,.12);
    --red-soft: rgba(239,68,68,.10);

    --shadow:
        0 15px 40px rgba(15,23,42,.08);

    --focus-ring:
        rgba(16,185,129,.15);

    --shortcut-key-bg:
        #f1f5f9;
}


/* ================================================================
   GLOBAL
================================================================ */

html[data-theme="dark"] {
    color-scheme: dark;
}

html[data-theme="light"] {
    color-scheme: light;
}

html,
body {

    background:
        var(--bg-page) !important;

    color:
        var(--text-body) !important;
}

.main,
.content {

    background:
        var(--bg-page) !important;

    color:
        var(--text-body) !important;
}


/* ================================================================
   PAGE
================================================================ */

.add-category-page {

    width: 100%;
    max-width: 1180px;

    margin: 0 auto;

    padding:
        20px 20px 45px;

    box-sizing: border-box;

    color:
        var(--text-body);
}

.add-category-page * {
    box-sizing: border-box;
}


/* ================================================================
   HEADER
================================================================ */

.add-category-header {

    display: flex;

    align-items: flex-end;

    justify-content:
        space-between;

    gap: 20px;

    margin-bottom: 24px;

    padding-bottom: 20px;

    border-bottom:
        1px solid var(--border);
}

.add-category-breadcrumb {

    display: flex;

    align-items: center;

    gap: 7px;

    margin-bottom: 8px;

    color:
        var(--text-mute);

    font-size: 10px;

    font-weight: 700;

    letter-spacing: .4px;

    text-transform: uppercase;
}

.add-category-breadcrumb .current {

    color:
        var(--green);
}

.add-category-title {

    margin: 0;

    color:
        var(--text-hi);

    font-size: 27px;

    line-height: 1.2;

    font-weight: 800;
}

.add-category-subtitle {

    margin: 7px 0 0;

    color:
        var(--text-mute);

    font-size: 12px;

    line-height: 1.6;
}


/* ================================================================
   BACK BUTTON
================================================================ */

.add-category-back {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    min-height: 40px;

    padding:
        0 16px;

    border:
        1px solid var(--border);

    border-radius: 9px;

    background:
        var(--bg-input);

    color:
        var(--text-body) !important;

    font-size: 11px;

    font-weight: 700;

    text-decoration: none;

    transition:
        .2s ease;
}

.add-category-back:hover {

    border-color:
        var(--green);

    background:
        var(--green-soft);

    color:
        var(--green) !important;
}


/* ================================================================
   ERROR
================================================================ */

.add-category-error {

    display: flex;

    align-items: flex-start;

    gap: 11px;

    margin-bottom: 20px;

    padding:
        14px 16px;

    border:
        1px solid rgba(239,68,68,.30);

    border-left:
        4px solid var(--red);

    border-radius: 9px;

    background:
        var(--red-soft);

    color:
        #b91c1c;

    font-size: 12px;

    font-weight: 600;
}

html[data-theme="dark"]
.add-category-error {

    color:
        #fca5a5;
}

.add-category-error-icon {

    display: flex;

    align-items: center;

    justify-content: center;

    width: 22px;

    height: 22px;

    flex: 0 0 22px;

    border-radius: 50%;

    background:
        var(--red);

    color: #fff;

    font-size: 12px;

    font-weight: 900;
}


/* ================================================================
   FORM
================================================================ */

.add-category-form {

    background:
        var(--bg-card);

    border:
        1px solid var(--border);

    border-radius:
        12px;

    overflow:
        hidden;

    box-shadow:
        var(--shadow);
}

.add-category-form-body {

    padding:
        24px 22px;

    background:
        var(--bg-card);
}

.add-category-grid {

    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap:
        20px 24px;
}


/* ================================================================
   SECTION HEADERS
================================================================ */

.add-category-section {

    grid-column:
        1 / -1;

    display: flex;

    align-items: center;

    gap: 10px;

    margin-top: 10px;

    padding:
        10px 14px;

    border-radius: 8px;

    background:
        var(--green-soft);

    border-left:
        3px solid var(--green);
}

.add-category-section:first-child {
    margin-top: 0;
}

.add-category-section.sec-blue {

    background:
        var(--blue-soft);

    border-left-color:
        var(--blue);
}

.add-category-section.sec-purple {

    background:
        var(--purple-soft);

    border-left-color:
        var(--purple);
}

.add-category-section.sec-amber {

    background:
        var(--amber-soft);

    border-left-color:
        var(--amber);
}

.add-category-section-icon {

    display: flex;

    align-items: center;

    justify-content: center;

    width: 28px;

    height: 28px;

    border-radius: 7px;

    background:
        rgba(16,185,129,.18);

    color:
        var(--green);

    font-size: 14px;

    font-weight: 900;
}

.sec-blue .add-category-section-icon {

    background:
        rgba(59,130,246,.18);

    color:
        var(--blue);
}

.sec-purple .add-category-section-icon {

    background:
        rgba(139,92,246,.18);

    color:
        var(--purple);
}

.sec-amber .add-category-section-icon {

    background:
        rgba(245,158,11,.18);

    color:
        var(--amber);
}

.add-category-section-title {

    color:
        var(--text-hi);

    font-size: 11px;

    font-weight: 800;

    text-transform:
        uppercase;

    letter-spacing:
        .8px;
}


/* ================================================================
   FORM GROUP
================================================================ */

.add-form-group {
    min-width: 0;
}

.add-form-group-full {
    grid-column: 1 / -1;
}

.add-form-label {

    display: flex;

    align-items: center;

    justify-content:
        space-between;

    gap: 10px;

    margin-bottom: 7px;

    color:
        var(--text-body);

    font-size: 10.5px;

    font-weight: 700;

    text-transform:
        uppercase;

    letter-spacing:
        .3px;
}

.add-form-required {
    color:
        var(--red);
}


/* ================================================================
   SHORTCUT BADGE
================================================================ */

.shortcut-badge {

    padding:
        3px 7px;

    border:
        1px solid var(--border);

    border-radius: 5px;

    background:
        var(--shortcut-key-bg);

    color:
        var(--text-mute);

    font-family:
        monospace;

    font-size: 9px;

    text-transform:
        none;

    letter-spacing: 0;
}


/* ================================================================
   INPUTS
================================================================ */

.add-form-input,
.add-form-select {

    width: 100%;

    height: 42px;

    padding:
        0 13px;

    box-sizing:
        border-box;

    border:
        1px solid var(--border);

    border-radius:
        9px;

    background:
        var(--bg-input);

    color:
        var(--text-hi);

    font-family:
        inherit;

    font-size:
        12px;

    transition:
        all .18s ease;
}

.add-form-input::placeholder {
    color:
        var(--text-mute);

    opacity:
        .8;
}

.add-form-input:focus,
.add-form-select:focus {

    outline: none;

    border-color:
        var(--green);

    box-shadow:
        0 0 0 3px var(--focus-ring);
}


/* ================================================================
   SELECT
================================================================ */

.add-form-select {

    appearance: none;

    background-image:
        url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364758b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");

    background-repeat:
        no-repeat;

    background-position:
        right 12px center;

    background-size:
        14px;

    padding-right:
        36px;

    cursor:
        pointer;
}

html[data-theme="dark"]
.add-form-select option {

    background:
        #0d1620;

    color:
        #f0f4f8;
}

html[data-theme="light"]
.add-form-select option {

    background:
        #ffffff;

    color:
        #172033;
}


/* ================================================================
   COUPON CODE ACTION
================================================================ */

.input-with-action {

    display: flex;

    gap: 8px;
}

.input-with-action
.add-form-input {

    flex: 1;
}

.btn-action-input {

    height: 42px;

    padding:
        0 14px;

    border:
        1px solid var(--border);

    border-radius:
        8px;

    background:
        var(--bg-input);

    color:
        var(--text-body);

    font-size:
        11px;

    font-weight:
        700;

    cursor:
        pointer;

    white-space:
        nowrap;

    transition:
        all .2s ease;
}

.btn-action-input:hover {

    border-color:
        var(--green);

    color:
        var(--green);

    background:
        var(--green-soft);
}


/* ================================================================
   FIELD HELP
================================================================ */

.form-help {

    margin-top:
        5px;

    color:
        var(--text-mute);

    font-size:
        10px;

    line-height:
        1.5;
}


/* ================================================================
   DATE INPUT
================================================================ */

.date-picker-input {

    letter-spacing:
        .5px;

    font-weight:
        600;

    cursor:
        pointer;
}


/* ================================================================
   FLAGS
================================================================ */

.flags-container {

    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap:
        12px;
}

.add-active-box {

    display: flex;

    align-items: center;

    min-height:
        48px;

    padding:
        0 14px;

    border:
        1px solid var(--border);

    border-radius:
        9px;

    background:
        var(--bg-input);
}

.add-active-label {

    display: inline-flex;

    align-items: center;

    gap: 9px;

    color:
        var(--text-body);

    font-size:
        11.5px;

    font-weight:
        600;

    cursor:
        pointer;
}

.add-active-checkbox {

    width:
        17px;

    height:
        17px;

    accent-color:
        var(--green);

    cursor:
        pointer;
}


/* ================================================================
   FOOTER
================================================================ */

.add-category-form-footer {

    display: flex;

    align-items: center;

    justify-content:
        flex-end;

    gap:
        10px;

    padding:
        16px 22px;

    border-top:
        1px solid var(--border);

    background:
        var(--bg-card-alt);
}

.add-category-btn {

    display: inline-flex;

    align-items: center;

    justify-content:
        center;

    gap:
        8px;

    min-height:
        42px;

    padding:
        0 22px;

    border-radius:
        9px;

    font-family:
        inherit;

    font-size:
        11.5px;

    font-weight:
        700;

    cursor:
        pointer;

    text-decoration:
        none;

    transition:
        .2s ease;
}

.add-category-cancel {

    border:
        1px solid var(--border);

    background:
        var(--bg-input);

    color:
        var(--text-body) !important;
}

.add-category-cancel:hover {

    background:
        var(--bg-hover);

    color:
        var(--text-hi) !important;
}

.add-category-save {

    border:
        1px solid var(--green-dark);

    background:
        linear-gradient(
            135deg,
            #059669 0%,
            #10b981 100%
        );

    color:
        #ffffff;
}

.add-category-save:hover {

    filter:
        brightness(1.08);
}

.add-category-save:disabled {

    cursor:
        not-allowed;

    opacity:
        .7;
}


/* ================================================================
   SHORTCUT HELP
================================================================ */

.shortcut-help-box {

    margin-top:
        22px;

    padding:
        16px 20px;

    border:
        1px solid var(--border);

    border-radius:
        12px;

    background:
        var(--bg-card);

    box-shadow:
        var(--shadow);
}

.shortcut-help-box.hidden {
    display:
        none;
}

.shortcut-help-title {

    display: flex;

    align-items: center;

    gap: 8px;

    margin-bottom:
        12px;

    color:
        var(--text-hi);

    font-size:
        12.5px;

    font-weight:
        700;
}

.shortcut-help-title small {

    margin-left:
        auto;

    color:
        var(--text-mute);

    font-size:
        10px;

    font-weight:
        600;
}

.shortcut-grid {

    display: grid;

    grid-template-columns:
        repeat(
            auto-fill,
            minmax(190px, 1fr)
        );

    gap:
        10px;
}

.shortcut-item {

    display: flex;

    align-items: center;

    gap: 10px;

    padding:
        7px 10px;

    border:
        1px solid var(--border-soft);

    border-radius:
        8px;

    background:
        var(--bg-input);
}

.shortcut-key {

    min-width:
        32px;

    text-align:
        center;

    background:
        var(--shortcut-key-bg);

    border:
        1px solid var(--border);

    color:
        var(--green);

    font-weight:
        800;

    padding:
        3px 7px;

    border-radius:
        5px;

    font-family:
        monospace;

    font-size:
        11px;
}

.shortcut-desc {

    font-size:
        11px;

    font-weight:
        600;

    color:
        var(--text-body);
}


/* ================================================================
   FLATPICKR
================================================================ */

.flatpickr-calendar {

    background:
        var(--bg-card) !important;

    border:
        1px solid var(--border) !important;

    box-shadow:
        var(--shadow) !important;

    color:
        var(--text-body) !important;
}

.flatpickr-months
.flatpickr-month,

.flatpickr-current-month
.flatpickr-monthDropdown-months,

.flatpickr-current-month
input.cur-year {

    background:
        var(--bg-card) !important;

    color:
        var(--text-hi) !important;

    fill:
        var(--text-hi) !important;
}

.flatpickr-weekdays {

    background:
        var(--bg-card) !important;
}

span.flatpickr-weekday {

    background:
        var(--bg-card) !important;

    color:
        var(--text-mute) !important;
}

.flatpickr-day {

    color:
        var(--text-body) !important;

    border-color:
        transparent !important;
}

.flatpickr-day:hover {

    background:
        var(--bg-hover) !important;
}

.flatpickr-day.prevMonthDay,
.flatpickr-day.nextMonthDay {

    color:
        var(--text-mute) !important;
}

.flatpickr-day.today {

    border-color:
        var(--green) !important;
}

.flatpickr-day.selected,
.flatpickr-day.startRange,
.flatpickr-day.endRange {

    background:
        var(--green) !important;

    border-color:
        var(--green) !important;

    color:
        #ffffff !important;
}

.flatpickr-time {

    background:
        var(--bg-card) !important;

    border-top:
        1px solid var(--border) !important;
}

.flatpickr-time input,
.flatpickr-time .flatpickr-am-pm {

    background:
        var(--bg-input) !important;

    color:
        var(--text-hi) !important;
}

.flatpickr-prev-month,
.flatpickr-next-month {

    color:
        var(--text-body) !important;

    fill:
        var(--text-body) !important;
}

.flatpickr-prev-month:hover,
.flatpickr-next-month:hover {

    color:
        var(--green) !important;

    fill:
        var(--green) !important;
}


/* ================================================================
   TRANSITIONS
================================================================ */

.add-category-page,
.add-category-form,
.add-category-form-body,
.add-category-form-footer,
.shortcut-help-box,
.add-active-box,
.shortcut-item,
.add-form-input,
.add-form-select,
.btn-action-input,
.add-category-back,
.add-category-cancel {

    transition:
        background-color .18s ease,
        color .18s ease,
        border-color .18s ease,
        box-shadow .18s ease;
}


/* ================================================================
   RESPONSIVE
================================================================ */

@media (max-width: 860px) {

    .add-category-grid {

        grid-template-columns:
            1fr;
    }

    .add-form-group-full {

        grid-column:
            auto;
    }

    .add-category-header {

        align-items:
            flex-start;

        flex-direction:
            column;
    }

    .add-category-back {

        align-self:
            flex-start;
    }

    .flags-container {

        grid-template-columns:
            1fr;
    }
}


@media (max-width: 560px) {

    .add-category-page {

        padding:
            15px 12px 35px;
    }

    .add-category-form-body {

        padding:
            18px 14px;
    }

    .add-category-form-footer {

        padding:
            14px;

        flex-direction:
            column-reverse;
    }

    .add-category-btn {

        width:
            100%;
    }

    .shortcut-grid {

        grid-template-columns:
            1fr;
    }

    .input-with-action {

        flex-direction:
            column;
    }

    .btn-action-input {

        width:
            100%;
    }

    .add-category-title {

        font-size:
            23px;
    }
}

</style>


<!-- ================================================================
     MAIN
================================================================ -->

<main class="main">

    <section class="content">

        <div class="add-category-page">


            <!-- ========================================================
                 PAGE HEADER
            ========================================================= -->

            <div class="add-category-header">

                <div>

                    <div class="add-category-breadcrumb">

                        <span>Dashboard</span>

                        <span>›</span>

                        <span>Marketing</span>

                        <span>›</span>

                        <span class="current">
                            Add Coupon
                        </span>

                    </div>


                    <h1 class="add-category-title">

                        Create Discount Coupon

                    </h1>


                    <p class="add-category-subtitle">

                        Create a coupon with discount rules,
                        order limits, validity dates,
                        usage limits and customer targeting.

                    </p>

                </div>


                <a
                    href="index.php"
                    class="add-category-back"
                    title="Shortcut: B"
                >

                    <span>←</span>

                    <span>
                        Back to Coupons
                        <small
                            style="
                                opacity:.7;
                                font-size:9px;
                            "
                        >
                            [B]
                        </small>
                    </span>

                </a>

            </div>


            <!-- ========================================================
                 ERROR
            ========================================================= -->

            <?php if ($error !== ""): ?>

                <div class="add-category-error">

                    <div class="add-category-error-icon">
                        !
                    </div>

                    <div>
                        <?= e($error) ?>
                    </div>

                </div>

            <?php endif; ?>


            <!-- ========================================================
                 FORM
            ========================================================= -->

            <form
                method="POST"
                autocomplete="off"
                class="add-category-form"
                id="addCouponForm"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($csrfToken) ?>"
                >


                <div class="add-category-form-body">

                    <div class="add-category-grid">


                        <!-- ==================================================
                             SECTION 1
                        =================================================== -->

                        <div class="add-category-section">

                            <div class="add-category-section-icon">
                                %
                            </div>

                            <div class="add-category-section-title">

                                Coupon & Discount Configuration

                            </div>

                        </div>


                        <!-- ==================================================
                             COUPON CODE
                        =================================================== -->

                        <div class="add-form-group">

                            <label
                                for="couponCode"
                                class="add-form-label"
                            >

                                <span>

                                    Coupon Code

                                    <span class="add-form-required">
                                        *
                                    </span>

                                </span>

                                <span class="shortcut-badge">
                                    Shortcut: C
                                </span>

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
                                    style="
                                        text-transform:uppercase;
                                        font-family:monospace;
                                        font-weight:700;
                                    "
                                    required
                                >


                                <button
                                    type="button"
                                    class="btn-action-input"
                                    id="btnGenCode"
                                    title="Generate coupon code"
                                >

                                    ⚡ Generate

                                </button>

                            </div>


                            <div class="form-help">

                                Letters, numbers, dashes and underscores only.
                                Code is automatically converted to uppercase.

                            </div>

                        </div>


                        <!-- ==================================================
                             DISCOUNT TYPE
                        =================================================== -->

                        <div class="add-form-group">

                            <label
                                for="discountType"
                                class="add-form-label"
                            >

                                <span>

                                    Discount Type

                                    <span class="add-form-required">
                                        *
                                    </span>

                                </span>

                            </label>


                            <select
                                id="discountType"
                                name="discount_type"
                                class="add-form-select"
                                required
                            >

                                <option
                                    value="Percentage"
                                    <?= $discountType === "Percentage"
                                        ? "selected"
                                        : "" ?>
                                >
                                    Percentage (%)
                                </option>

                                <option
                                    value="Fixed"
                                    <?= $discountType === "Fixed"
                                        ? "selected"
                                        : "" ?>
                                >
                                    Fixed Amount ($)
                                </option>

                            </select>

                        </div>


                        <!-- ==================================================
                             DISCOUNT VALUE
                        =================================================== -->

                        <div class="add-form-group">

                            <label
                                for="discountValue"
                                class="add-form-label"
                            >

                                <span>

                                    Discount Value

                                    <span class="add-form-required">
                                        *
                                    </span>

                                    <span id="valUnitText">
                                        (%)
                                    </span>

                                </span>

                                <span class="shortcut-badge">
                                    Shortcut: V
                                </span>

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


                        <!-- ==================================================
                             MAX DISCOUNT
                        =================================================== -->

                        <div
                            class="add-form-group"
                            id="maxDiscountGroup"
                        >

                            <label
                                for="maxDiscountAmount"
                                class="add-form-label"
                            >

                                <span>

                                    Maximum Discount Cap ($)

                                    <small
                                        style="
                                            color:var(--text-mute);
                                            text-transform:none;
                                        "
                                    >
                                        Optional
                                    </small>

                                </span>

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


                            <div class="form-help">

                                Maximum dollar amount that can be
                                discounted for a percentage coupon.

                            </div>

                        </div>


                        <!-- ==================================================
                             SECTION 2
                        =================================================== -->

                        <div class="add-category-section sec-amber">

                            <div class="add-category-section-icon">
                                📅
                            </div>

                            <div class="add-category-section-title">

                                Order Criteria & Validity

                            </div>

                        </div>


                        <!-- ==================================================
                             MIN ORDER
                        =================================================== -->

                        <div class="add-form-group">

                            <label
                                for="minOrderAmount"
                                class="add-form-label"
                            >

                                Minimum Order Amount ($)

                                <small
                                    style="
                                        color:var(--text-mute);
                                        text-transform:none;
                                    "
                                >
                                    Optional
                                </small>

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


                            <div class="form-help">

                                Minimum subtotal required before
                                this coupon can be applied.

                            </div>

                        </div>


                        <!-- ==================================================
                             USAGE LIMIT
                        =================================================== -->

                        <div class="add-form-group">

                            <label
                                for="usageLimit"
                                class="add-form-label"
                            >

                                Total Usage Limit

                                <small
                                    style="
                                        color:var(--text-mute);
                                        text-transform:none;
                                    "
                                >
                                    Optional
                                </small>

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


                            <div class="form-help">

                                Maximum number of redemptions
                                across all customers.

                            </div>

                        </div>


                        <!-- ==================================================
                             START DATE
                        =================================================== -->

                        <div class="add-form-group">

                            <label
                                for="startDate"
                                class="add-form-label"
                            >

                                Start Date

                                <span class="shortcut-badge">
                                    DD/MM/YYYY
                                </span>

                            </label>


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


                        <!-- ==================================================
                             END DATE
                        =================================================== -->

                        <div class="add-form-group">

                            <label
                                for="endDate"
                                class="add-form-label"
                            >

                                End Date

                                <span class="shortcut-badge">
                                    DD/MM/YYYY
                                </span>

                            </label>


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


                        <!-- ==================================================
                             SECTION 3
                        =================================================== -->

                        <div class="add-category-section sec-purple">

                            <div class="add-category-section-icon">
                                ✓
                            </div>

                            <div class="add-category-section-title">

                                Audience & Status

                            </div>

                        </div>


                        <!-- ==================================================
                             FLAGS
                        =================================================== -->

                        <div
                            class="add-form-group add-form-group-full"
                        >

                            <div class="flags-container">


                                <!-- ACTIVE -->

                                <div class="add-active-box">

                                    <label
                                        class="add-active-label"
                                    >

                                        <input
                                            type="checkbox"
                                            name="is_active"
                                            value="1"
                                            class="add-active-checkbox"
                                            <?= $isActive
                                                ? "checked"
                                                : "" ?>
                                        >

                                        <span>
                                            Active Coupon
                                        </span>

                                    </label>

                                </div>


                                <!-- WHOLESALE -->

                                <div class="add-active-box">

                                    <label
                                        class="add-active-label"
                                    >

                                        <input
                                            type="checkbox"
                                            name="is_for_wholesale_only"
                                            value="1"
                                            class="add-active-checkbox"
                                            <?= $isForWholesaleOnly
                                                ? "checked"
                                                : "" ?>
                                        >

                                        <span>
                                            Wholesale Accounts Only
                                        </span>

                                    </label>

                                </div>

                            </div>

                        </div>


                    </div>

                </div>


                <!-- ========================================================
                     FORM FOOTER
                ========================================================= -->

                <div class="add-category-form-footer">

                    <a
                        href="index.php"
                        class="add-category-btn add-category-cancel"
                        title="Shortcut: B"
                    >

                        Cancel

                        <small
                            style="
                                opacity:.7;
                                font-size:9px;
                            "
                        >
                            [B]
                        </small>

                    </a>


                    <button
                        type="submit"
                        class="add-category-btn add-category-save"
                        id="saveCouponBtn"
                        title="Shortcut: A"
                    >

                        <span>✓</span>

                        <span>

                            Save Coupon

                            <small
                                style="
                                    opacity:.8;
                                    font-size:9px;
                                "
                            >
                                [A]
                            </small>

                        </span>

                    </button>

                </div>

            </form>


            <!-- ========================================================
                 SHORTCUTS
            ========================================================= -->

            <div
                class="shortcut-help-box"
                id="shortcutHelpBox"
            >

                <div class="shortcut-help-title">

                    <span>
                        ⌨ Keyboard Shortcuts
                    </span>

                    <small>
                        H = Show / Hide
                        &nbsp;•&nbsp;
                        T = Theme
                    </small>

                </div>


                <div class="shortcut-grid">


                    <div class="shortcut-item">

                        <span class="shortcut-key">
                            A
                        </span>

                        <span class="shortcut-desc">
                            Save Coupon
                        </span>

                    </div>


                    <div class="shortcut-item">

                        <span class="shortcut-key">
                            B
                        </span>

                        <span class="shortcut-desc">
                            Back to Coupons
                        </span>

                    </div>


                    <div class="shortcut-item">

                        <span class="shortcut-key">
                            C
                        </span>

                        <span class="shortcut-desc">
                            Focus Coupon Code
                        </span>

                    </div>


                    <div class="shortcut-item">

                        <span class="shortcut-key">
                            V
                        </span>

                        <span class="shortcut-desc">
                            Focus Discount Value
                        </span>

                    </div>


                    <div class="shortcut-item">

                        <span class="shortcut-key">
                            G
                        </span>

                        <span class="shortcut-desc">
                            Generate Coupon Code
                        </span>

                    </div>


                    <div class="shortcut-item">

                        <span class="shortcut-key">
                            T
                        </span>

                        <span class="shortcut-desc">
                            Toggle Light / Dark Theme
                        </span>

                    </div>


                    <div class="shortcut-item">

                        <span class="shortcut-key">
                            H
                        </span>

                        <span class="shortcut-desc">
                            Show / Hide Shortcuts
                        </span>

                    </div>


                    <div class="shortcut-item">

                        <span class="shortcut-key">
                            Esc
                        </span>

                        <span class="shortcut-desc">
                            Blur Current Field
                        </span>

                    </div>


                </div>

            </div>


        </div>

    </section>

</main>


<!-- ================================================================
     FLATPICKR JS
================================================================ -->

<script
    src="https://cdn.jsdelivr.net/npm/flatpickr"
></script>


<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const root =
            document.documentElement;

        const body =
            document.body;

        const form =
            document.getElementById(
                "addCouponForm"
            );

        const codeInput =
            document.getElementById(
                "couponCode"
            );

        const typeSelect =
            document.getElementById(
                "discountType"
            );

        const valInput =
            document.getElementById(
                "discountValue"
            );

        const valUnitText =
            document.getElementById(
                "valUnitText"
            );

        const maxGroup =
            document.getElementById(
                "maxDiscountGroup"
            );

        const maxDiscountInput =
            document.getElementById(
                "maxDiscountAmount"
            );

        const btnGen =
            document.getElementById(
                "btnGenCode"
            );

        const saveBtn =
            document.getElementById(
                "saveCouponBtn"
            );

        const shortcutBox =
            document.getElementById(
                "shortcutHelpBox"
            );

        const startInput =
            document.getElementById(
                "startDate"
            );

        const endInput =
            document.getElementById(
                "endDate"
            );


        /* ==========================================================
           GLOBAL THEME
        ========================================================== */

        const THEME_KEY =
            "gatewaylinen-theme";


        function normalizeTheme(theme) {

            return theme === "light"
                ? "light"
                : "dark";
        }


        function getGlobalTheme() {

            try {

                const saved =
                    localStorage.getItem(
                        THEME_KEY
                    );

                if (
                    saved === "light" ||
                    saved === "dark"
                ) {
                    return saved;
                }

            } catch (e) {}

            const current =
                root.getAttribute(
                    "data-theme"
                );

            return normalizeTheme(
                current
            );
        }


        function applyGlobalTheme(
            theme,
            save = true
        ) {

            theme =
                normalizeTheme(theme);

            root.setAttribute(
                "data-theme",
                theme
            );

            if (body) {

                body.setAttribute(
                    "data-theme",
                    theme
                );

                body.classList.toggle(
                    "dark-mode",
                    theme === "dark"
                );

                body.classList.toggle(
                    "light-mode",
                    theme === "light"
                );
            }


            if (save) {

                try {

                    localStorage.setItem(
                        THEME_KEY,
                        theme
                    );

                } catch (e) {}
            }


            document.dispatchEvent(
                new CustomEvent(
                    "gatewayThemeChanged",
                    {
                        detail: {
                            theme: theme
                        }
                    }
                )
            );
        }


        applyGlobalTheme(
            getGlobalTheme(),
            false
        );


        window.addEventListener(
            "storage",
            function (event) {

                if (
                    event.key === THEME_KEY &&
                    (
                        event.newValue === "dark" ||
                        event.newValue === "light"
                    )
                ) {

                    applyGlobalTheme(
                        event.newValue,
                        false
                    );
                }

            }
        );


        function toggleGlobalTheme() {

            if (
                typeof window.toggleGatewayTheme ===
                "function"
            ) {

                window.toggleGatewayTheme();

                return;
            }


            const current =
                getGlobalTheme();

            const next =
                current === "dark"
                    ? "light"
                    : "dark";

            applyGlobalTheme(
                next,
                true
            );
        }


        /* ==========================================================
           FLATPICKR
        ========================================================== */

        let startPicker = null;
        let endPicker = null;


        if (
            window.flatpickr &&
            startInput &&
            endInput
        ) {

            startPicker =
                flatpickr(
                    startInput,
                    {
                        dateFormat: "d/m/Y",
                        allowInput: true,
                        disableMobile: true
                    }
                );


            endPicker =
                flatpickr(
                    endInput,
                    {
                        dateFormat: "d/m/Y",
                        allowInput: true,
                        disableMobile: true
                    }
                );
        }


        /* ==========================================================
           AUTO DATE SLASH
        ========================================================== */

        function setupDateAutoSlash(input) {

            if (!input) {
                return;
            }


            input.addEventListener(
                "input",
                function () {

                    let value =
                        this.value
                            .replace(/\D/g, "")
                            .slice(0, 8);


                    if (
                        value.length >= 5
                    ) {

                        this.value =
                            value.slice(0, 2) +
                            "/" +
                            value.slice(2, 4) +
                            "/" +
                            value.slice(4);

                    } else if (
                        value.length >= 3
                    ) {

                        this.value =
                            value.slice(0, 2) +
                            "/" +
                            value.slice(2);

                    } else {

                        this.value =
                            value;
                    }

                }
            );
        }


        setupDateAutoSlash(
            startInput
        );

        setupDateAutoSlash(
            endInput
        );


        /* ==========================================================
           DISCOUNT TYPE
        ========================================================== */

        function syncDiscountType() {

            if (
                !typeSelect ||
                !valInput
            ) {
                return;
            }


            if (
                typeSelect.value ===
                "Percentage"
            ) {

                if (valUnitText) {

                    valUnitText.textContent =
                        "(%)";
                }

                valInput.max =
                    "100";

                valInput.placeholder =
                    "e.g. 15.00";

                if (maxGroup) {

                    maxGroup.style.display =
                        "";
                }

            } else {

                if (valUnitText) {

                    valUnitText.textContent =
                        "($)";
                }

                valInput.removeAttribute(
                    "max"
                );

                valInput.placeholder =
                    "e.g. 25.00";

                if (maxGroup) {

                    maxGroup.style.display =
                        "none";
                }

                if (maxDiscountInput) {

                    maxDiscountInput.value =
                        "";
                }
            }
        }


        if (typeSelect) {

            typeSelect.addEventListener(
                "change",
                syncDiscountType
            );

            syncDiscountType();
        }


        /* ==========================================================
           COUPON CODE
        ========================================================== */

        if (codeInput) {

            codeInput.addEventListener(
                "input",
                function () {

                    this.value =
                        this.value
                            .toUpperCase()
                            .replace(
                                /[^A-Z0-9_-]/g,
                                ""
                            )
                            .slice(0, 50);
                }
            );
        }


        /* ==========================================================
           GENERATE COUPON
        ========================================================== */

        function generateCouponCode() {

            const prefixes = [
                "SALE",
                "SAVE",
                "LINEN",
                "HOTEL",
                "DEAL",
                "GIFT",
                "VIP",
                "WELCOME",
                "OFFER"
            ];


            const prefix =
                prefixes[
                    Math.floor(
                        Math.random() *
                        prefixes.length
                    )
                ];


            const number =
                Math.floor(
                    10 +
                    Math.random() * 90
                );


            const chars =
                "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";


            let suffix = "";


            for (
                let i = 0;
                i < 3;
                i++
            ) {

                suffix +=
                    chars.charAt(
                        Math.floor(
                            Math.random() *
                            chars.length
                        )
                    );
            }


            return (
                prefix +
                number +
                suffix
            );
        }


        if (btnGen) {

            btnGen.addEventListener(
                "click",
                function (event) {

                    event.preventDefault();

                    if (codeInput) {

                        codeInput.value =
                            generateCouponCode();

                        codeInput.focus();

                        codeInput.select();
                    }
                }
            );
        }


        /* ==========================================================
           DOUBLE SUBMIT PROTECTION
        ========================================================== */

        let isSubmitting = false;


        if (
            form &&
            saveBtn
        ) {

            form.addEventListener(
                "submit",
                function (event) {

                    if (isSubmitting) {

                        event.preventDefault();

                        return;
                    }


                    isSubmitting = true;

                    saveBtn.disabled =
                        true;

                    saveBtn.innerHTML =
                        "<span>✓</span>" +
                        "<span>Saving Coupon...</span>";
                }
            );
        }


        /* ==========================================================
           KEYBOARD SHORTCUTS
        ========================================================== */

        let helpVisible = true;


        document.addEventListener(
            "keydown",
            function (event) {

                const key =
                    event.key.toLowerCase();

                const active =
                    document.activeElement;

                const isTyping =
                    active &&
                    [
                        "INPUT",
                        "TEXTAREA",
                        "SELECT"
                    ].includes(
                        active.tagName
                    );

                const hasModifier =
                    event.ctrlKey ||
                    event.altKey ||
                    event.metaKey;


                /* Escape */

                if (
                    event.key ===
                    "Escape"
                ) {

                    if (
                        active &&
                        typeof active.blur ===
                        "function"
                    ) {

                        active.blur();
                    }

                    return;
                }


                if (
                    isTyping ||
                    hasModifier
                ) {
                    return;
                }


                /* H */

                if (key === "h") {

                    event.preventDefault();

                    helpVisible =
                        !helpVisible;

                    if (shortcutBox) {

                        shortcutBox.classList.toggle(
                            "hidden",
                            !helpVisible
                        );
                    }

                    return;
                }


                /* T */

                if (key === "t") {

                    event.preventDefault();

                    toggleGlobalTheme();

                    return;
                }


                /* A */

                if (key === "a") {

                    event.preventDefault();

                    if (form) {

                        if (
                            typeof form.requestSubmit ===
                            "function"
                        ) {

                            form.requestSubmit();

                        } else {

                            form.submit();
                        }
                    }

                    return;
                }


                /* B */

                if (key === "b") {

                    event.preventDefault();

                    window.location.href =
                        "index.php";

                    return;
                }


                /* C */

                if (
                    key === "c" &&
                    codeInput
                ) {

                    event.preventDefault();

                    codeInput.focus();

                    codeInput.select();

                    return;
                }


                /* V */

                if (
                    key === "v" &&
                    valInput
                ) {

                    event.preventDefault();

                    valInput.focus();

                    valInput.select();

                    return;
                }


                /* G */

                if (
                    key === "g" &&
                    btnGen
                ) {

                    event.preventDefault();

                    btnGen.click();

                    return;
                }

            }
        );


        /* ==========================================================
           DESKTOP AUTO FOCUS
        ========================================================== */

        if (
            codeInput &&
            window.innerWidth > 768 &&
            document.activeElement &&
            ![
                "INPUT",
                "TEXTAREA",
                "SELECT"
            ].includes(
                document.activeElement.tagName
            )
        ) {

            setTimeout(
                function () {

                    codeInput.focus();

                },
                150
            );
        }

    }
);

</script>


<?php

require_once __DIR__ . "/../includes/footer.php";

?>