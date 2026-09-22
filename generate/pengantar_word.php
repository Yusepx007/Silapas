<?php
// generate/pengantar_word.php — Download Word: Surat Pengantar CB/PB

// Tampilkan error jika ada (bantu debug di hosting)
ini_set('display_errors', 0);
error_reporting(E_ALL);
set_exception_handler(function(Throwable $e) {
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code(500);
    die('<div style="font-family:Arial;padding:30px;background:#fff0f0;border:2px solid red;border-radius:8px;margin:20px">
        <h2 style="color:red">❌ Error Generate Surat</h2>
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

$cbList = array_filter($napiList, fn($n) => $n['kegiatan'] === 'CB');
$pbList = array_filter($napiList, fn($n) => $n['kegiatan'] === 'PB');
$total  = count($napiList);

// Setting instansi
$namaInstansi = getSetting('nama_instansi');
$kanwil       = getSetting('kanwil');
$alamat       = getSetting('alamat');
$kodePos      = getSetting('kode_pos');
$telp         = getSetting('telp');
$laman        = getSetting('laman');
$email        = getSetting('email');
$namaKalapas  = getSetting('nama_kalapas');
$nipKalapas   = getSetting('nip_kalapas');

$doc = new DocxWriter();

// KOP SURAT (simulasi dengan teks)
$doc->addParagraph('KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN REPUBLIK INDONESIA', ['bold' => true, 'align' => 'center', 'size' => 22, 'before' => 0, 'after' => 20]);
$doc->addParagraph('DIREKTORAT JENDERAL PEMASYARAKATAN', ['bold' => true, 'align' => 'center', 'size' => 22, 'before' => 0, 'after' => 20]);
$doc->addParagraph($kanwil, ['bold' => true, 'align' => 'center', 'size' => 22, 'before' => 0, 'after' => 20]);
$doc->addParagraph($namaInstansi, ['bold' => true, 'align' => 'center', 'size' => 26, 'before' => 0, 'after' => 20]);
$doc->addParagraph($alamat . ', Kode Pos ' . $kodePos . ' Telp. ' . $telp, ['align' => 'center', 'size' => 18, 'before' => 0, 'after' => 20]);
$doc->addParagraph('Laman : ' . $laman . ' Pos-el : ' . $email, ['align' => 'center', 'size' => 18, 'before' => 0, 'after' => 60]);

$doc->addParagraph('', ['before' => 0, 'after' => 0]);

// NOMOR, LAMPIRAN, HAL
$tglFormatted = formatTanggal($tanggalSurat);
$doc->addParagraph('Nomor    :  ' . $nomorSurat . "\t\t\t\t\t" . $tglFormatted, ['size' => 22, 'before' => 60, 'after' => 40]);
$doc->addParagraph('Lampiran :  ' . $lampiran, ['size' => 22, 'before' => 0, 'after' => 40]);
$doc->addParagraph('Hal      :  Usulan  CB dan PB PerMenKum HAM R.I No. 07 Tahun 2022', ['size' => 22, 'before' => 0, 'after' => 80]);

// TUJUAN
$doc->addParagraph('Yth. Direktur Jenderal Pemasyarakatan', ['size' => 22, 'before' => 60, 'after' => 20]);
$doc->addParagraph('     Kementerian Imigrasi dan Pemasyarakatan R.I.', ['size' => 22, 'before' => 0, 'after' => 20]);
$doc->addParagraph('          di', ['size' => 22, 'before' => 0, 'after' => 20]);
$doc->addParagraph('               J a k a r t a', ['size' => 22, 'before' => 0, 'after' => 100]);

// ISI
$isi1 = '          Dengan hormat kami sampaikan bahwa, dalam rangka kegiatan pembinaan narapidana di luar Lembaga Pemasyarakatan, kami bermaksud memberikan Cuti Bersyarat (CB) dan Pembebasan Bersyarat (PB) berdasarkan Peraturan Menteri Hukum dan HAM R.I Nomor 07 Tahun 2022  terhadap Narapidana yang telah memenuhi persyaratan substantif dan administratif.';
$doc->addParagraph($isi1, ['size' => 22, 'before' => 60, 'after' => 80]);

$isi2 = '          Sehubungan dengan hal tersebut, kami mengajukan permohonan dimaksud terhadap ' . $total . ' (' . terbilang($total) . ') orang Narapidana (daftar terlampir) dengan rincian sebagai berikut :';
$doc->addParagraph($isi2, ['size' => 22, 'before' => 0, 'after' => 60]);

$doc->addParagraph('     1. Cuti Bersyarat (CB)            : ' . count($cbList) . ' (' . terbilang(count($cbList)) . ' ) Orang', ['size' => 22, 'before' => 0, 'after' => 40]);
$doc->addParagraph('     2. Pembebasan Bersyarat (PB) : ' . count($pbList) . ' (' . terbilang(count($pbList)) . ' ) Orang', ['size' => 22, 'before' => 0, 'after' => 80]);

$isi3 = '          Demikian permohonan ini kami sampaikan, atas perhatian dan perkenan Bapak, kami ucapkan terimakasih.';
$doc->addParagraph($isi3, ['size' => 22, 'before' => 0, 'after' => 200]);

// TTD
$doc->addParagraph('K e p a l a', ['align' => 'right', 'size' => 22, 'before' => 0, 'after' => 20]);
$doc->addParagraph('', ['before' => 0, 'after' => 20]);
$doc->addParagraph('', ['before' => 0, 'after' => 20]);
$doc->addParagraph('', ['before' => 0, 'after' => 20]);
$doc->addParagraph($namaKalapas, ['align' => 'right', 'bold' => true, 'underline' => true, 'size' => 22, 'before' => 0, 'after' => 20]);

// TEMBUSAN
$doc->addParagraph('', ['before' => 0, 'after' => 60]);
$doc->addParagraph('Tembusan :', ['italic' => true, 'size' => 20, 'before' => 0, 'after' => 20]);
$doc->addParagraph('Yth. Direktorat Jenderal Pemasyarakatan Kantor Wilayah Jawa Barat', ['italic' => true, 'size' => 20, 'before' => 0, 'after' => 20]);
$doc->addParagraph('     Di - Bandung', ['italic' => true, 'size' => 20, 'before' => 0, 'after' => 0]);

$doc->download('Surat_Pengantar_CB_PB_' . date('Ymd') . '.docx');
