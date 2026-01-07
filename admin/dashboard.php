<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// Keamanan: Hanya Role 'admin' (Guru) yang boleh masuk
checkRole(['admin']);

// Ambil ID Sekolah dari sesi login guru tersebut
$school_id = $_SESSION['school_id'];

// 1. DATA PENDING (Menunggu Review)
$stmt_pending = $pdo->prepare("SELECT p.*, u.name as student_name 
                               FROM products p 
                               JOIN users u ON p.user_id = u.id 
                               WHERE p.school_id = ? AND p.status = 'pending' 
                               ORDER BY p.created_at ASC");
$stmt_pending->execute([$school_id]);
$pending_items = $stmt_pending->fetchAll();

// 2. DATA ACTIVE (Katalog Tayang)
$stmt_active = $pdo->prepare("SELECT p.*, u.name as student_name 
                              FROM products p 
                              JOIN users u ON p.user_id = u.id 
                              WHERE p.school_id = ? AND p.status = 'active' 
                              ORDER BY p.created_at DESC");
$stmt_active->execute([$school_id]);
$active_items = $stmt_active->fetchAll();

// 3. DATA LAPORAN (Reports)
$stmt_reports = $pdo->prepare("SELECT r.id as report_id, r.reason, r.created_at as report_date,
                                      p.id as product_id, p.title, p.image, u.name as student_name
                               FROM reports r 
                               JOIN products p ON r.product_id = p.id 
                               JOIN users u ON p.user_id = u.id
                               WHERE p.school_id = ?
                               ORDER BY r.created_at DESC");
$stmt_reports->execute([$school_id]);
$reports = $stmt_reports->fetchAll();

require_once '../layout/header.php';
?>

<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Dashboard Guru</h1>
            <p class="text-gray-600">Kelola karya siswa sekolah Anda.</p>
        </div>
        <div class="mt-4 md:mt-0">
            <span class="bg-primary text-white px-4 py-2 rounded-full text-sm font-semibold">
                Sekolah: <?= isset($_SESSION['school_name']) ? $_SESSION['school_name'] : 'Sekolah Anda' ?>
            </span>
        </div>
    </div>

    <div class="flex border-b border-gray-200 mb-6 space-x-8">
        <button onclick="showTab('pending')" id="btn-pending" class="tab-btn py-2 px-1 border-b-2 border-primary font-bold text-primary transition">
            Moderasi (<?= count($pending_items) ?>)
        </button>
        <button onclick="showTab('active')" id="btn-active" class="tab-btn py-2 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 transition">
            Katalog Aktif (<?= count($active_items) ?>)
        </button>
        <button onclick="showTab('reports')" id="btn-reports" class="tab-btn py-2 px-1 border-b-2 border-transparent text-gray-500 hover:text-red-600 transition">
            Laporan <?php if(count($reports)>0) echo "<span class='bg-red-500 text-white text-xs px-2 py-0.5 rounded-full ml-1'>".count($reports)."</span>"; ?>
        </button>
    </div>

    <div id="pending" class="tab-content block">
        <?php if(count($pending_items) == 0): ?>
            <div class="text-center py-10 bg-gray-50 rounded border border-dashed border-gray-300">
                <p class="text-gray-500">Tidak ada karya baru yang perlu direview.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach($pending_items as $p): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-100">
                    <img src="../uploads/<?= $p['image'] ?>" class="w-full h-48 object-cover bg-gray-200">
                    <div class="p-4">
                        <div class="flex justify-between items-start">
                            <h3 class="font-bold text-lg text-gray-800 truncate"><?= $p['title'] ?></h3>
                            <span class="text-primary font-bold">Rp <?= number_format($p['price']) ?></span>
                        </div>
                        <p class="text-xs text-gray-500 mb-2">Siswa: <?= $p['student_name'] ?></p>
                        <p class="text-sm text-gray-600 line-clamp-2 mb-4"><?= $p['description'] ?></p>
                        
                        <form action="process.php" method="POST" class="space-y-2">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button type="submit" name="action" value="approve" class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-2 rounded transition">
                                ✓ Setujui & Tayangkan
                            </button>
                            
                            <div class="relative">
                                <input type="text" name="reason" placeholder="Alasan penolakan (opsional)" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-red-500">
                            </div>
                            <button type="submit" name="action" value="reject" class="w-full bg-white border border-red-500 text-red-500 hover:bg-red-50 font-bold py-2 rounded transition">
                                ✗ Tolak
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div id="active" class="tab-content hidden">
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produk</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Siswa</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Harga</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach($active_items as $p): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <img class="h-10 w-10 rounded object-cover" src="../uploads/<?= $p['image'] ?>" alt="">
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900"><?= $p['title'] ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $p['student_name'] ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Rp <?= number_format($p['price']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            <a href="../student/product_form.php?id=<?= $p['id'] ?>" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-3 py-1 rounded">Edit</a>
                            <a href="process.php?action=delete&id=<?= $p['id'] ?>" onclick="return confirm('Yakin hapus produk ini?')" class="text-red-600 hover:text-red-900 bg-red-50 px-3 py-1 rounded">Hapus</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="reports" class="tab-content hidden">
        <?php if(count($reports) == 0): ?>
            <div class="text-center py-10 bg-green-50 rounded border border-green-200">
                <p class="text-green-700 font-medium">Bagus! Tidak ada laporan pelanggaran saat ini.</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach($reports as $rep): ?>
                <div class="bg-white border-l-4 border-red-500 shadow rounded p-4 flex flex-col md:flex-row justify-between items-start md:items-center">
                    <div class="flex items-start space-x-4 mb-4 md:mb-0">
                        <img src="../uploads/<?= $rep['image'] ?>" class="w-16 h-16 object-cover rounded bg-gray-200">
                        <div>
                            <h4 class="font-bold text-gray-800">Dilaporkan: <?= $rep['title'] ?></h4>
                            <p class="text-sm text-gray-600">Siswa: <?= $rep['student_name'] ?></p>
                            <div class="mt-2 bg-red-50 text-red-700 px-3 py-1 rounded text-sm inline-block">
                                <strong>Alasan:</strong> "<?= $rep['reason'] ?>"
                            </div>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <a href="../student/product_form.php?id=<?= $rep['product_id'] ?>" target="_blank" class="bg-gray-100 text-gray-700 px-4 py-2 rounded hover:bg-gray-200 text-sm">
                            Cek Detail
                        </a>
                        <a href="process.php?action=ignore_report&report_id=<?= $rep['report_id'] ?>" class="bg-blue-100 text-blue-700 px-4 py-2 rounded hover:bg-blue-200 text-sm">
                            Abaikan
                        </a>
                        <a href="process.php?action=delete&id=<?= $rep['product_id'] ?>" onclick="return confirm('Hapus produk ini permanen?')" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 text-sm shadow">
                            Takedown Produk
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
function showTab(tabName) {
    // Sembunyikan semua konten
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    
    // Reset style semua tombol
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('border-primary', 'text-primary');
        btn.classList.add('border-transparent', 'text-gray-500');
    });

    // Tampilkan konten yang dipilih
    document.getElementById(tabName).classList.remove('hidden');
    
    // Highlight tombol yang dipilih
    const activeBtn = document.getElementById('btn-' + tabName);
    activeBtn.classList.remove('border-transparent', 'text-gray-500');
    activeBtn.classList.add('border-primary', 'text-primary');
}
</script>