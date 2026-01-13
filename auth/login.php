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
        
        // --- LOGIC REDIRECT BERDASARKAN ROLE ---
        if($user['role'] == 'student') {
            header("Location: ../student/dashboard.php");
        } 
        else if($user['role'] == 'admin') {
            header("Location: ../admin/dashboard.php"); 
        } 
        else if($user['role'] == 'superadmin') {
            // Biasanya superadmin masuk ke index.php di folder superadmin
            header("Location: ../superadmin/dashboard.php");
        }
        else {
            // ROLE PUBLIC / UMUM
            // Langsung diarahkan ke Halaman Utama (Katalog)
            header("Location: ../index.php");
        }
        // ----------------------------------------
        exit;
        
    } else {
        $error = "Email atau password salah.";
    }
}
require_once '../layout/header.php';
?>

<div class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-sm border border-gray-100">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-primary">Selamat Datang</h2>
            <p class="text-sm text-gray-500">Silakan login untuk melanjutkan</p>
        </div>
        
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'registered'): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 p-3 mb-4 rounded-lg text-center text-sm font-medium">
                ✅ Pendaftaran berhasil! Silakan login.
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'login_first'): ?>
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 p-3 mb-4 rounded-lg text-center text-sm font-medium">
                🔒 Silakan login untuk akses fitur tersebut.
            </div>
        <?php endif; ?>

        <?php if(isset($error)) echo "<p class='bg-red-50 border border-red-200 text-red-600 p-3 mb-4 rounded-lg text-center text-sm font-bold'>$error</p>"; ?>
        
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-1">Email</label>
                <input type="email" name="email" placeholder="nama@email.com" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition" required>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-1">Password</label>
                <input type="password" name="password" placeholder="••••••••" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition" required>
            </div>
            <button type="submit" class="w-full bg-primary text-white font-bold py-2.5 rounded-lg hover:bg-teal-700 transition shadow-md hover:shadow-lg transform hover:-translate-y-0.5">Masuk</button>
        </form>

        <div class="mt-6 text-center border-t pt-4">
            <p class="text-sm text-gray-600 mb-2">Belum punya akun?</p>
            <a href="register.php" class="inline-block text-primary font-bold hover:underline transition">Daftar Akun Baru</a>
        </div>
        
        <div class="mt-4 text-center">
            <a href="../index.php" class="text-xs text-gray-400 hover:text-gray-600 flex items-center justify-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Beranda
            </a>
        </div>
    </div>
</div>