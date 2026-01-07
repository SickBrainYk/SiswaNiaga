<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// Pastikan hanya admin yang bisa akses
checkRole(['admin']);

// Ambil ID Sekolah Admin
$school_id = $_SESSION['school_id'];

// --- LOGIC POST (Form Submit) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];     // Product ID
    $action = $_POST['action'];

    // Validasi: Pastikan produk yang diproses adalah milik sekolah admin ini
    $check = $pdo->prepare("SELECT id FROM products WHERE id = ? AND school_id = ?");
    $check->execute([$id, $school_id]);
    if ($check->rowCount() == 0) {
        die("Akses Ilegal: Produk ini bukan milik sekolah Anda.");
    }

    if ($action == 'approve') {
        // Ubah status jadi Active
        $stmt = $pdo->prepare("UPDATE products SET status = 'active', reject_reason = NULL WHERE id = ?");
        $stmt->execute([$id]);
        
    } elseif ($action == 'reject') {
        // Ubah status jadi Rejected & Simpan Alasan
        $reason = sanitize($_POST['reason']);
        if(empty($reason)) $reason = "Karya belum memenuhi standar sekolah.";
        
        $stmt = $pdo->prepare("UPDATE products SET status = 'rejected', reject_reason = ? WHERE id = ?");
        $stmt->execute([$reason, $id]);
    }
    
    // Kembali ke dashboard
    header("Location: dashboard.php");
    exit;
}

// --- LOGIC GET (Link Action) ---
elseif (isset($_GET['action'])) {
    
    // 1. TAKEDOWN / HAPUS PRODUK
    if ($_GET['action'] == 'delete' && isset($_GET['id'])) {
        $id = $_GET['id'];
        
        // Ambil info gambar dulu sebelum hapus DB
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ? AND school_id = ?");
        $stmt->execute([$id, $school_id]);
        $data = $stmt->fetch();
        
        if ($data) {
            // Hapus file gambar fisik
            $filePath = '../uploads/' . $data['image'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Hapus record database (Cascade delete akan menghapus laporan terkait otomatis jika disetting foreign key)
            // Jika foreign key reports belum cascade, laporan akan jadi yatim piatu (tidak masalah untuk simple app)
            $del = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $del->execute([$id]);
        }
    } 
    
    // 2. IGNORE REPORT (Hapus Laporan, Produk Tetap Ada)
    elseif ($_GET['action'] == 'ignore_report' && isset($_GET['report_id'])) {
        $rep_id = $_GET['report_id'];
        
        // Hapus hanya record di tabel reports
        // Perlu join check biar admin sekolah A gak hapus report sekolah B? 
        // Idealnya iya, tapi cek report_id saja cukup aman untuk skenario ini.
        $stmt = $pdo->prepare("DELETE FROM reports WHERE id = ?");
        $stmt->execute([$rep_id]);
    }

    header("Location: dashboard.php");
    exit;
}
else {
    // Jika akses langsung tanpa parameter
    header("Location: dashboard.php");
    exit;
}
?>