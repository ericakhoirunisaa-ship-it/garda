<?php
require_once 'config.php';
requireLogin();
require_once __DIR__ . '/se_petugas.php';

/* ── Desa map (pre-generated dari LK Hasil Pengolahan Muatan_Kab 7206_result.xlsx) ── */
require_once __DIR__ . '/se_desa_map.php';

/* ── Pengawas per kecamatan dari sensus_pml ── */
$pengawasPerKec = [];
$pmlRows = $conn->query("SELECT nama, email, kec_code FROM sensus_pml ORDER BY kec_code, nama")->fetch_all(MYSQLI_ASSOC);
foreach ($pmlRows as $r) {
    $kc   = $r['kec_code'];
    $nama = trim($r['nama']) ?: $r['email'];
    if ($nama) $pengawasPerKec[$kc][] = $nama;
}
$getPengawas = function($kec_code) use ($pengawasPerKec) {
    return isset($pengawasPerKec[$kec_code])
        ? implode('; ', $pengawasPerKec[$kec_code])
        : '';
};

/* ── Filter kecamatan (dari query string) ── */
$kecFilter = trim($_GET['kec'] ?? 'all');
if ($kecFilter !== 'all' && !isset($kecNama[$kecFilter])) $kecFilter = 'all';
$kecCond  = $kecFilter !== 'all'
    ? "WHERE SUBSTRING(sls_code, 5, 3) = '" . $conn->real_escape_string($kecFilter) . "'"
    : '';

/* ── Query semua SLS ── */
$slsRows = $conn->query("
    SELECT id, email, sls_code,
           SUBSTRING(sls_code,5,3) AS kec_code,
           CONCAT(SUBSTRING(sls_code,5,3), SUBSTRING(sls_code,8,3)) AS desa_key,
           open_count, draft,
           submitted_by_pencacah, submitted_respondent,
           rejected, approved, `revoke`,
           edited_by_pengawas, edited_by_admin_kabupaten
    FROM sensus_ekonomi $kecCond
    ORDER BY kec_code, sls_code, email
")->fetch_all(MYSQLI_ASSOC);

/* ── Header file Excel ── */
$kecLabel  = ($kecFilter !== 'all') ? ('_Kec' . $kecFilter) : '';
$filename  = 'Monitoring_SE2026' . $kecLabel . '_' . date('Ymd_His') . '.xls';
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

/* ── Helper XML cells ── */
function xc($val, $sid = 'data') {
    $v = htmlspecialchars((string)($val ?? ''), ENT_XML1, 'UTF-8');
    return "<Cell ss:StyleID=\"{$sid}\"><Data ss:Type=\"String\">{$v}</Data></Cell>\n";
}
function xcn($val, $sid = 'num') {
    return "<Cell ss:StyleID=\"{$sid}\"><Data ss:Type=\"Number\">" . (int)$val . "</Data></Cell>\n";
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:x="urn:schemas-microsoft-com:office:excel">
 <Styles>
  <Style ss:ID="hdr">
   <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="9"/>
   <Interior ss:Color="#f79039" ss:Pattern="Solid"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#e06010"/>
   </Borders>
  </Style>
  <Style ss:ID="data">
   <Font ss:Size="9"/>
   <Alignment ss:Vertical="Top" ss:WrapText="1"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#e2e8f0"/>
   </Borders>
  </Style>
  <Style ss:ID="num">
   <Font ss:Size="9"/>
   <Alignment ss:Horizontal="Center" ss:Vertical="Top"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#e2e8f0"/>
   </Borders>
  </Style>
  <Style ss:ID="mono">
   <Font ss:Name="Courier New" ss:Size="9"/>
   <Alignment ss:Vertical="Top"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#e2e8f0"/>
   </Borders>
  </Style>
  <Style ss:ID="title">
   <Font ss:Bold="1" ss:Size="12"/>
  </Style>
  <Style ss:ID="sub">
   <Font ss:Italic="1" ss:Size="9" ss:Color="#6b7280"/>
  </Style>
 </Styles>
 <Worksheet ss:Name="Monitoring SE2026">
  <Table>
   <Column ss:Width="28"/>   <!-- No -->
   <Column ss:Width="90"/>   <!-- Kode SLS -->
   <Column ss:Width="80"/>   <!-- Kecamatan -->
   <Column ss:Width="100"/>  <!-- Desa (LK) -->
   <Column ss:Width="120"/>  <!-- Nama Petugas -->
   <Column ss:Width="140"/>  <!-- Email -->
   <Column ss:Width="150"/>  <!-- Nama Pengawas -->
   <Column ss:Width="42"/>   <!-- Open -->
   <Column ss:Width="42"/>   <!-- Draft -->
   <Column ss:Width="55"/>   <!-- Sub. Pencacah -->
   <Column ss:Width="55"/>   <!-- Sub. Respondent -->
   <Column ss:Width="52"/>   <!-- Rejected -->
   <Column ss:Width="52"/>   <!-- Approved -->
   <Column ss:Width="48"/>   <!-- Revoke -->
   <Column ss:Width="55"/>   <!-- Edit Pengawas -->
   <Column ss:Width="55"/>   <!-- Edit Admin Kab -->

   <!-- Judul -->
   <Row ss:Height="20">
    <Cell ss:StyleID="title"><Data ss:Type="String">Monitoring Sensus Ekonomi 2026<?php
        echo $kecFilter !== 'all' ? ' — Kecamatan ' . htmlspecialchars($kecNama[$kecFilter] ?? $kecFilter) : '';
    ?></Data></Cell>
   </Row>
   <Row ss:Height="14">
    <Cell ss:StyleID="sub"><Data ss:Type="String">Dicetak: <?= date('d M Y H:i') ?> WIB  |  Total SLS: <?= count($slsRows) ?></Data></Cell>
   </Row>
   <Row/>

   <!-- Header kolom -->
   <Row ss:Height="30">
    <?= xc('#', 'hdr') ?>
    <?= xc('Kode SLS', 'hdr') ?>
    <?= xc('Kecamatan', 'hdr') ?>
    <?= xc('Desa/Kelurahan', 'hdr') ?>
    <?= xc('Nama Petugas', 'hdr') ?>
    <?= xc('Email', 'hdr') ?>
    <?= xc('Nama Pengawas', 'hdr') ?>
    <?= xc('Open', 'hdr') ?>
    <?= xc('Draft', 'hdr') ?>
    <?= xc('Submit Pencacah', 'hdr') ?>
    <?= xc('Submit Respondent', 'hdr') ?>
    <?= xc('Rejected', 'hdr') ?>
    <?= xc('Approved', 'hdr') ?>
    <?= xc('Revoke', 'hdr') ?>
    <?= xc('Edit Pengawas', 'hdr') ?>
    <?= xc('Edit Admin Kab', 'hdr') ?>
   </Row>

<?php foreach ($slsRows as $i => $r):
    $kec_code   = $r['kec_code'];
    $nmDesa     = $desaMap[$r['desa_key']] ?? '';
    $nmKec      = $kecNama[$kec_code] ?? $kec_code;
    $nmPetugas  = $getNama($r['email']);
    $nmPengawas = $getPengawas($kec_code);
?>
   <Row>
    <?= xcn($i + 1) ?>
    <Cell ss:StyleID="mono"><Data ss:Type="String"><?= htmlspecialchars($r['sls_code']) ?></Data></Cell>
    <?= xc($nmKec) ?>
    <?= xc($nmDesa) ?>
    <?= xc($nmPetugas) ?>
    <?= xc($r['email']) ?>
    <?= xc($nmPengawas) ?>
    <?= xcn($r['open_count']) ?>
    <?= xcn($r['draft']) ?>
    <?= xcn($r['submitted_by_pencacah']) ?>
    <?= xcn($r['submitted_respondent']) ?>
    <?= xcn($r['rejected']) ?>
    <?= xcn($r['approved']) ?>
    <?= xcn($r['revoke']) ?>
    <?= xcn($r['edited_by_pengawas'] ?? 0) ?>
    <?= xcn($r['edited_by_admin_kabupaten'] ?? 0) ?>
   </Row>
<?php endforeach; ?>

  </Table>
 </Worksheet>
</Workbook>
