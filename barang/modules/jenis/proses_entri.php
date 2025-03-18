<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    // Check if category name already exists
    $check_query = "SELECT id_jenis FROM jenis_barang WHERE nama_jenis = '$nama_jenis'";
    $check_result = query($check_query);

    if (num_rows($check_result) > 0) {
        echo "<script>
            alert('Nama jenis barang sudah ada!');
            window.history.back();
        </script>";
        exit();
    }

    // Insert new category
    $query = "INSERT INTO jenis_barang (nama_jenis, keterangan) 
              VALUES ('$nama_jenis', " . ($keterangan ? "'$keterangan'" : "NULL") . ")";

    if (query($query)) {
        header("Location: ?module=jenis&success=Data jenis barang berhasil ditambahkan");
    } else {
        echo "<script>
            alert('Gagal menambahkan jenis barang!');
            window.history.back();
        </script>";
    }
} else {
    header("Location: ?module=jenis");
}
?>