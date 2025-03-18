<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_barang_masuk = (int)$_POST['id_barang_masuk'];
    $id_barang = (int)$_POST['id_barang'];
    $id_lokasi = (int)$_POST['id_lokasi'];
    $tanggal_masuk = escape_string($_POST['tanggal_masuk']);
    $jumlah = (int)$_POST['jumlah'];
    $jumlah_lama = (int)$_POST['jumlah_lama'];
    $keterangan = escape_string($_POST['keterangan']);

    // Validate required fields
    if (empty($id_barang) || empty($id_lokasi) || empty($tanggal_masuk) || empty($jumlah)) {
        echo "<script>
            alert('Semua field wajib diisi!');
            window.history.back();
        </script>";
        exit();
    }

    // Validate date
    if ($tanggal_masuk > date('Y-m-d')) {
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

    // Check if incoming item exists
    $check_query = "SELECT * FROM barang_masuk WHERE id_barang_masuk = $id_barang_masuk";
    $check_result = query($check_query);

    if (num_rows($check_result) === 0) {
        echo "<script>
            alert('Data barang masuk tidak ditemukan!');
            window.location.href = '?module=barang-masuk';
        </script>";
        exit();
    }

    // Start transaction
    query("START TRANSACTION");

    try {
        // Update incoming item record
        $query = "UPDATE barang_masuk SET 
                  tanggal_masuk = '$tanggal_masuk',
                  jumlah = $jumlah,
                  id_lokasi = $id_lokasi,
                  keterangan = " . ($keterangan ? "'$keterangan'" : "NULL") . "
                  WHERE id_barang_masuk = $id_barang_masuk";

        if (!query($query)) {
            throw new Exception("Gagal memperbarui data barang masuk");
        }

        // Update stock in barang_lokasi
        // First, revert the old quantity
        $query_old_location = "UPDATE barang_lokasi 
                             SET jumlah = jumlah - $jumlah_lama 
                             WHERE id_barang = $id_barang AND id_lokasi = $id_lokasi";
        if (!query($query_old_location)) {
            throw new Exception("Gagal memperbarui stok lama");
        }

        // Then, add the new quantity
        $query_check_location = "SELECT * FROM barang_lokasi 
                               WHERE id_barang = $id_barang AND id_lokasi = $id_lokasi";
        $result_check_location = query($query_check_location);

        if (num_rows($result_check_location) > 0) {
            // Update existing location
            $query_update = "UPDATE barang_lokasi 
                           SET jumlah = jumlah + $jumlah 
                           WHERE id_barang = $id_barang AND id_lokasi = $id_lokasi";
            if (!query($query_update)) {
                throw new Exception("Gagal memperbarui stok baru");
            }
        } else {
            // Insert new location
            $query_insert = "INSERT INTO barang_lokasi (id_barang, id_lokasi, jumlah) 
                           VALUES ($id_barang, $id_lokasi, $jumlah)";
            if (!query($query_insert)) {
                throw new Exception("Gagal menambahkan lokasi baru");
            }
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
        header("Location: ?module=barang-masuk&success=Data barang masuk berhasil diperbarui");

    } catch (Exception $e) {
        // Rollback on error
        query("ROLLBACK");
        echo "<script>
            alert('" . $e->getMessage() . "');
            window.history.back();
        </script>";
    }
} else {
    header("Location: ?module=barang-masuk");
}
?>