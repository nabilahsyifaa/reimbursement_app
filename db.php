<?php
$host = "localhost";
$user = "nsworksm_nabilah";
$password = "unsadaproject2025"; // default XAMPP biasanya kosong
$database = "nsworksm_reimbursement_db";

// Buat koneksi
$conn = mysqli_connect($host, $user, $password, $database);

// Cek koneksi
if (!$conn) {
  die("Koneksi gagal: " . mysqli_connect_error());
} 
?>
