<?php
// surat/data_primer.php — Data Primer LITMAS
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth.php';

$pageTitle  = 'Data Primer LITMAS';
$activePage = 'surat_data_primer';
$breadcrumb = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Data Primer LITMAS']
];

$preselect = (int)($_GET['id'] ?? 0);
$allWBP = $pdo->query("SELECT id, nama, no_register, kegiatan FROM narapidana WHERE status='aktif' ORDER BY nama")->fetchAll();

$napiData = null;
$penjamin  = null;
$riwayat   = null;

if ($preselect) {
    $stmt = $pdo->prepare("SELECT * FROM narapidana WHERE id=?");
    $stmt->execute([$preselect]);
    $napiData = $stmt->fetch();

    if ($napiData) {
        $stmt2 = $pdo->prepare("SELECT * FROM penjamin WHERE narapidana_id=? ORDER BY urutan LIMIT 1");
        $stmt2->execute([$preselect]);
        $penjamin = $stmt2->fetch();

        $stmt3 = $pdo->prepare("SELECT * FROM riwayat_pembinaan WHERE narapidana_id=? LIMIT 1");
        $stmt3->execute([$preselect]);
        $riwayat = $stmt3->fetch();
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="surat-grid">

    <div class="surat-form-panel">
        <div class="card">
            <div class="card-header"><h2><i class="fas fa-folder-open"></i> Data Primer LITMAS</h2></div>
            <div class="card-body">
                <div class="form-group mb-16">
                    <label class="form-label required">Pilih WBP</label>
                    <select class="form-control" onchange="if(this.value) location.href='?id='+this.value">
                        <option value="">— Pilih WBP —</option>
                        <?php foreach ($allWBP as $w): ?>
                        <option value="<?= $w['id'] ?>" <?= $preselect===$w['id']?'selected':'' ?>>
                            <?= e($w['nama']) ?> (<?= e($w['no_register']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($napiData): ?>
                <div style="display:flex;flex-direction:column;gap:10px">
                    <a href="<?= BASE_URL ?>/generate/data_primer_word.php?id=<?= $preselect ?>" class="btn btn-gold">
                        <i class="fas fa-download"></i> Download Word
                    </a>
                    <a href="<?= BASE_URL ?>/narapidana/edit.php?id=<?= $preselect ?>" class="btn btn-outline">
                        <i class="fas fa-pen-to-square"></i> Edit Data WBP
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Preview Data Primer -->
    <div class="card">
        <div class="card-header"><h2><i class="fas fa-file-lines"></i> Preview Data Primer LITMAS</h2></div>
        <div class="card-body">
            <?php if (!$napiData): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-folder-open"></i></div>
                <h3>Pilih WBP untuk melihat data primer</h3>
                <p>Data Primer LITMAS akan tampil di sini</p>
            </div>
            <?php else: ?>

            <?php
            $lamaPidana = [];
            if ($napiData['lama_pidana_tahun']) $lamaPidana[] = $napiData['lama_pidana_tahun'] . ' ('. (function($n){$s=['','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan','sepuluh'];return $s[$n]??$n;})(min($napiData['lama_pidana_tahun'],10)) .') Tahun';
            if ($napiData['lama_pidana_bulan']) $lamaPidana[] = $napiData['lama_pidana_bulan'] . ' Bulan';
            if ($napiData['lama_pidana_hari'])  $lamaPidana[] = $napiData['lama_pidana_hari']  . ' Hari';
            if ($napiData['ekspirasi_pb'] ?? $napiData['ekspirasi']) $lamaPidana[] .= ' Denda ...';
            $lamaStr = implode(' ', $lamaPidana);
            ?>

            <div class="surat-preview" style="font-family:'Times New Roman',serif;font-size:12pt;line-height:1.8">

                <!-- KOP -->
                <div style="text-align:center;border-bottom:3px double #000;padding-bottom:10px;margin-bottom:14px">
                    <table style="width:100%;border:none">
                        <tr>
                            <td style="width:70px;text-align:center">
                                <img src="<?= BASE_URL ?>/logo.png" style="width:65px;height:65px;object-fit:contain">
                            </td>
                            <td style="text-align:center">
                                <div>KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN REPUBLIK INDONESIA</div>
                                <div>DIREKTORAT JENDERAL PEMASYARAKATAN</div>
                                <div><?= e(getSetting('kanwil')) ?></div>
                                <div style="font-weight:bold"><?= e(getSetting('nama_instansi')) ?></div>
                                <div style="font-size:9pt"><?= e(getSetting('alamat')) ?>, Kode Pos <?= e(getSetting('kode_pos')) ?> Telp. <?= e(getSetting('telp')) ?></div>
                                <div style="font-size:9pt">Laman : <?= e(getSetting('laman')) ?> Pos-el : <?= e(getSetting('email')) ?></div>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Header -->
                <div style="text-align:center;font-weight:bold;text-decoration:underline;margin:12px 0 4px">DATA PRIMER PENELITIAN KEMASYARAKATAN</div>
                <div style="text-align:center;font-weight:bold;text-decoration:underline;margin:0 0 16px">UNTUK PEMBINAAN DILUAR LEMBAGA PEMASYARAKATAN</div>

                <div>Terhadap &nbsp;&nbsp;&nbsp;&nbsp;: <strong><?= e($napiData['nama']) ?></strong></div>
                <div>Nomor Register : <?= e($napiData['no_register']) ?></div>
                <div>Perkara &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <?= e($napiData['perkara']) ?></div>

                <div style="margin:16px 0 10px;font-weight:bold;text-decoration:underline">I.&nbsp;&nbsp;IDENTITAS</div>

                <div style="font-weight:bold;text-decoration:underline;margin-left:20px">A. KLIEN</div>
                <table style="border:none;width:100%;margin-left:30px;margin-bottom:12px">
                    <?php
                    $rows = [
                        ['1. Nama', e($napiData['nama'])],
                        ['2. Tempat /Tanggal Lahir', e($napiData['tempat_lahir']) . ', ' . formatTanggal($napiData['tanggal_lahir'] ?? '')],
                        ['3. Jenis Kelamin', $napiData['jenis_kelamin'] === 'L' ? 'Laki Laki' : 'Perempuan'],
                        ['4. Agama', e($napiData['agama'])],
                        ['5. Pendidikan / Pekerjaan', e($napiData['pendidikan']) . ' / ' . e($napiData['pekerjaan'])],
                        ['6. Suku/Bangsa/WN', e($napiData['suku']) . '  /  ' . e($napiData['bangsa_wn'])],
                        ['7. Status Perkawinan', e($napiData['status_perkawinan'])],
                        ['8. Saat ini dibina di', 'LAPAS Kelas IIB Tasikmalaya'],
                        ['9. Lama Pidana', e($lamaStr)],
                        ['10. Putusan PN', e($napiData['putusan_pn'])],
                        ['11. Perkara', e($napiData['perkara'])],
                        ['12a. Tgl 1/3 masa pidana', formatTanggal($napiData['tanggal_1_3'] ?? '')],
                        ['12b. Tgl 1/2 masa pidana', formatTanggal($napiData['tanggal_1_2'] ?? '')],
                        ['12c. Tgl 2/3 masa pidana', formatTanggal($napiData['tanggal_2_3'] ?? '')],
                        ['13. Ekspirasi', formatTanggal($napiData['ekspirasi'] ?? '')],
                        ['14. Expirasi (PB)', formatTanggal($napiData['ekspirasi_pb'] ?? $napiData['tanggal_2_3'] ?? '')],
                        ['15. Kegunaan', 'Usulan ' . ($napiData['kegiatan'] === 'PB' ? 'Pembebasan Bersyarat ( PB )' : 'Cuti Bersyarat ( CB )')],
                    ];
                    foreach ($rows as [$label, $val]):
                    ?>
                    <tr>
                        <td style="width:260px;vertical-align:top"><?= $label ?></td>
                        <td style="width:20px;vertical-align:top">:</td>
                        <td><?= $val ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>

                <?php if ($penjamin): ?>
                <div style="font-weight:bold;text-decoration:underline;margin-left:20px;margin-bottom:8px">B. Penanggung Jawab /Penjamin ( <?= e($penjamin['hubungan']) ?> )</div>
                <table style="border:none;width:100%;margin-left:30px;margin-bottom:16px">
                    <?php
                    $pjRows = [
                        ['1. Nama', e($penjamin['nama'])],
                        ['2. Tempat / Tanggal Lahir', e($penjamin['tempat_lahir']) . ', ' . formatTanggal($penjamin['tanggal_lahir'] ?? '')],
                        ['3. Agama', e($penjamin['agama'])],
                        ['4. Suku/bangsa/WN', e($penjamin['suku']) . ' / ' . e($penjamin['bangsa_wn'])],
                        ['5. Pendidikan', e($penjamin['pendidikan'])],
                        ['6. Pekerjaan', e($penjamin['pekerjaan'])],
                        ['7. Hubungan Dengan klien', e($penjamin['hubungan'])],
                        ['8. Alamat', e($penjamin['alamat'])],
                        ['9. HP Penjamin', e($penjamin['hp'])],
                        ['10. Bentuk Jaminan', e($penjamin['bentuk_jaminan'])],
                    ];
                    foreach ($pjRows as [$label, $val]):
                    ?>
                    <tr>
                        <td style="width:260px;vertical-align:top"><?= $label ?></td>
                        <td style="width:20px;vertical-align:top">:</td>
                        <td><?= $val ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php endif; ?>

                <?php if ($riwayat): ?>
                <div style="margin:16px 0 10px;font-weight:bold;text-decoration:underline">II.&nbsp;&nbsp;MASALAH YANG DIHADAPI KLIEN</div>

                <div style="font-weight:bold;text-decoration:underline;margin-left:20px;margin-bottom:8px">A. Masalah dan Perkembangan selama di LAPAS</div>

                <?php if ($riwayat['perilaku_pribadi']): ?>
                <div style="margin-left:30px;margin-bottom:10px">
                    <div style="font-weight:500">1. Pribadi Klien</div>
                    <div style="text-align:justify;margin-left:20px"><?= nl2br(e($riwayat['perilaku_pribadi'])) ?></div>
                </div>
                <?php endif; ?>

                <?php if ($riwayat['kesehatan']): ?>
                <div style="margin-left:30px;margin-bottom:10px">
                    <div style="font-weight:500">2. Kesehatan</div>
                    <div style="text-align:justify;margin-left:20px"><?= nl2br(e($riwayat['kesehatan'])) ?></div>
                </div>
                <?php endif; ?>

                <?php if ($riwayat['kegemaran']): ?>
                <div style="margin-left:30px;margin-bottom:10px">
                    <div style="font-weight:500">3. Kegemaran/Hobby</div>
                    <div style="text-align:justify;margin-left:20px"><?= nl2br(e($riwayat['kegemaran'])) ?></div>
                </div>
                <?php endif; ?>

                <?php if ($riwayat['cita_cita']): ?>
                <div style="margin-left:30px;margin-bottom:10px">
                    <div style="font-weight:500">4. Cita-cita dan Harapan</div>
                    <div style="text-align:justify;margin-left:20px"><?= nl2br(e($riwayat['cita_cita'])) ?></div>
                </div>
                <?php endif; ?>

                <?php if ($riwayat['pendidikan_ketrampilan']): ?>
                <div style="margin-left:30px;margin-bottom:10px">
                    <div style="font-weight:500">5. Pendidikan dan Ketrampilan yang diperoleh selama dalam Lapas</div>
                    <div style="text-align:justify;margin-left:20px"><?= nl2br(e($riwayat['pendidikan_ketrampilan'])) ?></div>
                </div>
                <?php endif; ?>

                <?php if ($riwayat['hubungan_petugas'] || $riwayat['hubungan_sesama'] || $riwayat['hubungan_keluarga']): ?>
                <div style="margin-left:30px;margin-bottom:10px">
                    <div style="font-weight:500">6. Hubungan Sosial dengan Petugas, Penghuni dan Keluarga</div>
                    <?php if ($riwayat['hubungan_petugas']): ?>
                    <div style="margin-left:20px">a. Hubungan dengan petugas<br><span style="margin-left:20px"><?= nl2br(e($riwayat['hubungan_petugas'])) ?></span></div>
                    <?php endif; ?>
                    <?php if ($riwayat['hubungan_sesama']): ?>
                    <div style="margin-left:20px">b. Hubungan dengan sesama penghuni<br><span style="margin-left:20px"><?= nl2br(e($riwayat['hubungan_sesama'])) ?></span></div>
                    <?php endif; ?>
                    <?php if ($riwayat['hubungan_keluarga']): ?>
                    <div style="margin-left:20px">c. Hubungan dengan Keluarga<br><span style="margin-left:20px"><?= nl2br(e($riwayat['hubungan_keluarga'])) ?></span></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ($riwayat['tahap_1_3'] || $riwayat['tahap_1_2'] || $riwayat['tahap_2_3']): ?>
                <div style="font-weight:bold;text-decoration:underline;margin-left:20px;margin-top:14px;margin-bottom:8px">B. Data (Record) Pembinaan Klien</div>

                <?php if ($riwayat['tahap_1_3']): ?>
                <div style="margin-left:30px;margin-bottom:8px">
                    <div>1. Tahap 1/3 Masa Pidana (Admisi Orentasi)</div>
                    <div style="margin-left:20px"><?= nl2br(e($riwayat['tahap_1_3'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if ($riwayat['tahap_1_2']): ?>
                <div style="margin-left:30px;margin-bottom:8px">
                    <div>2. Tahap ½ Masa Pidana (Tahap Asimilasi)</div>
                    <div style="margin-left:20px"><?= nl2br(e($riwayat['tahap_1_2'])) ?></div>
                </div>
                <?php endif; ?>
                <?php if ($riwayat['tahap_2_3']): ?>
                <div style="margin-left:30px;margin-bottom:8px">
                    <div>3. Tahap 2/3 Masa Pidana (Tahap Integrasi)</div>
                    <div style="margin-left:20px"><?= nl2br(e($riwayat['tahap_2_3'])) ?></div>
                </div>
                <?php endif; ?>
                <?php endif; ?>

                <?php if ($napiData['tempat_asimilasi']): ?>
                <div style="font-weight:bold;text-decoration:underline;margin-left:20px;margin-top:14px;margin-bottom:8px">D.&nbsp;&nbsp;TEMPAT PELAKSANAAN ASIMILASI</div>
                <div style="margin-left:30px;margin-bottom:16px"><?= e($napiData['tempat_asimilasi']) ?></div>
                <?php endif; ?>

                <?php endif; // end if riwayat ?>

                <!-- PENUTUP -->
                <div style="margin:16px 0 10px;font-weight:bold;text-decoration:underline">III.&nbsp;&nbsp;PENUTUP</div>
                <div style="text-align:justify;margin-left:20px;margin-bottom:20px">
                    Demikian risalah (data primer) ini untuk dijadikan bahan pelaksanaan Penelitian Kemasyarakatan (LITMAS) klien yang bersangkutan.
                </div>

                <!-- TTD -->
                <table style="width:100%;border:none;margin-top:16px">
                    <tr>
                        <td style="width:50%;text-align:center">Mengetahui ;<br>Kepala</td>
                        <td style="text-align:center">Tasikmalaya, <?= formatTanggal(date('Y-m-d')) ?><br>Wali  Pemasyarakatan</td>
                    </tr>
                    <tr>
                        <td style="text-align:center;padding:50px 0 0">
                            <strong><u><?= e(getSetting('nama_kalapas')) ?></u></strong>
                        </td>
                        <td style="text-align:center;padding:50px 0 0">
                            <strong><u><?= e($riwayat['wali_pemasyarakatan'] ?? 'WALI PEMASYARAKATAN') ?></u></strong>
                        </td>
                    </tr>
                </table>

            </div>
            <?php endif; // end if napiData ?>
        </div>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
