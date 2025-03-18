<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

if (isset($_GET['id']) && isset($_GET['status'])) {
    $id_peminjaman = (int)$_GET['id'];
    $status = escape_string($_GET['status']);
    $reason = isset($_GET['reason']) ? escape_string($_GET['reason']) : '';

    // Validate status
    if (!in_array($status, ['dipinjam', 'ditolak'])) {
        echo "<script>
            alert('Status tidak valid!');
            window.location.href = '?module=peminjaman';
        </script>";
        exit();
    }

    // Check if loan exists and can be processed
    $query_check = "SELECT p.*, b.nama_barang, b.jenis_item,
                   (SELECT SUM(bl.jumlah) FROM barang_lokasi bl WHERE bl.id_barang = b.id_barang) as total_stok
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

    // Check if loan is still pending
    if ($data_peminjaman['status'] !== 'pending') {
        echo "<script>
            alert('Peminjaman ini sudah diproses sebelumnya!');
            window.location.href = '?module=peminjaman';
        </script>";
        exit();
    }

    // Start transaction
    query("START TRANSACTION");

    try {
        if ($status === 'dipinjam') {
            // Check if item is fixed asset
            if ($data_peminjaman['jenis_item'] !== 'tetap') {
                throw new Exception("Hanya barang tetap yang dapat dipinjamkan!");
            }

            // Check if stock is sufficient
            $query_stok = "SELECT jumlah FROM barang_lokasi 
                          WHERE id_barang = {$data_peminjaman['id_barang']} 
                          AND id_lokasi = {$data_peminjaman['lokasi_peminjaman']}";
            $result_stok = query($query_stok);
            
            if (num_rows($result_stok) === 0) {
                throw new Exception("Barang tidak tersedia di lokasi yang dipilih!");
            }

            $data_stok = fetch_assoc($result_stok);
            if ($data_peminjaman['jumlah'] > $data_stok['jumlah']) {
                throw new Exception("Stok tidak mencukupi di lokasi yang dipilih!");
            }

            // Update stock in location
            $query_update = "UPDATE barang_lokasi 
                           SET jumlah = jumlah - {$data_peminjaman['jumlah']} 
                           WHERE id_barang = {$data_peminjaman['id_barang']} 
                           AND id_lokasi = {$data_peminjaman['lokasi_peminjaman']}";
            
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
                           WHERE id_barang = {$data_peminjaman['id_barang']}";
            
            if (!query($query_total)) {
                throw new Exception("Gagal memperbarui total stok");
            }
        }

        // Update loan status
        $query = "UPDATE peminjaman SET 
                  status = '$status',
                  approved_by = {$_SESSION['user_id']},
                  approved_at = NOW(),
                  approval_note = " . ($status === 'ditolak' ? "'$reason'" : "NULL") . "
                  WHERE id_peminjaman = $id_peminjaman";

        if (!query($query)) {
            throw new Exception("Gagal memperbarui status peminjaman");
        }

        // Commit transaction
        query("COMMIT");

        // Send notification to user (if implemented)
        // sendNotificationToUser($data_peminjaman['id_user'], $status, $reason);

        header("Location: ?module=peminjaman&success=Peminjaman berhasil " . ($status === 'dipinjam' ? 'disetujui' : 'ditolak'));

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