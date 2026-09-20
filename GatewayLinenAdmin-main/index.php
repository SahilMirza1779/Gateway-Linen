<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/config/database.php';

/*
|--------------------------------------------------------------------------
| GatewayLinen Login
|--------------------------------------------------------------------------
| Login table: dbo.Users
| Columns:
| UserId
| Username
| FullName
| Email
| RoleId
| PasswordHash
|--------------------------------------------------------------------------
*/

$error = "";
$username = "";

$currentDate = date("d-m-Y");

/*
|--------------------------------------------------------------------------
| LOGIN PROCESS
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        $error = "Please enter your username and password.";
    } else {

        // FIX: Query ab dbo.admins table se data fetch karegi
        $sql = "
            SELECT
                AdminID,
                FullName,
                Username,
                Email,
                PasswordHash,
                Status
            FROM dbo.admins
            WHERE Username = ?
        ";

        $stmt = sqlsrv_query(
            $conn,
            $sql,
            [$username]
        );

        if ($stmt === false) {
            error_log("GatewayLinen Admin Login Query Error: " . print_r(sqlsrv_errors(), true));
            $error = "Database error. Please try again.";
        } else {

            $admin = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

            if (!$admin) {
                $error = "Incorrect username or password.";
            } else {

                // Check if admin status is active (Status = 1)
                if ((int)($admin['Status'] ?? 0) !== 1) {
                    $error = "Admin account is disabled.";
                } else {
                    $passwordHash = trim($admin["PasswordHash"] ?? "");

                    if ($passwordHash !== "" && password_verify($password, $passwordHash)) {

                        session_regenerate_id(true);

                        // FIX: Session keys updated for dbo.admins columns
                        $_SESSION["admin_id"] = (int) $admin["AdminID"];
                        $_SESSION["admin_name"] = $admin["FullName"];
                        $_SESSION["admin_username"] = $admin["Username"];
                        $_SESSION["admin_email"] = $admin["Email"] ?? "";
                        $_SESSION["login_date"] = date("d-m-Y");
                        $_SESSION["login_time"] = date("H:i:s");

                        header("Location: dashboard.php");
                        exit;
                    } else {
                        $error = "Incorrect username or password.";
                    }
                }
            }

            sqlsrv_free_stmt($stmt);
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0, viewport-fit=cover">

    <meta
        name="theme-color"
        content="#0a1119">

    <meta
        name="description"
        content="Secure GatewayLinen administrator sign-in">

    <title>GatewayLinen | Administrator Sign-In</title>

    <style>
        /* =========================================================
           RESET
        ========================================================= */

        *,
        *::before,
        *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        /* =========================================================
           ROOT - DARK THEME
        ========================================================= */

        :root {

            /* Dark backgrounds */
            --bg-page: #0a1119;
            --bg-card: #111b26;
            --bg-card-alt: #0f1823;
            --bg-header: #0d1620;
            --bg-input: #0d1620;
            --bg-hover: #16222e;

            /* Borders */
            --border: #1e2d3d;
            --border-soft: #182636;

            /* Text */
            --text-hi: #f0f4f8;
            --text-body: #a8b8c8;
            --text-mute: #5f7488;

            /* Accents */
            --green: #10b981;
            --green-dark: #059669;
            --green-soft: rgba(16, 185, 129, 0.12);

            --gold: #f0b429;
            --gold-dark: #d99a1c;
            --gold-soft: rgba(240, 180, 41, 0.12);

            --red: #ef4444;
            --red-soft: rgba(239, 68, 68, 0.12);

            /* Shadow */
            --shadow-soft:
                0 24px 70px rgba(0, 0, 0, 0.5);

            /* Radius */
            --radius-lg: 22px;
            --radius-md: 14px;
        }


        /* =========================================================
           HTML / BODY
        ========================================================= */

        html {
            width: 100%;
            min-height: 100%;
            scroll-behavior: smooth;
            background: var(--bg-page);
        }


        body {

            width: 100%;
            min-height: 100vh;
            min-height: 100dvh;

            font-family:
                "Segoe UI",
                Inter,
                Arial,
                sans-serif;

            background: var(--bg-page);
            color: var(--text-body);

            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;

            overflow-x: hidden;
        }


        button,
        input {
            font-family: inherit;
        }


        button {
            -webkit-tap-highlight-color: transparent;
        }


        /* =========================================================
           CUSTOM SCROLLBAR - DARK
        ========================================================= */

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
           LOGIN WRAPPER
        ========================================================= */

        .login-wrapper {

            width: 100%;
            min-height: 100vh;
            min-height: 100dvh;

            display: grid;

            grid-template-columns:
                minmax(0, 1.08fr) minmax(380px, 0.92fr);

            background: var(--bg-page);

            overflow: hidden;
        }


        /* =========================================================
           LEFT BRAND PANEL - DARK
        ========================================================= */

        .brand-panel {

            position: relative;

            min-width: 0;
            min-height: 100vh;
            min-height: 100dvh;

            display: flex;
            align-items: center;
            justify-content: center;

            padding:
                clamp(2rem, 5vw, 5rem) clamp(2rem, 5vw, 6rem);

            background:
                linear-gradient(145deg,
                    #060d14 0%,
                    #0d1620 45%,
                    #0d3a35 100%);

            overflow: hidden;

            isolation: isolate;

            border-right: 1px solid var(--border);
        }


        /* =========================================================
           DECORATIVE CIRCLES - GREEN GLOW
        ========================================================= */

        .brand-panel::before {

            content: "";

            position: absolute;

            width: clamp(380px, 45vw, 700px);
            height: clamp(380px, 45vw, 700px);

            border-radius: 50%;

            border:
                1px solid rgba(16, 185, 129, 0.15);

            top: clamp(-300px, -15vw, -160px);
            right: clamp(-300px, -15vw, -160px);

            pointer-events: none;

            box-shadow:
                0 0 120px rgba(16, 185, 129, 0.08);
        }


        .brand-panel::after {

            content: "";

            position: absolute;

            width: clamp(450px, 55vw, 850px);
            height: clamp(450px, 55vw, 850px);

            border-radius: 50%;

            border:
                1px solid rgba(16, 185, 129, 0.06);

            bottom: clamp(-500px, -25vw, -250px);
            left: clamp(-450px, -25vw, -200px);

            pointer-events: none;
        }


        /* =========================================================
           BRAND CONTENT
        ========================================================= */

        .brand-inner {

            position: relative;
            z-index: 2;

            width: 100%;
            max-width: 620px;

            margin: auto;
        }


        /* =========================================================
           LOGO RING - DARK + GREEN GLOW
        ========================================================= */

        .logo-ring {

            width: clamp(86px, 9vw, 132px);
            height: clamp(86px, 9vw, 132px);

            background: #0a1119;

            border-radius: 50%;

            border:
                clamp(2px, 0.2vw, 3px) solid var(--green);

            box-shadow:
                0 0 0 4px rgba(16, 185, 129, 0.08),
                0 18px 42px rgba(0, 0, 0, 0.5);

            display: flex;
            align-items: center;
            justify-content: center;

            padding: clamp(6px, 0.7vw, 10px);

            margin-bottom:
                clamp(1.2rem, 2.5vw, 2rem);
        }


        .logo-ring img {

            width: 100%;
            height: 100%;

            display: block;

            object-fit: contain;

            border-radius: 50%;

            background: #ffffff;
        }


        /* =========================================================
           BRAND NAME
        ========================================================= */

        .brand-name {

            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size:
                clamp(2.5rem, 5vw, 5rem);

            font-weight: 700;

            line-height: 1.02;

            letter-spacing: -1.5px;

            color: var(--text-hi);

            margin: 0;
        }


        .brand-name span {
            color: var(--green);
        }


        /* =========================================================
           BRAND LINE - GREEN
        ========================================================= */

        .brand-line {

            width: clamp(55px, 6vw, 82px);
            height: clamp(3px, 0.3vw, 5px);

            background:
                linear-gradient(90deg,
                    var(--green),
                    rgba(16, 185, 129, 0.3));

            border-radius: 100px;

            margin:
                clamp(0.5rem, 1vw, 0.8rem) 0 clamp(0.9rem, 1.5vw, 1.3rem);

            box-shadow:
                0 0 16px rgba(16, 185, 129, 0.4);
        }


        /* =========================================================
           TAGLINE
        ========================================================= */

        .brand-tagline {

            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size:
                clamp(0.75rem, 1.1vw, 1rem);

            font-weight: 700;

            letter-spacing:
                clamp(1.3px, 0.2vw, 2.5px);

            text-transform: uppercase;

            color: rgba(16, 185, 129, 0.85);

            margin-bottom:
                clamp(0.8rem, 1.4vw, 1.2rem);
        }


        /* =========================================================
           BRAND DESCRIPTION
        ========================================================= */

        .brand-text {

            max-width: 520px;

            font-size:
                clamp(0.86rem, 1vw, 1rem);

            line-height: 1.75;

            color: var(--text-body);

            margin-bottom:
                clamp(1.3rem, 2vw, 2rem);
        }


        /* =========================================================
           FEATURE GRID - DARK
        ========================================================= */

        .feature-grid {

            width: 100%;

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap:
                clamp(0.55rem, 1vw, 0.85rem);
        }


        .feature-item {

            min-width: 0;

            display: flex;
            align-items: center;

            gap: 10px;

            padding:
                clamp(0.65rem, 1vw, 0.85rem) clamp(0.75rem, 1.2vw, 1rem);

            background:
                rgba(16, 185, 129, 0.06);

            border:
                1px solid var(--border);

            border-radius:
                clamp(10px, 1vw, 14px);

            color: var(--text-body);

            font-size:
                clamp(0.72rem, 0.9vw, 0.86rem);

            font-weight: 600;

            backdrop-filter: blur(6px);

            transition:
                background 0.2s ease,
                border-color 0.2s ease,
                transform 0.2s ease;
        }


        .feature-item:hover {

            background:
                rgba(16, 185, 129, 0.12);

            border-color:
                rgba(16, 185, 129, 0.35);

            color: var(--text-hi);

            transform: translateY(-2px);
        }


        .feature-icon {

            width: 24px;
            height: 24px;

            flex: 0 0 24px;

            display: flex;
            align-items: center;
            justify-content: center;

            color: var(--green);

            font-size: 0.95rem;

            text-shadow:
                0 0 10px rgba(16, 185, 129, 0.5);
        }


        /* =========================================================
           RIGHT LOGIN PANEL - DARK
        ========================================================= */

        .login-panel {

            min-width: 0;
            min-height: 100vh;
            min-height: 100dvh;

            display: flex;
            align-items: center;
            justify-content: center;

            padding:
                clamp(1.5rem, 4vw, 4.5rem) clamp(1.5rem, 4vw, 5rem);

            background: var(--bg-page);

            overflow-y: auto;
        }


        /* =========================================================
           LOGIN CARD
        ========================================================= */

        .login-card {

            width: 100%;
            max-width: 460px;

            margin: auto;
        }


        /* =========================================================
           BADGE - DARK GREEN
        ========================================================= */

        .login-badge {

            display: inline-flex;
            align-items: center;

            gap: 8px;

            background: var(--green-soft);

            border:
                1px solid rgba(16, 185, 129, 0.35);

            border-radius: 100px;

            padding:
                0.45rem 0.95rem 0.45rem 0.72rem;

            font-size: 0.7rem;

            font-weight: 800;

            letter-spacing: 0.65px;

            text-transform: uppercase;

            color: var(--green);

            margin-bottom:
                clamp(1.1rem, 2vw, 1.7rem);
        }


        .badge-dot {

            width: 8px;
            height: 8px;

            flex: 0 0 8px;

            border-radius: 50%;

            background: var(--green);

            box-shadow:
                0 0 0 4px rgba(16, 185, 129, 0.2),
                0 0 12px rgba(16, 185, 129, 0.6);

            animation: pulse-dot 2s infinite;
        }


        @keyframes pulse-dot {

            0%,
            100% {
                box-shadow:
                    0 0 0 4px rgba(16, 185, 129, 0.2),
                    0 0 12px rgba(16, 185, 129, 0.6);
            }

            50% {
                box-shadow:
                    0 0 0 6px rgba(16, 185, 129, 0.15),
                    0 0 20px rgba(16, 185, 129, 0.8);
            }
        }


        /* =========================================================
           LOGIN HEADING - DARK
        ========================================================= */

        .login-card h1 {

            font-family:
                Georgia,
                "Times New Roman",
                serif;

            font-size:
                clamp(2rem, 3.2vw, 2.8rem);

            font-weight: 700;

            line-height: 1.12;

            color: var(--text-hi);

            margin-bottom: 0.55rem;
        }


        .login-sub {

            font-size:
                clamp(0.84rem, 1vw, 0.95rem);

            line-height: 1.65;

            color: var(--text-mute);

            margin-bottom:
                clamp(1.3rem, 2.5vw, 2rem);
        }


        /* =========================================================
           ERROR MESSAGE - DARK RED
        ========================================================= */

        .error-box {

            display: flex;
            align-items: flex-start;

            gap: 10px;

            background: var(--red-soft);

            border:
                1px solid rgba(239, 68, 68, 0.3);

            border-left:
                3px solid var(--red);

            border-radius: 12px;

            padding:
                0.85rem 1rem;

            margin-bottom: 1.4rem;

            color: #fca5a5;

            font-size: 0.84rem;

            line-height: 1.5;
        }


        .error-icon {

            width: 22px;
            height: 22px;

            flex: 0 0 22px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: rgba(239, 68, 68, 0.25);

            color: #fca5a5;

            font-size: 0.72rem;

            font-weight: 800;
        }


        /* =========================================================
           FORM GROUP
        ========================================================= */

        .form-group {

            margin-bottom:
                clamp(1rem, 1.7vw, 1.4rem);
        }


        .form-label {

            display: block;

            font-size:
                clamp(0.76rem, 0.9vw, 0.84rem);

            font-weight: 700;

            color: var(--text-hi);

            margin-bottom: 0.5rem;

            text-transform: uppercase;

            letter-spacing: 0.4px;
        }


        /* =========================================================
           INPUT WRAPPER
        ========================================================= */

        .input-wrap {

            position: relative;

            width: 100%;
        }


        .input-icon {

            position: absolute;

            left: 1rem;
            top: 50%;

            transform: translateY(-50%);

            width: 20px;
            height: 20px;

            display: flex;
            align-items: center;
            justify-content: center;

            color: var(--text-mute);

            pointer-events: none;

            z-index: 2;

            transition: color .18s ease;
        }


        /* =========================================================
           INPUT - DARK
        ========================================================= */

        .form-input {

            width: 100%;

            height:
                clamp(52px, 4.2vw, 58px);

            padding:
                0 3.2rem 0 2.8rem;

            border:
                1px solid var(--border);

            border-radius:
                clamp(11px, 1vw, 14px);

            outline: none;

            background: var(--bg-input);

            color: var(--text-hi);

            font-size:
                clamp(0.84rem, 1vw, 0.94rem);

            font-weight: 500;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease,
                background 0.2s ease;
        }


        .form-input:hover {

            border-color: #26384a;
        }


        .form-input:focus {

            border-color: var(--green);

            background: var(--bg-input);

            box-shadow:
                0 0 0 4px rgba(16, 185, 129, 0.15);
        }


        .form-input:focus~.input-icon {

            color: var(--green);
        }


        .form-input::placeholder {

            color: var(--text-mute);

            font-weight: 400;
        }


        /* =========================================================
           PASSWORD TOGGLE
        ========================================================= */

        .password-toggle {

            position: absolute;

            right: 8px;
            top: 50%;

            transform: translateY(-50%);

            width: 42px;
            height: 42px;

            border: none;

            background: transparent;

            border-radius: 10px;

            color: var(--text-mute);

            cursor: pointer;

            display: flex;
            align-items: center;
            justify-content: center;

            transition:
                background 0.2s ease,
                color 0.2s ease;
        }


        .password-toggle:hover {

            background: var(--bg-hover);

            color: var(--green);
        }


        /* =========================================================
           EYE ICON
        ========================================================= */

        .eye-icon {

            position: relative;

            width: 18px;
            height: 12px;

            border:
                2px solid currentColor;

            border-radius:
                80% 20%;

            transform: rotate(45deg);
        }


        .eye-icon::after {

            content: "";

            position: absolute;

            width: 5px;
            height: 5px;

            border-radius: 50%;

            background: currentColor;

            left: 50%;
            top: 50%;

            transform:
                translate(-50%, -50%);
        }


        .eye-icon.hidden {

            transform: rotate(45deg);

            opacity: 0.8;
        }


        .eye-icon.hidden::before {

            content: "";

            position: absolute;

            width: 23px;
            height: 2px;

            background: currentColor;

            left: -4px;
            top: 5px;

            transform: rotate(-45deg);

            border-radius: 10px;
        }


        /* =========================================================
           LOGIN BUTTON - GREEN GRADIENT
        ========================================================= */

        .btn-login {

            width: 100%;

            min-height:
                clamp(52px, 4.2vw, 60px);

            border: none;

            border-radius:
                clamp(11px, 1vw, 14px);

            background:
                linear-gradient(135deg,
                    #059669 0%,
                    #10b981 100%);

            color: #FFFFFF;

            font-size:
                clamp(0.84rem, 1vw, 0.96rem);

            font-weight: 700;

            display: flex;
            align-items: center;
            justify-content: center;

            gap: 11px;

            cursor: pointer;

            padding:
                0 1.4rem;

            box-shadow:
                0 12px 28px rgba(16, 185, 129, 0.25);

            transition:
                transform 0.18s ease,
                box-shadow 0.2s ease,
                filter 0.2s ease;

            letter-spacing: 0.3px;
        }


        .btn-login:hover {

            filter: brightness(1.08);

            box-shadow:
                0 16px 36px rgba(16, 185, 129, 0.4);

            transform:
                translateY(-1px);
        }


        .btn-login:active {

            transform:
                translateY(1px);
        }


        .btn-login.loading {

            opacity: 0.72;

            pointer-events: none;
        }


        .arrow-icon {

            font-size: 1.2rem;

            line-height: 1;

            transition:
                transform 0.2s ease;
        }


        .btn-login:hover .arrow-icon {

            transform:
                translateX(4px);
        }


        /* =========================================================
           SECURITY NOTE
        ========================================================= */

        .security-note {

            display: flex;
            align-items: center;
            justify-content: center;

            gap: 8px;

            margin-top:
                clamp(0.9rem, 1.5vw, 1.2rem);

            font-size: 0.7rem;

            color: var(--text-mute);

            font-weight: 500;
        }


        .security-icon {

            color: var(--green);

            font-size: 0.8rem;

            text-shadow:
                0 0 8px rgba(16, 185, 129, 0.5);
        }


        /* =========================================================
           FOOTER - DARK
        ========================================================= */

        .login-footer {

            margin-top:
                clamp(1.5rem, 2.5vw, 2.3rem);

            padding-top:
                clamp(1rem, 1.5vw, 1.35rem);

            border-top:
                1px solid var(--border);

            text-align: center;

            font-size: 0.7rem;

            color: var(--text-mute);

            line-height: 1.7;
        }


        .login-footer strong {

            color: var(--text-hi);

            font-size: 0.75rem;

            letter-spacing: 0.3px;
        }


        /* =========================================================
           LARGE DESKTOP
        ========================================================= */

        @media screen and (min-width: 1440px) {

            .login-wrapper {

                grid-template-columns:
                    minmax(0, 1.12fr) minmax(500px, 0.88fr);
            }

            .brand-inner {

                max-width: 680px;
            }

            .login-card {

                max-width: 470px;
            }
        }


        /* =========================================================
           LAPTOP
        ========================================================= */

        @media screen and (max-width: 1199px) {

            .login-wrapper {

                grid-template-columns:
                    minmax(0, 1fr) minmax(360px, 0.9fr);
            }

            .brand-panel {

                padding:
                    clamp(2rem, 4vw, 3.5rem);
            }

            .login-panel {

                padding:
                    clamp(1.8rem, 3vw, 3.5rem);
            }

            .feature-grid {

                gap: 0.6rem;
            }

            .feature-item {

                padding:
                    0.7rem;
            }
        }


        /* =========================================================
           SMALL LAPTOP / TABLET LANDSCAPE
        ========================================================= */

        @media screen and (max-width: 1024px) {

            .login-wrapper {

                grid-template-columns:
                    minmax(0, 0.95fr) minmax(350px, 1.05fr);
            }

            .brand-panel {

                padding:
                    2rem;
            }

            .brand-name {

                font-size:
                    clamp(2.4rem, 5vw, 3.5rem);
            }

            .brand-text {

                font-size: 0.86rem;
            }

            .feature-grid {

                grid-template-columns: 1fr;
            }

            .feature-item {

                font-size: 0.78rem;
            }

            .login-panel {

                padding:
                    2rem;
            }
        }


        /* =========================================================
           TABLET PORTRAIT
        ========================================================= */

        @media screen and (max-width: 820px) {

            body {

                background:
                    var(--bg-page);
            }


            .login-wrapper {

                display: flex;
                flex-direction: column;

                min-height: 100vh;
                min-height: 100dvh;

                overflow: visible;
            }


            .brand-panel {

                min-height: auto;

                padding:
                    clamp(1.5rem, 5vw, 2.4rem) clamp(1.2rem, 5vw, 2rem);

                text-align: center;

                border-radius:
                    0 0 26px 26px;

                border-right: none;

                border-bottom:
                    1px solid var(--border);
            }


            .brand-inner {

                max-width: 600px;
            }


            .logo-ring {

                margin:
                    0 auto clamp(0.8rem, 2vw, 1.2rem);
            }


            .brand-name {

                font-size:
                    clamp(2rem, 7vw, 3rem);

                letter-spacing:
                    -0.8px;
            }


            .brand-line {

                margin:
                    0.55rem auto 0.8rem;
            }


            .brand-tagline {

                margin-bottom: 0;

                font-size:
                    clamp(0.68rem, 2vw, 0.82rem);
            }


            .brand-text,
            .feature-grid {

                display: none;
            }


            .login-panel {

                min-height: auto;

                padding:
                    clamp(1.2rem, 4vw, 2rem) clamp(1rem, 4vw, 1.5rem) 2rem;

                align-items: flex-start;

                background:
                    var(--bg-page);

                overflow: visible;
            }


            .login-card {

                max-width: 520px;

                background: var(--bg-card);

                border:
                    1px solid var(--border);

                border-radius: 22px;

                padding:
                    clamp(1.4rem, 5vw, 2.1rem);

                box-shadow:
                    var(--shadow-soft);
            }
        }


        /* =========================================================
           MOBILE
        ========================================================= */

        @media screen and (max-width: 560px) {

            .brand-panel {

                padding:
                    1.35rem 1rem 1.4rem;
            }


            .logo-ring {

                width: 74px;
                height: 74px;

                border-width: 2px;
            }


            .brand-name {

                font-size:
                    clamp(1.75rem, 9vw, 2.35rem);
            }


            .brand-tagline {

                font-size: 0.65rem;

                letter-spacing:
                    1.1px;
            }


            .login-panel {

                padding:
                    0.9rem 0.75rem 1.4rem;
            }


            .login-card {

                border-radius: 18px;

                padding:
                    1.3rem 1.05rem 1.35rem;
            }


            .login-badge {

                margin-bottom: 1.15rem;

                font-size: 0.64rem;

                padding:
                    0.4rem 0.75rem 0.4rem 0.6rem;
            }


            .login-card h1 {

                font-size:
                    1.8rem;
            }


            .login-sub {

                font-size:
                    0.82rem;

                margin-bottom:
                    1.35rem;
            }


            .form-input {

                height: 52px;
            }


            .btn-login {

                min-height: 54px;
            }


            .login-footer {

                margin-top:
                    1.5rem;
            }
        }


        /* =========================================================
           VERY SMALL MOBILE
        ========================================================= */

        @media screen and (max-width: 380px) {

            .brand-panel {

                padding:
                    1.1rem 0.8rem 1.2rem;
            }


            .logo-ring {

                width: 66px;
                height: 66px;
            }


            .brand-name {

                font-size: 1.65rem;
            }


            .brand-tagline {

                font-size: 0.58rem;

                letter-spacing: 0.9px;
            }


            .login-panel {

                padding:
                    0.7rem 0.6rem 1rem;
            }


            .login-card {

                padding:
                    1.15rem 0.9rem 1.2rem;

                border-radius: 16px;
            }


            .login-card h1 {

                font-size: 1.65rem;
            }


            .form-input {

                padding-left:
                    2.55rem;

                padding-right:
                    2.9rem;
            }
        }


        /* =========================================================
           SHORT SCREEN
        ========================================================= */

        @media screen and (max-height: 700px) and (min-width: 821px) {

            .brand-panel,
            .login-panel {

                padding-top: 1.5rem;
                padding-bottom: 1.5rem;
            }


            .logo-ring {

                margin-bottom: 1rem;
            }


            .brand-text {

                margin-bottom: 1.2rem;
            }


            .login-card h1 {

                font-size: 2rem;
            }


            .login-sub {

                margin-bottom: 1.3rem;
            }


            .form-group {

                margin-bottom: 1rem;
            }


            .login-footer {

                margin-top: 1.2rem;
            }
        }


        /* =========================================================
           ACCESSIBILITY
        ========================================================= */

        :focus-visible {

            outline:
                3px solid rgba(16, 185, 129, 0.5);

            outline-offset: 3px;
        }


        /* =========================================================
           REDUCED MOTION
        ========================================================= */

        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {

                scroll-behavior: auto !important;

                transition: none !important;

                animation: none !important;
            }
        }
    </style>

</head>


<body>


    <div class="login-wrapper">


        <!-- =========================================================
         LEFT BRAND PANEL
    ========================================================= -->

        <section class="brand-panel">

            <div class="brand-inner">


                <!-- LOGO -->

                <div class="logo-ring">

                    <img
                        src="uploads/logo/logo.png"
                        alt="GatewayLinen logo"
                        onerror="
                        this.style.display='none';
                        this.parentNode.innerHTML=
                        '<span style=&quot;font-size:2.5rem;color:#10b981;font-weight:700;font-family:Georgia,serif;&quot;>GL</span>';
                    ">

                </div>


                <!-- BRAND NAME -->

                <h2 class="brand-name">
                    Gateway<span>Linen</span>
                </h2>


                <div class="brand-line"></div>


                <!-- TAGLINE -->

                <div class="brand-tagline">
                    Premium Linen Solutions
                </div>


                <!-- DESCRIPTION -->

                <p class="brand-text">

                    Welcome to GatewayLinen Administration. Manage products, inventory, orders, customers, and day-to-day business operations from one secure and professional workspace.

                </p>


                <!-- FEATURES -->

                <div class="feature-grid">


                    <div class="feature-item">

                        <span class="feature-icon">◆</span>

                        <span>
                            Secure Administration
                        </span>

                    </div>


                    <div class="feature-item">

                        <span class="feature-icon">◇</span>

                        <span>
                            Product &amp; Catalogue Management
                        </span>

                    </div>


                    <div class="feature-item">

                        <span class="feature-icon">▣</span>

                        <span>
                            Inventory Management
                        </span>

                    </div>


                    <div class="feature-item">

                        <span class="feature-icon">●</span>

                        <span>
                            Order &amp; Customer Management
                        </span>

                    </div>


                </div>


            </div>

        </section>


        <!-- =========================================================
         RIGHT LOGIN PANEL
    ========================================================= -->

        <section class="login-panel">


            <div class="login-card">


                <!-- ADMINISTRATOR BADGE -->

                <div class="login-badge">

                    <span class="badge-dot"></span>

                    Administrator Sign-In

                </div>


                <!-- HEADING -->

                <h1>
                    Welcome back
                </h1>


                <p class="login-sub">

                    Sign in securely to access the GatewayLinen administration portal.

                </p>


                <!-- =====================================================
                 ERROR MESSAGE
            ===================================================== -->

                <?php if ($error !== ""): ?>

                    <div class="error-box">

                        <div class="error-icon">
                            !
                        </div>

                        <div>
                            <?= htmlspecialchars($error) ?>
                        </div>

                    </div>

                <?php endif; ?>


                <!-- =====================================================
                 LOGIN FORM
            ===================================================== -->

                <form
                    id="loginForm"
                    method="POST"
                    action=""
                    autocomplete="on">


                    <!-- USERNAME -->

                    <div class="form-group">

                        <label
                            class="form-label"
                            for="username">
                            Username
                        </label>


                        <div class="input-wrap">

                            <span class="input-icon">
                                ●
                            </span>


                            <input
                                type="text"
                                id="username"
                                name="username"
                                class="form-input"
                                placeholder="Enter your username"
                                value="<?= htmlspecialchars($username) ?>"
                                autocomplete="username"
                                required
                                autofocus>

                        </div>

                    </div>


                    <!-- PASSWORD -->

                    <div class="form-group">

                        <label
                            class="form-label"
                            for="password">
                            Password
                        </label>


                        <div class="input-wrap">

                            <span class="input-icon">
                                ◆
                            </span>


                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-input"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required>


                            <button
                                type="button"
                                class="password-toggle"
                                id="togglePassword"
                                aria-label="Show password"
                                title="Show password">

                                <span
                                    class="eye-icon"
                                    id="eyeIcon"></span>

                            </button>

                        </div>

                    </div>


                    <!-- LOGIN BUTTON -->

                    <button
                        type="submit"
                        class="btn-login"
                        id="loginButton">

                        <span id="buttonText">
                            Sign In to Administration
                        </span>

                        <span class="arrow-icon">
                            →
                        </span>

                    </button>


                </form>


                <!-- =====================================================
                 SECURITY NOTE
            ===================================================== -->

                <div class="security-note">

                    <span class="security-icon">
                        ◆
                    </span>

                    <span>
                        Secure administrator authentication
                    </span>

                </div>


                <!-- =====================================================
                 FOOTER
            ===================================================== -->

                <div class="login-footer">

                    <strong>
                        GatewayLinen
                    </strong>

                    <br>

                    Premium Linen Solutions for the Hospitality Industry

                    <br>

                    &copy;
                    <?= date("Y") ?>
                    GatewayLinen.
                    All rights reserved.

                </div>


            </div>

        </section>


    </div>


    <script>
        (function() {


            /* =========================================================
               PASSWORD SHOW / HIDE
            ========================================================= */

            const passwordInput =
                document.getElementById("password");

            const toggleBtn =
                document.getElementById("togglePassword");

            const eyeIcon =
                document.getElementById("eyeIcon");


            toggleBtn.addEventListener(
                "click",
                function() {

                    if (
                        passwordInput.type === "password"
                    ) {

                        passwordInput.type = "text";

                        eyeIcon.classList.add("hidden");

                        toggleBtn.setAttribute(
                            "aria-label",
                            "Hide password"
                        );

                        toggleBtn.setAttribute(
                            "title",
                            "Hide password"
                        );

                    } else {

                        passwordInput.type = "password";

                        eyeIcon.classList.remove("hidden");

                        toggleBtn.setAttribute(
                            "aria-label",
                            "Show password"
                        );

                        toggleBtn.setAttribute(
                            "title",
                            "Show password"
                        );

                    }

                }
            );


            /* =========================================================
               PREVENT DOUBLE SUBMISSION
            ========================================================= */

            const loginForm =
                document.getElementById("loginForm");

            const loginButton =
                document.getElementById("loginButton");

            const buttonText =
                document.getElementById("buttonText");


            let submitted = false;


            loginForm.addEventListener(
                "submit",
                function(event) {

                    if (submitted) {

                        event.preventDefault();

                        return;
                    }


                    submitted = true;


                    loginButton.classList.add(
                        "loading"
                    );


                    buttonText.textContent =
                        "Signing in...";

                }
            );


            /* =========================================================
               ENTER KEY TO SUBMIT (GLOBAL)
            ========================================================= */

            document.addEventListener(
                "keydown",
                function(e) {

                    if (e.key === "Enter") {

                        const active = document.activeElement;

                        if (
                            active &&
                            active.tagName !== "BUTTON"
                        ) {

                            if (loginForm) {

                                if (loginForm.requestSubmit) {
                                    loginForm.requestSubmit();
                                }

                            }

                        }

                    }

                }
            );


        })();
    </script>


</body>

</html>