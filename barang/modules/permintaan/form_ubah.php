<?php
// Get request ID from URL
$id_permintaan = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get request data
$query = "SELECT pb.*, b.nama_barang, b.kode_barang, j.nama_jenis, s.nama_satuan,
          (SELECT SUM(bl.jumlah) FROM barang_lokasi bl WHERE bl.id_barang = b.id_barang) as total_stok,
          u.nama_lengkap as pemohon
          FROM permintaan_barang pb
          JOIN barang b ON pb.id_barang = b.id_barang
          LEFT JOIN jenis_barang j ON b.id_jenis = j.id_jenis
          LEFT JOIN satuan s ON b.id_satuan = s.id_satuan
          LEFT JOIN users u ON pb.id_user = u.id_user
          WHERE pb.id_permintaan = $id_permintaan";
$result = query($query);

if (num_rows($result) === 0) {
    echo "<script>
        alert('Data permintaan tidak ditemukan!');
        window.location.href = '?module=permintaan';
    </script>";
    exit();
}

$data = fetch_assoc($result);

// Check if user has permission to edit
if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] !== $data['id_user']) {
    echo "<script>
        alert('Anda tidak memiliki akses untuk mengubah permintaan ini!');
        window.location.href = '?module=permintaan';
    </script>";
    exit();
}

// Check if request can be edited
if ($data['status'] !== 'pending') {
    echo "<script>
        alert('Permintaan yang sudah diproses tidak dapat diubah!');
        window.location.href = '?module=permintaan';
    </script>";
    exit();
}
?>

<!-- Page Header -->
<div class="bg-white shadow-sm border-b mb-6">
    <div class="px-4 py-4 sm:px-6 flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Edit Permintaan Barang</h2>
        <a href="?module=permintaan" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg text-sm font-medium flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>
</div>

<!-- Edit Form -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <form action="?module=permintaan&action=proses_ubah" method="POST" onsubmit="return validateForm('permintaanForm')" id="permintaanForm">
        <input type="hidden" name="id_permintaan" value="<?php echo $data['id_permintaan']; ?>">
        <input type="hidden" name="id_barang" value="<?php echo $data['id_barang']; ?>">
        
        <div class="p-6 space-y-6">
            <!-- Request Info -->
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
                        <label class="block text-sm font-medium text-gray-500">Pemohon</label>
                        <p class="mt-1 text-sm text-gray-900"><?php echo $data['pemohon']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Quantity -->
            <div>
                <label for="jumlah" class="block text-sm font-medium text-gray-700 mb-2">
                    Jumlah <span class="text-red-500">*</span>
                </label>
                <input type="number" name="jumlah" id="jumlah" required min="1"
                    value="<?php echo $data['jumlah']; ?>"
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
                    placeholder="Jelaskan tujuan penggunaan barang"><?php echo $data['tujuan_penggunaan']; ?></textarea>
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

            <!-- Request History -->
            <?php if ($data['approved_by'] || $data['approved_at'] || $data['approval_note']): ?>
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-history text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Riwayat Permintaan:</h3>
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