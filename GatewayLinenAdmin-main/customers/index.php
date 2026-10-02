<?php
session_start();

/*
|--------------------------------------------------------------------------
| GatewayLinen Admin - Customers Management
|--------------------------------------------------------------------------
*/

if (empty($_SESSION["admin_id"])) {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$activeMenu = "customers";
$pageTitle  = "GatewayLinen | Customers Management";

if (!isset($_SESSION["admin_name"])) {
    $_SESSION["admin_name"] = $_SESSION["admin_username"] ?? "GatewayLinen Administrator";
}

if (!isset($_SESSION["admin_role"])) {
    $_SESSION["admin_role"] = "Administrator";
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

/*
|--------------------------------------------------------------------------
| HANDLE BLOCK / UNBLOCK ACTION
|--------------------------------------------------------------------------
*/
$actionMessage = "";
$actionType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"], $_POST["user_id"])) {
    $targetUserId = (int)$_POST["user_id"];
    $newStatus = ($_POST["action"] === "block") ? 0 : 1;

    $updateSql = "UPDATE dbo.Users SET IsActive = ? WHERE UserId = ?";
    $updateParams = [$newStatus, $targetUserId];
    $updateStmt = sqlsrv_query($conn, $updateSql, $updateParams);

    if ($updateStmt !== false) {
        $actionMessage = ($newStatus === 0) ? "Customer successfully blocked." : "Customer successfully unblocked.";
        $actionType = "success";
        sqlsrv_free_stmt($updateStmt);
    } else {
        $actionMessage = "Failed to update customer status.";
        $actionType = "error";
    }
}

/*
|--------------------------------------------------------------------------
| FETCH CUSTOMERS (USERS)
|--------------------------------------------------------------------------
*/
$customers = [];
$sql = "
    SELECT 
        UserId, 
        FullName, 
        Email, 
        Phone, 
        CompanyName, 
        IsActive, 
        CreatedAt 
    FROM dbo.Users 
    WHERE RoleId IS NULL OR RoleId != 1 
    ORDER BY CreatedAt DESC
";

$stmt = sqlsrv_query($conn, $sql);

if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $customers[] = $row;
    }
    sqlsrv_free_stmt($stmt);
}

/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/
$totalCustomers = count($customers);
$activeCount = 0;
$blockedCount = 0;

foreach ($customers as $c) {
    $active = !isset($c["IsActive"]) || (int)$c["IsActive"] === 1;
    if ($active) {
        $activeCount++;
    } else {
        $blockedCount++;
    }
}

require_once __DIR__ . "/../includes/header.php";
require_once __DIR__ . "/../includes/sidebar.php";
?>

<style>
/* =========================================================
   GatewayLinen Customers Management
   Theme system follows the same pattern as Categories:
   - Light variables on .customers-page
   - Dark variables when html/body has data-theme="dark"
     or .dark / .dark-mode
   - Parent layout also changes so there is no dark/white mix
   ========================================================= */

.customers-page,
.customers-page * { box-sizing: border-box; }

.customers-page {
    width: 100%;
    max-width: 1600px;
    margin: 0 auto;
    padding: 0;
    font-size: 13px;

    /* LIGHT THEME */
    --cust-page: #f3f6fa;
    --cust-card: #ffffff;
    --cust-card-alt: #f8fafc;
    --cust-input: #ffffff;
    --cust-hover: #f8fafc;
    --cust-border: #dce4ec;
    --cust-border-soft: #e8edf3;
    --cust-text: #162334;
    --cust-body: #536579;
    --cust-muted: #7b8da1;
    --cust-green: #059669;
    --cust-green-soft: rgba(5,150,105,.10);
    --cust-red: #dc2626;
    --cust-red-soft: rgba(220,38,38,.09);
    --cust-blue: #0284c7;
    --cust-blue-soft: rgba(2,132,199,.09);
    --cust-shadow: 0 5px 18px rgba(15,23,42,.05);
    --cust-overlay: rgba(15,23,42,.60);
}

/* =========================================================
   DARK THEME - EXACT CATEGORY STYLE
   ========================================================= */
html[data-theme="dark"] .customers-page,
body[data-theme="dark"] .customers-page,
html.dark .customers-page,
body.dark .customers-page,
html.dark-mode .customers-page,
body.dark-mode .customers-page,
html.theme-dark .customers-page,
body.theme-dark .customers-page {
    --cust-page: #0a1119;
    --cust-card: #111b26;
    --cust-card-alt: #0f1823;
    --cust-input: #0d1620;
    --cust-hover: #142333;
    --cust-border: #1e2d3d;
    --cust-border-soft: #182636;
    --cust-text: #f0f4f8;
    --cust-body: #a8b8c8;
    --cust-muted: #6f8295;
    --cust-green: #10b981;
    --cust-green-soft: rgba(16,185,129,.12);
    --cust-red: #ef4444;
    --cust-red-soft: rgba(239,68,68,.12);
    --cust-blue: #38bdf8;
    --cust-blue-soft: rgba(56,189,248,.12);
    --cust-shadow: none;
    --cust-overlay: rgba(0,0,0,.72);
}

/* =========================================================
   LIGHT THEME - EXPLICIT
   ========================================================= */
html[data-theme="light"] .customers-page,
body[data-theme="light"] .customers-page,
html.light .customers-page,
body.light .customers-page,
html.light-mode .customers-page,
body.light-mode .customers-page,
html.theme-light .customers-page,
body.theme-light .customers-page {
    --cust-page: #f3f6fa;
    --cust-card: #ffffff;
    --cust-card-alt: #f8fafc;
    --cust-input: #ffffff;
    --cust-hover: #f8fafc;
    --cust-border: #dce4ec;
    --cust-border-soft: #e8edf3;
    --cust-text: #162334;
    --cust-body: #536579;
    --cust-muted: #7b8da1;
    --cust-green: #059669;
    --cust-green-soft: rgba(5,150,105,.10);
    --cust-red: #dc2626;
    --cust-red-soft: rgba(220,38,38,.09);
    --cust-blue: #0284c7;
    --cust-blue-soft: rgba(2,132,199,.09);
    --cust-shadow: 0 5px 18px rgba(15,23,42,.05);
    --cust-overlay: rgba(15,23,42,.60);
}

/* PAGE + CONTENT AREA */
.customers-page {
    background: var(--cust-page);
    color: var(--cust-body);
}

/* The page shell follows the selected theme too. */
html[data-theme="light"] body .main,
html[data-theme="light"] body .content,
body[data-theme="light"] .main,
body[data-theme="light"] .content,
html.light .main,
html.light .content,
body.light .main,
body.light .content,
html.light-mode .main,
html.light-mode .content,
body.light-mode .main,
body.light-mode .content,
html.theme-light .main,
html.theme-light .content,
body.theme-light .main,
body.theme-light .content {
    background: #f3f6fa !important;
    color: #536579 !important;
}

html[data-theme="dark"] body .main,
html[data-theme="dark"] body .content,
body[data-theme="dark"] .main,
body[data-theme="dark"] .content,
html.dark .main,
html.dark .content,
body.dark .main,
body.dark .content,
html.dark-mode .main,
html.dark-mode .content,
body.dark-mode .main,
body.dark-mode .content,
html.theme-dark .main,
html.theme-dark .content,
body.theme-dark .main,
body.theme-dark .content {
    background: #0a1119 !important;
    color: #a8b8c8 !important;
}

/* HEADER */
.page-header {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:28px;
    margin:0 0 18px;
    padding:0 0 16px;
    border-bottom:1px solid var(--cust-border);
}
.breadcrumb {
    display:flex;
    align-items:center;
    gap:9px;
    margin-bottom:7px;
    color:var(--cust-muted);
    font-size:12px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.45px;
}
.breadcrumb .current { color:var(--cust-green); }

.page-title {
    margin:0;
    color:var(--cust-text);
    font-size:30px;
    font-weight:900;
    letter-spacing:-.5px;
}
.page-subtitle {
    margin:7px 0 0;
    color:var(--cust-muted);
    font-size:13px;
    font-weight:600;
}

.header-actions,
.filters-wrap,
.export-bar {
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
}
.header-actions { justify-content:flex-end; }

.btn {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:6px;
    min-height:44px;
    padding:0 15px;
    border:1px solid var(--cust-border);
    border-radius:9px;
    background:var(--cust-card);
    color:var(--cust-text) !important;
    font-size:13px;
    font-weight:900;
    text-decoration:none;
    cursor:pointer;
    transition:.16s ease;
    box-shadow:none;
}
.btn:hover {
    border-color:var(--cust-green);
    background:var(--cust-green-soft);
    color:var(--cust-green) !important;
}
.btn small,
.key-hint {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:21px;
    height:21px;
    padding:0 4px;
    border:1px solid currentColor;
    border-radius:4px;
    font:800 9px/1 monospace;
    opacity:.9;
    background:transparent;
    color:inherit;
}

/* STATS */
.stats-grid {
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:14px;
    margin-bottom:16px;
}
.stat-item {
    display:flex;
    align-items:center;
    gap:14px;
    min-height:92px;
    padding:18px 20px;
    background:var(--cust-card);
    border:1px solid var(--cust-border);
    border-radius:10px;
    box-shadow:var(--cust-shadow);
}
.stat-icon {
    display:flex;
    align-items:center;
    justify-content:center;
    width:52px;
    height:52px;
    flex:0 0 52px;
    border-radius:11px;
    background:var(--cust-green-soft);
    color:var(--cust-green);
    font-size:18px;
    font-weight:900;
}
.stat-label {
    color:var(--cust-muted);
    font-size:10px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.5px;
}
.stat-value {
    margin-top:2px;
    color:var(--cust-text);
    font-size:23px;
    font-weight:900;
}

/* NOTICE */
.alert-msg {
    padding:12px 14px;
    margin-bottom:14px;
    border-radius:8px;
    font-size:12px;
    font-weight:800;
}
.alert-success {
    background:var(--cust-green-soft);
    color:var(--cust-green);
    border:1px solid rgba(16,185,129,.30);
}
.alert-error {
    background:var(--cust-red-soft);
    color:var(--cust-red);
    border:1px solid rgba(239,68,68,.30);
}

/* CONTENT CARD */
.table-card {
    background:var(--cust-card);
    border:1px solid var(--cust-border);
    border-radius:12px;
    overflow:hidden;
    box-shadow:var(--cust-shadow);
}
.content-header {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:20px;
    padding:20px;
    background:var(--cust-card);
    border-bottom:1px solid var(--cust-border);
}
.content-title h2 {
    margin:0;
    color:var(--cust-text);
    font-size:17px;
    font-weight:900;
}
.content-title p {
    margin:4px 0 0;
    color:var(--cust-muted);
    font-size:12px;
    font-weight:600;
}

/* SEARCH + FILTER */
.search-wrap {
    position:relative;
    width:340px;
}
.search-icon {
    position:absolute;
    left:14px;
    top:50%;
    transform:translateY(-50%);
    color:var(--cust-muted);
    pointer-events:none;
}
.search-input,
.status-filter {
    height:44px;
    border:1px solid var(--cust-border) !important;
    border-radius:9px;
    outline:none;
    background:var(--cust-input) !important;
    color:var(--cust-text) !important;
    font-size:12px;
    font-weight:600;
}
.search-input {
    width:100%;
    padding:0 45px 0 36px;
}
.search-input::placeholder { color:var(--cust-muted) !important; }
.status-filter {
    min-width:170px;
    padding:0 12px;
}
.status-filter option {
    background:var(--cust-input);
    color:var(--cust-text);
}
.search-input:focus,
.status-filter:focus {
    border-color:var(--cust-green) !important;
    box-shadow:0 0 0 3px rgba(16,185,129,.12);
}
.search-shortcut-badge {
    position:absolute;
    right:10px;
    top:50%;
    transform:translateY(-50%);
    min-width:21px;
    padding:3px 5px;
    background:var(--cust-card-alt);
    border:1px solid var(--cust-border);
    border-radius:4px;
    color:var(--cust-muted);
    font-size:9px;
    font-weight:800;
    text-align:center;
    pointer-events:none;
}

/* SUMMARY */
.table-summary {
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:12px 20px;
    background:var(--cust-card-alt);
    border-bottom:1px solid var(--cust-border);
}
.result-text {
    color:var(--cust-muted);
    font-size:11.5px;
    font-weight:700;
}
.result-text strong { color:var(--cust-text); }

/* TABLE */
.table-wrapper {
    width:100%;
    overflow-x:auto;
    background:var(--cust-card);
}
.data-table {
    width:100%;
    min-width:1100px;
    border-collapse:collapse;
    text-align:left;
    background:var(--cust-card);
}
.data-table th {
    height:46px;
    padding:0 16px;
    background:var(--cust-card-alt);
    color:var(--cust-muted);
    font-size:10.5px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.6px;
    border-bottom:1px solid var(--cust-border);
    white-space:nowrap;
}
.data-table td {
    padding:14px 16px;
    background:var(--cust-card);
    color:var(--cust-body);
    font-size:12.5px;
    border-bottom:1px solid var(--cust-border-soft);
    vertical-align:middle;
}
.data-table tr:hover td { background:var(--cust-hover); }
.data-table tr.keyboard-selected {
    outline:2px solid var(--cust-green);
    outline-offset:-2px;
    background:var(--cust-green-soft);
}
.order-box {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:36px;
    height:32px;
    padding:0 8px;
    border-radius:6px;
    background:var(--cust-card-alt);
    border:1px solid var(--cust-border);
    color:var(--cust-text);
    font-size:11px;
    font-weight:800;
}
.customer-name {
    color:var(--cust-text);
    font-weight:800;
    font-size:13px;
}
.customer-email {
    margin-top:2px;
    color:var(--cust-muted);
    font-size:11px;
}

/* STATUS */
.badge {
    display:inline-flex;
    align-items:center;
    padding:5px 10px;
    border-radius:20px;
    font-size:10.5px;
    font-weight:800;
    text-transform:uppercase;
}
.badge-active {
    background:var(--cust-green-soft);
    color:var(--cust-green);
    border:1px solid rgba(16,185,129,.22);
}
.badge-inactive {
    background:var(--cust-red-soft);
    color:var(--cust-red);
    border:1px solid rgba(239,68,68,.22);
}

/* ACTIONS */
.action-links {
    display:flex;
    gap:6px;
    align-items:center;
    justify-content:flex-end;
}
.action-btn {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:34px;
    height:34px;
    border-radius:7px;
    border:1px solid var(--cust-border);
    background:var(--cust-input);
    color:var(--cust-text);
    cursor:pointer;
    text-decoration:none;
    font-size:12px;
    transition:.15s ease;
}
.action-btn:hover {
    border-color:var(--cust-blue);
    color:var(--cust-blue);
    background:var(--cust-blue-soft);
}
.action-btn.btn-block-action:hover {
    border-color:var(--cust-red);
    color:var(--cust-red);
    background:var(--cust-red-soft);
}
.action-btn.btn-unblock-action:hover {
    border-color:var(--cust-green);
    color:var(--cust-green);
    background:var(--cust-green-soft);
}

.empty-state,
.no-result {
    text-align:center;
    padding:60px 20px;
    color:var(--cust-muted);
    background:var(--cust-card);
}
.empty-state h3,
.no-result h3 {
    margin:0 0 7px;
    color:var(--cust-text);
}
.empty-state p,
.no-result p {
    margin:0;
    color:var(--cust-muted);
}

/* PAGINATION */
.table-footer {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
    padding:14px 20px;
    background:var(--cust-card-alt);
    border-top:1px solid var(--cust-border);
}
.pagination-controls {
    display:flex;
    align-items:center;
    gap:6px;
}
.page-item {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:32px;
    height:32px;
    padding:0 8px;
    border:1px solid var(--cust-border);
    border-radius:6px;
    background:var(--cust-input);
    color:var(--cust-text);
    font-size:12px;
    font-weight:700;
    cursor:pointer;
}
.page-item:hover {
    border-color:var(--cust-green);
    color:var(--cust-green);
}
.rows-per-page {
    display:flex;
    align-items:center;
    gap:8px;
    color:var(--cust-muted);
    font-size:12px;
    font-weight:600;
}
.rows-select {
    height:32px;
    padding:0 8px;
    border:1px solid var(--cust-border) !important;
    border-radius:6px;
    background:var(--cust-input) !important;
    color:var(--cust-text) !important;
    font-size:12px;
}
.rows-select option {
    background:var(--cust-input);
    color:var(--cust-text);
}

/* MODAL */
.custom-modal-overlay {
    display:none;
    position:fixed;
    inset:0;
    z-index:99999;
    align-items:center;
    justify-content:center;
    padding:20px;
    background:var(--cust-overlay);
    backdrop-filter:blur(3px);
}
.custom-modal-overlay.show { display:flex; }
.custom-modal-box {
    width:100%;
    max-width:400px;
    padding:24px;
    text-align:center;
    background:var(--cust-card);
    border:1px solid var(--cust-border);
    border-radius:14px;
    box-shadow:0 20px 40px rgba(0,0,0,.20);
}
.custom-modal-title {
    margin-bottom:8px;
    color:var(--cust-text);
    font-size:17px;
    font-weight:900;
}
.custom-modal-desc {
    margin-bottom:20px;
    color:var(--cust-muted);
    font-size:13px;
    line-height:1.5;
}
.custom-modal-desc b { color:var(--cust-text); }
.custom-modal-actions {
    display:flex;
    gap:10px;
    justify-content:center;
}
.modal-btn {
    padding:9px 18px;
    border-radius:8px;
    font-size:12px;
    font-weight:800;
    cursor:pointer;
}
.modal-btn-cancel {
    background:var(--cust-card-alt);
    color:var(--cust-text);
    border:1px solid var(--cust-border);
}
.modal-btn-cancel:hover {
    background:var(--cust-hover);
}
.modal-btn-confirm {
    color:#fff !important;
    border:1px solid transparent;
}

/* =========================================================
   PRINT
   ========================================================= */
@media print {
    html,body,.main,.content,.customers-page {
        background:#fff !important;
        color:#111 !important;
    }
    .sidebar,
    .side-bar,
    .topbar,
    .top-bar,
    .header,
    .navbar,
    .header-actions,
    .filters-wrap,
    .action-links,
    .table-footer,
    .custom-modal-overlay {
        display:none !important;
    }
    .table-card,
    .stat-item {
        box-shadow:none !important;
        border:1px solid #ccc !important;
        background:#fff !important;
    }
    .data-table th {
        background:#f1f1f1 !important;
        color:#111 !important;
    }
    .data-table td {
        background:#fff !important;
        color:#222 !important;
    }
}

/* RESPONSIVE */
@media (max-width:1000px) {
    .stats-grid { grid-template-columns:1fr; }
    .page-header,
    .content-header {
        align-items:stretch;
        flex-direction:column;
    }
    .header-actions,
    .filters-wrap {
        justify-content:flex-start;
    }
    .search-wrap { width:min(100%,420px); }
}
@media (max-width:650px) {
    .customers-page { padding:0 8px; }
    .page-title { font-size:23px; }
    .table-summary,
    .table-footer {
        flex-direction:column;
        align-items:flex-start;
    }
    .pagination-controls {
        width:100%;
        justify-content:flex-end;
    }
}
</style>

<main class="main">
    <section class="content">
        <div class="customers-page">

            <div class="page-header">
                <div>
                    <div class="breadcrumb">
                        <span>Management</span>
                        <span>/</span>
                        <span class="current">Customers</span>
                    </div>
                    <h1 class="page-title">Customers Management</h1>
                    <p class="page-subtitle">View and manage registered users, block or unblock user accounts, and review status.</p>
                </div>

                <div class="header-actions">
                    <button type="button" class="btn" id="printBtn">🖨 Print <small>P</small></button>
                    <button type="button" class="btn" id="pdfBtn">↓ PDF <small>V</small></button>
                    <button type="button" class="btn" id="excelBtn">↓ Excel <small>X</small></button>
                </div>
            </div>

            <?php if (!empty($actionMessage)): ?>
                <div class="alert-msg <?= $actionType === 'success' ? 'alert-success' : 'alert-error' ?>">
                    <?= e($actionMessage) ?>
                </div>
            <?php endif; ?>

            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon">#</div>
                    <div>
                        <div class="stat-label">Total Customers</div>
                        <div class="stat-value"><?= $totalCustomers ?></div>
                    </div>
                </div>

                <div class="stat-item">
                    <div class="stat-icon">✓</div>
                    <div>
                        <div class="stat-label">Active Accounts</div>
                        <div class="stat-value"><?= $activeCount ?></div>
                    </div>
                </div>

                <div class="stat-item">
                    <div class="stat-icon" style="background: var(--red-soft); color: var(--red);">○</div>
                    <div>
                        <div class="stat-label">Blocked Accounts</div>
                        <div class="stat-value"><?= $blockedCount ?></div>
                    </div>
                </div>
            </div>

            <div class="table-card">
                <div class="content-header">
                    <div class="content-title">
                        <h2>Customer List</h2>
                        <p>Search through user records and manage account statuses.</p>
                    </div>

                    <div class="filters-wrap">
                        <div class="search-wrap">
                            <span class="search-icon">⌕</span>
                            <input type="search" id="customerSearch" class="search-input" placeholder="Search name, email..." autocomplete="off">
                            <span class="search-shortcut-badge">B</span>
                        </div>

                        <div style="position: relative; display: inline-block;">
                            <select id="customerStatusFilter" class="status-filter" title="Status Filter (Press C)">
                                <option value="all">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Blocked</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="table-summary">
                    <div class="result-text">
                        Showing <strong id="visibleCustomerCount"><?= count($customers) ?></strong> customers
                    </div>
                    <div class="result-text">
                        Total: <strong><?= $totalCustomers ?></strong>
                    </div>
                </div>

                <div class="table-wrapper">
                    <?php if (empty($customers)): ?>
                        <div class="empty-state">
                            <h3>No Customers Found</h3>
                            <p>No registered customers available in the database.</p>
                        </div>
                    <?php else: ?>
                        <table class="data-table" id="customerTable">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Customer Name</th>
                                    <th>Phone</th>
                                    <th>Company</th>
                                    <th>Status</th>
                                    <th>Registered On</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $orderNo = 1;
                                foreach ($customers as $cust):
                                    $isActive = !isset($cust["IsActive"]) || (int)$cust["IsActive"] === 1;
                                    $currentOrder = $orderNo++;
                                    $fullName = (string)($cust["FullName"] ?? 'Unknown User');
                                    $email = (string)($cust["Email"] ?? '');
                                    $phone = (string)($cust["Phone"] ?? 'N/A');
                                    $company = (string)($cust["CompanyName"] ?? 'N/A');
                                    $regDate = isset($cust["CreatedAt"]) && is_object($cust["CreatedAt"]) ? $cust["CreatedAt"]->format('d M Y, h:i A') : 'N/A';
                                    $statusStr = $isActive ? 'active' : 'inactive';
                                    $searchStr = strtolower($fullName . ' ' . $email . ' ' . $phone . ' ' . $company);
                                ?>
                                    <tr class="customer-row" 
                                        data-status="<?= $statusStr ?>" 
                                        data-search="<?= e($searchStr) ?>"
                                        data-order="<?= $currentOrder ?>">
                                        <td>
                                            <span class="order-box"><?= $currentOrder ?></span>
                                        </td>
                                        <td>
                                            <div class="customer-name"><?= e($fullName) ?></div>
                                            <div class="customer-email"><?= e($email) ?></div>
                                        </td>
                                        <td><?= e($phone) ?></td>
                                        <td><?= e($company) ?></td>
                                        <td>
                                            <?php if ($isActive): ?>
                                                <span class="badge badge-active">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-inactive">Blocked</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= e($regDate) ?></td>
                                        <td style="text-align:right;">
                                            <div class="action-links">
                                                <a href="view.php?id=<?= (int)$cust["UserId"] ?>" class="action-btn" title="View Details">👁</a>

                                                <form method="POST" style="margin: 0;" onsubmit="return showCustomConfirm(this, '<?= $isActive ? 'block' : 'unblock' ?>', '<?= e($fullName) ?>')">
                                                    <input type="hidden" name="user_id" value="<?= (int)$cust["UserId"] ?>">
                                                    <?php if ($isActive): ?>
                                                        <input type="hidden" name="action" value="block">
                                                        <button type="submit" class="action-btn btn-block-action" title="Block Customer">🚫</button>
                                                    <?php else: ?>
                                                        <input type="hidden" name="action" value="unblock">
                                                        <button type="submit" class="action-btn btn-unblock-action" title="Unblock Customer">✓</button>
                                                    <?php endif; ?>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div id="customerNoResult" class="no-result" style="display:none">
                            <h3>No matching customers</h3>
                            <p>Try changing your search query or status filter.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- TABLE FOOTER -->
                <div class="table-footer">
                    <div class="rows-per-page">
                        <span>Show</span>
                        <select class="rows-select" id="rowsPerPageSelect">
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="all">All</option>
                        </select>
                        <span>customers</span>
                    </div>
                    <div class="pagination-controls">
                        <button type="button" class="page-item" title="First Page">«</button>
                        <button type="button" class="page-item" title="Previous Page">‹</button>
                        <button type="button" class="page-item" style="background: var(--green-soft); color: var(--green); border-color: var(--green);">1</button>
                        <button type="button" class="page-item" title="Next Page">›</button>
                        <button type="button" class="page-item" title="Last Page">»</button>
                    </div>
                </div>
            </div>

        </div>
    </section>
</main>

<!-- Custom Confirmation Modal -->
<div id="customConfirmModal" class="custom-modal-overlay">
    <div class="custom-modal-box">
        <h3 id="modalTitle" class="custom-modal-title">Confirm Action</h3>
        <p id="modalDesc" class="custom-modal-desc">Are you sure you want to proceed with this action?</p>
        <div class="custom-modal-actions">
            <button type="button" id="modalCancelBtn" class="modal-btn modal-btn-cancel">Cancel</button>
            <button type="button" id="modalConfirmBtn" class="modal-btn modal-btn-confirm">Confirm</button>
        </div>
    </div>
</div>

<!-- PDF / EXCEL LIBRARIES -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.4/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>


<script>
/*
|--------------------------------------------------------------------------
| GatewayLinen Customers - Theme Bridge
|--------------------------------------------------------------------------
| Uses the same theme signals as the Categories page.
| It never forces a theme. It only mirrors the existing header/sidebar
| selection between html/body when one side changes.
*/
(function () {
    'use strict';

    function getTheme() {
        const html = document.documentElement;
        const body = document.body;

        const values = [
            html.getAttribute('data-theme'),
            body.getAttribute('data-theme'),
            html.getAttribute('data-mode'),
            body.getAttribute('data-mode')
        ];

        for (const value of values) {
            if (!value) continue;
            const v = String(value).toLowerCase().trim();
            if (v === 'dark' || v === 'dark-mode' || v === 'theme-dark') return 'dark';
            if (v === 'light' || v === 'light-mode' || v === 'theme-light') return 'light';
        }

        const classes = ((html.className || '') + ' ' + (body.className || '')).toLowerCase();

        if (/\bdark-mode\b|\btheme-dark\b|\bdark\b/.test(classes)) return 'dark';
        if (/\blight-mode\b|\btheme-light\b|\blight\b/.test(classes)) return 'light';

        return null;
    }

    function syncTheme() {
        const theme = getTheme();
        if (!theme) return;

        const html = document.documentElement;
        const body = document.body;

        /* Only add the same attribute if the header uses the other element. */
        if (html.getAttribute('data-theme') !== theme) {
            html.setAttribute('data-theme', theme);
        }
        if (body.getAttribute('data-theme') !== theme) {
            body.setAttribute('data-theme', theme);
        }
    }

    /* Initial sync after header/sidebar have loaded. */
    syncTheme();

    /* Detect header/sidebar theme changes immediately. */
    const observer = new MutationObserver(function () {
        syncTheme();
    });

    observer.observe(document.documentElement, {
        attributes:true,
        attributeFilter:['class','data-theme','data-mode']
    });

    observer.observe(document.body, {
        attributes:true,
        attributeFilter:['class','data-theme','data-mode']
    });
})();
</script>

<script>
    let activeForm = null;

    function showCustomConfirm(form, actionType, customerName) {
        activeForm = form;
        const modal = document.getElementById('customConfirmModal');
        const titleEl = document.getElementById('modalTitle');
        const descEl = document.getElementById('modalDesc');
        const confirmBtn = document.getElementById('modalConfirmBtn');

        if (actionType === 'block') {
            titleEl.innerText = "Block Customer";
            descEl.innerHTML = "Are you sure you want to block <b>" + customerName + "</b>? They will not be able to access the system.";
            confirmBtn.style.background = "#ef4444";
            confirmBtn.innerText = "Block Customer";
        } else {
            titleEl.innerText = "Unblock Customer";
            descEl.innerHTML = "Are you sure you want to unblock <b>" + customerName + "</b>? Their system access will be restored.";
            confirmBtn.style.background = "#10b981";
            confirmBtn.innerText = "Unblock Customer";
        }

        modal.classList.add('show');
        return false;
    }

    document.getElementById('modalCancelBtn').onclick = function() {
        document.getElementById('customConfirmModal').classList.remove('show');
        activeForm = null;
    };

    document.getElementById('modalConfirmBtn').onclick = function() {
        if (activeForm) {
            activeForm.submit();
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('customerSearch');
        const statusFilter = document.getElementById('customerStatusFilter');
        const table = document.getElementById('customerTable');
        const countElement = document.getElementById('visibleCustomerCount');
        const noResult = document.getElementById('customerNoResult');

        function rows() {
            return table ? Array.from(table.querySelectorAll('tbody .customer-row')) : [];
        }

        function visibleRows() {
            return rows().filter(row => row.style.display !== 'none');
        }

        function filterCustomers() {
            if (!table) return;
            const q = (searchInput?.value || '').toLowerCase().trim();
            const status = statusFilter?.value || 'all';

            let visibleCount = 0;

            rows().forEach(function(row) {
                const text = row.dataset.search || '';
                const rowStatus = row.dataset.status || '';

                const matchesQuery = (!q || text.includes(q));
                const matchesStatus = (status === 'all' || rowStatus === status);

                if (matchesQuery && matchesStatus) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (countElement) countElement.textContent = visibleCount;
            if (noResult) noResult.style.display = (visibleCount === 0) ? 'block' : 'none';
        }

        searchInput?.addEventListener('input', filterCustomers);
        statusFilter?.addEventListener('change', filterCustomers);

        function printCustomers() { window.print(); }

        function excelCustomers() {
            const data = visibleRows().map(function(row) {
                return {
                    'Order': row.querySelector('.order-box')?.innerText.trim() || '',
                    'Customer Name': row.querySelector('.customer-name')?.innerText.trim() || '',
                    'Email': row.querySelector('.customer-email')?.innerText.trim() || '',
                    'Phone': row.querySelector('td:nth-child(3)')?.innerText.trim() || '',
                    'Company': row.querySelector('td:nth-child(4)')?.innerText.trim() || '',
                    'Status': row.dataset.status === 'active' ? 'Active' : 'Blocked',
                    'Registered': row.querySelector('td:nth-child(6)')?.innerText.trim() || ''
                };
            });

            if (window.XLSX) {
                const ws = XLSX.utils.json_to_sheet(data);
                ws['!cols'] = [{ wch: 8 }, { wch: 25 }, { wch: 30 }, { wch: 15 }, { wch: 20 }, { wch: 12 }, { wch: 20 }];
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Customers');
                XLSX.writeFile(wb, 'customers-' + new Date().toISOString().slice(0, 10) + '.xlsx');
            }
        }

        function pdfCustomers() {
            if (!window.jspdf || !window.jspdf.jsPDF) {
                alert('PDF library is not loaded.');
                return;
            }

            const body = visibleRows().map(function(row) {
                return [
                    row.querySelector('.order-box')?.innerText.trim() || '',
                    row.querySelector('.customer-name')?.innerText.trim() || '',
                    row.querySelector('td:nth-child(3)')?.innerText.trim() || '',
                    row.querySelector('td:nth-child(4)')?.innerText.trim() || '',
                    row.dataset.status === 'active' ? 'Active' : 'Blocked',
                    row.querySelector('td:nth-child(6)')?.innerText.trim() || ''
                ];
            });

            const doc = new jspdf.jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
            doc.setFontSize(16);
            doc.text('GatewayLinen - Customers Management', 14, 14);
            doc.setFontSize(9);
            doc.text('Generated: ' + new Date().toLocaleString(), 14, 20);

            if (typeof doc.autoTable === 'function') {
                doc.autoTable({
                    startY: 26,
                    head: [['Order', 'Customer Name', 'Phone', 'Company', 'Status', 'Registered']],
                    body: body,
                    styles: { fontSize: 8, cellPadding: 3 },
                    headStyles: { fillColor: [16, 185, 129] }
                });
            }

            doc.save('customers-' + new Date().toISOString().slice(0, 10) + '.pdf');
        }

        document.getElementById('printBtn')?.addEventListener('click', printCustomers);
        document.getElementById('pdfBtn')?.addEventListener('click', pdfCustomers);
        document.getElementById('excelBtn')?.addEventListener('click', excelCustomers);

        // Keyboard Shortcuts Handler (B, C, P, V, X, Esc)
        document.addEventListener('keydown', function(e) {
            const tag = (e.target?.tagName || '').toLowerCase();
            const typing = tag === 'input' || tag === 'textarea' || tag === 'select' || e.target?.isContentEditable;
            const key = (e.key || '').toUpperCase();

            if (!typing) {
                if (['B', 'C', 'P', 'V', 'X'].includes(key)) {
                    e.preventDefault();
                    e.stopPropagation();
                }

                if (key === 'B') {
                    searchInput?.focus();
                    searchInput?.select();
                } else if (key === 'C') {
                    statusFilter?.focus();
                } else if (key === 'P') {
                    printCustomers();
                } else if (key === 'V') {
                    pdfCustomers();
                } else if (key === 'X') {
                    excelCustomers();
                }
            }

            if (e.key === 'Escape') {
                const modal = document.getElementById('customConfirmModal');
                if (modal?.classList.contains('show')) {
                    modal.classList.remove('show');
                } else if (searchInput?.value) {
                    searchInput.value = '';
                    filterCustomers();
                }
                searchInput?.blur();
                statusFilter?.blur();
            }
        }, true);

        filterCustomers();
    });
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>