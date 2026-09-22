<?php
// generate/undangan_word.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/DocxWriter.php';
require_once __DIR__ . '/../includes/auth.php';

$sidangId = (int)($_GET['sidang_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM sidang_tpp WHERE id=?");
$stmt->execute([$sidangId]);
$s = $stmt->fetch();
if (!$s) { exit('Sidang tidak ditemukan.'); }

$anggotaTPP = ['Andi Wahyu Suwardi','Asep Jatnika','Gun Gun Setiawan','Yadi Suryaman','Agus Herianto','Ayep Iwan Suryawan','Arief Setyo Budiarto','Agus Habibulloh','Wali Pemasyarakatan'];

$doc = new DocxWriter();

// KOP
$doc->addParagraph('KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN R.I.', ['bold'=>true,'align'=>'center','size'=>20,'before'=>0,'after'=>20]);
$doc->addParagraph('DIREKTORAT JENDERAL PEMASYARAKATAN', ['bold'=>true,'align'=>'center','size'=>20,'before'=>0,'after'=>20]);
$doc->addParagraph(getSetting('kanwil'), ['bold'=>true,'align'=>'center','size'=>20,'before'=>0,'after'=>20]);
$doc->addParagraph(getSetting('nama_instansi'), ['bold'=>true,'align'=>'center','size'=>24,'before'=>0,'after'=>20]);
$doc->addParagraph(getSetting('alamat') . ' Kota Tasikmalaya', ['align'=>'center','size'=>18,'before'=>0,'after'=>20]);
$doc->addParagraph('Telepon  ' . getSetting('telp') . ', Faksimili ' . getSetting('fax',getSetting('telp')) . ', Email : ' . getSetting('email'), ['align'=>'center','size'=>18,'before'=>0,'after'=>60]);

// Header surat
$doc->addParagraph('Nomor     : ' . $s['nomor_surat'] . "\t\t\t" . formatTanggal($s['tanggal_surat']), ['size'=>22,'before'=>60,'after'=>40]);
$doc->addParagraph('Lampiran  : ' . $s['lampiran'], ['size'=>22,'before'=>0,'after'=>40]);
$doc->addParagraph('Perihal   : ' . $s['perihal'], ['size'=>22,'before'=>0,'after'=>80]);

// Kepada
$doc->addParagraph('Kepada.', ['size'=>22,'before'=>60,'after'=>40]);
foreach ($anggotaTPP as $i => $a) {
    $doc->addParagraph('     ' . ($i+1) . '. ' . $a, ['size'=>22,'before'=>0,'after'=>20]);
}
$doc->addParagraph('di', ['size'=>22,'before'=>40,'after'=>20]);
$doc->addParagraph('Tasikmalaya', ['size'=>22,'before'=>0,'after'=>80]);

// Isi
$doc->addParagraph('     Dengan Hormat kami sampaikan bahwa kami mengajukan permintaan pembuatan Penelitian Kemasyarakatan (LITMAS) guna usulan Pembebasan Bersyarat / Cuti Bersyarat pada Lembaga Pemasyarakatan Kelas IIB Tasikmalaya (daftar nama terlampir).', ['size'=>22,'before'=>60,'after'=>60]);
$doc->addParagraph('     Sehubungan dengan hal tersebut kami mengundang saudara untuk dapat hadir pada acara sidang Tim Pengamat Pemasyarakatan yang akan dilaksanakan pada :', ['size'=>22,'before'=>0,'after'=>60]);

$doc->addParagraph('Hari       : ' . $s['hari'], ['size'=>22,'before'=>0,'after'=>40,'indent'=>420]);
$doc->addParagraph('Tanggal    : ' . formatTanggal($s['tanggal_sidang']), ['size'=>22,'before'=>0,'after'=>40,'indent'=>420]);
$doc->addParagraph('Pukul      : ' . $s['pukul_mulai'] . ' wib s/d ' . $s['pukul_selesai'], ['size'=>22,'before'=>0,'after'=>40,'indent'=>420]);
$doc->addParagraph('Materi     : ' . $s['materi'], ['size'=>22,'before'=>0,'after'=>40,'indent'=>420]);
$doc->addParagraph('Tempat     : ' . $s['tempat'], ['size'=>22,'before'=>0,'after'=>80,'indent'=>420]);

$doc->addParagraph('     Demikian undangan ini kami sampaikan, mohon kiranya Saudara dapat hadir tepat pada waktunya. Atas perhatiannya diucapkan terima kasih.', ['size'=>22,'before'=>0,'after'=>180]);

// TTD
$doc->addTable(
    ['Mengetahui;', 'Ketua'],
    [['Kalapas', $s['ketua_tpp']]],
    ['colWidths' => [4680, 4680]]
);
$doc->addBlankLine(4);
$doc->addTable(
    [getSetting('nama_kalapas'), ''],
    [['NIP. ' . getSetting('nip_kalapas'), '']],
    ['colWidths' => [4680, 4680]]
);

$doc->download('Undangan_Sidang_TPP_' . date('Ymd', strtotime($s['tanggal_sidang'])) . '.docx');
