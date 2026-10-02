<?php

session_start();

/*
|--------------------------------------------------------------------------
| ADMIN LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$activeMenu = 'products';
$pageTitle  = 'GatewayLinen | Edit Product';

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] =
        $_SESSION['admin_username'] ?? 'GatewayLinen Administrator';
}

if (!isset($_SESSION['admin_role'])) {
    $_SESSION['admin_role'] = 'Administrator';
}

/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function e($value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function slugify(string $text): string
{
    $text = trim($text);

    if ($text === '') {
        return '';
    }

    $text = strtolower($text);

    $text = preg_replace(
        '/[^a-z0-9]+/',
        '-',
        $text
    );

    return trim($text, '-');
}

function uploadDirectory(): string
{
    $dir =
        dirname(__DIR__) .
        DIRECTORY_SEPARATOR .
        'uploads' .
        DIRECTORY_SEPARATOR .
        'products';

    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    return $dir;
}

function imageWebPath(string $file): string
{
    $file = trim($file);

    if ($file === '') {
        return '';
    }

    if (
        preg_match(
            '~^(https?:)?//|^data:image/~i',
            $file
        )
    ) {
        return $file;
    }

    $file = str_replace('\\', '/', $file);

    $basename = basename(
        parse_url(
            $file,
            PHP_URL_PATH
        ) ?: $file
    );

    if (
        $basename === '' ||
        $basename === '.' ||
        $basename === '..'
    ) {
        return '';
    }

    $script = str_replace(
        '\\',
        '/',
        $_SERVER['SCRIPT_NAME'] ?? '/products/edit.php'
    );

    $appRoot = dirname(dirname($script));

    $appRoot = trim(
        str_replace('\\', '/', $appRoot),
        '/'
    );

    $prefix =
        ($appRoot === '' || $appRoot === '.')
            ? ''
            : '/' . $appRoot;

    return $prefix .
        '/uploads/products/' .
        rawurlencode($basename);
}

function deleteProductFile(string $imagePath): void
{
    $imagePath = trim($imagePath);

    if ($imagePath === '') {
        return;
    }

    /*
     * Never delete remote/data images.
     */
    if (
        preg_match(
            '~^(https?:)?//|^data:image/~i',
            $imagePath
        )
    ) {
        return;
    }

    $imagePath = str_replace(
        '\\',
        '/',
        $imagePath
    );

    $basename = basename(
        parse_url(
            $imagePath,
            PHP_URL_PATH
        ) ?: $imagePath
    );

    if (
        $basename === '' ||
        $basename === '.' ||
        $basename === '..'
    ) {
        return;
    }

    $baseDir = realpath(
        uploadDirectory()
    );

    if ($baseDir === false) {
        return;
    }

    $fullPath =
        $baseDir .
        DIRECTORY_SEPARATOR .
        $basename;

    if (
        is_file($fullPath) &&
        realpath(dirname($fullPath)) === $baseDir
    ) {
        @unlink($fullPath);
    }
}

function validateUploadedImage(
    array $file,
    int $maxSize = 5242880
): array {

    if (
        !isset($file['error']) ||
        !isset($file['tmp_name'])
    ) {
        return [
            'success' => false,
            'message' => 'Invalid uploaded image.'
        ];
    }

    if (
        $file['error'] === UPLOAD_ERR_NO_FILE
    ) {
        return [
            'success' => true,
            'empty' => true
        ];
    }

    if (
        $file['error'] !== UPLOAD_ERR_OK
    ) {
        return [
            'success' => false,
            'message' => 'Image upload failed.'
        ];
    }

    if (
        !is_uploaded_file(
            $file['tmp_name']
        )
    ) {
        return [
            'success' => false,
            'message' => 'Invalid uploaded file.'
        ];
    }

    if (
        (int)$file['size'] > $maxSize
    ) {
        return [
            'success' => false,
            'message' => 'Image size must be 5 MB or less.'
        ];
    }

    $finfo = new finfo(
        FILEINFO_MIME_TYPE
    );

    $mime = $finfo->file(
        $file['tmp_name']
    );

    $allowedMime = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif'
    ];

    if (
        !isset($allowedMime[$mime])
    ) {
        return [
            'success' => false,
            'message' =>
                'Only JPG, PNG, WEBP or GIF images are allowed.'
        ];
    }

    return [
        'success' => true,
        'empty'   => false,
        'mime'    => $mime,
        'extension' => $allowedMime[$mime]
    ];
}

/*
|--------------------------------------------------------------------------
| GET PRODUCT ID
|--------------------------------------------------------------------------
*/

$productId = (int)(
    $_GET['id'] ??
    $_POST['product_id'] ??
    0
);

if ($productId <= 0) {
    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| LOAD PRODUCT
|--------------------------------------------------------------------------
*/

$loadSql = "
    SELECT *
    FROM dbo.Products
    WHERE ProductId = ?
";

$loadStmt = sqlsrv_query(
    $conn,
    $loadSql,
    [$productId]
);

if ($loadStmt === false) {
    die('Unable to load product.');
}

$product = sqlsrv_fetch_array(
    $loadStmt,
    SQLSRV_FETCH_ASSOC
);

sqlsrv_free_stmt($loadStmt);

if (!$product) {
    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| LOAD PRODUCT IMAGES
|--------------------------------------------------------------------------
*/

$imagesSql = "
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

$imagesStmt = sqlsrv_query(
    $conn,
    $imagesSql,
    [$productId]
);

$existingImages = [];

if ($imagesStmt !== false) {

    while (
        $imgRow =
        sqlsrv_fetch_array(
            $imagesStmt,
            SQLSRV_FETCH_ASSOC
        )
    ) {
        $existingImages[] = $imgRow;
    }

    sqlsrv_free_stmt($imagesStmt);
}

/*
|--------------------------------------------------------------------------
| LOAD CATEGORIES
|--------------------------------------------------------------------------
*/

$catSql = "
    SELECT
        CategoryId,
        ParentCategoryId,
        Name
    FROM dbo.Categories
    WHERE IsActive = 1
    ORDER BY
        Name ASC
";

$catStmt = sqlsrv_query(
    $conn,
    $catSql
);

$categoriesList = [];

if ($catStmt !== false) {

    while (
        $crow =
        sqlsrv_fetch_array(
            $catStmt,
            SQLSRV_FETCH_ASSOC
        )
    ) {
        $categoriesList[] = $crow;
    }

    sqlsrv_free_stmt($catStmt);
}

/*
|--------------------------------------------------------------------------
| DEFAULT PRODUCT VALUES
|--------------------------------------------------------------------------
*/

$categoryId =
    (int)($product['CategoryId'] ?? 0);

$name =
    (string)($product['Name'] ?? '');

$slug =
    (string)($product['Slug'] ?? '');

$shortDescription =
    (string)($product['ShortDescription'] ?? '');

$description =
    (string)($product['Description'] ?? '');

$specifications =
    (string)($product['Specifications'] ?? '');

$careInstructions =
    (string)($product['CareInstructions'] ?? '');

$basePrice =
    (string)($product['BasePrice'] ?? '0.00');

$gstPercentage =
    (string)($product['GstPercentage'] ?? '5.00');

$pstPercentage =
    (string)($product['PstPercentage'] ?? '7.00');

$metaTitle =
    (string)($product['MetaTitle'] ?? '');

$metaDescription =
    (string)($product['MetaDescription'] ?? '');

$isActive =
    !empty($product['IsActive']);

$isFeatured =
    !empty($product['IsFeatured']);

$isNewArrival =
    !empty($product['IsNewArrival']);

$isBestSeller =
    !empty($product['IsBestSeller']);

/*
|--------------------------------------------------------------------------
| TAX TYPE
|--------------------------------------------------------------------------
*/

$taxType = 'custom';

if (
    (float)$gstPercentage === 5.00 &&
    (float)$pstPercentage === 7.00
) {
    $taxType = 'both';

} elseif (
    (float)$gstPercentage === 5.00 &&
    (float)$pstPercentage === 0.00
) {
    $taxType = 'gst_only';

} elseif (
    (float)$gstPercentage === 0.00 &&
    (float)$pstPercentage === 7.00
) {
    $taxType = 'pst_only';

} elseif (
    (float)$gstPercentage === 0.00 &&
    (float)$pstPercentage === 0.00
) {
    $taxType = 'none';
}

$errorMessage = '';

/*
|--------------------------------------------------------------------------
| UPDATE PRODUCT
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'update_product'
) {

    /*
     * CSRF
     */

    $postedToken =
        (string)($_POST['csrf_token'] ?? '');

    if (
        !hash_equals(
            $_SESSION['csrf_token'],
            $postedToken
        )
    ) {

        $errorMessage =
            'Security verification failed. Please refresh the page and try again.';

    } else {

        /*
         * INPUT
         */

        $categoryId =
            (int)($_POST['category_id'] ?? 0);

        $name =
            trim(
                (string)($_POST['name'] ?? '')
            );

        $slug =
            trim(
                (string)($_POST['slug'] ?? '')
            );

        $shortDescription =
            trim(
                (string)(
                    $_POST['short_description'] ?? ''
                )
            );

        $description =
            trim(
                (string)(
                    $_POST['description'] ?? ''
                )
            );

        $specifications =
            trim(
                (string)(
                    $_POST['specifications'] ?? ''
                )
            );

        $careInstructions =
            trim(
                (string)(
                    $_POST['care_instructions'] ?? ''
                )
            );

        $basePrice =
            trim(
                (string)(
                    $_POST['base_price'] ?? '0.00'
                )
            );

        $taxType =
            trim(
                (string)(
                    $_POST['tax_type'] ?? 'both'
                )
            );

        $metaTitle =
            trim(
                (string)(
                    $_POST['meta_title'] ?? ''
                )
            );

        $metaDescription =
            trim(
                (string)(
                    $_POST['meta_description'] ?? ''
                )
            );

        $isActive =
            isset($_POST['is_active']) &&
            $_POST['is_active'] === '1';

        $isFeatured =
            isset($_POST['is_featured']) &&
            $_POST['is_featured'] === '1';

        $isNewArrival =
            isset($_POST['is_new_arrival']) &&
            $_POST['is_new_arrival'] === '1';

        $isBestSeller =
            isset($_POST['is_best_seller']) &&
            $_POST['is_best_seller'] === '1';

        $mainImageId =
            (int)($_POST['main_image_id'] ?? 0);

        $deleteImages =
            $_POST['delete_images'] ?? [];

        /*
         * TAX
         */

        if ($taxType === 'gst_only') {

            $gstPercentage =
                trim(
                    (string)(
                        $_POST['gst_percentage'] ?? '5.00'
                    )
                );

            $pstPercentage = '0.00';

        } elseif ($taxType === 'pst_only') {

            $gstPercentage = '0.00';

            $pstPercentage =
                trim(
                    (string)(
                        $_POST['pst_percentage'] ?? '7.00'
                    )
                );

        } elseif ($taxType === 'none') {

            $gstPercentage = '0.00';
            $pstPercentage = '0.00';

        } else {

            $gstPercentage =
                trim(
                    (string)(
                        $_POST['gst_percentage'] ?? '5.00'
                    )
                );

            $pstPercentage =
                trim(
                    (string)(
                        $_POST['pst_percentage'] ?? '7.00'
                    )
                );
        }

        /*
         * AUTO SLUG
         */

        if ($slug === '') {
            $slug = slugify($name);
        } else {
            $slug = slugify($slug);
        }

        /*
         * VALIDATION
         */

        if ($categoryId <= 0) {

            $errorMessage =
                'Please select a valid category.';

        } elseif ($name === '') {

            $errorMessage =
                'Product name is required.';

        } elseif (strlen($name) > 255) {

            $errorMessage =
                'Product name cannot exceed 255 characters.';

        } elseif ($slug === '') {

            $errorMessage =
                'A valid slug could not be generated.';

        } elseif (strlen($slug) > 255) {

            $errorMessage =
                'Slug cannot exceed 255 characters.';

        } elseif (
            !is_numeric($basePrice) ||
            (float)$basePrice < 0
        ) {

            $errorMessage =
                'Please enter a valid base price.';

        } elseif (
            !is_numeric($gstPercentage) ||
            (float)$gstPercentage < 0 ||
            (float)$gstPercentage > 100
        ) {

            $errorMessage =
                'GST percentage must be between 0 and 100.';

        } elseif (
            !is_numeric($pstPercentage) ||
            (float)$pstPercentage < 0 ||
            (float)$pstPercentage > 100
        ) {

            $errorMessage =
                'PST percentage must be between 0 and 100.';
        }

        /*
         * CHECK CATEGORY EXISTS
         */

        if ($errorMessage === '') {

            $categoryCheckSql = "
                SELECT TOP 1 CategoryId
                FROM dbo.Categories
                WHERE
                    CategoryId = ?
                    AND IsActive = 1
            ";

            $categoryCheckStmt =
                sqlsrv_query(
                    $conn,
                    $categoryCheckSql,
                    [$categoryId]
                );

            if (
                $categoryCheckStmt === false ||
                !sqlsrv_fetch_array(
                    $categoryCheckStmt,
                    SQLSRV_FETCH_ASSOC
                )
            ) {

                $errorMessage =
                    'Selected category does not exist or is inactive.';
            }

            if (
                $categoryCheckStmt !== false
            ) {
                sqlsrv_free_stmt(
                    $categoryCheckStmt
                );
            }
        }

        /*
         * DUPLICATE SLUG
         */

        if ($errorMessage === '') {

            $dupSql = "
                SELECT TOP 1 ProductId
                FROM dbo.Products
                WHERE
                    ProductId <> ?
                    AND Slug = ?
            ";

            $dupStmt =
                sqlsrv_query(
                    $conn,
                    $dupSql,
                    [
                        $productId,
                        $slug
                    ]
                );

            if ($dupStmt === false) {

                $errorMessage =
                    'Unable to validate product slug.';

            } else {

                if (
                    sqlsrv_fetch_array(
                        $dupStmt,
                        SQLSRV_FETCH_ASSOC
                    )
                ) {

                    $errorMessage =
                        'Another product already uses this slug. Please change it.';
                }

                sqlsrv_free_stmt($dupStmt);
            }
        }

        /*
         * NEW IMAGE VALIDATION
         */

        $allowedMime = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif'
        ];

        /*
         * TRANSACTION
         */

        if ($errorMessage === '') {

            if (
                sqlsrv_begin_transaction($conn) === false
            ) {

                $errorMessage =
                    'Unable to start database transaction.';

            } else {

                $transactionOk = true;

                /*
                 * UPDATE PRODUCT
                 */

                $updateSql = "
                    UPDATE dbo.Products
                    SET
                        CategoryId = ?,
                        Name = ?,
                        Slug = ?,
                        ShortDescription = ?,
                        Description = ?,
                        CareInstructions = ?,
                        Specifications = ?,
                        BasePrice = ?,
                        GstPercentage = ?,
                        PstPercentage = ?,
                        MetaTitle = ?,
                        MetaDescription = ?,
                        IsFeatured = ?,
                        IsNewArrival = ?,
                        IsBestSeller = ?,
                        IsActive = ?,
                        UpdatedAt = GETDATE()
                    WHERE ProductId = ?
                ";

                $updateParams = [
                    $categoryId,
                    $name,
                    $slug,

                    $shortDescription !== ''
                        ? $shortDescription
                        : null,

                    $description !== ''
                        ? $description
                        : null,

                    $careInstructions !== ''
                        ? $careInstructions
                        : null,

                    $specifications !== ''
                        ? $specifications
                        : null,

                    (float)$basePrice,
                    (float)$gstPercentage,
                    (float)$pstPercentage,

                    $metaTitle !== ''
                        ? $metaTitle
                        : null,

                    $metaDescription !== ''
                        ? $metaDescription
                        : null,

                    $isFeatured ? 1 : 0,
                    $isNewArrival ? 1 : 0,
                    $isBestSeller ? 1 : 0,
                    $isActive ? 1 : 0,

                    $productId
                ];

                $updateStmt =
                    sqlsrv_query(
                        $conn,
                        $updateSql,
                        $updateParams
                    );

                if ($updateStmt === false) {

                    $transactionOk = false;

                    $errorMessage =
                        'Unable to update product details. Database query error.';

                } else {

                    sqlsrv_free_stmt(
                        $updateStmt
                    );
                }

                /*
                 * DELETE SELECTED IMAGES
                 */

                if (
                    $transactionOk &&
                    is_array($deleteImages) &&
                    !empty($deleteImages)
                ) {

                    foreach (
                        $deleteImages as $delImgId
                    ) {

                        $delImgId =
                            (int)$delImgId;

                        if ($delImgId <= 0) {
                            continue;
                        }

                        /*
                         * Do not delete the selected main
                         * image without selecting another one.
                         * The main image correction below will
                         * handle the remaining gallery.
                         */

                        $findSql = "
                            SELECT ImageUrl
                            FROM dbo.ProductImages
                            WHERE
                                ImageId = ?
                                AND ProductId = ?
                        ";

                        $findStmt =
                            sqlsrv_query(
                                $conn,
                                $findSql,
                                [
                                    $delImgId,
                                    $productId
                                ]
                            );

                        $oldImageUrl = '';

                        if ($findStmt !== false) {

                            $fRow =
                                sqlsrv_fetch_array(
                                    $findStmt,
                                    SQLSRV_FETCH_ASSOC
                                );

                            if ($fRow) {
                                $oldImageUrl =
                                    (string)(
                                        $fRow['ImageUrl'] ?? ''
                                    );
                            }

                            sqlsrv_free_stmt(
                                $findStmt
                            );
                        }

                        $delStmt = sqlsrv_query(
                            $conn,
                            "
                                DELETE FROM dbo.ProductImages
                                WHERE
                                    ImageId = ?
                                    AND ProductId = ?
                            ",
                            [
                                $delImgId,
                                $productId
                            ]
                        );

                        if ($delStmt === false) {

                            $transactionOk = false;

                            $errorMessage =
                                'Unable to delete selected product image.';

                            break;
                        }

                        sqlsrv_free_stmt(
                            $delStmt
                        );

                        if ($oldImageUrl !== '') {
                            deleteProductFile(
                                $oldImageUrl
                            );
                        }
                    }
                }

                /*
                 * REPLACE EXISTING IMAGES
                 */

                if (
                    $transactionOk &&
                    isset($_FILES['replace_image']) &&
                    is_array(
                        $_FILES['replace_image']['name'] ?? null
                    )
                ) {

                    foreach (
                        $_FILES['replace_image']['name']
                        as $imgIdKey => $imgFileName
                    ) {

                        $imgIdKey =
                            (int)$imgIdKey;

                        if ($imgIdKey <= 0) {
                            continue;
                        }

                        $fileData = [
                            'name' =>
                                $_FILES['replace_image']['name'][$imgIdKey] ?? '',

                            'type' =>
                                $_FILES['replace_image']['type'][$imgIdKey] ?? '',

                            'tmp_name' =>
                                $_FILES['replace_image']['tmp_name'][$imgIdKey] ?? '',

                            'error' =>
                                $_FILES['replace_image']['error'][$imgIdKey] ?? UPLOAD_ERR_NO_FILE,

                            'size' =>
                                $_FILES['replace_image']['size'][$imgIdKey] ?? 0
                        ];

                        if (
                            $fileData['error'] ===
                            UPLOAD_ERR_NO_FILE
                        ) {
                            continue;
                        }

                        $imageValidation =
                            validateUploadedImage(
                                $fileData
                            );

                        if (
                            !$imageValidation['success']
                        ) {

                            $transactionOk = false;

                            $errorMessage =
                                $imageValidation['message'];

                            break;
                        }

                        $extension =
                            $imageValidation['extension'];

                        $newFile =
                            'prod_' .
                            date('Ymd_His') .
                            '_rep_' .
                            $imgIdKey .
                            '_' .
                            bin2hex(
                                random_bytes(4)
                            ) .
                            '.' .
                            $extension;

                        $destination =
                            uploadDirectory() .
                            DIRECTORY_SEPARATOR .
                            $newFile;

                        if (
                            !move_uploaded_file(
                                $fileData['tmp_name'],
                                $destination
                            )
                        ) {

                            $transactionOk = false;

                            $errorMessage =
                                'Unable to save replacement image.';

                            break;
                        }

                        /*
                         * Get old image
                         */

                        $oldSql = "
                            SELECT ImageUrl
                            FROM dbo.ProductImages
                            WHERE
                                ImageId = ?
                                AND ProductId = ?
                        ";

                        $oldStmt =
                            sqlsrv_query(
                                $conn,
                                $oldSql,
                                [
                                    $imgIdKey,
                                    $productId
                                ]
                            );

                        $oldImage =
                            '';

                        if (
                            $oldStmt !== false
                        ) {

                            $oldRow =
                                sqlsrv_fetch_array(
                                    $oldStmt,
                                    SQLSRV_FETCH_ASSOC
                                );

                            if ($oldRow) {
                                $oldImage =
                                    (string)(
                                        $oldRow['ImageUrl'] ?? ''
                                    );
                            }

                            sqlsrv_free_stmt(
                                $oldStmt
                            );
                        }

                        $newDbPath =
                            'uploads/products/' .
                            $newFile;

                        $replaceSql = "
                            UPDATE dbo.ProductImages
                            SET
                                ImageUrl = ?,
                                AltText = ?
                            WHERE
                                ImageId = ?
                                AND ProductId = ?
                        ";

                        $replaceStmt =
                            sqlsrv_query(
                                $conn,
                                $replaceSql,
                                [
                                    $newDbPath,
                                    $name,
                                    $imgIdKey,
                                    $productId
                                ]
                            );

                        if (
                            $replaceStmt === false
                        ) {

                            @unlink(
                                $destination
                            );

                            $transactionOk = false;

                            $errorMessage =
                                'Unable to update replacement image.';

                            break;
                        }

                        sqlsrv_free_stmt(
                            $replaceStmt
                        );

                        /*
                         * Delete old file only after DB update.
                         */

                        if (
                            $oldImage !== '' &&
                            $oldImage !== $newDbPath
                        ) {

                            deleteProductFile(
                                $oldImage
                            );
                        }
                    }
                }

                /*
                 * ADD NEW IMAGES
                 */

                if (
                    $transactionOk &&
                    isset(
                        $_FILES['new_product_images']
                    ) &&
                    is_array(
                        $_FILES['new_product_images']['name'] ?? null
                    )
                ) {

                    $newFiles =
                        $_FILES['new_product_images'];

                    $count =
                        count(
                            $newFiles['name']
                        );

                    for (
                        $i = 0;
                        $i < $count;
                        $i++
                    ) {

                        if (
                            ($newFiles['error'][$i] ?? UPLOAD_ERR_NO_FILE)
                            === UPLOAD_ERR_NO_FILE
                        ) {
                            continue;
                        }

                        $fileData = [
                            'name' =>
                                $newFiles['name'][$i] ?? '',

                            'type' =>
                                $newFiles['type'][$i] ?? '',

                            'tmp_name' =>
                                $newFiles['tmp_name'][$i] ?? '',

                            'error' =>
                                $newFiles['error'][$i] ?? UPLOAD_ERR_NO_FILE,

                            'size' =>
                                $newFiles['size'][$i] ?? 0
                        ];

                        $imageValidation =
                            validateUploadedImage(
                                $fileData
                            );

                        if (
                            !$imageValidation['success']
                        ) {

                            $transactionOk = false;

                            $errorMessage =
                                $imageValidation['message'];

                            break;
                        }

                        $extension =
                            $imageValidation['extension'];

                        $newFileName =
                            'prod_' .
                            date('Ymd_His') .
                            '_new_' .
                            $i .
                            '_' .
                            bin2hex(
                                random_bytes(4)
                            ) .
                            '.' .
                            $extension;

                        $destPath =
                            uploadDirectory() .
                            DIRECTORY_SEPARATOR .
                            $newFileName;

                        if (
                            !move_uploaded_file(
                                $fileData['tmp_name'],
                                $destPath
                            )
                        ) {

                            $transactionOk = false;

                            $errorMessage =
                                'Unable to save new product image.';

                            break;
                        }

                        $dbPath =
                            'uploads/products/' .
                            $newFileName;

                        /*
                         * Get next display order.
                         */

                        $orderSql = "
                            SELECT
                                ISNULL(
                                    MAX(DisplayOrder),
                                    0
                                ) + 1 AS NextOrder
                            FROM dbo.ProductImages
                            WHERE ProductId = ?
                        ";

                        $orderStmt =
                            sqlsrv_query(
                                $conn,
                                $orderSql,
                                [$productId]
                            );

                        $nextOrder = 1;

                        if (
                            $orderStmt !== false
                        ) {

                            $orderRow =
                                sqlsrv_fetch_array(
                                    $orderStmt,
                                    SQLSRV_FETCH_ASSOC
                                );

                            $nextOrder =
                                (int)(
                                    $orderRow['NextOrder']
                                    ?? 1
                                );

                            sqlsrv_free_stmt(
                                $orderStmt
                            );
                        }

                        $insSql = "
                            INSERT INTO dbo.ProductImages
                            (
                                ProductId,
                                VariantId,
                                ImageUrl,
                                AltText,
                                IsMain,
                                DisplayOrder
                            )
                            VALUES
                            (
                                ?,
                                NULL,
                                ?,
                                ?,
                                0,
                                ?
                            )
                        ";

                        $insStmt =
                            sqlsrv_query(
                                $conn,
                                $insSql,
                                [
                                    $productId,
                                    $dbPath,
                                    $name,
                                    $nextOrder
                                ]
                            );

                        if (
                            $insStmt === false
                        ) {

                            @unlink(
                                $destPath
                            );

                            $transactionOk = false;

                            $errorMessage =
                                'Unable to add new product image.';

                            break;
                        }

                        sqlsrv_free_stmt(
                            $insStmt
                        );
                    }
                }

                /*
                 * MAIN IMAGE
                 */

                if (
                    $transactionOk &&
                    $mainImageId > 0
                ) {

                    /*
                     * Verify selected image belongs
                     * to this product and was not deleted.
                     */

                    $verifyMainSql = "
                        SELECT TOP 1 ImageId
                        FROM dbo.ProductImages
                        WHERE
                            ImageId = ?
                            AND ProductId = ?
                    ";

                    $verifyMainStmt =
                        sqlsrv_query(
                            $conn,
                            $verifyMainSql,
                            [
                                $mainImageId,
                                $productId
                            ]
                        );

                    $validMain =
                        false;

                    if (
                        $verifyMainStmt !== false
                    ) {

                        $validMain =
                            (bool)sqlsrv_fetch_array(
                                $verifyMainStmt,
                                SQLSRV_FETCH_ASSOC
                            );

                        sqlsrv_free_stmt(
                            $verifyMainStmt
                        );
                    }

                    if ($validMain) {

                        $resetMainStmt =
                            sqlsrv_query(
                                $conn,
                                "
                                    UPDATE dbo.ProductImages
                                    SET IsMain = 0
                                    WHERE ProductId = ?
                                ",
                                [$productId]
                            );

                        if (
                            $resetMainStmt === false
                        ) {

                            $transactionOk = false;

                            $errorMessage =
                                'Unable to update main image.';
                        } else {

                            sqlsrv_free_stmt(
                                $resetMainStmt
                            );
                        }

                        if ($transactionOk) {

                            $setMainStmt =
                                sqlsrv_query(
                                    $conn,
                                    "
                                        UPDATE dbo.ProductImages
                                        SET IsMain = 1
                                        WHERE
                                            ImageId = ?
                                            AND ProductId = ?
                                    ",
                                    [
                                        $mainImageId,
                                        $productId
                                    ]
                                );

                            if (
                                $setMainStmt === false
                            ) {

                                $transactionOk = false;

                                $errorMessage =
                                    'Unable to set main image.';

                            } else {

                                sqlsrv_free_stmt(
                                    $setMainStmt
                                );
                            }
                        }
                    }
                }

                /*
                 * ENSURE AT LEAST ONE MAIN IMAGE
                 */

                if ($transactionOk) {

                    $checkMain =
                        sqlsrv_query(
                            $conn,
                            "
                                SELECT COUNT(*) AS MainCount
                                FROM dbo.ProductImages
                                WHERE
                                    ProductId = ?
                                    AND IsMain = 1
                            ",
                            [$productId]
                        );

                    $mainCount = 0;

                    if (
                        $checkMain !== false
                    ) {

                        $mRow =
                            sqlsrv_fetch_array(
                                $checkMain,
                                SQLSRV_FETCH_ASSOC
                            );

                        $mainCount =
                            (int)(
                                $mRow['MainCount'] ?? 0
                            );

                        sqlsrv_free_stmt(
                            $checkMain
                        );
                    }

                    if ($mainCount === 0) {

                        $setFirstMain =
                            sqlsrv_query(
                                $conn,
                                "
                                    UPDATE TOP (1)
                                        dbo.ProductImages
                                    SET IsMain = 1
                                    WHERE ProductId = ?
                                    ORDER BY DisplayOrder ASC, ImageId ASC
                                ",
                                [$productId]
                            );

                        /*
                         * SQL Server does not support ORDER BY
                         * directly in this UPDATE TOP syntax in
                         * every configuration. Fallback below.
                         */

                        if (
                            $setFirstMain === false
                        ) {

                            $fallbackMain =
                                sqlsrv_query(
                                    $conn,
                                    "
                                        DECLARE @FirstImageId INT;

                                        SELECT TOP 1
                                            @FirstImageId = ImageId
                                        FROM dbo.ProductImages
                                        WHERE ProductId = ?
                                        ORDER BY
                                            DisplayOrder ASC,
                                            ImageId ASC;

                                        IF @FirstImageId IS NOT NULL
                                        BEGIN
                                            UPDATE dbo.ProductImages
                                            SET IsMain = 1
                                            WHERE ImageId = @FirstImageId;
                                        END
                                    ",
                                    [$productId]
                                );

                            if (
                                $fallbackMain === false
                            ) {

                                $transactionOk = false;

                                $errorMessage =
                                    'Unable to assign main product image.';

                            } else {

                                sqlsrv_free_stmt(
                                    $fallbackMain
                                );
                            }

                        } else {

                            sqlsrv_free_stmt(
                                $setFirstMain
                            );
                        }
                    }
                }

                /*
                 * COMMIT / ROLLBACK
                 */

                if ($transactionOk) {

                    if (
                        sqlsrv_commit($conn) === false
                    ) {

                        sqlsrv_rollback($conn);

                        $errorMessage =
                            'Product update could not be completed.';
                    } else {

                        header(
                            'Location: index.php?success=' .
                            urlencode(
                                'Product "' .
                                $name .
                                '" updated successfully.'
                            )
                        );

                        exit;
                    }

                } else {

                    sqlsrv_rollback($conn);
                }
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| CURRENT VALUES FOR DISPLAY
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<style>

/* ============================================================
   GATEWAYLINEN
   PRODUCT EDIT
   UNIFIED LIGHT + DARK THEME
   ============================================================ */

:root {

    --bg-page: #f5f7fa;
    --bg-card: #ffffff;
    --bg-card-alt: #f8fafc;
    --bg-input: #ffffff;

    --border: #d9e1ea;
    --border-soft: #e7edf3;

    --text-hi: #17212b;
    --text-body: #344454;
    --text-mute: #687789;

    --green: #10b981;
    --green-dark: #059669;
    --green-soft: #ecfdf5;

    --red: #dc2626;
    --red-soft: #fef2f2;

    --blue: #2563eb;
    --blue-soft: #eff6ff;

    --radius: 12px;
}

/* ============================================================
   DARK MODE
   ============================================================ */

body.dark-mode,
body[data-theme="dark"],
body.dark,
.dark-mode,
[data-theme="dark"],
html.dark {

    --bg-page: #0f172a;
    --bg-card: #1e293b;
    --bg-card-alt: #172235;
    --bg-input: #1e293b;

    --border: #334155;
    --border-soft: #273548;

    --text-hi: #f8fafc;
    --text-body: #cbd5e1;
    --text-mute: #94a3b8;

    --green-soft: rgba(16,185,129,.15);

    --red-soft: rgba(220,38,38,.15);

    --blue-soft: rgba(37,99,235,.15);
}

/* ============================================================
   GLOBAL
   ============================================================ */

* {
    box-sizing: border-box;
}

html,
body,
.main,
.content {

    background: var(--bg-page) !important;
    color: var(--text-body) !important;
}

/* ============================================================
   PAGE
   ============================================================ */

.product-edit-page {

    width: 100%;
    max-width: 1350px;

    margin: 0 auto;

    padding: 8px 0 35px;
}

/* ============================================================
   HEADER
   ============================================================ */

.page-header {

    display: flex;

    align-items: flex-end;

    justify-content: space-between;

    gap: 24px;

    padding: 0 0 20px;

    margin-bottom: 22px;

    border-bottom: 1px solid var(--border);
}

.breadcrumb {

    display: flex;

    align-items: center;

    gap: 9px;

    margin-bottom: 10px;

    color: var(--text-mute);

    font-size: 12px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .55px;
}

.breadcrumb .current {

    color: var(--green);
}

.page-header h1 {

    margin: 0;

    color: var(--text-hi);

    font-size: 30px;

    line-height: 1.2;

    font-weight: 800;

    letter-spacing: -.3px;
}

.page-header p {

    margin: 8px 0 0;

    color: var(--text-mute);

    font-size: 14px;

    line-height: 1.5;
}

.header-actions {

    display: flex;

    gap: 10px;

    flex-wrap: wrap;
}

/* ============================================================
   BUTTONS
   ============================================================ */

.btn {

    min-height: 42px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    padding: 0 17px;

    border: 1px solid var(--border);

    border-radius: 8px;

    background: var(--bg-card);

    color: var(--text-body) !important;

    font-size: 13px;

    font-weight: 800;

    text-decoration: none;

    cursor: pointer;

    transition: .18s ease;

    white-space: nowrap;
}

.btn:hover {

    border-color: var(--green);

    background: var(--green-soft);

    color: var(--green) !important;
}

.btn-primary {

    border-color: transparent;

    background:
        linear-gradient(
            135deg,
            var(--green-dark),
            var(--green)
        );

    color: #ffffff !important;

    box-shadow:
        0 6px 16px
        rgba(16,185,129,.18);
}

.btn-primary:hover {

    color: #ffffff !important;

    transform: translateY(-1px);
}

.btn-shortcut {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-width: 30px;

    min-height: 22px;

    padding: 2px 7px;

    border: 1px solid var(--border);

    border-radius: 5px;

    background: var(--bg-input);

    color: var(--green);

    font-family:
        ui-monospace,
        SFMono-Regular,
        Menlo,
        Monaco,
        Consolas,
        monospace;

    font-size: 10px;

    line-height: 1;

    font-weight: 800;
}

.btn-primary .btn-shortcut {

    border-color:
        rgba(255,255,255,.28);

    background:
        rgba(255,255,255,.12);

    color: #ffffff;
}

/* ============================================================
   NOTICE
   ============================================================ */

.notice {

    margin-bottom: 18px;

    padding: 14px 16px;

    border-radius: 9px;

    font-size: 13px;

    line-height: 1.5;

    font-weight: 700;
}

.notice-error {

    border:
        1px solid
        rgba(220,38,38,.3);

    background: var(--red-soft);

    color: var(--red);
}

/* ============================================================
   LAYOUT
   ============================================================ */

.edit-layout {

    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        420px;

    gap: 18px;

    align-items: start;
}

/* ============================================================
   CARD
   ============================================================ */

.card {

    background: var(--bg-card);

    border:
        1px solid
        var(--border);

    border-radius: var(--radius);

    overflow: hidden;

    margin-bottom: 18px;

    box-shadow:
        0 3px 14px
        rgba(15,23,42,.045);
}

.card-header {

    padding: 18px 20px;

    background: var(--bg-card);

    border-bottom:
        1px solid
        var(--border-soft);
}

.card-header h2 {

    margin: 0;

    color: var(--text-hi);

    font-size: 18px;

    line-height: 1.35;

    font-weight: 800;
}

.card-header p {

    margin: 6px 0 0;

    color: var(--text-mute);

    font-size: 13px;

    line-height: 1.45;
}

.card-body {

    padding: 24px 22px;
}

/* ============================================================
   FORM
   ============================================================ */

.form-group {

    margin-bottom: 20px;
}

.form-group:last-child {

    margin-bottom: 0;
}

.form-label {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;

    margin-bottom: 8px;

    color: var(--text-hi);

    font-size: 13px;

    line-height: 1.4;

    font-weight: 800;
}

.required {

    color: var(--red);
}

.input,
.textarea,
.select {

    width: 100%;

    border:
        1px solid
        var(--border);

    border-radius: 8px;

    outline: none;

    background: var(--bg-input);

    color: var(--text-hi);

    font-family: inherit;

    font-size: 14px;

    font-weight: 500;

    transition: .18s ease;

    box-shadow:
        inset 0 1px 2px
        rgba(15,23,42,.025);
}

.input::placeholder,
.textarea::placeholder {

    color: var(--text-mute);
}

.input,
.select {

    height: 46px;

    padding: 0 14px;
}

.textarea {

    min-height: 145px;

    padding: 13px 14px;

    resize: vertical;

    line-height: 1.6;
}

.input:hover,
.textarea:hover,
.select:hover {

    border-color:
        var(--text-mute);
}

.input:focus,
.textarea:focus,
.select:focus {

    border-color:
        var(--green);

    box-shadow:
        0 0 0 3px
        rgba(16,185,129,.15);
}

.input:disabled,
.input:read-only {

    background:
        var(--bg-card-alt);

    color:
        var(--text-mute);
}

/* ============================================================
   SELECT
   ============================================================ */

.select {

    cursor: pointer;
}

body.dark-mode .select,
body[data-theme="dark"] .select,
body.dark .select,
.dark-mode .select,
[data-theme="dark"] .select,
html.dark .select {

    color-scheme: dark;
}

/* ============================================================
   SLUG
   ============================================================ */

.input-prefix {

    position: relative;
}

.input-prefix span {

    position: absolute;

    left: 14px;

    top: 50%;

    transform:
        translateY(-50%);

    color: var(--text-mute);

    font-size: 14px;

    font-weight: 700;

    pointer-events: none;
}

.input-prefix .input {

    padding-left: 30px;
}

.help-text {

    margin-top: 7px;

    color: var(--text-mute);

    font-size: 11px;

    line-height: 1.45;
}

/* ============================================================
   TWO COLUMNS
   ============================================================ */

.two-column {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 16px;
}

/* ============================================================
   TAX
   ============================================================ */

.tax-edit-btn {

    min-height: 26px;

    padding: 3px 9px;

    border:
        1px solid
        rgba(37,99,235,.25);

    border-radius: 6px;

    background:
        var(--blue-soft);

    color:
        var(--blue);

    cursor: pointer;

    font-size: 10px;

    font-weight: 800;

    transition: .18s;
}

.tax-edit-btn:hover {

    border-color:
        var(--blue);

    background:
        rgba(37,99,235,.12);
}

.tax-edit-btn.unlocked {

    border-color:
        rgba(16,185,129,.3);

    background:
        var(--green-soft);

    color:
        var(--green);
}

.tax-summary-box {

    margin-top: 15px;

    padding: 14px;

    border:
        1px solid
        rgba(16,185,129,.22);

    border-radius: 9px;

    background:
        var(--green-soft);
}

.tax-summary-row {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    margin-bottom: 7px;

    color: var(--text-body);

    font-size: 12px;
}

.tax-summary-row:last-child {

    margin-bottom: 0;

    padding-top: 8px;

    border-top:
        1px dashed
        rgba(16,185,129,.3);

    color: var(--text-hi);

    font-size: 13px;

    font-weight: 800;
}

/* ============================================================
   STATUS / FLAGS
   ============================================================ */

.flags-grid {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 12px;
}

.status-box {

    min-height: 50px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;

    padding: 10px 13px;

    border:
        1px solid
        var(--border);

    border-radius: 9px;

    background: var(--bg-input);
}

.status-info strong {

    display: block;

    color: var(--text-hi);

    font-size: 13px;

    font-weight: 800;
}

.status-info span {

    display: block;

    margin-top: 3px;

    color: var(--text-mute);

    font-size: 10px;
}

.switch {

    position: relative;

    width: 46px;

    height: 25px;

    flex-shrink: 0;
}

.switch input {

    opacity: 0;

    width: 0;

    height: 0;
}

.slider {

    position: absolute;

    inset: 0;

    cursor: pointer;

    border-radius: 30px;

    background:
        var(--border);

    transition: .2s;
}

.slider:before {

    content: "";

    position: absolute;

    width: 19px;

    height: 19px;

    left: 3px;

    top: 3px;

    border-radius: 50%;

    background: #ffffff;

    box-shadow:
        0 1px 3px
        rgba(15,23,42,.18);

    transition: .2s;
}

.switch input:checked + .slider {

    background:
        var(--green);
}

.switch input:checked + .slider:before {

    transform:
        translateX(21px);
}

/* ============================================================
   GALLERY
   ============================================================ */

.gallery-list {

    display: flex;

    flex-direction: column;

    gap: 12px;
}

.gallery-card-row {

    display: grid;

    grid-template-columns:
        100px
        minmax(0,1fr);

    gap: 14px;

    padding: 13px;

    background:
        var(--bg-input);

    border:
        1px solid
        var(--border-soft);

    border-radius: 10px;

    align-items: center;

    position: relative;

    transition: .18s;
}

.gallery-card-row:hover {

    border-color:
        var(--border);
}

.gallery-card-row.is-main-active {

    border-color:
        rgba(16,185,129,.45);

    background:
        var(--green-soft);
}

.gallery-card-img-wrap {

    position: relative;

    width: 100px;

    height: 100px;

    border-radius: 9px;

    overflow: hidden;

    border:
        1px solid
        var(--border);

    background:
        var(--bg-card-alt);
}

.gallery-card-img-wrap img {

    width: 100%;

    height: 100%;

    object-fit: cover;

    display: block;
}

.main-badge {

    position: absolute;

    top: 5px;

    left: 5px;

    z-index: 2;

    padding: 3px 6px;

    border-radius: 4px;

    background:
        var(--green);

    color: #ffffff;

    font-size: 8px;

    font-weight: 900;

    letter-spacing: .4px;
}

.gallery-card-controls {

    min-width: 0;

    display: flex;

    flex-direction: column;

    gap: 10px;
}

.gallery-card-actions {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;
}

.main-radio-label,
.del-checkbox-label {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    cursor: pointer;

    font-size: 12px;

    font-weight: 800;
}

.main-radio-label {

    color: var(--text-hi);
}

.del-checkbox-label {

    color: var(--red);
}

.main-radio-label input {

    accent-color:
        var(--green);

    cursor: pointer;
}

.del-checkbox-label input {

    accent-color:
        var(--red);

    cursor: pointer;
}

.replace-file-label {

    display: block;

    margin-bottom: 5px;

    color: var(--text-mute);

    font-size: 11px;

    font-weight: 700;
}

.replace-file-input {

    width: 100%;

    padding: 7px;

    border:
        1px dashed
        var(--border);

    border-radius: 7px;

    background:
        var(--bg-card);

    color:
        var(--text-body);

    font-size: 10px;

    cursor: pointer;
}

.replace-file-input:hover {

    border-color:
        var(--green);
}

.file-input-multiple {

    width: 100%;

    padding: 13px;

    border:
        1px dashed
        var(--border);

    border-radius: 8px;

    background:
        var(--bg-input);

    color:
        var(--text-body);

    font-size: 12px;

    cursor: pointer;
}

.file-input-multiple:hover {

    border-color:
        var(--green);

    background:
        var(--green-soft);
}

.empty-gallery {

    padding: 18px;

    border:
        1px dashed
        var(--border);

    border-radius: 9px;

    color:
        var(--text-mute);

    background:
        var(--bg-input);

    font-size: 12px;

    text-align: center;
}

/* ============================================================
   ACTION FOOTER
   ============================================================ */

.action-footer {

    display: flex;

    justify-content: flex-end;

    gap: 10px;

    padding: 17px 22px;

    background:
        var(--bg-card);

    border-top:
        1px solid
        var(--border-soft);
}

/* ============================================================
   SHORTCUTS
   ============================================================ */

.shortcut-box {

    padding: 16px 18px;

    border:
        1px solid
        var(--border);

    border-radius: 12px;

    background:
        var(--bg-card);
}

.shortcut-title {

    display: flex;

    align-items: center;

    gap: 8px;

    color:
        var(--text-hi);

    font-size: 13px;

    font-weight: 800;
}

.shortcut-grid {

    display: grid;

    grid-template-columns:
        repeat(3, minmax(0,1fr));

    gap: 9px;

    margin-top: 12px;
}

.shortcut {

    display: flex;

    align-items: center;

    gap: 8px;

    padding: 9px 10px;

    border:
        1px solid
        var(--border-soft);

    border-radius: 7px;

    background:
        var(--bg-input);
}

.key {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-width: 42px;

    height: 25px;

    padding: 0 7px;

    border:
        1px solid
        var(--border);

    border-radius: 5px;

    background:
        var(--bg-card-alt);

    color:
        var(--green);

    font-family: monospace;

    font-size: 9px;

    font-weight: 800;
}

.key-text {

    color:
        var(--text-body);

    font-size: 11px;

    font-weight: 600;
}

/* ============================================================
   RESPONSIVE
   ============================================================ */

@media (max-width: 1100px) {

    .product-edit-page {

        padding-left: 15px;

        padding-right: 15px;
    }

    .edit-layout {

        grid-template-columns:
            minmax(0,1fr)
            360px;
    }
}

@media (max-width: 950px) {

    .edit-layout {

        grid-template-columns:
            1fr;
    }
}

@media (max-width: 650px) {

    .product-edit-page {

        padding:
            0 10px 25px;
    }

    .page-header {

        flex-direction: column;

        align-items: flex-start;

        gap: 15px;
    }

    .page-header h1 {

        font-size: 26px;
    }

    .page-header p {

        font-size: 13px;
    }

    .header-actions {

        width: 100%;
    }

    .header-actions .btn {

        flex: 1;
    }

    .card-header {

        padding: 16px;
    }

    .card-header h2 {

        font-size: 17px;
    }

    .card-body {

        padding:
            20px 16px;
    }

    .two-column,
    .flags-grid {

        grid-template-columns:
            1fr;
    }

    .gallery-card-row {

        grid-template-columns:
            80px
            minmax(0,1fr);
    }

    .gallery-card-img-wrap {

        width: 80px;

        height: 80px;
    }

    .gallery-card-actions {

        flex-direction: column;

        align-items: flex-start;
    }

    .action-footer {

        flex-direction: column-reverse;

        padding:
            15px 16px;
    }

    .action-footer .btn {

        width: 100%;
    }

    .shortcut-grid {

        grid-template-columns:
            1fr;
    }
}

@media (max-width: 430px) {

    .page-header h1 {

        font-size: 24px;
    }

    .breadcrumb {

        font-size: 11px;
    }

    .input,
    .select {

        height: 44px;
    }

    .textarea {

        min-height: 130px;
    }

    .gallery-card-row {

        grid-template-columns:
            1fr;
    }

    .gallery-card-img-wrap {

        width: 100%;

        height: 180px;
    }
}

</style>


<main class="main">

<section class="content">

<div class="product-edit-page">

    <!-- ======================================================
         PAGE HEADER
         ====================================================== -->

    <div class="page-header">

        <div>

            <div class="breadcrumb">

                <span>Products</span>

                <span>/</span>

                <span class="current">
                    Edit Product
                </span>

            </div>

            <h1>
                Edit Product
            </h1>

            <p>
                Update product information, pricing,
                taxes, visibility and gallery images.
            </p>

        </div>

        <div class="header-actions">

            <a
                href="index.php"
                class="btn"
                title="Cancel (Esc)"
            >

                <span class="btn-shortcut">
                    Esc
                </span>

                <span>
                    ← Back
                </span>

            </a>

            <button
                type="submit"
                form="productEditForm"
                class="btn btn-primary"
                title="Save (Ctrl + S)"
            >

                <span>
                    ✓ Save Changes
                </span>

                <span class="btn-shortcut">
                    Ctrl+S
                </span>

            </button>

        </div>

    </div>


    <!-- ======================================================
         ERROR
         ====================================================== -->

    <?php if ($errorMessage !== ''): ?>

        <div class="notice notice-error">

            <?= e($errorMessage) ?>

        </div>

    <?php endif; ?>


    <!-- ======================================================
         FORM
         ====================================================== -->

    <form
        method="post"
        enctype="multipart/form-data"
        id="productEditForm"
        autocomplete="off"
    >

        <input
            type="hidden"
            name="action"
            value="update_product"
        >

        <input
            type="hidden"
            name="product_id"
            value="<?= $productId ?>"
        >

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e($csrfToken) ?>"
        >


        <div class="edit-layout">


            <!-- ==================================================
                 LEFT COLUMN
                 ================================================== -->

            <div>


                <!-- GENERAL INFORMATION -->

                <div class="card">

                    <div class="card-header">

                        <h2>
                            General Information
                        </h2>

                        <p>
                            Basic product information and category mapping.
                        </p>

                    </div>

                    <div class="card-body">


                        <!-- CATEGORY -->

                        <div class="form-group">

                            <label class="form-label">

                                <span>
                                    Category
                                    <span class="required">*</span>
                                </span>

                            </label>

                            <select
                                name="category_id"
                                class="select"
                                required
                            >

                                <option value="">
                                    Select Category
                                </option>

                                <?php foreach (
                                    $categoriesList
                                    as $cat
                                ): ?>

                                    <?php
                                    $catId =
                                        (int)$cat['CategoryId'];

                                    $parentId =
                                        (int)(
                                            $cat['ParentCategoryId']
                                            ?? 0
                                        );

                                    $catName =
                                        (string)$cat['Name'];
                                    ?>

                                    <option
                                        value="<?= $catId ?>"
                                        <?= $categoryId === $catId
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >

                                        <?= $parentId > 0
                                            ? '↳ '
                                            : ''
                                        ?>

                                        <?= e($catName) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- PRODUCT NAME -->

                        <div class="form-group">

                            <label class="form-label">

                                <span>

                                    Product Name

                                    <span class="required">
                                        *
                                    </span>

                                </span>

                                <span id="nameCounter">

                                    <?= strlen($name) ?>/255

                                </span>

                            </label>

                            <input
                                type="text"
                                name="name"
                                id="productName"
                                class="input"
                                value="<?= e($name) ?>"
                                maxlength="255"
                                required
                                placeholder="e.g. Premium Bath Towel"
                            >

                        </div>


                        <!-- SLUG -->

                        <div class="form-group">

                            <label class="form-label">

                                <span>

                                    Slug

                                    <span class="required">
                                        *
                                    </span>

                                </span>

                            </label>

                            <div class="input-prefix">

                                <span>/</span>

                                <input
                                    type="text"
                                    name="slug"
                                    id="productSlug"
                                    class="input"
                                    value="<?= e($slug) ?>"
                                    maxlength="255"
                                    required
                                    placeholder="premium-bath-towel"
                                >

                            </div>

                            <div class="help-text">

                                Slug automatically follows
                                the product name until you
                                manually change it.

                            </div>

                        </div>


                        <!-- SHORT DESCRIPTION -->

                        <div class="form-group">

                            <label class="form-label">

                                <span>
                                    Short Description
                                </span>

                            </label>

                            <input
                                type="text"
                                name="short_description"
                                class="input"
                                value="<?= e($shortDescription) ?>"
                                maxlength="500"
                                placeholder="One-line product summary"
                            >

                        </div>


                        <!-- DESCRIPTION -->

                        <div class="form-group">

                            <label class="form-label">

                                <span>
                                    Full Description
                                </span>

                            </label>

                            <textarea
                                name="description"
                                class="textarea"
                                maxlength="5000"
                                placeholder="Write detailed product description..."
                            ><?= e($description) ?></textarea>

                        </div>

                    </div>

                </div>


                <!-- SPECIFICATIONS -->

                <div class="card">

                    <div class="card-header">

                        <h2>
                            Specifications & Care
                        </h2>

                        <p>
                            Product material, size, GSM and
                            care information.
                        </p>

                    </div>

                    <div class="card-body">

                        <div class="two-column">


                            <div class="form-group">

                                <label class="form-label">

                                    Specifications

                                </label>

                                <textarea
                                    name="specifications"
                                    class="textarea"
                                    maxlength="5000"
                                    placeholder="e.g. 100% Cotton, 600 GSM, 70x140 cm"
                                ><?= e($specifications) ?></textarea>

                            </div>


                            <div class="form-group">

                                <label class="form-label">

                                    Care Instructions

                                </label>

                                <textarea
                                    name="care_instructions"
                                    class="textarea"
                                    maxlength="5000"
                                    placeholder="e.g. Machine wash cold with similar colors."
                                ><?= e($careInstructions) ?></textarea>

                            </div>


                        </div>

                    </div>

                </div>


                <!-- SEO -->

                <div class="card">

                    <div class="card-header">

                        <h2>
                            Search Engine Optimization
                        </h2>

                        <p>
                            Product meta title and description.
                        </p>

                    </div>

                    <div class="card-body">


                        <div class="form-group">

                            <label class="form-label">

                                <span>
                                    Meta Title
                                </span>

                                <span id="metaTitleCounter">
                                    <?= strlen($metaTitle) ?>/255
                                </span>

                            </label>

                            <input
                                type="text"
                                name="meta_title"
                                id="metaTitle"
                                class="input"
                                value="<?= e($metaTitle) ?>"
                                maxlength="255"
                                placeholder="Premium Bath Towel | GatewayLinen"
                            >

                        </div>


                        <div class="form-group">

                            <label class="form-label">

                                <span>
                                    Meta Description
                                </span>

                                <span id="metaDescriptionCounter">
                                    <?= strlen($metaDescription) ?>/500
                                </span>

                            </label>

                            <textarea
                                name="meta_description"
                                id="metaDescription"
                                class="textarea"
                                maxlength="500"
                                style="min-height:110px;"
                                placeholder="Write a search engine friendly description..."
                            ><?= e($metaDescription) ?></textarea>

                        </div>


                    </div>

                </div>


            </div>


            <!-- ==================================================
                 RIGHT COLUMN
                 ================================================== -->

            <div>


                <!-- PRICING -->

                <div class="card">

                    <div class="card-header">

                        <h2>
                            Pricing & Taxes
                        </h2>

                        <p>
                            Product price and GST/PST configuration.
                        </p>

                    </div>

                    <div class="card-body">


                        <!-- BASE PRICE -->

                        <div class="form-group">

                            <label class="form-label">

                                <span>

                                    Base Price

                                    <span class="required">
                                        *
                                    </span>

                                </span>

                            </label>

                            <div class="input-prefix">

                                <span>
                                    $
                                </span>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    id="basePrice"
                                    name="base_price"
                                    class="input"
                                    value="<?= e($basePrice) ?>"
                                    required
                                >

                            </div>

                        </div>


                        <!-- TAX TYPE -->

                        <div class="form-group">

                            <label class="form-label">

                                Tax Configuration

                            </label>

                            <select
                                id="taxType"
                                name="tax_type"
                                class="select"
                            >

                                <option
                                    value="both"
                                    <?= $taxType === 'both'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Both GST (5%) & PST (7%)
                                </option>

                                <option
                                    value="gst_only"
                                    <?= $taxType === 'gst_only'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    GST Only
                                </option>

                                <option
                                    value="pst_only"
                                    <?= $taxType === 'pst_only'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    PST Only
                                </option>

                                <option
                                    value="none"
                                    <?= $taxType === 'none'
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Without Tax
                                </option>

                                <option
                                    value="custom"
                                    id="customTaxOption"
                                    <?= $taxType === 'custom'
                                        ? 'selected'
                                        : ''
                                    ?>
                                    <?= $taxType !== 'custom'
                                        ? 'style="display:none;"'
                                        : ''
                                    ?>
                                >
                                    Custom Tax
                                </option>

                            </select>

                        </div>


                        <!-- GST / PST -->

                        <div class="two-column">


                            <div class="form-group">

                                <label class="form-label">

                                    <span>
                                        GST Rate (%)
                                    </span>

                                    <button
                                        type="button"
                                        class="tax-edit-btn"
                                        id="editGstBtn"
                                    >
                                        🔒 Unlock
                                    </button>

                                </label>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    id="gstPercentage"
                                    name="gst_percentage"
                                    class="input"
                                    value="<?= e($gstPercentage) ?>"
                                    readonly
                                >

                            </div>


                            <div class="form-group">

                                <label class="form-label">

                                    <span>
                                        PST Rate (%)
                                    </span>

                                    <button
                                        type="button"
                                        class="tax-edit-btn"
                                        id="editPstBtn"
                                    >
                                        🔒 Unlock
                                    </button>

                                </label>

                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    id="pstPercentage"
                                    name="pst_percentage"
                                    class="input"
                                    value="<?= e($pstPercentage) ?>"
                                    readonly
                                >

                            </div>


                        </div>


                        <!-- TAX SUMMARY -->

                        <div class="tax-summary-box">

                            <div class="tax-summary-row">

                                <span>
                                    Base Price
                                </span>

                                <span id="summaryBasePrice">
                                    $0.00
                                </span>

                            </div>

                            <div class="tax-summary-row">

                                <span>
                                    GST Amount
                                </span>

                                <span id="summaryGstAmount">
                                    $0.00
                                </span>

                            </div>

                            <div class="tax-summary-row">

                                <span>
                                    PST Amount
                                </span>

                                <span id="summaryPstAmount">
                                    $0.00
                                </span>

                            </div>

                            <div class="tax-summary-row">

                                <span>
                                    Grand Total
                                </span>

                                <span id="summaryGrandTotal">
                                    $0.00
                                </span>

                            </div>

                        </div>


                    </div>

                </div>


                <!-- VISIBILITY -->

                <div class="card">

                    <div class="card-header">

                        <h2>
                            Visibility & Flags
                        </h2>

                        <p>
                            Product status and showcase settings.
                        </p>

                    </div>

                    <div class="card-body">

                        <div class="flags-grid">


                            <div class="status-box">

                                <div class="status-info">

                                    <strong>
                                        Active
                                    </strong>

                                    <span>
                                        Product visibility
                                    </span>

                                </div>

                                <label class="switch">

                                    <input
                                        type="checkbox"
                                        name="is_active"
                                        value="1"
                                        <?= $isActive
                                            ? 'checked'
                                            : ''
                                        ?>
                                    >

                                    <span class="slider"></span>

                                </label>

                            </div>


                            <div class="status-box">

                                <div class="status-info">

                                    <strong>
                                        Featured
                                    </strong>

                                    <span>
                                        Show as featured
                                    </span>

                                </div>

                                <label class="switch">

                                    <input
                                        type="checkbox"
                                        name="is_featured"
                                        value="1"
                                        <?= $isFeatured
                                            ? 'checked'
                                            : ''
                                        ?>
                                    >

                                    <span class="slider"></span>

                                </label>

                            </div>


                            <div class="status-box">

                                <div class="status-info">

                                    <strong>
                                        New Arrival
                                    </strong>

                                    <span>
                                        New product badge
                                    </span>

                                </div>

                                <label class="switch">

                                    <input
                                        type="checkbox"
                                        name="is_new_arrival"
                                        value="1"
                                        <?= $isNewArrival
                                            ? 'checked'
                                            : ''
                                        ?>
                                    >

                                    <span class="slider"></span>

                                </label>

                            </div>


                            <div class="status-box">

                                <div class="status-info">

                                    <strong>
                                        Best Seller
                                    </strong>

                                    <span>
                                        Best seller badge
                                    </span>

                                </div>

                                <label class="switch">

                                    <input
                                        type="checkbox"
                                        name="is_best_seller"
                                        value="1"
                                        <?= $isBestSeller
                                            ? 'checked'
                                            : ''
                                        ?>
                                    >

                                    <span class="slider"></span>

                                </label>

                            </div>


                        </div>

                    </div>

                </div>


                <!-- PRODUCT IMAGES -->

                <div class="card">

                    <div class="card-header">

                        <h2>
                            Product Images
                        </h2>

                        <p>
                            Replace, delete, select main
                            image or add new photos.
                        </p>

                    </div>

                    <div class="card-body">


                        <?php if (
                            !empty($existingImages)
                        ): ?>

                            <div class="gallery-list">

                                <?php foreach (
                                    $existingImages
                                    as $gImg
                                ): ?>

                                    <?php

                                    $imgId =
                                        (int)$gImg['ImageId'];

                                    $isMain =
                                        !empty(
                                            $gImg['IsMain']
                                        );

                                    $imageSrc =
                                        imageWebPath(
                                            (string)(
                                                $gImg['ImageUrl']
                                                ?? ''
                                            )
                                        );

                                    $altText =
                                        (string)(
                                            $gImg['AltText']
                                            ?? $name
                                        );

                                    ?>

                                    <div
                                        class="
                                            gallery-card-row
                                            <?= $isMain
                                                ? 'is-main-active'
                                                : ''
                                            ?>
                                        "
                                    >


                                        <!-- IMAGE -->

                                        <div
                                            class="gallery-card-img-wrap"
                                        >

                                            <?php if (
                                                $isMain
                                            ): ?>

                                                <span
                                                    class="main-badge"
                                                >
                                                    MAIN
                                                </span>

                                            <?php endif; ?>


                                            <?php if (
                                                $imageSrc !== ''
                                            ): ?>

                                                <img
                                                    src="<?= e($imageSrc) ?>"
                                                    alt="<?= e($altText) ?>"
                                                    loading="lazy"
                                                    onerror="this.style.display='none';"
                                                >

                                            <?php else: ?>

                                                <div
                                                    style="
                                                        width:100%;
                                                        height:100%;
                                                        display:flex;
                                                        align-items:center;
                                                        justify-content:center;
                                                        color:var(--text-mute);
                                                        font-size:26px;
                                                    "
                                                >
                                                    ◈
                                                </div>

                                            <?php endif; ?>

                                        </div>


                                        <!-- CONTROLS -->

                                        <div
                                            class="gallery-card-controls"
                                        >


                                            <div
                                                class="gallery-card-actions"
                                            >


                                                <label
                                                    class="main-radio-label"
                                                    title="Set this image as main image"
                                                >

                                                    <input
                                                        type="radio"
                                                        name="main_image_id"
                                                        value="<?= $imgId ?>"
                                                        <?= $isMain
                                                            ? 'checked'
                                                            : ''
                                                        ?>
                                                    >

                                                    <span>
                                                        Set as Main
                                                    </span>

                                                </label>


                                                <label
                                                    class="del-checkbox-label"
                                                    title="Delete this image"
                                                >

                                                    <input
                                                        type="checkbox"
                                                        name="delete_images[]"
                                                        value="<?= $imgId ?>"
                                                    >

                                                    <span>
                                                        Delete
                                                    </span>

                                                </label>


                                            </div>


                                            <div>

                                                <span
                                                    class="replace-file-label"
                                                >
                                                    Replace this photo
                                                </span>

                                                <input
                                                    type="file"
                                                    name="replace_image[<?= $imgId ?>]"
                                                    class="replace-file-input"
                                                    accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif"
                                                >

                                            </div>


                                        </div>


                                    </div>

                                <?php endforeach; ?>

                            </div>

                        <?php else: ?>

                            <div
                                class="empty-gallery"
                            >
                                No product images have been uploaded yet.
                            </div>

                        <?php endif; ?>


                        <!-- ADD MORE -->

                        <div
                            class="form-group"
                            style="
                                margin-top:18px;
                                margin-bottom:0;
                            "
                        >

                            <label class="form-label">

                                Add More Images

                            </label>

                            <input
                                type="file"
                                name="new_product_images[]"
                                id="newProductImages"
                                class="file-input-multiple"
                                multiple
                                accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif"
                            >

                            <div class="help-text">

                                JPG, PNG, WEBP or GIF.
                                Maximum 5 MB per image.

                            </div>

                        </div>


                    </div>


                    <!-- FOOTER -->

                    <div class="action-footer">

                        <a
                            href="index.php"
                            class="btn"
                        >

                            <span class="btn-shortcut">
                                Esc
                            </span>

                            <span>
                                Cancel
                            </span>

                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >

                            <span>
                                ✓ Update Product
                            </span>

                            <span class="btn-shortcut">
                                Ctrl+S
                            </span>

                        </button>

                    </div>

                </div>


            </div>

        </div>

    </form>


    <!-- ======================================================
         KEYBOARD SHORTCUTS
         ====================================================== -->

    <div class="shortcut-box">

        <div class="shortcut-title">

            ⌨ Keyboard Shortcuts

        </div>

        <div class="shortcut-grid">

            <div class="shortcut">

                <span class="key">
                    Ctrl+S
                </span>

                <span class="key-text">
                    Save Changes
                </span>

            </div>

            <div class="shortcut">

                <span class="key">
                    Ctrl+Shift+S
                </span>

                <span class="key-text">
                    Save Changes
                </span>

            </div>

            <div class="shortcut">

                <span class="key">
                    Esc
                </span>

                <span class="key-text">
                    Cancel / Back
                </span>

            </div>

        </div>

    </div>


</div>

</section>

</main>


<script>

/* ============================================================
   PRODUCT EDIT JAVASCRIPT
   ============================================================ */

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const form =
            document.getElementById(
                'productEditForm'
            );

        const nameInput =
            document.getElementById(
                'productName'
            );

        const slugInput =
            document.getElementById(
                'productSlug'
            );

        const nameCounter =
            document.getElementById(
                'nameCounter'
            );

        const basePriceInput =
            document.getElementById(
                'basePrice'
            );

        const taxTypeSelect =
            document.getElementById(
                'taxType'
            );

        const gstInput =
            document.getElementById(
                'gstPercentage'
            );

        const pstInput =
            document.getElementById(
                'pstPercentage'
            );

        const editGstBtn =
            document.getElementById(
                'editGstBtn'
            );

        const editPstBtn =
            document.getElementById(
                'editPstBtn'
            );

        const customTaxOption =
            document.getElementById(
                'customTaxOption'
            );

        const summaryBasePrice =
            document.getElementById(
                'summaryBasePrice'
            );

        const summaryGstAmount =
            document.getElementById(
                'summaryGstAmount'
            );

        const summaryPstAmount =
            document.getElementById(
                'summaryPstAmount'
            );

        const summaryGrandTotal =
            document.getElementById(
                'summaryGrandTotal'
            );

        const metaTitle =
            document.getElementById(
                'metaTitle'
            );

        const metaTitleCounter =
            document.getElementById(
                'metaTitleCounter'
            );

        const metaDescription =
            document.getElementById(
                'metaDescription'
            );

        const metaDescriptionCounter =
            document.getElementById(
                'metaDescriptionCounter'
            );

        const newProductImages =
            document.getElementById(
                'newProductImages'
            );


        /* ====================================================
           SLUG
           ==================================================== */

        function makeSlug(value) {

            return value
                .toLowerCase()
                .trim()
                .replace(
                    /[^a-z0-9]+/g,
                    '-'
                )
                .replace(
                    /^-+|-+$/g,
                    ''
                );
        }


        let slugManuallyEdited =
            slugInput.value.trim() !==
            makeSlug(
                nameInput.value
            );


        nameInput.addEventListener(
            'input',
            function () {

                nameCounter.textContent =
                    this.value.length +
                    '/255';

                if (!slugManuallyEdited) {

                    slugInput.value =
                        makeSlug(
                            this.value
                        );
                }
            }
        );


        slugInput.addEventListener(
            'input',
            function () {

                const generated =
                    makeSlug(
                        nameInput.value
                    );

                slugManuallyEdited =
                    this.value !== generated;
            }
        );


        /* ====================================================
           META COUNTERS
           ==================================================== */

        if (metaTitle) {

            metaTitle.addEventListener(
                'input',
                function () {

                    metaTitleCounter.textContent =
                        this.value.length +
                        '/255';
                }
            );
        }


        if (metaDescription) {

            metaDescription.addEventListener(
                'input',
                function () {

                    metaDescriptionCounter.textContent =
                        this.value.length +
                        '/500';
                }
            );
        }


        /* ====================================================
           TAX CALCULATION
           ==================================================== */

        function calculateTaxSummary() {

            const basePrice =
                parseFloat(
                    basePriceInput.value
                ) || 0;

            const gstRate =
                parseFloat(
                    gstInput.value
                ) || 0;

            const pstRate =
                parseFloat(
                    pstInput.value
                ) || 0;

            const gstAmount =
                (
                    basePrice *
                    gstRate
                ) / 100;

            const pstAmount =
                (
                    basePrice *
                    pstRate
                ) / 100;

            const grandTotal =
                basePrice +
                gstAmount +
                pstAmount;

            summaryBasePrice.textContent =
                '$' +
                basePrice.toFixed(2);

            summaryGstAmount.textContent =
                '$' +
                gstAmount.toFixed(2) +
                ' (' +
                gstRate +
                '%)';

            summaryPstAmount.textContent =
                '$' +
                pstAmount.toFixed(2) +
                ' (' +
                pstRate +
                '%)';

            summaryGrandTotal.textContent =
                '$' +
                grandTotal.toFixed(2);
        }


        function updateTaxFields() {

            const mode =
                taxTypeSelect.value;

            if (mode === 'both') {

                gstInput.value =
                    '5.00';

                pstInput.value =
                    '7.00';

                customTaxOption.style.display =
                    'none';

            } else if (
                mode === 'gst_only'
            ) {

                gstInput.value =
                    '5.00';

                pstInput.value =
                    '0.00';

                customTaxOption.style.display =
                    'none';

            } else if (
                mode === 'pst_only'
            ) {

                gstInput.value =
                    '0.00';

                pstInput.value =
                    '7.00';

                customTaxOption.style.display =
                    'none';

            } else if (
                mode === 'none'
            ) {

                gstInput.value =
                    '0.00';

                pstInput.value =
                    '0.00';

                customTaxOption.style.display =
                    'none';
            }

            calculateTaxSummary();
        }


        function checkCustomTaxStatus() {

            const gst =
                parseFloat(
                    gstInput.value
                ) || 0;

            const pst =
                parseFloat(
                    pstInput.value
                ) || 0;

            const mode =
                taxTypeSelect.value;

            let standard =
                false;

            if (
                mode === 'both' &&
                gst === 5 &&
                pst === 7
            ) {
                standard = true;
            }

            if (
                mode === 'gst_only' &&
                gst === 5 &&
                pst === 0
            ) {
                standard = true;
            }

            if (
                mode === 'pst_only' &&
                gst === 0 &&
                pst === 7
            ) {
                standard = true;
            }

            if (
                mode === 'none' &&
                gst === 0 &&
                pst === 0
            ) {
                standard = true;
            }

            if (!standard) {

                customTaxOption.style.display =
                    'block';

                customTaxOption.textContent =
                    'Custom Tax (GST: ' +
                    gst +
                    '%, PST: ' +
                    pst +
                    '%)';

                taxTypeSelect.value =
                    'custom';

            } else {

                customTaxOption.style.display =
                    'none';
            }

            calculateTaxSummary();
        }


        taxTypeSelect.addEventListener(
            'change',
            updateTaxFields
        );


        basePriceInput.addEventListener(
            'input',
            calculateTaxSummary
        );


        /* ====================================================
           GST UNLOCK
           ==================================================== */

        editGstBtn.addEventListener(
            'click',
            function () {

                if (
                    gstInput.hasAttribute(
                        'readonly'
                    )
                ) {

                    gstInput.removeAttribute(
                        'readonly'
                    );

                    gstInput.focus();

                    editGstBtn.textContent =
                        '🔓 Lock';

                    editGstBtn.classList.add(
                        'unlocked'
                    );

                } else {

                    gstInput.setAttribute(
                        'readonly',
                        'readonly'
                    );

                    editGstBtn.textContent =
                        '🔒 Unlock';

                    editGstBtn.classList.remove(
                        'unlocked'
                    );

                    checkCustomTaxStatus();
                }
            }
        );


        /* ====================================================
           PST UNLOCK
           ==================================================== */

        editPstBtn.addEventListener(
            'click',
            function () {

                if (
                    pstInput.hasAttribute(
                        'readonly'
                    )
                ) {

                    pstInput.removeAttribute(
                        'readonly'
                    );

                    pstInput.focus();

                    editPstBtn.textContent =
                        '🔓 Lock';

                    editPstBtn.classList.add(
                        'unlocked'
                    );

                } else {

                    pstInput.setAttribute(
                        'readonly',
                        'readonly'
                    );

                    editPstBtn.textContent =
                        '🔒 Unlock';

                    editPstBtn.classList.remove(
                        'unlocked'
                    );

                    checkCustomTaxStatus();
                }
            }
        );


        gstInput.addEventListener(
            'input',
            function () {

                if (
                    !gstInput.hasAttribute(
                        'readonly'
                    )
                ) {

                    checkCustomTaxStatus();
                }
            }
        );


        pstInput.addEventListener(
            'input',
            function () {

                if (
                    !pstInput.hasAttribute(
                        'readonly'
                    )
                ) {

                    checkCustomTaxStatus();
                }
            }
        );


        /* ====================================================
           IMAGE SELECTION VALIDATION
           ==================================================== */

        function validateFile(
            file
        ) {

            if (!file) {
                return true;
            }

            const allowed = [
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/gif'
            ];

            if (
                !allowed.includes(
                    file.type
                )
            ) {

                alert(
                    'Please select JPG, PNG, WEBP or GIF image.'
                );

                return false;
            }

            if (
                file.size >
                5 * 1024 * 1024
            ) {

                alert(
                    'Image size must be 5 MB or less.'
                );

                return false;
            }

            return true;
        }


        /* Existing replacement inputs */

        document
            .querySelectorAll(
                '.replace-file-input'
            )
            .forEach(
                function (input) {

                    input.addEventListener(
                        'change',
                        function () {

                            const file =
                                this.files[0];

                            if (
                                file &&
                                !validateFile(
                                    file
                                )
                            ) {

                                this.value =
                                    '';
                            }
                        }
                    );
                }
            );


        /* New images */

        if (newProductImages) {

            newProductImages.addEventListener(
                'change',
                function () {

                    const files =
                        Array.from(
                            this.files
                        );

                    for (
                        const file
                        of files
                    ) {

                        if (
                            !validateFile(
                                file
                            )
                        ) {

                            this.value =
                                '';

                            return;
                        }
                    }
                }
            );
        }


        /* ====================================================
           DELETE + MAIN IMAGE SAFETY
           ==================================================== */

        const deleteCheckboxes =
            document.querySelectorAll(
                'input[name="delete_images[]"]'
            );

        const mainRadios =
            document.querySelectorAll(
                'input[name="main_image_id"]'
            );


        deleteCheckboxes.forEach(
            function (checkbox) {

                checkbox.addEventListener(
                    'change',
                    function () {

                        if (
                            !this.checked
                        ) {
                            return;
                        }

                        const row =
                            this.closest(
                                '.gallery-card-row'
                            );

                        if (!row) {
                            return;
                        }

                        const mainRadio =
                            row.querySelector(
                                'input[name="main_image_id"]'
                            );

                        if (
                            mainRadio &&
                            mainRadio.checked
                        ) {

                            const alternative =
                                Array.from(
                                    mainRadios
                                ).find(
                                    function (radio) {

                                        const radioRow =
                                            radio.closest(
                                                '.gallery-card-row'
                                            );

                                        const deleteBox =
                                            radioRow
                                                ?.querySelector(
                                                    'input[name="delete_images[]"]'
                                                );

                                        return (
                                            radio !==
                                            mainRadio &&
                                            !(
                                                deleteBox &&
                                                deleteBox.checked
                                            )
                                        );
                                    }
                                );

                            if (alternative) {

                                alternative.checked =
                                    true;

                            } else {

                                alert(
                                    'Please keep at least one image as the main image.'
                                );

                                this.checked =
                                    false;
                            }
                        }
                    }
                );
            }
        );


        /* ====================================================
           INITIAL TAX
           ==================================================== */

        calculateTaxSummary();


        /* ====================================================
           KEYBOARD SHORTCUTS
           ==================================================== */

        document.addEventListener(
            'keydown',
            function (e) {

                /*
                 * Ctrl + S
                 */

                if (
                    e.ctrlKey &&
                    !e.altKey &&
                    e.key.toLowerCase() === 's'
                ) {

                    e.preventDefault();

                    if (
                        form.requestSubmit
                    ) {

                        form.requestSubmit();

                    } else {

                        form.submit();
                    }

                    return;
                }


                /*
                 * Escape
                 */

                if (
                    e.key === 'Escape'
                ) {

                    const tag =
                        e.target?.tagName
                            ?.toLowerCase();

                    if (
                        [
                            'input',
                            'textarea',
                            'select'
                        ].includes(tag)
                    ) {

                        if (
                            e.target.value
                        ) {

                            e.target.blur();

                            return;
                        }
                    }

                    window.location.href =
                        'index.php';
                }

            }
        );


        /* ====================================================
           DOUBLE SUBMIT PROTECTION
           ==================================================== */

        let submitting =
            false;

        form.addEventListener(
            'submit',
            function (e) {

                if (submitting) {

                    e.preventDefault();

                    return;
                }

                submitting = true;

                const buttons =
                    form.querySelectorAll(
                        'button[type="submit"]'
                    );

                buttons.forEach(
                    function (button) {

                        button.disabled =
                            true;

                        button.dataset.originalText =
                            button.innerHTML;

                        button.innerHTML =
                            '⏳ Saving...';
                    }
                );

            }
        );

    }
);

</script>


<?php

require_once __DIR__ .
    '/../includes/footer.php';

?>