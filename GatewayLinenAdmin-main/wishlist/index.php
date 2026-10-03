<?php
session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$activeMenu = 'wishlist';
$pageTitle  = 'GatewayLinen | Wishlist Items';

if (!isset($_SESSION['admin_name'])) {
    $_SESSION['admin_name'] = $_SESSION['admin_username'] ?? 'GatewayLinen Administrator';
}
if (!isset($_SESSION['admin_role'])) {
    $_SESSION['admin_role'] = 'Administrator';
}

if (empty($_SESSION['wishlist_delete_token'])) {
    $_SESSION['wishlist_delete_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['wishlist_delete_token'];

$actionMessage = trim((string)($_GET['success'] ?? ''));
$actionError   = trim((string)($_GET['error'] ?? ''));

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function dateValue($value): string
{
    if ($value instanceof DateTimeInterface) {
        return $value->format('d M Y, h:i A');
    }
    return trim((string)$value);
}

/* --------------------------------------------------------------------------
   LOAD WISHLIST ITEMS WITH JOINS (Users & Variants/Products if available)
   -------------------------------------------------------------------------- */
$sql = "
    SELECT 
        w.WishlistId,
        w.UserId,
        w.VariantId,
        w.AddedDate
    FROM dbo.WishlistItems w
    ORDER BY w.AddedDate DESC, w.WishlistId DESC
";

$stmt = sqlsrv_query($conn, $sql);
$allWishlistItems = [];
$queryError = '';

if ($stmt !== false) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $allWishlistItems[] = $row;
    }
    sqlsrv_free_stmt($stmt);
} else {
    $queryError = 'Unable to load wishlist items right now.';
}

$totalWishlistItems = count($allWishlistItems);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<style>
.wishlist-page,
.wishlist-page * { box-sizing: border-box; }

.wishlist-page {
    width: 100%;
    max-width: 1600px;
    margin: 0 auto;
    padding: 0;
    font-size: 13px;

    --wish-page: #f3f6fa;
    --wish-card: #ffffff;
    --wish-card-alt: #f8fafc;
    --wish-input: #ffffff;
    --wish-border: #dce4ec;
    --wish-border-soft: #e8edf3;
    --wish-text: #162334;
    --wish-body: #536579;
    --wish-muted: #7b8da1;
    --wish-green: #059669;
    --wish-green-soft: rgba(5,150,105,.10);
    --wish-red: #dc2626;
    --wish-red-soft: rgba(220,38,38,.09);
    --wish-blue: #0284c7;
    --wish-blue-soft: rgba(2,132,199,.09);
    --wish-shadow: 0 5px 18px rgba(15,23,42,.05);
}

html[data-theme="dark"] .wishlist-page,
body[data-theme="dark"] .wishlist-page {
    --wish-page: #0a1119;
    --wish-card: #111b26;
    --wish-card-alt: #0f1823;
    --wish-input: #0d1620;
    --wish-border: #1e2d3d;
    --wish-border-soft: #182636;
    --wish-text: #f0f4f8;
    --wish-body: #a8b8c8;
    --wish-muted: #6f8295;
    --wish-green: #10b981;
    --wish-green-soft: rgba(16,185,129,.12);
    --wish-red: #ef4444;
    --wish-red-soft: rgba(239,68,68,.12);
    --wish-blue: #38bdf8;
    --wish-blue-soft: rgba(56,189,248,.12);
    --wish-shadow: none;
}

.wishlist-page { background: var(--wish-page); color: var(--wish-body); }

.wishlist-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 28px;
    margin: 0 0 18px;
    padding: 0 0 16px;
    border-bottom: 1px solid var(--wish-border);
}

.wishlist-breadcrumb {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 7px;
    color: var(--wish-muted);
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .45px;
}
.wishlist-breadcrumb .current { color: var(--wish-green); }

.wishlist-page-header h1 { margin: 0; color: var(--wish-text); font-size: 30px; font-weight: 900; letter-spacing: -.5px; }
.wishlist-page-header p { margin: 7px 0 0; color: var(--wish-muted); font-size: 13px; font-weight: 600; }

.header-actions { display: flex; align-items: center; gap: 10px; }

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 44px;
    padding: 0 15px;
    border: 1px solid var(--wish-border);
    border-radius: 9px;
    background: var(--wish-card);
    color: var(--wish-text) !important;
    font-size: 13px;
    font-weight: 900;
    text-decoration: none;
    cursor: pointer;
    transition: .16s ease;
}
.btn:hover { border-color: var(--wish-green); background: var(--wish-green-soft); color: var(--wish-green) !important; }

.key-hint, .btn small {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 21px; height: 21px; padding: 0 4px;
    border: 1px solid currentColor; border-radius: 4px; font: 800 9px/1 monospace; opacity: .9;
}

.wishlist-stats { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 14px; margin-bottom: 16px; }
.wishlist-stat-item {
    display: flex; align-items: center; gap: 13px; min-height: 82px; padding: 14px 17px;
    background: var(--wish-card); border: 1px solid var(--wish-border); border-radius: 11px; box-shadow: var(--wish-shadow);
}
.wishlist-stat-icon {
    display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; flex: 0 0 40px;
    border-radius: 10px; background: var(--wish-green-soft); color: var(--wish-green); font-size: 17px; font-weight: 900;
}
.wishlist-stat-label { color: var(--wish-muted); font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .45px; }
.wishlist-stat-value { margin-top: 4px; color: var(--wish-text); font-size: 24px; font-weight: 900; line-height: 1; }

.notice { margin-bottom: 12px; padding: 12px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; }
.notice-success { border: 1px solid rgba(5,150,105,.25); background: var(--wish-green-soft); color: var(--wish-green); }
.notice-error { border: 1px solid rgba(220,38,38,.25); background: var(--wish-red-soft); color: var(--wish-red); }

.wishlist-content { background: var(--wish-card); border: 1px solid var(--wish-border); border-radius: 12px; overflow: hidden; box-shadow: var(--wish-shadow); }
.wishlist-content-header { display: flex; align-items: center; justify-content: space-between; gap: 28px; padding: 20px 24px; min-height: 104px; border-bottom: 1px solid var(--wish-border); }
.wishlist-content-title h2 { margin: 0; color: var(--wish-text); font-size: 22px; font-weight: 900; }
.wishlist-content-title p { margin: 6px 0 0; color: var(--wish-muted); font-size: 11px; font-weight: 600; line-height: 1.5; }

.wishlist-filters { display: flex; align-items: center; gap: 10px; flex: 1 1 auto; justify-content: flex-end; }
.wishlist-search-wrap { position: relative; width: min(720px, 100%); flex: 1 1 620px; }
.wishlist-search-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--wish-muted); pointer-events: none; font-size: 17px; }

.wishlist-search {
    height: 54px; border: 1px solid var(--wish-border); border-radius: 10px; outline: none; background: var(--wish-input); color: var(--wish-text); font-size: 14px; font-weight: 600;
    width: 100%; padding: 0 16px 0 46px;
}
.wishlist-search::placeholder { color: var(--wish-muted); font-size: 14px; font-weight: 500; }
.wishlist-search:focus { border-color: var(--wish-green); box-shadow: 0 0 0 3px var(--wish-green-soft); }

.wishlist-table-summary { display: flex; align-items: center; justify-content: space-between; padding: 12px 24px; border-bottom: 1px solid var(--wish-border); }
.wishlist-result-text { color: var(--wish-muted); font-size: 11px; font-weight: 700; }
.wishlist-result-text strong { color: var(--wish-text); }

.wishlist-table-wrapper { width: 100%; overflow-x: auto; }
.wishlist-table { width: 100%; border-collapse: collapse; }
.wishlist-table th {
    height: 48px; padding: 0 16px; background: var(--wish-card-alt); border-bottom: 1px solid var(--wish-border);
    color: var(--wish-muted); font-size: 10px; font-weight: 900; text-align: left; text-transform: uppercase; letter-spacing: .55px; white-space: nowrap;
}
.wishlist-table td { padding: 15px 16px; background: transparent; border-bottom: 1px solid var(--wish-border-soft); color: var(--wish-body); font-size: 12px; line-height: 1.45; vertical-align: middle; }
.wishlist-table tbody tr:hover { background: var(--wish-green-soft); }

.order-box {
    display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 30px; padding: 0 8px;
    border-radius: 7px; background: var(--wish-input); border: 1px solid var(--wish-border); color: var(--wish-green); font-size: 11px; font-weight: 900;
}
.wishlist-date { color: var(--wish-muted); font-size: 10px; line-height: 1.45; white-space: nowrap; }

.wishlist-actions { display: flex; align-items: center; gap: 5px; flex-wrap: nowrap; }
.wishlist-action {
    display: inline-flex; align-items: center; justify-content: center; gap: 3px; width: 36px; height: 36px;
    border: 1px solid var(--wish-border); border-radius: 8px; background: var(--wish-card); color: var(--wish-body) !important;
    text-decoration: none; cursor: pointer; font-size: 13px;
}
.wishlist-action:hover { border-color: var(--wish-green); background: var(--wish-green-soft); color: var(--wish-green) !important; }
.wishlist-action-delete:hover { border-color: rgba(220,38,38,.4); background: var(--wish-red-soft); color: var(--wish-red) !important; }

.wishlist-empty, .wishlist-no-result { padding: 65px 20px; text-align: center; }
.wishlist-empty-icon, .wishlist-no-result-icon { margin-bottom: 12px; color: var(--wish-green); font-size: 32px; }
.wishlist-empty h3, .wishlist-no-result h3 { margin: 0; color: var(--wish-text); font-size: 16px; font-weight: 900; }
.wishlist-empty p, .wishlist-no-result p { margin: 6px 0 0; color: var(--wish-muted); font-size: 11px; }

.wishlist-pagination-bar { display: flex; align-items: center; justify-content: space-between; gap: 15px; padding: 14px 18px; background: var(--wish-card); border-top: 1px solid var(--wish-border); min-height: 68px; }
.wishlist-page-info { color: var(--wish-muted); font-size: 11px; font-weight: 700; }
.wishlist-page-controls { display: flex; align-items: center; gap: 6px; }
.page-size-wrap { display: flex; align-items: center; gap: 8px; margin-right: 10px; color: var(--wish-muted); font-size: 10px; font-weight: 800; }
.page-size-select, .pagination-btn { height: 42px; border: 1px solid var(--wish-border); border-radius: 8px; background: var(--wish-card); color: var(--wish-text); font-size: 12px; font-weight: 800; outline: none; }
.page-size-select { min-width: 78px; padding: 0 10px; }
.pagination-btn { min-width: 42px; padding: 0 10px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
.pagination-btn:hover:not(:disabled), .pagination-btn.active { background: var(--wish-green); border-color: var(--wish-green); color: #fff !important; }
.pagination-btn:disabled { opacity: .4; cursor: not-allowed; }
.pagination-ellipsis { min-width: 26px; text-align: center; color: var(--wish-muted); font-size: 13px; }
</style>

<main class="main">
    <section class="content">
        <div class="wishlist-page">

            <div class="wishlist-page-header">
                <div>
                    <div class="wishlist-breadcrumb">
                        <span>Customer</span><span>/</span><span class="current">Wishlist Items</span>
                    </div>
                    <h1>Wishlist Items</h1>
                    <p>Monitor products and variants saved by customers to their wishlists.</p>
                </div>

                <div class="header-actions">
                    <button type="button" class="btn" id="printBtn" title="Print (P)">🖨 Print <small>P</small></button>
                    <button type="button" class="btn" id="pdfBtn" title="PDF (V)">↓ PDF <small>V</small></button>
                    <button type="button" class="btn" id="excelBtn" title="Excel (X)">↓ Excel <small>X</small></button>
                </div>
            </div>

            <?php if ($actionMessage !== ''): ?>
                <div class="notice notice-success"><?= e($actionMessage) ?></div>
            <?php endif; ?>
            <?php if ($actionError !== ''): ?>
                <div class="notice notice-error"><?= e($actionError) ?></div>
            <?php endif; ?>
            <?php if ($queryError !== ''): ?>
                <div class="notice notice-error"><?= e($queryError) ?></div>
            <?php endif; ?>

            <div class="wishlist-stats">
                <div class="wishlist-stat-item"><div class="wishlist-stat-icon">#</div><div><div class="wishlist-stat-label">Total Wishlist Entries</div><div class="wishlist-stat-value"><?= $totalWishlistItems ?></div></div></div>
                <div class="wishlist-stat-item"><div class="wishlist-stat-icon">♡</div><div><div class="wishlist-stat-label">Active Tracked Items</div><div class="wishlist-stat-value"><?= $totalWishlistItems ?></div></div></div>
            </div>

            <div class="wishlist-content">
                <div class="wishlist-content-header">
                    <div class="wishlist-content-title">
                        <h2>Wishlist List</h2>
                        <p>Wishlist ID, User ID, Variant ID, and Date Added.</p>
                    </div>

                    <div class="wishlist-filters">
                        <div class="wishlist-search-wrap">
                            <span class="wishlist-search-icon">⌕</span>
                            <input type="search" id="wishlistSearch" class="wishlist-search" placeholder="Search wishlist ID, User ID, Variant ID... (B)" autocomplete="off">
                        </div>
                    </div>
                </div>

                <div class="wishlist-table-summary">
                    <div class="wishlist-result-text">Showing <strong id="visibleWishlistCount"><?= $totalWishlistItems ?></strong> items</div>
                    <div class="wishlist-result-text">Total: <strong><?= $totalWishlistItems ?></strong></div>
                </div>

                <div class="wishlist-table-wrapper">
                    <?php if (empty($allWishlistItems)): ?>
                        <div class="wishlist-empty">
                            <div class="wishlist-empty-icon">♡</div>
                            <h3>No Wishlist Items Found</h3>
                            <p>Customers have not added any products to their wishlists yet.</p>
                        </div>
                    <?php else: ?>
                        <table class="wishlist-table" id="wishlistTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Wishlist ID</th>
                                    <th>User ID</th>
                                    <th>Variant ID</th>
                                    <th>Added Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php $orderNo = 1; foreach ($allWishlistItems as $item): ?>
                                <?php
                                $wishlistId = (int)$item['WishlistId'];
                                $userId     = (int)($item['UserId'] ?? 0);
                                $variantId  = (int)($item['VariantId'] ?? 0);
                                $addedDate  = dateValue($item['AddedDate']);
                                $currentOrder = $orderNo++;
                                ?>
                                <tr class="wishlist-row" data-id="<?= $wishlistId ?>" data-search="<?= e(strtolower($wishlistId . ' ' . $userId . ' ' . $variantId)) ?>">
                                    <td><span class="order-box"><?= $currentOrder ?></span></td>
                                    <td><strong>#<?= $wishlistId ?></strong></td>
                                    <td>User #<?= $userId ?></td>
                                    <td>Variant #<?= $variantId ?></td>
                                    <td><div class="wishlist-date"><?= e($addedDate) ?></div></td>
                                    <td>
                                        <div class="wishlist-actions">
                                            <form method="POST" action="delete.php" class="delete-form" style="display:inline">
                                                <input type="hidden" name="wishlist_id" value="<?= $wishlistId ?>">
                                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                                <button type="submit" class="wishlist-action wishlist-action-delete delete-wishlist-btn" title="Remove from wishlist (D)">×<span class="key-hint">D</span></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div id="wishlistNoResult" class="wishlist-no-result" style="display:none">
                            <div class="wishlist-no-result-icon">⌕</div>
                            <h3>No matching wishlist items</h3>
                            <p>Try changing your search keywords.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="wishlist-pagination-bar" id="wishlistPaginationBar">
                    <div>
                        <div class="wishlist-page-info" id="wishlistPageInfo">Showing 0-0 of 0 items</div>
                        <div class="dataset-note" style="color:var(--wish-muted);font-size:10px;">Use Search and page controls to manage records.</div>
                    </div>

                    <div class="wishlist-page-controls">
                        <div class="page-size-wrap">
                            <span>Show</span>
                            <select id="wishlistPageSize" class="page-size-select">
                                <option value="25">25</option>
                                <option value="50" selected>50</option>
                                <option value="100">100</option>
                                <option value="200">200</option>
                            </select>
                            <span>items</span>
                        </div>

                        <button type="button" class="pagination-btn" id="wishlistFirstPage" title="First page">«</button>
                        <button type="button" class="pagination-btn" id="wishlistPrevPage" title="Previous page">‹</button>
                        <span id="wishlistPageNumbers"></span>
                        <button type="button" class="pagination-btn" id="wishlistNextPage" title="Next page">›</button>
                        <button type="button" class="pagination-btn" id="wishlistLastPage" title="Last page">»</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.4/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script> 
 
<script> 
(function(){ 
    'use strict'; 
 
    document.addEventListener('DOMContentLoaded', function(){ 
        const searchInput  = document.getElementById('wishlistSearch'); 
        const table        = document.getElementById('wishlistTable'); 
        const countElement = document.getElementById('visibleWishlistCount'); 
        const noResult     = document.getElementById('wishlistNoResult'); 
 
        const pageInfo     = document.getElementById('wishlistPageInfo'); 
        const pageNumbers  = document.getElementById('wishlistPageNumbers'); 
        const pageSizeSelect= document.getElementById('wishlistPageSize'); 
        const firstPageBtn = document.getElementById('wishlistFirstPage'); 
        const prevPageBtn  = document.getElementById('wishlistPrevPage'); 
        const nextPageBtn  = document.getElementById('wishlistNextPage'); 
        const lastPageBtn  = document.getElementById('wishlistLastPage'); 
 
        let currentPage = 1; 
        let pageSize = parseInt(pageSizeSelect?.value || '50', 10); 
 
        function rows(){ 
            return table ? Array.from(table.querySelectorAll('tbody .wishlist-row')) : []; 
        } 
 
        function visibleRows(){ 
            return rows().filter(row => row.style.display !== 'none'); 
        } 

        function matchingRows(){
            const q = (searchInput?.value || '').toLowerCase().trim();
            return rows().filter(row => {
                if (!q) return true;
                return (row.dataset.search || '').includes(q);
            });
        }
 
        function renderPageNumbers(totalPages){ 
            if (!pageNumbers) return; 
            pageNumbers.innerHTML = ''; 
            if (totalPages <= 1) return; 
 
            const maxButtons = 7; 
            let start = Math.max(1, currentPage - 3); 
            let end = Math.min(totalPages, start + maxButtons - 1); 
 
            if ((end - start + 1) < maxButtons) { 
                start = Math.max(1, end - maxButtons + 1); 
            } 
 
            for (let page = start; page <= end; page++) { 
                const btn = document.createElement('button'); 
                btn.type = 'button'; 
                btn.className = 'pagination-btn' + (page === currentPage ? ' active' : ''); 
                btn.textContent = String(page); 
                btn.addEventListener('click', () => { currentPage = page; renderPagination(); }); 
                pageNumbers.appendChild(btn); 
            } 
        } 
 
        function renderPagination(){ 
            if (!table) return; 
            const matched = matchingRows(); 
            const total = matched.length; 
            const totalPages = Math.max(1, Math.ceil(total / pageSize)); 
 
            if (currentPage > totalPages) currentPage = totalPages; 
            if (currentPage < 1) currentPage = 1; 
 
            rows().forEach(r => r.style.display = 'none'); 
 
            const startIndex = (currentPage - 1) * pageSize; 
            const pageRows = matched.slice(startIndex, startIndex + pageSize); 
 
            pageRows.forEach(row => { row.style.display = ''; }); 
 
            const firstItem = total === 0 ? 0 : startIndex + 1; 
            const lastItem = Math.min(startIndex + pageSize, total); 
 
            if (pageInfo) pageInfo.textContent = 'Showing ' + firstItem + '–' + lastItem + ' of ' + total + ' items'; 
            if (countElement) countElement.textContent = total; 
            if (noResult) noResult.style.display = total === 0 ? 'block' : 'none'; 
 
            if (firstPageBtn) firstPageBtn.disabled = currentPage <= 1; 
            if (prevPageBtn) prevPageBtn.disabled = currentPage <= 1; 
            if (nextPageBtn) nextPageBtn.disabled = currentPage >= totalPages || total === 0; 
            if (lastPageBtn) lastPageBtn.disabled = currentPage >= totalPages || total === 0; 
 
            renderPageNumbers(totalPages); 
        } 
 
        function filterWishlist(resetPage){ 
            if (resetPage !== false) currentPage = 1; 
            renderPagination(); 
        } 
 
        function printWishlist(){ window.print(); } 
        function excelWishlist(){ 
            const data = matchingRows().map(row => ({ 
                'Wishlist ID': row.cells[1]?.innerText.trim() || '', 
                'User ID': row.cells[2]?.innerText.trim() || '', 
                'Variant ID': row.cells[3]?.innerText.trim() || '', 
                'Added Date': row.cells[4]?.innerText.trim() || '' 
            })); 
            if (!window.XLSX) return alert('Excel library is not loaded.'); 
            const ws = XLSX.utils.json_to_sheet(data); 
            const wb = XLSX.utils.book_new(); 
            XLSX.utils.book_append_sheet(wb, ws, 'Wishlist'); 
            XLSX.writeFile(wb, 'wishlist-' + new Date().toISOString().slice(0,10) + '.xlsx'); 
        } 
 
        function pdfWishlist(){ 
            if (!window.jspdf || !window.jspdf.jsPDF) return alert('PDF library is not loaded.'); 
            const body = matchingRows().map(row => [ 
                row.cells[0]?.innerText.trim() || '', 
                row.cells[1]?.innerText.trim() || '', 
                row.cells[2]?.innerText.trim() || '', 
                row.cells[3]?.innerText.trim() || '', 
                row.cells[4]?.innerText.trim() || '' 
            ]); 
 
            const doc = new jspdf.jsPDF({orientation:'portrait',unit:'mm',format:'a4'}); 
            doc.setFontSize(15); doc.text('GatewayLinen - Wishlist Items',14,14); 
            if (typeof doc.autoTable === 'function') { 
                doc.autoTable({startY:22,head:[['#','Wishlist ID','User ID','Variant ID','Added Date']],body:body,styles:{fontSize:9,cellPadding:3},headStyles:{fillColor:[5,150,105]}}); 
            } 
            doc.save('wishlist-' + new Date().toISOString().slice(0,10) + '.pdf'); 
        } 
 
        document.getElementById('printBtn')?.addEventListener('click', printWishlist); 
        document.getElementById('pdfBtn')?.addEventListener('click', pdfWishlist); 
        document.getElementById('excelBtn')?.addEventListener('click', excelWishlist); 
        searchInput?.addEventListener('input', () => filterWishlist()); 
 
        document.querySelectorAll('.delete-wishlist-btn').forEach(btn => { 
            btn.addEventListener('click', function(e){ 
                e.stopPropagation(); 
                if (!confirm('Remove this item from the wishlist?')) e.preventDefault(); 
            }); 
        }); 

        pageSizeSelect?.addEventListener('change', function(){ 
            pageSize = parseInt(this.value || '50', 10); 
            currentPage = 1; 
            renderPagination(); 
        }); 
 
        filterWishlist(); 
    }); 
})(); 
</script> 
 
<?php require_once __DIR__ . '/../includes/footer.php'; ?>