<?php
// narapidana/detail.php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { redirect(BASE_URL . '/narapidana/index.php'); }

$stmt = $pdo->prepare("SELECT * FROM narapidana WHERE id = ?");
$stmt->execute([$id]);
$n = $stmt->fetch();
if (!$n) {
    setFlash('danger', 'Data tidak ditemukan.');
    redirect(BASE_URL . '/narapidana/index.php');
}

$stmtPj = $pdo->prepare("SELECT * FROM penjamin WHERE narapidana_id = ? ORDER BY urutan ASC");
$stmtPj->execute([$id]);
$penjaminList = $stmtPj->fetchAll();
$penjamin = $penjaminList[0] ?? [];

$stmtRw = $pdo->prepare("SELECT * FROM riwayat_pembinaan WHERE narapidana_id = ? LIMIT 1");
$stmtRw->execute([$id]);
$riwayat = $stmtRw->fetch() ?: [];

$pageTitle  = 'Detail WBP: ' . $n['nama'];
$activePage = 'napi_list';
$breadcrumb = [
    ['label' => 'Dashboard',       'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Data Narapidana', 'url' => BASE_URL . '/narapidana/index.php'],
    ['label' => $n['nama']]
];

include __DIR__ . '/../includes/header.php';

// Helper
function field(string $label, ?string $value, string $colSpan = '1'): void {
    $v = $value ? htmlspecialchars($value) : '<span style="color:#9CA3AF">—</span>';
    echo '<div style="grid-column:span ' . $colSpan . ';padding:10px 0;border-bottom:1px solid var(--border)">';
    echo '<div style="font-size:11px;color:var(--text-muted);font-weight:500;text-transform:uppercase;letter-spacing:0.3px;margin-bottom:3px">' . htmlspecialchars($label) . '</div>';
    echo '<div style="font-size:13px;color:var(--text)">' . $v . '</div>';
    echo '</div>';
}
?>

<!-- Action Bar -->
<div class="d-flex gap-12 mb-20" style="flex-wrap:wrap">
    <a href="<?= BASE_URL ?>/narapidana/edit.php?id=<?= $id ?>" class="btn btn-gold"><i class="fas fa-pen-to-square"></i> Edit Data</a>
    <a href="<?= BASE_URL ?>/surat/pernyataan.php?id=<?= $id ?>" class="btn btn-primary"><i class="fas fa-file-pen"></i> Surat Pernyataan</a>
    <a href="<?= BASE_URL ?>/surat/data_primer.php?id=<?= $id ?>" class="btn btn-primary"><i class="fas fa-folder-open"></i> Data Primer LITMAS</a>
    <a href="<?= BASE_URL ?>/narapidana/index.php" class="btn btn-outline" style="margin-left:auto">← Kembali</a>
    <a href="<?= BASE_URL ?>/narapidana/hapus.php?id=<?= $id ?>"
       class="btn btn-danger"
       onclick="return confirm('Hapus data <?= addslashes($n['nama']) ?>? Tindakan ini tidak bisa dibatalkan!')"><i class="fas fa-trash"></i> Hapus</a>
</div>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:20px;align-items:start">

    <!-- Kolom Kiri: Foto + Info Singkat -->
    <div style="display:flex;flex-direction:column;gap:16px">

        <div class="card">
            <div class="card-body" style="text-align:center;padding:28px 20px">
                <?php if ($n['foto']): ?>
                <img src="<?= UPLOAD_URL . e($n['foto']) ?>"
                     alt="Foto <?= e($n['nama']) ?>"
                     style="width:150px;height:185px;object-fit:cover;border-radius:10px;border:3px solid var(--accent);box-shadow:var(--shadow-lg)"
                     onerror="this.style.display='none'">
                <?php else: ?>
                <div style="width:150px;height:185px;border-radius:10px;background:var(--bg);border:2px dashed var(--border);display:flex;align-items:center;justify-content:center;font-size:48px;margin:0 auto"><i class="fas fa-user"></i></div>
                <?php endif; ?>
                <h2 style="font-size:16px;font-weight:700;margin-top:16px;line-height:1.3"><?= e($n['nama']) ?></h2>
                <div style="color:var(--primary);font-size:13px;font-weight:600;margin-top:4px"><?= e($n['no_register']) ?></div>
                <div style="margin-top:10px">
                    <span class="badge badge-<?= strtolower($n['kegiatan']) ?>" style="font-size:13px;padding:5px 16px"><?= e($n['kegiatan']) ?></span>
                </div>
            </div>
        </div>

        <!-- Tahapan -->
        <div class="card">
            <div class="card-header"><h2><i class="fas fa-calendar-days"></i> Tahapan Masa Pidana</h2></div>
            <div class="card-body">
                <?php
                $stages = [
                    '1/3 MP' => $n['tanggal_1_3'],
                    '1/2 MP' => $n['tanggal_1_2'],
                    '2/3 MP' => $n['tanggal_2_3'],
                    'Ekspirasi' => $n['ekspirasi'],
                ];
                foreach ($stages as $label => $tgl):
                    $isPast = $tgl && strtotime($tgl) < time();
                    $isSoon = $tgl && !$isPast && (strtotime($tgl) - time()) < 30 * 86400;
                ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border)">
                    <span style="font-size:13px;font-weight:500"><?= $label ?></span>
                    <span style="font-size:12px;color:<?= $isPast ? 'var(--text-muted)' : ($isSoon ? 'var(--danger)' : 'var(--success)') ?>;font-weight:600">
                        <?= $tgl ? formatTanggal($tgl) : '—' ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <!-- Kolom Kanan: Detail Lengkap -->
    <div style="display:flex;flex-direction:column;gap:16px">

        <!-- Data Pribadi -->
        <div class="card">
            <div class="card-header"><h2><i class="fas fa-user"></i> Data Pribadi</h2></div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 24px">
                    <?php
                    field('Jenis Kelamin', $n['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan');
                    field('Tempat / Tanggal Lahir', trim(($n['tempat_lahir'] ?? '') . ', ' . formatTanggal($n['tanggal_lahir'] ?? '')), '2');
                    field('Agama', $n['agama']);
                    field('Status Perkawinan', $n['status_perkawinan']);
                    field('Pendidikan', $n['pendidikan']);
                    field('Pekerjaan', $n['pekerjaan']);
                    field('Suku / Bangsa', $n['suku'] . ' / ' . $n['bangsa_wn']);
                    field('Saat ini di Lapas', getSetting('nama_instansi'));
                    field('Alamat', $n['alamat'], '2');
                    ?>
                </div>
            </div>
        </div>

        <!-- Data Pidana -->
        <div class="card">
            <div class="card-header"><h2><i class="fas fa-scale-balanced"></i> Data Pidana</h2></div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 24px">
                    <?php
                    $lamaPidana = [];
                    if ($n['lama_pidana_tahun']) $lamaPidana[] = $n['lama_pidana_tahun'] . ' Tahun';
                    if ($n['lama_pidana_bulan']) $lamaPidana[] = $n['lama_pidana_bulan'] . ' Bulan';
                    if ($n['lama_pidana_hari'])  $lamaPidana[] = $n['lama_pidana_hari']  . ' Hari';
                    field('Pasal / Dakwaan', $n['pasal'], '2');
                    field('Perkara / UU', $n['perkara'], '2');
                    field('Lama Pidana', $lamaPidana ? implode(' ', $lamaPidana) : '-');
                    field('Putusan PN', $n['putusan_pn']);
                    field('Tanggal Masuk LAPAS', formatTanggal($n['tanggal_masuk'] ?? ''));
                    field('Kegiatan', $n['kegiatan']);
                    ?>
                </div>
            </div>
        </div>

        <!-- Penjamin -->
        <?php if ($penjamin): ?>
        <div class="card">
            <div class="card-header"><h2><i class="fas fa-people-group"></i> Data Penjamin / Penanggung Jawab</h2></div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 24px">
                    <?php
                    $pjTtl = trim(($penjamin['tempat_lahir'] ?? '') . ', ' . formatTanggal($penjamin['tanggal_lahir'] ?? ''));
                    field('Nama Penjamin', $penjamin['nama']);
                    field('Hubungan', $penjamin['hubungan']);
                    field('Tempat / Tgl Lahir', $pjTtl);
                    field('Agama', $penjamin['agama']);
                    field('Pendidikan', $penjamin['pendidikan']);
                    field('Pekerjaan', $penjamin['pekerjaan']);
                    field('No. HP', $penjamin['hp']);
                    field('Alamat', $penjamin['alamat'], '2');
                    field('Bentuk Jaminan', $penjamin['bentuk_jaminan'], '2');
                    ?>
                </div>
                <?php if ($n['tempat_asimilasi']): ?>
                <div style="margin-top:12px;padding:12px;background:var(--bg);border-radius:8px">
                    <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600;margin-bottom:4px">Tempat Pelaksanaan Asimilasi</div>
                    <div style="font-size:13px"><?= e($n['tempat_asimilasi']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Riwayat Pembinaan -->
        <?php if ($riwayat): ?>
        <div class="card">
            <div class="card-header"><h2><i class="fas fa-file-pen"></i> Riwayat Pembinaan</h2></div>
            <div class="card-body">
                <?php
                $rwFields = [
                    'Perilaku Pribadi'         => $riwayat['perilaku_pribadi'],
                    'Kesehatan'                => $riwayat['kesehatan'],
                    'Kegemaran / Hobi'         => $riwayat['kegemaran'],
                    'Cita-cita dan Harapan'    => $riwayat['cita_cita'],
                    'Pendidikan & Ketrampilan' => $riwayat['pendidikan_ketrampilan'],
                    'Hubungan dengan Petugas'  => $riwayat['hubungan_petugas'],
                    'Hubungan Sesama Penghuni' => $riwayat['hubungan_sesama'],
                    'Hubungan dengan Keluarga' => $riwayat['hubungan_keluarga'],
                ];
                foreach ($rwFields as $label => $val):
                    if (!$val) continue;
                ?>
                <div style="margin-bottom:14px">
                    <div style="font-size:11px;color:var(--text-muted);font-weight:600;text-transform:uppercase;margin-bottom:4px"><?= $label ?></div>
                    <div style="font-size:13px;line-height:1.6"><?= nl2br(e($val)) ?></div>
                </div>
                <?php endforeach; ?>
                <?php if ($riwayat['wali_pemasyarakatan']): ?>
                <div style="margin-top:12px;padding:12px;background:var(--info-bg);border-radius:8px">
                    <span style="font-size:11px;color:var(--text-muted);text-transform:uppercase;font-weight:600">Wali Pemasyarakatan: </span>
                    <span style="font-size:13px;font-weight:600"><?= e($riwayat['wali_pemasyarakatan']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
