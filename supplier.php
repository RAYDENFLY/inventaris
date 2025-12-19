<?php
include 'config.php';
include 'includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_supplier = $_POST['id_supplier'];
    $nama_supplier = $_POST['nama_supplier'];
    $alamat = $_POST['alamat'];
    $telepon = $_POST['telepon'];

    if (isset($_POST['edit_id'])) {
        // Update existing supplier
        $id = $_POST['edit_id'];
        $stmt = $conn->prepare("UPDATE supplier SET id_supplier=?, nama_supplier=?, alamat=?, telepon=? WHERE id_supplier=?");
        $stmt->bind_param("sssss", $id_supplier, $nama_supplier, $alamat, $telepon, $id);
    } else {
        // Insert new supplier
        $stmt = $conn->prepare("INSERT INTO supplier (id_supplier, nama_supplier, alamat, telepon) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $id_supplier, $nama_supplier, $alamat, $telepon);
    }

    if ($stmt->execute()) {
        $_SESSION['flash_message'] = "Data supplier berhasil disimpan!";
        header("Location: supplier.php");
        exit();
    }
    $stmt->close();
}

// Handle delete
if (isset($_GET['delete'])) {
    $id_supplier = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM supplier WHERE id_supplier = ?");
    $stmt->bind_param("s", $id_supplier);
    
    if ($stmt->execute()) {
        $_SESSION['flash_message'] = "Data supplier berhasil dihapus!";
        header("Location: supplier.php");
        exit();
    }
    $stmt->close();
}

// Get all suppliers
$suppliers = $conn->query("SELECT * FROM supplier ORDER BY nama_supplier");

// Get supplier for edit
$edit_supplier = null;
if (isset($_GET['edit'])) {
    $edit_supplier = $conn->query("SELECT * FROM supplier WHERE id_supplier = '".$_GET['edit']."'")->fetch_assoc();
}
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-truck mr-2"></i>Manajemen Supplier
    </h2>
    <button onclick="openModal('tambahSupplierModal')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">
        <i class="fas fa-plus mr-2"></i>Tambah Supplier
    </button>
</div>

<!-- Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Supplier</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Supplier</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alamat</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telepon</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php while ($row = $suppliers->fetch_assoc()): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $row['id_supplier']; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $row['nama_supplier']; ?></td>
                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo $row['alamat']; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $row['telepon']; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="?edit=<?php echo $row['id_supplier']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="?delete=<?php echo $row['id_supplier']; ?>" onclick="return confirmDelete('Hapus supplier <?php echo $row['nama_supplier']; ?>?')" class="text-red-600 hover:text-red-900">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah/Edit Supplier -->
<div id="tambahSupplierModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full <?php echo !$edit_supplier ? 'hidden' : ''; ?>">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800">
                <?php echo $edit_supplier ? 'Edit Supplier' : 'Tambah Supplier Baru'; ?>
            </h3>
            <button onclick="closeModal('tambahSupplierModal')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">ID Supplier</label>
                    <input type="text" name="id_supplier" value="<?php echo $edit_supplier ? $edit_supplier['id_supplier'] : ''; ?>" 
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nama Supplier</label>
                    <input type="text" name="nama_supplier" value="<?php echo $edit_supplier ? $edit_supplier['nama_supplier'] : ''; ?>" 
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Alamat</label>
                    <textarea name="alamat" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"><?php echo $edit_supplier ? $edit_supplier['alamat'] : ''; ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Telepon</label>
                    <input type="text" name="telepon" value="<?php echo $edit_supplier ? $edit_supplier['telepon'] : ''; ?>" 
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeModal('tambahSupplierModal')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition duration-200">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition duration-200">
                    <?php echo $edit_supplier ? 'Update' : 'Simpan'; ?>
                </button>
            </div>
            
            <?php if ($edit_supplier): ?>
                <input type="hidden" name="edit_id" value="<?php echo $edit_supplier['id_supplier']; ?>">
            <?php endif; ?>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>