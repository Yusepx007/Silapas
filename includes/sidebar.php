<?php
// includes/sidebar.php
$active = $activePage ?? '';

function isActive(string $check, string $active): string {
    return $check === $active ? 'active' : '';
}

function isMenuOpen(array $children, string $active): string {
    return in_array($active, $children) ? 'open' : '';
}
?>

<!-- Sidebar overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">

    <div class="sidebar-brand">
        <img src="<?= BASE_URL ?>/logo_kop.png" alt="Logo Lapas"
             onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 80 80%22><circle cx=%2240%22 cy=%2240%22 r=%2238%22 fill=%22%23C9A227%22/><text x=%2240%22 y=%2248%22 text-anchor=%22middle%22 font-size=%2226%22 fill=%22%230B2545%22>L</text></svg>'">
        <div class="sidebar-brand-text">
            <h2>SIREGIS CB PB</h2>
            <span>Lapas Kelas IIB Tasikmalaya</span>
        </div>
    </div>

    <nav class="sidebar-nav">

        <a href="<?= BASE_URL ?>/dashboard.php"
           class="nav-item <?= isActive('dashboard', $active) ?>">
            <span class="nav-icon"><i class="fas fa-gauge-high"></i></span>
            <span>Dashboard</span>
        </a>

        <div class="nav-label">Data Master</div>

        <!-- Narapidana -->
        <div class="nav-group">
            <div class="nav-item <?= in_array($active, ['napi_list','napi_tambah','napi_edit','napi_detail']) ? 'active' : '' ?>"
                 onclick="toggleMenu('menuNapi', this)">
                <span class="nav-icon"><i class="fas fa-users"></i></span>
                <span>Data Narapidana</span>
                <span class="nav-toggle-icon <?= isMenuOpen(['napi_list','napi_tambah','napi_edit','napi_detail'], $active) ? 'rotated' : '' ?>"
                      id="iconNapi"><i class="fas fa-chevron-right"></i></span>
            </div>
            <div class="nav-submenu <?= isMenuOpen(['napi_list','napi_tambah','napi_edit','napi_detail'], $active) ?>"
                 id="menuNapi">
                <a href="<?= BASE_URL ?>/narapidana/index.php"
                   class="nav-item <?= isActive('napi_list', $active) ?>">
                    <span class="nav-icon"><i class="fas fa-list"></i></span>
                    <span>Daftar WBP</span>
                </a>
                <a href="<?= BASE_URL ?>/narapidana/tambah.php"
                   class="nav-item <?= isActive('napi_tambah', $active) ?>">
                    <span class="nav-icon"><i class="fas fa-user-plus"></i></span>
                    <span>Tambah WBP</span>
                </a>
            </div>
        </div>

        <!-- Sidang TPP -->
        <div class="nav-group">
            <div class="nav-item <?= in_array($active, ['sidang_list','sidang_tambah','sidang_edit']) ? 'active' : '' ?>"
                 onclick="toggleMenu('menuSidang', this)">
                <span class="nav-icon"><i class="fas fa-scale-balanced"></i></span>
                <span>Sidang TPP</span>
                <span class="nav-toggle-icon <?= isMenuOpen(['sidang_list','sidang_tambah','sidang_edit'], $active) ? 'rotated' : '' ?>"
                      id="iconSidang"><i class="fas fa-chevron-right"></i></span>
            </div>
            <div class="nav-submenu <?= isMenuOpen(['sidang_list','sidang_tambah','sidang_edit'], $active) ?>"
                 id="menuSidang">
                <a href="<?= BASE_URL ?>/sidang/index.php"
                   class="nav-item <?= isActive('sidang_list', $active) ?>">
                    <span class="nav-icon"><i class="fas fa-list-check"></i></span>
                    <span>Daftar Sidang</span>
                </a>
                <a href="<?= BASE_URL ?>/sidang/tambah.php"
                   class="nav-item <?= isActive('sidang_tambah', $active) ?>">
                    <span class="nav-icon"><i class="fas fa-calendar-plus"></i></span>
                    <span>Buat Sidang Baru</span>
                </a>
            </div>
        </div>

        <div class="nav-label">Generator Surat</div>

        <a href="<?= BASE_URL ?>/surat/pengantar_cb_pb.php"
           class="nav-item <?= isActive('surat_pengantar', $active) ?>">
            <span class="nav-icon"><i class="fas fa-envelope-open-text"></i></span>
            <span>Surat Pengantar Usulan Integrasi PB dan CB</span>
        </a>

        <a href="<?= BASE_URL ?>/surat/pernyataan.php"
           class="nav-item <?= isActive('surat_pernyataan', $active) ?>">
            <span class="nav-icon"><i class="fas fa-file-signature"></i></span>
            <span>Surat Pernyataan</span>
        </a>

        <a href="<?= BASE_URL ?>/surat/undangan_tpp.php"
           class="nav-item <?= isActive('surat_undangan', $active) ?>">
            <span class="nav-icon"><i class="fas fa-envelope"></i></span>
            <span>Undangan Integrasi PB dan CB</span>
        </a>

        <a href="<?= BASE_URL ?>/surat/daftar_litmas.php"
           class="nav-item <?= isActive('surat_daftar_litmas', $active) ?>">
            <span class="nav-icon"><i class="fas fa-file-lines"></i></span>
            <span>Usulan LITMAS</span>
        </a>

        <div class="nav-label">Lainnya</div>

        <a href="<?= BASE_URL ?>/pengaturan/index.php"
           class="nav-item <?= isActive('pengaturan', $active) ?>">
            <span class="nav-icon"><i class="fas fa-gear"></i></span>
            <span>Pengaturan Sistem</span>
        </a>

    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar">
                <?= strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="sidebar-user-info">
                <div class="name"><?= e($_SESSION['admin_name'] ?? 'Admin') ?></div>
                <div class="role"><?= e($_SESSION['admin_role'] ?? 'Administrator') ?></div>
            </div>
            <a href="<?= BASE_URL ?>/logout.php"
               class="sidebar-logout"
               title="Keluar"
               onclick="return confirm('Yakin keluar?')">
                <i class="fas fa-right-from-bracket"></i>
            </a>
        </div>
    </div>

</aside>

<script>
// ID submenu yang ada beserta parent toggle-nya
var MENUS = {
    menuNapi:   'toggleNapi',
    menuSidang: 'toggleSidang'
};

// Daftar page yang "memaksa" submenu tertentu terbuka
var FORCED_OPEN = <?= json_encode([
    'menuNapi'   => ['napi_list','napi_tambah','napi_edit','napi_detail'],
    'menuSidang' => ['sidang_list','sidang_tambah','sidang_edit'],
]) ?>;

var CURRENT_PAGE = <?= json_encode($active) ?>;

function toggleMenu(menuId, elem) {
    var menu = document.getElementById(menuId);
    if (!menu) return;
    var isOpen = menu.classList.toggle('open');

    // Rotasi icon chevron pada elemen yang diklik
    var icon = elem.querySelector('.nav-toggle-icon');
    if (icon) icon.classList.toggle('rotated', isOpen);

    // Simpan state ke sessionStorage agar bertahan saat navigasi
    try {
        sessionStorage.setItem('sb_' + menuId, isOpen ? '1' : '0');
    } catch(e) {}
}

// Restore state submenu saat halaman dimuat
document.addEventListener('DOMContentLoaded', function() {
    Object.keys(MENUS).forEach(function(menuId) {
        var menu = document.getElementById(menuId);
        if (!menu) return;

        // Cek apakah halaman ini memaksa submenu terbuka (dari PHP)
        var forcedPages = FORCED_OPEN[menuId] || [];
        var isForced    = forcedPages.indexOf(CURRENT_PAGE) !== -1;

        if (isForced) {
            // PHP sudah set class 'open', pastikan konsisten
            menu.classList.add('open');
            setIconRotated(menuId, true);
            // Update sessionStorage agar sinkron
            try { sessionStorage.setItem('sb_' + menuId, '1'); } catch(e) {}
        } else {
            // Tidak dipaksa terbuka — cek sessionStorage
            var stored;
            try { stored = sessionStorage.getItem('sb_' + menuId); } catch(e) {}

            if (stored === '1') {
                menu.classList.add('open');
                setIconRotated(menuId, true);
            } else if (stored === '0') {
                menu.classList.remove('open');
                setIconRotated(menuId, false);
            }
            // Jika null (belum pernah disentuh), biarkan state dari PHP
        }
    });
});

function setIconRotated(menuId, rotated) {
    // Cari toggle icon di dalam sibling sebelum submenu
    var menu   = document.getElementById(menuId);
    if (!menu) return;
    var parent = menu.parentElement;           // nav-group
    if (!parent) return;
    var toggle = parent.querySelector('.nav-toggle-icon');
    if (toggle) toggle.classList.toggle('rotated', rotated);
}

// =====================================================
// Fix: Simpan & restore posisi scroll sidebar
// Mencegah sidebar loncat ke atas saat pindah halaman
// =====================================================
document.addEventListener('DOMContentLoaded', function() {
    var nav = document.querySelector('.sidebar-nav');
    if (!nav) return;

    // Restore posisi scroll
    try {
        var savedScroll = sessionStorage.getItem('sb_scroll');
        if (savedScroll !== null) {
            nav.scrollTop = parseInt(savedScroll, 10);
        }
    } catch(e) {}

    // Simpan posisi scroll setiap kali berubah (debounced)
    var scrollTimer;
    nav.addEventListener('scroll', function() {
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(function() {
            try {
                sessionStorage.setItem('sb_scroll', nav.scrollTop);
            } catch(e) {}
        }, 100);
    });

    // Juga simpan tepat sebelum link diklik (untuk navigasi cepat)
    nav.addEventListener('click', function(e) {
        var link = e.target.closest('a[href]');
        if (link) {
            try {
                sessionStorage.setItem('sb_scroll', nav.scrollTop);
            } catch(e) {}
        }
    });
});
</script>

