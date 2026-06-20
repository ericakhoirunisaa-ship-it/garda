<?php
require_once 'config.php';

// Keamanan: jalankan hanya sekali, hapus file ini setelahnya
$token = $_GET['token'] ?? '';
if ($token !== 'SE2026_IMPORT') {
    die('<h3>Akses ditolak.</h3><p>Tambahkan <code>?token=SE2026_IMPORT</code> di URL untuk menjalankan import.</p>');
}

$xlsxFile = __DIR__ . '/data se.xlsx';
if (!file_exists($xlsxFile)) {
    die('File "data se.xlsx" tidak ditemukan di root proyek.');
}

// ===== XLSX Reader (tanpa library eksternal) =====
function readXlsxSheet($file) {
    $zip = new ZipArchive();
    if ($zip->open($file) !== true) return false;

    // Shared strings
    $strings = [];
    $ss = $zip->getFromName('xl/sharedStrings.xml');
    if ($ss) {
        $xml = simplexml_load_string($ss);
        foreach ($xml->si as $si) {
            $str = '';
            if (isset($si->t)) {
                $str = (string)$si->t;
            } else {
                foreach ($si->r as $r) {
                    if (isset($r->t)) $str .= (string)$r->t;
                }
            }
            $strings[] = $str;
        }
    }

    // Sheet data
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $xml = simplexml_load_string($sheetXml);
    $zip->close();

    $data = [];
    $maxCol = 23; // baca sampai kolom W (RAM HP)

    foreach ($xml->sheetData->row as $row) {
        $rowIdx = (int)$row['r'] - 1;
        $rowData = array_fill(0, $maxCol, '');
        foreach ($row->c as $cell) {
            $ref = (string)$cell['r'];
            preg_match('/([A-Z]+)(\d+)/', $ref, $m);
            $col = $m[1];
            $colIdx = 0;
            for ($j = 0; $j < strlen($col); $j++) {
                $colIdx = $colIdx * 26 + (ord($col[$j]) - ord('A') + 1);
            }
            $colIdx--;
            if ($colIdx >= $maxCol) continue;

            $t = (string)$cell['t'];
            $v = isset($cell->v) ? (string)$cell->v : '';
            if ($t === 's') {
                $rowData[$colIdx] = $strings[(int)$v] ?? '';
            } else {
                $rowData[$colIdx] = $v;
            }
        }
        $data[$rowIdx] = $rowData;
    }
    ksort($data);
    return array_values($data);
}

$rows = readXlsxSheet($xlsxFile);
if (!$rows) { die('Gagal membaca file Excel.'); }

// Hapus header row
array_shift($rows);

$imported = 0;
$skipped  = 0;
$errors   = [];

// Pastikan kolom HP ada di tabel peserta (kompatibel semua versi MySQL)
(function() use ($conn) {
    $existing = [];
    $r = $conn->query("SHOW COLUMNS FROM peserta");
    while ($c = $r->fetch_assoc()) $existing[] = $c['Field'];
    foreach (['merk_hp' => "VARCHAR(100) NULL", 'tipe_hp' => "VARCHAR(100) NULL", 'ram_hp' => "VARCHAR(50) NULL"] as $col => $def) {
        if (!in_array($col, $existing)) {
            $conn->query("ALTER TABLE peserta ADD COLUMN `$col` $def");
        }
    }
})();

$stmt = $conn->prepare("INSERT INTO peserta
    (nama, posisi, status_seleksi, posisi_daftar, alamat_detail,
     alamat_prov, alamat_kab, alamat_kec, alamat_desa,
     ttl, jenis_kelamin, pendidikan, pekerjaan, deskripsi_pekerjaan,
     no_telp, sobat_id, email, status_nik, verifikasi_pada,
     merk_hp, tipe_hp, ram_hp)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

foreach ($rows as $i => $r) {
    $nama   = trim($r[0] ?? '');
    $status = trim($r[2] ?? '');

    // Hanya import yang Diterima
    if ($status !== 'Diterima' || $nama === '') {
        $skipped++;
        continue;
    }

    $stmt->bind_param("ssssssssssssssssssssss",
        $r[0],  // nama
        $r[1],  // posisi
        $r[2],  // status_seleksi
        $r[3],  // posisi_daftar
        $r[4],  // alamat_detail
        $r[5],  // alamat_prov
        $r[6],  // alamat_kab
        $r[7],  // alamat_kec
        $r[8],  // alamat_desa
        $r[9],  // ttl
        $r[10], // jenis_kelamin
        $r[11], // pendidikan
        $r[12], // pekerjaan
        $r[13], // deskripsi_pekerjaan
        $r[14], // no_telp
        $r[15], // sobat_id
        $r[16], // email
        $r[17], // status_nik
        $r[18], // verifikasi_pada
        $r[20], // merk_hp
        $r[21], // tipe_hp
        $r[22]  // ram_hp
    );

    if ($stmt->execute()) {
        $imported++;
    } else {
        $errors[] = "Baris " . ($i+2) . " ({$r[0]}): " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Import Peserta - SE2026</title>
    <link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css">
</head>
<body class="p-5">
    <div class="card max-width: 600px mx-auto shadow-sm" style="max-width:600px">
        <div class="card-header bg-success text-white fw-bold">
            <i class="bi bi-check-circle me-2"></i>Hasil Import Data Peserta
        </div>
        <div class="card-body">
            <p><strong>Berhasil diimpor:</strong> <span class="badge bg-success fs-6"><?= $imported ?> peserta</span></p>
            <p><strong>Dilewati (Ditolak/kosong):</strong> <span class="badge bg-secondary"><?= $skipped ?></span></p>
            <?php if ($errors): ?>
                <div class="alert alert-warning">
                    <strong>Error:</strong>
                    <ul class="mb-0 mt-1">
                        <?php foreach ($errors as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <hr>
            <p class="text-danger fw-semibold"><i class="bi bi-exclamation-triangle me-1"></i>
                Hapus atau amankan file ini setelah import selesai!</p>
            <a href="admin/index.php" class="btn btn-primary">Buka Halaman Admin</a>
        </div>
    </div>
</body>
</html>
