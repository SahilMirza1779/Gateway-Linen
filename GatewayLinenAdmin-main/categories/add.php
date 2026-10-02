<?php

session_start();

/*
|--------------------------------------------------------------------------
| GatewayLinen Admin - Add Category
|--------------------------------------------------------------------------
| File:
| GatewayLinenadmin/categories/add.php
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

$activeMenu = "categories";
$pageTitle  = "GatewayLinen | Add Category";


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

if (empty($_SESSION["category_csrf_token"])) {

    $_SESSION["category_csrf_token"] =
        bin2hex(random_bytes(32));

}

$csrfToken =
    $_SESSION["category_csrf_token"];


/*
|--------------------------------------------------------------------------
| FORM VARIABLES
|--------------------------------------------------------------------------
*/

$name            = "";
$slug            = "";
$parentId        = null;
$description     = "";
$metaTitle       = "";
$metaDescription = "";

$displayOrder = 1;
$isActive     = 1;

$error = "";


/*
|--------------------------------------------------------------------------
| DATABASE COLUMN COMPATIBILITY
|--------------------------------------------------------------------------
*/

$hasParentCategoryId = false;
$hasMetaTitle        = false;
$hasMetaDescription  = false;


/*
|--------------------------------------------------------------------------
| CHECK COLUMNS
|--------------------------------------------------------------------------
*/

$columnCheckSql = "
    SELECT
        COL_LENGTH('dbo.Categories', 'ParentCategoryId') AS ParentCategoryIdLength,
        COL_LENGTH('dbo.Categories', 'MetaTitle') AS MetaTitleLength,
        COL_LENGTH('dbo.Categories', 'MetaDescription') AS MetaDescriptionLength
";

$columnCheckStmt = sqlsrv_query(
    $conn,
    $columnCheckSql
);

if ($columnCheckStmt !== false) {

    $columnRow = sqlsrv_fetch_array(
        $columnCheckStmt,
        SQLSRV_FETCH_ASSOC
    );

    if ($columnRow) {

        $hasParentCategoryId =
            $columnRow["ParentCategoryIdLength"] !== null;

        $hasMetaTitle =
            $columnRow["MetaTitleLength"] !== null;

        $hasMetaDescription =
            $columnRow["MetaDescriptionLength"] !== null;
    }

    sqlsrv_free_stmt($columnCheckStmt);
}


/*
|--------------------------------------------------------------------------
| UPLOAD DIRECTORY
|--------------------------------------------------------------------------
*/

$uploadDirectory =
    __DIR__ . "/../uploads/categories/";

if (!is_dir($uploadDirectory)) {

    if (!@mkdir($uploadDirectory, 0755, true)) {

        $error =
            "Unable to create category upload directory.";
    }
}


/*
|--------------------------------------------------------------------------
| GET NEXT DISPLAY ORDER
|--------------------------------------------------------------------------
*/

function getNextDisplayOrder(
    $conn,
    $parentId = null,
    $hasParentCategoryId = true
) {

    $nextOrder = 1;

    if (!$hasParentCategoryId) {

        $sql = "
            SELECT
                ISNULL(MAX(DisplayOrder), 0) + 1
                    AS NextDisplayOrder
            FROM dbo.Categories
        ";

        $params = [];

    } elseif ($parentId === null || $parentId === "" || $parentId === "0") {

        $sql = "
            SELECT
                ISNULL(MAX(DisplayOrder), 0) + 1
                    AS NextDisplayOrder
            FROM dbo.Categories
            WHERE ParentCategoryId IS NULL
        ";

        $params = [];

    } else {

        $sql = "
            SELECT
                ISNULL(MAX(DisplayOrder), 0) + 1
                    AS NextDisplayOrder
            FROM dbo.Categories
            WHERE ParentCategoryId = ?
        ";

        $params = [$parentId];
    }

    $stmt = sqlsrv_query(
        $conn,
        $sql,
        $params
    );

    if ($stmt !== false) {

        $row = sqlsrv_fetch_array(
            $stmt,
            SQLSRV_FETCH_ASSOC
        );

        if ($row) {

            $nextOrder =
                (int) (
                    $row["NextDisplayOrder"]
                    ?? 1
                );
        }

        sqlsrv_free_stmt($stmt);
    }

    if ($nextOrder < 1) {
        $nextOrder = 1;
    }

    return $nextOrder;
}


/*
|--------------------------------------------------------------------------
| AJAX ENDPOINT FOR FETCHING DISPLAY ORDER DYNAMICALLY
|--------------------------------------------------------------------------
*/

if (isset($_GET['get_order']) && $_GET['get_order'] == '1') {
    $ajaxParentId = isset($_GET['parent_id']) && $_GET['parent_id'] !== '' ? $_GET['parent_id'] : null;
    $order = getNextDisplayOrder($conn, $ajaxParentId, $hasParentCategoryId);
    echo json_encode(['display_order' => $order]);
    exit;
}


/*
|--------------------------------------------------------------------------
| INITIAL DISPLAY ORDER
|--------------------------------------------------------------------------
*/

$displayOrder =
    getNextDisplayOrder(
        $conn,
        null,
        $hasParentCategoryId
    );


/*
|--------------------------------------------------------------------------
| PARENT CATEGORIES
|--------------------------------------------------------------------------
*/

$parents = [];

if ($hasParentCategoryId) {

    $parentSql = "
        SELECT
            CategoryId,
            Name
        FROM dbo.Categories
        WHERE ParentCategoryId IS NULL
        ORDER BY Name ASC
    ";

} else {

    $parentSql = "
        SELECT
            CategoryId,
            Name
        FROM dbo.Categories
        ORDER BY Name ASC
    ";
}

$parentStmt = sqlsrv_query(
    $conn,
    $parentSql
);

if ($parentStmt !== false) {

    while (
        $row = sqlsrv_fetch_array(
            $parentStmt,
            SQLSRV_FETCH_ASSOC
        )
    ) {

        $parents[] = $row;
    }

    sqlsrv_free_stmt($parentStmt);
}


/*
|--------------------------------------------------------------------------
| SAVE CATEGORY
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $postedToken =
        $_POST["csrf_token"]
        ?? "";

    if (
        !hash_equals(
            $_SESSION["category_csrf_token"],
            $postedToken
        )
    ) {

        $error =
            "Security verification failed. Please refresh the page and try again.";
    }

    if ($error === "") {

        $name =
            trim(
                $_POST["name"]
                ?? ""
            );

        $slug =
            trim(
                $_POST["slug"]
                ?? ""
            );

        if ($hasParentCategoryId) {

            $parentId =
                !empty(
                    $_POST["parent_category_id"]
                    ?? ""
                )
                    ? (int)
                        $_POST["parent_category_id"]
                    : null;

        } else {

            $parentId = null;
        }

        $description =
            trim(
                $_POST["description"]
                ?? ""
            );

        $metaTitle =
            trim(
                $_POST["meta_title"]
                ?? ""
            );

        $metaDescription =
            trim(
                $_POST["meta_description"]
                ?? ""
            );

        $isActive =
            isset(
                $_POST["is_active"]
            )
                ? 1
                : 0;
    }

    /*
    |--------------------------------------------------------------------------
    | MANDATORY FIELD VALIDATIONS
    |--------------------------------------------------------------------------
    */

    if ($error === "" && $name === "") {
        $error = "Category name is required.";
    }

    if ($error === "" && mb_strlen($name) > 100) {
        $error = "Category name cannot be longer than 100 characters.";
    }

    if ($error === "" && $description === "") {
        $error = "Description is required.";
    }

    if ($error === "" && mb_strlen($description) > 300) {
        $error = "Description cannot be longer than 300 characters.";
    }

    if (
        $error === "" &&
        (!isset($_FILES["category_image"]) || $_FILES["category_image"]["error"] === UPLOAD_ERR_NO_FILE)
    ) {
        $error = "Category image is required.";
    }

    if (
        $error === "" &&
        mb_strlen($metaTitle) > 200
    ) {

        $error =
            "Meta title cannot be longer than 200 characters.";
    }

    if (
        $error === "" &&
        mb_strlen($metaDescription) > 500
    ) {

        $error =
            "Meta description cannot be longer than 500 characters.";
    }

    if ($error === "") {

        if ($slug === "") {

            $slug =
                strtolower(
                    preg_replace(
                        "/[^a-zA-Z0-9]+/",
                        "-",
                        $name
                    )
                );

            $slug =
                trim(
                    $slug,
                    "-"
                );
        }

        $slug =
            strtolower(
                preg_replace(
                    "/[^a-zA-Z0-9\-]/",
                    "",
                    $slug
                )
            );

        $slug =
            trim(
                $slug,
                "-"
            );

        if ($slug === "") {

            $error =
                "Please enter a valid category name or slug.";
        }
    }

    if (
        $error === "" &&
        strlen($slug) > 150
    ) {

        $error =
            "Slug cannot be longer than 150 characters.";
    }

    if ($error === "") {

        $checkSlugSql = "
            SELECT TOP 1
                CategoryId
            FROM dbo.Categories
            WHERE Slug = ?
        ";

        $checkSlugStmt =
            sqlsrv_query(
                $conn,
                $checkSlugSql,
                [$slug]
            );

        if ($checkSlugStmt === false) {

            $errors =
                sqlsrv_errors();

            $error =
                $errors[0]["message"]
                ?? "Unable to check category slug.";

        } else {

            $existingSlug =
                sqlsrv_fetch_array(
                    $checkSlugStmt,
                    SQLSRV_FETCH_ASSOC
                );

            sqlsrv_free_stmt(
                $checkSlugStmt
            );

            if ($existingSlug) {

                $error =
                    "This category slug already exists. Please use another slug.";
            }
        }
    }

    if (
        $error === "" &&
        $hasParentCategoryId &&
        $parentId !== null
    ) {

        $parentCheckSql = "
            SELECT TOP 1
                CategoryId
            FROM dbo.Categories
            WHERE CategoryId = ?
              AND ParentCategoryId IS NULL
        ";

        $parentCheckStmt =
            sqlsrv_query(
                $conn,
                $parentCheckSql,
                [$parentId]
            );

        if ($parentCheckStmt === false) {

            $errors =
                sqlsrv_errors();

            $error =
                $errors[0]["message"]
                ?? "Unable to validate parent category.";

        } else {

            $validParent =
                sqlsrv_fetch_array(
                    $parentCheckStmt,
                    SQLSRV_FETCH_ASSOC
                );

            sqlsrv_free_stmt(
                $parentCheckStmt
            );

            if (!$validParent) {

                $error =
                    "The selected parent category is invalid.";
            }
        }
    }

    $uploadedImagePath    = null;
    $uploadedPhysicalPath = null;

    if (
        $error === "" &&
        isset($_FILES["category_image"]) &&
        $_FILES["category_image"]["error"]
            !== UPLOAD_ERR_NO_FILE
    ) {

        $file =
            $_FILES["category_image"];

        if (
            $file["error"]
            !== UPLOAD_ERR_OK
        ) {

            $error =
                "Unable to upload the category image.";
        }

        if (
            $error === "" &&
            $file["size"] > (5 * 1024 * 1024)
        ) {

            $error =
                "Image size must be less than 5 MB.";
        }

        if (
            $error === "" &&
            $file["size"] <= 0
        ) {

            $error =
                "The uploaded image is empty.";
        }

        if (
            $error === "" &&
            !is_uploaded_file(
                $file["tmp_name"]
            )
        ) {

            $error =
                "Invalid image upload.";
        }

        $imageInformation = false;

        if ($error === "") {

            $imageInformation =
                @getimagesize(
                    $file["tmp_name"]
                );

            if (
                $imageInformation === false
            ) {

                $error =
                    "Please upload a valid image file.";
            }
        }

        $allowedMimeTypes = [

            "image/jpeg",
            "image/png",
            "image/webp",
            "image/gif"

        ];

        if (
            $error === "" &&
            !in_array(
                $imageInformation["mime"],
                $allowedMimeTypes,
                true
            )
        ) {

            $error =
                "Only JPG, JPEG, PNG, WEBP and GIF images are allowed.";
        }

        $extension = "";

        if ($error === "") {

            switch (
                $imageInformation["mime"]
            ) {

                case "image/jpeg":

                    $extension = "jpg";

                    break;

                case "image/png":

                    $extension = "png";

                    break;

                case "image/webp":

                    $extension = "webp";

                    break;

                case "image/gif":

                    $extension = "gif";

                    break;
            }

            if ($extension === "") {

                $error =
                    "Unsupported image format.";
            }
        }

        if ($error === "") {

            try {

                $randomPart =
                    bin2hex(
                        random_bytes(8)
                    );

            } catch (Throwable $e) {

                $randomPart =
                    uniqid(
                        "",
                        true
                    );
            }

            $uniqueName =
                "category_" .
                date("Ymd_His") .
                "_" .
                $randomPart .
                "." .
                $extension;

            $uploadedPhysicalPath =
                $uploadDirectory .
                $uniqueName;

            if (
                !move_uploaded_file(
                    $file["tmp_name"],
                    $uploadedPhysicalPath
                )
            ) {

                $error =
                    "Unable to save the uploaded image.";

            } else {

                $uploadedImagePath =
                    "uploads/categories/" .
                    $uniqueName;
            }
        }
    }

    if (
        $error === "" &&
        $metaTitle === ""
    ) {

        $metaTitle =
            $name .
            " | GatewayLinen";
    }

    if (
        $error === "" &&
        $metaDescription === "" &&
        $description !== ""
    ) {

        $metaDescription =
            $description;
    }

    if ($error === "") {

        $displayOrder =
            getNextDisplayOrder(
                $conn,
                $parentId,
                $hasParentCategoryId
            );
    }

    if ($error === "") {

        $columns = [
            "Name",
            "Slug",
            "Description",
            "ImageUrl",
            "DisplayOrder",
            "IsActive",
            "CreatedAt"
        ];

        $values = [
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "GETDATE()"
        ];

        $params = [
            $name,
            $slug,
            $description !== ""
                ? $description
                : null,
            $uploadedImagePath !== null
                ? $uploadedImagePath
                : null,
            $displayOrder,
            $isActive
        ];

        if ($hasParentCategoryId) {

            array_unshift(
                $columns,
                "ParentCategoryId"
            );

            array_unshift(
                $values,
                "?"
            );

            array_unshift(
                $params,
                $parentId
            );
        }

        if ($hasMetaTitle) {

            $insertIndex =
                count($columns) - 1;

            array_splice(
                $columns,
                $insertIndex,
                0,
                ["MetaTitle"]
            );

            array_splice(
                $values,
                $insertIndex,
                0,
                ["?"]
            );

            array_splice(
                $params,
                $insertIndex,
                0,
                [
                    $metaTitle !== ""
                        ? $metaTitle
                        : null
                ]
            );
        }

        if ($hasMetaDescription) {

            $insertIndex =
                count($columns) - 1;

            array_splice(
                $columns,
                $insertIndex,
                0,
                ["MetaDescription"]
            );

            array_splice(
                $values,
                $insertIndex,
                0,
                ["?"]
            );

            array_splice(
                $params,
                $insertIndex,
                0,
                [
                    $metaDescription !== ""
                        ? $metaDescription
                        : null
                ]
            );
        }

        $sql = "
            INSERT INTO dbo.Categories
            (
                " .
                implode(
                    ", ",
                    $columns
                ) .
            "
            )
            VALUES
            (
                " .
                implode(
                    ", ",
                    $values
                ) .
            "
            )
        ";

        $stmt =
            sqlsrv_query(
                $conn,
                $sql,
                $params
            );

        if ($stmt === false) {

            if (
                $uploadedPhysicalPath !== null &&
                file_exists(
                    $uploadedPhysicalPath
                )
            ) {

                @unlink(
                    $uploadedPhysicalPath
                );
            }

            $errors =
                sqlsrv_errors();

            $error =
                $errors[0]["message"]
                ?? "Unable to create category.";

        } else {

            sqlsrv_free_stmt(
                $stmt
            );

            $_SESSION["category_csrf_token"] =
                bin2hex(
                    random_bytes(32)
                );

            header(
                "Location: index.php?success=category_created"
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

<style>

/*
|--------------------------------------------------------------------------
| THEME VARIABLES (DYNAMIC LIGHT/DARK SUPPORT)
|--------------------------------------------------------------------------
*/

:root {
    --bg-page: #f8fafc;
    --bg-card: #ffffff;
    --bg-card-alt: #f1f5f9;
    --bg-input: #ffffff;
    --bg-hover: #e2e8f0;
    --border: #cbd5e1;
    --border-soft: #e2e8f0;
    --text-hi: #0f172a;
    --text-body: #334155;
    --text-mute: #64748b;
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
    --radius: 10px;
}

body.dark-theme, 
body[data-theme="dark"],
.dark-theme :root,
[data-theme="dark"] {
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
}

@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) {
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
    }
}


/*
|--------------------------------------------------------------------------
| MAIN
|--------------------------------------------------------------------------
*/

.main,
.content {
    background: var(--bg-page);
    color: var(--text-body);
}


.add-category-page {
    width: 100%;
    max-width: 1180px;
    margin: 0 auto;
    padding: 6px 20px 30px;
    box-sizing: border-box;
    color: var(--text-body);
}


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

.add-category-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--border);
}


.add-category-header-left {
    min-width: 0;
}


.add-category-breadcrumb {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 5px;
    margin-bottom: 2px;
    color: var(--text-mute);
    font-size: 9px;
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
    font-size: 20px;
    line-height: 1.2;
    font-weight: 800;
    letter-spacing: -.5px;
}


.add-category-subtitle {
    margin: 2px 0 0;
    color: var(--text-mute);
    font-size: 11px;
    line-height: 1.3;
}


/*
|--------------------------------------------------------------------------
| BACK BUTTON
|--------------------------------------------------------------------------
*/

.add-category-back {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 32px;
    padding: 0 12px;
    border: 1px solid var(--border);
    border-radius: 7px;
    background: var(--bg-input);
    color: var(--text-body) !important;
    font-size: 10.5px;
    font-weight: 700;
    text-decoration: none;
    white-space: nowrap;
    transition: .2s ease;
}


.add-category-back:hover {
    border-color: var(--green);
    background: var(--green-soft);
    color: var(--green) !important;
    transform: translateY(-1px);
}


/*
|--------------------------------------------------------------------------
| ERROR
|--------------------------------------------------------------------------
*/

.add-category-error {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 12px;
    padding: 10px 12px;
    border: 1px solid rgba(239,68,68,.3);
    border-left: 4px solid var(--red);
    border-radius: 8px;
    background: var(--red-soft);
    color: var(--text-hi);
    font-size: 11.5px;
    font-weight: 600;
    line-height: 1.4;
}


.add-category-error-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    flex: 0 0 20px;
    border-radius: 50%;
    background: rgba(239,68,68,.25);
    color: var(--red);
    font-weight: 900;
    font-size: 11px;
}


/*
|--------------------------------------------------------------------------
| FORM
|--------------------------------------------------------------------------
*/

.add-category-form {
    width: 100%;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 15px 40px rgba(0,0,0,.08);
}


.add-category-form-body {
    width: 100%;
    padding: 18px 20px;
    box-sizing: border-box;
}


.add-category-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px 24px;
}


/*
|--------------------------------------------------------------------------
| SECTION
|--------------------------------------------------------------------------
*/

.add-category-section {
    grid-column: 1 / -1;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 8px;
    padding: 7px 12px;
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
    width: 22px;
    height: 22px;
    flex: 0 0 22px;
    border-radius: 6px;
    background: rgba(16,185,129,.18);
    color: var(--green);
    font-size: 10.5px;
    font-weight: 900;
}


.sec-blue .add-category-section-icon {
    background: rgba(59,130,246,.18);
    color: var(--blue);
}


.sec-purple .add-category-section-icon {
    background: rgba(139,92,246,.18);
    color: var(--purple);
}


.sec-amber .add-category-section-icon {
    background: rgba(245,158,11,.18);
    color: var(--amber);
}


.add-category-section-title {
    color: var(--text-hi);
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .8px;
}


/*
|--------------------------------------------------------------------------
| FORM GROUP
|--------------------------------------------------------------------------
*/

.add-form-group {
    min-width: 0;
}


.add-form-group-full {
    grid-column: 1 / -1;
}


/*
|--------------------------------------------------------------------------
| LABEL
|--------------------------------------------------------------------------
*/

.add-form-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
    color: var(--text-body);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .3px;
    text-transform: uppercase;
}

.shortcut-badge {
    background: var(--green-soft);
    color: var(--green);
    font-family: monospace;
    font-size: 9px;
    padding: 1px 5px;
    border-radius: 4px;
    border: 1px solid rgba(16,185,129,0.3);
}


.add-form-required {
    color: var(--red);
    margin-left: 3px;
}


/*
|--------------------------------------------------------------------------
| INPUT
|--------------------------------------------------------------------------
*/

.add-form-input,
.add-form-select,
.add-form-textarea {
    width: 100%;
    box-sizing: border-box;
    border: 1px solid var(--border);
    border-radius: 9px;
    outline: none;
    background: var(--bg-input);
    color: var(--text-hi);
    font-family: inherit;
    font-size: 14px;
    font-weight: 500;
    transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
}


.add-form-input,
.add-form-select {
    height: 42px;
    padding: 0 12px;
}


.add-form-textarea {
    min-height: 95px;
    padding: 10px 12px;
    resize: vertical;
    line-height: 1.5;
}


.add-form-input::placeholder,
.add-form-textarea::placeholder {
    color: var(--text-mute);
    font-weight: 400;
}


.add-form-input:hover,
.add-form-select:hover,
.add-form-textarea:hover {
    border-color: var(--text-mute);
}


.add-form-input:focus,
.add-form-select:focus,
.add-form-textarea:focus {
    border-color: var(--green);
    box-shadow: 0 0 0 3px rgba(16,185,129,.13);
}


/*
|--------------------------------------------------------------------------
| AUTO ORDER
|--------------------------------------------------------------------------
*/

.auto-order-input {
    border-color: rgba(16,185,129,.35);
    background: rgba(16,185,129,.06);
    color: var(--green);
    font-weight: 800;
    cursor: not-allowed;
}


.auto-order-note {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-top: 4px;
    color: var(--text-mute);
    font-size: 9.5px;
    line-height: 1.4;
}


.auto-order-note span {
    color: var(--green);
    font-weight: 800;
}


/*
|--------------------------------------------------------------------------
| SELECT
|--------------------------------------------------------------------------
*/

.add-form-select {
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%235f7488' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 13px;
    padding-right: 36px;
    cursor: pointer;
}


.add-form-select option {
    background: var(--bg-card);
    color: var(--text-hi);
}


/*
|--------------------------------------------------------------------------
| IMAGE UPLOAD
|--------------------------------------------------------------------------
*/

.category-upload-box {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 130px;
    padding: 14px;
    box-sizing: border-box;
    border: 2px dashed rgba(16,185,129,.38);
    border-radius: 10px;
    background: rgba(16,185,129,.045);
    cursor: pointer;
    transition: border-color .2s ease, background .2s ease, box-shadow .2s ease;
}


.category-upload-box:hover {
    border-color: var(--green);
    background: rgba(16,185,129,.09);
    box-shadow: 0 0 0 4px rgba(16,185,129,.06);
}


.category-upload-box.dragging {
    border-color: var(--green);
    background: rgba(16,185,129,.12);
    box-shadow: 0 0 0 4px rgba(16,185,129,.08);
}


.category-upload-input {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
    pointer-events: none;
}


.category-upload-content {
    text-align: center;
}


.category-upload-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    margin: 0 auto 6px;
    border-radius: 10px;
    background: rgba(16,185,129,.15);
    color: var(--green);
    font-size: 16px;
    font-weight: 900;
}


.category-upload-title {
    color: var(--text-hi);
    font-size: 11px;
    font-weight: 700;
}


.category-upload-title span {
    color: var(--green);
}


.category-upload-help {
    margin-top: 4px;
    color: var(--text-mute);
    font-size: 9px;
    font-weight: 500;
}


/*
|--------------------------------------------------------------------------
| IMAGE PREVIEW
|--------------------------------------------------------------------------
*/

.category-image-preview {
    display: none;
    align-items: center;
    gap: 14px;
    width: 100%;
}


.category-preview-image {
    width: 75px;
    height: 75px;
    flex: 0 0 75px;
    object-fit: cover;
    border: 2px solid var(--border);
    border-radius: 9px;
    background: var(--bg-card-alt);
    box-shadow: 0 5px 15px rgba(0,0,0,.15);
}


.category-preview-info {
    min-width: 0;
    flex: 1;
    text-align: left;
}


.category-preview-name {
    color: var(--text-hi);
    font-size: 11px;
    font-weight: 700;
    word-break: break-word;
}


.category-preview-size {
    margin-top: 3px;
    color: var(--text-mute);
    font-size: 9.5px;
}


.category-preview-change {
    display: inline-flex;
    align-items: center;
    margin-top: 6px;
    padding: 3px 9px;
    border-radius: 20px;
    background: var(--green-soft);
    color: var(--green);
    font-size: 9px;
    font-weight: 700;
}


/*
|--------------------------------------------------------------------------
| ACTIVE
|--------------------------------------------------------------------------
*/

.add-active-box {
    display: flex;
    align-items: center;
    min-height: 42px;
    padding: 0 12px;
    border: 1px solid var(--border);
    border-radius: 9px;
    background: var(--bg-input);
}


.add-active-label {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    color: var(--text-body);
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    user-select: none;
}


.add-active-checkbox {
    width: 17px;
    height: 17px;
    margin: 0;
    accent-color: var(--green);
    cursor: pointer;
}


/*
|--------------------------------------------------------------------------
| SEO
|--------------------------------------------------------------------------
*/

.seo-note {
    margin-top: 4px;
    color: var(--text-mute);
    font-size: 9.5px;
    line-height: 1.4;
}

.char-counter {
    text-align: right;
    margin-top: 4px;
    color: var(--text-mute);
    font-size: 9.5px;
}


/*
|--------------------------------------------------------------------------
| FORM FOOTER
|--------------------------------------------------------------------------
*/

.add-category-form-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding: 14px 20px;
    border-top: 1px solid var(--border);
    background: var(--bg-card-alt);
}


.add-category-footer-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}


/*
|--------------------------------------------------------------------------
| BUTTONS
|--------------------------------------------------------------------------
*/

.add-category-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 18px;
    border-radius: 8px;
    text-decoration: none;
    font-family: inherit;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .2px;
    cursor: pointer;
    transition: all .18s ease;
}


.add-category-btn:active {
    transform: translateY(1px);
}


.add-category-cancel {
    border: 1px solid var(--border);
    background: var(--bg-input);
    color: var(--text-body) !important;
}


.add-category-cancel:hover {
    border-color: var(--text-mute);
    background: var(--bg-hover);
    color: var(--text-hi) !important;
}


.add-category-save {
    border: 1px solid var(--green-dark);
    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
    color: #ffffff;
    box-shadow: 0 5px 15px rgba(16,185,129,.24);
}


.add-category-save:hover {
    filter: brightness(1.08);
    box-shadow: 0 8px 20px rgba(16,185,129,.35);
    transform: translateY(-1px);
}


.add-category-save:disabled {
    opacity: .7;
    cursor: wait;
    transform: none;
}


/*
|--------------------------------------------------------------------------
| RESPONSIVE
|--------------------------------------------------------------------------
*/

@media (max-width: 1000px) {
    .add-category-page {
        max-width: 100%;
    }
}


@media (max-width: 900px) {
    .add-category-header {
        align-items: flex-start;
        flex-direction: column;
        gap: 10px;
    }

    .add-category-back {
        width: 100%;
    }

    .add-category-grid {
        grid-template-columns: 1fr;
    }

    .add-form-group-full {
        grid-column: auto;
    }

    .add-category-form-footer {
        padding: 14px;
    }

    .add-category-footer-actions {
        width: 100%;
    }

    .add-category-btn {
        flex: 1;
    }
}


/*
|--------------------------------------------------------------------------
| PRINT
|--------------------------------------------------------------------------
*/

@media print {
    .admin-header,
    .sidebar,
    #adminSidebar,
    #sidebarOverlay,
    .add-category-header,
    .add-category-form-footer {
        display: none !important;
    }

    .main {
        margin-left: 0 !important;
        padding: 0 !important;
    }
}

</style>


<!--
|--------------------------------------------------------------------------
| MAIN CONTENT
|--------------------------------------------------------------------------
-->

<main class="main">

    <section class="content">

        <div class="add-category-page">


            <!-- PAGE HEADER -->

            <div class="add-category-header">

                <div class="add-category-header-left">

                    <div class="add-category-breadcrumb">

                        <span>Dashboard</span>

                        <span>›</span>

                        <span>Categories</span>

                        <span>›</span>

                        <span class="current">
                            Add Category
                        </span>

                    </div>


                    <h1 class="add-category-title">

                        Add New Category

                    </h1>


                    <p class="add-category-subtitle">

                        Create a new product category with
                        automatic display order, image,
                        SEO and display settings.

                    </p>

                </div>


                <a
                    href="index.php"
                    class="add-category-back"
                    title="Shortcut: B"
                >

                    <span>←</span>

                    <span>
                        Back to Categories <small style="opacity:0.7; font-size:9px;">[B]</small>
                    </span>

                </a>

            </div>


            <!-- ERROR -->

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


            <!-- FORM -->

            <form
                method="POST"
                enctype="multipart/form-data"
                autocomplete="off"
                class="add-category-form"
                id="addCategoryForm"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($csrfToken) ?>"
                >


                <div class="add-category-form-body">

                    <div class="add-category-grid">


                        <!-- BASIC INFORMATION -->

                        <div class="add-category-section">

                            <div class="add-category-section-icon">
                                #
                            </div>

                            <div class="add-category-section-title">
                                Basic Information
                            </div>

                        </div>


                        <!-- CATEGORY NAME -->

                        <div class="add-form-group">

                            <label
                                for="categoryName"
                                class="add-form-label"
                            >
                                <span>
                                    Category Name
                                    <span class="add-form-required">*</span>
                                </span>
                                <span class="shortcut-badge">Shortcut: N</span>
                            </label>


                            <input
                                type="text"
                                id="categoryName"
                                name="name"
                                class="add-form-input"
                                value="<?= e($name) ?>"
                                placeholder="e.g. Bath Towels"
                                maxlength="100"
                                required
                            >

                        </div>


                        <!-- SLUG -->

                        <div class="add-form-group">

                            <label
                                for="categorySlug"
                                class="add-form-label"
                            >
                                <span>Slug</span>
                                <span class="shortcut-badge">Shortcut: G</span>
                            </label>


                            <input
                                type="text"
                                id="categorySlug"
                                name="slug"
                                class="add-form-input"
                                value="<?= e($slug) ?>"
                                placeholder="bath-towels"
                                maxlength="150"
                            >

                            <div class="seo-note">

                                Automatically generated from
                                category name.

                            </div>

                        </div>


                        <!-- PARENT CATEGORY -->

                        <div class="add-form-group">

                            <label
                                for="parentCategory"
                                class="add-form-label"
                            >
                                <span>Parent Category</span>
                            </label>


                            <?php if ($hasParentCategoryId): ?>

                                <select
                                    id="parentCategory"
                                    name="parent_category_id"
                                    class="add-form-select"
                                >

                                    <option value="">
                                        Main Category
                                    </option>


                                    <?php foreach (
                                        $parents
                                        as $parent
                                    ): ?>

                                        <?php

                                        $parentCategoryId =
                                            (int) (
                                                $parent["CategoryId"]
                                                ?? 0
                                            );

                                        ?>

                                        <option
                                            value="<?= $parentCategoryId ?>"
                                            <?= (
                                                (string) $parentId ===
                                                (string) $parentCategoryId
                                            )
                                                ? "selected"
                                                : "" ?>
                                        >

                                            <?= e(
                                                $parent["Name"]
                                                ?? ""
                                            ) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            <?php else: ?>

                                <select
                                    id="parentCategory"
                                    class="add-form-select"
                                    disabled
                                >

                                    <option>
                                        Main Category
                                    </option>

                                </select>

                                <div class="seo-note">

                                    Parent category support is not
                                    available in the current database table.

                                </div>

                            <?php endif; ?>

                        </div>


                        <!-- DISPLAY ORDER -->

                        <div class="add-form-group">

                            <label
                                for="displayOrder"
                                class="add-form-label"
                            >
                                <span>Display Order</span>
                            </label>


                            <input
                                type="text"
                                id="displayOrder"
                                class="add-form-input auto-order-input"
                                value="<?= (int) $displayOrder ?>"
                                readonly
                                aria-readonly="true"
                                tabindex="-1"
                            >


                            <div class="auto-order-note">

                                <span>✓</span>

                                <span>
                                    Automatically assigned from the
                                    next available order number.
                                </span>

                            </div>

                        </div>


                        <!-- CATEGORY IMAGE -->

                        <div class="add-category-section sec-blue">

                            <div class="add-category-section-icon">
                                ↑
                            </div>

                            <div class="add-category-section-title">

                                Category Image <small style="font-weight: normal; text-transform: none; margin-left: 8px;">[Shortcut: I]</small>
                                <span class="add-form-required" style="margin-left: 2px;">*</span>

                            </div>

                        </div>


                        <!-- IMAGE -->

                        <div
                            class="add-form-group add-form-group-full"
                        >

                            <label
                                for="categoryImage"
                                class="category-upload-box"
                                id="categoryUploadBox"
                            >

                                <input
                                    type="file"
                                    id="categoryImage"
                                    name="category_image"
                                    class="category-upload-input"
                                    accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif"
                                >


                                <!-- UPLOAD MESSAGE -->

                                <div
                                    class="category-upload-content"
                                    id="categoryUploadContent"
                                >

                                    <div
                                        class="category-upload-icon"
                                    >
                                        ↑
                                    </div>


                                    <div
                                        class="category-upload-title"
                                    >

                                        Click or press <span>I</span> to

                                        <span>
                                            upload image
                                        </span>

                                    </div>


                                    <div
                                        class="category-upload-help"
                                    >

                                        JPG, JPEG, PNG, WEBP or GIF
                                        &nbsp;•&nbsp;
                                        Maximum 5 MB

                                    </div>

                                </div>


                                <!-- IMAGE PREVIEW -->

                                <div
                                    class="category-image-preview"
                                    id="categoryImagePreview"
                                >

                                    <img
                                        src=""
                                        alt="Category Preview"
                                        class="category-preview-image"
                                        id="categoryPreviewImage"
                                    >


                                    <div
                                        class="category-preview-info"
                                    >

                                        <div
                                            class="category-preview-name"
                                            id="categoryPreviewName"
                                        ></div>


                                        <div
                                            class="category-preview-size"
                                            id="categoryPreviewSize"
                                        ></div>


                                        <div
                                            class="category-preview-change"
                                        >

                                            Click to change image

                                        </div>

                                    </div>

                                </div>

                            </label>

                        </div>


                        <!-- ADDITIONAL INFORMATION -->

                        <div
                            class="add-category-section sec-purple"
                        >

                            <div
                                class="add-category-section-icon"
                            >
                                ≡
                            </div>

                            <div
                                class="add-category-section-title"
                            >

                                Additional Information

                            </div>

                        </div>


                        <!-- DESCRIPTION (Max 300 Characters, Mandatory) -->

                        <div
                            class="add-form-group add-form-group-full"
                        >

                            <label
                                for="categoryDescription"
                                class="add-form-label"
                            >
                                <span>
                                    Description (Max 300 chars)
                                    <span class="add-form-required">*</span>
                                </span>
                            </label>


                            <textarea
                                id="categoryDescription"
                                name="description"
                                class="add-form-textarea"
                                placeholder="Write a short description for this category (up to 300 characters)..."
                                maxlength="300"
                                required
                            ><?= e($description) ?></textarea>
                            <div class="char-counter" id="descCharCounter">0 / 300 characters</div>

                        </div>


                        <!-- STATUS -->

                        <div class="add-form-group">

                            <label class="add-form-label">
                                <span>Category Status</span>
                            </label>


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
                                        Active Category
                                    </span>

                                </label>

                            </div>

                        </div>


                        <div class="add-form-group"></div>


                        <!-- SEO -->

                        <div
                            class="add-category-section sec-amber"
                        >

                            <div
                                class="add-category-section-icon"
                            >
                                S
                            </div>

                            <div
                                class="add-category-section-title"
                            >

                                SEO Settings (Optional)

                            </div>

                        </div>


                        <!-- META TITLE (Optional) -->

                        <div class="add-form-group">

                            <label
                                for="metaTitle"
                                class="add-form-label"
                            >
                                <span>Meta Title (Optional)</span>
                            </label>


                            <input
                                type="text"
                                id="metaTitle"
                                name="meta_title"
                                class="add-form-input"
                                value="<?= e($metaTitle) ?>"
                                placeholder="SEO title"
                                maxlength="200"
                            >


                            <?php if (!$hasMetaTitle): ?>

                                <div class="seo-note">

                                    MetaTitle column is not available
                                    in the current database.

                                </div>

                            <?php endif; ?>

                        </div>


                        <!-- META DESCRIPTION (Optional) -->

                        <div class="add-form-group">

                            <label
                                for="metaDescription"
                                class="add-form-label"
                            >
                                <span>Meta Description (Optional)</span>
                            </label>


                            <input
                                type="text"
                                id="metaDescription"
                                name="meta_description"
                                class="add-form-input"
                                value="<?= e($metaDescription) ?>"
                                placeholder="SEO description"
                                maxlength="500"
                            >


                            <?php if (!$hasMetaDescription): ?>

                                <div class="seo-note">

                                    MetaDescription column is not available
                                    in the current database.

                                </div>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>


                <!-- FORM FOOTER -->

                <div class="add-category-form-footer">

                    <div
                        class="add-category-footer-actions"
                    >

                        <a
                            href="index.php"
                            class="add-category-btn add-category-cancel"
                            title="Shortcut: C"
                        >
                            Cancel <small style="opacity:0.7; font-size:9px;">[C]</small>
                        </a>


                        <button
                            type="submit"
                            class="add-category-btn add-category-save"
                            id="saveCategoryButton"
                            title="Shortcut: A"
                        >

                            <span>✓</span>

                            <span>
                                Save Category <small style="opacity:0.8; font-size:9px;">[A]</small>
                            </span>

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </section>

</main>


<script>

/*
|--------------------------------------------------------------------------
| CATEGORY ADD PAGE JAVASCRIPT
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const imageInput =
            document.getElementById(
                "categoryImage"
            );


        const uploadBox =
            document.getElementById(
                "categoryUploadBox"
            );


        const uploadContent =
            document.getElementById(
                "categoryUploadContent"
            );


        const imagePreview =
            document.getElementById(
                "categoryImagePreview"
            );


        const previewImage =
            document.getElementById(
                "categoryPreviewImage"
            );


        const previewName =
            document.getElementById(
                "categoryPreviewName"
            );


        const previewSize =
            document.getElementById(
                "categoryPreviewSize"
            );


        const nameInput =
            document.getElementById(
                "categoryName"
            );


        const slugInput =
            document.getElementById(
                "categorySlug"
            );


        const parentInput =
            document.getElementById(
                "parentCategory"
            );


        const displayOrderInput =
            document.getElementById(
                "displayOrder"
            );


        const descriptionInput =
            document.getElementById(
                "categoryDescription"
            );


        const descCharCounter =
            document.getElementById(
                "descCharCounter"
            );


        const metaTitleInput =
            document.getElementById(
                "metaTitle"
            );


        const metaDescriptionInput =
            document.getElementById(
                "metaDescription"
            );


        const form =
            document.getElementById(
                "addCategoryForm"
            );


        const saveButton =
            document.getElementById(
                "saveCategoryButton"
            );


        /*
        |--------------------------------------------------------------------------
        | DYNAMIC DISPLAY ORDER UPDATE ON PARENT CHANGE
        |--------------------------------------------------------------------------
        */

        if (parentInput && displayOrderInput) {
            parentInput.addEventListener("change", function () {
                const parentId = this.value;
                
                fetch("add.php?get_order=1&parent_id=" + encodeURIComponent(parentId))
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.display_order !== undefined) {
                            displayOrderInput.value = data.display_order;
                        }
                    })
                    .catch(error => {
                        console.error("Error fetching display order:", error);
                    });
            });
        }


        /*
        |--------------------------------------------------------------------------
        | DESCRIPTION CHARACTER COUNTER
        |--------------------------------------------------------------------------
        */

        function updateCharCounter() {
            if (descriptionInput && descCharCounter) {
                const len = descriptionInput.value.length;
                descCharCounter.textContent = len + " / 300 characters";
            }
        }

        if (descriptionInput) {
            updateCharCounter();
            descriptionInput.addEventListener("input", updateCharCounter);
        }


        /*
        |--------------------------------------------------------------------------
        | SLUG STATE
        |--------------------------------------------------------------------------
        */

        let slugManuallyChanged =
            slugInput &&
            slugInput.value.trim() !== "";


        /*
        |--------------------------------------------------------------------------
        | IMAGE RESET
        |--------------------------------------------------------------------------
        */

        function resetImagePreview()
        {

            if (uploadContent) {

                uploadContent.style.display =
                    "block";
            }


            if (imagePreview) {

                imagePreview.style.display =
                    "none";
            }


            if (previewImage) {

                previewImage.removeAttribute(
                    "src"
                );
            }


            if (previewName) {

                previewName.textContent =
                    "";
            }


            if (previewSize) {

                previewSize.textContent =
                    "";
            }
        }


        /*
        |--------------------------------------------------------------------------
        | IMAGE VALIDATION
        |--------------------------------------------------------------------------
        */

        function validateImage(file)
        {

            if (!file) {

                return false;
            }


            if (
                file.size >
                5 * 1024 * 1024
            ) {

                alert(
                    "Image size must be less than 5 MB."
                );

                return false;
            }


            const allowedTypes = [

                "image/jpeg",
                "image/png",
                "image/webp",
                "image/gif"

            ];


            if (
                !allowedTypes.includes(
                    file.type
                )
            ) {

                alert(
                    "Only JPG, JPEG, PNG, WEBP and GIF images are allowed."
                );

                return false;
            }


            return true;
        }


        /*
        |--------------------------------------------------------------------------
        | SHOW IMAGE PREVIEW
        |--------------------------------------------------------------------------
        */

        function showImagePreview(file)
        {

            if (!file) {

                resetImagePreview();

                return;
            }


            if (
                !validateImage(file)
            ) {

                if (imageInput) {

                    imageInput.value =
                        "";
                }

                resetImagePreview();

                return;
            }


            const reader =
                new FileReader();


            reader.onload =
                function (event) {

                    if (previewImage) {

                        previewImage.src =
                            event.target.result;
                    }


                    if (previewName) {

                        previewName.textContent =
                            file.name;
                    }


                    if (previewSize) {

                        const sizeMB =
                            (
                                file.size /
                                (
                                    1024 *
                                    1024
                                )
                            ).toFixed(2);


                        previewSize.textContent =
                            sizeMB +
                            " MB";
                    }


                    if (uploadContent) {

                        uploadContent.style.display =
                            "none";
                    }


                    if (imagePreview) {

                        imagePreview.style.display =
                            "flex";
                    }

                };


            reader.readAsDataURL(
                file
            );
        }


        /*
        |--------------------------------------------------------------------------
        | IMAGE CHANGE
        |--------------------------------------------------------------------------
        */

        if (imageInput) {

            imageInput.addEventListener(
                "change",
                function () {

                    const file =
                        this.files &&
                        this.files[0]
                            ? this.files[0]
                            : null;


                    showImagePreview(
                        file
                    );

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | DRAG & DROP
        |--------------------------------------------------------------------------
        */

        if (uploadBox) {

            uploadBox.addEventListener(
                "dragover",
                function (event) {

                    event.preventDefault();

                    uploadBox.classList.add(
                        "dragging"
                    );

                }
            );


            uploadBox.addEventListener(
                "dragleave",
                function () {

                    uploadBox.classList.remove(
                        "dragging"
                    );

                }
            );


            uploadBox.addEventListener(
                "drop",
                function (event) {

                    event.preventDefault();

                    uploadBox.classList.remove(
                        "dragging"
                    );


                    const files =
                        event.dataTransfer.files;


                    if (
                        files &&
                        files.length > 0
                    ) {

                        const file =
                            files[0];


                        if (imageInput) {

                            try {

                                const dataTransfer =
                                    new DataTransfer();

                                dataTransfer.items.add(
                                    file
                                );

                                imageInput.files =
                                    dataTransfer.files;

                            } catch (error) {

                            }

                        }


                        showImagePreview(
                            file
                        );

                    }

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | AUTO SLUG
        |--------------------------------------------------------------------------
        */

        if (slugInput) {

            slugInput.addEventListener(
                "input",
                function () {

                    slugManuallyChanged =
                        this.value.trim() !== "";

                }
            );

        }


        if (
            nameInput &&
            slugInput
        ) {

            nameInput.addEventListener(
                "input",
                function () {

                    if (
                        !slugManuallyChanged
                    ) {

                        let slug =
                            this.value
                                .toLowerCase()
                                .trim()
                                .replace(
                                    /[^a-z0-9]+/g,
                                    "-"
                                )
                                .replace(
                                    /^-+|-+$/g,
                                    ""
                                );


                        slugInput.value =
                            slug;

                    }


                    if (
                        metaTitleInput &&
                        metaTitleInput.value.trim() === ""
                    ) {

                        metaTitleInput.value =
                            this.value.trim() !== ""
                                ? this.value.trim() +
                                  " | GatewayLinen"
                                : "";

                    }

                }
            );

        }


        if (
            descriptionInput &&
            metaDescriptionInput
        ) {

            descriptionInput.addEventListener(
                "input",
                function () {

                    if (
                        metaDescriptionInput.value.trim() === ""
                    ) {

                        metaDescriptionInput.value =
                            this.value.trim();

                    }

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | FORM SUBMIT VALIDATION
        |--------------------------------------------------------------------------
        */

        let formSubmitting = false;


        if (
            form &&
            saveButton
        ) {

            form.addEventListener(
                "submit",
                function (event) {


                    if (
                        !nameInput ||
                        nameInput.value.trim() === ""
                    ) {

                        event.preventDefault();


                        alert(
                            "Please enter category name."
                        );


                        if (nameInput) {

                            nameInput.focus();

                        }

                        return;

                    }

                    if (
                        !descriptionInput ||
                        descriptionInput.value.trim() === ""
                    ) {

                        event.preventDefault();

                        alert(
                            "Please enter a category description."
                        );

                        if (descriptionInput) {

                            descriptionInput.focus();

                        }

                        return;

                    }

                    if (
                        (!imageInput || !imageInput.files || imageInput.files.length === 0) &&
                        (!document.getElementById("categoryImagePreview") || document.getElementById("categoryImagePreview").style.display !== "flex")
                    ) {

                        event.preventDefault();

                        alert(
                            "Please upload a category image."
                        );

                        return;

                    }


                    if (formSubmitting) {

                        event.preventDefault();

                        return;

                    }


                    if (
                        imageInput &&
                        imageInput.files &&
                        imageInput.files.length > 0
                    ) {

                        const file =
                            imageInput.files[0];


                        if (
                            !validateImage(file)
                        ) {

                            event.preventDefault();

                            imageInput.focus();

                            return;

                        }

                    }


                    formSubmitting =
                        true;


                    saveButton.disabled =
                        true;


                    saveButton.style.opacity =
                        "0.75";


                    saveButton.style.cursor =
                        "wait";


                    saveButton.innerHTML =
                        "<span>✓</span>" +
                        "<span>Saving...</span>";

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

                const key =
                    event.key.toLowerCase();


                const activeElement =
                    document.activeElement;


                const isTyping =
                    activeElement &&
                    (
                        activeElement.tagName === "INPUT" ||
                        activeElement.tagName === "TEXTAREA" ||
                        activeElement.tagName === "SELECT" ||
                        activeElement.isContentEditable
                    );


                if (
                    event.key === "Escape"
                ) {

                    if (
                        document.activeElement &&
                        typeof document.activeElement.blur ===
                        "function"
                    ) {

                        document.activeElement.blur();

                    }

                    return;

                }


                if (isTyping) {

                    return;

                }


                if (
                    event.ctrlKey ||
                    event.altKey ||
                    event.metaKey
                ) {

                    return;

                }


                if (
                    key === "a"
                ) {

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


                if (
                    key === "b"
                ) {

                    event.preventDefault();


                    window.location.href =
                        "index.php";


                    return;

                }


                if (
                    key === "c"
                ) {

                    event.preventDefault();


                    window.location.href =
                        "index.php";


                    return;

                }


                if (
                    key === "n"
                ) {

                    event.preventDefault();


                    if (nameInput) {

                        nameInput.focus();

                        nameInput.select();

                    }

                    return;

                }


                if (
                    key === "g"
                ) {

                    event.preventDefault();


                    if (slugInput) {

                        slugInput.focus();

                        slugInput.select();

                    }

                    return;

                }


                if (
                    key === "i"
                ) {

                    event.preventDefault();


                    if (imageInput) {

                        imageInput.click();

                    }

                    return;

                }

            }
        );


        if (nameInput) {

            if (
                window.innerWidth > 700
            ) {

                setTimeout(
                    function () {

                        nameInput.focus();

                    },
                    150
                );

            }

        }

    }
);

</script>


<?php

/*
|--------------------------------------------------------------------------
| FOOTER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../includes/footer.php";

?>