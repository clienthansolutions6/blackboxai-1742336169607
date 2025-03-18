<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_barang = (int)$_POST['id_barang'];
    $nama_barang = escape_string($_POST['nama_barang']);
    $id_jenis = (int)$_POST['id_jenis'];
    $id_satuan = (int)$_POST['id_satuan'];
    $jenis_item = escape_string($_POST['jenis_item']);
    $minimal_stok = $jenis_item === 'habis_pakai' ? (int)$_POST['minimal_stok'] : 0;
    $keterangan = escape_string($_POST['keterangan']);
    $lokasi = $_POST['lokasi'];
    $jumlah = $_POST['jumlah'];

    // Validate required fields
    if (empty($nama_barang) || empty($id_jenis) || empty($id_satuan) || empty($jenis_item)) {
        echo "<script>
            alert('Semua field wajib diisi!');
            window.history.back();
        </script>";
        exit();
    }

    // Check if item exists
    $check_query = "SELECT * FROM barang WHERE id_barang = $id_barang";
    $check_result = query($check_query);

    if (num_rows($check_result) === 0) {
        echo "<script>
            alert('Data barang tidak ditemukan!');
            window.location.href = '?module=barang';
        </script>";
        exit();
    }

    $current_data = fetch_assoc($check_result);

    // Start transaction
    query("START TRANSACTION");

    try {
        // Update item
        $query = "UPDATE barang SET 
                  nama_barang = '$nama_barang',
                  id_jenis = $id_jenis,
                  id_satuan = $id_satuan,
                  jenis_item = '$jenis_item',
                  minimal_stok = $minimal_stok,
                  keterangan = " . ($keterangan ? "'$keterangan'" : "NULL") . "
                  WHERE id_barang = $id_barang";

        if (!query($query)) {
            throw new Exception("Gagal memperbarui data barang");
        }

        // Get current locations
        $current_locations = [];
        $query_current = "SELECT id_lokasi, jumlah FROM barang_lokasi WHERE id_barang = $id_barang";
        $result_current = query($query_current);
        while ($row = fetch_assoc($result_current)) {
            $current_locations[$row['id_lokasi']] = $row['jumlah'];
        }

        // Process locations
        $new_locations = [];
        $total_stok = 0;
        foreach ($lokasi as $key => $id_lokasi) {
            if (empty($id_lokasi)) continue;
            
            $jml = (int)$jumlah[$key];
            $total_stok += $jml;
            $new_locations[$id_lokasi] = $jml;

            // If location exists, update it
            if (isset($current_locations[$id_lokasi])) {
                if ($current_locations[$id_lokasi] != $jml) {
                    $query_update = "UPDATE barang_lokasi 
                                   SET jumlah = $jml 
                                   WHERE id_barang = $id_barang AND id_lokasi = $id_lokasi";
                    if (!query($query_update)) {
                        throw new Exception("Gagal memperbarui jumlah barang di lokasi");
                    }
                }
            } else {
                // If new location, insert it
                $query_insert = "INSERT INTO barang_lokasi (id_barang, id_lokasi, jumlah) 
                                VALUES ($id_barang, $id_lokasi, $jml)";
                if (!query($query_insert)) {
                    throw new Exception("Gagal menambahkan lokasi baru");
                }
            }
        }

        // Remove locations that are no longer used
        foreach ($current_locations as $id_lokasi => $jumlah) {
            if (!isset($new_locations[$id_lokasi])) {
                $query_delete = "DELETE FROM barang_lokasi 
                                WHERE id_barang = $id_barang AND id_lokasi = $id_lokasi";
                if (!query($query_delete)) {
                    throw new Exception("Gagal menghapus lokasi yang tidak digunakan");
                }
            }
        }

        // Update total stock
        $query_update = "UPDATE barang SET stok = $total_stok WHERE id_barang = $id_barang";
        if (!query($query_update)) {
            throw new Exception("Gagal memperbarui total stok barang");
        }

        // Record stock adjustment if total changed
        if ($total_stok != $current_data['stok']) {
            $tanggal = date('Y-m-d');
            $selisih = $total_stok - $current_data['stok'];
            
            if ($selisih > 0) {
                // Stock increased
                $query_masuk = "INSERT INTO barang_masuk (id_barang, jumlah, tanggal_masuk, keterangan, created_by) 
                               VALUES ($id_barang, $selisih, '$tanggal', 'Penyesuaian stok', {$_SESSION['user_id']})";
                if (!query($query_masuk)) {
                    throw new Exception("Gagal mencatat penambahan stok");
                }
            } else if ($selisih < 0) {
                // Stock decreased
                $query_keluar = "INSERT INTO barang_keluar (id_barang, jumlah, tanggal_keluar, keterangan, created_by) 
                                VALUES ($id_barang, " . abs($selisih) . ", '$tanggal', 'Penyesuaian stok', {$_SESSION['user_id']})";
                if (!query($query_keluar)) {
                    throw new Exception("Gagal mencatat pengurangan stok");
                }
            }
        }

        // Commit transaction
        query("COMMIT");
        header("Location: ?module=barang&success=Data barang berhasil diperbarui");

    } catch (Exception $e) {
        // Rollback on error
        query("ROLLBACK");
        echo "<script>
            alert('" . $e->getMessage() . "');
            window.history.back();
        </script>";
    }
} else {
    header("Location: ?module=barang");
}
?>