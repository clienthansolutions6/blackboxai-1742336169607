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
        <h2 class="text-xl font-semibold text-gray-800">Tambah Jenis Barang</h2>
        <a href="?module=jenis" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg text-sm font-medium flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>
</div>

<!-- Entry Form -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <form action="?module=jenis&action=proses_entri" method="POST" onsubmit="return validateForm('jenisForm')" id="jenisForm">
        <div class="p-6 space-y-6">
            <!-- Category Name -->
            <div>
                <label for="nama_jenis" class="block text-sm font-medium text-gray-700 mb-2">
                    Nama Jenis Barang
                </label>
                <input type="text" name="nama_jenis" id="nama_jenis" required
                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    placeholder="Masukkan nama jenis barang">
            </div>

            <!-- Description -->
            <div>
                <label for="keterangan" class="block text-sm font-medium text-gray-700 mb-2">
                    Keterangan
                </label>
                <textarea name="keterangan" id="keterangan" rows="4"
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
document.addEventListener('DOMContentLoaded', function() {
    // Custom validation for category name
    const namaJenisInput = document.getElementById('nama_jenis');
    namaJenisInput.addEventListener('input', function() {
        if (this.value.length < 3) {
            this.classList.add('border-red-500');
            showNotification('Nama jenis barang minimal 3 karakter', 'error');
        } else {
            this.classList.remove('border-red-500');
        }
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

    // Check category name length
    const namaJenis = form.querySelector('#nama_jenis');
    if (namaJenis.value.length < 3) {
        namaJenis.classList.add('border-red-500');
        showNotification('Nama jenis barang minimal 3 karakter', 'error');
        isValid = false;
    }

    if (!isValid) {
        showNotification('Mohon lengkapi semua field yang wajib diisi dengan benar', 'error');
    }

    return isValid;
}
</script>