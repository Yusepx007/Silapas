<?php
// generate/undangan_word.php — Undangan INTEGRASI PB dan CB + Lampiran Daftar WBP
ini_set('display_errors', 0);
error_reporting(E_ALL);

set_exception_handler(function(Throwable $e) {
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code(200);
    die('<div style="font-family:Arial;padding:30px;background:#fff0f0;border:2px solid red;border-radius:8px;margin:20px">
        <h2 style="color:red">&#10060; Error Generate Undangan Integrasi</h2>
        <p><b>' . get_class($e) . ':</b> ' . htmlspecialchars($e->getMessage()) . '</p>
        <p>File: ' . htmlspecialchars($e->getFile()) . ' baris ' . $e->getLine() . '</p>
    </div>');
});

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/DocxWriter.php';
require_once __DIR__ . '/../includes/auth.php';

$sidangId = (int)($_GET['sidang_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM sidang_tpp WHERE id=?");
$stmt->execute([$sidangId]);
$s = $stmt->fetch();
if (!$s) { exit('Sidang tidak ditemukan.'); }

// Ambil daftar narapidana dalam sidang ini
$stmtN = $pdo->prepare("SELECT n.* FROM narapidana n JOIN sidang_narapidana sn ON n.id=sn.narapidana_id WHERE sn.sidang_id=? ORDER BY n.kegiatan, n.nama");
$stmtN->execute([$sidangId]);
$napiList = $stmtN->fetchAll();

$namaWbpPertama = !empty($napiList) ? mb_strtoupper($napiList[0]['nama']) : '..................';

$namaKalapas  = getSetting('nama_kalapas');
$nipKalapas   = getSetting('nip_kalapas');
$namaInstansi = getSetting('nama_instansi');
$kanwil       = getSetting('kanwil');
$alamat       = getSetting('alamat');
$telp         = getSetting('telp');
$fax          = getSetting('fax') ?: $telp;
$email        = getSetting('email');
$logoPath     = __DIR__ . '/../logo_kop.png';
$laman        = getSetting('laman')    ?: 'lapastasikmalaya.kemenkumham.go.id';
$kodePos      = getSetting('kode_pos') ?: '46112';

$ketua = $s['ketua_tpp'] ?? 'ANDI WAHYU SUWARDI';

$anggotaTPP = [
    'Andi Wahyu Suwardi',
    'Asep Jatnika',
    'Gun Gun Setiawan',
    'Yadi Suryaman',
    'Agus Herianto',
    'Ayep Iwan Suryawan',
    'Arief Setyo Budiarto',
    'Agus Habibulloh',
    'Wali Pemasyarakatan'
];

$doc = new DocxWriter();

// ═══════════════════════════════════════════════════
// HALAMAN 1: Undangan Rapat Sidang TPP
// ═══════════════════════════════════════════════════

// KOP Surat dengan logo
$doc->addKopWithLogo($logoPath, $namaInstansi, $kanwil, $alamat, $telp, $fax, $email, $laman, $kodePos);
$doc->addBlankLine();

// Nomor, Lampiran, Perihal — menggunakan tabel 2 kolom (kiri: label+isi, kanan: tanggal)
$tglStr = formatTanggal($s['tanggal_surat'] ?? $s['tanggal_sidang']);
// Nomor/tanggal — no border
$doc->addTable(
    ['', ''],
    [[
        ['text' => 'Nomor       : ' . ($s['nomor_surat'] ?? ''), 'opts' => ['size' => 22]],
        ['text' => $tglStr, 'opts' => ['size' => 22, 'align' => 'right']],
    ]],
    ['colWidths' => [6500, 2860], 'noBorder' => true]
);
$doc->addParagraph('Lampiran    : 1 (satu) halaman', ['size' => 22, 'before' => 0, 'after' => 40]);
$doc->addParagraph('Perihal     : Undangan Rapat Sidang Tim Pengamat Pemasyarakatan (TPP)', ['size' => 22, 'before' => 0, 'after' => 80]);

// Kepada
$doc->addParagraph('Kepada.', ['size' => 22, 'before' => 60, 'after' => 40]);
foreach ($anggotaTPP as $i => $a) {
    $doc->addParagraph('        ' . ($i + 1) . '. ' . $a, ['size' => 22, 'before' => 0, 'after' => 20]);
}
$doc->addParagraph('        di',          ['size' => 22, 'before' => 40, 'after' => 20]);
$doc->addParagraph('        Tasikmalaya', ['size' => 22, 'before' => 0, 'after' => 80]);

// Isi surat
$par1 = '    Dengan Hormat kami sampaikan bahwa kami mengajukan permintaan pembuatan Penelitian Kemasyarakatan (LITMAS) guna usulan Pembebasan Bersyarat / Cuti Bersyarat pada Lembaga Pemasyarakatan Kelas IIB Tasikmalaya a.n ' . $namaWbpPertama . ', Dkk (daftar nama terlampir).';
$doc->addParagraph($par1, ['size' => 22, 'before' => 60, 'after' => 60, 'align' => 'both']);

$par2 = '    Sehubungan dengan hal tersebut kami mengundang saudara untuk dapat hadir pada acara sidang Tim Pengamat Pemasyarakatan yang akan dilaksanakan pada :';
$doc->addParagraph($par2, ['size' => 22, 'before' => 0, 'after' => 60, 'align' => 'both']);

$doc->addParagraph('Hari        : ' . ($s['hari'] ?: formatTanggal($s['tanggal_sidang'], true)), ['size' => 22, 'before' => 0, 'after' => 40, 'indent' => 360]);
$doc->addParagraph('Tanggal     : ' . formatTanggal($s['tanggal_sidang']),                        ['size' => 22, 'before' => 0, 'after' => 40, 'indent' => 360]);
$doc->addParagraph('Pukul       : ' . $s['pukul_mulai'] . ' wib s/d ' . $s['pukul_selesai'],     ['size' => 22, 'before' => 0, 'after' => 40, 'indent' => 360]);
$doc->addParagraph('Materi      : Usulan WBP yang akan mengikuti program Integrasi CB ( Cuti Bersyarat ) dan PB (Pembebasan Bersyarat)', ['size' => 22, 'before' => 0, 'after' => 40, 'indent' => 360]);
$doc->addParagraph('Tempat      : ' . ($s['tempat'] ?: 'Aula Atas Lapas Tasikmalaya'),            ['size' => 22, 'before' => 0, 'after' => 80, 'indent' => 360]);

$par3 = '    Demikian undangan ini kami sampaikan, mohon kiranya Saudara dapat hadir tepat pada waktunya. Atas perhatiannya diucapkan terima kasih.';
$doc->addParagraph($par3, ['size' => 22, 'before' => 0, 'after' => 120, 'align' => 'both']);

// TTD Halaman 1 — 2 kolom no border
$doc->addTable(
    ['Mengetahui ;', 'Ketua'],
    [['K a l a p a s', '']],
    ['colWidths' => [4680, 4680], 'noBorder' => true]
);
$doc->addBlankLine(4);
$doc->addTable(
    [$namaKalapas, $ketua],
    [['NIP. ' . $nipKalapas, '']],
    ['colWidths' => [4680, 4680], 'noBorder' => true]
);

// ═══════════════════════════════════════════════════
// HALAMAN 2+: DAFTAR NARAPIDANA
// ═══════════════════════════════════════════════════
$doc->addPageBreak();

// KOP Lampiran dengan logo
$doc->addKopWithLogo($logoPath, $namaInstansi, $kanwil, $alamat, $telp, $fax, $email, $laman, $kodePos);
$doc->addBlankLine();

// Judul Lampiran
$doc->addParagraph('DAFTAR NARAPIDANA YANG MENGIKUTI',
    ['bold' => true, 'align' => 'center', 'size' => 24, 'before' => 60, 'after' => 20]);
$doc->addParagraph('USULAN PROGRAM INTEGRASI PB (PEMBEBASAN BERSYRAT) DAN CB ( CUTI BERSYARAT)',
    ['bold' => true, 'align' => 'center', 'size' => 22, 'before' => 0, 'after' => 20]);
$doc->addParagraph('DALAM SIDANG TIM PENGAMAT PEMASYARAKATAN (TPP)',
    ['bold' => true, 'align' => 'center', 'size' => 22, 'before' => 0, 'after' => 20]);
$doc->addParagraph('Nomor  : ' . ($s['nomor_surat'] ?? '') . '          Tanggal  : ' . $tglStr,
    ['align' => 'center', 'size' => 20, 'before' => 0, 'after' => 60]);

// Daftar narapidana — plain text (hanya pengantar yg pakai tabel)
$countPB = 0; $countCB = 0;
foreach ($napiList as $i => $n) {
    $kegiatan = $n['kegiatan'] ?? 'PB';
    if ($kegiatan === 'PB') $countPB++;
    if ($kegiatan === 'CB') $countCB++;
    $vonis = [];
    if ($n['lama_pidana_tahun']) $vonis[] = $n['lama_pidana_tahun'] . ' Thn';
    if ($n['lama_pidana_bulan']) $vonis[] = $n['lama_pidana_bulan'] . ' Bln';
    if ($n['lama_pidana_hari'])  $vonis[] = $n['lama_pidana_hari']  . ' Hr';
    $eks = $n['ekspirasi'] ? date('d/m/Y', strtotime($n['ekspirasi'])) : '-';
    $line = ($i+1) . '.   ' . mb_strtoupper($n['nama']) . '   -   Reg. ' . $n['no_register']
          . '   -   ' . implode(' ', $vonis)
          . '   -   Eks. ' . $eks . '   (' . $kegiatan . ')';
    $doc->addParagraph($line, ['size' => 20, 'before' => 0, 'after' => 40]);
}

$doc->addBlankLine(2);

// Keterangan Jumlah
$doc->addParagraph('Keterangan',                                                    ['bold' => true, 'size' => 20, 'before' => 40, 'after' => 20]);
$doc->addParagraph('Pembebasan Bersyarat (PB) : ' . $countPB . ' Orang WBP',       ['size' => 20, 'before' => 0, 'after' => 10]);
$doc->addParagraph('Cuti Bersyarat (CB)       : ' . $countCB . ' Orang WBP',       ['size' => 20, 'before' => 0, 'after' => 80]);

// TTD Halaman 2 — kanan: Mengetahui; Kalapas (noBorder)
$doc->addTable(
    ['', 'Mengetahui ;'],
    [['', 'K a l a p a s']],
    ['colWidths' => [5000, 4360], 'noBorder' => true]
);
$doc->addBlankLine(4);
$doc->addTable(
    ['', $namaKalapas],
    [['', 'NIP. ' . $nipKalapas]],
    ['colWidths' => [5000, 4360], 'noBorder' => true]
);

$doc->download('Undangan_Integrasi_PB_dan_CB_' . date('Ymd', strtotime($s['tanggal_sidang'])) . '.docx');
