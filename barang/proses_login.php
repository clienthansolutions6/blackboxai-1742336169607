<?php
session_start();
require_once 'config/database.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = escape_string($_POST['username']);
    $password = $_POST['password'];

    // Validate input
    if (empty($username) || empty($password)) {
        header("Location: login.php?error=empty");
        exit();
    }

    // Query to check user
    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = query($query);

    if (num_rows($result) == 1) {
        $user = fetch_assoc($result);
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];

            // Redirect based on role
            switch($user['role']) {
                case 'admin':
                case 'kepala_gudang':
                    header("Location: index.php?module=dashboard");
                    break;
                case 'mahasiswa':
                case 'dosen':
                case 'staff':
                    header("Location: index.php?module=dashboard");
                    break;
                default:
                    header("Location: index.php");
            }
            exit();
        } else {
            header("Location: login.php?error=invalid");
            exit();
        }
    } else {
        header("Location: login.php?error=invalid");
        exit();
    }
} else {
    // If not POST request, redirect to login page
    header("Location: login.php");
    exit();
}
?>