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
header("Content-Disposition: attachment; filename=Data_Permintaan_Barang_" . date('Y-m-d_H-i-s') . ".xls");

// Get filter parameters
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$filter_barang = isset($_GET['filter_barang']) ? (int)$_GET['filter_barang'] : '';
$filter_user = isset($_GET['filter_user']) ? (int)$_GET['filter_user'] : '';

// Build query conditions
$where = [];
if ($filter_status) {
    $where[] = "pb.status = '$filter_status'";
}
if ($filter_barang) {
    $where[] = "pb.id_barang = $filter_barang";
}
if ($filter_user) {
    $where[] = "pb.id_user = $filter_user";
}

// Construct WHERE clause
$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Get requests data
$query = "SELECT pb.*, 
          b.kode_barang, b.nama_barang, 
          j.nama_jenis,
          s.nama_satuan,
          u.nama_lengkap as pemohon,
          a.nama_lengkap as approver
          FROM permintaan_barang pb
          JOIN barang b ON pb.id_barang = b.id_barang
          LEFT JOIN jenis_barang j ON b.id_jenis = j.id_jenis
          LEFT JOIN satuan s ON b.id_satuan = s.id_satuan
          LEFT JOIN users u ON pb.id_user = u.id_user
          LEFT JOIN users a ON pb.approved_by = a.id_user
          $where_clause
          ORDER BY pb.created_at DESC";
$result = query($query);

// Calculate totals
$total_requests = 0;
$total_by_status = [
    'pending' => 0,
    'disetujui' => 0,
    'ditolak' => 0,
    'selesai' => 0
];
$total_by_item = [];
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Export Data Permintaan Barang</title>
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
    <h2>Data Permintaan Barang</h2>
    <p>Tanggal Export: <?php echo tanggal_indo(date('Y-m-d')) . ' ' . date('H:i:s'); ?></p>
    
    <?php if ($filter_status): ?>
    <p>Status: <?php echo ucfirst($filter_status); ?></p>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Jumlah</th>
                <th>Satuan</th>
                <th>Pemohon</th>
                <th>Tujuan Penggunaan</th>
                <th>Status</th>
                <th>Diproses Oleh</th>
                <th>Tanggal Diproses</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            while ($data = fetch_assoc($result)): 
                $total_requests++;
                $total_by_status[$data['status']]++;
                
                // Group by item
                $item_key = $data['nama_barang'];
                if (!isset($total_by_item[$item_key])) {
                    $total_by_item[$item_key] = [
                        'count' => 0,
                        'approved' => 0,
                        'rejected' => 0,
                        'satuan' => $data['nama_satuan']
                    ];
                }
                $total_by_item[$item_key]['count']++;
                if ($data['status'] === 'disetujui') {
                    $total_by_item[$item_key]['approved'] += $data['jumlah'];
                } elseif ($data['status'] === 'ditolak') {
                    $total_by_item[$item_key]['rejected']++;
                }
            ?>
            <tr>
                <td class="text-center"><?php echo $no++; ?></td>
                <td class="text-center"><?php echo date('d/m/Y H:i', strtotime($data['created_at'])); ?></td>
                <td><?php echo $data['kode_barang']; ?></td>
                <td><?php echo $data['nama_barang']; ?></td>
                <td class="text-right"><?php echo $data['jumlah']; ?></td>
                <td><?php echo $data['nama_satuan']; ?></td>
                <td><?php echo $data['pemohon']; ?></td>
                <td><?php echo $data['tujuan_penggunaan']; ?></td>
                <td><?php echo ucfirst($data['status']); ?></td>
                <td><?php echo $data['approver'] ?: '-'; ?></td>
                <td><?php echo $data['approved_at'] ? date('d/m/Y H:i', strtotime($data['approved_at'])) : '-'; ?></td>
                <td><?php echo $data['approval_note'] ?: '-'; ?></td>
            </tr>
            <?php endwhile; ?>

            <?php if (num_rows($result) == 0): ?>
            <tr>
                <td colspan="12" class="text-center">Tidak ada data permintaan barang</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h3>Rekap Status Permintaan:</h3>
    <table>
        <thead>
            <tr>
                <th>Status</th>
                <th>Jumlah</th>
                <th>Persentase</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($total_by_status as $status => $count): ?>
            <tr>
                <td><?php echo ucfirst($status); ?></td>
                <td class="text-right"><?php echo $count; ?></td>
                <td class="text-right">
                    <?php echo $total_requests > 0 ? round(($count / $total_requests) * 100, 2) : 0; ?>%
                </td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <td><strong>Total</strong></td>
                <td class="text-right"><strong><?php echo $total_requests; ?></strong></td>
                <td class="text-right"><strong>100%</strong></td>
            </tr>
        </tbody>
    </table>

    <h3>Rekap per Barang:</h3>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Barang</th>
                <th>Total Permintaan</th>
                <th>Jumlah Disetujui</th>
                <th>Jumlah Ditolak</th>
                <th>Satuan</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            foreach ($total_by_item as $nama_barang => $data): 
            ?>
            <tr>
                <td class="text-center"><?php echo $no++; ?></td>
                <td><?php echo $nama_barang; ?></td>
                <td class="text-right"><?php echo $data['count']; ?></td>
                <td class="text-right"><?php echo $data['approved']; ?></td>
                <td class="text-right"><?php echo $data['rejected']; ?></td>
                <td><?php echo $data['satuan']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
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