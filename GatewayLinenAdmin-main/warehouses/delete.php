<?php
session_start();

/*
|--------------------------------------------------------------------------
| GatewayLinen Admin - Delete Warehouse
|--------------------------------------------------------------------------
*/

if (empty($_SESSION["admin_id"])) {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $warehouseId = (int)($_POST["warehouse_id"] ?? 0);

    if ($warehouseId > 0) {
        $deleteSql = "DELETE FROM dbo.Warehouses WHERE WarehouseId = ?";
        $stmt = sqlsrv_query($conn, $deleteSql, [$warehouseId]);

        if ($stmt !== false) {
            sqlsrv_free_stmt($stmt);
            header("Location: index.php?success=" . urlencode("Warehouse deleted successfully."));
            exit;
        } else {
            header("Location: index.php?error=" . urlencode("Failed to delete warehouse. It may be in use."));
            exit;
        }
    }
}

header("Location: index.php");
exit;