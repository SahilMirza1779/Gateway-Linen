<?php
/*
|--------------------------------------------------------------------------
| GatewayLinen Common Header
|--------------------------------------------------------------------------
| Common header used by all authenticated admin pages.
|
| Required variables:
| $pageTitle
| $adminName
|
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| BASE URL
|--------------------------------------------------------------------------
| The base URL is detected from the current PHP URL so the project
| continues to work whether the parent folder is GatewayLinen,
| Gateway-Linen, or another folder name.
|--------------------------------------------------------------------------
*/
if (!defined('GATEWAY_BASE')) {

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $adminFolder = '/GatewayLinenAdmin-main';
    $adminPos = stripos($scriptName, $adminFolder);

    if ($adminPos !== false) {
        define(
            'GATEWAY_BASE',
            substr(
                $scriptName,
                0,
                $adminPos + strlen($adminFolder)
            )
        );
    } else {
        /*
         * Fallback only when SCRIPT_NAME does not contain the
         * GatewayLinenAdmin-main folder.
         */
        define(
            'GATEWAY_BASE',
            '/GatewayLinen/GatewayLinenAdmin-main'
        );
    }
}

/*
|--------------------------------------------------------------------------
| URL HELPER
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
| CURRENT PAGE
|--------------------------------------------------------------------------
*/
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');

/*
|--------------------------------------------------------------------------
| ADMIN NAME
|--------------------------------------------------------------------------
*/
$adminName = $_SESSION['admin_name'] ?? 'Administrator';
$adminName = trim((string) $adminName);

if ($adminName === '') {
    $adminName = 'Administrator';
}

/*
|--------------------------------------------------------------------------
| FIRST LETTER
|--------------------------------------------------------------------------
*/
$firstLetter = strtoupper(substr($adminName, 0, 1));

if ($firstLetter === '') {
    $firstLetter = 'A';
}

/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/
$pageTitle = $pageTitle ?? 'GatewayLinen | Administration';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <meta
        name="theme-color"
        content="#0a1119">

    <meta
        name="mobile-web-app-capable"
        content="yes">

    <title>
        <?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>
    </title>

    <style>
        /* =========================================================
           GLOBAL RESET
        ========================================================== */
        *,
        *::before,
        *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* =========================================================
           ROOT - DARK THEME TOKENS
        ========================================================== */
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
            --green-dark: #059669;
            --green-soft: rgba(16, 185, 129, 0.12);
            --gold: #f0b429;
            --gold-soft: rgba(240, 180, 41, 0.12);
            --navy: #071b35;
            --blue: #155f8f;

            --sidebar-width: 255px;
            --topbar-height: 68px;
        }

        /* =========================================================
           HTML
        ========================================================== */
        html {
            scroll-behavior: smooth;
            background: var(--bg-page);
        }

        /* =========================================================
           BODY
        ========================================================== */
        body {
            min-height: 100vh;
            background: var(--bg-page);
            color: var(--text-body);
            font-family:
                "Segoe UI",
                Arial,
                Helvetica,
                sans-serif;
            font-size: 14px;
            overflow-x: hidden;
        }

        /* =========================================================
           LINKS
        ========================================================== */
        a {
            text-decoration: none;
            color: inherit;
        }

        /* =========================================================
           FORM ELEMENTS
        ========================================================== */
        button,
        input,
        select,
        textarea {
            font-family: inherit;
        }

        /* =========================================================
           MAIN AREA
        ========================================================== */
        .main {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            background: var(--bg-page);
            transition: margin-left 0.25s ease;
        }

        /* =========================================================
           TOPBAR
        ========================================================== */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 900;
            width: calc(100% - var(--sidebar-width));
            height: var(--topbar-height);
            margin-left: var(--sidebar-width);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            background: rgba(13, 22, 32, 0.92);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
        }

        /* =========================================================
           TOPBAR LEFT
        ========================================================== */
        .topbar-left {
            min-width: 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* =========================================================
           MOBILE MENU BUTTON
        ========================================================== */
        .mobile-menu-btn {
            display: none;
            width: 42px;
            height: 42px;
            flex-shrink: 0;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--bg-input);
            cursor: pointer;
            align-items: center;
            justify-content: center;
            color: var(--text-body);
            font-size: 21px;
            line-height: 1;
            transition: all 0.2s ease;
        }

        .mobile-menu-btn:hover {
            background: var(--bg-hover);
            border-color: var(--green);
            color: var(--green);
        }

        .mobile-menu-btn:active {
            transform: scale(0.96);
        }

        /* =========================================================
           PAGE TITLE
        ========================================================== */
        .page-title {
            min-width: 0;
            color: var(--text-hi);
            font-size: 17px;
            line-height: 1.2;
            font-weight: 700;
            letter-spacing: -0.3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* =========================================================
           PAGE SUBTITLE
        ========================================================== */
        .page-subtitle {
            margin-top: 3px;
            color: var(--text-mute);
            font-size: 10.5px;
            line-height: 1.3;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* =========================================================
           PROFILE
        ========================================================== */
        .profile {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            gap: 11px;
            max-width: 280px;
            padding: 5px 12px 5px 5px;
            border: 1px solid var(--border);
            border-radius: 30px;
            background: var(--bg-input);
            transition:
                border-color 0.18s ease,
                background 0.18s ease;
        }

        .profile:hover {
            border-color: #26384a;
            background: var(--bg-hover);
        }

        /* =========================================================
           AVATAR
        ========================================================== */
        .avatar {
            width: 36px;
            height: 36px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: linear-gradient(135deg, #2a2310, #4a3c16);
            border: 1px solid rgba(240, 180, 41, 0.3);
            color: #f0b429;
            font-size: 13px;
            font-weight: 800;
            box-shadow: 0 0 0 2px rgba(240, 180, 41, 0.08);
        }

        /* =========================================================
           PROFILE INFORMATION
        ========================================================== */
        .profile-info {
            min-width: 0;
        }

        .profile-name {
            max-width: 190px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: var(--text-hi);
            font-size: 12px;
            font-weight: 700;
        }

        .profile-role {
            margin-top: 2px;
            color: var(--text-mute);
            font-size: 10px;
            font-weight: 600;
        }

        /* =========================================================
           CONTENT AREA
        ========================================================== */
        .content {
            width: 100%;
            max-width: 100%;
            padding: 30px 32px 40px;
            background: var(--bg-page);
        }

        /* =========================================================
           WELCOME
        ========================================================== */
        .welcome {
            position: relative;
            overflow: hidden;
            padding: 30px 32px;
            border-radius: 18px;
            background:
                linear-gradient(
                    135deg,
                    #0d1620 0%,
                    #142430 55%,
                    #0d3a35 100%
                );
            border: 1px solid var(--border);
            color: var(--text-hi);
            box-shadow: 0 14px 35px rgba(0, 0, 0, 0.3);
        }

        .welcome::after {
            content: "";
            position: absolute;
            width: 240px;
            height: 240px;
            right: -80px;
            top: -120px;
            border-radius: 50%;
            background: rgba(16, 185, 129, 0.08);
        }

        .welcome h1 {
            position: relative;
            z-index: 2;
            font-family:
                Georgia,
                "Times New Roman",
                serif;
            font-size: 27px;
            line-height: 1.3;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text-hi);
        }

        .welcome p {
            position: relative;
            z-index: 2;
            max-width: 700px;
            color: var(--text-body);
            font-size: 13px;
            line-height: 1.6;
        }

        /* =========================================================
           STATS
        ========================================================== */
        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-top: 22px;
        }

        .stat-card {
            min-width: 0;
            padding: 21px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 15px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.25);
            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                border-color 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            border-color: #26384a;
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.4);
        }

        .stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 16px;
        }

        .stat-title {
            color: var(--text-mute);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            background: var(--green-soft);
            color: var(--green);
        }

        .stat-icon svg {
            width: 19px;
            height: 19px;
        }

        .stat-number {
            color: var(--text-hi);
            font-size: 28px;
            line-height: 1;
            font-weight: 800;
        }

        /* =========================================================
           SECTION TITLE
        ========================================================== */
        .section-title {
            margin: 31px 0 14px;
            color: var(--text-hi);
            font-size: 17px;
            font-weight: 800;
        }

        /* =========================================================
           QUICK ACTIONS
        ========================================================== */
        .actions {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 17px;
        }

        .action {
            display: block;
            min-width: 0;
            padding: 20px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 14px;
            color: var(--text-hi);
            transition:
                transform 0.2s ease,
                border-color 0.2s ease,
                box-shadow 0.2s ease,
                background 0.2s ease;
        }

        .action:hover {
            transform: translateY(-2px);
            border-color: var(--green);
            background: var(--bg-hover);
            box-shadow: 0 9px 25px rgba(0, 0, 0, 0.4);
        }

        .action-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            border-radius: 10px;
            background: var(--green-soft);
            color: var(--green);
        }

        .action-icon svg {
            width: 18px;
            height: 18px;
        }

        .action strong {
            display: block;
            margin-bottom: 5px;
            color: var(--text-hi);
            font-size: 13px;
            font-weight: 800;
        }

        .action span {
            color: var(--text-mute);
            font-size: 11px;
            line-height: 1.5;
        }

        /* =========================================================
           SCROLLBAR
        ========================================================== */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        ::-webkit-scrollbar-track {
            background: #0a1119;
        }

        ::-webkit-scrollbar-thumb {
            background: #26384a;
            border-radius: 6px;
            border: 2px solid #0a1119;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #35495c;
        }

        /* =========================================================
           LARGE TABLET
        ========================================================== */
        @media (max-width: 1200px) {
            .content {
                padding-left: 24px;
                padding-right: 24px;
            }

            .topbar {
                padding-left: 24px;
                padding-right: 24px;
            }

            .profile {
                max-width: 230px;
            }

            .profile-name {
                max-width: 150px;
            }
        }

        /* =========================================================
           TABLET
        ========================================================== */
        @media (max-width: 1100px) {
            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .actions {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        /* =========================================================
           MOBILE / TABLET
        ========================================================== */
        @media (max-width: 900px) {
            .main {
                margin-left: 0;
            }

            .topbar {
                width: 100%;
                margin-left: 0;
                height: 68px;
                padding: 0 18px;
            }

            .mobile-menu-btn {
                display: flex;
            }

            .page-title {
                font-size: 16px;
            }

            .page-subtitle {
                font-size: 10px;
            }

            .profile {
                max-width: 210px;
                gap: 8px;
            }

            .profile-name {
                max-width: 125px;
            }

            .content {
                padding: 20px 17px 30px;
            }

            .welcome {
                padding: 24px 21px;
                border-radius: 15px;
            }

            .welcome h1 {
                font-size: 22px;
            }

            .welcome p {
                font-size: 12px;
            }

            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
                margin-top: 15px;
            }

            .stat-card {
                padding: 16px;
            }

            .stat-number {
                font-size: 24px;
            }

            .actions {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .action {
                padding: 16px;
            }
        }

        /* =========================================================
           SMALL MOBILE
        ========================================================== */
        @media (max-width: 600px) {
            .topbar {
                padding: 0 13px;
            }

            .topbar-left {
                gap: 9px;
                min-width: 0;
            }

            .mobile-menu-btn {
                width: 40px;
                height: 40px;
                border-radius: 9px;
                font-size: 20px;
            }

            .page-title {
                max-width: 170px;
                font-size: 15px;
            }

            .page-subtitle {
                max-width: 170px;
                font-size: 9px;
            }

            .profile {
                gap: 7px;
                padding: 4px 8px 4px 4px;
            }

            .avatar {
                width: 32px;
                height: 32px;
                font-size: 12px;
            }

            .profile-name {
                max-width: 90px;
                font-size: 11px;
            }

            .profile-role {
                display: none;
            }

            .content {
                padding: 15px 12px 25px;
            }

            .welcome {
                padding: 20px 17px;
            }

            .welcome h1 {
                font-size: 20px;
            }

            .welcome p {
                font-size: 11px;
            }

            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 9px;
            }

            .stat-card {
                padding: 14px;
            }

            .stat-top {
                margin-bottom: 13px;
            }

            .stat-title {
                font-size: 10px;
            }

            .stat-icon {
                width: 34px;
                height: 34px;
            }

            .stat-number {
                font-size: 21px;
            }

            .section-title {
                margin-top: 25px;
                font-size: 15px;
            }

            .actions {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .action {
                display: flex;
                align-items: center;
                gap: 13px;
                padding: 14px;
            }

            .action-icon {
                flex-shrink: 0;
                margin-bottom: 0;
            }
        }

        /* =========================================================
           EXTRA SMALL MOBILE
        ========================================================== */
        @media (max-width: 400px) {
            .topbar {
                padding: 0 10px;
            }

            .mobile-menu-btn {
                width: 37px;
                height: 37px;
            }

            .page-title {
                max-width: 130px;
                font-size: 14px;
            }

            .page-subtitle {
                max-width: 130px;
                font-size: 8px;
            }

            .profile-name {
                max-width: 70px;
                font-size: 10px;
            }

            .avatar {
                width: 30px;
                height: 30px;
                font-size: 11px;
            }
        }

        /* =========================================================
           PRINT
        ========================================================== */
        @media print {
            .topbar,
            .mobile-menu-btn,
            .profile {
                display: none !important;
            }

            .main {
                margin-left: 0 !important;
            }

            .content {
                padding: 0 !important;
            }

            body {
                background: #ffffff !important;
                color: #000000 !important;
            }
        }
    </style>
</head>

<body>

    <!-- =========================================================
         COMMON TOP HEADER - DARK
    ========================================================== -->
    <header class="topbar">

        <!-- LEFT SIDE -->
        <div class="topbar-left">

            <!-- MOBILE MENU BUTTON -->
            <button
                type="button"
                class="mobile-menu-btn"
                onclick="openMobileMenu()"
                aria-label="Open navigation menu"
                title="Open menu">
                ☰
            </button>

            <!-- PAGE INFORMATION -->
            <div>
                <div class="page-title">
                    <?= htmlspecialchars(
                        $pageTitle,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>

                <div class="page-subtitle">
                    GatewayLinen Administration
                </div>
            </div>
        </div>

        <!-- RIGHT SIDE - ADMIN PROFILE -->
        <div class="profile">

            <!-- AVATAR -->
            <div class="avatar">
                <?= htmlspecialchars(
                    $firstLetter,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>

            <!-- PROFILE INFO -->
            <div class="profile-info">

                <div class="profile-name">
                    <?= htmlspecialchars(
                        $adminName,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>

                <div class="profile-role">
                    Administrator
                </div>

            </div>
        </div>

    </header>
