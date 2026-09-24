<?php
session_start();

/*
|--------------------------------------------------------------------------
| GatewayLinen Admin - Add Product
|--------------------------------------------------------------------------
| File:
| GatewayLinenadmin/products/add.php
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

$activeMenu = "products";
$pageTitle  = "GatewayLinen | Add Product";

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
| HELPER FUNCTION
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

/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION["product_csrf_token"])) {
    $_SESSION["product_csrf_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["product_csrf_token"];

/*
|--------------------------------------------------------------------------
| FORM VARIABLES
|--------------------------------------------------------------------------
*/

$categoryId       = "";
$name             = "";
$slug             = "";
$shortDescription = "";
$description      = "";
$careInstructions = "";
$specifications   = "";
$basePrice        = "0.00";
$gstPercentage    = "5.00";
$pstPercentage    = "0.00";
$metaTitle        = "";
$metaDescription  = "";

$isFeatured   = 0;
$isNewArrival = 1;
$isBestSeller = 0;
$isActive     = 1;

$error = "";

/*
|--------------------------------------------------------------------------
| UPLOAD DIRECTORY
|--------------------------------------------------------------------------
*/

$uploadDirectory = __DIR__ . "/../uploads/products/";

if (!is_dir($uploadDirectory)) {
    if (!@mkdir($uploadDirectory, 0755, true)) {
        $error = "Unable to create product upload directory.";
    }
}

/*
|--------------------------------------------------------------------------
| FETCH ACTIVE CATEGORIES
|--------------------------------------------------------------------------
*/

$categories = [];
$categorySql = "
    SELECT CategoryId, Name 
    FROM dbo.Categories 
    ORDER BY Name ASC
";
$categoryStmt = sqlsrv_query($conn, $categorySql);

if ($categoryStmt !== false) {
    while ($row = sqlsrv_fetch_array($categoryStmt, SQLSRV_FETCH_ASSOC)) {
        $categories[] = $row;
    }
    sqlsrv_free_stmt($categoryStmt);
}

/*
|--------------------------------------------------------------------------
| SAVE PRODUCT
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /*
    |--------------------------------------------------------------------------
    | CSRF VALIDATION
    |--------------------------------------------------------------------------
    */
    $postedToken = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["product_csrf_token"], $postedToken)) {
        $error = "Security verification failed. Please refresh the page and try again.";
    }

    /*
    |--------------------------------------------------------------------------
    | GET FORM DATA
    |--------------------------------------------------------------------------
    */
    if ($error === "") {
        $categoryId       = !empty($_POST["category_id"]) ? (int) $_POST["category_id"] : null;
        $name             = trim($_POST["name"] ?? "");
        $slug             = trim($_POST["slug"] ?? "");
        $shortDescription = trim($_POST["short_description"] ?? "");
        $description      = trim($_POST["description"] ?? "");
        $careInstructions = trim($_POST["care_instructions"] ?? "");
        $specifications   = trim($_POST["specifications"] ?? "");
        $basePrice        = trim($_POST["base_price"] ?? "0.00");
        $gstPercentage    = trim($_POST["gst_percentage"] ?? "0.00");
        $pstPercentage    = trim($_POST["pst_percentage"] ?? "0.00");
        $metaTitle        = trim($_POST["meta_title"] ?? "");
        $metaDescription  = trim($_POST["meta_description"] ?? "");

        $isFeatured   = isset($_POST["is_featured"]) ? 1 : 0;
        $isNewArrival = isset($_POST["is_new_arrival"]) ? 1 : 0;
        $isBestSeller = isset($_POST["is_best_seller"]) ? 1 : 0;
        $isActive     = isset($_POST["is_active"]) ? 1 : 0;
    }

    /*
    |--------------------------------------------------------------------------
    | BASIC VALIDATIONS
    |--------------------------------------------------------------------------
    */
    if ($error === "" && empty($categoryId)) {
        $error = "Please select a category.";
    }

    if ($error === "" && $name === "") {
        $error = "Product name is required.";
    }

    if ($error === "" && mb_strlen($name) > 255) {
        $error = "Product name cannot be longer than 255 characters.";
    }

    if ($error === "" && (!is_numeric($basePrice) || (float)$basePrice < 0)) {
        $error = "Please enter a valid base price.";
    }

    /*
    |--------------------------------------------------------------------------
    | AUTO SLUG
    |--------------------------------------------------------------------------
    */
    if ($error === "") {
        if ($slug === "") {
            $slug = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "-", $name));
            $slug = trim($slug, "-");
        }

        $slug = strtolower(preg_replace("/[^a-zA-Z0-9\-]/", "", $slug));
        $slug = trim($slug, "-");

        if ($slug === "") {
            $error = "Please enter a valid product name or slug.";
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DUPLICATE SLUG CHECK
    |--------------------------------------------------------------------------
    */
    if ($error === "") {
        $checkSlugSql = "SELECT TOP 1 ProductId FROM dbo.Products WHERE Slug = ?";
        $checkSlugStmt = sqlsrv_query($conn, $checkSlugSql, [$slug]);

        if ($checkSlugStmt !== false) {
            if (sqlsrv_fetch_array($checkSlugStmt, SQLSRV_FETCH_ASSOC)) {
                $error = "This product slug already exists. Please choose a different slug.";
            }
            sqlsrv_free_stmt($checkSlugStmt);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | AUTO SEO
    |--------------------------------------------------------------------------
    */
    if ($error === "" && $metaTitle === "") {
        $metaTitle = $name . " | GatewayLinen";
    }

    if ($error === "" && $metaDescription === "" && $shortDescription !== "") {
        $metaDescription = $shortDescription;
    }

    /*
    |--------------------------------------------------------------------------
    | UPLOAD MULTIPLE IMAGES PRE-CHECK
    |--------------------------------------------------------------------------
    */
    $uploadedFiles = [];
    $allowedMimeTypes = [
        "image/jpeg" => "jpg",
        "image/png"  => "png",
        "image/webp" => "webp",
        "image/gif"  => "gif"
    ];

    if ($error === "" && isset($_FILES["product_images"])) {
        $files = $_FILES["product_images"];
        $count = count($files["name"]);

        for ($i = 0; $i < $count; $i++) {
            if ($files["error"][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($files["error"][$i] !== UPLOAD_ERR_OK) {
                $error = "An error occurred while uploading an image.";
                break;
            }

            if ($files["size"][$i] > (5 * 1024 * 1024)) {
                $error = "Each image must be less than 5 MB.";
                break;
            }

            $imgInfo = @getimagesize($files["tmp_name"][$i]);
            if ($imgInfo === false || !isset($allowedMimeTypes[$imgInfo["mime"]])) {
                $error = "Only JPG, PNG, WEBP and GIF images are allowed.";
                break;
            }

            $ext = $allowedMimeTypes[$imgInfo["mime"]];
            $randHex = bin2hex(random_bytes(8));
            $newFileName = "prod_" . date("Ymd_His") . "_{$i}_" . $randHex . "." . $ext;
            $physicalPath = $uploadDirectory . $newFileName;
            $dbPath = "uploads/products/" . $newFileName;

            $uploadedFiles[] = [
                "tmp"      => $files["tmp_name"][$i],
                "dest"     => $physicalPath,
                "db_path"  => $dbPath,
                "name"     => $files["name"][$i]
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | INSERT PRODUCT & IMAGES (TRANSACTION)
    |--------------------------------------------------------------------------
    */
    if ($error === "") {
        sqlsrv_begin_transaction($conn);

        $productSql = "
            INSERT INTO dbo.Products (
                CategoryId, Name, Slug, ShortDescription, Description,
                CareInstructions, Specifications, BasePrice, GstPercentage,
                PstPercentage, MetaTitle, MetaDescription, IsFeatured,
                IsNewArrival, IsBestSeller, IsActive, CreatedAt, UpdatedAt
            )
            OUTPUT INSERTED.ProductId
            VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, GETDATE(), GETDATE()
            )
        ";

        $productParams = [
            $categoryId,
            $name,
            $slug,
            $shortDescription !== "" ? $shortDescription : null,
            $description !== "" ? $description : null,
            $careInstructions !== "" ? $careInstructions : null,
            $specifications !== "" ? $specifications : null,
            (float)$basePrice,
            (float)$gstPercentage,
            (float)$pstPercentage,
            $metaTitle !== "" ? $metaTitle : null,
            $metaDescription !== "" ? $metaDescription : null,
            $isFeatured,
            $isNewArrival,
            $isBestSeller,
            $isActive
        ];

        $productStmt = sqlsrv_query($conn, $productSql, $productParams);

        if ($productStmt === false) {
            sqlsrv_rollback($conn);
            $errors = sqlsrv_errors();
            $error = $errors[0]["message"] ?? "Failed to save the product.";
        } else {
            $insertedRow = sqlsrv_fetch_array($productStmt, SQLSRV_FETCH_ASSOC);
            $newProductId = $insertedRow["ProductId"] ?? null;
            sqlsrv_free_stmt($productStmt);

            if ($newProductId) {
                $imagesSavedSuccessfully = true;
                $savedPhysicalFiles = [];

                foreach ($uploadedFiles as $index => $uFile) {
                    if (move_uploaded_file($uFile["tmp"], $uFile["dest"])) {
                        $savedPhysicalFiles[] = $uFile["dest"];

                        $isMain = ($index === 0) ? 1 : 0;
                        $displayOrder = $index + 1;
                        $altText = $name;

                        $imgSql = "
                            INSERT INTO dbo.ProductImages (
                                ProductId, VariantId, ImageUrl, AltText, IsMain, DisplayOrder
                            ) VALUES (
                                ?, NULL, ?, ?, ?, ?
                            )
                        ";
                        $imgParams = [$newProductId, $uFile["db_path"], $altText, $isMain, $displayOrder];
                        $imgStmt = sqlsrv_query($conn, $imgSql, $imgParams);

                        if ($imgStmt === false) {
                            $imagesSavedSuccessfully = false;
                            break;
                        }
                        sqlsrv_free_stmt($imgStmt);
                    } else {
                        $imagesSavedSuccessfully = false;
                        break;
                    }
                }

                if ($imagesSavedSuccessfully) {
                    sqlsrv_commit($conn);

                    $_SESSION["product_csrf_token"] = bin2hex(random_bytes(32));
                    header("Location: index.php?success=product_created");
                    exit;
                } else {
                    sqlsrv_rollback($conn);
                    foreach ($savedPhysicalFiles as $fPath) {
                        @unlink($fPath);
                    }
                    $error = "Failed to upload one or more product images.";
                }
            } else {
                sqlsrv_rollback($conn);
                $error = "Product was created but Product ID could not be retrieved.";
            }
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

<style>
    /* Theme colors consistent with category page */
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
        --green-soft: rgba(16, 185, 129, .12);
        --blue: #3b82f6;
        --blue-soft: rgba(59, 130, 246, .12);
        --purple: #8b5cf6;
        --purple-soft: rgba(139, 92, 246, .12);
        --amber: #f59e0b;
        --amber-soft: rgba(245, 158, 11, .12);
        --red: #ef4444;
        --red-soft: rgba(239, 68, 68, .12);
    }

    html,
    body {
        background: #0a1119 !important;
        color: #a8b8c8 !important;
    }

    .main,
    .content {
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

    .add-category-breadcrumb .current {
        color: var(--green);
    }

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
        border: 1px solid rgba(239, 68, 68, .3);
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
        box-shadow: 0 15px 40px rgba(0, 0, 0, .18);
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

    .add-category-section:first-child {
        margin-top: 0;
    }

    .add-category-section.sec-blue {
        background: var(--blue-soft);
        border-left-color: var(--blue);
    }

    .add-category-section.sec-purple {
        background: var(--purple-soft);
        border-left-color: var(--purple);
    }

    .add-category-section.sec-amber {
        background: var(--amber-soft);
        border-left-color: var(--amber);
    }

    .add-category-section-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 26px;
        border-radius: 7px;
        background: rgba(16, 185, 129, .18);
        color: var(--green);
        font-weight: 900;
    }

    .sec-blue .add-category-section-icon {
        background: rgba(59, 130, 246, .18);
        color: var(--blue);
    }

    .sec-purple .add-category-section-icon {
        background: rgba(139, 92, 246, .18);
        color: var(--purple);
    }

    .sec-amber .add-category-section-icon {
        background: rgba(245, 158, 11, .18);
        color: var(--amber);
    }

    .add-category-section-title {
        color: var(--text-hi);
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .8px;
    }

    .add-form-group {
        min-width: 0;
    }

    .add-form-group-full {
        grid-column: 1 / -1;
    }

    .add-form-label {
        display: block;
        margin-bottom: 7px;
        color: var(--text-body);
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .3px;
    }

    .add-form-required {
        color: var(--red);
    }

    .add-form-input,
    .add-form-select,
    .add-form-textarea {
        width: 100%;
        box-sizing: border-box;
        border: 1px solid var(--border);
        border-radius: 9px;
        background: var(--bg-input);
        color: var(--text-hi);
        font-family: inherit;
        font-size: 12px;
        transition: all .18s ease;
    }

    .add-form-input,
    .add-form-select {
        height: 42px;
        padding: 0 13px;
    }

    .add-form-textarea {
        min-height: 90px;
        padding: 10px 13px;
        resize: vertical;
        line-height: 1.5;
    }

    .add-form-input:focus,
    .add-form-select:focus,
    .add-form-textarea:focus {
        outline: none;
        border-color: var(--green);
        box-shadow: 0 0 0 3px rgba(16, 185, 129, .13);
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

    /* Switches / Badges */
    .flags-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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

    /* Image Upload Area */
    .category-upload-box {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 140px;
        padding: 20px;
        border: 2px dashed rgba(59, 130, 246, .38);
        border-radius: 11px;
        background: rgba(59, 130, 246, .045);
        cursor: pointer;
        text-align: center;
    }

    .category-upload-box:hover {
        border-color: var(--blue);
        background: rgba(59, 130, 246, .08);
    }

    .category-upload-input {
        position: absolute;
        width: 1px;
        height: 1px;
        opacity: 0;
    }

    .category-upload-icon {
        font-size: 26px;
        color: var(--blue);
        margin-bottom: 6px;
    }

    .category-preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
        gap: 12px;
        margin-top: 14px;
    }

    .category-preview-item {
        position: relative;
        width: 100%;
        height: 90px;
        border: 2px solid var(--border);
        border-radius: 8px;
        overflow: hidden;
        background: var(--bg-card-alt);
    }

    .category-preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .category-preview-badge {
        position: absolute;
        top: 4px;
        left: 4px;
        background: var(--green);
        color: #fff;
        font-size: 8.5px;
        font-weight: 800;
        padding: 2px 5px;
        border-radius: 4px;
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

    .add-category-cancel:hover {
        background: var(--bg-hover);
        color: var(--text-hi) !important;
    }

    .add-category-save {
        border: 1px solid var(--green-dark);
        background: linear-gradient(135deg, #059669 0%, #10b981 100%);
        color: #fff;
    }

    .add-category-save:hover {
        filter: brightness(1.08);
    }

    /* Shortcuts Box */
    .shortcut-help-box {
        margin-top: 22px;
        padding: 16px 20px;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: var(--bg-card);
    }

    .shortcut-help-box.hidden {
        display: none;
    }

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

    .shortcut-desc {
        font-size: 11px;
        font-weight: 600;
        color: var(--text-body);
    }

    @media (max-width: 860px) {
        .add-category-grid {
            grid-template-columns: 1fr;
        }

        .add-form-group-full {
            grid-column: auto;
        }
    }
</style>

<main class="main">
    <section class="content">
        <div class="add-category-page">

            <div class="add-category-header">
                <div>
                    <div class="add-category-breadcrumb">
                        <span>Dashboard</span>
                        <span>›</span>
                        <span>Products</span>
                        <span>›</span>
                        <span class="current">Add Product</span>
                    </div>
                    <h1 class="add-category-title">Add New Product</h1>
                    <p class="add-category-subtitle">
                        Create a product with pricing, tax (GST/PST), multiple images, specifications, and SEO settings.
                    </p>
                </div>
                <a href="index.php" class="add-category-back">
                    <span>←</span>
                    <span>Back to Products</span>
                </a>
            </div>

            <?php if ($error !== ""): ?>
                <div class="add-category-error">
                    <span style="font-weight:900;">!</span>
                    <div><?= e($error) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" autocomplete="off" class="add-category-form" id="addProductForm">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

                <div class="add-category-form-body">
                    <div class="add-category-grid">

                        <div class="add-category-section">
                            <div class="add-category-section-icon">#</div>
                            <div class="add-category-section-title">Basic Information</div>
                        </div>

                        <div class="add-form-group">
                            <label for="productCategory" class="add-form-label">
                                Category <span class="add-form-required">*</span>
                            </label>
                            <select id="productCategory" name="category_id" class="add-form-select" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int)$cat["CategoryId"] ?>" <?= (string)$categoryId === (string)$cat["CategoryId"] ? "selected" : "" ?>>
                                        <?= e($cat["Name"]) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="add-form-group">
                            <label for="productName" class="add-form-label">
                                Product Name <span class="add-form-required">*</span>
                            </label>
                            <input type="text" id="productName" name="name" class="add-form-input" value="<?= e($name) ?>" placeholder="e.g. Premium Bath Towel" maxlength="255" required>
                        </div>

                        <div class="add-form-group">
                            <label for="productSlug" class="add-form-label">Slug</label>
                            <input type="text" id="productSlug" name="slug" class="add-form-input" value="<?= e($slug) ?>" placeholder="premium-bath-towel" maxlength="255">
                        </div>

                        <div class="add-form-group">
                            <label for="productShortDesc" class="add-form-label">Short Description</label>
                            <input type="text" id="productShortDesc" name="short_description" class="add-form-input" value="<?= e($shortDescription) ?>" placeholder="Brief 1-line overview of product">
                        </div>

                        <div class="add-form-group add-form-group-full">
                            <label for="productDesc" class="add-form-label">Full Description</label>
                            <textarea id="productDesc" name="description" class="add-form-textarea" placeholder="Detailed product overview..."><?= e($description) ?></textarea>
                        </div>

                        <div class="add-category-section sec-amber">
                            <div class="add-category-section-icon">$</div>
                            <div class="add-category-section-title">Pricing & Taxation</div>
                        </div>

                        <div class="add-form-group">
                            <label for="basePrice" class="add-form-label">Base Price ($) <span class="add-form-required">*</span></label>
                            <input type="number" step="0.01" min="0" id="basePrice" name="base_price" class="add-form-input" value="<?= e($basePrice) ?>" required>
                        </div>

                        <div class="add-form-group">
                            <label for="gstPercentage" class="add-form-label">GST Percentage (%)</label>
                            <input type="number" step="0.01" min="0" id="gstPercentage" name="gst_percentage" class="add-form-input" value="<?= e($gstPercentage) ?>">
                        </div>

                        <div class="add-form-group">
                            <label for="pstPercentage" class="add-form-label">PST Percentage (%)</label>
                            <input type="number" step="0.01" min="0" id="pstPercentage" name="pst_percentage" class="add-form-input" value="<?= e($pstPercentage) ?>">
                        </div>

                        <div class="add-category-section sec-purple">
                            <div class="add-category-section-icon">≡</div>
                            <div class="add-category-section-title">Specifications & Care</div>
                        </div>

                        <div class="add-form-group">
                            <label for="productSpecs" class="add-form-label">Specifications</label>
                            <textarea id="productSpecs" name="specifications" class="add-form-textarea" placeholder="e.g. 100% Cotton, 600 GSM, Hotel Quality"><?= e($specifications) ?></textarea>
                        </div>

                        <div class="add-form-group">
                            <label for="careInstructions" class="add-form-label">Care Instructions</label>
                            <textarea id="careInstructions" name="care_instructions" class="add-form-textarea" placeholder="e.g. Machine wash cold. Do not bleach."><?= e($careInstructions) ?></textarea>
                        </div>

                        <div class="add-category-section sec-blue">
                            <div class="add-category-section-icon">↑</div>
                            <div class="add-category-section-title">Product Images (dbo.ProductImages)</div>
                        </div>

                        <div class="add-form-group add-form-group-full">
                            <label for="productImages" class="category-upload-box" id="imageUploadBox">
                                <input type="file" id="productImages" name="product_images[]" class="category-upload-input" multiple accept=".jpg,.jpeg,.png,.webp,.gif">
                                <div class="category-upload-icon">📁</div>
                                <div style="color:var(--text-hi); font-size:13px; font-weight:700;">
                                    Click or Drag & Drop to upload product images
                                </div>
                                <div style="color:var(--text-mute); font-size:10px; margin-top:5px;">
                                    First uploaded image will be set as Main Image (IsMain = 1). Max 5MB each.
                                </div>
                            </label>
                            <div class="category-preview-grid" id="imagePreviewGrid"></div>
                        </div>

                        <div class="add-category-section">
                            <div class="add-category-section-icon">✓</div>
                            <div class="add-category-section-title">Flags & Visibility</div>
                        </div>

                        <div class="add-form-group add-form-group-full">
                            <div class="flags-container">
                                <div class="add-active-box">
                                    <label class="add-active-label">
                                        <input type="checkbox" name="is_active" value="1" class="add-active-checkbox" <?= $isActive ? "checked" : "" ?>>
                                        <span>Active Product</span>
                                    </label>
                                </div>

                                <div class="add-active-box">
                                    <label class="add-active-label">
                                        <input type="checkbox" name="is_featured" value="1" class="add-active-checkbox" <?= $isFeatured ? "checked" : "" ?>>
                                        <span>Featured</span>
                                    </label>
                                </div>

                                <div class="add-active-box">
                                    <label class="add-active-label">
                                        <input type="checkbox" name="is_new_arrival" value="1" class="add-active-checkbox" <?= $isNewArrival ? "checked" : "" ?>>
                                        <span>New Arrival</span>
                                    </label>
                                </div>

                                <div class="add-active-box">
                                    <label class="add-active-label">
                                        <input type="checkbox" name="is_best_seller" value="1" class="add-active-checkbox" <?= $isBestSeller ? "checked" : "" ?>>
                                        <span>Best Seller</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="add-category-section sec-amber">
                            <div class="add-category-section-icon">S</div>
                            <div class="add-category-section-title">SEO Settings</div>
                        </div>

                        <div class="add-form-group">
                            <label for="metaTitle" class="add-form-label">Meta Title</label>
                            <input type="text" id="metaTitle" name="meta_title" class="add-form-input" value="<?= e($metaTitle) ?>" placeholder="SEO Title">
                        </div>

                        <div class="add-form-group">
                            <label for="metaDescription" class="add-form-label">Meta Description</label>
                            <input type="text" id="metaDescription" name="meta_description" class="add-form-input" value="<?= e($metaDescription) ?>" placeholder="SEO Description">
                        </div>

                    </div>
                </div>

                <div class="add-category-form-footer">
                    <a href="index.php" class="add-category-btn add-category-cancel">Cancel</a>
                    <button type="submit" class="add-category-btn add-category-save" id="saveProductBtn">
                        <span>✓</span>
                        <span>Save Product</span>
                    </button>
                </div>
            </form>

            <div class="shortcut-help-box" id="shortcutHelpBox">
                <div class="shortcut-help-title">
                    <span>⌨ Keyboard Shortcuts</span>
                    <small style="margin-left:auto; color:var(--text-mute);">Press H to show/hide</small>
                </div>
                <div class="shortcut-grid">
                    <div class="shortcut-item"><span class="shortcut-key">A</span><span class="shortcut-desc">Save Product</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">B</span><span class="shortcut-desc">Back to Products</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">N</span><span class="shortcut-desc">Focus Name</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">P</span><span class="shortcut-desc">Focus Price</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">I</span><span class="shortcut-desc">Upload Images</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">Esc</span><span class="shortcut-desc">Blur Field</span></div>
                </div>
            </div>

        </div>
    </section>
</main>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const nameInput = document.getElementById("productName");
        const slugInput = document.getElementById("productSlug");
        const priceInput = document.getElementById("basePrice");
        const metaTitleInput = document.getElementById("metaTitle");
        const shortDescInput = document.getElementById("productShortDesc");
        const metaDescInput = document.getElementById("metaDescription");
        const imageInput = document.getElementById("productImages");
        const imagePreviewGrid = document.getElementById("imagePreviewGrid");
        const form = document.getElementById("addProductForm");
        const saveBtn = document.getElementById("saveProductBtn");
        const shortcutBox = document.getElementById("shortcutHelpBox");

        let slugManuallyChanged = slugInput && slugInput.value.trim() !== "";

        // Auto Slug & Auto Meta Title
        if (nameInput && slugInput) {
            nameInput.addEventListener("input", function() {
                if (!slugManuallyChanged) {
                    slugInput.value = this.value
                        .toLowerCase()
                        .trim()
                        .replace(/[^a-z0-9]+/g, "-")
                        .replace(/^-+|-+$/g, "");
                }
                if (metaTitleInput && metaTitleInput.value.trim() === "") {
                    metaTitleInput.value = this.value.trim() ? this.value.trim() + " | GatewayLinen" : "";
                }
            });

            slugInput.addEventListener("input", function() {
                slugManuallyChanged = this.value.trim() !== "";
            });
        }

        if (shortDescInput && metaDescInput) {
            shortDescInput.addEventListener("input", function() {
                if (metaDescInput.value.trim() === "") {
                    metaDescInput.value = this.value.trim();
                }
            });
        }

        // Multiple Image Preview Handling
        if (imageInput && imagePreviewGrid) {
            imageInput.addEventListener("change", function() {
                imagePreviewGrid.innerHTML = "";
                const files = this.files;

                if (files && files.length > 0) {
                    Array.from(files).forEach((file, index) => {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const item = document.createElement("div");
                            item.className = "category-preview-item";
                            item.innerHTML = `
                            <img src="${e.target.result}" alt="Preview">
                            ${index === 0 ? '<span class="category-preview-badge">MAIN</span>' : ''}
                        `;
                            imagePreviewGrid.appendChild(item);
                        };
                        reader.readAsDataURL(file);
                    });
                }
            });
        }

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
                saveBtn.innerHTML = "<span>✓</span><span>Saving Product...</span>";
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
            } else if (key === "n" && nameInput) {
                e.preventDefault();
                nameInput.focus();
            } else if (key === "p" && priceInput) {
                e.preventDefault();
                priceInput.focus();
            } else if (key === "i" && imageInput) {
                e.preventDefault();
                imageInput.click();
            }
        });

        if (nameInput && window.innerWidth > 768) {
            setTimeout(() => nameInput.focus(), 150);
        }
    });
</script>

<?php
require_once __DIR__ . "/../includes/footer.php";
?>