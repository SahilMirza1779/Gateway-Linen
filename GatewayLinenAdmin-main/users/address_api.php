<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-API-KEY");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$headers = apache_request_headers();
if (!isset($headers['X-API-KEY']) || $headers['X-API-KEY'] !== 'GatewayLinen@2026') {
    echo json_encode(["success" => false, "message" => "Unauthorized access."]);
    exit();
}

require_once '../config/database.php';
$database = new Database();
$conn = $database->getConnection();

$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput);

$action = isset($data->action) ? $data->action : '';

// 1. Get Addresses
if ($action === 'get_addresses') {
    $userId = isset($data->userId) ? $data->userId : null;
    if (!$userId) {
        echo json_encode(["success" => false, "message" => "User ID is required."]);
        exit();
    }

    $query = "SELECT * FROM [UserAddresses] WHERE UserId = ? AND (IsDeleted = 0 OR IsDeleted IS NULL) ORDER BY IsDefault DESC, CreatedAt DESC";
    $params = array($userId);
    $stmt = sqlsrv_query($conn, $query, $params);

    if ($stmt === false) {
        echo json_encode(["success" => false, "message" => "Database query failed.", "errors" => sqlsrv_errors()]);
        exit();
    }

    $addresses = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        if (isset($row['CreatedAt']) && is_object($row['CreatedAt'])) {
            $row['CreatedAt'] = $row['CreatedAt']->format('Y-m-d H:i:s');
        }
        if (isset($row['UpdatedAt']) && is_object($row['UpdatedAt'])) {
            $row['UpdatedAt'] = $row['UpdatedAt']->format('Y-m-d H:i:s');
        }
        $addresses[] = $row;
    }
    echo json_encode(["success" => true, "data" => $addresses]);
}
// 2. Add Address
elseif ($action === 'add_address') {
    $userId = isset($data->userId) ? $data->userId : null;
    $recipientName = isset($data->recipientName) ? trim($data->recipientName) : null;
    $phone = isset($data->phone) ? trim($data->phone) : null;
    $addressLine1 = isset($data->addressLine1) ? trim($data->addressLine1) : null;
    $city = isset($data->city) ? trim($data->city) : null;
    $stateProvince = isset($data->stateProvince) ? trim($data->stateProvince) : null;
    $postalCode = isset($data->postalCode) ? trim($data->postalCode) : null;
    $country = "Canada";

    if (empty($userId) || empty($recipientName) || empty($addressLine1) || empty($city) || empty($postalCode)) {
        echo json_encode(["success" => false, "message" => "Please fill all required fields."]);
        exit();
    }

    $query = "INSERT INTO [UserAddresses] 
              (UserId, AddressType, RecipientName, Phone, AddressLine1, City, StateProvince, PostalCode, Country, IsDefault, CreatedAt) 
              VALUES (?, 'Shipping', ?, ?, ?, ?, ?, ?, ?, 0, GETDATE())";
    $params = array($userId, $recipientName, $phone, $addressLine1, $city, $stateProvince, $postalCode, $country);

    $stmt = sqlsrv_query($conn, $query, $params);

    if ($stmt) {
        echo json_encode(["success" => true, "message" => "Address added successfully!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to add address in DB.", "errors" => sqlsrv_errors()]);
    }
}
// 3. Update Address
elseif ($action === 'update_address') {
    $addressId = isset($data->addressId) ? $data->addressId : null;
    $recipientName = isset($data->recipientName) ? trim($data->recipientName) : null;
    $phone = isset($data->phone) ? trim($data->phone) : null;
    $addressLine1 = isset($data->addressLine1) ? trim($data->addressLine1) : null;
    $city = isset($data->city) ? trim($data->city) : null;
    $stateProvince = isset($data->stateProvince) ? trim($data->stateProvince) : null;
    $postalCode = isset($data->postalCode) ? trim($data->postalCode) : null;

    if (empty($addressId) || empty($recipientName) || empty($addressLine1) || empty($city) || empty($postalCode)) {
        echo json_encode(["success" => false, "message" => "Please fill all required fields for update."]);
        exit();
    }

    $query = "UPDATE [UserAddresses] SET RecipientName = ?, Phone = ?, AddressLine1 = ?, City = ?, StateProvince = ?, PostalCode = ?, UpdatedAt = GETDATE() WHERE AddressId = ?";
    $params = array($recipientName, $phone, $addressLine1, $city, $stateProvince, $postalCode, $addressId);

    $stmt = sqlsrv_query($conn, $query, $params);

    if ($stmt) {
        echo json_encode(["success" => true, "message" => "Address updated successfully!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update address.", "errors" => sqlsrv_errors()]);
    }
}
// 4. Delete Address (Soft Delete)
elseif ($action === 'delete_address') {
    $addressId = isset($data->addressId) ? $data->addressId : null;

    if (empty($addressId)) {
        echo json_encode(["success" => false, "message" => "Address ID is required."]);
        exit();
    }

    $query = "UPDATE [UserAddresses] SET IsDeleted = 1, UpdatedAt = GETDATE() WHERE AddressId = ?";
    $params = array($addressId);

    $stmt = sqlsrv_query($conn, $query, $params);

    if ($stmt) {
        echo json_encode(["success" => true, "message" => "Address deleted successfully!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to delete address.", "errors" => sqlsrv_errors()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid action specified."]);
}
