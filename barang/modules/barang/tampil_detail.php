<?php
// Get item ID from URL
$id_barang = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get item details with related information
$query = "SELECT b.*, j.nama_jenis, s.nama_satuan,
          (SELECT SUM(bl.jumlah) FROM barang_lokasi bl WHERE bl.id_barang = b.id_barang) as total_stok
          FROM barang b
          LEFT JOIN jenis_barang j ON b.id_jenis = j.id_jenis
          LEFT JOIN satuan s ON b.id_satuan = s.id_satuan
          WHERE b.id_barang = $id_barang";
$result = query($query);

if (num_rows($result) === 0) {
    echo "<script>
        alert('Data barang tidak ditemukan!');
        window.location.href = '?module=barang';
    </script>";
    exit();
}

$data = fetch_assoc($result);

// Get item locations
$query_lokasi = "SELECT bl.*, l.nama_lokasi 
                 FROM barang_lokasi bl 
                 JOIN lokasi l ON bl.id_lokasi = l.id_lokasi 
                 WHERE bl.id_barang = $id_barang 
                 ORDER BY l.nama_lokasi ASC";
$result_lokasi = query($query_lokasi);

// Get recent transactions
$query_transaksi = "SELECT 'masuk' as tipe, 
                           bm.tanggal_masuk as tanggal,
                           bm.jumlah,
                           bm.keterangan,
                           u.nama_lengkap as petugas
                    FROM barang_masuk bm
                    JOIN users u ON bm.created_by = u.id_user
                    WHERE bm.id_barang = $id_barang
                    UNION ALL
                    SELECT 'keluar' as tipe,
                           bk.tanggal_keluar as tanggal,
                           bk.jumlah,
                           bk.keterangan,
                           u.nama_lengkap as petugas
                    FROM barang_keluar bk
                    JOIN users u ON bk.created_by = u.id_user
                    WHERE bk.id_barang = $id_barang
                    ORDER BY tanggal DESC
                    LIMIT 10";
$result_transaksi = query($query_transaksi);

// Get active requests and loans
$query_permintaan = "SELECT pb.*, u.nama_lengkap 
                     FROM permintaan_barang pb
                     JOIN users u ON pb.id_user = u.id_user
                     WHERE pb.id_barang = $id_barang AND pb.status = 'pending'
                     ORDER BY pb.created_at DESC";
$result_permintaan = query($query_permintaan);

$query_peminjaman = "SELECT p.*, u.nama_lengkap 
                     FROM peminjaman p
                     JOIN users u ON p.id_user = u.id_user
                     WHERE p.id_barang = $id_barang AND p.status = 'dipinjam'
                     ORDER BY p.tanggal_pinjam DESC";
$result_peminjaman = query($query_peminjaman);
?>

<!-- Page Header -->
<div class="bg-white shadow-sm border-b mb-6">
    <div class="px-4 py-4 sm:px-6 flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Detail Barang</h2>
        <div class="flex space-x-2">
            <a href="?module=barang&action=form_ubah&id=<?php echo $id_barang; ?>" 
               class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg text-sm font-medium flex items-center">
                <i class="fas fa-edit mr-2"></i> Edit
            </a>
            <a href="?module=barang" 
               class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg text-sm font-medium flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Kembali
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Info -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Basic Information -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Barang</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Kode Barang</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo $data['kode_barang']; ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Nama Barang</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo $data['nama_barang']; ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Jenis Barang</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo $data['nama_jenis']; ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Satuan</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo $data['nama_satuan']; ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Tipe Barang</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php echo $data['jenis_item'] == 'tetap' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>">
                                <?php echo $data['jenis_item'] == 'tetap' ? 'Barang Tetap' : 'Barang Habis Pakai'; ?>
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Total Stok</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php 
                                if ($data['total_stok'] <= 0) {
                                    echo 'bg-red-100 text-red-800';
                                } elseif ($data['total_stok'] <= $data['minimal_stok']) {
                                    echo 'bg-yellow-100 text-yellow-800';
                                } else {
                                    echo 'bg-green-100 text-green-800';
                                }
                                ?>">
                                <?php echo $data['total_stok']; ?> <?php echo $data['nama_satuan']; ?>
                            </span>
                        </dd>
                    </div>
                    <?php if ($data['jenis_item'] == 'habis_pakai'): ?>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Stok Minimal</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo $data['minimal_stok']; ?> <?php echo $data['nama_satuan']; ?></dd>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if ($data['keterangan']): ?>
                <div class="mt-4">
                    <dt class="text-sm font-medium text-gray-500">Keterangan</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo nl2br($data['keterangan']); ?></dd>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stock Locations -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Lokasi Penyimpanan</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lokasi</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($lokasi = fetch_assoc($result_lokasi)): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $lokasi['nama_lokasi']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $lokasi['jumlah']; ?> <?php echo $data['nama_satuan']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if (num_rows($result_lokasi) == 0): ?>
                            <tr>
                                <td colspan="2" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada data lokasi</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Riwayat Transaksi Terakhir</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Petugas</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($transaksi = fetch_assoc($result_transaksi)): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('d/m/Y', strtotime($transaksi['tanggal'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $transaksi['tipe'] == 'masuk' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo ucfirst($transaksi['tipe']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $transaksi['jumlah']; ?> <?php echo $data['nama_satuan']; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo $transaksi['keterangan']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $transaksi['petugas']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if (num_rows($result_transaksi) == 0): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada riwayat transaksi</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Side Info -->
    <div class="space-y-6">
        <!-- Active Requests -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Permintaan Aktif</h3>
                <div class="space-y-3">
                    <?php while ($permintaan = fetch_assoc($result_permintaan)): ?>
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-900"><?php echo $permintaan['nama_lengkap']; ?></p>
                                <p class="text-sm text-gray-500">
                                    <?php echo $permintaan['jumlah']; ?> <?php echo $data['nama_satuan']; ?>
                                </p>
                                <p class="text-xs text-gray-500">
                                    <?php echo date('d/m/Y', strtotime($permintaan['created_at'])); ?>
                                </p>
                            </div>
                            <a href="?module=permintaan&action=form_ubah&id=<?php echo $permintaan['id_permintaan']; ?>" 
                               class="text-blue-500 hover:text-blue-700">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <?php if (num_rows($result_permintaan) == 0): ?>
                    <p class="text-sm text-gray-500 text-center">Tidak ada permintaan aktif</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Active Loans -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Peminjaman Aktif</h3>
                <div class="space-y-3">
                    <?php while ($peminjaman = fetch_assoc($result_peminjaman)): ?>
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-900"><?php echo $peminjaman['nama_lengkap']; ?></p>
                                <p class="text-sm text-gray-500">
                                    <?php echo $peminjaman['jumlah']; ?> <?php echo $data['nama_satuan']; ?>
                                </p>
                                <p class="text-xs text-gray-500">
                                    <?php echo date('d/m/Y', strtotime($peminjaman['tanggal_pinjam'])); ?> - 
                                    <?php echo date('d/m/Y', strtotime($peminjaman['tanggal_kembali'])); ?>
                                </p>
                            </div>
                            <a href="?module=peminjaman&action=form_ubah&id=<?php echo $peminjaman['id_peminjaman']; ?>" 
                               class="text-blue-500 hover:text-blue-700">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <?php if (num_rows($result_peminjaman) == 0): ?>
                    <p class="text-sm text-gray-500 text-center">Tidak ada peminjaman aktif</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Item Info -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Tambahan</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Tanggal Dibuat</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?php echo date('d/m/Y H:i', strtotime($data['created_at'])); ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Terakhir Diupdate</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?php echo date('d/m/Y H:i', strtotime($data['updated_at'])); ?>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>