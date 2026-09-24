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

$sessionToken = $_SESSION['coupon_delete_token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';

    if (empty($sessionToken) || empty($postToken) || !hash_equals($sessionToken, $postToken)) {
        header('Location: index.php?error=' . urlencode('Invalid security verification token. Please try again.'));
        exit;
    }

    $couponId = isset($_POST['coupon_id']) ? (int)$_POST['coupon_id'] : 0;
} else {
    // GET Request support agar direct link se aaye
    $couponId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
}

if ($couponId <= 0) {
    header('Location: index.php?error=' . urlencode('Invalid coupon selected.'));
    exit;
}

/*
|--------------------------------------------------------------------------
| 1. VERIFY COUPON DETAILS
|--------------------------------------------------------------------------
*/

$checkSql = "SELECT CouponId, CouponCode, TimesUsed FROM dbo.Coupons WHERE CouponId = ?";
$checkStmt = sqlsrv_query($conn, $checkSql, [$couponId]);

if ($checkStmt === false) {
    header('Location: index.php?error=' . urlencode('Unable to verify the selected coupon.'));
    exit;
}

$coupon = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($checkStmt);

if (!$coupon) {
    header('Location: index.php?error=' . urlencode('Coupon not found or already deleted.'));
    exit;
}

/*
|--------------------------------------------------------------------------
| 2. USAGE CHECK (SAFETY PRECAUTION)
|--------------------------------------------------------------------------
*/

$timesUsed = (int)($coupon['TimesUsed'] ?? 0);

if ($timesUsed > 0) {
    $msg = 'Coupon "' . ($coupon['CouponCode'] ?? 'Code') . '" cannot be deleted because it has already been used ' . $timesUsed . ' time(s) in orders. Please set its status to Inactive instead.';
    header('Location: index.php?error=' . urlencode($msg));
    exit;
}

/*
|--------------------------------------------------------------------------
| 3. START TRANSACTION & DELETE
|--------------------------------------------------------------------------
*/

if (!sqlsrv_begin_transaction($conn)) {
    header('Location: index.php?error=' . urlencode('Unable to initiate coupon deletion transaction.'));
    exit;
}

$deleteSql = "DELETE FROM dbo.Coupons WHERE CouponId = ?";
$deleteStmt = sqlsrv_query($conn, $deleteSql, [$couponId]);

if ($deleteStmt === false) {
    sqlsrv_rollback($conn);
    $errors = sqlsrv_errors();
    $errorMsg = $errors[0]['message'] ?? 'Coupon could not be deleted because it is linked to other records.';
    header('Location: index.php?error=' . urlencode($errorMsg));
    exit;
}

sqlsrv_free_stmt($deleteStmt);

if (!sqlsrv_commit($conn)) {
    sqlsrv_rollback($conn);
    header('Location: index.php?error=' . urlencode('Transaction commit failed during coupon deletion.'));
    exit;
}

/*
|--------------------------------------------------------------------------
| 4. REFRESH TOKEN & REDIRECT
|--------------------------------------------------------------------------
*/

$_SESSION['coupon_delete_token'] = bin2hex(random_bytes(32));

$deletedCode = (string)($coupon['CouponCode'] ?? 'Coupon');
$successMessage = 'Coupon "' . $deletedCode . '" was deleted successfully.';

header('Location: index.php?success=' . urlencode($successMessage));
exit;