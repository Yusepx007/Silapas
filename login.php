<?php
// login.php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_SESSION['admin_id'])) {
    redirect(BASE_URL . '/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_name'] = $admin['nama_lengkap'];
            $_SESSION['admin_role'] = $admin['jabatan'];
            session_regenerate_id(true);
            redirect(BASE_URL . '/dashboard.php');
        } else {
            $error = 'Username atau password salah. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SIREGIS CB PB Kelas IIB Tasikmalaya</title>
    <meta name="description" content="Login ke SILAPAS Lembaga Pemasyarakatan Kelas IIB Tasikmalaya">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        .p:nth-child(1)  { left:10%;animation-duration:12s;animation-delay:0s; }
        .p:nth-child(2)  { left:25%;animation-duration:15s;animation-delay:2s; }
        .p:nth-child(3)  { left:40%;animation-duration:10s;animation-delay:4s; }
        .p:nth-child(4)  { left:55%;animation-duration:13s;animation-delay:1s; }
        .p:nth-child(5)  { left:70%;animation-duration:11s;animation-delay:3s; }
        .p:nth-child(6)  { left:85%;animation-duration:14s;animation-delay:5s; }
        .p:nth-child(7)  { left:15%;animation-duration:16s;animation-delay:6s; }
        .p:nth-child(8)  { left:60%;animation-duration:9s; animation-delay:2.5s; }
        .p:nth-child(9)  { left:80%;animation-duration:12s;animation-delay:7s;width:5px;height:5px; }
        .p:nth-child(10) { left:35%;animation-duration:18s;animation-delay:0.5s; }
    </style>
</head>
<body class="login-body">

    <div class="login-particles">
        <?php for ($i = 1; $i <= 10; $i++): ?>
        <span class="p"></span>
        <?php endfor; ?>
    </div>

    <div class="login-card">
        <!-- Logo & Header -->
        <div class="login-logo">
            <img src="<?= BASE_URL ?>/logo.jpg" alt="Logo Lapas" onerror="this.style.display='none'">
            <div class="login-app-name">SIREGIS CB PB</div>
            <h1>Sistem Informasi Registrasi Usulan Program CB dan PB</h1>
            <p>Lapas Kelas IIB Tasikmalaya</p>
        </div>

        <div class="login-divider"></div>

        <?php if ($error): ?>
        <div class="login-alert">
            <i class="fas fa-triangle-exclamation"></i>
            <?= e($error) ?>
        </div>
        <?php endif; ?>

        <!-- Form Login -->
        <form class="login-form" method="POST" action="" id="loginForm">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrap">
                    <span class="input-icon"><i class="fas fa-user"></i></span>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Masukkan username"
                        value="<?= e($_POST['username'] ?? '') ?>"
                        autocomplete="username"
                        required
                        autofocus
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Masukkan password"
                        autocomplete="current-password"
                        required
                    >
                    <button type="button" class="pw-toggle" id="pwToggle" aria-label="Tampilkan password" tabindex="-1"
                        style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:rgba(255,255,255,0.5);font-size:16px;padding:4px 6px;display:flex;align-items:center;line-height:1;">
                        <i class="fas fa-eye" id="pwToggleIcon"></i>
                    </button>
                </div>
            </div>

            <div style="text-align: right; margin-bottom: 16px; font-size: 13px;">
                <a href="<?= BASE_URL ?>/lupa_password.php" style="color: #64b5f6; text-decoration: none;"><i class="fas fa-key"></i> Lupa Kata Sandi?</a>
            </div>

            <button type="submit" class="btn-login" id="loginBtn">
                <span id="loginText">
                    <i class="fas fa-right-to-bracket"></i> Masuk ke Sistem
                </span>
            </button>
        </form>

        <div class="login-footer" style="margin-top: 25px;">
            Maganghub Kemnaker Lapas Kelas IIB Tasikmalaya tahun 2026
        </div>
    </div>

    <script>
        // Submit loading
        document.getElementById('loginForm').addEventListener('submit', function() {
            var btn = document.getElementById('loginBtn');
            var txt = document.getElementById('loginText');
            btn.disabled = true;
            txt.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
        });

        // Toggle show/hide password
        var pwToggle = document.getElementById('pwToggle');
        var pwInput  = document.getElementById('password');
        var pwIcon   = document.getElementById('pwToggleIcon');
        if (pwToggle && pwInput) {
            pwToggle.addEventListener('click', function() {
                var isHidden = pwInput.type === 'password';
                pwInput.type = isHidden ? 'text' : 'password';
                pwIcon.className = isHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
                pwToggle.setAttribute('aria-label', isHidden ? 'Sembunyikan password' : 'Tampilkan password');
                pwInput.focus();
            });
        }
    </script>
</body>
</html>
