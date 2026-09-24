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
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| VALIDATE REQUEST & CSRF TOKEN
|--------------------------------------------------------------------------
*/
$sessionToken = $_SESSION['order_csrf_token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = $_POST['csrf_token'] ?? '';

    if (empty($sessionToken) || empty($postToken) || !hash_equals($sessionToken, $postToken)) {
        header('Location: index.php?error=' . urlencode('Security verification failed. Please try again.'));
        exit;
    }

    $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
} else {
    // GET Request support
    $orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
}

if ($orderId <= 0) {
    header('Location: index.php?error=' . urlencode('Invalid order ID provided.'));
    exit;
}

/*
|--------------------------------------------------------------------------
| CHECK IF ORDER EXISTS
|--------------------------------------------------------------------------
*/
$checkSql = "SELECT OrderId, OrderNumber FROM dbo.Orders WHERE OrderId = ?";
$checkStmt = sqlsrv_query($conn, $checkSql, [$orderId]);

if ($checkStmt === false) {
    $errors = sqlsrv_errors();
    $dbMsg = $errors[0]['message'] ?? 'Database error while checking order.';
    header('Location: index.php?error=' . urlencode($dbMsg));
    exit;
}

$order = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($checkStmt);

if (!$order) {
    header('Location: index.php?error=' . urlencode('Order not found or has already been removed.'));
    exit;
}

$orderNum = !empty($order['OrderNumber']) ? $order['OrderNumber'] : ('#' . $orderId);

/*
|--------------------------------------------------------------------------
| BEGIN TRANSACTION TO SAFELY REMOVE ALL DEPENDENCIES
|--------------------------------------------------------------------------
*/
if (!sqlsrv_begin_transaction($conn)) {
    header('Location: index.php?error=' . urlencode('Unable to initiate database transaction.'));
    exit;
}

// 1. Delete Order Status History
$delHist = sqlsrv_query($conn, "DELETE FROM dbo.OrderStatusHistory WHERE OrderId = ?", [$orderId]);
if ($delHist === false) {
    sqlsrv_rollback($conn);
    header('Location: index.php?error=' . urlencode('Failed to delete order history logs.'));
    exit;
}
sqlsrv_free_stmt($delHist);

// 2. Delete Order Shipments
$delShip = sqlsrv_query($conn, "DELETE FROM dbo.OrderShipments WHERE OrderId = ?", [$orderId]);
if ($delShip === false) {
    sqlsrv_rollback($conn);
    header('Location: index.php?error=' . urlencode('Failed to delete order shipment details.'));
    exit;
}
sqlsrv_free_stmt($delShip);

// 3. Delete Order Refunds
$delRefunds = sqlsrv_query($conn, "DELETE FROM dbo.OrderRefunds WHERE OrderId = ?", [$orderId]);
if ($delRefunds === false) {
    sqlsrv_rollback($conn);
    header('Location: index.php?error=' . urlencode('Failed to delete order refunds data.'));
    exit;
}
sqlsrv_free_stmt($delRefunds);

// 4. Delete Payments associated with this order
$delPayments = sqlsrv_query($conn, "DELETE FROM dbo.Payment WHERE OrderId = ?", [$orderId]);
if ($delPayments === false) {
    sqlsrv_rollback($conn);
    header('Location: index.php?error=' . urlencode('Failed to delete payment records for this order.'));
    exit;
}
if (is_resource($delPayments)) sqlsrv_free_stmt($delPayments);

// 5. Delete Order Items
$delItems = sqlsrv_query($conn, "DELETE FROM dbo.OrderItems WHERE OrderId = ?", [$orderId]);
if ($delItems === false) {
    sqlsrv_rollback($conn);
    header('Location: index.php?error=' . urlencode('Failed to delete order items.'));
    exit;
}
sqlsrv_free_stmt($delItems);

// 6. Delete Main Order
$delOrder = sqlsrv_query($conn, "DELETE FROM dbo.Orders WHERE OrderId = ?", [$orderId]);
if ($delOrder === false) {
    sqlsrv_rollback($conn);
    $errors = sqlsrv_errors();
    $sqlMsg = $errors[0]['message'] ?? 'Could not delete the order record.';
    header('Location: index.php?error=' . urlencode($sqlMsg));
    exit;
}
sqlsrv_free_stmt($delOrder);

/*
|--------------------------------------------------------------------------
| COMMIT TRANSACTION
|--------------------------------------------------------------------------
*/
if (!sqlsrv_commit($conn)) {
    sqlsrv_rollback($conn);
    header('Location: index.php?error=' . urlencode('Transaction commit failed.'));
    exit;
}

// Regenerate CSRF Token
$_SESSION['order_csrf_token'] = bin2hex(random_bytes(32));

// Redirect back to orders listing
header('Location: index.php?success=' . urlencode('Order ' . $orderNum . ' and all related records were successfully deleted.'));
exit;