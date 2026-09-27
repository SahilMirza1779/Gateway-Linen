<?php
session_start();

/*
|--------------------------------------------------------------------------
| GatewayLinen Admin - Add Warehouse
|--------------------------------------------------------------------------
*/

if (empty($_SESSION["admin_id"])) {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . "/../config/database.php";

$activeMenu = "warehouses";
$pageTitle  = "GatewayLinen | Add Warehouse";

if (!isset($_SESSION["admin_name"])) {
    $_SESSION["admin_name"] = $_SESSION["admin_username"] ?? "GatewayLinen Administrator";
}

if (!isset($_SESSION["admin_role"])) {
    $_SESSION["admin_role"] = "Administrator";
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/
if (empty($_SESSION["warehouse_csrf_token"])) {
    $_SESSION["warehouse_csrf_token"] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION["warehouse_csrf_token"];

/*
|--------------------------------------------------------------------------
| FORM VARIABLES
|--------------------------------------------------------------------------
*/
$warehouseCode  = "";
$warehouseName  = "";
$address        = "";
$city           = "";
$stateProvince  = "";
$postalCode     = "";
$isPrimary      = 0;
$isActive       = 1;
$error          = "";

/*
|--------------------------------------------------------------------------
| HANDLE FORM SUBMISSION
|--------------------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $postedToken = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["warehouse_csrf_token"], $postedToken)) {
        $error = "Security verification failed. Please refresh the page and try again.";
    }

    if ($error === "") {
        $warehouseCode  = trim($_POST["warehouse_code"] ?? "");
        $warehouseName  = trim($_POST["warehouse_name"] ?? "");
        $address        = trim($_POST["address"] ?? "");
        $city           = trim($_POST["city"] ?? "");
        $stateProvince  = trim($_POST["state_province"] ?? "");
        $postalCode     = trim($_POST["postal_code"] ?? "");
        $isPrimary      = isset($_POST["is_primary"]) ? 1 : 0;
        $isActive       = isset($_POST["is_active"]) ? 1 : 0;
    }

    if ($error === "" && $warehouseName === "") {
        $error = "Warehouse name is required.";
    }

    if ($error === "" && $warehouseCode === "") {
        $error = "Warehouse code is required.";
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK IF PRIMARY WAREHOUSE ALREADY EXISTS (OPTIONAL LOGIC)
    |--------------------------------------------------------------------------
    */
    if ($error === "" && $isPrimary === 1) {
        $resetPrimarySql = "UPDATE dbo.Warehouses SET IsPrimary = 0";
        sqlsrv_query($conn, $resetPrimarySql);
    }

    /*
    |--------------------------------------------------------------------------
    | INSERT WAREHOUSE
    |--------------------------------------------------------------------------
    */
    if ($error === "") {
        $insertSql = "
            INSERT INTO dbo.Warehouses
            (
                WarehouseCode,
                WarehouseName,
                Address,
                City,
                StateProvince,
                PostalCode,
                IsPrimary,
                IsActive,
                CreatedAt
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, GETDATE())
        ";

        $params = [
            $warehouseCode,
            $warehouseName,
            $address !== "" ? $address : null,
            $city !== "" ? $city : null,
            $stateProvince !== "" ? $stateProvince : null,
            $postalCode !== "" ? $postalCode : null,
            $isPrimary,
            $isActive
        ];

        $stmt = sqlsrv_query($conn, $insertSql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            $error = $errors[0]["message"] ?? "Unable to create warehouse.";
        } else {
            sqlsrv_free_stmt($stmt);
            $_SESSION["warehouse_csrf_token"] = bin2hex(random_bytes(32));
            header("Location: index.php?success=" . urlencode("Warehouse created successfully."));
            exit;
        }
    }
}

require_once __DIR__ . "/../includes/header.php";
require_once __DIR__ . "/../includes/sidebar.php";
?>

<style>
    :root {
        --bg-page: #0a1119;
        --bg-card: #111b26;
        --bg-card-alt: #0f1823;
        --bg-input: #0d1620;
        --bg-hover: #16222e;
        --border: #1e2d3d;
        --border-soft: #182636;
        --text-hi: #f0f4f8;
        --text-body: #a8b8c8;
        --text-mute: #5f7488;
        --green: #10b981;
        --green-dark: #059669;
        --green-soft: rgba(16,185,129,.12);
        --blue: #3b82f6;
        --blue-soft: rgba(59,130,246,.12);
        --purple: #8b5cf6;
        --purple-soft: rgba(139,92,246,.12);
        --red: #ef4444;
        --red-soft: rgba(239,68,68,.12);
        --radius: 10px;
    }

    html, body { background: var(--bg-page) !important; color: var(--text-body) !important; }
    .main, .content { background: var(--bg-page) !important; }

    .add-warehouse-page {
        width: 100%;
        max-width: 1180px;
        margin: 0 auto;
        padding: 20px 20px 45px;
        box-sizing: border-box;
    }

    .page-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 24px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--border);
    }

    .breadcrumb {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 8px;
        color: var(--text-mute);
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .4px;
        text-transform: uppercase;
    }
    .breadcrumb .current { color: var(--green); }

    .page-title {
        margin: 0;
        color: var(--text-hi);
        font-size: 27px;
        line-height: 1.2;
        font-weight: 800;
    }

    .page-subtitle {
        margin: 7px 0 0;
        color: var(--text-mute);
        font-size: 12px;
        line-height: 1.5;
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 40px;
        padding: 0 16px;
        border: 1px solid var(--border);
        border-radius: 9px;
        background: var(--bg-input);
        color: var(--text-body) !important;
        font-size: 11px;
        font-weight: 700;
        text-decoration: none;
        white-space: nowrap;
        transition: .2s ease;
    }
    .btn-back:hover {
        border-color: var(--green);
        background: var(--green-soft);
        color: var(--green) !important;
    }

    .alert-error {
        display: flex;
        align-items: flex-start;
        gap: 11px;
        margin-bottom: 20px;
        padding: 14px 16px;
        border: 1px solid rgba(239,68,68,.3);
        border-left: 4px solid var(--red);
        border-radius: 9px;
        background: var(--red-soft);
        color: #fca5a5;
        font-size: 12px;
        font-weight: 600;
    }

    .form-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 15px 40px rgba(0,0,0,.18);
    }

    .form-body {
        padding: 24px 22px;
        box-sizing: border-box;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 22px 26px;
    }

    .form-section {
        grid-column: 1 / -1;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 14px;
        padding: 10px 14px;
        border-radius: 8px;
        background: var(--green-soft);
        border-left: 3px solid var(--green);
    }
    .form-section:first-child { margin-top: 0; }
    .form-section-icon {
        display: flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 7px; background: rgba(16,185,129,.18); color: var(--green); font-size: 12px; font-weight: 900;
    }
    .form-section-title { color: var(--text-hi); font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .8px; }

    .form-group { min-width: 0; }
    .form-group-full { grid-column: 1 / -1; }

    .form-label {
        display: block; margin-bottom: 8px; color: var(--text-body); font-size: 10.5px; font-weight: 700; letter-spacing: .3px; text-transform: uppercase;
    }
    .required { color: var(--red); margin-left: 3px; }

    .form-input, .form-textarea {
        width: 100%; box-sizing: border-box; border: 1px solid var(--border); border-radius: 9px; outline: none; background: var(--bg-input); color: var(--text-hi); font-family: inherit; font-size: 12px; font-weight: 500; transition: border-color .18s ease, box-shadow .18s ease;
    }
    .form-input { height: 44px; padding: 0 13px; }
    .form-textarea { min-height: 90px; padding: 12px 13px; resize: vertical; line-height: 1.5; }
    .form-input:focus, .form-textarea:focus { border-color: var(--green); box-shadow: 0 0 0 3px rgba(16,185,129,.13); }

    .checkbox-box {
        display: flex; align-items: center; min-height: 44px; padding: 0 14px; border: 1px solid var(--border); border-radius: 9px; background: var(--bg-input);
    }
    .checkbox-label {
        display: inline-flex; align-items: center; gap: 10px; color: var(--text-body); font-size: 11.5px; font-weight: 600; cursor: pointer; user-select: none;
    }
    .checkbox-input { width: 18px; height: 18px; margin: 0; accent-color: var(--green); cursor: pointer; }

    .form-footer {
        display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding: 18px 22px; border-top: 1px solid var(--border); background: var(--bg-card-alt);
    }
    .btn-submit {
        display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 42px; padding: 0 22px; border-radius: 9px; text-decoration: none; font-family: inherit; font-size: 11.5px; font-weight: 700; cursor: pointer; border: 1px solid var(--green-dark); background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: #ffffff; box-shadow: 0 6px 18px rgba(16,185,129,.24); transition: all .18s ease;
    }
    .btn-submit:hover { filter: brightness(1.08); transform: translateY(-1px); }

    /* SHORTCUTS */
    .shortcut-help-box {
        margin-top: 22px; padding: 18px 22px; border: 1px solid var(--border); border-radius: 12px; background: var(--bg-card);
    }
    .shortcut-help-box.hidden { display: none; }
    .shortcut-help-title { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; color: var(--text-hi); font-size: 13px; font-weight: 700; }
    .shortcut-help-title small { margin-left: auto; color: var(--text-mute); font: 600 10px monospace; }
    .shortcut-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 10px; }
    .shortcut-item { display: flex; align-items: center; gap: 11px; padding: 8px 11px; border: 1px solid var(--border-soft); border-radius: 9px; background: var(--bg-input); }
    .shortcut-key { display: inline-flex; align-items: center; justify-content: center; min-width: 38px; height: 30px; padding: 0 10px; border-radius: 7px; background: #0a1119; border: 1px solid var(--border); color: var(--green); font: 900 11px monospace; }
    .shortcut-desc { color: var(--text-body); font-size: 11px; font-weight: 600; }

    @media (max-width: 900px) {
        .form-grid { grid-template-columns: 1fr; }
        .form-group-full { grid-column: auto; }
        .shortcut-grid { grid-template-columns: repeat(2, 1fr); }
    }
</style>

<main class="main">
    <section class="content">
        <div class="add-warehouse-page">

            <!-- PAGE HEADER -->
            <div class="page-header">
                <div>
                    <div class="breadcrumb">
                        <span>Dashboard</span>
                        <span>›</span>
                        <span>Warehouses</span>
                        <span>›</span>
                        <span class="current">Add Warehouse</span>
                    </div>
                    <h1 class="page-title">Add New Warehouse</h1>
                    <p class="page-subtitle">Configure fulfillment center details, address, and operating status.</p>
                </div>

                <a href="index.php" class="btn-back">← Back to Warehouses</a>
            </div>

            <!-- ERROR -->
            <?php if ($error !== ""): ?>
                <div class="alert-error">
                    <div>!</div>
                    <div><?= e($error) ?></div>
                </div>
            <?php endif; ?>

            <!-- FORM -->
            <form method="POST" autocomplete="off" class="form-card" id="addWarehouseForm">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

                <div class="form-body">
                    <div class="form-grid">

                        <div class="form-section">
                            <div class="form-section-icon">#</div>
                            <div class="form-section-title">Warehouse Details</div>
                        </div>

                        <!-- WAREHOUSE NAME -->
                        <div class="form-group">
                            <label for="warehouseName" class="form-label">
                                Warehouse Name <span class="required">*</span>
                            </label>
                            <input
                                type="text"
                                id="warehouseName"
                                name="warehouse_name"
                                class="form-input"
                                value="<?= e($warehouseName) ?>"
                                placeholder="e.g. Main Fulfillment Center"
                                maxlength="100"
                                required
                            >
                        </div>

                        <!-- WAREHOUSE CODE -->
                        <div class="form-group">
                            <label for="warehouseCode" class="form-label">
                                Warehouse Code <span class="required">*</span>
                            </label>
                            <input
                                type="text"
                                id="warehouseCode"
                                name="warehouse_code"
                                class="form-input"
                                value="<?= e($warehouseCode) ?>"
                                placeholder="e.g. WH-MAIN-01"
                                maxlength="50"
                                required
                            >
                        </div>

                        <div class="form-section sec-blue" style="grid-column: 1/-1;">
                            <div class="form-section-icon">📍</div>
                            <div class="form-section-title">Location & Address</div>
                        </div>

                        <!-- ADDRESS -->
                        <div class="form-group form-group-full">
                            <label for="address" class="form-label">Street Address</label>
                            <textarea
                                id="address"
                                name="address"
                                class="form-textarea"
                                placeholder="Enter full street address..."
                                maxlength="255"
                            ><?= e($address) ?></textarea>
                        </div>

                        <!-- CITY -->
                        <div class="form-group">
                            <label for="city" class="form-label">City</label>
                            <input
                                type="text"
                                id="city"
                                name="city"
                                class="form-input"
                                value="<?= e($city) ?>"
                                placeholder="e.g. New York"
                                maxlength="100"
                            >
                        </div>

                        <!-- STATE / PROVINCE -->
                        <div class="form-group">
                            <label for="stateProvince" class="form-label">State / Province</label>
                            <input
                                type="text"
                                id="stateProvince"
                                name="state_province"
                                class="form-input"
                                value="<?= e($stateProvince) ?>"
                                placeholder="e.g. NY"
                                maxlength="100"
                            >
                        </div>

                        <!-- POSTAL CODE -->
                        <div class="form-group">
                            <label for="postalCode" class="form-label">Postal / Zip Code</label>
                            <input
                                type="text"
                                id="postalCode"
                                name="postal_code"
                                class="form-input"
                                value="<?= e($postalCode) ?>"
                                placeholder="e.g. 10001"
                                maxlength="20"
                            >
                        </div>

                        <div class="form-group"></div>

                        <div class="form-section sec-amber" style="grid-column: 1/-1;">
                            <div class="form-section-icon">⚙</div>
                            <div class="form-section-title">Status & Settings</div>
                        </div>

                        <!-- ACTIVE STATUS -->
                        <div class="form-group">
                            <label class="form-label">Operational Status</label>
                            <div class="checkbox-box">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="is_active" value="1" class="checkbox-input" <?= $isActive ? 'checked' : '' ?>>
                                    <span>Active Warehouse</span>
                                </label>
                            </div>
                        </div>

                        <!-- PRIMARY STATUS -->
                        <div class="form-group">
                            <label class="form-label">Primary Designation</label>
                            <div class="checkbox-box">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="is_primary" value="1" class="checkbox-input" <?= $isPrimary ? 'checked' : '' ?>>
                                    <span>Set as Primary Fulfillment Hub</span>
                                </label>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- FORM FOOTER -->
                <div class="form-footer">
                    <a href="index.php" class="btn-back" style="min-height:42px; padding:0 18px;">Cancel</a>
                    <button type="submit" class="btn-submit" id="saveWarehouseBtn">
                        <span>✓</span>
                        <span>Save Warehouse</span>
                    </button>
                </div>
            </form>

            <!-- SHORTCUTS -->
            <div class="shortcut-help-box" id="shortcutHelpBox">
                <div class="shortcut-help-title">
                    <span>⌨</span>
                    <span>Keyboard Shortcuts</span>
                    <small>Press H to show / hide</small>
                </div>
                <div class="shortcut-grid">
                    <div class="shortcut-item"><span class="shortcut-key">A</span><span class="shortcut-desc">Save Warehouse</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">B</span><span class="shortcut-desc">Back to List</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">N</span><span class="shortcut-desc">Focus Warehouse Name</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">H</span><span class="shortcut-desc">Toggle Shortcuts</span></div>
                    <div class="shortcut-item"><span class="shortcut-key">Esc</span><span class="shortcut-desc">Blur Field</span></div>
                </div>
            </div>

        </div>
    </section>
</main>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const nameInput = document.getElementById("warehouseName");
        const codeInput = document.getElementById("warehouseCode");
        const form = document.getElementById("addWarehouseForm");
        const shortcutBox = document.getElementById("shortcutHelpBox");

        if (nameInput && window.innerWidth > 700) {
            setTimeout(() => nameInput.focus(), 150);
        }

        document.addEventListener("keydown", function (event) {
            const key = event.key.toLowerCase();
            const activeEl = document.activeElement;
            const isTyping = activeEl && (activeEl.tagName === "INPUT" || activeEl.tagName === "TEXTAREA" || activeEl.tagName === "SELECT");

            if (key === "h" && !event.ctrlKey && !event.metaKey && !event.altKey && !isTyping) {
                event.preventDefault();
                shortcutBox?.classList.toggle("hidden");
                return;
            }

            if (event.key === "Escape") {
                activeEl?.blur();
                return;
            }

            if (isTyping) return;

            if (key === "a" && !event.ctrlKey) {
                event.preventDefault();
                form?.submit();
            } else if (key === "b" && !event.ctrlKey) {
                event.preventDefault();
                window.location.href = "index.php";
            } else if (key === "n" && !event.ctrlKey) {
                event.preventDefault();
                nameInput?.focus();
            }
        });
    });
</script>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>