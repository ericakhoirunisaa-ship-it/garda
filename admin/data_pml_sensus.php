<?php
require_once 'config.php';
requireLogin();
requireSuperAdmin();

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

$conn->query("CREATE TABLE IF NOT EXISTS sensus_sls_pengawas (
    sls_code VARCHAR(20) NOT NULL,
    pml_email VARCHAR(150) NOT NULL DEFAULT '',
    pml_nama  VARCHAR(200) NOT NULL DEFAULT '',
    PRIMARY KEY (sls_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$kecNama = [
    '010' => 'Dampal Selatan', '020' => 'Dampal Utara',
    '030' => 'Dondo',          '031' => 'Ogodeide',
    '032' => 'Basidondo',      '040' => 'Baolan',
    '041' => 'Lampasio',       '050' => 'Galang',
    '060' => 'Tolitoli Utara', '061' => 'Dako Pemean',
];

$emailNama = [
    'wirtakwir8@gmail.com' => 'Takwir',
    'nurazizalakoro@gmail.com' => 'Nuraziza',
    'sugengarrauf@gmail.com' => 'Sugeng. A',
    'lyliburhan99@gmail.com' => 'Eriyanti S',
    'rahmiagussalim43@gmail.com' => 'Rahmi Agussalim',
    'mukranumran@gmail.com' => 'Mukran',
    'azizanihru@gmail.com' => 'Aziza',
    'ramnitolis2023@gmail.com' => 'RAMNI',
    'fitri.ani0@yahoo.com' => 'Fitriani',
    'ahmadsumitro96@gmail.com' => 'Ahmad',
    'sriwahyunimakkuraga@gmail.com' => 'Sri wahyuni',
    'sahratulaini99@gmail.com' => 'SAHRATUL AINI',
    'hashari.h.hh@gmail.com' => 'Hashari',
    'armina160298@gmail.com' => 'Armina',
    'ulfiani.ani992@gmail.com' => 'ULPIANI RUSNO',
    'dewisosiartika@gmail.com' => 'DEWI SOSIARTIKA',
    'syahdigalumpang@gmail.com' => 'SYAHDI',
    'afriantomuhsen756@gmail.com' => 'Afrianto',
    'albarbar24@gmail.com' => 'ALBAR',
    'halfinafhyna@gmail.com' => 'HALFINA',
    '02lovelymore@gmail.com' => 'FARADHIBA',
    'farida181198@gmail.com' => 'Farida',
    'karnitanita808@gmail.com' => 'KARNITA',
    'meyandris29@gmail.com' => 'MEYSAHDI. A',
    'muliantii1996@gmail.com' => 'Mulianti Maulana',
    'misnajo1@gmail.com' => 'Misna',
    'meghanaharudin763@gmail.com' => 'Sulastri',
    'wahyudiforester6@gmail.com' => 'WAHYUDI MOH HARIS',
    'iffatpratama321@gmail.com' => 'Moh. Iffat Widad Pratama',
    'ffara8629@gmail.com' => 'Farah',
    'faisalchaldo@gmail.com' => 'Moh faisal',
    'nurulayustina198@gmail.com' => 'Nurul Ayustina',
    'trimuliadi00@gmail.com' => 'TRI MULIADI',
    'latumpu77@gmail.com' => 'yusuf',
    'mutmainna140201@gmail.com' => 'Mutmainna',
    'nurdianadina1220@gmail.com' => 'Nurdiana Zaharman',
    'saniyyah0410@gmail.com' => 'SANIYYAH NUR PURNAMA S.Sos',
    'ayusyawitri22@gmail.com' => 'SRI DEVI AYU SYAWITRI',
    'usuci8993@gmail.com' => 'Suci Safitry',
    'nurhajra08092019@gmail.com' => 'NUR HAJRA SYAFRUDDIN',
    'indahade247@gmail.com' => 'ADE INDAH PUSPITA SARI',
    'ahmaadtlz17@gmail.com' => 'AHMAD SAID',
    'fitriyantimarhalil@gmail.com' => 'Fitriyanti',
    'umijarnawi@gmail.com' => 'JUMIATI',
    'wulandarisb981@gmail.com' => 'Wulandari S. Butudoka',
    'nanadjapara10@gmail.com' => 'Nurhasanah',
    'raramutiara353@gmail.com' => 'ANDI MUTIARA ISTIQHAMARANI',
    'fitriyawatitadore@gmail.com' => 'FITRIYAWATI NH HANS TADORE',
    'astuti7061992@gmail.com' => 'Tri astuti',
    'deliyhana2022@gmail.com' => 'SRI DELY',
    'mohfajrin219@gmail.com' => 'Moh. Fajrin',
    'maulana022007@gmail.com' => 'MOH. RISKY MAULANA',
    'rosnirauf@gmail.com' => 'ROSNI',
    'wahyuniarfan1989@gmail.com' => 'Sri wahyuni syaripudin',
    'rizkika357@gmail.com' => 'Rizkyka Putri',
    'septiyadimerdekap@gmail.com' => 'SEPTYADI MERDEKA PUTRA',
    'sriwahyunikmajah@gmail.com' => 'SRI WAHYUNI K.MAJAH',
    'zhaafirah030501@gmail.com' => 'Yusriyyah Zhaafirah',
    'nuraida25041981@gmail.com' => 'Nuraida',
    'ramsaanca177@gmail.com' => 'RAMSA',
    'fajarirawan0501@gmail.com' => 'Fajar irawan',
    'yusnihidayah21@gmail.com' => 'Yusni Hidayah',
    'tinasuhar65@gmail.com' => 'Suhartina',
    'irnawati170792@gmail.com' => 'IRNAWATI ASHAR',
    'yuyundiahpratiwi2397@gmail.com' => 'YUYUN DIAH PRATIWI',
    'niluhulan@gmail.com' => 'Ni Luh Ulan Sariani',
    'dessy261297@gmail.com' => 'DESSY PUTRI AMALIA',
    'ikkenvs@gmail.com' => 'Ikke Nur Vita Sari',
    'nurinsyan14@gmail.com' => 'NURINSYAN',
    'animariany16@gmail.com' => 'Mariani',
    'moh.reza992@gmail.com' => 'MOHAMMAD REZA',
    'yunusmuh580@gmail.com' => 'Muh. Yunus',
    'anif73599@gmail.com' => 'Pitriani',
    'hasnirizal06@gmail.com' => 'Hasni Rizal',
    'tutiwinarni628@gmail.com' => 'Tuti Winarni',
    'tinatangkuman102@gmail.com' => 'Hartina',
    'lelaalfiana@gmail.com' => 'Alfiana',
    'iqbalm2410@gmail.com' => 'Moh Iqbal',
    'moh.khairaat@gmail.com' => 'Moh. Mulkan',
    'suhradinad@gmail.com' => 'SUHRADINA',
    'liaa301102@gmail.com' => 'Lia Andriani',
    'indrayaniburhanudin@gmail.com' => 'Indrayani Burhanudin',
    'smia89864@gmail.com' => 'Salmia',
    'srihardiyantii26@gmail.com' => 'Sri Hardiyanti',
    'aliflibiya254@gmail.com' => 'Alif Libiya Melani',
    '082293jya@gmail.com' => 'Nurjaya',
    'budimann225@gmail.com' => 'Budiman',
    'deviarni01@gmail.com' => 'Deviana',
    'darachandra2@gmail.com' => 'Chandra',
    'annisarahma16102002@gmail.com' => 'Annisa Rahmayanti',
    'melinndaa26@gmail.com' => 'Melinda',
    'ammargonggol@gmail.com' => 'Muammar',
    'riswanaswaludin4@gmail.com' => 'Moh. Riswan Aswaludin',
    'novianivhy34@gmail.com' => 'Noviani',
    'sittihajarsalam63@gmail.com' => 'Siti Hajar',
    'susantitolis2018@gmail.com' => 'Susanti',
    'tritakdir753@gmail.com' => 'TRI TAKDIR U SYAH',
    'puspitasaritolis2020@gmail.com' => 'Puspita Sari',
    'asdardahyar@gmail.com' => 'ASDAR',
    'nvtasariy@gmail.com' => 'Novita sari',
    'nia.060803@gmail.com' => 'Asmania',
    '2000padlia@gmail.com' => 'PADLIA',
    'rhiahijria994@gmail.com' => 'Fahrin',
    'fitryani0102@gmail.com' => 'Fitriani',
    'ibrahimanthonie0707@gmail.com' => 'Ibrahim anthonie',
    'fatihfatir97@gmail.com' => 'Marta',
    'zeynzacky2023@gmail.com' => 'MOHAMAD YASIR',
    'nasriajamrin2002@gmail.com' => 'Nasria',
    'rahmatialukman27@gmail.com' => 'RAHMATIA',
    'noviawulandari129@gmail.com' => 'Novia Edi Wulandari',
    'hasrianihamzah579@gmail.com' => 'Hasriani',
    'misnasupriadi935@gmail.com' => 'MISNA',
    'zulkifliusman1997@gmail.com' => 'ZULKIFLI USMAN',
    'sasmita08082004@gmail.com' => 'Sasmita',
    'nasirhartati77@gmail.com' => 'Hartati',
    'magfira066@gmail.com' => 'MAGFIRA',
    'ikamulyady@gmail.com' => 'Kartika',
    'abbdulsamad61@gmail.com' => 'Abd Samad',
    'reniamalia978@gmail.com' => 'RENI AMALIA',
    'irayuliastari1@gmail.com' => 'Ira yuliastari',
    'sardianaj@gmail.com' => 'Sardiana',
    'damsel.bangkir43@gmail.com' => 'Rezkiyani yaya',
    'siskaikha310@gmail.com' => 'Siska',
    'riniarditasari18@gmail.com' => 'Rini Ardita Sari',
    'rahmatiabahri@gmail.com' => 'Rahmatia',
    'kadirmankadir3@gmail.com' => 'Kadirman',
    'srisutanti4767@gmail.com' => 'SRI SUTANTi',
    'qolbirahmaaulia@gmail.com' => 'Rahma Aulia',
    'hasrianisri061@gmail.com' => 'Hasriani',
    'andimariam249@gmail.com' => 'Andi mariam',
    'andanghamdan51@gmail.com' => 'HAMDAN',
    'silfaniagus27@gmail.com' => 'Silfani',
    'esiastarita394@gmail.com' => 'Esi Astarita',
    'astarita832@gmail.com' => 'IRANINGSIH',
    'winisudarmin23@gmail.com' => 'WINI',
    'mokhoramli@gmail.com' => 'Harmoko',
    'miftahjannah1924@gmail.com' => 'Miftahul Jannah',
    'dhevyaries07@gmail.com' => 'Devi afrianingsi',
    'miftachulannah@gmail.com' => 'MIFTAHUL JANNAH',
    'tasyi679@gmail.com' => 'Natasya',
    'lukmanpramuka13@gmail.com' => 'LUKMAN',
    'syafiahfiah232@gmail.com' => 'Syafiah',
    'trisyetrisnawati01@gmail.com' => 'Trisye Trisnawati',
    'wana061101@gmail.com' => 'NISWANA',
    'yunianang04@gmail.com' => 'Wahyuni',
    'pianoviani2911@gmail.com' => 'Noviani',
    'bluebubblegum201@gmail.com' => 'AFIFAH TRI HUMAIRAH',
    'hallowdaya@gmail.com' => 'Nurhidayah',
    'abjadalam9@gmail.com' => 'Sudirman',
    'desiraihana8896@gmail.com' => 'Desi Raihana',
    'ramlicovid@gmail.com' => 'RAMLI',
    'tinaahada@gmail.com' => 'Siti hartina',
    'nrfaisah24@gmail.com' => 'Nur faisah',
    'samrins365@gmail.com' => 'Samrin',
    'ekarezkiana11@gmail.com' => 'Eka Rezkiana S. N. Daud',
    'lisstiawati10@gmail.com' => 'Lis Setyawati',
    'ikkireskiikkireski199@gmail.com' => 'RESKI',
    'irhamdhanial23@gmail.com' => 'M IRHAM',
    'abdulbasar181010129@gmail.com' => 'ABDUL BASAR',
    'nurinurhasanah46@gmail.com' => 'Nuri Nurhasanah',
    'sartika.awaludin@gmail.com' => 'Sartika',
    'moh.haris2025@gmail.com' => 'Moh Haris',
    'rizalreynaldy.rr@gmail.com' => 'MOH. RIZAL REYNALDY',
    'sindy.tolis22@gmail.com' => 'SINDI AMELIA',
    'mirzabahtiar87@gmail.com' => 'MIRZA',
    'samitmidong@gmail.com' => 'Samid',
    'hasmanman836@gmail.com' => 'HASMAN',
    'fadeld76@gmail.com' => 'Moh Fadel',
    'tolutjayamandiri@gmail.com' => 'Leni Liana',
    'marwarusno2000@gmail.com' => 'Marwa Rusno',
    'sayidatunnisah@gmail.com' => 'SAYIDATUN NISAH',
    'eetarf03@gmail.com' => 'Moh. Etsyakhran',
    'tahwiltahir@gmail.com' => 'Tahwil',
    'andisingkarahmadana@gmail.com' => 'ANDI SINGKA RAHMADANA',
    'yulianalatape@gmail.com' => 'Yuliana',
    'delilarf3009@gmail.com' => 'Delila Rizky Fauzia',
    'zahraanggraini9722@gmail.com' => 'ZAHRA FIRTSZA ANGGRAINI',
    'artalitagafur@gmail.com' => 'Artalita',
    'setiawanrahmat50509@gmail.com' => 'RAHMAT SETIAWAN',
    'yunitatolis5@gmail.com' => 'Yunita Arifin',
    'pong290597@gmail.com' => 'EVAYANA',
    'ikhsanwiratama2025@gmail.com' => 'Ikhsan',
    'mohikhwan826@gmail.com' => 'Moh Ikhwan',
    'pratiwihalil101@gmail.com' => 'Pratiwi',
    'ihsan.dumbo11@gmail.com' => 'Ihsan',
    'jalangkoteibuudin26@gmail.com' => 'ANDI JUMRIANI',
    'aisyahsah456@gmail.com' => 'Nur Aisyah',
    'kurniatirustam21@gmail.com' => 'Kurniati',
    'hafidzahnurul61@gmail.com' => 'Hafidzah Nurul Millah. Z',
    'ma147696@gmail.com' => 'Moh. Agung',
    'ikaingguti@gmail.com' => 'Nurwahida H Ingguti',
    'mohhamkadahlan271299@gmail.com' => 'MOH HAMKA',
    'efry0222@gmail.com' => 'Efriwanda',
    'fadlina.linaa2003@gmail.com' => 'FADLINA',
    'nahdatulrahman@gmail.com' => 'Nahdatu Rahma',
    'erwinpradistyaa@gmail.com' => 'Erwin',
    'arumijennaira1341@gmail.com' => 'Mega Alvionita',
    'miftahuljanna.mjii@gmail.com' => 'Miftahul Janna',
    'dindaaprilya222@gmail.com' => 'Dinda Aprilya',
    'ernihasbi56@gmail.com' => 'ERNY',
    'viivikusuma@gmail.com' => 'Silvie Sri Kusuma',
    'fitriwahyuningsih116@gmail.com' => 'Fitri Wahyu Ningsih',
    'muhainiyusuf@gmail.com' => 'MUHAINI YUSUF',
    'ammankmuhsalman@gmail.com' => 'Muhammad salman',
    'intanprdtha24@gmail.com' => 'Intan Paraditha',
    'fajaraswah53@gmail.com' => 'MUH FAJAR ASWAH',
    'ririnmarliana1430@gmail.com' => 'Ririn marliana',
    'adfadilah06@gmail.com' => 'Fadila',
    'safiraramadani1999@gmail.com' => 'Sapira Ramadani',
    'alwizihab285@gmail.com' => 'ALWI',
    'nandapade@icloud.com' => 'Albar Sukri',
    'rinipntoh@gmail.com' => 'Rini.s',
    'vhiasilvia2020@gmail.com' => 'Vina silvia',
    'shajaredwin@gmail.com' => 'Sitti Hajar E. Dt. Amas',
    'tampubolonsahala49@gmail.com' => 'SAHALA TAMPUBOLON',
    'niskayhabu@gmail.com' => 'Niska Y. Habu',
    'wahyuliadjmallo@gmail.com' => 'WAHYULIA',
    'mawar191291@gmail.com' => 'Mawar',
    'iketutpujaastawadiputra@gmail.com' => 'I ketut puja astawa diputra',
    'lisaxnx20@gmail.com' => 'Sitti Nurhaliza',
    'andinipontoh1@gmail.com' => 'ANDINI S A PONTOH',
    'nurlindahramadhanyy06@gmail.com' => 'Nurlindah Ramadhany',
    'sitierlinaarahmanerlina@gmail.com' => 'Siti Erlin A Rahman',
    'gamainumaki@gmail.com' => 'Ananda zesakarti pramana putra',
    'jusmahwati08@gmail.com' => 'Jusmahwati',
    'indarwandi937@gmail.com' => 'Indarwati',
    'musliaditolis@gmail.com' => 'Sitti kurnia',
    'agustinaxx02@gmail.com' => 'Agustina',
    'nurhasidahs125@gmail.com' => 'Nurhasida HS',
    'sritutialawia@gmail.com' => 'SRITUTI ALAWIA',
    'nurnawiraazzhara@gmail.com' => 'Nur Nawira Azzhara',
    'idjafar095@gmail.com' => 'MUHAMMAD NURDIANSYAH S DJAFAR',
    'djumaopall@gmail.com' => 'ALHAMDANI',
    'nafilatulyulmaz@gmail.com' => 'YULIANA',
    'menni010429@gmail.com' => 'Sumarni',
    'sridevilula991115@gmail.com' => 'Sri devi',
    'mohtaufanalfareza@gmail.com' => 'Muh. Taufan Al Fareza',
    'stuntingsalumpaga@gmail.com' => 'RAMNI',
];
$getNamaPML = fn($email, $namaDB) => $emailNama[strtolower($email)] ?? ($namaDB ?: $email);

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
$getKecPML = function($email, $kecDB) use ($emailKec, $kecNama) {
    $kec = $emailKec[strtolower($email)] ?? $kecDB;
    return $kec ? (($kecNama[$kec] ?? $kec) . ' (' . $kec . ')') : '—';
};

// ── Export CSV ────────────────────────────────────────────────────────────────
if (($_GET['action'] ?? '') === 'export') {
    $all = $conn->query("SELECT * FROM sensus_pml ORDER BY kec_code, nama, email")->fetch_all(MYSQLI_ASSOC);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="data_pml_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Email','Nama','Kec_Code','OPEN','DRAFT','SUBMITTED_BY_PENCACAH','APPROVED_BY_PENGAWAS','REJECTED_BY_PENGAWAS','REVOKED_BY_PENGAWAS','EDITED_BY_ADMIN_KABUPATEN','EDITED_BY_PENGAWAS']);
    foreach ($all as $row) {
        fputcsv($out, [$row['email'], $row['nama'], $row['kec_code'],
            $row['open_count'], $row['draft'], $row['submitted'],
            $row['approved'], $row['rejected'], $row['revoke'],
            $row['edited_by_pengawas'], $row['edited_by_admin']]);
    }
    fclose($out);
    exit;
}

// ── Export Excel ──────────────────────────────────────────────────────────────
if (($_GET['action'] ?? '') === 'export_excel') {
    $kecXls  = $conn->real_escape_string($_GET['kec'] ?? '');
    $where   = $kecXls ? "WHERE kec_code='$kecXls'" : '';
    $all     = $conn->query("SELECT * FROM sensus_pml $where ORDER BY kec_code, nama, email")->fetch_all(MYSQLI_ASSOC);

    $kecLabel = $kecXls ? ('_Kec' . $kecXls) : '';
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="Data_PML_SE2026' . $kecLabel . '_' . date('Ymd_His') . '.xls"');
    header('Cache-Control: max-age=0');

    function xc_pml($val, $sid = 'data') {
        $v = htmlspecialchars((string)($val ?? ''), ENT_XML1, 'UTF-8');
        return "<Cell ss:StyleID=\"{$sid}\"><Data ss:Type=\"String\">{$v}</Data></Cell>\n";
    }
    function xcn_pml($val, $sid = 'num') {
        return "<Cell ss:StyleID=\"{$sid}\"><Data ss:Type=\"Number\">" . (int)$val . "</Data></Cell>\n";
    }

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:x="urn:schemas-microsoft-com:office:excel">
 <Styles>
  <Style ss:ID="hdr">
   <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="9"/>
   <Interior ss:Color="#f79039" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
  </Style>
  <Style ss:ID="data">
   <Font ss:Size="9"/>
   <Alignment ss:Vertical="Top" ss:WrapText="1"/>
   <Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#e2e8f0"/></Borders>
  </Style>
  <Style ss:ID="num">
   <Font ss:Size="9"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Top"/>
   <Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#e2e8f0"/></Borders>
  </Style>
  <Style ss:ID="title"><Font ss:Bold="1" ss:Size="12"/></Style>
  <Style ss:ID="sub"><Font ss:Italic="1" ss:Size="9" ss:Color="#6b7280"/></Style>
 </Styles>
 <Worksheet ss:Name="Data PML SE2026">
  <Table>
   <Column ss:Width="28"/>
   <Column ss:Width="150"/>
   <Column ss:Width="130"/>
   <Column ss:Width="110"/>
   <Column ss:Width="42"/>
   <Column ss:Width="42"/>
   <Column ss:Width="52"/>
   <Column ss:Width="52"/>
   <Column ss:Width="52"/>
   <Column ss:Width="48"/>
   <Column ss:Width="55"/>
   <Column ss:Width="55"/>
   <Row ss:Height="20">
    <Cell ss:StyleID="title"><Data ss:Type="String">Data PML Sensus Ekonomi 2026' .
        ($kecXls ? ' — Kecamatan ' . htmlspecialchars($kecNama[$kecXls] ?? $kecXls) : '') .
    '</Data></Cell>
   </Row>
   <Row ss:Height="14">
    <Cell ss:StyleID="sub"><Data ss:Type="String">Dicetak: ' . date('d M Y H:i') . ' WIB  |  Total: ' . count($all) . ' PML</Data></Cell>
   </Row>
   <Row/>
   <Row ss:Height="28">
    ' . xc_pml('#', 'hdr') .
    xc_pml('Email PML', 'hdr') .
    xc_pml('Nama PML', 'hdr') .
    xc_pml('Kecamatan', 'hdr') .
    xc_pml('Open', 'hdr') .
    xc_pml('Draft', 'hdr') .
    xc_pml('Submit', 'hdr') .
    xc_pml('Approved', 'hdr') .
    xc_pml('Rejected', 'hdr') .
    xc_pml('Revoke', 'hdr') .
    xc_pml('Edit PML', 'hdr') .
    xc_pml('Edit Admin', 'hdr') . '
   </Row>';

    foreach ($all as $i => $row) {
        $resolvedKec = $emailKec[strtolower($row['email'])] ?? $row['kec_code'];
        $nmKec = ($kecNama[$resolvedKec] ?? $resolvedKec) . ' (' . $resolvedKec . ')';
        $nmPML = $getNamaPML($row['email'], $row['nama']);
        echo '   <Row>
    ' . xcn_pml($i + 1) .
        xc_pml($row['email']) .
        xc_pml($nmPML) .
        xc_pml($nmKec) .
        xcn_pml($row['open_count']) .
        xcn_pml($row['draft']) .
        xcn_pml($row['submitted']) .
        xcn_pml($row['approved']) .
        xcn_pml($row['rejected']) .
        xcn_pml($row['revoke']) .
        xcn_pml($row['edited_by_pengawas']) .
        xcn_pml($row['edited_by_admin']) . '
   </Row>' . "\n";
    }

    echo '  </Table>
 </Worksheet>
</Workbook>';
    exit;
}

// ── POST handlers ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $e = fn($v) => $conn->real_escape_string(trim((string)($v ?? '')));

    $redir_msg  = '';
    $redir_type = 'success';

    if ($action === 'add') {
        $email   = $e($_POST['email'] ?? '');
        $nama    = $e($_POST['nama'] ?? '');
        $kec     = $e($_POST['kec_code'] ?? '');
        $open_count        = (int)($_POST['open_count'] ?? 0);
        $draft             = (int)($_POST['draft'] ?? 0);
        $submitted         = (int)($_POST['submitted'] ?? 0);
        $approved          = (int)($_POST['approved'] ?? 0);
        $rejected          = (int)($_POST['rejected'] ?? 0);
        $revoke            = (int)($_POST['revoke'] ?? 0);
        $edited_by_pengawas = (int)($_POST['edited_by_pengawas'] ?? 0);
        $edited_by_admin    = (int)($_POST['edited_by_admin'] ?? 0);

        if (!$email || !$kec) {
            $redir_msg = 'Email dan Kecamatan wajib diisi.'; $redir_type = 'danger';
        } else {
            $conn->query("INSERT INTO sensus_pml
                (email, nama, kec_code, open_count, draft, submitted, approved, rejected, `revoke`, edited_by_pengawas, edited_by_admin)
                VALUES ('$email','$nama','$kec',$open_count,$draft,$submitted,$approved,$rejected,$revoke,$edited_by_pengawas,$edited_by_admin)");
            $redir_msg = $conn->affected_rows ? 'Data PML berhasil disimpan.' : ('Gagal: ' . $conn->error);
            if (!$conn->affected_rows) $redir_type = 'danger';
        }
    }
    elseif ($action === 'edit') {
        $id      = (int)($_POST['id'] ?? 0);
        $email   = $e($_POST['email'] ?? '');
        $nama    = $e($_POST['nama'] ?? '');
        $kec     = $e($_POST['kec_code'] ?? '');
        $open_count        = (int)($_POST['open_count'] ?? 0);
        $draft             = (int)($_POST['draft'] ?? 0);
        $submitted         = (int)($_POST['submitted'] ?? 0);
        $approved          = (int)($_POST['approved'] ?? 0);
        $rejected          = (int)($_POST['rejected'] ?? 0);
        $revoke            = (int)($_POST['revoke'] ?? 0);
        $edited_by_pengawas = (int)($_POST['edited_by_pengawas'] ?? 0);
        $edited_by_admin    = (int)($_POST['edited_by_admin'] ?? 0);

        if ($id && $email && $kec) {
            $conn->query("UPDATE sensus_pml SET
                email='$email', nama='$nama', kec_code='$kec',
                open_count=$open_count, draft=$draft, submitted=$submitted,
                approved=$approved, rejected=$rejected,
                `revoke`=$revoke, edited_by_pengawas=$edited_by_pengawas, edited_by_admin=$edited_by_admin
                WHERE id=$id");
            $redir_msg = 'Data PML berhasil diperbarui.';
        } else {
            $redir_msg = 'Data tidak valid.'; $redir_type = 'danger';
        }
    }
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $conn->query("DELETE FROM sensus_pml WHERE id=$id");
            $redir_msg = 'Data PML berhasil dihapus.';
        }
    }
    elseif ($action === 'delete_all') {
        $conn->query("TRUNCATE TABLE sensus_pml");
        $redir_msg = 'Semua data PML berhasil dihapus.';
    }
    elseif ($action === 'import_csv') {
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $handle     = fopen($_FILES['csv_file']['tmp_name'], 'r');
            $header     = fgetcsv($handle);
            $upserted   = 0; $skipped = 0;
            $clearFirst = ($_POST['overwrite'] ?? '0') === '1';

            if ($clearFirst) {
                $conn->query("TRUNCATE TABLE sensus_pml");
                $conn->query("TRUNCATE TABLE sensus_sls_pengawas");
            }

            // Format: Email, Nama, SLS_Code, OPEN, DRAFT, SUBMITTED_BY_PENCACAH,
            //         APPROVED_BY_PENGAWAS, REJECTED_BY_PENGAWAS, REVOKED_BY_PENGAWAS,
            //         EDITED_BY_ADMIN_KABUPATEN, EDITED_BY_PENGAWAS
            // kec_code = $emailKec lookup, fallback 3 digit pertama SLS_Code
            // Baris di-aggregate (SUM) per email+kec_code sebelum upsert
            $grouped    = [];
            $slsMapping = []; // sls_code => [email, nama]
            while (($row = fgetcsv($handle)) !== false) {
                $row    = array_pad($row, 11, 0);
                [$remail, $rnama, $rsls, $ropen, $rdraft, $rsub, $rappr, $rrej, $rrev, $rea, $rep] = $row;
                $remail = strtolower(trim(trim($remail), '"'));
                $rnama  = trim(trim($rnama), '"');
                $rsls   = trim(trim($rsls), '"');
                if (!$remail) { $skipped++; continue; }
                $rkec = $emailKec[strtolower($remail)] ?? substr($rsls, 0, 3);
                if (!$rkec) { $skipped++; continue; }

                // Simpan mapping sls_code → PML
                if ($rsls) $slsMapping[$rsls] = ['email' => $remail, 'nama' => $rnama];

                $key = $remail . '|' . $rkec;
                if (!isset($grouped[$key])) {
                    $grouped[$key] = ['email'=>$remail,'nama'=>$rnama,'kec'=>$rkec,
                        'op'=>0,'dr'=>0,'sb'=>0,'ap'=>0,'rj'=>0,'rv'=>0,'ea'=>0,'ep'=>0];
                }
                $grouped[$key]['op'] += (int)$ropen;
                $grouped[$key]['dr'] += (int)$rdraft;
                $grouped[$key]['sb'] += (int)$rsub;
                $grouped[$key]['ap'] += (int)$rappr;
                $grouped[$key]['rj'] += (int)$rrej;
                $grouped[$key]['rv'] += (int)$rrev;
                $grouped[$key]['ea'] += (int)$rea;   // EDITED_BY_ADMIN_KABUPATEN
                $grouped[$key]['ep'] += (int)$rep;   // EDITED_BY_PENGAWAS
            }
            fclose($handle);

            // Simpan mapping sls_code → pengawas
            foreach ($slsMapping as $sls => $pml) {
                $es = $conn->real_escape_string($sls);
                $ep = $conn->real_escape_string($pml['email']);
                $np = $conn->real_escape_string($pml['nama']);
                $conn->query("INSERT INTO sensus_sls_pengawas (sls_code, pml_email, pml_nama)
                    VALUES ('$es','$ep','$np')
                    ON DUPLICATE KEY UPDATE pml_email='$ep', pml_nama='$np'");
            }

            foreach ($grouped as $g) {
                $em = $conn->real_escape_string($g['email']);
                $nm = $conn->real_escape_string($g['nama']);
                $kc = $conn->real_escape_string($g['kec']);
                [$op,$dr,$sb,$ap,$rj,$rv,$ep,$ea] = [$g['op'],$g['dr'],$g['sb'],$g['ap'],$g['rj'],$g['rv'],$g['ep'],$g['ea']];
                $r = $conn->query("INSERT INTO sensus_pml
                    (email, nama, kec_code, open_count, draft, submitted, approved, rejected, `revoke`, edited_by_pengawas, edited_by_admin)
                    VALUES ('$em','$nm','$kc',$op,$dr,$sb,$ap,$rj,$rv,$ep,$ea)
                    ON DUPLICATE KEY UPDATE
                    nama='$nm', open_count=$op, draft=$dr, submitted=$sb,
                    approved=$ap, rejected=$rj, `revoke`=$rv,
                    edited_by_pengawas=$ep, edited_by_admin=$ea");
                if ($r) $upserted++; else $skipped++;
            }
            $redir_msg = "Import selesai: $upserted baris diproses (insert/update), " . count($slsMapping) . " SLS pengawas dipetakan" . ($skipped ? ", $skipped dilewati" : '') . '.';
        } else {
            $redir_msg = 'Gagal membaca file CSV.'; $redir_type = 'danger';
        }
    }

    $qs = http_build_query(['msg' => $redir_msg, 'msg_type' => $redir_type,
        'q' => $_GET['q'] ?? '', 'kec' => $_GET['kec'] ?? '', 'page' => $_GET['page'] ?? 1]);
    header("Location: data_pml_sensus.php?$qs");
    exit;
}

// ── GET params ────────────────────────────────────────────────────────────────
$q         = $conn->real_escape_string($_GET['q']  ?? '');
$filterKec = $conn->real_escape_string($_GET['kec'] ?? '');
$msg       = htmlspecialchars($_GET['msg']      ?? '');
$msgType   = htmlspecialchars($_GET['msg_type'] ?? 'success');
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 50;

$conds = [];
if ($q)         $conds[] = "(email LIKE '%$q%' OR nama LIKE '%$q%')";
if ($filterKec) $conds[] = "kec_code='$filterKec'";
$whereSQL = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

$stats = $conn->query("SELECT
    COUNT(*) AS total,
    COALESCE(SUM(open_count),0) AS total_open,
    COALESCE(SUM(draft),0) AS total_draft,
    COALESCE(SUM(submitted),0) AS total_submitted,
    COALESCE(SUM(approved),0) AS total_approved,
    COALESCE(SUM(rejected),0) AS total_rejected,
    COALESCE(SUM(`revoke`),0) AS total_revoke,
    COALESCE(SUM(edited_by_pengawas),0) AS total_edited_by_pengawas,
    COALESCE(SUM(edited_by_admin),0) AS total_edited_by_admin
FROM sensus_pml")->fetch_assoc();

$totalRec   = (int)$conn->query("SELECT COUNT(*) c FROM sensus_pml $whereSQL")->fetch_assoc()['c'];
$totalPages = max(1, (int)ceil($totalRec / $perPage));
$offset     = ($page - 1) * $perPage;
$records    = $conn->query("SELECT * FROM sensus_pml $whereSQL ORDER BY kec_code, nama, email LIMIT $perPage OFFSET $offset")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data PML Sensus Ekonomi — SEMANIS 2026</title>
    <link rel="stylesheet" href="../assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/vendor/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        .stat-icon { width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; flex-shrink:0; }
        .section-head { font-weight:700; font-size:.7rem; text-transform:uppercase; letter-spacing:.08em; color:#94a3b8; margin-bottom:14px; }
        .table-hover tbody tr:hover { background:#fff8f0; }
        .act-btn { padding:3px 8px; font-size:.78rem; border-radius:6px; }
        .badge-open     { background:#ffedd5; color:#c2410c; }
        .badge-draft    { background:#dbeafe; color:#1d4ed8; }
        .badge-sub      { background:#dcfce7; color:#166534; }
        .badge-approved { background:#dcfce7; color:#166534; }
        .badge-rej      { background:#fee2e2; color:#991b1b; }
        .badge-rev      { background:#ffedd5; color:#c2410c; }
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
                    <i class="bi bi-person-check-fill me-2" style="color:#f79039"></i>Data PML Sensus Ekonomi
                </div>
                <div class="topbar-sub">Kelola Data Progress Pengawas Lapangan (PML) SE2026</div>
            </div>
            <div class="topbar-right">
                <a href="../monitoring_sensus.html" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-graph-up-arrow me-1"></i>Lihat Monitoring
                </a>
            </div>
        </header>

        <div class="page-body">

            <?php if ($msg): ?>
            <div class="alert alert-<?= $msgType ?> alert-dismissible d-flex align-items-center" style="border-radius:10px">
                <i class="bi bi-<?= $msgType === 'success' ? 'check-circle-fill' : 'x-circle-fill' ?> me-2"></i>
                <?= $msg ?>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- ── Stat Cards ─────────────────────────────────── -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-layers-fill"></i></div>
                            <div><div class="fs-2 fw-bold lh-1"><?= number_format((int)$stats['total']) ?></div><div class="text-muted small">Total Record</div></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check2-all"></i></div>
                            <div><div class="fs-2 fw-bold lh-1"><?= number_format((int)$stats['total_approved']) ?></div><div class="text-muted small">Approved</div></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-folder2-open"></i></div>
                            <div><div class="fs-2 fw-bold lh-1"><?= number_format((int)$stats['total_open']) ?></div><div class="text-muted small">Open</div></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="card stat-card p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-x-circle-fill"></i></div>
                            <div><div class="fs-2 fw-bold lh-1"><?= number_format((int)$stats['total_rejected']) ?></div><div class="text-muted small">Rejected</div></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Toolbar ──────────────────────────────────────── -->
            <div class="card stat-card p-3 mb-4">
                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                    <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                        <div class="input-group" style="max-width:280px">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="q" class="form-control border-start-0" placeholder="Cari nama, email…" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                        </div>
                        <select name="kec" class="form-select" style="max-width:200px">
                            <option value="">Semua Kecamatan</option>
                            <?php foreach ($kecNama as $kd => $nm): ?>
                            <option value="<?= $kd ?>" <?= $filterKec === $kd ? 'selected' : '' ?>><?= $kd ?> — <?= $nm ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm text-white" style="background:#f79039">
                            <i class="bi bi-funnel-fill me-1"></i>Filter
                        </button>
                        <?php if ($q || $filterKec): ?>
                        <a href="data_pml_sensus.php" class="btn btn-sm btn-outline-secondary">Reset</a>
                        <?php endif; ?>
                    </form>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-sm text-white" style="background:#f79039" onclick="openAddModal()">
                            <i class="bi bi-plus-lg me-1"></i>Tambah
                        </button>
                        <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class="bi bi-upload me-1"></i>Import CSV
                        </button>
                        <a href="?action=export" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-download me-1"></i>Export CSV
                        </a>
                        <a href="?action=export_excel&kec=<?= htmlspecialchars($filterKec) ?>" class="btn btn-sm btn-success">
                            <i class="bi bi-file-earmark-excel me-1"></i>Unduh Excel
                        </a>
                        <button class="btn btn-sm btn-outline-danger" onclick="confirmDeleteAll()">
                            <i class="bi bi-trash3-fill me-1"></i>Hapus Semua
                        </button>
                    </div>
                </div>
            </div>

            <!-- ── Data Table ──────────────────────────────────── -->
            <div class="card stat-card p-0">
                <div class="d-flex justify-content-between align-items-center px-4 pt-3 pb-2">
                    <div class="section-head mb-0">
                        <i class="bi bi-table me-1"></i>Data PML Sensus Ekonomi
                        <span class="badge bg-secondary ms-1 fw-normal"><?= $totalRec ?> record</span>
                    </div>
                    <small class="text-muted"><?= date('d M Y, H:i') ?> WITA</small>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:.83rem;">
                        <thead style="background:#f79039; color:#fff;">
                            <tr>
                                <th class="ps-4" style="width:50px">#</th>
                                <th>Email PML</th>
                                <th>Nama</th>
                                <th>Kecamatan</th>
                                <th class="text-center">Open</th>
                                <th class="text-center">Draft</th>
                                <th class="text-center">Submit</th>
                                <th class="text-center">Approved</th>
                                <th class="text-center">Rejected</th>
                                <th class="text-center">Revoke</th>
                                <th class="text-center">Edit PML</th>
                                <th class="text-center">Edit Admin</th>
                                <th class="text-center pe-4" style="width:90px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($records)): ?>
                            <tr><td colspan="13" class="text-center text-muted py-5">
                                Belum ada data. Gunakan tombol <strong>Tambah</strong> untuk menambahkan data PML.
                            </td></tr>
                        <?php else: ?>
                        <?php foreach ($records as $i => $r): ?>
                            <tr>
                                <td class="ps-4 text-muted"><?= $offset + $i + 1 ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($r['email']) ?></td>
                                <td><?= htmlspecialchars($getNamaPML($r['email'], $r['nama'])) ?></td>
                                <td>
                                    <?php $resolvedKec = $emailKec[strtolower($r['email'])] ?? $r['kec_code']; ?>
                                    <span class="badge" style="background:#fff3e0;color:#f79039"><?= htmlspecialchars($resolvedKec) ?></span>
                                    <?= htmlspecialchars($kecNama[$resolvedKec] ?? '') ?>
                                </td>
                                <td class="text-center"><span class="badge badge-open"><?= $r['open_count'] ?></span></td>
                                <td class="text-center"><span class="badge badge-draft"><?= $r['draft'] ?></span></td>
                                <td class="text-center"><span class="badge badge-sub"><?= $r['submitted'] ?></span></td>
                                <td class="text-center"><span class="badge badge-approved"><?= $r['approved'] ?></span></td>
                                <td class="text-center"><span class="badge badge-rej"><?= $r['rejected'] ?></span></td>
                                <td class="text-center"><span class="badge badge-rev"><?= $r['revoke'] ?></span></td>
                                <td class="text-center"><span class="badge" style="background:#d1fae5;color:#065f46"><?= $r['edited_by_pengawas'] ?></span></td>
                                <td class="text-center"><span class="badge" style="background:#e0e7ff;color:#3730a3"><?= $r['edited_by_admin'] ?></span></td>
                                <td class="text-center pe-4">
                                    <button class="btn btn-sm btn-outline-primary act-btn me-1"
                                            onclick='openEditModal(<?= json_encode($r) ?>)'>
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger act-btn"
                                            onclick="confirmDelete(<?= $r['id'] ?>, '<?= htmlspecialchars(addslashes($r['email']), ENT_QUOTES) ?>')">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top">
                    <small class="text-muted">Halaman <?= $page ?> dari <?= $totalPages ?> (<?= $totalRec ?> record)</small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <?php
                            $qsBase = http_build_query(['q' => $_GET['q'] ?? '', 'kec' => $_GET['kec'] ?? '']);
                            $start  = max(1, $page - 2);
                            $end    = min($totalPages, $page + 2);
                            if ($page > 1): ?>
                            <li class="page-item"><a class="page-link" href="?<?= $qsBase ?>&page=<?= $page-1 ?>">‹</a></li>
                            <?php endif;
                            for ($p = $start; $p <= $end; $p++): ?>
                            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= $qsBase ?>&page=<?= $p ?>"><?= $p ?></a>
                            </li>
                            <?php endfor;
                            if ($page < $totalPages): ?>
                            <li class="page-item"><a class="page-link" href="?<?= $qsBase ?>&page=<?= $page+1 ?>">›</a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>

        </div><!-- /page-body -->
    </div><!-- /main-content -->
</div>

<!-- ── Modal Tambah / Edit ─────────────────────────────────────────────────── -->
<div class="modal fade" id="dataModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="dataForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="formId">
                <div class="modal-header" style="background:#f79039; color:#fff;">
                    <h5 class="modal-title" id="modalTitle"><i class="bi bi-plus-lg me-2"></i>Tambah Data PML</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email PML <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="formEmail" class="form-control" required placeholder="contoh@gmail.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nama PML</label>
                            <input type="text" name="nama" id="formNama" class="form-control" placeholder="Nama lengkap PML">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kecamatan <span class="text-danger">*</span></label>
                            <select name="kec_code" id="formKec" class="form-select" required>
                                <option value="">-- Pilih Kecamatan --</option>
                                <?php foreach ($kecNama as $kd => $nm): ?>
                                <option value="<?= $kd ?>"><?= $kd ?> — <?= $nm ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Open</label>
                            <input type="number" name="open_count" id="formOpenCount" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Draft</label>
                            <input type="number" name="draft" id="formDraft" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Submitted</label>
                            <input type="number" name="submitted" id="formSubmitted" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Approved</label>
                            <input type="number" name="approved" id="formApproved" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Rejected</label>
                            <input type="number" name="rejected" id="formRejected" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Revoke</label>
                            <input type="number" name="revoke" id="formRevoke" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Edit PML (Pengawas)</label>
                            <input type="number" name="edited_by_pengawas" id="formEditedByPengawas" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Edit Admin</label>
                            <input type="number" name="edited_by_admin" id="formEditedByAdmin" class="form-control" min="0" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn text-white fw-semibold" style="background:#f79039" id="submitBtn">
                        <i class="bi bi-save-fill me-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Modal Delete ────────────────────────────────────────────────────────── -->
<form method="POST" id="deleteForm">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-trash-fill me-2"></i>Konfirmasi Hapus</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Yakin menghapus data PML <strong id="deleteEmail"></strong>? Tindakan ini tidak dapat dibatalkan.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger fw-semibold" onclick="document.getElementById('deleteForm').submit()">
                    <i class="bi bi-trash-fill me-1"></i>Hapus
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Modal Delete All ────────────────────────────────────────────────────── -->
<form method="POST" id="deleteAllForm">
    <input type="hidden" name="action" value="delete_all">
</form>
<div class="modal fade" id="deleteAllModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Hapus Semua Data PML</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger mb-0">
                    <strong>Peringatan!</strong> Semua <?= number_format((int)$stats['total']) ?> record data PML akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger fw-semibold" onclick="document.getElementById('deleteAllForm').submit()">
                    <i class="bi bi-trash3-fill me-1"></i>Hapus Semua
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── Modal Import CSV ─────────────────────────────────────────────────────── -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import_csv">
                <div class="modal-header" style="background:#f79039; color:#fff;">
                    <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Import CSV Data PML</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3" style="font-size:.82rem;">
                        <strong>Format kolom CSV (urut):</strong><br>
                        <code>Email, Nama, SLS_Code, OPEN, DRAFT, SUBMITTED_BY_PENCACAH, APPROVED_BY_PENGAWAS, REJECTED_BY_PENGAWAS, REVOKED_BY_PENGAWAS, EDITED_BY_ADMIN_KABUPATEN, EDITED_BY_PENGAWAS</code>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih File CSV <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv,text/csv" required>
                    </div>
                    <div class="mb-0">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="overwrite" value="1" id="chkOverwrite">
                            <label class="form-check-label small" for="chkOverwrite">
                                <strong>Reset — hapus semua data lama sebelum import</strong><br>
                                <span class="text-muted">Jika <strong>tidak</strong> dicentang (default): data existing di-update, baris baru di-insert (aman untuk update berkala). Jika dicentang: semua data dihapus dulu lalu seluruh baris dari file dimasukkan.</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-outline-success btn-sm me-auto" onclick="downloadTemplate()">
                        <i class="bi bi-download me-1"></i>Template
                    </button>
                    <button type="submit" class="btn btn-success fw-semibold">
                        <i class="bi bi-upload me-1"></i>Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
function openAddModal() {
    document.getElementById('formAction').value   = 'add';
    document.getElementById('formId').value       = '';
    document.getElementById('formEmail').value    = '';
    document.getElementById('formNama').value     = '';
    document.getElementById('formKec').value               = '';
    document.getElementById('formOpenCount').value         = '0';
    document.getElementById('formDraft').value             = '0';
    document.getElementById('formSubmitted').value         = '0';
    document.getElementById('formApproved').value          = '0';
    document.getElementById('formRejected').value          = '0';
    document.getElementById('formRevoke').value            = '0';
    document.getElementById('formEditedByPengawas').value  = '0';
    document.getElementById('formEditedByAdmin').value     = '0';
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-plus-lg me-2"></i>Tambah Data PML';
    document.getElementById('submitBtn').innerHTML  = '<i class="bi bi-save-fill me-1"></i>Simpan';
    new bootstrap.Modal(document.getElementById('dataModal')).show();
}

function openEditModal(r) {
    document.getElementById('formAction').value   = 'edit';
    document.getElementById('formId').value       = r.id;
    document.getElementById('formEmail').value    = r.email || '';
    document.getElementById('formNama').value     = r.nama || '';
    document.getElementById('formKec').value               = r.kec_code || '';
    document.getElementById('formOpenCount').value         = r.open_count || 0;
    document.getElementById('formDraft').value             = r.draft || 0;
    document.getElementById('formSubmitted').value         = r.submitted || 0;
    document.getElementById('formApproved').value          = r.approved || 0;
    document.getElementById('formRejected').value          = r.rejected || 0;
    document.getElementById('formRevoke').value            = r.revoke || 0;
    document.getElementById('formEditedByPengawas').value  = r.edited_by_pengawas || 0;
    document.getElementById('formEditedByAdmin').value     = r.edited_by_admin || 0;
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil-fill me-2"></i>Edit Data PML';
    document.getElementById('submitBtn').innerHTML  = '<i class="bi bi-save-fill me-1"></i>Perbarui';
    new bootstrap.Modal(document.getElementById('dataModal')).show();
}

function confirmDelete(id, email) {
    document.getElementById('deleteId').value           = id;
    document.getElementById('deleteEmail').textContent  = email;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

function confirmDeleteAll() {
    new bootstrap.Modal(document.getElementById('deleteAllModal')).show();
}

function downloadTemplate() {
    const rows = [
        ['Email','Nama','SLS_Code','OPEN','DRAFT','SUBMITTED_BY_PENCACAH','APPROVED_BY_PENGAWAS','REJECTED_BY_PENGAWAS','REVOKED_BY_PENGAWAS','EDITED_BY_ADMIN_KABUPATEN','EDITED_BY_PENGAWAS'],
        ['"contoh@gmail.com"','"Nama PML"','"040001001"','10','5','3','5','2','1','0','1'],
        ['"contoh@gmail.com"','"Nama PML"','"040001002"','8','3','2','3','1','0','0','0'],
    ];
    const csv  = rows.map(r => r.join(',')).join('\n');
    const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
    const a    = document.createElement('a');
    a.href     = URL.createObjectURL(blob);
    a.download = 'template_pml.csv';
    a.click();
}
</script>
</body>
</html>
