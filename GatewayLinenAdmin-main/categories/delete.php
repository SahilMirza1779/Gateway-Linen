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

/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| ONLY POST REQUEST ALLOWED
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| CSRF CHECK
|--------------------------------------------------------------------------
*/

$sessionToken = $_SESSION['category_delete_token'] ?? '';
$postToken    = $_POST['csrf_token'] ?? '';

if (
    empty($sessionToken) ||
    empty($postToken) ||
    !hash_equals($sessionToken, $postToken)
) {
    header('Location: index.php?error=' . urlencode('Invalid security token. Please try again.'));
    exit;
}

/*
|--------------------------------------------------------------------------
| CATEGORY ID
|--------------------------------------------------------------------------
*/

$categoryId = isset($_POST['category_id'])
    ? (int)$_POST['category_id']
    : 0;

if ($categoryId <= 0) {
    header('Location: index.php?error=' . urlencode('Invalid category selected.'));
    exit;
}

/*
|--------------------------------------------------------------------------
| CHECK CATEGORY
|--------------------------------------------------------------------------
*/

$checkSql = "
    SELECT
        c.CategoryId,
        c.Name,
        c.DisplayOrder,
        (
            SELECT COUNT(*)
            FROM dbo.Products p
            WHERE p.CategoryId = c.CategoryId
        ) AS ProductCount
    FROM dbo.Categories c
    WHERE c.CategoryId = ?
";

$checkStmt = sqlsrv_query(
    $conn,
    $checkSql,
    [$categoryId]
);

if ($checkStmt === false) {

    header(
        'Location: index.php?error=' .
        urlencode('Unable to check the selected category.')
    );

    exit;
}

$category = sqlsrv_fetch_array(
    $checkStmt,
    SQLSRV_FETCH_ASSOC
);

sqlsrv_free_stmt($checkStmt);

/*
|--------------------------------------------------------------------------
| CATEGORY NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$category) {

    header(
        'Location: index.php?error=' .
        urlencode('Category not found.')
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| PRODUCT CHECK
|--------------------------------------------------------------------------
*/

$productCount = (int)($category['ProductCount'] ?? 0);

if ($productCount > 0) {

    $message =
        'Category "' .
        ($category['Name'] ?? 'Unknown') .
        '" cannot be deleted because it contains ' .
        $productCount .
        ' product(s). Move or remove those products first.';

    header(
        'Location: index.php?error=' .
        urlencode($message)
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| START TRANSACTION
|--------------------------------------------------------------------------
*/

if (!sqlsrv_begin_transaction($conn)) {

    header(
        'Location: index.php?error=' .
        urlencode('Unable to start delete transaction.')
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| DELETE CATEGORY
|--------------------------------------------------------------------------
*/

$deleteSql = "
    DELETE FROM dbo.Categories
    WHERE CategoryId = ?
";

$deleteStmt = sqlsrv_query(
    $conn,
    $deleteSql,
    [$categoryId]
);

if ($deleteStmt === false) {

    sqlsrv_rollback($conn);

    header(
        'Location: index.php?error=' .
        urlencode(
            'Category could not be deleted. It may be referenced by another record.'
        )
    );

    exit;
}

sqlsrv_free_stmt($deleteStmt);

/*
|--------------------------------------------------------------------------
| IMPORTANT:
| TEMPORARILY MOVE DISPLAY ORDER
|--------------------------------------------------------------------------
|
| This avoids duplicate DisplayOrder problems if DisplayOrder
| has a UNIQUE constraint/index.
|
*/

$tempSql = "
    UPDATE dbo.Categories
    SET DisplayOrder = DisplayOrder + 100000
";

$tempStmt = sqlsrv_query(
    $conn,
    $tempSql
);

if ($tempStmt === false) {

    sqlsrv_rollback($conn);

    header(
        'Location: index.php?error=' .
        urlencode(
            'Category was not deleted because category numbering could not be prepared.'
        )
    );

    exit;
}

sqlsrv_free_stmt($tempStmt);

/*
|--------------------------------------------------------------------------
| AUTO RE-NUMBER
|--------------------------------------------------------------------------
|
| Remaining categories:
|
| 1
| 2
| 3
| 4
| ...
|
| Existing order is preserved.
|
*/

$renumberSql = "
    ;WITH OrderedCategories AS
    (
        SELECT
            CategoryId,
            ROW_NUMBER() OVER
            (
                ORDER BY
                    DisplayOrder ASC,
                    Name ASC,
                    CategoryId ASC
            ) AS NewOrder
        FROM dbo.Categories
    )
    UPDATE c
    SET c.DisplayOrder = o.NewOrder
    FROM dbo.Categories c
    INNER JOIN OrderedCategories o
        ON o.CategoryId = c.CategoryId;
";

$renumberStmt = sqlsrv_query(
    $conn,
    $renumberSql
);

if ($renumberStmt === false) {

    sqlsrv_rollback($conn);

    header(
        'Location: index.php?error=' .
        urlencode(
            'Category was not deleted because automatic re-numbering failed.'
        )
    );

    exit;
}

sqlsrv_free_stmt($renumberStmt);

/*
|--------------------------------------------------------------------------
| COMMIT
|--------------------------------------------------------------------------
*/

if (!sqlsrv_commit($conn)) {

    sqlsrv_rollback($conn);

    header(
        'Location: index.php?error=' .
        urlencode('Category deletion could not be completed.')
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| SUCCESS
|--------------------------------------------------------------------------
*/

$deletedName = (string)($category['Name'] ?? 'Category');

$message =
    'Category "' .
    $deletedName .
    '" deleted successfully. Category numbers were updated automatically.';

header(
    'Location: index.php?success=' .
    urlencode($message)
);

exit;