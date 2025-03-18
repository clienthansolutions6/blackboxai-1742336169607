<?php
// Get all items with their stock
$query_barang = "SELECT b.*, j.nama_jenis, s.nama_satuan,
                (SELECT SUM(bl.jumlah) FROM barang_lokasi bl WHERE bl.id_barang = b.id_barang) as total_stok
                FROM barang b
                LEFT JOIN jenis_barang j ON b.id_jenis = j.id_jenis
                LEFT JOIN satuan s ON b.id_satuan = s.id_satuan
                WHERE b.jenis_item = 'habis_pakai'
                ORDER BY b.nama_barang ASC";
$result_barang = query($query_barang);
?>

<!-- Page Header -->
<div class="bg-white shadow-sm border-b mb-6">
    <div class="px-4 py-4 sm:px-6 flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Buat Permintaan Barang</h2>
        <a href="?module=permintaan" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg text-sm font-medium flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>
</div>

<!-- Entry Form -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <form action="?module=permintaan&action=proses_entri" method="POST" onsubmit="return validateForm('permintaanForm')" id="permintaanForm">
        <div class="p-6 space-y-6">
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
                        <?php echo $barang['nama_barang']; ?> (Stok: <?php echo $barang['total_stok']; ?> <?php echo $barang['nama_satuan']; ?>)
                    </option>
                    <?php endwhile; ?>
                </select>
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
                        <label class="block text-sm font-medium text-gray-500">Stok Tersedia</label>
                        <p class="mt-1 text-sm text-gray-900">
                            <span id="stokBarang">0</span> <span id="satuanBarang">-</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Quantity -->
            <div>
                <label for="jumlah" class="block text-sm font-medium text-gray-700 mb-2">
                    Jumlah <span class="text-red-500">*</span>
                </label>
                <input type="number" name="jumlah" id="jumlah" required min="1"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    placeholder="Masukkan jumlah yang diminta">
            </div>

            <!-- Purpose -->
            <div>
                <label for="tujuan_penggunaan" class="block text-sm font-medium text-gray-700 mb-2">
                    Tujuan Penggunaan <span class="text-red-500">*</span>
                </label>
                <textarea name="tujuan_penggunaan" id="tujuan_penggunaan" required rows="3"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    placeholder="Jelaskan tujuan penggunaan barang"></textarea>
            </div>

            <!-- Notes -->
            <div>
                <label for="keterangan" class="block text-sm font-medium text-gray-700 mb-2">
                    Keterangan Tambahan
                </label>
                <textarea name="keterangan" id="keterangan" rows="3"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    placeholder="Masukkan keterangan tambahan (opsional)"></textarea>
            </div>

            <!-- Guidelines -->
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Petunjuk Pengajuan:</h3>
                        <ul class="mt-2 text-sm text-blue-700 list-disc list-inside">
                            <li>Pastikan barang yang diminta sesuai dengan kebutuhan</li>
                            <li>Jumlah yang diminta harus wajar dan sesuai penggunaan</li>
                            <li>Berikan tujuan penggunaan yang jelas dan spesifik</li>
                            <li>Permintaan akan diproses setelah disetujui admin</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 text-right">
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center ml-auto">
                <i class="fas fa-paper-plane mr-2"></i> Ajukan Permintaan
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

    // Check purpose length
    const tujuan = form.querySelector('#tujuan_penggunaan');
    if (tujuan.value.length < 10) {
        tujuan.classList.add('border-red-500');
        showNotification('Tujuan penggunaan terlalu singkat (minimal 10 karakter)', 'error');
        isValid = false;
    }

    if (!isValid) {
        showNotification('Mohon lengkapi semua field yang wajib diisi dengan benar', 'error');
    }

    return isValid;
}
</script>