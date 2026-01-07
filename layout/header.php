<?php require_once __DIR__ . '/../config/database.php'; ?>
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
                    <a href="../index.php" class="text-2xl font-extrabold text-primary tracking-tight">
                        Siswa<span class="text-secondary">Niaga</span>
                    </a>
                </div>

                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="hidden md:flex flex-1 max-w-xl px-4">
                        <form action="" method="GET" class="w-full relative">
                            <span class="absolute inset-y-0 right-4 flex items-center pl-2">
                                <button type="submit" class="p-1 focus:outline-none focus:shadow-outline text-gray-400 hover:text-primary">
                                    <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" class="w-5 h-5"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </button>
                            </span>
                            <input type="text" name="q" value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>" class="w-full py-2.5 pl-5 pr-10 text-sm text-gray-700 bg-white border border-gray-300 rounded-full focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-shadow shadow-sm" placeholder="Search product..." autocomplete="off">
                        </form>
                    </div>

                    <div class="flex items-center gap-3 sm:gap-5 ml-auto">
                        
                        <button class="relative p-2.5 bg-gray-50 hover:bg-gray-100 rounded-2xl transition-all border border-transparent hover:border-gray-200 group">
                            <svg class="w-5 h-5 text-gray-500 group-hover:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <span class="absolute top-2 right-2 flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500 border border-white"></span>
                            </span>
                            <span class="absolute top-1 right-1 bg-red-500 text-white text-[9px] font-bold px-1 rounded-full border border-white hidden">2</span>
                        </button>

                        <button class="relative p-2.5 bg-gray-50 hover:bg-gray-100 rounded-2xl transition-all border border-transparent hover:border-gray-200 group">
                            <svg class="w-5 h-5 text-gray-500 group-hover:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            <span class="absolute top-1 right-1.5 h-4 w-4 bg-red-500 text-white text-[9px] font-bold flex items-center justify-center rounded-full border border-white shadow-sm">8</span>
                        </button>

                        <div class="h-8 w-px bg-gray-200 mx-1 hidden sm:block"></div>

                        <div class="relative group cursor-pointer">
                            <div class="flex items-center gap-3 pl-2">
                                <div class="relative">
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['name']) ?>&background=0f766e&color=fff&size=128" 
                                         class="w-10 h-10 rounded-xl object-cover border border-gray-100 shadow-sm" alt="Profile">
                                    <span class="absolute bottom-0 right-0 block h-2.5 w-2.5 rounded-full ring-2 ring-white bg-green-500"></span>
                                </div>
                                
                                <div class="hidden md:block">
                                    <p class="text-sm font-bold text-gray-800 leading-tight"><?= substr($_SESSION['name'], 0, 15) ?></p>
                                    <p class="text-[10px] text-gray-500 font-medium uppercase tracking-wide mt-0.5"><?= $_SESSION['role'] ?></p>
                                </div>

                                <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>

                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl py-2 border border-gray-100 hidden group-hover:block transform origin-top-right transition-all z-50">
                                <div class="px-4 py-2 border-b border-gray-50">
                                    <p class="text-xs text-gray-400">Signed in as</p>
                                    <p class="text-sm font-bold text-gray-800 truncate"><?= $_SESSION['name'] ?></p>
                                </div>
                                
                                <?php 
                                    // Link dashboard sesuai role
                                    $dashLink = '#';
                                    if($_SESSION['role'] == 'admin') $dashLink = '../admin/dashboard.php';
                                    elseif($_SESSION['role'] == 'student') $dashLink = '../student/dashboard.php';
                                    elseif($_SESSION['role'] == 'superadmin') $dashLink = '../superadmin/dashboard.php';
                                ?>
                                <a href="<?= $dashLink ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary">Dashboard</a>
                                <a href="../auth/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 hover:text-red-700">Sign Out</a>
                            </div>
                        </div>

                    </div>
                
                <?php else: ?>
                    
                    <div class="flex items-center space-x-4">
                        <a href="../auth/login.php" class="text-gray-600 hover:text-primary font-medium transition">Masuk</a>
                        <a href="../auth/register.php" class="bg-primary text-white px-5 py-2.5 rounded-full font-bold hover:bg-teal-700 shadow-lg shadow-teal-500/30 transition-all transform hover:-translate-y-0.5">Daftar Sekarang</a>
                    </div>

                <?php endif; ?>
            </div>
        </div>
    </nav>