<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_barang = (int)$_POST['id_barang'];
    $id_lokasi = (int)$_POST['id_lokasi'];
    $tanggal_masuk = escape_string($_POST['tanggal_masuk']);
    $jumlah = (int)$_POST['jumlah'];
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

    // Start transaction
    query("START TRANSACTION");

    try {
        // Insert incoming item record
        $query = "INSERT INTO barang_masuk (id_barang, id_lokasi, tanggal_masuk, jumlah, keterangan, created_by) 
                  VALUES ($id_barang, $id_lokasi, '$tanggal_masuk', $jumlah, " . 
                  ($keterangan ? "'$keterangan'" : "NULL") . ", {$_SESSION['user_id']})";

        if (!query($query)) {
            throw new ExceptionNow I'll create the process file for editing incoming items.

<create_file>
<path>barang/modules/barang-masuk/proses_ubah.php</path>
<content>
<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_barang_masuk = (int)$_POST['id_barang_masuk'];
    $id_barang = (int)$_POST['id_barang'];
    $jumlah_lama = (int)$_POST['jumlah_lama'];
    $tanggal_masuk = escape_string($_POST['tanggal_masuk']);
    $jumlah = (int)$_POST['jumlah'];
    $keterangan = escape_string($_POST['keterangan']);
    $id_lokasi = (int)$_POST['id_lokasi'];

    // Validate required fields
    if (empty($id_barang) || empty($jumlah) || empty($id_lokasi)) {
        echo "<script>
            alert('Semua field wajib diisi!');
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
        // Update incoming item
        $query = "UPDATE barang_masuk SET 
                  id_barang = $id_barang,
                  tanggal_masuk = '$tanggal_masuk',
                  jumlah = $jumlah,
                  id_lokasi = $id_lokasi,
                  keterangan = " . ($keterangan ? "'$keterangan'" : "NULL") . "
                  WHERE id_barang_masuk = $id_barang_masuk";

        if (!query($query)) {
            throw new Exception("Gagal memperbarui data barang masuk");
        }

        // Update stock in barang_lokasi
        $query_lokasi = "SELECT * FROM barang_lokasi WHERE id_barang = $id_barang AND id_lokasi = $id_lokasi";
        $result_lokasi = query($query_lokasi);
        
        if (num_rows($result_lokasi) > 0) {
            // Update existing location
            $query_update = "UPDATE barang_lokasi SET 
                             jumlah = $jumlah 
                             WHERE id_barang = $id_barang AND id_lokasi = $id_lokasi";
            if (!query($query_update)) {
                throw new Exception("Gagal memperbarui jumlah barang di lokasi");
            }
        } else {
            // Insert new location if it doesn't exist
            $query_insert = "INSERT INTO barang_lokasi (id_barang, id_lokasi, jumlah) 
                             VALUES ($id_barang, $id_lokasi, $jumlah)";
            if (!query($query_insert)) {
                throw new Exception("Gagal menambahkan lokasi barang");
            }
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