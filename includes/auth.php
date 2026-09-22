<?php
// includes/auth.php — Pengecekan autentikasi
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Ambil data admin yang login
$adminData = null;
if (isset($pdo)) {
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $adminData = $stmt->fetch();
}
