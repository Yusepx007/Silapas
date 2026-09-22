<?php
// pengaturan/index.php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth.php';

$pageTitle  = 'Pengaturan Sistem';
$activePage = 'pengaturan';
$breadcrumb = [['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'], ['label' => 'Pengaturan']];

$settings = [
    'nama_instansi' => 'LEMBAGA PEMASYARAKATAN KELAS IIB TASIKMALAYA',
    'kanwil'        => 'KANTOR WILAYAH KEMENTERIAN HUKUM DAN HAM PROVINSI JAWA BARAT',
    'alamat'        => 'Jl. Oto Iskandardinata No.01',
    'kode_pos'      => '46112',
    'telp'          => '(0265) 312161',
    'fax'           => '(0265) 312161',
    'laman'         => 'www.lapas.go.id',
    'email'         => 'lapas.tasikmalaya@imigrasi.go.id',
    'nama_kalapas'  => 'H. MUHAMAD SOFWAN HADI, A.Md.IP.S.H.M.H',
    'nip_kalapas'   => '197508091998031001',
    'kode_surat'    => 'WP.11.PAS.PAS15_PK.01.05.02',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($settings as $key => $default) {
        $value = trim($_POST[$key] ?? '');
        $pdo->prepare("INSERT INTO pengaturan (kunci, nilai) VALUES (?,?) ON DUPLICATE KEY UPDATE nilai=?")->execute([$key, $value, $value]);
    }
    setFlash('success', 'Pengaturan berhasil disimpan!');
    redirect(BASE_URL . '/pengaturan/index.php');
}

// Load current
$current = [];
foreach ($settings as $key => $default) {
    $current[$key] = getSetting($key) ?: $default;
}

include __DIR__ . '/../includes/header.php';
?>

<form method="POST">
<div class="card mb-20">
    <div class="card-header">
        <h2><i class="fas fa-gear"></i> Informasi Instansi</h2>
        <p style="font-size:12px;color:var(--text-muted);margin:0">Digunakan pada kop surat semua dokumen</p>
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group form-full">
                <label class="form-label">Nama Instansi</label>
                <input type="text" name="nama_instansi" class="form-control" value="<?= e($current['nama_instansi']) ?>">
            </div>
            <div class="form-group form-full">
                <label class="form-label">Kantor Wilayah</label>
                <input type="text" name="kanwil" class="form-control" value="<?= e($current['kanwil']) ?>">
            </div>
            <div class="form-group form-full">
                <label class="form-label">Alamat Lengkap</label>
                <input type="text" name="alamat" class="form-control" value="<?= e($current['alamat']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Kode Pos</label>
                <input type="text" name="kode_pos" class="form-control" value="<?= e($current['kode_pos']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Telepon</label>
                <input type="text" name="telp" class="form-control" value="<?= e($current['telp']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Faksimili</label>
                <input type="text" name="fax" class="form-control" value="<?= e($current['fax']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Website/Laman</label>
                <input type="text" name="laman" class="form-control" value="<?= e($current['laman']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="text" name="email" class="form-control" value="<?= e($current['email']) ?>">
            </div>
        </div>
    </div>
</div>

<div class="card mb-20">
    <div class="card-header"><h2><i class="fas fa-user"></i> Data Kepala LAPAS</h2></div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group form-full">
                <label class="form-label">Nama Kepala LAPAS</label>
                <input type="text" name="nama_kalapas" class="form-control" value="<?= e($current['nama_kalapas']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">NIP Kepala LAPAS</label>
                <input type="text" name="nip_kalapas" class="form-control" value="<?= e($current['nip_kalapas']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Kode Surat (Prefix)</label>
                <input type="text" name="kode_surat" class="form-control" value="<?= e($current['kode_surat']) ?>">
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:16px 24px">
        <button type="submit" class="btn btn-gold btn-lg"><i class="fas fa-floppy-disk"></i> Simpan Pengaturan</button>
    </div>
</div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
