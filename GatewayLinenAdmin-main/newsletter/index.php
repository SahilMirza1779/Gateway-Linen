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
| DATABASE
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| PAGE SETTINGS
|--------------------------------------------------------------------------
*/
$activeMenu = 'newsletter';
$pageTitle  = 'GatewayLinen | Newsletter Subscribers';

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] = $_SESSION['admin_username'] ?? 'GatewayLinen Administrator';
}

if (!isset($_SESSION['admin_role'])) {
    $_SESSION['admin_role'] = 'Administrator';
}

/*
|--------------------------------------------------------------------------
| MESSAGES
|--------------------------------------------------------------------------
*/
$actionMessage = trim((string)($_GET['success'] ?? ''));
$actionError   = trim((string)($_GET['error'] ?? ''));

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function dateValue($value, $format = 'd/m/Y, h:i A'): string
{
    if ($value instanceof DateTimeInterface) {
        return $value->format($format);
    }
    if (!empty($value)) {
        try {
            $dt = new DateTime((string)$value);
            return $dt->format($format);
        } catch (Exception $ex) {
            return trim((string)$value);
        }
    }
    return '—';
}

/*
|--------------------------------------------------------------------------
| FETCH SUBSCRIBERS FROM DATABASE
|--------------------------------------------------------------------------
*/
$subscribersList = [];
$queryError = '';

$sql = "
    SELECT 
        SubscriberId,
        Email,
        IsActive,
        CreatedAt
    FROM dbo.NewsletterSubscribers
    ORDER BY SubscriberId DESC
";

$stmt = sqlsrv_query($conn, $sql);
if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $subscribersList[] = $row;
    }
    sqlsrv_free_stmt($stmt);
} else {
    $errors = sqlsrv_errors();
    $queryError = 'Unable to load newsletter subscribers from database: ' . ($errors[0]['message'] ?? 'Unknown error');
}

/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/
$totalSubscribers  = count($subscribersList);
$activeSubscribers = 0;
$unsubscribedCount = 0;

foreach ($subscribersList as $sub) {
    if (!empty($sub['IsActive'])) {
        $activeSubscribers++;
    } else {
        $unsubscribedCount++;
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
        --radius: 10px;
    }

    html, body, .main, .content {
        background: var(--bg-page) !important;
        color: var(--text-body) !important;
    }

    .subscribers-page {
        width: 100%;
        max-width: 1600px;
        margin: 0 auto;
        padding: 0;
    }

    .subscribers-page * { box-sizing: border-box; }

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

    .page-header h1 {
        margin: 0;
        color: var(--text-hi);
        font-size: 26px;
        font-weight: 800;
    }

    .page-header p {
        margin: 6px 0 0;
        color: var(--text-mute);
        font-size: 12px;
    }

    .header-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
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
    .subscriber-stats {
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
    .notice {
        margin-bottom: 14px;
        padding: 12px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
    }
    .notice-success {
        border: 1px solid rgba(16, 185, 129, .3);
        background: var(--green-soft);
        color: #6ee7b7;
    }
    .notice-error {
        border: 1px solid rgba(239, 68, 68, .3);
        background: var(--red-soft);
        color: #fca5a5;
    }

    /* CONTENT BOX */
    .subscriber-content {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
    }

    .content-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 17px 20px;
        border-bottom: 1px solid var(--border);
    }

    .content-title h2 {
        margin: 0;
        color: var(--text-hi);
        font-size: 16px;
    }

    .content-title p {
        margin: 4px 0 0;
        color: var(--text-mute);
        font-size: 11px;
    }

    .subscriber-filters {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .search-wrap {
        position: relative;
        width: 270px;
    }

    .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-mute);
        pointer-events: none;
    }

    .search-input, .select-filter {
        height: 36px;
        border: 1px solid var(--border);
        border-radius: 8px;
        outline: none;
        background: var(--bg-input);
        color: var(--text-hi);
        font-size: 12px;
    }

    .search-input { width: 100%; padding: 0 12px 0 34px; }
    .select-filter { min-width: 140px; padding: 0 10px; }

    .search-input:focus, .select-filter:focus {
        border-color: var(--green);
        box-shadow: 0 0 0 3px rgba(16, 185, 129, .1);
    }

    .table-summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 11px 20px;
        border-bottom: 1px solid var(--border);
        font-size: 11px;
        color: var(--text-mute);
    }
    .table-summary strong { color: var(--text-hi); }

    /* TABLE */
    .table-wrapper {
        width: 100%;
        overflow-x: auto;
    }

    .sub-table {
        width: 100%;
        min-width: 900px;
        border-collapse: collapse;
    }

    .sub-table th {
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

    .sub-table td {
        padding: 12px 16px;
        border-bottom: 1px solid var(--border-soft);
        color: var(--text-body);
        font-size: 12px;
        vertical-align: middle;
    }

    .sub-table tbody tr:hover { background: var(--bg-hover); }

    .order-box {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 44px;
        height: 28px;
        padding: 0 8px;
        border-radius: 7px;
        background: var(--bg-input);
        border: 1px solid var(--border);
        color: var(--text-mute);
        font-size: 11px;
        font-weight: 800;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-height: 24px;
        padding: 0 9px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 700;
    }

    .pill-green { background: var(--green-soft); color: var(--green); }
    .pill-red { background: var(--red-soft); color: #f87171; }

    @media (max-width: 900px) {
        .subscriber-stats { grid-template-columns: 1fr; }
        .content-header { flex-direction: column; align-items: stretch; }
        .search-wrap { width: 100%; }
    }
</style>

<main class="main">
    <section class="content">
        <div class="subscribers-page">

            <!-- PAGE HEADER -->
            <div class="page-header">
                <div>
                    <div class="breadcrumb">
                        <span>Marketing</span>
                        <span>/</span>
                        <span class="current">Newsletter</span>
                    </div>
                    <h1>Newsletter Subscribers</h1>
                    <p>Manage newsletter subscribers, subscription status and email contacts.</p>
                </div>

                <div class="header-actions">
                    <button type="button" class="btn btn-blue" id="printBtn">🖨 Print</button>
                    <button type="button" class="btn" id="excelBtn">↓ Excel</button>
                </div>
            </div>

            <!-- SUCCESS/ERROR MESSAGES -->
            <?php if ($actionMessage !== ''): ?>
                <div class="notice notice-success"><?= e($actionMessage) ?></div>
            <?php endif; ?>
            <?php if ($actionError !== ''): ?>
                <div class="notice notice-error"><?= e($actionError) ?></div>
            <?php endif; ?>
            <?php if ($queryError !== ''): ?>
                <div class="notice notice-error"><?= e($queryError) ?></div>
            <?php endif; ?>

            <!-- STATS COUNTERS -->
            <div class="subscriber-stats">
                <div class="stat-item">
                    <div class="stat-icon">✉</div>
                    <div>
                        <div class="stat-label">Total Subscribers</div>
                        <div class="stat-value"><?= number_format($totalSubscribers) ?></div>
                    </div>
                </div>

                <div class="stat-item">
                    <div class="stat-icon" style="background:var(--green-soft); color:var(--green);">✓</div>
                    <div>
                        <div class="stat-label">Active Subscribers</div>
                        <div class="stat-value"><?= number_format($activeSubscribers) ?></div>
                    </div>
                </div>

                <div class="stat-item">
                    <div class="stat-icon" style="background:var(--red-soft); color:var(--red);">❚❚</div>
                    <div>
                        <div class="stat-label">Unsubscribed</div>
                        <div class="stat-value"><?= number_format($unsubscribedCount) ?></div>
                    </div>
                </div>
            </div>

            <!-- CONTENT BOX -->
            <div class="subscriber-content">
                <div class="content-header">
                    <div class="content-title">
                        <h2>Subscriber List</h2>
                        <p>View and manage all newsletter email subscribers.</p>
                    </div>

                    <div class="subscriber-filters">
                        <!-- REAL-TIME SEARCH -->
                        <div class="search-wrap">
                            <span class="search-icon">⌕</span>
                            <input
                                type="search"
                                id="subSearch"
                                class="search-input"
                                placeholder="Search email or subscriber ID..."
                                autocomplete="off"
                            >
                        </div>

                        <!-- STATUS FILTER -->
                        <select id="statusFilter" class="select-filter">
                            <option value="all">All Subscribers</option>
                            <option value="active">Active Only</option>
                            <option value="unsubscribed">Unsubscribed Only</option>
                        </select>
                    </div>
                </div>

                <!-- TABLE SUMMARY -->
                <div class="table-summary">
                    <div>Showing <strong id="visibleCount"><?= $totalSubscribers ?></strong> subscriber(s)</div>
                    <div>Total: <strong><?= $totalSubscribers ?></strong></div>
                </div>

                <!-- TABLE -->
                <div class="table-wrapper">
                    <?php if (empty($subscribersList)): ?>
                        <div style="padding:65px 20px; text-align:center; color:var(--text-mute);">
                            <div style="font-size:32px; margin-bottom:10px;">✉</div>
                            <h3 style="color:var(--text-hi); margin:0 0 6px 0;">No Newsletter Subscribers Found</h3>
                            <p style="font-size:12px; margin:0;">Subscribers will appear here when users sign up on your website.</p>
                        </div>
                    <?php else: ?>
                        <table class="sub-table" id="subTable">
                            <thead>
                                <tr>
                                    <th># ID</th>
                                    <th>Email Address</th>
                                    <th>Status</th>
                                    <th>Subscribed Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                foreach ($subscribersList as $sub): 
                                    $subId      = (int)($sub['SubscriberId'] ?? 0);
                                    $email      = (string)($sub['Email'] ?? '');
                                    $isActive   = !empty($sub['IsActive']);
                                    $createdAt  = dateValue($sub['CreatedAt'] ?? null);

                                    $statusClass = $isActive ? 'pill-green' : 'pill-red';
                                    $statusText  = $isActive ? 'Active' : 'Unsubscribed';
                                    $filterVal   = $isActive ? 'active' : 'unsubscribed';
                                ?>
                                    <tr 
                                        class="sub-row" 
                                        data-email="<?= e(strtolower($email)) ?>"
                                        data-id="<?= $subId ?>"
                                        data-status="<?= $filterVal ?>"
                                    >
                                        <td><span class="order-box">#<?= $subId ?></span></td>
                                        <td><strong style="color:var(--text-hi); font-size:13px;"><?= e($email) ?></strong></td>
                                        <td>
                                            <span class="status-pill <?= $statusClass ?>">
                                                <span>●</span> <?= $statusText ?>
                                            </span>
                                        </td>
                                        <td><span style="font-size:11px; color:var(--text-mute);"><?= $createdAt ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div id="noResult" style="display:none; padding:55px 20px; text-align:center; color:var(--text-mute);">
                            <div style="font-size:26px; margin-bottom:8px;">⌕</div>
                            <h3 style="color:var(--text-hi); margin:0;">No matching subscribers</h3>
                            <p style="font-size:12px; margin-top:5px;">Try adjusting your search terms or status filter.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </section>
</main>

<!-- LIBRARIES FOR EXCEL EXPORT -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        const searchInput  = document.getElementById('subSearch');
        const statusFilter = document.getElementById('statusFilter');
        const table        = document.getElementById('subTable');
        const countElement = document.getElementById('visibleCount');
        const noResult     = document.getElementById('noResult');

        function rows() {
            return table ? Array.from(table.querySelectorAll('tbody .sub-row')) : [];
        }

        function visibleRows() {
            return rows().filter(r => r.style.display !== 'none');
        }

        function filterSubscribers() {
            if (!table) return;

            const q  = (searchInput?.value || '').toLowerCase().trim();
            const st = (statusFilter?.value || 'all');

            let count = 0;

            rows().forEach(function(row) {
                const email = row.dataset.email || '';
                const id    = row.dataset.id || '';
                const rSt   = row.dataset.status || '';

                const qMatch  = (!q || email.includes(q) || id.includes(q));
                const stMatch = (st === 'all' || rSt === st);

                if (qMatch && stMatch) {
                    row.style.display = '';
                    count++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (countElement) countElement.textContent = count;
            if (noResult) noResult.style.display = (count === 0) ? 'block' : 'none';
        }

        function excelExport() {
            const data = visibleRows().map(function(row) {
                return {
                    'Subscriber ID': row.dataset.id || '',
                    'Email Address': row.dataset.email || '',
                    'Status': row.querySelector('.status-pill')?.innerText.trim() || '',
                    'Subscribed Date': row.cells[3]?.innerText.trim() || ''
                };
            });

            if (window.XLSX) {
                const ws = XLSX.utils.json_to_sheet(data);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Subscribers');
                XLSX.writeFile(wb, 'newsletter-subscribers-' + new Date().toISOString().slice(0, 10) + '.xlsx');
            }
        }

        document.getElementById('printBtn')?.addEventListener('click', () => window.print());
        document.getElementById('excelBtn')?.addEventListener('click', excelExport);

        searchInput?.addEventListener('input', filterSubscribers);
        statusFilter?.addEventListener('change', filterSubscribers);

        filterSubscribers();
    });
})();
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>