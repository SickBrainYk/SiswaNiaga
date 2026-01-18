<?php
// File: student/check_status.php
require_once '../config/database.php';

header('Content-Type: application/json');

// Ambil input JSON dari JavaScript
$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['products'])) {
    $ids = $input['products']; // Array ID produk yang ada di dashboard
    
    // Siapkan query untuk mengambil status terbaru
    // Kita gunakan implode untuk membuat list ID (contoh: 1,2,3)
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $sql = "SELECT id, status FROM products WHERE id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($ids);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Kembalikan data status terbaru ke JavaScript
    echo json_encode($results);
} else {
    echo json_encode([]);
}
?>