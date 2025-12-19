<?php
include 'config.php';
include 'includes/header.php';

// Get statistics
$total_barang = $conn->query("SELECT COUNT(*) as total FROM barang")->fetch_assoc()['total'];
$total_supplier = $conn->query("SELECT COUNT(*) as total FROM supplier")->fetch_assoc()['total'];
$total_masuk = $conn->query("SELECT SUM(jumlah) as total FROM detail_barang_masuk")->fetch_assoc()['total'];
$total_keluar = $conn->query("SELECT SUM(jumlah) as total FROM detail_barang_keluar")->fetch_assoc()['total'];

// Get low stock items
$low_stock = $conn->query("SELECT * FROM barang WHERE stok <= stok_minimum LIMIT 5");
if (!$low_stock) {
    $low_stock = $conn->query("SELECT 'Error' as nama_barang, 0 as stok, 'pcs' as satuan LIMIT 0");
}

// Get recent activities
$activities_query = "
    SELECT 'masuk' as type, bm.tanggal, CONCAT('Barang masuk dari ', s.nama_supplier) as deskripsi, bm.tanggal as tanggal
    FROM barang_masuk bm
    LEFT JOIN supplier s ON bm.id_supplier = s.id
    UNION ALL
    SELECT 'keluar' as type, bk.tanggal, CONCAT('Barang keluar ke ', bk.penerima) as deskripsi, bk.tanggal as tanggal
    FROM barang_keluar bk
    ORDER BY tanggal DESC LIMIT 5
";
$activities = $conn->query($activities_query);
if (!$activities) {
    // Handle error, for now create empty result
    $activities = $conn->query("SELECT 'error' as type, '2023-01-01' as tanggal, 'Tidak dapat memuat aktivitas' as deskripsi, '2023-01-01' as tanggal LIMIT 0");
}

// Function to get relative time
function getRelativeTime($timestamp) {
    $now = time();
    $time = strtotime($timestamp);
    $diff = $now - $time;
    
    if ($diff < 60) return 'baru saja';
    if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    if ($diff < 604800) return floor($diff / 86400) . ' hari lalu';
    return date('d/m/Y', $time);
}
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Stat Cards -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                <i class="fas fa-box text-2xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-500">Total Barang</h3>
                <p class="text-2xl font-bold text-gray-900"><?php echo $total_barang; ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <i class="fas fa-truck text-2xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-500">Total Supplier</h3>
                <p class="text-2xl font-bold text-gray-900"><?php echo $total_supplier; ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                <i class="fas fa-sign-in-alt text-2xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-500">Barang Masuk</h3>
                <p class="text-2xl font-bold text-gray-900"><?php echo $total_masuk ?: '0'; ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 text-red-600">
                <i class="fas fa-sign-out-alt text-2xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-500">Barang Keluar</h3>
                <p class="text-2xl font-bold text-gray-900"><?php echo $total_keluar ?: '0'; ?></p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Low Stock Warning -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                Stok Menipis
            </h2>
        </div>
        <div class="p-6">
            <?php if ($low_stock->num_rows > 0): ?>
                <div class="space-y-4">
                    <?php while ($item = $low_stock->fetch_assoc()): ?>
                        <div class="flex justify-between items-center p-3 bg-yellow-50 rounded-lg">
                            <div>
                                <h4 class="font-medium text-gray-800"><?php echo $item['nama_barang']; ?></h4>
                                <p class="text-sm text-gray-600">Stok: <?php echo $item['stok']; ?> <?php echo $item['satuan']; ?></p>
                            </div>
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-medium rounded">Perlu Restock</span>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-center py-4">Tidak ada barang dengan stok menipis</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-history mr-2"></i>
                Aktivitas Terbaru
            </h2>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <?php if ($activities->num_rows > 0): ?>
                    <?php while ($activity = $activities->fetch_assoc()): ?>
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-<?php echo $activity['type'] == 'masuk' ? 'sign-in-alt text-green-500' : 'sign-out-alt text-red-500'; ?> mr-3"></i>
                            <span><?php echo $activity['deskripsi']; ?></span>
                            <span class="ml-auto text-gray-400"><?php echo getRelativeTime($activity['tanggal']); ?></span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-4">Tidak ada aktivitas terbaru</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>