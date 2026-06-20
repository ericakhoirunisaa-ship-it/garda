<?php
require_once 'config.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: pelatihan.php'); exit; }

$pelatihan = $conn->query("SELECT * FROM pelatihan WHERE id=$id")->fetch_assoc();
if (!$pelatihan) { header('Location: pelatihan.php'); exit; }

$success = '';
$error   = '';

// ─── POST Handlers ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isSuperAdmin()) {
    $action = $_POST['action'] ?? '';

    // ── Tambah soal ──────────────────────────────────────────────────────────
    if ($action === 'tambah_soal') {
        $pertanyaan = trim($_POST['pertanyaan'] ?? '');
        $jenis      = in_array($_POST['jenis'] ?? '', ['pilihan_ganda','uraian']) ? $_POST['jenis'] : 'pilihan_ganda';
        $poin       = max(1, (int)($_POST['poin'] ?? 1));
        $maxUrut    = $conn->query("SELECT COALESCE(MAX(nomor_urut),0) AS m FROM pelatihan_soal WHERE pelatihan_id=$id")->fetch_assoc()['m'];

        if ($pertanyaan) {
            $stmt = $conn->prepare("INSERT INTO pelatihan_soal (pelatihan_id, nomor_urut, pertanyaan, jenis, poin) VALUES (?,?,?,?,?)");
            $nomor = $maxUrut + 1;
            $stmt->bind_param("iissi", $id, $nomor, $pertanyaan, $jenis, $poin);
            $stmt->execute();
            $soalId = $conn->insert_id;

            if ($jenis === 'pilihan_ganda') {
                $huruf     = ['A','B','C','D','E'];
                $jawabBenar = strtoupper(trim($_POST['jawab_benar'] ?? 'A'));
                foreach ($huruf as $h) {
                    $teks = trim($_POST['opsi_' . strtolower($h)] ?? '');
                    if ($teks === '') continue;
                    $isBenar = ($jawabBenar === $h) ? 1 : 0;
                    $stmt2 = $conn->prepare("INSERT INTO pelatihan_opsi (soal_id, huruf, teks, is_benar) VALUES (?,?,?,?)");
                    $stmt2->bind_param("issi", $soalId, $h, $teks, $isBenar);
                    $stmt2->execute();
                }
            }
            $success = "Soal nomor $nomor berhasil ditambahkan.";
        } else {
            $error = "Pertanyaan wajib diisi.";
        }

    // ── Edit soal ────────────────────────────────────────────────────────────
    } elseif ($action === 'edit_soal') {
        $soalId     = (int)($_POST['soal_id'] ?? 0);
        $pertanyaan = trim($_POST['pertanyaan'] ?? '');
        $poin       = max(1, (int)($_POST['poin'] ?? 1));
        $jenis      = in_array($_POST['jenis'] ?? '', ['pilihan_ganda','uraian']) ? $_POST['jenis'] : 'pilihan_ganda';

        if ($soalId && $pertanyaan) {
            $stmt = $conn->prepare("UPDATE pelatihan_soal SET pertanyaan=?, jenis=?, poin=? WHERE id=? AND pelatihan_id=?");
            $stmt->bind_param("ssiii", $pertanyaan, $jenis, $poin, $soalId, $id);
            $stmt->execute();

            if ($jenis === 'pilihan_ganda') {
                $conn->query("DELETE FROM pelatihan_opsi WHERE soal_id=$soalId");
                $huruf      = ['A','B','C','D','E'];
                $jawabBenar = strtoupper(trim($_POST['jawab_benar'] ?? 'A'));
                foreach ($huruf as $h) {
                    $teks = trim($_POST['opsi_' . strtolower($h)] ?? '');
                    if ($teks === '') continue;
                    $isBenar = ($jawabBenar === $h) ? 1 : 0;
                    $stmt2 = $conn->prepare("INSERT INTO pelatihan_opsi (soal_id, huruf, teks, is_benar) VALUES (?,?,?,?)");
                    $stmt2->bind_param("issi", $soalId, $h, $teks, $isBenar);
                    $stmt2->execute();
                }
            } else {
                $conn->query("DELETE FROM pelatihan_opsi WHERE soal_id=$soalId");
            }
            $success = "Soal berhasil diperbarui.";
        } else {
            $error = "Data tidak valid.";
        }

    // ── Hapus soal ───────────────────────────────────────────────────────────
    } elseif ($action === 'hapus_soal') {
        $soalId = (int)($_POST['soal_id'] ?? 0);
        if ($soalId) {
            // Cek apakah sudah ada jawaban
            $cek = $conn->query("SELECT COUNT(*) AS c FROM pelatihan_jawaban WHERE soal_id=$soalId")->fetch_assoc();
            if ($cek['c'] > 0) {
                $error = "Soal tidak bisa dihapus karena sudah ada peserta yang menjawab.";
            } else {
                $conn->query("DELETE FROM pelatihan_opsi WHERE soal_id=$soalId");
                $conn->query("DELETE FROM pelatihan_soal WHERE id=$soalId AND pelatihan_id=$id");
                // Re-number
                $soalList = $conn->query("SELECT id FROM pelatihan_soal WHERE pelatihan_id=$id ORDER BY nomor_urut ASC")->fetch_all(MYSQLI_ASSOC);
                foreach ($soalList as $n => $s) {
                    $num = $n + 1;
                    $conn->query("UPDATE pelatihan_soal SET nomor_urut=$num WHERE id=" . (int)$s['id']);
                }
                $success = "Soal berhasil dihapus.";
            }
        }

    // ── Import CSV ───────────────────────────────────────────────────────────
    } elseif ($action === 'import_csv') {
        if (!empty($_FILES['file_csv']['tmp_name'])) {
            $tmpFile = $_FILES['file_csv']['tmp_name'];
            $ext     = strtolower(pathinfo($_FILES['file_csv']['name'], PATHINFO_EXTENSION));

            if ($ext !== 'csv') {
                $error = "Format file harus CSV.";
            } else {
                $handle  = fopen($tmpFile, 'r');
                $header  = fgetcsv($handle); // skip header row
                $imported = 0;
                $maxUrut  = $conn->query("SELECT COALESCE(MAX(nomor_urut),0) AS m FROM pelatihan_soal WHERE pelatihan_id=$id")->fetch_assoc()['m'];

                while (($row = fgetcsv($handle)) !== false) {
                    if (count($row) < 2) continue;
                    $pertanyaan = trim($row[1] ?? '');
                    $jenis      = strtolower(trim($row[2] ?? 'pilihan_ganda'));
                    if (!in_array($jenis, ['pilihan_ganda','uraian'])) $jenis = 'pilihan_ganda';
                    $poin       = max(1, (int)($row[8] ?? 1));

                    if (!$pertanyaan) continue;
                    $maxUrut++;
                    $stmt = $conn->prepare("INSERT INTO pelatihan_soal (pelatihan_id, nomor_urut, pertanyaan, jenis, poin) VALUES (?,?,?,?,?)");
                    $stmt->bind_param("iissi", $id, $maxUrut, $pertanyaan, $jenis, $poin);
                    $stmt->execute();
                    $soalId = $conn->insert_id;

                    if ($jenis === 'pilihan_ganda') {
                        // Format: nomor,pertanyaan,jenis,opsi_a,opsi_b,opsi_c,opsi_d,jawab_benar,poin
                        $opsiMap = ['A' => trim($row[3] ?? ''), 'B' => trim($row[4] ?? ''), 'C' => trim($row[5] ?? ''), 'D' => trim($row[6] ?? '')];
                        $jawabBenar = strtoupper(trim($row[7] ?? 'A'));
                        foreach ($opsiMap as $h => $teks) {
                            if ($teks === '') continue;
                            $isBenar = ($jawabBenar === $h) ? 1 : 0;
                            $stmt2 = $conn->prepare("INSERT INTO pelatihan_opsi (soal_id, huruf, teks, is_benar) VALUES (?,?,?,?)");
                            $stmt2->bind_param("issi", $soalId, $h, $teks, $isBenar);
                            $stmt2->execute();
                        }
                    }
                    $imported++;
                }
                fclose($handle);
                $success = "$imported soal berhasil diimport dari CSV.";
            }
        } else {
            $error = "Pilih file CSV terlebih dahulu.";
        }
    }
}

// ─── Query Soal ───────────────────────────────────────────────────────────────
$soalList = $conn->query("SELECT s.*,
    (SELECT COUNT(*) FROM pelatihan_opsi WHERE soal_id = s.id) AS jumlah_opsi
    FROM pelatihan_soal s WHERE s.pelatihan_id=$id ORDER BY s.nomor_urut ASC")->fetch_all(MYSQLI_ASSOC);

$totalSoal = count($soalList);
$totalPoin = array_sum(array_column($soalList, 'poin'));

$jenisLabel = ['pretest' => 'Pretest', 'posttest' => 'Posttest', 'ujian' => 'Ujian'];
$statusBadge = ['draft' => 'bg-secondary', 'aktif' => 'bg-success', 'selesai' => 'bg-dark'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Soal — <?= htmlspecialchars($pelatihan['judul']) ?></title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        .soal-card { border-left: 4px solid #f79039; }
        .opsi-item { background:#f8f9fa; border-radius:6px; padding:6px 10px; margin-bottom:4px; font-size:.85rem; }
        .opsi-benar { background:#d1fae5; border-left:3px solid #16a34a; }
    </style>
</head>
<body>
<?php include '_sidebar.php'; ?>
<div class="main-content">
    <!-- Topbar -->
    <header class="topbar">
        <button class="topbar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
        <div>
            <div class="topbar-title">
                <a href="pelatihan.php" class="text-muted text-decoration-none me-1"><i class="bi bi-chevron-left"></i></a>
                <i class="bi bi-list-ul me-2" style="color:#f79039"></i>
                Kelola Soal — <?= htmlspecialchars($pelatihan['judul']) ?>
            </div>
            <div class="topbar-sub">
                <span class="badge <?= $statusBadge[$pelatihan['status']] ?> me-1"><?= $pelatihan['status'] ?></span>
                <?= $jenisLabel[$pelatihan['jenis']] ?> · <?= $pelatihan['durasi_menit'] ?> menit · <?= $totalSoal ?> soal · <?= $totalPoin ?> poin total
            </div>
        </div>
        <div class="topbar-right d-flex gap-2">
            <a href="pelatihan_monitoring.php?id=<?= $id ?>" class="btn btn-sm btn-outline-info">
                <i class="bi bi-eye me-1"></i>Monitoring
            </a>
            <?php if (isSuperAdmin()): ?>
            <button class="btn btn-sm btn-warning fw-bold" data-bs-toggle="modal" data-bs-target="#modalTambahSoal">
                <i class="bi bi-plus-lg me-1"></i>Tambah Soal
            </button>
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalImport">
                <i class="bi bi-upload me-1"></i>Import CSV
            </button>
            <?php endif; ?>
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
            <i class="bi bi-exclamation-triangle me-2"></i><?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (empty($soalList)): ?>
        <div class="card stat-card text-center py-5">
            <div class="text-muted"><i class="bi bi-journal-x fs-2 d-block mb-2"></i>
                Belum ada soal.
                <?php if (isSuperAdmin()): ?>Klik <strong>Tambah Soal</strong> atau <strong>Import CSV</strong>.<?php endif; ?>
            </div>
        </div>
        <?php else: ?>

        <?php foreach ($soalList as $soal): ?>
        <?php
            $opsiList = $conn->query("SELECT * FROM pelatihan_opsi WHERE soal_id=" . (int)$soal['id'] . " ORDER BY huruf ASC")->fetch_all(MYSQLI_ASSOC);
        ?>
        <div class="card stat-card soal-card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark fw-bold">No. <?= $soal['nomor_urut'] ?></span>
                        <span class="badge <?= $soal['jenis'] === 'pilihan_ganda' ? 'bg-primary' : 'bg-purple' ?> bg-opacity-75">
                            <?= $soal['jenis'] === 'pilihan_ganda' ? 'Pilihan Ganda' : 'Uraian' ?>
                        </span>
                        <span class="text-muted small"><?= $soal['poin'] ?> poin</span>
                    </div>
                    <?php if (isSuperAdmin()): ?>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-warning" onclick="editSoal(<?= htmlspecialchars(json_encode($soal)) ?>, <?= htmlspecialchars(json_encode($opsiList)) ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="hapusSoal(<?= $soal['id'] ?>, <?= $soal['nomor_urut'] ?>)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                <p class="mb-2 fw-semibold" style="white-space:pre-wrap"><?= htmlspecialchars($soal['pertanyaan']) ?></p>
                <?php if ($soal['jenis'] === 'pilihan_ganda' && $opsiList): ?>
                <div class="mt-2">
                    <?php foreach ($opsiList as $o): ?>
                    <div class="opsi-item <?= $o['is_benar'] ? 'opsi-benar' : '' ?>">
                        <strong><?= $o['huruf'] ?>.</strong> <?= htmlspecialchars($o['teks']) ?>
                        <?php if ($o['is_benar']): ?><i class="bi bi-check-circle-fill text-success ms-1"></i><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php elseif ($soal['jenis'] === 'uraian'): ?>
                <div class="text-muted small fst-italic mt-1"><i class="bi bi-pencil-square me-1"></i>Peserta akan menulis jawaban uraian.</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php endif; ?>
    </div>
</div>

<!-- ─── Modal Tambah Soal ─────────────────────────────────────────────────── -->
<div class="modal fade" id="modalTambahSoal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content" id="formTambah">
            <input type="hidden" name="action" value="tambah_soal">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2 text-warning"></i>Tambah Soal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Pertanyaan <span class="text-danger">*</span></label>
                    <textarea name="pertanyaan" class="form-control" rows="3" placeholder="Tulis pertanyaan di sini..." required></textarea>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Jenis Soal</label>
                        <select name="jenis" class="form-select" onchange="toggleOpsiTambah(this.value)">
                            <option value="pilihan_ganda">Pilihan Ganda</option>
                            <option value="uraian">Uraian</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Poin</label>
                        <input type="number" name="poin" class="form-control" value="1" min="1" max="100">
                    </div>
                </div>
                <div id="seksiOpsiTambah">
                    <div class="mb-2"><label class="form-label fw-semibold">Opsi Jawaban</label></div>
                    <?php foreach (['a','b','c','d'] as $h): ?>
                    <div class="input-group mb-2">
                        <span class="input-group-text fw-bold" style="width:38px"><?= strtoupper($h) ?></span>
                        <input type="text" name="opsi_<?= $h ?>" class="form-control" placeholder="Isi opsi <?= strtoupper($h) ?>...">
                    </div>
                    <?php endforeach; ?>
                    <div class="mt-2">
                        <label class="form-label fw-semibold">Jawaban Benar</label>
                        <select name="jawab_benar" class="form-select">
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning fw-bold">Simpan Soal</button>
            </div>
        </form>
    </div>
</div>

<!-- ─── Modal Edit Soal ───────────────────────────────────────────────────── -->
<div class="modal fade" id="modalEditSoal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="edit_soal">
            <input type="hidden" name="soal_id" id="editSoalId">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2 text-warning"></i>Edit Soal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Pertanyaan <span class="text-danger">*</span></label>
                    <textarea name="pertanyaan" id="editSoalPertanyaan" class="form-control" rows="3" required></textarea>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Jenis Soal</label>
                        <select name="jenis" id="editSoalJenis" class="form-select" onchange="toggleOpsiEdit(this.value)">
                            <option value="pilihan_ganda">Pilihan Ganda</option>
                            <option value="uraian">Uraian</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Poin</label>
                        <input type="number" name="poin" id="editSoalPoin" class="form-control" min="1" max="100">
                    </div>
                </div>
                <div id="seksiOpsiEdit">
                    <div class="mb-2"><label class="form-label fw-semibold">Opsi Jawaban</label></div>
                    <?php foreach (['a','b','c','d'] as $h): ?>
                    <div class="input-group mb-2">
                        <span class="input-group-text fw-bold" style="width:38px"><?= strtoupper($h) ?></span>
                        <input type="text" name="opsi_<?= $h ?>" id="editOpsi<?= strtoupper($h) ?>" class="form-control">
                    </div>
                    <?php endforeach; ?>
                    <div class="mt-2">
                        <label class="form-label fw-semibold">Jawaban Benar</label>
                        <select name="jawab_benar" id="editJawabBenar" class="form-select">
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning fw-bold">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- ─── Modal Import CSV ──────────────────────────────────────────────────── -->
<div class="modal fade" id="modalImport" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" enctype="multipart/form-data" class="modal-content">
            <input type="hidden" name="action" value="import_csv">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-upload me-2 text-warning"></i>Import Soal dari CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 small">
                    <strong>Format CSV (tanpa baris header):</strong><br>
                    <code>nomor, pertanyaan, jenis, opsi_a, opsi_b, opsi_c, opsi_d, jawab_benar, poin</code><br><br>
                    <strong>Contoh:</strong><br>
                    <code>1,"Apa tugas petugas lapangan?",pilihan_ganda,"Mencatat data","Mengolah data","Menganalisis","Mempublikasikan",A,1</code><br>
                    <code>2,"Jelaskan cara pendekatan ke responden!",uraian,,,,,,2</code><br><br>
                    <em>Untuk uraian, kolom opsi dan jawab_benar biarkan kosong.</em>
                </div>
                <a href="pelatihan_template_csv.php" class="btn btn-sm btn-outline-secondary mb-3">
                    <i class="bi bi-download me-1"></i>Download Template CSV
                </a>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Pilih File CSV <span class="text-danger">*</span></label>
                    <input type="file" name="file_csv" accept=".csv" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning fw-bold">Import Soal</button>
            </div>
        </form>
    </div>
</div>

<!-- Form Hapus Soal (hidden) -->
<form id="formHapusSoal" method="POST" style="display:none">
    <input type="hidden" name="action" value="hapus_soal">
    <input type="hidden" name="soal_id" id="hapusSoalId">
</form>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
function toggleOpsiTambah(jenis) {
    document.getElementById('seksiOpsiTambah').style.display = jenis === 'pilihan_ganda' ? '' : 'none';
}
function toggleOpsiEdit(jenis) {
    document.getElementById('seksiOpsiEdit').style.display = jenis === 'pilihan_ganda' ? '' : 'none';
}
function editSoal(soal, opsiList) {
    document.getElementById('editSoalId').value = soal.id;
    document.getElementById('editSoalPertanyaan').value = soal.pertanyaan;
    document.getElementById('editSoalJenis').value = soal.jenis;
    document.getElementById('editSoalPoin').value = soal.poin;
    toggleOpsiEdit(soal.jenis);

    ['A','B','C','D'].forEach(h => {
        document.getElementById('editOpsi' + h).value = '';
    });
    document.getElementById('editJawabBenar').value = 'A';

    opsiList.forEach(o => {
        const el = document.getElementById('editOpsi' + o.huruf);
        if (el) el.value = o.teks;
        if (parseInt(o.is_benar)) {
            document.getElementById('editJawabBenar').value = o.huruf;
        }
    });
    new bootstrap.Modal(document.getElementById('modalEditSoal')).show();
}
function hapusSoal(soalId, nomor) {
    if (!confirm('Hapus soal nomor ' + nomor + '?\nSoal tidak bisa dihapus jika sudah ada peserta yang menjawab.')) return;
    document.getElementById('hapusSoalId').value = soalId;
    document.getElementById('formHapusSoal').submit();
}
</script>
</body>
</html>
