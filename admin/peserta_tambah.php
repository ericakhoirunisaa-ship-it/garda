<?php
require_once 'config.php';
requireLogin();

// Migrate kelompok column if not exists
(function() use ($conn) {
    $cols = array_column($conn->query("SHOW COLUMNS FROM peserta")->fetch_all(MYSQLI_ASSOC), 'Field');
    if (!in_array('kelompok', $cols))
        $conn->query("ALTER TABLE peserta ADD COLUMN `kelompok` VARCHAR(100) NULL DEFAULT NULL");
})();

$kecMap = [
    "010" => "Dampal Selatan", "020" => "Dampal Utara", "030" => "Dondo",
    "031" => "Ogodeide", "032" => "Basidondo", "040" => "Baolan",
    "041" => "Lampasio", "050" => "Galang", "060" => "Tolitoli Utara", "061" => "Dako Pemean",
];

$tab     = $_GET['tab'] ?? 'afirmasi';
$msg     = $_GET['msg'] ?? '';
$msgType = 'success';

function escStr($conn, $v) {
    return $conn->real_escape_string(trim((string)($v ?? '')));
}

// ── Hapus peserta ──
if (($_POST['action'] ?? '') === 'hapus_peserta') {
    $pid = (int)($_POST['peserta_id'] ?? 0);
    if ($pid > 0) {
        $conn->query("DELETE FROM wawancara WHERE peserta_id=$pid");
        $conn->query("DELETE FROM peserta WHERE id=$pid AND kelompok='Afirmasi'");
    }
    header('Location: peserta_tambah.php?tab=afirmasi&msg=hapus');
    exit;
}

// ── Import dari afirmasi.csv ──
if (($_POST['action'] ?? '') === 'import_afirmasi') {
    $tab     = 'afirmasi';
    $csvPath = __DIR__ . '/../afirmasi.csv';
    if (!file_exists($csvPath)) {
        $msg = 'File afirmasi.csv tidak ditemukan di server.';
        $msgType = 'danger';
    } else {
        $inserted = 0; $skipped = 0;
        $fh = fopen($csvPath, 'r');
        fgetcsv($fh); // skip header
        while (($row = fgetcsv($fh)) !== false) {
            $nama = trim($row[0] ?? '');
            if (!$nama) continue;
            $st = $conn->prepare("SELECT id FROM peserta WHERE nama=? LIMIT 1");
            $st->bind_param('s', $nama);
            $st->execute();
            $dup = $st->get_result()->num_rows > 0;
            $st->close();
            if ($dup) { $skipped++; continue; }

            preg_match('/\((\d{3})\)/', $row[6] ?? '', $m);
            $kec = $m[1] ?? '';
            $jk  = (strtolower(trim($row[10] ?? '')) === 'pr') ? 'Pr' : 'Lk';
            $e   = fn($v) => escStr($conn, $v);

            $conn->query("INSERT INTO peserta
                (nama,status_seleksi,posisi_daftar,alamat_detail,alamat_kec,
                 pendidikan,pekerjaan,jenis_kelamin,no_telp,email,
                 merk_hp,tipe_hp,ram_hp,kelompok)
                VALUES
                ('{$e($row[0])}','Diterima','{$e($row[2])}','{$e($row[3])}','$kec',
                 '{$e($row[11])}','{$e($row[12])}','$jk','{$e($row[17])}','{$e($row[19])}',
                 '{$e($row[24])}','{$e($row[25])}','{$e($row[26])}','Afirmasi')
            ") && $inserted++;
        }
        fclose($fh);
        $msg = "<strong>$inserted peserta</strong> berhasil diimpor dari afirmasi.csv.";
        if ($skipped) $msg .= " <strong>$skipped</strong> dilewati (nama sudah ada).";
    }
}

// ── Tambah manual ──
if (($_POST['action'] ?? '') === 'tambah_manual') {
    $tab  = 'manual';
    $nama = trim($_POST['nama'] ?? '');
    if (!$nama) {
        $msg = 'Nama tidak boleh kosong.';
        $msgType = 'danger';
    } else {
        $e   = fn($v) => escStr($conn, $v);
        $sql = "INSERT INTO peserta
            (nama,status_seleksi,posisi_daftar,alamat_detail,alamat_kec,
             pendidikan,pekerjaan,jenis_kelamin,no_telp,email,
             merk_hp,tipe_hp,ram_hp,kelompok)
            VALUES
            ('{$e($nama)}','Diterima','{$e($_POST['posisi_daftar']??'')}',
             '{$e($_POST['alamat_detail']??'')}','{$e($_POST['alamat_kec']??'')}',
             '{$e($_POST['pendidikan']??'')}','{$e($_POST['pekerjaan']??'')}',
             '{$e($_POST['jenis_kelamin']??'')}','{$e($_POST['no_telp']??'')}',
             '{$e($_POST['email']??'')}',
             '{$e($_POST['merk_hp']??'')}','{$e($_POST['tipe_hp']??'')}',
             '{$e($_POST['ram_hp']??'')}',
             '{$e($_POST['kelompok']??'Afirmasi')}')";
        if ($conn->query($sql)) {
            $msg = "Peserta <strong>{$e($nama)}</strong> berhasil ditambahkan.";
            $tab = 'afirmasi';
        } else {
            $msg = 'Gagal menyimpan: ' . htmlspecialchars($conn->error);
            $msgType = 'danger';
        }
    }
}

// ── Import dari CSV ──
if (($_POST['action'] ?? '') === 'import_csv') {
    $tab      = 'excel';
    $kelompok = escStr($conn, $_POST['kelompok_csv'] ?? 'Afirmasi');
    if (empty($_FILES['csv_file']['tmp_name']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $msg = 'File tidak valid atau gagal diupload.';
        $msgType = 'danger';
    } else {
        $inserted = 0; $skipped = 0;
        $fh = fopen($_FILES['csv_file']['tmp_name'], 'r');
        // strip UTF-8 BOM if present
        $bom = fread($fh, 3);
        if ($bom !== "\xEF\xBB\xBF") rewind($fh);
        fgetcsv($fh); // skip header row
        while (($row = fgetcsv($fh)) !== false) {
            $nama = trim($row[0] ?? '');
            if (!$nama) continue;
            $st = $conn->prepare("SELECT id FROM peserta WHERE nama=? LIMIT 1");
            $st->bind_param('s', $nama);
            $st->execute();
            $dup = $st->get_result()->num_rows > 0;
            $st->close();
            if ($dup) { $skipped++; continue; }
            $e = fn($v) => escStr($conn, $v);
            $conn->query("INSERT INTO peserta
                (nama,status_seleksi,posisi_daftar,alamat_detail,alamat_kec,
                 pendidikan,pekerjaan,jenis_kelamin,no_telp,email,
                 merk_hp,tipe_hp,ram_hp,kelompok)
                VALUES
                ('{$e($row[0])}','Diterima','{$e($row[1]??'')}','{$e($row[2]??'')}',
                 '{$e($row[3]??'')}','{$e($row[4]??'')}','{$e($row[5]??'')}',
                 '{$e($row[6]??'')}','{$e($row[7]??'')}','{$e($row[8]??'')}',
                 '{$e($row[9]??'')}','{$e($row[10]??'')}','{$e($row[11]??'')}',
                 '$kelompok')
            ") && $inserted++;
        }
        fclose($fh);
        $msg = "<strong>$inserted peserta</strong> berhasil diimpor.";
        if ($skipped) $msg .= " ($skipped dilewati karena nama sudah ada)";
    }
}

// ── Stats ──
$csvPath = __DIR__ . '/../afirmasi.csv';
$afirmasiCsvCount = 0;
if (file_exists($csvPath)) {
    $fh = fopen($csvPath, 'r');
    fgetcsv($fh);
    while (($row = fgetcsv($fh)) !== false) {
        if (trim($row[0] ?? '')) $afirmasiCsvCount++;
    }
    fclose($fh);
}
$importedAfirmasi = (int)($conn->query("SELECT COUNT(*) c FROM peserta WHERE kelompok='Afirmasi'")->fetch_assoc()['c'] ?? 0);
$totalPeserta     = (int)($conn->query("SELECT COUNT(*) c FROM peserta WHERE status_seleksi='Diterima'")->fetch_assoc()['c'] ?? 0);

// Resolve GET msg
if ($msg === 'hapus') { $msg = 'Peserta berhasil dihapus.'; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Peserta — SEMANIS 2026</title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        .tab-btn {
            border: 2px solid #dee2e6; background: #fff; color: #6c757d;
            border-radius: 10px; padding: 10px 20px; cursor: pointer;
            font-weight: 600; font-size: .88rem; transition: all .15s;
        }
        .tab-btn.active { border-color: #f79039; background: #fff8f0; color: #f79039; }
        .tab-btn:hover:not(.active) { border-color: #f79039; color: #f79039; }
        .stat-icon { width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; }
        .format-box { background:#f8f9fa; border:1px dashed #dee2e6; border-radius:8px; padding:14px 18px; font-size:.8rem; }
        .badge-afirmasi { background:#fff8f0; color:#f79039; border:1px solid #f79039; font-size:.72rem; font-weight:700; }
    </style>
</head>
<body>
<div class="d-flex">
    <?php include '_sidebar.php'; ?>

    <div class="main-content w-100">
        <header class="topbar">
            <button class="topbar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div>
                <div class="topbar-title"><i class="bi bi-person-plus-fill me-2" style="color:#f79039"></i>Tambah Peserta Wawancara</div>
                <div class="topbar-sub">Sensus Ekonomi 2026 — BPS Kab. Toli-Toli</div>
            </div>
            <div class="topbar-right">
                <a href="index.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar
                </a>
            </div>
        </header>

        <div class="page-body">

            <!-- Stat Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people-fill"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= $totalPeserta ?></div>
                                <div class="text-muted small">Total Peserta</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background:rgba(247,144,57,.12);color:#f79039"><i class="bi bi-tag-fill"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= $importedAfirmasi ?></div>
                                <div class="text-muted small">Kelompok Afirmasi</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-file-csv-fill"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= $afirmasiCsvCount ?></div>
                                <div class="text-muted small">Data di afirmasi.csv</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card stat-card p-3 <?= ($afirmasiCsvCount > $importedAfirmasi) ? 'border-warning' : '' ?>">
                        <div class="d-flex align-items-center gap-3">
                            <?php if ($afirmasiCsvCount > $importedAfirmasi): ?>
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-exclamation-circle-fill"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1 text-warning"><?= $afirmasiCsvCount - $importedAfirmasi ?></div>
                                <div class="text-muted small">Belum Diimpor</div>
                            </div>
                            <?php else: ?>
                            <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle-fill"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1 text-success">✓</div>
                                <div class="text-muted small">Semua Terimport</div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert -->
            <?php if ($msg): ?>
            <div class="alert alert-<?= $msgType ?> d-flex align-items-center justify-content-between gap-2" style="border-radius:10px">
                <span><i class="bi bi-<?= $msgType === 'success' ? 'check-circle-fill' : 'x-circle-fill' ?> me-2"></i><?= $msg ?></span>
                <?php if ($msgType === 'success'): ?>
                <a href="index.php?kelompok=Afirmasi" class="btn btn-sm btn-<?= $msgType ?> flex-shrink-0">
                    <i class="bi bi-eye me-1"></i>Lihat Kelompok Afirmasi
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Tabs -->
            <div class="d-flex gap-2 mb-4 flex-wrap">
                <button class="tab-btn <?= $tab === 'afirmasi' ? 'active' : '' ?>" onclick="switchTab('afirmasi')">
                    <i class="bi bi-file-earmark-arrow-down me-2"></i>Import dari afirmasi.csv
                </button>
                <button class="tab-btn <?= $tab === 'manual' ? 'active' : '' ?>" onclick="switchTab('manual')">
                    <i class="bi bi-person-plus me-2"></i>Tambah Manual
                </button>
                <button class="tab-btn <?= $tab === 'excel' ? 'active' : '' ?>" onclick="switchTab('excel')">
                    <i class="bi bi-file-earmark-spreadsheet me-2"></i>Import Excel/CSV
                </button>
            </div>

            <!-- ── Tab: Import afirmasi.csv ── -->
            <div id="tabAfirmasi" class="<?= $tab !== 'afirmasi' ? 'd-none' : '' ?>">
                <div class="card stat-card mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">
                            <i class="bi bi-file-earmark-arrow-down me-2" style="color:#f79039"></i>Import dari afirmasi.csv
                        </h6>
                        <p class="text-muted small mb-4">
                            Mengimpor seluruh data dari file <code>afirmasi.csv</code> yang tersimpan di server.
                            Peserta yang namanya sudah terdaftar akan dilewati secara otomatis.
                            Semua peserta baru akan diberi label kelompok <span class="badge badge-afirmasi rounded-pill px-2">Afirmasi</span>.
                        </p>

                        <?php if (!file_exists(__DIR__ . '/../afirmasi.csv')): ?>
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            File <code>afirmasi.csv</code> tidak ditemukan. Pastikan file ada di direktori root proyek.
                        </div>
                        <?php else: ?>
                        <div class="p-4 rounded mb-4 d-flex align-items-center gap-3"
                             style="background:#f0fdf4;border:1px solid #bbf7d0">
                            <div style="font-size:2.5rem;line-height:1">📄</div>
                            <div class="flex-grow-1">
                                <div class="fw-bold">afirmasi.csv</div>
                                <div class="text-muted small">
                                    <strong><?= $afirmasiCsvCount ?></strong> peserta ditemukan &nbsp;·&nbsp;
                                    Sudah diimpor: <strong><?= $importedAfirmasi ?></strong> &nbsp;·&nbsp;
                                    Belum: <strong><?= max(0, $afirmasiCsvCount - $importedAfirmasi) ?></strong>
                                </div>
                            </div>
                            <?php if ($importedAfirmasi >= $afirmasiCsvCount): ?>
                            <span class="badge bg-success px-3 py-2"><i class="bi bi-check-circle me-1"></i>Semua sudah diimpor</span>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex gap-2 flex-wrap">
                            <?php if ($importedAfirmasi < $afirmasiCsvCount): ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="import_afirmasi">
                                <button type="submit" class="btn btn-lg text-white px-5 fw-semibold" style="background:#f79039;border-radius:10px">
                                    <i class="bi bi-download me-2"></i>Import Sekarang
                                    (<?= $afirmasiCsvCount - $importedAfirmasi ?> peserta baru)
                                </button>
                            </form>
                            <?php else: ?>
                            <form method="POST" onsubmit="return confirm('Semua sudah diimpor. Import ulang akan melewati nama yang sudah ada. Lanjutkan?')">
                                <input type="hidden" name="action" value="import_afirmasi">
                                <button type="submit" class="btn btn-outline-secondary px-4" style="border-radius:10px">
                                    <i class="bi bi-arrow-repeat me-1"></i>Import Ulang
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($importedAfirmasi > 0):
                    $afList = $conn->query(
                        "SELECT id, nama, alamat_kec, pendidikan, jenis_kelamin, no_telp
                         FROM peserta WHERE kelompok='Afirmasi' ORDER BY alamat_kec, nama"
                    )->fetch_all(MYSQLI_ASSOC);
                ?>
                <div class="card stat-card" style="overflow:hidden">
                    <div class="d-flex align-items-center justify-content-between px-4 py-3"
                         style="background:#fff8f0;border-bottom:1px solid #ffe0c0">
                        <div class="fw-bold" style="color:#f79039">
                            <i class="bi bi-people-fill me-2"></i>Kelompok Afirmasi
                            <span class="badge ms-2 px-2 py-1" style="background:#f79039;font-size:.75rem"><?= $importedAfirmasi ?></span>
                        </div>
                        <a href="index.php?kelompok=Afirmasi" class="btn btn-sm btn-outline-secondary" style="border-radius:8px">
                            <i class="bi bi-eye me-1"></i>Lihat di Daftar Wawancara
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size:.83rem">
                            <thead style="background:#fff3e6">
                                <tr>
                                    <th class="ps-3 text-muted fw-semibold" style="font-size:.73rem">#</th>
                                    <th class="text-muted fw-semibold" style="font-size:.73rem">Nama</th>
                                    <th class="text-muted fw-semibold" style="font-size:.73rem">Kecamatan</th>
                                    <th class="text-muted fw-semibold" style="font-size:.73rem">Pendidikan</th>
                                    <th class="text-muted fw-semibold" style="font-size:.73rem">No. Telp</th>
                                    <th class="text-center text-muted fw-semibold" style="font-size:.73rem">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($afList as $i => $p):
                                $kecNama = $kecMap[$p['alamat_kec']] ?? $p['alamat_kec'];
                            ?>
                            <tr>
                                <td class="ps-3 text-muted"><?= $i + 1 ?></td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($p['nama']) ?></div>
                                    <small class="text-muted"><?= $p['jenis_kelamin'] === 'Pr' ? 'Perempuan' : 'Laki-laki' ?></small>
                                </td>
                                <td>
                                    <small><?= $p['alamat_kec']
                                        ? "<span class='badge' style='background:#f79039;font-size:.7rem'>{$p['alamat_kec']}</span> {$kecNama}"
                                        : '<span class="text-muted">—</span>' ?>
                                    </small>
                                </td>
                                <td><small><?= htmlspecialchars($p['pendidikan'] ?? '—') ?></small></td>
                                <td><small><?= htmlspecialchars($p['no_telp'] ?? '—') ?></small></td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="form_wawancara.php?id=<?= $p['id'] ?>"
                                           class="btn btn-sm btn-outline-primary" title="Wawancara">
                                            <i class="bi bi-mic-fill"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-danger"
                                                onclick="hapusPeserta(<?= $p['id'] ?>, <?= htmlspecialchars(json_encode($p['nama']), ENT_QUOTES) ?>)"
                                                title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- ── Tab: Manual ── -->
            <div id="tabManual" class="<?= $tab !== 'manual' ? 'd-none' : '' ?>">
                <div class="card stat-card">
                    <div class="card-body">
                        <h6 class="fw-bold mb-4">
                            <i class="bi bi-person-plus me-2" style="color:#f79039"></i>Tambah Peserta Manual
                        </h6>
                        <form method="POST">
                            <input type="hidden" name="action" value="tambah_manual">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" name="nama" class="form-control" placeholder="Nama lengkap peserta" required
                                           value="<?= $msgType === 'danger' && $tab === 'manual' ? htmlspecialchars($_POST['nama'] ?? '') : '' ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Kelompok</label>
                                    <input type="text" name="kelompok" class="form-control" value="Afirmasi" placeholder="Afirmasi / batch lain">
                                    <small class="text-muted">Label untuk mengelompokkan peserta</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Posisi Daftar</label>
                                    <input type="text" name="posisi_daftar" class="form-control" placeholder="Mitra Pendataan / Mitra Pengolahan">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Kecamatan</label>
                                    <select name="alamat_kec" class="form-select">
                                        <option value="">— Pilih Kecamatan —</option>
                                        <?php foreach ($kecMap as $kode => $nama): ?>
                                        <option value="<?= $kode ?>"><?= $kode ?> — <?= htmlspecialchars($nama) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Jenis Kelamin</label>
                                    <select name="jenis_kelamin" class="form-select">
                                        <option value="">— Pilih —</option>
                                        <option value="Lk">Laki-laki</option>
                                        <option value="Pr">Perempuan</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">No. Telepon</label>
                                    <input type="text" name="no_telp" class="form-control" placeholder="+62 812-xxx-xxxx">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Email</label>
                                    <input type="email" name="email" class="form-control" placeholder="email@contoh.com">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Pendidikan Terakhir</label>
                                    <input type="text" name="pendidikan" class="form-control" placeholder="Tamat D4/S1 / Tamat SMA / dll">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Pekerjaan</label>
                                    <input type="text" name="pekerjaan" class="form-control" placeholder="Pelajar / Wiraswasta / dll">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Alamat Detail</label>
                                    <input type="text" name="alamat_detail" class="form-control" placeholder="Jl. ... No. ...">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Merk HP</label>
                                    <input type="text" name="merk_hp" class="form-control" placeholder="Samsung / Xiaomi / Oppo">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Tipe HP</label>
                                    <input type="text" name="tipe_hp" class="form-control" placeholder="Galaxy A15 / Note 14">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">RAM HP (GB)</label>
                                    <input type="text" name="ram_hp" class="form-control" placeholder="4 / 6 / 8">
                                </div>
                                <div class="col-12 pt-2">
                                    <button type="submit" class="btn btn-lg text-white fw-semibold px-5" style="background:#f79039;border-radius:10px">
                                        <i class="bi bi-save-fill me-2"></i>Simpan Peserta
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ── Tab: Excel/CSV ── -->
            <div id="tabExcel" class="<?= $tab !== 'excel' ? 'd-none' : '' ?>">
                <div class="card stat-card">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1">
                            <i class="bi bi-file-earmark-spreadsheet me-2" style="color:#f79039"></i>Import dari Excel / CSV
                        </h6>
                        <p class="text-muted small mb-4">
                            Upload file CSV. Jika menggunakan Excel, simpan terlebih dahulu sebagai CSV (UTF-8) sebelum diupload.
                            Peserta yang namanya sudah terdaftar akan dilewati otomatis.
                        </p>

                        <div class="mb-4">
                            <a href="peserta_template_csv.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-download me-1"></i>Download Template CSV
                            </a>
                        </div>

                        <div class="format-box mb-4">
                            <div class="fw-semibold mb-2"><i class="bi bi-info-circle me-1" style="color:#f79039"></i>Format kolom CSV (baris pertama = header, dilewati otomatis):</div>
                            <code style="font-size:.78rem;word-break:break-all">
                                Nama, Posisi Daftar, Alamat Detail, Kode Kecamatan (3 digit), Pendidikan, Pekerjaan, Jenis Kelamin (Lk/Pr), No Telp, Email, Merk HP, Tipe HP, RAM HP
                            </code>
                            <div class="mt-2 text-muted" style="font-size:.76rem">
                                Kode kecamatan: 010=Dampal Selatan, 020=Dampal Utara, 030=Dondo, 031=Ogodeide, 032=Basidondo, 040=Baolan, 041=Lampasio, 050=Galang, 060=Tolitoli Utara, 061=Dako Pemean
                            </div>
                        </div>

                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="import_csv">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Nama Kelompok</label>
                                    <input type="text" name="kelompok_csv" class="form-control" value="Afirmasi" placeholder="Afirmasi">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold">File CSV <span class="text-danger">*</span></label>
                                    <input type="file" name="csv_file" id="csvFileInput" class="form-control"
                                           accept=".csv,.txt" required
                                           onchange="document.getElementById('csvFileName').textContent = this.files[0]?.name ?? ''">
                                    <small id="csvFileName" class="text-muted"></small>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn text-white fw-semibold px-5" style="background:#f79039;border-radius:10px">
                                        <i class="bi bi-upload me-2"></i>Upload & Import
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div><!-- /page-body -->
    </div><!-- /main-content -->
</div>

<!-- Modal hapus peserta -->
<div class="modal fade" id="modalHapus" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px">
        <div class="modal-content" style="border-radius:14px;overflow:hidden">
            <div class="modal-header border-0 py-3" style="background:#dc2626">
                <h6 class="modal-title text-white fw-bold mb-0">
                    <i class="bi bi-trash-fill me-2"></i>Hapus Peserta
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <p class="mb-1">Hapus peserta:</p>
                <p class="fw-bold mb-3" id="hapusNama">—</p>
                <p class="mb-0 text-muted" style="font-size:.875rem">
                    Data peserta dan semua catatan wawancara terkait akan
                    <strong>dihapus permanen</strong>. Tindakan ini tidak dapat dibatalkan.
                </p>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="hapus_peserta">
                    <input type="hidden" name="peserta_id" id="hapusId">
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-trash me-1"></i>Ya, Hapus
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
function switchTab(t) {
    document.getElementById('tabAfirmasi').classList.toggle('d-none', t !== 'afirmasi');
    document.getElementById('tabManual').classList.toggle('d-none', t !== 'manual');
    document.getElementById('tabExcel').classList.toggle('d-none', t !== 'excel');
    const ids = ['afirmasi', 'manual', 'excel'];
    document.querySelectorAll('.tab-btn').forEach((btn, i) => {
        btn.classList.toggle('active', ids[i] === t);
    });
}

function hapusPeserta(id, nama) {
    document.getElementById('hapusId').value = id;
    document.getElementById('hapusNama').textContent = nama;
    new bootstrap.Modal(document.getElementById('modalHapus')).show();
}
</script>
</body>
</html>
