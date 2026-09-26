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
| HANDLE STATUS UPDATES
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['quote_id'])) {
    $quoteId = (int)$_POST['quote_id'];
    $actionType = trim($_POST['action']);

    if ($quoteId > 0) {
        $allowedStatuses = ['Pending', 'Sent', 'Approved', 'Rejected'];
        $newStatus = '';

        if ($actionType === 'update_status') {
            $newStatus = trim($_POST['status'] ?? 'Pending');
        } elseif (in_array($actionType, $allowedStatuses, true)) {
            $newStatus = $actionType;
        }

        if ($newStatus !== '') {
            $upSql = "UPDATE dbo.Quotes SET Status = ? WHERE QuoteId = ?";
            $upStmt = sqlsrv_query($conn, $upSql, [$newStatus, $quoteId]);
            if ($upStmt !== false) {
                header('Location: index.php?success=' . urlencode("Quote #$quoteId status updated to $newStatus successfully."));
                exit;
            } else {
                $actionError = "Failed to update quote status.";
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| FILTERS & PAGINATION SETUP
|--------------------------------------------------------------------------
*/
$statusFilter = trim($_GET['status_filter'] ?? '');
$fromDate     = trim($_GET['from_date'] ?? '');
$toDate       = trim($_GET['to_date'] ?? '');
$searchQuery  = trim($_GET['q'] ?? '');

$page         = max(1, (int)($_GET['page'] ?? 1));
$limit        = 10;
$offset       = ($page - 1) * $limit;

// Base Where Clause for Filtering
$whereSql = " WHERE 1=1";
$params = [];

if ($statusFilter !== '') {
    if ($statusFilter === 'Approved') {
        $whereSql .= " AND q.Status IN ('Approved', 'Accepted')";
    } else {
        $whereSql .= " AND q.Status = ?";
        $params[] = $statusFilter;
    }
}

if ($fromDate !== '') {
    $whereSql .= " AND q.CreatedAt >= ?";
    $params[] = $fromDate . ' 00:00:00';
}

if ($toDate !== '') {
    $whereSql .= " AND q.CreatedAt <= ?";
    $params[] = $toDate . ' 23:59:59';
}

if ($searchQuery !== '') {
    $whereSql .= " AND (q.QuoteNumber LIKE ? OR q.CompanyName LIKE ? OR q.ContactPerson LIKE ?)";
    $like = '%' . $searchQuery . '%';
    array_push($params, $like, $like, $like);
}

/*
|--------------------------------------------------------------------------
| EXPORT TO CSV FEATURE
|--------------------------------------------------------------------------
*/
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=quotations_export_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Quote #', 'Company Name', 'Contact Person', 'Email', 'Quoted Amount', 'Status', 'Expiry Date', 'Created At']);

    $exportSql = "SELECT q.QuoteNumber, q.QuoteId, q.CompanyName, q.ContactPerson, u.Email, q.TotalQuotedAmount, q.Status, q.ExpiryDate, q.CreatedAt 
                  FROM dbo.Quotes q LEFT JOIN dbo.Users u ON q.UserId = u.UserId" . $whereSql . " ORDER BY q.QuoteId DESC";
    $expStmt = sqlsrv_query($conn, $exportSql, $params);
    while ($row = sqlsrv_fetch_array($expStmt, SQLSRV_FETCH_ASSOC)) {
        fputcsv($output, [
            $row['QuoteNumber'] ?: 'QT-' . str_pad($row['QuoteId'], 5, '0', STR_PAD_LEFT),
            $row['CompanyName'],
            $row['ContactPerson'],
            $row['Email'],
            $row['TotalQuotedAmount'],
            $row['Status'],
            $row['ExpiryDate'] instanceof DateTime ? $row['ExpiryDate']->format('Y-m-d H:i') : $row['ExpiryDate'],
            $row['CreatedAt'] instanceof DateTime ? $row['CreatedAt']->format('Y-m-d H:i') : $row['CreatedAt']
        ]);
    }
    fclose($output);
    exit;
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
| FETCH PAGINATED QUOTES LIST WITH FILTERS
|--------------------------------------------------------------------------
*/
// Count Total for Pagination
$countSql = "SELECT COUNT(*) AS Total FROM dbo.Quotes q" . $whereSql;
$countStmt = sqlsrv_query($conn, $countSql, $params);
$totalRows = 0;
if ($countStmt && $cRow = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC)) {
    $totalRows = (int)$cRow['Total'];
}
$totalPages = max(1, ceil($totalRows / $limit));

// Fetch Data with Offset for MSSQL
$sql = "SELECT * FROM (
            SELECT ROW_NUMBER() OVER (ORDER BY q.QuoteId DESC) AS RowNum,
                q.QuoteId, q.QuoteNumber, q.UserId, ISNULL(q.CompanyName, 'Individual Client') AS CompanyName,
                q.ContactPerson, q.TotalQuotedAmount, ISNULL(q.Status, 'Pending') AS Status,
                q.ExpiryDate, q.ConvertedOrderId, q.CreatedAt, u.Email AS UserEmail, u.Phone AS UserPhone
            FROM dbo.Quotes q
            LEFT JOIN dbo.Users u ON q.UserId = u.UserId" . $whereSql . "
        ) ASed 
        WHERE RowNum BETWEEN ? AND ?";

$paginationParams = $params;
$paginationParams[] = $offset + 1;
$paginationParams[] = $offset + $limit;

$stmt = sqlsrv_query($conn, $sql, $paginationParams);
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

html, body, .main, .content { background: var(--bg-page) !important; color: var(--text-body) !important; font-family: inherit; }
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
    height: 36px; padding: 0 14px; border-radius: 8px; border: 1px solid var(--border);
    background: var(--bg-input); color: var(--text-body) !important; font-size: 11px;
    font-weight: 700; text-decoration: none; cursor: pointer; transition: .18s;
}
.btn:hover { border-color: var(--green); background: var(--green-soft); color: var(--green) !important; }
.btn-primary { border-color: transparent; background: linear-gradient(135deg, #059669, #10b981); color: #fff !important; }
.btn-blue { color: var(--blue) !important; }
.btn-blue:hover { border-color: var(--blue); background: var(--blue-soft); }

/* METRICS */
.metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 24px; }
.metric-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; }
.metric-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
.metric-val { font-size: 22px; font-weight: 800; color: var(--text-hi); line-height: 1.1; }
.metric-label { font-size: 11px; font-weight: 700; color: var(--text-mute); text-transform: uppercase; margin-top: 2px; }

/* CONTENT & TABLE */
.content-box { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; box-shadow: 0 15px 40px rgba(0,0,0,.2); }
.content-box-header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
.search-input, .form-select { height: 36px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-input); color: var(--text-hi); padding: 0 12px; font-size: 12px; outline: none; }
.search-input:focus, .form-select:focus { border-color: var(--green); }

.data-table { width: 100%; min-width: 1100px; border-collapse: collapse; }
.data-table th { background: var(--bg-header); padding: 12px 16px; color: var(--text-mute); font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid var(--border); text-align: left; }
.data-table td { padding: 14px 16px; border-bottom: 1px solid var(--border-soft); color: var(--text-body); font-size: 12.5px; vertical-align: middle; }
.data-table tr:hover td { background: rgba(255,255,255,0.015); }

.badge { padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 800; text-transform: uppercase; display: inline-block; }
.badge-pending { background: var(--amber-soft); color: var(--amber); border: 1px solid rgba(245,158,11,.2); }
.badge-approved { background: var(--green-soft); color: var(--green); border: 1px solid rgba(16,185,129,.2); }
.badge-rejected { background: var(--red-soft); color: var(--red); border: 1px solid rgba(239,68,68,.2); }
.badge-converted { background: var(--blue-soft); color: var(--blue); border: 1px solid rgba(56,189,248,.2); }

.action-links { display: flex; gap: 6px; justify-content: flex-end; align-items: center; }
.btn-action { padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; border: 1px solid var(--border); background: var(--bg-input); color: var(--text-body); cursor: pointer; text-decoration: none; display: inline-block; transition: .15s ease; }
.btn-action:hover { border-color: var(--blue); color: var(--blue); background: var(--blue-soft); }
.btn-approve:hover { background: var(--green-soft); border-color: var(--green); color: var(--green); }
.btn-reject:hover { background: var(--red-soft); border-color: var(--red); color: var(--red); }

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
.modal-box { background: var(--bg-card); border: 1px solid var(--border); width: 100%; max-width: 440px; border-radius: 14px; padding: 22px; box-shadow: 0 20px 40px rgba(0,0,0,.5); }
.form-control { width: 100%; height: 38px; background: var(--bg-card-alt); border: 1px solid var(--border); border-radius: 8px; color: var(--text-hi); padding: 0 12px; margin-bottom: 14px; box-sizing: border-box; outline: none; }
.form-control:focus { border-color: var(--green); }
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
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn btn-blue">📥 Export CSV</a>
                    <button type="button" class="btn btn-blue" onclick="window.print();">🖨 Print <span class="key-badge">P</span></button>
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

            <!-- ADVANCED FILTER BAR -->
            <div class="content-box" style="margin-bottom: 20px; padding: 16px 20px;">
                <form method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                    <input type="text" name="q" class="search-input" placeholder="Search quote #, company..." value="<?= e($searchQuery) ?>" style="flex: 1; min-width: 200px;">
                    
                    <select name="status_filter" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Sent" <?= $statusFilter === 'Sent' ? 'selected' : '' ?>>Sent</option>
                        <option value="Approved" <?= $statusFilter === 'Approved' ? 'selected' : '' ?>>Approved / Accepted</option>
                        <option value="Rejected" <?= $statusFilter === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>

                    <input type="date" name="from_date" class="search-input" value="<?= e($fromDate) ?>" title="From Date">
                    <input type="date" name="to_date" class="search-input" value="<?= e($toDate) ?>" title="To Date">

                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="index.php" class="btn">Reset</a>
                </form>
            </div>

            <!-- TABLE BOX -->
            <div class="content-box">
                <div class="content-box-header">
                    <div>
                        <h2 style="margin:0; font-size:14px; font-weight:800; color:var(--text-hi); text-transform:uppercase;">Quotation Requests Roster (<?= $totalRows ?> found)</h2>
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
                                        No price quotations found matching your criteria.
                                    </td>
                                </tr>
                            <?php else: foreach ($quotesList as $q): 
                                $st = strtolower($q['Status'] ?? 'pending');
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
                                        <strong style="color:var(--text-hi); font-size:14px;">$<?= number_format((float)($q['TotalQuotedAmount'] ?? 0), 2) ?></strong>
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
                                            <a href="../orders/view.php?id=<?= (int)$q['ConvertedOrderId'] ?>" style="color:var(--green); font-weight:700; text-decoration:none;">
                                                Order #<?= e($q['ConvertedOrderId']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span style="color:var(--text-mute); font-size:11px;">Not Converted</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-links">
                                            <a href="view.php?id=<?= (int)$q['QuoteId'] ?>" class="btn-action">View</a>

                                            <?php if ($st === 'pending'): ?>
                                                <form method="POST" style="margin:0;">
                                                    <input type="hidden" name="quote_id" value="<?= (int)$q['QuoteId'] ?>">
                                                    <input type="hidden" name="action" value="Approved">
                                                    <button type="submit" class="btn-action btn-approve" onclick="return confirm('Approve quote #<?= (int)$q['QuoteId'] ?>?');">Approve</button>
                                                </form>
                                                <form method="POST" style="margin:0;">
                                                    <input type="hidden" name="quote_id" value="<?= (int)$q['QuoteId'] ?>">
                                                    <input type="hidden" name="action" value="Rejected">
                                                    <button type="submit" class="btn-action btn-reject" onclick="return confirm('Reject quote #<?= (int)$q['QuoteId'] ?>?');">Reject</button>
                                                </form>
                                            <?php endif; ?>

                                            <button type="button" class="btn-action" onclick="openStatusModal(<?= (int)$q['QuoteId'] ?>, '<?= e($q['Status']) ?>')">
                                                ⚙️ Status
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION FOOTER -->
                <?php if ($totalPages > 1): ?>
                    <div style="padding: 16px 20px; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <span style="font-size: 12px; color: var(--text-mute);">Page <?= $page ?> of <?= $totalPages ?></span>
                        <div style="display: flex; gap: 5px;">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="btn-action" style="<?= $i === $page ? 'background: var(--green-soft); border-color: var(--green); color: var(--green);' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endif; ?>

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

            <label style="font-size:11.5px; font-weight:700; color:var(--text-mute); display:block; margin-bottom:6px;">CHANGE QUOTE STATUS:</label>
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

// Keyboard Shortcuts
window.addEventListener('keydown', function(e) {
    if (['INPUT', 'SELECT', 'TEXTAREA'].includes(document.activeElement.tagName)) return;
    const k = e.key.toUpperCase();
    if (k === 'P') { e.preventDefault(); window.print(); }
    if (k === 'W') { e.preventDefault(); window.location.href = '../wholesale/index.php'; }
    if (e.key === 'Escape') { closeStatusModal(); }
});
</script>

<?php require_cache: ?><?php require_once __DIR__ . '/../includes/footer.php'; ?>