# Sistem Manajemen Surat LAPAS Kelas IIB Tasikmalaya

## Instalasi

### Persyaratan
- XAMPP (PHP 7.4+ dan MySQL/MariaDB)
- PHP ZipArchive extension (biasanya sudah aktif di XAMPP)

### Langkah Setup

1. **Copy folder** ini ke `C:\xampp\htdocs\Project_Lapas\`

2. **Import Database**
   - Buka phpMyAdmin: http://localhost/phpmyadmin
   - Klik "New" → buat database bernama `lapas_db`
   - Klik "Import" → pilih file `database.sql` → klik "Go"

3. **Upload Logo** (Opsional)
   - Copy logo LAPAS ke `Project_Lapas\logo.png`

4. **Akses Aplikasi**
   - Buka browser: http://localhost/Project_Lapas
   - Login dengan:
     - **Username:** `admin`
     - **Password:** `lapas2026`

5. **Pengaturan Awal**
   - Setelah login, masuk ke menu **Pengaturan**
   - Update nama Kalapas, NIP, dan informasi instansi

---

## Struktur Folder

```
Project_Lapas/
├── assets/
│   ├── css/style.css        — Style utama
│   └── js/main.js           — JavaScript global
├── generate/
│   ├── DocxWriter.php       — Generator file Word (.docx)
│   ├── pengantar_word.php   — Download Surat Pengantar CB/PB
│   ├── pernyataan_word.php  — Download Surat Pernyataan
│   ├── undangan_word.php    — Download Undangan Sidang TPP
│   ├── daftar_tpp_word.php  — Download Daftar WBP TPP
│   ├── daftar_litmas_word.php — Download Daftar WBP LITMAS
│   └── data_primer_word.php — Download Data Primer LITMAS
├── includes/
│   ├── auth.php             — Middleware autentikasi
│   ├── header.php           — Template header
│   ├── sidebar.php          — Navigasi sidebar
│   └── footer.php           — Template footer
├── narapidana/
│   ├── index.php            — Daftar WBP
│   ├── tambah.php           — Form tambah WBP
│   ├── edit.php             — Form edit WBP
│   ├── detail.php           — Detail WBP
│   └── hapus.php            — Hapus WBP
├── pengaturan/
│   └── index.php            — Pengaturan sistem
├── sidang/
│   ├── index.php            — Daftar Sidang TPP
│   ├── tambah.php           — Buat sidang baru
│   ├── edit.php             — Edit sidang
│   └── hapus.php            — Hapus sidang
├── surat/
│   ├── pengantar_cb_pb.php  — Generator Surat Pengantar
│   ├── pernyataan.php       — Generator Surat Pernyataan
│   ├── undangan_tpp.php     — Generator Undangan TPP
│   ├── daftar_tpp.php       — Generator Daftar WBP TPP
│   ├── daftar_litmas.php    — Generator Daftar LITMAS
│   ├── data_primer.php      — Generator Data Primer LITMAS
│   └── ajax_daftar_tpp.php  — AJAX preview daftar TPP
├── uploads/foto/            — Foto narapidana (auto-created)
├── config.php               — Konfigurasi utama
├── dashboard.php            — Halaman dashboard
├── login.php                — Halaman login
├── logout.php               — Handler logout
├── index.php                — Entry point
├── database.sql             — Schema database
└── logo.png                 — Logo LAPAS
```

---

## Fitur

| Fitur | Keterangan |
|-------|-----------|
| Login | Autentikasi admin dengan session |
| Dashboard | Statistik WBP, sidang mendatang, WBP ekspirasi |
| Data WBP | CRUD lengkap dengan foto, data pribadi, pidana, penjamin, riwayat pembinaan |
| Sidang TPP | Buat sidang, pilih peserta WBP |
| Surat Pengantar CB/PB | Preview + download Word |
| Surat Pernyataan | Preview + download Word per WBP |
| Undangan Sidang TPP | Preview + download Word |
| Daftar WBP Sidang TPP | Preview + download Word |
| Daftar WBP LITMAS | Preview + download Word |
| Data Primer LITMAS | Preview + download Word |
| Pengaturan | Nama instansi, Kalapas, dll |

---

## Akses Jaringan WiFi (Multi-user)

Aplikasi ini berjalan di XAMPP dan otomatis dapat diakses dari jaringan lokal:

1. Pastikan komputer server dan komputer klien terhubung ke WiFi yang sama
2. Cari IP address komputer server (jalankan `ipconfig` di CMD)
3. Dari komputer lain, akses: `http://[IP-SERVER]/Project_Lapas`

Contoh: `http://192.168.1.100/Project_Lapas`
