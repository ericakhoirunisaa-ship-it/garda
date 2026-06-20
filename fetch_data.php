<?php
ob_start();
header('Content-Type: application/json');

require_once __DIR__ . '/config.php';

if ($conn->connect_error) {
    ob_end_clean();
    echo json_encode(["error" => "Koneksi database gagal"]);
    exit;
}

// Auto-create & migrasi tabel
$conn->query("CREATE TABLE IF NOT EXISTS pencacahan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(150) NOT NULL,
    kdkec VARCHAR(10) NOT NULL,
    kddesa VARCHAR(100) NULL,
    kdsls VARCHAR(100) NULL,
    status VARCHAR(50) DEFAULT 'OPEN',
    catatan TEXT NULL,
    tanggal_update DATE NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$existCols = [];
$cr = $conn->query("SHOW COLUMNS FROM pencacahan");
while ($c = $cr->fetch_assoc()) $existCols[] = $c['Field'];
if (!in_array('id', $existCols))
    $conn->query("ALTER TABLE pencacahan ADD COLUMN id INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
if (!in_array('tanggal_update', $existCols))
    $conn->query("ALTER TABLE pencacahan ADD COLUMN tanggal_update DATE NULL");
if (!in_array('catatan', $existCols))
    $conn->query("ALTER TABLE pencacahan ADD COLUMN catatan TEXT NULL");
$conn->query("ALTER TABLE pencacahan MODIFY COLUMN kdkec VARCHAR(10)");
$conn->query("ALTER TABLE pencacahan MODIFY COLUMN kddesa VARCHAR(100)");
$conn->query("ALTER TABLE pencacahan MODIFY COLUMN kdsls VARCHAR(100)");

$kdkec_filter = isset($_GET['kdkec']) ? $conn->real_escape_string($_GET['kdkec']) : 'all';
$where = ($kdkec_filter !== 'all') ? "WHERE kdkec = '$kdkec_filter'" : "";

// 1. Stats
$res_stats = $conn->query("SELECT status, COUNT(*) as jml FROM pencacahan $where GROUP BY status");
$stats = ['SUBMITTED' => 0, 'OPEN' => 0, 'REJECTED' => 0];
if ($res_stats) {
    while ($r = $res_stats->fetch_assoc()) {
        $s = strtoupper($r['status']);
        if ($s === 'SUBMITTED') $stats['SUBMITTED'] += (int)$r['jml'];
        elseif ($s === 'OPEN')  $stats['OPEN']      += (int)$r['jml'];
        elseif ($s === 'REJECTED') $stats['REJECTED'] += (int)$r['jml'];
    }
}

// 2. Tabel Kecamatan
$nama_kec = [
    "010" => "Dampal Selatan", "020" => "Dampal Utara",
    "030" => "Dondo",          "031" => "Ogodeide",
    "032" => "Basidondo",      "040" => "Baolan",
    "041" => "Lampasio",       "050" => "Galang",
    "060" => "Tolitoli Utara", "061" => "Dako Pemean",
];

$tabel_kec = [];
foreach ($nama_kec as $kd => $nm) {
    if ($kdkec_filter !== 'all' && $kdkec_filter !== $kd) continue;
    $res = $conn->query("SELECT COUNT(*) as total,
                        SUM(CASE WHEN status='SUBMITTED' THEN 1 ELSE 0 END) as s
                        FROM pencacahan WHERE kdkec='$kd'");
    $d = $res->fetch_assoc();
    $total = (int)$d['total'];
    $sub   = (int)$d['s'];
    $prog  = $total > 0 ? round($sub / $total * 100, 1) : 0;
    $tabel_kec[] = ['nama' => $nm, 'total' => $total, 'sub' => $sub, 'prog' => $prog];
}

// 3. Tabel Petugas (maks 200)
$tabel_petugas = [];
$res_p = $conn->query("SELECT nama, kdkec, kddesa, kdsls, status FROM pencacahan $where ORDER BY tanggal_update DESC, id DESC LIMIT 200");
if ($res_p) {
    while ($r = $res_p->fetch_assoc()) $tabel_petugas[] = $r;
}

// 4. Last update
$lu_row = $conn->query("SELECT MAX(tanggal_update) as lu FROM pencacahan")->fetch_assoc();
$last_update = $lu_row['lu'] ? date('d M Y', strtotime($lu_row['lu'])) . ' WITA' : 'Belum ada data';

ob_end_clean();
echo json_encode([
    'stats'          => $stats,
    'tabel_kec'      => $tabel_kec,
    'tabel_petugas'  => $tabel_petugas,
    'last_update'    => $last_update,
]);
exit;
