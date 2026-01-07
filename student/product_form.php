<?php
require_once '../config/database.php';
require_once '../config/functions.php';
checkRole(['student', 'admin']); // Admin juga bisa akses untuk edit
require_once '../layout/header.php';

// [BARU] Ambil daftar kategori baku dari functions.php
$categories = getCategories();

$id = $_GET['id'] ?? null;
$product = null;

if ($id) {
    // Jika Admin, bisa edit punya siapa saja di sekolahnya, Jika Siswa hanya punya sendiri
    $sql = "SELECT * FROM products WHERE id = ?";
    if($_SESSION['role'] == 'student') $sql .= " AND user_id = " . $_SESSION['user_id'];
    elseif($_SESSION['role'] == 'admin') $sql .= " AND school_id = " . $_SESSION['school_id'];
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if(!$product) die("Akses ditolak atau produk tidak ditemukan.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // [BARU] Ambil input category
    $category = sanitize($_POST['category']);
    $title = sanitize($_POST['title']);
    $desc = sanitize($_POST['description']);
    $price = $_POST['price'];
    $imageName = $product['image'] ?? '';

    // Logic Gambar
    if (!empty($_FILES['image']['name'])) {
        $upload = uploadImage($_FILES['image']);
        if ($upload['status']) {
            // Hapus gambar lama jika ada
            if ($product && file_exists("../uploads/" . $product['image'])) {
                unlink("../uploads/" . $product['image']);
            }
            $imageName = $upload['fileName'];
        } else {
            $error = $upload['msg'];
        }
    }

    if (!isset($error)) {
        if ($id) {
            // [BARU] Update Query dengan Category
            $sql = "UPDATE products SET category=?, title=?, description=?, price=?, image=?, status='pending' WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$category, $title, $desc, $price, $imageName, $id]);
        } else {
            // [BARU] Insert Query dengan Category
            $sql = "INSERT INTO products (user_id, school_id, category, title, description, price, image) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_SESSION['user_id'], $_SESSION['school_id'], $category, $title, $desc, $price, $imageName]);
        }
        
        $redirect = ($_SESSION['role'] == 'student') ? 'dashboard.php' : '../admin/dashboard.php';
        echo "<script>alert('Berhasil disimpan!'); window.location='$redirect';</script>";
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