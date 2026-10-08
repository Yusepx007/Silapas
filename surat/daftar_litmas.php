<?php
// surat/daftar_litmas.php — Daftar Narapidana Permohonan LITMAS
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth.php';

$pageTitle  = 'Daftar WBP Permohonan LITMAS';
$activePage = 'surat_daftar_litmas';
$breadcrumb = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Daftar WBP LITMAS']
];

$allWBP = $pdo->query("SELECT n.id, n.nama, n.no_register, n.kegiatan, n.pasal, n.lama_pidana_tahun, n.lama_pidana_bulan, n.lama_pidana_hari, n.tanggal_1_3, n.tanggal_1_2, n.tanggal_2_3, n.ekspirasi, p.nama AS penjamin_nama, p.hubungan AS penjamin_hubungan, p.alamat AS penjamin_alamat, p.hp AS penjamin_hp FROM narapidana n LEFT JOIN penjamin p ON p.narapidana_id=n.id AND p.urutan=1 WHERE n.status='aktif' ORDER BY n.nama")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="surat-grid">

    <div class="surat-form-panel">
        <div class="card">
            <div class="card-header"><h2><i class="fas fa-magnifying-glass"></i> Daftar LITMAS</h2></div>
            <div class="card-body">
                <div class="form-group mb-16">
                    <label class="form-label">Nomor Lampiran</label>
                    <input type="text" id="nomorLampiran" class="form-control" value="">
                </div>
                <div class="form-group mb-16">
                    <label class="form-label">Tanggal Surat</label>
                    <input type="date" id="tanggalSurat" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group mb-16">
                    <label class="form-label">Pilih WBP</label>
                    <div style="border:1.5px solid var(--border);border-radius:8px;max-height:300px;overflow-y:auto;padding:8px">
                        <div style="display:flex;gap:8px;margin-bottom:8px">
                            <button type="button" onclick="selectAll(true)"  class="btn btn-outline btn-sm" style="flex:1">Semua</button>
                            <button type="button" onclick="selectAll(false)" class="btn btn-outline btn-sm" style="flex:1">Reset</button>
                        </div>
                        <?php foreach ($allWBP as $w): ?>
                        <label style="display:flex;gap:8px;padding:6px;cursor:pointer;border-radius:6px;transition:background 0.2s" onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background=''">
                            <input type="checkbox" class="wbp-check" value="<?= $w['id'] ?>" style="accent-color:var(--primary);margin-top:2px">
                            <div style="font-size:12px">
                                <div style="font-weight:600"><?= e($w['nama']) ?></div>
                                <div style="color:var(--text-muted)"><?= e($w['no_register']) ?> — <?= e($w['kegiatan']) ?></div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;gap:10px">
                    <button onclick="generatePreview()" class="btn btn-primary">👁 Preview</button>
                    <button onclick="downloadWord()" class="btn btn-gold"><i class="fas fa-download"></i> Download Word</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h2><i class="fas fa-file-lines"></i> Preview Daftar LITMAS</h2></div>
        <div class="card-body" id="previewArea"
             contenteditable="false"
             style="pointer-events:none;user-select:none;-webkit-user-select:none">
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-magnifying-glass"></i></div>
                <h3>Pilih WBP untuk preview</h3>
            </div>
        </div>
    </div>
</div>

<?php
$wbpJson = json_encode($allWBP, JSON_UNESCAPED_UNICODE);
?>
<script>
var allWBP = <?= $wbpJson ?>;
var instansi = {
    nama: <?= json_encode(getSetting('nama_instansi'), JSON_UNESCAPED_UNICODE) ?>,
    kanwil: <?= json_encode(getSetting('kanwil'), JSON_UNESCAPED_UNICODE) ?>,
    alamat: <?= json_encode(getSetting('alamat'), JSON_UNESCAPED_UNICODE) ?>,
    telp: <?= json_encode(getSetting('telp'), JSON_UNESCAPED_UNICODE) ?>,
    kalapas: <?= json_encode(getSetting('nama_kalapas'), JSON_UNESCAPED_UNICODE) ?>,
    nip: <?= json_encode(getSetting('nip_kalapas'), JSON_UNESCAPED_UNICODE) ?>,
};

function formatTanggalJS(ds) {
    if (!ds) return '-';
    var m=['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    var d=new Date(ds+'T00:00:00');
    return d.getDate()+' '+m[d.getMonth()+1]+' '+d.getFullYear();
}

function getSelectedIds() {
    return Array.from(document.querySelectorAll('.wbp-check:checked')).map(c => parseInt(c.value));
}

function selectAll(state) {
    document.querySelectorAll('.wbp-check').forEach(c => c.checked = state);
}

function generatePreview() {
    var ids = getSelectedIds();
    if (!ids.length) { alert('Pilih minimal 1 WBP'); return; }
    var selected = allWBP.filter(w => ids.includes(w.id));
    var nomorLampiran = document.getElementById('nomorLampiran').value;
    var tglSurat = document.getElementById('tanggalSurat').value;

    var html = '<div style="font-family:\'Times New Roman\',serif;font-size:11pt;line-height:1.6">';

    // Lampiran header
    if (nomorLampiran) {
        html += '<div style="text-align:right;margin-bottom:8px">';
        html += '<div>Lampiran Permohonan Penelitian Kemasyarakatan</div>';
        html += '<div>Nomor &nbsp;&nbsp;: ' + nomorLampiran + '</div>';
        html += '<div>Tanggal : ' + formatTanggalJS(tglSurat) + '</div>';
        html += '</div>';
    }

    // Kop
    html += '<div style="text-align:center;border-bottom:2px solid #000;padding-bottom:8px;margin-bottom:14px">';
    html += '<table style="width:100%;border:none"><tr>';
    html += '<td style="width:70px;text-align:center"><img src="<?= BASE_URL ?>/logo_kop.png" style="width:60px;height:60px;object-fit:contain"></td>';
    html += '<td style="text-align:center">';
    html += '<div>KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN REPUBLIK INDONESIA</div>';
    html += '<div>DIREKTORAT JENDERAL PEMASYARAKATAN</div>';
    html += '<div>' + instansi.kanwil + '</div>';
    html += '<div><strong>' + instansi.nama + '</strong></div>';
    html += '<div style="font-size:9pt">' + instansi.alamat + ' Kota Tasikmalaya, Kode Pos 46112 Telp. ' + instansi.telp + '</div>';
    html += '</td></tr></table></div>';

    html += '<div style="text-align:center;font-weight:bold;margin:12px 0"><u>DAFTAR NARAPIDANA<br>UNTUK PERMOHONAN LITMAS</u></div>';

    // Tabel
    html += '<table style="width:100%;border-collapse:collapse;font-size:10pt">';
    html += '<thead><tr style="background:#fff;color:#000;border-bottom:2px solid #000">';
    html += '<th style="border:1px solid #000;padding:5px;width:30px;text-align:center">No</th>';
    html += '<th style="border:1px solid #000;padding:5px;text-align:left">Nama / No. Register</th>';
    html += '<th style="border:1px solid #000;padding:5px;text-align:left">Pidana / Pasal</th>';
    html += '<th style="border:1px solid #000;padding:5px;text-align:center;width:90px">1/3 MP</th>';
    html += '<th style="border:1px solid #000;padding:5px;text-align:center;width:90px">1/2MP</th>';
    html += '<th style="border:1px solid #000;padding:5px;text-align:center;width:90px">2/3 MP</th>';
    html += '<th style="border:1px solid #000;padding:5px;text-align:center;width:100px">Ekspirasi</th>';
    html += '<th style="border:1px solid #000;padding:5px;text-align:left">Nama dan Alamat Penjamin</th>';
    html += '<th style="border:1px solid #000;padding:5px;text-align:center;width:60px">Ket</th>';
    html += '</tr></thead><tbody>';

    selected.forEach(function(n, i) {
        var lama = [];
        if (n.lama_pidana_tahun) lama.push(n.lama_pidana_tahun + ' Tahun');
        if (n.lama_pidana_bulan) lama.push(n.lama_pidana_bulan + ' Bulan');
        if (n.lama_pidana_hari)  lama.push(n.lama_pidana_hari  + ' Hari');

        var penjaminInfo = '';
        if (n.penjamin_nama) {
            penjaminInfo = '<strong>' + n.penjamin_nama + '</strong>';
            if (n.penjamin_alamat) penjaminInfo += '<br>' + n.penjamin_alamat;
            if (n.penjamin_hp)     penjaminInfo += '<br>' + n.penjamin_hp;
        }

        var bg = i%2===0 ? '#fff' : '#f8f9fa';
        html += '<tr style="background:' + bg + '">';
        html += '<td style="border:1px solid #000;padding:5px;text-align:center;vertical-align:top">' + (i+1) + '.</td>';
        html += '<td style="border:1px solid #000;padding:5px;vertical-align:top"><strong>' + n.nama + '</strong><br><span style="font-size:9pt">' + n.no_register + '</span></td>';
        html += '<td style="border:1px solid #000;padding:5px;font-size:9pt;vertical-align:top">' + (lama.join(' ') || '') + '<br>' + (n.pasal || '') + '</td>';
        html += '<td style="border:1px solid #000;padding:5px;text-align:center;font-size:9pt;vertical-align:top">' + (n.tanggal_1_3 ? formatTanggalJS(n.tanggal_1_3) : '-') + '</td>';
        html += '<td style="border:1px solid #000;padding:5px;text-align:center;font-size:9pt;vertical-align:top">' + (n.tanggal_1_2 ? formatTanggalJS(n.tanggal_1_2) : '-') + '</td>';
        html += '<td style="border:1px solid #000;padding:5px;text-align:center;font-size:9pt;vertical-align:top">' + (n.tanggal_2_3 ? formatTanggalJS(n.tanggal_2_3) : '-') + '</td>';
        html += '<td style="border:1px solid #000;padding:5px;text-align:center;font-size:9pt;vertical-align:top">' + (n.ekspirasi ? formatTanggalJS(n.ekspirasi) : '-') + '</td>';
        html += '<td style="border:1px solid #000;padding:5px;font-size:9pt;vertical-align:top">' + penjaminInfo + '</td>';
        html += '<td style="border:1px solid #000;padding:5px;text-align:center;font-size:9pt;vertical-align:top">' + (n.penjamin_hubungan || '') + '</td>';
        html += '</tr>';
    });

    html += '</tbody></table>';

    // TTD
    html += '<div style="margin-top:20px;text-align:right">';
    html += 'Mengetahui ;<br><strong>Kalapas</strong><br><br><br><br>';
    html += '<strong><u>' + instansi.kalapas + '</u></strong>';
    html += '</div>';
    html += '</div>';

    document.getElementById('previewArea').innerHTML = html;
}

function downloadWord() {
    var ids = getSelectedIds();
    if (!ids.length) { alert('Pilih minimal 1 WBP'); return; }
    var nomor = document.getElementById('nomorLampiran').value;
    var tgl   = document.getElementById('tanggalSurat').value;
    window.location.href = '<?= BASE_URL ?>/generate/daftar_litmas_word.php?ids=' + ids.join(',') + '&nomor=' + encodeURIComponent(nomor) + '&tgl=' + tgl;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
