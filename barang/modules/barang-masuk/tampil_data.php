<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

// Get all incoming items
$query = "SELECT bm.*, b.kode_barang, b.nama_barang, s.nama_satuan, u.nama_lengkap as petugas
          FROM barang_masuk bm
          JOIN barang b ON bm.id_barang = b.id_barang
          JOIN satuan s ON b.id_satuan = s.id_satuan
          JOIN users u ON bm.created_by = u.id_user
          ORDER BY bm.tanggal_masuk DESC, bm.created_at DESC";
$result = query($query);

// Success message handling
$success_msg = isset($_GET['success']) ? $_GET['success'] : '';
?>

<!-- Page Header -->
<div class="bg-white shadow-sm border-b mb-6">
    <div class="px-4 py-4 sm:px-6 flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Data Barang Masuk</h2>
        <div class="flex space-x-2">
            <a href="?module=barang-masuk&action=export" class="bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded-lg text-sm font-medium flex items-center">
                <i class="fas fa-file-excel mr-2"></i> Export Excel
            </a>
            <a href="?module=barang-masuk&action=form_entri" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg text-sm font-medium flex items-center">
                <i class="fas fa-plus mr-2"></i> Tambah Barang Masuk
            </a>
        </div>
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
        <input type="hidden" name="module" value="barang-masuk">
        
        <div>
            <label for="filter_tanggal_awal" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Awal</label>
            <input type="date" name="filter_tanggal_awal" id="filter_tanggal_awal" 
                value="<?php echo isset($_GET['filter_tanggal_awal']) ? $_GET['filter_tanggal_awal'] : ''; ?>"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
        </div>

        <div>
            <label for="filter_tanggal_akhir" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Akhir</label>
            <input type="date" name="filter_tanggal_akhir" id="filter_tanggal_akhir"
                value="<?php echo isset($_GET['filter_tanggal_akhir']) ? $_GET['filter_tanggal_akhir'] : ''; ?>"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
        </div>

        <div>
            <label for="filter_barang" class="block text-sm font-medium text-gray-700 mb-1">Barang</label>
            <select name="filter_barang" id="filter_barang" 
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                <option value="">Semua Barang</option>
                <?php
                $query_barang = "SELECT id_barang, kode_barang, nama_barang FROM barang ORDER BY nama_barang ASC";
                $result_barang = query($query_barang);
                while ($barang = fetch_assoc($result_barang)):
                ?>
                <option value="<?php echo $barang['id_barang']; ?>" 
                    <?php echo isset($_GET['filter_barang']) && $_GET['filter_barang'] == $barang['id_barang'] ? 'selected' : ''; ?>>
                    <?php echo $barang['nama_barang']; ?> (<?php echo $barang['kode_barang']; ?>)
                </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="flex items-end">
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium w-full">
                <i class="fas fa-filter mr-2"></i> Filter
            </button>
        </div>
    </form>
</div>

<!-- Incoming Items Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode Barang</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Barang</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Petugas</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php 
                $no = 1;
                while ($data = fetch_assoc($result)): 
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $no++; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo date('d/m/Y', strtotime($data['tanggal_masuk'])); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                            <?php echo $data['kode_barang']; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $data['nama_barang']; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo $data['jumlah']; ?> <?php echo $data['nama_satuan']; ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo $data['keterangan'] ?: '-'; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $data['petugas']; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="?module=barang-masuk&action=form_ubah&id=<?php echo $data['id_barang_masuk']; ?>" 
                           class="text-blue-500 hover:text-blue-700" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>

                <?php if (num_rows($result) == 0): ?>
                <tr>
                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                        Tidak ada data barang masuk
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>