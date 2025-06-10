<?php
$host = "localhost";
$user = "root";
$password = ""; // default XAMPP biasanya kosong
$database = "reimbursement_db";

// Buat koneksi
$conn = mysqli_connect($host, $user, $password, $database);

// Cek koneksi
if (!$conn) {
  die("Koneksi gagal: " . mysqli_connect_error());
} 
?>
