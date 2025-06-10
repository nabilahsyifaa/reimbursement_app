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

$message = "";

// Ambil pesan dari session jika ada
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

// Ambil data role
$roleOptions = "";
$sqlRole = "SELECT id_role, nama_role FROM roles";
$resultRole = $conn->query($sqlRole);
if ($resultRole && $resultRole->num_rows > 0) {
    while ($row = $resultRole->fetch_assoc()) {
        $roleOptions .= "<option value=\"{$row['id_role']}\">{$row['nama_role']}</option>\n";
    }
}

// Ambil data divisi (hanya yang belum dihapus)
$divisiOptions = "";
$sqlDivisions = "SELECT id_divisi, nama_divisi FROM divisions WHERE deleted_at IS NULL";
$resultDivisions = $conn->query($sqlDivisions);
if ($resultDivisions && $resultDivisions->num_rows > 0) {
    while ($row = $resultDivisions->fetch_assoc()) {
        $divisiOptions .= "<option value=\"{$row['id_divisi']}\">{$row['nama_divisi']}</option>\n";
    }
}

// Proses simpan
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_posisi = trim($_POST['nama_posisi']);
    $id_role = $_POST['id_role'];
    $id_divisi = $_POST['id_divisi'];
    $created_at = date('Y-m-d H:i:s');

    if ($nama_posisi === "" || $id_role === "" || $id_divisi === "") {
        $_SESSION['flash_message'] = "Semua field harus diisi.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        // Cek duplikat nama posisi dalam divisi
        $checkQuery = $conn->prepare("SELECT COUNT(*) FROM positions WHERE nama_posisi = ? AND id_divisi = ? AND deleted_at IS NULL");
        $checkQuery->bind_param("si", $nama_posisi, $id_divisi);
        $checkQuery->execute();
        $checkQuery->bind_result($count);
        $checkQuery->fetch();
        $checkQuery->close();

        if ($count > 0) {
            $_SESSION['flash_message'] = "Nama posisi sudah ada di divisi tersebut.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

        // Insert jika tidak duplikat
        $stmt = $conn->prepare("INSERT INTO positions (id_divisi, nama_posisi, id_role, created_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isis", $id_divisi, $nama_posisi, $id_role, $created_at);

        if ($stmt->execute()) {
            $_SESSION['flash_message'] = "Posisi berhasil ditambahkan.";
            header("Location: master_posisi.php");
            exit;
        } else {
            $_SESSION['flash_message'] = "Gagal menambahkan posisi: " . $stmt->error;
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Tambah Posisi</title>
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
      max-width: 700px;
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
    <h1>Tambah Posisi</h1>
    <div>
      <a href="ubah_password.php">Ubah Password</a>
      <a href="logout.php">Logout</a>
    </div>
  </div>

  <form method="POST" action="">
    <div class="form-group">
      <label for="namaPosisi">Nama Posisi</label>
      <input type="text" id="namaPosisi" name="nama_posisi" placeholder="Masukkan Nama Posisi" required />
    </div>

    <div class="form-group">
      <label for="role">Role</label>
      <select id="role" name="id_role" required>
        <option value="">Pilih Role</option>
        <?php echo $roleOptions; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="divisi">Divisi</label>
      <select id="divisi" name="id_divisi" required>
        <option value="">Pilih Divisi</option>
        <?php echo $divisiOptions; ?>
      </select>
    </div>

    <div class="form-actions">
      <button type="button" onclick="window.location.href='master_posisi.php'">Kembali</button>
      <button type="submit">Simpan</button>
    </div>
  </form>
</div>

<div id="notification"></div>

<?php if ($message): ?>
<script>
  (function() {
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