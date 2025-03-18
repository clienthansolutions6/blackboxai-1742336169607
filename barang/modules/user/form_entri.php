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
        <h2 class="text-xl font-semibold text-gray-800">Tambah Pengguna</h2>
        <a href="?module=user" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg text-sm font-medium flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>
</div>

<!-- Entry Form -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <form action="?module=user&action=proses_entri" method="POST" onsubmit="return validateForm('userForm')" id="userForm">
        <div class="p-6 space-y-6">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                    <input type="text" name="username" id="username" required
                        class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                        placeholder="Masukkan username">
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="password" id="password" required
                        class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                        placeholder="Masukkan password">
                </div>

                <!-- Full Name -->
                <div>
                    <label for="nama_lengkap" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" id="nama_lengkap" required
                        class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                        placeholder="Masukkan nama lengkap">
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" id="email"
                        class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                        placeholder="Masukkan email">
                </div>

                <!-- Role -->
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                    <select name="role" id="role" required
                        class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Pilih Role</option>
                        <option value="admin">Administrator</option>
                        <option value="kepala_gudang">Kepala Gudang</option>
                        <option value="mahasiswa">Mahasiswa</option>
                        <option value="dosen">Dosen</option>
                        <option value="staff">Staff</option>
                    </select>
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
    // Custom validation for email (optional field)
    const emailInput = document.getElementById('email');
    emailInput.addEventListener('input', function() {
        if (this.value !== '' && !this.checkValidity()) {
            this.classList.add('border-red-500');
            showNotification('Format email tidak valid', 'error');
        } else {
            this.classList.remove('border-red-500');
        }
    });

    // Custom validation for username (no spaces allowed)
    const usernameInput = document.getElementById('username');
    usernameInput.addEventListener('input', function() {
        if (this.value.includes(' ')) {
            this.classList.add('border-red-500');
            showNotification('Username tidak boleh mengandung spasi', 'error');
        } else {
            this.classList.remove('border-red-500');
        }
    });

    // Password strength validation
    const passwordInput = document.getElementById('password');
    passwordInput.addEventListener('input', function() {
        if (this.value.length < 6) {
            this.classList.add('border-red-500');
            showNotification('Password minimal 6 karakter', 'error');
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

    // Check username format
    const username = form.querySelector('#username');
    if (username.value.includes(' ')) {
        username.classList.add('border-red-500');
        showNotification('Username tidak boleh mengandung spasi', 'error');
        isValid = false;
    }

    // Check password length
    const password = form.querySelector('#password');
    if (password.value.length < 6) {
        password.classList.add('border-red-500');
        showNotification('Password minimal 6 karakter', 'error');
        isValid = false;
    }

    // Check email format if provided
    const email = form.querySelector('#email');
    if (email.value && !email.checkValidity()) {
        email.classList.add('border-red-500');
        showNotification('Format email tidak valid', 'error');
        isValid = false;
    }

    if (!isValid) {
        showNotification('Mohon lengkapi semua field yang wajib diisi dengan benar', 'error');
    }

    return isValid;
}
</script>