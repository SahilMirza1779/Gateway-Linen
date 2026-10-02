<?php
/*
|--------------------------------------------------------------------------
| GatewayLinen Admin Sidebar + Topbar
|--------------------------------------------------------------------------
| File:
| includes/sidebar.php
|--------------------------------------------------------------------------
*/

if (!defined('GATEWAY_BASE')) {
    define(
        'GATEWAY_BASE',
        '/Gateway-Linen/GatewayLinenAdmin-main'
    );
}

$activeMenu = $activeMenu ?? '';

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

if (!function_exists('gateway_menu_active')) {

    function gateway_menu_active(string $menu): string
    {
        global $activeMenu;

        return ($activeMenu === $menu)
            ? 'active'
            : '';
    }
}
?>

<style>

/* =========================================================
   GATEWAYLINEN SIDEBAR VARIABLES
========================================================= */

:root {

    --gateway-sidebar-width: 255px;
    --gateway-topbar-height: 68px;

    --gateway-bg: #f4f7fb;
    --gateway-sidebar-bg: #ffffff;
    --gateway-topbar-bg: #ffffff;
    --gateway-card: #ffffff;

    --gateway-border: #e2e8f0;

    --gateway-text: #172033;
    --gateway-text-soft: #475569;
    --gateway-muted: #64748b;

    --gateway-green: #059669;
    --gateway-green-light: #10b981;

    --gateway-hover: #f1f5f9;

    --gateway-shadow:
        0 4px 20px rgba(15, 23, 42, .08);
}


/* =========================================================
   DARK MODE
========================================================= */

html[data-theme="dark"] {

    --gateway-bg: #0a1119;
    --gateway-sidebar-bg: #0d1620;
    --gateway-topbar-bg: #0f1924;
    --gateway-card: #111b26;

    --gateway-border: #263544;

    --gateway-text: #f0f4f8;
    --gateway-text-soft: #a8b8c8;
    --gateway-muted: #71869a;

    --gateway-green: #10b981;
    --gateway-green-light: #10b981;

    --gateway-hover: #16222e;

    --gateway-shadow:
        0 5px 25px rgba(0, 0, 0, .35);
}


/* =========================================================
   BODY
========================================================= */

html,
body {

    margin: 0;
    padding: 0;

    background: var(--gateway-bg);
    color: var(--gateway-text);

    transition:
        background .2s ease,
        color .2s ease;
}


/* =========================================================
   TOPBAR
========================================================= */

.gateway-topbar {

    position: fixed;

    top: 0;
    left: var(--gateway-sidebar-width);
    right: 0;

    height: var(--gateway-topbar-height);

    z-index: 900;

    display: flex;

    align-items: center;
    justify-content: space-between;

    padding: 0 22px;

    box-sizing: border-box;

    background: var(--gateway-topbar-bg);

    border-bottom:
        1px solid var(--gateway-border);

    box-shadow:
        var(--gateway-shadow);

    transition:
        background .2s ease,
        border-color .2s ease;
}


/* =========================================================
   TOPBAR LEFT
========================================================= */

.gateway-topbar-left {

    display: flex;

    align-items: center;

    gap: 12px;

    min-width: 0;
}


/* =========================================================
   MOBILE MENU
========================================================= */

.gateway-mobile-menu {

    width: 40px;
    height: 40px;

    display: none;

    align-items: center;
    justify-content: center;

    border:
        1px solid var(--gateway-border);

    border-radius: 9px;

    background:
        var(--gateway-card);

    color:
        var(--gateway-text);

    cursor: pointer;

    font-size: 18px;

    transition: .2s ease;
}

.gateway-mobile-menu:hover {

    border-color:
        var(--gateway-green);

    color:
        var(--gateway-green);
}


/* =========================================================
   PAGE TITLE
========================================================= */

.gateway-page-title {

    display: flex;

    flex-direction: column;

    gap: 3px;

    min-width: 0;
}

.gateway-page-title strong {

    color:
        var(--gateway-text);

    font-size: 17px;

    font-weight: 750;

    white-space: nowrap;
}

.gateway-page-title span {

    color:
        var(--gateway-muted);

    font-size: 10px;

    white-space: nowrap;
}


/* =========================================================
   TOPBAR RIGHT
========================================================= */

.gateway-topbar-right {

    display: flex;

    align-items: center;

    gap: 10px;
}


/* =========================================================
   SINGLE THEME BUTTON
========================================================= */

.gateway-theme-button {

    width: 42px;
    height: 42px;

    display: flex;

    align-items: center;
    justify-content: center;

    border:
        1px solid var(--gateway-border);

    border-radius: 50%;

    background:
        var(--gateway-card);

    color:
        var(--gateway-text);

    cursor: pointer;

    font-size: 18px;

    box-shadow:
        0 3px 12px rgba(0,0,0,.08);

    transition:
        all .2s ease;
}

.gateway-theme-button:hover {

    border-color:
        var(--gateway-green);

    color:
        var(--gateway-green);

    transform:
        rotate(12deg);
}


/* =========================================================
   ADMIN USER
========================================================= */

.gateway-admin-user {

    display: flex;

    align-items: center;

    gap: 9px;

    padding:
        5px 11px 5px 6px;

    background:
        var(--gateway-card);

    border:
        1px solid var(--gateway-border);

    border-radius: 11px;
}


/* =========================================================
   ADMIN AVATAR
========================================================= */

.gateway-admin-avatar {

    width: 34px;
    height: 34px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background:
        linear-gradient(
            135deg,
            #059669,
            #10b981
        );

    color: #ffffff;

    font-size: 12px;

    font-weight: 800;
}


/* =========================================================
   ADMIN INFO
========================================================= */

.gateway-admin-info {

    display: flex;

    flex-direction: column;

    gap: 2px;

    line-height: 1.2;
}

.gateway-admin-info strong {

    color:
        var(--gateway-text);

    font-size: 10px;

    font-weight: 750;
}

.gateway-admin-info span {

    color:
        var(--gateway-muted);

    font-size: 9px;
}


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {

    position: fixed;

    top: 0;
    left: 0;
    bottom: 0;

    width:
        var(--gateway-sidebar-width);

    z-index: 1000;

    display: flex;

    flex-direction: column;

    box-sizing: border-box;

    padding:
        14px 11px;

    overflow-y: auto;

    background:
        var(--gateway-sidebar-bg);

    border-right:
        1px solid var(--gateway-border);

    color:
        var(--gateway-text);

    box-shadow:
        7px 0 25px rgba(0,0,0,.06);

    transition:
        background .2s ease,
        border-color .2s ease,
        left .25s ease;
}


/* =========================================================
   SIDEBAR SCROLLBAR
========================================================= */

.sidebar {

    scrollbar-width: thin;

    scrollbar-color:
        rgba(100,116,139,.35)
        transparent;
}

.sidebar::-webkit-scrollbar {

    width: 5px;
}

.sidebar::-webkit-scrollbar-track {

    background: transparent;
}

.sidebar::-webkit-scrollbar-thumb {

    background:
        rgba(100,116,139,.35);

    border-radius: 10px;
}


/* =========================================================
   BRAND
========================================================= */

.sidebar-brand {

    min-height: 57px;

    display: flex;

    align-items: center;

    gap: 11px;

    padding:
        0 9px 13px;

    margin-bottom: 9px;

    border-bottom:
        1px solid var(--gateway-border);
}


/* =========================================================
   LOGO
========================================================= */

.sidebar-logo {

    width: 40px;
    height: 40px;

    flex-shrink: 0;

    display: flex;

    align-items: center;
    justify-content: center;

    overflow: hidden;

    border-radius: 50%;

    background: #ffffff;

    border:
        2px solid var(--gateway-green);

    box-shadow:
        0 0 0 3px
        rgba(16,185,129,.10);
}

.sidebar-logo img {

    width: 100%;
    height: 100%;

    object-fit: contain;

    border-radius: 50%;
}

.sidebar-logo-fallback {

    display: none;

    width: 100%;
    height: 100%;

    align-items: center;
    justify-content: center;

    background: #ffffff;

    color: var(--gateway-green);

    font-family:
        Georgia,
        "Times New Roman",
        serif;

    font-size: 11px;

    font-weight: 800;
}


/* =========================================================
   BRAND NAME
========================================================= */

.sidebar-brand-name {

    color:
        var(--gateway-text);

    font-family:
        Georgia,
        "Times New Roman",
        serif;

    font-size: 18px;

    font-weight: 700;

    white-space: nowrap;
}

.sidebar-brand-name span {

    color:
        var(--gateway-green);
}


/* =========================================================
   SECTION
========================================================= */

.sidebar-section {

    margin-bottom: 7px;
}

.sidebar-section-title {

    padding:
        10px 10px 6px;

    color:
        var(--gateway-muted);

    font-size: 9px;

    font-weight: 800;

    letter-spacing: 1.1px;

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

    min-height: 39px;

    gap: 10px;

    padding:
        8px 10px;

    margin-bottom: 2px;

    box-sizing: border-box;

    border-radius: 8px;

    color:
        var(--gateway-text-soft);

    text-decoration: none;

    font-size: 11px;

    font-weight: 600;

    transition:
        all .18s ease;
}


/* =========================================================
   HOVER
========================================================= */

.sidebar-menu-item:hover {

    color:
        var(--gateway-text);

    background:
        rgba(16,185,129,.09);

    transform:
        translateX(2px);
}


/* =========================================================
   ACTIVE
========================================================= */

.sidebar-menu-item.active {

    color: #ffffff;

    font-weight: 700;

    background:
        linear-gradient(
            90deg,
            #059669,
            #10b981
        );

    box-shadow:
        0 5px 15px
        rgba(16,185,129,.24);
}

.sidebar-menu-item.active:hover {

    color: #ffffff;

    transform: none;

    background:
        linear-gradient(
            90deg,
            #059669,
            #10b981
        );
}


/* =========================================================
   ACTIVE LEFT LINE
========================================================= */

.sidebar-menu-item.active::before {

    content: "";

    position: absolute;

    left: -11px;

    top: 50%;

    transform:
        translateY(-50%);

    width: 3px;

    height: 22px;

    border-radius:
        0 3px 3px 0;

    background:
        var(--gateway-green);

    box-shadow:
        0 0 12px
        rgba(16,185,129,.55);
}


/* =========================================================
   ICON
========================================================= */

.sidebar-icon {

    width: 20px;
    height: 20px;

    flex-shrink: 0;

    display: flex;

    align-items: center;
    justify-content: center;

    color:
        #71869a;
}

.sidebar-icon svg {

    width: 16px;
    height: 16px;

    fill: none;

    stroke: currentColor;

    stroke-width: 1.7;

    stroke-linecap: round;

    stroke-linejoin: round;
}

.sidebar-menu-item:hover
.sidebar-icon {

    color:
        var(--gateway-green);
}

.sidebar-menu-item.active
.sidebar-icon {

    color: #ffffff;
}


/* =========================================================
   TEXT
========================================================= */

.sidebar-text {

    flex: 1;

    overflow: hidden;

    white-space: nowrap;

    text-overflow: ellipsis;
}


/* =========================================================
   BOTTOM
========================================================= */

.sidebar-bottom {

    margin-top: auto;

    padding-top: 10px;

    border-top:
        1px solid var(--gateway-border);
}


/* =========================================================
   LOGOUT
========================================================= */

.sidebar-logout:hover {

    color: #ef4444;

    background:
        rgba(239,68,68,.08);
}

.sidebar-logout:hover
.sidebar-icon {

    color: #ef4444;
}


/* =========================================================
   MAIN CONTENT OFFSET
========================================================= */

.admin-main,
.main-content,
.page-content {

    margin-left:
        var(--gateway-sidebar-width);

    padding-top:
        var(--gateway-topbar-height);

    min-height: 100vh;

    box-sizing: border-box;

    background:
        var(--gateway-bg);

    color:
        var(--gateway-text);

    transition:
        background .2s ease,
        color .2s ease;
}


/* =========================================================
   MOBILE OVERLAY
========================================================= */

.sidebar-overlay {

    display: none;

    position: fixed;

    inset: 0;

    z-index: 999;

    background:
        rgba(0,0,0,.60);

    backdrop-filter:
        blur(3px);
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 900px) {

    .sidebar {

        left: -270px;

        width: 260px;
    }

    .sidebar.mobile-open {

        left: 0;
    }

    .sidebar-overlay.mobile-open {

        display: block;
    }

    .gateway-topbar {

        left: 0;

        padding:
            0 15px;
    }

    .gateway-mobile-menu {

        display: flex;
    }

    .admin-main,
    .main-content,
    .page-content {

        margin-left: 0;
    }

    .gateway-admin-info {

        display: none;
    }
}


/* =========================================================
   SMALL MOBILE
========================================================= */

@media (max-width: 480px) {

    .gateway-topbar {

        height: 62px;

        padding:
            0 10px;
    }

    .gateway-page-title strong {

        font-size: 15px;
    }

    .gateway-page-title span {

        display: none;
    }

    .gateway-theme-button {

        width: 38px;
        height: 38px;
    }

    .gateway-admin-user {

        padding: 3px;

        border: none;

        background: transparent;
    }

    .admin-main,
    .main-content,
    .page-content {

        padding-top: 62px;
    }
}


/* =========================================================
   PRINT
========================================================= */

@media print {

    .sidebar,
    .gateway-topbar,
    .sidebar-overlay {

        display: none !important;
    }

    .admin-main,
    .main-content,
    .page-content {

        margin-left: 0 !important;

        padding-top: 0 !important;
    }
}

</style>


<!-- =========================================================
     MOBILE OVERLAY
========================================================= -->

<div
    id="sidebarOverlay"
    class="sidebar-overlay"
    onclick="closeMobileSidebar()">
</div>


<!-- =========================================================
     TOPBAR
========================================================= -->

<header class="gateway-topbar">

    <div class="gateway-topbar-left">

        <button
            type="button"
            class="gateway-mobile-menu"
            onclick="toggleMobileSidebar()"
            aria-label="Open Menu">

            ☰

        </button>


        <div class="gateway-page-title">

            <strong>
                GatewayLinen
            </strong>

            <span>
                Administration Panel
            </span>

        </div>

    </div>


    <div class="gateway-topbar-right">

        <!-- ONLY ONE THEME BUTTON -->

        <button
            type="button"
            id="gatewayThemeButton"
            class="gateway-theme-button"
            onclick="toggleGatewayTheme()"
            aria-label="Change theme"
            title="Change theme">

            ☀️

        </button>


        <!-- ADMIN USER -->

        <div class="gateway-admin-user">

            <div class="gateway-admin-avatar">
                GL
            </div>

            <div class="gateway-admin-info">

                <strong>
                    GatewayLinen Administrator
                </strong>

                <span>
                    Administrator
                </span>

            </div>

        </div>

    </div>

</header>


<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside
    id="adminSidebar"
    class="sidebar">


    <!-- =====================================================
         BRAND
    ====================================================== -->

    <div class="sidebar-brand">

        <div class="sidebar-logo">

            <img
                src="<?= htmlspecialchars(
                    gateway_admin_url(
                        'uploads/logo/logo.png'
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                alt="GatewayLinen Logo"

                onerror="
                    this.style.display='none';
                    this.nextElementSibling.style.display='flex';
                "
            >

            <div class="sidebar-logo-fallback">
                GL
            </div>

        </div>


        <div class="sidebar-brand-name">

            Gateway<span>Linen</span>

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

        <a
            href="<?= htmlspecialchars(
                gateway_admin_url(
                    'dashboard.php'
                ),
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            class="sidebar-menu-item
            <?= gateway_menu_active('dashboard') ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <rect
                        x="3"
                        y="3"
                        width="7"
                        height="7"
                        rx="1">
                    </rect>

                    <rect
                        x="14"
                        y="3"
                        width="7"
                        height="7"
                        rx="1">
                    </rect>

                    <rect
                        x="3"
                        y="14"
                        width="7"
                        height="7"
                        rx="1">
                    </rect>

                    <rect
                        x="14"
                        y="14"
                        width="7"
                        height="7"
                        rx="1">
                    </rect>

                </svg>

            </div>

            <span class="sidebar-text">
                Dashboard
            </span>

        </a>


        <!-- CATEGORIES -->

        <a
            href="<?= htmlspecialchars(
                gateway_admin_url(
                    'categories/index.php'
                ),
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            class="sidebar-menu-item
            <?= gateway_menu_active('categories') ?>">

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

        <a
            href="<?= htmlspecialchars(
                gateway_admin_url(
                    'products/index.php'
                ),
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            class="sidebar-menu-item
            <?= gateway_menu_active('products') ?>">

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


        <!-- PRODUCT VARIANTS -->

        <a
            href="<?= htmlspecialchars(
                gateway_admin_url(
                    'variants/index.php'
                ),
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            class="sidebar-menu-item
            <?= gateway_menu_active('variants') ?>">

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


        <!-- INVENTORY -->

        <a
            href="<?= htmlspecialchars(
                gateway_admin_url(
                    'inventory/index.php'
                ),
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            class="sidebar-menu-item
            <?= gateway_menu_active('inventory') ?>">

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

        <a
            href="<?= htmlspecialchars(
                gateway_admin_url(
                    'orders/index.php'
                ),
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            class="sidebar-menu-item
            <?= gateway_menu_active('orders') ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <circle
                        cx="9"
                        cy="20"
                        r="1">
                    </circle>

                    <circle
                        cx="18"
                        cy="20"
                        r="1">
                    </circle>

                    <path
                        d="M3 4h2l2.2 11h10.9l2-8H6">
                    </path>

                </svg>

            </div>

            <span class="sidebar-text">
                Orders
            </span>

        </a>


        <!-- CUSTOMERS -->

        <a
            href="<?= htmlspecialchars(
                gateway_admin_url(
                    'customers/index.php'
                ),
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            class="sidebar-menu-item
            <?= gateway_menu_active('customers') ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <circle
                        cx="9"
                        cy="8"
                        r="3">
                    </circle>

                    <path
                        d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6">
                    </path>

                    <circle
                        cx="18"
                        cy="9"
                        r="2">
                    </circle>

                    <path
                        d="M16 15c2.8.5 4.5 2.3 4.5 5">
                    </path>

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


        <?php

        $marketingMenus = [

            [
                'key'  => 'wholesale',
                'url'  => 'wholesale/index.php',
                'name' => 'Wholesale'
            ],

            [
                'key'  => 'quotes',
                'url'  => 'quotes/index.php',
                'name' => 'Quotes'
            ],

            [
                'key'  => 'bulk-inquiries',
                'url'  => 'bulk-inquiries/index.php',
                'name' => 'Bulk Inquiries'
            ],

            [
                'key'  => 'wishlist',
                'url'  => 'wishlist/index.php',
                'name' => 'Wishlist'
            ],

            [
                'key'  => 'coupons',
                'url'  => 'coupons/index.php',
                'name' => 'Coupons'
            ],

            [
                'key'  => 'reviews',
                'url'  => 'reviews/index.php',
                'name' => 'Reviews'
            ],

            [
                'key'  => 'newsletter',
                'url'  => 'newsletter/index.php',
                'name' => 'Newsletter'
            ],

            [
                'key'  => 'back-in-stock',
                'url'  => 'back-in-stock/index.php',
                'name' => 'Back in Stock'
            ]

        ];

        foreach ($marketingMenus as $menu):

        ?>

            <a
                href="<?= htmlspecialchars(
                    gateway_admin_url(
                        $menu['url']
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                class="sidebar-menu-item
                <?= gateway_menu_active(
                    $menu['key']
                ) ?>">

                <div class="sidebar-icon">

                    <svg viewBox="0 0 24 24">

                        <circle
                            cx="12"
                            cy="12"
                            r="7">
                        </circle>

                        <path d="M8 12h8"></path>

                    </svg>

                </div>

                <span class="sidebar-text">

                    <?= htmlspecialchars(
                        $menu['name'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </span>

            </a>

        <?php endforeach; ?>

    </div>


    <!-- =====================================================
         OPERATIONS
    ====================================================== -->

    <div class="sidebar-section">

        <div class="sidebar-section-title">
            Operations
        </div>


        <!-- WAREHOUSES -->

        <a
            href="<?= htmlspecialchars(
                gateway_admin_url(
                    'warehouses/index.php'
                ),
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            class="sidebar-menu-item
            <?= gateway_menu_active('warehouses') ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <path d="M3 10l9-6 9 6"></path>
                    <path d="M5 9v11h14V9"></path>
                    <path d="M9 20v-6h6v6"></path>

                </svg>

            </div>

            <span class="sidebar-text">
                Warehouses
            </span>

        </a>


        <!-- STOCK MOVEMENTS -->

        <a
            href="<?= htmlspecialchars(
                gateway_admin_url(
                    'inventory/stock-movements.php'
                ),
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            class="sidebar-menu-item
            <?= gateway_menu_active(
                'stock-movements'
            ) ?>">

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


        <!-- TAXES SHIPPING -->

        <a
            href="<?= htmlspecialchars(
                gateway_admin_url(
                    'taxes-shipping/index.php'
                ),
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            class="sidebar-menu-item
            <?= gateway_menu_active(
                'taxes-shipping'
            ) ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <path d="M3 6h11v11H3z"></path>
                    <path d="M14 10h4l3 3v4h-7z"></path>

                    <circle
                        cx="7"
                        cy="19"
                        r="2">
                    </circle>

                    <circle
                        cx="18"
                        cy="19"
                        r="2">
                    </circle>

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

        <a
            href="<?= htmlspecialchars(
                gateway_admin_url(
                    'users/index.php'
                ),
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            class="sidebar-menu-item
            <?= gateway_menu_active('users') ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <circle
                        cx="9"
                        cy="8"
                        r="3">
                    </circle>

                    <path
                        d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6">
                    </path>

                    <path d="M16 8h5"></path>
                    <path d="M18.5 5.5v5"></path>

                </svg>

            </div>

            <span class="sidebar-text">
                Users
            </span>

        </a>


        <!-- ROLES -->

        <a
            href="<?= htmlspecialchars(
                gateway_admin_url(
                    'roles-permissions/index.php'
                ),
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            class="sidebar-menu-item
            <?= gateway_menu_active(
                'roles-permissions'
            ) ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <circle
                        cx="12"
                        cy="8"
                        r="3">
                    </circle>

                    <path
                        d="M5 21c0-4 3-7 7-7s7 3 7 7">
                    </path>

                </svg>

            </div>

            <span class="sidebar-text">
                Roles &amp; Permissions
            </span>

        </a>


        <!-- SETTINGS -->

        <a
            href="<?= htmlspecialchars(
                gateway_admin_url(
                    'settings/general.php'
                ),
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            class="sidebar-menu-item
            <?= gateway_menu_active(
                'settings'
            ) ?>">

            <div class="sidebar-icon">

                <svg viewBox="0 0 24 24">

                    <circle
                        cx="12"
                        cy="12"
                        r="3">
                    </circle>

                    <path
                        d="M19 12a7 7 0 01-.2 1.7l2 1.5-2 3.4-2.3-1a8 8 0 01-3 1.7L13 21H9l-.5-1.7a8 8 0 01-3-1.7l-2.3 1-2-3.4 2-1.5A7 7 0 013 12c0-.6.1-1.2.2-1.7l-2-1.5 2-3.4 2.3 1a8 8 0 013-1.7L9 3h4l.5 1.7a8 8 0 013 1.7l2.3-1 2 3.4-2 1.5c.1.5.2 1.1.2 1.7z">
                    </path>

                </svg>

            </div>

            <span class="sidebar-text">
                System Settings
            </span>

        </a>


        <!-- AUDIT LOGS -->

        <a
            href="<?= htmlspecialchars(
                gateway_admin_url(
                    'audit-logs/index.php'
                ),
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            class="sidebar-menu-item
            <?= gateway_menu_active(
                'audit-logs'
            ) ?>">

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

        <a
            href="<?= htmlspecialchars(
                gateway_admin_url(
                    'logout.php'
                ),
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
            class="sidebar-menu-item sidebar-logout"
            onclick="return confirmGatewayLogout();">

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

/* =========================================================
   MOBILE SIDEBAR
========================================================= */

function openMobileSidebar() {

    const sidebar =
        document.getElementById(
            'adminSidebar'
        );

    const overlay =
        document.getElementById(
            'sidebarOverlay'
        );

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
}


function closeMobileSidebar() {

    const sidebar =
        document.getElementById(
            'adminSidebar'
        );

    const overlay =
        document.getElementById(
            'sidebarOverlay'
        );

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
}


function toggleMobileSidebar() {

    const sidebar =
        document.getElementById(
            'adminSidebar'
        );

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
}


/* =========================================================
   LOGOUT
========================================================= */

function confirmGatewayLogout() {

    return confirm(
        'Are you sure you want to logout?'
    );
}


/* =========================================================
   ESC KEY
========================================================= */

document.addEventListener(
    'keydown',
    function (event) {

        if (event.key === 'Escape') {

            closeMobileSidebar();
        }
    }
);


/* =========================================================
   MOBILE LINK CLOSE
========================================================= */

document.addEventListener(
    'click',
    function (event) {

        const link =
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


/* =========================================================
   THEME BUTTON
========================================================= */

function updateGatewayThemeButton() {

    const button =
        document.getElementById(
            'gatewayThemeButton'
        );

    if (!button) {
        return;
    }

    const theme =
        document.documentElement.getAttribute(
            'data-theme'
        ) || 'light';

    if (theme === 'dark') {

        button.innerHTML = '☀️';

        button.title =
            'Switch to Light Mode';

        button.setAttribute(
            'aria-label',
            'Switch to Light Mode'
        );

    } else {

        button.innerHTML = '🌙';

        button.title =
            'Switch to Dark Mode';

        button.setAttribute(
            'aria-label',
            'Switch to Dark Mode'
        );
    }
}


/* =========================================================
   CHANGE THEME
========================================================= */

function toggleGatewayTheme() {

    const html =
        document.documentElement;

    const currentTheme =
        html.getAttribute(
            'data-theme'
        ) || 'light';

    const newTheme =
        currentTheme === 'light'
            ? 'dark'
            : 'light';


    /* Change immediately */

    html.setAttribute(
        'data-theme',
        newTheme
    );


    /* Save permanently */

    localStorage.setItem(
        'gatewaylinen-theme',
        newTheme
    );


    /* Update icon */

    updateGatewayThemeButton();
}


/* =========================================================
   INITIAL THEME
========================================================= */

(function () {

    const html =
        document.documentElement;

    let savedTheme =
        localStorage.getItem(
            'gatewaylinen-theme'
        );


    if (
        savedTheme !== 'light' &&
        savedTheme !== 'dark'
    ) {

        savedTheme = 'light';

        localStorage.setItem(
            'gatewaylinen-theme',
            savedTheme
        );
    }


    html.setAttribute(
        'data-theme',
        savedTheme
    );


    updateGatewayThemeButton();

})();


/* =========================================================
   WINDOW RESIZE
========================================================= */

window.addEventListener(
    'resize',
    function () {

        if (window.innerWidth > 900) {

            closeMobileSidebar();
        }
    }
);

</script>