<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

// Get category ID from URL
$id_jenis = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_jenis > 0) {
    // Check if category exists
    $check_query = "SELECT id_jenis FROM jenis_barang WHERE id_jenis = $id_jenis";
    $check_result = query($check_query);

    if (num_rows($check_result) === 0) {
        echo "<script>
            alert('Data jenis barang tidak ditemukan!');
            window.location.href = '?module=jenis';
        </script>";
        exit();
    }

    // Check if category is being used by any items
    $check_items = "SELECT COUNT(*) as total FROM barang WHERE id_jenis = $id_jenis";
    $result_items = query($check_items);
    $data_items = fetch_assoc($result_items);

    if ($data_items['total'] > 0) {
        echo "<script>
            alert('Jenis barang tidak dapat dihapus karena sedang digunakan oleh " . $data_items['total'] . " barang!');
            window.location.href = '?module=jenis';
        </script>";
        exit();
    }

    // Delete category
    $query = "DELETE FROM jenis_barang WHERE id_jenis = $id_jenis";
    
    if (query($query)) {
        header("Location: ?module=jenis&success=Data jenis barang berhasil dihapus");
    } else {
        echo "<script>
            alert('Gagal menghapus data jenis barang!');
            window.location.href = '?module=jenis';
        </script>";
    }
} else {
    header("Location: ?module=jenis");
}
?>