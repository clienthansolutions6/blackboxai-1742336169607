<?php
// Get loan ID from URL
$id_peminjaman = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get loan data
$query = "SELECT p.*, b.nama_barang, b.kode_barang, j.nama_jenis, s.nama_satuan,
          (SELECT SUM(bl.jumlah) FROM barang_lokasi bl WHERE bl.id_barang = b.id_barang) as total_stok,
          u.nama_lengkap as peminjam, l.nama_lokasi,
          DATEDIFF(p.tanggal_kembali, CURRENT_DATE()) as sisa_hari
          FROM peminjaman p
          JOIN barang b ON p.id_barang = b.id_barang
          LEFT JOIN jenis_barang j ON b.id_jenis = j.id_jenis
          LEFT JOIN satuan s ON b.id_satuan = s.id_satuan
          LEFT JOIN users u ON p.id_user = u.id_user
          LEFT JOIN lokasi l ON p.lokasi_peminjaman = l.id_lokasi
          WHERE p.id_peminjaman = $id_peminjaman";
$result = query($query);

if (num_rows($result) === 0) {
    echo "<script>
        alert('Data peminjaman tidak ditemukan!');
        window.location.href = '?module=peminjaman';
    </script>";
    exit();
}

$data = fetch_assoc($result);

// Check if user has permission to edit
if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] !== $data['id_user']) {
    echo "<script>
        alert('Anda tidak memiliki akses untuk mengubah peminjaman ini!');
        window.location.href = '?module=peminjaman';
    </script>";
    exit();
}

// Check if loan can be edited
if ($data['status'] !== 'pending') {
    echo "<script>
        alert('Peminjaman yang sudah diproses tidak dapat diubah!');
        window.location.href = '?module=peminjaman';
    </script>";
    exit();
}

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
        <h2 class="text-xl font-semibold text-gray-800">Edit Peminjaman</h2>
        <a href="?module=peminjaman" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg text-sm font-medium flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>
</div>

<!-- Edit Form -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <form action="?module=peminjaman&action=proses_ubah" method="POST" onsubmit="return validateForm('peminjamanForm')" id="peminjamanForm">
        <input type="hidden" name="id_peminjaman" value="<?php echo $data['id_peminjaman']; ?>">
        <input type="hidden" name="id_barang" value="<?php echo $data['id_barang']; ?>">
        <input type="hidden" name="jumlah_lama" value="<?php echo $data['jumlah']; ?>">
        
        <div class="p-6 space-y-6">
            <!-- Item Info -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Kode Barang</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo $data['kode_barang']; ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Nama Barang</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo $data['nama_barang']; ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Stok Tersedia</label>
                        <p class="mt-1 text-sm text-gray-900">
                            <?php echo $data['total_stok']; ?> <?php echo $data['nama_satuan']; ?>
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Peminjam</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo $data['peminjam']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Location Selection -->
            <div>
                <label for="lokasi_peminjaman" class="block text-sm font-medium text-gray-700 mb-2">
                    Lokasi Peminjaman <span class="text-red-500">*</span>
                </label>
                <select name="lokasi_peminjaman" id="lokasi_peminjaman" required onchange="updateAvailableStock()"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">Pilih Lokasi</option>
                    <?php while ($lokasi = fetch_assoc($result_lokasi)): ?>
                    <option value="<?php echo $lokasi['id_lokasi']; ?>" 
                            data-stok="<?php echo $lokasi['jumlah']; ?>"
                            <?php echo $lokasi['id_lokasi'] == $data['lokasi_peminjaman'] ? 'selected' : ''; ?>>
                        <?php echo $lokasi['nama_lokasi']; ?> (Tersedia: <?php echo $lokasi['jumlah']; ?>)
                    </option>
                    <?php endwhile; ?>
                </select>
                <p class="mt-1 text-sm text-gray-500">Stok tersedia di lokasi: <span id="stokLokasi">
                    <?php 
                    $current_lokasi = fetch_assoc(query("SELECT jumlah FROM barang_lokasi 
                                                       WHERE id_barang = {$data['id_barang']} 
                                                       AND id_lokasi = {$data['lokasi_peminjaman']}"));
                    echo $current_lokasi['jumlah'];
                    ?>
                </span></p>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Loan Date -->
                <div>
                    <label for="tanggal_pinjam" class="block text-sm font-medium text-gray-700 mb-2">
                        Tanggal Pinjam <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="tanggal_pinjam" id="tanggal_pinjam" required
                        value="<?php echo $data['tanggal_pinjam']; ?>"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <!-- Return Date -->
                <div>
                    <label for="tanggal_kembali" class="block text-sm font-medium text-gray-700 mb-2">
                        Tanggal Kembali <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="tanggal_kembali" id="tanggal_kembali" required
                        value="<?php echo $data['tanggal_kembali']; ?>"
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
                        placeholder="Masukkan jumlah yang dipinjam">
                </div>

                <!-- Purpose -->
                <div>
                    <label for="keperluan" class="block text-sm font-medium text-gray-700 mb-2">
                        Keperluan <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="keperluan" id="keperluan" required
                        value="<?php echo $data['keperluan']; ?>"
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
                    placeholder="Masukkan keterangan tambahan (opsional)"><?php echo $data['keterangan']; ?></textarea>
            </div>

            <!-- Loan History -->
            <?php if ($data['approved_by'] || $data['approved_at'] || $data['approval_note']): ?>
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-history text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Riwayat Peminjaman:</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <?php if ($data['approved_by']): ?>
                            <?php 
                            $approver = fetch_assoc(query("SELECT nama_lengkap FROM users WHERE id_user = {$data['approved_by']}"));
                            ?>
                            <p>Diproses oleh: <?php echo $approver['nama_lengkap']; ?></p>
                            <?php endif; ?>
                            
                            <?php if ($data['approved_at']): ?>
                            <p>Tanggal diproses: <?php echo date('d/m/Y H:i', strtotime($data['approved_at'])); ?></p>
                            <?php endif; ?>
                            
                            <?php if ($data['approval_note']): ?>
                            <p>Catatan: <?php echo $data['approval_note']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
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
    const lokasiSelect = document.getElementById('lokasi_peminjaman');
    const option = lokasiSelect.options[lokasiSelect.selectedIndex];
    const stokLokasi = document.getElementById('stokLokasi');
    const jumlahLama = <?php echo $data['jumlah']; ?>;
    
    if (lokasiSelect.value && option.dataset.stok) {
        const currentStok = parseInt(option.dataset.stok);
        // Add back the current quantity if we're editing the same location
        const availableStok = lokasiSelect.value == <?php echo $data['lokasi_peminjaman']; ?> 
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

    // Check dates
    const tanggalPinjam = form.querySelector('#tanggal_pinjam');
    const tanggalKembali = form.querySelector('#tanggal_kembali');
    
    if (tanggalKembali.value <= tanggalPinjam.value) {
        tanggalKembali.classList.add('border-red-500');
        showNotification('Tanggal kembali harus lebih dari tanggal pinjam', 'error');
        isValid = false;
    }

    // Check quantity
    const jumlah = form.querySelector('#jumlah');
    const lokasiSelect = document.getElementById('lokasi_peminjaman');
    const option = lokasiSelect.options[lokasiSelect.selectedIndex];
    const jumlahLama = <?php echo $data['jumlah']; ?>;
    
    if (jumlah.value < 1) {
        jumlah.classList.add('border-red-500');
        showNotification('Jumlah harus lebih dari 0', 'error');
        isValid = false;
    } else if (option && option.dataset.stok) {
        const currentStok = parseInt(option.dataset.stok);
        const availableStok = lokasiSelect.value == <?php echo $data['lokasi_peminjaman']; ?>
            ? currentStok + jumlahLama
            : currentStok;
            
        if (parseInt(jumlah.value) > availableStok) {
            jumlah.classList.add('border-red-500');
            showNotification('Jumlah melebihi stok tersedia', 'error');
            isValid = false;
        }
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

// Initialize available stock display
document.addEventListener('DOMContentLoaded', function() {
    updateAvailableStock();
});
</script>