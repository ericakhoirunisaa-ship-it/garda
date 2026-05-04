<?php
// Mencegah output karakter aneh di awal
ob_start();
header('Content-Type: application/json');

// Tambahkan :3307 setelah localhost
$conn = new mysqli("localhost:3307", "root", "", "db_semanis");

if ($conn->connect_error) {
    die(json_encode(["error" => "Koneksi database gagal"]));
}

$kdkec_filter = isset($_GET['kdkec']) ? $conn->real_escape_string($_GET['kdkec']) : 'all';
$where = ($kdkec_filter !== 'all') ? "WHERE kdkec = '$kdkec_filter'" : "";

// 1. Ambil Stats
$res_stats = $conn->query("SELECT status, COUNT(*) as jml FROM pencacahan $where GROUP BY status");
$stats = ['SUBMITTED' => 0, 'OPEN' => 0, 'REJECTED' => 0];

if ($res_stats) {
    while($r = $res_stats->fetch_assoc()) { 
        $s = strtoupper($r['status']);
        // Mencocokkan status "SUBMITTED BY Pencacah" atau "OPEN"
        if (strpos($s, 'SUBMITTED') !== false) $stats['SUBMITTED'] += (int)$r['jml'];
        else if (strpos($s, 'OPEN') !== false) $stats['OPEN'] += (int)$r['jml'];
        else if (strpos($s, 'REJECTED') !== false) $stats['REJECTED'] += (int)$r['jml'];
    }
}

// 2. Data Tabel Kecamatan (Daftar Kecamatan Statis)
$nama_kec = [
    "010" => "Dampal Selatan", "020" => "Dampal Utara", "030" => "Dondo", 
    "040" => "Basidondo", "050" => "Ogodeide", "060" => "Lampasio", 
    "070" => "Baolan", "080" => "Galang", "090" => "Tolitoli Utara", "100" => "Dako Pamean"
];

$tabel_kec = [];
foreach($nama_kec as $kd => $nm) {
    if($kdkec_filter !== 'all' && $kdkec_filter !== $kd) continue;
    
    $res = $conn->query("SELECT COUNT(*) as total, 
                        SUM(CASE WHEN status LIKE '%SUBMITTED%' THEN 1 ELSE 0 END) as s 
                        FROM pencacahan WHERE kdkec = '$kd'");
    $d = $res->fetch_assoc();
    $total = (int)$d['total'];
    $sub = (int)$d['s'];
    $prog = ($total > 0) ? ($sub / $total) * 100 : 0;
    
    $tabel_kec[] = [
        'nama' => $nm, 
        'total' => $total, 
        'sub' => $sub, 
        'prog' => round($prog, 1)
    ];
}

// 3. Data Tabel Petugas (Maksimal 100 baris agar tidak berat)
$tabel_petugas = [];
$res_p = $conn->query("SELECT nama, kdkec, kddesa, kdsls, status FROM pencacahan $where LIMIT 100");
if ($res_p) {
    while($r = $res_p->fetch_assoc()) { 
        $tabel_petugas[] = $r; 
    }
}

// Membersihkan buffer dan kirim JSON
ob_end_clean();
echo json_encode([
    'stats' => $stats,
    'tabel_kec' => $tabel_kec,
    'tabel_petugas' => $tabel_petugas
]);
exit;