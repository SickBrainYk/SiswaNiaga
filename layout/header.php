<?php require_once __DIR__ . '/../config/database.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiswaNiaga</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { primary: '#0f766e', secondary: '#f59e0b' } } }
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-800 font-sans">
    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="../index.php" class="text-2xl font-bold text-primary">SiswaNiaga</a>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php 
                            $dashboard = '../student/dashboard.php';
                            if($_SESSION['role'] == 'admin') $dashboard = '../admin/dashboard.php';
                            if($_SESSION['role'] == 'superadmin') $dashboard = '../superadmin/dashboard.php';
                        ?>
                        <a href="<?= $dashboard ?>" class="text-gray-600 hover:text-primary">Dashboard</a>
                        <a href="../auth/logout.php" class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600">Logout</a>
                    <?php else: ?>
                        <a href="../auth/login.php" class="text-gray-600 hover:text-primary">Login</a>
                        <a href="../auth/register.php" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-teal-700">Daftar</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>