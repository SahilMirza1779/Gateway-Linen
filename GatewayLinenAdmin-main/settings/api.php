<?php
// GatewayLinenAdmin-main/settings/api.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-API-KEY");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . "/../config/database.php";

$action = isset($_GET['action']) ? trim($_GET['action']) : '';

if ($action === 'get_hero_image') {
    $sql = "SELECT SettingValue FROM dbo.SiteSettings WHERE SettingKey = 'HeroImage'";
    $stmt = sqlsrv_query($conn, $sql);

    $heroImage = "https://images.unsplash.com/photo-1590490360182-c33d57733427?q=80&w=1920&auto=format&fit=crop";

    if ($stmt !== false) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if ($row && !empty($row['SettingValue'])) {
            $rawImg = $row['SettingValue'];
            if (strpos($rawImg, 'http') === 0) {
                $heroImage = $rawImg;
            } else {
                $cleanPath = ltrim($rawImg, '/');
                $heroImage = "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/" . $cleanPath;
            }
        }
        sqlsrv_free_stmt($stmt);
    }

    echo json_encode(["success" => true, "imageUrl" => $heroImage]);
    exit;
}

if ($action === 'update_hero_image' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $headers = getallheaders();
    $apiKey = isset($headers['X-API-KEY']) ? $headers['X-API-KEY'] : (isset($headers['x-api-key']) ? $headers['x-api-key'] : '');

    if ($apiKey !== 'GatewayLinen@2026') {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Unauthorized access. Invalid API Key."]);
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/settings/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (isset($_FILES['heroImage'])) {
        $fileError = $_FILES['heroImage']['error'];

        if ($fileError === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['heroImage']['tmp_name'];
            $fileName = $_FILES['heroImage']['name'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            $allowedExts = array('jpg', 'jpeg', 'png', 'webp');

            if (in_array($fileExtension, $allowedExts)) {
                $newFileName = 'hero_image_' . time() . '.' . $fileExtension;
                $destPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $relativePath = 'uploads/settings/' . $newFileName;

                    $checkSql = "SELECT COUNT(*) AS count FROM dbo.SiteSettings WHERE SettingKey = 'HeroImage'";
                    $checkStmt = sqlsrv_query($conn, $checkSql);
                    $row = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);

                    if ($row && $row['count'] > 0) {
                        $sql = "UPDATE dbo.SiteSettings SET SettingValue = ?, UpdatedAt = GETDATE() WHERE SettingKey = 'HeroImage'";
                    } else {
                        $sql = "INSERT INTO dbo.SiteSettings (SettingKey, SettingValue) VALUES ('HeroImage', ?)";
                    }

                    $stmt = sqlsrv_query($conn, $sql, [$relativePath]);

                    if ($stmt) {
                        echo json_encode([
                            "success" => true,
                            "message" => "Hero image updated successfully!",
                            "imageUrl" => "http://localhost/Gateway-Linen/GatewayLinenAdmin-main/" . $relativePath
                        ]);
                    } else {
                        echo json_encode(["success" => false, "message" => "Database update failed."]);
                    }
                } else {
                    echo json_encode(["success" => false, "message" => "Error moving the uploaded file. Check folder permissions."]);
                }
            } else {
                echo json_encode(["success" => false, "message" => "Invalid file extension. Only JPG, JPEG, PNG, WEBP allowed."]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "PHP Upload Error Code: " . $fileError]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "No 'heroImage' variable found in request."]);
    }
    exit;
}

http_response_code(400);
echo json_encode(["success" => false, "message" => "Invalid action requested."]);
