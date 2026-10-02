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
$taxType          = "both"; // Options: 'both', 'gst_only', 'pst_only', 'none', 'custom'
$gstPercentage    = "5.00";
$pstPercentage    = "7.00";
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
| FETCH ALL CATEGORIES FOR CATEGORY PICKER
|--------------------------------------------------------------------------
| The product category field opens a searchable picker instead of a
| native <select>. Main categories and subcategories are both shown.
*/

$categories = [];
$categorySql = "
    SELECT
        c.CategoryId,
        c.Name,
        c.ParentCategoryId,
        p.Name AS ParentCategoryName,
        c.IsActive
    FROM dbo.Categories c
    INNER JOIN dbo.Categories p
        ON p.CategoryId = c.ParentCategoryId
    WHERE c.ParentCategoryId IS NOT NULL
    ORDER BY
        p.Name,
        c.DisplayOrder ASC,
        c.Name
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
        $taxType          = trim($_POST["tax_type"] ?? "both");
        // SEO Meta Title and Meta Description are intentionally not used on this form.
        $metaTitle        = "";
        $metaDescription  = "";

        // Handle tax percentages based on selected tax type
        if ($taxType === "gst_only") {
            $gstPercentage = trim($_POST["gst_percentage"] ?? "5.00");
            $pstPercentage = "0.00";
        } elseif ($taxType === "pst_only") {
            $gstPercentage = "0.00";
            $pstPercentage = trim($_POST["pst_percentage"] ?? "7.00");
        } elseif ($taxType === "none") {
            $gstPercentage = "0.00";
            $pstPercentage = "0.00";
        } else {
            // 'both' or 'custom'
            $gstPercentage = trim($_POST["gst_percentage"] ?? "5.00");
            $pstPercentage = trim($_POST["pst_percentage"] ?? "7.00");
        }

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

    /* Verify the selected category really exists. */
    if ($error === "" && !empty($categoryId)) {
        $categoryCheckSql = "SELECT TOP 1 CategoryId, ParentCategoryId FROM dbo.Categories WHERE CategoryId = ?";
        $categoryCheckStmt = sqlsrv_query($conn, $categoryCheckSql, [$categoryId]);

        if ($categoryCheckStmt === false) {
            $error = "Unable to verify the selected category.";
        } else {
            $categoryRow = sqlsrv_fetch_array($categoryCheckStmt, SQLSRV_FETCH_ASSOC);
            if (!$categoryRow) {
                $error = "The selected category does not exist.";
            } elseif (empty($categoryRow["ParentCategoryId"])) {
                $error = "Please select a subcategory. Main categories cannot be selected for products.";
            }
        }

        if ($categoryCheckStmt !== false) {
            sqlsrv_free_stmt($categoryCheckStmt);
        }
    }

    if ($error === "" && $name === "") {
        $error = "Product name is required.";
    }

    if ($error === "" && mb_strlen($name) < 2) {
        $error = "Product name must contain at least 2 characters.";
    }

    if ($error === "" && mb_strlen($name) > 150) {
        $error = "Product name cannot be longer than 150 characters.";
    }

    if ($error === "" && $slug === "") {
        $slug = strtolower(preg_replace("/[^a-zA-Z0-9]+/", "-", $name));
        $slug = trim($slug, "-");
    }

    if ($error === "" && $slug === "") {
        $error = "Please enter a valid product slug.";
    }

    if ($error === "" && !preg_match("/^[a-z0-9]+(?:-[a-z0-9]+)*$/", $slug)) {
        $error = "Slug may contain only lowercase letters, numbers and single hyphens.";
    }

    if ($error === "" && mb_strlen($slug) > 150) {
        $error = "Product slug cannot be longer than 150 characters.";
    }

    if ($error === "" && $shortDescription === "") {
        $error = "Short description is required.";
    }

    if ($error === "" && mb_strlen($shortDescription) < 5) {
        $error = "Short description must contain at least 5 characters.";
    }

    if ($error === "" && mb_strlen($shortDescription) > 300) {
        $error = "Short description cannot be longer than 300 characters.";
    }

    if ($error === "" && $description === "") {
        $error = "Full description is required.";
    }

    if ($error === "" && mb_strlen($description) < 10) {
        $error = "Full description must contain at least 10 characters.";
    }

    if ($error === "" && mb_strlen($description) > 2000) {
        $error = "Full description cannot be longer than 2,000 characters.";
    }

    if ($error === "" && (!is_numeric($basePrice) || !is_finite((float)$basePrice) || (float)$basePrice <= 0)) {
        $error = "Please enter a base price greater than 0.";
    }

    if ($error === "" && !in_array($taxType, ["both", "gst_only", "pst_only", "none", "custom"], true)) {
        $error = "Please select a valid tax configuration.";
    }

    if ($error === "" && (!is_numeric($gstPercentage) || (float)$gstPercentage < 0 || (float)$gstPercentage > 100)) {
        $error = "GST percentage must be between 0 and 100.";
    }

    if ($error === "" && (!is_numeric($pstPercentage) || (float)$pstPercentage < 0 || (float)$pstPercentage > 100)) {
        $error = "PST percentage must be between 0 and 100.";
    }

    if ($error === "" && $taxType === "gst_only" && (float)$gstPercentage <= 0) {
        $error = "GST percentage must be greater than 0 for GST Only.";
    }

    if ($error === "" && $taxType === "pst_only" && (float)$pstPercentage <= 0) {
        $error = "PST percentage must be greater than 0 for PST Only.";
    }

    if ($error === "" && $taxType === "both" && ((float)$gstPercentage <= 0 || (float)$pstPercentage <= 0)) {
        $error = "Both GST and PST percentages must be greater than 0.";
    }

    if ($error === "" && $taxType === "custom" && ((float)$gstPercentage < 0 || (float)$pstPercentage < 0)) {
        $error = "Custom tax percentages cannot be negative.";
    }

    if ($error === "" && $specifications === "") {
        $error = "Specifications are required.";
    }

    if ($error === "" && mb_strlen($specifications) < 5) {
        $error = "Specifications must contain at least 5 characters.";
    }

    if ($error === "" && mb_strlen($specifications) > 1500) {
        $error = "Specifications cannot be longer than 1,500 characters.";
    }

    if ($error === "" && $careInstructions === "") {
        $error = "Care instructions are required.";
    }

    if ($error === "" && mb_strlen($careInstructions) < 5) {
        $error = "Care instructions must contain at least 5 characters.";
    }

    if ($error === "" && mb_strlen($careInstructions) > 1000) {
        $error = "Care instructions cannot be longer than 1,000 characters.";
    }

    /*
    |--------------------------------------------------------------------------
    | NORMALIZE SLUG
    |--------------------------------------------------------------------------
    */
    if ($error === "") {
        $slug = strtolower(trim($slug));
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
                "tmp"     => $files["tmp_name"][$i],
                "dest"    => $physicalPath,
                "db_path" => $dbPath,
                "name"    => $files["name"][$i]
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | IMAGE REQUIRED
    |--------------------------------------------------------------------------
    */
    if ($error === "" && count($uploadedFiles) < 1) {
        $error = "Please upload at least one product image.";
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
        justify-content: space-between;
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

    .section-left-content {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .shortcut-badge {
        background: rgba(0,0,0,0.25);
        color: var(--text-hi);
        font-size: 9.5px;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 4px;
        font-family: monospace;
        letter-spacing: 0.5px;
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
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 7px;
        color: var(--text-body);
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .3px;
    }

    .tax-edit-btn {
        background: rgba(59, 130, 246, 0.1);
        border: 1px solid rgba(59, 130, 246, 0.3);
        color: var(--blue);
        cursor: pointer;
        font-size: 10px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 5px;
        text-transform: none;
        letter-spacing: 0;
        transition: all 0.2s ease;
    }

    .tax-edit-btn:hover {
        background: rgba(59, 130, 246, 0.2);
        border-color: var(--blue);
    }

    .tax-edit-btn.unlocked {
        background: rgba(16, 185, 129, 0.1);
        border-color: rgba(16, 185, 129, 0.3);
        color: var(--green);
    }

    .tax-edit-btn.unlocked:hover {
        background: rgba(16, 185, 129, 0.2);
        border-color: var(--green);
    }

    /* Live Tax Calculation Summary Box */
    .tax-summary-box {
        grid-column: 1 / -1;
        margin-top: 5px;
        padding: 12px 14px;
        background: rgba(16, 185, 129, 0.05);
        border: 1px solid rgba(16, 185, 129, 0.2);
        border-radius: 8px;
        font-size: 11.5px;
    }
    .tax-summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
        color: var(--text-body);
    }
    .tax-summary-row:last-child {
        margin-bottom: 0;
        padding-top: 6px;
        border-top: 1px dashed rgba(16, 185, 129, 0.2);
        font-weight: 800;
        color: var(--text-hi);
    }

    .add-form-required {
        color: var(--red);
    }

    .add-form-input,
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

    .add-form-input:read-only {
        background: rgba(15, 24, 35, 0.6);
        color: var(--text-mute);
        border-color: var(--border-soft);
        cursor: not-allowed;
    }

    .add-form-input {
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
    .add-form-textarea:focus {
        outline: none;
        border-color: var(--green);
        box-shadow: 0 0 0 3px rgba(16, 185, 129, .13);
    }

    /* Custom Checkbox Group for Tax Configuration instead of dropdown */
    .tax-checkbox-group {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 5px;
    }

    .tax-checkbox-box {
        display: flex;
        align-items: center;
        min-height: 42px;
        padding: 0 14px;
        border: 1px solid var(--border);
        border-radius: 9px;
        background: var(--bg-input);
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .tax-checkbox-box:hover {
        border-color: var(--green);
        background: var(--green-soft);
    }

    .tax-checkbox-label {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        color: var(--text-body);
        font-size: 11.5px;
        font-weight: 600;
        cursor: pointer;
        width: 100%;
    }

    .tax-checkbox-input {
        width: 17px;
        height: 17px;
        accent-color: var(--green);
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

    @media (max-width: 860px) {
        .add-category-grid {
            grid-template-columns: 1fr;
        }

        .add-form-group-full {
            grid-column: auto;
        }
        .tax-checkbox-group {
            grid-template-columns: 1fr;
        }
    }


/* ============================================================
   SEARCHABLE CATEGORY PICKER
   ============================================================ */
.category-picker-trigger {
    width: 100%;
    min-height: 54px;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: var(--bg-input);
    color: var(--text-hi);
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 15px;
    font: inherit;
    text-align: left;
    cursor: pointer;
    transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
}
.category-picker-trigger:hover,
.category-picker-trigger:focus {
    border-color: var(--green);
    outline: none;
    box-shadow: 0 0 0 3px rgba(16,185,129,.12);
}
.category-picker-icon {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    display: grid;
    place-items: center;
    background: var(--green-soft);
    color: var(--green);
    flex: 0 0 auto;
}
#selectedCategoryText {
    flex: 1;
    font-weight: 700;
}
.category-picker-arrow {
    color: var(--text-mute);
    font-size: 23px;
}
.category-selected-helper {
    margin-top: 7px;
    min-height: 15px;
    color: var(--text-mute);
    font-size: 11px;
    font-weight: 600;
}

.category-picker-modal {
    position: fixed;
    inset: 0;
    z-index: 99999;
    display: none;
}
.category-picker-modal.is-open {
    display: block;
}
.category-picker-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(2, 8, 15, .62);
    backdrop-filter: blur(4px);
}
.category-picker-dialog {
    position: relative;
    width: min(980px, calc(100vw - 36px));
    max-height: min(900px, calc(100vh - 28px));
    margin: 14px auto;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 16px;
    box-shadow: 0 28px 80px rgba(0,0,0,.30);
}
.category-picker-header {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    padding: 24px 28px 18px;
    border-bottom: 1px solid var(--border-soft);
}
.category-picker-title {
    color: var(--text-hi);
    font-size: 20px;
    font-weight: 900;
}
.category-picker-subtitle {
    margin-top: 5px;
    color: var(--text-mute);
    font-size: 12px;
}
.category-picker-close {
    margin-left: auto;
    width: 38px;
    height: 38px;
    border: 1px solid var(--border);
    border-radius: 9px;
    background: var(--bg-input);
    color: var(--text-hi);
    font-size: 25px;
    line-height: 1;
    cursor: pointer;
}
.category-picker-close:hover {
    border-color: var(--red);
    color: var(--red);
}
.category-picker-search-wrap {
    margin: 18px 28px 10px;
    position: relative;
}
.category-picker-search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-mute);
    font-size: 21px;
}
.category-picker-search {
    width: 100%;
    min-height: 58px;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: var(--bg-input);
    color: var(--text-hi);
    padding: 0 14px 0 42px;
    font: inherit;
    outline: none;
}
.category-picker-search:focus {
    border-color: var(--green);
    box-shadow: 0 0 0 3px rgba(16,185,129,.10);
}
.category-picker-count {
    padding: 0 28px 10px;
    color: var(--text-mute);
    font-size: 11px;
    font-weight: 700;
}
.category-picker-list {
    overflow-y: auto;
    padding: 0 12px 12px;
    min-height: 100px;
}
.category-picker-item {
    width: 100%;
    min-height: 62px;
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1px solid transparent;
    border-radius: 10px;
    background: transparent;
    color: var(--text-hi);
    padding: 8px 12px;
    margin-bottom: 5px;
    text-align: left;
    cursor: pointer;
}
.category-picker-item:hover,
.category-picker-item:focus {
    background: var(--green-soft);
    border-color: rgba(16,185,129,.25);
    outline: none;
}
.category-picker-item.is-main-category {
    background: rgba(59,130,246,.045);
}
.category-picker-item-icon {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    display: grid;
    place-items: center;
    background: var(--bg-hover);
    color: var(--green);
    font-weight: 900;
    flex: 0 0 auto;
}
.category-picker-item-content {
    min-width: 0;
    flex: 1;
}
.category-picker-item-name {
    display: block;
    color: var(--text-hi);
    font-size: 13px;
    font-weight: 800;
}
.category-picker-item-parent {
    display: block;
    margin-top: 3px;
    color: var(--text-mute);
    font-size: 10px;
    font-weight: 600;
}
.category-picker-item-check {
    opacity: 0;
    color: var(--green);
    font-size: 18px;
    font-weight: 900;
}
.category-picker-item.is-selected .category-picker-item-check {
    opacity: 1;
}
.category-picker-empty {
    padding: 35px 20px;
    text-align: center;
    color: var(--text-mute);
    font-size: 13px;
}
.category-picker-footer {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 13px 20px;
    border-top: 1px solid var(--border-soft);
    color: var(--text-mute);
    font-size: 11px;
}
.category-picker-footer kbd {
    padding: 2px 6px;
    border: 1px solid var(--border);
    border-radius: 5px;
    background: var(--bg-hover);
    color: var(--text-hi);
}
.category-picker-cancel {
    margin-left: auto;
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 8px 15px;
    background: var(--bg-input);
    color: var(--text-hi);
    cursor: pointer;
    font-weight: 700;
}
.category-picker-cancel:hover {
    border-color: var(--green);
    color: var(--green);
}
body.category-picker-open {
    overflow: hidden !important;
}
@media (max-width: 620px) {
    .category-picker-dialog {
        width: calc(100vw - 12px);
        max-height: calc(100vh - 12px);
        margin: 6px auto;
    }
    .category-picker-header {
        padding: 17px 14px 13px;
    }
    .category-picker-search-wrap {
        margin-left: 15px;
        margin-right: 15px;
    }
    .category-picker-count {
        padding-left: 15px;
        padding-right: 15px;
    }
}

/* ============================================================
   GATEWAYLINEN THEME ADAPTATION
   ============================================================ */
:root {
    --bg-page: #f5f7fb;
    --bg-card: #ffffff;
    --bg-card-alt: #f8fafc;
    --bg-input: #ffffff;
    --bg-hover: #f1f5f9;
    --border: #dbe3ec;
    --border-soft: #e7edf3;
    --text-hi: #172033;
    --text-body: #52627a;
    --text-mute: #7a8aa0;
    --green: #10b981;
    --green-dark: #059669;
    --green-soft: rgba(16, 185, 129, .10);
    --blue: #3b82f6;
    --blue-soft: rgba(59, 130, 246, .09);
    --purple: #8b5cf6;
    --purple-soft: rgba(139, 92, 246, .09);
    --amber: #f59e0b;
    --amber-soft: rgba(245, 158, 11, .10);
    --red: #ef4444;
    --red-soft: rgba(239, 68, 68, .08);
    --theme-shadow: 0 12px 32px rgba(15, 23, 42, .08);
    --theme-input-readonly: #f1f5f9;
    --theme-dashed: rgba(59, 130, 246, .35);
}

html,
body {
    background: #f5f7fb !important;
    color: #52627a !important;
    transition: background-color .2s ease, color .2s ease;
}

.main,
.content {
    background: #f5f7fb !important;
    color: #52627a !important;
}

.add-category-page {
    color: var(--text-body) !important;
}

.add-category-title,
.add-category-section-title {
    color: var(--text-hi) !important;
}

.add-category-subtitle,
.add-category-breadcrumb,
.add-category-back,
.add-form-label,
.add-active-label,
.tax-checkbox-label,
.tax-summary-row {
    color: var(--text-body) !important;
}

.add-category-breadcrumb .current {
    color: var(--green) !important;
}

.add-category-form {
    background: #ffffff !important;
    border-color: var(--border) !important;
    box-shadow: var(--theme-shadow) !important;
}

.add-category-form-body {
    background: #ffffff !important;
}

.add-category-form-footer {
    background: #f8fafc !important;
    border-color: var(--border) !important;
}

.add-form-input,
.add-form-textarea,
.add-active-box,
.tax-checkbox-box,
.add-category-back {
    background: #ffffff !important;
    color: var(--text-hi) !important;
    border-color: var(--border) !important;
}

.add-form-input::placeholder,
.add-form-textarea::placeholder {
    color: #98a6b8 !important;
}

.add-form-input:read-only {
    background: #f1f5f9 !important;
    color: #6d7d92 !important;
    border-color: #dbe3ec !important;
}

.tax-summary-box {
    background: rgba(16, 185, 129, .055) !important;
    border-color: rgba(16, 185, 129, .20) !important;
}

.category-upload-box {
    background: rgba(59, 130, 246, .035) !important;
    border-color: var(--theme-dashed) !important;
}

.category-preview-item {
    background: #f8fafc !important;
    border-color: var(--border) !important;
}

.add-category-back:hover {
    background: var(--green-soft) !important;
}

.add-category-error {
    color: #b42318 !important;
    background: rgba(239, 68, 68, .06) !important;
}

/* DARK MODE */
html[data-theme="dark"],
html.dark-mode,
body[data-theme="dark"],
body.dark-mode,
body.theme-dark {
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
    --theme-shadow: 0 15px 40px rgba(0, 0, 0, .18);
    --theme-input-readonly: rgba(15, 24, 35, .6);
    --theme-dashed: rgba(59, 130, 246, .38);
}

html[data-theme="dark"] body,
html.dark-mode body,
body[data-theme="dark"],
body.dark-mode,
body.theme-dark {
    background: #0a1119 !important;
    color: #a8b8c8 !important;
}

html[data-theme="dark"] .main,
html[data-theme="dark"] .content,
html.dark-mode .main,
html.dark-mode .content,
body[data-theme="dark"] .main,
body[data-theme="dark"] .content,
body.dark-mode .main,
body.dark-mode .content,
body.theme-dark .main,
body.theme-dark .content {
    background: #0a1119 !important;
    color: #a8b8c8 !important;
}

html[data-theme="dark"] .add-category-form,
html[data-theme="dark"] .add-category-form-body,
html.dark-mode .add-category-form,
html.dark-mode .add-category-form-body,
body[data-theme="dark"] .add-category-form,
body[data-theme="dark"] .add-category-form-body,
body.dark-mode .add-category-form,
body.dark-mode .add-category-form-body,
body.theme-dark .add-category-form,
body.theme-dark .add-category-form-body {
    background: #111b26 !important;
}

html[data-theme="dark"] .add-category-form-footer,
html.dark-mode .add-category-form-footer,
body[data-theme="dark"] .add-category-form-footer,
body.dark-mode .add-category-form-footer,
body.theme-dark .add-category-form-footer {
    background: #0f1823 !important;
}

html[data-theme="dark"] .add-form-input,
html[data-theme="dark"] .add-form-textarea,
html[data-theme="dark"] .add-active-box,
html[data-theme="dark"] .tax-checkbox-box,
html[data-theme="dark"] .add-category-back,
html.dark-mode .add-form-input,
html.dark-mode .add-form-textarea,
html.dark-mode .add-active-box,
html.dark-mode .tax-checkbox-box,
html.dark-mode .add-category-back,
body[data-theme="dark"] .add-form-input,
body[data-theme="dark"] .add-form-textarea,
body[data-theme="dark"] .add-active-box,
body[data-theme="dark"] .tax-checkbox-box,
body[data-theme="dark"] .add-category-back,
body.dark-mode .add-form-input,
body.dark-mode .add-form-textarea,
body.dark-mode .add-active-box,
body.dark-mode .tax-checkbox-box,
body.dark-mode .add-category-back,
body.theme-dark .add-form-input,
body.theme-dark .add-form-textarea,
body.theme-dark .add-active-box,
body.theme-dark .tax-checkbox-box,
body.theme-dark .add-category-back {
    background: #0d1620 !important;
    color: #f0f4f8 !important;
    border-color: #1e2d3d !important;
}

html[data-theme="dark"] .add-form-input:read-only,
html.dark-mode .add-form-input:read-only,
body[data-theme="dark"] .add-form-input:read-only,
body.dark-mode .add-form-input:read-only,
body.theme-dark .add-form-input:read-only {
    background: rgba(15, 24, 35, .6) !important;
    color: #5f7488 !important;
    border-color: #182636 !important;
}

html[data-theme="dark"] .category-preview-item,
html.dark-mode .category-preview-item,
body[data-theme="dark"] .category-preview-item,
body.dark-mode .category-preview-item,
body.theme-dark .category-preview-item {
    background: #0f1823 !important;
    border-color: #1e2d3d !important;
}

html[data-theme="dark"] .add-category-error,
html.dark-mode .add-category-error,
body[data-theme="dark"] .add-category-error,
body.dark-mode .add-category-error,
body.theme-dark .add-category-error {
    color: #fca5a5 !important;
    background: rgba(239, 68, 68, .12) !important;
}

@media (max-width: 860px) {
    .add-category-page {
        padding: 16px 12px 35px;
    }

    .add-category-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .add-category-back {
        width: 100%;
        justify-content: center;
    }

    .add-category-form-body {
        padding: 18px 14px;
    }

    .add-category-form-footer {
        padding: 14px;
    }
}


/* ============================================================
   FINAL SIMPLE PRODUCT FORM POLISH
   ============================================================ */
.field-help-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-top: 5px;
    padding: 0 2px;
    color: var(--text-mute);
    font-size: 10.5px;
    line-height: 1.35;
}
.field-help-row span:last-child {
    font-weight: 700;
    white-space: nowrap;
}
.field-help-row .limit-warning {
    color: #d97706;
}
.field-help-row .limit-error {
    color: #dc2626;
}

.category-picker-header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}
.category-picker-add-category {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 40px;
    padding: 0 13px;
    border: 1px solid rgba(16,185,129,.30);
    border-radius: 9px;
    background: var(--green-soft);
    color: var(--green-dark);
    text-decoration: none;
    font-size: 11px;
    font-weight: 800;
}
.category-picker-add-category:hover {
    background: rgba(16,185,129,.16);
}
html[data-theme="dark"] .category-picker-add-category,
html.dark-mode .category-picker-add-category,
body[data-theme="dark"] .category-picker-add-category,
body.dark-mode .category-picker-add-category,
body.theme-dark .category-picker-add-category {
    color: #6ee7b7;
}
.category-picker-result-note {
    color: var(--text-mute);
    font-size: 10.5px;
    font-weight: 600;
}

.category-picker-list {
    min-height: 120px;
    max-height: min(64vh, 610px);
}
.category-picker-dialog {
    max-height: min(94vh, 900px);
}
@media (max-width: 640px) {
    .category-picker-header {
        align-items: flex-start;
    }
    .category-picker-header-actions {
        gap: 5px;
    }
    .category-picker-add-category {
        padding: 0 9px;
    }
    .category-picker-add-category span:last-child {
        display: none;
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
                        Create a product with category, pricing, tax, images, specifications, care instructions, and visibility settings.
                    </p>
                </div>
                <a href="index.php" class="add-category-back">
                    <span>←</span>
                    <span>Back to Products [Esc]</span>
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
                <input type="hidden" id="taxType" name="tax_type" value="<?= e($taxType) ?>">

                <div class="add-category-form-body">
                    <div class="add-category-grid">

                        <div class="add-category-section">
                            <div class="section-left-content">
                                <div class="add-category-section-icon">#</div>
                                <div class="add-category-section-title">Basic Information</div>
                            </div>
                            <span class="shortcut-badge">SHORTCUT: C</span>
                        </div>

                        <div class="add-form-group">
                            <label for="productCategoryDisplay" class="add-form-label">
                                <span>Category <span class="add-form-required">*</span></span>
                            </label>

                            <input type="hidden"
                                   id="productCategory"
                                   name="category_id"
                                   value="<?= e($categoryId) ?>">

                            <button type="button"
                                    class="category-picker-trigger"
                                    id="productCategoryDisplay"
                                    aria-haspopup="dialog"
                                    aria-controls="categoryPickerModal">
                                <span class="category-picker-icon">▣</span>
                                <span id="selectedCategoryText">
                                    <?php
                                    $selectedCategoryName = "";
                                    $selectedParentName = "";
                                    if (!empty($categoryId)) {
                                        foreach ($categories as $cat) {
                                            if ((string)$cat["CategoryId"] === (string)$categoryId) {
                                                $selectedCategoryName = $cat["Name"];
                                                $selectedParentName = $cat["ParentCategoryName"] ?? "";
                                                break;
                                            }
                                        }
                                    }
                                    ?>
                                    <?= $selectedCategoryName !== "" ? e($selectedCategoryName) : "Click or press C to select subcategory" ?>
                                </span>
                                <span class="category-picker-arrow">⌄</span>
                            </button>

                            <div class="category-selected-helper" id="selectedCategoryHelper">
                                <?php if ($selectedCategoryName !== ""): ?>
                                    <?= $selectedParentName !== "" ? e($selectedParentName) . " → " : "" ?><?= e($selectedCategoryName) ?>
                                <?php else: ?>
                                    No subcategory selected
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="add-form-group">
                            <label for="productName" class="add-form-label">
                                <span>Product Name <span class="add-form-required">*</span></span>
                                <span class="shortcut-badge">SHORTCUT: N</span>
                            </label>
                            <input type="text" id="productName" name="name" class="add-form-input" value="<?= e($name) ?>" placeholder="e.g. Premium Bath Towel" maxlength="150" minlength="2" required>
                        </div>

                        <div class="add-form-group">
                            <label for="productSlug" class="add-form-label">
                                <span>Slug</span>
                                <span class="shortcut-badge">SHORTCUT: G</span>
                            </label>
                            <input type="text" id="productSlug" name="slug" class="add-form-input" value="<?= e($slug) ?>" placeholder="premium-bath-towel" maxlength="150" minlength="2" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" required>
                        </div>

                        <div class="add-form-group">
                            <label for="productShortDesc" class="add-form-label"><span>Short Description <span class="add-form-required">*</span></span></label>
                            <input type="text" id="productShortDesc" name="short_description" class="add-form-input" value="<?= e($shortDescription) ?>" placeholder="Brief 1-line overview of product" maxlength="300" minlength="5" required>
                            <div class="field-help-row"><span>Maximum 300 characters.</span><span id="shortDescCounter">0 / 300</span></div>
                        </div>

                        <div class="add-form-group add-form-group-full">
                            <label for="productDesc" class="add-form-label"><span>Full Description <span class="add-form-required">*</span></span></label>
                            <textarea id="productDesc" name="description" class="add-form-textarea" placeholder="Detailed product overview..." maxlength="2000" minlength="10" required><?= e($description) ?></textarea>
                            <div class="field-help-row"><span>Maximum 2,000 characters.</span><span id="descriptionCounter">0 / 2000</span></div>
                        </div>

                        <div class="add-category-section sec-amber">
                            <div class="section-left-content">
                                <div class="add-category-section-icon">$</div>
                                <div class="add-category-section-title">Pricing & Taxation</div>
                            </div>
                        </div>

                        <div class="add-form-group">
                            <label for="basePrice" class="add-form-label"><span>Base Price ($) <span class="add-form-required">*</span></span></label>
                            <input type="number" step="0.01" min="0" id="basePrice" name="base_price" class="add-form-input" value="<?= e($basePrice) ?>" required>
                        </div>

                        <div class="add-form-group">
                            <label class="add-form-label"><span>Tax Configuration (Checkboxes)</span></label>
                            <div class="tax-checkbox-group">
                                <div class="tax-checkbox-box">
                                    <label class="tax-checkbox-label">
                                        <input type="checkbox" id="taxGstCheck" class="tax-checkbox-input" <?= ($taxType === 'both' || $taxType === 'gst_only' || $taxType === 'custom') ? 'checked' : '' ?>>
                                        <span>Apply GST (5%)</span>
                                    </label>
                                </div>
                                <div class="tax-checkbox-box">
                                    <label class="tax-checkbox-label">
                                        <input type="checkbox" id="taxPstCheck" class="tax-checkbox-input" <?= ($taxType === 'both' || $taxType === 'pst_only' || $taxType === 'custom') ? 'checked' : '' ?>>
                                        <span>Apply PST (7%)</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="add-form-group">
                            <label for="gstPercentage" class="add-form-label">
                                <span>GST Percentage (%)</span>
                                <button type="button" class="tax-edit-btn" id="editGstBtn">🔒 Unlock</button>
                            </label>
                            <input type="number" step="0.01" min="0" id="gstPercentage" name="gst_percentage" class="add-form-input" value="<?= e($gstPercentage) ?>" readonly>
                        </div>

                        <div class="add-form-group">
                            <label for="pstPercentage" class="add-form-label">
                                <span>PST Percentage (%)</span>
                                <button type="button" class="tax-edit-btn" id="editPstBtn">🔒 Unlock</button>
                            </label>
                            <input type="number" step="0.01" min="0" id="pstPercentage" name="pst_percentage" class="add-form-input" value="<?= e($pstPercentage) ?>" readonly>
                        </div>

                        <!-- LIVE TAX & TOTAL CALCULATION SUMMARY -->
                        <div class="tax-summary-box">
                            <div class="tax-summary-row">
                                <span>GST Amount:</span>
                                <span id="summaryGstAmount">$0.00</span>
                            </div>
                            <div class="tax-summary-row">
                                <span>PST Amount:</span>
                                <span id="summaryPstAmount">$0.00</span>
                            </div>
                            <div class="tax-summary-row">
                                <span>Grand Total (Incl. Tax):</span>
                                <span id="summaryGrandTotal">$0.00</span>
                            </div>
                        </div>

                        <div class="add-category-section sec-purple">
                            <div class="section-left-content">
                                <div class="add-category-section-icon">≡</div>
                                <div class="add-category-section-title">Specifications & Care</div>
                            </div>
                        </div>

                        <div class="add-form-group">
                            <label for="productSpecs" class="add-form-label"><span>Specifications <span class="add-form-required">*</span></span></label>
                            <textarea id="productSpecs" name="specifications" class="add-form-textarea" placeholder="e.g. 100% Cotton, 600 GSM, Hotel Quality" maxlength="1500" minlength="5" required><?= e($specifications) ?></textarea>
                            <div class="field-help-row"><span>Maximum 1,500 characters.</span><span id="specCounter">0 / 1500</span></div>
                        </div>

                        <div class="add-form-group">
                            <label for="careInstructions" class="add-form-label"><span>Care Instructions <span class="add-form-required">*</span></span></label>
                            <textarea id="careInstructions" name="care_instructions" class="add-form-textarea" placeholder="e.g. Machine wash cold. Do not bleach." maxlength="1000" minlength="5" required><?= e($careInstructions) ?></textarea>
                            <div class="field-help-row"><span>Maximum 1,000 characters.</span><span id="careCounter">0 / 1000</span></div>
                        </div>

                        <div class="add-category-section sec-blue">
                            <div class="section-left-content">
                                <div class="add-category-section-icon">↑</div>
                                <div class="add-category-section-title">Product Images (dbo.ProductImages)</div>
                            </div>
                            <span class="shortcut-badge">SHORTCUT: I</span>
                        </div>

                        <div class="add-form-group add-form-group-full">
                            <label for="productImages" class="category-upload-box" id="imageUploadBox">
                                <input type="file" id="productImages" name="product_images[]" class="category-upload-input" multiple accept=".jpg,.jpeg,.png,.webp,.gif">
                                <div class="category-upload-icon">📁</div>
                                <div style="color:var(--text-hi); font-size:13px; font-weight:700;">
                                    Click or press <kbd style="background:var(--bg-input); padding:2px 6px; border:1px solid var(--border); border-radius:4px; color:var(--green);">I</kbd> to upload product images
                                </div>
                                <div style="color:var(--text-mute); font-size:10px; margin-top:5px;">
                                    First uploaded image will be set as Main Image (IsMain = 1). Max 5MB each. At least 1 image is required.
                                </div>
                            </label>
                            <div class="category-preview-grid" id="imagePreviewGrid"></div>
                        </div>

                        <div class="add-category-section">
                            <div class="section-left-content">
                                <div class="add-category-section-icon">✓</div>
                                <div class="add-category-section-title">Flags & Visibility</div>
                            </div>
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

                    </div>
                </div>

                <!-- CATEGORY PICKER MODAL -->
                <div class="category-picker-modal" id="categoryPickerModal" aria-hidden="true">
                    <div class="category-picker-backdrop" data-category-close></div>

                    <div class="category-picker-dialog"
                         role="dialog"
                         aria-modal="true"
                         aria-labelledby="categoryPickerTitle">

                        <div class="category-picker-header">
                            <div>
                                <div class="category-picker-title" id="categoryPickerTitle">Select Subcategory</div>
                                <div class="category-picker-subtitle">
                                    Select a subcategory for this product. Main categories are not shown.
                                </div>
                            </div>
                            <div class="category-picker-header-actions">
                                <a href="../categories/add.php"
                                   class="category-picker-add-category"
                                   target="_blank"
                                   rel="noopener">
                                    <span>+</span>
                                    <span>Add Category</span>
                                </a>
                                <button type="button" class="category-picker-close" id="categoryPickerClose" aria-label="Close">
                                    ×
                                </button>
                            </div>
                        </div>

                        <div class="category-picker-search-wrap">
                            <span class="category-picker-search-icon">⌕</span>
                            <input type="search"
                                   id="categoryPickerSearch"
                                   class="category-picker-search"
                                   placeholder="Search category..."
                                   autocomplete="off">
                        </div>

                        <div class="category-picker-count" id="categoryPickerCount"></div>

                        <div class="category-picker-list" id="categoryPickerList">
                            <?php foreach ($categories as $cat): ?>
                                <?php
                                    $catId = (int)$cat["CategoryId"];
                                    $catName = (string)$cat["Name"];
                                    $parentName = (string)($cat["ParentCategoryName"] ?? "");
                                    $isMain = empty($cat["ParentCategoryId"]);
                                ?>
                                <button type="button"
                                        class="category-picker-item is-sub-category"
                                        data-category-id="<?= $catId ?>"
                                        data-category-name="<?= e($catName) ?>"
                                        data-parent-name="<?= e($parentName) ?>"
                                        data-search="<?= e(strtolower($catName . " " . $parentName)) ?>">
                                    <span class="category-picker-item-icon">└</span>
                                    <span class="category-picker-item-content">
                                        <span class="category-picker-item-name"><?= e($catName) ?></span>
                                        <span class="category-picker-item-parent"><?= e($parentName) ?></span>
                                    </span>
                                    <span class="category-picker-item-check">✓</span>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <div class="category-picker-empty" id="categoryPickerEmpty" hidden>
                            No subcategories found.
                        </div>

                        <div class="category-picker-footer">
                            <span class="category-picker-result-note" id="categoryPickerResultNote">Select one subcategory</span>
                            <button type="button" class="category-picker-cancel" data-category-close>Cancel</button>
                        </div>
                    </div>
                </div>

                <div class="add-category-form-footer">
                    <a href="index.php" class="add-category-btn add-category-cancel">Cancel [C]</a>
                    <button type="submit" class="add-category-btn add-category-save" id="saveProductBtn">
                        <span>✓</span>
                        <span>Save Product [A]</span>
                    </button>
                </div>
            </form>

        </div>
    </section>
</main>


<script>
/* ============================================================
   GATEWAYLINEN THEME BRIDGE
   ============================================================ */
(function () {
    function getThemeState() {
        const html = document.documentElement;
        const body = document.body;

        const dark =
            html.getAttribute("data-theme") === "dark" ||
            html.classList.contains("dark-mode") ||
            body.getAttribute("data-theme") === "dark" ||
            body.classList.contains("dark-mode") ||
            body.classList.contains("theme-dark");

        return dark ? "dark" : "light";
    }

    function syncProductPageTheme() {
        const theme = getThemeState();
        document.documentElement.setAttribute("data-product-theme", theme);
        document.body.setAttribute("data-product-theme", theme);
    }

    syncProductPageTheme();

    const observer = new MutationObserver(function () {
        syncProductPageTheme();
    });

    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ["class", "data-theme"]
    });

    observer.observe(document.body, {
        attributes: true,
        attributeFilter: ["class", "data-theme"]
    });
})();
</script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const nameInput = document.getElementById("productName");
        const slugInput = document.getElementById("productSlug");
        const priceInput = document.getElementById("basePrice");
        const taxTypeInput = document.getElementById("taxType");
        const taxGstCheck = document.getElementById("taxGstCheck");
        const taxPstCheck = document.getElementById("taxPstCheck");
        const gstInput = document.getElementById("gstPercentage");
        const pstInput = document.getElementById("pstPercentage");
        const editGstBtn = document.getElementById("editGstBtn");
        const editPstBtn = document.getElementById("editPstBtn");
        const shortDescInput = document.getElementById("productShortDesc");
        const imageInput = document.getElementById("productImages");
        const imagePreviewGrid = document.getElementById("imagePreviewGrid");
        const form = document.getElementById("addProductForm");
        const saveBtn = document.getElementById("saveProductBtn");

        const summaryGstAmount = document.getElementById("summaryGstAmount");
        const summaryPstAmount = document.getElementById("summaryPstAmount");
        const summaryGrandTotal = document.getElementById("summaryGrandTotal");


        /* ============================================================
           SEARCHABLE CATEGORY PICKER
           ============================================================ */
        const categoryInput = document.getElementById("productCategory");
        const categoryDisplay = document.getElementById("productCategoryDisplay");
        const selectedCategoryText = document.getElementById("selectedCategoryText");
        const selectedCategoryHelper = document.getElementById("selectedCategoryHelper");
        const categoryModal = document.getElementById("categoryPickerModal");
        const categoryModalClose = document.getElementById("categoryPickerClose");
        const categorySearch = document.getElementById("categoryPickerSearch");
        const categoryList = document.getElementById("categoryPickerList");
        const categoryEmpty = document.getElementById("categoryPickerEmpty");
        const categoryCount = document.getElementById("categoryPickerCount");
        const categoryItems = Array.from(document.querySelectorAll(".category-picker-item"));

        function openCategoryPicker() {
            if (!categoryModal) return;

            categoryModal.classList.add("is-open");
            categoryModal.setAttribute("aria-hidden", "false");
            document.body.classList.add("category-picker-open");

            if (categorySearch) {
                categorySearch.value = "";
                filterCategories("");
                setTimeout(() => categorySearch.focus(), 50);
            }
        }

        function closeCategoryPicker() {
            if (!categoryModal) return;

            categoryModal.classList.remove("is-open");
            categoryModal.setAttribute("aria-hidden", "true");
            document.body.classList.remove("category-picker-open");
        }

        function filterCategories(term) {
            const q = String(term || "").trim().toLowerCase();
            let visible = 0;

            categoryItems.forEach(function (item) {
                const haystack = item.getAttribute("data-search") || "";
                const match = q === "" || haystack.includes(q);
                item.style.display = match ? "flex" : "none";
                if (match) visible++;
            });

            if (categoryCount) {
                categoryCount.textContent =
                    visible + " subcategor" + (visible === 1 ? "y" : "ies") + " available";
            }

            if (categoryEmpty) {
                categoryEmpty.hidden = visible !== 0;
            }
        }

        function markSelectedCategory() {
            const selectedId = String(categoryInput ? categoryInput.value : "");
            categoryItems.forEach(function (item) {
                item.classList.toggle(
                    "is-selected",
                    String(item.getAttribute("data-category-id")) === selectedId
                );
            });
        }

        if (categoryDisplay) {
            categoryDisplay.addEventListener("click", openCategoryPicker);
        }

        if (categoryModalClose) {
            categoryModalClose.addEventListener("click", closeCategoryPicker);
        }

        document.querySelectorAll("[data-category-close]").forEach(function (el) {
            el.addEventListener("click", closeCategoryPicker);
        });

        categoryItems.forEach(function (item) {
            item.addEventListener("click", function () {
                const id = item.getAttribute("data-category-id") || "";
                const name = item.getAttribute("data-category-name") || "";
                const parent = item.getAttribute("data-parent-name") || "";

                if (categoryInput) categoryInput.value = id;
                if (selectedCategoryText) selectedCategoryText.textContent = name;
                if (selectedCategoryHelper) {
                    selectedCategoryHelper.textContent =
                        parent ? parent + " → " + name : name;
                }

                markSelectedCategory();
                closeCategoryPicker();

                if (categoryDisplay) {
                    categoryDisplay.focus();
                }
            });
        });

        if (categorySearch) {
            categorySearch.addEventListener("input", function () {
                filterCategories(categorySearch.value);
            });
        }

        markSelectedCategory();
        filterCategories("");

        /* ============================================================
           COMPLETE CLIENT-SIDE VALIDATION
           ============================================================ */
        function clearFieldValidity(el) {
            if (el) el.setCustomValidity("");
        }

        function validateProductForm() {
            let firstInvalid = null;

            if (!categoryInput || !categoryInput.value) {
                if (categoryDisplay) {
                    categoryDisplay.setCustomValidity("Please select a category.");
                    firstInvalid = firstInvalid || categoryDisplay;
                }
            } else if (categoryDisplay) {
                categoryDisplay.setCustomValidity("");
            }

            const requiredTextFields = [
                {el: nameInput, label: "Product name", min: 2},
                {el: slugInput, label: "Slug", min: 2},
                {el: shortDescInput, label: "Short description", min: 5, max: 300},
                {el: document.getElementById("productDesc"), label: "Full description", min: 10, max: 2000},
                {el: document.getElementById("productSpecs"), label: "Specifications", min: 5, max: 1500},
                {el: document.getElementById("careInstructions"), label: "Care instructions", min: 5, max: 1000}
            ];

            requiredTextFields.forEach(function (field) {
                if (!field.el) return;

                const value = String(field.el.value || "").trim();

                if (!value) {
                    field.el.setCustomValidity(field.label + " is required.");
                    firstInvalid = firstInvalid || field.el;
                } else if (value.length < field.min) {
                    field.el.setCustomValidity(
                        field.label + " must contain at least " + field.min + " characters."
                    );
                    firstInvalid = firstInvalid || field.el;
                } else if (field.max && value.length > field.max) {
                    field.el.setCustomValidity(
                        field.label + " cannot be longer than " + field.max.toLocaleString() + " characters."
                    );
                    firstInvalid = firstInvalid || field.el;
                } else {
                    clearFieldValidity(field.el);
                }
            });

            if (slugInput && slugInput.value.trim()) {
                const slug = slugInput.value.trim().toLowerCase();
                if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug)) {
                    slugInput.setCustomValidity(
                        "Slug may contain only lowercase letters, numbers and single hyphens."
                    );
                    firstInvalid = firstInvalid || slugInput;
                }
            }

            if (priceInput) {
                const price = Number(priceInput.value);
                if (!Number.isFinite(price) || price <= 0) {
                    priceInput.setCustomValidity("Base price must be greater than 0.");
                    firstInvalid = firstInvalid || priceInput;
                } else {
                    clearFieldValidity(priceInput);
                }
            }

            if (imageInput) {
                if (!imageInput.files || imageInput.files.length < 1) {
                    imageInput.setCustomValidity("Please upload at least one product image.");
                    firstInvalid = firstInvalid || imageInput;
                } else {
                    clearFieldValidity(imageInput);
                }
            }

            if (firstInvalid) {
                firstInvalid.focus();
                return false;
            }

            return true;
        }

        [
            categoryDisplay,
            nameInput,
            slugInput,
            shortDescInput,
            document.getElementById("productDesc"),
            document.getElementById("productSpecs"),
            document.getElementById("careInstructions"),
            priceInput,
            gstInput,
            pstInput,
            imageInput
        ].forEach(function (el) {
            if (el) {
                el.addEventListener("input", function () {
                    clearFieldValidity(el);
                });
                el.addEventListener("change", function () {
                    clearFieldValidity(el);
                });
            }
        });

        if (form) {
            form.addEventListener("submit", function (event) {
                if (!validateProductForm()) {
                    event.preventDefault();
                    event.stopPropagation();

                    const invalid = form.querySelector(":invalid");
                    if (invalid && typeof invalid.reportValidity === "function") {
                        invalid.reportValidity();
                    }
                    return false;
                }
            });
        }

        let customGstValue = null;
        let customPstValue = null;

        function calculateTaxSummary() {
            const basePrice = parseFloat(priceInput.value) || 0;
            const gstRate = parseFloat(gstInput.value) || 0;
            const pstRate = parseFloat(pstInput.value) || 0;

            const gstAmt = (basePrice * gstRate) / 100;
            const pstAmt = (basePrice * pstRate) / 100;
            const grandTotal = basePrice + gstAmt + pstAmt;

            summaryGstAmount.textContent = `$${gstAmt.toFixed(2)} (${gstRate}%)`;
            summaryPstAmount.textContent = `$${pstAmt.toFixed(2)} (${pstRate}%)`;
            summaryGrandTotal.textContent = `$${grandTotal.toFixed(2)}`;
        }

        function updateTaxCheckboxes() {
            const hasGst = taxGstCheck && taxGstCheck.checked;
            const hasPst = taxPstCheck && taxPstCheck.checked;

            if (hasGst && hasPst) {
                taxTypeInput.value = "both";
                gstInput.value = customGstValue !== null ? customGstValue : "5.00";
                pstInput.value = customPstValue !== null ? customPstValue : "7.00";
            } else if (hasGst && !hasPst) {
                taxTypeInput.value = "gst_only";
                gstInput.value = customGstValue !== null ? customGstValue : "5.00";
                pstInput.value = "0.00";
            } else if (!hasGst && hasPst) {
                taxTypeInput.value = "pst_only";
                gstInput.value = "0.00";
                pstInput.value = customPstValue !== null ? customPstValue : "7.00";
            } else {
                taxTypeInput.value = "none";
                gstInput.value = "0.00";
                pstInput.value = "0.00";
            }
            calculateTaxSummary();
        }

        if (taxGstCheck) {
            taxGstCheck.addEventListener("change", updateTaxCheckboxes);
        }
        if (taxPstCheck) {
            taxPstCheck.addEventListener("change", updateTaxCheckboxes);
        }

        if (priceInput) {
            priceInput.addEventListener("input", calculateTaxSummary);
        }

        if (editGstBtn && gstInput) {
            editGstBtn.addEventListener("click", function() {
                if (gstInput.hasAttribute("readonly")) {
                    gstInput.removeAttribute("readonly");
                    gstInput.focus();
                    editGstBtn.textContent = "🔓 Lock";
                    editGstBtn.classList.add("unlocked");
                } else {
                    gstInput.setAttribute("readonly", "readonly");
                    editGstBtn.textContent = "🔒 Unlock";
                    editGstBtn.classList.remove("unlocked");
                    customGstValue = gstInput.value;
                    taxTypeInput.value = "custom";
                    calculateTaxSummary();
                }
            });
        }

        if (gstInput) {
            gstInput.addEventListener("input", function() {
                if (!gstInput.hasAttribute("readonly")) {
                    customGstValue = gstInput.value;
                    taxTypeInput.value = "custom";
                    calculateTaxSummary();
                }
            });
        }

        if (editPstBtn && pstInput) {
            editPstBtn.addEventListener("click", function() {
                if (pstInput.hasAttribute("readonly")) {
                    pstInput.removeAttribute("readonly");
                    pstInput.focus();
                    editPstBtn.textContent = "🔓 Lock";
                    editPstBtn.classList.add("unlocked");
                } else {
                    pstInput.setAttribute("readonly", "readonly");
                    editPstBtn.textContent = "🔒 Unlock";
                    editPstBtn.classList.remove("unlocked");
                    customPstValue = pstInput.value;
                    taxTypeInput.value = "custom";
                    calculateTaxSummary();
                }
            });
        }

        if (pstInput) {
            pstInput.addEventListener("input", function() {
                if (!pstInput.hasAttribute("readonly")) {
                    customPstValue = pstInput.value;
                    taxTypeInput.value = "custom";
                    calculateTaxSummary();
                }
            });
        }

        calculateTaxSummary();

        let slugManuallyChanged = slugInput && slugInput.value.trim() !== "";

        if (nameInput && slugInput) {
            nameInput.addEventListener("input", function() {
                if (!slugManuallyChanged) {
                    slugInput.value = this.value
                        .toLowerCase()
                        .trim()
                        .replace(/[^a-z0-9]+/g, "-")
                        .replace(/^-+|-+$/g, "");
                }
            });

            slugInput.addEventListener("input", function() {
                slugManuallyChanged = this.value.trim() !== "";
            });
        }

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

        function bindCounter(inputId, counterId, max) {
            const input = document.getElementById(inputId);
            const counter = document.getElementById(counterId);
            if (!input || !counter) return;

            function update() {
                const length = String(input.value || "").length;
                counter.textContent = length + " / " + max;
                counter.classList.toggle("limit-warning", length > max * 0.9);
                counter.classList.toggle("limit-error", length > max);
            }

            input.addEventListener("input", update);
            update();
        }

        bindCounter("productShortDesc", "shortDescCounter", 300);
        bindCounter("productDesc", "descriptionCounter", 2000);
        bindCounter("productSpecs", "specCounter", 1500);
        bindCounter("careInstructions", "careCounter", 1000);

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

        // Single-key Shortcuts matching the category page style (C, N, G, I, A, Esc)
        document.addEventListener("keydown", function(e) {
            const target = e.target;
            const isInputActive = target && (target.tagName === "INPUT" || target.tagName === "TEXTAREA" || target.tagName === "SELECT");

            if (e.key === "Escape") {
                if (categoryModal && categoryModal.classList.contains("is-open")) {
                    closeCategoryPicker();
                    e.preventDefault();
                } else if (isInputActive) {
                    target.blur();
                    e.preventDefault();
                } else {
                    window.location.href = "index.php";
                    e.preventDefault();
                }
                return;
            }

            // Do not trigger single-letter shortcuts if user is typing inside an input/textarea
            if (isInputActive) {
                return;
            }

            const key = e.key.toLowerCase();

            if (key === "c") {
                // Shortcut C: Open Category Picker Modal (or cancel if modal is open)
                e.preventDefault();
                if (categoryModal && categoryModal.classList.contains("is-open")) {
                    closeCategoryPicker();
                } else {
                    openCategoryPicker();
                }
            } else if (key === "n") {
                // Shortcut N: Focus Product Name
                e.preventDefault();
                if (nameInput) {
                    nameInput.focus();
                    nameInput.select();
                }
            } else if (key === "g") {
                // Shortcut G: Focus Slug
                e.preventDefault();
                if (slugInput) {
                    slugInput.focus();
                    slugInput.select();
                }
            } else if (key === "i") {
                // Shortcut I: Click Image Upload Box
                e.preventDefault();
                if (imageInput) {
                    imageInput.click();
                }
            } else if (key === "a") {
                // Shortcut A: Save Product Form
                e.preventDefault();
                if (saveBtn && !saveBtn.disabled) {
                    form.requestSubmit ? form.requestSubmit() : form.submit();
                }
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