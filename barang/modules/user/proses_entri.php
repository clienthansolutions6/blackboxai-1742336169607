<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = escape_string($_POST['username']);
    $password = $_POST['password'];
    $nama_lengkap = escape_string($_POST['nama_lengkap']);
    $email = escape_string($_POST['email']);
    $role = escape_string($_POST['role']);

    // Validate required fields
    if (empty($username) || empty($password) || empty($nama_lengkap) || empty($role)) {
        echo "<script>
            alert('Semua field wajib diisi!');
            window.history.back();
        </script>";
        exit();
    }

    // Check if username already exists
    $check_query = "SELECT id_user FROM users WHERE username = '$username'";
    $check_result = query($check_query);

    if (num_rows($check_result) > 0) {
        echo "<script>
            alert('Username sudah digunakan!');
            window.history.back();
        </script>";
        exit();
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $query = "INSERT INTO users (username, password, nama_lengkap, email, role) 
              VALUES ('$username', '$hashed_password', '$nama_lengkap', " . 
              ($email ? "'$email'" : "NULL") . ", '$role')";

    if (query($query)) {
        header("Location: ?module=user&success=Data pengguna berhasil ditambahkan");
    } else {
        echo "<script>
            alert('Gagal menambahkan pengguna!');
            window.history.back();
        </script>";
    }
} else {
    header("Location: ?module=user");
}
?>