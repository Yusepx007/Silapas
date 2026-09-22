<?php
// qr.php — Halaman QR Code Akses SILAPAS
// Buka di browser komputer server, lalu scan dari HP

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

// Ambil IP server yang sedang diakses (bukan localhost)
$serverIP = $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';
if ($serverIP === '127.0.0.1' || $serverIP === '::1') {
    // Coba ambil IP lokal dari nama host
    $serverIP = gethostbyname(gethostname());
}

$loginURL = $protocol . '://' . $serverIP . '/Silapas/login.php';
$encoded  = urlencode($loginURL);
$qrAPI    = "https://api.qrserver.com/v1/create-qr-code/?size=280x280&ecc=M&data={$encoded}";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Akses SILAPAS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            padding: 20px;
        }

        .card {
            background: rgba(255,255,255,0.06);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 28px;
            padding: 48px 40px;
            text-align: center;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 32px 80px rgba(0,0,0,0.4);
            animation: fadeUp 0.6s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .badge {
            display: inline-block;
            background: linear-gradient(135deg, #f7971e, #ffd200);
            color: #1a1a2e;
            font-weight: 700;
            font-size: 11px;
            letter-spacing: 2px;
            text-transform: uppercase;
            padding: 5px 14px;
            border-radius: 20px;
            margin-bottom: 20px;
        }

        h1 {
            color: #fff;
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }

        .subtitle {
            color: rgba(255,255,255,0.5);
            font-size: 14px;
            margin-bottom: 36px;
        }

        .qr-wrap {
            background: #fff;
            border-radius: 20px;
            padding: 20px;
            display: inline-block;
            margin-bottom: 28px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            position: relative;
        }

        .qr-wrap img {
            display: block;
            width: 240px;
            height: 240px;
            border-radius: 8px;
        }

        .qr-logo {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 52px; height: 52px;
            background: linear-gradient(135deg, #302b63, #24243e);
            border: 3px solid #fff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .url-box {
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .url-icon { font-size: 18px; }

        .url-text {
            flex: 1;
            text-align: left;
        }

        .url-label {
            font-size: 10px;
            color: rgba(255,255,255,0.4);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }

        .url-value {
            font-size: 14px;
            color: #ffd200;
            font-weight: 600;
            word-break: break-all;
        }

        .copy-btn {
            background: rgba(255,255,255,0.1);
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 12px;
            padding: 6px 12px;
            cursor: pointer;
            transition: background 0.2s;
            white-space: nowrap;
        }
        .copy-btn:hover { background: rgba(255,255,255,0.2); }
        .copy-btn.copied { background: #27ae60; }

        .steps {
            text-align: left;
            margin-bottom: 28px;
        }

        .step {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
        }

        .step-num {
            width: 26px; height: 26px;
            background: linear-gradient(135deg, #f7971e, #ffd200);
            color: #1a1a2e;
            font-weight: 700;
            font-size: 12px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .step-text {
            color: rgba(255,255,255,0.75);
            font-size: 13px;
            line-height: 1.5;
        }

        .step-text strong { color: #fff; }

        .divider {
            border: none;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin: 24px 0;
        }

        .footer {
            color: rgba(255,255,255,0.3);
            font-size: 12px;
        }

        /* Pulse animation on QR */
        .qr-wrap::before {
            content: '';
            position: absolute;
            inset: -8px;
            border-radius: 26px;
            border: 2px solid rgba(255,210,0,0.3);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50%       { opacity: 0.8; transform: scale(1.02); }
        }
    </style>
</head>
<body>
<div class="card">
    <div class="badge">📡 Akses Jaringan Kantor</div>
    <h1>🔳 Scan untuk Masuk</h1>
    <p class="subtitle">Tunjukkan layar ini, scan QR dari HP</p>

    <div class="qr-wrap">
        <img src="<?= $qrAPI ?>" alt="QR Code SILAPAS" id="qrImg">
        <div class="qr-logo">🏛️</div>
    </div>

    <div class="url-box">
        <span class="url-icon">🔗</span>
        <div class="url-text">
            <div class="url-label">URL Login</div>
            <div class="url-value" id="urlDisplay"><?= htmlspecialchars($loginURL) ?></div>
        </div>
        <button class="copy-btn" id="copyBtn" onclick="copyURL()">Salin</button>
    </div>

    <div class="steps">
        <div class="step">
            <div class="step-num">1</div>
            <div class="step-text">Sambungkan HP ke <strong>WiFi kantor</strong> yang sama</div>
        </div>
        <div class="step">
            <div class="step-num">2</div>
            <div class="step-text">Buka <strong>Kamera HP</strong> atau aplikasi <strong>QR Scanner</strong></div>
        </div>
        <div class="step">
            <div class="step-num">3</div>
            <div class="step-text">Arahkan ke QR Code di atas → tap notifikasi yang muncul</div>
        </div>
    </div>

    <hr class="divider">
    <div class="footer">
        SILAPAS • Lapas Kelas IIB Tasikmalaya &bull; <?= date('Y') ?>
    </div>
</div>

<script>
function copyURL() {
    const url = document.getElementById('urlDisplay').innerText;
    navigator.clipboard.writeText(url).then(() => {
        const btn = document.getElementById('copyBtn');
        btn.textContent = '✓ Disalin!';
        btn.classList.add('copied');
        setTimeout(() => {
            btn.textContent = 'Salin';
            btn.classList.remove('copied');
        }, 2000);
    });
}
</script>
</body>
</html>
