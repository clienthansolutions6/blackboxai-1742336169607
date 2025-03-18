<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_barang = (int)$_POST['id_barang'];
    $jumlah = (int)$_POST['jumlah'];
    $tujuan_penggunaan = escape_string($_POST['tujuan_penggunaan']);
    $keterangan = escape_string($_POST['keterangan']);

    // Validate required fields
    if (empty($id_barang) || empty($jumlah) || empty($tujuan_penggunaan)) {
        echo "<script>
            alert('Semua field wajib diisi!');
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

    // Check if item exists and is consumable
    $query_check = "SELECT b.*, 
                   (SELECT SUM(bl.jumlah) FROM barang_lokasi bl WHERE bl.id_barang = b.id_barang) as total_stok
                   FROM barang b 
                   WHERE b.id_barang = $id_barang AND b.jenis_item = 'habis_pakai'";
    $result_check = query($query_check);

    if (num_rows($result_check) === 0) {
        echo "<script>
            alert('Barang tidak ditemukan atau bukan barang habis pakai!');
            window.history.back();
        </script>";
        exit();
    }

    $data_barang = fetch_assoc($result_check);

    // Check if requested quantity is reasonable (not more than current stock + 50%)
    $max_request = ceil($data_barang['total_stok'] * 1.5);
    if ($jumlah > $max_request) {
        echo "<script>
            alert('Jumlah permintaan terlalu besar!');
            window.history.back();
        </script>";
        exit();
    }

    // Check if user has pending request for the same item
    $query_pending = "SELECT COUNT(*) as total 
                     FROM permintaan_barang 
                     WHERE id_barang = $id_barang 
                     AND id_user = {$_SESSION['user_id']}
                     AND status = 'pending'";
    $result_pending = query($query_pending);
    $data_pending = fetch_assoc($result_pending);

    if ($data_pending['total'] > 0) {
        echo "<script>
            alert('Anda masih memiliki permintaan yang belum diproses untuk barang ini!');
            window.history.back();
        </script>";
        exit();
    }

    // Start transaction
    query("START TRANSACTION");

    try {
        // Insert request
        $query = "INSERT INTO permintaan_barang (
                    id_barang, 
                    id_user, 
                    jumlah, 
                    tujuan_penggunaan, 
                    keterangan, 
                    status
                ) VALUES (
                    $id_barang,
                    {$_SESSION['user_id']},
                    $jumlah,
                    '$tujuan_penggunaan',
                    " . ($keterangan ? "'$keterangan'" : "NULL") . ",
                    'pending'
                )";

        if (!query($query)) {
            throw new Exception("Gagal menyimpan permintaan barang");
        }

        // Commit transaction
        query("COMMIT");

        // Send notification to admin (if implemented)
        // sendNotificationToAdmin($id_barang, $jumlah);

        header("Location: ?module=permintaan&success=Permintaan barang berhasil diajukan");

    } catch (Exception $e) {
        // Rollback on error
        query("ROLLBACK");
        echo "<script>
            alert('" . $e->getMessage() . "');
            window.history.back();
        </script>";
    }
} else {
    header("Location: ?module=permintaan");
}
?>