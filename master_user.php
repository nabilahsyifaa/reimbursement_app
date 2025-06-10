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

// Ambil data dari tabel users dengan join ke tabel divisi dan posisi
$query = "
SELECT users.*, divisions.nama_divisi, positions.nama_posisi
FROM users
LEFT JOIN divisions ON users.id_divisi = divisions.id_divisi
LEFT JOIN positions ON users.id_posisi = positions.id_posisi
WHERE users.deleted_at IS NULL
ORDER BY users.created_at DESC
";
$result = $conn->query($query);

// Ambil pesan notifikasi jika ada
$message = '';
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>User Akses</title>
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
      }
    }

    /* Notifikasi */
    #notification {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 12px 20px;
      border-radius: 6px;
      font-weight: 500;
      display: none;
      z-index: 9999;
      color: white;
      box-shadow: 0 2px 6px rgba(0,0,0,0.2);
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
  <a href="dashboard_administrator.php">Dashboard</a>
  <a href="master_user.php">User Akses</a>
  <a href="master_divisi.php">Master Divisi</a>
  <a href="master_posisi.php">Master Posisi</a>
  <a href="master_project.php">Project List</a>
  <a href="pengajuan_reimbursement_admin.php">Pengajuan Reimbursement</a>
  <a href="monitor_reimbursement.php">Monitor Rembursement</a>
  </div>

  <div class="main">
    <div class="topbar">
      <h1>User Akses</h1>
      <div>
      <a href="ubah_password.php">Ubah Password</a>
      <a href="logout.php">Logout</a>
      </div>
    </div>

    <!-- NOTIFIKASI DIV TAMBAHAN -->
    <div id="notification"></div>

    <?php if (!empty($message)): ?>
    <script>
      (function(){
        const notification = document.getElementById('notification');
        const message = <?= json_encode($message) ?>;
        const isSuccess = message.toLowerCase().includes('berhasil');

        notification.textContent = message;
        notification.style.backgroundColor = isSuccess ? '#28a745' : '#dc3545';
        notification.style.display = 'block';

        setTimeout(() => {
          notification.style.display = 'none';
        }, 4000);
      })();
    </script>
    <?php endif; ?>

    <button class="btn" onclick="window.location.href='tambah_user_akses.php'">
      <i class="fas fa-plus"></i> Tambah
    </button>

    <table>
      <thead>
        <tr>
          <th>Aksi</th>
          <th>Nama</th>
          <th>Email</th>
          <th>NIK</th>
          <th>No. Telepon</th>
          <th>Posisi</th>
          <th>Divisi</th>
          <th>Bank</th>
          <th>No. Rekening</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while($row = $result->fetch_assoc()): ?>
            <tr>
              <td>
                <a href="ubah_user_akses.php?id=<?= $row['id_user'] ?>"><i class="fas fa-pen icon" title="Edit"></i></a>
                <a href="hapus_user_akses.php?id=<?= $row['id_user'] ?>" onclick="return confirm('Yakin ingin menghapus user ini?');"><i class="fas fa-trash icon" title="Hapus"></i></a>
              </td>
              <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
              <td><?= htmlspecialchars($row['email']) ?></td>
              <td><?= htmlspecialchars($row['nik']) ?></td>
              <td><?= htmlspecialchars($row['no_telepon']) ?></td>
              <td><?= htmlspecialchars($row['nama_posisi']) ?></td>
              <td><?= htmlspecialchars($row['nama_divisi']) ?></td>
              <td><?= htmlspecialchars($row['bank']) ?></td>
              <td><?= htmlspecialchars($row['no_rekening']) ?></td>
              <td><?= $row['status'] ? 'Aktif' : 'Nonaktif' ?></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="10" style="text-align: center;">Belum ada data user.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</body>
</html>