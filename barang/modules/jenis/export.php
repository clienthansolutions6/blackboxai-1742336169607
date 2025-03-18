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
header("Content-Disposition: attachment; filename=Data_Jenis_Barang_" . date('Y-m-d_H-i-s') . ".xls");

// Get all categories with item count
$query = "SELECT j.*, 
          (SELECT COUNT(*) FROM barang b WHERE b.id_jenis = j.id_jenis) as jumlah_barang,
          (SELECT GROUP_CONCAT(b.nama_barang SEPARATOR ', ') 
           FROM barang b 
           WHERE b.id_jenis = j.id_jenis 
           GROUP BY b.id_jenis) as daftar_barang
          FROM jenis_barang j 
          ORDER BY j.nama_jenis ASC";
$result = query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Export Data Jenis Barang</title>
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
    <h2>Data Jenis Barang</h2>
    <p>Tanggal Export: <?php echo tanggal_indo(date('Y-m-d')) . ' ' . date('H:i:s'); ?></p>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Jenis</th>
                <th>Keterangan</th>
                <th>Jumlah Barang</th>
                <th>Daftar Barang</th>
                <th>Tanggal Dibuat</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            while ($data = fetch_assoc($result)): 
            ?>
            <tr>
                <td class="text-center"><?php echo $no++; ?></td>
                <td><?php echo $data['nama_jenis']; ?></td>
                <td><?php echo $data['keterangan'] ?: '-'; ?></td>
                <td class="text-center"><?php echo $data['jumlah_barang']; ?></td>
                <td><?php echo $data['daftar_barang'] ?: '-'; ?></td>
                <td class="text-center"><?php echo tanggal_indo_timestamp($data['created_at']); ?></td>
            </tr>
            <?php endwhile; ?>

            <?php if (num_rows($result) == 0): ?>
            <tr>
                <td colspan="6" class="text-center">Tidak ada data jenis barang</td>
            </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right"><strong>Total Jenis Barang:</strong></td>
                <td class="text-center"><strong><?php echo num_rows($result); ?></strong></td>
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
</body>
</html>