<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_barang = (int)$_POST['id_barang'];
    $lokasi_peminjaman = (int)$_POST['lokasi_peminjaman'];
    $tanggal_pinjam = escape_string($_POST['tanggal_pinjam']);
    $tanggal_kembali = escape_string($_POST['tanggal_kembali']);
    $jumlah = (int)$_POST['jumlah'];
    $keperluan = escape_string($_POST['keperluan']);
    $keterangan = escape_string($_POST['keterangan']);

    // Validate required fields
    if (empty($id_barang) || empty($lokasi_peminjaman) || empty($tanggal_pinjam) || 
        empty($tanggal_kembali) || empty($jumlah) || empty($keperluan)) {
        echo "<script>
            alert('Semua field wajib diisi!');
            window.history.back();
        </script>";
        exit();
    }

    // Validate dates
    $today = date('Y-m-d');
    if ($tanggal_pinjam < $today) {
        echo "<script>
            alert('Tanggal pinjam tidak boleh kurang dari hari ini!');
            window.history.back();
        </script>";
        exit();
    }

    if ($tanggal_kembali <= $tanggal_pinjam) {
        echo "<script>
            alert('Tanggal kembali harus lebih dari tanggal pinjam!');
            window.history.back();
        </script>";
        exit();
    }

    // Check if item exists and is fixed asset
    $query_check = "SELECT b.*, 
                   (SELECT SUM(bl.jumlah) FROM barang_lokasi bl WHERE bl.id_barang = b.id_barang) as total_stok
                   FROM barang b 
                   WHERE b.id_barang = $id_barang AND b.jenis_item = 'tetap'";
    $result_check = query($query_check);

    if (num_rows($result_check) === 0) {
        echo "<script>
            alert('Barang tidak ditemukan atau bukan barang tetap!');
            window.history.back();
        </script>";
        exit();
    }

    $data_barang = fetch_assoc($result_check);

    // Check if stock is available in selected location
    $query_stok = "SELECT jumlah FROM barang_lokasi 
                   WHERE id_barang = $id_barang AND id_lokasi = $lokasi_peminjaman";
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

    // Check if user has active loans for the same item
    $query_active = "SELECT COUNT(*) as total 
                     FROM peminjaman 
                     WHERE id_barang = $id_barang 
                     AND id_user = {$_SESSION['user_id']}
                     AND status IN ('pending', 'dipinjam')";
    $result_active = query($query_active);
    $data_active = fetch_assoc($result_active);

    if ($data_active['total'] > 0) {
        echo "<script>
            alert('Anda masih memiliki peminjaman aktif untuk barang ini!');
            window.history.back();
        </script>";
        exit();
    }

    // Start transaction
    query("START TRANSACTION");

    try {
        // Insert loan record
        $query = "INSERT INTO peminjaman (
                    id_barang,
                    id_user,
                    lokasi_peminjaman,
                    tanggal_pinjam,
                    tanggal_kembali,
                    jumlah,
                    keperluan,
                    keterangan,
                    status
                ) VALUES (
                    $id_barang,
                    {$_SESSION['user_id']},
                    $lokasi_peminjaman,
                    '$tanggal_pinjam',
                    '$tanggal_kembali',
                    $jumlah,
                    '$keperluan',
                    " . ($keterangan ? "'$keterangan'" : "NULL") . ",
                    'pending'
                )";

        if (!query($query)) {
            throw new Exception("Gagal menyimpan data peminjaman");
        }

        // Commit transaction
        query("COMMIT");

        // Send notification to admin (if implemented)
        // sendNotificationToAdmin($id_barang, $jumlah);

        header("Location: ?module=peminjaman&success=Peminjaman barang berhasil diajukan");

    } catch (Exception $e) {
        // Rollback on error
        query("ROLLBACK");
        echo "<script>
            alert('" . $e->getMessage() . "');
            window.history.back();
        </script>";
    }
} else {
    header("Location: ?module=peminjaman");
}
?>