<?php
// CORS Headers for React Frontend
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-API-KEY");

// Handle Preflight OPTIONS request for browser security
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check API Key Security
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
| GET - Fetch All Customers List
|--------------------------------------------------------------------------
*/
if ($method === 'GET') {
    $customers = [];
    $sql = "SELECT UserId, FullName, Email, Phone, CompanyName, IsActive, CreatedAt 
            FROM dbo.Users 
            WHERE RoleId IS NULL OR RoleId != 1 
            ORDER BY CreatedAt DESC";

    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt !== false) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Format datetime object to standard string
            if (isset($row['CreatedAt']) && is_object($row['CreatedAt'])) {
                $row['CreatedAt'] = $row['CreatedAt']->format('Y-m-d H:i:s');
            }
            // Ensure IsActive is integer
            $row['IsActive'] = isset($row['IsActive']) ? (int)$row['IsActive'] : 1;

            $customers[] = $row;
        }
        sqlsrv_free_stmt($stmt);
        echo json_encode(["success" => true, "data" => $customers]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to fetch customers.", "error" => sqlsrv_errors()]);
    }
}
/*
|--------------------------------------------------------------------------
| POST - Handle Actions (e.g., Block / Unblock Customer)
|--------------------------------------------------------------------------
*/ elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['action']) && $data['action'] === 'update_status') {

        if (!isset($data['user_id']) || !isset($data['is_active'])) {
            echo json_encode(["success" => false, "message" => "Missing required fields (user_id, is_active)."]);
            exit;
        }

        $userId = (int)$data['user_id'];
        $isActive = (int)$data['is_active']; // 0 = Blocked, 1 = Active

        $sql = "UPDATE dbo.Users SET IsActive = ? WHERE UserId = ?";
        $stmt = sqlsrv_query($conn, $sql, [$isActive, $userId]);

        if ($stmt) {
            $statusMsg = $isActive === 1 ? "Customer unblocked successfully." : "Customer blocked successfully.";
            echo json_encode(["success" => true, "message" => $statusMsg]);
            sqlsrv_free_stmt($stmt);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to update status.", "error" => sqlsrv_errors()]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Invalid action specified."]);
    }
}
/*
|--------------------------------------------------------------------------
| Invalid Method
|--------------------------------------------------------------------------
*/ else {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed. Use GET or POST."]);
}
