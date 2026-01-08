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
    
    // 1. TAKEDOWN / HAPUS PRODUK (FIXED: HAPUS 3 FILE IMAGE)
    if ($_GET['action'] == 'delete' && isset($_GET['id'])) {
        $id = $_GET['id'];
        
        // Ambil info KETIGA gambar dulu sebelum hapus DB
        $stmt = $pdo->prepare("SELECT image, image1, image2 FROM products WHERE id = ? AND school_id = ?");
        $stmt->execute([$id, $school_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            // Daftar kolom yang ingin dicek
            $imageFields = ['image', 'image1', 'image2'];

            // Loop untuk menghapus setiap file gambar yang ada
            foreach ($imageFields as $field) {
                $filename = $data[$field];
                
                // Jika nama file tidak kosong
                if (!empty($filename)) {
                    $filePath = '../uploads/' . $filename;
                    
                    // Jika file fisik ada di server, hapus
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
            }
            
            // Setelah file terhapus, baru hapus record database
            $del = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $del->execute([$id]);
        }
    } 
    
    // 2. IGNORE REPORT (Hapus Laporan, Produk Tetap Ada)
    elseif ($_GET['action'] == 'ignore_report' && isset($_GET['report_id'])) {
        $rep_id = $_GET['report_id'];
        
        // Hapus hanya record di tabel reports
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