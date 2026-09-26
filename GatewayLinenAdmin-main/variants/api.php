<?php
// 1. Enable CORS for frontend (React/Vue/Angular) requests
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// 2. Include Database Connection
require_once __DIR__ . "/../config/database.php";

// 3. Validate HTTP Method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["status" => "error", "message" => "Method not allowed. Please use GET request."]);
    exit;
}

$action = isset($_GET['action']) ? trim($_GET['action']) : 'get_variants';

try {
    if ($action === 'get_variants') {
        $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

        // 4. Comprehensive SQL JOIN to get rich data for frontend Quote Builder
        $sql = "
            SELECT 
                v.VariantId, 
                v.ProductId, 
                v.SKU, 
                v.Barcode, 
                v.Size, 
                v.Color, 
                v.Material, 
                v.WeightGSM, 
                v.Dimensions, 
                v.Price, 
                v.CompareAtPrice, 
                v.WholesalePrice, 
                v.WeightKg,
                v.LowStockThreshold,
                p.Name AS ProductName
            FROM dbo.ProductVariants v
            INNER JOIN dbo.Products p ON v.ProductId = p.ProductId
            WHERE v.IsActive = 1
        ";

        $params = [];

        if ($productId > 0) {
            $sql .= " AND v.ProductId = ?";
            $params[] = $productId;
        }

        // Order by Product Name and Variant ID
        $sql .= " ORDER BY p.Name ASC, v.VariantId DESC";

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            throw new Exception("Database query failed: " . print_r(sqlsrv_errors(), true));
        }

        $variants = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // 5. Formatting data types properly for frontend consumption
            $variants[] = [
                'variant_id' => (int)$row['VariantId'],
                'product_id' => (int)$row['ProductId'],
                'product_name' => $row['ProductName'],
                'sku' => $row['SKU'],
                'barcode' => $row['Barcode'] ? $row['Barcode'] : null,
                'attributes' => [
                    'size' => $row['Size'],
                    'color' => $row['Color'],
                    'material' => $row['Material'],
                    'weight_gsm' => $row['WeightGSM'],
                    'dimensions' => $row['Dimensions'],
                    'weight_kg' => $row['WeightKg'] ? (float)$row['WeightKg'] : null,
                ],
                'pricing' => [
                    'price' => (float)$row['Price'],
                    'compare_at_price' => $row['CompareAtPrice'] ? (float)$row['CompareAtPrice'] : null,
                    'wholesale_price' => $row['WholesalePrice'] ? (float)$row['WholesalePrice'] : null,
                ],
                'inventory' => [
                    'low_stock_threshold' => (int)$row['LowStockThreshold']
                ]
            ];
        }

        sqlsrv_free_stmt($stmt);

        // 6. Send successful JSON response
        http_response_code(200); // OK
        echo json_encode([
            "status" => "success",
            "count" => count($variants),
            "data" => $variants
        ]);
    } elseif ($action === 'get_single_variant') {
        $variantId = isset($_GET['variant_id']) ? (int)$_GET['variant_id'] : 0;

        if ($variantId <= 0) {
            throw new Exception("Invalid Variant ID provided.");
        }

        $sql = "
            SELECT v.*, p.Name AS ProductName 
            FROM dbo.ProductVariants v
            INNER JOIN dbo.Products p ON v.ProductId = p.ProductId 
            WHERE v.VariantId = ? AND v.IsActive = 1
         ";
        $stmt = sqlsrv_query($conn, $sql, [$variantId]);

        if ($stmt === false) {
            throw new Exception("Database query failed.");
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);

        if ($row) {
            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $row]);
        } else {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Variant not found."]);
        }
    } else {
        http_response_code(400); // Bad Request
        echo json_encode(["status" => "error", "message" => "Invalid action requested."]);
    }
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode([
        "status" => "error",
        "message" => "Server error occurred.",
        "error_details" => $e->getMessage() // Useful for debugging
    ]);
}
