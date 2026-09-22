<?php
// fix_logo.php — Hapus background hitam dari logo, simpan sebagai PNG transparan
// Jalankan SEKALI via browser: http://localhost/Silapas/fix_logo.php

$srcPath = __DIR__ . '/logo.png'; // input (JPG yang dikopi tadi)
$outPath = __DIR__ . '/logo.png'; // output (timpa jadi PNG transparan)

if (!file_exists($srcPath)) {
    die('❌ File logo.png tidak ditemukan di: ' . $srcPath);
}

// Cek ekstensi GD
if (!function_exists('imagecreatefromjpeg')) {
    die('❌ GD library tidak aktif. Aktifkan extension=gd di php.ini');
}

// Load gambar sumber
$src = @imagecreatefromjpeg($srcPath);
if (!$src) {
    // Coba sebagai PNG jika sudah dikonversi
    $src = @imagecreatefrompng($srcPath);
}
if (!$src) {
    die('❌ Gagal membaca file logo. Pastikan file adalah JPG/PNG yang valid.');
}

$w = imagesx($src);
$h = imagesy($src);

// Buat canvas baru dengan alpha channel
$dst = imagecreatetruecolor($w, $h);
imagealphablending($dst, false);
imagesavealpha($dst, true);

// Isi dengan transparan penuh
$fullyTransparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
imagefill($dst, 0, 0, $fullyTransparent);

// =============================================
// Flood-fill dari 4 sudut untuk tandai background
// Background = pixel hitam/gelap yang terhubung ke sudut
// =============================================
$threshold = 20; // pixel dengan R,G,B < 20 dianggap hitam
$bgMask    = [];

function isBackground($img, $x, $y, $w, $h, $threshold) {
    if ($x < 0 || $x >= $w || $y < 0 || $y >= $h) return false;
    $c = imagecolorat($img, $x, $y);
    $r = ($c >> 16) & 0xFF;
    $g = ($c >>  8) & 0xFF;
    $b =  $c        & 0xFF;
    return ($r < $threshold && $g < $threshold && $b < $threshold);
}

// Stack-based flood fill agar tidak stackoverflow
function floodMarkBg($img, $startX, $startY, $w, $h, $threshold, &$bgMask) {
    $stack = [[$startX, $startY]];
    while (!empty($stack)) {
        [$x, $y] = array_pop($stack);
        if ($x < 0 || $x >= $w || $y < 0 || $y >= $h) continue;
        if (isset($bgMask[$y][$x])) continue;
        if (!isBackground($img, $x, $y, $w, $h, $threshold)) continue;
        $bgMask[$y][$x] = true;
        $stack[] = [$x+1, $y];
        $stack[] = [$x-1, $y];
        $stack[] = [$x, $y+1];
        $stack[] = [$x, $y-1];
    }
}

// Flood fill dari 4 pojok gambar
floodMarkBg($src, 0,    0,    $w, $h, $threshold, $bgMask);
floodMarkBg($src, $w-1, 0,    $w, $h, $threshold, $bgMask);
floodMarkBg($src, 0,    $h-1, $w, $h, $threshold, $bgMask);
floodMarkBg($src, $w-1, $h-1, $w, $h, $threshold, $bgMask);

// =============================================
// Salin pixel — background jadi transparan
// =============================================
for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        if (isset($bgMask[$y][$x])) {
            // Background → transparan
            imagesetpixel($dst, $x, $y, $fullyTransparent);
        } else {
            // Logo → salin warna asli
            $c    = imagecolorat($src, $x, $y);
            $r    = ($c >> 16) & 0xFF;
            $g    = ($c >>  8) & 0xFF;
            $b    =  $c        & 0xFF;
            $pixel = imagecolorallocatealpha($dst, $r, $g, $b, 0);
            imagesetpixel($dst, $x, $y, $pixel);
        }
    }
}

// Simpan sebagai PNG (mendukung transparansi)
imagepng($dst, $outPath, 9);
imagedestroy($src);
imagedestroy($dst);

echo '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Fix Logo</title>
    <style>
        body { font-family: Arial, sans-serif; background: #0B2545; color: #fff; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .box { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 20px; padding: 48px; text-align: center; max-width: 500px; }
        h2 { color: #C9A227; margin-bottom: 12px; }
        .preview { background: repeating-conic-gradient(#555 0% 25%, #333 0% 50%) 0 0 / 20px 20px; border-radius: 12px; padding: 20px; margin: 24px auto; display: inline-block; }
        img { width: 120px; height: 120px; object-fit: contain; display: block; }
        a { display: inline-block; margin-top: 24px; padding: 12px 28px; background: #C9A227; color: #0B2545; font-weight: 700; border-radius: 8px; text-decoration: none; }
        a:hover { background: #E8C547; }
    </style>
</head>
<body>
<div class="box">
    <h2>✅ Logo Berhasil Diperbaiki!</h2>
    <p>Background hitam sudah dihapus. Preview logo (kotak kotak = transparan):</p>
    <div class="preview">
        <img src="/Silapas/logo.png?v=' . time() . '" alt="Logo Preview">
    </div>
    <p style="color:rgba(255,255,255,0.6);font-size:13px;">File disimpan: <code>logo.png</code></p>
    <a href="/Silapas/login.php">→ Lihat di Halaman Login</a>
</div>
</body>
</html>';
