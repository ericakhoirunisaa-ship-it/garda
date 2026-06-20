<?php
require_once 'config.php';
requireLogin();

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isSuperAdmin()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'tambah') {
        $judul     = trim($_POST['judul'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $petunjuk  = trim($_POST['petunjuk'] ?? '');
        $status    = in_array($_POST['status'] ?? '', ['aktif','nonaktif']) ? $_POST['status'] : 'aktif';
        $durasi    = max(0, (int)($_POST['durasi_menit'] ?? 0));
        if ($judul) {
            $adminId = (int)($_SESSION['admin_id'] ?? 0);
            $stmt = $conn->prepare("INSERT INTO ujian (judul, deskripsi, durasi_menit, petunjuk, status, dibuat_oleh) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("ssissi", $judul, $deskripsi, $durasi, $petunjuk, $status, $adminId);
            $stmt->execute();
            $success = "Ujian berhasil ditambahkan.";
        } else {
            $error = "Judul ujian wajib diisi.";
        }

    } elseif ($action === 'edit') {
        $id        = (int)($_POST['ujian_id'] ?? 0);
        $judul     = trim($_POST['judul'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $petunjuk  = trim($_POST['petunjuk'] ?? '');
        $status    = in_array($_POST['status'] ?? '', ['aktif','nonaktif']) ? $_POST['status'] : 'aktif';
        $durasi    = max(0, (int)($_POST['durasi_menit'] ?? 0));
        if ($id && $judul) {
            $stmt = $conn->prepare("UPDATE ujian SET judul=?, deskripsi=?, durasi_menit=?, petunjuk=?, status=? WHERE id=?");
            $stmt->bind_param("ssissi", $judul, $deskripsi, $durasi, $petunjuk, $status, $id);
            $stmt->execute();
            $success = "Ujian berhasil diperbarui.";
        } else {
            $error = "Data tidak valid.";
        }

    } elseif ($action === 'toggle') {
        $id = (int)($_POST['ujian_id'] ?? 0);
        if ($id) {
            $row = $conn->query("SELECT status FROM ujian WHERE id=$id LIMIT 1")->fetch_assoc();
            if ($row) {
                $newStatus = $row['status'] === 'aktif' ? 'nonaktif' : 'aktif';
                $conn->query("UPDATE ujian SET status='$newStatus' WHERE id=$id");
                $success = "Status ujian diubah ke <strong>$newStatus</strong>.";
            }
        }

    } elseif ($action === 'hapus') {
        $id = (int)($_POST['ujian_id'] ?? 0);
        if ($id) {
            $cek = $conn->query("SELECT COUNT(*) AS c FROM ujian_pengerjaan WHERE ujian_id=$id")->fetch_assoc();
            if ((int)$cek['c'] > 0) {
                $error = "Ujian tidak bisa dihapus karena sudah ada {$cek['c']} pengerjaan.";
            } else {
                $soals = $conn->query("SELECT id FROM ujian_soal WHERE ujian_id=$id")->fetch_all(MYSQLI_ASSOC);
                foreach ($soals as $s) {
                    $conn->query("DELETE FROM ujian_opsi WHERE soal_id=" . (int)$s['id']);
                }
                $conn->query("DELETE FROM ujian_soal WHERE ujian_id=$id");
                $conn->query("DELETE FROM ujian WHERE id=$id");
                $success = "Ujian berhasil dihapus.";
            }
        }
    }
}

$ujianList = $conn->query("
    SELECT u.*,
           (SELECT COUNT(*) FROM ujian_soal WHERE ujian_id=u.id) AS jml_soal,
           (SELECT COUNT(*) FROM ujian_pengerjaan WHERE ujian_id=u.id) AS jml_pengerjaan
    FROM ujian u
    ORDER BY u.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Ujian — Admin</title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/font/bootstrap-icons.css">
</head>
<body>
<?php include '_sidebar.php'; ?>
<div class="main-content">
    <header class="topbar">
        <button class="topbar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
        <div>
            <div class="topbar-title">
                <i class="bi bi-journal-check me-2" style="color:#f79039"></i>Manajemen Ujian
            </div>
            <div class="topbar-sub"><?= count($ujianList) ?> ujian terdaftar &nbsp;·&nbsp;
                <a href="../ujian.php" target="_blank" class="text-decoration-none" style="color:#f79039">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Lihat Halaman Publik
                </a>
            </div>
        </div>
        <div class="topbar-right d-flex gap-2">
            <?php if (isSuperAdmin()): ?>
            <button class="btn btn-sm btn-warning fw-bold" data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="bi bi-plus-lg me-1"></i>Tambah Ujian
            </button>
            <?php endif; ?>
            <a href="ujian_peserta.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-people me-1"></i>Peserta
            </a>
            <a href="ujian_hasil.php" class="btn btn-sm btn-outline-info">
                <i class="bi bi-clipboard2-data me-1"></i>Hasil Ujian
            </a>
        </div>
    </header>

    <div class="page-body">
        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show py-2 mb-3">
            <i class="bi bi-check-circle me-2"></i><?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php elseif ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2 mb-3">
            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (empty($ujianList)): ?>
        <div class="card stat-card text-center py-5">
            <div class="text-muted">
                <i class="bi bi-journal-x fs-2 d-block mb-2"></i>
                Belum ada ujian.
                <?php if (isSuperAdmin()): ?>Klik <strong>Tambah Ujian</strong> untuk membuat ujian baru.<?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="row g-3">
        <?php foreach ($ujianList as $u): ?>
        <div class="col-md-6 col-xl-4">
            <div class="card stat-card h-100" style="border-left:4px solid <?= $u['status']==='aktif' ? '#f79039' : '#9ca3af' ?>">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge <?= $u['status']==='aktif' ? 'text-bg-warning' : 'text-bg-secondary' ?>">
                            <?= $u['status'] === 'aktif' ? 'Aktif' : 'Nonaktif' ?>
                        </span>
                        <small class="text-muted"><?= date('d/m/Y', strtotime($u['created_at'])) ?></small>
                    </div>
                    <h6 class="fw-bold mb-1"><?= htmlspecialchars($u['judul']) ?></h6>
                    <?php if ($u['deskripsi']): ?>
                    <p class="small text-muted mb-2" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                        <?= htmlspecialchars($u['deskripsi']) ?>
                    </p>
                    <?php endif; ?>
                    <div class="d-flex gap-4 my-3">
                        <div>
                            <div class="fw-bold fs-5" style="color:#f79039"><?= $u['jml_soal'] ?></div>
                            <div class="small text-muted">Soal</div>
                        </div>
                        <div>
                            <div class="fw-bold fs-5 text-primary"><?= $u['jml_pengerjaan'] ?></div>
                            <div class="small text-muted">Pengerjaan</div>
                        </div>
                        <div>
                            <div class="fw-bold fs-5 text-secondary">
                                <?= $u['durasi_menit'] > 0 ? $u['durasi_menit'] . ' mnt' : '∞' ?>
                            </div>
                            <div class="small text-muted">Durasi</div>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap mt-auto">
                        <a href="ujian_soal.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-warning fw-bold">
                            <i class="bi bi-list-check me-1"></i>Kelola Soal
                        </a>
                        <?php if (isSuperAdmin()): ?>
                        <button class="btn btn-sm btn-outline-secondary"
                                onclick="editUjian(<?= htmlspecialchars(json_encode($u)) ?>)"
                                title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="ujian_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-sm <?= $u['status']==='aktif' ? 'btn-outline-secondary' : 'btn-outline-success' ?>"
                                    title="<?= $u['status']==='aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                <i class="bi bi-<?= $u['status']==='aktif' ? 'pause' : 'play' ?>-fill"></i>
                            </button>
                        </form>
                        <form method="POST" style="display:inline"
                              onsubmit="return confirm('Hapus ujian ini beserta semua soalnya?');">
                            <input type="hidden" name="action" value="hapus">
                            <input type="hidden" name="ujian_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:#f79039; color:#fff;">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Tambah Ujian</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="tambah">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Judul Ujian <span class="text-danger">*</span></label>
                        <input type="text" name="judul" class="form-control"
                               placeholder="Contoh: Ujian Kompetensi Mitra SE2026" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="2"
                                  placeholder="Deskripsi singkat ujian (opsional)"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Petunjuk Pengerjaan</label>
                        <textarea name="petunjuk" class="form-control" rows="4"
                                  placeholder="Tulis petunjuk pengerjaan yang akan ditampilkan kepada peserta (opsional)&#10;Contoh:&#10;1. Baca setiap soal dengan teliti.&#10;2. Pilih satu jawaban yang paling tepat."></textarea>
                        <div class="form-text">Ditampilkan di halaman ujian sebelum soal dimulai.</div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Durasi (menit)</label>
                            <input type="number" name="durasi_menit" class="form-control"
                                   min="0" max="999" value="0" placeholder="0 = tidak terbatas">
                            <div class="form-text">0 = waktu tidak dibatasi</div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning fw-bold">
                        <i class="bi bi-plus-lg me-1"></i>Tambah
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:#f79039; color:#fff;">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Ujian</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="ujian_id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Judul Ujian <span class="text-danger">*</span></label>
                        <input type="text" name="judul" id="edit_judul" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi</label>
                        <textarea name="deskripsi" id="edit_deskripsi" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Petunjuk Pengerjaan</label>
                        <textarea name="petunjuk" id="edit_petunjuk" class="form-control" rows="4"
                                  placeholder="Tulis petunjuk pengerjaan (opsional)"></textarea>
                        <div class="form-text">Ditampilkan di halaman ujian sebelum soal dimulai.</div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Durasi (menit)</label>
                            <input type="number" name="durasi_menit" id="edit_durasi" class="form-control"
                                   min="0" max="999" placeholder="0 = tidak terbatas">
                            <div class="form-text">0 = waktu tidak dibatasi</div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="aktif">Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning fw-bold">
                        <i class="bi bi-save me-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
function editUjian(u) {
    document.getElementById('edit_id').value = u.id;
    document.getElementById('edit_judul').value = u.judul;
    document.getElementById('edit_deskripsi').value = u.deskripsi || '';
    document.getElementById('edit_petunjuk').value = u.petunjuk || '';
    document.getElementById('edit_durasi').value = u.durasi_menit || 0;
    document.getElementById('edit_status').value = u.status;
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}
</script>
</body>
</html>
