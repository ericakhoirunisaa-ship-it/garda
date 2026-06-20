<?php
require_once 'config.php';
requireSuperAdmin();

// ── Pure-PHP reader: baca satu entry dari ZIP/XLSX ─────────────────────────
// Tidak butuh ekstensi apapun — hanya file_get_contents + gzinflate (built-in)
function zipReadEntryPA(string $zipPath, string $entryName): ?string {
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) === true) {
            $c = $zip->getFromName($entryName);
            $zip->close();
            if ($c !== false) return $c;
        }
    }
    // Fallback: pure PHP ZIP parser
    $raw = @file_get_contents($zipPath);
    if ($raw === false || strlen($raw) < 22) return null;
    $eocdSig = "\x50\x4b\x05\x06";
    $eocdPos = strrpos($raw, $eocdSig);
    if ($eocdPos === false) return null;
    $cdCount  = unpack('v', substr($raw, $eocdPos + 10, 2))[1];
    $cdOffset = unpack('V', substr($raw, $eocdPos + 16, 4))[1];
    $pos = $cdOffset;
    for ($i = 0; $i < $cdCount; $i++) {
        if (substr($raw, $pos, 4) !== "\x50\x4b\x01\x02") break;
        $compression = unpack('v', substr($raw, $pos + 10, 2))[1];
        $compSize    = unpack('V', substr($raw, $pos + 20, 4))[1];
        $nameLen     = unpack('v', substr($raw, $pos + 28, 2))[1];
        $extraLen    = unpack('v', substr($raw, $pos + 30, 2))[1];
        $commentLen  = unpack('v', substr($raw, $pos + 32, 2))[1];
        $localOffset = unpack('V', substr($raw, $pos + 42, 4))[1];
        $name        = substr($raw, $pos + 46, $nameLen);
        if ($name === $entryName) {
            if (substr($raw, $localOffset, 4) !== "\x50\x4b\x03\x04") return null;
            $lNameLen  = unpack('v', substr($raw, $localOffset + 26, 2))[1];
            $lExtraLen = unpack('v', substr($raw, $localOffset + 28, 2))[1];
            $dataStart = $localOffset + 30 + $lNameLen + $lExtraLen;
            $compData  = substr($raw, $dataStart, $compSize);
            if ($compression === 0) return $compData;
            if ($compression === 8) return @gzinflate($compData);
            return null;
        }
        $pos += 46 + $nameLen + $extraLen + $commentLen;
    }
    return null;
}

// ── Baca nama → nilai dari data se.xlsx (kolom A=nama, X=nilai) ────────────
function buildNilaiMapFromDataSe(string $file): array {
    $ss       = zipReadEntryPA($file, 'xl/sharedStrings.xml');
    $sheetRaw = zipReadEntryPA($file, 'xl/worksheets/sheet1.xml');
    if (!$sheetRaw) return [];

    $strings = [];
    if ($ss) {
        $xml = @simplexml_load_string($ss);
        if ($xml) {
            foreach ($xml->si as $si) {
                $str = '';
                if (isset($si->t)) { $str = (string)$si->t; }
                else { foreach ($si->r as $r) { if (isset($r->t)) $str .= (string)$r->t; } }
                $strings[] = $str;
            }
        }
    }

    $xml2 = @simplexml_load_string($sheetRaw);
    if (!$xml2) return [];

    $map = [];
    foreach ($xml2->sheetData->row as $row) {
        if ((int)$row['r'] === 1) continue; // skip header
        $cells = [];
        foreach ($row->c as $cell) {
            preg_match('/([A-Z]+)/', (string)$cell['r'], $m);
            $col = $m[1] ?? '';
            $t   = (string)$cell['t'];
            $v   = isset($cell->v) ? (string)$cell->v : '';
            $cells[$col] = ($t === 's') ? ($strings[(int)$v] ?? '') : $v;
        }
        $nama     = trim($cells['A'] ?? '');
        $nilaiRaw = trim($cells['X'] ?? '');
        $noTelp   = trim($cells['O'] ?? '');
        if ($nama === '') continue;
        $key = strtoupper(preg_replace('/\s+/', ' ', $nama));
        $map[$key] = [
            'nilai'   => ($nilaiRaw !== '' && $nilaiRaw !== '-') ? (float)str_replace(',', '.', $nilaiRaw) : null,
            'no_telp' => $noTelp,
        ];
    }
    return $map;
}

// ── Auto-sync nilai_kompetensi dari ujian_pengerjaan (match by nama) ────────
$conn->query("
    UPDATE peserta p
    INNER JOIN (
        SELECT UPPER(TRIM(nama)) AS nama_upper, MAX(nilai) AS nilai_max
        FROM ujian_pengerjaan
        GROUP BY UPPER(TRIM(nama))
    ) up ON UPPER(TRIM(p.nama)) = up.nama_upper
    SET p.nilai_kompetensi = up.nilai_max
    WHERE p.status_seleksi = 'Diterima'
");

// ── Fallback: sync dari data se.xlsx untuk peserta yang belum punya nilai ──
$seFile = __DIR__ . '/../data se.xlsx';
$countNull = (int)$conn->query(
    "SELECT COUNT(*) FROM peserta WHERE status_seleksi='Diterima' AND nilai_kompetensi IS NULL"
)->fetch_row()[0];
$countNullTelp = (int)$conn->query(
    "SELECT COUNT(*) FROM peserta WHERE status_seleksi='Diterima' AND (no_telp IS NULL OR no_telp = '')"
)->fetch_row()[0];

if (($countNull > 0 || $countNullTelp > 0) && file_exists($seFile)) {
    $nilaiMap = buildNilaiMapFromDataSe($seFile);
    if ($nilaiMap) {
        $stmtSync = $conn->prepare(
            "UPDATE peserta SET nilai_kompetensi = IF(nilai_kompetensi IS NULL, ?, nilai_kompetensi),
             no_telp = IF(? <> '', ?, no_telp) WHERE UPPER(TRIM(nama)) = ? AND status_seleksi = 'Diterima'"
        );
        foreach ($nilaiMap as $upperName => $data) {
            $nilai  = $data['nilai'];
            $noTelp = $data['no_telp'];
            $stmtSync->bind_param('dsss', $nilai, $noTelp, $noTelp, $upperName);
            $stmtSync->execute();
        }
    }
}

// ── Query utama ────────────────────────────────────────────────────────────
$search  = trim($_GET['search'] ?? '');
$filter  = $_GET['filter'] ?? 'semua';
$kec     = trim($_GET['kec'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset  = ($page - 1) * $perPage;

// Daftar kecamatan untuk dropdown
$kecList = [];
$resKec  = $conn->query("SELECT DISTINCT alamat_kec FROM peserta WHERE status_seleksi='Diterima' AND alamat_kec IS NOT NULL AND alamat_kec <> '' ORDER BY alamat_kec");
while ($kRow = $resKec->fetch_row()) $kecList[] = $kRow[0];

$where  = "WHERE p.status_seleksi = 'Diterima'";
$params = []; $types = '';

if ($search !== '') {
    $where .= " AND p.nama LIKE ?";
    $params[] = "%$search%"; $types .= 's';
}
if ($kec !== '') {
    $where .= " AND p.alamat_kec = ?";
    $params[] = $kec; $types .= 's';
}
if ($filter === 'lulus')             $where .= " AND pa.status_akhir = 'Lulus'";
elseif ($filter === 'tidak_lulus')   $where .= " AND pa.status_akhir = 'Tidak Lulus'";
elseif ($filter === 'belum')         $where .= " AND (pa.id IS NULL OR pa.status_akhir IS NULL OR pa.status_akhir = '')";
elseif ($filter === 'blm_wawancara') $where .= " AND w.id IS NULL";

$selectCols = "p.id AS peserta_id, p.nama, p.kelompok, p.posisi_daftar, p.jenis_kelamin, p.pendidikan,
               p.no_telp, p.alamat_kec, p.nilai_kompetensi,
               w.id AS wawancara_id,
               w.nilai_komunikasi, w.nilai_penampilan, w.nilai_analisa, w.nilai, w.predikat,
               w.catatan AS catatan_wawancara,
               ads.nama AS pewawancara,
               pa.status_akhir, pa.keterangan";

$joinSQL = "FROM peserta p
            LEFT JOIN wawancara w ON w.id = (
                SELECT id FROM wawancara WHERE peserta_id = p.id ORDER BY created_at DESC LIMIT 1
            )
            LEFT JOIN admin_seleksi ads ON ads.id = w.admin_id
            LEFT JOIN penilaian_akhir pa ON pa.peserta_id = p.id
            $where";

$orderSQL = "ORDER BY CASE WHEN w.id IS NULL THEN 1 ELSE 0 END, w.nilai DESC, p.nama ASC";

$stmt = $conn->prepare("SELECT $selectCols $joinSQL $orderSQL LIMIT ? OFFSET ?");
$pf   = array_merge($params, [$perPage, $offset]);
$tf   = $types . 'ii';
$stmt->bind_param($tf, ...$pf);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmtC = $conn->prepare("SELECT COUNT(*) $joinSQL");
if ($params) $stmtC->bind_param($types, ...$params);
$stmtC->execute();
$total     = $stmtC->get_result()->fetch_row()[0];
$totalPage = ceil($total / $perPage);

$stats = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN pa.status_akhir = 'Lulus'        THEN 1 ELSE 0 END) AS jml_lulus,
        SUM(CASE WHEN pa.status_akhir = 'Tidak Lulus'   THEN 1 ELSE 0 END) AS jml_tidak_lulus,
        SUM(CASE WHEN pa.id IS NULL OR pa.status_akhir IS NULL OR pa.status_akhir = '' THEN 1 ELSE 0 END) AS jml_belum,
        SUM(CASE WHEN w.id IS NULL THEN 1 ELSE 0 END) AS jml_blm_wawancara
    FROM peserta p
    LEFT JOIN wawancara w ON w.id = (
        SELECT id FROM wawancara WHERE peserta_id = p.id ORDER BY created_at DESC LIMIT 1
    )
    LEFT JOIN penilaian_akhir pa ON pa.peserta_id = p.id
    WHERE p.status_seleksi = 'Diterima'
")->fetch_assoc();

$predikatInfo = [
    'A' => ['color' => '#16a34a', 'bg' => '#d1fae5'],
    'B' => ['color' => '#2563eb', 'bg' => '#dbeafe'],
    'C' => ['color' => '#d97706', 'bg' => '#fef3c7'],
    'D' => ['color' => '#ea580c', 'bg' => '#ffedd5'],
    'E' => ['color' => '#991b1b', 'bg' => '#fee2e2'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penilaian Akhir Mitra — SEMANIS 2026</title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        th { white-space:nowrap; font-size:.76rem; }
        td { font-size:.83rem; vertical-align:middle; }
        .stat-icon { width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
        .badge-lulus { background:#16a34a; color:#fff; }
        .badge-tidak { background:#dc2626; color:#fff; }
        .badge-belum { background:#6b7280; color:#fff; }
        .badge-e-val { background:#fee2e2; color:#991b1b; font-weight:700; border-radius:5px; padding:1px 7px; font-size:.8rem; }
        .badge-tdk-waw { background:#f1f5f9; color:#64748b; font-weight:500; border-radius:5px; padding:1px 6px; font-size:.72rem; white-space:nowrap; }
        .keterangan-cell { max-width:150px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .posisi-badge { background:#eff6ff; color:#1d4ed8; border-radius:6px; padding:2px 8px; font-size:.72rem; font-weight:600; white-space:nowrap; }
        tr.row-no-wawancara td { background:#fffbf0 !important; }
    </style>
</head>
<body>
<div class="d-flex">
    <?php include '_sidebar.php'; ?>

    <div class="main-content w-100">
        <header class="topbar">
            <button class="topbar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div>
                <div class="topbar-title">
                    <i class="bi bi-award-fill me-2" style="color:#f79039"></i>Penilaian Akhir Mitra
                </div>
                <div class="topbar-sub">Sensus Ekonomi 2026 — BPS Kab. Toli-Toli</div>
            </div>
            <div class="topbar-right">
                <a id="exportLink"
                   href="export_penilaian_akhir.php?search=<?= urlencode($search) ?>&filter=<?= $filter ?>&kec=<?= urlencode($kec) ?>"
                   class="btn btn-sm d-flex align-items-center gap-1 fw-semibold"
                   style="background:#16a34a;color:#fff;border-radius:8px;">
                    <i class="bi bi-file-earmark-excel-fill"></i>
                    <span class="d-none d-md-inline">Export Excel</span>
                </a>
            </div>
        </header>

        <div class="page-body">

            <!-- Stat Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="stat-icon" style="background:#fff3cd;color:#d97706"><i class="bi bi-people-fill"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= $stats['total'] ?? 0 ?></div>
                                <div class="text-muted" style="font-size:.68rem">Total Peserta</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="stat-icon" style="background:#d1fae5;color:#16a34a"><i class="bi bi-check-circle-fill"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1" style="color:#16a34a"><?= $stats['jml_lulus'] ?? 0 ?></div>
                                <div class="text-muted" style="font-size:.68rem">Lulus</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="stat-icon" style="background:#fee2e2;color:#dc2626"><i class="bi bi-x-circle-fill"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1" style="color:#dc2626"><?= $stats['jml_tidak_lulus'] ?? 0 ?></div>
                                <div class="text-muted" style="font-size:.68rem">Tidak Lulus</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="stat-icon" style="background:#f1f5f9;color:#6b7280"><i class="bi bi-hourglass-split"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1" style="color:#6b7280"><?= $stats['jml_belum'] ?? 0 ?></div>
                                <div class="text-muted" style="font-size:.68rem">Belum Dinilai</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="stat-icon" style="background:#fef9c3;color:#a16207"><i class="bi bi-mic-mute-fill"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1" style="color:#a16207"><?= $stats['jml_blm_wawancara'] ?? 0 ?></div>
                                <div class="text-muted" style="font-size:.68rem">Belum Wawancara</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter -->
            <div class="card stat-card mb-3">
                <div class="card-body py-3">
                    <form method="GET" class="row g-2 align-items-center">
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" name="search" class="form-control border-start-0"
                                       placeholder="Cari nama peserta..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="kec" id="selKec" class="form-select">
                                <option value="">Semua Kecamatan</option>
                                <?php foreach ($kecList as $k): ?>
                                    <option value="<?= htmlspecialchars($k) ?>" <?= $kec===$k?'selected':'' ?>>
                                        <?= htmlspecialchars($k) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="filter" class="form-select">
                                <option value="semua"         <?= $filter==='semua'?'selected':'' ?>>Semua Peserta</option>
                                <option value="lulus"         <?= $filter==='lulus'?'selected':'' ?>>Lulus</option>
                                <option value="tidak_lulus"   <?= $filter==='tidak_lulus'?'selected':'' ?>>Tidak Lulus</option>
                                <option value="belum"         <?= $filter==='belum'?'selected':'' ?>>Belum Dinilai</option>
                                <option value="blm_wawancara" <?= $filter==='blm_wawancara'?'selected':'' ?>>Belum Wawancara</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn w-100 text-white fw-semibold" style="background:#f79039">Filter</button>
                        </div>
                        <div class="col-6 col-md-1">
                            <a href="penilaian_akhir.php" class="btn btn-outline-secondary w-100">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabel -->
            <div class="card stat-card" id="tableWrapper">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead style="background:#f79039; color:#fff;">
                                <tr>
                                    <th class="ps-3">#</th>
                                    <th>Nama</th>
                                    <th>Posisi Daftar</th>
                                    <th>JK</th>
                                    <th>Pendidikan</th>
                                    <th>Kecamatan</th>
                                    <th>No Telp</th>
                                    <th class="text-center">Kom.</th>
                                    <th class="text-center">Pen.</th>
                                    <th class="text-center">Ana.</th>
                                    <th class="text-center">Rata²<br><small style="font-weight:400;font-size:.68rem">Wawancara</small></th>
                                    <th class="text-center">Predikat</th>
                                    <th class="text-center">Nilai<br><small style="font-weight:400;font-size:.68rem">Kompetensi</small></th>
                                    <th class="text-center">Status</th>
                                    <th>Keterangan</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="16" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>Tidak ada data.
                                </td></tr>
                            <?php endif; ?>
                            <?php foreach ($rows as $i => $r):
                                $sudah    = $r['wawancara_id'] !== null;
                                $predTamp = $sudah ? ($r['predikat'] ?? null) : null;
                                $piTamp   = $predikatInfo[$predTamp] ?? null;
                                $nKomp    = $r['nilai_kompetensi'];
                            ?>
                                <tr class="<?= !$sudah ? 'row-no-wawancara' : '' ?>" data-pid="<?= $r['peserta_id'] ?>">
                                    <td class="ps-3 text-muted small"><?= $offset + $i + 1 ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($r['nama']) ?></div>
                                        <?php if (($r['kelompok'] ?? '') === 'Afirmasi'): ?>
                                            <span class="badge" style="background:#fff8f0;color:#f79039;border:1px solid #f7c39e;font-size:.66rem;font-weight:600;">Afirmasi</span>
                                        <?php endif; ?>
                                        <?php if (!$sudah): ?>
                                            <small style="font-size:.7rem;color:#92400e">
                                                <i class="bi bi-mic-mute"></i> Belum wawancara
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($r['posisi_daftar']): ?>
                                            <span class="posisi-badge"><?= htmlspecialchars($r['posisi_daftar']) ?></span>
                                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                                    </td>
                                    <td><small><?= $r['jenis_kelamin'] === 'Lk' ? 'L' : 'P' ?></small></td>
                                    <td style="font-size:.78rem"><?= htmlspecialchars($r['pendidikan']) ?></td>
                                    <td style="font-size:.78rem"><?= htmlspecialchars($r['alamat_kec'] ?? '—') ?></td>
                                    <td style="font-size:.78rem;white-space:nowrap">
                                        <?php if ($r['no_telp']): ?>
                                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/','',$r['no_telp']) ?>" target="_blank"
                                               class="text-decoration-none text-dark" title="Buka WhatsApp">
                                                <i class="bi bi-whatsapp text-success me-1"></i><?= htmlspecialchars($r['no_telp']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-center"><?php if ($sudah): ?><span class="fw-bold"><?= $r['nilai_komunikasi'] ?: '—' ?></span><?php else: ?><span class="badge-tdk-waw">Tidak Ikut</span><?php endif; ?></td>
                                    <td class="text-center"><?php if ($sudah): ?><span class="fw-bold"><?= $r['nilai_penampilan'] ?: '—' ?></span><?php else: ?><span class="badge-tdk-waw">Tidak Ikut</span><?php endif; ?></td>
                                    <td class="text-center"><?php if ($sudah): ?><span class="fw-bold"><?= $r['nilai_analisa']    ?: '—' ?></span><?php else: ?><span class="badge-tdk-waw">Tidak Ikut</span><?php endif; ?></td>
                                    <td class="text-center fw-bold" style="color:<?= $sudah ? '#f79039' : 'inherit' ?>">
                                        <?= $sudah ? ($r['nilai'] ?: '—') : '<span class="badge-tdk-waw">Tidak Ikut</span>' ?>
                                    </td>

                                    <td class="text-center">
                                        <?php if (!$sudah): ?>
                                            <span class="badge-tdk-waw">Tidak Ikut</span>
                                        <?php elseif ($piTamp): ?>
                                            <span class="badge fw-bold px-2" style="background:<?= $piTamp['bg'] ?>;color:<?= $piTamp['color'] ?>;font-size:.8rem">
                                                <?= $predTamp ?>
                                            </span>
                                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                                    </td>

                                    <td class="text-center fw-bold">
                                        <?php if ($nKomp !== null): ?>
                                            <span style="color:#2563eb"><?= number_format((float)$nKomp, 2, ',', '') ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-center status-cell">
                                        <?php if ($r['status_akhir'] === 'Lulus'): ?>
                                            <span class="badge badge-lulus px-2">Lulus</span>
                                        <?php elseif ($r['status_akhir'] === 'Tidak Lulus'): ?>
                                            <span class="badge badge-tidak px-2">Tidak Lulus</span>
                                        <?php else: ?>
                                            <span class="badge badge-belum px-2">Belum</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="keterangan-cell ket-cell" title="<?= htmlspecialchars($r['keterangan'] ?? '') ?>"
                                        style="font-size:.78rem;color:#6c757d">
                                        <?= $r['keterangan'] ? htmlspecialchars($r['keterangan']) : '<span class="text-muted">—</span>' ?>
                                    </td>

                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary"
                                                onclick='bukaPenilaian(<?= htmlspecialchars(json_encode([
                                                    'peserta_id'       => $r['peserta_id'],
                                                    'nama'             => $r['nama'],
                                                    'posisi_daftar'    => $r['posisi_daftar'],
                                                    'sudah_wawancara'  => $sudah,
                                                    'nilai_komunikasi' => $r['nilai_komunikasi'],
                                                    'nilai_penampilan' => $r['nilai_penampilan'],
                                                    'nilai_analisa'    => $r['nilai_analisa'],
                                                    'nilai'            => $r['nilai'],
                                                    'predikat'         => $predTamp,
                                                    'nilai_kompetensi'   => $nKomp,
                                                    'pewawancara'        => $r['pewawancara'],
                                                    'catatan_wawancara'  => $r['catatan_wawancara'],
                                                    'status_akhir'       => $r['status_akhir'],
                                                    'keterangan'         => $r['keterangan'],
                                                ]), ENT_QUOTES) ?>)'
                                                title="Edit Penilaian">
                                            <i class="bi bi-pencil-fill"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if ($totalPage > 1): ?>
                <div class="card-footer d-flex justify-content-between align-items-center bg-white">
                    <small class="text-muted">Menampilkan <?= count($rows) ?> dari <?= $total ?> hasil</small>
                    <nav><ul class="pagination pagination-sm mb-0">
                        <?php for ($p2 = 1; $p2 <= $totalPage; $p2++): ?>
                            <li class="page-item <?= $p2 === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $p2 ?>&search=<?= urlencode($search) ?>&filter=<?= $filter ?>&kec=<?= urlencode($kec) ?>"><?= $p2 ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul></nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Penilaian -->
<div class="modal fade" id="modalPenilaian" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:460px">
        <div class="modal-content" style="border-radius:14px;overflow:hidden;">
            <div class="modal-header border-0 py-3" style="background:#f79039;">
                <div>
                    <h6 class="modal-title text-white fw-bold mb-0"><i class="bi bi-award-fill me-2"></i>Penilaian Akhir</h6>
                    <small class="text-white opacity-80" id="modalNama">—</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <!-- Rekap nilai -->
                <div class="mb-3 p-3 rounded-3" style="background:#f8f9fa;border:1px solid #e9ecef;">
                    <div class="fw-semibold mb-2" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;">
                        Rekap Nilai Wawancara
                    </div>
                    <div class="row g-2 text-center">
                        <div class="col-3"><div style="font-size:.65rem;color:#6c757d">Kom.</div><div class="fw-bold" id="mKom">—</div></div>
                        <div class="col-3"><div style="font-size:.65rem;color:#6c757d">Pen.</div><div class="fw-bold" id="mPen">—</div></div>
                        <div class="col-3"><div style="font-size:.65rem;color:#6c757d">Ana.</div><div class="fw-bold" id="mAna">—</div></div>
                        <div class="col-3"><div style="font-size:.65rem;color:#6c757d">Rata²</div><div class="fw-bold" id="mRata" style="color:#f79039">—</div></div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mt-2 pt-2" style="border-top:1px solid #e9ecef;">
                        <div style="font-size:.75rem;color:#6c757d">Predikat: <span id="mPredikat" class="badge fw-bold px-2 ms-1">—</span></div>
                        <div style="font-size:.75rem;color:#6c757d">
                            Kompetensi: <span id="mKomp" class="fw-bold ms-1" style="color:#2563eb">—</span>
                        </div>
                    </div>
                </div>

                <!-- Pewawancara & Catatan -->
                <div id="wrapPewawancara" class="mb-3 p-3 rounded-3" style="background:#f0f9ff;border:1px solid #bae6fd;display:none">
                    <div class="fw-semibold mb-2" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;">
                        Info Wawancara
                    </div>
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <i class="bi bi-person-badge-fill mt-1" style="color:#0284c7;font-size:.9rem;flex-shrink:0"></i>
                        <div>
                            <div style="font-size:.68rem;color:#6c757d">Pewawancara</div>
                            <div class="fw-semibold" id="mPewawancara" style="font-size:.85rem">—</div>
                        </div>
                    </div>
                    <div id="wrapCatatan" class="d-flex align-items-start gap-2" style="display:none!important">
                        <i class="bi bi-chat-quote-fill mt-1" style="color:#0284c7;font-size:.9rem;flex-shrink:0"></i>
                        <div>
                            <div style="font-size:.68rem;color:#6c757d">Catatan Pewawancara</div>
                            <div id="mCatatan" style="font-size:.82rem;color:#374151;white-space:pre-wrap">—</div>
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:.84rem;">
                        <i class="bi bi-patch-check-fill me-1" style="color:#f79039"></i>Status Kelulusan
                    </label>
                    <select id="selectStatus" class="form-select">
                        <option value="">— Belum ditentukan —</option>
                        <option value="Lulus">Lulus</option>
                        <option value="Tidak Lulus">Tidak Lulus</option>
                    </select>
                </div>

                <!-- Keterangan -->
                <div class="mb-1">
                    <label class="form-label fw-semibold" style="font-size:.84rem;">
                        <i class="bi bi-chat-text-fill me-1" style="color:#6b7280"></i>Keterangan
                    </label>
                    <textarea id="inputKeterangan" class="form-control" rows="3"
                              placeholder="Catatan atau keterangan tambahan..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-sm text-white fw-semibold px-4"
                        style="background:#f79039;border-color:#f79039" onclick="simpanPenilaian()">
                    <i class="bi bi-save-fill me-1"></i>Simpan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999">
    <div id="toastSimpan" class="toast align-items-center text-white border-0" role="alert" style="background:#16a34a">
        <div class="d-flex">
            <div class="toast-body fw-semibold"><i class="bi bi-check-circle-fill me-2"></i>Penilaian berhasil disimpan.</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
// ── Filter kecamatan tanpa reload halaman ─────────────────────────────────
document.getElementById('selKec').addEventListener('change', function () {
    const url = new URL(window.location.href);
    url.searchParams.set('kec', this.value);
    url.searchParams.set('page', '1');

    const wrapper = document.getElementById('tableWrapper');
    wrapper.style.opacity = '0.5';
    wrapper.style.pointerEvents = 'none';

    fetch(url)
        .then(r => r.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const newWrapper = doc.getElementById('tableWrapper');
            if (newWrapper) {
                wrapper.outerHTML = newWrapper.outerHTML;
                document.getElementById('tableWrapper').style.opacity = '';
                document.getElementById('tableWrapper').style.pointerEvents = '';
            }
            // Sync export link
            const newExport = doc.getElementById('exportLink');
            const curExport = document.getElementById('exportLink');
            if (newExport && curExport) curExport.href = newExport.href;

            history.pushState({}, '', url);
        })
        .catch(() => {
            document.getElementById('tableWrapper').style.opacity = '';
            document.getElementById('tableWrapper').style.pointerEvents = '';
        });
});

let modalBS = null, currentPesertaId = null;
const savedState = {}; // peserta_id → {status_akhir, keterangan}
document.addEventListener('DOMContentLoaded', () => {
    modalBS = new bootstrap.Modal(document.getElementById('modalPenilaian'));
});
const predCfg = {
    A:{color:'#16a34a',bg:'#d1fae5'}, B:{color:'#2563eb',bg:'#dbeafe'},
    C:{color:'#d97706',bg:'#fef3c7'}, D:{color:'#ea580c',bg:'#ffedd5'},
    E:{color:'#991b1b',bg:'#fee2e2'},
};
function bukaPenilaian(data) {
    currentPesertaId = data.peserta_id;
    document.getElementById('modalNama').textContent =
        data.nama + (data.posisi_daftar ? ' · ' + data.posisi_daftar : '');
    const TDK = 'Tidak Ikut', D = '—';
    document.getElementById('mKom').textContent   = data.sudah_wawancara ? (data.nilai_komunikasi || D) : TDK;
    document.getElementById('mPen').textContent   = data.sudah_wawancara ? (data.nilai_penampilan || D) : TDK;
    document.getElementById('mAna').textContent   = data.sudah_wawancara ? (data.nilai_analisa    || D) : TDK;
    document.getElementById('mRata').textContent  = data.sudah_wawancara ? (data.nilai            || D) : TDK;

    const nk = data.nilai_kompetensi;
    document.getElementById('mKomp').textContent =
        (nk !== null && nk !== undefined && nk !== '') ?
            parseFloat(nk).toFixed(2).replace('.', ',') : '—';

    const pred = document.getElementById('mPredikat');
    const p = data.predikat;
    if (!data.sudah_wawancara) {
        pred.textContent = TDK; pred.style.background = '#f1f5f9'; pred.style.color = '#64748b';
    } else if (p && predCfg[p]) {
        pred.textContent = p; pred.style.background = predCfg[p].bg; pred.style.color = predCfg[p].color;
    } else {
        pred.textContent = D; pred.style.background = '#f1f5f9'; pred.style.color = '#6b7280';
    }
    // Pewawancara & catatan
    const wrapPew = document.getElementById('wrapPewawancara');
    const wrapCat = document.getElementById('wrapCatatan');
    if (data.sudah_wawancara) {
        document.getElementById('mPewawancara').textContent = data.pewawancara || '—';
        const catatan = data.catatan_wawancara || '';
        document.getElementById('mCatatan').textContent = catatan || '—';
        wrapPew.style.display = '';
        wrapCat.style.setProperty('display', catatan ? '' : 'none', 'important');
    } else {
        wrapPew.style.display = 'none';
    }

    const st = savedState[data.peserta_id] || {};
    document.getElementById('selectStatus').value    = st.status_akhir  ?? (data.status_akhir || '');
    document.getElementById('inputKeterangan').value = st.keterangan    ?? (data.keterangan  || '');
    modalBS.show();
}
function simpanPenilaian() {
    const status   = document.getElementById('selectStatus').value;
    const ket      = document.getElementById('inputKeterangan').value.trim();
    const fd = new FormData();
    fd.append('peserta_id',   currentPesertaId);
    fd.append('status_akhir', status);
    fd.append('keterangan',   ket);
    fetch('save_penilaian_akhir.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(res => {
            if (!res.ok) { alert('Gagal: ' + (res.msg || 'Error')); return; }
            modalBS.hide();

            // Update baris tanpa reload
            const row = document.querySelector('tr[data-pid="' + currentPesertaId + '"]');
            if (row) {
                // Status badge
                const sc = row.querySelector('.status-cell');
                if (sc) {
                    let html = '';
                    if (status === 'Lulus')
                        html = '<span class="badge badge-lulus px-2">Lulus</span>';
                    else if (status === 'Tidak Lulus')
                        html = '<span class="badge badge-tidak px-2">Tidak Lulus</span>';
                    else
                        html = '<span class="badge badge-belum px-2">Belum</span>';
                    sc.innerHTML = html;
                }
                // Keterangan
                const kc = row.querySelector('.ket-cell');
                if (kc) {
                    kc.title       = ket;
                    kc.innerHTML   = ket ? ket.replace(/</g,'&lt;').replace(/>/g,'&gt;') : '<span class="text-muted">—</span>';
                }
            }

            savedState[currentPesertaId] = { status_akhir: status, keterangan: ket };
            new bootstrap.Toast(document.getElementById('toastSimpan')).show();
        })
        .catch(() => alert('Kesalahan jaringan.'));
}
</script>
</body>
</html>
