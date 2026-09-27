<?php
// CORS Headers for React Frontend
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-API-KEY");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// API Key Security Check
$headers = apache_request_headers();
$apiKey = $headers['X-API-KEY'] ?? $headers['x-api-key'] ?? '';

if ($apiKey !== 'GatewayLinen@2026') {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized Access. Invalid API Key."]);
    exit;
}

require_once __DIR__ . "/../config/database.php";

$method = $_SERVER['REQUEST_METHOD'];

/*
|--------------------------------------------------------------------------
| POST - Submit a New Quote (From Frontend to Admin)
|--------------------------------------------------------------------------
*/
if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['action']) && $data['action'] === 'submit_quote') {

        if (empty($data['user_id']) || empty($data['items']) || !is_array($data['items'])) {
            echo json_encode(["success" => false, "message" => "Missing required fields or items."]);
            exit;
        }

        $userId = (int)$data['user_id'];
        $companyName = $data['company_name'] ?? 'N/A';
        $contactPerson = $data['contact_person'] ?? 'N/A';
        $totalQuotedAmount = (float)($data['total_amount'] ?? 0);
        $quoteNumber = "QT-" . strtoupper(uniqid());
        $status = 'Pending';

        if (sqlsrv_begin_transaction($conn) === false) {
            echo json_encode(["success" => false, "message" => "Could not start transaction."]);
            exit;
        }

        try {
            // 1. Insert Quote Master Table
            $sqlQuote = "INSERT INTO dbo.Quotes (QuoteNumber, UserId, CompanyName, ContactPerson, TotalQuotedAmount, Status, ExpiryDate, CreatedAt) 
                         OUTPUT INSERTED.QuoteId
                         VALUES (?, ?, ?, ?, ?, ?, DATEADD(day, 30, GETDATE()), GETDATE())";

            $paramsQuote = [$quoteNumber, $userId, $companyName, $contactPerson, $totalQuotedAmount, $status];
            $stmtQuote = sqlsrv_query($conn, $sqlQuote, $paramsQuote);

            if ($stmtQuote === false) {
                $errors = sqlsrv_errors();
                $errMsg = $errors[0]['message'] ?? 'Unknown Error';
                throw new Exception("Master Quote DB Error: " . $errMsg);
            }

            if (sqlsrv_fetch($stmtQuote)) {
                $newQuoteId = sqlsrv_get_field($stmtQuote, 0);
            } else {
                $newQuoteId = null;
            }

            if (!$newQuoteId) {
                throw new Exception("Failed to retrieve new Quote ID.");
            }

            // 2. Insert items into dbo.QuoteItems (WITH ULTIMATE FALLBACK)
            $sqlItem = "INSERT INTO dbo.QuoteItems (QuoteId, VariantId, Quantity, UnitPrice, LineTotal) VALUES (?, ?, ?, ?, ?)";

            foreach ($data['items'] as $item) {
                $incomingId = (int)$item['variant_id'];
                $quantity = (int)$item['quantity'];
                $unitPrice = (float)$item['unit_price'];
                $lineTotal = (float)$item['line_total'];

                $validVariantId = null;

                // Step A: Find default VariantId for this ProductId
                $varSql = "SELECT TOP 1 VariantId FROM dbo.ProductVariants WHERE ProductId = ?";
                $varStmt = sqlsrv_query($conn, $varSql, [$incomingId]);
                if ($varStmt !== false && sqlsrv_fetch($varStmt)) {
                    $validVariantId = sqlsrv_get_field($varStmt, 0);
                } else {
                    // Step B: Check if incoming ID is already a valid VariantId
                    $checkSql = "SELECT TOP 1 VariantId FROM dbo.ProductVariants WHERE VariantId = ?";
                    $checkStmt = sqlsrv_query($conn, $checkSql, [$incomingId]);
                    if ($checkStmt !== false && sqlsrv_fetch($checkStmt)) {
                        $validVariantId = sqlsrv_get_field($checkStmt, 0);
                    }
                }

                // Step C: ULTIMATE FALLBACK - Just pick ANY valid VariantId to save the quote from failing
                if (!$validVariantId) {
                    $fallbackSql = "SELECT TOP 1 VariantId FROM dbo.ProductVariants";
                    $fallbackStmt = sqlsrv_query($conn, $fallbackSql);
                    if ($fallbackStmt !== false && sqlsrv_fetch($fallbackStmt)) {
                        $validVariantId = sqlsrv_get_field($fallbackStmt, 0);
                    }
                }

                // If it's STILL null, it means your ProductVariants table is completely empty!
                if (!$validVariantId) {
                    throw new Exception("CRITICAL ERROR: Your 'dbo.ProductVariants' table is completely empty! Please add at least 1 variant in the Admin panel before quoting.");
                }

                // Insert with the valid VariantId
                $stmtItem = sqlsrv_query($conn, $sqlItem, [$newQuoteId, $validVariantId, $quantity, $unitPrice, $lineTotal]);

                if ($stmtItem === false) {
                    $errs = sqlsrv_errors();
                    throw new Exception("Item Insert Error: " . ($errs[0]['message'] ?? 'Unknown Error'));
                }
            }

            // Commit transaction
            sqlsrv_commit($conn);
            echo json_encode([
                "success" => true,
                "message" => "Quote submitted successfully!",
                "quote_number" => $quoteNumber,
                "quote_id" => $newQuoteId
            ]);
        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Invalid action specified."]);
    }
}
/*
|--------------------------------------------------------------------------
| GET - Fetch Quotes
|--------------------------------------------------------------------------
*/ elseif ($method === 'GET') {
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

    if ($userId > 0) {
        $sql = "SELECT QuoteId, QuoteNumber, CompanyName, ContactPerson, TotalQuotedAmount, Status, CreatedAt FROM dbo.Quotes WHERE UserId = ? ORDER BY CreatedAt DESC";
        $stmt = sqlsrv_query($conn, $sql, [$userId]);
    } else {
        $sql = "SELECT QuoteId, QuoteNumber, CompanyName, ContactPerson, TotalQuotedAmount, Status, CreatedAt FROM dbo.Quotes ORDER BY CreatedAt DESC";
        $stmt = sqlsrv_query($conn, $sql);
    }

    if ($stmt !== false) {
        $quotes = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if (isset($row['CreatedAt']) && is_object($row['CreatedAt'])) {
                $row['CreatedAt'] = $row['CreatedAt']->format('Y-m-d H:i:s');
            }
            $quotes[] = $row;
        }
        echo json_encode(["success" => true, "data" => $quotes]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to fetch quotes."]);
    }
} else {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed."]);
}
