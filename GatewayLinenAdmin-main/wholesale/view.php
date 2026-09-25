<?php
session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$activeMenu = 'wholesale';
$pageTitle  = 'GatewayLinen | Wholesale Client Details';

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function formatDate($value): string {
    if ($value instanceof DateTimeInterface) {
        return $value->format('d M Y, h:i A');
    }
    return !empty($value) ? date('d M Y, h:i A', strtotime($value)) : '—';
}

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($userId <= 0) {
    header('Location: index.php?error=' . urlencode('Invalid client selected.'));
    exit;
}

// 1. Fetch Client Profile
$uSql = "SELECT * FROM dbo.Users WHERE UserId = ?";
$uStmt = sqlsrv_query($conn, $uSql, [$userId]);
$client = ($uStmt !== false) ? sqlsrv_fetch_array($uStmt, SQLSRV_FETCH_ASSOC) : null;
if ($uStmt !== false) sqlsrv_free_stmt($uStmt);

if (!$client) {
    header('Location: index.php?error=' . urlencode('Wholesale client not found.'));
    exit;
}

// 2. Fetch Client Orders
$ordersSql = "SELECT OrderId, OrderNumber, TotalAmount, OrderStatus, CreatedAt FROM dbo.Orders WHERE UserId = ? ORDER BY OrderId DESC";
$ordersStmt = sqlsrv_query($conn, $ordersSql, [$userId]);
$clientOrders = [];
$totalSpent = 0;
if ($ordersStmt !== false) {
    while ($r = sqlsrv_fetch_array($ordersStmt, SQLSRV_FETCH_ASSOC)) {
        $clientOrders[] = $r;
        $totalSpent += (float)($r['TotalAmount'] ?? 0);
    }
    sqlsrv_free_stmt($ordersStmt);
}

// 3. Fetch Client Quotes
$quotesSql = "SELECT QuoteId, QuoteNumber, TotalQuotedAmount, Status, CreatedAt FROM dbo.Quotes WHERE UserId = ? ORDER BY QuoteId DESC";
$quotesStmt = sqlsrv_query($conn, $quotesSql, [$userId]);
$clientQuotes = [];
if ($quotesStmt !== false) {
    while ($qr = sqlsrv_fetch_array($quotesStmt, SQLSRV_FETCH_ASSOC)) {
        $clientQuotes[] = $qr;
    }
    sqlsrv_free_stmt($quotesStmt);
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
    --blue: #38bdf8;
    --blue-soft: rgba(56,189,248,.15);
}

html, body, .main, .content { background: var(--bg-page) !important; color: var(--text-body) !important; }
.view-wrapper { width: 100%; max-width: 1400px; margin: 0 auto; padding-bottom: 50px; }

.page-header {
    display: flex; justify-content: space-between; align-items: flex-end;
    padding-bottom: 20px; border-bottom: 1px solid var(--border); margin-bottom: 24px;
}
.btn {
    display: inline-flex; align-items: center; gap: 7px; height: 38px; padding: 0 16px;
    border-radius: 8px; border: 1px solid var(--border); background: var(--bg-input);
    color: var(--text-body); font-size: 12px; font-weight: 700; text-decoration: none; cursor: pointer;
}
.btn:hover { border-color: var(--green); color: var(--green); }
.btn-primary { background: linear-gradient(135deg, #059669, #10b981); color: #fff !important; border: 0; }

.grid-layout { display: grid; grid-template-columns: 360px 1fr; gap: 20px; }
.card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; margin-bottom: 20px; }
.card-header { padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
.card-header h2 { margin: 0; font-size: 13.5px; font-weight: 800; color: var(--text-hi); text-transform: uppercase; }
.card-body { padding: 20px; }

.info-item { display: flex; justify-content: space-between; padding: 9px 0; border-bottom: 1px solid var(--border-soft); font-size: 12.5px; }
.info-item:last-child { border-bottom: none; }
.info-label { color: var(--text-mute); font-weight: 600; }
.info-value { color: var(--text-hi); font-weight: 700; text-align: right; }

.table-data { width: 100%; border-collapse: collapse; }
.table-data th { background: #0d1620; padding: 10px 14px; text-align: left; font-size: 11px; text-transform: uppercase; color: var(--text-mute); border-bottom: 1px solid var(--border); }
.table-data td { padding: 12px 14px; border-bottom: 1px solid var(--border-soft); font-size: 12px; }
.badge { padding: 3px 8px; border-radius: 12px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
</style>

<main class="main">
    <section class="content">
        <div class="view-wrapper">
            <div class="page-header">
                <div>
                    <h1 style="margin:0; font-size:24px; color:var(--text-hi); font-weight:800;"><?= e($client['CompanyName'] ?: $client['FullName']) ?></h1>
                    <span style="font-size:12px; color:var(--text-mute);">Corporate Wholesale Account Profile</span>
                </div>
                <div style="display:flex; gap:10px;">
                    <a href="edit.php?id=<?= $userId ?>" class="btn btn-primary">✎ Edit Terms</a>
                    <a href="index.php" class="btn">← Back to Roster</a>
                </div>
            </div>

            <div class="grid-layout">
                <!-- LEFT: PROFILE SUMMARY -->
                <div>
                    <div class="card">
                        <div class="card-header"><h2>Client Information</h2></div>
                        <div class="card-body">
                            <div class="info-item"><span class="info-label">Contact Person</span><span class="info-value"><?= e($client['FullName']) ?></span></div>
                            <div class="info-item"><span class="info-label">Email</span><span class="info-value"><?= e($client['Email']) ?></span></div>
                            <div class="info-item"><span class="info-label">Phone</span><span class="info-value"><?= e($client['Phone'] ?: '—') ?></span></div>
                            <div class="info-item"><span class="info-label">Tax / GST ID</span><span class="info-value"><?= e($client['TaxNumber'] ?: 'N/A') ?></span></div>
                            <div class="info-item"><span class="info-label">Status</span>
                                <span class="info-value" style="color:<?= $client['IsWholesaleApproved'] ? 'var(--green)' : 'var(--amber)' ?>;">
                                    <?= $client['IsWholesaleApproved'] ? 'Approved B2B' : 'Pending Approval' ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h2>Commercial Terms</h2></div>
                        <div class="card-body">
                            <div class="info-item"><span class="info-label">Slab Discount</span><span class="info-value" style="color:var(--green); font-size:14px;"><?= number_format((float)$client['WholesaleDiscountPct'], 1) ?>%</span></div>
                            <div class="info-item"><span class="info-label">Credit Line Limit</span><span class="info-value">$<?= number_format((float)$client['CreditLimit'], 2) ?></span></div>
                            <div class="info-item"><span class="info-label">Lifetime Spend</span><span class="info-value" style="color:var(--text-hi); font-size:14px;">$<?= number_format($totalSpent, 2) ?></span></div>
                            <div class="info-item"><span class="info-label">Total Orders</span><span class="info-value"><?= count($clientOrders) ?></span></div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: ORDERS & QUOTES -->
                <div>
                    <div class="card">
                        <div class="card-header"><h2>B2B Order History (<?= count($clientOrders) ?>)</h2></div>
                        <div class="card-body" style="padding:0;">
                            <table class="table-data">
                                <thead><tr><th>Order #</th><th>Date</th><th>Status</th><th style="text-align:right;">Amount</th></tr></thead>
                                <tbody>
                                    <?php if(empty($clientOrders)): ?>
                                        <tr><td colspan="4" style="text-align:center; padding:25px; color:var(--text-mute);">No orders placed yet.</td></tr>
                                    <?php else: foreach($clientOrders as $o): ?>
                                        <tr>
                                            <td><a href="../orders/view.php?id=<?= $o['OrderId'] ?>" style="color:var(--blue); font-weight:700; text-decoration:none;"><?= e($o['OrderNumber'] ?: $o['OrderId']) ?></a></td>
                                            <td><?= formatDate($o['CreatedAt']) ?></td>
                                            <td><span class="badge" style="background:var(--green-soft); color:var(--green);"><?= e($o['OrderStatus']) ?></span></td>
                                            <td style="text-align:right; font-weight:800; color:var(--text-hi);">$<?= number_format((float)$o['TotalAmount'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h2>Active Quotations (<?= count($clientQuotes) ?>)</h2></div>
                        <div class="card-body" style="padding:0;">
                            <table class="table-data">
                                <thead><tr><th>Quote #</th><th>Requested On</th><th>Status</th><th style="text-align:right;">Quoted Value</th></tr></thead>
                                <tbody>
                                    <?php if(empty($clientQuotes)): ?>
                                        <tr><td colspan="4" style="text-align:center; padding:25px; color:var(--text-mute);">No quotations requested.</td></tr>
                                    <?php else: foreach($clientQuotes as $q): ?>
                                        <tr>
                                            <td><strong style="color:var(--text-hi);"><?= e($q['QuoteNumber'] ?: $q['QuoteId']) ?></strong></td>
                                            <td><?= formatDate($q['CreatedAt']) ?></td>
                                            <td><span class="badge" style="background:var(--blue-soft); color:var(--blue);"><?= e($q['Status']) ?></span></td>
                                            <td style="text-align:right; font-weight:800; color:var(--text-hi);">$<?= number_format((float)$q['TotalQuotedAmount'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>