<?php
require_once '../config/database.php';
require_once '../config/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Set Session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['school_id'] = $user['school_id'];
        
        // --- PERBAIKAN DI SINI ---
        if($user['role'] == 'student') {
            header("Location: ../student/dashboard.php");
        } 
        else if($user['role'] == 'admin') {
            // Arahkan Admin Sekolah ke folder admin
            header("Location: ../admin/dashboard.php"); 
        } 
        else if($user['role'] == 'superadmin') {
            header("Location: ../superadmin/dashboard.php");
        }
        exit;
        // -------------------------
        
    } else {
        $error = "Email atau password salah.";
    }
}
require_once '../layout/header.php';
?>

<div class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="bg-white p-8 rounded shadow-md w-96">
        <h2 class="text-2xl font-bold mb-6 text-center text-primary">Login</h2>
        
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'registered'): ?>
            <div class="bg-green-100 text-green-700 p-2 mb-4 rounded text-center text-sm">
                Pendaftaran berhasil! Silakan login.
            </div>
        <?php endif; ?>

        <?php if(isset($error)) echo "<p class='bg-red-100 text-red-500 p-2 mb-4 rounded text-center text-sm'>$error</p>"; ?>
        
        <form method="POST">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                <input type="email" name="email" placeholder="email@sekolah.com" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-primary" required>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                <input type="password" name="password" placeholder="********" class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-primary" required>
            </div>
            <button type="submit" class="w-full bg-primary text-white font-bold p-2 rounded hover:bg-teal-700 transition">Masuk</button>
        </form>

        <p class="text-center mt-4 text-sm text-gray-600">
            Siswa baru? <a href="register.php" class="text-primary font-bold hover:underline">Daftar di sini</a>
        </p>
    </div>
</div>