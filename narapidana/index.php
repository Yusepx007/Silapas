<?php
// narapidana/index.php — Daftar WBP
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth.php';

$pageTitle  = 'Daftar Warga Binaan Pemasyarakatan';
$activePage = 'napi_list';
$breadcrumb = [
    ['label' => 'Dashboard', 'url' => BASE_URL . '/dashboard.php'],
    ['label' => 'Data Narapidana']
];

// Filter & Search
$search  = trim($_GET['search'] ?? '');
$filter  = trim($_GET['filter'] ?? 'semua');
$perPage = 15;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// Build query
$where  = ["status = 'aktif'"];
$params = [];

if ($search !== '') {
    $where[]  = "(nama LIKE ? OR no_register LIKE ? OR pasal LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (in_array($filter, ['CB', 'PB'])) {
    $where[]  = "kegiatan = ?";
    $params[] = $filter;
} elseif ($filter === 'ekspirasi') {
    $where[] = "ekspirasi BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
}

$whereClause = 'WHERE ' . implode(' AND ', $where);

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM narapidana $whereClause");
$totalStmt->execute($params);
$total     = (int)$totalStmt->fetchColumn();
$totalPage = (int)ceil($total / $perPage);

$stmt = $pdo->prepare("SELECT * FROM narapidana $whereClause ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$napiList = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- Search & Filter -->
<div class="card mb-20">
    <div class="card-body" style="padding:16px 20px">
        <form method="GET" class="search-bar">
            <div class="search-input-wrap">
                <span class="search-icon">🔍</span>
                <input type="text" name="search" placeholder="Cari nama, nomor register, pasal..."
                       value="<?= e($search) ?>">
            </div>
            <select name="filter" class="form-control" style="width:auto;min-width:160px">
                <option value="semua"   <?= $filter==='semua'   ?'selected':'' ?>>Semua Kategori</option>
                <option value="CB"      <?= $filter==='CB'      ?'selected':'' ?>>Cuti Bersyarat (CB)</option>
                <option value="PB"      <?= $filter==='PB'      ?'selected':'' ?>>Pembebasan Bersyarat (PB)</option>
                <option value="ekspirasi" <?= $filter==='ekspirasi'?'selected':'' ?>>⏰ Ekspirasi ≤ 30 Hari</option>
            </select>
            <button type="submit" class="btn btn-primary">Cari</button>
            <?php if ($search || $filter !== 'semua'): ?>
            <a href="<?= BASE_URL ?>/narapidana/index.php" class="btn btn-outline">Reset</a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/narapidana/tambah.php" class="btn btn-gold" style="margin-left:auto">
                <i class="fas fa-user-plus"></i> Tambah WBP
            </a>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-list"></i> Daftar WBP (<?= $total ?> data)</h2>
        <div style="display:flex;gap:8px">
            <span class="badge badge-pb"><?= $pdo->query("SELECT COUNT(*) FROM narapidana WHERE status='aktif' AND kegiatan='PB'")->fetchColumn() ?> PB</span>
            <span class="badge badge-cb"><?= $pdo->query("SELECT COUNT(*) FROM narapidana WHERE status='aktif' AND kegiatan='CB'")->fetchColumn() ?> CB</span>
        </div>
    </div>
    <div class="card-body" style="padding:0">
        <?php if (empty($napiList)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-users" style="font-size:48px;opacity:0.3"></i></div>
            <h3>Tidak ada data ditemukan</h3>
            <p>Coba ubah filter atau tambahkan data WBP baru</p>
            <a href="<?= BASE_URL ?>/narapidana/tambah.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Tambah WBP</a>
        </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px">No</th>
                        <th class="col-hide-mobile" style="width:70px">Foto</th>
                        <th>Nama</th>
                        <th>No. Register</th>
                        <th class="col-hide-mobile">Pasal</th>
                        <th class="col-hide-mobile">Lama Pidana</th>
                        <th class="col-hide-mobile">2/3 MP</th>
                        <th>Ekspirasi</th>
                        <th>Kategori</th>
                        <th style="width:60px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($napiList as $i => $n): ?>
                    <?php
                        $no = $offset + $i + 1;
                        $hariSisa = $n['ekspirasi'] ? (int)((strtotime($n['ekspirasi']) - time()) / 86400) : 9999;
                        $rowStyle = $hariSisa <= 7 ? 'background:rgba(239,68,68,0.05)' : '';
                    ?>
                    <tr style="<?= $rowStyle ?>">
                        <td style="color:var(--text-muted);font-size:12px"><?= $no ?></td>
                        <td class="col-hide-mobile">
                            <?php if ($n['foto']): ?>
                            <img src="<?= UPLOAD_URL . e($n['foto']) ?>"
                                 alt="Foto <?= e($n['nama']) ?>"
                                 class="napi-photo"
                                 onerror="this.style.display='none'">
                            <?php else: ?>
                            <div class="napi-photo-placeholder">👤</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-weight:600"><?= e($n['nama']) ?></div>
                            <div style="font-size:11px;color:var(--text-muted)"><?= e($n['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan') ?></div>
                        </td>
                        <td style="font-size:12px;font-weight:500;color:var(--primary)"><?= e($n['no_register']) ?></td>
                        <td class="col-hide-mobile" style="font-size:11px;max-width:160px"><?= e(mb_substr($n['pasal'] ?? '-', 0, 60)) . (mb_strlen($n['pasal'] ?? '') > 60 ? '...' : '') ?></td>
                        <td class="col-hide-mobile" style="font-size:12px;white-space:nowrap">
                            <?php
                                $pid = [];
                                if ($n['lama_pidana_tahun']) $pid[] = $n['lama_pidana_tahun'] . ' Thn';
                                if ($n['lama_pidana_bulan']) $pid[] = $n['lama_pidana_bulan'] . ' Bln';
                                if ($n['lama_pidana_hari'])  $pid[] = $n['lama_pidana_hari'] . ' Hr';
                                echo $pid ? implode(' ', $pid) : '-';
                            ?>
                        </td>
                        <td class="col-hide-mobile" style="font-size:11px"><?= $n['tanggal_2_3'] ? formatTanggal($n['tanggal_2_3']) : '-' ?></td>
                        <td style="font-size:11px">
                            <?= $n['ekspirasi'] ? formatTanggal($n['ekspirasi']) : '-' ?>
                            <?php if ($hariSisa <= 30 && $hariSisa >= 0): ?>
                            <br><span class="badge badge-danger" style="font-size:9px"><?= $hariSisa ?> hr</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge badge-<?= strtolower($n['kegiatan']) ?>"><?= e($n['kegiatan']) ?></span></td>
                        <td>
                            <div class="action-group">
                                <a href="<?= BASE_URL ?>/narapidana/detail.php?id=<?= $n['id'] ?>"
                                   class="btn btn-info btn-sm" title="Detail"><i class="fas fa-eye"></i></a>
                                <a href="<?= BASE_URL ?>/narapidana/edit.php?id=<?= $n['id'] ?>"
                                   class="btn btn-primary btn-sm btn-mobile-hide" title="Edit"><i class="fas fa-pen-to-square"></i></a>
                                <a href="<?= BASE_URL ?>/narapidana/hapus.php?id=<?= $n['id'] ?>"
                                   class="btn btn-danger btn-sm btn-mobile-hide" title="Hapus"
                                   onclick="return confirm('Hapus data <?= addslashes($n['nama']) ?>?')"><i class="fas fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPage > 1): ?>
        <div class="pagination" style="padding:16px">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&filter=<?= urlencode($filter) ?>">‹ Prev</a>
            <?php endif; ?>
            <?php for ($p = max(1, $page-2); $p <= min($totalPage, $page+2); $p++): ?>
            <?php if ($p === $page): ?>
            <span class="active"><?= $p ?></span>
            <?php else: ?>
            <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&filter=<?= urlencode($filter) ?>"><?= $p ?></a>
            <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $totalPage): ?>
            <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&filter=<?= urlencode($filter) ?>">Next ›</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
