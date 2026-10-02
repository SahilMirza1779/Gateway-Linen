<?php
declare(strict_types=1);

session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$activeMenu = 'wholesale';
$pageTitle  = 'GatewayLinen | Wholesale Clients';

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] = $_SESSION['admin_username'] ?? 'GatewayLinen Administrator';
}
if (!isset($_SESSION['admin_role'])) {
    $_SESSION['admin_role'] = 'Administrator';
}

if (empty($_SESSION['wholesale_csrf'])) {
    $_SESSION['wholesale_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['wholesale_csrf'];

function e(mixed $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function moneyValue(mixed $value): string
{
    return number_format((float)($value ?? 0), 2);
}

function dateValue(mixed $value): string
{
    if ($value instanceof DateTimeInterface) {
        return $value->format('d M Y, h:i A');
    }
    if ($value === null || trim((string)$value) === '') {
        return '—';
    }
    $timestamp = strtotime((string)$value);
    return $timestamp ? date('d M Y, h:i A', $timestamp) : (string)$value;
}

$actionMessage = trim((string)($_GET['success'] ?? ''));
$actionError   = trim((string)($_GET['error'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = (string)($_POST['csrf_token'] ?? '');

    if (!hash_equals($csrfToken, $postedToken)) {
        header('Location: index.php?error=' . urlencode('Security validation failed. Please try again.'));
        exit;
    }

    $actionType   = trim((string)($_POST['action'] ?? ''));
    $targetUserId = (int)($_POST['user_id'] ?? 0);

    if ($targetUserId <= 0) {
        header('Location: index.php?error=' . urlencode('Invalid wholesale client.'));
        exit;
    }

    if ($actionType === 'approve_wholesale') {
        $discount = max(0, min(100, (float)($_POST['discount_pct'] ?? 15)));
        $credit   = max(0, (float)($_POST['credit_limit'] ?? 5000));

        $sql = "UPDATE dbo.Users SET IsWholesaleApproved = 1, WholesaleDiscountPct = ?, CreditLimit = ? WHERE UserId = ?";
        $stmt = sqlsrv_query($conn, $sql, [$discount, $credit, $targetUserId]);

        if ($stmt !== false) {
            header('Location: index.php?success=' . urlencode('Wholesale client approved successfully.'));
            exit;
        }
        header('Location: index.php?error=' . urlencode('Failed to approve wholesale client.'));
        exit;
    }

    if ($actionType === 'reject_wholesale') {
        $sql = "UPDATE dbo.Users SET IsWholesaleApproved = 0 WHERE UserId = ?";
        $stmt = sqlsrv_query($conn, $sql, [$targetUserId]);

        if ($stmt !== false) {
            header('Location: index.php?success=' . urlencode('Wholesale access revoked successfully.'));
            exit;
        }
        header('Location: index.php?error=' . urlencode('Failed to update wholesale client status.'));
        exit;
    }

    if ($actionType === 'update_terms') {
        $discount = max(0, min(100, (float)($_POST['discount_pct'] ?? 0)));
        $credit   = max(0, (float)($_POST['credit_limit'] ?? 0));

        $sql = "UPDATE dbo.Users SET WholesaleDiscountPct = ?, CreditLimit = ? WHERE UserId = ?";
        $stmt = sqlsrv_query($conn, $sql, [$discount, $credit, $targetUserId]);

        if ($stmt !== false) {
            header('Location: index.php?success=' . urlencode('Wholesale pricing terms updated successfully.'));
            exit;
        }
        header('Location: index.php?error=' . urlencode('Failed to update wholesale pricing terms.'));
        exit;
    }

    header('Location: index.php?error=' . urlencode('Unknown action.'));
    exit;
}

$approvedCount = 0;
$pendingCount  = 0;
$activeQuotes  = 0;
$openInquiries = 0;

$stmt = sqlsrv_query($conn, "SELECT COUNT(*) AS Cnt FROM dbo.Users WHERE ISNULL(IsWholesaleApproved, 0) = 1");
if ($stmt && ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) { $approvedCount = (int)$row['Cnt']; }
if ($stmt) sqlsrv_free_stmt($stmt);

$stmt = sqlsrv_query($conn, "SELECT COUNT(*) AS Cnt FROM dbo.Users WHERE CompanyName IS NOT NULL AND LTRIM(RTRIM(CompanyName)) <> '' AND ISNULL(IsWholesaleApproved, 0) = 0");
if ($stmt && ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) { $pendingCount = (int)$row['Cnt']; }
if ($stmt) sqlsrv_free_stmt($stmt);

$stmt = sqlsrv_query($conn, "SELECT COUNT(*) AS Cnt FROM dbo.Quotes WHERE Status IN ('Pending', 'Active', 'Sent')");
if ($stmt && ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) { $activeQuotes = (int)$row['Cnt']; }
if ($stmt) sqlsrv_free_stmt($stmt);

$stmt = sqlsrv_query($conn, "SELECT COUNT(*) AS Cnt FROM dbo.BulkInquiries WHERE Status IN ('New', 'Pending', 'Open')");
if ($stmt && ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) { $openInquiries = (int)$row['Cnt']; }
if ($stmt) sqlsrv_free_stmt($stmt);

$sql = "
    SELECT
        u.UserId,
        u.FullName,
        u.Email,
        u.Phone,
        ISNULL(u.CompanyName, 'Individual Buyer') AS CompanyName,
        ISNULL(u.TaxNumber, 'N/A') AS TaxNumber,
        ISNULL(u.IsWholesaleApproved, 0) AS IsWholesaleApproved,
        ISNULL(u.WholesaleDiscountPct, 0.00) AS WholesaleDiscountPct,
        ISNULL(u.CreditLimit, 0.00) AS CreditLimit,
        u.CreatedAt,
        u.UpdatedAt,
        u.LastLoginAt,
        0 AS TotalOrders,
        0.00 AS TotalSpent
    FROM dbo.Users u
    WHERE
        ISNULL(u.IsWholesaleApproved, 0) = 1
        OR (
            u.CompanyName IS NOT NULL
            AND LTRIM(RTRIM(u.CompanyName)) <> ''
        )
    ORDER BY
        CASE WHEN ISNULL(u.IsWholesaleApproved, 0) = 1 THEN 0 ELSE 1 END,
        u.UserId DESC
";

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
.wholesale-page,
.wholesale-page * { box-sizing: border-box; }

.wholesale-page {
    width: 100%;
    max-width: 1600px;
    margin: 0 auto;
    padding: 0;
    font-size: 13px;

    --ws-page: #f3f6fa;
    --ws-card: #ffffff;
    --ws-card-alt: #f8fafc;
    --ws-input: #ffffff;
    --ws-border: #dce4ec;
    --ws-border-soft: #e8edf3;
    --ws-text: #162334;
    --ws-body: #536579;
    --ws-muted: #7b8da1;
    --ws-green: #059669;
    --ws-green-soft: rgba(5,150,105,.10);
    --ws-red: #dc2626;
    --ws-red-soft: rgba(220,38,38,.09);
    --ws-blue: #0284c7;
    --ws-blue-soft: rgba(2,132,199,.09);
    --ws-amber: #d97706;
    --ws-amber-soft: rgba(217,119,6,.10);
    --ws-purple: #7c3aed;
    --ws-purple-soft: rgba(124,58,237,.09);
    --ws-shadow: 0 5px 18px rgba(15,23,42,.05);

    background: var(--ws-page);
    color: var(--ws-body);
}

/* Same light/dark behavior as Categories */
html[data-theme="dark"] .wholesale-page,
body[data-theme="dark"] .wholesale-page,
html.dark .wholesale-page,
body.dark .wholesale-page,
html.dark-mode .wholesale-page,
body.dark-mode .wholesale-page {
    --ws-page: #0a1119;
    --ws-card: #111b26;
    --ws-card-alt: #0f1823;
    --ws-input: #0d1620;
    --ws-border: #1e2d3d;
    --ws-border-soft: #182636;
    --ws-text: #f0f4f8;
    --ws-body: #a8b8c8;
    --ws-muted: #6f8295;
    --ws-green: #10b981;
    --ws-green-soft: rgba(16,185,129,.12);
    --ws-red: #ef4444;
    --ws-red-soft: rgba(239,68,68,.12);
    --ws-blue: #38bdf8;
    --ws-blue-soft: rgba(56,189,248,.12);
    --ws-amber: #f59e0b;
    --ws-amber-soft: rgba(245,158,11,.12);
    --ws-purple: #a78bfa;
    --ws-purple-soft: rgba(167,139,250,.12);
    --ws-shadow: none;
}

.wholesale-page { background: var(--ws-page); }

.wholesale-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 28px;
    margin: 0 0 18px;
    padding: 0 0 16px;
    border-bottom: 1px solid var(--ws-border);
}

.wholesale-breadcrumb {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 7px;
    color: var(--ws-muted);
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .45px;
}

.wholesale-breadcrumb .current { color: var(--ws-green); }

.wholesale-page-header h1 {
    margin: 0;
    color: var(--ws-text);
    font-size: 30px;
    font-weight: 900;
    letter-spacing: -.5px;
}

.wholesale-page-header p {
    margin: 7px 0 0;
    color: var(--ws-muted);
    font-size: 13px;
    font-weight: 600;
}

.header-actions,
.wholesale-filters,
.wholesale-actions {
    display: flex;
    align-items: center;
    gap: 9px;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 44px;
    padding: 0 15px;
    border: 1px solid var(--ws-border);
    border-radius: 9px;
    background: var(--ws-card);
    color: var(--ws-text) !important;
    font-size: 13px;
    font-weight: 900;
    text-decoration: none;
    cursor: pointer;
    transition: .16s ease;
}

.btn:hover {
    border-color: var(--ws-green);
    background: var(--ws-green-soft);
    color: var(--ws-green) !important;
}

.btn-primary {
    border-color: transparent;
    background: linear-gradient(135deg,#059669,#10b981);
    color: #fff !important;
    box-shadow: 0 5px 14px rgba(16,185,129,.16);
}

.btn-primary:hover {
    background: linear-gradient(135deg,#047857,#059669);
    color: #fff !important;
}

html[data-theme="dark"] .btn-primary,
body[data-theme="dark"] .btn-primary,
html.dark .btn-primary,
body.dark .btn-primary,
html.dark-mode .btn-primary,
body.dark-mode .btn-primary {
    background: #111b26;
    border: 1px solid #1e2d3d;
    color: #f0f4f8 !important;
    box-shadow: none;
}

html[data-theme="dark"] .btn-primary:hover,
body[data-theme="dark"] .btn-primary:hover,
html.dark .btn-primary:hover,
body.dark .btn-primary:hover,
html.dark-mode .btn-primary:hover,
body.dark-mode .btn-primary:hover {
    border-color: #10b981;
    background: rgba(16,185,129,.12);
    color: #10b981 !important;
}

.btn-blue { color: var(--ws-blue) !important; }

.btn small,
.key-hint {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 21px;
    height: 21px;
    padding: 0 4px;
    border: 1px solid currentColor;
    border-radius: 4px;
    font: 800 9px/1 monospace;
    opacity: .9;
}

.wholesale-stats {
    display: grid;
    grid-template-columns: repeat(4,minmax(0,1fr));
    gap: 14px;
    margin-bottom: 16px;
}

.wholesale-stat-item {
    display: flex;
    align-items: center;
    gap: 13px;
    min-height: 82px;
    padding: 14px 17px;
    background: var(--ws-card);
    border: 1px solid var(--ws-border);
    border-radius: 11px;
    box-shadow: var(--ws-shadow);
}

.wholesale-stat-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    flex: 0 0 40px;
    border-radius: 10px;
    font-size: 17px;
    font-weight: 900;
}

.stat-green { background: var(--ws-green-soft); color: var(--ws-green); }
.stat-amber { background: var(--ws-amber-soft); color: var(--ws-amber); }
.stat-blue { background: var(--ws-blue-soft); color: var(--ws-blue); }
.stat-purple { background: var(--ws-purple-soft); color: var(--ws-purple); }

.wholesale-stat-label {
    color: var(--ws-muted);
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .45px;
}

.wholesale-stat-value {
    margin-top: 4px;
    color: var(--ws-text);
    font-size: 24px;
    font-weight: 900;
    line-height: 1;
}

.notice {
    margin-bottom: 12px;
    padding: 12px 14px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
}

.notice-success {
    border: 1px solid rgba(5,150,105,.25);
    background: var(--ws-green-soft);
    color: var(--ws-green);
}

.notice-error {
    border: 1px solid rgba(220,38,38,.25);
    background: var(--ws-red-soft);
    color: var(--ws-red);
}

.wholesale-content {
    background: var(--ws-card);
    border: 1px solid var(--ws-border);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--ws-shadow);
}

.wholesale-content-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 28px;
    padding: 20px 24px;
    min-height: 104px;
    border-bottom: 1px solid var(--ws-border);
}

.wholesale-content-title h2 {
    margin: 0;
    color: var(--ws-text);
    font-size: 22px;
    font-weight: 900;
}

.wholesale-content-title p {
    margin: 6px 0 0;
    color: var(--ws-muted);
    font-size: 11px;
    font-weight: 600;
    line-height: 1.5;
}

.wholesale-filters {
    flex: 1 1 auto;
    justify-content: flex-end;
}

.wholesale-search-wrap {
    position: relative;
    width: min(650px,100%);
    flex: 1 1 520px;
}

.wholesale-search-icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--ws-muted);
    pointer-events: none;
    font-size: 17px;
}

.wholesale-search,
.wholesale-status-filter {
    height: 54px;
    border: 1px solid var(--ws-border);
    border-radius: 10px;
    outline: none;
    background: var(--ws-input);
    color: var(--ws-text);
    font-size: 14px;
    font-weight: 600;
}

.wholesale-search {
    width: 100%;
    padding: 0 16px 0 46px;
}

.wholesale-status-filter {
    min-width: 190px;
    padding: 0 14px;
}

.wholesale-search::placeholder {
    color: var(--ws-muted);
    font-size: 14px;
    font-weight: 500;
}

.wholesale-search:focus,
.wholesale-status-filter:focus {
    border-color: var(--ws-green);
    box-shadow: 0 0 0 3px var(--ws-green-soft);
}

.wholesale-table-summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 24px;
    border-bottom: 1px solid var(--ws-border);
}

.wholesale-result-text {
    color: var(--ws-muted);
    font-size: 11px;
    font-weight: 700;
}

.wholesale-result-text strong { color: var(--ws-text); }

.wholesale-table-wrapper {
    width: 100%;
    overflow-x: auto;
}

.wholesale-table {
    width: 100%;
    min-width: 1250px;
    border-collapse: collapse;
}

.wholesale-table th {
    height: 48px;
    padding: 0 16px;
    background: var(--ws-card-alt);
    border-bottom: 1px solid var(--ws-border);
    color: var(--ws-muted);
    font-size: 10px;
    font-weight: 900;
    text-align: left;
    text-transform: uppercase;
    letter-spacing: .55px;
    white-space: nowrap;
}

.wholesale-table td {
    padding: 15px 16px;
    background: transparent;
    border-bottom: 1px solid var(--ws-border-soft);
    color: var(--ws-body);
    font-size: 12px;
    line-height: 1.45;
    vertical-align: middle;
}

.wholesale-table tbody tr { cursor: pointer; }
.wholesale-table tbody tr:hover { background: var(--ws-green-soft); }

.order-box {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 30px;
    padding: 0 8px;
    border-radius: 7px;
    background: var(--ws-input);
    border: 1px solid var(--ws-border);
    color: var(--ws-green);
    font-size: 11px;
    font-weight: 900;
}

.client-main {
    display: flex;
    align-items: center;
    gap: 11px;
}

.client-avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    flex: 0 0 48px;
    border-radius: 10px;
    background: var(--ws-green-soft);
    color: var(--ws-green);
    border: 1px solid var(--ws-border);
    font-size: 17px;
    font-weight: 900;
}

.client-company { color: var(--ws-text); font-size: 14px; font-weight: 900; }
.client-name { margin-top: 3px; color: var(--ws-body); font-size: 12px; font-weight: 700; }
.client-email { margin-top: 3px; color: var(--ws-muted); font-size: 10px; }
.client-tax { margin-top: 4px; color: var(--ws-muted); font-family: monospace; font-size: 10px; }

.wholesale-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 28px;
    padding: 0 10px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 800;
    white-space: nowrap;
}

.status-dot { width: 6px; height: 6px; border-radius: 50%; }
.status-approved { background: var(--ws-green-soft); color: var(--ws-green); }
.status-approved .status-dot { background: var(--ws-green); box-shadow: 0 0 6px var(--ws-green); }
.status-pending { background: var(--ws-amber-soft); color: var(--ws-amber); }
.status-pending .status-dot { background: var(--ws-amber); }

.discount-value { color: var(--ws-green); font-size: 14px; font-weight: 900; }
.credit-value, .orders-value { color: var(--ws-text); font-size: 13px; font-weight: 900; }
.spent-value { margin-top: 3px; color: var(--ws-muted); font-size: 10px; }

.wholesale-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border: 1px solid var(--ws-border);
    border-radius: 8px;
    background: var(--ws-card);
    color: var(--ws-body) !important;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
}

.wholesale-action:hover {
    border-color: var(--ws-green);
    background: var(--ws-green-soft);
    color: var(--ws-green) !important;
}

.wholesale-action-danger:hover {
    border-color: rgba(220,38,38,.4);
    background: var(--ws-red-soft);
    color: var(--ws-red) !important;
}

.wholesale-action-approve { color: var(--ws-green) !important; }

.wholesale-empty,
.wholesale-no-result {
    padding: 65px 20px;
    text-align: center;
}

.wholesale-empty h3,
.wholesale-no-result h3 {
    margin: 0;
    color: var(--ws-text);
    font-size: 16px;
    font-weight: 900;
}

.wholesale-empty p,
.wholesale-no-result p {
    margin: 6px 0 0;
    color: var(--ws-muted);
    font-size: 11px;
}

/* =========================
   MODALS - LIGHT MODE
   ========================= */
.modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 99999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(0,0,0,.75);
    backdrop-filter: blur(4px);
}

.modal-backdrop.show { display: flex; }

.wholesale-modal {
    width: min(850px,100%);
    max-height: 90vh;
    overflow-y: auto;
    background: #ffffff !important;
    color: #162334 !important;
    border: 1px solid #dce4ec;
    border-radius: 14px;
    box-shadow: 0 25px 75px rgba(0,0,0,.50);
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px;
    border-bottom: 1px solid #dce4ec;
    background: #f8fafc !important;
}

.modal-header h3 {
    margin: 0;
    color: #162334 !important;
    font-size: 18px;
    font-weight: 900;
}

.modal-close {
    border: 0;
    background: transparent;
    color: #7b8da1;
    font-size: 26px;
    font-weight: 700;
    cursor: pointer;
}

.modal-close:hover { color: #dc2626; }

.modal-body {
    padding: 28px;
    background: #ffffff !important;
    color: #162334 !important;
}

.detail-main-layout {
    display: grid;
    grid-template-columns: 170px 1fr;
    gap: 22px;
    align-items: start;
}

.detail-avatar {
    width: 170px;
    height: 170px;
    border-radius: 14px;
    background: #f8fafc;
    border: 1px solid #dce4ec;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #059669;
    font-size: 52px;
    font-weight: 900;
}

.detail-info-grid {
    display: grid;
    grid-template-columns: repeat(2,minmax(0,1fr));
    gap: 12px;
}

.detail-item {
    min-height: 68px;
    padding: 13px;
    border: 1px solid #e8edf3;
    border-radius: 9px;
    background: #f8fafc;
}

.detail-item.full-width { grid-column: 1 / -1; }

.detail-item label {
    display: block;
    margin-bottom: 5px;
    color: #7b8da1 !important;
    font-size: 9px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .5px;
}

.detail-item .val {
    color: #162334 !important;
    font-size: 13px;
    font-weight: 800;
    word-break: break-word;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding: 14px 24px;
    border-top: 1px solid #dce4ec;
    background: #f8fafc !important;
}

.modal-footer .btn {
    background: #ffffff !important;
    color: #162334 !important;
    border-color: #dce4ec !important;
}

.modal-footer .btn:hover {
    background: rgba(5,150,105,.10) !important;
    color: #059669 !important;
    border-color: #059669 !important;
}

.form-label {
    display: block;
    margin-bottom: 6px;
    color: #536579;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
}

.form-control {
    width: 100%;
    height: 46px;
    padding: 0 13px;
    border: 1px solid #dce4ec;
    border-radius: 9px;
    outline: none;
    background: #ffffff;
    color: #162334;
    font-size: 13px;
    font-weight: 700;
}

.form-control:focus {
    border-color: #059669;
    box-shadow: 0 0 0 3px rgba(5,150,105,.10);
}

.form-help {
    margin-top: 5px;
    color: #7b8da1;
    font-size: 10px;
}

.approve-summary {
    padding: 12px 14px;
    margin-bottom: 16px;
    border-radius: 9px;
    background: rgba(5,150,105,.10);
    border: 1px solid rgba(5,150,105,.25);
    color: #059669;
    font-size: 12px;
    font-weight: 700;
}

/* =========================
   MODALS - DARK MODE
   ========================= */
html[data-theme="dark"] .wholesale-modal,
body[data-theme="dark"] .wholesale-modal,
html.dark .wholesale-modal,
body.dark .wholesale-modal,
html.dark-mode .wholesale-modal,
body.dark-mode .wholesale-modal {
    background: #111b26 !important;
    color: #f0f4f8 !important;
    border-color: #1e2d3d;
}

html[data-theme="dark"] .wholesale-modal .modal-header,
body[data-theme="dark"] .wholesale-modal .modal-header,
html.dark .wholesale-modal .modal-header,
body.dark .wholesale-modal .modal-header,
html.dark-mode .wholesale-modal .modal-header,
body.dark-mode .wholesale-modal .modal-header {
    background: #0f1823 !important;
    border-color: #1e2d3d;
}

html[data-theme="dark"] .wholesale-modal .modal-header h3,
body[data-theme="dark"] .wholesale-modal .modal-header h3,
html.dark .wholesale-modal .modal-header h3,
body.dark .wholesale-modal .modal-header h3,
html.dark-mode .wholesale-modal .modal-header h3,
body.dark-mode .wholesale-modal .modal-header h3 {
    color: #f0f4f8 !important;
}

html[data-theme="dark"] .wholesale-modal .modal-body,
body[data-theme="dark"] .wholesale-modal .modal-body,
html.dark .wholesale-modal .modal-body,
body.dark .wholesale-modal .modal-body,
html.dark-mode .wholesale-modal .modal-body,
body.dark-mode .wholesale-modal .modal-body {
    background: #111b26 !important;
    color: #f0f4f8 !important;
}

html[data-theme="dark"] .wholesale-modal .detail-avatar,
body[data-theme="dark"] .wholesale-modal .detail-avatar,
html.dark .wholesale-modal .detail-avatar,
body.dark .wholesale-modal .detail-avatar,
html.dark-mode .wholesale-modal .detail-avatar,
body.dark-mode .wholesale-modal .detail-avatar {
    background: #0d1620;
    border-color: #1e2d3d;
    color: #10b981;
}

html[data-theme="dark"] .wholesale-modal .detail-item,
body[data-theme="dark"] .wholesale-modal .detail-item,
html.dark .wholesale-modal .detail-item,
body.dark .wholesale-modal .detail-item,
html.dark-mode .wholesale-modal .detail-item,
body.dark-mode .wholesale-modal .detail-item {
    background: #0f1823;
    border-color: #182636;
}

html[data-theme="dark"] .wholesale-modal .detail-item label,
body[data-theme="dark"] .wholesale-modal .detail-item label,
html.dark .wholesale-modal .detail-item label,
body.dark .wholesale-modal .detail-item label,
html.dark-mode .wholesale-modal .detail-item label,
body.dark-mode .wholesale-modal .detail-item label {
    color: #6f8295 !important;
}

html[data-theme="dark"] .wholesale-modal .detail-item .val,
body[data-theme="dark"] .wholesale-modal .detail-item .val,
html.dark .wholesale-modal .detail-item .val,
body.dark .wholesale-modal .detail-item .val,
html.dark-mode .wholesale-modal .detail-item .val,
body.dark-mode .wholesale-modal .detail-item .val {
    color: #f0f4f8 !important;
}

html[data-theme="dark"] .wholesale-modal .modal-footer,
body[data-theme="dark"] .wholesale-modal .modal-footer,
html.dark .wholesale-modal .modal-footer,
body.dark .wholesale-modal .modal-footer,
html.dark-mode .wholesale-modal .modal-footer,
body.dark-mode .wholesale-modal .modal-footer {
    background: #0f1823 !important;
    border-color: #1e2d3d;
}

html[data-theme="dark"] .wholesale-modal .modal-footer .btn,
body[data-theme="dark"] .wholesale-modal .modal-footer .btn,
html.dark .wholesale-modal .modal-footer .btn,
body.dark .wholesale-modal .modal-footer .btn,
html.dark-mode .wholesale-modal .modal-footer .btn,
body.dark-mode .wholesale-modal .modal-footer .btn {
    background: #111b26 !important;
    color: #f0f4f8 !important;
    border-color: #1e2d3d !important;
}

html[data-theme="dark"] .wholesale-modal .form-label,
body[data-theme="dark"] .wholesale-modal .form-label,
html.dark .wholesale-modal .form-label,
body.dark .wholesale-modal .form-label,
html.dark-mode .wholesale-modal .form-label,
body.dark-mode .wholesale-modal .form-label {
    color: #a8b8c8;
}

html[data-theme="dark"] .wholesale-modal .form-control,
body[data-theme="dark"] .wholesale-modal .form-control,
html.dark .wholesale-modal .form-control,
body.dark .wholesale-modal .form-control,
html.dark-mode .wholesale-modal .form-control,
body.dark-mode .wholesale-modal .form-control {
    background: #0d1620;
    color: #f0f4f8;
    border-color: #1e2d3d;
}

html[data-theme="dark"] .wholesale-modal .form-help,
body[data-theme="dark"] .wholesale-modal .form-help,
html.dark .wholesale-modal .form-help,
body.dark .wholesale-modal .form-help,
html.dark-mode .wholesale-modal .form-help,
body.dark-mode .wholesale-modal .form-help {
    color: #6f8295;
}

html[data-theme="dark"] .wholesale-modal .approve-summary,
body[data-theme="dark"] .wholesale-modal .approve-summary,
html.dark .wholesale-modal .approve-summary,
body.dark .wholesale-modal .approve-summary,
html.dark-mode .wholesale-modal .approve-summary,
body.dark-mode .wholesale-modal .approve-summary {
    background: rgba(16,185,129,.12);
    border-color: rgba(16,185,129,.25);
    color: #10b981;
}

/* Responsive */
@media (max-width: 1200px) {
    .wholesale-page-header,
    .wholesale-content-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .header-actions { width: 100%; }
    .wholesale-filters { width: 100%; justify-content: stretch; }
    .wholesale-search-wrap { flex: 1 1 auto; }
}

@media (max-width: 900px) {
    .wholesale-stats {
        grid-template-columns: repeat(2,minmax(0,1fr));
    }

    .wholesale-content-header {
        gap: 15px;
    }

    .wholesale-filters {
        flex-direction: column;
        align-items: stretch;
    }

    .wholesale-search-wrap,
    .wholesale-status-filter {
        width: 100%;
        min-width: 0;
    }
}

@media (max-width: 680px) {
    .wholesale-stats {
        grid-template-columns: 1fr;
    }

    .wholesale-page-header h1 {
        font-size: 25px;
    }

    .header-actions .btn {
        flex: 1 1 calc(50% - 8px);
    }

    .wholesale-table-summary {
        gap: 10px;
        flex-direction: column;
        align-items: flex-start;
    }

    .detail-main-layout {
        grid-template-columns: 1fr;
    }

    .detail-avatar {
        width: 100%;
        height: 150px;
    }

    .detail-info-grid {
        grid-template-columns: 1fr;
    }

    .detail-item.full-width {
        grid-column: auto;
    }
}

@media print {
    body * { visibility: hidden; }

    .wholesale-content,
    .wholesale-content * {
        visibility: visible;
    }

    .wholesale-content {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        box-shadow: none !important;
        border: none !important;
    }

    .wholesale-content-header,
    .wholesale-filters,
    .wholesale-table-summary,
    .wholesale-actions,
    .header-actions,
    .wholesale-action,
    .modal-backdrop {
        display: none !important;
    }

    .wholesale-table {
        min-width: 0;
    }
}


/* ================================================================
   THEME CONSISTENCY FIX
   Light mode = everything light/white.
   Dark mode  = everything dark.
   This section intentionally overrides generic/global table, form,
   main/content and modal styles from the admin layout.
   ================================================================ */
.wholesale-page,
.wholesale-page .main,
.wholesale-page .content,
.wholesale-page .wholesale-content,
.wholesale-page .wholesale-table,
.wholesale-page .wholesale-table thead,
.wholesale-page .wholesale-table tbody,
.wholesale-page .wholesale-table tfoot,
.wholesale-page .wholesale-table tr,
.wholesale-page .wholesale-table td,
.wholesale-page .wholesale-table th {
    transition: background-color .18s ease, color .18s ease, border-color .18s ease;
}

/* LIGHT THEME */
.wholesale-page {
    --ws-page: #f3f6fa;
    --ws-card: #ffffff;
    --ws-card-alt: #f8fafc;
    --ws-input: #ffffff;
    --ws-border: #dce4ec;
    --ws-border-soft: #e8edf3;
    --ws-text: #162334;
    --ws-body: #536579;
    --ws-muted: #7b8da1;
    --ws-green: #059669;
    --ws-green-soft: rgba(5,150,105,.10);
    --ws-red: #dc2626;
    --ws-red-soft: rgba(220,38,38,.09);
    --ws-blue: #0284c7;
    --ws-blue-soft: rgba(2,132,199,.09);
    --ws-amber: #d97706;
    --ws-amber-soft: rgba(217,119,6,.10);
    --ws-purple: #7c3aed;
    --ws-purple-soft: rgba(124,58,237,.09);
    --ws-shadow: 0 5px 18px rgba(15,23,42,.05);
    background: var(--ws-page) !important;
    color: var(--ws-body) !important;
}

.wholesale-page,
.wholesale-page .wholesale-content,
.wholesale-page .wholesale-stat-item,
.wholesale-page .wholesale-action,
.wholesale-page .btn,
.wholesale-page .order-box,
.wholesale-page .wholesale-table,
.wholesale-page .wholesale-table tbody,
.wholesale-page .wholesale-table tr,
.wholesale-page .wholesale-table td {
    background-color: var(--ws-card) !important;
}

.wholesale-page .wholesale-table th {
    background-color: var(--ws-card-alt) !important;
}

.wholesale-page .wholesale-search,
.wholesale-page .wholesale-status-filter,
.wholesale-page .form-control {
    background-color: var(--ws-input) !important;
    color: var(--ws-text) !important;
}

.wholesale-page .wholesale-table td,
.wholesale-page .wholesale-table th,
.wholesale-page .wholesale-content,
.wholesale-page .wholesale-content-header,
.wholesale-page .wholesale-table-summary,
.wholesale-page .wholesale-stat-item,
.wholesale-page .wholesale-action,
.wholesale-page .order-box,
.wholesale-page .btn,
.wholesale-page .wholesale-search,
.wholesale-page .wholesale-status-filter,
.wholesale-page .form-control {
    border-color: var(--ws-border) !important;
}

.wholesale-page .wholesale-table td {
    border-bottom-color: var(--ws-border-soft) !important;
    color: var(--ws-body) !important;
}

.wholesale-page .wholesale-table tbody tr {
    background-color: var(--ws-card) !important;
}

.wholesale-page .wholesale-table tbody tr:hover {
    background-color: var(--ws-green-soft) !important;
}

/* DARK THEME - supports the common theme attributes/classes used by the admin shell */
html[data-theme="dark"] .wholesale-page,
body[data-theme="dark"] .wholesale-page,
html.dark .wholesale-page,
body.dark .wholesale-page,
html.dark-mode .wholesale-page,
body.dark-mode .wholesale-page,
html.theme-dark .wholesale-page,
body.theme-dark .wholesale-page,
html.is-dark .wholesale-page,
body.is-dark .wholesale-page,
.wholesale-page.is-dark {
    --ws-page: #0a1119;
    --ws-card: #111b26;
    --ws-card-alt: #0f1823;
    --ws-input: #0d1620;
    --ws-border: #1e2d3d;
    --ws-border-soft: #182636;
    --ws-text: #f0f4f8;
    --ws-body: #a8b8c8;
    --ws-muted: #8093a7;
    --ws-green: #10b981;
    --ws-green-soft: rgba(16,185,129,.12);
    --ws-red: #ef4444;
    --ws-red-soft: rgba(239,68,68,.12);
    --ws-blue: #38bdf8;
    --ws-blue-soft: rgba(56,189,248,.12);
    --ws-amber: #f59e0b;
    --ws-amber-soft: rgba(245,158,11,.12);
    --ws-purple: #a78bfa;
    --ws-purple-soft: rgba(167,139,250,.12);
    --ws-shadow: none;
    background-color: var(--ws-page) !important;
    color: var(--ws-body) !important;
}

html[data-theme="dark"] .wholesale-page .wholesale-content,
body[data-theme="dark"] .wholesale-page .wholesale-content,
html.dark .wholesale-page .wholesale-content,
body.dark .wholesale-page .wholesale-content,
html.dark-mode .wholesale-page .wholesale-content,
body.dark-mode .wholesale-page .wholesale-content,
html.theme-dark .wholesale-page .wholesale-content,
body.theme-dark .wholesale-page .wholesale-content,
html.is-dark .wholesale-page .wholesale-content,
body.is-dark .wholesale-page .wholesale-content,
.wholesale-page.is-dark .wholesale-content,
html[data-theme="dark"] .wholesale-page .wholesale-stat-item,
body[data-theme="dark"] .wholesale-page .wholesale-stat-item,
html.dark .wholesale-page .wholesale-stat-item,
body.dark .wholesale-page .wholesale-stat-item,
html.dark-mode .wholesale-page .wholesale-stat-item,
body.dark-mode .wholesale-page .wholesale-stat-item,
.wholesale-page.is-dark .wholesale-stat-item {
    background-color: var(--ws-card) !important;
}

html[data-theme="dark"] .wholesale-page .wholesale-table,
body[data-theme="dark"] .wholesale-page .wholesale-table,
html.dark .wholesale-page .wholesale-table,
body.dark .wholesale-page .wholesale-table,
html.dark-mode .wholesale-page .wholesale-table,
body.dark-mode .wholesale-page .wholesale-table,
html.theme-dark .wholesale-page .wholesale-table,
body.theme-dark .wholesale-page .wholesale-table,
html.is-dark .wholesale-page .wholesale-table,
body.is-dark .wholesale-page .wholesale-table,
.wholesale-page.is-dark .wholesale-table,
html[data-theme="dark"] .wholesale-page .wholesale-table tbody,
body[data-theme="dark"] .wholesale-page .wholesale-table tbody,
html.dark .wholesale-page .wholesale-table tbody,
body.dark .wholesale-page .wholesale-table tbody,
html.dark-mode .wholesale-page .wholesale-table tbody,
body.dark-mode .wholesale-page .wholesale-table tbody,
.wholesale-page.is-dark .wholesale-table tbody,
html[data-theme="dark"] .wholesale-page .wholesale-table tr,
body[data-theme="dark"] .wholesale-page .wholesale-table tr,
html.dark .wholesale-page .wholesale-table tr,
body.dark .wholesale-page .wholesale-table tr,
html.dark-mode .wholesale-page .wholesale-table tr,
body.dark-mode .wholesale-page .wholesale-table tr,
.wholesale-page.is-dark .wholesale-table tr,
html[data-theme="dark"] .wholesale-page .wholesale-table td,
body[data-theme="dark"] .wholesale-page .wholesale-table td,
html.dark .wholesale-page .wholesale-table td,
body.dark .wholesale-page .wholesale-table td,
html.dark-mode .wholesale-page .wholesale-table td,
body.dark-mode .wholesale-page .wholesale-table td,
.wholesale-page.is-dark .wholesale-table td {
    background-color: var(--ws-card) !important;
    color: var(--ws-body) !important;
    border-color: var(--ws-border-soft) !important;
}

html[data-theme="dark"] .wholesale-page .wholesale-table th,
body[data-theme="dark"] .wholesale-page .wholesale-table th,
html.dark .wholesale-page .wholesale-table th,
body.dark .wholesale-page .wholesale-table th,
html.dark-mode .wholesale-page .wholesale-table th,
body.dark-mode .wholesale-page .wholesale-table th,
html.theme-dark .wholesale-page .wholesale-table th,
body.theme-dark .wholesale-page .wholesale-table th,
html.is-dark .wholesale-page .wholesale-table th,
body.is-dark .wholesale-page .wholesale-table th,
.wholesale-page.is-dark .wholesale-table th {
    background-color: var(--ws-card-alt) !important;
    color: var(--ws-muted) !important;
    border-color: var(--ws-border) !important;
}

html[data-theme="dark"] .wholesale-page .wholesale-table tbody tr:hover,
body[data-theme="dark"] .wholesale-page .wholesale-table tbody tr:hover,
html.dark .wholesale-page .wholesale-table tbody tr:hover,
body.dark .wholesale-page .wholesale-table tbody tr:hover,
html.dark-mode .wholesale-page .wholesale-table tbody tr:hover,
body.dark-mode .wholesale-page .wholesale-table tbody tr:hover,
.wholesale-page.is-dark .wholesale-table tbody tr:hover {
    background-color: var(--ws-green-soft) !important;
}

html[data-theme="dark"] .wholesale-page .wholesale-search,
body[data-theme="dark"] .wholesale-page .wholesale-search,
html.dark .wholesale-page .wholesale-search,
body.dark .wholesale-page .wholesale-search,
html.dark-mode .wholesale-page .wholesale-search,
body.dark-mode .wholesale-page .wholesale-search,
html.theme-dark .wholesale-page .wholesale-search,
body.theme-dark .wholesale-page .wholesale-search,
.wholesale-page.is-dark .wholesale-search,
html[data-theme="dark"] .wholesale-page .wholesale-status-filter,
body[data-theme="dark"] .wholesale-page .wholesale-status-filter,
html.dark .wholesale-page .wholesale-status-filter,
body.dark .wholesale-page .wholesale-status-filter,
html.dark-mode .wholesale-page .wholesale-status-filter,
body.dark-mode .wholesale-page .wholesale-status-filter,
.wholesale-page.is-dark .wholesale-status-filter,
html[data-theme="dark"] .wholesale-page .form-control,
body[data-theme="dark"] .wholesale-page .form-control,
html.dark .wholesale-page .form-control,
body.dark .wholesale-page .form-control,
html.dark-mode .wholesale-page .form-control,
body.dark-mode .wholesale-page .form-control,
.wholesale-page.is-dark .form-control {
    background-color: var(--ws-input) !important;
    color: var(--ws-text) !important;
    border-color: var(--ws-border) !important;
}

html[data-theme="dark"] .wholesale-page .wholesale-table .client-company,
body[data-theme="dark"] .wholesale-page .wholesale-table .client-company,
html.dark .wholesale-page .wholesale-table .client-company,
body.dark .wholesale-page .wholesale-table .client-company,
html.dark-mode .wholesale-page .wholesale-table .client-company,
body.dark-mode .wholesale-page .wholesale-table .client-company,
.wholesale-page.is-dark .wholesale-table .client-company,
html[data-theme="dark"] .wholesale-page .credit-value,
body[data-theme="dark"] .wholesale-page .credit-value,
html.dark .wholesale-page .credit-value,
body.dark .wholesale-page .credit-value,
html.dark-mode .wholesale-page .credit-value,
body.dark-mode .wholesale-page .credit-value,
.wholesale-page.is-dark .credit-value,
html[data-theme="dark"] .wholesale-page .orders-value,
body[data-theme="dark"] .wholesale-page .orders-value,
html.dark .wholesale-page .orders-value,
body.dark .wholesale-page .orders-value,
html.dark-mode .wholesale-page .orders-value,
body.dark-mode .wholesale-page .orders-value,
.wholesale-page.is-dark .orders-value {
    color: var(--ws-text) !important;
}

/* Dark page surface: also cover generic admin wrappers immediately around the page. */
html[data-theme="dark"] body,
body[data-theme="dark"],
html.dark body,
body.dark,
html.dark-mode body,
body.dark-mode,
html.theme-dark body,
body.theme-dark,
html.is-dark body,
body.is-dark {
    color-scheme: dark;
}

/* MODALS always follow the same palette as the page */
.wholesale-page ~ .modal-backdrop .wholesale-modal,
.wholesale-modal {
    background: var(--ws-card) !important;
    color: var(--ws-text) !important;
    border-color: var(--ws-border) !important;
}

.wholesale-modal .modal-header,
.wholesale-modal .modal-body,
.wholesale-modal .modal-footer,
.wholesale-modal .detail-item,
.wholesale-modal .detail-avatar {
    border-color: var(--ws-border) !important;
}

.wholesale-modal .modal-header,
.wholesale-modal .modal-footer,
.wholesale-modal .detail-item {
    background: var(--ws-card-alt) !important;
}

.wholesale-modal .modal-body,
.wholesale-modal .detail-avatar {
    background: var(--ws-card) !important;
}

.wholesale-modal .modal-header h3,
.wholesale-modal .detail-item .val {
    color: var(--ws-text) !important;
}

.wholesale-modal .detail-item label,
.wholesale-modal .form-label,
.wholesale-modal .form-help {
    color: var(--ws-muted) !important;
}

.wholesale-modal .form-control {
    background: var(--ws-input) !important;
    color: var(--ws-text) !important;
    border-color: var(--ws-border) !important;
}

/* If the theme script adds this class, these rules are definitive. */
.wholesale-page.is-dark,
.wholesale-page.is-dark .wholesale-content,
.wholesale-page.is-dark .wholesale-stat-item,
.wholesale-page.is-dark .wholesale-table,
.wholesale-page.is-dark .wholesale-table tbody,
.wholesale-page.is-dark .wholesale-table tr,
.wholesale-page.is-dark .wholesale-table td,
.wholesale-page.is-dark .wholesale-action,
.wholesale-page.is-dark .btn {
    background-color: var(--ws-card) !important;
    color: var(--ws-body) !important;
}

.wholesale-page.is-dark .wholesale-table th {
    background-color: var(--ws-card-alt) !important;
    color: var(--ws-muted) !important;
}

.wholesale-page.is-dark .wholesale-content-title h2,
.wholesale-page.is-dark .wholesale-page-header h1,
.wholesale-page.is-dark .wholesale-stat-value,
.wholesale-page.is-dark .client-company,
.wholesale-page.is-dark .credit-value,
.wholesale-page.is-dark .orders-value,
.wholesale-page.is-dark .btn,
.wholesale-page.is-dark .wholesale-action {
    color: var(--ws-text) !important;
}

.wholesale-page.is-dark .wholesale-search,
.wholesale-page.is-dark .wholesale-status-filter,
.wholesale-page.is-dark .form-control {
    background-color: var(--ws-input) !important;
    color: var(--ws-text) !important;
    border-color: var(--ws-border) !important;
}

.wholesale-page.is-dark .wholesale-table td,
.wholesale-page.is-dark .wholesale-table th,
.wholesale-page.is-dark .wholesale-content,
.wholesale-page.is-dark .wholesale-content-header,
.wholesale-page.is-dark .wholesale-table-summary,
.wholesale-page.is-dark .wholesale-stat-item,
.wholesale-page.is-dark .wholesale-action,
.wholesale-page.is-dark .btn {
    border-color: var(--ws-border) !important;
}



/* ================================================================
   FINAL WHOLESALE THEME OVERRIDE
   LIGHT = completely white/light
   DARK  = completely dark
   This is intentionally placed at the end so global admin CSS cannot
   leave tables/modals/forms partially in the opposite theme.
   ================================================================ */

/* ---------- LIGHT: page ---------- */
.wholesale-page.is-light,
body:not(.wholesale-theme-dark) .wholesale-page.is-light {
    --ws-page:#ffffff !important;
    --ws-card:#ffffff !important;
    --ws-card-alt:#f7f9fb !important;
    --ws-input:#ffffff !important;
    --ws-border:#dfe5ec !important;
    --ws-border-soft:#e9edf2 !important;
    --ws-text:#162334 !important;
    --ws-body:#536579 !important;
    --ws-muted:#7b8da1 !important;
    background:#ffffff !important;
    color:#536579 !important;
}

.wholesale-page.is-light,
.wholesale-page.is-light .wholesale-content,
.wholesale-page.is-light .wholesale-stat-item,
.wholesale-page.is-light .wholesale-table,
.wholesale-page.is-light .wholesale-table thead,
.wholesale-page.is-light .wholesale-table tbody,
.wholesale-page.is-light .wholesale-table tr,
.wholesale-page.is-light .wholesale-table td,
.wholesale-page.is-light .wholesale-table th,
.wholesale-page.is-light .wholesale-content-header,
.wholesale-page.is-light .wholesale-table-summary,
.wholesale-page.is-light .wholesale-action,
.wholesale-page.is-light .btn {
    background-color:#ffffff !important;
    color:#536579 !important;
    border-color:#dfe5ec !important;
}

.wholesale-page.is-light .wholesale-table th,
.wholesale-page.is-light .wholesale-content-header,
.wholesale-page.is-light .wholesale-table-summary {
    background-color:#f7f9fb !important;
}

.wholesale-page.is-light .wholesale-page-header,
.wholesale-page.is-light .wholesale-content,
.wholesale-page.is-light .wholesale-stat-item,
.wholesale-page.is-light .wholesale-content-header,
.wholesale-page.is-light .wholesale-table-summary,
.wholesale-page.is-light .wholesale-table th,
.wholesale-page.is-light .wholesale-table td,
.wholesale-page.is-light .wholesale-action,
.wholesale-page.is-light .btn,
.wholesale-page.is-light .wholesale-search,
.wholesale-page.is-light .wholesale-status-filter {
    border-color:#dfe5ec !important;
}

.wholesale-page.is-light .wholesale-page-header h1,
.wholesale-page.is-light .wholesale-content-title h2,
.wholesale-page.is-light .wholesale-stat-value,
.wholesale-page.is-light .client-company,
.wholesale-page.is-light .credit-value,
.wholesale-page.is-light .orders-value,
.wholesale-page.is-light .btn,
.wholesale-page.is-light .wholesale-action {
    color:#162334 !important;
}

.wholesale-page.is-light .wholesale-table td,
.wholesale-page.is-light .client-name {
    color:#536579 !important;
}

.wholesale-page.is-light .wholesale-search,
.wholesale-page.is-light .wholesale-status-filter,
.wholesale-page.is-light .form-control {
    background:#ffffff !important;
    color:#162334 !important;
    border-color:#dfe5ec !important;
}

.wholesale-page.is-light .wholesale-search::placeholder,
.wholesale-page.is-light .client-email,
.wholesale-page.is-light .client-tax,
.wholesale-page.is-light .spent-value,
.wholesale-page.is-light .wholesale-muted,
.wholesale-page.is-light .wholesale-result-text,
.wholesale-page.is-light .wholesale-stat-label,
.wholesale-page.is-light .wholesale-content-title p,
.wholesale-page.is-light .wholesale-page-header p {
    color:#7b8da1 !important;
}

.wholesale-page.is-light .wholesale-table tbody tr:hover,
.wholesale-page.is-light .wholesale-action:hover,
.wholesale-page.is-light .btn:hover {
    background:#ecfdf5 !important;
}

/* ---------- DARK: page ---------- */
body.wholesale-theme-dark .wholesale-page,
html.wholesale-theme-dark .wholesale-page,
.wholesale-page.is-dark {
    --ws-page:#080f17 !important;
    --ws-card:#101923 !important;
    --ws-card-alt:#0d151e !important;
    --ws-input:#0b131c !important;
    --ws-border:#223244 !important;
    --ws-border-soft:#1a2938 !important;
    --ws-text:#f1f5f9 !important;
    --ws-body:#aab8c7 !important;
    --ws-muted:#73869a !important;
    background:#080f17 !important;
    color:#aab8c7 !important;
}

body.wholesale-theme-dark .wholesale-page,
body.wholesale-theme-dark .wholesale-page .wholesale-content,
body.wholesale-theme-dark .wholesale-page .wholesale-stat-item,
body.wholesale-theme-dark .wholesale-page .wholesale-table,
body.wholesale-theme-dark .wholesale-page .wholesale-table thead,
body.wholesale-theme-dark .wholesale-page .wholesale-table tbody,
body.wholesale-theme-dark .wholesale-page .wholesale-table tr,
body.wholesale-theme-dark .wholesale-page .wholesale-table td,
body.wholesale-theme-dark .wholesale-page .wholesale-table th,
body.wholesale-theme-dark .wholesale-page .wholesale-content-header,
body.wholesale-theme-dark .wholesale-page .wholesale-table-summary,
body.wholesale-theme-dark .wholesale-page .wholesale-action,
body.wholesale-theme-dark .wholesale-page .btn,
.wholesale-page.is-dark,
.wholesale-page.is-dark .wholesale-content,
.wholesale-page.is-dark .wholesale-stat-item,
.wholesale-page.is-dark .wholesale-table,
.wholesale-page.is-dark .wholesale-table thead,
.wholesale-page.is-dark .wholesale-table tbody,
.wholesale-page.is-dark .wholesale-table tr,
.wholesale-page.is-dark .wholesale-table td,
.wholesale-page.is-dark .wholesale-table th,
.wholesale-page.is-dark .wholesale-content-header,
.wholesale-page.is-dark .wholesale-table-summary,
.wholesale-page.is-dark .wholesale-action,
.wholesale-page.is-dark .btn {
    background-color:#101923 !important;
    color:#aab8c7 !important;
    border-color:#223244 !important;
}

body.wholesale-theme-dark .wholesale-page .wholesale-table th,
body.wholesale-theme-dark .wholesale-page .wholesale-content-header,
body.wholesale-theme-dark .wholesale-page .wholesale-table-summary,
.wholesale-page.is-dark .wholesale-table th,
.wholesale-page.is-dark .wholesale-content-header,
.wholesale-page.is-dark .wholesale-table-summary {
    background-color:#0d151e !important;
}

body.wholesale-theme-dark .wholesale-page .wholesale-page-header,
body.wholesale-theme-dark .wholesale-page .wholesale-content,
body.wholesale-theme-dark .wholesale-page .wholesale-stat-item,
body.wholesale-theme-dark .wholesale-page .wholesale-content-header,
body.wholesale-theme-dark .wholesale-page .wholesale-table-summary,
body.wholesale-theme-dark .wholesale-page .wholesale-table th,
body.wholesale-theme-dark .wholesale-page .wholesale-table td,
body.wholesale-theme-dark .wholesale-page .wholesale-action,
body.wholesale-theme-dark .wholesale-page .btn,
body.wholesale-theme-dark .wholesale-page .wholesale-search,
body.wholesale-theme-dark .wholesale-page .wholesale-status-filter,
.wholesale-page.is-dark .wholesale-page-header,
.wholesale-page.is-dark .wholesale-content,
.wholesale-page.is-dark .wholesale-stat-item,
.wholesale-page.is-dark .wholesale-content-header,
.wholesale-page.is-dark .wholesale-table-summary,
.wholesale-page.is-dark .wholesale-table th,
.wholesale-page.is-dark .wholesale-table td,
.wholesale-page.is-dark .wholesale-action,
.wholesale-page.is-dark .btn,
.wholesale-page.is-dark .wholesale-search,
.wholesale-page.is-dark .wholesale-status-filter {
    border-color:#223244 !important;
}

body.wholesale-theme-dark .wholesale-page .wholesale-page-header h1,
body.wholesale-theme-dark .wholesale-page .wholesale-content-title h2,
body.wholesale-theme-dark .wholesale-page .wholesale-stat-value,
body.wholesale-theme-dark .wholesale-page .client-company,
body.wholesale-theme-dark .wholesale-page .credit-value,
body.wholesale-theme-dark .wholesale-page .orders-value,
body.wholesale-theme-dark .wholesale-page .btn,
body.wholesale-theme-dark .wholesale-page .wholesale-action,
.wholesale-page.is-dark .wholesale-page-header h1,
.wholesale-page.is-dark .wholesale-content-title h2,
.wholesale-page.is-dark .wholesale-stat-value,
.wholesale-page.is-dark .client-company,
.wholesale-page.is-dark .credit-value,
.wholesale-page.is-dark .orders-value,
.wholesale-page.is-dark .btn,
.wholesale-page.is-dark .wholesale-action {
    color:#f1f5f9 !important;
}

body.wholesale-theme-dark .wholesale-page .wholesale-table td,
body.wholesale-theme-dark .wholesale-page .client-name,
.wholesale-page.is-dark .wholesale-table td,
.wholesale-page.is-dark .client-name {
    color:#aab8c7 !important;
}

body.wholesale-theme-dark .wholesale-page .wholesale-search,
body.wholesale-theme-dark .wholesale-page .wholesale-status-filter,
body.wholesale-theme-dark .wholesale-page .form-control,
.wholesale-page.is-dark .wholesale-search,
.wholesale-page.is-dark .wholesale-status-filter,
.wholesale-page.is-dark .form-control {
    background:#0b131c !important;
    color:#f1f5f9 !important;
    border-color:#223244 !important;
}

body.wholesale-theme-dark .wholesale-page .wholesale-search::placeholder,
body.wholesale-theme-dark .wholesale-page .client-email,
body.wholesale-theme-dark .wholesale-page .client-tax,
body.wholesale-theme-dark .wholesale-page .spent-value,
body.wholesale-theme-dark .wholesale-page .wholesale-result-text,
body.wholesale-theme-dark .wholesale-page .wholesale-stat-label,
body.wholesale-theme-dark .wholesale-page .wholesale-content-title p,
body.wholesale-theme-dark .wholesale-page .wholesale-page-header p,
.wholesale-page.is-dark .wholesale-search::placeholder,
.wholesale-page.is-dark .client-email,
.wholesale-page.is-dark .client-tax,
.wholesale-page.is-dark .spent-value,
.wholesale-page.is-dark .wholesale-result-text,
.wholesale-page.is-dark .wholesale-stat-label,
.wholesale-page.is-dark .wholesale-content-title p,
.wholesale-page.is-dark .wholesale-page-header p {
    color:#73869a !important;
}

body.wholesale-theme-dark .wholesale-page .wholesale-table tbody tr:hover,
body.wholesale-theme-dark .wholesale-page .wholesale-action:hover,
body.wholesale-theme-dark .wholesale-page .btn:hover,
.wholesale-page.is-dark .wholesale-table tbody tr:hover,
.wholesale-page.is-dark .wholesale-action:hover,
.wholesale-page.is-dark .btn:hover {
    background:#12271f !important;
}

/* ---------- MODAL: never inherit opacity/filter from global admin CSS ---------- */
.modal-backdrop#detailsModal,
.modal-backdrop#termsModal {
    opacity:1 !important;
    filter:none !important;
    mix-blend-mode:normal !important;
    isolation:isolate !important;
    z-index:2147483000 !important;
    padding:20px !important;
}

/* Light modal */
body:not(.wholesale-theme-dark) .modal-backdrop#detailsModal,
body:not(.wholesale-theme-dark) .modal-backdrop#termsModal {
    background:rgba(15,23,42,.42) !important;
    backdrop-filter:blur(3px) !important;
}

body:not(.wholesale-theme-dark) .modal-backdrop#detailsModal .wholesale-modal,
body:not(.wholesale-theme-dark) .modal-backdrop#termsModal .wholesale-modal {
    opacity:1 !important;
    filter:none !important;
    background:#ffffff !important;
    color:#162334 !important;
    border:1px solid #dfe5ec !important;
    box-shadow:0 24px 70px rgba(15,23,42,.28) !important;
}

body:not(.wholesale-theme-dark) .modal-backdrop#detailsModal .modal-header,
body:not(.wholesale-theme-dark) .modal-backdrop#termsModal .modal-header,
body:not(.wholesale-theme-dark) .modal-backdrop#detailsModal .modal-footer,
body:not(.wholesale-theme-dark) .modal-backdrop#termsModal .modal-footer,
body:not(.wholesale-theme-dark) .modal-backdrop#detailsModal .detail-item,
body:not(.wholesale-theme-dark) .modal-backdrop#termsModal .detail-item {
    background:#f7f9fb !important;
    color:#162334 !important;
    border-color:#dfe5ec !important;
}

body:not(.wholesale-theme-dark) .modal-backdrop#detailsModal .modal-body,
body:not(.wholesale-theme-dark) .modal-backdrop#termsModal .modal-body,
body:not(.wholesale-theme-dark) .modal-backdrop#detailsModal .detail-avatar {
    background:#ffffff !important;
    color:#162334 !important;
    border-color:#dfe5ec !important;
}

body:not(.wholesale-theme-dark) .modal-backdrop#detailsModal .modal-header h3,
body:not(.wholesale-theme-dark) .modal-backdrop#termsModal .modal-header h3,
body:not(.wholesale-theme-dark) .modal-backdrop#detailsModal .detail-item .val,
body:not(.wholesale-theme-dark) .modal-backdrop#termsModal .detail-item .val {
    color:#162334 !important;
}

body:not(.wholesale-theme-dark) .modal-backdrop#detailsModal .detail-item label,
body:not(.wholesale-theme-dark) .modal-backdrop#termsModal .form-label,
body:not(.wholesale-theme-dark) .modal-backdrop#termsModal .form-help {
    color:#7b8da1 !important;
}

body:not(.wholesale-theme-dark) .modal-backdrop#termsModal .form-control {
    background:#ffffff !important;
    color:#162334 !important;
    border-color:#dfe5ec !important;
}

body:not(.wholesale-theme-dark) .modal-backdrop#detailsModal .modal-footer .btn,
body:not(.wholesale-theme-dark) .modal-backdrop#termsModal .modal-footer .btn {
    background:#ffffff !important;
    color:#162334 !important;
    border-color:#dfe5ec !important;
}

/* Dark modal */
body.wholesale-theme-dark .modal-backdrop#detailsModal,
body.wholesale-theme-dark .modal-backdrop#termsModal {
    background:rgba(0,0,0,.78) !important;
    backdrop-filter:blur(4px) !important;
}

body.wholesale-theme-dark .modal-backdrop#detailsModal .wholesale-modal,
body.wholesale-theme-dark .modal-backdrop#termsModal .wholesale-modal {
    opacity:1 !important;
    filter:none !important;
    background:#101923 !important;
    color:#f1f5f9 !important;
    border:1px solid #223244 !important;
    box-shadow:0 24px 80px rgba(0,0,0,.65) !important;
}

body.wholesale-theme-dark .modal-backdrop#detailsModal .modal-header,
body.wholesale-theme-dark .modal-backdrop#termsModal .modal-header,
body.wholesale-theme-dark .modal-backdrop#detailsModal .modal-footer,
body.wholesale-theme-dark .modal-backdrop#termsModal .modal-footer,
body.wholesale-theme-dark .modal-backdrop#detailsModal .detail-item,
body.wholesale-theme-dark .modal-backdrop#termsModal .detail-item {
    background:#0d151e !important;
    color:#f1f5f9 !important;
    border-color:#223244 !important;
}

body.wholesale-theme-dark .modal-backdrop#detailsModal .modal-body,
body.wholesale-theme-dark .modal-backdrop#termsModal .modal-body,
body.wholesale-theme-dark .modal-backdrop#detailsModal .detail-avatar {
    background:#101923 !important;
    color:#f1f5f9 !important;
    border-color:#223244 !important;
}

body.wholesale-theme-dark .modal-backdrop#detailsModal .modal-header h3,
body.wholesale-theme-dark .modal-backdrop#termsModal .modal-header h3,
body.wholesale-theme-dark .modal-backdrop#detailsModal .detail-item .val,
body.wholesale-theme-dark .modal-backdrop#termsModal .detail-item .val {
    color:#f1f5f9 !important;
}

body.wholesale-theme-dark .modal-backdrop#detailsModal .detail-item label,
body.wholesale-theme-dark .modal-backdrop#termsModal .form-label,
body.wholesale-theme-dark .modal-backdrop#termsModal .form-help {
    color:#73869a !important;
}

body.wholesale-theme-dark .modal-backdrop#termsModal .form-control {
    background:#0b131c !important;
    color:#f1f5f9 !important;
    border-color:#223244 !important;
}

body.wholesale-theme-dark .modal-backdrop#detailsModal .modal-footer .btn,
body.wholesale-theme-dark .modal-backdrop#termsModal .modal-footer .btn {
    background:#101923 !important;
    color:#f1f5f9 !important;
    border-color:#223244 !important;
}

/* Close buttons and modal text are explicitly themed. */
body.wholesale-theme-dark .modal-backdrop .modal-close { color:#aab8c7 !important; }
body:not(.wholesale-theme-dark) .modal-backdrop .modal-close { color:#7b8da1 !important; }
body.wholesale-theme-dark .modal-backdrop .modal-close:hover { color:#ef4444 !important; }
body:not(.wholesale-theme-dark) .modal-backdrop .modal-close:hover { color:#dc2626 !important; }

/* Keep the actual modal above its overlay and fully opaque. */
.modal-backdrop .wholesale-modal,
.modal-backdrop .wholesale-modal * {
    opacity:1 !important;
    filter:none !important;
    mix-blend-mode:normal !important;
}

/* Full-page body surface follows the selected theme too. */
body.wholesale-theme-dark { color-scheme:dark !important; }
body:not(.wholesale-theme-dark) { color-scheme:light !important; }


/* ================================================================
   FINAL POLISH + KEYBOARD SHORTCUTS
   ================================================================ */
.wholesale-page .key-hint,
.wholesale-modal .key-hint,
.wholesale-page .action-key {
    display:inline-flex !important;
    align-items:center;
    justify-content:center;
    min-width:22px;
    height:20px;
    padding:0 5px;
    margin-left:3px;
    border:1px solid currentColor;
    border-radius:5px;
    font:800 9px/1 ui-monospace,SFMono-Regular,Menlo,Monospace;
    letter-spacing:0;
    opacity:.78;
}

.wholesale-page .btn {
    min-height:42px;
    white-space:nowrap;
}

.wholesale-page .wholesale-search-wrap { position:relative; }
.wholesale-page .wholesale-search { padding-right:58px !important; }
.wholesale-page .search-shortcut {
    position:absolute;
    right:12px;
    top:50%;
    transform:translateY(-50%);
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:24px;
    height:22px;
    padding:0 6px;
    border:1px solid var(--ws-border);
    border-radius:5px;
    background:var(--ws-card-alt);
    color:var(--ws-muted);
    font:800 10px/1 ui-monospace,SFMono-Regular,Menlo,Monospace;
    pointer-events:none;
}

.wholesale-page .wholesale-action {
    position:relative;
    gap:4px;
    width:44px;
    height:40px;
    min-width:44px;
}
.wholesale-page .wholesale-action > span:first-child { line-height:1; }
.wholesale-page .wholesale-action .action-key {
    position:absolute;
    right:2px;
    bottom:2px;
    min-width:13px;
    width:auto;
    height:13px;
    padding:0 3px;
    margin:0;
    border-radius:3px;
    font-size:7px;
    background:var(--ws-card-alt);
    color:var(--ws-muted);
}

/* Terms modal: clean, compact, professional and never transparent. */
#termsModal .wholesale-modal {
    width:min(680px,100%) !important;
    max-height:min(88vh,760px) !important;
    border-radius:18px !important;
    overflow:hidden !important;
}
#termsModal .modal-header {
    min-height:78px;
    padding:20px 28px !important;
}
#termsModal .modal-header h3 {
    font-size:20px !important;
    line-height:1.25;
    letter-spacing:-.25px;
}
#termsModal .modal-close {
    width:auto;
    min-width:40px;
    height:40px;
    padding:0 7px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:5px;
    border:1px solid transparent;
    border-radius:9px;
    font-size:25px;
    line-height:1;
}
#termsModal .modal-close small {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:22px;
    height:18px;
    padding:0 4px;
    border:1px solid currentColor;
    border-radius:4px;
    font:800 8px/1 ui-monospace,SFMono-Regular,Menlo,Monospace;
    opacity:.72;
}
#termsModal .modal-body {
    padding:26px 28px 24px !important;
}
#termsModal .approve-summary {
    margin-bottom:20px !important;
    padding:13px 15px !important;
    border-radius:11px !important;
    font-size:12px !important;
    line-height:1.5;
}
#termsModal form > .modal-body > div[style] {
    margin-bottom:18px !important;
}
#termsModal .form-label {
    margin-bottom:7px !important;
    font-size:10px !important;
    letter-spacing:.45px;
}
#termsModal .form-control {
    height:52px !important;
    padding:0 16px !important;
    border-radius:10px !important;
    font-size:14px !important;
    font-weight:800 !important;
}
#termsModal .form-help {
    margin-top:6px !important;
    font-size:10px !important;
    line-height:1.4;
}
#termsModal .modal-footer {
    min-height:78px;
    padding:15px 28px !important;
    gap:10px !important;
}
#termsModal .modal-footer .btn {
    min-width:112px;
    height:44px;
    border-radius:10px !important;
    font-size:12px !important;
}
#termsModal .modal-footer .terms-save-btn,
#termsModal .modal-footer .terms-save-btn:hover,
#termsModal .modal-footer .terms-save-btn:focus {
    background:#059669 !important;
    border:1px solid #059669 !important;
    color:#fff !important;
    box-shadow:0 7px 18px rgba(5,150,105,.20) !important;
}
#termsModal .modal-footer .terms-save-btn .key-hint {
    color:#fff !important;
    border-color:rgba(255,255,255,.65) !important;
}

/* Light terms modal */
body:not(.wholesale-theme-dark) #termsModal .wholesale-modal {
    background:#fff !important;
    color:#162334 !important;
    border-color:#dfe5ec !important;
}
body:not(.wholesale-theme-dark) #termsModal .modal-header,
body:not(.wholesale-theme-dark) #termsModal .modal-footer {
    background:#f8fafc !important;
    border-color:#dfe5ec !important;
}
body:not(.wholesale-theme-dark) #termsModal .modal-body { background:#fff !important; }
body:not(.wholesale-theme-dark) #termsModal .modal-header h3,
body:not(.wholesale-theme-dark) #termsModal .form-label { color:#162334 !important; }
body:not(.wholesale-theme-dark) #termsModal .form-control {
    background:#fff !important;
    color:#162334 !important;
    border-color:#d8e0e8 !important;
}
body:not(.wholesale-theme-dark) #termsModal .form-control:focus {
    border-color:#059669 !important;
    box-shadow:0 0 0 3px rgba(5,150,105,.10) !important;
}

/* Dark terms modal */
body.wholesale-theme-dark #termsModal .wholesale-modal {
    background:#101923 !important;
    color:#f1f5f9 !important;
    border-color:#223244 !important;
}
body.wholesale-theme-dark #termsModal .modal-header,
body.wholesale-theme-dark #termsModal .modal-footer {
    background:#0d151e !important;
    border-color:#223244 !important;
}
body.wholesale-theme-dark #termsModal .modal-body { background:#101923 !important; }
body.wholesale-theme-dark #termsModal .modal-header h3,
body.wholesale-theme-dark #termsModal .form-label { color:#f1f5f9 !important; }
body.wholesale-theme-dark #termsModal .form-control {
    background:#0b131c !important;
    color:#f1f5f9 !important;
    border-color:#223244 !important;
}
body.wholesale-theme-dark #termsModal .form-control:focus {
    border-color:#10b981 !important;
    box-shadow:0 0 0 3px rgba(16,185,129,.12) !important;
}
body.wholesale-theme-dark #termsModal .key-hint {
    border-color:#33485d !important;
    background:#101923 !important;
    color:#aab8c7 !important;
}
body:not(.wholesale-theme-dark) #termsModal .key-hint {
    background:#f8fafc !important;
    color:#536579 !important;
    border-color:#d8e0e8 !important;
}

@media (max-width:680px) {
    #termsModal .modal-header,
    #termsModal .modal-body,
    #termsModal .modal-footer { padding-left:18px !important; padding-right:18px !important; }
    #termsModal .modal-footer { flex-wrap:wrap; }
    #termsModal .modal-footer .btn { flex:1 1 140px; }
}

</style>


<main class="main">
<section class="content">
<div class="wholesale-page">

    <div class="wholesale-page-header">
        <div>
            <div class="wholesale-breadcrumb">
                <span>Sales &amp; Marketing</span>
                <span>/</span>
                <span class="current">Wholesale</span>
            </div>
            <h1>Wholesale Clients</h1>
            <p>Manage B2B companies, approval status, discount terms, credit limits, orders and account details.</p>
        </div>

        <div class="header-actions">
            <button type="button" class="btn btn-blue" id="printBtn" data-shortcut="p" aria-keyshortcuts="P" title="Print page — P">🖨 Print <small class="key-hint">P</small></button>
            <button type="button" class="btn" id="pdfBtn" data-shortcut="v" aria-keyshortcuts="V" title="PDF — V">↓ PDF <small class="key-hint">V</small></button>
            <button type="button" class="btn" id="excelBtn" data-shortcut="x" aria-keyshortcuts="X" title="Excel — X">↓ Excel <small class="key-hint">X</small></button>
            <a href="../quotes/index.php" class="btn" id="quotesBtn" data-shortcut="q" aria-keyshortcuts="Q" title="Quotes — Q">📑 Quotes <small class="key-hint">Q</small></a>
        </div>
    </div>

    <?php if ($actionMessage !== ''): ?>
        <div class="notice notice-success"><?= e($actionMessage) ?></div>
    <?php endif; ?>

    <?php if ($actionError !== ''): ?>
        <div class="notice notice-error"><?= e($actionError) ?></div>
    <?php endif; ?>

    <?php if ($queryError !== ''): ?>
        <div class="notice notice-error"><?= e($queryError) ?></div>
    <?php endif; ?>

    <div class="wholesale-stats">
        <div class="wholesale-stat-item">
            <div class="wholesale-stat-icon stat-green">🏢</div>
            <div>
                <div class="wholesale-stat-label">Approved B2B</div>
                <div class="wholesale-stat-value"><?= $approvedCount ?></div>
            </div>
        </div>

        <div class="wholesale-stat-item">
            <div class="wholesale-stat-icon stat-amber">⏳</div>
            <div>
                <div class="wholesale-stat-label">Pending Approval</div>
                <div class="wholesale-stat-value"><?= $pendingCount ?></div>
            </div>
        </div>

        <div class="wholesale-stat-item">
            <div class="wholesale-stat-icon stat-blue">📑</div>
            <div>
                <div class="wholesale-stat-label">Active Quotes</div>
                <div class="wholesale-stat-value"><?= $activeQuotes ?></div>
            </div>
        </div>

        <div class="wholesale-stat-item">
            <div class="wholesale-stat-icon stat-purple">💬</div>
            <div>
                <div class="wholesale-stat-label">Bulk Inquiries</div>
                <div class="wholesale-stat-value"><?= $openInquiries ?></div>
            </div>
        </div>
    </div>

    <div class="wholesale-content">
        <div class="wholesale-content-header">
            <div class="wholesale-content-title">
                <h2>Wholesale Account Roster</h2>
                <p>Company, contact, tax registration, discount, credit, order history and account status.</p>
            </div>

            <div class="wholesale-filters">
                <div class="wholesale-search-wrap">
                    <span class="wholesale-search-icon">⌕</span>
                    <input type="search" id="wholesaleSearch" class="wholesale-search" placeholder="Search company, contact, email, phone, tax ID..." autocomplete="off" aria-keyshortcuts="/">
                    <span class="search-shortcut" aria-hidden="true">/</span>
                </div>

                <select id="wholesaleStatusFilter" class="wholesale-status-filter" aria-keyshortcuts="F" title="Filter status — F">
                    <option value="all">All Wholesale</option>
                    <option value="approved">Approved Only</option>
                    <option value="pending">Pending Approval</option>
                </select>
            </div>
        </div>

        <div class="wholesale-table-summary">
            <div class="wholesale-result-text">Showing <strong id="visibleCount"><?= $totalWholesale ?></strong> accounts</div>
            <div class="wholesale-result-text">Total Registered: <strong><?= $totalWholesale ?></strong></div>
        </div>

        <div class="wholesale-table-wrapper">
        <?php if (empty($allClients)): ?>
            <div class="wholesale-empty">
                <h3>No Wholesale Accounts Found</h3>
                <p>Clients with company details will appear here automatically.</p>
            </div>
        <?php else: ?>
            <table class="wholesale-table" id="wholesaleTable">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Company &amp; Contact</th>
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
                    $userId = (int)$client['UserId'];
                    $isApproved = ((int)$client['IsWholesaleApproved'] === 1);
                    $company = trim((string)($client['CompanyName'] ?? 'Individual Buyer'));
                    $name    = trim((string)($client['FullName'] ?? ''));
                    $email   = trim((string)($client['Email'] ?? ''));
                    $phone   = trim((string)($client['Phone'] ?? ''));
                    $tax     = trim((string)($client['TaxNumber'] ?? 'N/A'));
                    $discount = (float)($client['WholesaleDiscountPct'] ?? 0);
                    $credit   = (float)($client['CreditLimit'] ?? 0);
                    $orders   = (int)($client['TotalOrders'] ?? 0);
                    $spent    = (float)($client['TotalSpent'] ?? 0);
                    
                    $createdAt   = dateValue($client['CreatedAt'] ?? null);
                    $updatedAt   = dateValue($client['UpdatedAt'] ?? null);
                    $lastLoginAt = dateValue($client['LastLoginAt'] ?? null);
                    $avatarLetter = strtoupper(substr($company !== '' ? $company : $name, 0, 1));
                ?>
                    <tr class="client-row" data-id="<?= $userId ?>" data-status="<?= $isApproved ? 'approved' : 'pending' ?>" data-search="<?= e(strtolower($company . ' ' . $name . ' ' . $email . ' ' . $phone . ' ' . $tax)) ?>">
                        <td><span class="order-box"><?= $rowNo++ ?></span></td>
                        <td>
                            <div class="client-main">
                                <div class="client-avatar"><?= e($avatarLetter ?: 'W') ?></div>
                                <div>
                                    <div class="client-company"><?= e($company) ?></div>
                                    <div class="client-name"><?= e($name ?: '—') ?></div>
                                    <div class="client-email"><?= e($email ?: '—') ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div><?= e($phone ?: '—') ?></div>
                            <div class="client-tax">Tax: <?= e($tax ?: 'N/A') ?></div>
                        </td>
                        <td>
                            <?php if ($isApproved): ?>
                                <span class="wholesale-status status-approved"><span class="status-dot"></span>Approved</span>
                            <?php else: ?>
                                <span class="wholesale-status status-pending"><span class="status-dot"></span>Pending</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="discount-value"><?= number_format($discount, 1) ?>%</span></td>
                        <td><span class="credit-value">$<?= moneyValue($credit) ?></span></td>
                        <td>
                            <div class="orders-value"><?= $orders ?> Orders</div>
                            <div class="spent-value">$<?= moneyValue($spent) ?></div>
                        </td>
                        <td>
                            <div class="wholesale-actions">
                                <button type="button" class="wholesale-action detail-btn" title="View client details — D" aria-keyshortcuts="D" data-shortcut="d"
                                    data-company="<?= e($company) ?>" data-name="<?= e($name ?: '—') ?>" data-email="<?= e($email ?: '—') ?>"
                                    data-phone="<?= e($phone ?: '—') ?>" data-tax="<?= e($tax ?: 'N/A') ?>" data-status="<?= $isApproved ? 'Approved' : 'Pending' ?>"
                                    data-discount="<?= number_format($discount, 1) ?>%" data-credit="$<?= moneyValue($credit) ?>"
                                    data-orders="<?= $orders ?>" data-spent="$<?= moneyValue($spent) ?>"
                                    data-created="<?= e($createdAt) ?>" data-updated="<?= e($updatedAt) ?>" data-login="<?= e($lastLoginAt) ?>"><span>◉</span><small class="action-key">D</small></button>

                                <button type="button" class="wholesale-action edit-terms-btn" title="Edit discount and credit terms — E" aria-keyshortcuts="E" data-shortcut="e"
                                    data-id="<?= $userId ?>" data-company="<?= e($company) ?>"
                                    data-discount="<?= e((string)$discount) ?>" data-credit="<?= e((string)$credit) ?>"><span>✎</span><small class="action-key">E</small></button>

                                <?php if (!$isApproved): ?>
                                    <button type="button" class="wholesale-action wholesale-action-approve approve-btn" title="Approve wholesale client — A" aria-keyshortcuts="A" data-shortcut="a"
                                        data-id="<?= $userId ?>" data-company="<?= e($company) ?>"
                                        data-discount="<?= e((string)$discount) ?>" data-credit="<?= e((string)$credit) ?>"><span>✓</span><small class="action-key">A</small></button>
                                <?php else: ?>
                                    <form method="POST" class="revoke-form" style="display:inline">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="action" value="reject_wholesale">
                                        <input type="hidden" name="user_id" value="<?= $userId ?>">
                                        <button type="submit" class="wholesale-action wholesale-action-danger" title="Revoke wholesale approval — R" aria-keyshortcuts="R"><span>×</span><small class="action-key">R</small></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="wholesale-no-result" id="wholesaleNoResult" style="display:none">
                <h3>No matching wholesale accounts</h3>
                <p>Try changing your search text or status filter.</p>
            </div>
        <?php endif; ?>
        </div>
    </div>
</div>
</section>
</main>

<!-- DETAILS MODAL -->
<div class="modal-backdrop" id="detailsModal" aria-hidden="true">
    <div class="wholesale-modal" role="dialog" aria-modal="true">
        <div class="modal-header">
            <h3>B2B Client Complete Database Overview</h3>
            <button type="button" class="modal-close" data-close-modal="detailsModal" aria-label="Close details — Esc" title="Close — Esc">×<small>Esc</small></button>
        </div>
        <div class="modal-body">
            <div class="detail-main-layout">
                <div class="detail-avatar" id="detailAvatar">W</div>
                <div class="detail-info-grid">
                    <div class="detail-item"><label>Company Name</label><div class="val" id="detailCompany">—</div></div>
                    <div class="detail-item"><label>Contact Person</label><div class="val" id="detailName">—</div></div>
                    <div class="detail-item"><label>Email Address</label><div class="val" id="detailEmail">—</div></div>
                    <div class="detail-item"><label>Phone Number</label><div class="val" id="detailPhone">—</div></div>
                    <div class="detail-item"><label>Tax Registration</label><div class="val" id="detailTax">—</div></div>
                    <div class="detail-item"><label>Account Status</label><div class="val" id="detailStatus">—</div></div>
                    <div class="detail-item"><label>Wholesale Discount</label><div class="val" id="detailDiscount">—</div></div>
                    <div class="detail-item"><label>Credit Limit</label><div class="val" id="detailCredit">—</div></div>
                    <div class="detail-item"><label>Lifetime Orders</label><div class="val" id="detailOrders">—</div></div>
                    <div class="detail-item"><label>Total Amount Spent</label><div class="val" id="detailSpent">—</div></div>
                    <div class="detail-item"><label>Created At</label><div class="val" id="detailCreated">—</div></div>
                    <div class="detail-item"><label>Updated At</label><div class="val" id="detailUpdated">—</div></div>
                    <div class="detail-item full-width"><label>Last Login At</label><div class="val" id="detailLogin">—</div></div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn" data-close-modal="detailsModal" aria-keyshortcuts="Escape">Close <small class="key-hint">Esc</small></button>
        </div>
    </div>
</div>

<!-- TERMS MODAL (EDIT / APPROVE WORKING) -->
<div class="modal-backdrop" id="termsModal" aria-hidden="true">
    <div class="wholesale-modal" style="width:min(520px,100%);" role="dialog" aria-modal="true">
        <div class="modal-header">
            <h3 id="termsTitle">Edit Wholesale Terms</h3>
            <button type="button" class="modal-close" data-close-modal="termsModal" aria-label="Close terms — Esc" title="Close — Esc">×<small>Esc</small></button>
        </div>
        <form method="POST" id="termsForm">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" id="termsAction" value="update_terms">
                <input type="hidden" name="user_id" id="termsUserId" value="0">

                <div class="approve-summary" id="termsSummary">Update the wholesale discount and credit limit for this account.</div>

                <div style="margin-bottom:15px;">
                    <label class="form-label" for="termsDiscount">Wholesale Discount (%)</label>
                    <input type="number" name="discount_pct" id="termsDiscount" class="form-control" min="0" max="100" step="0.5" required>
                    <div class="form-help">Allowed range: 0% to 100%.</div>
                </div>

                <div style="margin-bottom:15px;">
                    <label class="form-label" for="termsCredit">Credit Limit ($)</label>
                    <input type="number" name="credit_limit" id="termsCredit" class="form-control" min="0" step="100" required>
                    <div class="form-help">Enter 0 if the client has no approved credit line.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" data-close-modal="termsModal" aria-keyshortcuts="Escape">Cancel <small class="key-hint">Esc</small></button>
                <button type="submit" class="btn btn-primary terms-save-btn" aria-keyshortcuts="Control+Enter">Save Terms <small class="key-hint">Ctrl↵</small></button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    'use strict';

    function themeIsDark() {
        const html = document.documentElement;
        const body = document.body;
        const selectors = [
            html,
            body,
            document.querySelector('[data-theme]'),
            document.querySelector('.app'),
            document.querySelector('.admin-layout'),
            document.querySelector('.dashboard-layout'),
            document.querySelector('.layout')
        ].filter(Boolean);

        return selectors.some(function (el) {
            const cls = String(el.className || '').toLowerCase();
            const dataTheme = String(el.getAttribute('data-theme') || '').toLowerCase();
            return (
                dataTheme === 'dark' ||
                cls.split(/\s+/).some(function (c) {
                    return c === 'dark' || c === 'dark-mode' || c === 'theme-dark' || c === 'is-dark';
                })
            );
        });
    }

    function syncWholesaleTheme() {
        const page = document.querySelector('.wholesale-page');
        if (!page) return;

        const dark = themeIsDark();
        const html = document.documentElement;
        const body = document.body;

        page.classList.toggle('is-dark', dark);
        page.classList.toggle('is-light', !dark);

        /* The modal elements live outside .wholesale-page, so put one
           definitive theme class on the document/body as well. */
        html.classList.toggle('wholesale-theme-dark', dark);
        body.classList.toggle('wholesale-theme-dark', dark);

        document.querySelectorAll('.modal-backdrop').forEach(function (modal) {
            modal.classList.toggle('wholesale-theme-dark', dark);
            modal.classList.toggle('wholesale-theme-light', !dark);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        syncWholesaleTheme();

        const observer = new MutationObserver(syncWholesaleTheme);
        [
            document.documentElement,
            document.body,
            document.querySelector('.app'),
            document.querySelector('.admin-layout'),
            document.querySelector('.dashboard-layout'),
            document.querySelector('.layout')
        ].filter(Boolean).forEach(function (el) {
            observer.observe(el, {
                attributes: true,
                attributeFilter: ['class', 'data-theme']
            });
        });

        document.addEventListener('themechange', syncWholesaleTheme);
        window.addEventListener('storage', syncWholesaleTheme);

        /* Some admin themes switch class names a moment after DOM ready. */
        setTimeout(syncWholesaleTheme, 0);
        setTimeout(syncWholesaleTheme, 150);
        setTimeout(syncWholesaleTheme, 500);
    });
})();
</script>

<script>
(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        const detailsModal = document.getElementById('detailsModal');
        const termsModal = document.getElementById('termsModal');

        function openModal(modal) {
            if (!modal) return;
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modal) {
            if (!modal) return;
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        document.querySelectorAll('[data-close-modal]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                closeModal(document.getElementById(this.dataset.closeModal));
            });
        });

        // Details Modal functionality
        document.querySelectorAll('.detail-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                document.getElementById('detailCompany').textContent = this.dataset.company || '—';
                document.getElementById('detailName').textContent = this.dataset.name || '—';
                document.getElementById('detailEmail').textContent = this.dataset.email || '—';
                document.getElementById('detailPhone').textContent = this.dataset.phone || '—';
                document.getElementById('detailTax').textContent = this.dataset.tax || '—';
                document.getElementById('detailStatus').textContent = this.dataset.status || '—';
                document.getElementById('detailDiscount').textContent = this.dataset.discount || '0%';
                document.getElementById('detailCredit').textContent = this.dataset.credit || '$0';
                document.getElementById('detailOrders').textContent = this.dataset.orders || '0';
                document.getElementById('detailSpent').textContent = this.dataset.spent || '$0';
                document.getElementById('detailCreated').textContent = this.dataset.created || '—';
                document.getElementById('detailUpdated').textContent = this.dataset.updated || '—';
                document.getElementById('detailLogin').textContent = this.dataset.login || '—';

                const avatarText = (this.dataset.company || this.dataset.name || 'W').trim().charAt(0).toUpperCase();
                document.getElementById('detailAvatar').textContent = avatarText || 'W';
                openModal(detailsModal);
            });
        });

        // Edit terms & Approve modal functionality
        function openTermsModal(btn, approveMode) {
            const id = btn.dataset.id || '0';
            const company = btn.dataset.company || 'Wholesale Client';
            const currentDiscount = parseFloat(btn.dataset.discount || '0');
            const currentCredit = parseFloat(btn.dataset.credit || '0');

            document.getElementById('termsUserId').value = id;

            if (approveMode) {
                document.getElementById('termsTitle').textContent = 'Approve: ' + company;
                document.getElementById('termsAction').value = 'approve_wholesale';
                document.getElementById('termsDiscount').value = currentDiscount > 0 ? currentDiscount : 15;
                document.getElementById('termsCredit').value = currentCredit > 0 ? currentCredit : 5000;
                document.getElementById('termsSummary').textContent = 'Approve this company as a wholesale client and set its initial B2B pricing terms.';
            } else {
                document.getElementById('termsTitle').textContent = 'Edit Terms: ' + company;
                document.getElementById('termsAction').value = 'update_terms';
                document.getElementById('termsDiscount').value = currentDiscount;
                document.getElementById('termsCredit').value = currentCredit;
                document.getElementById('termsSummary').textContent = 'Update the wholesale discount and approved credit limit for this account.';
            }
            openModal(termsModal);
        }

        document.querySelectorAll('.edit-terms-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                openTermsModal(this, false);
            });
        });

        document.querySelectorAll('.approve-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                openTermsModal(this, true);
            });
        });
    });
})();
</script>


<script>
(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        const search = document.getElementById('wholesaleSearch');
        const filter = document.getElementById('wholesaleStatusFilter');
        const termsModal = document.getElementById('termsModal');
        const detailsModal = document.getElementById('detailsModal');

        function visibleRows() {
            return Array.from(document.querySelectorAll('#wholesaleTable tbody .client-row')).filter(function (row) {
                return row.style.display !== 'none';
            });
        }
        function activeRow() {
            const hovered = document.querySelector('#wholesaleTable tbody .client-row:hover');
            if (hovered && hovered.style.display !== 'none') return hovered;
            const focused = document.activeElement && document.activeElement.closest ? document.activeElement.closest('.client-row') : null;
            if (focused && focused.style.display !== 'none') return focused;
            return visibleRows()[0] || null;
        }
        function clickInRow(row, selector) {
            if (!row) return;
            const btn = row.querySelector(selector);
            if (btn) btn.click();
        }
        function modalOpen(modal) {
            return modal && modal.classList.contains('show');
        }

        document.addEventListener('keydown', function (event) {
            const key = String(event.key || '').toLowerCase();
            const typing = ['INPUT','TEXTAREA','SELECT'].includes(document.activeElement && document.activeElement.tagName);

            if (key === 'escape') {
                const open = document.querySelector('.modal-backdrop.show');
                if (open) {
                    const close = open.querySelector('[data-close-modal]');
                    if (close) close.click();
                    event.preventDefault();
                }
                return;
            }

            if (modalOpen(termsModal) && event.ctrlKey && key === 'enter') {
                const form = document.getElementById('termsForm');
                if (form) form.requestSubmit();
                event.preventDefault();
                return;
            }

            if (typing && key !== '/') return;
            if (event.ctrlKey || event.altKey || event.metaKey) return;

            if (key === '/') {
                if (search) {
                    search.focus();
                    search.select();
                    event.preventDefault();
                }
                return;
            }
            if (key === 'f') {
                if (filter) {
                    filter.focus();
                    event.preventDefault();
                }
                return;
            }
            if (key === 'p') {
                const btn = document.getElementById('printBtn');
                if (btn) btn.click();
                event.preventDefault();
                return;
            }
            if (key === 'v') {
                const btn = document.getElementById('pdfBtn');
                if (btn) btn.click();
                event.preventDefault();
                return;
            }
            if (key === 'x') {
                const btn = document.getElementById('excelBtn');
                if (btn) btn.click();
                event.preventDefault();
                return;
            }
            if (key === 'q') {
                const btn = document.getElementById('quotesBtn');
                if (btn) btn.click();
                event.preventDefault();
                return;
            }
            if (key === 'd') { clickInRow(activeRow(), '.detail-btn'); event.preventDefault(); return; }
            if (key === 'e') { clickInRow(activeRow(), '.edit-terms-btn'); event.preventDefault(); return; }
            if (key === 'a') { clickInRow(activeRow(), '.approve-btn'); event.preventDefault(); return; }
            if (key === 'r') {
                const row = activeRow();
                const revoke = row && row.querySelector('.revoke-form button');
                if (revoke) {
                    if (window.confirm('Revoke wholesale approval for this client?')) revoke.click();
                }
                event.preventDefault();
            }
        });

        /* Make every table row keyboard-focusable so D/E/A/R can be used predictably. */
        document.querySelectorAll('#wholesaleTable tbody .client-row').forEach(function (row) {
            row.setAttribute('tabindex', '0');
        });
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
