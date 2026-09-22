<?php
// dashboard.php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/auth.php';

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
$breadcrumb = [['label' => 'Dashboard']];

$totalWBP = $pdo->query("SELECT COUNT(*) FROM narapidana WHERE status = 'aktif'")->fetchColumn();
$totalCB  = $pdo->query("SELECT COUNT(*) FROM narapidana WHERE status = 'aktif' AND kegiatan = 'CB'")->fetchColumn();
$totalPB  = $pdo->query("SELECT COUNT(*) FROM narapidana WHERE status = 'aktif' AND kegiatan = 'PB'")->fetchColumn();
$totalCMB = $pdo->query("SELECT COUNT(*) FROM narapidana WHERE status = 'aktif' AND kegiatan = 'CMB'")->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM narapidana WHERE status = 'aktif' AND ekspirasi BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
$ekspirasiBulanIni = $stmt->fetchColumn();

$stmtSidang = $pdo->query("SELECT s.*, COUNT(sn.id) as jumlah_napi FROM sidang_tpp s LEFT JOIN sidang_narapidana sn ON s.id = sn.sidang_id WHERE s.tanggal_sidang >= CURDATE() GROUP BY s.id ORDER BY s.tanggal_sidang ASC LIMIT 5");
$sidangMendatang = $stmtSidang->fetchAll();

$stmtEkspirasi = $pdo->query("SELECT nama, no_register, kegiatan, ekspirasi FROM narapidana WHERE status = 'aktif' AND ekspirasi BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY ekspirasi ASC LIMIT 8");
$napiEkspirasi = $stmtEkspirasi->fetchAll();

$stmtTerbaru = $pdo->query("SELECT nama, no_register, kegiatan, created_at FROM narapidana WHERE status = 'aktif' ORDER BY created_at DESC LIMIT 5");
$napiTerbaru = $stmtTerbaru->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card navy">
        <div class="stat-icon"><i class="fas fa-users fa-lg"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $totalWBP ?></div>
            <div class="stat-label">Total WBP Aktif</div>
        </div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon"><i class="fas fa-house fa-lg"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $totalCB ?></div>
            <div class="stat-label">Cuti Bersyarat (CB)</div>
        </div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon"><i class="fas fa-dove fa-lg"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $totalPB ?></div>
            <div class="stat-label">Pembebasan Bersyarat (PB)</div>
        </div>
    </div>
    <div class="stat-card gold">
        <div class="stat-icon"><i class="fas fa-clipboard-list fa-lg"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $totalCMB ?></div>
            <div class="stat-label">CMB</div>
        </div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon"><i class="fas fa-clock fa-lg"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= $ekspirasiBulanIni ?></div>
            <div class="stat-label">Ekspirasi &le; 30 Hari</div>
        </div>
    </div>
</div>

<!-- Content Grid -->
<div class="dashboard-grid-2">

    <!-- WBP Mendekati Ekspirasi -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-clock" style="color:var(--danger)"></i> WBP Mendekati Ekspirasi</h2>
            <a href="<?= BASE_URL ?>/narapidana/index.php?filter=ekspirasi" class="btn btn-outline btn-sm">Lihat Semua</a>
        </div>
        <div class="card-body" style="padding:0">
            <?php if (empty($napiEkspirasi)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-circle-check" style="font-size:48px;color:var(--success)"></i></div>
                <h3>Tidak ada WBP</h3>
                <p>yang ekspirasi dalam 30 hari ke depan</p>
            </div>
            <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama / No. Reg</th>
                        <th>Kategori</th>
                        <th>Ekspirasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($napiEkspirasi as $n): ?>
                    <?php
                        $hariSisa = (int)((strtotime($n['ekspirasi']) - time()) / 86400);
                        $warna = $hariSisa <= 7 ? 'danger' : ($hariSisa <= 14 ? 'warning' : 'info');
                    ?>
                    <tr>
                        <td>
                            <div style="font-weight:600;font-size:13px"><?= e($n['nama']) ?></div>
                            <div style="color:var(--text-muted);font-size:11px"><?= e($n['no_register']) ?></div>
                        </td>
                        <td><span class="badge badge-<?= strtolower($n['kegiatan']) ?>"><?= e($n['kegiatan']) ?></span></td>
                        <td>
                            <div style="font-size:12px"><?= formatTanggal($n['ekspirasi']) ?></div>
                            <span class="badge badge-<?= $warna ?>" style="font-size:10px"><?= $hariSisa ?> hari lagi</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidang TPP Mendatang -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-scale-balanced" style="color:var(--primary)"></i> Sidang TPP Mendatang</h2>
            <a href="<?= BASE_URL ?>/sidang/tambah.php" class="btn btn-gold btn-sm">
                <i class="fas fa-plus"></i> Buat Sidang
            </a>
        </div>
        <div class="card-body" style="padding:0">
            <?php if (empty($sidangMendatang)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-calendar-xmark" style="font-size:48px;opacity:0.3"></i></div>
                <h3>Belum ada sidang</h3>
                <p>yang dijadwalkan</p>
            </div>
            <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Perihal</th>
                        <th>WBP</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sidangMendatang as $s): ?>
                    <tr>
                        <td style="font-size:12px;white-space:nowrap"><?= formatTanggal($s['tanggal_sidang']) ?></td>
                        <td style="font-size:12px;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($s['perihal']) ?></td>
                        <td><span class="badge badge-pb"><?= $s['jumlah_napi'] ?> WBP</span></td>
                        <td>
                            <a href="<?= BASE_URL ?>/sidang/edit.php?id=<?= $s['id'] ?>" class="btn btn-outline btn-sm">
                                <i class="fas fa-pen-to-square"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- WBP Terbaru & Quick Actions -->
<div class="dashboard-grid-21">

    <!-- WBP Terbaru -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-star" style="color:var(--accent)"></i> WBP Terbaru Ditambahkan</h2>
            <a href="<?= BASE_URL ?>/narapidana/tambah.php" class="btn btn-primary btn-sm">
                <i class="fas fa-user-plus"></i> Tambah WBP
            </a>
        </div>
        <div class="card-body" style="padding:0">
            <?php if (empty($napiTerbaru)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-users" style="font-size:48px;opacity:0.3"></i></div>
                <h3>Belum ada data</h3>
                <p>Mulai tambahkan data narapidana</p>
            </div>
            <?php else: ?>
            <table class="data-table">
                <thead><tr><th>Nama</th><th>No. Register</th><th>Kategori</th><th>Ditambahkan</th></tr></thead>
                <tbody>
                    <?php foreach ($napiTerbaru as $n): ?>
                    <tr>
                        <td style="font-weight:500"><?= e($n['nama']) ?></td>
                        <td style="font-size:12px;color:var(--text-muted)"><?= e($n['no_register']) ?></td>
                        <td><span class="badge badge-<?= strtolower($n['kegiatan']) ?>"><?= e($n['kegiatan']) ?></span></td>
                        <td style="font-size:11px;color:var(--text-muted)"><?= date('d/m/Y', strtotime($n['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-bolt" style="color:var(--accent)"></i> Aksi Cepat</h2>
        </div>
        <div class="card-body">
            <div style="display:flex;flex-direction:column;gap:10px">
                <a href="<?= BASE_URL ?>/narapidana/tambah.php" class="btn btn-primary" style="justify-content:flex-start">
                    <i class="fas fa-user-plus"></i> Tambah Data WBP
                </a>
                <a href="<?= BASE_URL ?>/sidang/tambah.php" class="btn btn-outline" style="justify-content:flex-start">
                    <i class="fas fa-scale-balanced"></i> Buat Sidang TPP
                </a>
                <a href="<?= BASE_URL ?>/surat/pengantar_cb_pb.php" class="btn btn-outline" style="justify-content:flex-start">
                    <i class="fas fa-envelope-open-text"></i> Surat Pengantar CB/PB
                </a>
                <a href="<?= BASE_URL ?>/surat/undangan_tpp.php" class="btn btn-outline" style="justify-content:flex-start">
                    <i class="fas fa-envelope"></i> Undangan Sidang TPP
                </a>
                <a href="<?= BASE_URL ?>/surat/daftar_tpp.php" class="btn btn-outline" style="justify-content:flex-start">
                    <i class="fas fa-table-list"></i> Daftar WBP Sidang
                </a>
                <a href="<?= BASE_URL ?>/surat/data_primer.php" class="btn btn-gold" style="justify-content:flex-start">
                    <i class="fas fa-folder-open"></i> Data Primer LITMAS
                </a>
            </div>
        </div>
    </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
