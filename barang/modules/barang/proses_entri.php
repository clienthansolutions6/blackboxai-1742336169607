<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_barang = escape_string($_POST['kode_barang']);
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

    // Start transaction
    query("START TRANSACTION");

    try {
        // Insert item
        $query = "INSERT INTO barang (kode_barang, nama_barang, id_jenis, id_satuan, jenis_item, minimal_stok, keterangan) 
                  VALUES ('$kode_barang', '$nama_barang', $id_jenis, $id_satuan, '$jenis_item', $minimal_stok, " . 
                  ($keterangan ? "'$keterangan'" : "NULL") . ")";
        
        if (!query($query)) {
            throw new Exception("Gagal menambahkan data barang");
        }

        $id_barang = last_inserted_id();

        // Insert item locations
        $total_stok = 0;
        foreach ($lokasi as $key => $id_lokasi) {
            if (empty($id_lokasi)) continue;
            
            $jml = (int)$jumlah[$key];
            $total_stok += $jml;

            $query_lokasi = "INSERT INTO barang_lokasi (id_barang, id_lokasi, jumlah) 
                            VALUES ($id_barang, $id_lokasi, $jml)";
            
            if (!query($query_lokasi)) {
                throw new Exception("Gagal menambahkan lokasi barang");
            }
        }

        // Update total stock
        $query_update = "UPDATE barang SET stok = $total_stok WHERE id_barang = $id_barang";
        if (!query($query_update)) {
            throw new Exception("Gagal memperbarui stok barang");
        }

        // Insert stock entry transaction
        if ($total_stok > 0) {
            $tanggal = date('Y-m-d');
            $query_masuk = "INSERT INTO barang_masuk (id_barang, jumlah, tanggal_masuk, keterangan, created_by) 
                           VALUES ($id_barang, $total_stok, '$tanggal', 'Stok awal', {$_SESSION['user_id']})";
            
            if (!query($query_masuk)) {
                throw new Exception("Gagal mencatat transaksi barang masuk");
            }
        }

        // Commit transaction
        query("COMMIT");
        header("Location: ?module=barang&success=Data barang berhasil ditambahkan");

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