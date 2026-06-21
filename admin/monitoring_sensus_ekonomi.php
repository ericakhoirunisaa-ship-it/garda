<?php
require_once 'config.php';
requireLogin();

$conn->query("CREATE TABLE IF NOT EXISTS sensus_ekonomi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    sls_code VARCHAR(20) NOT NULL,
    open_count INT DEFAULT 0,
    draft INT DEFAULT 0,
    submitted_by_pencacah INT DEFAULT 0,
    submitted_respondent INT DEFAULT 0,
    rejected INT DEFAULT 0,
    approved INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_email_sls (email, sls_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$kecNama = [
    '010' => 'Dampal Selatan', '020' => 'Dampal Utara',
    '030' => 'Dondo',          '031' => 'Ogodeide',
    '032' => 'Basidondo',      '040' => 'Baolan',
    '041' => 'Lampasio',       '050' => 'Galang',
    '060' => 'Tolitoli Utara', '061' => 'Dako Pemean',
];

$kecFilter = trim($_GET['kec'] ?? 'all');
if ($kecFilter !== 'all' && !isset($kecNama[$kecFilter])) $kecFilter = 'all';

$kecCond  = $kecFilter !== 'all'
    ? "SUBSTRING(sls_code, 5, 3) = '" . $conn->real_escape_string($kecFilter) . "'"
    : '';
$kecWhere = $kecCond ? "WHERE $kecCond" : '';

$stats = $conn->query("SELECT
    COALESCE(SUM(open_count), 0)                                   AS total_open,
    COALESCE(SUM(draft), 0)                                        AS total_draft,
    COALESCE(SUM(submitted_by_pencacah + submitted_respondent), 0) AS total_submitted,
    COALESCE(SUM(rejected), 0)                                     AS total_rejected,
    COALESCE(SUM(approved), 0)                                     AS total_approved,
    COUNT(DISTINCT email)                                          AS total_petugas,
    COUNT(DISTINCT sls_code)                                       AS total_sls
FROM sensus_ekonomi $kecWhere")->fetch_assoc();

$globalDenominator = (int)$stats['total_open'] + (int)$stats['total_draft']
                   + (int)$stats['total_submitted'] + (int)$stats['total_rejected']
                   + (int)$stats['total_approved'];
$globalNumerator   = (int)$stats['total_submitted'] + (int)$stats['total_rejected']
                   + (int)$stats['total_approved'];
$globalPct = $globalDenominator > 0
    ? number_format($globalNumerator / $globalDenominator * 100, 2)
    : '0.00';

$perPetugas = $conn->query("SELECT
    email,
    COALESCE(SUM(open_count), 0)                                   AS total_open,
    COALESCE(SUM(draft), 0)                                        AS total_draft,
    COALESCE(SUM(submitted_by_pencacah), 0)                        AS submitted_pencacah,
    COALESCE(SUM(submitted_respondent), 0)                         AS submitted_resp,
    COALESCE(SUM(submitted_by_pencacah + submitted_respondent), 0) AS total_submitted,
    COALESCE(SUM(rejected), 0)                                     AS total_rejected,
    COALESCE(SUM(approved), 0)                                     AS total_approved,
    COUNT(DISTINCT sls_code)                                       AS total_sls
FROM sensus_ekonomi $kecWhere
GROUP BY email
ORDER BY total_submitted DESC, total_open DESC")->fetch_all(MYSQLI_ASSOC);

$perKec = $conn->query("SELECT
    SUBSTRING(sls_code, 5, 3) AS kec_code,
    COALESCE(SUM(open_count), 0)                                   AS total_open,
    COALESCE(SUM(draft), 0)                                        AS total_draft,
    COALESCE(SUM(submitted_by_pencacah + submitted_respondent), 0) AS total_submitted,
    COALESCE(SUM(rejected), 0)                                     AS total_rejected,
    COALESCE(SUM(approved), 0)                                     AS total_approved,
    COUNT(DISTINCT email)                                          AS total_petugas,
    COUNT(DISTINCT sls_code)                                       AS total_sls
FROM sensus_ekonomi
GROUP BY SUBSTRING(sls_code, 5, 3)
ORDER BY kec_code")->fetch_all(MYSQLI_ASSOC);

$chartLabels    = json_encode(array_map(fn($p) => explode('@', $p['email'])[0], $perPetugas));
$chartOpen      = json_encode(array_map('intval', array_column($perPetugas, 'total_open')));
$chartDraft     = json_encode(array_map('intval', array_column($perPetugas, 'total_draft')));
$chartSubmitted = json_encode(array_map('intval', array_column($perPetugas, 'total_submitted')));
$chartRejected  = json_encode(array_map('intval', array_column($perPetugas, 'total_rejected')));
$doughnutData   = json_encode([
    (int)$stats['total_open'],
    (int)$stats['total_draft'],
    (int)$stats['total_submitted'],
    (int)$stats['total_rejected'],
]);
$barWidth = max(600, count($perPetugas) * 60);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Sensus Ekonomi — SEMANIS 2026</title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        .stat-icon { width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; }
        .section-head { font-weight:700; font-size:.7rem; text-transform:uppercase; letter-spacing:.08em; color:#94a3b8; margin-bottom:14px; }
        .table-hover tbody tr:hover { background:#fff8f0; }
        .chart-scroll { overflow-x:auto; }
        .nav-tabs .nav-link { color:#64748b; font-size:.83rem; font-weight:600; }
        .nav-tabs .nav-link.active { color:#f79039; border-bottom-color:#f79039; }
        .nav-tabs { border-bottom:2px solid #e9ecef; }
        .badge-open   { background:#dbeafe; color:#1d4ed8; }
        .badge-draft  { background:#fef9c3; color:#92400e; }
        .badge-sub    { background:#dcfce7; color:#166534; }
        .badge-rej    { background:#fee2e2; color:#991b1b; }
    </style>
</head>
<body>
<div class="d-flex">
    <?php include '_sidebar.php'; ?>

    <div class="main-content w-100">
        <header class="topbar">
            <button class="topbar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div>
                <div class="topbar-title">
                    <i class="bi bi-graph-up-arrow me-2" style="color:#f79039"></i>Monitoring Sensus Ekonomi
                </div>
                <div class="topbar-sub">Progress Pencacahan SE2026 — BPS Kab. Toli-Toli</div>
            </div>
            <div class="topbar-right d-flex align-items-center gap-2">
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
                <?php if (isSuperAdmin()): ?>
                <a href="data_sensus_ekonomi.php" class="btn btn-sm text-white" style="background:#f79039; white-space:nowrap;">
                    <i class="bi bi-database-fill-gear me-1"></i>Kelola Data
                </a>
                <?php endif; ?>
            </div>
        </header>

        <div class="page-body">

            <!-- ── Stat Cards ─────────────────────────────────── -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-sm-4 col-xl-2">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-folder2-open"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= number_format((int)$stats['total_open']) ?></div>
                                <div class="text-muted small">Open</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-4 col-xl-2">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-pencil-square"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= number_format((int)$stats['total_draft']) ?></div>
                                <div class="text-muted small">Draft</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-4 col-xl-2">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check2-all"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= number_format((int)$stats['total_submitted']) ?></div>
                                <div class="text-muted small">Submitted</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-4 col-xl-2">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-x-circle-fill"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= number_format((int)$stats['total_rejected']) ?></div>
                                <div class="text-muted small">Rejected</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-4 col-xl-2">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background:rgba(247,144,57,.12);color:#f79039"><i class="bi bi-person-badge-fill"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= (int)$stats['total_petugas'] ?></div>
                                <div class="text-muted small">Petugas</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-4 col-xl-2">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background:rgba(99,102,241,.12);color:#6366f1"><i class="bi bi-geo-alt-fill"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= (int)$stats['total_sls'] ?></div>
                                <div class="text-muted small">SLS</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Progress Bar Global ───────────────────────── -->
            <div class="card stat-card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-semibold">
                        Progress Pencacahan
                        <?php if ($kecFilter !== 'all'): ?>
                            — <span style="color:#f79039"><?= htmlspecialchars($kecNama[$kecFilter]) ?></span>
                        <?php else: ?>
                            Keseluruhan
                        <?php endif; ?>
                    </span>
                    <span class="fw-bold fs-5" style="color:#f79039"><?= $globalPct ?>%</span>
                </div>
                <div class="progress" style="height:8px; border-radius:6px; background:#e9ecef;">
                    <div class="progress-bar progress-accent" style="width:<?= $globalPct ?>%; border-radius:6px;"></div>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <small class="text-muted"><?= number_format($globalNumerator) ?> dari <?= number_format($globalDenominator) ?> dokumen sudah diproses (submit + reject + approve)</small>
                    <small class="text-muted"><?= number_format((int)$stats['total_open'] + (int)$stats['total_draft']) ?> dokumen tersisa</small>
                </div>
            </div>

            <!-- ── Charts ─────────────────────────────────────── -->
            <div class="row g-4 mb-4">
                <!-- Doughnut -->
                <div class="col-lg-4">
                    <div class="card stat-card p-4 h-100">
                        <div class="section-head"><i class="bi bi-pie-chart-fill me-1"></i>Distribusi Status
                            <?php if ($kecFilter !== 'all'): ?>
                            — <span style="color:#f79039"><?= htmlspecialchars($kecNama[$kecFilter]) ?></span>
                            <?php endif; ?>
                        </div>
                        <div style="max-width:300px; margin:0 auto;">
                            <canvas id="doughnutChart"></canvas>
                        </div>
                        <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                            <span class="badge badge-open px-3 py-2">Open: <?= number_format((int)$stats['total_open']) ?></span>
                            <span class="badge badge-draft px-3 py-2">Draft: <?= number_format((int)$stats['total_draft']) ?></span>
                            <span class="badge badge-sub px-3 py-2">Submitted: <?= number_format((int)$stats['total_submitted']) ?></span>
                            <span class="badge badge-rej px-3 py-2">Rejected: <?= number_format((int)$stats['total_rejected']) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Bar per petugas -->
                <div class="col-lg-8">
                    <div class="card stat-card p-4 h-100">
                        <div class="section-head"><i class="bi bi-bar-chart-fill me-1"></i>Progress per Petugas</div>
                        <div class="chart-scroll">
                            <canvas id="barChart" style="min-width:<?= $barWidth ?>px; height:260px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Tabs: Petugas | Kecamatan ──────────────────── -->
            <div class="card stat-card mb-0">
                <div class="card-header bg-white border-bottom-0 pt-3 px-4">
                    <ul class="nav nav-tabs border-0" id="mainTabs">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabPetugas">
                                <i class="bi bi-person-lines-fill me-1"></i>Per Petugas
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabKecamatan">
                                <i class="bi bi-map-fill me-1"></i>Per Kecamatan
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabSLS">
                                <i class="bi bi-list-ul me-1"></i>Per SLS
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="tab-content">

                    <!-- Tab Petugas -->
                    <div class="tab-pane fade show active" id="tabPetugas">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead style="background:#f79039; color:#fff;">
                                    <tr>
                                        <th class="ps-4">#</th>
                                        <th>Email Petugas</th>
                                        <th class="text-center">SLS</th>
                                        <th class="text-center">Open</th>
                                        <th class="text-center">Draft</th>
                                        <th class="text-center">Submitted<br><small style="font-weight:400;font-size:.72rem;">Pencacah</small></th>
                                        <th class="text-center">Submitted<br><small style="font-weight:400;font-size:.72rem;">Respondent</small></th>
                                        <th class="text-center">Rejected</th>
                                        <th class="text-center">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($perPetugas)): ?>
                                    <tr><td colspan="9" class="text-center text-muted py-4">Belum ada data. <a href="data_sensus_ekonomi.php">Import data?</a></td></tr>
                                <?php else: ?>
                                <?php foreach ($perPetugas as $i => $p): ?>
                                    <?php
                                    $total = (int)$p['total_open'] + (int)$p['total_draft']
                                           + (int)$p['total_submitted'] + (int)$p['total_rejected'];
                                    ?>
                                    <tr>
                                        <td class="ps-4 text-muted small"><?= $i + 1 ?></td>
                                        <td class="fw-semibold small"><?= htmlspecialchars($p['email']) ?></td>
                                        <td class="text-center"><span class="badge" style="background:#e0e7ff;color:#3730a3"><?= $p['total_sls'] ?></span></td>
                                        <td class="text-center"><span class="badge badge-open fw-semibold"><?= number_format((int)$p['total_open']) ?></span></td>
                                        <td class="text-center"><span class="badge badge-draft fw-semibold"><?= number_format((int)$p['total_draft']) ?></span></td>
                                        <td class="text-center"><span class="badge badge-sub fw-semibold"><?= number_format((int)$p['submitted_pencacah']) ?></span></td>
                                        <td class="text-center"><span class="badge badge-sub fw-semibold"><?= number_format((int)$p['submitted_resp']) ?></span></td>
                                        <td class="text-center"><span class="badge badge-rej fw-semibold"><?= number_format((int)$p['total_rejected']) ?></span></td>
                                        <td class="text-center fw-bold text-muted"><?= number_format($total) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab Kecamatan -->
                    <div class="tab-pane fade" id="tabKecamatan">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead style="background:#f79039; color:#fff;">
                                    <tr>
                                        <th class="ps-4">Kode</th>
                                        <th>Kecamatan</th>
                                        <th class="text-center">Petugas</th>
                                        <th class="text-center">SLS</th>
                                        <th class="text-center">Open</th>
                                        <th class="text-center">Draft</th>
                                        <th class="text-center">Submitted</th>
                                        <th class="text-center">Rejected</th>
                                        <th class="text-center">Progress</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($perKec)): ?>
                                    <tr><td colspan="9" class="text-center text-muted py-4">Belum ada data.</td></tr>
                                <?php else: ?>
                                <?php foreach ($perKec as $kec): ?>
                                    <?php
                                    $kDenominator = (int)$kec['total_open'] + (int)$kec['total_draft']
                                                  + (int)$kec['total_submitted'] + (int)$kec['total_rejected']
                                                  + (int)$kec['total_approved'];
                                    $kNumerator   = (int)$kec['total_submitted'] + (int)$kec['total_rejected']
                                                  + (int)$kec['total_approved'];
                                    $pct = $kDenominator > 0
                                        ? number_format($kNumerator / $kDenominator * 100, 2)
                                        : '0.00';
                                    $nama = $kecNama[$kec['kec_code']] ?? 'Kec. ' . $kec['kec_code'];
                                    ?>
                                    <tr>
                                        <td class="ps-4">
                                            <a href="?kec=<?= htmlspecialchars($kec['kec_code']) ?>" class="badge text-decoration-none" style="background:#fff3e0;color:#f79039;font-size:.78rem">
                                                <?= htmlspecialchars($kec['kec_code']) ?>
                                            </a>
                                        </td>
                                        <td class="fw-semibold small"><?= htmlspecialchars($nama) ?></td>
                                        <td class="text-center small"><?= $kec['total_petugas'] ?></td>
                                        <td class="text-center small"><?= $kec['total_sls'] ?></td>
                                        <td class="text-center"><span class="badge badge-open"><?= number_format((int)$kec['total_open']) ?></span></td>
                                        <td class="text-center"><span class="badge badge-draft"><?= number_format((int)$kec['total_draft']) ?></span></td>
                                        <td class="text-center"><span class="badge badge-sub"><?= number_format((int)$kec['total_submitted']) ?></span></td>
                                        <td class="text-center"><span class="badge badge-rej"><?= number_format((int)$kec['total_rejected']) ?></span></td>
                                        <td style="min-width:120px;">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height:6px">
                                                    <div class="progress-bar progress-accent" style="width:<?= $pct ?>%; border-radius:6px;"></div>
                                                </div>
                                                <small class="text-muted" style="white-space:nowrap"><?= $pct ?>%</small>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab SLS -->
                    <div class="tab-pane fade" id="tabSLS">
                        <div class="px-4 pt-3 pb-2">
                            <div class="section-head mb-0">
                                Detail per SLS
                                <?php if ($kecFilter !== 'all'): ?>
                                — <span style="color:#f79039"><?= htmlspecialchars($kecNama[$kecFilter]) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="font-size:.82rem;">
                                <thead style="background:#f79039; color:#fff;">
                                    <tr>
                                        <th class="ps-4">#</th>
                                        <th>SLS Code</th>
                                        <th>Kecamatan</th>
                                        <th>Email Petugas</th>
                                        <th class="text-center">Open</th>
                                        <th class="text-center">Draft</th>
                                        <th class="text-center">Sub. Pencacah</th>
                                        <th class="text-center">Sub. Resp.</th>
                                        <th class="text-center">Rejected</th>
                                        <th class="text-center">Approved</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                $page    = max(1, (int)($_GET['page'] ?? 1));
                                $perPage = 100;
                                $slsTotal = (int)$conn->query("SELECT COUNT(*) c FROM sensus_ekonomi $kecWhere")->fetch_assoc()['c'];
                                $totalPages = max(1, ceil($slsTotal / $perPage));
                                $offset = ($page - 1) * $perPage;
                                $slsRows = $conn->query("SELECT id, email, sls_code,
                                    SUBSTRING(sls_code,5,3) AS kec_code,
                                    open_count, draft, submitted_by_pencacah, submitted_respondent, rejected, approved
                                FROM sensus_ekonomi $kecWhere
                                ORDER BY kec_code, sls_code, email
                                LIMIT $perPage OFFSET $offset")->fetch_all(MYSQLI_ASSOC);
                                ?>
                                <?php if (empty($slsRows)): ?>
                                    <tr><td colspan="10" class="text-center text-muted py-4">Belum ada data.</td></tr>
                                <?php else: ?>
                                <?php foreach ($slsRows as $i => $r): ?>
                                    <tr>
                                        <td class="ps-4 text-muted"><?= $offset + $i + 1 ?></td>
                                        <td class="fw-semibold" style="font-family:monospace"><?= htmlspecialchars($r['sls_code']) ?></td>
                                        <td>
                                            <?php $kn = $kecNama[$r['kec_code']] ?? $r['kec_code']; ?>
                                            <span class="badge" style="background:#fff3e0;color:#f79039"><?= $r['kec_code'] ?></span>
                                            <?= htmlspecialchars($kn) ?>
                                        </td>
                                        <td class="text-muted"><?= htmlspecialchars($r['email']) ?></td>
                                        <td class="text-center"><span class="badge badge-open"><?= $r['open_count'] ?></span></td>
                                        <td class="text-center"><span class="badge badge-draft"><?= $r['draft'] ?></span></td>
                                        <td class="text-center"><span class="badge badge-sub"><?= $r['submitted_by_pencacah'] ?></span></td>
                                        <td class="text-center"><span class="badge badge-sub"><?= $r['submitted_respondent'] ?></span></td>
                                        <td class="text-center"><span class="badge badge-rej"><?= $r['rejected'] ?></span></td>
                                        <td class="text-center"><span class="badge bg-secondary bg-opacity-75"><?= $r['approved'] ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($totalPages > 1): ?>
                        <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top">
                            <small class="text-muted">Halaman <?= $page ?> dari <?= $totalPages ?> (<?= $slsTotal ?> record)</small>
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    <?php
                                    $qs = http_build_query(['kec' => $_GET['kec'] ?? '']);
                                    $start = max(1, $page - 2);
                                    $end   = min($totalPages, $page + 2);
                                    if ($page > 1): ?>
                                    <li class="page-item"><a class="page-link" href="?<?= $qs ?>&page=<?= $page-1 ?>#tabSLS">‹</a></li>
                                    <?php endif;
                                    for ($p = $start; $p <= $end; $p++): ?>
                                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?<?= $qs ?>&page=<?= $p ?>#tabSLS"><?= $p ?></a>
                                    </li>
                                    <?php endfor;
                                    if ($page < $totalPages): ?>
                                    <li class="page-item"><a class="page-link" href="?<?= $qs ?>&page=<?= $page+1 ?>#tabSLS">›</a></li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
                    </div>

                </div><!-- /tab-content -->
            </div>

        </div><!-- /page-body -->
    </div><!-- /main-content -->
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "'Roboto', system-ui, sans-serif";
Chart.defaults.font.size   = 12;

// ── Doughnut Chart ────────────────────────────────────
new Chart(document.getElementById('doughnutChart'), {
    type: 'doughnut',
    data: {
        labels: ['Open','Draft','Submitted','Rejected'],
        datasets: [{
            data: <?= $doughnutData ?>,
            backgroundColor: ['#3b82f6','#f59e0b','#22c55e','#ef4444'],
            borderWidth: 2, borderColor: '#fff',
        }]
    },
    options: {
        cutout: '65%',
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 10 } } },
        animation: { duration: 600 },
    }
});

// ── Bar Chart per Petugas ─────────────────────────────
const labels    = <?= $chartLabels ?>;
const barOpen   = <?= $chartOpen ?>;
const barDraft  = <?= $chartDraft ?>;
const barSub    = <?= $chartSubmitted ?>;
const barRej    = <?= $chartRejected ?>;

new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [
            { label:'Open',      data:barOpen,  backgroundColor:'#93c5fd', stack:'s' },
            { label:'Draft',     data:barDraft, backgroundColor:'#fcd34d', stack:'s' },
            { label:'Submitted', data:barSub,   backgroundColor:'#4ade80', stack:'s' },
            { label:'Rejected',  data:barRej,   backgroundColor:'#f87171', stack:'s' },
        ]
    },
    options: {
        responsive: false,
        maintainAspectRatio: false,
        scales: {
            x: { stacked:true, ticks:{ maxRotation:45, font:{size:10} } },
            y: { stacked:true, beginAtZero:true },
        },
        plugins: { legend:{ position:'top', labels:{ boxWidth:12 } } },
        animation: { duration:600 },
    }
});

// ── Activate tab from hash ────────────────────────────
const hash = window.location.hash;
if (hash === '#tabSLS') {
    new bootstrap.Tab(document.querySelector('[data-bs-target="#tabSLS"]')).show();
}
</script>
</body>
</html>
