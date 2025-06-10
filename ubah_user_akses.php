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
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

// Cek edit mode
$is_edit = false;
$user_id = $_GET['id'] ?? null;
$edit_data = [];

if ($user_id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id_user = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $edit_data = $result->fetch_assoc();
        $is_edit = true;
    }
    $stmt->close();
}

// Ambil data posisi
$positions = [];
$query = "SELECT p.id_posisi, p.nama_posisi, d.nama_divisi 
          FROM positions p
          LEFT JOIN divisions d ON p.id_divisi = d.id_divisi
          WHERE p.deleted_at IS NULL
          ORDER BY p.nama_posisi ASC";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $positions[] = $row;
    }
}

// Tangani submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_user = $_POST['id_user'] ?? null;
    $nama_lengkap = $_POST['nama_lengkap'] ?? '';
    $email = $_POST['email'] ?? '';
    $no_telepon = $_POST['no_telepon'] ?? '';
    $nik = $_POST['nik'] ?? '';
    $bank = $_POST['bank'] ?? '';
    $no_rekening = $_POST['no_rekening'] ?? '';
    $id_posisi = $_POST['posisi'] ?? '';
    $password_input = $_POST['password'] ?? '';
    $status = isset($_POST['status']) ? 1 : 0;

    if (empty($id_posisi)) {
        $message = "Pilih posisi terlebih dahulu.";
    } else {
        $stmt = $conn->prepare("SELECT id_divisi, id_role FROM positions WHERE id_posisi = ?");
        $stmt->bind_param("s", $id_posisi);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $id_divisi = $row['id_divisi'];
            $id_role = $row['id_role'];
        }
        $stmt->close();

        if (!$id_divisi || !$id_role) {
            $message = "Divisi atau role untuk posisi tidak ditemukan.";
        } else {
            $now = date('Y-m-d H:i:s');

            // VALIDASI NIK & EMAIL SAAT EDIT
            $check_stmt = $conn->prepare("SELECT id_user FROM users WHERE (email = ? OR nik = ?) AND id_user != ? AND deleted_at IS NULL");
            $check_stmt->bind_param("ssi", $email, $nik, $id_user);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $message = "Gagal menyimpan: Email atau NIK sudah digunakan oleh user lain.";
                $check_stmt->close();
            } else {
                $check_stmt->close();

                if ($id_user) {
                    // UPDATE USER
                    if (!empty($password_input)) {
                        $password = password_hash($password_input, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET nama_lengkap=?, email=?, no_telepon=?, nik=?, bank=?, no_rekening=?, id_posisi=?, id_divisi=?, id_role=?, password=?, status=?, updated_at=? WHERE id_user=?");
                        $stmt->bind_param("ssssssssisssi", $nama_lengkap, $email, $no_telepon, $nik, $bank, $no_rekening, $id_posisi, $id_divisi, $id_role, $password, $status, $now, $id_user);
                    } else {
                        $stmt = $conn->prepare("UPDATE users SET nama_lengkap=?, email=?, no_telepon=?, nik=?, bank=?, no_rekening=?, id_posisi=?, id_divisi=?, id_role=?, status=?, updated_at=? WHERE id_user=?");
                        $stmt->bind_param("sssssssisssi", $nama_lengkap, $email, $no_telepon, $nik, $bank, $no_rekening, $id_posisi, $id_divisi, $id_role, $status, $now, $id_user);
                    }

                    if ($stmt->execute()) {
                        $_SESSION['flash_message'] = "User berhasil diperbarui.";
                        $stmt->close();
                        header("Location: master_user.php");
                        exit();
                    } else {
                        $message = "Gagal memperbarui user: " . $stmt->error;
                        $stmt->close();
                    }

                } else {
                    // TAMBAH USER
                    $check = $conn->prepare("SELECT id_user FROM users WHERE (email = ? OR nik = ?) AND deleted_at IS NULL");
                    $check->bind_param("ss", $email, $nik);
                    $check->execute();
                    $checkResult = $check->get_result();

                    if ($checkResult->num_rows > 0) {
                        $_SESSION['flash_message'] = "Gagal menambahkan user: Email atau NIK sudah terdaftar.";
                        $check->close();
                        header("Location: master_user.php");
                        exit();
                    } else {
                        $check->close();

                        $password = password_hash($password_input, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("INSERT INTO users (nama_lengkap, email, no_telepon, nik, bank, no_rekening, id_posisi, id_divisi, id_role, password, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("sssssssissis", $nama_lengkap, $email, $no_telepon, $nik, $bank, $no_rekening, $id_posisi, $id_divisi, $id_role, $password, $status, $now);

                        if ($stmt->execute()) {
                            $_SESSION['flash_message'] = "User berhasil ditambahkan.";
                            $stmt->close();
                            header("Location: master_user.php");
                            exit();
                        } else {
                            $message = "Gagal menambahkan user: " . $stmt->error;
                            $stmt->close();
                        }
                    }
                }
            }
        }
    }
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
    <?php if ($is_edit): ?>
  <input type="hidden" name="id_user" value="<?= htmlspecialchars($edit_data['id_user']) ?>" />
<?php endif; ?>

<div class="form-group"><label>Nama Lengkap</label>
  <input type="text" name="nama_lengkap" placeholder="Masukkan Nama Lengkap" required
    value="<?= htmlspecialchars($edit_data['nama_lengkap'] ?? '') ?>" />
</div>

<div class="form-group"><label>Email</label>
  <input type="email" name="email" placeholder="Masukkan Email" required
    value="<?= htmlspecialchars($edit_data['email'] ?? '') ?>" />
</div>

<div class="form-group"><label>No. Telepon</label>
  <input type="text" name="no_telepon" placeholder="Masukkan No. Telepon" required
    value="<?= htmlspecialchars($edit_data['no_telepon'] ?? '') ?>" />
</div>

<div class="form-group"><label>NIK</label>
  <input type="text" name="nik" placeholder="Masukkan NIK" required
    value="<?= htmlspecialchars($edit_data['nik'] ?? '') ?>" />
</div>

<div class="form-group"><label>Bank</label>
  <input type="text" name="bank" placeholder="Masukkan Bank" required
    value="<?= htmlspecialchars($edit_data['bank'] ?? '') ?>" />
</div>

<div class="form-group"><label>No. Rekening</label>
  <input type="text" name="no_rekening" placeholder="Masukkan No. Rekening" required
    value="<?= htmlspecialchars($edit_data['no_rekening'] ?? '') ?>" />
</div>

<!-- Posisi -->
<div class="form-group">
  <label>Posisi</label>
  <select name="posisi" id="posisi" required>
    <option value="">Pilih Posisi</option>
    <?php foreach ($positions as $pos): ?>
      <option value="<?= $pos['id_posisi'] ?>" data-divisi="<?= htmlspecialchars($pos['nama_divisi']) ?>"
        <?= isset($edit_data['id_posisi']) && $edit_data['id_posisi'] == $pos['id_posisi'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($pos['nama_posisi']) ?>
      </option>
    <?php endforeach; ?>
  </select>
</div>

<!-- Divisi (tampilan saja) -->
<div class="form-group">
  <label>Divisi</label>
  <input type="text" id="divisi" disabled style="background-color: #eee;"
    value="<?php
      if ($is_edit && !empty($positions)) {
          foreach ($positions as $p) {
              if ($p['id_posisi'] == $edit_data['id_posisi']) {
                  echo htmlspecialchars($p['nama_divisi']);
                  break;
              }
          }
      }
    ?>" />
</div>

<div class="form-group"><label>Password</label>
  <input type="password" name="password" placeholder="Masukkan Password" <?= $is_edit ? '' : 'required' ?> />
</div>

<div class="form-group">
  <label>Status</label>
  <div class="toggle">
    <input type="checkbox" name="status"
      <?= (isset($edit_data['status']) && $edit_data['status'] == 1) || !$is_edit ? 'checked' : '' ?> />
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

<script>
  // Auto-trigger divisi saat halaman load
  window.addEventListener('DOMContentLoaded', function () {
    const posisiSelect = document.getElementById('posisi');
    const selectedOption = posisiSelect.options[posisiSelect.selectedIndex];
    const divisi = selectedOption.getAttribute('data-divisi') || '';
    document.getElementById('divisi').value = divisi;
  });
</script>



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

