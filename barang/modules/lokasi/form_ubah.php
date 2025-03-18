<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

// Get location ID from URL
$id_lokasi = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get location data
$query = "SELECT * FROM lokasi WHERE id_lokasi = $id_lokasi";
$result = query($query);

if (num_rows($result) === 0) {
    echo "<script>
        alert('Data lokasi tidak ditemukan!');
        window.location.href = '?module=lokasi';
    </script>";
    exit();
}

$data = fetch_assoc($result);

// Get items in this location
$query_items = "SELECT b.nama_barang, bl.jumlah 
                FROM barang_lokasi bl 
                JOIN barang b ON bl.id_barang = b.id_barang 
                WHERE bl.id_lokasi = $id_lokasi 
                ORDER BY b.nama_barang ASC";
$result_items = query($query_items);
?>

<!-- Page Header -->
<div class="bg-white shadow-sm border-b mb-6">
    <div class="px-4 py-4 sm:px-6 flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Edit Lokasi</h2>
        <a href="?module=lokasi" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg text-sm font-medium flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>
</div>

<!-- Edit Form -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <form action="?module=lokasi&action=proses_ubah" method="POST" onsubmit="return validateForm('lokasiForm')" id="lokasiForm">
        <input type="hidden" name="id_lokasi" value="<?php echo $data['id_lokasi']; ?>">
        
        <div class="p-6 space-y-6">
            <!-- Location Name -->
            <div>
                <label for="nama_lokasi" class="block text-sm font-medium text-gray-700 mb-2">
                    Nama Lokasi
                </label>
                <input type="text" name="nama_lokasi" id="nama_lokasi" required
                    value="<?php echo $data['nama_lokasi']; ?>"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    placeholder="Masukkan nama lokasi">
                <p class="mt-1 text-xs text-gray-500">
                    Contoh: Gudang Utama, Laboratorium Komputer, Ruang Kelas A101, dll
                </p>
            </div>

            <!-- Description -->
            <div>
                <label for="keterangan" class="block text-sm font-medium text-gray-700 mb-2">
                    Keterangan
                </label>
                <textarea name="keterangan" id="keterangan" rows="4"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    placeholder="Masukkan keterangan lokasi (opsional)"><?php echo $data['keterangan']; ?></textarea>
            </div>

            <!-- Current Items -->
            <?php if (num_rows($result_items) > 0): ?>
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700 font-medium">
                            Barang yang tersimpan di lokasi ini:
                        </p>
                        <div class="mt-2 space-y-1">
                            <?php while ($item = fetch_assoc($result_items)): ?>
                            <p class="text-sm text-yellow-700">
                                - <?php echo $item['nama_barang']; ?> (<?php echo $item['jumlah']; ?> unit)
                            </p>
                            <?php endwhile; ?>
                        </div>
                        <p class="mt-2 text-sm text-yellow-700">
                            Perubahan nama lokasi akan mempengaruhi semua barang di atas.
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Guidelines -->
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Petunjuk Pengisian:</h3>
                        <ul class="mt-2 text-sm text-blue-700 list-disc list-inside">
                            <li>Nama lokasi harus unik dan belum pernah digunakan</li>
                            <li>Gunakan nama lokasi yang spesifik dan mudah diidentifikasi</li>
                            <li>Tambahkan keterangan untuk informasi tambahan seperti lantai, gedung, atau area</li>
                            <li>Pastikan nama lokasi sesuai dengan denah atau peta kampus</li>
                        </ul>
                    </div>
                </div>
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
document.addEventListener('DOMContentLoaded', function() {
    // Custom validation for location name
    const namaLokasiInput = document.getElementById('nama_lokasi');
    namaLokasiInput.addEventListener('input', function() {
        if (this.value.length < 3) {
            this.classList.add('border-red-500');
            showNotification('Nama lokasi minimal 3 karakter', 'error');
        } else {
            this.classList.remove('border-red-500');
        }
    });

    // Capitalize first letter of each word
    namaLokasiInput.addEventListener('blur', function() {
        this.value = this.value.replace(/\w\S*/g, function(txt) {
            return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
        });
    });
});

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

    // Check location name length
    const namaLokasi = form.querySelector('#nama_lokasi');
    if (namaLokasi.value.length < 3) {
        namaLokasi.classList.add('border-red-500');
        showNotification('Nama lokasi minimal 3 karakter', 'error');
        isValid = false;
    }

    if (!isValid) {
        showNotification('Mohon lengkapi semua field yang wajib diisi dengan benar', 'error');
    }

    return isValid;
}
</script>