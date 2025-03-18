<?php
// Get all loans
$query = "SELECT p.*, b.kode_barang, b.nama_barang, s.nama_satuan, 
          u.nama_lengkap as peminjam, l.nama_lokasi,
          DATEDIFF(p.tanggal_kembali, CURRENT_DATE()) as sisa_hari
          FROM peminjaman p
          JOIN barang b ON p.id_barang = b.id_barang
          JOIN satuan s ON b.id_satuan = s.id_satuan
          JOIN users u ON p.id_user = u.id_user
          LEFT JOIN lokasi l ON p.lokasi_peminjaman = l.id_lokasi
          ORDER BY p.created_at DESC";
$result = query($query);

// Success message handling
$success_msg = isset($_GET['success']) ? $_GET['success'] : '';
?>

<!-- Page Header -->
<div class="bg-white shadow-sm border-b mb-6">
    <div class="px-4 py-4 sm:px-6 flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Data Peminjaman Barang</h2>
        <div class="flex space-x-2">
            <a href="?module=peminjaman&action=export" class="bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded-lg text-sm font-medium flex items-center">
                <i class="fas fa-file-excel mr-2"></i> Export Excel
            </a>
            <a href="?module=peminjaman&action=form_entri" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg text-sm font-medium flex items-center">
                <i class="fas fa-plus mr-2"></i> Pinjam Barang
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
        <input type="hidden" name="module" value="peminjaman">
        
        <div>
            <label for="filter_status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="filter_status" id="filter_status" 
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                <option value="">Semua Status</option>
                <option value="pending" <?php echo isset($_GET['filter_status']) && $_GET['filter_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="dipinjam" <?php echo isset($_GET['filter_status']) && $_GET['filter_status'] == 'dipinjam' ? 'selected' : ''; ?>>Dipinjam</option>
                <option value="dikembalikan" <?php echo isset($_GET['filter_status']) && $_GET['filter_status'] == 'dikembalikan' ? 'selected' : ''; ?>>Dikembalikan</option>
                <option value="terlambat" <?php echo isset($_GET['filter_status']) && $_GET['filter_status'] == 'terlambat' ? 'selected' : ''; ?>>Terlambat</option>
            </select>
        </div>

        <div>
            <label for="filter_barang" class="block text-sm font-medium text-gray-700 mb-1">Barang</label>
            <select name="filter_barang" id="filter_barang" 
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                <option value="">Semua Barang</option>
                <?php
                $query_barang = "SELECT id_barang, kode_barang, nama_barang 
                                FROM barang 
                                WHERE jenis_item = 'tetap' 
                                ORDER BY nama_barang ASC";
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
            <label for="filter_user" class="block text-sm font-medium text-gray-700 mb-1">Peminjam</label>
            <select name="filter_user" id="filter_user" 
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                <option value="">Semua Peminjam</option>
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

<!-- Loans Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Pinjam</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode Barang</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Barang</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lokasi</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Peminjam</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Kembali</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php 
                $no = 1;
                while ($data = fetch_assoc($result)): 
                    $status = $data['status'];
                    if ($status === 'dipinjam' && $data['sisa_hari'] < 0) {
                        $status = 'terlambat';
                    }

                    $status_class = '';
                    switch ($status) {
                        case 'pending':
                            $status_class = 'bg-yellow-100 text-yellow-800';
                            break;
                        case 'dipinjam':
                            $status_class = 'bg-blue-100 text-blue-800';
                            break;
                        case 'terlambat':
                            $status_class = 'bg-red-100 text-red-800';
                            break;
                        case 'dikembalikan':
                            $status_class = 'bg-green-100 text-green-800';
                            break;
                    }
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $no++; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo date('d/m/Y', strtotime($data['tanggal_pinjam'])); ?>
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
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $data['nama_lokasi']; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $data['peminjam']; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo date('d/m/Y', strtotime($data['tanggal_kembali'])); ?>
                        <?php if ($status === 'dipinjam' || $status === 'terlambat'): ?>
                        <br>
                        <span class="text-xs <?php echo $data['sisa_hari'] < 0 ? 'text-red-600' : 'text-gray-500'; ?>">
                            <?php 
                            if ($data['sisa_hari'] < 0) {
                                echo 'Terlambat ' . abs($data['sisa_hari']) . ' hari';
                            } else {
                                echo 'Sisa ' . $data['sisa_hari'] . ' hari';
                            }
                            ?>
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                            <?php echo ucfirst($status); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                        <?php if ($_SESSION['role'] === 'admin' || $_SESSION['user_id'] === $data['id_user']): ?>
                        <a href="?module=peminjaman&action=form_ubah&id=<?php echo $data['id_peminjaman']; ?>" 
                           class="text-blue-500 hover:text-blue-700" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <?php if ($data['status'] === 'pending'): ?>
                            <a href="javascript:void(0);" 
                               onclick="approveLoan(<?php echo $data['id_peminjaman']; ?>)"
                               class="text-green-500 hover:text-green-700" title="Setujui">
                                <i class="fas fa-check"></i>
                            </a>
                            <a href="javascript:void(0);" 
                               onclick="rejectLoan(<?php echo $data['id_peminjaman']; ?>)"
                               class="text-red-500 hover:text-red-700" title="Tolak">
                                <i class="fas fa-times"></i>
                            </a>
                            <?php elseif ($data['status'] === 'dipinjam' || $data['status'] === 'terlambat'): ?>
                            <a href="javascript:void(0);" 
                               onclick="returnLoan(<?php echo $data['id_peminjaman']; ?>)"
                               class="text-yellow-500 hover:text-yellow-700" title="Proses Pengembalian">
                                <i class="fas fa-undo"></i>
                            </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>

                <?php if (num_rows($result) == 0): ?>
                <tr>
                    <td colspan="10" class="px-6 py-4 text-center text-gray-500">
                        Tidak ada data peminjaman barang
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function approveLoan(id) {
    if (confirm('Apakah Anda yakin ingin menyetujui peminjaman ini?')) {
        window.location.href = `?module=peminjaman&action=proses_approval&id=${id}&status=dipinjam`;
    }
}

function rejectLoan(id) {
    const reason = prompt('Masukkan alasan penolakan:');
    if (reason !== null) {
        window.location.href = `?module=peminjaman&action=proses_approval&id=${id}&status=ditolak&reason=${encodeURIComponent(reason)}`;
    }
}

function returnLoan(id) {
    const condition = prompt('Masukkan kondisi barang saat dikembalikan:', 'Baik');
    if (condition !== null) {
        window.location.href = `?module=peminjaman&action=proses_return&id=${id}&condition=${encodeURIComponent(condition)}`;
    }
}
</script>