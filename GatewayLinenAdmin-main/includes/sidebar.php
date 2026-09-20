<?php
/*
|--------------------------------------------------------------------------
| GatewayLinen Admin Sidebar
|--------------------------------------------------------------------------
| Location:
| C:\wamp64\www\GatewayLinen\GatewayLinenadmin-main\includes\sidebar.php
|--------------------------------------------------------------------------
| Admin Base:
| /GatewayLinen/GatewayLinenAdmin-main-main
|--------------------------------------------------------------------------
*/

if (!defined('GATEWAY_BASE')) {
    define('GATEWAY_BASE', '/GatewayLinen/GatewayLinenAdmin-main');
}

$activeMenu = $activeMenu ?? '';

/*
|--------------------------------------------------------------------------
| ADMIN URL HELPER
|--------------------------------------------------------------------------
| This creates correct absolute URLs from every admin page.
|--------------------------------------------------------------------------
*/

if (!function_exists('gateway_admin_url')) {
    function gateway_admin_url(string $path = ''): string
    {
        $base = rtrim(GATEWAY_BASE, '/');
        $path = ltrim($path, '/');

        if ($path === '') {
            return $base . '/';
        }

        return $base . '/' . $path;
    }
}

/*
|--------------------------------------------------------------------------
| ACTIVE MENU
|--------------------------------------------------------------------------
*/

if (!function_exists('gateway_menu_active')) {
    function gateway_menu_active(string $menu): string
    {
        global $activeMenu;

        return ($activeMenu === $menu) ? 'active' : '';
    }
}
?>

<style>
    /* =========================================================
   GATEWAYLINEN ADMIN SIDEBAR
========================================================= */

    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;

        width: 255px;

        z-index: 1000;

        display: flex;
        flex-direction: column;

        padding: 14px 11px;

        box-sizing: border-box;

        background: linear-gradient(180deg,
                #0a1119 0%,
                #0d1620 100%);

        border-right: 1px solid #1e2d3d;

        color: #f0f4f8;

        overflow-y: auto;

        box-shadow: 8px 0 30px rgba(0, 0, 0, 0.4);

        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, .16) transparent;
    }

    .sidebar::-webkit-scrollbar {
        width: 4px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: transparent;
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, .16);
        border-radius: 10px;
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, .28);
    }


    /* =========================================================
   BRAND
========================================================= */

    .sidebar-brand {
        display: flex;
        align-items: center;

        gap: 10px;

        min-height: 48px;

        padding: 0 8px 13px;

        margin-bottom: 9px;

        border-bottom: 1px solid #1e2d3d;
    }

    .sidebar-logo {
        width: 35px;
        height: 35px;

        flex-shrink: 0;

        display: flex;
        align-items: center;
        justify-content: center;

        overflow: hidden;

        border-radius: 50%;

        background: #0a1119;

        border: 2px solid #10b981;

        box-shadow:
            0 0 0 3px rgba(16, 185, 129, .12);
    }

    .sidebar-logo img {
        width: 100%;
        height: 100%;

        object-fit: contain;

        background: #ffffff;

        border-radius: 50%;
    }

    .sidebar-logo-fallback {
        display: none;

        width: 100%;
        height: 100%;

        align-items: center;
        justify-content: center;

        color: #10b981;

        background: #0d1620;

        font-family: Georgia, "Times New Roman", serif;

        font-size: 11px;

        font-weight: 800;

        letter-spacing: .5px;
    }

    .sidebar-brand-name {
        color: #f0f4f8;

        font-family: Georgia, "Times New Roman", serif;

        font-size: 17px;

        font-weight: 700;

        letter-spacing: -.2px;

        white-space: nowrap;

        background: linear-gradient(90deg,
                #f0f4f8,
                #10b981);

        -webkit-background-clip: text;
        background-clip: text;

        -webkit-text-fill-color: transparent;
    }


    /* =========================================================
   SECTION
========================================================= */

    .sidebar-section {
        margin-bottom: 7px;
    }

    .sidebar-section-title {
        padding: 10px 10px 6px;

        color: #5f7488;

        font-size: 8.5px;

        font-weight: 800;

        letter-spacing: 1.15px;

        text-transform: uppercase;
    }


    /* =========================================================
   MENU ITEM
========================================================= */

    .sidebar-menu-item {
        position: relative;

        display: flex;
        align-items: center;

        width: 100%;

        min-height: 37px;

        gap: 10px;

        padding: 8px 10px;

        margin-bottom: 2px;

        box-sizing: border-box;

        border-radius: 7px;

        color: #a8b8c8;

        text-decoration: none;

        font-size: 10.5px;

        font-weight: 600;

        transition: all .18s ease;
    }

    .sidebar-menu-item:hover {
        color: #f0f4f8;

        background: rgba(16, 185, 129, .08);

        transform: translateX(2px);
    }

    .sidebar-menu-item.active {
        color: #ffffff;

        font-weight: 700;

        background: linear-gradient(90deg,
                #059669,
                #10b981);

        box-shadow:
            0 4px 13px rgba(16, 185, 129, .28);
    }

    .sidebar-menu-item.active:hover {
        transform: none;

        background: linear-gradient(90deg,
                #059669,
                #10b981);
    }

    .sidebar-menu-item.active::before {
        content: "";

        position: absolute;

        left: -11px;

        top: 50%;

        transform: translateY(-50%);

        width: 3px;

        height: 20px;

        border-radius: 0 3px 3px 0;

        background: #10b981;

        box-shadow:
            0 0 12px rgba(16, 185, 129, .6);
    }


    /* =========================================================
   ICON
========================================================= */

    .sidebar-icon {
        width: 19px;
        height: 19px;

        flex-shrink: 0;

        display: flex;
        align-items: center;
        justify-content: center;

        color: #5f7488;

        transition: color .18s ease;
    }

    .sidebar-icon svg {
        width: 15px;
        height: 15px;

        fill: none;

        stroke: currentColor;

        stroke-width: 1.7;

        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .sidebar-menu-item:hover .sidebar-icon {
        color: #10b981;
    }

    .sidebar-menu-item.active .sidebar-icon {
        color: #ffffff;
    }


    /* =========================================================
   TEXT
========================================================= */

    .sidebar-text {
        flex: 1;

        white-space: nowrap;

        overflow: hidden;

        text-overflow: ellipsis;
    }


    /* =========================================================
   BADGE
========================================================= */

    .sidebar-badge {
        min-width: 18px;
        height: 17px;

        padding: 0 5px;

        display: inline-flex;

        align-items: center;
        justify-content: center;

        border-radius: 20px;

        background: rgba(16, 185, 129, .15);

        color: #10b981;

        font-size: 8px;

        font-weight: 700;
    }

    .sidebar-menu-item.active .sidebar-badge {
        background: rgba(255, 255, 255, .2);

        color: #ffffff;
    }


    /* =========================================================
   BOTTOM
========================================================= */

    .sidebar-bottom {
        margin-top: auto;

        padding-top: 10px;

        border-top: 1px solid #1e2d3d;
    }


    /* =========================================================
   LOGOUT
========================================================= */

    .sidebar-logout {
        color: #a8b8c8;
    }

    .sidebar-logout .sidebar-icon {
        color: #5f7488;
    }

    .sidebar-logout:hover {
        color: #fca5a5;

        background: rgba(239, 68, 68, .1);
    }

    .sidebar-logout:hover .sidebar-icon {
        color: #f87171;
    }


    /* =========================================================
   MOBILE OVERLAY
========================================================= */

    .sidebar-overlay {
        display: none;

        position: fixed;

        inset: 0;

        z-index: 999;

        background: rgba(3, 12, 20, .7);

        backdrop-filter: blur(3px);

        -webkit-backdrop-filter: blur(3px);
    }


    /* =========================================================
   MOBILE
========================================================= */

    @media (max-width: 900px) {

        .sidebar {
            left: -270px;

            width: 260px;

            transition: left .25s ease;
        }

        .sidebar.mobile-open {
            left: 0;

            box-shadow:
                8px 0 40px rgba(0, 0, 0, .6);
        }

        .sidebar-overlay.mobile-open {
            display: block;
        }
    }


    /* =========================================================
   SMALL MOBILE
========================================================= */

    @media (max-width: 480px) {

        .sidebar {
            width: 250px;

            left: -260px;
        }

        .sidebar.mobile-open {
            left: 0;
        }
    }


    /* =========================================================
   PRINT
========================================================= */

    @media print {

        .sidebar,
        .sidebar-overlay {
            display: none !important;
        }
    }
</style>


<!-- =========================================================
     MOBILE OVERLAY
========================================================= -->

<div class="sidebar-overlay"
    id="sidebarOverlay"
    onclick="closeMobileSidebar()"></div>


<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside class="sidebar"
    id="adminSidebar">


    <!-- =====================================================
         BRAND
    ====================================================== -->

    <div class="sidebar-brand">

        <div class="sidebar-logo">

            <img src="<?= htmlspecialchars(
                            gateway_admin_url('uploads/logo/logo.png'),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                alt="GatewayLinen"
                onerror="
                    this.style.display='none';
                    this.nextElementSibling.style.display='flex';
                ">

            <div class="sidebar-logo-fallback">
                GL
            </div>

        </div>


        <div class="sidebar-brand-name">
            GatewayLinen
        </div>

    </div>


    <!-- =====================================================
         MAIN
    ====================================================== -->

    <div class="sidebar-section">

        <div class="sidebar-section-title">
            Main
        </div>


        <!-- DASHBOARD -->

        <a href="<?= htmlspecialchars(
                        gateway_admin_url('dashboard.php'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
            class="sidebar-menu-item <?= gateway_menu_active('dashboard') ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <rect x="3"
                        y="3"
                        width="7"
                        height="7"
                        rx="1"></rect>

                    <rect x="14"
                        y="3"
                        width="7"
                        height="7"
                        rx="1"></rect>

                    <rect x="3"
                        y="14"
                        width="7"
                        height="7"
                        rx="1"></rect>

                    <rect x="14"
                        y="14"
                        width="7"
                        height="7"
                        rx="1"></rect>

                </svg>

            </div>

            <span class="sidebar-text">
                Dashboard
            </span>

        </a>


        <!-- CATEGORIES -->

        <a href="<?= htmlspecialchars(
                        gateway_admin_url('categories/index.php'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
            class="sidebar-menu-item <?= gateway_menu_active('categories') ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <path d="M4 4h7v7H4z"></path>
                    <path d="M13 4h7v7h-7z"></path>
                    <path d="M4 13h7v7H4z"></path>
                    <path d="M13 13h7v7h-7z"></path>

                </svg>

            </div>

            <span class="sidebar-text">
                Categories
            </span>

        </a>


        <!-- PRODUCTS -->

        <a href="<?= htmlspecialchars(
                        gateway_admin_url('products/index.php'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
            class="sidebar-menu-item <?= gateway_menu_active('products') ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <path d="M3 8.5L12 3l9 5.5"></path>

                    <path d="M3 8.5V17l9 5 9-5V8.5"></path>

                    <path d="M12 22V12"></path>

                    <path d="M3.5 8.5L12 13l8.5-4.5"></path>

                </svg>

            </div>

            <span class="sidebar-text">
                Products
            </span>

        </a>


        <!-- INVENTORY -->

        <a href="<?= htmlspecialchars(
                        gateway_admin_url('inventory/index.php'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
            class="sidebar-menu-item <?= gateway_menu_active('inventory') ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <path d="M4 7h16"></path>

                    <path d="M6 7l1-3h10l1 3"></path>

                    <path d="M5 7v13h14V7"></path>

                    <path d="M9 11h6"></path>

                </svg>

            </div>

            <span class="sidebar-text">
                Inventory
            </span>

        </a>


        <!-- ORDERS -->

        <a href="<?= htmlspecialchars(
                        gateway_admin_url('orders/index.php'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
            class="sidebar-menu-item <?= gateway_menu_active('orders') ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <circle cx="9"
                        cy="20"
                        r="1"></circle>

                    <circle cx="18"
                        cy="20"
                        r="1"></circle>

                    <path d="M3 4h2l2.2 11h10.9l2-8H6"></path>

                </svg>

            </div>

            <span class="sidebar-text">
                Orders
            </span>

        </a>


        <!-- CUSTOMERS -->

        <a href="<?= htmlspecialchars(
                        gateway_admin_url('customers/index.php'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
            class="sidebar-menu-item <?= gateway_menu_active('customers') ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <circle cx="9"
                        cy="8"
                        r="3"></circle>

                    <path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"></path>

                    <circle cx="18"
                        cy="9"
                        r="2"></circle>

                    <path d="M16 15c2.8.5 4.5 2.3 4.5 5"></path>

                </svg>

            </div>

            <span class="sidebar-text">
                Customers
            </span>

        </a>

    </div>


    <!-- =====================================================
         SALES & MARKETING
    ====================================================== -->

    <div class="sidebar-section">

        <div class="sidebar-section-title">
            Sales &amp; Marketing
        </div>


        <!-- WHOLESALE -->

        <a href="<?= htmlspecialchars(
                        gateway_admin_url('wholesale/index.php'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
            class="sidebar-menu-item <?= gateway_menu_active('wholesale') ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <path d="M3 21V7l9-4 9 4v14"></path>

                    <path d="M3 10h18"></path>

                    <path d="M8 10v11"></path>

                    <path d="M16 10v11"></path>

                </svg>

            </div>

            <span class="sidebar-text">
                Wholesale
            </span>

        </a>


        <!-- QUOTES -->

        <a href="<?= htmlspecialchars(
                        gateway_admin_url('quotes/index.php'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
            class="sidebar-menu-item <?= gateway_menu_active('quotes') ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <path d="M6 3h12v18H6z"></path>

                    <path d="M9 7h6"></path>

                    <path d="M9 11h6"></path>

                    <path d="M9 15h4"></path>

                </svg>

            </div>

            <span class="sidebar-text">
                Quotes
            </span>

        </a>


        <!-- BULK INQUIRIES -->

        <a href="<?= htmlspecialchars(
                        gateway_admin_url('bulk-inquiries/index.php'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
            class="sidebar-menu-item <?= gateway_menu_active('bulk-inquiries') ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <path d="M4 5h16v12H4z"></path>

                    <path d="M8 21h8"></path>

                    <path d="M12 17v4"></path>

                    <path d="M8 9h8"></path>

                    <path d="M8 13h5"></path>

                </svg>

            </div>

            <span class="sidebar-text">
                Bulk Inquiries
            </span>

        </a>


        <!-- WISHLIST -->

        <a href="<?= htmlspecialchars(
                        gateway_admin_url('wishlist/index.php'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
            class="sidebar-menu-item <?= gateway_menu_active('wishlist') ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <path d="M20.8 8.8c0 5.5-8.8 10.2-8.8 10.2S3.2 14.3 3.2 8.8A5.1 5.1 0 018.3 4c1.6 0 3 .7 3.7 1.9C12.7 4.7 14.1 4 15.7 4a5.1 5.1 0 015.1 4.8z"></path>

                </svg>

            </div>

            <span class="sidebar-text">
                Wishlist
            </span>

        </a>


        <!-- COUPONS -->

        <a href="<?= htmlspecialchars(
                        gateway_admin_url('coupons/index.php'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
            class="sidebar-menu-item <?= gateway_menu_active('coupons') ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <path d="M20 12a2 2 0 010-4V5a2 2 0 00-2-2H6a2 2 0 00-2 2v3a2 2 0 010 4v3a2 2 0 002 2h12a2 2 0 002-2v-3z"></path>

                    <path d="M9 9h.01"></path>

                    <path d="M15 15h.01"></path>

                    <path d="M9 15l6-6"></path>

                </svg>

            </div>

            <span class="sidebar-text">
                Coupons
            </span>

        </a>


        <!-- REVIEWS -->

        <a href="<?= htmlspecialchars(
                        gateway_admin_url('reviews/index.php'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
            class="sidebar-menu-item <?= gateway_menu_active('reviews') ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <path d="M12 3l2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3 6.4 20.2l1.1-6.2L3 9.6l6.2-.9L12 3z"></path>

                </svg>

            </div>

            <span class="sidebar-text">
                Reviews
            </span>

        </a>


        <!-- NEWSLETTER -->

        <a href="<?= htmlspecialchars(
                        gateway_admin_url('newsletter/index.php'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
            class="sidebar-menu-item <?= gateway_menu_active('newsletter') ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <rect x="3"
                        y="5"
                        width="18"
                        height="14"
                        rx="2"></rect>

                    <path d="M3 7l9 6 9-6"></path>

                </svg>

            </div>

            <span class="sidebar-text">
                Newsletter
            </span>

        </a>


        <!-- BACK IN STOCK -->

        <a href="<?= htmlspecialchars(
                        gateway_admin_url('back-in-stock/index.php'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
            class="sidebar-menu-item <?= gateway_menu_active('back-in-stock') ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <path d="M4 4v6h6"></path>

                    <path d="M20 20v-6h-6"></path>

                    <path d="M20 9a8 8 0 00-14-5L4 10"></path>

                    <path d="M4 15a8 8 0 0014 5l2-6"></path>

                </svg>

            </div>

            <span class="sidebar-text">
                Back in Stock
            </span>

        </a>

    </div>


    <!-- =====================================================
         OPERATIONS
    ====================================================== -->

    <div class="sidebar-section">

        <div class="sidebar-section-title">
            Operations
        </div>


        <!-- WAREHOUSES -->

        <a href="<?= htmlspecialchars(
                        gateway_admin_url('warehouses/index.php'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
            class="sidebar-menu-item <?= gateway_menu_active('warehouses') ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <path d="M3 10l9-6 9 6"></path>

                    <path d="M5 9v11h14V9"></path>

                    <path d="M9 20v-6h6v6"></path>

                    <path d="M8 11h8"></path>

                </svg>

            </div>

            <span class="sidebar-text">
                Warehouses
            </span>

        </a>


        <!-- PRODUCT VARIANTS -->

        <a href="<?= htmlspecialchars(
                        gateway_admin_url('variants/index.php'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
            class="sidebar-menu-item <?= gateway_menu_active('variants') ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <path d="M4 4h7v7H4z"></path>

                    <path d="M13 4h7v7h-7z"></path>

                    <path d="M4 13h7v7H4z"></path>

                    <path d="M13 13h7v7h-7z"></path>

                </svg>

            </div>

            <span class="sidebar-text">
                Product Variants
            </span>

        </a>


        <!-- STOCK MOVEMENTS -->

        <a href="<?= htmlspecialchars(
                        gateway_admin_url('inventory/stock-movements.php'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
            class="sidebar-menu-item <?= gateway_menu_active('stock-movements') ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <path d="M7 7h11l-3-3"></path>

                    <path d="M18 7l-3 3"></path>

                    <path d="M17 17H6l3 3"></path>

                    <path d="M6 17l3-3"></path>

                </svg>

            </div>

            <span class="sidebar-text">
                Stock Movements
            </span>

        </a>


        <!-- TAXES & SHIPPING -->

        <a href="<?= htmlspecialchars(
                        gateway_admin_url('taxes-shipping/index.php'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
            class="sidebar-menu-item <?= gateway_menu_active('taxes-shipping') ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <path d="M3 6h11v11H3z"></path>

                    <path d="M14 10h4l3 3v4h-7z"></path>

                    <circle cx="7"
                        cy="19"
                        r="2"></circle>

                    <circle cx="18"
                        cy="19"
                        r="2"></circle>

                </svg>

            </div>

            <span class="sidebar-text">
                Taxes &amp; Shipping
            </span>

        </a>

    </div>


    <!-- =====================================================
         SYSTEM
    ====================================================== -->

    <div class="sidebar-section">

        <div class="sidebar-section-title">
            System
        </div>


        <!-- USERS -->

        <a href="<?= htmlspecialchars(
                        gateway_admin_url('users/index.php'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
            class="sidebar-menu-item <?= gateway_menu_active('users') ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <circle cx="9"
                        cy="8"
                        r="3"></circle>

                    <path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"></path>

                    <path d="M16 8h5"></path>

                    <path d="M18.5 5.5v5"></path>

                </svg>

            </div>

            <span class="sidebar-text">
                Users
            </span>

        </a>


        <!-- ROLES & PERMISSIONS -->

        <a href="<?= htmlspecialchars(
                        gateway_admin_url('roles-permissions/index.php'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
            class="sidebar-menu-item <?= gateway_menu_active('roles-permissions') ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <circle cx="12"
                        cy="8"
                        r="3"></circle>

                    <path d="M5 21c0-4 3-7 7-7s7 3 7 7"></path>

                    <path d="M19 5l2 2-2 2"></path>

                </svg>

            </div>

            <span class="sidebar-text">
                Roles &amp; Permissions
            </span>

        </a>


        <!-- SETTINGS -->

        <a href="<?= htmlspecialchars(
                        gateway_admin_url('settings/general.php'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
            class="sidebar-menu-item <?= gateway_menu_active('settings') ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <circle cx="12"
                        cy="12"
                        r="3"></circle>

                    <path d="M19 12a7 7 0 01-.2 1.7l2 1.5-2 3.4-2.3-1a8 8 0 01-3 1.7L13 21H9l-.5-1.7a8 8 0 01-3-1.7l-2.3 1-2-3.4 2-1.5A7 7 0 013 12c0-.6.1-1.2.2-1.7l-2-1.5 2-3.4 2.3 1a8 8 0 013-1.7L9 3h4l.5 1.7a8 8 0 013 1.7l2.3-1 2 3.4-2 1.5c.1.5.2 1.1.2 1.7z"></path>

                </svg>

            </div>

            <span class="sidebar-text">
                System Settings
            </span>

        </a>


        <!-- AUDIT LOGS -->

        <a href="<?= htmlspecialchars(
                        gateway_admin_url('audit-logs/index.php'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
            class="sidebar-menu-item <?= gateway_menu_active('audit-logs') ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <path d="M4 4h16v16H4z"></path>

                    <path d="M8 8h8"></path>

                    <path d="M8 12h8"></path>

                    <path d="M8 16h5"></path>

                </svg>

            </div>

            <span class="sidebar-text">
                Audit Logs
            </span>

        </a>

    </div>


    <!-- =====================================================
         LOGOUT
    ====================================================== -->

    <div class="sidebar-bottom">

        <a href="<?= htmlspecialchars(
                        gateway_admin_url('logout.php'),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
            class="sidebar-menu-item sidebar-logout"
            onclick="return confirmLogout();">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <path d="M10 17l5-5-5-5"></path>

                    <path d="M15 12H3"></path>

                    <path d="M21 3v18"></path>

                </svg>

            </div>

            <span class="sidebar-text">
                Logout
            </span>

        </a>

    </div>

</aside>


<script>
    (function() {

        'use strict';


        /* =====================================================
           CLOSE SIDEBAR
        ====================================================== */

        window.closeMobileSidebar = function() {

            var sidebar =
                document.getElementById('adminSidebar');

            var overlay =
                document.getElementById('sidebarOverlay');


            if (sidebar) {

                sidebar.classList.remove(
                    'mobile-open'
                );

            }


            if (overlay) {

                overlay.classList.remove(
                    'mobile-open'
                );

            }


            document.body.classList.remove(
                'sidebar-mobile-open'
            );

        };


        /* =====================================================
           OPEN SIDEBAR
        ====================================================== */

        window.openMobileSidebar = function() {

            var sidebar =
                document.getElementById('adminSidebar');

            var overlay =
                document.getElementById('sidebarOverlay');


            if (sidebar) {

                sidebar.classList.add(
                    'mobile-open'
                );

            }


            if (overlay) {

                overlay.classList.add(
                    'mobile-open'
                );

            }


            document.body.classList.add(
                'sidebar-mobile-open'
            );

        };


        /* =====================================================
           TOGGLE SIDEBAR
        ====================================================== */

        window.toggleMobileSidebar = function() {

            var sidebar =
                document.getElementById('adminSidebar');


            if (!sidebar) {
                return;
            }


            if (
                sidebar.classList.contains(
                    'mobile-open'
                )
            ) {

                closeMobileSidebar();

            } else {

                openMobileSidebar();

            }

        };


        /* =====================================================
           LOGOUT CONFIRMATION
        ====================================================== */

        window.confirmLogout = function() {

            return window.confirm(
                'Are you sure you want to logout?'
            );

        };


        /* =====================================================
           ESC KEY
        ====================================================== */

        document.addEventListener(
            'keydown',
            function(event) {

                if (event.key === 'Escape') {

                    closeMobileSidebar();

                }

            }
        );


        /* =====================================================
           CLOSE AFTER CLICKING LINK ON MOBILE
        ====================================================== */

        document.addEventListener(
            'click',
            function(event) {

                var link =
                    event.target.closest(
                        '#adminSidebar a'
                    );


                if (
                    link &&
                    window.innerWidth <= 900
                ) {

                    closeMobileSidebar();

                }

            }
        );


        /* =====================================================
           CLOSE ON RESIZE
        ====================================================== */

        window.addEventListener(
            'resize',
            function() {

                if (window.innerWidth > 900) {

                    closeMobileSidebar();

                }

            }
        );

    })();
</script>