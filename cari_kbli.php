<?php
include 'koneksi.php';

$keyword = $_POST['keyword'];
$sql = "SELECT * FROM kbli WHERE deskripsi LIKE '%$keyword%'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        echo "<tr>
                <td><strong>{$row['kode']}</strong></td>
                <td>{$row['deskripsi']}</td>
                <td>{$row['kegiatan_utama']}</td>
                <td><small>{$row['penjelasan']}</small></td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='4' class='text-center'>Data tidak ditemukan</td></tr>";
}
?>