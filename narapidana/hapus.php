<?php
// narapidana/hapus.php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { redirect(BASE_URL . '/narapidana/index.php'); }

$stmt = $pdo->prepare("SELECT * FROM narapidana WHERE id = ?");
$stmt->execute([$id]);
$napi = $stmt->fetch();

if (!$napi) {
    setFlash('danger', 'Data tidak ditemukan.');
    redirect(BASE_URL . '/narapidana/index.php');
}

// Hapus foto jika ada
if ($napi['foto'] && file_exists(UPLOAD_PATH . $napi['foto'])) {
    unlink(UPLOAD_PATH . $napi['foto']);
}

// Hapus data (cascade ke penjamin, riwayat, dll)
$pdo->prepare("DELETE FROM narapidana WHERE id = ?")->execute([$id]);

setFlash('success', 'Data WBP ' . $napi['nama'] . ' berhasil dihapus.');
redirect(BASE_URL . '/narapidana/index.php');
