<?php
session_start();
date_default_timezone_set('Asia/Jakarta'); 
include 'db.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
  $_SESSION['flash_message'] = "ID project tidak ditemukan.";
  header("Location: master_project.php");
  exit;
}

$id = (int) $_GET['id'];
$deleted_at = date("Y-m-d H:i:s");

// Update kolom deleted_at untuk soft delete
$stmt = $conn->prepare("UPDATE projects SET deleted_at = ? WHERE id_project = ?");
$stmt->bind_param("si", $deleted_at, $id);

if ($stmt->execute()) {
  $_SESSION['flash_message'] = "Project berhasil dihapus.";
} else {
  $_SESSION['flash_message'] = "Gagal menghapus project: " . $conn->error;
}

header("Location: master_project.php");
exit;
?>
