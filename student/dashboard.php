<?php
require_once '../config/database.php';
require_once '../config/functions.php';
checkRole(['student']);

// --- LOGIKA HAPUS (Ditaruh sebelum load header) ---
if (isset($_GET['delete_id'])) {
    $id_produk = $_GET['delete_id'];
    $user_id = $_SESSION['user_id']; 

    // 1. Ambil nama file gambar untuk dihapus
    $stmt = $pdo->prepare("SELECT image, image1, image2 FROM products WHERE id = ? AND user_id = ?");
    $stmt->execute([$id_produk, $user_id]);
    $product = $stmt->fetch();

    if ($product) {
        $imageFields = ['image', 'image1', 'image2'];
        foreach ($imageFields as $field) {
            $filename = $product[$field];
            if (!empty($filename)) {
                $path_file = "../uploads/" . $filename;
                if (file_exists($path_file)) unlink($path_file);
            }
        }

        // 2. Hapus data dari database
        $stmt_del = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt_del->execute([$id_produk]);

        header("Location: dashboard.php");
        exit;
    }
}

require_once '../layout/header.php';

// Ambil data produk
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$products = $stmt->fetchAll();

// --- HITUNG STATISTIK ---
$total_karya = count($products);
$total_views = 0;
$total_likes = 0;
$count_active = 0;
$count_pending = 0;
$count_rejected = 0;

foreach ($products as $p) {
    $total_views += isset($p['views']) ? $p['views'] : 0;
    $total_likes += isset($p['likes']) ? $p['likes'] : 0;
    
    if ($p['status'] == 'active') $count_active++;
    elseif ($p['status'] == 'pending') $count_pending++;
    elseif ($p['status'] == 'rejected') $count_rejected++;
}
?>

<div class="max-w-7xl mx-auto px-4 py-8 min-h-screen">
    
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">Dashboard Kreator</h1>
            <p class="text-gray-500 mt-1">Kelola karyamu dan pantau performanya di sini.</p>
        </div>
        <a href="product_form.php" class="bg-primary hover:bg-teal-700 text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-teal-500/30 transition transform hover:-translate-y-1 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Upload Karya Baru
        </a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center text-center hover:shadow-md transition">
            <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mb-2">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
            </div>
            <span class="text-3xl font-bold text-gray-800"><?= $total_karya ?></span>
            <span class="text-xs text-gray-500 font-medium uppercase tracking-wide">Total Karya</span>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center text-center hover:shadow-md transition">
            <div class="w-10 h-10 rounded-full bg-orange-50 text-orange-500 flex items-center justify-center mb-2">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
            </div>
            <span class="text-3xl font-bold text-gray-800"><?= number_format($total_views) ?></span>
            <span class="text-xs text-gray-500 font-medium uppercase tracking-wide">Total Dilihat</span>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center text-center hover:shadow-md transition">
            <div class="w-10 h-10 rounded-full bg-green-50 text-green-600 flex items-center justify-center mb-2">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <span class="text-3xl font-bold text-gray-800"><?= $count_active ?></span>
            <span class="text-xs text-gray-500 font-medium uppercase tracking-wide">Disetujui</span>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex flex-col items-center text-center hover:shadow-md transition">
            <div class="w-10 h-10 rounded-full bg-yellow-50 text-yellow-600 flex items-center justify-center mb-2">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <span class="text-3xl font-bold text-gray-800"><?= $count_pending ?></span>
            <span class="text-xs text-gray-500 font-medium uppercase tracking-wide">Menunggu</span>
        </div>
    </div>

    <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
        <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
        Daftar Karyaku
    </h2>

    <?php if (count($products) == 0): ?>
        <div class="bg-white rounded-2xl p-12 text-center border-2 border-dashed border-gray-200">
            <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4 text-gray-400">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900">Belum ada karya</h3>
            <p class="text-gray-500 mb-6">Ayo mulai upload karyamu sekarang dan tunjukkan pada dunia!</p>
            <a href="product_form.php" class="text-primary font-bold hover:underline">Upload Sekarang &rarr;</a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach($products as $p): ?>
                <div class="item-product bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300 group flex flex-col h-full"
                     data-id="<?= $p['id'] ?>" 
                     data-status="<?= $p['status'] ?>">
                    
                    <div class="relative w-full h-48 bg-gray-100 overflow-hidden">
                        <img src="../uploads/<?= htmlspecialchars($p['image']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        
                        <div class="absolute top-3 right-3">
                            <?php if($p['status'] == 'active'): ?>
                                <span class="bg-green-500 text-white text-[10px] font-bold px-2 py-1 rounded-lg shadow-sm flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg> Approved
                                </span>
                            <?php elseif($p['status'] == 'rejected'): ?>
                                <span class="bg-red-500 text-white text-[10px] font-bold px-2 py-1 rounded-lg shadow-sm flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg> Rejected
                                </span>
                            <?php else: ?>
                                <span class="bg-yellow-400 text-yellow-900 text-[10px] font-bold px-2 py-1 rounded-lg shadow-sm flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg> Pending
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="p-4 flex flex-col flex-1">
                        <div class="mb-2">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider"><?= $p['category'] ?></span>
                            <h3 class="font-bold text-gray-800 text-lg leading-tight truncate"><?= htmlspecialchars($p['title']) ?></h3>
                        </div>
                        
                        <div class="font-bold text-primary text-lg mb-4">Rp <?= number_format($p['price']) ?></div>

                        <?php if($p['status'] == 'rejected' && !empty($p['reject_reason'])): ?>
                            <div class="bg-red-50 text-red-600 text-xs p-2 rounded-lg mb-4 border border-red-100">
                                <strong>Info:</strong> <?= htmlspecialchars($p['reject_reason']) ?>
                            </div>
                        <?php endif; ?>

                        <div class="flex items-center gap-4 text-xs text-gray-400 border-t border-gray-50 pt-3 mt-auto mb-4">
                            <span class="flex items-center gap-1"><svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg> <?= isset($p['views']) ? $p['views'] : 0 ?></span>
                            <span class="flex items-center gap-1"><svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg> <?= isset($p['likes']) ? $p['likes'] : 0 ?></span>
                            <span class="ml-auto"><?= date('d M Y', strtotime($p['created_at'])) ?></span>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <a href="product_form.php?id=<?= $p['id'] ?>" class="flex items-center justify-center gap-1 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-blue-600 hover:border-blue-200 transition text-sm font-medium">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                Edit
                            </a>
                            <a href="dashboard.php?delete_id=<?= $p['id'] ?>" onclick="return confirm('Yakin ingin menghapus karya ini secara permanen?')" class="flex items-center justify-center gap-1 py-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition text-sm font-medium">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                Hapus
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Ambil semua elemen produk di dashboard
    const productElements = document.querySelectorAll('.item-product');
    
    // Jika tidak ada produk, tidak perlu polling
    if (productElements.length === 0) return;

    const products = [];
    productElements.forEach(el => {
        products.push({
            id: el.getAttribute('data-id'),
            currentStatus: el.getAttribute('data-status')
        });
    });

    // 2. Fungsi Cek Status
    function checkStatus() {
        // Hanya ambil ID-nya saja untuk dikirim ke server
        const productIds = products.map(p => p.id);

        fetch('check_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ products: productIds })
        })
        .then(response => response.json())
        .then(data => {
            let needsReload = false;

            // Loop data terbaru dari server
            data.forEach(serverProduct => {
                // Cari produk lokal yang cocok
                const localProduct = products.find(p => p.id == serverProduct.id);
                
                if (localProduct) {
                    // Jika status di server beda dengan status di layar (browser)
                    // Misal: Layar 'pending', Server 'active' -> Reload!
                    if (serverProduct.status !== localProduct.currentStatus) {
                        needsReload = true;
                    }
                }
            });

            if (needsReload) {
                console.log("Perubahan status terdeteksi! Reloading...");
                window.location.reload();
            }
        })
        .catch(err => console.error("Gagal cek status:", err));
    }

    // 3. Jalankan pengecekan setiap 5 detik
    setInterval(checkStatus, 5000);
});
</script>

<?php require_once '../layout/footer.php'; ?>