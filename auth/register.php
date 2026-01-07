<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// Ambil data sekolah untuk dropdown
$stmt = $pdo->query("SELECT * FROM schools ORDER BY name ASC");
$schools = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $school_id = $_POST['school_id'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // 1. Cek Email
    $cek = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $cek->execute([$email]);
    
    if ($cek->rowCount() > 0) {
        $error = "Email sudah digunakan!";
    } else {
        // 2. Insert Siswa (Role otomatis 'student')
        $sql = "INSERT INTO users (school_id, name, email, password, phone, role) VALUES (?, ?, ?, ?, ?, 'student')";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$school_id, $name, $email, $password, $phone])) {
            header("Location: login.php?msg=registered");
            exit;
        } else {
            $error = "Gagal mendaftar.";
        }
    }
}
require_once '../layout/header.php';
?>

<div class="flex items-center justify-center min-h-screen bg-gray-100 py-10">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
        <h2 class="text-2xl font-bold mb-2 text-center text-primary">Daftar SiswaNiaga</h2>
        <p class="text-center text-gray-500 mb-6 text-sm">Khusus untuk Siswa Kreator</p>
        
        <?php if(isset($error)) echo "<div class='bg-red-100 text-red-500 p-2 mb-4 rounded text-sm text-center'>$error</div>"; ?>
        
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                <input type="text" name="name" class="w-full border p-2 rounded mt-1" required>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Asal Sekolah</label>
                <select name="school_id" class="w-full border p-2 rounded mt-1" required>
                    <option value="">-- Pilih Sekolah --</option>
                    <?php foreach($schools as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= $s['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">No. WhatsApp</label>
                <input type="text" name="phone" placeholder="Contoh: 62812345678" class="w-full border p-2 rounded mt-1" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" class="w-full border p-2 rounded mt-1" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" name="password" class="w-full border p-2 rounded mt-1" required>
            </div>

            <button type="submit" class="w-full bg-primary text-white p-2 rounded font-bold hover:bg-teal-700 mt-4">Daftar Sekarang</button>
        </form>
        
        <p class="text-center mt-4 text-sm">Sudah punya akun? <a href="login.php" class="text-primary font-bold">Login</a></p>
    </div>
</div>