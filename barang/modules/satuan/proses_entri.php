<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    // Check if unit name already exists
    $check_query = "SELECT id_satuan FROM satuan WHERE nama_satuan = '$nama_satuan'";
    $check_result = query($check_query);

    if (num_rows($check_result) > 0) {
        echo "<script>
            alert('Nama satuan sudah ada!');
            window.history.back();
        </script>";
        exit();
    }

    // Insert new unit
    $query = "INSERT INTO satuan (nama_satuan, keterangan) 
              VALUES ('$nama_satuan', " . ($keterangan ? "'$keterangan'" : "NULL") . ")";

    if (query($query)) {
        header("Location: ?module=satuan&success=Data satuan berhasil ditambahkan");
    } else {
        echo "<script>
            alert('Gagal menambahkan satuan!');
            window.history.back();
        </script>";
    }
} else {
    header("Location: ?module=satuan");
}
?>