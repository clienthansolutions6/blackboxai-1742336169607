<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_lokasi = (int)$_POST['id_lokasi'];
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

    // Check if location exists
    $check_exists = "SELECT id_lokasi FROM lokasi WHERE id_lokasi = $id_lokasi";
    $result_exists = query($check_exists);

    if (num_rows($result_exists) === 0) {
        echo "<script>
            alert('Data lokasi tidak ditemukan!');
            window.location.href = '?module=lokasi';
        </script>";
        exit();
    }

    // Check if location name already exists (excluding current location)
    $check_query = "SELECT id_lokasi FROM lokasi WHERE nama_lokasi = '$nama_lokasi' AND id_lokasi != $id_lokasi";
    $check_result = query($check_query);

    if (num_rows($check_result) > 0) {
        echo "<script>
            alert('Nama lokasi sudah ada!');
            window.history.back();
        </script>";
        exit();
    }

    // Update location
    $query = "UPDATE lokasi SET 
              nama_lokasi = '$nama_lokasi',
              keterangan = " . ($keterangan ? "'$keterangan'" : "NULL") . "
              WHERE id_lokasi = $id_lokasi";

    if (query($query)) {
        header("Location: ?module=lokasi&success=Data lokasi berhasil diperbarui");
    } else {
        echo "<script>
            alert('Gagal memperbarui data lokasi!');
            window.history.back();
        </script>";
    }
} else {
    header("Location: ?module=lokasi");
}
?>