<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

if (isset($_GET['id']) && isset($_GET['status'])) {
    $id_permintaan = (int)$_GET['id'];
    $status = escape_string($_GET['status']);
    $reason = isset($_GET['reason']) ? escape_string($_GET['reason']) : '';

    // Validate status
    if (!in_array($status, ['disetujui', 'ditolak'])) {
        echo "<script>
            alert('Status tidak valid!');
            window.location.href = '?module=permintaan';
        </script>";
        exit();
    }

    // Check if request exists and can be processed
    $query_check = "SELECT pb.*, b.nama_barang, b.jenis_item,
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

    // Check if request is still pending
    if ($data_permintaan['status'] !== 'pending') {
        echo "<script>
            alert('Permintaan ini sudah diproses sebelumnya!');
            window.location.href = '?module=permintaan';
        </script>";
        exit();
    }

    // Start transaction
    query("START TRANSACTION");

    try {
        if ($status === 'disetujui') {
            // Check if item is consumable
            if ($data_permintaan['jenis_item'] !== 'habis_pakai') {
                throw new Exception("Hanya barang habis pakai yang dapat disetujui!");
            }

            // Check if stock is sufficient
            if ($data_permintaan['jumlah'] > $data_permintaan['total_stok']) {
                throw new Exception("Stok barang tidak mencukupi!");
            }

            // Get available locations with stock
            $query_lokasi = "SELECT bl.id_lokasi, bl.jumlah, l.nama_lokasi
                            FROM barang_lokasi bl
                            JOIN lokasi l ON bl.id_lokasi = l.id_lokasi
                            WHERE bl.id_barang = {$data_permintaan['id_barang']}
                            AND bl.jumlah > 0
                            ORDER BY bl.jumlah DESC";
            $result_lokasi = query($query_lokasi);

            $remaining_qty = $data_permintaan['jumlah'];
            $locations_used = [];

            while ($lokasi = fetch_assoc($result_lokasi)) {
                if ($remaining_qty <= 0) break;

                $qty_from_location = min($remaining_qty, $lokasi['jumlah']);
                $remaining_qty -= $qty_from_location;

                // Update stock in location
                $query_update_lokasi = "UPDATE barang_lokasi 
                                      SET jumlah = jumlah - $qty_from_location 
                                      WHERE id_barang = {$data_permintaan['id_barang']} 
                                      AND id_lokasi = {$lokasi['id_lokasi']}";
                if (!query($query_update_lokasi)) {
                    throw new Exception("Gagal memperbarui stok di lokasi {$lokasi['nama_lokasi']}");
                }

                $locations_used[] = [
                    'nama_lokasi' => $lokasi['nama_lokasi'],
                    'jumlah' => $qty_from_location
                ];
            }

            if ($remaining_qty > 0) {
                throw new Exception("Stok tidak mencukupi di semua lokasi!");
            }

            // Create barang keluar record
            $tanggal = date('Y-m-d');
            $query_keluar = "INSERT INTO barang_keluar (
                                id_barang, 
                                tanggal_keluar, 
                                jumlah, 
                                tujuan,
                                keterangan,
                                created_by
                            ) VALUES (
                                {$data_permintaan['id_barang']},
                                '$tanggal',
                                {$data_permintaan['jumlah']},
                                'Permintaan: {$data_permintaan['tujuan_penggunaan']}',
                                'Permintaan disetujui dari ID: $id_permintaan',
                                {$_SESSION['user_id']}
                            )";
            
            if (!query($query_keluar)) {
                throw new Exception("Gagal mencatat barang keluar");
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
                           WHERE id_barang = {$data_permintaan['id_barang']}";
            if (!query($query_total)) {
                throw new Exception("Gagal memperbarui total stok");
            }

            // Prepare approval note with location details
            $location_details = "Diambil dari lokasi:\n";
            foreach ($locations_used as $loc) {
                $location_details .= "- {$loc['nama_lokasi']}: {$loc['jumlah']} unit\n";
            }
        }

        // Update request status
        $query = "UPDATE permintaan_barang SET 
                  status = '$status',
                  approved_by = {$_SESSION['user_id']},
                  approved_at = NOW(),
                  approval_note = " . ($status === 'disetujui' ? "'$location_details'" : "'$reason'") . "
                  WHERE id_permintaan = $id_permintaan";

        if (!query($query)) {
            throw new Exception("Gagal memperbarui status permintaan");
        }

        // Commit transaction
        query("COMMIT");

        // Send notification to user (if implemented)
        // sendNotificationToUser($data_permintaan['id_user'], $status, $reason);

        header("Location: ?module=permintaan&success=Permintaan berhasil " . ($status === 'disetujui' ? 'disetujui' : 'ditolak'));

    } catch (Exception $e) {
        // Rollback on error
        query("ROLLBACK");
        echo "<script>
            alert('" . $e->getMessage() . "');
            window.location.href = '?module=permintaan';
        </script>";
    }
} else {
    header("Location: ?module=permintaan");
}
?>