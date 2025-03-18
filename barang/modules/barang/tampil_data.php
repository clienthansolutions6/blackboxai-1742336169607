<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

// Get all items with their details
$query = "SELECT b.*, j.nama_jenis, s.nama_satuan,
          (SELECT SUM(bl.jumlah) FROM barang_lokasi bl WHERE bl.id_barang = b.id_barang) as total_stok,
          (SELECT GROUP_CONCAT(CONCAT(l.nama_lokasi, ' (', bl.jumlah, ')') SEPARATOR ', ')
           FROM barang_lokasi bl 
           JOIN lokasi l ON bl.id_lokasi = l.id_lokasi
           WHERE bl.id_barang = b.id_barang) as lokasi_barang
          FROM barang b
          LEFT JOIN jenis_barang j ON b.id_jenis = j.id_jenis
          LEFT JOIN satuan s ON b.id_satuan = s.id_satuan
          ORDER BY b.nama_barang ASC";
$result = query($query);

// Success message handling
$success_msg = isset($_GET['success']) ? $_GET['success'] : '';
?>

<!-- Page Header -->
<div class="bg-white shadow-sm border-b mb-6">
    <div class="px-4 py-4 sm:px-6 flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Data Barang</h2>
        <a href="?module=barang&action=form_entri" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg text-sm font-medium flex items-center">
            <i class="fas fa-plus mr-2"></i> Tambah Barang
        </a>
    </div>
</div>

<?php if ($success_msg): ?>
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
    <span class="block sm:inline"><?php echo $success_msg; ?></span>
</div>
<?php endif; ?>

<!-- Filter Section -->
<div class="bg-white rounded-lg shadow mb-6 p-4">
    <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <input type="hidden" name="module" value="barang">
        
        <div>
            <label for="filter_jenis" class="block text-sm font-medium text-gray-700 mb-1">Jenis Barang</label>
            <select name="filter_jenis" id="filter_jenis" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                <option value="">Semua Jenis</option>
                <?php
                $query_jenis = "SELECT * FROM jenis_barang ORDER BY nama_jenis ASC";
                $result_jenis = query($query_jenis);
                while ($jenis = fetch_assoc($result_jenis)):
                ?>
                <option value="<?php echo $jenis['id_jenis']; ?>" <?php echo isset($_GET['filter_jenis']) && $_GET['filter_jenis'] == $jenis['id_jenis'] ? 'selected' : ''; ?>>
                    <?php echo $jenis['nama_jenis']; ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div>
            <label for="filter_tipe" class="block text-sm font-medium text-gray-700 mb-1">Tipe Barang</label>
            <select name="filter_tipe" id="filter_tipe" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                <option value="">Semua Tipe</option>
                <option value="tetap" <?php echo isset($_GET['filter_tipe']) && $_GET['filter_tipe'] == 'tetap' ? 'selected' : ''; ?>>Barang Tetap</option>
                <option value="habis_pakai" <?php echo isset($_GET['filter_tipe']) && $_GET['filter_tipe'] == 'habis_pakai' ? 'selected' : ''; ?>>Barang Habis Pakai</option>
            </select>
        </div>

        <div>
            <label for="filter_stok" class="block text-sm font-medium text-gray-700 mb-1">Status Stok</label>
            <select name="filter_stok" id="filter_stok" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                <option value="">Semua Status</option>
                <option value="tersedia" <?php echo isset($_GET['filter_stok']) && $_GET['filter_stok'] == 'tersedia' ? 'selected' : ''; ?>>Tersedia</option>
                <option value="habis" <?php echo isset($_GET['filter_stok']) && $_GET['filter_stok'] == 'habis' ? 'selected' : ''; ?>>Habis</option>
                <option value="minimal" <?php echo isset($_GET['filter_stok']) && $_GET['filter_stok'] == 'minimal' ? 'selected' : ''; ?>>Stok Minimal</option>
            </select>
        </div>

        <div class="flex items-end">
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium w-full">
                <i class="fas fa-filter mr-2"></i> Filter
            </button>
        </div>
    </form>
</div>

<!-- Items Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Barang</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lokasi</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php 
                $no = 1;
                while ($data = fetch_assoc($result)): 
                    $stok_status = '';
                    $stok_class = '';
                    
                    if ($data['total_stok'] <= 0) {
                        $stok_status = 'Habis';
                        $stok_class = 'bg-red-100 text-red-800';
                    } elseif ($data['total_stok'] <= $data['minimal_stok']) {
                        $stok_status = 'Minimal';
                        $stok_class = 'bg-yellow-100 text-yellow-800';
                    } else {
                        $stok_status = 'Tersedia';
                        $stok_class = 'bg-green-100 text-green-800';
                    }
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $no++; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                            <?php echo $data['kode_barang']; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900"><?php echo $data['nama_barang']; ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo $data['nama_jenis']; ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $data['jenis_item'] == 'tetap' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>">
                            <?php echo $data['jenis_item'] == 'tetap' ? 'Tetap' : 'Habis Pakai'; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $stok_class; ?>">
                                <?php echo $data['total_stok'] ?: '0'; ?> <?php echo $data['nama_satuan']; ?>
                            </span>
                            <?php if ($stok_status != 'Tersedia'): ?>
                            <span class="ml-2 text-xs text-gray-500">(<?php echo $stok_status; ?>)</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900"><?php echo $data['lokasi_barang'] ?: '-'; ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                        <a href="?module=barang&action=tampil_detail&id=<?php echo $data['id_barang']; ?>" 
                           class="text-indigo-500 hover:text-indigo-700" title="Detail">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="?module=barang&action=form_ubah&id=<?php echo $data['id_barang']; ?>" 
                           class="text-blue-500 hover:text-blue-700" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="javascript:void(0);" 
                           onclick="confirmDelete('?module=barang&action=proses_hapus&id=<?php echo $data['id_barang']; ?>')"
                           class="text-red-500 hover:text-red-700" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>

                <?php if (num_rows($result) == 0): ?>
                <tr>
                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                        Tidak ada data barang
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function confirmDelete(url) {
    if (confirm('Apakah Anda yakin ingin menghapus barang ini?')) {
        window.location.href = url;
    }
}
</script>