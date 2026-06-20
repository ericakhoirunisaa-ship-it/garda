<?php
require_once 'config.php';

$cari = isset($_POST['cari']) ? mysqli_real_escape_string($conn, $_POST['cari']) : '';
$page = isset($_POST['halaman']) ? (int)$_POST['halaman'] : 1;
$limit = 10;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

$where = "";
if (!empty($cari)) {
    $where = "WHERE kode LIKE '%$cari%' OR deskripsi LIKE '%$cari%' OR kegiatan_utama LIKE '%$cari%' OR penjelasan LIKE '%$cari%'";
}

// Hitung Total Data
$query_count = mysqli_query($conn, "SELECT COUNT(*) AS total FROM kbli $where");
$total_data = mysqli_fetch_assoc($query_count)['total'];
$total_pages = ceil($total_data / $limit);

// Ambil Data
$sql = "SELECT * FROM kbli $where ORDER BY kode ASC LIMIT $start, $limit";
$result = mysqli_query($conn, $sql);

// Bangun Baris Tabel
$tabel_html = '';
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $tabel_html .= '<tr>
            <td class="px-4 align-middle"><strong>'.htmlspecialchars($row['kode']).'</strong></td>
            <td class="align-middle fw-bold text-dark">'.htmlspecialchars($row['deskripsi']).'</td>
            <td class="align-middle text-muted">'.htmlspecialchars($row['kegiatan_utama']).'</td>
            <td class="px-4 align-middle small">'.htmlspecialchars($row['penjelasan']).'</td>
        </tr>';
    }
} else {
    $tabel_html = '<tr><td colspan="4" class="text-center py-5 text-muted">Data tidak ditemukan.</td></tr>';
}

// Bangun Pagination
$pagination_html = '<nav><ul class="pagination justify-content-center">';
if ($total_pages > 1) {
    // Previous
    $disabled_prev = ($page <= 1) ? 'disabled' : '';
    $pagination_html .= '<li class="page-item '.$disabled_prev.'"><a class="page-link-ajax shadow-sm" data-halaman="'.($page-1).'">Previous</a></li>';
    
    // Numbers
    for ($i = 1; $i <= $total_pages; $i++) {
        $active = ($page == $i) ? 'active' : '';
        $pagination_html .= '<li class="page-item"><a class="page-link-ajax shadow-sm '.$active.'" data-halaman="'.$i.'">'.$i.'</a></li>';
    }
    
    // Next
    $disabled_next = ($page >= $total_pages) ? 'disabled' : '';
    $pagination_html .= '<li class="page-item '.$disabled_next.'"><a class="page-link-ajax shadow-sm" data-halaman="'.($page+1).'">Next</a></li>';
}
$pagination_html .= '</ul></nav>';

// Kirim balik sebagai JSON
echo json_encode([
    'tabel' => $tabel_html,
    'pagination' => $pagination_html
]);