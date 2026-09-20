<?php

session_start();

/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/

if (empty($_SESSION["admin_id"])) {

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

$adminName = $_SESSION["admin_name"] ?? "Administrator";

$adminUsername = $_SESSION["admin_username"] ?? "admin";


/*
|--------------------------------------------------------------------------
| PAGE SETTINGS
|--------------------------------------------------------------------------
*/

$pageTitle = "GatewayLinen | Dashboard";

$activeMenu = "dashboard";


/*
|--------------------------------------------------------------------------
| COUNT FUNCTION
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

    /*
    |--------------------------------------------------------------------------
    | SECURITY
    |--------------------------------------------------------------------------
    */

    if (!in_array($tableName, $allowedTables, true)) {
        return 0;
    }


    /*
    |--------------------------------------------------------------------------
    | QUERY
    |--------------------------------------------------------------------------
    */

    $sql = "
        SELECT COUNT(*) AS Total
        FROM dbo." . $tableName;


    $stmt = sqlsrv_query($conn, $sql);


    /*
    |--------------------------------------------------------------------------
    | QUERY ERROR
    |--------------------------------------------------------------------------
    */

    if ($stmt === false) {
        return 0;
    }


    /*
    |--------------------------------------------------------------------------
    | FETCH
    |--------------------------------------------------------------------------
    */

    $row = sqlsrv_fetch_array(
        $stmt,
        SQLSRV_FETCH_ASSOC
    );


    sqlsrv_free_stmt($stmt);


    return (int)($row["Total"] ?? 0);
}


/*
|--------------------------------------------------------------------------
| DASHBOARD COUNTS
|--------------------------------------------------------------------------
*/

$totalProducts = getTableCount(
    $conn,
    "Products"
);


$totalCategories = getTableCount(
    $conn,
    "Categories"
);


$totalOrders = getTableCount(
    $conn,
    "Orders"
);


$totalCustomers = getTableCount(
    $conn,
    "Users"
);


$totalWarehouses = getTableCount(
    $conn,
    "Warehouses"
);


$totalReviews = getTableCount(
    $conn,
    "ProductReviews"
);

?>


<?php
/*
|--------------------------------------------------------------------------
| COMMON HEADER
|--------------------------------------------------------------------------
| IMPORTANT:
| header.php already contains the ONE common top header.
| Do NOT create another topbar here.
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/includes/header.php";
?>


<?php
/*
|--------------------------------------------------------------------------
| COMMON SIDEBAR
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/includes/sidebar.php";
?>


<!-- =====================================================================
     MAIN CONTENT
====================================================================== -->

<main class="main">


    <!-- =================================================================
         DASHBOARD CONTENT
    ================================================================== -->

    <section class="content">


        <!-- =============================================================
             WELCOME SECTION
        ============================================================== -->

        <div class="welcome">

            <h1>

                Welcome back,
                <?= htmlspecialchars($adminName) ?>

            </h1>


            <p>

                Manage your GatewayLinen
                products, inventory, orders,
                customers and business operations
                from one secure administration workspace.

            </p>

        </div>


        <!-- =============================================================
             STATISTICS
        ============================================================== -->

        <div class="stats">


            <!-- =========================================================
                 TOTAL PRODUCTS
            ========================================================== -->

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
                            aria-hidden="true"
                        >

                            <path
                                d="M21 8.5L12 3 3 8.5"
                            />

                            <path
                                d="M3 8.5V17l9 5 9-5V8.5"
                            />

                            <path
                                d="M12 22V12"
                            />

                        </svg>

                    </div>

                </div>


                <div class="stat-number">

                    <?= number_format($totalProducts) ?>

                </div>

            </div>


            <!-- =========================================================
                 CATEGORIES
            ========================================================== -->

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
                            aria-hidden="true"
                        >

                            <path d="M4 5h16" />

                            <path d="M4 12h16" />

                            <path d="M4 19h16" />

                        </svg>

                    </div>

                </div>


                <div class="stat-number">

                    <?= number_format($totalCategories) ?>

                </div>

            </div>


            <!-- =========================================================
                 TOTAL ORDERS
            ========================================================== -->

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
                            aria-hidden="true"
                        >

                            <circle
                                cx="9"
                                cy="20"
                                r="1"
                            />

                            <circle
                                cx="18"
                                cy="20"
                                r="1"
                            />

                            <path
                                d="M3 4h2l2.2 11h10.9l2-8H6"
                            />

                        </svg>

                    </div>

                </div>


                <div class="stat-number">

                    <?= number_format($totalOrders) ?>

                </div>

            </div>


            <!-- =========================================================
                 CUSTOMERS
            ========================================================== -->

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
                            aria-hidden="true"
                        >

                            <circle
                                cx="9"
                                cy="8"
                                r="3"
                            />

                            <path
                                d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"
                            />

                            <path
                                d="M17 5c2.2.2 4 2 4 4.3"
                            />

                        </svg>

                    </div>

                </div>


                <div class="stat-number">

                    <?= number_format($totalCustomers) ?>

                </div>

            </div>


        </div>


        <!-- =============================================================
             QUICK ACTIONS
        ============================================================== -->

        <h2 class="section-title">
            Quick Actions
        </h2>


        <div class="actions">


            <!-- =========================================================
                 ADD PRODUCT
            ========================================================== -->

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
                        aria-hidden="true"
                    >

                        <path d="M12 5v14" />

                        <path d="M5 12h14" />

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


            <!-- =========================================================
                 ADD CATEGORY
            ========================================================== -->

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
                        aria-hidden="true"
                    >

                        <path d="M12 3v18" />

                        <path d="M3 12h18" />

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


            <!-- =========================================================
                 INVENTORY
            ========================================================== -->

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
                        aria-hidden="true"
                    >

                        <path d="M3 7h18" />

                        <path d="M5 7l1-3h12l1 3" />

                        <path d="M5 7v13h14V7" />

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


            <!-- =========================================================
                 ORDERS
            ========================================================== -->

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
                        aria-hidden="true"
                    >

                        <path d="M4 4h16v16H4z" />

                        <path d="M8 8h8" />

                        <path d="M8 12h8" />

                        <path d="M8 16h5" />

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


        <!-- =============================================================
             BUSINESS OVERVIEW
        ============================================================== -->

        <h2 class="section-title">
            Business Overview
        </h2>


        <div class="actions">


            <!-- =========================================================
                 WAREHOUSES
            ========================================================== -->

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
                        aria-hidden="true"
                    >

                        <path d="M3 21V8l9-5 9 5v13" />

                        <path d="M7 21v-8h10v8" />

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


            <!-- =========================================================
                 REVIEWS
            ========================================================== -->

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
                        aria-hidden="true"
                    >

                        <path
                            d="M12 3l2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3 6.4 20.2l1.1-6.2L3 9.6l6.2-.9L12 3z"
                        />

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


            <!-- =========================================================
                 CUSTOMERS
            ========================================================== -->

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
                        aria-hidden="true"
                    >

                        <circle
                            cx="9"
                            cy="8"
                            r="3"
                        />

                        <path
                            d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"
                        />

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


            <!-- =========================================================
                 SETTINGS
            ========================================================== -->

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
                        aria-hidden="true"
                    >

                        <circle
                            cx="12"
                            cy="12"
                            r="3"
                        />

                        <path
                            d="M19.4 15a1.7 1.7 0 000-6"
                        />

                        <path
                            d="M4.6 9a1.7 1.7 0 000 6"
                        />

                        <path
                            d="M9 4.6a1.7 1.7 0 006 0"
                        />

                        <path
                            d="M9 19.4a1.7 1.7 0 006 0"
                        />

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


<?php
/*
|--------------------------------------------------------------------------
| COMMON FOOTER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/includes/footer.php";
?>