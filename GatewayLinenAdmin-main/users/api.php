<?php

declare(strict_types=1);

/*
    |--------------------------------------------------------------------------
    | GatewayLinen - Users + Roles REST API (With Mailer & Stateless OTP)
    |--------------------------------------------------------------------------
    |
    | File:
    |   /GatewayLinenadmin/users/api.php
    |
    | Database:
    |   GatewayLinenDB
    |
    | Supports:
    |
    | GET
    |   ?entity=users
    |   ?entity=users&id=1
    |   ?entity=roles
    |   ?entity=roles&id=1
    |
    | POST
    |   User Insert (With Welcome Email)
    |   User Update
    |   User Delete
    |   User Login
    |   Forgot Password - Send OTP (Stateless)
    |   Forgot Password - Verify OTP (Stateless)
    |   Forgot Password - Reset Password (Stateless)
    |   Role Insert
    |   Role Update
    |   Role Delete
    |
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | Header:
    |
    | X-API-KEY: GatewayLinen@2026
    |
    |--------------------------------------------------------------------------
    */


/*
    |--------------------------------------------------------------------------
    | CORS
    |--------------------------------------------------------------------------
    */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header(
    "Access-Control-Allow-Headers: " .
        "Content-Type, Authorization, X-API-KEY"
);

header("Content-Type: application/json; charset=UTF-8");


/*
    |--------------------------------------------------------------------------
    | OPTIONS / PREFLIGHT
    |--------------------------------------------------------------------------
    */

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


/*
    |--------------------------------------------------------------------------
    | DATABASE & MAILER
    |--------------------------------------------------------------------------
    */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/mailer.php';


/*
    |--------------------------------------------------------------------------
    | DATABASE CONNECTION CHECK
    |--------------------------------------------------------------------------
    */

if (!isset($conn) || !$conn) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database connection is not available.",
        "data" => null
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}


/*
    |--------------------------------------------------------------------------
    | API KEY
    |--------------------------------------------------------------------------
    */

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


/*
    |--------------------------------------------------------------------------
    | COMMON RESPONSE
    |--------------------------------------------------------------------------
    */

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


/*
    |--------------------------------------------------------------------------
    | JSON BODY
    |--------------------------------------------------------------------------
    */

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


/*
    |--------------------------------------------------------------------------
    | CLEAN VALUE
    |--------------------------------------------------------------------------
    */

function cleanValue(mixed $value): string
{
    return trim((string)($value ?? ''));
}


/*
    |--------------------------------------------------------------------------
    | POSITIVE INTEGER
    |--------------------------------------------------------------------------
    */

function getPositiveId(mixed $value, string $field): int
{
    if (
        $value === null ||
        $value === '' ||
        filter_var($value, FILTER_VALIDATE_INT) === false ||
        (int)$value <= 0
    ) {
        apiResponse(false, "Valid {$field} is required.", null, 422);
    }
    return (int)$value;
}


/*
    |--------------------------------------------------------------------------
    | BOOLEAN
    |--------------------------------------------------------------------------
    */

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


/*
    |--------------------------------------------------------------------------
    | PASSWORD HASH
    |--------------------------------------------------------------------------
    */

function makePasswordHash(string $password): string
{
    if ($password === '') {
        apiResponse(false, "Password is required.", null, 422);
    }
    if (strlen($password) < 6) {
        apiResponse(false, "Password must be at least 6 characters.", null, 422);
    }
    return password_hash($password, PASSWORD_BCRYPT);
}


/*
    |--------------------------------------------------------------------------
    | DATE FORMAT
    |--------------------------------------------------------------------------
    */

function dateValue(mixed $value): mixed
{
    if ($value instanceof DateTimeInterface) {
        return $value->format("Y-m-d H:i:s");
    }
    return $value;
}


/*
    |--------------------------------------------------------------------------
    | ROLE RESPONSE
    |--------------------------------------------------------------------------
    */

function roleRow(array $row): array
{
    return [
        "roleId" => isset($row["RoleId"]) ? (int)$row["RoleId"] : 0,
        "roleName" => $row["RoleName"] ?? null,
        "description" => $row["Description"] ?? null,
        "createdAt" => dateValue($row["CreatedAt"] ?? null)
    ];
}


/*
    |--------------------------------------------------------------------------
    | USER RESPONSE
    |--------------------------------------------------------------------------
    |
    | PasswordHash is NEVER returned.
    |
    |--------------------------------------------------------------------------
    */

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


/*
    |--------------------------------------------------------------------------
    | SELECT USERS
    |--------------------------------------------------------------------------
    |
    | RoleName is also returned.
    |
    |--------------------------------------------------------------------------
    */

$userSelect = "
        SELECT
            U.UserId,
            U.RoleId,
            R.RoleName,
            U.Email,
            U.PasswordHash,
            U.FullName,
            U.Phone,
            U.CompanyName,
            U.TaxNumber,
            U.IsWholesaleApproved,
            U.WholesaleDiscountPct,
            U.CreditLimit,
            U.IsTaxExempt,
            U.IsEmailVerified,
            U.IsActive,
            U.CreatedAt,
            U.UpdatedAt,
            U.LastLoginAt
        FROM dbo.Users U
        LEFT JOIN dbo.Roles R
            ON R.RoleId = U.RoleId
    ";


/*
    |--------------------------------------------------------------------------
    | SELECT ROLES
    |--------------------------------------------------------------------------
    */

$roleSelect = "
        SELECT
            RoleId,
            RoleName,
            Description,
            CreatedAt
        FROM dbo.Roles
    ";


/*
    |--------------------------------------------------------------------------
    | REQUEST METHOD
    |--------------------------------------------------------------------------
    */

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');


/*
    |--------------------------------------------------------------------------
    | GET
    |--------------------------------------------------------------------------
    */

if ($method === 'GET') {

    $entity = strtolower(cleanValue($_GET['entity'] ?? 'users'));

    /*
        |--------------------------------------------------------------------------
        | USERS
        |--------------------------------------------------------------------------
        */

    if ($entity === 'user' || $entity === 'users') {

        /*
            | Single User
            */

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

            if (!$row) {
                apiResponse(false, "User not found.", null, 404);
            }

            apiResponse(true, "User fetched successfully.", userRow($row));
        }

        /*
            | All Users
            */

        $conditions = [];
        $params = [];

        $search = cleanValue($_GET['search'] ?? '');
        if ($search !== '') {
            $conditions[] = "
                    (
                        U.Email LIKE ?
                        OR U.FullName LIKE ?
                        OR U.Phone LIKE ?
                        OR U.CompanyName LIKE ?
                    )
                ";
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if (isset($_GET['roleId']) && $_GET['roleId'] !== '') {
            $roleId = getPositiveId($_GET['roleId'], 'role id');
            $conditions[] = "U.RoleId = ?";
            $params[] = $roleId;
        }

        if (isset($_GET['isActive']) && $_GET['isActive'] !== '') {
            $conditions[] = "U.IsActive = ?";
            $params[] = booleanValue($_GET['isActive']);
        }

        $where = '';
        if (!empty($conditions)) {
            $where = " WHERE " . implode(" AND ", $conditions);
        }

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

    /*
        |--------------------------------------------------------------------------
        | ROLES
        |--------------------------------------------------------------------------
        */

    if ($entity === 'role' || $entity === 'roles') {

        /*
            | Single Role
            */

        if (isset($_GET['id'])) {
            $roleId = getPositiveId($_GET['id'], 'role id');
            $stmt = sqlsrv_query($conn, $roleSelect . " WHERE RoleId = ? ", [$roleId]);

            if ($stmt === false) {
                error_log("ROLE GET ERROR: " . print_r(sqlsrv_errors(), true));
                apiResponse(false, "Unable to fetch role.", null, 500);
            }

            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);

            if (!$row) {
                apiResponse(false, "Role not found.", null, 404);
            }

            apiResponse(true, "Role fetched successfully.", roleRow($row));
        }

        /*
            | All Roles
            */

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


/*
    |--------------------------------------------------------------------------
    | POST
    |--------------------------------------------------------------------------
    */

if ($method === 'POST') {

    /*
        | Read JSON
        */

    $data = getJsonBody();

    /*
        | FORM DATA SUPPORT
        */

    if (empty($data) && !empty($_POST)) {
        $data = $_POST;
    }

    /*
        | Entity
        */

    $entity = strtolower(cleanValue($data['entity'] ?? ''));

    if ($entity === '') {
        $entity = strtolower(cleanValue($data['type'] ?? ''));
    }

    /*
        | Action
        */

    $action = strtolower(cleanValue($data['action'] ?? ''));

    /*
        | Validate Entity
        */

    if (!in_array($entity, ['user', 'users', 'role', 'roles'], true)) {
        apiResponse(false, "Invalid entity. Use user or role.", null, 400);
    }

    /*
        | Validate Action (Updated with Forgot Password actions)
        */

    if (!in_array($action, ['insert', 'update', 'delete', 'login', 'send_otp', 'verify_otp', 'reset_password'], true)) {
        apiResponse(false, "Invalid action.", null, 400);
    }

    /*
        |--------------------------------------------------------------------------
        | USER LOGIN
        |--------------------------------------------------------------------------
        */

    if (($entity === 'user' || $entity === 'users') && $action === 'login') {

        $email = cleanValue($data['email'] ?? $data['Email'] ?? '');
        $password = (string)($data['password'] ?? $data['Password'] ?? '');

        if ($email === '' || $password === '') {
            apiResponse(false, "Email and password are required.", null, 422);
        }

        // Find user by email
        $stmt = sqlsrv_query(
            $conn,
            $userSelect . " WHERE LOWER(U.Email) = LOWER(?)",
            [$email]
        );

        if ($stmt === false) {
            error_log("LOGIN DB ERROR: " . print_r(sqlsrv_errors(), true));
            apiResponse(false, "Database error during login.", null, 500);
        }

        $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);

        // Check if user exists
        if (!$user) {
            apiResponse(false, "Invalid email or password.", null, 401);
        }

        // Verify Password against hash
        if (!password_verify($password, $user['PasswordHash'])) {
            apiResponse(false, "Invalid email or password.", null, 401);
        }

        // Check if active
        if (!(bool)($user["IsActive"] ?? false)) {
            apiResponse(false, "Account is disabled. Please contact support.", null, 403);
        }

        // Update Last Login Time
        sqlsrv_query(
            $conn,
            "UPDATE dbo.Users SET LastLoginAt = GETDATE() WHERE UserId = ?",
            [$user['UserId']]
        );

        // Success Response
        apiResponse(true, "Login successful.", userRow($user), 200);
    }

    /*
        |--------------------------------------------------------------------------
        | FORGOT PASSWORD: SEND OTP (STATELESS)
        |--------------------------------------------------------------------------
        */

    if (($entity === 'user' || $entity === 'users') && $action === 'send_otp') {

        $email = strtolower(cleanValue($data['email'] ?? ''));

        if ($email === '') {
            apiResponse(false, "Email is required.", null, 422);
        }

        $stmt = sqlsrv_query($conn, "SELECT UserId, FullName FROM dbo.Users WHERE LOWER(Email) = ?", [$email]);
        $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);

        if (!$user) {
            apiResponse(true, "If the email exists, an OTP has been sent.", null, 200);
        }

        $otp = (string)random_int(100000, 999999);
        @sendOTPEmail($email, $user['FullName'] ?? 'Customer', $otp);

        // Create a secure token valid for 10 minutes
        $expiryTime = time() + (10 * 60);
        $payload = $email . '|' . $otp . '|' . $expiryTime;
        $signature = hash_hmac('sha256', $payload, $API_KEY);
        $secureToken = base64_encode($payload . '::' . $signature);

        apiResponse(true, "OTP sent successfully.", ["token" => $secureToken], 200);
    }

    /*
        |--------------------------------------------------------------------------
        | FORGOT PASSWORD: VERIFY OTP (STATELESS)
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
        $expectedSignature = hash_hmac('sha256', $payload, $API_KEY);

        if (!hash_equals($expectedSignature, $signature)) {
            apiResponse(false, "Token tampered.", null, 400);
        }

        list($tokEmail, $tokOtp, $tokExpiry) = explode('|', $payload);

        if ($tokEmail !== $email || $tokOtp !== $otp) {
            apiResponse(false, "Invalid OTP.", null, 400);
        }
        if (time() > (int)$tokExpiry) {
            apiResponse(false, "OTP has expired. Please request a new one.", null, 400);
        }

        // Pass token back so frontend can use it for the final reset step
        apiResponse(true, "OTP verified successfully.", ["token" => $token], 200);
    }

    /*
        |--------------------------------------------------------------------------
        | FORGOT PASSWORD: RESET PASSWORD (STATELESS)
        |--------------------------------------------------------------------------
        */

    if (($entity === 'user' || $entity === 'users') && $action === 'reset_password') {

        $email = strtolower(cleanValue($data['email'] ?? ''));
        $otp = cleanValue($data['otp'] ?? '');
        $newPassword = (string)($data['newPassword'] ?? '');
        $token = cleanValue($data['token'] ?? '');

        if ($email === '' || $otp === '' || $newPassword === '' || $token === '') {
            apiResponse(false, "All fields are required.", null, 422);
        }

        // Verify the token one last time before allowing the update
        $decoded = base64_decode($token);
        if (!$decoded || strpos($decoded, '::') === false) {
            apiResponse(false, "Invalid secure token.", null, 400);
        }

        list($payload, $signature) = explode('::', $decoded, 2);
        if (!hash_equals(hash_hmac('sha256', $payload, $API_KEY), $signature)) {
            apiResponse(false, "Token tampered.", null, 400);
        }

        list($tokEmail, $tokOtp, $tokExpiry) = explode('|', $payload);
        if ($tokEmail !== $email || $tokOtp !== $otp || time() > (int)$tokExpiry) {
            apiResponse(false, "Invalid or expired session.", null, 400);
        }

        // Everything is valid, update the database
        $passwordHash = makePasswordHash($newPassword);
        $updateStmt = sqlsrv_query(
            $conn,
            "UPDATE dbo.Users SET PasswordHash = ?, UpdatedAt = GETDATE() WHERE LOWER(Email) = ?",
            [$passwordHash, $email]
        );

        if ($updateStmt === false) {
            apiResponse(false, "Failed to update password.", null, 500);
        }
        sqlsrv_free_stmt($updateStmt);

        // Fetch the user's name to send a friendly confirmation email
        $stmt = sqlsrv_query($conn, "SELECT FullName FROM dbo.Users WHERE LOWER(Email) = ?", [$email]);
        $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);

        @sendPasswordChangedEmail($email, $user['FullName'] ?? 'Customer');

        apiResponse(true, "Password has been reset successfully.", null, 200);
    }

    /*
        |--------------------------------------------------------------------------
        | USER INSERT (WITH WELCOME EMAIL TRIGGER)
        |--------------------------------------------------------------------------
        */

    if (($entity === 'user' || $entity === 'users') && $action === 'insert') {

        $email = cleanValue($data['email'] ?? $data['Email'] ?? '');

        if ($email === '') {
            apiResponse(false, "Email is required.", null, 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            apiResponse(false, "Invalid email address.", null, 422);
        }

        $duplicateStmt = sqlsrv_query($conn, "SELECT COUNT(*) AS Total FROM dbo.Users WHERE LOWER(Email) = LOWER(?)", [$email]);
        if ($duplicateStmt === false) {
            apiResponse(false, "Unable to validate email.", null, 500);
        }

        $duplicateRow = sqlsrv_fetch_array($duplicateStmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($duplicateStmt);

        if ((int)($duplicateRow['Total'] ?? 0) > 0) {
            apiResponse(false, "A user with this email already exists.", null, 409);
        }

        $plainPassword = (string)($data['password'] ?? $data['Password'] ?? '');
        $passwordHash = makePasswordHash($plainPassword);

        $roleValue = $data['roleId'] ?? $data['RoleId'] ?? null;
        $roleId = null;

        if ($roleValue !== null && $roleValue !== '') {
            $roleId = getPositiveId($roleValue, 'role id');
            $roleStmt = sqlsrv_query($conn, "SELECT RoleId FROM dbo.Roles WHERE RoleId = ?", [$roleId]);
            if ($roleStmt === false) {
                apiResponse(false, "Unable to validate role.", null, 500);
            }
            $roleExists = sqlsrv_fetch_array($roleStmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($roleStmt);
            if (!$roleExists) {
                apiResponse(false, "Role not found.", null, 422);
            }
        }

        $fullName = cleanValue($data['fullName'] ?? $data['FullName'] ?? '');
        $phone = cleanValue($data['phone'] ?? $data['Phone'] ?? '');
        $companyName = cleanValue($data['companyName'] ?? $data['CompanyName'] ?? '');
        $taxNumber = cleanValue($data['taxNumber'] ?? $data['TaxNumber'] ?? '');

        $isWholesaleApproved = booleanValue($data['isWholesaleApproved'] ?? $data['IsWholesaleApproved'] ?? 0);
        $wholesaleDiscountPct = (float)($data['wholesaleDiscountPct'] ?? $data['WholesaleDiscountPct'] ?? 0);
        $creditLimit = (float)($data['creditLimit'] ?? $data['CreditLimit'] ?? 0);
        $isTaxExempt = booleanValue($data['isTaxExempt'] ?? $data['IsTaxExempt'] ?? 0);
        $isEmailVerified = booleanValue($data['isEmailVerified'] ?? $data['IsEmailVerified'] ?? 0);
        $isActive = booleanValue($data['isActive'] ?? $data['IsActive'] ?? 1);

        if ($wholesaleDiscountPct < 0 || $wholesaleDiscountPct > 100) {
            apiResponse(false, "WholesaleDiscountPct must be between 0 and 100.", null, 422);
        }

        if ($creditLimit < 0) {
            apiResponse(false, "CreditLimit cannot be negative.", null, 422);
        }

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
            $passwordHash,
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
            error_log("USER INSERT ERROR: " . print_r(sqlsrv_errors(), true));
            apiResponse(false, "Unable to create user.", null, 500);
        }

        $newUser = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);

        // TRIGGER WELCOME EMAIL VIA MAILER INCLUDE
        @sendWelcomeEmail($email, $fullName !== '' ? $fullName : 'Valued Customer', $plainPassword);

        apiResponse(true, "User created successfully and welcome email sent.", $newUser ? userRow($newUser) : null, 201);
    }

    /*
        |--------------------------------------------------------------------------
        | USER UPDATE
        |--------------------------------------------------------------------------
        */

    if (($entity === 'user' || $entity === 'users') && $action === 'update') {

        $userId = getPositiveId($data['userId'] ?? $data['UserId'] ?? null, 'user id');

        $checkStmt = sqlsrv_query($conn, $userSelect . " WHERE U.UserId = ? ", [$userId]);
        if ($checkStmt === false) {
            apiResponse(false, "Unable to check user.", null, 500);
        }

        $existing = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($checkStmt);

        if (!$existing) {
            apiResponse(false, "User not found.", null, 404);
        }

        $sets = [];
        $params = [];

        if (array_key_exists('email', $data) || array_key_exists('Email', $data)) {
            $email = cleanValue($data['email'] ?? $data['Email'] ?? '');
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                apiResponse(false, "Valid email is required.", null, 422);
            }

            $duplicateStmt = sqlsrv_query($conn, "SELECT COUNT(*) AS Total FROM dbo.Users WHERE LOWER(Email) = LOWER(?) AND UserId <> ?", [$email, $userId]);
            if ($duplicateStmt === false) {
                apiResponse(false, "Unable to validate email.", null, 500);
            }
            $duplicateRow = sqlsrv_fetch_array($duplicateStmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($duplicateStmt);

            if ((int)($duplicateRow['Total'] ?? 0) > 0) {
                apiResponse(false, "Another user already uses this email.", null, 409);
            }

            $sets[] = "Email = ?";
            $params[] = $email;
        }

        if (array_key_exists('password', $data) || array_key_exists('Password', $data)) {
            $password = (string)($data['password'] ?? $data['Password'] ?? '');
            $sets[] = "PasswordHash = ?";
            $params[] = makePasswordHash($password);
        }

        if (array_key_exists('roleId', $data) || array_key_exists('RoleId', $data)) {
            $roleValue = $data['roleId'] ?? $data['RoleId'] ?? null;
            if ($roleValue === null || $roleValue === '') {
                $sets[] = "RoleId = NULL";
            } else {
                $roleId = getPositiveId($roleValue, 'role id');
                $roleStmt = sqlsrv_query($conn, "SELECT RoleId FROM dbo.Roles WHERE RoleId = ?", [$roleId]);
                if ($roleStmt === false) {
                    apiResponse(false, "Unable to validate role.", null, 500);
                }
                $roleExists = sqlsrv_fetch_array($roleStmt, SQLSRV_FETCH_ASSOC);
                sqlsrv_free_stmt($roleStmt);
                if (!$roleExists) {
                    apiResponse(false, "Role not found.", null, 422);
                }
                $sets[] = "RoleId = ?";
                $params[] = $roleId;
            }
        }

        $stringMap = [
            'fullName' => 'FullName',
            'phone' => 'Phone',
            'companyName' => 'CompanyName',
            'taxNumber' => 'TaxNumber'
        ];

        foreach ($stringMap as $input => $column) {
            $alternate = ucfirst($input);
            if (array_key_exists($input, $data)) {
                $value = cleanValue($data[$input]);
                $sets[] = "{$column} = ?";
                $params[] = $value !== '' ? $value : null;
            } elseif (array_key_exists($alternate, $data)) {
                $value = cleanValue($data[$alternate]);
                $sets[] = "{$column} = ?";
                $params[] = $value !== '' ? $value : null;
            }
        }

        $booleanMap = [
            'isWholesaleApproved' => 'IsWholesaleApproved',
            'isTaxExempt' => 'IsTaxExempt',
            'isEmailVerified' => 'IsEmailVerified',
            'isActive' => 'IsActive'
        ];

        foreach ($booleanMap as $input => $column) {
            $alternate = ucfirst($input);
            if (array_key_exists($input, $data)) {
                $sets[] = "{$column} = ?";
                $params[] = booleanValue($data[$input]);
            } elseif (array_key_exists($alternate, $data)) {
                $sets[] = "{$column} = ?";
                $params[] = booleanValue($data[$alternate]);
            }
        }

        if (array_key_exists('wholesaleDiscountPct', $data) || array_key_exists('WholesaleDiscountPct', $data)) {
            $discount = (float)($data['wholesaleDiscountPct'] ?? $data['WholesaleDiscountPct']);
            if ($discount < 0 || $discount > 100) {
                apiResponse(false, "WholesaleDiscountPct must be between 0 and 100.", null, 422);
            }
            $sets[] = "WholesaleDiscountPct = ?";
            $params[] = $discount;
        }

        if (array_key_exists('creditLimit', $data) || array_key_exists('CreditLimit', $data)) {
            $creditLimit = (float)($data['creditLimit'] ?? $data['CreditLimit']);
            if ($creditLimit < 0) {
                apiResponse(false, "CreditLimit cannot be negative.", null, 422);
            }
            $sets[] = "CreditLimit = ?";
            $params[] = $creditLimit;
        }

        if (empty($sets)) {
            apiResponse(false, "No fields supplied for update.", null, 422);
        }

        $sets[] = "UpdatedAt = GETDATE()";
        $params[] = $userId;

        $updateSql = "
                UPDATE dbo.Users
                SET " . implode(", ", $sets) . "
                OUTPUT
                    INSERTED.UserId, INSERTED.RoleId, INSERTED.Email, INSERTED.FullName, INSERTED.Phone,
                    INSERTED.CompanyName, INSERTED.TaxNumber, INSERTED.IsWholesaleApproved,
                    INSERTED.WholesaleDiscountPct, INSERTED.CreditLimit, INSERTED.IsTaxExempt,
                    INSERTED.IsEmailVerified, INSERTED.IsActive, INSERTED.CreatedAt,
                    INSERTED.UpdatedAt, INSERTED.LastLoginAt
                WHERE UserId = ?
            ";

        $stmt = sqlsrv_query($conn, $updateSql, $params);
        if ($stmt === false) {
            error_log("USER UPDATE ERROR: " . print_r(sqlsrv_errors(), true));
            apiResponse(false, "Unable to update user.", null, 500);
        }

        $updatedUser = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);

        if (!$updatedUser) {
            apiResponse(false, "User not found.", null, 404);
        }

        apiResponse(true, "User updated successfully.", userRow($updatedUser));
    }

    /*
        |--------------------------------------------------------------------------
        | USER DELETE
        |--------------------------------------------------------------------------
        */

    if (($entity === 'user' || $entity === 'users') && $action === 'delete') {

        $userId = getPositiveId($data['userId'] ?? $data['UserId'] ?? null, 'user id');

        $checkStmt = sqlsrv_query($conn, "SELECT UserId, Email, FullName FROM dbo.Users WHERE UserId = ?", [$userId]);
        if ($checkStmt === false) {
            apiResponse(false, "Unable to check user.", null, 500);
        }

        $user = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($checkStmt);

        if (!$user) {
            apiResponse(false, "User not found.", null, 404);
        }

        $deleteStmt = sqlsrv_query($conn, "DELETE FROM dbo.Users WHERE UserId = ?", [$userId]);
        if ($deleteStmt === false) {
            error_log("USER DELETE ERROR: " . print_r(sqlsrv_errors(), true));
            apiResponse(false, "Unable to delete user. Check foreign-key dependencies.", null, 409);
        }

        sqlsrv_free_stmt($deleteStmt);
        apiResponse(true, "User deleted successfully.", ["userId" => $userId, "email" => $user["Email"] ?? null]);
    }


    /*
        |--------------------------------------------------------------------------
        | ROLE INSERT
        |--------------------------------------------------------------------------
        */

    if (($entity === 'role' || $entity === 'roles') && $action === 'insert') {

        $roleName = cleanValue($data['roleName'] ?? $data['RoleName'] ?? '');
        $description = cleanValue($data['description'] ?? $data['Description'] ?? '');

        if ($roleName === '') {
            apiResponse(false, "RoleName is required.", null, 422);
        }

        $duplicateStmt = sqlsrv_query($conn, "SELECT COUNT(*) AS Total FROM dbo.Roles WHERE LOWER(RoleName) = LOWER(?)", [$roleName]);
        if ($duplicateStmt === false) {
            apiResponse(false, "Unable to validate role.", null, 500);
        }

        $duplicateRow = sqlsrv_fetch_array($duplicateStmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($duplicateStmt);

        if ((int)($duplicateRow['Total'] ?? 0) > 0) {
            apiResponse(false, "Role with this name already exists.", null, 409);
        }

        $stmt = sqlsrv_query(
            $conn,
            "
                    INSERT INTO dbo.Roles (RoleName, Description, CreatedAt)
                    OUTPUT INSERTED.RoleId, INSERTED.RoleName, INSERTED.Description, INSERTED.CreatedAt
                    VALUES (?, ?, GETDATE())
                ",
            [$roleName, $description !== '' ? $description : null]
        );

        if ($stmt === false) {
            error_log("ROLE INSERT ERROR: " . print_r(sqlsrv_errors(), true));
            apiResponse(false, "Unable to create role.", null, 500);
        }

        $newRole = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);

        apiResponse(true, "Role created successfully.", $newRole ? roleRow($newRole) : null, 201);
    }

    /*
        |--------------------------------------------------------------------------
        | ROLE UPDATE
        |--------------------------------------------------------------------------
        */

    if (($entity === 'role' || $entity === 'roles') && $action === 'update') {

        $roleId = getPositiveId($data['roleId'] ?? $data['RoleId'] ?? null, 'role id');

        $checkStmt = sqlsrv_query($conn, $roleSelect . " WHERE RoleId = ? ", [$roleId]);
        if ($checkStmt === false) {
            apiResponse(false, "Unable to check role.", null, 500);
        }

        $existing = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($checkStmt);

        if (!$existing) {
            apiResponse(false, "Role not found.", null, 404);
        }

        $roleName = cleanValue($data['roleName'] ?? $data['RoleName'] ?? '');
        $description = cleanValue($data['description'] ?? $data['Description'] ?? '');

        if ($roleName === '') {
            apiResponse(false, "RoleName is required.", null, 422);
        }

        $duplicateStmt = sqlsrv_query($conn, "SELECT COUNT(*) AS Total FROM dbo.Roles WHERE LOWER(RoleName) = LOWER(?) AND RoleId <> ?", [$roleName, $roleId]);
        if ($duplicateStmt === false) {
            apiResponse(false, "Unable to validate role.", null, 500);
        }

        $duplicateRow = sqlsrv_fetch_array($duplicateStmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($duplicateStmt);

        if ((int)($duplicateRow['Total'] ?? 0) > 0) {
            apiResponse(false, "Another role with this name already exists.", null, 409);
        }

        $stmt = sqlsrv_query(
            $conn,
            "
                    UPDATE dbo.Roles
                    SET RoleName = ?, Description = ?
                    OUTPUT INSERTED.RoleId, INSERTED.RoleName, INSERTED.Description, INSERTED.CreatedAt
                    WHERE RoleId = ?
                ",
            [$roleName, $description !== '' ? $description : null, $roleId]
        );

        if ($stmt === false) {
            error_log("ROLE UPDATE ERROR: " . print_r(sqlsrv_errors(), true));
            apiResponse(false, "Unable to update role.", null, 500);
        }

        $updatedRole = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);

        if (!$updatedRole) {
            apiResponse(false, "Role not found.", null, 404);
        }

        apiResponse(true, "Role updated successfully.", roleRow($updatedRole));
    }

    /*
        |--------------------------------------------------------------------------
        | ROLE DELETE
        |--------------------------------------------------------------------------
        */

    if (($entity === 'role' || $entity === 'roles') && $action === 'delete') {

        $roleId = getPositiveId($data['roleId'] ?? $data['RoleId'] ?? null, 'role id');

        $checkStmt = sqlsrv_query($conn, "SELECT RoleId, RoleName FROM dbo.Roles WHERE RoleId = ?", [$roleId]);
        if ($checkStmt === false) {
            apiResponse(false, "Unable to check role.", null, 500);
        }

        $role = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($checkStmt);

        if (!$role) {
            apiResponse(false, "Role not found.", null, 404);
        }

        $userCountStmt = sqlsrv_query($conn, "SELECT COUNT(*) AS Total FROM dbo.Users WHERE RoleId = ?", [$roleId]);
        if ($userCountStmt === false) {
            apiResponse(false, "Unable to check users assigned to this role.", null, 500);
        }

        $userCountRow = sqlsrv_fetch_array($userCountStmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($userCountStmt);

        $userCount = (int)($userCountRow['Total'] ?? 0);
        if ($userCount > 0) {
            apiResponse(
                false,
                "Role cannot be deleted because users are assigned to it.",
                ["roleId" => $roleId, "roleName" => $role["RoleName"], "userCount" => $userCount],
                409
            );
        }

        $deleteStmt = sqlsrv_query($conn, "DELETE FROM dbo.Roles WHERE RoleId = ?", [$roleId]);
        if ($deleteStmt === false) {
            error_log("ROLE DELETE ERROR: " . print_r(sqlsrv_errors(), true));
            apiResponse(false, "Unable to delete role.", null, 500);
        }

        sqlsrv_free_stmt($deleteStmt);
        apiResponse(true, "Role deleted successfully.", ["roleId" => $roleId, "roleName" => $role["RoleName"]]);
    }

    /*
        |--------------------------------------------------------------------------
        | INVALID REQUEST
        |--------------------------------------------------------------------------
        */

    apiResponse(false, "Invalid request.", null, 400);
}

/*
    |--------------------------------------------------------------------------
    | METHOD NOT ALLOWED
    |--------------------------------------------------------------------------
    */

apiResponse(false, "Method not allowed. Use GET or POST.", null, 405);
