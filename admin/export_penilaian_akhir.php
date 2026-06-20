<?php
require_once 'config.php';
requireSuperAdmin();

// ── Query semua peserta Diterima ───────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'semua';
$kec    = trim($_GET['kec'] ?? '');

$where  = "WHERE p.status_seleksi = 'Diterima'";
if ($search !== '') {
    $s = $conn->real_escape_string('%' . $search . '%');
    $where .= " AND p.nama LIKE '$s'";
}
if ($kec !== '') {
    $k = $conn->real_escape_string($kec);
    $where .= " AND p.alamat_kec = '$k'";
}
if ($filter === 'lulus')         $where .= " AND pa.status_akhir = 'Lulus'";
elseif ($filter === 'tidak_lulus') $where .= " AND pa.status_akhir = 'Tidak Lulus'";
elseif ($filter === 'belum')     $where .= " AND (pa.id IS NULL OR pa.status_akhir IS NULL OR pa.status_akhir = '')";
elseif ($filter === 'blm_wawancara') $where .= " AND w.id IS NULL";

$sql = "SELECT p.nama, p.kelompok, p.posisi_daftar, p.jenis_kelamin, p.pendidikan, p.no_telp, p.alamat_kec,
               w.id AS wawancara_id,
               w.nilai_komunikasi, w.nilai_penampilan, w.nilai_analisa, w.nilai, w.predikat,
               p.nilai_kompetensi, pa.status_akhir, pa.keterangan
        FROM peserta p
        LEFT JOIN wawancara w ON w.id = (
            SELECT id FROM wawancara WHERE peserta_id = p.id ORDER BY created_at DESC LIMIT 1
        )
        LEFT JOIN penilaian_akhir pa ON pa.peserta_id = p.id
        $where
        ORDER BY CASE WHEN w.id IS NULL THEN 1 ELSE 0 END, w.nilai DESC, p.nama ASC";

$result = $conn->query($sql);
$rows   = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// ── Output XLS ────────────────────────────────────────────────────────────
$filename = 'Penilaian_Akhir_Mitra_SE2026_' . date('Ymd_His') . '.xls';
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

function xc($val, $style = 'wrap') {
    $v = htmlspecialchars((string)($val ?? ''), ENT_XML1);
    return "<Cell ss:StyleID=\"{$style}\"><Data ss:Type=\"String\">{$v}</Data></Cell>\n";
}
function xcn($val, $style = 'center') {
    if ($val === null || $val === '' || $val === 0) return xc('', $style);
    return "<Cell ss:StyleID=\"{$style}\"><Data ss:Type=\"Number\">" . (float)$val . "</Data></Cell>\n";
}
function xcpred($val) {
    static $map = ['A'=>'pred_A','B'=>'pred_B','C'=>'pred_C','D'=>'pred_D','E'=>'pred_E'];
    return xc((string)($val ?? ''), $map[$val] ?? 'center');
}
function xcstatus($val) {
    if ($val === 'Lulus')      return xc('Lulus',      'st_lulus');
    if ($val === 'Tidak Lulus') return xc('Tidak Lulus','st_tidak');
    return xc('Belum', 'st_belum');
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:x="urn:schemas-microsoft-com:office:excel">
 <Styles>
  <Style ss:ID="title">
   <Font ss:Bold="1" ss:Size="13" ss:Color="#1e293b"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="subtitle">
   <Font ss:Size="9" ss:Color="#64748b"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="header">
   <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="9"/>
   <Interior ss:Color="#F79039" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
   <Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E06820"/></Borders>
  </Style>
  <Style ss:ID="sec_peserta">
   <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="9"/>
   <Interior ss:Color="#1e40af" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="sec_nilai">
   <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="9"/>
   <Interior ss:Color="#b45309" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="sec_hasil">
   <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="9"/>
   <Interior ss:Color="#166534" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
  </Style>
  <Style ss:ID="wrap">
   <Alignment ss:WrapText="1" ss:Vertical="Top"/>
   <Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders>
  </Style>
  <Style ss:ID="alt">
   <Interior ss:Color="#FFFBF0" ss:Pattern="Solid"/>
   <Alignment ss:WrapText="1" ss:Vertical="Top"/>
   <Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders>
  </Style>
  <Style ss:ID="center">
   <Alignment ss:Horizontal="Center" ss:Vertical="Top"/>
   <Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders>
  </Style>
  <Style ss:ID="altc">
   <Interior ss:Color="#FFFBF0" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Top"/>
   <Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders>
  </Style>
  <Style ss:ID="nilai_num">
   <Font ss:Bold="1" ss:Size="10"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>
   <Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders>
  </Style>
  <Style ss:ID="no_waw">
   <Interior ss:Color="#FFFBEB" ss:Pattern="Solid"/>
   <Alignment ss:WrapText="1" ss:Vertical="Top"/>
   <Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders>
  </Style>
  <Style ss:ID="no_waw_c">
   <Interior ss:Color="#FFFBEB" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Top"/>
   <Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders>
  </Style>
  <!-- Predikat -->
  <Style ss:ID="pred_A"><Font ss:Bold="1" ss:Color="#166534"/><Interior ss:Color="#DCFCE7" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders></Style>
  <Style ss:ID="pred_B"><Font ss:Bold="1" ss:Color="#1E3A8A"/><Interior ss:Color="#DBEAFE" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders></Style>
  <Style ss:ID="pred_C"><Font ss:Bold="1" ss:Color="#92400E"/><Interior ss:Color="#FEF9C3" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders></Style>
  <Style ss:ID="pred_D"><Font ss:Bold="1" ss:Color="#9A3412"/><Interior ss:Color="#FFEDD5" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders></Style>
  <Style ss:ID="pred_E"><Font ss:Bold="1" ss:Color="#991B1B"/><Interior ss:Color="#FEE2E2" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders></Style>
  <!-- Status akhir -->
  <Style ss:ID="st_lulus"><Font ss:Bold="1" ss:Color="#166534"/><Interior ss:Color="#DCFCE7" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders></Style>
  <Style ss:ID="st_tidak"><Font ss:Bold="1" ss:Color="#991B1B"/><Interior ss:Color="#FEE2E2" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders></Style>
  <Style ss:ID="st_belum"><Font ss:Color="#374151"/><Interior ss:Color="#F1F5F9" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders></Style>
  <Style ss:ID="komp_num"><Font ss:Bold="1" ss:Color="#1e40af"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="All" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/></Borders></Style>
 </Styles>

 <Worksheet ss:Name="Penilaian Akhir Mitra">
  <Table>
   <Column ss:Width="30"/>   <!-- No -->
   <Column ss:Width="160"/>  <!-- Nama -->
   <Column ss:Width="110"/>  <!-- Posisi Daftar -->
   <Column ss:Width="30"/>   <!-- JK -->
   <Column ss:Width="100"/>  <!-- Pendidikan -->
   <Column ss:Width="100"/>  <!-- Kecamatan -->
   <Column ss:Width="110"/>  <!-- No Telp -->
   <Column ss:Width="65"/>   <!-- Kom -->
   <Column ss:Width="65"/>   <!-- Pen -->
   <Column ss:Width="65"/>   <!-- Ana -->
   <Column ss:Width="65"/>   <!-- Rata² -->
   <Column ss:Width="65"/>   <!-- Predikat -->
   <Column ss:Width="80"/>   <!-- Nilai Kompetensi -->
   <Column ss:Width="100"/>  <!-- Status -->
   <Column ss:Width="200"/>  <!-- Keterangan -->

   <!-- Row 1: Judul -->
   <Row ss:Height="26">
    <Cell ss:StyleID="title" ss:MergeAcross="14">
     <Data ss:Type="String">REKAPITULASI PENILAIAN AKHIR MITRA — SENSUS EKONOMI 2026</Data>
    </Cell>
   </Row>
   <!-- Row 2: Subtitle -->
   <Row ss:Height="18">
    <Cell ss:StyleID="subtitle" ss:MergeAcross="14">
     <Data ss:Type="String">BPS Kabupaten Toli-Toli | Dicetak: <?= date('d/m/Y H:i') ?></Data>
    </Cell>
   </Row>
   <!-- Row 3: Kosong -->
   <Row ss:Height="8"/>

   <!-- Row 4: Section labels -->
   <Row ss:Height="20">
    <Cell ss:StyleID="sec_peserta"><Data ss:Type="String">No.</Data></Cell>
    <Cell ss:StyleID="sec_peserta" ss:MergeAcross="5"><Data ss:Type="String">DATA PESERTA</Data></Cell>
    <Cell ss:StyleID="sec_nilai" ss:MergeAcross="5"><Data ss:Type="String">PENILAIAN</Data></Cell>
    <Cell ss:StyleID="sec_hasil" ss:MergeAcross="1"><Data ss:Type="String">HASIL AKHIR</Data></Cell>
   </Row>

   <!-- Row 5: Column headers -->
   <Row ss:Height="40">
    <Cell ss:StyleID="header"><Data ss:Type="String">No.</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Nama Lengkap</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Kelompok</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Posisi Daftar</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">JK</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Pendidikan</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Kecamatan</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">No Telp</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Nilai Komunikasi</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Nilai Penampilan</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Nilai Analisis</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Rata-rata Wawancara</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Predikat</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Nilai Kompetensi</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Status Kelulusan</Data></Cell>
    <Cell ss:StyleID="header"><Data ss:Type="String">Keterangan</Data></Cell>
   </Row>

   <?php foreach ($rows as $i => $r):
       $sudah = $r['wawancara_id'] !== null;
       $alt   = ($i % 2 === 1);
       $rs    = $sudah ? ($alt ? 'alt'  : 'wrap')   : ($alt ? 'no_waw'   : 'no_waw');
       $rsc   = $sudah ? ($alt ? 'altc' : 'center') : ($alt ? 'no_waw_c' : 'no_waw_c');
       $jk    = $r['jenis_kelamin'] ?? '';
       $jkOut = (stripos($jk,'l')===0) ? 'L' : ((stripos($jk,'p')===0) ? 'P' : $jk);

       // Nilai wawancara — "Tidak Ikut" jika belum wawancara
       $nKom  = $sudah ? $r['nilai_komunikasi'] : null;
       $nPen  = $sudah ? $r['nilai_penampilan'] : null;
       $nAna  = $sudah ? $r['nilai_analisa']    : null;
       $nRata = $sudah ? $r['nilai']            : null;
       $pred  = $sudah ? ($r['predikat'] ?? null) : null;

       // Nilai kompetensi dari peserta (semua peserta, bukan hanya yang wawancara)
       $nKompRaw = $r['nilai_kompetensi'];
       $nKomp = ($nKompRaw !== null) ? number_format((float)$nKompRaw, 2, ',', '') : null;
   ?>
   <Row ss:AutoFitHeight="1">
    <?= xc((string)($i + 1), $rsc) ?>
    <?= xc($r['nama'], $rs) ?>
    <?= xc($r['kelompok'] ?? '', $rsc) ?>
    <?= xc($r['posisi_daftar'], $rs) ?>
    <?= xc($jkOut, $rsc) ?>
    <?= xc($r['pendidikan'], $rs) ?>
    <?= xc($r['alamat_kec'], $rsc) ?>
    <?= xc($r['no_telp'] ?? '', $rsc) ?>

    <?php if ($sudah && $nKom !== null && $nKom > 0): ?>
        <?= xcn($nKom, 'nilai_num') ?>
    <?php elseif (!$sudah): ?>
        <?= xc('Tidak Ikut', $rsc) ?>
    <?php else: ?>
        <?= xc('—', $rsc) ?>
    <?php endif; ?>

    <?php if ($sudah && $nPen !== null && $nPen > 0): ?>
        <?= xcn($nPen, 'nilai_num') ?>
    <?php elseif (!$sudah): ?>
        <?= xc('Tidak Ikut', $rsc) ?>
    <?php else: ?>
        <?= xc('—', $rsc) ?>
    <?php endif; ?>

    <?php if ($sudah && $nAna !== null && $nAna > 0): ?>
        <?= xcn($nAna, 'nilai_num') ?>
    <?php elseif (!$sudah): ?>
        <?= xc('Tidak Ikut', $rsc) ?>
    <?php else: ?>
        <?= xc('—', $rsc) ?>
    <?php endif; ?>

    <?php if ($sudah && $nRata !== null && $nRata > 0): ?>
        <?= xcn($nRata, 'nilai_num') ?>
    <?php elseif (!$sudah): ?>
        <?= xc('Tidak Ikut', $rsc) ?>
    <?php else: ?>
        <?= xc('—', $rsc) ?>
    <?php endif; ?>

    <?php if (!$sudah): ?>
        <?= xc('Tidak Ikut', $rsc) ?>
    <?php elseif ($pred !== null): ?>
        <?= xcpred($pred) ?>
    <?php else: ?>
        <?= xc('—', $rsc) ?>
    <?php endif; ?>

    <?php if ($nKomp !== null): ?>
        <?= xc($nKomp, 'komp_num') ?>
    <?php else: ?>
        <?= xc('—', $rsc) ?>
    <?php endif; ?>

    <?= xcstatus($r['status_akhir']) ?>
    <?= xc($r['keterangan'], $rs) ?>
   </Row>
   <?php endforeach; ?>

   <!-- Baris kosong -->
   <Row ss:Height="8"/>
   <!-- Footer info -->
   <Row>
    <Cell ss:StyleID="subtitle" ss:MergeAcross="14">
     <Data ss:Type="String">Keterangan: Baris berwarna kuning = belum mengikuti wawancara (semua nilai otomatis E)</Data>
    </Cell>
   </Row>
  </Table>
 </Worksheet>
</Workbook>
