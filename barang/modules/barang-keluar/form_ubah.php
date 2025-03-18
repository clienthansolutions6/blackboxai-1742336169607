<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

// Get outgoing item ID from URL
$id_barang_keluar = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get outgoing item data
$query = "SELECT bk.*, b.nama_barang, b.kode_barang, j.nama_jenis, s.nama_satuan,
          (SELECT SUM(bl.jumlah) FROM barang_lokasi bl WHERE bl.id_barang = b.id_barang) as total_stok,
          l.nama_lokasi
          FROM barang_keluar bk
          JOIN barang b ON bk.id_barang = b.id_barang
          LEFT JOIN jenis_barang j ON b.id_jenis = j.id_jenis
          LEFT JOIN satuan s ON b.id_satuan = s.id_satuan
          LEFT JOIN lokasi l ON bk.id_lokasi = l.id_lokasi
          WHERE bk.id_barang_keluar = $id_barang_keluar";
$result = query($query);

if (num_rows($result) === 0) {
    echo "<script>
        alert('Data barang keluar tidak ditemukan!');
        window.location.href = '?module=barang-keluar';
    </script>";
    exit();
}

$data = fetch_assoc($result);

// Get all locations with stock for this item
$query_lokasi = "SELECT l.*, bl.jumlah 
                 FROM lokasi l
                 JOIN barang_lokasi bl ON l.id_lokasi = bl.id_lokasi
                 WHERE bl.id_barang = {$data['id_barang']}
                 ORDER BY l.nama_lokasi ASC";
$result_lokasi = query($query_lokasi);
?>

<!-- Page Header -->
<div class="bg-white shadow-sm border-b mb-6">
    <div class="px-4 py-4 sm:px-6 flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Edit Barang Keluar</h2>
        <a href="?module=barang-keluar" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg text-sm font-medium flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>
</div>

<!-- Edit Form -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <form action="?module=barang-keluar&action=proses_ubah" method="POST" onsubmit="return validateForm('barangKeluarForm')" id="barangKeluarForm">
        <input type="hidden" name="id_barang_keluar" value="<?php echo $data['id_barang_keluar']; ?>">
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
                <!-- Exit Date -->
                <div>
                    <label for="tanggal_keluar" class="block text-sm font-medium text-gray-700 mb-2">
                        Tanggal Keluar <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="tanggal_keluar" id="tanggal_keluar" required
                        value="<?php echo $data['tanggal_keluar']; ?>"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <!-- Location -->
                <div>
                    <label for="id_lokasi" class="block text-sm font-medium text-gray-700 mb-2">
                        Lokasi Asal <span class="text-red-500">*</span>
                    </label>
                    <select name="id_lokasi" id="id_lokasi" required onchange="updateAvailableStock()"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Pilih Lokasi</option>
                        <?php while ($lokasi = fetch_assoc($result_lokasi)): ?>
                        <option value="<?php echo $lokasi['id_lokasi']; ?>" 
                                data-stok="<?php echo $lokasi['jumlah']; ?>"
                                <?php echo $lokasi['id_lokasi'] == $data['id_lokasi'] ? 'selected' : ''; ?>>
                            <?php echo $lokasi['nama_lokasi']; ?> (Stok: <?php echo $lokasi['jumlah']; ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <p class="mt-1 text-sm text-gray-500">Stok tersedia di lokasi: <span id="stokLokasi">
                        <?php 
                        $current_lokasi = fetch_assoc(query("SELECT jumlah FROM barang_lokasi 
                                                           WHERE id_barang = {$data['id_barang']} 
                                                           AND id_lokasi = {$data['id_lokasi']}"));
                        echo $current_lokasi['jumlah'];
                        ?>
                    </span></p>
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

                <!-- Destination -->
                <div>
                    <label for="tujuan" class="block text-sm font-medium text-gray-700 mb-2">
                        Tujuan <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="tujuan" id="tujuan" required
                        value="<?php echo $data['tujuan']; ?>"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="Masukkan tujuan">
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
function updateAvailableStock() {
    const lokasiSelect = document.getElementById('id_lokasi');
    const option = lokasiSelect.options[lokasiSelect.selectedIndex];
    const stokLokasi = document.getElementById('stokLokasi');
    const jumlahLama = <?php echo $data['jumlah']; ?>;
    
    if (lokasiSelect.value && option.dataset.stok) {
        const currentStok = parseInt(option.dataset.stok);
        // Add back the current quantity if we're editing the same location
        const availableStok = lokasiSelect.value == <?php echo $data['id_lokasi']; ?> 
            ? currentStok + jumlahLama 
            : currentStok;
        
        stokLokasi.textContent = availableStok;
        document.getElementById('jumlah').max = availableStok;
    } else {
        stokLokasi.textContent = '0';
        document.getElementById('jumlah').max = '';
    }
}

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
    const lokasiSelect = document.getElementById('id_lokasi');
    const option = lokasiSelect.options[lokasiSelect.selectedIndex];
    const jumlahLama = <?php echo $data['jumlah']; ?>;
    
    if (jumlah.value < 1) {
        jumlah.classList.add('border-red-500');
        showNotification('Jumlah harus lebih dari 0', 'error');
        isValid = false;
    } else if (option && option.dataset.stok) {
        const currentStok = parseInt(option.dataset.stok);
        const availableStok = lokasiSelect.value == <?php echo $data['id_lokasi']; ?>
            ? currentStok + jumlahLama
            : currentStok;
            
        if (parseInt(jumlah.value) > availableStok) {
            jumlah.classList.add('border-red-500');
            showNotification('Jumlah melebihi stok tersedia', 'error');
            isValid = false;
        }
    }

    // Check date
    const tanggal = form.querySelector('#tanggal_keluar');
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

// Initialize available stock display
document.addEventListener('DOMContentLoaded', function() {
    updateAvailableStock();
});
</script>