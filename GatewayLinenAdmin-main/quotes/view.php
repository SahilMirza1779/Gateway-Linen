<?php
session_start();

/*
|--------------------------------------------------------------------------
| GatewayLinen Admin - Complete Quote Details with All Items & Redirect
|--------------------------------------------------------------------------
| File:
| GatewayLinenAdmin/quotes/view.php
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| AUTHENTICATION
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

$activeMenu = 'quotes';
$pageTitle  = 'GatewayLinen | Quote Details';

/*
|--------------------------------------------------------------------------
| ADMIN NAME FALLBACK
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] =
        $_SESSION['admin_username'] ??
        'GatewayLinen Administrator';
}

/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

function e($value)
{
    if ($value instanceof DateTimeInterface) {
        return htmlspecialchars(
            $value->format('d M Y, h:i A'),
            ENT_QUOTES,
            'UTF-8'
        );
    }

    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function formatQuoteValue($value, $key = '')
{
    if ($value === null || $value === '') {
        return '—';
    }

    if ($value instanceof DateTimeInterface) {
        return e($value->format('d M Y, h:i A'));
    }

    if (is_resource($value)) {
        return '[Binary Data]';
    }

    if (is_bool($value)) {
        return $value ? 'Yes' : 'No';
    }

    $keyLower = strtolower($key);

    $moneyKeys = [
        'totalquotedamount',
        'unitprice',
        'linetotal',
        'totalamount',
        'subtotal',
        'grandtotal',
        'discountamount',
        'taxamount',
        'shippingamount',
        'amount'
    ];

    foreach ($moneyKeys as $moneyKey) {
        if (strpos($keyLower, $moneyKey) !== false) {
            if (is_numeric($value)) {
                return '$' . number_format((float)$value, 2);
            }
        }
    }

    if (
        strpos($keyLower, 'email') !== false &&
        filter_var($value, FILTER_VALIDATE_EMAIL)
    ) {
        return '<a class="detail-link" href="mailto:' .
            e($value) .
            '">' .
            e($value) .
            '</a>';
    }

    if (
        strpos($keyLower, 'phone') !== false ||
        strpos($keyLower, 'mobile') !== false
    ) {
        return '<a class="detail-link" href="tel:' .
            e($value) .
            '">' .
            e($value) .
            '</a>';
    }

    return e($value);
}

/*
|--------------------------------------------------------------------------
| QUOTE ID
|--------------------------------------------------------------------------
*/

$quote_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($quote_id <= 0) {
    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['quote_csrf'])) {
    $_SESSION['quote_csrf'] = bin2hex(random_bytes(32));
}

/*
|--------------------------------------------------------------------------
| STATUS ACTION & IMMEDIATE REDIRECT TO INDEX
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $postedQuoteId = isset($_POST['quote_id'])
        ? (int)$_POST['quote_id']
        : 0;

    $action = $_POST['quote_action'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (
        !empty($csrf) &&
        hash_equals($_SESSION['quote_csrf'], $csrf) &&
        $postedQuoteId === $quote_id
    ) {
        $newStatus = '';

        if ($action === 'approve') {
            $newStatus = 'Approved';
        } elseif ($action === 'reject') {
            $newStatus = 'Rejected';
        }

        if ($newStatus !== '') {
            $updateSql = "
                UPDATE dbo.Quotes
                SET Status = ?
                WHERE QuoteId = ?
            ";

            $updateStmt = sqlsrv_query(
                $conn,
                $updateSql,
                [$newStatus, $quote_id]
            );

            if ($updateStmt !== false) {
                header('Location: index.php');
                exit;
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| FETCH QUOTE
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT *
    FROM dbo.Quotes
    WHERE QuoteId = ?
";

$stmt = sqlsrv_query(
    $conn,
    $sql,
    [$quote_id]
);

if ($stmt === false) {
    die('Unable to load quote details.');
}

$quote = sqlsrv_fetch_array(
    $stmt,
    SQLSRV_FETCH_ASSOC
);

if (!$quote) {
    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| FETCH ALL QUOTE ITEMS WITH PRODUCT DETAILS
|--------------------------------------------------------------------------
*/

$items = [];

$itemSql = "
    SELECT 
        qi.QuoteItemId,
        qi.QuoteId,
        qi.VariantId,
        qi.Quantity,
        qi.UnitPrice,
        qi.LineTotal,
        p.Name AS ProductName
    FROM dbo.QuoteItems qi
    LEFT JOIN dbo.ProductVariants pv ON qi.VariantId = pv.VariantId
    LEFT JOIN dbo.Products p ON pv.ProductId = p.ProductId
    WHERE qi.QuoteId = ?
";

$itemStmt = sqlsrv_query(
    $conn,
    $itemSql,
    [$quote_id]
);

if ($itemStmt !== false) {
    while ($row = sqlsrv_fetch_array(
        $itemStmt,
        SQLSRV_FETCH_ASSOC
    )) {
        if (!empty($row['QuoteItemId'])) {
            $items[] = $row;
        }
    }
}

/*
|--------------------------------------------------------------------------
| CURRENT STATUS
|--------------------------------------------------------------------------
*/

$currentStatus = trim(
    (string)($quote['Status'] ?? 'Pending')
);

$statusLower = strtolower($currentStatus);

$statusClass = 'status-pending';

if (
    $statusLower === 'approved' ||
    $statusLower === 'accepted'
) {
    $statusClass = 'status-approved';
}

if (
    $statusLower === 'rejected' ||
    $statusLower === 'declined'
) {
    $statusClass = 'status-rejected';
}

$quoteNumber = $quote['QuoteNumber']
    ?? ('QT-' . str_pad(
        (string)$quote_id,
        5,
        '0',
        STR_PAD_LEFT
    ));

$totalQuotedAmount = isset($quote['TotalQuotedAmount'])
    ? (float)$quote['TotalQuotedAmount']
    : 0;

/*
|--------------------------------------------------------------------------
| HEADER & SIDEBAR
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<style>
:root {
    --quote-page-bg: #ffffff;
    --quote-card-bg: #ffffff;
    --quote-box-bg: #f8fafc;
    --quote-table-head: #f1f5f9;
    --quote-table-hover: #f8fafc;
    --quote-border: #dbe3ec;
    --quote-text-main: #0f172a;
    --quote-text-body: #334155;
    --quote-text-muted: #64748b;
    --quote-blue: #0284c7;
    --quote-green: #059669;
    --quote-red: #dc2626;
    --quote-yellow: #d97706;
    --quote-shadow: 0 20px 50px rgba(15, 23, 42, 0.12);
}

html.dark-mode, body.dark-mode,
html[data-theme="dark"], body[data-theme="dark"] {
    --quote-page-bg: #0a1119;
    --quote-card-bg: #111b26;
    --quote-box-bg: #0d1722;
    --quote-table-head: #162230;
    --quote-table-hover: #182635;
    --quote-border: #26384b;
    --quote-text-main: #f8fafc;
    --quote-text-body: #cbd5e1;
    --quote-text-muted: #94a3b8;
    --quote-blue: #38bdf8;
    --quote-green: #10b981;
    --quote-red: #ef4444;
    --quote-yellow: #f59e0b;
    --quote-shadow: 0 25px 60px rgba(0, 0, 0, 0.45);
}

.main, .content {
    background: var(--quote-page-bg) !important;
    color: var(--quote-text-body) !important;
}
.content { min-height: calc(100vh - 70px); }
.quote-view-page { width: 100%; min-height: calc(100vh - 70px); padding: 32px 28px 60px; background: var(--quote-page-bg); box-sizing: border-box; }
.quote-wrapper { width: 100%; max-width: 1250px; margin: 0 auto; }
.quote-topbar { display: flex; justify-content: space-between; align-items: center; gap: 20px; margin-bottom: 22px; }
.quote-heading { margin: 0; color: var(--quote-text-main); font-size: 24px; font-weight: 800; }
.quote-heading span { color: var(--quote-blue); }
.quote-subtitle { margin: 7px 0 0; color: var(--quote-text-muted); font-size: 13px; }
.quote-close { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border: 1px solid var(--quote-border); border-radius: 10px; background: var(--quote-card-bg); color: var(--quote-text-muted); text-decoration: none; font-size: 20px; transition: all .2s ease; }
.quote-close:hover { color: var(--quote-text-main); border-color: var(--quote-blue); }
.quote-card { width: 100%; background: var(--quote-card-bg); border: 1px solid var(--quote-border); border-radius: 16px; box-shadow: var(--quote-shadow); overflow: hidden; }
.quote-card-header { padding: 22px 26px; display: flex; align-items: center; justify-content: space-between; gap: 20px; border-bottom: 1px solid var(--quote-border); background: var(--quote-card-bg); }
.quote-card-title { margin: 0; color: var(--quote-text-main); font-size: 17px; font-weight: 800; }
.quote-card-title .quote-number { color: var(--quote-blue); font-family: monospace; }
.quote-status { display: inline-flex; align-items: center; justify-content: center; min-width: 92px; padding: 7px 12px; border-radius: 999px; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: .5px; }
.status-pending { color: var(--quote-yellow); background: rgba(245, 158, 11, .12); border: 1px solid rgba(245, 158, 11, .25); }
.status-approved { color: var(--quote-green); background: rgba(16, 185, 129, .12); border: 1px solid rgba(16, 185, 129, .25); }
.status-rejected { color: var(--quote-red); background: rgba(239, 68, 68, .12); border: 1px solid rgba(239, 68, 68, .25); }
.quote-card-body { padding: 26px; }
.quote-section { margin-bottom: 30px; }
.quote-section:last-child { margin-bottom: 0; }
.quote-section-title { display: flex; align-items: center; gap: 10px; margin: 0 0 14px; padding-bottom: 10px; border-bottom: 1px solid var(--quote-border); color: var(--quote-text-main); font-size: 12px; font-weight: 900; text-transform: uppercase; letter-spacing: .8px; }
.quote-section-title::before { content: ""; width: 4px; height: 16px; display: inline-block; border-radius: 10px; background: var(--quote-green); }
.quote-info-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
.quote-info-box { padding: 16px; background: var(--quote-box-bg); border: 1px solid var(--quote-border); border-radius: 10px; }
.quote-info-label { margin-bottom: 7px; color: var(--quote-text-muted); font-size: 9px; font-weight: 900; text-transform: uppercase; }
.quote-info-value { color: var(--quote-text-main); font-size: 13px; font-weight: 650; word-break: break-word; }
.all-details-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
.all-detail-box { padding: 13px 14px; background: var(--quote-box-bg); border: 1px solid var(--quote-border); border-radius: 9px; }
.all-detail-key { margin-bottom: 5px; color: var(--quote-text-muted); font-size: 9px; font-weight: 900; text-transform: uppercase; }
.all-detail-value { color: var(--quote-text-main); font-size: 12px; word-break: break-word; }
.items-wrapper { width: 100%; overflow-x: auto; border: 1px solid var(--quote-border); border-radius: 10px; }
.items-table { width: 100%; min-width: 700px; border-collapse: collapse; }
.items-table th { padding: 13px 14px; background: var(--quote-table-head); color: var(--quote-text-muted); border-bottom: 1px solid var(--quote-border); font-size: 10px; font-weight: 900; text-transform: uppercase; text-align: left; white-space: nowrap; }
.items-table td { padding: 13px 14px; color: var(--quote-text-body); border-bottom: 1px solid var(--quote-border); font-size: 12px; vertical-align: top; white-space: nowrap; }
.items-table tbody tr:last-child td { border-bottom: none; }
.items-table tbody tr:hover td { background: var(--quote-table-hover); }
.empty-items { padding: 35px; text-align: center; color: var(--quote-text-muted); font-size: 13px; }
.quote-total-box { margin-top: 18px; padding: 20px 22px; display: flex; justify-content: flex-end; align-items: center; gap: 18px; background: var(--quote-box-bg); border: 1px solid var(--quote-border); border-radius: 10px; }
.quote-total-label { color: var(--quote-text-muted); font-size: 10px; font-weight: 900; text-transform: uppercase; }
.quote-total-value { color: var(--quote-green); font-size: 23px; font-weight: 900; }
.quote-actions { padding: 20px 26px; display: flex; justify-content: flex-end; align-items: center; gap: 10px; border-top: 1px solid var(--quote-border); background: var(--quote-card-bg); }
.quote-action-btn { min-width: 145px; height: 42px; padding: 0 18px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; border-radius: 9px; border: 1px solid transparent; font-size: 12px; font-weight: 800; cursor: pointer; transition: all .2s ease; }
.btn-accept { color: #ffffff; background: #059669; border-color: #059669; }
.btn-accept:hover { background: #047857; }
.btn-reject { color: #ffffff; background: #dc2626; border-color: #dc2626; }
.btn-reject:hover { background: #b91c1c; }
@media (max-width: 1000px) {
    .quote-info-grid, .all-details-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 700px) {
    .quote-info-grid, .all-details-grid { grid-template-columns: 1fr; }
    .quote-actions { flex-direction: column; align-items: stretch; }
    .quote-action-btn { width: 100%; }
}
</style>

<main class="main">
    <section class="content">
        <div class="quote-view-page">
            <div class="quote-wrapper">

                <div class="quote-topbar">
                    <div>
                        <h1 class="quote-heading">
                            Quote Details: <span><?= e($quoteNumber) ?></span>
                        </h1>
                        <p class="quote-subtitle">
                            Complete quotation information, requested items, pricing and current status.
                        </p>
                    </div>
                    <a href="index.php" class="quote-close" title="Back to Quotes" aria-label="Back to Quotes">&times;</a>
                </div>

                <div class="quote-card">
                    <div class="quote-card-header">
                        <h2 class="quote-card-title">
                            Quote Details: <span class="quote-number"><?= e($quoteNumber) ?></span>
                        </h2>
                        <span class="quote-status <?= e($statusClass) ?>">
                            <?= e($currentStatus) ?>
                        </span>
                    </div>

                    <div class="quote-card-body">

                        <!-- BASIC INFORMATION -->
                        <div class="quote-section">
                            <h3 class="quote-section-title">Quote Information</h3>
                            <div class="quote-info-grid">
                                <div class="quote-info-box">
                                    <div class="quote-info-label">Quote Number</div>
                                    <div class="quote-info-value"><?= e($quoteNumber) ?></div>
                                </div>
                                <div class="quote-info-box">
                                    <div class="quote-info-label">Contact Person</div>
                                    <div class="quote-info-value"><?= e($quote['ContactPerson'] ?? '—') ?></div>
                                </div>
                                <div class="quote-info-box">
                                    <div class="quote-info-label">Company Name</div>
                                    <div class="quote-info-value"><?= e($quote['CompanyName'] ?? '—') ?></div>
                                </div>
                                <div class="quote-info-box">
                                    <div class="quote-info-label">Status</div>
                                    <div class="quote-info-value">
                                        <span class="quote-status <?= e($statusClass) ?>"><?= e($currentStatus) ?></span>
                                    </div>
                                </div>
                                <div class="quote-info-box">
                                    <div class="quote-info-label">Email</div>
                                    <div class="quote-info-value">
                                        <?php $emailValue = $quote['UserEmail'] ?? $quote['Email'] ?? null; ?>
                                        <?= formatQuoteValue($emailValue, 'Email') ?>
                                    </div>
                                </div>
                                <div class="quote-info-box">
                                    <div class="quote-info-label">Phone</div>
                                    <div class="quote-info-value">
                                        <?= formatQuoteValue($quote['Phone'] ?? $quote['PhoneNumber'] ?? $quote['Mobile'] ?? null, 'Phone') ?>
                                    </div>
                                </div>
                                <div class="quote-info-box">
                                    <div class="quote-info-label">Date Submitted</div>
                                    <div class="quote-info-value">
                                        <?= formatQuoteValue($quote['CreatedAt'] ?? null, 'CreatedAt') ?>
                                    </div>
                                </div>
                                <div class="quote-info-box">
                                    <div class="quote-info-label">Expiry Date</div>
                                    <div class="quote-info-value">
                                        <?= formatQuoteValue($quote['ExpiryDate'] ?? null, 'ExpiryDate') ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- COMPLETE QUOTE DATABASE DETAILS -->
                        <div class="quote-section">
                            <h3 class="quote-section-title">Complete Quote Details</h3>
                            <div class="all-details-grid">
                                <?php foreach ($quote as $key => $value): ?>
                                    <div class="all-detail-box">
                                        <div class="all-detail-key">
                                            <?= e(preg_replace('/(?<!^)([A-Z])/', ' $1', (string)$key)) ?>
                                        </div>
                                        <div class="all-detail-value">
                                            <?= formatQuoteValue($value, $key) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- REQUESTED ITEMS WITH PRODUCT NAMES & QUANTITIES -->
                        <div class="quote-section">
                            <h3 class="quote-section-title">Requested Items</h3>

                            <?php if (!empty($items)): ?>
                                <div class="items-wrapper">
                                    <table class="items-table">
                                        <thead>
                                            <tr>
                                                <th>Quote Item ID</th>
                                                <th>Product Name</th>
                                                <th>Variant ID</th>
                                                <th>Quantity</th>
                                                <th>Unit Price</th>
                                                <th>Line Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($items as $item): ?>
                                                <tr>
                                                    <td><?= formatQuoteValue($item['QuoteItemId'] ?? null, 'QuoteItemId') ?></td>
                                                    <td><strong><?= e($item['ProductName'] ?? 'Unknown Product') ?></strong></td>
                                                    <td><?= formatQuoteValue($item['VariantId'] ?? null, 'VariantId') ?></td>
                                                    <td><?= formatQuoteValue($item['Quantity'] ?? null, 'Quantity') ?></td>
                                                    <td><?= formatQuoteValue($item['UnitPrice'] ?? null, 'UnitPrice') ?></td>
                                                    <td><?= formatQuoteValue($item['LineTotal'] ?? null, 'LineTotal') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="empty-items">No items found for this quotation.</div>
                            <?php endif; ?>

                            <div class="quote-total-box">
                                <span class="quote-total-label">Total Quoted Amount</span>
                                <strong class="quote-total-value">$<?= number_format($totalQuotedAmount, 2) ?></strong>
                            </div>
                        </div>

                    </div>

                    <!-- ACTION FOOTER -->
                    <div class="quote-actions">
                        <!-- ACCEPT -->
                        <form method="POST" action="" style="margin:0;" onsubmit="return confirmAccept();">
                            <input type="hidden" name="quote_id" value="<?= (int)$quote_id ?>">
                            <input type="hidden" name="quote_action" value="approve">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['quote_csrf']) ?>">
                            <button type="submit" class="quote-action-btn btn-accept">✓ Accept Quote</button>
                        </form>

                        <!-- REJECT -->
                        <form method="POST" action="" style="margin:0;" onsubmit="return confirmReject();">
                            <input type="hidden" name="quote_id" value="<?= (int)$quote_id ?>">
                            <input type="hidden" name="quote_action" value="reject">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['quote_csrf']) ?>">
                            <button type="submit" class="quote-action-btn btn-reject">✕ Reject Quote</button>
                        </form>
                    </div>

                </div>

            </div>
        </div>
    </section>
</main>

<script>
function confirmAccept() {
    return window.confirm('Are you sure you want to ACCEPT this quotation?');
}

function confirmReject() {
    return window.confirm('Are you sure you want to REJECT this quotation?');
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>