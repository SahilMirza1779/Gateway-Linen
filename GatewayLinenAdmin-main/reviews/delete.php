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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_id'])) {
    $reviewId = (int)$_POST['review_id'];

    if ($reviewId > 0) {
        $delSql = "DELETE FROM dbo.ProductReviews WHERE ReviewId = ?";
        $delStmt = sqlsrv_query($conn, $delSql, [$reviewId]);

        if ($delStmt !== false) {
            header('Location: index.php?success=' . urlencode("Review deleted successfully."));
            exit;
        } else {
            header('Location: index.php?error=' . urlencode("Failed to delete review."));
            exit;
        }
    }
}

header('Location: index.php');
exit;