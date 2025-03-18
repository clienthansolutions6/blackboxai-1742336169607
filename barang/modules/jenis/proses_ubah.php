<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_jenis = (int)$_POST['id_jenis'];
    $nama_jenis = escape_string($_POST['nama_jenis']);
    $keterangan = escape_string($_POST['keterangan']);

    // Validate required fields
    if (empty($nama_jenis)) {
        echo "<script>
            alert('Nama jenis barang wajib diisi!');
            window.history.back();
        </script>";
        exit();
    }

    // Check if category exists
    $check_exists = "SELECT id_jenis FROM jenis_barang WHERE id_jenis = $id_jenis";
    $result_exists = query($check_exists);

    if (num_rows($result_exists) === 0) {
        echo "<script>
            alert('Data jenis barang tidak ditemukan!');
            window.location.href = '?module=jenis';
        </script>";
        exit();
    }

    // Check if category name already exists (excluding current category)
    $check_query = "SELECT id_jenis FROM jenis_barang WHERE nama_jenis = '$nama_jenis' AND id_jenis != $id_jenis";
    $check_result = query($check_query);

    if (num_rows($check_result) > 0) {
        echo "<script>
            alert('Nama jenis barang sudah ada!');
            window.history.back();
        </script>";
        exit();
    }

    // Update category
    $query = "UPDATE jenis_barang SET 
              nama_jenis = '$nama_jenis',
              keterangan = " . ($keterangan ? "'$keterangan'" : "NULL") . "
              WHERE id_jenis = $id_jenis";

    if (query($query)) {
        header("Location: ?module=jenis&success=Data jenis barang berhasil diperbarui");
    } else {
        echo "<script>
            alert('Gagal memperbarui data jenis barang!');
            window.history.back();
        </script>";
    }
} else {
    header("Location: ?module=jenis");
}
?>