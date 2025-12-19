<?php
include '../config.php';

// Check supplier table structure
$result = $conn->query('DESCRIBE supplier');
if (!$result) {
    echo 'Error describing supplier table: ' . $conn->error . PHP_EOL;
} else {
    echo 'Supplier table structure:' . PHP_EOL;
    while($row = $result->fetch_assoc()) {
        echo $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
    }
}

// Check barang_masuk table structure
$result = $conn->query('DESCRIBE barang_masuk');
if (!$result) {
    echo PHP_EOL . 'Error describing barang_masuk table: ' . $conn->error . PHP_EOL;
} else {
    echo PHP_EOL . 'Barang_masuk table structure:' . PHP_EOL;
    while($row = $result->fetch_assoc()) {
        echo $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
    }
}

// Check sample data
$result = $conn->query('SELECT bm.*, s.nama_supplier FROM barang_masuk bm LEFT JOIN supplier s ON bm.id_supplier = s.id LIMIT 1');
if (!$result) {
    echo PHP_EOL . 'Error in JOIN query: ' . $conn->error . PHP_EOL;
} elseif ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo PHP_EOL . 'Sample JOIN result:' . PHP_EOL;
    print_r($row);
} else {
    echo PHP_EOL . 'No sample data or JOIN failed' . PHP_EOL;
}
?>
