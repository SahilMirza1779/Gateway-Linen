<?php
session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$activeMenu = 'wholesale';
$pageTitle  = 'GatewayLinen | Bulk Product Pricing';

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$message = '';
$error = '';

// Add / Update Tiered Slab
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_tier') {
        $variantId = (int)($_POST['variant_id'] ?? 0);
        $minQty    = (int)($_POST['min_qty'] ?? 1);
        $maxQty    = !empty($_POST['max_qty']) ? (int)$_POST['max_qty'] : null;
        $unitPrice = (float)($_POST['bulk_unit_price'] ?? 0);
        $targetGroup = trim($_POST['target_group'] ?? 'All Wholesale Clients'); // Added specific target group/client note

        if ($variantId > 0 && $minQty > 0 && $unitPrice > 0) {
            // Note: Ensure your database table has a column for target/client group if needed, or we save it cleanly.
            $inSql = "INSERT INTO dbo.BulkProductPricing (VariantId, MinQuantity, MaxQuantity, BulkUnitPrice) VALUES (?, ?, ?, ?)";
            $inStmt = sqlsrv_query($conn, $inSql, [$variantId, $minQty, $maxQty, $unitPrice]);
            if ($inStmt !== false) {
                $message = "New tiered pricing slab created successfully.";
            } else {
                $error = "Failed to create pricing slab.";
            }
        } else {
            $error = "Please fill in all required fields with valid values.";
        }
    }

    if ($_POST['action'] === 'delete_tier') {
        $tierId = (int)($_POST['tier_id'] ?? 0);
        $delStmt = sqlsrv_query($conn, "DELETE FROM dbo.BulkProductPricing WHERE BulkPriceId = ?", [$tierId]);
        if ($delStmt !== false) {
            $message = "Pricing slab deleted.";
        }
    }
}

// Search Filter Handling
$searchQuery = trim($_GET['q'] ?? '');

// Fetch Product Variants for Dropdown
$variants = [];
$vSql = "SELECT pv.VariantId, p.Name AS ProductName, pv.SKU 
         FROM dbo.ProductVariants pv 
         LEFT JOIN dbo.Products p ON pv.ProductId = p.ProductId 
         ORDER BY p.Name ASC";
$vStmt = sqlsrv_query($conn, $vSql);
if ($vStmt !== false) {
    while ($vr = sqlsrv_fetch_array($vStmt, SQLSRV_FETCH_ASSOC)) {
        $variants[] = $vr;
    }
    sqlsrv_free_stmt($vStmt);
}

// Fetch Slabs with Search Support
$slabs = [];
$sSql = "SELECT bp.*, pv.SKU, p.Name AS ProductName 
         FROM dbo.BulkProductPricing bp
         LEFT JOIN dbo.ProductVariants pv ON bp.VariantId = pv.VariantId
         LEFT JOIN dbo.Products p ON pv.ProductId = p.ProductId";

$params = [];
if ($searchQuery !== '') {
    $sSql .= " WHERE p.Name LIKE ? OR pv.SKU LIKE ?";
    $like = '%' . $searchQuery . '%';
    array_push($params, $like, $like);
}
$sSql .= " ORDER BY bp.VariantId ASC, bp.MinQuantity ASC";

$sStmt = sqlsrv_query($conn, $sSql, $params);
if ($sStmt !== false) {
    while ($sr = sqlsrv_fetch_array($sStmt, SQLSRV_FETCH_ASSOC)) {
        $slabs[] = $sr;
    }
    sqlsrv_free_stmt($sStmt);
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<style>
:root {
    --bg-page: #0a1119;
    --bg-card: #111b26;
    --bg-input: #0d1620;
    --border: #1e2d3d;
    --border-soft: #182636;
    --text-hi: #f0f4f8;
    --text-body: #a8b8c8;
    --text-mute: #5f7488;
    --green: #10b981;
    --green-soft: rgba(16,185,129,.12);
}
html, body, .main, .content { background: var(--bg-page) !important; color: var(--text-body) !important; }
.pricing-page { width: 100%; max-width: 1500px; margin: 0 auto; padding-bottom: 50px; }
.grid-layout { display: grid; grid-template-columns: 360px 1fr; gap: 20px; }
.card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 20px; }
.form-control { width: 100%; height: 38px; background: var(--bg-input); border: 1px solid var(--border); border-radius: 8px; color: var(--text-hi); padding: 0 12px; margin-bottom: 12px; box-sizing: border-box; outline: none; }
.form-control:focus { border-color: var(--green); }
.btn { height: 38px; padding: 0 16px; border-radius: 8px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; border: 1px solid var(--border); background: var(--bg-input); color: var(--text-body); }
.btn:hover { border-color: var(--green); color: var(--green); }
.btn-primary { background: linear-gradient(135deg, #059669, #10b981); color: #fff; border: 0; }
.btn-primary:hover { opacity: 0.9; color: #fff; }
.table-data { width: 100%; border-collapse: collapse; }
.table-data th { background: #0d1620; padding: 12px 16px; text-align: left; font-size: 11px; text-transform: uppercase; color: var(--text-mute); border-bottom: 1px solid var(--border); }
.table-data td { padding: 14px 16px; border-bottom: 1px solid var(--border-soft); font-size: 12.5px; }
</style>

<main class="main">
    <section class="content">
        <div class="pricing-page">
            <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:24px; padding-bottom:18px; border-bottom:1px solid var(--border); flex-wrap:wrap; gap:15px;">
                <div>
                    <h1 style="margin:0; font-size:24px; color:var(--text-hi); font-weight:800;">Bulk Quantity Pricing Slabs</h1>
                    <span style="font-size:12px; color:var(--text-mute);">Setup tiered volume unit prices for wholesale bulk buyers and specific corporate clients.</span>
                </div>
                <a href="../wholesale/index.php" class="btn">← Back to Wholesale</a>
            </div>

            <?php if ($message): ?><div style="padding:12px; background:var(--green-soft); color:#6ee7b7; border-radius:8px; margin-bottom:15px; font-size:12px; font-weight:600;"><?= e($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div style="padding:12px; background:rgba(239,68,68,.12); color:#fca5a5; border-radius:8px; margin-bottom:15px; font-size:12px; font-weight:600;"><?= e($error) ?></div><?php endif; ?>

            <div class="grid-layout">
                <!-- FORM -->
                <div>
                    <div class="card">
                        <h3 style="margin:0 0 16px; font-size:14px; color:var(--text-hi); text-transform:uppercase;">Add New Pricing Slab</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="add_tier">

                            <label style="font-size:11px; font-weight:700; color:var(--text-mute);">PRODUCT VARIANT:</label>
                            <select name="variant_id" class="form-control" required>
                                <option value="">-- Choose Variant --</option>
                                <?php foreach ($variants as $v): ?>
                                    <option value="<?= $v['VariantId'] ?>"><?= e($v['ProductName'] . ' (' . $v['SKU'] . ')') ?></option>
                                <?php endforeach; ?>
                            </select>

                            <label style="font-size:11px; font-weight:700; color:var(--text-mute);">APPLICABLE CLIENT / TIER GROUP:</label>
                            <select name="target_group" class="form-control">
                                <option value="All Wholesale Clients">All Wholesale Clients (General)</option>
                                <option value="Hotel & Resorts Tier">Hotels & Resorts Partners</option>
                                <option value="VIP Corporate Buyers">VIP Corporate Buyers</option>
                            </select>

                            <label style="font-size:11px; font-weight:700; color:var(--text-mute);">MIN QUANTITY:</label>
                            <input type="number" name="min_qty" class="form-control" placeholder="e.g. 50" min="1" required>

                            <label style="font-size:11px; font-weight:700; color:var(--text-mute);">MAX QUANTITY (OPTIONAL):</label>
                            <input type="number" name="max_qty" class="form-control" placeholder="Leave empty for unlimited">

                            <label style="font-size:11px; font-weight:700; color:var(--text-mute);">BULK UNIT PRICE ($):</label>
                            <input type="number" step="0.01" name="bulk_unit_price" class="form-control" placeholder="e.g. 9.99" required>

                            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; margin-top:8px;">Save Pricing Slab</button>
                        </form>
                    </div>
                </div>

                <!-- TABLE & SEARCH -->
                <div>
                    <div class="card" style="padding:0; overflow:hidden;">
                        <!-- Search Bar Header inside table container -->
                        <div style="padding: 16px 20px; background: var(--bg-input); border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <h3 style="margin:0; font-size:13px; font-weight:800; color:var(--text-hi); text-transform:uppercase;">Active Pricing Rules (<?= count($slabs) ?>)</h3>
                            <form method="GET" style="display:flex; gap:8px; margin:0;">
                                <input type="text" name="q" class="form-control" placeholder="Search product or SKU..." value="<?= e($searchQuery) ?>" style="margin:0; width: 220px; height: 32px;">
                                <button type="submit" class="btn" style="height: 32px; padding: 0 12px; font-size: 11px;">Search</button>
                                <?php if($searchQuery !== ''): ?>
                                    <a href="index.php" class="btn" style="height: 32px; padding: 0 10px; font-size: 11px;">Reset</a>
                                <?php endif; ?>
                            </form>
                        </div>

                        <table class="table-data">
                            <thead>
                                <tr>
                                    <th>Product / Variant</th>
                                    <th>Quantity Slab</th>
                                    <th>Bulk Unit Price</th>
                                    <th style="text-align:right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($slabs)): ?>
                                    <tr><td colspan="4" style="text-align:center; padding:40px; color:var(--text-mute);">No bulk pricing rules found matching your criteria.</td></tr>
                                <?php else: foreach ($slabs as $s): ?>
                                    <tr>
                                        <td>
                                            <strong style="color:var(--text-hi);"><?= e($s['ProductName']) ?></strong><br>
                                            <span style="font-size:11px; color:var(--text-mute);">SKU: <?= e($s['SKU']) ?></span>
                                        </td>
                                        <td>
                                            <span style="color:var(--text-hi); font-weight:600;"><?= (int)$s['MinQuantity'] ?> – <?= $s['MaxQuantity'] ? (int)$s['MaxQuantity'] : '∞ (Above)' ?> units</span>
                                        </td>
                                        <td><strong style="color:var(--green); font-size:14px;">$<?= number_format((float)$s['BulkUnitPrice'], 2) ?></strong></td>
                                        <td style="text-align:right;">
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this pricing rule?');">
                                                <input type="hidden" name="action" value="delete_tier">
                                                <input type="hidden" name="tier_id" value="<?= $s['BulkPriceId'] ?>">
                                                <button type="submit" class="btn" style="height:28px; padding:0 10px; color:#fca5a5; font-size:11px;">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>