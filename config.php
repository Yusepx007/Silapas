<?php
// =============================================
// Konfigurasi Database & Aplikasi
// SILAPAS — Sistem Informasi Lembaga Pemasyarakatan
// =============================================

// =============================================
// GANTI nilai di bawah sesuai panel InfinityFree:
// Panel → Basis Data MySQL → lihat MySQL Server & nama DB
// =============================================
define('DB_HOST', 'sql204.infinityfree.com'); // InfinityFree MySQL Server
define('DB_USER', 'if0_42977027');             // Username MySQL
define('DB_PASS', 'Lapas2026');                // Password MySQL
define('DB_NAME', 'if0_42977027_lapas_db');   // Nama Database

define('SITE_NAME', 'Lapas Tasikmalaya');
define('SITE_TITLE', 'SILAPAS LAPAS');

// =============================================
// Konfigurasi Jaringan Kantor (Multi-IP)
// =============================================
// IP Komputer Server (yang menjalankan XAMPP)
// Komputer lain di kantor akses via IP ini
define('SERVER_IP_1', '192.168.1.2');    // ← IP Wi-Fi  (jaringan kantor)
define('SERVER_IP_2', '192.168.56.1');   // ← IP Ethernet / VirtualBox

// Daftar IP / subnet yang DIIZINKAN mengakses SILAPAS
// Tambahkan IP komputer kantor di sini untuk keamanan
// Kosongkan array [] untuk mengizinkan SEMUA perangkat
// Dikosongkan agar bisa diakses dari hosting publik (InfinityFree)
// Isi kembali dengan IP kantor jika kembali ke mode lokal
define('ALLOWED_NETWORK', []);

// =============================================
// Base URL — otomatis mengikuti IP yang diakses
// Mendukung: localhost, 192.168.1.2, 192.168.56.1
// =============================================
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('BASE_URL', $protocol . '://' . $host . '/Silapas');

// =============================================
// Cek Akses Jaringan (IP Whitelist)
// =============================================
if (!empty(ALLOWED_NETWORK)) {
    $clientIP  = $_SERVER['REMOTE_ADDR'] ?? '';
    $ipAllowed = false;
    foreach (ALLOWED_NETWORK as $allowedEntry) {
        // Cek exact match atau subnet prefix (misal '192.168.1.')
        if ($clientIP === $allowedEntry || str_starts_with($clientIP, $allowedEntry)) {
            $ipAllowed = true;
            break;
        }
    }
    if (!$ipAllowed) {
        http_response_code(403);
        die('<div style="font-family:Arial;text-align:center;padding:80px;background:#1a1a2e;color:#e74c3c;min-height:100vh;">
            <h1 style="font-size:48px;">🔒 Akses Ditolak</h1>
            <p style="font-size:18px;color:#aaa;">IP Anda (<strong style="color:#e74c3c;">' . htmlspecialchars($clientIP) . '</strong>) tidak diizinkan mengakses sistem ini.</p>
            <p style="color:#888;">Hubungi administrator jika Anda merasa ini adalah kesalahan.</p>
        </div>');
    }
}

define('UPLOAD_PATH', __DIR__ . '/uploads/foto/');
define('UPLOAD_URL', BASE_URL . '/uploads/foto/');

// =============================================
// Koneksi PDO
// =============================================
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('<div style="font-family:Arial;padding:40px;background:#fff0f0;border:2px solid #c0392b;margin:20px;border-radius:8px;">
        <h2 style="color:#c0392b;">❌ Gagal Terhubung ke Database</h2>
        <p>Pastikan MySQL sudah berjalan di XAMPP dan database <strong>' . DB_NAME . '</strong> sudah di-import.</p>
        <p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
        <p>Langkah perbaikan: Buka phpMyAdmin → Import file <code>database.sql</code></p>
    </div>');
}

// =============================================
// Fungsi Bantuan Global
// =============================================

/**
 * Ambil pengaturan dari tabel pengaturan
 */
function getSetting(string $kunci, string $default = ''): string {
    global $pdo;
    static $cache = [];
    if (!isset($cache[$kunci])) {
        $stmt = $pdo->prepare("SELECT nilai FROM pengaturan WHERE kunci = ?");
        $stmt->execute([$kunci]);
        $row = $stmt->fetch();
        $cache[$kunci] = $row ? $row['nilai'] : $default;
    }
    return $cache[$kunci];
}

/**
 * Format tanggal Indonesia
 */
function formatTanggal(?string $date, bool $withDay = false): string {
    if (!$date || $date === '0000-00-00') return '-';
    $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
               'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $days   = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $ts     = strtotime($date);
    $day    = $withDay ? $days[date('w', $ts)] . ', ' : '';
    return $day . date('d', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

/**
 * Flash message
 */
function setFlash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Sanitasi output
 */
function e(?string $str): string {
    return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Upload foto narapidana
 */
function uploadFoto(array $file, ?string $oldFoto = null): ?string {
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    $maxSize      = 5 * 1024 * 1024; // 5MB

    if ($file['error'] !== UPLOAD_ERR_OK) return $oldFoto;
    if (!in_array($file['type'], $allowedTypes)) return $oldFoto;
    if ($file['size'] > $maxSize) return $oldFoto;

    if (!is_dir(UPLOAD_PATH)) mkdir(UPLOAD_PATH, 0777, true);

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('napi_', true) . '.' . $ext;
    $destPath = UPLOAD_PATH . $filename;

    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        // Hapus foto lama jika ada
        if ($oldFoto && file_exists(UPLOAD_PATH . $oldFoto)) {
            unlink(UPLOAD_PATH . $oldFoto);
        }
        return $filename;
    }
    return $oldFoto;
}

/**
 * Terbilang (angka ke kata Indonesia) — untuk keperluan surat
 */
function terbilang(int $angka): string {
    $satuan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan',
               'sepuluh', 'sebelas', 'dua belas', 'tiga belas', 'empat belas', 'lima belas',
               'enam belas', 'tujuh belas', 'delapan belas', 'sembilan belas'];
    $puluhan = ['', '', 'dua puluh', 'tiga puluh', 'empat puluh', 'lima puluh',
                'enam puluh', 'tujuh puluh', 'delapan puluh', 'sembilan puluh'];

    if ($angka < 20) return $satuan[$angka];
    if ($angka < 100) return trim($puluhan[intdiv($angka, 10)] . ' ' . $satuan[$angka % 10]);
    return $angka . '';
}

/**
 * Nama hari dalam Bahasa Indonesia
 */
function namaHari(string $date): string {
    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    return $days[date('w', strtotime($date))];
}
