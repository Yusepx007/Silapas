<?php
// surat/undangan_tpp.php — Generator Undangan Sidang TPP
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth.php';

$pageTitle  = 'Undangan Rapat Sidang TPP';
$activePage = 'surat_undangan';
$breadcrumb = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Undangan Sidang TPP']
];

$preselect = (int)($_GET['sidang_id'] ?? 0);
$sidangList = $pdo->query("SELECT * FROM sidang_tpp ORDER BY tanggal_sidang DESC")->fetchAll();

// Data anggota TPP (bisa dikostumisasi di pengaturan)
$anggotaTPP = [
    'Andi Wahyu Suwardi',
    'Asep Jatnika',
    'Gun Gun Setiawan',
    'Yadi Suryaman',
    'Agus Herianto',
    'Ayep Iwan Suryawan',
    'Arief Setyo Budiarto',
    'Agus Habibulloh',
    'Wali Pemasyarakatan',
];

include __DIR__ . '/../includes/header.php';
?>

<div class="surat-grid">

    <div class="surat-form-panel">
        <div class="card">
            <div class="card-header"><h2><i class="fas fa-envelope"></i> Undangan Sidang TPP</h2></div>
            <div class="card-body">
                <div class="form-group mb-16">
                    <label class="form-label required">Pilih Sidang TPP</label>
                    <select id="selectSidang" class="form-control" onchange="loadSidang(this.value)">
                        <option value="">— Pilih Sidang —</option>
                        <?php foreach ($sidangList as $s): ?>
                        <option value="<?= $s['id'] ?>"
                                data-nomor="<?= e($s['nomor_surat']) ?>"
                                data-tgl_surat="<?= e($s['tanggal_surat']) ?>"
                                data-lampiran="<?= e($s['lampiran']) ?>"
                                data-perihal="<?= e($s['perihal']) ?>"
                                data-hari="<?= e($s['hari']) ?>"
                                data-tgl_sidang="<?= e($s['tanggal_sidang']) ?>"
                                data-pukul_mulai="<?= e($s['pukul_mulai']) ?>"
                                data-pukul_selesai="<?= e($s['pukul_selesai']) ?>"
                                data-materi="<?= e($s['materi']) ?>"
                                data-tempat="<?= e($s['tempat']) ?>"
                                data-ketua="<?= e($s['ketua_tpp']) ?>"
                                <?= $preselect === $s['id'] ? 'selected' : '' ?>>
                            <?= e($s['perihal'] ? mb_substr($s['perihal'], 0, 40) . '...' : $s['nomor_surat']) ?> — <?= formatTanggal($s['tanggal_sidang']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if (empty($sidangList)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-triangle-exclamation"></i> Belum ada sidang TPP. <a href="<?= BASE_URL ?>/sidang/tambah.php" class="fw-600">Buat sidang baru</a>
                </div>
                <?php else: ?>
                <div class="surat-action-btns">
                    <button type="button" onclick="generatePreview()" class="btn btn-primary"><i class="fas fa-eye"></i> Preview Undangan</button>
                    <button type="button" onclick="downloadWord()" class="btn btn-gold"><i class="fas fa-download"></i> Download Word</button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Preview -->
    <div class="card">
        <div class="card-header"><h2><i class="fas fa-file-lines"></i> Preview Undangan</h2></div>
        <div class="card-body">
            <div id="previewArea">
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-envelope"></i></div>
                    <h3>Pilih sidang TPP untuk preview</h3>
                    <p>Undangan akan tampil di sini</p>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
var anggotaTPP = <?= json_encode($anggotaTPP, JSON_UNESCAPED_UNICODE) ?>;
var instansi = {
    nama: <?= json_encode(getSetting('nama_instansi'), JSON_UNESCAPED_UNICODE) ?>,
    kanwil: <?= json_encode(getSetting('kanwil'), JSON_UNESCAPED_UNICODE) ?>,
    alamat: <?= json_encode(getSetting('alamat'), JSON_UNESCAPED_UNICODE) ?>,
    telp: <?= json_encode(getSetting('telp'), JSON_UNESCAPED_UNICODE) ?>,
    fax: <?= json_encode(getSetting('fax', getSetting('telp')), JSON_UNESCAPED_UNICODE) ?>,
    email: <?= json_encode(getSetting('email'), JSON_UNESCAPED_UNICODE) ?>,
    kalapas: <?= json_encode(getSetting('nama_kalapas'), JSON_UNESCAPED_UNICODE) ?>,
    nip: <?= json_encode(getSetting('nip_kalapas'), JSON_UNESCAPED_UNICODE) ?>,
};

var currentSidang = null;

function formatTanggalJS(dateStr) {
    if (!dateStr) return '-';
    var months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    var d = new Date(dateStr + 'T00:00:00');
    return d.getDate() + ' ' + months[d.getMonth()+1] + ' ' + d.getFullYear();
}

function loadSidang(id) {
    var sel = document.getElementById('selectSidang');
    var opt = sel.options[sel.selectedIndex];
    if (!id) { currentSidang = null; return; }
    currentSidang = {
        id: id,
        nomor: opt.dataset.nomor,
        tgl_surat: opt.dataset.tgl_surat,
        lampiran: opt.dataset.lampiran,
        perihal: opt.dataset.perihal,
        hari: opt.dataset.hari,
        tgl_sidang: opt.dataset.tgl_sidang,
        pukul_mulai: opt.dataset.pukul_mulai,
        pukul_selesai: opt.dataset.pukul_selesai,
        materi: opt.dataset.materi,
        tempat: opt.dataset.tempat,
        ketua: opt.dataset.ketua,
    };
}

function generatePreview() {
    if (!currentSidang) { alert('Pilih sidang TPP terlebih dahulu'); return; }
    var s = currentSidang;

    var anggotaList = anggotaTPP.map(function(a, i) {
        return '<div>' + (i+1) + '. ' + a + '</div>';
    }).join('');

    var html = '<div class="surat-preview" style="font-family:\'Times New Roman\',serif;font-size:12pt;line-height:1.8">';

    // Kop
    html += '<div style="text-align:center;border-bottom:3px double #000;padding-bottom:8px;margin-bottom:14px">';
    html += '<table style="width:100%;border:none"><tr>';
    html += '<td style="width:70px;text-align:center"><img src="<?= BASE_URL ?>/logo.png" style="width:60px;height:60px;object-fit:contain"></td>';
    html += '<td style="text-align:center">';
    html += '<div>KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN R.I.</div>';
    html += '<div>DIREKTORAT JENDERAL PEMASYARAKATAN</div>';
    html += '<div>' + instansi.kanwil + '</div>';
    html += '<div><strong>' + instansi.nama + '</strong></div>';
    html += '<div style="font-size:10pt">' + instansi.alamat + ' Kota Tasikmalaya</div>';
    html += '<div style="font-size:10pt">Telepon  ' + instansi.telp + ', Faksimili ' + instansi.fax + ', Email : ' + instansi.email + '</div>';
    html += '</td></tr></table></div>';

    // Nomor dll
    html += '<table style="width:100%;border:none;margin-bottom:14px">';
    html += '<tr><td style="width:120px">Nomor</td><td style="width:20px">:</td><td>' + s.nomor + '</td><td style="text-align:right">' + formatTanggalJS(s.tgl_surat) + '</td></tr>';
    html += '<tr><td>Lampiran</td><td>:</td><td colspan="2">' + s.lampiran + '</td></tr>';
    html += '<tr><td>Perihal</td><td>:</td><td colspan="2">' + s.perihal + '</td></tr>';
    html += '</table>';

    html += '<div style="margin-bottom:14px">Kepada.<br>';
    html += '<div style="margin-left:30px">' + anggotaList + '</div>';
    html += 'di<br>Tasikmalaya</div>';

    html += '<p style="text-align:justify">Dengan Hormat kami sampaikan bahwa kami mengajukan permintaan pembuatan Penelitian Kemasyarakatan (LITMAS) guna usulan Pembebasan Bersyarat / Cuti Bersyarat pada Lembaga Pemasyarakatan Kelas IIB Tasikmalaya (daftar nama terlampir).</p>';
    html += '<p style="text-align:justify">Sehubungan dengan hal tersebut kami mengundang saudara untuk dapat hadir pada acara sidang Tim Pengamat Pemasyarakatan yang akan dilaksanakan pada :</p>';

    html += '<table style="border:none;width:100%;margin:10px 0 10px 30px">';
    html += '<tr><td style="width:120px">Hari</td><td style="width:20px">:</td><td>' + s.hari + '</td></tr>';
    html += '<tr><td>Tanggal</td><td>:</td><td>' + formatTanggalJS(s.tgl_sidang) + '</td></tr>';
    html += '<tr><td>Pukul</td><td>:</td><td>' + s.pukul_mulai + ' wib s/d ' + s.pukul_selesai + '</td></tr>';
    html += '<tr><td>Materi</td><td>:</td><td>' + s.materi + '</td></tr>';
    html += '<tr><td>Tempat</td><td>:</td><td>: ' + s.tempat + '</td></tr>';
    html += '</table>';

    html += '<p style="text-align:justify">Demikian undangan ini kami sampaikan, mohon kiranya Saudara dapat hadir tepat pada waktunya. Atas perhatiannya diucapkan terima kasih.</p>';

    html += '<div style="margin-top:20px">';
    html += '<table style="width:100%;border:none">';
    html += '<tr>';
    html += '<td style="width:50%">Mengetahui ;<br><strong>Kalapas</strong><br><br><br><br></td>';
    html += '<td style="text-align:center">Ketua<br><br><br><br></td>';
    html += '</tr>';
    html += '<tr>';
    html += '<td></td>';
    html += '<td style="text-align:center"><strong>' + s.ketua + '</strong></td>';
    html += '</tr>';
    html += '<tr>';
    html += '<td><strong><u>' + instansi.kalapas + '</u></strong><br>NIP. ' + instansi.nip + '</td>';
    html += '<td></td>';
    html += '</tr>';
    html += '</table>';
    html += '</div>';
    html += '</div>';

    document.getElementById('previewArea').innerHTML = html;
}

function downloadWord() {
    if (!currentSidang) { alert('Pilih sidang TPP terlebih dahulu'); return; }
    window.location.href = '<?= BASE_URL ?>/generate/undangan_word.php?sidang_id=' + currentSidang.id;
}

// Auto load jika ada preselect
<?php if ($preselect): ?>
window.addEventListener('load', function() {
    loadSidang('<?= $preselect ?>');
    generatePreview();
});
<?php endif; ?>
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
