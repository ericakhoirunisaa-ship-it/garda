<?php
require_once 'config.php';
requireLogin();

$kecMap = [
    "010" => "Dampal Selatan",
    "020" => "Dampal Utara",
    "030" => "Dondo",
    "031" => "Ogodeide",
    "032" => "Basidondo",
    "040" => "Baolan",
    "041" => "Lampasio",
    "050" => "Galang",
    "060" => "Tolitoli Utara",
    "061" => "Dako Pemean",
];

// Fetch all ujian for filter dropdown
$ujianAll = $conn->query("SELECT id, judul FROM ujian ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

// ─── Excel Export (XLSX) ──────────────────────────────────────────────────────
if (isset($_GET['export'])) {
    $expUjian = (int)($_GET['ujian_id'] ?? 0);
    $expKec   = $_GET['kec'] ?? 'all';
    $expQ     = trim($_GET['q'] ?? '');
    $expKelas = $_GET['kelas'] ?? 'all';

    $expWhere = "1=1";
    if ($expUjian > 0) $expWhere .= " AND p.ujian_id = $expUjian";
    if ($expKec !== 'all' && isset($kecMap[$expKec])) {
        $expWhere .= " AND p.kecamatan = '" . $conn->real_escape_string($expKec) . "'";
    }
    if ($expKelas !== 'all' && $expKelas !== '') {
        $expWhere .= " AND p.kelas = '" . $conn->real_escape_string($expKelas) . "'";
    }
    if ($expQ !== '') {
        $expWhere .= " AND p.nama LIKE '%" . $conn->real_escape_string($expQ) . "%'";
    }

    $expRows = $conn->query("
        SELECT p.*, u.judul AS judul_ujian
        FROM ujian_pengerjaan p
        LEFT JOIN ujian u ON u.id = p.ujian_id
        WHERE $expWhere
        ORDER BY u.judul ASC, p.kelas ASC, p.nilai DESC, p.waktu DESC
    ")->fetch_all(MYSQLI_ASSOC);

    $ujianLabel = '';
    $kelasLabel = ($expKelas !== 'all' && $expKelas !== '') ? '_Kelas' . $expKelas : '';
    if ($expUjian > 0) {
        foreach ($ujianAll as $uj) {
            if ((int)$uj['id'] === $expUjian) {
                $ujianLabel = '_' . preg_replace('/[^A-Za-z0-9]/', '_', $uj['judul']);
                break;
            }
        }
    }
    $filename = 'Hasil_Ujian' . $ujianLabel . $kelasLabel . '_' . date('Ymd_His') . '.xlsx';

    // ── Pure-PHP ZIP builder (tidak butuh ekstensi ZipArchive) ──────────────────
    function zipBuild(array $files): string {
        $local = '';
        $cd    = '';
        $off   = 0;
        foreach ($files as $name => $data) {
            $nl  = strlen($name);
            $dl  = strlen($data);
            $crc = crc32($data);
            $local .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, 0, 0, $crc, $dl, $dl, $nl, 0) . $name . $data;
            $cd    .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, 0, 0, $crc, $dl, $dl, $nl, 0, 0, 0, 0, 0, $off) . $name;
            $off   += 30 + $nl + $dl;
        }
        $cdl  = strlen($cd);
        $cnt  = count($files);
        return $local . $cd . pack('VvvvvVVv', 0x06054b50, 0, 0, $cnt, $cnt, $cdl, $off, 0);
    }

    // ── XML helpers ──────────────────────────────────────────────────────────────
    function xesc(string $v): string {
        return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
    function xlCell(string $col, int $row, $value, int $s): string {
        $r = $col . $row;
        if (is_int($value) || is_float($value))
            return '<c r="' . $r . '" s="' . $s . '"><v>' . $value . '</v></c>';
        return '<c r="' . $r . '" t="inlineStr" s="' . $s . '"><is><t>' . xesc((string)$value) . '</t></is></c>';
    }

    // ── Build XML parts ───────────────────────────────────────────────────────
    $xmlCT = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
        . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
        . '</Types>';

    $xmlRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '</Relationships>';

    $xmlWb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheets><sheet name="Hasil Ujian" sheetId="1" r:id="rId1"/></sheets>'
        . '</workbook>';

    $xmlWbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
        . '</Relationships>';

    $xmlStyles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
        . '<fills count="3">'
        . '<fill><patternFill patternType="none"/></fill>'
        . '<fill><patternFill patternType="gray125"/></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFF79039"/><bgColor indexed="64"/></patternFill></fill>'
        . '</fills>'
        . '<borders count="2">'
        . '<border><left/><right/><top/><bottom/><diagonal/></border>'
        . '<border><left style="thin"><color auto="1"/></left><right style="thin"><color auto="1"/></right>'
        . '<top style="thin"><color auto="1"/></top><bottom style="thin"><color auto="1"/></bottom><diagonal/></border>'
        . '</borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="5">'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
        . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"><alignment vertical="top" wrapText="1"/></xf>'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"><alignment horizontal="center" vertical="top"/></xf>'
        . '<xf numFmtId="2" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyNumberFormat="1"><alignment horizontal="right" vertical="top"/></xf>'
        . '</cellXfs>'
        . '</styleSheet>';

    $cols    = ['A','B','C','D','E','F','G','H','I','J','K','L'];
    $headers = ['No','Judul Ujian','Kelas','Peran','Nama','NIK','Kecamatan','Nilai','Benar','Salah','Total Soal','Waktu Pengerjaan'];

    $xmlSheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

    $xmlSheet .= '<row r="1">';
    foreach ($headers as $ci => $h) $xmlSheet .= xlCell($cols[$ci], 1, $h, 1);
    $xmlSheet .= '</row>';

    foreach ($expRows as $i => $r) {
        $rowNum  = $i + 2;
        $salah   = (int)$r['total_soal'] - (int)$r['total_benar'];
        $kecNama = $kecMap[$r['kecamatan']] ?? $r['kecamatan'];
        $xmlSheet .= '<row r="' . $rowNum . '">'
            . xlCell('A', $rowNum, $i + 1, 3)
            . xlCell('B', $rowNum, $r['judul_ujian'] ?? '', 2)
            . xlCell('C', $rowNum, $r['kelas'] ?? '', 3)
            . xlCell('D', $rowNum, $r['peran'] ?? '', 3)
            . xlCell('E', $rowNum, $r['nama'], 2)
            . xlCell('F', $rowNum, $r['nik'] ?? '', 2)
            . xlCell('G', $rowNum, $kecNama, 2)
            . xlCell('H', $rowNum, (float)$r['nilai'], 4)
            . xlCell('I', $rowNum, (int)$r['total_benar'], 3)
            . xlCell('J', $rowNum, $salah, 3)
            . xlCell('K', $rowNum, (int)$r['total_soal'], 3)
            . xlCell('L', $rowNum, date('d/m/Y H:i', strtotime($r['waktu'])), 2)
            . '</row>';
    }
    $xmlSheet .= '</sheetData></worksheet>';

    $xlsxContent = zipBuild([
        '[Content_Types].xml'        => $xmlCT,
        '_rels/.rels'                => $xmlRels,
        'xl/workbook.xml'            => $xmlWb,
        'xl/_rels/workbook.xml.rels' => $xmlWbRels,
        'xl/styles.xml'              => $xmlStyles,
        'xl/worksheets/sheet1.xml'   => $xmlSheet,
    ]);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($xlsxContent));
    header('Cache-Control: max-age=0');
    echo $xlsxContent;
    exit;
}

// ─── AJAX: detail jawaban peserta ─────────────────────────────────────────────
if (isset($_GET['ajax_detail'])) {
    $pid = (int)($_GET['pengerjaan_id'] ?? 0);
    if (!$pid) { echo json_encode([]); exit; }

    $pengerjaan = $conn->query("SELECT p.*, u.judul AS judul_ujian FROM ujian_pengerjaan p LEFT JOIN ujian u ON u.id=p.ujian_id WHERE p.id=$pid LIMIT 1")->fetch_assoc();
    if (!$pengerjaan) { echo json_encode([]); exit; }

    $jawaban = $conn->query("
        SELECT j.*, s.pertanyaan, s.nomor_urut,
               op_pilih.huruf AS huruf_dipilih, op_pilih.teks AS teks_dipilih,
               op_benar.huruf AS huruf_benar, op_benar.teks AS teks_benar
        FROM ujian_jawaban j
        JOIN ujian_soal s ON s.id = j.soal_id
        LEFT JOIN ujian_opsi op_pilih ON op_pilih.id = j.opsi_id
        LEFT JOIN ujian_opsi op_benar ON op_benar.soal_id = j.soal_id AND op_benar.is_benar = 1
        WHERE j.pengerjaan_id = $pid
        ORDER BY s.nomor_urut ASC
    ")->fetch_all(MYSQLI_ASSOC);

    header('Content-Type: application/json');
    echo json_encode(['pengerjaan' => $pengerjaan, 'jawaban' => $jawaban, 'kecMap' => $kecMap]);
    exit;
}

// ─── AJAX: edit nama peserta ─────────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'edit_nama') {
    $editId   = (int)($_POST['id'] ?? 0);
    $namaBaru = trim($_POST['nama'] ?? '');
    if ($editId > 0 && $namaBaru !== '') {
        $stmt = $conn->prepare("UPDATE ujian_pengerjaan SET nama=? WHERE id=?");
        $stmt->bind_param("si", $namaBaru, $editId);
        $stmt->execute();
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'msg' => 'Data tidak valid']);
    }
    exit;
}

// ─── AJAX: hapus pengerjaan ──────────────────────────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'hapus') {
    $delId = (int)($_POST['id'] ?? 0);
    if ($delId > 0) {
        $conn->query("DELETE FROM ujian_jawaban WHERE pengerjaan_id=$delId");
        $conn->query("DELETE FROM ujian_pengerjaan WHERE id=$delId");
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false]);
    }
    exit;
}

// ─── Filter ───────────────────────────────────────────────────────────────────
$filterKec   = $_GET['kec'] ?? 'all';
$filterUjian = (int)($_GET['ujian_id'] ?? 0);
$filterKelas = $_GET['kelas'] ?? 'all';
$search      = trim($_GET['q'] ?? '');

$where = "1=1";
if ($filterKec !== 'all' && isset($kecMap[$filterKec])) {
    $fk    = $conn->real_escape_string($filterKec);
    $where .= " AND p.kecamatan = '$fk'";
}
if ($filterUjian > 0) {
    $where .= " AND p.ujian_id = $filterUjian";
}
if ($filterKelas !== 'all' && $filterKelas !== '') {
    $fkl   = $conn->real_escape_string($filterKelas);
    $where .= " AND p.kelas = '$fkl'";
}
if ($search !== '') {
    $esc   = $conn->real_escape_string($search);
    $where .= " AND p.nama LIKE '%$esc%'";
}

// ─── Stats (scoped to current filter) ────────────────────────────────────────
$statsRow = $conn->query("SELECT COUNT(*) AS total, COALESCE(AVG(nilai),0) AS rata, COALESCE(MAX(nilai),0) AS tertinggi FROM ujian_pengerjaan p WHERE $where")->fetch_assoc();
$totalPeserta   = (int)$statsRow['total'];
$rataRata       = round((float)$statsRow['rata'], 2);
$nilaiTertinggi = round((float)$statsRow['tertinggi'], 2);

$totalRows = (int)$conn->query("SELECT COUNT(*) AS c FROM ujian_pengerjaan p WHERE $where")->fetch_assoc()['c'];

$hasilList = $conn->query("
    SELECT p.*, u.judul AS judul_ujian
    FROM ujian_pengerjaan p
    LEFT JOIN ujian u ON u.id = p.ujian_id
    WHERE $where
    ORDER BY u.judul ASC, p.kelas ASC, p.nilai DESC, p.waktu DESC
")->fetch_all(MYSQLI_ASSOC);

// Kelompokkan per judul ujian → per kelas, simpan juga ujian_id per judul
$hasilGrouped  = [];
$ujianIdByJudul = [];
foreach ($hasilList as $h) {
    $judul = $h['judul_ujian'] ?? 'Tanpa Judul';
    $kls   = $h['kelas'] ?: '—';
    $hasilGrouped[$judul][$kls][] = $h;
    if (!isset($ujianIdByJudul[$judul])) {
        $ujianIdByJudul[$judul] = (int)$h['ujian_id'];
    }
}

function getGradeH(float $nilai): array {
    if ($nilai >= 90) return ['A', 'success'];
    if ($nilai >= 75) return ['B', 'primary'];
    if ($nilai >= 60) return ['C', 'warning'];
    if ($nilai >= 45) return ['D', 'danger'];
    return ['E', 'secondary'];
}

function buildQuery(array $extra = []): string {
    $params = array_merge([
        'ujian_id' => $_GET['ujian_id'] ?? '',
        'kec'      => $_GET['kec'] ?? 'all',
        'kelas'    => $_GET['kelas'] ?? 'all',
        'q'        => $_GET['q'] ?? '',
    ], $extra);
    return '?' . http_build_query(array_filter($params, fn($v) => $v !== '' && $v !== 'all' && $v !== '0' && $v !== 0));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Ujian Publik — Admin</title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        .jawaban-item { padding: 8px 12px; border-radius: 8px; margin-bottom: 6px; font-size: .875rem; }
        .jawaban-benar { background: #d1fae5; border-left: 3px solid #16a34a; }
        .jawaban-salah { background: #fee2e2; border-left: 3px solid #dc2626; }
        .jawaban-kosong { background: #f3f4f6; border-left: 3px solid #9ca3af; }
        .nilai-bar { height: 6px; border-radius: 3px; }
    </style>
</head>
<body>
<?php include '_sidebar.php'; ?>
<div class="main-content">
    <header class="topbar">
        <button class="topbar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
        <div>
            <div class="topbar-title">
                <i class="bi bi-clipboard2-data-fill me-2" style="color:#f79039"></i>Hasil Ujian Publik
            </div>
            <div class="topbar-sub"><?= $totalRows ?> pengerjaan &nbsp;·&nbsp; Rata-rata: <strong><?= $rataRata ?></strong></div>
        </div>
        <div class="topbar-right d-flex gap-2">
            <a href="ujian.php" class="btn btn-sm btn-outline-warning">
                <i class="bi bi-journal-check me-1"></i>Kelola Ujian
            </a>
        </div>
    </header>

    <div class="page-body">

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <div class="card stat-card p-3 text-center">
                    <div class="fw-bold fs-3" style="color:#f79039"><?= $totalPeserta ?></div>
                    <small class="text-muted">Total Pengerjaan</small>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card stat-card p-3 text-center">
                    <div class="fw-bold fs-3 text-primary"><?= $rataRata ?></div>
                    <small class="text-muted">Rata-rata Nilai</small>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card stat-card p-3 text-center">
                    <div class="fw-bold fs-3 text-success"><?= $nilaiTertinggi ?></div>
                    <small class="text-muted">Nilai Tertinggi</small>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="card stat-card mb-4">
            <div class="card-body py-3">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-sm-3">
                        <label class="form-label small fw-semibold mb-1">Judul Ujian</label>
                        <select name="ujian_id" class="form-select form-select-sm">
                            <option value="">Semua Ujian</option>
                            <?php foreach ($ujianAll as $uj): ?>
                            <option value="<?= $uj['id'] ?>" <?= $filterUjian === (int)$uj['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($uj['judul']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label small fw-semibold mb-1">Kelas</label>
                        <select name="kelas" class="form-select form-select-sm">
                            <option value="all" <?= $filterKelas === 'all' ? 'selected' : '' ?>>Semua Kelas</option>
                            <?php foreach (['A','B','C','D','E','F','G','H','I'] as $_kl): ?>
                            <option value="<?= $_kl ?>" <?= $filterKelas === $_kl ? 'selected' : '' ?>>
                                Kelas <?= $_kl ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <label class="form-label small fw-semibold mb-1">Kecamatan</label>
                        <select name="kec" class="form-select form-select-sm">
                            <option value="all" <?= $filterKec === 'all' ? 'selected' : '' ?>>Semua Kecamatan</option>
                            <?php foreach ($kecMap as $kode => $nm): ?>
                            <option value="<?= $kode ?>" <?= $filterKec === $kode ? 'selected' : '' ?>>
                                <?= htmlspecialchars($nm) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label small fw-semibold mb-1">Cari Nama</label>
                        <input type="text" name="q" class="form-control form-select-sm"
                               placeholder="Nama peserta..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-sm-2 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-warning fw-bold flex-fill">
                            <i class="bi bi-search me-1"></i>Filter
                        </button>
                        <a href="ujian_hasil.php" class="btn btn-sm btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Hasil per Ujian → per Kelas -->
        <?php if (empty($hasilList)): ?>
        <div class="card stat-card text-center py-5">
            <div class="text-muted"><i class="bi bi-inbox fs-2 d-block mb-2"></i>Belum ada data pengerjaan ujian.</div>
        </div>
        <?php else: ?>
        <?php foreach ($hasilGrouped as $judulUjian => $kelasList): ?>

        <!-- Header Ujian -->
        <div class="d-flex align-items-center gap-2 mb-2 mt-3 flex-wrap">
            <i class="bi bi-journal-check fs-5" style="color:#f79039"></i>
            <span class="fw-bold fs-6" style="color:#c05300;"><?= htmlspecialchars($judulUjian) ?></span>
            <span class="badge rounded-pill px-3 ms-1" style="background:#f79039; font-size:.78rem;">
                <?= array_sum(array_map('count', $kelasList)) ?> peserta
            </span>
            <a href="ujian_hasil.php<?= buildQuery(['ujian_id' => $ujianIdByJudul[$judulUjian], 'export' => '1']) ?>"
               class="btn btn-sm btn-success fw-bold ms-auto">
                <i class="bi bi-file-earmark-excel-fill me-1"></i>Export Excel
            </a>
        </div>

        <?php ksort($kelasList); foreach ($kelasList as $kelasLabel => $rows): ?>
        <div class="card stat-card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between py-2 px-3"
                 style="background:#f0f7ff; border-bottom:2px solid #3b82f6;">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge fw-bold px-3 py-2" style="background:#3b82f6; font-size:.85rem;">
                        Kelas <?= htmlspecialchars($kelasLabel) ?>
                    </span>
                </div>
                <span class="badge rounded-pill px-3 bg-light text-dark border" style="font-size:.78rem;">
                    <?= count($rows) ?> peserta
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width:44px">No</th>
                                <th>Nama</th>
                                <th style="width:65px" class="text-center">Peran</th>
                                <th>Kecamatan</th>
                                <th class="text-center">Nilai</th>
                                <th class="text-center">Benar</th>
                                <th>Waktu</th>
                                <th class="text-center" style="width:130px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($rows as $i => $h): ?>
                        <?php [$grade, $color] = getGradeH((float)$h['nilai']); ?>
                        <tr>
                            <td class="ps-3 text-muted small"><?= $i + 1 ?></td>
                            <td class="fw-semibold" data-nama-id="<?= $h['id'] ?>"><?= htmlspecialchars($h['nama']) ?></td>
                            <td class="text-center">
                                <?php if ($h['peran']): ?>
                                <span class="badge <?= $h['peran'] === 'PML Sensus' ? 'bg-primary' : 'bg-success' ?>" style="font-size:.75rem;">
                                    <?= htmlspecialchars($h['peran']) ?>
                                </span>
                                <?php else: ?>
                                <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= htmlspecialchars($kecMap[$h['kecamatan']] ?? $h['kecamatan']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <span class="badge bg-<?= $color ?> fw-bold" style="font-size:.82rem; min-width:30px;"><?= $grade ?></span>
                                    <span class="fw-bold"><?= number_format((float)$h['nilai'], 2) ?></span>
                                </div>
                                <div class="progress mt-1" style="height:4px; max-width:80px; margin:0 auto;">
                                    <div class="progress-bar bg-<?= $color ?>" style="width:<?= $h['nilai'] ?>%"></div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="text-success fw-bold"><?= $h['total_benar'] ?></span>
                                <span class="text-muted">/<?= $h['total_soal'] ?></span>
                            </td>
                            <td class="small text-muted">
                                <?= date('d/m/Y H:i', strtotime($h['waktu'])) ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-info me-1"
                                        onclick="lihatDetail(<?= $h['id'] ?>)" title="Detail">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-warning me-1"
                                        onclick="editNama(<?= $h['id'] ?>, <?= htmlspecialchars(json_encode($h['nama'])) ?>)" title="Edit Nama">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger"
                                        onclick="hapus(<?= $h['id'] ?>, this)" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ─── Modal Detail Jawaban ──────────────────────────────────────────────── -->
<div class="modal fade" id="modalDetail" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background:#f79039; color:#fff;">
                <h5 class="modal-title" id="modalDetailTitle">Detail Jawaban</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalDetailBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-warning" role="status"></div>
                    <p class="mt-2 text-muted small">Memuat data...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ─── Modal Edit Nama Peserta ───────────────────────────────────────────── -->
<div class="modal fade" id="modalEditNama" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:#f79039; color:#fff;">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Nama Peserta</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editNamaId">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Peserta</label>
                    <input type="text" id="editNamaInput" class="form-control" placeholder="Nama peserta">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn fw-bold" style="background:#f79039;color:#fff;" onclick="simpanEditNama()">
                    <i class="bi bi-save me-1"></i>Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
const kecMap = <?= json_encode($kecMap) ?>;

function lihatDetail(id) {
    const modal = new bootstrap.Modal(document.getElementById('modalDetail'));
    document.getElementById('modalDetailTitle').textContent = 'Detail Jawaban';
    document.getElementById('modalDetailBody').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-warning" role="status"></div>
            <p class="mt-2 text-muted small">Memuat data...</p>
        </div>`;
    modal.show();

    fetch('ujian_hasil.php?ajax_detail=1&pengerjaan_id=' + id)
        .then(r => r.json())
        .then(data => {
            if (!data.pengerjaan) {
                document.getElementById('modalDetailBody').innerHTML = '<p class="text-muted text-center py-3">Data tidak ditemukan.</p>';
                return;
            }
            const p = data.pengerjaan;
            const kec = kecMap[p.kecamatan] || p.kecamatan;
            const grade = getGrade(parseFloat(p.nilai));
            const kelasBadge = p.kelas ? `<span class="badge fw-bold px-2 me-1" style="background:#3b82f6;font-size:.78rem;">Kelas ${escHtml(p.kelas)}</span>` : '';
            const peranBadge = p.peran ? `<span class="badge px-2 ${p.peran === 'PML Sensus' ? 'bg-primary' : 'bg-success'}" style="font-size:.78rem;">${escHtml(p.peran)}</span>` : '';

            let html = `
            <div class="text-center mb-4">
                <div style="width:80px;height:80px;border-radius:50%;border:4px solid ${grade[1]};color:${grade[1]};
                            display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:1.8rem;font-weight:900;">
                    ${grade[0]}
                </div>
                <div class="small text-muted mb-1" style="color:#f79039!important;font-weight:600;">${escHtml(p.judul_ujian || '')}</div>
                <h5 class="fw-bold mb-1">${escHtml(p.nama)}</h5>
                <div class="d-flex justify-content-center gap-1 mb-2">${kelasBadge}${peranBadge}</div>
                <p class="text-muted small mb-1">${p.nik ? '<span class="font-monospace">NIK: ' + escHtml(p.nik) + '</span> &nbsp;·&nbsp; ' : ''}${escHtml(kec)}</p>
                <p class="text-muted small mb-2">${formatDate(p.waktu)}</p>
                <div class="d-flex justify-content-center gap-3">
                    <div class="text-center"><div class="fw-bold fs-4">${p.nilai}</div><small class="text-muted">Nilai</small></div>
                    <div class="text-center"><div class="fw-bold fs-4 text-success">${p.total_benar}</div><small class="text-muted">Benar</small></div>
                    <div class="text-center"><div class="fw-bold fs-4 text-danger">${parseInt(p.total_soal) - parseInt(p.total_benar)}</div><small class="text-muted">Salah</small></div>
                    <div class="text-center"><div class="fw-bold fs-4">${p.total_soal}</div><small class="text-muted">Total</small></div>
                </div>
            </div>
            <hr>
            <h6 class="fw-bold mb-3"><i class="bi bi-list-check me-1"></i>Rincian Jawaban</h6>`;

            data.jawaban.forEach((j, i) => {
                const answered  = j.huruf_dipilih !== null && j.huruf_dipilih !== '';
                // opsi_id ada tapi JOIN gagal = peserta sudah jawab, tapi data opsi terhapus saat edit soal
                const dataHilang = !answered && j.opsi_id && parseInt(j.opsi_id) > 0;
                const isBenar   = parseInt(j.is_benar) === 1;
                let cls = 'jawaban-kosong';
                let icon = '<i class="bi bi-dash-circle text-muted"></i>';
                if (answered) {
                    cls  = isBenar ? 'jawaban-benar' : 'jawaban-salah';
                    icon = isBenar ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle-fill text-danger"></i>';
                } else if (dataHilang) {
                    cls  = 'jawaban-salah';
                    icon = '<i class="bi bi-x-circle-fill text-danger"></i>';
                }

                let dijawabTeks;
                if (answered) {
                    dijawabTeks = `<strong>${escHtml(j.huruf_dipilih + '. ' + j.teks_dipilih)}</strong>`;
                } else if (dataHilang) {
                    dijawabTeks = `<strong><em class="text-warning" title="Peserta sudah menjawab, tapi data pilihan terhapus saat soal diedit oleh admin">Sudah dijawab (pilihan tidak tersedia)</em></strong>`;
                } else {
                    dijawabTeks = `<em class="text-muted">Tidak dijawab</em>`;
                }

                html += `<div class="jawaban-item ${cls}">
                    <div class="d-flex align-items-start gap-2">
                        <span class="fw-bold text-muted" style="min-width:22px;font-size:.8rem;">${j.nomor_urut}.</span>
                        <div class="flex-grow-1">
                            <div class="mb-1" style="font-size:.875rem;">${escHtml(j.pertanyaan)}</div>
                            <div class="d-flex flex-wrap gap-2 small">
                                <span>Dijawab: ${dijawabTeks}</span>
                                ${!isBenar && j.huruf_benar ? `<span class="text-success">Benar: <strong>${escHtml(j.huruf_benar + '. ' + j.teks_benar)}</strong></span>` : ''}
                            </div>
                        </div>
                        <div class="flex-shrink-0">${icon}</div>
                    </div>
                </div>`;
            });

            document.getElementById('modalDetailTitle').textContent = 'Detail — ' + p.nama;
            document.getElementById('modalDetailBody').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('modalDetailBody').innerHTML = '<p class="text-danger text-center py-3">Gagal memuat data.</p>';
        });
}

function getGrade(nilai) {
    if (nilai >= 90) return ['A', '#16a34a'];
    if (nilai >= 75) return ['B', '#2563eb'];
    if (nilai >= 60) return ['C', '#d97706'];
    if (nilai >= 45) return ['D', '#dc2626'];
    return ['E', '#9ca3af'];
}

function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function formatDate(str) {
    if (!str) return '';
    const d = new Date(str.replace(' ', 'T'));
    return d.toLocaleDateString('id-ID', {day:'2-digit',month:'2-digit',year:'numeric'}) + ' ' +
           d.toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit'});
}

function hapus(id, btn) {
    if (!confirm('Hapus hasil ujian peserta ini? Data tidak bisa dikembalikan.')) return;
    const row = btn.closest('tr');
    fetch('ujian_hasil.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=hapus&id=' + id
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            row.style.transition = 'opacity .3s';
            row.style.opacity = '0';
            setTimeout(() => {
                row.remove();
                // Update jumlah peserta di header section
                const tbody = btn.closest('tbody');
                const header = btn.closest('.card').querySelector('.badge');
                if (header) {
                    const sisa = tbody.querySelectorAll('tr').length;
                    header.textContent = sisa + ' peserta';
                    if (sisa === 0) btn.closest('.card').remove();
                }
            }, 300);
        } else {
            alert('Gagal menghapus data.');
        }
    })
    .catch(() => alert('Terjadi kesalahan.'));
}

function editNama(id, nama) {
    document.getElementById('editNamaId').value = id;
    document.getElementById('editNamaInput').value = nama;
    new bootstrap.Modal(document.getElementById('modalEditNama')).show();
    setTimeout(() => document.getElementById('editNamaInput').focus(), 300);
}

function simpanEditNama() {
    const id   = document.getElementById('editNamaId').value;
    const nama = document.getElementById('editNamaInput').value.trim();
    if (!nama) { alert('Nama tidak boleh kosong.'); return; }

    fetch('ujian_hasil.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=edit_nama&id=' + id + '&nama=' + encodeURIComponent(nama)
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            const cell = document.querySelector(`td[data-nama-id="${id}"]`);
            if (cell) cell.textContent = nama;
            bootstrap.Modal.getInstance(document.getElementById('modalEditNama')).hide();
        } else {
            alert(d.msg || 'Gagal menyimpan.');
        }
    })
    .catch(() => alert('Terjadi kesalahan.'));
}
</script>
</body>
</html>
