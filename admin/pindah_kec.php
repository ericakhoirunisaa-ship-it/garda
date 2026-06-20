<?php
require_once 'config.php';
requireLogin();
if (!isSuperAdmin()) { header('Location: index.php'); exit; }

$kodeAsal   = ['011', '012', '130'];
$kodeTujuan = '040';
$namaTujuan = 'Baolan';

$placeholders = implode(',', array_map(fn($k) => "'$k'", $kodeAsal));

// Eksekusi pindah jika dikonfirmasi
$pesan = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'ya') {
    $sql = "UPDATE peserta SET alamat_kec = '$kodeTujuan'
            WHERE alamat_kec IN ($placeholders)";
    if ($conn->query($sql)) {
        $affected = $conn->affected_rows;
        $pesan = "<div class='alert alert-success'><i class='bi bi-check-circle-fill me-2'></i>
                  Berhasil memindahkan <strong>$affected peserta</strong>
                  ke kecamatan <strong>$namaTujuan ($kodeTujuan)</strong>.</div>";
    } else {
        $pesan = "<div class='alert alert-danger'>Gagal: " . htmlspecialchars($conn->error) . "</div>";
    }
}

// Ambil data yang akan/sudah dipindah
$hasil = $conn->query("SELECT nama, alamat_kec, alamat_desa, posisi, status_seleksi
                        FROM peserta
                        WHERE alamat_kec IN ($placeholders)
                        ORDER BY alamat_kec, nama");
$rows = $hasil ? $hasil->fetch_all(MYSQLI_ASSOC) : [];

$kecLabel = ['011' => '011', '012' => '012', '130' => '130'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Pindah Kecamatan — Admin</title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        body { background: #f5f6fa; }
        .card { border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
        .blok-title { background:#f79039;color:#fff;padding:8px 16px;border-radius:8px;
                      font-size:.85rem;font-weight:700;margin-bottom:20px; }
    </style>
</head>
<body class="p-4">
<div class="mx-auto" style="max-width:780px">

    <div class="card">
        <div class="card-body p-4">
            <div class="blok-title">
                <i class="bi bi-geo-alt-fill me-2"></i>Pindah Kecamatan Peserta
            </div>

            <?= $pesan ?>

            <div class="alert mb-4" style="background:#fff8f0;border:1px solid #f79039;border-radius:8px;font-size:.875rem">
                <i class="bi bi-info-circle-fill me-2" style="color:#f79039"></i>
                Peserta dengan kode kecamatan
                <strong><?= implode(', ', $kodeAsal) ?></strong>
                akan dipindahkan ke kecamatan
                <strong><?= $namaTujuan ?> (<?= $kodeTujuan ?>)</strong>.
            </div>

            <?php if ($rows): ?>
            <div class="mb-4">
                <div class="fw-semibold mb-2">
                    Data yang akan dipindah
                    <span class="badge ms-1" style="background:#f79039"><?= count($rows) ?> peserta</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle" style="font-size:.85rem">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>Kec. Asal</th>
                                <th>Desa/Kel.</th>
                                <th>Posisi</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $i => $r): ?>
                            <tr>
                                <td class="text-center text-muted"><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($r['nama']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($r['alamat_kec']) ?></span></td>
                                <td><?= htmlspecialchars($r['alamat_desa'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($r['posisi'] ?? '—') ?></td>
                                <td>
                                    <?php $s = $r['status_seleksi']; ?>
                                    <span class="badge <?= $s === 'Diterima' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= htmlspecialchars($s) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <form method="POST" onsubmit="return confirm('Yakin memindahkan <?= count($rows) ?> peserta ke kec. <?= $namaTujuan ?>?')">
                    <input type="hidden" name="confirm" value="ya">
                    <button type="submit" class="btn text-white fw-semibold px-4"
                            style="background:#f79039;border-radius:8px">
                        <i class="bi bi-arrow-right-circle-fill me-2"></i>
                        Pindahkan <?= count($rows) ?> Peserta ke Kec. <?= $namaTujuan ?> (<?= $kodeTujuan ?>)
                    </button>
                </form>
            </div>

            <?php else: ?>
            <div class="alert alert-secondary">
                <i class="bi bi-check2-circle me-2"></i>
                Tidak ada peserta dengan kode kecamatan
                <strong><?= implode(', ', $kodeAsal) ?></strong>
                <?= $pesan ? '— data sudah dipindahkan.' : 'yang perlu dipindahkan.' ?>
            </div>
            <?php endif; ?>

            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar
            </a>
        </div>
    </div>

</div>
</body>
</html>
