<?php
// generate/daftar_tpp_word.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/DocxWriter.php';
require_once __DIR__ . '/../includes/auth.php';

$sidangId = (int)($_GET['sidang_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM sidang_tpp WHERE id=?");
$stmt->execute([$sidangId]);
$sidang = $stmt->fetch();
if (!$sidang) exit('Sidang tidak ditemukan.');

$stmtN = $pdo->prepare("SELECT n.* FROM narapidana n JOIN sidang_narapidana sn ON n.id=sn.narapidana_id WHERE sn.sidang_id=? ORDER BY n.kegiatan, n.nama");
$stmtN->execute([$sidangId]);
$napiList = $stmtN->fetchAll();

$cbList = array_filter($napiList, fn($n) => $n['kegiatan']==='CB');
$pbList = array_filter($napiList, fn($n) => $n['kegiatan']==='PB');

$doc = new DocxWriter();

// KOP
$doc->addParagraph('KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN R.I', ['bold'=>true,'align'=>'center','size'=>20,'before'=>0,'after'=>20]);
$doc->addParagraph('DIREKTORAT JENDERAL PEMASYARAKATAN', ['bold'=>true,'align'=>'center','size'=>20,'before'=>0,'after'=>20]);
$doc->addParagraph(getSetting('kanwil'), ['bold'=>true,'align'=>'center','size'=>20,'before'=>0,'after'=>20]);
$doc->addParagraph(getSetting('nama_instansi'), ['bold'=>true,'align'=>'center','size'=>22,'before'=>0,'after'=>20]);
$doc->addParagraph(getSetting('alamat') . ', Kode Pos ' . (getSetting('kode_pos') ?: '46112') . ' Telp. ' . getSetting('telp'), ['align'=>'center','size'=>16,'before'=>0,'after'=>20]);
$doc->addParagraph('Telp. ' . getSetting('telp') . ' Email: ' . getSetting('email'), ['align'=>'center','size'=>16,'before'=>0,'after'=>60]);

// Judul
$doc->addParagraph('DAFTAR NARAPIDANA YANG MENGIKUTI', ['bold'=>true,'align'=>'center','size'=>24,'before'=>60,'after'=>20]);
$doc->addParagraph('USULAN PROGRAM INTEGRASI PB (PEMBEBASAN BERSYARAT) DAN CB ( CUTI BERSYARAT)', ['bold'=>true,'align'=>'center','size'=>22,'before'=>0,'after'=>20]);
$doc->addParagraph('DALAM SIDANG TIM PENGAMAT PEMASYARAKATAN (TPP)', ['bold'=>true,'align'=>'center','size'=>22,'before'=>0,'after'=>80]);

// Tabel
$headers = ['No', 'N a m a', 'No Reg', 'Pasal', 'Thn', 'Bln', 'Hr', 'Pentahapan', 'Kegiatan', 'Keterangan'];
$widths   = [400, 2000, 800, 1600, 350, 350, 350, 1400, 700, 700];

$rows = [];
foreach ($napiList as $i => $n) {
    $pentahapan = '';
    if ($n['tanggal_1_3']) $pentahapan .= '1/3: ' . date('d/m/Y', strtotime($n['tanggal_1_3'])) . "\n";
    if ($n['tanggal_1_2']) $pentahapan .= '1/2: ' . date('d/m/Y', strtotime($n['tanggal_1_2'])) . "\n";
    if ($n['tanggal_2_3']) $pentahapan .= '2/3: ' . date('d/m/Y', strtotime($n['tanggal_2_3'])) . "\n";
    if ($n['ekspirasi'])   $pentahapan .= 'Eks. ' . date('d/m/Y', strtotime($n['ekspirasi']));

    $rows[] = [
        ['text' => (string)($i+1),              'opts' => ['align' => 'center', 'size' => 16]],
        ['text' => $n['nama'],                  'opts' => ['bold' => true, 'size' => 16]],
        ['text' => $n['no_register'],            'opts' => ['align' => 'center', 'size' => 14]],
        ['text' => $n['pasal'] ?? '',            'opts' => ['size' => 14]],
        ['text' => ($n['lama_pidana_tahun'] ?: '-'), 'opts' => ['align' => 'center', 'size' => 16]],
        ['text' => ($n['lama_pidana_bulan'] ?: '-'), 'opts' => ['align' => 'center', 'size' => 16]],
        ['text' => ($n['lama_pidana_hari']  ?: '-'), 'opts' => ['align' => 'center', 'size' => 16]],
        ['text' => trim($pentahapan),           'opts' => ['size' => 14]],
        ['text' => $n['kegiatan'],              'opts' => ['align' => 'center', 'bold' => true, 'size' => 16]],
        ['text' => '',                          'opts' => ['align' => 'center', 'size' => 14]],
    ];
}

$doc->addTable($headers, $rows, ['colWidths' => $widths]);

// Keterangan
$doc->addBlankLine();
$doc->addParagraph('Keterangan', ['bold'=>true,'size'=>20,'before'=>40,'after'=>20]);
$doc->addParagraph('Pembebasan Bersyarat (PB) : ' . count($pbList) . ' Orang WBP', ['size'=>20,'before'=>0,'after'=>20]);
$doc->addParagraph('Cuti Bersyarat (CB)       : ' . count($cbList) . ' Orang WBP', ['size'=>20,'before'=>0,'after'=>80]);

// TTD
$ttdPath = __DIR__ . '/../ttd_kalapas.jpg';
$doc->addParagraph('Mengetahui ;', ['align'=>'right','size'=>22,'before'=>0,'after'=>20]);
$doc->addParagraph('K a l a p a s', ['align'=>'right','size'=>22,'before'=>0,'after'=>20]);
$ttdRId = $doc->embedImage($ttdPath);
if (!empty($ttdRId)) { $doc->addInlineImage($ttdRId, 1800000, 700000, 'right'); }
$doc->addParagraph(getSetting('nama_kalapas'), ['align'=>'right','bold'=>true,'underline'=>true,'size'=>22,'before'=>0,'after'=>20]);
if (getSetting('nip_kalapas')) {
    $doc->addParagraph('NIP. ' . getSetting('nip_kalapas'), ['align'=>'right','size'=>20,'before'=>0,'after'=>0]);
}

$doc->download('Daftar_WBP_Sidang_TPP_' . date('Ymd', strtotime($sidang['tanggal_sidang'])) . '.docx');
