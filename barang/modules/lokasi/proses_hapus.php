<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

// Get location ID from URL
$id_lokasi = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_lokasi > 0) {
    // Check if location exists
    $check_query = "SELECT id_lokasi FROM lokasi WHERE id_lokasi = $id_lokasi";
    $check_result = query($check_query);

    if (num_rows($check_result) === 0) {
        echo "<script>
            alert('Data lokasi tidak ditemukan!');
            window.location.href = '?module=lokasi';
        </script>";
        exit();
    }

    // Check if location has any items
    $check_items = "SELECT COUNT(*) as total FROM barang_lokasi WHERE id_lokasi = $id_lokasi";
    $result_items = query($check_items);
    $data_items = fetch_assoc($result_items);

    if ($data_items['total'] > 0) {
        echo "<script>
            alert('Lokasi tidak dapat dihapus karena masih menyimpan " . $data_items['total'] . " barang!');
            window.location.href = '?module=lokasi';
        </script>";
        exit();
    }

    // Check if location is being used in peminjaman
    $check_peminjaman = "SELECT COUNT(*) as total FROM peminjaman WHERE lokasi_peminjaman = '$id_lokasi'";
    $result_peminjaman = query($check_peminjaman);
    $data_peminjaman = fetch_assoc($result_peminjaman);

    if ($data_peminjaman['total'] > 0) {
        echo "<script>
            alert('Lokasi tidak dapat dihapus karena terkait dengan data peminjaman!');
            window.location.href = '?module=lokasi';
        </script>";
        exit();
    }

    // Delete location
    $query = "DELETE FROM lokasi WHERE id_lokasi = $id_lokasi";
    
    if (query($query)) {
        header("Location: ?module=lokasi&success=Data lokasi berhasil dihapus");
    } else {
        echo "<script>
            alert('Gagal menghapus data lokasi!');
            window.location.href = '?module=lokasi';
        </script>";
    }
} else {
    header("Location: ?module=lokasi");
}
?>