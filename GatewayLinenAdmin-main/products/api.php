<?php

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/database.php';

function response(
    bool $success,
    string $message,
    $data = null,
    int $statusCode = 200
): void {
    http_response_code($statusCode);

    echo json_encode(
        [
            'success' => $success,
            'message' => $message,
            'data'    => $data
        ],
        JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES |
            JSON_PRETTY_PRINT
    );

    exit;
}

if (!isset($conn)) {
    response(
        false,
        'Database connection is not available.',
        null,
        500
    );
}

function databaseError(string $defaultMessage = 'Database operation failed.'): void
{
    $errors = sqlsrv_errors();
    $errorDetails = [];

    if ($errors) {
        foreach ($errors as $error) {
            $errorDetails[] = [
                'code'    => $error['code'] ?? null,
                'message' => $error['message'] ?? null
            ];
        }
    }

    response(
        false,
        $defaultMessage,
        [
            'errors' => $errorDetails
        ],
        500
    );
}

function getJsonInput(): array
{
    $raw = file_get_contents('php://input');

    if ($raw === false || trim($raw) === '') {
        response(
            false,
            'Request body is empty.',
            null,
            400
        );
    }

    $data = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        response(
            false,
            'Invalid JSON request body.',
            [
                'jsonError' => json_last_error_msg()
            ],
            400
        );
    }

    return $data;
}

function boolValue($value, bool $default = false): bool
{
    if ($value === null) {
        return $default;
    }

    if (is_bool($value)) {
        return $value;
    }

    if (is_numeric($value)) {
        return ((int)$value) === 1;
    }

    $value = strtolower(trim((string)$value));

    return in_array(
        $value,
        ['1', 'true', 'yes', 'on'],
        true
    );
}

function makeSlug(string $text): string
{
    $text = trim($text);
    $text = strtolower($text);

    $converted = @iconv(
        'UTF-8',
        'ASCII//TRANSLIT//IGNORE',
        $text
    );

    if ($converted !== false) {
        $text = $converted;
    }

    $text = preg_replace(
        '/[^a-z0-9]+/',
        '-',
        $text
    );

    $text = trim($text, '-');

    return $text;
}

function getUniqueSlug(
    $conn,
    string $slug,
    ?int $ignoreProductId = null
): string {
    if ($slug === '') {
        $slug = 'product';
    }

    $baseSlug = $slug;
    $counter = 1;

    while (true) {
        if ($ignoreProductId !== null) {
            $sql = "
                SELECT COUNT(*) AS Total
                FROM dbo.Products
                WHERE Slug = ?
                AND ProductId <> ?
            ";
            $params = [$slug, $ignoreProductId];
        } else {
            $sql = "
                SELECT COUNT(*) AS Total
                FROM dbo.Products
                WHERE Slug = ?
            ";
            $params = [$slug];
        }

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            databaseError('Unable to check product slug.');
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $count = (int)($row['Total'] ?? 0);

        if ($count === 0) {
            return $slug;
        }

        $counter++;
        $slug = $baseSlug . '-' . $counter;
    }
}

function getProductId(): ?int
{
    if (isset($_GET['id'])) {
        $id = filter_var(
            $_GET['id'],
            FILTER_VALIDATE_INT
        );

        if ($id === false || $id <= 0) {
            response(
                false,
                'Invalid product ID.',
                null,
                400
            );
        }

        return (int)$id;
    }

    return null;
}

function categoryExists($conn, int $categoryId): bool
{
    $sql = "
        SELECT COUNT(*) AS Total
        FROM dbo.Categories
        WHERE CategoryId = ?
        AND IsActive = 1
    ";

    $stmt = sqlsrv_query($conn, $sql, [$categoryId]);

    if ($stmt === false) {
        databaseError('Unable to validate category.');
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    return ((int)($row['Total'] ?? 0)) > 0;
}

function formatDateValue($value)
{
    if ($value instanceof DateTimeInterface) {
        return $value->format('Y-m-d H:i:s');
    }

    if ($value === null) {
        return null;
    }

    return (string)$value;
}

function getProductImages($conn, int $productId): array
{
    $sql = "
        SELECT
            ImageId,
            ProductId,
            VariantId,
            ImageUrl,
            AltText,
            IsMain,
            DisplayOrder
        FROM dbo.ProductImages
        WHERE ProductId = ?
        ORDER BY
            IsMain DESC,
            DisplayOrder ASC,
            ImageId ASC
    ";

    $stmt = sqlsrv_query($conn, $sql, [$productId]);

    if ($stmt === false) {
        return [];
    }

    $images = [];

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $images[] = [
            'imageId'      => (int)$row['ImageId'],
            'productId'    => (int)$row['ProductId'],
            'variantId'    => $row['VariantId'] !== null ? (int)$row['VariantId'] : null,
            'imageUrl'     => $row['ImageUrl'],
            'altText'      => $row['AltText'],
            'isMain'       => (bool)$row['IsMain'],
            'displayOrder' => (int)$row['DisplayOrder']
        ];
    }

    return $images;
}

function getProductVariants($conn, int $productId): array
{
    $sql = "
        SELECT
            VariantId,
            ProductId,
            SKU,
            Barcode,
            Size,
            Color,
            ThreadCount,
            Material,
            WeightGSM,
            Dimensions,
            Price,
            CompareAtPrice,
            WholesalePrice,
            CostPrice,
            WeightKg,
            LowStockThreshold,
            IsActive,
            CreatedAt
        FROM dbo.ProductVariants
        WHERE ProductId = ?
        ORDER BY VariantId ASC
    ";

    $stmt = sqlsrv_query($conn, $sql, [$productId]);

    if ($stmt === false) {
        return [];
    }

    $variants = [];

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $variants[] = [
            'variantId'         => (int)$row['VariantId'],
            'productId'         => (int)$row['ProductId'],
            'sku'               => $row['SKU'],
            'barcode'           => $row['Barcode'],
            'size'              => $row['Size'],
            'color'             => $row['Color'],
            'threadCount'       => $row['ThreadCount'],
            'material'          => $row['Material'],
            'weightGSM'         => $row['WeightGSM'] !== null ? (float)$row['WeightGSM'] : null,
            'dimensions'        => $row['Dimensions'],
            'price'             => $row['Price'] !== null ? (float)$row['Price'] : null,
            'compareAtPrice'    => $row['CompareAtPrice'] !== null ? (float)$row['CompareAtPrice'] : null,
            'wholesalePrice'    => $row['WholesalePrice'] !== null ? (float)$row['WholesalePrice'] : null,
            'costPrice'         => $row['CostPrice'] !== null ? (float)$row['CostPrice'] : null,
            'weightKg'          => $row['WeightKg'] !== null ? (float)$row['WeightKg'] : null,
            'lowStockThreshold' => $row['LowStockThreshold'] !== null ? (int)$row['LowStockThreshold'] : null,
            'isActive'          => (bool)$row['IsActive'],
            'createdAt'         => formatDateValue($row['CreatedAt'])
        ];
    }

    return $variants;
}

function getProduct(
    $conn,
    int $productId,
    bool $includeChildren = true
): ?array {
    $sql = "
        SELECT
            p.ProductId,
            p.CategoryId,
            c.Name AS CategoryName,
            p.Name,
            p.Slug,
            p.ShortDescription,
            p.Description,
            p.CareInstructions,
            p.Specifications,
            p.BasePrice,
            p.GstPercentage,
            p.PstPercentage,
            p.MetaTitle,
            p.MetaDescription,
            p.IsFeatured,
            p.IsNewArrival,
            p.IsBestSeller,
            p.IsActive,
            p.CreatedAt,
            p.UpdatedAt
        FROM dbo.Products p
        LEFT JOIN dbo.Categories c
            ON c.CategoryId = p.CategoryId
        WHERE p.ProductId = ?
    ";

    $stmt = sqlsrv_query($conn, $sql, [$productId]);

    if ($stmt === false) {
        databaseError('Unable to fetch product.');
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if (!$row) {
        return null;
    }

    $imagesList = getProductImages($conn, $productId);
    $mainImageUrl = !empty($imagesList) ? $imagesList[0]['imageUrl'] : null;

    $product = [
        'productId'        => (int)$row['ProductId'],
        'categoryId'       => (int)$row['CategoryId'],
        'categoryName'     => $row['CategoryName'],
        'name'             => $row['Name'],
        'slug'             => $row['Slug'],
        'shortDescription' => $row['ShortDescription'],
        'description'      => $row['Description'],
        'careInstructions' => $row['CareInstructions'],
        'specifications'   => $row['Specifications'],
        'basePrice'        => $row['BasePrice'] !== null ? (float)$row['BasePrice'] : null,
        'gstPercentage'    => $row['GstPercentage'] !== null ? (float)$row['GstPercentage'] : null,
        'pstPercentage'    => $row['PstPercentage'] !== null ? (float)$row['PstPercentage'] : null,
        'metaTitle'        => $row['MetaTitle'],
        'metaDescription'  => $row['MetaDescription'],
        'isFeatured'       => (bool)$row['IsFeatured'],
        'isNewArrival'     => (bool)$row['IsNewArrival'],
        'isBestSeller'     => (bool)$row['IsBestSeller'],
        'isActive'         => (bool)$row['IsActive'],
        'imageUrl'         => $mainImageUrl,
        'images'           => $imagesList,
        'createdAt'        => formatDateValue($row['CreatedAt']),
        'updatedAt'        => formatDateValue($row['UpdatedAt'])
    ];

    if ($includeChildren) {
        $product['variants'] = getProductVariants($conn, $productId);
    }

    return $product;
}

function showProducts($conn): void
{
    $productId = getProductId();

    if ($productId !== null) {
        $product = getProduct($conn, $productId, true);

        if ($product === null) {
            response(false, 'Product not found.', null, 404);
        }

        response(true, 'Product fetched successfully.', $product, 200);
    }

    $categoryId = null;
    if (isset($_GET['categoryId']) && $_GET['categoryId'] !== '') {
        $categoryId = filter_var($_GET['categoryId'], FILTER_VALIDATE_INT);
    }

    $isActive = null;
    if (isset($_GET['isActive']) && $_GET['isActive'] !== '') {
        $isActive = boolValue($_GET['isActive']);
    }

    $search = null;
    if (isset($_GET['search'])) {
        $search = trim((string)$_GET['search']);
    }

    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 20;
    $offset = ($page - 1) * $limit;

    $where = [];
    $params = [];

    if ($categoryId !== null) {
        $where[] = 'p.CategoryId = ?';
        $params[] = $categoryId;
    }

    if ($isActive !== null) {
        $where[] = 'p.IsActive = ?';
        $params[] = $isActive ? 1 : 0;
    }

    if ($search !== null && $search !== '') {
        $where[] = "(p.Name LIKE ? OR p.Slug LIKE ? OR p.ShortDescription LIKE ?)";
        $searchValue = '%' . $search . '%';
        $params[] = $searchValue;
        $params[] = $searchValue;
        $params[] = $searchValue;
    }

    $whereSql = '';
    if (!empty($where)) {
        $whereSql = ' WHERE ' . implode(' AND ', $where);
    }

    $countSql = "SELECT COUNT(*) AS Total FROM dbo.Products p $whereSql";
    $countStmt = sqlsrv_query($conn, $countSql, $params);
    if ($countStmt === false) {
        databaseError('Unable to count products.');
    }
    $countRow = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC);
    $total = (int)($countRow['Total'] ?? 0);

    $sql = "
        SELECT
            p.ProductId,
            p.CategoryId,
            c.Name AS CategoryName,
            p.Name,
            p.Slug,
            p.ShortDescription,
            p.Description,
            p.CareInstructions,
            p.Specifications,
            p.BasePrice,
            p.GstPercentage,
            p.PstPercentage,
            p.MetaTitle,
            p.MetaDescription,
            p.IsFeatured,
            p.IsNewArrival,
            p.IsBestSeller,
            p.IsActive,
            p.CreatedAt,
            p.UpdatedAt
        FROM dbo.Products p
        LEFT JOIN dbo.Categories c
            ON c.CategoryId = p.CategoryId
        $whereSql
        ORDER BY p.ProductId DESC
        OFFSET ? ROWS
        FETCH NEXT ? ROWS ONLY
    ";

    $listParams = $params;
    $listParams[] = $offset;
    $listParams[] = $limit;

    $stmt = sqlsrv_query($conn, $sql, $listParams);
    if ($stmt === false) {
        databaseError('Unable to fetch products.');
    }

    $products = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $prodId = (int)$row['ProductId'];
        $imagesList = getProductImages($conn, $prodId);
        $mainImageUrl = !empty($imagesList) ? $imagesList[0]['imageUrl'] : null;

        $products[] = [
            'productId'        => $prodId,
            'categoryId'       => (int)$row['CategoryId'],
            'categoryName'     => $row['CategoryName'],
            'name'             => $row['Name'],
            'slug'             => $row['Slug'],
            'shortDescription' => $row['ShortDescription'],
            'description'      => $row['Description'],
            'careInstructions' => $row['CareInstructions'],
            'specifications'   => $row['Specifications'],
            'basePrice'        => $row['BasePrice'] !== null ? (float)$row['BasePrice'] : null,
            'gstPercentage'    => $row['GstPercentage'] !== null ? (float)$row['GstPercentage'] : null,
            'pstPercentage'    => $row['PstPercentage'] !== null ? (float)$row['PstPercentage'] : null,
            'metaTitle'        => $row['MetaTitle'],
            'metaDescription'  => $row['MetaDescription'],
            'isFeatured'       => (bool)$row['IsFeatured'],
            'isNewArrival'     => (bool)$row['IsNewArrival'],
            'isBestSeller'     => (bool)$row['IsBestSeller'],
            'isActive'         => (bool)$row['IsActive'],
            'imageUrl'         => $mainImageUrl,
            'images'           => $imagesList,
            'createdAt'        => formatDateValue($row['CreatedAt']),
            'updatedAt'        => formatDateValue($row['UpdatedAt'])
        ];
    }

    $totalPages = $limit > 0 ? (int)ceil($total / $limit) : 0;

    response(
        true,
        'Products fetched successfully.',
        [
            'items' => $products,
            'pagination' => [
                'page'       => $page,
                'limit'      => $limit,
                'total'      => $total,
                'totalPages' => $totalPages
            ]
        ],
        200
    );
}

function createProduct($conn): void
{
    $data = getJsonInput();

    $categoryId = isset($data['categoryId']) ? (int)$data['categoryId'] : 0;
    $name = isset($data['name']) ? trim((string)$data['name']) : '';

    if ($categoryId <= 0) {
        response(false, 'categoryId is required.', null, 422);
    }

    if ($name === '') {
        response(false, 'Product name is required.', null, 422);
    }

    if (!categoryExists($conn, $categoryId)) {
        response(false, 'Selected category does not exist or is inactive.', null, 422);
    }

    $slug = isset($data['slug']) ? trim((string)$data['slug']) : '';
    if ($slug === '') {
        $slug = makeSlug($name);
    } else {
        $slug = makeSlug($slug);
    }

    $slug = getUniqueSlug($conn, $slug);

    $shortDescription = isset($data['shortDescription']) ? trim((string)$data['shortDescription']) : null;
    $description = isset($data['description']) ? trim((string)$data['description']) : null;
    $careInstructions = isset($data['careInstructions']) ? trim((string)$data['careInstructions']) : null;
    $specifications = isset($data['specifications']) ? trim((string)$data['specifications']) : null;

    $basePrice = isset($data['basePrice']) ? (float)$data['basePrice'] : 0;
    $gstPercentage = isset($data['gstPercentage']) ? (float)$data['gstPercentage'] : 0;
    $pstPercentage = isset($data['pstPercentage']) ? (float)$data['pstPercentage'] : 0;

    $metaTitle = isset($data['metaTitle']) ? trim((string)$data['metaTitle']) : null;
    $metaDescription = isset($data['metaDescription']) ? trim((string)$data['metaDescription']) : null;

    $isFeatured = boolValue($data['isFeatured'] ?? false);
    $isNewArrival = boolValue($data['isNewArrival'] ?? false);
    $isBestSeller = boolValue($data['isBestSeller'] ?? false);
    $isActive = boolValue($data['isActive'] ?? true);

    if ($basePrice < 0 || $gstPercentage < 0 || $pstPercentage < 0) {
        response(false, 'Price and tax percentages cannot be negative.', null, 422);
    }

    $sql = "
        INSERT INTO dbo.Products
        (
            CategoryId, Name, Slug, ShortDescription, Description, CareInstructions,
            Specifications, BasePrice, GstPercentage, PstPercentage, MetaTitle,
            MetaDescription, IsFeatured, IsNewArrival, IsBestSeller, IsActive,
            CreatedAt, UpdatedAt
        )
        OUTPUT INSERTED.ProductId
        VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE(), GETDATE())
    ";

    $params = [
        $categoryId,
        $name,
        $slug,
        $shortDescription,
        $description,
        $careInstructions,
        $specifications,
        $basePrice,
        $gstPercentage,
        $pstPercentage,
        $metaTitle,
        $metaDescription,
        $isFeatured ? 1 : 0,
        $isNewArrival ? 1 : 0,
        $isBestSeller ? 1 : 0,
        $isActive ? 1 : 0
    ];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        databaseError('Unable to create product.');
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $productId = (int)$row['ProductId'];

    $product = getProduct($conn, $productId, true);

    response(true, 'Product created successfully.', $product, 201);
}

function updateProduct($conn, int $productId): void
{
    $existing = getProduct($conn, $productId, false);
    if ($existing === null) {
        response(false, 'Product not found.', null, 404);
    }

    $data = getJsonInput();

    $categoryId = array_key_exists('categoryId', $data) ? (int)$data['categoryId'] : (int)$existing['categoryId'];
    if ($categoryId <= 0 || !categoryExists($conn, $categoryId)) {
        response(false, 'Invalid or non-existent categoryId.', null, 422);
    }

    $name = array_key_exists('name', $data) ? trim((string)$data['name']) : $existing['name'];
    if ($name === '') {
        response(false, 'Product name is required.', null, 422);
    }

    $slug = array_key_exists('slug', $data) ? trim((string)$data['slug']) : $existing['slug'];
    $slug = $slug === '' ? makeSlug($name) : makeSlug($slug);
    $slug = getUniqueSlug($conn, $slug, $productId);

    $shortDescription = array_key_exists('shortDescription', $data) ? trim((string)$data['shortDescription']) : $existing['shortDescription'];
    $description = array_key_exists('description', $data) ? trim((string)$data['description']) : $existing['description'];
    $careInstructions = array_key_exists('careInstructions', $data) ? trim((string)$data['careInstructions']) : $existing['careInstructions'];
    $specifications = array_key_exists('specifications', $data) ? trim((string)$data['specifications']) : $existing['specifications'];

    $basePrice = array_key_exists('basePrice', $data) ? (float)$data['basePrice'] : (float)$existing['basePrice'];
    $gstPercentage = array_key_exists('gstPercentage', $data) ? (float)$data['gstPercentage'] : (float)$existing['gstPercentage'];
    $pstPercentage = array_key_exists('pstPercentage', $data) ? (float)$data['pstPercentage'] : (float)$existing['pstPercentage'];

    $metaTitle = array_key_exists('metaTitle', $data) ? trim((string)$data['metaTitle']) : $existing['metaTitle'];
    $metaDescription = array_key_exists('metaDescription', $data) ? trim((string)$data['metaDescription']) : $existing['metaDescription'];

    $isFeatured = array_key_exists('isFeatured', $data) ? boolValue($data['isFeatured']) : $existing['isFeatured'];
    $isNewArrival = array_key_exists('isNewArrival', $data) ? boolValue($data['isNewArrival']) : $existing['isNewArrival'];
    $isBestSeller = array_key_exists('isBestSeller', $data) ? boolValue($data['isBestSeller']) : $existing['isBestSeller'];
    $isActive = array_key_exists('isActive', $data) ? boolValue($data['isActive']) : $existing['isActive'];

    $sql = "
        UPDATE dbo.Products
        SET
            CategoryId = ?, Name = ?, Slug = ?, ShortDescription = ?, Description = ?,
            CareInstructions = ?, Specifications = ?, BasePrice = ?, GstPercentage = ?,
            PstPercentage = ?, MetaTitle = ?, MetaDescription = ?, IsFeatured = ?,
            IsNewArrival = ?, IsBestSeller = ?, IsActive = ?, UpdatedAt = GETDATE()
        WHERE ProductId = ?
    ";

    $params = [
        $categoryId,
        $name,
        $slug,
        $shortDescription,
        $description,
        $careInstructions,
        $specifications,
        $basePrice,
        $gstPercentage,
        $pstPercentage,
        $metaTitle,
        $metaDescription,
        $isFeatured ? 1 : 0,
        $isNewArrival ? 1 : 0,
        $isBestSeller ? 1 : 0,
        $isActive ? 1 : 0,
        $productId
    ];

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        databaseError('Unable to update product.');
    }

    $product = getProduct($conn, $productId, true);

    response(true, 'Product updated successfully.', $product, 200);
}

function deleteProduct($conn, int $productId): void
{
    $existing = getProduct($conn, $productId, false);
    if ($existing === null) {
        response(false, 'Product not found.', null, 404);
    }

    $sql = "DELETE FROM dbo.Products WHERE ProductId = ?";
    $stmt = sqlsrv_query($conn, $sql, [$productId]);

    if ($stmt === false) {
        databaseError('Unable to delete product because related records exist.');
    }

    response(true, 'Product deleted successfully.', ['productId' => $productId], 200);
}

try {
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

    switch ($method) {
        case 'GET':
            showProducts($conn);
            break;
        case 'POST':
            createProduct($conn);
            break;
        case 'PUT':
            $productId = getProductId();
            if ($productId === null) {
                response(false, 'Product ID is required for update.', null, 400);
            }
            updateProduct($conn, $productId);
            break;
        case 'DELETE':
            $productId = getProductId();
            if ($productId === null) {
                response(false, 'Product ID is required for delete.', null, 400);
            }
            deleteProduct($conn, $productId);
            break;
        default:
            response(false, 'Method not allowed.', null, 405);
    }
} catch (Throwable $e) {
    response(false, 'Unexpected server error.', ['error' => $e->getMessage()], 500);
}
