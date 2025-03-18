<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}
?>

<!-- Page Header -->
<div class="bg-white shadow-sm border-b mb-6">
    <div class="px-4 py-4 sm:px-6 flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Tambah Lokasi</h2>
        <a href="?module=lokasi" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg text-sm font-medium flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>
</div>

<!-- Entry Form -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <form action="?module=lokasi&action=proses_entri" method="POST" onsubmit="return validateForm('lokasiForm')" id="lokasiForm">
        <div class="p-6 space-y-6">
            <!-- Location Name -->
            <div>
                <label for="nama_lokasi" class="block text-sm font-medium text-gray-700 mb-2">
                    Nama Lokasi
                </label>
                <input type="text" name="nama_lokasi" id="nama_lokasi" required
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
                    placeholder="Masukkan keterangan lokasi (opsional)"></textarea>
            </div>

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
                <i class="fas fa-save mr-2"></i> Simpan
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