<?php
require_once '../config/database.php';
require_once '../config/functions.php';
checkRole(['superadmin']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $action = $_POST['action'];

    if ($action == 'approve') {
        $stmt = $pdo->prepare("UPDATE products SET status = 'active' WHERE id = ?");
        $stmt->execute([$id]);
    } elseif ($action == 'reject') {
        $reason = sanitize($_POST['reason']);
        if(empty($reason)) $reason = "Karya tidak memenuhi standar."; // Default reason
        $stmt = $pdo->prepare("UPDATE products SET status = 'rejected', reject_reason = ? WHERE id = ?");
        $stmt->execute([$reason, $id]);
    }
} elseif (isset($_GET['action']) && $_GET['action'] == 'delete') {
    // Delete logic (Takedown)
    $id = $_GET['id'];
    // Validasi: Pastikan produk milik sekolah admin ini
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND school_id = ?");
    $stmt->execute([$id, $_SESSION['school_id']]);
}

header("Location: dashboard.php");
?>