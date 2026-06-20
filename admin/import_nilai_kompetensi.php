<?php
require_once 'config.php';
requireSuperAdmin();

$xlsxFile  = __DIR__ . '/../nilai_kompetensi.xlsx';
$fileExists = file_exists($xlsxFile);

$imported  = 0;
$unmatched = [];
$done      = false;
$fatalMsg  = '';

// ── Baca satu file dari dalam ZIP/XLSX ─────────────────────────────────────
// Coba ZipArchive dulu, lalu exec unzip -p sebagai fallback
function xlsxGetEntry($xlsxPath, $entryName) {
    // Metode 1: ZipArchive
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($xlsxPath) === true) {
            $content = $zip->getFromName($entryName);
            $zip->close();
            return $content !== false ? $content : null;
        }
    }
    // Metode 2: exec unzip -p (output file ke stdout)
    if (function_exists('exec')) {
        $out = []; $ret = 0;
        exec('unzip -p ' . escapeshellarg($xlsxPath) . ' ' . escapeshellarg($entryName) . ' 2>/dev/null', $out, $ret);
        if ($ret === 0 && !empty($out)) return implode("\n", $out);
    }
    return null;
}

function canReadXlsx() {
    return class_exists('ZipArchive') || function_exists('exec');
}

function readXlsxKompetensi($file) {
    $ss = xlsxGetEntry($file, 'xl/sharedStrings.xml');
    $sheetRaw = xlsxGetEntry($file, 'xl/worksheets/sheet1.xml');
    if (!$sheetRaw) return false;

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
    if (!$xml2) return false;

    $data = [];
    foreach ($xml2->sheetData->row as $row) {
        $rowIdx  = (int)$row['r'] - 1;
        $rowData = ['A' => '', 'B' => ''];
        foreach ($row->c as $cell) {
            $ref = (string)$cell['r'];
            preg_match('/([A-Z]+)/', $ref, $m);
            $col = $m[1] ?? '';
            if (!isset($rowData[$col])) continue;
            $t = (string)$cell['t'];
            $v = isset($cell->v) ? (string)$cell->v : '';
            $rowData[$col] = ($t === 's') ? ($strings[(int)$v] ?? '') : $v;
        }
        $data[$rowIdx] = $rowData;
    }
    ksort($data);
    return array_values($data);
}

// ── Cek ketersediaan metode baca ──────────────────────────────────────────
if ($fileExists && !canReadXlsx()) {
    $fatalMsg = 'Ekstensi <strong>php-zip</strong> tidak aktif dan fungsi <strong>exec()</strong> dinonaktifkan pada server ini.
        Aktifkan salah satunya:<br>
        <code>sudo apt install php-zip &amp;&amp; systemctl restart apache2</code><br>
        atau aktifkan <code>exec</code> di <code>php.ini</code> (hapus dari <code>disable_functions</code>).';
}

// ── Proses import ─────────────────────────────────────────────────────────
if (!$fatalMsg && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_import']) && $fileExists) {
    $rows = readXlsxKompetensi($xlsxFile);
    if ($rows === false) {
        $fatalMsg = 'Gagal membaca file xlsx. Pastikan file tidak rusak.';
    } else {
        array_shift($rows); // skip header

        // Semua peserta diterima (tidak perlu sudah diwawancara untuk cocokkan nama)
        $pesertaMap = [];
        $res = $conn->query("SELECT id, nama FROM peserta WHERE status_seleksi = 'Diterima'");
        while ($p = $res->fetch_assoc()) {
            $key = strtoupper(trim(preg_replace('/\s+/', ' ', $p['nama'])));
            $pesertaMap[$key] = $p['id'];
        }

        $adminId = (int)($_SESSION['admin_id'] ?? 0);

        foreach ($rows as $row) {
            $namaExcel = trim($row['A'] ?? '');
            $nilaiRaw  = trim($row['B'] ?? '');

            if ($namaExcel === '' || $namaExcel === 'Nama Lengkap') continue;
            if (stripos($namaExcel, 'Keterangan') !== false) continue;

            $nilaiFloat = null;
            if ($nilaiRaw !== '' && $nilaiRaw !== '-') {
                $nilaiFloat = (float)str_replace(',', '.', $nilaiRaw);
            }

            $keyExcel  = strtoupper(trim(preg_replace('/\s+/', ' ', $namaExcel)));
            $pesertaId = $pesertaMap[$keyExcel] ?? null;

            if ($pesertaId) {
                $stmt = $conn->prepare("
                    INSERT INTO penilaian_akhir (peserta_id, nilai_kompetensi, updated_by, updated_at)
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                        nilai_kompetensi = VALUES(nilai_kompetensi),
                        updated_by       = VALUES(updated_by),
                        updated_at       = NOW()
                ");
                $stmt->bind_param('idi', $pesertaId, $nilaiFloat, $adminId);
                $stmt->execute();
                $imported++;
            } else {
                $unmatched[] = ['nama' => $namaExcel, 'nilai' => $nilaiRaw];
            }
        }
        $done = true;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Nilai Kompetensi — SEMANIS 2026</title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/font/bootstrap-icons.css">
</head>
<body>
<div class="d-flex">
    <?php include '_sidebar.php'; ?>

    <div class="main-content w-100">
        <header class="topbar">
            <button class="topbar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <div>
                <div class="topbar-title">
                    <i class="bi bi-upload me-2" style="color:#f79039"></i>Import Nilai Kompetensi
                </div>
                <div class="topbar-sub">Sensus Ekonomi 2026 — BPS Kab. Toli-Toli</div>
            </div>
            <div class="topbar-right">
                <a href="penilaian_akhir.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </header>

        <div class="page-body" style="max-width:820px">

            <?php if ($fatalMsg): ?>
            <div class="alert alert-danger d-flex gap-2" style="border-radius:10px">
                <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0 mt-1"></i>
                <div><?= $fatalMsg ?></div>
            </div>

            <?php elseif (!$fileExists): ?>
            <div class="alert alert-danger d-flex gap-2 align-items-center" style="border-radius:10px">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div>
                    <strong>File tidak ditemukan.</strong><br>
                    Letakkan file <code>nilai_kompetensi.xlsx</code> di folder utama proyek, lalu muat ulang halaman ini.
                </div>
            </div>

            <?php elseif ($done): ?>

            <div class="card stat-card mb-4">
                <div class="card-header fw-bold" style="background:#f79039;color:#fff;border-radius:12px 12px 0 0;">
                    <i class="bi bi-check-circle-fill me-2"></i>Hasil Import
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-4">
                            <div class="p-3 rounded-3 text-center" style="background:#d1fae5">
                                <div class="fs-3 fw-bold" style="color:#16a34a"><?= $imported ?></div>
                                <div style="font-size:.78rem;color:#065f46">Berhasil diimport</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="p-3 rounded-3 text-center" style="background:#fee2e2">
                                <div class="fs-3 fw-bold" style="color:#dc2626"><?= count($unmatched) ?></div>
                                <div style="font-size:.78rem;color:#991b1b">Tidak cocok</div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($unmatched)): ?>
                    <div class="mt-3">
                        <div class="fw-semibold mb-2" style="font-size:.84rem;color:#dc2626">
                            <i class="bi bi-exclamation-circle-fill me-1"></i>
                            Nama tidak cocok di database (<?= count($unmatched) ?>):
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered" style="font-size:.8rem">
                                <thead class="table-light">
                                    <tr><th>#</th><th>Nama di Excel</th><th>Nilai</th></tr>
                                </thead>
                                <tbody>
                                <?php foreach ($unmatched as $j => $u): ?>
                                    <tr>
                                        <td class="text-muted"><?= $j + 1 ?></td>
                                        <td><?= htmlspecialchars($u['nama']) ?></td>
                                        <td><?= htmlspecialchars($u['nilai']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-warning mt-2 py-2" style="font-size:.8rem;border-radius:8px">
                            <i class="bi bi-info-circle me-1"></i>
                            Nama yang tidak cocok dapat diinput manual via tombol Edit di halaman Penilaian Akhir.
                        </div>
                    </div>
                    <?php endif; ?>

                    <a href="penilaian_akhir.php" class="btn text-white fw-semibold mt-2"
                       style="background:#f79039;border-color:#f79039">
                        <i class="bi bi-arrow-left me-1"></i>Kembali ke Penilaian Akhir
                    </a>
                </div>
            </div>

            <?php else: ?>

            <div class="card stat-card">
                <div class="card-header fw-bold" style="background:#f79039;color:#fff;border-radius:12px 12px 0 0;">
                    <i class="bi bi-file-earmark-excel-fill me-2"></i>Import dari nilai_kompetensi.xlsx
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2" style="font-size:.82rem;color:#166534">
                        <i class="bi bi-check-circle-fill"></i>
                        <span>
                            File ditemukan &nbsp;|&nbsp;
                            Metode baca:
                            <?php if (class_exists('ZipArchive')): ?>
                                <strong>ZipArchive</strong>
                            <?php elseif (function_exists('exec')): ?>
                                <strong>exec/unzip</strong>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="alert d-flex gap-2 align-items-start mb-3"
                         style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;color:#1d4ed8;font-size:.84rem;">
                        <i class="bi bi-info-circle-fill fs-5 flex-shrink-0 mt-1"></i>
                        <div>
                            Nama di Excel akan dicocokkan ke peserta secara <em>case-insensitive</em>.
                            Nilai kompetensi disimpan ke database dan ditampilkan di halaman Penilaian Akhir.<br>
                            <span class="fw-semibold" style="color:#b45309">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                Nilai yang sudah ada akan ditimpa.
                            </span>
                        </div>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="do_import" value="1">
                        <button type="submit" class="btn text-white fw-semibold px-4"
                                style="background:#2563eb;border-color:#2563eb">
                            <i class="bi bi-upload me-2"></i>Mulai Import
                        </button>
                        <a href="penilaian_akhir.php" class="btn btn-outline-secondary ms-2">Batal</a>
                    </form>
                </div>
            </div>

            <?php endif; ?>

        </div>
    </div>
</div>
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
