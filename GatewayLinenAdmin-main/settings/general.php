<?php
// GatewayLinenAdmin-main/settings/general.php
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GatewayLinen | System Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0b132b;
            color: #f3f4f6;
        }

        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #0b132b;
        }

        ::-webkit-scrollbar-thumb {
            background: #1f2937;
            border-radius: 3px;
        }
    </style>
</head>

<body class="min-h-screen flex bg-[#0b132b]">

    <!-- EXACT MATCHING SIDEBAR -->
    <aside class="w-64 bg-[#0b132b] border-r border-[#1f2937] flex flex-col justify-between hidden md:flex fixed h-full z-20 overflow-y-auto">
        <div>
            <!-- Logo Area -->
            <div class="p-6 flex items-center gap-3 border-b border-[#1f2937]">
                <div class="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center font-bold text-[#0b132b]">G</div>
                <span class="font-bold text-lg text-white tracking-wide">GatewayLinen</span>
            </div>

            <!-- Navigation Menu -->
            <div class="px-4 py-6 space-y-6">
                <!-- MAIN -->
                <div>
                    <p class="px-4 text-[10px] font-bold text-gray-500 tracking-wider uppercase mb-2">Main</p>
                    <div class="space-y-1">
                        <a href="../dashboard.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-gray-400 hover:bg-[#111827] hover:text-white transition-all"><span>Dashboard</span></a>
                        <a href="../categories/index.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-gray-400 hover:bg-[#111827] hover:text-white transition-all"><span>Categories</span></a>
                        <a href="../products/index.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-gray-400 hover:bg-[#111827] hover:text-white transition-all"><span>Products</span></a>
                        <a href="../inventory/index.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-gray-400 hover:bg-[#111827] hover:text-white transition-all"><span>Inventory</span></a>
                    </div>
                </div>

                <!-- SALES & MARKETING -->
                <div>
                    <p class="px-4 text-[10px] font-bold text-gray-500 tracking-wider uppercase mb-2">Sales & Marketing</p>
                    <div class="space-y-1">
                        <a href="../orders/index.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-gray-400 hover:bg-[#111827] hover:text-white transition-all"><span>Orders</span></a>
                        <a href="../customers/index.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-gray-400 hover:bg-[#111827] hover:text-white transition-all"><span>Customers</span></a>
                        <a href="../wholesale/index.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-gray-400 hover:bg-[#111827] hover:text-white transition-all"><span>Wholesale</span></a>
                        <a href="../quotes/index.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-gray-400 hover:bg-[#111827] hover:text-white transition-all"><span>Quotes</span></a>
                        <a href="../bulk_inquiries/index.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-gray-400 hover:bg-[#111827] hover:text-white transition-all"><span>Bulk Inquiries</span></a>
                        <a href="../wishlist/index.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-gray-400 hover:bg-[#111827] hover:text-white transition-all"><span>Wishlist</span></a>
                        <a href="../coupons/index.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-gray-400 hover:bg-[#111827] hover:text-white transition-all"><span>Coupons</span></a>
                        <a href="../reviews/index.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-gray-400 hover:bg-[#111827] hover:text-white transition-all"><span>Reviews</span></a>
                        <a href="../newsletter/index.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-gray-400 hover:bg-[#111827] hover:text-white transition-all"><span>Newsletter</span></a>
                        <a href="../back_in_stock/index.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-gray-400 hover:bg-[#111827] hover:text-white transition-all"><span>Back in Stock</span></a>
                    </div>
                </div>

                <!-- OPERATIONS -->
                <div>
                    <p class="px-4 text-[10px] font-bold text-gray-500 tracking-wider uppercase mb-2">Operations</p>
                    <div class="space-y-1">
                        <a href="../warehouses/index.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-gray-400 hover:bg-[#111827] hover:text-white transition-all"><span>Warehouses</span></a>
                        <a href="../variants/index.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-gray-400 hover:bg-[#111827] hover:text-white transition-all"><span>Product Variants</span></a>
                        <a href="../stock_movements/index.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-gray-400 hover:bg-[#111827] hover:text-white transition-all"><span>Stock Movements</span></a>
                        <a href="../shipping/index.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-gray-400 hover:bg-[#111827] hover:text-white transition-all"><span>Taxes & Shipping</span></a>
                    </div>
                </div>

                <!-- SYSTEM -->
                <div>
                    <p class="px-4 text-[10px] font-bold text-gray-500 tracking-wider uppercase mb-2">System</p>
                    <div class="space-y-1">
                        <a href="../users/index.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-gray-400 hover:bg-[#111827] hover:text-white transition-all"><span>Users</span></a>
                        <a href="../roles/index.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-gray-400 hover:bg-[#111827] hover:text-white transition-all"><span>Roles & Permissions</span></a>
                        <a href="general.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold bg-emerald-500 text-[#0b132b] shadow-lg"><span>System Settings</span></a>
                        <a href="../audit_logs/index.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-gray-400 hover:bg-[#111827] hover:text-white transition-all"><span>Audit Logs</span></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-4 border-t border-[#1f2937]">
            <a href="../logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-semibold text-red-400 hover:bg-red-500/10 transition-all"><span>Logout</span></a>
        </div>
    </aside>

    <!-- MAIN CONTENT AREA -->
    <main class="flex-1 md:ml-64 p-6 md:p-10 flex flex-col justify-center items-center">
        <div class="w-full max-w-2xl bg-[#111827] border border-[#1f2937] rounded-3xl p-6 md:p-8 shadow-2xl">

            <div class="mb-6 border-b border-[#1f2937] pb-4">
                <span class="text-[10px] font-bold text-emerald-400 tracking-widest uppercase">System Configuration</span>
                <h2 class="text-xl md:text-2xl font-bold text-white mt-1">Homepage Hero Banner Settings</h2>
                <p class="text-xs text-gray-400 mt-1 font-light">
                    Manage and update the primary banner image displayed on the website's hero section. Changes apply instantly.
                </p>
            </div>

            <form id="heroForm" onsubmit="uploadHeroImage(event)" class="space-y-5">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-300 mb-2">
                        Select New Hero Image
                    </label>
                    <div class="flex items-center justify-center w-full">
                        <label class="flex flex-col items-center justify-center w-full h-44 border-2 border-dashed border-[#374151] rounded-2xl cursor-pointer bg-[#0b132b] hover:border-emerald-500 transition-all group relative overflow-hidden">

                            <div id="defaultPrompt" class="flex flex-col items-center justify-center pt-5 pb-6 px-4 text-center">
                                <svg class="w-9 h-9 mb-2 text-gray-400 group-hover:text-emerald-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                <p class="text-xs text-gray-300 font-medium"><span class="text-emerald-400 font-semibold">Click to upload</span> or drag & drop</p>
                                <p class="text-[10px] text-gray-500 mt-1">PNG, JPG, WEBP up to 10MB</p>
                            </div>

                            <img id="imagePreview" class="hidden absolute inset-0 w-full h-full object-cover opacity-90" alt="Preview" />

                            <input type="file" id="heroImageInput" name="heroImage" accept="image/png, image/jpeg, image/jpg, image/webp" class="hidden" />
                        </label>
                    </div>
                </div>

                <button
                    type="submit"
                    class="w-full py-3.5 bg-emerald-500 hover:bg-emerald-600 text-[#0b132b] font-bold text-xs uppercase tracking-widest rounded-xl shadow-lg transition-all cursor-pointer flex items-center justify-center gap-2">
                    <span>Upload & Update Hero Image</span>
                </button>

                <div id="statusMsg" class="text-center text-xs font-medium mt-3"></div>
            </form>
        </div>
    </main>

    <script>
        let uploadedFile = null;

        document.getElementById('heroImageInput').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                uploadedFile = e.target.files[0];
                const previewImg = document.getElementById('imagePreview');
                const defaultPrompt = document.getElementById('defaultPrompt');
                const statusMsg = document.getElementById('statusMsg');

                const reader = new FileReader();
                reader.onload = function(event) {
                    previewImg.src = event.target.result;
                    previewImg.classList.remove('hidden');
                    defaultPrompt.classList.add('hidden');
                }
                reader.readAsDataURL(uploadedFile);

                statusMsg.style.color = "#34d399";
                statusMsg.innerText = "Selected: " + uploadedFile.name;
            }
        });

        async function uploadHeroImage(event) {
            event.preventDefault();
            const statusMsg = document.getElementById('statusMsg');

            if (!uploadedFile) {
                statusMsg.style.color = "#f87171";
                statusMsg.innerText = "Kripya pehle ek image file select karein!";
                return;
            }

            // Manually FormData create karke 'heroImage' key bind kar rahe hain
            const formData = new FormData();
            formData.append("heroImage", uploadedFile);

            statusMsg.style.color = "#34d399";
            statusMsg.innerText = "Uploading image and updating database...";

            try {
                const response = await fetch("api.php?action=update_hero_image", {
                    method: "POST",
                    headers: {
                        "X-API-KEY": "GatewayLinen@2026"
                    },
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    statusMsg.style.color = "#34d399";
                    statusMsg.innerText = "✔ " + result.message;
                } else {
                    statusMsg.style.color = "#f87171";
                    statusMsg.innerText = "Error: " + result.message;
                }
            } catch (error) {
                console.error("Error:", error);
                statusMsg.style.color = "#f87171";
                statusMsg.innerText = "Server connection fail ho gaya!";
            }
        }
    </script>

</body>

</html>