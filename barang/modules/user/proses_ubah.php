<?php
// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php?module=dashboard");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_user = (int)$_POST['id_user'];
    $username = escape_string($_POST['username']);
    $password = $_POST['password'];
    $nama_lengkap = escape_string($_POST['nama_lengkap']);
    $email = escape_string($_POST['email']);
    $role = escape_string($_POST['role']);

    // Validate required fields
    if (empty($username) || empty($nama_lengkap) || empty($role)) {
        echo "<script>
            alert('Username, nama lengkap, dan role wajib diisi!');
            window.history.back();
        </script>";
        exit();
    }

    // Check if username already exists (excluding current user)
    $check_query = "SELECT id_user FROM users WHERE username = '$username' AND id_user != $id_user";
    $check_result = query($check_query);

    if (num_rows($check_result) > 0) {
        echo "<script>
            alert('Username sudah digunakan!');
            window.history.back();
        </script>";
        exit();
    }

    // Build update query
    $query = "UPDATE users SET 
              username = '$username',
              nama_lengkap = '$nama_lengkap',
              email = " . ($email ? "'$email'" : "NULL") . ",
              role = '$role'";

    // Add password to update query if it's being changed
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query .= ", password = '$hashed_password'";
    }

    $query .= " WHERE id_user = $id_user";

    // Execute update
    if (query($query)) {
        header("Location: ?module=user&success=Data pengguna berhasil diperbarui");
    } else {
        echo "<script>
            alert('Gagal memperbarui data pengguna!');
            window.history.back();
        </script>";
    }
} else {
    header("Location: ?module=user");
}
?>