<?php
// generate/pengantar_word.php — Surat Pengantar CB/PB + Lampiran Daftar WBP
ini_set('display_errors', 0);
error_reporting(E_ALL);
set_exception_handler(function(Throwable $e) {
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code(500);
    die('<div style="font-family:Arial;padding:30px;background:#fff0f0;border:2px solid red;border-radius:8px;margin:20px">
        <h2 style="color:red">❌ Error Generate Surat Pengantar</h2>
        <p><b>' . get_class($e) . ':</b> ' . htmlspecialchars($e->getMessage()) . '</p>
        <p>File: ' . htmlspecialchars($e->getFile()) . ' baris ' . $e->getLine() . '</p>
    </div>');
});

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/DocxWriter.php';
require_once __DIR__ . '/../includes/auth.php';

$nomorSurat   = $_GET['nomor_surat']   ?? '';
$tanggalSurat = $_GET['tanggal_surat'] ?? date('Y-m-d');
$lampiran     = $_GET['lampiran']      ?? '1 (satu) Berkas';
$ids          = array_filter(array_map('intval', explode(',', $_GET['ids'] ?? '')));

if (empty($ids)) {
    setFlash('danger', 'Tidak ada WBP yang dipilih.');
    redirect(BASE_URL . '/surat/pengantar_cb_pb.php');
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT * FROM narapidana WHERE id IN ($placeholders) ORDER BY kegiatan, nama");
$stmt->execute($ids);
$napiList = $stmt->fetchAll();

$cbList = array_values(array_filter($napiList, fn($n) => $n['kegiatan'] === 'CB'));
$pbList = array_values(array_filter($napiList, fn($n) => $n['kegiatan'] === 'PB'));
$total  = count($napiList);

// Nama WBP pertama untuk Hal
$namaFirst = !empty($napiList) ? mb_strtoupper($napiList[0]['nama']) : '';
$halWbp    = $namaFirst . ($total > 1 ? ', dkk' : '');

// Setting instansi
$namaInstansi = getSetting('nama_instansi');
$kanwil       = getSetting('kanwil');
$alamat       = getSetting('alamat');
$kodePos      = getSetting('kode_pos');
$telp         = getSetting('telp');
$fax          = getSetting('fax') ?: $telp;
$laman        = getSetting('laman');
$email        = getSetting('email');
$namaKalapas  = getSetting('nama_kalapas');
$nipKalapas   = getSetting('nip_kalapas');
$logoPath     = __DIR__ . '/../logo_kop.png';
$ttdPath      = __DIR__ . '/../ttd_kalapas.jpg'; // Badge elektronik KEMENIMIPAS
$laman        = getSetting('laman')    ?: 'lapastasikmalaya.kemenkumham.go.id';
$kodePos      = getSetting('kode_pos') ?: '46112';

$tglFormatted = formatTanggal($tanggalSurat);

$doc = new DocxWriter();

// ═══════════════════════════════════════════════════
// HALAMAN 1: SURAT PENGANTAR
// ═══════════════════════════════════════════════════

// KOP SURAT dengan Logo
$doc->addKopWithLogo($logoPath, $namaInstansi, $kanwil, $alamat, $telp, $fax, $email, $laman, $kodePos);
$doc->addBlankLine();

// NOMOR | TANGGAL (2 kolom: kiri label+nomor, kanan tanggal) — no border
$doc->addTable(
    ['', ''],
    [[
        ['text' => 'Nomor    :  ' . $nomorSurat,    'opts' => ['size' => 22]],
        ['text' => $tglFormatted,                   'opts' => ['size' => 22, 'align' => 'right']],
    ]],
    ['colWidths' => [6200, 3160], 'noBorder' => true]
);
$doc->addParagraph('Lampiran :  ' . $lampiran,                                             ['size' => 22, 'before' => 0, 'after' => 40]);
$doc->addParagraph('Hal      :  Usulan CB dan PB PerMenKum HAM R.I No. 07 Tahun 2022 an, ' . $halWbp, ['size' => 22, 'before' => 0, 'after' => 80]);

// TUJUAN
$doc->addParagraph('Yth. Direktur Jenderal Pemasyarakatan',                               ['size' => 22, 'before' => 60, 'after' => 20]);
$doc->addParagraph('     Kementerian Imigrasi dan Pemasyarakatan R.I.',                   ['size' => 22, 'before' => 0,  'after' => 20]);
$doc->addParagraph('          di',                                                        ['size' => 22, 'before' => 0,  'after' => 20]);
$doc->addParagraph('               J a k a r t a',                                       ['size' => 22, 'before' => 0,  'after' => 100]);

// ISI
$doc->addParagraph(
    '          Dengan hormat kami sampaikan bahwa, dalam rangka kegiatan pembinaan narapidana di luar Lembaga Pemasyarakatan, kami bermaksud memberikan Cuti Bersyarat (CB) dan Pembebasan Bersyarat (PB) berdasarkan Peraturan Menteri Hukum dan HAM R.I Nomor 07 Tahun 2022 terhadap Narapidana yang telah memenuhi persyaratan substantif dan administratif.',
    ['size' => 22, 'before' => 60, 'after' => 60, 'align' => 'both']
);

$doc->addParagraph(
    '          Sehubungan dengan hal tersebut, kami mengajukan permohonan dimaksud terhadap ' . $total . ' (' . terbilang($total) . ') orang Narapidana (daftar terlampir) dengan rincian sebagai berikut :',
    ['size' => 22, 'before' => 0, 'after' => 40, 'align' => 'both']
);

$doc->addParagraph('     1. Cuti Bersyarat (CB)            : ' . count($cbList) . ' (' . terbilang(count($cbList)) . ') Orang', ['size' => 22, 'before' => 0, 'after' => 20]);
$doc->addParagraph('     2. Pembebasan Bersyarat (PB) : ' . count($pbList) . ' (' . terbilang(count($pbList)) . ') Orang',      ['size' => 22, 'before' => 0, 'after' => 60]);

$doc->addParagraph(
    '          Demikian permohonan ini kami sampaikan, atas perhatian dan perkenan Bapak, kami ucapkan terimakasih.',
    ['size' => 22, 'before' => 0, 'after' => 200, 'align' => 'both']
);

// TTD halaman 1 (template: nama kalapas saja, tidak ada NIP)
$doc->addParagraph('K e p a l a', ['align' => 'right', 'size' => 22, 'before' => 0, 'after' => 20]);
$ttdRId = $doc->embedImage($ttdPath);
if (!empty($ttdRId)) {
    $doc->addInlineImage($ttdRId, 1800000, 700000, 'right');
}
$doc->addParagraph($namaKalapas, ['align' => 'right', 'bold' => true, 'underline' => true, 'size' => 22, 'before' => 0, 'after' => 20]);

// TEMBUSAN
$doc->addBlankLine();
$doc->addParagraph('Tembusan :',                                                          ['italic' => true, 'size' => 20, 'before' => 0, 'after' => 20]);
$doc->addParagraph('Yth. Direktorat Jenderal Pemasyarakatan Kantor Wilayah Jawa Barat',  ['italic' => true, 'size' => 20, 'before' => 0, 'after' => 20]);
$doc->addParagraph('     Di - Bandung',                                                  ['italic' => true, 'size' => 20, 'before' => 0, 'after' => 0]);

// ═══════════════════════════════════════════════════
// HALAMAN 2: DAFTAR NARAPIDANA YANG DIUSULKAN
// ═══════════════════════════════════════════════════
$doc->addPageBreak();

// KOP Lampiran
$doc->addKopWithLogo($logoPath, $namaInstansi, $kanwil, $alamat, $telp, $fax, $email, $laman, $kodePos);
$doc->addBlankLine();

// Judul daftar
$doc->addParagraph('DAFTAR NAMA NARAPIDANA YANG DIUSULKAN',
    ['bold' => true, 'align' => 'center', 'size' => 22, 'before' => 40, 'after' => 20]);
$doc->addParagraph('PEMBEBASAN BERSYARAT DAN CUTI BERSYARAT',
    ['bold' => true, 'align' => 'center', 'size' => 22, 'before' => 0, 'after' => 20]);
$doc->addParagraph('Nomor : ' . $nomorSurat . '          Tanggal : ' . $tglFormatted,
    ['align' => 'center', 'size' => 20, 'before' => 0, 'after' => 60]);

// Tabel 8 kolom sesuai template
$headers = ['No.', 'Nama WBP', 'No. Reg', 'Pasal', 'Vonis', 'Tgl 2/3', 'Tgl Expirasi', 'Keterangan'];
$widths  = [360, 2100, 900, 1700, 900, 900, 1000, 700];

$rows = [];
foreach ($napiList as $i => $n) {
    $vonis = [];
    if ($n['lama_pidana_tahun']) $vonis[] = $n['lama_pidana_tahun'] . ' Tahun';
    if ($n['lama_pidana_bulan']) $vonis[] = $n['lama_pidana_bulan'] . ' Bulan';
    if ($n['lama_pidana_hari'])  $vonis[] = $n['lama_pidana_hari']  . ' Hari';

    $rows[] = [
        ['text' => ($i + 1) . '.',                                                               'opts' => ['align' => 'center', 'size' => 18]],
        ['text' => mb_strtoupper($n['nama']),                                                    'opts' => ['bold' => true, 'size' => 18]],
        ['text' => $n['no_register'],                                                            'opts' => ['align' => 'center', 'size' => 18]],
        ['text' => $n['pasal'] ?? '',                                                            'opts' => ['size' => 16]],
        ['text' => implode(' ', $vonis) ?: '-',                                                  'opts' => ['align' => 'center', 'size' => 18]],
        ['text' => ($n['tanggal_2_3'] ? date('d/m/Y', strtotime($n['tanggal_2_3'])) : '-'),     'opts' => ['align' => 'center', 'size' => 18]],
        ['text' => ($n['ekspirasi']   ? date('d/m/Y', strtotime($n['ekspirasi']))   : '-'),     'opts' => ['align' => 'center', 'size' => 18]],
        ['text' => $n['kegiatan'],                                                               'opts' => ['align' => 'center', 'bold' => true, 'size' => 18]],
    ];
}

$doc->addTable($headers, $rows, ['colWidths' => $widths]);
$doc->addBlankLine(2);

// TTD lampiran
$doc->addParagraph('Mengetahui ;', ['align' => 'right', 'size' => 22, 'before' => 40, 'after' => 20]);
$doc->addParagraph('K a l a p a s', ['align' => 'right', 'size' => 22, 'before' => 0, 'after' => 20]);
$ttdRId2 = $doc->embedImage($ttdPath);
if (!empty($ttdRId2)) {
    $doc->addInlineImage($ttdRId2, 1800000, 700000, 'right');
}
$doc->addParagraph($namaKalapas, ['align' => 'right', 'bold' => true, 'underline' => true, 'size' => 22, 'before' => 0, 'after' => 20]);
if ($nipKalapas) {
    $doc->addParagraph('NIP. ' . $nipKalapas, ['align' => 'right', 'size' => 20, 'before' => 0, 'after' => 0]);
}

$doc->download('Surat_Pengantar_CB_PB_' . date('Ymd') . '.docx');
