<?php
require_once '../config/database.php';
require_once '../config/functions.php';
checkRole(['superadmin']); // Hanya Super Admin
require_once '../layout/header.php';

// --- Inisialisasi Variabel Sticky ---
$val_school_id = '';
$val_name = '';
$val_email = '';
$val_phone = '';

// --- 1. LOGIC HANDLER (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // A. Tambah Sekolah
    if (isset($_POST['add_school'])) {
        $name = sanitize($_POST['school_name']);
        if($name) {
            $pdo->prepare("INSERT INTO schools (name) VALUES (?)")->execute([$name]);
            echo "<script>alert('Sekolah berhasil ditambah!'); window.location='?page=schools';</script>";
        }
    }
    
    // B. Tambah Admin
    if (isset($_POST['add_admin'])) {
        $school_id = $_POST['school_id'];
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $phone_input = sanitize($_POST['phone']);
        $password = $_POST['password'];

        $val_school_id = $school_id;
        $val_name = $name;
        $val_email = $email;
        $val_phone = $phone_input;

        $phone = preg_replace('/[^0-9]/', '', $phone_input);
        $error_msg = null;
        if (substr($phone, 0, 1) == '0') $phone = '62' . substr($phone, 1);
        elseif (substr($phone, 0, 2) != '62') $error_msg = "Format nomor HP salah. Gunakan awalan 08 atau 62.";
        if (!$error_msg && strlen($phone) < 10) $error_msg = "Nomor HP terlalu pendek.";

        if ($error_msg) {
            echo "<script>alert('$error_msg');</script>";
        } else {
            $cek = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $cek->execute([$email]);
            if ($cek->rowCount() > 0) {
                echo "<script>alert('Gagal: Email sudah terdaftar!');</script>";
            } else {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $sql = "INSERT INTO users (school_id, name, email, password, phone, role) VALUES (?, ?, ?, ?, ?, 'admin')";
                $pdo->prepare($sql)->execute([$school_id, $name, $email, $hashed_password, $phone]);
                $val_school_id = $val_name = $val_email = $val_phone = ''; 
                echo "<script>alert('Admin Sekolah berhasil dibuat!'); window.location='?page=admins';</script>";
            }
        }
    }

    // C. Hapus Data
    if (isset($_POST['delete_type'])) {
        $id = $_POST['delete_id'];
        if ($_POST['delete_type'] == 'school') {
            $pdo->prepare("DELETE FROM schools WHERE id = ?")->execute([$id]);
        } elseif ($_POST['delete_type'] == 'user') {
            $stmtProd = $pdo->prepare("SELECT image, image1, image2 FROM products WHERE user_id = ?");
            $stmtProd->execute([$id]);
            $userProducts = $stmtProd->fetchAll();
            foreach ($userProducts as $p) {
                $filesToDelete = [$p['image'], $p['image1'], $p['image2']];
                foreach ($filesToDelete as $file) {
                    if (!empty($file) && file_exists('../uploads/' . $file)) unlink('../uploads/' . $file); 
                }
            }
            $pdo->prepare("DELETE FROM products WHERE user_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        }
        $redirect_page = isset($_GET['page']) ? $_GET['page'] : 'schools';
        echo "<script>window.location='?page=$redirect_page';</script>";
    }

    // D. Takedown Produk
    if (isset($_POST['takedown_product'])) {
        $id = $_POST['product_id'];
        $stmt = $pdo->prepare("SELECT image, image1, image2 FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $img = $stmt->fetch();
        if($img) {
            $filesToDelete = [$img['image'], $img['image1'], $img['image2']];
            foreach ($filesToDelete as $file) {
                if (!empty($file) && file_exists('../uploads/' . $file)) unlink('../uploads/' . $file);
            }
        }
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
        echo "<script>alert('Produk berhasil di-takedown!'); window.location='?page=products';</script>";
    }

    // E. Hapus Laporan
    if (isset($_POST['dismiss_report'])) {
        $id = $_POST['report_id'];
        $pdo->prepare("DELETE FROM reports WHERE id = ?")->execute([$id]);
        echo "<script>window.location='?page=reports';</script>";
    }
}

// --- 2. NAVIGASI HALAMAN ---
$page = isset($_GET['page']) ? $_GET['page'] : 'overview';

// --- 3. DATA OVERVIEW (STATISTIK) ---
if ($page == 'overview') {
    $stat_schools = $pdo->query("SELECT COUNT(*) FROM schools")->fetchColumn();
    $stat_admins = $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
    $stat_students = $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
    $stat_public = $pdo->query("SELECT COUNT(*) FROM users WHERE role='public'")->fetchColumn(); 
    $stat_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $stat_reports = $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();
    
    // Data untuk Grafik Batang
    $chart_labels = ['Sekolah', 'Guru Admin', 'Siswa', 'Akun Publik', 'Karya', 'Laporan'];
    $chart_data = [$stat_schools, $stat_admins, $stat_students, $stat_public, $stat_products, $stat_reports];
    
    $recent_schools = $pdo->query("SELECT * FROM schools ORDER BY id DESC LIMIT 5")->fetchAll();
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="flex min-h-[calc(100vh-64px)] bg-gray-50">
    
    <aside class="w-64 bg-white shadow-xl hidden md:block border-r border-gray-100 flex-shrink-0 z-10">
        <div class="p-6">
            <h2 class="text-2xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-teal-500 to-emerald-600">Super Admin</h2>
            <p class="text-xs text-gray-400 mt-1 font-medium tracking-wide">CONTROL PANEL PUSAT</p>
        </div>
        <nav class="mt-2 space-y-1 px-3">
            <a href="?page=overview" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?= $page=='overview' ? 'bg-teal-50 text-teal-700 font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <span>📊</span> Ringkasan
            </a>
            <a href="?page=schools" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?= $page=='schools' ? 'bg-teal-50 text-teal-700 font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <span>🏫</span> Data Sekolah
            </a>
            <a href="?page=admins" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?= $page=='admins' ? 'bg-teal-50 text-teal-700 font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <span>👨‍🏫</span> Admin Sekolah
            </a>
            <a href="?page=students" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?= $page=='students' ? 'bg-teal-50 text-teal-700 font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <span>🎓</span> Data Siswa
            </a>
            <a href="?page=public_users" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?= $page=='public_users' ? 'bg-teal-50 text-teal-700 font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <span>👤</span> Akun Publik
            </a>
            <a href="?page=products" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?= $page=='products' ? 'bg-teal-50 text-teal-700 font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <span>🛍️</span> Semua Karya
            </a>
            <a href="?page=reports" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?= $page=='reports' ? 'bg-teal-50 text-teal-700 font-bold shadow-sm' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <span>🚩</span> Laporan
            </a>
        </nav>
    </aside>

    <main class="flex-1 p-8 overflow-y-auto h-[calc(100vh-64px)]">
        
        <?php if($page == 'overview'): ?>
            <h1 class="text-3xl font-bold text-gray-800 tracking-tight mb-8">Ringkasan Sistem</h1>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 mb-8">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col hover:shadow-md transition">
                    <span class="text-xs text-gray-500 uppercase font-bold tracking-wide">Total Sekolah</span>
                    <span class="text-4xl font-extrabold text-teal-600 mt-2"><?= number_format($stat_schools) ?></span>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col hover:shadow-md transition">
                    <span class="text-xs text-gray-500 uppercase font-bold tracking-wide">Total Guru Admin</span>
                    <span class="text-4xl font-extrabold text-blue-600 mt-2"><?= number_format($stat_admins) ?></span>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col hover:shadow-md transition">
                    <span class="text-xs text-gray-500 uppercase font-bold tracking-wide">Total Siswa</span>
                    <span class="text-4xl font-extrabold text-green-600 mt-2"><?= number_format($stat_students) ?></span>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col hover:shadow-md transition">
                    <span class="text-xs text-gray-500 uppercase font-bold tracking-wide">Akun Publik</span>
                    <span class="text-4xl font-extrabold text-gray-700 mt-2"><?= number_format($stat_public) ?></span>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col hover:shadow-md transition">
                    <span class="text-xs text-gray-500 uppercase font-bold tracking-wide">Karya Diupload</span>
                    <span class="text-4xl font-extrabold text-purple-600 mt-2"><?= number_format($stat_products) ?></span>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col hover:shadow-md transition">
                    <span class="text-xs text-gray-500 uppercase font-bold tracking-wide">Laporan Masuk</span>
                    <span class="text-4xl font-extrabold text-red-600 mt-2"><?= number_format($stat_reports) ?></span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 lg:col-span-2">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-bold text-xl text-gray-800">Analisis Data Sistem</h3>
                    </div>
                    
                    <div class="relative w-full h-[350px]">
                        <canvas id="barChart"></canvas>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-bold text-gray-800">Sekolah Baru</h3>
                        <a href="?page=schools" class="text-xs font-bold text-teal-600 hover:underline">Lihat Semua</a>
                    </div>
                    <div class="space-y-4">
                        <?php foreach($recent_schools as $rs): ?>
                        <div class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition border border-transparent hover:border-gray-100">
                            <div class="w-10 h-10 rounded-full bg-teal-50 flex items-center justify-center text-teal-600 font-bold shadow-sm">
                                <?= substr($rs['name'], 0, 1) ?>
                            </div>
                            <div class="overflow-hidden">
                                <p class="text-sm font-bold text-gray-800 truncate"><?= $rs['name'] ?></p>
                                <p class="text-[10px] text-gray-400">ID: #<?= str_pad($rs['id'], 3, '0', STR_PAD_LEFT) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if(count($recent_schools) == 0): ?>
                            <p class="text-sm text-gray-400 text-center py-4">Belum ada data sekolah.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <script>
                const ctx = document.getElementById('barChart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($chart_labels) ?>,
                        datasets: [{
                            label: 'Jumlah Data',
                            data: <?= json_encode($chart_data) ?>,
                            backgroundColor: [
                                '#0d9488', // Teal
                                '#2563eb', // Blue
                                '#16a34a', // Green
                                '#374151', // Gray
                                '#9333ea', // Purple
                                '#dc2626'  // Red
                            ],
                            borderRadius: 8,
                            barThickness: 40
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: 'rgba(17, 24, 39, 0.9)',
                                padding: 12,
                                cornerRadius: 8
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1, // Kunci: Jarak antar angka minimal 1 (tidak ada koma)
                                    precision: 0, // Kunci: Tidak menampilkan desimal
                                    font: { family: "'Inter', sans-serif" }
                                },
                                grid: {
                                    color: '#f3f4f6',
                                    borderDash: [5, 5]
                                }
                            },
                            x: {
                                grid: { display: false },
                                ticks: { 
                                    font: { family: "'Inter', sans-serif", weight: 'bold' } 
                                }
                            }
                        }
                    }
                });
            </script>

        <?php elseif($page == 'schools'): ?>
            <?php $schools = $pdo->query("SELECT * FROM schools ORDER BY name ASC")->fetchAll(); ?>
            <h1 class="text-2xl font-bold mb-6 text-gray-800">Manajemen Data Sekolah</h1>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded shadow h-fit">
                    <h3 class="font-bold border-b pb-2 mb-4">Tambah Sekolah Baru</h3>
                    <form method="POST" class="flex flex-col gap-3">
                        <label class="text-sm text-gray-600">Nama Sekolah</label>
                        <input type="text" name="school_name" placeholder="Contoh: SMA Negeri 1..." class="border p-2 rounded focus:ring-1 focus:ring-primary" required>
                        <button type="submit" name="add_school" class="bg-primary text-white py-2 rounded font-bold hover:bg-teal-700 transition">Simpan Sekolah</button>
                    </form>
                </div>
                <div class="md:col-span-2 bg-white p-6 rounded shadow">
                    <h3 class="font-bold border-b pb-2 mb-4">Daftar Sekolah Terdaftar (<?= count($schools) ?>)</h3>
                    <div class="overflow-y-auto max-h-[500px]">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 border-b sticky top-0"><tr><th class="py-3 px-2">Nama Sekolah</th><th class="py-3 px-2 text-right">Aksi</th></tr></thead>
                            <tbody>
                                <?php foreach($schools as $s): ?>
                                <tr class="border-b last:border-0 hover:bg-gray-50 transition">
                                    <td class="py-3 px-2 font-medium"><?= $s['name'] ?></td>
                                    <td class="py-3 px-2 text-right">
                                        <form method="POST" onsubmit="return confirm('PERINGATAN: Menghapus sekolah akan menghapus SEMUA DATA (Guru, Siswa, Produk) di sekolah ini. Lanjutkan?')">
                                            <input type="hidden" name="delete_type" value="school"><input type="hidden" name="delete_id" value="<?= $s['id'] ?>">
                                            <button class="bg-red-100 text-red-600 px-3 py-1 rounded text-xs hover:bg-red-200 font-bold">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif($page == 'admins'): ?>
            <?php 
            $schools = $pdo->query("SELECT * FROM schools ORDER BY name ASC")->fetchAll();
            $p_num = isset($_GET['p']) ? (int)$_GET['p'] : 1; $limit = 20; $offset = ($p_num - 1) * $limit;
            $search_admin = isset($_GET['q']) ? $_GET['q'] : '';
            $whereClause = "WHERE u.role = 'admin'";
            if($search_admin) { $whereClause .= " AND (u.name LIKE '%$search_admin%' OR s.name LIKE '%$search_admin%')"; }
            $total_sql = "SELECT COUNT(*) FROM users u JOIN schools s ON u.school_id = s.id $whereClause";
            $total_rows = $pdo->query($total_sql)->fetchColumn();
            $total_pages = ceil($total_rows / $limit);
            $sql = "SELECT u.*, s.name as school_name FROM users u JOIN schools s ON u.school_id = s.id $whereClause ORDER BY s.name ASC, u.name ASC LIMIT $limit OFFSET $offset";
            $admins = $pdo->query($sql)->fetchAll();
            ?>
            <h1 class="text-2xl font-bold mb-6 text-gray-800">Kelola Admin Sekolah (Guru)</h1>
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded shadow h-fit">
                    <h3 class="font-bold border-b pb-2 mb-4">Buat Akun Admin</h3>
                    <form method="POST" id="adminForm" onsubmit="return validateAdmin(event)" class="flex flex-col gap-4">
                        <div><label class="block text-xs font-bold text-gray-500 mb-1">Sekolah</label><select name="school_id" class="w-full border p-2 rounded focus:ring-1 focus:ring-primary" required><option value="">-- Pilih Sekolah --</option><?php foreach($schools as $s): ?><option value="<?= $s['id'] ?>" <?= $val_school_id == $s['id'] ? 'selected' : '' ?>><?= $s['name'] ?></option><?php endforeach; ?></select></div>
                        <div><label class="block text-xs font-bold text-gray-500 mb-1">Nama Guru</label><input type="text" name="name" value="<?= htmlspecialchars($val_name) ?>" class="w-full border p-2 rounded" required></div>
                        <div><label class="block text-xs font-bold text-gray-500 mb-1">WhatsApp (62...)</label><input type="text" name="phone" id="phoneInput" value="<?= htmlspecialchars($val_phone) ?>" class="w-full border p-2 rounded" required></div>
                        <div><label class="block text-xs font-bold text-gray-500 mb-1">Email Login</label><input type="email" name="email" value="<?= htmlspecialchars($val_email) ?>" class="w-full border p-2 rounded" required></div>
                        <div><label class="block text-xs font-bold text-gray-500 mb-1">Password</label><input type="text" name="password" class="w-full border p-2 rounded" required></div>
                        <button type="submit" name="add_admin" class="bg-secondary text-white py-2 rounded font-bold hover:bg-teal-600 transition">Simpan Akun</button>
                    </form>
                </div>
                <div class="xl:col-span-2 bg-white p-6 rounded shadow flex flex-col h-full">
                    <div class="flex justify-between items-center border-b pb-2 mb-4">
                        <h3 class="font-bold">List Admin Aktif</h3>
                        <form class="flex gap-2"><input type="hidden" name="page" value="admins"><input type="text" name="q" value="<?= htmlspecialchars($search_admin) ?>" placeholder="Cari Guru / Sekolah..." class="border px-2 py-1 rounded text-sm w-48"><button class="bg-gray-100 px-3 py-1 rounded text-sm hover:bg-gray-200">🔍</button></form>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <table class="w-full text-sm text-left"><thead class="bg-gray-50 border-b sticky top-0"><tr><th class="py-3 px-2">Nama & Kontak</th><th class="py-3 px-2">Asal Sekolah</th><th class="py-3 px-2 text-right">Aksi</th></tr></thead><tbody><?php if(count($admins) == 0): ?><tr><td colspan="3" class="text-center py-4 text-gray-500">Tidak ada data admin ditemukan.</td></tr><?php else: ?><?php foreach($admins as $a): ?><tr class="border-b last:border-0 hover:bg-gray-50"><td class="py-3 px-2"><div class="font-bold text-gray-800"><?= $a['name'] ?></div><div class="text-xs text-gray-500"><?= $a['email'] ?></div><div class="text-xs text-gray-500"><?= $a['phone'] ?></div></td><td class="py-3 px-2"><span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded font-bold"><?= $a['school_name'] ?></span></td><td class="py-3 px-2 text-right"><form method="POST" onsubmit="return confirm('Hapus akun admin ini? Dia tidak akan bisa login lagi.')"><input type="hidden" name="delete_type" value="user"><input type="hidden" name="delete_id" value="<?= $a['id'] ?>"><button class="text-red-500 hover:text-red-700 font-bold text-xs border border-red-200 bg-red-50 px-3 py-1 rounded">Hapus</button></form></td></tr><?php endforeach; ?><?php endif; ?></tbody></table>
                    </div>
                    <?php if($total_pages > 1): ?><div class="flex justify-center gap-2 mt-4 pt-4 border-t"><?php if($p_num > 1): ?><a href="?page=admins&q=<?= $search_admin ?>&p=<?= $p_num - 1 ?>" class="px-3 py-1 border rounded hover:bg-gray-100 text-sm">Prev</a><?php endif; ?><span class="px-3 py-1 border rounded bg-gray-100 font-bold text-sm">Hal <?= $p_num ?></span><?php if($p_num < $total_pages): ?><a href="?page=admins&q=<?= $search_admin ?>&p=<?= $p_num + 1 ?>" class="px-3 py-1 border rounded hover:bg-gray-100 text-sm">Next</a><?php endif; ?></div><?php endif; ?>
                </div>
            </div>

        <?php elseif($page == 'students'): ?>
            <?php 
            $p_num = isset($_GET['p']) ? (int)$_GET['p'] : 1; $limit = 50; $offset = ($p_num - 1) * $limit;
            $search_student = isset($_GET['q']) ? $_GET['q'] : '';
            $whereClause = "WHERE u.role = 'student'";
            if($search_student) { $whereClause .= " AND (u.name LIKE '%$search_student%' OR u.email LIKE '%$search_student%' OR s.name LIKE '%$search_student%')"; }
            $total_sql = "SELECT COUNT(*) FROM users u LEFT JOIN schools s ON u.school_id = s.id $whereClause";
            $total_rows = $pdo->query($total_sql)->fetchColumn();
            $total_pages = ceil($total_rows / $limit);
            $sql = "SELECT u.*, s.name as school_name FROM users u LEFT JOIN schools s ON u.school_id = s.id $whereClause ORDER BY s.name ASC, u.name ASC LIMIT $limit OFFSET $offset";
            $students = $pdo->query($sql)->fetchAll();
            ?>
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                <div><h1 class="text-2xl font-bold text-gray-800">Manajemen Siswa (Global)</h1><p class="text-sm text-gray-500">Total Siswa: <?= number_format($total_rows) ?></p></div>
                <form class="flex gap-2"><input type="hidden" name="page" value="students"><input type="text" name="q" value="<?= htmlspecialchars($search_student) ?>" placeholder="Cari Nama / Email / Sekolah..." class="border p-2 rounded w-64"><button type="submit" class="bg-primary text-white px-4 py-2 rounded">Cari</button></form>
            </div>
            <div class="bg-white rounded shadow overflow-hidden mb-6">
                <table class="w-full text-left text-sm whitespace-nowrap"><thead class="bg-gray-50 border-b"><tr><th class="p-4">Nama Siswa</th><th class="p-4">Sekolah</th><th class="p-4">Kontak (Email / WA)</th><th class="p-4 text-center">Aksi</th></tr></thead><tbody class="divide-y"><?php if(empty($students)): ?><tr><td colspan="4" class="p-4 text-center text-gray-500">Data siswa tidak ditemukan.</td></tr><?php else: ?><?php foreach($students as $s): ?><tr class="hover:bg-gray-50"><td class="p-4 font-bold"><?= $s['name'] ?></td><td class="p-4"><span class="bg-indigo-100 text-indigo-800 text-xs font-bold px-2 py-1 rounded"><?= $s['school_name'] ? $s['school_name'] : '(Tanpa Sekolah)' ?></span></td><td class="p-4"><div class="text-xs"><?= $s['email'] ?></div><div class="text-xs text-gray-500"><?= $s['phone'] ?></div></td><td class="p-4 text-center"><form method="POST" onsubmit="return confirm('PERINGATAN: Hapus siswa ini beserta seluruh karyanya?')"><input type="hidden" name="delete_type" value="user"><input type="hidden" name="delete_id" value="<?= $s['id'] ?>"><button class="bg-red-100 text-red-600 px-3 py-1 rounded text-xs hover:bg-red-200 font-bold">Hapus Akun</button></form></td></tr><?php endforeach; ?><?php endif; ?></tbody></table>
            </div>
            <?php if($total_pages > 1): ?><div class="flex justify-center gap-2 pb-10"><?php if($p_num > 1): ?><a href="?page=students&q=<?= $search_student ?>&p=<?= $p_num - 1 ?>" class="px-3 py-1 border rounded hover:bg-gray-100">Prev</a><?php endif; ?><span class="px-3 py-1 border rounded bg-gray-100 font-bold">Halaman <?= $p_num ?> dari <?= $total_pages ?></span><?php if($p_num < $total_pages): ?><a href="?page=students&q=<?= $search_student ?>&p=<?= $p_num + 1 ?>" class="px-3 py-1 border rounded hover:bg-gray-100">Next</a><?php endif; ?></div><?php endif; ?>

        <?php elseif($page == 'public_users'): ?>
            <?php 
            $p_num = isset($_GET['p']) ? (int)$_GET['p'] : 1; $limit = 50; $offset = ($p_num - 1) * $limit;
            $search_public = isset($_GET['q']) ? $_GET['q'] : '';
            $whereClause = "WHERE role = 'public'";
            if($search_public) { $whereClause .= " AND (name LIKE '%$search_public%' OR email LIKE '%$search_public%' OR phone LIKE '%$search_public%')"; }
            $total_sql = "SELECT COUNT(*) FROM users $whereClause";
            $total_rows = $pdo->query($total_sql)->fetchColumn();
            $total_pages = ceil($total_rows / $limit);
            $sql = "SELECT * FROM users $whereClause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
            $public_users = $pdo->query($sql)->fetchAll();
            ?>
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                <div><h1 class="text-2xl font-bold text-gray-800">Manajemen Akun Publik</h1><p class="text-sm text-gray-500">Total Pengguna Umum: <?= number_format($total_rows) ?></p></div>
                <form class="flex gap-2"><input type="hidden" name="page" value="public_users"><input type="text" name="q" value="<?= htmlspecialchars($search_public) ?>" placeholder="Cari Nama / Email / No.HP..." class="border p-2 rounded w-64"><button type="submit" class="bg-primary text-white px-4 py-2 rounded">Cari</button></form>
            </div>
            <div class="bg-white rounded shadow overflow-hidden mb-6">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-gray-50 border-b"><tr><th class="p-4">Nama Pengguna</th><th class="p-4">Email</th><th class="p-4">No. WhatsApp</th><th class="p-4 text-center">Aksi</th></tr></thead>
                    <tbody class="divide-y">
                        <?php if(empty($public_users)): ?>
                            <tr><td colspan="4" class="p-4 text-center text-gray-500">Data pengguna umum tidak ditemukan.</td></tr>
                        <?php else: ?>
                            <?php foreach($public_users as $pu): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-4 font-bold"><?= $pu['name'] ?></td>
                                <td class="p-4"><?= $pu['email'] ?></td>
                                <td class="p-4 text-gray-500"><?= $pu['phone'] ?></td>
                                <td class="p-4 text-center">
                                    <form method="POST" onsubmit="return confirm('PERINGATAN: Hapus akun pengguna ini? Seluruh komentar & like mereka akan hilang.')">
                                        <input type="hidden" name="delete_type" value="user"><input type="hidden" name="delete_id" value="<?= $pu['id'] ?>">
                                        <button class="bg-red-100 text-red-600 px-3 py-1 rounded text-xs hover:bg-red-200 font-bold">Hapus Akun</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if($total_pages > 1): ?><div class="flex justify-center gap-2 pb-10"><?php if($p_num > 1): ?><a href="?page=public_users&q=<?= $search_public ?>&p=<?= $p_num - 1 ?>" class="px-3 py-1 border rounded hover:bg-gray-100">Prev</a><?php endif; ?><span class="px-3 py-1 border rounded bg-gray-100 font-bold">Halaman <?= $p_num ?> dari <?= $total_pages ?></span><?php if($p_num < $total_pages): ?><a href="?page=public_users&q=<?= $search_public ?>&p=<?= $p_num + 1 ?>" class="px-3 py-1 border rounded hover:bg-gray-100">Next</a><?php endif; ?></div><?php endif; ?>

        <?php elseif($page == 'products'): ?>
            <?php 
            $query = "SELECT p.*, s.name as school_name, u.name as student_name FROM products p JOIN schools s ON p.school_id = s.id JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 50";
            $products = $pdo->query($query)->fetchAll();
            ?>
            <h1 class="text-2xl font-bold mb-6 text-gray-800">Moderasi Seluruh Karya Siswa</h1>
            <div class="bg-white rounded shadow overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap"><thead class="bg-gray-50 border-b"><tr><th class="p-4">Foto</th><th class="p-4">Judul & Harga</th><th class="p-4">Siswa & Sekolah</th><th class="p-4">Status</th><th class="p-4 text-center">Aksi</th></tr></thead><tbody class="divide-y"><?php foreach($products as $p): ?><tr class="hover:bg-gray-50"><td class="p-4"><img src="../uploads/<?= $p['image'] ?>" class="w-12 h-12 object-cover rounded bg-gray-200"></td><td class="p-4"><div class="font-bold"><?= $p['title'] ?></div><div class="text-xs text-gray-500">Rp <?= number_format($p['price']) ?></div></td><td class="p-4"><div><?= $p['student_name'] ?></div><div class="text-xs text-indigo-600 font-bold"><?= $p['school_name'] ?></div></td><td class="p-4"><?php $cls = $p['status']=='active'?'green':($p['status']=='rejected'?'red':'yellow'); ?><span class="px-2 py-1 rounded bg-<?= $cls ?>-100 text-<?= $cls ?>-800 text-xs font-bold uppercase"><?= $p['status'] ?></span></td><td class="p-4 text-center"><form method="POST" onsubmit="return confirm('PAKSA HAPUS (Takedown)? Karya ini akan hilang permanen.')"><input type="hidden" name="takedown_product" value="1"><input type="hidden" name="product_id" value="<?= $p['id'] ?>"><button class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700">Takedown</button></form></td></tr><?php endforeach; ?></tbody></table>
            </div>

        <?php elseif($page == 'reports'): ?>
            <?php 
            $query = "SELECT r.id as report_id, r.reason, r.created_at, p.id as product_id, p.title, p.image, s.name as school_name FROM reports r JOIN products p ON r.product_id = p.id JOIN schools s ON p.school_id = s.id ORDER BY r.created_at DESC";
            $reports = $pdo->query($query)->fetchAll();
            ?>
            <h1 class="text-2xl font-bold mb-6 text-gray-800">Laporan Pelanggaran (Global)</h1>
            <?php if(empty($reports)): ?><div class="bg-white p-8 rounded shadow text-center text-gray-500">Belum ada laporan masuk.</div><?php else: ?><div class="grid grid-cols-1 gap-4"><?php foreach($reports as $rep): ?><div class="bg-white p-4 rounded shadow border-l-4 border-red-500 flex flex-col md:flex-row justify-between items-center"><div class="flex items-center gap-4 mb-4 md:mb-0"><img src="../uploads/<?= $rep['image'] ?>" class="w-16 h-16 rounded object-cover"><div><h4 class="font-bold text-red-700">Laporan: <?= $rep['title'] ?></h4><p class="text-sm">Sekolah: <span class="font-bold"><?= $rep['school_name'] ?></span></p><p class="text-sm italic text-gray-600">"<?= $rep['reason'] ?>"</p></div></div><div class="flex gap-2"><form method="POST"><input type="hidden" name="dismiss_report" value="1"><input type="hidden" name="report_id" value="<?= $rep['report_id'] ?>"><button class="bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-300">Abaikan</button></form><form method="POST" onsubmit="return confirm('Hapus produk ini secara permanen?')"><input type="hidden" name="takedown_product" value="1"><input type="hidden" name="product_id" value="<?= $rep['product_id'] ?>"><button class="bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700">Takedown Produk</button></form></div></div><?php endforeach; ?></div><?php endif; ?>

        <?php endif; ?>

    </main>
</div>

<script>
function validateAdmin(event) {
    const phoneInput = document.getElementById('phoneInput');
    let phone = phoneInput.value.replace(/[^0-9]/g, '');
    if (!phone.startsWith('08') && !phone.startsWith('62')) { alert("Format nomor HP salah! \nHarus diawali '08' atau '62'.\nContoh: 08123456789"); event.preventDefault(); return false; }
    if (phone.length < 10) { alert("Nomor HP terlalu pendek (minimal 10 digit)."); event.preventDefault(); return false; }
    return true;
}
</script>