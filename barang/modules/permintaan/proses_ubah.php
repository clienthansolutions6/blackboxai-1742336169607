<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_permintaan = (int)$_POST['id_permintaan'];
    $id_barang = (int)$_POST['id_barang'];
    $jumlah = (int)$_POST['jumlah'];
    $tujuan_penggunaan = escape_string($_POST['tujuan_penggunaan']);
    $keterangan = escape_string($_POST['keterangan']);

    // Validate required fields
    if (empty($id_permintaan) || empty($id_barang) || empty($jumlah) || empty($tujuan_penggunaan)) {
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

    // Check if request exists and can be edited
    $query_check = "SELECT pb.*, b.jenis_item,
                   (SELECT SUM(bl.jumlah) FROM barang_lokasi bl WHERE bl.id_barang = b.id_barang) as total_stok
                   FROM permintaan_barang pb
                   JOIN barang b ON pb.id_barang = b.id_barang
                   WHERE pb.id_permintaan = $id_permintaan";
    $result_check = query($query_check);

    if (num_rows($result_check) === 0) {
        echo "<script>
            alert('Data permintaan tidak ditemukan!');
            window.location.href = '?module=permintaan';
        </script>";
        exit();
    }

    $data_permintaan = fetch_assoc($result_check);

    // Check if user has permission to edit
    if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] !== $data_permintaan['id_user']) {
        echo "<script>
            alert('Anda tidak memiliki akses untuk mengubah permintaan ini!');
            window.location.href = '?module=permintaan';
        </script>";
        exit();
    }

    // Check if request can be edited
    if ($data_permintaan['status'] !== 'pending') {
        echo "<script>
            alert('Permintaan yang sudah diproses tidak dapat diubah!');
            window.location.href = '?module=permintaan';
        </script>";
        exit();
    }

    // Check if item is consumable
    if ($data_permintaan['jenis_item'] !== 'habis_pakai') {
        echo "<script>
            alert('Hanya barang habis pakai yang dapat diminta!');
            window.location.href = '?module=permintaan';
        </script>";
        exit();
    }

    // Check if requested quantity is reasonable (not more than current stock + 50%)
    $max_request = ceil($data_permintaan['total_stok'] * 1.5);
    if ($jumlah > $max_request) {
        echo "<script>
            alert('Jumlah permintaan terlalu besar!');
            window.history.back();
        </script>";
        exit();
    }

    // Start transaction
    query("START TRANSACTION");

    try {
        // Update request
        $query = "UPDATE permintaan_barang SET 
                  jumlah = $jumlah,
                  tujuan_penggunaan = '$tujuan_penggunaan',
                  keterangan = " . ($keterangan ? "'$keterangan'" : "NULL") . ",
                  updated_at = NOW()
                  WHERE id_permintaan = $id_permintaan";

        if (!query($query)) {
            throw new Exception("Gagal memperbarui permintaan barang");
        }

        // Log the change
        $query_log = "INSERT INTO log_aktivitas (
                        id_user,
                        tipe_aktivitas,
                        id_referensi,
                        keterangan,
                        created_at
                    ) VALUES (
                        {$_SESSION['user_id']},
                        'edit_permintaan',
                        $id_permintaan,
                        'Mengubah permintaan barang',
                        NOW()
                    )";

        if (!query($query_log)) {
            throw new Exception("Gagal mencatat log aktivitas");
        }

        // Commit transaction
        query("COMMIT");

        // Send notification to admin if significant changes (if implemented)
        // if ($jumlah != $data_permintaan['jumlah']) {
        //     sendNotificationToAdmin($id_barang, $jumlah, $data_permintaan['jumlah']);
        // }

        header("Location: ?module=permintaan&success=Permintaan barang berhasil diperbarui");

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