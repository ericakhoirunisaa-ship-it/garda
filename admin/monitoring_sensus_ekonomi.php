<?php
require_once 'config.php';
requireLogin();

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
    `revoke` INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$chk = $conn->query("SHOW COLUMNS FROM sensus_ekonomi LIKE 'revoke'");
if ($chk && $chk->num_rows === 0) {
    $conn->query("ALTER TABLE sensus_ekonomi ADD COLUMN `revoke` INT DEFAULT 0 AFTER approved");
}
$chkIdx = $conn->query("SHOW INDEX FROM sensus_ekonomi WHERE Key_name = 'uq_email_sls'");
if ($chkIdx && $chkIdx->num_rows > 0) {
    $conn->query("ALTER TABLE sensus_ekonomi DROP INDEX uq_email_sls");
}

$kecNama = [
    '010' => 'Dampal Selatan', '020' => 'Dampal Utara',
    '030' => 'Dondo',          '031' => 'Ogodeide',
    '032' => 'Basidondo',      '040' => 'Baolan',
    '041' => 'Lampasio',       '050' => 'Galang',
    '060' => 'Tolitoli Utara', '061' => 'Dako Pemean',
];

// Email → Nama Petugas (sinkronisasi dari email.xlsx, semua key lowercase)
$emailNama = [
    'wirtakwir8@gmail.com'           => 'Takwir',
    'nurazizalakoro@gmail.com'        => 'Nuraziza',
    'sugengarrauf@gmail.com'          => 'Sugeng. A',
    'lyliburhan99@gmail.com'          => 'Eriyanti S',
    'rahmiagussalim43@gmail.com'      => 'Rahmi Agussalim',
    'mukranumran@gmail.com'           => 'Mukran',
    'azizanihru@gmail.com'            => 'Aziza',
    'ramnitolis2023@gmail.com'        => 'RAMNI',
    'fitri.ani0@yahoo.com'            => 'Fitriani',
    'ahmadsumitro96@gmail.com'        => 'Ahmad',
    'sriwahyunimakkuraga@gmail.com'   => 'Sri wahyuni',
    'sahratulaini99@gmail.com'        => 'SAHRATUL AINI',
    'hashari.h.hh@gmail.com'          => 'Hashari',
    'armina160298@gmail.com'          => 'Armina',
    'ulfiani.ani992@gmail.com'        => 'ULPIANI RUSNO',
    'dewisosiartika@gmail.com'        => 'DEWI SOSIARTIKA',
    'syahdigalumpang@gmail.com'       => 'SYAHDI',
    'afriantomuhsen756@gmail.com'     => 'Afrianto',
    'albarbar24@gmail.com'            => 'ALBAR',
    'halfinafhyna@gmail.com'          => 'HALFINA',
    '02lovelymore@gmail.com'          => 'FARADHIBA',
    'farida181198@gmail.com'          => 'Farida',
    'karnitanita808@gmail.com'        => 'KARNITA',
    'meyandris29@gmail.com'           => 'MEYSAHDI. A',
    'muliantii1996@gmail.com'         => 'Mulianti Maulana',
    'misnajo1@gmail.com'              => 'Misna',
    'meghanaharudin763@gmail.com'     => 'Sulastri',
    'wahyudiforester6@gmail.com'      => 'WAHYUDI MOH HARIS',
    'iffatpratama321@gmail.com'       => 'Moh. Iffat Widad Pratama',
    'ffara8629@gmail.com'             => 'Farah',
    'faisalchaldo@gmail.com'          => 'Moh faisal',
    'nurulayustina198@gmail.com'      => 'Nurul Ayustina',
    'trimuliadi00@gmail.com'          => 'TRI MULIADI',
    'latumpu77@gmail.com'             => 'yusuf',
    'mutmainna140201@gmail.com'       => 'Mutmainna',
    'nurdianadina1220@gmail.com'      => 'Nurdiana Zaharman',
    'saniyyah0410@gmail.com'          => 'SANIYYAH NUR PURNAMA S.Sos',
    'ayusyawitri22@gmail.com'         => 'SRI DEVI AYU SYAWITRI',
    'usuci8993@gmail.com'             => 'Suci Safitry',
    'nurhajra08092019@gmail.com'      => 'NUR HAJRA SYAFRUDDIN',
    'indahade247@gmail.com'           => 'ADE INDAH PUSPITA SARI',
    'ahmaadtlz17@gmail.com'           => 'AHMAD SAID',
    'fitriyantimarhalil@gmail.com'    => 'Fitriyanti',
    'umijarnawi@gmail.com'            => 'JUMIATI',
    'wulandarisb981@gmail.com'        => 'Wulandari S. Butudoka',
    'nanadjapara10@gmail.com'         => 'Nurhasanah',
    'raramutiara353@gmail.com'        => 'ANDI MUTIARA ISTIQHAMARANI',
    'iyasdwi996@gmail.com'            => 'Dwi wahyono',
    'fitriyawatitadore@gmail.com'     => 'FITRIYAWATI NH HANS TADORE',
    'astuti7061992@gmail.com'         => 'Tri astuti',
    'deliyhana2022@gmail.com'         => 'SRI DELY',
    'mohfajrin219@gmail.com'          => 'Moh. Fajrin',
    'maulana022007@gmail.com'         => 'MOH. RISKY MAULANA',
    'rosnirauf@gmail.com'             => 'ROSNI',
    'wahyuniarfan1989@gmail.com'      => 'Sri wahyuni syaripudin',
    'rizkika357@gmail.com'            => 'Rizkyka Putri',
    'septiyadimerdekap@gmail.com'     => 'SEPTYADI MERDEKA PUTRA',
    'sriwahyunikmajah@gmail.com'      => 'SRI WAHYUNI K.MAJAH',
    'zhaafirah030501@gmail.com'       => 'Yusriyyah Zhaafirah',
    'nuraida25041981@gmail.com'       => 'Nuraida',
    'ramsaanca177@gmail.com'          => 'RAMSA',
    'fajarirawan0501@gmail.com'       => 'Fajar irawan',
    'yusnihidayah21@gmail.com'        => 'Yusni Hidayah',
    'tinasuhar65@gmail.com'           => 'Suhartina',
    'irnawati170792@gmail.com'        => 'IRNAWATI ASHAR',
    'yuyundiahpratiwi2397@gmail.com'  => 'YUYUN DIAH PRATIWI',
    'niluhulan@gmail.com'             => 'Ni Luh Ulan Sariani',
    'dessy261297@gmail.com'           => 'DESSY PUTRI AMALIA',
    'ikkenvs@gmail.com'               => 'Ikke Nur Vita Sari',
    'nurinsyan14@gmail.com'           => 'NURINSYAN',
    'animariany16@gmail.com'          => 'Mariani',
    'moh.reza992@gmail.com'           => 'MOHAMMAD REZA',
    'yunusmuh580@gmail.com'           => 'Muh. Yunus',
    'anif73599@gmail.com'             => 'Pitriani',
    'hasnirizal06@gmail.com'          => 'Hasni Rizal',
    'tutiwinarni628@gmail.com'        => 'Tuti Winarni',
    'tinatangkuman102@gmail.com'      => 'Hartina',
    'lelaalfiana@gmail.com'           => 'Alfiana',
    'iqbalm2410@gmail.com'            => 'Moh Iqbal',
    'moh.khairaat@gmail.com'          => 'Moh. Mulkan',
    'suhradinad@gmail.com'            => 'SUHRADINA',
    'liaa301102@gmail.com'            => 'Lia Andriani',
    'indrayaniburhanudin@gmail.com'   => 'Indrayani Burhanudin',
    'smia89864@gmail.com'             => 'Salmia',
    'srihardiyantii26@gmail.com'      => 'Sri Hardiyanti',
    'aliflibiya254@gmail.com'         => 'Alif Libiya Melani',
    '082293jya@gmail.com'             => 'Nurjaya',
    'budimann225@gmail.com'           => 'Budiman',
    'deviarni01@gmail.com'            => 'Deviana',
    'darachandra2@gmail.com'          => 'Chandra',
    'annisarahma16102002@gmail.com'   => 'Annisa Rahmayanti',
    'melinndaa26@gmail.com'           => 'Melinda',
    'ammargonggol@gmail.com'          => 'Muammar',
    'riswanaswaludin4@gmail.com'      => 'Moh. Riswan Aswaludin',
    'novianivhy34@gmail.com'          => 'Noviani',
    'sittihajarsalam63@gmail.com'     => 'Siti Hajar',
    'susantitolis2018@gmail.com'      => 'Susanti',
    'tritakdir753@gmail.com'          => 'TRI TAKDIR U SYAH',
    'puspitasaritolis2020@gmail.com'  => 'Puspita Sari',
    'asdardahyar@gmail.com'           => 'ASDAR',
    'nvtasariy@gmail.com'             => 'Novita sari',
    'nia.060803@gmail.com'            => 'Asmania',
    '2000padlia@gmail.com'            => 'PADLIA',
    'rhiahijria994@gmail.com'         => 'Fahrin',
    'fitryani0102@gmail.com'          => 'Fitriani',
    'ibrahimanthonie0707@gmail.com'   => 'Ibrahim anthonie',
    'fatihfatir97@gmail.com'          => 'Marta',
    'zeynzacky2023@gmail.com'         => 'MOHAMAD YASIR',
    'nasriajamrin2002@gmail.com'      => 'Nasria',
    'rahmatialukman27@gmail.com'      => 'RAHMATIA',
    'noviawulandari129@gmail.com'     => 'Novia Edi Wulandari',
    'hasrianihamzah579@gmail.com'     => 'Hasriani',
    'misnasupriadi935@gmail.com'      => 'MISNA',
    'zulkifliusman1997@gmail.com'     => 'ZULKIFLI USMAN',
    'sasmita08082004@gmail.com'       => 'Sasmita',
    'nasirhartati77@gmail.com'        => 'Hartati',
    'magfira066@gmail.com'            => 'MAGFIRA',
    'ikamulyady@gmail.com'            => 'Kartika',
    'abbdulsamad61@gmail.com'         => 'Abd Samad',
    'reniamalia978@gmail.com'         => 'RENI AMALIA',
    'irayuliastari1@gmail.com'        => 'Ira yuliastari',
    'sardianaj@gmail.com'             => 'Sardiana',
    'damsel.bangkir43@gmail.com'      => 'Rezkiyani yaya',
    'siskaikha310@gmail.com'          => 'Siska',
    'riniarditasari18@gmail.com'      => 'Rini Ardita Sari',
    'rahmatiabahri@gmail.com'         => 'Rahmatia',
    'kadirmankadir3@gmail.com'        => 'Kadirman',
    'srisutanti4767@gmail.com'        => 'SRI SUTANTi',
    'qolbirahmaaulia@gmail.com'       => 'Rahma Aulia',
    'hasrianisri061@gmail.com'        => 'Hasriani',
    'andimariam249@gmail.com'         => 'Andi mariam',
    'andanghamdan51@gmail.com'        => 'HAMDAN',
    'silfaniagus27@gmail.com'         => 'Silfani',
    'esiastarita394@gmail.com'        => 'Esi Astarita',
    'astarita832@gmail.com'           => 'IRANINGSIH',
    'winisudarmin23@gmail.com'        => 'WINI',
    'mokhoramli@gmail.com'            => 'Harmoko',
    'miftahjannah1924@gmail.com'      => 'Miftahul Jannah',
    'dhevyaries07@gmail.com'          => 'Devi afrianingsi',
    'miftachulannah@gmail.com'        => 'MIFTAHUL JANNAH',
    'tasyi679@gmail.com'              => 'Natasya',
    'lukmanpramuka13@gmail.com'       => 'LUKMAN',
    'syafiahfiah232@gmail.com'        => 'Syafiah',
    'trisyetrisnawati01@gmail.com'    => 'Trisye Trisnawati',
    'wana061101@gmail.com'            => 'NISWANA',
    'yunianang04@gmail.com'           => 'Wahyuni',
    'pianoviani2911@gmail.com'        => 'Noviani',
    'bluebubblegum201@gmail.com'      => 'AFIFAH TRI HUMAIRAH',
    'hallowdaya@gmail.com'            => 'Nurhidayah',
    'abjadalam9@gmail.com'            => 'Sudirman',
    'desiraihana8896@gmail.com'       => 'Desi Raihana',
    'ramlicovid@gmail.com'            => 'RAMLI',
    'tinaahada@gmail.com'             => 'Siti hartina',
    'nrfaisah24@gmail.com'            => 'Nur faisah',
    'samrins365@gmail.com'            => 'Samrin',
    'ekarezkiana11@gmail.com'         => 'Eka Rezkiana S. N. Daud',
    'lisstiawati10@gmail.com'         => 'Lis Setyawati',
    'ikkireskiikkireski199@gmail.com'  => 'RESKI',
    'irhamdhanial23@gmail.com'        => 'M IRHAM',
    'abdulbasar181010129@gmail.com'   => 'ABDUL BASAR',
    'nurinurhasanah46@gmail.com'      => 'Nuri Nurhasanah',
    'sartika.awaludin@gmail.com'      => 'Sartika',
    'moh.haris2025@gmail.com'         => 'Moh Haris',
    'rizalreynaldy.rr@gmail.com'      => 'MOH. RIZAL REYNALDY',
    'sindy.tolis22@gmail.com'         => 'SINDI AMELIA',
    'mirzabahtiar87@gmail.com'        => 'MIRZA',
    'samitmidong@gmail.com'           => 'Samid',
    'hasmanman836@gmail.com'          => 'HASMAN',
    'fadeld76@gmail.com'              => 'Moh Fadel',
    'tolutjayamandiri@gmail.com'      => 'Leni Liana',
    'marwarusno2000@gmail.com'        => 'Marwa Rusno',
    'sayidatunnisah@gmail.com'        => 'SAYIDATUN NISAH',
    'eetarf03@gmail.com'              => 'Moh. Etsyakhran',
    'tahwiltahir@gmail.com'           => 'Tahwil',
    'andisingkarahmadana@gmail.com'   => 'ANDI SINGKA RAHMADANA',
    'yulianalatape@gmail.com'         => 'Yuliana',
    'delilarf3009@gmail.com'          => 'Delila Rizky Fauzia',
    'zahraanggraini9722@gmail.com'    => 'ZAHRA FIRTSZA ANGGRAINI',
    'artalitagafur@gmail.com'         => 'Artalita',
    'setiawanrahmat50509@gmail.com'   => 'RAHMAT SETIAWAN',
    'yunitatolis5@gmail.com'          => 'Yunita Arifin',
    'pong290597@gmail.com'            => 'EVAYANA',
    'ikhsanwiratama2025@gmail.com'    => 'Ikhsan',
    'mohikhwan826@gmail.com'          => 'Moh Ikhwan',
    'pratiwihalil101@gmail.com'       => 'Pratiwi',
    'ihsan.dumbo11@gmail.com'         => 'Ihsan',
    'jalangkoteibuudin26@gmail.com'   => 'ANDI JUMRIANI',
    'aisyahsah456@gmail.com'          => 'Nur Aisyah',
    'kurniatirustam21@gmail.com'      => 'Kurniati',
    'hafidzahnurul61@gmail.com'       => 'Hafidzah Nurul Millah. Z',
    'ma147696@gmail.com'              => 'Moh. Agung',
    'ikaingguti@gmail.com'            => 'Nurwahida H Ingguti',
    'mohhamkadahlan271299@gmail.com'  => 'MOH HAMKA',
    'efry0222@gmail.com'              => 'Efriwanda',
    'fadlina.linaa2003@gmail.com'     => 'FADLINA',
    'nahdatulrahman@gmail.com'        => 'Nahdatu Rahma',
    'erwinpradistyaa@gmail.com'       => 'Erwin',
    'arumijennaira1341@gmail.com'     => 'Mega Alvionita',
    'miftahuljanna.mjii@gmail.com'    => 'Miftahul Janna',
    'dindaaprilya222@gmail.com'       => 'Dinda Aprilya',
    'ernihasbi56@gmail.com'           => 'ERNY',
    'viivikusuma@gmail.com'           => 'Silvie Sri Kusuma',
    'fitriwahyuningsih116@gmail.com'  => 'Fitri Wahyu Ningsih',
    'muhainiyusuf@gmail.com'          => 'MUHAINI YUSUF',
    'ammankmuhsalman@gmail.com'       => 'Muhammad salman',
    'intanprdtha24@gmail.com'         => 'Intan Paraditha',
    'fajaraswah53@gmail.com'          => 'MUH FAJAR ASWAH',
    'ririnmarliana1430@gmail.com'     => 'Ririn marliana',
    'adfadilah06@gmail.com'           => 'Fadila',
    'safiraramadani1999@gmail.com'    => 'Sapira Ramadani',
    'alwizihab285@gmail.com'          => 'ALWI',
    'nandapade@icloud.com'            => 'Albar Sukri',
    'rinipntoh@gmail.com'             => 'Rini.s',
    'vhiasilvia2020@gmail.com'        => 'Vina silvia',
    'shajaredwin@gmail.com'           => 'Sitti Hajar E. Dt. Amas',
    'tampubolonsahala49@gmail.com'    => 'SAHALA TAMPUBOLON',
    'niskayhabu@gmail.com'            => 'Niska Y. Habu',
    'wahyuliadjmallo@gmail.com'       => 'WAHYULIA',
    'mawar191291@gmail.com'           => 'Mawar',
    'iketutpujaastawadiputra@gmail.com' => 'I ketut puja astawa diputra',
    'lisaxnx20@gmail.com'             => 'Sitti Nurhaliza',
    'andinipontoh1@gmail.com'         => 'ANDINI S A PONTOH',
    'nurlindahramadhanyy06@gmail.com' => 'Nurlindah Ramadhany',
    'sitierlinaarahmanerlina@gmail.com' => 'Siti Erlin A Rahman',
    'gamainumaki@gmail.com'           => 'Ananda zesakarti pramana putra',
    'jusmahwati08@gmail.com'          => 'Jusmahwati',
    'indarwandi937@gmail.com'         => 'Indarwati',
    'musliaditolis@gmail.com'         => 'Sitti kurnia',
    'agustinaxx02@gmail.com'          => 'Agustina',
    'nurhasidahs125@gmail.com'        => 'Nurhasida HS',
    'sritutialawia@gmail.com'         => 'SRITUTI ALAWIA',
    'nurnawiraazzhara@gmail.com'      => 'Nur Nawira Azzhara',
    'idjafar095@gmail.com'            => 'MUHAMMAD NURDIANSYAH S DJAFAR',
    'djumaopall@gmail.com'            => 'ALHAMDANI',
    'nafilatulyulmaz@gmail.com'       => 'YULIANA',
    'menni010429@gmail.com'           => 'Sumarni',
    'sridevilula991115@gmail.com'     => 'Sri devi',
    'mohtaufanalfareza@gmail.com'     => 'Muh. Taufan Al Fareza',
];

// Helper: ambil nama dari email (case-insensitive)
$getNama = fn($email) => $emailNama[strtolower($email)] ?? $email;

$kecFilter = trim($_GET['kec'] ?? 'all');
if ($kecFilter !== 'all' && !isset($kecNama[$kecFilter])) $kecFilter = 'all';

$kecCond  = $kecFilter !== 'all'
    ? "SUBSTRING(sls_code, 5, 3) = '" . $conn->real_escape_string($kecFilter) . "'"
    : '';
$kecWhere = $kecCond ? "WHERE $kecCond" : '';

$stats = $conn->query("SELECT
    COALESCE(SUM(open_count), 0)                                   AS total_open,
    COALESCE(SUM(draft), 0)                                        AS total_draft,
    COALESCE(SUM(submitted_by_pencacah), 0) AS total_submitted,
    COALESCE(SUM(rejected), 0)                                     AS total_rejected,
    COALESCE(SUM(approved), 0)                                     AS total_approved,
    COALESCE(SUM(`revoke`), 0)                                     AS total_revoke,
    COUNT(DISTINCT email)                                          AS total_petugas,
    COUNT(DISTINCT sls_code)                                       AS total_sls
FROM sensus_ekonomi $kecWhere")->fetch_assoc();

// Waktu update data terakhir
$lastUpdateRow = $conn->query("SELECT MAX(GREATEST(created_at, COALESCE(updated_at, '2000-01-01'))) AS last_update FROM sensus_ekonomi")->fetch_assoc();
$lastUpdate = $lastUpdateRow['last_update'];
$lastUpdateStr = $lastUpdate ? date('d M Y H:i', strtotime($lastUpdate)) . ' WIB' : null;

$globalDenominator = (int)$stats['total_open'] + (int)$stats['total_draft']
                   + (int)$stats['total_submitted'] + (int)$stats['total_rejected']
                   + (int)$stats['total_approved'] + (int)$stats['total_revoke'];
$globalNumerator   = (int)$stats['total_submitted'] + (int)$stats['total_rejected']
                   + (int)$stats['total_approved'] + (int)$stats['total_revoke'];
$globalPct = $globalDenominator > 0
    ? number_format($globalNumerator / $globalDenominator * 100, 2)
    : '0.00';

$perPetugas = $conn->query("SELECT
    email,
    COALESCE(SUM(open_count), 0)                                   AS total_open,
    COALESCE(SUM(draft), 0)                                        AS total_draft,
    COALESCE(SUM(submitted_by_pencacah), 0)                        AS submitted_pencacah,
    COALESCE(SUM(submitted_respondent), 0)                         AS submitted_resp,
    COALESCE(SUM(submitted_by_pencacah), 0) AS total_submitted,
    COALESCE(SUM(rejected), 0)                                     AS total_rejected,
    COALESCE(SUM(approved), 0)                                     AS total_approved,
    COALESCE(SUM(`revoke`), 0)                                     AS total_revoke,
    COUNT(DISTINCT sls_code)                                       AS total_sls
FROM sensus_ekonomi $kecWhere
GROUP BY email
ORDER BY total_submitted DESC, total_open DESC")->fetch_all(MYSQLI_ASSOC);

$perKec = $conn->query("SELECT
    SUBSTRING(sls_code, 5, 3) AS kec_code,
    COALESCE(SUM(open_count), 0)                                   AS total_open,
    COALESCE(SUM(draft), 0)                                        AS total_draft,
    COALESCE(SUM(submitted_by_pencacah), 0) AS total_submitted,
    COALESCE(SUM(rejected), 0)                                     AS total_rejected,
    COALESCE(SUM(approved), 0)                                     AS total_approved,
    COALESCE(SUM(`revoke`), 0)                                     AS total_revoke,
    COUNT(DISTINCT email)                                          AS total_petugas,
    COUNT(DISTINCT sls_code)                                       AS total_sls
FROM sensus_ekonomi
GROUP BY SUBSTRING(sls_code, 5, 3)
ORDER BY kec_code")->fetch_all(MYSQLI_ASSOC);

// Label chart: gunakan nama depan petugas
$chartLabels = json_encode(array_map(function($p) use ($getNama, $emailNama) {
    $nama = $getNama($p['email']);
    // Jika nama ditemukan di mapping, ambil kata pertama; jika tidak, pakai prefix email
    if (isset($emailNama[strtolower($p['email'])])) {
        return explode(' ', $nama)[0];
    }
    return explode('@', $p['email'])[0];
}, $perPetugas));

$chartOpen      = json_encode(array_map('intval', array_column($perPetugas, 'total_open')));
$chartDraft     = json_encode(array_map('intval', array_column($perPetugas, 'total_draft')));
$chartSubmitted = json_encode(array_map('intval', array_column($perPetugas, 'total_submitted')));
$chartRejected  = json_encode(array_map('intval', array_column($perPetugas, 'total_rejected')));
$chartApproved  = json_encode(array_map('intval', array_column($perPetugas, 'total_approved')));
$chartRevoke    = json_encode(array_map('intval', array_column($perPetugas, 'total_revoke')));
$doughnutData   = json_encode([
    (int)$stats['total_open'],
    (int)$stats['total_draft'],
    (int)$stats['total_submitted'],
    (int)$stats['total_rejected'],
    (int)$stats['total_approved'],
    (int)$stats['total_revoke'],
]);
$barWidth = max(600, count($perPetugas) * 60);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Sensus Ekonomi — SEMANIS 2026</title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        .stat-icon { width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; }
        .section-head { font-weight:700; font-size:.7rem; text-transform:uppercase; letter-spacing:.08em; color:#94a3b8; margin-bottom:14px; }
        .table-hover tbody tr:hover { background:#fff8f0; }
        .chart-scroll { overflow-x:auto; }
        .nav-tabs .nav-link { color:#64748b; font-size:.83rem; font-weight:600; }
        .nav-tabs .nav-link.active { color:#f79039; border-bottom-color:#f79039; }
        .nav-tabs { border-bottom:2px solid #e9ecef; }
        .badge-open   { background:#dbeafe; color:#1d4ed8; }
        .badge-draft  { background:#fef9c3; color:#92400e; }
        .badge-sub    { background:#dcfce7; color:#166534; }
        .badge-rej    { background:#fee2e2; color:#991b1b; }
        .badge-app    { background:#ede9fe; color:#5b21b6; }
        .badge-rev    { background:#ffedd5; color:#c2410c; }
        .update-badge { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; border-radius:6px; padding:3px 10px; font-size:.75rem; display:inline-flex; align-items:center; gap:5px; }
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
                    <i class="bi bi-graph-up-arrow me-2" style="color:#f79039"></i>Monitoring Sensus Ekonomi
                </div>
                <div class="topbar-sub">Progress Pencacahan SE2026 — BPS Kab. Toli-Toli</div>
            </div>
            <div class="topbar-right d-flex align-items-center gap-2">
                <form method="GET" class="d-flex align-items-center gap-2">
                    <select name="kec" class="form-select form-select-sm" style="min-width:190px;font-size:.82rem;" onchange="this.form.submit()">
                        <option value="all" <?= $kecFilter === 'all' ? 'selected' : '' ?>>Semua Kecamatan</option>
                        <?php foreach ($kecNama as $kode => $nama): ?>
                        <option value="<?= $kode ?>" <?= $kecFilter === $kode ? 'selected' : '' ?>>
                            <?= $kode ?> — <?= htmlspecialchars($nama) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <?php if (isSuperAdmin()): ?>
                <a href="data_sensus_ekonomi.php" class="btn btn-sm text-white" style="background:#f79039; white-space:nowrap;">
                    <i class="bi bi-database-fill-gear me-1"></i>Kelola Data
                </a>
                <?php endif; ?>
            </div>
        </header>

        <div class="page-body">

            <!-- ── Stat Cards ─────────────────────────────────── -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-sm-4 col-xl-2">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-folder2-open"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= number_format((int)$stats['total_open']) ?></div>
                                <div class="text-muted small">Open</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-4 col-xl-2">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-pencil-square"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= number_format((int)$stats['total_draft']) ?></div>
                                <div class="text-muted small">Draft</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-4 col-xl-2">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check2-all"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= number_format((int)$stats['total_submitted']) ?></div>
                                <div class="text-muted small">Submitted</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-4 col-xl-2">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-x-circle-fill"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= number_format((int)$stats['total_rejected']) ?></div>
                                <div class="text-muted small">Rejected</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-4 col-xl-2">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background:#ede9fe;color:#5b21b6"><i class="bi bi-patch-check-fill"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= number_format((int)$stats['total_approved']) ?></div>
                                <div class="text-muted small">Approved</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-4 col-xl-2">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background:#ffedd5;color:#c2410c"><i class="bi bi-arrow-counterclockwise"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= number_format((int)$stats['total_revoke']) ?></div>
                                <div class="text-muted small">Revoke</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-4 col-xl-2">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background:rgba(247,144,57,.12);color:#f79039"><i class="bi bi-person-badge-fill"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= (int)$stats['total_petugas'] ?></div>
                                <div class="text-muted small">Petugas</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-sm-4 col-xl-2">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background:rgba(99,102,241,.12);color:#6366f1"><i class="bi bi-geo-alt-fill"></i></div>
                            <div>
                                <div class="fs-2 fw-bold lh-1"><?= (int)$stats['total_sls'] ?></div>
                                <div class="text-muted small">SLS</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Progress Bar Global ───────────────────────── -->
            <div class="card stat-card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-semibold">
                        Progress Pencacahan
                        <?php if ($kecFilter !== 'all'): ?>
                            — <span style="color:#f79039"><?= htmlspecialchars($kecNama[$kecFilter]) ?></span>
                        <?php else: ?>
                            Keseluruhan
                        <?php endif; ?>
                    </span>
                    <span class="fw-bold fs-5" style="color:#f79039"><?= $globalPct ?>%</span>
                </div>
                <div class="progress" style="height:8px; border-radius:6px; background:#e9ecef;">
                    <div class="progress-bar progress-accent" style="width:<?= $globalPct ?>%; border-radius:6px;"></div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <small class="text-muted"><?= number_format($globalNumerator) ?> dari <?= number_format($globalDenominator) ?> dokumen sudah diproses (submit + reject + approve + revoke)</small>
                    <small class="text-muted"><?= number_format((int)$stats['total_open'] + (int)$stats['total_draft']) ?> dokumen tersisa</small>
                </div>
                <?php if ($lastUpdateStr): ?>
                <div class="mt-2 pt-2 border-top d-flex align-items-center gap-2">
                    <span class="update-badge">
                        <i class="bi bi-clock-history"></i>
                        Data terakhir diperbarui: <strong><?= $lastUpdateStr ?></strong>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <!-- ── Charts ─────────────────────────────────────── -->
            <div class="row g-4 mb-4">
                <!-- Doughnut -->
                <div class="col-lg-4">
                    <div class="card stat-card p-4 h-100">
                        <div class="section-head"><i class="bi bi-pie-chart-fill me-1"></i>Distribusi Status
                            <?php if ($kecFilter !== 'all'): ?>
                            — <span style="color:#f79039"><?= htmlspecialchars($kecNama[$kecFilter]) ?></span>
                            <?php endif; ?>
                        </div>
                        <div style="max-width:300px; margin:0 auto;">
                            <canvas id="doughnutChart"></canvas>
                        </div>
                        <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                            <span class="badge badge-open px-3 py-2">Open: <?= number_format((int)$stats['total_open']) ?></span>
                            <span class="badge badge-draft px-3 py-2">Draft: <?= number_format((int)$stats['total_draft']) ?></span>
                            <span class="badge badge-sub px-3 py-2">Submitted: <?= number_format((int)$stats['total_submitted']) ?></span>
                            <span class="badge badge-rej px-3 py-2">Rejected: <?= number_format((int)$stats['total_rejected']) ?></span>
                            <span class="badge badge-app px-3 py-2">Approved: <?= number_format((int)$stats['total_approved']) ?></span>
                            <span class="badge badge-rev px-3 py-2">Revoke: <?= number_format((int)$stats['total_revoke']) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Bar per petugas -->
                <div class="col-lg-8">
                    <div class="card stat-card p-4 h-100">
                        <div class="section-head"><i class="bi bi-bar-chart-fill me-1"></i>Progress per Petugas</div>
                        <div class="chart-scroll">
                            <canvas id="barChart" style="min-width:<?= $barWidth ?>px; height:260px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Tabs: Petugas | Kecamatan ──────────────────── -->
            <div class="card stat-card mb-0">
                <div class="card-header bg-white border-bottom-0 pt-3 px-4">
                    <ul class="nav nav-tabs border-0" id="mainTabs">
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabPetugas">
                                <i class="bi bi-person-lines-fill me-1"></i>Per Petugas
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabKecamatan">
                                <i class="bi bi-map-fill me-1"></i>Per Kecamatan
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabSLS">
                                <i class="bi bi-list-ul me-1"></i>Per SLS
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="tab-content">

                    <!-- Tab Petugas -->
                    <div class="tab-pane fade show active" id="tabPetugas">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead style="background:#f79039; color:#fff;">
                                    <tr>
                                        <th class="ps-4">#</th>
                                        <th>Nama Petugas</th>
                                        <th class="text-center">SLS</th>
                                        <th class="text-center">Open</th>
                                        <th class="text-center">Draft</th>
                                        <th class="text-center">Submitted<br><small style="font-weight:400;font-size:.72rem;">Pencacah</small></th>
                                        <th class="text-center">Submitted<br><small style="font-weight:400;font-size:.72rem;">Respondent</small></th>
                                        <th class="text-center">Rejected</th>
                                        <th class="text-center">Approved</th>
                                        <th class="text-center">Revoke</th>
                                        <th class="text-center">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($perPetugas)): ?>
                                    <tr><td colspan="11" class="text-center text-muted py-4">Belum ada data. <a href="data_sensus_ekonomi.php">Import data?</a></td></tr>
                                <?php else: ?>
                                <?php foreach ($perPetugas as $i => $p): ?>
                                    <?php
                                    $total = (int)$p['total_open'] + (int)$p['total_draft']
                                           + (int)$p['total_submitted'] + (int)$p['total_rejected']
                                           + (int)$p['total_approved'] + (int)$p['total_revoke'];
                                    $namaPetugas = $getNama($p['email']);
                                    ?>
                                    <tr>
                                        <td class="ps-4 text-muted small"><?= $i + 1 ?></td>
                                        <td>
                                            <div class="fw-semibold small"><?= htmlspecialchars($namaPetugas) ?></div>
                                            <div class="text-muted" style="font-size:.72rem;"><?= htmlspecialchars($p['email']) ?></div>
                                        </td>
                                        <td class="text-center"><span class="badge" style="background:#e0e7ff;color:#3730a3"><?= $p['total_sls'] ?></span></td>
                                        <td class="text-center"><span class="badge badge-open fw-semibold"><?= number_format((int)$p['total_open']) ?></span></td>
                                        <td class="text-center"><span class="badge badge-draft fw-semibold"><?= number_format((int)$p['total_draft']) ?></span></td>
                                        <td class="text-center"><span class="badge badge-sub fw-semibold"><?= number_format((int)$p['submitted_pencacah']) ?></span></td>
                                        <td class="text-center"><span class="badge badge-sub fw-semibold"><?= number_format((int)$p['submitted_resp']) ?></span></td>
                                        <td class="text-center"><span class="badge badge-rej fw-semibold"><?= number_format((int)$p['total_rejected']) ?></span></td>
                                        <td class="text-center"><span class="badge badge-app fw-semibold"><?= number_format((int)$p['total_approved']) ?></span></td>
                                        <td class="text-center"><span class="badge badge-rev fw-semibold"><?= number_format((int)$p['total_revoke']) ?></span></td>
                                        <td class="text-center fw-bold text-muted"><?= number_format($total) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab Kecamatan -->
                    <div class="tab-pane fade" id="tabKecamatan">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead style="background:#f79039; color:#fff;">
                                    <tr>
                                        <th class="ps-4">Kode</th>
                                        <th>Kecamatan</th>
                                        <th class="text-center">Petugas</th>
                                        <th class="text-center">SLS</th>
                                        <th class="text-center">Open</th>
                                        <th class="text-center">Draft</th>
                                        <th class="text-center">Submitted</th>
                                        <th class="text-center">Rejected</th>
                                        <th class="text-center">Approved</th>
                                        <th class="text-center">Progress</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($perKec)): ?>
                                    <tr><td colspan="10" class="text-center text-muted py-4">Belum ada data.</td></tr>
                                <?php else: ?>
                                <?php foreach ($perKec as $kec): ?>
                                    <?php
                                    $kDenominator = (int)$kec['total_open'] + (int)$kec['total_draft']
                                                  + (int)$kec['total_submitted'] + (int)$kec['total_rejected']
                                                  + (int)$kec['total_approved'] + (int)$kec['total_revoke'];
                                    $kNumerator   = (int)$kec['total_submitted'] + (int)$kec['total_rejected']
                                                  + (int)$kec['total_approved'] + (int)$kec['total_revoke'];
                                    $pct = $kDenominator > 0
                                        ? number_format($kNumerator / $kDenominator * 100, 2)
                                        : '0.00';
                                    $nama = $kecNama[$kec['kec_code']] ?? 'Kec. ' . $kec['kec_code'];
                                    ?>
                                    <tr>
                                        <td class="ps-4">
                                            <a href="?kec=<?= htmlspecialchars($kec['kec_code']) ?>" class="badge text-decoration-none" style="background:#fff3e0;color:#f79039;font-size:.78rem">
                                                <?= htmlspecialchars($kec['kec_code']) ?>
                                            </a>
                                        </td>
                                        <td class="fw-semibold small"><?= htmlspecialchars($nama) ?></td>
                                        <td class="text-center small"><?= $kec['total_petugas'] ?></td>
                                        <td class="text-center small"><?= $kec['total_sls'] ?></td>
                                        <td class="text-center"><span class="badge badge-open"><?= number_format((int)$kec['total_open']) ?></span></td>
                                        <td class="text-center"><span class="badge badge-draft"><?= number_format((int)$kec['total_draft']) ?></span></td>
                                        <td class="text-center"><span class="badge badge-sub"><?= number_format((int)$kec['total_submitted']) ?></span></td>
                                        <td class="text-center"><span class="badge badge-rej"><?= number_format((int)$kec['total_rejected']) ?></span></td>
                                        <td class="text-center"><span class="badge badge-app"><?= number_format((int)$kec['total_approved']) ?></span></td>
                                        <td style="min-width:120px;">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height:6px">
                                                    <div class="progress-bar progress-accent" style="width:<?= $pct ?>%; border-radius:6px;"></div>
                                                </div>
                                                <small class="text-muted" style="white-space:nowrap"><?= $pct ?>%</small>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab SLS -->
                    <div class="tab-pane fade" id="tabSLS">
                        <div class="px-4 pt-3 pb-2">
                            <div class="section-head mb-0">
                                Detail per SLS
                                <?php if ($kecFilter !== 'all'): ?>
                                — <span style="color:#f79039"><?= htmlspecialchars($kecNama[$kecFilter]) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="font-size:.82rem;">
                                <thead style="background:#f79039; color:#fff;">
                                    <tr>
                                        <th class="ps-4">#</th>
                                        <th>SLS Code</th>
                                        <th>Kecamatan</th>
                                        <th>Nama Petugas</th>
                                        <th class="text-center">Open</th>
                                        <th class="text-center">Draft</th>
                                        <th class="text-center">Sub. Pencacah</th>
                                        <th class="text-center">Sub. Resp.</th>
                                        <th class="text-center">Rejected</th>
                                        <th class="text-center">Approved</th>
                                        <th class="text-center">Revoke</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                $page    = max(1, (int)($_GET['page'] ?? 1));
                                $perPage = 100;
                                $slsTotal = (int)$conn->query("SELECT COUNT(*) c FROM sensus_ekonomi $kecWhere")->fetch_assoc()['c'];
                                $totalPages = max(1, ceil($slsTotal / $perPage));
                                $offset = ($page - 1) * $perPage;
                                $slsRows = $conn->query("SELECT id, email, sls_code,
                                    SUBSTRING(sls_code,5,3) AS kec_code,
                                    open_count, draft, submitted_by_pencacah, submitted_respondent, rejected, approved, `revoke`
                                FROM sensus_ekonomi $kecWhere
                                ORDER BY kec_code, sls_code, email
                                LIMIT $perPage OFFSET $offset")->fetch_all(MYSQLI_ASSOC);
                                ?>
                                <?php if (empty($slsRows)): ?>
                                    <tr><td colspan="10" class="text-center text-muted py-4">Belum ada data.</td></tr>
                                <?php else: ?>
                                <?php foreach ($slsRows as $i => $r): ?>
                                    <tr>
                                        <td class="ps-4 text-muted"><?= $offset + $i + 1 ?></td>
                                        <td class="fw-semibold" style="font-family:monospace"><?= htmlspecialchars($r['sls_code']) ?></td>
                                        <td>
                                            <?php $kn = $kecNama[$r['kec_code']] ?? $r['kec_code']; ?>
                                            <span class="badge" style="background:#fff3e0;color:#f79039"><?= $r['kec_code'] ?></span>
                                            <?= htmlspecialchars($kn) ?>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($getNama($r['email'])) ?></div>
                                            <div class="text-muted" style="font-size:.7rem;"><?= htmlspecialchars($r['email']) ?></div>
                                        </td>
                                        <td class="text-center"><span class="badge badge-open"><?= $r['open_count'] ?></span></td>
                                        <td class="text-center"><span class="badge badge-draft"><?= $r['draft'] ?></span></td>
                                        <td class="text-center"><span class="badge badge-sub"><?= $r['submitted_by_pencacah'] ?></span></td>
                                        <td class="text-center"><span class="badge badge-sub"><?= $r['submitted_respondent'] ?></span></td>
                                        <td class="text-center"><span class="badge badge-rej"><?= $r['rejected'] ?></span></td>
                                        <td class="text-center"><span class="badge bg-secondary bg-opacity-75"><?= $r['approved'] ?></span></td>
                                        <td class="text-center"><span class="badge badge-rev"><?= $r['revoke'] ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($totalPages > 1): ?>
                        <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top">
                            <small class="text-muted">Halaman <?= $page ?> dari <?= $totalPages ?> (<?= $slsTotal ?> record)</small>
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    <?php
                                    $qs = http_build_query(['kec' => $_GET['kec'] ?? '']);
                                    $start = max(1, $page - 2);
                                    $end   = min($totalPages, $page + 2);
                                    if ($page > 1): ?>
                                    <li class="page-item"><a class="page-link" href="?<?= $qs ?>&page=<?= $page-1 ?>#tabSLS">‹</a></li>
                                    <?php endif;
                                    for ($p = $start; $p <= $end; $p++): ?>
                                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?<?= $qs ?>&page=<?= $p ?>#tabSLS"><?= $p ?></a>
                                    </li>
                                    <?php endfor;
                                    if ($page < $totalPages): ?>
                                    <li class="page-item"><a class="page-link" href="?<?= $qs ?>&page=<?= $page+1 ?>#tabSLS">›</a></li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
                    </div>

                </div><!-- /tab-content -->
            </div>

        </div><!-- /page-body -->
    </div><!-- /main-content -->
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "'Roboto', system-ui, sans-serif";
Chart.defaults.font.size   = 12;

// ── Doughnut Chart ────────────────────────────────────
new Chart(document.getElementById('doughnutChart'), {
    type: 'doughnut',
    data: {
        labels: ['Open','Draft','Submitted','Rejected','Approved','Revoke'],
        datasets: [{
            data: <?= $doughnutData ?>,
            backgroundColor: ['#3b82f6','#f59e0b','#22c55e','#ef4444','#8b5cf6','#f97316'],
            borderWidth: 2, borderColor: '#fff',
        }]
    },
    options: {
        cutout: '65%',
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, padding: 10 } } },
        animation: { duration: 600 },
    }
});

// ── Bar Chart per Petugas ─────────────────────────────
const labels    = <?= $chartLabels ?>;
const barOpen   = <?= $chartOpen ?>;
const barDraft  = <?= $chartDraft ?>;
const barSub    = <?= $chartSubmitted ?>;
const barRej    = <?= $chartRejected ?>;
const barApp    = <?= $chartApproved ?>;
const barRev    = <?= $chartRevoke ?>;

new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [
            { label:'Open',      data:barOpen,  backgroundColor:'#93c5fd', stack:'s' },
            { label:'Draft',     data:barDraft, backgroundColor:'#fcd34d', stack:'s' },
            { label:'Submitted', data:barSub,   backgroundColor:'#4ade80', stack:'s' },
            { label:'Rejected',  data:barRej,   backgroundColor:'#f87171', stack:'s' },
            { label:'Approved',  data:barApp,   backgroundColor:'#c4b5fd', stack:'s' },
            { label:'Revoke',    data:barRev,   backgroundColor:'#fdba74', stack:'s' },
        ]
    },
    options: {
        responsive: false,
        maintainAspectRatio: false,
        scales: {
            x: { stacked:true, ticks:{ maxRotation:45, font:{size:10} } },
            y: { stacked:true, beginAtZero:true },
        },
        plugins: { legend:{ position:'top', labels:{ boxWidth:12 } } },
        animation: { duration:600 },
    }
});

// ── Activate tab from hash ────────────────────────────
const hash = window.location.hash;
if (hash === '#tabSLS') {
    new bootstrap.Tab(document.querySelector('[data-bs-target="#tabSLS"]')).show();
}
</script>
</body>
</html>
