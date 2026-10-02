<?php

/* =========================================================
   GATEWAYLINEN ADMIN
   VARIANT EDIT PAGE
   ========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================================================
   AUTH CHECK
   ========================================================= */

if (empty($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}

/* =========================================================
   DATABASE
   ========================================================= */

require_once __DIR__ . '/../config/database.php';

/* =========================================================
   PAGE SETTINGS
   ========================================================= */

$activeMenu = 'variants';
$pageTitle  = 'GatewayLinen | Edit Variant';

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] =
        $_SESSION['admin_username'] ??
        'GatewayLinen Administrator';
}

/* =========================================================
   HELPER FUNCTIONS
   ========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function postValue($key, $default = '')
{
    if (isset($_POST[$key])) {
        return trim((string)$_POST[$key]);
    }

    return $default;
}

function nullableStringValue($value)
{
    $value = trim((string)$value);

    return $value === '' ? null : $value;
}

function nullableNumberValue($value)
{
    if ($value === null || $value === '') {
        return null;
    }

    if (!is_numeric($value)) {
        return null;
    }

    return (float)$value;
}

function getSqlsrvErrorMessage()
{
    $errors = sqlsrv_errors();

    if (!$errors) {
        return 'Unknown database error.';
    }

    $messages = [];

    foreach ($errors as $error) {
        if (!empty($error['message'])) {
            $messages[] = trim($error['message']);
        }
    }

    if (empty($messages)) {
        return 'Unknown database error.';
    }

    return implode(' | ', $messages);
}

/* =========================================================
   VARIANT ID
   ========================================================= */

$variantId = 0;

if (isset($_GET['id'])) {
    $variantId = (int)$_GET['id'];
}

if ($variantId <= 0) {
    header('Location: index.php');
    exit;
}

/* =========================================================
   CSRF TOKEN
   ========================================================= */

if (empty($_SESSION['variant_edit_csrf'])) {
    $_SESSION['variant_edit_csrf'] =
        bin2hex(random_bytes(32));
}

$csrfToken =
    $_SESSION['variant_edit_csrf'];

/* =========================================================
   MESSAGE
   ========================================================= */

$actionMessage = '';
$actionType = '';

/* =========================================================
   FETCH CURRENT VARIANT
   ========================================================= */

$variantData = null;

$fetchSql = "
    SELECT
        VariantId,
        ProductId,
        SKU,
        Barcode,
        Size,
        Color,
        ThreadCount,
        Material,
        WeightGSM,
        Dimensions,
        Price,
        CompareAtPrice,
        WholesalePrice,
        CostPrice,
        WeightKg,
        LowStockThreshold,
        IsActive
    FROM dbo.ProductVariants
    WHERE VariantId = ?
";

$fetchStmt = sqlsrv_query(
    $conn,
    $fetchSql,
    array($variantId)
);

if ($fetchStmt === false) {

    die(
        '<div style="font-family:Arial;padding:30px;color:#b91c1c;">' .
        '<h2>Unable to load variant</h2>' .
        '<p>' . e(getSqlsrvErrorMessage()) . '</p>' .
        '</div>'
    );
}

$variantData = sqlsrv_fetch_array(
    $fetchStmt,
    SQLSRV_FETCH_ASSOC
);

sqlsrv_free_stmt($fetchStmt);

if (!$variantData) {
    header('Location: index.php?msg=notfound');
    exit;
}

/* =========================================================
   FORM DATA
   ========================================================= */

$form = array(

    'product_id' =>
        isset($variantData['ProductId'])
            ? $variantData['ProductId']
            : '',

    'sku' =>
        isset($variantData['SKU'])
            ? $variantData['SKU']
            : '',

    'barcode' =>
        isset($variantData['Barcode'])
            ? $variantData['Barcode']
            : '',

    'size' =>
        isset($variantData['Size'])
            ? $variantData['Size']
            : '',

    'color' =>
        isset($variantData['Color'])
            ? $variantData['Color']
            : '',

    'thread_count' =>
        isset($variantData['ThreadCount'])
            ? $variantData['ThreadCount']
            : '',

    'material' =>
        isset($variantData['Material'])
            ? $variantData['Material']
            : '',

    'weight_gsm' =>
        isset($variantData['WeightGSM'])
            ? $variantData['WeightGSM']
            : '',

    'dimensions' =>
        isset($variantData['Dimensions'])
            ? $variantData['Dimensions']
            : '',

    'price' =>
        isset($variantData['Price']) &&
        $variantData['Price'] !== null
            ? number_format(
                (float)$variantData['Price'],
                2,
                '.',
                ''
            )
            : '',

    'compare_at_price' =>
        isset($variantData['CompareAtPrice']) &&
        $variantData['CompareAtPrice'] !== null
            ? number_format(
                (float)$variantData['CompareAtPrice'],
                2,
                '.',
                ''
            )
            : '',

    'wholesale_price' =>
        isset($variantData['WholesalePrice']) &&
        $variantData['WholesalePrice'] !== null
            ? number_format(
                (float)$variantData['WholesalePrice'],
                2,
                '.',
                ''
            )
            : '',

    'cost_price' =>
        isset($variantData['CostPrice']) &&
        $variantData['CostPrice'] !== null
            ? number_format(
                (float)$variantData['CostPrice'],
                2,
                '.',
                ''
            )
            : '',

    'weight_kg' =>
        isset($variantData['WeightKg']) &&
        $variantData['WeightKg'] !== null
            ? number_format(
                (float)$variantData['WeightKg'],
                3,
                '.',
                ''
            )
            : '',

    'low_stock_threshold' =>
        isset($variantData['LowStockThreshold']) &&
        $variantData['LowStockThreshold'] !== null
            ? (int)$variantData['LowStockThreshold']
            : 5,

    'is_active' =>
        !empty($variantData['IsActive'])
            ? 1
            : 0
);

/* =========================================================
   UPDATE
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* =====================================================
       CSRF CHECK
       ===================================================== */

    $submittedToken =
        isset($_POST['csrf_token'])
            ? $_POST['csrf_token']
            : '';

    if (
        empty($submittedToken) ||
        !hash_equals(
            $csrfToken,
            (string)$submittedToken
        )
    ) {

        $actionMessage =
            'Security validation failed. Please refresh the page and try again.';

        $actionType = 'error';

    } else {

        /* =================================================
           GET FORM VALUES
           ================================================= */

        $form['product_id'] =
            postValue('product_id');

        $form['sku'] =
            postValue('sku');

        $form['barcode'] =
            postValue('barcode');

        $form['size'] =
            postValue('size');

        $form['color'] =
            postValue('color');

        $form['thread_count'] =
            postValue('thread_count');

        $form['material'] =
            postValue('material');

        $form['weight_gsm'] =
            postValue('weight_gsm');

        $form['dimensions'] =
            postValue('dimensions');

        $form['price'] =
            postValue('price');

        $form['compare_at_price'] =
            postValue('compare_at_price');

        $form['wholesale_price'] =
            postValue('wholesale_price');

        $form['cost_price'] =
            postValue('cost_price');

        $form['weight_kg'] =
            postValue('weight_kg');

        $form['low_stock_threshold'] =
            postValue(
                'low_stock_threshold',
                '5'
            );

        $form['is_active'] =
            isset($_POST['is_active'])
                ? 1
                : 0;

        /* =================================================
           VALIDATION
           ================================================= */

        $errors = array();

        $productId = (int)$form['product_id'];
        $sku = trim($form['sku']);
        $barcode = nullableStringValue($form['barcode']);
        $size = trim($form['size']);
        $color = trim($form['color']);
        $threadCount = trim($form['thread_count']);
        $material = trim($form['material']);
        $weightGsm = trim($form['weight_gsm']);
        $dimensions = trim($form['dimensions']);
        $priceRaw = trim($form['price']);
        $compareRaw = trim($form['compare_at_price']);
        $wholesaleRaw = trim($form['wholesale_price']);
        $costRaw = trim($form['cost_price']);
        $weightKgRaw = trim($form['weight_kg']);
        $lowStockRaw = trim($form['low_stock_threshold']);

        /* PRODUCT */
        if ($productId <= 0) {
            $errors[] = 'Please select a valid product.';
        }

        /* SKU */
        if ($sku === '') {
            $errors[] = 'SKU / Variant Code is required.';
        } elseif (mb_strlen($sku) > 100) {
            $errors[] = 'SKU cannot be longer than 100 characters.';
        }

        /* BARCODE */
        if ($barcode !== null && mb_strlen($barcode) > 100) {
            $errors[] = 'Barcode cannot be longer than 100 characters.';
        }

        /* ATTRIBUTE LENGTHS */
        $attributeLimits = array(
            array($size, 100, 'Size'),
            array($color, 100, 'Color'),
            array($threadCount, 100, 'Thread Count'),
            array($material, 150, 'Material'),
            array($dimensions, 150, 'Dimensions')
        );

        foreach ($attributeLimits as $attribute) {
            if (mb_strlen($attribute[0]) > $attribute[1]) {
                $errors[] = $attribute[2] . ' cannot be longer than ' . $attribute[1] . ' characters.';
            }
        }

        /* GSM */
        $weightGsmValue = null;
        if ($weightGsm !== '') {
            if (!is_numeric($weightGsm)) {
                $errors[] = 'Weight GSM must be a valid number.';
            } else {
                $weightGsmValue = (float)$weightGsm;
                if ($weightGsmValue < 0) {
                    $errors[] = 'Weight GSM cannot be negative.';
                }
            }
        }

        /* SELLING PRICE */
        $price = null;
        if ($priceRaw === '') {
            $errors[] = 'Selling Price is required.';
        } elseif (!is_numeric($priceRaw)) {
            $errors[] = 'Selling Price must be a valid number.';
        } else {
            $price = (float)$priceRaw;
            if ($price < 0) {
                $errors[] = 'Selling Price cannot be negative.';
            }
        }

        /* OPTIONAL PRICES */
        $compareAtPrice = null;
        $wholesalePrice = null;
        $costPrice = null;

        $optionalPrices = array(
            array($compareRaw, 'Compare At Price'),
            array($wholesaleRaw, 'Wholesale Price'),
            array($costRaw, 'Cost Price')
        );

        foreach ($optionalPrices as $item) {
            if ($item[0] !== '') {
                if (!is_numeric($item[0])) {
                    $errors[] = $item[1] . ' must be a valid number.';
                    continue;
                }

                $numericValue = (float)$item[0];

                if ($numericValue < 0) {
                    $errors[] = $item[1] . ' cannot be negative.';
                }

                if ($item[1] === 'Compare At Price') {
                    $compareAtPrice = $numericValue;
                } elseif ($item[1] === 'Wholesale Price') {
                    $wholesalePrice = $numericValue;
                } elseif ($item[1] === 'Cost Price') {
                    $costPrice = $numericValue;
                }
            }
        }

        /* PRICE RELATIONS */
        if ($price !== null && $compareAtPrice !== null && $compareAtPrice < $price) {
            $errors[] = 'Compare At Price should be greater than or equal to Selling Price.';
        }

        if ($price !== null && $wholesalePrice !== null && $wholesalePrice > $price) {
            $errors[] = 'Wholesale Price should not be greater than Selling Price.';
        }

        if ($price !== null && $costPrice !== null && $costPrice > $price) {
            $errors[] = 'Cost Price should not be greater than Selling Price.';
        }

        /* WEIGHT KG */
        $weightKg = null;
        if ($weightKgRaw !== '') {
            if (!is_numeric($weightKgRaw)) {
                $errors[] = 'Weight KG must be a valid number.';
            } else {
                $weightKg = (float)$weightKgRaw;
                if ($weightKg < 0) {
                    $errors[] = 'Weight KG cannot be negative.';
                }
            }
        }

        /* LOW STOCK */
        $lowStockThreshold = null;
        if ($lowStockRaw === '') {
            $errors[] = 'Low Stock Alert is required.';
        } elseif (!is_numeric($lowStockRaw)) {
            $errors[] = 'Low Stock Alert must be a valid number.';
        } elseif (filter_var($lowStockRaw, FILTER_VALIDATE_INT) === false) {
            $errors[] = 'Low Stock Alert must be a whole number.';
        } else {
            $lowStockThreshold = (int)$lowStockRaw;
            if ($lowStockThreshold < 0) {
                $errors[] = 'Low Stock Alert cannot be negative.';
            }
        }

        /* Keep the original form values ready for redisplay. */
        $form['price'] = $priceRaw;
        $form['compare_at_price'] = $compareRaw;
        $form['wholesale_price'] = $wholesaleRaw;
        $form['cost_price'] = $costRaw;
        $form['weight_kg'] = $weightKgRaw;
        $form['weight_gsm'] = $weightGsm;
        $form['low_stock_threshold'] = $lowStockRaw;

        /* =================================================
           CHECK PRODUCT EXISTS
           ================================================= */

        if (empty($errors)) {

            $productCheckSql = "
                SELECT TOP 1 ProductId
                FROM dbo.Products
                WHERE ProductId = ?
            ";

            $productCheckStmt =
                sqlsrv_query(
                    $conn,
                    $productCheckSql,
                    array($productId)
                );

            if ($productCheckStmt === false) {

                $errors[] =
                    'Unable to validate selected product.';

            } else {

                $productExists =
                    sqlsrv_fetch_array(
                        $productCheckStmt,
                        SQLSRV_FETCH_ASSOC
                    );

                sqlsrv_free_stmt(
                    $productCheckStmt
                );

                if (!$productExists) {

                    $errors[] =
                        'Selected product does not exist.';
                }
            }
        }

        /* =================================================
           CHECK DUPLICATE SKU
           ================================================= */

        if (empty($errors)) {

            $skuCheckSql = "
                SELECT TOP 1 VariantId
                FROM dbo.ProductVariants
                WHERE SKU = ?
                  AND VariantId <> ?
            ";

            $skuCheckStmt =
                sqlsrv_query(
                    $conn,
                    $skuCheckSql,
                    array(
                        $sku,
                        $variantId
                    )
                );

            if ($skuCheckStmt === false) {

                $errors[] =
                    'Unable to check SKU uniqueness.';

            } else {

                $duplicateSku =
                    sqlsrv_fetch_array(
                        $skuCheckStmt,
                        SQLSRV_FETCH_ASSOC
                    );

                sqlsrv_free_stmt(
                    $skuCheckStmt
                );

                if ($duplicateSku) {

                    $errors[] =
                        'This SKU already exists for another variant.';
                }
            }
        }

        /* =================================================
           CHECK DUPLICATE BARCODE
           ================================================= */

        if (
            empty($errors) &&
            $barcode !== null
        ) {

            $barcodeCheckSql = "
                SELECT TOP 1 VariantId
                FROM dbo.ProductVariants
                WHERE Barcode = ?
                  AND VariantId <> ?
            ";

            $barcodeCheckStmt =
                sqlsrv_query(
                    $conn,
                    $barcodeCheckSql,
                    array(
                        $barcode,
                        $variantId
                    )
                );

            if ($barcodeCheckStmt === false) {

                $errors[] =
                    'Unable to check barcode uniqueness.';

            } else {

                $duplicateBarcode =
                    sqlsrv_fetch_array(
                        $barcodeCheckStmt,
                        SQLSRV_FETCH_ASSOC
                    );

                sqlsrv_free_stmt(
                    $barcodeCheckStmt
                );

                if ($duplicateBarcode) {

                    $errors[] =
                        'This barcode already exists for another variant.';
                }
            }
        }

        /* =================================================
           UPDATE DATABASE
           ================================================= */

        if (empty($errors)) {

            $updateSql = "
                UPDATE dbo.ProductVariants
                SET
                    ProductId = ?,
                    SKU = ?,
                    Barcode = ?,
                    Size = ?,
                    Color = ?,
                    ThreadCount = ?,
                    Material = ?,
                    WeightGSM = ?,
                    Dimensions = ?,
                    Price = ?,
                    CompareAtPrice = ?,
                    WholesalePrice = ?,
                    CostPrice = ?,
                    WeightKg = ?,
                    LowStockThreshold = ?,
                    IsActive = ?
                WHERE VariantId = ?
            ";

            $updateParams = array(

                $productId,

                $sku,

                $barcode,

                nullableStringValue(
                    $form['size']
                ),

                nullableStringValue(
                    $form['color']
                ),

                nullableStringValue(
                    $form['thread_count']
                ),

                nullableStringValue(
                    $form['material']
                ),

                $weightGsm,

                nullableStringValue(
                    $form['dimensions']
                ),

                $price,

                $compareAtPrice,

                $wholesalePrice,

                $costPrice,

                $weightKg,

                $lowStockThreshold,

                $form['is_active'],

                $variantId
            );

            $updateStmt =
                sqlsrv_query(
                    $conn,
                    $updateSql,
                    $updateParams
                );

            if ($updateStmt === false) {

                $actionMessage =
                    'Variant update failed. ' .
                    getSqlsrvErrorMessage();

                $actionType = 'error';

            } else {

                sqlsrv_free_stmt(
                    $updateStmt
                );

                /* NEW CSRF TOKEN */

                unset(
                    $_SESSION['variant_edit_csrf']
                );

                /* SUCCESS */

                header(
                    'Location: index.php?msg=updated'
                );

                exit;
            }

        } else {

            $actionMessage =
                implode(' ', $errors);

            $actionType =
                'error';
        }
    }
}

/* =========================================================
   FETCH PRODUCTS
   ========================================================= */

$products = array();

$productSql = "
    SELECT
        ProductId,
        Name
    FROM dbo.Products
    ORDER BY Name ASC
";

$productStmt =
    sqlsrv_query(
        $conn,
        $productSql
    );

if ($productStmt !== false) {

    while (
        $productRow =
            sqlsrv_fetch_array(
                $productStmt,
                SQLSRV_FETCH_ASSOC
            )
    ) {

        $products[] =
            $productRow;
    }

    sqlsrv_free_stmt(
        $productStmt
    );
}

/* =========================================================
   HEADER
   ========================================================= */

require_once __DIR__ . '/../includes/header.php';

/* =========================================================
   SIDEBAR
   ========================================================= */

require_once __DIR__ . '/../includes/sidebar.php';

?>

<style>

/* =========================================================
   PAGE VARIABLES
========================================================= */

.variant-edit-page {

    --v-bg: #f5f7fa;
    --v-card: #ffffff;
    --v-section: #f8fafc;
    --v-input: #ffffff;

    --v-border: #dce3ea;

    --v-text: #18212b;
    --v-body: #536273;
    --v-muted: #7b8794;

    --v-green: #10b981;
    --v-green-hover: #059669;

    --v-red: #dc2626;

    width: 100%;
    min-height: calc(100vh - 70px);

    background: var(--v-bg);

    color: var(--v-body);

    box-sizing: border-box;
}


/* =========================================================
   DARK THEME
========================================================= */

html.dark .variant-edit-page,
body.dark .variant-edit-page,
body.dark-mode .variant-edit-page,
body.dark-theme .variant-edit-page,
html[data-theme="dark"] .variant-edit-page,
body[data-theme="dark"] .variant-edit-page {

    --v-bg: #0a1119;
    --v-card: #111b26;
    --v-section: #0f1c29;
    --v-input: #0d1620;

    --v-border: #1e2d3d;

    --v-text: #f0f4f8;
    --v-body: #a8b8c8;
    --v-muted: #71869a;

    --v-green: #10b981;
    --v-green-hover: #059669;

    --v-red: #ef4444;
}


/* =========================================================
   LIGHT THEME
========================================================= */

html.light .variant-edit-page,
body.light .variant-edit-page,
body.light-mode .variant-edit-page,
body.light-theme .variant-edit-page,
html[data-theme="light"] .variant-edit-page,
body[data-theme="light"] .variant-edit-page {

    --v-bg: #f5f7fa;
    --v-card: #ffffff;
    --v-section: #f8fafc;
    --v-input: #ffffff;

    --v-border: #dce3ea;

    --v-text: #18212b;
    --v-body: #536273;
    --v-muted: #7b8794;
}


/* =========================================================
   CONTAINER
========================================================= */

.variant-container {

    width: 100%;

    max-width: 100%;

    margin: 0 auto;

    padding:
        24px
        24px
        70px;

    box-sizing: border-box;
}


/* =========================================================
   TOP
========================================================= */

.variant-top {

    display: flex;

    align-items: flex-end;

    justify-content: space-between;

    gap: 20px;

    margin-bottom: 25px;

    padding-bottom: 22px;

    border-bottom:
        1px solid
        var(--v-border);
}


/* =========================================================
   TITLE
========================================================= */

.variant-title-small {

    margin-bottom: 7px;

    color:
        var(--v-green);

    font-size: 12px;

    font-weight: 800;

    letter-spacing: 1px;

    text-transform: uppercase;
}


.variant-title {

    margin: 0;

    color:
        var(--v-text);

    font-size: 30px;

    font-weight: 800;

    line-height: 1.2;
}


.variant-subtitle {

    margin:
        7px
        0
        0;

    color:
        var(--v-muted);

    font-size: 14px;
}


/* =========================================================
   BUTTONS
========================================================= */

.variant-actions {

    display: flex;

    align-items: center;

    gap: 10px;
}


.v-btn {

    min-height: 43px;

    padding:
        0
        17px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    border-radius: 8px;

    font-family: inherit;

    font-size: 14px;

    font-weight: 800;

    text-decoration: none;

    cursor: pointer;

    box-sizing: border-box;

    transition:
        all .18s ease;
}


.v-btn-back {

    color:
        var(--v-text);

    background:
        var(--v-card);

    border:
        1px solid
        var(--v-border);
}


.v-btn-back:hover {

    transform:
        translateY(-1px);

    border-color:
        var(--v-muted);
}


.v-btn-save {

    color: #ffffff;

    background:
        var(--v-green);

    border:
        1px solid
        var(--v-green);
}


.v-btn-save:hover {

    background:
        var(--v-green-hover);

    border-color:
        var(--v-green-hover);

    box-shadow:
        0 6px 20px
        rgba(16, 185, 129, .20);
}


/* =========================================================
   ALERT
========================================================= */

.variant-alert {

    margin-bottom: 22px;

    padding:
        14px
        16px;

    display: flex;

    align-items: flex-start;

    gap: 10px;

    color:
        var(--v-red);

    background:
        rgba(239, 68, 68, .08);

    border:
        1px solid
        rgba(239, 68, 68, .25);

    border-radius: 9px;

    font-size: 14px;

    font-weight: 700;

    line-height: 1.5;
}


/* =========================================================
   MAIN GRID
========================================================= */

.variant-grid {

    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        minmax(320px, 360px);

    gap: 22px;

    align-items: start;
}


/* =========================================================
   CARD
========================================================= */

.v-card {

    margin-bottom: 24px;

    overflow: hidden;

    background:
        var(--v-card);

    border:
        1px solid
        var(--v-border);

    border-radius: 12px;

    box-shadow:
        0 7px 25px
        rgba(0, 0, 0, .055);
}


.v-card-header {

    padding:
        20px
        24px;

    background:
        var(--v-section);

    border-bottom:
        1px solid
        var(--v-border);
}


.v-card-title {

    margin: 0;

    color:
        var(--v-text);

    font-size: 17px;

    font-weight: 800;
}


.v-card-description {

    margin:
        5px
        0
        0;

    color:
        var(--v-muted);

    font-size: 12px;
}


.v-card-body {

    padding: 26px;
}


/* =========================================================
   FORM GRID
========================================================= */

.v-form-grid {

    display: grid;

    grid-template-columns:
        repeat(
            2,
            minmax(0, 1fr)
        );

    gap: 22px;
}


.v-field {

    min-width: 0;
}


.v-label {

    display: block;

    margin-bottom: 9px;

    color:
        var(--v-text);

    font-size: 12px;

    font-weight: 800;

    letter-spacing: .5px;

    text-transform: uppercase;
}


.required {

    color:
        var(--v-red);
}


/* =========================================================
   INPUT
========================================================= */

.v-input {

    width: 100%;

    min-height: 50px;

    padding:
        11px
        14px;

    box-sizing: border-box;

    color:
        var(--v-text);

    background:
        var(--v-input);

    border:
        1px solid
        var(--v-border);

    border-radius: 8px;

    outline: none;

    font-family: inherit;

    font-size: 14px;

    transition:
        border-color .18s ease,
        box-shadow .18s ease;
}


.v-input::placeholder {

    color:
        var(--v-muted);
}


.v-input:focus {

    border-color:
        var(--v-green);

    box-shadow:
        0 0 0 3px
        rgba(16, 185, 129, .10);
}


select.v-input {

    cursor: pointer;
}


input[type="number"].v-input {

    appearance: textfield;
}


textarea.v-input {

    min-height: 100px;

    resize: vertical;
}


/* =========================================================
   STATUS
========================================================= */

.status-box {

    min-height: 50px;

    padding:
        0
        13px;

    display: flex;

    align-items: center;

    box-sizing: border-box;

    background:
        var(--v-input);

    border:
        1px solid
        var(--v-border);

    border-radius: 8px;
}


.status-label {

    display: flex;

    align-items: center;

    gap: 10px;

    color:
        var(--v-text);

    font-size: 14px;

    font-weight: 700;

    cursor: pointer;
}


.status-checkbox {

    width: 18px;

    height: 18px;

    margin: 0;

    accent-color:
        var(--v-green);

    cursor: pointer;
}


/* =========================================================
   HELP
========================================================= */

.v-help {

    margin-top: 6px;

    color:
        var(--v-muted);

    font-size: 11px;

    line-height: 1.4;
}


/* =========================================================
   PRICE SUMMARY
========================================================= */

.price-summary {

    margin-top: 20px;

    padding: 16px;

    background:
        var(--v-section);

    border:
        1px solid
        var(--v-border);

    border-radius: 9px;
}


.price-row {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;

    margin-bottom: 11px;

    color:
        var(--v-body);

    font-size: 12px;
}


.price-row strong {

    color:
        var(--v-text);

    font-size: 14px;
}


.price-total {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;

    padding-top: 12px;

    color:
        var(--v-green);

    border-top:
        1px dashed
        var(--v-border);

    font-size: 15px;

    font-weight: 800;
}


/* =========================================================
   INFO
========================================================= */

.info-row {

    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 15px;

    padding:
        13px
        0;

    border-bottom:
        1px solid
        var(--v-border);
}


.info-row:last-child {

    border-bottom: 0;
}


.info-label {

    color:
        var(--v-muted);

    font-size: 12px;

    font-weight: 700;
}


.info-value {

    max-width: 60%;

    color:
        var(--v-text);

    font-size: 12px;

    font-weight: 800;

    text-align: right;

    word-break: break-word;
}


/* =========================================================
   BOTTOM ACTION
========================================================= */

.bottom-actions {

    display: flex;

    align-items: center;

    justify-content: flex-end;

    gap: 10px;

    margin-top: 24px;

    padding-top: 20px;

    border-top:
        1px solid
        var(--v-border);
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1250px) {

    .variant-grid {

        grid-template-columns: 1fr;
    }

    .variant-container {

        max-width: 100%;
    }
}


@media (max-width: 1100px) {

    .variant-grid {

        grid-template-columns: 1fr;
    }
}


@media (max-width: 760px) {

    .variant-container {

        padding:
            18px
            14px
            50px;
    }

    .variant-top {

        align-items: flex-start;

        flex-direction: column;
    }

    .variant-title {

        font-size: 26px;
    }

    .variant-actions {

        width: 100%;
    }

    .variant-actions .v-btn {

        flex: 1;
    }

    .v-form-grid {

        grid-template-columns: 1fr;
    }

    .v-card-body {

        padding: 18px;
    }
}


@media (max-width: 480px) {

    .variant-actions {

        flex-direction: column;
    }

    .variant-actions .v-btn {

        width: 100%;
    }

    .bottom-actions {

        flex-direction: column;
    }

    .bottom-actions .v-btn {

        width: 100%;
    }
}

</style>


<!-- =========================================================
     PAGE
========================================================= -->

<main class="main">

    <section class="content">

        <div class="variant-edit-page">

            <div class="variant-container">

                <!-- =================================================
                     HEADER
                ================================================== -->

                <div class="variant-top">

                    <div>

                        <div class="variant-title-small">
                            Variants / Edit
                        </div>

                        <h1 class="variant-title">
                            Edit Variant
                        </h1>

                        <p class="variant-subtitle">
                            Update variant information, specifications,
                            pricing and stock settings.
                        </p>

                    </div>


                    <div class="variant-actions">

                        <a
                            href="index.php"
                            class="v-btn v-btn-back"
                        >
                            ← Back
                        </a>

                        <button
                            type="submit"
                            form="variantEditForm"
                            class="v-btn v-btn-save"
                        >
                            ✓ Save Changes
                        </button>

                    </div>

                </div>


                <!-- =================================================
                     ERROR
                ================================================== -->

                <?php if ($actionMessage !== ''): ?>

                    <div class="variant-alert">

                        <span>
                            ⚠
                        </span>

                        <span>
                            <?= e($actionMessage) ?>
                        </span>

                    </div>

                <?php endif; ?>


                <!-- =================================================
                     FORM
                ================================================== -->

                <form
                    method="POST"
                    id="variantEditForm"
                    autocomplete="off"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($csrfToken) ?>"
                    >


                    <div class="variant-grid">


                        <!-- =================================================
                             LEFT SIDE
                        ================================================== -->

                        <div>


                            <!-- GENERAL -->
                            <div class="v-card">

                                <div class="v-card-header">

                                    <h2 class="v-card-title">
                                        General Information
                                    </h2>

                                    <p class="v-card-description">
                                        Product assignment and variant identification.
                                    </p>

                                </div>


                                <div class="v-card-body">

                                    <div class="v-form-grid">


                                        <!-- PRODUCT -->
                                        <div class="v-field">

                                            <label
                                                for="product_id"
                                                class="v-label"
                                            >
                                                Base Product
                                                <span class="required">*</span>
                                            </label>

                                            <select
                                                id="product_id"
                                                name="product_id"
                                                class="v-input"
                                                required
                                            >

                                                <option value="">
                                                    Select Product
                                                </option>

                                                <?php foreach (
                                                    $products
                                                    as $product
                                                ): ?>

                                                    <option
                                                        value="<?= (int)$product['ProductId'] ?>"
                                                        <?= (
                                                            (string)$product['ProductId'] ===
                                                            (string)$form['product_id']
                                                        )
                                                            ? 'selected'
                                                            : ''
                                                        ?>
                                                    >

                                                        <?= e(
                                                            $product['Name']
                                                        ) ?>

                                                    </option>

                                                <?php endforeach; ?>

                                            </select>

                                        </div>


                                        <!-- SKU -->
                                        <div class="v-field">

                                            <label
                                                for="sku"
                                                class="v-label"
                                            >
                                                SKU / Variant Code
                                                <span class="required">*</span>
                                            </label>

                                            <input
                                                type="text"
                                                id="sku"
                                                name="sku"
                                                class="v-input"
                                                maxlength="100"
                                                value="<?= e($form['sku']) ?>"
                                                placeholder="Example: TOWEL-WHITE-XL"
                                                required
                                            >

                                            <div class="v-help">
                                                SKU must be unique.
                                            </div>

                                        </div>


                                        <!-- BARCODE -->
                                        <div class="v-field">

                                            <label
                                                for="barcode"
                                                class="v-label"
                                            >
                                                Barcode / UPC
                                            </label>

                                            <input
                                                type="text"
                                                id="barcode"
                                                name="barcode"
                                                class="v-input"
                                                maxlength="100"
                                                value="<?= e($form['barcode']) ?>"
                                                placeholder="Enter barcode"
                                            >

                                        </div>


                                        <!-- STATUS -->
                                        <div class="v-field">

                                            <label class="v-label">
                                                Variant Status
                                            </label>

                                            <div class="status-box">

                                                <label
                                                    class="status-label"
                                                >

                                                    <input
                                                        type="checkbox"
                                                        name="is_active"
                                                        value="1"
                                                        class="status-checkbox"
                                                        <?= $form['is_active']
                                                            ? 'checked'
                                                            : ''
                                                        ?>
                                                    >

                                                    Active Variant

                                                </label>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>


                            <!-- ATTRIBUTES -->
                            <div class="v-card">

                                <div class="v-card-header">

                                    <h2 class="v-card-title">
                                        Attributes & Specifications
                                    </h2>

                                    <p class="v-card-description">
                                        Size, color, material and physical details.
                                    </p>

                                </div>


                                <div class="v-card-body">

                                    <div class="v-form-grid">


                                        <!-- SIZE -->
                                        <div class="v-field">

                                            <label
                                                for="size"
                                                class="v-label"
                                            >
                                                Size
                                            </label>

                                            <input
                                                type="text"
                                                id="size"
                                                name="size"
                                                class="v-input"
                                                maxlength="100"
                                                value="<?= e($form['size']) ?>"
                                                placeholder="Example: King / XL"
                                            >

                                        </div>


                                        <!-- COLOR -->
                                        <div class="v-field">

                                            <label
                                                for="color"
                                                class="v-label"
                                            >
                                                Color
                                            </label>

                                            <input
                                                type="text"
                                                id="color"
                                                name="color"
                                                class="v-input"
                                                maxlength="100"
                                                value="<?= e($form['color']) ?>"
                                                placeholder="Example: White"
                                            >

                                        </div>


                                        <!-- MATERIAL -->
                                        <div class="v-field">

                                            <label
                                                for="material"
                                                class="v-label"
                                            >
                                                Material
                                            </label>

                                            <input
                                                type="text"
                                                id="material"
                                                name="material"
                                                class="v-input"
                                                maxlength="150"
                                                value="<?= e($form['material']) ?>"
                                                placeholder="Example: 100% Cotton"
                                            >

                                        </div>


                                        <!-- THREAD -->
                                        <div class="v-field">

                                            <label
                                                for="thread_count"
                                                class="v-label"
                                            >
                                                Thread Count
                                            </label>

                                            <input
                                                type="text"
                                                id="thread_count"
                                                name="thread_count"
                                                class="v-input"
                                                maxlength="100"
                                                value="<?= e($form['thread_count']) ?>"
                                                placeholder="Example: 600 TC"
                                            >

                                        </div>


                                        <!-- GSM -->
                                        <div class="v-field">

                                            <label
                                                for="weight_gsm"
                                                class="v-label"
                                            >
                                                Weight (GSM)
                                            </label>

                                            <input
                                                type="number"
                                                id="weight_gsm"
                                                name="weight_gsm"
                                                class="v-input"
                                                min="0"
                                                step="0.01"
                                                value="<?= e($form['weight_gsm']) ?>"
                                                placeholder="Example: 550"
                                            >

                                        </div>


                                        <!-- DIMENSIONS -->
                                        <div class="v-field">

                                            <label
                                                for="dimensions"
                                                class="v-label"
                                            >
                                                Dimensions
                                            </label>

                                            <input
                                                type="text"
                                                id="dimensions"
                                                name="dimensions"
                                                class="v-input"
                                                maxlength="150"
                                                value="<?= e($form['dimensions']) ?>"
                                                placeholder="Example: 70 x 140 cm"
                                            >

                                        </div>

                                    </div>

                                </div>

                            </div>


                        </div>


                        <!-- =================================================
                             RIGHT SIDE
                        ================================================== -->

                        <div>


                            <!-- PRICING -->
                            <div class="v-card">

                                <div class="v-card-header">

                                    <h2 class="v-card-title">
                                        Pricing & Stock
                                    </h2>

                                    <p class="v-card-description">
                                        Configure variant prices and stock alert.
                                    </p>

                                </div>


                                <div class="v-card-body">


                                    <!-- SELLING -->
                                    <div class="v-field">

                                        <label
                                            for="price"
                                            class="v-label"
                                        >
                                            Selling Price
                                            <span class="required">*</span>
                                        </label>

                                        <input
                                            type="number"
                                            id="price"
                                            name="price"
                                            class="v-input"
                                            min="0"
                                            step="0.01"
                                            value="<?= e($form['price']) ?>"
                                            placeholder="0.00"
                                            required
                                        >

                                    </div>


                                    <br>


                                    <!-- COMPARE -->
                                    <div class="v-field">

                                        <label
                                            for="compare_at_price"
                                            class="v-label"
                                        >
                                            Compare At Price
                                        </label>

                                        <input
                                            type="number"
                                            id="compare_at_price"
                                            name="compare_at_price"
                                            class="v-input"
                                            min="0"
                                            step="0.01"
                                            value="<?= e($form['compare_at_price']) ?>"
                                            placeholder="0.00"
                                        >

                                    </div>


                                    <br>


                                    <!-- WHOLESALE -->
                                    <div class="v-field">

                                        <label
                                            for="wholesale_price"
                                            class="v-label"
                                        >
                                            Wholesale Price
                                        </label>

                                        <input
                                            type="number"
                                            id="wholesale_price"
                                            name="wholesale_price"
                                            class="v-input"
                                            min="0"
                                            step="0.01"
                                            value="<?= e($form['wholesale_price']) ?>"
                                            placeholder="0.00"
                                        >

                                    </div>


                                    <br>


                                    <!-- COST -->
                                    <div class="v-field">

                                        <label
                                            for="cost_price"
                                            class="v-label"
                                        >
                                            Cost Price
                                        </label>

                                        <input
                                            type="number"
                                            id="cost_price"
                                            name="cost_price"
                                            class="v-input"
                                            min="0"
                                            step="0.01"
                                            value="<?= e($form['cost_price']) ?>"
                                            placeholder="0.00"
                                        >

                                    </div>


                                    <br>


                                    <!-- WEIGHT + STOCK -->
                                    <div class="v-form-grid">

                                        <div class="v-field">

                                            <label
                                                for="weight_kg"
                                                class="v-label"
                                            >
                                                Weight (Kg)
                                            </label>

                                            <input
                                                type="number"
                                                id="weight_kg"
                                                name="weight_kg"
                                                class="v-input"
                                                min="0"
                                                step="0.001"
                                                value="<?= e($form['weight_kg']) ?>"
                                                placeholder="0.000"
                                            >

                                        </div>


                                        <div class="v-field">

                                            <label
                                                for="low_stock_threshold"
                                                class="v-label"
                                            >
                                                Low Stock Alert
                                            </label>

                                            <input
                                                type="number"
                                                id="low_stock_threshold"
                                                name="low_stock_threshold"
                                                class="v-input"
                                                min="0"
                                                step="1"
                                                value="<?= e($form['low_stock_threshold']) ?>"
                                            >

                                        </div>

                                    </div>


                                    <!-- PRICE SUMMARY -->
                                    <div class="price-summary">

                                        <div class="price-row">

                                            <span>
                                                Current Price
                                            </span>

                                            <strong>
                                                <?= number_format(
                                                    (float)(
                                                        isset($variantData['Price'])
                                                            ? $variantData['Price']
                                                            : 0
                                                    ),
                                                    2
                                                ) ?>
                                            </strong>

                                        </div>


                                        <div class="price-total">

                                            <span>
                                                Final Price
                                            </span>

                                            <span id="finalPrice">
                                                <?= number_format(
                                                    (float)(
                                                        $form['price'] !== ''
                                                            ? $form['price']
                                                            : 0
                                                    ),
                                                    2
                                                ) ?>
                                            </span>

                                        </div>

                                    </div>

                                </div>

                            </div>


                            <!-- INFORMATION -->
                            <div class="v-card">

                                <div class="v-card-header">

                                    <h2 class="v-card-title">
                                        Variant Information
                                    </h2>

                                </div>


                                <div class="v-card-body">


                                    <div class="info-row">

                                        <span class="info-label">
                                            Variant ID
                                        </span>

                                        <span class="info-value">
                                            #<?= (int)$variantData['VariantId'] ?>
                                        </span>

                                    </div>


                                    <div class="info-row">

                                        <span class="info-label">
                                            Product ID
                                        </span>

                                        <span class="info-value">
                                            #<?= (int)$variantData['ProductId'] ?>
                                        </span>

                                    </div>


                                    <div class="info-row">

                                        <span class="info-label">
                                            SKU
                                        </span>

                                        <span class="info-value">
                                            <?= e($variantData['SKU']) ?>
                                        </span>

                                    </div>


                                    <div class="info-row">

                                        <span class="info-label">
                                            Status
                                        </span>

                                        <span class="info-value">
                                            <?= !empty(
                                                $variantData['IsActive']
                                            )
                                                ? 'Active'
                                                : 'Inactive'
                                            ?>
                                        </span>

                                    </div>

                                </div>

                            </div>


                        </div>

                    </div>


                    <!-- =================================================
                         BOTTOM BUTTONS
                    ================================================== -->

                    <div class="bottom-actions">

                        <a
                            href="index.php"
                            class="v-btn v-btn-back"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="v-btn v-btn-save"
                        >
                            ✓ Update Variant
                        </button>

                    </div>


                </form>

            </div>

        </div>

    </section>

</main>


<script>

/* =========================================================
   GATEWAYLINEN VARIANT EDIT JS
========================================================= */

(function () {

    'use strict';

    /* =====================================================
       PRICE PREVIEW
    ===================================================== */

    var priceInput =
        document.getElementById('price');

    var finalPrice =
        document.getElementById('finalPrice');

    function updatePrice()
    {
        if (!priceInput || !finalPrice) {
            return;
        }

        var value =
            parseFloat(priceInput.value);

        if (isNaN(value)) {
            value = 0;
        }

        finalPrice.textContent =
            value.toFixed(2);
    }

    if (priceInput) {

        priceInput.addEventListener(
            'input',
            updatePrice
        );

        updatePrice();
    }


    /* =====================================================
       FORM VALIDATION
    ===================================================== */

    var form = document.getElementById('variantEditForm');

    function fieldValue(id) {
        var el = document.getElementById(id);
        return el ? el.value.trim() : '';
    }

    function addError(errors, message) {
        errors.push(message);
    }

    if (form) {
        form.addEventListener('submit', function (event) {
            var errors = [];

            var product = fieldValue('product_id');
            var sku = fieldValue('sku');
            var barcode = fieldValue('barcode');
            var size = fieldValue('size');
            var color = fieldValue('color');
            var threadCount = fieldValue('thread_count');
            var material = fieldValue('material');
            var weightGsm = fieldValue('weight_gsm');
            var dimensions = fieldValue('dimensions');
            var price = fieldValue('price');
            var compareAtPrice = fieldValue('compare_at_price');
            var wholesalePrice = fieldValue('wholesale_price');
            var costPrice = fieldValue('cost_price');
            var weightKg = fieldValue('weight_kg');
            var lowStock = fieldValue('low_stock_threshold');

            if (!product || parseInt(product, 10) <= 0) {
                addError(errors, 'Please select a valid Base Product.');
            }

            if (!sku) {
                addError(errors, 'SKU / Variant Code is required.');
            } else if (sku.length > 100) {
                addError(errors, 'SKU cannot be longer than 100 characters.');
            }

            if (barcode.length > 100) {
                addError(errors, 'Barcode cannot be longer than 100 characters.');
            }

            [
                ['Size', size, 100],
                ['Color', color, 100],
                ['Thread Count', threadCount, 100],
                ['Material', material, 150],
                ['Dimensions', dimensions, 150]
            ].forEach(function (item) {
                if (item[1].length > item[2]) {
                    addError(errors, item[0] + ' cannot be longer than ' + item[2] + ' characters.');
                }
            });

            if (!price) {
                addError(errors, 'Selling Price is required.');
            } else if (!/^(?:\d+(?:\.\d+)?|\.\d+)$/.test(price)) {
                addError(errors, 'Selling Price must be a valid number.');
            } else if (parseFloat(price) < 0) {
                addError(errors, 'Selling Price cannot be negative.');
            }

            function validateOptionalNumber(value, label) {
                if (value === '') return null;
                if (!/^(?:\d+(?:\.\d+)?|\.\d+)$/.test(value)) {
                    addError(errors, label + ' must be a valid number.');
                    return null;
                }
                var number = parseFloat(value);
                if (number < 0) {
                    addError(errors, label + ' cannot be negative.');
                }
                return number;
            }

            var priceNumber = price !== '' && isFinite(parseFloat(price)) ? parseFloat(price) : null;
            var compareNumber = validateOptionalNumber(compareAtPrice, 'Compare At Price');
            var wholesaleNumber = validateOptionalNumber(wholesalePrice, 'Wholesale Price');
            var costNumber = validateOptionalNumber(costPrice, 'Cost Price');
            var weightKgNumber = validateOptionalNumber(weightKg, 'Weight KG');
            var gsmNumber = validateOptionalNumber(weightGsm, 'Weight GSM');

            if (compareNumber !== null && priceNumber !== null && compareNumber < priceNumber) {
                addError(errors, 'Compare At Price should be greater than or equal to Selling Price.');
            }

            if (wholesaleNumber !== null && priceNumber !== null && wholesaleNumber > priceNumber) {
                addError(errors, 'Wholesale Price should not be greater than Selling Price.');
            }

            if (costNumber !== null && priceNumber !== null && costNumber > priceNumber) {
                addError(errors, 'Cost Price should not be greater than Selling Price.');
            }

            if (lowStock === '') {
                addError(errors, 'Low Stock Alert is required.');
            } else if (!/^\d+$/.test(lowStock)) {
                addError(errors, 'Low Stock Alert must be a whole number.');
            }

            if (errors.length > 0) {
                event.preventDefault();
                alert(errors.join('\n'));
                return false;
            }

            return true;
        });
    }

})();

</script>


<?php

/* =========================================================
   FOOTER
========================================================= */

require_once __DIR__ . '/../includes/footer.php';

?>