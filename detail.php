<?php
require_once 'config/database.php';
require_once 'config/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 1. AMBIL DATA PRODUK
$stmt = $pdo->prepare("SELECT p.*, u.name as student_name, u.phone, s.name as school_name 
                        FROM products p 
                        JOIN users u ON p.user_id = u.id 
                        JOIN schools s ON p.school_id = s.id 
                        WHERE p.id = ? AND p.status = 'active'");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    die("Produk tidak ditemukan atau sedang ditinjau.");
}

// 2. LOGIC KIRIM LAPORAN (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_report'])) {
    $reason = sanitize($_POST['reason']);
    if ($reason) {
        $stmt_rep = $pdo->prepare("INSERT INTO reports (product_id, reason) VALUES (?, ?)");
        $stmt_rep->execute([$id, $reason]);
        
        echo "<script>
                alert('Laporan berhasil dikirim! Terima kasih atas bantuannya.');
                window.location = 'detail.php?id=$id';
              </script>";
        exit;
    }
}

// Ambil daftar kategori untuk label
$categories = getCategories();

require_once 'layout/header.php';
?>

<div class="max-w-7xl mx-auto px-4 py-8">
    
    <a href="index.php" class="flex items-center text-gray-500 hover:text-primary mb-6 transition-colors group">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Kembali ke Katalog
    </a>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
        <div class="md:flex">
            
            <div class="md:w-1/2 bg-gray-100 relative group">
                <img src="uploads/<?= $product['image'] ?>" alt="<?= $product['title'] ?>" class="w-full h-96 md:h-full object-cover">
                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors duration-300"></div>
            </div>

            <div class="p-8 md:w-1/2 flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h1 class="text-3xl font-extrabold text-gray-800 mb-2 leading-tight"><?= $product['title'] ?></h1>
                            <p class="text-primary text-3xl font-bold">Rp <?= number_format($product['price'], 0, ',', '.') ?></p>
                            
                            <div class="mt-3">
                                <span class="inline-flex items-center gap-1.5 bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-sm font-medium border border-gray-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                    <?= isset($product['category']) && isset($categories[$product['category']]) ? $categories[$product['category']] : 'Umum' ?>
                                </span>
                            </div>

                        </div>
                        <span class="bg-teal-50 text-teal-700 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider border border-teal-200 shadow-sm">
                            <?= $product['school_name'] ?>
                        </span>
                    </div>

                    <div class="h-px bg-gray-100 my-6"></div>

                    <div class="prose prose-sm text-gray-600 mb-8">
                        <p class="leading-relaxed whitespace-pre-line"><?= $product['description'] ?></p>
                    </div>

                    <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg border border-gray-100">
                        <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xl">
                            <?= substr($product['student_name'], 0, 1) ?>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-semibold">Kreator</p>
                            <p class="font-bold text-gray-800 text-lg"><?= $product['student_name'] ?></p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-3 mt-8">
                    <?php 
                        $waText = "Halo, saya tertarik dengan karya *{$product['title']}* di SiswaNiaga. Apakah masih tersedia?"; 
                        $waLink = "https://wa.me/{$product['phone']}?text=" . urlencode($waText);
                    ?>
                    <a href="<?= $waLink ?>" target="_blank" class="flex items-center justify-center gap-2 w-full bg-green-500 text-white font-bold py-4 rounded-xl hover:bg-green-600 transition shadow-lg hover:shadow-green-200 transform hover:-translate-y-0.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0012.04 2zM12.05 20.21c-1.5 0-2.96-.39-4.27-1.15l-.3-.18-3.11.82.83-3.04-.2-.31a8.154 8.154 0 01-1.26-4.44c0-4.52 3.67-8.19 8.19-8.19 2.19 0 4.24.85 5.78 2.39 1.54 1.54 2.39 3.59 2.39 5.78 0 4.51-3.68 8.18-8.19 8.18zm4.52-6.13c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.12-.17.25-.64.81-.78.97-.14.17-.29.19-.54.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.02-.38.11-.51.11-.11.25-.29.37-.43.12-.14.16-.23.24-.39.08-.17.04-.31-.02-.43-.06-.12-.56-1.34-.76-1.84-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.43.06-.66.31-.22.25-.87.85-.87 2.07 0 1.22.89 2.39 1.01 2.56.12.17 1.75 2.67 4.23 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.67-1.18.21-.58.21-1.07.14-1.18-.07-.11-.24-.18-.49-.3z"/>
                        </svg>
                        <span>Beli Sekarang via WhatsApp</span>
                    </a>
                    
                    <button onclick="toggleModal()" type="button" class="group flex items-center justify-center gap-2 w-full text-center text-gray-400 hover:text-red-500 text-sm py-2 transition font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 group-hover:text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        Laporkan konten ini
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="reportModal" class="fixed inset-0 bg-black/60 z-50 hidden items-center justify-center p-4 backdrop-blur-sm transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 relative animate-fade-in transform scale-100">
        
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-red-600 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-8a2 2 0 01-2-8 4 4 0 016.5-2.6A4 4 0 0113 5c.74 0 1.45.18 2.08.51a4 4 0 003.56.09L19 5a2 2 0 012 2v6a2 2 0 01-2 2h-1a2 2 0 00-1.78.9A4 4 0 0113 18a4 4 0 00-3.24-1.35L9 17a2 2 0 01-2-2" />
                </svg>
                Laporkan Pelanggaran
            </h2>
            <button onclick="toggleModal()" class="text-gray-400 hover:text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-full p-1 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <p class="text-sm text-gray-600 mb-6 bg-red-50 p-3 rounded-lg border border-red-100">
            Karya ini akan ditinjau oleh pihak sekolah. Harap berikan alasan yang jelas mengapa karya ini melanggar aturan.
        </p>

        <form method="POST">
            <label class="block text-sm font-medium text-gray-700 mb-2">Alasan Pelaporan</label>
            <textarea name="reason" rows="4" class="w-full border border-gray-300 rounded-xl p-3 focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-200 transition resize-none" placeholder="Contoh: Mengandung unsur SARA, Penipuan, Pornografi..." required></textarea>
            
            <div class="mt-6 flex gap-3">
                <button type="button" onclick="toggleModal()" class="flex-1 bg-white border border-gray-300 text-gray-700 py-3 rounded-xl font-semibold hover:bg-gray-50 transition">Batal</button>
                <button type="submit" name="submit_report" class="flex-1 bg-red-600 text-white py-3 rounded-xl font-bold hover:bg-red-700 shadow-lg hover:shadow-red-200 transition">Kirim Laporan</button>
            </div>
        </form>

    </div>
</div>

<script>
// Fungsi Toggle Modal
function toggleModal() {
    const modal = document.getElementById('reportModal');
    if (modal.classList.contains('hidden')) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    } else {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }
}

// Logic Auto-Open Modal jika ada parameter trigger_report=true dari halaman index
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('trigger_report') === 'true') {
        toggleModal();
        
        // Membersihkan URL agar jika direfresh modal tidak muncul lagi
        const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + "?id=" + urlParams.get('id');
        window.history.replaceState({path: newUrl}, '', newUrl);
    }
});
</script>

<style>
    @keyframes fade-in {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
    .animate-fade-in {
        animation: fade-in 0.2s ease-out forwards;
    }
</style>
<?php require_once 'layout/footer.php'; ?>
</body>
</html>