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

require_once __DIR__ . '/../config/database.php';

$activeMenu = 'products';
$pageTitle  = 'GatewayLinen | Edit Product';

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] = $_SESSION['admin_username'] ?? 'GatewayLinen Administrator';
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
function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function slugify(string $text): string {
    $text = trim($text);
    if ($text === '') return '';
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

function uploadDirectory(): string {
    $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'products';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

function deleteProductFile(string $imagePath): void {
    $imagePath = trim($imagePath);
    if ($imagePath === '') return;
    if (preg_match('~^(https?:)?//|^data:image/~i', $imagePath)) return;

    $basename = basename(parse_url(str_replace('\\', '/', $imagePath), PHP_URL_PATH) ?: $imagePath);
    if ($basename === '' || $basename === '.' || $basename === '..') return;

    $baseDir = realpath(uploadDirectory());
    if ($baseDir === false) return;

    $fullPath = $baseDir . DIRECTORY_SEPARATOR . $basename;
    if (is_file($fullPath) && realpath(dirname($fullPath)) === $baseDir) {
        @unlink($fullPath);
    }
}

/*
|--------------------------------------------------------------------------
| GET PRODUCT ID & LOAD PRODUCT
|--------------------------------------------------------------------------
*/
$productId = (int)($_GET['id'] ?? $_POST['product_id'] ?? 0);

if ($productId <= 0) {
    header('Location: index.php');
    exit;
}

$loadSql = "SELECT * FROM dbo.Products WHERE ProductId = ?";
$loadStmt = sqlsrv_query($conn, $loadSql, [$productId]);

if ($loadStmt === false) {
    die('Unable to load product.');
}

$product = sqlsrv_fetch_array($loadStmt, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($loadStmt);

if (!$product) {
    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| FETCH ALL IMAGES FOR THIS PRODUCT
|--------------------------------------------------------------------------
*/
$imagesSql = "
    SELECT ImageId, ImageUrl, AltText, IsMain, DisplayOrder 
    FROM dbo.ProductImages 
    WHERE ProductId = ? 
    ORDER BY IsMain DESC, DisplayOrder ASC, ImageId ASC
";
$imagesStmt = sqlsrv_query($conn, $imagesSql, [$productId]);
$existingImages = [];
if ($imagesStmt !== false) {
    while ($imgRow = sqlsrv_fetch_array($imagesStmt, SQLSRV_FETCH_ASSOC)) {
        $existingImages[] = $imgRow;
    }
    sqlsrv_free_stmt($imagesStmt);
}

/*
|--------------------------------------------------------------------------
| FETCH CATEGORIES FOR DROPDOWN
|--------------------------------------------------------------------------
*/
$catSql = "SELECT CategoryId, Name FROM dbo.Categories ORDER BY Name ASC";
$catStmt = sqlsrv_query($conn, $catSql);
$categoriesList = [];
if ($catStmt !== false) {
    while ($crow = sqlsrv_fetch_array($catStmt, SQLSRV_FETCH_ASSOC)) {
        $categoriesList[] = $crow;
    }
    sqlsrv_free_stmt($catStmt);
}

/*
|--------------------------------------------------------------------------
| DEFAULT VALUES
|--------------------------------------------------------------------------
*/
$categoryId       = (int)($product['CategoryId'] ?? 0);
$name             = (string)($product['Name'] ?? '');
$slug             = (string)($product['Slug'] ?? '');
$shortDescription = (string)($product['ShortDescription'] ?? '');
$description      = (string)($product['Description'] ?? '');
$specifications   = (string)($product['Specifications'] ?? '');
$careInstructions = (string)($product['CareInstructions'] ?? '');
$basePrice        = (string)($product['BasePrice'] ?? '0.00');
$gstPercentage    = (string)($product['GstPercentage'] ?? '0.00');
$pstPercentage    = (string)($product['PstPercentage'] ?? '0.00');
$metaTitle        = (string)($product['MetaTitle'] ?? '');
$metaDescription  = (string)($product['MetaDescription'] ?? '');

$isActive     = !empty($product['IsActive']);
$isFeatured   = !empty($product['IsFeatured']);
$isNewArrival = !empty($product['IsNewArrival']);
$isBestSeller = !empty($product['IsBestSeller']);

$errorMessage = '';

/*
|--------------------------------------------------------------------------
| UPDATE PRODUCT SUBMISSION
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_product') {
    $postedToken = (string)($_POST['csrf_token'] ?? '');

    if (!hash_equals($_SESSION['csrf_token'], $postedToken)) {
        $errorMessage = 'Security verification failed. Please refresh the page and try again.';
    } else {
        $categoryId       = (int)($_POST['category_id'] ?? 0);
        $name             = trim((string)($_POST['name'] ?? ''));
        $slug             = trim((string)($_POST['slug'] ?? ''));
        $shortDescription = trim((string)($_POST['short_description'] ?? ''));
        $description      = trim((string)($_POST['description'] ?? ''));
        $specifications   = trim((string)($_POST['specifications'] ?? ''));
        $careInstructions = trim((string)($_POST['care_instructions'] ?? ''));
        $basePrice        = trim((string)($_POST['base_price'] ?? '0.00'));
        $gstPercentage    = trim((string)($_POST['gst_percentage'] ?? '0.00'));
        $pstPercentage    = trim((string)($_POST['pst_percentage'] ?? '0.00'));
        $metaTitle        = trim((string)($_POST['meta_title'] ?? ''));
        $metaDescription  = trim((string)($_POST['meta_description'] ?? ''));

        $isActive     = isset($_POST['is_active']) && $_POST['is_active'] === '1';
        $isFeatured   = isset($_POST['is_featured']) && $_POST['is_featured'] === '1';
        $isNewArrival = isset($_POST['is_new_arrival']) && $_POST['is_new_arrival'] === '1';
        $isBestSeller = isset($_POST['is_best_seller']) && $_POST['is_best_seller'] === '1';

        $mainImageId  = (int)($_POST['main_image_id'] ?? 0);
        $deleteImages = $_POST['delete_images'] ?? [];

        if ($slug === '') {
            $slug = slugify($name);
        } else {
            $slug = slugify($slug);
        }

        if ($categoryId <= 0) {
            $errorMessage = 'Please select a valid category.';
        } elseif ($name === '') {
            $errorMessage = 'Product name is required.';
        } elseif (strlen($name) > 255) {
            $errorMessage = 'Product name cannot exceed 255 characters.';
        } elseif ($slug === '') {
            $errorMessage = 'A valid slug could not be generated.';
        } elseif (!is_numeric($basePrice) || (float)$basePrice < 0) {
            $errorMessage = 'Please enter a valid base price.';
        }

        // Duplicate Slug Check
        if ($errorMessage === '') {
            $dupSql = "SELECT TOP 1 ProductId FROM dbo.Products WHERE ProductId <> ? AND Slug = ?";
            $dupStmt = sqlsrv_query($conn, $dupSql, [$productId, $slug]);
            if ($dupStmt !== false) {
                if (sqlsrv_fetch_array($dupStmt, SQLSRV_FETCH_ASSOC)) {
                    $errorMessage = 'Another product already uses this slug. Please change it.';
                }
                sqlsrv_free_stmt($dupStmt);
            }
        }

        $allowedMime = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif'
        ];

        /*
        |--------------------------------------------------------------------------
        | EXECUTE UPDATE TRANSACTION
        |--------------------------------------------------------------------------
        */
        if ($errorMessage === '') {
            sqlsrv_begin_transaction($conn);

            $updateSql = "
                UPDATE dbo.Products
                SET
                    CategoryId = ?,
                    Name = ?,
                    Slug = ?,
                    ShortDescription = ?,
                    Description = ?,
                    CareInstructions = ?,
                    Specifications = ?,
                    BasePrice = ?,
                    GstPercentage = ?,
                    PstPercentage = ?,
                    MetaTitle = ?,
                    MetaDescription = ?,
                    IsFeatured = ?,
                    IsNewArrival = ?,
                    IsBestSeller = ?,
                    IsActive = ?,
                    UpdatedAt = GETDATE()
                WHERE ProductId = ?
            ";

            $updateParams = [
                $categoryId,
                $name,
                $slug,
                $shortDescription !== '' ? $shortDescription : null,
                $description !== '' ? $description : null,
                $careInstructions !== '' ? $careInstructions : null,
                $specifications !== '' ? $specifications : null,
                (float)$basePrice,
                (float)$gstPercentage,
                (float)$pstPercentage,
                $metaTitle !== '' ? $metaTitle : null,
                $metaDescription !== '' ? $metaDescription : null,
                $isFeatured ? 1 : 0,
                $isNewArrival ? 1 : 0,
                $isBestSeller ? 1 : 0,
                $isActive ? 1 : 0,
                $productId
            ];

            $updateStmt = sqlsrv_query($conn, $updateSql, $updateParams);

            if ($updateStmt === false) {
                sqlsrv_rollback($conn);
                $errorMessage = 'Unable to update product details. Database query error.';
            } else {
                sqlsrv_free_stmt($updateStmt);

                // 1. DELETE SELECTED IMAGES
                if (!empty($deleteImages) && is_array($deleteImages)) {
                    foreach ($deleteImages as $delImgId) {
                        $delImgId = (int)$delImgId;
                        $findSql = "SELECT ImageUrl FROM dbo.ProductImages WHERE ImageId = ? AND ProductId = ?";
                        $findStmt = sqlsrv_query($conn, $findSql, [$delImgId, $productId]);
                        if ($findStmt !== false) {
                            if ($fRow = sqlsrv_fetch_array($findStmt, SQLSRV_FETCH_ASSOC)) {
                                deleteProductFile($fRow['ImageUrl']);
                            }
                            sqlsrv_free_stmt($findStmt);
                        }
                        $delStmt = sqlsrv_query($conn, "DELETE FROM dbo.ProductImages WHERE ImageId = ? AND ProductId = ?", [$delImgId, $productId]);
                        if ($delStmt !== false) sqlsrv_free_stmt($delStmt);
                    }
                }

                // 2. REPLACE INDIVIDUAL EXISTING IMAGES (Per Image Replace File)
                if (isset($_FILES['replace_image']) && is_array($_FILES['replace_image']['name'])) {
                    foreach ($_FILES['replace_image']['name'] as $imgIdKey => $imgFileName) {
                        if ($_FILES['replace_image']['error'][$imgIdKey] === UPLOAD_ERR_OK) {
                            $imgIdKey = (int)$imgIdKey;
                            $tmp = $_FILES['replace_image']['tmp_name'][$imgIdKey];
                            
                            $finfo = new finfo(FILEINFO_MIME_TYPE);
                            $mime = $finfo->file($tmp);

                            if (isset($allowedMime[$mime])) {
                                $ext = $allowedMime[$mime];
                                $newFile = 'prod_' . date('Ymd_His') . '_rep_' . $imgIdKey . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                                $dest = uploadDirectory() . DIRECTORY_SEPARATOR . $newFile;

                                if (move_uploaded_file($tmp, $dest)) {
                                    // Purani image ko delete karo
                                    $oldSql = "SELECT ImageUrl FROM dbo.ProductImages WHERE ImageId = ? AND ProductId = ?";
                                    $oldStmt = sqlsrv_query($conn, $oldSql, [$imgIdKey, $productId]);
                                    if ($oldStmt !== false) {
                                        if ($oldR = sqlsrv_fetch_array($oldStmt, SQLSRV_FETCH_ASSOC)) {
                                            deleteProductFile($oldR['ImageUrl']);
                                        }
                                        sqlsrv_free_stmt($oldStmt);
                                    }

                                    // Database update path
                                    $newDbPath = 'uploads/products/' . $newFile;
                                    $repSql = "UPDATE dbo.ProductImages SET ImageUrl = ? WHERE ImageId = ? AND ProductId = ?";
                                    $repStmt = sqlsrv_query($conn, $repSql, [$newDbPath, $imgIdKey, $productId]);
                                    if ($repStmt !== false) sqlsrv_free_stmt($repStmt);
                                }
                            }
                        }
                    }
                }

                // 3. UPLOAD NEW ADDITIONAL IMAGES
                if (isset($_FILES['new_product_images'])) {
                    $newFiles = $_FILES['new_product_images'];
                    $count = count($newFiles['name']);

                    for ($i = 0; $i < $count; $i++) {
                        if ($newFiles['error'][$i] === UPLOAD_ERR_OK) {
                            $tmp = $newFiles['tmp_name'][$i];
                            $finfo = new finfo(FILEINFO_MIME_TYPE);
                            $mime = $finfo->file($tmp);

                            if (isset($allowedMime[$mime])) {
                                $ext = $allowedMime[$mime];
                                $newFileName = 'prod_' . date('Ymd_His') . '_new_' . $i . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                                $destPath = uploadDirectory() . DIRECTORY_SEPARATOR . $newFileName;

                                if (move_uploaded_file($tmp, $destPath)) {
                                    $dbPath = 'uploads/products/' . $newFileName;
                                    $insSql = "INSERT INTO dbo.ProductImages (ProductId, VariantId, ImageUrl, AltText, IsMain, DisplayOrder) VALUES (?, NULL, ?, ?, 0, 1)";
                                    $insStmt = sqlsrv_query($conn, $insSql, [$productId, $dbPath, $name]);
                                    if ($insStmt !== false) sqlsrv_free_stmt($insStmt);
                                }
                            }
                        }
                    }
                }

                // 4. MAIN IMAGE SELECTION
                if ($mainImageId > 0) {
                    sqlsrv_query($conn, "UPDATE dbo.ProductImages SET IsMain = 0 WHERE ProductId = ?", [$productId]);
                    sqlsrv_query($conn, "UPDATE dbo.ProductImages SET IsMain = 1 WHERE ImageId = ? AND ProductId = ?", [$mainImageId, $productId]);
                } else {
                    $checkMain = sqlsrv_query($conn, "SELECT COUNT(*) as c FROM dbo.ProductImages WHERE ProductId = ? AND IsMain = 1", [$productId]);
                    $mainCount = 0;
                    if ($checkMain !== false) {
                        $mRow = sqlsrv_fetch_array($checkMain, SQLSRV_FETCH_ASSOC);
                        $mainCount = (int)($mRow['c'] ?? 0);
                        sqlsrv_free_stmt($checkMain);
                    }
                    if ($mainCount === 0) {
                        sqlsrv_query($conn, "UPDATE TOP (1) dbo.ProductImages SET IsMain = 1 WHERE ProductId = ?", [$productId]);
                    }
                }

                sqlsrv_commit($conn);
                header('Location: index.php?success=' . urlencode('Product "' . $name . '" updated successfully.'));
                exit;
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<style>
:root {
    --bg-page: #0a1119;
    --bg-card: #111b26;
    --bg-card-alt: #0f1823;
    --bg-input: #0d1620;
    --border: #1e2d3d;
    --border-soft: #182636;

    --text-hi: #f0f4f8;
    --text-body: #a8b8c8;
    --text-mute: #6b7f91;

    --green: #10b981;
    --green-dark: #059669;
    --green-soft: rgba(16,185,129,.12);

    --red: #ef4444;
    --red-soft: rgba(239,68,68,.12);

    --blue: #38bdf8;
    --blue-soft: rgba(56,189,248,.12);

    --radius: 12px;
}

* { box-sizing: border-box; }

html, body, .main, .content {
    background: var(--bg-page) !important;
    color: var(--text-body) !important;
}

.category-edit-page {
    width: 100%;
    max-width: 1350px;
    margin: 0 auto;
    padding: 0 0 35px;
}

.page-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    padding-bottom: 18px;
    margin-bottom: 20px;
    border-bottom: 1px solid var(--border);
}

.breadcrumb {
    display: flex;
    gap: 8px;
    margin-bottom: 8px;
    color: var(--text-mute);
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .4px;
}

.breadcrumb .current { color: var(--green); }

.page-header h1 {
    margin: 0;
    color: var(--text-hi);
    font-size: 26px;
    font-weight: 800;
}

.page-header p {
    margin: 6px 0 0;
    color: var(--text-mute);
    font-size: 12px;
}

.header-actions { display: flex; gap: 8px; flex-wrap: wrap; }

.btn {
    min-height: 39px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 14px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--bg-input);
    color: var(--text-body) !important;
    font-size: 11px;
    font-weight: 800;
    text-decoration: none;
    cursor: pointer;
    transition: .18s;
}

.btn:hover {
    border-color: var(--green);
    background: var(--green-soft);
    color: var(--green) !important;
}

.btn-primary {
    border-color: transparent;
    background: linear-gradient(135deg, var(--green-dark), var(--green));
    color: #fff !important;
    box-shadow: 0 7px 20px rgba(16,185,129,.18);
}

.btn-primary:hover {
    color: #fff !important;
    transform: translateY(-1px);
}

.notice {
    margin-bottom: 15px;
    padding: 12px 14px;
    border-radius: 9px;
    font-size: 12px;
    font-weight: 700;
}

.notice-error {
    border: 1px solid rgba(239,68,68,.3);
    background: var(--red-soft);
    color: #fca5a5;
}

.edit-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 460px;
    gap: 16px;
}

.card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    margin-bottom: 16px;
}

.card-header {
    padding: 16px 18px;
    border-bottom: 1px solid var(--border);
}

.card-header h2 {
    margin: 0;
    color: var(--text-hi);
    font-size: 15px;
    font-weight: 800;
}

.card-header p {
    margin: 5px 0 0;
    color: var(--text-mute);
    font-size: 11px;
}

.card-body { padding: 20px; }
.form-group { margin-bottom: 17px; }

.form-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 7px;
    color: var(--text-body);
    font-size: 11px;
    font-weight: 800;
}

.required { color: #f87171; }

.input, .textarea, .select {
    width: 100%;
    border: 1px solid var(--border);
    border-radius: 8px;
    outline: none;
    background: var(--bg-input);
    color: var(--text-hi);
    font-family: inherit;
    font-size: 12px;
    transition: .18s;
}

.input, .select { height: 42px; padding: 0 12px; }
.textarea { min-height: 100px; padding: 12px; resize: vertical; line-height: 1.5; }

.input:focus, .textarea:focus, .select:focus {
    border-color: var(--green);
    box-shadow: 0 0 0 3px rgba(16,185,129,.1);
}

.input-prefix { position: relative; }
.input-prefix span {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-mute);
    font-size: 12px;
    pointer-events: none;
}
.input-prefix .input { padding-left: 25px; }

.help-text { margin-top: 6px; color: var(--text-mute); font-size: 10px; }

.two-column {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.flags-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.status-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 12px;
    border: 1px solid var(--border);
    border-radius: 9px;
    background: var(--bg-input);
}

.status-info strong {
    display: block;
    color: var(--text-hi);
    font-size: 11.5px;
}

.switch {
    position: relative;
    width: 40px;
    height: 22px;
}
.switch input { opacity: 0; width: 0; height: 0; }
.slider {
    position: absolute; inset: 0; cursor: pointer;
    border-radius: 30px; background: #263544; transition: .2s;
}
.slider:before {
    content: ""; position: absolute; width: 16px; height: 16px;
    left: 3px; top: 3px; border-radius: 50%; background: #fff; transition: .2s;
}
.switch input:checked + .slider { background: var(--green); }
.switch input:checked + .slider:before { transform: translateX(18px); }

/* =========================================================
   ALL IMAGES SHOW & EDIT CARD STYLES
========================================================= */
.gallery-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 18px;
}

.gallery-card-row {
    display: grid;
    grid-template-columns: 85px 1fr;
    gap: 12px;
    padding: 12px;
    background: var(--bg-input);
    border: 1px solid var(--border-soft);
    border-radius: 10px;
    align-items: center;
    position: relative;
}

.gallery-card-row.is-main-active {
    border-color: rgba(16, 185, 129, 0.4);
    background: rgba(16, 185, 129, 0.03);
}

.gallery-card-img-wrap {
    position: relative;
    width: 85px;
    height: 85px;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid var(--border);
    background: #000;
}

.gallery-card-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.main-badge {
    position: absolute;
    top: 4px;
    left: 4px;
    background: var(--green);
    color: #fff;
    font-size: 8px;
    font-weight: 900;
    padding: 2px 5px;
    border-radius: 4px;
    letter-spacing: .4px;
}

.gallery-card-controls {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.gallery-card-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 11px;
}

.main-radio-label {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
    font-weight: 700;
    color: var(--text-hi);
}

.main-radio-label input { accent-color: var(--green); cursor: pointer; }

.del-checkbox-label {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
    font-weight: 700;
    color: #f87171;
}

.del-checkbox-label input { accent-color: var(--red); cursor: pointer; }

.replace-file-label {
    display: block;
    font-size: 10px;
    color: var(--text-mute);
    font-weight: 600;
    margin-bottom: 3px;
}

.replace-file-input {
    width: 100%;
    font-size: 10px;
    color: var(--text-mute);
    background: var(--bg-card);
    border: 1px dashed var(--border);
    border-radius: 6px;
    padding: 4px 6px;
    cursor: pointer;
}

.replace-file-input:hover { border-color: var(--blue); }

.file-input-multiple {
    width: 100%;
    padding: 12px;
    border: 1px dashed var(--border);
    border-radius: 8px;
    background: var(--bg-input);
    color: var(--text-body);
    font-size: 11px;
    cursor: pointer;
    text-align: center;
}
.file-input-multiple:hover { border-color: var(--green); }

.action-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding: 15px 20px;
    border-top: 1px solid var(--border);
}

.shortcut-box {
    margin-top: 16px;
    padding: 15px 18px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: var(--bg-card);
}

.shortcut-title {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text-hi);
    font-size: 12px;
    font-weight: 800;
}

.shortcut-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
    margin-top: 12px;
}

.shortcut {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    border: 1px solid var(--border-soft);
    border-radius: 7px;
    background: var(--bg-input);
}

.key {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 38px;
    height: 24px;
    padding: 0 6px;
    border: 1px solid var(--border);
    border-radius: 5px;
    background: #080e15;
    color: var(--green);
    font-family: monospace;
    font-size: 9px;
    font-weight: 800;
}

.key-text { color: var(--text-body); font-size: 10px; font-weight: 600; }

@media (max-width: 990px) {
    .edit-layout { grid-template-columns: 1fr; }
}

@media (max-width: 650px) {
    .two-column, .flags-grid { grid-template-columns: 1fr; }
    .page-header { flex-direction: column; align-items: flex-start; }
    .header-actions { width: 100%; }
    .header-actions .btn { flex: 1; }
    .action-footer { flex-direction: column-reverse; }
    .action-footer .btn { width: 100%; }
}
</style>

<main class="main">
<section class="content">
<div class="category-edit-page">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div>
            <div class="breadcrumb">
                <span>Products</span>
                <span>/</span>
                <span class="current">Edit Product</span>
            </div>
            <h1>Edit Product</h1>
            <p>Modify product info, specifications, pricing, taxes and manage all gallery photos.</p>
        </div>

        <div class="header-actions">
            <a href="index.php" class="btn" title="Cancel (Esc)">← Back</a>
            <button type="submit" form="productEditForm" class="btn btn-primary" title="Save (Ctrl + S)">
                ✓ Save Changes
            </button>
        </div>
    </div>

    <!-- NOTICES -->
    <?php if ($errorMessage !== ''): ?>
        <div class="notice notice-error"><?= e($errorMessage) ?></div>
    <?php endif; ?>

    <!-- EDIT FORM -->
    <form method="post" enctype="multipart/form-data" id="productEditForm" autocomplete="off">
        <input type="hidden" name="action" value="update_product">
        <input type="hidden" name="product_id" value="<?= $productId ?>">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

        <div class="edit-layout">

            <!-- LEFT COLUMN: PRODUCT DETAILS -->
            <div>
                <!-- GENERAL DETAILS -->
                <div class="card">
                    <div class="card-header">
                        <h2>General Information</h2>
                        <p>Basic identifiers and category mapping.</p>
                    </div>

                    <div class="card-body">
                        <!-- CATEGORY -->
                        <div class="form-group">
                            <label class="form-label">
                                <span>Category <span class="required">*</span></span>
                            </label>
                            <select name="category_id" class="select" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categoriesList as $cat): ?>
                                    <option value="<?= (int)$cat['CategoryId'] ?>" <?= $categoryId === (int)$cat['CategoryId'] ? 'selected' : '' ?>>
                                        <?= e($cat['Name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- PRODUCT NAME -->
                        <div class="form-group">
                            <label class="form-label">
                                <span>Product Name <span class="required">*</span></span>
                                <span id="nameCounter"><?= strlen($name) ?>/255</span>
                            </label>
                            <input type="text" name="name" id="productName" class="input" value="<?= e($name) ?>" maxlength="255" required placeholder="e.g. Premium Bath Towel">
                        </div>

                        <!-- SLUG -->
                        <div class="form-group">
                            <label class="form-label">
                                <span>Slug <span class="required">*</span></span>
                            </label>
                            <div class="input-prefix">
                                <span>/</span>
                                <input type="text" name="slug" id="productSlug" class="input" value="<?= e($slug) ?>" maxlength="255" required placeholder="premium-bath-towel">
                            </div>
                            <div class="help-text">Slug is auto-synced with product name unless modified manually.</div>
                        </div>

                        <!-- SHORT DESCRIPTION -->
                        <div class="form-group">
                            <label class="form-label"><span>Short Summary</span></label>
                            <input type="text" name="short_description" class="input" value="<?= e($shortDescription) ?>" placeholder="1-line summary of the item">
                        </div>

                        <!-- FULL DESCRIPTION -->
                        <div class="form-group">
                            <label class="form-label"><span>Full Description</span></label>
                            <textarea name="description" class="textarea" placeholder="Detailed product description..."><?= e($description) ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- SPECIFICATIONS & CARE -->
                <div class="card">
                    <div class="card-header">
                        <h2>Specifications & Care Instructions</h2>
                        <p>Attributes, GSM, fabric, and cleaning guidelines.</p>
                    </div>

                    <div class="card-body">
                        <div class="two-column">
                            <div class="form-group">
                                <label class="form-label"><span>Specifications</span></label>
                                <textarea name="specifications" class="textarea" placeholder="e.g. 100% Cotton, 600 GSM, 70x140 cm"><?= e($specifications) ?></textarea>
                            </div>

                            <div class="form-group">
                                <label class="form-label"><span>Care Instructions</span></label>
                                <textarea name="care_instructions" class="textarea" placeholder="e.g. Machine wash cold with similar colors."><?= e($careInstructions) ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SEO METADATA -->
                <div class="card">
                    <div class="card-header">
                        <h2>Search Engine Optimization (SEO)</h2>
                        <p>Custom meta tags for ranking and social shares.</p>
                    </div>

                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label"><span>Meta Title</span></label>
                            <input type="text" name="meta_title" class="input" value="<?= e($metaTitle) ?>" placeholder="SEO Title">
                        </div>

                        <div class="form-group">
                            <label class="form-label"><span>Meta Description</span></label>
                            <textarea name="meta_description" class="textarea" style="min-height:75px;" placeholder="Search engine snippet description..."><?= e($metaDescription) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: IMAGES & PRICING -->
            <div>
                <!-- PRICING & TAXATION -->
                <div class="card">
                    <div class="card-header">
                        <h2>Pricing & Taxes</h2>
                        <p>Base price and regional tax percentages.</p>
                    </div>

                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label"><span>Base Price ($) <span class="required">*</span></span></label>
                            <input type="number" step="0.01" min="0" name="base_price" class="input" value="<?= e($basePrice) ?>" required>
                        </div>

                        <div class="two-column">
                            <div class="form-group">
                                <label class="form-label"><span>GST Rate (%)</span></label>
                                <input type="number" step="0.01" min="0" name="gst_percentage" class="input" value="<?= e($gstPercentage) ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><span>PST Rate (%)</span></label>
                                <input type="number" step="0.01" min="0" name="pst_percentage" class="input" value="<?= e($pstPercentage) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VISIBILITY & FLAGS -->
                <div class="card">
                    <div class="card-header">
                        <h2>Visibility & Flags</h2>
                        <p>Status toggle and showcase badges.</p>
                    </div>

                    <div class="card-body">
                        <div class="flags-grid">
                            <div class="status-box">
                                <div class="status-info"><strong>Active</strong></div>
                                <label class="switch">
                                    <input type="checkbox" name="is_active" value="1" <?= $isActive ? 'checked' : '' ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="status-box">
                                <div class="status-info"><strong>Featured</strong></div>
                                <label class="switch">
                                    <input type="checkbox" name="is_featured" value="1" <?= $isFeatured ? 'checked' : '' ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="status-box">
                                <div class="status-info"><strong>New Arrival</strong></div>
                                <label class="switch">
                                    <input type="checkbox" name="is_new_arrival" value="1" <?= $isNewArrival ? 'checked' : '' ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="status-box">
                                <div class="status-info"><strong>Best Seller</strong></div>
                                <label class="switch">
                                    <input type="checkbox" name="is_best_seller" value="1" <?= $isBestSeller ? 'checked' : '' ?>>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PRODUCT GALLERY & ALL IMAGES (SHOW / EDIT / REPLACE) -->
                <div class="card">
                    <div class="card-header">
                        <h2>Product Images (All Photos)</h2>
                        <p>Jitni bhi images hain yahan show hongi. Aap replace, delete ya Main bana sakte hain.</p>
                    </div>

                    <div class="card-body">
                        <?php if (!empty($existingImages)): ?>
                            <div class="gallery-list">
                                <?php foreach ($existingImages as $gImg): ?>
                                    <?php 
                                    $imgId = (int)$gImg['ImageId'];
                                    $isMain = !empty($gImg['IsMain']);
                                    ?>
                                    <div class="gallery-card-row <?= $isMain ? 'is-main-active' : '' ?>">
                                        <!-- Image Thumbnail -->
                                        <div class="gallery-card-img-wrap">
                                            <?php if ($isMain): ?>
                                                <span class="main-badge">MAIN</span>
                                            <?php endif; ?>
                                            <img src="../<?= e($gImg['ImageUrl']) ?>" alt="Product Image" onerror="this.src=''; this.alt='No Image';">
                                        </div>

                                        <!-- Edit Controls for this Image -->
                                        <div class="gallery-card-controls">
                                            <div class="gallery-card-actions">
                                                <!-- Radio to set Main Photo -->
                                                <label class="main-radio-label" title="Set this photo as Main Photo">
                                                    <input type="radio" name="main_image_id" value="<?= $imgId ?>" <?= $isMain ? 'checked' : '' ?>>
                                                    <span>Set as Main</span>
                                                </label>

                                                <!-- Checkbox to Delete Photo -->
                                                <label class="del-checkbox-label" title="Delete this image">
                                                    <input type="checkbox" name="delete_images[]" value="<?= $imgId ?>">
                                                    <span>Delete</span>
                                                </label>
                                            </div>

                                            <!-- Replace file input for this specific photo -->
                                            <div>
                                                <span class="replace-file-label">Replace this photo:</span>
                                                <input type="file" name="replace_image[<?= $imgId ?>]" class="replace-file-input" accept=".jpg,.jpeg,.png,.webp,.gif">
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="font-size:11px; color:var(--text-mute); margin-top:0;">Is product ke liye koi photo upload nahi hai.</p>
                        <?php endif; ?>

                        <!-- UPLOAD NEW MORE PHOTOS -->
                        <div class="form-group" style="margin-top:14px; margin-bottom:0;">
                            <label class="form-label"><span>Add More Images (Multiple)</span></label>
                            <input type="file" name="new_product_images[]" class="file-input-multiple" multiple accept=".jpg,.jpeg,.png,.webp,.gif">
                            <div class="help-text">JPG, PNG, WEBP, GIF. Max 5MB each.</div>
                        </div>
                    </div>

                    <div class="action-footer">
                        <a href="index.php" class="btn">Cancel</a>
                        <button type="submit" class="btn btn-primary">✓ Update Product</button>
                    </div>
                </div>

            </div>

        </div>
    </form>

    <!-- KEYBOARD SHORTCUTS -->
    <div class="shortcut-box">
        <div class="shortcut-title">⌨ Keyboard Shortcuts</div>
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
document.addEventListener('DOMContentLoaded', function(){
    const form        = document.getElementById('productEditForm');
    const nameInput   = document.getElementById('productName');
    const slugInput   = document.getElementById('productSlug');
    const nameCounter = document.getElementById('nameCounter');

    function makeSlug(value){
        return value.toLowerCase().trim()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    let slugManuallyEdited = slugInput.value.trim() !== makeSlug(nameInput.value);

    nameInput.addEventListener('input', function(){
        nameCounter.textContent = this.value.length + '/255';
        if (!slugManuallyEdited) {
            slugInput.value = makeSlug(this.value);
        }
    });

    slugInput.addEventListener('input', function(){
        slugManuallyEdited = this.value !== makeSlug(nameInput.value);
    });

    // Keyboard Shortcuts
    document.addEventListener('keydown', function(e){
        if (e.ctrlKey && e.key.toLowerCase() === 's') {
            e.preventDefault();
            form.requestSubmit();
            return;
        }

        if (e.key === 'Escape') {
            const tag = e.target?.tagName?.toLowerCase();
            if (['input', 'textarea', 'select'].includes(tag) && e.target.value) {
                e.target.blur();
                return;
            }
            window.location.href = 'index.php';
        }
    });

    // Prevent Double Submission
    let submitting = false;
    form.addEventListener('submit', function(){
        if (submitting) return;
        submitting = true;
        const buttons = form.querySelectorAll('button[type="submit"]');
        buttons.forEach(button => {
            button.disabled = true;
            button.innerHTML = '⏳ Saving...';
        });
    });
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>