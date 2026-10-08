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
                    <select id="selectNapi" class="form-control" onchange="generatePreview()">
                        <option value="">— Pilih WBP —</option>
                        <?php foreach ($allWBP as $w): ?>
                        <option value="<?= $w['id'] ?>"
                                <?= $preselect === $w['id'] ? 'selected' : '' ?>>
                            <?= e($w['nama']) ?> (<?= e($w['no_register']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-16">
                    <label class="form-label">Tanggal Surat</label>
                    <input type="date" id="tanggalSurat" class="form-control" value="<?= date('Y-m-d') ?>" onchange="generatePreview()">
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
        <div class="card-header"><h2><i class="fas fa-file-lines"></i> Preview Surat Pernyataan (6 Halaman)</h2></div>
        <div class="card-body" style="max-height:80vh;overflow-y:auto;padding:16px">
            <div id="previewArea"
                 contenteditable="false"
                 style="pointer-events:none;user-select:none;-webkit-user-select:none">
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-file-pen"></i></div>
                    <h3>Pilih WBP untuk preview</h3>
                    <p>Surat pernyataan (6 halaman) akan tampil di sini</p>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
$wbpData = [];
foreach ($allWBP as $w) {
    $lamaPidana = [];
    $tmp = $pdo->prepare("SELECT lama_pidana_tahun, lama_pidana_bulan, lama_pidana_hari FROM narapidana WHERE id=?");
    $tmp->execute([$w['id']]);
    $t = $tmp->fetch();
    if ($t['lama_pidana_tahun']) $lamaPidana[] = $t['lama_pidana_tahun'] . ' Tahun';
    if ($t['lama_pidana_bulan']) $lamaPidana[] = $t['lama_pidana_bulan'] . ' Bulan';
    if ($t['lama_pidana_hari'])  $lamaPidana[] = $t['lama_pidana_hari']  . ' Hari';
    $wbpData[$w['id']] = [
        'nama'        => $w['nama'],
        'no_register' => $w['no_register'],
        'pasal'       => $w['pasal'],
        'ekspirasi'   => $w['ekspirasi'],
        'alamat'      => $w['alamat'],
        'lama'        => implode(' ', $lamaPidana),
    ];
}
?>

<script>
var wbpData = <?= json_encode($wbpData, JSON_UNESCAPED_UNICODE) ?>;
var instansi = {
    nama:    <?= json_encode(getSetting('nama_instansi'), JSON_UNESCAPED_UNICODE) ?>,
    kanwil:  <?= json_encode(getSetting('kanwil'),        JSON_UNESCAPED_UNICODE) ?>,
    alamat:  <?= json_encode(getSetting('alamat'),        JSON_UNESCAPED_UNICODE) ?>,
    telp:    <?= json_encode(getSetting('telp'),          JSON_UNESCAPED_UNICODE) ?>,
    kalapas: <?= json_encode(getSetting('nama_kalapas'),  JSON_UNESCAPED_UNICODE) ?>,
    nip:     <?= json_encode(getSetting('nip_kalapas'),   JSON_UNESCAPED_UNICODE) ?>,
};
var BASE_URL = '<?= BASE_URL ?>';

function formatTanggalJS(dateStr) {
    if (!dateStr) return '-';
    var months = ['','Januari','Februari','Maret','April','Mei','Juni',
                  'Juli','Agustus','September','Oktober','November','Desember'];
    var d = new Date(dateStr + 'T00:00:00');
    return d.getDate() + ' ' + months[d.getMonth()+1] + ' ' + d.getFullYear();
}

function kopHtml() {
    return '<div style="border-bottom:3px solid #000;padding-bottom:8px;margin-bottom:14px">' +
        '<table style="width:100%;border-collapse:collapse"><tr>' +
        '<td style="width:80px;vertical-align:middle;text-align:center">' +
        '<img src="' + BASE_URL + '/logo_kop.png" style="width:70px;height:70px;object-fit:contain"></td>' +
        '<td style="vertical-align:middle;text-align:center">' +
        '<div style="font-size:9pt">KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN REPUBLIK INDONESIA</div>' +
        '<div style="font-size:9pt">DIREKTORAT JENDERAL PEMASYARAKATAN</div>' +
        '<div style="font-size:9pt">' + instansi.kanwil + '</div>' +
        '<div style="font-size:11pt;font-weight:bold">' + instansi.nama + '</div>' +
        '<div style="font-size:8pt">' + instansi.alamat + ' Telepon ' + instansi.telp + '</div>' +
        '</td></tr></table></div>';
}

function pgBreak(label) {
    return '<div style="border-top:2px dashed #ccc;margin:28px 0 20px;text-align:center;color:#aaa;font-size:9pt">' + label + '</div>';
}

var D4 = '<br><br><br><br>';
var GARIS = '(.............................)'

function dataRow(label, val) {
    return '<tr><td style="width:160px;vertical-align:top">' + label + '</td><td style="width:12px;vertical-align:top">:</td><td>' + val + '</td></tr>';
}

function generatePreview() {
    var napiId = document.getElementById('selectNapi').value;
    var tgl    = document.getElementById('tanggalSurat').value;
    if (!napiId) {
        document.getElementById('previewArea').innerHTML =
            '<div class="empty-state"><div class="empty-icon"><i class="fas fa-file-pen"></i></div><h3>Pilih WBP untuk preview</h3></div>';
        return;
    }
    var napi   = wbpData[napiId];
    var tglStr = formatTanggalJS(tgl);
    var fs     = 'font-family:\'Times New Roman\',serif;font-size:11.5pt;line-height:1.8';
    var html   = '<div style="' + fs + '">';

    // ══════════════════════════════════════════════
    // HALAMAN 1: Surat Pernyataan (tidak dipungut biaya)
    // ══════════════════════════════════════════════
    html += kopHtml();
    html += '<p style="text-align:center;font-weight:bold;text-decoration:underline;font-size:13pt">SURAT PERNYATAAN</p>';
    html += '<p>Saya yang bertanda tangan di bawah ini;</p>';
    html += '<table style="border:none;width:100%;margin-bottom:10px">';
    html += dataRow('Nama Lengkap', napi.nama||'');
    html += dataRow('Pasal',        napi.pasal||'');
    html += dataRow('Lama Pidana',  napi.lama||'');
    html += dataRow('Expirasi',     formatTanggalJS(napi.ekspirasi));
    html += dataRow('Alamat',       napi.alamat||'-');
    html += '</table>';
    html += '<p style="text-align:justify">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Menyatakan dengan sebenarnya bahwa dalam pengurusan program PB/CB/CMB kami tidak dikenakan biaya apapun dan tidak memberikan atau menjanjikan imbalan / upeti / sejenisnya berupa apapun secara langsung maupun tidak langsung kepada Pejabat / Pegawai di Lapas Kelas IIB Tasikmalaya dan atas layanan yang diberikan Pejabat / Pegawai tidak memungut biaya apapun.</p>';
    html += '<p style="text-align:justify">Demikian surat pernyataan ini saya buat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.</p>';
    html += '<div style="text-align:right">Tasikmalaya, ' + tglStr + '</div>';
    html += '<table style="width:100%;border:none;margin-top:10px"><tr>';
    html += '<td style="text-align:center">Keluarga Narapidana,' + D4 + GARIS + '</td>';
    html += '<td style="text-align:center">Narapidana,' + D4 + GARIS + '</td>';
    html += '</tr></table>';
    html += '<div style="text-align:center;margin-top:16px">Mengetahui;<br>Kepala,' + D4;
    html += '<u><strong>' + instansi.kalapas + '</strong></u><br>NIP. ' + instansi.nip + '</div>';

    // ══════════════════════════════════════════════
    // HALAMAN 2: Surat Pernyataan (komitmen PB)
    // ══════════════════════════════════════════════
    html += pgBreak('--- Halaman 2: Surat Pernyataan Narapidana (Komitmen PB) ---');
    html += kopHtml();
    html += '<p style="text-align:center;font-weight:bold;text-decoration:underline;font-size:13pt">SURAT PERNYATAAN</p>';
    html += '<table style="border:none;width:100%;margin-bottom:10px">';
    html += dataRow('Nama Lengkap',   napi.nama||'');
    html += dataRow('Nomor Register', napi.no_register||'');
    html += dataRow('Perkara',        napi.pasal||'-');
    html += dataRow('Alamat',         napi.alamat||'-');
    html += '</table>';
    html += '<p style="text-align:justify">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Untuk melengkapi proses pengusulan Pembebasan Bersyarat (PB) saya, dengan ini saya menyatakan dengan kesadaran sendiri, tanpa ada paksaan dari pihak lain sebagai berikut:</p>';
    html += '<ol style="margin-left:24px;text-align:justify">';
    html += '<li>Selama proses pengusulan Pembebasan Bersyarat (PB) saya berlangsung, saya akan tetap mengikuti program pembinaan yang ada dan tetap bekerja sebagaimana mestinya;</li>';
    html += '<li>Jika Pembebasan Bersyarat (PB) saya dikabulkan, selama menjalani PB saya akan senantiasa menjalani sesuai dengan ketentuan dan tidak akan melakukan perbuatan yang melanggar hukum lagi sampai masa bimbingan berakhir, sekaligus mentaati ketentuan yang akan diberikan oleh Pembimbing Kemasyarakatan (PK) dari Balai Pemasyarakatan setempat;</li>';
    html += '<li>Saya bersedia melapor diri sebulan sekali ke Balai Pemasyarakatan, KeJaksaan Negeri setempat selama masa pembinaan Pembebasan Bersyarat (PB) saya;</li>';
    html += '<li>Saya senantiasa akan berusaha menjadi Warga Negara yang baik (berguna bagi keluarga, masyarakat dan Negara).</li>';
    html += '</ol>';
    html += '<p style="text-align:justify">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Apabila dikemudian hari baik disengaja maupun tidak disengaja saya melakukan perbuatan yang sama / pelanggaran hukum lainnya maupun melalaikan pernyataan tersebut di atas saya bersedia menerima sanksi berupa Pencabutan Usulan / Surat Keputusan PB saya.</p>';
    html += '<p style="text-align:justify">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Demikian Surat Pernyataan ini dibuat untuk dipergunakan seperlunya dan sebagai pengikat diri saya selama menunggu proses pengusulan Pembebasan Bersyarat (PB) maupun selama menJalani masa bimbingannya nanti.</p>';
    html += '<div style="text-align:right">Tasikmalaya, ' + tglStr + '</div>';
    html += '<table style="width:100%;border:none;margin-top:10px"><tr>';
    html += '<td style="text-align:left;width:50%">Mengetahui;<br>Kepala,' + D4 + '<u><strong>' + instansi.kalapas + '</strong></u><br>NIP. ' + instansi.nip + '</td>';
    html += '<td style="text-align:center;width:50%">Narapidana,' + D4 + GARIS + '</td>';
    html += '</tr></table>';

    // ══════════════════════════════════════════════
    // HALAMAN 3: Surat Pernyataan Jaminan
    // ══════════════════════════════════════════════
    html += pgBreak('--- Halaman 3: Surat Pernyataan Jaminan ---');
    html += '<p style="text-align:center;font-weight:bold;text-decoration:underline;font-size:13pt">SURAT PERNYATAAN JAMINAN</p>';
    html += '<p>Yang bertanda tangan di bawah ini :</p>';
    html += '<table style="border:none;width:100%;margin-bottom:10px">';
    html += dataRow('Nama Lengkap', '......................................');
    html += dataRow('Umur',         '......................................');
    html += dataRow('Pekerjaan',    '......................................');
    html += dataRow('Alamat',       '......................................');
    html += '</table>';
    html += '<p>Adalah sebagai Penjamin dari Narapidana :</p>';
    html += '<table style="border:none;width:100%;margin-bottom:10px">';
    html += dataRow('Nama Lengkap', napi.nama||'');
    html += dataRow('Umur',         '......................................');
    html += '</table>';
    html += '<p style="text-align:justify">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Yang saat ini sedang menjalani pidana di Lembaga Pemasyarakatan Kelas IIB Tasikmalaya</p>';
    html += '<p>Dengan ini menyatakan:</p>';
    html += '<ol style="margin-left:24px;text-align:justify">';
    html += '<li>Sanggup menjamin sepenuhnya apabila Narapidana tersebut diberikan izin Asimilasi, Cuti Bersyarat, Cuti Menjelang Bebas dan Pembebasan Bersyarat yang bersangkutan tidak melarikan diri dan atau melanggar ketentuan-ketentuan lainnya;</li>';
    html += '<li>Sanggup turut mengawasi dan membina Narapidana yang bersangkutan agar menjadi warga negara yang bertanggung jawab;</li>';
    html += '<li>Bahwasanya dalam proses pengusulan program pembinaan berupa PB, CB, CMB, CMK dan Remisi tidak pernah dipungut biaya apapun oleh petugas.</li>';
    html += '</ol>';
    html += '<p style="text-align:justify">Demikian Surat Jaminan ini dibuat dengan sesungguhnya untuk dipergunakan seperlunya.</p>';
    html += '<div style="text-align:right">Tasikmalaya,<br>Yang Membuat Pernyataan,' + D4 + GARIS + '</div>';

    // ══════════════════════════════════════════════
    // HALAMAN 4: Surat Pernyataan (siap terima kembali)
    // ══════════════════════════════════════════════
    html += pgBreak('--- Halaman 4: Surat Pernyataan Keluarga/Penjamin ---');
    html += '<p style="text-align:center;font-weight:bold;text-decoration:underline;font-size:13pt">SURAT PERNYATAAN</p>';
    html += '<p>Yang bertanda tangan di bawah ini :</p>';
    html += '<table style="border:none;width:100%;margin-bottom:10px">';
    html += dataRow('Nama Lengkap', '......................................');
    html += dataRow('Umur',         '......................................');
    html += dataRow('Pekerjaan',    '......................................');
    html += dataRow('Alamat',       '......................................');
    html += '</table>';
    html += '<p style="text-align:justify">Adalah sebagai ………………… dari Narapidana bernama;</p>';
    html += '<p><strong>' + (napi.nama||'') + '</strong></p>';
    html += '<p style="text-align:justify">Yang sedang menjalani pidana di Lembaga Pemasyarakatan Kelas IIB Tasikmalaya, memberikan pernyataan bahwa apabila yang bersangkutan mendapat Cuti Bersyarat, Cuti Menjelang Bebas dan Pembebasan Bersyarat:</p>';
    html += '<ol style="margin-left:24px;text-align:justify">';
    html += '<li>Kami akan bersedia menerima kembali yang bersangkutan untuk bertempat tinggal di rumah kami;</li>';
    html += '<li>Kami sanggup membantu penghidupannya baik secara moril maupun materil.</li>';
    html += '</ol>';
    html += '<p>Demikian Surat Jaminan ini dibuat dengan sesungguhnya untuk dipergunakan seperlunya.</p>';
    html += '<p>Yang membuat pernyataan;</p>';
    html += '<table style="width:100%;border:none;margin-top:10px"><tr>';
    html += '<td style="text-align:center">Penjamin Pertama,' + D4 + GARIS + '</td>';
    html += '<td style="text-align:center">Penjamin Kedua,' + D4 + GARIS + '</td>';
    html += '</tr></table>';
    html += '<div style="text-align:center;margin-top:16px">Mengetahui;<br>Kepala Desa/Kelurahan,' + D4 + GARIS + '</div>';

    // ══════════════════════════════════════════════
    // HALAMAN 5: Surat Pernyataan dari Lingkungan Setempat
    // ══════════════════════════════════════════════
    html += pgBreak('--- Halaman 5: Surat Pernyataan dari Lingkungan Setempat ---');
    html += '<p style="text-align:center;font-weight:bold;text-decoration:underline;font-size:13pt">SURAT PERNYATAAN DARI LINGKUNGAN SETEMPAT</p>';
    html += '<p>Yang bertanda tangan di bawah ini :<br>Pengurus</p>';
    html += '<p>Dengan ini menyatakan bahwa:</p>';
    html += '<p>Apabila Narapidana Atas Nama <strong>' + (napi.nama||'…………………………………') + '</strong></p>';
    html += '<p style="text-align:justify">Yang saat ini sedang menjalani pidana di Lembaga Pemasyarakatan Kelas IIB Tasikmalaya. Diberikan Asimilasi / Pembebasan Bersyarat / Cuti Bersyarat / Cuti Menjelang Bebas / Cuti Mengunjungi Keluarga dan kembali berada ditengah-tengah masyarakat, maka kami menyatakan;</p>';
    html += '<ol style="margin-left:24px;text-align:justify">';
    html += '<li>Dapat menerima keberadaan Narapidana tersebut ditengah-tengah lingkungan masyarakat,</li>';
    html += '<li>Akan berusaha membantu untuk mengawasi dan membina Narapidana tersebut agar dapat berperilaku baik serta tidak melakukan perbuatan yang melanggar hukum lagi;</li>';
    html += '<li>Mendukung program pembinaan yang dijalani oleh Narapidana tersebut.</li>';
    html += '</ol>';
    html += '<p>Selain hal tersebut diatas, kami juga memberikan keterangan bahwa Penjamin / Penanggung Jawab;</p>';
    html += '<table style="border:none;width:100%;margin-bottom:10px">';
    html += dataRow('Nama',      '......................................');
    html += dataRow('Umur',      '......................................');
    html += dataRow('Pekerjaan', '......................................');
    html += dataRow('Alamat',    '......................................');
    html += '</table>';
    html += '<p>Adalah benar-benar warga masyarakat di lingkungan kami.</p>';
    html += '<p>Demikian Surat Pernyataan ini dibuat untuk dipergunakan seperlunya.</p>';
    html += '<div style="text-align:center">Mengetahui;</div>';
    html += '<table style="width:100%;border:none;margin-top:10px"><tr>';
    html += '<td style="text-align:center">Ketua RT,' + D4 + GARIS + '</td>';
    html += '<td style="text-align:center">Ketua RW,' + D4 + GARIS + '</td>';
    html += '</tr></table>';
    html += '<div style="text-align:center;margin-top:16px">Mengetahui;<br>Kepala Desa/Kelurahan,' + D4 + GARIS + '</div>';

    // ══════════════════════════════════════════════
    // HALAMAN 6: Surat Pernyataan dari Warga Masyarakat
    // ══════════════════════════════════════════════
    html += pgBreak('--- Halaman 6: Surat Pernyataan dari Warga Masyarakat ---');
    html += '<p style="text-align:center;font-weight:bold;text-decoration:underline;font-size:13pt">SURAT PERNYATAAN DARI WARGA MASYARAKAT</p>';
    html += '<p>Yang bertanda tangan di bawah ini, Kami warga masyarakat;</p>';
    html += '<table style="width:100%;border-collapse:collapse;font-size:10.5pt;margin-bottom:12px">';
    html += '<thead><tr>';
    html += '<th style="border:1px solid #000;padding:5px;text-align:center;width:35px">NO</th>';
    html += '<th style="border:1px solid #000;padding:5px">NAMA</th>';
    html += '<th style="border:1px solid #000;padding:5px;width:55px">UMUR</th>';
    html += '<th style="border:1px solid #000;padding:5px">PEKERJAAN</th>';
    html += '<th style="border:1px solid #000;padding:5px">TANDA TANGAN</th>';
    html += '</tr></thead><tbody>';
    for (var i = 1; i <= 5; i++) {
        html += '<tr><td style="border:1px solid #000;padding:8px;text-align:center">' + i + '</td>';
        html += '<td style="border:1px solid #000;padding:8px"></td>';
        html += '<td style="border:1px solid #000;padding:8px"></td>';
        html += '<td style="border:1px solid #000;padding:8px"></td>';
        html += '<td style="border:1px solid #000;padding:8px"></td></tr>';
    }
    html += '</tbody></table>';
    html += '<p>Apabila Narapidana Atas Nama <strong>' + (napi.nama||'…………………………………') + '</strong></p>';
    html += '<p style="text-align:justify">Yang saat ini sedang menjalani pidana di Lembaga Pemasyarakatan Kelas IIB Tasikmalaya. Diberikan Asimilasi / Pembebasan Bersyarat / Cuti Bersyarat / Cuti Menjelang Bebas / Cuti Mengunjungi Keluarga dan kembali berada ditengah-tengah masyarakat, maka kami menyatakan;</p>';
    html += '<ol style="margin-left:24px;text-align:justify">';
    html += '<li>Dapat menerima keberadaan Narapidana tersebut ditengah-tengah lingkungan masyarakat;</li>';
    html += '<li>Akan berusaha membantu untuk mengawasi dan membina Narapidana tersebut agar dapat berperilaku baik serta tidak melakukan perbuatan yang melanggar hukum lagi;</li>';
    html += '<li>Mendukung program pembinaan yang dijalani oleh Narapidana tersebut.</li>';
    html += '</ol>';
    html += '<p>Demikian Surat Pernyataan ini dibuat untuk dipergunakan seperlunya.</p>';
    html += '<div style="text-align:center">Mengetahui;</div>';
    html += '<table style="width:100%;border:none;margin-top:10px"><tr>';
    html += '<td style="text-align:center">Ketua RT,' + D4 + GARIS + '</td>';
    html += '<td style="text-align:center">Ketua RW,' + D4 + GARIS + '</td>';
    html += '</tr></table>';
    html += '<div style="text-align:center;margin-top:16px">Mengetahui;<br>Kepala Desa/Kelurahan,' + D4 + GARIS + '</div>';

    html += '</div>';
    document.getElementById('previewArea').innerHTML = html;
}

function downloadWord() {
    var napiId = document.getElementById('selectNapi').value;
    var tgl    = document.getElementById('tanggalSurat').value;
    if (!napiId) { alert('Pilih WBP terlebih dahulu'); return; }
    window.location.href = BASE_URL + '/generate/pernyataan_word.php?id=' + napiId + '&tgl=' + tgl;
}

<?php if ($preselect): ?>
window.addEventListener('load', generatePreview);
<?php endif; ?>
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
