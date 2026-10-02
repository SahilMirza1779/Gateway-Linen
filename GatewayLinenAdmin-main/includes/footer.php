<?php
/*
|--------------------------------------------------------------------------
| GatewayLinen Common Footer
|--------------------------------------------------------------------------
| File:
|     includes/footer.php
|
| Purpose:
|     Common footer for all authenticated admin pages.
|
| Features:
|     - Professional light/dark theme support
|     - Responsive footer
|     - Automatic current year
|     - Mobile sidebar support
|     - Sidebar overlay support
|     - ESC key support
|     - Resize support
|     - No duplicate theme button
|--------------------------------------------------------------------------
*/
?>

<!-- =========================================================
     GATEWAYLINEN FOOTER
========================================================= -->

<footer class="gateway-footer" id="gatewayFooter">

    <div class="gateway-footer-content">

        <span class="footer-copy">
            © <?= date("Y") ?> GatewayLinen
        </span>

        <span class="footer-separator">•</span>

        <span class="footer-rights">
            All Rights Reserved
        </span>

    </div>

</footer>


<!-- =========================================================
     FOOTER CSS
     LIGHT + DARK THEME
========================================================= -->

<style>

    /* =========================================================
       FOOTER THEME VARIABLES
    ========================================================== */

    :root {

        --footer-bg: #ffffff;
        --footer-border: #e2e8f0;

        --footer-text: #64748b;
        --footer-text-strong: #475569;

        --footer-accent: #10b981;
        --footer-separator: #cbd5e1;

        --footer-shadow:
            0 -4px 18px rgba(15, 23, 42, 0.04);

    }


    /* =========================================================
       DARK MODE
    ========================================================== */

    html[data-theme="dark"],
    body.dark-mode,
    body[data-theme="dark"] {

        --footer-bg: #0d1620;
        --footer-border: #1e2d3d;

        --footer-text: #71859a;
        --footer-text-strong: #a8b8c8;

        --footer-accent: #10b981;
        --footer-separator: #35495c;

        --footer-shadow:
            0 -4px 18px rgba(0, 0, 0, 0.25);

    }


    /* =========================================================
       MAIN FOOTER
    ========================================================== */

    .gateway-footer {

        width: 100%;

        min-height: 58px;

        margin-top: 30px;

        padding: 16px 24px;

        background: var(--footer-bg);

        border-top: 1px solid var(--footer-border);

        box-shadow: var(--footer-shadow);

        box-sizing: border-box;

        display: flex;

        align-items: center;

        justify-content: center;

        text-align: center;

        color: var(--footer-text);

        font-family:
            "Segoe UI",
            Arial,
            Helvetica,
            sans-serif;

        font-size: 13px;

        line-height: 1.5;

        transition:
            background-color 0.25s ease,
            border-color 0.25s ease,
            color 0.25s ease,
            box-shadow 0.25s ease;

    }


    /* =========================================================
       FOOTER CONTENT
    ========================================================== */

    .gateway-footer-content {

        width: 100%;

        max-width: 1400px;

        display: flex;

        align-items: center;

        justify-content: center;

        gap: 10px;

        text-align: center;

        flex-wrap: wrap;

    }


    /* =========================================================
       COPYRIGHT
    ========================================================== */

    .footer-copy {

        color: var(--footer-text-strong);

        font-size: 13px;

        font-weight: 600;

        white-space: nowrap;

        transition:
            color 0.25s ease;

    }


    /* Green small indicator */

    .footer-copy::before {

        content: "";

        display: inline-block;

        width: 6px;

        height: 6px;

        margin-right: 8px;

        vertical-align: middle;

        border-radius: 50%;

        background: var(--footer-accent);

        box-shadow:
            0 0 0 3px rgba(16, 185, 129, 0.10);

    }


    /* =========================================================
       SEPARATOR
    ========================================================== */

    .footer-separator {

        color: var(--footer-separator);

        font-size: 12px;

        font-weight: 700;

        line-height: 1;

        transition:
            color 0.25s ease;

    }


    /* =========================================================
       RIGHTS
    ========================================================== */

    .footer-rights {

        color: var(--footer-text);

        font-size: 12px;

        font-weight: 500;

        white-space: nowrap;

        transition:
            color 0.25s ease;

    }


    /* =========================================================
       HOVER
    ========================================================== */

    .footer-copy:hover {

        color: var(--footer-accent);

    }


    .footer-rights:hover {

        color: var(--footer-text-strong);

    }


    /* =========================================================
       TABLET
    ========================================================== */

    @media (max-width: 1100px) {

        .gateway-footer {

            min-height: 56px;

            margin-top: 25px;

            padding: 15px 20px;

        }

    }


    /* =========================================================
       MOBILE / TABLET
    ========================================================== */

    @media (max-width: 900px) {

        .gateway-footer {

            min-height: 55px;

            margin-top: 22px;

            padding: 15px 16px;

        }

        .gateway-footer-content {

            gap: 8px;

        }

        .footer-copy {

            font-size: 12px;

        }

        .footer-rights {

            font-size: 11px;

        }

    }


    /* =========================================================
       SMALL MOBILE
    ========================================================== */

    @media (max-width: 480px) {

        .gateway-footer {

            min-height: 52px;

            margin-top: 18px;

            padding: 14px 10px;

            font-size: 12px;

        }

        .gateway-footer-content {

            gap: 6px;

        }

        .footer-copy {

            font-size: 11px;

        }

        .footer-rights {

            font-size: 10px;

        }

        .footer-copy::before {

            width: 5px;

            height: 5px;

            margin-right: 6px;

        }

    }


    /* =========================================================
       VERY SMALL MOBILE
    ========================================================== */

    @media (max-width: 360px) {

        .gateway-footer {

            padding-left: 8px;

            padding-right: 8px;

        }

        .gateway-footer-content {

            gap: 5px;

        }

        .footer-copy {

            font-size: 10px;

        }

        .footer-rights {

            font-size: 9px;

        }

        .footer-separator {

            font-size: 10px;

        }

    }


    /* =========================================================
       PRINT
    ========================================================== */

    @media print {

        .gateway-footer {

            background: #ffffff !important;

            border-top: 1px solid #dddddd !important;

            box-shadow: none !important;

            color: #666666 !important;

        }

        .footer-copy,

        .footer-rights,

        .footer-separator {

            color: #666666 !important;

        }

        .footer-copy::before {

            background: #666666 !important;

            box-shadow: none !important;

        }

    }

</style>


<!-- =========================================================
     MOBILE SIDEBAR JAVASCRIPT
========================================================= -->

<script>

(function () {

    "use strict";


    /* =========================================================
       GET SIDEBAR
    ========================================================== */

    function getSidebar() {

        return document.getElementById("adminSidebar");

    }


    /* =========================================================
       GET OVERLAY
    ========================================================== */

    function getOverlay() {

        return document.getElementById("sidebarOverlay");

    }


    /* =========================================================
       OPEN MOBILE MENU
    ========================================================== */

    function openMobileMenu() {

        const sidebar = getSidebar();

        const overlay = getOverlay();


        if (!sidebar) {

            return;

        }


        if (window.innerWidth <= 900) {

            sidebar.classList.add("mobile-open");


            if (overlay) {

                overlay.classList.add("mobile-open");

            }


            document.body.classList.add("sidebar-is-open");

            document.body.style.overflow = "hidden";

        }

    }


    /* =========================================================
       CLOSE MOBILE MENU
    ========================================================== */

    function closeMobileMenu() {

        const sidebar = getSidebar();

        const overlay = getOverlay();


        if (sidebar) {

            sidebar.classList.remove("mobile-open");

        }


        if (overlay) {

            overlay.classList.remove("mobile-open");

        }


        document.body.classList.remove("sidebar-is-open");

        document.body.style.overflow = "";

    }


    /* =========================================================
       GLOBAL FUNCTIONS
    ========================================================== */

    window.openMobileMenu = openMobileMenu;

    window.closeMobileMenu = closeMobileMenu;

    window.openMobileSidebar = openMobileMenu;

    window.closeMobileSidebar = closeMobileMenu;


    /* =========================================================
       OVERLAY CLICK
    ========================================================== */

    document.addEventListener("click", function (event) {

        const overlay = getOverlay();


        if (!overlay) {

            return;

        }


        if (event.target === overlay) {

            closeMobileMenu();

        }

    });


    /* =========================================================
       SIDEBAR LINK CLICK
    ========================================================== */

    document.addEventListener("click", function (event) {

        const sidebar = getSidebar();


        if (!sidebar) {

            return;

        }


        const link = event.target.closest(
            "#adminSidebar a"
        );


        if (!link) {

            return;

        }


        if (window.innerWidth <= 900) {

            setTimeout(function () {

                closeMobileMenu();

            }, 100);

        }

    });


    /* =========================================================
       ESC KEY
    ========================================================== */

    document.addEventListener("keydown", function (event) {

        if (event.key === "Escape") {

            closeMobileMenu();

        }

    });


    /* =========================================================
       WINDOW RESIZE
    ========================================================== */

    window.addEventListener("resize", function () {

        if (window.innerWidth > 900) {

            closeMobileMenu();

        }

    });


    /* =========================================================
       THEME CHANGE SUPPORT
       Footer automatically follows:
       data-theme="dark"
       body.dark-mode
    ========================================================== */

    function syncFooterTheme() {

        const footer = document.getElementById(
            "gatewayFooter"
        );


        if (!footer) {

            return;

        }


        const htmlTheme =
            document.documentElement.getAttribute(
                "data-theme"
            );

        const bodyTheme =
            document.body.getAttribute(
                "data-theme"
            );

        const darkMode =
            document.body.classList.contains(
                "dark-mode"
            );


        const isDark =
            htmlTheme === "dark" ||
            bodyTheme === "dark" ||
            darkMode;


        footer.setAttribute(
            "data-footer-theme",
            isDark ? "dark" : "light"
        );

    }


    /* =========================================================
       OBSERVE THEME CHANGES
    ========================================================== */

    function watchThemeChanges() {

        const observer = new MutationObserver(
            function () {

                syncFooterTheme();

            }
        );


        observer.observe(
            document.documentElement,
            {
                attributes: true,
                attributeFilter: [
                    "class",
                    "data-theme"
                ]
            }
        );


        observer.observe(
            document.body,
            {
                attributes: true,
                attributeFilter: [
                    "class",
                    "data-theme"
                ]
            }
        );

    }


    /* =========================================================
       PAGE READY
    ========================================================== */

    function initializeFooter() {

        if (window.innerWidth > 900) {

            closeMobileMenu();

        }

        syncFooterTheme();

        watchThemeChanges();

    }


    /* =========================================================
       DOM READY
    ========================================================== */

    if (
        document.readyState === "loading"
    ) {

        document.addEventListener(
            "DOMContentLoaded",
            initializeFooter
        );

    } else {

        initializeFooter();

    }


})();

</script>


<!-- =========================================================
     COMMON THEME FILE
========================================================= -->

<?php

/*
|--------------------------------------------------------------------------
| IMPORTANT
|--------------------------------------------------------------------------
| theme.php should contain ONLY the theme functionality.
| Do not create another moon/sun button in footer.php.
|--------------------------------------------------------------------------
*/

$themeFile = __DIR__ . '/theme.php';

if (file_exists($themeFile)) {

    require_once $themeFile;

}

?>


<!-- =========================================================
     CLOSE BODY / HTML
========================================================= -->

</body>

</html>