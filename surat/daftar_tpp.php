<?php
// surat/daftar_tpp.php — Daftar Narapidana Sidang TPP
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth.php';

$pageTitle  = 'Daftar WBP Sidang TPP';
$activePage = 'surat_daftar_tpp';
$breadcrumb = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Daftar WBP Sidang TPP']
];

$preselect  = (int)($_GET['sidang_id'] ?? 0);
$sidangList = $pdo->query("SELECT * FROM sidang_tpp ORDER BY tanggal_sidang DESC")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="surat-grid">

    <div class="surat-form-panel">
        <div class="card">
            <div class="card-header"><h2><i class="fas fa-chart-bar"></i> Daftar WBP TPP</h2></div>
            <div class="card-body">
                <div class="form-group mb-16">
                    <label class="form-label required">Pilih Sidang TPP</label>
                    <select id="selectSidang" class="form-control" onchange="loadPreview(this.value)">
                        <option value="">— Pilih Sidang —</option>
                        <?php foreach ($sidangList as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $preselect === $s['id'] ? 'selected' : '' ?>>
                            <?= formatTanggal($s['tanggal_sidang']) ?> — <?= e(mb_substr($s['perihal'] ?? $s['nomor_surat'], 0, 50)) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display:flex;flex-direction:column;gap:10px">
                    <button type="button" onclick="loadPreview(document.getElementById('selectSidang').value)" class="btn btn-primary">👁 Tampilkan</button>
                    <a id="btnDownload" href="#" class="btn btn-gold" style="pointer-events:none;opacity:0.5"><i class="fas fa-download"></i> Download Word</a>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h2><i class="fas fa-file-lines"></i> Preview Daftar WBP</h2></div>
        <div class="card-body" id="previewArea">
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-chart-bar"></i></div>
                <h3>Pilih sidang TPP untuk melihat daftar WBP</h3>
            </div>
        </div>
    </div>
</div>

<script>
function loadPreview(sidangId) {
    if (!sidangId) return;
    document.getElementById('previewArea').innerHTML = '<div style="text-align:center;padding:40px"><div style="font-size:24px"><i class="fas fa-spinner fa-spin"></i></div><p>Memuat data...</p></div>';

    fetch('<?= BASE_URL ?>/surat/ajax_daftar_tpp.php?sidang_id=' + sidangId)
        .then(r => r.text())
        .then(html => {
            document.getElementById('previewArea').innerHTML = html;
            var btn = document.getElementById('btnDownload');
            btn.href = '<?= BASE_URL ?>/generate/daftar_tpp_word.php?sidang_id=' + sidangId;
            btn.style.pointerEvents = 'auto';
            btn.style.opacity = '1';
        })
        .catch(() => {
            document.getElementById('previewArea').innerHTML = '<div class="alert alert-danger">Gagal memuat data.</div>';
        });
}
<?php if ($preselect): ?>
window.addEventListener('load', function() { loadPreview('<?= $preselect ?>'); });
<?php endif; ?>
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
