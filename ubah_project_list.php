<?php
date_default_timezone_set('Asia/Jakarta'); 
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

// Cek apakah ID project tersedia
if (!isset($_GET['id']) || empty($_GET['id'])) {
  $_SESSION['flash_message'] = "ID project tidak ditemukan.";
  header("Location: master_project.php");
  exit;
}

$id = (int) $_GET['id'];

// Ambil data project berdasarkan ID
$stmt = $conn->prepare("SELECT * FROM projects WHERE id_project = ? AND deleted_at IS NULL");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();

if (!$project) {
  $_SESSION['flash_message'] = "Project tidak ditemukan.";
  header("Location: master_project.php");
  exit;
}

// Ambil data Project Manager (role = 3)
$projectManagers = [];
$result = $conn->query("SELECT id_user, nama_lengkap FROM users WHERE id_role = 3 AND status = 1 AND deleted_at IS NULL");
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $projectManagers[] = $row;
  }
}

// Simpan perubahan jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $kode = trim($_POST['kode_project']);
  $nama = trim($_POST['nama_project']);
  $pm = $_POST['project_manager'];

  // Cek duplikat (kecuali ID yang sedang diedit)
  $cek = $conn->prepare("SELECT * FROM projects WHERE (kode_project = ? OR nama_project = ?) AND id_project != ? AND deleted_at IS NULL");
  $cek->bind_param("ssi", $kode, $nama, $id);
  $cek->execute();
  $duplikat = $cek->get_result();

  if ($duplikat->num_rows > 0) {
    $_SESSION['flash_message'] = "Kode atau nama project sudah digunakan.";
    header("Location: ubah_project_list.php?id=" . $id);
    exit;
  }

  // Update project
  $update = $conn->prepare("UPDATE projects SET kode_project = ?, nama_project = ?, id_pm = ?, updated_at = NOW() WHERE id_project = ?");
  $update->bind_param("ssii", $kode, $nama, $pm, $id);

  if ($update->execute()) {
    $_SESSION['flash_message'] = "Project berhasil diperbarui.";
  } else {
    $_SESSION['flash_message'] = "Gagal memperbarui project: " . $conn->error;
  }

  header("Location: master_project.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Ubah Project</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
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
      background-color: #f4f6f9;
    }

    .sidebar {
      width: 250px;
      background-color: #003366;
      color: white;
      padding: 20px;
    }

    .sidebar h2 {
      background-color: white;
      color: #003366;
      text-align: center;
      border-radius: 8px;
      padding: 10px;
      margin-bottom: 10px;
    }

    .sidebar p {
      text-align: center;
      font-size: 14px;
      margin-bottom: 20px;
    }

    .sidebar a {
      display: block;
      color: white;
      text-decoration: none;
      padding: 10px 15px;
      border-radius: 6px;
      margin-bottom: 8px;
      transition: background 0.2s;
    }

    .sidebar a:hover {
      background-color: #004080;
    }

    .main {
      flex: 1;
      padding: 30px 40px;
    }

    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }

    .topbar h1 {
      font-size: 24px;
      color: #003366;
    }

    .topbar a {
      color: #003366;
      font-weight: 600;
      margin-left: 20px;
      text-decoration: none;
    }

    form {
      background-color: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
      max-width: 650px;
    }

    .form-group {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
    }

    .form-group label {
      width: 150px;
      font-weight: 600;
      color: #333;
    }

    .form-group input,
    .form-group select {
      flex: 1;
      padding: 10px;
      font-size: 14px;
      border: 1px solid #ccc;
      border-radius: 6px;
      transition: border 0.2s;
    }

    .form-group input:focus,
    .form-group select:focus {
      border-color: #003366;
      outline: none;
    }

    .form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 15px;
      margin-top: 30px;
    }

    .form-actions button {
      padding: 10px 20px;
      border: none;
      border-radius: 6px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
    }

    .form-actions button:first-child {
      background-color: #e0e0e0;
    }

    .form-actions button:first-child:hover {
      background-color: #c2c2c2;
    }

    .form-actions button:last-child {
      background-color: #003366;
      color: white;
    }

    .form-actions button:last-child:hover {
      background-color: #002244;
    }

    #notification {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 15px 25px;
      border-radius: 8px;
      font-weight: 600;
      display: none;
      z-index: 1000;
    }

    #notification.success {
      background-color: #4CAF50;
      color: white;
    }

    #notification.error {
      background-color: #f44336;
      color: white;
    }

    #notification.show {
      display: block;
    }

    @media (max-width: 768px) {
      .form-group {
        flex-direction: column;
        align-items: flex-start;
      }

      .form-group label,
      .form-group input,
      .form-group select,
      .form-actions {
        width: 100%;
      }

      .form-actions {
        flex-direction: column;
        gap: 10px;
        align-items: stretch;
      }
    }
  </style>
</head>
<body>

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
      <h1>Ubah Project</h1>
      <div>
      <a href="ubah_password.php">Ubah Password</a>
      <a href="logout.php">Logout</a>
      </div>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
      <div id="notification" class="show <?= stripos($_SESSION['flash_message'], 'berhasil') !== false ? 'success' : 'error' ?>">
        <?= $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label for="kodeProject">Kode Project</label>
        <input
          type="text"
          id="kodeProject"
          name="kode_project"
          value="<?= htmlspecialchars($project['kode_project']) ?>"
          placeholder="Masukkan Kode Project"
          required
        />
      </div>

      <div class="form-group">
        <label for="namaProject">Nama Project</label>
        <input
          type="text"
          id="namaProject"
          name="nama_project"
          value="<?= htmlspecialchars($project['nama_project']) ?>"
          placeholder="Masukkan Nama Project"
          required
        />
      </div>

      <div class="form-group">
        <label for="projectManager">Project Manager</label>
        <select id="projectManager" name="project_manager" required>
          <option value="">Pilih Project Manager</option>
          <?php foreach ($projectManagers as $pm): ?>
            <option value="<?= htmlspecialchars($pm['id_user']) ?>"
              <?= $pm['id_user'] == $project['id_pm'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($pm['nama_lengkap']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-actions">
        <button type="button" onclick="window.location.href='master_project.php'">Kembali</button>
        <button type="submit">Simpan</button>
      </div>
    </form>
  </div>

</body>
</html>
