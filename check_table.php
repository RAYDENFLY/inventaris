<?php
include 'config.php';
$result = $conn->query('DESCRIBE barang');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
}
?>
