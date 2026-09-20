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
|     - Centered footer (DARK THEME)
|     - Responsive footer
|     - Current year automatically
|     - Mobile sidebar support
|     - Overlay support
|     - ESC key support
|     - Resize support
|--------------------------------------------------------------------------
*/
?>


<!-- =========================================================
     GATEWAYLINEN FOOTER - DARK
========================================================= -->

<footer class="gateway-footer">

    <div class="gateway-footer-content">

        <span class="footer-copy">
            © <?= date("Y") ?> GatewayLinen
        </span>

        <span class="footer-dot">•</span>

        <span class="footer-rights">
            All Rights Reserved
        </span>

    </div>

</footer>


<!-- =========================================================
     FOOTER CSS - DARK THEME
========================================================= -->

<style>

    /* =====================================================
       MAIN FOOTER - DARK
    ===================================================== */

    .gateway-footer {

        width: 100%;

        min-height: 58px;

        margin-top: 30px;

        padding: 16px 20px;

        background: #0d1620;

        border-top: 1px solid #1e2d3d;

        box-sizing: border-box;

        display: flex;

        align-items: center;

        justify-content: center;

        text-align: center;

        font-family:
            "Segoe UI",
            Arial,
            Helvetica,
            sans-serif;

        font-size: 13px;

        color: #5f7488;

    }


    /* =====================================================
       FOOTER CONTENT
    ===================================================== */

    .gateway-footer-content {

        display: flex;

        align-items: center;

        justify-content: center;

        gap: 10px;

        width: 100%;

        text-align: center;

        line-height: 1.5;

    }


    /* =====================================================
       COPYRIGHT
    ===================================================== */

    .footer-copy {

        color: #a8b8c8;

        font-weight: 500;

        white-space: nowrap;

    }

    .footer-copy::before {
        content: "◆ ";
        color: #10b981;
        font-size: 8px;
        vertical-align: middle;
        margin-right: 2px;
    }


    /* =====================================================
       DOT
    ===================================================== */

    .footer-dot {

        color: #35495c;

        font-weight: 600;

        line-height: 1;

    }


    /* =====================================================
       ALL RIGHTS RESERVED
    ===================================================== */

    .footer-rights {

        color: #5f7488;

        font-weight: 500;

        white-space: nowrap;

    }


    /* =====================================================
       TABLET
    ===================================================== */

    @media (max-width: 1100px) {

        .gateway-footer {

            min-height: 56px;

            padding: 15px 18px;

            margin-top: 25px;

        }

    }


    /* =====================================================
       MOBILE
    ===================================================== */

    @media (max-width: 900px) {

        .gateway-footer {

            min-height: 55px;

            margin-top: 20px;

            padding: 15px 12px;

            justify-content: center;

            text-align: center;

        }


        .gateway-footer-content {

            justify-content: center;

            text-align: center;

            flex-wrap: wrap;

            gap: 7px;

        }

    }


    /* =====================================================
       SMALL MOBILE
    ===================================================== */

    @media (max-width: 480px) {

        .gateway-footer {

            min-height: 52px;

            padding: 14px 10px;

            font-size: 12px;

        }


        .gateway-footer-content {

            gap: 6px;

        }

    }


    /* =====================================================
       VERY SMALL MOBILE
    ===================================================== */

    @media (max-width: 360px) {

        .gateway-footer {

            font-size: 11px;

            padding-left: 8px;

            padding-right: 8px;

        }

    }


    /* =====================================================
       PRINT
    ===================================================== */

    @media print {

        .gateway-footer {

            background: #ffffff;

            border-top: 1px solid #ddd;

            color: #666;

        }

        .footer-copy,
        .footer-rights {
            color: #666;
        }

        .footer-copy::before {
            color: #666;
        }

    }

</style>


<!-- =========================================================
     MOBILE SIDEBAR JAVASCRIPT
========================================================= -->

<script>

(function () {

    "use strict";


    /* =====================================================
       GET SIDEBAR
    ===================================================== */

    function getSidebar() {

        return document.getElementById("adminSidebar");

    }


    /* =====================================================
       GET OVERLAY
    ===================================================== */

    function getOverlay() {

        return document.getElementById("sidebarOverlay");

    }


    /* =====================================================
       OPEN MOBILE SIDEBAR
    ===================================================== */

    function openMobileMenu() {

        const sidebar = getSidebar();

        const overlay = getOverlay();


        /* Sidebar does not exist */

        if (!sidebar) {

            return;

        }


        /* Only open on mobile/tablet */

        if (window.innerWidth <= 900) {

            sidebar.classList.add("mobile-open");


            if (overlay) {

                overlay.classList.add("mobile-open");

            }


            document.body.style.overflow = "hidden";

        }

    }


    /* =====================================================
       CLOSE MOBILE SIDEBAR
    ===================================================== */

    function closeMobileMenu() {

        const sidebar = getSidebar();

        const overlay = getOverlay();


        if (sidebar) {

            sidebar.classList.remove("mobile-open");

        }


        if (overlay) {

            overlay.classList.remove("mobile-open");

        }


        document.body.style.overflow = "";

    }


    /* =====================================================
       GLOBAL FUNCTIONS
    ===================================================== */

    window.openMobileMenu = openMobileMenu;

    window.closeMobileMenu = closeMobileMenu;

    window.openMobileSidebar = openMobileMenu;

    window.closeMobileSidebar = closeMobileMenu;


    /* =====================================================
       OVERLAY CLICK
    ===================================================== */

    document.addEventListener(
        "click",
        function (event) {

            const overlay = getOverlay();


            if (
                overlay &&
                event.target === overlay
            ) {

                closeMobileMenu();

            }

        }
    );


    /* =====================================================
       SIDEBAR MENU ITEM CLICK
    ===================================================== */

    document.addEventListener(
        "click",
        function (event) {

            const link = event.target.closest(
                "#adminSidebar .sidebar-menu-item"
            );


            if (!link) {

                return;

            }


            if (window.innerWidth <= 900) {

                setTimeout(
                    function () {

                        closeMobileMenu();

                    },
                    100
                );

            }

        }
    );


    /* =====================================================
       SIDEBAR LINK BACKUP
    ===================================================== */

    document.addEventListener(
        "click",
        function (event) {

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

                setTimeout(
                    function () {

                        closeMobileMenu();

                    },
                    100
                );

            }

        }
    );


    /* =====================================================
       ESCAPE KEY
    ===================================================== */

    document.addEventListener(
        "keydown",
        function (event) {

            if (event.key === "Escape") {

                closeMobileMenu();

            }

        }
    );


    /* =====================================================
       WINDOW RESIZE
    ===================================================== */

    window.addEventListener(
        "resize",
        function () {

            if (window.innerWidth > 900) {

                closeMobileMenu();

            }

        }
    );


    /* =====================================================
       PAGE LOAD
    ===================================================== */

    document.addEventListener(
        "DOMContentLoaded",
        function () {

            if (window.innerWidth > 900) {

                closeMobileMenu();

            }

        }
    );


})();

</script>


<!-- =========================================================
     CLOSE BODY / HTML
========================================================= -->

<?php require_once __DIR__ . '/theme.php'; ?>

</body>

</html>