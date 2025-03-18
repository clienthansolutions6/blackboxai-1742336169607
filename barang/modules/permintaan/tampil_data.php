<?php
// Get all item requests
$query = "SELECT pb.*, b.kode_barang, b.nama_barang, s.nama_satuan, u.nama_lengkap as pemohon
          FROM permintaan_barang pb
          JOIN barang b ON pb.id_barang = b.id_barang
          JOIN satuan s ON b.id_satuan = s.id_satuan
          JOIN users u ON pb.id_user = u.id_user
          ORDER BY pb.created_at DESC";
$result = query($query);

// Success message handling
$success_msg = isset($_GET['success']) ? $_GET['success'] : '';
?>

<!-- Page Header -->
<div class="bg-white shadow-sm border-b mb-6">
    <div class="px-4 py-4 sm:px-6 flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Data Permintaan Barang</h2>
        <div class="flex space-x-2">
            <a href="?module=permintaan&action=export" class="bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded-lg text-sm font-medium flex items-center">
                <i class="fas fa-file-excel mr-2"></i> Export Excel
            </a>
            <a href="?module=permintaan&action=form_entri" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg text-sm font-medium flex items-center">
                <i class="fas fa-plus mr-2"></i> Buat Permintaan
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
        <input type="hidden" name="module" value="permintaan">
        
        <div>
            <label for="filter_status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="filter_status" id="filter_status" 
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                <option value="">Semua Status</option>
                <option value="pending" <?php echo isset($_GET['filter_status']) && $_GET['filter_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="disetujui" <?php echo isset($_GET['filter_status']) && $_GET['filter_status'] == 'disetujui' ? 'selected' : ''; ?>>Disetujui</option>
                <option value="ditolak" <?php echo isset($_GET['filter_status']) && $_GET['filter_status'] == 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                <option value="selesai" <?php echo isset($_GET['filter_status']) && $_GET['filter_status'] == 'selesai' ? 'selected' : ''; ?>>Selesai</option>
            </select>
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

        <div>
            <label for="filter_user" class="block text-sm font-medium text-gray-700 mb-1">Pemohon</label>
            <select name="filter_user" id="filter_user" 
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                <option value="">Semua Pemohon</option>
                <?php
                $query_user = "SELECT id_user, nama_lengkap FROM users ORDER BY nama_lengkap ASC";
                $result_user = query($query_user);
                while ($user = fetch_assoc($result_user)):
                ?>
                <option value="<?php echo $user['id_user']; ?>" 
                    <?php echo isset($_GET['filter_user']) && $_GET['filter_user'] == $user['id_user'] ? 'selected' : ''; ?>>
                    <?php echo $user['nama_lengkap']; ?>
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

<!-- Requests Table -->
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
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pemohon</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php 
                $no = 1;
                while ($data = fetch_assoc($result)): 
                    $status_class = '';
                    switch ($data['status']) {
                        case 'pending':
                            $status_class = 'bg-yellow-100 text-yellow-800';
                            break;
                        case 'disetujui':
                            $status_class = 'bg-green-100 text-green-800';
                            break;
                        case 'ditolak':
                            $status_class = 'bg-red-100 text-red-800';
                            break;
                        case 'selesai':
                            $status_class = 'bg-blue-100 text-blue-800';
                            break;
                    }
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $no++; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo date('d/m/Y H:i', strtotime($data['created_at'])); ?>
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
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $data['pemohon']; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                            <?php echo ucfirst($data['status']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900"><?php echo $data['keterangan'] ?: '-'; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                        <?php if ($_SESSION['role'] === 'admin' || $_SESSION['user_id'] === $data['id_user']): ?>
                        <a href="?module=permintaan&action=form_ubah&id=<?php echo $data['id_permintaan']; ?>" 
                           class="text-blue-500 hover:text-blue-700" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($_SESSION['role'] === 'admin' && $data['status'] === 'pending'): ?>
                        <a href="javascript:void(0);" 
                           onclick="approveRequest(<?php echo $data['id_permintaan']; ?>)"
                           class="text-green-500 hover:text-green-700" title="Setujui">
                            <i class="fas fa-check"></i>
                        </a>
                        <a href="javascript:void(0);" 
                           onclick="rejectRequest(<?php echo $data['id_permintaan']; ?>)"
                           class="text-red-500 hover:text-red-700" title="Tolak">
                            <i class="fas fa-times"></i>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>

                <?php if (num_rows($result) == 0): ?>
                <tr>
                    <td colspan="9" class="px-6 py-4 text-center text-gray-500">
                        Tidak ada data permintaan barang
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function approveRequest(id) {
    if (confirm('Apakah Anda yakin ingin menyetujui permintaan ini?')) {
        window.location.href = `?module=permintaan&action=proses_approval&id=${id}&status=disetujui`;
    }
}

function rejectRequest(id) {
    const reason = prompt('Masukkan alasan penolakan:');
    if (reason !== null) {
        window.location.href = `?module=permintaan&action=proses_approval&id=${id}&status=ditolak&reason=${encodeURIComponent(reason)}`;
    }
}
</script>