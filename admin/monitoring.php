<?php
require_once 'config.php';
requireLogin();

$kecNama = [
    '010' => 'Dampal Selatan', '020' => 'Dampal Utara',
    '030' => 'Dondo',          '031' => 'Ogodeide',
    '032' => 'Basidondo',      '040' => 'Baolan',
    '041' => 'Lampasio',       '050' => 'Galang',
    '060' => 'Tolitoli Utara', '061' => 'Dako Pemean',
];

$kecFilter = trim($_GET['kec'] ?? 'all');
if ($kecFilter !== 'all' && !isset($kecNama[$kecFilter])) $kecFilter = 'all';
$kecWhere = $kecFilter !== 'all'
    ? " AND p.alamat_kec = '" . $conn->real_escape_string($kecFilter) . "'"
    : '';

// Stats utama
$stats = $conn->query("SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN w.id IS NOT NULL THEN 1 ELSE 0 END) AS sudah,
    SUM(CASE WHEN w.id IS NULL    THEN 1 ELSE 0 END) AS belum,
    SUM(CASE WHEN w.predikat IN ('A','B') THEN 1 ELSE 0 END) AS direkomendasikan,
    SUM(CASE WHEN w.predikat IN ('D','E') THEN 1 ELSE 0 END) AS tidak_direkomendasikan,
    ROUND(AVG(w.nilai), 1) AS rata_nilai
FROM peserta p
LEFT JOIN wawancara w ON w.id = (
    SELECT id FROM wawancara WHERE peserta_id = p.id ORDER BY created_at DESC LIMIT 1
)
WHERE p.status_seleksi = 'Diterima'$kecWhere")->fetch_assoc();

// Progress per kecamatan (urut sesuai kode)
$kecData = $conn->query("SELECT
    p.alamat_kec AS kec,
    COUNT(*) AS total,
    SUM(CASE WHEN w.id IS NOT NULL THEN 1 ELSE 0 END) AS sudah,
    SUM(CASE WHEN w.predikat IN ('A','B') THEN 1 ELSE 0 END) AS direkomendasikan
FROM peserta p
LEFT JOIN wawancara w ON w.id = (
    SELECT id FROM wawancara WHERE peserta_id = p.id ORDER BY created_at DESC LIMIT 1
)
WHERE p.status_seleksi = 'Diterima'$kecWhere
GROUP BY p.alamat_kec
ORDER BY p.alamat_kec ASC")->fetch_all(MYSQLI_ASSOC);

// Progress per petugas (superadmin only)
$petugasData = [];
if (isSuperAdmin()) {
    $petugasData = $conn->query("SELECT
        a.nama AS petugas,
        COUNT(w.id) AS jumlah,
        SUM(CASE WHEN w.predikat IN ('A','B') THEN 1 ELSE 0 END) AS direkomendasikan,
        ROUND(AVG(w.nilai),1) AS rata_nilai
    FROM wawancara w
    JOIN admin_seleksi a ON a.id = w.admin_id
    JOIN peserta p ON p.id = w.peserta_id
    WHERE 1=1$kecWhere
    GROUP BY w.admin_id
    ORDER BY jumlah DESC")->fetch_all(MYSQLI_ASSOC);
}

// Wawancara terbaru
$terbaru = $conn->query("SELECT p.nama, w.nilai, w.predikat, w.created_at, a.nama AS petugas
    FROM wawancara w
    JOIN peserta p ON p.id = w.peserta_id
    JOIN admin_seleksi a ON a.id = w.admin_id
    WHERE 1=1$kecWhere
    ORDER BY w.created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

$pct = $stats['total'] > 0 ? round($stats['sudah'] / $stats['total'] * 100) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring — SEMANIS 2026</title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        .stat-icon { width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; }
        .progress  { height:8px; border-radius:6px; background:#e9ecef; }
        .kec-row:hover { background:#fff8f0; }
        .section-head { font-weight:700; font-size:.7rem; text-transform:uppercase; letter-spacing:.08em; color:#94a3b8; margin-bottom:14px; }
    </style>
</head>
<body>
<div class="d-flex">
    <?php include '_sidebar.php'; ?>

    <div class="main-content w-100">
        <!-- Topbar -->
        <header class="topbar">
            <button class="topbar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div>
                <div class="topbar-title"><i class="bi bi-bar-chart-line-fill me-2" style="color:#f79039"></i>Monitoring Wawancara</div>
                <div class="topbar-sub">Sensus Ekonomi 2026 — BPS Kab. Toli-Toli</div>
            </div>
            <div class="topbar-right">
                <form method="GET" class="d-flex align-items-center gap-2">
                    <select name="kec" class="form-select form-select-sm" style="min-width:190px;font-size:.82rem;" onchange="this.form.submit()">
                        <option value="all" <?= $kecFilter === 'all' ? 'selected' : '' ?>>Semua Kecamatan</option>
                        <?php foreach ($kecNama as $kode => $nama): ?>
                            <option value="<?= $kode ?>" <?= $kecFilter === $kode ? 'selected' : '' ?>>
                                <?= $kode ?> — <?= htmlspecialchars($nama) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </header>

        <div class="page-body">

            <!-- Stat Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-2">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people-fill"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= $stats['total'] ?></div>
                                <div class="text-muted small">Total</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-2">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check2-circle"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= $stats['sudah'] ?></div>
                                <div class="text-muted small">Sudah</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-2">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-hourglass-split"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= $stats['belum'] ?></div>
                                <div class="text-muted small">Belum</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-2">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-award-fill"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= $stats['direkomendasikan'] ?></div>
                                <div class="text-muted small">Direkomendasikan</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-2">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-x-circle-fill"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= $stats['tidak_direkomendasikan'] ?></div>
                                <div class="text-muted small">Tidak Direkomendasikan</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-2">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background:rgba(247,144,57,.12);color:#f79039"><i class="bi bi-star-fill"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= $stats['rata_nilai'] ?? 0 ?></div>
                                <div class="text-muted small">Rata Nilai</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Bar Global -->
            <div class="card stat-card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-semibold">
                        Progress Wawancara
                        <?php if ($kecFilter !== 'all'): ?>
                            — <span style="color:#f79039"><?= htmlspecialchars($kecNama[$kecFilter]) ?></span>
                        <?php else: ?>
                            Keseluruhan
                        <?php endif; ?>
                    </span>
                    <span class="fw-bold fs-5" style="color:#f79039"><?= $pct ?>%</span>
                </div>
                <div class="progress">
                    <div class="progress-bar progress-accent" style="width:<?= $pct ?>%; border-radius:6px;"></div>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <small class="text-muted"><?= $stats['sudah'] ?> dari <?= $stats['total'] ?> peserta sudah diwawancara</small>
                    <small class="text-muted"><?= $stats['belum'] ?> peserta tersisa</small>
                </div>
            </div>

            <div class="row g-4">
                <!-- Progress per Kecamatan -->
                <div class="col-lg-5">
                    <div class="card stat-card p-4 h-100">
                        <div class="section-head"><i class="bi bi-geo-alt-fill me-1"></i>Progress per Kecamatan</div>
                        <div class="d-flex flex-column gap-3">
                        <?php foreach ($kecData as $kec): ?>
                            <?php
                            $nama = $kecNama[$kec['kec']] ?? 'Kec. ' . $kec['kec'];
                            $p    = $kec['total'] > 0 ? round($kec['sudah'] / $kec['total'] * 100) : 0;
                            ?>
                            <div class="kec-row rounded p-2">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="small fw-semibold">
                                        <span class="text-muted" style="font-size:.7rem"><?= htmlspecialchars($kec['kec']) ?></span>
                                        &nbsp;<?= htmlspecialchars($nama) ?>
                                    </span>
                                    <span class="small text-muted">
                                        <?= $kec['sudah'] ?>/<?= $kec['total'] ?>
                                        &nbsp;<span class="badge badge-lulus rounded-pill"><?= $kec['direkomendasikan'] ?> rekomen</span>
                                    </span>
                                </div>
                                <div class="progress" style="height:6px">
                                    <div class="progress-bar progress-accent" style="width:<?= $p ?>%; border-radius:6px;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($kecData)): ?>
                            <p class="text-muted text-center py-3 mb-0">Belum ada data.</p>
                        <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Kolom kanan -->
                <div class="col-lg-7">
                    <!-- Progress per Petugas (superadmin only) -->
                    <?php if (isSuperAdmin() && !empty($petugasData)): ?>
                    <div class="card stat-card p-4 mb-4">
                        <div class="section-head"><i class="bi bi-person-badge-fill me-1"></i>Input per Petugas</div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead style="background:#f79039;color:#fff;">
                                    <tr>
                                        <th>Petugas</th>
                                        <th class="text-center">Jumlah</th>
                                        <th class="text-center">Rekomen.</th>
                                        <th class="text-center">Rata Nilai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($petugasData as $p): ?>
                                    <tr>
                                        <td class="fw-semibold small"><?= htmlspecialchars($p['petugas']) ?></td>
                                        <td class="text-center fw-bold"><?= $p['jumlah'] ?></td>
                                        <td class="text-center"><span class="badge badge-lulus rounded-pill"><?= $p['direkomendasikan'] ?></span></td>
                                        <td class="text-center text-muted"><?= $p['rata_nilai'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Wawancara Terbaru -->
                    <div class="card stat-card p-4">
                        <div class="section-head"><i class="bi bi-clock-history me-1"></i>Wawancara Terbaru</div>
                        <div class="d-flex flex-column gap-1">
                        <?php foreach ($terbaru as $t): ?>
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <div>
                                    <div class="fw-semibold small"><?= htmlspecialchars($t['nama']) ?></div>
                                    <small class="text-muted">
                                        oleh <?= htmlspecialchars($t['petugas']) ?> &middot; <?= date('d M, H:i', strtotime($t['created_at'])) ?>
                                    </small>
                                </div>
                                <?php
                                $predColors = ['A'=>['#166534','#d1fae5'],'B'=>['#1e40af','#dbeafe'],
                                               'C'=>['#92400e','#fef9c3'],'D'=>['#9a3412','#ffedd5'],'E'=>['#991b1b','#fee2e2']];
                                $pc = $predColors[$t['predikat']] ?? null;
                                ?>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-bold"><?= $t['nilai'] ?></span>
                                    <?php if ($pc): ?>
                                    <span class="badge rounded-pill fw-bold"
                                          style="background:<?= $pc[1] ?>;color:<?= $pc[0] ?>">
                                        <?= $t['predikat'] ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($terbaru)): ?>
                            <p class="text-muted text-center py-3 mb-0">Belum ada data wawancara.</p>
                        <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /page-body -->
    </div><!-- /main-content -->
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
