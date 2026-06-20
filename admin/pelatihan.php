<?php
require_once 'config.php';
requireLogin();

$success = '';
$error   = '';

// ─── POST Handlers (superadmin only) ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isSuperAdmin()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'buat') {
        $judul   = trim($_POST['judul'] ?? '');
        $jenis   = in_array($_POST['jenis'] ?? '', ['pretest','posttest','ujian']) ? $_POST['jenis'] : 'ujian';
        $desk    = trim($_POST['deskripsi'] ?? '');
        $durasi  = max(1, (int)($_POST['durasi_menit'] ?? 60));
        if ($judul) {
            $stmt = $conn->prepare("INSERT INTO pelatihan (judul, jenis, deskripsi, durasi_menit, dibuat_oleh) VALUES (?,?,?,?,?)");
            $stmt->bind_param("ssiii", $judul, $jenis, $desk, $durasi, $_SESSION['admin_id']);
            $stmt->execute();
            $success = "Pelatihan \"" . htmlspecialchars($judul) . "\" berhasil dibuat.";
        } else {
            $error = "Judul pelatihan wajib diisi.";
        }

    } elseif ($action === 'edit') {
        $id     = (int)($_POST['id'] ?? 0);
        $judul  = trim($_POST['judul'] ?? '');
        $jenis  = in_array($_POST['jenis'] ?? '', ['pretest','posttest','ujian']) ? $_POST['jenis'] : 'ujian';
        $desk   = trim($_POST['deskripsi'] ?? '');
        $durasi = max(1, (int)($_POST['durasi_menit'] ?? 60));
        if ($id && $judul) {
            $stmt = $conn->prepare("UPDATE pelatihan SET judul=?, jenis=?, deskripsi=?, durasi_menit=? WHERE id=?");
            $stmt->bind_param("ssiii", $judul, $jenis, $desk, $durasi, $id);
            $stmt->execute();
            $success = "Pelatihan berhasil diperbarui.";
        } else {
            $error = "Data tidak valid.";
        }

    } elseif ($action === 'status') {
        $id     = (int)($_POST['id'] ?? 0);
        $status = in_array($_POST['status'] ?? '', ['draft','aktif','selesai']) ? $_POST['status'] : 'draft';
        if ($id) {
            $sid = $conn->real_escape_string($status);
            $conn->query("UPDATE pelatihan SET status='$sid' WHERE id=$id");
            $success = "Status pelatihan berhasil diubah ke <strong>$sid</strong>.";
        }

    } elseif ($action === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $cek = $conn->query("SELECT COUNT(*) AS c FROM pelatihan_hasil WHERE pelatihan_id=$id")->fetch_assoc();
            if ($cek['c'] > 0) {
                $error = "Tidak bisa menghapus — sudah ada " . $cek['c'] . " peserta yang mengerjakan pelatihan ini.";
            } else {
                $soalIds = $conn->query("SELECT id FROM pelatihan_soal WHERE pelatihan_id=$id")->fetch_all(MYSQLI_ASSOC);
                foreach ($soalIds as $s) {
                    $conn->query("DELETE FROM pelatihan_opsi WHERE soal_id=" . (int)$s['id']);
                }
                $conn->query("DELETE FROM pelatihan_soal WHERE pelatihan_id=$id");
                $conn->query("DELETE FROM pelatihan WHERE id=$id");
                $success = "Pelatihan berhasil dihapus.";
            }
        }
    }
}

// ─── Query ────────────────────────────────────────────────────────────────────
$rows = $conn->query("
    SELECT p.*,
        a.nama AS dibuat_nama,
        COALESCE((SELECT COUNT(*) FROM pelatihan_soal ps WHERE ps.pelatihan_id = p.id),0) AS jumlah_soal,
        COALESCE((SELECT COUNT(*) FROM pelatihan_hasil ph WHERE ph.pelatihan_id = p.id AND ph.status='selesai'),0) AS jumlah_selesai
    FROM pelatihan p
    LEFT JOIN admin_seleksi a ON a.id = p.dibuat_oleh
    ORDER BY p.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$total   = count($rows);
$aktif   = count(array_filter($rows, fn($r) => $r['status'] === 'aktif'));
$selesai = count(array_filter($rows, fn($r) => $r['status'] === 'selesai'));
$draft   = count(array_filter($rows, fn($r) => $r['status'] === 'draft'));

$jenisLabel = ['pretest' => 'Pretest', 'posttest' => 'Posttest', 'ujian' => 'Ujian'];
$jenisBadge = ['pretest' => 'bg-info', 'posttest' => 'bg-success', 'ujian' => 'bg-warning text-dark'];
$statusBadge = ['draft' => 'bg-secondary', 'aktif' => 'bg-success', 'selesai' => 'bg-dark'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pelatihan Petugas — SEMANIS 2026</title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/font/bootstrap-icons.css">
</head>
<body>
<?php include '_sidebar.php'; ?>
<div class="main-content">
    <!-- Topbar -->
    <header class="topbar">
        <button class="topbar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
        <div>
            <div class="topbar-title"><i class="bi bi-journal-check me-2" style="color:#f79039"></i>Pelatihan &amp; Ujian Petugas</div>
            <div class="topbar-sub">Sensus Ekonomi 2026 — BPS Kab. Toli-Toli</div>
        </div>
        <div class="topbar-right">
            <?php if (isSuperAdmin()): ?>
            <button class="btn btn-sm btn-warning fw-bold" data-bs-toggle="modal" data-bs-target="#modalBuat">
                <i class="bi bi-plus-lg me-1"></i>Buat Pelatihan
            </button>
            <?php endif; ?>
        </div>
    </header>

    <div class="page-body">
        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show py-2 mb-3" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php elseif ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2 mb-3" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card stat-card text-center py-3">
                    <div class="fw-bold fs-3 text-warning"><?= $total ?></div>
                    <div class="text-muted small">Total Pelatihan</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card stat-card text-center py-3">
                    <div class="fw-bold fs-3 text-success"><?= $aktif ?></div>
                    <div class="text-muted small">Sedang Aktif</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card stat-card text-center py-3">
                    <div class="fw-bold fs-3 text-dark"><?= $selesai ?></div>
                    <div class="text-muted small">Selesai</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card stat-card text-center py-3">
                    <div class="fw-bold fs-3 text-secondary"><?= $draft ?></div>
                    <div class="text-muted small">Draft</div>
                </div>
            </div>
        </div>

        <!-- Link untuk peserta -->
        <?php if ($aktif > 0): ?>
        <div class="alert alert-info py-2 mb-3 d-flex align-items-center gap-2">
            <i class="bi bi-link-45deg fs-5"></i>
            <div>
                <strong>Link ujian untuk peserta:</strong>
                <code class="ms-2"><?= rtrim((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname(dirname($_SERVER['REQUEST_URI'])), '/') ?>/forms/ujian.php</code>
            </div>
        </div>
        <?php endif; ?>

        <!-- Table -->
        <div class="card stat-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">#</th>
                                <th>Judul</th>
                                <th>Jenis</th>
                                <th class="text-center">Soal</th>
                                <th class="text-center">Durasi</th>
                                <th class="text-center">Peserta Selesai</th>
                                <th class="text-center">Status</th>
                                <th class="text-center pe-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">Belum ada pelatihan. <?= isSuperAdmin() ? 'Klik <strong>Buat Pelatihan</strong> untuk memulai.' : '' ?></td></tr>
                        <?php else: foreach ($rows as $i => $r): ?>
                            <tr>
                                <td class="ps-3 text-muted small"><?= $i+1 ?></td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($r['judul']) ?></div>
                                    <?php if ($r['deskripsi']): ?>
                                    <div class="text-muted small text-truncate" style="max-width:220px"><?= htmlspecialchars($r['deskripsi']) ?></div>
                                    <?php endif; ?>
                                    <div class="text-muted small">oleh <?= htmlspecialchars($r['dibuat_nama'] ?? '—') ?></div>
                                </td>
                                <td><span class="badge <?= $jenisBadge[$r['jenis']] ?>"><?= $jenisLabel[$r['jenis']] ?></span></td>
                                <td class="text-center">
                                    <span class="badge rounded-pill bg-light text-dark border"><?= $r['jumlah_soal'] ?> soal</span>
                                </td>
                                <td class="text-center text-muted small"><?= $r['durasi_menit'] ?> mnt</td>
                                <td class="text-center">
                                    <?php if ($r['jumlah_selesai'] > 0): ?>
                                    <a href="pelatihan_monitoring.php?id=<?= $r['id'] ?>" class="badge bg-primary text-decoration-none">
                                        <?= $r['jumlah_selesai'] ?> peserta
                                    </a>
                                    <?php else: ?>
                                    <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $statusBadge[$r['status']] ?> text-capitalize"><?= $r['status'] ?></span>
                                </td>
                                <td class="text-center pe-3">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="pelatihan_detail.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary" title="Kelola Soal">
                                            <i class="bi bi-list-ul"></i>
                                        </a>
                                        <a href="pelatihan_monitoring.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-info" title="Monitoring">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (isSuperAdmin()): ?>
                                        <button class="btn btn-sm btn-outline-warning" title="Edit"
                                            onclick="editPelatihan(<?= htmlspecialchars(json_encode($r)) ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary" title="Ubah Status"
                                            onclick="ubahStatus(<?= $r['id'] ?>, '<?= $r['status'] ?>', '<?= htmlspecialchars($r['judul']) ?>')">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" title="Hapus"
                                            onclick="hapusPelatihan(<?= $r['id'] ?>, '<?= htmlspecialchars($r['judul']) ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div><!-- /page-body -->
</div><!-- /main-content -->

<!-- ─── Modal Buat Pelatihan ─────────────────────────────────────────────── -->
<div class="modal fade" id="modalBuat" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="buat">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2 text-warning"></i>Buat Pelatihan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Judul Pelatihan <span class="text-danger">*</span></label>
                    <input type="text" name="judul" class="form-control" placeholder="Contoh: Pretest Pelatihan SE2026 Gel.1" required>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Jenis</label>
                        <select name="jenis" class="form-select">
                            <option value="pretest">Pretest</option>
                            <option value="posttest">Posttest</option>
                            <option value="ujian" selected>Ujian</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Durasi (menit)</label>
                        <input type="number" name="durasi_menit" class="form-control" value="60" min="1" max="480">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label fw-semibold">Deskripsi <span class="text-muted">(opsional)</span></label>
                    <textarea name="deskripsi" class="form-control" rows="2" placeholder="Keterangan singkat tentang pelatihan ini..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning fw-bold">Buat Pelatihan</button>
            </div>
        </form>
    </div>
</div>

<!-- ─── Modal Edit Pelatihan ─────────────────────────────────────────────── -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editId">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2 text-warning"></i>Edit Pelatihan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Judul Pelatihan <span class="text-danger">*</span></label>
                    <input type="text" name="judul" id="editJudul" class="form-control" required>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Jenis</label>
                        <select name="jenis" id="editJenis" class="form-select">
                            <option value="pretest">Pretest</option>
                            <option value="posttest">Posttest</option>
                            <option value="ujian">Ujian</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Durasi (menit)</label>
                        <input type="number" name="durasi_menit" id="editDurasi" class="form-control" min="1" max="480">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label fw-semibold">Deskripsi</label>
                    <textarea name="deskripsi" id="editDeskripsi" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning fw-bold">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- ─── Modal Ubah Status ─────────────────────────────────────────────────── -->
<div class="modal fade" id="modalStatus" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="status">
            <input type="hidden" name="id" id="statusId">
            <div class="modal-header">
                <h6 class="modal-title">Ubah Status Pelatihan</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small mb-2">Pelatihan: <strong id="statusJudul"></strong></p>
                <select name="status" id="statusVal" class="form-select">
                    <option value="draft">Draft (belum aktif)</option>
                    <option value="aktif">Aktif (peserta bisa mengerjakan)</option>
                    <option value="selesai">Selesai (ditutup)</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning btn-sm fw-bold">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- ─── Form Hapus (hidden) ─────────────────────────────────────────────── -->
<form id="formHapus" method="POST" style="display:none">
    <input type="hidden" name="action" value="hapus">
    <input type="hidden" name="id" id="hapusId">
</form>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
function editPelatihan(r) {
    document.getElementById('editId').value      = r.id;
    document.getElementById('editJudul').value   = r.judul;
    document.getElementById('editJenis').value   = r.jenis;
    document.getElementById('editDurasi').value  = r.durasi_menit;
    document.getElementById('editDeskripsi').value = r.deskripsi || '';
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}
function ubahStatus(id, current, judul) {
    document.getElementById('statusId').value    = id;
    document.getElementById('statusJudul').textContent = judul;
    document.getElementById('statusVal').value   = current;
    new bootstrap.Modal(document.getElementById('modalStatus')).show();
}
function hapusPelatihan(id, judul) {
    if (!confirm('Hapus pelatihan "' + judul + '"?\n\nHanya bisa dihapus jika belum ada peserta yang mengerjakan.')) return;
    document.getElementById('hapusId').value = id;
    document.getElementById('formHapus').submit();
}
</script>
</body>
</html>
