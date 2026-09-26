<?php
session_start();

if (empty($_SESSION["admin_id"])) {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$activeMenu = "variants";
$pageTitle  = "GatewayLinen | Add Product Variant";

if (!isset($_SESSION["admin_name"])) {
    $_SESSION["admin_name"] = $_SESSION["admin_username"] ?? "GatewayLinen Administrator";
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$actionMessage = "";
$actionType = "";

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
        $insSql = "INSERT INTO dbo.ProductVariants 
            (ProductId, SKU, Barcode, Size, Color, ThreadCount, Material, WeightGSM, Dimensions, Price, CompareAtPrice, WholesalePrice, CostPrice, WeightKg, LowStockThreshold, IsActive) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

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
            $isActive
        ];

        $insStmt = sqlsrv_query($conn, $insSql, $params);

        if ($insStmt !== false) {
            sqlsrv_free_stmt($insStmt);
            header("Location: index.php?msg=added");
            exit;
        } else {
            $actionMessage = "Failed to add variant. SKU/Barcode might already exist.";
            $actionType = "error";
        }
    } else {
        $actionMessage = "Product and SKU are required.";
        $actionType = "error";
    }
}

// Fetch Products for the Dropdown
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
        --text-hi: #f0f4f8;
        --text-body: #a8b8c8;
        --text-mute: #5f7488;
        --green: #10b981;
        --green-hover: #059669;
        --red: #ef4444;
        --gold: #f59e0b;
        --cyan: #06b6d4;
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

    .add-variant-page {
        width: 100%;
        max-width: 1200px;
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

    .page-header {
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

    .btn-back {
        background: transparent;
        color: var(--text-hi);
        border: 1px solid var(--border);
        padding: 8px 14px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        text-decoration: none;
        transition: 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-back:hover {
        border-color: var(--text-mute);
    }

    .alert-error {
        background: rgba(239, 68, 68, 0.1);
        color: var(--red);
        border: 1px solid rgba(239, 68, 68, 0.2);
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 20px;
    }

    .form-container {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .form-section {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .section-header {
        background: var(--bg-section);
        padding: 14px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid var(--border);
    }

    .icon-box {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 24px;
        height: 24px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 800;
    }

    .icon-green {
        background: rgba(16, 185, 129, 0.15);
        color: var(--green);
    }

    .icon-gold {
        background: rgba(245, 158, 11, 0.15);
        color: var(--gold);
    }

    .icon-cyan {
        background: rgba(6, 182, 212, 0.15);
        color: var(--cyan);
    }

    .section-title {
        color: var(--text-hi);
        font-size: 13px;
        font-weight: 800;
        letter-spacing: 0.5px;
    }

    .section-body {
        padding: 24px;
    }

    .grid-2-col {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
    }

    .grid-3-col {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 20px;
    }

    @media (max-width: 768px) {

        .grid-2-col,
        .grid-3-col {
            grid-template-columns: 1fr;
        }
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
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }

    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--text-hi);
        font-size: 13px;
        cursor: pointer;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 16px;
        padding: 24px 0;
    }

    .btn-cancel {
        background: transparent;
        color: var(--text-hi);
        border: 1px solid var(--border);
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        transition: 0.2s;
    }

    .btn-cancel:hover {
        background: var(--bg-card);
        border-color: var(--text-mute);
    }

    .btn-save {
        background: var(--green);
        color: #fff;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 800;
        cursor: pointer;
        transition: 0.2s;
    }

    .btn-save:hover {
        background: var(--green-hover);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
</style>

<main class="main">
    <section class="content">
        <div class="add-variant-page">

            <div class="breadcrumb">
                DASHBOARD &nbsp;&rsaquo;&nbsp; VARIANTS &nbsp;&rsaquo;&nbsp; <span>ADD VARIANT</span>
            </div>

            <div class="page-header">
                <div>
                    <h1 class="page-title">Add New Variant</h1>
                    <p class="page-subtitle">Create a new variant and map it to an existing product.</p>
                </div>
                <div>
                    <a href="index.php" class="btn-back">&larr; Back to Variants</a>
                </div>
            </div>

            <?php if (!empty($actionMessage)): ?>
                <div class="alert-error"><?= e($actionMessage) ?></div>
            <?php endif; ?>

            <form method="POST" class="form-container">

                <!-- Section 1: Basic Info -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="icon-box icon-green">#</div>
                        <div class="section-title">BASIC INFORMATION</div>
                    </div>
                    <div class="section-body">
                        <div class="grid-2-col">
                            <div class="form-group">
                                <label class="form-label">Base Product <span class="req">*</span></label>
                                <select name="product_id" class="form-control" required>
                                    <option value="">Select Base Product</option>
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?= (int)$p['ProductId'] ?>"><?= e($p['Name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">SKU / Variant Code <span class="req">*</span></label>
                                <input type="text" name="sku" class="form-control" placeholder="e.g. BED-WHT-01" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Barcode / UPC</label>
                                <input type="text" name="barcode" class="form-control" placeholder="e.g. 890123456789">
                            </div>
                            <div class="form-group" style="justify-content: center;">
                                <label class="checkbox-label" style="margin-top: 20px;">
                                    <input type="checkbox" name="is_active" value="1" checked style="width: 18px; height: 18px; accent-color: var(--green);">
                                    Active Variant (Visible on site)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Attributes -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="icon-box icon-cyan">&#9776;</div>
                        <div class="section-title">ATTRIBUTES & SPECIFICATIONS</div>
                    </div>
                    <div class="section-body">
                        <div class="grid-3-col">
                            <div class="form-group"><label class="form-label">Size</label><input type="text" name="size" class="form-control" placeholder="e.g. King, Queen"></div>
                            <div class="form-group"><label class="form-label">Color</label><input type="text" name="color" class="form-control" placeholder="e.g. White, Blue"></div>
                            <div class="form-group"><label class="form-label">Material</label><input type="text" name="material" class="form-control" placeholder="e.g. 100% Cotton"></div>
                            <div class="form-group"><label class="form-label">Thread Count</label><input type="text" name="thread_count" class="form-control" placeholder="e.g. 400 TC"></div>
                            <div class="form-group"><label class="form-label">Weight (GSM)</label><input type="text" name="weight_gsm" class="form-control" placeholder="e.g. 600 GSM"></div>
                            <div class="form-group"><label class="form-label">Dimensions</label><input type="text" name="dimensions" class="form-control" placeholder="e.g. 90x100 inches"></div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Pricing & Stock -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="icon-box icon-gold">$</div>
                        <div class="section-title">PRICING & INVENTORY</div>
                    </div>
                    <div class="section-body">
                        <div class="grid-3-col">
                            <div class="form-group"><label class="form-label">Selling Price ($) <span class="req">*</span></label><input type="number" step="0.01" name="price" class="form-control" placeholder="0.00" required></div>
                            <div class="form-group"><label class="form-label">Compare At Price ($)</label><input type="number" step="0.01" name="compare_at_price" class="form-control" placeholder="0.00"></div>
                            <div class="form-group"><label class="form-label">Wholesale Price ($)</label><input type="number" step="0.01" name="wholesale_price" class="form-control" placeholder="0.00"></div>
                            <div class="form-group"><label class="form-label">Cost Price ($)</label><input type="number" step="0.01" name="cost_price" class="form-control" placeholder="0.00"></div>
                            <div class="form-group"><label class="form-label">Weight (Kg)</label><input type="number" step="0.01" name="weight_kg" class="form-control" placeholder="e.g. 1.5"></div>
                            <div class="form-group"><label class="form-label">Low Stock Threshold</label><input type="number" name="low_stock_threshold" class="form-control" value="5"></div>
                        </div>
                    </div>
                </div>

                <!-- Action Bar -->
                <div class="form-actions">
                    <a href="index.php" class="btn-cancel">Cancel</a>
                    <button type="submit" class="btn-save">&#10004; Save Variant</button>
                </div>

            </form>

        </div>
    </section>
</main>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>