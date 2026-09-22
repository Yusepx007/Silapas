# =============================================
# SILAPAS — Setup Firewall untuk Akses Jaringan Kantor
# Jalankan script ini sebagai Administrator
# =============================================

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  SILAPAS - Setup Firewall Jaringan     " -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Hapus rule lama kalau ada
$existingRule = Get-NetFirewallRule -DisplayName "XAMPP Apache - SILAPAS LAN" -ErrorAction SilentlyContinue
if ($existingRule) {
    Remove-NetFirewallRule -DisplayName "XAMPP Apache - SILAPAS LAN"
    Write-Host "[INFO] Rule lama dihapus." -ForegroundColor Yellow
}

# Tambah rule baru: izinkan akses port 80 dari subnet kantor
New-NetFirewallRule `
    -DisplayName "XAMPP Apache - SILAPAS LAN" `
    -Direction Inbound `
    -Action Allow `
    -Protocol TCP `
    -LocalPort 80 `
    -RemoteAddress "192.168.1.0/24", "192.168.56.0/24", "127.0.0.1" `
    -Profile Any `
    -Description "Izinkan akses SILAPAS dari jaringan Wi-Fi kantor (192.168.1.x) dan Ethernet (192.168.56.x)"

Write-Host ""
Write-Host "[OK] Firewall berhasil dikonfigurasi!" -ForegroundColor Green
Write-Host ""
Write-Host "Komputer kantor sekarang bisa akses SILAPAS via:" -ForegroundColor White
Write-Host "  → http://192.168.1.2/Silapas/login.php  (Wi-Fi)" -ForegroundColor Yellow
Write-Host "  → http://192.168.56.1/Silapas/login.php (Ethernet)" -ForegroundColor Yellow
Write-Host ""
Write-Host "Pastikan XAMPP Apache & MySQL sudah RUNNING!" -ForegroundColor Cyan
Write-Host ""
Pause
