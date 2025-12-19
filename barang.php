<?php
include 'config.php';
include 'includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kode_barang = $_POST['kode_barang'];
    $nama_barang = $_POST['nama_barang'];
    $deskripsi = $_POST['deskripsi'];
    $satuan = $_POST['satuan'];
    $stok_minimum = $_POST['stok_minimum'];

    if (isset($_POST['edit_id'])) {
        // Update existing barang
        $id = $_POST['edit_id'];
        $stmt = $conn->prepare("UPDATE barang SET kode_barang=?, nama_barang=?, deskripsi=?, satuan=?, stok_minimum=? WHERE kode_barang=?");
        $stmt->bind_param("ssssis", $kode_barang, $nama_barang, $deskripsi, $satuan, $stok_minimum, $id);
    } else {
        // Insert new barang
        $stmt = $conn->prepare("INSERT INTO barang (kode_barang, nama_barang, deskripsi, satuan, stok_minimum) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $kode_barang, $nama_barang, $deskripsi, $satuan, $stok_minimum);
    }

    if ($stmt->execute()) {
        $_SESSION['flash_message'] = "Data barang berhasil disimpan!";
        header("Location: barang.php");
        exit();
    } else {
        $error = "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Handle delete
if (isset($_GET['delete'])) {
    $kode_barang = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM barang WHERE kode_barang = ?");
    $stmt->bind_param("s", $kode_barang);
    
    if ($stmt->execute()) {
        $_SESSION['flash_message'] = "Data barang berhasil dihapus!";
        header("Location: barang.php");
        exit();
    }
    $stmt->close();
}

// Get all barang
$barang = $conn->query("SELECT * FROM barang ORDER BY nama_barang");

// Get barang for edit
$edit_barang = null;
if (isset($_GET['edit'])) {
    $edit_barang = $conn->query("SELECT * FROM barang WHERE kode_barang = '".$_GET['edit']."'")->fetch_assoc();
}
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-box mr-2"></i>Manajemen Barang
    </h2>
    <button onclick="openModal('tambahBarangModal')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">
        <i class="fas fa-plus mr-2"></i>Tambah Barang
    </button>
</div>

<!-- Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Barang</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Satuan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok Min.</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php while ($row = $barang->fetch_assoc()): ?>
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
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="?edit=<?php echo $row['kode_barang']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="?delete=<?php echo $row['kode_barang']; ?>" onclick="return confirmDelete('Hapus barang <?php echo $row['nama_barang']; ?>?')" class="text-red-600 hover:text-red-900">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah/Edit Barang -->
<div id="tambahBarangModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full <?php echo !$edit_barang ? 'hidden' : ''; ?>">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800">
                <?php echo $edit_barang ? 'Edit Barang' : 'Tambah Barang Baru'; ?>
            </h3>
            <button onclick="closeModal('tambahBarangModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Kode Barang</label>
                    <input type="text" name="kode_barang" value="<?php echo $edit_barang ? $edit_barang['kode_barang'] : ''; ?>" 
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nama Barang</label>
                    <input type="text" name="nama_barang" value="<?php echo $edit_barang ? $edit_barang['nama_barang'] : ''; ?>" 
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                    <textarea name="deskripsi" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?php echo $edit_barang ? $edit_barang['deskripsi'] : ''; ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Satuan</label>
                    <select name="satuan" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="pcs" <?php echo ($edit_barang && $edit_barang['satuan'] == 'pcs') ? 'selected' : ''; ?>>pcs</option>
                        <option value="unit" <?php echo ($edit_barang && $edit_barang['satuan'] == 'unit') ? 'selected' : ''; ?>>unit</option>
                        <option value="box" <?php echo ($edit_barang && $edit_barang['satuan'] == 'box') ? 'selected' : ''; ?>>box</option>
                        <option value="kg" <?php echo ($edit_barang && $edit_barang['satuan'] == 'kg') ? 'selected' : ''; ?>>kg</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Stok Minimum</label>
                    <input type="number" name="stok_minimum" value="<?php echo $edit_barang ? $edit_barang['stok_minimum'] : '0'; ?>" 
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeModal('tambahBarangModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition duration-200">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition duration-200">
                    <?php echo $edit_barang ? 'Update' : 'Simpan'; ?>
                </button>
            </div>
            
            <?php if ($edit_barang): ?>
                <input type="hidden" name="edit_id" value="<?php echo $edit_barang['kode_barang']; ?>">
            <?php endif; ?>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>