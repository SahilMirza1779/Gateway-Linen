<?php

session_start();

/*
|--------------------------------------------------------------------------
| GatewayLinen Admin - Price Quotations
|--------------------------------------------------------------------------
| File:
| GatewayLinenadmin/quotes/index.php
|
| Actions:
| - View quotation only
| - Search
| - Status filter
| - Date filter
| - CSV export
| - Print
| - Pagination
|--------------------------------------------------------------------------
*/


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

$activeMenu = 'quotes';
$pageTitle  = 'GatewayLinen | Price Quotations';


/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

function e($value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}


function formatDateValue($value): string
{
    if ($value instanceof DateTimeInterface) {
        return $value->format('d M Y, h:i A');
    }

    if (!empty($value)) {
        $timestamp = strtotime((string)$value);

        if ($timestamp !== false) {
            return date('d M Y, h:i A', $timestamp);
        }
    }

    return '—';
}


function formatMoney($value): string
{
    return '$' . number_format(
        (float)($value ?? 0),
        2
    );
}


function getQuoteNumber(array $quote): string
{
    if (!empty($quote['QuoteNumber'])) {
        return (string)$quote['QuoteNumber'];
    }

    return 'QT-' . str_pad(
        (string)($quote['QuoteId'] ?? 0),
        5,
        '0',
        STR_PAD_LEFT
    );
}


function getStatusClass($status): string
{
    $status = strtolower(
        trim((string)$status)
    );

    switch ($status) {

        case 'approved':
        case 'accepted':
            return 'status-approved';

        case 'rejected':
            return 'status-rejected';

        case 'sent':
            return 'status-sent';

        case 'pending':
        default:
            return 'status-pending';
    }
}


function getStatusIcon($status): string
{
    $status = strtolower(
        trim((string)$status)
    );

    switch ($status) {

        case 'approved':
        case 'accepted':
            return '✓';

        case 'rejected':
            return '×';

        case 'sent':
            return '➤';

        case 'pending':
        default:
            return '⏳';
    }
}


/*
|--------------------------------------------------------------------------
| FILTER VALUES
|--------------------------------------------------------------------------
*/

$statusFilter = trim(
    (string)($_GET['status_filter'] ?? '')
);

$fromDate = trim(
    (string)($_GET['from_date'] ?? '')
);

$toDate = trim(
    (string)($_GET['to_date'] ?? '')
);

$searchQuery = trim(
    (string)($_GET['q'] ?? '')
);


/*
|--------------------------------------------------------------------------
| PAGINATION
|--------------------------------------------------------------------------
*/

$page = max(
    1,
    (int)($_GET['page'] ?? 1)
);

$limit = 10;

$offset = (
    $page - 1
) * $limit;


/*
|--------------------------------------------------------------------------
| WHERE CLAUSE
|--------------------------------------------------------------------------
*/

$whereSql = "
    WHERE 1 = 1
";

$params = [];


/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

if ($statusFilter !== '') {

    if ($statusFilter === 'Approved') {

        $whereSql .= "
            AND q.Status IN ('Approved', 'Accepted')
        ";

    } else {

        $whereSql .= "
            AND q.Status = ?
        ";

        $params[] = $statusFilter;
    }
}


/*
|--------------------------------------------------------------------------
| FROM DATE
|--------------------------------------------------------------------------
*/

if ($fromDate !== '') {

    $whereSql .= "
        AND q.CreatedAt >= ?
    ";

    $params[] =
        $fromDate . ' 00:00:00';
}


/*
|--------------------------------------------------------------------------
| TO DATE
|--------------------------------------------------------------------------
*/

if ($toDate !== '') {

    $whereSql .= "
        AND q.CreatedAt <= ?
    ";

    $params[] =
        $toDate . ' 23:59:59';
}


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

if ($searchQuery !== '') {

    $whereSql .= "
        AND
        (
            q.QuoteNumber LIKE ?
            OR q.CompanyName LIKE ?
            OR q.ContactPerson LIKE ?
        )
    ";

    $searchLike =
        '%' . $searchQuery . '%';

    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
}


/*
|--------------------------------------------------------------------------
| CSV EXPORT
|--------------------------------------------------------------------------
*/

if (
    isset($_GET['export'])
    && $_GET['export'] === 'csv'
) {

    header(
        'Content-Type: text/csv; charset=utf-8'
    );

    header(
        'Content-Disposition: attachment; filename="quotations_' .
        date('Y-m-d') .
        '.csv"'
    );


    $output = fopen(
        'php://output',
        'w'
    );


    fputcsv(
        $output,
        [
            'Quote #',
            'Company Name',
            'Contact Person',
            'Email',
            'Quoted Amount',
            'Status',
            'Expiry Date',
            'Created At',
            'Converted Order'
        ]
    );


    $exportSql = "
        SELECT
            q.QuoteNumber,
            q.QuoteId,
            q.CompanyName,
            q.ContactPerson,
            q.TotalQuotedAmount,
            q.Status,
            q.ExpiryDate,
            q.CreatedAt,
            q.ConvertedOrderId,
            u.Email AS UserEmail

        FROM dbo.Quotes q

        LEFT JOIN dbo.Users u
            ON q.UserId = u.UserId

        $whereSql

        ORDER BY q.QuoteId DESC
    ";


    $exportStmt = sqlsrv_query(
        $conn,
        $exportSql,
        $params
    );


    if ($exportStmt !== false) {

        while (
            $row = sqlsrv_fetch_array(
                $exportStmt,
                SQLSRV_FETCH_ASSOC
            )
        ) {

            fputcsv(
                $output,
                [
                    getQuoteNumber($row),

                    $row['CompanyName']
                        ?? '',

                    $row['ContactPerson']
                        ?? '',

                    $row['UserEmail']
                        ?? '',

                    $row['TotalQuotedAmount']
                        ?? 0,

                    $row['Status']
                        ?? '',

                    $row['ExpiryDate'] instanceof DateTime
                        ? $row['ExpiryDate']->format(
                            'Y-m-d H:i'
                        )
                        : (
                            $row['ExpiryDate']
                            ?? ''
                        ),

                    $row['CreatedAt'] instanceof DateTime
                        ? $row['CreatedAt']->format(
                            'Y-m-d H:i'
                        )
                        : (
                            $row['CreatedAt']
                            ?? ''
                        ),

                    !empty(
                        $row['ConvertedOrderId']
                    )
                        ? $row['ConvertedOrderId']
                        : 'Not Converted'
                ]
            );
        }


        sqlsrv_free_stmt(
            $exportStmt
        );
    }


    fclose($output);

    exit;
}


/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$totalQuotes     = 0;
$pendingQuotes   = 0;
$approvedQuotes  = 0;
$convertedOrders = 0;


$statsSql = "
    SELECT

        COUNT(*) AS TotalCount,

        SUM(
            CASE
                WHEN Status = 'Pending'
                THEN 1
                ELSE 0
            END
        ) AS PendingCount,

        SUM(
            CASE
                WHEN Status IN ('Approved', 'Accepted')
                THEN 1
                ELSE 0
            END
        ) AS ApprovedCount,

        SUM(
            CASE
                WHEN ConvertedOrderId IS NOT NULL
                THEN 1
                ELSE 0
            END
        ) AS ConvertedCount

    FROM dbo.Quotes
";


$statsStmt = sqlsrv_query(
    $conn,
    $statsSql
);


if ($statsStmt !== false) {

    $statsRow = sqlsrv_fetch_array(
        $statsStmt,
        SQLSRV_FETCH_ASSOC
    );


    if ($statsRow) {

        $totalQuotes =
            (int)($statsRow['TotalCount'] ?? 0);

        $pendingQuotes =
            (int)($statsRow['PendingCount'] ?? 0);

        $approvedQuotes =
            (int)($statsRow['ApprovedCount'] ?? 0);

        $convertedOrders =
            (int)($statsRow['ConvertedCount'] ?? 0);
    }


    sqlsrv_free_stmt(
        $statsStmt
    );
}


/*
|--------------------------------------------------------------------------
| FILTERED TOTAL
|--------------------------------------------------------------------------
*/

$countSql = "
    SELECT COUNT(*) AS Total

    FROM dbo.Quotes q

    $whereSql
";


$countStmt = sqlsrv_query(
    $conn,
    $countSql,
    $params
);


$totalRows = 0;


if ($countStmt !== false) {

    $countRow = sqlsrv_fetch_array(
        $countStmt,
        SQLSRV_FETCH_ASSOC
    );


    if ($countRow) {

        $totalRows =
            (int)($countRow['Total'] ?? 0);
    }


    sqlsrv_free_stmt(
        $countStmt
    );
}


$totalPages = max(
    1,
    (int)ceil(
        $totalRows / $limit
    )
);


if ($page > $totalPages) {

    $page = $totalPages;

    $offset =
        ($page - 1) * $limit;
}


/*
|--------------------------------------------------------------------------
| FETCH QUOTATIONS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT *
    FROM
    (
        SELECT

            ROW_NUMBER() OVER (
                ORDER BY q.QuoteId DESC
            ) AS RowNum,

            q.QuoteId,
            q.QuoteNumber,
            q.UserId,

            ISNULL(
                q.CompanyName,
                'Individual Client'
            ) AS CompanyName,

            q.ContactPerson,

            q.TotalQuotedAmount,

            ISNULL(
                q.Status,
                'Pending'
            ) AS Status,

            q.ExpiryDate,
            q.ConvertedOrderId,
            q.CreatedAt,

            u.Email AS UserEmail,
            u.Phone AS UserPhone

        FROM dbo.Quotes q

        LEFT JOIN dbo.Users u
            ON q.UserId = u.UserId

        $whereSql

    ) AS QuoteRows

    WHERE RowNum BETWEEN ? AND ?

    ORDER BY RowNum
";


$queryParams = $params;

$queryParams[] =
    $offset + 1;

$queryParams[] =
    $offset + $limit;


$stmt = sqlsrv_query(
    $conn,
    $sql,
    $queryParams
);


$quotesList = [];

$queryError = '';


if ($stmt !== false) {

    while (
        $row = sqlsrv_fetch_array(
            $stmt,
            SQLSRV_FETCH_ASSOC
        )
    ) {

        $quotesList[] = $row;
    }


    sqlsrv_free_stmt(
        $stmt
    );

} else {

    $queryError =
        'Unable to load quotations right now.';
}


/*
|--------------------------------------------------------------------------
| HEADER / SIDEBAR
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/header.php';

require_once __DIR__ . '/../includes/sidebar.php';

?>


<style>

/* =========================================================
   QUOTATIONS PAGE
========================================================= */

:root {

    --quote-bg: #f5f7fa;

    --quote-card: #ffffff;

    --quote-card-2: #f8fafc;

    --quote-border: #dbe3ec;

    --quote-border-soft: #e8edf3;

    --quote-text: #172033;

    --quote-text-2: #475569;

    --quote-muted: #64748b;

    --quote-green: #059669;

    --quote-green-light: #10b981;

    --quote-green-bg: rgba(16, 185, 129, .10);

    --quote-blue: #0284c7;

    --quote-blue-bg: rgba(14, 165, 233, .10);

    --quote-orange: #d97706;

    --quote-orange-bg: rgba(245, 158, 11, .12);

    --quote-purple: #7c3aed;

    --quote-purple-bg: rgba(139, 92, 246, .10);

    --quote-red: #dc2626;

    --quote-red-bg: rgba(239, 68, 68, .10);

    --quote-shadow:
        0 12px 35px rgba(15, 23, 42, .07);
}


/* =========================================================
   DARK MODE
========================================================= */

html.dark-mode,
body.dark-mode,
html[data-theme="dark"],
body[data-theme="dark"],
.dark-mode {

    --quote-bg: #0a1119;

    --quote-card: #111b26;

    --quote-card-2: #0f1823;

    --quote-border: #26384a;

    --quote-border-soft: #1c2b39;

    --quote-text: #f1f5f9;

    --quote-text-2: #cbd5e1;

    --quote-muted: #8da0b4;

    --quote-green: #10b981;

    --quote-green-light: #34d399;

    --quote-green-bg: rgba(16, 185, 129, .12);

    --quote-blue: #38bdf8;

    --quote-blue-bg: rgba(56, 189, 248, .12);

    --quote-orange: #f59e0b;

    --quote-orange-bg: rgba(245, 158, 11, .12);

    --quote-purple: #a78bfa;

    --quote-purple-bg: rgba(139, 92, 246, .12);

    --quote-red: #ef4444;

    --quote-red-bg: rgba(239, 68, 68, .12);

    --quote-shadow:
        0 18px 45px rgba(0, 0, 0, .28);
}


/* =========================================================
   PAGE
========================================================= */

.quotes-page {

    width: 100%;

    max-width: 1600px;

    margin: 0 auto;

    padding: 28px 0 60px;

    color: var(--quote-text);
}


/* =========================================================
   PAGE HEADER
========================================================= */

.quote-page-header {

    display: flex;

    align-items: flex-end;

    justify-content: space-between;

    gap: 20px;

    padding-bottom: 20px;

    margin-bottom: 20px;

    border-bottom:
        1px solid var(--quote-border);

    flex-wrap: wrap;
}


.quote-title {

    margin: 0;

    color: var(--quote-text);

    font-size: 26px;

    font-weight: 800;

    line-height: 1.2;
}


.quote-subtitle {

    display: block;

    margin-top: 7px;

    color: var(--quote-muted);

    font-size: 12px;

    line-height: 1.6;
}


.quote-header-actions {

    display: flex;

    align-items: center;

    gap: 8px;

    flex-wrap: wrap;
}


/* =========================================================
   BUTTON
========================================================= */

.q-btn {

    min-height: 36px;

    padding: 0 13px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 6px;

    border: 1px solid var(--quote-border);

    border-radius: 8px;

    background: var(--quote-card);

    color: var(--quote-text-2) !important;

    font-size: 11px;

    font-weight: 700;

    text-decoration: none;

    cursor: pointer;

    transition: all .18s ease;
}


.q-btn:hover {

    border-color: var(--quote-green);

    background: var(--quote-green-bg);

    color: var(--quote-green) !important;

    transform: translateY(-1px);
}


.q-btn-blue {

    color: var(--quote-blue) !important;
}


.q-btn-blue:hover {

    color: var(--quote-blue) !important;

    border-color: var(--quote-blue);

    background: var(--quote-blue-bg);
}


.q-key {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-width: 18px;

    height: 17px;

    padding: 0 4px;

    border: 1px solid var(--quote-border);

    border-radius: 4px;

    background: var(--quote-card-2);

    color: var(--quote-muted);

    font-size: 9px;

    font-family: Consolas, monospace;
}


/* =========================================================
   ALERT
========================================================= */

.q-alert {

    margin-bottom: 18px;

    padding: 12px 15px;

    border: 1px solid;

    border-radius: 8px;

    font-size: 12px;

    font-weight: 700;
}


.q-alert-error {

    color: var(--quote-red);

    background: var(--quote-red-bg);

    border-color: rgba(239, 68, 68, .25);
}


/* =========================================================
   STAT CARDS
========================================================= */

.q-metrics {

    display: grid;

    grid-template-columns:
        repeat(4, minmax(0, 1fr));

    gap: 14px;

    margin-bottom: 20px;
}


.q-metric {

    min-height: 84px;

    padding: 16px 18px;

    display: flex;

    align-items: center;

    gap: 14px;

    border:
        1px solid var(--quote-border);

    border-radius: 12px;

    background:
        var(--quote-card);

    box-shadow:
        var(--quote-shadow);
}


.q-metric-icon {

    width: 44px;

    height: 44px;

    flex: 0 0 44px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 11px;

    font-size: 18px;
}


.icon-blue {

    color: var(--quote-blue);

    background:
        var(--quote-blue-bg);
}


.icon-orange {

    color: var(--quote-orange);

    background:
        var(--quote-orange-bg);
}


.icon-green {

    color: var(--quote-green);

    background:
        var(--quote-green-bg);
}


.icon-purple {

    color: var(--quote-purple);

    background:
        var(--quote-purple-bg);
}


.q-metric-value {

    color: var(--quote-text);

    font-size: 22px;

    line-height: 1.1;

    font-weight: 800;
}


.q-metric-label {

    margin-top: 4px;

    color: var(--quote-muted);

    font-size: 10px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .3px;
}


/* =========================================================
   BOX
========================================================= */

.q-box {

    overflow: hidden;

    border:
        1px solid var(--quote-border);

    border-radius: 12px;

    background:
        var(--quote-card);

    box-shadow:
        var(--quote-shadow);
}


/* =========================================================
   FILTERS
========================================================= */

.q-filter-box {

    padding: 14px 18px;

    margin-bottom: 20px;
}


.q-filter-form {

    display: grid;

    grid-template-columns:
        minmax(220px, 1fr)
        150px
        145px
        145px
        auto
        auto;

    gap: 9px;

    align-items: center;
}


.q-input,
.q-select {

    width: 100%;

    height: 38px;

    box-sizing: border-box;

    padding: 0 11px;

    border:
        1px solid var(--quote-border);

    border-radius: 8px;

    outline: none;

    background:
        var(--quote-card-2);

    color:
        var(--quote-text);

    font-size: 11.5px;

    transition: all .18s ease;
}


.q-input::placeholder {

    color:
        var(--quote-muted);
}


.q-input:focus,
.q-select:focus {

    border-color:
        var(--quote-green);

    box-shadow:
        0 0 0 3px var(--quote-green-bg);
}


.q-select option {

    background:
        var(--quote-card);

    color:
        var(--quote-text);
}


.filter-btn {

    height: 38px;

    padding: 0 15px;

    border: 0;

    border-radius: 8px;

    background:
        linear-gradient(
            135deg,
            #059669,
            #10b981
        );

    color: #ffffff;

    font-size: 11px;

    font-weight: 800;

    cursor: pointer;
}


.filter-btn:hover {

    background:
        linear-gradient(
            135deg,
            #047857,
            #059669
        );
}


/* =========================================================
   TABLE HEADER
========================================================= */

.q-box-header {

    min-height: 55px;

    padding: 0 18px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    border-bottom:
        1px solid var(--quote-border);

    background:
        var(--quote-card);
}


.q-box-title {

    margin: 0;

    color:
        var(--quote-text);

    font-size: 13px;

    font-weight: 800;

    text-transform: uppercase;
}


.q-found {

    color:
        var(--quote-green);
}


/* =========================================================
   TABLE
========================================================= */

.q-table-wrap {

    width: 100%;

    overflow-x: auto;

    -webkit-overflow-scrolling:
        touch;
}


.q-table {

    width: 100%;

    min-width: 1100px;

    border-collapse: collapse;
}


.q-table th {

    padding: 12px 14px;

    border-bottom:
        1px solid var(--quote-border);

    background:
        var(--quote-card-2);

    color:
        var(--quote-muted);

    font-size: 10px;

    font-weight: 800;

    text-align: left;

    text-transform: uppercase;

    letter-spacing: .35px;

    white-space: nowrap;
}


.q-table td {

    padding: 14px;

    border-bottom:
        1px solid var(--quote-border-soft);

    color:
        var(--quote-text-2);

    font-size: 12px;

    vertical-align: middle;
}


.q-table tbody tr {

    transition:
        background .15s ease;
}


.q-table tbody tr:hover {

    background:
        var(--quote-card-2);
}


.q-table tbody tr:last-child td {

    border-bottom: 0;
}


/* =========================================================
   QUOTE NUMBER
========================================================= */

.quote-number {

    color:
        var(--quote-blue);

    font-family:
        Consolas,
        Monaco,
        monospace;

    font-size: 12px;

    font-weight: 800;

    white-space: nowrap;
}


/* =========================================================
   COMPANY
========================================================= */

.company-name {

    color:
        var(--quote-text);

    font-size: 12px;

    font-weight: 800;

    line-height: 1.4;
}


.contact-name {

    margin-top: 2px;

    color:
        var(--quote-green);

    font-size: 11px;

    line-height: 1.4;
}


.contact-email {

    margin-top: 1px;

    color:
        var(--quote-muted);

    font-size: 10px;

    line-height: 1.4;
}


/* =========================================================
   AMOUNT
========================================================= */

.quote-amount {

    color:
        var(--quote-text);

    font-size: 13px;

    font-weight: 800;

    white-space: nowrap;
}


/* =========================================================
   STATUS
========================================================= */

.q-status {

    display: inline-flex;

    align-items: center;

    gap: 5px;

    min-height: 23px;

    padding: 0 9px;

    border: 1px solid;

    border-radius: 20px;

    font-size: 9.5px;

    font-weight: 800;

    text-transform: uppercase;

    white-space: nowrap;
}


.status-pending {

    color:
        var(--quote-orange);

    background:
        var(--quote-orange-bg);

    border-color:
        rgba(245, 158, 11, .25);
}


.status-approved {

    color:
        var(--quote-green);

    background:
        var(--quote-green-bg);

    border-color:
        rgba(16, 185, 129, .25);
}


.status-rejected {

    color:
        var(--quote-red);

    background:
        var(--quote-red-bg);

    border-color:
        rgba(239, 68, 68, .25);
}


.status-sent {

    color:
        var(--quote-blue);

    background:
        var(--quote-blue-bg);

    border-color:
        rgba(14, 165, 233, .25);
}


/* =========================================================
   DATE
========================================================= */

.q-date {

    color:
        var(--quote-muted);

    font-size: 11px;

    white-space: nowrap;
}


/* =========================================================
   ORDER
========================================================= */

.order-link {

    color:
        var(--quote-green) !important;

    font-size: 11px;

    font-weight: 800;

    text-decoration: none;
}


.order-link:hover {

    text-decoration: underline;
}


.not-converted {

    color:
        var(--quote-muted);

    font-size: 10.5px;
}


/* =========================================================
   ONLY VIEW BUTTON
========================================================= */

.q-actions {

    display: flex;

    align-items: center;

    justify-content: flex-end;
}


.q-view-btn {

    min-width: 62px;

    height: 32px;

    padding: 0 12px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 5px;

    border:
        1px solid var(--quote-border);

    border-radius: 7px;

    background:
        var(--quote-card-2);

    color:
        var(--quote-text-2) !important;

    font-size: 10.5px;

    font-weight: 800;

    text-decoration: none;

    transition: all .18s ease;
}


.q-view-btn:hover {

    border-color:
        var(--quote-blue);

    background:
        var(--quote-blue-bg);

    color:
        var(--quote-blue) !important;

    transform:
        translateY(-1px);
}


/* =========================================================
   EMPTY
========================================================= */

.q-empty {

    padding: 65px 20px !important;

    text-align: center;

    color:
        var(--quote-muted) !important;
}


.q-empty-icon {

    margin-bottom: 8px;

    font-size: 30px;

    opacity: .65;
}


.q-empty-title {

    color:
        var(--quote-text);

    font-size: 13px;

    font-weight: 800;
}


.q-empty-text {

    margin-top: 5px;

    font-size: 11px;
}


/* =========================================================
   PAGINATION
========================================================= */

.q-pagination {

    min-height: 62px;

    padding: 10px 18px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 12px;

    flex-wrap: wrap;

    border-top:
        1px solid var(--quote-border);
}


.q-page-info {

    color:
        var(--quote-muted);

    font-size: 11px;
}


.q-page-links {

    display: flex;

    align-items: center;

    gap: 5px;

    flex-wrap: wrap;
}


.q-page-link {

    min-width: 30px;

    height: 30px;

    padding: 0 8px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    border:
        1px solid var(--quote-border);

    border-radius: 6px;

    background:
        var(--quote-card-2);

    color:
        var(--quote-text-2);

    font-size: 10px;

    font-weight: 800;

    text-decoration: none;
}


.q-page-link:hover {

    color:
        var(--quote-green);

    border-color:
        var(--quote-green);

    background:
        var(--quote-green-bg);
}


.q-page-active {

    color:
        var(--quote-green) !important;

    border-color:
        var(--quote-green);

    background:
        var(--quote-green-bg);
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1150px) {

    .q-metrics {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }


    .q-filter-form {

        grid-template-columns:
            minmax(220px, 1fr)
            150px
            150px;
    }
}


@media (max-width: 800px) {

    .quotes-page {

        padding:
            20px 0 40px;
    }


    .quote-title {

        font-size: 22px;
    }


    .q-filter-form {

        grid-template-columns:
            1fr;
    }


    .q-filter-form .q-btn,
    .q-filter-form .filter-btn {

        width: 100%;
    }


    .q-metrics {

        grid-template-columns:
            1fr;
    }
}


@media (max-width: 520px) {

    .quote-page-header {

        align-items:
            flex-start;
    }


    .quote-header-actions {

        width:
            100%;
    }


    .quote-header-actions .q-btn {

        flex:
            1;
    }


    .q-box-header {

        padding:
            13px 14px;
    }


    .q-table td,
    .q-table th {

        padding:
            12px 10px;
    }


    .q-pagination {

        justify-content:
            center;
    }


    .q-page-info {

        width:
            100%;

        text-align:
            center;
    }
}


/* =========================================================
   PRINT
========================================================= */

@media print {

    body {

        background:
            #ffffff !important;
    }


    .quotes-page {

        max-width:
            none;

        padding:
            0;
    }


    .no-print,
    .quote-header-actions,
    .q-filter-box,
    .q-pagination {

        display:
            none !important;
    }


    .q-box,
    .q-metric {

        box-shadow:
            none !important;
    }


    .q-table {

        min-width:
            0;
    }


    .q-table th,
    .q-table td {

        color:
            #111827 !important;

        background:
            #ffffff !important;
    }
}

</style>


<main class="main">

    <section class="content">

        <div class="quotes-page">


            <!-- =====================================================
                 HEADER
            ====================================================== -->

            <div class="quote-page-header">

                <div>

                    <h1 class="quote-title">
                        Price Quotations
                    </h1>

                    <span class="quote-subtitle">
                        Manage requested price quotations,
                        bulk estimates, expiry dates,
                        and order conversions.
                    </span>

                </div>


                <div class="quote-header-actions no-print">


                    <!-- CSV -->

                    <a
                        href="?<?= e(
                            http_build_query(
                                array_merge(
                                    $_GET,
                                    [
                                        'export' => 'csv'
                                    ]
                                )
                            )
                        ) ?>"
                        class="q-btn q-btn-blue"
                    >
                        📥 Export CSV
                    </a>


                    <!-- PRINT -->

                    <button
                        type="button"
                        class="q-btn q-btn-blue"
                        onclick="window.print();"
                    >
                        🖨 Print

                        <span class="q-key">
                            P
                        </span>

                    </button>


                    <!-- BACK -->

                    <a
                        href="../wholesale/index.php"
                        class="q-btn"
                    >
                        ← Back to Wholesale

                        <span class="q-key">
                            W
                        </span>

                    </a>


                </div>

            </div>


            <!-- =====================================================
                 ERROR
            ====================================================== -->

            <?php if ($queryError !== ''): ?>

                <div class="q-alert q-alert-error">
                    ! <?= e($queryError) ?>
                </div>

            <?php endif; ?>


            <!-- =====================================================
                 STATISTICS
            ====================================================== -->

            <div class="q-metrics">


                <!-- TOTAL -->

                <div class="q-metric">

                    <div class="q-metric-icon icon-blue">
                        📄
                    </div>

                    <div>

                        <div class="q-metric-value">
                            <?= number_format(
                                $totalQuotes
                            ) ?>
                        </div>

                        <div class="q-metric-label">
                            Total Quotes
                        </div>

                    </div>

                </div>


                <!-- PENDING -->

                <div class="q-metric">

                    <div class="q-metric-icon icon-orange">
                        ⏳
                    </div>

                    <div>

                        <div class="q-metric-value">
                            <?= number_format(
                                $pendingQuotes
                            ) ?>
                        </div>

                        <div class="q-metric-label">
                            Pending Response
                        </div>

                    </div>

                </div>


                <!-- APPROVED -->

                <div class="q-metric">

                    <div class="q-metric-icon icon-green">
                        ✓
                    </div>

                    <div>

                        <div class="q-metric-value">
                            <?= number_format(
                                $approvedQuotes
                            ) ?>
                        </div>

                        <div class="q-metric-label">
                            Accepted Quotes
                        </div>

                    </div>

                </div>


                <!-- CONVERTED -->

                <div class="q-metric">

                    <div class="q-metric-icon icon-purple">
                        📦
                    </div>

                    <div>

                        <div class="q-metric-value">
                            <?= number_format(
                                $convertedOrders
                            ) ?>
                        </div>

                        <div class="q-metric-label">
                            Converted to Orders
                        </div>

                    </div>

                </div>


            </div>


            <!-- =====================================================
                 FILTERS
            ====================================================== -->

            <div class="q-box q-filter-box no-print">

                <form
                    method="GET"
                    class="q-filter-form"
                >


                    <!-- SEARCH -->

                    <input
                        type="text"
                        name="q"
                        class="q-input"
                        placeholder="Search quote #, company, contact..."
                        value="<?= e(
                            $searchQuery
                        ) ?>"
                        autocomplete="off"
                    >


                    <!-- STATUS -->

                    <select
                        name="status_filter"
                        class="q-select"
                    >

                        <option value="">
                            All Statuses
                        </option>

                        <option
                            value="Pending"
                            <?= $statusFilter === 'Pending'
                                ? 'selected'
                                : '' ?>
                        >
                            Pending
                        </option>

                        <option
                            value="Sent"
                            <?= $statusFilter === 'Sent'
                                ? 'selected'
                                : '' ?>
                        >
                            Sent
                        </option>

                        <option
                            value="Approved"
                            <?= $statusFilter === 'Approved'
                                ? 'selected'
                                : '' ?>
                        >
                            Approved / Accepted
                        </option>

                        <option
                            value="Rejected"
                            <?= $statusFilter === 'Rejected'
                                ? 'selected'
                                : '' ?>
                        >
                            Rejected
                        </option>

                    </select>


                    <!-- FROM -->

                    <input
                        type="date"
                        name="from_date"
                        class="q-input"
                        value="<?= e(
                            $fromDate
                        ) ?>"
                    >


                    <!-- TO -->

                    <input
                        type="date"
                        name="to_date"
                        class="q-input"
                        value="<?= e(
                            $toDate
                        ) ?>"
                    >


                    <!-- FILTER -->

                    <button
                        type="submit"
                        class="filter-btn"
                    >
                        Filter
                    </button>


                    <!-- RESET -->

                    <a
                        href="index.php"
                        class="q-btn"
                    >
                        Reset
                    </a>


                </form>

            </div>


            <!-- =====================================================
                 QUOTATION TABLE
            ====================================================== -->

            <div class="q-box">


                <!-- TABLE TITLE -->

                <div class="q-box-header">

                    <h2 class="q-box-title">

                        Quotation Requests Roster

                        <span class="q-found">
                            (<?= number_format(
                                $totalRows
                            ) ?> found)
                        </span>

                    </h2>

                </div>


                <!-- TABLE -->

                <div class="q-table-wrap">

                    <table class="q-table">

                        <thead>

                            <tr>

                                <th>
                                    Quote #
                                </th>

                                <th>
                                    Company / Contact
                                </th>

                                <th>
                                    Quoted Amount
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Expiry Date
                                </th>

                                <th>
                                    Created
                                </th>

                                <th>
                                    Converted Order
                                </th>

                                <th
                                    style="
                                        text-align:right;
                                    "
                                >
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (
                            empty($quotesList)
                        ): ?>


                            <tr>

                                <td
                                    colspan="8"
                                    class="q-empty"
                                >

                                    <div class="q-empty-icon">
                                        📄
                                    </div>

                                    <div class="q-empty-title">
                                        No price quotations found
                                    </div>

                                    <div class="q-empty-text">
                                        No quotations match
                                        your current filters.
                                    </div>

                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach (
                                $quotesList
                                as $quote
                            ): ?>


                                <?php

                                $status =
                                    (string)(
                                        $quote['Status']
                                        ?? 'Pending'
                                    );

                                $quoteNumber =
                                    getQuoteNumber(
                                        $quote
                                    );

                                $statusClass =
                                    getStatusClass(
                                        $status
                                    );

                                $statusIcon =
                                    getStatusIcon(
                                        $status
                                    );

                                ?>


                                <tr>


                                    <!-- QUOTE NUMBER -->

                                    <td>

                                        <span
                                            class="quote-number"
                                        >
                                            <?= e(
                                                $quoteNumber
                                            ) ?>
                                        </span>

                                    </td>


                                    <!-- COMPANY -->

                                    <td>

                                        <div
                                            class="company-name"
                                        >
                                            <?= e(
                                                $quote[
                                                    'CompanyName'
                                                ]
                                                ??
                                                'Individual Client'
                                            ) ?>
                                        </div>


                                        <div
                                            class="contact-name"
                                        >
                                            <?= e(
                                                $quote[
                                                    'ContactPerson'
                                                ]
                                                ?: '—'
                                            ) ?>
                                        </div>


                                        <?php if (
                                            !empty(
                                                $quote[
                                                    'UserEmail'
                                                ]
                                            )
                                        ): ?>

                                            <div
                                                class="contact-email"
                                            >
                                                <?= e(
                                                    $quote[
                                                        'UserEmail'
                                                    ]
                                                ) ?>
                                            </div>

                                        <?php endif; ?>

                                    </td>


                                    <!-- AMOUNT -->

                                    <td>

                                        <span
                                            class="quote-amount"
                                        >
                                            <?= e(
                                                formatMoney(
                                                    $quote[
                                                        'TotalQuotedAmount'
                                                    ]
                                                    ?? 0
                                                )
                                            ) ?>
                                        </span>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <span
                                            class="
                                                q-status
                                                <?= e(
                                                    $statusClass
                                                ) ?>
                                            "
                                        >

                                            <span>
                                                <?= e(
                                                    $statusIcon
                                                ) ?>
                                            </span>

                                            <?= e(
                                                $status
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- EXPIRY -->

                                    <td>

                                        <span
                                            class="q-date"
                                        >
                                            <?= e(
                                                formatDateValue(
                                                    $quote[
                                                        'ExpiryDate'
                                                    ]
                                                    ?? null
                                                )
                                            ) ?>
                                        </span>

                                    </td>


                                    <!-- CREATED -->

                                    <td>

                                        <span
                                            class="q-date"
                                        >
                                            <?= e(
                                                formatDateValue(
                                                    $quote[
                                                        'CreatedAt'
                                                    ]
                                                    ?? null
                                                )
                                            ) ?>
                                        </span>

                                    </td>


                                    <!-- ORDER -->

                                    <td>

                                        <?php if (
                                            !empty(
                                                $quote[
                                                    'ConvertedOrderId'
                                                ]
                                            )
                                        ): ?>

                                            <a
                                                href="../orders/view.php?id=<?= (int)$quote['ConvertedOrderId'] ?>"
                                                class="order-link"
                                            >
                                                Order
                                                #<?= e(
                                                    $quote[
                                                        'ConvertedOrderId'
                                                    ]
                                                ) ?>
                                            </a>

                                        <?php else: ?>

                                            <span
                                                class="not-converted"
                                            >
                                                Not Converted
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- ONLY VIEW BUTTON -->

                                    <td>

                                        <div
                                            class="q-actions"
                                        >

                                            <a
                                                href="view.php?id=<?= (int)$quote['QuoteId'] ?>"
                                                class="q-view-btn"
                                                title="View quotation details"
                                            >
                                                👁 View
                                            </a>

                                        </div>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php endif; ?>


                        </tbody>

                    </table>

                </div>


                <!-- =================================================
                     PAGINATION
                ================================================== -->

                <?php if (
                    $totalPages > 1
                ): ?>


                    <div
                        class="q-pagination no-print"
                    >


                        <div
                            class="q-page-info"
                        >

                            Page
                            <strong>
                                <?= $page ?>
                            </strong>

                            of

                            <strong>
                                <?= $totalPages ?>
                            </strong>

                            ·

                            <?= number_format(
                                $totalRows
                            ) ?>

                            quotations

                        </div>


                        <div
                            class="q-page-links"
                        >


                            <?php if (
                                $page > 1
                            ): ?>


                                <a
                                    href="?<?= e(
                                        http_build_query(
                                            array_merge(
                                                $_GET,
                                                [
                                                    'page' => 1
                                                ]
                                            )
                                        )
                                    ) ?>"
                                    class="q-page-link"
                                >
                                    «
                                </a>


                                <a
                                    href="?<?= e(
                                        http_build_query(
                                            array_merge(
                                                $_GET,
                                                [
                                                    'page' =>
                                                        $page - 1
                                                ]
                                            )
                                        )
                                    ) ?>"
                                    class="q-page-link"
                                >
                                    ‹
                                </a>


                            <?php endif; ?>


                            <?php

                            $startPage =
                                max(
                                    1,
                                    $page - 3
                                );

                            $endPage =
                                min(
                                    $totalPages,
                                    $page + 3
                                );

                            ?>


                            <?php for (
                                $i = $startPage;
                                $i <= $endPage;
                                $i++
                            ): ?>


                                <a
                                    href="?<?= e(
                                        http_build_query(
                                            array_merge(
                                                $_GET,
                                                [
                                                    'page' => $i
                                                ]
                                            )
                                        )
                                    ) ?>"
                                    class="
                                        q-page-link
                                        <?= $i === $page
                                            ? 'q-page-active'
                                            : ''
                                        ?>
                                    "
                                >
                                    <?= $i ?>
                                </a>


                            <?php endfor; ?>


                            <?php if (
                                $page < $totalPages
                            ): ?>


                                <a
                                    href="?<?= e(
                                        http_build_query(
                                            array_merge(
                                                $_GET,
                                                [
                                                    'page' =>
                                                        $page + 1
                                                ]
                                            )
                                        )
                                    ) ?>"
                                    class="q-page-link"
                                >
                                    ›
                                </a>


                                <a
                                    href="?<?= e(
                                        http_build_query(
                                            array_merge(
                                                $_GET,
                                                [
                                                    'page' =>
                                                        $totalPages
                                                ]
                                            )
                                        )
                                    ) ?>"
                                    class="q-page-link"
                                >
                                    »
                                </a>


                            <?php endif; ?>


                        </div>

                    </div>


                <?php endif; ?>


            </div>


        </div>

    </section>

</main>


<script>

/*
|--------------------------------------------------------------------------
| KEYBOARD SHORTCUTS
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'keydown',
    function (event) {

        /*
         * Do not activate shortcuts
         * while typing.
         */

        const tag =
            document.activeElement
                ? document.activeElement.tagName
                : '';


        if (
            tag === 'INPUT'
            || tag === 'SELECT'
            || tag === 'TEXTAREA'
        ) {
            return;
        }


        /*
         * "/" = Search
         */

        if (
            event.key === '/'
            && !event.ctrlKey
            && !event.altKey
            && !event.metaKey
        ) {

            event.preventDefault();

            const search =
                document.querySelector(
                    'input[name="q"]'
                );


            if (search) {

                search.focus();

                search.select();
            }

            return;
        }


        /*
         * P = Print
         */

        if (
            event.key.toUpperCase()
            === 'P'
        ) {

            event.preventDefault();

            window.print();

            return;
        }


        /*
         * W = Wholesale
         */

        if (
            event.key.toUpperCase()
            === 'W'
        ) {

            event.preventDefault();

            window.location.href =
                '../wholesale/index.php';

            return;
        }

    }
);

</script>


<?php

/*
|--------------------------------------------------------------------------
| FOOTER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/footer.php';

?>