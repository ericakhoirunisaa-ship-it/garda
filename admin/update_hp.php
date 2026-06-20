<?php
require_once 'config.php';
requireLogin();
if (!isSuperAdmin()) { die('Hanya superadmin yang bisa menjalankan ini.'); }

$xlsxFile = dirname(__DIR__) . '/data se.xlsx';
if (!file_exists($xlsxFile)) { die('File data se.xlsx tidak ditemukan.'); }

// ── Pastikan kolom HP ada di tabel peserta (kompatibel semua versi MySQL) ────
(function() use ($conn) {
    $existing = [];
    $r = $conn->query("SHOW COLUMNS FROM peserta");
    while ($c = $r->fetch_assoc()) $existing[] = $c['Field'];
    $cols = ['merk_hp' => "VARCHAR(100) NULL", 'tipe_hp' => "VARCHAR(100) NULL", 'ram_hp' => "VARCHAR(50) NULL"];
    foreach ($cols as $col => $def) {
        if (!in_array($col, $existing)) {
            $conn->query("ALTER TABLE peserta ADD COLUMN `$col` $def");
        }
    }
})();

// ── Baca Excel ───────────────────────────────────────────────────────────────
function readXlsxHP($file) {
    $zip = new ZipArchive();
    if ($zip->open($file) !== true) return false;

    $strings = [];
    $ss = $zip->getFromName('xl/sharedStrings.xml');
    if ($ss) {
        $xml = simplexml_load_string($ss);
        foreach ($xml->si as $si) {
            $str = '';
            if (isset($si->t)) { $str = (string)$si->t; }
            else { foreach ($si->r as $r) { if (isset($r->t)) $str .= (string)$r->t; } }
            $strings[] = $str;
        }
    }

    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $xml = simplexml_load_string($sheetXml);
    $zip->close();

    $data = [];
    foreach ($xml->sheetData->row as $row) {
        $rowIdx  = (int)$row['r'] - 1;
        $rowData = array_fill(0, 24, '');
        foreach ($row->c as $cell) {
            $ref = (string)$cell['r'];
            preg_match('/([A-Z]+)(\d+)/', $ref, $m);
            $col = $m[1];
            $colIdx = 0;
            for ($j = 0; $j < strlen($col); $j++) {
                $colIdx = $colIdx * 26 + (ord($col[$j]) - ord('A') + 1);
            }
            $colIdx--;
            if ($colIdx >= 24) continue;
            $t = (string)$cell['t'];
            $v = isset($cell->v) ? (string)$cell->v : '';
            $rowData[$colIdx] = ($t === 's') ? ($strings[(int)$v] ?? '') : $v;
        }
        $data[$rowIdx] = $rowData;
    }
    ksort($data);
    return array_values($data);
}

$rows = readXlsxHP($xlsxFile);
if (!$rows) { die('Gagal membaca Excel.'); }
array_shift($rows); // hapus header

// ── Update peserta ────────────────────────────────────────────────────────────
$updated = 0; $skipped = 0; $notFound = [];

foreach ($rows as $r) {
    $nama   = trim($r[0] ?? '');
    $status = trim($r[2] ?? '');
    $merk   = trim($r[20] ?? '');
    $tipe   = trim($r[21] ?? '');
    $ram    = trim($r[22] ?? '');

    if ($status !== 'Diterima' || $nama === '') { $skipped++; continue; }
    if ($merk === '' && $tipe === '' && $ram === '') { $skipped++; continue; }

    $eNama = $conn->real_escape_string($nama);
    $eMerk = $conn->real_escape_string($merk);
    $eTipe = $conn->real_escape_string($tipe);
    $eRam  = $conn->real_escape_string($ram);

    $res = $conn->query("UPDATE peserta SET merk_hp='$eMerk', tipe_hp='$eTipe', ram_hp='$eRam'
                         WHERE nama='$eNama' AND status_seleksi='Diterima' LIMIT 1");
    if ($res && $conn->affected_rows > 0) {
        $updated++;
    } else {
        $notFound[] = htmlspecialchars($nama);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Update HP Peserta</title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
</head>
<body class="p-4">
<div class="card shadow-sm mx-auto" style="max-width:640px">
    <div class="card-header fw-bold" style="background:#f79039;color:#fff">
        <i class="bi bi-phone me-2"></i>Update Data HP Peserta dari Excel
    </div>
    <div class="card-body">
        <p><strong>Berhasil diperbarui:</strong> <span class="badge bg-success fs-6"><?= $updated ?></span></p>
        <p><strong>Dilewati (kosong/ditolak):</strong> <span class="badge bg-secondary"><?= $skipped ?></span></p>
        <?php if ($notFound): ?>
        <div class="alert alert-warning mt-2">
            <strong>Nama tidak cocok di database (<?= count($notFound) ?>):</strong>
            <ul class="mb-0 mt-1 small">
                <?php foreach ($notFound as $n): ?>
                <li><?= $n ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        <hr>
        <a href="index.php" class="btn btn-primary btn-sm">Kembali ke Daftar</a>
    </div>
</div>
</body>
</html>
