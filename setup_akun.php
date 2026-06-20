<?php
require_once 'config.php';

$token = $_GET['token'] ?? '';
if ($token !== 'SE2026_SETUP') {
    die('<h3>Akses ditolak.</h3><p>Tambahkan <code>?token=SE2026_SETUP</code> di URL.</p>');
}

$akun = [];

// 1 Super Admin
$akun[] = ['superadmin', 'Admin@SE2026', 'Super Admin', 'superadmin'];

// 30 Petugas
for ($i = 1; $i <= 30; $i++) {
    $username = 'petugas' . str_pad($i, 2, '0', STR_PAD_LEFT);
    $nama     = 'Petugas ' . str_pad($i, 2, '0', STR_PAD_LEFT);
    $akun[]   = [$username, 'Petugas@2026', $nama, 'petugas'];
}

$stmt = $conn->prepare("INSERT IGNORE INTO admin_seleksi (username, password, nama, role) VALUES (?,?,?,?)");

$created = 0;
$skipped = 0;
$log     = [];

foreach ($akun as [$username, $password, $nama, $role]) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt->bind_param("ssss", $username, $hash, $nama, $role);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        $created++;
        $log[] = ['status' => 'baru', 'username' => $username, 'nama' => $nama, 'role' => $role, 'pass' => $password];
    } else {
        $skipped++;
        $log[] = ['status' => 'skip', 'username' => $username, 'nama' => $nama, 'role' => $role, 'pass' => '-'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Setup Akun - SE2026</title>
    <link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css">
</head>
<body class="p-4">
    <div class="card shadow-sm" style="max-width:700px">
        <div class="card-header bg-primary text-white fw-bold">Setup Akun Petugas SE2026</div>
        <div class="card-body">
            <p><strong>Akun dibuat:</strong> <span class="badge bg-success"><?= $created ?></span>
               <strong class="ms-3">Dilewati (sudah ada):</strong> <span class="badge bg-secondary"><?= $skipped ?></span></p>
            <div class="alert alert-info small mb-3">
                <strong>Default Password:</strong><br>
                Super Admin: <code>Admin@SE2026</code><br>
                Petugas (01-30): <code>Petugas@2026</code>
            </div>
            <table class="table table-sm table-bordered">
                <thead class="table-dark"><tr><th>#</th><th>Username</th><th>Nama</th><th>Role</th><th>Password</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($log as $i => $l): ?>
                    <tr class="<?= $l['status']==='baru' ? '' : 'text-muted' ?>">
                        <td><?= $i+1 ?></td>
                        <td><code><?= htmlspecialchars($l['username']) ?></code></td>
                        <td><?= htmlspecialchars($l['nama']) ?></td>
                        <td><?= $l['role'] ?></td>
                        <td><code><?= $l['pass'] ?></code></td>
                        <td><?= $l['status']==='baru' ? '<span class="badge bg-success">Dibuat</span>' : '<span class="badge bg-secondary">Sudah Ada</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p class="text-danger fw-semibold"><i class="bi bi-exclamation-triangle me-1"></i>Hapus file ini setelah setup selesai!</p>
            <a href="admin/login.php" class="btn btn-primary">Login ke Admin</a>
        </div>
    </div>
</body>
</html>
