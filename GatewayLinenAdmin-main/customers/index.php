<?php
session_start();

/*
|--------------------------------------------------------------------------
| GatewayLinen Admin - Customers Management
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
| HANDLE BLOCK / UNBLOCK ACTION
|--------------------------------------------------------------------------
*/
$actionMessage = "";
$actionType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"], $_POST["user_id"])) {
    $targetUserId = (int)$_POST["user_id"];
    $newStatus = ($_POST["action"] === "block") ? 0 : 1;

    $updateSql = "UPDATE dbo.Users SET IsActive = ? WHERE UserId = ?";
    $updateParams = [$newStatus, $targetUserId];
    $updateStmt = sqlsrv_query($conn, $updateSql, $updateParams);

    if ($updateStmt !== false) {
        $actionMessage = ($newStatus === 0) ? "Customer successfully blocked." : "Customer successfully unblocked.";
        $actionType = "success";
        sqlsrv_free_stmt($updateStmt);
    } else {
        $actionMessage = "Failed to update customer status.";
        $actionType = "error";
    }
}

/*
|--------------------------------------------------------------------------
| FETCH CUSTOMERS (USERS)
|--------------------------------------------------------------------------
*/
$customers = [];
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

    .alert-msg {
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 20px;
    }

    .alert-success {
        background: var(--green-soft);
        color: var(--green);
        border: 1px solid rgba(16, 185, 129, .2);
    }

    .alert-error {
        background: var(--red-soft);
        color: var(--red);
        border: 1px solid rgba(239, 68, 68, .2);
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
        align-items: center;
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
        cursor: pointer;
    }

    .btn-action:hover {
        border-color: var(--blue);
        color: var(--blue);
        background: var(--blue-soft);
    }

    .btn-block {
        border-color: rgba(239, 68, 68, 0.4);
        color: var(--red);
    }

    .btn-block:hover {
        background: var(--red-soft);
        border-color: var(--red);
        color: var(--red);
    }

    .btn-unblock {
        border-color: rgba(16, 185, 129, 0.4);
        color: var(--green);
    }

    .btn-unblock:hover {
        background: var(--green-soft);
        border-color: var(--green);
        color: var(--green);
    }

    .empty-state {
        text-align: center;
        padding: 50px 20px;
        color: var(--text-mute);
    }

    /* Custom Dark Modal Styles */
    .custom-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(4, 8, 13, 0.8);
        backdrop-filter: blur(4px);
        z-index: 99999;
        justify-content: center;
        align-items: center;
    }

    .custom-modal-box {
        background: #161f2e;
        border: 1px solid #2a374a;
        padding: 24px;
        border-radius: 14px;
        width: 100%;
        max-width: 380px;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.6);
        text-align: center;
    }

    .custom-modal-title {
        color: #ffffff;
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .custom-modal-desc {
        color: #94a3b8;
        font-size: 12.5px;
        margin-bottom: 20px;
        line-height: 1.4;
    }

    .custom-modal-actions {
        display: flex;
        gap: 10px;
        justify-content: center;
    }

    .modal-btn {
        padding: 8px 18px;
        border-radius: 8px;
        font-size: 11.5px;
        font-weight: 700;
        cursor: pointer;
        border: none;
        transition: 0.15s ease;
    }

    .modal-btn-cancel {
        background: #0f1724;
        color: #94a3b8;
        border: 1px solid #2a374a;
    }

    .modal-btn-cancel:hover {
        background: #1e293b;
        color: #ffffff;
    }

    .modal-btn-confirm {
        color: #ffffff;
    }
</style>

<main class="main">
    <section class="content">
        <div class="customers-page">

            <div class="page-header">
                <div>
                    <h1 class="page-title">Customers Management</h1>
                    <p class="page-subtitle">View and manage registered users, block or unblock accounts.</p>
                </div>
            </div>

            <?php if (!empty($actionMessage)): ?>
                <div class="alert-msg <?= $actionType === 'success' ? 'alert-success' : 'alert-error' ?>">
                    <?= e($actionMessage) ?>
                </div>
            <?php endif; ?>

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
                            <?php foreach ($customers as $cust):
                                $isActive = !isset($cust["IsActive"]) || (int)$cust["IsActive"] === 1;
                            ?>
                                <tr>
                                    <td>
                                        <div class="customer-name"><?= e($cust["FullName"] ?? 'Unknown User') ?></div>
                                        <div class="customer-email"><?= e($cust["Email"]) ?></div>
                                    </td>
                                    <td><?= e($cust["Phone"] ?? 'N/A') ?></td>
                                    <td><?= e($cust["CompanyName"] ?? 'N/A') ?></td>
                                    <td>
                                        <?php if ($isActive): ?>
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
                                            <a href="view.php?id=<?= (int)$cust["UserId"] ?>" class="btn-action">View</a>

                                            <form method="POST" style="margin: 0;" onsubmit="return showCustomConfirm(this, '<?= $isActive ? 'block' : 'unblock' ?>', '<?= e($cust["FullName"] ?? 'this customer') ?>')">
                                                <input type="hidden" name="user_id" value="<?= (int)$cust["UserId"] ?>">
                                                <?php if ($isActive): ?>
                                                    <input type="hidden" name="action" value="block">
                                                    <button type="submit" class="btn-action btn-block">Block</button>
                                                <?php else: ?>
                                                    <input type="hidden" name="action" value="unblock">
                                                    <button type="submit" class="btn-action btn-unblock">Unblock</button>
                                                <?php endif; ?>
                                            </form>
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

<!-- Custom Dark Theme Confirmation Modal -->
<div id="customConfirmModal" class="custom-modal-overlay">
    <div class="custom-modal-box">
        <h3 id="modalTitle" class="custom-modal-title">Confirm Action</h3>
        <p id="modalDesc" class="custom-modal-desc">Are you sure you want to proceed with this action?</p>
        <div class="custom-modal-actions">
            <button type="button" id="modalCancelBtn" class="modal-btn modal-btn-cancel">Cancel</button>
            <button type="button" id="modalConfirmBtn" class="modal-btn modal-btn-confirm">Confirm</button>
        </div>
    </div>
</div>

<script>
    let activeForm = null;

    function showCustomConfirm(form, actionType, customerName) {
        activeForm = form;
        const modal = document.getElementById('customConfirmModal');
        const titleEl = document.getElementById('modalTitle');
        const descEl = document.getElementById('modalDesc');
        const confirmBtn = document.getElementById('modalConfirmBtn');

        if (actionType === 'block') {
            titleEl.innerText = "Block Customer";
            descEl.innerHTML = "Are you sure you want to block <b>" + customerName + "</b>? They will not be able to access the system.";
            confirmBtn.style.background = "#ef4444";
            confirmBtn.innerText = "Block";
        } else {
            titleEl.innerText = "Unblock Customer";
            descEl.innerHTML = "Are you sure you want to unblock <b>" + customerName + "</b>? Their access will be restored.";
            confirmBtn.style.background = "#10b981";
            confirmBtn.innerText = "Unblock";
        }

        modal.style.display = 'flex';
        return false; // Prevent immediate form submission
    }

    document.getElementById('modalCancelBtn').onclick = function() {
        document.getElementById('customConfirmModal').style.display = 'none';
        activeForm = null;
    };

    document.getElementById('modalConfirmBtn').onclick = function() {
        if (activeForm) {
            activeForm.submit();
        }
    };
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>