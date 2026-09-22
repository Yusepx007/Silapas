<?php
// sidang/index.php — Daftar Sidang TPP
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth.php';

$pageTitle  = 'Daftar Sidang TPP';
$activePage = 'sidang_list';
$breadcrumb = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Sidang TPP']
];

$stmt = $pdo->query("SELECT s.*, COUNT(sn.id) AS jumlah_napi FROM sidang_tpp s LEFT JOIN sidang_narapidana sn ON s.id = sn.sidang_id GROUP BY s.id ORDER BY s.tanggal_sidang DESC");
$sidangList = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-between align-center mb-20" style="flex-wrap:wrap;gap:12px">
    <div></div>
    <a href="<?= BASE_URL ?>/sidang/tambah.php" class="btn btn-gold"><i class="fas fa-plus"></i> Buat Sidang TPP Baru</a>
</div>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-scale-balanced"></i> Daftar Sidang TPP (<?= count($sidangList) ?>)</h2>
    </div>
    <div class="card-body" style="padding:0">
        <?php if (empty($sidangList)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-scale-balanced"></i></div>
            <h3>Belum ada sidang TPP</h3>
            <p>Buat sidang TPP baru untuk mulai mengkelola</p>
            <a href="<?= BASE_URL ?>/sidang/tambah.php" class="btn btn-primary"><i class="fas fa-plus"></i> Buat Sidang Baru</a>
        </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nomor Surat</th>
                        <th>Tanggal Surat</th>
                        <th>Tanggal Sidang</th>
                        <th>Perihal</th>
                        <th>WBP</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sidangList as $s): ?>
                    <?php $isPast = strtotime($s['tanggal_sidang']) < strtotime('today'); ?>
                    <tr>
                        <td style="font-size:12px;font-weight:500"><?= e($s['nomor_surat']) ?></td>
                        <td style="font-size:12px"><?= formatTanggal($s['tanggal_surat']) ?></td>
                        <td style="font-size:12px">
                            <?= formatTanggal($s['tanggal_sidang'], true) ?>
                            <?php if ($isPast): ?>
                            <br><span class="badge badge-danger" style="font-size:9px">Sudah lewat</span>
                            <?php else: ?>
                            <br><span class="badge badge-pb" style="font-size:9px">Mendatang</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:12px;max-width:200px"><?= e(mb_substr($s['perihal'] ?? '', 0, 80)) ?></td>
                        <td><span class="badge badge-cb"><?= $s['jumlah_napi'] ?> WBP</span></td>
                        <td>
                            <div class="action-group">
                                <a href="<?= BASE_URL ?>/sidang/edit.php?id=<?= $s['id'] ?>" class="btn btn-primary btn-sm" data-tooltip="Edit"><i class="fas fa-pen-to-square"></i></a>
                                <a href="<?= BASE_URL ?>/surat/undangan_tpp.php?sidang_id=<?= $s['id'] ?>" class="btn btn-info btn-sm" data-tooltip="Undangan"><i class="fas fa-envelope"></i></a>
                                <a href="<?= BASE_URL ?>/surat/daftar_tpp.php?sidang_id=<?= $s['id'] ?>" class="btn btn-success btn-sm" data-tooltip="Daftar WBP"><i class="fas fa-chart-bar"></i></a>
                                <a href="<?= BASE_URL ?>/sidang/hapus.php?id=<?= $s['id'] ?>" class="btn btn-danger btn-sm" data-tooltip="Hapus"
                                   onclick="return confirm('Hapus sidang ini?')"><i class="fas fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
