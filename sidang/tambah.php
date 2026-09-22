<?php
// sidang/tambah.php — Buat sidang TPP baru
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth.php';

$pageTitle  = 'Buat Sidang TPP Baru';
$activePage = 'sidang_tambah';
$breadcrumb = [
    ['label' => 'Dashboard',   'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Sidang TPP',  'url' => BASE_URL . '/sidang/index.php'],
    ['label' => 'Buat Sidang Baru']
];

// Ambil semua WBP aktif
$stmtWBP = $pdo->query("SELECT id, nama, no_register, kegiatan FROM narapidana WHERE status='aktif' ORDER BY nama ASC");
$allWBP = $stmtWBP->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty(trim($_POST['nomor_surat'] ?? ''))) $errors['nomor_surat'] = 'Wajib diisi';
    if (empty($_POST['tanggal_surat'] ?? ''))      $errors['tanggal_surat'] = 'Wajib diisi';
    if (empty($_POST['tanggal_sidang'] ?? ''))     $errors['tanggal_sidang'] = 'Wajib diisi';

    if (empty($errors)) {
        $pdo->prepare("INSERT INTO sidang_tpp (nomor_surat, tanggal_surat, lampiran, perihal, hari, tanggal_sidang, pukul_mulai, pukul_selesai, materi, tempat, ketua_tpp)
                       VALUES (?,?,?,?,?,?,?,?,?,?,?)")->execute([
            trim($_POST['nomor_surat']),
            $_POST['tanggal_surat'],
            trim($_POST['lampiran']    ?? '1 (satu) halaman'),
            trim($_POST['perihal']     ?? ''),
            namaHari($_POST['tanggal_sidang']),
            $_POST['tanggal_sidang'],
            trim($_POST['pukul_mulai']  ?? '13.00'),
            trim($_POST['pukul_selesai']?? 'selesai'),
            trim($_POST['materi']       ?? ''),
            trim($_POST['tempat']       ?? 'Aula Atas Lapas Tasikmalaya'),
            trim($_POST['ketua_tpp']    ?? ''),
        ]);

        $sidangId = $pdo->lastInsertId();

        // Tambahkan WBP ke sidang
        if (!empty($_POST['narapidana_ids'])) {
            $stmtSN = $pdo->prepare("INSERT IGNORE INTO sidang_narapidana (sidang_id, narapidana_id) VALUES (?,?)");
            foreach ($_POST['narapidana_ids'] as $napiId) {
                $stmtSN->execute([$sidangId, (int)$napiId]);
            }
        }

        setFlash('success', 'Sidang TPP berhasil dibuat!');
        redirect(BASE_URL . '/sidang/edit.php?id=' . $sidangId);
    }
}

include __DIR__ . '/../includes/header.php';
?>

<form method="POST" id="formSidang">
<div class="card mb-20">
    <div class="card-header"><h2><i class="fas fa-list"></i> Detail Sidang TPP</h2></div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label required">Nomor Surat</label>
                <input type="text" name="nomor_surat" class="form-control <?= isset($errors['nomor_surat'])?'is-invalid':'' ?>"
                       value="<?= e($_POST['nomor_surat'] ?? '') ?>"
                       placeholder="WP.11.PAS.PAS15_PK.01.05.02_747 Tahun <?= date('Y') ?>">
                <?php if (isset($errors['nomor_surat'])): ?><div class="invalid-feedback"><?= $errors['nomor_surat'] ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label required">Tanggal Surat</label>
                <input type="date" name="tanggal_surat" class="form-control <?= isset($errors['tanggal_surat'])?'is-invalid':'' ?>"
                       value="<?= e($_POST['tanggal_surat'] ?? date('Y-m-d')) ?>">
                <?php if (isset($errors['tanggal_surat'])): ?><div class="invalid-feedback"><?= $errors['tanggal_surat'] ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label">Lampiran</label>
                <input type="text" name="lampiran" class="form-control"
                       value="<?= e($_POST['lampiran'] ?? '1 (satu) halaman') ?>">
            </div>
            <div class="form-group">
                <label class="form-label required">Tanggal Sidang</label>
                <input type="date" name="tanggal_sidang" class="form-control <?= isset($errors['tanggal_sidang'])?'is-invalid':'' ?>"
                       value="<?= e($_POST['tanggal_sidang'] ?? '') ?>">
                <?php if (isset($errors['tanggal_sidang'])): ?><div class="invalid-feedback"><?= $errors['tanggal_sidang'] ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label">Pukul Mulai</label>
                <input type="text" name="pukul_mulai" class="form-control"
                       value="<?= e($_POST['pukul_mulai'] ?? '13.00') ?>" placeholder="13.00 wib">
            </div>
            <div class="form-group">
                <label class="form-label">Pukul Selesai</label>
                <input type="text" name="pukul_selesai" class="form-control"
                       value="<?= e($_POST['pukul_selesai'] ?? 'selesai') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Tempat Sidang</label>
                <input type="text" name="tempat" class="form-control"
                       value="<?= e($_POST['tempat'] ?? 'Aula Atas Lapas Tasikmalaya') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Ketua TPP</label>
                <input type="text" name="ketua_tpp" class="form-control"
                       value="<?= e($_POST['ketua_tpp'] ?? 'ANDI WAHYU SUWARDI') ?>">
            </div>
            <div class="form-group form-full">
                <label class="form-label">Perihal</label>
                <textarea name="perihal" class="form-control" rows="2"
                          placeholder="Undangan Rapat Sidang Tim Pengamat Pemasyarakatan (TPP)"><?= e($_POST['perihal'] ?? '') ?></textarea>
            </div>
            <div class="form-group form-full">
                <label class="form-label">Materi Sidang</label>
                <textarea name="materi" class="form-control" rows="2"
                          placeholder="Usulan WBP yang akan mengikuti program Integrasi CB dan PB"><?= e($_POST['materi'] ?? '') ?></textarea>
            </div>
        </div>
    </div>
</div>

<!-- Pilih WBP -->
<div class="card mb-20">
    <div class="card-header">
        <h2><i class="fas fa-users"></i> Pilih WBP Peserta Sidang</h2>
        <div>
            <button type="button" onclick="selectAll(true)" class="btn btn-outline btn-sm">Pilih Semua</button>
            <button type="button" onclick="selectAll(false)" class="btn btn-outline btn-sm">Batal Semua</button>
            <span id="selectedCount" class="badge badge-pb" style="margin-left:8px">0 dipilih</span>
        </div>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:10px">
            <?php foreach ($allWBP as $w): ?>
            <label style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1.5px solid var(--border);border-radius:8px;cursor:pointer;transition:all 0.2s" class="wbp-check-label">
                <input type="checkbox" name="narapidana_ids[]" value="<?= $w['id'] ?>"
                       class="wbp-check"
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
            <button type="submit" class="btn btn-gold btn-lg"><i class="fas fa-floppy-disk"></i> Simpan Sidang TPP</button>
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
    });
    updateCount();
}

// Style for checked checkboxes
document.querySelectorAll('.wbp-check').forEach(function(cb) {
    cb.addEventListener('change', function() {
        this.closest('.wbp-check-label').style.borderColor = this.checked ? 'var(--primary)' : 'var(--border)';
        this.closest('.wbp-check-label').style.background = this.checked ? 'rgba(11,37,69,0.04)' : '';
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
