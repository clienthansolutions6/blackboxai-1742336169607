<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

// Get unit ID from URL
$id_satuan = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_satuan > 0) {
    // Check if unit exists
    $check_query = "SELECT id_satuan FROM satuan WHERE id_satuan = $id_satuan";
    $check_result = query($check_query);

    if (num_rows($check_result) === 0) {
        echo "<script>
            alert('Data satuan tidak ditemukan!');
            window.location.href = '?module=satuan';
        </script>";
        exit();
    }

    // Check if unit is being used by any items
    $check_items = "SELECT COUNT(*) as total FROM barang WHERE id_satuan = $id_satuan";
    $result_items = query($check_items);
    $data_items = fetch_assoc($result_items);

    if ($data_items['total'] > 0) {
        echo "<script>
            alert('Satuan tidak dapat dihapus karena sedang digunakan oleh " . $data_items['total'] . " barang!');
            window.location.href = '?module=satuan';
        </script>";
        exit();
    }

    // Delete unit
    $query = "DELETE FROM satuan WHERE id_satuan = $id_satuan";
    
    if (query($query)) {
        header("Location: ?module=satuan&success=Data satuan berhasil dihapus");
    } else {
        echo "<script>
            alert('Gagal menghapus data satuan!');
            window.location.href = '?module=satuan';
        </script>";
    }
} else {
    header("Location: ?module=satuan");
}
?>