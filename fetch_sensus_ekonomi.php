<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Makassar');
ob_start();

register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && ($err['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_RECOVERABLE_ERROR))) {
        ob_end_clean();
        echo json_encode(['error' => $err['message'] . ' (' . basename($err['file']) . ':' . $err['line'] . ')']);
    } else {
        ob_end_flush();
    }
});

$conn = @mysqli_connect('43.128.105.129', 'root', 'Djt04k91On5HRE8ZyNVxha7JUF6m3bW2', 'db_semanis', 30444);
if (!$conn) {
    ob_end_clean();
    echo json_encode(['error' => 'DB: ' . mysqli_connect_error()]);
    exit;
}
$conn->set_charset('utf8mb4');

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

$getNama = fn($email) => $emailNama[strtolower($email)] ?? $email;

$kdkec = trim($_GET['kdkec'] ?? 'all');
if ($kdkec !== 'all' && !isset($kecNama[$kdkec])) $kdkec = 'all';

$kecCond  = ($kdkec !== 'all') ? "SUBSTRING(sls_code,5,3)='" . $conn->real_escape_string($kdkec) . "'" : '';
$kecWhere = $kecCond ? "WHERE $kecCond" : '';

// Overall stats
$s = $conn->query("SELECT
    COALESCE(SUM(open_count),0)                                   AS total_open,
    COALESCE(SUM(draft),0)                                        AS total_draft,
    COALESCE(SUM(submitted_by_pencacah),0)                        AS total_submitted,
    COALESCE(SUM(rejected),0)                                     AS total_rejected,
    COALESCE(SUM(approved),0)                                     AS total_approved,
    COALESCE(SUM(`revoke`),0)                                     AS total_revoke,
    COUNT(*)                                                       AS total_sls,
    COUNT(DISTINCT email)                                         AS total_petugas
FROM sensus_ekonomi $kecWhere")->fetch_assoc();

// Per kecamatan (always all for the bar chart overview)
$kecRows = $conn->query("SELECT
    SUBSTRING(sls_code,5,3) AS kec_code,
    COALESCE(SUM(open_count),0)                                   AS open,
    COALESCE(SUM(draft),0)                                        AS draft,
    COALESCE(SUM(submitted_by_pencacah),0)                        AS submitted,
    COALESCE(SUM(rejected),0)                                     AS rejected,
    COALESCE(SUM(approved),0)                                     AS approved,
    COALESCE(SUM(`revoke`),0)                                     AS `revoke`,
    COUNT(*)                                                      AS sls
FROM sensus_ekonomi
GROUP BY SUBSTRING(sls_code,5,3)
ORDER BY kec_code")->fetch_all(MYSQLI_ASSOC);

$tabel_kec = [];
foreach ($kecRows as $k) {
    $tot  = (int)$k['open'] + (int)$k['draft'] + (int)$k['submitted']
          + (int)$k['rejected'] + (int)$k['approved'] + (int)$k['revoke'];
    $num  = (int)$k['submitted'] + (int)$k['rejected'] + (int)$k['approved'] + (int)$k['revoke'];
    $prog = $tot > 0 ? round($num / $tot * 100, 2) : 0;
    $tabel_kec[] = [
        'kec_code'  => $k['kec_code'],
        'nama'      => $kecNama[$k['kec_code']] ?? 'Kec. ' . $k['kec_code'],
        'open'      => (int)$k['open'],
        'draft'     => (int)$k['draft'],
        'submitted' => (int)$k['submitted'],
        'rejected'  => (int)$k['rejected'],
        'approved'  => (int)$k['approved'],
        'revoke'    => (int)$k['revoke'],
        'total'     => $tot,
        'sls'       => (int)$k['sls'],
        'prog'      => $prog,
    ];
}

// Per petugas (filtered)
$petRows = $conn->query("SELECT
    email,
    GROUP_CONCAT(DISTINCT SUBSTRING(sls_code,5,3) ORDER BY SUBSTRING(sls_code,5,3)) AS kec_codes,
    COALESCE(SUM(open_count),0)                                   AS open,
    COALESCE(SUM(draft),0)                                        AS draft,
    COALESCE(SUM(submitted_by_pencacah),0)                        AS submitted,
    COALESCE(SUM(rejected),0)                                     AS rejected,
    COALESCE(SUM(approved),0)                                     AS approved,
    COALESCE(SUM(`revoke`),0)                                     AS `revoke`,
    COUNT(*)                                                      AS sls
FROM sensus_ekonomi $kecWhere
GROUP BY email
ORDER BY submitted DESC, open DESC")->fetch_all(MYSQLI_ASSOC);

$tabel_petugas = [];
foreach ($petRows as $p) {
    $tot = (int)$p['open'] + (int)$p['draft'] + (int)$p['submitted']
         + (int)$p['rejected'] + (int)$p['approved'] + (int)$p['revoke'];
    $kecNames = array_map(
        fn($k) => $kecNama[trim($k)] ?? trim($k),
        explode(',', $p['kec_codes'] ?? '')
    );
    $tabel_petugas[] = [
        'nama'      => $getNama($p['email']),
        'kecamatan' => implode(', ', $kecNames),
        'open'      => (int)$p['open'],
        'draft'     => (int)$p['draft'],
        'submitted' => (int)$p['submitted'],
        'rejected'  => (int)$p['rejected'],
        'approved'  => (int)$p['approved'],
        'revoke'    => (int)$p['revoke'],
        'total'     => $tot,
        'sls'       => (int)$p['sls'],
    ];
}

// Last update — baca dari tabel meta yang dicatat saat import CSV
$conn->query("CREATE TABLE IF NOT EXISTS sensus_meta (k VARCHAR(50) PRIMARY KEY, v TEXT) ENGINE=InnoDB");
$luRow = $conn->query("SELECT v FROM sensus_meta WHERE k='last_import' LIMIT 1");
$lastUpdate = null;
if ($luRow && ($luData = $luRow->fetch_assoc())) {
    $dt = new DateTime($luData['v']);
    $bulan = ['','Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];
    $lastUpdate = $dt->format('d') . ' ' . $bulan[(int)$dt->format('n')] . ' ' . $dt->format('Y H:i') . ' WITA';
}

echo json_encode([
    'stats' => [
        'open'      => (int)$s['total_open'],
        'draft'     => (int)$s['total_draft'],
        'submitted' => (int)$s['total_submitted'],
        'rejected'  => (int)$s['total_rejected'],
        'approved'  => (int)$s['total_approved'],
        'revoke'    => (int)$s['total_revoke'],
        'sls'       => (int)$s['total_sls'],
        'petugas'   => (int)$s['total_petugas'],
    ],
    'tabel_kec'     => $tabel_kec,
    'tabel_petugas' => $tabel_petugas,
    'last_update'   => $lastUpdate,
], JSON_UNESCAPED_UNICODE);
