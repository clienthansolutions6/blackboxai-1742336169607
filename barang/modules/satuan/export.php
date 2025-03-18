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
header("Content-Disposition: attachment; filename=Data_Satuan_" . date('Y-m-d_H-i-s') . ".xls");

// Get all units with item count and list
$query = "SELECT s.*, 
          (SELECT COUNT(*) FROM barang b WHERE b.id_satuan = s.id_satuan) as jumlah_barang,
          (SELECT GROUP_CONCAT(b.nama_barang SEPARATOR ', ') 
           FROM barang b 
           WHERE b.id_satuan = s.id_satuan 
           GROUP BY b.id_satuan) as daftar_barang
          FROM satuan s 
          ORDER BY s.nama_satuan ASC";
$result = query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Export Data Satuan</title>
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
    <h2>Data Satuan</h2>
    <p>Tanggal Export: <?php echo tanggal_indo(date('Y-m-d')) . ' ' . date('H:i:s'); ?></p>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Satuan</th>
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
                <td><?php echo $data['nama_satuan']; ?></td>
                <td><?php echo $data['keterangan'] ?: '-'; ?></td>
                <td class="text-center"><?php echo $data['jumlah_barang']; ?></td>
                <td><?php echo $data['daftar_barang'] ?: '-'; ?></td>
                <td class="text-center"><?php echo tanggal_indo_timestamp($data['created_at']); ?></td>
            </tr>
            <?php endwhile; ?>

            <?php if (num_rows($result) == 0): ?>
            <tr>
                <td colspan="6" class="text-center">Tidak ada data satuan</td>
            </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right"><strong>Total Satuan:</strong></td>
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