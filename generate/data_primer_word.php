<?php
// generate/data_primer_word.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/DocxWriter.php';
require_once __DIR__ . '/../includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM narapidana WHERE id=?");
$stmt->execute([$id]);
$n = $stmt->fetch();
if (!$n) exit('WBP tidak ditemukan.');

$stmt2 = $pdo->prepare("SELECT * FROM penjamin WHERE narapidana_id=? ORDER BY urutan LIMIT 1");
$stmt2->execute([$id]);
$pj = $stmt2->fetch() ?: [];

$stmt3 = $pdo->prepare("SELECT * FROM riwayat_pembinaan WHERE narapidana_id=? LIMIT 1");
$stmt3->execute([$id]);
$rw = $stmt3->fetch() ?: [];

$lamaPidana = [];
if ($n['lama_pidana_tahun']) $lamaPidana[] = $n['lama_pidana_tahun'] . ' Tahun';
if ($n['lama_pidana_bulan']) $lamaPidana[] = $n['lama_pidana_bulan'] . ' Bulan';
if ($n['lama_pidana_hari'])  $lamaPidana[] = $n['lama_pidana_hari']  . ' Hari';
$lamaStr = implode(' ', $lamaPidana);

$doc = new DocxWriter();

// KOP
$doc->addParagraph('KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN REPUBLIK INDONESIA', ['bold'=>true,'align'=>'center','size'=>18,'before'=>0,'after'=>16]);
$doc->addParagraph('DIREKTORAT JENDERAL PEMASYARAKATAN', ['bold'=>true,'align'=>'center','size'=>18,'before'=>0,'after'=>16]);
$doc->addParagraph(getSetting('kanwil'), ['bold'=>true,'align'=>'center','size'=>18,'before'=>0,'after'=>16]);
$doc->addParagraph(getSetting('nama_instansi'), ['bold'=>true,'align'=>'center','size'=>22,'before'=>0,'after'=>16]);
$doc->addParagraph(getSetting('alamat') . ', Kode Pos ' . getSetting('kode_pos') . ' Telp. ' . getSetting('telp'), ['align'=>'center','size'=>16,'before'=>0,'after'=>16]);
$doc->addParagraph('Laman : ' . getSetting('laman') . ' Pos-el : ' . getSetting('email'), ['align'=>'center','size'=>16,'before'=>0,'after'=>60]);

// Judul
$doc->addParagraph('DATA PRIMER PENELITIAN KEMASYARAKATAN', ['bold'=>true,'align'=>'center','underline'=>true,'size'=>24,'before'=>60,'after'=>20]);
$doc->addParagraph('UNTUK PEMBINAAN DILUAR LEMBAGA PEMASYARAKATAN', ['bold'=>true,'align'=>'center','underline'=>true,'size'=>22,'before'=>0,'after'=>80]);

// Header
$doc->addParagraph('Terhadap      : ' . $n['nama'], ['size'=>22,'before'=>40,'after'=>30]);
$doc->addParagraph('Nomor Register : ' . $n['no_register'], ['size'=>22,'before'=>0,'after'=>30]);
$doc->addParagraph('Perkara       : ' . ($n['perkara'] ?? ''), ['size'=>22,'before'=>0,'after'=>80]);

// IDENTITAS
$doc->addParagraph('I.   IDENTITAS', ['bold'=>true,'underline'=>true,'size'=>22,'before'=>60,'after'=>40]);
$doc->addParagraph('A. KLIEN', ['bold'=>true,'underline'=>true,'size'=>22,'before'=>20,'after'=>40,'indent'=>420]);

$klienRows = [
    ['1. Nama',                     $n['nama']],
    ['2. Tempat /Tanggal Lahir',    ($n['tempat_lahir'] ?? '') . ', ' . formatTanggal($n['tanggal_lahir'] ?? '')],
    ['3. Jenis Kelamin',            $n['jenis_kelamin']==='L' ? 'Laki Laki' : 'Perempuan'],
    ['4. Agama',                    $n['agama'] ?? ''],
    ['5. Pendidikan / Pekerjaan',   ($n['pendidikan'] ?? '') . ' / ' . ($n['pekerjaan'] ?? '')],
    ['6. Suku/Bangsa/WN',           ($n['suku'] ?? '') . '  /  ' . ($n['bangsa_wn'] ?? '')],
    ['7. Status Perkawinan',        $n['status_perkawinan'] ?? ''],
    ['8. Saat ini dibina di',       'LAPAS Kelas IIB Tasikmalaya'],
    ['9. Lama Pidana',              $lamaStr],
    ['10. Putusan PN',              $n['putusan_pn'] ?? ''],
    ['11. Perkara',                 $n['perkara'] ?? ''],
    ['12. Tahapan Pembinaan', ''],
    ['    a. Tgl 1/3 masa pidana',  formatTanggal($n['tanggal_1_3'] ?? '')],
    ['    b. Tgl 1/2 masa pidana',  formatTanggal($n['tanggal_1_2'] ?? '')],
    ['    c. Tgl 2/3 masa pidana',  formatTanggal($n['tanggal_2_3'] ?? '')],
    ['13. Ekspirasi',               formatTanggal($n['ekspirasi'] ?? '')],
    ['14. Expirasi (PB)',           formatTanggal($n['ekspirasi_pb'] ?? $n['tanggal_2_3'] ?? '')],
    ['15. Kegunaan',                'Usulan ' . ($n['kegiatan']==='PB' ? 'Pembebasan Bersyarat ( PB )' : 'Cuti Bersyarat ( CB )')],
];
$doc->addTable(['', '', ''], $klienRows, ['colWidths' => [2400, 300, 6660]]);

if (!empty($pj)) {
    $doc->addBlankLine();
    $doc->addParagraph('B. Penanggung Jawab /Penjamin ( ' . ($pj['hubungan'] ?? '') . ' )', ['bold'=>true,'underline'=>true,'size'=>22,'before'=>40,'after'=>40,'indent'=>420]);
    $pjRows = [
        ['1. Nama',                 $pj['nama']],
        ['2. Tempat / Tanggal Lahir', ($pj['tempat_lahir']??'') . ', ' . formatTanggal($pj['tanggal_lahir']??'')],
        ['3. Agama',                $pj['agama']??''],
        ['4. Suku/bangsa/WN',       ($pj['suku']??'') . ' / ' . ($pj['bangsa_wn']??'')],
        ['5. Pendidikan',           $pj['pendidikan']??''],
        ['6. Pekerjaan',            $pj['pekerjaan']??''],
        ['7. Hubungan Dengan klien',$pj['hubungan']??''],
        ['8. Alamat',               $pj['alamat']??''],
        ['9. HP Penjamin',          $pj['hp']??''],
        ['10. Bentuk Jaminan',      $pj['bentuk_jaminan']??''],
    ];
    $doc->addTable(['', '', ''], $pjRows, ['colWidths' => [2400, 300, 6660]]);
}

// MASALAH
if (!empty($rw)) {
    $doc->addBlankLine();
    $doc->addParagraph('II.   MASALAH YANG DIHADAPI KLIEN', ['bold'=>true,'underline'=>true,'size'=>22,'before'=>60,'after'=>40]);
    $doc->addParagraph('A. Masalah dan Perkembangan selama di LAPAS', ['bold'=>true,'underline'=>true,'size'=>22,'before'=>20,'after'=>40,'indent'=>420]);

    if ($rw['perilaku_pribadi']) {
        $doc->addParagraph('1. Pribadi Klien', ['size'=>22,'before'=>40,'after'=>20,'indent'=>840]);
        $doc->addParagraph($rw['perilaku_pribadi'], ['size'=>22,'before'=>0,'after'=>40,'indent'=>1120]);
    }
    if ($rw['kesehatan']) {
        $doc->addParagraph('2. Kesehatan', ['size'=>22,'before'=>40,'after'=>20,'indent'=>840]);
        $doc->addParagraph($rw['kesehatan'], ['size'=>22,'before'=>0,'after'=>40,'indent'=>1120]);
    }
    if ($rw['kegemaran']) {
        $doc->addParagraph('3. Kegemaran/Hobby', ['size'=>22,'before'=>40,'after'=>20,'indent'=>840]);
        $doc->addParagraph($rw['kegemaran'], ['size'=>22,'before'=>0,'after'=>40,'indent'=>1120]);
    }
    if ($rw['cita_cita']) {
        $doc->addParagraph('4. Cita-cita dan Harapan', ['size'=>22,'before'=>40,'after'=>20,'indent'=>840]);
        $doc->addParagraph($rw['cita_cita'], ['size'=>22,'before'=>0,'after'=>40,'indent'=>1120]);
    }
    if ($rw['pendidikan_ketrampilan']) {
        $doc->addParagraph('5. Pendidikan dan Ketrampilan yang diperoleh selama dalam Lapas', ['size'=>22,'before'=>40,'after'=>20,'indent'=>840]);
        $doc->addParagraph($rw['pendidikan_ketrampilan'], ['size'=>22,'before'=>0,'after'=>40,'indent'=>1120]);
    }
    if ($rw['hubungan_petugas'] || $rw['hubungan_sesama'] || $rw['hubungan_keluarga']) {
        $doc->addParagraph('6. Hubungan Sosial dengan Petugas, Penghuni dan Keluarga', ['size'=>22,'before'=>40,'after'=>20,'indent'=>840]);
        if ($rw['hubungan_petugas']) $doc->addParagraph('a. Hubungan dengan petugas' . "\n" . $rw['hubungan_petugas'], ['size'=>22,'before'=>0,'after'=>30,'indent'=>1120]);
        if ($rw['hubungan_sesama'])  $doc->addParagraph('b. Hubungan dengan sesama penghuni' . "\n" . $rw['hubungan_sesama'], ['size'=>22,'before'=>0,'after'=>30,'indent'=>1120]);
        if ($rw['hubungan_keluarga'])$doc->addParagraph('c. Hubungan dengan Keluarga' . "\n" . $rw['hubungan_keluarga'], ['size'=>22,'before'=>0,'after'=>30,'indent'=>1120]);
    }

    if ($rw['tahap_1_3'] || $rw['tahap_1_2'] || $rw['tahap_2_3']) {
        $doc->addParagraph('B. Data (Record) Pembinaan Klien', ['bold'=>true,'underline'=>true,'size'=>22,'before'=>60,'after'=>40,'indent'=>420]);
        if ($rw['tahap_1_3']) $doc->addParagraph('1. Tahap 1/3 Masa Pidana (Admisi Orentasi)' . "\n" . $rw['tahap_1_3'], ['size'=>22,'before'=>20,'after'=>40,'indent'=>840]);
        if ($rw['tahap_1_2']) $doc->addParagraph('2. Tahap ½ Masa Pidana (Tahap Asimilasi)' . "\n" . $rw['tahap_1_2'], ['size'=>22,'before'=>20,'after'=>40,'indent'=>840]);
        if ($rw['tahap_2_3']) $doc->addParagraph('3. Tahap 2/3 Masa Pidana (Tahap Integrasi)' . "\n" . $rw['tahap_2_3'], ['size'=>22,'before'=>20,'after'=>40,'indent'=>840]);
    }
}

if ($n['tempat_asimilasi']) {
    $doc->addBlankLine();
    $doc->addParagraph('D.   TEMPAT PELAKSANAAN ASIMILASI', ['bold'=>true,'underline'=>true,'size'=>22,'before'=>40,'after'=>20,'indent'=>420]);
    $doc->addParagraph($n['tempat_asimilasi'], ['size'=>22,'before'=>0,'after'=>60,'indent'=>840]);
}

// PENUTUP
$doc->addBlankLine();
$doc->addParagraph('III.   PENUTUP', ['bold'=>true,'underline'=>true,'size'=>22,'before'=>60,'after'=>40]);
$doc->addParagraph('     Demikian risalah (data primer) ini untuk dijadikan bahan pelaksanaan Penelitian Kemasyarakatan (LITMAS) klien yang bersangkutan.', ['size'=>22,'before'=>0,'after'=>200]);

// TTD
$doc->addTable(
    ['Mengetahui ;', 'Tasikmalaya, ' . formatTanggal(date('Y-m-d'))],
    [['Kepala', 'Wali  Pemasyarakatan']],
    ['colWidths' => [4680, 4680]]
);
$doc->addBlankLine(4);
$doc->addTable(
    [getSetting('nama_kalapas'), ($rw['wali_pemasyarakatan'] ?? 'WALI PEMASYARAKATAN')],
    [['', '']],
    ['colWidths' => [4680, 4680]]
);

$doc->download('Data_Primer_LITMAS_' . preg_replace('/[^a-zA-Z0-9]/', '_', $n['nama']) . '_' . date('Ymd') . '.docx');
