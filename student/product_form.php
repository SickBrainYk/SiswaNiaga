<?php
require_once '../config/database.php';
require_once '../config/functions.php';
checkRole(['student', 'admin']); 
require_once '../layout/header.php';

$categories = getCategories();
$id = $_GET['id'] ?? null;
$product = null;
$showModal = false; 
$adminData = null; 

// 1. Ambil Data Produk (Jika Edit)
if ($id) {
    $sql = "SELECT * FROM products WHERE id = ?";
    if($_SESSION['role'] == 'student') $sql .= " AND user_id = " . $_SESSION['user_id'];
    elseif($_SESSION['role'] == 'admin') $sql .= " AND school_id = " . $_SESSION['school_id'];
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if(!$product) die("Akses ditolak atau produk tidak ditemukan.");
}

// 2. Logic Simpan Data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category = sanitize($_POST['category']);
    $title = sanitize($_POST['title']);
    $desc = sanitize($_POST['description']);
    $price = $_POST['price'];
    $imageName = $product['image'] ?? '';

    // Logic Upload Gambar
    if (!empty($_FILES['image']['name'])) {
        $upload = uploadImage($_FILES['image']);
        if ($upload['status']) {
            if ($product && file_exists("../uploads/" . $product['image'])) {
                unlink("../uploads/" . $product['image']);
            }
            $imageName = $upload['fileName'];
        } else {
            $error = $upload['msg'];
        }
    }

    if (!isset($error)) {
        // Eksekusi Query
        if ($id) {
            $sql = "UPDATE products SET category=?, title=?, description=?, price=?, image=?, status='pending' WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$category, $title, $desc, $price, $imageName, $id]);
        } else {
            $sql = "INSERT INTO products (user_id, school_id, category, title, description, price, image) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_SESSION['user_id'], $_SESSION['school_id'], $category, $title, $desc, $price, $imageName]);
        }
        
        // AMBIL DATA ADMIN SEKOLAH
        $school_id = $_SESSION['school_id'];
        $stmtAdmin = $pdo->prepare("SELECT name, phone FROM users WHERE school_id = ? AND role = 'admin' LIMIT 1");
        $stmtAdmin->execute([$school_id]);
        $adminData = $stmtAdmin->fetch();

        // Aktifkan Modal
        $showModal = true;
    }
}
?>

<div class="max-w-2xl mx-auto px-4 py-8 bg-white shadow-lg mt-10 rounded-xl border border-gray-100">
    <div class="border-b pb-4 mb-6">
        <h2 class="text-2xl font-bold text-gray-800"><?= $id ? 'Edit' : 'Upload' ?> Karya</h2>
        <p class="text-sm text-gray-500">Lengkapi detail karya Anda untuk ditampilkan di katalog.</p>
    </div>

    <?php if(isset($error)) echo "<div class='bg-red-50 text-red-500 p-3 rounded mb-4 text-sm font-bold'>$error</div>"; ?>
    
    <form method="POST" enctype="multipart/form-data" class="space-y-5">
        
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Kategori Karya</label>
            <select name="category" class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition" required>
                <option value="">-- Pilih Kategori --</option>
                <?php foreach($categories as $key => $label): ?>
                    <option value="<?= $key ?>" <?= (isset($product['category']) && $product['category'] == $key) ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Judul Karya</label>
            <input type="text" name="title" value="<?= $product['title'] ?? '' ?>" class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition" placeholder="Contoh: Keripik Pisang Coklat" required>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Deskripsi</label>
            <textarea name="description" rows="4" class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition" placeholder="Jelaskan keunggulan produkmu..." required><?= $product['description'] ?? '' ?></textarea>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Harga (Rp)</label>
            <input type="number" name="price" value="<?= $product['price'] ?? '' ?>" class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition" placeholder="Contoh: 15000" required>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Foto Produk</label>
            <?php if(isset($product['image'])): ?>
                <div class="mb-3">
                    <img src="../uploads/<?= $product['image'] ?>" class="w-32 h-32 object-cover rounded-lg border">
                    <p class="text-xs text-gray-500 mt-1">Foto saat ini</p>
                </div>
            <?php endif; ?>
            <input type="file" name="image" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20" <?= $id ? '' : 'required' ?>>
            <p class="text-xs text-gray-400 mt-1">Format: JPG/PNG, Maks 2MB.</p>
        </div>

        <div class="pt-4">
            <button type="submit" class="w-full bg-primary text-white font-bold py-3 rounded-lg hover:bg-teal-700 transition shadow-md">
                Simpan Karya
            </button>
        </div>
    </form>
</div>

<?php if($showModal): ?>
<div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm animate-fade-in">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 text-center">
        
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
            <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>

        <h3 class="text-2xl font-bold text-gray-800 mb-2">Upload Berhasil!</h3>
        <p class="text-gray-600 mb-6">
            Karyamu sudah tersimpan. Silakan temui atau hubungi Admin Sekolah untuk persetujuan.
        </p>

        <?php if($adminData): ?>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-5 mb-6">
                <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">Nomor Admin Sekolah</p>
                <p class="text-3xl font-extrabold text-blue-800 tracking-wide select-all">
                    <?= $adminData['phone'] ?>
                </p>
                <p class="text-sm text-gray-600 mt-1 font-medium">(<?= $adminData['name'] ?>)</p>
            </div>
        <?php else: ?>
            <p class="text-red-500 mb-4 text-sm bg-red-50 p-2 rounded">Data Admin Sekolah tidak ditemukan.</p>
        <?php endif; ?>

        <a href="dashboard.php" class="block w-full bg-gray-800 text-white font-bold py-3 rounded-lg hover:bg-gray-900 transition">
            OK, Kembali ke Dashboard
        </a>

    </div>
</div>

<style>
    @keyframes fade-in {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
    .animate-fade-in {
        animation: fade-in 0.2s ease-out forwards;
    }
    /* Agar nomor HP bisa dicopy dengan mudah */
    .select-all {
        user-select: all;
    }
</style>
<?php endif; ?>