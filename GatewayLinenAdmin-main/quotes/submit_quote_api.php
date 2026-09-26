<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-API-KEY');

require_once __DIR__ . "/../config/database.php";

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || ($input['action'] ?? '') !== 'submit_quote') {
    echo json_encode(['success' => false, 'message' => 'Invalid request action.']);
    exit;
}

$userId = isset($input['userId']) ? (int)$input['userId'] : 11;
$companyName = isset($input['companyDetails']['hotelName']) ? trim($input['companyDetails']['hotelName']) : 'N/A';
$contactPerson = isset($input['companyDetails']['fullName']) ? trim($input['companyDetails']['fullName']) : 'N/A';
$totalAmount = isset($input['grandTotal']) ? (float)$input['grandTotal'] : 0;
$items = isset($input['quoteItems']) ? $input['quoteItems'] : [];

if (empty($items)) {
    echo json_encode(['success' => false, 'message' => 'No items selected for the quote.']);
    exit;
}

$quoteNumber = "QT-" . strtoupper(substr(md5(uniqid()), 0, 8));

// Added ExpiryDate (30 days from now) to satisfy NOT NULL constraint in dbo.Quotes
$sql = "INSERT INTO dbo.Quotes (QuoteNumber, UserId, CompanyName, ContactPerson, TotalQuotedAmount, Status, ExpiryDate, CreatedAt) 
        VALUES (?, ?, ?, ?, ?, 'Pending', DATEADD(day, 30, GETDATE()), GETDATE())";

$params = [$quoteNumber, $userId, $companyName, $contactPerson, $totalAmount];
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    $errors = sqlsrv_errors();
    $errorMsg = $errors ? $errors[0]['message'] : 'Unknown error';

    echo json_encode([
        'success' => false,
        'message' => 'Database SQL Error: ' . $errorMsg
    ]);
    exit;
}

sqlsrv_free_stmt($stmt);

// Get the inserted QuoteId
$idStmt = sqlsrv_query($conn, "SELECT @@IDENTITY AS QuoteId");
$row = sqlsrv_fetch_array($idStmt, SQLSRV_FETCH_ASSOC);
$quoteId = $row['QuoteId'] ?? 0;
sqlsrv_free_stmt($idStmt);

// Insert Quote Items
foreach ($items as $item) {
    $variantId = isset($item['variant_id']) ? (int)$item['variant_id'] : 0;
    $quantity = isset($item['exactQuantity']) ? (int)$item['exactQuantity'] : 0;
    $unitPrice = isset($item['pricing']['price']) ? (float)$item['pricing']['price'] : 0;
    $lineTotal = $quantity * $unitPrice;

    if ($variantId > 0 && $quantity > 0) {
        $itemSql = "INSERT INTO dbo.QuoteItems (QuoteId, VariantId, Quantity, UnitPrice, LineTotal) VALUES (?, ?, ?, ?, ?)";
        $itemStmt = sqlsrv_query($conn, $itemSql, [$quoteId, $variantId, $quantity, $unitPrice, $lineTotal]);
        if ($itemStmt !== false) {
            sqlsrv_free_stmt($itemStmt);
        }
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Quote submitted successfully!',
    'quoteId' => $quoteId,
    'quoteNumber' => $quoteNumber
]);
exit;
