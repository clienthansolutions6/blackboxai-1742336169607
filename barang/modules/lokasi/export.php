<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

// Include helper functions
require_once 'helper/fungsi_tanggal_indo.php';

// Set headers for Excel download
header("Content-type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Data_Lokasi_" . date('Y-m-d_H-i-s') . ".xls");

// Get all locations with item details
$query = "SELECT l.*, 
          (SELECT COUNT(DISTINCT bl.id_barang) FROM barang_lokasi bl WHERE bl.id_lokasi = l.id_lokasi) as jumlah_jenis_barang,
          (SELECT SUM(bl.jumlah) FROM barang_lokasi bl WHERE bl.id_lokasi = l.id_lokasi) as total_barang,
          (SELECT GROUP_CONCAT(
              CONCAT(b.nama_barang, ' (', bl.jumlah, ' ', s.nama_satuan, ')')
              SEPARATOR ', '
           ) 
           FROM barang_lokasi bl 
           JOIN barang b ON bl.id_barang = b.id_barang
           JOIN satuan s ON b.id_satuan = s.id_satuan
           WHERE bl.id_lokasi = l.id_lokasi
           GROUP BY bl.id_lokasi) as daftar_barang
          FROM lokasi l 
          ORDER BY l.nama_lokasi ASC";
$result = query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Export Data Lokasi</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #000;
            padding: 5px;
        }
        th {
            background-color: #f0f0f0;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <h2>Data Lokasi</h2>
    <p>Tanggal Export: <?php echo tanggal_indo(date('Y-m-d')) . ' ' . date('H:i:s'); ?></p>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Lokasi</th>
                <th>Keterangan</th>
                <th>Jumlah Jenis Barang</th>
                <th>Total Barang</th>
                <th>Daftar Barang</th>
                <th>Tanggal Dibuat</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $total_jenis = 0;
            $total_barang = 0;
            while ($data = fetch_assoc($result)): 
                $total_jenis += $data['jumlah_jenis_barang'];
                $total_barang += $data['total_barang'];
            ?>
            <tr>
                <td class="text-center"><?php echo $no++; ?></td>
                <td><?php echo $data['nama_lokasi']; ?></td>
                <td><?php echo $data['keterangan'] ?: '-'; ?></td>
                <td class="text-center"><?php echo $data['jumlah_jenis_barang']; ?></td>
                <td class="text-center"><?php echo $data['total_barang'] ?: '0'; ?></td>
                <td><?php echo $data['daftar_barang'] ?: '-'; ?></td>
                <td class="text-center"><?php echo tanggal_indo_timestamp($data['created_at']); ?></td>
            </tr>
            <?php endwhile; ?>

            <?php if (num_rows($result) == 0): ?>
            <tr>
                <td colspan="7" class="text-center">Tidak ada data lokasi</td>
            </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right"><strong>Total:</strong></td>
                <td class="text-center"><strong><?php echo $total_jenis; ?></strong></td>
                <td class="text-center"><strong><?php echo $total_barang; ?></strong></td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    <p>
        <small>
            * Data ini diekspor dari Sistem Inventaris Kampus<br>
            * Tanggal: <?php echo tanggal_indo(date('Y-m-d')) . ' ' . date('H:i:s'); ?><br>
            * Exported by: <?php echo $_SESSION['nama_lengkap']; ?>
        </small>
    </p>

    <div style="margin-top: 20px;">
        <h3>Keterangan:</h3>
        <ul>
            <li>Jumlah Jenis Barang: Menunjukkan berapa jenis barang yang berbeda di lokasi tersebut</li>
            <li>Total Barang: Menunjukkan total keseluruhan barang (jumlah unit) di lokasi tersebut</li>
            <li>Daftar Barang: Menampilkan detail barang beserta jumlahnya di lokasi tersebut</li>
        </ul>
    </div>
</body>
</html>