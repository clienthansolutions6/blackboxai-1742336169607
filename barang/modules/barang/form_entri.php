<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

// Get all categories
$query_jenis = "SELECT * FROM jenis_barang ORDER BY nama_jenis ASC";
$result_jenis = query($query_jenis);

// Get all units
$query_satuan = "SELECT * FROM satuan ORDER BY nama_satuan ASC";
$result_satuan = query($query_satuan);

// Get all locations
$query_lokasi = "SELECT * FROM lokasi ORDER BY nama_lokasi ASC";
$result_lokasi = query($query_lokasi);

// Generate unique item code
$tahun = date('Y');
$query_last_code = "SELECT MAX(SUBSTRING(kode_barang, 9)) as last_num 
                    FROM barang 
                    WHERE kode_barang LIKE 'BRG$tahun%'";
$result_last_code = query($query_last_code);
$data_last_code = fetch_assoc($result_last_code);
$last_num = (int)$data_last_code['last_num'];
$new_num = str_pad($last_num + 1, 4, '0', STR_PAD_LEFT);
$kode_barang = "BRG$tahun$new_num";
?>

<!-- Page Header -->
<div class="bg-white shadow-sm border-b mb-6">
    <div class="px-4 py-4 sm:px-6 flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Tambah Barang</h2>
        <a href="?module=barang" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg text-sm font-medium flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>
</div>

<!-- Entry Form -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <form action="?module=barang&action=proses_entri" method="POST" onsubmit="return validateForm('barangForm')" id="barangForm">
        <div class="p-6 space-y-6">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Item Code -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Kode Barang
                    </label>
                    <input type="text" name="kode_barang" value="<?php echo $kode_barang; ?>" readonly
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-50 text-gray-500 sm:text-sm">
                </div>

                <!-- Item Name -->
                <div>
                    <label for="nama_barang" class="block text-sm font-medium text-gray-700 mb-2">
                        Nama Barang <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="nama_barang" id="nama_barang" required
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="Masukkan nama barang">
                </div>

                <!-- Category -->
                <div>
                    <label for="id_jenis" class="block text-sm font-medium text-gray-700 mb-2">
                        Jenis Barang <span class="text-red-500">*</span>
                    </label>
                    <select name="id_jenis" id="id_jenis" required
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Pilih Jenis Barang</option>
                        <?php while ($jenis = fetch_assoc($result_jenis)): ?>
                        <option value="<?php echo $jenis['id_jenis']; ?>">
                            <?php echo $jenis['nama_jenis']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Unit -->
                <div>
                    <label for="id_satuan" class="block text-sm font-medium text-gray-700 mb-2">
                        Satuan <span class="text-red-500">*</span>
                    </label>
                    <select name="id_satuan" id="id_satuan" required
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Pilih Satuan</option>
                        <?php while ($satuan = fetch_assoc($result_satuan)): ?>
                        <option value="<?php echo $satuan['id_satuan']; ?>">
                            <?php echo $satuan['nama_satuan']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Item Type -->
                <div>
                    <label for="jenis_item" class="block text-sm font-medium text-gray-700 mb-2">
                        Tipe Barang <span class="text-red-500">*</span>
                    </label>
                    <select name="jenis_item" id="jenis_item" required onchange="toggleStokMinimal()"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Pilih Tipe Barang</option>
                        <option value="tetap">Barang Tetap</option>
                        <option value="habis_pakai">Barang Habis Pakai</option>
                    </select>
                </div>

                <!-- Minimum Stock -->
                <div id="stokMinimalContainer" style="display: none;">
                    <label for="minimal_stok" class="block text-sm font-medium text-gray-700 mb-2">
                        Stok Minimal
                    </label>
                    <input type="number" name="minimal_stok" id="minimal_stok" min="0"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="Masukkan stok minimal">
                </div>
            </div>

            <!-- Description -->
            <div>
                <label for="keterangan" class="block text-sm font-medium text-gray-700 mb-2">
                    Keterangan
                </label>
                <textarea name="keterangan" id="keterangan" rows="3"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    placeholder="Masukkan keterangan (opsional)"></textarea>
            </div>

            <!-- Initial Stock -->
            <div class="border rounded-lg p-4 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Stok Awal</h3>
                <div class="space-y-4" id="stokContainer">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 stok-item">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Lokasi <span class="text-red-500">*</span>
                            </label>
                            <select name="lokasi[]" required
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Pilih Lokasi</option>
                                <?php while ($lokasi = fetch_assoc($result_lokasi)): ?>
                                <option value="<?php echo $lokasi['id_lokasi']; ?>">
                                    <?php echo $lokasi['nama_lokasi']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Jumlah <span class="text-red-500">*</span>
                            </label>
                            <div class="flex">
                                <input type="number" name="jumlah[]" required min="1"
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    placeholder="Masukkan jumlah">
                                <button type="button" onclick="removeStokItem(this)"
                                    class="ml-2 px-3 py-2 bg-red-500 text-white rounded-md hover:bg-red-600">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" onclick="addStokItem()"
                    class="mt-4 px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">
                    <i class="fas fa-plus mr-2"></i> Tambah Lokasi
                </button>
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
function toggleStokMinimal() {
    const jenisItem = document.getElementById('jenis_item').value;
    const stokMinimalContainer = document.getElementById('stokMinimalContainer');
    const minimalStokInput = document.getElementById('minimal_stok');

    if (jenisItem === 'habis_pakai') {
        stokMinimalContainer.style.display = 'block';
        minimalStokInput.required = true;
    } else {
        stokMinimalContainer.style.display = 'none';
        minimalStokInput.required = false;
        minimalStokInput.value = '';
    }
}

function addStokItem() {
    const container = document.getElementById('stokContainer');
    const template = container.querySelector('.stok-item').cloneNode(true);
    
    // Clear values
    template.querySelectorAll('input').forEach(input => input.value = '');
    template.querySelectorAll('select').forEach(select => select.selectedIndex = 0);
    
    container.appendChild(template);
}

function removeStokItem(button) {
    const items = document.querySelectorAll('.stok-item');
    if (items.length > 1) {
        button.closest('.stok-item').remove();
    } else {
        showNotification('Minimal harus ada satu lokasi', 'error');
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

    // Check item name length
    const namaBarang = form.querySelector('#nama_barang');
    if (namaBarang.value.length < 3) {
        namaBarang.classList.add('border-red-500');
        showNotification('Nama barang minimal 3 karakter', 'error');
        isValid = false;
    }

    // Check minimum stock for consumable items
    const jenisItem = form.querySelector('#jenis_item');
    const minimalStok = form.querySelector('#minimal_stok');
    if (jenisItem.value === 'habis_pakai' && (!minimalStok.value || minimalStok.value < 0)) {
        minimalStok.classList.add('border-red-500');
        showNotification('Stok minimal harus diisi dengan nilai tidak negatif', 'error');
        isValid = false;
    }

    // Check for duplicate locations
    const locations = new Set();
    form.querySelectorAll('select[name="lokasi[]"]').forEach(function(select) {
        if (select.value && locations.has(select.value)) {
            select.classList.add('border-red-500');
            showNotification('Lokasi tidak boleh duplikat', 'error');
            isValid = false;
        }
        locations.add(select.value);
    });

    if (!isValid) {
        showNotification('Mohon lengkapi semua field yang wajib diisi dengan benar', 'error');
    }

    return isValid;
}
</script>