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

require_once __DIR__ . '/../config/database.php';

$activeMenu = 'reviews';
$pageTitle  = 'GatewayLinen | Product Reviews Management';

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] = $_SESSION['admin_username'] ?? 'GatewayLinen Administrator';
}

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function formatDate($value): string {
    if ($value instanceof DateTimeInterface) {
        return $value->format('d M Y, h:i A');
    }
    return !empty($value) ? date('d M Y, h:i A', strtotime($value)) : '—';
}

$actionMessage = trim((string)($_GET['success'] ?? ''));
$actionError   = trim((string)($_GET['error'] ?? ''));

/*
|--------------------------------------------------------------------------
| HANDLE ACTIONS (UPDATE STATUS / DELETE)
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['review_id'])) {
    $reviewId = (int)$_POST['review_id'];
    $actionType = trim($_POST['action']);

    if ($reviewId > 0) {
        if ($actionType === 'toggle_approval') {
            $currentStatus = (int)($_POST['current_status'] ?? 0);
            $newStatus = $currentStatus === 1 ? 0 : 1;

            $upSql = "UPDATE dbo.ProductReviews SET IsApproved = ? WHERE ReviewId = ?";
            $upStmt = sqlsrv_query($conn, $upSql, [$newStatus, $reviewId]);
            if ($upStmt !== false) {
                header('Location: index.php?success=' . urlencode("Review status updated successfully."));
                exit;
            } else {
                $actionError = "Failed to update review status.";
            }
        } elseif ($actionType === 'delete') {
            $delSql = "DELETE FROM dbo.ProductReviews WHERE ReviewId = ?";
            $delStmt = sqlsrv_query($conn, $delSql, [$reviewId]);
            if ($delStmt !== false) {
                header('Location: index.php?success=' . urlencode("Review deleted successfully."));
                exit;
            } else {
                $actionError = "Failed to delete review.";
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| FETCH REVIEWS LIST FROM DATABASE
|--------------------------------------------------------------------------
*/
$sql = "SELECT 
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
        LEFT JOIN dbo.Products p ON r.ProductId = p.ProductId
        LEFT JOIN dbo.Users u ON r.UserId = u.UserId
        ORDER BY r.ReviewId DESC";

$stmt = sqlsrv_query($conn, $sql);
$reviewsList = [];
$queryError = '';

if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $reviewsList[] = $row;
    }
    sqlsrv_free_stmt($stmt);
} else {
    $queryError = 'Unable to fetch reviews from database.';
}

/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/
$totalReviews = count($reviewsList);
$approvedReviews = 0;
$pendingReviews = 0;

foreach ($reviewsList as $rev) {
    if (!empty($rev['IsApproved'])) {
        $approvedReviews++;
    } else {
        $pendingReviews++;
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<style>
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
        --text-mute: #5f7488;
        --green: #10b981;
        --green-soft: rgba(16, 185, 129, .12);
        --red: #ef4444;
        --red-soft: rgba(239, 68, 68, .12);
        --amber: #f59e0b;
        --amber-soft: rgba(245, 158, 11, .12);
        --blue: #38bdf8;
        --blue-soft: rgba(56, 189, 248, .15);
        --radius: 10px;
    }

    html, body, .main, .content {
        background: var(--bg-page) !important;
        color: var(--text-body) !important;
    }

    .reviews-page {
        width: 100%;
        max-width: 1600px;
        margin: 0 auto;
        padding: 0;
    }

    .reviews-page * {
        box-sizing: border-box;
    }

    /* HEADER */
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
        gap: 8px;
        margin-bottom: 8px;
        color: var(--text-mute);
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .3px;
    }

    .reviews-breadcrumb .current {
        color: var(--green);
    }

    .reviews-page-header h1 {
        margin: 0;
        color: var(--text-hi);
        font-size: 26px;
        font-weight: 800;
    }

    .reviews-page-header p {
        margin: 6px 0 0;
        color: var(--text-mute);
        font-size: 12px;
    }

    /* BUTTONS */
    .header-actions, .reviews-actions, .reviews-filters, .export-bar {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .header-actions {
        justify-content: flex-end;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 38px;
        padding: 0 13px;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--bg-input);
        color: var(--text-body) !important;
        font-size: 11px;
        font-weight: 800;
        text-decoration: none;
        cursor: pointer;
        transition: .18s;
    }

    .btn:hover {
        border-color: var(--green);
        background: var(--green-soft);
        color: var(--green) !important;
    }

    .btn-blue { color: var(--blue) !important; }

    /* STATS */
    .reviews-stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }

    .reviews-stat-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px 17px;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
    }

    .reviews-stat-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 9px;
        background: var(--green-soft);
        color: var(--green);
        font-size: 15px;
        font-weight: 800;
    }

    .reviews-stat-label {
        color: var(--text-mute);
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .reviews-stat-value {
        margin-top: 2px;
        color: var(--text-hi);
        font-size: 20px;
        font-weight: 800;
    }

    /* NOTICE */
    .notice {
        margin-bottom: 14px;
        padding: 12px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
    }
    .notice-success { border: 1px solid rgba(16, 185, 129, .3); background: var(--green-soft); color: #6ee7b7; }
    .notice-error { border: 1px solid rgba(239, 68, 68, .3); background: var(--red-soft); color: #fca5a5; }

    /* CONTENT */
    .reviews-content {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
    }

    .reviews-content-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 17px 20px;
        border-bottom: 1px solid var(--border);
    }

    .reviews-content-title h2 { margin: 0; color: var(--text-hi); font-size: 16px; }
    .reviews-content-title p { margin: 4px 0 0; color: var(--text-mute); font-size: 11px; }

    /* SEARCH */
    .reviews-search-wrap {
        position: relative;
        width: 300px;
    }

    .reviews-search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-mute);
        pointer-events: none;
    }

    .reviews-search, .reviews-status-filter {
        height: 36px;
        border: 1px solid var(--border);
        border-radius: 8px;
        outline: none;
        background: var(--bg-input);
        color: var(--text-hi);
        font-size: 12px;
    }

    .reviews-search { width: 100%; padding: 0 12px 0 34px; }
    .reviews-status-filter { min-width: 135px; padding: 0 10px; }

    .reviews-search:focus, .reviews-status-filter:focus {
        border-color: var(--green);
        box-shadow: 0 0 0 3px rgba(16, 185, 129, .1);
    }

    /* SUMMARY */
    .reviews-table-summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 11px 20px;
        border-bottom: 1px solid var(--border);
    }

    .reviews-result-text { color: var(--text-mute); font-size: 11px; font-weight: 600; }
    .reviews-result-text strong { color: var(--text-hi); }

    /* TABLE */
    .reviews-table-wrapper { width: 100%; overflow-x: auto; }
    .reviews-table { width: 100%; min-width: 1100px; border-collapse: collapse; }
    .reviews-table th {
        height: 44px; padding: 0 16px; background: var(--bg-header); border-bottom: 1px solid var(--border);
        color: var(--text-mute); font-size: 10px; font-weight: 800; text-align: left; text-transform: uppercase; letter-spacing: .5px;
    }
    .reviews-table td { padding: 12px 16px; background: transparent; border-bottom: 1px solid var(--border-soft); color: var(--text-body); font-size: 12px; vertical-align: middle; }
    .reviews-table tbody tr:hover { background: var(--bg-hover); }

    .order-box {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 38px; height: 26px; padding: 0 8px; border-radius: 6px;
        background: var(--bg-input); border: 1px solid var(--border); color: var(--blue); font-size: 11px; font-weight: 800;
    }

    /* STATUS BADGES */
    .badge { padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 800; text-transform: uppercase; display: inline-block; }
    .badge-approved { background: var(--green-soft); color: var(--green); border: 1px solid rgba(16,185,129,.2); }
    .badge-pending { background: var(--amber-soft); color: var(--amber); border: 1px solid rgba(245,158,11,.2); }

    .category-action {
        display: inline-flex; align-items: center; justify-content: center;
        width: 32px; height: 32px; border: 1px solid var(--border); border-radius: 7px;
        background: var(--bg-input); color: var(--text-body) !important; text-decoration: none; cursor: pointer; font-size: 11px; font-weight: 700;
    }
    .category-action:hover { border-color: var(--green); background: var(--green-soft); color: var(--green) !important; }
    .category-action-delete:hover { border-color: rgba(239, 68, 68, .5); background: var(--red-soft); color: var(--red) !important; }

    .reviews-empty, .reviews-no-result { padding: 65px 20px; text-align: center; }

    /* SHORTCUTS */
    .shortcut-help-box {
        margin-top: 16px;
        padding: 16px 20px;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: var(--bg-card);
    }
    .shortcut-help-box.hidden { display: none; }
    .shortcut-help-title {
        display: flex; align-items: center; gap: 9px; margin-bottom: 12px;
        color: var(--text-hi); font-size: 13px; font-weight: 800;
    }
    .shortcut-help-title small { margin-left: auto; color: var(--text-mute); font: 600 10px monospace; }
    .shortcut-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; }
    .shortcut-item {
        display: flex; align-items: center; gap: 9px; padding: 8px 10px;
        border: 1px solid var(--border-soft); border-radius: 8px; background: var(--bg-input);
    }
    .shortcut-key {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 30px; height: 23px; padding: 0 6px; border-radius: 5px;
        background: #0a1119; border: 1px solid var(--border); color: var(--green); font: 800 10px monospace;
    }
    .shortcut-desc { color: var(--text-body); font-size: 11px; font-weight: 600; }

    @media print {
        .sidebar, .header-actions, .reviews-filters, .shortcut-help-box, th:last-child, td:last-child {
            display: none !important;
        }
        .main, .content, .reviews-content {
            background: white !important;
            color: black !important;
        }
    }
</style>

<main class="main">
    <section class="content">
        <div class="reviews-page">

            <!-- PAGE HEADER -->
            <div class="reviews-page-header">
                <div>
                    <div class="reviews-breadcrumb">
                        <span>Marketing</span>
                        <span>/</span>
                        <span class="current">Product Reviews</span>
                    </div>
                    <h1>Product Reviews Management</h1>
                    <p>Manage customer feedback, star ratings, and review approvals.</p>
                </div>

                <div class="header-actions">
                    <button type="button" class="btn btn-blue" id="printBtn">🖨 Print <small>P</small></button>
                    <a href="../dashboard.php" class="btn">← Dashboard</a>
                </div>
            </div>

            <!-- MESSAGES -->
            <?php if ($actionMessage !== ''): ?>
                <div class="notice notice-success">✓ <?= e($actionMessage) ?></div>
            <?php endif; ?>

            <?php if ($actionError !== '' || $queryError !== ''): ?>
                <div class="notice notice-error">! <?= e($actionError ?: $queryError) ?></div>
            <?php endif; ?>

            <!-- STATISTICS -->
            <div class="reviews-stats">
                <div class="reviews-stat-item">
                    <div class="reviews-stat-icon">★</div>
                    <div>
                        <div class="reviews-stat-label">Total Reviews</div>
                        <div class="reviews-stat-value"><?= $totalReviews ?></div>
                    </div>
                </div>

                <div class="reviews-stat-item">
                    <div class="reviews-stat-icon">✓</div>
                    <div>
                        <div class="reviews-stat-label">Approved</div>
                        <div class="reviews-stat-value"><?= $approvedReviews ?></div>
                    </div>
                </div>

                <div class="reviews-stat-item">
                    <div class="reviews-stat-icon">⏳</div>
                    <div>
                        <div class="reviews-stat-label">Pending Approval</div>
                        <div class="reviews-stat-value"><?= $pendingReviews ?></div>
                    </div>
                </div>
            </div>

            <!-- CONTENT BOX -->
            <div class="reviews-content">
                <div class="reviews-content-header">
                    <div class="reviews-content-title">
                        <h2>Reviews List</h2>
                        <p>Search through user comments and moderate review visibility.</p>
                    </div>

                    <div class="reviews-filters">
                        <div class="reviews-search-wrap">
                            <span class="reviews-search-icon">⌕</span>
                            <input type="search" id="reviewSearch" class="reviews-search" placeholder="Search product, email, comment..." autocomplete="off">
                        </div>

                        <select id="reviewStatusFilter" class="reviews-status-filter">
                            <option value="all">All Status</option>
                            <option value="approved">Approved</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                </div>

                <!-- TABLE SUMMARY -->
                <div class="reviews-table-summary">
                    <div class="reviews-result-text">
                        Showing <strong id="visibleReviewCount"><?= count($reviewsList) ?></strong> reviews
                    </div>
                </div>

                <!-- TABLE WRAPPER -->
                <div class="reviews-table-wrapper">
                    <?php if (empty($reviewsList)): ?>
                        <div class="reviews-empty">
                            <h3>No Product Reviews Found</h3>
                            <p>Customers haven't submitted any reviews yet.</p>
                        </div>
                    <?php else: ?>
                        <table class="reviews-table" id="reviewTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Product / User</th>
                                    <th>Rating</th>
                                    <th>Comment</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $serialNo = 1;
                                foreach ($reviewsList as $rev): 
                                    $isApproved = (int)($rev['IsApproved'] ?? 0) === 1;
                                    $productName = (string)($rev['ProductName'] ?? 'Unknown Product');
                                    $userEmail = (string)($rev['UserEmail'] ?? 'User #' . $rev['UserId']);
                                    $comment = (string)($rev['Comment'] ?? '');
                                    $rating = (int)($rev['Rating'] ?? 0);
                                    $createdAt = formatDate($rev['CreatedAt'] ?? '');
                                    $statusStr = $isApproved ? 'approved' : 'pending';
                                ?>
                                    <tr class="review-row" 
                                        data-status="<?= $statusStr ?>"
                                        data-search="<?= e(strtolower($productName . ' ' . $userEmail . ' ' . $comment)) ?>">
                                        <td>
                                            <span class="order-box">#<?= $serialNo++ ?></span>
                                        </td>
                                        <td>
                                            <strong style="color:var(--text-hi); font-size:13px;"><?= e($productName) ?></strong>
                                            <div style="font-size:11px; color:var(--text-mute);"><?= e($userEmail) ?></div>
                                        </td>
                                        <td>
                                            <span style="color: var(--amber); font-weight:800; letter-spacing:1px;">
                                                <?= str_repeat('★', $rating) ?><span style="color:var(--border);"><?= str_repeat('★', 5 - $rating) ?></span>
                                            </span>
                                            <span style="font-size:11px; color:var(--text-mute); margin-left:4px;">(<?= $rating ?>/5)</span>
                                        </td>
                                        <td style="max-width: 320px; white-space: normal; word-break: break-word; line-height: 1.4;">
                                            <?= e($comment) ?>
                                        </td>
                                        <td>
                                            <?php if ($isApproved): ?>
                                                <span class="badge badge-approved">Approved</span>
                                            <?php else: ?>
                                                <span class="badge badge-pending">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="color:var(--text-mute); font-size:11px;">
                                            <?= e($createdAt) ?>
                                        </td>
                                        <td style="text-align:right;">
                                            <div style="display:inline-flex; gap:6px; justify-content:flex-end;">
                                                <!-- Toggle Approval Form -->
                                                <form method="POST" style="margin:0;">
                                                    <input type="hidden" name="review_id" value="<?= (int)$rev['ReviewId'] ?>">
                                                    <input type="hidden" name="action" value="toggle_approval">
                                                    <input type="hidden" name="current_status" value="<?= (int)$rev['IsApproved'] ?>">
                                                    <button type="submit" class="category-action" title="<?= $isApproved ? 'Unapprove review' : 'Approve review' ?>" style="width:auto; padding:0 10px; font-size:10px;">
                                                        <?= $isApproved ? 'Unapprove' : 'Approve' ?>
                                                    </button>
                                                </form>

                                                <!-- Delete Form -->
                                                <form method="POST" style="margin:0;" onsubmit="return confirm('Are you sure you want to delete this review?');">
                                                    <input type="hidden" name="review_id" value="<?= (int)$rev['ReviewId'] ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit" class="category-action category-action-delete" title="Delete review">×</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div id="reviewNoResult" class="reviews-no-result" style="display:none">
                            <h3>No matching reviews</h3>
                            <p>Try changing your search query or status filter.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- KEYBOARD SHORTCUTS HELP BOX -->
            <div class="shortcut-help-box" id="shortcutHelpBox">
                <div class="shortcut-help-title">
                    <span>⌨</span>
                    <span>Keyboard Shortcuts</span>
                    <small>B C P H • Esc</small>
                </div>
                <div class="shortcut-grid">
                    <div class="shortcut-item"><span class="shortcut-key">B</span><span class="shortcut-desc">Focus Search</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">C</span><span class="shortcut-desc">Status Filter</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">P</span><span class="shortcut-desc">Print List</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">H</span><span class="shortcut-desc">Toggle Shortcuts</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">Esc</span><span class="shortcut-desc">Clear Search / Blur</span></div>
                </div>
            </div>

        </div>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('reviewSearch');
        const statusFilter = document.getElementById('reviewStatusFilter');
        const table = document.getElementById('reviewTable');
        const countElement = document.getElementById('visibleReviewCount');
        const noResult = document.getElementById('reviewNoResult');
        const shortcutBox = document.getElementById('shortcutHelpBox');
        const printBtn = document.getElementById('printBtn');

        // Print Button Event Listener
        printBtn?.addEventListener('click', function(e) {
            e.preventDefault();
            window.print();
        });

        function filterReviews() {
            if (!table) return;
            const q = (searchInput?.value || '').toLowerCase().trim();
            const status = statusFilter?.value || 'all';

            const rows = table.querySelectorAll('tbody .review-row');
            let visibleCount = 0;

            rows.forEach(function(row) {
                const text = row.dataset.search || '';
                const rowStatus = row.dataset.status || '';

                const matchesQuery = (!q || text.includes(q));
                const matchesStatus = (status === 'all' || rowStatus === status);

                if (matchesQuery && matchesStatus) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (countElement) countElement.textContent = visibleCount;
            if (noResult) noResult.style.display = (visibleCount === 0) ? 'block' : 'none';
        }

        searchInput?.addEventListener('input', filterReviews);
        statusFilter?.addEventListener('change', filterReviews);

        // Keyboard Shortcuts Handler
        document.addEventListener('keydown', function(e) {
            const tag = (e.target?.tagName || '').toLowerCase();
            const typing = tag === 'input' || tag === 'textarea' || tag === 'select' || e.target?.isContentEditable;

            const key = (e.key || '').toUpperCase();

            if (!typing) {
                if (['B', 'C', 'P', 'H'].includes(key)) {
                    e.preventDefault();
                    e.stopPropagation();
                }

                if (key === 'B') {
                    searchInput?.focus();
                    searchInput?.select();
                } else if (key === 'C') {
                    statusFilter?.focus();
                } else if (key === 'P') {
                    window.print();
                } else if (key === 'H') {
                    shortcutBox?.classList.toggle('hidden');
                }
            }

            if (e.key === 'Escape') {
                if (searchInput?.value) {
                    searchInput.value = '';
                    filterReviews();
                }
                searchInput?.blur();
                statusFilter?.blur();
            }
        }, true);
    });
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>