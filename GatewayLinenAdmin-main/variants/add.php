<?php

session_start();

/*
|--------------------------------------------------------------------------
| GatewayLinen Admin - Add Product Variant
|--------------------------------------------------------------------------
| File:
| GatewayLinenadmin/variants/add.php
|
| Same UI / same fields / same workflow.
| Validation enhanced:
| - CSRF
| - Product validation
| - SKU validation + uniqueness
| - Barcode validation + uniqueness
| - Attribute validation
| - Numeric validation
| - Price relationship validation
| - Weight validation
| - Stock threshold validation
| - Duplicate submit protection
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

$activeMenu = "variants";
$pageTitle  = "GatewayLinen | Add Product Variant";


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
| DATABASE ERROR HELPER
|--------------------------------------------------------------------------
*/

function dbErrorMessage($fallback = "Database operation failed.")
{
    $errors = sqlsrv_errors();

    if (!empty($errors)) {
        foreach ($errors as $error) {
            if (!empty($error["message"])) {
                return $error["message"];
            }
        }
    }

    return $fallback;
}


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION["variant_csrf_token"])) {
    $_SESSION["variant_csrf_token"] =
        bin2hex(random_bytes(32));
}

$csrfToken =
    $_SESSION["variant_csrf_token"];


/*
|--------------------------------------------------------------------------
| FORM VARIABLES
|--------------------------------------------------------------------------
*/

$productId         = "";
$productName       = "";

$sku               = "";
$barcode           = "";
$size              = "";
$color             = "";
$threadCount       = "";
$material          = "";
$weightGsm         = "";
$dimensions        = "";

$price             = "";
$compareAtPrice    = "";
$wholesalePrice    = "";
$costPrice         = "";
$weightKg          = "";
$lowStockThreshold = "5";
$isActive          = 1;

$actionMessage = "";
$actionType    = "";

$validationField = "";


/*
|--------------------------------------------------------------------------
| FETCH PRODUCTS
|--------------------------------------------------------------------------
*/

$products = [];

$prodSql = "
    SELECT
        ProductId,
        Name
    FROM dbo.Products
    ORDER BY Name ASC
";

$prodStmt = sqlsrv_query(
    $conn,
    $prodSql
);

if ($prodStmt !== false) {

    while (
        $row = sqlsrv_fetch_array(
            $prodStmt,
            SQLSRV_FETCH_ASSOC
        )
    ) {
        $products[] = $row;
    }

    sqlsrv_free_stmt($prodStmt);

} else {

    $actionMessage =
        dbErrorMessage("Unable to load products.");

    $actionType = "error";
}


/*
|--------------------------------------------------------------------------
| AJAX - AUTO SKU
|--------------------------------------------------------------------------
*/

if (
    isset($_GET["generate_sku"]) &&
    $_GET["generate_sku"] === "1"
) {

    header(
        "Content-Type: application/json; charset=UTF-8"
    );

    $ajaxProductId =
        isset($_GET["product_id"])
            ? (int)$_GET["product_id"]
            : 0;

    $ajaxSize = trim(
        $_GET["size"] ?? ""
    );

    $ajaxColor = trim(
        $_GET["color"] ?? ""
    );

    if ($ajaxProductId <= 0) {

        echo json_encode([
            "success" => false,
            "message" => "Please select a product first."
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Product lookup
    |--------------------------------------------------------------------------
    */

    $productSql = "
        SELECT TOP 1
            ProductId,
            Name
        FROM dbo.Products
        WHERE ProductId = ?
    ";

    $productStmt = sqlsrv_query(
        $conn,
        $productSql,
        [$ajaxProductId]
    );

    if ($productStmt === false) {

        echo json_encode([
            "success" => false,
            "message" => "Unable to find selected product."
        ]);

        exit;
    }


    $productRow = sqlsrv_fetch_array(
        $productStmt,
        SQLSRV_FETCH_ASSOC
    );

    sqlsrv_free_stmt($productStmt);


    if (!$productRow) {

        echo json_encode([
            "success" => false,
            "message" => "Selected product does not exist."
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | SKU part helper
    |--------------------------------------------------------------------------
    */

    $makeSkuPart = function (
        $value,
        $fallback = ""
    ) {

        $value = strtoupper(
            trim((string)$value)
        );

        $value = preg_replace(
            "/[^A-Z0-9]+/",
            "-",
            $value
        );

        $value = trim(
            $value,
            "-"
        );

        if ($value === "") {
            $value = $fallback;
        }

        return substr(
            $value,
            0,
            20
        );
    };


    $productPart = $makeSkuPart(
        $productRow["Name"] ?? "",
        "PRODUCT"
    );

    $sizePart = $makeSkuPart(
        $ajaxSize,
        ""
    );

    $colorPart = $makeSkuPart(
        $ajaxColor,
        ""
    );


    $skuParts = [
        $productPart
    ];

    if ($sizePart !== "") {
        $skuParts[] = $sizePart;
    }

    if ($colorPart !== "") {
        $skuParts[] = $colorPart;
    }


    /*
    |--------------------------------------------------------------------------
    | Unique random section
    |--------------------------------------------------------------------------
    */

    try {

        $uniquePart = strtoupper(
            substr(
                bin2hex(
                    random_bytes(3)
                ),
                0,
                6
            )
        );

    } catch (Throwable $e) {

        $uniquePart = strtoupper(
            substr(
                md5(
                    uniqid(
                        "",
                        true
                    )
                ),
                0,
                6
            )
        );
    }


    $baseSku =
        implode(
            "-",
            $skuParts
        )
        . "-"
        . $uniquePart;


    $generatedSku =
        $baseSku;


    /*
    |--------------------------------------------------------------------------
    | Ensure generated SKU is unique
    |--------------------------------------------------------------------------
    */

    for ($i = 0; $i < 20; $i++) {

        $checkSql = "
            SELECT TOP 1
                ProductVariantId
            FROM dbo.ProductVariants
            WHERE SKU = ?
        ";

        $checkStmt = sqlsrv_query(
            $conn,
            $checkSql,
            [$generatedSku]
        );

        if ($checkStmt === false) {
            break;
        }


        $exists = sqlsrv_fetch_array(
            $checkStmt,
            SQLSRV_FETCH_ASSOC
        );

        sqlsrv_free_stmt($checkStmt);


        if (!$exists) {
            break;
        }


        try {

            $newRandom = strtoupper(
                substr(
                    bin2hex(
                        random_bytes(4)
                    ),
                    0,
                    8
                )
            );

        } catch (Throwable $e) {

            $newRandom = strtoupper(
                substr(
                    md5(
                        uniqid(
                            "",
                            true
                        )
                    ),
                    0,
                    8
                )
            );
        }


        $generatedSku =
            implode(
                "-",
                $skuParts
            )
            . "-"
            . $newRandom;
    }


    echo json_encode([
        "success" => true,
        "sku"     => $generatedSku
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| AJAX - CHECK SKU
|--------------------------------------------------------------------------
*/

if (
    isset($_GET["check_sku"]) &&
    $_GET["check_sku"] === "1"
) {

    header(
        "Content-Type: application/json; charset=UTF-8"
    );

    $checkSku = strtoupper(
        trim(
            $_GET["sku"] ?? ""
        )
    );


    if ($checkSku === "") {

        echo json_encode([
            "exists" => false
        ]);

        exit;
    }


    $sql = "
        SELECT TOP 1
            ProductVariantId
        FROM dbo.ProductVariants
        WHERE SKU = ?
    ";

    $stmt = sqlsrv_query(
        $conn,
        $sql,
        [$checkSku]
    );


    if ($stmt === false) {

        echo json_encode([
            "exists" => false,
            "error"  => true
        ]);

        exit;
    }


    $row = sqlsrv_fetch_array(
        $stmt,
        SQLSRV_FETCH_ASSOC
    );

    sqlsrv_free_stmt($stmt);


    echo json_encode([
        "exists" => $row ? true : false
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| SAVE VARIANT
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
        empty($_SESSION["variant_csrf_token"]) ||
        !hash_equals(
            $_SESSION["variant_csrf_token"],
            $postedToken
        )
    ) {

        $actionMessage =
            "Security verification failed. Please refresh the page and try again.";

        $actionType = "error";
    }


    /*
    |--------------------------------------------------------------------------
    | Read POST values
    |--------------------------------------------------------------------------
    */

    if ($actionMessage === "") {

        $productId =
            isset($_POST["product_id"])
                ? (int)$_POST["product_id"]
                : 0;

        $sku = strtoupper(
            trim(
                $_POST["sku"] ?? ""
            )
        );

        $barcode = trim(
            $_POST["barcode"] ?? ""
        );

        $size = trim(
            $_POST["size"] ?? ""
        );

        $color = trim(
            $_POST["color"] ?? ""
        );

        $threadCount = trim(
            $_POST["thread_count"] ?? ""
        );

        $material = trim(
            $_POST["material"] ?? ""
        );

        $weightGsm = trim(
            $_POST["weight_gsm"] ?? ""
        );

        $dimensions = trim(
            $_POST["dimensions"] ?? ""
        );

        $price = trim(
            $_POST["price"] ?? ""
        );

        $compareAtPrice = trim(
            $_POST["compare_at_price"] ?? ""
        );

        $wholesalePrice = trim(
            $_POST["wholesale_price"] ?? ""
        );

        $costPrice = trim(
            $_POST["cost_price"] ?? ""
        );

        $weightKg = trim(
            $_POST["weight_kg"] ?? ""
        );

        $lowStockThreshold = trim(
            $_POST["low_stock_threshold"] ?? "5"
        );

        $isActive =
            isset($_POST["is_active"])
                ? 1
                : 0;
    }


    /*
    |--------------------------------------------------------------------------
    | PRODUCT VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $actionMessage === "" &&
        $productId <= 0
    ) {

        $actionMessage =
            "Please select a base product.";

        $actionType = "error";
        $validationField = "product";
    }


    /*
    |--------------------------------------------------------------------------
    | SKU REQUIRED
    |--------------------------------------------------------------------------
    */

    if (
        $actionMessage === "" &&
        $sku === ""
    ) {

        $actionMessage =
            "SKU / Variant Code is required.";

        $actionType = "error";
        $validationField = "sku";
    }


    /*
    |--------------------------------------------------------------------------
    | SKU LENGTH
    |--------------------------------------------------------------------------
    */

    if (
        $actionMessage === "" &&
        mb_strlen($sku) > 100
    ) {

        $actionMessage =
            "SKU cannot be longer than 100 characters.";

        $actionType = "error";
        $validationField = "sku";
    }


    /*
    |--------------------------------------------------------------------------
    | SKU FORMAT
    |--------------------------------------------------------------------------
    */

    if (
        $actionMessage === "" &&
        !preg_match(
            "/^[A-Z0-9_-]+$/",
            $sku
        )
    ) {

        $actionMessage =
            "SKU can contain only letters, numbers, hyphen (-) and underscore (_).";

        $actionType = "error";
        $validationField = "sku";
    }


    /*
    |--------------------------------------------------------------------------
    | PRODUCT EXISTS
    |--------------------------------------------------------------------------
    */

    if (
        $actionMessage === "" &&
        $productId > 0
    ) {

        $productCheckSql = "
            SELECT TOP 1
                ProductId,
                Name
            FROM dbo.Products
            WHERE ProductId = ?
        ";

        $productCheckStmt = sqlsrv_query(
            $conn,
            $productCheckSql,
            [$productId]
        );


        if ($productCheckStmt === false) {

            $actionMessage =
                dbErrorMessage(
                    "Unable to validate product."
                );

            $actionType = "error";

        } else {

            $validProduct =
                sqlsrv_fetch_array(
                    $productCheckStmt,
                    SQLSRV_FETCH_ASSOC
                );

            sqlsrv_free_stmt(
                $productCheckStmt
            );


            if (!$validProduct) {

                $actionMessage =
                    "The selected product is invalid.";

                $actionType = "error";
                $validationField = "product";

            } else {

                $productName =
                    $validProduct["Name"] ?? "";
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | BARCODE LENGTH
    |--------------------------------------------------------------------------
    */

    if (
        $actionMessage === "" &&
        mb_strlen($barcode) > 100
    ) {

        $actionMessage =
            "Barcode cannot be longer than 100 characters.";

        $actionType = "error";
        $validationField = "barcode";
    }


    /*
    |--------------------------------------------------------------------------
    | BARCODE FORMAT
    |--------------------------------------------------------------------------
    */

    if (
        $actionMessage === "" &&
        $barcode !== "" &&
        !preg_match(
            "/^[A-Za-z0-9_-]+$/",
            $barcode
        )
    ) {

        $actionMessage =
            "Barcode can contain only letters, numbers, hyphen (-) and underscore (_).";

        $actionType = "error";
        $validationField = "barcode";
    }


    /*
    |--------------------------------------------------------------------------
    | ATTRIBUTE LENGTH VALIDATION
    |--------------------------------------------------------------------------
    */

    $attributeLimits = [

        "size" => [
            $size,
            100,
            "Size"
        ],

        "color" => [
            $color,
            100,
            "Color"
        ],

        "thread_count" => [
            $threadCount,
            100,
            "Thread Count"
        ],

        "material" => [
            $material,
            150,
            "Material"
        ],

        "weight_gsm" => [
            $weightGsm,
            100,
            "Weight GSM"
        ],

        "dimensions" => [
            $dimensions,
            150,
            "Dimensions"
        ]
    ];


    if ($actionMessage === "") {

        foreach (
            $attributeLimits
            as $field => $attribute
        ) {

            if (
                mb_strlen($attribute[0])
                >
                $attribute[1]
            ) {

                $actionMessage =
                    $attribute[2]
                    . " cannot be longer than "
                    . $attribute[1]
                    . " characters.";

                $actionType = "error";

                $validationField =
                    $field;

                break;
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | THREAD COUNT VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $actionMessage === "" &&
        $threadCount !== ""
    ) {

        if (
            !preg_match(
                "/^[0-9]+(?:\s*TC)?$/i",
                $threadCount
            )
        ) {

            $actionMessage =
                "Thread Count must contain a valid number, for example 400 or 400 TC.";

            $actionType = "error";
            $validationField = "threadCount";
        }
    }


    /*
    |--------------------------------------------------------------------------
    | GSM VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $actionMessage === "" &&
        $weightGsm !== ""
    ) {

        if (
            !preg_match(
                "/^[0-9]+(?:\.[0-9]+)?(?:\s*GSM)?$/i",
                $weightGsm
            )
        ) {

            $actionMessage =
                "Weight GSM must contain a valid number, for example 600 or 600 GSM.";

            $actionType = "error";
            $validationField = "weightGsm";
        }
    }


    /*
    |--------------------------------------------------------------------------
    | SELLING PRICE REQUIRED
    |--------------------------------------------------------------------------
    */

    if (
        $actionMessage === "" &&
        $price === ""
    ) {

        $actionMessage =
            "Selling price is required.";

        $actionType = "error";
        $validationField = "price";
    }


    /*
    |--------------------------------------------------------------------------
    | SELLING PRICE NUMERIC
    |--------------------------------------------------------------------------
    */

    if (
        $actionMessage === "" &&
        !is_numeric($price)
    ) {

        $actionMessage =
            "Selling price must be a valid number.";

        $actionType = "error";
        $validationField = "price";
    }


    /*
    |--------------------------------------------------------------------------
    | SELLING PRICE NEGATIVE / ZERO
    |--------------------------------------------------------------------------
    */

    if (
        $actionMessage === "" &&
        (float)$price <= 0
    ) {

        $actionMessage =
            "Selling price must be greater than 0.";

        $actionType = "error";
        $validationField = "price";
    }


    /*
    |--------------------------------------------------------------------------
    | OPTIONAL PRICES
    |--------------------------------------------------------------------------
    */

    $optionalPrices = [

        "compare_at_price" => [
            $compareAtPrice,
            "Compare At Price",
            "compareAtPrice"
        ],

        "wholesale_price" => [
            $wholesalePrice,
            "Wholesale Price",
            "wholesalePrice"
        ],

        "cost_price" => [
            $costPrice,
            "Cost Price",
            "costPrice"
        ]
    ];


    if ($actionMessage === "") {

        foreach (
            $optionalPrices
            as $item
        ) {

            if (
                $item[0] !== "" &&
                !is_numeric($item[0])
            ) {

                $actionMessage =
                    $item[1]
                    . " must be a valid number.";

                $actionType = "error";
                $validationField = $item[2];

                break;
            }


            if (
                $item[0] !== "" &&
                (float)$item[0] < 0
            ) {

                $actionMessage =
                    $item[1]
                    . " cannot be negative.";

                $actionType = "error";
                $validationField = $item[2];

                break;
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | PRICE RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    if (
        $actionMessage === "" &&
        $compareAtPrice !== "" &&
        (float)$compareAtPrice < (float)$price
    ) {

        $actionMessage =
            "Compare At Price should be greater than or equal to Selling Price.";

        $actionType = "error";
        $validationField = "compareAtPrice";
    }


    if (
        $actionMessage === "" &&
        $wholesalePrice !== "" &&
        (float)$wholesalePrice > (float)$price
    ) {

        $actionMessage =
            "Wholesale Price should normally not be greater than Selling Price.";

        $actionType = "error";
        $validationField = "wholesalePrice";
    }


    if (
        $actionMessage === "" &&
        $costPrice !== "" &&
        (float)$costPrice > (float)$price
    ) {

        $actionMessage =
            "Cost Price cannot be greater than Selling Price.";

        $actionType = "error";
        $validationField = "costPrice";
    }


    /*
    |--------------------------------------------------------------------------
    | WEIGHT KG
    |--------------------------------------------------------------------------
    */

    if (
        $actionMessage === "" &&
        $weightKg !== "" &&
        !is_numeric($weightKg)
    ) {

        $actionMessage =
            "Weight (Kg) must be a valid number.";

        $actionType = "error";
        $validationField = "weightKg";
    }


    if (
        $actionMessage === "" &&
        $weightKg !== "" &&
        (float)$weightKg < 0
    ) {

        $actionMessage =
            "Weight (Kg) cannot be negative.";

        $actionType = "error";
        $validationField = "weightKg";
    }


    /*
    |--------------------------------------------------------------------------
    | LOW STOCK THRESHOLD
    |--------------------------------------------------------------------------
    */

    if (
        $actionMessage === "" &&
        (
            $lowStockThreshold === "" ||
            !preg_match(
                "/^[0-9]+$/",
                $lowStockThreshold
            )
        )
    ) {

        $actionMessage =
            "Low stock threshold must be a whole number.";

        $actionType = "error";
        $validationField = "lowStockThreshold";
    }


    if (
        $actionMessage === "" &&
        (int)$lowStockThreshold < 0
    ) {

        $actionMessage =
            "Low stock threshold cannot be negative.";

        $actionType = "error";
        $validationField = "lowStockThreshold";
    }


    /*
    |--------------------------------------------------------------------------
    | SKU DUPLICATE
    |--------------------------------------------------------------------------
    */

    if (
        $actionMessage === "" &&
        $sku !== ""
    ) {

        $skuCheckSql = "
            SELECT TOP 1
                ProductVariantId
            FROM dbo.ProductVariants
            WHERE SKU = ?
        ";

        $skuCheckStmt = sqlsrv_query(
            $conn,
            $skuCheckSql,
            [$sku]
        );


        if ($skuCheckStmt === false) {

            $actionMessage =
                dbErrorMessage(
                    "Unable to check SKU."
                );

            $actionType = "error";

        } else {

            $existingSku =
                sqlsrv_fetch_array(
                    $skuCheckStmt,
                    SQLSRV_FETCH_ASSOC
                );

            sqlsrv_free_stmt(
                $skuCheckStmt
            );


            if ($existingSku) {

                $actionMessage =
                    "This SKU already exists. Please use another SKU or click Auto Generate.";

                $actionType = "error";
                $validationField = "sku";
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | BARCODE DUPLICATE
    |--------------------------------------------------------------------------
    */

    if (
        $actionMessage === "" &&
        $barcode !== ""
    ) {

        $barcodeCheckSql = "
            SELECT TOP 1
                ProductVariantId
            FROM dbo.ProductVariants
            WHERE Barcode = ?
        ";

        $barcodeCheckStmt = sqlsrv_query(
            $conn,
            $barcodeCheckSql,
            [$barcode]
        );


        if ($barcodeCheckStmt === false) {

            $actionMessage =
                dbErrorMessage(
                    "Unable to check barcode."
                );

            $actionType = "error";

        } else {

            $existingBarcode =
                sqlsrv_fetch_array(
                    $barcodeCheckStmt,
                    SQLSRV_FETCH_ASSOC
                );

            sqlsrv_free_stmt(
                $barcodeCheckStmt
            );


            if ($existingBarcode) {

                $actionMessage =
                    "This barcode already exists. Please use another barcode.";

                $actionType = "error";
                $validationField = "barcode";
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | PREPARE DATABASE VALUES
    |--------------------------------------------------------------------------
    */

    if ($actionMessage === "") {

        $dbPrice =
            round(
                (float)$price,
                2
            );

        $dbCompareAtPrice =
            $compareAtPrice !== ""
                ? round(
                    (float)$compareAtPrice,
                    2
                )
                : null;

        $dbWholesalePrice =
            $wholesalePrice !== ""
                ? round(
                    (float)$wholesalePrice,
                    2
                )
                : null;

        $dbCostPrice =
            $costPrice !== ""
                ? round(
                    (float)$costPrice,
                    2
                )
                : null;

        $dbWeightKg =
            $weightKg !== ""
                ? round(
                    (float)$weightKg,
                    3
                )
                : null;

        $dbLowStockThreshold =
            (int)$lowStockThreshold;
    }


    /*
    |--------------------------------------------------------------------------
    | INSERT
    |--------------------------------------------------------------------------
    */

    if ($actionMessage === "") {

        $insSql = "
            INSERT INTO dbo.ProductVariants
            (
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

            $productId,

            $sku,

            $barcode !== ""
                ? $barcode
                : null,

            $size !== ""
                ? $size
                : null,

            $color !== ""
                ? $color
                : null,

            $threadCount !== ""
                ? $threadCount
                : null,

            $material !== ""
                ? $material
                : null,

            $weightGsm !== ""
                ? $weightGsm
                : null,

            $dimensions !== ""
                ? $dimensions
                : null,

            $dbPrice,

            $dbCompareAtPrice,

            $dbWholesalePrice,

            $dbCostPrice,

            $dbWeightKg,

            $dbLowStockThreshold,

            $isActive
        ];


        $insStmt = sqlsrv_query(
            $conn,
            $insSql,
            $params
        );


        if ($insStmt !== false) {

            sqlsrv_free_stmt(
                $insStmt
            );


            /*
            |--------------------------------------------------------------------------
            | New CSRF token after successful insert
            |--------------------------------------------------------------------------
            */

            $_SESSION["variant_csrf_token"] =
                bin2hex(
                    random_bytes(32)
                );


            header(
                "Location: index.php?msg=added"
            );

            exit;

        } else {

            $dbMessage =
                dbErrorMessage(
                    "Failed to add variant."
                );


            /*
            |--------------------------------------------------------------------------
            | Handle DB duplicate errors safely
            |--------------------------------------------------------------------------
            */

            $lowerDbMessage =
                strtolower($dbMessage);


            if (
                strpos(
                    $lowerDbMessage,
                    "unique"
                ) !== false ||
                strpos(
                    $lowerDbMessage,
                    "duplicate"
                ) !== false
            ) {

                $actionMessage =
                    "SKU or Barcode already exists. Please use a unique value.";

            } else {

                $actionMessage =
                    $dbMessage;
            }


            $actionType = "error";
        }
    }
}


/*
|--------------------------------------------------------------------------
| HEADER & SIDEBAR
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../includes/header.php";
require_once __DIR__ . "/../includes/sidebar.php";

?>

<style>

/*
|--------------------------------------------------------------------------
| THEME & COMPONENTS
|--------------------------------------------------------------------------
*/

:root {

    --bg-page: #f8fafc;
    --bg-card: #ffffff;
    --bg-section: #f1f5f9;
    --bg-input: #ffffff;
    --bg-hover: #e2e8f0;

    --border: #cbd5e1;
    --border-soft: #e2e8f0;

    --text-hi: #0f172a;
    --text-body: #334155;
    --text-mute: #64748b;

    --green: #10b981;
    --green-hover: #059669;
    --green-soft: rgba(16,185,129,.12);

    --blue: #3b82f6;
    --blue-soft: rgba(59,130,246,.12);

    --cyan: #06b6d4;
    --cyan-soft: rgba(6,182,212,.12);

    --purple: #8b5cf6;
    --purple-soft: rgba(139,92,246,.12);

    --gold: #f59e0b;
    --gold-soft: rgba(245,158,11,.12);

    --red: #ef4444;
    --red-soft: rgba(239,68,68,.12);

    --radius: 10px;
}


body.dark-theme,
body[data-theme="dark"],
[data-theme="dark"] {

    --bg-page: #0a1119;
    --bg-card: #111b26;
    --bg-section: #0f1c29;
    --bg-input: #0d1620;
    --bg-hover: #16222e;

    --border: #1e2d3d;
    --border-soft: #182636;

    --text-hi: #f0f4f8;
    --text-body: #a8b8c8;
    --text-mute: #5f7488;
}


html,
body {

    background: var(--bg-page) !important;
    color: var(--text-body) !important;
}


.main,
.content {

    background: var(--bg-page) !important;
    color: var(--text-body);
}


.add-variant-page {

    width: 100%;
    max-width: 1180px;
    margin: 0 auto;

    padding:
        8px
        20px
        40px;

    box-sizing: border-box;
}


.add-variant-header {

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 15px;

    margin-bottom: 14px;

    padding-bottom: 10px;

    border-bottom:
        1px solid
        var(--border);
}


.add-variant-breadcrumb {

    display: flex;
    align-items: center;
    flex-wrap: wrap;

    gap: 5px;

    margin-bottom: 3px;

    color: var(--text-mute);

    font-size: 9px;
    font-weight: 800;

    letter-spacing: .5px;

    text-transform: uppercase;
}


.add-variant-breadcrumb .current {

    color: var(--green);
}


.add-variant-title {

    margin: 0;

    color: var(--text-hi);

    font-size: 22px;

    line-height: 1.2;

    font-weight: 850;

    letter-spacing: -.5px;
}


.add-variant-subtitle {

    margin:
        3px
        0
        0;

    color: var(--text-mute);

    font-size: 11px;

    line-height: 1.4;
}


.add-variant-back {

    display: inline-flex;

    align-items: center;
    justify-content: center;

    gap: 6px;

    min-height: 34px;

    padding:
        0
        13px;

    border:
        1px solid
        var(--border);

    border-radius: 8px;

    background:
        var(--bg-input);

    color:
        var(--text-body) !important;

    font-size: 10.5px;

    font-weight: 750;

    text-decoration: none;

    white-space: nowrap;

    transition:
        .2s
        ease;
}


.add-variant-back:hover {

    border-color:
        var(--green);

    background:
        var(--green-soft);

    color:
        var(--green) !important;

    transform:
        translateY(-1px);
}


.variant-alert {

    display: flex;

    align-items: flex-start;

    gap: 10px;

    margin-bottom: 14px;

    padding:
        10px
        13px;

    border:
        1px solid
        rgba(239,68,68,.3);

    border-left:
        4px solid
        var(--red);

    border-radius: 8px;

    background:
        var(--red-soft);

    color:
        var(--text-hi);

    font-size: 11.5px;

    font-weight: 650;

    line-height: 1.5;
}


.variant-alert-icon {

    display: flex;

    align-items: center;
    justify-content: center;

    width: 20px;
    height: 20px;

    flex:
        0 0
        20px;

    border-radius: 50%;

    background:
        rgba(239,68,68,.2);

    color:
        var(--red);

    font-weight: 900;
}


.variant-form {

    width: 100%;
}


.variant-section {

    width: 100%;

    margin-bottom: 14px;

    background:
        var(--bg-card);

    border:
        1px solid
        var(--border);

    border-radius: 12px;

    overflow: hidden;

    box-shadow:
        0 10px 30px
        rgba(0,0,0,.08);
}


.variant-section-header {

    display: flex;

    align-items: center;

    gap: 10px;

    padding:
        10px
        15px;

    border-bottom:
        1px solid
        var(--border);

    background:
        var(--bg-section);
}


.variant-section-icon {

    display: flex;

    align-items: center;
    justify-content: center;

    width: 25px;
    height: 25px;

    flex:
        0 0
        25px;

    border-radius: 7px;

    font-size: 11px;

    font-weight: 900;
}


.icon-green {

    background:
        var(--green-soft);

    color:
        var(--green);
}


.icon-blue {

    background:
        var(--blue-soft);

    color:
        var(--blue);
}


.icon-cyan {

    background:
        var(--cyan-soft);

    color:
        var(--cyan);
}


.icon-gold {

    background:
        var(--gold-soft);

    color:
        var(--gold);
}


.variant-section-title {

    color:
        var(--text-hi);

    font-size: 11px;

    font-weight: 850;

    letter-spacing: .7px;

    text-transform: uppercase;
}


.variant-section-subtitle {

    margin-left: auto;

    color:
        var(--text-mute);

    font-size: 9px;

    font-weight: 600;
}


.variant-section-body {

    padding:
        16px
        18px;
}


.variant-grid-2 {

    display: grid;

    grid-template-columns:
        repeat(
            2,
            minmax(
                0,
                1fr
            )
        );

    gap:
        15px
        22px;
}


.variant-grid-3 {

    display: grid;

    grid-template-columns:
        repeat(
            3,
            minmax(
                0,
                1fr
            )
        );

    gap:
        15px
        18px;
}


.variant-form-group {

    min-width: 0;
}


.variant-form-label {

    display: flex;

    align-items: center;
    justify-content: space-between;

    gap: 10px;

    margin-bottom: 5px;

    color:
        var(--text-body);

    font-size: 10px;

    font-weight: 800;

    letter-spacing: .35px;

    text-transform: uppercase;
}


.variant-required {

    color:
        var(--red);

    margin-left: 2px;
}


.shortcut-badge {

    flex:
        0 0
        auto;

    padding:
        2px
        5px;

    border:
        1px solid
        rgba(16,185,129,.3);

    border-radius: 4px;

    background:
        var(--green-soft);

    color:
        var(--green);

    font-family:
        monospace;

    font-size: 8.5px;

    text-transform: none;

    letter-spacing: 0;
}


.variant-input,
.product-select-trigger {

    width: 100%;

    height: 40px;

    box-sizing: border-box;

    padding:
        0
        11px;

    border:
        1px solid
        var(--border);

    border-radius: 8px;

    outline: none;

    background:
        var(--bg-input);

    color:
        var(--text-hi);

    font-family: inherit;

    font-size: 13px;

    font-weight: 500;

    display: flex;

    align-items: center;

    text-decoration: none;

    transition:
        border-color .18s ease,
        box-shadow .18s ease,
        background .18s ease;
}


.variant-input:focus {

    border-color:
        var(--green);

    box-shadow:
        0 0 0
        3px
        rgba(16,185,129,.12);
}


.variant-input.validation-error {

    border-color:
        var(--red) !important;

    box-shadow:
        0 0 0
        3px
        rgba(239,68,68,.10) !important;
}


.product-select-trigger {

    justify-content:
        space-between;

    cursor:
        pointer;
}


.product-select-trigger:hover {

    border-color:
        var(--green);

    background:
        var(--green-soft);
}


.sku-row {

    display: grid;

    grid-template-columns:
        minmax(0,1fr)
        auto;

    gap: 7px;
}


.sku-input {

    font-family:
        monospace;

    font-weight:
        750;

    letter-spacing:
        .4px;

    text-transform:
        uppercase;
}


.auto-sku-btn {

    height: 40px;

    padding:
        0
        12px;

    border:
        1px solid
        rgba(16,185,129,.35);

    border-radius: 8px;

    background:
        var(--green-soft);

    color:
        var(--green);

    font-size: 9.5px;

    font-weight: 850;

    cursor:
        pointer;

    white-space:
        nowrap;

    transition:
        .18s
        ease;
}


.auto-sku-btn:hover {

    border-color:
        var(--green);

    background:
        rgba(16,185,129,.18);

    transform:
        translateY(-1px);
}


.auto-sku-btn:disabled {

    opacity:
        .65;

    cursor:
        wait;

    transform:
        none;
}


.field-note {

    margin-top: 4px;

    color:
        var(--text-mute);

    font-size: 9px;

    line-height: 1.4;
}


.field-note strong {

    color:
        var(--green);
}


.status-box {

    display: flex;

    align-items: center;

    min-height: 40px;

    box-sizing:
        border-box;

    padding:
        0
        11px;

    border:
        1px solid
        var(--border);

    border-radius: 8px;

    background:
        var(--bg-input);
}


.status-label {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    color:
        var(--text-body);

    font-size: 11px;

    font-weight: 650;

    cursor:
        pointer;

    user-select:
        none;
}


.status-checkbox {

    width: 17px;

    height: 17px;

    margin: 0;

    accent-color:
        var(--green);

    cursor:
        pointer;
}


.price-input-wrap {

    position:
        relative;
}


.price-symbol {

    position:
        absolute;

    left:
        11px;

    top:
        50%;

    transform:
        translateY(-50%);

    color:
        var(--text-mute);

    font-size:
        12px;

    font-weight:
        800;

    pointer-events:
        none;
}


.price-input {

    padding-left:
        25px;
}


.variant-form-footer {

    display: flex;

    align-items: center;

    justify-content:
        space-between;

    gap:
        15px;

    padding:
        13px
        16px;

    border:
        1px solid
        var(--border);

    border-radius:
        11px;

    background:
        var(--bg-section);

    margin-top:
        2px;
}


.variant-footer-note {

    color:
        var(--text-mute);

    font-size:
        9.5px;

    line-height:
        1.4;
}


.variant-footer-actions {

    display: flex;

    align-items:
        center;

    gap:
        9px;
}


.variant-btn {

    display: inline-flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        7px;

    min-height:
        37px;

    padding:
        0
        16px;

    border-radius:
        8px;

    font-family:
        inherit;

    font-size:
        10.5px;

    font-weight:
        800;

    text-decoration:
        none;

    cursor:
        pointer;

    transition:
        .18s ease;

    box-sizing:
        border-box;
}


.variant-btn-cancel {

    border:
        1px solid
        var(--border);

    background:
        var(--bg-input);

    color:
        var(--text-body) !important;
}


.variant-btn-save {

    border:
        1px solid
        var(--green-hover);

    background:
        linear-gradient(
            135deg,
            #059669 0%,
            #10b981 100%
        );

    color:
        #ffffff;

    box-shadow:
        0 5px 15px
        rgba(16,185,129,.22);
}


.variant-btn-save:hover {

    filter:
        brightness(1.07);

    transform:
        translateY(-1px);
}


.variant-btn-save:disabled {

    opacity:
        .75;

    cursor:
        wait;

    transform:
        none;
}


/*
|--------------------------------------------------------------------------
| MODAL
|--------------------------------------------------------------------------
*/

.product-modal-overlay {

    display:
        none;

    position:
        fixed;

    inset:
        0;

    z-index:
        9999;

    background:
        rgba(0,0,0,.6);

    align-items:
        center;

    justify-content:
        center;

    padding:
        20px;

    backdrop-filter:
        blur(2px);
}


.product-modal-overlay.active {

    display:
        flex;
}


.product-modal-box {

    width:
        100%;

    max-width:
        650px;

    background:
        var(--bg-card);

    border:
        1px solid
        var(--border);

    border-radius:
        16px;

    box-shadow:
        0 20px 40px
        rgba(0,0,0,.3);

    display:
        flex;

    flex-direction:
        column;

    overflow:
        hidden;

    animation:
        modalPop .2s ease;
}


@keyframes modalPop {

    from {

        transform:
            scale(.95);

        opacity:
            0;
    }

    to {

        transform:
            scale(1);

        opacity:
            1;
    }
}


.product-modal-header {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    padding:
        20px
        24px
        15px;

    border-bottom:
        1px solid
        var(--border);
}


.product-modal-title {

    margin:
        0;

    color:
        var(--text-hi);

    font-size:
        18px;

    font-weight:
        800;
}


.product-modal-close {

    background:
        none;

    border:
        1px solid
        var(--border);

    border-radius:
        8px;

    width:
        32px;

    height:
        32px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    color:
        var(--text-mute);

    cursor:
        pointer;

    font-size:
        16px;
}


.product-modal-close:hover {

    background:
        var(--bg-hover);

    color:
        var(--text-hi);
}


.product-modal-body {

    padding:
        20px
        24px;

    display:
        flex;

    flex-direction:
        column;

    gap:
        15px;
}


.product-modal-search-wrap {

    position:
        relative;
}


.product-modal-search {

    width:
        100%;

    height:
        42px;

    padding:
        0
        15px
        0
        38px;

    border:
        1px solid
        var(--border);

    border-radius:
        10px;

    background:
        var(--bg-input);

    color:
        var(--text-hi);

    font-size:
        13px;

    outline:
        none;
}


.product-modal-search:focus {

    border-color:
        var(--green);

    box-shadow:
        0 0 0
        3px
        rgba(16,185,129,.12);
}


.product-modal-search-icon {

    position:
        absolute;

    left:
        12px;

    top:
        50%;

    transform:
        translateY(-50%);

    color:
        var(--text-mute);

    font-size:
        14px;

    pointer-events:
        none;
}


.product-modal-list {

    display:
        flex;

    flex-direction:
        column;

    gap:
        8px;

    max-height:
        320px;

    overflow-y:
        auto;

    padding-right:
        4px;
}


.product-modal-item {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    padding:
        12px
        16px;

    border:
        1px solid
        var(--border);

    border-radius:
        10px;

    background:
        var(--bg-input);

    color:
        var(--text-hi);

    cursor:
        pointer;

    text-decoration:
        none;

    transition:
        .15s ease;
}


.product-modal-item:hover,
.product-modal-item.selected {

    border-color:
        var(--green);

    background:
        var(--green-soft);
}


.product-modal-item-left {

    display:
        flex;

    align-items:
        center;

    gap:
        12px;
}


.product-modal-badge {

    width:
        30px;

    height:
        30px;

    border-radius:
        8px;

    background:
        var(--green-soft);

    color:
        var(--green);

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    font-weight:
        900;

    font-size:
        12px;
}


.product-modal-item-name {

    font-weight:
        650;

    font-size:
        13.5px;
}


.product-modal-footer {

    display:
        flex;

    align-items:
        center;

    justify-content:
        flex-end;

    padding:
        15px
        24px;

    border-top:
        1px solid
        var(--border);

    background:
        var(--bg-section);
}


/*
|--------------------------------------------------------------------------
| VALIDATION MESSAGE
|--------------------------------------------------------------------------
*/

.client-validation-message {

    display:
        none;

    margin-top:
        5px;

    color:
        var(--red);

    font-size:
        9.5px;

    font-weight:
        700;

    line-height:
        1.35;
}


.client-validation-message.show {

    display:
        block;
}


@media (max-width:760px) {

    .variant-grid-2,
    .variant-grid-3 {

        grid-template-columns:
            1fr;
    }


    .variant-form-footer {

        flex-direction:
            column;

        align-items:
            stretch;
    }


    .variant-footer-actions {

        width:
            100%;
    }


    .variant-btn {

        flex:
            1;
    }


    .add-variant-header {

        align-items:
            flex-start;

        flex-direction:
            column;
    }


    .add-variant-back {

        width:
            100%;
    }
}

</style>


<main class="main">

    <section class="content">

        <div class="add-variant-page">

            <!-- HEADER -->

            <div class="add-variant-header">

                <div class="add-variant-header-left">

                    <div class="add-variant-breadcrumb">

                        <span>Dashboard</span>

                        <span>›</span>

                        <span>Variants</span>

                        <span>›</span>

                        <span class="current">
                            Add Variant
                        </span>

                    </div>


                    <h1 class="add-variant-title">
                        Add New Product Variant
                    </h1>


                    <p class="add-variant-subtitle">
                        Create a product variant with automatic SKU generation, pricing, specifications and inventory settings.
                    </p>

                </div>


                <a
                    href="index.php"
                    class="add-variant-back"
                >
                    <span>←</span>

                    <span>
                        Back to Variants
                        <small>[B]</small>
                    </span>

                </a>

            </div>


            <!-- SERVER ERROR -->

            <?php if ($actionMessage !== ""): ?>

                <div class="variant-alert">

                    <div class="variant-alert-icon">
                        !
                    </div>

                    <div>
                        <?= e($actionMessage) ?>
                    </div>

                </div>

            <?php endif; ?>


            <!-- FORM -->

            <form
                method="POST"
                class="variant-form"
                id="addVariantForm"
                autocomplete="off"
                novalidate
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($csrfToken) ?>"
                >


                <input
                    type="hidden"
                    name="product_id"
                    id="productId"
                    value="<?= e($productId) ?>"
                >


                <!--
                |--------------------------------------------------------------------------
                | BASIC INFORMATION
                |--------------------------------------------------------------------------
                -->

                <div class="variant-section">

                    <div class="variant-section-header">

                        <div class="variant-section-icon icon-green">
                            #
                        </div>

                        <div class="variant-section-title">
                            Basic Information
                        </div>

                        <div class="variant-section-subtitle">
                            Product & identification
                        </div>

                    </div>


                    <div class="variant-section-body">

                        <div class="variant-grid-2">


                            <!-- PRODUCT -->

                            <div class="variant-form-group">

                                <label
                                    class="variant-form-label"
                                    for="productTrigger"
                                >

                                    <span>
                                        Base Product
                                        <span class="variant-required">
                                            *
                                        </span>
                                    </span>

                                    <span class="shortcut-badge">
                                        P
                                    </span>

                                </label>


                                <div
                                    id="productTrigger"
                                    class="product-select-trigger"
                                    title="Click to select base product"
                                >

                                    <span
                                        id="selectedProductLabel"
                                        style="color:var(--text-mute);"
                                    >
                                        <?=
                                            $productName !== ""
                                                ? e($productName)
                                                : "Select Base Product..."
                                        ?>
                                    </span>


                                    <span
                                        style="
                                            color:var(--text-mute);
                                            font-size:11px;
                                        "
                                    >
                                        🔍 Search
                                    </span>

                                </div>


                                <div
                                    class="client-validation-message"
                                    id="productError"
                                ></div>


                                <div class="field-note">
                                    Click to open product selection and search popup.
                                </div>

                            </div>


                            <!-- SKU -->

                            <div class="variant-form-group">

                                <label
                                    class="variant-form-label"
                                    for="sku"
                                >

                                    <span>
                                        SKU / Variant Code
                                        <span class="variant-required">
                                            *
                                        </span>
                                    </span>

                                    <span class="shortcut-badge">
                                        S
                                    </span>

                                </label>


                                <div class="sku-row">

                                    <input
                                        type="text"
                                        name="sku"
                                        id="sku"
                                        class="variant-input sku-input"
                                        value="<?= e($sku) ?>"
                                        placeholder="e.g. BATH-TOWEL-KING-WHITE"
                                        maxlength="100"
                                        required
                                    >


                                    <button
                                        type="button"
                                        class="auto-sku-btn"
                                        id="generateSkuButton"
                                    >
                                        ✦ AUTO GENERATE
                                    </button>

                                </div>


                                <div
                                    class="client-validation-message"
                                    id="skuError"
                                ></div>


                                <div class="field-note">

                                    <strong>
                                        Auto Generate:
                                    </strong>

                                    creates a unique SKU from product, size and color.

                                </div>

                            </div>


                            <!-- BARCODE -->

                            <div class="variant-form-group">

                                <label
                                    class="variant-form-label"
                                    for="barcode"
                                >
                                    Barcode / UPC
                                </label>


                                <input
                                    type="text"
                                    name="barcode"
                                    id="barcode"
                                    class="variant-input"
                                    value="<?= e($barcode) ?>"
                                    placeholder="e.g. 890123456789"
                                    maxlength="100"
                                >


                                <div
                                    class="client-validation-message"
                                    id="barcodeError"
                                ></div>


                                <div class="field-note">
                                    Optional. Must be unique when entered.
                                </div>

                            </div>


                            <!-- STATUS -->

                            <div class="variant-form-group">

                                <label class="variant-form-label">
                                    Variant Status
                                </label>


                                <div class="status-box">

                                    <label class="status-label">

                                        <input
                                            type="checkbox"
                                            name="is_active"
                                            value="1"
                                            class="status-checkbox"
                                            <?= $isActive ? "checked" : "" ?>
                                        >

                                        <span>
                                            Active Variant
                                        </span>

                                    </label>

                                </div>

                            </div>


                        </div>

                    </div>

                </div>


                <!--
                |--------------------------------------------------------------------------
                | ATTRIBUTES
                |--------------------------------------------------------------------------
                -->

                <div class="variant-section">

                    <div class="variant-section-header">

                        <div class="variant-section-icon icon-cyan">
                            ≡
                        </div>

                        <div class="variant-section-title">
                            Attributes & Specifications
                        </div>

                        <div class="variant-section-subtitle">
                            Variant details
                        </div>

                    </div>


                    <div class="variant-section-body">

                        <div class="variant-grid-3">


                            <!-- SIZE -->

                            <div class="variant-form-group">

                                <label
                                    class="variant-form-label"
                                    for="size"
                                >
                                    Size
                                </label>

                                <input
                                    type="text"
                                    name="size"
                                    id="size"
                                    class="variant-input"
                                    value="<?= e($size) ?>"
                                    placeholder="e.g. King, Queen"
                                    maxlength="100"
                                >

                                <div
                                    class="client-validation-message"
                                    id="sizeError"
                                ></div>

                            </div>


                            <!-- COLOR -->

                            <div class="variant-form-group">

                                <label
                                    class="variant-form-label"
                                    for="color"
                                >
                                    Color
                                </label>

                                <input
                                    type="text"
                                    name="color"
                                    id="color"
                                    class="variant-input"
                                    value="<?= e($color) ?>"
                                    placeholder="e.g. White, Blue"
                                    maxlength="100"
                                >

                                <div
                                    class="client-validation-message"
                                    id="colorError"
                                ></div>

                            </div>


                            <!-- MATERIAL -->

                            <div class="variant-form-group">

                                <label
                                    class="variant-form-label"
                                    for="material"
                                >
                                    Material
                                </label>

                                <input
                                    type="text"
                                    name="material"
                                    id="material"
                                    class="variant-input"
                                    value="<?= e($material) ?>"
                                    placeholder="e.g. 100% Cotton"
                                    maxlength="150"
                                >

                                <div
                                    class="client-validation-message"
                                    id="materialError"
                                ></div>

                            </div>


                            <!-- THREAD COUNT -->

                            <div class="variant-form-group">

                                <label
                                    class="variant-form-label"
                                    for="threadCount"
                                >
                                    Thread Count
                                </label>

                                <input
                                    type="text"
                                    name="thread_count"
                                    id="threadCount"
                                    class="variant-input"
                                    value="<?= e($threadCount) ?>"
                                    placeholder="e.g. 400 TC"
                                    maxlength="100"
                                >

                                <div
                                    class="client-validation-message"
                                    id="threadCountError"
                                ></div>

                            </div>


                            <!-- GSM -->

                            <div class="variant-form-group">

                                <label
                                    class="variant-form-label"
                                    for="weightGsm"
                                >
                                    Weight (GSM)
                                </label>

                                <input
                                    type="text"
                                    name="weight_gsm"
                                    id="weightGsm"
                                    class="variant-input"
                                    value="<?= e($weightGsm) ?>"
                                    placeholder="e.g. 600 GSM"
                                    maxlength="100"
                                >

                                <div
                                    class="client-validation-message"
                                    id="weightGsmError"
                                ></div>

                            </div>


                            <!-- DIMENSIONS -->

                            <div class="variant-form-group">

                                <label
                                    class="variant-form-label"
                                    for="dimensions"
                                >
                                    Dimensions
                                </label>

                                <input
                                    type="text"
                                    name="dimensions"
                                    id="dimensions"
                                    class="variant-input"
                                    value="<?= e($dimensions) ?>"
                                    placeholder="e.g. 90x100 inches"
                                    maxlength="150"
                                >

                                <div
                                    class="client-validation-message"
                                    id="dimensionsError"
                                ></div>

                            </div>


                        </div>

                    </div>

                </div>


                <!--
                |--------------------------------------------------------------------------
                | PRICING & INVENTORY
                |--------------------------------------------------------------------------
                -->

                <div class="variant-section">

                    <div class="variant-section-header">

                        <div class="variant-section-icon icon-gold">
                            ₹
                        </div>

                        <div class="variant-section-title">
                            Pricing & Inventory
                        </div>

                        <div class="variant-section-subtitle">
                            Commercial settings
                        </div>

                    </div>


                    <div class="variant-section-body">

                        <div class="variant-grid-3">


                            <!-- SELLING PRICE -->

                            <div class="variant-form-group">

                                <label
                                    class="variant-form-label"
                                    for="price"
                                >

                                    <span>
                                        Selling Price
                                        <span class="variant-required">
                                            *
                                        </span>
                                    </span>

                                </label>


                                <div class="price-input-wrap">

                                    <span class="price-symbol">
                                        ₹
                                    </span>

                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        name="price"
                                        id="price"
                                        class="variant-input price-input"
                                        value="<?= e($price) ?>"
                                        placeholder="0.00"
                                        required
                                    >

                                </div>


                                <div
                                    class="client-validation-message"
                                    id="priceError"
                                ></div>

                            </div>


                            <!-- COMPARE PRICE -->

                            <div class="variant-form-group">

                                <label
                                    class="variant-form-label"
                                    for="compareAtPrice"
                                >
                                    Compare At Price
                                </label>


                                <div class="price-input-wrap">

                                    <span class="price-symbol">
                                        ₹
                                    </span>

                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        name="compare_at_price"
                                        id="compareAtPrice"
                                        class="variant-input price-input"
                                        value="<?= e($compareAtPrice) ?>"
                                        placeholder="0.00"
                                    >

                                </div>


                                <div
                                    class="client-validation-message"
                                    id="compareAtPriceError"
                                ></div>

                            </div>


                            <!-- WHOLESALE -->

                            <div class="variant-form-group">

                                <label
                                    class="variant-form-label"
                                    for="wholesalePrice"
                                >
                                    Wholesale Price
                                </label>


                                <div class="price-input-wrap">

                                    <span class="price-symbol">
                                        ₹
                                    </span>

                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        name="wholesale_price"
                                        id="wholesalePrice"
                                        class="variant-input price-input"
                                        value="<?= e($wholesalePrice) ?>"
                                        placeholder="0.00"
                                    >

                                </div>


                                <div
                                    class="client-validation-message"
                                    id="wholesalePriceError"
                                ></div>

                            </div>


                            <!-- COST -->

                            <div class="variant-form-group">

                                <label
                                    class="variant-form-label"
                                    for="costPrice"
                                >
                                    Cost Price
                                </label>


                                <div class="price-input-wrap">

                                    <span class="price-symbol">
                                        ₹
                                    </span>

                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        name="cost_price"
                                        id="costPrice"
                                        class="variant-input price-input"
                                        value="<?= e($costPrice) ?>"
                                        placeholder="0.00"
                                    >

                                </div>


                                <div
                                    class="client-validation-message"
                                    id="costPriceError"
                                ></div>

                            </div>


                            <!-- WEIGHT -->

                            <div class="variant-form-group">

                                <label
                                    class="variant-form-label"
                                    for="weightKg"
                                >
                                    Weight (Kg)
                                </label>


                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="weight_kg"
                                    id="weightKg"
                                    class="variant-input"
                                    value="<?= e($weightKg) ?>"
                                    placeholder="e.g. 1.50"
                                >


                                <div
                                    class="client-validation-message"
                                    id="weightKgError"
                                ></div>

                            </div>


                            <!-- LOW STOCK -->

                            <div class="variant-form-group">

                                <label
                                    class="variant-form-label"
                                    for="lowStockThreshold"
                                >
                                    Low Stock Threshold
                                </label>


                                <input
                                    type="number"
                                    min="0"
                                    step="1"
                                    name="low_stock_threshold"
                                    id="lowStockThreshold"
                                    class="variant-input"
                                    value="<?= e($lowStockThreshold) ?>"
                                    placeholder="5"
                                >


                                <div
                                    class="client-validation-message"
                                    id="lowStockThresholdError"
                                ></div>


                                <div class="field-note">
                                    Alert when stock reaches this quantity.
                                </div>

                            </div>


                        </div>

                    </div>

                </div>


                <!-- FOOTER -->

                <div class="variant-form-footer">

                    <div class="variant-footer-note">

                        <strong>
                            * Required fields
                        </strong>

                        • SKU must be unique.

                        • Use Auto Generate for a unique SKU.

                    </div>


                    <div class="variant-footer-actions">

                        <a
                            href="index.php"
                            class="variant-btn variant-btn-cancel"
                            title="Shortcut: C"
                        >
                            Cancel
                            <small>[C]</small>
                        </a>


                        <button
                            type="submit"
                            class="variant-btn variant-btn-save"
                            id="saveVariantButton"
                            title="Shortcut: A"
                        >

                            <span>
                                ✓
                            </span>

                            <span>
                                Save Variant
                            </span>

                            <small>
                                [A]
                            </small>

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </section>

</main>


<!--
|--------------------------------------------------------------------------
| PRODUCT SELECTION MODAL
|--------------------------------------------------------------------------
-->

<div
    class="product-modal-overlay"
    id="productModal"
>

    <div class="product-modal-box">


        <div class="product-modal-header">

            <h3 class="product-modal-title">
                Select Base Product
            </h3>


            <button
                type="button"
                class="product-modal-close"
                id="closeProductModal"
            >
                ✕
            </button>

        </div>


        <div class="product-modal-body">

            <div class="product-modal-search-wrap">

                <span class="product-modal-search-icon">
                    🔍
                </span>

                <input
                    type="text"
                    id="modalProductSearch"
                    class="product-modal-search"
                    placeholder="Search product name..."
                    autocomplete="off"
                >

            </div>


            <div
                class="product-modal-list"
                id="modalProductList"
            >

                <?php if (empty($products)): ?>

                    <p
                        style="
                            text-align:center;
                            color:var(--text-mute);
                            padding:20px;
                        "
                    >
                        No products found.
                    </p>

                <?php else: ?>

                    <?php foreach ($products as $p): ?>

                        <div
                            class="product-modal-item"
                            data-id="<?= (int)$p["ProductId"] ?>"
                            data-name="<?= e($p["Name"]) ?>"
                        >

                            <div class="product-modal-item-left">

                                <div class="product-modal-badge">
                                    P
                                </div>

                                <div class="product-modal-item-name">
                                    <?= e($p["Name"]) ?>
                                </div>

                            </div>


                            <span
                                style="
                                    font-size:11px;
                                    color:var(--green);
                                    font-weight:bold;
                                "
                            >
                                Select →
                            </span>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </div>


        <div class="product-modal-footer">

            <button
                type="button"
                class="variant-btn variant-btn-cancel"
                id="cancelProductModal"
            >
                Cancel
            </button>

        </div>

    </div>

</div>


<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


    /*
    |--------------------------------------------------------------------------
    | ELEMENTS
    |--------------------------------------------------------------------------
    */

    const form =
        document.getElementById(
            "addVariantForm"
        );

    const productId =
        document.getElementById(
            "productId"
        );

    const productTrigger =
        document.getElementById(
            "productTrigger"
        );

    const selectedProductLabel =
        document.getElementById(
            "selectedProductLabel"
        );

    const productModal =
        document.getElementById(
            "productModal"
        );

    const closeProductModal =
        document.getElementById(
            "closeProductModal"
        );

    const cancelProductModal =
        document.getElementById(
            "cancelProductModal"
        );

    const modalProductSearch =
        document.getElementById(
            "modalProductSearch"
        );

    const modalProductList =
        document.getElementById(
            "modalProductList"
        );

    const sku =
        document.getElementById(
            "sku"
        );

    const size =
        document.getElementById(
            "size"
        );

    const color =
        document.getElementById(
            "color"
        );

    const barcode =
        document.getElementById(
            "barcode"
        );

    const threadCount =
        document.getElementById(
            "threadCount"
        );

    const weightGsm =
        document.getElementById(
            "weightGsm"
        );

    const material =
        document.getElementById(
            "material"
        );

    const dimensions =
        document.getElementById(
            "dimensions"
        );

    const price =
        document.getElementById(
            "price"
        );

    const compareAtPrice =
        document.getElementById(
            "compareAtPrice"
        );

    const wholesalePrice =
        document.getElementById(
            "wholesalePrice"
        );

    const costPrice =
        document.getElementById(
            "costPrice"
        );

    const weightKg =
        document.getElementById(
            "weightKg"
        );

    const lowStockThreshold =
        document.getElementById(
            "lowStockThreshold"
        );

    const generateSkuButton =
        document.getElementById(
            "generateSkuButton"
        );

    const saveButton =
        document.getElementById(
            "saveVariantButton"
        );


    /*
    |--------------------------------------------------------------------------
    | MODAL
    |--------------------------------------------------------------------------
    */

    if (productTrigger) {

        productTrigger.addEventListener(
            "click",
            function () {

                productModal.classList.add(
                    "active"
                );

                if (modalProductSearch) {

                    modalProductSearch.value =
                        "";

                    modalProductSearch.focus();

                    filterProducts("");
                }
            }
        );
    }


    function closeModal() {

        productModal.classList.remove(
            "active"
        );
    }


    if (closeProductModal) {

        closeProductModal.addEventListener(
            "click",
            closeModal
        );
    }


    if (cancelProductModal) {

        cancelProductModal.addEventListener(
            "click",
            closeModal
        );
    }


    if (productModal) {

        productModal.addEventListener(
            "click",
            function (e) {

                if (
                    e.target ===
                    productModal
                ) {
                    closeModal();
                }
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SEARCH PRODUCTS
    |--------------------------------------------------------------------------
    */

    function filterProducts(query) {

        const q =
            query
                .trim()
                .toLowerCase();

        const items =
            modalProductList.querySelectorAll(
                ".product-modal-item"
            );


        items.forEach(
            function (item) {

                const name =
                    item.dataset.name
                        .toLowerCase();


                if (
                    q === "" ||
                    name.includes(q)
                ) {

                    item.style.display =
                        "flex";

                } else {

                    item.style.display =
                        "none";
                }
            }
        );
    }


    if (modalProductSearch) {

        modalProductSearch.addEventListener(
            "input",
            function () {

                filterProducts(
                    this.value
                );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SELECT PRODUCT
    |--------------------------------------------------------------------------
    */

    if (modalProductList) {

        modalProductList.addEventListener(
            "click",
            function (e) {

                const item =
                    e.target.closest(
                        ".product-modal-item"
                    );


                if (!item) {
                    return;
                }


                const id =
                    item.dataset.id;

                const name =
                    item.dataset.name;


                productId.value =
                    id;

                selectedProductLabel.textContent =
                    name;

                selectedProductLabel.style.color =
                    "var(--text-hi)";

                selectedProductLabel.style.fontWeight =
                    "650";


                clearValidation(
                    "productId",
                    "productError"
                );


                closeModal();


                if (
                    sku &&
                    sku.value.trim() === ""
                ) {

                    generateSku();
                }
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATION HELPERS
    |--------------------------------------------------------------------------
    */

    function showValidation(
        inputId,
        errorId,
        message
    ) {

        const input =
            document.getElementById(
                inputId
            );

        const error =
            document.getElementById(
                errorId
            );


        if (input) {

            input.classList.add(
                "validation-error"
            );
        }


        if (error) {

            error.textContent =
                message;

            error.classList.add(
                "show"
            );
        }
    }


    function clearValidation(
        inputId,
        errorId
    ) {

        const input =
            document.getElementById(
                inputId
            );

        const error =
            document.getElementById(
                errorId
            );


        if (input) {

            input.classList.remove(
                "validation-error"
            );
        }


        if (error) {

            error.textContent =
                "";

            error.classList.remove(
                "show"
            );
        }
    }


    function clearAllValidation() {

        document
            .querySelectorAll(
                ".validation-error"
            )
            .forEach(
                function (el) {

                    el.classList.remove(
                        "validation-error"
                    );
                }
            );


        document
            .querySelectorAll(
                ".client-validation-message"
            )
            .forEach(
                function (el) {

                    el.textContent =
                        "";

                    el.classList.remove(
                        "show"
                    );
                }
            );
    }


    function focusField(element) {

        if (!element) {
            return;
        }

        element.focus();

        if (
            typeof element.select ===
            "function"
        ) {
            element.select();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | AUTO SKU GENERATOR
    |--------------------------------------------------------------------------
    */

    async function generateSku() {

        if (!productId || !sku) {
            return;
        }


        const selectedProduct =
            productId.value;


        if (!selectedProduct) {

            showValidation(
                "productTrigger",
                "productError",
                "Please select a base product first."
            );

            productTrigger.click();

            return;
        }


        if (generateSkuButton) {

            generateSkuButton.disabled =
                true;

            generateSkuButton.innerHTML =
                "Generating...";
        }


        try {

            const url =
                "add.php?generate_sku=1"
                + "&product_id="
                + encodeURIComponent(
                    selectedProduct
                )
                + "&size="
                + encodeURIComponent(
                    size
                        ? size.value
                        : ""
                )
                + "&color="
                + encodeURIComponent(
                    color
                        ? color.value
                        : ""
                );


            const response =
                await fetch(
                    url,
                    {
                        method: "GET",

                        headers: {
                            "X-Requested-With":
                                "XMLHttpRequest"
                        }
                    }
                );


            if (!response.ok) {

                throw new Error(
                    "Server error"
                );
            }


            const data =
                await response.json();


            if (
                data &&
                data.success &&
                data.sku
            ) {

                sku.value =
                    data.sku;


                sku.dispatchEvent(
                    new Event(
                        "input",
                        {
                            bubbles:
                                true
                        }
                    )
                );


                clearValidation(
                    "sku",
                    "skuError"
                );


                sku.focus();

                sku.select();

            } else {

                showValidation(
                    "sku",
                    "skuError",
                    data.message ||
                    "Unable to generate SKU."
                );
            }


        } catch (error) {

            console.error(error);

            showValidation(
                "sku",
                "skuError",
                "Unable to generate SKU. Please try again."
            );


        } finally {

            if (generateSkuButton) {

                generateSkuButton.disabled =
                    false;

                generateSkuButton.innerHTML =
                    "✦ AUTO GENERATE";
            }
        }
    }


    if (generateSkuButton) {

        generateSkuButton.addEventListener(
            "click",
            generateSku
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SKU INPUT
    |--------------------------------------------------------------------------
    */

    if (sku) {

        sku.addEventListener(
            "input",
            function () {

                this.value =
                    this.value
                        .toUpperCase()
                        .replace(
                            /\s+/g,
                            "-"
                        )
                        .replace(
                            /[^A-Z0-9_-]/g,
                            ""
                        );


                clearValidation(
                    "sku",
                    "skuError"
                );
            }
        );


        sku.addEventListener(
            "blur",
            async function () {

                const value =
                    this.value.trim();


                if (!value) {
                    return;
                }


                if (
                    !/^[A-Z0-9_-]+$/.test(
                        value
                    )
                ) {

                    showValidation(
                        "sku",
                        "skuError",
                        "SKU can contain only letters, numbers, hyphen and underscore."
                    );

                    return;
                }


                if (
                    value.length > 100
                ) {

                    showValidation(
                        "sku",
                        "skuError",
                        "SKU cannot be longer than 100 characters."
                    );

                    return;
                }


                try {

                    const response =
                        await fetch(
                            "add.php?check_sku=1&sku="
                            +
                            encodeURIComponent(
                                value
                            )
                        );


                    const data =
                        await response.json();


                    if (
                        data &&
                        data.exists
                    ) {

                        showValidation(
                            "sku",
                            "skuError",
                            "This SKU already exists. Please use another SKU or click Auto Generate."
                        );

                        this.focus();

                        this.select();
                    }


                } catch (error) {

                    console.error(error);
                }
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | BARCODE INPUT
    |--------------------------------------------------------------------------
    */

    if (barcode) {

        barcode.addEventListener(
            "input",
            function () {

                this.value =
                    this.value.replace(
                        /[^0-9A-Za-z_-]/g,
                        ""
                    );


                clearValidation(
                    "barcode",
                    "barcodeError"
                );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CLEAR VALIDATION ON INPUT
    |--------------------------------------------------------------------------
    */

    [
        [size, "size", "sizeError"],
        [color, "color", "colorError"],
        [material, "material", "materialError"],
        [threadCount, "threadCount", "threadCountError"],
        [weightGsm, "weightGsm", "weightGsmError"],
        [dimensions, "dimensions", "dimensionsError"],
        [price, "price", "priceError"],
        [compareAtPrice, "compareAtPrice", "compareAtPriceError"],
        [wholesalePrice, "wholesalePrice", "wholesalePriceError"],
        [costPrice, "costPrice", "costPriceError"],
        [weightKg, "weightKg", "weightKgError"],
        [lowStockThreshold, "lowStockThreshold", "lowStockThresholdError"]
    ].forEach(
        function (item) {

            if (item[0]) {

                item[0].addEventListener(
                    "input",
                    function () {

                        clearValidation(
                            item[1],
                            item[2]
                        );
                    }
                );
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | FORM VALIDATION
    |--------------------------------------------------------------------------
    */

    let formSubmitting =
        false;


    if (form && saveButton) {

        form.addEventListener(
            "submit",
            function (event) {

                if (formSubmitting) {

                    event.preventDefault();

                    return;
                }


                clearAllValidation();


                /*
                |--------------------------------------------------------------------------
                | PRODUCT
                |--------------------------------------------------------------------------
                */

                if (
                    !productId ||
                    !productId.value
                ) {

                    event.preventDefault();

                    showValidation(
                        "productTrigger",
                        "productError",
                        "Please select a base product."
                    );

                    productTrigger.click();

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | SKU
                |--------------------------------------------------------------------------
                */

                const skuValue =
                    sku.value.trim();


                if (!skuValue) {

                    event.preventDefault();

                    showValidation(
                        "sku",
                        "skuError",
                        "SKU / Variant Code is required."
                    );

                    focusField(sku);

                    return;
                }


                if (
                    skuValue.length > 100
                ) {

                    event.preventDefault();

                    showValidation(
                        "sku",
                        "skuError",
                        "SKU cannot be longer than 100 characters."
                    );

                    focusField(sku);

                    return;
                }


                if (
                    !/^[A-Z0-9_-]+$/.test(
                        skuValue
                    )
                ) {

                    event.preventDefault();

                    showValidation(
                        "sku",
                        "skuError",
                        "SKU can contain only letters, numbers, hyphen and underscore."
                    );

                    focusField(sku);

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | BARCODE
                |--------------------------------------------------------------------------
                */

                const barcodeValue =
                    barcode.value.trim();


                if (
                    barcodeValue &&
                    barcodeValue.length > 100
                ) {

                    event.preventDefault();

                    showValidation(
                        "barcode",
                        "barcodeError",
                        "Barcode cannot be longer than 100 characters."
                    );

                    focusField(barcode);

                    return;
                }


                if (
                    barcodeValue &&
                    !/^[A-Za-z0-9_-]+$/.test(
                        barcodeValue
                    )
                ) {

                    event.preventDefault();

                    showValidation(
                        "barcode",
                        "barcodeError",
                        "Barcode can contain only letters, numbers, hyphen and underscore."
                    );

                    focusField(barcode);

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | THREAD COUNT
                |--------------------------------------------------------------------------
                */

                if (
                    threadCount.value.trim() &&
                    !/^[0-9]+(?:\s*TC)?$/i.test(
                        threadCount.value.trim()
                    )
                ) {

                    event.preventDefault();

                    showValidation(
                        "threadCount",
                        "threadCountError",
                        "Enter Thread Count like 400 or 400 TC."
                    );

                    focusField(threadCount);

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | GSM
                |--------------------------------------------------------------------------
                */

                if (
                    weightGsm.value.trim() &&
                    !/^[0-9]+(?:\.[0-9]+)?(?:\s*GSM)?$/i.test(
                        weightGsm.value.trim()
                    )
                ) {

                    event.preventDefault();

                    showValidation(
                        "weightGsm",
                        "weightGsmError",
                        "Enter GSM like 600 or 600 GSM."
                    );

                    focusField(weightGsm);

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | SELLING PRICE
                |--------------------------------------------------------------------------
                */

                const priceValue =
                    price.value.trim();


                if (!priceValue) {

                    event.preventDefault();

                    showValidation(
                        "price",
                        "priceError",
                        "Selling price is required."
                    );

                    focusField(price);

                    return;
                }


                if (
                    !isFinite(
                        Number(priceValue)
                    )
                ) {

                    event.preventDefault();

                    showValidation(
                        "price",
                        "priceError",
                        "Selling price must be a valid number."
                    );

                    focusField(price);

                    return;
                }


                if (
                    Number(priceValue) <= 0
                ) {

                    event.preventDefault();

                    showValidation(
                        "price",
                        "priceError",
                        "Selling price must be greater than 0."
                    );

                    focusField(price);

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | COMPARE PRICE
                |--------------------------------------------------------------------------
                */

                const compareValue =
                    compareAtPrice.value.trim();


                if (
                    compareValue &&
                    (
                        !isFinite(
                            Number(compareValue)
                        ) ||
                        Number(compareValue) < 0
                    )
                ) {

                    event.preventDefault();

                    showValidation(
                        "compareAtPrice",
                        "compareAtPriceError",
                        "Compare At Price must be a valid positive number."
                    );

                    focusField(compareAtPrice);

                    return;
                }


                if (
                    compareValue &&
                    Number(compareValue)
                    <
                    Number(priceValue)
                ) {

                    event.preventDefault();

                    showValidation(
                        "compareAtPrice",
                        "compareAtPriceError",
                        "Compare At Price should be greater than or equal to Selling Price."
                    );

                    focusField(compareAtPrice);

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | WHOLESALE
                |--------------------------------------------------------------------------
                */

                const wholesaleValue =
                    wholesalePrice.value.trim();


                if (
                    wholesaleValue &&
                    (
                        !isFinite(
                            Number(wholesaleValue)
                        ) ||
                        Number(wholesaleValue) < 0
                    )
                ) {

                    event.preventDefault();

                    showValidation(
                        "wholesalePrice",
                        "wholesalePriceError",
                        "Wholesale Price must be a valid positive number."
                    );

                    focusField(wholesalePrice);

                    return;
                }


                if (
                    wholesaleValue &&
                    Number(wholesaleValue)
                    >
                    Number(priceValue)
                ) {

                    event.preventDefault();

                    showValidation(
                        "wholesalePrice",
                        "wholesalePriceError",
                        "Wholesale Price should not be greater than Selling Price."
                    );

                    focusField(wholesalePrice);

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | COST
                |--------------------------------------------------------------------------
                */

                const costValue =
                    costPrice.value.trim();


                if (
                    costValue &&
                    (
                        !isFinite(
                            Number(costValue)
                        ) ||
                        Number(costValue) < 0
                    )
                ) {

                    event.preventDefault();

                    showValidation(
                        "costPrice",
                        "costPriceError",
                        "Cost Price must be a valid positive number."
                    );

                    focusField(costPrice);

                    return;
                }


                if (
                    costValue &&
                    Number(costValue)
                    >
                    Number(priceValue)
                ) {

                    event.preventDefault();

                    showValidation(
                        "costPrice",
                        "costPriceError",
                        "Cost Price cannot be greater than Selling Price."
                    );

                    focusField(costPrice);

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | WEIGHT KG
                |--------------------------------------------------------------------------
                */

                const weightValue =
                    weightKg.value.trim();


                if (
                    weightValue &&
                    (
                        !isFinite(
                            Number(weightValue)
                        ) ||
                        Number(weightValue) < 0
                    )
                ) {

                    event.preventDefault();

                    showValidation(
                        "weightKg",
                        "weightKgError",
                        "Weight must be a valid number and cannot be negative."
                    );

                    focusField(weightKg);

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | LOW STOCK
                |--------------------------------------------------------------------------
                */

                const stockValue =
                    lowStockThreshold.value.trim();


                if (
                    !/^[0-9]+$/.test(
                        stockValue
                    )
                ) {

                    event.preventDefault();

                    showValidation(
                        "lowStockThreshold",
                        "lowStockThresholdError",
                        "Low stock threshold must be a whole number."
                    );

                    focusField(
                        lowStockThreshold
                    );

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | ALL VALID
                |--------------------------------------------------------------------------
                */

                formSubmitting =
                    true;


                saveButton.disabled =
                    true;

                saveButton.style.opacity =
                    ".75";

                saveButton.style.cursor =
                    "wait";

                saveButton.innerHTML =
                    "<span>✓</span><span>Saving...</span>";

            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | KEYBOARD SHORTCUTS
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        "keydown",
        function (event) {

            /*
            P = Product
            */

            if (
                event.key.toLowerCase() === "p" &&
                !isTyping(event.target)
            ) {

                event.preventDefault();

                if (productTrigger) {
                    productTrigger.click();
                }

                return;
            }


            /*
            S = SKU
            */

            if (
                event.key.toLowerCase() === "s" &&
                !isTyping(event.target)
            ) {

                event.preventDefault();

                if (sku) {
                    sku.focus();
                }

                return;
            }


            /*
            B = Back
            */

            if (
                event.key.toLowerCase() === "b" &&
                !isTyping(event.target)
            ) {

                window.location.href =
                    "index.php";

                return;
            }


            /*
            C = Cancel
            */

            if (
                event.key.toLowerCase() === "c" &&
                !isTyping(event.target)
            ) {

                window.location.href =
                    "index.php";

                return;
            }


            /*
            A = Add / Save
            */

            if (
                event.key.toLowerCase() === "a" &&
                !isTyping(event.target)
            ) {

                event.preventDefault();

                if (form) {
                    form.requestSubmit();
                }
            }
        }
    );


    function isTyping(element) {

        if (!element) {
            return false;
        }


        const tag =
            element.tagName
                ? element.tagName.toLowerCase()
                : "";


        return (
            tag === "input" ||
            tag === "textarea" ||
            tag === "select" ||
            element.isContentEditable
        );
    }


});

</script>


<?php

require_once __DIR__ . "/../includes/footer.php";

?>