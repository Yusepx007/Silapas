/* assets/js/main.js — Global scripts */

// =========================================================
// Auto-dismiss alerts setelah 5 detik
// =========================================================
document.querySelectorAll('.alert[id]').forEach(function(alert) {
    setTimeout(function() {
        alert.style.opacity = '0';
        alert.style.transition = 'opacity 0.5s';
        setTimeout(function() { alert.remove(); }, 500);
    }, 5000);
});

// =========================================================
// Tooltip sederhana
// =========================================================
document.querySelectorAll('[data-tooltip]').forEach(function(el) {
    el.setAttribute('title', el.dataset.tooltip);
});

// =========================================================
// Konfirmasi hapus dengan data-confirm
// =========================================================
document.querySelectorAll('[data-confirm]').forEach(function(el) {
    el.addEventListener('click', function(e) {
        if (!confirm(this.dataset.confirm)) e.preventDefault();
    });
});

// =========================================================
// Table search (client-side, jika ada input#tableSearch)
// =========================================================
var tableSearch = document.getElementById('tableSearch');
if (tableSearch) {
    tableSearch.addEventListener('input', function() {
        var q = this.value.toLowerCase();
        document.querySelectorAll('.data-table tbody tr').forEach(function(row) {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
}

// =========================================================
// Sidebar mobile toggle — dengan overlay & scroll lock
// =========================================================
var sidebarToggle = document.getElementById('sidebarToggle');
var sidebar       = document.getElementById('sidebar');
var overlay       = document.getElementById('sidebarOverlay');

function openSidebar() {
    if (!sidebar) return;
    sidebar.classList.add('mobile-open');
    if (overlay) overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeSidebar() {
    if (!sidebar) return;
    sidebar.classList.remove('mobile-open');
    if (overlay) overlay.classList.remove('active');
    // Selalu reset overflow meski apapun yang terjadi
    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';
}

if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.contains('mobile-open') ? closeSidebar() : openSidebar();
    });
}

// Klik overlay = tutup sidebar
if (overlay) {
    overlay.addEventListener('click', closeSidebar);
}

// Tutup sidebar saat link diklik di mobile
if (sidebar) {
    sidebar.querySelectorAll('a[href]').forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 900) closeSidebar();
        });
    });
}
