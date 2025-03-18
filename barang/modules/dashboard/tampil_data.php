<?php
// Include helper functions
require_once 'helper/fungsi_tanggal_indo.php';

// Function to get total count from a table
function get_total_count($table) {
    $query = "SELECT COUNT(*) as total FROM $table";
    $result = query($query);
    $data = fetch_assoc($result);
    return $data['total'];
}

// Function to get total items by type
function get_items_by_type($type) {
    $query = "SELECT COUNT(*) as total FROM barang WHERE jenis_item = '$type'";
    $result = query($query);
    $data = fetch_assoc($result);
    return $data['total'];
}

// Function to get low stock items
function get_low_stock_items() {
    $query = "SELECT b.*, j.nama_jenis, s.nama_satuan 
              FROM barang b 
              LEFT JOIN jenis_barang j ON b.id_jenis = j.id_jenis 
              LEFT JOIN satuan s ON b.id_satuan = s.id_satuan 
              WHERE b.stok <= b.minimal_stok 
              ORDER BY b.stok ASC 
              LIMIT 5";
    return query($query);
}

// Function to get recent transactions
function get_recent_transactions($limit = 5) {
    $query = "SELECT 
                'masuk' as tipe,
                bm.tanggal_masuk as tanggal,
                b.nama_barang,
                bm.jumlah,
                u.nama_lengkap as pengguna
              FROM barang_masuk bm
              JOIN barang b ON bm.id_barang = b.id_barang
              JOIN users u ON bm.created_by = u.id_user
              UNION ALL
              SELECT 
                'keluar' as tipe,
                bk.tanggal_keluar as tanggal,
                b.nama_barang,
                bk.jumlah,
                u.nama_lengkap as pengguna
              FROM barang_keluar bk
              JOIN barang b ON bk.id_barang = b.id_barang
              JOIN users u ON bk.created_by = u.id_user
              ORDER BY tanggal DESC
              LIMIT $limit";
    return query($query);
}

// Get statistics based on user role
$role = $_SESSION['role'];
$stats = [];

if (in_array($role, ['admin', 'kepala_gudang'])) {
    $stats = [
        'total_barang' => get_total_count('barang'),
        'barang_tetap' => get_items_by_type('tetap'),
        'barang_habis_pakai' => get_items_by_type('habis_pakai'),
        'total_permintaan' => get_total_count('permintaan_barang'),
        'total_peminjaman' => get_total_count('peminjaman')
    ];
}

// Get pending requests for admin
$pending_requests = [];
if ($role === 'admin') {
    // Get pending item requests
    $query_permintaan = "SELECT pb.*, b.nama_barang, u.nama_lengkap 
                        FROM permintaan_barang pb
                        JOIN barang b ON pb.id_barang = b.id_barang
                        JOIN users u ON pb.id_user = u.id_user
                        WHERE pb.status = 'pending'
                        ORDER BY pb.created_at DESC
                        LIMIT 5";
    $pending_requests['permintaan'] = query($query_permintaan);

    // Get pending borrowing requests
    $query_peminjaman = "SELECT p.*, b.nama_barang, u.nama_lengkap 
                        FROM peminjaman p
                        JOIN barang b ON p.id_barang = b.id_barang
                        JOIN users u ON p.id_user = u.id_user
                        WHERE p.status = 'pending'
                        ORDER BY p.created_at DESC
                        LIMIT 5";
    $pending_requests['peminjaman'] = query($query_peminjaman);
}

// Get user's requests
$user_requests = [];
if (in_array($role, ['mahasiswa', 'dosen', 'staff'])) {
    $user_id = $_SESSION['user_id'];
    
    // Get user's item requests
    $query_permintaan = "SELECT pb.*, b.nama_barang 
                        FROM permintaan_barang pb
                        JOIN barang b ON pb.id_barang = b.id_barang
                        WHERE pb.id_user = $user_id
                        ORDER BY pb.created_at DESC
                        LIMIT 5";
    $user_requests['permintaan'] = query($query_permintaan);

    // Get user's borrowing requests
    $query_peminjaman = "SELECT p.*, b.nama_barang 
                        FROM peminjaman p
                        JOIN barang b ON p.id_barang = b.id_barang
                        WHERE p.id_user = $user_id
                        ORDER BY p.created_at DESC
                        LIMIT 5";
    $user_requests['peminjaman'] = query($query_peminjaman);
}
?>

<!-- Dashboard Content -->
<div class="space-y-6">
    <?php if (in_array($role, ['admin', 'kepala_gudang'])): ?>
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-500 bg-opacity-10">
                    <i class="fas fa-boxes text-2xl text-blue-500"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Total Barang</p>
                    <p class="text-xl font-semibold"><?php echo $stats['total_barang']; ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-500 bg-opacity-10">
                    <i class="fas fa-archive text-2xl text-green-500"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Barang Tetap</p>
                    <p class="text-xl font-semibold"><?php echo $stats['barang_tetap']; ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-500 bg-opacity-10">
                    <i class="fas fa-box-open text-2xl text-yellow-500"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Barang Habis Pakai</p>
                    <p class="text-xl font-semibold"><?php echo $stats['barang_habis_pakai']; ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-500 bg-opacity-10">
                    <i class="fas fa-file-alt text-2xl text-purple-500"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Total Pengajuan</p>
                    <p class="text-xl font-semibold"><?php echo $stats['total_permintaan'] + $stats['total_peminjaman']; ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <?php if ($role === 'admin'): ?>
        <!-- Pending Requests -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b">
                <h2 class="text-lg font-semibold">Pengajuan Menunggu Persetujuan</h2>
            </div>
            <div class="p-4">
                <div class="space-y-4">
                    <?php if (num_rows($pending_requests['permintaan']) > 0): ?>
                    <div>
                        <h3 class="text-md font-medium mb-2">Permintaan Barang</h3>
                        <div class="space-y-2">
                            <?php while ($row = fetch_assoc($pending_requests['permintaan'])): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                <div>
                                    <p class="font-medium"><?php echo $row['nama_lengkap']; ?></p>
                                    <p class="text-sm text-gray-600"><?php echo $row['nama_barang']; ?> (<?php echo $row['jumlah']; ?>)</p>
                                </div>
                                <a href="?module=permintaan&action=form_ubah&id=<?php echo $row['id_permintaan']; ?>" 
                                   class="text-blue-500 hover:text-blue-700">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (num_rows($pending_requests['peminjaman']) > 0): ?>
                    <div>
                        <h3 class="text-md font-medium mb-2">Peminjaman Barang</h3>
                        <div class="space-y-2">
                            <?php while ($row = fetch_assoc($pending_requests['peminjaman'])): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                <div>
                                    <p class="font-medium"><?php echo $row['nama_lengkap']; ?></p>
                                    <p class="text-sm text-gray-600"><?php echo $row['nama_barang']; ?> (<?php echo $row['jumlah']; ?>)</p>
                                </div>
                                <a href="?module=peminjaman&action=form_ubah&id=<?php echo $row['id_peminjaman']; ?>" 
                                   class="text-blue-500 hover:text-blue-700">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (in_array($role, ['admin', 'kepala_gudang'])): ?>
        <!-- Low Stock Items -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b">
                <h2 class="text-lg font-semibold">Stok Menipis</h2>
            </div>
            <div class="p-4">
                <div class="space-y-2">
                    <?php 
                    $low_stock = get_low_stock_items();
                    while ($row = fetch_assoc($low_stock)): 
                    ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                        <div>
                            <p class="font-medium"><?php echo $row['nama_barang']; ?></p>
                            <p class="text-sm text-gray-600">
                                Stok: <?php echo $row['stok']; ?> <?php echo $row['nama_satuan']; ?>
                                (Min: <?php echo $row['minimal_stok']; ?>)
                            </p>
                        </div>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                            <?php echo $row['stok'] == 0 ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                            <?php echo $row['stok'] == 0 ? 'Habis' : 'Menipis'; ?>
                        </span>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (in_array($role, ['mahasiswa', 'dosen', 'staff'])): ?>
        <!-- User's Requests -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b">
                <h2 class="text-lg font-semibold">Riwayat Pengajuan</h2>
            </div>
            <div class="p-4">
                <div class="space-y-4">
                    <?php if (num_rows($user_requests['permintaan']) > 0): ?>
                    <div>
                        <h3 class="text-md font-medium mb-2">Permintaan Barang</h3>
                        <div class="space-y-2">
                            <?php while ($row = fetch_assoc($user_requests['permintaan'])): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                <div>
                                    <p class="font-medium"><?php echo $row['nama_barang']; ?></p>
                                    <p class="text-sm text-gray-600">Jumlah: <?php echo $row['jumlah']; ?></p>
                                </div>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    <?php 
                                    switch($row['status']) {
                                        case 'pending':
                                            echo 'bg-yellow-100 text-yellow-800';
                                            break;
                                        case 'disetujui':
                                            echo 'bg-green-100 text-green-800';
                                            break;
                                        case 'ditolak':
                                            echo 'bg-red-100 text-red-800';
                                            break;
                                    }
                                    ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (num_rows($user_requests['peminjaman']) > 0): ?>
                    <div>
                        <h3 class="text-md font-medium mb-2">Peminjaman Barang</h3>
                        <div class="space-y-2">
                            <?php while ($row = fetch_assoc($user_requests['peminjaman'])): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                <div>
                                    <p class="font-medium"><?php echo $row['nama_barang']; ?></p>
                                    <p class="text-sm text-gray-600">
                                        Jumlah: <?php echo $row['jumlah']; ?><br>
                                        <?php echo tanggal_indo($row['tanggal_pinjam']); ?>
                                    </p>
                                </div>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    <?php 
                                    switch($row['status']) {
                                        case 'pending':
                                            echo 'bg-yellow-100 text-yellow-800';
                                            break;
                                        case 'dipinjam':
                                            echo 'bg-blue-100 text-blue-800';
                                            break;
                                        case 'dikembalikan':
                                            echo 'bg-green-100 text-green-800';
                                            break;
                                        case 'ditolak':
                                            echo 'bg-red-100 text-red-800';
                                            break;
                                    }
                                    ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (in_array($role, ['admin', 'kepala_gudang'])): ?>
        <!-- Recent Transactions -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-4 border-b">
                <h2 class="text-lg font-semibold">Transaksi Terbaru</h2>
            </div>
            <div class="p-4">
                <div class="space-y-2">
                    <?php 
                    $transactions = get_recent_transactions();
                    while ($row = fetch_assoc($transactions)): 
                    ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                        <div>
                            <p class="font-medium"><?php echo $row['nama_barang']; ?></p>
                            <p class="text-sm text-gray-600">
                                <?php echo $row['jumlah']; ?> unit - 
                                <?php echo tanggal_indo($row['tanggal']); ?>
                            </p>
                            <p class="text-xs text-gray-500"><?php echo $row['pengguna']; ?></p>
                        </div>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                            <?php echo $row['tipe'] === 'masuk' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo $row['tipe'] === 'masuk' ? 'Masuk' : 'Keluar'; ?>
                        </span>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>