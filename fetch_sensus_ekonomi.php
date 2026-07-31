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

mysqli_report(MYSQLI_REPORT_OFF);
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
$chk2 = $conn->query("SHOW COLUMNS FROM sensus_ekonomi LIKE 'edited_by_pengawas'");
if ($chk2 && $chk2->num_rows === 0) {
    $conn->query("ALTER TABLE sensus_ekonomi ADD COLUMN edited_by_pengawas INT DEFAULT 0 AFTER `revoke`");
}
$chk3 = $conn->query("SHOW COLUMNS FROM sensus_ekonomi LIKE 'edited_by_admin_kabupaten'");
if ($chk3 && $chk3->num_rows === 0) {
    $conn->query("ALTER TABLE sensus_ekonomi ADD COLUMN edited_by_admin_kabupaten INT DEFAULT 0 AFTER edited_by_pengawas");
}
$chkIdx = $conn->query("SHOW INDEX FROM sensus_ekonomi WHERE Key_name = 'uq_email_sls'");
if ($chkIdx && $chkIdx->num_rows === 0) {
    $conn->query("DELETE s1 FROM sensus_ekonomi s1
        JOIN sensus_ekonomi s2
        ON s1.email = s2.email AND s1.sls_code = s2.sls_code AND s1.id < s2.id");
    $conn->query("ALTER TABLE sensus_ekonomi ADD UNIQUE KEY uq_email_sls (email, sls_code)");
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
    'stuntingsalumpaga@gmail.com'     => 'RAMNI',
];

$getNama = fn($email) => $emailNama[strtolower($email)] ?? $email;

$kdkec = trim($_GET['kdkec'] ?? 'all');
if ($kdkec !== 'all' && !isset($kecNama[$kdkec])) $kdkec = 'all';

$kecCond  = ($kdkec !== 'all') ? "SUBSTRING(sls_code,5,3)='" . $conn->real_escape_string($kdkec) . "'" : '';
$kecWhere = $kecCond ? "WHERE $kecCond" : '';

// Overall stats
$s = $conn->query("SELECT
    COALESCE(SUM(open_count),0)                           AS total_open,
    COALESCE(SUM(draft),0)                                AS total_draft,
    COALESCE(SUM(submitted_by_pencacah),0)                AS total_submitted,
    COALESCE(SUM(rejected),0)                             AS total_rejected,
    COALESCE(SUM(approved),0)                             AS total_approved,
    COALESCE(SUM(`revoke`),0)                             AS total_revoke,
    COALESCE(SUM(edited_by_pengawas),0)                   AS total_edited_pengawas,
    COALESCE(SUM(edited_by_admin_kabupaten),0)            AS total_edited_admin,
    COUNT(*)                                              AS total_sls,
    COUNT(DISTINCT email)                                 AS total_petugas
FROM sensus_ekonomi $kecWhere")->fetch_assoc();

// Per kecamatan (always all for the bar chart overview)
$kecRows = $conn->query("SELECT
    SUBSTRING(sls_code,5,3) AS kec_code,
    COALESCE(SUM(open_count),0)                   AS open,
    COALESCE(SUM(draft),0)                         AS draft,
    COALESCE(SUM(submitted_by_pencacah),0)         AS submitted,
    COALESCE(SUM(rejected),0)                      AS rejected,
    COALESCE(SUM(approved),0)                      AS approved,
    COALESCE(SUM(`revoke`),0)                      AS `revoke`,
    COALESCE(SUM(edited_by_pengawas),0)            AS edited_pengawas,
    COALESCE(SUM(edited_by_admin_kabupaten),0)     AS edited_admin,
    COUNT(*)                                       AS sls
FROM sensus_ekonomi
GROUP BY SUBSTRING(sls_code,5,3)
ORDER BY kec_code")->fetch_all(MYSQLI_ASSOC);

$tabel_kec = [];
foreach ($kecRows as $k) {
    $kNum = (int)$k['submitted'] + (int)$k['approved']
          + (int)$k['edited_pengawas'] + (int)$k['edited_admin']
          + (int)$k['rejected'] + (int)$k['revoke'];
    $kDen = $kNum + (int)$k['open'] + (int)$k['draft'];
    $prog = $kDen > 0 ? round($kNum / $kDen * 100, 2) : 0;
    $tot  = $kDen;
    $tabel_kec[] = [
        'kec_code'        => $k['kec_code'],
        'nama'            => $kecNama[$k['kec_code']] ?? 'Kec. ' . $k['kec_code'],
        'open'            => (int)$k['open'],
        'draft'           => (int)$k['draft'],
        'submitted'       => (int)$k['submitted'],
        'rejected'        => (int)$k['rejected'],
        'approved'        => (int)$k['approved'],
        'edited_pengawas' => (int)$k['edited_pengawas'],
        'edited_admin'    => (int)$k['edited_admin'],
        'revoke'          => (int)$k['revoke'],
        'total'           => $tot,
        'sls'             => (int)$k['sls'],
        'prog'            => $prog,
    ];
}

// Per petugas (filtered)
$petRows = $conn->query("SELECT
    email,
    GROUP_CONCAT(DISTINCT SUBSTRING(sls_code,5,3) ORDER BY SUBSTRING(sls_code,5,3)) AS kec_codes,
    COALESCE(SUM(open_count),0)                   AS open,
    COALESCE(SUM(draft),0)                         AS draft,
    COALESCE(SUM(submitted_by_pencacah),0)         AS submitted,
    COALESCE(SUM(rejected),0)                      AS rejected,
    COALESCE(SUM(approved),0)                      AS approved,
    COALESCE(SUM(`revoke`),0)                      AS `revoke`,
    COALESCE(SUM(edited_by_pengawas),0)            AS edited_pengawas,
    COALESCE(SUM(edited_by_admin_kabupaten),0)     AS edited_admin,
    COUNT(*)                                       AS sls
FROM sensus_ekonomi $kecWhere
GROUP BY email
ORDER BY submitted DESC, open DESC")->fetch_all(MYSQLI_ASSOC);

$tabel_petugas = [];
foreach ($petRows as $p) {
    $pNum = (int)$p['submitted'] + (int)$p['approved']
          + (int)$p['edited_pengawas'] + (int)$p['edited_admin']
          + (int)$p['rejected'] + (int)$p['revoke'];
    $pDen = $pNum + (int)$p['open'] + (int)$p['draft'];
    $prog = $pDen > 0 ? round($pNum / $pDen * 100, 2) : 0;
    $tot  = $pDen;
    $kecNames = array_map(
        fn($k) => $kecNama[trim($k)] ?? trim($k),
        explode(',', $p['kec_codes'] ?? '')
    );
    $tabel_petugas[] = [
        'nama'            => $getNama($p['email']),
        'kecamatan'       => implode(', ', $kecNames),
        'open'            => (int)$p['open'],
        'draft'           => (int)$p['draft'],
        'submitted'       => (int)$p['submitted'],
        'rejected'        => (int)$p['rejected'],
        'approved'        => (int)$p['approved'],
        'edited_pengawas' => (int)$p['edited_pengawas'],
        'edited_admin'    => (int)$p['edited_admin'],
        'revoke'          => (int)$p['revoke'],
        'total'           => $tot,
        'sls'             => (int)$p['sls'],
        'prog'            => $prog,
    ];
}

// Buat tabel sensus_pml jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS sensus_pml (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    nama VARCHAR(200) NOT NULL DEFAULT '',
    kec_code VARCHAR(20) NOT NULL DEFAULT '',
    open_count INT DEFAULT 0,
    draft INT DEFAULT 0,
    submitted INT DEFAULT 0,
    approved INT DEFAULT 0,
    rejected INT DEFAULT 0,
    `revoke` INT DEFAULT 0,
    edited_by_pengawas INT DEFAULT 0,
    edited_by_admin INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pml_email_kec (email, kec_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$conn->query("ALTER TABLE sensus_pml MODIFY COLUMN kec_code VARCHAR(20) NOT NULL DEFAULT ''");
foreach (['open_count','draft','submitted','edited_by_pengawas','edited_by_admin'] as $_col) {
    $chk = $conn->query("SHOW COLUMNS FROM sensus_pml LIKE '$_col'");
    if ($chk && $chk->num_rows === 0)
        $conn->query("ALTER TABLE sensus_pml ADD COLUMN `$_col` INT DEFAULT 0");
}

$kecWherePML = ($kdkec !== 'all') ? "WHERE kec_code='" . $conn->real_escape_string($kdkec) . "'" : '';

$pmlRows = $conn->query("SELECT
    email, nama,
    GROUP_CONCAT(DISTINCT kec_code ORDER BY kec_code) AS kec_codes,
    COALESCE(SUM(open_count),0)          AS open,
    COALESCE(SUM(draft),0)               AS draft,
    COALESCE(SUM(submitted),0)           AS submitted,
    COALESCE(SUM(approved),0)            AS approved,
    COALESCE(SUM(rejected),0)            AS rejected,
    COALESCE(SUM(`revoke`),0)            AS `revoke`,
    COALESCE(SUM(edited_by_pengawas),0)  AS edited_pengawas,
    COALESCE(SUM(edited_by_admin),0)     AS edited_admin
FROM sensus_pml $kecWherePML
GROUP BY email, nama
ORDER BY submitted DESC, open DESC")->fetch_all(MYSQLI_ASSOC);

$kecNamaPML = [
    '010' => 'Dampal Selatan', '020' => 'Dampal Utara',
    '030' => 'Dondo',          '031' => 'Ogodeide',
    '032' => 'Basidondo',      '040' => 'Baolan',
    '041' => 'Lampasio',       '050' => 'Galang',
    '060' => 'Tolitoli Utara', '061' => 'Dako Pemean',
];

$emailKec = [
    'wirtakwir8@gmail.com' => '060', 'nurazizalakoro@gmail.com' => '060',
    'sugengarrauf@gmail.com' => '060', 'lyliburhan99@gmail.com' => '060',
    'rahmiagussalim43@gmail.com' => '060', 'mukranumran@gmail.com' => '060',
    'azizanihru@gmail.com' => '060', 'ramnitolis2023@gmail.com' => '060',
    'fitri.ani0@yahoo.com' => '060', 'ahmadsumitro96@gmail.com' => '060',
    'sriwahyunimakkuraga@gmail.com' => '061', 'sahratulaini99@gmail.com' => '061',
    'hashari.h.hh@gmail.com' => '061', 'armina160298@gmail.com' => '061',
    'ulfiani.ani992@gmail.com' => '061', 'dewisosiartika@gmail.com' => '061',
    'syahdigalumpang@gmail.com' => '061', 'afriantomuhsen756@gmail.com' => '020',
    'albarbar24@gmail.com' => '020', 'halfinafhyna@gmail.com' => '010',
    '02lovelymore@gmail.com' => '020', 'farida181198@gmail.com' => '020',
    'karnitanita808@gmail.com' => '020', 'meyandris29@gmail.com' => '020',
    'muliantii1996@gmail.com' => '020', 'misnajo1@gmail.com' => '020',
    'meghanaharudin763@gmail.com' => '020', 'wahyudiforester6@gmail.com' => '020',
    'iffatpratama321@gmail.com' => '040', 'ffara8629@gmail.com' => '040',
    'faisalchaldo@gmail.com' => '040', 'nurulayustina198@gmail.com' => '040',
    'trimuliadi00@gmail.com' => '040', 'latumpu77@gmail.com' => '040',
    'mutmainna140201@gmail.com' => '040', 'nurdianadina1220@gmail.com' => '040',
    'saniyyah0410@gmail.com' => '040', 'ayusyawitri22@gmail.com' => '040',
    'usuci8993@gmail.com' => '040', 'nurhajra08092019@gmail.com' => '040',
    'indahade247@gmail.com' => '040', 'ahmaadtlz17@gmail.com' => '040',
    'fitriyantimarhalil@gmail.com' => '040', 'umijarnawi@gmail.com' => '040',
    'wulandarisb981@gmail.com' => '040', 'nanadjapara10@gmail.com' => '040',
    'raramutiara353@gmail.com' => '040',
    'fitriyawatitadore@gmail.com' => '040', 'astuti7061992@gmail.com' => '040',
    'deliyhana2022@gmail.com' => '040', 'mohfajrin219@gmail.com' => '040',
    'maulana022007@gmail.com' => '040', 'rosnirauf@gmail.com' => '040',
    'wahyuniarfan1989@gmail.com' => '040', 'rizkika357@gmail.com' => '040',
    'septiyadimerdekap@gmail.com' => '040', 'sriwahyunikmajah@gmail.com' => '040',
    'zhaafirah030501@gmail.com' => '040', 'nuraida25041981@gmail.com' => '040',
    'ramsaanca177@gmail.com' => '032', 'fajarirawan0501@gmail.com' => '032',
    'yusnihidayah21@gmail.com' => '032', 'tinasuhar65@gmail.com' => '032',
    'irnawati170792@gmail.com' => '061', 'yuyundiahpratiwi2397@gmail.com' => '041',
    'niluhulan@gmail.com' => '041', 'dessy261297@gmail.com' => '040',
    'ikkenvs@gmail.com' => '041', 'nurinsyan14@gmail.com' => '041',
    'animariany16@gmail.com' => '050', 'moh.reza992@gmail.com' => '050',
    'yunusmuh580@gmail.com' => '050', 'anif73599@gmail.com' => '050',
    'hasnirizal06@gmail.com' => '050', 'tutiwinarni628@gmail.com' => '050',
    'tinatangkuman102@gmail.com' => '050', 'lelaalfiana@gmail.com' => '050',
    'iqbalm2410@gmail.com' => '050', 'moh.khairaat@gmail.com' => '050',
    'suhradinad@gmail.com' => '050', 'liaa301102@gmail.com' => '050',
    'indrayaniburhanudin@gmail.com' => '050', 'smia89864@gmail.com' => '050',
    'srihardiyantii26@gmail.com' => '050', 'aliflibiya254@gmail.com' => '040',
    '082293jya@gmail.com' => '030', 'budimann225@gmail.com' => '030',
    'deviarni01@gmail.com' => '030', 'darachandra2@gmail.com' => '030',
    'annisarahma16102002@gmail.com' => '030', 'melinndaa26@gmail.com' => '030',
    'ammargonggol@gmail.com' => '030', 'riswanaswaludin4@gmail.com' => '030',
    'novianivhy34@gmail.com' => '030', 'sittihajarsalam63@gmail.com' => '030',
    'susantitolis2018@gmail.com' => '030', 'tritakdir753@gmail.com' => '030',
    'puspitasaritolis2020@gmail.com' => '030', 'asdardahyar@gmail.com' => '031',
    'nvtasariy@gmail.com' => '031', 'nia.060803@gmail.com' => '031',
    '2000padlia@gmail.com' => '031', 'rhiahijria994@gmail.com' => '031',
    'fitryani0102@gmail.com' => '031', 'ibrahimanthonie0707@gmail.com' => '031',
    'fatihfatir97@gmail.com' => '031', 'zeynzacky2023@gmail.com' => '031',
    'nasriajamrin2002@gmail.com' => '031', 'rahmatialukman27@gmail.com' => '031',
    'noviawulandari129@gmail.com' => '031', 'hasrianihamzah579@gmail.com' => '010',
    'misnasupriadi935@gmail.com' => '010', 'zulkifliusman1997@gmail.com' => '010',
    'sasmita08082004@gmail.com' => '010', 'nasirhartati77@gmail.com' => '010',
    'magfira066@gmail.com' => '010', 'ikamulyady@gmail.com' => '010',
    'abbdulsamad61@gmail.com' => '010', 'reniamalia978@gmail.com' => '010',
    'irayuliastari1@gmail.com' => '010', 'sardianaj@gmail.com' => '010',
    'damsel.bangkir43@gmail.com' => '010', 'siskaikha310@gmail.com' => '010',
    'riniarditasari18@gmail.com' => '010', 'rahmatiabahri@gmail.com' => '010',
    'kadirmankadir3@gmail.com' => '010', 'srisutanti4767@gmail.com' => '061',
    'qolbirahmaaulia@gmail.com' => '061', 'hasrianisri061@gmail.com' => '010',
    'andimariam249@gmail.com' => '060', 'andanghamdan51@gmail.com' => '020',
    'silfaniagus27@gmail.com' => '020', 'esiastarita394@gmail.com' => '020',
    'astarita832@gmail.com' => '020', 'winisudarmin23@gmail.com' => '020',
    'mokhoramli@gmail.com' => '030', 'miftahjannah1924@gmail.com' => '030',
    'dhevyaries07@gmail.com' => '030', 'miftachulannah@gmail.com' => '030',
    'tasyi679@gmail.com' => '030', 'lukmanpramuka13@gmail.com' => '030',
    'syafiahfiah232@gmail.com' => '030', 'trisyetrisnawati01@gmail.com' => '030',
    'wana061101@gmail.com' => '030', 'yunianang04@gmail.com' => '030',
    'pianoviani2911@gmail.com' => '030', 'bluebubblegum201@gmail.com' => '031',
    'hallowdaya@gmail.com' => '031', 'abjadalam9@gmail.com' => '031',
    'desiraihana8896@gmail.com' => '041', 'ramlicovid@gmail.com' => '031',
    'tinaahada@gmail.com' => '040', 'nrfaisah24@gmail.com' => '041',
    'samrins365@gmail.com' => '032', 'ekarezkiana11@gmail.com' => '032',
    'lisstiawati10@gmail.com' => '050', 'ikkireskiikkireski199@gmail.com' => '010',
    'irhamdhanial23@gmail.com' => '032', 'abdulbasar181010129@gmail.com' => '060',
    'nurinurhasanah46@gmail.com' => '040', 'sartika.awaludin@gmail.com' => '050',
    'moh.haris2025@gmail.com' => '031', 'rizalreynaldy.rr@gmail.com' => '040',
    'sindy.tolis22@gmail.com' => '050', 'mirzabahtiar87@gmail.com' => '040',
    'samitmidong@gmail.com' => '032', 'hasmanman836@gmail.com' => '050',
    'fadeld76@gmail.com' => '040', 'tolutjayamandiri@gmail.com' => '060',
    'marwarusno2000@gmail.com' => '060', 'sayidatunnisah@gmail.com' => '040',
    'eetarf03@gmail.com' => '050', 'tahwiltahir@gmail.com' => '010',
    'andisingkarahmadana@gmail.com' => '032', 'yulianalatape@gmail.com' => '050',
    'delilarf3009@gmail.com' => '050', 'zahraanggraini9722@gmail.com' => '040',
    'artalitagafur@gmail.com' => '031', 'setiawanrahmat50509@gmail.com' => '010',
    'yunitatolis5@gmail.com' => '040', 'pong290597@gmail.com' => '060',
    'ikhsanwiratama2025@gmail.com' => '010', 'mohikhwan826@gmail.com' => '041',
    'pratiwihalil101@gmail.com' => '040', 'ihsan.dumbo11@gmail.com' => '050',
    'jalangkoteibuudin26@gmail.com' => '041', 'aisyahsah456@gmail.com' => '050',
    'kurniatirustam21@gmail.com' => '050', 'hafidzahnurul61@gmail.com' => '040',
    'ma147696@gmail.com' => '050', 'ikaingguti@gmail.com' => '040',
    'mohhamkadahlan271299@gmail.com' => '032', 'efry0222@gmail.com' => '060',
    'fadlina.linaa2003@gmail.com' => '050', 'nahdatulrahman@gmail.com' => '050',
    'erwinpradistyaa@gmail.com' => '032', 'arumijennaira1341@gmail.com' => '041',
    'miftahuljanna.mjii@gmail.com' => '050', 'dindaaprilya222@gmail.com' => '050',
    'ernihasbi56@gmail.com' => '040', 'viivikusuma@gmail.com' => '040',
    'fitriwahyuningsih116@gmail.com' => '040', 'muhainiyusuf@gmail.com' => '050',
    'ammankmuhsalman@gmail.com' => '040', 'intanprdtha24@gmail.com' => '032',
    'fajaraswah53@gmail.com' => '041', 'ririnmarliana1430@gmail.com' => '040',
    'adfadilah06@gmail.com' => '040', 'safiraramadani1999@gmail.com' => '040',
    'alwizihab285@gmail.com' => '050', 'nandapade@icloud.com' => '050',
    'rinipntoh@gmail.com' => '060', 'vhiasilvia2020@gmail.com' => '060',
    'shajaredwin@gmail.com' => '060', 'tampubolonsahala49@gmail.com' => '040',
    'niskayhabu@gmail.com' => '050', 'wahyuliadjmallo@gmail.com' => '040',
    'mawar191291@gmail.com' => '050', 'iketutpujaastawadiputra@gmail.com' => '041',
    'lisaxnx20@gmail.com' => '040', 'andinipontoh1@gmail.com' => '060',
    'nurlindahramadhanyy06@gmail.com' => '040', 'sitierlinaarahmanerlina@gmail.com' => '060',
    'gamainumaki@gmail.com' => '040', 'jusmahwati08@gmail.com' => '040',
    'indarwandi937@gmail.com' => '010', 'musliaditolis@gmail.com' => '041',
    'agustinaxx02@gmail.com' => '010', 'nurhasidahs125@gmail.com' => '041',
    'sritutialawia@gmail.com' => '060', 'nurnawiraazzhara@gmail.com' => '010',
    'idjafar095@gmail.com' => '060', 'djumaopall@gmail.com' => '040',
    'nafilatulyulmaz@gmail.com' => '060', 'menni010429@gmail.com' => '050',
    'sridevilula991115@gmail.com' => '041', 'mohtaufanalfareza@gmail.com' => '040',
    'stuntingsalumpaga@gmail.com' => '060',
];

$tabel_pml = [];
foreach ($pmlRows as $r) {
    $pNum = (int)$r['approved'] + (int)$r['edited_pengawas'] + (int)$r['edited_admin'];
    $pDen = (int)$r['open'] + (int)$r['draft'] + (int)$r['submitted'] + (int)$r['rejected']
          + (int)$r['approved'] + (int)$r['edited_pengawas'] + (int)$r['edited_admin']
          + (int)$r['revoke'];
    $prog = $pDen > 0 ? round($pNum / $pDen * 100, 2) : 0;
    $resolvedKec = $emailKec[strtolower($r['email'])] ?? null;
    $kecCodes    = $resolvedKec ? [$resolvedKec] : array_map('trim', explode(',', $r['kec_codes'] ?? ''));
    $kecNames    = array_filter(array_map(fn($k) => $kecNamaPML[$k] ?? $k, $kecCodes));
    $tabel_pml[] = [
        'nama'            => $emailNama[strtolower($r['email'])] ?? ($r['nama'] ?: $r['email']),
        'kecamatan'       => implode(', ', $kecNames),
        'open'            => (int)$r['open'],
        'draft'           => (int)$r['draft'],
        'submitted'       => (int)$r['submitted'],
        'approved'        => (int)$r['approved'],
        'rejected'        => (int)$r['rejected'],
        'revoke'          => (int)$r['revoke'],
        'edited_pengawas' => (int)$r['edited_pengawas'],
        'edited_admin'    => (int)$r['edited_admin'],
        'total'           => $pDen,
        'prog'            => $prog,
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
        'open'            => (int)$s['total_open'],
        'draft'           => (int)$s['total_draft'],
        'submitted'       => (int)$s['total_submitted'],
        'rejected'        => (int)$s['total_rejected'],
        'approved'        => (int)$s['total_approved'],
        'edited_pengawas' => (int)$s['total_edited_pengawas'],
        'edited_admin'    => (int)$s['total_edited_admin'],
        'revoke'          => (int)$s['total_revoke'],
        'sls'             => (int)$s['total_sls'],
        'petugas'         => (int)$s['total_petugas'],
    ],
    'tabel_kec'     => $tabel_kec,
    'tabel_petugas' => $tabel_petugas,
    'tabel_pml'     => $tabel_pml,
    'last_update'   => $lastUpdate,
], JSON_UNESCAPED_UNICODE);
