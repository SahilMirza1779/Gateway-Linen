<?php
session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$activeMenu = 'inventory';
$pageTitle  = 'GatewayLinen | Stock In (Receive Goods)';

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] = $_SESSION['admin_username'] ?? 'GatewayLinen Administrator';
}

if (!isset($_SESSION['admin_role'])) {
    $_SESSION['admin_role'] = 'Administrator';
}

if (empty($_SESSION['stock_csrf_token'])) {
    $_SESSION['stock_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['stock_csrf_token'];

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| FETCH OR ENSURE WAREHOUSE
|--------------------------------------------------------------------------
*/
$warehouses = [];
$wSql = "SELECT WarehouseId, Name FROM dbo.Warehouses WHERE IsActive = 1 ORDER BY WarehouseId ASC";
$wStmt = sqlsrv_query($conn, $wSql);

if ($wStmt !== false) {
    while ($wRow = sqlsrv_fetch_array($wStmt, SQLSRV_FETCH_ASSOC)) {
        $warehouses[] = $wRow;
    }
    sqlsrv_free_stmt($wStmt);
}

// Fallback: Agar DB me koi warehouse na mile toh query all warehouses
if (empty($warehouses)) {
    $wStmtAll = sqlsrv_query($conn, "SELECT WarehouseId, Name FROM dbo.Warehouses");
    if ($wStmtAll !== false) {
        while ($wRow = sqlsrv_fetch_array($wStmtAll, SQLSRV_FETCH_ASSOC)) {
            $warehouses[] = $wRow;
        }
        sqlsrv_free_stmt($wStmtAll);
    }
}

/*
|--------------------------------------------------------------------------
| FETCH PRODUCTS & VARIANTS
|--------------------------------------------------------------------------
*/
$productsList = [];
$pSql = "
    SELECT 
        p.ProductId,
        p.Name AS ProductName,
        p.Slug,
        ISNULL(pv.VariantId, 0) AS VariantId,
        ISNULL(pv.SKU, p.Slug) AS SkuDisplay
    FROM dbo.Products p
    LEFT JOIN dbo.ProductVariants pv ON p.ProductId = pv.ProductId
    ORDER BY p.Name ASC
";
$pStmt = sqlsrv_query($conn, $pSql);
if ($pStmt !== false) {
    while ($pRow = sqlsrv_fetch_array($pStmt, SQLSRV_FETCH_ASSOC)) {
        $productsList[] = $pRow;
    }
    sqlsrv_free_stmt($pStmt);
}

/*
|--------------------------------------------------------------------------
| FORM SUBMISSION
|--------------------------------------------------------------------------
*/
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['stock_csrf_token'], $postedToken)) {
        $error = "Security verification failed. Please refresh the page.";
    } else {
        $selectedItem = trim($_POST['item_identifier'] ?? '');
        $warehouseId  = (int)($_POST['warehouse_id'] ?? 0);
        $quantity     = (int)($_POST['quantity'] ?? 0);
        $binLocation  = trim($_POST['bin_location'] ?? '');
        $referenceNo  = trim($_POST['reference_no'] ?? '');
        $notes        = trim($_POST['notes'] ?? '');

        $parts = explode('_', $selectedItem);
        $productId = isset($parts[0]) ? (int)$parts[0] : 0;
        $variantId = isset($parts[1]) ? (int)$parts[1] : 0;

        if ($productId <= 0) {
            $error = "Please select a product.";
        } elseif ($warehouseId <= 0) {
            $error = "Please select a destination warehouse. (Please add one in Warehouses table if empty)";
        } elseif ($quantity <= 0) {
            $error = "Quantity must be greater than 0.";
        }

        if ($error === "") {
            sqlsrv_begin_transaction($conn);

            // 1. Ensure Variant exists
            if ($variantId <= 0) {
                $chkVar = sqlsrv_query($conn, "SELECT TOP 1 VariantId FROM dbo.ProductVariants WHERE ProductId = ?", [$productId]);
                if ($chkVar !== false && $vRow = sqlsrv_fetch_array($chkVar, SQLSRV_FETCH_ASSOC)) {
                    $variantId = (int)$vRow['VariantId'];
                    sqlsrv_free_stmt($chkVar);
                } else {
                    $getProd = sqlsrv_query($conn, "SELECT Slug, BasePrice FROM dbo.Products WHERE ProductId = ?", [$productId]);
                    $pData = sqlsrv_fetch_array($getProd, SQLSRV_FETCH_ASSOC);
                    sqlsrv_free_stmt($getProd);

                    $sku = !empty($pData['Slug']) ? strtoupper($pData['Slug']) : ("SKU-" . $productId);
                    $price = isset($pData['BasePrice']) ? (float)$pData['BasePrice'] : 0.0;

                    $insVarSql = "INSERT INTO dbo.ProductVariants (ProductId, SKU, Size, Color, Price, IsActive, CreatedAt) OUTPUT INSERTED.VariantId VALUES (?, ?, 'Standard', 'Default', ?, 1, GETDATE())";
                    $insVarStmt = sqlsrv_query($conn, $insVarSql, [$productId, $sku, $price]);
                    if ($insVarStmt !== false && $newV = sqlsrv_fetch_array($insVarStmt, SQLSRV_FETCH_ASSOC)) {
                        $variantId = (int)$newV['VariantId'];
                        sqlsrv_free_stmt($insVarStmt);
                    } else {
                        $errs = sqlsrv_errors();
                        $error = "Variant creation failed: " . ($errs[0]['message'] ?? 'Error');
                    }
                }
            }

            // 2. Insert / Update Inventory
            if ($error === "" && $variantId > 0 && $warehouseId > 0) {
                $chkInvSql = "SELECT InventoryId, StockQty FROM dbo.Inventory WHERE VariantId = ? AND WarehouseId = ?";
                $chkInvStmt = sqlsrv_query($conn, $chkInvSql, [$variantId, $warehouseId]);
                $existingInv = ($chkInvStmt !== false) ? sqlsrv_fetch_array($chkInvStmt, SQLSRV_FETCH_ASSOC) : null;
                if ($chkInvStmt !== false) sqlsrv_free_stmt($chkInvStmt);

                if ($existingInv) {
                    $invId = (int)$existingInv['InventoryId'];
                    $upSql = "
                        UPDATE dbo.Inventory 
                        SET StockQty = StockQty + ?,
                            BinLocation = CASE WHEN ? <> '' THEN ? ELSE BinLocation END,
                            LastRestockedAt = GETDATE()
                        WHERE InventoryId = ?
                    ";
                    $upStmt = sqlsrv_query($conn, $upSql, [$quantity, $binLocation, $binLocation, $invId]);
                    if ($upStmt === false) {
                        $errs = sqlsrv_errors();
                        $error = "Inventory update failed: " . ($errs[0]['message'] ?? 'Error');
                    }
                } else {
                    $inSql = "
                        INSERT INTO dbo.Inventory (
                            WarehouseId, VariantId, StockQty, ReservedQty, BinLocation, LastRestockedAt
                        ) VALUES (?, ?, ?, 0, ?, GETDATE())
                    ";
                    $inStmt = sqlsrv_query($conn, $inSql, [$warehouseId, $variantId, $quantity, $binLocation !== '' ? $binLocation : null]);
                    if ($inStmt === false) {
                        $errs = sqlsrv_errors();
                        $error = "Inventory insert failed: " . ($errs[0]['message'] ?? 'Error');
                    }
                }
            }

            // 3. Stock Movement
            if ($error === "") {
                $movSql = "INSERT INTO dbo.StockMovements (VariantId, WarehouseId, MovementType, Quantity, Reference, Notes, CreatedAt) VALUES (?, ?, 'StockIn', ?, ?, ?, GETDATE())";
                $refText = $referenceNo !== '' ? $referenceNo : 'Manual Stock In';
                $noteText = $notes !== '' ? $notes : ("Added {$quantity} units by " . $_SESSION['admin_name']);
                @sqlsrv_query($conn, $movSql, [$variantId, $warehouseId, $quantity, $refText, $noteText]);

                sqlsrv_commit($conn);
                $_SESSION['stock_csrf_token'] = bin2hex(random_bytes(32));
                header("Location: index.php?success=" . urlencode("Successfully added {$quantity} units to inventory."));
                exit;
            } else {
                sqlsrv_rollback($conn);
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<style>
:root {
    --bg-page: #0a1119;
    --bg-card: #111b26;
    --bg-card-alt: #0f1823;
    --bg-input: #0d1620;
    --border: #1e2d3d;
    --text-hi: #f0f4f8;
    --text-body: #a8b8c8;
    --text-mute: #5f7488;
    --green: #10b981;
    --green-dark: #059669;
    --green-soft: rgba(16,185,129,.12);
    --blue: #3b82f6;
    --blue-soft: rgba(59,130,246,.12);
    --red: #ef4444;
    --red-soft: rgba(239,68,68,.12);
}

html, body, .main, .content { background: var(--bg-page) !important; color: var(--text-body) !important; }
.stock-in-page { width: 100%; max-width: 1050px; margin: 0 auto; padding: 0 0 35px; }

.page-header {
    display: flex; align-items: flex-end; justify-content: space-between; gap: 20px;
    margin-bottom: 24px; padding-bottom: 18px; border-bottom: 1px solid var(--border);
}
.breadcrumb { display: flex; gap: 8px; margin-bottom: 8px; color: var(--text-mute); font-size: 11px; font-weight: 700; text-transform: uppercase; }
.breadcrumb .current { color: var(--green); }
.page-header h1 { margin: 0; color: var(--text-hi); font-size: 26px; font-weight: 800; }
.page-header p { margin: 6px 0 0; color: var(--text-mute); font-size: 12px; }

.btn-back {
    display: inline-flex; align-items: center; gap: 8px; min-height: 40px; padding: 0 16px;
    border: 1px solid var(--border); border-radius: 9px; background: var(--bg-input);
    color: var(--text-body) !important; font-size: 11px; font-weight: 700; text-decoration: none;
}
.btn-back:hover { border-color: var(--green); background: var(--green-soft); color: var(--green) !important; }

.notice-error {
    display: flex; align-items: center; gap: 10px; margin-bottom: 20px; padding: 14px 16px;
    border: 1px solid rgba(239,68,68,.3); border-left: 4px solid var(--red); border-radius: 9px;
    background: var(--red-soft); color: #fca5a5; font-size: 12px; font-weight: 600;
}

.form-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
.form-body { padding: 24px; }
.form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px 24px; }

.form-section {
    grid-column: 1 / -1; display: flex; align-items: center; gap: 10px; margin-top: 10px; padding: 10px 14px;
    border-radius: 8px; background: var(--green-soft); border-left: 3px solid var(--green);
}
.form-section:first-child { margin-top: 0; }
.form-section.sec-blue { background: var(--blue-soft); border-left-color: var(--blue); }

.form-section-icon {
    display: flex; align-items: center; justify-content: center; width: 24px; height: 24px;
    border-radius: 6px; background: rgba(16,185,129,.18); color: var(--green); font-weight: 800; font-size: 12px;
}
.sec-blue .form-section-icon { background: rgba(59,130,246,.18); color: var(--blue); }
.form-section-title { color: var(--text-hi); font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .8px; }

.form-group { min-width: 0; }
.form-group-full { grid-column: 1 / -1; }
.form-label { display: block; margin-bottom: 7px; color: var(--text-body); font-size: 11px; font-weight: 700; text-transform: uppercase; }
.form-required { color: var(--red); }

.form-input, .form-select, .form-textarea {
    width: 100%; box-sizing: border-box; border: 1px solid var(--border); border-radius: 9px;
    background: var(--bg-input); color: var(--text-hi); font-family: inherit; font-size: 12px; outline: none;
}
.form-input, .form-select { height: 42px; padding: 0 13px; }
.form-textarea { min-height: 85px; padding: 10px 13px; resize: vertical; line-height: 1.5; }
.form-input:focus, .form-select:focus, .form-textarea:focus { border-color: var(--green); box-shadow: 0 0 0 3px rgba(16,185,129,.13); }

.form-select {
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%235f7488' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat; background-position: right 12px center; background-size: 14px; padding-right: 36px; cursor: pointer;
}

.form-select option { background: #111b26; color: #f0f4f8; padding: 8px; }

.form-footer {
    display: flex; justify-content: flex-end; gap: 10px; padding: 16px 24px;
    border-top: 1px solid var(--border); background: var(--bg-card-alt);
}
.btn-submit {
    display: inline-flex; align-items: center; gap: 8px; min-height: 42px; padding: 0 22px;
    border-radius: 9px; font-size: 11.5px; font-weight: 700; border: 1px solid var(--green-dark);
    background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: #fff; cursor: pointer;
}
.btn-cancel {
    display: inline-flex; align-items: center; justify-content: center; min-height: 42px;
    padding: 0 20px; border: 1px solid var(--border); border-radius: 9px; background: var(--bg-input);
    color: var(--text-body) !important; text-decoration: none; font-size: 11.5px; font-weight: 700;
}
</style>

<main class="main">
    <section class="content">
        <div class="stock-in-page">

            <div class="page-header">
                <div>
                    <div class="breadcrumb">
                        <span>Inventory</span> / <span class="current">Stock In (Receive Goods)</span>
                    </div>
                    <h1>Receive Stock & Inventory Replenishment</h1>
                    <p>Select your product, warehouse location, and enter quantity received.</p>
                </div>
                <a href="index.php" class="btn-back">← Back to Inventory</a>
            </div>

            <?php if ($error !== ""): ?>
                <div class="notice-error">
                    <span>!</span> <div><?= e($error) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off" class="form-card">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

                <div class="form-body">
                    <div class="form-grid">

                        <div class="form-section">
                            <div class="form-section-icon">📦</div>
                            <div class="form-section-title">Product & Destination</div>
                        </div>

                        <!-- PRODUCT SELECTION -->
                        <div class="form-group form-group-full">
                            <label class="form-label">Product to Restock <span class="form-required">*</span></label>
                            <select name="item_identifier" class="form-select" required>
                                <option value="">Select Product from Catalog</option>
                                <?php foreach ($productsList as $p): ?>
                                    <?php $optVal = $p['ProductId'] . '_' . $p['VariantId']; ?>
                                    <option value="<?= e($optVal) ?>">
                                        <?= e($p['ProductName']) ?> (SKU: <?= e($p['SkuDisplay']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- WAREHOUSE SELECTION -->
                        <div class="form-group">
                            <label class="form-label">Destination Warehouse <span class="form-required">*</span></label>
                            <select name="warehouse_id" class="form-select" required>
                                <option value="">Select Warehouse</option>
                                <?php foreach ($warehouses as $wh): ?>
                                    <option value="<?= (int)$wh['WarehouseId'] ?>">
                                        <?= e($wh['Name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- RACK LOCATION -->
                        <div class="form-group">
                            <label class="form-label">Bin / Rack Location</label>
                            <input type="text" name="bin_location" class="form-input" placeholder="e.g. Rack-A1, Shelf-2" maxlength="50">
                        </div>

                        <div class="form-section sec-blue">
                            <div class="form-section-icon">＋</div>
                            <div class="form-section-title">Quantity & Details</div>
                        </div>

                        <!-- QUANTITY -->
                        <div class="form-group">
                            <label class="form-label">Quantity to Add (Units) <span class="form-required">*</span></label>
                            <input type="number" step="1" min="1" name="quantity" class="form-input" placeholder="e.g. 100" required>
                        </div>

                        <!-- REFERENCE -->
                        <div class="form-group">
                            <label class="form-label">PO / Delivery Challan Ref #</label>
                            <input type="text" name="reference_no" class="form-input" placeholder="e.g. PO-9801" maxlength="50">
                        </div>

                        <!-- NOTES -->
                        <div class="form-group form-group-full">
                            <label class="form-label">Notes / Remarks</label>
                            <textarea name="notes" class="form-textarea" placeholder="Supplier name or batch details..."></textarea>
                        </div>

                    </div>
                </div>

                <div class="form-footer">
                    <a href="index.php" class="btn-cancel">Cancel</a>
                    <button type="submit" class="btn-submit">✓ Receive & Update Stock</button>
                </div>
            </form>

        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>