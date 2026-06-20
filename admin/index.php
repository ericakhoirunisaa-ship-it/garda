<?php
require_once 'config.php';
requireLogin();

// Migrate kelompok column if not exists
(function() use ($conn) {
    $cols = array_column($conn->query("SHOW COLUMNS FROM peserta")->fetch_all(MYSQLI_ASSOC), 'Field');
    if (!in_array('kelompok', $cols))
        $conn->query("ALTER TABLE peserta ADD COLUMN `kelompok` VARCHAR(100) NULL DEFAULT NULL");
})();

$search          = trim($_GET['search'] ?? '');
$kecFilter       = trim($_GET['kec'] ?? 'all');
$kelompokFilter  = trim($_GET['kelompok'] ?? 'all');

// Kode → nama kecamatan (DB menyimpan kode angka, bukan nama)
$kecMap = [
    "010" => "Dampal Selatan",
    "020" => "Dampal Utara",
    "030" => "Dondo",
    "031" => "Ogodeide",
    "032" => "Basidondo",
    "040" => "Baolan",
    "041" => "Lampasio",
    "050" => "Galang",
    "060" => "Tolitoli Utara",
    "061" => "Dako Pemean",
];

$joinWawancara = "LEFT JOIN wawancara w ON w.id = (
    SELECT id FROM wawancara WHERE peserta_id = p.id ORDER BY created_at DESC LIMIT 1
)";

$where  = "WHERE p.status_seleksi = 'Diterima'";
$params = [];
$types  = '';
if ($search !== '') {
    $where .= " AND (p.nama LIKE ? OR p.no_telp LIKE ? OR p.email LIKE ?)";
    $like    = "%$search%";
    $params  = [$like, $like, $like];
    $types   = 'sss';
}
if ($kecFilter !== 'all' && isset($kecMap[$kecFilter])) {
    $where .= " AND p.alamat_kec = ?";
    $params[] = $kecFilter;
    $types   .= 's';
}

if ($kelompokFilter !== 'all') {
    $where .= " AND p.kelompok = ?";
    $params[]  = $kelompokFilter;
    $types    .= 's';
}

$sql = "SELECT p.id, p.nama, p.posisi, p.posisi_daftar, p.alamat_kec,
               p.pendidikan, p.jenis_kelamin, p.no_telp, p.email,
               p.kelompok,
               w.id AS wawancara_id, w.nilai, w.predikat,
               a.nama AS pewawancara
        FROM peserta p
        $joinWawancara
        LEFT JOIN admin_seleksi a ON a.id = w.admin_id
        $where
        ORDER BY p.alamat_kec ASC, p.nama ASC";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$allPeserta = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Grup berdasarkan kode kecamatan; kode sudah numerik → ksort() menghasilkan urutan benar
$grouped = [];
foreach ($allPeserta as $p) {
    $kode = trim($p['alamat_kec']) ?: '???';
    $grouped[$kode][] = $p;
}
ksort($grouped);

// Stats keseluruhan
$stats = $conn->query("SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN w.id IS NOT NULL THEN 1 ELSE 0 END) AS sudah,
    SUM(CASE WHEN w.id IS NULL    THEN 1 ELSE 0 END) AS belum
FROM peserta p
LEFT JOIN wawancara w ON w.id = (
    SELECT id FROM wawancara WHERE peserta_id = p.id ORDER BY created_at DESC LIMIT 1
)
WHERE p.status_seleksi = 'Diterima'")->fetch_assoc();

// Distinct kelompok values for filter
$kelompokList = [];
$r = $conn->query("SELECT DISTINCT kelompok FROM peserta WHERE status_seleksi='Diterima' AND kelompok IS NOT NULL AND kelompok<>'' ORDER BY kelompok");
while ($row = $r->fetch_row()) $kelompokList[] = $row[0];

$predikatInfo = [
    'A' => ['color' => '#166534', 'bg' => '#d1fae5'],
    'B' => ['color' => '#1e40af', 'bg' => '#dbeafe'],
    'C' => ['color' => '#92400e', 'bg' => '#fef9c3'],
    'D' => ['color' => '#9a3412', 'bg' => '#ffedd5'],
    'E' => ['color' => '#991b1b', 'bg' => '#fee2e2'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wawancara — SEMANIS 2026</title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        .stat-icon { width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; }
        .table-hover tbody tr:hover { background:#fff8f0; }
        .kec-header {
            background: #f79039; color: #fff;
            padding: 12px 18px; cursor: pointer;
            user-select: none;
        }
        .kec-header:hover { background: #e06820; }
        .kec-badge-done { background: rgba(255,255,255,.2); }
        .kec-badge-pending { background: rgba(255,255,255,.35); }
        .chevron-icon { transition: transform .2s; }
        .collapsed .chevron-icon { transform: rotate(-90deg); }
        .kec-progress { height: 5px; background: rgba(255,255,255,.3); border-radius: 3px; }
        .kec-progress-bar { height: 5px; background: #fff; border-radius: 3px; transition: width .3s; }
    </style>
</head>
<body>
<div class="d-flex">
    <?php include '_sidebar.php'; ?>

    <div class="main-content w-100">
        <header class="topbar">
            <button class="topbar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div>
                <div class="topbar-title"><i class="bi bi-mic-fill me-2" style="color:#f79039"></i>Daftar Peserta Wawancara</div>
                <div class="topbar-sub">Sensus Ekonomi 2026 — BPS Kab. Toli-Toli</div>
            </div>
            <div class="topbar-right d-flex align-items-center gap-2">
                <a href="peserta_tambah.php" class="btn btn-sm text-white fw-semibold" style="background:#f79039;border-radius:8px">
                    <i class="bi bi-person-plus-fill me-1"></i>Tambah Peserta
                </a>
                <small class="text-muted d-none d-md-flex align-items-center gap-1">
                    <i class="bi bi-clock"></i><?= date('d M Y') ?>
                </small>
            </div>
        </header>

        <div class="page-body">

            <!-- Stat Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people-fill"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= $stats['total'] ?></div>
                                <div class="text-muted small">Total Peserta</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check2-circle"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= $stats['sudah'] ?></div>
                                <div class="text-muted small">Sudah Diwawancara</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-hourglass-split"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= $stats['belum'] ?></div>
                                <div class="text-muted small">Belum Diwawancara</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background:rgba(247,144,57,.12);color:#f79039"><i class="bi bi-geo-alt-fill"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= count($grouped) ?></div>
                                <div class="text-muted small">Kecamatan</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search & Filter -->
            <div class="card stat-card mb-3">
                <div class="card-body py-3">
                    <form method="GET" class="row g-2 align-items-center">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" name="search" class="form-control border-start-0"
                                       placeholder="Cari nama, telepon, atau email..."
                                       value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="kec" class="form-select">
                                <option value="all" <?= $kecFilter === 'all' ? 'selected' : '' ?>>— Semua Kecamatan —</option>
                                <?php foreach ($kecMap as $kode => $nama): ?>
                                <option value="<?= $kode ?>" <?= $kecFilter === $kode ? 'selected' : '' ?>>
                                    <?= $kode ?> — <?= htmlspecialchars($nama) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="kelompok" class="form-select">
                                <option value="all" <?= $kelompokFilter === 'all' ? 'selected' : '' ?>>— Semua Kelompok —</option>
                                <?php foreach ($kelompokList as $kg): ?>
                                <option value="<?= htmlspecialchars($kg) ?>" <?= $kelompokFilter === $kg ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($kg) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn text-white fw-semibold px-3" style="background:#f79039">
                                <i class="bi bi-search me-1"></i>Cari
                            </button>
                        </div>
                        <?php if ($search !== '' || $kecFilter !== 'all' || $kelompokFilter !== 'all'): ?>
                        <div class="col-auto">
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="bi bi-x me-1"></i>Reset
                            </a>
                        </div>
                        <?php endif; ?>
                    </form>
                    <?php if ($search !== '' || $kecFilter !== 'all' || $kelompokFilter !== 'all'): ?>
                    <div class="mt-2 text-muted small d-flex flex-wrap gap-2 align-items-center">
                        <i class="bi bi-funnel me-1"></i>
                        Menampilkan <strong><?= count($allPeserta) ?></strong> peserta
                        <?php if ($kelompokFilter !== 'all'): ?>
                            &nbsp;kelompok <span class="badge px-2" style="background:#f79039;color:#fff;font-size:.77rem"><?= htmlspecialchars($kelompokFilter) ?></span>
                        <?php endif; ?>
                        <?php if ($kecFilter !== 'all' && isset($kecMap[$kecFilter])): ?>
                            &nbsp;di <span class="badge px-2" style="background:#f79039;color:#fff;font-size:.77rem"><?= $kecFilter ?> — <?= htmlspecialchars($kecMap[$kecFilter]) ?></span>
                        <?php endif; ?>
                        <?php if ($search !== ''): ?>
                            &nbsp;kata kunci "<strong><?= htmlspecialchars($search) ?></strong>"
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Kelompok Banner -->
            <?php if ($kelompokFilter !== 'all'): ?>
            <div class="d-flex align-items-center gap-3 p-3 mb-3 rounded"
                 style="background:#fff8f0;border:1px solid #f79039">
                <i class="bi bi-tag-fill" style="color:#f79039;font-size:1.2rem"></i>
                <div>
                    <div class="fw-bold" style="color:#f79039">Kelompok: <?= htmlspecialchars($kelompokFilter) ?></div>
                    <div class="text-muted small"><?= count($allPeserta) ?> peserta dalam kelompok ini</div>
                </div>
                <a href="index.php" class="btn btn-sm btn-outline-secondary ms-auto">
                    <i class="bi bi-x me-1"></i>Lihat Semua
                </a>
            </div>
            <?php endif; ?>

            <!-- Expand/Collapse All -->
            <?php if (!empty($grouped)): ?>
            <div class="d-flex justify-content-end mb-2 gap-2">
                <button class="btn btn-sm btn-outline-secondary" onclick="toggleAll(true)">
                    <i class="bi bi-arrows-expand me-1"></i>Buka Semua
                </button>
                <button class="btn btn-sm btn-outline-secondary" onclick="toggleAll(false)">
                    <i class="bi bi-arrows-collapse me-1"></i>Tutup Semua
                </button>
            </div>
            <?php endif; ?>

            <!-- Accordion per Kecamatan -->
            <?php if (empty($grouped)): ?>
                <div class="card stat-card">
                    <div class="card-body text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                        <?= $search ? 'Tidak ada peserta yang cocok dengan pencarian.' : 'Belum ada data peserta.' ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php foreach ($grouped as $kecKode => $pesertaList):
                $kecNama  = $kecMap[$kecKode] ?? $kecKode;
                $totalKec = count($pesertaList);
                $sudahKec = count(array_filter($pesertaList, fn($p) => $p['wawancara_id']));
                $belumKec = $totalKec - $sudahKec;
                $pctKec   = $totalKec > 0 ? round($sudahKec / $totalKec * 100) : 0;
                $isOpen   = ($belumKec > 0 || $search !== '');
                $accId    = 'kec_' . $kecKode;
            ?>
            <div class="card stat-card mb-3" style="border:none;overflow:hidden;">

                <!-- Header kecamatan -->
                <div class="kec-header d-flex align-items-center gap-3"
                     data-bs-toggle="collapse" data-bs-target="#<?= $accId ?>"
                     aria-expanded="<?= $isOpen ? 'true' : 'false' ?>"
                     <?= $isOpen ? '' : 'class="kec-header collapsed"' ?>>
                    <div class="flex-grow-1">
                        <div class="fw-bold" style="font-size:.95rem;">
                            <i class="bi bi-geo-alt-fill me-2 opacity-75"></i>
                            <span style="opacity:.75;font-size:.8rem"><?= $kecKode ?></span>
                            &nbsp;<?= htmlspecialchars($kecNama) ?>
                        </div>
                        <div style="font-size:.73rem; opacity:.85; margin-top:2px;">
                            <?= $totalKec ?> peserta
                            &nbsp;·&nbsp;
                            <span style="color:#bbf7d0"><?= $sudahKec ?> selesai</span>
                            &nbsp;·&nbsp;
                            <span style="color:<?= $belumKec > 0 ? '#fde68a' : '#bbf7d0' ?>"><?= $belumKec ?> belum</span>
                        </div>
                        <div class="kec-progress mt-2" style="width:140px">
                            <div class="kec-progress-bar" style="width:<?= $pctKec ?>%"></div>
                        </div>
                    </div>
                    <div class="text-end">
                        <div style="font-size:1.3rem; font-weight:800; line-height:1"><?= $pctKec ?>%</div>
                        <div style="font-size:.68rem; opacity:.8">selesai</div>
                    </div>
                    <?php if ($belumKec === 0): ?>
                        <i class="bi bi-check-circle-fill" style="font-size:1.3rem; opacity:.9"></i>
                    <?php else: ?>
                        <span class="badge rounded-pill px-2 py-1 kec-badge-pending" style="font-size:.72rem">
                            <?= $belumKec ?> pending
                        </span>
                    <?php endif; ?>
                    <i class="bi bi-chevron-down chevron-icon" style="font-size:.85rem; opacity:.8"></i>
                </div>

                <!-- Tabel peserta -->
                <div class="collapse <?= $isOpen ? 'show' : '' ?>" id="<?= $accId ?>">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size:.84rem;">
                            <thead style="background:#fff3e6;">
                                <tr>
                                    <th class="ps-3 text-muted fw-semibold" style="width:36px; font-size:.74rem">#</th>
                                    <th class="text-muted fw-semibold" style="font-size:.74rem">Nama</th>
                                    <th class="text-muted fw-semibold" style="font-size:.74rem">Posisi</th>
                                    <th class="text-muted fw-semibold" style="font-size:.74rem">Pendidikan</th>
                                    <th class="text-muted fw-semibold" style="font-size:.74rem">No. Telp</th>
                                    <th class="text-center text-muted fw-semibold" style="font-size:.74rem">Status</th>
                                    <th class="text-center text-muted fw-semibold" style="font-size:.74rem">Nilai</th>
                                    <th class="text-center text-muted fw-semibold" style="font-size:.74rem">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($pesertaList as $i => $p):
                                $pi = $predikatInfo[$p['predikat']] ?? null;
                            ?>
                                <tr>
                                    <td class="ps-3 text-muted"><?= $i + 1 ?></td>
                                    <td>
                                        <div class="fw-semibold">
                                            <?= htmlspecialchars($p['nama']) ?>
                                            <?php if (!empty($p['kelompok'])): ?>
                                            <span class="badge rounded-pill ms-1 px-2"
                                                  style="background:#fff8f0;color:#f79039;border:1px solid #f79039;font-size:.67rem;font-weight:700">
                                                <?= htmlspecialchars($p['kelompok']) ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted"><?= htmlspecialchars($p['email'] ?? '') ?></small>
                                    </td>
                                    <td><small class="text-muted"><?= htmlspecialchars($p['posisi'] ?: ($p['posisi_daftar'] ?? '')) ?></small></td>
                                    <td><small><?= htmlspecialchars($p['pendidikan'] ?? '') ?></small></td>
                                    <td><small><?= htmlspecialchars($p['no_telp'] ?? '') ?></small></td>
                                    <td class="text-center">
                                        <?php if ($p['wawancara_id']): ?>
                                            <?php if ($pi): ?>
                                                <span class="badge rounded-pill px-2 fw-bold"
                                                      style="background:<?= $pi['bg'] ?>;color:<?= $pi['color'] ?>;font-size:.78rem">
                                                    <?= htmlspecialchars($p['predikat']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-info rounded-pill px-2">Selesai</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge badge-belum rounded-pill px-2">Belum</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center fw-bold">
                                        <?= ($p['wawancara_id'] && $p['nilai'] !== null)
                                            ? $p['nilai']
                                            : '<span class="text-muted">—</span>' ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($p['wawancara_id']): ?>
                                            <div class="d-flex gap-1 justify-content-center">
                                                <a href="form_wawancara.php?id=<?= $p['id'] ?>"
                                                   class="btn btn-sm btn-outline-primary" title="Edit Wawancara">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </a>
                                                <?php if (isSuperAdmin()): ?>
                                                <button class="btn btn-sm btn-outline-danger"
                                                        onclick='openReset(<?= $p['wawancara_id'] ?>, <?= htmlspecialchars(json_encode($p['nama']), ENT_QUOTES) ?>)'
                                                        title="Reset Data Wawancara">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <a href="form_wawancara.php?id=<?= $p['id'] ?>"
                                               class="btn btn-sm text-white fw-semibold" style="background:#f79039">
                                                <i class="bi bi-mic-fill me-1"></i>Wawancara
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

        </div><!-- /page-body -->
    </div><!-- /main-content -->
</div>

<!-- ── Modal Reset Wawancara ── -->
<div class="modal fade" id="modalReset" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
        <div class="modal-content" style="border-radius:14px;overflow:hidden;">
            <div class="modal-header border-0 py-3" style="background:#dc2626;">
                <h6 class="modal-title text-white fw-bold mb-0">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Reset Data Wawancara
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <p class="mb-1">Data wawancara peserta:</p>
                <p class="fw-bold mb-3" id="resetNama">—</p>
                <p class="mb-0 text-muted" style="font-size:.875rem">
                    akan <strong>dihapus</strong> sehingga peserta dapat diwawancara ulang.
                    Tindakan ini <strong>tidak dapat dibatalkan</strong>.
                </p>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <form method="POST" action="reset_wawancara.php" class="d-inline">
                    <input type="hidden" name="wawancara_id" id="resetWid">
                    <input type="hidden" name="redirect" value="index.php">
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Ya, Reset
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
function openReset(wid, nama) {
    document.getElementById('resetWid').value = wid;
    document.getElementById('resetNama').textContent = nama;
    new bootstrap.Modal(document.getElementById('modalReset')).show();
}

function toggleAll(open) {
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(btn => {
        const target = document.querySelector(btn.dataset.bsTarget);
        if (!target) return;
        const bsCol = bootstrap.Collapse.getOrCreateInstance(target, { toggle: false });
        open ? bsCol.show() : bsCol.hide();
    });
}

// Rotate chevron icon on collapse toggle
document.addEventListener('show.bs.collapse', e => {
    const btn = document.querySelector(`[data-bs-target="#${e.target.id}"]`);
    if (btn) btn.querySelector('.chevron-icon')?.style.setProperty('transform', 'rotate(0deg)');
});
document.addEventListener('hide.bs.collapse', e => {
    const btn = document.querySelector(`[data-bs-target="#${e.target.id}"]`);
    if (btn) btn.querySelector('.chevron-icon')?.style.setProperty('transform', 'rotate(-90deg)');
});
</script>
</body>
</html>
