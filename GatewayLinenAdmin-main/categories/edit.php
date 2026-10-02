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
/* ============================================================
   GATEWAYLINEN - EDIT CATEGORY (Fully Unified Theme)
   ============================================================ */

:root {
    --bg-page: #f5f7fa;
    --bg-card: #ffffff;
    --bg-input: #ffffff;
    --border: #d9e1ea;
    --border-soft: #e7edf3;
    --text-hi: #17212b;
    --text-body: #344454;
    --text-mute: #687789;
    --green: #10b981;
    --green-dark: #059669;
    --green-soft: #ecfdf5;
    --red: #dc2626;
    --red-soft: #fef2f2;
    --blue: #2563eb;
    --radius: 12px;
}

/* Comprehensive Dark Mode Selectors */
body.dark-mode, 
body[data-theme="dark"], 
body.dark,
.dark-mode, 
[data-theme="dark"],
html.dark {
    --bg-page: #0f172a;
    --bg-card: #1e293b;
    --bg-input: #1e293b;
    --border: #334155;
    --border-soft: #273548;
    --text-hi: #f8fafc;
    --text-body: #cbd5e1;
    --text-mute: #94a3b8;
    --green-soft: rgba(16, 185, 129, 0.15);
    --red-soft: rgba(220, 38, 38, 0.15);
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
    max-width:1320px;
    margin:0 auto;
    padding:8px 0 35px;
}

/* =========================
   PAGE HEADER
   ========================= */

.page-header{
    display:flex;
    align-items:flex-end;
    justify-content:space-between;
    gap:24px;
    padding:0 0 20px;
    margin-bottom:22px;
    border-bottom:1px solid var(--border);
}

.breadcrumb{
    display:flex;
    align-items:center;
    gap:9px;
    margin-bottom:10px;
    color:var(--text-mute);
    font-size:12px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.55px;
}

.breadcrumb .current{
    color:var(--green);
}

.page-header h1{
    margin:0;
    color:var(--text-hi);
    font-size:30px;
    line-height:1.2;
    font-weight:800;
    letter-spacing:-.3px;
}

.page-header p{
    margin:8px 0 0;
    color:var(--text-mute);
    font-size:14px;
    line-height:1.5;
}

.header-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

/* =========================
   BUTTONS
   ========================= */

.btn{
    min-height:42px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    padding:0 17px;
    border:1px solid var(--border);
    border-radius:8px;
    background:var(--bg-card);
    color:var(--text-body)!important;
    font-size:13px;
    font-weight:800;
    text-decoration:none;
    cursor:pointer;
    transition:.18s ease;
}

.btn:hover{
    border-color:var(--green);
    background:var(--green-soft);
    color:var(--green)!important;
}

.btn-primary{
    border-color:transparent;
    background:linear-gradient(135deg,var(--green-dark),var(--green));
    color:#ffffff!important;
    box-shadow:0 6px 16px rgba(16,185,129,.18);
}

.btn-primary:hover{
    color:#ffffff!important;
    transform:translateY(-1px);
}

.btn-danger{
    color:var(--red)!important;
}

.notice{
    margin-bottom:18px;
    padding:14px 16px;
    border-radius:9px;
    font-size:13px;
    line-height:1.5;
    font-weight:700;
}

.notice-error{
    border:1px solid rgba(220, 38, 38, 0.3);
    background:var(--red-soft);
    color:var(--red);
}

/* =========================
   MAIN GRID
   ========================= */

.edit-layout{
    display:grid;
    grid-template-columns:minmax(0,1fr) 390px;
    gap:18px;
}

/* =========================
   CARD
   ========================= */

.card{
    background:var(--bg-card);
    border:1px solid var(--border);
    border-radius:var(--radius);
    overflow:hidden;
    box-shadow:0 3px 14px rgba(15,23,42,.045);
}

.card-header{
    padding:18px 20px;
    background:var(--bg-card);
    border-bottom:1px solid var(--border-soft);
}

.card-header h2{
    margin:0;
    color:var(--text-hi);
    font-size:18px;
    line-height:1.35;
    font-weight:800;
}

.card-header p{
    margin:6px 0 0;
    color:var(--text-mute);
    font-size:13px;
    line-height:1.45;
}

.card-body{
    padding:24px 22px;
}

/* =========================
   FORM
   ========================= */

.form-group{
    margin-bottom:20px;
}

.form-label{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:8px;
    color:var(--text-hi);
    font-size:13px;
    line-height:1.4;
    font-weight:800;
}

.required{
    color:var(--red);
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
    font-size:14px;
    font-weight:500;
    transition:.18s ease;
    box-shadow:inset 0 1px 2px rgba(15,23,42,.025);
}

.input::placeholder,
.textarea::placeholder{
    color:var(--text-mute);
}

.input,
.select{
    height:46px;
    padding:0 14px;
}

.textarea{
    min-height:145px;
    padding:13px 14px;
    resize:vertical;
    line-height:1.6;
}

.input:hover,
.textarea:hover,
.select:hover{
    border-color:var(--text-mute);
}

.input:focus,
.textarea:focus,
.select:focus{
    border-color:var(--green);
    box-shadow:0 0 0 3px rgba(16,185,129,.15);
}

.slug-row{
    display:grid;
    grid-template-columns:1fr 120px;
    gap:12px;
}

.input-prefix{
    position:relative;
}

.input-prefix span{
    position:absolute;
    left:14px;
    top:50%;
    transform:translateY(-50%);
    color:var(--text-mute);
    font-size:14px;
    font-weight:700;
    pointer-events:none;
}

.input-prefix .input{
    padding-left:30px;
}

.help-text{
    margin-top:7px;
    color:var(--text-mute);
    font-size:11px;
    line-height:1.45;
}

.two-column{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:16px;
}

/* =========================
   STATUS
   ========================= */

.status-box{
    min-height:46px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:9px 13px;
    border:1px solid var(--border);
    border-radius:9px;
    background:var(--bg-input);
}

.status-info strong{
    display:block;
    color:var(--text-hi);
    font-size:13px;
    font-weight:800;
}

.status-info span{
    display:block;
    margin-top:3px;
    color:var(--text-mute);
    font-size:11px;
}

.switch{
    position:relative;
    width:46px;
    height:25px;
    flex-shrink:0;
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
    background:var(--border);
    transition:.2s;
}

.slider:before{
    content:"";
    position:absolute;
    width:19px;
    height:19px;
    left:3px;
    top:3px;
    border-radius:50%;
    background:#ffffff;
    box-shadow:0 1px 3px rgba(15,23,42,.18);
    transition:.2s;
}

.switch input:checked + .slider{
    background:var(--green);
}

.switch input:checked + .slider:before{
    transform:translateX(21px);
}

/* =========================
   IMAGE CARD
   ========================= */

.image-card{
    padding:20px;
    background:var(--bg-card);
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
    background:var(--bg-input);
    color:var(--text-mute);
    font-size:40px;
}

.image-preview img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
}

.file-input-wrap{
    margin-top:15px;
}

.file-input{
    width:100%;
    padding:11px;
    border:1px dashed var(--border);
    border-radius:8px;
    background:var(--bg-input);
    color:var(--text-body);
    font-size:12px;
    font-weight:600;
    cursor:pointer;
}

.file-input:hover{
    border-color:var(--green);
    background:var(--green-soft);
}

.file-info{
    margin-top:8px;
    color:var(--text-mute);
    font-size:11px;
    line-height:1.6;
}

.file-info strong{
    color:var(--text-hi);
}

.current-image{
    margin-top:13px;
    padding:11px;
    border:1px solid var(--border);
    border-radius:8px;
    background:var(--bg-input);
    color:var(--text-mute);
    font-size:11px;
    line-height:1.5;
    word-break:break-all;
}

/* =========================
   FOOTER ACTIONS
   ========================= */

.action-footer{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    padding:17px 22px;
    background:var(--bg-card);
    border-top:1px solid var(--border-soft);
}

/* =========================
   ACTION BUTTON SHORTCUT BADGES
   ========================= */

.btn-shortcut{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:30px;
    min-height:22px;
    padding:2px 7px;
    border:1px solid rgba(255,255,255,.28);
    border-radius:5px;
    background:rgba(255,255,255,.12);
    color:inherit;
    font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;
    font-size:10px;
    line-height:1;
    font-weight:800;
    white-space:nowrap;
}

.btn:not(.btn-primary) .btn-shortcut{
    border-color:var(--border);
    background:var(--bg-input);
    color:var(--green);
}

.btn-primary .btn-shortcut{
    color:#ffffff;
}

.btn{
    white-space:nowrap;
}

@media(max-width:1050px){
    .category-edit-page{
        max-width:100%;
        padding-left:15px;
        padding-right:15px;
    }
    .edit-layout{
        grid-template-columns:1fr 340px;
    }
}

@media(max-width:950px){
    .edit-layout{
        grid-template-columns:1fr;
    }
    .image-card{
        display:grid;
        grid-template-columns:260px 1fr;
        gap:22px;
        align-items:center;
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
        gap:15px;
    }
    .page-header h1{
        font-size:26px;
    }
    .page-header p{
        font-size:13px;
    }
    .header-actions{
        width:100%;
    }
    .header-actions .btn{
        flex:1;
    }
    .card-header{
        padding:16px;
    }
    .card-header h2{
        font-size:17px;
    }
    .card-body{
        padding:20px 16px;
    }
    .two-column,
    .slug-row{
        grid-template-columns:1fr;
    }
    .image-card{
        display:block;
        padding:16px;
    }
    .file-input-wrap{
        margin-top:14px;
    }
    .action-footer{
        flex-direction:column-reverse;
        padding:15px 16px;
    }
    .action-footer .btn{
        width:100%;
    }
}

@media(max-width:430px){
    .page-header h1{
        font-size:24px;
    }
    .breadcrumb{
        font-size:11px;
    }
    .input,
    .select{
        height:44px;
    }
    .textarea{
        min-height:130px;
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
                <span class="btn-shortcut">Esc</span>
                <span>← Back</span>
            </a>

            <button
                type="submit"
                form="categoryEditForm"
                class="btn btn-primary"
                title="Save (Ctrl + S)"
            >
                <span>✓ Save Changes</span>
                <span class="btn-shortcut">Ctrl+S</span>
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
                        <span class="btn-shortcut">Esc</span>
                        <span>Cancel</span>
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        <span>✓ Update Category</span>
                        <span class="btn-shortcut">Ctrl+S</span>
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