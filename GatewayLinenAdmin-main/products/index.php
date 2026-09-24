<?php
session_start();

/*
|--------------------------------------------------------------------------
| GatewayLinen Admin - Products List (Index)
|--------------------------------------------------------------------------
| File:
| GatewayLinenadmin/products/index.php
|
| Requires:
| ../config/database.php
| ../includes/header.php
| ../includes/sidebar.php
| ../includes/footer.php
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/

if (empty($_SESSION["admin_id"])) {
    header("Location: ../index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../config/database.php";

/*
|--------------------------------------------------------------------------
| PAGE SETTINGS
|--------------------------------------------------------------------------
*/

$activeMenu = "products";
$pageTitle  = "GatewayLinen | Products Management";

/*
|--------------------------------------------------------------------------
| HELPER FUNCTION
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

/*
|--------------------------------------------------------------------------
| FETCH PRODUCTS FROM DATABASE
|--------------------------------------------------------------------------
*/

$products = [];
$productSql = "
    SELECT 
        p.ProductId,
        p.Name,
        p.Slug,
        p.BasePrice,
        p.IsActive,
        p.IsFeatured,
        p.IsNewArrival,
        p.IsBestSeller,
        c.Name AS CategoryName,
        (SELECT TOP 1 ImageUrl FROM dbo.ProductImages WHERE ProductId = p.ProductId ORDER BY IsMain DESC, DisplayOrder ASC) AS MainImage
    FROM dbo.Products p
    LEFT JOIN dbo.Categories c ON p.CategoryId = c.CategoryId
    ORDER BY p.CreatedAt DESC
";

$productStmt = sqlsrv_query($conn, $productSql);

if ($productStmt !== false) {
    while ($row = sqlsrv_fetch_array($productStmt, SQLSRV_FETCH_ASSOC)) {
        $products[] = $row;
    }
    sqlsrv_free_stmt($productStmt);
}

/*
|--------------------------------------------------------------------------
| HEADER + SIDEBAR
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../includes/header.php";
require_once __DIR__ . "/../includes/sidebar.php";

?>

<style>
    :root {
        --bg-page: #0a1119;
        --bg-card: #111b26;
        --bg-card-alt: #0f1823;
        --bg-input: #0d1620;
        --bg-hover: #16222e;
        --border: #1e2d3d;
        --border-soft: #182636;
        --text-hi: #f0f4f8;
        --text-body: #a8b8c8;
        --text-mute: #5f7488;
        --green: #10b981;
        --green-dark: #059669;
        --green-soft: rgba(16, 185, 129, .12);
        --blue: #3b82f6;
        --blue-soft: rgba(59, 130, 246, .12);
        --amber: #f59e0b;
        --amber-soft: rgba(245, 158, 11, .12);
        --red: #ef4444;
        --red-soft: rgba(239, 68, 68, .12);
    }

    html,
    body {
        background: #0a1119 !important;
        color: #a8b8c8 !important;
    }

    .main,
    .content {
        background: var(--bg-page) !important;
    }

    .products-page {
        width: 100%;
        max-width: 1280px;
        margin: 0 auto;
        padding: 20px 20px 45px;
        box-sizing: border-box;
        color: var(--text-body);
    }

    .products-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 24px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--border);
    }

    .products-title {
        margin: 0;
        color: var(--text-hi);
        font-size: 27px;
        line-height: 1.2;
        font-weight: 800;
    }

    .products-subtitle {
        margin: 7px 0 0;
        color: var(--text-mute);
        font-size: 12px;
    }

    .btn-add-product {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 42px;
        padding: 0 20px;
        border-radius: 9px;
        border: 1px solid var(--green-dark);
        background: linear-gradient(135deg, #059669 0%, #10b981 100%);
        color: #fff;
        font-size: 11.5px;
        font-weight: 700;
        text-decoration: none;
        transition: .2s ease;
        box-shadow: 0 4px 12px rgba(16, 185, 129, .2);
    }

    .btn-add-product:hover {
        filter: brightness(1.08);
    }

    .products-table-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 15px 40px rgba(0, 0, 0, .18);
    }

    .products-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .products-table th {
        background: var(--bg-card-alt);
        color: var(--text-mute);
        font-size: 10.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .6px;
        padding: 14px 18px;
        border-bottom: 1px solid var(--border);
    }

    .products-table td {
        padding: 14px 18px;
        border-bottom: 1px solid var(--border-soft);
        font-size: 12px;
        color: var(--text-body);
        vertical-align: middle;
    }

    .products-table tr:hover td {
        background: var(--bg-hover);
    }

    .product-thumb {
        width: 44px;
        height: 44px;
        border-radius: 8px;
        border: 1px solid var(--border);
        object-fit: cover;
        background: var(--bg-input);
    }

    .product-name {
        color: var(--text-hi);
        font-weight: 700;
        font-size: 13px;
    }

    .badge {
        display: inline-flex;
        align-items: center;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .badge-active {
        background: var(--green-soft);
        color: var(--green);
        border: 1px solid rgba(16, 185, 129, .2);
    }

    .badge-inactive {
        background: var(--red-soft);
        color: var(--red);
        border: 1px solid rgba(239, 68, 68, .2);
    }

    .action-links {
        display: flex;
        gap: 8px;
    }

    .btn-action {
        padding: 6px 12px;
        border-radius: 7px;
        font-size: 11px;
        font-weight: 700;
        text-decoration: none;
        border: 1px solid var(--border);
        background: var(--bg-input);
        color: var(--text-body);
        transition: .15s ease;
    }

    .btn-action:hover {
        border-color: var(--blue);
        color: var(--blue);
        background: var(--blue-soft);
    }

    .btn-action.delete:hover {
        border-color: var(--red);
        color: var(--red);
        background: var(--red-soft);
    }

    .empty-state {
        text-align: center;
        padding: 50px 20px;
        color: var(--text-mute);
    }
</style>

<main class="main">
    <section class="content">
        <div class="products-page">

            <div class="products-header">
                <div>
                    <h1 class="products-title">Products Management</h1>
                    <p class="products-subtitle">View, manage, and add new hospitality linen products.</p>
                </div>
                <a href="add.php" class="btn-add-product">
                    <span>+</span>
                    <span>Add New Product</span>
                </a>
            </div>

            <div class="products-table-card">
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Base Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($products)): ?>
                            <?php foreach ($products as $prod): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($prod["MainImage"])): ?>
                                            <img src="../<?= e($prod["MainImage"]) ?>" alt="" class="product-thumb">
                                        <?php else: ?>
                                            <div class="product-thumb" style="display:flex; align-items:center; justify-content:center; color:var(--text-mute); font-size:16px;">📦</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="product-name"><?= e($prod["Name"]) ?></div>
                                        <div style="font-size:10.5px; color:var(--text-mute);"><?= e($prod["Slug"]) ?></div>
                                    </td>
                                    <td><?= e($prod["CategoryName"] ?? "Uncategorized") ?></td>
                                    <td><strong>CAD $<?= number_format((float)$prod["BasePrice"], 2) ?></strong></td>
                                    <td>
                                        <?php if ((int)$prod["IsActive"] === 1): ?>
                                            <span class="badge badge-active">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-inactive">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-links">
                                            <a href="edit.php?id=<?= (int)$prod["ProductId"] ?>" class="btn-action">Edit</a>
                                            <a href="delete.php?id=<?= (int)$prod["ProductId"] ?>" class="btn-action delete" onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    No products found in the database. Click "Add New Product" to create one.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </section>
</main>

<?php
require_once __DIR__ . "/../includes/footer.php";
?>