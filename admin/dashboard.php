<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// Keamanan: Hanya Role 'admin' (Guru) yang boleh masuk
checkRole(['admin']);

$school_id = $_SESSION['school_id'];
$admin_id = $_SESSION['user_id'];

// --- LOGIC: UPDATE PROFIL ADMIN ---
$error_profile = null;
$success_profile = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone_input = sanitize($_POST['phone']);
    $password = $_POST['password'];

    // Auto-Format HP (08 -> 62)
    $phone = preg_replace('/[^0-9]/', '', $phone_input);
    if (substr($phone, 0, 1) == '0') {
        $phone = '62' . substr($phone, 1);
    } elseif (substr($phone, 0, 2) != '62') {
        $error_profile = "Format nomor HP salah. Gunakan awalan 08 atau 62.";
    }

    if (!$error_profile && strlen($phone) < 10) {
        $error_profile = "Nomor HP terlalu pendek.";
    }

    if (!$error_profile) {
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $sql = "UPDATE users SET name=?, email=?, phone=?, password=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$name, $email, $phone, $hashed_password, $admin_id]);
        } else {
            $sql = "UPDATE users SET name=?, email=?, phone=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$name, $email, $phone, $admin_id]);
        }
        
        if($result) {
            $_SESSION['name'] = $name;
            $success_profile = "Profil berhasil diperbarui!";
            // Refresh data admin
            $stmt_user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt_user->execute([$admin_id]);
            $admin_data = $stmt_user->fetch();
        } else {
            $error_profile = "Gagal menyimpan perubahan.";
        }
    }
}

// --- LOGIC: HAPUS SISWA ---
if (isset($_GET['delete_student'])) {
    $student_id = (int)$_GET['delete_student'];
    // Hapus Produk Siswa Terlebih Dahulu (Optional: tergantung kebijakan database foreign key)
    $pdo->prepare("DELETE FROM products WHERE user_id = ?")->execute([$student_id]);
    // Hapus User
    $pdo->prepare("DELETE FROM users WHERE id = ? AND school_id = ? AND role = 'student'")->execute([$student_id, $school_id]);
    
    echo "<script>alert('Siswa berhasil dihapus!'); window.location='dashboard.php?tab=students';</script>";
    exit;
}

// --- AMBIL DATA USER ADMIN ---
if (!isset($admin_data)) {
    $stmt_user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt_user->execute([$admin_id]);
    $admin_data = $stmt_user->fetch();
}

// --- AMBIL DATA UNTUK DASHBOARD ---
// 1. Pending Products
$stmt_pending = $pdo->prepare("SELECT p.*, u.name as student_name FROM products p JOIN users u ON p.user_id = u.id WHERE p.school_id = ? AND p.status = 'pending' ORDER BY p.created_at ASC");
$stmt_pending->execute([$school_id]);
$pending_items = $stmt_pending->fetchAll();

// 2. Active Products
$stmt_active = $pdo->prepare("SELECT p.*, u.name as student_name FROM products p JOIN users u ON p.user_id = u.id WHERE p.school_id = ? AND p.status = 'active' ORDER BY p.created_at DESC");
$stmt_active->execute([$school_id]);
$active_items = $stmt_active->fetchAll();

// 3. Reports
$stmt_reports = $pdo->prepare("SELECT r.id as report_id, r.reason, r.created_at, p.id as product_id, p.title, p.image, u.name as student_name FROM reports r JOIN products p ON r.product_id = p.id JOIN users u ON p.user_id = u.id WHERE p.school_id = ? ORDER BY r.created_at DESC");
$stmt_reports->execute([$school_id]);
$reports = $stmt_reports->fetchAll();

// 4. Data Siswa
$stmt_students = $pdo->prepare("SELECT * FROM users WHERE school_id = ? AND role = 'student' ORDER BY name ASC");
$stmt_students->execute([$school_id]);
$students = $stmt_students->fetchAll();

// Penentuan Tab Aktif (Default: pending)
$tab = isset($_GET['tab']) ? $_GET['tab'] : (isset($_POST['update_profile']) ? 'profile' : 'pending');

require_once '../layout/header.php';
?>

<div class="flex h-screen bg-gray-100">
    
    <div class="w-64 bg-white shadow-md flex-shrink-0 flex flex-col hidden md:flex">
        <div class="p-6 border-b">
            <h2 class="text-2xl font-bold text-primary">Admin Panel</h2>
            <p class="text-xs text-gray-500 mt-1"><?= $_SESSION['name'] ?></p>
        </div>
        
        <nav class="flex-1 p-4 space-y-2">
            <button onclick="switchTab('pending')" id="link-pending" class="sidebar-link w-full text-left px-4 py-3 rounded-lg flex items-center gap-3 transition <?= $tab == 'pending' ? 'bg-primary text-white shadow-md' : 'text-gray-600 hover:bg-gray-50' ?>">
                <span>📋</span> Moderasi <span class="ml-auto bg-red-100 text-red-600 text-xs font-bold px-2 py-0.5 rounded-full"><?= count($pending_items) ?></span>
            </button>
            
            <button onclick="switchTab('active')" id="link-active" class="sidebar-link w-full text-left px-4 py-3 rounded-lg flex items-center gap-3 transition <?= $tab == 'active' ? 'bg-primary text-white shadow-md' : 'text-gray-600 hover:bg-gray-50' ?>">
                <span>📦</span> Katalog Aktif
            </button>
            
            <button onclick="switchTab('reports')" id="link-reports" class="sidebar-link w-full text-left px-4 py-3 rounded-lg flex items-center gap-3 transition <?= $tab == 'reports' ? 'bg-primary text-white shadow-md' : 'text-gray-600 hover:bg-gray-50' ?>">
                <span>🚩</span> Laporan <span class="ml-auto bg-gray-100 text-gray-600 text-xs font-bold px-2 py-0.5 rounded-full"><?= count($reports) ?></span>
            </button>

            <button onclick="switchTab('students')" id="link-students" class="sidebar-link w-full text-left px-4 py-3 rounded-lg flex items-center gap-3 transition <?= $tab == 'students' ? 'bg-primary text-white shadow-md' : 'text-gray-600 hover:bg-gray-50' ?>">
                <span>👥</span> Kelola Siswa
            </button>
            
            <button onclick="switchTab('profile')" id="link-profile" class="sidebar-link w-full text-left px-4 py-3 rounded-lg flex items-center gap-3 transition <?= $tab == 'profile' ? 'bg-primary text-white shadow-md' : 'text-gray-600 hover:bg-gray-50' ?>">
                <span>⚙️</span> Profil Saya
            </button>
        </nav>

        <div class="p-4 border-t">
            <a href="../auth/logout.php" class="block w-full text-center text-red-600 bg-red-50 hover:bg-red-100 py-2 rounded-lg font-semibold transition">Logout</a>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto p-8">
        
        <div class="md:hidden mb-6 flex justify-between items-center">
            <h1 class="text-xl font-bold text-gray-800">Admin Dashboard</h1>
            </div>

        <div id="content-pending" class="content-section <?= $tab == 'pending' ? '' : 'hidden' ?>">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Moderasi Karya (<?= count($pending_items) ?>)</h2>
            <?php if(count($pending_items) == 0): ?>
                <div class="text-center py-20 bg-white rounded-xl shadow-sm border border-dashed border-gray-300">
                    <p class="text-gray-500">Bagus! Tidak ada antrian moderasi.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach($pending_items as $p): ?>
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                        <img src="../uploads/<?= $p['image'] ?>" class="w-full h-48 object-cover bg-gray-200">
                        <div class="p-5">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-bold text-lg text-gray-800 truncate"><?= $p['title'] ?></h3>
                                <span class="text-primary font-bold">Rp <?= number_format($p['price']) ?></span>
                            </div>
                            <p class="text-xs text-gray-500 mb-3">Oleh: <?= $p['student_name'] ?></p>
                            
                            <form action="process.php" method="POST" class="space-y-3">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button type="submit" name="action" value="approve" class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-2 rounded-lg transition shadow-sm">
                                    ✓ Setujui
                                </button>
                                <button type="submit" name="action" value="reject" class="w-full bg-white border border-red-200 text-red-500 hover:bg-red-50 font-bold py-2 rounded-lg transition">
                                    ✗ Tolak
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div id="content-active" class="content-section <?= $tab == 'active' ? '' : 'hidden' ?>">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Katalog Aktif</h2>
            <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-100">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produk</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Siswa</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach($active_items as $p): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <img class="h-10 w-10 rounded-lg object-cover mr-3" src="../uploads/<?= $p['image'] ?>" alt="">
                                    <div class="text-sm font-medium text-gray-900"><?= $p['title'] ?></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $p['student_name'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Rp <?= number_format($p['price']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <a href="../student/product_form.php?id=<?= $p['id'] ?>" class="text-blue-600 hover:text-blue-900 bg-blue-50 px-3 py-1 rounded-md">Edit</a>
                                <a href="process.php?action=delete&id=<?= $p['id'] ?>" onclick="return confirm('Hapus produk?')" class="text-red-600 hover:text-red-900 bg-red-50 px-3 py-1 rounded-md">Hapus</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="content-reports" class="content-section <?= $tab == 'reports' ? '' : 'hidden' ?>">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Laporan Masuk</h2>
            <?php if(count($reports) == 0): ?>
                <div class="p-6 bg-green-50 border border-green-200 rounded-lg text-green-700">Aman! Tidak ada laporan.</div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach($reports as $rep): ?>
                    <div class="bg-white p-4 rounded-xl shadow-sm border-l-4 border-red-500 flex justify-between items-center">
                        <div>
                            <h4 class="font-bold text-gray-800">Laporan: <?= $rep['title'] ?></h4>
                            <p class="text-sm text-gray-600">Alasan: <span class="text-red-600 font-semibold"><?= $rep['reason'] ?></span></p>
                        </div>
                        <div class="flex gap-2">
                            <a href="../student/product_form.php?id=<?= $rep['product_id'] ?>" target="_blank" class="px-4 py-2 bg-gray-100 rounded-lg text-sm hover:bg-gray-200">Cek</a>
                            <a href="process.php?action=delete&id=<?= $rep['product_id'] ?>" onclick="return confirm('Takedown produk ini?')" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">Takedown</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div id="content-students" class="content-section <?= $tab == 'students' ? '' : 'hidden' ?>">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Daftar Siswa</h2>
            <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-100">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Siswa</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. HP</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach($students as $s): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900"><?= $s['name'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $s['email'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $s['phone'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <a href="?delete_student=<?= $s['id'] ?>" onclick="return confirm('PERINGATAN: Menghapus siswa ini akan menghapus SEMUA produk mereka secara permanen. Lanjutkan?')" class="text-red-600 hover:text-red-900 bg-red-50 px-3 py-1 rounded-md font-medium">
                                    Hapus Akun
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="content-profile" class="content-section <?= $tab == 'profile' ? '' : 'hidden' ?>">
            <div class="max-w-2xl bg-white p-8 rounded-xl shadow-sm border border-gray-100">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-4 border-b">Edit Profil</h2>
                
                <?php if($success_profile) echo "<div class='bg-green-100 text-green-700 p-3 rounded mb-4'>$success_profile</div>"; ?>
                <?php if($error_profile) echo "<div class='bg-red-100 text-red-700 p-3 rounded mb-4'>$error_profile</div>"; ?>

                <form method="POST">
                    <div class="space-y-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Lengkap</label>
                            <input type="text" name="name" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : $admin_data['name'] ?>" class="w-full border p-3 rounded-lg focus:ring-primary focus:border-primary" required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Nomor WhatsApp</label>
                            <input type="number" name="phone" value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : $admin_data['phone'] ?>" class="w-full border p-3 rounded-lg bg-yellow-50 focus:bg-white transition" required>
                            <p class="text-xs text-gray-500 mt-1">Sistem otomatis mengubah 08xx ke 62xx.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : $admin_data['email'] ?>" class="w-full border p-3 rounded-lg" required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Password Baru (Opsional)</label>
                            <input type="password" name="password" class="w-full border p-3 rounded-lg" placeholder="Isi jika ingin mengganti password">
                        </div>
                        <button type="submit" name="update_profile" class="w-full bg-primary text-white py-3 rounded-lg font-bold hover:bg-teal-700 shadow-md transition">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
function switchTab(tabName) {
    // Sembunyikan semua konten
    document.querySelectorAll('.content-section').forEach(el => el.classList.add('hidden'));
    
    // Reset style sidebar links
    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.classList.remove('bg-primary', 'text-white', 'shadow-md');
        link.classList.add('text-gray-600', 'hover:bg-gray-50');
    });

    // Tampilkan konten terpilih
    document.getElementById('content-' + tabName).classList.remove('hidden');
    
    // Highlight link terpilih
    const activeLink = document.getElementById('link-' + tabName);
    activeLink.classList.remove('text-gray-600', 'hover:bg-gray-50');
    activeLink.classList.add('bg-primary', 'text-white', 'shadow-md');
}
</script>