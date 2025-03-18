<?php
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get requested module
$module = isset($_GET['module']) ? $_GET['module'] : 'dashboard';

// Define allowed modules for each role
$allowed_modules = [
    'admin' => [
        'dashboard', 'barang', 'jenis', 'satuan', 'lokasi', 'user',
        'barang-masuk', 'barang-keluar', 'permintaan', 'peminjaman',
        'laporan-stok', 'laporan-barang-masuk', 'laporan-barang-keluar',
        'password'
    ],
    'kepala_gudang' => [
        'dashboard', 'laporan-stok', 'laporan-barang-masuk',
        'laporan-barang-keluar', 'password'
    ],
    'mahasiswa' => [
        'dashboard', 'permintaan', 'peminjaman', 'password'
    ],
    'dosen' => [
        'dashboard', 'permintaan', 'peminjaman', 'password'
    ],
    'staff' => [
        'dashboard', 'permintaan', 'peminjaman', 'password'
    ]
];

// Check if module is allowed for current user role
if (!in_array($module, $allowed_modules[$_SESSION['role']])) {
    // If not allowed, redirect to dashboard
    header("Location: index.php?module=dashboard");
    exit();
}

// Function to display error message
function showError($message) {
    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">';
    echo '<span class="block sm:inline">' . $message . '</span>';
    echo '</div>';
}

// Function to display success message
function showSuccess($message) {
    echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">';
    echo '<span class="block sm:inline">' . $message . '</span>';
    echo '</div>';
}

// Path to module file
$module_path = "modules/{$module}/";

// Default action is tampil_data
$action = isset($_GET['action']) ? $_GET['action'] : 'tampil_data';

// Construct file path
$file_path = $module_path . $action . ".php";

// Check if file exists
if (file_exists($file_path)) {
    // Include the module file
    include $file_path;
} else {
    // Show error if file doesn't exist
    showError("Module tidak ditemukan!");
}

// Add common JavaScript for data tables and form validation
?>
<script>
// DataTables initialization
$(document).ready(function() {
    if ($('.datatable').length) {
        $('.datatable').DataTable({
            "responsive": true,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json"
            }
        });
    }
});

// Form validation
function validateForm(formId) {
    let form = document.getElementById(formId);
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

    if (!isValid) {
        showNotification('Mohon lengkapi semua field yang wajib diisi', 'error');
    }

    return isValid;
}

// Confirmation dialog
function confirmDelete(url) {
    if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
        window.location.href = url;
    }
}

// File input preview
function previewImage(input) {
    if (input.files && input.files[0]) {
        let reader = new FileReader();
        reader.onload = function(e) {
            let preview = document.querySelector('#imagePreview');
            if (preview) {
                preview.src = e.target.result;
            }
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Dynamic form fields
function addFormField(containerId, template) {
    let container = document.getElementById(containerId);
    let newField = template.cloneNode(true);
    container.appendChild(newField);
}

function removeFormField(button) {
    button.closest('.form-field').remove();
}

// AJAX helper function
function fetchData(url, callback) {
    fetch(url)
        .then(response => response.json())
        .then(data => callback(data))
        .catch(error => console.error('Error:', error));
}
</script>