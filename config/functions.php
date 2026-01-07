<?php
// 1. Fungsi Sanitasi Input (Mencegah XSS)
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// 2. Fungsi Cek Login & Role
function checkRole($allowed_roles) {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: ../auth/login.php");
        exit;
    }
}

// 3. Logic Upload & Rename Unik
function uploadImage($file, $targetDir = '../uploads/') {
    $fileName = $file['name'];
    $fileTmp  = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileErr  = $file['error'];

    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png'];

    if (!in_array($ext, $allowed)) return ['status' => false, 'msg' => 'Format file harus JPG/PNG'];
    if ($fileSize > 2000000) return ['status' => false, 'msg' => 'Ukuran file maks 2MB'];
    if ($fileErr !== 0) return ['status' => false, 'msg' => 'Error upload'];

    // Rename unik: time_random.ext
    $newFileName = time() . '_' . rand(1000, 9999) . '.' . $ext;
    
    if (move_uploaded_file($fileTmp, $targetDir . $newFileName)) {
        return ['status' => true, 'fileName' => $newFileName];
    }
    return ['status' => false, 'msg' => 'Gagal memindahkan file'];
}

// 4. [BARU] Daftar Kategori Baku
// Key (kiri) untuk disimpan di Database.
// Value (kanan) untuk ditampilkan di Label/UI.
function getCategories() {
    return [
        'Kuliner'   => 'Makanan & Minuman',
        'Kerajinan' => 'Kerajinan Tangan & Kriya',
        'Fashion'   => 'Fashion & Aksesoris',
        'Seni Rupa' => 'Lukisan & Patung',
        'Digital'   => 'Produk Digital',
        'Teknologi' => 'Rekayasa & Teknologi',
        'Lainnya'   => 'Lainnya'
    ];
}
?>