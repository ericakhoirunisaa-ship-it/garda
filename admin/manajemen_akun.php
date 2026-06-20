<?php
require_once 'config.php';
requireSuperAdmin();

$success = '';
$error   = '';

// Tambah akun
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah') {
    $username = trim($_POST['username'] ?? '');
    $nama     = trim($_POST['nama'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = in_array($_POST['role'] ?? '', ['petugas','superadmin']) ? $_POST['role'] : 'petugas';

    if ($username && $nama && strlen($password) >= 6) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO admin_seleksi (username, password, plain_password, nama, role) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sssss", $username, $hash, $password, $nama, $role);
        try {
            $stmt->execute();
            $success = "Akun '$username' berhasil ditambahkan.";
        } catch (mysqli_sql_exception $e) {
            $error = "Username '$username' sudah digunakan, pilih username lain.";
        }
    } else {
        $error = "Semua field wajib diisi. Password minimal 6 karakter.";
    }
}

// Reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    $id       = (int)$_POST['id'];
    $password = $_POST['new_password'] ?? '';
    if ($id && strlen($password) >= 6) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $plain = $conn->real_escape_string($password);
        $conn->query("UPDATE admin_seleksi SET password='$hash', plain_password='$plain' WHERE id=$id");
        $success = "Password berhasil direset.";
    } else {
        $error = "Password minimal 6 karakter.";
    }
}

// Hapus akun
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'hapus') {
    $id = (int)$_POST['id'];
    if ($id && $id !== (int)$_SESSION['admin_id']) {
        $conn->query("DELETE FROM admin_seleksi WHERE id=$id");
        $success = "Akun berhasil dihapus.";
    }
}

$akun = $conn->query("SELECT id, username, plain_password, nama, role, created_at FROM admin_seleksi ORDER BY role DESC, nama ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Akun — SEMANIS 2026</title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/font/bootstrap-icons.css">
</head>
<body>
<div class="d-flex">
    <?php include '_sidebar.php'; ?>

    <div class="main-content w-100">
        <!-- Topbar -->
        <header class="topbar">
            <button class="topbar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div>
                <div class="topbar-title"><i class="bi bi-people-fill me-2" style="color:#f79039"></i>Manajemen Akun Petugas</div>
                <div class="topbar-sub">Sensus Ekonomi 2026 — BPS Kab. Toli-Toli</div>
            </div>
            <div class="topbar-right">
                <span class="badge rounded-pill text-bg-secondary"><?= count($akun) ?> Akun</span>
            </div>
        </header>

        <div class="page-body">

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2" style="border-radius:10px">
                    <i class="bi bi-check-circle-fill"></i><?= $success ?>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2" style="border-radius:10px">
                    <i class="bi bi-x-circle-fill"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Form Tambah -->
                <div class="col-lg-4">
                    <div class="card stat-card">
                        <div class="card-header border-0 fw-semibold py-3 d-flex align-items-center gap-2"
                             style="background:linear-gradient(135deg,#f79039,#e06820);color:#fff;border-radius:12px 12px 0 0;">
                            <i class="bi bi-person-plus-fill"></i>Tambah Akun Baru
                        </div>
                        <div class="card-body p-4">
                            <form method="POST">
                                <input type="hidden" name="action" value="tambah">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Nama Lengkap</label>
                                    <input type="text" name="nama" class="form-control" placeholder="Nama petugas" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Username</label>
                                    <input type="text" name="username" class="form-control" placeholder="contoh: petugas01" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Password</label>
                                    <input type="password" name="password" class="form-control" placeholder="Min. 6 karakter" required>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-semibold small">Role</label>
                                    <select name="role" class="form-select">
                                        <option value="petugas">Petugas</option>
                                        <option value="superadmin">Super Admin</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn w-100 text-white fw-semibold" style="background:#f79039;border-radius:8px;">
                                    <i class="bi bi-plus-circle me-1"></i>Tambah Akun
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Setup Cepat -->
                    <div class="card stat-card mt-3 p-3">
                        <p class="fw-semibold mb-1 small"><i class="bi bi-lightning-fill text-warning me-1"></i>Setup Cepat 30 Akun</p>
                        <p class="text-muted small mb-2">Jalankan <code>setup_akun.php</code> untuk membuat 30 akun petugas sekaligus.</p>
                        <a href="../setup_akun.php" class="btn btn-outline-warning btn-sm"
                           onclick="return confirm('Buat 30 akun petugas otomatis?')">
                            Setup 30 Akun Otomatis
                        </a>
                    </div>
                </div>

                <!-- Daftar Akun -->
                <div class="col-lg-8">
                    <div class="card stat-card">
                        <div class="card-header border-0 bg-white fw-semibold py-3" style="border-radius:12px 12px 0 0;">
                            Daftar Akun
                            <span class="badge ms-2" style="background:#f79039"><?= count($akun) ?></span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead style="background:#f79039; color:#fff;">
                                    <tr>
                                        <th class="ps-3">#</th>
                                        <th>Nama</th>
                                        <th>Username</th>
                                        <th>Password</th>
                                        <th>Role</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($akun as $i => $a): ?>
                                    <tr>
                                        <td class="ps-3 text-muted small"><?= $i+1 ?></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($a['nama']) ?></td>
                                        <td><code class="text-muted"><?= htmlspecialchars($a['username']) ?></code></td>
                                        <td>
                                            <?php if ($a['plain_password']): ?>
                                                <span class="d-inline-flex align-items-center gap-1">
                                                    <code class="pass-text" style="filter:blur(4px);cursor:pointer;user-select:none;"
                                                          onclick="togglePass(this)"
                                                          title="Klik untuk lihat"><?= htmlspecialchars($a['plain_password']) ?></code>
                                                    <i class="bi bi-eye text-muted small" style="pointer-events:none"></i>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted small fst-italic">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($a['role'] === 'superadmin'): ?>
                                                <span class="badge rounded-pill px-3" style="background:#f79039">Super Admin</span>
                                            <?php else: ?>
                                                <span class="badge rounded-pill bg-secondary px-3">Petugas</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-warning"
                                                    onclick="resetPass(<?= $a['id'] ?>, '<?= htmlspecialchars($a['username']) ?>')"
                                                    title="Reset Password">
                                                <i class="bi bi-key-fill"></i>
                                            </button>
                                            <?php if ($a['id'] != $_SESSION['admin_id']): ?>
                                            <form method="POST" class="d-inline"
                                                  onsubmit="return confirm('Hapus akun <?= htmlspecialchars($a['username']) ?>?')">
                                                <input type="hidden" name="action" value="hapus">
                                                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                                <button class="btn btn-sm btn-outline-danger" title="Hapus">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /page-body -->
    </div><!-- /main-content -->
</div>

<!-- Modal Reset Password -->
<div class="modal fade" id="modalReset" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="POST">
            <input type="hidden" name="action" value="reset">
            <input type="hidden" name="id" id="resetId">
            <div class="modal-content" style="border-radius:14px;overflow:hidden;">
                <div class="modal-header border-0" style="background:#f79039;">
                    <h6 class="modal-title text-white">
                        <i class="bi bi-key-fill me-2" style="color:#f79039"></i>
                        Reset Password — <span id="resetUsername" class="text-warning"></span>
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold small">Password Baru (min. 6 karakter)</label>
                    <input type="password" name="new_password" class="form-control" required minlength="6" placeholder="••••••••">
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm text-white fw-semibold" style="background:#f79039">Simpan Password</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
function resetPass(id, username) {
    document.getElementById('resetId').value = id;
    document.getElementById('resetUsername').textContent = username;
    new bootstrap.Modal(document.getElementById('modalReset')).show();
}
function togglePass(el) {
    const blurred = el.style.filter === 'blur(4px)';
    el.style.filter = blurred ? 'none' : 'blur(4px)';
}
</script>
</body>
</html>
