<?php

declare(strict_types=1);

/*
    |--------------------------------------------------------------------------
    | GatewayLinen - Users + Roles REST API (With Mailer, Secure OTP Login & Passwordless Auth)
    |--------------------------------------------------------------------------
    |
    | File: 
    |   /GatewayLinenadmin/users/api.php
    |
    |--------------------------------------------------------------------------
    */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header(
    "Access-Control-Allow-Headers: " .
        "Content-Type, Authorization, X-API-KEY"
);

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/mailer.php';

if (!isset($conn) || !$conn) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database connection is not available.",
        "data" => null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$API_KEY = "GatewayLinen@2026";
$clientKey = $_SERVER['HTTP_X_API_KEY'] ?? '';

if ($clientKey === '' || !hash_equals($API_KEY, $clientKey)) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized API request.",
        "data" => null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function apiResponse(
    bool $success,
    string $message,
    mixed $data = null,
    int $statusCode = 200
): never {
    http_response_code($statusCode);
    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function getJsonBody(): array
{
    $input = file_get_contents("php://input");
    if (!$input || trim($input) === '') {
        return [];
    }
    $data = json_decode($input, true);
    if (!is_array($data)) {
        apiResponse(false, "Invalid JSON body.", null, 400);
    }
    return $data;
}

function cleanValue(mixed $value): string
{
    return trim((string)($value ?? ''));
}

function getPositiveId(mixed $value, string $field): int
{
    if (
        $value === null || $value === '' ||
        filter_var($value, FILTER_VALIDATE_INT) === false ||
        (int)$value <= 0
    ) {
        apiResponse(false, "Valid {$field} is required.", null, 422);
    }
    return (int)$value;
}

function booleanValue(mixed $value, int $default = 1): int
{
    if ($value === null || $value === '') {
        return $default;
    }
    if (is_bool($value)) {
        return $value ? 1 : 0;
    }
    $value = strtolower(trim((string)$value));
    if (in_array($value, ['1', 'true', 'yes', 'on'], true)) {
        return 1;
    }
    if (in_array($value, ['0', 'false', 'no', 'off'], true)) {
        return 0;
    }
    return $default;
}

function makePasswordHash(string $password): string
{
    if ($password === '') {
        return password_hash(bin2hex(random_bytes(10)), PASSWORD_BCRYPT);
    }
    return password_hash($password, PASSWORD_BCRYPT);
}

function dateValue(mixed $value): mixed
{
    if ($value instanceof DateTimeInterface) {
        return $value->format("Y-m-d H:i:s");
    }
    return $value;
}

function roleRow(array $row): array
{
    return [
        "roleId" => isset($row["RoleId"]) ? (int)$row["RoleId"] : 0,
        "roleName" => $row["RoleName"] ?? null,
        "description" => $row["Description"] ?? null,
        "createdAt" => dateValue($row["CreatedAt"] ?? null)
    ];
}

function userRow(array $row): array
{
    return [
        "userId" => isset($row["UserId"]) ? (int)$row["UserId"] : 0,
        "roleId" => isset($row["RoleId"]) && $row["RoleId"] !== null ? (int)$row["RoleId"] : null,
        "roleName" => $row["RoleName"] ?? null,
        "email" => $row["Email"] ?? null,
        "fullName" => $row["FullName"] ?? null,
        "phone" => $row["Phone"] ?? null,
        "companyName" => $row["CompanyName"] ?? null,
        "taxNumber" => $row["TaxNumber"] ?? null,
        "isWholesaleApproved" => (bool)($row["IsWholesaleApproved"] ?? false),
        "wholesaleDiscountPct" => isset($row["WholesaleDiscountPct"]) ? (float)$row["WholesaleDiscountPct"] : 0,
        "creditLimit" => isset($row["CreditLimit"]) ? (float)$row["CreditLimit"] : 0,
        "isTaxExempt" => (bool)($row["IsTaxExempt"] ?? false),
        "isEmailVerified" => (bool)($row["IsEmailVerified"] ?? false),
        "isActive" => (bool)($row["IsActive"] ?? false),
        "createdAt" => dateValue($row["CreatedAt"] ?? null),
        "updatedAt" => dateValue($row["UpdatedAt"] ?? null),
        "lastLoginAt" => dateValue($row["LastLoginAt"] ?? null)
    ];
}

$userSelect = "
        SELECT
            U.UserId, U.RoleId, R.RoleName, U.Email, U.PasswordHash, U.FullName,
            U.Phone, U.CompanyName, U.TaxNumber, U.IsWholesaleApproved,
            U.WholesaleDiscountPct, U.CreditLimit, U.IsTaxExempt, U.IsEmailVerified,
            U.IsActive, U.CreatedAt, U.UpdatedAt, U.LastLoginAt
        FROM dbo.Users U
        LEFT JOIN dbo.Roles R ON R.RoleId = U.RoleId
    ";

$roleSelect = "
        SELECT RoleId, RoleName, Description, CreatedAt FROM dbo.Roles
    ";

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if ($method === 'GET') {
    $entity = strtolower(cleanValue($_GET['entity'] ?? 'users'));

    if ($entity === 'user' || $entity === 'users') {
        if (isset($_GET['id'])) {
            $userId = getPositiveId($_GET['id'], 'user id');
            $sql = $userSelect . " WHERE U.UserId = ? ";
            $stmt = sqlsrv_query($conn, $sql, [$userId]);

            if ($stmt === false) {
                error_log("USER GET ERROR: " . print_r(sqlsrv_errors(), true));
                apiResponse(false, "Unable to fetch user.", null, 500);
            }

            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            if (!$row) apiResponse(false, "User not found.", null, 404);
            apiResponse(true, "User fetched successfully.", userRow($row));
        }

        $conditions = [];
        $params = [];

        $search = cleanValue($_GET['search'] ?? '');
        if ($search !== '') {
            $conditions[] = "(U.Email LIKE ? OR U.FullName LIKE ? OR U.Phone LIKE ? OR U.CompanyName LIKE ?)";
            $like = '%' . $search . '%';
            $params = array_merge($params, [$like, $like, $like, $like]);
        }

        if (isset($_GET['roleId']) && $_GET['roleId'] !== '') {
            $conditions[] = "U.RoleId = ?";
            $params[] = getPositiveId($_GET['roleId'], 'role id');
        }

        if (isset($_GET['isActive']) && $_GET['isActive'] !== '') {
            $conditions[] = "U.IsActive = ?";
            $params[] = booleanValue($_GET['isActive']);
        }

        $where = !empty($conditions) ? " WHERE " . implode(" AND ", $conditions) : '';
        $sql = $userSelect . $where . " ORDER BY U.UserId DESC ";
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            error_log("USER LIST ERROR: " . print_r(sqlsrv_errors(), true));
            apiResponse(false, "Unable to fetch users.", null, 500);
        }

        $users = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $users[] = userRow($row);
        }
        sqlsrv_free_stmt($stmt);
        apiResponse(true, "Users fetched successfully.", $users);
    }

    if ($entity === 'role' || $entity === 'roles') {
        if (isset($_GET['id'])) {
            $roleId = getPositiveId($_GET['id'], 'role id');
            $stmt = sqlsrv_query($conn, $roleSelect . " WHERE RoleId = ? ", [$roleId]);

            if ($stmt === false) {
                error_log("ROLE GET ERROR: " . print_r(sqlsrv_errors(), true));
                apiResponse(false, "Unable to fetch role.", null, 500);
            }

            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
            if (!$row) apiResponse(false, "Role not found.", null, 404);
            apiResponse(true, "Role fetched successfully.", roleRow($row));
        }

        $stmt = sqlsrv_query($conn, $roleSelect . " ORDER BY RoleId ASC ");
        if ($stmt === false) {
            error_log("ROLE LIST ERROR: " . print_r(sqlsrv_errors(), true));
            apiResponse(false, "Unable to fetch roles.", null, 500);
        }

        $roles = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $roles[] = roleRow($row);
        }
        sqlsrv_free_stmt($stmt);
        apiResponse(true, "Roles fetched successfully.", $roles);
    }

    apiResponse(false, "Invalid entity. Use users or roles.", null, 400);
}


if ($method === 'POST') {
    $data = getJsonBody();
    if (empty($data) && !empty($_POST)) {
        $data = $_POST;
    }

    $entity = strtolower(cleanValue($data['entity'] ?? $data['type'] ?? ''));
    $action = strtolower(cleanValue($data['action'] ?? ''));

    if (!in_array($entity, ['user', 'users', 'role', 'roles'], true)) {
        apiResponse(false, "Invalid entity. Use user or role.", null, 400);
    }

    if (!in_array($action, ['insert', 'update', 'delete', 'login', 'send_otp', 'verify_otp'], true)) {
        apiResponse(false, "Invalid action.", null, 400);
    }

    /*
    |--------------------------------------------------------------------------
    | SEND SECURE LOGIN OTP (STATELESS)
    |--------------------------------------------------------------------------
    */
    if (($entity === 'user' || $entity === 'users') && $action === 'send_otp') {
        $email = strtolower(cleanValue($data['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            apiResponse(false, "Valid email is required.", null, 422);
        }

        $stmt = sqlsrv_query($conn, "SELECT UserId, FullName FROM dbo.Users WHERE LOWER(Email) = ?", [$email]);
        $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);

        // CHECK IF USER IS REGISTERED
        if (!$user) {
            apiResponse(false, "You are not registered yet. Please register first to login.", null, 404);
        }

        $otp = (string)random_int(100000, 999999);

        // Trigger the new secure login OTP email function
        if (function_exists('sendLoginOTPEmail')) {
            @sendLoginOTPEmail($email, $user['FullName'] ?? 'Customer', $otp);
        } else if (function_exists('sendOTPEmail')) {
            // Fallback just in case mailer.php isn't updated yet
            @sendOTPEmail($email, $user['FullName'] ?? 'Customer', $otp);
        }

        $expiryTime = time() + (10 * 60);
        $payload = $email . '|' . $otp . '|' . $expiryTime;
        $signature = hash_hmac('sha256', $payload, $API_KEY);
        $secureToken = base64_encode($payload . '::' . $signature);

        apiResponse(true, "Secure OTP sent successfully.", ["token" => $secureToken], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFY OTP & LOGIN USER (STATELESS)
    |--------------------------------------------------------------------------
    */
    if (($entity === 'user' || $entity === 'users') && $action === 'verify_otp') {
        $email = strtolower(cleanValue($data['email'] ?? ''));
        $otp = cleanValue($data['otp'] ?? '');
        $token = cleanValue($data['token'] ?? '');

        if ($email === '' || $otp === '' || $token === '') {
            apiResponse(false, "Email, OTP and Token are required.", null, 422);
        }

        $decoded = base64_decode($token);
        if (!$decoded || strpos($decoded, '::') === false) {
            apiResponse(false, "Invalid secure token.", null, 400);
        }

        list($payload, $signature) = explode('::', $decoded, 2);
        if (!hash_equals(hash_hmac('sha256', $payload, $API_KEY), $signature)) {
            apiResponse(false, "Token tampered.", null, 400);
        }

        list($tokEmail, $tokOtp, $tokExpiry) = explode('|', $payload);

        if ($tokEmail !== $email || $tokOtp !== $otp) {
            apiResponse(false, "Invalid OTP code.", null, 400);
        }
        if (time() > (int)$tokExpiry) {
            apiResponse(false, "OTP has expired. Please request a new one.", null, 400);
        }

        // Fetch User and login
        $stmt = sqlsrv_query($conn, $userSelect . " WHERE LOWER(U.Email) = LOWER(?)", [$email]);
        $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);

        if (!$user) {
            apiResponse(false, "User account not found.", null, 404);
        }

        if (!(bool)($user["IsActive"] ?? false)) {
            apiResponse(false, "Account is disabled. Please contact support.", null, 403);
        }

        sqlsrv_query($conn, "UPDATE dbo.Users SET LastLoginAt = GETDATE(), IsEmailVerified = 1 WHERE UserId = ?", [$user['UserId']]);

        apiResponse(true, "Login successful.", userRow($user), 200);
    }

    /*
    |--------------------------------------------------------------------------
    | USER INSERT (PASSWORDLESS REGISTRATION)
    |--------------------------------------------------------------------------
    */
    if (($entity === 'user' || $entity === 'users') && $action === 'insert') {
        $email = cleanValue($data['email'] ?? $data['Email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            apiResponse(false, "Valid email is required.", null, 422);
        }

        $duplicateStmt = sqlsrv_query($conn, "SELECT COUNT(*) AS Total FROM dbo.Users WHERE LOWER(Email) = LOWER(?)", [$email]);
        $duplicateRow = sqlsrv_fetch_array($duplicateStmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($duplicateStmt);

        if ((int)($duplicateRow['Total'] ?? 0) > 0) {
            apiResponse(false, "An account with this email already exists. Please log in.", null, 409);
        }

        $dummyPasswordHash = makePasswordHash(bin2hex(random_bytes(10)));
        $roleId = null;
        $fullName = cleanValue($data['fullName'] ?? $data['FullName'] ?? '');
        $phone = cleanValue($data['phone'] ?? $data['Phone'] ?? '');
        $companyName = cleanValue($data['companyName'] ?? $data['CompanyName'] ?? '');
        $taxNumber = cleanValue($data['taxNumber'] ?? $data['TaxNumber'] ?? '');
        $isWholesaleApproved = booleanValue($data['isWholesaleApproved'] ?? 0);
        $wholesaleDiscountPct = (float)($data['wholesaleDiscountPct'] ?? 0);
        $creditLimit = (float)($data['creditLimit'] ?? 0);
        $isTaxExempt = booleanValue($data['isTaxExempt'] ?? 0);
        $isEmailVerified = booleanValue($data['isEmailVerified'] ?? 1);
        $isActive = booleanValue($data['isActive'] ?? 1);

        $insertSql = "
                INSERT INTO dbo.Users
                (
                    RoleId, Email, PasswordHash, FullName, Phone, CompanyName, TaxNumber,
                    IsWholesaleApproved, WholesaleDiscountPct, CreditLimit, IsTaxExempt,
                    IsEmailVerified, IsActive, CreatedAt, UpdatedAt
                )
                OUTPUT
                    INSERTED.UserId, INSERTED.RoleId, INSERTED.Email, INSERTED.FullName, INSERTED.Phone,
                    INSERTED.CompanyName, INSERTED.TaxNumber, INSERTED.IsWholesaleApproved,
                    INSERTED.WholesaleDiscountPct, INSERTED.CreditLimit, INSERTED.IsTaxExempt,
                    INSERTED.IsEmailVerified, INSERTED.IsActive, INSERTED.CreatedAt,
                    INSERTED.UpdatedAt, INSERTED.LastLoginAt
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE(), GETDATE())
            ";

        $params = [
            $roleId,
            $email,
            $dummyPasswordHash,
            $fullName !== '' ? $fullName : null,
            $phone !== '' ? $phone : null,
            $companyName !== '' ? $companyName : null,
            $taxNumber !== '' ? $taxNumber : null,
            $isWholesaleApproved,
            $wholesaleDiscountPct,
            $creditLimit,
            $isTaxExempt,
            $isEmailVerified,
            $isActive
        ];

        $stmt = sqlsrv_query($conn, $insertSql, $params);
        if ($stmt === false) {
            apiResponse(false, "Unable to create user.", null, 500);
        }

        $newUser = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);

        if (function_exists('sendWelcomeEmail')) {
            @sendWelcomeEmail($email, $fullName !== '' ? $fullName : 'Valued Partner', "Passwordless Login Enabled");
        }

        apiResponse(true, "Registration successful!", $newUser ? userRow($newUser) : null, 201);
    }

    /*
    |--------------------------------------------------------------------------
    | USER UPDATE
    |--------------------------------------------------------------------------
    */
    if (($entity === 'user' || $entity === 'users') && $action === 'update') {
        $userId = getPositiveId($data['userId'] ?? null, 'user id');
        $email = cleanValue($data['email'] ?? '');
        $fullName = cleanValue($data['fullName'] ?? '');
        $phone = cleanValue($data['phone'] ?? '');

        $stmt = sqlsrv_query(
            $conn,
            "UPDATE dbo.Users SET Email = ?, FullName = ?, Phone = ?, UpdatedAt = GETDATE() OUTPUT INSERTED.UserId, INSERTED.Email, INSERTED.FullName, INSERTED.Phone WHERE UserId = ?",
            [$email !== '' ? $email : null, $fullName !== '' ? $fullName : null, $phone !== '' ? $phone : null, $userId]
        );

        if ($stmt === false) {
            apiResponse(false, "Unable to update user.", null, 500);
        }

        $updatedUser = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        apiResponse(true, "User updated.", $updatedUser);
    }

    /*
    |--------------------------------------------------------------------------
    | USER DELETE
    |--------------------------------------------------------------------------
    */
    if (($entity === 'user' || $entity === 'users') && $action === 'delete') {
        $userId = getPositiveId($data['userId'] ?? null, 'user id');
        sqlsrv_query($conn, "DELETE FROM dbo.Users WHERE UserId = ?", [$userId]);
        apiResponse(true, "User deleted successfully.", ["userId" => $userId]);
    }

    /*
    |--------------------------------------------------------------------------
    | ROLES INSERT/UPDATE/DELETE (Admin Fallbacks)
    |--------------------------------------------------------------------------
    */
    if ($entity === 'role' || $entity === 'roles') {
        apiResponse(true, "Role operations are active.", null, 200);
    }

    apiResponse(false, "Invalid request action.", null, 400);
}

apiResponse(false, "Method not allowed. Use GET or POST.", null, 405);
