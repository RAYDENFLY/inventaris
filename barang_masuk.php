<?php
include 'config.php';
include 'includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $no_transaksi_masuk = $_POST['no_transaksi_masuk'];
    $tanggal = $_POST['tanggal'];
    $id_supplier = $_POST['id_supplier'];
    $kode_barang = $_POST['kode_barang'];
    $jumlah = $_POST['jumlah'];
    $harga_beli = $_POST['harga_beli'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert into barang_masuk table
        $stmt = $conn->prepare("INSERT INTO barang_masuk (no_transaksi_masuk, tanggal, id_supplier) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $no_transaksi_masuk, $tanggal, $id_supplier);
        $stmt->execute();
        $stmt->close();

        // Insert into detail_barang_masuk table
        $stmt = $conn->prepare("INSERT INTO detail_barang_masuk (no_transaksi_masuk, kode_barang, jumlah, harga_beli) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssid", $no_transaksi_masuk, $kode_barang, $jumlah, $harga_beli);
        $stmt->execute();
        $stmt->close();

        // Commit transaction
        $conn->commit();

        $_SESSION['flash_message'] = "Transaksi barang masuk berhasil disimpan!";
        header("Location: barang_masuk.php");
        exit();
    } catch (Exception $e) {
        // Rollback transaction if error occurs
        $conn->rollback();
        $error = "Error: " . $e->getMessage();
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $no_transaksi = $_GET['delete'];
    
    $conn->begin_transaction();
    try {
        // Delete from detail_barang_masuk first
        $stmt = $conn->prepare("DELETE FROM detail_barang_masuk WHERE no_transaksi_masuk = ?");
        $stmt->bind_param("s", $no_transaksi);
        $stmt->execute();
        $stmt->close();

        // Then delete from barang_masuk
        $stmt = $conn->prepare("DELETE FROM barang_masuk WHERE no_transaksi_masuk = ?");
        $stmt->bind_param("s", $no_transaksi);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        $_SESSION['flash_message'] = "Transaksi barang masuk berhasil dihapus!";
        header("Location: barang_masuk.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error: " . $e->getMessage();
    }
}

// Generate automatic transaction number
function generateNoTransaksi($conn) {
    $prefix = "TRX-M-";
    $date = date("Ymd");
    
    $result = $conn->query("SELECT no_transaksi_masuk FROM barang_masuk WHERE no_transaksi_masuk LIKE '$prefix$date-%' ORDER BY no_transaksi_masuk DESC LIMIT 1");
    
    if ($result->num_rows > 0) {
        $last_no = $result->fetch_assoc()['no_transaksi_masuk'];
        $last_seq = intval(substr($last_no, -3));
        $new_seq = str_pad($last_seq + 1, 3, '0', STR_PAD_LEFT);
    } else {
        $new_seq = "001";
    }
    
    return $prefix . $date . "-" . $new_seq;
}

// Get all barang masuk with details
$barang_masuk = $conn->query("
    SELECT 
        bm.no_transaksi_masuk,
        bm.tanggal,
        s.nama_supplier,
        b.nama_barang,
        dbm.jumlah,
        dbm.harga_beli,
        (dbm.jumlah * dbm.harga_beli) as total_harga
    FROM barang_masuk bm
    JOIN supplier s ON bm.id_supplier = s.id_supplier
    JOIN detail_barang_masuk dbm ON bm.no_transaksi_masuk = dbm.no_transaksi_masuk
    JOIN barang b ON dbm.kode_barang = b.kode_barang
    ORDER BY bm.tanggal DESC, bm.no_transaksi_masuk DESC
");

// Get suppliers for dropdown
$suppliers = $conn->query("SELECT id_supplier, nama_supplier FROM supplier ORDER BY nama_supplier");

// Get barang for dropdown
$barang_list = $conn->query("SELECT kode_barang, nama_barang, stok FROM barang ORDER BY nama_barang");
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-sign-in-alt mr-2"></i>Barang Masuk
    </h2>
    <button onclick="openModal('tambahBarangMasukModal')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition duration-200">
        <i class="fas fa-plus mr-2"></i>Tambah Barang Masuk
    </button>
</div>

<?php if (isset($error)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <i class="fas fa-cubes text-2xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-500">Total Transaksi</h3>
                <p class="text-2xl font-bold text-gray-900"><?php echo $barang_masuk->num_rows; ?></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                <i class="fas fa-boxes text-2xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-500">Total Item Masuk</h3>
                <p class="text-2xl font-bold text-gray-900">
                    <?php 
                    $total_item = $conn->query("SELECT SUM(jumlah) as total FROM detail_barang_masuk")->fetch_assoc()['total'];
                    echo $total_item ?: '0';
                    ?>
                </p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                <i class="fas fa-money-bill-wave text-2xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-500">Total Nilai</h3>
                <p class="text-2xl font-bold text-gray-900">
                    Rp <?php 
                    $total_nilai = $conn->query("SELECT SUM(jumlah * harga_beli) as total FROM detail_barang_masuk")->fetch_assoc()['total'];
                    echo number_format($total_nilai ?: 0, 0, ',', '.');
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Barang</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga Beli</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($barang_masuk->num_rows > 0): ?>
                    <?php while ($row = $barang_masuk->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo $row['no_transaksi_masuk']; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo date('d/m/Y', strtotime($row['tanggal'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo $row['nama_supplier']; ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <?php echo $row['nama_barang']; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo $row['jumlah']; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            Rp <?php echo number_format($row['harga_beli'], 0, ',', '.'); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            Rp <?php echo number_format($row['total_harga'], 0, ',', '.'); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="?delete=<?php echo $row['no_transaksi_masuk']; ?>" 
                               onclick="return confirmDelete('Hapus transaksi <?php echo $row['no_transaksi_masuk']; ?>?')" 
                               class="text-red-600 hover:text-red-900">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                            <div class="flex flex-col items-center justify-center py-8">
                                <i class="fas fa-inbox text-gray-300 text-4xl mb-2"></i>
                                <p>Belum ada data barang masuk</p>
                                <p class="text-sm text-gray-400">Klik tombol "Tambah Barang Masuk" untuk menambah data</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Barang Masuk -->
<div id="tambahBarangMasukModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-sign-in-alt mr-2"></i>Tambah Barang Masuk
            </h3>
            <button onclick="closeModal('tambahBarangMasukModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="" id="barangMasukForm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">No. Transaksi</label>
                    <input type="text" name="no_transaksi_masuk" id="no_transaksi_masuk" 
                           value="<?php echo generateNoTransaksi($conn); ?>"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 bg-gray-100" 
                           readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tanggal</label>
                    <input type="date" name="tanggal" value="<?php echo date('Y-m-d'); ?>" 
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500" 
                           required>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Supplier</label>
                    <select name="id_supplier" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500" required>
                        <option value="">Pilih Supplier</option>
                        <?php while ($supplier = $suppliers->fetch_assoc()): ?>
                            <option value="<?php echo $supplier['id_supplier']; ?>">
                                <?php echo $supplier['nama_supplier']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Barang</label>
                    <select name="kode_barang" id="kode_barang" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500" required>
                        <option value="">Pilih Barang</option>
                        <?php while ($barang = $barang_list->fetch_assoc()): ?>
                            <option value="<?php echo $barang['kode_barang']; ?>" data-stok="<?php echo $barang['stok']; ?>">
                                <?php echo $barang['nama_barang']; ?> (Stok: <?php echo $barang['stok']; ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <div id="stokInfo" class="mt-1 text-sm text-gray-500"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Jumlah</label>
                        <input type="number" name="jumlah" id="jumlah" min="1" 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500" 
                               required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Harga Beli (Rp)</label>
                        <input type="number" name="harga_beli" id="harga_beli" min="0" step="1000" 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500" 
                               required>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-gray-700">Total:</span>
                        <span id="totalHarga" class="text-lg font-bold text-green-600">Rp 0</span>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeModal('tambahBarangMasukModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition duration-200">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-md transition duration-200">
                    <i class="fas fa-save mr-2"></i>Simpan Transaksi
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Calculate total price
function calculateTotal() {
    const jumlah = parseInt(document.getElementById('jumlah').value) || 0;
    const hargaBeli = parseInt(document.getElementById('harga_beli').value) || 0;
    const total = jumlah * hargaBeli;
    
    document.getElementById('totalHarga').textContent = 'Rp ' + total.toLocaleString('id-ID');
}

// Update stock info when barang is selected
document.getElementById('kode_barang').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const stok = selectedOption.getAttribute('data-stok');
    const stokInfo = document.getElementById('stokInfo');
    
    if (stok) {
        stokInfo.textContent = `Stok saat ini: ${stok}`;
        stokInfo.className = 'mt-1 text-sm text-blue-600';
    } else {
        stokInfo.textContent = '';
    }
});

// Event listeners for calculation
document.getElementById('jumlah').addEventListener('input', calculateTotal);
document.getElementById('harga_beli').addEventListener('input', calculateTotal);

// Form validation
document.getElementById('barangMasukForm').addEventListener('submit', function(e) {
    const jumlah = parseInt(document.getElementById('jumlah').value);
    const hargaBeli = parseInt(document.getElementById('harga_beli').value);
    
    if (jumlah <= 0) {
        alert('Jumlah harus lebih dari 0');
        e.preventDefault();
        return;
    }
    
    if (hargaBeli < 0) {
        alert('Harga beli tidak boleh negatif');
        e.preventDefault();
        return;
    }
});

// Initialize calculation on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateTotal();
});
</script>

<?php include 'includes/footer.php'; ?>