<?php
session_start();

/*
|--------------------------------------------------------------------------
| GatewayLinen Admin - View Quote Details
|--------------------------------------------------------------------------
*/

if (empty($_SESSION["admin_id"])) {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$activeMenu = "quotes";
$pageTitle  = "GatewayLinen | Quote Details";

if (!isset($_SESSION["admin_name"])) {
    $_SESSION["admin_name"] = $_SESSION["admin_username"] ?? "GatewayLinen Administrator";
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$quote_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($quote_id === 0) {
    echo "<div class='text-white p-6'>Invalid Quote ID.</div>";
    exit;
}

// 1. Fetch Quote Basic Info
$sql = "SELECT * FROM dbo.Quotes WHERE QuoteId = ?";
$stmt = sqlsrv_query($conn, $sql, [$quote_id]);
$quote = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$quote) {
    echo "<div class='text-white p-6'>Quote not found in database!</div>";
    exit;
}

// 2. Fetch Quote Items
$items = [];
$itemSql = "SELECT * FROM dbo.QuoteItems WHERE QuoteId = ?";
$itemStmt = sqlsrv_query($conn, $itemSql, [$quote_id]);
if ($itemStmt !== false) {
    while ($row = sqlsrv_fetch_array($itemStmt, SQLSRV_FETCH_ASSOC)) {
        $items[] = $row;
    }
}

require_once __DIR__ . "/../includes/header.php";
require_once __DIR__ . "/../includes/sidebar.php";
?>

<style>
    /* Theme matching your existing modals */
    :root {
        --bg-page: #0a1119;
        --bg-modal-card: #161f2e;
        --bg-input-box: #0f1724;
        --border-color: #2a374a;
        --text-hi: #ffffff;
        --text-body: #94a3b8;
        --text-label: #64748b;
        --green: #10b981;
        --blue: #3b82f6;
        --yellow: #f59e0b;
        --red: #ef4444;
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

    .view-wrapper {
        padding: 40px 20px;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        min-height: calc(100vh - 80px);
    }

    .details-card {
        width: 100%;
        max-width: 900px;
        background: var(--bg-modal-card);
        border-radius: 12px;
        border: 1px solid var(--border-color);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        overflow: hidden;
    }

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

    .card-body {
        padding: 24px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
        margin-bottom: 24px;
    }

    .info-box {
        background: var(--bg-input-box);
        padding: 16px;
        border-radius: 8px;
        border: 1px solid var(--border-color);
    }

    .info-box .main-label {
        font-size: 10px;
        text-transform: uppercase;
        color: var(--text-label);
        font-weight: 800;
        letter-spacing: 0.8px;
        margin-bottom: 6px;
    }

    .info-box .val {
        font-size: 14px;
        color: var(--text-hi);
        font-weight: 600;
    }

    .status-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .status-pending {
        background: rgba(245, 158, 11, .12);
        color: var(--yellow);
        border: 1px solid rgba(245, 158, 11, .2);
    }

    .status-approved {
        background: rgba(16, 185, 129, .12);
        color: var(--green);
        border: 1px solid rgba(16, 185, 129, .2);
    }

    .status-rejected {
        background: rgba(239, 68, 68, .12);
        color: var(--red);
        border: 1px solid rgba(239, 68, 68, .2);
    }

    .section-divider {
        font-size: 11px;
        text-transform: uppercase;
        color: var(--text-label);
        font-weight: 800;
        letter-spacing: 0.8px;
        margin: 24px 0 12px;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 8px;
    }

    /* Items Table */
    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .items-table th {
        background: var(--bg-input-box);
        color: var(--text-label);
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
    }

    .items-table td {
        padding: 12px;
        border-bottom: 1px solid var(--border-color);
        font-size: 13px;
        color: var(--text-body);
    }

    .items-table tr:hover td {
        background: rgba(255, 255, 255, 0.02);
    }

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
        transition: 0.2s ease;
    }

    .btn-secondary:hover {
        background: #1e293b;
    }
</style>

<main class="main">
    <section class="content">
        <div class="view-wrapper">

            <div class="details-card">
                <div class="card-header">
                    <h2 class="card-title">Quote Details: <span style="color:var(--blue);"><?= e($quote['QuoteNumber'] ?? 'N/A') ?></span></h2>
                    <a href="index.php" class="btn-close" title="Close">&#10005;</a>
                </div>

                <div class="card-body">

                    <?php
                    $statusClass = 'status-pending';
                    if (strtolower($quote['Status']) == 'approved') $statusClass = 'status-approved';
                    if (strtolower($quote['Status']) == 'rejected') $statusClass = 'status-rejected';
                    ?>

                    <div class="info-grid">
                        <div class="info-box">
                            <div class="main-label">Contact Person</div>
                            <div class="val"><?= e($quote['ContactPerson'] ?? 'N/A') ?></div>
                        </div>
                        <div class="info-box">
                            <div class="main-label">Company Name</div>
                            <div class="val"><?= e($quote['CompanyName'] ?? 'N/A') ?></div>
                        </div>
                        <div class="info-box">
                            <div class="main-label">Status</div>
                            <div class="status-badge <?= $statusClass ?>"><?= e($quote['Status'] ?? 'Pending') ?></div>
                        </div>
                        <div class="info-box">
                            <div class="main-label">Date Submitted</div>
                            <div class="val"><?= isset($quote["CreatedAt"]) && is_object($quote["CreatedAt"]) ? $quote["CreatedAt"]->format('d M, Y h:i A') : 'N/A' ?></div>
                        </div>
                    </div>

                    <div class="section-divider">Requested Items</div>

                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Variant ID</th>
                                <th>Quantity</th>
                                <th>Unit Price (CAD)</th>
                                <th>Line Total (CAD)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($items)): ?>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><strong style="color:var(--text-hi);">#<?= e($item['VariantId']) ?></strong></td>
                                        <td><?= e($item['Quantity']) ?></td>
                                        <td>$<?= number_format((float)$item['UnitPrice'], 2) ?></td>
                                        <td style="color:var(--text-hi); font-weight:600;">$<?= number_format((float)$item['LineTotal'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align:center; padding:30px; color:var(--text-label);">No items found for this quote.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <div style="text-align: right; margin-top: 20px; font-size: 16px;">
                        <span style="color:var(--text-label); font-weight:700; text-transform:uppercase; font-size:11px; margin-right:10px;">Total Quoted Amount:</span>
                        <strong style="color:var(--green);">$<?= number_format((float)($quote['TotalQuotedAmount'] ?? 0), 2) ?></strong>
                    </div>

                </div>

                <div class="card-footer">
                    <a href="index.php" class="btn-secondary">Back to Quotes</a>
                </div>
            </div>

        </div>
    </section>
</main>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>