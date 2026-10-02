<?php

session_start();

/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/config/database.php";

/*
|--------------------------------------------------------------------------
| ADMIN INFORMATION
|--------------------------------------------------------------------------
*/

$adminName = $_SESSION['admin_name'] ?? "Administrator";
$adminUsername = $_SESSION['admin_username'] ?? "admin";

/*
|--------------------------------------------------------------------------
| PAGE SETTINGS
|--------------------------------------------------------------------------
*/

$pageTitle  = "GatewayLinen | Dashboard";
$activeMenu = "dashboard";

/*
|--------------------------------------------------------------------------
| SAFE TABLE COUNT
|--------------------------------------------------------------------------
*/

function getTableCount($conn, $tableName)
{
    $allowedTables = [
        "Products",
        "Categories",
        "Orders",
        "Users",
        "Warehouses",
        "ProductReviews"
    ];

    if (!in_array($tableName, $allowedTables, true)) {
        return 0;
    }

    $sql = "SELECT COUNT(*) AS Total FROM dbo." . $tableName;

    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        return 0;
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    sqlsrv_free_stmt($stmt);

    return (int)($row["Total"] ?? 0);
}

/*
|--------------------------------------------------------------------------
| DASHBOARD COUNTS
|--------------------------------------------------------------------------
*/

$totalProducts    = getTableCount($conn, "Products");
$totalCategories  = getTableCount($conn, "Categories");
$totalOrders      = getTableCount($conn, "Orders");
$totalCustomers   = getTableCount($conn, "Users");
$totalWarehouses  = getTableCount($conn, "Warehouses");
$totalReviews     = getTableCount($conn, "ProductReviews");

/*
|--------------------------------------------------------------------------
| CATEGORY CHART DATA
|--------------------------------------------------------------------------
*/

$categoryLabels = [];
$categoryCounts = [];

$catSql = "
    SELECT TOP 6
        c.CategoryName,
        COUNT(p.ProductID) AS ProductCount
    FROM dbo.Categories c
    LEFT JOIN dbo.Products p
        ON c.CategoryID = p.CategoryID
    GROUP BY c.CategoryName
    ORDER BY ProductCount DESC
";

$catStmt = sqlsrv_query($conn, $catSql);

if ($catStmt !== false) {

    while ($row = sqlsrv_fetch_array($catStmt, SQLSRV_FETCH_ASSOC)) {

        $categoryLabels[] = $row["CategoryName"] ?? "Unassigned";

        $categoryCounts[] = (int)($row["ProductCount"] ?? 0);
    }

    sqlsrv_free_stmt($catStmt);
}

/*
|--------------------------------------------------------------------------
| ORDER SCATTER DATA
|--------------------------------------------------------------------------
*/

$scatterData = [];

$ordSql = "
    SELECT TOP 20
        OrderID,
        TotalAmount
    FROM dbo.Orders
    ORDER BY OrderID DESC
";

$ordStmt = sqlsrv_query($conn, $ordSql);

if ($ordStmt !== false) {

    $xIndex = 1;

    while ($row = sqlsrv_fetch_array($ordStmt, SQLSRV_FETCH_ASSOC)) {

        $scatterData[] = [
            "x" => $xIndex++,
            "y" => (float)($row["TotalAmount"] ?? 0)
        ];
    }

    sqlsrv_free_stmt($ordStmt);
}

/*
|--------------------------------------------------------------------------
| REVIEW RATING DATA
|--------------------------------------------------------------------------
*/

$reviewRatings = [
    1 => 0,
    2 => 0,
    3 => 0,
    4 => 0,
    5 => 0
];

$revSql = "
    SELECT
        Rating,
        COUNT(*) AS RatingCount
    FROM dbo.ProductReviews
    GROUP BY Rating
    ORDER BY Rating
";

$revStmt = sqlsrv_query($conn, $revSql);

if ($revStmt !== false) {

    while ($row = sqlsrv_fetch_array($revStmt, SQLSRV_FETCH_ASSOC)) {

        $rating = (int)($row["Rating"] ?? 0);

        if (isset($reviewRatings[$rating])) {

            $reviewRatings[$rating] =
                (int)($row["RatingCount"] ?? 0);
        }
    }

    sqlsrv_free_stmt($revStmt);
}

$histogramCounts = array_values($reviewRatings);

/*
|--------------------------------------------------------------------------
| HEADER + SIDEBAR
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/sidebar.php";

?>

<style>

/*
|--------------------------------------------------------------------------
| DASHBOARD VARIABLES
|--------------------------------------------------------------------------
| IMPORTANT:
| These variables follow the theme already applied to html/body.
| They do NOT create a second theme system.
|--------------------------------------------------------------------------
*/

.dashboard-page {

    --dash-bg: #f5f7fa;
    --dash-card: #ffffff;
    --dash-card-soft: #f8fafc;
    --dash-border: #e2e8f0;

    --dash-heading: #0f172a;
    --dash-text: #475569;
    --dash-muted: #64748b;

    --dash-green: #10b981;
    --dash-green-dark: #059669;

    --dash-shadow:
        0 8px 25px rgba(15, 23, 42, 0.07);

    color: var(--dash-heading);
}


/*
|--------------------------------------------------------------------------
| DARK THEME
|--------------------------------------------------------------------------
| Supports common theme implementations:
| html[data-theme="dark"]
| body.dark-mode
|--------------------------------------------------------------------------
*/

html[data-theme="dark"] .dashboard-page,
body.dark-mode .dashboard-page {

    --dash-bg: #0a1119;
    --dash-card: #111b26;
    --dash-card-soft: #162330;

    --dash-border: #263646;

    --dash-heading: #f8fafc;
    --dash-text: #cbd5e1;
    --dash-muted: #94a3b8;

    --dash-green: #10b981;
    --dash-green-dark: #059669;

    --dash-shadow:
        0 10px 30px rgba(0, 0, 0, 0.28);
}


/*
|--------------------------------------------------------------------------
| MAIN PAGE
|--------------------------------------------------------------------------
*/

.dashboard-page {

    min-height: calc(100vh - 80px);

    background: var(--dash-bg);

    transition:
        background-color 0.25s ease,
        color 0.25s ease;
}


/*
|--------------------------------------------------------------------------
| CONTENT WRAPPER
|--------------------------------------------------------------------------
*/

.dashboard-page .content {

    width: 100%;

    max-width: 1600px;

    margin: 0 auto;

    padding: 28px 30px 50px;

    box-sizing: border-box;
}


/*
|--------------------------------------------------------------------------
| WELCOME
|--------------------------------------------------------------------------
*/

.dashboard-page .welcome {

    background: var(--dash-card);

    border: 1px solid var(--dash-border);

    border-radius: 16px;

    padding: 30px;

    margin-bottom: 24px;

    box-shadow: var(--dash-shadow);

    transition:
        background-color 0.25s ease,
        border-color 0.25s ease,
        box-shadow 0.25s ease;
}

.dashboard-page .welcome h1 {

    margin: 0 0 8px;

    color: var(--dash-heading);

    font-size: 28px;

    font-weight: 750;

    line-height: 1.25;
}

.dashboard-page .welcome p {

    margin: 0;

    max-width: 850px;

    color: var(--dash-text);

    font-size: 15px;

    line-height: 1.7;
}


/*
|--------------------------------------------------------------------------
| STATISTICS GRID
|--------------------------------------------------------------------------
*/

.dashboard-page .stats {

    display: grid;

    grid-template-columns:
        repeat(4, minmax(0, 1fr));

    gap: 18px;

    margin-bottom: 32px;
}


/*
|--------------------------------------------------------------------------
| STAT CARD
|--------------------------------------------------------------------------
*/

.dashboard-page .stat-card {

    position: relative;

    background: var(--dash-card);

    border: 1px solid var(--dash-border);

    border-radius: 16px;

    padding: 22px;

    min-height: 145px;

    box-shadow: var(--dash-shadow);

    transition:
        transform 0.2s ease,
        background-color 0.25s ease,
        border-color 0.25s ease;
}

.dashboard-page .stat-card:hover {

    transform: translateY(-3px);
}

.dashboard-page .stat-top {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;
}

.dashboard-page .stat-title {

    color: var(--dash-muted);

    font-size: 14px;

    font-weight: 650;
}

.dashboard-page .stat-icon {

    width: 44px;

    height: 44px;

    border-radius: 12px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: var(--dash-green);

    background: rgba(16, 185, 129, 0.12);

    flex-shrink: 0;
}

.dashboard-page .stat-icon svg {

    width: 21px;

    height: 21px;
}

.dashboard-page .stat-number {

    margin-top: 18px;

    color: var(--dash-heading);

    font-size: 30px;

    font-weight: 800;

    line-height: 1;
}


/*
|--------------------------------------------------------------------------
| SECTION TITLE
|--------------------------------------------------------------------------
*/

.dashboard-page .section-title {

    margin: 0 0 16px;

    color: var(--dash-heading);

    font-size: 20px;

    font-weight: 750;
}


/*
|--------------------------------------------------------------------------
| CHART GRID
|--------------------------------------------------------------------------
*/

.dashboard-page .charts-grid {

    display: grid;

    grid-template-columns:
        repeat(3, minmax(0, 1fr));

    gap: 20px;

    margin-bottom: 34px;
}


/*
|--------------------------------------------------------------------------
| CHART CARD
|--------------------------------------------------------------------------
*/

.dashboard-page .chart-card {

    background: var(--dash-card);

    border: 1px solid var(--dash-border);

    border-radius: 16px;

    padding: 20px;

    min-width: 0;

    box-shadow: var(--dash-shadow);

    transition:
        background-color 0.25s ease,
        border-color 0.25s ease;
}

.dashboard-page .chart-card h3 {

    margin: 0 0 18px;

    color: var(--dash-heading);

    font-size: 15px;

    font-weight: 700;
}

.dashboard-page .chart-container {

    position: relative;

    width: 100%;

    height: 280px;
}


/*
|--------------------------------------------------------------------------
| QUICK ACTIONS / BUSINESS OVERVIEW
|--------------------------------------------------------------------------
*/

.dashboard-page .actions {

    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 16px;

    margin-bottom: 32px;
}


/*
|--------------------------------------------------------------------------
| ACTION CARD
|--------------------------------------------------------------------------
*/

.dashboard-page .action {

    display: flex;

    align-items: center;

    gap: 15px;

    min-width: 0;

    padding: 18px;

    text-decoration: none;

    background: var(--dash-card);

    border: 1px solid var(--dash-border);

    border-radius: 14px;

    box-shadow: var(--dash-shadow);

    transition:
        transform 0.2s ease,
        border-color 0.2s ease,
        background-color 0.25s ease;
}

.dashboard-page .action:hover {

    transform: translateY(-2px);

    border-color: var(--dash-green);
}

.dashboard-page .action-icon {

    width: 45px;

    height: 45px;

    min-width: 45px;

    border-radius: 12px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: var(--dash-green);

    background: rgba(16, 185, 129, 0.12);
}

.dashboard-page .action-icon svg {

    width: 21px;

    height: 21px;
}

.dashboard-page .action strong {

    display: block;

    margin-bottom: 4px;

    color: var(--dash-heading);

    font-size: 15px;

    font-weight: 700;
}

.dashboard-page .action span {

    display: block;

    color: var(--dash-muted);

    font-size: 13px;

    line-height: 1.5;
}


/*
|--------------------------------------------------------------------------
| MOBILE
|--------------------------------------------------------------------------
*/

@media (max-width: 1200px) {

    .dashboard-page .stats {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

    .dashboard-page .charts-grid {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

    .dashboard-page .chart-card:last-child {

        grid-column: 1 / -1;
    }
}


@media (max-width: 900px) {

    .dashboard-page .content {

        padding: 22px 18px 40px;
    }

    .dashboard-page .charts-grid {

        grid-template-columns: 1fr;
    }

    .dashboard-page .chart-card:last-child {

        grid-column: auto;
    }

    .dashboard-page .actions {

        grid-template-columns: 1fr;
    }
}


@media (max-width: 600px) {

    .dashboard-page .content {

        padding: 16px 12px 30px;
    }

    .dashboard-page .welcome {

        padding: 22px;

        border-radius: 13px;
    }

    .dashboard-page .welcome h1 {

        font-size: 23px;
    }

    .dashboard-page .welcome p {

        font-size: 14px;
    }

    .dashboard-page .stats {

        grid-template-columns: 1fr;

        gap: 12px;
    }

    .dashboard-page .stat-card {

        min-height: 125px;

        padding: 18px;
    }

    .dashboard-page .stat-number {

        font-size: 27px;
    }

    .dashboard-page .chart-card {

        padding: 16px;
    }

    .dashboard-page .chart-container {

        height: 250px;
    }
}

</style>


<!-- ======================================================================
     DASHBOARD
====================================================================== -->

<main class="main dashboard-page">

    <section class="content">


        <!-- ==============================================================
             WELCOME
        ============================================================== -->

        <div class="welcome">

            <h1>
                Welcome back,
                <?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?>
            </h1>

            <p>
                Manage your GatewayLinen products, inventory, orders,
                customers and business operations from one secure
                administration workspace.
            </p>

        </div>


        <!-- ==============================================================
             STATISTICS
        ============================================================== -->

        <div class="stats">


            <!-- PRODUCTS -->

            <div class="stat-card">

                <div class="stat-top">

                    <span class="stat-title">
                        Total Products
                    </span>

                    <div class="stat-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M21 8.5L12 3 3 8.5"></path>
                            <path d="M3 8.5V17l9 5 9-5V8.5"></path>
                            <path d="M12 22V12"></path>
                        </svg>

                    </div>

                </div>

                <div class="stat-number">
                    <?= number_format($totalProducts) ?>
                </div>

            </div>


            <!-- CATEGORIES -->

            <div class="stat-card">

                <div class="stat-top">

                    <span class="stat-title">
                        Categories
                    </span>

                    <div class="stat-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M4 5h16"></path>
                            <path d="M4 12h16"></path>
                            <path d="M4 19h16"></path>
                        </svg>

                    </div>

                </div>

                <div class="stat-number">
                    <?= number_format($totalCategories) ?>
                </div>

            </div>


            <!-- ORDERS -->

            <div class="stat-card">

                <div class="stat-top">

                    <span class="stat-title">
                        Total Orders
                    </span>

                    <div class="stat-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle cx="9" cy="20" r="1"></circle>
                            <circle cx="18" cy="20" r="1"></circle>
                            <path d="M3 4h2l2.2 11h10.9l2-8H6"></path>
                        </svg>

                    </div>

                </div>

                <div class="stat-number">
                    <?= number_format($totalOrders) ?>
                </div>

            </div>


            <!-- CUSTOMERS -->

            <div class="stat-card">

                <div class="stat-top">

                    <span class="stat-title">
                        Customers
                    </span>

                    <div class="stat-icon">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle cx="9" cy="8" r="3"></circle>
                            <path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"></path>
                            <path d="M17 5c2.2.2 4 2 4 4.3"></path>
                        </svg>

                    </div>

                </div>

                <div class="stat-number">
                    <?= number_format($totalCustomers) ?>
                </div>

            </div>

        </div>


        <!-- ==============================================================
             BUSINESS ANALYTICS
        ============================================================== -->

        <h2 class="section-title">
            Business Analytics
        </h2>


        <div class="charts-grid">


            <!-- PIE -->

            <div class="chart-card">

                <h3>
                    Products per Category
                </h3>

                <div class="chart-container">

                    <canvas id="pieChart"></canvas>

                </div>

            </div>


            <!-- SCATTER -->

            <div class="chart-card">

                <h3>
                    Order Values Spread
                </h3>

                <div class="chart-container">

                    <canvas id="scatterPlot"></canvas>

                </div>

            </div>


            <!-- HISTOGRAM -->

            <div class="chart-card">

                <h3>
                    Review Ratings Spread
                </h3>

                <div class="chart-container">

                    <canvas id="histogramChart"></canvas>

                </div>

            </div>

        </div>


        <!-- ==============================================================
             QUICK ACTIONS
        ============================================================== -->

        <h2 class="section-title">
            Quick Actions
        </h2>


        <div class="actions">


            <a
                href="<?= GATEWAY_BASE ?>/products/add.php"
                class="action"
            >

                <div class="action-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M12 5v14"></path>
                        <path d="M5 12h14"></path>
                    </svg>

                </div>

                <div>

                    <strong>
                        Add Product
                    </strong>

                    <span>
                        Create and publish a new product
                    </span>

                </div>

            </a>


            <a
                href="<?= GATEWAY_BASE ?>/categories/add.php"
                class="action"
            >

                <div class="action-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M12 3v18"></path>
                        <path d="M3 12h18"></path>
                    </svg>

                </div>

                <div>

                    <strong>
                        Add Category
                    </strong>

                    <span>
                        Create a new product category
                    </span>

                </div>

            </a>


            <a
                href="<?= GATEWAY_BASE ?>/inventory/index.php"
                class="action"
            >

                <div class="action-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M3 7h18"></path>
                        <path d="M5 7l1-3h12l1 3"></path>
                        <path d="M5 7v13h14V7"></path>
                    </svg>

                </div>

                <div>

                    <strong>
                        View Inventory
                    </strong>

                    <span>
                        Monitor stock and warehouse inventory
                    </span>

                </div>

            </a>


            <a
                href="<?= GATEWAY_BASE ?>/orders/index.php"
                class="action"
            >

                <div class="action-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M4 4h16v16H4z"></path>
                        <path d="M8 8h8"></path>
                        <path d="M8 12h8"></path>
                        <path d="M8 16h5"></path>
                    </svg>

                </div>

                <div>

                    <strong>
                        Manage Orders
                    </strong>

                    <span>
                        View and manage customer orders
                    </span>

                </div>

            </a>

        </div>


        <!-- ==============================================================
             BUSINESS OVERVIEW
        ============================================================== -->

        <h2 class="section-title">
            Business Overview
        </h2>


        <div class="actions">


            <!-- WAREHOUSES -->

            <a
                href="<?= GATEWAY_BASE ?>/warehouses/index.php"
                class="action"
            >

                <div class="action-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M3 21V8l9-5 9 5v13"></path>
                        <path d="M7 21v-8h10v8"></path>
                    </svg>

                </div>

                <div>

                    <strong>
                        Warehouses
                    </strong>

                    <span>
                        <?= number_format($totalWarehouses) ?>
                        registered warehouses
                    </span>

                </div>

            </a>


            <!-- REVIEWS -->

            <a
                href="<?= GATEWAY_BASE ?>/reviews/index.php"
                class="action"
            >

                <div class="action-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M12 3l2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3 6.4 20.2l1.1-6.2L3 9.6l6.2-.9L12 3z"></path>
                    </svg>

                </div>

                <div>

                    <strong>
                        Product Reviews
                    </strong>

                    <span>
                        <?= number_format($totalReviews) ?>
                        customer reviews
                    </span>

                </div>

            </a>


            <!-- CUSTOMERS -->

            <a
                href="<?= GATEWAY_BASE ?>/customers/index.php"
                class="action"
            >

                <div class="action-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <circle cx="9" cy="8" r="3"></circle>
                        <path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"></path>
                    </svg>

                </div>

                <div>

                    <strong>
                        Customers
                    </strong>

                    <span>
                        Manage registered customers
                    </span>

                </div>

            </a>


            <!-- SETTINGS -->

            <a
                href="<?= GATEWAY_BASE ?>/settings/general.php"
                class="action"
            >

                <div class="action-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.7 1.7 0 000-6"></path>
                        <path d="M4.6 9a1.7 1.7 0 000 6"></path>
                        <path d="M9 4.6a1.7 1.7 0 006 0"></path>
                        <path d="M9 19.4a1.7 1.7 0 006 0"></path>
                    </svg>

                </div>

                <div>

                    <strong>
                        Settings
                    </strong>

                    <span>
                        Manage store and system settings
                    </span>

                </div>

            </a>

        </div>

    </section>

</main>


<!-- ======================================================================
     CHART.JS
====================================================================== -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<script>

/*
|--------------------------------------------------------------------------
| DATABASE DATA
|--------------------------------------------------------------------------
*/

const dbCategoryLabels =
    <?= json_encode($categoryLabels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

const dbCategoryCounts =
    <?= json_encode($categoryCounts) ?>;

const dbScatterData =
    <?= json_encode($scatterData) ?>;

const dbHistogramCounts =
    <?= json_encode($histogramCounts) ?>;


/*
|--------------------------------------------------------------------------
| THEME DETECTION
|--------------------------------------------------------------------------
*/

function isDarkTheme() {

    return (
        document.documentElement.getAttribute("data-theme") === "dark" ||
        document.body.classList.contains("dark-mode")
    );
}


/*
|--------------------------------------------------------------------------
| CHART COLORS
|--------------------------------------------------------------------------
*/

function getChartColors() {

    const dark = isDarkTheme();

    return {

        text:
            dark
                ? "#cbd5e1"
                : "#64748b",

        heading:
            dark
                ? "#f8fafc"
                : "#0f172a",

        grid:
            dark
                ? "#334155"
                : "#e2e8f0",

        tooltipBackground:
            dark
                ? "#0f172a"
                : "#ffffff",

        tooltipText:
            dark
                ? "#f8fafc"
                : "#0f172a"

    };
}


/*
|--------------------------------------------------------------------------
| CHART INSTANCES
|--------------------------------------------------------------------------
*/

let pieChart = null;
let scatterChart = null;
let histogramChart = null;


/*
|--------------------------------------------------------------------------
| CREATE CHARTS
|--------------------------------------------------------------------------
*/

function createDashboardCharts() {

    const colors = getChartColors();


    /*
    ----------------------------------------------------------------------
    | PIE CHART
    ----------------------------------------------------------------------
    */

    const pieCanvas =
        document.getElementById("pieChart");

    if (pieCanvas) {

        if (pieChart) {
            pieChart.destroy();
        }

        pieChart = new Chart(
            pieCanvas.getContext("2d"),
            {

                type: "pie",

                data: {

                    labels:
                        dbCategoryLabels.length > 0
                            ? dbCategoryLabels
                            : ["No Categories"],

                    datasets: [

                        {

                            data:
                                dbCategoryCounts.length > 0
                                    ? dbCategoryCounts
                                    : [1],

                            backgroundColor: [

                                "#10b981",
                                "#14b8a6",
                                "#2dd4bf",
                                "#0d9488",
                                "#5eead4",
                                "#115e59"

                            ],

                            borderColor:
                                isDarkTheme()
                                    ? "#111b26"
                                    : "#ffffff",

                            borderWidth: 2

                        }

                    ]

                },

                options: {

                    responsive: true,

                    maintainAspectRatio: false,

                    plugins: {

                        legend: {

                            position: "bottom",

                            labels: {

                                color: colors.text,

                                padding: 15,

                                usePointStyle: true,

                                font: {

                                    size: 12

                                }

                            }

                        },

                        tooltip: {

                            backgroundColor:
                                colors.tooltipBackground,

                            titleColor:
                                colors.tooltipText,

                            bodyColor:
                                colors.tooltipText,

                            borderColor:
                                isDarkTheme()
                                    ? "#334155"
                                    : "#e2e8f0",

                            borderWidth: 1

                        }

                    }

                }

            }
        );
    }


    /*
    ----------------------------------------------------------------------
    | SCATTER CHART
    ----------------------------------------------------------------------
    */

    const scatterCanvas =
        document.getElementById("scatterPlot");

    if (scatterCanvas) {

        if (scatterChart) {
            scatterChart.destroy();
        }

        scatterChart = new Chart(
            scatterCanvas.getContext("2d"),
            {

                type: "scatter",

                data: {

                    datasets: [

                        {

                            label: "Order Amounts ($)",

                            data:
                                dbScatterData.length > 0
                                    ? dbScatterData
                                    : [
                                        {
                                            x: 0,
                                            y: 0
                                        }
                                    ],

                            backgroundColor:
                                "#10b981",

                            borderColor:
                                "#10b981",

                            pointRadius: 5,

                            pointHoverRadius: 7

                        }

                    ]

                },

                options: {

                    responsive: true,

                    maintainAspectRatio: false,

                    scales: {

                        x: {

                            title: {

                                display: true,

                                text: "Order Index",

                                color: colors.text

                            },

                            grid: {

                                color: colors.grid

                            },

                            ticks: {

                                color: colors.text

                            }

                        },

                        y: {

                            title: {

                                display: true,

                                text: "Total Amount ($)",

                                color: colors.text

                            },

                            grid: {

                                color: colors.grid

                            },

                            ticks: {

                                color: colors.text

                            }

                        }

                    },

                    plugins: {

                        legend: {

                            labels: {

                                color: colors.text

                            }

                        },

                        tooltip: {

                            backgroundColor:
                                colors.tooltipBackground,

                            titleColor:
                                colors.tooltipText,

                            bodyColor:
                                colors.tooltipText

                        }

                    }

                }

            }
        );
    }


    /*
    ----------------------------------------------------------------------
    | HISTOGRAM / BAR
    ----------------------------------------------------------------------
    */

    const histogramCanvas =
        document.getElementById("histogramChart");

    if (histogramCanvas) {

        if (histogramChart) {
            histogramChart.destroy();
        }

        histogramChart = new Chart(
            histogramCanvas.getContext("2d"),
            {

                type: "bar",

                data: {

                    labels: [

                        "1 Star",
                        "2 Stars",
                        "3 Stars",
                        "4 Stars",
                        "5 Stars"

                    ],

                    datasets: [

                        {

                            label: "Review Frequency",

                            data:
                                dbHistogramCounts,

                            backgroundColor:
                                "#10b981",

                            borderColor:
                                "#059669",

                            borderWidth: 1,

                            borderRadius: 7,

                            maxBarThickness: 55

                        }

                    ]

                },

                options: {

                    responsive: true,

                    maintainAspectRatio: false,

                    scales: {

                        x: {

                            grid: {

                                color: colors.grid

                            },

                            ticks: {

                                color: colors.text

                            }

                        },

                        y: {

                            beginAtZero: true,

                            title: {

                                display: true,

                                text: "Review Count",

                                color: colors.text

                            },

                            grid: {

                                color: colors.grid

                            },

                            ticks: {

                                color: colors.text

                            }

                        }

                    },

                    plugins: {

                        legend: {

                            display: false

                        },

                        tooltip: {

                            backgroundColor:
                                colors.tooltipBackground,

                            titleColor:
                                colors.tooltipText,

                            bodyColor:
                                colors.tooltipText

                        }

                    }

                }

            }
        );
    }
}


/*
|--------------------------------------------------------------------------
| INITIAL LOAD
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "DOMContentLoaded",
    function () {

        createDashboardCharts();

    }
);


/*
|--------------------------------------------------------------------------
| THEME CHANGE SUPPORT
|--------------------------------------------------------------------------
| Header/theme system changes data-theme or dark-mode.
| MutationObserver catches that change and rebuilds the charts.
|--------------------------------------------------------------------------
*/

const dashboardThemeObserver =
    new MutationObserver(
        function (mutations) {

            let themeChanged = false;

            mutations.forEach(
                function (mutation) {

                    if (
                        mutation.type === "attributes" &&
                        (
                            mutation.attributeName === "data-theme" ||
                            mutation.attributeName === "class"
                        )
                    ) {

                        themeChanged = true;

                    }

                }
            );

            if (themeChanged) {

                setTimeout(
                    function () {

                        createDashboardCharts();

                    },
                    50
                );

            }

        }
    );


dashboardThemeObserver.observe(
    document.documentElement,
    {
        attributes: true
    }
);


dashboardThemeObserver.observe(
    document.body,
    {
        attributes: true
    }
);

</script>


<?php

require_once __DIR__ . "/includes/footer.php";

?>