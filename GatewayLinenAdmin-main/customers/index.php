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

function dateValue($value): string
{
    if ($value instanceof DateTimeInterface) {
        return $value->format('d M Y, h:i A');
    }
    return !empty($value) ? date('d M Y, h:i A', strtotime($value)) : '—';
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
        --green-soft: rgba(16, 185, 129, .12);
        --blue: #38bdf8;
        --blue-soft: rgba(56, 189, 248, .15);
        --red: #ef4444;
        --red-soft: rgba(239, 68, 68, .12);
        --radius: 10px;
    }

    html, body, .main, .content {
        background: var(--bg-page) !important;
        color: var(--text-body) !important;
    }

    .customers-page {
        width: 100%;
        max-width: 1600px;
        margin: 0 auto;
        padding: 0;
    }

    .customers-page * { box-sizing: border-box; }

    /* HEADER */
    .page-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 24px;
        margin-bottom: 20px;
        padding-bottom: 18px;
        border-bottom: 1px solid var(--border);
    }

    .breadcrumb {
        display: flex;
        gap: 8px;
        margin-bottom: 8px;
        color: var(--text-mute);
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .3px;
    }
    .breadcrumb .current { color: var(--green); }

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

    /* BUTTONS */
    .header-actions, .filters-wrap, .export-bar {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .header-actions { justify-content: flex-end; }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 38px;
        padding: 0 13px;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--bg-input);
        color: var(--text-body) !important;
        font-size: 11px;
        font-weight: 800;
        text-decoration: none;
        cursor: pointer;
        transition: .18s;
    }
    .btn:hover {
        border-color: var(--green);
        background: var(--green-soft);
        color: var(--green) !important;
    }
    .btn-blue { color: var(--blue) !important; }

    /* STATS */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }
    .stat-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px 17px;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
    }
    .stat-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 9px;
        background: var(--green-soft);
        color: var(--green);
        font-size: 15px;
        font-weight: 800;
    }
    .stat-label {
        color: var(--text-mute);
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
    }
    .stat-value {
        margin-top: 2px;
        color: var(--text-hi);
        font-size: 20px;
        font-weight: 800;
    }

    /* NOTICE */
    .alert-msg {
        padding: 12px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 14px;
    }
    .alert-success { background: var(--green-soft); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, .3); }
    .alert-error { background: var(--red-soft); color: #fca5a5; border: 1px solid rgba(239, 68, 68, .3); }

    /* CONTENT BOX */
    .table-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 15px 40px rgba(0, 0, 0, .18);
    }
    .content-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 17px 20px;
        border-bottom: 1px solid var(--border);
    }
    .content-title h2 { margin: 0; color: var(--text-hi); font-size: 16px; }
    .content-title p { margin: 4px 0 0; color: var(--text-mute); font-size: 11px; }

    /* SEARCH & FILTER */
    .search-wrap { position: relative; width: 280px; }
    .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-mute); pointer-events: none; }
    .search-input, .status-filter {
        height: 36px;
        border: 1px solid var(--border);
        border-radius: 8px;
        outline: none;
        background: var(--bg-input);
        color: var(--text-hi);
        font-size: 12px;
    }
    .search-input { width: 100%; padding: 0 12px 0 34px; }
    .status-filter { min-width: 125px; padding: 0 10px; }
    .search-input:focus, .status-filter:focus {
        border-color: var(--green);
        box-shadow: 0 0 0 3px rgba(16, 185, 129, .1);
    }

    /* EXPORT BAR */
    .export-bar {
        padding: 10px 20px;
        border-bottom: 1px solid var(--border);
        background: var(--bg-card-alt);
    }
    .export-label {
        margin-right: auto;
        color: var(--text-mute);
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    /* SUMMARY */
    .table-summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 11px 20px;
        border-bottom: 1px solid var(--border);
    }
    .result-text { color: var(--text-mute); font-size: 11px; font-weight: 600; }
    .result-text strong { color: var(--text-hi); }

    /* TABLE */
    .table-wrapper { width: 100%; overflow-x: auto; }
    .data-table { width: 100%; min-width: 1100px; border-collapse: collapse; text-align: left; }
    .data-table th {
        height: 44px;
        background: var(--bg-card-alt);
        color: var(--text-mute);
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .6px;
        padding: 0 16px;
        border-bottom: 1px solid var(--border);
        white-space: nowrap;
    }
    .data-table td {
        padding: 13px 16px;
        border-bottom: 1px solid var(--border-soft);
        font-size: 12px;
        color: var(--text-body);
        vertical-align: middle;
    }
    .data-table tr:hover td { background: var(--bg-hover); }
    .data-table tr.keyboard-selected {
        outline: 2px solid var(--green);
        outline-offset: -2px;
        background: var(--green-soft);
    }

    .order-box {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 42px;
        height: 28px;
        padding: 0 9px;
        border-radius: 7px;
        background: var(--bg-input);
        border: 1px solid var(--border);
        color: var(--green);
        font-size: 11px;
        font-weight: 800;
    }

    .customer-name { color: var(--text-hi); font-weight: 700; font-size: 13px; }
    .customer-email { font-size: 10.5px; color: var(--text-mute); margin-top: 2px; }

    .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
    }
    .badge-active { background: var(--green-soft); color: var(--green); border: 1px solid rgba(16, 185, 129, .2); }
    .badge-inactive { background: var(--red-soft); color: var(--red); border: 1px solid rgba(239, 68, 68, .2); }

    .action-links { display: flex; gap: 6px; align-items: center; justify-content: flex-end; }
    .btn-action {
        padding: 6px 12px;
        border-radius: 7px;
        font-size: 11px;
        font-weight: 700;
        text-decoration: none;
        border: 1px solid var(--border);
        background: var(--bg-input);
        color: var(--text-body);
        cursor: pointer;
    }
    .btn-action:hover { border-color: var(--blue); color: var(--blue); background: var(--blue-soft); }
    .btn-block { border-color: rgba(239, 68, 68, 0.4); color: var(--red); }
    .btn-block:hover { background: var(--red-soft); border-color: var(--red); color: var(--red); }
    .btn-unblock { border-color: rgba(16, 185, 129, 0.4); color: var(--green); }
    .btn-unblock:hover { background: var(--green-soft); border-color: var(--green); color: var(--green); }

    .empty-state, .no-result { text-align: center; padding: 60px 20px; color: var(--text-mute); }

    /* SHORTCUTS BOX */
    .shortcut-help-box {
        margin-top: 16px;
        padding: 16px 20px;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: var(--bg-card);
    }
    .shortcut-help-box.hidden { display: none; }
    .shortcut-help-title {
        display: flex; align-items: center; gap: 9px; margin-bottom: 12px; color: var(--text-hi); font-size: 13px; font-weight: 800;
    }
    .shortcut-help-title small { margin-left: auto; color: var(--text-mute); font: 600 10px monospace; }
    .shortcut-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
    .shortcut-item {
        display: flex; align-items: center; gap: 9px; padding: 8px 10px; border: 1px solid var(--border-soft); border-radius: 8px; background: var(--bg-input);
    }
    .shortcut-key {
        display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 25px; padding: 0 7px; border-radius: 5px;
        background: #0a1119; border: 1px solid var(--border); color: var(--green); font: 800 10px monospace;
    }
    .shortcut-desc { color: var(--text-body); font-size: 11px; font-weight: 600; }

    /* MODAL */
    .custom-modal-overlay {
        display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, .75); backdrop-filter: blur(4px); z-index: 99999; justify-content: center; align-items: center; padding: 20px;
    }
    .custom-modal-overlay.show { display: flex; }
    .custom-modal-box {
        background: var(--bg-card); border: 1px solid var(--border); padding: 22px; border-radius: 14px; width: 100%; max-width: 400px; box-shadow: 0 25px 50px rgba(0, 0, 0, 0.6); text-align: center;
    }
    .custom-modal-title { color: var(--text-hi); font-size: 16px; font-weight: 700; margin-bottom: 8px; }
    .custom-modal-desc { color: var(--text-mute); font-size: 12.5px; margin-bottom: 20px; line-height: 1.4; }
    .custom-modal-actions { display: flex; gap: 10px; justify-content: center; }
    .modal-btn { padding: 8px 18px; border-radius: 8px; font-size: 11.5px; font-weight: 700; cursor: pointer; border: none; }
    .modal-btn-cancel { background: var(--bg-input); color: var(--text-mute); border: 1px solid var(--border); }
    .modal-btn-cancel:hover { background: var(--bg-hover); color: var(--text-hi); }
    .modal-btn-confirm { color: #ffffff; }

    @media print {
        @page { size: landscape; margin: 10mm; }
        html, body, .main, .content { background: #fff !important; color: #111 !important; width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .customers-page { max-width: none !important; width: 100% !important; }
        .page-header, .stats-grid, .content-header, .export-bar, .shortcut-help-box, .alert-msg, .custom-modal-overlay, th:last-child, td:last-child { display: none !important; }
        .table-card { border: 0 !important; background: none !important; box-shadow: none !important; }
        .table-wrapper { overflow: visible !important; }
        .data-table { width: 100% !important; min-width: 0 !important; border-collapse: collapse !important; }
        .data-table th, .data-table td { color: #111 !important; background: #fff !important; border: 1px solid #ddd !important; padding: 8px !important; font-size: 11px !important; }
        .data-table th { background: #f2f2f2 !important; }
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
                    <p class="page-subtitle">View and manage registered users, block/unblock accounts, and shortcuts.</p>
                </div>

                <div class="header-actions">
                    <button type="button" class="btn btn-blue" id="printBtn">🖨 Print <small>P</small></button>
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
                    <div class="stat-icon">○</div>
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
                        <p>Search through user records and manage user statuses.</p>
                    </div>

                    <div class="filters-wrap">
                        <div class="search-wrap">
                            <span class="search-icon">⌕</span>
                            <input type="search" id="customerSearch" class="search-input" placeholder="Search name, email, company..." autocomplete="off">
                        </div>

                        <select id="customerStatusFilter" class="status-filter">
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Blocked</option>
                        </select>
                    </div>
                </div>

                <!-- EXPORT BAR -->
                <div class="export-bar">
                    <span class="export-label">Reports & Export</span>
                    <button type="button" class="btn" id="printBtn2">🖨 Print</button>
                    <button type="button" class="btn" id="pdfBtn2">↓ PDF</button>
                    <button type="button" class="btn" id="excelBtn2">↓ Excel</button>
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
                                    $regDate = isset($cust["CreatedAt"]) && is_object($cust["CreatedAt"]) ? $cust["CreatedAt"]->format('Y-m-d') : 'N/A';
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
                                                <a href="view.php?id=<?= (int)$cust["UserId"] ?>" class="btn-action">View</a>

                                                <form method="POST" style="margin: 0;" onsubmit="return showCustomConfirm(this, '<?= $isActive ? 'block' : 'unblock' ?>', '<?= e($fullName) ?>')">
                                                    <input type="hidden" name="user_id" value="<?= (int)$cust["UserId"] ?>">
                                                    <?php if ($isActive): ?>
                                                        <input type="hidden" name="action" value="block">
                                                        <button type="submit" class="btn-action btn-block">Block</button>
                                                    <?php else: ?>
                                                        <input type="hidden" name="action" value="unblock">
                                                        <button type="submit" class="btn-action btn-unblock">Unblock</button>
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
            </div>

            <!-- SHORTCUTS -->
            <div class="shortcut-help-box" id="shortcutHelpBox">
                <div class="shortcut-help-title">
                    <span>⌨</span>
                    <span>Keyboard Shortcuts</span>
                    <small>B C P V X H • Esc</small>
                </div>
                <div class="shortcut-grid">
                    <div class="shortcut-item"><span class="shortcut-key">B</span><span class="shortcut-desc">Focus search</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">C</span><span class="shortcut-desc">Status filter</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">P</span><span class="shortcut-desc">Print</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">V</span><span class="shortcut-desc">Download PDF</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">X</span><span class="shortcut-desc">Download Excel</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">H</span><span class="shortcut-desc">Toggle shortcuts</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">Esc</span><span class="shortcut-desc">Clear search / Blur</span></div>
                </div>
            </div>

        </div>
    </section>
</main>

<!-- Custom Dark Theme Confirmation Modal -->
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
            confirmBtn.innerText = "Block";
        } else {
            titleEl.innerText = "Unblock Customer";
            descEl.innerHTML = "Are you sure you want to unblock <b>" + customerName + "</b>? Their access will be restored.";
            confirmBtn.style.background = "#10b981";
            confirmBtn.innerText = "Unblock";
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
        const shortcutBox = document.getElementById('shortcutHelpBox');

        function rows() {
            return table ? Array.from(table.querySelectorAll('tbody .customer-row')) : [];
        }

        function visibleRows() {
            return rows().filter(row => row.style.display !== 'none');
        }

        function selectFirstVisible(scroll) {
            rows().forEach(r => r.classList.remove('keyboard-selected'));
            const first = visibleRows()[0];
            if (first) {
                first.classList.add('keyboard-selected');
                if (scroll) first.scrollIntoView({ block: 'nearest' });
            }
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

            selectFirstVisible(false);
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
                ws['!cols'] = [{ wch: 8 }, { wch: 25 }, { wch: 30 }, { wch: 15 }, { wch: 20 }, { wch: 12 }, { wch: 15 }];
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Customers');
                XLSX.writeFile(wb, 'customers-' + new Date().toISOString().slice(0, 10) + '.xlsx');
            }
        }

        function pdfCustomers() {
            if (!window.jspdf || !window.jspdf.jsPDF) {
                alert('PDF library is not loaded. Use Print and choose Save as PDF.');
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
        document.getElementById('printBtn2')?.addEventListener('click', printCustomers);
        document.getElementById('pdfBtn')?.addEventListener('click', pdfCustomers);
        document.getElementById('pdfBtn2')?.addEventListener('click', pdfCustomers);
        document.getElementById('excelBtn')?.addEventListener('click', excelCustomers);
        document.getElementById('excelBtn2')?.addEventListener('click', excelCustomers);

        // Keyboard Shortcuts Handler
        document.addEventListener('keydown', function(e) {
            const tag = (e.target?.tagName || '').toLowerCase();
            const typing = tag === 'input' || tag === 'textarea' || tag === 'select' || e.target?.isContentEditable;
            const key = (e.key || '').toUpperCase();

            if (!typing) {
                if (['B', 'C', 'P', 'V', 'X', 'H'].includes(key)) {
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
                } else if (key === 'H') {
                    shortcutBox?.classList.toggle('hidden');
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

        rows().forEach(function(row) {
            row.addEventListener('click', function(e) {
                if (e.target.closest('button, a, form')) return;
                rows().forEach(r => r.classList.remove('keyboard-selected'));
                row.classList.add('keyboard-selected');
            });
        });

        filterCustomers();
    });
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>