<?php
// 1. Mulai session untuk bisa mengakses data session saat ini
session_start();

// 2. Kosongkan semua variabel session ($_SESSION)
$_SESSION = [];

// 3. (Opsional tapi disarankan) Hapus cookie session jika ada
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Hancurkan session di server
session_destroy();

// 5. Redirect kembali ke halaman login
header("Location: login.php");
exit;
?>