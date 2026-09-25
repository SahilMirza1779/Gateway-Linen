<?php
session_start();

/*
|--------------------------------------------------------------------------
| GatewayLinen Admin - Quotes Management
|--------------------------------------------------------------------------
*/

if (empty($_SESSION["admin_id"])) {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$activeMenu = "quotes";
$pageTitle  = "GatewayLinen | Bulk Inquiries & Quotes";

if (!isset($_SESSION["admin_name"])) {
    $_SESSION["admin_name"] = $_SESSION["admin_username"] ?? "GatewayLinen Administrator";
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

/*
|--------------------------------------------------------------------------
| HANDLE QUOTE STATUS UPDATE (Approve / Reject)
|--------------------------------------------------------------------------
*/
$actionMessage = "";
$actionType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"], $_POST["quote_id"])) {
    $targetQuoteId = (int)$_POST["quote_id"];
    $newStatus = $_POST["action"]; // 'Approved' or 'Rejected'

    $updateSql = "UPDATE dbo.Quotes SET Status = ? WHERE QuoteId = ?";
    $updateParams = [$newStatus, $targetQuoteId];
    $updateStmt = sqlsrv_query($conn, $updateSql, $updateParams);

    if ($updateStmt !== false) {
        $actionMessage = "Quote successfully marked as " . e($newStatus) . ".";
        $actionType = "success";
        sqlsrv_free_stmt($updateStmt);
    } else {
        $actionMessage = "Failed to update quote status.";
        $actionType = "error";
    }
}

/*
|--------------------------------------------------------------------------
| FETCH QUOTES FROM DATABASE
|--------------------------------------------------------------------------
*/
$quotes = [];
$sql = "
    SELECT 
        QuoteId, 
        QuoteNumber, 
        UserId, 
        CompanyName, 
        ContactPerson, 
        TotalQuotedAmount, 
        Status, 
        CreatedAt 
    FROM dbo.Quotes 
    ORDER BY CreatedAt DESC
";

$stmt = sqlsrv_query($conn, $sql);

if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $quotes[] = $row;
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
        --yellow: #f59e0b;
        --yellow-soft: rgba(245, 158, 11, .12);
        --red: #ef4444;
        --red-soft: rgba(239, 68, 68, .12);
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

    .quotes-page {
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

    .quote-user {
        color: var(--text-hi);
        font-weight: 700;
        font-size: 13px;
    }

    .quote-company {
        font-size: 10.5px;
        color: var(--text-mute);
    }

    .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .badge-pending {
        background: var(--yellow-soft);
        color: var(--yellow);
        border: 1px solid rgba(245, 158, 11, .2);
    }

    .badge-approved {
        background: var(--green-soft);
        color: var(--green);
        border: 1px solid rgba(16, 185, 129, .2);
    }

    .badge-rejected {
        background: var(--red-soft);
        color: var(--red);
        border: 1px solid rgba(239, 68, 68, .2);
    }

    .action-links {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .btn-action {
        padding: 6px 12px;
        border-radius: 7px;
        font-size: 11px;
        font-weight: 700;
        border: 1px solid var(--border);
        background: var(--bg-input);
        color: var(--text-body);
        cursor: pointer;
        transition: .15s ease;
        text-decoration: none;
    }

    .btn-action:hover {
        border-color: var(--blue);
        color: var(--blue);
        background: var(--blue-soft);
    }

    .btn-approve:hover {
        background: var(--green-soft);
        border-color: var(--green);
        color: var(--green);
    }

    .btn-reject:hover {
        background: var(--red-soft);
        border-color: var(--red);
        color: var(--red);
    }
</style>

<main class="main">
    <section class="content">
        <div class="quotes-page">

            <div class="page-header">
                <div>
                    <h1 class="page-title">Bulk Inquiries & Quotes</h1>
                    <p class="page-subtitle">Review, approve, or reject bulk order quotation requests from customers.</p>
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
                            <th>Quote #</th>
                            <th>Contact Person</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($quotes)): ?>
                            <?php foreach ($quotes as $q):
                                $status = $q['Status'] ?? 'Pending';
                                $badgeClass = 'badge-pending';
                                if (strtolower($status) == 'approved') $badgeClass = 'badge-approved';
                                if (strtolower($status) == 'rejected') $badgeClass = 'badge-rejected';
                            ?>
                                <tr>
                                    <td><span style="color: var(--blue); font-weight: 700;"><?= e($q["QuoteNumber"] ?? 'N/A') ?></span></td>
                                    <td>
                                        <div class="quote-user"><?= e($q["ContactPerson"] ?? 'Unknown') ?></div>
                                        <div class="quote-company"><?= e($q["CompanyName"] ?? 'N/A') ?></div>
                                    </td>
                                    <td>
                                        <div style="color: var(--text-hi); font-weight: 600;">
                                            CAD $<?= number_format((float)($q["TotalQuotedAmount"] ?? 0), 2) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?= isset($q["CreatedAt"]) && is_object($q["CreatedAt"]) ? $q["CreatedAt"]->format('d M, Y') : 'N/A' ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $badgeClass ?>"><?= e($status) ?></span>
                                    </td>
                                    <td>
                                        <div class="action-links">
                                            <a href="view.php?id=<?= (int)$q["QuoteId"] ?>" class="btn-action">View</a>

                                            <?php if (strtolower($status) === 'pending' || $status === '' || $status === null): ?>
                                                <form method="POST" style="margin:0;">
                                                    <input type="hidden" name="quote_id" value="<?= (int)$q["QuoteId"] ?>">
                                                    <input type="hidden" name="action" value="Approved">
                                                    <button type="submit" class="btn-action btn-approve" onclick="return confirm('Are you sure you want to approve this quote?');">Approve</button>
                                                </form>
                                                <form method="POST" style="margin:0;">
                                                    <input type="hidden" name="quote_id" value="<?= (int)$q["QuoteId"] ?>">
                                                    <input type="hidden" name="action" value="Rejected">
                                                    <button type="submit" class="btn-action btn-reject" onclick="return confirm('Are you sure you want to reject this quote?');">Reject</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 50px 20px; color: var(--text-mute);">
                                    No quotes found in the database.
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