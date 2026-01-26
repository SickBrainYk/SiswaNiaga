<?php
require_once '../config/database.php';
require_once '../config/functions.php';
checkRole(['student', 'admin']); 
require_once '../layout/header.php';

$categories = getCategories();
$id = $_GET['id'] ?? null;
$product = null;
$showModal = false; 
$adminData = null; 

// 1. AMBIL DATA PRODUK (JIKA EDIT)
if ($id) {
    $sql = "SELECT * FROM products WHERE id = ?";
    if($_SESSION['role'] == 'student') $sql .= " AND user_id = " . $_SESSION['user_id'];
    elseif($_SESSION['role'] == 'admin') $sql .= " AND school_id = " . $_SESSION['school_id'];
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if(!$product) die("Akses ditolak atau produk tidak ditemukan.");
}

// --- FUNGSI KOMPRESI GAMBAR OTOMATIS ---
function compressImage($source, $destination, $maxSizeKB) {
    // 1. Ambil Info Gambar
    $imgInfo = getimagesize($source);
    $mime = $imgInfo['mime'];
    
    // 2. Buat Image Resource berdasarkan tipe file
    switch($mime){
        case 'image/jpeg': $image = imagecreatefromjpeg($source); break;
        case 'image/png':  $image = imagecreatefrompng($source); break;
        case 'image/gif':  $image = imagecreatefromgif($source); break;
        default: return false; // Tipe tidak didukung
    }

    // 3. Handle Transparansi (Khusus PNG/GIF jika diubah ke JPG background jadi hitam, kita ubah jadi putih)
    if ($mime == 'image/png' || $mime == 'image/gif') {
        $bg = imagecreatetruecolor(imagesx($image), imagesy($image));
        $white = imagecolorallocate($bg, 255, 255, 255);
        imagefill($bg, 0, 0, $white);
        imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        $image = $bg;
    }

    // 4. Logika Kompresi (Looping quality sampai file size < Max Size)
    $quality = 90; // Mulai dari kualitas 90%
    $step = 5;     // Turun 5% setiap loop
    $maxSizeBytes = $maxSizeKB * 1024; // Konversi KB ke Bytes

    do {
        // Simpan gambar ke tujuan dengan kualitas saat ini
        imagejpeg($image, $destination, $quality);
        
        // Cek ukuran file hasil
        $fileSize = filesize($destination);
        
        // Kurangi kualitas untuk iterasi berikutnya
        $quality -= $step;

    } while ($fileSize > $maxSizeBytes && $quality >= 10); // Stop jika sudah target atau kualitas terlalu rendah (10%)

    // Bersihkan memori
    imagedestroy($image);
    
    return true;
}

// 2. LOGIC SIMPAN DATA (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category = sanitize($_POST['category']);
    $title = sanitize($_POST['title']);
    $desc = sanitize($_POST['description']);
    $price = $_POST['price'];
    
    // Siapkan variabel gambar (Ambil dari DB jika ada)
    $imgUtama  = $product['image'] ?? '';
    $imgKedua  = $product['image1'] ?? ''; 
    $imgKetiga = $product['image2'] ?? '';

    // --- VALIDASI WAJIB 3 GAMBAR ---
    if (empty($_FILES['image']['name']) && empty($imgUtama)) {
        $error = "Foto Utama wajib diisi!";
    }
    elseif (empty($_FILES['image1']['name']) && empty($imgKedua)) {
        $error = "Foto ke-2 wajib diisi!";
    }
    elseif (empty($_FILES['image2']['name']) && empty($imgKetiga)) {
        $error = "Foto ke-3 wajib diisi!";
    }

    // --- PROSES UPLOAD DENGAN KOMPRESI ---
    if (!isset($error)) {
        
        function processUploadAndCompress($inputName, $currentImgName) {
            if (!empty($_FILES[$inputName]['name'])) {
                
                $tmpName = $_FILES[$inputName]['tmp_name'];
                $ext = 'jpg'; // Kita paksa jadi JPG agar kompresi maksimal
                $newFileName = uniqid() . '_' . time() . '.' . $ext;
                $targetDir = "../uploads/";
                $targetFile = $targetDir . $newFileName;

                // Jalankan Kompresi (Max 250KB)
                if (compressImage($tmpName, $targetFile, 250)) {
                    // Jika sukses upload baru, hapus gambar lama
                    if ($currentImgName && file_exists("../uploads/" . $currentImgName)) {
                        unlink("../uploads/" . $currentImgName);
                    }
                    return $newFileName;
                } else {
                    return ['error' => "Gagal mengompres gambar. Pastikan format JPG/PNG."];
                }
            }
            return $currentImgName; 
        }

        // 1. Proses Image Utama
        $res1 = processUploadAndCompress('image', $imgUtama);
        if (is_array($res1)) $error = "Foto 1: " . $res1['error'];
        else $imgUtama = $res1;

        // 2. Proses Image 1
        if (!isset($error)) {
            $res2 = processUploadAndCompress('image1', $imgKedua);
            if (is_array($res2)) $error = "Foto 2: " . $res2['error'];
            else $imgKedua = $res2;
        }

        // 3. Proses Image 2
        if (!isset($error)) {
            $res3 = processUploadAndCompress('image2', $imgKetiga);
            if (is_array($res3)) $error = "Foto 3: " . $res3['error'];
            else $imgKetiga = $res3;
        }
    }

    // --- SIMPAN KE DATABASE ---
    if (!isset($error)) {
        if ($id) {
            // UPDATE
            $sql = "UPDATE products SET category=?, title=?, description=?, price=?, image=?, image1=?, image2=?, status='pending' WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$category, $title, $desc, $price, $imgUtama, $imgKedua, $imgKetiga, $id]);
        } else {
            // INSERT
            $sql = "INSERT INTO products (user_id, school_id, category, title, description, price, image, image1, image2) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_SESSION['user_id'], $_SESSION['school_id'], $category, $title, $desc, $price, $imgUtama, $imgKedua, $imgKetiga]);
        }
        
        // Ambil Data Admin untuk Modal Notifikasi
        $school_id = $_SESSION['school_id'];
        $stmtAdmin = $pdo->prepare("SELECT name, phone FROM users WHERE school_id = ? AND role = 'admin' LIMIT 1");
        $stmtAdmin->execute([$school_id]);
        $adminData = $stmtAdmin->fetch();

        $showModal = true;
    }
}
?>

<div class="max-w-3xl mx-auto px-4 py-8 bg-white shadow-lg mt-10 rounded-xl border border-gray-100">
    <div class="border-b pb-4 mb-6">
        <h2 class="text-2xl font-bold text-gray-800"><?= $id ? 'Edit' : 'Upload' ?> Karya</h2>
        <p class="text-sm text-gray-500">Gambar akan otomatis dikompres agar ringan (Max 250KB).</p>
    </div>

    <?php if(isset($error)) echo "<div class='bg-red-50 text-red-500 p-3 rounded mb-4 text-sm font-bold border border-red-200'>$error</div>"; ?>
    
    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Kategori Karya</label>
                    <select name="category" class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach($categories as $key => $label): ?>
                            <option value="<?= $key ?>" <?= (isset($product['category']) && $product['category'] == $key) ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Judul Karya</label>
                    <input type="text" name="title" value="<?= $product['title'] ?? '' ?>" class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition" placeholder="Contoh: Keripik Pisang" required>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Harga (Rp)</label>
                    <input type="number" name="price" value="<?= $product['price'] ?? '' ?>" class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition" placeholder="Contoh: 15000" required>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Deskripsi Lengkap</label>
                <textarea name="description" rows="9" class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition h-full" placeholder="Jelaskan keunggulan produkmu, bahan yang digunakan, dll..." required><?= $product['description'] ?? '' ?></textarea>
            </div>
        </div>

        <hr>

        <div>
            <label class="block text-sm font-bold text-gray-800 mb-3">Foto Produk (Wajib 3 Foto)</label>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <div class="border-2 border-dashed border-gray-300 p-3 rounded-xl hover:border-primary transition bg-gray-50 text-center relative">
                    <p class="text-xs font-bold text-gray-600 mb-2 bg-white inline-block px-2 py-1 rounded shadow-sm">Foto Utama</p>
                    <div class="w-full h-32 mb-2 bg-gray-200 rounded-lg flex items-center justify-center overflow-hidden">
                        <img id="prev-img" src="<?= !empty($product['image']) ? '../uploads/'.$product['image'] : '' ?>" 
                             class="w-full h-full object-cover <?= !empty($product['image']) ? '' : 'hidden' ?>">
                        <span id="ph-img" class="text-gray-400 text-xs <?= !empty($product['image']) ? 'hidden' : '' ?>">Preview Foto</span>
                    </div>
                    <input type="file" name="image" onchange="previewFile(this, 'prev-img', 'ph-img')" 
                           class="w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-primary file:text-white hover:file:bg-teal-700" 
                           <?= (!empty($product['image'])) ? '' : 'required' ?>>
                </div>

                <div class="border-2 border-dashed border-gray-300 p-3 rounded-xl hover:border-primary transition bg-gray-50 text-center relative">
                    <p class="text-xs font-bold text-gray-600 mb-2 bg-white inline-block px-2 py-1 rounded shadow-sm">Foto Samping</p>
                    <div class="w-full h-32 mb-2 bg-gray-200 rounded-lg flex items-center justify-center overflow-hidden">
                        <img id="prev-img1" src="<?= !empty($product['image1']) ? '../uploads/'.$product['image1'] : '' ?>" 
                             class="w-full h-full object-cover <?= !empty($product['image1']) ? '' : 'hidden' ?>">
                        <span id="ph-img1" class="text-gray-400 text-xs <?= !empty($product['image1']) ? 'hidden' : '' ?>">Preview Foto</span>
                    </div>
                    <input type="file" name="image1" onchange="previewFile(this, 'prev-img1', 'ph-img1')" 
                           class="w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-primary file:text-white hover:file:bg-teal-700" 
                           <?= (!empty($product['image1'])) ? '' : 'required' ?>>
                </div>

                <div class="border-2 border-dashed border-gray-300 p-3 rounded-xl hover:border-primary transition bg-gray-50 text-center relative">
                    <p class="text-xs font-bold text-gray-600 mb-2 bg-white inline-block px-2 py-1 rounded shadow-sm">Foto Detail</p>
                    <div class="w-full h-32 mb-2 bg-gray-200 rounded-lg flex items-center justify-center overflow-hidden">
                        <img id="prev-img2" src="<?= !empty($product['image2']) ? '../uploads/'.$product['image2'] : '' ?>" 
                             class="w-full h-full object-cover <?= !empty($product['image2']) ? '' : 'hidden' ?>">
                        <span id="ph-img2" class="text-gray-400 text-xs <?= !empty($product['image2']) ? 'hidden' : '' ?>">Preview Foto</span>
                    </div>
                    <input type="file" name="image2" onchange="previewFile(this, 'prev-img2', 'ph-img2')" 
                           class="w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-primary file:text-white hover:file:bg-teal-700" 
                           <?= (!empty($product['image2'])) ? '' : 'required' ?>>
                </div>

            </div>
            <p class="text-xs text-gray-400 mt-2">* Gambar akan otomatis dikompres sistem jika terlalu besar.</p>
        </div>

        <div class="pt-4">
            <button type="submit" class="w-full bg-primary text-white font-bold py-3.5 rounded-lg hover:bg-teal-700 transition shadow-md flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                </svg>
                Simpan & Ajukan Karya
            </button>
        </div>
    </form>
</div>

<script>
function previewFile(input, imgId, placeholderId) {
    const preview = document.getElementById(imgId);
    const placeholder = document.getElementById(placeholderId);
    const file = input.files[0];

    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
        }
        reader.readAsDataURL(file);
    }
}
</script>

<?php if($showModal): ?>
<div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm animate-fade-in">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8 text-center">
        
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
            <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>

        <h3 class="text-2xl font-bold text-gray-800 mb-2">Upload Berhasil!</h3>
        <p class="text-gray-600 mb-6">
            Karyamu sudah tersimpan. Silakan temui atau hubungi Admin Sekolah untuk persetujuan.
        </p>

        <?php if($adminData): ?>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-5 mb-6">
                <p class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">Nomor Admin Sekolah</p>
                <p class="text-3xl font-extrabold text-blue-800 tracking-wide select-all">
                    <?= $adminData['phone'] ?>
                </p>
                <p class="text-sm text-gray-600 mt-1 font-medium">(<?= $adminData['name'] ?>)</p>
            </div>
        <?php endif; ?>

        <a href="dashboard.php" class="block w-full bg-gray-800 text-white font-bold py-3 rounded-lg hover:bg-gray-900 transition">
            OK, Kembali ke Dashboard
        </a>

    </div>
</div>

<style>
    @keyframes fade-in {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
    .animate-fade-in {
        animation: fade-in 0.2s ease-out forwards;
    }
    .select-all { user-select: all; }
</style>
<?php endif; ?>