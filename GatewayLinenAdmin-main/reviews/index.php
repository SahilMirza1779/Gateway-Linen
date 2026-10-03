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

$activeMenu = 'reviews';
$pageTitle  = 'GatewayLinen | Product Reviews Management';

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] = $_SESSION['admin_username'] ?? 'GatewayLinen Administrator';
}

if (!isset($_SESSION['admin_role'])) {
    $_SESSION['admin_role'] = 'Administrator';
}

/*
|--------------------------------------------------------------------------
| HELPER
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

function formatReviewDate($value): string
{
    if ($value instanceof DateTimeInterface) {
        return $value->format('d M Y, h:i A');
    }

    if (empty($value)) {
        return '—';
    }

    $timestamp = strtotime((string)$value);

    if ($timestamp === false) {
        return '—';
    }

    return date('d M Y, h:i A', $timestamp);
}

/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['reviews_csrf_token'])) {
    $_SESSION['reviews_csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['reviews_csrf_token'];

/*
|--------------------------------------------------------------------------
| URL MESSAGES
|--------------------------------------------------------------------------
*/

$actionMessage = trim((string)($_GET['success'] ?? ''));
$actionError   = trim((string)($_GET['error'] ?? ''));

/*
|--------------------------------------------------------------------------
| POST ACTIONS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $postedToken = (string)($_POST['csrf_token'] ?? '');

    if (
        empty($postedToken) ||
        empty($_SESSION['reviews_csrf_token']) ||
        !hash_equals($_SESSION['reviews_csrf_token'], $postedToken)
    ) {
        $actionError = 'Security verification failed. Please refresh the page and try again.';
    } else {

        $reviewId  = (int)($_POST['review_id'] ?? 0);
        $actionType = trim((string)($_POST['action'] ?? ''));

        if ($reviewId <= 0) {

            $actionError = 'Invalid review selected.';

        } elseif ($actionType === 'toggle_approval') {

            /*
            |------------------------------------------------------------------
            | Get Current Status From Database
            |------------------------------------------------------------------
            | Do not trust hidden current_status from browser.
            */

            $statusSql = "
                SELECT TOP 1
                    ReviewId,
                    IsApproved
                FROM dbo.ProductReviews
                WHERE ReviewId = ?
            ";

            $statusStmt = sqlsrv_query(
                $conn,
                $statusSql,
                [$reviewId]
            );

            if ($statusStmt === false) {

                $actionError = 'Unable to read the current review status.';

            } else {

                $reviewRow = sqlsrv_fetch_array(
                    $statusStmt,
                    SQLSRV_FETCH_ASSOC
                );

                sqlsrv_free_stmt($statusStmt);

                if (!$reviewRow) {

                    $actionError = 'Review not found.';

                } else {

                    $currentStatus = (int)($reviewRow['IsApproved'] ?? 0);
                    $newStatus     = $currentStatus === 1 ? 0 : 1;

                    $updateSql = "
                        UPDATE dbo.ProductReviews
                        SET IsApproved = ?
                        WHERE ReviewId = ?
                    ";

                    $updateStmt = sqlsrv_query(
                        $conn,
                        $updateSql,
                        [$newStatus, $reviewId]
                    );

                    if ($updateStmt === false) {

                        $actionError = 'Failed to update review status.';

                    } else {

                        sqlsrv_free_stmt($updateStmt);

                        $_SESSION['reviews_csrf_token'] =
                            bin2hex(random_bytes(32));

                        $message = $newStatus === 1
                            ? 'Review approved successfully.'
                            : 'Review moved to pending successfully.';

                        header(
                            'Location: index.php?success=' .
                            urlencode($message)
                        );
                        exit;
                    }
                }
            }

        } elseif ($actionType === 'delete') {

            /*
            |------------------------------------------------------------------
            | Check Review Exists Before Delete
            |------------------------------------------------------------------
            */

            $existsSql = "
                SELECT TOP 1 ReviewId
                FROM dbo.ProductReviews
                WHERE ReviewId = ?
            ";

            $existsStmt = sqlsrv_query(
                $conn,
                $existsSql,
                [$reviewId]
            );

            if ($existsStmt === false) {

                $actionError = 'Unable to verify the selected review.';

            } else {

                $existsRow = sqlsrv_fetch_array(
                    $existsStmt,
                    SQLSRV_FETCH_ASSOC
                );

                sqlsrv_free_stmt($existsStmt);

                if (!$existsRow) {

                    $actionError = 'Review not found or already deleted.';

                } else {

                    $deleteSql = "
                        DELETE FROM dbo.ProductReviews
                        WHERE ReviewId = ?
                    ";

                    $deleteStmt = sqlsrv_query(
                        $conn,
                        $deleteSql,
                        [$reviewId]
                    );

                    if ($deleteStmt === false) {

                        $sqlErrors = sqlsrv_errors();

                        $actionError =
                            $sqlErrors[0]['message']
                            ?? 'Failed to delete review.';

                    } else {

                        sqlsrv_free_stmt($deleteStmt);

                        $_SESSION['reviews_csrf_token'] =
                            bin2hex(random_bytes(32));

                        header(
                            'Location: index.php?success=' .
                            urlencode('Review deleted successfully.')
                        );
                        exit;
                    }
                }
            }

        } else {

            $actionError = 'Invalid review action.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| FETCH REVIEWS
|--------------------------------------------------------------------------
*/

$reviewsList = [];
$queryError  = '';

$sql = "
    SELECT
        r.ReviewId,
        r.ProductId,
        r.UserId,
        r.Rating,
        r.Comment,
        r.IsApproved,
        r.CreatedAt,

        p.Name AS ProductName,

        u.Email AS UserEmail

    FROM dbo.ProductReviews r

    LEFT JOIN dbo.Products p
        ON r.ProductId = p.ProductId

    LEFT JOIN dbo.Users u
        ON r.UserId = u.UserId

    ORDER BY r.ReviewId DESC
";

$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {

    $queryError = 'Unable to fetch reviews from database.';

} else {

    while ($row = sqlsrv_fetch_array(
        $stmt,
        SQLSRV_FETCH_ASSOC
    )) {
        $reviewsList[] = $row;
    }

    sqlsrv_free_stmt($stmt);
}

/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$totalReviews    = count($reviewsList);
$approvedReviews = 0;
$pendingReviews  = 0;
$fiveStarReviews = 0;
$ratingTotal     = 0;

foreach ($reviewsList as $review) {

    $rating = (int)($review['Rating'] ?? 0);

    if (!empty($review['IsApproved'])) {
        $approvedReviews++;
    } else {
        $pendingReviews++;
    }

    if ($rating === 5) {
        $fiveStarReviews++;
    }

    if ($rating >= 1 && $rating <= 5) {
        $ratingTotal += $rating;
    }
}

$averageRating = $totalReviews > 0
    ? round($ratingTotal / $totalReviews, 1)
    : 0;

/*
|--------------------------------------------------------------------------
| HEADER / SIDEBAR
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<style>

/* ==========================================================================
   GATEWAYLINEN REVIEWS PAGE
   ========================================================================== */

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
    --text-mute: #71859a;

    --green: #10b981;
    --green-dark: #059669;
    --green-soft: rgba(16,185,129,.12);

    --blue: #38bdf8;
    --blue-soft: rgba(56,189,248,.12);

    --amber: #f59e0b;
    --amber-soft: rgba(245,158,11,.12);

    --red: #ef4444;
    --red-soft: rgba(239,68,68,.12);

    --purple: #8b5cf6;
    --purple-soft: rgba(139,92,246,.12);

    --shadow: 0 15px 40px rgba(0,0,0,.22);

    --radius: 11px;
}

/* LIGHT THEME */

html[data-theme="light"] {

    --bg-page: #f4f7fb;
    --bg-card: #ffffff;
    --bg-card-alt: #f8fafc;
    --bg-header: #f8fafc;
    --bg-hover: #eef2f7;
    --bg-input: #ffffff;

    --border: #d7e0ea;
    --border-soft: #e6ecf2;

    --text-hi: #172033;
    --text-body: #475569;
    --text-mute: #64748b;

    --green: #059669;
    --green-dark: #047857;
    --green-soft: rgba(16,185,129,.11);

    --blue: #3b82f6;
    --blue-soft: rgba(59,130,246,.10);

    --amber: #f59e0b;
    --amber-soft: rgba(245,158,11,.12);

    --red: #ef4444;
    --red-soft: rgba(239,68,68,.10);

    --purple: #8b5cf6;
    --purple-soft: rgba(139,92,246,.10);

    --shadow: 0 15px 40px rgba(15,23,42,.08);
}

/* BASE */

.reviews-page,
.reviews-page * {
    box-sizing: border-box;
}

html,
body,
.main,
.content {
    background: var(--bg-page) !important;
    color: var(--text-body) !important;
}

.reviews-page {
    width: 100%;
    max-width: 1600px;
    margin: 0 auto;
    padding: 0 0 35px;
}

/* PAGE HEADER */

.reviews-page-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 24px;

    margin-bottom: 20px;
    padding-bottom: 18px;

    border-bottom: 1px solid var(--border);
}

.reviews-breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;

    margin-bottom: 8px;

    color: var(--text-mute);

    font-size: 11px;
    font-weight: 800;

    text-transform: uppercase;
    letter-spacing: .35px;
}

.reviews-breadcrumb .current {
    color: var(--green);
}

.reviews-page-header h1 {
    margin: 0;

    color: var(--text-hi);

    font-size: 26px;
    line-height: 1.2;
    font-weight: 800;
}

.reviews-page-header p {
    margin: 7px 0 0;

    color: var(--text-mute);

    font-size: 12px;
}

/* HEADER BUTTONS */

.header-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    flex-wrap: wrap;
}

.btn {
    min-height: 38px;

    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;

    padding: 0 13px;

    border: 1px solid var(--border);
    border-radius: 8px;

    background: var(--bg-input);

    color: var(--text-body) !important;

    font-size: 11px;
    font-weight: 800;

    text-decoration: none;

    cursor: pointer;

    transition:
        background .18s ease,
        border-color .18s ease,
        color .18s ease,
        transform .18s ease;
}

.btn:hover {
    border-color: var(--green);
    background: var(--green-soft);
    color: var(--green) !important;
}

.btn:active {
    transform: translateY(1px);
}

.btn-blue {
    color: var(--blue) !important;
}

.btn-primary {
    border-color: transparent;
    background: linear-gradient(
        135deg,
        var(--green-dark),
        var(--green)
    );

    color: #fff !important;

    box-shadow:
        0 7px 20px rgba(16,185,129,.18);
}

.btn-primary:hover {
    color: #fff !important;
    transform: translateY(-1px);
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
    border: 1px solid rgba(16,185,129,.30);

    background: var(--green-soft);

    color: #34d399;
}

html[data-theme="light"] .notice-success {
    color: #047857;
}

.notice-error {
    border: 1px solid rgba(239,68,68,.30);

    background: var(--red-soft);

    color: #fca5a5;
}

html[data-theme="light"] .notice-error {
    color: #b91c1c;
}

/* STATS */

.reviews-stats {
    display: grid;

    grid-template-columns:
        repeat(5, minmax(0, 1fr));

    gap: 12px;

    margin-bottom: 20px;
}

.reviews-stat-item {
    display: flex;
    align-items: center;
    gap: 12px;

    min-width: 0;

    padding: 15px 17px;

    background: var(--bg-card);

    border: 1px solid var(--border);
    border-radius: var(--radius);

    box-shadow: 0 5px 18px rgba(0,0,0,.03);
}

.reviews-stat-icon {
    flex: 0 0 auto;

    width: 38px;
    height: 38px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 9px;

    background: var(--green-soft);

    color: var(--green);

    font-size: 15px;
    font-weight: 800;
}

.reviews-stat-item:nth-child(2) .reviews-stat-icon {
    background: var(--blue-soft);
    color: var(--blue);
}

.reviews-stat-item:nth-child(3) .reviews-stat-icon {
    background: var(--amber-soft);
    color: var(--amber);
}

.reviews-stat-item:nth-child(4) .reviews-stat-icon {
    background: var(--purple-soft);
    color: var(--purple);
}

.reviews-stat-item:nth-child(5) .reviews-stat-icon {
    background: var(--red-soft);
    color: var(--red);
}

.reviews-stat-label {
    color: var(--text-mute);

    font-size: 9px;
    font-weight: 800;

    text-transform: uppercase;
    letter-spacing: .3px;
}

.reviews-stat-value {
    margin-top: 2px;

    color: var(--text-hi);

    font-size: 19px;
    line-height: 1.2;
    font-weight: 800;
}

/* CONTENT */

.reviews-content {
    overflow: hidden;

    background: var(--bg-card);

    border: 1px solid var(--border);
    border-radius: 12px;

    box-shadow: var(--shadow);
}

.reviews-content-header {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 20px;

    padding: 17px 20px;

    border-bottom: 1px solid var(--border);
}

.reviews-content-title h2 {
    margin: 0;

    color: var(--text-hi);

    font-size: 16px;
    font-weight: 800;
}

.reviews-content-title p {
    margin: 4px 0 0;

    color: var(--text-mute);

    font-size: 11px;
}

/* FILTERS */

.reviews-filters {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.reviews-search-wrap {
    position: relative;

    width: 310px;
}

.reviews-search-icon {
    position: absolute;

    left: 12px;
    top: 50%;

    transform: translateY(-50%);

    color: var(--text-mute);

    font-size: 17px;

    pointer-events: none;
}

.reviews-search,
.reviews-status-filter {
    height: 37px;

    border: 1px solid var(--border);
    border-radius: 8px;

    outline: none;

    background: var(--bg-input);
    color: var(--text-hi);

    font-family: inherit;
    font-size: 12px;

    transition: .18s;
}

.reviews-search {
    width: 100%;

    padding: 0 12px 0 35px;
}

.reviews-status-filter {
    min-width: 140px;

    padding: 0 10px;

    cursor: pointer;
}

.reviews-search:focus,
.reviews-status-filter:focus {
    border-color: var(--green);

    box-shadow:
        0 0 0 3px rgba(16,185,129,.10);
}

/* SUMMARY */

.reviews-table-summary {
    display: flex;
    align-items: center;
    justify-content: space-between;

    min-height: 44px;

    padding: 10px 20px;

    border-bottom: 1px solid var(--border);
}

.reviews-result-text {
    color: var(--text-mute);

    font-size: 11px;
    font-weight: 600;
}

.reviews-result-text strong {
    color: var(--text-hi);
}

/* TABLE */

.reviews-table-wrapper {
    width: 100%;
    overflow-x: auto;
}

.reviews-table {
    width: 100%;
    min-width: 1120px;

    border-collapse: collapse;
}

.reviews-table th {
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
}

.reviews-table td {
    padding: 13px 16px;

    background: transparent;

    border-bottom: 1px solid var(--border-soft);

    color: var(--text-body);

    font-size: 12px;

    vertical-align: middle;
}

.reviews-table tbody tr {
    transition: background .15s ease;
}

.reviews-table tbody tr:hover {
    background: var(--bg-hover);
}

/* SERIAL */

.review-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;

    min-width: 40px;
    height: 27px;

    padding: 0 8px;

    background: var(--bg-input);

    border: 1px solid var(--border);
    border-radius: 6px;

    color: var(--blue);

    font-size: 11px;
    font-weight: 800;
}

/* PRODUCT */

.review-product-name {
    display: block;

    max-width: 260px;

    overflow: hidden;

    color: var(--text-hi);

    font-size: 13px;
    font-weight: 800;

    text-overflow: ellipsis;
    white-space: nowrap;
}

.review-user-email {
    display: block;

    max-width: 260px;

    margin-top: 3px;

    overflow: hidden;

    color: var(--text-mute);

    font-size: 10px;

    text-overflow: ellipsis;
    white-space: nowrap;
}

/* RATING */

.review-rating {
    white-space: nowrap;
}

.review-stars {
    color: var(--amber);

    font-size: 13px;
    font-weight: 800;

    letter-spacing: 1px;
}

.review-stars-empty {
    color: var(--border);
}

.review-rating-number {
    margin-left: 5px;

    color: var(--text-mute);

    font-size: 10px;
    font-weight: 700;
}

/* COMMENT */

.review-comment {
    max-width: 350px;

    color: var(--text-body);

    font-size: 11px;
    line-height: 1.55;

    word-break: break-word;
}

/* BADGES */

.badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;

    min-height: 25px;

    padding: 4px 10px;

    border-radius: 20px;

    font-size: 9px;
    font-weight: 800;

    text-transform: uppercase;
    letter-spacing: .2px;
}

.badge-approved {
    background: var(--green-soft);

    border: 1px solid rgba(16,185,129,.22);

    color: var(--green);
}

.badge-pending {
    background: var(--amber-soft);

    border: 1px solid rgba(245,158,11,.22);

    color: var(--amber);
}

/* DATE */

.review-date {
    color: var(--text-mute);

    font-size: 10px;
    white-space: nowrap;
}

/* ACTIONS */

.review-actions {
    display: inline-flex;

    align-items: center;
    justify-content: flex-end;

    gap: 6px;
}

.review-action-form {
    margin: 0;
    padding: 0;
}

.review-action {
    min-width: 32px;
    height: 32px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    padding: 0 9px;

    border: 1px solid var(--border);
    border-radius: 7px;

    background: var(--bg-input);

    color: var(--text-body) !important;

    font-size: 10px;
    font-weight: 800;

    cursor: pointer;

    transition: .18s;
}

.review-action:hover {
    border-color: var(--green);

    background: var(--green-soft);

    color: var(--green) !important;
}

.review-action-delete:hover {
    border-color: rgba(239,68,68,.5);

    background: var(--red-soft);

    color: var(--red) !important;
}

/* EMPTY */

.reviews-empty,
.reviews-no-result {
    padding: 70px 20px;

    text-align: center;
}

.reviews-empty-icon {
    width: 55px;
    height: 55px;

    display: flex;
    align-items: center;
    justify-content: center;

    margin: 0 auto 14px;

    border-radius: 14px;

    background: var(--green-soft);

    color: var(--green);

    font-size: 23px;
}

.reviews-empty h3,
.reviews-no-result h3 {
    margin: 0;

    color: var(--text-hi);

    font-size: 15px;
}

.reviews-empty p,
.reviews-no-result p {
    margin: 7px 0 0;

    color: var(--text-mute);

    font-size: 11px;
}

/* SHORTCUT BOX */

.shortcut-help-box {
    margin-top: 16px;

    padding: 16px 20px;

    background: var(--bg-card);

    border: 1px solid var(--border);
    border-radius: 12px;

    box-shadow: 0 8px 25px rgba(0,0,0,.04);
}

.shortcut-help-box.hidden {
    display: none;
}

.shortcut-help-title {
    display: flex;
    align-items: center;
    gap: 9px;

    margin-bottom: 12px;

    color: var(--text-hi);

    font-size: 13px;
    font-weight: 800;
}

.shortcut-help-title small {
    margin-left: auto;

    color: var(--text-mute);

    font: 600 10px monospace;
}

.shortcut-grid {
    display: grid;

    grid-template-columns:
        repeat(5, minmax(0, 1fr));

    gap: 8px;
}

.shortcut-item {
    display: flex;
    align-items: center;
    gap: 9px;

    padding: 8px 10px;

    background: var(--bg-input);

    border: 1px solid var(--border-soft);
    border-radius: 8px;
}

.shortcut-key {
    min-width: 30px;
    height: 23px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    padding: 0 6px;

    background: var(--bg-card-alt);

    border: 1px solid var(--border);
    border-radius: 5px;

    color: var(--green);

    font: 800 10px monospace;
}

.shortcut-desc {
    color: var(--text-body);

    font-size: 10px;
    font-weight: 600;
}

/* PRINT */

@media print {

    .sidebar,
    .header-actions,
    .reviews-filters,
    .shortcut-help-box,
    .review-actions,
    .reviews-table th:last-child,
    .reviews-table td:last-child {
        display: none !important;
    }

    html,
    body,
    .main,
    .content,
    .reviews-content,
    .reviews-stat-item {
        background: #fff !important;
        color: #111 !important;
    }

    .reviews-page-header {
        border-color: #ddd !important;
    }

    .reviews-page-header h1,
    .reviews-content-title h2,
    .reviews-table td,
    .reviews-stat-value {
        color: #111 !important;
    }

    .reviews-table th {
        background: #f5f5f5 !important;
        color: #555 !important;
    }

    .reviews-table td,
    .reviews-table th,
    .reviews-content,
    .reviews-stat-item {
        border-color: #ddd !important;
    }
}

/* TABLET */

@media (max-width: 1200px) {

    .reviews-stats {
        grid-template-columns:
            repeat(3, minmax(0, 1fr));
    }

    .shortcut-grid {
        grid-template-columns:
            repeat(3, minmax(0, 1fr));
    }
}

/* MOBILE */

@media (max-width: 850px) {

    .reviews-page {
        padding: 0 10px 25px;
    }

    .reviews-page-header {
        align-items: flex-start;

        flex-direction: column;

        gap: 14px;
    }

    .header-actions {
        width: 100%;

        justify-content: flex-start;
    }

    .reviews-stats {
        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

    .reviews-content-header {
        align-items: flex-start;

        flex-direction: column;
    }

    .reviews-filters {
        width: 100%;
    }

    .reviews-search-wrap {
        flex: 1;
        width: auto;
        min-width: 200px;
    }

    .reviews-status-filter {
        flex: 0 0 140px;
    }
}

@media (max-width: 560px) {

    .reviews-page-header h1 {
        font-size: 22px;
    }

    .header-actions {
        display: grid;

        grid-template-columns:
            1fr 1fr;
    }

    .header-actions .btn {
        width: 100%;
    }

    .reviews-stats {
        grid-template-columns: 1fr;
    }

    .reviews-filters {
        display: grid;

        grid-template-columns: 1fr;

        width: 100%;
    }

    .reviews-search-wrap {
        width: 100%;
        min-width: 0;
    }

    .reviews-status-filter {
        width: 100%;
    }

    .shortcut-grid {
        grid-template-columns: 1fr;
    }

    .reviews-table-summary {
        padding: 10px 14px;
    }
}

</style>


<main class="main">

    <section class="content">

        <div class="reviews-page">

            <!-- ==========================================================
                 PAGE HEADER
                 ========================================================== -->

            <div class="reviews-page-header">

                <div>

                    <div class="reviews-breadcrumb">

                        <span>Marketing</span>

                        <span>/</span>

                        <span class="current">
                            Product Reviews
                        </span>

                    </div>

                    <h1>
                        Product Reviews Management
                    </h1>

                    <p>
                        Manage customer feedback, star ratings, and review approvals.
                    </p>

                </div>


                <div class="header-actions">

                    <button
                        type="button"
                        class="btn btn-blue"
                        id="printBtn"
                        title="Print reviews (P)"
                    >
                        🖨 Print
                    </button>

                    <a
                        href="../dashboard.php"
                        class="btn"
                    >
                        ← Dashboard
                    </a>

                </div>

            </div>


            <!-- ==========================================================
                 SUCCESS MESSAGE
                 ========================================================== -->

            <?php if ($actionMessage !== ''): ?>

                <div class="notice notice-success">
                    ✓ <?= e($actionMessage) ?>
                </div>

            <?php endif; ?>


            <!-- ==========================================================
                 ERROR MESSAGE
                 ========================================================== -->

            <?php if ($actionError !== '' || $queryError !== ''): ?>

                <div class="notice notice-error">

                    ! <?= e($actionError ?: $queryError) ?>

                </div>

            <?php endif; ?>


            <!-- ==========================================================
                 STATISTICS
                 ========================================================== -->

            <div class="reviews-stats">

                <!-- TOTAL -->

                <div class="reviews-stat-item">

                    <div class="reviews-stat-icon">
                        ★
                    </div>

                    <div>

                        <div class="reviews-stat-label">
                            Total Reviews
                        </div>

                        <div class="reviews-stat-value">
                            <?= number_format($totalReviews) ?>
                        </div>

                    </div>

                </div>


                <!-- APPROVED -->

                <div class="reviews-stat-item">

                    <div class="reviews-stat-icon">
                        ✓
                    </div>

                    <div>

                        <div class="reviews-stat-label">
                            Approved
                        </div>

                        <div class="reviews-stat-value">
                            <?= number_format($approvedReviews) ?>
                        </div>

                    </div>

                </div>


                <!-- PENDING -->

                <div class="reviews-stat-item">

                    <div class="reviews-stat-icon">
                        ⏳
                    </div>

                    <div>

                        <div class="reviews-stat-label">
                            Pending
                        </div>

                        <div class="reviews-stat-value">
                            <?= number_format($pendingReviews) ?>
                        </div>

                    </div>

                </div>


                <!-- AVERAGE -->

                <div class="reviews-stat-item">

                    <div class="reviews-stat-icon">
                        ★
                    </div>

                    <div>

                        <div class="reviews-stat-label">
                            Average Rating
                        </div>

                        <div class="reviews-stat-value">
                            <?= e(number_format($averageRating, 1)) ?>/5
                        </div>

                    </div>

                </div>


                <!-- FIVE STAR -->

                <div class="reviews-stat-item">

                    <div class="reviews-stat-icon">
                        5★
                    </div>

                    <div>

                        <div class="reviews-stat-label">
                            Five Star
                        </div>

                        <div class="reviews-stat-value">
                            <?= number_format($fiveStarReviews) ?>
                        </div>

                    </div>

                </div>

            </div>


            <!-- ==========================================================
                 REVIEWS CONTENT
                 ========================================================== -->

            <div class="reviews-content">

                <!-- CONTENT HEADER -->

                <div class="reviews-content-header">

                    <div class="reviews-content-title">

                        <h2>
                            Reviews List
                        </h2>

                        <p>
                            Search through user comments and moderate review visibility.
                        </p>

                    </div>


                    <div class="reviews-filters">

                        <!-- SEARCH -->

                        <div class="reviews-search-wrap">

                            <span class="reviews-search-icon">
                                ⌕
                            </span>

                            <input
                                type="search"
                                id="reviewSearch"
                                class="reviews-search"
                                placeholder="Search product, email, comment..."
                                autocomplete="off"
                                aria-label="Search reviews"
                            >

                        </div>


                        <!-- STATUS -->

                        <select
                            id="reviewStatusFilter"
                            class="reviews-status-filter"
                            aria-label="Filter review status"
                        >

                            <option value="all">
                                All Status
                            </option>

                            <option value="approved">
                                Approved
                            </option>

                            <option value="pending">
                                Pending
                            </option>

                        </select>

                    </div>

                </div>


                <!-- TABLE SUMMARY -->

                <div class="reviews-table-summary">

                    <div class="reviews-result-text">

                        Showing
                        <strong id="visibleReviewCount">
                            <?= count($reviewsList) ?>
                        </strong>
                        reviews

                    </div>

                </div>


                <!-- TABLE -->

                <div class="reviews-table-wrapper">

                    <?php if (empty($reviewsList)): ?>

                        <div class="reviews-empty">

                            <div class="reviews-empty-icon">
                                ★
                            </div>

                            <h3>
                                No Product Reviews Found
                            </h3>

                            <p>
                                Customers haven't submitted any reviews yet.
                            </p>

                        </div>

                    <?php else: ?>

                        <table
                            class="reviews-table"
                            id="reviewTable"
                        >

                            <thead>

                                <tr>

                                    <th>
                                        #
                                    </th>

                                    <th>
                                        Product / User
                                    </th>

                                    <th>
                                        Rating
                                    </th>

                                    <th>
                                        Comment
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Date
                                    </th>

                                    <th style="text-align:right;">
                                        Actions
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php

                                $serialNo = 1;

                                foreach ($reviewsList as $review):

                                    $reviewId = (int)(
                                        $review['ReviewId'] ?? 0
                                    );

                                    $isApproved =
                                        (int)(
                                            $review['IsApproved'] ?? 0
                                        ) === 1;

                                    $productName =
                                        trim(
                                            (string)(
                                                $review['ProductName']
                                                ?? ''
                                            )
                                        );

                                    if ($productName === '') {
                                        $productName = 'Unknown Product';
                                    }

                                    $userId =
                                        (int)(
                                            $review['UserId'] ?? 0
                                        );

                                    $userEmail =
                                        trim(
                                            (string)(
                                                $review['UserEmail']
                                                ?? ''
                                            )
                                        );

                                    if ($userEmail === '') {
                                        $userEmail =
                                            $userId > 0
                                            ? 'User #' . $userId
                                            : 'Guest User';
                                    }

                                    $comment =
                                        trim(
                                            (string)(
                                                $review['Comment']
                                                ?? ''
                                            )
                                        );

                                    if ($comment === '') {
                                        $comment = 'No comment provided.';
                                    }

                                    $rating =
                                        (int)(
                                            $review['Rating'] ?? 0
                                        );

                                    if ($rating < 0) {
                                        $rating = 0;
                                    }

                                    if ($rating > 5) {
                                        $rating = 5;
                                    }

                                    $createdAt =
                                        formatReviewDate(
                                            $review['CreatedAt'] ?? null
                                        );

                                    $statusStr =
                                        $isApproved
                                        ? 'approved'
                                        : 'pending';

                                    $searchText = strtolower(
                                        $productName
                                        . ' '
                                        . $userEmail
                                        . ' '
                                        . $comment
                                    );

                                ?>

                                    <tr
                                        class="review-row"
                                        data-status="<?= e($statusStr) ?>"
                                        data-search="<?= e($searchText) ?>"
                                    >

                                        <!-- SERIAL -->

                                        <td>

                                            <span class="review-number">
                                                #<?= $serialNo++ ?>
                                            </span>

                                        </td>


                                        <!-- PRODUCT / USER -->

                                        <td>

                                            <span class="review-product-name">
                                                <?= e($productName) ?>
                                            </span>

                                            <span class="review-user-email">
                                                <?= e($userEmail) ?>
                                            </span>

                                        </td>


                                        <!-- RATING -->

                                        <td>

                                            <div class="review-rating">

                                                <span class="review-stars">

                                                    <?= str_repeat('★', $rating) ?>

                                                    <?php if ($rating < 5): ?>

                                                        <span class="review-stars-empty">
                                                            <?= str_repeat('★', 5 - $rating) ?>
                                                        </span>

                                                    <?php endif; ?>

                                                </span>

                                                <span class="review-rating-number">
                                                    (<?= $rating ?>/5)
                                                </span>

                                            </div>

                                        </td>


                                        <!-- COMMENT -->

                                        <td>

                                            <div class="review-comment">

                                                <?= e($comment) ?>

                                            </div>

                                        </td>


                                        <!-- STATUS -->

                                        <td>

                                            <?php if ($isApproved): ?>

                                                <span class="badge badge-approved">
                                                    Approved
                                                </span>

                                            <?php else: ?>

                                                <span class="badge badge-pending">
                                                    Pending
                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <!-- DATE -->

                                        <td>

                                            <span class="review-date">
                                                <?= e($createdAt) ?>
                                            </span>

                                        </td>


                                        <!-- ACTIONS -->

                                        <td style="text-align:right;">

                                            <div class="review-actions">

                                                <!-- APPROVE / UNAPPROVE -->

                                                <form
                                                    method="post"
                                                    class="review-action-form"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="toggle_approval"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="review_id"
                                                        value="<?= $reviewId ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= e($csrfToken) ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="review-action"
                                                        title="<?= $isApproved ? 'Move to pending' : 'Approve review' ?>"
                                                    >

                                                        <?= $isApproved
                                                            ? 'Unapprove'
                                                            : 'Approve'
                                                        ?>

                                                    </button>

                                                </form>


                                                <!-- DELETE -->

                                                <form
                                                    method="post"
                                                    class="review-action-form delete-review-form"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="delete"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="review_id"
                                                        value="<?= $reviewId ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= e($csrfToken) ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="review-action review-action-delete"
                                                        title="Delete review"
                                                    >
                                                        ×
                                                    </button>

                                                </form>

                                            </div>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>


                        <!-- NO SEARCH RESULT -->

                        <div
                            id="reviewNoResult"
                            class="reviews-no-result"
                            style="display:none;"
                        >

                            <div class="reviews-empty-icon">
                                ⌕
                            </div>

                            <h3>
                                No Matching Reviews
                            </h3>

                            <p>
                                Try changing your search query or status filter.
                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <!-- ==========================================================
                 KEYBOARD SHORTCUTS
                 ========================================================== -->

            <div
                class="shortcut-help-box"
                id="shortcutHelpBox"
            >

                <div class="shortcut-help-title">

                    <span>
                        ⌨
                    </span>

                    <span>
                        Keyboard Shortcuts
                    </span>

                    <small>
                        B C P H • Esc
                    </small>

                </div>


                <div class="shortcut-grid">

                    <div class="shortcut-item">

                        <span class="shortcut-key">
                            B
                        </span>

                        <span class="shortcut-desc">
                            Focus Search
                        </span>

                    </div>


                    <div class="shortcut-item">

                        <span class="shortcut-key">
                            C
                        </span>

                        <span class="shortcut-desc">
                            Status Filter
                        </span>

                    </div>


                    <div class="shortcut-item">

                        <span class="shortcut-key">
                            P
                        </span>

                        <span class="shortcut-desc">
                            Print List
                        </span>

                    </div>


                    <div class="shortcut-item">

                        <span class="shortcut-key">
                            H
                        </span>

                        <span class="shortcut-desc">
                            Toggle Shortcuts
                        </span>

                    </div>


                    <div class="shortcut-item">

                        <span class="shortcut-key">
                            Esc
                        </span>

                        <span class="shortcut-desc">
                            Clear Search / Blur
                        </span>

                    </div>

                </div>

            </div>

        </div>

    </section>

</main>


<script>

/*
|--------------------------------------------------------------------------
| GATEWAYLINEN REVIEWS JAVASCRIPT
|--------------------------------------------------------------------------
*/

(function () {

    'use strict';

    function initReviewsPage() {

        const searchInput =
            document.getElementById('reviewSearch');

        const statusFilter =
            document.getElementById('reviewStatusFilter');

        const table =
            document.getElementById('reviewTable');

        const countElement =
            document.getElementById('visibleReviewCount');

        const noResult =
            document.getElementById('reviewNoResult');

        const shortcutBox =
            document.getElementById('shortcutHelpBox');

        const printBtn =
            document.getElementById('printBtn');


        /*
        |--------------------------------------------------------------------------
        | PRINT
        |--------------------------------------------------------------------------
        */

        if (printBtn) {

            printBtn.addEventListener('click', function (event) {

                event.preventDefault();

                window.print();

            });

        }


        /*
        |--------------------------------------------------------------------------
        | FILTER REVIEWS
        |--------------------------------------------------------------------------
        */

        function filterReviews() {

            if (!table) {
                return;
            }

            const query =
                (
                    searchInput
                    ? searchInput.value
                    : ''
                )
                .toLowerCase()
                .trim();

            const status =
                statusFilter
                ? statusFilter.value
                : 'all';

            const rows =
                table.querySelectorAll(
                    'tbody .review-row'
                );

            let visibleCount = 0;


            rows.forEach(function (row) {

                const searchText =
                    (
                        row.dataset.search
                        || ''
                    ).toLowerCase();

                const rowStatus =
                    row.dataset.status
                    || '';


                const matchesQuery =
                    query === ''
                    ||
                    searchText.includes(query);

                const matchesStatus =
                    status === 'all'
                    ||
                    rowStatus === status;


                if (
                    matchesQuery
                    &&
                    matchesStatus
                ) {

                    row.style.display = '';

                    visibleCount++;

                } else {

                    row.style.display = 'none';

                }

            });


            if (countElement) {

                countElement.textContent =
                    visibleCount;

            }


            if (noResult) {

                noResult.style.display =
                    visibleCount === 0
                    ? 'block'
                    : 'none';

            }

        }


        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if (searchInput) {

            searchInput.addEventListener(
                'input',
                filterReviews
            );

        }


        /*
        |--------------------------------------------------------------------------
        | STATUS FILTER
        |--------------------------------------------------------------------------
        */

        if (statusFilter) {

            statusFilter.addEventListener(
                'change',
                filterReviews
            );

        }


        /*
        |--------------------------------------------------------------------------
        | DELETE CONFIRMATION
        |--------------------------------------------------------------------------
        */

        const deleteForms =
            document.querySelectorAll(
                '.delete-review-form'
            );

        deleteForms.forEach(function (form) {

            form.addEventListener(
                'submit',
                function (event) {

                    const confirmed =
                        window.confirm(
                            'Are you sure you want to permanently delete this review?'
                        );

                    if (!confirmed) {

                        event.preventDefault();

                    }

                }
            );

        });


        /*
        |--------------------------------------------------------------------------
        | PREVENT DOUBLE SUBMISSION
        |--------------------------------------------------------------------------
        */

        const actionForms =
            document.querySelectorAll(
                '.review-action-form'
            );

        actionForms.forEach(function (form) {

            form.addEventListener(
                'submit',
                function () {

                    if (
                        form.dataset.submitting === '1'
                    ) {
                        return;
                    }

                    form.dataset.submitting = '1';

                    const button =
                        form.querySelector(
                            'button[type="submit"]'
                        );

                    if (button) {

                        button.disabled = true;

                        button.style.opacity = '.65';

                        button.style.cursor = 'wait';

                    }

                }
            );

        });


        /*
        |--------------------------------------------------------------------------
        | KEYBOARD SHORTCUTS
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            'keydown',
            function (event) {

                const target =
                    event.target;

                const tag =
                    (
                        target
                        &&
                        target.tagName
                    )
                    ? target.tagName.toLowerCase()
                    : '';

                const typing =
                    tag === 'input'
                    ||
                    tag === 'textarea'
                    ||
                    tag === 'select'
                    ||
                    (
                        target
                        &&
                        target.isContentEditable
                    );


                const key =
                    String(
                        event.key || ''
                    ).toUpperCase();


                /*
                |--------------------------------------------------------------
                | Escape
                |--------------------------------------------------------------
                */

                if (event.key === 'Escape') {

                    if (
                        searchInput
                        &&
                        searchInput.value !== ''
                    ) {

                        searchInput.value = '';

                        filterReviews();

                    }

                    if (searchInput) {
                        searchInput.blur();
                    }

                    if (statusFilter) {
                        statusFilter.blur();
                    }

                    return;

                }


                /*
                |--------------------------------------------------------------
                | Do not trigger single-key shortcuts while typing
                |--------------------------------------------------------------
                */

                if (typing) {
                    return;
                }


                /*
                |--------------------------------------------------------------
                | B = Search
                |--------------------------------------------------------------
                */

                if (key === 'B') {

                    event.preventDefault();

                    if (searchInput) {

                        searchInput.focus();

                        searchInput.select();

                    }

                    return;
                }


                /*
                |--------------------------------------------------------------
                | C = Status
                |--------------------------------------------------------------
                */

                if (key === 'C') {

                    event.preventDefault();

                    if (statusFilter) {

                        statusFilter.focus();

                    }

                    return;
                }


                /*
                |--------------------------------------------------------------
                | P = Print
                |--------------------------------------------------------------
                */

                if (key === 'P') {

                    event.preventDefault();

                    window.print();

                    return;
                }


                /*
                |--------------------------------------------------------------
                | H = Toggle Help
                |--------------------------------------------------------------
                */

                if (key === 'H') {

                    event.preventDefault();

                    if (shortcutBox) {

                        shortcutBox.classList.toggle(
                            'hidden'
                        );

                    }

                    return;
                }

            },
            true
        );


        /*
        |--------------------------------------------------------------------------
        | INITIAL FILTER
        |--------------------------------------------------------------------------
        */

        filterReviews();

    }


    /*
    |--------------------------------------------------------------------------
    | DOM READY
    |--------------------------------------------------------------------------
    */

    if (
        document.readyState === 'loading'
    ) {

        document.addEventListener(
            'DOMContentLoaded',
            initReviewsPage
        );

    } else {

        initReviewsPage();

    }

})();

</script>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>