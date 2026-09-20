<?php
session_start();

// Agar admin login nahi hai, toh login page par bhej do
if (!isset($_SESSION["admin_id"])) {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Active menu item ko highlight karne ke liye variable
$activeMenu = 'products';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GatewayLinen | Products Management</title>

    <style>
        /* =========================================================
           ROOT - DARK THEME VARIABLES (Matching your UI)
        ========================================================= */
        :root {
            --bg-page: #0a1119;
            --bg-card: #111b26;
            --border: #1e2d3d;
            --text-hi: #f0f4f8;
            --text-body: #a8b8c8;
            --text-mute: #5f7488;
            --green: #10b981;
            --green-hover: #059669;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: "Segoe UI", Inter, Arial, sans-serif;
            background: var(--bg-page);
            color: var(--text-body);
        }

        /* MAIN CONTENT AREA */
        .main-content {
            margin-left: 255px;
            /* Sidebar ki width ke barabar */
            padding: 30px;
        }

        @media (max-width: 900px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
        }

        /* PAGE HEADER */
        .page-header {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .page-title {
            font-family: Georgia, "Times New Roman", serif;
            font-size: 1.8rem;
            color: var(--text-hi);
            margin: 0 0 5px 0;
        }

        .page-sub {
            color: var(--text-mute);
            margin: 0;
            font-size: 0.9rem;
        }

        /* BUTTONS */
        .btn-add {
            background: linear-gradient(135deg, var(--green-hover) 0%, var(--green) 100%);
            color: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
            transition: all 0.2s ease;
        }

        .btn-add:hover {
            filter: brightness(1.1);
            transform: translateY(-2px);
        }

        /* TABLE CONTAINER */
        .table-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        th {
            color: var(--text-mute);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
        }

        td {
            color: var(--text-hi);
            font-size: 0.9rem;
        }

        .status-badge {
            background: rgba(16, 185, 129, 0.15);
            color: var(--green);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-mute);
        }
    </style>
</head>

<body>

    <!-- SIDEBAR INCLUSION -->
    <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- MAIN DASHBOARD CONTENT -->
    <main class="main-content">

        <!-- Header Section -->
        <div class="page-header">
            <div>
                <h1 class="page-title">Products List</h1>
                <p class="page-sub">View, add, edit, and manage your GatewayLinen products catalog.</p>
            </div>
            <div>
                <a href="add.php" class="btn-add">+ Add New Product</a>
            </div>
        </div>

        <!-- Products Table Section -->
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Abhi ke liye empty state dikha rahe hain. Baad mein isko Database loop se connect karenge -->
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <h3>No Products Found</h3>
                                <p>You haven't added any products yet. Click the "Add New Product" button to get started.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    </main>

</body>

</html>