<?php
// includes/header.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Dashboard') ?> — LAPAS Kelas IIB Tasikmalaya</title>
    <meta name="description" content="SILAPAS Lembaga Pemasyarakatan Kelas IIB Tasikmalaya">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-wrapper">

    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main-wrapper">

        <!-- TOP HEADER -->
        <header class="top-header">
            <!-- Hamburger (mobile only) -->
            <button id="sidebarToggle" class="hamburger-btn" aria-label="Buka menu">
                <i class="fas fa-bars"></i>
            </button>

            <div class="header-title">
                <h1><?= e($pageTitle ?? 'Dashboard') ?></h1>
                <?php if (!empty($breadcrumb)): ?>
                <div class="breadcrumb">
                    <?php foreach ($breadcrumb as $i => $crumb): ?>
                        <?php if ($i > 0): ?> &rsaquo; <?php endif; ?>
                        <?php if (isset($crumb['url'])): ?>
                            <a href="<?= e($crumb['url']) ?>" style="color:var(--text-muted)"><?= e($crumb['label']) ?></a>
                        <?php else: ?>
                            <span><?= e($crumb['label']) ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="header-actions">
                <span class="header-date">
                    <i class="fas fa-calendar-days" style="color:var(--text-muted)"></i>
                    <?= formatTanggal(date('Y-m-d'), true) ?>
                </span>
                <a href="<?= BASE_URL ?>/logout.php"
                   class="btn btn-outline btn-sm"
                   onclick="return confirm('Yakin ingin keluar?')">
                   <i class="fas fa-right-from-bracket"></i> <span class="btn-logout-text">Keluar</span>
                </a>
            </div>
        </header>

        <!-- PAGE CONTENT -->
        <main class="page-content fade-in">
            <?php
            $flash = getFlash();
            if ($flash):
                $icons = ['success' => 'circle-check', 'danger' => 'circle-xmark', 'warning' => 'triangle-exclamation', 'info' => 'circle-info'];
                $icon  = $icons[$flash['type']] ?? 'circle-info';
            ?>
            <div class="alert alert-<?= e($flash['type']) ?>" id="flashAlert">
                <i class="fas fa-<?= $icon ?>"></i>
                <?= e($flash['message']) ?>
                <button onclick="this.parentElement.remove()" style="margin-left:auto;background:none;border:none;cursor:pointer;font-size:16px;color:inherit;">&times;</button>
            </div>
            <script>setTimeout(function(){ var a=document.getElementById('flashAlert'); if(a){a.style.opacity='0';a.style.transition='opacity 0.5s';setTimeout(function(){a.remove();},500);} }, 4000);</script>
            <?php endif; ?>
