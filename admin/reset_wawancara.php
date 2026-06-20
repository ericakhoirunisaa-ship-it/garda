<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: rekap.php'); exit;
}

$wid      = (int)($_POST['wawancara_id'] ?? 0);
$redirect = ($_POST['redirect'] ?? '') === 'index.php' ? 'index.php' : 'rekap.php';

if ($wid > 0) {
    $row = $conn->query("SELECT foto FROM wawancara WHERE id = $wid")->fetch_assoc();
    if ($row) {
        if ($row['foto']) {
            $path = __DIR__ . '/uploads/foto/' . $row['foto'];
            if (file_exists($path)) unlink($path);
        }
        $conn->query("DELETE FROM wawancara WHERE id = $wid");
    }
}

header("Location: $redirect");
exit;
