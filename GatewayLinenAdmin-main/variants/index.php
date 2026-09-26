<?php
session_start();

if (empty($_SESSION["admin_id"])) {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$activeMenu = "variants";
$pageTitle  = "GatewayLinen | Product Variants";

if (!isset($_SESSION["admin_name"])) {
    $_SESSION["admin_name"] = $_SESSION["admin_username"] ?? "GatewayLinen Administrator";
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$actionMessage = "";
$actionType = "";

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'added') {
        $actionMessage = "Variant successfully added!";
        $actionType = "success";
    } elseif ($_GET['msg'] === 'updated') {
        $actionMessage = "Variant updated successfully!";
        $actionType = "success";
    } elseif ($_GET['msg'] === 'deleted') {
        $actionMessage = "Variant deleted successfully!";
        $actionType = "success";
    } elseif ($_GET['msg'] === 'err_used') {
        $actionMessage = "Cannot delete this variant because it is already associated with an active Quote.";
        $actionType = "error";
    }
}

// Fetch Variants Data
$variants = [];
$varSql = "
    SELECT v.VariantId, v.SKU, v.Price, v.IsActive, p.Name AS ProductName, p.CategoryId 
    FROM dbo.ProductVariants v
    JOIN dbo.Products p ON v.ProductId = p.ProductId
    ORDER BY v.VariantId DESC
";
$varStmt = sqlsrv_query($conn, $varSql);
$totalActive = 0;
$totalInactive = 0;

if ($varStmt !== false) {
    while ($row = sqlsrv_fetch_array($varStmt, SQLSRV_FETCH_ASSOC)) {
        $variants[] = $row;
        if ($row['IsActive']) $totalActive++;
        else $totalInactive++;
    }
    sqlsrv_free_stmt($varStmt);
}

$totalVariants = count($variants);

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
        --bg-section: #0f1c29;
        --border: #1e2d3d;
        --border-soft: #182636;
        --text-hi: #f0f4f8;
        --text-body: #a8b8c8;
        --text-mute: #5f7488;
        --green: #10b981;
        --green-soft: rgba(16, 185, 129, .12);
        --blue: #3b82f6;
        --blue-soft: rgba(59, 130, 246, .12);
        --red: #ef4444;
        --red-soft: rgba(239, 68, 68, .12);
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

    .page-container {
        width: 100%;
        max-width: 1300px;
        margin: 0 auto;
        padding: 20px 20px 60px;
        box-sizing: border-box;
    }

    /* Breadcrumbs */
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

    /* Header & Actions */
    .header-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 24px;
        padding-bottom: 16px;
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
        gap: 10px;
        align-items: center;
    }

    .btn-dark {
        background: var(--bg-input);
        border: 1px solid var(--border);
        color: var(--text-hi);
        padding: 8px 14px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: 0.2s;
    }

    .btn-dark:hover {
        border-color: var(--text-mute);
    }

    .btn-green-add {
        background: var(--green);
        color: #fff;
        padding: 9px 18px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: none;
        transition: 0.2s;
    }

    .btn-green-add:hover {
        background: #059669;
    }

    .alert-success {
        background: var(--green-soft);
        color: var(--green);
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 20px;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .alert-error {
        background: var(--red-soft);
        color: var(--red);
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 20px;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    /* Stat Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }

    @media(max-width: 900px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .stat-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background: var(--bg-input);
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 16px;
        font-weight: 800;
    }

    .icon-cyan {
        color: #06b6d4;
        background: rgba(6, 182, 212, 0.1);
    }

    .icon-green {
        color: var(--green);
        background: var(--green-soft);
    }

    .stat-info {
        display: flex;
        flex-direction: column;
    }

    .stat-label {
        font-size: 10px;
        font-weight: 800;
        color: var(--text-mute);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-value {
        font-size: 22px;
        font-weight: 800;
        color: var(--text-hi);
        margin-top: 4px;
    }

    /* List Area */
    .list-section {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        margin-bottom: 24px;
    }

    .list-header {
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border);
    }

    .list-title-block {
        display: flex;
        flex-direction: column;
    }

    .list-title {
        color: var(--text-hi);
        font-size: 16px;
        font-weight: 800;
        margin: 0;
    }

    .list-subtitle {
        color: var(--text-mute);
        font-size: 11px;
        margin-top: 4px;
    }

    .list-filters {
        display: flex;
        gap: 12px;
    }

    .search-input {
        background: var(--bg-input);
        border: 1px solid var(--border);
        padding: 9px 14px;
        border-radius: 6px;
        color: var(--text-hi);
        font-size: 12px;
        min-width: 250px;
    }

    .search-input:focus {
        outline: none;
        border-color: var(--blue);
    }

    .filter-select {
        background: var(--bg-input);
        border: 1px solid var(--border);
        color: var(--text-hi);
        padding: 9px 12px;
        border-radius: 6px;
        font-size: 12px;
    }

    /* Table */
    .table-responsive {
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .data-table th {
        background: var(--bg-card-alt);
        color: var(--text-mute);
        font-size: 10.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .6px;
        padding: 14px 20px;
        border-bottom: 1px solid var(--border);
    }

    .data-table td {
        padding: 16px 20px;
        border-bottom: 1px solid var(--border-soft);
        font-size: 13px;
        color: var(--text-body);
        vertical-align: middle;
    }

    .data-table tr:hover td {
        background: var(--bg-hover);
    }

    .variant-product {
        color: var(--text-hi);
        font-weight: 800;
        font-size: 14px;
        margin-bottom: 4px;
    }

    .variant-sku {
        color: var(--text-mute);
        font-size: 11px;
        font-family: monospace;
    }

    .price-text {
        color: var(--text-hi);
        font-weight: 800;
        font-size: 14px;
    }

    .badge-active {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: var(--green-soft);
        color: var(--green);
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 800;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .badge-active::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--green);
    }

    .badge-inactive {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(100, 116, 139, 0.1);
        color: #94a3b8;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 800;
        border: 1px solid rgba(100, 116, 139, 0.2);
    }

    .badge-inactive::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #94a3b8;
    }

    /* Action Buttons (Circles) */
    .actions-stack {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .btn-circle {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        background: var(--bg-input);
        border: 1px solid var(--border);
        color: var(--text-body);
        cursor: pointer;
        transition: 0.2s;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 12px;
    }

    .btn-circle:hover {
        background: var(--bg-hover);
        color: var(--text-hi);
        border-color: var(--text-mute);
    }

    .btn-circle-red:hover {
        background: var(--red-soft);
        color: var(--red);
        border-color: var(--red);
    }

    /* --- PREMIUM CUSTOM MODAL CSS --- */
    .custom-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(5, 10, 15, 0.85);
        backdrop-filter: blur(5px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }

    .custom-modal {
        background: var(--bg-card);
        border: 1px solid var(--border);
        width: 100%;
        max-width: 440px;
        border-radius: 14px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        overflow: hidden;
        animation: modalPop 0.2s ease-out;
    }

    @keyframes modalPop {
        0% {
            transform: scale(0.95);
            opacity: 0;
        }

        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    .custom-modal-header {
        padding: 18px 20px;
        border-bottom: 1px solid var(--border);
        font-size: 15px;
        font-weight: 800;
        color: var(--text-hi);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--bg-section);
    }

    .custom-modal-body {
        padding: 24px 20px;
        font-size: 13px;
        color: var(--text-body);
        line-height: 1.5;
    }

    .custom-modal-footer {
        padding: 14px 20px;
        background: var(--bg-card-alt);
        border-top: 1px solid var(--border);
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .btn-modal-cancel {
        background: var(--bg-input);
        color: var(--text-body);
        border: 1px solid var(--border);
        padding: 8px 16px;
        border-radius: 7px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: 0.2s;
    }

    .btn-modal-cancel:hover {
        background: var(--border);
        color: var(--text-hi);
    }

    .btn-modal-delete {
        background: var(--red);
        color: #fff;
        border: none;
        padding: 8px 18px;
        border-radius: 7px;
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
        transition: 0.2s;
    }

    .btn-modal-delete:hover {
        opacity: 0.9;
    }

    /* Keyboard Shortcuts Panel */
    .shortcuts-panel {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 20px;
        margin-top: 24px;
    }

    .shortcuts-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        border-bottom: 1px solid var(--border);
        padding-bottom: 12px;
    }

    .shortcuts-title {
        color: var(--text-hi);
        font-size: 14px;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .shortcuts-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
    }

    @media(max-width: 900px) {
        .shortcuts-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .shortcut-item {
        display: flex;
        align-items: center;
        gap: 10px;
        background: var(--bg-input);
        padding: 8px 12px;
        border-radius: 8px;
        border: 1px solid var(--border);
    }

    .key-badge {
        background: var(--bg-card);
        color: var(--green);
        border: 1px solid var(--border);
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 800;
        min-width: 20px;
        text-align: center;
    }

    .key-desc {
        color: var(--text-body);
        font-size: 11px;
    }
</style>

<main class="main">
    <section class="content">
        <div class="page-container">

            <div class="breadcrumb">
                CATALOG &nbsp;&rsaquo;&nbsp; <span>VARIANTS</span>
            </div>

            <div class="header-row">
                <div>
                    <h1 class="page-title">Variants Management</h1>
                    <p class="page-subtitle">Manage product variant catalog, pricing, SKUs, and shortcuts.</p>
                </div>
                <div class="top-action-btns">
                    <button class="btn-dark">Print P</button>
                    <button class="btn-dark">&darr; PDF V</button>
                    <button class="btn-dark">&darr; Excel X</button>
                    <a href="add.php" class="btn-green-add">+ Add Variant A</a>
                </div>
            </div>

            <?php if (!empty($actionMessage)): ?>
                <div class="<?= $actionType === 'success' ? 'alert-success' : 'alert-error' ?>">
                    <?= e($actionMessage) ?>
                </div>
            <?php endif; ?>

            <!-- Stats Row -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon icon-cyan">#</div>
                    <div class="stat-info">
                        <div class="stat-label">TOTAL VARIANTS</div>
                        <div class="stat-value"><?= $totalVariants ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-green">&#10004;</div>
                    <div class="stat-info">
                        <div class="stat-label">ACTIVE</div>
                        <div class="stat-value"><?= $totalActive ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="color: #64748b; background: rgba(100,116,139,0.1);">O</div>
                    <div class="stat-info">
                        <div class="stat-label">INACTIVE</div>
                        <div class="stat-value"><?= $totalInactive ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="color: var(--green); background: rgba(16,185,129,0.1);">&#9733;</div>
                    <div class="stat-info">
                        <div class="stat-label">FEATURED</div>
                        <div class="stat-value">1</div>
                    </div>
                </div>
            </div>

            <!-- List Area -->
            <div class="list-section">
                <div class="list-header">
                    <div class="list-title-block">
                        <h3 class="list-title">Variants List</h3>
                        <div class="list-subtitle">Title, SKU code, base price, status and actions.</div>
                    </div>
                    <div class="list-filters">
                        <input type="text" class="search-input" placeholder="Search variant name, sku...">
                        <select class="filter-select">
                            <option>All Products</option>
                        </select>
                        <select class="filter-select">
                            <option>All Status</option>
                        </select>
                    </div>
                </div>

                <div style="padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); background: var(--bg-card-alt);">
                    <div style="font-size: 11px; color: var(--text-mute);">Showing <span style="color: var(--text-hi); font-weight: 800;"><?= $totalVariants ?></span> variants</div>
                    <div style="font-size: 11px; color: var(--text-mute); font-weight: 800;">Total: <?= $totalVariants ?></div>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th>VARIANT DETAILS</th>
                                <th>CATEGORY</th>
                                <th>PRICE (CAD)</th>
                                <th>STATUS</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($variants)): ?>
                                <?php foreach ($variants as $v): $vJson = htmlspecialchars(json_encode([
                                        'id' => $v['VariantId'],
                                        'name' => $v['ProductName'],
                                        'sku' => $v['SKU'],
                                        'price' => number_format((float)$v['Price'], 2),
                                        'status' => $v['IsActive'] ? 'Active' : 'Inactive'
                                    ]), ENT_QUOTES, 'UTF-8');
                                ?>
                                    <tr>
                                        <td>
                                            <span style="background: var(--bg-input); padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 800; color: var(--text-mute);">#<?= (int)$v['VariantId'] ?></span>
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <div style="width: 36px; height: 36px; background: var(--bg-input); border-radius: 6px; display: flex; justify-content: center; align-items: center; border: 1px solid var(--border);">📦</div>
                                                <div>
                                                    <div class="variant-product"><?= e($v['ProductName']) ?></div>
                                                    <div class="variant-sku">/<?= strtolower(e($v['SKU'])) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="background: var(--blue-soft); color: var(--blue); padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 700;">Catalog</span>
                                        </td>
                                        <td>
                                            <div class="price-text">$<?= number_format((float)$v['Price'], 2) ?></div>
                                        </td>
                                        <td>
                                            <?php if ($v['IsActive']): ?>
                                                <span class="badge-active">Active</span>
                                            <?php else: ?>
                                                <span class="badge-inactive">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="width: 60px;">
                                            <div class="actions-stack">
                                                <!-- View Icon -->
                                                <button type="button" class="btn-circle" onclick='openVariantModal(<?= $vJson ?>)'>&#128065;</button>
                                                <!-- Edit Icon -->
                                                <a href="edit.php?id=<?= (int)$v['VariantId'] ?>" class="btn-circle" style="text-decoration: none;">&#9998;</a>
                                                <!-- Custom Delete Button -->
                                                <button type="button" class="btn-circle btn-circle-red" onclick="openDeleteModal(<?= (int)$v['VariantId'] ?>, '<?= e($v['ProductName']) ?>')">&#10005;</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 50px; color: var(--text-mute);">
                                        No variants found. Click "+ Add Variant" to create one.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Keyboard Shortcuts Panel -->
            <div class="shortcuts-panel">
                <div class="shortcuts-header">
                    <div class="shortcuts-title">🖮 Keyboard Shortcuts</div>
                    <div style="font-size: 10px; color: var(--text-mute);">Press H to show/hide</div>
                </div>
                <div class="shortcuts-grid">
                    <div class="shortcut-item"><span class="key-badge">A</span><span class="key-desc">Add Variant</span></div>
                    <div class="shortcut-item"><span class="key-badge">B</span><span class="key-desc">Search / Focus search field</span></div>
                    <div class="shortcut-item"><span class="key-badge">C</span><span class="key-desc">Filter Status / Categories</span></div>
                    <div class="shortcut-item"><span class="key-badge">P</span><span class="key-desc">Print view</span></div>
                    <div class="shortcut-item"><span class="key-badge">D</span><span class="key-desc">Edit selected</span></div>
                    <div class="shortcut-item"><span class="key-badge">E</span><span class="key-desc">Delete selected</span></div>
                    <div class="shortcut-item"><span class="key-badge">V</span><span class="key-desc">Download PDF Report</span></div>
                    <div class="shortcut-item"><span class="key-badge">X</span><span class="key-desc">Download Excel (.xlsx)</span></div>
                </div>
            </div>

        </div>
    </section>
</main>

<!-- --- CUSTOM DELETE CONFIRMATION MODAL --- -->
<div id="deleteModalOverlay" class="custom-modal-overlay">
    <div class="custom-modal">
        <div class="custom-modal-header">
            <span>Delete Product Variant</span>
            <span style="cursor: pointer; color: var(--text-mute);" onclick="closeDeleteModal()">&times;</span>
        </div>
        <div class="custom-modal-body" id="deleteModalBodyText">
            Are you sure you want to delete this variant? This action cannot be undone.
        </div>
        <div class="custom-modal-footer">
            <button type="button" class="btn-modal-cancel" onclick="closeDeleteModal()">Cancel</button>
            <form id="deleteForm" action="delete.php" method="POST" style="margin: 0;">
                <input type="hidden" name="variant_id" id="modalDeleteVariantId">
                <button type="submit" class="btn-modal-delete">Yes, Delete</button>
            </form>
        </div>
    </div>
</div>

<!-- --- VARIANT DETAILS MODAL --- -->
<div id="variantModalOverlay" class="custom-modal-overlay">
    <div class="custom-modal" style="max-width: 600px;">
        <div class="custom-modal-header">
            <span>Variant Details</span>
            <span style="cursor: pointer; color: var(--text-mute);" onclick="closeVariantModal()">&times;</span>
        </div>
        <div class="custom-modal-body">
            <div style="display: flex; gap: 20px; align-items: center;">
                <div style="width: 80px; height: 80px; background: var(--bg-input); border-radius: 8px; display: flex; justify-content: center; align-items: center; border: 1px solid var(--border); font-size: 30px;">📦</div>
                <div>
                    <div style="font-size: 10px; font-weight: 800; color: var(--text-mute); text-transform: uppercase;">Product Name</div>
                    <div id="modalProductName" style="font-size: 18px; font-weight: 800; color: var(--text-hi); margin-top: 2px;">Name</div>
                    <div id="modalSku" style="font-size: 11px; color: var(--text-mute); font-family: monospace; margin-top: 4px;">/sku</div>
                </div>
            </div>
            <div style="margin-top: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div style="background: var(--bg-input); padding: 12px; border-radius: 8px; border: 1px solid var(--border);">
                    <div style="font-size: 10px; font-weight: 800; color: var(--text-mute); text-transform: uppercase;">Price (CAD)</div>
                    <div id="modalPrice" style="font-size: 14px; font-weight: 800; color: var(--green); margin-top: 4px;">$0.00</div>
                </div>
                <div style="background: var(--bg-input); padding: 12px; border-radius: 8px; border: 1px solid var(--border);">
                    <div style="font-size: 10px; font-weight: 800; color: var(--text-mute); text-transform: uppercase;">Status</div>
                    <div id="modalStatus" style="font-size: 14px; font-weight: 800; color: var(--green); margin-top: 4px;">Active</div>
                </div>
            </div>
        </div>
        <div class="custom-modal-footer">
            <button type="button" class="btn-modal-cancel" onclick="closeVariantModal()">Close</button>
        </div>
    </div>
</div>

<script>
    // Delete Modal Functions
    function openDeleteModal(variantId, variantName) {
        document.getElementById('modalDeleteVariantId').value = variantId;
        document.getElementById('deleteModalBodyText').innerHTML = `Are you sure you want to delete variant for <strong style="color:var(--text-hi);">${variantName}</strong>? This action is permanent.`;
        document.getElementById('deleteModalOverlay').style.display = 'flex';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModalOverlay').style.display = 'none';
    }

    // View Details Modal Functions
    function openVariantModal(variant) {
        document.getElementById('modalProductName').innerText = variant.name;
        document.getElementById('modalSku').innerText = '/' + variant.sku.toLowerCase();
        document.getElementById('modalPrice').innerText = '$' + variant.price;

        let statusEl = document.getElementById('modalStatus');
        statusEl.innerText = variant.status;
        if (variant.status === 'Inactive') {
            statusEl.style.color = '#94a3b8';
        } else {
            statusEl.style.color = 'var(--green)';
        }

        document.getElementById('variantModalOverlay').style.display = 'flex';
    }

    function closeVariantModal() {
        document.getElementById('variantModalOverlay').style.display = 'none';
    }

    // Close on outside click
    window.onclick = function(event) {
        const delOverlay = document.getElementById('deleteModalOverlay');
        const varOverlay = document.getElementById('variantModalOverlay');
        if (event.target === delOverlay) closeDeleteModal();
        if (event.target === varOverlay) closeVariantModal();
    }
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>