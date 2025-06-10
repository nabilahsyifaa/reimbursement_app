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

// Ambil flash message jika ada
$message = "";
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

// Ambil data posisi (hanya yang belum dihapus)
$positions = [];
$sqlPosisi = "SELECT p.id_posisi, p.nama_posisi, d.nama_divisi 
              FROM positions p
              JOIN divisions d ON p.id_divisi = d.id_divisi
              WHERE p.deleted_at IS NULL";
$resultPosisi = $conn->query($sqlPosisi);
if ($resultPosisi && $resultPosisi->num_rows > 0) {
    while ($row = $resultPosisi->fetch_assoc()) {
        $positions[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_lengkap = $_POST['nama_lengkap'] ?? '';
    $email = $_POST['email'] ?? '';
    $no_telepon = $_POST['no_telepon'] ?? '';
    $nik = $_POST['nik'] ?? '';
    $bank = $_POST['bank'] ?? '';
    $no_rekening = $_POST['no_rekening'] ?? '';
    $id_posisi = $_POST['id_posisi'] ?? '';
    $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
    $status = isset($_POST['status']) ? 1 : 0;

    // Validasi posisi
    if (empty($id_posisi)) {
        $_SESSION['flash_message'] = "Pilih posisi terlebih dahulu.";
        header("Location: master_user.php");
        exit();
    }

    // Ambil id_divisi dan id_role berdasarkan id_posisi
    $id_divisi = null;
    $id_role = null;
    $stmt = $conn->prepare("SELECT id_divisi, id_role FROM positions WHERE id_posisi = ?");
    $stmt->bind_param("s", $id_posisi);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $id_divisi = $row['id_divisi'];
        $id_role = $row['id_role'];
    }
    $stmt->close();

    if (empty($id_divisi) || empty($id_role)) {
        $_SESSION['flash_message'] = "Divisi atau role untuk posisi tersebut tidak ditemukan.";
        header("Location: master_user.php");
        exit();
    }

    // Cek duplikasi email atau NIK
    $check = $conn->prepare("SELECT * FROM users WHERE (email = ? OR nik = ?) AND deleted_at IS NULL");
    $check->bind_param("ss", $email, $nik);
    $check->execute();
    $checkResult = $check->get_result();

    if ($checkResult->num_rows > 0) {
        $_SESSION['flash_message'] = "Gagal menambahkan user: Email atau NIK sudah terdaftar.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    $check->close();

    // Simpan data ke database
    $stmt = $conn->prepare("INSERT INTO users (nama_lengkap, email, no_telepon, nik, bank, no_rekening, id_posisi, id_divisi, id_role, password, status)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssisi", $nama_lengkap, $email, $no_telepon, $nik, $bank, $no_rekening, $id_posisi, $id_divisi, $id_role, $password, $status);

    if ($stmt->execute()) {
        $_SESSION['flash_message'] = "User berhasil ditambahkan.";
    } else {
        $_SESSION['flash_message'] = "Terjadi kesalahan saat menyimpan data.";
    }

    $stmt->close();
    $conn->close();
    header("Location: master_user.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Tambah User Akses</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    /* style tetap sama seperti sebelumnya (tidak diubah) */
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
    <h1>Tambah User Akses</h1>
    <div>
      <a href="ubah_password.php">Ubah Password</a>
      <a href="logout.php">Logout</a>
    </div>
  </div>

  <form method="post" action="">
    <div class="form-group"><label>Nama Lengkap</label><input type="text" name="nama_lengkap" placeholder="Masukkan Nama Lengkap" required /></div>
    <div class="form-group"><label>Email</label><input type="email" name="email" placeholder="Masukkan Email" required /></div>
    <div class="form-group"><label>No. Telepon</label><input type="text" name="no_telepon" placeholder="Masukkan No. Telepon" required /></div>
    <div class="form-group"><label>NIK</label><input type="text" name="nik" placeholder="Masukkan NIK" required /></div>
    <div class="form-group"><label>Bank</label><input type="text" name="bank" placeholder="Masukkan Bank" required /></div>
    <div class="form-group"><label>No. Rekening</label><input type="text" name="no_rekening" placeholder="Masukkan No. Rekening" required /></div>

    <!-- Posisi -->
<div class="form-group">
  <label for="posisi">Posisi</label>
  <select id="posisi" name="id_posisi">
    <option value="">Pilih Posisi</option>
    <?php foreach ($positions as $pos): ?>
      <option value="<?= $pos['id_posisi'] ?>" data-divisi="<?= htmlspecialchars($pos['nama_divisi']) ?>">
        <?= htmlspecialchars($pos['nama_posisi']) ?>
      </option>
    <?php endforeach; ?>
  </select>
</div>


<!-- Divisi (hanya tampilan, tidak dikirim ke server) -->
<div class="form-group">
  <label>Divisi</label>
  <input type="text" id="divisi" disabled style="background-color: #eee;" />
</div>

    <div class="form-group"><label>Password</label><input type="password" name="password" placeholder="Masukkan Password" required /></div>
    <div class="form-group">
      <label>Status</label>
      <div class="toggle">
        <input type="checkbox" name="status" checked />
        <span>Aktif</span>
      </div>
    </div>

    <div class="form-actions">
      <button type="button" onclick="window.location.href='master_user.php'">Kembali</button>
      <button type="submit">Simpan</button>
    </div>
  </form>
</div>

<script>
  document.getElementById('posisi').addEventListener('change', function () {
    const selected = this.options[this.selectedIndex];
    const divisi = selected.getAttribute('data-divisi') || '';
    document.getElementById('divisi').value = divisi;
  });
</script>

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