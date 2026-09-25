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
    echo "<div class='text-white p-6'>Invalid Customer ID.</div>";
    exit;
}

/* 
|--------------------------------------------------------------------------
| FETCH REAL CUSTOMER DATA FROM DATABASE
|--------------------------------------------------------------------------
*/

// 1. Fetch Basic User Info
$userSql = "SELECT FullName, Email, Phone, CompanyName, IsActive, CreatedAt FROM dbo.Users WHERE UserId = ?";
$userStmt = sqlsrv_query($conn, $userSql, [$customer_id]);

if ($userStmt === false || !($userRow = sqlsrv_fetch_array($userStmt, SQLSRV_FETCH_ASSOC))) {
    echo "<div class='text-white p-6'>Customer not found in database!</div>";
    exit;
}

// 2. Fetch Order Statistics (Total Orders & Total Spent)
$statsSql = "SELECT COUNT(OrderId) AS TotalOrders, SUM(FinalTotal) AS TotalSpent FROM dbo.Orders WHERE UserId = ?";
$statsStmt = sqlsrv_query($conn, $statsSql, [$customer_id]);
$totalOrders = 0;
$totalSpent = 0.00;

if ($statsStmt !== false && $statsRow = sqlsrv_fetch_array($statsStmt, SQLSRV_FETCH_ASSOC)) {
    $totalOrders = (int)$statsRow['TotalOrders'];
    $totalSpent = (float)$statsRow['TotalSpent'];
}

// 3. Fetch Primary/Latest Address from UserAddresses
$addrSql = "SELECT TOP 1 AddressLine1, City, StateProvince, PostalCode FROM dbo.UserAddresses WHERE UserId = ? ORDER BY AddressId DESC";
$addrStmt = sqlsrv_query($conn, $addrSql, [$customer_id]);
$addressString = "No address on file.";

if ($addrStmt !== false && $addrRow = sqlsrv_fetch_array($addrStmt, SQLSRV_FETCH_ASSOC)) {
    $parts = array_filter([$addrRow['AddressLine1'], $addrRow['City'], $addrRow['StateProvince'], $addrRow['PostalCode']]);
    if (!empty($parts)) {
        $addressString = implode(", ", $parts);
    }
}

// Map the real data to the $customer array for the UI
$customer = [
    'name' => !empty($userRow['FullName']) ? $userRow['FullName'] : 'Unknown User',
    'email' => $userRow['Email'],
    'phone' => !empty($userRow['Phone']) ? $userRow['Phone'] : 'N/A',
    'company' => !empty($userRow['CompanyName']) ? $userRow['CompanyName'] : 'N/A',
    'status' => (!isset($userRow['IsActive']) || $userRow['IsActive'] == 1) ? 'Active' : 'Inactive',
    'registered_on' => (isset($userRow['CreatedAt']) && is_object($userRow['CreatedAt'])) ? $userRow['CreatedAt']->format('d M Y, h:i A') : 'N/A',
    'address' => $addressString,
    'total_orders' => $totalOrders,
    'total_spent' => number_format($totalSpent, 2)
];

require_once __DIR__ . "/../includes/header.php";
require_once __DIR__ . "/../includes/sidebar.php";
?>

<style>
    /* Theme matching Product Details Modal */
    :root {
        --bg-page: #0a1119;
        --bg-modal-card: #161f2e;
        /* Darker slate matching product modal */
        --bg-input-box: #0f1724;
        --border-color: #2a374a;
        --text-hi: #ffffff;
        --text-body: #94a3b8;
        --text-label: #64748b;
        --green: #10b981;
        --blue: #3b82f6;
    }

    html,
    body {
        background: var(--bg-page) !important;
        color: var(--text-body) !important;
        font-family: sans-serif;
    }

    .main,
    .content {
        background: var(--bg-page) !important;
    }

    /* Modal / Card Wrapper */
    .view-wrapper {
        padding: 40px 20px;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        min-height: calc(100vh - 80px);
    }

    .details-card {
        width: 100%;
        max-width: 850px;
        background: var(--bg-modal-card);
        border-radius: 12px;
        border: 1px solid var(--border-color);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        overflow: hidden;
    }

    /* Header */
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 24px;
        border-bottom: 1px solid var(--border-color);
    }

    .card-title {
        color: var(--text-hi);
        font-size: 16px;
        font-weight: 700;
        margin: 0;
    }

    .btn-close {
        color: var(--text-label);
        font-size: 20px;
        text-decoration: none;
        transition: 0.2s ease;
        line-height: 1;
    }

    .btn-close:hover {
        color: var(--text-hi);
    }

    /* Body */
    .card-body {
        padding: 24px;
    }

    /* Top Section (Avatar + Key Info) */
    .top-section {
        display: flex;
        gap: 24px;
        margin-bottom: 30px;
    }

    @media (max-width: 768px) {
        .top-section {
            flex-direction: column;
        }
    }

    .avatar-box {
        width: 160px;
        height: 160px;
        background: var(--bg-input-box);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 64px;
        color: var(--green);
        font-weight: bold;
        flex-shrink: 0;
        box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.2);
    }

    .info-container {
        flex: 1;
    }

    .main-label {
        font-size: 10px;
        text-transform: uppercase;
        color: var(--text-label);
        font-weight: 800;
        letter-spacing: 0.8px;
        margin-bottom: 4px;
    }

    .customer-name-lg {
        font-size: 20px;
        color: var(--text-hi);
        font-weight: 700;
        margin-bottom: 16px;
    }

    /* Info Grid matching Product Modal */
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
    }

    .info-box {
        background: var(--bg-input-box);
        padding: 12px 16px;
        border-radius: 8px;
        border: 1px solid var(--border-color);
    }

    .info-box .val {
        font-size: 13px;
        color: var(--text-hi);
        font-weight: 600;
        margin-top: 4px;
    }

    .val.status-active {
        color: var(--green);
    }

    /* Divider Sections */
    .section-divider {
        font-size: 10px;
        text-transform: uppercase;
        color: var(--text-label);
        font-weight: 800;
        letter-spacing: 0.8px;
        margin-top: 24px;
        margin-bottom: 12px;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 6px;
    }

    .text-block {
        font-size: 13px;
        color: var(--text-hi);
        line-height: 1.5;
        background: var(--bg-input-box);
        padding: 16px;
        border-radius: 8px;
        border: 1px solid var(--border-color);
    }

    /* Footer */
    .card-footer {
        padding: 16px 24px;
        border-top: 1px solid var(--border-color);
        display: flex;
        justify-content: flex-end;
        background: rgba(0, 0, 0, 0.1);
    }

    .btn-secondary {
        background: var(--bg-input-box);
        color: var(--text-hi);
        border: 1px solid var(--border-color);
        padding: 8px 20px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: 0.2s ease;
    }

    .btn-secondary:hover {
        background: #1e293b;
        border-color: #334155;
    }
</style>

<main class="main">
    <section class="content">
        <div class="view-wrapper">

            <!-- Details Card (Modal Style) -->
            <div class="details-card">
                <div class="card-header">
                    <h2 class="card-title">Customer Details</h2>
                    <a href="index.php" class="btn-close" title="Close">&#10005;</a>
                </div>

                <div class="card-body">

                    <!-- Top Section -->
                    <div class="top-section">
                        <!-- Avatar / Image Box -->
                        <div class="avatar-box">
                            <?= strtoupper(substr($customer['name'], 0, 1)) ?>
                        </div>

                        <!-- Details Grid -->
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
                                    <div class="val <?= strtolower($customer['status']) === 'active' ? 'status-active' : '' ?>">
                                        <?= e($customer['status']) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Analytics & Value -->
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

                    <!-- Additional Details -->
                    <div class="section-divider">Address & Registration Info</div>
                    <div class="text-block">
                        <div style="margin-bottom: 8px;">
                            <span style="color: var(--text-label); font-size: 11px; text-transform: uppercase; font-weight: bold; margin-right: 8px;">Address:</span>
                            <?= e($customer['address']) ?>
                        </div>
                        <div>
                            <span style="color: var(--text-label); font-size: 11px; text-transform: uppercase; font-weight: bold; margin-right: 8px;">Registered On:</span>
                            <?= e($customer['registered_on']) ?>
                        </div>
                    </div>

                </div>

                <div class="card-footer">
                    <a href="index.php" class="btn-secondary">Close</a>
                </div>
            </div>

        </div>
    </section>
</main>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>