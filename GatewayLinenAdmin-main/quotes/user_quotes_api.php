<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-API-KEY');

require_once __DIR__ . "/../config/database.php";

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || ($input['action'] ?? '') !== 'get_user_quotes') {
    echo json_encode(['success' => false, 'message' => 'Invalid request action.']);
    exit;
}

$userId = isset($input['userId']) ? (int)$input['userId'] : 11;

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'User ID is required.']);
    exit;
}

// Fetch Quotes for this specific user
$sql = "SELECT QuoteId, QuoteNumber, TotalQuotedAmount, Status, FORMAT(CreatedAt, 'dd MMM yyyy, hh:mm tt') AS CreatedAt 
        FROM dbo.Quotes 
        WHERE UserId = ? 
        ORDER BY QuoteId DESC";

$stmt = sqlsrv_query($conn, $sql, [$userId]);

$quotes = [];
if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $quotes[] = [
            'quoteId' => $row['QuoteId'],
            'quoteNumber' => $row['QuoteNumber'],
            'totalAmount' => $row['TotalQuotedAmount'],
            'status' => $row['Status'],
            'date' => $row['CreatedAt']
        ];
    }
    sqlsrv_free_stmt($stmt);
    echo json_encode(['success' => true, 'data' => $quotes]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch quotes from database.']);
}
exit;
