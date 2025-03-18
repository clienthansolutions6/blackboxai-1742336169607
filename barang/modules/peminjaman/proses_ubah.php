<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_peminjaman = (int)$_POST['id_peminjaman'];
    $id_barang = (int)$_POST['id_barang'];
    $lokasi_peminjaman = (int)$_POST['lokasi_peminjaman'];
    $tanggal_pinjam = escape_string($_POST['tanggal_pinjam']);
    $tanggal_kembali = escape_string($_POST['tanggal_kembali']);
    $jumlah = (int)$_POST['jumlah'];
    $jumlah_lama = (int)$_POST['jumlah_lama'];
    $keperluan = escape_string($_POST['keperluan']);
    $keterangan = escape_string($_POST['keterangan']);

    // Validate required fields
    if (empty($id_peminjaman) || empty($id_barang) || empty($lokasi_peminjaman) || 
        empty($tanggal_pinjam) || empty($tanggal_kembali) || empty($jumlah) || empty($keperluan)) {
        echo "<script>
            alert('Semua field wajib diisi!');
            window.history.back();
        </script>";
        exit();
    }

    // Check if loan exists and can be edited
    $query_check = "SELECT p.*, b.jenis_item
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

    // Check if user has permission to edit
    if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] !== $data_peminjaman['id_user']) {
        echo "<script>
            alert('Anda tidak memiliki akses untuk mengubah peminjaman ini!');
            window.location.href = '?module=peminjaman';
        </script>";
        exit();
    }

    // Check if loan can be edited
    if ($data_peminjaman['status'] !== 'pending') {
        echo "<script>
            alert('Peminjaman yang sudah diproses tidak dapat diubah!');
            window.location.href = '?module=peminjaman';
        </script>";
        exit();
    }

    // Check if item is fixed asset
    if ($data_peminjaman['jenis_item'] !== 'tetap') {
        echo "<script>
            alert('Hanya barang tetap yang dapat dipinjam!');
            window.location.href = '?module=peminjaman';
        </script>";
        exit();
    }

    // Validate dates
    if ($tanggal_kembali <= $tanggal_pinjam) {
        echo "<script>
            alert('Tanggal kembali harus lebih dari tanggal pinjam!');
            window.history.back();
        </script>";
        exit();
    }

    // Check stock availability in new location
    if ($lokasi_peminjaman != $data_peminjaman['lokasi_peminjaman'] || $jumlah != $jumlah_lama) {
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
        $available_stock = $data_stok['jumlah'];
        
        // If same location, add back the old quantity
        if ($lokasi_peminjaman == $data_peminjaman['lokasi_peminjaman']) {
            $available_stock += $jumlah_lama;
        }

        if ($jumlah > $available_stock) {
            echo "<script>
                alert('Jumlah melebihi stok yang tersedia di lokasi ini!');
                window.history.back();
            </script>";
            exit();
        }
    }

    // Start transaction
    query("START TRANSACTION");

    try {
        // Update loan record
        $query = "UPDATE peminjaman SET 
                  lokasi_peminjaman = $lokasi_peminjaman,
                  tanggal_pinjam = '$tanggal_pinjam',
                  tanggal_kembali = '$tanggal_kembali',
                  jumlah = $jumlah,
                  keperluan = '$keperluan',
                  keterangan = " . ($keterangan ? "'$keterangan'" : "NULL") . ",
                  updated_at = NOW()
                  WHERE id_peminjaman = $id_peminjaman";

        if (!query($query)) {
            throw new Exception("Gagal memperbarui data peminjaman");
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
                        'edit_peminjaman',
                        $id_peminjaman,
                        'Mengubah peminjaman barang',
                        NOW()
                    )";

        if (!query($query_log)) {
            throw new Exception("Gagal mencatat log aktivitas");
        }

        // Commit transaction
        query("COMMIT");

        // Send notification to admin if significant changes (if implemented)
        // if ($jumlah != $jumlah_lama || $lokasi_peminjaman != $data_peminjaman['lokasi_peminjaman']) {
        //     sendNotificationToAdmin($id_barang, $jumlah, $jumlah_lama);
        // }

        header("Location: ?module=peminjaman&success=Peminjaman barang berhasil diperbarui");

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