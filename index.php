<?php 
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'layout/header.php'; 

// --- 1. CONFIG PAGINATION & FILTER ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12; // Tampilkan 12 produk per halaman
$offset = ($page - 1) * $limit;

$search = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$school_filter = isset($_GET['school']) ? sanitize($_GET['school']) : '';
$cat_filter = isset($_GET['category']) ? sanitize($_GET['category']) : '';

// --- 2. BANGUN QUERY 'WHERE' (Dipakai untuk Hitung Total & Ambil Data) ---
$whereClause = "WHERE p.status = 'active'";

if($search) {
    // Pakai kurung (...) agar logika OR tidak merusak filter status
    $whereClause .= " AND (p.title LIKE '%$search%' OR p.description LIKE '%$search%')";
}
if($school_filter) {
    $whereClause .= " AND p.school_id = '$school_filter'";
}
if($cat_filter) {
    $whereClause .= " AND p.category = '$cat_filter'";
}

// --- 3. QUERY 1: HITUNG TOTAL DATA (Untuk Pagination) ---
// Kita perlu tahu ada berapa total produk yg cocok untuk menentukan jumlah halaman
$sqlCount = "SELECT COUNT(*) as total 
             FROM products p 
             JOIN users u ON p.user_id = u.id 
             JOIN schools s ON p.school_id = s.id 
             $whereClause";
$stmtCount = $pdo->query($sqlCount);
$total_rows = $stmtCount->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// --- 4. QUERY 2: AMBIL DATA PRODUK (Dengan LIMIT & OFFSET) ---
$sql = "SELECT p.*, u.name as student_name, u.phone, s.name as school_name 
        FROM products p 
        JOIN users u ON p.user_id = u.id 
        JOIN schools s ON p.school_id = s.id 
        $whereClause 
        ORDER BY p.created_at DESC 
        LIMIT $limit OFFSET $offset";

$stmt = $pdo->query($sql);
$products = $stmt->fetchAll();

// Ambil data pendukung (Sekolah & Kategori untuk filter)
$schools = $pdo->query("SELECT * FROM schools ORDER BY name ASC")->fetchAll();
$categories = getCategories(); 

// Helper: Fungsi membuat URL agar filter tidak hilang saat pindah halaman
function buildUrl($newPage) {
    $params = $_GET;
    $params['page'] = $newPage;
    return '?' . http_build_query($params);
}
?>

<div class="max-w-7xl mx-auto px-4 py-8 min-h-screen">
    
    <div class="text-center mb-10">
        <h1 class="text-4xl font-extrabold text-gray-800 tracking-tight mb-2">
            Katalog Karya <span class="text-primary">Siswa</span>
        </h1>
        <p class="text-gray-500 max-w-2xl mx-auto">
            Dukung kreativitas generasi muda. Temukan produk unik dan inovatif langsung dari tangan siswa sekolah di sekitar Anda.
        </p>
    </div>

    <form class="flex flex-col md:flex-row gap-4 mb-6 max-w-5xl mx-auto">
        <div class="flex-1 relative">
            <span class="absolute left-3 top-3 text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </span>
            <input type="text" name="q" placeholder="Cari judul atau deskripsi..." value="<?= $search ?>" 
                   class="w-full pl-10 p-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
        </div>
        
        <div class="w-full md:w-1/4">
            <select name="category" class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary bg-white transition cursor-pointer">
                <option value="">-- Semua Kategori --</option>
                <?php foreach($categories as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $cat_filter == $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="w-full md:w-1/4">
            <select name="school" class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary bg-white transition cursor-pointer">
                <option value="">-- Semua Sekolah --</option>
                <?php foreach($schools as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $school_filter == $s['id'] ? 'selected' : '' ?>><?= $s['name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button type="submit" class="bg-primary text-white px-8 py-3 rounded-lg font-bold hover:bg-teal-700 shadow-md transition-transform transform hover:scale-105">
            Cari
        </button>
    </form>

    <div class="text-center text-sm text-gray-500 mb-8">
        Menampilkan <b><?= count($products) ?></b> dari total <b><?= $total_rows ?></b> karya.
    </div>

    <?php if (count($products) > 0): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 pb-12">
            <?php foreach($products as $p): ?>
                
                <div class="group bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 overflow-hidden flex flex-col relative">
                    
                    <a href="detail.php?id=<?= $p['id'] ?>&trigger_report=true" 
                       class="absolute top-3 right-3 z-10 bg-white/90 backdrop-blur-sm p-2 rounded-full shadow-sm text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors duration-200"
                       title="Laporkan Karya Ini">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-8a2 2 0 01-2-8 4 4 0 016.5-2.6A4 4 0 0113 5c.74 0 1.45.18 2.08.51a4 4 0 003.56.09L19 5a2 2 0 012 2v6a2 2 0 01-2 2h-1a2 2 0 00-1.78.9A4 4 0 0113 18a4 4 0 00-3.24-1.35L9 17a2 2 0 01-2-2" />
                        </svg>
                    </a>

                    <a href="detail.php?id=<?= $p['id'] ?>" class="relative h-56 overflow-hidden block bg-gray-100">
                        <img src="uploads/<?= $p['image'] ?>" alt="<?= $p['title'] ?>" 
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        
                        <span class="absolute bottom-2 left-2 bg-black/60 text-white text-[10px] uppercase font-bold px-2 py-1 rounded backdrop-blur-md tracking-wide">
                            <?= $p['school_name'] ?>
                        </span>
                    </a>

                    <div class="p-4 flex-1 flex flex-col">
                        <div class="mb-3">
                            <p class="text-[10px] text-primary uppercase font-bold tracking-wider mb-1">
                                <?= isset($p['category']) && $p['category'] ? $p['category'] : 'Umum' ?>
                            </p>

                            <a href="detail.php?id=<?= $p['id'] ?>" class="hover:text-primary transition-colors">
                                <h3 class="font-bold text-gray-800 line-clamp-1 text-lg leading-tight" title="<?= $p['title'] ?>"><?= $p['title'] ?></h3>
                            </a>
                            <div class="flex items-center gap-1 mt-1">
                                <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
                                <p class="text-xs text-gray-500 truncate"><?= $p['student_name'] ?></p>
                            </div>
                        </div>
                        
                        <div class="mt-auto flex justify-between items-center mb-4">
                            <p class="text-primary font-extrabold text-xl">Rp <?= number_format($p['price'], 0, ',', '.') ?></p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 pt-4 border-t border-gray-100">
                            <a href="detail.php?id=<?= $p['id'] ?>" class="flex items-center justify-center gap-2 bg-gray-50 text-gray-600 py-2.5 rounded-lg hover:bg-gray-100 hover:text-primary transition-colors text-sm font-semibold group/btn">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover/btn:text-primary transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                Detail
                            </a>
                            <?php 
                            $waText = "Halo, saya tertarik dengan karya *{$p['title']}* yang ada di SiswaNiaga."; 
                            $waLink = "https://wa.me/{$p['phone']}?text=" . urlencode($waText);
                            ?>
                            <a href="<?= $waLink ?>" target="_blank" class="flex items-center justify-center gap-2 bg-green-500 text-white py-2.5 rounded-lg hover:bg-green-600 transition-colors text-sm font-semibold shadow-sm hover:shadow-md">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0012.04 2zM12.05 20.21c-1.5 0-2.96-.39-4.27-1.15l-.3-.18-3.11.82.83-3.04-.2-.31a8.154 8.154 0 01-1.26-4.44c0-4.52 3.67-8.19 8.19-8.19 2.19 0 4.24.85 5.78 2.39 1.54 1.54 2.39 3.59 2.39 5.78 0 4.51-3.68 8.18-8.19 8.18zm4.52-6.13c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.12-.17.25-.64.81-.78.97-.14.17-.29.19-.54.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.02-.38.11-.51.11-.11.25-.29.37-.43.12-.14.16-.23.24-.39.08-.17.04-.31-.02-.43-.06-.12-.56-1.34-.76-1.84-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.43.06-.66.31-.22.25-.87.85-.87 2.07 0 1.22.89 2.39 1.01 2.56.12.17 1.75 2.67 4.23 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.67-1.18.21-.58.21-1.07.14-1.18-.07-.11-.24-.18-.49-.3z"/></svg>
                                Beli
                            </a>
                        </div>
                    </div>
                </div>
                
            <?php endforeach; ?>
        </div>

        <?php if($total_pages > 1): ?>
        <div class="flex justify-center mt-12">
            <nav class="flex items-center gap-2">
                
                <?php if($page > 1): ?>
                    <a href="<?= buildUrl($page - 1) ?>" class="w-10 h-10 flex items-center justify-center rounded-full border border-gray-300 text-gray-500 hover:bg-gray-50 hover:text-primary transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                <?php else: ?>
                    <span class="w-10 h-10 flex items-center justify-center rounded-full border border-gray-200 text-gray-300 cursor-not-allowed">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </span>
                <?php endif; ?>

                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if($i == $page): ?>
                        <span class="w-10 h-10 flex items-center justify-center rounded-full bg-primary text-white border border-primary font-bold shadow-md">
                            <?= $i ?>
                        </span>
                    <?php else: ?>
                        <a href="<?= buildUrl($i) ?>" class="w-10 h-10 flex items-center justify-center rounded-full border border-gray-300 text-gray-600 hover:bg-gray-50 hover:text-primary transition-all">
                            <?= $i ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if($page < $total_pages): ?>
                    <a href="<?= buildUrl($page + 1) ?>" class="w-10 h-10 flex items-center justify-center rounded-full border border-gray-300 text-gray-500 hover:bg-gray-50 hover:text-primary transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                <?php else: ?>
                    <span class="w-10 h-10 flex items-center justify-center rounded-full border border-gray-200 text-gray-300 cursor-not-allowed">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </span>
                <?php endif; ?>

            </nav>
        </div>
        <?php endif; ?>
    
    <?php else: ?>
        
        <div class="text-center py-20 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200">
            <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900">Belum ada karya ditemukan</h3>
            <p class="mt-1 text-sm text-gray-500">Coba kata kunci lain atau reset filter.</p>
            <div class="mt-6">
                <a href="index.php" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-teal-700 focus:outline-none">
                    Reset Pencarian
                </a>
            </div>
        </div>

    <?php endif; ?>

</div>

</body>
</html>