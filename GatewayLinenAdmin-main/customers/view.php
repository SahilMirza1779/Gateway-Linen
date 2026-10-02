<?php
session_start();

/*
|--------------------------------------------------------------------------
| GatewayLinen Admin - View Customer Details
|--------------------------------------------------------------------------
*/

if (empty($_SESSION["admin_id"])) {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$activeMenu = "customers";
$pageTitle  = "GatewayLinen | Customer Details";

if (!isset($_SESSION["admin_name"])) {
    $_SESSION["admin_name"] = $_SESSION["admin_username"] ?? "GatewayLinen Administrator";
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$customer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($customer_id === 0) {
    echo "<div style='color: var(--text-hi); padding: 40px; text-align: center;'>Invalid Customer ID.</div>";
    exit;
}

/* 
|--------------------------------------------------------------------------
| FETCH REAL CUSTOMER DATA FROM DATABASE
|--------------------------------------------------------------------------
*/

$userSql = "SELECT FullName, Email, Phone, CompanyName, IsActive, CreatedAt FROM dbo.Users WHERE UserId = ?";
$userStmt = sqlsrv_query($conn, $userSql, [$customer_id]);

if ($userStmt === false || !($userRow = sqlsrv_fetch_array($userStmt, SQLSRV_FETCH_ASSOC))) {
    echo "<div style='color: var(--text-hi); padding: 40px; text-align: center;'>Customer not found in database!</div>";
    exit;
}

$statsSql = "SELECT COUNT(OrderId) AS TotalOrders, SUM(FinalTotal) AS TotalSpent FROM dbo.Orders WHERE UserId = ?";
$statsStmt = sqlsrv_query($conn, $statsSql, [$customer_id]);
$totalOrders = 0;
$totalSpent = 0.00;

if ($statsStmt !== false && $statsRow = sqlsrv_fetch_array($statsStmt, SQLSRV_FETCH_ASSOC)) {
    $totalOrders = (int)$statsRow['TotalOrders'];
    $totalSpent = (float)$statsRow['TotalSpent'];
}

$addrSql = "SELECT TOP 1 AddressLine1, City, StateProvince, PostalCode FROM dbo.UserAddresses WHERE UserId = ? ORDER BY AddressId DESC";
$addrStmt = sqlsrv_query($conn, $addrSql, [$customer_id]);
$addressString = "No address on file.";

if ($addrStmt !== false && $addrRow = sqlsrv_fetch_array($addrStmt, SQLSRV_FETCH_ASSOC)) {
    $parts = array_filter([$addrRow['AddressLine1'], $addrRow['City'], $addrRow['StateProvince'], $addrRow['PostalCode']]);
    if (!empty($parts)) {
        $addressString = implode(", ", $parts);
    }
}

$customer = [
    'name' => !empty($userRow['FullName']) ? $userRow['FullName'] : 'Unknown User',
    'email' => $userRow['Email'],
    'phone' => !empty($userRow['Phone']) ? $userRow['Phone'] : 'N/A',
    'company' => !empty($userRow['CompanyName']) ? $userRow['CompanyName'] : 'N/A',
    'status' => (!isset($userRow['IsActive']) || $userRow['IsActive'] == 1) ? 'Active' : 'Blocked',
    'registered_on' => (isset($userRow['CreatedAt']) && is_object($userRow['CreatedAt'])) ? $userRow['CreatedAt']->format('d M Y, h:i A') : 'N/A',
    'address' => $addressString,
    'total_orders' => $totalOrders,
    'total_spent' => number_format($totalSpent, 2)
];

require_once __DIR__ . "/../includes/header.php";
require_once __DIR__ . "/../includes/sidebar.php";
?>

<style>
    :root {
        --bg-page: #f8fafc;
        --bg-card: #ffffff;
        --bg-card-alt: #f1f5f9;
        --bg-input: #ffffff;
        --border: #e2e8f0;
        --border-soft: #edf2f7;
        --text-hi: #0f172a;
        --text-body: #475569;
        --text-mute: #64748b;
        --green: #10b981;
        --green-soft: rgba(16, 185, 129, .12);
        --blue: #3b82f6;
        --blue-soft: rgba(59, 130, 246, .12);
        --red: #ef4444;
        --red-soft: rgba(239, 68, 68, .12);
        --radius: 10px;
    }

    [data-theme="dark"] body, body.dark-mode, .dark-theme {
        --bg-page: #0a1119;
        --bg-card: #111b26;
        --bg-card-alt: #0f1823;
        --bg-input: #0d1620;
        --border: #1e2d3d;
        --border-soft: #182636;
        --text-hi: #f0f4f8;
        --text-body: #a8b8c8;
        --text-mute: #5f7488;
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

    .table-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        padding: 24px;
    }

    .top-section {
        display: flex;
        gap: 24px;
        margin-bottom: 24px;
    }

    @media (max-width: 768px) {
        .top-section {
            flex-direction: column;
        }
    }

    .avatar-box {
        width: 140px;
        height: 140px;
        background: var(--bg-card-alt);
        border: 1px solid var(--border);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 52px;
        color: var(--green);
        font-weight: 800;
        flex-shrink: 0;
    }

    .info-container {
        flex: 1;
    }

    .main-label {
        font-size: 10.5px;
        text-transform: uppercase;
        color: var(--text-mute);
        font-weight: 700;
        letter-spacing: 0.6px;
        margin-bottom: 4px;
    }

    .customer-name-lg {
        font-size: 22px;
        color: var(--text-hi);
        font-weight: 800;
        margin-bottom: 16px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 14px;
    }

    .info-box {
        background: var(--bg-card-alt);
        padding: 14px 16px;
        border-radius: 8px;
        border: 1px solid var(--border);
    }

    .info-box .val {
        font-size: 13px;
        color: var(--text-hi);
        font-weight: 600;
        margin-top: 4px;
    }

    .val.status-active {
        color: var(--green);
        font-weight: 700;
    }
    .val.status-inactive {
        color: var(--red);
        font-weight: 700;
    }

    .section-divider {
        font-size: 10.5px;
        text-transform: uppercase;
        color: var(--text-mute);
        font-weight: 700;
        letter-spacing: 0.6px;
        margin-top: 28px;
        margin-bottom: 12px;
        border-bottom: 1px solid var(--border);
        padding-bottom: 6px;
    }

    .text-block {
        font-size: 13px;
        color: var(--text-hi);
        line-height: 1.6;
        background: var(--bg-card-alt);
        padding: 18px;
        border-radius: 8px;
        border: 1px solid var(--border);
    }

    .card-footer-actions {
        margin-top: 24px;
        display: flex;
        justify-content: flex-end;
    }

    .btn-secondary {
        background: var(--bg-card);
        color: var(--text-body);
        border: 1px solid var(--border);
        padding: 9px 16px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        transition: .18s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-secondary small {
        background: var(--bg-card-alt);
        border: 1px solid var(--border);
        border-radius: 4px;
        padding: 1px 5px;
        font-size: 9px;
        color: var(--text-mute);
    }
    .btn-secondary:hover {
        border-color: var(--green);
        background: var(--green-soft);
        color: var(--green);
    }

    .shortcut-help-box {
        margin-top: 20px;
        padding: 16px 20px;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: var(--bg-card);
    }
    .shortcut-help-box.hidden { display: none; }
    .shortcut-help-title {
        display: flex; align-items: center; gap: 9px; margin-bottom: 12px; color: var(--text-hi); font-size: 13px; font-weight: 700;
    }
    .shortcut-help-title small { margin-left: auto; color: var(--text-mute); font: 600 10px monospace; }
    .shortcut-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
    .shortcut-item {
        display: flex; align-items: center; gap: 9px; padding: 8px 10px; border: 1px solid var(--border-soft); border-radius: 8px; background: var(--bg-card-alt);
    }
    .shortcut-key {
        display: inline-flex; align-items: center; justify-content: center; min-width: 32px; height: 24px; padding: 0 6px; border-radius: 5px;
        background: var(--bg-card); border: 1px solid var(--border); color: var(--green); font: 800 10px monospace;
    }
    .shortcut-desc { color: var(--text-body); font-size: 11.5px; font-weight: 600; }
</style>

<main class="main">
    <section class="content">
        <div class="customers-page">

            <div class="page-header">
                <div>
                    <div class="breadcrumb">
                        <span>Management</span>
                        <span>/</span>
                        <span>Customers</span>
                        <span>/</span>
                        <span class="current">Details</span>
                    </div>
                    <h1 class="page-title">Customer Profile Details</h1>
                    <p class="page-subtitle">Review full customer information, account analytics, and recent activity.</p>
                </div>

                <div class="header-actions">
                    <a href="index.php" class="btn-secondary" id="backBtn">← Back to Customers <small>B</small></a>
                </div>
            </div>

            <div class="table-card">
                <div class="top-section">
                    <div class="avatar-box">
                        <?= strtoupper(substr($customer['name'], 0, 1)) ?>
                    </div>

                    <div class="info-container">
                        <div class="main-label">Customer Name</div>
                        <div class="customer-name-lg"><?= e($customer['name']) ?></div>

                        <div class="info-grid">
                            <div class="info-box">
                                <div class="main-label">Email Address</div>
                                <div class="val"><?= e($customer['email']) ?></div>
                            </div>
                            <div class="info-box">
                                <div class="main-label">Phone Number</div>
                                <div class="val"><?= e($customer['phone']) ?></div>
                            </div>
                            <div class="info-box">
                                <div class="main-label">Company</div>
                                <div class="val"><?= e($customer['company']) ?></div>
                            </div>
                            <div class="info-box">
                                <div class="main-label">Status</div>
                                <div class="val <?= strtolower($customer['status']) === 'active' ? 'status-active' : 'status-inactive' ?>">
                                    <?= e($customer['status']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="section-divider">Analytics & Lifetime Value</div>
                <div class="info-grid" style="grid-template-columns: repeat(2, 1fr);">
                    <div class="info-box">
                        <div class="main-label">Total Orders</div>
                        <div class="val" style="font-size: 16px;"><?= e($customer['total_orders']) ?></div>
                    </div>
                    <div class="info-box">
                        <div class="main-label">Total Amount Spent</div>
                        <div class="val" style="font-size: 16px; color: var(--blue);">CAD $<?= e($customer['total_spent']) ?></div>
                    </div>
                </div>

                <div class="section-divider">Address & Registration Info</div>
                <div class="text-block">
                    <div style="margin-bottom: 8px;">
                        <span style="color: var(--text-mute); font-size: 11px; text-transform: uppercase; font-weight: 700; margin-right: 8px;">Address:</span>
                        <?= e($customer['address']) ?>
                    </div>
                    <div>
                        <span style="color: var(--text-mute); font-size: 11px; text-transform: uppercase; font-weight: 700; margin-right: 8px;">Registered On:</span>
                        <?= e($customer['registered_on']) ?>
                    </div>
                </div>

                <div class="card-footer-actions">
                    <a href="index.php" class="btn-secondary">Back to Customers List</a>
                </div>
            </div>

            <div class="shortcut-help-box" id="shortcutHelpBox">
                <div class="shortcut-help-title">
                    <span>⌨</span>
                    <span>Keyboard Shortcuts</span>
                    <small>B • H • Esc</small>
                </div>
                <div class="shortcut-grid">
                    <div class="shortcut-item"><span class="shortcut-key">B</span><span class="shortcut-desc">Back to customers</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">H</span><span class="shortcut-desc">Toggle shortcuts help</span></div>
                </div>
            </div>

        </div>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const shortcutBox = document.getElementById('shortcutHelpBox');

        // Sync theme with system/admin header toggle if available
        if (localStorage.getItem('theme') === 'dark' || document.body.classList.contains('dark-mode')) {
            document.documentElement.setAttribute('data-theme', 'dark');
        }

        document.addEventListener('keydown', function(e) {
            const tag = (e.target?.tagName || '').toLowerCase();
            const typing = tag === 'input' || tag === 'textarea' || tag === 'select' || e.target?.isContentEditable;
            const key = (e.key || '').toUpperCase();

            if (!typing) {
                if (key === 'B') {
                    e.preventDefault();
                    window.location.href = 'index.php';
                } else if (key === 'H') {
                    e.preventDefault();
                    shortcutBox?.classList.toggle('hidden');
                }
            }

            if (e.key === 'Escape') {
                window.location.href = 'index.php';
            }
        }, true);
    });
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>