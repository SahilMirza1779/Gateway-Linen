<?php
/*
|--------------------------------------------------------------------------
| GATEWAYLINEN - GLOBAL DARK / LIGHT MODE
| This file is loaded from common footer.php
|--------------------------------------------------------------------------
*/
?>

<style>
/* =========================================================
   GLOBAL THEME VARIABLES
========================================================= */

:root {
    --theme-bg: #0a1119;
    --theme-sidebar: #071019;
    --theme-card: #111b26;
    --theme-header: #0d1620;
    --theme-input: #0d1620;
    --theme-border: #1e2d3d;
    --theme-text: #f0f4f8;
    --theme-text-soft: #a8b8c8;
    --theme-muted: #6f8497;
    --theme-hover: #16222e;
    --theme-green: #10b981;
}

/* =========================================================
   LIGHT MODE
========================================================= */

html[data-theme="light"] {
    --theme-bg: #f4f7fb;
    --theme-sidebar: #ffffff;
    --theme-card: #ffffff;
    --theme-header: #ffffff;
    --theme-input: #ffffff;
    --theme-border: #e2e8f0;
    --theme-text: #172033;
    --theme-text-soft: #475569;
    --theme-muted: #64748b;
    --theme-hover: #f1f5f9;
    --theme-green: #059669;
}

/* =========================================================
   GLOBAL PAGE
========================================================= */

html[data-theme="light"],
html[data-theme="light"] body {
    background: var(--theme-bg) !important;
    color: var(--theme-text) !important;
}

/* Common wrappers */
html[data-theme="light"] .main,
html[data-theme="light"] .content,
html[data-theme="light"] .main-content,
html[data-theme="light"] .page-content,
html[data-theme="light"] .wrapper {
    background: var(--theme-bg) !important;
    color: var(--theme-text) !important;
}

/* =========================================================
   SIDEBAR
========================================================= */

html[data-theme="light"] .sidebar,
html[data-theme="light"] aside,
html[data-theme="light"] #sidebar {
    background: var(--theme-sidebar) !important;
    border-color: var(--theme-border) !important;
    color: var(--theme-text) !important;
}

/* =========================================================
   HEADER / NAVBAR
========================================================= */

html[data-theme="light"] header,
html[data-theme="light"] .navbar,
html[data-theme="light"] .topbar,
html[data-theme="light"] .main-header {
    background: var(--theme-header) !important;
    border-color: var(--theme-border) !important;
    color: var(--theme-text) !important;
}

/* =========================================================
   TEXT
========================================================= */

html[data-theme="light"] h1,
html[data-theme="light"] h2,
html[data-theme="light"] h3,
html[data-theme="light"] h4,
html[data-theme="light"] h5,
html[data-theme="light"] h6,
html[data-theme="light"] strong,
html[data-theme="light"] label {
    color: var(--theme-text) !important;
}

html[data-theme="light"] p,
html[data-theme="light"] small,
html[data-theme="light"] .text-muted {
    color: var(--theme-text-soft) !important;
}

/* =========================================================
   CARDS / PANELS / BOXES
========================================================= */

html[data-theme="light"] .card,
html[data-theme="light"] .panel,
html[data-theme="light"] .box,
html[data-theme="light"] .form-card,
html[data-theme="light"] .dashboard-card,
html[data-theme="light"] .stat-card {
    background: var(--theme-card) !important;
    color: var(--theme-text) !important;
    border-color: var(--theme-border) !important;
}

/* =========================================================
   FORMS
========================================================= */

html[data-theme="light"] input,
html[data-theme="light"] select,
html[data-theme="light"] textarea,
html[data-theme="light"] .form-control,
html[data-theme="light"] .form-select {
    background: var(--theme-input) !important;
    color: var(--theme-text) !important;
    border-color: var(--theme-border) !important;
}

html[data-theme="light"] input::placeholder,
html[data-theme="light"] textarea::placeholder,
html[data-theme="light"] .form-control::placeholder {
    color: var(--theme-muted) !important;
}

/* =========================================================
   TABLES
========================================================= */

html[data-theme="light"] table,
html[data-theme="light"] .table {
    color: var(--theme-text) !important;
    border-color: var(--theme-border) !important;
}

html[data-theme="light"] table th,
html[data-theme="light"] .table th {
    background: var(--theme-card) !important;
    color: var(--theme-text-soft) !important;
    border-color: var(--theme-border) !important;
}

html[data-theme="light"] table td,
html[data-theme="light"] .table td {
    background: var(--theme-card) !important;
    color: var(--theme-text) !important;
    border-color: var(--theme-border) !important;
}

/* =========================================================
   DROPDOWN / MODAL
========================================================= */

html[data-theme="light"] .dropdown-menu,
html[data-theme="light"] .modal-content {
    background: var(--theme-card) !important;
    color: var(--theme-text) !important;
    border-color: var(--theme-border) !important;
}

html[data-theme="light"] .dropdown-item {
    color: var(--theme-text) !important;
}

html[data-theme="light"] .dropdown-item:hover {
    background: var(--theme-hover) !important;
}

/* =========================================================
   CATEGORY PAGE - YOUR EXISTING VARIABLES
========================================================= */

html[data-theme="light"] {
    --bg-page: #f4f7fb;
    --bg-card: #ffffff;
    --bg-card-alt: #f8fafc;
    --bg-header: #ffffff;
    --bg-hover: #f1f5f9;
    --bg-input: #ffffff;

    --border: #e2e8f0;
    --border-soft: #edf2f7;

    --text-hi: #172033;
    --text-body: #475569;
    --text-mute: #64748b;

    --green: #059669;
    --green-dark: #047857;
    --green-soft: rgba(5, 150, 105, 0.10);
}

/* =========================================================
   THEME BUTTON
========================================================= */

#globalThemeToggle {
    position: fixed;
    top: 18px;
    right: 24px;
    z-index: 99999;

    width: 42px;
    height: 42px;

    display: flex;
    align-items: center;
    justify-content: center;

    border: 1px solid var(--theme-border);
    border-radius: 50%;

    background: var(--theme-card);
    color: var(--theme-text);

    cursor: pointer;
    font-size: 18px;

    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);

    transition: all 0.25s ease;
}

#globalThemeToggle:hover {
    border-color: var(--theme-green);
    color: var(--theme-green);
    transform: rotate(15deg);
}

/* Light mode button */
html[data-theme="light"] #globalThemeToggle {
    background: #ffffff;
    color: #172033;
    border-color: #dbe3ec;
}

/* Mobile */
@media (max-width: 768px) {
    #globalThemeToggle {
        top: 14px;
        right: 14px;
        width: 38px;
        height: 38px;
        font-size: 16px;
    }
}
</style>


<script>
(function () {

    const html = document.documentElement;

    /*
    |----------------------------------------------------------
    | LOAD SAVED THEME
    |----------------------------------------------------------
    */

    const savedTheme =
        localStorage.getItem('gatewaylinen-theme') || 'dark';

    html.setAttribute('data-theme', savedTheme);


    /*
    |----------------------------------------------------------
    | CREATE BUTTON AUTOMATICALLY
    |----------------------------------------------------------
    | Isliye header.php mein button add karne ki zarurat nahi.
    */

    function createThemeButton() {

        if (document.getElementById('globalThemeToggle')) {
            return;
        }

        const button = document.createElement('button');

        button.id = 'globalThemeToggle';
        button.type = 'button';

        document.body.appendChild(button);

        updateButton();

        button.addEventListener('click', function () {

            const currentTheme =
                html.getAttribute('data-theme');

            const newTheme =
                currentTheme === 'light' ? 'dark' : 'light';

            html.setAttribute('data-theme', newTheme);

            localStorage.setItem(
                'gatewaylinen-theme',
                newTheme
            );

            updateButton();

        });

    }


    /*
    |----------------------------------------------------------
    | UPDATE ICON
    |----------------------------------------------------------
    */

    function updateButton() {

        const button =
            document.getElementById('globalThemeToggle');

        if (!button) return;

        const currentTheme =
            html.getAttribute('data-theme');

        if (currentTheme === 'light') {

            button.innerHTML = '☀️';
            button.title = 'Switch to Dark Mode';
            button.setAttribute(
                'aria-label',
                'Switch to Dark Mode'
            );

        } else {

            button.innerHTML = '🌙';
            button.title = 'Switch to Light Mode';
            button.setAttribute(
                'aria-label',
                'Switch to Light Mode'
            );

        }

    }


    /*
    |----------------------------------------------------------
    | START
    |----------------------------------------------------------
    */

    if (document.readyState === 'loading') {

        document.addEventListener(
            'DOMContentLoaded',
            createThemeButton
        );

    } else {

        createThemeButton();

    }

})();
</script>