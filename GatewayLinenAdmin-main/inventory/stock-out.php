<?php

session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$activeMenu = 'inventory';
$pageTitle  = 'GatewayLinen | Stock Out (Reduce Inventory)';

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] = $_SESSION['admin_username'] ?? 'GatewayLinen Administrator';
}

if (!isset($_SESSION['admin_role'])) {
    $_SESSION['admin_role'] = 'Administrator';
}

if (empty($_SESSION['stock_out_csrf_token'])) {
    $_SESSION['stock_out_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['stock_out_csrf_token'];

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| FETCH INVENTORY LINE ITEMS THAT HAVE AVAILABLE STOCK
|--------------------------------------------------------------------------
*/
$invItems = [];
$invSql = "
    SELECT 
        i.InventoryId,
        i.WarehouseId,
        i.VariantId,
        ISNULL(i.StockQty, 0) AS StockQty,
        ISNULL(i.ReservedQty, 0) AS ReservedQty,
        (ISNULL(i.StockQty, 0) - ISNULL(i.ReservedQty, 0)) AS AvailableQty,
        ISNULL(w.Name, 'Main Warehouse') AS WarehouseName,
        ISNULL(p.Name, 'Product #' + CAST(ISNULL(pv.ProductId, i.VariantId) AS VARCHAR(10))) AS ProductName,
        ISNULL(pv.SKU, ISNULL(p.Slug, 'ITEM-' + CAST(i.InventoryId AS VARCHAR(10)))) AS SkuDisplay
    FROM dbo.Inventory i
    LEFT JOIN dbo.Warehouses w ON i.WarehouseId = w.WarehouseId
    LEFT JOIN dbo.ProductVariants pv ON i.VariantId = pv.VariantId
    LEFT JOIN dbo.Products p ON pv.ProductId = p.ProductId
    WHERE (ISNULL(i.StockQty, 0) - ISNULL(i.ReservedQty, 0)) > 0
    ORDER BY ProductName ASC
";

$invStmt = sqlsrv_query($conn, $invSql);
if ($invStmt !== false) {
    while ($row = sqlsrv_fetch_array($invStmt, SQLSRV_FETCH_ASSOC)) {
        $invItems[] = $row;
    }
    sqlsrv_free_stmt($invStmt);
}

/*
|--------------------------------------------------------------------------
| FORM SUBMISSION
|--------------------------------------------------------------------------
*/
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['stock_out_csrf_token'], $postedToken)) {
        $error = "Security verification failed. Please refresh the page.";
    } else {
        $inventoryId = (int)($_POST['inventory_id'] ?? 0);
        $quantity    = (int)($_POST['quantity'] ?? 0);
        $reason      = trim($_POST['reason'] ?? 'Manual Adjustment');
        $referenceNo = trim($_POST['reference_no'] ?? '');
        $notes       = trim($_POST['notes'] ?? '');

        if ($inventoryId <= 0) {
            $error = "Please select an item from inventory.";
        } elseif ($quantity <= 0) {
            $error = "Quantity must be greater than 0.";
        }

        if ($error === "") {
            sqlsrv_begin_transaction($conn);

            // Fetch and lock current inventory record
            $chkSql = "
                SELECT 
                    InventoryId, 
                    VariantId, 
                    WarehouseId, 
                    ISNULL(StockQty, 0) AS StockQty, 
                    ISNULL(ReservedQty, 0) AS ReservedQty, 
                    (ISNULL(StockQty, 0) - ISNULL(ReservedQty, 0)) AS AvailableQty 
                FROM dbo.Inventory 
                WHERE InventoryId = ?
            ";
            $chkStmt = sqlsrv_query($conn, $chkSql, [$inventoryId]);
            $currInv = ($chkStmt !== false) ? sqlsrv_fetch_array($chkStmt, SQLSRV_FETCH_ASSOC) : null;
            if ($chkStmt !== false) sqlsrv_free_stmt($chkStmt);

            if (!$currInv) {
                sqlsrv_rollback($conn);
                $error = "Inventory record not found.";
            } else {
                $avail = (int)$currInv['AvailableQty'];
                if ($quantity > $avail) {
                    sqlsrv_rollback($conn);
                    $error = "Cannot remove {$quantity} units. Only {$avail} units are available.";
                } else {
                    // Deduct stock from inventory
                    $deductSql = "
                        UPDATE dbo.Inventory 
                        SET 
                            StockQty = StockQty - ?,
                            LastRestockedAt = GETDATE()
                        WHERE InventoryId = ?
                    ";
                    $deductStmt = sqlsrv_query($conn, $deductSql, [$quantity, $inventoryId]);

                    if ($deductStmt === false) {
                        sqlsrv_rollback($conn);
                        $errs = sqlsrv_errors();
                        $error = "Failed to update stock: " . ($errs[0]['message'] ?? 'Database error');
                    } else {
                        sqlsrv_free_stmt($deductStmt);

                        // Insert audit trail into dbo.StockMovements
                        $targetVariantId = !empty($currInv['VariantId']) ? (int)$currInv['VariantId'] : null;
                        $targetWarehouseId = !empty($currInv['WarehouseId']) ? (int)$currInv['WarehouseId'] : 1;

                        $movSql = "
                            INSERT INTO dbo.StockMovements (
                                VariantId, WarehouseId, MovementType, Quantity, Reference, Notes, CreatedAt
                            ) VALUES (
                                ?, ?, 'StockOut', ?, ?, ?, GETDATE()
                            )
                        ";
                        $movRef   = $referenceNo !== '' ? $referenceNo : 'Stock Out';
                        $movNotes = "[{$reason}] " . ($notes !== '' ? $notes : "Deducted by " . $_SESSION['admin_name']);
                        
                        $movStmt = @sqlsrv_query($conn, $movSql, [
                            $targetVariantId, 
                            $targetWarehouseId, 
                            -$quantity, 
                            $movRef, 
                            $movNotes
                        ]);

                        if ($movStmt !== false) {
                            sqlsrv_free_stmt($movStmt);
                        }

                        sqlsrv_commit($conn);
                        $_SESSION['stock_out_csrf_token'] = bin2hex(random_bytes(32));
                        header("Location: index.php?success=" . urlencode("Successfully deducted {$quantity} units from inventory."));
                        exit;
                    }
                }
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
    --amber: #f59e0b;
    --amber-dark: #d97706;
    --amber-soft: rgba(245,158,11,.12);
    --red: #ef4444;
    --red-soft: rgba(239,68,68,.12);
}

html, body, .main, .content { background: var(--bg-page) !important; color: var(--text-body) !important; }
.stock-out-page { width: 100%; max-width: 1050px; margin: 0 auto; padding: 0 0 35px; }

.page-header {
    display: flex; align-items: flex-end; justify-content: space-between; gap: 20px;
    margin-bottom: 24px; padding-bottom: 18px; border-bottom: 1px solid var(--border);
}
.breadcrumb { display: flex; gap: 8px; margin-bottom: 8px; color: var(--text-mute); font-size: 11px; font-weight: 700; text-transform: uppercase; }
.breadcrumb .current { color: var(--amber); }
.page-header h1 { margin: 0; color: var(--text-hi); font-size: 26px; font-weight: 800; }
.page-header p { margin: 6px 0 0; color: var(--text-mute); font-size: 12px; }

.btn-back {
    display: inline-flex; align-items: center; gap: 8px; min-height: 40px; padding: 0 16px;
    border: 1px solid var(--border); border-radius: 9px; background: var(--bg-input);
    color: var(--text-body) !important; font-size: 11px; font-weight: 700; text-decoration: none;
}
.btn-back:hover { border-color: var(--amber); background: var(--amber-soft); color: var(--amber) !important; }

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
    border-radius: 8px; background: var(--amber-soft); border-left: 3px solid var(--amber);
}
.form-section:first-child { margin-top: 0; }
.form-section.sec-red { background: var(--red-soft); border-left-color: var(--red); }

.form-section-icon {
    display: flex; align-items: center; justify-content: center; width: 24px; height: 24px;
    border-radius: 6px; background: rgba(245,158,11,.2); color: var(--amber); font-weight: 800; font-size: 13px;
}
.sec-red .form-section-icon { background: rgba(239,68,68,.2); color: var(--red); }
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
.form-input:focus, .form-select:focus, .form-textarea:focus { border-color: var(--amber); box-shadow: 0 0 0 3px rgba(245,158,11,.13); }

.form-select {
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%235f7488' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat; background-position: right 12px center; background-size: 14px; padding-right: 36px; cursor: pointer;
}

.form-select option {
    background: #111b26;
    color: #f0f4f8;
    padding: 8px;
}

.form-footer {
    display: flex; justify-content: flex-end; gap: 10px; padding: 16px 24px;
    border-top: 1px solid var(--border); background: var(--bg-card-alt);
}
.btn-submit {
    display: inline-flex; align-items: center; gap: 8px; min-height: 42px; padding: 0 22px;
    border-radius: 9px; font-size: 11.5px; font-weight: 700; border: 1px solid var(--amber-dark);
    background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%); color: #fff; cursor: pointer;
    transition: all .18s ease;
}
.btn-submit:hover { filter: brightness(1.08); transform: translateY(-1px); }
.btn-cancel {
    display: inline-flex; align-items: center; justify-content: center; min-height: 42px;
    padding: 0 20px; border: 1px solid var(--border); border-radius: 9px; background: var(--bg-input);
    color: var(--text-body) !important; text-decoration: none; font-size: 11.5px; font-weight: 700;
}
</style>

<main class="main">
    <section class="content">
        <div class="stock-out-page">

            <div class="page-header">
                <div>
                    <div class="breadcrumb">
                        <span>Inventory</span> / <span class="current">Stock Out (Reduce Inventory)</span>
                    </div>
                    <h1>Stock Out & Quantity Deduction</h1>
                    <p>Reduce stock count due to damage, wholesale sale, sample, or manual adjustments.</p>
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
                            <div class="form-section-title">Select Item to Deduct</div>
                        </div>

                        <!-- INVENTORY SELECTION -->
                        <div class="form-group form-group-full">
                            <label class="form-label">Inventory Item <span class="form-required">*</span></label>
                            <select name="inventory_id" class="form-select" required>
                                <?php if (empty($invItems)): ?>
                                    <option value="">No inventory items currently in stock (Please Stock-In first)</option>
                                <?php else: ?>
                                    <option value="">Select Item (Product - Warehouse - Available Qty)</option>
                                    <?php foreach ($invItems as $i): ?>
                                        <option value="<?= (int)$i['InventoryId'] ?>">
                                            <?= e($i['ProductName']) ?> (SKU: <?= e($i['SkuDisplay']) ?>) — [<?= e($i['WarehouseName']) ?>] | Available: <?= (int)$i['AvailableQty'] ?> units
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="form-section sec-red">
                            <div class="form-section-icon">−</div>
                            <div class="form-section-title">Quantity & Reason</div>
                        </div>

                        <!-- QUANTITY -->
                        <div class="form-group">
                            <label class="form-label">Quantity to Remove (Units) <span class="form-required">*</span></label>
                            <input type="number" step="1" min="1" name="quantity" class="form-input" placeholder="e.g. 5" required>
                        </div>

                        <!-- REASON -->
                        <div class="form-group">
                            <label class="form-label">Reason for Deduction <span class="form-required">*</span></label>
                            <select name="reason" class="form-select" required>
                                <option value="Damage / Defect">Damage / Defective Fabric</option>
                                <option value="Manual Adjustment">Manual Count Discrepancy</option>
                                <option value="Direct Offline Sale">Direct Wholesale Offline Sale</option>
                                <option value="Sample / Marketing">Showroom Sample / Testing</option>
                                <option value="Loss / Theft">Loss / Shrinkage</option>
                            </select>
                        </div>

                        <!-- REFERENCE -->
                        <div class="form-group">
                            <label class="form-label">Incident / Invoice Ref #</label>
                            <input type="text" name="reference_no" class="form-input" placeholder="e.g. DMG-01" maxlength="50">
                        </div>

                        <!-- NOTES -->
                        <div class="form-group form-group-full">
                            <label class="form-label">Remarks / Audit Note</label>
                            <textarea name="notes" class="form-textarea" placeholder="Explain why stock is being deducted..."></textarea>
                        </div>

                    </div>
                </div>

                <div class="form-footer">
                    <a href="index.php" class="btn-cancel">Cancel</a>
                    <button type="submit" class="btn-submit">− Deduct Stock</button>
                </div>
            </form>

        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>