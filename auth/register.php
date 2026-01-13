<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// Ambil data sekolah untuk dropdown
$stmt = $pdo->query("SELECT * FROM schools ORDER BY name ASC");
$schools = $stmt->fetchAll();

$error = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Ambil & Sanitasi Input Dasar
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password_raw = $_POST['password'];
    $input_phone = $_POST['phone'];
    
    // 2. Tentukan Role & Sekolah berdasarkan Tab yang dipilih
    $reg_type = $_POST['reg_type']; // Mengambil nilai dari input hidden
    
    if ($reg_type == 'student') {
        $role = 'student';
        $school_id = $_POST['school_id'];
        // Validasi khusus siswa: Sekolah wajib dipilih
        if (empty($school_id)) {
            $error = "Mohon pilih asal sekolah.";
        }
    } else {
        $role = 'public';
        $school_id = null; // Umum tidak punya sekolah
    }

    // 3. Logic Auto-Format Nomor HP (08 -> 62)
    if (!$error) {
        $phone = preg_replace('/[^0-9]/', '', $input_phone);
        if (substr($phone, 0, 1) == '0') {
            $phone = '62' . substr($phone, 1);
        } elseif (substr($phone, 0, 2) != '62') {
            $error = "Format nomor WhatsApp salah. Gunakan format 08xx atau 628xx.";
        }

        if (!$error && strlen($phone) < 10) {
            $error = "Nomor WhatsApp terlalu pendek.";
        }
    }

    // 4. Proses Pendaftaran
    if (!$error) {
        // Cek Email Duplikat
        $cek = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $cek->execute([$email]);
        
        if ($cek->rowCount() > 0) {
            $error = "Email sudah digunakan!";
        } else {
            // Hash Password
            $password_hash = password_hash($password_raw, PASSWORD_BCRYPT);
            
            // Insert Data (school_id bisa NULL jika public)
            $sql = "INSERT INTO users (school_id, name, email, password, phone, role) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            
            try {
                if ($stmt->execute([$school_id, $name, $email, $password_hash, $phone, $role])) {
                    header("Location: ../index.php");
                    exit;
                } else {
                    $error = "Gagal mendaftar. Silakan coba lagi.";
                }
            } catch (PDOException $e) {
                $error = "Terjadi kesalahan sistem: " . $e->getMessage();
            }
        }
    }
}
require_once '../layout/header.php';
?>

<div class="flex items-center justify-center min-h-screen bg-gray-100 py-10 px-4">
    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md border border-gray-100">
        
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-primary">Daftar Akun Baru</h2>
            <p class="text-gray-500 text-sm">Bergabunglah dengan komunitas SiswaNiaga</p>
        </div>

        <div class="flex border-b mb-6">
            <button type="button" onclick="switchTab('student')" id="tab-student" class="w-1/2 pb-2 text-sm font-bold text-primary border-b-2 border-primary transition focus:outline-none">
                Siswa Sekolah
            </button>
            <button type="button" onclick="switchTab('public')" id="tab-public" class="w-1/2 pb-2 text-sm font-bold text-gray-400 border-b-2 border-transparent hover:text-gray-600 transition focus:outline-none">
                Masyarakat Umum
            </button>
        </div>
        
        <?php if($error): ?>
            <div class='bg-red-50 border-l-4 border-red-500 text-red-700 p-3 rounded mb-6 text-sm font-medium'>
                <?= $error ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-4">
            
            <input type="hidden" name="reg_type" id="reg_type" value="student">

            <div>
                <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                <input type="text" name="name" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" class="w-full border px-3 py-2 rounded-lg mt-1 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition" required>
            </div>
            
            <div id="school-input-group" class="transition-all duration-300">
                <label class="block text-sm font-medium text-gray-700">Asal Sekolah <span class="text-red-500">*</span></label>
                <select name="school_id" id="school_id" class="w-full border px-3 py-2 rounded-lg mt-1 focus:ring-2 focus:ring-primary focus:border-primary outline-none bg-white" required>
                    <option value="">-- Pilih Sekolah --</option>
                    <?php foreach($schools as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= (isset($_POST['school_id']) && $_POST['school_id'] == $s['id']) ? 'selected' : '' ?>>
                            <?= $s['name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-gray-400 mt-1">Wajib dipilih bagi Siswa Kreator</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">No. WhatsApp</label>
                <input type="number" name="phone" value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>" placeholder="Contoh: 08123456789" class="w-full border px-3 py-2 rounded-lg mt-1 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition" required>
                <p class="text-xs text-gray-400 mt-1">Gunakan format 08xx atau 628xx</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" class="w-full border px-3 py-2 rounded-lg mt-1 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" name="password" class="w-full border px-3 py-2 rounded-lg mt-1 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition" required>
            </div>

            <button type="submit" class="w-full bg-primary text-white py-3 rounded-lg font-bold hover:bg-teal-700 shadow-md transition mt-6 transform hover:-translate-y-0.5">Daftar Sekarang</button>
        </form>
        
        <p class="text-center mt-6 text-sm text-gray-600">Sudah punya akun? <a href="login.php" class="text-primary font-bold hover:underline">Login disini</a></p>
    </div>
</div>

<script>
    function switchTab(type) {
        // 1. Set Nilai Hidden Input
        document.getElementById('reg_type').value = type;
        
        // 2. Atur Tampilan & Requirement Sekolah
        const schoolGroup = document.getElementById('school-input-group');
        const schoolSelect = document.getElementById('school_id');
        
        if (type === 'public') {
            // Jika Umum: Sembunyikan sekolah & matikan required
            schoolGroup.classList.add('hidden'); 
            schoolSelect.removeAttribute('required');
        } else {
            // Jika Siswa: Tampilkan sekolah & nyalakan required
            schoolGroup.classList.remove('hidden'); 
            schoolSelect.setAttribute('required', 'required'); 
        }

        // 3. Update Style Tab (Visual)
        const tabStudent = document.getElementById('tab-student');
        const tabPublic = document.getElementById('tab-public');

        const activeClass = "w-1/2 pb-2 text-sm font-bold text-primary border-b-2 border-primary transition focus:outline-none";
        const inactiveClass = "w-1/2 pb-2 text-sm font-bold text-gray-400 border-b-2 border-transparent hover:text-gray-600 transition focus:outline-none";

        if (type === 'student') {
            tabStudent.className = activeClass;
            tabPublic.className = inactiveClass;
        } else {
            tabPublic.className = activeClass;
            tabStudent.className = inactiveClass;
        }
    }
</script>