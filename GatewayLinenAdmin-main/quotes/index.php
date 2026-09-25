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

$activeMenu = 'quotes';
$pageTitle  = 'GatewayLinen | Price Quotations Management';

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] = $_SESSION['admin_username'] ?? 'GatewayLinen Administrator';
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
| UPDATE QUOTE STATUS / CONVERT TO ORDER
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $quoteId = (int)($_POST['quote_id'] ?? 0);
    $actionType = trim($_POST['action']);

    if ($quoteId > 0) {
        if ($actionType === 'update_status') {
            $newStatus = trim($_POST['status'] ?? 'Pending');
            $upSql = "UPDATE dbo.Quotes SET Status = ? WHERE QuoteId = ?";
            $upStmt = sqlsrv_query($conn, $upSql, [$newStatus, $quoteId]);
            if ($upStmt !== false) {
                header('Location: index.php?success=' . urlencode("Quote status updated to $newStatus successfully."));
                exit;
            } else {
                $actionError = "Failed to update quote status.";
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| FETCH METRICS
|--------------------------------------------------------------------------
*/
$totalQuotes = 0;
$pendingQuotes = 0;
$approvedQuotes = 0;
$convertedOrders = 0;

$statSql = "SELECT 
    COUNT(*) AS TotalCount,
    COUNT(CASE WHEN Status = 'Pending' THEN 1 END) AS PendingCount,
    COUNT(CASE WHEN Status IN ('Approved', 'Accepted') THEN 1 END) AS ApprovedCount,
    COUNT(CASE WHEN ConvertedOrderId IS NOT NULL THEN 1 END) AS ConvertedCount
    FROM dbo.Quotes";
$statStmt = sqlsrv_query($conn, $statSql);
if ($statStmt && $r = sqlsrv_fetch_array($statStmt, SQLSRV_FETCH_ASSOC)) {
    $totalQuotes     = (int)$r['TotalCount'];
    $pendingQuotes   = (int)$r['PendingCount'];
    $approvedQuotes  = (int)$r['ApprovedCount'];
    $convertedOrders = (int)$r['ConvertedCount'];
}

/*
|--------------------------------------------------------------------------
| FETCH QUOTES LIST
|--------------------------------------------------------------------------
*/
$sql = "SELECT 
            q.QuoteId,
            q.QuoteNumber,
            q.UserId,
            ISNULL(q.CompanyName, 'Individual Client') AS CompanyName,
            q.ContactPerson,
            q.TotalQuotedAmount,
            ISNULL(q.Status, 'Pending') AS Status,
            q.ExpiryDate,
            q.ConvertedOrderId,
            q.CreatedAt,
            u.Email AS UserEmail,
            u.Phone AS UserPhone
        FROM dbo.Quotes q
        LEFT JOIN dbo.Users u ON q.UserId = u.UserId
        ORDER BY q.QuoteId DESC";

$stmt = sqlsrv_query($conn, $sql);
$quotesList = [];
$queryError = '';

if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $quotesList[] = $row;
    }
    sqlsrv_free_stmt($stmt);
} else {
    $queryError = 'Unable to fetch quotations from database.';
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<style>
:root {
    --bg-page: #0a1119;
    --bg-card: #111b26;
    --bg-card-alt: #0f1823;
    --bg-header: #0d1620;
    --bg-input: #0d1620;
    --border: #1e2d3d;
    --border-soft: #182636;
    --text-hi: #f0f4f8;
    --text-body: #a8b8c8;
    --text-mute: #5f7488;
    --green: #10b981;
    --green-soft: rgba(16, 185, 129, .12);
    --blue: #38bdf8;
    --blue-soft: rgba(56, 189, 248, .15);
    --amber: #f59e0b;
    --amber-soft: rgba(245, 158, 11, .12);
    --red: #ef4444;
    --red-soft: rgba(239, 68, 68, .12);
}

html, body, .main, .content { background: var(--bg-page) !important; color: var(--text-body) !important; }
.quotes-page { width: 100%; max-width: 1600px; margin: 0 auto; padding-bottom: 50px; }

.page-header {
    display: flex; justify-content: space-between; align-items: flex-end;
    margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid var(--border);
    flex-wrap: wrap; gap: 15px;
}
.page-title { margin: 0; font-size: 26px; font-weight: 800; color: var(--text-hi); }
.page-sub { font-size: 12px; color: var(--text-mute); margin-top: 4px; display: block; }

.btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 7px;
    height: 38px; padding: 0 15px; border-radius: 8px; border: 1px solid var(--border);
    background: var(--bg-input); color: var(--text-body) !important; font-size: 11px;
    font-weight: 800; text-decoration: none; cursor: pointer; transition: .18s;
}
.btn:hover { border-color: var(--green); background: var(--green-soft); color: var(--green) !important; }
.btn-primary { border-color: transparent; background: linear-gradient(135deg, #059669, #10b981); color: #fff !important; }
.btn-blue { color: var(--blue) !important; }

/* METRICS */
.metrics-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin-bottom: 24px; }
.metric-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; }
.metric-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
.metric-val { font-size: 22px; font-weight: 800; color: var(--text-hi); line-height: 1.1; }
.metric-label { font-size: 11px; font-weight: 700; color: var(--text-mute); text-transform: uppercase; margin-top: 2px; }

/* CONTENT & TABLE */
.content-box { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
.content-box-header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
.search-input { height: 36px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-input); color: var(--text-hi); padding: 0 14px; font-size: 12px; width: 260px; outline: none; }
.search-input:focus { border-color: var(--green); }

.data-table { width: 100%; min-width: 1100px; border-collapse: collapse; }
.data-table th { background: var(--bg-header); padding: 12px 16px; color: var(--text-mute); font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid var(--border); text-align: left; }
.data-table td { padding: 14px 16px; border-bottom: 1px solid var(--border-soft); color: var(--text-body); font-size: 12.5px; vertical-align: middle; }
.data-table tr:hover td { background: rgba(255,255,255,0.015); }

.badge { padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 800; text-transform: uppercase; display: inline-block; }
.badge-pending { background: var(--amber-soft); color: var(--amber); }
.badge-approved { background: var(--green-soft); color: var(--green); }
.badge-rejected { background: var(--red-soft); color: var(--red); }
.badge-converted { background: var(--blue-soft); color: var(--blue); }

.key-badge {
    font-size: 9.5px; font-family: monospace; font-weight: 800; padding: 2px 6px;
    border-radius: 4px; background: #1e2d3d; border: 1px solid rgba(255,255,255,0.15);
    color: #cbd5e1; margin-left: 4px;
}

.modal-overlay {
    display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75);
    backdrop-filter: blur(4px); z-index: 99999; align-items: center; justify-content: center;
}
.modal-overlay.active { display: flex; }
.modal-box { background: var(--bg-card); border: 1px solid var(--border); width: 100%; max-width: 440px; border-radius: 14px; padding: 22px; }
.form-control { width: 100%; height: 38px; background: var(--bg-card-alt); border: 1px solid var(--border); border-radius: 8px; color: var(--text-hi); padding: 0 12px; margin-bottom: 14px; box-sizing: border-box; }
</style>

<main class="main">
    <section class="content">
        <div class="quotes-page">

            <!-- HEADER -->
            <div class="page-header">
                <div>
                    <h1 class="page-title">Price Quotations</h1>
                    <span class="page-sub">Manage requested price quotations, bulk estimates, expiry dates, and order conversions.</span>
                </div>
                <div style="display:flex; gap:10px;">
                    <button type="button" class="btn btn-blue" onclick="window.print();">🖨 Print List <span class="key-badge">P</span></button>
                    <a href="../wholesale/index.php" class="btn">← Back to Wholesale <span class="key-badge">W</span></a>
                </div>
            </div>

            <?php if ($actionMessage !== ''): ?>
                <div style="padding:12px 16px; background:var(--green-soft); border:1px solid rgba(16,185,129,.3); border-radius:8px; color:#6ee7b7; margin-bottom:20px; font-size:12px; font-weight:600;">
                    ✓ <?= e($actionMessage) ?>
                </div>
            <?php endif; ?>

            <?php if ($actionError !== '' || $queryError !== ''): ?>
                <div style="padding:12px 16px; background:var(--red-soft); border:1px solid rgba(239,68,68,.3); border-radius:8px; color:#fca5a5; margin-bottom:20px; font-size:12px; font-weight:600;">
                    ! <?= e($actionError ?: $queryError) ?>
                </div>
            <?php endif; ?>

            <!-- STATS -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-icon" style="background:var(--blue-soft); color:var(--blue);">📑</div>
                    <div>
                        <div class="metric-val"><?= $totalQuotes ?></div>
                        <div class="metric-label">Total Quotes</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon" style="background:var(--amber-soft); color:var(--amber);">⏳</div>
                    <div>
                        <div class="metric-val"><?= $pendingQuotes ?></div>
                        <div class="metric-label">Pending Response</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon" style="background:var(--green-soft); color:var(--green);">✓</div>
                    <div>
                        <div class="metric-val"><?= $approvedQuotes ?></div>
                        <div class="metric-label">Accepted Quotes</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon" style="background:rgba(139,92,246,.12); color:#a78bfa;">📦</div>
                    <div>
                        <div class="metric-val"><?= $convertedOrders ?></div>
                        <div class="metric-label">Converted to Orders</div>
                    </div>
                </div>
            </div>

            <!-- TABLE BOX -->
            <div class="content-box">
                <div class="content-box-header">
                    <div>
                        <h2 style="margin:0; font-size:14px; font-weight:800; color:var(--text-hi); text-transform:uppercase;">Quotation Requests Roster</h2>
                    </div>
                    <div>
                        <input type="text" id="quoteSearch" class="search-input" placeholder="Search quote #, company, contact...">
                    </div>
                </div>

                <div style="overflow-x:auto;">
                    <table class="data-table" id="quoteTable">
                        <thead>
                            <tr>
                                <th>Quote #</th>
                                <th>Company / Contact</th>
                                <th>Quoted Amount</th>
                                <th>Status</th>
                                <th>Expiry Date</th>
                                <th>Created</th>
                                <th>Converted Order</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($quotesList)): ?>
                                <tr>
                                    <td colspan="8" style="text-align:center; padding:50px; color:var(--text-mute);">
                                        No price quotations found in the database.
                                    </td>
                                </tr>
                            <?php else: foreach ($quotesList as $q): 
                                $st = strtolower($q['Status']);
                                $bClass = 'badge-pending';
                                if (in_array($st, ['approved', 'accepted'])) $bClass = 'badge-approved';
                                if ($st === 'rejected') $bClass = 'badge-rejected';
                                if (!empty($q['ConvertedOrderId'])) $bClass = 'badge-converted';
                            ?>
                                <tr class="quote-row">
                                    <td>
                                        <strong style="color:var(--blue); font-family:monospace; font-size:13px;">
                                            <?= e($q['QuoteNumber'] ?: 'QT-' . str_pad($q['QuoteId'], 5, '0', STR_PAD_LEFT)) ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <strong style="color:var(--text-hi); font-size:13px;"><?= e($q['CompanyName']) ?></strong>
                                        <div style="font-size:11.5px; color:var(--green); margin-top:2px;"><?= e($q['ContactPerson'] ?: '—') ?></div>
                                        <div style="font-size:10.5px; color:var(--text-mute);"><?= e($q['UserEmail'] ?? '') ?></div>
                                    </td>
                                    <td>
                                        <strong style="color:var(--text-hi); font-size:14px;">$<?= number_format((float)$q['TotalQuotedAmount'], 2) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge <?= $bClass ?>"><?= e($q['Status']) ?></span>
                                    </td>
                                    <td style="color:var(--text-mute); font-size:12px;">
                                        <?= formatDate($q['ExpiryDate']) ?>
                                    </td>
                                    <td style="color:var(--text-mute); font-size:12px;">
                                        <?= formatDate($q['CreatedAt']) ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($q['ConvertedOrderId'])): ?>
                                            <a href="../orders/view.php?id=<?= $q['ConvertedOrderId'] ?>" style="color:var(--green); font-weight:700; text-decoration:none;">
                                                Order #<?= e($q['ConvertedOrderId']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span style="color:var(--text-mute); font-size:11px;">Not Converted</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <button type="button" class="btn" style="height:32px; padding:0 10px;" onclick="openStatusModal(<?= $q['QuoteId'] ?>, '<?= e($q['Status']) ?>')">
                                            ⚙️ Update
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </section>
</main>

<!-- UPDATE STATUS MODAL -->
<div class="modal-overlay" id="statusModal">
    <div class="modal-box">
        <h3 style="margin:0 0 14px; font-size:15px; color:var(--text-hi);">Update Quote Status</h3>
        <form method="POST">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="quote_id" id="modalQuoteId" value="0">

            <label style="font-size:11px; font-weight:700; color:var(--text-mute); display:block; margin-bottom:4px;">CHANGE QUOTE STATUS:</label>
            <select name="status" id="modalQuoteStatus" class="form-control">
                <option value="Pending">Pending (Draft / Under Review)</option>
                <option value="Sent">Sent to Customer</option>
                <option value="Approved">Approved / Accepted</option>
                <option value="Rejected">Rejected / Cancelled</option>
            </select>

            <div style="display:flex; gap:10px; margin-top:10px;">
                <button type="submit" class="btn btn-primary" style="flex:1; height:38px;">Save Status</button>
                <button type="button" class="btn" style="height:38px;" onclick="closeStatusModal();">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openStatusModal(quoteId, currentStatus) {
    document.getElementById('modalQuoteId').value = quoteId;
    document.getElementById('modalQuoteStatus').value = currentStatus;
    document.getElementById('statusModal').classList.add('active');
}

function closeStatusModal() {
    document.getElementById('statusModal').classList.remove('active');
}

// Live Search Filter
document.getElementById('quoteSearch')?.addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('#quoteTable tbody .quote-row').forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = (!q || text.includes(q)) ? '' : 'none';
    });
});

// Shortcuts
window.addEventListener('keydown', function(e) {
    if (['INPUT', 'SELECT', 'TEXTAREA'].includes(document.activeElement.tagName)) return;
    const k = e.key.toUpperCase();
    if (k === 'P') { e.preventDefault(); window.print(); }
    if (k === 'W') { e.preventDefault(); window.location.href = '../wholesale/index.php'; }
    if (e.key === 'Escape') { closeStatusModal(); }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>