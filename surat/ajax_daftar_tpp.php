<?php
// surat/ajax_daftar_tpp.php — AJAX handler untuk preview daftar TPP
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) { http_response_code(401); exit; }

$sidangId = (int)($_GET['sidang_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM sidang_tpp WHERE id=?");
$stmt->execute([$sidangId]);
$sidang = $stmt->fetch();
if (!$sidang) { echo '<div class="alert alert-danger">Sidang tidak ditemukan.</div>'; exit; }

// Ambil WBP dalam sidang
$stmtN = $pdo->prepare("SELECT n.* FROM narapidana n JOIN sidang_narapidana sn ON n.id = sn.narapidana_id WHERE sn.sidang_id = ? ORDER BY n.kegiatan, n.nama");
$stmtN->execute([$sidangId]);
$napiList = $stmtN->fetchAll();

$cbList = array_filter($napiList, fn($n) => $n['kegiatan'] === 'CB');
$pbList = array_filter($napiList, fn($n) => $n['kegiatan'] === 'PB');
?>

<div style="font-family:'Times New Roman',serif;font-size:11pt;line-height:1.6">

    <!-- KOP -->
    <div style="text-align:center;margin-bottom:12px">
        <table style="width:100%;border:none">
            <tr>
                <td style="width:70px;text-align:center">
                    <img src="<?= BASE_URL ?>/logo_kop.png" style="width:60px;height:60px;object-fit:contain">
                </td>
                <td style="text-align:center">
                    <div>KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN R.I</div>
                    <div>DIREKTORAT JENDERAL PEMASYARAKATAN</div>
                    <div><?= e(getSetting('kanwil')) ?></div>
                    <div style="font-weight:bold"><?= e(getSetting('nama_instansi')) ?></div>
                    <div style="font-size:9pt">Jl. Oto Iskandardinata No.01 Kota Tasikmalaya</div>
                    <div style="font-size:9pt">Telp. <?= e(getSetting('telp')) ?> Email: <?= e(getSetting('email')) ?></div>
                </td>
            </tr>
        </table>
    </div>

    <div style="text-align:center;font-weight:bold;font-size:12pt;margin:16px 0 4px">
        DAFTAR NARAPIDANA YANG MENGIKUTI
    </div>
    <div style="text-align:center;font-weight:bold;font-size:12pt;margin:0 0 4px">
        USULAN PROGRAM INTEGRASI PB (PEMBEBASAN BERSYARAT) DAN CB ( CUTI BERSYARAT)
    </div>
    <div style="text-align:center;font-weight:bold;font-size:12pt;margin:0 0 16px">
        DALAM SIDANG TIM PENGAMAT PEMASYARAKATAN (TPP)
    </div>

    <!-- Tabel WBP -->
    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:10pt">
            <thead>
                <tr style="background:#0B2545;color:#fff">
                    <th style="border:1px solid #000;padding:6px 4px;text-align:center;width:30px">No</th>
                    <th style="border:1px solid #000;padding:6px 4px;text-align:left">N a m a</th>
                    <th style="border:1px solid #000;padding:6px 4px;text-align:center;width:80px">No Reg</th>
                    <th style="border:1px solid #000;padding:6px 4px;text-align:left">Pasal</th>
                    <th style="border:1px solid #000;padding:6px 4px;text-align:center;width:35px">Thn</th>
                    <th style="border:1px solid #000;padding:6px 4px;text-align:center;width:35px">Bln</th>
                    <th style="border:1px solid #000;padding:6px 4px;text-align:center;width:35px">Hr</th>
                    <th style="border:1px solid #000;padding:6px 4px;text-align:left;width:140px">Pentahapan</th>
                    <th style="border:1px solid #000;padding:6px 4px;text-align:center;width:60px">Kegiatan</th>
                    <th style="border:1px solid #000;padding:6px 4px;text-align:center;width:80px">Foto</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($napiList as $i => $n): ?>
                <tr style="<?= $i%2===0?'':'background:#f8f9fa' ?>">
                    <td style="border:1px solid #000;padding:6px 4px;text-align:center;vertical-align:top"><?= $i+1 ?></td>
                    <td style="border:1px solid #000;padding:6px 4px;font-weight:bold;vertical-align:top"><?= e($n['nama']) ?></td>
                    <td style="border:1px solid #000;padding:6px 4px;text-align:center;vertical-align:top;font-size:9pt"><?= e($n['no_register']) ?></td>
                    <td style="border:1px solid #000;padding:6px 4px;font-size:9pt;vertical-align:top"><?= nl2br(e($n['pasal'])) ?></td>
                    <td style="border:1px solid #000;padding:6px 4px;text-align:center;vertical-align:top"><?= $n['lama_pidana_tahun'] ?: '-' ?></td>
                    <td style="border:1px solid #000;padding:6px 4px;text-align:center;vertical-align:top"><?= $n['lama_pidana_bulan'] ?: '-' ?></td>
                    <td style="border:1px solid #000;padding:6px 4px;text-align:center;vertical-align:top"><?= $n['lama_pidana_hari'] ?: '-' ?></td>
                    <td style="border:1px solid #000;padding:6px 4px;font-size:9pt;vertical-align:top">
                        <?php if ($n['tanggal_1_3']): ?>1/3: <?= date('d/m/Y', strtotime($n['tanggal_1_3'])) ?><br><?php endif; ?>
                        <?php if ($n['tanggal_1_2']): ?>1/2: <?= date('d/m/Y', strtotime($n['tanggal_1_2'])) ?><br><?php endif; ?>
                        <?php if ($n['tanggal_2_3']): ?>2/3: <?= date('d/m/Y', strtotime($n['tanggal_2_3'])) ?><br><?php endif; ?>
                        <?php if ($n['ekspirasi']):   ?>Eks. <?= date('d/m/Y', strtotime($n['ekspirasi'])) ?><?php endif; ?>
                    </td>
                    <td style="border:1px solid #000;padding:6px 4px;text-align:center;vertical-align:top;font-weight:bold"><?= e($n['kegiatan']) ?></td>
                    <td style="border:1px solid #000;padding:4px;text-align:center;vertical-align:top">
                        <?php if ($n['foto'] && file_exists(UPLOAD_PATH . $n['foto'])): ?>
                        <img src="<?= UPLOAD_URL . e($n['foto']) ?>" style="width:60px;height:75px;object-fit:cover" alt="">
                        <?php else: ?>
                        <div style="width:60px;height:75px;background:#f0f0f0;display:inline-flex;align-items:center;justify-content:center;font-size:20px"><i class="fas fa-user"></i></div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Keterangan -->
    <div style="margin-top:14px">
        <div style="font-weight:bold">Keterangan</div>
        <div>Pembebasan Bersyarat (PB) : <?= count($pbList) ?> Orang WBP</div>
        <div>Cuti Bersyarat (CB) &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <?= count($cbList) ?> Orang WBP</div>
    </div>

    <!-- TTD -->
    <div style="margin-top:24px">
        <table style="width:100%;border:none">
            <tr>
                <td style="width:50%"></td>
                <td style="text-align:center">Mengetahui ;<br><strong>Kalapas</strong></td>
            </tr>
            <tr>
                <td></td>
                <td style="text-align:center;padding:40px 0 0">
                    <strong><u><?= e(getSetting('nama_kalapas')) ?></u></strong>
                </td>
            </tr>
        </table>
    </div>
</div>
