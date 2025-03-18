<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_satuan = (int)$_POST['id_satuan'];
    $nama_satuan = strtoupper(escape_string($_POST['nama_satuan']));
    $keterangan = escape_string($_POST['keterangan']);

    // Validate required fields
    if (empty($nama_satuan)) {
        echo "<script>
            alert('Nama satuan wajib diisi!');
            window.history.back();
        </script>";
        exit();
    }

    // Check if unit exists
    $check_exists = "SELECT id_satuan FROM satuan WHERE id_satuan = $id_satuan";
    $result_exists = query($check_exists);

    if (num_rows($result_exists) === 0) {
        echo "<script>
            alert('Data satuan tidak ditemukan!');
            window.location.href = '?module=satuan';
        </script>";
        exit();
    }

    // Check if unit name already exists (excluding current unit)
    $check_query = "SELECT id_satuan FROM satuan WHERE nama_satuan = '$nama_satuan' AND id_satuan != $id_satuan";
    $check_result = query($check_query);

    if (num_rows($check_result) > 0) {
        echo "<script>
            alert('Nama satuan sudah ada!');
            window.history.back();
        </script>";
        exit();
    }

    // Update unit
    $query = "UPDATE satuan SET 
              nama_satuan = '$nama_satuan',
              keterangan = " . ($keterangan ? "'$keterangan'" : "NULL") . "
              WHERE id_satuan = $id_satuan";

    if (query($query)) {
        header("Location: ?module=satuan&success=Data satuan berhasil diperbarui");
    } else {
        echo "<script>
            alert('Gagal memperbarui data satuan!');
            window.history.back();
        </script>";
    }
} else {
    header("Location: ?module=satuan");
}
?>