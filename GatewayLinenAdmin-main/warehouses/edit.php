<?php

session_start();

/*
|--------------------------------------------------------------------------
| GatewayLinen Admin - Edit Warehouse
|--------------------------------------------------------------------------
| File:
| GatewayLinenadmin/warehouses/edit.php
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| ADMIN AUTHENTICATION
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
$pageTitle  = 'GatewayLinen | Edit Warehouse';


/*
|--------------------------------------------------------------------------
| ADMIN SESSION FALLBACKS
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
| SAFE OUTPUT HELPER
|--------------------------------------------------------------------------
| Unique function name so it does not conflict with header/sidebar files.
|--------------------------------------------------------------------------
*/

if (!function_exists('gateway_warehouse_edit_escape')) {
    function gateway_warehouse_edit_escape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


/*
|--------------------------------------------------------------------------
| STRING LENGTH HELPER
|--------------------------------------------------------------------------
*/

if (!function_exists('gateway_warehouse_edit_strlen')) {
    function gateway_warehouse_edit_strlen($value): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen((string) $value);
        }

        return strlen((string) $value);
    }
}


/*
|--------------------------------------------------------------------------
| SQL ERROR LOGGING
|--------------------------------------------------------------------------
| Detailed SQL errors are logged on server only.
| They are NOT shown to the admin user.
|--------------------------------------------------------------------------
*/

if (!function_exists('gateway_warehouse_edit_log_sql_error')) {
    function gateway_warehouse_edit_log_sql_error(string $context): void
    {
        $errors = sqlsrv_errors(SQLSRV_ERR_ALL);

        if (!$errors) {
            error_log('[GatewayLinen Warehouse Edit] ' . $context);
            return;
        }

        $safeErrors = [];

        foreach ($errors as $sqlError) {
            $safeErrors[] = [
                'SQLSTATE' => $sqlError['SQLSTATE'] ?? '',
                'code'     => $sqlError['code'] ?? '',
                'message'  => $sqlError['message'] ?? '',
            ];
        }

        error_log(
            '[GatewayLinen Warehouse Edit] ' .
            $context .
            ' | ' .
            json_encode($safeErrors, JSON_UNESCAPED_UNICODE)
        );
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

$csrfToken = (string) $_SESSION['warehouse_csrf_token'];


/*
|--------------------------------------------------------------------------
| WAREHOUSE ID
|--------------------------------------------------------------------------
*/

$warehouseId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT,
    [
        'options' => [
            'min_range' => 1
        ]
    ]
);


/*
|--------------------------------------------------------------------------
| VALIDATE WAREHOUSE ID
|--------------------------------------------------------------------------
*/

if (!$warehouseId) {
    header(
        'Location: index.php?error=' .
        rawurlencode('Invalid warehouse ID.')
    );
    exit;
}


/*
|--------------------------------------------------------------------------
| DEFAULT FORM VALUES
|--------------------------------------------------------------------------
*/

$warehouseCode = '';
$warehouseName = '';
$address       = '';
$city          = '';
$stateProvince = '';
$postalCode    = '';
$isPrimary     = 0;
$isActive      = 1;

$error = '';


/*
|--------------------------------------------------------------------------
| FETCH EXISTING WAREHOUSE
|--------------------------------------------------------------------------
*/

$fetchSql = "
    SELECT
        WarehouseId,
        WarehouseCode,
        WarehouseName,
        Address,
        City,
        StateProvince,
        PostalCode,
        IsPrimary,
        IsActive
    FROM dbo.Warehouses
    WHERE WarehouseId = ?
";

$fetchStmt = sqlsrv_query(
    $conn,
    $fetchSql,
    [$warehouseId]
);

if ($fetchStmt === false) {

    gateway_warehouse_edit_log_sql_error(
        'Unable to fetch warehouse ID ' . $warehouseId
    );

    header(
        'Location: index.php?error=' .
        rawurlencode('Unable to load warehouse.')
    );
    exit;
}

$warehouse = sqlsrv_fetch_array(
    $fetchStmt,
    SQLSRV_FETCH_ASSOC
);

sqlsrv_free_stmt($fetchStmt);


if (!$warehouse) {
    header(
        'Location: index.php?error=' .
        rawurlencode('Warehouse not found.')
    );
    exit;
}


/*
|--------------------------------------------------------------------------
| LOAD DATABASE VALUES
|--------------------------------------------------------------------------
*/

$warehouseCode = trim((string) ($warehouse['WarehouseCode'] ?? ''));
$warehouseName = trim((string) ($warehouse['WarehouseName'] ?? ''));
$address       = trim((string) ($warehouse['Address'] ?? ''));
$city          = trim((string) ($warehouse['City'] ?? ''));
$stateProvince = trim((string) ($warehouse['StateProvince'] ?? ''));
$postalCode    = trim((string) ($warehouse['PostalCode'] ?? ''));
$isPrimary     = (int) ($warehouse['IsPrimary'] ?? 0);
$isActive      = (int) ($warehouse['IsActive'] ?? 1);


/*
|--------------------------------------------------------------------------
| HANDLE FORM SUBMISSION
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | CSRF VALIDATION
    |--------------------------------------------------------------------------
    */

    $postedToken = (string) ($_POST['csrf_token'] ?? '');

    if (
        !hash_equals(
            (string) $_SESSION['warehouse_csrf_token'],
            $postedToken
        )
    ) {
        $error = 'Security verification failed. Please refresh the page and try again.';
    }


    /*
    |--------------------------------------------------------------------------
    | READ FORM VALUES
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $warehouseCode = strtoupper(
            trim((string) ($_POST['warehouse_code'] ?? ''))
        );

        $warehouseName = trim(
            (string) ($_POST['warehouse_name'] ?? '')
        );

        $address = trim(
            (string) ($_POST['address'] ?? '')
        );

        $city = trim(
            (string) ($_POST['city'] ?? '')
        );

        $stateProvince = trim(
            (string) ($_POST['state_province'] ?? '')
        );

        $postalCode = trim(
            (string) ($_POST['postal_code'] ?? '')
        );

        $isPrimary = isset($_POST['is_primary']) ? 1 : 0;
        $isActive  = isset($_POST['is_active']) ? 1 : 0;
    }


    /*
    |--------------------------------------------------------------------------
    | SERVER-SIDE VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        if ($warehouseName === '') {

            $error = 'Warehouse name is required.';

        } elseif (
            gateway_warehouse_edit_strlen($warehouseName) > 100
        ) {

            $error = 'Warehouse name cannot exceed 100 characters.';

        } elseif ($warehouseCode === '') {

            $error = 'Warehouse code is required.';

        } elseif (
            gateway_warehouse_edit_strlen($warehouseCode) > 50
        ) {

            $error = 'Warehouse code cannot exceed 50 characters.';

        } elseif (
            !preg_match(
                '/^[A-Z0-9][A-Z0-9._-]*$/',
                $warehouseCode
            )
        ) {

            $error = 'Warehouse code can contain only letters, numbers, hyphens, underscores, and dots.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | ADDRESS VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        if (
            gateway_warehouse_edit_strlen($address) > 255
        ) {

            $error = 'Street address cannot exceed 255 characters.';

        } elseif (
            gateway_warehouse_edit_strlen($city) > 100
        ) {

            $error = 'City cannot exceed 100 characters.';

        } elseif (
            gateway_warehouse_edit_strlen($stateProvince) > 100
        ) {

            $error = 'State / Province cannot exceed 100 characters.';

        } elseif (
            gateway_warehouse_edit_strlen($postalCode) > 20
        ) {

            $error = 'Postal / Zip Code cannot exceed 20 characters.';
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
            WHERE
                UPPER(LTRIM(RTRIM(WarehouseCode))) = ?
                AND WarehouseId <> ?
        ";

        $duplicateStmt = sqlsrv_query(
            $conn,
            $duplicateSql,
            [
                $warehouseCode,
                $warehouseId
            ]
        );

        if ($duplicateStmt === false) {

            gateway_warehouse_edit_log_sql_error(
                'Duplicate warehouse code check failed.'
            );

            $error = 'Unable to validate warehouse code right now. Please try again.';

        } else {

            $duplicateWarehouse = sqlsrv_fetch_array(
                $duplicateStmt,
                SQLSRV_FETCH_ASSOC
            );

            sqlsrv_free_stmt($duplicateStmt);

            if ($duplicateWarehouse) {
                $error = 'This warehouse code is already being used by another warehouse.';
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE WAREHOUSE
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        /*
        |--------------------------------------------------------------------------
        | START SQL TRANSACTION
        |--------------------------------------------------------------------------
        */

        if (!sqlsrv_begin_transaction($conn)) {

            gateway_warehouse_edit_log_sql_error(
                'Unable to start warehouse update transaction.'
            );

            $error = 'Unable to update warehouse right now. Please try again.';

        } else {

            $transactionSuccessful = true;


            /*
            |--------------------------------------------------------------------------
            | RESET EXISTING PRIMARY WAREHOUSE
            |--------------------------------------------------------------------------
            */

            if ($isPrimary === 1) {

                $resetPrimarySql = "
                    UPDATE dbo.Warehouses
                    SET IsPrimary = 0
                    WHERE WarehouseId <> ?
                ";

                $resetPrimaryStmt = sqlsrv_query(
                    $conn,
                    $resetPrimarySql,
                    [$warehouseId]
                );

                if ($resetPrimaryStmt === false) {

                    gateway_warehouse_edit_log_sql_error(
                        'Unable to reset previous primary warehouse.'
                    );

                    $transactionSuccessful = false;
                }

                if ($resetPrimaryStmt !== false) {
                    sqlsrv_free_stmt($resetPrimaryStmt);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | UPDATE CURRENT WAREHOUSE
            |--------------------------------------------------------------------------
            */

            if ($transactionSuccessful) {

                $updateSql = "
                    UPDATE dbo.Warehouses
                    SET
                        WarehouseCode = ?,
                        WarehouseName = ?,
                        Address = ?,
                        City = ?,
                        StateProvince = ?,
                        PostalCode = ?,
                        IsPrimary = ?,
                        IsActive = ?
                    WHERE WarehouseId = ?
                ";

                $updateParams = [
                    $warehouseCode,
                    $warehouseName,

                    $address !== ''
                        ? $address
                        : null,

                    $city !== ''
                        ? $city
                        : null,

                    $stateProvince !== ''
                        ? $stateProvince
                        : null,

                    $postalCode !== ''
                        ? $postalCode
                        : null,

                    $isPrimary,
                    $isActive,
                    $warehouseId
                ];

                $updateStmt = sqlsrv_query(
                    $conn,
                    $updateSql,
                    $updateParams
                );

                if ($updateStmt === false) {

                    gateway_warehouse_edit_log_sql_error(
                        'Unable to update warehouse ID ' . $warehouseId
                    );

                    $transactionSuccessful = false;
                }

                if ($updateStmt !== false) {

                    sqlsrv_free_stmt($updateStmt);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | COMMIT / ROLLBACK
            |--------------------------------------------------------------------------
            */

            if ($transactionSuccessful) {

                if (!sqlsrv_commit($conn)) {

                    gateway_warehouse_edit_log_sql_error(
                        'Unable to commit warehouse update.'
                    );

                    sqlsrv_rollback($conn);

                    $error = 'Unable to save warehouse changes. Please try again.';

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | REGENERATE CSRF TOKEN
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
                        rawurlencode('Warehouse updated successfully.')
                    );

                    exit;
                }

            } else {

                sqlsrv_rollback($conn);

                $error = 'Unable to update warehouse right now. Please check the information and try again.';
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

$csrfToken = (string) $_SESSION['warehouse_csrf_token'];


/*
|--------------------------------------------------------------------------
| COMMON ADMIN HEADER + SIDEBAR
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<style>

/*
|--------------------------------------------------------------------------
| GATEWAYLINEN WAREHOUSE EDIT PAGE
|--------------------------------------------------------------------------
| This page does NOT create its own theme toggle.
| It follows the shared admin theme using:
|
| html[data-theme="light"]
| html[data-theme="dark"]
|
| Compatibility with body.light-mode / body.dark-mode
| is also included.
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| DARK THEME - DEFAULT
|--------------------------------------------------------------------------
*/

:root {
    color-scheme: dark;

    --gw-page-bg: #0a1119;
    --gw-card-bg: #111b26;
    --gw-card-alt: #0f1823;
    --gw-input-bg: #0d1620;
    --gw-hover-bg: #16222e;

    --gw-border: #1e2d3d;
    --gw-border-soft: #182636;

    --gw-text-primary: #f0f4f8;
    --gw-text-body: #a8b8c8;
    --gw-text-muted: #71859a;

    --gw-green: #10b981;
    --gw-green-dark: #059669;
    --gw-green-soft: rgba(16, 185, 129, 0.12);

    --gw-blue: #38bdf8;
    --gw-blue-soft: rgba(56, 189, 248, 0.12);

    --gw-amber: #f59e0b;
    --gw-amber-soft: rgba(245, 158, 11, 0.12);

    --gw-red: #ef4444;
    --gw-red-soft: rgba(239, 68, 68, 0.12);

    --gw-shadow: 0 15px 40px rgba(0, 0, 0, 0.20);
}


/*
|--------------------------------------------------------------------------
| LIGHT THEME
|--------------------------------------------------------------------------
*/

html[data-theme="light"],
body.light-mode {

    color-scheme: light;

    --gw-page-bg: #f4f7fb;
    --gw-card-bg: #ffffff;
    --gw-card-alt: #f8fafc;
    --gw-input-bg: #ffffff;
    --gw-hover-bg: #eef2f7;

    --gw-border: #d7e0ea;
    --gw-border-soft: #e6ecf2;

    --gw-text-primary: #172033;
    --gw-text-body: #475569;
    --gw-text-muted: #64748b;

    --gw-green: #059669;
    --gw-green-dark: #047857;
    --gw-green-soft: rgba(5, 150, 105, 0.10);

    --gw-blue: #0284c7;
    --gw-blue-soft: rgba(2, 132, 199, 0.10);

    --gw-amber: #d97706;
    --gw-amber-soft: rgba(217, 119, 6, 0.10);

    --gw-red: #dc2626;
    --gw-red-soft: rgba(220, 38, 38, 0.10);

    --gw-shadow: 0 15px 40px rgba(15, 23, 42, 0.08);
}


/*
|--------------------------------------------------------------------------
| DARK BODY COMPATIBILITY
|--------------------------------------------------------------------------
*/

html[data-theme="dark"],
body.dark-mode {

    color-scheme: dark;

    --gw-page-bg: #0a1119;
    --gw-card-bg: #111b26;
    --gw-card-alt: #0f1823;
    --gw-input-bg: #0d1620;
    --gw-hover-bg: #16222e;

    --gw-border: #1e2d3d;
    --gw-border-soft: #182636;

    --gw-text-primary: #f0f4f8;
    --gw-text-body: #a8b8c8;
    --gw-text-muted: #71859a;

    --gw-green: #10b981;
    --gw-green-dark: #059669;
    --gw-green-soft: rgba(16, 185, 129, 0.12);

    --gw-blue: #38bdf8;
    --gw-blue-soft: rgba(56, 189, 248, 0.12);

    --gw-amber: #f59e0b;
    --gw-amber-soft: rgba(245, 158, 11, 0.12);

    --gw-red: #ef4444;
    --gw-red-soft: rgba(239, 68, 68, 0.12);

    --gw-shadow: 0 15px 40px rgba(0, 0, 0, 0.20);
}


/*
|--------------------------------------------------------------------------
| PAGE BASE
|--------------------------------------------------------------------------
*/

html,
body {
    min-height: 100%;
    background: var(--gw-page-bg) !important;
    color: var(--gw-text-body) !important;
}

body {
    transition:
        background-color 0.18s ease,
        color 0.18s ease;
}

.main,
.content {
    background: var(--gw-page-bg) !important;
    color: var(--gw-text-body) !important;
    min-height: 100%;
}


/*
|--------------------------------------------------------------------------
| MAIN PAGE
|--------------------------------------------------------------------------
*/

.edit-warehouse-page {
    width: 100%;
    max-width: 1180px;
    margin: 0 auto;
    padding: 20px 20px 45px;
    box-sizing: border-box;
}


/*
|--------------------------------------------------------------------------
| PAGE HEADER
|--------------------------------------------------------------------------
*/

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
    align-items: center;
    flex-wrap: wrap;
    gap: 7px;

    margin-bottom: 8px;

    color: var(--gw-text-muted);

    font-size: 10px;
    font-weight: 700;

    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.breadcrumb .current {
    color: var(--gw-green);
}

.page-title {
    margin: 0;

    color: var(--gw-text-primary);

    font-size: 27px;
    line-height: 1.2;
    font-weight: 800;
}

.page-subtitle {
    margin: 7px 0 0;

    color: var(--gw-text-muted);

    font-size: 12px;
    line-height: 1.5;
}


/*
|--------------------------------------------------------------------------
| BACK BUTTON
|--------------------------------------------------------------------------
*/

.btn-back {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;

    min-height: 40px;
    padding: 0 16px;

    border: 1px solid var(--gw-border);
    border-radius: 9px;

    background: var(--gw-input-bg);
    color: var(--gw-text-body) !important;

    font-family: inherit;
    font-size: 11px;
    font-weight: 700;

    text-decoration: none;

    cursor: pointer;

    transition:
        background-color 0.18s ease,
        border-color 0.18s ease,
        color 0.18s ease,
        transform 0.18s ease;
}

.btn-back:hover {
    border-color: var(--gw-green);
    background: var(--gw-green-soft);
    color: var(--gw-green) !important;
    transform: translateY(-1px);
}

.btn-back:focus-visible {
    outline: none;
    border-color: var(--gw-green);
    box-shadow: 0 0 0 3px var(--gw-green-soft);
}


/*
|--------------------------------------------------------------------------
| ERROR ALERT
|--------------------------------------------------------------------------
*/

.alert-error {
    display: flex;
    align-items: flex-start;
    gap: 11px;

    margin-bottom: 20px;
    padding: 14px 16px;

    border: 1px solid var(--gw-red-soft);
    border-left: 4px solid var(--gw-red);
    border-radius: 9px;

    background: var(--gw-red-soft);
    color: var(--gw-red);

    font-size: 12px;
    line-height: 1.5;
    font-weight: 600;
}

.alert-error-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;

    flex: 0 0 22px;

    width: 22px;
    height: 22px;

    border-radius: 50%;

    background: var(--gw-red);
    color: #ffffff;

    font-size: 12px;
    font-weight: 900;
}


/*
|--------------------------------------------------------------------------
| FORM CARD
|--------------------------------------------------------------------------
*/

.form-card {
    overflow: hidden;

    background: var(--gw-card-bg);

    border: 1px solid var(--gw-border);
    border-radius: 12px;

    box-shadow: var(--gw-shadow);

    transition:
        background-color 0.18s ease,
        border-color 0.18s ease,
        box-shadow 0.18s ease;
}

.form-body {
    padding: 24px 22px;
    box-sizing: border-box;
}

.form-grid {
    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 22px 26px;
}


/*
|--------------------------------------------------------------------------
| FORM SECTION HEADER
|--------------------------------------------------------------------------
*/

.form-section {
    grid-column: 1 / -1;

    display: flex;
    align-items: center;
    gap: 10px;

    margin-top: 14px;
    padding: 10px 14px;

    border-left: 3px solid var(--gw-green);
    border-radius: 8px;

    background: var(--gw-green-soft);
}

.form-section:first-child {
    margin-top: 0;
}

.form-section.sec-blue {
    border-left-color: var(--gw-blue);
    background: var(--gw-blue-soft);
}

.form-section.sec-amber {
    border-left-color: var(--gw-amber);
    background: var(--gw-amber-soft);
}

.form-section-icon {
    display: flex;
    align-items: center;
    justify-content: center;

    flex: 0 0 26px;

    width: 26px;
    height: 26px;

    border-radius: 7px;

    background: rgba(16, 185, 129, 0.16);
    color: var(--gw-green);

    font-size: 12px;
    font-weight: 900;
}

.sec-blue .form-section-icon {
    background: rgba(56, 189, 248, 0.16);
    color: var(--gw-blue);
}

.sec-amber .form-section-icon {
    background: rgba(245, 158, 11, 0.16);
    color: var(--gw-amber);
}

.form-section-title {
    color: var(--gw-text-primary);

    font-size: 10.5px;
    font-weight: 800;

    text-transform: uppercase;
    letter-spacing: 0.8px;
}


/*
|--------------------------------------------------------------------------
| FORM GROUP
|--------------------------------------------------------------------------
*/

.form-group {
    min-width: 0;
}

.form-group-full {
    grid-column: 1 / -1;
}


/*
|--------------------------------------------------------------------------
| LABEL
|--------------------------------------------------------------------------
*/

.form-label {
    display: block;

    margin-bottom: 8px;

    color: var(--gw-text-body);

    font-size: 10.5px;
    font-weight: 700;

    text-transform: uppercase;
    letter-spacing: 0.25px;
}

.required {
    margin-left: 3px;
    color: var(--gw-red);
}


/*
|--------------------------------------------------------------------------
| INPUTS
|--------------------------------------------------------------------------
*/

.form-input,
.form-textarea {
    width: 100%;

    box-sizing: border-box;

    border: 1px solid var(--gw-border);
    border-radius: 9px;

    outline: none;

    background: var(--gw-input-bg);
    color: var(--gw-text-primary);

    font-family: inherit;
    font-size: 12px;
    font-weight: 500;

    transition:
        border-color 0.18s ease,
        box-shadow 0.18s ease,
        background-color 0.18s ease;
}

.form-input {
    height: 44px;
    padding: 0 13px;
}

.form-textarea {
    min-height: 90px;

    padding: 12px 13px;

    resize: vertical;

    line-height: 1.5;
}

.form-input:hover,
.form-textarea:hover {
    border-color: var(--gw-text-muted);
}

.form-input:focus,
.form-textarea:focus {
    border-color: var(--gw-green);

    box-shadow:
        0 0 0 3px var(--gw-green-soft);
}

.form-input::placeholder,
.form-textarea::placeholder {
    color: var(--gw-text-muted);
}

.form-input:disabled,
.form-textarea:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}


/*
|--------------------------------------------------------------------------
| WAREHOUSE CODE
|--------------------------------------------------------------------------
*/

#warehouseCode {
    text-transform: uppercase;
    letter-spacing: 0.4px;
}


/*
|--------------------------------------------------------------------------
| CHECKBOX
|--------------------------------------------------------------------------
*/

.checkbox-box {
    display: flex;
    align-items: center;

    min-height: 44px;
    padding: 0 14px;

    box-sizing: border-box;

    border: 1px solid var(--gw-border);
    border-radius: 9px;

    background: var(--gw-input-bg);

    transition:
        border-color 0.18s ease,
        background-color 0.18s ease;
}

.checkbox-box:hover {
    border-color: var(--gw-text-muted);
    background: var(--gw-hover-bg);
}

.checkbox-label {
    display: inline-flex;
    align-items: center;
    gap: 10px;

    color: var(--gw-text-body);

    font-size: 11.5px;
    font-weight: 600;

    cursor: pointer;
}

.checkbox-input {
    width: 18px;
    height: 18px;

    margin: 0;

    accent-color: var(--gw-green);

    cursor: pointer;
}

.checkbox-input:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px var(--gw-green-soft);
}


/*
|--------------------------------------------------------------------------
| FORM FOOTER
|--------------------------------------------------------------------------
*/

.form-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;

    padding: 18px 22px;

    border-top: 1px solid var(--gw-border);

    background: var(--gw-card-alt);
}


/*
|--------------------------------------------------------------------------
| UPDATE BUTTON
|--------------------------------------------------------------------------
*/

.btn-submit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;

    min-height: 42px;
    padding: 0 22px;

    border: 1px solid var(--gw-green-dark);
    border-radius: 9px;

    background: var(--gw-green);
    color: #ffffff;

    font-family: inherit;
    font-size: 11.5px;
    font-weight: 700;

    cursor: pointer;

    box-shadow:
        0 6px 18px rgba(16, 185, 129, 0.20);

    transition:
        background-color 0.18s ease,
        transform 0.18s ease,
        opacity 0.18s ease,
        box-shadow 0.18s ease;
}

.btn-submit:hover {
    background: var(--gw-green-dark);
    transform: translateY(-1px);
}

.btn-submit:active {
    transform: translateY(0);
}

.btn-submit:focus-visible {
    outline: none;

    box-shadow:
        0 0 0 3px var(--gw-green-soft),
        0 6px 18px rgba(16, 185, 129, 0.20);
}

.btn-submit:disabled {
    opacity: 0.65;
    cursor: wait;
    transform: none;
}


/*
|--------------------------------------------------------------------------
| SHORTCUT BOX
|--------------------------------------------------------------------------
*/

.shortcut-help-box {
    margin-top: 22px;
    padding: 18px 22px;

    border: 1px solid var(--gw-border);
    border-radius: 12px;

    background: var(--gw-card-bg);

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

    color: var(--gw-text-primary);

    font-size: 13px;
    font-weight: 700;
}

.shortcut-help-title small {
    margin-left: auto;

    color: var(--gw-text-muted);

    font:
        600 10px
        monospace;
}

.shortcut-grid {
    display: grid;

    grid-template-columns:
        repeat(auto-fill, minmax(220px, 1fr));

    gap: 10px;
}

.shortcut-item {
    display: flex;
    align-items: center;
    gap: 11px;

    padding: 8px 11px;

    border: 1px solid var(--gw-border-soft);
    border-radius: 9px;

    background: var(--gw-input-bg);
}

.shortcut-key {
    display: inline-flex;
    align-items: center;
    justify-content: center;

    min-width: 38px;
    height: 30px;

    padding: 0 10px;

    box-sizing: border-box;

    border: 1px solid var(--gw-border);
    border-radius: 7px;

    background: var(--gw-hover-bg);
    color: var(--gw-green);

    font:
        900 11px
        monospace;
}

.shortcut-desc {
    color: var(--gw-text-body);

    font-size: 11px;
    font-weight: 600;
}


/*
|--------------------------------------------------------------------------
| RESPONSIVE
|--------------------------------------------------------------------------
*/

@media (max-width: 900px) {

    .edit-warehouse-page {
        padding: 18px 15px 35px;
    }

    .page-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .page-title {
        font-size: 24px;
    }

    .page-header .btn-back {
        width: 100%;
    }

    .form-body {
        padding: 20px 16px;
    }

    .form-grid {
        grid-template-columns: 1fr;
        gap: 18px;
    }

    .form-group-full {
        grid-column: auto;
    }

    .form-section {
        grid-column: 1 / -1;
    }

    .form-footer {
        padding: 16px;
    }
}


@media (max-width: 600px) {

    .edit-warehouse-page {
        padding: 15px 10px 30px;
    }

    .page-header {
        margin-bottom: 18px;
        padding-bottom: 16px;
    }

    .breadcrumb {
        font-size: 9px;
    }

    .page-title {
        font-size: 21px;
    }

    .page-subtitle {
        font-size: 11px;
    }

    .form-card {
        border-radius: 10px;
    }

    .form-body {
        padding: 16px 12px;
    }

    .form-footer {
        flex-direction: column-reverse;
        align-items: stretch;
    }

    .form-footer .btn-back,
    .form-footer .btn-submit {
        width: 100%;
    }

    .shortcut-help-box {
        padding: 15px 13px;
    }

    .shortcut-grid {
        grid-template-columns: 1fr;
    }

    .shortcut-help-title small {
        display: none;
    }
}


/*
|--------------------------------------------------------------------------
| REDUCED MOTION
|--------------------------------------------------------------------------
*/

@media (prefers-reduced-motion: reduce) {

    *,
    *::before,
    *::after {
        transition: none !important;
        animation: none !important;
    }
}

</style>


<main class="main">

    <section class="content">

        <div class="edit-warehouse-page">


            <!-- ==========================================================
                 PAGE HEADER
            =========================================================== -->

            <div class="page-header">

                <div>

                    <div class="breadcrumb">
                        <span>Dashboard</span>
                        <span>›</span>
                        <span>Warehouses</span>
                        <span>›</span>
                        <span class="current">Edit Warehouse</span>
                    </div>

                    <h1 class="page-title">
                        Edit Warehouse
                    </h1>

                    <p class="page-subtitle">
                        Modify warehouse information, location, and operational configurations.
                    </p>

                </div>


                <a
                    href="index.php"
                    class="btn-back"
                    title="Back to Warehouses"
                >
                    ← Back to Warehouses
                </a>

            </div>


            <!-- ==========================================================
                 ERROR MESSAGE
            =========================================================== -->

            <?php if ($error !== ''): ?>

                <div
                    class="alert-error"
                    role="alert"
                    aria-live="polite"
                >

                    <span class="alert-error-icon">
                        !
                    </span>

                    <span>
                        <?= gateway_warehouse_edit_escape($error) ?>
                    </span>

                </div>

            <?php endif; ?>


            <!-- ==========================================================
                 EDIT FORM
            =========================================================== -->

            <form
                method="POST"
                action=""
                autocomplete="off"
                class="form-card"
                id="editWarehouseForm"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= gateway_warehouse_edit_escape($csrfToken) ?>"
                >


                <div class="form-body">

                    <div class="form-grid">


                        <!-- ==================================================
                             WAREHOUSE DETAILS
                        =================================================== -->

                        <div class="form-section">

                            <div class="form-section-icon">
                                #
                            </div>

                            <div class="form-section-title">
                                Warehouse Details
                            </div>

                        </div>


                        <!-- WAREHOUSE NAME -->

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
                                value="<?= gateway_warehouse_edit_escape($warehouseName) ?>"
                                maxlength="100"
                                required
                                autocomplete="organization"
                                placeholder="Enter warehouse name"
                            >

                        </div>


                        <!-- WAREHOUSE CODE -->

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
                                value="<?= gateway_warehouse_edit_escape($warehouseCode) ?>"
                                maxlength="50"
                                required
                                spellcheck="false"
                                autocapitalize="characters"
                                placeholder="e.g. WH-MUM-01"
                            >

                        </div>


                        <!-- ==================================================
                             LOCATION
                        =================================================== -->

                        <div class="form-section sec-blue">

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
                                placeholder="Enter complete warehouse address"
                            ><?= gateway_warehouse_edit_escape($address) ?></textarea>

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
                                value="<?= gateway_warehouse_edit_escape($city) ?>"
                                maxlength="100"
                                placeholder="Enter city"
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
                                value="<?= gateway_warehouse_edit_escape($stateProvince) ?>"
                                maxlength="100"
                                placeholder="Enter state or province"
                            >

                        </div>


                        <!-- POSTAL CODE -->

                        <div class="form-group">

                            <label
                                for="postalCode"
                                class="form-label"
                            >
                                Postal / Zip Code
                            </label>

                            <input
                                type="text"
                                id="postalCode"
                                name="postal_code"
                                class="form-input"
                                value="<?= gateway_warehouse_edit_escape($postalCode) ?>"
                                maxlength="20"
                                placeholder="Enter postal / zip code"
                            >

                        </div>


                        <!-- ==================================================
                             STATUS & SETTINGS
                        =================================================== -->

                        <div class="form-section sec-amber">

                            <div class="form-section-icon">
                                ⚙
                            </div>

                            <div class="form-section-title">
                                Status & Settings
                            </div>

                        </div>


                        <!-- ACTIVE STATUS -->

                        <div class="form-group">

                            <label class="form-label">
                                Operational Status
                            </label>

                            <div class="checkbox-box">

                                <label
                                    class="checkbox-label"
                                    for="isActive"
                                >

                                    <input
                                        type="checkbox"
                                        id="isActive"
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

                        </div>


                        <!-- PRIMARY -->

                        <div class="form-group">

                            <label class="form-label">
                                Primary Designation
                            </label>

                            <div class="checkbox-box">

                                <label
                                    class="checkbox-label"
                                    for="isPrimary"
                                >

                                    <input
                                        type="checkbox"
                                        id="isPrimary"
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

                        </div>


                    </div>

                </div>


                <!-- ==========================================================
                     FORM FOOTER
                =========================================================== -->

                <div class="form-footer">

                    <a
                        href="index.php"
                        class="btn-back"
                        style="min-height:42px; padding:0 18px;"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="btn-submit"
                        id="updateWarehouseBtn"
                    >

                        <span>✓</span>

                        <span id="updateWarehouseText">
                            Update Warehouse
                        </span>

                    </button>

                </div>

            </form>


            <!-- ==========================================================
                 KEYBOARD SHORTCUTS
            =========================================================== -->

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
                        Press H to show / hide
                    </small>

                </div>


                <div class="shortcut-grid">

                    <div class="shortcut-item">

                        <span class="shortcut-key">
                            A
                        </span>

                        <span class="shortcut-desc">
                            Update Warehouse
                        </span>

                    </div>


                    <div class="shortcut-item">

                        <span class="shortcut-key">
                            B
                        </span>

                        <span class="shortcut-desc">
                            Back to List
                        </span>

                    </div>


                    <div class="shortcut-item">

                        <span class="shortcut-key">
                            N
                        </span>

                        <span class="shortcut-desc">
                            Focus Name
                        </span>

                    </div>


                    <div class="shortcut-item">

                        <span class="shortcut-key">
                            H
                        </span>

                        <span class="shortcut-desc">
                            Toggle Shortcuts
                        </span>

                    </div>


                    <div class="shortcut-item">

                        <span class="shortcut-key">
                            Esc
                        </span>

                        <span class="shortcut-desc">
                            Remove Focus
                        </span>

                    </div>

                </div>

            </div>


        </div>

    </section>

</main>


<script>

document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('editWarehouseForm');

    const nameInput = document.getElementById('warehouseName');

    const codeInput = document.getElementById('warehouseCode');

    const shortcutBox = document.getElementById('shortcutHelpBox');

    const updateButton = document.getElementById('updateWarehouseBtn');

    const updateText = document.getElementById('updateWarehouseText');


    /*
    |--------------------------------------------------------------------------
    | INITIAL FOCUS
    |--------------------------------------------------------------------------
    */

    if (
        nameInput &&
        window.innerWidth > 700
    ) {

        setTimeout(function () {

            try {
                nameInput.focus();

                const length = nameInput.value.length;

                nameInput.setSelectionRange(
                    length,
                    length
                );

            } catch (error) {
                // Ignore focus errors.
            }

        }, 180);
    }


    /*
    |--------------------------------------------------------------------------
    | WAREHOUSE CODE - UPPERCASE
    |--------------------------------------------------------------------------
    */

    if (codeInput) {

        codeInput.addEventListener(
            'input',
            function () {

                this.value = this.value.toUpperCase();

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | FORM SUBMIT
    |--------------------------------------------------------------------------
    | Prevent double-click / duplicate submissions.
    |--------------------------------------------------------------------------
    */

    if (form) {

        form.addEventListener(
            'submit',
            function (event) {

                /*
                |--------------------------------------------------------------
                | Browser validation first
                |--------------------------------------------------------------
                */

                if (!form.checkValidity()) {
                    return;
                }


                /*
                |--------------------------------------------------------------
                | Disable button after validation
                |--------------------------------------------------------------
                */

                if (updateButton) {

                    updateButton.disabled = true;

                }

                if (updateText) {

                    updateText.textContent = 'Updating...';

                }

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | RESTORE BUTTON AFTER BROWSER BACK/FORWARD
    |--------------------------------------------------------------------------
    */

    window.addEventListener(
        'pageshow',
        function () {

            if (updateButton) {

                updateButton.disabled = false;

            }

            if (updateText) {

                updateText.textContent = 'Update Warehouse';

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | KEYBOARD SHORTCUTS
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'keydown',
        function (event) {

            const key = event.key.toLowerCase();

            const activeElement = document.activeElement;

            const isTyping =
                activeElement &&
                (
                    activeElement.tagName === 'INPUT' ||
                    activeElement.tagName === 'TEXTAREA' ||
                    activeElement.tagName === 'SELECT'
                );

            const hasModifier =
                event.ctrlKey ||
                event.metaKey ||
                event.altKey;


            /*
            |--------------------------------------------------------------
            | ESC
            |--------------------------------------------------------------
            */

            if (event.key === 'Escape') {

                if (activeElement) {

                    activeElement.blur();

                }

                return;
            }


            /*
            |--------------------------------------------------------------
            | CTRL + S / CMD + S
            |--------------------------------------------------------------
            */

            if (
                key === 's' &&
                (event.ctrlKey || event.metaKey)
            ) {

                event.preventDefault();

                if (form) {

                    form.requestSubmit();

                }

                return;
            }


            /*
            |--------------------------------------------------------------
            | H - SHOW / HIDE SHORTCUTS
            |--------------------------------------------------------------
            */

            if (
                key === 'h' &&
                !hasModifier &&
                !isTyping
            ) {

                event.preventDefault();

                if (shortcutBox) {

                    shortcutBox.classList.toggle('hidden');

                }

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Do not execute normal shortcuts while typing
            |--------------------------------------------------------------------------
            */

            if (isTyping) {
                return;
            }


            /*
            |--------------------------------------------------------------------------
            | A - UPDATE
            |--------------------------------------------------------------------------
            */

            if (
                key === 'a' &&
                !hasModifier
            ) {

                event.preventDefault();

                if (form) {

                    /*
                    | requestSubmit() keeps HTML validation active.
                    */
                    form.requestSubmit();

                }

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | B - BACK
            |--------------------------------------------------------------------------
            */

            if (
                key === 'b' &&
                !hasModifier
            ) {

                event.preventDefault();

                window.location.href = 'index.php';

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | N - FOCUS NAME
            |--------------------------------------------------------------------------
            */

            if (
                key === 'n' &&
                !hasModifier
            ) {

                event.preventDefault();

                if (nameInput) {

                    nameInput.focus();

                }

            }

        }
    );

});

</script>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>