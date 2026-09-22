<?php
// generate/daftar_litmas_word.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/DocxWriter.php';
require_once __DIR__ . '/../includes/auth.php';

$ids   = array_filter(array_map('intval', explode(',', $_GET['ids'] ?? '')));
$nomor = $_GET['nomor'] ?? '';
$tgl   = $_GET['tgl']   ?? date('Y-m-d');

if (empty($ids)) exit('Tidak ada WBP dipilih.');

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT n.*, p.nama AS penjamin_nama, p.hubungan AS penjamin_hubungan, p.alamat AS penjamin_alamat, p.hp AS penjamin_hp FROM narapidana n LEFT JOIN penjamin p ON p.narapidana_id=n.id AND p.urutan=1 WHERE n.id IN ($placeholders) ORDER BY n.nama");
$stmt->execute($ids);
$napiList = $stmt->fetchAll();

$doc = new DocxWriter();

// Lampiran header
if ($nomor) {
    $doc->addParagraph('Lampiran Permohonan Penelitian Kemasyarakatan', ['align'=>'right','size'=>20,'before'=>0,'after'=>20]);
    $doc->addParagraph('Nomor    : ' . $nomor, ['align'=>'right','size'=>20,'before'=>0,'after'=>20]);
    $doc->addParagraph('Tanggal  : ' . formatTanggal($tgl), ['align'=>'right','size'=>20,'before'=>0,'after'=>60]);
}

// KOP
$doc->addParagraph('KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN REPUBLIK INDONESIA', ['bold'=>true,'align'=>'center','size'=>18,'before'=>0,'after'=>16]);
$doc->addParagraph('DIREKTORAT JENDERAL PEMASYARAKATAN', ['bold'=>true,'align'=>'center','size'=>18,'before'=>0,'after'=>16]);
$doc->addParagraph(getSetting('kanwil'), ['bold'=>true,'align'=>'center','size'=>18,'before'=>0,'after'=>16]);
$doc->addParagraph(getSetting('nama_instansi'), ['bold'=>true,'align'=>'center','size'=>22,'before'=>0,'after'=>16]);
$doc->addParagraph(getSetting('alamat') . ' Kota Tasikmalaya, Kode Pos ' . getSetting('kode_pos') . ' Telp. ' . getSetting('telp'), ['align'=>'center','size'=>16,'before'=>0,'after'=>60]);

// Judul
$doc->addParagraph('DAFTAR NARAPIDANA', ['bold'=>true,'align'=>'center','underline'=>true,'size'=>24,'before'=>60,'after'=>20]);
$doc->addParagraph('UNTUK PERMOHONAN LITMAS', ['bold'=>true,'align'=>'center','underline'=>true,'size'=>22,'before'=>0,'after'=>80]);

// Tabel
$headers = ['No', 'Nama / No. Register', 'Pidana / Pasal', '1/3 MP', '1/2MP', '2/3 MP', 'Ekspirasi', 'Nama dan Alamat Penjamin', 'Keterangan'];
$widths   = [300, 1800, 1800, 900, 900, 900, 900, 1660, 700];

$rows = [];
foreach ($napiList as $i => $n) {
    $lama = [];
    if ($n['lama_pidana_tahun']) $lama[] = $n['lama_pidana_tahun'] . ' Tahun';
    if ($n['lama_pidana_bulan']) $lama[] = $n['lama_pidana_bulan'] . ' Bulan';
    if ($n['lama_pidana_hari'])  $lama[] = $n['lama_pidana_hari']  . ' Hari';

    $penjaminTxt = $n['penjamin_nama'] ?? '';
    if ($n['penjamin_alamat']) $penjaminTxt .= "\n" . $n['penjamin_alamat'];
    if ($n['penjamin_hp'])     $penjaminTxt .= "\n" . $n['penjamin_hp'];

    $rows[] = [
        ['text' => ($i+1) . '.', 'opts' => ['align' => 'center', 'size' => 16]],
        ['text' => $n['nama'] . "\n" . $n['no_register'], 'opts' => ['bold' => true, 'size' => 16]],
        ['text' => implode(' ', $lama) . "\n" . ($n['pasal'] ?? ''), 'opts' => ['size' => 14]],
        ['text' => $n['tanggal_1_3'] ? date('d/m/Y', strtotime($n['tanggal_1_3'])) : '-', 'opts' => ['align' => 'center', 'size' => 16]],
        ['text' => $n['tanggal_1_2'] ? date('d/m/Y', strtotime($n['tanggal_1_2'])) : '-', 'opts' => ['align' => 'center', 'size' => 16]],
        ['text' => $n['tanggal_2_3'] ? date('d/m/Y', strtotime($n['tanggal_2_3'])) : '-', 'opts' => ['align' => 'center', 'size' => 16]],
        ['text' => $n['ekspirasi']   ? date('d/m/Y', strtotime($n['ekspirasi']))   : '-', 'opts' => ['align' => 'center', 'size' => 16]],
        ['text' => $penjaminTxt, 'opts' => ['size' => 14]],
        ['text' => $n['penjamin_hubungan'] ?? '', 'opts' => ['align' => 'center', 'size' => 14]],
    ];
}

$doc->addTable($headers, $rows, ['colWidths' => $widths]);
$doc->addBlankLine(2);

// TTD
$doc->addParagraph('Mengetahui ;', ['align'=>'right','size'=>22,'before'=>60,'after'=>20]);
$doc->addParagraph('K a l a p a s', ['align'=>'right','size'=>22,'before'=>0,'after'=>20]);
$doc->addBlankLine(4);
$doc->addParagraph(getSetting('nama_kalapas'), ['align'=>'right','bold'=>true,'underline'=>true,'size'=>22,'before'=>0,'after'=>20]);

$doc->download('Daftar_LITMAS_' . date('Ymd') . '.docx');
