<?php
require_once 'config.php';
requireLogin();
requireSuperAdmin();

// Auto-create tabel
$conn->query("CREATE TABLE IF NOT EXISTS pencacahan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(150) NOT NULL,
    kdkec VARCHAR(10) NOT NULL,
    kddesa VARCHAR(100) NULL,
    kdsls VARCHAR(100) NULL,
    status VARCHAR(50) DEFAULT 'OPEN',
    catatan TEXT NULL,
    tanggal_update DATE NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Migrasi kolom yang mungkin belum ada di tabel lama
$existingCols = [];
$colRes = $conn->query("SHOW COLUMNS FROM pencacahan");
while ($col = $colRes->fetch_assoc()) $existingCols[] = $col['Field'];

if (!in_array('id', $existingCols))
    $conn->query("ALTER TABLE pencacahan ADD COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
if (!in_array('catatan', $existingCols))
    $conn->query("ALTER TABLE pencacahan ADD COLUMN catatan TEXT NULL");
if (!in_array('tanggal_update', $existingCols))
    $conn->query("ALTER TABLE pencacahan ADD COLUMN tanggal_update DATE NULL");
if (!in_array('created_at', $existingCols))
    $conn->query("ALTER TABLE pencacahan ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
if (!in_array('updated_at', $existingCols))
    $conn->query("ALTER TABLE pencacahan ADD COLUMN updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP");

// Perlebar kolom yang mungkin terlalu kecil
$conn->query("ALTER TABLE pencacahan MODIFY COLUMN nama VARCHAR(150)");
$conn->query("ALTER TABLE pencacahan MODIFY COLUMN kdkec VARCHAR(10)");
$conn->query("ALTER TABLE pencacahan MODIFY COLUMN kddesa VARCHAR(100)");
$conn->query("ALTER TABLE pencacahan MODIFY COLUMN kdsls VARCHAR(100)");

$kecMap = [
    "010" => "Dampal Selatan", "020" => "Dampal Utara",
    "030" => "Dondo",          "031" => "Ogodeide",
    "032" => "Basidondo",      "040" => "Baolan",
    "041" => "Lampasio",       "050" => "Galang",
    "060" => "Tolitoli Utara", "061" => "Dako Pemean",
];

// ── Handle POST actions ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $e = fn($v) => $conn->real_escape_string(trim((string)($v ?? '')));

    $redir_msg = '';
    $redir_type = 'success';

    if ($action === 'add') {
        $nama   = $e($_POST['nama']   ?? '');
        $kdkec  = $e($_POST['kdkec']  ?? '');
        $kddesa = $e($_POST['kddesa'] ?? '');
        $kdsls  = $e($_POST['kdsls']  ?? '');
        $status = $e($_POST['status'] ?? 'OPEN');
        $catatan= $e($_POST['catatan']?? '');
        $tgl    = $e($_POST['tanggal_update'] ?? date('Y-m-d'));
        if (!$nama || !$kdkec) {
            $redir_msg = 'Nama dan Kecamatan wajib diisi.'; $redir_type = 'danger';
        } else {
            $conn->query("INSERT INTO pencacahan (nama,kdkec,kddesa,kdsls,status,catatan,tanggal_update)
                          VALUES ('$nama','$kdkec','$kddesa','$kdsls','$status','$catatan','$tgl')");
            $redir_msg = $conn->affected_rows ? 'Data berhasil ditambahkan.' : 'Gagal: ' . $conn->error;
            if ($conn->affected_rows < 1) $redir_type = 'danger';
        }
    }
    elseif ($action === 'edit') {
        $id     = (int)($_POST['id'] ?? 0);
        $nama   = $e($_POST['nama']   ?? '');
        $kdkec  = $e($_POST['kdkec']  ?? '');
        $kddesa = $e($_POST['kddesa'] ?? '');
        $kdsls  = $e($_POST['kdsls']  ?? '');
        $status = $e($_POST['status'] ?? 'OPEN');
        $catatan= $e($_POST['catatan']?? '');
        $tgl    = $e($_POST['tanggal_update'] ?? date('Y-m-d'));
        if ($id && $nama && $kdkec) {
            $conn->query("UPDATE pencacahan SET nama='$nama',kdkec='$kdkec',kddesa='$kddesa',
                          kdsls='$kdsls',status='$status',catatan='$catatan',
                          tanggal_update='$tgl' WHERE id=$id");
            $redir_msg = 'Data berhasil diperbarui.';
        } else {
            $redir_msg = 'Data tidak valid.'; $redir_type = 'danger';
        }
    }
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $conn->query("DELETE FROM pencacahan WHERE id=$id");
            $redir_msg = 'Data berhasil dihapus.';
        }
    }
    elseif ($action === 'import_csv') {
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $handle   = fopen($_FILES['csv_file']['tmp_name'], 'r');
            fgetcsv($handle); // skip header
            $inserted = 0; $skipped = 0;
            while (($row = fgetcsv($handle)) !== false) {
                $row = array_pad($row, 7, '');
                [$rnama,$rkdkec,$rkddesa,$rkdsls,$rstatus,$rcatatan,$rtgl] = $row;
                $rnama   = trim($rnama);
                $rkdkec  = trim($rkdkec);
                if (!$rnama || !$rkdkec) { $skipped++; continue; }
                $rstatus = strtoupper(trim($rstatus));
                if (!in_array($rstatus, ['OPEN','SUBMITTED','REJECTED'])) $rstatus = 'OPEN';
                $rtgl = trim($rtgl);
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $rtgl)) $rtgl = date('Y-m-d');
                $n=$e($rnama);$k=$e($rkdkec);$d=$e($rkddesa);$s=$e($rkdsls);
                $st=$e($rstatus);$cat=$e($rcatatan);$tgl=$e($rtgl);
                if ($conn->query("INSERT INTO pencacahan (nama,kdkec,kddesa,kdsls,status,catatan,tanggal_update)
                                  VALUES ('$n','$k','$d','$s','$st','$cat','$tgl')")) {
                    $inserted++;
                } else {
                    $skipped++;
                }
            }
            fclose($handle);
            $redir_msg = "Import selesai: $inserted data berhasil" . ($skipped ? ", $skipped baris dilewati." : '.');
        } else {
            $redir_msg = 'Gagal membaca file CSV.'; $redir_type = 'danger';
        }
    }

    $qs = http_build_query(['msg' => $redir_msg, 'msg_type' => $redir_type,
                            'q' => $_GET['q'] ?? '', 'kec' => $_GET['kec'] ?? '',
                            'sf' => $_GET['sf'] ?? '', 'page' => $_GET['page'] ?? 1]);
    header("Location: monitoring_pencacahan.php?$qs");
    exit;
}

// ── GET params ────────────────────────────────────────────────────────────
$q       = $conn->real_escape_string($_GET['q']  ?? '');
$filterKec    = $conn->real_escape_string($_GET['kec'] ?? '');
$filterStatus = $conn->real_escape_string($_GET['sf']  ?? '');
$msg     = htmlspecialchars($_GET['msg']      ?? '');
$msgType = htmlspecialchars($_GET['msg_type'] ?? 'success');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;

// ── Build WHERE ───────────────────────────────────────────────────────────
$conds = [];
if ($q)           $conds[] = "(nama LIKE '%$q%' OR kddesa LIKE '%$q%' OR kdsls LIKE '%$q%')";
if ($filterKec)   $conds[] = "kdkec='$filterKec'";
if ($filterStatus)$conds[] = "status='$filterStatus'";
$whereSQL = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

// ── Stats (always from full table) ───────────────────────────────────────
$statsAll = ['SUBMITTED' => 0, 'OPEN' => 0, 'REJECTED' => 0, 'total' => 0];
$sRes = $conn->query("SELECT status, COUNT(*) as jml FROM pencacahan GROUP BY status");
if ($sRes) while ($r = $sRes->fetch_assoc()) {
    $s = strtoupper($r['status']);
    if (isset($statsAll[$s])) $statsAll[$s] = (int)$r['jml'];
    $statsAll['total'] += (int)$r['jml'];
}

// ── Filtered count & records ─────────────────────────────────────────────
$totalRec = (int)$conn->query("SELECT COUNT(*) as c FROM pencacahan $whereSQL")->fetch_assoc()['c'];
$totalPages = max(1, (int)ceil($totalRec / $perPage));
$offset = ($page - 1) * $perPage;
$records = $conn->query("SELECT * FROM pencacahan $whereSQL ORDER BY tanggal_update DESC, id DESC LIMIT $perPage OFFSET $offset")->fetch_all(MYSQLI_ASSOC);

// ── Last update ───────────────────────────────────────────────────────────
$luRow = $conn->query("SELECT MAX(tanggal_update) as lu FROM pencacahan")->fetch_assoc();
$lastUpdate = $luRow['lu'] ? date('d M Y', strtotime($luRow['lu'])) : '—';

function statusBadge($s) {
    return match(strtoupper($s)) {
        'SUBMITTED' => '<span class="badge bg-success">SUBMITTED</span>',
        'REJECTED'  => '<span class="badge bg-danger">REJECTED</span>',
        default     => '<span class="badge bg-warning text-dark">OPEN</span>',
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Pencacahan — SEMANIS 2026</title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        .stat-icon { width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; }
        .section-head { font-weight:700; font-size:.7rem; text-transform:uppercase; letter-spacing:.08em; color:#94a3b8; margin-bottom:14px; }
        .table-hover tbody tr:hover { background:#fff8f0; }
        .act-btn { padding:3px 8px; font-size:.78rem; border-radius:6px; }
        .search-bar { max-width:320px; }
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
                    <i class="bi bi-clipboard2-check-fill me-2" style="color:#f79039"></i>Monitoring Pencacahan
                </div>
                <div class="topbar-sub">Sensus Ekonomi 2026 — BPS Kab. Toli-Toli &nbsp;|&nbsp; Update terakhir: <strong><?= $lastUpdate ?></strong></div>
            </div>
            <div class="topbar-right">
                <small class="text-muted d-none d-md-flex align-items-center gap-1">
                    <i class="bi bi-clock"></i><?= date('d M Y, H:i') ?> WITA
                </small>
            </div>
        </header>

        <div class="page-body">

            <?php if ($msg): ?>
            <div class="alert alert-<?= $msgType ?> alert-dismissible d-flex align-items-center" style="border-radius:10px">
                <i class="bi bi-<?= $msgType === 'success' ? 'check-circle-fill' : 'x-circle-fill' ?> me-2"></i>
                <?= $msg ?>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- ── Stat Cards ───────────────────────────────────────────── -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-layers-fill"></i></div>
                            <div><div class="fs-2 fw-bold lh-1"><?= $statsAll['total'] ?></div><div class="text-muted small">Total Dokumen</div></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle-fill"></i></div>
                            <div><div class="fs-2 fw-bold lh-1"><?= $statsAll['SUBMITTED'] ?></div><div class="text-muted small">Submitted</div></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-clock-fill"></i></div>
                            <div><div class="fs-2 fw-bold lh-1"><?= $statsAll['OPEN'] ?></div><div class="text-muted small">In Process</div></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-exclamation-triangle-fill"></i></div>
                            <div><div class="fs-2 fw-bold lh-1"><?= $statsAll['REJECTED'] ?></div><div class="text-muted small">Rejected</div></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Toolbar ──────────────────────────────────────────────── -->
            <div class="card stat-card p-3 mb-4">
                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">

                    <!-- Search & Filter -->
                    <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                        <div class="input-group search-bar">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="q" class="form-control border-start-0" placeholder="Cari nama, desa, SLS…" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                        </div>
                        <select name="kec" class="form-select" style="max-width:200px">
                            <option value="">Semua Kecamatan</option>
                            <?php foreach ($kecMap as $kd => $nm): ?>
                            <option value="<?= $kd ?>" <?= ($filterKec === $kd) ? 'selected' : '' ?>><?= $nm ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="sf" class="form-select" style="max-width:150px">
                            <option value="">Semua Status</option>
                            <option value="SUBMITTED" <?= $filterStatus === 'SUBMITTED' ? 'selected' : '' ?>>SUBMITTED</option>
                            <option value="OPEN"      <?= $filterStatus === 'OPEN'      ? 'selected' : '' ?>>OPEN</option>
                            <option value="REJECTED"  <?= $filterStatus === 'REJECTED'  ? 'selected' : '' ?>>REJECTED</option>
                        </select>
                        <button type="submit" class="btn btn-sm text-white" style="background:#f79039">
                            <i class="bi bi-funnel-fill me-1"></i>Filter
                        </button>
                        <?php if ($q || $filterKec || $filterStatus): ?>
                        <a href="monitoring_pencacahan.php" class="btn btn-sm btn-outline-secondary">Reset</a>
                        <?php endif; ?>
                    </form>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-sm text-white" style="background:#f79039"
                                onclick="openAddModal()">
                            <i class="bi bi-plus-lg me-1"></i>Tambah Data
                        </button>
                        <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class="bi bi-upload me-1"></i>Import CSV
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="downloadTemplate()">
                            <i class="bi bi-download me-1"></i>Template CSV
                        </button>
                    </div>
                </div>
            </div>

            <!-- ── Tabel Data ────────────────────────────────────────────── -->
            <div class="card stat-card p-0 mb-4">
                <div class="card-body p-0">
                    <div class="d-flex justify-content-between align-items-center px-4 pt-3 pb-2">
                        <div class="section-head mb-0">
                            <i class="bi bi-table me-1"></i>Data Pencacahan
                            <span class="badge bg-secondary ms-1 fw-normal"><?= $totalRec ?> record</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead style="background:#f79039; color:#fff;">
                                <tr>
                                    <th class="ps-4" style="width:50px">#</th>
                                    <th>Nama Petugas</th>
                                    <th>Kecamatan</th>
                                    <th>Desa</th>
                                    <th>SLS</th>
                                    <th>Status</th>
                                    <th>Tgl Update</th>
                                    <th>Catatan</th>
                                    <th class="text-center pe-4" style="width:110px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($records)): ?>
                                <tr><td colspan="9" class="text-center text-muted py-4">Belum ada data.</td></tr>
                            <?php else: ?>
                            <?php foreach ($records as $i => $r): ?>
                                <tr>
                                    <td class="ps-4 text-muted small"><?= $offset + $i + 1 ?></td>
                                    <td class="fw-semibold small"><?= htmlspecialchars($r['nama']) ?></td>
                                    <td class="small"><?= htmlspecialchars($kecMap[$r['kdkec']] ?? $r['kdkec']) ?></td>
                                    <td class="small"><?= htmlspecialchars($r['kddesa'] ?? '—') ?></td>
                                    <td class="small"><?= htmlspecialchars($r['kdsls']  ?? '—') ?></td>
                                    <td><?= statusBadge($r['status']) ?></td>
                                    <td class="small text-muted"><?= $r['tanggal_update'] ? date('d/m/Y', strtotime($r['tanggal_update'])) : '—' ?></td>
                                    <td class="small text-muted" style="max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        <?= htmlspecialchars($r['catatan'] ?? '') ?>
                                    </td>
                                    <td class="text-center pe-4">
                                        <button class="btn btn-sm btn-outline-primary act-btn me-1"
                                                onclick='openEditModal(<?= json_encode($r) ?>)'>
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger act-btn"
                                                onclick="confirmDelete(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['nama']), ENT_QUOTES) ?>')">
                                            <i class="bi bi-trash-fill"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top">
                        <small class="text-muted">Halaman <?= $page ?> dari <?= $totalPages ?> (<?= $totalRec ?> record)</small>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <?php
                                $qs_base = http_build_query(['q' => $_GET['q'] ?? '', 'kec' => $_GET['kec'] ?? '', 'sf' => $_GET['sf'] ?? '']);
                                $start = max(1, $page - 2);
                                $end   = min($totalPages, $page + 2);
                                if ($page > 1): ?>
                                <li class="page-item"><a class="page-link" href="?<?= $qs_base ?>&page=<?= $page - 1 ?>">‹</a></li>
                                <?php endif;
                                for ($p = $start; $p <= $end; $p++): ?>
                                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= $qs_base ?>&page=<?= $p ?>"><?= $p ?></a>
                                </li>
                                <?php endfor;
                                if ($page < $totalPages): ?>
                                <li class="page-item"><a class="page-link" href="?<?= $qs_base ?>&page=<?= $page + 1 ?>">›</a></li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /page-body -->
    </div><!-- /main-content -->
</div>

<!-- ── Modal Tambah / Edit ──────────────────────────────────────────────── -->
<div class="modal fade" id="dataModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="dataForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id"     id="formId">
                <div class="modal-header" style="background:#f79039; color:#fff;">
                    <h5 class="modal-title" id="modalTitle"><i class="bi bi-plus-lg me-2"></i>Tambah Data</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nama Petugas <span class="text-danger">*</span></label>
                            <input type="text" name="nama" id="formNama" class="form-control" required placeholder="Nama lengkap petugas">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kecamatan <span class="text-danger">*</span></label>
                            <select name="kdkec" id="formKdkec" class="form-select" required>
                                <option value="">— Pilih Kecamatan —</option>
                                <?php foreach ($kecMap as $kd => $nm): ?>
                                <option value="<?= $kd ?>"><?= $nm ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Kode/Nama Desa</label>
                            <input type="text" name="kddesa" id="formKddesa" class="form-control" placeholder="Contoh: 001 / Tuweley">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Kode SLS</label>
                            <input type="text" name="kdsls" id="formKdsls" class="form-control" placeholder="Contoh: 001">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" id="formStatus" class="form-select">
                                <option value="OPEN">OPEN</option>
                                <option value="SUBMITTED">SUBMITTED</option>
                                <option value="REJECTED">REJECTED</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Update</label>
                            <input type="date" name="tanggal_update" id="formTgl" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Catatan</label>
                            <textarea name="catatan" id="formCatatan" class="form-control" rows="2" placeholder="Catatan (opsional)"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn text-white fw-semibold" style="background:#f79039" id="submitBtn">
                        <i class="bi bi-save-fill me-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Modal Delete Confirm ──────────────────────────────────────────────── -->
<form method="POST" id="deleteForm">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-trash-fill me-2"></i>Konfirmasi Hapus</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Yakin menghapus data petugas <strong id="deleteNama"></strong>? Tindakan ini tidak dapat dibatalkan.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger fw-semibold" onclick="document.getElementById('deleteForm').submit()">
                    <i class="bi bi-trash-fill me-1"></i>Hapus
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Modal Import CSV ──────────────────────────────────────────────────── -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import_csv">
                <div class="modal-header" style="background:#f79039; color:#fff;">
                    <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Import CSV</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Upload file CSV dengan kolom: <code>nama, kdkec, kddesa, kdsls, status, catatan, tanggal_update</code><br>
                        Format tanggal: <code>YYYY-MM-DD</code> &nbsp;|&nbsp; Status: <code>OPEN / SUBMITTED / REJECTED</code>
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih File CSV</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv,text/csv" required>
                    </div>
                    <div class="alert alert-warning small py-2 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Baris yang tidak memiliki nama atau kecamatan akan dilewati. Download template untuk format yang benar.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-outline-success btn-sm me-auto" onclick="downloadTemplate()">
                        <i class="bi bi-download me-1"></i>Template
                    </button>
                    <button type="submit" class="btn btn-success fw-semibold">
                        <i class="bi bi-upload me-1"></i>Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
function openAddModal() {
    document.getElementById('formAction').value = 'add';
    document.getElementById('formId').value     = '';
    document.getElementById('formNama').value   = '';
    document.getElementById('formKdkec').value  = '';
    document.getElementById('formKddesa').value = '';
    document.getElementById('formKdsls').value  = '';
    document.getElementById('formStatus').value = 'OPEN';
    document.getElementById('formCatatan').value= '';
    document.getElementById('formTgl').value    = '<?= date('Y-m-d') ?>';
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-plus-lg me-2"></i>Tambah Data';
    document.getElementById('submitBtn').innerHTML  = '<i class="bi bi-save-fill me-1"></i>Simpan';
    new bootstrap.Modal(document.getElementById('dataModal')).show();
}

function openEditModal(r) {
    document.getElementById('formAction').value = 'edit';
    document.getElementById('formId').value     = r.id;
    document.getElementById('formNama').value   = r.nama   || '';
    document.getElementById('formKdkec').value  = r.kdkec  || '';
    document.getElementById('formKddesa').value = r.kddesa || '';
    document.getElementById('formKdsls').value  = r.kdsls  || '';
    document.getElementById('formStatus').value = r.status || 'OPEN';
    document.getElementById('formCatatan').value= r.catatan|| '';
    document.getElementById('formTgl').value    = r.tanggal_update || '<?= date('Y-m-d') ?>';
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil-fill me-2"></i>Edit Data';
    document.getElementById('submitBtn').innerHTML  = '<i class="bi bi-save-fill me-1"></i>Perbarui';
    new bootstrap.Modal(document.getElementById('dataModal')).show();
}

function confirmDelete(id, nama) {
    document.getElementById('deleteId').value   = id;
    document.getElementById('deleteNama').textContent = nama;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

function downloadTemplate() {
    const rows = [
        ['nama','kdkec','kddesa','kdsls','status','catatan','tanggal_update'],
        ['Contoh Petugas','070','001','001','OPEN','','<?= date('Y-m-d') ?>'],
    ];
    const csv = rows.map(r => r.join(',')).join('\n');
    const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'template_pencacahan.csv';
    a.click();
}
</script>
</body>
</html>
