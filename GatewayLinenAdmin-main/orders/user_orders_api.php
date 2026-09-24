<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-KEY");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$headers = apache_request_headers();
$apiKey = $headers['X-API-KEY'] ?? $headers['x-api-key'] ?? '';
if ($apiKey !== 'GatewayLinen@2026') {
    echo json_encode(["success" => false, "message" => "Unauthorized access."]);
    exit();
}

require_once __DIR__ . '/../config/database.php';
$database = new Database();
$conn = $database->getConnection();

if (!isset($conn)) {
    echo json_encode(["success" => false, "message" => "Database connection failed."]);
    exit();
}

$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);
$action = isset($data['action']) ? $data['action'] : '';

// --- 1. FETCH USER ORDERS ---
if ($action === 'get_user_orders') {
    $userId = isset($data['userId']) ? (int)$data['userId'] : 0;

    if ($userId <= 0) {
        echo json_encode(["success" => false, "message" => "User ID is required."]);
        exit();
    }

    $orderQuery = "
        SELECT 
            OrderId, 
            OrderNumber, 
            OrderStatus, 
            CustomerOrderNotes, 
            FinalTotal, 
            OrderDate 
        FROM dbo.Orders 
        WHERE UserId = ? 
        ORDER BY OrderDate DESC
    ";

    $stmt = sqlsrv_query($conn, $orderQuery, array($userId));

    if ($stmt === false) {
        echo json_encode(["success" => false, "message" => "Failed to fetch orders.", "errors" => sqlsrv_errors()]);
        exit();
    }

    $orders = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $orderId = $row['OrderId'];

        $orderDate = "";
        if (isset($row['OrderDate']) && is_object($row['OrderDate'])) {
            $orderDate = $row['OrderDate']->format('Y-m-d');
        }

        $paymentMethodText = !empty($row['CustomerOrderNotes']) ? $row['CustomerOrderNotes'] : "Credit / Debit Card";

        $itemQuery = "
            SELECT 
                ProductName, 
                VariantDetails,
                Quantity, 
                UnitPrice 
            FROM dbo.OrderItems 
            WHERE OrderId = ?
        ";
        $itemStmt = sqlsrv_query($conn, $itemQuery, array($orderId));
        $items = [];

        if ($itemStmt) {
            while ($itemRow = sqlsrv_fetch_array($itemStmt, SQLSRV_FETCH_ASSOC)) {
                $image = "";
                $size = "Standard";

                if (!empty($itemRow['VariantDetails'])) {
                    $vd = json_decode($itemRow['VariantDetails'], true);
                    if (is_array($vd)) {
                        $image = isset($vd['image']) ? $vd['image'] : "";
                        $size = isset($vd['size']) ? $vd['size'] : "Standard";
                    }
                }

                $items[] = [
                    "name" => $itemRow['ProductName'] ?? "Product",
                    "cartQuantity" => (int)$itemRow['Quantity'],
                    "price" => (float)$itemRow['UnitPrice'],
                    "size" => $size,
                    "image" => $image
                ];
            }
        }

        $orders[] = [
            "id" => $row['OrderNumber'] ?? ("GW-" . $orderId),
            "date" => $orderDate,
            "total" => number_format((float)$row['FinalTotal'], 2, '.', ''),
            "status" => ucfirst($row['OrderStatus'] ?? 'Processing'),
            "paymentMethod" => $paymentMethodText,
            "items" => $items
        ];
    }

    echo json_encode(["success" => true, "data" => $orders]);
}

// --- 2. CREATE NEW ORDER ---
elseif ($action === 'create_order') {
    $userId = isset($data['userId']) ? (int)$data['userId'] : 0;
    $totalAmount = isset($data['totalAmount']) ? (float)$data['totalAmount'] : 0;
    $subtotal = isset($data['subtotal']) ? (float)$data['subtotal'] : $totalAmount;
    $shipping = isset($data['shipping']) ? (float)$data['shipping'] : 0;
    $tax = isset($data['tax']) ? (float)$data['tax'] : 0;
    $paymentMethodInput = isset($data['paymentMethod']) ? $data['paymentMethod'] : 'credit_card';
    $shipAddr = isset($data['shippingAddress']) ? $data['shippingAddress'] : [];
    $items = isset($data['items']) ? $data['items'] : [];

    if ($userId <= 0 || empty($items)) {
        echo json_encode(["success" => false, "message" => "User ID and items are required to place an order."]);
        exit();
    }

    $paymentMethodName = ($paymentMethodInput === 'cash_on_delivery') ? "Cash on Delivery / Wholesale Invoice" : "Credit / Debit Card";

    $userQuery = "SELECT FullName, Email, Phone FROM dbo.Users WHERE UserId = ?";
    $userStmt = sqlsrv_query($conn, $userQuery, array($userId));
    $customerName = "Valued Customer";
    $customerEmail = "customer@gatewaylinen.ca";
    $customerPhone = "0000000000";

    if ($userStmt && $userRow = sqlsrv_fetch_array($userStmt, SQLSRV_FETCH_ASSOC)) {
        if (!empty($userRow['FullName'])) $customerName = $userRow['FullName'];
        if (!empty($userRow['Email'])) $customerEmail = $userRow['Email'];
        if (!empty($userRow['Phone'])) $customerPhone = $userRow['Phone'];
    }

    $addrLine1 = !empty($shipAddr['addressLine1']) ? $shipAddr['addressLine1'] : "Main Street";
    $city = !empty($shipAddr['city']) ? $shipAddr['city'] : "Surat";
    $state = !empty($shipAddr['stateProvince']) ? $shipAddr['stateProvince'] : "Gujarat";
    $postal = !empty($shipAddr['postalCode']) ? $shipAddr['postalCode'] : "395006";

    $orderNumber = "GW-" . mt_rand(10000, 99999);

    $orderSql = "
        INSERT INTO dbo.Orders 
        (UserId, OrderNumber, OrderStatus, CustomerFullName, CustomerEmail, CustomerPhone, ShippingAddressLine1, ShippingCity, ShippingStateProvince, ShippingPostalCode, SubTotal, ShippingFee, TotalGstAmount, FinalTotal, CustomerOrderNotes, OrderDate) 
        OUTPUT INSERTED.OrderId 
        VALUES (?, ?, 'Processing', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE())
    ";

    $orderParams = array(
        $userId,
        $orderNumber,
        $customerName,
        $customerEmail,
        $customerPhone,
        $addrLine1,
        $city,
        $state,
        $postal,
        $subtotal,
        $shipping,
        $tax,
        $totalAmount,
        $paymentMethodName
    );

    $orderStmt = sqlsrv_query($conn, $orderSql, $orderParams);

    if ($orderStmt === false) {
        echo json_encode(["success" => false, "message" => "Database error inserting order.", "errors" => sqlsrv_errors()]);
        exit();
    }

    $orderRow = sqlsrv_fetch_array($orderStmt, SQLSRV_FETCH_ASSOC);
    $orderId = $orderRow['OrderId'] ?? 0;

    if ($orderId <= 0) {
        echo json_encode(["success" => false, "message" => "Order ID was not generated."]);
        exit();
    }

    foreach ($items as $item) {
        $productName = isset($item['name']) ? $item['name'] : 'Product';
        $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 1;
        $price = isset($item['price']) ? (float)$item['price'] : 0;
        $lineTotal = $price * $quantity;

        $size = isset($item['size']) ? $item['size'] : 'Standard';
        $image = isset($item['image']) ? $item['image'] : '';
        $sku = "GW-SKU-" . mt_rand(10000, 99999);

        $variantDetails = json_encode(['size' => $size, 'image' => $image]);

        $itemSql = "
            INSERT INTO dbo.OrderItems 
            (OrderId, SKU, ProductName, VariantDetails, Quantity, UnitPrice, LineTotal) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";
        $itemParams = array($orderId, $sku, $productName, $variantDetails, $quantity, $price, $lineTotal);
        $itemStmt = sqlsrv_query($conn, $itemSql, $itemParams);

        if ($itemStmt === false) {
            echo json_encode(["success" => false, "message" => "Error inserting order item.", "errors" => sqlsrv_errors()]);
            exit();
        }
    }

    echo json_encode([
        "success" => true,
        "message" => "Order placed successfully!",
        "data" => [
            "orderId" => $orderId,
            "orderNumber" => $orderNumber
        ]
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Invalid action specified."]);
}
