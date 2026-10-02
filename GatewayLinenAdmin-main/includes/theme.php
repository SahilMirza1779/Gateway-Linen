<?php
/*
|--------------------------------------------------------------------------
| GatewayLinen Global Theme
|--------------------------------------------------------------------------
| File:
|     includes/theme.php
|
| Default:
|     LIGHT
|
| Dark Mode:
|     Enabled only when user selects dark mode.
|
| Storage:
|     localStorage -> gatewaylinen-theme
|--------------------------------------------------------------------------
*/
?>

<script>
(function () {

    /*
    |--------------------------------------------------------------------------
    | THEME INITIALIZER
    |--------------------------------------------------------------------------
    | Default is LIGHT.
    | Dark will only be applied if the user has selected it.
    |--------------------------------------------------------------------------
    */

    const savedTheme = localStorage.getItem('gatewaylinen-theme');

    const theme =
        savedTheme === 'dark'
            ? 'dark'
            : 'light';

    document.documentElement.setAttribute(
        'data-theme',
        theme
    );

})();
</script>


<style>

/* ==========================================================================
   LIGHT THEME - DEFAULT
============================================================================= */

:root {

    --theme-bg: #f4f7fb;

    --theme-sidebar: #ffffff;

    --theme-card: #ffffff;

    --theme-header: #ffffff;

    --theme-input: #ffffff;

    --theme-border: #e2e8f0;

    --theme-border-soft: #edf1f5;

    --theme-text: #172033;

    --theme-text-soft: #475569;

    --theme-muted: #64748b;

    --theme-hover: #f1f5f9;

    --theme-green: #059669;

    --theme-green-dark: #047857;

    --theme-green-soft: rgba(5, 150, 105, 0.10);

    --theme-shadow:
        0 8px 25px rgba(15, 23, 42, 0.06);

    --theme-shadow-hover:
        0 12px 30px rgba(15, 23, 42, 0.10);
}


/* ==========================================================================
   DARK THEME
============================================================================= */

html[data-theme="dark"] {

    --theme-bg: #0a1119;

    --theme-sidebar: #071019;

    --theme-card: #111b26;

    --theme-header: #0d1620;

    --theme-input: #0d1620;

    --theme-border: #1e2d3d;

    --theme-border-soft: #182636;

    --theme-text: #f0f4f8;

    --theme-text-soft: #a8b8c8;

    --theme-muted: #6f8497;

    --theme-hover: #16222e;

    --theme-green: #10b981;

    --theme-green-dark: #059669;

    --theme-green-soft: rgba(16, 185, 129, 0.12);

    --theme-shadow:
        0 8px 25px rgba(0, 0, 0, 0.25);

    --theme-shadow-hover:
        0 12px 30px rgba(0, 0, 0, 0.40);
}


/* ==========================================================================
   HTML
============================================================================= */

html {

    background: var(--theme-bg);

    color: var(--theme-text);

}


/* ==========================================================================
   BODY
============================================================================= */

html,
body {

    min-height: 100%;

    background: var(--theme-bg) !important;

    color: var(--theme-text) !important;

    transition:
        background-color .20s ease,
        color .20s ease;

}


/* ==========================================================================
   BODY
============================================================================= */

body {

    margin: 0;

    font-family:
        "Segoe UI",
        Arial,
        Helvetica,
        sans-serif;

}


/* ==========================================================================
   MAIN PAGE AREAS
============================================================================= */

.main,
.content,
.main-content,
.page-content,
.wrapper,
.admin-main {

    background: var(--theme-bg) !important;

    color: var(--theme-text);

}


/* ==========================================================================
   TOP HEADER
============================================================================= */

.topbar,
.gateway-topbar,
.main-header,
.navbar {

    background: var(--theme-header) !important;

    color: var(--theme-text) !important;

    border-color: var(--theme-border) !important;

}


/* ==========================================================================
   SIDEBAR
============================================================================= */

.sidebar,
#adminSidebar,
#sidebar,
aside {

    background: var(--theme-sidebar) !important;

    color: var(--theme-text) !important;

    border-color: var(--theme-border) !important;

}


/* ==========================================================================
   HEADINGS
============================================================================= */

h1,
h2,
h3,
h4,
h5,
h6 {

    color: var(--theme-text);

}


/* ==========================================================================
   NORMAL TEXT
============================================================================= */

p,
small {

    color: var(--theme-text-soft);

}


/* ==========================================================================
   LABELS
============================================================================= */

label,
strong {

    color: var(--theme-text);

}


/* ==========================================================================
   LINKS
============================================================================= */

a {

    color: inherit;

}


/* ==========================================================================
   CARDS
============================================================================= */

.card,
.panel,
.box,
.form-card,
.dashboard-card,
.stat-card,
.action {

    background: var(--theme-card) !important;

    color: var(--theme-text) !important;

    border-color: var(--theme-border) !important;

}


/* ==========================================================================
   INPUTS
============================================================================= */

input,
select,
textarea,
.form-control,
.form-select {

    background: var(--theme-input) !important;

    color: var(--theme-text) !important;

    border-color: var(--theme-border) !important;

}


/* ==========================================================================
   INPUT PLACEHOLDER
============================================================================= */

input::placeholder,
textarea::placeholder,
.form-control::placeholder {

    color: var(--theme-muted) !important;

}


/* ==========================================================================
   TABLE
============================================================================= */

table,
.table {

    color: var(--theme-text) !important;

    border-color: var(--theme-border) !important;

}


/* ==========================================================================
   TABLE HEADER
============================================================================= */

table thead,
.table thead {

    background: var(--theme-card) !important;

}


table th,
.table th {

    background: var(--theme-card) !important;

    color: var(--theme-text-soft) !important;

    border-color: var(--theme-border) !important;

}


/* ==========================================================================
   TABLE BODY
============================================================================= */

table td,
.table td {

    background: var(--theme-card) !important;

    color: var(--theme-text) !important;

    border-color: var(--theme-border) !important;

}


/* ==========================================================================
   TABLE ROW HOVER
============================================================================= */

table tbody tr:hover td,
.table tbody tr:hover td {

    background: var(--theme-hover) !important;

}


/* ==========================================================================
   DROPDOWN
============================================================================= */

.dropdown-menu {

    background: var(--theme-card) !important;

    color: var(--theme-text) !important;

    border-color: var(--theme-border) !important;

}


.dropdown-item {

    color: var(--theme-text) !important;

}


.dropdown-item:hover {

    background: var(--theme-hover) !important;

    color: var(--theme-text) !important;

}


/* ==========================================================================
   MODAL
============================================================================= */

.modal-content {

    background: var(--theme-card) !important;

    color: var(--theme-text) !important;

    border-color: var(--theme-border) !important;

}


/* ==========================================================================
   BORDER
============================================================================= */

.border,
.border-top,
.border-bottom,
.border-start,
.border-end {

    border-color: var(--theme-border) !important;

}


/* ==========================================================================
   BG WHITE
============================================================================= */

.bg-white {

    background: var(--theme-card) !important;

}


/* ==========================================================================
   BG LIGHT
============================================================================= */

.bg-light {

    background: var(--theme-card) !important;

    color: var(--theme-text) !important;

}


/* ==========================================================================
   TEXT DARK
============================================================================= */

.text-dark {

    color: var(--theme-text) !important;

}


/* ==========================================================================
   TEXT SECONDARY
============================================================================= */

.text-secondary {

    color: var(--theme-text-soft) !important;

}


/* ==========================================================================
   BUTTON OUTLINE
============================================================================= */

.btn-outline-secondary {

    color: var(--theme-text-soft);

    border-color: var(--theme-border);

}


.btn-outline-secondary:hover {

    background: var(--theme-hover);

    color: var(--theme-text);

}


/* ==========================================================================
   GREEN ELEMENTS
============================================================================= */

.stat-icon,
.action-icon {

    background: var(--theme-green-soft);

    color: var(--theme-green);

}


/* ==========================================================================
   FOOTER
============================================================================= */

.gateway-footer {

    background: var(--theme-header) !important;

    color: var(--theme-muted) !important;

    border-color: var(--theme-border) !important;

}


/* ==========================================================================
   SCROLLBAR
============================================================================= */

* {

    scrollbar-width: thin;

    scrollbar-color:
        rgba(100, 116, 139, .45)
        transparent;

}


*::-webkit-scrollbar {

    width: 7px;

    height: 7px;

}


*::-webkit-scrollbar-track {

    background: transparent;

}


*::-webkit-scrollbar-thumb {

    background:
        rgba(100, 116, 139, .45);

    border-radius: 10px;

}


/* ==========================================================================
   PRINT
============================================================================= */

@media print {

    html,
    body {

        background: #ffffff !important;

        color: #000000 !important;

    }

}

</style>


<script>

/* ==========================================================================
   GATEWAYLINEN THEME MANAGER
============================================================================= */

(function () {

    "use strict";


    const STORAGE_KEY =
        "gatewaylinen-theme";


    const html =
        document.documentElement;


    /*
    |--------------------------------------------------------------------------
    | GET CURRENT THEME
    |--------------------------------------------------------------------------
    */

    function getTheme() {

        const saved =
            localStorage.getItem(STORAGE_KEY);

        return saved === "dark"
            ? "dark"
            : "light";

    }


    /*
    |--------------------------------------------------------------------------
    | APPLY THEME
    |--------------------------------------------------------------------------
    */

    function applyTheme(theme) {

        theme =
            theme === "dark"
                ? "dark"
                : "light";


        html.setAttribute(
            "data-theme",
            theme
        );


        localStorage.setItem(
            STORAGE_KEY,
            theme
        );


        /*
        | Update theme button if it exists
        */

        const button =
            document.getElementById(
                "gatewayThemeButton"
            );


        if (button) {

            if (theme === "dark") {

                button.innerHTML = "☀️";

                button.setAttribute(
                    "aria-label",
                    "Switch to Light Mode"
                );

                button.setAttribute(
                    "title",
                    "Switch to Light Mode"
                );

            } else {

                button.innerHTML = "🌙";

                button.setAttribute(
                    "aria-label",
                    "Switch to Dark Mode"
                );

                button.setAttribute(
                    "title",
                    "Switch to Dark Mode"
                );

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | TOGGLE THEME
    |--------------------------------------------------------------------------
    */

    window.toggleGatewayTheme =
        function () {

            const current =
                html.getAttribute(
                    "data-theme"
                ) || "light";


            const next =
                current === "dark"
                    ? "light"
                    : "dark";


            applyTheme(next);

        };


    /*
    |--------------------------------------------------------------------------
    | INITIALIZE
    |--------------------------------------------------------------------------
    */

    applyTheme(
        getTheme()
    );


    /*
    |--------------------------------------------------------------------------
    | CROSS-TAB SUPPORT
    |--------------------------------------------------------------------------
    */

    window.addEventListener(
        "storage",
        function (event) {

            if (
                event.key === STORAGE_KEY
            ) {

                applyTheme(
                    event.newValue === "dark"
                        ? "dark"
                        : "light"
                );

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | DOM READY
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        "DOMContentLoaded",
        function () {

            applyTheme(
                getTheme()
            );

        }
    );


})();

</script>