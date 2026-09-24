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
| REQUEST METHOD & CSRF CHECK
|--------------------------------------------------------------------------
*/

$sessionToken = $_SESSION['product_delete_token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';

    if (empty($sessionToken) || empty($postToken) || !hash_equals($sessionToken, $postToken)) {
        header('Location: index.php?error=' . urlencode('Invalid security verification token. Please try again.'));
        exit;
    }

    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
} else {
    // GET Request support
    $productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
}

if ($productId <= 0) {
    header('Location: index.php?error=' . urlencode('Invalid product selected.'));
    exit;
}

/*
|--------------------------------------------------------------------------
| 1. CHECK PRODUCT DETAILS (SIMPLIFIED & SAFE)
|--------------------------------------------------------------------------
*/

$checkSql = "SELECT ProductId, Name FROM dbo.Products WHERE ProductId = ?";
$checkStmt = sqlsrv_query($conn, $checkSql, [$productId]);

if ($checkStmt === false) {
    $errors = sqlsrv_errors();
    $dbMsg = $errors[0]['message'] ?? 'Database error while checking product.';
    header('Location: index.php?error=' . urlencode($dbMsg));
    exit;
}

$product = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($checkStmt);

if (!$product) {
    header('Location: index.php?error=' . urlencode('Product not found or already deleted.'));
    exit;
}

/*
|--------------------------------------------------------------------------
| 2. FETCH PRODUCT IMAGES (FOR FILE REMOVAL)
|--------------------------------------------------------------------------
*/

$imagesSql = "SELECT ImageUrl FROM dbo.ProductImages WHERE ProductId = ?";
$imagesStmt = sqlsrv_query($conn, $imagesSql, [$productId]);
$imagesToDelete = [];

if ($imagesStmt !== false) {
    while ($imgRow = sqlsrv_fetch_array($imagesStmt, SQLSRV_FETCH_ASSOC)) {
        if (!empty($imgRow['ImageUrl'])) {
            $imagesToDelete[] = $imgRow['ImageUrl'];
        }
    }
    sqlsrv_free_stmt($imagesStmt);
}

/*
|--------------------------------------------------------------------------
| 3. START TRANSACTION
|--------------------------------------------------------------------------
*/

if (!sqlsrv_begin_transaction($conn)) {
    header('Location: index.php?error=' . urlencode('Unable to start delete transaction.'));
    exit;
}

/*
|--------------------------------------------------------------------------
| 4. CLEANUP CHILD / LINKED DATA (FK SAFE DELETION)
|--------------------------------------------------------------------------
*/

// Delete images records
$delImgStmt = sqlsrv_query($conn, "DELETE FROM dbo.ProductImages WHERE ProductId = ?", [$productId]);
if ($delImgStmt === false) {
    sqlsrv_rollback($conn);
    header('Location: index.php?error=' . urlencode('Could not remove associated product images.'));
    exit;
}
sqlsrv_free_stmt($delImgStmt);

// Optional: clean reviews, cart, wishlist jodi thake
@sqlsrv_query($conn, "DELETE FROM dbo.ProductReviews WHERE ProductId = ?", [$productId]);
@sqlsrv_query($conn, "DELETE FROM dbo.WishlistItems WHERE ProductId = ?", [$productId]);
@sqlsrv_query($conn, "DELETE FROM dbo.CartItems WHERE ProductId = ?", [$productId]);

/*
|--------------------------------------------------------------------------
| 5. DELETE PRODUCT
|--------------------------------------------------------------------------
*/

$deleteSql = "DELETE FROM dbo.Products WHERE ProductId = ?";
$deleteStmt = sqlsrv_query($conn, $deleteSql, [$productId]);

if ($deleteStmt === false) {
    sqlsrv_rollback($conn);
    $errors = sqlsrv_errors();
    $sqlMsg = $errors[0]['message'] ?? 'Product could not be deleted because it is referenced by another record.';
    header('Location: index.php?error=' . urlencode($sqlMsg));
    exit;
}
sqlsrv_free_stmt($deleteStmt);

/*
|--------------------------------------------------------------------------
| 6. COMMIT TRANSACTION
|--------------------------------------------------------------------------
*/

if (!sqlsrv_commit($conn)) {
    sqlsrv_rollback($conn);
    header('Location: index.php?error=' . urlencode('Transaction commit failed.'));
    exit;
}

/*
|--------------------------------------------------------------------------
| 7. DELETE PHYSICAL FILES FROM DISK
|--------------------------------------------------------------------------
*/

$projectRoot = dirname(__DIR__);

foreach ($imagesToDelete as $imagePath) {
    $cleanPath = ltrim(str_replace('\\', '/', $imagePath), '/');
    $physicalFilePath = $projectRoot . '/' . $cleanPath;

    if (file_exists($physicalFilePath) && is_file($physicalFilePath)) {
        @unlink($physicalFilePath);
    }
}

/*
|--------------------------------------------------------------------------
| 8. REDIRECT WITH SUCCESS
|--------------------------------------------------------------------------
*/

$_SESSION['product_delete_token'] = bin2hex(random_bytes(32));

$deletedName = (string)($product['Name'] ?? 'Product');
$message = 'Product "' . $deletedName . '" and its images were permanently deleted.';

header('Location: index.php?success=' . urlencode($message));
exit;