<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

// Get item ID from URL
$id_barang = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_barang > 0) {
    // Check if item exists
    $check_query = "SELECT id_barang, nama_barang FROM barang WHERE id_barang = $id_barang";
    $check_result = query($check_query);

    if (num_rows($check_result) === 0) {
        echo "<script>
            alert('Data barang tidak ditemukan!');
            window.location.href = '?module=barang';
        </script>";
        exit();
    }

    $data_barang = fetch_assoc($check_result);

    // Check related records
    $checks = [
        'permintaan' => "SELECT COUNT(*) as total FROM permintaan_barang WHERE id_barang = $id_barang",
        'peminjaman' => "SELECT COUNT(*) as total FROM peminjaman WHERE id_barang = $id_barang",
        'barang_masuk' => "SELECT COUNT(*) as total FROM barang_masuk WHERE id_barang = $id_barang",
        'barang_keluar' => "SELECT COUNT(*) as total FROM barang_keluar WHERE id_barang = $id_barang"
    ];

    $related_records = [];
    foreach ($checks as $type => $query) {
        $result = query($query);
        $data = fetch_assoc($result);
        if ($data['total'] > 0) {
            $related_records[$type] = $data['total'];
        }
    }

    // If there are related records, show error message
    if (!empty($related_records)) {
        $message = "Barang '{$data_barang['nama_barang']}' tidak dapat dihapus karena masih memiliki data terkait:\n\n";
        foreach ($related_records as $type => $count) {
            switch ($type) {
                case 'permintaan':
                    $message .= "- $count data permintaan barang\n";
                    break;
                case 'peminjaman':
                    $message .= "- $count data peminjaman\n";
                    break;
                case 'barang_masuk':
                    $message .= "- $count data barang masuk\n";
                    break;
                case 'barang_keluar':
                    $message .= "- $count data barang keluar\n";
                    break;
            }
        }
        
        echo "<script>
            alert('" . str_replace("'", "\\'", $message) . "');
            window.location.href = '?module=barang';
        </script>";
        exit();
    }

    // Start transaction
    query("START TRANSACTION");

    try {
        // Delete item locations first
        $query_lokasi = "DELETE FROM barang_lokasi WHERE id_barang = $id_barang";
        if (!query($query_lokasi)) {
            throw new Exception("Gagal menghapus data lokasi barang");
        }

        // Delete item
        $query = "DELETE FROM barang WHERE id_barang = $id_barang";
        if (!query($query)) {
            throw new Exception("Gagal menghapus data barang");
        }

        // Commit transaction
        query("COMMIT");
        header("Location: ?module=barang&success=Data barang berhasil dihapus");

    } catch (Exception $e) {
        // Rollback on error
        query("ROLLBACK");
        echo "<script>
            alert('" . $e->getMessage() . "');
            window.location.href = '?module=barang';
        </script>";
    }
} else {
    header("Location: ?module=barang");
}
?>