<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
include 'db.php';

if (isset($_GET['id'])) {
    $id_user = $_GET['id'];
    $deleted_at = date('Y-m-d H:i:s');

    // Update deleted_at dan status sekaligus
    $stmt = $conn->prepare("UPDATE users SET deleted_at = ?, status = 0 WHERE id_user = ?");
    $stmt->bind_param("si", $deleted_at, $id_user);

    if ($stmt->execute()) {
        $_SESSION['flash_message'] = "User berhasil dihapus.";
    } else {
        $_SESSION['flash_message'] = "Gagal menghapus user: " . $stmt->error;
    }

    $stmt->close();
} else {
    $_SESSION['flash_message'] = "ID user tidak ditemukan.";
}

header("Location: master_user.php");
exit();
?>
