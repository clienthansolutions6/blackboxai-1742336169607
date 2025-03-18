<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

// Get date range from query parameters or set defaults
$tanggal_awal = isset($_GET['tanggal_awal']) ? $_GET['tanggal_awal'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');
?>

<!-- Page Header -->
<div class="bg-white shadow-sm border-b mb-6">
    <div class="px-4 py-4 sm:px-6">
        <h2 class="text-xl font-semibold text-gray-800">Laporan</h2>
    </div>
</div>

<!-- Date Filter -->
<div class="bg-white rounded-lg shadow mb-6 p-4">
    <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input type="hidden" name="module" value="laporan">
        
        <div>
            <label for="tanggal_awal" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Awal</label>
            <input type="date" name="tanggal_awal" id="tanggal_awal" 
                value="<?php echo $tanggal_awal; ?>"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
        </div>

        <div>
            <label for="tanggal_akhir" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Akhir</label>
            <input type="date" name="tanggal_akhir" id="tanggal_akhir"
                value="<?php echo $tanggal_akhir; ?>"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
        </div>

        <div class="flex items-end">
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium w-full">
                <i class="fas fa-filter mr-2"></i> Tampilkan
            </button>
        </div>
    </form>
</div>

<!-- Report Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <?php
    // Barang Masuk
    $query_masuk = "SELECT COUNT(*) as total, COALESCE(SUM(jumlah), 0) as total_qty
                    FROM barang_masuk 
                    WHERE tanggal_masuk BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
    $data_masuk = fetch_assoc(query($query_masuk));
    ?>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-500">
                <i class="fas fa-arrow-down text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Barang Masuk</p>
                <p class="text-xl font-semibold"><?php echo $data_masuk['total_qty']; ?> unit</p>
                <p class="text-sm text-gray-500"><?php echo $data_masuk['total']; ?> transaksi</p>
            </div>
        </div>
    </div>

    <?php
    // Barang Keluar
    $query_keluar = "SELECT COUNT(*) as total, COALESCE(SUM(jumlah), 0) as total_qty
                     FROM barang_keluar 
                     WHERE tanggal_keluar BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
    $data_keluar = fetch_assoc(query($query_keluar));
    ?>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 text-red-500">
                <i class="fas fa-arrow-up text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Barang Keluar</p>
                <p class="text-xl font-semibold"><?php echo $data_keluar['total_qty']; ?> unit</p>
                <p class="text-sm text-gray-500"><?php echo $data_keluar['total']; ?> transaksi</p>
            </div>
        </div>
    </div>

    <?php
    // Peminjaman
    $query_pinjam = "SELECT 
                     COUNT(*) as total,
                     SUM(CASE WHEN status = 'dipinjam' THEN 1 ELSE 0 END) as active,
                     SUM(CASE WHEN status = 'dipinjam' AND tanggal_kembali < CURRENT_DATE() THEN 1 ELSE 0 END) as late
                     FROM peminjaman 
                     WHERE tanggal_pinjam BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
    $data_pinjam = fetch_assoc(query($query_pinjam));
    ?>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                <i class="fas fa-hand-holding text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Peminjaman</p>
                <p class="text-xl font-semibold"><?php echo $data_pinjam['total']; ?> total</p>
                <p class="text-sm text-gray-500">
                    <?php echo $data_pinjam['active']; ?> aktif, 
                    <?php echo $data_pinjam['late']; ?> terlambat
                </p>
            </div>
        </div>
    </div>

    <?php
    // Permintaan
    $query_minta = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'disetujui' THEN 1 ELSE 0 END) as approved
                    FROM permintaan_barang 
                    WHERE created_at BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
    $data_minta = fetch_assoc(query($query_minta));
    ?>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-500">
                <i class="fas fa-file-alt text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Permintaan</p>
                <p class="text-xl font-semibold"><?php echo $data_minta['total']; ?> total</p>
                <p class="text-sm text-gray-500">
                    <?php echo $data_minta['pending']; ?> pending, 
                    <?php echo $data_minta['approved']; ?> disetujui
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Report Sections -->
<div class="space-y-6">
    <!-- Top Items -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
            <h3 class="text-lg font-medium text-gray-900">Barang Terbanyak Keluar/Dipinjam</h3>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Barang</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keluar</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dipinjam</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
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
                        while ($row = fetch_assoc($result_top)):
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $row['kode_barang']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $row['nama_barang']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $row['total_keluar']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $row['total_pinjam']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $row['total']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Stock Alerts -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
            <h3 class="text-lg font-medium text-gray-900">Peringatan Stok Menipis</h3>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Barang</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Minimal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
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
                        while ($row = fetch_assoc($result_alert)):
                            $status_class = $row['stok'] == 0 ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800';
                            $status_text = $row['stok'] == 0 ? 'Habis' : 'Menipis';
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $row['kode_barang']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $row['nama_barang']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $row['stok'] . ' ' . $row['nama_satuan']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $row['stok_minimal'] . ' ' . $row['nama_satuan']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Export Options -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
            <h3 class="text-lg font-medium text-gray-900">Export Laporan</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="?module=barang-masuk&action=export&tanggal_awal=<?php echo $tanggal_awal; ?>&tanggal_akhir=<?php echo $tanggal_akhir; ?>" 
                   class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100">
                    <i class="fas fa-file-excel text-green-500 text-2xl"></i>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-900">Barang Masuk</p>
                        <p class="text-xs text-green-500">Export ke Excel</p>
                    </div>
                </a>

                <a href="?module=barang-keluar&action=export&tanggal_awal=<?php echo $tanggal_awal; ?>&tanggal_akhir=<?php echo $tanggal_akhir; ?>" 
                   class="flex items-center p-4 bg-red-50 rounded-lg hover:bg-red-100">
                    <i class="fas fa-file-excel text-red-500 text-2xl"></i>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-900">Barang Keluar</p>
                        <p class="text-xs text-red-500">Export ke Excel</p>
                    </div>
                </a>

                <a href="?module=peminjaman&action=export&tanggal_awal=<?php echo $tanggal_awal; ?>&tanggal_akhir=<?php echo $tanggal_akhir; ?>" 
                   class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100">
                    <i class="fas fa-file-excel text-blue-500 text-2xl"></i>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-blue-900">Peminjaman</p>
                        <p class="text-xs text-blue-500">Export ke Excel</p>
                    </div>
                </a>

                <a href="?module=permintaan&action=export&tanggal_awal=<?php echo $tanggal_awal; ?>&tanggal_akhir=<?php echo $tanggal_akhir; ?>" 
                   class="flex items-center p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100">
                    <i class="fas fa-file-excel text-yellow-500 text-2xl"></i>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-yellow-900">Permintaan</p>
                        <p class="text-xs text-yellow-500">Export ke Excel</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>