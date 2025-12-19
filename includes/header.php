<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Inventaris Barang</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-blue-600 text-white shadow-lg">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <i class="fas fa-boxes text-2xl"></i>
                    <h1 class="text-xl font-bold">Sistem Inventaris</h1>
                </div>
                <div class="flex space-x-6">
                    <a href="index.php" class="hover:bg-blue-700 px-3 py-2 rounded transition duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-blue-700' : ''; ?>">
                        <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                    </a>
                    <a href="barang.php" class="hover:bg-blue-700 px-3 py-2 rounded transition duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'barang.php' ? 'bg-blue-700' : ''; ?>">
                        <i class="fas fa-box mr-2"></i>Barang
                    </a>
                    <a href="supplier.php" class="hover:bg-blue-700 px-3 py-2 rounded transition duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'supplier.php' ? 'bg-blue-700' : ''; ?>">
                        <i class="fas fa-truck mr-2"></i>Supplier
                    </a>
                    <a href="barang_masuk.php" class="hover:bg-blue-700 px-3 py-2 rounded transition duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'barang_masuk.php' ? 'bg-blue-700' : ''; ?>">
                        <i class="fas fa-sign-in-alt mr-2"></i>Barang Masuk
                    </a>
                    <a href="barang_keluar.php" class="hover:bg-blue-700 px-3 py-2 rounded transition duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'barang_keluar.php' ? 'bg-blue-700' : ''; ?>">
                        <i class="fas fa-sign-out-alt mr-2"></i>Barang Keluar
                    </a>
                    <a href="laporan.php" class="hover:bg-blue-700 px-3 py-2 rounded transition duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'bg-blue-700' : ''; ?>">
                        <i class="fas fa-chart-bar mr-2"></i>Laporan
                    </a>
                </div>
                <div class="relative">
                    <button id="userMenuBtn" class="bg-blue-700 px-3 py-2 rounded hover:bg-blue-800 transition duration-200 flex items-center">
                        <i class="fas fa-user mr-2"></i>Admin
                        <i class="fas fa-chevron-down ml-2"></i>
                    </button>
                    <div id="userMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50 hidden">
                        <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-6">