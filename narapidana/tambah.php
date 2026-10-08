<?php
// narapidana/tambah.php — Tambah Data WBP
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth.php';

$pageTitle  = 'Tambah Data WBP';
$activePage = 'napi_tambah';
$breadcrumb = [
    ['label' => 'Dashboard',       'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Data Narapidana', 'url' => BASE_URL . '/narapidana/index.php'],
    ['label' => 'Tambah WBP']
];

$errors = [];
$data   = $_POST ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validasi wajib
    $required = ['nama', 'no_register', 'kegiatan'];
    foreach ($required as $field) {
        if (empty(trim($_POST[$field] ?? ''))) {
            $errors[$field] = 'Field ini wajib diisi.';
        }
    }

    // Cek no_register unik
    if (!isset($errors['no_register'])) {
        $cek = $pdo->prepare("SELECT id FROM narapidana WHERE no_register = ?");
        $cek->execute([trim($_POST['no_register'])]);
        if ($cek->fetch()) {
            $errors['no_register'] = 'Nomor register sudah ada di database.';
        }
    }

    if (empty($errors)) {
        // Upload foto
        $fotoFilename = null;
        if (!empty($_FILES['foto']['name'])) {
            $fotoFilename = uploadFoto($_FILES['foto']);
        }

        // Insert narapidana
        $sql = "INSERT INTO narapidana
            (nama, no_register, jenis_kelamin, tempat_lahir, tanggal_lahir, agama, pendidikan,
             pekerjaan, suku, bangsa_wn, status_perkawinan, pasal, perkara,
             lama_pidana_tahun, lama_pidana_bulan, lama_pidana_hari, putusan_pn,
             tanggal_masuk, tanggal_1_3, tanggal_1_2, tanggal_2_3, ekspirasi, ekspirasi_pb,
             kegiatan, foto, alamat, tempat_asimilasi, keterangan)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            trim($_POST['nama']),
            trim($_POST['no_register']),
            $_POST['jenis_kelamin']       ?? 'L',
            trim($_POST['tempat_lahir']   ?? ''),
            $_POST['tanggal_lahir']       ?: null,
            trim($_POST['agama']          ?? ''),
            trim($_POST['pendidikan']     ?? ''),
            trim($_POST['pekerjaan']      ?? ''),
            trim($_POST['suku']           ?? ''),
            trim($_POST['bangsa_wn']      ?? 'Indonesia'),
            $_POST['status_perkawinan']   ?? 'Belum Kawin',
            trim($_POST['pasal']          ?? ''),
            trim($_POST['perkara']        ?? ''),
            (int)($_POST['lama_pidana_tahun'] ?? 0),
            (int)($_POST['lama_pidana_bulan'] ?? 0),
            (int)($_POST['lama_pidana_hari']  ?? 0),
            trim($_POST['putusan_pn']     ?? ''),
            $_POST['tanggal_masuk']       ?: null,
            $_POST['tanggal_1_3']         ?: null,
            $_POST['tanggal_1_2']         ?: null,
            $_POST['tanggal_2_3']         ?: null,
            $_POST['ekspirasi']           ?: null,
            $_POST['ekspirasi_pb']        ?: null,
            $_POST['kegiatan']            ?? 'PB',
            $fotoFilename,
            trim($_POST['alamat']         ?? ''),
            trim($_POST['tempat_asimilasi'] ?? ''),
            trim($_POST['keterangan']     ?? ''),
        ]);

        $napiId = $pdo->lastInsertId();

        // Insert penjamin (jika ada)
        if (!empty(trim($_POST['penjamin_nama'] ?? ''))) {
            $sqlPj = "INSERT INTO penjamin (narapidana_id, nama, tempat_lahir, tanggal_lahir, agama, suku, bangsa_wn, pendidikan, pekerjaan, hubungan, alamat, hp, bentuk_jaminan, urutan)
                      VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,1)";
            $stmtPj = $pdo->prepare($sqlPj);
            $stmtPj->execute([
                $napiId,
                trim($_POST['penjamin_nama']),
                trim($_POST['penjamin_tempat_lahir']   ?? ''),
                $_POST['penjamin_tanggal_lahir']        ?: null,
                trim($_POST['penjamin_agama']          ?? ''),
                trim($_POST['penjamin_suku']           ?? ''),
                trim($_POST['penjamin_bangsa_wn']      ?? 'Indonesia'),
                trim($_POST['penjamin_pendidikan']     ?? ''),
                trim($_POST['penjamin_pekerjaan']      ?? ''),
                trim($_POST['penjamin_hubungan']       ?? ''),
                trim($_POST['penjamin_alamat']         ?? ''),
                trim($_POST['penjamin_hp']             ?? ''),
                trim($_POST['penjamin_bentuk_jaminan'] ?? ''),
            ]);
        }

        // Insert riwayat pembinaan
        $sqlRw = "INSERT INTO riwayat_pembinaan (narapidana_id, perilaku_pribadi, kesehatan, kegemaran, cita_cita, pendidikan_ketrampilan, hubungan_petugas, hubungan_sesama, hubungan_keluarga, tahap_1_3, tahap_1_2, tahap_2_3, wali_pemasyarakatan) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmtRw = $pdo->prepare($sqlRw);
        $stmtRw->execute([
            $napiId,
            trim($_POST['rw_perilaku']          ?? ''),
            trim($_POST['rw_kesehatan']         ?? ''),
            trim($_POST['rw_kegemaran']         ?? ''),
            trim($_POST['rw_cita_cita']         ?? ''),
            trim($_POST['rw_pendidikan']        ?? ''),
            trim($_POST['rw_hub_petugas']       ?? ''),
            trim($_POST['rw_hub_sesama']        ?? ''),
            trim($_POST['rw_hub_keluarga']      ?? ''),
            trim($_POST['rw_tahap_1_3']         ?? ''),
            trim($_POST['rw_tahap_1_2']         ?? ''),
            trim($_POST['rw_tahap_2_3']         ?? ''),
            trim($_POST['rw_wali']              ?? ''),
        ]);

        setFlash('success', 'Data WBP ' . trim($_POST['nama']) . ' berhasil ditambahkan!');
        redirect(BASE_URL . '/narapidana/index.php');
    }
}

include __DIR__ . '/../includes/header.php';
?>

<form method="POST" enctype="multipart/form-data" id="formTambah">

<!-- Bagian I: Data Pribadi -->
<div class="card mb-20">
    <div class="card-header">
        <h2><i class="fas fa-user"></i> I. Data Pribadi Narapidana</h2>
    </div>
    <div class="card-body">

        <div class="form-grid">

            <!-- Foto Upload -->
            <div class="form-group" style="grid-column:1;grid-row:1/4">
                <label class="form-label">Foto WBP</label>
                <div class="photo-upload-area" onclick="document.getElementById('foto').click()" id="photoArea">
                    <img id="photoPreview" class="photo-preview" src="" alt="" style="display:none">
                    <div id="photoPlaceholder">
                        <div style="font-size:32px;margin-bottom:8px;color:var(--text-muted)"><i class="fas fa-camera"></i></div>
                        <div style="font-size:13px;color:var(--text-muted)">Klik untuk upload foto</div>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:4px">JPG/PNG, max 5MB</div>
                    </div>
                </div>
                <input type="file" id="foto" name="foto" accept="image/*" style="display:none" onchange="previewPhoto(this)">
            </div>

            <div class="form-group">
                <label class="form-label required">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control <?= isset($errors['nama'])?'is-invalid':'' ?>"
                       value="<?= e($data['nama'] ?? '') ?>" placeholder="Nama lengkap sesuai akta/KTP" required>
                <?php if (isset($errors['nama'])): ?>
                <div class="invalid-feedback"><?= $errors['nama'] ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label required">Nomor Register</label>
                <input type="text" name="no_register" class="form-control <?= isset($errors['no_register'])?'is-invalid':'' ?>"
                       value="<?= e($data['no_register'] ?? '') ?>" placeholder="Contoh: B I 47/25">
                <?php if (isset($errors['no_register'])): ?>
                <div class="invalid-feedback"><?= $errors['no_register'] ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label">Jenis Kelamin</label>
                <select name="jenis_kelamin" class="form-control">
                    <option value="L" <?= ($data['jenis_kelamin']??'L')==='L'?'selected':'' ?>>Laki-laki</option>
                    <option value="P" <?= ($data['jenis_kelamin']??'')==='P'?'selected':'' ?>>Perempuan</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Tempat Lahir</label>
                <input type="text" name="tempat_lahir" class="form-control"
                       value="<?= e($data['tempat_lahir'] ?? '') ?>" placeholder="Kota/Kabupaten">
            </div>

            <div class="form-group">
                <label class="form-label">Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir" class="form-control"
                       value="<?= e($data['tanggal_lahir'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Agama</label>
                <select name="agama" class="form-control">
                    <option value="">— Pilih Agama —</option>
                    <?php foreach (['Islam','Kristen','Katolik','Hindu','Buddha','Konghucu'] as $ag): ?>
                    <option value="<?= $ag ?>" <?= ($data['agama']??'')===$ag?'selected':'' ?>><?= $ag ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Pendidikan / Pekerjaan</label>
                <input type="text" name="pendidikan" class="form-control"
                       value="<?= e($data['pendidikan'] ?? '') ?>" placeholder="SD / SMP / SMA / ...">
            </div>

            <div class="form-group">
                <label class="form-label">Pekerjaan</label>
                <input type="text" name="pekerjaan" class="form-control"
                       value="<?= e($data['pekerjaan'] ?? '') ?>" placeholder="Petani, Buruh, ...">
            </div>

            <div class="form-group">
                <label class="form-label">Suku / Bangsa / WN</label>
                <input type="text" name="suku" class="form-control"
                       value="<?= e($data['suku'] ?? '') ?>" placeholder="Sunda / Indonesia">
            </div>

            <div class="form-group">
                <label class="form-label">Bangsa / WN</label>
                <input type="text" name="bangsa_wn" class="form-control"
                       value="<?= e($data['bangsa_wn'] ?? 'Indonesia') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Status Perkawinan</label>
                <select name="status_perkawinan" class="form-control">
                    <?php foreach (['Belum Kawin','Kawin','Cerai'] as $sp): ?>
                    <option value="<?= $sp ?>" <?= ($data['status_perkawinan']??'Belum Kawin')===$sp?'selected':'' ?>><?= $sp ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group form-full">
                <label class="form-label">Alamat</label>
                <textarea name="alamat" class="form-control" rows="2"
                          placeholder="Alamat lengkap narapidana"><?= e($data['alamat'] ?? '') ?></textarea>
            </div>

        </div>
    </div>
</div>

<!-- Bagian II: Data Pidana -->
<div class="card mb-20">
    <div class="card-header">
        <h2><i class="fas fa-scale-balanced"></i> II. Data Pidana</h2>
    </div>
    <div class="card-body">
        <div class="form-grid">

            <div class="form-group form-full">
                <label class="form-label">Pasal / Dakwaan</label>
                <textarea name="pasal" class="form-control" rows="3"
                          placeholder="Contoh: Penganiayaan / Pasal 351 Ayat (1) Jo 55 Ayat (1) KUHP"><?= e($data['pasal'] ?? '') ?></textarea>
            </div>

            <div class="form-group form-full">
                <label class="form-label">Perkara / UU</label>
                <textarea name="perkara" class="form-control" rows="2"
                          placeholder="Contoh: UU NOMOR 12 TAHUN 2022"><?= e($data['perkara'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Lama Pidana — Tahun</label>
                <input type="number" name="lama_pidana_tahun" class="form-control"
                       value="<?= e($data['lama_pidana_tahun'] ?? 0) ?>" min="0" max="99">
            </div>
            <div class="form-group">
                <label class="form-label">Lama Pidana — Bulan</label>
                <input type="number" name="lama_pidana_bulan" class="form-control"
                       value="<?= e($data['lama_pidana_bulan'] ?? 0) ?>" min="0" max="11">
            </div>
            <div class="form-group">
                <label class="form-label">Lama Pidana — Hari</label>
                <input type="number" name="lama_pidana_hari" class="form-control"
                       value="<?= e($data['lama_pidana_hari'] ?? 0) ?>" min="0" max="30">
            </div>

            <div class="form-group">
                <label class="form-label">Putusan Pengadilan Negeri</label>
                <input type="text" name="putusan_pn" class="form-control"
                       value="<?= e($data['putusan_pn'] ?? '') ?>"
                       placeholder="Contoh: 21/01/2025 No. 358/Pid.Sus/2024/PN Tsm">
            </div>

            <div class="form-group">
                <label class="form-label">Tanggal Masuk</label>
                <input type="date" name="tanggal_masuk" class="form-control"
                       value="<?= e($data['tanggal_masuk'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label required">Kategori Kegiatan</label>
                <select name="kegiatan" class="form-control" required>
                    <option value="PB"  <?= ($data['kegiatan']??'PB')==='PB'  ?'selected':'' ?>>Pembebasan Bersyarat (PB)</option>
                    <option value="CB"  <?= ($data['kegiatan']??'')==='CB'  ?'selected':'' ?>>Cuti Bersyarat (CB)</option>
                </select>
            </div>

        </div>
    </div>
</div>

<!-- Bagian III: Tahapan -->
<div class="card mb-20">
    <div class="card-header">
        <h2><i class="fas fa-calendar-days"></i> III. Tahapan Masa Pidana</h2>
    </div>
    <div class="card-body">
        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label class="form-label">1/3 Masa Pidana</label>
                <input type="date" name="tanggal_1_3" class="form-control"
                       value="<?= e($data['tanggal_1_3'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">1/2 Masa Pidana</label>
                <input type="date" name="tanggal_1_2" class="form-control"
                       value="<?= e($data['tanggal_1_2'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">2/3 Masa Pidana</label>
                <input type="date" name="tanggal_2_3" class="form-control"
                       value="<?= e($data['tanggal_2_3'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Ekspirasi</label>
                <input type="date" name="ekspirasi" class="form-control"
                       value="<?= e($data['ekspirasi'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Ekspirasi PB (khusus)</label>
                <input type="date" name="ekspirasi_pb" class="form-control"
                       value="<?= e($data['ekspirasi_pb'] ?? '') ?>">
            </div>
        </div>
    </div>
</div>

<!-- Bagian IV: Data Penjamin -->
<div class="card mb-20">
    <div class="card-header">
        <h2><i class="fas fa-people-group"></i> IV. Data Penanggung Jawab / Penjamin</h2>
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Nama Penjamin</label>
                <input type="text" name="penjamin_nama" class="form-control"
                       value="<?= e($data['penjamin_nama'] ?? '') ?>" placeholder="Nama lengkap penjamin">
            </div>
            <div class="form-group">
                <label class="form-label">Hubungan dengan WBP</label>
                <input type="text" name="penjamin_hubungan" class="form-control"
                       value="<?= e($data['penjamin_hubungan'] ?? '') ?>"
                       placeholder="Istri, Ayah Kandung, Saudara, dll">
            </div>
            <div class="form-group">
                <label class="form-label">Tempat Lahir Penjamin</label>
                <input type="text" name="penjamin_tempat_lahir" class="form-control"
                       value="<?= e($data['penjamin_tempat_lahir'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Tanggal Lahir Penjamin</label>
                <input type="date" name="penjamin_tanggal_lahir" class="form-control"
                       value="<?= e($data['penjamin_tanggal_lahir'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Agama Penjamin</label>
                <input type="text" name="penjamin_agama" class="form-control"
                       value="<?= e($data['penjamin_agama'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Pendidikan Penjamin</label>
                <input type="text" name="penjamin_pendidikan" class="form-control"
                       value="<?= e($data['penjamin_pendidikan'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Pekerjaan Penjamin</label>
                <input type="text" name="penjamin_pekerjaan" class="form-control"
                       value="<?= e($data['penjamin_pekerjaan'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">No. HP Penjamin</label>
                <input type="text" name="penjamin_hp" class="form-control"
                       value="<?= e($data['penjamin_hp'] ?? '') ?>" placeholder="08xxxxxxxxxx">
            </div>
            <div class="form-group form-full">
                <label class="form-label">Alamat Penjamin</label>
                <textarea name="penjamin_alamat" class="form-control" rows="2"><?= e($data['penjamin_alamat'] ?? '') ?></textarea>
            </div>
            <div class="form-group form-full">
                <label class="form-label">Bentuk Jaminan</label>
                <input type="text" name="penjamin_bentuk_jaminan" class="form-control"
                       value="<?= e($data['penjamin_bentuk_jaminan'] ?? '') ?>"
                       placeholder="Surat Pernyataan dan Jaminan yang ditanda tangani...">
            </div>
            <div class="form-group form-full">
                <label class="form-label">Tempat Pelaksanaan Asimilasi</label>
                <textarea name="tempat_asimilasi" class="form-control" rows="2"
                          placeholder="Alamat tempat tinggal selama asimilasi"><?= e($data['tempat_asimilasi'] ?? '') ?></textarea>
            </div>
        </div>
    </div>
</div>

<!-- Bagian V: Riwayat Pembinaan -->
<div class="card mb-20">
    <div class="card-header">
        <h2><i class="fas fa-file-pen"></i> V. Riwayat Pembinaan di LAPAS</h2>
    </div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group form-full">
                <label class="form-label">Perilaku Pribadi selama di LAPAS</label>
                <textarea name="rw_perilaku" class="form-control" rows="3"
                          placeholder="Deskripsi perilaku pribadi WBP..."><?= e($data['rw_perilaku'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Kondisi Kesehatan</label>
                <textarea name="rw_kesehatan" class="form-control" rows="2"><?= e($data['rw_kesehatan'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Kegemaran / Hobi</label>
                <textarea name="rw_kegemaran" class="form-control" rows="2"><?= e($data['rw_kegemaran'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Cita-cita dan Harapan</label>
                <textarea name="rw_cita_cita" class="form-control" rows="2"><?= e($data['rw_cita_cita'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Pendidikan & Ketrampilan di LAPAS</label>
                <textarea name="rw_pendidikan" class="form-control" rows="2"><?= e($data['rw_pendidikan'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Hubungan dengan Petugas</label>
                <textarea name="rw_hub_petugas" class="form-control" rows="2"><?= e($data['rw_hub_petugas'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Hubungan dengan Sesama Penghuni</label>
                <textarea name="rw_hub_sesama" class="form-control" rows="2"><?= e($data['rw_hub_sesama'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Hubungan dengan Keluarga</label>
                <textarea name="rw_hub_keluarga" class="form-control" rows="2"><?= e($data['rw_hub_keluarga'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Kegiatan Tahap 1/3 MP (Admisi-Orientasi)</label>
                <textarea name="rw_tahap_1_3" class="form-control" rows="2"
                          placeholder="Pengenalan lingkungan, bergaul sesama napi (Maximum Security)..."><?= e($data['rw_tahap_1_3'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Kegiatan Tahap 1/2 MP (Asimilasi)</label>
                <textarea name="rw_tahap_1_2" class="form-control" rows="2"
                          placeholder="Olah raga, membaca buku (Medium Security)..."><?= e($data['rw_tahap_1_2'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Kegiatan Tahap 2/3 MP (Integrasi)</label>
                <textarea name="rw_tahap_2_3" class="form-control" rows="2"
                          placeholder="Usulan Pembebasan Bersyarat (Minimum Security)..."><?= e($data['rw_tahap_2_3'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Wali Pemasyarakatan</label>
                <input type="text" name="rw_wali" class="form-control"
                       value="<?= e($data['rw_wali'] ?? '') ?>" placeholder="Nama wali pemasyarakatan">
            </div>
            <div class="form-group form-full">
                <label class="form-label">Keterangan Tambahan</label>
                <textarea name="keterangan" class="form-control" rows="2"><?= e($data['keterangan'] ?? '') ?></textarea>
            </div>
        </div>
    </div>
</div>

<!-- Tombol Aksi -->
<div class="card">
    <div class="card-body" style="padding:16px 24px">
        <div class="d-flex gap-12">
            <button type="submit" class="btn btn-gold btn-lg" id="submitBtn">
                <i class="fas fa-floppy-disk"></i> Simpan Data WBP
            </button>
            <a href="<?= BASE_URL ?>/narapidana/index.php" class="btn btn-outline btn-lg">
                <i class="fas fa-xmark"></i> Batal
            </a>
        </div>
    </div>
</div>

</form>

<script>
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var preview = document.getElementById('photoPreview');
            var placeholder = document.getElementById('photoPlaceholder');
            preview.src = e.target.result;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

document.getElementById('formTambah').addEventListener('submit', function() {
    var btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
