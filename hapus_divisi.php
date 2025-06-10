<?php
include 'db.php';
session_start();
date_default_timezone_set('Asia/Jakarta'); 

if (isset($_GET['id']))
    $id = $_GET['id'];
    $now = date('Y-m-d H:i:s');

    $sql = "UPDATE divisions SET deleted_at = ? WHERE id_divisi = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $now, $id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Divisi berhasil dihapus.";
    } else {
        $_SESSION['message'] = "Gagal menghapus divisi.";
    }

header("Location: master_divisi.php");
exit;
?>
