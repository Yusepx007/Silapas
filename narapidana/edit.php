<?php
// narapidana/edit.php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { redirect(BASE_URL . '/narapidana/index.php'); }

// Ambil data narapidana
$stmt = $pdo->prepare("SELECT * FROM narapidana WHERE id = ?");
$stmt->execute([$id]);
$napi = $stmt->fetch();
if (!$napi) {
    setFlash('danger', 'Data WBP tidak ditemukan.');
    redirect(BASE_URL . '/narapidana/index.php');
}

// Ambil data penjamin
$stmtPj = $pdo->prepare("SELECT * FROM penjamin WHERE narapidana_id = ? ORDER BY urutan ASC LIMIT 1");
$stmtPj->execute([$id]);
$penjamin = $stmtPj->fetch() ?: [];

// Ambil riwayat pembinaan
$stmtRw = $pdo->prepare("SELECT * FROM riwayat_pembinaan WHERE narapidana_id = ? LIMIT 1");
$stmtRw->execute([$id]);
$riwayat = $stmtRw->fetch() ?: [];

$pageTitle  = 'Edit Data WBP: ' . $napi['nama'];
$activePage = 'napi_list';
$breadcrumb = [
    ['label' => 'Dashboard',       'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Data Narapidana', 'url' => BASE_URL . '/narapidana/index.php'],
    ['label' => 'Edit: ' . $napi['nama']]
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $required = ['nama', 'no_register', 'kegiatan'];
    foreach ($required as $field) {
        if (empty(trim($_POST[$field] ?? ''))) {
            $errors[$field] = 'Field ini wajib diisi.';
        }
    }

    // Cek no_register unik (kecuali milik sendiri)
    if (!isset($errors['no_register'])) {
        $cek = $pdo->prepare("SELECT id FROM narapidana WHERE no_register = ? AND id != ?");
        $cek->execute([trim($_POST['no_register']), $id]);
        if ($cek->fetch()) {
            $errors['no_register'] = 'Nomor register sudah digunakan oleh WBP lain.';
        }
    }

    if (empty($errors)) {
        // Handle foto
        $fotoFilename = $napi['foto'];
        if (!empty($_FILES['foto']['name'])) {
            $fotoFilename = uploadFoto($_FILES['foto'], $napi['foto']);
        }

        // Update narapidana
        $sql = "UPDATE narapidana SET
            nama=?, no_register=?, jenis_kelamin=?, tempat_lahir=?, tanggal_lahir=?, agama=?,
            pendidikan=?, pekerjaan=?, suku=?, bangsa_wn=?, status_perkawinan=?,
            pasal=?, perkara=?, lama_pidana_tahun=?, lama_pidana_bulan=?, lama_pidana_hari=?,
            putusan_pn=?, tanggal_masuk=?, tanggal_1_3=?, tanggal_1_2=?, tanggal_2_3=?,
            ekspirasi=?, ekspirasi_pb=?, kegiatan=?, foto=?, alamat=?, tempat_asimilasi=?, keterangan=?
            WHERE id=?";

        $pdo->prepare($sql)->execute([
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
            $id,
        ]);

        // Upsert penjamin
        if (!empty(trim($_POST['penjamin_nama'] ?? ''))) {
            if ($penjamin) {
                $sqlPj = "UPDATE penjamin SET nama=?, tempat_lahir=?, tanggal_lahir=?, agama=?, suku=?, bangsa_wn=?, pendidikan=?, pekerjaan=?, hubungan=?, alamat=?, hp=?, bentuk_jaminan=? WHERE id=?";
                $pdo->prepare($sqlPj)->execute([
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
                    $penjamin['id'],
                ]);
            } else {
                $sqlPj = "INSERT INTO penjamin (narapidana_id, nama, tempat_lahir, tanggal_lahir, agama, suku, bangsa_wn, pendidikan, pekerjaan, hubungan, alamat, hp, bentuk_jaminan, urutan) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,1)";
                $pdo->prepare($sqlPj)->execute([
                    $id,
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
        }

        // Upsert riwayat
        if ($riwayat) {
            $sqlRw = "UPDATE riwayat_pembinaan SET perilaku_pribadi=?, kesehatan=?, kegemaran=?, cita_cita=?, pendidikan_ketrampilan=?, hubungan_petugas=?, hubungan_sesama=?, hubungan_keluarga=?, tahap_1_3=?, tahap_1_2=?, tahap_2_3=?, wali_pemasyarakatan=? WHERE narapidana_id=?";
        } else {
            $sqlRw = "INSERT INTO riwayat_pembinaan (perilaku_pribadi, kesehatan, kegemaran, cita_cita, pendidikan_ketrampilan, hubungan_petugas, hubungan_sesama, hubungan_keluarga, tahap_1_3, tahap_1_2, tahap_2_3, wali_pemasyarakatan, narapidana_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
        }
        $pdo->prepare($sqlRw)->execute([
            trim($_POST['rw_perilaku']    ?? ''),
            trim($_POST['rw_kesehatan']   ?? ''),
            trim($_POST['rw_kegemaran']   ?? ''),
            trim($_POST['rw_cita_cita']   ?? ''),
            trim($_POST['rw_pendidikan']  ?? ''),
            trim($_POST['rw_hub_petugas'] ?? ''),
            trim($_POST['rw_hub_sesama']  ?? ''),
            trim($_POST['rw_hub_keluarga']?? ''),
            trim($_POST['rw_tahap_1_3']   ?? ''),
            trim($_POST['rw_tahap_1_2']   ?? ''),
            trim($_POST['rw_tahap_2_3']   ?? ''),
            trim($_POST['rw_wali']        ?? ''),
            $id,
        ]);

        setFlash('success', 'Data WBP ' . trim($_POST['nama']) . ' berhasil diperbarui!');
        redirect(BASE_URL . '/narapidana/detail.php?id=' . $id);
    }

    // Jika ada error, gunakan data POST
    $napi    = array_merge($napi, $_POST);
    $penjamin = array_merge($penjamin, $_POST);
    $riwayat  = array_merge($riwayat, [
        'perilaku_pribadi'       => $_POST['rw_perilaku']    ?? '',
        'kesehatan'              => $_POST['rw_kesehatan']   ?? '',
        'kegemaran'              => $_POST['rw_kegemaran']   ?? '',
        'cita_cita'              => $_POST['rw_cita_cita']   ?? '',
        'pendidikan_ketrampilan' => $_POST['rw_pendidikan']  ?? '',
        'hubungan_petugas'       => $_POST['rw_hub_petugas'] ?? '',
        'hubungan_sesama'        => $_POST['rw_hub_sesama']  ?? '',
        'hubungan_keluarga'      => $_POST['rw_hub_keluarga']?? '',
        'tahap_1_3'              => $_POST['rw_tahap_1_3']   ?? '',
        'tahap_1_2'              => $_POST['rw_tahap_1_2']   ?? '',
        'tahap_2_3'              => $_POST['rw_tahap_2_3']   ?? '',
        'wali_pemasyarakatan'    => $_POST['rw_wali']        ?? '',
    ]);
}

include __DIR__ . '/../includes/header.php';
?>

<form method="POST" enctype="multipart/form-data" id="formEdit">

<div style="display:flex;gap:12px;margin-bottom:20px;align-items:center;flex-wrap:wrap">
    <div style="flex:1">
        <div style="font-size:12px;color:var(--text-muted)">Mengedit data:</div>
        <div style="font-size:18px;font-weight:700"><?= e($napi['nama']) ?></div>
        <div style="font-size:13px;color:var(--primary)"><?= e($napi['no_register']) ?></div>
    </div>
    <a href="<?= BASE_URL ?>/narapidana/detail.php?id=<?= $id ?>" class="btn btn-outline">👁 Lihat Detail</a>
    <a href="<?= BASE_URL ?>/narapidana/index.php" class="btn btn-outline">← Kembali</a>
</div>

<!-- ===== DATA PRIBADI ===== -->
<div class="card mb-20">
    <div class="card-header"><h2><i class="fas fa-user"></i> I. Data Pribadi</h2></div>
    <div class="card-body">
        <div class="form-grid">
            <!-- Foto -->
            <div class="form-group" style="grid-column:1;grid-row:1/4">
                <label class="form-label">Foto WBP</label>
                <div class="photo-upload-area" onclick="document.getElementById('foto').click()">
                    <img id="photoPreview" class="photo-preview"
                         src="<?= $napi['foto'] ? UPLOAD_URL . e($napi['foto']) : '' ?>"
                         alt="" style="<?= $napi['foto'] ? 'display:block' : 'display:none' ?>">
                    <div id="photoPlaceholder" style="<?= $napi['foto'] ? 'display:none' : '' ?>">
                        <div style="font-size:36px;margin-bottom:8px"><i class="fas fa-camera"></i></div>
                        <div style="font-size:13px;color:var(--text-muted)">Klik untuk ganti foto</div>
                    </div>
                </div>
                <input type="file" id="foto" name="foto" accept="image/*" style="display:none" onchange="previewPhoto(this)">
                <?php if ($napi['foto']): ?>
                <p style="font-size:11px;color:var(--text-muted);text-align:center;margin-top:6px">Biarkan kosong untuk tidak mengubah foto</p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label required">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control <?= isset($errors['nama'])?'is-invalid':'' ?>"
                       value="<?= e($napi['nama']) ?>" required>
                <?php if (isset($errors['nama'])): ?><div class="invalid-feedback"><?= $errors['nama'] ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label required">Nomor Register</label>
                <input type="text" name="no_register" class="form-control <?= isset($errors['no_register'])?'is-invalid':'' ?>"
                       value="<?= e($napi['no_register']) ?>">
                <?php if (isset($errors['no_register'])): ?><div class="invalid-feedback"><?= $errors['no_register'] ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label">Jenis Kelamin</label>
                <select name="jenis_kelamin" class="form-control">
                    <option value="L" <?= $napi['jenis_kelamin']==='L'?'selected':'' ?>>Laki-laki</option>
                    <option value="P" <?= $napi['jenis_kelamin']==='P'?'selected':'' ?>>Perempuan</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Tempat Lahir</label>
                <input type="text" name="tempat_lahir" class="form-control" value="<?= e($napi['tempat_lahir']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir" class="form-control" value="<?= e($napi['tanggal_lahir']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Agama</label>
                <select name="agama" class="form-control">
                    <?php foreach (['Islam','Kristen','Katolik','Hindu','Buddha','Konghucu'] as $ag): ?>
                    <option value="<?= $ag ?>" <?= $napi['agama']===$ag?'selected':'' ?>><?= $ag ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Pendidikan</label>
                <input type="text" name="pendidikan" class="form-control" value="<?= e($napi['pendidikan']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Pekerjaan</label>
                <input type="text" name="pekerjaan" class="form-control" value="<?= e($napi['pekerjaan']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Suku</label>
                <input type="text" name="suku" class="form-control" value="<?= e($napi['suku']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Bangsa/WN</label>
                <input type="text" name="bangsa_wn" class="form-control" value="<?= e($napi['bangsa_wn']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Status Perkawinan</label>
                <select name="status_perkawinan" class="form-control">
                    <?php foreach (['Belum Kawin','Kawin','Cerai'] as $sp): ?>
                    <option value="<?= $sp ?>" <?= $napi['status_perkawinan']===$sp?'selected':'' ?>><?= $sp ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group form-full">
                <label class="form-label">Alamat</label>
                <textarea name="alamat" class="form-control" rows="2"><?= e($napi['alamat']) ?></textarea>
            </div>
        </div>
    </div>
</div>

<!-- ===== DATA PIDANA ===== -->
<div class="card mb-20">
    <div class="card-header"><h2><i class="fas fa-scale-balanced"></i> II. Data Pidana & Tahapan</h2></div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group form-full">
                <label class="form-label">Pasal / Dakwaan</label>
                <textarea name="pasal" class="form-control" rows="3"><?= e($napi['pasal']) ?></textarea>
            </div>
            <div class="form-group form-full">
                <label class="form-label">Perkara / UU</label>
                <textarea name="perkara" class="form-control" rows="2"><?= e($napi['perkara']) ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Lama Pidana — Tahun</label>
                <input type="number" name="lama_pidana_tahun" class="form-control" value="<?= e($napi['lama_pidana_tahun']) ?>" min="0">
            </div>
            <div class="form-group">
                <label class="form-label">Lama Pidana — Bulan</label>
                <input type="number" name="lama_pidana_bulan" class="form-control" value="<?= e($napi['lama_pidana_bulan']) ?>" min="0" max="11">
            </div>
            <div class="form-group">
                <label class="form-label">Lama Pidana — Hari</label>
                <input type="number" name="lama_pidana_hari" class="form-control" value="<?= e($napi['lama_pidana_hari']) ?>" min="0" max="30">
            </div>
            <div class="form-group">
                <label class="form-label">Putusan PN</label>
                <input type="text" name="putusan_pn" class="form-control" value="<?= e($napi['putusan_pn']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Tanggal Masuk</label>
                <input type="date" name="tanggal_masuk" class="form-control" value="<?= e($napi['tanggal_masuk']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label required">Kategori</label>
                <select name="kegiatan" class="form-control" required>
                    <option value="PB"  <?= $napi['kegiatan']==='PB'  ?'selected':'' ?>>Pembebasan Bersyarat (PB)</option>
                    <option value="CB"  <?= $napi['kegiatan']==='CB'  ?'selected':'' ?>>Cuti Bersyarat (CB)</option>
                    <option value="CMB" <?= $napi['kegiatan']==='CMB' ?'selected':'' ?>>CMB</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">1/3 Masa Pidana</label>
                <input type="date" name="tanggal_1_3" class="form-control" value="<?= e($napi['tanggal_1_3']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">1/2 Masa Pidana</label>
                <input type="date" name="tanggal_1_2" class="form-control" value="<?= e($napi['tanggal_1_2']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">2/3 Masa Pidana</label>
                <input type="date" name="tanggal_2_3" class="form-control" value="<?= e($napi['tanggal_2_3']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Ekspirasi</label>
                <input type="date" name="ekspirasi" class="form-control" value="<?= e($napi['ekspirasi']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Ekspirasi PB</label>
                <input type="date" name="ekspirasi_pb" class="form-control" value="<?= e($napi['ekspirasi_pb']) ?>">
            </div>
        </div>
    </div>
</div>

<!-- ===== PENJAMIN ===== -->
<div class="card mb-20">
    <div class="card-header"><h2><i class="fas fa-people-group"></i> III. Data Penjamin</h2></div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Nama Penjamin</label>
                <input type="text" name="penjamin_nama" class="form-control" value="<?= e($penjamin['nama'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Hubungan</label>
                <input type="text" name="penjamin_hubungan" class="form-control" value="<?= e($penjamin['hubungan'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Tempat Lahir</label>
                <input type="text" name="penjamin_tempat_lahir" class="form-control" value="<?= e($penjamin['tempat_lahir'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Tanggal Lahir</label>
                <input type="date" name="penjamin_tanggal_lahir" class="form-control" value="<?= e($penjamin['tanggal_lahir'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Agama</label>
                <input type="text" name="penjamin_agama" class="form-control" value="<?= e($penjamin['agama'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Pendidikan</label>
                <input type="text" name="penjamin_pendidikan" class="form-control" value="<?= e($penjamin['pendidikan'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Pekerjaan</label>
                <input type="text" name="penjamin_pekerjaan" class="form-control" value="<?= e($penjamin['pekerjaan'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">No. HP</label>
                <input type="text" name="penjamin_hp" class="form-control" value="<?= e($penjamin['hp'] ?? '') ?>">
            </div>
            <div class="form-group form-full">
                <label class="form-label">Alamat Penjamin</label>
                <textarea name="penjamin_alamat" class="form-control" rows="2"><?= e($penjamin['alamat'] ?? '') ?></textarea>
            </div>
            <div class="form-group form-full">
                <label class="form-label">Bentuk Jaminan</label>
                <input type="text" name="penjamin_bentuk_jaminan" class="form-control" value="<?= e($penjamin['bentuk_jaminan'] ?? '') ?>">
            </div>
            <div class="form-group form-full">
                <label class="form-label">Tempat Asimilasi</label>
                <textarea name="tempat_asimilasi" class="form-control" rows="2"><?= e($napi['tempat_asimilasi']) ?></textarea>
            </div>
        </div>
    </div>
</div>

<!-- ===== RIWAYAT PEMBINAAN ===== -->
<div class="card mb-20">
    <div class="card-header"><h2><i class="fas fa-file-pen"></i> IV. Riwayat Pembinaan</h2></div>
    <div class="card-body">
        <div class="form-grid">
            <div class="form-group form-full">
                <label class="form-label">Perilaku Pribadi</label>
                <textarea name="rw_perilaku" class="form-control" rows="3"><?= e($riwayat['perilaku_pribadi'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Kesehatan</label>
                <textarea name="rw_kesehatan" class="form-control" rows="2"><?= e($riwayat['kesehatan'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Kegemaran</label>
                <textarea name="rw_kegemaran" class="form-control" rows="2"><?= e($riwayat['kegemaran'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Cita-cita</label>
                <textarea name="rw_cita_cita" class="form-control" rows="2"><?= e($riwayat['cita_cita'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Pendidikan & Ketrampilan di LAPAS</label>
                <textarea name="rw_pendidikan" class="form-control" rows="2"><?= e($riwayat['pendidikan_ketrampilan'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Hubungan dengan Petugas</label>
                <textarea name="rw_hub_petugas" class="form-control" rows="2"><?= e($riwayat['hubungan_petugas'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Hubungan dengan Sesama</label>
                <textarea name="rw_hub_sesama" class="form-control" rows="2"><?= e($riwayat['hubungan_sesama'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Hubungan dengan Keluarga</label>
                <textarea name="rw_hub_keluarga" class="form-control" rows="2"><?= e($riwayat['hubungan_keluarga'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Kegiatan Tahap 1/3</label>
                <textarea name="rw_tahap_1_3" class="form-control" rows="2"><?= e($riwayat['tahap_1_3'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Kegiatan Tahap 1/2</label>
                <textarea name="rw_tahap_1_2" class="form-control" rows="2"><?= e($riwayat['tahap_1_2'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Kegiatan Tahap 2/3</label>
                <textarea name="rw_tahap_2_3" class="form-control" rows="2"><?= e($riwayat['tahap_2_3'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Wali Pemasyarakatan</label>
                <input type="text" name="rw_wali" class="form-control" value="<?= e($riwayat['wali_pemasyarakatan'] ?? '') ?>">
            </div>
            <div class="form-group form-full">
                <label class="form-label">Keterangan Tambahan</label>
                <textarea name="keterangan" class="form-control" rows="2"><?= e($napi['keterangan']) ?></textarea>
            </div>
        </div>
    </div>
</div>

<!-- Tombol -->
<div class="card">
    <div class="card-body" style="padding:16px 24px">
        <div class="d-flex gap-12">
            <button type="submit" class="btn btn-gold btn-lg"><i class="fas fa-floppy-disk"></i> Simpan Perubahan</button>
            <a href="<?= BASE_URL ?>/narapidana/detail.php?id=<?= $id ?>" class="btn btn-outline btn-lg"><i class="fas fa-xmark"></i> Batal</a>
        </div>
    </div>
</div>

</form>

<script>
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('photoPreview').src = e.target.result;
            document.getElementById('photoPreview').style.display = 'block';
            document.getElementById('photoPlaceholder').style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
