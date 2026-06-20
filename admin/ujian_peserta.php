<?php
require_once 'config.php';
requireLogin();

$kecMap = [
    "010" => "Dampal Selatan", "020" => "Dampal Utara", "030" => "Dondo",
    "031" => "Ogodeide",       "032" => "Basidondo",    "040" => "Baolan",
    "041" => "Lampasio",       "050" => "Galang",       "060" => "Tolitoli Utara",
    "061" => "Dako Pemean",
];

$success = '';
$error   = '';
$tab     = $_GET['tab'] ?? 'daftar';

// ─── Template CSV download ────────────────────────────────────────────────────
if (isset($_GET['template'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="template_peserta_ujian.csv"');
    echo "\xEF\xBB\xBF";
    echo "Nama,Kode Kecamatan,Kelas\n";
    echo "Contoh Nama Peserta,040,A\n";
    exit;
}

// ─── AJAX: edit peserta ───────────────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'edit_nama') {
    $id        = (int)($_POST['id'] ?? 0);
    $nama      = trim($_POST['nama'] ?? '');
    $kecamatan = trim($_POST['kecamatan'] ?? '');
    $kelas     = strtoupper(trim($_POST['kelas'] ?? ''));
    $peran     = strtoupper(trim($_POST['peran'] ?? ''));
    if ($id > 0 && $nama !== '') {
        $stmt = $conn->prepare("UPDATE ujian_mitra SET nama=?, kecamatan=?, kelas=?, peran=? WHERE id=?");
        $stmt->bind_param("ssssi", $nama, $kecamatan, $kelas, $peran, $id);
        $stmt->execute();
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'msg' => 'Data tidak valid']);
    }
    exit;
}

// ─── POST: hapus peserta ──────────────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'hapus') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $conn->query("DELETE FROM ujian_mitra WHERE id=$id");
        $success = 'Peserta berhasil dihapus.';
    }
    $tab = 'daftar';
}

// ─── POST: tambah manual ──────────────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'tambah_manual') {
    $nama  = trim($_POST['nama'] ?? '');
    $kec   = trim($_POST['kecamatan'] ?? '');
    $kelas = strtoupper(trim($_POST['kelas'] ?? ''));
    $peran = strtoupper(trim($_POST['peran'] ?? ''));
    $tab   = 'manual';
    if (!$nama) {
        $error = 'Nama tidak boleh kosong.';
    } else {
        $stmt = $conn->prepare("INSERT INTO ujian_mitra (nama, kecamatan, kelas, peran) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nama, $kec, $kelas, $peran);
        if ($stmt->execute()) {
            $success = "Peserta <strong>" . htmlspecialchars($nama) . "</strong> berhasil ditambahkan.";
            $tab = 'daftar';
        } else {
            $error = 'Gagal menyimpan: ' . htmlspecialchars($conn->error);
        }
    }
}

// ─── POST: import CSV ─────────────────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'import_csv') {
    $tab = 'csv';
    if (empty($_FILES['csv_file']['tmp_name']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'File tidak valid atau gagal diupload.';
    } else {
        $inserted = 0; $skipped = 0;
        $fh = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $bom = fread($fh, 3);
        if ($bom !== "\xEF\xBB\xBF") rewind($fh);
        fgetcsv($fh); // skip header
        $stmt = $conn->prepare("INSERT INTO ujian_mitra (nama, kecamatan, kelas, peran) VALUES (?, ?, ?, ?)");
        while (($row = fgetcsv($fh)) !== false) {
            $nama  = trim($row[0] ?? '');
            if (!$nama) continue;
            $kec   = trim($row[1] ?? '');
            $kelas = strtoupper(trim($row[2] ?? ''));
            $peran = ''; // Peran tidak diimport dari CSV, diisi sendiri oleh peserta saat ujian
            // Skip jika nama sudah ada
            $cek = $conn->prepare("SELECT id FROM ujian_mitra WHERE nama=? LIMIT 1");
            $cek->bind_param("s", $nama);
            $cek->execute();
            if ($cek->get_result()->num_rows > 0) { $skipped++; continue; }
            $stmt->bind_param("ssss", $nama, $kec, $kelas, $peran);
            $stmt->execute() && $inserted++;
        }
        fclose($fh);
        $success = "<strong>$inserted peserta</strong> berhasil diimpor.";
        if ($skipped) $success .= " ($skipped dilewati karena sudah ada).";
        $tab = 'daftar';
    }
}

// ─── Load data ────────────────────────────────────────────────────────────────
$filterKelas = $_GET['kelas'] ?? 'all';
$search      = trim($_GET['q'] ?? '');

$total = (int)$conn->query("SELECT COUNT(*) AS c FROM ujian_mitra")->fetch_assoc()['c'];

$mWhere = "1=1";
if ($filterKelas !== 'all' && $filterKelas !== '') {
    $fk = $conn->real_escape_string($filterKelas);
    $mWhere .= " AND kelas='$fk'";
}
if ($search !== '') {
    $esc = $conn->real_escape_string($search);
    $mWhere .= " AND nama LIKE '%$esc%'";
}
$mitraList = $conn->query("SELECT * FROM ujian_mitra WHERE $mWhere ORDER BY kelas ASC, kecamatan ASC, nama ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peserta Ujian — Admin</title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        .tab-btn {
            border: 2px solid #dee2e6; background: #fff; color: #6c757d;
            border-radius: 10px; padding: 9px 18px; cursor: pointer;
            font-weight: 600; font-size: .875rem; transition: all .15s;
        }
        .tab-btn.active { border-color: #f79039; background: #fff8f0; color: #f79039; }
        .tab-btn:hover:not(.active) { border-color: #f79039; color: #f79039; }
    </style>
</head>
<body>
<?php include '_sidebar.php'; ?>
<div class="main-content">
    <header class="topbar">
        <button class="topbar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
        <div>
            <div class="topbar-title">
                <i class="bi bi-people-fill me-2" style="color:#f79039"></i>Peserta Ujian
            </div>
            <div class="topbar-sub"><?= $total ?> peserta terdaftar &nbsp;·&nbsp; Daftar nama yang muncul di halaman ujian publik</div>
        </div>
        <div class="topbar-right d-flex gap-2">
            <a href="ujian.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kelola Ujian
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

        <!-- Tabs -->
        <div class="d-flex gap-2 mb-4 flex-wrap">
            <button class="tab-btn <?= $tab === 'daftar' ? 'active' : '' ?>" onclick="switchTab('daftar')">
                <i class="bi bi-list-ul me-2"></i>Daftar Peserta
            </button>
            <button class="tab-btn <?= $tab === 'manual' ? 'active' : '' ?>" onclick="switchTab('manual')">
                <i class="bi bi-person-plus me-2"></i>Tambah Manual
            </button>
            <button class="tab-btn <?= $tab === 'csv' ? 'active' : '' ?>" onclick="switchTab('csv')">
                <i class="bi bi-file-earmark-spreadsheet me-2"></i>Import CSV
            </button>
        </div>

        <!-- ── Tab: Daftar ── -->
        <div id="tabDaftar" class="<?= $tab !== 'daftar' ? 'd-none' : '' ?>">

            <!-- Filter & Cari -->
            <div class="card stat-card mb-3">
                <div class="card-body py-2">
                    <form method="GET" class="row g-2 align-items-center">
                        <input type="hidden" name="tab" value="daftar">
                        <div class="col-auto">
                            <select name="kelas" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="all" <?= $filterKelas === 'all' ? 'selected' : '' ?>>Semua Kelas</option>
                                <?php foreach (['A','B','C','D','E','F','G','H','I'] as $_kl): ?>
                                <option value="<?= $_kl ?>" <?= $filterKelas === $_kl ? 'selected' : '' ?>>
                                    Kelas <?= $_kl ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col">
                            <input type="text" name="q" class="form-control form-control-sm"
                                   placeholder="Cari nama peserta..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-auto d-flex gap-1">
                            <button type="submit" class="btn btn-sm btn-warning fw-bold">
                                <i class="bi bi-search me-1"></i>Cari
                            </button>
                            <?php if ($search || $filterKelas !== 'all'): ?>
                            <a href="ujian_peserta.php" class="btn btn-sm btn-outline-secondary">Reset</a>
                            <?php endif; ?>
                        </div>
                        <div class="col-auto">
                            <span class="text-muted small"><?= count($mitraList) ?> peserta<?= ($search || $filterKelas !== 'all') ? ' ditemukan' : '' ?></span>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (empty($mitraList)): ?>
            <div class="card stat-card text-center py-5">
                <div class="text-muted">
                    <i class="bi bi-people fs-2 d-block mb-2"></i>
                    <?= $search ? 'Tidak ada peserta yang cocok dengan pencarian.' : 'Belum ada peserta. Tambah manual atau import CSV.' ?>
                </div>
            </div>
            <?php else: ?>
            <div class="card stat-card" style="overflow:hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width:44px">No</th>
                                <th style="width:70px">Kelas</th>
                                <th>Nama</th>
                                <th>Kecamatan</th>
                                <th style="width:70px">Peran</th>
                                <th class="text-center" style="width:110px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($mitraList as $i => $m): ?>
                        <tr>
                            <td class="ps-3 text-muted small"><?= $i + 1 ?></td>
                            <td>
                                <?php if ($m['kelas']): ?>
                                <span class="badge fw-bold px-2" style="background:#f79039;font-size:.8rem;">
                                    <?= htmlspecialchars($m['kelas']) ?>
                                </span>
                                <?php else: ?>
                                <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-semibold" data-mitra-id="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama']) ?></td>
                            <td>
                                <?php if ($m['kecamatan']): ?>
                                <span class="badge bg-light text-dark border" style="font-size:.78rem;">
                                    <?= htmlspecialchars($kecMap[$m['kecamatan']] ?? $m['kecamatan']) ?>
                                </span>
                                <?php else: ?>
                                <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($m['peran']): ?>
                                <span class="badge <?= $m['peran'] === 'PML Sensus' ? 'bg-primary' : 'bg-success' ?>" style="font-size:.78rem;">
                                    <?= htmlspecialchars($m['peran']) ?>
                                </span>
                                <?php else: ?>
                                <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-warning me-1"
                                        onclick="editMitra(<?= $m['id'] ?>, <?= htmlspecialchars(json_encode($m['nama'])) ?>, <?= htmlspecialchars(json_encode($m['kecamatan'] ?? '')) ?>, <?= htmlspecialchars(json_encode($m['kelas'] ?? '')) ?>, <?= htmlspecialchars(json_encode($m['peran'] ?? '')) ?>)"
                                        title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger"
                                        onclick="hapusMitra(<?= $m['id'] ?>, <?= htmlspecialchars(json_encode($m['nama'])) ?>)"
                                        title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── Tab: Tambah Manual ── -->
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
                                <input type="text" name="nama" class="form-control"
                                       placeholder="Nama lengkap peserta" required
                                       value="<?= ($tab === 'manual' && $error) ? htmlspecialchars($_POST['nama'] ?? '') : '' ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Kelas</label>
                                <select name="kelas" class="form-select">
                                    <option value="">— Pilih Kelas —</option>
                                    <?php foreach (['A','B','C','D','E','F','G','H','I'] as $_kl): ?>
                                    <option value="<?= $_kl ?>"><?= $_kl ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Kecamatan</label>
                                <select name="kecamatan" class="form-select">
                                    <option value="">— Pilih Kecamatan —</option>
                                    <?php foreach ($kecMap as $kode => $nm): ?>
                                    <option value="<?= $kode ?>"><?= $kode ?> — <?= htmlspecialchars($nm) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Peran</label>
                                <select name="peran" class="form-select">
                                    <option value="">— Pilih Peran —</option>
                                    <option value="PPL Sensus">PPL Sensus — Petugas Lapangan Sensus</option>
                                    <option value="PML Sensus">PML Sensus — Pemeriksa Lapangan Sensus</option>
                                </select>
                            </div>
                            <div class="col-12 pt-1">
                                <button type="submit" class="btn btn-warning fw-bold px-5">
                                    <i class="bi bi-save me-1"></i>Simpan Peserta
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ── Tab: Import CSV ── -->
        <div id="tabCsv" class="<?= $tab !== 'csv' ? 'd-none' : '' ?>">
            <div class="card stat-card">
                <div class="card-body">
                    <h6 class="fw-bold mb-1">
                        <i class="bi bi-file-earmark-spreadsheet me-2" style="color:#f79039"></i>Import Peserta dari CSV
                    </h6>
                    <p class="text-muted small mb-4">
                        Upload file CSV dengan dua kolom: <strong>Nama</strong> dan <strong>Kode Kecamatan</strong>.
                        Baris pertama adalah header dan akan dilewati. Nama yang sudah terdaftar akan dilewati otomatis.
                    </p>

                    <div class="mb-4">
                        <a href="ujian_peserta.php?template=1" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-download me-1"></i>Download Template CSV
                        </a>
                    </div>

                    <div class="rounded p-3 mb-4" style="background:#f8f9fa;border:1px dashed #dee2e6;font-size:.82rem;">
                        <strong>Format kolom (header baris pertama dilewati):</strong><br>
                        <code>Nama, Kode Kecamatan, Kelas</code><br>
                        <span class="text-muted">Kelas: A–I &nbsp;|&nbsp;
                        Kode kecamatan: 010=Dampal Selatan, 020=Dampal Utara, 030=Dondo, 031=Ogodeide, 032=Basidondo, 040=Baolan, 041=Lampasio, 050=Galang, 060=Tolitoli Utara, 061=Dako Pemean</span><br>
                        <span class="text-info"><i class="bi bi-info-circle me-1"></i>Kolom <strong>Peran</strong> tidak perlu diisi di CSV — peserta akan memilih sendiri (PPL Sensus atau PML Sensus) saat mengerjakan ujian.</span>
                    </div>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="import_csv">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">File CSV <span class="text-danger">*</span></label>
                                <input type="file" name="csv_file" class="form-control" accept=".csv,.txt" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-warning fw-bold px-5">
                                    <i class="bi bi-upload me-1"></i>Upload & Import
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Edit Peserta -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:#f79039; color:#fff;">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Peserta</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editId">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Peserta</label>
                    <input type="text" id="editNama" class="form-control" placeholder="Nama peserta">
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Kelas</label>
                        <select id="editKelas" class="form-select">
                            <option value="">— Pilih Kelas —</option>
                            <?php foreach (['A','B','C','D','E','F','G','H','I'] as $_kl): ?>
                            <option value="<?= $_kl ?>"><?= $_kl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Kecamatan</label>
                        <select id="editKec" class="form-select">
                            <option value="">— Pilih Kecamatan —</option>
                            <?php foreach ($kecMap as $kode => $nm): ?>
                            <option value="<?= $kode ?>"><?= $kode ?> — <?= htmlspecialchars($nm) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Peran</label>
                        <select id="editPeran" class="form-select">
                            <option value="">— Pilih Peran —</option>
                            <option value="PPL Sensus">PPL Sensus — Petugas Lapangan Sensus</option>
                            <option value="PML Sensus">PML Sensus — Pemeriksa Lapangan Sensus</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-warning fw-bold" onclick="simpanEdit()">
                    <i class="bi bi-save me-1"></i>Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Hapus -->
<div class="modal fade" id="modalHapus" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px">
        <div class="modal-content">
            <div class="modal-header border-0" style="background:#dc2626; color:#fff;">
                <h6 class="modal-title fw-bold"><i class="bi bi-trash-fill me-2"></i>Hapus Peserta</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <p class="mb-1">Hapus peserta:</p>
                <p class="fw-bold mb-3" id="hapusNama">—</p>
                <p class="text-muted small mb-0">Peserta ini tidak akan muncul di dropdown halaman ujian publik. Tindakan ini tidak dapat dibatalkan.</p>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="hapus">
                    <input type="hidden" name="id" id="hapusId">
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
    ['Daftar','Manual','Csv'].forEach(n => {
        document.getElementById('tab' + n).classList.toggle('d-none', t !== n.toLowerCase());
    });
    document.querySelectorAll('.tab-btn').forEach((btn, i) => {
        btn.classList.toggle('active', ['daftar','manual','csv'][i] === t);
    });
}

function editMitra(id, nama, kec, kelas, peran) {
    document.getElementById('editId').value    = id;
    document.getElementById('editNama').value  = nama;
    document.getElementById('editKec').value   = kec   || '';
    document.getElementById('editKelas').value = kelas || '';
    document.getElementById('editPeran').value = peran || '';
    new bootstrap.Modal(document.getElementById('modalEdit')).show();
    setTimeout(() => document.getElementById('editNama').focus(), 300);
}

function simpanEdit() {
    const id    = document.getElementById('editId').value;
    const nama  = document.getElementById('editNama').value.trim();
    const kec   = document.getElementById('editKec').value;
    const kelas = document.getElementById('editKelas').value;
    const peran = document.getElementById('editPeran').value;
    if (!nama) { alert('Nama tidak boleh kosong.'); return; }

    fetch('ujian_peserta.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=edit_nama&id=' + id
            + '&nama=' + encodeURIComponent(nama)
            + '&kecamatan=' + encodeURIComponent(kec)
            + '&kelas=' + encodeURIComponent(kelas)
            + '&peran=' + encodeURIComponent(peran)
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            // Reload page to reflect all updated columns
            location.reload();
        } else {
            alert(d.msg || 'Gagal menyimpan.');
        }
    })
    .catch(() => alert('Terjadi kesalahan.'));
}

function hapusMitra(id, nama) {
    document.getElementById('hapusId').value   = id;
    document.getElementById('hapusNama').textContent = nama;
    new bootstrap.Modal(document.getElementById('modalHapus')).show();
}
</script>
</body>
</html>
