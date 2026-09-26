<?php
session_start();

if (empty($_SESSION["admin_id"])) {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$activeMenu = "variants";
$pageTitle  = "GatewayLinen | Edit Variant";

if (!isset($_SESSION["admin_name"])) {
    $_SESSION["admin_name"] = $_SESSION["admin_username"] ?? "GatewayLinen Administrator";
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$actionMessage = "";
$actionType = "";
$variantId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($variantId === 0) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $productId = (int)$_POST["product_id"];
    $sku = trim($_POST["sku"]);
    $barcode = trim($_POST["barcode"]);
    $size = trim($_POST["size"]);
    $color = trim($_POST["color"]);
    $threadCount = trim($_POST["thread_count"]);
    $material = trim($_POST["material"]);
    $weightGsm = trim($_POST["weight_gsm"]);
    $dimensions = trim($_POST["dimensions"]);

    $price = (float)$_POST["price"];
    $compareAtPrice = !empty($_POST["compare_at_price"]) ? (float)$_POST["compare_at_price"] : null;
    $wholesalePrice = !empty($_POST["wholesale_price"]) ? (float)$_POST["wholesale_price"] : null;
    $costPrice = !empty($_POST["cost_price"]) ? (float)$_POST["cost_price"] : null;

    $weightKg = !empty($_POST["weight_kg"]) ? (float)$_POST["weight_kg"] : null;
    $lowStockThreshold = !empty($_POST["low_stock_threshold"]) ? (int)$_POST["low_stock_threshold"] : 5;
    $isActive = isset($_POST["is_active"]) ? 1 : 0;

    if ($productId > 0 && !empty($sku)) {
        $updSql = "UPDATE dbo.ProductVariants 
            SET ProductId=?, SKU=?, Barcode=?, Size=?, Color=?, ThreadCount=?, Material=?, WeightGSM=?, Dimensions=?, 
            Price=?, CompareAtPrice=?, WholesalePrice=?, CostPrice=?, WeightKg=?, LowStockThreshold=?, IsActive=? 
            WHERE VariantId=?";

        $params = [
            $productId,
            $sku,
            $barcode,
            $size,
            $color,
            $threadCount,
            $material,
            $weightGsm,
            $dimensions,
            $price,
            $compareAtPrice,
            $wholesalePrice,
            $costPrice,
            $weightKg,
            $lowStockThreshold,
            $isActive,
            $variantId
        ];

        $updStmt = sqlsrv_query($conn, $updSql, $params);

        if ($updStmt !== false) {
            sqlsrv_free_stmt($updStmt);
            header("Location: index.php?msg=updated");
            exit;
        } else {
            $actionMessage = "Failed to update variant. SKU might already exist.";
            $actionType = "error";
        }
    } else {
        $actionMessage = "Product and SKU are required.";
        $actionType = "error";
    }
}

// Fetch Current Variant Data
$variantData = null;
$fetchSql = "SELECT * FROM dbo.ProductVariants WHERE VariantId = ?";
$fetchStmt = sqlsrv_query($conn, $fetchSql, [$variantId]);
if ($fetchStmt !== false && $row = sqlsrv_fetch_array($fetchStmt, SQLSRV_FETCH_ASSOC)) {
    $variantData = $row;
    sqlsrv_free_stmt($fetchStmt);
} else {
    header("Location: index.php");
    exit;
}

// Fetch Products for Dropdown
$products = [];
$prodSql = "SELECT ProductId, Name FROM dbo.Products ORDER BY Name ASC";
$prodStmt = sqlsrv_query($conn, $prodSql);
if ($prodStmt !== false) {
    while ($row = sqlsrv_fetch_array($prodStmt, SQLSRV_FETCH_ASSOC)) {
        $products[] = $row;
    }
    sqlsrv_free_stmt($prodStmt);
}

require_once __DIR__ . "/../includes/header.php";
require_once __DIR__ . "/../includes/sidebar.php";
?>

<style>
    :root {
        --bg-page: #0a1119;
        --bg-card: #111b26;
        --bg-section: #0f1c29;
        --bg-input: #0d1620;
        --border: #1e2d3d;
        --border-soft: #182636;
        --text-hi: #f0f4f8;
        --text-body: #a8b8c8;
        --text-mute: #5f7488;
        --green: #10b981;
        --green-hover: #059669;
        --green-soft: rgba(16, 185, 129, .12);
        --red: #ef4444;
        --red-soft: rgba(239, 68, 68, .12);
        --cyan: #06b6d4;
        --gold: #f59e0b;
    }

    html,
    body {
        background: var(--bg-page) !important;
        color: var(--text-body) !important;
        font-family: sans-serif;
    }

    .main,
    .content {
        background: var(--bg-page) !important;
    }

    .edit-page-container {
        width: 100%;
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px 20px 60px;
        box-sizing: border-box;
    }

    .breadcrumb {
        font-size: 10px;
        font-weight: 800;
        color: var(--text-mute);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 8px;
    }

    .breadcrumb span {
        color: var(--green);
    }

    .header-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 24px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--border);
    }

    .page-title {
        margin: 0;
        color: var(--text-hi);
        font-size: 26px;
        font-weight: 800;
    }

    .page-subtitle {
        margin: 6px 0 0;
        color: var(--text-mute);
        font-size: 12px;
    }

    .top-action-btns {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .btn-back {
        background: transparent;
        color: var(--text-hi);
        border: 1px solid var(--border);
        padding: 10px 18px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        transition: 0.2s;
    }

    .btn-back:hover {
        border-color: var(--text-mute);
    }

    .btn-save-top {
        background: var(--green);
        color: #fff;
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 800;
        border: none;
        cursor: pointer;
        transition: 0.2s;
    }

    .btn-save-top:hover {
        background: var(--green-hover);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .alert-error {
        background: var(--red-soft);
        color: var(--red);
        border: 1px solid rgba(239, 68, 68, 0.2);
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 24px;
    }

    .edit-grid {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 24px;
        align-items: start;
    }

    @media (max-width: 1000px) {
        .edit-grid {
            grid-template-columns: 1fr;
        }
    }

    .panel-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        overflow: hidden;
        margin-bottom: 24px;
    }

    .panel-header {
        padding: 18px 24px;
        border-bottom: 1px solid var(--border);
        background: var(--bg-section);
    }

    .panel-title {
        color: var(--text-hi);
        font-size: 16px;
        font-weight: 800;
        margin: 0;
    }

    .panel-body {
        padding: 24px;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .grid-2-col {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .form-label {
        font-size: 10.5px;
        font-weight: 800;
        color: var(--text-hi);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .req {
        color: var(--red);
        margin-left: 2px;
    }

    .form-control {
        width: 100%;
        padding: 14px 16px;
        background: var(--bg-input);
        border: 1px solid var(--border);
        border-radius: 8px;
        color: var(--text-hi);
        font-size: 13px;
        box-sizing: border-box;
        transition: 0.2s;
        font-family: inherit;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--green);
    }

    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--text-hi);
        font-size: 13px;
        cursor: pointer;
    }

    .summary-box {
        background: var(--bg-section);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 16px;
        margin-top: 10px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        font-size: 12px;
        margin-bottom: 10px;
        color: var(--text-body);
    }

    .summary-total {
        display: flex;
        justify-content: space-between;
        font-size: 14px;
        font-weight: 800;
        color: var(--green);
        padding-top: 10px;
        border-top: 1px dashed var(--border-soft);
    }
</style>

<main class="main">
    <section class="content">
        <div class="edit-page-container">
            <div class="breadcrumb">
                VARIANTS &nbsp;&rsaquo;&nbsp; <span>EDIT VARIANT</span>
            </div>

            <form method="POST" id="editVariantForm">
                <div class="header-row">
                    <div>
                        <h1 class="page-title">Edit Variant</h1>
                        <p class="page-subtitle">Modify variant info, SKU code, attributes and pricing.</p>
                    </div>
                    <div class="top-action-btns">
                        <a href="index.php" class="btn-back">&larr; Back</a>
                        <button type="submit" class="btn-save-top">&#10004; Save Changes</button>
                    </div>
                </div>

                <?php if (!empty($actionMessage)): ?>
                    <div class="alert-error"><?= e($actionMessage) ?></div>
                <?php endif; ?>

                <div class="edit-grid">

                    <!-- Left Column: General & Attributes -->
                    <div>
                        <div class="panel-card">
                            <div class="panel-header">
                                <h3 class="panel-title">General Information</h3>
                            </div>
                            <div class="panel-body">
                                <div class="grid-2-col">
                                    <div class="form-group">
                                        <label class="form-label">Base Product <span class="req">*</span></label>
                                        <select name="product_id" class="form-control" required>
                                            <option value="">Select Base Product</option>
                                            <?php foreach ($products as $p): ?>
                                                <option value="<?= (int)$p['ProductId'] ?>" <?= ($p['ProductId'] == $variantData['ProductId']) ? 'selected' : '' ?>>
                                                    <?= e($p['Name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group"><label class="form-label">SKU / Variant Code <span class="req">*</span></label><input type="text" name="sku" class="form-control" value="<?= e($variantData['SKU']) ?>" required></div>
                                    <div class="form-group"><label class="form-label">Barcode / UPC</label><input type="text" name="barcode" class="form-control" value="<?= e($variantData['Barcode']) ?>"></div>
                                    <div class="form-group" style="justify-content: center;">
                                        <label class="checkbox-label" style="margin-top: 20px;">
                                            <input type="checkbox" name="is_active" value="1" <?= $variantData['IsActive'] ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--green);">
                                            Active Variant
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="panel-card">
                            <div class="panel-header">
                                <h3 class="panel-title">Attributes & Specifications</h3>
                            </div>
                            <div class="panel-body grid-2-col">
                                <div class="form-group"><label class="form-label">Size</label><input type="text" name="size" class="form-control" value="<?= e($variantData['Size']) ?>"></div>
                                <div class="form-group"><label class="form-label">Color</label><input type="text" name="color" class="form-control" value="<?= e($variantData['Color']) ?>"></div>
                                <div class="form-group"><label class="form-label">Material</label><input type="text" name="material" class="form-control" value="<?= e($variantData['Material']) ?>"></div>
                                <div class="form-group"><label class="form-label">Thread Count</label><input type="text" name="thread_count" class="form-control" value="<?= e($variantData['ThreadCount']) ?>"></div>
                                <div class="form-group"><label class="form-label">Weight (GSM)</label><input type="text" name="weight_gsm" class="form-control" value="<?= e($variantData['WeightGSM']) ?>"></div>
                                <div class="form-group"><label class="form-label">Dimensions</label><input type="text" name="dimensions" class="form-control" value="<?= e($variantData['Dimensions']) ?>"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Pricing & Stock -->
                    <div>
                        <div class="panel-card">
                            <div class="panel-header">
                                <h3 class="panel-title">Pricing & Stock</h3>
                            </div>
                            <div class="panel-body">
                                <div class="form-group">
                                    <label class="form-label">Selling Price ($) <span class="req">*</span></label>
                                    <input type="number" step="0.01" name="price" id="priceInput" class="form-control" value="<?= number_format((float)$variantData['Price'], 2, '.', '') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Compare At Price ($)</label>
                                    <input type="number" step="0.01" name="compare_at_price" class="form-control" value="<?= isset($variantData['CompareAtPrice']) ? number_format((float)$variantData['CompareAtPrice'], 2, '.', '') : '' ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Cost Price ($)</label>
                                    <input type="number" step="0.01" name="cost_price" class="form-control" value="<?= isset($variantData['CostPrice']) ? number_format((float)$variantData['CostPrice'], 2, '.', '') : '' ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Wholesale Price ($)</label>
                                    <input type="number" step="0.01" name="wholesale_price" class="form-control" value="<?= isset($variantData['WholesalePrice']) ? number_format((float)$variantData['WholesalePrice'], 2, '.', '') : '' ?>">
                                </div>

                                <div class="grid-2-col" style="margin-top: 10px;">
                                    <div class="form-group"><label class="form-label">Weight (Kg)</label><input type="number" step="0.01" name="weight_kg" class="form-control" value="<?= isset($variantData['WeightKg']) ? number_format((float)$variantData['WeightKg'], 3, '.', '') : '' ?>"></div>
                                    <div class="form-group"><label class="form-label">Low Stock Alert</label><input type="number" name="low_stock_threshold" class="form-control" value="<?= (int)$variantData['LowStockThreshold'] ?>"></div>
                                </div>

                                <div class="summary-box">
                                    <div class="summary-row">
                                        <span>Current Saved Price:</span>
                                        <span>$<?= number_format((float)$variantData['Price'], 2) ?></span>
                                    </div>
                                    <div class="summary-total">
                                        <span>Final Variant Price:</span>
                                        <span id="finalPriceDisplay">$<?= number_format((float)$variantData['Price'], 2) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </form>

        </div>
    </section>
</main>

<script>
    document.getElementById('priceInput').addEventListener('input', function(e) {
        let val = parseFloat(e.target.value) || 0;
        document.getElementById('finalPriceDisplay').innerText = '$' + val.toFixed(2);
    });
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>