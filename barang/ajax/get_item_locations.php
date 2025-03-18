<?php
// Include database connection
require_once '../config/database.php';

// Check if request has item ID
if (!isset($_GET['id_barang'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID barang tidak ditemukan']);
    exit();
}

$id_barang = (int)$_GET['id_barang'];

// Get all locations with stock for this item
$query = "SELECT l.id_lokasi, l.nama_lokasi, bl.jumlah 
          FROM lokasi l
          JOIN barang_lokasi bl ON l.id_lokasi = bl.id_lokasi
          WHERE bl.id_barang = $id_barang AND bl.jumlah > 0
          ORDER BY l.nama_lokasi ASC";
$result = query($query);

$locations = [];
while ($row = fetch_assoc($result)) {
    $locations[] = [
        'id_lokasi' => $row['id_lokasi'],
        'nama_lokasi' => $row['nama_lokasi'],
        'jumlah' => $row['jumlah']
    ];
}

// Set JSON response headers
header('Content-Type: application/json');
echo json_encode($locations);
?>