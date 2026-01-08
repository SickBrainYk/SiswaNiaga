<?php
require_once '../config/database.php';
require_once '../config/functions.php';
checkRole(['student']);

// --- LOGIKA HAPUS (Ditaruh sebelum load header) ---
if (isset($_GET['delete_id'])) {
    $id_produk = $_GET['delete_id'];
    $user_id = $_SESSION['user_id']; // Keamanan: Ambil ID user dari session

    // 1. Ambil nama file dari KETIGA kolom gambar
    // Pastikan produk milik user yang sedang login
    $stmt = $pdo->prepare("SELECT image, image1, image2 FROM products WHERE id = ? AND user_id = ?");
    $stmt->execute([$id_produk, $user_id]);
    $product = $stmt->fetch();

    if ($product) {
        // Daftar kolom gambar yang ingin dicek
        $imageFields = ['image', 'image1', 'image2'];

        // 2. Loop setiap kolom untuk menghapus file fisiknya
        foreach ($imageFields as $field) {
            $filename = $product[$field];
            
            // Cek apakah database berisi nama file (tidak kosong/null)
            if (!empty($filename)) {
                $path_file = "../uploads/" . $filename;
                
                // Cek apakah file fisik benar-benar ada di folder
                if (file_exists($path_file)) {
                    unlink($path_file); // Hapus file
                }
            }
        }

        // 3. Setelah gambar bersih, hapus data dari database
        $stmt_del = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt_del->execute([$id_produk]);

        // 4. Refresh halaman agar parameter delete_id hilang dari URL
        header("Location: dashboard.php");
        exit;
    } else {
        // Opsional: Handle jika produk tidak ditemukan atau akses ditolak
    }
}
// --- AKHIR LOGIKA HAPUS ---

require_once '../layout/header.php';

// Ambil data produk untuk ditampilkan
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$products = $stmt->fetchAll();
?>

<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Karyaku</h1>
        <a href="product_form.php" class="bg-primary text-white px-4 py-2 rounded">+ Upload Karya</a>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="py-2 px-4 border-b">Foto Utama</th>
                    <th class="py-2 px-4 border-b">Judul</th>
                    <th class="py-2 px-4 border-b">Harga</th>
                    <th class="py-2 px-4 border-b">Status</th>
                    <th class="py-2 px-4 border-b">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($products as $p): ?>
                <tr>
                    <td class="py-2 px-4 border-b">
                        <img src="../uploads/<?= htmlspecialchars($p['image']) ?>" class="w-16 h-16 object-cover rounded">
                    </td>
                    <td class="py-2 px-4 border-b"><?= htmlspecialchars($p['title']) ?></td>
                    <td class="py-2 px-4 border-b">Rp <?= number_format($p['price']) ?></td>
                    <td class="py-2 px-4 border-b">
                        <?php 
                        $color = $p['status'] == 'active' ? 'green' : ($p['status'] == 'rejected' ? 'red' : 'yellow');
                        echo "<span class='bg-{$color}-100 text-{$color}-800 px-2 py-1 rounded text-xs uppercase font-bold'>{$p['status']}</span>";
                        if($p['status'] == 'rejected') echo "<br><span class='text-xs text-red-500'>Note: {$p['reject_reason']}</span>";
                        ?>
                    </td>
                    <td class="py-2 px-4 border-b">
                        <a href="product_form.php?id=<?= $p['id'] ?>" class="text-blue-600 hover:underline">Edit</a> |
                        
                        <a href="dashboard.php?delete_id=<?= $p['id'] ?>" 
                           onclick="return confirm('Yakin ingin menghapus karya ini? Semua gambar terkait akan dihapus permanen.')" 
                           class="text-red-600 hover:underline">
                           Hapus
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(count($products) == 0): ?>
                <tr>
                    <td colspan="5" class="py-4 text-center text-gray-500">Belum ada karya yang diupload.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>