<?php
session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$activeMenu = 'categories';
$pageTitle  = 'GatewayLinen | Edit Category';

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

function slugify(string $text): string
{
    $text = trim($text);

    if ($text === '') {
        return '';
    }

    $text = strtolower($text);

    $text = preg_replace(
        '/[^a-z0-9]+/',
        '-',
        $text
    );

    $text = trim($text, '-');

    return $text;
}

function uploadDirectory(): string
{
    $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads'
         . DIRECTORY_SEPARATOR . 'categories';

    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    return $dir;
}

function imageWebPath(string $file): string
{
    $file = trim($file);

    if ($file === '') {
        return '';
    }

    if (
        preg_match(
            '~^(https?:)?//|^data:image/~i',
            $file
        )
    ) {
        return $file;
    }

    $file = str_replace('\\', '/', $file);

    $basename = basename(
        parse_url($file, PHP_URL_PATH) ?: $file
    );

    if (
        $basename === '' ||
        $basename === '.' ||
        $basename === '..'
    ) {
        return '';
    }

    $script = str_replace(
        '\\',
        '/',
        $_SERVER['SCRIPT_NAME'] ?? '/categories/edit.php'
    );

    $appRoot = dirname(dirname($script));

    $appRoot = trim(
        str_replace('\\', '/', $appRoot),
        '/'
    );

    $prefix =
        ($appRoot === '' || $appRoot === '.')
            ? ''
            : '/' . $appRoot;

    return $prefix .
        '/uploads/categories/' .
        rawurlencode($basename);
}

function deleteImageFile(string $imagePath): void
{
    $imagePath = trim($imagePath);

    if ($imagePath === '') {
        return;
    }

    /*
     * Never delete remote images.
     */
    if (
        preg_match(
            '~^(https?:)?//|^data:image/~i',
            $imagePath
        )
    ) {
        return;
    }

    $basename = basename(
        parse_url(
            str_replace('\\', '/', $imagePath),
            PHP_URL_PATH
        ) ?: $imagePath
    );

    if (
        $basename === '' ||
        $basename === '.' ||
        $basename === '..'
    ) {
        return;
    }

    $baseDir = realpath(uploadDirectory());

    if ($baseDir === false) {
        return;
    }

    $fullPath = $baseDir .
        DIRECTORY_SEPARATOR .
        $basename;

    /*
     * Delete only files inside categories upload folder.
     */
    if (
        is_file($fullPath) &&
        realpath(dirname($fullPath)) === $baseDir
    ) {
        @unlink($fullPath);
    }
}

/*
|--------------------------------------------------------------------------
| GET CATEGORY ID
|--------------------------------------------------------------------------
*/

$categoryId = (int)($_GET['id'] ?? $_POST['category_id'] ?? 0);

if ($categoryId <= 0) {
    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| LOAD CATEGORY
|--------------------------------------------------------------------------
*/

$loadSql = "
    SELECT
        CategoryId,
        Name,
        Slug,
        Description,
        ImageUrl,
        DisplayOrder,
        IsActive,
        CreatedAt
    FROM dbo.Categories
    WHERE CategoryId = ?
";

$loadStmt = sqlsrv_query(
    $conn,
    $loadSql,
    [$categoryId]
);

if ($loadStmt === false) {
    die('Unable to load category.');
}

$category = sqlsrv_fetch_array(
    $loadStmt,
    SQLSRV_FETCH_ASSOC
);

sqlsrv_free_stmt($loadStmt);

if (!$category) {
    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| DEFAULT VALUES
|--------------------------------------------------------------------------
*/

$name = (string)($category['Name'] ?? '');
$slug = (string)($category['Slug'] ?? '');
$description = (string)($category['Description'] ?? '');
$imageUrl = (string)($category['ImageUrl'] ?? '');
$displayOrder = (int)($category['DisplayOrder'] ?? 1);
$isActive = !empty($category['IsActive']);

$successMessage = '';
$errorMessage = '';

/*
|--------------------------------------------------------------------------
| UPDATE CATEGORY
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'update_category'
) {

    /*
     * CSRF
     */
    $postedToken = (string)($_POST['csrf_token'] ?? '');

    if (
        !hash_equals(
            $_SESSION['csrf_token'],
            $postedToken
        )
    ) {
        $errorMessage =
            'Security verification failed. Please refresh the page and try again.';
    } else {

        /*
         * INPUT
         */
        $name = trim(
            (string)($_POST['name'] ?? '')
        );

        $slug = trim(
            (string)($_POST['slug'] ?? '')
        );

        $description = trim(
            (string)($_POST['description'] ?? '')
        );

        $displayOrder = max(
            1,
            (int)($_POST['display_order'] ?? 1)
        );

        $isActive =
            isset($_POST['is_active']) &&
            $_POST['is_active'] === '1';

        /*
         * AUTO SLUG
         */
        if ($slug === '') {
            $slug = slugify($name);
        } else {
            $slug = slugify($slug);
        }

        /*
         * VALIDATION
         */
        if ($name === '') {
            $errorMessage =
                'Category name is required.';
        } elseif (strlen($name) > 150) {
            $errorMessage =
                'Category name cannot exceed 150 characters.';
        } elseif ($slug === '') {
            $errorMessage =
                'A valid slug could not be generated.';
        } elseif (strlen($slug) > 180) {
            $errorMessage =
                'Slug cannot exceed 180 characters.';
        }

        /*
         * CHECK DUPLICATE NAME / SLUG
         */
        if ($errorMessage === '') {

            $duplicateSql = "
                SELECT TOP 1 CategoryId
                FROM dbo.Categories
                WHERE
                    CategoryId <> ?
                    AND (
                        Name = ?
                        OR Slug = ?
                    )
            ";

            $duplicateStmt = sqlsrv_query(
                $conn,
                $duplicateSql,
                [
                    $categoryId,
                    $name,
                    $slug
                ]
            );

            if ($duplicateStmt === false) {

                $errorMessage =
                    'Unable to validate category information.';

            } else {

                $duplicate =
                    sqlsrv_fetch_array(
                        $duplicateStmt,
                        SQLSRV_FETCH_ASSOC
                    );

                sqlsrv_free_stmt(
                    $duplicateStmt
                );

                if ($duplicate) {
                    $errorMessage =
                        'Another category already uses this name or slug.';
                }
            }
        }

        /*
         * IMAGE UPLOAD
         */
        $newImageUrl = $imageUrl;
        $newUploadedFile = '';

        if (
            $errorMessage === '' &&
            isset($_FILES['category_image']) &&
            $_FILES['category_image']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            $file = $_FILES['category_image'];

            if (
                $file['error'] !== UPLOAD_ERR_OK
            ) {

                $errorMessage =
                    'Image upload failed. Please try again.';

            } elseif (
                $file['size'] > 5 * 1024 * 1024
            ) {

                $errorMessage =
                    'Image size must be 5 MB or less.';

            } else {

                /*
                 * Detect MIME type safely.
                 */
                $finfo = new finfo(
                    FILEINFO_MIME_TYPE
                );

                $mime = $finfo->file(
                    $file['tmp_name']
                );

                $allowedMime = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                    'image/gif'  => 'gif'
                ];

                if (
                    !isset(
                        $allowedMime[$mime]
                    )
                ) {

                    $errorMessage =
                        'Only JPG, PNG, WEBP or GIF images are allowed.';

                } else {

                    $extension =
                        $allowedMime[$mime];

                    /*
                     * Unique automatic filename.
                     */
                    $newUploadedFile =
                        'category_' .
                        date('Ymd_His') .
                        '_' .
                        bin2hex(
                            random_bytes(5)
                        ) .
                        '.' .
                        $extension;

                    $uploadDir =
                        uploadDirectory();

                    $destination =
                        $uploadDir .
                        DIRECTORY_SEPARATOR .
                        $newUploadedFile;

                    if (
                        !move_uploaded_file(
                            $file['tmp_name'],
                            $destination
                        )
                    ) {

                        $errorMessage =
                            'Unable to save the uploaded image.';

                    } else {

                        $newImageUrl =
                            'uploads/categories/' .
                            $newUploadedFile;
                    }
                }
            }
        }

        /*
         * UPDATE DATABASE
         */
        if ($errorMessage === '') {

            $updateSql = "
                UPDATE dbo.Categories
                SET
                    Name = ?,
                    Slug = ?,
                    Description = ?,
                    ImageUrl = ?,
                    DisplayOrder = ?,
                    IsActive = ?
                WHERE CategoryId = ?
            ";

            $params = [
                $name,
                $slug,
                $description !== ''
                    ? $description
                    : null,
                $newImageUrl !== ''
                    ? $newImageUrl
                    : null,
                $displayOrder,
                $isActive ? 1 : 0,
                $categoryId
            ];

            $updateStmt = sqlsrv_query(
                $conn,
                $updateSql,
                $params
            );

            if ($updateStmt === false) {

                /*
                 * If DB update fails, remove newly uploaded image.
                 */
                if (
                    $newUploadedFile !== ''
                ) {
                    deleteImageFile(
                        'uploads/categories/' .
                        $newUploadedFile
                    );
                }

                $errorMessage =
                    'Category could not be updated. Please try again.';

            } else {

                sqlsrv_free_stmt(
                    $updateStmt
                );

                /*
                 * Delete old image only after successful DB update.
                 */
                if (
                    $newUploadedFile !== '' &&
                    $imageUrl !== '' &&
                    $imageUrl !== $newImageUrl
                ) {
                    deleteImageFile(
                        $imageUrl
                    );
                }

                /*
                 * Success.
                 */
                header(
                    'Location: index.php?updated=1'
                );
                exit;
            }
        }
    }
}

$currentImage = imageWebPath($imageUrl);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<style>

:root{
    --bg-page:#0a1119;
    --bg-card:#111b26;
    --bg-card-alt:#0f1823;
    --bg-input:#0d1620;
    --border:#1e2d3d;
    --border-soft:#182636;

    --text-hi:#f0f4f8;
    --text-body:#a8b8c8;
    --text-mute:#6b7f91;

    --green:#10b981;
    --green-dark:#059669;
    --green-soft:rgba(16,185,129,.12);

    --red:#ef4444;
    --red-soft:rgba(239,68,68,.12);

    --blue:#38bdf8;

    --radius:12px;
}

*{
    box-sizing:border-box;
}

html,
body,
.main,
.content{
    background:var(--bg-page)!important;
    color:var(--text-body)!important;
}

.category-edit-page{
    width:100%;
    max-width:1250px;
    margin:0 auto;
    padding:0 0 30px;
}

.page-header{
    display:flex;
    align-items:flex-end;
    justify-content:space-between;
    gap:20px;

    padding-bottom:18px;
    margin-bottom:20px;

    border-bottom:1px solid var(--border);
}

.breadcrumb{
    display:flex;
    gap:8px;

    margin-bottom:8px;

    color:var(--text-mute);
    font-size:11px;
    font-weight:800;

    text-transform:uppercase;
    letter-spacing:.4px;
}

.breadcrumb .current{
    color:var(--green);
}

.page-header h1{
    margin:0;

    color:var(--text-hi);

    font-size:26px;
    font-weight:800;
}

.page-header p{
    margin:6px 0 0;

    color:var(--text-mute);

    font-size:12px;
}

.header-actions{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}

.btn{
    min-height:39px;

    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:7px;

    padding:0 14px;

    border:1px solid var(--border);
    border-radius:8px;

    background:var(--bg-input);
    color:var(--text-body)!important;

    font-size:11px;
    font-weight:800;

    text-decoration:none;

    cursor:pointer;

    transition:.18s;
}

.btn:hover{
    border-color:var(--green);
    background:var(--green-soft);
    color:var(--green)!important;
}

.btn-primary{
    border-color:transparent;

    background:linear-gradient(
        135deg,
        var(--green-dark),
        var(--green)
    );

    color:#fff!important;

    box-shadow:
        0 7px 20px
        rgba(16,185,129,.18);
}

.btn-primary:hover{
    color:#fff!important;
    transform:translateY(-1px);
}

.btn-danger{
    color:#f87171!important;
}

.notice{
    margin-bottom:15px;

    padding:12px 14px;

    border-radius:9px;

    font-size:12px;
    font-weight:700;
}

.notice-error{
    border:1px solid rgba(239,68,68,.3);
    background:var(--red-soft);
    color:#fca5a5;
}

.edit-layout{
    display:grid;

    grid-template-columns:
        minmax(0,1fr)
        360px;

    gap:16px;
}

.card{
    background:var(--bg-card);

    border:1px solid var(--border);

    border-radius:var(--radius);

    overflow:hidden;
}

.card-header{
    padding:16px 18px;

    border-bottom:1px solid var(--border);
}

.card-header h2{
    margin:0;

    color:var(--text-hi);

    font-size:15px;
    font-weight:800;
}

.card-header p{
    margin:5px 0 0;

    color:var(--text-mute);

    font-size:11px;
}

.card-body{
    padding:20px;
}

.form-group{
    margin-bottom:17px;
}

.form-label{
    display:flex;
    align-items:center;
    justify-content:space-between;

    margin-bottom:7px;

    color:var(--text-body);

    font-size:11px;
    font-weight:800;
}

.required{
    color:#f87171;
}

.input,
.textarea,
.select{
    width:100%;

    border:1px solid var(--border);
    border-radius:8px;

    outline:none;

    background:var(--bg-input);
    color:var(--text-hi);

    font-family:inherit;
    font-size:12px;

    transition:.18s;
}

.input,
.select{
    height:42px;
    padding:0 12px;
}

.textarea{
    min-height:125px;

    padding:12px;

    resize:vertical;

    line-height:1.5;
}

.input:focus,
.textarea:focus,
.select:focus{
    border-color:var(--green);

    box-shadow:
        0 0 0 3px
        rgba(16,185,129,.1);
}

.slug-row{
    display:grid;

    grid-template-columns:1fr 105px;

    gap:10px;
}

.input-prefix{
    position:relative;
}

.input-prefix span{
    position:absolute;

    left:12px;
    top:50%;

    transform:translateY(-50%);

    color:var(--text-mute);

    font-size:12px;

    pointer-events:none;
}

.input-prefix .input{
    padding-left:25px;
}

.help-text{
    margin-top:6px;

    color:var(--text-mute);

    font-size:10px;
}

.two-column{
    display:grid;

    grid-template-columns:
        1fr 1fr;

    gap:12px;
}

.status-box{
    display:flex;
    align-items:center;
    justify-content:space-between;

    padding:13px 14px;

    border:1px solid var(--border);
    border-radius:9px;

    background:var(--bg-input);
}

.status-info strong{
    display:block;

    color:var(--text-hi);

    font-size:12px;
}

.status-info span{
    display:block;

    margin-top:3px;

    color:var(--text-mute);

    font-size:10px;
}

.switch{
    position:relative;

    width:44px;
    height:24px;
}

.switch input{
    opacity:0;
    width:0;
    height:0;
}

.slider{
    position:absolute;
    inset:0;

    cursor:pointer;

    border-radius:30px;

    background:#263544;

    transition:.2s;
}

.slider:before{
    content:"";

    position:absolute;

    width:18px;
    height:18px;

    left:3px;
    top:3px;

    border-radius:50%;

    background:#fff;

    transition:.2s;
}

.switch input:checked + .slider{
    background:var(--green);
}

.switch input:checked + .slider:before{
    transform:translateX(20px);
}

.image-card{
    padding:18px;
}

.image-preview{
    width:100%;
    aspect-ratio:1/1;

    display:flex;
    align-items:center;
    justify-content:center;

    overflow:hidden;

    border:1px solid var(--border);

    border-radius:10px;

    background:
        linear-gradient(
            135deg,
            #16222e,
            #0f1823
        );

    color:var(--text-mute);

    font-size:35px;
}

.image-preview img{
    width:100%;
    height:100%;

    object-fit:cover;

    display:block;
}

.file-input-wrap{
    margin-top:12px;
}

.file-input{
    width:100%;

    padding:10px;

    border:1px dashed var(--border);

    border-radius:8px;

    background:var(--bg-input);

    color:var(--text-body);

    font-size:11px;

    cursor:pointer;
}

.file-input:hover{
    border-color:var(--green);
}

.file-info{
    margin-top:7px;

    color:var(--text-mute);

    font-size:10px;

    line-height:1.5;
}

.current-image{
    margin-top:12px;

    padding:10px;

    border:1px solid var(--border-soft);

    border-radius:8px;

    background:var(--bg-input);

    color:var(--text-mute);

    font-size:10px;

    word-break:break-all;
}

.action-footer{
    display:flex;

    justify-content:flex-end;

    gap:8px;

    padding:15px 20px;

    border-top:1px solid var(--border);
}

.shortcut-box{
    margin-top:16px;

    padding:15px 18px;

    border:1px solid var(--border);

    border-radius:12px;

    background:var(--bg-card);
}

.shortcut-title{
    display:flex;
    align-items:center;
    gap:8px;

    color:var(--text-hi);

    font-size:12px;
    font-weight:800;
}

.shortcut-grid{
    display:grid;

    grid-template-columns:
        repeat(3,minmax(0,1fr));

    gap:8px;

    margin-top:12px;
}

.shortcut{
    display:flex;
    align-items:center;
    gap:8px;

    padding:8px 10px;

    border:1px solid var(--border-soft);

    border-radius:7px;

    background:var(--bg-input);
}

.key{
    display:inline-flex;
    align-items:center;
    justify-content:center;

    min-width:38px;
    height:24px;

    padding:0 6px;

    border:1px solid var(--border);

    border-radius:5px;

    background:#080e15;

    color:var(--green);

    font-family:monospace;
    font-size:9px;
    font-weight:800;
}

.key-text{
    color:var(--text-body);

    font-size:10px;
    font-weight:600;
}

@media(max-width:950px){

    .edit-layout{
        grid-template-columns:1fr;
    }

    .image-card{
        display:grid;

        grid-template-columns:
            220px 1fr;

        gap:18px;
    }

    .file-input-wrap{
        margin-top:0;
        align-self:center;
    }

}

@media(max-width:650px){

    .category-edit-page{
        padding:0 10px 25px;
    }

    .page-header{
        flex-direction:column;
        align-items:flex-start;
    }

    .header-actions{
        width:100%;
    }

    .header-actions .btn{
        flex:1;
    }

    .two-column,
    .slug-row{
        grid-template-columns:1fr;
    }

    .image-card{
        display:block;
    }

    .file-input-wrap{
        margin-top:12px;
    }

    .shortcut-grid{
        grid-template-columns:1fr;
    }

    .action-footer{
        flex-direction:column-reverse;
    }

    .action-footer .btn{
        width:100%;
    }

}

</style>


<main class="main">

<section class="content">

<div class="category-edit-page">

    <!-- HEADER -->

    <div class="page-header">

        <div>

            <div class="breadcrumb">
                <span>Products</span>
                <span>/</span>
                <span>Categories</span>
                <span>/</span>
                <span class="current">Edit</span>
            </div>

            <h1>Edit Category</h1>

            <p>
                Update category information, image, order and status.
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
                form="categoryEditForm"
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
        enctype="multipart/form-data"
        id="categoryEditForm"
        autocomplete="off"
    >

        <input
            type="hidden"
            name="action"
            value="update_category"
        >

        <input
            type="hidden"
            name="category_id"
            value="<?= $categoryId ?>"
        >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e($csrfToken) ?>"
        >


        <div class="edit-layout">


            <!-- LEFT -->

            <div class="card">

                <div class="card-header">

                    <h2>Category Information</h2>

                    <p>
                        Edit the main category details.
                    </p>

                </div>


                <div class="card-body">


                    <!-- NAME -->

                    <div class="form-group">

                        <label class="form-label">

                            <span>
                                Category Name
                                <span class="required">*</span>
                            </span>

                            <span id="nameCounter">
                                <?= strlen($name) ?>/150
                            </span>

                        </label>

                        <input
                            type="text"
                            name="name"
                            id="categoryName"
                            class="input"
                            value="<?= e($name) ?>"
                            maxlength="150"
                            required
                            placeholder="e.g. Bedsheets"
                        >

                    </div>


                    <!-- SLUG -->

                    <div class="form-group">

                        <label class="form-label">

                            <span>
                                Slug
                                <span class="required">*</span>
                            </span>

                        </label>

                        <div class="input-prefix">

                            <span>/</span>

                            <input
                                type="text"
                                name="slug"
                                id="categorySlug"
                                class="input"
                                value="<?= e($slug) ?>"
                                maxlength="180"
                                required
                                placeholder="bedsheets"
                            >

                        </div>

                        <div class="help-text">
                            Slug is automatically generated from category name.
                            You can also edit it manually.
                        </div>

                    </div>


                    <!-- DESCRIPTION -->

                    <div class="form-group">

                        <label class="form-label">

                            <span>
                                Description
                            </span>

                            <span id="descriptionCounter">
                                <?= strlen($description) ?>/1000
                            </span>

                        </label>

                        <textarea
                            name="description"
                            id="categoryDescription"
                            class="textarea"
                            maxlength="1000"
                            placeholder="Write a short category description..."
                        ><?= e($description) ?></textarea>

                    </div>


                    <!-- ORDER + STATUS -->

                    <div class="two-column">

                        <div class="form-group">

                            <label class="form-label">
                                Display Order
                            </label>

                            <input
                                type="number"
                                name="display_order"
                                class="input"
                                value="<?= $displayOrder ?>"
                                min="1"
                                step="1"
                            >

                            <div class="help-text">
                                Category position in the list.
                            </div>

                        </div>


                        <div class="form-group">

                            <label class="form-label">
                                Category Status
                            </label>

                            <div class="status-box">

                                <div class="status-info">

                                    <strong id="statusText">
                                        <?= $isActive
                                            ? 'Active'
                                            : 'Inactive'
                                        ?>
                                    </strong>

                                    <span>
                                        Category visibility
                                    </span>

                                </div>

                                <label class="switch">

                                    <input
                                        type="checkbox"
                                        name="is_active"
                                        value="1"
                                        id="activeSwitch"
                                        <?= $isActive
                                            ? 'checked'
                                            : ''
                                        ?>
                                    >

                                    <span class="slider"></span>

                                </label>

                            </div>

                        </div>

                    </div>


                </div>


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
                    >
                        ✓ Update Category
                    </button>

                </div>

            </div>


            <!-- RIGHT -->

            <div class="card">

                <div class="card-header">

                    <h2>Category Image</h2>

                    <p>
                        Upload or replace the category image.
                    </p>

                </div>


                <div class="image-card">

                    <div
                        class="image-preview"
                        id="imagePreview"
                    >

                        <?php if ($currentImage !== ''): ?>

                            <img
                                src="<?= e($currentImage) ?>"
                                alt="<?= e($name) ?>"
                                id="previewImage"
                            >

                        <?php else: ?>

                            <span id="previewPlaceholder">
                                ◈
                            </span>

                        <?php endif; ?>

                    </div>


                    <div class="file-input-wrap">

                        <label class="form-label">
                            Replace Image
                        </label>

                        <input
                            type="file"
                            name="category_image"
                            id="categoryImage"
                            class="file-input"
                            accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif"
                        >

                        <div class="file-info">

                            Maximum size:
                            <strong>5 MB</strong>

                            <br>

                            Allowed:
                            JPG, PNG, WEBP, GIF

                            <br>

                            New filename is generated automatically.

                        </div>

                        <?php if ($imageUrl !== ''): ?>

                            <div class="current-image">

                                Current file:
                                <br>

                                <?= e($imageUrl) ?>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>

    </form>


    <!-- SHORTCUTS -->

    <div class="shortcut-box">

        <div class="shortcut-title">

            ⌨ Keyboard Shortcuts

        </div>

        <div class="shortcut-grid">

            <div class="shortcut">
                <span class="key">Ctrl+S</span>
                <span class="key-text">Save Changes</span>
            </div>

            <div class="shortcut">
                <span class="key">Ctrl+Shift+S</span>
                <span class="key-text">Save Changes</span>
            </div>

            <div class="shortcut">
                <span class="key">Esc</span>
                <span class="key-text">Cancel / Back</span>
            </div>

        </div>

    </div>


</div>

</section>

</main>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function(){

        const form =
            document.getElementById(
                'categoryEditForm'
            );

        const nameInput =
            document.getElementById(
                'categoryName'
            );

        const slugInput =
            document.getElementById(
                'categorySlug'
            );

        const imageInput =
            document.getElementById(
                'categoryImage'
            );

        const preview =
            document.getElementById(
                'imagePreview'
            );

        const description =
            document.getElementById(
                'categoryDescription'
            );

        const nameCounter =
            document.getElementById(
                'nameCounter'
            );

        const descriptionCounter =
            document.getElementById(
                'descriptionCounter'
            );

        const activeSwitch =
            document.getElementById(
                'activeSwitch'
            );

        const statusText =
            document.getElementById(
                'statusText'
            );


        /*
        |--------------------------------------------------------------------------
        | SLUG GENERATOR
        |--------------------------------------------------------------------------
        */

        function makeSlug(value){

            return value
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');

        }


        let slugManuallyEdited =
            slugInput.value.trim() !==
            makeSlug(nameInput.value);


        nameInput.addEventListener(
            'input',
            function(){

                nameCounter.textContent =
                    this.value.length +
                    '/150';

                if (!slugManuallyEdited) {

                    slugInput.value =
                        makeSlug(this.value);

                }

            }
        );


        slugInput.addEventListener(
            'input',
            function(){

                const generated =
                    makeSlug(
                        nameInput.value
                    );

                slugManuallyEdited =
                    this.value !== generated;

            }
        );


        /*
        |--------------------------------------------------------------------------
        | DESCRIPTION COUNTER
        |--------------------------------------------------------------------------
        */

        description.addEventListener(
            'input',
            function(){

                descriptionCounter.textContent =
                    this.value.length +
                    '/1000';

            }
        );


        /*
        |--------------------------------------------------------------------------
        | STATUS TEXT
        |--------------------------------------------------------------------------
        */

        function updateStatus(){

            statusText.textContent =
                activeSwitch.checked
                    ? 'Active'
                    : 'Inactive';

        }

        activeSwitch.addEventListener(
            'change',
            updateStatus
        );


        /*
        |--------------------------------------------------------------------------
        | IMAGE PREVIEW
        |--------------------------------------------------------------------------
        */

        imageInput.addEventListener(
            'change',
            function(){

                const file =
                    this.files[0];

                if (!file) {
                    return;
                }

                const allowed = [
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                    'image/gif'
                ];

                if (
                    !allowed.includes(
                        file.type
                    )
                ) {

                    alert(
                        'Please select JPG, PNG, WEBP or GIF image.'
                    );

                    this.value = '';

                    return;
                }

                if (
                    file.size >
                    5 * 1024 * 1024
                ) {

                    alert(
                        'Image size must be 5 MB or less.'
                    );

                    this.value = '';

                    return;
                }

                const reader =
                    new FileReader();

                reader.onload =
                    function(event){

                        preview.innerHTML = '';

                        const img =
                            document.createElement(
                                'img'
                            );

                        img.src =
                            event.target.result;

                        img.alt =
                            'New Category Image';

                        preview.appendChild(
                            img
                        );

                    };

                reader.readAsDataURL(
                    file
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | KEYBOARD SHORTCUTS
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            'keydown',
            function(e){

                /*
                 * Ctrl + S
                 */
                if (
                    e.ctrlKey &&
                    !e.shiftKey &&
                    e.key.toLowerCase() === 's'
                ) {

                    e.preventDefault();

                    form.requestSubmit();

                    return;
                }


                /*
                 * Ctrl + Shift + S
                 */
                if (
                    e.ctrlKey &&
                    e.shiftKey &&
                    e.key.toLowerCase() === 's'
                ) {

                    e.preventDefault();

                    form.requestSubmit();

                    return;
                }


                /*
                 * Escape
                 */
                if (
                    e.key === 'Escape'
                ) {

                    /*
                     * Do not leave if user is typing
                     * inside a textarea/input.
                     */
                    const tag =
                        e.target?.tagName
                            ?.toLowerCase();

                    if (
                        tag === 'input' ||
                        tag === 'textarea' ||
                        tag === 'select'
                    ) {

                        if (
                            e.target.value
                        ) {

                            e.target.blur();

                            return;
                        }
                    }

                    window.location.href =
                        'index.php';

                }

            }
        );


        /*
        |--------------------------------------------------------------------------
        | FORM SUBMIT PROTECTION
        |--------------------------------------------------------------------------
        */

        let submitting = false;

        form.addEventListener(
            'submit',
            function(){

                if (submitting) {

                    return;

                }

                submitting = true;

                const buttons =
                    form.querySelectorAll(
                        'button[type="submit"]'
                    );

                buttons.forEach(
                    function(button){

                        button.disabled = true;

                        button.dataset.originalText =
                            button.innerHTML;

                        button.innerHTML =
                            '⏳ Saving...';

                    }
                );

            }
        );

    }
);

</script>


<?php
require_once __DIR__ . '/../includes/footer.php';
?>