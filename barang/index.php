<?php
session_start();

// Check if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Inventaris Kampus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1e40af',
                        secondary: '#64748b',
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .sidebar-active {
            background-color: #1e40af;
            color: white;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-lg">
            <div class="p-4 border-b">
                <h2 class="text-xl font-bold text-gray-800">Inventaris Kampus</h2>
                <p class="text-sm text-gray-600">Selamat datang, <?php echo $_SESSION['nama_lengkap']; ?></p>
            </div>
            
            <?php include 'sidebar_menu.php'; ?>
        </aside>

        <!-- Main Content -->
        <main class="flex-1">
            <!-- Top Navigation -->
            <nav class="bg-white shadow-lg">
                <div class="mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <div class="flex-shrink-0 flex items-center">
                                <span class="text-xl font-semibold text-gray-800">
                                    <?php
                                    $module = isset($_GET['module']) ? $_GET['module'] : 'dashboard';
                                    echo ucwords(str_replace('-', ' ', $module));
                                    ?>
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <div class="ml-3 relative">
                                <div class="flex items-center space-x-4">
                                    <span class="text-gray-700"><?php echo $_SESSION['role']; ?></span>
                                    <a href="logout.php" class="text-red-600 hover:text-red-800">
                                        <i class="fas fa-sign-out-alt"></i> Logout
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Content -->
            <div class="py-6 px-4 sm:px-6 lg:px-8">
                <?php include 'content.php'; ?>
            </div>
        </main>
    </div>

    <script>
        // Handle active sidebar menu
        document.addEventListener('DOMContentLoaded', function() {
            const currentModule = '<?php echo isset($_GET["module"]) ? $_GET["module"] : "dashboard"; ?>';
            const sidebarLinks = document.querySelectorAll('.sidebar-link');
            
            sidebarLinks.forEach(link => {
                const href = link.getAttribute('href');
                if (href.includes(currentModule)) {
                    link.classList.add('sidebar-active');
                }
            });
        });

        // Handle notifications
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg ${
                type === 'success' ? 'bg-green-500' : 'bg-red-500'
            } text-white`;
            notification.textContent = message;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        }
    </script>
</body>
</html>