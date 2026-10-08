<?php
// generate/pernyataan_word.php — 6 Halaman Surat Pernyataan

ini_set('display_errors', 0);
error_reporting(E_ALL);
set_exception_handler(function(Throwable $e) {
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code(200);
    die('<div style="font-family:Arial;padding:30px;background:#fff0f0;border:2px solid red;border-radius:8px;margin:20px">
        <h2 style="color:red">&#10060; Error Generate Surat Pernyataan</h2>
        <p><b>' . get_class($e) . ':</b> ' . htmlspecialchars($e->getMessage()) . '</p>
        <p>File: ' . htmlspecialchars($e->getFile()) . ' baris ' . $e->getLine() . '</p>
    </div>');
});

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/DocxWriter.php';
require_once __DIR__ . '/../includes/auth.php';

$id  = (int)($_GET['id'] ?? 0);
$tgl = $_GET['tgl'] ?? date('Y-m-d');

// ── Data Narapidana ──────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM narapidana WHERE id=?");
$stmt->execute([$id]);
$n = $stmt->fetch();
if (!$n) exit('WBP tidak ditemukan.');

// ── Data Penjamin (urutan 1) ────────────────────
$stmt2 = $pdo->prepare("SELECT * FROM penjamin WHERE narapidana_id=? ORDER BY urutan LIMIT 1");
$stmt2->execute([$id]);
$pj = $stmt2->fetch() ?: [];

// ── Helper ──────────────────────────────────────
$lamaPidana = [];
if ($n['lama_pidana_tahun']) $lamaPidana[] = $n['lama_pidana_tahun'] . ' Tahun';
if ($n['lama_pidana_bulan']) $lamaPidana[] = $n['lama_pidana_bulan'] . ' Bulan';
if ($n['lama_pidana_hari'])  $lamaPidana[] = $n['lama_pidana_hari']  . ' Hari';
$lamaStr = implode(' ', $lamaPidana) ?: '-';

$tglStr       = formatTanggal($tgl);
$namaKalapas  = getSetting('nama_kalapas');
$nipKalapas   = getSetting('nip_kalapas');
$namaInstansi = getSetting('nama_instansi');
$kanwil       = getSetting('kanwil');
$alamat       = getSetting('alamat');
$telp         = getSetting('telp');
$fax          = getSetting('fax') ?: getSetting('telp');
$email        = getSetting('email');
$logoPath     = __DIR__ . '/../logo_kop.png'; // logo lambang untuk kop surat
$ttdPath      = __DIR__ . '/../ttd_kalapas.jpg'; // badge TTD Kalapas
$laman        = getSetting('laman')    ?: 'lapastasikmalaya.kemenkumham.go.id';
$kodePos      = getSetting('kode_pos') ?: '46112';

function pjField(array $pj, string $key): string {
    return $pj[$key] ?? '';
}
function napiUmur(array $n): string {
    $tgl = $n['tanggal_lahir'] ?? '';
    if (!$tgl || $tgl === '0000-00-00') return '';
    try {
        $diff = (new DateTime($tgl))->diff(new DateTime());
        return $diff->y . ' Tahun';
    } catch (Throwable $e) { return ''; }
}
function pjUmur(array $pj): string {
    if (!empty($pj['umur'])) return $pj['umur'] . ' Tahun';
    $tgl = $pj['tanggal_lahir'] ?? '';
    if (!$tgl || $tgl === '0000-00-00') return '';
    try {
        $diff = (new DateTime($tgl))->diff(new DateTime());
        return $diff->y . ' Tahun';
    } catch (Throwable $e) { return ''; }
}

function addDataRow(DocxWriter $doc, array $rows, array $colWidths = [2500, 200, 6660]): void {
    $doc->addTable(['','',''], $rows, ['colWidths' => $colWidths, 'noBorder' => true]);
}

function addTtd2Kolom(DocxWriter $doc, string $kiri, string $kanan, array $colWidths = [4680,4680]): void {
    $doc->addTable([$kiri, $kanan], [['','']], ['colWidths' => $colWidths, 'noBorder' => true]);
}

// ═══════════════════════════════════════════════════
// HALAMAN 1: Surat Pernyataan Narapidana
//            (tidak dipungut biaya PB/CB/CMB)
// ═══════════════════════════════════════════════════
$doc = new DocxWriter();

$doc->addKopWithLogo($logoPath, $namaInstansi, $kanwil, $alamat, $telp, $fax, $email, $laman, $kodePos);
$doc->addBlankLine();

$doc->addParagraph('SURAT PERNYATAAN',
    ['bold'=>true,'align'=>'center','underline'=>true,'size'=>28,'before'=>80,'after'=>80]);

$doc->addParagraph('Saya yang bertanda tangan di bawah ini;',
    ['size'=>22,'before'=>60,'after'=>60]);

addDataRow($doc, [
    ['Nama Lengkap', ':', $n['nama']],
    ['Pasal',        ':', $n['pasal'] ?? '-'],
    ['Lama Pidana',  ':', $lamaStr],
    ['Expirasi',     ':', formatTanggal($n['ekspirasi'] ?? '')],
    ['Alamat',       ':', $n['alamat'] ?? '-'],
]);

$doc->addBlankLine();
$doc->addParagraph(
    '          Menyatakan dengan sebenarnya bahwa dalam pengurusan program PB/CB/CMB kami tidak dikenakan biaya apapun dan tidak memberikan atau menjanjikan imbalan / upeti / sejenisnya berupa apapun secara langsung maupun tidak langsung kepada Pejabat / Pegawai di Lapas Kelas IIB Tasikmalaya dan atas layanan yang diberikan Pejabat / Pegawai tidak memungut biaya apapun.',
    ['size'=>22,'before'=>60,'after'=>40,'align'=>'both']);
$doc->addParagraph(
    'Demikian surat pernyataan ini saya buat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.',
    ['size'=>22,'before'=>0,'after'=>120,'align'=>'both']);

$doc->addParagraph('Tasikmalaya, ' . $tglStr,
    ['size'=>22,'align'=>'right','before'=>0,'after'=>40]);
addTtd2Kolom($doc, 'Keluarga Narapidana,', 'Narapidana,');
$doc->addBlankLine(4);
$doc->addParagraph('Mengetahui;',   ['size'=>22,'align'=>'center','before'=>0,'after'=>20]);
$doc->addParagraph('Kepala,',       ['size'=>22,'align'=>'center','before'=>0,'after'=>20]);
$ttdRId = $doc->embedImage($ttdPath);
if (!empty($ttdRId)) { $doc->addInlineImage($ttdRId, 1800000, 700000, 'center'); }
$doc->addParagraph($namaKalapas,    ['size'=>22,'align'=>'center','bold'=>true,'underline'=>true,'before'=>0,'after'=>20]);
$doc->addParagraph('NIP. ' . $nipKalapas, ['size'=>22,'align'=>'center','before'=>0,'after'=>0]);

// ─────────────────────────────────────────────────
$doc->addPageBreak();

// ═══════════════════════════════════════════════════
// HALAMAN 2: Surat Pernyataan Narapidana
//            (tentang proses PB — komitmen)
// ═══════════════════════════════════════════════════
$doc->addKopWithLogo($logoPath, $namaInstansi, $kanwil, $alamat, $telp, $fax, $email, $laman, $kodePos);
$doc->addBlankLine();

$doc->addParagraph('SURAT PERNYATAAN',
    ['bold'=>true,'align'=>'center','underline'=>true,'size'=>28,'before'=>80,'after'=>60]);

addDataRow($doc, [
    ['Nama Lengkap',    ':', $n['nama']],
    ['Nomor Register',  ':', $n['no_register']],
    ['Perkara',         ':', $n['perkara'] ?? '-'],
    ['Alamat',          ':', $n['alamat'] ?? '-'],
]);

$doc->addBlankLine();
$kegiatan = $n['kegiatan'] === 'PB' ? 'Pembebasan Bersyarat (PB)' : 'Cuti Bersyarat (CB)';
$doc->addParagraph(
    '          Untuk melengkapi proses pengusulan ' . $kegiatan . ' saya, dengan ini saya menyatakan dengan kesadaran sendiri, tanpa ada paksaan dari pihak lain sebagai berikut:',
    ['size'=>22,'before'=>60,'after'=>60,'align'=>'both']);

$doc->addParagraph('1   Selama proses pengusulan ' . $kegiatan . ' saya berlangsung, saya akan tetap mengikuti program pembinaan yang ada dan tetap bekerja sebagaimana mestinya;',
    ['size'=>22,'before'=>0,'after'=>40,'align'=>'both','indent'=>360]);
$doc->addParagraph('2   Jika ' . $kegiatan . ' saya dikabulkan, selama menjalani ' . ($n['kegiatan'] ?? 'PB') . ' saya akan senantiasa menjalani sesuai ketentuan dan tidak akan melakukan perbuatan yang melanggar hukum lagi sampai masa bimbingan berakhir, sekaligus mentaati ketentuan yang akan diberikan oleh Pembimbing Kemasyarakatan (PK) dari Balai Pemasyarakatan setempat;',
    ['size'=>22,'before'=>0,'after'=>40,'align'=>'both','indent'=>360]);
$doc->addParagraph('3   Saya bersedia melapor diri sebulan sekali ke Balai Pemasyarakatan, KeJaksaan Negeri setempat selama masa pembinaan ' . $kegiatan . ' saya;',
    ['size'=>22,'before'=>0,'after'=>40,'align'=>'both','indent'=>360]);
$doc->addParagraph('4   Saya senantiasa akan berusaha menjadi Warga Negara yang baik (berguna bagi keluarga, masyarakat dan Negara).',
    ['size'=>22,'before'=>0,'after'=>60,'align'=>'both','indent'=>360]);

$doc->addParagraph(
    '          Apabila dikemudian hari baik disengaja maupun tidak disengaja saya melakukan perbuatan yang sama / pelanggaran hukum lainnya maupun melalikan pernyataan tersebut di atas saya bersedia menerima sanksi berupa Pencabutan Usulan / Surat Keputusan ' . ($n['kegiatan'] ?? 'PB') . ' saya.',
    ['size'=>22,'before'=>0,'after'=>40,'align'=>'both']);
$doc->addParagraph(
    '          Demikian Surat Pernyataan ini dibuat untuk dipergunakan seperlunya dan sebagai pengikat diri saya selama menunggu proses pengusulan ' . $kegiatan . ' maupun selama menJalani masa bimbingannya nanti.',
    ['size'=>22,'before'=>0,'after'=>120,'align'=>'both']);

$doc->addParagraph('Tasikmalaya, ' . $tglStr,
    ['size'=>22,'align'=>'right','before'=>0,'after'=>40]);
addTtd2Kolom($doc, 'Mengetahui;' . "\n" . 'Kepala,', 'Narapidana,');
$ttdRId2 = $doc->embedImage($ttdPath);
if (!empty($ttdRId2)) { $doc->addInlineImage($ttdRId2, 1800000, 700000, 'left'); }
$doc->addParagraph($namaKalapas,         ['size'=>22,'bold'=>true,'underline'=>true,'before'=>0,'after'=>16]);
$doc->addParagraph('NIP. ' . $nipKalapas, ['size'=>22,'before'=>0,'after'=>0]);

// ─────────────────────────────────────────────────
$doc->addPageBreak();

// ═══════════════════════════════════════════════════
// HALAMAN 3: Surat Pernyataan Jaminan (by Penjamin)
// ═══════════════════════════════════════════════════
$doc->addParagraph('SURAT PERNYATAAN JAMINAN',
    ['bold'=>true,'align'=>'center','underline'=>true,'size'=>24,'before'=>80,'after'=>80]);

$doc->addParagraph('Yang bertanda tangan di bawah ini :',
    ['size'=>22,'before'=>60,'after'=>60]);

addDataRow($doc, [
    ['Nama Lengkap', ':', pjField($pj,'nama')],
    ['Umur',         ':', pjUmur($pj)],
    ['Pekerjaan',    ':', pjField($pj,'pekerjaan')],
    ['Alamat',       ':', pjField($pj,'alamat')],
]);

$doc->addBlankLine();
$doc->addParagraph('Adalah sebagai Penjamin dari Narapidana:',
    ['size'=>22,'before'=>60,'after'=>40]);
addDataRow($doc, [
    ['Nama Lengkap', ':', $n['nama']],
    ['Umur',         ':', napiUmur($n)],
]);

$doc->addBlankLine();
$doc->addParagraph(
    '          Yang saat ini sedang menjalani pidana di Lembaga Pemasyarakatan Kelas IIB Tasikmalaya',
    ['size'=>22,'before'=>60,'after'=>20,'align'=>'both']);
$doc->addParagraph('Dengan ini menyatakan:',
    ['size'=>22,'before'=>0,'after'=>40]);

$doc->addParagraph('1   Sanggup menjamin sepenuhnya apabila Narapidana tersebut diberikan izin Asimilasi, Cuti Bersyarat, Cuti Menjelang Bebas dan Pembebasan Bersyarat yang bersangkutan tidak melarikan diri dan atau melanggar ketentuan-ketentuan lainnya;',
    ['size'=>22,'before'=>0,'after'=>40,'align'=>'both','indent'=>360]);
$doc->addParagraph('2   Sanggup turut mengawasi dan membina Narapidana yang bersangkutan agar menjadi warga negara yang bertanggung jawab;',
    ['size'=>22,'before'=>0,'after'=>40,'align'=>'both','indent'=>360]);
$doc->addParagraph('3   Bahwasanya dalam proses pengusulan program pembinaan berupa PB, CB, CMB, CMK dan Remisi tidak pernah dipungut biaya apapun oleh petugas.',
    ['size'=>22,'before'=>0,'after'=>60,'align'=>'both','indent'=>360]);

$doc->addParagraph(
    '          Demikian Surat Jaminan ini dibuat dengan sesungguhnya untuk dipergunakan seperlunya.',
    ['size'=>22,'before'=>0,'after'=>120,'align'=>'both']);

$doc->addParagraph('Tasikmalaya,',       ['size'=>22,'align'=>'right','before'=>0,'after'=>0]);
$doc->addParagraph('Yang Membuat Pernyataan,', ['size'=>22,'align'=>'right','before'=>0,'after'=>0]);
$doc->addBlankLine(4);
$doc->addParagraph('..............................', ['size'=>22,'align'=>'right','before'=>0,'after'=>0]);

// ─────────────────────────────────────────────────
$doc->addPageBreak();

// ═══════════════════════════════════════════════════
// HALAMAN 4: Surat Pernyataan Keluarga/Penjamin
// ═══════════════════════════════════════════════════
$doc->addParagraph('SURAT PERNYATAAN',
    ['bold'=>true,'align'=>'center','underline'=>true,'size'=>24,'before'=>80,'after'=>80]);

$doc->addParagraph('Yang bertanda tangan di bawah ini :',
    ['size'=>22,'before'=>60,'after'=>60]);

addDataRow($doc, [
    ['Nama Lengkap', ':', pjField($pj,'nama')],
    ['Umur',         ':', pjUmur($pj)],
    ['Pekerjaan',    ':', pjField($pj,'pekerjaan')],
    ['Alamat',       ':', pjField($pj,'alamat')],
]);

$doc->addBlankLine();
$hubungan = pjField($pj,'hubungan') ?: '……………………………';
$doc->addParagraph('Adalah sebagai ' . $hubungan . ' dari Narapidana bernama;',
    ['size'=>22,'before'=>60,'after'=>20,'align'=>'both']);
$doc->addParagraph($n['nama'],
    ['size'=>22,'before'=>0,'after'=>40]);

$doc->addParagraph(
    'Yang sedang menjalani pidana di Lembaga Pemasyarakatan Kelas IIB Tasikmalaya, memberikan pernyataan bahwa apabila yang bersangkutan mendapat Cuti Bersyarat, Cuti Menjelang Bebas dan Pembebasan Bersyarat:',
    ['size'=>22,'before'=>0,'after'=>40,'align'=>'both']);
$doc->addParagraph('1   Kami akan bersedia menerima kembali yang bersangkutan untuk bertempat tinggal di rumah kami;',
    ['size'=>22,'before'=>0,'after'=>30,'align'=>'both','indent'=>360]);
$doc->addParagraph('2   Kami sanggup membantu penghidupannya baik secara moril maupun materil.',
    ['size'=>22,'before'=>0,'after'=>60,'align'=>'both','indent'=>360]);

$doc->addParagraph(
    'Demikian Surat Jaminan ini dibuat dengan sesungguhnya untuk dipergunakan seperlunya.',
    ['size'=>22,'before'=>0,'after'=>100,'align'=>'both']);

$doc->addParagraph('Yang membuat pernyataan;',
    ['size'=>22,'align'=>'center','before'=>0,'after'=>40]);
addTtd2Kolom($doc, 'Penjamin Pertama,', 'Penjamin Kedua,');
$doc->addBlankLine(4);
addTtd2Kolom($doc, '..............................', '..............................');
$doc->addBlankLine();
$doc->addParagraph('Mengetahui;',            ['size'=>22,'align'=>'center','before'=>0,'after'=>10]);
$doc->addParagraph('Kepala Desa/Kelurahan,', ['size'=>22,'align'=>'center','before'=>0,'after'=>0]);
$doc->addBlankLine(4);
$doc->addParagraph('..............................', ['size'=>22,'align'=>'center','before'=>0,'after'=>0]);

// ─────────────────────────────────────────────────
$doc->addPageBreak();

// ═══════════════════════════════════════════════════
// HALAMAN 5: Surat Pernyataan Dari Lingkungan Setempat
// ═══════════════════════════════════════════════════
$doc->addParagraph('SURAT PERNYATAAN DARI LINGKUNGAN SETEMPAT',
    ['bold'=>true,'align'=>'center','underline'=>true,'size'=>22,'before'=>80,'after'=>80]);

$doc->addParagraph('Yang bertanda tangan di bawah ini :', ['size'=>22,'before'=>60,'after'=>10]);
$doc->addParagraph('Pengurus',                            ['size'=>22,'before'=>0,'after'=>60]);

$doc->addParagraph('Dengan ini menyatakan bahwa:',        ['size'=>22,'before'=>0,'after'=>20]);
$doc->addParagraph('Apabila Narapidana Atas Nama …………………………………',
    ['size'=>22,'before'=>0,'after'=>20]);
$doc->addParagraph(
    'Yang saat ini sedang menjalani pidana di Lembaga Pemasyarakatan Kelas IIB Tasikmalaya. Diberikan Asimilasi / Pembebasan Bersyarat / Cuti Bersyarat/ Cuti Menjelang Bebas / Cuti Mengunjungi Keluarga dan kembali berada ditengah-tengah masyarakat, maka kami menyatakan;',
    ['size'=>22,'before'=>0,'after'=>40,'align'=>'both']);

$doc->addParagraph('1   Dapat menerima keberadaan Narapidana tersebut ditengah-tengah lingkungan masyarakat,',
    ['size'=>22,'before'=>0,'after'=>30,'align'=>'both','indent'=>360]);
$doc->addParagraph('2   Akan berusaha membantu untuk mengawasi dan membina Narapidana tersebut agar dapat berperilaku baik serta tidak melakukan perbuatan yang melanggar hukum lagi;',
    ['size'=>22,'before'=>0,'after'=>30,'align'=>'both','indent'=>360]);
$doc->addParagraph('3   Mendukung program pembinaan yang dijalani oleh Narapidana tersebut.',
    ['size'=>22,'before'=>0,'after'=>60,'align'=>'both','indent'=>360]);

$doc->addParagraph(
    'Selain hal tersebut diatas, kami juga memberikan keterangan bahwa Penjamin / Penanggung Jawab;',
    ['size'=>22,'before'=>0,'after'=>40,'align'=>'both']);

addDataRow($doc, [
    ['Nama',      ':', pjField($pj,'nama')],
    ['Umur',      ':', pjUmur($pj)],
    ['Pekerjaan', ':', pjField($pj,'pekerjaan')],
    ['Alamat',    ':', pjField($pj,'alamat')],
]);

$doc->addBlankLine();
$doc->addParagraph('Adalah benar-benar warga masyarakat di lingkungan kami.',
    ['size'=>22,'before'=>40,'after'=>20]);
$doc->addParagraph('Demikian Surat Pernyataan ini dibuat untuk dipergunakan seperlunya.',
    ['size'=>22,'before'=>0,'after'=>80,'align'=>'both']);

$doc->addParagraph('Mengetahui;',   ['size'=>22,'align'=>'center','before'=>0,'after'=>40]);
addTtd2Kolom($doc, 'Ketua RT,', 'Ketua RW,');
$doc->addBlankLine(4);
addTtd2Kolom($doc, '..............................', '..............................');
$doc->addBlankLine();
$doc->addParagraph('Mengetahui;',            ['size'=>22,'align'=>'center','before'=>0,'after'=>10]);
$doc->addParagraph('Kepala Desa/Kelurahan,', ['size'=>22,'align'=>'center','before'=>0,'after'=>0]);
$doc->addBlankLine(4);
$doc->addParagraph('..............................', ['size'=>22,'align'=>'center','before'=>0,'after'=>0]);

// ─────────────────────────────────────────────────
$doc->addPageBreak();

// ═══════════════════════════════════════════════════
// HALAMAN 6: Surat Pernyataan Dari Warga Masyarakat
// ═══════════════════════════════════════════════════
$doc->addParagraph('SURAT PERNYATAAN DARI WARGA MASYARAKAT',
    ['bold'=>true,'align'=>'center','underline'=>true,'size'=>22,'before'=>80,'after'=>80]);

$doc->addParagraph('Yang bertanda tangan di bawah ini, Kami warga masyarakat;',
    ['size'=>22,'before'=>60,'after'=>40]);

// Tabel dengan 5 baris kosong
$emptyRows = [];
for ($i = 1; $i <= 5; $i++) {
    $emptyRows[] = [
        ['text' => (string)$i, 'opts' => ['align'=>'center','size'=>22]],
        ['text' => '', 'opts' => ['size'=>22]],
        ['text' => '', 'opts' => ['align'=>'center','size'=>22]],
        ['text' => '', 'opts' => ['size'=>22]],
        ['text' => '', 'opts' => ['size'=>22]],
    ];
}
$doc->addTable(['NO','NAMA','UMUR','PEKERJAAN','TANDA TANGAN'], $emptyRows, [
    'colWidths' => [500, 2500, 1000, 2300, 3060],
    'noBorder'  => true,
]);


$doc->addBlankLine();
$doc->addParagraph('Apabila Narapidana Atas Nama …………………………………',
    ['size'=>22,'before'=>40,'after'=>20]);
$doc->addParagraph(
    'Yang saat ini sedang menjalani pidana di Lembaga Pemasyarakatan Kelas IIB Tasikmalaya. Diberikan Asimilasi / Pembebasan Bersyarat / Cuti Bersyarat/ Cuti Menjelang Bebas / Cuti Mengunjungi Keluarga dan kembali berada ditengah-tengah masyarakat, maka kami menyatakan;',
    ['size'=>22,'before'=>0,'after'=>40,'align'=>'both']);

$doc->addParagraph('1   Dapat menerima keberadaan Narapidana tersebut ditengah-tengah lingkungan masyarakat,',
    ['size'=>22,'before'=>0,'after'=>30,'align'=>'both','indent'=>360]);
$doc->addParagraph('2   Akan berusaha membantu untuk mengawasi dan membina Narapidana tersebut agar dapat berperilaku baik serta tidak melakukan perbuatan yang melanggar hukum lagi;',
    ['size'=>22,'before'=>0,'after'=>30,'align'=>'both','indent'=>360]);
$doc->addParagraph('3   Mendukung program pembinaan yang dijalani oleh Narapidana tersebut.',
    ['size'=>22,'before'=>0,'after'=>60,'align'=>'both','indent'=>360]);

$doc->addParagraph('Demikian Surat Pernyataan ini dibuat untuk dipergunakan seperlunya.',
    ['size'=>22,'before'=>0,'after'=>80,'align'=>'both']);

$doc->addParagraph('Mengetahui;',   ['size'=>22,'align'=>'center','before'=>0,'after'=>40]);
addTtd2Kolom($doc, 'Ketua RT,', 'Ketua RW,');
$doc->addBlankLine(4);
addTtd2Kolom($doc, '..............................', '..............................');
$doc->addBlankLine();
$doc->addParagraph('Mengetahui;',            ['size'=>22,'align'=>'center','before'=>0,'after'=>10]);
$doc->addParagraph('Kepala Desa/Kelurahan,', ['size'=>22,'align'=>'center','before'=>0,'after'=>0]);
$doc->addBlankLine(4);
$doc->addParagraph('..............................', ['size'=>22,'align'=>'center','before'=>0,'after'=>0]);

// ─────────────────────────────────────────────────
$doc->download('Surat_Pernyataan_' . preg_replace('/[^a-zA-Z0-9]/', '_', $n['nama']) . '_' . date('Ymd') . '.docx');
