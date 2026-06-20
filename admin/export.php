<?php
require_once 'config.php';
requireSuperAdmin();

$search    = trim($_GET['search'] ?? '');
$filter    = $_GET['filter'] ?? 'semua';
$pesertaId = (int)($_GET['peserta_id'] ?? 0);

$where = "WHERE p.status_seleksi = 'Diterima' AND w.id IS NOT NULL";
if ($pesertaId > 0)
    $where .= " AND p.id = $pesertaId";
if ($search !== '') {
    $s = $conn->real_escape_string('%' . $search . '%');
    $where .= " AND (p.nama LIKE '$s' OR p.no_telp LIKE '$s')";
}
if (in_array($filter, ['A','B','C','D','E']))
    $where .= " AND w.predikat = '" . $conn->real_escape_string($filter) . "'";

$sql = "SELECT p.nama, p.jenis_kelamin, p.pendidikan, p.pekerjaan,
               p.no_telp, p.email, p.posisi_daftar, p.alamat_kec,
               w.kepemilikan_motor, w.kemampuan_berkendara, w.pernah_capi,
               w.merk_hp, w.tipe_hp, w.ram_hp,
               w.jawab_perkenalan, w.jawab_pengetahuan_bps, w.jawab_alasan_daftar,
               w.pernah_pendataan, w.jawab_evaluasi,
               w.jawab_penolak, w.jawab_konflik, w.jawab_manajemen_waktu,
               w.status_asn, w.status_kontrak, w.status_hamil,
               w.punya_balita, w.keterangan_balita, w.pasangan_daftar,
               w.penyakit_kronis, w.keterangan_penyakit,
               w.bersedia_kec_lain, w.bersedia_tanggung_jawab,
               w.bersedia_validasi, w.bersedia_bayar_clean,
               w.nilai_komunikasi, w.nilai_penampilan, w.nilai_analisa, w.nilai,
               w.catatan, w.predikat,
               a.nama AS pewawancara, w.created_at
        FROM peserta p
        JOIN wawancara w ON w.id = (
            SELECT id FROM wawancara WHERE peserta_id = p.id ORDER BY created_at DESC LIMIT 1
        )
        JOIN admin_seleksi a ON a.id = w.admin_id
        $where ORDER BY w.nilai DESC, p.nama ASC";

$result = $conn->query($sql);
$rows   = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$filename = $pesertaId > 0
    ? 'Wawancara_' . $pesertaId . '_' . date('Ymd') . '.xls'
    : 'Rekap_Wawancara_SE2026_' . date('Ymd_His') . '.xls';

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

/* ── XML cell helpers ── */
function xc($val, $style = 'wrap') {
    $v = htmlspecialchars((string)($val ?? ''));
    return "<Cell ss:StyleID=\"{$style}\"><Data ss:Type=\"String\">{$v}</Data></Cell>\n";
}
function xcn($val, $style = 'center') {
    return "<Cell ss:StyleID=\"{$style}\"><Data ss:Type=\"Number\">" . (int)$val . "</Data></Cell>\n";
}
function xcy($val) {
    $v = (string)($val ?? '');
    if ($v === 'Ya')    return xc('Ya',    'cell_ya');
    if ($v === 'Tidak') return xc('Tidak', 'cell_tdk');
    return xc($v, 'center');
}
function xcpred($val) {
    static $map = ['A'=>'pred_A','B'=>'pred_B','C'=>'pred_C','D'=>'pred_D','E'=>'pred_E'];
    return xc((string)($val ?? ''), $map[$val] ?? 'center');
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:x="urn:schemas-microsoft-com:office:excel">
 <Styles>
  <Style ss:ID="sec_no">
   <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="9"/>
   <Interior ss:Color="#374151" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="sec_peserta">
   <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="9"/>
   <Interior ss:Color="#1e40af" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="sec_b1">
   <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="9"/>
   <Interior ss:Color="#0369a1" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="sec_b2u">
   <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="9"/>
   <Interior ss:Color="#6d28d9" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="sec_b2y">
   <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="9"/>
   <Interior ss:Color="#5b21b6" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="sec_b4">
   <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="9"/>
   <Interior ss:Color="#b45309" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="sec_b5">
   <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="9"/>
   <Interior ss:Color="#166534" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="sec_meta">
   <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="9"/>
   <Interior ss:Color="#374151" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="header">
   <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="9"/>
   <Interior ss:Color="#F79039" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#E06820"/>
   </Borders>
  </Style>
  <Style ss:ID="center">
   <Alignment ss:Horizontal="Center" ss:Vertical="Top"/>
   <Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders>
  </Style>
  <Style ss:ID="wrap">
   <Alignment ss:WrapText="1" ss:Vertical="Top"/>
   <Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders>
  </Style>
  <Style ss:ID="alt">
   <Interior ss:Color="#FFF8F0" ss:Pattern="Solid"/>
   <Alignment ss:WrapText="1" ss:Vertical="Top"/>
   <Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders>
  </Style>
  <Style ss:ID="altc">
   <Interior ss:Color="#FFF8F0" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Top"/>
   <Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders>
  </Style>
  <Style ss:ID="nilai">
   <Font ss:Bold="1" ss:Size="11"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders>
  </Style>
  <Style ss:ID="cell_ya">
   <Font ss:Bold="1" ss:Color="#166534"/>
   <Interior ss:Color="#DCFCE7" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Top"/>
   <Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders>
  </Style>
  <Style ss:ID="cell_tdk">
   <Font ss:Bold="1" ss:Color="#991B1B"/>
   <Interior ss:Color="#FEE2E2" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Top"/>
   <Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders>
  </Style>
  <Style ss:ID="pred_A">
   <Font ss:Bold="1" ss:Color="#166534"/>
   <Interior ss:Color="#DCFCE7" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders>
  </Style>
  <Style ss:ID="pred_B">
   <Font ss:Bold="1" ss:Color="#1E3A8A"/>
   <Interior ss:Color="#DBEAFE" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders>
  </Style>
  <Style ss:ID="pred_C">
   <Font ss:Bold="1" ss:Color="#92400E"/>
   <Interior ss:Color="#FEF9C3" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders>
  </Style>
  <Style ss:ID="pred_D">
   <Font ss:Bold="1" ss:Color="#9A3412"/>
   <Interior ss:Color="#FFEDD5" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders>
  </Style>
  <Style ss:ID="pred_E">
   <Font ss:Bold="1" ss:Color="#991B1B"/>
   <Interior ss:Color="#FEE2E2" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders>
  </Style>
 </Styles>
 <Worksheet ss:Name="Rekap Wawancara">
  <Table>
   <!-- 43 columns total:
        1=No | 2-9=Peserta(8) | 10-15=BlokI(6) | 16-23=BlokII Uraian(8) |
        24-35=BlokII YaTdk(12) | 36-39=BlokIV(4) | 40-41=BlokV(2) | 42-43=Meta(2) -->
   <Column ss:Width="30"/>
   <Column ss:Width="150"/>
   <Column ss:Width="35"/>
   <Column ss:Width="90"/>
   <Column ss:Width="100"/>
   <Column ss:Width="90"/>
   <Column ss:Width="140"/>
   <Column ss:Width="120"/>
   <Column ss:Width="85"/>
   <Column ss:Width="70"/>
   <Column ss:Width="80"/>
   <Column ss:Width="65"/>
   <Column ss:Width="80"/>
   <Column ss:Width="90"/>
   <Column ss:Width="60"/>
   <Column ss:Width="200"/>
   <Column ss:Width="200"/>
   <Column ss:Width="200"/>
   <Column ss:Width="75"/>
   <Column ss:Width="200"/>
   <Column ss:Width="200"/>
   <Column ss:Width="200"/>
   <Column ss:Width="200"/>
   <Column ss:Width="70"/>
   <Column ss:Width="70"/>
   <Column ss:Width="65"/>
   <Column ss:Width="65"/>
   <Column ss:Width="160"/>
   <Column ss:Width="70"/>
   <Column ss:Width="70"/>
   <Column ss:Width="160"/>
   <Column ss:Width="70"/>
   <Column ss:Width="70"/>
   <Column ss:Width="70"/>
   <Column ss:Width="70"/>
   <Column ss:Width="75"/>
   <Column ss:Width="75"/>
   <Column ss:Width="75"/>
   <Column ss:Width="75"/>
   <Column ss:Width="200"/>
   <Column ss:Width="110"/>
   <Column ss:Width="120"/>
   <Column ss:Width="120"/>
   <!-- Row 1: Section group labels -->
   <Row ss:Height="22">
    <Cell ss:StyleID="sec_no"><Data ss:Type="String">No.</Data></Cell>
    <Cell ss:StyleID="sec_peserta" ss:MergeAcross="7"><Data ss:Type="String">DATA PESERTA</Data></Cell>
    <Cell ss:StyleID="sec_b1" ss:MergeAcross="5"><Data ss:Type="String">BLOK I — KETERANGAN CALON MITRA</Data></Cell>
    <Cell ss:StyleID="sec_b2u" ss:MergeAcross="7"><Data ss:Type="String">BLOK II — URAIAN JAWABAN</Data></Cell>
    <Cell ss:StyleID="sec_b2y" ss:MergeAcross="11"><Data ss:Type="String">BLOK II — YA / TIDAK</Data></Cell>
    <Cell ss:StyleID="sec_b4" ss:MergeAcross="3"><Data ss:Type="String">BLOK IV — PENILAIAN</Data></Cell>
    <Cell ss:StyleID="sec_b5" ss:MergeAcross="1"><Data ss:Type="String">BLOK V</Data></Cell>
    <Cell ss:StyleID="sec_meta" ss:MergeAcross="1"><Data ss:Type="String">INFO</Data></Cell>
   </Row>
   <!-- Row 2: Column headers -->
   <Row ss:Height="42">
    <Cell ss:StyleID="header"><Data ss:Type="String">No.</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Nama Lengkap</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">JK</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Pendidikan</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Pekerjaan</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">No. Telp</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Email</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Posisi Daftar</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Kecamatan</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Kepemilikan Motor</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Kemampuan Berkendara</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Pernah CAPI</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Merk HP</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Tipe HP</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">RAM HP</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Q1: Perkenalan Diri</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Q2: Pengetahuan BPS</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Q3: Alasan Daftar</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Q4: Pernah Pendataan</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Q5: Evaluasi Pendataan</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Q6: Responden Menolak</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Q7: Konflik dengan Tim</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Q8: Manajemen Waktu</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Q9: Status ASN/PNS</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Q10: Sistem Kontrak</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Q11: Sedang Hamil</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Q12: Punya Balita</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Ket. Balita</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Q13: Pasangan Daftar</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Q14: Penyakit Kronis</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Ket. Penyakit</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Q15: Bersedia Kec. Lain</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Q16: Tanggung Jawab Dok.</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Q17: Bersedia Validasi</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Q18: Bayar Setelah Clean</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Komunikasi</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Penampilan</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Kemampuan Analisa</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Rata-rata</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Catatan Pewawancara</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Predikat</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Pewawancara</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Tgl Wawancara</Data></Cell>
   </Row>
   <?php foreach ($rows as $i => $r):
       $rs  = ($i % 2 === 1) ? 'alt'  : 'wrap';
       $rsc = ($i % 2 === 1) ? 'altc' : 'center';
       $jk  = $r['jenis_kelamin'] ?? '';
       $jk_out = (stripos($jk,'l')===0) ? 'L' : ((stripos($jk,'p')===0) ? 'P' : $jk);
   ?>
   <Row ss:AutoFitHeight="1">
    <?= xcn($i + 1) ?>
    <?= xc($r['nama'], $rs) ?>
    <?= xc($jk_out, $rsc) ?>
    <?= xc($r['pendidikan'], $rs) ?>
    <?= xc($r['pekerjaan'], $rs) ?>
    <?= xc($r['no_telp'], $rsc) ?>
    <?= xc($r['email'], $rs) ?>
    <?= xc($r['posisi_daftar'], $rs) ?>
    <?= xc($r['alamat_kec'], $rsc) ?>
    <?= xcy($r['kepemilikan_motor']) ?>
    <?= xcy($r['kemampuan_berkendara']) ?>
    <?= xcy($r['pernah_capi']) ?>
    <?= xc($r['merk_hp'], $rsc) ?>
    <?= xc($r['tipe_hp'], $rsc) ?>
    <?= xc($r['ram_hp'], $rsc) ?>
    <?= xc($r['jawab_perkenalan'], $rs) ?>
    <?= xc($r['jawab_pengetahuan_bps'], $rs) ?>
    <?= xc($r['jawab_alasan_daftar'], $rs) ?>
    <?= xcy($r['pernah_pendataan']) ?>
    <?= xc($r['jawab_evaluasi'], $rs) ?>
    <?= xc($r['jawab_penolak'], $rs) ?>
    <?= xc($r['jawab_konflik'], $rs) ?>
    <?= xc($r['jawab_manajemen_waktu'], $rs) ?>
    <?= xcy($r['status_asn']) ?>
    <?= xcy($r['status_kontrak']) ?>
    <?= xcy($r['status_hamil']) ?>
    <?= xcy($r['punya_balita']) ?>
    <?= xc($r['keterangan_balita'], $rs) ?>
    <?= xcy($r['pasangan_daftar']) ?>
    <?= xcy($r['penyakit_kronis']) ?>
    <?= xc($r['keterangan_penyakit'], $rs) ?>
    <?= xcy($r['bersedia_kec_lain']) ?>
    <?= xcy($r['bersedia_tanggung_jawab']) ?>
    <?= xcy($r['bersedia_validasi']) ?>
    <?= xcy($r['bersedia_bayar_clean']) ?>
    <?= xcn($r['nilai_komunikasi'], 'nilai') ?>
    <?= xcn($r['nilai_penampilan'], 'nilai') ?>
    <?= xcn($r['nilai_analisa'], 'nilai') ?>
    <?= xcn($r['nilai'], 'nilai') ?>
    <?= xc($r['catatan'], $rs) ?>
    <?= xcpred($r['predikat']) ?>
    <?= xc($r['pewawancara'], $rs) ?>
    <?= xc(substr($r['created_at'] ?? '', 0, 16), $rsc) ?>
   </Row>
   <?php endforeach; ?>
  </Table>
 </Worksheet>
</Workbook>
