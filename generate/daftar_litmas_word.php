<?php
// generate/daftar_litmas_word.php — Daftar Narapidana Permohonan LITMAS
ini_set('display_errors', 0);
error_reporting(E_ALL);
set_exception_handler(function(Throwable $e) {
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code(500);
    die('<div style="font-family:Arial;padding:30px;background:#fff0f0;border:2px solid red;border-radius:8px;margin:20px">
        <h2 style="color:red">❌ Error Generate Daftar LITMAS</h2>
        <p><b>' . get_class($e) . ':</b> ' . htmlspecialchars($e->getMessage()) . '</p>
        <p>File: ' . htmlspecialchars($e->getFile()) . ' baris ' . $e->getLine() . '</p>
    </div>');
});

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/DocxWriter.php';
require_once __DIR__ . '/../includes/auth.php';

$ids   = array_filter(array_map('intval', explode(',', $_GET['ids'] ?? '')));
$nomor = $_GET['nomor'] ?? '';
$tgl   = $_GET['tgl']   ?? date('Y-m-d');

if (empty($ids)) exit('Tidak ada WBP dipilih.');

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT n.*, p.nama AS penjamin_nama, p.hubungan AS penjamin_hubungan,
                        p.alamat AS penjamin_alamat, p.hp AS penjamin_hp
                        FROM narapidana n
                        LEFT JOIN penjamin p ON p.narapidana_id=n.id AND p.urutan=1
                        WHERE n.id IN ($placeholders) ORDER BY n.nama");
$stmt->execute($ids);
$napiList = $stmt->fetchAll();

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
$ttdPath      = __DIR__ . '/../ttd_kalapas.jpg';
$tglStr       = formatTanggal($tgl);

// Nama WBP pertama untuk Hal
$namaFirst = !empty($napiList) ? $napiList[0]['nama'] : '';
$total     = count($napiList);
$halNama   = $namaFirst . ($total > 1 ? ', dkk' : '');

$doc = new DocxWriter();

// ═══════════════════════════════════════════════════
// HALAMAN 1: SURAT PERMOHONAN LITMAS
// ═══════════════════════════════════════════════════

$doc->addKopWithLogo($logoPath, $namaInstansi, $kanwil, $alamat, $telp, $fax, $email, $laman, $kodePos);
$doc->addBlankLine();

// Nomor | Tanggal (2 kolom — no border)
$doc->addTable(
    ['', ''],
    [[
        ['text' => 'Nomor',     'opts' => ['size' => 22]],
        ['text' => $tglStr,     'opts' => ['size' => 22, 'align' => 'right']],
    ]],
    ['colWidths' => [6200, 3160], 'noBorder' => true]
);
$doc->addParagraph('Lampiran :  ' . $total . ' (' . terbilang($total) . ') Berkas', ['size' => 22, 'before' => 0, 'after' => 20]);
$doc->addParagraph('Hal          :  Permohonan Penelitian Kemasyarakatan a/n', ['size' => 22, 'before' => 0, 'after' => 20]);
$doc->addParagraph('                  ' . $halNama, ['size' => 22, 'before' => 0, 'after' => 80]);

// Tujuan
$doc->addParagraph('Yth,', ['size' => 22, 'before' => 60, 'after' => 20]);
$doc->addParagraph('Kepala Balai Pemasyarakatan (BAPAS) Kelas II GARUT', ['size' => 22, 'before' => 0, 'after' => 20]);
$doc->addParagraph('     di-', ['size' => 22, 'before' => 0, 'after' => 20]);
$doc->addParagraph('          G A R U T', ['size' => 22, 'before' => 0, 'after' => 100]);

// Isi
$doc->addParagraph(
    '          Dalam rangka pembinaan dan pembimbingan bagi narapidana / anak didik dengan Sistem Pemasyarakatan sesuai dengan Peraturan Menteri Hukum dan Hak Asasi Manusia R.I Nomor : 7 Tahun 2022 tentang Syarat dan Tata Cara Pemberian Remisi, Asimilasi, Cuti Mengunjungi Keluarga, Pembebasan Bersyarat, Cuti Menjelang Bebas dan Cuti Bersyarat.',
    ['size' => 22, 'before' => 60, 'after' => 60, 'align' => 'both']
);
$doc->addParagraph(
    '          Maka bersama ini dengan hormat kami mengajukan permintaan pembuatan Penelitian Kemasyarakatan (LITMAS) guna usulan Pembebasan Bersyarat / Cuti Bersyarat pada Lembaga Pemasyarakatan Kelas IIB Tasikmalaya a.n ' . $halNama . ' (daftar nama terlampir).',
    ['size' => 22, 'before' => 0, 'after' => 60, 'align' => 'both']
);
$doc->addParagraph(
    '          Demikian kami sampaikan atas perhatian dan kerjasamanya di ucapkan terimakasih,',
    ['size' => 22, 'before' => 0, 'after' => 200, 'align' => 'both']
);

// TTD
$doc->addParagraph('K e p a l a,', ['align' => 'right', 'size' => 22, 'before' => 0, 'after' => 20]);
$ttdRId = $doc->embedImage($ttdPath);
if (!empty($ttdRId)) { $doc->addInlineImage($ttdRId, 1800000, 700000, 'right'); }
$doc->addParagraph($namaKalapas, ['align' => 'right', 'bold' => true, 'underline' => true, 'size' => 22, 'before' => 0, 'after' => 20]);
if ($nipKalapas) {
    $doc->addParagraph('NIP. ' . $nipKalapas, ['align' => 'right', 'size' => 20, 'before' => 0, 'after' => 40]);
}

// Tembusan
$doc->addBlankLine();
$doc->addParagraph('Tembusan :', ['italic' => true, 'size' => 20, 'before' => 0, 'after' => 20]);
$doc->addParagraph('Kepala Kantor Wilayah Kementerian Hukum dan HAM Jawa Barat', ['italic' => true, 'size' => 20, 'before' => 0, 'after' => 20]);
$doc->addParagraph('(Cq. Kepala Divisi Pemasyarakatan Jawa Barat)', ['italic' => true, 'size' => 20, 'before' => 0, 'after' => 0]);

// ═══════════════════════════════════════════════════
// HALAMAN 2: LAMPIRAN DAFTAR NARAPIDANA
// ═══════════════════════════════════════════════════
$doc->addPageBreak();

$doc->addKopWithLogo($logoPath, $namaInstansi, $kanwil, $alamat, $telp, $fax, $email, $laman, $kodePos);
$doc->addBlankLine();

// Lampiran header kanan
$doc->addParagraph('Lampiran Permohonan Penelitian Kemasyarakatan', ['align' => 'right', 'size' => 20, 'before' => 0, 'after' => 20]);
if ($nomor) {
    $doc->addParagraph('Nomor    : ' . $nomor, ['align' => 'right', 'size' => 20, 'before' => 0, 'after' => 20]);
}
$doc->addParagraph('Tanggal  : ' . $tglStr, ['align' => 'right', 'size' => 20, 'before' => 0, 'after' => 40]);

// Judul
$doc->addParagraph('DAFTAR NARAPIDANA', ['bold' => true, 'align' => 'center', 'underline' => true, 'size' => 24, 'before' => 40, 'after' => 20]);
$doc->addParagraph('UNTUK PERMOHONAN LITMAS', ['bold' => true, 'align' => 'center', 'underline' => true, 'size' => 22, 'before' => 0, 'after' => 60]);

// Daftar narapidana — plain text (hanya pengantar yg pakai tabel)
foreach ($napiList as $i => $n) {
    $lama = [];
    if ($n['lama_pidana_tahun']) $lama[] = $n['lama_pidana_tahun'] . ' Tahun';
    if ($n['lama_pidana_bulan']) $lama[] = $n['lama_pidana_bulan'] . ' Bulan';
    if ($n['lama_pidana_hari'])  $lama[] = $n['lama_pidana_hari']  . ' Hari';

    $penjaminTxt = $n['penjamin_nama'] ?? '';
    if ($n['penjamin_alamat']) $penjaminTxt .= ' / ' . $n['penjamin_alamat'];

    $line = ($i+1) . '.   ' . $n['nama'] . '   ' . $n['no_register']
          . '   -   ' . implode(' ', $lama) . '   -   ' . ($n['pasal'] ?? '')
          . ($penjaminTxt ? '   -   Penjamin: ' . $penjaminTxt : '');
    $doc->addParagraph($line, ['size' => 20, 'before' => 0, 'after' => 60]);
}
$doc->addBlankLine(2);

// TTD
$doc->addParagraph('Mengetahui ;',  ['align' => 'right', 'size' => 22, 'before' => 40, 'after' => 20]);
$doc->addParagraph('K a l a p a s', ['align' => 'right', 'size' => 22, 'before' => 0, 'after' => 20]);
$ttdRId2 = $doc->embedImage($ttdPath);
if (!empty($ttdRId2)) { $doc->addInlineImage($ttdRId2, 1800000, 700000, 'right'); }
$doc->addParagraph($namaKalapas,    ['align' => 'right', 'bold' => true, 'underline' => true, 'size' => 22, 'before' => 0, 'after' => 20]);
if ($nipKalapas) {
    $doc->addParagraph('NIP. ' . $nipKalapas, ['align' => 'right', 'size' => 20, 'before' => 0, 'after' => 0]);
}

$doc->download('Daftar_LITMAS_' . date('Ymd') . '.docx');
