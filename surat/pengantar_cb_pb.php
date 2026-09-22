<?php
// surat/pengantar_cb_pb.php — Generator Surat Pengantar Usulan CB/PB
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth.php';

$pageTitle  = 'Surat Pengantar Usulan CB/PB';
$activePage = 'surat_pengantar';
$breadcrumb = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Generator Surat'],
    ['label' => 'Surat Pengantar CB/PB']
];

// Ambil semua WBP aktif
$allWBP = $pdo->query("SELECT id, nama, no_register, kegiatan, tanggal_2_3, ekspirasi FROM narapidana WHERE status='aktif' ORDER BY kegiatan, nama ASC")->fetchAll();

$errors = [];
$data   = $_POST ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty(trim($_POST['nomor_surat'] ?? ''))) $errors['nomor_surat'] = 'Wajib diisi';
    if (empty($_POST['tanggal_surat'] ?? ''))      $errors['tanggal_surat'] = 'Wajib diisi';
    if (empty($_POST['narapidana_ids'] ?? []))     $errors['narapidana_ids'] = 'Pilih minimal 1 WBP';

    if (empty($errors) && isset($_POST['action']) && $_POST['action'] === 'download') {
        // Download Word
        redirect(BASE_URL . '/generate/pengantar_word.php?' . http_build_query([
            'nomor_surat'    => $_POST['nomor_surat'],
            'tanggal_surat'  => $_POST['tanggal_surat'],
            'lampiran'       => $_POST['lampiran'] ?? '',
            'ids'            => implode(',', $_POST['narapidana_ids'] ?? []),
        ]));
    }
}

// Hitung jumlah per kategori untuk display
$cbCount = count(array_filter($allWBP, fn($w) => $w['kegiatan'] === 'CB'));
$pbCount = count(array_filter($allWBP, fn($w) => $w['kegiatan'] === 'PB'));

include __DIR__ . '/../includes/header.php';
?>

<form method="POST" id="formPengantar">
<input type="hidden" name="action" id="formAction" value="preview">

<div class="surat-grid">

    <!-- Panel Kiri: Form -->
    <div class="surat-form-panel">

        <div class="card">
            <div class="card-header"><h2><i class="fas fa-envelope-open-text"></i> Detail Surat</h2></div>
            <div class="card-body">
                <div class="form-group mb-16">
                    <label class="form-label required">Nomor Surat</label>
                    <input type="text" name="nomor_surat" class="form-control"
                           value="<?= e($data['nomor_surat'] ?? getSetting('kode_surat') . '.PK.06.03-' . date('Y')) ?>">
                    <?php if (isset($errors['nomor_surat'])): ?><div class="invalid-feedback d-block"><?= $errors['nomor_surat'] ?></div><?php endif; ?>
                </div>
                <div class="form-group mb-16">
                    <label class="form-label required">Tanggal Surat</label>
                    <input type="date" name="tanggal_surat" class="form-control"
                           value="<?= e($data['tanggal_surat'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Lampiran</label>
                    <input type="text" name="lampiran" class="form-control"
                           value="<?= e($data['lampiran'] ?? '1 (satu) Berkas') ?>">
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div style="display:flex;gap:10px;flex-direction:column">
                    <button type="submit" class="btn btn-primary" onclick="document.getElementById('formAction').value='preview'">
                        👁 Preview Surat
                    </button>
                    <button type="submit" class="btn btn-gold" onclick="document.getElementById('formAction').value='download'">
                        <i class="fas fa-download"></i> Download Word (.docx)
                    </button>
                </div>
                <?php if (isset($errors['narapidana_ids'])): ?>
                <div class="alert alert-danger mt-8"><?= $errors['narapidana_ids'] ?></div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Panel Kanan: Pilih WBP + Preview -->
    <div>

        <!-- Pilih WBP -->
        <div class="card mb-20">
            <div class="card-header">
                <h2><i class="fas fa-users"></i> Pilih WBP yang Diusulkan</h2>
                <div style="display:flex;gap:8px;align-items:center">
                    <button type="button" onclick="selectAllWBP(true)"  class="btn btn-outline btn-sm">Semua</button>
                    <button type="button" onclick="selectAllWBP(false)" class="btn btn-outline btn-sm">Reset</button>
                    <span class="badge badge-cb"  id="countCB">0 CB</span>
                    <span class="badge badge-pb"  id="countPB">0 PB</span>
                </div>
            </div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:8px">
                    <?php foreach ($allWBP as $w): ?>
                    <?php $preChecked = in_array($w['id'], (array)($data['narapidana_ids'] ?? [])); ?>
                    <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;cursor:pointer;transition:all 0.2s" class="wbp-check-label">
                        <input type="checkbox" name="narapidana_ids[]" value="<?= $w['id'] ?>"
                               data-kegiatan="<?= $w['kegiatan'] ?>"
                               class="wbp-check" <?= $preChecked ? 'checked' : '' ?>
                               onchange="updateCounts(); styleLabel(this)"
                               style="width:15px;height:15px;accent-color:var(--primary)">
                        <div style="flex:1;min-width:0">
                            <div style="font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e($w['nama']) ?></div>
                            <div style="font-size:10px;color:var(--text-muted)"><?= e($w['no_register']) ?></div>
                        </div>
                        <span class="badge badge-<?= strtolower($w['kegiatan']) ?>"><?= $w['kegiatan'] ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Preview -->
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors['narapidana_ids']) && empty($errors['nomor_surat'])): ?>
        <?php
            $selectedIds = array_map('intval', $_POST['narapidana_ids'] ?? []);
            $selectedWBP = array_filter($allWBP, fn($w) => in_array($w['id'], $selectedIds));
            $cbSelected  = array_filter($selectedWBP, fn($w) => $w['kegiatan'] === 'CB');
            $pbSelected  = array_filter($selectedWBP, fn($w) => $w['kegiatan'] === 'PB');
            $totalSelected = count($selectedWBP);
        ?>
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-file-lines"></i> Preview Surat</h2>
            </div>
            <div class="card-body">
                <div class="surat-preview" style="font-family:'Times New Roman',serif;font-size:12pt;line-height:1.8">

                    <!-- KOP SURAT -->
                    <div style="text-align:center;border-bottom:3px solid #000;padding-bottom:10px;margin-bottom:18px">
                        <table style="width:100%;border:none">
                            <tr>
                                <td style="width:80px;vertical-align:middle;text-align:center">
                                    <img src="<?= BASE_URL ?>/logo.png" style="width:70px;height:70px;object-fit:contain">
                                </td>
                                <td style="vertical-align:middle;text-align:center">
                                    <div style="font-size:11pt">KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN REPUBLIK INDONESIA</div>
                                    <div style="font-size:11pt">DIREKTORAT JENDERAL PEMASYARAKATAN</div>
                                    <div style="font-size:11pt"><?= e(getSetting('kanwil')) ?></div>
                                    <div style="font-size:12pt;font-weight:bold"><?= e(getSetting('nama_instansi')) ?></div>
                                    <div style="font-size:9pt"><?= e(getSetting('alamat')) ?>, Kode Pos <?= e(getSetting('kode_pos')) ?> Telp. <?= e(getSetting('telp')) ?></div>
                                    <div style="font-size:9pt">Laman : <?= e(getSetting('laman')) ?> Pos-el : <?= e(getSetting('email')) ?></div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- NOMOR, LAMPIRAN, HAL -->
                    <table style="width:100%;border:none;margin-bottom:18px">
                        <tr>
                            <td style="width:120px">Nomor</td>
                            <td style="width:20px">:</td>
                            <td style="flex:1"><?= e($_POST['nomor_surat']) ?></td>
                            <td style="text-align:right"><?= formatTanggal($_POST['tanggal_surat']) ?></td>
                        </tr>
                        <tr>
                            <td>Lampiran</td>
                            <td>:</td>
                            <td colspan="2"><?= e($_POST['lampiran'] ?? '1 (satu) Berkas') ?></td>
                        </tr>
                        <tr>
                            <td>Hal</td>
                            <td>:</td>
                            <td colspan="2">Usulan  CB dan PB PerMenKum HAM R.I No. 07<br>Tahun 2022</td>
                        </tr>
                    </table>

                    <!-- ALAMAT TUJUAN -->
                    <div style="margin-bottom:18px">
                        Yth. Direktur Jenderal Pemasyarakatan<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;Kementerian Imigrasi dan Pemasyarakatan R.I.<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;di<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;J a k a r t a
                    </div>

                    <!-- ISI SURAT -->
                    <div style="text-align:justify;margin-bottom:16px">
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Dengan hormat kami sampaikan bahwa, dalam rangka kegiatan pembinaan narapidana di luar Lembaga Pemasyarakatan, kami bermaksud memberikan Cuti Bersyarat (CB) dan Pembebasan Bersyarat (PB) berdasarkan Peraturan Menteri Hukum dan HAM R.I Nomor 07 Tahun 2022  terhadap Narapidana yang telah memenuhi persyaratan substantif dan administratif.
                    </div>
                    <div style="text-align:justify;margin-bottom:16px">
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Sehubungan dengan hal tersebut, kami mengajukan permohonan dimaksud terhadap <strong><?= $totalSelected ?> (<?= terbilang($totalSelected) ?>) orang</strong> Narapidana (daftar terlampir) dengan rincian sebagai berikut :
                    </div>
                    <div style="margin-left:60px;margin-bottom:16px">
                        <ol style="list-style:decimal">
                            <li>Cuti Bersyarat (CB)&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <strong><?= count($cbSelected) ?> (<?= terbilang(count($cbSelected)) ?>) Orang</strong></li>
                            <li>Pembebasan Bersyarat (PB) : <strong><?= count($pbSelected) ?> (<?= terbilang(count($pbSelected)) ?>) Orang</strong></li>
                        </ol>
                    </div>
                    <div style="text-align:justify;margin-bottom:24px">
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Demikian permohonan ini kami sampaikan, atas perhatian dan perkenan Bapak, kami ucapkan terimakasih.
                    </div>

                    <!-- TANDA TANGAN -->
                    <table style="width:100%;border:none">
                        <tr>
                            <td style="width:50%"></td>
                            <td style="text-align:center">
                                K e p a l a<br><br><br><br><br>
                                <strong><?= e(getSetting('nama_kalapas')) ?></strong>
                            </td>
                        </tr>
                    </table>

                    <!-- TEMBUSAN -->
                    <div style="margin-top:20px;font-size:11pt">
                        <em>Tembusan :</em><br>
                        <em>Yth. Direktorat Jenderal Pemasyarakatan Kantor Wilayah Jawa Barat</em><br>
                        <em>&nbsp;&nbsp;&nbsp;&nbsp;Di - Bandung</em>
                    </div>

                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>
</form>

<script>
function updateCounts() {
    var cbCount = document.querySelectorAll('.wbp-check[data-kegiatan="CB"]:checked').length;
    var pbCount = document.querySelectorAll('.wbp-check[data-kegiatan="PB"]:checked').length;
    document.getElementById('countCB').textContent = cbCount + ' CB';
    document.getElementById('countPB').textContent = pbCount + ' PB';
}

function styleLabel(cb) {
    var lbl = cb.closest('.wbp-check-label');
    lbl.style.borderColor = cb.checked ? 'var(--primary)' : 'var(--border)';
    lbl.style.background  = cb.checked ? 'rgba(11,37,69,0.04)' : '';
}

function selectAllWBP(state) {
    document.querySelectorAll('.wbp-check').forEach(function(cb) {
        cb.checked = state;
        styleLabel(cb);
    });
    updateCounts();
}

document.querySelectorAll('.wbp-check').forEach(function(cb) {
    if (cb.checked) styleLabel(cb);
});

updateCounts();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
