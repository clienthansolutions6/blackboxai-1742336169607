<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

// Get all users except current user
$query = "SELECT * FROM users WHERE id_user != {$_SESSION['user_id']} ORDER BY nama_lengkap ASC";
$result = query($query);

// Success message handling
$success_msg = isset($_GET['success']) ? $_GET['success'] : '';
?>

<!-- Page Header -->
<div class="bg-white shadow-sm border-b mb-6">
    <div class="px-4 py-4 sm:px-6 flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Data Pengguna</h2>
        <a href="?module=user&action=form_entri" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg text-sm font-medium flex items-center">
            <i class="fas fa-plus mr-2"></i> Tambah Pengguna
        </a>
    </div>
</div>

<?php if ($success_msg): ?>
<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
    <span class="block sm:inline"><?php echo $success_msg; ?></span>
</div>
<?php endif; ?>

<!-- Users Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Lengkap</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
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
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900"><?php echo $data['nama_lengkap']; ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo $data['username']; ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900"><?php echo $data['email'] ?: '-'; ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            <?php
                            switch($data['role']) {
                                case 'admin':
                                    echo 'bg-purple-100 text-purple-800';
                                    break;
                                case 'kepala_gudang':
                                    echo 'bg-blue-100 text-blue-800';
                                    break;
                                case 'mahasiswa':
                                    echo 'bg-green-100 text-green-800';
                                    break;
                                case 'dosen':
                                    echo 'bg-yellow-100 text-yellow-800';
                                    break;
                                case 'staff':
                                    echo 'bg-gray-100 text-gray-800';
                                    break;
                            }
                            ?>">
                            <?php echo ucwords(str_replace('_', ' ', $data['role'])); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                        <a href="?module=user&action=form_ubah&id=<?php echo $data['id_user']; ?>" 
                           class="text-blue-500 hover:text-blue-700">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="javascript:void(0);" 
                           onclick="confirmDelete('?module=user&action=proses_hapus&id=<?php echo $data['id_user']; ?>')"
                           class="text-red-500 hover:text-red-700">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>

                <?php if (num_rows($result) == 0): ?>
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                        Tidak ada data pengguna
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function confirmDelete(url) {
    if (confirm('Apakah Anda yakin ingin menghapus pengguna ini?')) {
        window.location.href = url;
    }
}
</script>