<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

// Get user ID from URL
$id_user = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_user > 0) {
    // Check if user exists and is not the current user
    $check_query = "SELECT id_user FROM users WHERE id_user = $id_user AND id_user != {$_SESSION['user_id']}";
    $check_result = query($check_query);

    if (num_rows($check_result) === 0) {
        echo "<script>
            alert('Data pengguna tidak ditemukan atau tidak dapat dihapus!');
            window.location.href = '?module=user';
        </script>";
        exit();
    }

    // Check if user has any related records
    $check_permintaan = "SELECT id_permintaan FROM permintaan_barang WHERE id_user = $id_user LIMIT 1";
    $check_peminjaman = "SELECT id_peminjaman FROM peminjaman WHERE id_user = $id_user LIMIT 1";
    $check_barang_masuk = "SELECT id_barang_masuk FROM barang_masuk WHERE created_by = $id_user LIMIT 1";
    $check_barang_keluar = "SELECT id_barang_keluar FROM barang_keluar WHERE created_by = $id_user LIMIT 1";

    $has_permintaan = num_rows(query($check_permintaan)) > 0;
    $has_peminjaman = num_rows(query($check_peminjaman)) > 0;
    $has_barang_masuk = num_rows(query($check_barang_masuk)) > 0;
    $has_barang_keluar = num_rows(query($check_barang_keluar)) > 0;

    if ($has_permintaan || $has_peminjaman || $has_barang_masuk || $has_barang_keluar) {
        echo "<script>
            alert('Pengguna tidak dapat dihapus karena memiliki data terkait!');
            window.location.href = '?module=user';
        </script>";
        exit();
    }

    // Delete user
    $query = "DELETE FROM users WHERE id_user = $id_user";
    
    if (query($query)) {
        header("Location: ?module=user&success=Data pengguna berhasil dihapus");
    } else {
        echo "<script>
            alert('Gagal menghapus data pengguna!');
            window.location.href = '?module=user';
        </script>";
    }
} else {
    header("Location: ?module=user");
}
?>