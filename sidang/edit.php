<?php
// sidang/edit.php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(BASE_URL . '/sidang/index.php');

$stmt = $pdo->prepare("SELECT * FROM sidang_tpp WHERE id = ?");
$stmt->execute([$id]);
$sidang = $stmt->fetch();
if (!$sidang) {
    setFlash('danger', 'Data sidang tidak ditemukan.');
    redirect(BASE_URL . '/sidang/index.php');
}

// WBP yang sudah ada di sidang ini
$stmtSN = $pdo->prepare("SELECT narapidana_id FROM sidang_narapidana WHERE sidang_id = ?");
$stmtSN->execute([$id]);
$selectedIds = array_column($stmtSN->fetchAll(), 'narapidana_id');

// Semua WBP aktif
$allWBP = $pdo->query("SELECT id, nama, no_register, kegiatan FROM narapidana WHERE status='aktif' ORDER BY nama ASC")->fetchAll();

$pageTitle  = 'Edit Sidang TPP';
$activePage = 'sidang_list';
$breadcrumb = [
    ['label' => 'Sidang TPP', 'url' => BASE_URL . '/sidang/index.php'],
    ['label' => 'Edit Sidang']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->prepare("UPDATE sidang_tpp SET nomor_surat=?, tanggal_surat=?, lampiran=?, perihal=?, hari=?, tanggal_sidang=?, pukul_mulai=?, pukul_selesai=?, materi=?, tempat=?, ketua_tpp=? WHERE id=?")->execute([
        trim($_POST['nomor_surat']),
        $_POST['tanggal_surat'],
        trim($_POST['lampiran']    ?? ''),
        trim($_POST['perihal']     ?? ''),
        namaHari($_POST['tanggal_sidang']),
        $_POST['tanggal_sidang'],
        trim($_POST['pukul_mulai']  ?? ''),
        trim($_POST['pukul_selesai']?? ''),
        trim($_POST['materi']       ?? ''),
        trim($_POST['tempat']       ?? ''),
        trim($_POST['ketua_tpp']    ?? ''),
        $id,
    ]);

    // Update WBP peserta
    $pdo->prepare("DELETE FROM sidang_narapidana WHERE sidang_id = ?")->execute([$id]);
    if (!empty($_POST['narapidana_ids'])) {
        $stmtSN = $pdo->prepare("INSERT IGNORE INTO sidang_narapidana (sidang_id, narapidana_id) VALUES (?,?)");
        foreach ($_POST['narapidana_ids'] as $napiId) {
            $stmtSN->execute([$id, (int)$napiId]);
        }
    }

    setFlash('success', 'Sidang TPP berhasil diperbarui!');
    redirect(BASE_URL . '/sidang/edit.php?id=' . $id);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex gap-12 mb-20">
    <a href="<?= BASE_URL ?>/surat/undangan_tpp.php?sidang_id=<?= $id ?>" class="btn btn-primary"><i class="fas fa-envelope"></i> Buat Undangan</a>
    <a href="<?= BASE_URL ?>/surat/daftar_tpp.php?sidang_id=<?= $id ?>" class="btn btn-success"><i class="fas fa-chart-bar"></i> Daftar WBP Sidang</a>
    <a href="<?= BASE_URL ?>/sidang/index.php" class="btn btn-outline" style="margin-left:auto">← Kembali</a>
</div>

<form method="POST">
<div class="card mb-20">
    <div class="card-header"><h2><i class="fas fa-scale-balanced"></i> Edit Sidang TPP</h2></div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label required">Nomor Surat</label>
                <input type="text" name="nomor_surat" class="form-control" value="<?= e($sidang['nomor_surat']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label required">Tanggal Surat</label>
                <input type="date" name="tanggal_surat" class="form-control" value="<?= e($sidang['tanggal_surat']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Lampiran</label>
                <input type="text" name="lampiran" class="form-control" value="<?= e($sidang['lampiran']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label required">Tanggal Sidang</label>
                <input type="date" name="tanggal_sidang" class="form-control" value="<?= e($sidang['tanggal_sidang']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Pukul Mulai</label>
                <input type="text" name="pukul_mulai" class="form-control" value="<?= e($sidang['pukul_mulai']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Pukul Selesai</label>
                <input type="text" name="pukul_selesai" class="form-control" value="<?= e($sidang['pukul_selesai']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Tempat</label>
                <input type="text" name="tempat" class="form-control" value="<?= e($sidang['tempat']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Ketua TPP</label>
                <input type="text" name="ketua_tpp" class="form-control" value="<?= e($sidang['ketua_tpp']) ?>">
            </div>
            <div class="form-group form-full">
                <label class="form-label">Perihal</label>
                <textarea name="perihal" class="form-control" rows="2"><?= e($sidang['perihal']) ?></textarea>
            </div>
            <div class="form-group form-full">
                <label class="form-label">Materi</label>
                <textarea name="materi" class="form-control" rows="2"><?= e($sidang['materi']) ?></textarea>
            </div>
        </div>
    </div>
</div>

<!-- Pilih WBP -->
<div class="card mb-20">
    <div class="card-header">
        <h2><i class="fas fa-users"></i> WBP Peserta Sidang</h2>
        <div>
            <button type="button" onclick="selectAll(true)" class="btn btn-outline btn-sm">Pilih Semua</button>
            <button type="button" onclick="selectAll(false)" class="btn btn-outline btn-sm">Batal Semua</button>
            <span id="selectedCount" class="badge badge-pb" style="margin-left:8px"><?= count($selectedIds) ?> dipilih</span>
        </div>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:10px">
            <?php foreach ($allWBP as $w): ?>
            <?php $checked = in_array($w['id'], $selectedIds); ?>
            <label style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1.5px solid <?= $checked ? 'var(--primary)' : 'var(--border)' ?>;background:<?= $checked ? 'rgba(11,37,69,0.04)' : '' ?>;border-radius:8px;cursor:pointer;transition:all 0.2s" class="wbp-check-label">
                <input type="checkbox" name="narapidana_ids[]" value="<?= $w['id'] ?>"
                       class="wbp-check"
                       <?= $checked ? 'checked' : '' ?>
                       onchange="updateCount()"
                       style="width:16px;height:16px;accent-color:var(--primary)">
                <div style="flex:1">
                    <div style="font-size:13px;font-weight:600"><?= e($w['nama']) ?></div>
                    <div style="font-size:11px;color:var(--text-muted)"><?= e($w['no_register']) ?></div>
                </div>
                <span class="badge badge-<?= strtolower($w['kegiatan']) ?>"><?= e($w['kegiatan']) ?></span>
            </label>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:16px 24px">
        <div class="d-flex gap-12">
            <button type="submit" class="btn btn-gold btn-lg"><i class="fas fa-floppy-disk"></i> Simpan Perubahan</button>
            <a href="<?= BASE_URL ?>/sidang/index.php" class="btn btn-outline btn-lg"><i class="fas fa-xmark"></i> Batal</a>
        </div>
    </div>
</div>
</form>

<script>
function updateCount() {
    var count = document.querySelectorAll('.wbp-check:checked').length;
    document.getElementById('selectedCount').textContent = count + ' dipilih';
}

function selectAll(state) {
    document.querySelectorAll('.wbp-check').forEach(function(cb) {
        cb.checked = state;
        var lbl = cb.closest('.wbp-check-label');
        lbl.style.borderColor = state ? 'var(--primary)' : 'var(--border)';
        lbl.style.background = state ? 'rgba(11,37,69,0.04)' : '';
    });
    updateCount();
}

document.querySelectorAll('.wbp-check').forEach(function(cb) {
    cb.addEventListener('change', function() {
        var lbl = this.closest('.wbp-check-label');
        lbl.style.borderColor = this.checked ? 'var(--primary)' : 'var(--border)';
        lbl.style.background = this.checked ? 'rgba(11,37,69,0.04)' : '';
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
