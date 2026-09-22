-- =============================================
-- DATABASE: lapas_db
-- Sistem Manajemen Surat Lapas Kelas IIB Tasikmalaya
-- Catatan: CREATE DATABASE & USE dihapus untuk kompatibilitas shared hosting
-- =============================================


-- =============================================
-- Table: admin
-- =============================================
CREATE TABLE IF NOT EXISTS `admin` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nama_lengkap` VARCHAR(150) NOT NULL,
  `jabatan` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin: username=admin, password=lapas2026
INSERT IGNORE INTO `admin` (`username`, `password`, `nama_lengkap`, `jabatan`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'Petugas Lapas');

-- =============================================
-- Table: narapidana
-- =============================================
CREATE TABLE IF NOT EXISTS `narapidana` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(255) NOT NULL,
  `no_register` VARCHAR(50) NOT NULL UNIQUE,
  `jenis_kelamin` ENUM('L','P') NOT NULL DEFAULT 'L',
  `tempat_lahir` VARCHAR(100) DEFAULT NULL,
  `tanggal_lahir` DATE DEFAULT NULL,
  `agama` VARCHAR(50) DEFAULT NULL,
  `pendidikan` VARCHAR(100) DEFAULT NULL,
  `pekerjaan` VARCHAR(100) DEFAULT NULL,
  `suku` VARCHAR(100) DEFAULT NULL,
  `bangsa_wn` VARCHAR(100) DEFAULT 'Indonesia',
  `status_perkawinan` ENUM('Belum Kawin','Kawin','Cerai') DEFAULT 'Belum Kawin',
  `pasal` TEXT DEFAULT NULL COMMENT 'Pasal pelanggaran',
  `perkara` TEXT DEFAULT NULL COMMENT 'Keterangan perkara/UU',
  `lama_pidana_tahun` INT DEFAULT 0,
  `lama_pidana_bulan` INT DEFAULT 0,
  `lama_pidana_hari` INT DEFAULT 0,
  `putusan_pn` VARCHAR(200) DEFAULT NULL,
  `tanggal_masuk` DATE DEFAULT NULL,
  `tanggal_1_3` DATE DEFAULT NULL COMMENT '1/3 Masa Pidana',
  `tanggal_1_2` DATE DEFAULT NULL COMMENT '1/2 Masa Pidana',
  `tanggal_2_3` DATE DEFAULT NULL COMMENT '2/3 Masa Pidana',
  `ekspirasi` DATE DEFAULT NULL COMMENT 'Tanggal Ekspirasi',
  `ekspirasi_pb` DATE DEFAULT NULL COMMENT 'Ekspirasi PB khusus',
  `kegiatan` ENUM('CB','PB','CMB') DEFAULT 'PB' COMMENT 'CB=Cuti Bersyarat, PB=Pembebasan Bersyarat, CMB=CMB',
  `foto` VARCHAR(255) DEFAULT NULL,
  `alamat` TEXT DEFAULT NULL COMMENT 'Alamat narapidana',
  `tempat_asimilasi` TEXT DEFAULT NULL COMMENT 'Alamat tempat asimilasi/penjamin',
  `keterangan` TEXT DEFAULT NULL,
  `status` ENUM('aktif','bebas','meninggal') DEFAULT 'aktif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: penjamin
-- =============================================
CREATE TABLE IF NOT EXISTS `penjamin` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `narapidana_id` INT NOT NULL,
  `nama` VARCHAR(255) NOT NULL,
  `tempat_lahir` VARCHAR(100) DEFAULT NULL,
  `tanggal_lahir` DATE DEFAULT NULL,
  `agama` VARCHAR(50) DEFAULT NULL,
  `suku` VARCHAR(100) DEFAULT NULL,
  `bangsa_wn` VARCHAR(100) DEFAULT 'Indonesia',
  `pendidikan` VARCHAR(100) DEFAULT NULL,
  `pekerjaan` VARCHAR(100) DEFAULT NULL,
  `hubungan` VARCHAR(100) DEFAULT NULL COMMENT 'Hubungan dengan narapidana',
  `alamat` TEXT DEFAULT NULL,
  `hp` VARCHAR(20) DEFAULT NULL,
  `bentuk_jaminan` TEXT DEFAULT NULL,
  `urutan` INT DEFAULT 1 COMMENT '1=penjamin utama, dst',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`narapidana_id`) REFERENCES `narapidana`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: keluarga_penjamin
-- =============================================
CREATE TABLE IF NOT EXISTS `keluarga_penjamin` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `penjamin_id` INT NOT NULL,
  `nama` VARCHAR(255) NOT NULL,
  `umur` INT DEFAULT NULL,
  `jenis_kelamin` ENUM('L','P') DEFAULT 'L',
  `pekerjaan` VARCHAR(100) DEFAULT NULL,
  `keterangan` VARCHAR(200) DEFAULT NULL COMMENT 'Contoh: Klien, Penjamin 1, Penjamin 2',
  `urutan` INT DEFAULT 1,
  FOREIGN KEY (`penjamin_id`) REFERENCES `penjamin`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: riwayat_pembinaan
-- =============================================
CREATE TABLE IF NOT EXISTS `riwayat_pembinaan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `narapidana_id` INT NOT NULL,
  `perilaku_pribadi` TEXT DEFAULT NULL,
  `kesehatan` TEXT DEFAULT NULL,
  `kegemaran` TEXT DEFAULT NULL,
  `cita_cita` TEXT DEFAULT NULL,
  `pendidikan_ketrampilan` TEXT DEFAULT NULL,
  `hubungan_petugas` TEXT DEFAULT NULL,
  `hubungan_sesama` TEXT DEFAULT NULL,
  `hubungan_keluarga` TEXT DEFAULT NULL,
  `tahap_1_3` TEXT DEFAULT NULL COMMENT 'Kegiatan tahap 1/3',
  `tahap_1_2` TEXT DEFAULT NULL COMMENT 'Kegiatan tahap 1/2',
  `tahap_2_3` TEXT DEFAULT NULL COMMENT 'Kegiatan tahap 2/3',
  `wali_pemasyarakatan` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`narapidana_id`) REFERENCES `narapidana`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: sidang_tpp
-- =============================================
CREATE TABLE IF NOT EXISTS `sidang_tpp` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nomor_surat` VARCHAR(150) NOT NULL,
  `tanggal_surat` DATE NOT NULL,
  `lampiran` VARCHAR(50) DEFAULT '1 (satu) halaman',
  `perihal` TEXT DEFAULT NULL,
  `hari` VARCHAR(20) DEFAULT NULL,
  `tanggal_sidang` DATE NOT NULL,
  `pukul_mulai` VARCHAR(20) DEFAULT '13.00',
  `pukul_selesai` VARCHAR(20) DEFAULT 'selesai',
  `materi` TEXT DEFAULT NULL,
  `tempat` VARCHAR(255) DEFAULT 'Aula Atas Lapas Tasikmalaya',
  `ketua_tpp` VARCHAR(150) DEFAULT 'ANDI WAHYU SUWARDI',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: sidang_narapidana (junction)
-- =============================================
CREATE TABLE IF NOT EXISTS `sidang_narapidana` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sidang_id` INT NOT NULL,
  `narapidana_id` INT NOT NULL,
  `keterangan` TEXT DEFAULT NULL,
  FOREIGN KEY (`sidang_id`) REFERENCES `sidang_tpp`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`narapidana_id`) REFERENCES `narapidana`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_sidang_napi` (`sidang_id`, `narapidana_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: surat_usulan (Pengantar CB/PB)
-- =============================================
CREATE TABLE IF NOT EXISTS `surat_usulan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nomor_surat` VARCHAR(150) NOT NULL,
  `tanggal_surat` DATE NOT NULL,
  `lampiran` VARCHAR(50) DEFAULT '1 (satu) Berkas',
  `hal` TEXT DEFAULT NULL,
  `jumlah_cb` INT DEFAULT 0,
  `jumlah_pb` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: surat_usulan_narapidana (junction)
-- =============================================
CREATE TABLE IF NOT EXISTS `surat_usulan_narapidana` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `surat_usulan_id` INT NOT NULL,
  `narapidana_id` INT NOT NULL,
  FOREIGN KEY (`surat_usulan_id`) REFERENCES `surat_usulan`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`narapidana_id`) REFERENCES `narapidana`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: pengaturan (konfigurasi instansi)
-- =============================================
CREATE TABLE IF NOT EXISTS `pengaturan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kunci` VARCHAR(100) NOT NULL UNIQUE,
  `nilai` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `pengaturan` (`kunci`, `nilai`) VALUES
('nama_instansi', 'LEMBAGA PEMASYARAKATAN KELAS IIB TASIKMALAYA'),
('kanwil', 'KANTOR WILAYAH KEMENTERIAN HUKUM DAN HAM PROVINSI JAWA BARAT'),
('alamat', 'Jl. Oto Iskandardinata No.01'),
('kode_pos', '46112'),
('telp', '(0265) 312161'),
('fax', '(0265) 312161'),
('email', 'lapas.tasikmalaya@imigrasi.go.id'),
('laman', 'www.lapas.go.id'),
('nama_kalapas', 'H. MUHAMAD SOFWAN HADI, A.Md.IP.S.H.M.H'),
('nip_kalapas', '197508091998031001'),
('kode_surat', 'WP.11.PAS.PAS15_PK.01.05.02')
ON DUPLICATE KEY UPDATE nilai = VALUES(nilai);

-- Akun admin default: username=admin password=lapas2026
-- Catatan: Password sudah di-hash menggunakan PHP password_hash()
-- Untuk generate password baru, jalankan: php -r "echo password_hash('lapas2026', PASSWORD_DEFAULT);"

