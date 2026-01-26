<?php 
// Gunakan __DIR__ agar include database selalu jalan dimanapun file ini dipanggil
require_once __DIR__ . '/../config/database.php'; 

// --- LOGIC PATH OTOMATIS ---
if (file_exists('config/database.php')) {
    $base = ''; // Di Root (index.php)
} else {
    $base = '../'; // Di Subfolder
}

// --- LOGIC LINK DASHBOARD ---
$dashLink = '#';
if(isset($_SESSION['role'])) {
    if($_SESSION['role'] == 'admin') $dashLink = $base . 'admin/dashboard.php';
    elseif($_SESSION['role'] == 'student') $dashLink = $base . 'student/dashboard.php';
    elseif($_SESSION['role'] == 'superadmin') $dashLink = $base . 'superadmin/dashboard.php';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiswaNiaga</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { primary: '#0f766e', secondary: '#f59e0b' } } }
        }
    </script>
</head>
<body class="bg-gray-50 text-gray-800 font-sans flex flex-col min-h-screen">
    
    <nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                
                <div class="flex items-center flex-shrink-0 mr-8">
                    <a href="<?= $base ?>index.php" class="text-2xl font-extrabold text-primary tracking-tight">
                        Siswa<span class="text-secondary">Niaga</span>
                    </a>
                </div>

                <?php if(isset($_SESSION['user_id'])): ?>
                    
                    <div class="flex items-center gap-2 sm:gap-4 ml-auto">
                        
                        <a href="<?= $dashLink ?>" class="flex items-center gap-3 px-2 py-1 rounded-xl hover:bg-gray-50 transition border border-transparent hover:border-gray-200" title="Ke Dashboard">
                            <div class="relative">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['name']) ?>&background=0f766e&color=fff&size=128" 
                                     class="w-10 h-10 rounded-xl object-cover border border-gray-100 shadow-sm" alt="Profile">
                                <span class="absolute bottom-0 right-0 block h-2.5 w-2.5 rounded-full ring-2 ring-white bg-green-500"></span>
                            </div>
                            
                            <div class="hidden md:block text-left">
                                <p class="text-sm font-bold text-gray-800 leading-tight"><?= substr($_SESSION['name'], 0, 15) ?></p>
                                <p class="text-[10px] text-gray-500 font-medium uppercase tracking-wide mt-0.5"><?= $_SESSION['role'] ?></p>
                            </div>
                        </a>

                        <div class="h-8 w-px bg-gray-200 mx-1 hidden sm:block"></div>

                        <a href="<?= $base ?>auth/logout.php" class="p-2.5 text-red-500 bg-red-50 hover:bg-red-100 rounded-xl transition border border-red-100" title="Keluar Aplikasi">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </a>

                    </div>
                
                <?php else: ?>
                    
                    <div class="flex items-center space-x-4 ml-auto">
                        <a href="<?= $base ?>auth/login.php" class="text-gray-600 hover:text-primary font-medium transition">Masuk</a>
                        <a href="<?= $base ?>auth/register.php" class="bg-primary text-white px-5 py-2.5 rounded-full font-bold hover:bg-teal-700 shadow-lg shadow-teal-500/30 transition-all transform hover:-translate-y-0.5">Daftar Sekarang</a>
                    </div>

                <?php endif; ?>
            </div>
        </div>
    </nav>