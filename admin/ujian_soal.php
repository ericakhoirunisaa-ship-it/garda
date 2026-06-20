<?php
require_once 'config.php';
requireLogin();

$ujianId = (int)($_GET['id'] ?? 0);

// Require a valid ujian_id — redirect to ujian list if missing
if (!$ujianId) {
    header('Location: ujian.php');
    exit;
}

$ujianRow = $conn->query("SELECT * FROM ujian WHERE id=$ujianId LIMIT 1")->fetch_assoc();
if (!$ujianRow) {
    header('Location: ujian.php');
    exit;
}

$success = '';
$error   = '';

// ─── Baca entri dari file XLSX (ZIP) ─────────────────────────────────────────
function xlsxGetEntryUjian(string $xlsxPath, string $entryName): ?string {
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($xlsxPath) === true) {
            $c = $zip->getFromName($entryName);
            $zip->close();
            return $c !== false ? $c : null;
        }
    }
    if (function_exists('exec')) {
        $out = []; $ret = 0;
        exec('unzip -p ' . escapeshellarg($xlsxPath) . ' ' . escapeshellarg($entryName) . ' 2>/dev/null', $out, $ret);
        if ($ret === 0 && !empty($out)) return implode("\n", $out);
    }
    return null;
}

function parseXlsxSoalUjian(string $file): array|false {
    $ss       = xlsxGetEntryUjian($file, 'xl/sharedStrings.xml');
    $sheetRaw = xlsxGetEntryUjian($file, 'xl/worksheets/sheet1.xml');
    if (!$sheetRaw) return false;

    $strings = [];
    if ($ss) {
        $xml = @simplexml_load_string($ss);
        if ($xml) {
            foreach ($xml->si as $si) {
                $str = '';
                if (isset($si->t)) $str = (string)$si->t;
                else foreach ($si->r as $r) { if (isset($r->t)) $str .= (string)$r->t; }
                $strings[] = $str;
            }
        }
    }

    $xml2 = @simplexml_load_string($sheetRaw);
    if (!$xml2) return false;

    $data = [];
    foreach ($xml2->sheetData->row as $row) {
        $rowIdx  = (int)$row['r'] - 1;
        $rowData = ['A'=>'','B'=>'','C'=>'','D'=>'','E'=>'','F'=>'','G'=>'','H'=>''];
        foreach ($row->c as $cell) {
            $ref = (string)$cell['r'];
            preg_match('/([A-Z]+)/', $ref, $m);
            $col = $m[1] ?? '';
            if (!array_key_exists($col, $rowData)) continue;
            $t = (string)$cell['t'];
            $v = isset($cell->v) ? (string)$cell->v : '';
            $rowData[$col] = ($t === 's') ? ($strings[(int)$v] ?? '') : $v;
        }
        $data[$rowIdx] = $rowData;
    }
    ksort($data);
    return array_values($data);
}

// ─── Download Template CSV ────────────────────────────────────────────────────
if (isset($_GET['template'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="template_soal_ujian.csv"');
    echo "\xEF\xBB\xBF";
    echo "nomor,pertanyaan,opsi_a,opsi_b,opsi_c,opsi_d,opsi_e,jawab_benar\n";
    echo "1,\"Apa kepanjangan dari BPS?\",\"Badan Penelitian Statistik\",\"Badan Pusat Statistik\",\"Balai Pusat Sensus\",\"Biro Penghitungan Statistik\",\"Badan Penghitung Sensus\",B\n";
    echo "2,\"Sensus Ekonomi 2026 dilaksanakan pada bulan?\",\"Maret\",\"April\",\"Mei\",\"Juni\",\"Juli\",C\n";
    exit;
}

// ─── POST Handlers ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isSuperAdmin()) {
    $action = $_POST['action'] ?? '';

    // ── Tambah soal ──────────────────────────────────────────────────────────
    if ($action === 'tambah') {
        $pertanyaan = trim($_POST['pertanyaan'] ?? '');
        $jawabBenar = strtoupper(trim($_POST['jawab_benar'] ?? 'A'));
        if ($pertanyaan) {
            $maxUrut = $conn->query("SELECT COALESCE(MAX(nomor_urut),0) AS m FROM ujian_soal WHERE ujian_id=$ujianId")->fetch_assoc()['m'];
            $nomor   = $maxUrut + 1;
            $stmt = $conn->prepare("INSERT INTO ujian_soal (ujian_id, nomor_urut, pertanyaan) VALUES (?,?,?)");
            $stmt->bind_param("iis", $ujianId, $nomor, $pertanyaan);
            $stmt->execute();
            $soalId = $conn->insert_id;
            foreach (['A','B','C','D','E'] as $h) {
                $teks = trim($_POST['opsi_' . strtolower($h)] ?? '');
                if ($teks === '') continue;
                $isBenar = ($jawabBenar === $h) ? 1 : 0;
                $s2 = $conn->prepare("INSERT INTO ujian_opsi (soal_id, huruf, teks, is_benar) VALUES (?,?,?,?)");
                $s2->bind_param("issi", $soalId, $h, $teks, $isBenar);
                $s2->execute();
            }
            $success = "Soal nomor $nomor berhasil ditambahkan.";
        } else {
            $error = "Pertanyaan wajib diisi.";
        }

    // ── Edit soal ────────────────────────────────────────────────────────────
    } elseif ($action === 'edit') {
        $soalId     = (int)($_POST['soal_id'] ?? 0);
        $pertanyaan = trim($_POST['pertanyaan'] ?? '');
        $jawabBenar = strtoupper(trim($_POST['jawab_benar'] ?? 'A'));
        if ($soalId && $pertanyaan) {
            $stmt = $conn->prepare("UPDATE ujian_soal SET pertanyaan=? WHERE id=? AND ujian_id=?");
            $stmt->bind_param("sii", $pertanyaan, $soalId, $ujianId);
            $stmt->execute();

            // Ambil opsi yang sudah ada agar ID-nya tidak berubah
            // (menghindari putusnya referensi opsi_id di tabel ujian_jawaban)
            $existingOpsi = [];
            $resOpsi = $conn->query("SELECT id, huruf FROM ujian_opsi WHERE soal_id=$soalId");
            while ($row = $resOpsi->fetch_assoc()) {
                $existingOpsi[$row['huruf']] = (int)$row['id'];
            }

            foreach (['A','B','C','D','E'] as $h) {
                $teks    = trim($_POST['opsi_' . strtolower($h)] ?? '');
                $isBenar = ($jawabBenar === $h) ? 1 : 0;
                if (isset($existingOpsi[$h])) {
                    if ($teks === '') {
                        $conn->query("DELETE FROM ujian_opsi WHERE id=" . $existingOpsi[$h]);
                    } else {
                        $s2 = $conn->prepare("UPDATE ujian_opsi SET teks=?, is_benar=? WHERE id=?");
                        $s2->bind_param("sii", $teks, $isBenar, $existingOpsi[$h]);
                        $s2->execute();
                    }
                } elseif ($teks !== '') {
                    $s2 = $conn->prepare("INSERT INTO ujian_opsi (soal_id, huruf, teks, is_benar) VALUES (?,?,?,?)");
                    $s2->bind_param("issi", $soalId, $h, $teks, $isBenar);
                    $s2->execute();
                }
            }

            // Sinkronkan is_benar di ujian_jawaban dan hitung ulang nilai peserta
            $benarOpsiId = 0;
            $bRow = $conn->query("SELECT id FROM ujian_opsi WHERE soal_id=$soalId AND is_benar=1 LIMIT 1")->fetch_assoc();
            if ($bRow) $benarOpsiId = (int)$bRow['id'];

            if ($benarOpsiId) {
                $conn->query("UPDATE ujian_jawaban SET is_benar = IF(opsi_id = $benarOpsiId, 1, 0) WHERE soal_id = $soalId");
            } else {
                $conn->query("UPDATE ujian_jawaban SET is_benar = 0 WHERE soal_id = $soalId");
            }

            $affected = $conn->query("SELECT DISTINCT pengerjaan_id FROM ujian_jawaban WHERE soal_id=$soalId")->fetch_all(MYSQLI_ASSOC);
            foreach ($affected as $ap) {
                $pid      = (int)$ap['pengerjaan_id'];
                $benarCnt = (int)$conn->query("SELECT COUNT(*) AS c FROM ujian_jawaban WHERE pengerjaan_id=$pid AND is_benar=1")->fetch_assoc()['c'];
                $totalSoalCnt = (int)$conn->query("SELECT total_soal FROM ujian_pengerjaan WHERE id=$pid LIMIT 1")->fetch_assoc()['total_soal'];
                $nilaiBaru = $totalSoalCnt > 0 ? round(($benarCnt / $totalSoalCnt) * 100, 2) : 0;
                $conn->query("UPDATE ujian_pengerjaan SET total_benar=$benarCnt, nilai=$nilaiBaru WHERE id=$pid");
            }

            $success = "Soal berhasil diperbarui dan nilai peserta telah dihitung ulang.";
        } else {
            $error = "Data tidak valid.";
        }

    // ── Hapus soal ───────────────────────────────────────────────────────────
    } elseif ($action === 'hapus') {
        $soalId = (int)($_POST['soal_id'] ?? 0);
        if ($soalId) {
            $cek = $conn->query("SELECT COUNT(*) AS c FROM ujian_jawaban WHERE soal_id=$soalId")->fetch_assoc();
            if ($cek['c'] > 0) {
                $error = "Soal tidak bisa dihapus karena sudah ada yang menjawab.";
            } else {
                $conn->query("DELETE FROM ujian_opsi WHERE soal_id=$soalId");
                $conn->query("DELETE FROM ujian_soal WHERE id=$soalId AND ujian_id=$ujianId");
                $all = $conn->query("SELECT id FROM ujian_soal WHERE ujian_id=$ujianId ORDER BY nomor_urut ASC")->fetch_all(MYSQLI_ASSOC);
                foreach ($all as $n => $s) {
                    $num = $n + 1;
                    $conn->query("UPDATE ujian_soal SET nomor_urut=$num WHERE id=" . (int)$s['id']);
                }
                $success = "Soal berhasil dihapus.";
            }
        }

    // ── Import file (XLSX atau CSV) ───────────────────────────────────────────
    } elseif ($action === 'import') {
        if (!empty($_FILES['file_import']['tmp_name'])) {
            $tmpFile = $_FILES['file_import']['tmp_name'];
            $ext     = strtolower(pathinfo($_FILES['file_import']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, ['xlsx','csv'])) {
                $error = "Format file harus .xlsx atau .csv.";
            } elseif ($ext === 'csv') {
                $handle   = fopen($tmpFile, 'r');
                $imported = 0;
                $isFirst  = true;
                $maxUrut  = $conn->query("SELECT COALESCE(MAX(nomor_urut),0) AS m FROM ujian_soal WHERE ujian_id=$ujianId")->fetch_assoc()['m'];
                while (($row = fgetcsv($handle)) !== false) {
                    if ($isFirst) { $isFirst = false; continue; }
                    $pertanyaan = trim($row[1] ?? '');
                    if (!$pertanyaan) continue;
                    $opsiA = trim($row[2] ?? '');
                    $opsiB = trim($row[3] ?? '');
                    $opsiC = trim($row[4] ?? '');
                    $opsiD = trim($row[5] ?? '');
                    $opsiE = trim($row[6] ?? '');
                    $jawabBenar = strtoupper(trim($row[7] ?? 'A'));
                    $maxUrut++;
                    $stmt = $conn->prepare("INSERT INTO ujian_soal (ujian_id, nomor_urut, pertanyaan) VALUES (?,?,?)");
                    $stmt->bind_param("iis", $ujianId, $maxUrut, $pertanyaan);
                    $stmt->execute();
                    $soalId = $conn->insert_id;
                    foreach (['A'=>$opsiA,'B'=>$opsiB,'C'=>$opsiC,'D'=>$opsiD,'E'=>$opsiE] as $h => $teks) {
                        if ($teks === '') continue;
                        $isBenar = ($jawabBenar === $h) ? 1 : 0;
                        $s2 = $conn->prepare("INSERT INTO ujian_opsi (soal_id, huruf, teks, is_benar) VALUES (?,?,?,?)");
                        $s2->bind_param("issi", $soalId, $h, $teks, $isBenar);
                        $s2->execute();
                    }
                    $imported++;
                }
                fclose($handle);
                $success = "$imported soal berhasil diimport dari CSV.";
            } else {
                if (!class_exists('ZipArchive') && !function_exists('exec')) {
                    $error = "Ekstensi php-zip tidak aktif di server ini.";
                } else {
                    $rows = parseXlsxSoalUjian($tmpFile);
                    if ($rows === false) {
                        $error = "Gagal membaca file Excel. Pastikan format sesuai template.";
                    } else {
                        $imported = 0;
                        $maxUrut  = $conn->query("SELECT COALESCE(MAX(nomor_urut),0) AS m FROM ujian_soal WHERE ujian_id=$ujianId")->fetch_assoc()['m'];
                        foreach ($rows as $i => $row) {
                            $pertanyaan = trim($row['B']);
                            if ($i === 0 && (mb_strtolower($pertanyaan) === 'pertanyaan' || $pertanyaan === '')) continue;
                            if (!$pertanyaan) continue;
                            $opsiA = trim($row['C']);
                            $opsiB = trim($row['D']);
                            $opsiC = trim($row['E']);
                            $opsiD = trim($row['F']);
                            $opsiE = trim($row['G']);
                            $jawabBenar = strtoupper(trim($row['H'] ?? 'A'));
                            $maxUrut++;
                            $stmt = $conn->prepare("INSERT INTO ujian_soal (ujian_id, nomor_urut, pertanyaan) VALUES (?,?,?)");
                            $stmt->bind_param("iis", $ujianId, $maxUrut, $pertanyaan);
                            $stmt->execute();
                            $soalId = $conn->insert_id;
                            foreach (['A'=>$opsiA,'B'=>$opsiB,'C'=>$opsiC,'D'=>$opsiD,'E'=>$opsiE] as $h => $teks) {
                                if ($teks === '') continue;
                                $isBenar = ($jawabBenar === $h) ? 1 : 0;
                                $s2 = $conn->prepare("INSERT INTO ujian_opsi (soal_id, huruf, teks, is_benar) VALUES (?,?,?,?)");
                                $s2->bind_param("issi", $soalId, $h, $teks, $isBenar);
                                $s2->execute();
                            }
                            $imported++;
                        }
                        $success = "$imported soal berhasil diimport dari Excel.";
                    }
                }
            }
        } else {
            $error = "Pilih file terlebih dahulu.";
        }
    }
}

// ─── Fetch Soal ───────────────────────────────────────────────────────────────
$soalList = $conn->query("SELECT * FROM ujian_soal WHERE ujian_id=$ujianId ORDER BY nomor_urut ASC")->fetch_all(MYSQLI_ASSOC);
foreach ($soalList as &$soal) {
    $sid = (int)$soal['id'];
    $soal['opsi'] = $conn->query("SELECT * FROM ujian_opsi WHERE soal_id=$sid ORDER BY huruf ASC")->fetch_all(MYSQLI_ASSOC);
    $soal['jawab_benar'] = '';
    foreach ($soal['opsi'] as $op) {
        if ($op['is_benar']) { $soal['jawab_benar'] = $op['huruf']; break; }
    }
}
unset($soal);
$totalSoal = count($soalList);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soal Ujian — <?= htmlspecialchars($ujianRow['judul']) ?> — Admin</title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        .soal-card { border-left: 4px solid #f79039; }
        .opsi-item { background:#f8f9fa; border-radius:6px; padding:7px 12px; margin-bottom:5px; font-size:.85rem; display:flex; align-items:center; gap:8px; }
        .opsi-benar { background:#d1fae5; border-left:3px solid #16a34a; }
        .huruf-badge { width:22px; height:22px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.72rem; background:#e9ecef; color:#555; flex-shrink:0; }
        .huruf-benar { background:#16a34a; color:#fff; }
    </style>
</head>
<body>
<?php include '_sidebar.php'; ?>
<div class="main-content">
    <header class="topbar">
        <button class="topbar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
        <div>
            <div class="topbar-title">
                <i class="bi bi-list-check me-2" style="color:#f79039"></i>
                <a href="ujian.php" class="text-decoration-none text-muted small me-1">Ujian</a>
                <span class="text-muted small me-1">/</span>
                <?= htmlspecialchars($ujianRow['judul']) ?>
            </div>
            <div class="topbar-sub"><?= $totalSoal ?> soal tersedia &nbsp;·&nbsp;
                <a href="../ujian.php?id=<?= $ujianId ?>" target="_blank" class="text-decoration-none" style="color:#f79039">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Lihat Halaman Ujian
                </a>
            </div>
        </div>
        <div class="topbar-right d-flex gap-2">
            <?php if (isSuperAdmin()): ?>
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalImport">
                <i class="bi bi-upload me-1"></i>Import Excel/CSV
            </button>
            <a href="ujian_soal.php?id=<?= $ujianId ?>&template=1" class="btn btn-sm btn-outline-success">
                <i class="bi bi-download me-1"></i>Template
            </a>
            <button class="btn btn-sm btn-warning fw-bold" data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="bi bi-plus-lg me-1"></i>Tambah Soal
            </button>
            <?php endif; ?>
            <a href="ujian.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </header>

    <div class="page-body">
        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show py-2 mb-3">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php elseif ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2 mb-3">
            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isSuperAdmin()): ?>
        <div class="alert alert-info py-2 mb-3 small">
            <i class="bi bi-info-circle me-1"></i>
            <strong>Format Excel/CSV:</strong>
            Kolom A = nomor, B = pertanyaan, C = opsi A, D = opsi B, E = opsi C, F = opsi D, G = opsi E, H = jawaban benar (A/B/C/D/E).
            <a href="ujian_soal.php?id=<?= $ujianId ?>&template=1" class="alert-link">Download template CSV</a>.
        </div>
        <?php endif; ?>

        <?php if (empty($soalList)): ?>
        <div class="card stat-card text-center py-5">
            <div class="text-muted">
                <i class="bi bi-journal-x fs-2 d-block mb-2"></i>
                Belum ada soal untuk ujian ini.
                <?php if (isSuperAdmin()): ?>Klik <strong>Tambah Soal</strong> atau <strong>Import Excel/CSV</strong>.<?php endif; ?>
            </div>
        </div>
        <?php else: ?>

        <?php foreach ($soalList as $soal): ?>
        <div class="card stat-card soal-card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge fw-bold" style="background:#f79039">No. <?= $soal['nomor_urut'] ?></span>
                        <?php if ($soal['jawab_benar']): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                            Jawaban: <?= htmlspecialchars($soal['jawab_benar']) ?>
                        </span>
                        <?php endif; ?>
                        <span class="text-muted small"><?= count($soal['opsi']) ?> pilihan</span>
                    </div>
                    <?php if (isSuperAdmin()): ?>
                    <div class="d-flex gap-1 flex-shrink-0">
                        <button class="btn btn-sm btn-outline-warning"
                                onclick="editSoal(<?= htmlspecialchars(json_encode($soal)) ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" onsubmit="return confirm('Hapus soal ini?');" style="display:inline">
                            <input type="hidden" name="action" value="hapus">
                            <input type="hidden" name="soal_id" value="<?= $soal['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
                <p class="fw-semibold mb-2" style="font-size:.95rem;"><?= nl2br(htmlspecialchars($soal['pertanyaan'])) ?></p>
                <div class="row g-2">
                    <?php foreach ($soal['opsi'] as $opsi): ?>
                    <div class="col-md-6">
                        <div class="opsi-item <?= $opsi['is_benar'] ? 'opsi-benar' : '' ?>">
                            <span class="huruf-badge <?= $opsi['is_benar'] ? 'huruf-benar' : '' ?>"><?= htmlspecialchars($opsi['huruf']) ?></span>
                            <span><?= htmlspecialchars($opsi['teks']) ?></span>
                            <?php if ($opsi['is_benar']): ?>
                            <i class="bi bi-check-circle-fill text-success ms-auto"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ─── Modal Tambah Soal ─────────────────────────────────────────────────── -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background:#f79039; color:#fff;">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Tambah Soal</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="tambah">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pertanyaan <span class="text-danger">*</span></label>
                        <textarea name="pertanyaan" class="form-control" rows="3" placeholder="Tulis pertanyaan di sini..." required></textarea>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold">Opsi Jawaban <span class="text-danger">*</span></label>
                        <p class="text-muted small mb-2">Isi pilihan jawaban dan tandai <span class="badge bg-success-subtle text-success border border-success-subtle px-2">✓ Benar</span> pada satu opsi yang merupakan jawaban benar.</p>
                    </div>
                    <?php foreach (['A','B','C','D','E'] as $idx => $_h): $isReq = $_h <= 'B' ? 'required' : ''; ?>
                    <div class="input-group mb-2 align-items-center tambah-opsi-row" id="tambah_row_<?= $_h ?>">
                        <div class="input-group-text px-2" style="background:transparent;border-right:0;cursor:pointer;" title="Tandai sebagai jawaban benar">
                            <input type="radio" name="jawab_benar" value="<?= $_h ?>" id="tambah_benar_<?= strtolower($_h) ?>"
                                   class="form-check-input mt-0"
                                   <?= $_h === 'A' ? 'checked' : '' ?>
                                   style="width:1.1em;height:1.1em;accent-color:#16a34a;">
                        </div>
                        <span class="input-group-text fw-bold" style="min-width:36px;justify-content:center;background:#f8f9fa;">
                            <?= $_h ?>
                        </span>
                        <input type="text" name="opsi_<?= strtolower($_h) ?>" class="form-control"
                               placeholder="Pilihan <?= $_h ?><?= $_h > 'B' ? ' (opsional)' : '' ?>"
                               <?= $isReq ?>>
                    </div>
                    <?php endforeach; ?>
                    <div class="alert alert-success py-2 mt-1 mb-0 small" id="tambah_benar_info">
                        <i class="bi bi-check-circle-fill me-1"></i>Jawaban benar: <strong id="tambah_benar_label">A</strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning fw-bold">
                        <i class="bi bi-plus-lg me-1"></i>Tambah Soal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ─── Modal Edit Soal ───────────────────────────────────────────────────── -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background:#f79039; color:#fff;">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Soal</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="soal_id" id="edit_soal_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pertanyaan <span class="text-danger">*</span></label>
                        <textarea name="pertanyaan" id="edit_pertanyaan" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold">Opsi Jawaban <span class="text-danger">*</span></label>
                        <p class="text-muted small mb-2">Tandai <span class="badge bg-success-subtle text-success border border-success-subtle px-2">✓ Benar</span> pada satu opsi yang merupakan jawaban benar.</p>
                    </div>
                    <?php foreach (['A','B','C','D','E'] as $_h): ?>
                    <div class="input-group mb-2 align-items-center">
                        <div class="input-group-text px-2" style="background:transparent;border-right:0;cursor:pointer;" title="Tandai sebagai jawaban benar">
                            <input type="radio" name="jawab_benar" value="<?= $_h ?>"
                                   id="edit_benar_<?= strtolower($_h) ?>"
                                   class="form-check-input mt-0"
                                   style="width:1.1em;height:1.1em;accent-color:#16a34a;">
                        </div>
                        <span class="input-group-text fw-bold" style="min-width:36px;justify-content:center;background:#f8f9fa;">
                            <?= $_h ?>
                        </span>
                        <input type="text" name="opsi_<?= strtolower($_h) ?>" id="edit_opsi_<?= strtolower($_h) ?>" class="form-control"
                               placeholder="Pilihan <?= $_h ?>">
                    </div>
                    <?php endforeach; ?>
                    <div class="alert alert-success py-2 mt-1 mb-0 small" id="edit_benar_info">
                        <i class="bi bi-check-circle-fill me-1"></i>Jawaban benar: <strong id="edit_benar_label">A</strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning fw-bold">
                        <i class="bi bi-save me-1"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ─── Modal Import ──────────────────────────────────────────────────────── -->
<div class="modal fade" id="modalImport" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:#f79039; color:#fff;">
                <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Import Soal</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import">
                <div class="modal-body">
                    <div class="alert alert-info py-2 small">
                        <strong>Format kolom:</strong><br>
                        A = nomor &nbsp;|&nbsp; B = pertanyaan &nbsp;|&nbsp; C = opsi A &nbsp;|&nbsp; D = opsi B &nbsp;|&nbsp; E = opsi C &nbsp;|&nbsp; F = opsi D &nbsp;|&nbsp; G = opsi E &nbsp;|&nbsp; H = jawaban benar (A/B/C/D/E)
                        <br><a href="ujian_soal.php?id=<?= $ujianId ?>&template=1" class="alert-link">Download template CSV</a>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih File <span class="text-danger">*</span></label>
                        <input type="file" name="file_import" class="form-control" accept=".xlsx,.csv" required>
                        <div class="form-text">Format yang didukung: .xlsx (Excel) atau .csv</div>
                    </div>
                    <div class="alert alert-warning py-2 small mb-0">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Soal akan ditambahkan ke soal yang sudah ada (tidak menimpa).
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning fw-bold">
                        <i class="bi bi-upload me-1"></i>Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
// ── Tambah modal: update info label when radio changes ─────────────────────
document.querySelectorAll('#modalTambah input[name="jawab_benar"]').forEach(r => {
    r.addEventListener('change', () => {
        const lbl = document.getElementById('tambah_benar_label');
        if (lbl) lbl.textContent = r.value;
    });
});

// ── Edit modal: update info label when radio changes ───────────────────────
document.querySelectorAll('#modalEdit input[name="jawab_benar"]').forEach(r => {
    r.addEventListener('change', () => {
        const lbl = document.getElementById('edit_benar_label');
        if (lbl) lbl.textContent = r.value;
    });
});

function editSoal(soal) {
    document.getElementById('edit_soal_id').value = soal.id;
    document.getElementById('edit_pertanyaan').value = soal.pertanyaan;

    // Reset semua opsi
    ['a','b','c','d','e'].forEach(h => {
        const inp = document.getElementById('edit_opsi_' + h);
        if (inp) inp.value = '';
    });

    // Isi teks opsi
    (soal.opsi || []).forEach(op => {
        const el = document.getElementById('edit_opsi_' + op.huruf.toLowerCase());
        if (el) el.value = op.teks;
    });

    // Set radio jawaban benar
    const benar = soal.jawab_benar || 'A';
    const radioEl = document.getElementById('edit_benar_' + benar.toLowerCase());
    if (radioEl) radioEl.checked = true;
    const lbl = document.getElementById('edit_benar_label');
    if (lbl) lbl.textContent = benar;

    new bootstrap.Modal(document.getElementById('modalEdit')).show();
}
</script>
</body>
</html>
