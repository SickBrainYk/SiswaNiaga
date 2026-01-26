<?php 
require_once 'config/database.php';
require_once 'config/functions.php';
require_once 'layout/header.php'; 

// --- 1. CONFIG PAGINATION & FILTER ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12; 
$offset = ($page - 1) * $limit;

$search = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$school_filter = isset($_GET['school']) ? sanitize($_GET['school']) : '';
$cat_filter = isset($_GET['category']) ? sanitize($_GET['category']) : '';

// --- 2. FILTER QUERY ---
$whereClause = "WHERE p.status = 'active'";
if($search) $whereClause .= " AND (p.title LIKE '%$search%' OR p.description LIKE '%$search%')";
if($school_filter) $whereClause .= " AND p.school_id = '$school_filter'";
if($cat_filter) $whereClause .= " AND p.category = '$cat_filter'";

// --- 3. HITUNG TOTAL DATA ---
$sqlCount = "SELECT COUNT(*) as total FROM products p JOIN users u ON p.user_id = u.id JOIN schools s ON p.school_id = s.id $whereClause";
$stmtCount = $pdo->query($sqlCount);
$total_rows = $stmtCount->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// --- 4. AMBIL DATA PRODUK (DENGAN TOTAL LIKES) ---
$sql = "SELECT p.*, 
               u.name as student_name, 
               u.phone, 
               s.name as school_name,
               (SELECT COUNT(*) FROM product_likes WHERE product_id = p.id) as total_likes 
        FROM products p 
        JOIN users u ON p.user_id = u.id 
        JOIN schools s ON p.school_id = s.id 
        $whereClause 
        ORDER BY p.created_at DESC 
        LIMIT $limit OFFSET $offset";

$stmt = $pdo->query($sql);
$products = $stmt->fetchAll();

$schools = $pdo->query("SELECT * FROM schools ORDER BY name ASC")->fetchAll();
$categories = getCategories(); 

function buildUrl($newPage) {
    $params = $_GET;
    $params['page'] = $newPage;
    return '?' . http_build_query($params);
}
?>

<div class="max-w-7xl mx-auto px-4 py-8 min-h-screen">
    
    <div class="mb-12 relative group">
        <div id="bannerSlider" class="flex overflow-x-auto snap-x snap-mandatory scroll-smooth scrollbar-hide rounded-3xl shadow-xl">
            <div class="snap-center flex-shrink-0 w-full bg-[#1e293b] text-white relative overflow-hidden h-[250px] md:h-[350px] rounded-3xl">
                <div class="absolute -right-20 -top-40 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
                <div class="relative z-10 h-full flex flex-col justify-center px-10 md:px-24">
                    <p class="text-gray-300 font-medium mb-2 tracking-wide uppercase text-sm">Penawaran Spesial</p>
                    <h2 class="text-4xl md:text-6xl font-extrabold mb-4 tracking-tight">SMART <span class="text-primary">WEARABLE.</span></h2>
                    <p class="text-xl md:text-2xl font-bold mb-8">DISKON HINGGA 80%</p>
                </div>
            </div>
            <div class="snap-center flex-shrink-0 w-full bg-gradient-to-r from-primary to-teal-900 text-white relative overflow-hidden h-[250px] md:h-[350px] rounded-3xl">
                <div class="relative z-10 h-full flex flex-col justify-center px-10 md:px-24">
                    <p class="text-teal-100 font-medium mb-2 tracking-wide uppercase text-sm">Karya Siswa Terbaik</p>
                    <h2 class="text-4xl md:text-6xl font-extrabold mb-4 tracking-tight">PEKAN <span class="text-secondary">KREATIF.</span></h2>
                    <a href="#katalog" class="inline-block bg-white text-primary px-6 py-3 rounded-full font-bold hover:bg-gray-100 transition shadow-lg w-fit">Lihat Katalog</a>
                </div>
            </div>
        </div>
        <button onclick="scrollBanner('left')" class="absolute left-0 top-1/2 -translate-y-1/2 -ml-3 bg-white text-primary p-3 rounded-full shadow-lg hover:scale-110 transition z-20 hidden md:flex border border-gray-100"><svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg></button>
        <button onclick="scrollBanner('right')" class="absolute right-0 top-1/2 -translate-y-1/2 -mr-3 bg-white text-primary p-3 rounded-full shadow-lg hover:scale-110 transition z-20 hidden md:flex border border-gray-100"><svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg></button>
    </div>
    <script>
        function scrollBanner(d) { const c = document.getElementById('bannerSlider'); c.scrollLeft += d === 'left' ? -c.offsetWidth : c.offsetWidth; }
        setInterval(() => { const c = document.getElementById('bannerSlider'); c.scrollLeft = (c.scrollLeft + c.offsetWidth >= c.scrollWidth) ? 0 : c.scrollLeft + c.offsetWidth; }, 5000);
    </script>
    <style>.scrollbar-hide::-webkit-scrollbar { display: none; } .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }</style>


    <div class="mb-14">
        <div class="flex justify-between items-end mb-6 border-b border-gray-200 pb-2">
            <h2 class="text-xl md:text-2xl font-bold text-gray-700">
                Belanja Berdasarkan <span class="text-primary border-b-4 border-primary pb-1">Kategori</span>
            </h2>
            
            <?php if($search || $school_filter || $cat_filter): ?>
            <a href="index.php" class="text-sm font-bold text-red-500 hover:text-red-700 flex items-center gap-1 transition-colors bg-red-50 px-3 py-1.5 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                Reset Filter
            </a>
            <?php else: ?>
            <a href="index.php" class="text-sm font-semibold text-primary hover:text-teal-800 flex items-center gap-1 transition-colors">Lihat Semua <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg></a>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-4">
            <?php foreach($categories as $key => $label): ?>
                <?php 
                    // --- LOGIKA PENCARIAN GAMBAR DARI FOLDER ASSETS ---
                    $imgSrc = null; 

                    // 1. Cek KHUSUS untuk Kuliner
                    if ($key == 'Kuliner') {
                        if (file_exists("assets/icons/makanan.jpg")) $imgSrc = "assets/icons/makanan.jpg";
                        elseif (file_exists("assets/icons/makanan.png")) $imgSrc = "assets/icons/makanan.png";
                    }

                    // 2. Cek nama kategori standard (hapus spasi)
                    if (!$imgSrc) {
                        $cleanKey = str_replace(' ', '', $key); 
                        if (file_exists("assets/icons/{$cleanKey}.jpg")) $imgSrc = "assets/icons/{$cleanKey}.jpg";
                        elseif (file_exists("assets/icons/{$cleanKey}.png")) $imgSrc = "assets/icons/{$cleanKey}.png";
                        elseif (file_exists("assets/icons/{$key}.jpg")) $imgSrc = "assets/icons/{$key}.jpg"; 
                    }

                    // 3. Fallback (Placeholder)
                    if (!$imgSrc) {
                        $imgSrc = "https://via.placeholder.com/200/e5e7eb/0f766e?text=" . substr($key,0,1);
                    }
                ?>

                <a href="?category=<?= $key ?>" class="group flex flex-col items-center text-center">
                    <div class="w-full aspect-square bg-gray-200 rounded-2xl mb-3 overflow-hidden relative shadow-sm group-hover:shadow-md group-hover:-translate-y-1 transition-all duration-300 border border-gray-100">
                        <img src="<?= $imgSrc ?>" 
                             alt="<?= $label ?>" 
                             class="w-full h-full object-cover group-hover:scale-110 transition-all duration-500"> 
                    </div>
                    <span class="text-sm font-semibold text-gray-600 group-hover:text-primary transition-colors"><?= $label ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>


    <div class="text-center mb-10" id="katalog">
        <h1 class="text-4xl font-extrabold text-gray-800 tracking-tight mb-2">Katalog Karya <span class="text-primary">Siswa</span></h1>
        <p class="text-gray-500 max-w-2xl mx-auto">Dukung kreativitas generasi muda. Temukan produk unik dan inovatif langsung dari tangan siswa sekolah di sekitar Anda.</p>
    </div>

    <form class="flex flex-col md:flex-row gap-4 mb-6 max-w-5xl mx-auto">
        <div class="flex-1 relative">
            <span class="absolute left-3 top-3 text-gray-400"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg></span>
            <input type="text" name="q" placeholder="Cari judul atau deskripsi..." value="<?= $search ?>" class="w-full pl-10 p-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition">
        </div>
        <div class="w-full md:w-1/4">
            <select name="category" class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary bg-white transition cursor-pointer">
                <option value="">-- Filter Kategori --</option>
                <?php foreach($categories as $key => $label): ?><option value="<?= $key ?>" <?= $cat_filter == $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="w-full md:w-1/4">
            <select name="school" class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary bg-white transition cursor-pointer">
                <option value="">-- Filter Sekolah --</option>
                <?php foreach($schools as $s): ?><option value="<?= $s['id'] ?>" <?= $school_filter == $s['id'] ? 'selected' : '' ?>><?= $s['name'] ?></option><?php endforeach; ?>
            </select>
        </div>
        
        <div class="flex gap-2 w-full md:w-auto">
            <button type="submit" class="bg-primary text-white px-8 py-3 rounded-lg font-bold hover:bg-teal-700 shadow-md transition-transform transform hover:scale-105 flex-1 md:flex-none">Cari</button>
            
            <a href="index.php" class="bg-gray-100 text-gray-600 px-4 py-3 rounded-lg font-bold hover:bg-gray-200 transition border border-gray-200 flex items-center justify-center" title="Reset Pencarian & Filter">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </a>
        </div>
    </form>

    <div class="text-center text-sm text-gray-500 mb-8">Menampilkan <b><?= count($products) ?></b> dari total <b><?= $total_rows ?></b> karya.</div>

    <?php if (count($products) > 0): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-x-6 gap-y-10 pb-12">
            <?php foreach($products as $p): ?>
                
                <div class="group flex flex-col">
                    <div class="relative w-full aspect-[3/4] bg-gray-100 rounded-3xl overflow-hidden mb-4 border border-gray-100 shadow-sm">
                        <a href="detail.php?id=<?= $p['id'] ?>" class="block w-full h-full">
                            <img src="uploads/<?= $p['image'] ?>" alt="<?= $p['title'] ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </a>
                        <span class="absolute bottom-3 left-3 bg-white/95 backdrop-blur-sm text-gray-800 text-[10px] font-bold px-3 py-1.5 rounded-full shadow-md z-10 truncate max-w-[80%]"><?= $p['school_name'] ?></span>
                        
                        <?php if($p['total_likes'] > 0): ?>
                        <div class="absolute top-3 left-3 bg-white/90 backdrop-blur-md px-2 py-1 rounded-full flex items-center gap-1 shadow-sm z-20">
                            <svg class="w-3 h-3 text-red-500 fill-current" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                            <span class="text-[10px] font-bold text-gray-700"><?= $p['total_likes'] ?></span>
                        </div>
                        <?php endif; ?>

                        <a href="detail.php?id=<?= $p['id'] ?>&trigger_report=true" class="absolute top-3 right-3 bg-white/60 hover:bg-white p-2 rounded-full text-gray-500 hover:text-red-600 transition-all backdrop-blur-sm z-20 shadow-sm" title="Laporkan"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-8a2 2 0 01-2-8 4 4 0 016.5-2.6A4 4 0 0113 5c.74 0 1.45.18 2.08.51a4 4 0 003.56.09L19 5a2 2 0 012 2v6a2 2 0 01-2 2h-1a2 2 0 00-1.78.9A4 4 0 0113 18a4 4 0 00-3.24-1.35L9 17a2 2 0 01-2-2" /></svg></a>
                    </div>

                    <div class="flex flex-col px-1">
                        <a href="detail.php?id=<?= $p['id'] ?>" class="group-hover:text-primary transition-colors"><h3 class="font-bold text-gray-900 text-lg leading-tight truncate mb-1" title="<?= $p['title'] ?>"><?= $p['title'] ?></h3></a>
                        <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                            <span class="bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded font-medium"><?= isset($p['category']) && $p['category'] ? $p['category'] : 'Umum' ?></span>
                            <span class="text-xs truncate max-w-[150px]">• <?= $p['student_name'] ?></span>
                        </div>
                        <div class="font-extrabold text-gray-900 text-xl mb-4">Rp <?= number_format($p['price'], 0, ',', '.') ?></div>
                        
                        <div class="grid grid-cols-2 gap-3 mt-auto">
                            <a href="detail.php?id=<?= $p['id'] ?>" class="flex items-center justify-center gap-2 border border-gray-300 text-gray-700 py-2.5 rounded-xl hover:bg-gray-50 hover:border-gray-400 transition-all text-sm font-bold">Detail</a>
                            <?php $waText = "Halo, saya tertarik dengan karya *{$p['title']}* yang ada di SiswaNiaga."; $waLink = "https://wa.me/{$p['phone']}?text=" . urlencode($waText); ?>
                            <a href="<?= $waLink ?>" target="_blank" class="flex items-center justify-center gap-2 bg-primary text-white py-2.5 rounded-xl hover:bg-teal-700 transition-all text-sm font-bold shadow-sm hover:shadow-md"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0012.04 2zM12.05 20.21c-1.5 0-2.96-.39-4.27-1.15l-.3-.18-3.11.82.83-3.04-.2-.31a8.154 8.154 0 01-1.26-4.44c0-4.52 3.67-8.19 8.19-8.19 2.19 0 4.24.85 5.78 2.39 1.54 1.54 2.39 3.59 2.39 5.78 0 4.51-3.68 8.18-8.19 8.18zm4.52-6.13c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.12-.17.25-.64.81-.78.97-.14.17-.29.19-.54.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.02-.38.11-.51.11-.11.25-.29.37-.43.12-.14.16-.23.24-.39.08-.17.04-.31-.02-.43-.06-.12-.56-1.34-.76-1.84-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.43.06-.66.31-.22.25-.87.85-.87 2.07 0 1.22.89 2.39 1.01 2.56.12.17 1.75 2.67 4.23 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.67-1.18.21-.58.21-1.07.14-1.18-.07-.11-.24-.18-.49-.3z"/></svg>Beli</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if($total_pages > 1): ?>
        <div class="flex justify-center mt-12">
            <nav class="flex items-center gap-2">
                <?php if($page > 1): ?><a href="<?= buildUrl($page - 1) ?>" class="w-10 h-10 flex items-center justify-center rounded-full border border-gray-300 text-gray-500 hover:bg-gray-50 hover:text-primary transition-all"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg></a><?php else: ?><span class="w-10 h-10 flex items-center justify-center rounded-full border border-gray-200 text-gray-300 cursor-not-allowed"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg></span><?php endif; ?>
                <?php for($i = 1; $i <= $total_pages; $i++): ?><?php if($i == $page): ?><span class="w-10 h-10 flex items-center justify-center rounded-full bg-primary text-white border border-primary font-bold shadow-md"><?= $i ?></span><?php else: ?><a href="<?= buildUrl($i) ?>" class="w-10 h-10 flex items-center justify-center rounded-full border border-gray-300 text-gray-600 hover:bg-gray-50 hover:text-primary transition-all"><?= $i ?></a><?php endif; ?><?php endfor; ?>
                <?php if($page < $total_pages): ?><a href="<?= buildUrl($page + 1) ?>" class="w-10 h-10 flex items-center justify-center rounded-full border border-gray-300 text-gray-500 hover:bg-gray-50 hover:text-primary transition-all"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg></a><?php else: ?><span class="w-10 h-10 flex items-center justify-center rounded-full border border-gray-200 text-gray-300 cursor-not-allowed"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg></span><?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>
    
    <?php else: ?>
        <div class="text-center py-20 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200">
            <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900">Belum ada karya ditemukan</h3>
            <p class="mt-1 text-sm text-gray-500">Coba kata kunci lain atau reset filter.</p>
            <div class="mt-6"><a href="index.php" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-teal-700 focus:outline-none">Reset Pencarian</a></div>
        </div>
    <?php endif; ?>

</div>
<?php require_once 'layout/footer.php'; ?>