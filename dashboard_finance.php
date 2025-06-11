<?php
include 'db.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Ambil data dari session
$user_id = $_SESSION['user_id'];
$namaLengkap = $_SESSION['nama'] ?? 'User';
$namaPosisi = $_SESSION['nama_posisi'] ?? 'Posisi';


// Ambil notifikasi jika ada
$message = "";
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// QUERY DENGAN PERBAIKAN JOIN log_pengajuan
$sql = "SELECT p.*, 
       u.nama_lengkap, 
       j.nama_pengeluaran,
       d.nama_divisi AS divisi,
       s.nama_posisi AS posisi,
       pr.nama_project AS project,
       a.nama_aktifitas AS status
FROM pengajuan p 
JOIN users u ON p.id_user = u.id_user
LEFT JOIN jenis_pengeluaran j ON p.id_pengeluaran = j.id_pengeluaran
LEFT JOIN divisions d ON u.id_divisi = d.id_divisi
LEFT JOIN positions s ON u.id_posisi = s.id_posisi
LEFT JOIN projects pr ON p.id_project = pr.id_project
LEFT JOIN log_pengajuan l ON l.id_pengajuan = p.id_pengajuan
LEFT JOIN aktifitas a ON l.id_aktifitas = a.id_aktifitas
WHERE l.id_aktifitas = 3
  AND l.updated_at IS NULL
  AND l.created_by = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id); // i untuk integer
$stmt->execute();
$result = $stmt->get_result();
?>




<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    * {
      box-sizing: border-box;
      font-family: 'Inter', sans-serif;
      margin: 0;
      padding: 0;
    }

    body {
      display: flex;
      min-height: 100vh;
      background-color: #f7f9fc;
    }

    .sidebar {
      width: 250px;
      background-color: #003366;
      color: white;
      padding: 20px;
      display: flex;
      flex-direction: column;
    }

    .sidebar h2 {
      font-size: 24px;
      background: white;
      color: #003366;
      padding: 10px;
      text-align: center;
      border-radius: 8px;
      margin-bottom: 10px;
    }

    .sidebar p {
      text-align: center;
      margin-bottom: 20px;
      font-size: 14px;
    }

    .sidebar a {
      text-decoration: none;
      color: white;
      font-weight: 500;
      margin: 8px 0;
      display: block;
      padding: 8px;
      border-radius: 6px;
      transition: background 0.2s;
    }

    .sidebar a:hover {
      background-color: #004080;
    }

    .main {
      flex: 1;
      padding: 30px;
      width: 100%; /* Tambahan agar full */
      overflow-x: auto; /* Agar responsif */
    }

    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }

    .topbar h1 {
      color: #003366;
      font-size: 28px;
    }

    .topbar a {
      color: #003366;
      text-decoration: none;
      font-weight: 600;
      margin-left: 20px;
    }

    .btn {
      background-color: #0066cc;
      color: white;
      padding: 8px 16px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 500;
      margin-bottom: 15px;
      transition: background 0.2s;
      text-decoration: none; /* Hilangkan underline */
    }

    .btn:hover {
      background-color: #004e99;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background-color: white;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 8px rgba(0,0,0,0.05);
      table-layout: auto;
    }

    th, td {
      padding: 14px 16px;
      border-bottom: 1px solid #eee;
      text-align: left;
      font-size: 14px;
    }

    th {
      background-color: #f0f4f8;
      font-weight: 600;
      color: #333;
    }

    tr:last-child td {
      border-bottom: none;
    }

    .icon {
      cursor: pointer;
      margin-right: 12px;
      font-size: 16px;
      color: #555;
      transition: color 0.2s;
    }

    .icon:hover {
      color: #0066cc;
    }

    .hamburger {
      display: none;
    }

    #notification {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 15px 25px;
      border-radius: 8px;
      color: white;
      font-weight: 600;
      font-size: 14px;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.5s ease;
      box-shadow: 0 2px 10px rgba(0,0,0,0.2);
      z-index: 9999;
      max-width: 300px;
      word-wrap: break-word;
    }

    #notification.show {
      opacity: 1;
      pointer-events: auto;
    }

    #notification.success {
      background-color: #28a745;
    }

    #notification.error {
      background-color: #dc3545;
    }

    @media (max-width: 768px) {
      .sidebar {
        display: none;
      }

      .hamburger {
        display: block;
        position: fixed;
        top: 10px;
        left: 10px;
        font-size: 26px;
        background: #003366;
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
        z-index: 999;
        cursor: pointer;
      }

      .main {
        margin-left: 0;
        padding-top: 60px;
        overflow-x: auto;
      }

      table {
        min-width: 600px;
      }
    }
  </style>
</head>
<body>

  <div class="hamburger"><i class="fas fa-bars"></i></div>

  <div class="sidebar">
  <img src="img/logo_adw.jpg" alt="Logo ADW" style="display: block; margin: 0 auto 10px; width: 80px; height: auto;" />
  <p>
  <?= htmlspecialchars($namaLengkap) ?><br>
  <small><?= htmlspecialchars($namaPosisi) ?></small>
</p>
    <a href="dashboard_finance.php">Dashboard</a>
    <a href="pengajuan_reimbursement.php">Pengajuan</a>
    <a href="daftar_pekerjaan_finance.php">Daftar Pekerjaan</a>
    <a href="monitor_reimbursement.php">Monitor</a>
  </div>

  <div class="main">
    <div class="topbar">
      <h1>Dashboard</h1>
      <div>
      <a href="ubah_password.php">Ubah Password</a>
      <a href="logout.php">Logout</a>
      </div>
    </div>

    <div style="margin-bottom: 20px; background-color: #e6f2ff; padding: 15px 20px; border-left: 6px solid #003366; border-radius: 6px; color: #003366;">
  Hai, selamat datang! Berikut ini merupakan pengajuan reimbursement yang perlu disetujui.
</div>

      <table>
  <thead>
    <tr>
      <th>Aksi</th>
      <th>ID Pengajuan</th>
      <th>Nama</th>
      <th>Posisi</th>
      <th>Divisi</th>
      <th>Project</th>
      <th>Jenis Pengeluaran</th>
      <th>Nominal</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
    <?php while ($row = $result->fetch_assoc()) { ?>
    <tr>
<td>
  <a href="verifikasi_pembayaran.php?id=<?= $row['id_pengajuan'] ?>" class="btn" style="padding: 6px 12px; font-size: 13px;">Proses</a>
</td>
      <td style="text-align: center;"><?= htmlspecialchars($row['id_pengajuan']) ?></td>
      <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
      <td><?= htmlspecialchars($row['posisi']) ?></td>
      <td><?= htmlspecialchars($row['divisi']) ?></td>
      <td><?= htmlspecialchars($row['project']) ?></td>
      <td><?= htmlspecialchars($row['nama_pengeluaran']) ?></td>
      <td>Rp<?= number_format($row['nominal'], 0, ',', '.') ?></td>
      <td><?= htmlspecialchars($row['status'] ?? '-') ?></td>
    </tr>
    <?php } ?>
  </tbody>
</table>
  </div>

  <?php if ($message): ?>
  <div id="notification"></div>
  <script>
    (function(){
      const notification = document.getElementById('notification');
      const message = <?= json_encode($message) ?>;
      const isSuccess = message.toLowerCase().includes('berhasil');

      notification.textContent = message;
      notification.classList.add('show');
      notification.classList.add(isSuccess ? 'success' : 'error');

      setTimeout(() => {
        notification.classList.remove('show');
      }, 4000);
    })();
  </script>
  <?php endif; ?>

</body>
</html>

