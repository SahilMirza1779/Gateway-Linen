<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-API-KEY");

// Include database connection
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Security check for API Key (Optional but recommended, match with your frontend)
$headers = apache_request_headers();
$apiKey = isset($headers['X-API-KEY']) ? $headers['X-API-KEY'] : '';

if ($apiKey !== 'GatewayLinen@2026') {
    echo json_encode(["success" => false, "message" => "Unauthorized Access."]);
    exit();
}

// Validate input
if (!isset($data->coupon_code) || !isset($data->cart_total)) {
    echo json_encode(["success" => false, "message" => "Coupon code aur cart total required hai."]);
    exit();
}

$couponCode = trim($data->coupon_code);
$cartTotal = floatval($data->cart_total);

try {
    // Fetch coupon details from database
    $query = "SELECT CouponId, CouponCode, DiscountType, DiscountValue, MinOrderAmount, MaxDiscountAmount, StartDate, EndDate, UsageLimit, TimesUsed, IsActive 
              FROM Coupons 
              WHERE CouponCode = ?";

    $stmt = sqlsrv_query($db, $query, array($couponCode));

    if ($stmt === false || !sqlsrv_has_rows($stmt)) {
        echo json_encode(["success" => false, "message" => "Invalid coupon code."]);
        exit();
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    // 1. Check if Coupon is Active
    if ($row['IsActive'] != 1) {
        echo json_encode(["success" => false, "message" => "Yeh coupon ab active nahi hai."]);
        exit();
    }

    // 2. Check Date Validity
    $today = date("Y-m-d");
    if ($row['StartDate'] !== null && $row['StartDate']->format('Y-m-d') > $today) {
        echo json_encode(["success" => false, "message" => "Yeh coupon abhi start nahi hua hai."]);
        exit();
    }
    if ($row['EndDate'] !== null && $row['EndDate']->format('Y-m-d') < $today) {
        echo json_encode(["success" => false, "message" => "Yeh coupon expire ho chuka hai."]);
        exit();
    }

    // 3. Check Usage Limits
    if ($row['UsageLimit'] !== null && $row['UsageLimit'] > 0 && $row['TimesUsed'] >= $row['UsageLimit']) {
        echo json_encode(["success" => false, "message" => "Is coupon ki usage limit khatam ho chuki hai."]);
        exit();
    }

    // 4. Check Minimum Order Amount
    if ($row['MinOrderAmount'] !== null && $cartTotal < $row['MinOrderAmount']) {
        echo json_encode(["success" => false, "message" => "Is coupon ke liye minimum order amount CAD $" . number_format($row['MinOrderAmount'], 2) . " hona chahiye."]);
        exit();
    }

    // 5. Calculate Discount
    $discountAmount = 0;

    // Check if DiscountType is Percentage or Fixed
    if (strtolower(trim($row['DiscountType'])) == 'percentage' || strtolower(trim($row['DiscountType'])) == '%') {
        $discountAmount = ($cartTotal * $row['DiscountValue']) / 100;

        // Apply Max Discount Cap if defined
        if ($row['MaxDiscountAmount'] !== null && $row['MaxDiscountAmount'] > 0 && $discountAmount > $row['MaxDiscountAmount']) {
            $discountAmount = $row['MaxDiscountAmount'];
        }
    } else {
        // Fixed amount discount
        $discountAmount = $row['DiscountValue'];
    }

    // Ensure discount is not more than cart total
    if ($discountAmount > $cartTotal) {
        $discountAmount = $cartTotal;
    }

    $finalTotal = $cartTotal - $discountAmount;

    // Return Success Response
    echo json_encode([
        "success" => true,
        "message" => "Coupon successfully apply ho gaya!",
        "data" => [
            "coupon_code" => $row['CouponCode'],
            "discount_type" => $row['DiscountType'],
            "discount_amount" => round($discountAmount, 2),
            "original_total" => round($cartTotal, 2),
            "final_total" => round($finalTotal, 2)
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server Error: " . $e->getMessage()
    ]);
}
