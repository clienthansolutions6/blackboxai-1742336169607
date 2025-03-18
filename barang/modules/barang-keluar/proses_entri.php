<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_barang = (int)$_POST['id_barang'];
    $id_lokasi = (int)$_POST['id_lokasi'];
    $tanggal_keluar = escape_string($_POST['tanggal_keluar']);
    $jumlah = (int)$_POST['jumlah'];
    $tujuan = escape_string($_POST['tujuan']);
    $keterangan = escape_string($_POST['keterangan']);

    // Validate required fields
    if (empty($id_barang) || empty($id_lokasi) || empty($tanggal_keluar) || empty($jumlah) || empty($tujuan)) {
        echo "<script>
            alert('Semua field wajib diisi!');
            window.history.back();
        </script>";
        exit();
    }

    // Validate date
    if ($tanggal_keluar > date('Y-m-d')) {
        echo "<script>
            alert('Tanggal tidak boleh lebih dari hari ini!');
            window.history.back();
        </script>";
        exit();
    }

    // Validate quantity
    if ($jumlah < 1) {
        echo "<script>
            alert('Jumlah harus lebih dari 0!');
            window.history.back();
        </script>";
        exit();
    }

    // Check available stock in location
    $query_stok = "SELECT jumlah FROM barang_lokasi 
                   WHERE id_barang = $id_barang AND id_lokasi = $id_lokasi";
    $result_stok = query($query_stok);
    
    if (num_rows($result_stok) === 0) {
        echo "<script>
            alert('Barang tidak tersedia di lokasi yang dipilih!');
            window.history.back();
        </script>";
        exit();
    }

    $data_stok = fetch_assoc($result_stok);
    if ($jumlah > $data_stok['jumlah']) {
        echo "<script>
            alert('Jumlah melebihi stok yang tersedia di lokasi ini!');
            window.history.back();
        </script>";
        exit();
    }

    // Start transaction
    query("START TRANSACTION");

    try {
        // Insert outgoing item record
        $query = "INSERT INTO barang_keluar (id_barang, id_lokasi, tanggal_keluar, jumlah, tujuan, keterangan, created_by) 
                  VALUES ($id_barang, $id_lokasi, '$tanggal_keluar', $jumlah, '$tujuan', " . 
                  ($keterangan ? "'$keterangan'" : "NULL") . ", {$_SESSION['user_id']})";

        if (!query($query)) {
            throw new Exception("Gagal menambahkan data barang keluar");
        }

        // Update stock in location
        $query_update = "UPDATE barang_lokasi 
                        SET jumlah = jumlah - $jumlah 
                        WHERE id_barang = $id_barang AND id_lokasi = $id_lokasi";
        
        if (!query($query_update)) {
            throw new Exception("Gagal memperbarui stok di lokasi");
        }

        // Clean up any locations with zero quantity
        $query_cleanup = "DELETE FROM barang_lokasi WHERE jumlah <= 0";
        if (!query($query_cleanup)) {
            throw new Exception("Gagal membersihkan data stok kosong");
        }

        // Update total stock in barang table
        $query_total = "UPDATE barang b 
                       SET stok = (
                           SELECT COALESCE(SUM(jumlah), 0) 
                           FROM barang_lokasi bl 
                           WHERE bl.id_barang = b.id_barang
                       ) 
                       WHERE id_barang = $id_barang";
        
        if (!query($query_total)) {
            throw new Exception("Gagal memperbarui total stok");
        }

        // Commit transaction
        query("COMMIT");
        header("Location: ?module=barang-keluar&success=Data barang keluar berhasil ditambahkan");

    } catch (Exception $e) {
        // Rollback on error
        query("ROLLBACK");
        echo "<script>
            alert('" . $e->getMessage() . "');
            window.history.back();
        </script>";
    }
} else {
    header("Location: ?module=barang-keluar");
}
?>