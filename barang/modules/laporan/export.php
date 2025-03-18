<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

// Include helper functions
require_once 'helper/fungsi_tanggal_indo.php';

// Get date range
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');

// Set headers for Excel download
header("Content-type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Inventaris_" . date('Y-m-d_H-i-s') . ".xls");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Inventaris</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
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
        .section {
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <h1>Laporan Inventaris</h1>
    <p>Periode: <?php echo tanggal_indo($tanggal_awal) . ' s/d ' . tanggal_indo($tanggal_akhir); ?></p>
    <p>Tanggal Export: <?php echo tanggal_indo(date('Y-m-d')) . ' ' . date('H:i:s'); ?></p>

    <!-- Summary Section -->
    <div class="section">
        <h2>Ringkasan</h2>
        <?php
        // Barang Masuk
        $query_masuk = "SELECT COUNT(*) as total, COALESCE(SUM(jumlah), 0) as total_qty
                        FROM barang_masuk 
                        WHERE tanggal_masuk BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
        $data_masuk = fetch_assoc(query($query_masuk));

        // Barang Keluar
        $query_keluar = "SELECT COUNT(*) as total, COALESCE(SUM(jumlah), 0) as total_qty
                         FROM barang_keluar 
                         WHERE tanggal_keluar BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
        $data_keluar = fetch_assoc(query($query_keluar));

        // Peminjaman
        $query_pinjam = "SELECT 
                         COUNT(*) as total,
                         SUM(CASE WHEN status = 'dipinjam' THEN 1 ELSE 0 END) as active,
                         SUM(CASE WHEN status = 'dipinjam' AND tanggal_kembali < CURRENT_DATE() THEN 1 ELSE 0 END) as late
                         FROM peminjaman 
                         WHERE tanggal_pinjam BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
        $data_pinjam = fetch_assoc(query($query_pinjam));

        // Permintaan
        $query_minta = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'disetujui' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as rejected
                        FROM permintaan_barang 
                        WHERE created_at BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
        $data_minta = fetch_assoc(query($query_minta));
        ?>
        <table>
            <tr>
                <th colspan="2">Barang Masuk</th>
                <th colspan="2">Barang Keluar</th>
                <th colspan="2">Peminjaman</th>
                <th colspan="2">Permintaan</th>
            </tr>
            <tr>
                <td>Total Transaksi</td>
                <td class="text-right"><?php echo $data_masuk['total']; ?></td>
                <td>Total Transaksi</td>
                <td class="text-right"><?php echo $data_keluar['total']; ?></td>
                <td>Total Peminjaman</td>
                <td class="text-right"><?php echo $data_pinjam['total']; ?></td>
                <td>Total Permintaan</td>
                <td class="text-right"><?php echo $data_minta['total']; ?></td>
            </tr>
            <tr>
                <td>Total Quantity</td>
                <td class="text-right"><?php echo $data_masuk['total_qty']; ?></td>
                <td>Total Quantity</td>
                <td class="text-right"><?php echo $data_keluar['total_qty']; ?></td>
                <td>Masih Dipinjam</td>
                <td class="text-right"><?php echo $data_pinjam['active']; ?></td>
                <td>Pending</td>
                <td class="text-right"><?php echo $data_minta['pending']; ?></td>
            </tr>
            <tr>
                <td colspan="2"></td>
                <td colspan="2"></td>
                <td>Terlambat</td>
                <td class="text-right"><?php echo $data_pinjam['late']; ?></td>
                <td>Disetujui</td>
                <td class="text-right"><?php echo $data_minta['approved']; ?></td>
            </tr>
        </table>
    </div>

    <!-- Top Items Section -->
    <div class="section">
        <h2>Barang Terbanyak Keluar/Dipinjam</h2>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode</th>
                    <th>Nama Barang</th>
                    <th>Keluar</th>
                    <th>Dipinjam</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query_top = "SELECT 
                    b.kode_barang,
                    b.nama_barang,
                    COALESCE(SUM(bk.jumlah), 0) as total_keluar,
                    COALESCE(COUNT(p.id_peminjaman), 0) as total_pinjam,
                    COALESCE(SUM(bk.jumlah), 0) + COALESCE(COUNT(p.id_peminjaman), 0) as total
                    FROM barang b
                    LEFT JOIN barang_keluar bk ON b.id_barang = bk.id_barang 
                        AND bk.tanggal_keluar BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
                    LEFT JOIN peminjaman p ON b.id_barang = p.id_barang 
                        AND p.tanggal_pinjam BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
                    GROUP BY b.id_barang
                    HAVING total > 0
                    ORDER BY total DESC
                    LIMIT 10";
                $result_top = query($query_top);
                $no = 1;
                while ($row = fetch_assoc($result_top)):
                ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td><?php echo $row['kode_barang']; ?></td>
                    <td><?php echo $row['nama_barang']; ?></td>
                    <td class="text-right"><?php echo $row['total_keluar']; ?></td>
                    <td class="text-right"><?php echo $row['total_pinjam']; ?></td>
                    <td class="text-right"><?php echo $row['total']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Stock Alerts Section -->
    <div class="section">
        <h2>Peringatan Stok</h2>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode</th>
                    <th>Nama Barang</th>
                    <th>Stok Saat Ini</th>
                    <th>Stok Minimal</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query_alert = "SELECT 
                    b.kode_barang,
                    b.nama_barang,
                    b.stok,
                    b.stok_minimal,
                    s.nama_satuan
                    FROM barang b
                    LEFT JOIN satuan s ON b.id_satuan = s.id_satuan
                    WHERE b.stok <= b.stok_minimal
                    ORDER BY b.stok ASC";
                $result_alert = query($query_alert);
                $no = 1;
                while ($row = fetch_assoc($result_alert)):
                    $status = $row['stok'] == 0 ? 'Habis' : 'Menipis';
                ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td><?php echo $row['kode_barang']; ?></td>
                    <td><?php echo $row['nama_barang']; ?></td>
                    <td class="text-right"><?php echo $row['stok'] . ' ' . $row['nama_satuan']; ?></td>
                    <td class="text-right"><?php echo $row['stok_minimal'] . ' ' . $row['nama_satuan']; ?></td>
                    <td class="text-center"><?php echo $status; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Location Stock Section -->
    <div class="section">
        <h2>Stok per Lokasi</h2>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Lokasi</th>
                    <th>Jumlah Item</th>
                    <th>Total Stok</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query_lokasi = "SELECT 
                    l.nama_lokasi,
                    COUNT(DISTINCT bl.id_barang) as total_item,
                    SUM(bl.jumlah) as total_stok
                    FROM lokasi l
                    LEFT JOIN barang_lokasi bl ON l.id_lokasi = bl.id_lokasi
                    GROUP BY l.id_lokasi
                    ORDER BY l.nama_lokasi ASC";
                $result_lokasi = query($query_lokasi);
                $no = 1;
                while ($row = fetch_assoc($result_lokasi)):
                ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td><?php echo $row['nama_lokasi']; ?></td>
                    <td class="text-right"><?php echo $row['total_item']; ?></td>
                    <td class="text-right"><?php echo $row['total_stok']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <p>
        <small>
            * Data ini diekspor dari Sistem Inventaris Kampus<br>
            * Tanggal: <?php echo tanggal_indo(date('Y-m-d')) . ' ' . date('H:i:s'); ?><br>
            * Exported by: <?php echo $_SESSION['nama_lengkap']; ?>
        </small>
    </p>
</body>
</html>