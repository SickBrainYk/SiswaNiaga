<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Pastikan session dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$current_role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// --- 1. LOGIKA VIEW ---
$pdo->prepare("UPDATE products SET views = views + 1 WHERE id = ?")->execute([$id]);

// --- 2. LOGIKA HANDLING POST ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // A. Handle Like (WAJIB LOGIN)
    if (isset($_POST['toggle_like'])) {
        if (!$current_user_id) {
            echo "<script>alert('Silakan login untuk menyukai karya ini.'); window.location='auth/login.php';</script>";
            exit;
        }

        $checkLike = $pdo->prepare("SELECT id FROM product_likes WHERE product_id = ? AND user_id = ?");
        $checkLike->execute([$id, $current_user_id]);

        if ($checkLike->rowCount() > 0) {
            $pdo->prepare("DELETE FROM product_likes WHERE product_id = ? AND user_id = ?")->execute([$id, $current_user_id]);
        } else {
            $pdo->prepare("INSERT INTO product_likes (product_id, user_id) VALUES (?, ?)")->execute([$id, $current_user_id]);
        }
        
        header("Location: detail.php?id=$id");
        exit;
    }

    // B. Handle Kirim Komentar
    if (isset($_POST['submit_comment'])) {
        if (!$current_user_id) {
            echo "<script>alert('Harap login untuk berkomentar'); window.location='auth/login.php';</script>";
            exit;
        }
        $comment = sanitize($_POST['comment']);
        if ($comment) {
            $stmtComm = $pdo->prepare("INSERT INTO product_comments (product_id, user_id, comment) VALUES (?, ?, ?)");
            $stmtComm->execute([$id, $current_user_id, $comment]);
            header("Location: detail.php?id=$id#comments-area");
            exit;
        }
    }

    // C. Handle Hapus Komentar
    if (isset($_POST['delete_comment'])) {
        if (!$current_user_id) {
             echo "<script>alert('Akses ditolak'); window.location='detail.php?id=$id';</script>";
             exit;
        }
        $comment_id = (int)$_POST['comment_id'];
        $stmtCheck = $pdo->prepare("SELECT user_id FROM product_comments WHERE id = ?");
        $stmtCheck->execute([$comment_id]);
        $commentData = $stmtCheck->fetch();

        if ($commentData && ($commentData['user_id'] == $current_user_id || $current_role == 'admin' || $current_role == 'superadmin')) {
            $pdo->prepare("DELETE FROM product_comments WHERE id = ?")->execute([$comment_id]);
            header("Location: detail.php?id=$id#comments-area");
            exit;
        } else {
             echo "<script>alert('Gagal menghapus komentar.'); window.location='detail.php?id=$id';</script>";
             exit;
        }
    }

    // D. Handle Laporan
    if (isset($_POST['submit_report'])) {
        $reason = sanitize($_POST['reason']);
        if ($reason) {
            $pdo->prepare("INSERT INTO reports (product_id, reason) VALUES (?, ?)")->execute([$id, $reason]);
            echo "<script>alert('Laporan berhasil dikirim!'); window.location='detail.php?id=$id';</script>";
            exit;
        }
    }
}

// --- 3. AMBIL DATA UTAMA ---
$stmt = $pdo->prepare("SELECT p.*, u.name as student_name, u.email as student_email, u.phone, s.name as school_name 
                        FROM products p 
                        JOIN users u ON p.user_id = u.id 
                        JOIN schools s ON p.school_id = s.id 
                        WHERE p.id = ? AND p.status = 'active'");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) die("Produk tidak ditemukan.");

// Hitung Total Karya Siswa
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM products WHERE user_id = ? AND status = 'active'");
$stmtCount->execute([$product['user_id']]);
$total_karya_siswa = $stmtCount->fetchColumn();

// Cek Status Like User Login
$is_liked = false;
if ($current_user_id) {
    $stmtLikeCheck = $pdo->prepare("SELECT id FROM product_likes WHERE product_id = ? AND user_id = ?");
    $stmtLikeCheck->execute([$id, $current_user_id]);
    if ($stmtLikeCheck->rowCount() > 0) $is_liked = true;
}

// Hitung Total Likes Real-time
$stmtTotalLikes = $pdo->prepare("SELECT COUNT(*) FROM product_likes WHERE product_id = ?");
$stmtTotalLikes->execute([$id]);
$total_likes_count = $stmtTotalLikes->fetchColumn();

// Ambil Komentar
$stmtComments = $pdo->prepare("SELECT c.*, u.name, u.role FROM product_comments c JOIN users u ON c.user_id = u.id WHERE c.product_id = ? ORDER BY c.created_at DESC");
$stmtComments->execute([$id]);
$comments = $stmtComments->fetchAll();
$total_comments = count($comments);

// --- LOGIKA GAMBAR KATEGORI (LOCAL STORAGE) ---
$categories = function_exists('getCategories') ? getCategories() : [];
$catKey = $product['category']; // Nama Kategori dari DB (misal: "Kuliner", "Seni Rupa")
$catLabel = isset($categories[$catKey]) ? $categories[$catKey] : ($catKey ?? 'Umum');

$iconPath = null;

// 1. Cek KHUSUS untuk Kuliner -> makanan.jpg
if ($catKey == 'Kuliner') {
    if (file_exists("assets/icons/makanan.jpg")) $iconPath = "assets/icons/makanan.jpg";
    elseif (file_exists("assets/icons/makanan.png")) $iconPath = "assets/icons/makanan.png";
}

// 2. Jika belum ketemu, cari sesuai nama kategori
if (!$iconPath) {
    $cleanKey = str_replace(' ', '', $catKey); // Hilangkan spasi
    
    if (file_exists("assets/icons/{$cleanKey}.jpg")) $iconPath = "assets/icons/{$cleanKey}.jpg";
    elseif (file_exists("assets/icons/{$cleanKey}.png")) $iconPath = "assets/icons/{$cleanKey}.png";
    elseif (file_exists("assets/icons/{$catKey}.jpg")) $iconPath = "assets/icons/{$catKey}.jpg"; 
}

// 3. Fallback jika tidak ada gambar
if (!$iconPath) {
    $iconPath = "https://via.placeholder.com/200/e5e7eb/0f766e?text=" . substr($catKey, 0, 1);
}

require_once 'layout/header.php';
?>

<div class="max-w-7xl mx-auto px-4 py-8 bg-gray-50 min-h-screen font-sans">
    
    <a href="index.php" class="inline-flex items-center gap-2 bg-white text-gray-600 border border-gray-200 px-4 py-2 rounded-lg font-medium text-sm hover:bg-gray-50 transition mb-6 shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
        Kembali
    </a>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 mb-10">
        
        <div class="flex gap-4 h-[500px]">
            <div class="flex flex-col gap-3 w-20 flex-shrink-0 overflow-y-auto scrollbar-hide">
                <button onclick="changeImage('uploads/<?= $product['image'] ?>')" class="w-20 h-20 rounded-xl border-2 border-transparent hover:border-indigo-500 focus:border-indigo-500 overflow-hidden bg-white shadow-sm transition">
                    <img src="uploads/<?= $product['image'] ?>" class="w-full h-full object-cover">
                </button>
                <?php if(!empty($product['image1'])): ?>
                <button onclick="changeImage('uploads/<?= $product['image1'] ?>')" class="w-20 h-20 rounded-xl border-2 border-transparent hover:border-indigo-500 focus:border-indigo-500 overflow-hidden bg-white shadow-sm transition">
                    <img src="uploads/<?= $product['image1'] ?>" class="w-full h-full object-cover">
                </button>
                <?php endif; ?>
                <?php if(!empty($product['image2'])): ?>
                <button onclick="changeImage('uploads/<?= $product['image2'] ?>')" class="w-20 h-20 rounded-xl border-2 border-transparent hover:border-indigo-500 focus:border-indigo-500 overflow-hidden bg-white shadow-sm transition">
                    <img src="uploads/<?= $product['image2'] ?>" class="w-full h-full object-cover">
                </button>
                <?php endif; ?>
            </div>
            <div class="flex-1 bg-white rounded-3xl p-2 shadow-sm border border-gray-100 overflow-hidden relative group">
                 <img id="mainImage" src="uploads/<?= $product['image'] ?>" class="w-full h-full object-contain hover:scale-105 transition duration-500">
            </div>
        </div>

        <div class="flex flex-col justify-center">
            <h1 class="text-4xl font-extrabold text-gray-900 mb-2 uppercase tracking-tight leading-none"><?= $product['title'] ?></h1>
            <div class="text-3xl font-bold text-gray-800 mb-6">Rp <?= number_format($product['price'], 0, ',', '.') ?></div>
            
            <p class="text-gray-500 leading-relaxed mb-6 text-base"><?= nl2br($product['description']) ?></p>

            <div class="mb-8">
                <form method="POST">
                    <input type="hidden" name="toggle_like" value="1">
                    <button type="submit" class="flex items-center gap-3 px-6 py-3 rounded-full transition-all duration-300 font-bold border-2 <?= $is_liked ? 'bg-red-50 border-red-500 text-red-600 hover:bg-red-100' : 'bg-white border-gray-200 text-gray-500 hover:border-red-400 hover:text-red-500' ?>">
                        <?php if($is_liked): ?>
                            <svg class="h-6 w-6 fill-current" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                            <span>Disukai</span>
                        <?php else: ?>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                            <span>Suka Karya Ini</span>
                        <?php endif; ?>
                        
                        <span class="ml-1 bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded-full"><?= number_format($total_likes_count) ?></span>
                    </button>
                </form>
            </div>

            <?php 
                $waText = "Halo {$product['student_name']},\n\n" .
                          "Saya ingin membeli karya Anda:\n" .
                          "*Judul:* {$product['title']}\n" .
                          "*Harga:* Rp " . number_format($product['price'], 0, ',', '.') . "\n\n" .
                          "Apakah stok masih tersedia dan bagaimana cara pembayarannya?";
                $waLink = "https://wa.me/{$product['phone']}?text=" . urlencode($waText);
            ?>
            <a href="<?= $waLink ?>" target="_blank" class="w-full bg-black text-white font-medium py-4 rounded-full text-center hover:bg-gray-800 transition shadow-lg flex items-center justify-center gap-2 mb-4 transform hover:-translate-y-1">
                Beli via WhatsApp
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.017-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
            </a>
            <button onclick="toggleReport()" class="text-xs text-gray-400 hover:text-red-500 underline text-center w-full">Laporkan Karya Ini</button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
        
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex flex-col justify-center">
            <h3 class="text-xl font-bold mb-4 text-gray-800">Statistik Karya</h3>
            <div class="space-y-4">
                <div class="flex items-center gap-4 p-3 rounded-xl bg-gray-50">
                    <div class="w-10 h-10 rounded-lg bg-orange-100 text-orange-500 flex items-center justify-center">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Dilihat</p>
                        <p class="font-bold text-gray-800 text-lg"><?= number_format($product['views']) ?> <span class="text-xs font-normal text-gray-400">kali</span></p>
                    </div>
                </div>

                <div class="flex items-center gap-4 p-3 rounded-xl bg-gray-50">
                    <div class="w-10 h-10 rounded-lg bg-red-100 text-red-500 flex items-center justify-center">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Disukai</p>
                        <p class="font-bold text-gray-800 text-lg"><?= number_format($total_likes_count) ?> <span class="text-xs font-normal text-gray-400">orang</span></p> 
                    </div>
                </div>

                <div class="flex items-center gap-4 p-3 rounded-xl bg-gray-50">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-500 flex items-center justify-center">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">Komentar</p>
                        <p class="font-bold text-gray-800 text-lg"><?= number_format($total_comments) ?> <span class="text-xs font-normal text-gray-400">ulasan</span></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 text-center flex flex-col items-center justify-center">
            <div class="w-20 h-20 rounded-full bg-[#ccf318] flex items-center justify-center text-4xl font-bold text-black mb-3 border-4 border-white shadow-sm">
                <?= substr($product['student_name'], 0, 1) ?>
            </div>
            <h3 class="text-xl font-bold text-gray-900"><?= $product['student_name'] ?></h3>
            <p class="text-xs text-gray-500 mb-4"><?= $product['school_name'] ?></p>
            <div class="flex gap-2 items-center mb-6 w-full px-4">
                <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-cyan-400 w-3/4"></div>
                </div>
                <span class="bg-[#ccf318] text-black text-xs font-bold px-2 py-1 rounded-full whitespace-nowrap">
                    <?= $total_karya_siswa ?> Karya
                </span>
            </div>
            <a href="public_profile.php?id=<?= $product['user_id'] ?>" class="w-full bg-indigo-500/10 text-indigo-600 font-bold py-3 rounded-xl hover:bg-indigo-500 hover:text-white transition flex items-center justify-center gap-2">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                Lihat Profil
            </a>
        </div>

        <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <img src="<?= $iconPath ?>" class="w-12 h-12 object-contain" alt="Icon">
                <div>
                    <p class="text-xs text-gray-400 uppercase font-bold tracking-wider mb-1">Kategori</p>
                    <span class="text-2xl font-bold text-gray-800"><?= $catLabel ?></span>
                </div>
            </div>
        </div>
    </div>

    <div id="comments-area" class="bg-white border border-gray-200 rounded-3xl p-6">
        <h3 class="font-bold text-gray-800 text-xl mb-6">Komentar & Feedback (<?= $total_comments ?>)</h3>
        
        <?php if($current_user_id): ?>
            <form method="POST" class="mb-8 flex gap-4 items-start">
                <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold shrink-0">
                    <?= substr($_SESSION['name'], 0, 1) ?>
                </div>
                <div class="flex-1">
                    <textarea name="comment" rows="2" class="w-full border-gray-200 border rounded-xl p-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none bg-gray-50 focus:bg-white transition" placeholder="Tulis tanggapan positifmu..." required></textarea>
                    <div class="mt-2 flex justify-end">
                        <button type="submit" name="submit_comment" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-indigo-700 transition shadow-sm">Kirim</button>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <div class="bg-indigo-50 p-4 rounded-xl flex justify-between items-center text-indigo-900 mb-8 border border-indigo-100">
                <div class="flex items-center gap-3">
                     <svg class="h-6 w-6 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                     <span class="font-medium">Silakan login untuk berkomentar</span>
                </div>
                <a href="auth/login.php" class="bg-white text-indigo-600 font-bold px-6 py-2 rounded-lg hover:bg-gray-100 transition shadow-sm border border-gray-200">Masuk</a>
            </div>
        <?php endif; ?>

        <div class="space-y-6">
            <?php if($total_comments > 0): ?>
                <?php foreach($comments as $c): ?>
                <div class="flex gap-4 group">
                    <div class="w-10 h-10 rounded-full <?= $c['role'] == 'student' ? 'bg-orange-100 text-orange-600' : 'bg-gray-100 text-gray-600' ?> flex items-center justify-center font-bold shrink-0">
                        <?= substr($c['name'], 0, 1) ?>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-gray-900"><?= htmlspecialchars($c['name']) ?></span>
                                <?php if($c['role'] == 'admin' || $c['role'] == 'superadmin'): ?>
                                    <span class="bg-blue-100 text-blue-700 text-[10px] px-2 py-0.5 rounded-full font-bold uppercase">Guru/Admin</span>
                                <?php endif; ?>
                                <span class="text-xs text-gray-400">• <?= date('d M Y, H:i', strtotime($c['created_at'])) ?></span>
                            </div>
                            
                            <?php if ($current_user_id && $current_user_id == $c['user_id']): ?>
                                <form method="POST" onsubmit="return confirm('Yakin ingin menghapus komentar ini?');">
                                    <input type="hidden" name="delete_comment" value="1">
                                    <input type="hidden" name="comment_id" value="<?= $c['id'] ?>">
                                    <button type="submit" class="text-gray-400 hover:text-red-500 transition text-xs flex items-center gap-1" title="Hapus Komentar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Hapus
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <p class="text-gray-600 text-sm leading-relaxed"><?= nl2br(htmlspecialchars($c['comment'])) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-10 opacity-50">
                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                    <p>Belum ada komentar. Jadilah yang pertama!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<div id="reportModal" class="fixed inset-0 bg-black/60 z-50 hidden items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-2xl w-full max-w-md p-6 animate-fade-in">
        <h2 class="text-xl font-bold text-red-600 mb-4">Laporkan Pelanggaran</h2>
        <form method="POST">
            <textarea name="reason" rows="4" class="w-full border p-3 rounded-lg mb-4 focus:ring-2 focus:ring-red-200 focus:border-red-500 outline-none" placeholder="Jelaskan alasan pelaporan..." required></textarea>
            <div class="flex gap-2">
                <button type="button" onclick="toggleReport()" class="flex-1 bg-gray-100 text-gray-700 py-2 rounded-lg font-bold hover:bg-gray-200">Batal</button>
                <button type="submit" name="submit_report" class="flex-1 bg-red-600 text-white py-2 rounded-lg font-bold hover:bg-red-700 shadow-lg shadow-red-200">Kirim</button>
            </div>
        </form>
    </div>
</div>

<script>
function changeImage(src) { document.getElementById('mainImage').src = src; }
function toggleReport() {
    const modal = document.getElementById('reportModal');
    modal.classList.toggle('hidden'); modal.classList.toggle('flex');
}
</script>
<?php require_once 'layout/footer.php'; ?>