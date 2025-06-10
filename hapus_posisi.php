<?php
include 'db.php';
session_start();
date_default_timezone_set('Asia/Jakarta'); 

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $now = date('Y-m-d H:i:s');

    $sql = "UPDATE positions SET deleted_at = ? WHERE id_posisi = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $now, $id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Posisi berhasil dihapus.";
    } else {
        $_SESSION['message'] = "Gagal menghapus posisi: " . $stmt->error;
    }
} else {
    $_SESSION['message'] = "ID posisi tidak ditemukan.";
}

header("Location: master_posisi.php");
exit;
?>
