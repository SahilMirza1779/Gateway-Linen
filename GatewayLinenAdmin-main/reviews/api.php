<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

/*
|--------------------------------------------------------------------------
| GET REQUEST: Fetch all approved reviews (or filter by ProductId)
|--------------------------------------------------------------------------
*/
if ($method === 'GET') {
    $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

    $sql = "SELECT 
                r.ReviewId,
                r.ProductId,
                r.UserId,
                r.Rating,
                r.Comment,
                r.IsApproved,
                r.CreatedAt,
                p.Name AS ProductName,
                ISNULL(u.Email, 'Anonymous') AS UserEmail
            FROM dbo.ProductReviews r
            LEFT JOIN dbo.Products p ON r.ProductId = p.ProductId
            LEFT JOIN dbo.Users u ON r.UserId = u.UserId
            WHERE r.IsApproved = 1";

    $params = [];
    if ($productId > 0) {
        $sql .= " AND r.ProductId = ?";
        $params[] = $productId;
    }

    $sql .= " ORDER BY r.ReviewId DESC";

    $stmt = sqlsrv_query($conn, $sql, $params);
    $reviews = [];

    if ($stmt !== false) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if ($row['CreatedAt'] instanceof DateTimeInterface) {
                $row['CreatedAt'] = $row['CreatedAt']->format('Y-m-d H:i:s');
            }
            $reviews[] = $row;
        }
        echo json_encode(["success" => true, "data" => $reviews]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to fetch reviews."]);
    }
    exit;
}

/*
|--------------------------------------------------------------------------
| POST REQUEST: Add a new review from the frontend
|--------------------------------------------------------------------------
*/
if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $reqProductId = (int)($data['product_id'] ?? 0);
    $userId       = (int)($data['user_id'] ?? 0);
    $rating       = (int)($data['rating'] ?? 5);
    $comment      = trim($data['comment'] ?? '');

    // 1. Check if reqProductId exists in dbo.Products table
    $productId = 0;
    if ($reqProductId > 0) {
        $chk = sqlsrv_query($conn, "SELECT ProductId FROM dbo.Products WHERE ProductId = ?", [$reqProductId]);
        if ($chk && sqlsrv_has_rows($chk)) {
            $productId = $reqProductId;
        }
    }

    // 2. If not found, grab the first valid ProductId from dbo.Products table automatically
    if ($productId <= 0) {
        $pQuery = sqlsrv_query($conn, "SELECT TOP 1 ProductId FROM dbo.Products");
        $pRow = ($pQuery) ? sqlsrv_fetch_array($pQuery, SQLSRV_FETCH_ASSOC) : null;
        $productId = ($pRow && isset($pRow['ProductId'])) ? (int)$pRow['ProductId'] : 0;
    }

    // 3. Check if userId exists in dbo.Users table
    $validUserId = 0;
    if ($userId > 0) {
        $uChk = sqlsrv_query($conn, "SELECT UserId FROM dbo.Users WHERE UserId = ?", [$userId]);
        if ($uChk && sqlsrv_has_rows($uChk)) {
            $validUserId = $userId;
        }
    }
    if ($validUserId <= 0) {
        $uQuery = sqlsrv_query($conn, "SELECT TOP 1 UserId FROM dbo.Users");
        $uRow = ($uQuery) ? sqlsrv_fetch_array($uQuery, SQLSRV_FETCH_ASSOC) : null;
        $validUserId = ($uRow && isset($uRow['UserId'])) ? (int)$uRow['UserId'] : 1;
    }

    if ($productId > 0 && $rating >= 1 && $rating <= 5 && $comment !== '') {
        $isApproved = 0;

        $sql = "INSERT INTO dbo.ProductReviews (ProductId, UserId, Rating, Comment, IsApproved, CreatedAt) VALUES (?, ?, ?, ?, ?, GETDATE())";
        $params = [$productId, $validUserId, $rating, $comment, $isApproved];

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt !== false) {
            echo json_encode(["success" => true, "message" => "Review submitted successfully and is pending approval."]);
        } else {
            $errors = sqlsrv_errors();
            echo json_encode([
                "success" => false,
                "message" => "Database insertion failed.",
                "sql_error" => $errors ? print_r($errors, true) : "Unknown error"
            ]);
        }
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Invalid product ID or review fields. Product ID found: $productId"
        ]);
    }
    exit;
}

/*
|--------------------------------------------------------------------------
| DELETE REQUEST: Remove a review
|--------------------------------------------------------------------------
*/
if ($method === 'DELETE' || (isset($_GET['action']) && $_GET['action'] === 'delete')) {
    $reviewId = isset($_GET['review_id']) ? (int)$_GET['review_id'] : 0;

    $sql = "DELETE FROM dbo.ProductReviews WHERE ReviewId = ?";
    $stmt = sqlsrv_query($conn, $sql, [$reviewId]);

    if ($stmt !== false) {
        echo json_encode(["success" => true, "message" => "Review deleted successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to delete review."]);
    }
    exit;
}

echo json_encode(["success" => false, "message" => "Unsupported request method."]);
