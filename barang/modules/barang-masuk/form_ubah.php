<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

// Get incoming item ID from URL
$id_barang_masuk = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get incoming item data
$query = "SELECT bm.*, b.nama_barang, b.kode_barang, j.nama_jenis, s.nama_satuan,
          (SELECT SUM(bl.jumlah) FROM barang_lokasi bl WHERE bl.id_barang = b.id_barang) as total_stok
          FROM barang_masuk bm
          JOIN barang b ON bm.id_barang = b.id_barang
          LEFT JOIN jenis_barang j ON b.id_jenis = j.id_jenis
          LEFT JOIN satuan s ON b.id_satuan = s.id_satuan
          WHERE bm.id_barang_masuk = $id_barang_masuk";
$result = query($query);

if (num_rows($result) === 0) {
    echo "<script>
        alert('Data barang masuk tidak ditemukan!');
        window.location.href = '?module=barang-masuk';
    </script>";
    exit();
}

$data = fetch_assoc($result);

// Get all locations
$query_lokasi = "SELECT * FROM lokasi ORDER BY nama_lokasi ASC";
$result_lokasi = query($query_lokasi);

// Get current location
$query_current_lokasi = "SELECT bl.id_lokasi, bl.jumlah, l.nama_lokasi 
                        FROM barang_lokasi bl 
                        JOIN lokasi l ON bl.id_lokasi = l.id_lokasi 
                        WHERE bl.id_barang = {$data['id_barang']}";
$result_current_lokasi = query($query_current_lokasi);
?>

<!-- Page Header -->
<div class="bg-white shadow-sm border-b mb-6">
    <div class="px-4 py-4 sm:px-6 flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Edit Barang Masuk</h2>
        <a href="?module=barang-masuk" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg text-sm font-medium flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>
</div>

<!-- Edit Form -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <form action="?module=barang-masuk&action=proses_ubah" method="POST" onsubmit="return validateForm('barangMasukForm')" id="barangMasukForm">
        <input type="hidden" name="id_barang_masuk" value="<?php echo $data['id_barang_masuk']; ?>">
        <input type="hidden" name="id_barang" value="<?php echo $data['id_barang']; ?>">
        <input type="hidden" name="jumlah_lama" value="<?php echo $data['jumlah']; ?>">
        
        <div class="p-6 space-y-6">
            <!-- Item Info -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Kode Barang</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo $data['kode_barang']; ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Nama Barang</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo $data['nama_barang']; ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Stok Saat Ini</label>
                        <p class="mt-1 text-sm text-gray-900">
                            <?php echo $data['total_stok']; ?> <?php echo $data['nama_satuan']; ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Entry Date -->
                <div>
                    <label for="tanggal_masuk" class="block text-sm font-medium text-gray-700 mb-2">
                        Tanggal Masuk <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="tanggal_masuk" id="tanggal_masuk" required
                        value="<?php echo $data['tanggal_masuk']; ?>"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <!-- Quantity -->
                <div>
                    <label for="jumlah" class="block text-sm font-medium text-gray-700 mb-2">
                        Jumlah <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="jumlah" id="jumlah" required min="1"
                        value="<?php echo $data['jumlah']; ?>"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="Masukkan jumlah">
                </div>
            </div>

            <!-- Location -->
            <div>
                <label for="id_lokasi" class="block text-sm font-medium text-gray-700 mb-2">
                    Lokasi <span class="text-red-500">*</span>
                </label>
                <select name="id_lokasi" id="id_lokasi" required
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">Pilih Lokasi</option>
                    <?php 
                    mysqli_data_seek($result_lokasi, 0);
                    while ($lokasi = fetch_assoc($result_lokasi)): 
                    ?>
                    <option value="<?php echo $lokasi['id_lokasi']; ?>"
                            <?php echo $lokasi['id_lokasi'] == $data['id_lokasi'] ? 'selected' : ''; ?>>
                        <?php echo $lokasi['nama_lokasi']; ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Current Locations -->
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Distribusi Stok Saat Ini:</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <?php while ($lokasi = fetch_assoc($result_current_lokasi)): ?>
                            <p>- <?php echo $lokasi['nama_lokasi']; ?>: <?php echo $lokasi['jumlah']; ?> <?php echo $data['nama_satuan']; ?></p>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label for="keterangan" class="block text-sm font-medium text-gray-700 mb-2">
                    Keterangan
                </label>
                <textarea name="keterangan" id="keterangan" rows="3"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    placeholder="Masukkan keterangan (opsional)"><?php echo $data['keterangan']; ?></textarea>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 text-right">
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center ml-auto">
                <i class="fas fa-save mr-2"></i> Simpan Perubahan
            </button>
        </div>
    </form>
</div>

<script>
function validateForm(formId) {
    const form = document.getElementById(formId);
    let isValid = true;

    // Check required fields
    form.querySelectorAll('[required]').forEach(function(element) {
        if (!element.value) {
            element.classList.add('border-red-500');
            isValid = false;
        } else {
            element.classList.remove('border-red-500');
        }
    });

    // Check quantity
    const jumlah = form.querySelector('#jumlah');
    if (jumlah.value < 1) {
        jumlah.classList.add('border-red-500');
        showNotification('Jumlah harus lebih dari 0', 'error');
        isValid = false;
    }

    // Check date
    const tanggal = form.querySelector('#tanggal_masuk');
    if (tanggal.value > new Date().toISOString().split('T')[0]) {
        tanggal.classList.add('border-red-500');
        showNotification('Tanggal tidak boleh lebih dari hari ini', 'error');
        isValid = false;
    }

    if (!isValid) {
        showNotification('Mohon lengkapi semua field yang wajib diisi dengan benar', 'error');
    }

    return isValid;
}
</script>