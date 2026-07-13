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
$chk2 = $conn->query("SHOW COLUMNS FROM sensus_ekonomi LIKE 'edited_by_pengawas'");
if ($chk2 && $chk2->num_rows === 0) {
    $conn->query("ALTER TABLE sensus_ekonomi ADD COLUMN edited_by_pengawas INT DEFAULT 0 AFTER `revoke`");
}
$chk3 = $conn->query("SHOW COLUMNS FROM sensus_ekonomi LIKE 'edited_by_admin_kabupaten'");
if ($chk3 && $chk3->num_rows === 0) {
    $conn->query("ALTER TABLE sensus_ekonomi ADD COLUMN edited_by_admin_kabupaten INT DEFAULT 0 AFTER edited_by_pengawas");
}

$kecNama = [
    '010' => 'Dampal Selatan', '020' => 'Dampal Utara',
    '030' => 'Dondo',          '031' => 'Ogodeide',
    '032' => 'Basidondo',      '040' => 'Baolan',
    '041' => 'Lampasio',       '050' => 'Galang',
    '060' => 'Tolitoli Utara', '061' => 'Dako Pemean',
];

// Data petugas dari email fix.xlsx: email → [nama, kec_code, kecamatan, desa]
$emailData = [
    'wirtakwir8@gmail.com'           => ['nama'=>'Takwir','kec_code'=>'060','kecamatan'=>'TOLITOLI UTARA','desa'=>'SALUMPAGA'],
    'nurazizalakoro@gmail.com'        => ['nama'=>'Nuraziza','kec_code'=>'060','kecamatan'=>'TOLITOLI UTARA','desa'=>'SALUMPAGA'],
    'sugengarrauf@gmail.com'          => ['nama'=>'Sugeng. A','kec_code'=>'060','kecamatan'=>'TOLITOLI UTARA','desa'=>'DIULE'],
    'lyliburhan99@gmail.com'          => ['nama'=>'Eriyanti S','kec_code'=>'060','kecamatan'=>'TOLITOLI UTARA','desa'=>'PINJAN'],
    'rahmiagussalim43@gmail.com'      => ['nama'=>'Rahmi Agussalim','kec_code'=>'060','kecamatan'=>'TOLITOLI UTARA','desa'=>'DIULE'],
    'mukranumran@gmail.com'           => ['nama'=>'Mukran','kec_code'=>'060','kecamatan'=>'TOLITOLI UTARA','desa'=>'LAKUAN TOLITOLI'],
    'azizanihru@gmail.com'            => ['nama'=>'Aziza','kec_code'=>'060','kecamatan'=>'TOLITOLI UTARA','desa'=>'SALUMPAGA'],
    'ramnitolis2023@gmail.com'        => ['nama'=>'RAMNI','kec_code'=>'060','kecamatan'=>'TOLITOLI UTARA','desa'=>'SALUMPAGA'],
    'fitri.ani0@yahoo.com'            => ['nama'=>'Fitriani','kec_code'=>'060','kecamatan'=>'TOLITOLI UTARA','desa'=>'PINJAN'],
    'ahmadsumitro96@gmail.com'        => ['nama'=>'Ahmad','kec_code'=>'060','kecamatan'=>'TOLITOLI UTARA','desa'=>'PINJAN'],
    'sriwahyunimakkuraga@gmail.com'   => ['nama'=>'Sri wahyuni','kec_code'=>'061','kecamatan'=>'DAKO PEMEAN','desa'=>'GALUMPANG'],
    'sahratulaini99@gmail.com'        => ['nama'=>'SAHRATUL AINI','kec_code'=>'061','kecamatan'=>'DAKO PEMEAN','desa'=>'DUNGINGIS'],
    'hashari.h.hh@gmail.com'          => ['nama'=>'Hashari','kec_code'=>'061','kecamatan'=>'DAKO PEMEAN','desa'=>'KAPAS'],
    'armina160298@gmail.com'          => ['nama'=>'Armina','kec_code'=>'061','kecamatan'=>'DAKO PEMEAN','desa'=>'LINGADAN'],
    'ulfiani.ani992@gmail.com'        => ['nama'=>'ULPIANI RUSNO','kec_code'=>'061','kecamatan'=>'DAKO PEMEAN','desa'=>'LINGADAN'],
    'dewisosiartika@gmail.com'        => ['nama'=>'DEWI SOSIARTIKA','kec_code'=>'061','kecamatan'=>'DAKO PEMEAN','desa'=>'GALUMPANG'],
    'syahdigalumpang@gmail.com'       => ['nama'=>'SYAHDI','kec_code'=>'061','kecamatan'=>'DAKO PEMEAN','desa'=>'GALUMPANG'],
    'afriantomuhsen756@gmail.com'     => ['nama'=>'Afrianto','kec_code'=>'020','kecamatan'=>'DAMPAL UTARA','desa'=>'OGOTUA'],
    'albarbar24@gmail.com'            => ['nama'=>'ALBAR','kec_code'=>'020','kecamatan'=>'DAMPAL UTARA','desa'=>'STADONG'],
    'halfinafhyna@gmail.com'          => ['nama'=>'HALFINA','kec_code'=>'010','kecamatan'=>'DAMPAL SELATAN','desa'=>'TAMPIALA'],
    '02lovelymore@gmail.com'          => ['nama'=>'FARADHIBA','kec_code'=>'020','kecamatan'=>'DAMPAL UTARA','desa'=>'OGOTUA'],
    'farida181198@gmail.com'          => ['nama'=>'Farida','kec_code'=>'020','kecamatan'=>'DAMPAL UTARA','desa'=>'OGOTUA'],
    'karnitanita808@gmail.com'        => ['nama'=>'KARNITA','kec_code'=>'020','kecamatan'=>'DAMPAL UTARA','desa'=>'BAMBAPULA'],
    'meyandris29@gmail.com'           => ['nama'=>'MEYSAHDI. A','kec_code'=>'020','kecamatan'=>'DAMPAL UTARA','desa'=>'OGOTUA'],
    'muliantii1996@gmail.com'         => ['nama'=>'Mulianti Maulana','kec_code'=>'020','kecamatan'=>'DAMPAL UTARA','desa'=>'SESE'],
    'misnajo1@gmail.com'              => ['nama'=>'Misna','kec_code'=>'020','kecamatan'=>'DAMPAL UTARA','desa'=>'OGOTUA'],
    'meghanaharudin763@gmail.com'     => ['nama'=>'Sulastri','kec_code'=>'020','kecamatan'=>'DAMPAL UTARA','desa'=>'BANAGAN'],
    'wahyudiforester6@gmail.com'      => ['nama'=>'WAHYUDI MOH HARIS','kec_code'=>'020','kecamatan'=>'DAMPAL UTARA','desa'=>'SESE'],
    'iffatpratama321@gmail.com'       => ['nama'=>'Moh. Iffat Widad Pratama','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'BARU'],
    'ffara8629@gmail.com'             => ['nama'=>'Farah','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'PANGI'],
    'faisalchaldo@gmail.com'          => ['nama'=>'Moh faisal','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'PANASAKAN'],
    'nurulayustina198@gmail.com'      => ['nama'=>'Nurul Ayustina','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'TUWELEY'],
    'trimuliadi00@gmail.com'          => ['nama'=>'TRI MULIADI','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'NALU'],
    'latumpu77@gmail.com'             => ['nama'=>'yusuf','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'BARU'],
    'mutmainna140201@gmail.com'       => ['nama'=>'Mutmainna','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'PANASAKAN'],
    'nurdianadina1220@gmail.com'      => ['nama'=>'Nurdiana Zaharman','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'TAMBUN'],
    'saniyyah0410@gmail.com'          => ['nama'=>'SANIYYAH NUR PURNAMA S.Sos','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'BARU'],
    'ayusyawitri22@gmail.com'         => ['nama'=>'SRI DEVI AYU SYAWITRI','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'BARU'],
    'usuci8993@gmail.com'             => ['nama'=>'Suci Safitry','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'TAMBUN'],
    'nurhajra08092019@gmail.com'      => ['nama'=>'NUR HAJRA SYAFRUDDIN','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'PANASAKAN'],
    'indahade247@gmail.com'           => ['nama'=>'ADE INDAH PUSPITA SARI','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'TAMBUN'],
    'ahmaadtlz17@gmail.com'           => ['nama'=>'AHMAD SAID','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'PANASAKAN'],
    'fitriyantimarhalil@gmail.com'    => ['nama'=>'Fitriyanti','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'NALU'],
    'umijarnawi@gmail.com'            => ['nama'=>'JUMIATI','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'LELEAN NONO'],
    'wulandarisb981@gmail.com'        => ['nama'=>'Wulandari S. Butudoka','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'NALU'],
    'nanadjapara10@gmail.com'         => ['nama'=>'Nurhasanah','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'NALU'],
    'raramutiara353@gmail.com'        => ['nama'=>'ANDI MUTIARA ISTIQHAMARANI','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'SIDOARJO'],
    'iyasdwi996@gmail.com'            => ['nama'=>'Dwi wahyono','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'NALU'],
    'fitriyawatitadore@gmail.com'     => ['nama'=>'FITRIYAWATI NH HANS TADORE','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'SIDOARJO'],
    'astuti7061992@gmail.com'         => ['nama'=>'Tri astuti','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'BUNTUNA'],
    'deliyhana2022@gmail.com'         => ['nama'=>'SRI DELY','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'TUWELEY'],
    'mohfajrin219@gmail.com'          => ['nama'=>'Moh. Fajrin','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'BARU'],
    'maulana022007@gmail.com'         => ['nama'=>'MOH. RISKY MAULANA','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'TAMBUN'],
    'rosnirauf@gmail.com'             => ['nama'=>'ROSNI','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'BARU'],
    'wahyuniarfan1989@gmail.com'      => ['nama'=>'Sri wahyuni syaripudin','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'TUWELEY'],
    'rizkika357@gmail.com'            => ['nama'=>'Rizkyka Putri','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'PANASAKAN'],
    'septiyadimerdekap@gmail.com'     => ['nama'=>'SEPTYADI MERDEKA PUTRA','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'PANASAKAN'],
    'sriwahyunikmajah@gmail.com'      => ['nama'=>'SRI WAHYUNI K.MAJAH','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'TUWELEY'],
    'zhaafirah030501@gmail.com'       => ['nama'=>'Yusriyyah Zhaafirah','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'BARU'],
    'nuraida25041981@gmail.com'       => ['nama'=>'Nuraida','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'SIDOARJO'],
    'ramsaanca177@gmail.com'          => ['nama'=>'RAMSA','kec_code'=>'032','kecamatan'=>'BASIDONDO','desa'=>'BASI'],
    'fajarirawan0501@gmail.com'       => ['nama'=>'Fajar irawan','kec_code'=>'032','kecamatan'=>'BASIDONDO','desa'=>'KAYULOMPA'],
    'yusnihidayah21@gmail.com'        => ['nama'=>'Yusni Hidayah','kec_code'=>'032','kecamatan'=>'BASIDONDO','desa'=>'BASI'],
    'tinasuhar65@gmail.com'           => ['nama'=>'Suhartina','kec_code'=>'032','kecamatan'=>'BASIDONDO','desa'=>'LABONU'],
    'irnawati170792@gmail.com'        => ['nama'=>'IRNAWATI ASHAR','kec_code'=>'061','kecamatan'=>'DAKO PEMEAN','desa'=>'GALUMPANG'],
    'yuyundiahpratiwi2397@gmail.com'  => ['nama'=>'YUYUN DIAH PRATIWI','kec_code'=>'041','kecamatan'=>'LAMPASIO','desa'=>'OGOMATANANG'],
    'niluhulan@gmail.com'             => ['nama'=>'Ni Luh Ulan Sariani','kec_code'=>'041','kecamatan'=>'LAMPASIO','desa'=>'SIBEA'],
    'dessy261297@gmail.com'           => ['nama'=>'DESSY PUTRI AMALIA','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'BARU'],
    'ikkenvs@gmail.com'               => ['nama'=>'Ikke Nur Vita Sari','kec_code'=>'041','kecamatan'=>'LAMPASIO','desa'=>'LAMPASIO'],
    'nurinsyan14@gmail.com'           => ['nama'=>'NURINSYAN','kec_code'=>'041','kecamatan'=>'LAMPASIO','desa'=>'OYOM'],
    'animariany16@gmail.com'          => ['nama'=>'Mariani','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'TINIGI'],
    'moh.reza992@gmail.com'           => ['nama'=>'MOHAMMAD REZA','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'OGOMOLI'],
    'yunusmuh580@gmail.com'           => ['nama'=>'Muh. Yunus','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'TINIGI'],
    'anif73599@gmail.com'             => ['nama'=>'Pitriani','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'TINIGI'],
    'hasnirizal06@gmail.com'          => ['nama'=>'Hasni Rizal','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'SANDANA'],
    'tutiwinarni628@gmail.com'        => ['nama'=>'Tuti Winarni','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'LAKATAN'],
    'tinatangkuman102@gmail.com'      => ['nama'=>'Hartina','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'BAJUGAN'],
    'lelaalfiana@gmail.com'           => ['nama'=>'Alfiana','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'TINIGI'],
    'iqbalm2410@gmail.com'            => ['nama'=>'Moh Iqbal','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'KALANGKANGAN'],
    'moh.khairaat@gmail.com'          => ['nama'=>'Moh. Mulkan','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'BAJUGAN'],
    'suhradinad@gmail.com'            => ['nama'=>'SUHRADINA','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'TINIGI'],
    'liaa301102@gmail.com'            => ['nama'=>'Lia Andriani','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'LANTAPAN'],
    'indrayaniburhanudin@gmail.com'   => ['nama'=>'Indrayani Burhanudin','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'AUNG'],
    'smia89864@gmail.com'             => ['nama'=>'Salmia','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'KINOPASAN'],
    'srihardiyantii26@gmail.com'      => ['nama'=>'Sri Hardiyanti','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'SANDANA'],
    'aliflibiya254@gmail.com'         => ['nama'=>'Alif Libiya Melani','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'PANASAKAN'],
    '082293jya@gmail.com'             => ['nama'=>'Nurjaya','kec_code'=>'030','kecamatan'=>'DONDO','desa'=>'OGOWELE BUGA'],
    'budimann225@gmail.com'           => ['nama'=>'Budiman','kec_code'=>'030','kecamatan'=>'DONDO','desa'=>'LOBUO'],
    'deviarni01@gmail.com'            => ['nama'=>'Deviana','kec_code'=>'030','kecamatan'=>'DONDO','desa'=>'TINABOGAN'],
    'darachandra2@gmail.com'          => ['nama'=>'Chandra','kec_code'=>'030','kecamatan'=>'DONDO','desa'=>'TINABOGAN'],
    'annisarahma16102002@gmail.com'   => ['nama'=>'Annisa Rahmayanti','kec_code'=>'030','kecamatan'=>'DONDO','desa'=>'MALALA'],
    'melinndaa26@gmail.com'           => ['nama'=>'Melinda','kec_code'=>'030','kecamatan'=>'DONDO','desa'=>'TINABOGAN'],
    'ammargonggol@gmail.com'          => ['nama'=>'Muammar','kec_code'=>'030','kecamatan'=>'DONDO','desa'=>'TINABOGAN'],
    'riswanaswaludin4@gmail.com'      => ['nama'=>'Moh. Riswan Aswaludin','kec_code'=>'030','kecamatan'=>'DONDO','desa'=>'TINABOGAN'],
    'novianivhy34@gmail.com'          => ['nama'=>'Noviani','kec_code'=>'030','kecamatan'=>'DONDO','desa'=>'OGOWELE BUGA'],
    'sittihajarsalam63@gmail.com'     => ['nama'=>'Siti Hajar','kec_code'=>'030','kecamatan'=>'DONDO','desa'=>'ANGGASAN'],
    'susantitolis2018@gmail.com'      => ['nama'=>'Susanti','kec_code'=>'030','kecamatan'=>'DONDO','desa'=>'LAIS'],
    'tritakdir753@gmail.com'          => ['nama'=>'TRI TAKDIR U SYAH','kec_code'=>'030','kecamatan'=>'DONDO','desa'=>'MALOMBA'],
    'puspitasaritolis2020@gmail.com'  => ['nama'=>'Puspita Sari','kec_code'=>'030','kecamatan'=>'DONDO','desa'=>'OGOWELE BUGA'],
    'asdardahyar@gmail.com'           => ['nama'=>'ASDAR','kec_code'=>'031','kecamatan'=>'OGODEIDE','desa'=>'LABUAN LOBO'],
    'nvtasariy@gmail.com'             => ['nama'=>'Novita sari','kec_code'=>'031','kecamatan'=>'OGODEIDE','desa'=>'BILO'],
    'nia.060803@gmail.com'            => ['nama'=>'Asmania','kec_code'=>'031','kecamatan'=>'OGODEIDE','desa'=>'LABUAN LOBO'],
    '2000padlia@gmail.com'            => ['nama'=>'PADLIA','kec_code'=>'031','kecamatan'=>'OGODEIDE','desa'=>'KAMALU'],
    'rhiahijria994@gmail.com'         => ['nama'=>'Fahrin','kec_code'=>'031','kecamatan'=>'OGODEIDE','desa'=>'BILO'],
    'fitryani0102@gmail.com'          => ['nama'=>'Fitriani','kec_code'=>'031','kecamatan'=>'OGODEIDE','desa'=>'BILO'],
    'ibrahimanthonie0707@gmail.com'   => ['nama'=>'Ibrahim anthonie','kec_code'=>'031','kecamatan'=>'OGODEIDE','desa'=>'PULIAS'],
    'fatihfatir97@gmail.com'          => ['nama'=>'Marta','kec_code'=>'031','kecamatan'=>'OGODEIDE','desa'=>'BATUILO'],
    'zeynzacky2023@gmail.com'         => ['nama'=>'MOHAMAD YASIR','kec_code'=>'031','kecamatan'=>'OGODEIDE','desa'=>'BATUILO'],
    'nasriajamrin2002@gmail.com'      => ['nama'=>'Nasria','kec_code'=>'031','kecamatan'=>'OGODEIDE','desa'=>'BILO'],
    'rahmatialukman27@gmail.com'      => ['nama'=>'RAHMATIA','kec_code'=>'031','kecamatan'=>'OGODEIDE','desa'=>'SAMBUJAN'],
    'noviawulandari129@gmail.com'     => ['nama'=>'Novia Edi Wulandari','kec_code'=>'031','kecamatan'=>'OGODEIDE','desa'=>'BUGA'],
    'hasrianihamzah579@gmail.com'     => ['nama'=>'Hasriani','kec_code'=>'010','kecamatan'=>'DAMPAL SELATAN','desa'=>'PADDUMPU'],
    'misnasupriadi935@gmail.com'      => ['nama'=>'MISNA','kec_code'=>'010','kecamatan'=>'DAMPAL SELATAN','desa'=>'DONGKO'],
    'zulkifliusman1997@gmail.com'     => ['nama'=>'ZULKIFLI USMAN','kec_code'=>'010','kecamatan'=>'DAMPAL SELATAN','desa'=>'PALLAKAWE'],
    'sasmita08082004@gmail.com'       => ['nama'=>'Sasmita','kec_code'=>'010','kecamatan'=>'DAMPAL SELATAN','desa'=>'TAMPIALA'],
    'nasirhartati77@gmail.com'        => ['nama'=>'Hartati','kec_code'=>'010','kecamatan'=>'DAMPAL SELATAN','desa'=>'TAMPIALA'],
    'magfira066@gmail.com'            => ['nama'=>'MAGFIRA','kec_code'=>'010','kecamatan'=>'DAMPAL SELATAN','desa'=>'TAMPIALA'],
    'ikamulyady@gmail.com'            => ['nama'=>'Kartika','kec_code'=>'010','kecamatan'=>'DAMPAL SELATAN','desa'=>'SONI'],
    'abbdulsamad61@gmail.com'         => ['nama'=>'Abd Samad','kec_code'=>'010','kecamatan'=>'DAMPAL SELATAN','desa'=>'TAMPIALA'],
    'reniamalia978@gmail.com'         => ['nama'=>'RENI AMALIA','kec_code'=>'010','kecamatan'=>'DAMPAL SELATAN','desa'=>'SONI'],
    'irayuliastari1@gmail.com'        => ['nama'=>'Ira yuliastari','kec_code'=>'010','kecamatan'=>'DAMPAL SELATAN','desa'=>'BANGKIR'],
    'sardianaj@gmail.com'             => ['nama'=>'Sardiana','kec_code'=>'010','kecamatan'=>'DAMPAL SELATAN','desa'=>'PADDUMPU'],
    'damsel.bangkir43@gmail.com'      => ['nama'=>'Rezkiyani yaya','kec_code'=>'010','kecamatan'=>'DAMPAL SELATAN','desa'=>'BANGKIR'],
    'siskaikha310@gmail.com'          => ['nama'=>'Siska','kec_code'=>'010','kecamatan'=>'DAMPAL SELATAN','desa'=>'LEMBA HARAPAN'],
    'riniarditasari18@gmail.com'      => ['nama'=>'Rini Ardita Sari','kec_code'=>'010','kecamatan'=>'DAMPAL SELATAN','desa'=>'BANGKIR'],
    'rahmatiabahri@gmail.com'         => ['nama'=>'Rahmatia','kec_code'=>'010','kecamatan'=>'DAMPAL SELATAN','desa'=>'PUSE'],
    'kadirmankadir3@gmail.com'        => ['nama'=>'Kadirman','kec_code'=>'010','kecamatan'=>'DAMPAL SELATAN','desa'=>'LEMBA HARAPAN'],
    'srisutanti4767@gmail.com'        => ['nama'=>'SRI SUTANTi','kec_code'=>'061','kecamatan'=>'DAKO PEMEAN','desa'=>'KAPAS'],
    'qolbirahmaaulia@gmail.com'       => ['nama'=>'Rahma Aulia','kec_code'=>'061','kecamatan'=>'DAKO PEMEAN','desa'=>'GALUMPANG'],
    'hasrianisri061@gmail.com'        => ['nama'=>'Hasriani','kec_code'=>'010','kecamatan'=>'DAMPAL SELATAN','desa'=>'SONI'],
    'andimariam249@gmail.com'         => ['nama'=>'Andi mariam','kec_code'=>'060','kecamatan'=>'TOLITOLI UTARA','desa'=>'LAKUAN TOLITOLI'],
    'andanghamdan51@gmail.com'        => ['nama'=>'HAMDAN','kec_code'=>'020','kecamatan'=>'DAMPAL UTARA','desa'=>'SIMATANG TANJUNG'],
    'silfaniagus27@gmail.com'         => ['nama'=>'Silfani','kec_code'=>'020','kecamatan'=>'DAMPAL UTARA','desa'=>'OGOTUA'],
    'esiastarita394@gmail.com'        => ['nama'=>'Esi Astarita','kec_code'=>'020','kecamatan'=>'DAMPAL UTARA','desa'=>'BAMBAPULA'],
    'astarita832@gmail.com'           => ['nama'=>'IRANINGSIH','kec_code'=>'020','kecamatan'=>'DAMPAL UTARA','desa'=>'BAMBAPULA'],
    'winisudarmin23@gmail.com'        => ['nama'=>'WINI','kec_code'=>'020','kecamatan'=>'DAMPAL UTARA','desa'=>'OGOTUA'],
    'mokhoramli@gmail.com'            => ['nama'=>'Harmoko','kec_code'=>'030','kecamatan'=>'DONDO','desa'=>'TINABOGAN'],
    'miftahjannah1924@gmail.com'      => ['nama'=>'Miftahul Jannah','kec_code'=>'030','kecamatan'=>'DONDO','desa'=>'MALALA'],
    'dhevyaries07@gmail.com'          => ['nama'=>'Devi afrianingsi','kec_code'=>'030','kecamatan'=>'DONDO','desa'=>'MALOMBA'],
    'miftachulannah@gmail.com'        => ['nama'=>'MIFTAHUL JANNAH','kec_code'=>'030','kecamatan'=>'DONDO','desa'=>'OGOWELE'],
    'tasyi679@gmail.com'              => ['nama'=>'Natasya','kec_code'=>'030','kecamatan'=>'DONDO','desa'=>'OGOWELE BUGA'],
    'lukmanpramuka13@gmail.com'       => ['nama'=>'LUKMAN','kec_code'=>'030','kecamatan'=>'DONDO','desa'=>'MALOMBA'],
    'syafiahfiah232@gmail.com'        => ['nama'=>'Syafiah','kec_code'=>'030','kecamatan'=>'DONDO','desa'=>'MALOMBA'],
    'trisyetrisnawati01@gmail.com'    => ['nama'=>'Trisye Trisnawati','kec_code'=>'030','kecamatan'=>'DONDO','desa'=>'TINABOGAN'],
    'wana061101@gmail.com'            => ['nama'=>'NISWANA','kec_code'=>'030','kecamatan'=>'DONDO','desa'=>'OGOGILI'],
    'yunianang04@gmail.com'           => ['nama'=>'Wahyuni','kec_code'=>'030','kecamatan'=>'DONDO','desa'=>'MALOMBA'],
    'pianoviani2911@gmail.com'        => ['nama'=>'Noviani','kec_code'=>'030','kecamatan'=>'DONDO','desa'=>'TINABOGAN'],
    'bluebubblegum201@gmail.com'      => ['nama'=>'AFIFAH TRI HUMAIRAH','kec_code'=>'031','kecamatan'=>'OGODEIDE','desa'=>'TONDO'],
    'hallowdaya@gmail.com'            => ['nama'=>'Nurhidayah','kec_code'=>'031','kecamatan'=>'OGODEIDE','desa'=>'TONDO'],
    'abjadalam9@gmail.com'            => ['nama'=>'Sudirman','kec_code'=>'031','kecamatan'=>'OGODEIDE','desa'=>'PULIAS'],
    'desiraihana8896@gmail.com'       => ['nama'=>'Desi Raihana','kec_code'=>'041','kecamatan'=>'LAMPASIO','desa'=>'OYOM'],
    'ramlicovid@gmail.com'            => ['nama'=>'RAMLI','kec_code'=>'031','kecamatan'=>'OGODEIDE','desa'=>'KABETAN'],
    'tinaahada@gmail.com'             => ['nama'=>'Siti hartina','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'BARU'],
    'nrfaisah24@gmail.com'            => ['nama'=>'Nur faisah','kec_code'=>'041','kecamatan'=>'LAMPASIO','desa'=>'OYOM'],
    'samrins365@gmail.com'            => ['nama'=>'Samrin','kec_code'=>'032','kecamatan'=>'BASIDONDO','desa'=>'SIBALUTON'],
    'ekarezkiana11@gmail.com'         => ['nama'=>'Eka Rezkiana S. N. Daud','kec_code'=>'032','kecamatan'=>'BASIDONDO','desa'=>'KONKOMOS'],
    'lisstiawati10@gmail.com'         => ['nama'=>'Lis Setyawati','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'GINUNGGUNG'],
    'ikkireskiikkireski199@gmail.com'  => ['nama'=>'RESKI','kec_code'=>'010','kecamatan'=>'DAMPAL SELATAN','desa'=>'PADDUMPU'],
    'irhamdhanial23@gmail.com'        => ['nama'=>'M IRHAM','kec_code'=>'032','kecamatan'=>'BASIDONDO','desa'=>'LABONU'],
    'abdulbasar181010129@gmail.com'   => ['nama'=>'ABDUL BASAR','kec_code'=>'060','kecamatan'=>'TOLITOLI UTARA','desa'=>'SALUMPAGA'],
    'nurinurhasanah46@gmail.com'      => ['nama'=>'Nuri Nurhasanah','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'NALU'],
    'sartika.awaludin@gmail.com'      => ['nama'=>'Sartika','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'KALANGKANGAN'],
    'moh.haris2025@gmail.com'         => ['nama'=>'Moh Haris','kec_code'=>'031','kecamatan'=>'OGODEIDE','desa'=>'MUARA BESAR'],
    'rizalreynaldy.rr@gmail.com'      => ['nama'=>'MOH. RIZAL REYNALDY','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'TUWELEY'],
    'sindy.tolis22@gmail.com'         => ['nama'=>'SINDI AMELIA','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'KALANGKANGAN'],
    'mirzabahtiar87@gmail.com'        => ['nama'=>'MIRZA','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'TUWELEY'],
    'samitmidong@gmail.com'           => ['nama'=>'Samid','kec_code'=>'032','kecamatan'=>'BASIDONDO','desa'=>'BASI'],
    'hasmanman836@gmail.com'          => ['nama'=>'HASMAN','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'TINIGI'],
    'fadeld76@gmail.com'              => ['nama'=>'Moh Fadel','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'NALU'],
    'tolutjayamandiri@gmail.com'      => ['nama'=>'Leni Liana','kec_code'=>'060','kecamatan'=>'TOLITOLI UTARA','desa'=>'SALUMPAGA'],
    'marwarusno2000@gmail.com'        => ['nama'=>'Marwa Rusno','kec_code'=>'060','kecamatan'=>'TOLITOLI UTARA','desa'=>'SALUMPAGA'],
    'sayidatunnisah@gmail.com'        => ['nama'=>'SAYIDATUN NISAH','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'BARU'],
    'eetarf03@gmail.com'              => ['nama'=>'Moh. Etsyakhran','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'LAKATAN'],
    'tahwiltahir@gmail.com'           => ['nama'=>'Tahwil','kec_code'=>'010','kecamatan'=>'DAMPAL SELATAN','desa'=>'SONI'],
    'andisingkarahmadana@gmail.com'   => ['nama'=>'ANDI SINGKA RAHMADANA','kec_code'=>'032','kecamatan'=>'BASIDONDO','desa'=>'KAYULOMPA'],
    'yulianalatape@gmail.com'         => ['nama'=>'Yuliana','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'LALOS'],
    'delilarf3009@gmail.com'          => ['nama'=>'Delila Rizky Fauzia','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'KALANGKANGAN'],
    'zahraanggraini9722@gmail.com'    => ['nama'=>'ZAHRA FIRTSZA ANGGRAINI','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'TUWELEY'],
    'artalitagafur@gmail.com'         => ['nama'=>'Artalita','kec_code'=>'031','kecamatan'=>'OGODEIDE','desa'=>'KAMALU'],
    'setiawanrahmat50509@gmail.com'   => ['nama'=>'RAHMAT SETIAWAN','kec_code'=>'010','kecamatan'=>'DAMPAL SELATAN','desa'=>'MIMBALA'],
    'yunitatolis5@gmail.com'          => ['nama'=>'Yunita Arifin','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'NALU'],
    'pong290597@gmail.com'            => ['nama'=>'EVAYANA','kec_code'=>'060','kecamatan'=>'TOLITOLI UTARA','desa'=>'SALUMPAGA'],
    'ikhsanwiratama2025@gmail.com'    => ['nama'=>'Ikhsan','kec_code'=>'010','kecamatan'=>'DAMPAL SELATAN','desa'=>'MIMBALA'],
    'mohikhwan826@gmail.com'          => ['nama'=>'Moh Ikhwan','kec_code'=>'041','kecamatan'=>'LAMPASIO','desa'=>'OYOM'],
    'pratiwihalil101@gmail.com'       => ['nama'=>'Pratiwi','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'NALU'],
    'ihsan.dumbo11@gmail.com'         => ['nama'=>'Ihsan','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'TINIGI'],
    'jalangkoteibuudin26@gmail.com'   => ['nama'=>'ANDI JUMRIANI','kec_code'=>'041','kecamatan'=>'LAMPASIO','desa'=>'LAMPASIO'],
    'aisyahsah456@gmail.com'          => ['nama'=>'Nur Aisyah','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'BAJUGAN'],
    'kurniatirustam21@gmail.com'      => ['nama'=>'Kurniati','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'KALANGKANGAN'],
    'hafidzahnurul61@gmail.com'       => ['nama'=>'Hafidzah Nurul Millah. Z','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'TUWELEY'],
    'ma147696@gmail.com'              => ['nama'=>'Moh. Agung','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'SANDANA'],
    'ikaingguti@gmail.com'            => ['nama'=>'Nurwahida H Ingguti','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'TUWELEY'],
    'mohhamkadahlan271299@gmail.com'  => ['nama'=>'MOH HAMKA','kec_code'=>'032','kecamatan'=>'BASIDONDO','desa'=>'KAYULOMPA'],
    'efry0222@gmail.com'              => ['nama'=>'Efriwanda','kec_code'=>'060','kecamatan'=>'TOLITOLI UTARA','desa'=>'TIMBOLO'],
    'fadlina.linaa2003@gmail.com'     => ['nama'=>'FADLINA','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'TINIGI'],
    'nahdatulrahman@gmail.com'        => ['nama'=>'Nahdatu Rahma','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'KINOPASAN'],
    'erwinpradistyaa@gmail.com'       => ['nama'=>'Erwin','kec_code'=>'032','kecamatan'=>'BASIDONDO','desa'=>'SIBALUTON'],
    'arumijennaira1341@gmail.com'     => ['nama'=>'Mega Alvionita','kec_code'=>'041','kecamatan'=>'LAMPASIO','desa'=>'OGOMATANANG'],
    'miftahuljanna.mjii@gmail.com'    => ['nama'=>'Miftahul Janna','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'KALANGKANGAN'],
    'dindaaprilya222@gmail.com'       => ['nama'=>'Dinda Aprilya','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'OGOMOLI'],
    'ernihasbi56@gmail.com'           => ['nama'=>'ERNY','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'BARU'],
    'viivikusuma@gmail.com'           => ['nama'=>'Silvie Sri Kusuma','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'PANASAKAN'],
    'fitriwahyuningsih116@gmail.com'  => ['nama'=>'Fitri Wahyu Ningsih','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'BARU'],
    'muhainiyusuf@gmail.com'          => ['nama'=>'MUHAINI YUSUF','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'KINOPASAN'],
    'ammankmuhsalman@gmail.com'       => ['nama'=>'Muhammad salman','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'BUNTUNA'],
    'intanprdtha24@gmail.com'         => ['nama'=>'Intan Paraditha','kec_code'=>'032','kecamatan'=>'BASIDONDO','desa'=>'KAYULOMPA'],
    'fajaraswah53@gmail.com'          => ['nama'=>'MUH FAJAR ASWAH','kec_code'=>'041','kecamatan'=>'LAMPASIO','desa'=>'MULIASARI'],
    'ririnmarliana1430@gmail.com'     => ['nama'=>'Ririn marliana','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'TAMBUN'],
    'adfadilah06@gmail.com'           => ['nama'=>'Fadila','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'TAMBUN'],
    'safiraramadani1999@gmail.com'    => ['nama'=>'Sapira Ramadani','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'BARU'],
    'alwizihab285@gmail.com'          => ['nama'=>'ALWI','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'GINUNGGUNG'],
    'nandapade@icloud.com'            => ['nama'=>'Albar Sukri','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'GINUNGGUNG'],
    'rinipntoh@gmail.com'             => ['nama'=>'Rini.s','kec_code'=>'060','kecamatan'=>'TOLITOLI UTARA','desa'=>'DIULE'],
    'vhiasilvia2020@gmail.com'        => ['nama'=>'Vina silvia','kec_code'=>'060','kecamatan'=>'TOLITOLI UTARA','desa'=>'BINONTOAN'],
    'shajaredwin@gmail.com'           => ['nama'=>'Sitti Hajar E. Dt. Amas','kec_code'=>'060','kecamatan'=>'TOLITOLI UTARA','desa'=>'BINONTOAN'],
    'tampubolonsahala49@gmail.com'    => ['nama'=>'SAHALA TAMPUBOLON','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'TUWELEY'],
    'niskayhabu@gmail.com'            => ['nama'=>'Niska Y. Habu','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'AUNG'],
    'wahyuliadjmallo@gmail.com'       => ['nama'=>'WAHYULIA','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'TUWELEY'],
    'mawar191291@gmail.com'           => ['nama'=>'Mawar','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'BAJUGAN'],
    'iketutpujaastawadiputra@gmail.com' => ['nama'=>'I ketut puja astawa diputra','kec_code'=>'041','kecamatan'=>'LAMPASIO','desa'=>'SIBEA'],
    'lisaxnx20@gmail.com'             => ['nama'=>'Sitti Nurhaliza','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'BARU'],
    'andinipontoh1@gmail.com'         => ['nama'=>'ANDINI S A PONTOH','kec_code'=>'060','kecamatan'=>'TOLITOLI UTARA','desa'=>'DIULE'],
    'nurlindahramadhanyy06@gmail.com' => ['nama'=>'Nurlindah Ramadhany','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'BARU'],
    'sitierlinaarahmanerlina@gmail.com' => ['nama'=>'Siti Erlin A Rahman','kec_code'=>'060','kecamatan'=>'TOLITOLI UTARA','desa'=>'LAKUAN TOLITOLI'],
    'gamainumaki@gmail.com'           => ['nama'=>'Ananda zesakarti pramana putra','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'PANASAKAN'],
    'jusmahwati08@gmail.com'          => ['nama'=>'Jusmahwati','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'TUWELEY'],
    'indarwandi937@gmail.com'         => ['nama'=>'Indarwati','kec_code'=>'010','kecamatan'=>'DAMPAL SELATAN','desa'=>'BANGKIR'],
    'musliaditolis@gmail.com'         => ['nama'=>'Sitti kurnia','kec_code'=>'041','kecamatan'=>'LAMPASIO','desa'=>'SIBEA'],
    'agustinaxx02@gmail.com'          => ['nama'=>'Agustina','kec_code'=>'010','kecamatan'=>'DAMPAL SELATAN','desa'=>'DONGKO'],
    'nurhasidahs125@gmail.com'        => ['nama'=>'Nurhasida HS','kec_code'=>'041','kecamatan'=>'LAMPASIO','desa'=>'LAMPASIO'],
    'sritutialawia@gmail.com'         => ['nama'=>'SRITUTI ALAWIA','kec_code'=>'060','kecamatan'=>'TOLITOLI UTARA','desa'=>'LAULALANG'],
    'nurnawiraazzhara@gmail.com'      => ['nama'=>'Nur Nawira Azzhara','kec_code'=>'010','kecamatan'=>'DAMPAL SELATAN','desa'=>'DONGKO'],
    'idjafar095@gmail.com'            => ['nama'=>'MUHAMMAD NURDIANSYAH S DJAFAR','kec_code'=>'060','kecamatan'=>'TOLITOLI UTARA','desa'=>'GIO'],
    'djumaopall@gmail.com'            => ['nama'=>'ALHAMDANI','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'TUWELEY'],
    'nafilatulyulmaz@gmail.com'       => ['nama'=>'YULIANA','kec_code'=>'060','kecamatan'=>'TOLITOLI UTARA','desa'=>'PINJAN'],
    'menni010429@gmail.com'           => ['nama'=>'Sumarni','kec_code'=>'050','kecamatan'=>'GALANG','desa'=>'OGOMOLI'],
    'sridevilula991115@gmail.com'     => ['nama'=>'Sri devi','kec_code'=>'041','kecamatan'=>'LAMPASIO','desa'=>'OYOM'],
    'mohtaufanalfareza@gmail.com'     => ['nama'=>'Muh. Taufan Al Fareza','kec_code'=>'040','kecamatan'=>'BAOLAN','desa'=>'BARU'],
];

// Helper: ambil nama dari email (case-insensitive)
$getNama = fn($email) => ($emailData[strtolower($email)]['nama'] ?? $email);

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
    COALESCE(SUM(edited_by_pengawas), 0)                           AS total_edited_pengawas,
    COALESCE(SUM(edited_by_admin_kabupaten), 0)                    AS total_edited_admin,
    COUNT(DISTINCT email)                                          AS total_petugas,
    COUNT(*)                                                       AS total_sls
FROM sensus_ekonomi $kecWhere")->fetch_assoc();

// Waktu update data terakhir
$lastUpdateRow = $conn->query("SELECT MAX(GREATEST(created_at, COALESCE(updated_at, '2000-01-01'))) AS last_update FROM sensus_ekonomi")->fetch_assoc();
$lastUpdate = $lastUpdateRow['last_update'];
$lastUpdateStr = $lastUpdate ? date('d M Y H:i', strtotime($lastUpdate)) . ' WIB' : null;

$totalApprovedCombined = (int)$stats['total_approved'] + (int)$stats['total_edited_pengawas'] + (int)$stats['total_edited_admin'];
$globalDenominator = (int)$stats['total_open'] + (int)$stats['total_draft']
                   + (int)$stats['total_submitted'] + (int)$stats['total_rejected']
                   + $totalApprovedCombined + (int)$stats['total_revoke'];
$globalNumerator   = (int)$stats['total_submitted'] + (int)$stats['total_rejected']
                   + $totalApprovedCombined + (int)$stats['total_revoke'];
$globalPct = $globalDenominator > 0
    ? number_format($globalNumerator / $globalDenominator * 100, 2)
    : '0.00';

// Ambil data DB per email (tanpa filter kec — nanti dimerge dengan filter xlsx)
$dbRows = $conn->query("SELECT
    email,
    COALESCE(SUM(open_count), 0)            AS total_open,
    COALESCE(SUM(draft), 0)                 AS total_draft,
    COALESCE(SUM(submitted_by_pencacah), 0) AS submitted_pencacah,
    COALESCE(SUM(submitted_respondent), 0)  AS submitted_resp,
    COALESCE(SUM(submitted_by_pencacah), 0) AS total_submitted,
    COALESCE(SUM(rejected), 0)              AS total_rejected,
    COALESCE(SUM(approved), 0)              AS total_approved,
    COALESCE(SUM(`revoke`), 0)              AS total_revoke,
    COALESCE(SUM(edited_by_pengawas), 0)    AS total_edited_pengawas,
    COALESCE(SUM(edited_by_admin_kabupaten), 0) AS total_edited_admin,
    COUNT(*)                                AS total_sls
FROM sensus_ekonomi $kecWhere
GROUP BY email")->fetch_all(MYSQLI_ASSOC);

// Buat map: email (lowercase) => data DB
$dbMap = [];
foreach ($dbRows as $row) {
    $dbMap[strtolower($row['email'])] = $row;
}

// Filter emailData berdasarkan kecamatan jika ada filter
$xlsxFiltered = $kecFilter !== 'all'
    ? array_filter($emailData, fn($d) => $d['kec_code'] === $kecFilter)
    : $emailData;

// Merge: semua petugas dari xlsx dengan data DB (0 jika belum ada data)
$perPetugas = [];
foreach ($xlsxFiltered as $email => $info) {
    $db = $dbMap[$email] ?? [];
    $perPetugas[] = [
        'email'              => $email,
        'nama'               => $info['nama'],
        'kecamatan'          => $info['kecamatan'],
        'desa'               => $info['desa'],
        'kec_code'           => $info['kec_code'],
        'total_open'         => (int)($db['total_open']        ?? 0),
        'total_draft'        => (int)($db['total_draft']       ?? 0),
        'submitted_pencacah' => (int)($db['submitted_pencacah']?? 0),
        'submitted_resp'     => (int)($db['submitted_resp']    ?? 0),
        'total_submitted'       => (int)($db['total_submitted']        ?? 0),
        'total_rejected'        => (int)($db['total_rejected']         ?? 0),
        'total_approved'        => (int)($db['total_approved']         ?? 0),
        'total_revoke'          => (int)($db['total_revoke']           ?? 0),
        'total_edited_pengawas' => (int)($db['total_edited_pengawas']  ?? 0),
        'total_edited_admin'    => (int)($db['total_edited_admin']     ?? 0),
        'total_sls'             => (int)($db['total_sls']              ?? 0),
    ];
}
usort($perPetugas, fn($a, $b) =>
    ($b['total_submitted'] + $b['total_open'] + $b['total_draft']) <=>
    ($a['total_submitted'] + $a['total_open'] + $a['total_draft'])
);

$perKec = $conn->query("SELECT
    SUBSTRING(sls_code, 5, 3) AS kec_code,
    COALESCE(SUM(open_count), 0)                        AS total_open,
    COALESCE(SUM(draft), 0)                             AS total_draft,
    COALESCE(SUM(submitted_by_pencacah), 0)             AS total_submitted,
    COALESCE(SUM(rejected), 0)                          AS total_rejected,
    COALESCE(SUM(approved), 0)                          AS total_approved,
    COALESCE(SUM(`revoke`), 0)                          AS total_revoke,
    COALESCE(SUM(edited_by_pengawas), 0)                AS total_edited_pengawas,
    COALESCE(SUM(edited_by_admin_kabupaten), 0)         AS total_edited_admin,
    COUNT(DISTINCT email)                               AS total_petugas,
    COUNT(*)                                            AS total_sls
FROM sensus_ekonomi
GROUP BY SUBSTRING(sls_code, 5, 3)
ORDER BY kec_code")->fetch_all(MYSQLI_ASSOC);

// Chart: hanya petugas dengan data di DB
$perPetugasChart = array_values(array_filter($perPetugas, fn($p) => $p['total_sls'] > 0));

$chartLabels    = json_encode(array_map(fn($p) => explode(' ', $p['nama'])[0], $perPetugasChart));
$chartOpen      = json_encode(array_map(fn($p) => $p['total_open'],        $perPetugasChart));
$chartDraft     = json_encode(array_map(fn($p) => $p['total_draft'],       $perPetugasChart));
$chartSubmitted = json_encode(array_map(fn($p) => $p['total_submitted'],   $perPetugasChart));
$chartRejected  = json_encode(array_map(fn($p) => $p['total_rejected'],    $perPetugasChart));
$chartApproved  = json_encode(array_map(fn($p) => $p['total_approved'] + $p['total_edited_pengawas'] + $p['total_edited_admin'], $perPetugasChart));
$chartRevoke    = json_encode(array_map(fn($p) => $p['total_revoke'],      $perPetugasChart));
$barWidth = max(500, count($perPetugasChart) * 48);
$doughnutData   = json_encode([
    (int)$stats['total_open'],
    (int)$stats['total_draft'],
    (int)$stats['total_submitted'],
    (int)$stats['total_rejected'],
    $totalApprovedCombined,
    (int)$stats['total_revoke'],
]);

// ── Grafik Target vs Realisasi per Kecamatan ──────────────────────────────
$startDate  = new DateTime('2026-06-15');
$today      = new DateTime('today');
$hariKe     = max(1, (int)$today->diff($startDate)->days + 1);

$petugasPerKec = [];
foreach ($emailData as $email => $info) {
    $kc = $info['kec_code'];
    $petugasPerKec[$kc] = ($petugasPerKec[$kc] ?? 0) + 1;
}

$realisasiRows = $conn->query("SELECT
    SUBSTRING(sls_code,5,3) AS kec_code,
    COALESCE(SUM(submitted_by_pencacah + rejected + approved + `revoke` + edited_by_pengawas + edited_by_admin_kabupaten), 0) AS realisasi
FROM sensus_ekonomi
GROUP BY SUBSTRING(sls_code,5,3)")->fetch_all(MYSQLI_ASSOC);
$realisasiByKec = [];
foreach ($realisasiRows as $row) {
    $realisasiByKec[$row['kec_code']] = (int)$row['realisasi'];
}

$kecChartLabels    = [];
$kecChartTarget    = [];
$kecChartRealisasi = [];
$kecChartPetugas   = [];
foreach ($kecNama as $kc => $nm) {
    $n = $petugasPerKec[$kc] ?? 0;
    $kecChartLabels[]    = $nm;
    $kecChartPetugas[]   = $n;
    $kecChartTarget[]    = $n * 6 * $hariKe;
    $kecChartRealisasi[] = $realisasiByKec[$kc] ?? 0;
}
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
                                <div class="fs-2 fw-bold lh-1"><?= number_format($totalApprovedCombined) ?></div>
                                <div class="text-muted small">Approved</div>
                                <div style="font-size:.65rem;color:#8b5cf6"><?= number_format((int)$stats['total_approved']) ?> + <?= number_format((int)$stats['total_edited_pengawas']) ?> + <?= number_format((int)$stats['total_edited_admin']) ?></div>
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
                    <small class="text-muted"><?= number_format($globalNumerator) ?> dari <?= number_format($globalDenominator) ?> dokumen sudah diproses (submit + reject + approve + edit pengawas + edit admin kab + revoke)</small>
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
                            <canvas id="barChart" style="min-width:<?= $barWidth ?>px; height:200px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Grafik Target vs Realisasi per Kecamatan ─────── -->
            <div class="card stat-card p-3 mb-4">
                <div class="section-head mb-2">
                    <i class="bi bi-bullseye me-1"></i>Target vs Realisasi per Kecamatan
                    <span class="text-muted fw-normal ms-2" style="font-size:.75rem;">
                        — Hari ke-<strong><?= $hariKe ?></strong> sejak 15 Jun 2026 &nbsp;·&nbsp; Target = petugas × 6 dok/hari
                    </span>
                </div>
                <div class="row g-3 align-items-start">
                    <div class="col-lg-7">
                        <canvas id="kecTargetChart" style="height:200px;"></canvas>
                    </div>
                    <div class="col-lg-5">
                        <table class="table table-sm align-middle mb-0" style="font-size:.78rem;">
                            <thead style="background:#f8fafc; color:#64748b; font-size:.68rem; text-transform:uppercase;">
                                <tr>
                                    <th class="ps-1">Kecamatan</th>
                                    <th class="text-center">Target</th>
                                    <th class="text-center">Real.</th>
                                    <th style="min-width:80px">Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($kecNama as $kc => $nm):
                                $n   = $petugasPerKec[$kc] ?? 0;
                                $tgt = $n * 6 * $hariKe;
                                $rl  = $realisasiByKec[$kc] ?? 0;
                                $pct = $tgt > 0 ? min(100, round($rl / $tgt * 100)) : 0;
                                $barColor = $pct >= 100 ? '#4ade80' : ($pct >= 70 ? '#facc15' : '#f87171');
                            ?>
                            <tr>
                                <td class="ps-1 fw-semibold" style="max-width:90px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($nm) ?></td>
                                <td class="text-center" style="color:#f79039"><?= number_format($tgt) ?></td>
                                <td class="text-center fw-bold" style="color:#166534"><?= number_format($rl) ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <div style="flex:1; background:#e9ecef; border-radius:4px; height:5px; overflow:hidden;">
                                            <div style="width:<?= $pct ?>%; height:100%; background:<?= $barColor ?>; border-radius:4px;"></div>
                                        </div>
                                        <span style="font-size:.7rem; min-width:28px; text-align:right; color:<?= $barColor ?>"><?= $pct ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
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
                                        <th>Kecamatan</th>
                                        <th>Desa</th>
                                        <th>Nama Petugas</th>
                                        <th>Email</th>
                                        <th class="text-center">SLS</th>
                                        <th class="text-center">Open</th>
                                        <th class="text-center">Draft</th>
                                        <th class="text-center">Submitted<br><small style="font-weight:400;font-size:.72rem;">Pencacah</small></th>
                                        <th class="text-center">Submitted<br><small style="font-weight:400;font-size:.72rem;">Respondent</small></th>
                                        <th class="text-center">Rejected</th>
                                        <th class="text-center">Approved</th>
                                        <th class="text-center">Revoke</th>
                                        <th class="text-center">Edited<br><small style="font-weight:400;font-size:.72rem;">Pengawas</small></th>
                                        <th class="text-center">Edited<br><small style="font-weight:400;font-size:.72rem;">Admin Kab</small></th>
                                        <th class="text-center">Total</th>
                                        <th style="min-width:100px">Progress</th>
                                        <th class="text-center">% Approve</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($perPetugas)): ?>
                                    <tr><td colspan="18" class="text-center text-muted py-4">Belum ada data. <a href="data_sensus_ekonomi.php">Import data?</a></td></tr>
                                <?php else: ?>
                                <?php foreach ($perPetugas as $i => $p): ?>
                                    <?php
                                    $total = $p['total_open'] + $p['total_draft']
                                           + $p['total_submitted'] + $p['total_rejected']
                                           + $p['total_approved'] + $p['total_revoke']
                                           + $p['total_edited_pengawas'] + $p['total_edited_admin'];
                                    $rowStyle = $total === 0 ? ' class="text-muted"' : '';
                                    $pNumerator   = $p['total_submitted'] + $p['total_approved'] + $p['total_revoke']
                                                  + $p['total_rejected'] + $p['total_edited_pengawas'] + $p['total_edited_admin'];
                                    $pDenominator = $pNumerator + $p['total_open'] + $p['total_draft'];
                                    $pPct = $pDenominator > 0
                                        ? number_format($pNumerator / $pDenominator * 100, 1)
                                        : '0.0';
                                    $pBarColor  = (float)$pPct >= 100 ? '#4ade80' : ((float)$pPct >= 70 ? '#facc15' : '#f87171');
                                    $pctApprove = $pDenominator > 0
                                        ? number_format(($p['total_approved'] + $p['total_edited_pengawas'] + $p['total_edited_admin']) / $pDenominator * 100, 1)
                                        : '0.0';
                                    ?>
                                    <tr<?= $rowStyle ?>>
                                        <td class="ps-4 text-muted small"><?= $i + 1 ?></td>
                                        <td class="small fw-semibold"><?= htmlspecialchars($p['kecamatan']) ?></td>
                                        <td class="small"><?= htmlspecialchars($p['desa']) ?></td>
                                        <td class="fw-semibold small"><?= htmlspecialchars($p['nama']) ?></td>
                                        <td class="text-muted" style="font-size:.72rem;"><?= htmlspecialchars($p['email']) ?></td>
                                        <td class="text-center"><span class="badge" style="background:#e0e7ff;color:#3730a3"><?= $p['total_sls'] ?></span></td>
                                        <td class="text-center"><span class="badge badge-open fw-semibold"><?= number_format($p['total_open']) ?></span></td>
                                        <td class="text-center"><span class="badge badge-draft fw-semibold"><?= number_format($p['total_draft']) ?></span></td>
                                        <td class="text-center"><span class="badge badge-sub fw-semibold"><?= number_format($p['submitted_pencacah']) ?></span></td>
                                        <td class="text-center"><span class="badge badge-sub fw-semibold"><?= number_format($p['submitted_resp']) ?></span></td>
                                        <td class="text-center"><span class="badge badge-rej fw-semibold"><?= number_format($p['total_rejected']) ?></span></td>
                                        <td class="text-center"><span class="badge badge-app fw-semibold"><?= number_format($p['total_approved']) ?></span></td>
                                        <td class="text-center"><span class="badge badge-rev fw-semibold"><?= number_format($p['total_revoke']) ?></span></td>
                                        <td class="text-center"><span class="badge fw-semibold" style="background:#d1fae5;color:#065f46"><?= number_format($p['total_edited_pengawas']) ?></span></td>
                                        <td class="text-center"><span class="badge fw-semibold" style="background:#e0e7ff;color:#3730a3"><?= number_format($p['total_edited_admin']) ?></span></td>
                                        <td class="text-center fw-bold text-muted"><?= number_format($total) ?></td>
                                        <td style="min-width:100px;">
                                            <div class="d-flex align-items-center gap-1">
                                                <div style="flex:1; background:#e9ecef; border-radius:4px; height:5px; overflow:hidden;">
                                                    <div style="width:<?= min(100, (float)$pPct) ?>%; height:100%; background:<?= $pBarColor ?>; border-radius:4px;"></div>
                                                </div>
                                                <span style="font-size:.7rem; min-width:34px; text-align:right; color:<?= $pBarColor ?>"><?= $pPct ?>%</span>
                                            </div>
                                        </td>
                                        <td class="text-center fw-semibold" style="font-size:.8rem; color:#5b21b6"><?= $pctApprove ?>%</td>
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
                                        <th class="text-center">Edit Pengawas</th>
                                        <th class="text-center">Edit Admin Kab</th>
                                        <th class="text-center">Progress</th>
                                        <th class="text-center">% Approve</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($perKec)): ?>
                                    <tr><td colspan="13" class="text-center text-muted py-4">Belum ada data.</td></tr>
                                <?php else: ?>
                                <?php foreach ($perKec as $kec): ?>
                                    <?php
                                    $kApprovedCombined = (int)$kec['total_approved'] + (int)$kec['total_edited_pengawas'] + (int)$kec['total_edited_admin'];
                                    $kDenominator = (int)$kec['total_open'] + (int)$kec['total_draft']
                                                  + (int)$kec['total_submitted'] + (int)$kec['total_rejected']
                                                  + $kApprovedCombined + (int)$kec['total_revoke'];
                                    $kNumerator   = (int)$kec['total_submitted'] + (int)$kec['total_rejected']
                                                  + $kApprovedCombined + (int)$kec['total_revoke'];
                                    $pct = $kDenominator > 0
                                        ? number_format($kNumerator / $kDenominator * 100, 2)
                                        : '0.00';
                                    $kPctApprove = $kDenominator > 0
                                        ? number_format($kApprovedCombined / $kDenominator * 100, 1)
                                        : '0.0';
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
                                        <td class="text-center"><span class="badge badge-app"><?= number_format($kApprovedCombined) ?></span></td>
                                        <td class="text-center"><span class="badge" style="background:#d1fae5;color:#065f46"><?= number_format((int)$kec['total_edited_pengawas']) ?></span></td>
                                        <td class="text-center"><span class="badge" style="background:#e0e7ff;color:#3730a3"><?= number_format((int)$kec['total_edited_admin']) ?></span></td>
                                        <td style="min-width:120px;">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height:6px">
                                                    <div class="progress-bar progress-accent" style="width:<?= $pct ?>%; border-radius:6px;"></div>
                                                </div>
                                                <small class="text-muted" style="white-space:nowrap"><?= $pct ?>%</small>
                                            </div>
                                        </td>
                                        <td class="text-center fw-semibold" style="font-size:.8rem; color:#5b21b6"><?= $kPctApprove ?>%</td>
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
                                        <th class="text-center">Edit Pengawas</th>
                                        <th class="text-center">Edit Admin Kab</th>
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
                                    open_count, draft, submitted_by_pencacah, submitted_respondent, rejected, approved, `revoke`,
                                    edited_by_pengawas, edited_by_admin_kabupaten
                                FROM sensus_ekonomi $kecWhere
                                ORDER BY kec_code, sls_code, email
                                LIMIT $perPage OFFSET $offset")->fetch_all(MYSQLI_ASSOC);
                                ?>
                                <?php if (empty($slsRows)): ?>
                                    <tr><td colspan="12" class="text-center text-muted py-4">Belum ada data.</td></tr>
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
                                        <td class="text-center"><span class="badge" style="background:#d1fae5;color:#065f46"><?= $r['edited_by_pengawas'] ?? 0 ?></span></td>
                                        <td class="text-center"><span class="badge" style="background:#e0e7ff;color:#3730a3"><?= $r['edited_by_admin_kabupaten'] ?? 0 ?></span></td>
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

// ── Target vs Realisasi per Kecamatan ────────────────
const kecLabels    = <?= json_encode($kecChartLabels) ?>;
const kecTarget    = <?= json_encode($kecChartTarget) ?>;
const kecRealisasi = <?= json_encode($kecChartRealisasi) ?>;
const kecPetugas   = <?= json_encode($kecChartPetugas) ?>;
const hariKe       = <?= $hariKe ?>;

new Chart(document.getElementById('kecTargetChart'), {
    data: {
        labels: kecLabels,
        datasets: [
            {
                type: 'bar',
                label: 'Realisasi',
                data: kecRealisasi,
                backgroundColor: kecRealisasi.map((v, i) => v >= kecTarget[i] ? '#4ade80' : v >= kecTarget[i] * 0.7 ? '#86efac' : '#bbf7d0'),
                borderColor:     kecRealisasi.map((v, i) => v >= kecTarget[i] ? '#16a34a' : '#4ade80'),
                borderWidth: 1,
                borderRadius: 4,
                order: 2,
            },
            {
                type: 'line',
                label: 'Target (hari ke-' + hariKe + ')',
                data: kecTarget,
                borderColor: '#f79039',
                backgroundColor: 'rgba(247,144,57,.08)',
                borderWidth: 2.5,
                pointBackgroundColor: '#f79039',
                pointRadius: 5,
                pointHoverRadius: 7,
                tension: 0.3,
                fill: false,
                order: 1,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index' },
        plugins: {
            legend: { position: 'top', labels: { boxWidth: 14, padding: 14 } },
            tooltip: {
                callbacks: {
                    afterBody(items) {
                        const i = items[0].dataIndex;
                        const pct = kecTarget[i] > 0
                            ? Math.min(100, Math.round(kecRealisasi[i] / kecTarget[i] * 100))
                            : 0;
                        return ['Petugas: ' + kecPetugas[i], 'Pencapaian: ' + pct + '%'];
                    }
                }
            }
        },
        scales: {
            x: { ticks: { font: { size: 11 } } },
            y: {
                beginAtZero: true,
                ticks: { font: { size: 11 } },
                title: { display: true, text: 'Jumlah Dokumen', font: { size: 11 } }
            }
        },
        animation: { duration: 700 },
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
