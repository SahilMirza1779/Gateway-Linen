<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| GatewayLinen - Categories API
|--------------------------------------------------------------------------
|
| GET
|   /categories/api.php
|   /categories/api.php?id=1003
|
| POST
|   action=insert
|   action=update
|   action=delete
|
| Authentication
|   X-API-KEY: GatewayLinen@2026
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| CORS / JSON
|--------------------------------------------------------------------------
*/

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY");
header("Content-Type: application/json; charset=UTF-8");


/*
|--------------------------------------------------------------------------
| OPTIONS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {

    http_response_code(200);

    exit;
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config/database.php';


/*
|--------------------------------------------------------------------------
| API KEY
|--------------------------------------------------------------------------
|
| Postman / React:
|
| Header:
|
| X-API-KEY: GatewayLinen@2026
|
|--------------------------------------------------------------------------
*/

$API_KEY = "GatewayLinen@2026";

$clientKey = $_SERVER['HTTP_X_API_KEY'] ?? '';

if (
    empty($clientKey) ||
    !hash_equals($API_KEY, $clientKey)
) {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Unauthorized API request."
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit;
}


/*
|--------------------------------------------------------------------------
| RESPONSE FUNCTION
|--------------------------------------------------------------------------
*/

function apiResponse(
    bool $success,
    string $message,
    mixed $data = null,
    int $statusCode = 200
): void {

    http_response_code($statusCode);

    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data"    => $data
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

    if (!$input) {
        return [];
    }

    $data = json_decode($input, true);

    if (!is_array($data)) {
        return [];
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
| SLUG GENERATOR
|--------------------------------------------------------------------------
*/

function generateSlug(string $name): string
{
    $slug = strtolower($name);

    $slug = preg_replace(
        '/[^a-z0-9]+/i',
        '-',
        $slug
    );

    $slug = trim((string)$slug, '-');

    return $slug;
}


/*
|--------------------------------------------------------------------------
| CATEGORY ROW
|--------------------------------------------------------------------------
*/

function categoryRow(array $row): array
{
    return [

        "categoryId" => isset($row["CategoryId"])
            ? (int)$row["CategoryId"]
            : 0,

        "parentCategoryId" =>
            $row["ParentCategoryId"] !== null
                ? (int)$row["ParentCategoryId"]
                : null,

        "name" =>
            $row["Name"] ?? null,

        "slug" =>
            $row["Slug"] ?? null,

        "description" =>
            $row["Description"] ?? null,

        "imageUrl" =>
            $row["ImageUrl"] ?? null,

        "iconClass" =>
            $row["IconClass"] ?? null,

        "metaTitle" =>
            $row["MetaTitle"] ?? null,

        "metaDescription" =>
            $row["MetaDescription"] ?? null,

        "displayOrder" =>
            $row["DisplayOrder"] !== null
                ? (int)$row["DisplayOrder"]
                : null,

        "isActive" =>
            (bool)$row["IsActive"],

        "createdAt" =>
            $row["CreatedAt"] instanceof DateTime
                ? $row["CreatedAt"]->format("Y-m-d H:i:s")
                : $row["CreatedAt"]

    ];
}


/*
|--------------------------------------------------------------------------
| GET CATEGORY SQL
|--------------------------------------------------------------------------
*/

$categorySelect = "
    SELECT
        CategoryId,
        ParentCategoryId,
        Name,
        Slug,
        Description,
        ImageUrl,
        IconClass,
        MetaTitle,
        MetaDescription,
        DisplayOrder,
        IsActive,
        CreatedAt
    FROM dbo.Categories
";


/*
|--------------------------------------------------------------------------
| REQUEST METHOD
|--------------------------------------------------------------------------
*/

$method = $_SERVER['REQUEST_METHOD'];


/*
|--------------------------------------------------------------------------
| GET
|--------------------------------------------------------------------------
*/

if ($method === 'GET') {

    /*
    |--------------------------------------------------------------------------
    | SINGLE CATEGORY
    |--------------------------------------------------------------------------
    */

    if (
        isset($_GET['id']) &&
        (int)$_GET['id'] > 0
    ) {

        $categoryId = (int)$_GET['id'];

        $sql = $categorySelect . "
            WHERE CategoryId = ?
        ";

        $stmt = sqlsrv_query(
            $conn,
            $sql,
            [$categoryId]
        );

        if ($stmt === false) {

            error_log(
                "Category GET error: " .
                print_r(sqlsrv_errors(), true)
            );

            apiResponse(
                false,
                "Unable to fetch category.",
                null,
                500
            );
        }

        $row = sqlsrv_fetch_array(
            $stmt,
            SQLSRV_FETCH_ASSOC
        );

        sqlsrv_free_stmt($stmt);

        if (!$row) {

            apiResponse(
                false,
                "Category not found.",
                null,
                404
            );
        }

        apiResponse(
            true,
            "Category fetched successfully.",
            categoryRow($row)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ALL CATEGORIES
    |--------------------------------------------------------------------------
    */

    $sql = $categorySelect . "

        ORDER BY

            CASE
                WHEN ParentCategoryId IS NULL
                THEN 0
                ELSE 1
            END,

            DisplayOrder ASC,

            Name ASC,

            CategoryId ASC
    ";


    $stmt = sqlsrv_query(
        $conn,
        $sql
    );


    if ($stmt === false) {

        error_log(
            "Category list error: " .
            print_r(sqlsrv_errors(), true)
        );

        apiResponse(
            false,
            "Unable to fetch categories.",
            null,
            500
        );
    }


    $categories = [];


    while (
        $row = sqlsrv_fetch_array(
            $stmt,
            SQLSRV_FETCH_ASSOC
        )
    ) {

        $categories[] = categoryRow($row);
    }


    sqlsrv_free_stmt($stmt);


    apiResponse(
        true,
        "Categories fetched successfully.",
        $categories
    );
}


/*
|--------------------------------------------------------------------------
| POST
|--------------------------------------------------------------------------
*/

if ($method === 'POST') {


    /*
    |--------------------------------------------------------------------------
    | FORM DATA ACTION
    |--------------------------------------------------------------------------
    */

    $action = cleanValue(
        $_POST['action'] ?? ''
    );


    /*
    |--------------------------------------------------------------------------
    | JSON BODY
    |--------------------------------------------------------------------------
    */

    $json = [];

    if ($action === '') {

        $json = getJsonBody();

        if (!empty($json)) {

            $action = cleanValue(
                $json['action'] ?? ''
            );

            $_POST = array_merge(
                $_POST,
                $json
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | ACTION VALIDATION
    |--------------------------------------------------------------------------
    */

    if (!in_array(
        $action,
        [
            'insert',
            'update',
            'delete'
        ],
        true
    )) {

        apiResponse(
            false,
            "Invalid action. Use insert, update or delete.",
            null,
            400
        );
    }


    /*
    |--------------------------------------------------------------------------
    | INSERT
    |--------------------------------------------------------------------------
    */

    if ($action === 'insert') {

        $name = cleanValue(
            $_POST['name'] ?? ''
        );

        $slug = cleanValue(
            $_POST['slug'] ?? ''
        );

        $parentCategoryId =
            $_POST['parentCategoryId'] ?? null;

        $description = cleanValue(
            $_POST['description'] ?? ''
        );

        $imageUrl = cleanValue(
            $_POST['imageUrl'] ?? ''
        );

        $iconClass = cleanValue(
            $_POST['iconClass'] ?? ''
        );

        $metaTitle = cleanValue(
            $_POST['metaTitle'] ?? ''
        );

        $metaDescription = cleanValue(
            $_POST['metaDescription'] ?? ''
        );

        $displayOrder =
            $_POST['displayOrder'] ?? null;

        $isActive = isset($_POST['isActive'])
            ? (int)$_POST['isActive']
            : 1;


        /*
        |--------------------------------------------------------------------------
        | NAME
        |--------------------------------------------------------------------------
        */

        if ($name === '') {

            apiResponse(
                false,
                "Category name is required.",
                null,
                422
            );
        }


        /*
        |--------------------------------------------------------------------------
        | SLUG
        |--------------------------------------------------------------------------
        */

        if ($slug === '') {

            $slug = generateSlug($name);
        }


        if ($slug === '') {

            apiResponse(
                false,
                "Unable to generate category slug.",
                null,
                422
            );
        }


        /*
        |--------------------------------------------------------------------------
        | PARENT
        |--------------------------------------------------------------------------
        */

        if (
            $parentCategoryId === '' ||
            $parentCategoryId === null
        ) {

            $parentCategoryId = null;

        } else {

            $parentCategoryId =
                (int)$parentCategoryId;


            /*
            | Check parent exists
            */

            $parentCheck = sqlsrv_query(
                $conn,
                "
                    SELECT CategoryId
                    FROM dbo.Categories
                    WHERE CategoryId = ?
                ",
                [$parentCategoryId]
            );


            if ($parentCheck === false) {

                apiResponse(
                    false,
                    "Unable to validate parent category.",
                    null,
                    500
                );
            }


            $parentRow =
                sqlsrv_fetch_array(
                    $parentCheck,
                    SQLSRV_FETCH_ASSOC
                );


            sqlsrv_free_stmt($parentCheck);


            if (!$parentRow) {

                apiResponse(
                    false,
                    "Parent category not found.",
                    null,
                    422
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | DISPLAY ORDER
        |--------------------------------------------------------------------------
        */

        if (
            $displayOrder === '' ||
            $displayOrder === null
        ) {

            if ($parentCategoryId === null) {

                $orderSql = "
                    SELECT
                        ISNULL(
                            MAX(DisplayOrder),
                            0
                        ) + 1 AS NextOrder
                    FROM dbo.Categories
                    WHERE ParentCategoryId IS NULL
                ";

                $orderParams = [];

            } else {

                $orderSql = "
                    SELECT
                        ISNULL(
                            MAX(DisplayOrder),
                            0
                        ) + 1 AS NextOrder
                    FROM dbo.Categories
                    WHERE ParentCategoryId = ?
                ";

                $orderParams = [
                    $parentCategoryId
                ];
            }


            $orderStmt = sqlsrv_query(
                $conn,
                $orderSql,
                $orderParams
            );


            if ($orderStmt === false) {

                apiResponse(
                    false,
                    "Unable to generate display order.",
                    null,
                    500
                );
            }


            $orderRow =
                sqlsrv_fetch_array(
                    $orderStmt,
                    SQLSRV_FETCH_ASSOC
                );


            sqlsrv_free_stmt($orderStmt);


            $displayOrder =
                (int)(
                    $orderRow['NextOrder'] ?? 1
                );

        } else {

            $displayOrder =
                (int)$displayOrder;
        }


        /*
        |--------------------------------------------------------------------------
        | DUPLICATE NAME
        |--------------------------------------------------------------------------
        */

        $duplicateStmt = sqlsrv_query(
            $conn,
            "
                SELECT COUNT(*) AS Total
                FROM dbo.Categories
                WHERE LOWER(Name) = LOWER(?)
            ",
            [$name]
        );


        if ($duplicateStmt === false) {

            apiResponse(
                false,
                "Unable to validate category.",
                null,
                500
            );
        }


        $duplicateRow =
            sqlsrv_fetch_array(
                $duplicateStmt,
                SQLSRV_FETCH_ASSOC
            );


        sqlsrv_free_stmt(
            $duplicateStmt
        );


        if (
            (int)(
                $duplicateRow['Total'] ?? 0
            ) > 0
        ) {

            apiResponse(
                false,
                "Category with this name already exists.",
                null,
                409
            );
        }


        /*
        |--------------------------------------------------------------------------
        | IMAGE UPLOAD
        |--------------------------------------------------------------------------
        */

        if (
            isset($_FILES['image']) &&
            $_FILES['image']['error'] === UPLOAD_ERR_OK
        ) {

            $uploadDir =
                __DIR__ .
                '/../uploads/categories/';


            if (!is_dir($uploadDir)) {

                mkdir(
                    $uploadDir,
                    0755,
                    true
                );
            }


            $tmpName =
                $_FILES['image']['tmp_name'];

            $originalName =
                $_FILES['image']['name'];

            $extension =
                strtolower(
                    pathinfo(
                        $originalName,
                        PATHINFO_EXTENSION
                    )
                );


            $allowedExtensions = [
                'jpg',
                'jpeg',
                'png',
                'webp',
                'gif'
            ];


            if (
                !in_array(
                    $extension,
                    $allowedExtensions,
                    true
                )
            ) {

                apiResponse(
                    false,
                    "Invalid image type.",
                    null,
                    422
                );
            }


            $imageName =
                'category_' .
                date('Ymd_His') .
                '_' .
                bin2hex(
                    random_bytes(4)
                ) .
                '.' .
                $extension;


            $destination =
                $uploadDir .
                $imageName;


            if (
                !move_uploaded_file(
                    $tmpName,
                    $destination
                )
            ) {

                apiResponse(
                    false,
                    "Unable to upload category image.",
                    null,
                    500
                );
            }


            $imageUrl =
                'uploads/categories/' .
                $imageName;
        }


        /*
        |--------------------------------------------------------------------------
        | INSERT
        |--------------------------------------------------------------------------
        */

        $insertSql = "

            INSERT INTO dbo.Categories
            (
                ParentCategoryId,
                Name,
                Slug,
                Description,
                ImageUrl,
                IconClass,
                MetaTitle,
                MetaDescription,
                DisplayOrder,
                IsActive,
                CreatedAt
            )

            OUTPUT
                INSERTED.CategoryId,
                INSERTED.ParentCategoryId,
                INSERTED.Name,
                INSERTED.Slug,
                INSERTED.Description,
                INSERTED.ImageUrl,
                INSERTED.IconClass,
                INSERTED.MetaTitle,
                INSERTED.MetaDescription,
                INSERTED.DisplayOrder,
                INSERTED.IsActive,
                INSERTED.CreatedAt

            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                GETDATE()
            )
        ";


        $insertParams = [

            $parentCategoryId,

            $name,

            $slug,

            $description !== ''
                ? $description
                : null,

            $imageUrl !== ''
                ? $imageUrl
                : null,

            $iconClass !== ''
                ? $iconClass
                : null,

            $metaTitle !== ''
                ? $metaTitle
                : null,

            $metaDescription !== ''
                ? $metaDescription
                : null,

            $displayOrder,

            $isActive
        ];


        $stmt = sqlsrv_query(
            $conn,
            $insertSql,
            $insertParams
        );


        if ($stmt === false) {

            error_log(
                "Category INSERT error: " .
                print_r(
                    sqlsrv_errors(),
                    true
                )
            );


            apiResponse(
                false,
                "Unable to create category.",
                null,
                500
            );
        }


        $newCategory =
            sqlsrv_fetch_array(
                $stmt,
                SQLSRV_FETCH_ASSOC
            );


        sqlsrv_free_stmt($stmt);


        apiResponse(
            true,
            "Category created successfully.",
            $newCategory
                ? categoryRow($newCategory)
                : null,
            201
        );
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    if ($action === 'update') {

        $categoryId =
            (int)(
                $_POST['categoryId'] ?? 0
            );


        if ($categoryId <= 0) {

            apiResponse(
                false,
                "Valid category ID is required.",
                null,
                422
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK CATEGORY
        |--------------------------------------------------------------------------
        */

        $existingStmt = sqlsrv_query(
            $conn,
            "
                SELECT *
                FROM dbo.Categories
                WHERE CategoryId = ?
            ",
            [$categoryId]
        );


        if ($existingStmt === false) {

            apiResponse(
                false,
                "Unable to check category.",
                null,
                500
            );
        }


        $existing =
            sqlsrv_fetch_array(
                $existingStmt,
                SQLSRV_FETCH_ASSOC
            );


        sqlsrv_free_stmt(
            $existingStmt
        );


        if (!$existing) {

            apiResponse(
                false,
                "Category not found.",
                null,
                404
            );
        }


        /*
        |--------------------------------------------------------------------------
        | VALUES
        |--------------------------------------------------------------------------
        */

        $name = cleanValue(
            $_POST['name'] ?? ''
        );


        $slug = cleanValue(
            $_POST['slug'] ?? ''
        );


        $parentCategoryId =
            $_POST['parentCategoryId'] ?? null;


        $description = cleanValue(
            $_POST['description'] ?? ''
        );


        $imageUrl = cleanValue(
            $_POST['imageUrl'] ?? ''
        );


        $iconClass = cleanValue(
            $_POST['iconClass'] ?? ''
        );


        $metaTitle = cleanValue(
            $_POST['metaTitle'] ?? ''
        );


        $metaDescription = cleanValue(
            $_POST['metaDescription'] ?? ''
        );


        $displayOrder =
            $_POST['displayOrder']
            ?? $existing['DisplayOrder']
            ?? 1;


        $isActive =
            isset($_POST['isActive'])
                ? (int)$_POST['isActive']
                : (int)$existing['IsActive'];


        /*
        |--------------------------------------------------------------------------
        | NAME
        |--------------------------------------------------------------------------
        */

        if ($name === '') {

            apiResponse(
                false,
                "Category name is required.",
                null,
                422
            );
        }


        /*
        |--------------------------------------------------------------------------
        | SLUG
        |--------------------------------------------------------------------------
        */

        if ($slug === '') {

            $slug = generateSlug(
                $name
            );
        }


        /*
        |--------------------------------------------------------------------------
        | PARENT
        |--------------------------------------------------------------------------
        */

        if (
            $parentCategoryId === '' ||
            $parentCategoryId === null
        ) {

            $parentCategoryId = null;

        } else {

            $parentCategoryId =
                (int)$parentCategoryId;
        }


        /*
        |--------------------------------------------------------------------------
        | SELF PARENT
        |--------------------------------------------------------------------------
        */

        if (
            $parentCategoryId !== null &&
            $parentCategoryId === $categoryId
        ) {

            apiResponse(
                false,
                "Category cannot be its own parent.",
                null,
                422
            );
        }


        /*
        |--------------------------------------------------------------------------
        | PARENT EXISTS
        |--------------------------------------------------------------------------
        */

        if ($parentCategoryId !== null) {

            $parentStmt = sqlsrv_query(
                $conn,
                "
                    SELECT CategoryId
                    FROM dbo.Categories
                    WHERE CategoryId = ?
                ",
                [$parentCategoryId]
            );


            if ($parentStmt === false) {

                apiResponse(
                    false,
                    "Unable to validate parent category.",
                    null,
                    500
                );
            }


            $parentRow =
                sqlsrv_fetch_array(
                    $parentStmt,
                    SQLSRV_FETCH_ASSOC
                );


            sqlsrv_free_stmt(
                $parentStmt
            );


            if (!$parentRow) {

                apiResponse(
                    false,
                    "Parent category not found.",
                    null,
                    422
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | DUPLICATE NAME
        |--------------------------------------------------------------------------
        */

        $duplicateStmt = sqlsrv_query(
            $conn,
            "
                SELECT COUNT(*) AS Total
                FROM dbo.Categories
                WHERE LOWER(Name) = LOWER(?)
                AND CategoryId <> ?
            ",
            [
                $name,
                $categoryId
            ]
        );


        if ($duplicateStmt === false) {

            apiResponse(
                false,
                "Unable to validate category.",
                null,
                500
            );
        }


        $duplicateRow =
            sqlsrv_fetch_array(
                $duplicateStmt,
                SQLSRV_FETCH_ASSOC
            );


        sqlsrv_free_stmt(
            $duplicateStmt
        );


        if (
            (int)(
                $duplicateRow['Total'] ?? 0
            ) > 0
        ) {

            apiResponse(
                false,
                "Another category with this name already exists.",
                null,
                409
            );
        }


        /*
        |--------------------------------------------------------------------------
        | IMAGE UPLOAD
        |--------------------------------------------------------------------------
        */

        if (
            isset($_FILES['image']) &&
            $_FILES['image']['error'] === UPLOAD_ERR_OK
        ) {

            $uploadDir =
                __DIR__ .
                '/../uploads/categories/';


            if (!is_dir($uploadDir)) {

                mkdir(
                    $uploadDir,
                    0755,
                    true
                );
            }


            $extension =
                strtolower(
                    pathinfo(
                        $_FILES['image']['name'],
                        PATHINFO_EXTENSION
                    )
                );


            $allowedExtensions = [
                'jpg',
                'jpeg',
                'png',
                'webp',
                'gif'
            ];


            if (
                !in_array(
                    $extension,
                    $allowedExtensions,
                    true
                )
            ) {

                apiResponse(
                    false,
                    "Invalid image type.",
                    null,
                    422
                );
            }


            $imageName =
                'category_' .
                date('Ymd_His') .
                '_' .
                bin2hex(
                    random_bytes(4)
                ) .
                '.' .
                $extension;


            $destination =
                $uploadDir .
                $imageName;


            if (
                !move_uploaded_file(
                    $_FILES['image']['tmp_name'],
                    $destination
                )
            ) {

                apiResponse(
                    false,
                    "Unable to upload category image.",
                    null,
                    500
                );
            }


            $imageUrl =
                'uploads/categories/' .
                $imageName;
        }


        /*
        |--------------------------------------------------------------------------
        | KEEP OLD IMAGE
        |--------------------------------------------------------------------------
        */

        if (
            $imageUrl === '' &&
            !isset($_FILES['image'])
        ) {

            $imageUrl =
                $existing['ImageUrl'];
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE
        |--------------------------------------------------------------------------
        */

        $updateSql = "

            UPDATE dbo.Categories

            SET

                ParentCategoryId = ?,

                Name = ?,

                Slug = ?,

                Description = ?,

                ImageUrl = ?,

                IconClass = ?,

                MetaTitle = ?,

                MetaDescription = ?,

                DisplayOrder = ?,

                IsActive = ?

            OUTPUT

                INSERTED.CategoryId,

                INSERTED.ParentCategoryId,

                INSERTED.Name,

                INSERTED.Slug,

                INSERTED.Description,

                INSERTED.ImageUrl,

                INSERTED.IconClass,

                INSERTED.MetaTitle,

                INSERTED.MetaDescription,

                INSERTED.DisplayOrder,

                INSERTED.IsActive,

                INSERTED.CreatedAt

            WHERE CategoryId = ?
        ";


        $updateParams = [

            $parentCategoryId,

            $name,

            $slug,

            $description !== ''
                ? $description
                : null,

            $imageUrl !== ''
                ? $imageUrl
                : null,

            $iconClass !== ''
                ? $iconClass
                : null,

            $metaTitle !== ''
                ? $metaTitle
                : null,

            $metaDescription !== ''
                ? $metaDescription
                : null,

            (int)$displayOrder,

            $isActive,

            $categoryId
        ];


        $stmt = sqlsrv_query(
            $conn,
            $updateSql,
            $updateParams
        );


        if ($stmt === false) {

            error_log(
                "Category UPDATE error: " .
                print_r(
                    sqlsrv_errors(),
                    true
                )
            );


            apiResponse(
                false,
                "Unable to update category.",
                null,
                500
            );
        }


        $updatedCategory =
            sqlsrv_fetch_array(
                $stmt,
                SQLSRV_FETCH_ASSOC
            );


        sqlsrv_free_stmt($stmt);


        if (!$updatedCategory) {

            apiResponse(
                false,
                "Category not found.",
                null,
                404
            );
        }


        apiResponse(
            true,
            "Category updated successfully.",
            categoryRow(
                $updatedCategory
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    if ($action === 'delete') {

        $categoryId =
            (int)(
                $_POST['categoryId'] ?? 0
            );


        if ($categoryId <= 0) {

            apiResponse(
                false,
                "Valid category ID is required.",
                null,
                422
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CATEGORY CHECK
        |--------------------------------------------------------------------------
        */

        $checkStmt = sqlsrv_query(
            $conn,
            "
                SELECT
                    CategoryId,
                    ParentCategoryId,
                    Name,
                    ImageUrl
                FROM dbo.Categories
                WHERE CategoryId = ?
            ",
            [$categoryId]
        );


        if ($checkStmt === false) {

            error_log(
                "Category DELETE check error: " .
                print_r(
                    sqlsrv_errors(),
                    true
                )
            );


            apiResponse(
                false,
                "Unable to check category.",
                null,
                500
            );
        }


        $category =
            sqlsrv_fetch_array(
                $checkStmt,
                SQLSRV_FETCH_ASSOC
            );


        sqlsrv_free_stmt(
            $checkStmt
        );


        if (!$category) {

            apiResponse(
                false,
                "Category not found.",
                null,
                404
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CHILD CATEGORY CHECK
        |--------------------------------------------------------------------------
        */

        $childStmt = sqlsrv_query(
            $conn,
            "
                SELECT COUNT(*) AS Total
                FROM dbo.Categories
                WHERE ParentCategoryId = ?
            ",
            [$categoryId]
        );


        if ($childStmt === false) {

            apiResponse(
                false,
                "Unable to check child categories.",
                null,
                500
            );
        }


        $childRow =
            sqlsrv_fetch_array(
                $childStmt,
                SQLSRV_FETCH_ASSOC
            );


        sqlsrv_free_stmt(
            $childStmt
        );


        $childCount =
            (int)(
                $childRow['Total'] ?? 0
            );


        if ($childCount > 0) {

            apiResponse(
                false,
                "This category has child categories. Delete or move child categories first.",
                [
                    "childCount" => $childCount
                ],
                409
            );
        }


        /*
        |--------------------------------------------------------------------------
        | PRODUCT CHECK
        |--------------------------------------------------------------------------
        */

        $productStmt = sqlsrv_query(
            $conn,
            "
                SELECT COUNT(*) AS Total
                FROM dbo.Products
                WHERE CategoryId = ?
            ",
            [$categoryId]
        );


        if ($productStmt === false) {

            apiResponse(
                false,
                "Unable to check products.",
                null,
                500
            );
        }


        $productRow =
            sqlsrv_fetch_array(
                $productStmt,
                SQLSRV_FETCH_ASSOC
            );


        sqlsrv_free_stmt(
            $productStmt
        );


        $productCount =
            (int)(
                $productRow['Total'] ?? 0
            );


        if ($productCount > 0) {

            apiResponse(
                false,
                "This category cannot be deleted because products are assigned to it.",
                [
                    "productCount" =>
                        $productCount
                ],
                409
            );
        }


        /*
        |--------------------------------------------------------------------------
        | TRANSACTION
        |--------------------------------------------------------------------------
        */

        if (!sqlsrv_begin_transaction($conn)) {

            apiResponse(
                false,
                "Unable to start transaction.",
                null,
                500
            );
        }


        try {


            /*
            |--------------------------------------------------------------------------
            | DELETE
            |--------------------------------------------------------------------------
            */

            $deleteStmt = sqlsrv_query(
                $conn,
                "
                    DELETE FROM dbo.Categories
                    WHERE CategoryId = ?
                ",
                [$categoryId]
            );


            if ($deleteStmt === false) {

                throw new RuntimeException(
                    "Delete query failed."
                );
            }


            sqlsrv_free_stmt(
                $deleteStmt
            );


            /*
            |--------------------------------------------------------------------------
            | RE-NUMBER
            |--------------------------------------------------------------------------
            */

            $renumberStmt = sqlsrv_query(
                $conn,
                "
                    ;WITH OrderedCategories AS
                    (
                        SELECT

                            CategoryId,

                            ROW_NUMBER() OVER
                            (
                                ORDER BY

                                    CASE
                                        WHEN DisplayOrder IS NULL
                                        THEN 2147483647
                                        ELSE DisplayOrder
                                    END ASC,

                                    Name ASC,

                                    CategoryId ASC

                            ) AS NewOrder

                        FROM dbo.Categories
                    )

                    UPDATE c

                    SET
                        c.DisplayOrder =
                            o.NewOrder

                    FROM dbo.Categories c

                    INNER JOIN
                        OrderedCategories o

                        ON o.CategoryId =
                           c.CategoryId;
                "
            );


            if ($renumberStmt === false) {

                throw new RuntimeException(
                    "Category renumber failed."
                );
            }


            sqlsrv_free_stmt(
                $renumberStmt
            );


            /*
            |--------------------------------------------------------------------------
            | COMMIT
            |--------------------------------------------------------------------------
            */

            if (!sqlsrv_commit($conn)) {

                throw new RuntimeException(
                    "Commit failed."
                );
            }


            /*
            |--------------------------------------------------------------------------
            | DELETE OLD IMAGE
            |--------------------------------------------------------------------------
            */

            if (
                !empty($category['ImageUrl'])
            ) {

                $oldImage =
                    __DIR__ .
                    '/../' .
                    ltrim(
                        $category['ImageUrl'],
                        '/'
                    );


                if (
                    is_file($oldImage)
                ) {

                    @unlink(
                        $oldImage
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | SUCCESS
            |--------------------------------------------------------------------------
            */

            apiResponse(
                true,
                "Category deleted successfully.",
                [
                    "categoryId" =>
                        $categoryId,

                    "name" =>
                        $category["Name"]
                ]
            );


        } catch (Throwable $e) {


            sqlsrv_rollback($conn);


            error_log(
                "Category DELETE error: " .
                $e->getMessage() .
                " | " .
                print_r(
                    sqlsrv_errors(),
                    true
                )
            );


            apiResponse(
                false,
                "Category could not be deleted.",
                null,
                500
            );
        }
    }
}


/*
|--------------------------------------------------------------------------
| METHOD NOT ALLOWED
|--------------------------------------------------------------------------
*/

apiResponse(
    false,
    "Method not allowed.",
    null,
    405
);