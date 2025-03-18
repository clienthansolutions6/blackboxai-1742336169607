<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

// Get all items
$query_barang = "SELECT b.*, j.nama_jenis, s.nama_satuan,
                (SELECT SUM(bl.jumlah) FROM barang_lokasi bl WHERE bl.id_barang = b.id_barang) as total_stok
                FROM barang b
                LEFT JOIN jenis_barang j ON b.id_jenis = j.id_jenis
                LEFT JOIN satuan s ON b.id_satuan = s.id_satuan
                ORDER BY b.nama_barang ASC";
$result_barang = query($query_barang);

// Get all locations
$query_lokasi = "SELECT * FROM lokasi ORDER BY nama_lokasi ASC";
$result_lokasi = query($query_lokasi);
?>

<!-- Page Header -->
<div class="bg-white shadow-sm border-b mb-6">
    <div class="px-4 py-4 sm:px-6 flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Tambah Barang Masuk</h2>
        <a href="?module=barang-masuk" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg text-sm font-medium flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>
</div>

<!-- Entry Form -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <form action="?module=barang-masuk&action=proses_entri" method="POST" onsubmit="return validateForm('barangMasukForm')" id="barangMasukForm">
        <div class="p-6 space-y-6">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Item Selection -->
                <div>
                    <label for="id_barang" class="block text-sm font-medium text-gray-700 mb-2">
                        Barang <span class="text-red-500">*</span>
                    </label>
                    <select name="id_barang" id="id_barang" required onchange="updateItemInfo()"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Pilih Barang</option>
                        <?php while ($barang = fetch_assoc($result_barang)): ?>
                        <option value="<?php echo $barang['id_barang']; ?>" 
                                data-kode="<?php echo $barang['kode_barang']; ?>"
                                data-jenis="<?php echo $barang['nama_jenis']; ?>"
                                data-satuan="<?php echo $barang['nama_satuan']; ?>"
                                data-stok="<?php echo $barang['total_stok']; ?>">
                            <?php echo $barang['nama_barang']; ?> (<?php echo $barang['kode_barang']; ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Entry Date -->
                <div>
                    <label for="tanggal_masuk" class="block text-sm font-medium text-gray-700 mb-2">
                        Tanggal Masuk <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="tanggal_masuk" id="tanggal_masuk" required
                        value="<?php echo date('Y-m-d'); ?>"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
            </div>

            <!-- Item Info -->
            <div id="itemInfo" class="hidden bg-gray-50 p-4 rounded-lg">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Kode Barang</label>
                        <p class="mt-1 text-sm text-gray-900" id="kodeBarang">-</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Jenis Barang</label>
                        <p class="mt-1 text-sm text-gray-900" id="jenisBarang">-</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Stok Saat Ini</label>
                        <p class="mt-1 text-sm text-gray-900">
                            <span id="stokBarang">0</span> <span id="satuanBarang">-</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Quantity and Location -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Quantity -->
                <div>
                    <label for="jumlah" class="block text-sm font-medium text-gray-700 mb-2">
                        Jumlah <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="jumlah" id="jumlah" required min="1"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="Masukkan jumlah">
                </div>

                <!-- Location -->
                <div>
                    <label for="id_lokasi" class="block text-sm font-medium text-gray-700 mb-2">
                        Lokasi <span class="text-red-500">*</span>
                    </label>
                    <select name="id_lokasi" id="id_lokasi" required
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Pilih Lokasi</option>
                        <?php while ($lokasi = fetch_assoc($result_lokasi)): ?>
                        <option value="<?php echo $lokasi['id_lokasi']; ?>">
                            <?php echo $lokasi['nama_lokasi']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label for="keterangan" class="block text-sm font-medium text-gray-700 mb-2">
                    Keterangan
                </label>
                <textarea name="keterangan" id="keterangan" rows="3"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    placeholder="Masukkan keterangan (opsional)"></textarea>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 text-right">
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center ml-auto">
                <i class="fas fa-save mr-2"></i> Simpan
            </button>
        </div>
    </form>
</div>

<script>
function updateItemInfo() {
    const select = document.getElementById('id_barang');
    const option = select.options[select.selectedIndex];
    const itemInfo = document.getElementById('itemInfo');
    
    if (select.value) {
        document.getElementById('kodeBarang').textContent = option.dataset.kode;
        document.getElementById('jenisBarang').textContent = option.dataset.jenis;
        document.getElementById('stokBarang').textContent = option.dataset.stok;
        document.getElementById('satuanBarang').textContent = option.dataset.satuan;
        itemInfo.classList.remove('hidden');
    } else {
        itemInfo.classList.add('hidden');
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