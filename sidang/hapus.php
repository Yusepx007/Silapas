<?php
// sidang/hapus.php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    setFlash('danger', 'ID tidak valid.');
    redirect(BASE_URL . '/sidang/index.php');
}
$pdo->prepare("DELETE FROM sidang_tpp WHERE id = ?")->execute([$id]);
setFlash('success', 'Sidang berhasil dihapus.');
redirect(BASE_URL . '/sidang/index.php');
