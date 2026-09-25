<?php
session_start();

/*
|--------------------------------------------------------------------------
| GatewayLinen Admin - Customers List
|--------------------------------------------------------------------------
*/

if (empty($_SESSION["admin_id"])) {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$activeMenu = "customers";
$pageTitle  = "GatewayLinen | Customers Management";

if (!isset($_SESSION["admin_name"])) {
    $_SESSION["admin_name"] = $_SESSION["admin_username"] ?? "GatewayLinen Administrator";
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

/*
|--------------------------------------------------------------------------
| FETCH CUSTOMERS (USERS)
|--------------------------------------------------------------------------
*/
$customers = [];
// Assuming RoleId IS NULL or a specific value defines a customer vs admin.
// Adjust the WHERE clause if your logic differs.
$sql = "
    SELECT 
        UserId, 
        FullName, 
        Email, 
        Phone, 
        CompanyName, 
        IsActive, 
        CreatedAt 
    FROM dbo.Users 
    WHERE RoleId IS NULL OR RoleId != 1 
    ORDER BY CreatedAt DESC
";

$stmt = sqlsrv_query($conn, $sql);

if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $customers[] = $row;
    }
    sqlsrv_free_stmt($stmt);
}

require_once __DIR__ . "/../includes/header.php";
require_once __DIR__ . "/../includes/sidebar.php";
?>

<style>
    :root {
        --bg-page: #0a1119;
        --bg-card: #111b26;
        --bg-card-alt: #0f1823;
        --bg-input: #0d1620;
        --bg-hover: #16222e;
        --border: #1e2d3d;
        --border-soft: #182636;
        --text-hi: #f0f4f8;
        --text-body: #a8b8c8;
        --text-mute: #5f7488;
        --green: #10b981;
        --green-soft: rgba(16, 185, 129, .12);
        --blue: #3b82f6;
        --blue-soft: rgba(59, 130, 246, .12);
        --red: #ef4444;
        --red-soft: rgba(239, 68, 68, .12);
    }

    html,
    body {
        background: var(--bg-page) !important;
        color: var(--text-body) !important;
    }

    .main,
    .content {
        background: var(--bg-page) !important;
    }

    .customers-page {
        width: 100%;
        max-width: 1280px;
        margin: 0 auto;
        padding: 20px 20px 45px;
        box-sizing: border-box;
    }

    .page-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        margin-bottom: 24px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--border);
    }

    .page-title {
        margin: 0;
        color: var(--text-hi);
        font-size: 27px;
        line-height: 1.2;
        font-weight: 800;
    }

    .page-subtitle {
        margin: 7px 0 0;
        color: var(--text-mute);
        font-size: 12px;
    }

    .table-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 15px 40px rgba(0, 0, 0, .18);
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .data-table th {
        background: var(--bg-card-alt);
        color: var(--text-mute);
        font-size: 10.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .6px;
        padding: 14px 18px;
        border-bottom: 1px solid var(--border);
    }

    .data-table td {
        padding: 14px 18px;
        border-bottom: 1px solid var(--border-soft);
        font-size: 12px;
        color: var(--text-body);
        vertical-align: middle;
    }

    .data-table tr:hover td {
        background: var(--bg-hover);
    }

    .customer-name {
        color: var(--text-hi);
        font-weight: 700;
        font-size: 13px;
    }

    .customer-email {
        font-size: 10.5px;
        color: var(--text-mute);
    }

    .badge {
        display: inline-flex;
        align-items: center;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .badge-active {
        background: var(--green-soft);
        color: var(--green);
        border: 1px solid rgba(16, 185, 129, .2);
    }

    .badge-inactive {
        background: var(--red-soft);
        color: var(--red);
        border: 1px solid rgba(239, 68, 68, .2);
    }

    .action-links {
        display: flex;
        gap: 8px;
    }

    .btn-action {
        padding: 6px 12px;
        border-radius: 7px;
        font-size: 11px;
        font-weight: 700;
        text-decoration: none;
        border: 1px solid var(--border);
        background: var(--bg-input);
        color: var(--text-body);
        transition: .15s ease;
    }

    .btn-action:hover {
        border-color: var(--blue);
        color: var(--blue);
        background: var(--blue-soft);
    }

    .empty-state {
        text-align: center;
        padding: 50px 20px;
        color: var(--text-mute);
    }
</style>

<main class="main">
    <section class="content">
        <div class="customers-page">

            <div class="page-header">
                <div>
                    <h1 class="page-title">Customers Management</h1>
                    <p class="page-subtitle">View and manage registered users and wholesale clients.</p>
                </div>
            </div>

            <div class="table-card">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Customer Name</th>
                            <th>Phone</th>
                            <th>Company</th>
                            <th>Status</th>
                            <th>Registered On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($customers)): ?>
                            <?php foreach ($customers as $cust): ?>
                                <tr>
                                    <td>
                                        <div class="customer-name"><?= e($cust["FullName"] ?? 'Unknown User') ?></div>
                                        <div class="customer-email"><?= e($cust["Email"]) ?></div>
                                    </td>
                                    <td><?= e($cust["Phone"] ?? 'N/A') ?></td>
                                    <td><?= e($cust["CompanyName"] ?? 'N/A') ?></td>
                                    <td>
                                        <?php if (!isset($cust["IsActive"]) || (int)$cust["IsActive"] === 1): ?>
                                            <span class="badge badge-active">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-inactive">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= isset($cust["CreatedAt"]) && is_object($cust["CreatedAt"]) ? $cust["CreatedAt"]->format('Y-m-d') : 'N/A' ?>
                                    </td>
                                    <td>
                                        <div class="action-links">
                                            <!-- Setup link to view or edit customer details later -->
                                            <a href="view.php?id=<?= (int)$cust["UserId"] ?>" class="btn-action">View</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    No customers found in the database.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </section>
</main>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>