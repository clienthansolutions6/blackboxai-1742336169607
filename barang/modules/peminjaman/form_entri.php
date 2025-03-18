<?php
// Get all fixed items with their stock
$query_barang = "SELECT b.*, j.nama_jenis, s.nama_satuan,
                (SELECT SUM(bl.jumlah) FROM barang_lokasi bl WHERE bl.id_barang = b.id_barang) as total_stok
                FROM barang b
                LEFT JOIN jenis_barang j ON b.id_jenis = j.id_jenis
                LEFT JOIN satuan s ON b.id_satuan = s.id_satuan
                WHERE b.jenis_item = 'tetap'
                HAVING total_stok > 0
                ORDER BY b.nama_barang ASC";
$result_barang = query($query_barang);

// Get all locations
$query_lokasi = "SELECT * FROM lokasi ORDER BY nama_lokasi ASC";
$result_lokasi = query($query_lokasi);
?>

<!-- Page Header -->
<div class="bg-white shadow-sm border-b mb-6">
    <div class="px-4 py-4 sm:px-6 flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Pinjam Barang</h2>
        <a href="?module=peminjaman" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg text-sm font-medium flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>
</div>

<!-- Entry Form -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <form action="?module=peminjaman&action=proses_entri" method="POST" onsubmit="return validateForm('peminjamanForm')" id="peminjamanForm">
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
                        <?php echo $barang['nama_barang']; ?> (Tersedia: <?php echo $barang['total_stok']; ?> <?php echo $barang['nama_satuan']; ?>)
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

            <!-- Location Selection -->
            <div id="lokasiContainer" class="hidden">
                <label for="lokasi_peminjaman" class="block text-sm font-medium text-gray-700 mb-2">
                    Lokasi Peminjaman <span class="text-red-500">*</span>
                </label>
                <select name="lokasi_peminjaman" id="lokasi_peminjaman" required onchange="updateAvailableStock()"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">Pilih Lokasi</option>
                </select>
                <p class="mt-1 text-sm text-gray-500">Stok tersedia di lokasi: <span id="stokLokasi">0</span></p>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Loan Date -->
                <div>
                    <label for="tanggal_pinjam" class="block text-sm font-medium text-gray-700 mb-2">
                        Tanggal Pinjam <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="tanggal_pinjam" id="tanggal_pinjam" required
                        value="<?php echo date('Y-m-d'); ?>"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <!-- Return Date -->
                <div>
                    <label for="tanggal_kembali" class="block text-sm font-medium text-gray-700 mb-2">
                        Tanggal Kembali <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="tanggal_kembali" id="tanggal_kembali" required
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <!-- Quantity -->
                <div>
                    <label for="jumlah" class="block text-sm font-medium text-gray-700 mb-2">
                        Jumlah <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="jumlah" id="jumlah" required min="1"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="Masukkan jumlah yang dipinjam">
                </div>

                <!-- Purpose -->
                <div>
                    <label for="keperluan" class="block text-sm font-medium text-gray-700 mb-2">
                        Keperluan <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="keperluan" id="keperluan" required
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="Masukkan keperluan peminjaman">
                </div>
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
                        <h3 class="text-sm font-medium text-blue-800">Petunjuk Peminjaman:</h3>
                        <ul class="mt-2 text-sm text-blue-700 list-disc list-inside">
                            <li>Peminjaman hanya untuk barang tetap/inventaris</li>
                            <li>Pastikan tanggal kembali sesuai dengan durasi peminjaman yang diizinkan</li>
                            <li>Jumlah peminjaman harus sesuai dengan kebutuhan dan ketersediaan</li>
                            <li>Peminjam bertanggung jawab atas kondisi barang selama dipinjam</li>
                            <li>Peminjaman akan diproses setelah disetujui admin</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 text-right">
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center ml-auto">
                <i class="fas fa-paper-plane mr-2"></i> Ajukan Peminjaman
            </button>
        </div>
    </form>
</div>

<script>
async function updateItemInfo() {
    const select = document.getElementById('id_barang');
    const option = select.options[select.selectedIndex];
    const itemInfo = document.getElementById('itemInfo');
    const lokasiContainer = document.getElementById('lokasiContainer');
    const lokasiSelect = document.getElementById('lokasi_peminjaman');
    
    if (select.value) {
        // Update item info
        document.getElementById('kodeBarang').textContent = option.dataset.kode;
        document.getElementById('jenisBarang').textContent = option.dataset.jenis;
        document.getElementById('stokBarang').textContent = option.dataset.stok;
        document.getElementById('satuanBarang').textContent = option.dataset.satuan;
        itemInfo.classList.remove('hidden');

        // Get locations for this item
        try {
            const response = await fetch(`ajax/get_item_locations.php?id_barang=${select.value}`);
            const locations = await response.json();
            
            // Update location select
            lokasiSelect.innerHTML = '<option value="">Pilih Lokasi</option>';
            locations.forEach(loc => {
                lokasiSelect.innerHTML += `
                    <option value="${loc.id_lokasi}" data-stok="${loc.jumlah}">
                        ${loc.nama_lokasi} (Tersedia: ${loc.jumlah})
                    </option>
                `;
            });
            
            lokasiContainer.classList.remove('hidden');
        } catch (error) {
            console.error('Error fetching locations:', error);
            showNotification('Gagal memuat data lokasi', 'error');
        }
    } else {
        itemInfo.classList.add('hidden');
        lokasiContainer.classList.add('hidden');
    }
}

function updateAvailableStock() {
    const lokasiSelect = document.getElementById('lokasi_peminjaman');
    const option = lokasiSelect.options[lokasiSelect.selectedIndex];
    const stokLokasi = document.getElementById('stokLokasi');
    
    if (lokasiSelect.value && option.dataset.stok) {
        stokLokasi.textContent = option.dataset.stok;
        document.getElementById('jumlah').max = option.dataset.stok;
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

    // Check dates
    const tanggalPinjam = form.querySelector('#tanggal_pinjam');
    const tanggalKembali = form.querySelector('#tanggal_kembali');
    const today = new Date().toISOString().split('T')[0];
    
    if (tanggalPinjam.value < today) {
        tanggalPinjam.classList.add('border-red-500');
        showNotification('Tanggal pinjam tidak boleh kurang dari hari ini', 'error');
        isValid = false;
    }
    
    if (tanggalKembali.value <= tanggalPinjam.value) {
        tanggalKembali.classList.add('border-red-500');
        showNotification('Tanggal kembali harus lebih dari tanggal pinjam', 'error');
        isValid = false;
    }

    // Check quantity
    const jumlah = form.querySelector('#jumlah');
    const lokasiSelect = document.getElementById('lokasi_peminjaman');
    const option = lokasiSelect.options[lokasiSelect.selectedIndex];
    
    if (jumlah.value < 1) {
        jumlah.classList.add('border-red-500');
        showNotification('Jumlah harus lebih dari 0', 'error');
        isValid = false;
    } else if (option && option.dataset.stok && parseInt(jumlah.value) > parseInt(option.dataset.stok)) {
        jumlah.classList.add('border-red-500');
        showNotification('Jumlah melebihi stok tersedia', 'error');
        isValid = false;
    }

    // Check purpose length
    const keperluan = form.querySelector('#keperluan');
    if (keperluan.value.length < 5) {
        keperluan.classList.add('border-red-500');
        showNotification('Keperluan terlalu singkat (minimal 5 karakter)', 'error');
        isValid = false;
    }

    if (!isValid) {
        showNotification('Mohon lengkapi semua field yang wajib diisi dengan benar', 'error');
    }

    return isValid;
}
</script>