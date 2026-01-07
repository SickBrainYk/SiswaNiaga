<?php
require_once '../config/database.php';
require_once '../config/functions.php';
checkRole(['student']);
require_once '../layout/header.php';

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$products = $stmt->fetchAll();
?>

<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Karyaku</h1>
        <a href="product_form.php" class="bg-primary text-white px-4 py-2 rounded">+ Upload Karya</a>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="py-2 px-4 border-b">Foto</th>
                    <th class="py-2 px-4 border-b">Judul</th>
                    <th class="py-2 px-4 border-b">Harga</th>
                    <th class="py-2 px-4 border-b">Status</th>
                    <th class="py-2 px-4 border-b">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($products as $p): ?>
                <tr>
                    <td class="py-2 px-4 border-b"><img src="../uploads/<?= $p['image'] ?>" class="w-16 h-16 object-cover rounded"></td>
                    <td class="py-2 px-4 border-b"><?= $p['title'] ?></td>
                    <td class="py-2 px-4 border-b">Rp <?= number_format($p['price']) ?></td>
                    <td class="py-2 px-4 border-b">
                        <?php 
                        $color = $p['status'] == 'active' ? 'green' : ($p['status'] == 'rejected' ? 'red' : 'yellow');
                        echo "<span class='bg-{$color}-100 text-{$color}-800 px-2 py-1 rounded text-xs uppercase font-bold'>{$p['status']}</span>";
                        if($p['status'] == 'rejected') echo "<br><span class='text-xs text-red-500'>Note: {$p['reject_reason']}</span>";
                        ?>
                    </td>
                    <td class="py-2 px-4 border-b">
                        <a href="product_form.php?id=<?= $p['id'] ?>" class="text-blue-600 hover:underline">Edit</a> |
                        <a href="delete_product.php?id=<?= $p['id'] ?>" onclick="return confirm('Hapus?')" class="text-red-600 hover:underline">Hapus</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>