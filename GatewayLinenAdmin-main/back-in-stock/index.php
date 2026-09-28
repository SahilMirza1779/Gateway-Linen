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
$activeMenu = 'back-in-stock';
$pageTitle  = 'GatewayLinen | Back in Stock Alerts';

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
| FETCH BACK IN STOCK ALERTS FROM DATABASE
|--------------------------------------------------------------------------
*/
$alertsList = [];
$queryError = '';

$sql = "
    SELECT 
        a.AlertId,
        a.CustomerEmail,
        a.IsSent,
        a.CreatedAt,
        p.Name AS ProductName,
        pv.SKU AS Sku
    FROM dbo.BackInStockAlerts a
    LEFT JOIN dbo.ProductVariants pv ON a.VariantId = pv.VariantId
    LEFT JOIN dbo.Products p ON pv.ProductId = p.ProductId
    ORDER BY a.AlertId DESC
";

$stmt = sqlsrv_query($conn, $sql);
if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $alertsList[] = $row;
    }
    sqlsrv_free_stmt($stmt);
} else {
    $errors = sqlsrv_errors();
    $queryError = 'Unable to load back in stock alerts from database: ' . ($errors[0]['message'] ?? 'Unknown error');
}

/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/
$totalAlerts  = count($alertsList);
$sentCount    = 0;
$pendingCount = 0;

foreach ($alertsList as $alert) {
    if (!empty($alert['IsSent'])) {
        $sentCount++;
    } else {
        $pendingCount++;
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
        --amber: #f59e0b;
        --amber-soft: rgba(245, 158, 11, .15);
        --radius: 10px;
    }

    html, body, .main, .content {
        background: var(--bg-page) !important;
        color: var(--text-body) !important;
    }

    .alerts-page {
        width: 100%;
        max-width: 1600px;
        margin: 0 auto;
        padding: 0;
    }

    .alerts-page * { box-sizing: border-box; }

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

    .btn-blue { color: var(--blue) !important; }

    /* STATS */
    .alert-stats {
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
    .alert-content {
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

    .alert-filters {
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

    .export-bar {
        display: flex;
        align-items: center;
        gap: 8px;
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

    .alert-table {
        width: 100%;
        min-width: 950px;
        border-collapse: collapse;
    }

    .alert-table th {
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

    .alert-table td {
        padding: 12px 16px;
        border-bottom: 1px solid var(--border-soft);
        color: var(--text-body);
        font-size: 12px;
        vertical-align: middle;
    }

    .alert-table tbody tr:hover { background: var(--bg-hover); }

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
    .pill-amber { background: var(--amber-soft); color: var(--amber); }

    /* SHORTCUTS HELP BOX */
    .shortcut-help-box {
        margin-top: 16px;
        padding: 16px 20px;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: var(--bg-card);
    }
    .shortcut-help-box.hidden { display: none; }
    .shortcut-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
        margin-top: 10px;
    }
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
        border-radius: 5px;
        background: #0a1119;
        border: 1px solid var(--border);
        color: var(--green);
        font: 800 10px monospace;
    }

    @media (max-width: 900px) {
        .alert-stats { grid-template-columns: 1fr; }
        .content-header { flex-direction: column; align-items: stretch; }
        .search-wrap { width: 100%; }
        .shortcut-grid { grid-template-columns: repeat(2, 1fr); }
    }
</style>

<main class="main">
    <section class="content">
        <div class="alerts-page">

            <!-- PAGE HEADER -->
            <div class="page-header">
                <div>
                    <div class="breadcrumb">
                        <span>Inventory</span>
                        <span>/</span>
                        <span class="current">Back in Stock Alerts</span>
                    </div>
                    <h1>Back in Stock Requests</h1>
                    <p>Track customer notifications and requests for items currently out of stock, reports and keyboard shortcuts.</p>
                </div>

                <div class="header-actions">
                    <button type="button" class="btn btn-blue" id="printBtn">🖨 Print <small>P</small></button>
                    <button type="button" class="btn" id="pdfBtn">↓ PDF <small>V</small></button>
                    <button type="button" class="btn" id="excelBtn">↓ Excel <small>X</small></button>
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
            <div class="alert-stats">
                <div class="stat-item">
                    <div class="stat-icon">🔔</div>
                    <div>
                        <div class="stat-label">Total Requests</div>
                        <div class="stat-value"><?= number_format($totalAlerts) ?></div>
                    </div>
                </div>

                <div class="stat-item">
                    <div class="stat-icon" style="background:var(--amber-soft); color:var(--amber);">⏳</div>
                    <div>
                        <div class="stat-label">Pending Notifications</div>
                        <div class="stat-value"><?= number_format($pendingCount) ?></div>
                    </div>
                </div>

                <div class="stat-item">
                    <div class="stat-icon" style="background:var(--green-soft); color:var(--green);">✓</div>
                    <div>
                        <div class="stat-label">Sent / Notified</div>
                        <div class="stat-value"><?= number_format($sentCount) ?></div>
                    </div>
                </div>
            </div>

            <!-- CONTENT BOX -->
            <div class="alert-content">
                <div class="content-header">
                    <div class="content-title">
                        <h2>Alert Requests List</h2>
                        <p>View customers waiting for product restocks.</p>
                    </div>

                    <div class="alert-filters">
                        <!-- REAL-TIME SEARCH -->
                        <div class="search-wrap">
                            <span class="search-icon">⌕</span>
                            <input
                                type="search"
                                id="alertSearch"
                                class="search-input"
                                placeholder="Search email, product, SKU..."
                                autocomplete="off"
                            >
                        </div>

                        <!-- STATUS FILTER -->
                        <select id="statusFilter" class="select-filter">
                            <option value="all">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="sent">Sent</option>
                        </select>
                    </div>
                </div>

                <!-- EXPORT BAR -->
                <div class="export-bar" style="display:flex; align-items:center; gap:8px; padding:10px 20px; border-bottom:1px solid var(--border); background:var(--bg-card-alt);">
                    <span class="export-label" style="margin-right:auto; color:var(--text-mute); font-size:10px; font-weight:800; text-transform:uppercase;">Reports & Export</span>
                    <button type="button" class="btn" id="printBtn2">🖨 Print</button>
                    <button type="button" class="btn" id="pdfBtn2">↓ PDF</button>
                    <button type="button" class="btn" id="excelBtn2">↓ Excel</button>
                </div>

                <!-- TABLE SUMMARY -->
                <div class="table-summary">
                    <div>Showing <strong id="visibleCount"><?= $totalAlerts ?></strong> request(s)</div>
                    <div>Total: <strong><?= $totalAlerts ?></strong></div>
                </div>

                <!-- TABLE -->
                <div class="table-wrapper">
                    <?php if (empty($alertsList)): ?>
                        <div style="padding:65px 20px; text-align:center; color:var(--text-mute);">
                            <div style="font-size:32px; margin-bottom:10px;">🔔</div>
                            <h3 style="color:var(--text-hi); margin:0 0 6px 0;">No Back in Stock Alerts Found</h3>
                            <p style="font-size:12px; margin:0;">Customer notifications will appear here when requested.</p>
                        </div>
                    <?php else: ?>
                        <table class="alert-table" id="alertTable">
                            <thead>
                                <tr>
                                    <th># ID</th>
                                    <th>Customer Email</th>
                                    <th>Requested Product / SKU</th>
                                    <th>Status</th>
                                    <th>Requested Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                foreach ($alertsList as $alert): 
                                    $alertId   = (int)($alert['AlertId'] ?? 0);
                                    $email     = (string)($alert['CustomerEmail'] ?? '');
                                    $prodName  = (string)($alert['ProductName'] ?? 'General Product');
                                    $sku       = (string)($alert['Sku'] ?? 'N/A');
                                    $isSent    = !empty($alert['IsSent']);
                                    $createdAt = dateValue($alert['CreatedAt'] ?? null);

                                    $statusClass = $isSent ? 'pill-green' : 'pill-amber';
                                    $statusText  = $isSent ? 'Sent' : 'Pending';
                                    $filterVal   = $isSent ? 'sent' : 'pending';
                                ?>
                                    <tr 
                                        class="alert-row" 
                                        data-email="<?= e(strtolower($email)) ?>"
                                        data-product="<?= e(strtolower($prodName)) ?>"
                                        data-sku="<?= e(strtolower($sku)) ?>"
                                        data-status="<?= $filterVal ?>"
                                    >
                                        <td><span class="order-box">#<?= $alertId ?></span></td>
                                        <td><strong style="color:var(--text-hi); font-size:13px;"><?= e($email) ?></strong></td>
                                        <td>
                                            <div style="color:var(--text-hi); font-weight:700;"><?= e($prodName) ?></div>
                                            <div style="font-family:monospace; color:var(--text-mute); font-size:10px;">SKU: <?= e($sku) ?></div>
                                        </td>
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
                            <h3 style="color:var(--text-hi); margin:0;">No matching alerts</h3>
                            <p style="font-size:12px; margin-top:5px;">Try adjusting your search terms or status filter.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- KEYBOARD SHORTCUTS HELP BOX -->
            <div class="shortcut-help-box" id="shortcutBox">
                <div style="display:flex; align-items:center; gap:8px; color:var(--text-hi); font-size:12px; font-weight:800;">
                    <span>⌨</span><span>Keyboard Shortcuts</span>
                    <small style="margin-left:auto; color:var(--text-mute);">B P V X H • Esc</small>
                </div>
                <div class="shortcut-grid">
                    <div class="shortcut-item"><span class="shortcut-key">B</span><span style="font-size:11px;">Focus Search Box</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">C</span><span style="font-size:11px;">Status Filter</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">P</span><span style="font-size:11px;">Print Report</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">V</span><span style="font-size:11px;">Download PDF</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">X</span><span style="font-size:11px;">Download Excel (.xlsx)</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">H</span><span style="font-size:11px;">Toggle Shortcuts</span></div>
                </div>
            </div>

        </div>
    </section>
</main>

<!-- LIBRARIES FOR PDF & EXCEL EXPORT -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.4/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        const searchInput  = document.getElementById('alertSearch');
        const statusFilter = document.getElementById('statusFilter');
        const table        = document.getElementById('alertTable');
        const countElement = document.getElementById('visibleCount');
        const noResult     = document.getElementById('noResult');
        const shortcutBox  = document.getElementById('shortcutBox');

        function rows() {
            return table ? Array.from(table.querySelectorAll('tbody .alert-row')) : [];
        }

        function visibleRows() {
            return rows().filter(r => r.style.display !== 'none');
        }

        function filterAlerts() {
            if (!table) return;

            const q  = (searchInput?.value || '').toLowerCase().trim();
            const st = (statusFilter?.value || 'all');

            let count = 0;

            rows().forEach(function(row) {
                const email = row.dataset.email || '';
                const prod  = row.dataset.product || '';
                const sku   = row.dataset.sku || '';
                const rSt   = row.dataset.status || '';

                const qMatch  = (!q || email.includes(q) || prod.includes(q) || sku.includes(q));
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
                    'Alert ID': row.cells[0]?.innerText.trim() || '',
                    'Customer Email': row.dataset.email || '',
                    'Product / SKU': row.cells[2]?.innerText.trim().replace(/\n/g, ' - ') || '',
                    'Status': row.querySelector('.status-pill')?.innerText.trim() || '',
                    'Requested Date': row.cells[4]?.innerText.trim() || ''
                };
            });

            if (window.XLSX) {
                const ws = XLSX.utils.json_to_sheet(data);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'BackInStockAlerts');
                XLSX.writeFile(wb, 'back-in-stock-alerts-' + new Date().toISOString().slice(0, 10) + '.xlsx');
            }
        }

        function pdfExport() {
            if (!window.jspdf || !window.jspdf.jsPDF) {
                alert('PDF library not available. Please use Print.');
                return;
            }

            const body = visibleRows().map(function(row) {
                return [
                    row.cells[0]?.innerText.trim() || '',
                    row.dataset.email || '',
                    row.cells[2]?.innerText.trim().replace(/\n/g, ' - ') || '',
                    row.querySelector('.status-pill')?.innerText.trim() || '',
                    row.cells[4]?.innerText.trim() || ''
                ];
            });

            const doc = new jspdf.jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
            doc.setFontSize(16);
            doc.text('GatewayLinen - Back in Stock Alerts Report', 14, 14);
            doc.setFontSize(9);
            doc.text('Generated: ' + new Date().toLocaleString(), 14, 20);

            if (typeof doc.autoTable === 'function') {
                doc.autoTable({
                    startY: 25,
                    head: [['# ID', 'Customer Email', 'Product / SKU', 'Status', 'Requested Date']],
                    body: body,
                    styles: { fontSize: 8, cellPadding: 3 },
                    headStyles: { fillColor: [16, 185, 129] }
                });
            }

            doc.save('back-in-stock-alerts-' + new Date().toISOString().slice(0, 10) + '.pdf');
        }

        document.getElementById('printBtn')?.addEventListener('click', () => window.print());
        document.getElementById('printBtn2')?.addEventListener('click', () => window.print());
        document.getElementById('pdfBtn')?.addEventListener('click', pdfExport);
        document.getElementById('pdfBtn2')?.addEventListener('click', pdfExport);
        document.getElementById('excelBtn')?.addEventListener('click', excelExport);
        document.getElementById('excelBtn2')?.addEventListener('click', excelExport);

        searchInput?.addEventListener('input', filterAlerts);
        statusFilter?.addEventListener('change', filterAlerts);

        /* KEYBOARD SHORTCUTS */
        document.addEventListener('keydown', function(e) {
            const tag = (e.target?.tagName || '').toLowerCase();
            const isTyping = tag === 'input' || tag === 'textarea' || tag === 'select';

            if (isTyping) return;

            const key = (e.key || '').toUpperCase();

            if (key === 'B') {
                e.preventDefault();
                searchInput?.focus();
                searchInput?.select();
            } else if (key === 'C') {
                e.preventDefault();
                statusFilter?.focus();
            } else if (key === 'P') {
                e.preventDefault();
                window.print();
            } else if (key === 'V') {
                e.preventDefault();
                pdfExport();
            } else if (key === 'X') {
                e.preventDefault();
                excelExport();
            } else if (key === 'H') {
                e.preventDefault();
                shortcutBox?.classList.toggle('hidden');
            } else if (key === 'ESCAPE') {
                if (searchInput?.value) {
                    searchInput.value = '';
                    filterAlerts();
                }
                searchInput?.blur();
                statusFilter?.blur();
            }
        });

        filterAlerts();
    });
})();
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>