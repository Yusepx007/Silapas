<?php
// surat/pernyataan.php — Generator Surat Pernyataan
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth.php';

$pageTitle  = 'Surat Pernyataan';
$activePage = 'surat_pernyataan';
$breadcrumb = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Surat Pernyataan']
];

$preselect = (int)($_GET['id'] ?? 0);
$allWBP = $pdo->query("SELECT id, nama, no_register, pasal, ekspirasi, alamat, kegiatan FROM narapidana WHERE status='aktif' ORDER BY nama")->fetchAll();

$napiSelected = null;
if ($preselect) {
    foreach ($allWBP as $w) {
        if ($w['id'] === $preselect) { $napiSelected = $w; break; }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="surat-grid">

    <!-- Panel Form -->
    <div class="surat-form-panel">
        <div class="card">
            <div class="card-header"><h2><i class="fas fa-file-pen"></i> Surat Pernyataan</h2></div>
            <div class="card-body">
                <div class="form-group mb-16">
                    <label class="form-label required">Pilih WBP</label>
                    <select id="selectNapi" class="form-control" onchange="loadNapi(this.value)">
                        <option value="">— Pilih WBP —</option>
                        <?php foreach ($allWBP as $w): ?>
                        <option value="<?= $w['id'] ?>"
                                data-nama="<?= e($w['nama']) ?>"
                                data-pasal="<?= e($w['pasal']) ?>"
                                data-ekspirasi="<?= e($w['ekspirasi']) ?>"
                                data-alamat="<?= e($w['alamat']) ?>"
                                <?= $preselect === $w['id'] ? 'selected' : '' ?>>
                            <?= e($w['nama']) ?> (<?= e($w['no_register']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-16">
                    <label class="form-label">Tanggal Surat</label>
                    <input type="date" id="tanggalSurat" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="surat-action-btns">
                    <button type="button" onclick="generatePreview()" class="btn btn-primary"><i class="fas fa-eye"></i> Preview</button>
                    <button type="button" onclick="downloadWord()" class="btn btn-gold"><i class="fas fa-download"></i> Download Word</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview -->
    <div class="card">
        <div class="card-header"><h2><i class="fas fa-file-lines"></i> Preview Surat Pernyataan</h2></div>
        <div class="card-body">
            <div id="previewArea">
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-file-pen"></i></div>
                    <h3>Pilih WBP untuk preview</h3>
                    <p>Surat pernyataan akan tampil di sini</p>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
// Data WBP sebagai JS object
$wbpData = [];
foreach ($allWBP as $w) {
    $lamaPidana = [];
    if ($w['pasal']) {
        // Get full data
        $tmp = $pdo->prepare("SELECT lama_pidana_tahun, lama_pidana_bulan, lama_pidana_hari FROM narapidana WHERE id=?");
        $tmp->execute([$w['id']]);
        $t = $tmp->fetch();
        if ($t['lama_pidana_tahun']) $lamaPidana[] = $t['lama_pidana_tahun'] . ' Tahun';
        if ($t['lama_pidana_bulan']) $lamaPidana[] = $t['lama_pidana_bulan'] . ' Bulan';
        if ($t['lama_pidana_hari'])  $lamaPidana[] = $t['lama_pidana_hari']  . ' Hari';
    }
    $wbpData[$w['id']] = [
        'nama'      => $w['nama'],
        'pasal'     => $w['pasal'],
        'ekspirasi' => $w['ekspirasi'],
        'alamat'    => $w['alamat'],
        'lama'      => implode(' ', $lamaPidana),
    ];
}
?>

<script>
var wbpData = <?= json_encode($wbpData, JSON_UNESCAPED_UNICODE) ?>;
var instansi = {
    nama: <?= json_encode(getSetting('nama_instansi'), JSON_UNESCAPED_UNICODE) ?>,
    kanwil: <?= json_encode(getSetting('kanwil'), JSON_UNESCAPED_UNICODE) ?>,
    alamat: <?= json_encode(getSetting('alamat'), JSON_UNESCAPED_UNICODE) ?>,
    telp: <?= json_encode(getSetting('telp'), JSON_UNESCAPED_UNICODE) ?>,
    kalapas: <?= json_encode(getSetting('nama_kalapas'), JSON_UNESCAPED_UNICODE) ?>,
    nip: <?= json_encode(getSetting('nip_kalapas'), JSON_UNESCAPED_UNICODE) ?>,
};

function formatTanggalJS(dateStr) {
    if (!dateStr) return '-';
    var months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    var d = new Date(dateStr);
    return d.getDate() + ' ' + months[d.getMonth()+1] + ' ' + d.getFullYear();
}

function generatePreview() {
    var napiId = document.getElementById('selectNapi').value;
    var tgl    = document.getElementById('tanggalSurat').value;
    if (!napiId) { alert('Pilih WBP terlebih dahulu'); return; }

    var napi = wbpData[napiId];
    var tglStr = formatTanggalJS(tgl);

    var html = '<div class="surat-preview" style="font-family:\'Times New Roman\',serif;font-size:12pt;line-height:1.8">';

    // Kop Surat
    html += '<div style="text-align:center;border-bottom:3px solid #000;padding-bottom:10px;margin-bottom:18px">';
    html += '<table style="width:100%;border:none"><tr>';
    html += '<td style="width:70px;text-align:center"><img src="<?= BASE_URL ?>/logo.png" style="width:60px;height:60px;object-fit:contain"></td>';
    html += '<td style="text-align:center">';
    html += '<div>KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN REPUBLIK INDONESIA</div>';
    html += '<div>' + instansi.kanwil + '</div>';
    html += '<div><strong>' + instansi.nama + '</strong></div>';
    html += '<div style="font-size:10pt">' + instansi.alamat + ' Telp. ' + instansi.telp + '</div>';
    html += '</td></tr></table></div>';

    // Judul
    html += '<div style="text-align:center;font-weight:bold;text-decoration:underline;font-size:14pt;margin:20px 0">SURAT PERNYATAAN</div>';

    // Isi
    html += '<p>Saya yang bertanda tangan di bawah ini;</p>';
    html += '<table style="border:none;width:100%">';
    html += '<tr><td style="width:180px">Nama Lengkap</td><td style="width:20px">:</td><td><strong>' + (napi.nama || '') + '</strong></td></tr>';
    html += '<tr><td>Pasal</td><td>:</td><td>' + (napi.pasal || '') + '</td></tr>';
    html += '<tr><td>Lama Pidana</td><td>:</td><td>' + (napi.lama || '') + '</td></tr>';
    html += '<tr><td>Expirasi</td><td>:</td><td>' + formatTanggalJS(napi.ekspirasi) + '</td></tr>';
    html += '<tr><td>Alamat</td><td>:</td><td>' + (napi.alamat || '') + '</td></tr>';
    html += '</table>';

    html += '<p style="margin-top:18px;text-align:justify">Menyatakan dengan sebenarnya bahwa dalam pengurusan program PB/CB/CMB kami tidak dikenakan biaya apapun dan tidak memberikan atau menjanjikan imbalan / upeti / sejenisnya berupa apapun secara langsung maupun tidak langsung kepada Pejabat / Pegawai di Lapas Kelas IIB Tasikmalaya dan atas layanan yang diberikan Pejabat / Pegawai tidak memungut biaya apapun.</p>';
    html += '<p style="text-align:justify">Demikian surat pernyataan ini saya buat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.</p>';

    html += '<div style="margin-top:20px"><table style="width:100%;border:none">';
    html += '<tr><td></td><td style="text-align:right">Tasikmalaya, ' + tglStr + '</td></tr>';
    html += '<tr><td>Keluarga Narapidana,</td><td style="text-align:center">Narapidana,</td></tr>';
    html += '<tr><td><br><br><br>............................</td><td style="text-align:center"><br><br><br>............................</td></tr>';
    html += '<tr><td colspan="2" style="text-align:center"><br>Mengetahui;<br>Kepala,<br><br><br><strong><u>' + instansi.kalapas + '</u></strong><br>NIP. ' + instansi.nip + '</td></tr>';
    html += '</table></div>';
    html += '</div>';

    document.getElementById('previewArea').innerHTML = html;
}

function downloadWord() {
    var napiId = document.getElementById('selectNapi').value;
    var tgl    = document.getElementById('tanggalSurat').value;
    if (!napiId) { alert('Pilih WBP terlebih dahulu'); return; }
    var url = '<?= BASE_URL ?>/generate/pernyataan_word.php?id=' + napiId + '&tgl=' + tgl;
    window.open(url, '_blank');
}

// Auto preview jika ada preselect
<?php if ($preselect): ?>
window.addEventListener('load', generatePreview);
<?php endif; ?>
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
