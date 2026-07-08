<?php
require_once 'config.php';
requireLogin();
requireSuperAdmin();

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
    `revoke` INT DEFAULT 0,
    edited_by_pengawas INT DEFAULT 0,
    edited_by_admin_kabupaten INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_email_sls (email, sls_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$chk = $conn->query("SHOW COLUMNS FROM sensus_ekonomi LIKE 'revoke'");
if ($chk && $chk->num_rows === 0) {
    $conn->query("ALTER TABLE sensus_ekonomi ADD COLUMN `revoke` INT DEFAULT 0 AFTER approved");
}
$chk2 = $conn->query("SHOW COLUMNS FROM sensus_ekonomi LIKE 'edited_by_pengawas'");
if ($chk2 && $chk2->num_rows === 0) {
    $conn->query("ALTER TABLE sensus_ekonomi ADD COLUMN edited_by_pengawas INT DEFAULT 0 AFTER `revoke`");
}
$chk3 = $conn->query("SHOW COLUMNS FROM sensus_ekonomi LIKE 'edited_by_admin_kabupaten'");
if ($chk3 && $chk3->num_rows === 0) {
    $conn->query("ALTER TABLE sensus_ekonomi ADD COLUMN edited_by_admin_kabupaten INT DEFAULT 0 AFTER edited_by_pengawas");
}
// Pastikan unique key ada (deduplikasi dulu jika ada duplikat lama)
$chkIdx = $conn->query("SHOW INDEX FROM sensus_ekonomi WHERE Key_name = 'uq_email_sls'");
if ($chkIdx && $chkIdx->num_rows === 0) {
    $conn->query("DELETE s1 FROM sensus_ekonomi s1
        JOIN sensus_ekonomi s2
        ON s1.email = s2.email AND s1.sls_code = s2.sls_code AND s1.id < s2.id");
    $conn->query("ALTER TABLE sensus_ekonomi ADD UNIQUE KEY uq_email_sls (email, sls_code)");
}

$kecNama = [
    '010' => 'Dampal Selatan', '020' => 'Dampal Utara',
    '030' => 'Dondo',          '031' => 'Ogodeide',
    '032' => 'Basidondo',      '040' => 'Baolan',
    '041' => 'Lampasio',       '050' => 'Galang',
    '060' => 'Tolitoli Utara', '061' => 'Dako Pemean',
];

// ── POST handlers ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $e = fn($v) => $conn->real_escape_string(trim((string)($v ?? '')));

    $redir_msg  = '';
    $redir_type = 'success';

    if ($action === 'add') {
        $email  = $e($_POST['email'] ?? '');
        $sls    = $e($_POST['sls_code'] ?? '');
        $open   = (int)($_POST['open_count'] ?? 0);
        $draft  = (int)($_POST['draft'] ?? 0);
        $subp   = (int)($_POST['submitted_by_pencacah'] ?? 0);
        $subr   = (int)($_POST['submitted_respondent'] ?? 0);
        $rej    = (int)($_POST['rejected'] ?? 0);
        $appr   = (int)($_POST['approved'] ?? 0);
        $rev    = (int)($_POST['revoke'] ?? 0);
        $ep     = (int)($_POST['edited_by_pengawas'] ?? 0);
        $ea     = (int)($_POST['edited_by_admin_kabupaten'] ?? 0);

        if (!$email || !$sls) {
            $redir_msg = 'Email dan SLS Code wajib diisi.'; $redir_type = 'danger';
        } else {
            $conn->query("INSERT INTO sensus_ekonomi
                (email,sls_code,open_count,draft,submitted_by_pencacah,submitted_respondent,rejected,approved,`revoke`,edited_by_pengawas,edited_by_admin_kabupaten)
                VALUES ('$email','$sls',$open,$draft,$subp,$subr,$rej,$appr,$rev,$ep,$ea)");
            $redir_msg = $conn->affected_rows ? 'Data berhasil disimpan.' : ('Gagal: ' . $conn->error);
            if (!$conn->affected_rows) $redir_type = 'danger';
        }
    }
    elseif ($action === 'edit') {
        $id   = (int)($_POST['id'] ?? 0);
        $email = $e($_POST['email'] ?? '');
        $sls   = $e($_POST['sls_code'] ?? '');
        $open  = (int)($_POST['open_count'] ?? 0);
        $draft = (int)($_POST['draft'] ?? 0);
        $subp  = (int)($_POST['submitted_by_pencacah'] ?? 0);
        $subr  = (int)($_POST['submitted_respondent'] ?? 0);
        $rej   = (int)($_POST['rejected'] ?? 0);
        $appr  = (int)($_POST['approved'] ?? 0);
        $rev   = (int)($_POST['revoke'] ?? 0);
        $ep    = (int)($_POST['edited_by_pengawas'] ?? 0);
        $ea    = (int)($_POST['edited_by_admin_kabupaten'] ?? 0);
        if ($id && $email && $sls) {
            $conn->query("UPDATE sensus_ekonomi SET
                email='$email', sls_code='$sls',
                open_count=$open, draft=$draft,
                submitted_by_pencacah=$subp, submitted_respondent=$subr,
                rejected=$rej, approved=$appr, `revoke`=$rev,
                edited_by_pengawas=$ep, edited_by_admin_kabupaten=$ea
                WHERE id=$id");
            $redir_msg = 'Data berhasil diperbarui.';
        } else {
            $redir_msg = 'Data tidak valid.'; $redir_type = 'danger';
        }
    }
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $conn->query("DELETE FROM sensus_ekonomi WHERE id=$id");
            $redir_msg = 'Data berhasil dihapus.';
        }
    }
    elseif ($action === 'delete_all') {
        $conn->query("TRUNCATE TABLE sensus_ekonomi");
        $redir_msg = 'Semua data berhasil dihapus.';
    }
    elseif ($action === 'import_csv') {
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $handle     = fopen($_FILES['csv_file']['tmp_name'], 'r');
            $header     = fgetcsv($handle);
            $upserted   = 0; $skipped = 0;
            $clearFirst = ($_POST['overwrite'] ?? '0') === '1';

            if ($clearFirst) {
                $conn->query("TRUNCATE TABLE sensus_ekonomi");
            }

            // Format kolom: Email, SLS_Code, OPEN, DRAFT, SUBMITTED_BY_PENCACAH,
            //               APPROVED_BY_PENGAWAS, REJECTED_BY_PENGAWAS, REVOKED_BY_PENGAWAS,
            //               EDITED_BY_PENGAWAS, EDITED_BY_ADMIN_KABUPATEN
            while (($row = fgetcsv($handle)) !== false) {
                $row    = array_pad($row, 10, 0);
                [$remail, $rsls, $ropen, $rdraft, $rsubp, $rappr, $rrej, $rrev, $rep, $rea] = $row;
                $remail = strtolower(trim(trim($remail), '"'));
                $rsls   = trim(trim($rsls), '"');
                if (!$remail || !$rsls) { $skipped++; continue; }

                $em = $conn->real_escape_string($remail);
                $sl = $conn->real_escape_string($rsls);
                $op = (int)$ropen;
                $dr = (int)$rdraft;
                $sp = (int)$rsubp;
                $ap = (int)$rappr;   // APPROVED_BY_PENGAWAS
                $rj = (int)$rrej;   // REJECTED_BY_PENGAWAS
                $rv = (int)$rrev;   // REVOKED_BY_PENGAWAS
                $epv = (int)$rep;   // EDITED_BY_PENGAWAS
                $eav = (int)$rea;   // EDITED_BY_ADMIN_KABUPATEN

                $r = $conn->query("INSERT INTO sensus_ekonomi
                    (email,sls_code,open_count,draft,submitted_by_pencacah,submitted_respondent,rejected,approved,`revoke`,edited_by_pengawas,edited_by_admin_kabupaten)
                    VALUES ('$em','$sl',$op,$dr,$sp,0,$rj,$ap,$rv,$epv,$eav)
                    ON DUPLICATE KEY UPDATE
                    open_count=$op, draft=$dr, submitted_by_pencacah=$sp,
                    rejected=$rj, approved=$ap, `revoke`=$rv,
                    edited_by_pengawas=$epv, edited_by_admin_kabupaten=$eav");
                if ($r) $upserted++; else $skipped++;
            }
            fclose($handle);
            if ($upserted > 0) {
                $conn->query("CREATE TABLE IF NOT EXISTS sensus_meta (k VARCHAR(50) PRIMARY KEY, v TEXT) ENGINE=InnoDB");
                $now = date('Y-m-d H:i:s');
                $conn->query("INSERT INTO sensus_meta (k,v) VALUES ('last_import','$now') ON DUPLICATE KEY UPDATE v='$now'");
            }
            $redir_msg = "Import selesai: $upserted baris diproses (insert/update)" . ($skipped ? ", $skipped dilewati (email/SLS kosong)" : '') . '.';
        } else {
            $redir_msg = 'Gagal membaca file CSV.'; $redir_type = 'danger';
        }
    }

    $qs = http_build_query(['msg' => $redir_msg, 'msg_type' => $redir_type,
        'q' => $_GET['q'] ?? '', 'kec' => $_GET['kec'] ?? '', 'page' => $_GET['page'] ?? 1]);
    header("Location: data_sensus_ekonomi.php?$qs");
    exit;
}

// ── GET params ────────────────────────────────────────────────────────────────
$q         = $conn->real_escape_string($_GET['q']  ?? '');
$filterKec = $conn->real_escape_string($_GET['kec'] ?? '');
$msg       = htmlspecialchars($_GET['msg']      ?? '');
$msgType   = htmlspecialchars($_GET['msg_type'] ?? 'success');
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 50;

$conds = [];
if ($q)         $conds[] = "(email LIKE '%$q%' OR sls_code LIKE '%$q%')";
if ($filterKec) $conds[] = "SUBSTRING(sls_code,5,3)='$filterKec'";
$whereSQL = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

$stats = $conn->query("SELECT
    COUNT(*) AS total,
    COALESCE(SUM(open_count),0) AS total_open,
    COALESCE(SUM(draft),0) AS total_draft,
    COALESCE(SUM(submitted_by_pencacah+submitted_respondent),0) AS total_submitted,
    COALESCE(SUM(rejected),0) AS total_rejected
FROM sensus_ekonomi")->fetch_assoc();

$totalRec   = (int)$conn->query("SELECT COUNT(*) c FROM sensus_ekonomi $whereSQL")->fetch_assoc()['c'];
$totalPages = max(1, (int)ceil($totalRec / $perPage));
$offset     = ($page - 1) * $perPage;
$records    = $conn->query("SELECT * FROM sensus_ekonomi $whereSQL ORDER BY SUBSTRING(sls_code,5,3), sls_code, email LIMIT $perPage OFFSET $offset")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Sensus Ekonomi — SEMANIS 2026</title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        .stat-icon { width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; }
        .section-head { font-weight:700; font-size:.7rem; text-transform:uppercase; letter-spacing:.08em; color:#94a3b8; margin-bottom:14px; }
        .table-hover tbody tr:hover { background:#fff8f0; }
        .act-btn { padding:3px 8px; font-size:.78rem; border-radius:6px; }
        .badge-open { background:#dbeafe; color:#1d4ed8; }
        .badge-draft { background:#fef9c3; color:#92400e; }
        .badge-sub  { background:#dcfce7; color:#166534; }
        .badge-rej  { background:#fee2e2; color:#991b1b; }
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
                    <i class="bi bi-database-fill-gear me-2" style="color:#f79039"></i>Data Sensus Ekonomi
                </div>
                <div class="topbar-sub">Kelola Data Progress Pencacahan SE2026</div>
            </div>
            <div class="topbar-right">
                <a href="monitoring_sensus_ekonomi.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-graph-up-arrow me-1"></i>Lihat Monitoring
                </a>
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

            <!-- ── Stat Cards ─────────────────────────────────── -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-layers-fill"></i></div>
                            <div><div class="fs-2 fw-bold lh-1"><?= number_format((int)$stats['total']) ?></div><div class="text-muted small">Total Record</div></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check2-all"></i></div>
                            <div><div class="fs-2 fw-bold lh-1"><?= number_format((int)$stats['total_submitted']) ?></div><div class="text-muted small">Submitted</div></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-pencil-square"></i></div>
                            <div><div class="fs-2 fw-bold lh-1"><?= number_format((int)$stats['total_open'] + (int)$stats['total_draft']) ?></div><div class="text-muted small">Open + Draft</div></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-x-circle-fill"></i></div>
                            <div><div class="fs-2 fw-bold lh-1"><?= number_format((int)$stats['total_rejected']) ?></div><div class="text-muted small">Rejected</div></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Toolbar ──────────────────────────────────────── -->
            <div class="card stat-card p-3 mb-4">
                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                    <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                        <div class="input-group" style="max-width:280px">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="q" class="form-control border-start-0" placeholder="Cari email, SLS…" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                        </div>
                        <select name="kec" class="form-select" style="max-width:200px">
                            <option value="">Semua Kecamatan</option>
                            <?php foreach ($kecNama as $kd => $nm): ?>
                            <option value="<?= $kd ?>" <?= $filterKec === $kd ? 'selected' : '' ?>><?= $kd ?> — <?= $nm ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm text-white" style="background:#f79039">
                            <i class="bi bi-funnel-fill me-1"></i>Filter
                        </button>
                        <?php if ($q || $filterKec): ?>
                        <a href="data_sensus_ekonomi.php" class="btn btn-sm btn-outline-secondary">Reset</a>
                        <?php endif; ?>
                    </form>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-sm text-white" style="background:#f79039" onclick="openAddModal()">
                            <i class="bi bi-plus-lg me-1"></i>Tambah
                        </button>
                        <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class="bi bi-upload me-1"></i>Import CSV
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="downloadTemplate()">
                            <i class="bi bi-download me-1"></i>Template
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="confirmDeleteAll()">
                            <i class="bi bi-trash3-fill me-1"></i>Hapus Semua
                        </button>
                    </div>
                </div>
            </div>

            <!-- ── Data Table ──────────────────────────────────── -->
            <div class="card stat-card p-0">
                <div class="d-flex justify-content-between align-items-center px-4 pt-3 pb-2">
                    <div class="section-head mb-0">
                        <i class="bi bi-table me-1"></i>Data Progress Sensus Ekonomi
                        <span class="badge bg-secondary ms-1 fw-normal"><?= $totalRec ?> record</span>
                    </div>
                    <small class="text-muted"><?= date('d M Y, H:i') ?> WITA</small>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:.83rem;">
                        <thead style="background:#f79039; color:#fff;">
                            <tr>
                                <th class="ps-4" style="width:50px">#</th>
                                <th>Email Petugas</th>
                                <th>SLS Code</th>
                                <th>Kecamatan</th>
                                <th class="text-center">Open</th>
                                <th class="text-center">Draft</th>
                                <th class="text-center">Sub. Pencacah</th>
                                <th class="text-center">Sub. Resp.</th>
                                <th class="text-center">Rejected</th>
                                <th class="text-center">Approved</th>
                                <th class="text-center">Revoke</th>
                                <th class="text-center">Edit Pengawas</th>
                                <th class="text-center">Edit Admin Kab</th>
                                <th class="text-center pe-4" style="width:90px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($records)): ?>
                            <tr><td colspan="13" class="text-center text-muted py-5">
                                Belum ada data. Gunakan tombol <strong>Tambah</strong> atau <strong>Import CSV</strong>.
                            </td></tr>
                        <?php else: ?>
                        <?php foreach ($records as $i => $r): ?>
                            <?php $kec_code = substr($r['sls_code'], 4, 3); ?>
                            <tr>
                                <td class="ps-4 text-muted"><?= $offset + $i + 1 ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($r['email']) ?></td>
                                <td style="font-family:monospace"><?= htmlspecialchars($r['sls_code']) ?></td>
                                <td>
                                    <span class="badge" style="background:#fff3e0;color:#f79039"><?= $kec_code ?></span>
                                    <?= htmlspecialchars($kecNama[$kec_code] ?? '') ?>
                                </td>
                                <td class="text-center"><span class="badge badge-open"><?= $r['open_count'] ?></span></td>
                                <td class="text-center"><span class="badge badge-draft"><?= $r['draft'] ?></span></td>
                                <td class="text-center"><span class="badge badge-sub"><?= $r['submitted_by_pencacah'] ?></span></td>
                                <td class="text-center"><span class="badge badge-sub"><?= $r['submitted_respondent'] ?></span></td>
                                <td class="text-center"><span class="badge badge-rej"><?= $r['rejected'] ?></span></td>
                                <td class="text-center"><span class="badge bg-secondary bg-opacity-75"><?= $r['approved'] ?></span></td>
                                <td class="text-center"><span class="badge" style="background:#ffedd5;color:#c2410c"><?= $r['revoke'] ?></span></td>
                                <td class="text-center"><span class="badge" style="background:#d1fae5;color:#065f46"><?= $r['edited_by_pengawas'] ?? 0 ?></span></td>
                                <td class="text-center"><span class="badge" style="background:#e0e7ff;color:#3730a3"><?= $r['edited_by_admin_kabupaten'] ?? 0 ?></span></td>
                                <td class="text-center pe-4">
                                    <button class="btn btn-sm btn-outline-primary act-btn me-1"
                                            onclick='openEditModal(<?= json_encode($r) ?>)'>
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger act-btn"
                                            onclick="confirmDelete(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['email']), ENT_QUOTES) ?>')">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top">
                    <small class="text-muted">Halaman <?= $page ?> dari <?= $totalPages ?> (<?= $totalRec ?> record)</small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <?php
                            $qsBase = http_build_query(['q' => $_GET['q'] ?? '', 'kec' => $_GET['kec'] ?? '']);
                            $start  = max(1, $page - 2);
                            $end    = min($totalPages, $page + 2);
                            if ($page > 1): ?>
                            <li class="page-item"><a class="page-link" href="?<?= $qsBase ?>&page=<?= $page-1 ?>">‹</a></li>
                            <?php endif;
                            for ($p = $start; $p <= $end; $p++): ?>
                            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= $qsBase ?>&page=<?= $p ?>"><?= $p ?></a>
                            </li>
                            <?php endfor;
                            if ($page < $totalPages): ?>
                            <li class="page-item"><a class="page-link" href="?<?= $qsBase ?>&page=<?= $page+1 ?>">›</a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>

        </div><!-- /page-body -->
    </div><!-- /main-content -->
</div>

<!-- ── Modal Tambah / Edit ─────────────────────────────────────────────────── -->
<div class="modal fade" id="dataModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="dataForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="formId">
                <div class="modal-header" style="background:#f79039; color:#fff;">
                    <h5 class="modal-title" id="modalTitle"><i class="bi bi-plus-lg me-2"></i>Tambah Data</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Email Petugas <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="formEmail" class="form-control" required placeholder="contoh@gmail.com">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">SLS Code <span class="text-danger">*</span></label>
                            <input type="text" name="sls_code" id="formSLS" class="form-control" required
                                   placeholder="7206050007000700" maxlength="20" style="font-family:monospace"
                                   oninput="updateKecPreview(this.value)">
                            <small class="text-muted" id="kecPreview"></small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Open</label>
                            <input type="number" name="open_count" id="formOpen" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Draft</label>
                            <input type="number" name="draft" id="formDraft" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Submitted Pencacah</label>
                            <input type="number" name="submitted_by_pencacah" id="formSubP" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Submitted Respondent</label>
                            <input type="number" name="submitted_respondent" id="formSubR" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Rejected</label>
                            <input type="number" name="rejected" id="formRej" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Approved</label>
                            <input type="number" name="approved" id="formAppr" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Revoke</label>
                            <input type="number" name="revoke" id="formRev" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Edited by Pengawas</label>
                            <input type="number" name="edited_by_pengawas" id="formEP" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Edited by Admin Kab</label>
                            <input type="number" name="edited_by_admin_kabupaten" id="formEA" class="form-control" min="0" value="0">
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

<!-- ── Modal Delete ────────────────────────────────────────────────────────── -->
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
                Yakin menghapus data <strong id="deleteEmail"></strong>? Tindakan ini tidak dapat dibatalkan.
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

<!-- ── Modal Delete All ────────────────────────────────────────────────────── -->
<form method="POST" id="deleteAllForm">
    <input type="hidden" name="action" value="delete_all">
</form>
<div class="modal fade" id="deleteAllModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Hapus Semua Data</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger mb-0">
                    <strong>Peringatan!</strong> Semua <?= number_format((int)$stats['total']) ?> record data sensus ekonomi akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger fw-semibold" onclick="document.getElementById('deleteAllForm').submit()">
                    <i class="bi bi-trash3-fill me-1"></i>Hapus Semua
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Modal Import CSV ─────────────────────────────────────────────────────── -->
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
                    <div class="alert alert-info py-2 mb-3" style="font-size:.82rem;">
                        <strong>Format kolom CSV (urut):</strong><br>
                        <code>Email, SLS_Code, OPEN, DRAFT, SUBMITTED_BY_PENCACAH, APPROVED_BY_PENGAWAS, REJECTED_BY_PENGAWAS, REVOKED_BY_PENGAWAS, EDITED_BY_PENGAWAS, EDITED_BY_ADMIN_KABUPATEN</code>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih File CSV <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv,text/csv" required>
                    </div>
                    <div class="mb-0">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="overwrite" value="1" id="chkOverwrite">
                            <label class="form-check-label small" for="chkOverwrite">
                                <strong>Reset — hapus semua data lama sebelum import</strong><br>
                                <span class="text-muted">Jika <strong>tidak</strong> dicentang (default): data existing di-update, baris baru di-insert (aman untuk update berkala). Jika dicentang: semua data dihapus dulu lalu seluruh baris dari file dimasukkan.</span>
                            </label>
                        </div>
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
const kecNama = <?= json_encode($kecNama) ?>;

function updateKecPreview(val) {
    const code = val.substring(4, 7);
    const el   = document.getElementById('kecPreview');
    if (code && kecNama[code]) {
        el.textContent = 'Kecamatan: ' + code + ' — ' + kecNama[code];
        el.style.color = '#f79039';
    } else {
        el.textContent = code ? 'Kode kecamatan: ' + code + ' (tidak dikenal)' : '';
        el.style.color = '#6b7280';
    }
}

function openAddModal() {
    document.getElementById('formAction').value = 'add';
    document.getElementById('formId').value     = '';
    document.getElementById('formEmail').value  = '';
    document.getElementById('formSLS').value    = '';
    document.getElementById('formOpen').value   = '0';
    document.getElementById('formDraft').value  = '0';
    document.getElementById('formSubP').value   = '0';
    document.getElementById('formSubR').value   = '0';
    document.getElementById('formRej').value    = '0';
    document.getElementById('formAppr').value   = '0';
    document.getElementById('formRev').value    = '0';
    document.getElementById('formEP').value     = '0';
    document.getElementById('formEA').value     = '0';
    document.getElementById('kecPreview').textContent = '';
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-plus-lg me-2"></i>Tambah Data';
    document.getElementById('submitBtn').innerHTML  = '<i class="bi bi-save-fill me-1"></i>Simpan';
    new bootstrap.Modal(document.getElementById('dataModal')).show();
}

function openEditModal(r) {
    document.getElementById('formAction').value = 'edit';
    document.getElementById('formId').value     = r.id;
    document.getElementById('formEmail').value  = r.email || '';
    document.getElementById('formSLS').value    = r.sls_code || '';
    document.getElementById('formOpen').value   = r.open_count || 0;
    document.getElementById('formDraft').value  = r.draft || 0;
    document.getElementById('formSubP').value   = r.submitted_by_pencacah || 0;
    document.getElementById('formSubR').value   = r.submitted_respondent || 0;
    document.getElementById('formRej').value    = r.rejected || 0;
    document.getElementById('formAppr').value   = r.approved || 0;
    document.getElementById('formRev').value    = r.revoke || 0;
    document.getElementById('formEP').value     = r.edited_by_pengawas || 0;
    document.getElementById('formEA').value     = r.edited_by_admin_kabupaten || 0;
    updateKecPreview(r.sls_code || '');
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil-fill me-2"></i>Edit Data';
    document.getElementById('submitBtn').innerHTML  = '<i class="bi bi-save-fill me-1"></i>Perbarui';
    new bootstrap.Modal(document.getElementById('dataModal')).show();
}

function confirmDelete(id, email) {
    document.getElementById('deleteId').value      = id;
    document.getElementById('deleteEmail').textContent = email;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

function confirmDeleteAll() {
    new bootstrap.Modal(document.getElementById('deleteAllModal')).show();
}

function downloadTemplate() {
    const rows = [
        ['Email','SLS_Code','OPEN','DRAFT','SUBMITTED_BY_PENCACAH','APPROVED_BY_PENGAWAS','REJECTED_BY_PENGAWAS','REVOKED_BY_PENGAWAS','EDITED_BY_PENGAWAS','EDITED_BY_ADMIN_KABUPATEN'],
        ['"contoh@gmail.com"','"7206050007000100"','50','5','10','0','0','0','0','0'],
        ['"contoh@gmail.com"','"7206050007000200"','40','3','8','0','0','0','0','0'],
    ];
    const csv  = rows.map(r => r.join(',')).join('\n');
    const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
    const a    = document.createElement('a');
    a.href     = URL.createObjectURL(blob);
    a.download = 'template_fasih_progress.csv';
    a.click();
}
</script>
</body>
</html>
