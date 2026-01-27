<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// --- PERBAIKAN FUNGSI WAKTU (FIXED) ---
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $weeks = floor($diff->d / 7);
    $days = $diff->d - ($weeks * 7);

    $string = array(
        'y' => 'tahun', 'm' => 'bulan', 'w' => 'minggu',
        'd' => 'hari', 'h' => 'jam', 'i' => 'menit', 's' => 'detik',
    );

    $values = [
        'y' => $diff->y, 'm' => $diff->m, 'w' => $weeks,
        'd' => $days, 'h' => $diff->h, 'i' => $diff->i, 's' => $diff->s,
    ];

    foreach ($string as $k => &$v) {
        if ($values[$k]) {
            $v = $values[$k] . ' ' . $v;
        } else {
            unset($string[$k]);
        }
    }

    if (!$string) return 'Baru saja';
    $string = array_slice($string, 0, 1);
    return implode(', ', $string) . ' lalu';
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 1. AMBIL DATA SISWA
$stmt = $pdo->prepare("SELECT u.*, s.name as school_name 
                        FROM users u 
                        JOIN schools s ON u.school_id = s.id 
                        WHERE u.id = ? AND u.role = 'student'");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

if (!$student) die("Halaman profil tidak ditemukan.");

// 2. HITUNG STATISTIK
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM products WHERE user_id = ?");
$stmtTotal->execute([$user_id]);
$total_karya = $stmtTotal->fetchColumn();

$stmtApprove = $pdo->prepare("SELECT COUNT(*) FROM products WHERE user_id = ? AND status = 'active'");
$stmtApprove->execute([$user_id]);
$total_approved = $stmtApprove->fetchColumn();

$stmtSum = $pdo->prepare("SELECT SUM(views) as total_views, SUM(likes) as total_likes FROM products WHERE user_id = ?");
$stmtSum->execute([$user_id]);
$stats = $stmtSum->fetch();
$total_views = $stats['total_views'] ? $stats['total_views'] : 0;
$total_likes = $stats['total_likes'] ? $stats['total_likes'] : 0;

// 3. AMBIL DAFTAR KARYA (Untuk List Aktivitas)
$stmtProds = $pdo->prepare("SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC");
$stmtProds->execute([$user_id]);
$products = $stmtProds->fetchAll();

require_once 'layout/header.php';
?>

<style>
    .profile-gradient { background: linear-gradient(135deg, #10df6d 0%, #10df6d 100%); }
    .active-tab {
        background-color: #ede9fe; color: #b000f0; border-left: 4px solid #f8fbf9;
    }
    .inactive-tab {
        color: #64748b; border-left: 4px solid transparent;
    }
    .inactive-tab:hover { background-color: #f8fafc; color: #334155; }
    @keyframes fade-in {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in { animation: fade-in 0.3s ease-out forwards; }
</style>

<div class="bg-gray-50 min-h-screen pb-12 font-sans">

    <div class="profile-gradient w-full text-white pt-12 pb-24 relative overflow-hidden">
        <div class="absolute top-0 left-0 w-64 h-64 bg-white/10 rounded-full mix-blend-overlay filter blur-3xl -translate-x-1/2 -translate-y-1/2"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-white/10 rounded-full mix-blend-overlay filter blur-3xl translate-x-1/4 translate-y-1/4"></div>

        <div class="max-w-5xl mx-auto px-4 relative z-10 text-center">
            <div class="absolute top-0 left-4">
                 <a href="javascript:history.back()" class="flex items-center gap-2 bg-white/20 hover:bg-white/30 px-4 py-2 rounded-full text-sm font-medium backdrop-blur-sm transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    Kembali
                 </a>
            </div>

            <div class="w-28 h-28 mx-auto bg-white/20 backdrop-blur-md p-1 rounded-full mb-4">
                <div class="w-full h-full bg-white rounded-full flex items-center justify-center text-4xl font-bold text-indigo-600 uppercase shadow-lg">
                    <?= substr($student['name'], 0, 1) ?>
                </div>
            </div>

            <h1 class="text-3xl font-bold mb-2"><?= $student['name'] ?></h1>
            <p class="text-indigo-100 text-lg mb-6">Siswa Kreatif • <?= $student['school_name'] ?></p>

            <div class="flex justify-center gap-3 mb-8">
                <span class="bg-white/20 backdrop-blur px-4 py-1.5 rounded-full text-sm flex items-center gap-2">
                    Bergabung <?= !empty($student['created_at']) ? date('M Y', strtotime($student['created_at'])) : '-' ?>
                </span>
                <span class="bg-yellow-400 text-yellow-900 px-4 py-1.5 rounded-full text-sm font-bold shadow-lg shadow-yellow-500/30">
                    ✓ Student Aktif
                </span>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 -mt-12 relative z-20">
        <div class="flex flex-col md:flex-row gap-6">
            
            <div class="w-full md:w-64 flex-shrink-0">
                <div class="bg-white rounded-2xl shadow-sm p-6 sticky top-24">
                    <h3 class="text-lg font-bold text-indigo-900 mb-1">Menu Profil</h3>
                    <p class="text-xs text-gray-500 mb-6">Jelajahi konten <?= explode(' ', $student['name'])[0] ?></p>
                    
                    <nav class="space-y-2">
                        <button onclick="switchTab('ringkasan')" id="btn-ringkasan" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all active-tab text-left">
                            <div class="w-8 h-8 rounded-lg bg-indigo-500 text-white flex items-center justify-center">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                            </div>
                            <div>
                                <span class="block text-sm">Ringkasan</span>
                                <span class="block text-[10px] opacity-70 font-normal">Overview aktivitas</span>
                            </div>
                        </button>

                        <button onclick="switchTab('karya')" id="btn-karya" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-medium transition-all inactive-tab text-left">
                            <div class="w-8 h-8 rounded-lg bg-blue-500 text-white flex items-center justify-center">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                            <div>
                                <span class="block text-sm">Karya Saya</span>
                                <span class="block text-[10px] opacity-70 font-normal"><?= count($products) ?> karya</span>
                            </div>
                        </button>
                    </nav>

                    <div class="mt-8 pt-6 border-t border-gray-100">
                        <p class="text-xs font-bold text-gray-400 uppercase mb-3">Aksi Cepat</p>
                        <ul class="space-y-3 text-sm text-gray-600">
                            <li class="flex items-center gap-2 cursor-pointer hover:text-indigo-600"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg> Jelajahi Galeri</li>
                            <li class="flex items-center gap-2 cursor-pointer hover:text-indigo-600"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" /></svg> Bagikan Profil</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="flex-1">
                
                <div id="tab-ringkasan" class="animate-fade-in">
                    
                    <div class="bg-white rounded-2xl shadow-sm p-8 space-y-8">
                        
                        <div class="flex justify-between items-center border-b border-gray-100 pb-4">
                            <h2 class="text-2xl font-bold text-indigo-900">Ringkasan Profil</h2>
                            <span class="bg-yellow-400 text-yellow-900 px-4 py-1.5 rounded-full text-xs font-bold shadow-sm">Student</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            
                            <div class="bg-gray-50 rounded-2xl p-5 border border-gray-100 flex items-center gap-4 hover:border-blue-200 transition">
                                <div class="bg-blue-100 w-12 h-12 rounded-xl flex items-center justify-center text-blue-600 shadow-sm">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                </div>
                                <div>
                                    <div class="text-2xl font-extrabold text-blue-700"><?= $total_karya ?></div>
                                    <div class="text-gray-600 font-bold text-xs uppercase tracking-wide">Total Karya</div>
                                </div>
                            </div>

                            <div class="bg-gray-50 rounded-2xl p-5 border border-gray-100 flex items-center gap-4 hover:border-green-200 transition">
                                <div class="bg-green-100 w-12 h-12 rounded-xl flex items-center justify-center text-green-600 shadow-sm">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <div>
                                    <div class="text-2xl font-extrabold text-green-700"><?= $total_approved ?></div>
                                    <div class="text-gray-600 font-bold text-xs uppercase tracking-wide">Disetujui</div>
                                </div>
                            </div>

                            <div class="bg-gray-50 rounded-2xl p-5 border border-gray-100 flex items-center gap-4 hover:border-purple-200 transition">
                                <div class="bg-purple-100 w-12 h-12 rounded-xl flex items-center justify-center text-purple-600 shadow-sm">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                                </div>
                                <div>
                                    <div class="text-2xl font-extrabold text-purple-700"><?= $total_likes ?></div>
                                    <div class="text-gray-600 font-bold text-xs uppercase tracking-wide">Total Likes</div>
                                </div>
                            </div>

                        </div>

                        <div>
                            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Aktivitas Terbaru
                            </h3>
                            
                            <?php if(count($products) > 0): ?>
                                <div class="space-y-3">
                                    <?php 
                                    $recent_products = array_slice($products, 0, 5); 
                                    foreach($recent_products as $p): 
                                    ?>
                                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between hover:bg-gray-100 transition gap-4">
                                        <div class="flex items-center gap-4 w-full">
                                            <div class="w-10 h-10 rounded-lg bg-indigo-500 flex flex-shrink-0 items-center justify-center text-white shadow-sm">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($p['title']) ?></h4>
                                                <p class="text-[11px] text-gray-500 mt-0.5"><?= time_elapsed_string($p['created_at']) ?></p>
                                            </div>
                                        </div>
                                        
                                        <div class="flex-shrink-0 ml-auto sm:ml-0">
                                            <?php if($p['status'] == 'active'): ?>
                                                <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider">Approved</span>
                                            <?php elseif($p['status'] == 'rejected'): ?>
                                                <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider">Rejected</span>
                                            <?php else: ?>
                                                <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider">Pending</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="bg-gray-50 p-8 rounded-xl text-center text-gray-400 border border-dashed border-gray-200 text-sm">
                                    Belum ada aktivitas terbaru.
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                    </div>

                <div id="tab-karya" class="hidden animate-fade-in">
                    <div class="bg-white rounded-2xl shadow-sm p-8 space-y-6">
                        <div class="flex justify-between items-center border-b border-gray-100 pb-4">
                            <div>
                                <h2 class="text-2xl font-bold text-indigo-900">Karya <?= explode(' ', $student['name'])[0] ?></h2>
                                <p class="text-sm text-gray-500 mt-1"><?= count($products) ?> karya telah dipublikasikan</p>
                            </div>
                            <span class="bg-orange-400 text-white px-3 py-1 rounded-lg text-xs font-bold shadow-sm"><?= $total_approved ?> Disetujui</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php foreach($products as $p): ?>
                            <div class="border border-gray-100 rounded-2xl p-4 hover:shadow-lg transition bg-white group relative">
                                <div class="relative h-48 mb-4 overflow-hidden rounded-xl bg-gray-100">
                                    <img src="uploads/<?= $p['image'] ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                                    <span class="absolute top-2 left-2 bg-indigo-600/90 backdrop-blur text-white text-[10px] font-bold px-2 py-1 rounded-lg">
                                        <?= $p['category'] ?>
                                    </span>
                                </div>
                                
                                <h3 class="font-bold text-gray-800 text-lg mb-1 truncate"><?= $p['title'] ?></h3>
                                <p class="text-sm text-gray-500 line-clamp-2 mb-4 h-10 leading-relaxed"><?= $p['description'] ?></p>
                                
                                <div class="flex items-center justify-between pt-4 border-t border-gray-50 text-xs text-gray-400">
                                    <div class="flex items-center gap-1">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                        <?= date('M Y', strtotime($p['created_at'])) ?>
                                    </div>
                                    <div class="flex gap-3">
                                        <span class="flex items-center gap-1"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg> <?= $p['views'] ?></span>
                                        <span class="flex items-center gap-1"><svg class="h-4 w-4 text-pink-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd" /></svg> <?= $p['likes'] ?></span>
                                    </div>
                                </div>
                                
                                <a href="detail.php?id=<?= $p['id'] ?>" class="absolute inset-0 z-10"></a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
function switchTab(tabName) {
    document.getElementById('tab-ringkasan').classList.add('hidden');
    document.getElementById('tab-karya').classList.add('hidden');
    
    document.getElementById('btn-ringkasan').classList.replace('active-tab', 'inactive-tab');
    document.getElementById('btn-karya').classList.replace('active-tab', 'inactive-tab');

    document.getElementById('tab-' + tabName).classList.remove('hidden');
    document.getElementById('btn-' + tabName).classList.replace('inactive-tab', 'active-tab');
}
</script>

<?php require_once 'layout/footer.php'; ?>