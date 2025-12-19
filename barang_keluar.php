<?php
include 'config.php';
include 'includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $no_transaksi_keluar = $_POST['no_transaksi_keluar'];
    $tanggal = $_POST['tanggal'];
    $penerima = $_POST['penerima'];
    $kode_barang = $_POST['kode_barang'];
    $jumlah = $_POST['jumlah'];
    $keterangan = $_POST['keterangan'];

    // Check stock availability
    $stok_result = $conn->query("SELECT stok FROM barang WHERE kode_barang = '$kode_barang'");
    $stok = $stok_result->fetch_assoc()['stok'];
    
    if ($stok < $jumlah) {
        $error = "Stok tidak mencukupi! Stok tersedia: $stok";
    } else {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Insert into barang_keluar table
            $stmt = $conn->prepare("INSERT INTO barang_keluar (no_transaksi_keluar, tanggal, penerima) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $no_transaksi_keluar, $tanggal, $penerima);
            $stmt->execute();
            $stmt->close();

            // Insert into detail_barang_keluar table
            $stmt = $conn->prepare("INSERT INTO detail_barang_keluar (no_transaksi_keluar, kode_barang, jumlah, keterangan) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssis", $no_transaksi_keluar, $kode_barang, $jumlah, $keterangan);
            $stmt->execute();
            $stmt->close();

            // Commit transaction
            $conn->commit();

            $_SESSION['flash_message'] = "Transaksi barang keluar berhasil disimpan!";
            header("Location: barang_keluar.php");
            exit();
        } catch (Exception $e) {
            // Rollback transaction if error occurs
            $conn->rollback();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $no_transaksi = $_GET['delete'];
    
    $conn->begin_transaction();
    try {
        // Delete from detail_barang_keluar first
        $stmt = $conn->prepare("DELETE FROM detail_barang_keluar WHERE no_transaksi_keluar = ?");
        $stmt->bind_param("s", $no_transaksi);
        $stmt->execute();
        $stmt->close();

        // Then delete from barang_keluar
        $stmt = $conn->prepare("DELETE FROM barang_keluar WHERE no_transaksi_keluar = ?");
        $stmt->bind_param("s", $no_transaksi);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        $_SESSION['flash_message'] = "Transaksi barang keluar berhasil dihapus!";
        header("Location: barang_keluar.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error: " . $e->getMessage();
    }
}

// Generate automatic transaction number
function generateNoTransaksiKeluar($conn) {
    $prefix = "TRX-K-";
    $date = date("Ymd");
    
    $result = $conn->query("SELECT no_transaksi_keluar FROM barang_keluar WHERE no_transaksi_keluar LIKE '$prefix$date-%' ORDER BY no_transaksi_keluar DESC LIMIT 1");
    
    if ($result->num_rows > 0) {
        $last_no = $result->fetch_assoc()['no_transaksi_keluar'];
        $last_seq = intval(substr($last_no, -3));
        $new_seq = str_pad($last_seq + 1, 3, '0', STR_PAD_LEFT);
    } else {
        $new_seq = "001";
    }
    
    return $prefix . $date . "-" . $new_seq;
}

// Get all barang keluar with details
$barang_keluar = $conn->query("
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
    ORDER BY bk.tanggal DESC, bk.no_transaksi_keluar DESC
");

// Get barang for dropdown
$barang_list = $conn->query("SELECT kode_barang, nama_barang, stok FROM barang ORDER BY nama_barang");
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-sign-out-alt mr-2"></i>Barang Keluar
    </h2>
    <button onclick="openModal('tambahBarangKeluarModal')" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition duration-200">
        <i class="fas fa-plus mr-2"></i>Tambah Barang Keluar
    </button>
</div>

<?php if (isset($error)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 text-red-600">
                <i class="fas fa-cubes text-2xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-500">Total Transaksi</h3>
                <p class="text-2xl font-bold text-gray-900"><?php echo $barang_keluar->num_rows; ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                <i class="fas fa-boxes text-2xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-500">Total Item Keluar</h3>
                <p class="text-2xl font-bold text-gray-900">
                    <?php 
                    $total_item = $conn->query("SELECT SUM(jumlah) as total FROM detail_barang_keluar")->fetch_assoc()['total'];
                    echo $total_item ?: '0';
                    ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($barang_keluar->num_rows > 0): ?>
                    <?php while ($row = $barang_keluar->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo $row['no_transaksi_keluar']; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo date('d/m/Y', strtotime($row['tanggal'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo $row['penerima']; ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <?php echo $row['nama_barang']; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-medium rounded">
                                <?php echo $row['jumlah']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <?php echo $row['keterangan'] ?: '-'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="?delete=<?php echo $row['no_transaksi_keluar']; ?>" 
                               onclick="return confirmDelete('Hapus transaksi <?php echo $row['no_transaksi_keluar']; ?>?')" 
                               class="text-red-600 hover:text-red-900">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                            <div class="flex flex-col items-center justify-center py-8">
                                <i class="fas fa-inbox text-gray-300 text-4xl mb-2"></i>
                                <p>Belum ada data barang keluar</p>
                                <p class="text-sm text-gray-400">Klik tombol "Tambah Barang Keluar" untuk menambah data</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Barang Keluar -->
<div id="tambahBarangKeluarModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-sign-out-alt mr-2"></i>Tambah Barang Keluar
            </h3>
            <button onclick="closeModal('tambahBarangKeluarModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="" id="barangKeluarForm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">No. Transaksi</label>
                    <input type="text" name="no_transaksi_keluar" id="no_transaksi_keluar" 
                           value="<?php echo generateNoTransaksiKeluar($conn); ?>"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500 bg-gray-100" 
                           readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tanggal</label>
                    <input type="date" name="tanggal" value="<?php echo date('Y-m-d'); ?>" 
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500" 
                           required>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Penerima</label>
                    <input type="text" name="penerima" placeholder="Masukkan nama penerima/departemen" 
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500" 
                           required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Barang</label>
                    <select name="kode_barang" id="kode_barang_keluar" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500" required>
                        <option value="">Pilih Barang</option>
                        <?php 
                        $barang_list2 = $conn->query("SELECT kode_barang, nama_barang, stok FROM barang ORDER BY nama_barang");
                        while ($barang = $barang_list2->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $barang['kode_barang']; ?>" data-stok="<?php echo $barang['stok']; ?>">
                                <?php echo $barang['nama_barang']; ?> (Stok: <?php echo $barang['stok']; ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <div id="stokInfoKeluar" class="mt-1 text-sm text-gray-500"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Jumlah</label>
                        <input type="number" name="jumlah" id="jumlah_keluar" min="1" 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500" 
                               required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Keterangan</label>
                        <input type="text" name="keterangan" placeholder="Alasan pengeluaran barang" 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500">
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeModal('tambahBarangKeluarModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition duration-200">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md transition duration-200">
                    <i class="fas fa-save mr-2"></i>Simpan Transaksi
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Update stock info when barang is selected
document.getElementById('kode_barang_keluar').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const stok = selectedOption.getAttribute('data-stok');
    const stokInfo = document.getElementById('stokInfoKeluar');
    
    if (stok) {
        stokInfo.textContent = `Stok tersedia: ${stok}`;
        stokInfo.className = 'mt-1 text-sm text-blue-600';
        
        // Set max value for jumlah input
        document.getElementById('jumlah_keluar').max = stok;
    } else {
        stokInfo.textContent = '';
    }
});

// Form validation
document.getElementById('barangKeluarForm').addEventListener('submit', function(e) {
    const jumlah = parseInt(document.getElementById('jumlah_keluar').value);
    const maxJumlah = parseInt(document.getElementById('jumlah_keluar').max);
    
    if (jumlah <= 0) {
        alert('Jumlah harus lebih dari 0');
        e.preventDefault();
        return;
    }
    
    if (jumlah > maxJumlah) {
        alert(`Jumlah melebihi stok tersedia! Stok tersedia: ${maxJumlah}`);
        e.preventDefault();
        return;
    }
});
</script>

<?php include 'includes/footer.php'; ?>