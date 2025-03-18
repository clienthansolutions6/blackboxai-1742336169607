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
header("Content-Disposition: attachment; filename=Data_Peminjaman_Barang_" . date('Y-m-d_H-i-s') . ".xls");

// Get filter parameters
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$filter_barang = isset($_GET['filter_barang']) ? (int)$_GET['filter_barang'] : '';
$filter_user = isset($_GET['filter_user']) ? (int)$_GET['filter_user'] : '';

// Build query conditions
$where = [];
if ($filter_status) {
    if ($filter_status === 'terlambat') {
        $where[] = "(p.status = 'dipinjam' AND p.tanggal_kembali < CURRENT_DATE())";
    } else {
        $where[] = "p.status = '$filter_status'";
    }
}
if ($filter_barang) {
    $where[] = "p.id_barang = $filter_barang";
}
if ($filter_user) {
    $where[] = "p.id_user = $filter_user";
}

// Construct WHERE clause
$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Get loans data
$query = "SELECT p.*, 
          b.kode_barang, b.nama_barang, 
          j.nama_jenis,
          s.nama_satuan,
          l.nama_lokasi,
          u.nama_lengkap as peminjam,
          a.nama_lengkap as approver,
          r.nama_lengkap as returner,
          DATEDIFF(p.tanggal_kembali, CURRENT_DATE()) as sisa_hari
          FROM peminjaman p
          JOIN barang b ON p.id_barang = b.id_barang
          LEFT JOIN jenis_barang j ON b.id_jenis = j.id_jenis
          LEFT JOIN satuan s ON b.id_satuan = s.id_satuan
          LEFT JOIN lokasi l ON p.lokasi_peminjaman = l.id_lokasi
          LEFT JOIN users u ON p.id_user = u.id_user
          LEFT JOIN users a ON p.approved_by = a.id_user
          LEFT JOIN users r ON p.returned_by = r.id_user
          $where_clause
          ORDER BY p.created_at DESC";
$result = query($query);

// Calculate totals
$total_loans = 0;
$total_by_status = [
    'pending' => 0,
    'dipinjam' => 0,
    'terlambat' => 0,
    'dikembalikan' => 0,
    'ditolak' => 0
];
$total_by_item = [];
$total_late_days = 0;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Export Data Peminjaman Barang</title>
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
    <h2>Data Peminjaman Barang</h2>
    <p>Tanggal Export: <?php echo tanggal_indo(date('Y-m-d')) . ' ' . date('H:i:s'); ?></p>
    
    <?php if ($filter_status): ?>
    <p>Status: <?php echo ucfirst($filter_status); ?></p>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal Pinjam</th>
                <th>Tanggal Kembali</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Jumlah</th>
                <th>Satuan</th>
                <th>Lokasi</th>
                <th>Peminjam</th>
                <th>Keperluan</th>
                <th>Status</th>
                <th>Keterlambatan</th>
                <th>Kondisi Kembali</th>
                <th>Diproses Oleh</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            while ($data = fetch_assoc($result)): 
                $total_loans++;
                $status = $data['status'];
                if ($status === 'dipinjam' && $data['sisa_hari'] < 0) {
                    $status = 'terlambat';
                }
                $total_by_status[$status]++;
                
                // Group by item
                $item_key = $data['nama_barang'];
                if (!isset($total_by_item[$item_key])) {
                    $total_by_item[$item_key] = [
                        'count' => 0,
                        'active' => 0,
                        'returned' => 0,
                        'late' => 0,
                        'satuan' => $data['nama_satuan']
                    ];
                }
                $total_by_item[$item_key]['count']++;
                
                if ($status === 'dipinjam') {
                    $total_by_item[$item_key]['active']++;
                } elseif ($status === 'dikembalikan') {
                    $total_by_item[$item_key]['returned']++;
                    if ($data['keterlambatan'] > 0) {
                        $total_by_item[$item_key]['late']++;
                        $total_late_days += $data['keterlambatan'];
                    }
                }
            ?>
            <tr>
                <td class="text-center"><?php echo $no++; ?></td>
                <td class="text-center"><?php echo tanggal_indo($data['tanggal_pinjam']); ?></td>
                <td class="text-center"><?php echo tanggal_indo($data['tanggal_kembali']); ?></td>
                <td><?php echo $data['kode_barang']; ?></td>
                <td><?php echo $data['nama_barang']; ?></td>
                <td class="text-right"><?php echo $data['jumlah']; ?></td>
                <td><?php echo $data['nama_satuan']; ?></td>
                <td><?php echo $data['nama_lokasi']; ?></td>
                <td><?php echo $data['peminjam']; ?></td>
                <td><?php echo $data['keperluan']; ?></td>
                <td><?php echo ucfirst($status); ?></td>
                <td class="text-center">
                    <?php 
                    if ($data['keterlambatan'] > 0) {
                        echo $data['keterlambatan'] . ' hari';
                    } elseif ($status === 'dipinjam' && $data['sisa_hari'] < 0) {
                        echo abs($data['sisa_hari']) . ' hari';
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
                <td><?php echo $data['kondisi_pengembalian'] ?: '-'; ?></td>
                <td><?php echo $data['approver'] ?: '-'; ?></td>
                <td><?php echo $data['approval_note'] ?: $data['return_note'] ?: '-'; ?></td>
            </tr>
            <?php endwhile; ?>

            <?php if (num_rows($result) == 0): ?>
            <tr>
                <td colspan="15" class="text-center">Tidak ada data peminjaman barang</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h3>Rekap Status Peminjaman:</h3>
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
                    <?php echo $total_loans > 0 ? round(($count / $total_loans) * 100, 2) : 0; ?>%
                </td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <td><strong>Total</strong></td>
                <td class="text-right"><strong><?php echo $total_loans; ?></strong></td>
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
                <th>Total Peminjaman</th>
                <th>Sedang Dipinjam</th>
                <th>Sudah Dikembalikan</th>
                <th>Pernah Terlambat</th>
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
                <td class="text-right"><?php echo $data['active']; ?></td>
                <td class="text-right"><?php echo $data['returned']; ?></td>
                <td class="text-right"><?php echo $data['late']; ?></td>
                <td><?php echo $data['satuan']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($total_late_days > 0): ?>
    <h3>Statistik Keterlambatan:</h3>
    <p>Total hari keterlambatan: <?php echo $total_late_days; ?> hari</p>
    <p>Rata-rata keterlambatan: <?php echo round($total_late_days / $total_by_status['dikembalikan'], 1); ?> hari per peminjaman yang terlambat</p>
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