<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-API-KEY");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . "/../config/database.php";

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed."]);
    exit;
}

$action = isset($_GET['action']) ? trim($_GET['action']) : 'get_variants';

try {
    if ($action === 'get_variants') {
        // 1. Pehle normal active variants fetch karo
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
            ORDER BY p.Name ASC, v.VariantId DESC
        ";

        $stmt = sqlsrv_query($conn, $sql);
        $variants = [];

        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $variants[] = [
                    'variant_id' => (int)$row['VariantId'],
                    'product_id' => (int)$row['ProductId'],
                    'product_name' => $row['ProductName'],
                    'sku' => $row['SKU'] ?? 'SKU-' . $row['ProductId'],
                    'barcode' => $row['Barcode'] ?? null,
                    'attributes' => [
                        'size' => $row['Size'] ?? 'Standard',
                        'color' => $row['Color'] ?? 'Default',
                        'material' => $row['Material'] ?? 'Linen',
                        'weight_gsm' => $row['WeightGSM'] ?? null,
                        'dimensions' => $row['Dimensions'] ?? null,
                        'weight_kg' => $row['WeightKg'] ? (float)$row['WeightKg'] : null,
                    ],
                    'pricing' => [
                        'price' => (float)$row['Price'],
                        'compare_at_price' => $row['CompareAtPrice'] ? (float)$row['CompareAtPrice'] : null,
                        'wholesale_price' => $row['WholesalePrice'] ? (float)$row['WholesalePrice'] : null,
                    ],
                    'inventory' => [
                        'low_stock_threshold' => (int)($row['LowStockThreshold'] ?? 5)
                    ]
                ];
            }
            sqlsrv_free_stmt($stmt);
        }

        // 2. SMART FALLBACK: Agar kisi product ka koi variant nahi hai, toh Products table se direct utha lo taaki wo Quote Builder mein dikhe!
        $pSql = "
            SELECT p.ProductId, p.Name, p.BasePrice 
            FROM dbo.Products p 
            WHERE p.IsActive = 1 
            AND p.ProductId NOT IN (SELECT DISTINCT ProductId FROM dbo.ProductVariants WHERE IsActive = 1)
        ";
        $pStmt = sqlsrv_query($conn, $pSql);
        if ($pStmt !== false) {
            while ($pRow = sqlsrv_fetch_array($pStmt, SQLSRV_FETCH_ASSOC)) {
                $variants[] = [
                    'variant_id' => 9000 + (int)$pRow['ProductId'], // Virtual Variant ID for products without variants
                    'product_id' => (int)$pRow['ProductId'],
                    'product_name' => $pRow['Name'],
                    'sku' => 'SKU-PROD-' . $pRow['ProductId'],
                    'barcode' => null,
                    'attributes' => [
                        'size' => 'Standard',
                        'color' => 'Default',
                        'material' => 'Linen',
                        'weight_gsm' => null,
                        'dimensions' => null,
                        'weight_kg' => null,
                    ],
                    'pricing' => [
                        'price' => (float)($pRow['BasePrice'] ?? 25.00),
                        'compare_at_price' => null,
                        'wholesale_price' => (float)($pRow['BasePrice'] ?? 25.00),
                    ],
                    'inventory' => [
                        'low_stock_threshold' => 5
                    ]
                ];
            }
            sqlsrv_free_stmt($pStmt);
        }

        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "count" => count($variants),
            "data" => $variants
        ]);
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid action requested."]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Server error occurred.",
        "error_details" => $e->getMessage()
    ]);
}
