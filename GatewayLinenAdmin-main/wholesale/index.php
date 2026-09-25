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

require_once __DIR__ . '/../config/database.php';

$activeMenu = 'wholesale';
$pageTitle  = 'GatewayLinen | Wholesale Clients Management';

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function formatDate($value): string {
    if ($value instanceof DateTimeInterface) {
        return $value->format('d M Y');
    }
    return !empty($value) ? date('d M Y', strtotime($value)) : '—';
}

$message = '';
$error = '';

/*
|--------------------------------------------------------------------------
| HANDLE ACTIONS: APPROVE / REJECT / UPDATE DISCOUNT & CREDIT LIMIT
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $targetUserId = (int)($_POST['user_id'] ?? 0);
    $actionType   = trim($_POST['action']);

    if ($targetUserId > 0) {
        // APPROVE WHOLESALE CLIENT
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
                $message = "Wholesale client approved successfully with " . $discount . "% discount.";
            } else {
                $error = "Failed to approve wholesale client.";
            }
        }

        // REVOKE / REJECT WHOLESALE ACCESS
        if ($actionType === 'reject_wholesale') {
            $upSql = "UPDATE dbo.Users 
                      SET IsWholesaleApproved = 0 
                      WHERE UserId = ?";
            $upStmt = sqlsrv_query($conn, $upSql, [$targetUserId]);
            if ($upStmt !== false) {
                $message = "Wholesale access revoked/rejected successfully.";
            } else {
                $error = "Failed to update client status.";
            }
        }

        // UPDATE DISCOUNT OR CREDIT LIMIT
        if ($actionType === 'update_terms') {
            $discount = (float)($_POST['discount_pct'] ?? 0);
            $credit   = (float)($_POST['credit_limit'] ?? 0);

            $upSql = "UPDATE dbo.Users 
                      SET WholesaleDiscountPct = ?, 
                          CreditLimit = ? 
                      WHERE UserId = ?";
            $upStmt = sqlsrv_query($conn, $upSql, [$discount, $credit, $targetUserId]);
            if ($upStmt !== false) {
                $message = "B2B client terms & credit limits updated.";
            } else {
                $error = "Failed to update terms.";
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| FETCH METRICS (COUNTING FROM DB)
|--------------------------------------------------------------------------
*/
// 1. Total Approved Wholesale Accounts
$approvedCount = 0;
$qApproved = sqlsrv_query($conn, "SELECT COUNT(*) AS Cnt FROM dbo.Users WHERE IsWholesaleApproved = 1");
if ($qApproved && $r = sqlsrv_fetch_array($qApproved, SQLSRV_FETCH_ASSOC)) {
    $approvedCount = (int)$r['Cnt'];
}

// 2. Pending Requests (Company filled but not approved yet)
$pendingCount = 0;
$qPending = sqlsrv_query($conn, "SELECT COUNT(*) AS Cnt FROM dbo.Users WHERE (CompanyName IS NOT NULL AND CompanyName != '') AND (IsWholesaleApproved = 0 OR IsWholesaleApproved IS NULL)");
if ($qPending && $r = sqlsrv_fetch_array($qPending, SQLSRV_FETCH_ASSOC)) {
    $pendingCount = (int)$r['Cnt'];
}

// 3. Active Quotations (from dbo.Quotes)
$activeQuotes = 0;
$qQuotes = sqlsrv_query($conn, "SELECT COUNT(*) AS Cnt FROM dbo.Quotes WHERE Status IN ('Pending', 'Active', 'Sent')");
if ($qQuotes && $r = sqlsrv_fetch_array($qQuotes, SQLSRV_FETCH_ASSOC)) {
    $activeQuotes = (int)$r['Cnt'];
}

// 4. Bulk Inquiries (from dbo.BulkInquiries)
$openInquiries = 0;
$qInq = sqlsrv_query($conn, "SELECT COUNT(*) AS Cnt FROM dbo.BulkInquiries WHERE Status IN ('New', 'Pending', 'Open')");
if ($qInq && $r = sqlsrv_fetch_array($qInq, SQLSRV_FETCH_ASSOC)) {
    $openInquiries = (int)$r['Cnt'];
}

/*
|--------------------------------------------------------------------------
| FETCH WHOLESALE CLIENTS LIST
|--------------------------------------------------------------------------
*/
$filter = trim($_GET['tab'] ?? 'all');
$whereSql = "WHERE (u.IsWholesaleApproved = 1 OR (u.CompanyName IS NOT NULL AND u.CompanyName != ''))";

if ($filter === 'approved') {
    $whereSql = "WHERE u.IsWholesaleApproved = 1";
} elseif ($filter === 'pending') {
    $whereSql = "WHERE (u.CompanyName IS NOT NULL AND u.CompanyName != '') AND (u.IsWholesaleApproved = 0 OR u.IsWholesaleApproved IS NULL)";
}

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
        $whereSql
        ORDER BY u.UserId DESC";

$stmt = sqlsrv_query($conn, $sql);
$clients = [];
if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $clients[] = $row;
    }
    sqlsrv_free_stmt($stmt);
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<style>
:root {
    --bg-page: #0a1119;
    --bg-card: #111b26;
    --bg-card-alt: #0d1620;
    --border: #1e2d3d;
    --border-soft: #182636;
    --text-hi: #f0f4f8;
    --text-body: #a8b8c8;
    --text-mute: #5f7488;
    --green: #10b981;
    --green-soft: rgba(16,185,129,.12);
    --blue: #3b82f6;
    --blue-soft: rgba(59,130,246,.12);
    --amber: #f59e0b;
    --amber-soft: rgba(245,158,11,.12);
    --red: #ef4444;
    --red-soft: rgba(239,68,68,.12);
}

html, body, .main, .content {
    background: var(--bg-page) !important;
    color: var(--text-body) !important;
}

.wholesale-page {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 0 50px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border);
    flex-wrap: wrap;
    gap: 15px;
}

.page-title { margin: 0; font-size: 26px; font-weight: 800; color: var(--text-hi); }
.page-sub { font-size: 12px; color: var(--text-mute); margin-top: 4px; display: block; }

/* SHORTCUT KEY BADGES */
.key-badge {
    font-size: 9.5px;
    font-family: monospace;
    font-weight: 800;
    padding: 2px 6px;
    border-radius: 4px;
    background: #1e2d3d;
    border: 1px solid rgba(255,255,255,0.15);
    color: #cbd5e1;
    box-shadow: 0 1px 3px rgba(0,0,0,0.4);
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* METRICS */
.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.metric-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
}

.metric-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.metric-val { font-size: 22px; font-weight: 800; color: var(--text-hi); line-height: 1.1; }
.metric-label { font-size: 11px; font-weight: 700; color: var(--text-mute); text-transform: uppercase; letter-spacing: .5px; margin-top: 2px; }

/* FILTERS */
.filter-bar {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    align-items: center;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    background: var(--bg-card);
    border: 1px solid var(--border);
    color: var(--text-body);
    transition: .15s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.filter-btn:hover, .filter-btn.active {
    border-color: var(--green);
    background: var(--green-soft);
    color: var(--green);
}

/* DATA TABLE */
.card-table {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 14px;
    overflow: hidden;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: #0d1620;
    padding: 12px 16px;
    color: var(--text-mute);
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .6px;
    border-bottom: 1px solid var(--border);
    text-align: left;
}

.data-table td {
    padding: 14px 16px;
    border-bottom: 1px solid var(--border-soft);
    color: var(--text-body);
    font-size: 12.5px;
    vertical-align: middle;
}

.data-table tr:hover td { background: rgba(255,255,255,0.015); }

.badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    display: inline-block;
}
.badge-approved { background: var(--green-soft); color: var(--green); }
.badge-pending  { background: var(--amber-soft); color: var(--amber); }

.btn-action {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    text-decoration: none;
    border: 1px solid var(--border);
    background: #0d1620;
    color: var(--text-hi);
    cursor: pointer;
    transition: .15s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.btn-action:hover { border-color: var(--blue); color: #60a5fa; }

.btn-approve {
    border-color: rgba(16,185,129,0.3);
    color: #6ee7b7;
    background: var(--green-soft);
}
.btn-approve:hover { background: var(--green); color: #fff; }

.btn-reject {
    border-color: rgba(239,68,68,0.3);
    color: #fca5a5;
    background: var(--red-soft);
}
.btn-reject:hover { background: var(--red); color: #fff; }

.notice-success {
    padding: 12px 16px;
    background: var(--green-soft);
    border: 1px solid rgba(16,185,129,.3);
    border-radius: 8px;
    color: #6ee7b7;
    margin-bottom: 20px;
    font-size: 12px;
    font-weight: 600;
}
.notice-error {
    padding: 12px 16px;
    background: var(--red-soft);
    border: 1px solid rgba(239,68,68,.3);
    border-radius: 8px;
    color: #fca5a5;
    margin-bottom: 20px;
    font-size: 12px;
    font-weight: 600;
}

/* MODAL */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.8);
    backdrop-filter: blur(4px);
    z-index: 99999;
    align-items: center;
    justify-content: center;
}
.modal-overlay.active { display: flex; }
.modal-box {
    background: var(--bg-card);
    border: 1px solid var(--border);
    width: 100%;
    max-width: 440px;
    border-radius: 14px;
    padding: 22px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.7);
}
.form-control {
    width: 100%;
    height: 38px;
    background: var(--bg-card-alt);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text-hi);
    padding: 0 12px;
    margin-bottom: 12px;
    box-sizing: border-box;
}

.shortcuts-hint-bar {
    display: flex;
    gap: 12px;
    align-items: center;
    background: #0f1823;
    padding: 8px 14px;
    border-radius: 8px;
    border: 1px solid var(--border);
    margin-bottom: 16px;
    font-size: 11px;
    color: var(--text-mute);
    flex-wrap: wrap;
}
</style>

<main class="main">
    <section class="content">
        <div class="wholesale-page">

            <!-- PAGE HEADER -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">Wholesale & B2B Management</h1>
                    <span class="page-sub">Manage corporate clients, hotel contracts, bulk discount tiers & credit limits.</span>
                </div>
                <div style="display:flex; gap:10px;">
                    <a href="../quotes/index.php" id="linkQuotes" class="btn-action" style="padding:9px 14px;" title="Short key: Q">
                        📑 View Quotes (<?= $activeQuotes ?>) <span class="key-badge">Q</span>
                    </a>
                    <a href="../bulk-pricing/index.php" id="linkPricing" class="btn-action" style="padding:9px 14px; border-color:var(--green); color:var(--green);" title="Short key: B">
                        🏷️ Bulk Price Slabs <span class="key-badge">B</span>
                    </a>
                </div>
            </div>

            <!-- SHORTCUTS BAR -->
            <div class="shortcuts-hint-bar">
                <span style="font-weight:700; color:var(--text-hi);">⚡ Short Keys:</span>
                <span><span class="key-badge">1</span> All</span>
                <span><span class="key-badge">2</span> Approved</span>
                <span><span class="key-badge">3</span> Pending</span>
                <span><span class="key-badge">A</span> Quick Approve First</span>
                <span><span class="key-badge">Q</span> Quotes</span>
                <span><span class="key-badge">B</span> Bulk Slabs</span>
                <span><span class="key-badge">Esc</span> Close Modal</span>
            </div>

            <?php if ($message !== ''): ?>
                <div class="notice-success">✓ <?= e($message) ?></div>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
                <div class="notice-error">! <?= e($error) ?></div>
            <?php endif; ?>

            <!-- STATS COUNTERS -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-icon" style="background:var(--green-soft); color:var(--green);">🏢</div>
                    <div>
                        <div class="metric-val"><?= $approvedCount ?></div>
                        <div class="metric-label">Approved Wholesale</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon" style="background:var(--amber-soft); color:var(--amber);">⏳</div>
                    <div>
                        <div class="metric-val"><?= $pendingCount ?></div>
                        <div class="metric-label">Pending Approval</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon" style="background:var(--blue-soft); color:var(--blue);">📑</div>
                    <div>
                        <div class="metric-val"><?= $activeQuotes ?></div>
                        <div class="metric-label">Active Quotes</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon" style="background:rgba(139,92,246,.12); color:#a78bfa;">💬</div>
                    <div>
                        <div class="metric-val"><?= $openInquiries ?></div>
                        <div class="metric-label">Bulk Inquiries</div>
                    </div>
                </div>
            </div>

            <!-- TABS -->
            <div class="filter-bar">
                <a href="index.php?tab=all" id="tabAll" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">
                    All Accounts (<?= count($clients) ?>) <span class="key-badge">1</span>
                </a>
                <a href="index.php?tab=approved" id="tabApproved" class="filter-btn <?= $filter === 'approved' ? 'active' : '' ?>">
                    Approved B2B (<?= $approvedCount ?>) <span class="key-badge">2</span>
                </a>
                <a href="index.php?tab=pending" id="tabPending" class="filter-btn <?= $filter === 'pending' ? 'active' : '' ?>">
                    Pending Applications (<?= $pendingCount ?>) <span class="key-badge">3</span>
                </a>
            </div>

            <!-- DATA TABLE -->
            <div class="card-table">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Company & Contact</th>
                            <th>Phone / Tax Number</th>
                            <th>B2B Status</th>
                            <th>Discount Slab</th>
                            <th>Credit Limit</th>
                            <th>Order Stats</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clients)): ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding:40px; color:var(--text-mute);">
                                    No records found for this category.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $firstPendingFound = false;
                            foreach ($clients as $c): 
                                $isApproved = ((int)$c['IsWholesaleApproved'] === 1);
                                $isFirstPending = false;
                                if (!$isApproved && !$firstPendingFound) {
                                    $firstPendingFound = true;
                                    $isFirstPending = true;
                                }
                            ?>
                            <tr>
                                <td>
                                    <strong style="color:var(--text-hi); font-size:13.5px;"><?= e($c['CompanyName']) ?></strong>
                                    <div style="font-size:11.5px; color:var(--green); margin-top:2px;"><?= e($c['FullName']) ?></div>
                                    <div style="font-size:11px; color:var(--text-mute);"><?= e($c['Email']) ?></div>
                                </td>
                                <td>
                                    <div><?= e($c['Phone'] ?: '—') ?></div>
                                    <div style="font-size:10.5px; color:var(--text-mute); font-family:monospace;">Tax ID: <?= e($c['TaxNumber']) ?></div>
                                </td>
                                <td>
                                    <?php if ($isApproved): ?>
                                        <span class="badge badge-approved">✓ Approved</span>
                                    <?php else: ?>
                                        <span class="badge badge-pending">Pending Approval</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong style="color:var(--green); font-size:13px;"><?= number_format((float)$c['WholesaleDiscountPct'], 1) ?>%</strong> OFF
                                </td>
                                <td>
                                    <strong style="color:var(--text-hi);">$<?= number_format((float)$c['CreditLimit'], 2) ?></strong>
                                </td>
                                <td>
                                    <div><strong><?= (int)$c['TotalOrders'] ?></strong> Orders</div>
                                    <div style="font-size:11px; color:var(--text-mute);">$<?= number_format((float)$c['TotalSpent'], 2) ?> total</div>
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:inline-flex; gap:6px;">
                                        <?php if (!$isApproved): ?>
                                            <button type="button" class="btn-action btn-approve js-first-approve-btn" 
                                                    onclick="openApprovalModal(<?= $c['UserId'] ?>, '<?= e(addslashes($c['CompanyName'])) ?>')">
                                                ✓ Approve <?php if ($isFirstPending): ?><span class="key-badge">A</span><?php endif; ?>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn-action" 
                                                    onclick="openTermsModal(<?= $c['UserId'] ?>, <?= $c['WholesaleDiscountPct'] ?>, <?= $c['CreditLimit'] ?>)">
                                                ⚙️ Terms
                                            </button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Revoke wholesale access?');">
                                                <input type="hidden" name="action" value="reject_wholesale">
                                                <input type="hidden" name="user_id" value="<?= $c['UserId'] ?>">
                                                <button type="submit" class="btn-action btn-reject" title="Revoke B2B status">Revoke</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </section>
</main>

<!-- APPROVAL / TERMS MODAL -->
<div class="modal-overlay" id="termsModal">
    <div class="modal-box">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
            <h3 style="margin:0; font-size:15px; color:var(--text-hi);" id="modalTitle">Wholesale Pricing Terms</h3>
            <span class="key-badge" style="cursor:pointer;" onclick="closeTermsModal();">Esc</span>
        </div>
        <form method="POST" id="termsForm">
            <input type="hidden" name="action" id="modalAction" value="approve_wholesale">
            <input type="hidden" name="user_id" id="modalUserId" value="0">

            <label style="font-size:11px; font-weight:700; color:var(--text-mute); display:block; margin-bottom:4px;">WHOLESALE DISCOUNT (%):</label>
            <input type="number" step="0.5" min="0" max="100" name="discount_pct" id="modalDiscount" class="form-control" value="10.0" required>

            <label style="font-size:11px; font-weight:700; color:var(--text-mute); display:block; margin-bottom:4px;">CREDIT LIMIT ($):</label>
            <input type="number" step="100" min="0" name="credit_limit" id="modalCredit" class="form-control" value="5000" required>

            <div style="display:flex; gap:10px; margin-top:15px; align-items:center;">
                <button type="submit" class="btn-action btn-approve" style="flex:1; justify-content:center; height:38px;">
                    Save & Apply <span class="key-badge" style="margin-left:6px;">Enter</span>
                </button>
                <button type="button" class="btn-action" style="height:38px;" onclick="closeTermsModal();">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openApprovalModal(userId, company) {
    document.getElementById('modalTitle').innerText = 'Approve: ' + company;
    document.getElementById('modalAction').value = 'approve_wholesale';
    document.getElementById('modalUserId').value = userId;
    document.getElementById('modalDiscount').value = '15.0';
    document.getElementById('modalCredit').value = '5000';
    document.getElementById('termsModal').classList.add('active');
    setTimeout(() => {
        document.getElementById('modalDiscount').focus();
    }, 100);
}

function openTermsModal(userId, discount, credit) {
    document.getElementById('modalTitle').innerText = 'Edit B2B Terms & Limits';
    document.getElementById('modalAction').value = 'update_terms';
    document.getElementById('modalUserId').value = userId;
    document.getElementById('modalDiscount').value = discount;
    document.getElementById('modalCredit').value = credit;
    document.getElementById('termsModal').classList.add('active');
    setTimeout(() => {
        document.getElementById('modalDiscount').focus();
    }, 100);
}

function closeTermsModal() {
    document.getElementById('termsModal').classList.remove('active');
}

/* =========================================================
   KEYBOARD SHORTCUTS INTEGRATION
========================================================= */
window.addEventListener('keydown', (e) => {
    const modal = document.getElementById('termsModal');
    const isModalOpen = modal.classList.contains('active');

    // 1. Escape to close Modal
    if (e.key === 'Escape' && isModalOpen) {
        e.preventDefault();
        closeTermsModal();
        return;
    }

    // Input fields ke andar typing karte waqt shortcuts ko block karein
    if (['INPUT', 'SELECT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
        return;
    }

    // 2. Tab Navigation: 1, 2, 3
    if (e.key === '1') {
        e.preventDefault();
        document.getElementById('tabAll').click();
    } else if (e.key === '2') {
        e.preventDefault();
        document.getElementById('tabApproved').click();
    } else if (e.key === '3') {
        e.preventDefault();
        document.getElementById('tabPending').click();
    }

    // 3. Q -> Quotes, B -> Bulk Pricing
    else if (e.key.toLowerCase() === 'q') {
        e.preventDefault();
        document.getElementById('linkQuotes').click();
    } else if (e.key.toLowerCase() === 'b') {
        e.preventDefault();
        document.getElementById('linkPricing').click();
    }

    // 4. A -> Quick Approve First Pending Client
    else if (e.key.toLowerCase() === 'a') {
        const firstBtn = document.querySelector('.js-first-approve-btn');
        if (firstBtn) {
            e.preventDefault();
            firstBtn.click();
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>