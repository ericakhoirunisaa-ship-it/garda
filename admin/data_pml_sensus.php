<?php
require_once 'config.php';
requireLogin();
requireSuperAdmin();

$conn->query("CREATE TABLE IF NOT EXISTS sensus_pml (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    nama VARCHAR(200) NOT NULL DEFAULT '',
    kec_code VARCHAR(5) NOT NULL DEFAULT '',
    pending INT DEFAULT 0,
    approved INT DEFAULT 0,
    rejected INT DEFAULT 0,
    `revoke` INT DEFAULT 0,
    edited INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pml_email_kec (email, kec_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$kecNama = [
    '010' => 'Dampal Selatan', '020' => 'Dampal Utara',
    '030' => 'Dondo',          '031' => 'Ogodeide',
    '032' => 'Basidondo',      '040' => 'Baolan',
    '041' => 'Lampasio',       '050' => 'Galang',
    '060' => 'Tolitoli Utara', '061' => 'Dako Pemean',
];

// ── Export CSV ────────────────────────────────────────────────────────────────
if (($_GET['action'] ?? '') === 'export') {
    $all = $conn->query("SELECT * FROM sensus_pml ORDER BY kec_code, nama, email")->fetch_all(MYSQLI_ASSOC);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="data_pml_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Email','Nama','Kec_Code','Pending','Approved','Rejected','Revoke','Edited']);
    foreach ($all as $row) {
        fputcsv($out, [$row['email'], $row['nama'], $row['kec_code'],
            $row['pending'], $row['approved'], $row['rejected'], $row['revoke'], $row['edited']]);
    }
    fclose($out);
    exit;
}

// ── POST handlers ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $e = fn($v) => $conn->real_escape_string(trim((string)($v ?? '')));

    $redir_msg  = '';
    $redir_type = 'success';

    if ($action === 'add') {
        $email   = $e($_POST['email'] ?? '');
        $nama    = $e($_POST['nama'] ?? '');
        $kec     = $e($_POST['kec_code'] ?? '');
        $pending  = (int)($_POST['pending'] ?? 0);
        $approved = (int)($_POST['approved'] ?? 0);
        $rejected = (int)($_POST['rejected'] ?? 0);
        $revoke   = (int)($_POST['revoke'] ?? 0);
        $edited   = (int)($_POST['edited'] ?? 0);

        if (!$email || !$kec) {
            $redir_msg = 'Email dan Kecamatan wajib diisi.'; $redir_type = 'danger';
        } else {
            $conn->query("INSERT INTO sensus_pml
                (email, nama, kec_code, pending, approved, rejected, `revoke`, edited)
                VALUES ('$email','$nama','$kec',$pending,$approved,$rejected,$revoke,$edited)");
            $redir_msg = $conn->affected_rows ? 'Data PML berhasil disimpan.' : ('Gagal: ' . $conn->error);
            if (!$conn->affected_rows) $redir_type = 'danger';
        }
    }
    elseif ($action === 'edit') {
        $id      = (int)($_POST['id'] ?? 0);
        $email   = $e($_POST['email'] ?? '');
        $nama    = $e($_POST['nama'] ?? '');
        $kec     = $e($_POST['kec_code'] ?? '');
        $pending  = (int)($_POST['pending'] ?? 0);
        $approved = (int)($_POST['approved'] ?? 0);
        $rejected = (int)($_POST['rejected'] ?? 0);
        $revoke   = (int)($_POST['revoke'] ?? 0);
        $edited   = (int)($_POST['edited'] ?? 0);

        if ($id && $email && $kec) {
            $conn->query("UPDATE sensus_pml SET
                email='$email', nama='$nama', kec_code='$kec',
                pending=$pending, approved=$approved, rejected=$rejected,
                `revoke`=$revoke, edited=$edited
                WHERE id=$id");
            $redir_msg = 'Data PML berhasil diperbarui.';
        } else {
            $redir_msg = 'Data tidak valid.'; $redir_type = 'danger';
        }
    }
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $conn->query("DELETE FROM sensus_pml WHERE id=$id");
            $redir_msg = 'Data PML berhasil dihapus.';
        }
    }
    elseif ($action === 'delete_all') {
        $conn->query("TRUNCATE TABLE sensus_pml");
        $redir_msg = 'Semua data PML berhasil dihapus.';
    }
    elseif ($action === 'import_csv') {
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $handle     = fopen($_FILES['csv_file']['tmp_name'], 'r');
            $header     = fgetcsv($handle);
            $upserted   = 0; $skipped = 0;
            $clearFirst = ($_POST['overwrite'] ?? '0') === '1';

            if ($clearFirst) {
                $conn->query("TRUNCATE TABLE sensus_pml");
            }

            // Format: Email, Nama, Kec_Code, Pending, Approved, Rejected, Revoke, Edited
            while (($row = fgetcsv($handle)) !== false) {
                $row = array_pad($row, 8, 0);
                [$remail, $rnama, $rkec, $rpend, $rappr, $rrej, $rrev, $redit] = $row;
                $remail = strtolower(trim(trim($remail), '"'));
                $rnama  = trim(trim($rnama), '"');
                $rkec   = trim(trim($rkec), '"');
                if (!$remail || !$rkec) { $skipped++; continue; }

                $em = $conn->real_escape_string($remail);
                $nm = $conn->real_escape_string($rnama);
                $kc = $conn->real_escape_string($rkec);
                $pd = (int)$rpend;
                $ap = (int)$rappr;
                $rj = (int)$rrej;
                $rv = (int)$rrev;
                $ed = (int)$redit;

                $r = $conn->query("INSERT INTO sensus_pml
                    (email, nama, kec_code, pending, approved, rejected, `revoke`, edited)
                    VALUES ('$em','$nm','$kc',$pd,$ap,$rj,$rv,$ed)
                    ON DUPLICATE KEY UPDATE
                    nama='$nm', pending=$pd, approved=$ap, rejected=$rj, `revoke`=$rv, edited=$ed");
                if ($r) $upserted++; else $skipped++;
            }
            fclose($handle);
            $redir_msg = "Import selesai: $upserted baris diproses (insert/update)" . ($skipped ? ", $skipped dilewati (email/kec kosong)" : '') . '.';
        } else {
            $redir_msg = 'Gagal membaca file CSV.'; $redir_type = 'danger';
        }
    }

    $qs = http_build_query(['msg' => $redir_msg, 'msg_type' => $redir_type,
        'q' => $_GET['q'] ?? '', 'kec' => $_GET['kec'] ?? '', 'page' => $_GET['page'] ?? 1]);
    header("Location: data_pml_sensus.php?$qs");
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
if ($q)         $conds[] = "(email LIKE '%$q%' OR nama LIKE '%$q%')";
if ($filterKec) $conds[] = "kec_code='$filterKec'";
$whereSQL = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

$stats = $conn->query("SELECT
    COUNT(*) AS total,
    COALESCE(SUM(pending),0) AS total_pending,
    COALESCE(SUM(approved),0) AS total_approved,
    COALESCE(SUM(rejected),0) AS total_rejected,
    COALESCE(SUM(`revoke`),0) AS total_revoke,
    COALESCE(SUM(edited),0) AS total_edited
FROM sensus_pml")->fetch_assoc();

$totalRec   = (int)$conn->query("SELECT COUNT(*) c FROM sensus_pml $whereSQL")->fetch_assoc()['c'];
$totalPages = max(1, (int)ceil($totalRec / $perPage));
$offset     = ($page - 1) * $perPage;
$records    = $conn->query("SELECT * FROM sensus_pml $whereSQL ORDER BY kec_code, nama, email LIMIT $perPage OFFSET $offset")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data PML Sensus Ekonomi — SEMANIS 2026</title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        .stat-icon { width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; }
        .section-head { font-weight:700; font-size:.7rem; text-transform:uppercase; letter-spacing:.08em; color:#94a3b8; margin-bottom:14px; }
        .table-hover tbody tr:hover { background:#fff8f0; }
        .act-btn { padding:3px 8px; font-size:.78rem; border-radius:6px; }
        .badge-pending  { background:#dbeafe; color:#1d4ed8; }
        .badge-approved { background:#dcfce7; color:#166534; }
        .badge-rej      { background:#fee2e2; color:#991b1b; }
        .badge-rev      { background:#ffedd5; color:#c2410c; }
        .badge-edited   { background:#d1fae5; color:#065f46; }
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
                    <i class="bi bi-person-check-fill me-2" style="color:#f79039"></i>Data PML Sensus Ekonomi
                </div>
                <div class="topbar-sub">Kelola Data Progress Pengawas Lapangan (PML) SE2026</div>
            </div>
            <div class="topbar-right">
                <a href="../monitoring_sensus.html" class="btn btn-sm btn-outline-secondary">
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
                            <div><div class="fs-2 fw-bold lh-1"><?= number_format((int)$stats['total_approved']) ?></div><div class="text-muted small">Approved</div></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-hourglass-split"></i></div>
                            <div><div class="fs-2 fw-bold lh-1"><?= number_format((int)$stats['total_pending']) ?></div><div class="text-muted small">Pending</div></div>
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
                            <input type="text" name="q" class="form-control border-start-0" placeholder="Cari nama, email…" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
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
                        <a href="data_pml_sensus.php" class="btn btn-sm btn-outline-secondary">Reset</a>
                        <?php endif; ?>
                    </form>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-sm text-white" style="background:#f79039" onclick="openAddModal()">
                            <i class="bi bi-plus-lg me-1"></i>Tambah
                        </button>
                        <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class="bi bi-upload me-1"></i>Import CSV
                        </button>
                        <a href="?action=export" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-download me-1"></i>Export CSV
                        </a>
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
                        <i class="bi bi-table me-1"></i>Data PML Sensus Ekonomi
                        <span class="badge bg-secondary ms-1 fw-normal"><?= $totalRec ?> record</span>
                    </div>
                    <small class="text-muted"><?= date('d M Y, H:i') ?> WITA</small>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:.83rem;">
                        <thead style="background:#f79039; color:#fff;">
                            <tr>
                                <th class="ps-4" style="width:50px">#</th>
                                <th>Email PML</th>
                                <th>Nama</th>
                                <th>Kecamatan</th>
                                <th class="text-center">Pending</th>
                                <th class="text-center">Approved</th>
                                <th class="text-center">Rejected</th>
                                <th class="text-center">Revoke</th>
                                <th class="text-center">Edited</th>
                                <th class="text-center pe-4" style="width:90px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($records)): ?>
                            <tr><td colspan="10" class="text-center text-muted py-5">
                                Belum ada data. Gunakan tombol <strong>Tambah</strong> untuk menambahkan data PML.
                            </td></tr>
                        <?php else: ?>
                        <?php foreach ($records as $i => $r): ?>
                            <tr>
                                <td class="ps-4 text-muted"><?= $offset + $i + 1 ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($r['email']) ?></td>
                                <td><?= htmlspecialchars($r['nama']) ?></td>
                                <td>
                                    <span class="badge" style="background:#fff3e0;color:#f79039"><?= htmlspecialchars($r['kec_code']) ?></span>
                                    <?= htmlspecialchars($kecNama[$r['kec_code']] ?? '') ?>
                                </td>
                                <td class="text-center"><span class="badge badge-pending"><?= $r['pending'] ?></span></td>
                                <td class="text-center"><span class="badge badge-approved"><?= $r['approved'] ?></span></td>
                                <td class="text-center"><span class="badge badge-rej"><?= $r['rejected'] ?></span></td>
                                <td class="text-center"><span class="badge badge-rev"><?= $r['revoke'] ?></span></td>
                                <td class="text-center"><span class="badge badge-edited"><?= $r['edited'] ?></span></td>
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
                    <h5 class="modal-title" id="modalTitle"><i class="bi bi-plus-lg me-2"></i>Tambah Data PML</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email PML <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="formEmail" class="form-control" required placeholder="contoh@gmail.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nama PML</label>
                            <input type="text" name="nama" id="formNama" class="form-control" placeholder="Nama lengkap PML">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kecamatan <span class="text-danger">*</span></label>
                            <select name="kec_code" id="formKec" class="form-select" required>
                                <option value="">-- Pilih Kecamatan --</option>
                                <?php foreach ($kecNama as $kd => $nm): ?>
                                <option value="<?= $kd ?>"><?= $kd ?> — <?= $nm ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Pending</label>
                            <input type="number" name="pending" id="formPending" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Approved</label>
                            <input type="number" name="approved" id="formApproved" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Rejected</label>
                            <input type="number" name="rejected" id="formRejected" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Revoke</label>
                            <input type="number" name="revoke" id="formRevoke" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Edited</label>
                            <input type="number" name="edited" id="formEdited" class="form-control" min="0" value="0">
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
                Yakin menghapus data PML <strong id="deleteEmail"></strong>? Tindakan ini tidak dapat dibatalkan.
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
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Hapus Semua Data PML</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger mb-0">
                    <strong>Peringatan!</strong> Semua <?= number_format((int)$stats['total']) ?> record data PML akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.
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
                    <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Import CSV Data PML</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3" style="font-size:.82rem;">
                        <strong>Format kolom CSV (urut):</strong><br>
                        <code>Email, Nama, Kec_Code, Pending, Approved, Rejected, Revoke, Edited</code>
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
function openAddModal() {
    document.getElementById('formAction').value   = 'add';
    document.getElementById('formId').value       = '';
    document.getElementById('formEmail').value    = '';
    document.getElementById('formNama').value     = '';
    document.getElementById('formKec').value      = '';
    document.getElementById('formPending').value  = '0';
    document.getElementById('formApproved').value = '0';
    document.getElementById('formRejected').value = '0';
    document.getElementById('formRevoke').value   = '0';
    document.getElementById('formEdited').value   = '0';
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-plus-lg me-2"></i>Tambah Data PML';
    document.getElementById('submitBtn').innerHTML  = '<i class="bi bi-save-fill me-1"></i>Simpan';
    new bootstrap.Modal(document.getElementById('dataModal')).show();
}

function openEditModal(r) {
    document.getElementById('formAction').value   = 'edit';
    document.getElementById('formId').value       = r.id;
    document.getElementById('formEmail').value    = r.email || '';
    document.getElementById('formNama').value     = r.nama || '';
    document.getElementById('formKec').value      = r.kec_code || '';
    document.getElementById('formPending').value  = r.pending || 0;
    document.getElementById('formApproved').value = r.approved || 0;
    document.getElementById('formRejected').value = r.rejected || 0;
    document.getElementById('formRevoke').value   = r.revoke || 0;
    document.getElementById('formEdited').value   = r.edited || 0;
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil-fill me-2"></i>Edit Data PML';
    document.getElementById('submitBtn').innerHTML  = '<i class="bi bi-save-fill me-1"></i>Perbarui';
    new bootstrap.Modal(document.getElementById('dataModal')).show();
}

function confirmDelete(id, email) {
    document.getElementById('deleteId').value           = id;
    document.getElementById('deleteEmail').textContent  = email;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

function confirmDeleteAll() {
    new bootstrap.Modal(document.getElementById('deleteAllModal')).show();
}

function downloadTemplate() {
    const rows = [
        ['Email','Nama','Kec_Code','Pending','Approved','Rejected','Revoke','Edited'],
        ['"contoh@gmail.com"','"Nama PML"','"040"','10','5','2','1','0'],
        ['"contoh@gmail.com"','"Nama PML"','"050"','8','3','1','0','0'],
    ];
    const csv  = rows.map(r => r.join(',')).join('\n');
    const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
    const a    = document.createElement('a');
    a.href     = URL.createObjectURL(blob);
    a.download = 'template_pml.csv';
    a.click();
}
</script>
</body>
</html>
