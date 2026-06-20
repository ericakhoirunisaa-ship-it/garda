<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';

$conn->query("CREATE TABLE IF NOT EXISTS sensus_ekonomi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    sls_code VARCHAR(20) NOT NULL,
    open_count INT DEFAULT 0,
    draft INT DEFAULT 0,
    submitted_by_pencacah INT DEFAULT 0,
    submitted_respondent INT DEFAULT 0,
    rejected INT DEFAULT 0,
    approved INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_email_sls (email, sls_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$kecNama = [
    '010' => 'Dampal Selatan', '020' => 'Dampal Utara',
    '030' => 'Dondo',          '031' => 'Ogodeide',
    '032' => 'Basidondo',      '040' => 'Baolan',
    '041' => 'Lampasio',       '050' => 'Galang',
    '060' => 'Tolitoli Utara', '061' => 'Dako Pemean',
];

$kdkec = trim($_GET['kdkec'] ?? 'all');
if ($kdkec !== 'all' && !isset($kecNama[$kdkec])) $kdkec = 'all';

$kecCond  = ($kdkec !== 'all') ? "SUBSTRING(sls_code,5,3)='" . $conn->real_escape_string($kdkec) . "'" : '';
$kecWhere = $kecCond ? "WHERE $kecCond" : '';

// Overall stats
$s = $conn->query("SELECT
    COALESCE(SUM(open_count),0)                                   AS total_open,
    COALESCE(SUM(draft),0)                                        AS total_draft,
    COALESCE(SUM(submitted_by_pencacah+submitted_respondent),0)   AS total_submitted,
    COALESCE(SUM(rejected),0)                                     AS total_rejected,
    COUNT(DISTINCT sls_code)                                      AS total_sls,
    COUNT(DISTINCT email)                                         AS total_petugas
FROM sensus_ekonomi $kecWhere")->fetch_assoc();

// Per kecamatan (always all for the bar chart overview)
$kecRows = $conn->query("SELECT
    SUBSTRING(sls_code,5,3) AS kec_code,
    COALESCE(SUM(open_count),0)                                   AS open,
    COALESCE(SUM(draft),0)                                        AS draft,
    COALESCE(SUM(submitted_by_pencacah+submitted_respondent),0)   AS submitted,
    COALESCE(SUM(rejected),0)                                     AS rejected,
    COUNT(DISTINCT sls_code)                                      AS sls
FROM sensus_ekonomi
GROUP BY SUBSTRING(sls_code,5,3)
ORDER BY kec_code")->fetch_all(MYSQLI_ASSOC);

$tabel_kec = [];
foreach ($kecRows as $k) {
    $tot  = (int)$k['open'] + (int)$k['draft'] + (int)$k['submitted'] + (int)$k['rejected'];
    $prog = $tot > 0 ? round($k['submitted'] / $tot * 100) : 0;
    $tabel_kec[] = [
        'kec_code'  => $k['kec_code'],
        'nama'      => $kecNama[$k['kec_code']] ?? 'Kec. ' . $k['kec_code'],
        'open'      => (int)$k['open'],
        'draft'     => (int)$k['draft'],
        'submitted' => (int)$k['submitted'],
        'rejected'  => (int)$k['rejected'],
        'total'     => $tot,
        'sls'       => (int)$k['sls'],
        'prog'      => $prog,
    ];
}

// Per petugas (filtered)
$petRows = $conn->query("SELECT
    email,
    COALESCE(SUM(open_count),0)                                   AS open,
    COALESCE(SUM(draft),0)                                        AS draft,
    COALESCE(SUM(submitted_by_pencacah+submitted_respondent),0)   AS submitted,
    COALESCE(SUM(rejected),0)                                     AS rejected,
    COUNT(DISTINCT sls_code)                                      AS sls
FROM sensus_ekonomi $kecWhere
GROUP BY email
ORDER BY submitted DESC, open DESC")->fetch_all(MYSQLI_ASSOC);

$tabel_petugas = [];
foreach ($petRows as $p) {
    $tot = (int)$p['open'] + (int)$p['draft'] + (int)$p['submitted'] + (int)$p['rejected'];
    $tabel_petugas[] = [
        'nama'      => explode('@', $p['email'])[0],
        'open'      => (int)$p['open'],
        'draft'     => (int)$p['draft'],
        'submitted' => (int)$p['submitted'],
        'rejected'  => (int)$p['rejected'],
        'total'     => $tot,
        'sls'       => (int)$p['sls'],
    ];
}

// Last update
$lu = $conn->query("SELECT DATE_FORMAT(MAX(updated_at),'%d %M %Y') AS lu FROM sensus_ekonomi")->fetch_assoc();
$lastUpdate = $lu['lu'] ?: date('d M Y');

echo json_encode([
    'stats'         => [
        'open'      => (int)$s['total_open'],
        'draft'     => (int)$s['total_draft'],
        'submitted' => (int)$s['total_submitted'],
        'rejected'  => (int)$s['total_rejected'],
        'sls'       => (int)$s['total_sls'],
        'petugas'   => (int)$s['total_petugas'],
    ],
    'tabel_kec'     => $tabel_kec,
    'tabel_petugas' => $tabel_petugas,
    'last_update'   => $lastUpdate,
], JSON_UNESCAPED_UNICODE);
