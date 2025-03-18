<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_barang_keluar = (int)$_POST['id_barang_keluar'];
    $id_barang = (int)$_POST['id_barang'];
    $id_lokasi = (int)$_POST['id_lokasi'];
    $tanggal_keluar = escape_string($_POST['tanggal_keluar']);
    $jumlah = (int)$_POST['jumlah'];
    $jumlah_lama = (int)$_POST['jumlah_lama'];
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

    // Check if outgoing item exists
    $check_query = "SELECT * FROM barang_keluar WHERE id_barang_keluar = $id_barang_keluar";
    $check_result = query($check_query);

    if (num_rows($check_result) === 0) {
        echo "<script>
            alert('Data barang keluar tidak ditemukan!');
            window.location.href = '?module=barang-keluar';
        </script>";
        exit();
    }

    $data_keluar = fetch_assoc($check_result);

    // Start transaction
    query("START TRANSACTION");

    try {
        // If location changed or quantity changed, update stocks
        if ($id_lokasi != $data_keluar['id_lokasi'] || $jumlah != $jumlah_lama) {
            // Return old quantity to old location
            $query_return = "UPDATE barang_lokasi 
                           SET jumlah = jumlah + $jumlah_lama 
                           WHERE id_barang = $id_barang AND id_lokasi = {$data_keluar['id_lokasi']}";
            if (!query($query_return)) {
                throw new Exception("Gagal mengembalikan stok ke lokasi lama");
            }

            // Check available stock in new location
            $query_stok = "SELECT jumlah FROM barang_lokasi 
                          WHERE id_barang = $id_barang AND id_lokasi = $id_lokasi";
            $result_stok = query($query_stok);
            
            if (num_rows($result_stok) === 0) {
                throw new Exception("Barang tidak tersedia di lokasi yang dipilih");
            }

            $data_stok = fetch_assoc($result_stok);
            if ($jumlah > $data_stok['jumlah']) {
                throw new Exception("Jumlah melebihi stok yang tersedia di lokasi ini");
            }

            // Deduct new quantity from new location
            $query_deduct = "UPDATE barang_lokasi 
                           SET jumlah = jumlah - $jumlah 
                           WHERE id_barang = $id_barang AND id_lokasi = $id_lokasi";
            if (!query($query_deduct)) {
                throw new Exception("Gagal memperbarui stok di lokasi baru");
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
        }

        // Update outgoing item record
        $query = "UPDATE barang_keluar SET 
                  tanggal_keluar = '$tanggal_keluar',
                  jumlah = $jumlah,
                  id_lokasi = $id_lokasi,
                  tujuan = '$tujuan',
                  keterangan = " . ($keterangan ? "'$keterangan'" : "NULL") . "
                  WHERE id_barang_keluar = $id_barang_keluar";

        if (!query($query)) {
            throw new Exception("Gagal memperbarui data barang keluar");
        }

        // Commit transaction
        query("COMMIT");
        header("Location: ?module=barang-keluar&success=Data barang keluar berhasil diperbarui");

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