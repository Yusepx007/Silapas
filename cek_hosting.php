<?php
// cek_hosting.php — Diagnostik server hosting
// Upload ke: silapas.free.je/Silapas/cek_hosting.php
// HAPUS file ini setelah selesai cek!

error_reporting(E_ALL);
ini_set('display_errors', 1);

$info = [
    'PHP Version'         => PHP_VERSION,
    'php.ini loaded'      => php_ini_loaded_file(),
    'ZipArchive'          => class_exists('ZipArchive') ? '✅ ADA' : '❌ TIDAK ADA',
    'GD Extension'        => function_exists('imagecreatefromjpeg') ? '✅ ADA' : '❌ TIDAK ADA',
    'crc32 test'          => dechex(crc32('test')),
    'pack() test'         => bin2hex(pack('V', 255)),
    'sys_get_temp_dir'    => sys_get_temp_dir(),
    'temp writable'       => is_writable(sys_get_temp_dir()) ? '✅ Writable' : '❌ Tidak writable',
    'memory_limit'        => ini_get('memory_limit'),
    'max_execution_time'  => ini_get('max_execution_time'),
    'output_buffering'    => ini_get('output_buffering') ? '✅ ON' : '❌ OFF',
    'session.save_path'   => ini_get('session.save_path'),
    'session writable'    => is_writable(ini_get('session.save_path') ?: sys_get_temp_dir()) ? '✅ Writable' : '❌ Tidak writable',
];

// Test buat file ZIP pure PHP
function testPureZip(): string {
    $zip  = '';
    $name = 'test.xml';
    $data = '<?xml version="1.0"?><test>ok</test>';
    $crc  = crc32($data) & 0xFFFFFFFF;
    $size = strlen($data);

    $local  = "\x50\x4b\x03\x04";
    $local .= pack('v', 20) . pack('v', 0) . pack('v', 0);
    $local .= pack('V', 0) . pack('V', $crc) . pack('V', $size) . pack('V', $size);
    $local .= pack('v', strlen($name)) . pack('v', 0) . $name . $data;

    $cd  = "\x50\x4b\x01\x02";
    $cd .= pack('v', 20) . pack('v', 20) . pack('v', 0) . pack('v', 0);
    $cd .= pack('V', 0) . pack('V', $crc) . pack('V', $size) . pack('V', $size);
    $cd .= pack('v', strlen($name)) . pack('v', 0) . pack('v', 0);
    $cd .= pack('v', 0) . pack('v', 0) . pack('V', 0) . pack('V', 0) . $name;

    $eocd  = "\x50\x4b\x05\x06";
    $eocd .= pack('v', 0) . pack('v', 0) . pack('v', 1) . pack('v', 1);
    $eocd .= pack('V', strlen($cd)) . pack('V', strlen($local)) . pack('v', 0);

    return strlen($local . $cd . $eocd) > 0 ? '✅ OK (' . strlen($local . $cd . $eocd) . ' bytes)' : '❌ GAGAL';
}

$info['Pure ZIP test'] = testPureZip();

// Test koneksi database
try {
    require_once __DIR__ . '/config.php';
    $info['Database'] = '✅ Terhubung ke: ' . DB_NAME;
    $info['BASE_URL']  = BASE_URL;
} catch (Throwable $e) {
    $info['Database'] = '❌ ERROR: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Diagnostik Hosting — SILAPAS</title>
<style>
body { font-family: Arial, sans-serif; background: #0B2545; color: #fff; padding: 30px; }
h2 { color: #C9A227; }
table { border-collapse: collapse; width: 100%; max-width: 800px; margin-top: 20px; }
td, th { padding: 10px 16px; border: 1px solid rgba(255,255,255,0.15); }
th { background: rgba(255,255,255,0.1); text-align: left; }
tr:nth-child(even) { background: rgba(255,255,255,0.04); }
.ok { color: #2ecc71; } .err { color: #e74c3c; }
.warn { background: rgba(231,76,60,0.2); padding: 16px; border-radius: 8px; margin-top: 20px; border: 1px solid #e74c3c; }
</style>
</head>
<body>
<h2>🔍 Diagnostik Server Hosting SILAPAS</h2>
<p style="color:rgba(255,255,255,0.5);">⚠️ Hapus file ini setelah selesai digunakan!</p>
<table>
<tr><th>Item</th><th>Nilai</th></tr>
<?php foreach ($info as $k => $v): ?>
<tr>
    <td><?= htmlspecialchars($k) ?></td>
    <td><?= str_contains((string)$v, '✅') ? '<span class="ok">' : (str_contains((string)$v, '❌') ? '<span class="err">' : '<span>') ?>
        <?= htmlspecialchars((string)$v) ?></span></td>
</tr>
<?php endforeach; ?>
</table>
<div class="warn">
    <b>⚠️ Catatan Keamanan:</b> Hapus file <code>cek_hosting.php</code> setelah selesai cek!
</div>
</body>
</html>
