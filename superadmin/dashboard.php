<?php
require_once '../config/database.php';
require_once '../config/functions.php';
checkRole(['superadmin']); // Hanya Super Admin
require_once '../layout/header.php';

// --- 1. LOGIC HANDLER (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // A. Logic Tambah Sekolah
    if (isset($_POST['add_school'])) {
        $name = sanitize($_POST['school_name']);
        if($name) {
            $pdo->prepare("INSERT INTO schools (name) VALUES (?)")->execute([$name]);
            echo "<script>alert('Sekolah berhasil ditambah!'); window.location='?page=schools';</script>";
        }
    }
    
    // B. Logic Tambah Admin Sekolah
    if (isset($_POST['add_admin'])) {
        $school_id = $_POST['school_id'];
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

        $cek = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $cek->execute([$email]);
        if ($cek->rowCount() > 0) {
            echo "<script>alert('Email sudah terdaftar!');</script>";
        } else {
            $sql = "INSERT INTO users (school_id, name, email, password, phone, role) VALUES (?, ?, ?, ?, ?, 'admin')";
            $pdo->prepare($sql)->execute([$school_id, $name, $email, $password, $phone]);
            echo "<script>alert('Admin Sekolah dibuat!'); window.location='?page=schools';</script>";
        }
    }

    // C. Logic Hapus (Sekolah/User)
    if (isset($_POST['delete_type'])) {
        $id = $_POST['delete_id'];
        if ($_POST['delete_type'] == 'school') {
            $pdo->prepare("DELETE FROM schools WHERE id = ?")->execute([$id]);
        } elseif ($_POST['delete_type'] == 'user') {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        }
        echo "<script>window.location='?page=schools';</script>";
    }

    // D. Logic Global Takedown (Produk)
    if (isset($_POST['takedown_product'])) {
        $id = $_POST['product_id'];
        // Hapus Gambar
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $img = $stmt->fetch();
        if($img && file_exists('../uploads/'.$img['image'])) unlink('../uploads/'.$img['image']);
        
        // Hapus Data
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
        echo "<script>alert('Produk berhasil di-takedown!'); window.location='?page=products';</script>";
    }

    // E. Logic Hapus Laporan
    if (isset($_POST['dismiss_report'])) {
        $id = $_POST['report_id'];
        $pdo->prepare("DELETE FROM reports WHERE id = ?")->execute([$id]);
        echo "<script>window.location='?page=reports';</script>";
    }
}

// --- 2. NAVIGASI HALAMAN (GET) ---
$page = isset($_GET['page']) ? $_GET['page'] : 'schools'; // Default halaman 'schools'
?>

<div class="flex min-h-[calc(100vh-64px)] bg-gray-100">
    
    <aside class="w-64 bg-white shadow-md hidden md:block border-r border-gray-200">
        <div class="p-6">
            <h2 class="text-xl font-bold text-primary">Super Admin</h2>
            <p class="text-xs text-gray-500">Control Panel Pusat</p>
        </div>
        <nav class="mt-2">
            <a href="?page=schools" class="block px-6 py-3 hover:bg-gray-50 <?= $page=='schools' ? 'bg-teal-50 text-primary font-bold border-r-4 border-primary' : 'text-gray-600' ?>">
                🏫 Manajemen Sekolah
            </a>
            <a href="?page=products" class="block px-6 py-3 hover:bg-gray-50 <?= $page=='products' ? 'bg-teal-50 text-primary font-bold border-r-4 border-primary' : 'text-gray-600' ?>">
                📦 Semua Karya Siswa
            </a>
            <a href="?page=reports" class="block px-6 py-3 hover:bg-gray-50 <?= $page=='reports' ? 'bg-teal-50 text-primary font-bold border-r-4 border-primary' : 'text-gray-600' ?>">
                🚩 Laporan Global
            </a>
        </nav>
    </aside>

    <main class="flex-1 p-8 overflow-y-auto h-[calc(100vh-64px)]">
        
        <?php if($page == 'schools'): ?>
            <?php 
            $schools = $pdo->query("SELECT * FROM schools ORDER BY name ASC")->fetchAll();
            $admins = $pdo->query("SELECT u.*, s.name as school_name FROM users u JOIN schools s ON u.school_id = s.id WHERE u.role = 'admin'")->fetchAll();
            ?>
            <h1 class="text-2xl font-bold mb-6 text-gray-800">Manajemen Sekolah & Guru</h1>
            
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded shadow">
                    <h3 class="font-bold border-b pb-2 mb-4">Daftar Sekolah</h3>
                    <form method="POST" class="flex gap-2 mb-4">
                        <input type="text" name="school_name" placeholder="Nama Sekolah Baru" class="flex-1 border p-2 rounded" required>
                        <button type="submit" name="add_school" class="bg-primary text-white px-4 py-2 rounded">+ Add</button>
                    </form>
                    <div class="overflow-y-auto max-h-60">
                        <table class="w-full text-sm">
                            <?php foreach($schools as $s): ?>
                            <tr class="border-b last:border-0">
                                <td class="py-2"><?= $s['name'] ?></td>
                                <td class="text-right">
                                    <form method="POST" onsubmit="return confirm('Hapus sekolah? Data guru/siswa ikut terhapus!')">
                                        <input type="hidden" name="delete_type" value="school">
                                        <input type="hidden" name="delete_id" value="<?= $s['id'] ?>">
                                        <button class="text-red-500 text-xs hover:underline">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>

                <div class="bg-white p-6 rounded shadow">
                    <h3 class="font-bold border-b pb-2 mb-4">Buat Akun Admin Sekolah</h3>
                    <form method="POST" class="grid grid-cols-2 gap-3 mb-6">
                        <select name="school_id" class="col-span-2 border p-2 rounded" required>
                            <option value="">Pilih Sekolah...</option>
                            <?php foreach($schools as $s): echo "<option value='{$s['id']}'>{$s['name']}</option>"; endforeach; ?>
                        </select>
                        <input type="text" name="name" placeholder="Nama Guru" class="border p-2 rounded" required>
                        <input type="text" name="phone" placeholder="No WA" class="border p-2 rounded" required>
                        <input type="email" name="email" placeholder="Email" class="border p-2 rounded" required>
                        <input type="text" name="password" placeholder="Password" class="border p-2 rounded" required>
                        <button type="submit" name="add_admin" class="col-span-2 bg-secondary text-white py-2 rounded font-bold">Simpan Akun</button>
                    </form>
                    
                    <h4 class="font-bold text-sm mb-2">List Admin Aktif</h4>
                    <div class="overflow-y-auto max-h-40">
                        <table class="w-full text-sm">
                            <?php foreach($admins as $a): ?>
                            <tr class="border-b">
                                <td class="py-2">
                                    <div class="font-bold"><?= $a['name'] ?></div>
                                    <div class="text-xs text-gray-500"><?= $a['school_name'] ?></div>
                                </td>
                                <td class="text-right">
                                    <form method="POST" onsubmit="return confirm('Hapus admin ini?')">
                                        <input type="hidden" name="delete_type" value="user">
                                        <input type="hidden" name="delete_id" value="<?= $a['id'] ?>">
                                        <button class="text-red-500 text-xs hover:underline">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif($page == 'products'): ?>
            <?php 
            $query = "SELECT p.*, s.name as school_name, u.name as student_name 
                      FROM products p 
                      JOIN schools s ON p.school_id = s.id 
                      JOIN users u ON p.user_id = u.id 
                      ORDER BY p.created_at DESC LIMIT 50"; // Limit agar tidak berat
            $products = $pdo->query($query)->fetchAll();
            ?>
            <h1 class="text-2xl font-bold mb-6 text-gray-800">Moderasi Seluruh Karya Siswa</h1>
            <div class="bg-white rounded shadow overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="p-4">Foto</th>
                            <th class="p-4">Judul & Harga</th>
                            <th class="p-4">Siswa & Sekolah</th>
                            <th class="p-4">Status</th>
                            <th class="p-4 text-center">Aksi (Superadmin)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php foreach($products as $p): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="p-4"><img src="../uploads/<?= $p['image'] ?>" class="w-12 h-12 object-cover rounded bg-gray-200"></td>
                            <td class="p-4">
                                <div class="font-bold"><?= $p['title'] ?></div>
                                <div class="text-xs text-gray-500">Rp <?= number_format($p['price']) ?></div>
                            </td>
                            <td class="p-4">
                                <div><?= $p['student_name'] ?></div>
                                <div class="text-xs text-indigo-600 font-bold"><?= $p['school_name'] ?></div>
                            </td>
                            <td class="p-4">
                                <?php $cls = $p['status']=='active'?'green':($p['status']=='rejected'?'red':'yellow'); ?>
                                <span class="px-2 py-1 rounded bg-<?= $cls ?>-100 text-<?= $cls ?>-800 text-xs font-bold uppercase"><?= $p['status'] ?></span>
                            </td>
                            <td class="p-4 text-center">
                                <form method="POST" onsubmit="return confirm('PAKSA HAPUS (Takedown)? Karya ini akan hilang permanen.')">
                                    <input type="hidden" name="takedown_product" value="1">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <button class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700">Takedown</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="p-4 text-xs text-gray-500 text-center">Menampilkan 50 karya terbaru.</p>
            </div>

        <?php elseif($page == 'reports'): ?>
            <?php 
            $query = "SELECT r.id as report_id, r.reason, r.created_at, 
                             p.id as product_id, p.title, p.image, 
                             s.name as school_name 
                      FROM reports r 
                      JOIN products p ON r.product_id = p.id 
                      JOIN schools s ON p.school_id = s.id 
                      ORDER BY r.created_at DESC";
            $reports = $pdo->query($query)->fetchAll();
            ?>
            <h1 class="text-2xl font-bold mb-6 text-gray-800">Laporan Pelanggaran (Global)</h1>
            
            <?php if(empty($reports)): ?>
                <div class="bg-white p-8 rounded shadow text-center text-gray-500">Belum ada laporan masuk.</div>
            <?php else: ?>
                <div class="grid grid-cols-1 gap-4">
                    <?php foreach($reports as $rep): ?>
                    <div class="bg-white p-4 rounded shadow border-l-4 border-red-500 flex flex-col md:flex-row justify-between items-center">
                        <div class="flex items-center gap-4 mb-4 md:mb-0">
                            <img src="../uploads/<?= $rep['image'] ?>" class="w-16 h-16 rounded object-cover">
                            <div>
                                <h4 class="font-bold text-red-700">Laporan: <?= $rep['title'] ?></h4>
                                <p class="text-sm">Sekolah: <span class="font-bold"><?= $rep['school_name'] ?></span></p>
                                <p class="text-sm italic text-gray-600">"<?= $rep['reason'] ?>"</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <form method="POST">
                                <input type="hidden" name="dismiss_report" value="1">
                                <input type="hidden" name="report_id" value="<?= $rep['report_id'] ?>">
                                <button class="bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-300">Abaikan</button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Hapus produk ini secara permanen?')">
                                <input type="hidden" name="takedown_product" value="1">
                                <input type="hidden" name="product_id" value="<?= $rep['product_id'] ?>">
                                <button class="bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700">Takedown Produk</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </main>
</div>