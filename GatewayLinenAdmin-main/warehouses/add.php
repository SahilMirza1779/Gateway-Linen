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

$activeMenu = 'warehouses';
$pageTitle  = 'GatewayLinen | Add Warehouse';

/*
|--------------------------------------------------------------------------
| ADMIN INFO
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] = $_SESSION['admin_username'] ?? 'GatewayLinen Administrator';
}

if (!isset($_SESSION['admin_role'])) {
    $_SESSION['admin_role'] = 'Administrator';
}

/*
|--------------------------------------------------------------------------
| ESCAPE FUNCTION
|--------------------------------------------------------------------------
*/

if (!function_exists('gatewayWarehouseEscape')) {
    function gatewayWarehouseEscape($value): string
    {
        return htmlspecialchars(
            (string)$value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

/*
|--------------------------------------------------------------------------
| SQL ERROR LOGGER
|--------------------------------------------------------------------------
*/

if (!function_exists('gatewayWarehouseLogSqlError')) {
    function gatewayWarehouseLogSqlError(string $message): void
    {
        $errors = sqlsrv_errors(SQLSRV_ERR_ALL);

        if ($errors) {
            error_log(
                $message . ' | ' . print_r($errors, true)
            );
        } else {
            error_log($message);
        }
    }
}

/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['warehouse_csrf_token'])) {
    $_SESSION['warehouse_csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['warehouse_csrf_token'];

/*
|--------------------------------------------------------------------------
| FORM VALUES
|--------------------------------------------------------------------------
*/

$warehouseCode = '';
$warehouseName = '';
$address       = '';
$city          = '';
$stateProvince = '';
$postalCode    = '';

$isPrimary = 0;
$isActive  = 1;

$error = '';

/*
|--------------------------------------------------------------------------
| FORM SUBMISSION
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | CSRF CHECK
    |--------------------------------------------------------------------------
    */

    $postedToken = (string)($_POST['csrf_token'] ?? '');

    if (
        empty($_SESSION['warehouse_csrf_token']) ||
        !hash_equals(
            (string)$_SESSION['warehouse_csrf_token'],
            $postedToken
        )
    ) {
        $error = 'Security verification failed. Please refresh the page and try again.';
    }

    /*
    |--------------------------------------------------------------------------
    | READ FORM DATA
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $warehouseCode = strtoupper(
            trim((string)($_POST['warehouse_code'] ?? ''))
        );

        $warehouseName = trim(
            (string)($_POST['warehouse_name'] ?? '')
        );

        $address = trim(
            (string)($_POST['address'] ?? '')
        );

        $city = trim(
            (string)($_POST['city'] ?? '')
        );

        $stateProvince = trim(
            (string)($_POST['state_province'] ?? '')
        );

        $postalCode = trim(
            (string)($_POST['postal_code'] ?? '')
        );

        $isPrimary = isset($_POST['is_primary']) ? 1 : 0;
        $isActive  = isset($_POST['is_active']) ? 1 : 0;
    }

    /*
    |--------------------------------------------------------------------------
    | REQUIRED VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($error === '' && $warehouseName === '') {
        $error = 'Warehouse name is required.';
    }

    if ($error === '' && $warehouseCode === '') {
        $error = 'Warehouse code is required.';
    }

    /*
    |--------------------------------------------------------------------------
    | LENGTH VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($error === '' && mb_strlen($warehouseName) > 100) {
        $error = 'Warehouse name cannot exceed 100 characters.';
    }

    if ($error === '' && mb_strlen($warehouseCode) > 50) {
        $error = 'Warehouse code cannot exceed 50 characters.';
    }

    if ($error === '' && mb_strlen($address) > 255) {
        $error = 'Address cannot exceed 255 characters.';
    }

    if ($error === '' && mb_strlen($city) > 100) {
        $error = 'City cannot exceed 100 characters.';
    }

    if ($error === '' && mb_strlen($stateProvince) > 100) {
        $error = 'State / Province cannot exceed 100 characters.';
    }

    if ($error === '' && mb_strlen($postalCode) > 20) {
        $error = 'Postal / ZIP code cannot exceed 20 characters.';
    }

    /*
    |--------------------------------------------------------------------------
    | WAREHOUSE CODE FORMAT
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $warehouseCode = preg_replace(
            '/\s+/',
            '-',
            $warehouseCode
        );

        $warehouseCode = preg_replace(
            '/[^A-Z0-9._-]/',
            '',
            $warehouseCode
        );

        if ($warehouseCode === '') {
            $error = 'Please enter a valid warehouse code.';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK DUPLICATE WAREHOUSE CODE
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $duplicateSql = "
            SELECT TOP 1 WarehouseId
            FROM dbo.Warehouses
            WHERE UPPER(LTRIM(RTRIM(WarehouseCode))) = ?
        ";

        $duplicateStmt = sqlsrv_query(
            $conn,
            $duplicateSql,
            [$warehouseCode]
        );

        if ($duplicateStmt === false) {

            gatewayWarehouseLogSqlError(
                'Warehouse duplicate-code check failed.'
            );

            $error = 'Unable to validate warehouse code right now. Please try again.';

        } else {

            $duplicateRow = sqlsrv_fetch_array(
                $duplicateStmt,
                SQLSRV_FETCH_ASSOC
            );

            sqlsrv_free_stmt($duplicateStmt);

            if ($duplicateRow) {
                $error = 'This warehouse code already exists. Please use a different code.';
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DATABASE TRANSACTION
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        if (!sqlsrv_begin_transaction($conn)) {

            gatewayWarehouseLogSqlError(
                'Unable to begin warehouse transaction.'
            );

            $error = 'Unable to start the warehouse transaction.';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | MAKE THIS WAREHOUSE PRIMARY
    |--------------------------------------------------------------------------
    */

    if ($error === '' && $isPrimary === 1) {

        $resetPrimarySql = "
            UPDATE dbo.Warehouses
            SET IsPrimary = 0
            WHERE IsPrimary = 1
        ";

        $resetStmt = sqlsrv_query(
            $conn,
            $resetPrimarySql
        );

        if ($resetStmt === false) {

            gatewayWarehouseLogSqlError(
                'Failed to reset existing primary warehouse.'
            );

            sqlsrv_rollback($conn);

            $error = 'Unable to update the primary warehouse setting.';

        } else {

            sqlsrv_free_stmt($resetStmt);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | INSERT WAREHOUSE
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $insertSql = "
            INSERT INTO dbo.Warehouses
            (
                WarehouseCode,
                WarehouseName,
                Address,
                City,
                StateProvince,
                PostalCode,
                IsPrimary,
                IsActive
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
                ?
            )
        ";

        $params = [
            $warehouseCode,
            $warehouseName,
            $address !== '' ? $address : null,
            $city !== '' ? $city : null,
            $stateProvince !== '' ? $stateProvince : null,
            $postalCode !== '' ? $postalCode : null,
            $isPrimary,
            $isActive
        ];

        $insertStmt = sqlsrv_query(
            $conn,
            $insertSql,
            $params
        );

        if ($insertStmt === false) {

            gatewayWarehouseLogSqlError(
                'Warehouse INSERT failed.'
            );

            sqlsrv_rollback($conn);

            $error = 'Unable to create warehouse. Please check the information and try again.';

        } else {

            sqlsrv_free_stmt($insertStmt);

            /*
            |--------------------------------------------------------------------------
            | COMMIT DATABASE
            |--------------------------------------------------------------------------
            */

            if (!sqlsrv_commit($conn)) {

                gatewayWarehouseLogSqlError(
                    'Warehouse transaction commit failed.'
                );

                sqlsrv_rollback($conn);

                $error = 'Warehouse could not be saved. Please try again.';

            } else {

                /*
                |--------------------------------------------------------------------------
                | NEW CSRF TOKEN
                |--------------------------------------------------------------------------
                */

                $_SESSION['warehouse_csrf_token'] = bin2hex(
                    random_bytes(32)
                );

                /*
                |--------------------------------------------------------------------------
                | SUCCESS REDIRECT
                |--------------------------------------------------------------------------
                */

                header(
                    'Location: index.php?success=' .
                    rawurlencode('Warehouse created successfully.')
                );

                exit;
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| HEADER + SIDEBAR
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<style>

/* ================================================================
   THEME VARIABLES
   ================================================================ */

:root {
    --gw-page: #0a1119;
    --gw-card: #111b26;
    --gw-card-alt: #0f1823;
    --gw-input: #0d1620;
    --gw-hover: #16222e;

    --gw-border: #1e2d3d;
    --gw-border-soft: #182636;

    --gw-text: #f0f4f8;
    --gw-text-body: #a8b8c8;
    --gw-text-muted: #71859a;

    --gw-green: #10b981;
    --gw-green-dark: #059669;
    --gw-green-soft: rgba(16,185,129,.12);

    --gw-blue: #38bdf8;
    --gw-blue-soft: rgba(56,189,248,.12);

    --gw-amber: #f59e0b;
    --gw-amber-soft: rgba(245,158,11,.12);

    --gw-red: #ef4444;
    --gw-red-soft: rgba(239,68,68,.12);

    --gw-shadow: 0 15px 40px rgba(0,0,0,.20);
}

/* LIGHT */

html[data-theme="light"],
body.light-mode,
body[data-theme="light"] {
    --gw-page: #f5f7fa;
    --gw-card: #ffffff;
    --gw-card-alt: #f8fafc;
    --gw-input: #ffffff;
    --gw-hover: #f1f5f9;

    --gw-border: #d9e1ea;
    --gw-border-soft: #e7edf3;

    --gw-text: #172033;
    --gw-text-body: #475569;
    --gw-text-muted: #64748b;

    --gw-green: #059669;
    --gw-green-dark: #047857;
    --gw-green-soft: rgba(5,150,105,.09);

    --gw-blue: #0284c7;
    --gw-blue-soft: rgba(2,132,199,.09);

    --gw-amber: #d97706;
    --gw-amber-soft: rgba(217,119,6,.09);

    --gw-red: #dc2626;
    --gw-red-soft: rgba(220,38,38,.08);

    --gw-shadow: 0 15px 40px rgba(15,23,42,.08);
}

/* DARK */

html[data-theme="dark"],
body.dark-mode,
body[data-theme="dark"] {
    --gw-page: #0a1119;
    --gw-card: #111b26;
    --gw-card-alt: #0f1823;
    --gw-input: #0d1620;
    --gw-hover: #16222e;

    --gw-border: #1e2d3d;
    --gw-border-soft: #182636;

    --gw-text: #f0f4f8;
    --gw-text-body: #a8b8c8;
    --gw-text-muted: #71859a;

    --gw-green: #10b981;
    --gw-green-dark: #059669;
    --gw-green-soft: rgba(16,185,129,.12);

    --gw-blue: #38bdf8;
    --gw-blue-soft: rgba(56,189,248,.12);

    --gw-amber: #f59e0b;
    --gw-amber-soft: rgba(245,158,11,.12);

    --gw-red: #ef4444;
    --gw-red-soft: rgba(239,68,68,.12);

    --gw-shadow: 0 15px 40px rgba(0,0,0,.20);
}

/* ================================================================
   PAGE
   ================================================================ */

html,
body,
.main,
.content {
    background: var(--gw-page) !important;
    color: var(--gw-text-body) !important;
}

.add-warehouse-page,
.add-warehouse-page * {
    box-sizing: border-box;
}

.add-warehouse-page {
    width: 100%;
    max-width: 1180px;
    margin: 0 auto;
    padding: 20px 20px 45px;
}

/* ================================================================
   HEADER
   ================================================================ */

.page-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;

    margin-bottom: 24px;
    padding-bottom: 20px;

    border-bottom: 1px solid var(--gw-border);
}

.breadcrumb {
    display: flex;
    gap: 7px;
    align-items: center;

    margin-bottom: 8px;

    color: var(--gw-text-muted);
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .45px;
}

.breadcrumb .current {
    color: var(--gw-green);
}

.page-title {
    margin: 0;

    color: var(--gw-text);

    font-size: 27px;
    font-weight: 800;
}

.page-subtitle {
    margin: 7px 0 0;

    color: var(--gw-text-muted);

    font-size: 12px;
}

/* ================================================================
   BUTTONS
   ================================================================ */

.btn-back {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;

    min-height: 40px;
    padding: 0 16px;

    border: 1px solid var(--gw-border);
    border-radius: 9px;

    background: var(--gw-input);
    color: var(--gw-text-body) !important;

    font-size: 11px;
    font-weight: 800;

    text-decoration: none;

    transition: .18s ease;
}

.btn-back:hover {
    border-color: var(--gw-green);
    background: var(--gw-green-soft);
    color: var(--gw-green) !important;
}

/* ================================================================
   ERROR
   ================================================================ */

.alert-error {
    display: flex;
    gap: 11px;
    align-items: flex-start;

    margin-bottom: 20px;
    padding: 14px 16px;

    border: 1px solid rgba(239,68,68,.30);
    border-left: 4px solid var(--gw-red);
    border-radius: 9px;

    background: var(--gw-red-soft);
    color: var(--gw-red);

    font-size: 12px;
    font-weight: 700;
}

/* ================================================================
   FORM
   ================================================================ */

.form-card {
    width: 100%;

    overflow: hidden;

    border: 1px solid var(--gw-border);
    border-radius: 12px;

    background: var(--gw-card);
    box-shadow: var(--gw-shadow);
}

.form-body {
    padding: 24px 22px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2,minmax(0,1fr));
    gap: 22px 26px;
}

/* ================================================================
   SECTION
   ================================================================ */

.form-section {
    grid-column: 1 / -1;

    display: flex;
    align-items: center;
    gap: 10px;

    margin-top: 6px;
    padding: 11px 14px;

    border: 1px solid var(--gw-border-soft);
    border-left: 3px solid var(--gw-green);
    border-radius: 9px;

    background: var(--gw-green-soft);
}

.form-section:first-child {
    margin-top: 0;
}

.form-section.blue {
    border-left-color: var(--gw-blue);
    background: var(--gw-blue-soft);
}

.form-section.amber {
    border-left-color: var(--gw-amber);
    background: var(--gw-amber-soft);
}

.form-section-icon {
    display: flex;
    align-items: center;
    justify-content: center;

    width: 28px;
    height: 28px;

    border: 1px solid var(--gw-border);
    border-radius: 7px;

    background: var(--gw-card);
    color: var(--gw-green);
}

.form-section.blue .form-section-icon {
    color: var(--gw-blue);
}

.form-section.amber .form-section-icon {
    color: var(--gw-amber);
}

.form-section-title {
    color: var(--gw-text);

    font-size: 10.5px;
    font-weight: 900;
    letter-spacing: .8px;
    text-transform: uppercase;
}

/* ================================================================
   FIELDS
   ================================================================ */

.form-group {
    min-width: 0;
}

.form-group-full {
    grid-column: 1 / -1;
}

.form-label {
    display: block;

    margin-bottom: 8px;

    color: var(--gw-text-body);

    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
}

.required {
    color: var(--gw-red);
}

.form-input,
.form-textarea {
    width: 100%;

    border: 1px solid var(--gw-border);
    border-radius: 9px;

    outline: none;

    background: var(--gw-input);
    color: var(--gw-text);

    font-family: inherit;
    font-size: 12px;

    transition: .18s ease;
}

.form-input {
    height: 44px;
    padding: 0 13px;
}

.form-textarea {
    min-height: 95px;
    padding: 12px 13px;
    resize: vertical;
}

.form-input::placeholder,
.form-textarea::placeholder {
    color: var(--gw-text-muted);
}

.form-input:focus,
.form-textarea:focus {
    border-color: var(--gw-green);

    box-shadow:
        0 0 0 3px var(--gw-green-soft);
}

.field-help {
    margin-top: 6px;

    color: var(--gw-text-muted);

    font-size: 10px;
}

/* ================================================================
   CHECKBOX
   ================================================================ */

.checkbox-box {
    display: flex;
    align-items: center;

    min-height: 44px;
    padding: 0 14px;

    border: 1px solid var(--gw-border);
    border-radius: 9px;

    background: var(--gw-input);
}

.checkbox-box:hover {
    border-color: var(--gw-green);
    background: var(--gw-hover);
}

.checkbox-label {
    display: inline-flex;
    align-items: center;
    gap: 10px;

    color: var(--gw-text-body);

    font-size: 11.5px;
    font-weight: 700;

    cursor: pointer;
}

.checkbox-input {
    width: 18px;
    height: 18px;

    margin: 0;

    accent-color: var(--gw-green);
}

/* ================================================================
   FOOTER
   ================================================================ */

.form-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;

    padding: 18px 22px;

    border-top: 1px solid var(--gw-border);

    background: var(--gw-card-alt);
}

.btn-submit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;

    min-height: 42px;
    min-width: 160px;

    padding: 0 22px;

    border: 1px solid var(--gw-green-dark);
    border-radius: 9px;

    background: var(--gw-green);

    color: #fff !important;

    font-family: inherit;
    font-size: 11.5px;
    font-weight: 800;

    cursor: pointer;

    transition: .18s ease;
}

.btn-submit:hover {
    background: var(--gw-green-dark);
}

.btn-submit:disabled {
    opacity: .65;
    cursor: not-allowed;
}

/* ================================================================
   SHORTCUTS
   ================================================================ */

.shortcut-help-box {
    margin-top: 22px;
    padding: 18px 22px;

    border: 1px solid var(--gw-border);
    border-radius: 12px;

    background: var(--gw-card);
    box-shadow: var(--gw-shadow);
}

.shortcut-help-box.hidden {
    display: none;
}

.shortcut-help-title {
    display: flex;
    align-items: center;
    gap: 10px;

    margin-bottom: 14px;

    color: var(--gw-text);

    font-size: 13px;
    font-weight: 800;
}

.shortcut-help-title small {
    margin-left: auto;

    color: var(--gw-text-muted);
}

.shortcut-grid {
    display: grid;
    grid-template-columns: repeat(6,1fr);
    gap: 10px;
}

.shortcut-item {
    display: flex;
    align-items: center;
    gap: 10px;

    padding: 8px 10px;

    border: 1px solid var(--gw-border-soft);
    border-radius: 8px;

    background: var(--gw-input);
}

.shortcut-key {
    display: flex;
    align-items: center;
    justify-content: center;

    min-width: 32px;
    height: 28px;

    border: 1px solid var(--gw-border);
    border-radius: 6px;

    background: var(--gw-hover);
    color: var(--gw-green);

    font-size: 10px;
    font-weight: 900;
}

.shortcut-desc {
    color: var(--gw-text-body);
    font-size: 10px;
    font-weight: 700;
}

/* ================================================================
   RESPONSIVE
   ================================================================ */

@media (max-width: 1000px) {

    .shortcut-grid {
        grid-template-columns: repeat(3,1fr);
    }
}

@media (max-width: 800px) {

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-group-full {
        grid-column: auto;
    }

    .page-header {
        align-items: flex-start;
        flex-direction: column;
    }
}

@media (max-width: 600px) {

    .add-warehouse-page {
        padding: 15px 10px 30px;
    }

    .page-title {
        font-size: 23px;
    }

    .btn-back {
        width: 100%;
    }

    .form-body {
        padding: 18px 14px;
    }

    .form-footer {
        flex-direction: column-reverse;
    }

    .form-footer .btn-back,
    .form-footer .btn-submit {
        width: 100%;
    }

    .shortcut-grid {
        grid-template-columns: 1fr;
    }
}

</style>

<main class="main">

<section class="content">

<div class="add-warehouse-page">

    <!-- PAGE HEADER -->

    <div class="page-header">

        <div>

            <div class="breadcrumb">
                <span>Dashboard</span>
                <span>›</span>
                <span>Warehouses</span>
                <span>›</span>
                <span class="current">Add Warehouse</span>
            </div>

            <h1 class="page-title">
                Add New Warehouse
            </h1>

            <p class="page-subtitle">
                Configure warehouse details, location, primary designation and operating status.
            </p>

        </div>

        <a href="index.php" class="btn-back">
            ← Back to Warehouses
        </a>

    </div>


    <!-- ERROR -->

    <?php if ($error !== ''): ?>

        <div class="alert-error">

            <strong>!</strong>

            <div>
                <?= gatewayWarehouseEscape($error) ?>
            </div>

        </div>

    <?php endif; ?>


    <!-- FORM -->

    <form
        method="POST"
        action=""
        autocomplete="off"
        class="form-card"
        id="addWarehouseForm"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= gatewayWarehouseEscape($csrfToken) ?>"
        >

        <div class="form-body">

            <div class="form-grid">

                <!-- DETAILS -->

                <div class="form-section">

                    <div class="form-section-icon">
                        #
                    </div>

                    <div class="form-section-title">
                        Warehouse Details
                    </div>

                </div>


                <!-- NAME -->

                <div class="form-group">

                    <label
                        for="warehouseName"
                        class="form-label"
                    >
                        Warehouse Name
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        id="warehouseName"
                        name="warehouse_name"
                        class="form-input"
                        value="<?= gatewayWarehouseEscape($warehouseName) ?>"
                        placeholder="e.g. Main Fulfillment Center"
                        maxlength="100"
                        required
                    >

                    <div class="field-help">
                        Official warehouse name.
                    </div>

                </div>


                <!-- CODE -->

                <div class="form-group">

                    <label
                        for="warehouseCode"
                        class="form-label"
                    >
                        Warehouse Code
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        id="warehouseCode"
                        name="warehouse_code"
                        class="form-input"
                        value="<?= gatewayWarehouseEscape($warehouseCode) ?>"
                        placeholder="e.g. WH-MAIN-01"
                        maxlength="50"
                        required
                    >

                    <div class="field-help">
                        Unique warehouse code.
                    </div>

                </div>


                <!-- LOCATION -->

                <div class="form-section blue">

                    <div class="form-section-icon">
                        📍
                    </div>

                    <div class="form-section-title">
                        Location & Address
                    </div>

                </div>


                <!-- ADDRESS -->

                <div class="form-group form-group-full">

                    <label
                        for="address"
                        class="form-label"
                    >
                        Street Address
                    </label>

                    <textarea
                        id="address"
                        name="address"
                        class="form-textarea"
                        maxlength="255"
                        placeholder="Enter complete street address..."
                    ><?= gatewayWarehouseEscape($address) ?></textarea>

                </div>


                <!-- CITY -->

                <div class="form-group">

                    <label
                        for="city"
                        class="form-label"
                    >
                        City
                    </label>

                    <input
                        type="text"
                        id="city"
                        name="city"
                        class="form-input"
                        value="<?= gatewayWarehouseEscape($city) ?>"
                        placeholder="e.g. Ahmedabad"
                        maxlength="100"
                    >

                </div>


                <!-- STATE -->

                <div class="form-group">

                    <label
                        for="stateProvince"
                        class="form-label"
                    >
                        State / Province
                    </label>

                    <input
                        type="text"
                        id="stateProvince"
                        name="state_province"
                        class="form-input"
                        value="<?= gatewayWarehouseEscape($stateProvince) ?>"
                        placeholder="e.g. Gujarat"
                        maxlength="100"
                    >

                </div>


                <!-- POSTAL -->

                <div class="form-group">

                    <label
                        for="postalCode"
                        class="form-label"
                    >
                        Postal / ZIP Code
                    </label>

                    <input
                        type="text"
                        id="postalCode"
                        name="postal_code"
                        class="form-input"
                        value="<?= gatewayWarehouseEscape($postalCode) ?>"
                        placeholder="e.g. 394440"
                        maxlength="20"
                    >

                </div>


                <!-- SETTINGS -->

                <div class="form-section amber">

                    <div class="form-section-icon">
                        ⚙
                    </div>

                    <div class="form-section-title">
                        Status & Settings
                    </div>

                </div>


                <!-- ACTIVE -->

                <div class="form-group">

                    <label class="form-label">
                        Operational Status
                    </label>

                    <div class="checkbox-box">

                        <label class="checkbox-label">

                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                class="checkbox-input"
                                <?= $isActive ? 'checked' : '' ?>
                            >

                            <span>
                                Active Warehouse
                            </span>

                        </label>

                    </div>

                    <div class="field-help">
                        Active warehouse can be used for inventory operations.
                    </div>

                </div>


                <!-- PRIMARY -->

                <div class="form-group">

                    <label class="form-label">
                        Primary Designation
                    </label>

                    <div class="checkbox-box">

                        <label class="checkbox-label">

                            <input
                                type="checkbox"
                                name="is_primary"
                                value="1"
                                class="checkbox-input"
                                <?= $isPrimary ? 'checked' : '' ?>
                            >

                            <span>
                                Set as Primary Fulfillment Hub
                            </span>

                        </label>

                    </div>

                    <div class="field-help">
                        This will remove primary status from other warehouses.
                    </div>

                </div>

            </div>

        </div>


        <!-- FORM FOOTER -->

        <div class="form-footer">

            <a
                href="index.php"
                class="btn-back"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="btn-submit"
                id="saveWarehouseBtn"
            >
                ✓
                <span id="saveWarehouseText">
                    Save Warehouse
                </span>
            </button>

        </div>

    </form>


    <!-- SHORTCUTS -->

    <div
        class="shortcut-help-box"
        id="shortcutHelpBox"
    >

        <div class="shortcut-help-title">

            <span>⌨</span>

            <span>
                Keyboard Shortcuts
            </span>

            <small>
                H = Show / Hide
            </small>

        </div>

        <div class="shortcut-grid">

            <div class="shortcut-item">
                <span class="shortcut-key">A</span>
                <span class="shortcut-desc">Save</span>
            </div>

            <div class="shortcut-item">
                <span class="shortcut-key">B</span>
                <span class="shortcut-desc">Back</span>
            </div>

            <div class="shortcut-item">
                <span class="shortcut-key">N</span>
                <span class="shortcut-desc">Name</span>
            </div>

            <div class="shortcut-item">
                <span class="shortcut-key">C</span>
                <span class="shortcut-desc">Code</span>
            </div>

            <div class="shortcut-item">
                <span class="shortcut-key">H</span>
                <span class="shortcut-desc">Shortcuts</span>
            </div>

            <div class="shortcut-item">
                <span class="shortcut-key">Esc</span>
                <span class="shortcut-desc">Blur</span>
            </div>

        </div>

    </div>

</div>

</section>

</main>


<script>

(function () {

    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        const form = document.getElementById('addWarehouseForm');
        const nameInput = document.getElementById('warehouseName');
        const codeInput = document.getElementById('warehouseCode');
        const shortcutBox = document.getElementById('shortcutHelpBox');
        const saveButton = document.getElementById('saveWarehouseBtn');
        const saveText = document.getElementById('saveWarehouseText');


        /*
        |--------------------------------------------------------------------------
        | FOCUS NAME
        |--------------------------------------------------------------------------
        */

        if (nameInput && window.innerWidth > 700) {

            setTimeout(function () {

                try {
                    nameInput.focus();
                } catch (e) {}

            }, 200);

        }


        /*
        |--------------------------------------------------------------------------
        | CODE FORMAT
        |--------------------------------------------------------------------------
        */

        if (codeInput) {

            codeInput.addEventListener('input', function () {

                this.value = this.value
                    .toUpperCase()
                    .replace(/\s+/g, '-')
                    .replace(/[^A-Z0-9._-]/g, '');

            });

        }


        /*
        |--------------------------------------------------------------------------
        | SUBMIT
        |--------------------------------------------------------------------------
        */

        if (form) {

            form.addEventListener('submit', function (event) {

                if (!form.checkValidity()) {

                    event.preventDefault();

                    form.reportValidity();

                    return;
                }

                if (saveButton) {
                    saveButton.disabled = true;
                }

                if (saveText) {
                    saveText.textContent = 'Saving Warehouse...';
                }

            });

        }


        /*
        |--------------------------------------------------------------------------
        | KEYBOARD SHORTCUTS
        |--------------------------------------------------------------------------
        */

        document.addEventListener('keydown', function (event) {

            const active = document.activeElement;

            const tag =
                active?.tagName?.toLowerCase() || '';

            const typing =
                tag === 'input' ||
                tag === 'textarea' ||
                tag === 'select' ||
                active?.isContentEditable;


            /*
            | ESC
            */

            if (event.key === 'Escape') {

                if (
                    active &&
                    typeof active.blur === 'function'
                ) {
                    active.blur();
                }

                return;
            }


            /*
            | H
            */

            if (
                event.key.toLowerCase() === 'h' &&
                !event.ctrlKey &&
                !event.metaKey &&
                !event.altKey &&
                !typing
            ) {

                event.preventDefault();

                if (shortcutBox) {
                    shortcutBox.classList.toggle('hidden');
                }

                return;
            }


            /*
            | CTRL + S
            */

            if (
                event.key.toLowerCase() === 's' &&
                (event.ctrlKey || event.metaKey)
            ) {

                event.preventDefault();

                if (form) {
                    form.requestSubmit();
                }

                return;
            }


            /*
            | IGNORE WHILE TYPING
            */

            if (typing) {
                return;
            }


            const key = event.key.toLowerCase();


            /*
            | A = SAVE
            */

            if (
                key === 'a' &&
                !event.ctrlKey &&
                !event.metaKey &&
                !event.altKey
            ) {

                event.preventDefault();

                if (form) {
                    form.requestSubmit();
                }

                return;
            }


            /*
            | B = BACK
            */

            if (
                key === 'b' &&
                !event.ctrlKey &&
                !event.metaKey &&
                !event.altKey
            ) {

                event.preventDefault();

                window.location.href = 'index.php';

                return;
            }


            /*
            | N = NAME
            */

            if (
                key === 'n' &&
                !event.ctrlKey &&
                !event.metaKey &&
                !event.altKey
            ) {

                event.preventDefault();

                nameInput?.focus();

                return;
            }


            /*
            | C = CODE
            */

            if (
                key === 'c' &&
                !event.ctrlKey &&
                !event.metaKey &&
                !event.altKey
            ) {

                event.preventDefault();

                codeInput?.focus();

                return;
            }

        });


        /*
        |--------------------------------------------------------------------------
        | BACK-FORWARD CACHE
        |--------------------------------------------------------------------------
        */

        window.addEventListener('pageshow', function () {

            if (saveButton) {
                saveButton.disabled = false;
            }

            if (saveText) {
                saveText.textContent = 'Save Warehouse';
            }

        });

    });

})();

</script>

<?php

require_once __DIR__ . '/../includes/footer.php';

?>