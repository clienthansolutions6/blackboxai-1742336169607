<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

if (isset($_GET['id']) && isset($_GET['condition'])) {
    $id_peminjaman = (int)$_GET['id'];
    $condition = escape_string($_GET['condition']);

    // Check if loan exists and can be returned
    $query_check = "SELECT p.*, b.nama_barang
                   FROM peminjaman p
                   JOIN barang b ON p.id_barang = b.id_barang
                   WHERE p.id_peminjaman = $id_peminjaman";
    $result_check = query($query_check);

    if (num_rows($result_check) === 0) {
        echo "<script>
            alert('Data peminjaman tidak ditemukan!');
            window.location.href = '?module=peminjaman';
        </script>";
        exit();
    }

    $data_peminjaman = fetch_assoc($result_check);

    // Check if loan is active
    if ($data_peminjaman['status'] !== 'dipinjam' && $data_peminjaman['status'] !== 'terlambat') {
        echo "<script>
            alert('Peminjaman ini tidak dalam status dipinjam!');
            window.location.href = '?module=peminjaman';
        </script>";
        exit();
    }

    // Start transaction
    query("START TRANSACTION");

    try {
        // Return stock to location
        $query_update = "UPDATE barang_lokasi 
                       SET jumlah = jumlah + {$data_peminjaman['jumlah']} 
                       WHERE id_barang = {$data_peminjaman['id_barang']} 
                       AND id_lokasi = {$data_peminjaman['lokasi_peminjaman']}";
        
        if (!query($query_update)) {
            // If location doesn't exist anymore, create new entry
            $query_insert = "INSERT INTO barang_lokasi (id_barang, id_lokasi, jumlah) 
                           VALUES ({$data_peminjaman['id_barang']}, 
                                   {$data_peminjaman['lokasi_peminjaman']}, 
                                   {$data_peminjaman['jumlah']})";
            
            if (!query($query_insert)) {
                throw new Exception("Gagal mengembalikan stok ke lokasi");
            }
        }

        // Update total stock in barang table
        $query_total = "UPDATE barang b 
                       SET stok = (
                           SELECT COALESCE(SUM(jumlah), 0) 
                           FROM barang_lokasi bl 
                           WHERE bl.id_barang = b.id_barang
                       ) 
                       WHERE id_barang = {$data_peminjaman['id_barang']}";
        
        if (!query($query_total)) {
            throw new Exception("Gagal memperbarui total stok");
        }

        // Calculate late days
        $late_days = 0;
        $today = new DateTime();
        $return_date = new DateTime($data_peminjaman['tanggal_kembali']);
        if ($today > $return_date) {
            $late_days = $today->diff($return_date)->days;
        }

        // Update loan status
        $query = "UPDATE peminjaman SET 
                  status = 'dikembalikan',
                  tanggal_pengembalian = CURRENT_DATE(),
                  kondisi_pengembalian = '$condition',
                  keterlambatan = $late_days,
                  return_note = " . ($late_days > 0 ? "'Terlambat $late_days hari'" : "NULL") . ",
                  returned_by = {$_SESSION['user_id']},
                  updated_at = NOW()
                  WHERE id_peminjaman = $id_peminjaman";

        if (!query($query)) {
            throw new Exception("Gagal memperbarui status peminjaman");
        }

        // Log the return
        $query_log = "INSERT INTO log_aktivitas (
                        id_user,
                        tipe_aktivitas,
                        id_referensi,
                        keterangan,
                        created_at
                    ) VALUES (
                        {$_SESSION['user_id']},
                        'pengembalian',
                        $id_peminjaman,
                        'Pengembalian barang: {$data_peminjaman['nama_barang']} - Kondisi: $condition" . 
                        ($late_days > 0 ? " - Terlambat $late_days hari" : "") . "',
                        NOW()
                    )";

        if (!query($query_log)) {
            throw new Exception("Gagal mencatat log aktivitas");
        }

        // Commit transaction
        query("COMMIT");

        // Send notification to user (if implemented)
        // sendNotificationToUser($data_peminjaman['id_user'], 'return', $condition);

        $success_msg = "Pengembalian barang berhasil diproses.";
        if ($late_days > 0) {
            $success_msg .= " Keterlambatan: $late_days hari.";
        }

        header("Location: ?module=peminjaman&success=" . urlencode($success_msg));

    } catch (Exception $e) {
        // Rollback on error
        query("ROLLBACK");
        echo "<script>
            alert('" . $e->getMessage() . "');
            window.location.href = '?module=peminjaman';
        </script>";
    }
} else {
    header("Location: ?module=peminjaman");
}
?>