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
| DATABASE & CONFIG
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/../config/database.php';

$activeMenu = 'wholesale';
$pageTitle  = 'GatewayLinen | Wholesale Clients Management';

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] = $_SESSION['admin_username'] ?? 'GatewayLinen Administrator';
}

if (!isset($_SESSION['admin_role'])) {
    $_SESSION['admin_role'] = 'Administrator';
}

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function formatDate($value): string {
    if ($value instanceof DateTimeInterface) {
        return $value->format('d M Y, h:i A');
    }
    return !empty($value) ? date('d M Y, h:i A', strtotime($value)) : '—';
}

$actionMessage = trim((string)($_GET['success'] ?? ''));
$actionError   = trim((string)($_GET['error'] ?? ''));

/*
|--------------------------------------------------------------------------
| POST ACTIONS: APPROVAL, REJECTION & TERMS UPDATE
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $targetUserId = (int)($_POST['user_id'] ?? 0);
    $actionType   = trim($_POST['action']);

    if ($targetUserId > 0) {
        if ($actionType === 'approve_wholesale') {
            $discount = (float)($_POST['discount_pct'] ?? 10.00);
            $credit   = (float)($_POST['credit_limit'] ?? 5000.00);

            $upSql = "UPDATE dbo.Users 
                      SET IsWholesaleApproved = 1, 
                          WholesaleDiscountPct = ?, 
                          CreditLimit = ? 
                      WHERE UserId = ?";
            $upStmt = sqlsrv_query($conn, $upSql, [$discount, $credit, $targetUserId]);
            if ($upStmt !== false) {
                header('Location: index.php?success=' . urlencode('Wholesale client approved successfully!'));
                exit;
            } else {
                $actionError = 'Failed to approve wholesale client.';
            }
        } elseif ($actionType === 'reject_wholesale') {
            $upSql = "UPDATE dbo.Users 
                      SET IsWholesaleApproved = 0 
                      WHERE UserId = ?";
            $upStmt = sqlsrv_query($conn, $upSql, [$targetUserId]);
            if ($upStmt !== false) {
                header('Location: index.php?success=' . urlencode('Wholesale access status updated/revoked.'));
                exit;
            } else {
                $actionError = 'Failed to update client status.';
            }
        } elseif ($actionType === 'update_terms') {
            $discount = (float)($_POST['discount_pct'] ?? 0.00);
            $credit   = (float)($_POST['credit_limit'] ?? 0.00);

            $upSql = "UPDATE dbo.Users 
                      SET WholesaleDiscountPct = ?, 
                          CreditLimit = ? 
                      WHERE UserId = ?";
            $upStmt = sqlsrv_query($conn, $upSql, [$discount, $credit, $targetUserId]);
            if ($upStmt !== false) {
                header('Location: index.php?success=' . urlencode('Wholesale pricing terms updated!'));
                exit;
            } else {
                $actionError = 'Failed to update client terms.';
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| FETCH METRICS (COUNTING)
|--------------------------------------------------------------------------
*/
$approvedCount = 0;
$qApproved = sqlsrv_query($conn, "SELECT COUNT(*) AS Cnt FROM dbo.Users WHERE IsWholesaleApproved = 1");
if ($qApproved && $r = sqlsrv_fetch_array($qApproved, SQLSRV_FETCH_ASSOC)) {
    $approvedCount = (int)$r['Cnt'];
}

$pendingCount = 0;
$qPending = sqlsrv_query($conn, "SELECT COUNT(*) AS Cnt FROM dbo.Users WHERE (CompanyName IS NOT NULL AND CompanyName != '') AND (IsWholesaleApproved = 0 OR IsWholesaleApproved IS NULL)");
if ($qPending && $r = sqlsrv_fetch_array($qPending, SQLSRV_FETCH_ASSOC)) {
    $pendingCount = (int)$r['Cnt'];
}

$activeQuotes = 0;
$qQuotes = sqlsrv_query($conn, "SELECT COUNT(*) AS Cnt FROM dbo.Quotes WHERE Status IN ('Pending', 'Active', 'Sent')");
if ($qQuotes && $r = sqlsrv_fetch_array($qQuotes, SQLSRV_FETCH_ASSOC)) {
    $activeQuotes = (int)$r['Cnt'];
}

$openInquiries = 0;
$qInq = sqlsrv_query($conn, "SELECT COUNT(*) AS Cnt FROM dbo.BulkInquiries WHERE Status IN ('New', 'Pending', 'Open')");
if ($qInq && $r = sqlsrv_fetch_array($qInq, SQLSRV_FETCH_ASSOC)) {
    $openInquiries = (int)$r['Cnt'];
}

/*
|--------------------------------------------------------------------------
| FETCH CLIENTS LIST WITH ORDERS DETAILS
|--------------------------------------------------------------------------
*/
$sql = "SELECT 
            u.UserId,
            u.FullName,
            u.Email,
            u.Phone,
            ISNULL(u.CompanyName, 'Individual Buyer') AS CompanyName,
            ISNULL(u.TaxNumber, 'N/A') AS TaxNumber,
            ISNULL(u.IsWholesaleApproved, 0) AS IsWholesaleApproved,
            ISNULL(u.WholesaleDiscountPct, 0.00) AS WholesaleDiscountPct,
            ISNULL(u.CreditLimit, 0.00) AS CreditLimit,
            (SELECT COUNT(*) FROM dbo.Orders o WHERE o.UserId = u.UserId) AS TotalOrders,
            (SELECT ISNULL(SUM(TotalAmount), 0) FROM dbo.Orders o WHERE o.UserId = u.UserId) AS TotalSpent
        FROM dbo.Users u
        WHERE (u.IsWholesaleApproved = 1 OR (u.CompanyName IS NOT NULL AND u.CompanyName != ''))
        ORDER BY u.UserId DESC";

$stmt = sqlsrv_query($conn, $sql);
$allClients = [];
$queryError = '';

if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $allClients[] = $row;
    }
    sqlsrv_free_stmt($stmt);
} else {
    $queryError = 'Unable to load wholesale clients right now.';
}

$totalWholesale = count($allClients);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<style>
    :root {
        --bg-page: #0a1119;
        --bg-card: #111b26;
        --bg-card-alt: #0f1823;
        --bg-header: #0d1620;
        --bg-hover: #16222e;
        --bg-input: #0d1620;
        --border: #1e2d3d;
        --border-soft: #182636;
        --text-hi: #f0f4f8;
        --text-body: #a8b8c8;
        --text-mute: #5f7488;
        --green: #10b981;
        --green-soft: rgba(16, 185, 129, .12);
        --red: #ef4444;
        --red-soft: rgba(239, 68, 68, .12);
        --blue: #38bdf8;
        --blue-soft: rgba(56, 189, 248, .15);
        --amber: #f59e0b;
        --amber-soft: rgba(245, 158, 11, .12);
        --radius: 10px;
    }

    html, body, .main, .content {
        background: var(--bg-page) !important;
        color: var(--text-body) !important;
    }

    .wholesale-page {
        width: 100%;
        max-width: 1600px;
        margin: 0 auto;
        padding: 0;
    }

    .wholesale-page * { box-sizing: border-box; }

    /* HEADER */
    .wholesale-page-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 24px;
        margin-bottom: 20px;
        padding-bottom: 18px;
        border-bottom: 1px solid var(--border);
    }

    .wholesale-breadcrumb {
        display: flex;
        gap: 8px;
        margin-bottom: 8px;
        color: var(--text-mute);
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .3px;
    }

    .wholesale-breadcrumb .current { color: var(--green); }
    .wholesale-page-header h1 { margin: 0; color: var(--text-hi); font-size: 26px; font-weight: 800; }
    .wholesale-page-header p { margin: 6px 0 0; color: var(--text-mute); font-size: 12px; }

    .header-actions, .wholesale-actions, .wholesale-filters, .export-bar {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

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

    .btn-primary {
        border-color: transparent;
        background: linear-gradient(135deg, #059669, #10b981);
        color: #fff !important;
        box-shadow: 0 6px 16px rgba(16, 185, 129, .2);
    }

    .btn-blue { color: var(--blue) !important; }

    /* STATS */
    .wholesale-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }

    .wholesale-stat-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px 17px;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
    }

    .wholesale-stat-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 9px;
        font-size: 16px;
        font-weight: 800;
    }

    .wholesale-stat-label {
        color: var(--text-mute);
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .wholesale-stat-value {
        margin-top: 2px;
        color: var(--text-hi);
        font-size: 20px;
        font-weight: 800;
    }

    /* NOTICE */
    .notice {
        margin-bottom: 14px;
        padding: 12px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
    }

    .notice-success { border: 1px solid rgba(16, 185, 129, .3); background: var(--green-soft); color: #6ee7b7; }
    .notice-error { border: 1px solid rgba(239, 68, 68, .3); background: var(--red-soft); color: #fca5a5; }

    /* CONTENT & TABLE */
    .wholesale-content {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
    }

    .wholesale-content-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 17px 20px;
        border-bottom: 1px solid var(--border);
    }

    .wholesale-content-title h2 { margin: 0; color: var(--text-hi); font-size: 16px; }
    .wholesale-content-title p { margin: 4px 0 0; color: var(--text-mute); font-size: 11px; }

    .wholesale-search-wrap { position: relative; width: 300px; }
    .wholesale-search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-mute);
        pointer-events: none;
    }

    .wholesale-search, .wholesale-status-filter {
        height: 36px;
        border: 1px solid var(--border);
        border-radius: 8px;
        outline: none;
        background: var(--bg-input);
        color: var(--text-hi);
        font-size: 12px;
    }

    .wholesale-search { width: 100%; padding: 0 12px 0 34px; }
    .wholesale-status-filter { min-width: 140px; padding: 0 10px; }

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

    .wholesale-table-summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 11px 20px;
        border-bottom: 1px solid var(--border);
    }

    .wholesale-result-text { color: var(--text-mute); font-size: 11px; font-weight: 600; }
    .wholesale-result-text strong { color: var(--text-hi); }

    .wholesale-table-wrapper { width: 100%; overflow-x: auto; }
    .wholesale-table { width: 100%; min-width: 1200px; border-collapse: collapse; }
    .wholesale-table th {
        height: 44px;
        padding: 0 16px;
        background: var(--bg-header);
        border-bottom: 1px solid var(--border);
        color: var(--text-mute);
        font-size: 10px;
        font-weight: 800;
        text-align: left;
        text-transform: uppercase;
        letter-spacing: .5px;
        white-space: nowrap;
    }

    .wholesale-table td {
        padding: 12px 16px;
        border-bottom: 1px solid var(--border-soft);
        color: var(--text-body);
        font-size: 12px;
        vertical-align: middle;
    }

    .wholesale-table tbody tr:hover { background: var(--bg-hover); }
    .wholesale-table tbody tr.keyboard-selected {
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

    .wholesale-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--bg-input);
        color: var(--text-body) !important;
        text-decoration: none;
        cursor: pointer;
    }

    .wholesale-action:hover {
        border-color: var(--green);
        background: var(--green-soft);
        color: var(--green) !important;
    }

    .wholesale-status {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-height: 25px;
        padding: 0 10px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
    }

    .wholesale-status-active { background: var(--green-soft); color: var(--green); }
    .wholesale-status-inactive { background: var(--amber-soft); color: var(--amber); }

    /* SHORTCUT HELP */
    .shortcut-help-box {
        margin-top: 16px;
        padding: 16px 20px;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: var(--bg-card);
    }

    .shortcut-help-box.hidden { display: none; }
    .shortcut-help-title {
        display: flex;
        align-items: center;
        gap: 9px;
        margin-bottom: 12px;
        color: var(--text-hi);
        font-size: 13px;
        font-weight: 800;
    }

    .shortcut-help-title small { margin-left: auto; color: var(--text-mute); font: 600 10px monospace; }
    .shortcut-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; }
    .shortcut-item {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 8px 10px;
        border: 1px solid var(--border-soft);
        border-radius: 8px;
        background: var(--bg-input);
    }

    .shortcut-key {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        height: 25px;
        padding: 0 7px;
        border-radius: 5px;
        background: #0a1119;
        border: 1px solid var(--border);
        color: var(--green);
        font: 800 10px monospace;
    }

    .shortcut-desc { color: var(--text-body); font-size: 11px; font-weight: 600; }

    /* MODAL */
    .modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(0, 0, 0, .75);
    }

    .modal-backdrop.show { display: flex; }
    .wholesale-modal {
        width: min(650px, 100%);
        max-height: 90vh;
        overflow: auto;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 14px;
        box-shadow: 0 24px 80px rgba(0, 0, 0, .5);
    }

    .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 15px 18px;
        border-bottom: 1px solid var(--border);
    }

    .modal-header h3 { margin: 0; color: var(--text-hi); font-size: 15px; }
    .modal-close { border: 0; background: transparent; color: var(--text-mute); font-size: 22px; cursor: pointer; }
    .modal-body { padding: 18px; }

    .detail-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; }
    .detail-item label { display: block; margin-bottom: 4px; color: var(--text-mute); font-size: 9px; font-weight: 800; text-transform: uppercase; }
    .detail-item div { color: var(--text-hi); font-size: 12px; }
    .detail-card { padding: 12px; border: 1px solid var(--border-soft); border-radius: 8px; background: var(--bg-input); }
    .form-control {
        width: 100%;
        height: 38px;
        background: var(--bg-input);
        border: 1px solid var(--border);
        border-radius: 8px;
        color: var(--text-hi);
        padding: 0 12px;
        margin-top: 4px;
        font-size: 12px;
    }

    @media print {
        html, body, .main, .content { background: #fff !important; color: #111 !important; }
        .wholesale-page-header, .wholesale-stats, .wholesale-filters, .export-bar, .wholesale-actions,
        .shortcut-help-box, .notice, .modal-backdrop { display: none !important; }
        .wholesale-content { border: 0; }
        .wholesale-table { min-width: 0; }
        .wholesale-table th, .wholesale-table td { color: #111 !important; background: #fff !important; border-color: #ccc !important; }
    }
</style>

<main class="main">
    <section class="content">
        <div class="wholesale-page">

            <!-- PAGE HEADER -->
            <div class="wholesale-page-header">
                <div>
                    <div class="wholesale-breadcrumb">
                        <span>Sales & Marketing</span>
                        <span>/</span>
                        <span class="current">Wholesale</span>
                    </div>
                    <h1>Wholesale Clients</h1>
                    <p>Manage verified B2B corporate buyers, hotels, credit limits, discount terms, and quotations.</p>
                </div>

                <div class="header-actions">
                    <button type="button" class="btn btn-blue" id="printBtn">🖨 Print <small>P</small></button>
                    <button type="button" class="btn" id="pdfBtn">↓ PDF <small>V</small></button>
                    <button type="button" class="btn" id="excelBtn">↓ Excel <small>X</small></button>
                    <a href="../quotes/index.php" class="btn" id="quotesBtn">📑 Quotes <small>Q</small></a>
                    <a href="../bulk-pricing/index.php" class="btn btn-primary" id="bulkPricingBtn">🏷️ Bulk Pricing <small>B</small></a>
                </div>
            </div>

            <!-- NOTICES -->
            <?php if ($actionMessage !== ''): ?>
                <div class="notice notice-success"><?= e($actionMessage) ?></div>
            <?php endif; ?>
            <?php if ($actionError !== ''): ?>
                <div class="notice notice-error"><?= e($actionError) ?></div>
            <?php endif; ?>
            <?php if ($queryError !== ''): ?>
                <div class="notice notice-error"><?= e($queryError) ?></div>
            <?php endif; ?>

            <!-- STATISTICS CARDS -->
            <div class="wholesale-stats">
                <div class="wholesale-stat-item">
                    <div class="wholesale-stat-icon" style="background:var(--green-soft); color:var(--green);">🏢</div>
                    <div>
                        <div class="wholesale-stat-label">Approved B2B</div>
                        <div class="wholesale-stat-value"><?= $approvedCount ?></div>
                    </div>
                </div>

                <div class="wholesale-stat-item">
                    <div class="wholesale-stat-icon" style="background:var(--amber-soft); color:var(--amber);">⏳</div>
                    <div>
                        <div class="wholesale-stat-label">Pending Approval</div>
                        <div class="wholesale-stat-value"><?= $pendingCount ?></div>
                    </div>
                </div>

                <div class="wholesale-stat-item">
                    <div class="wholesale-stat-icon" style="background:var(--blue-soft); color:var(--blue);">📑</div>
                    <div>
                        <div class="wholesale-stat-label">Active Quotes</div>
                        <div class="wholesale-stat-value"><?= $activeQuotes ?></div>
                    </div>
                </div>

                <div class="wholesale-stat-item">
                    <div class="wholesale-stat-icon" style="background:rgba(139,92,246,.12); color:#a78bfa;">💬</div>
                    <div>
                        <div class="wholesale-stat-label">Bulk Inquiries</div>
                        <div class="wholesale-stat-value"><?= $openInquiries ?></div>
                    </div>
                </div>
            </div>

            <!-- MAIN CONTENT AREA -->
            <div class="wholesale-content">
                <div class="wholesale-content-header">
                    <div class="wholesale-content-title">
                        <h2>Wholesale Account Roster</h2>
                        <p>Company name, tax registration, discount percentages, order history, and account status.</p>
                    </div>

                    <div class="wholesale-filters">
                        <div class="wholesale-search-wrap">
                            <span class="wholesale-search-icon">⌕</span>
                            <input
                                type="search"
                                id="wholesaleSearch"
                                class="wholesale-search"
                                placeholder="Search company, contact, email, phone..."
                                autocomplete="off">
                        </div>

                        <select id="wholesaleStatusFilter" class="wholesale-status-filter">
                            <option value="all">All Wholesale</option>
                            <option value="approved">Approved Only</option>
                            <option value="pending">Pending Approval</option>
                        </select>
                    </div>
                </div>

                <!-- EXPORT BAR -->
                <div class="export-bar">
                    <span class="export-label">Reports & Exports</span>
                    <button type="button" class="btn" id="printBtn2">🖨 Print</button>
                    <button type="button" class="btn" id="pdfBtn2">↓ PDF</button>
                    <button type="button" class="btn" id="excelBtn2">↓ Excel</button>
                </div>

                <!-- SUMMARY -->
                <div class="wholesale-table-summary">
                    <div class="wholesale-result-text">
                        Showing <strong id="visibleCount"><?= $totalWholesale ?></strong> accounts
                    </div>
                    <div class="wholesale-result-text">
                        Total Registered: <strong><?= $totalWholesale ?></strong>
                    </div>
                </div>

                <!-- TABLE -->
                <div class="wholesale-table-wrapper">
                    <?php if (empty($allClients)): ?>
                        <div style="padding:60px 20px; text-align:center;">
                            <h3 style="color:var(--text-hi); margin:0;">No Wholesale Accounts Found</h3>
                            <p style="color:var(--text-mute); font-size:12px; margin-top:6px;">Clients with company details will appear here automatically.</p>
                        </div>
                    <?php else: ?>
                        <table class="wholesale-table" id="wholesaleTable">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Company & Contact</th>
                                    <th>Phone / Tax ID</th>
                                    <th>Status</th>
                                    <th>Discount</th>
                                    <th>Credit Limit</th>
                                    <th>Orders / Spent</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rowNo = 1;
                                foreach ($allClients as $client): 
                                    $isApproved = ((int)$client['IsWholesaleApproved'] === 1);
                                    $uId = (int)$client['UserId'];
                                ?>
                                <tr class="client-row"
                                    data-id="<?= $uId ?>"
                                    data-name="<?= e(strtolower($client['FullName'])) ?>"
                                    data-company="<?= e(strtolower($client['CompanyName'])) ?>"
                                    data-email="<?= e(strtolower($client['Email'])) ?>"
                                    data-phone="<?= e(strtolower($client['Phone'] ?? '')) ?>"
                                    data-tax="<?= e(strtolower($client['TaxNumber'])) ?>"
                                    data-status="<?= $isApproved ? 'approved' : 'pending' ?>">
                                    
                                    <td>
                                        <span class="order-box"><?= $rowNo++ ?></span>
                                    </td>

                                    <td>
                                        <strong style="color:var(--green); font-size:13px; display:block;"><?= e($client['CompanyName']) ?></strong>
                                        <div style="font-size:12px; color:var(--text-hi); font-weight:700; margin-top:2px;"><?= e($client['FullName']) ?></div>
                                        <div style="font-size:11px; color:var(--text-mute);"><?= e($client['Email']) ?></div>
                                    </td>

                                    <td>
                                        <div><?= e($client['Phone'] ?: '—') ?></div>
                                        <div style="font-size:10.5px; color:var(--text-mute); font-family:monospace; margin-top:2px;">Tax: <?= e($client['TaxNumber']) ?></div>
                                    </td>

                                    <td>
                                        <?php if ($isApproved): ?>
                                            <span class="wholesale-status wholesale-status-active">● Approved</span>
                                        <?php else: ?>
                                            <span class="wholesale-status wholesale-status-inactive">○ Pending</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <strong style="color:var(--green); font-size:13px;"><?= number_format((float)$client['WholesaleDiscountPct'], 1) ?>%</strong>
                                    </td>

                                    <td>
                                        <strong style="color:var(--text-hi); font-size:13px;">$<?= number_format((float)$client['CreditLimit'], 2) ?></strong>
                                    </td>

                                    <td>
                                        <div><strong><?= (int)$client['TotalOrders'] ?></strong> Orders</div>
                                        <div style="font-size:11px; color:var(--text-mute);">$<?= number_format((float)$client['TotalSpent'], 2) ?></div>
                                    </td>

                                    <td>
                                        <div class="wholesale-actions">
                                            <!-- VIEW DETAILS -->
                                            <button type="button" class="wholesale-action detail-btn"
                                                    title="View Profile Details"
                                                    data-id="<?= $uId ?>"
                                                    data-name="<?= e($client['FullName']) ?>"
                                                    data-company="<?= e($client['CompanyName']) ?>"
                                                    data-email="<?= e($client['Email']) ?>"
                                                    data-phone="<?= e($client['Phone']) ?>"
                                                    data-tax="<?= e($client['TaxNumber']) ?>"
                                                    data-status="<?= $isApproved ? 'Approved' : 'Pending' ?>"
                                                    data-discount="<?= $client['WholesaleDiscountPct'] ?>"
                                                    data-credit="<?= $client['CreditLimit'] ?>"
                                                    data-orders="<?= $client['TotalOrders'] ?>"
                                                    data-spent="<?= $client['TotalSpent'] ?>">
                                                ◉
                                            </button>

                                            <!-- TERMS / EDIT -->
                                            <button type="button" class="wholesale-action edit-terms-btn"
                                                    title="Edit Discount & Terms"
                                                    data-id="<?= $uId ?>"
                                                    data-company="<?= e($client['CompanyName']) ?>"
                                                    data-discount="<?= $client['WholesaleDiscountPct'] ?>"
                                                    data-credit="<?= $client['CreditLimit'] ?>">
                                                ✎
                                            </button>

                                            <!-- APPROVE / REJECT QUICK BUTTONS -->
                                            <?php if (!$isApproved): ?>
                                                <button type="button" class="wholesale-action approve-quick-btn"
                                                        title="Approve Wholesale Client"
                                                        style="color:var(--green) !important;"
                                                        data-id="<?= $uId ?>"
                                                        data-company="<?= e($client['CompanyName']) ?>">
                                                    ✓
                                                </button>
                                            <?php else: ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Revoke wholesale status for this client?');">
                                                    <input type="hidden" name="action" value="reject_wholesale">
                                                    <input type="hidden" name="user_id" value="<?= $uId ?>">
                                                    <button type="submit" class="wholesale-action" title="Revoke B2B status" style="color:var(--red) !important;">×</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- KEYBOARD SHORTCUTS ACCORDION / BOX -->
            <div class="shortcut-help-box" id="shortcutHelpBox">
                <div class="shortcut-help-title">
                    <span>⌨</span>
                    <span>Keyboard Shortcuts</span>
                    <small>A B C D E P V X Q H • Esc</small>
                </div>

                <div class="shortcut-grid">
                    <div class="shortcut-item"><span class="shortcut-key">B</span><span class="shortcut-desc">Search / Focus search</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">C</span><span class="shortcut-desc">Status Filter</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">A</span><span class="shortcut-desc">Approve Selected</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">E</span><span class="shortcut-desc">Edit Terms</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">P</span><span class="shortcut-desc">Print List</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">V</span><span class="shortcut-desc">Export PDF</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">X</span><span class="shortcut-desc">Export Excel</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">Q</span><span class="shortcut-desc">Go to Quotes</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">H</span><span class="shortcut-desc">Show/Hide Keys</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">Esc</span><span class="shortcut-desc">Close Modal/Clear</span></div>
                </div>
            </div>

        </div>
    </section>
</main>

<!-- DETAILS MODAL -->
<div class="modal-backdrop" id="wholesaleModal" aria-hidden="true">
    <div class="wholesale-modal" role="dialog" aria-modal="true">
        <div class="modal-header">
            <h3>B2B Client Overview</h3>
            <button type="button" class="modal-close" onclick="closeModal('wholesaleModal');">×</button>
        </div>
        <div class="modal-body">
            <div class="detail-grid">
                <div class="detail-card">
                    <div class="detail-item"><label>Company Name</label><div id="dCompany" style="font-weight:700; color:var(--green);">—</div></div>
                </div>
                <div class="detail-card">
                    <div class="detail-item"><label>Contact Person</label><div id="dName">—</div></div>
                </div>
                <div class="detail-card">
                    <div class="detail-item"><label>Email Address</label><div id="dEmail">—</div></div>
                </div>
                <div class="detail-card">
                    <div class="detail-item"><label>Phone Number</label><div id="dPhone">—</div></div>
                </div>
                <div class="detail-card">
                    <div class="detail-item"><label>Tax Registration Number</label><div id="dTax">—</div></div>
                </div>
                <div class="detail-card">
                    <div class="detail-item"><label>Account Status</label><div id="dStatus">—</div></div>
                </div>
                <div class="detail-card">
                    <div class="detail-item"><label>Standard B2B Discount</label><div id="dDiscount">—</div></div>
                </div>
                <div class="detail-card">
                    <div class="detail-item"><label>Approved Credit Line</label><div id="dCredit">—</div></div>
                </div>
                <div class="detail-card">
                    <div class="detail-item"><label>Lifetime Orders</label><div id="dOrders">—</div></div>
                </div>
                <div class="detail-card">
                    <div class="detail-item"><label>Total Amount Spent</label><div id="dSpent">—</div></div>
                </div>
            </div>
        </div>
        <div style="display:flex; justify-content:flex-end; padding:14px 18px; border-top:1px solid var(--border);">
            <button type="button" class="btn" onclick="closeModal('wholesaleModal');">Close</button>
        </div>
    </div>
</div>

<!-- TERMS / EDIT MODAL -->
<div class="modal-backdrop" id="termsModal" aria-hidden="true">
    <div class="wholesale-modal" style="width:480px;">
        <div class="modal-header">
            <h3 id="termsModalTitle">Adjust Wholesale Pricing</h3>
            <button type="button" class="modal-close" onclick="closeModal('termsModal');">×</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" id="termsAction" value="update_terms">
                <input type="hidden" name="user_id" id="termsUserId" value="0">

                <div style="margin-bottom:12px;">
                    <label style="font-size:11px; font-weight:700; color:var(--text-mute); text-transform:uppercase;">Wholesale Discount (%):</label>
                    <input type="number" step="0.5" min="0" max="100" name="discount_pct" id="termsDiscount" class="form-control" required>
                </div>

                <div style="margin-bottom:12px;">
                    <label style="font-size:11px; font-weight:700; color:var(--text-mute); text-transform:uppercase;">Credit Limit ($):</label>
                    <input type="number" step="100" min="0" name="credit_limit" id="termsCredit" class="form-control" required>
                </div>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px; padding:14px 18px; border-top:1px solid var(--border);">
                <button type="button" class="btn" onclick="closeModal('termsModal');">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Terms</button>
            </div>
        </form>
    </div>
</div>

<!-- PDF & EXCEL SCRIPTS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.4/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        const searchInput  = document.getElementById('wholesaleSearch');
        const statusFilter = document.getElementById('wholesaleStatusFilter');
        const table        = document.getElementById('wholesaleTable');
        const countElement = document.getElementById('visibleCount');
        const shortcutBox  = document.getElementById('shortcutHelpBox');

        function rows() {
            return table ? Array.from(table.querySelectorAll('tbody .client-row')) : [];
        }

        function visibleRows() {
            return rows().filter(r => r.style.display !== 'none');
        }

        function filterList() {
            if (!table) return;
            const q = (searchInput?.value || '').toLowerCase().trim();
            const status = statusFilter?.value || 'all';

            let count = 0;
            rows().forEach(function(row) {
                const text = [
                    row.dataset.name,
                    row.dataset.company,
                    row.dataset.email,
                    row.dataset.phone,
                    row.dataset.tax
                ].join(' ');

                const matchesQ = !q || text.includes(q);
                const matchesS = (status === 'all' || row.dataset.status === status);

                if (matchesQ && matchesS) {
                    row.style.display = '';
                    count++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (countElement) countElement.textContent = count;
            selectFirst(false);
        }

        function selectFirst(scroll) {
            rows().forEach(r => r.classList.remove('keyboard-selected'));
            const first = visibleRows()[0];
            if (first) {
                first.classList.add('keyboard-selected');
                if (scroll) first.scrollIntoView({ block: 'nearest' });
            }
        }

        searchInput?.addEventListener('input', filterList);
        statusFilter?.addEventListener('change', filterList);

        // MODAL TOGGLES
        window.closeModal = function(id) {
            const m = document.getElementById(id);
            if (m) m.classList.remove('show');
        };

        document.querySelectorAll('.detail-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                document.getElementById('dCompany').textContent  = this.dataset.company || '—';
                document.getElementById('dName').textContent     = this.dataset.name || '—';
                document.getElementById('dEmail').textContent    = this.dataset.email || '—';
                document.getElementById('dPhone').textContent    = this.dataset.phone || '—';
                document.getElementById('dTax').textContent      = this.dataset.tax || '—';
                document.getElementById('dStatus').textContent   = this.dataset.status || '—';
                document.getElementById('dDiscount').textContent = parseFloat(this.dataset.discount).toFixed(1) + '%';
                document.getElementById('dCredit').textContent   = '$' + parseFloat(this.dataset.credit).toFixed(2);
                document.getElementById('dOrders').textContent   = this.dataset.orders || '0';
                document.getElementById('dSpent').textContent    = '$' + parseFloat(this.dataset.spent).toFixed(2);

                document.getElementById('wholesaleModal').classList.add('show');
            });
        });

        document.querySelectorAll('.edit-terms-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                document.getElementById('termsModalTitle').textContent = 'Edit Terms: ' + this.dataset.company;
                document.getElementById('termsAction').value = 'update_terms';
                document.getElementById('termsUserId').value = this.dataset.id;
                document.getElementById('termsDiscount').value = this.dataset.discount;
                document.getElementById('termsCredit').value = this.dataset.credit;

                document.getElementById('termsModal').classList.add('show');
            });
        });

        document.querySelectorAll('.approve-quick-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                document.getElementById('termsModalTitle').textContent = 'Approve: ' + this.dataset.company;
                document.getElementById('termsAction').value = 'approve_wholesale';
                document.getElementById('termsUserId').value = this.dataset.id;
                document.getElementById('termsDiscount').value = '15.0';
                document.getElementById('termsCredit').value = '5000';

                document.getElementById('termsModal').classList.add('show');
            });
        });

        // ROW SELECTION
        rows().forEach(function(row) {
            row.addEventListener('click', function(e) {
                if (e.target.closest('button, a, form')) return;
                rows().forEach(r => r.classList.remove('keyboard-selected'));
                row.classList.add('keyboard-selected');
            });
        });

        // REPORTS
        function exportExcel() {
            const data = visibleRows().map(function(r) {
                return {
                    'No': r.querySelector('.order-box')?.innerText.trim() || '',
                    'Company': r.dataset.company,
                    'Contact': r.dataset.name,
                    'Email': r.dataset.email,
                    'Phone': r.dataset.phone,
                    'Tax ID': r.dataset.tax,
                    'Status': r.dataset.status.toUpperCase(),
                    'Discount': r.children[4]?.innerText.trim() || '0%',
                    'Credit Limit': r.children[5]?.innerText.trim() || '$0'
                };
            });
            if (window.XLSX) {
                const ws = XLSX.utils.json_to_sheet(data);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Wholesale');
                XLSX.writeFile(wb, 'wholesale-clients-' + new Date().toISOString().slice(0, 10) + '.xlsx');
            }
        }

        function exportPdf() {
            if (!window.jspdf) return;
            const body = visibleRows().map(function(r) {
                return [
                    r.querySelector('.order-box')?.innerText.trim() || '',
                    r.dataset.company,
                    r.dataset.name,
                    r.dataset.phone,
                    r.dataset.tax,
                    r.dataset.status.toUpperCase(),
                    r.children[4]?.innerText.trim() || '0%',
                    r.children[5]?.innerText.trim() || '$0'
                ];
            });

            const doc = new jspdf.jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
            doc.setFontSize(15);
            doc.text('GatewayLinen - Wholesale Clients Roster', 14, 14);
            doc.autoTable({
                startY: 22,
                head: [['No', 'Company', 'Contact', 'Phone', 'Tax ID', 'Status', 'Discount', 'Credit Limit']],
                body: body,
                styles: { fontSize: 8 }
            });
            doc.save('wholesale-clients-' + new Date().toISOString().slice(0, 10) + '.pdf');
        }

        document.getElementById('printBtn')?.addEventListener('click', () => window.print());
        document.getElementById('printBtn2')?.addEventListener('click', () => window.print());
        document.getElementById('excelBtn')?.addEventListener('click', exportExcel);
        document.getElementById('excelBtn2')?.addEventListener('click', exportExcel);
        document.getElementById('pdfBtn')?.addEventListener('click', exportPdf);
        document.getElementById('pdfBtn2')?.addEventListener('click', exportPdf);

        // KEYBOARD SHORTCUTS
        document.addEventListener('keydown', function(e) {
            const tag = (e.target?.tagName || '').toLowerCase();
            if (tag === 'input' || tag === 'textarea' || tag === 'select') return;

            const key = (e.key || '').toUpperCase();

            if (['A', 'B', 'C', 'E', 'P', 'V', 'X', 'Q', 'H'].includes(key)) {
                e.preventDefault();
                e.stopPropagation();
            }

            if (key === 'B') {
                searchInput?.focus();
                searchInput?.select();
            } else if (key === 'C') {
                statusFilter?.focus();
            } else if (key === 'A') {
                const sel = document.querySelector('.client-row.keyboard-selected') || visibleRows()[0];
                sel?.querySelector('.approve-quick-btn')?.click();
            } else if (key === 'E') {
                const sel = document.querySelector('.client-row.keyboard-selected') || visibleRows()[0];
                sel?.querySelector('.edit-terms-btn')?.click();
            } else if (key === 'P') {
                window.print();
            } else if (key === 'V') {
                exportPdf();
            } else if (key === 'X') {
                exportExcel();
            } else if (key === 'Q') {
                document.getElementById('quotesBtn')?.click();
            } else if (key === 'H') {
                shortcutBox?.classList.toggle('hidden');
            } else if (key === 'ESCAPE') {
                closeModal('wholesaleModal');
                closeModal('termsModal');
                if (searchInput?.value) {
                    searchInput.value = '';
                    filterList();
                }
                searchInput?.blur();
                statusFilter?.blur();
            }
        }, true);

        filterList();
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>