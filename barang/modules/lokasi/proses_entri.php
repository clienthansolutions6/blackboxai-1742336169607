<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capitalize first letter of each word
    $nama_lokasi = ucwords(strtolower(escape_string($_POST['nama_lokasi'])));
    $keterangan = escape_string($_POST['keterangan']);

    // Validate required fields
    if (empty($nama_lokasi)) {
        echo "<script>
            alert('Nama lokasi wajib diisi!');
            window.history.back();
        </script>";
        exit();
    }

    // Check if location name already exists
    $check_query = "SELECT id_lokasi FROM lokasi WHERE nama_lokasi = '$nama_lokasi'";
    $check_result = query($check_query);

    if (num_rows($check_result) > 0) {
        echo "<script>
            alert('Nama lokasi sudah ada!');
            window.history.back();
        </script>";
        exit();
    }

    // Insert new location
    $query = "INSERT INTO lokasi (nama_lokasi, keterangan) 
              VALUES ('$nama_lokasi', " . ($keterangan ? "'$keterangan'" : "NULL") . ")";

    if (query($query)) {
        header("Location: ?module=lokasi&success=Data lokasi berhasil ditambahkan");
    } else {
        echo "<script>
            alert('Gagal menambahkan lokasi!');
            window.history.back();
        </script>";
    }
} else {
    header("Location: ?module=lokasi");
}
?>