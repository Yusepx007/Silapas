<?php
// File sementara untuk cek status ZipArchive — hapus setelah selesai
$zip = class_exists('ZipArchive');
$gd  = function_exists('imagecreatefromjpeg');

header('Content-Type: text/html; charset=utf-8');
echo '<style>body{font-family:Arial;padding:30px;background:#0B2545;color:#fff;}
.ok{color:#2ecc71;font-weight:bold;} .err{color:#e74c3c;font-weight:bold;}
table{border-collapse:collapse;margin-top:20px;}
td,th{padding:10px 20px;border:1px solid rgba(255,255,255,0.2);text-align:left;}
th{background:rgba(255,255,255,0.1);}
</style>';
echo '<h2>🔍 Cek Ekstensi PHP (Apache)</h2>';
echo '<table>';
echo '<tr><th>Ekstensi</th><th>Status</th><th>Keterangan</th></tr>';
echo '<tr><td>ZipArchive</td><td>' . ($zip ? '<span class="ok">✅ AKTIF</span>' : '<span class="err">❌ TIDAK AKTIF</span>') . '</td><td>Dibutuhkan untuk generate Word (.docx)</td></tr>';
echo '<tr><td>GD (imagecreatefromjpeg)</td><td>' . ($gd ? '<span class="ok">✅ AKTIF</span>' : '<span class="err">❌ TIDAK AKTIF</span>') . '</td><td>Dibutuhkan untuk fix logo</td></tr>';
echo '<tr><td>PHP Version</td><td><span class="ok">' . PHP_VERSION . '</span></td><td>-</td></tr>';
echo '<tr><td>php.ini location</td><td colspan="2"><code>' . php_ini_loaded_file() . '</code></td></tr>';
echo '</table>';

if (!$zip) {
    echo '<div style="margin-top:24px;padding:20px;background:rgba(231,76,60,0.2);border:1px solid #e74c3c;border-radius:8px;">';
    echo '<b>❌ ZipArchive belum aktif!</b><br><br>';
    echo 'Langkah perbaikan:<br>';
    echo '1. Buka <code>' . php_ini_loaded_file() . '</code><br>';
    echo '2. Cari <code>;extension=zip</code> → hapus titik koma<br>';
    echo '3. <b>Restart Apache dari XAMPP Control Panel</b>';
    echo '</div>';
} else {
    echo '<div style="margin-top:24px;padding:20px;background:rgba(46,204,113,0.2);border:1px solid #2ecc71;border-radius:8px;">';
    echo '<b>✅ Semua OK! Download surat Word sudah bisa digunakan.</b>';
    echo '</div>';
}
