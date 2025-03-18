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
header("Content-Disposition: attachment; filename=Data_Barang_Masuk_" . date('Y-m-d_H-i-s') . ".xls");

// Get filter parameters
$filter_tanggal_awal = isset($_GET['filter_tanggal_awal']) ? $_GET['filter_tanggal_awal'] : '';
$filter_tanggal_akhir = isset($_GET['filter_tanggal_akhir']) ? $_GET['filter_tanggal_akhir'] : '';
$filter_barang = isset($_GET['filter_barang']) ? (int)$_GET['filter_barang'] : '';

// Build query conditions
$where = [];
if ($filter_tanggal_awal && $filter_tanggal_akhir) {
    $where[] = "bm.tanggal_masuk BETWEEN '$filter_tanggal_awal' AND '$filter_tanggal_akhir'";
} elseif ($filter_tanggal_awal) {
    $where[] = "bm.tanggal_masuk >= '$filter_tanggal_awal'";
} elseif ($filter_tanggal_akhir) {
    $where[] = "bm.tanggal_masuk <= '$filter_tanggal_akhir'";
}
if ($filter_barang) {
    $where[] = "bm.id_barang = $filter_barang";
}

// Construct WHERE clause
$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Get incoming items data
$query = "SELECT bm.*, 
          b.kode_barang, b.nama_barang, 
          j.nama_jenis,
          s.nama_satuan,
          l.nama_lokasi,
          u.nama_lengkap as petugas
          FROM barang_masuk bm
          JOIN barang b ON bm.id_barang = b.id_barang
          LEFT JOIN jenis_barang j ON b.id_jenis = j.id_jenis
          LEFT JOIN satuan s ON b.id_satuan = s.id_satuan
          LEFT JOIN lokasi l ON bm.id_lokasi = l.id_lokasi
          LEFT JOIN users u ON bm.created_by = u.id_user
          $where_clause
          ORDER BY bm.tanggal_masuk DESC, bm.created_at DESC";
$result = query($query);

// Calculate totals
$total_items = 0;
$total_by_type = [];
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Export Data Barang Masuk</title>
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
    <h2>Data Barang Masuk</h2>
    <p>Tanggal Export: <?php echo tanggal_indo(date('Y-m-d')) . ' ' . date('H:i:s'); ?></p>
    
    <?php if ($filter_tanggal_awal || $filter_tanggal_akhir): ?>
    <p>
        Periode: 
        <?php 
        if ($filter_tanggal_awal && $filter_tanggal_akhir) {
            echo tanggal_indo($filter_tanggal_awal) . ' s/d ' . tanggal_indo($filter_tanggal_akhir);
        } elseif ($filter_tanggal_awal) {
            echo 'Dari ' . tanggal_indo($filter_tanggal_awal);
        } else {
            echo 'Sampai ' . tanggal_indo($filter_tanggal_akhir);
        }
        ?>
    </p>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Jenis</th>
                <th>Jumlah</th>
                <th>Satuan</th>
                <th>Lokasi</th>
                <th>Keterangan</th>
                <th>Petugas</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            while ($data = fetch_assoc($result)): 
                $total_items += $data['jumlah'];
                
                // Group by type
                $type_key = $data['nama_jenis'];
                if (!isset($total_by_type[$type_key])) {
                    $total_by_type[$type_key] = [
                        'count' => 0,
                        'satuan' => $data['nama_satuan']
                    ];
                }
                $total_by_type[$type_key]['count'] += $data['jumlah'];
            ?>
            <tr>
                <td class="text-center"><?php echo $no++; ?></td>
                <td class="text-center"><?php echo tanggal_indo($data['tanggal_masuk']); ?></td>
                <td><?php echo $data['kode_barang']; ?></td>
                <td><?php echo $data['nama_barang']; ?></td>
                <td><?php echo $data['nama_jenis']; ?></td>
                <td class="text-right"><?php echo $data['jumlah']; ?></td>
                <td><?php echo $data['nama_satuan']; ?></td>
                <td><?php echo $data['nama_lokasi']; ?></td>
                <td><?php echo $data['keterangan'] ?: '-'; ?></td>
                <td><?php echo $data['petugas']; ?></td>
            </tr>
            <?php endwhile; ?>

            <?php if (num_rows($result) == 0): ?>
            <tr>
                <td colspan="10" class="text-center">Tidak ada data barang masuk</td>
            </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right"><strong>Total Barang Masuk:</strong></td>
                <td class="text-right"><strong><?php echo $total_items; ?></strong></td>
                <td colspan="4"></td>
            </tr>
        </tfoot>
    </table>

    <?php if (!empty($total_by_type)): ?>
    <h3>Rekap per Jenis Barang:</h3>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Jenis Barang</th>
                <th>Jumlah</th>
                <th>Satuan</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            foreach ($total_by_type as $jenis => $data): 
            ?>
            <tr>
                <td class="text-center"><?php echo $no++; ?></td>
                <td><?php echo $jenis; ?></td>
                <td class="text-right"><?php echo $data['count']; ?></td>
                <td><?php echo $data['satuan']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <p>
        <small>
            * Data ini diekspor dari Sistem Inventaris Kampus<br>
            * Tanggal: <?php echo tanggal_indo(date('Y-m-d')) . ' ' . date('H:i:s'); ?><br>
            * Exported by: <?php echo $_SESSION['nama_lengkap']; ?>
        </small>
    </p>
</body>
</html>