<?php
include 'config.php';
include 'includes/header.php';

// Set default date range (last 6 months)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-6 months'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get filter values
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'stok';

// Get statistics for dashboard
$total_barang = $conn->query("SELECT COUNT(*) as total FROM barang")->fetch_assoc()['total'];
$total_supplier = $conn->query("SELECT COUNT(*) as total FROM supplier")->fetch_assoc()['total'];
$barang_masuk_count = $conn->query("SELECT COUNT(DISTINCT no_transaksi_masuk) as total FROM barang_masuk WHERE tanggal BETWEEN '$start_date' AND '$end_date'")->fetch_assoc()['total'];
$barang_keluar_count = $conn->query("SELECT COUNT(DISTINCT no_transaksi_keluar) as total FROM barang_keluar WHERE tanggal BETWEEN '$start_date' AND '$end_date'")->fetch_assoc()['total'];

// Get low stock items
$low_stock = $conn->query("SELECT * FROM barang WHERE stok <= stok_minimum ORDER BY stok ASC");

// Get stock report
if ($report_type == 'stok_tanggal') {
    $stok_report = $conn->query("
        SELECT 
            b.kode_barang,
            b.nama_barang,
            b.deskripsi,
            b.satuan,
            b.stok,
            b.stok_minimum,
            b.updated_at,
            CASE 
                WHEN b.stok <= b.stok_minimum THEN 'PERLU RESTOCK'
                ELSE 'AMAN'
            END AS status_stok
        FROM barang b
        WHERE b.updated_at <= '$end_date'
        ORDER BY b.stok ASC
    ");
} elseif ($report_type == 'stok') {
    $stok_report = $conn->query("
        SELECT DISTINCT
            b.kode_barang,
            b.nama_barang,
            b.deskripsi,
            b.satuan,
            b.stok,
            b.stok_minimum,
            b.updated_at,
            CASE 
                WHEN b.stok <= b.stok_minimum THEN 'PERLU RESTOCK'
                ELSE 'AMAN'
            END AS status_stok
        FROM barang b
        WHERE b.kode_barang IN (
            SELECT DISTINCT kode_barang FROM detail_barang_masuk dbm
            INNER JOIN barang_masuk bm ON dbm.no_transaksi_masuk = bm.no_transaksi_masuk
            WHERE bm.tanggal BETWEEN '$start_date' AND '$end_date'
        ) OR b.kode_barang IN (
            SELECT DISTINCT kode_barang FROM detail_barang_keluar dbk
            INNER JOIN barang_keluar bk ON dbk.no_transaksi_keluar = bk.no_transaksi_keluar
            WHERE bk.tanggal BETWEEN '$start_date' AND '$end_date'
        )
        ORDER BY b.stok ASC
    ");
} else {
    $stok_report = $conn->query("
        SELECT 
            b.kode_barang,
            b.nama_barang,
            b.deskripsi,
            b.satuan,
            b.stok,
            b.stok_minimum,
            b.updated_at,
            CASE 
                WHEN b.stok <= b.stok_minimum THEN 'PERLU RESTOCK'
                ELSE 'AMAN'
            END AS status_stok
        FROM barang b
        ORDER BY b.stok ASC
    ");
}

// Get barang masuk report
$barang_masuk_report = $conn->query("
    SELECT 
        bm.no_transaksi_masuk,
        bm.tanggal,
        s.nama_supplier,
        b.nama_barang,
        dbm.jumlah,
        dbm.harga_beli,
        (dbm.jumlah * dbm.harga_beli) as total_harga
    FROM barang_masuk bm
    LEFT JOIN supplier s ON bm.id_supplier = s.id_supplier
    JOIN detail_barang_masuk dbm ON bm.no_transaksi_masuk = dbm.no_transaksi_masuk
    JOIN barang b ON dbm.kode_barang = b.kode_barang
    WHERE bm.tanggal BETWEEN '$start_date' AND '$end_date'
    ORDER BY bm.tanggal DESC, bm.no_transaksi_masuk DESC
");

if (!$barang_masuk_report) {
    die("Error in barang_masuk query: " . $conn->error);
}

// Get barang keluar report
$barang_keluar_report = $conn->query("
    SELECT 
        bk.no_transaksi_keluar,
        bk.tanggal,
        bk.penerima,
        b.nama_barang,
        dbk.jumlah,
        dbk.keterangan
    FROM barang_keluar bk
    JOIN detail_barang_keluar dbk ON bk.no_transaksi_keluar = dbk.no_transaksi_keluar
    JOIN barang b ON dbk.kode_barang = b.kode_barang
    WHERE bk.tanggal BETWEEN '$start_date' AND '$end_date'
    ORDER BY bk.tanggal DESC, bk.no_transaksi_keluar DESC
");

if (!$barang_keluar_report) {
    die("Error in barang_keluar query: " . $conn->error);
}

// Get summary statistics
$summary_masuk = $conn->query("
    SELECT 
        COUNT(DISTINCT bm.no_transaksi_masuk) as total_transaksi,
        SUM(dbm.jumlah) as total_item,
        SUM(dbm.jumlah * dbm.harga_beli) as total_nilai
    FROM barang_masuk bm
    JOIN detail_barang_masuk dbm ON bm.no_transaksi_masuk = dbm.no_transaksi_masuk
    WHERE bm.tanggal BETWEEN '$start_date' AND '$end_date'
")->fetch_assoc();

$summary_keluar = $conn->query("
    SELECT 
        COUNT(DISTINCT bk.no_transaksi_keluar) as total_transaksi,
        SUM(dbk.jumlah) as total_item
    FROM barang_keluar bk
    JOIN detail_barang_keluar dbk ON bk.no_transaksi_keluar = dbk.no_transaksi_keluar
    WHERE bk.tanggal BETWEEN '$start_date' AND '$end_date'
")->fetch_assoc();
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-chart-bar mr-2"></i>Laporan & Analisis
    </h2>
    <div class="flex space-x-2">
        <button onclick="printReport()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">
            <i class="fas fa-print mr-2"></i>Cetak Laporan
        </button>
        <button onclick="exportToExcel()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition duration-200">
            <i class="fas fa-file-excel mr-2"></i>Export Excel
        </button>
    </div>
</div>

<!-- Filter Form -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">
        <i class="fas fa-filter mr-2"></i>Filter Laporan
    </h3>
    <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Jenis Laporan</label>
            <select name="report_type" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="stok" <?php echo $report_type == 'stok' ? 'selected' : ''; ?>>Laporan Stok</option>
                <option value="stok_tanggal" <?php echo $report_type == 'stok_tanggal' ? 'selected' : ''; ?>>Stok per Tanggal</option>
                <option value="masuk" <?php echo $report_type == 'masuk' ? 'selected' : ''; ?>>Barang Masuk</option>
                <option value="keluar" <?php echo $report_type == 'keluar' ? 'selected' : ''; ?>>Barang Keluar</option>
                <option value="summary" <?php echo $report_type == 'summary' ? 'selected' : ''; ?>>Ringkasan</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Tanggal Mulai</label>
            <input type="date" name="start_date" value="<?php echo $start_date; ?>" 
                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Tanggal Akhir</label>
            <input type="date" name="end_date" value="<?php echo $end_date; ?>" 
                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition duration-200">
                <i class="fas fa-search mr-2"></i>Terapkan Filter
            </button>
        </div>
    </form>
    <div class="mt-2 text-sm text-gray-600">
        Filter aktif: 
        <?php 
        if ($report_type == 'stok_tanggal') {
            echo 'Stok per Tanggal (hingga ' . date('d M Y', strtotime($end_date)) . ')';
        } elseif ($report_type == 'stok') {
            echo 'Laporan Stok (' . date('d M Y', strtotime($start_date)) . ' - ' . date('d M Y', strtotime($end_date)) . ')';
        } else {
            echo ucfirst($report_type) . ' (' . date('d M Y', strtotime($start_date)) . ' - ' . date('d M Y', strtotime($end_date)) . ')';
        }
        ?>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
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
                <h3 class="text-sm font-medium text-gray-500">Transaksi Masuk</h3>
                <p class="text-2xl font-bold text-gray-900"><?php echo $barang_masuk_count; ?></p>
                <p class="text-xs text-gray-500">Periode: <?php echo date('d M Y', strtotime($start_date)); ?> - <?php echo date('d M Y', strtotime($end_date)); ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 text-red-600">
                <i class="fas fa-sign-out-alt text-2xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-500">Transaksi Keluar</h3>
                <p class="text-2xl font-bold text-gray-900"><?php echo $barang_keluar_count; ?></p>
                <p class="text-xs text-gray-500">Periode: <?php echo date('d M Y', strtotime($start_date)); ?> - <?php echo date('d M Y', strtotime($end_date)); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Report Content -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <?php if ($report_type == 'stok' || $report_type == 'stok_tanggal'): ?>
        <!-- Stock Report -->
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-boxes mr-2"></i><?php echo $report_type == 'stok_tanggal' ? 'Stok per Tanggal' : 'Laporan Stok Barang'; ?>
            </h3>
            <p class="text-sm text-gray-600">
                <?php if ($report_type == 'stok_tanggal'): ?>
                    Data stok barang per <?php echo date('d M Y', strtotime($end_date)); ?>
                <?php elseif ($report_type == 'stok'): ?>
                    Barang dengan transaksi <?php echo date('d M Y', strtotime($start_date)); ?> - <?php echo date('d M Y', strtotime($end_date)); ?>
                <?php else: ?>
                    Data stok barang terbaru
                <?php endif; ?>
            </p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode Barang</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Barang</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Satuan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok Min.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Terakhir Diperbarui</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if ($stok_report->num_rows > 0): ?>
                        <?php while ($row = $stok_report->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $row['kode_barang']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $row['nama_barang']; ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo $row['deskripsi']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $row['satuan']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span class="px-2 py-1 text-xs rounded-full <?php echo $row['stok'] <= $row['stok_minimum'] ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>">
                                    <?php echo $row['stok']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $row['stok_minimum']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo date('d/m/Y H:i', strtotime($row['updated_at'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <span class="px-2 py-1 text-xs rounded-full <?php echo $row['status_stok'] == 'PERLU RESTOCK' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>">
                                    <?php echo $row['status_stok']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                                Tidak ada data stok
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($report_type == 'masuk'): ?>
        <!-- Barang Masuk Report -->
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-sign-in-alt mr-2"></i>Laporan Barang Masuk
            </h3>
            <p class="text-sm text-gray-600">
                Periode: <?php echo date('d M Y', strtotime($start_date)); ?> - <?php echo date('d M Y', strtotime($end_date)); ?>
            </p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Transaksi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Barang</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Beli</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if ($barang_masuk_report->num_rows > 0): ?>
                        <?php while ($row = $barang_masuk_report->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $row['no_transaksi_masuk']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $row['nama_supplier']; ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo $row['nama_barang']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $row['jumlah']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Rp <?php echo number_format($row['harga_beli'], 0, ',', '.'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Rp <?php echo number_format($row['total_harga'], 0, ',', '.'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                        <!-- Summary Row -->
                        <tr class="bg-gray-50 font-semibold">
                            <td colspan="4" class="px-6 py-4 text-right text-sm text-gray-900">Total:</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $summary_masuk['total_item'] ?: '0'; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">-</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Rp <?php echo number_format($summary_masuk['total_nilai'] ?: 0, 0, ',', '.'); ?></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                Tidak ada data barang masuk pada periode yang dipilih
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($report_type == 'keluar'): ?>
        <!-- Barang Keluar Report -->
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-sign-out-alt mr-2"></i>Laporan Barang Keluar
            </h3>
            <p class="text-sm text-gray-600">
                Periode: <?php echo date('d M Y', strtotime($start_date)); ?> - <?php echo date('d M Y', strtotime($end_date)); ?>
            </p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Transaksi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Penerima</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Barang</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if ($barang_keluar_report->num_rows > 0): ?>
                        <?php while ($row = $barang_keluar_report->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $row['no_transaksi_keluar']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $row['penerima']; ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo $row['nama_barang']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-medium rounded"><?php echo $row['jumlah']; ?></span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo $row['keterangan'] ?: '-'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                        <!-- Summary Row -->
                        <tr class="bg-gray-50 font-semibold">
                            <td colspan="4" class="px-6 py-4 text-right text-sm text-gray-900">Total:</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $summary_keluar['total_item'] ?: '0'; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">-</td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                Tidak ada data barang keluar pada periode yang dipilih
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($report_type == 'summary'): ?>
        <!-- Summary Report -->
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-chart-pie mr-2"></i>Ringkasan Laporan
            </h3>
            <p class="text-sm text-gray-600">
                Periode: <?php echo date('d M Y', strtotime($start_date)); ?> - <?php echo date('d M Y', strtotime($end_date)); ?>
            </p>
        </div>
        <div class="p-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-blue-50 rounded-lg p-6 border border-blue-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-sign-in-alt text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-blue-600">Transaksi Masuk</h3>
                            <p class="text-2xl font-bold text-blue-900"><?php echo $summary_masuk['total_transaksi'] ?: '0'; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-red-50 rounded-lg p-6 border border-red-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 text-red-600">
                            <i class="fas fa-sign-out-alt text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-red-600">Transaksi Keluar</h3>
                            <p class="text-2xl font-bold text-red-900"><?php echo $summary_keluar['total_transaksi'] ?: '0'; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-green-50 rounded-lg p-6 border border-green-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-boxes text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-green-600">Item Masuk</h3>
                            <p class="text-2xl font-bold text-green-900"><?php echo $summary_masuk['total_item'] ?: '0'; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-purple-50 rounded-lg p-6 border border-purple-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <i class="fas fa-money-bill-wave text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-purple-600">Nilai Masuk</h3>
                            <p class="text-2xl font-bold text-purple-900">Rp <?php echo number_format($summary_masuk['total_nilai'] ?: 0, 0, ',', '.'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Low Stock Alert -->
            <div class="bg-white border border-yellow-200 rounded-lg p-6 mb-6">
                <h4 class="text-lg font-semibold text-yellow-800 mb-4">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Peringatan Stok Menipis
                </h4>
                <?php if ($low_stock->num_rows > 0): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php while ($item = $low_stock->fetch_assoc()): ?>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h5 class="font-medium text-yellow-800"><?php echo $item['nama_barang']; ?></h5>
                                        <p class="text-sm text-yellow-600">Stok: <?php echo $item['stok']; ?> <?php echo $item['satuan']; ?></p>
                                        <p class="text-sm text-yellow-600">Minimal: <?php echo $item['stok_minimum']; ?> <?php echo $item['satuan']; ?></p>
                                    </div>
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-medium rounded">Perlu Restock</span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-4">Tidak ada barang dengan stok menipis</p>
                <?php endif; ?>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gray-50 rounded-lg p-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Statistik Cepat</h4>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Total Barang di Sistem</span>
                            <span class="font-medium text-gray-900"><?php echo $total_barang; ?> barang</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Total Supplier</span>
                            <span class="font-medium text-gray-900"><?php echo $total_supplier; ?> supplier</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Rata-rata Item per Transaksi Masuk</span>
                            <span class="font-medium text-gray-900">
                                <?php 
                                $avg = $summary_masuk['total_transaksi'] > 0 ? $summary_masuk['total_item'] / $summary_masuk['total_transaksi'] : 0;
                                echo number_format($avg, 1);
                                ?> item
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-lg p-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Periode Laporan</h4>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Tanggal Mulai</span>
                            <span class="font-medium text-gray-900"><?php echo date('d M Y', strtotime($start_date)); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Tanggal Akhir</span>
                            <span class="font-medium text-gray-900"><?php echo date('d M Y', strtotime($end_date)); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Durasi</span>
                            <span class="font-medium text-gray-900">
                                <?php 
                                $diff = strtotime($end_date) - strtotime($start_date);
                                echo floor($diff / (60 * 60 * 24)) + 1; 
                                ?> hari
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Print and Export Functions -->
<script>
function printReport() {
    const printContent = document.querySelector('.bg-white.rounded-lg.shadow.overflow-hidden').outerHTML;
    const originalContent = document.body.innerHTML;
    
    document.body.innerHTML = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Laporan Inventaris - <?php echo ucfirst($report_type); ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f8f9fa; }
                .header { text-align: center; margin-bottom: 20px; }
                .summary { margin: 20px 0; padding: 15px; background-color: #f8f9fa; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Laporan Sistem Inventaris</h1>
                <h2>Laporan <?php echo ucfirst($report_type); ?></h2>
                <p>Periode: <?php echo date('d M Y', strtotime($start_date)); ?> - <?php echo date('d M Y', strtotime($end_date)); ?></p>
                <p>Dicetak pada: <?php echo date('d M Y H:i:s'); ?></p>
            </div>
            ${printContent}
        </body>
        </html>
    `;
    
    window.print();
    document.body.innerHTML = originalContent;
    window.location.reload();
}

function exportToExcel() {
    // Simple CSV export
    let csv = [];
    let rows = document.querySelectorAll('table tr');
    
    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
            let text = cols[j].innerText.replace(/,/g, '');
            row.push(text);
        }
        
        csv.push(row.join(','));
    }

    // Download CSV file
    let csvFile = new Blob([csv.join('\n')], {type: 'text/csv'});
    let downloadLink = document.createElement('a');
    downloadLink.download = `laporan_<?php echo $report_type; ?>_<?php echo date('Y-m-d'); ?>.csv`;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = 'none';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>

<?php include 'includes/footer.php'; ?>