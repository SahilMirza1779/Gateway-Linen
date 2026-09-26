<?php
session_start();

if (empty($_SESSION["admin_id"])) {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["variant_id"])) {
    $variantId = (int)$_POST["variant_id"];

    $delSql = "DELETE FROM dbo.ProductVariants WHERE VariantId = ?";
    $delStmt = sqlsrv_query($conn, $delSql, [$variantId]);

    if ($delStmt !== false) {
        sqlsrv_free_stmt($delStmt);
        header("Location: index.php?msg=deleted");
        exit;
    } else {
        header("Location: index.php?msg=err_used");
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}
