<?php
include 'db.php';
date_default_timezone_set('Asia/Jakarta'); 

$message = "";

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Ambil data dari session
$user_id = $_SESSION['user_id'];
$namaLengkap = $_SESSION['nama'] ?? 'User';
$namaPosisi = $_SESSION['nama_posisi'] ?? 'Posisi';

if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}


// Ambil daftar project dan ID user project manager-nya
$projects = [];
$sqlProjects = "SELECT p.id_project, p.nama_project, p.id_pm, u.nama_lengkap AS nama_pm
                FROM projects p
                LEFT JOIN users u ON p.id_pm = u.id_user
                WHERE p.deleted_at IS NULL";
$resultProjects = $conn->query($sqlProjects);
if ($resultProjects && $resultProjects->num_rows > 0) {
    while ($row = $resultProjects->fetch_assoc()) {
        $projects[] = $row;
    }
}

// Ambil daftar jenis pengeluaran
$pengeluarans = [];
$sqlPengeluaran = "SELECT id_pengeluaran, nama_pengeluaran FROM jenis_pengeluaran";
$resultPengeluaran = $conn->query($sqlPengeluaran);
if ($resultPengeluaran && $resultPengeluaran->num_rows > 0) {
    while ($row = $resultPengeluaran->fetch_assoc()) {
        $pengeluarans[] = $row;
    }
}

// Ambil daftar aksi untuk role id_role = 2
$aksiList = [];
$sqlAksi = "SELECT id_aksi, nama_aksi 
            FROM aksi 
            WHERE id_role = 2";
$resultAksi = $conn->query($sqlAksi);
if ($resultAksi && $resultAksi->num_rows > 0) {
    while ($row = $resultAksi->fetch_assoc()) {
        $aksiList[] = $row;
    }
}


// Ambil data user dari DB
$sql = "SELECT u.*, d.nama_divisi 
        FROM users u 
        LEFT JOIN divisions d ON u.id_divisi = d.id_divisi 
        WHERE u.id_user = ? AND u.deleted_at IS NULL";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Tangani simpan pengajuan jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_user = $_SESSION['user_id'];
    $id_project = $_POST['id_project'] ?? null;
    $id_pm = $_POST['id_pm'] ?? null;
    $id_pengeluaran = $_POST['id_pengeluaran'] ?? null;
    $nominal = $_POST['nominal'] ?? 0;
    $catatan = $_POST['keterangan'] ?? '';
    $id_aksi = $_POST['id_aksi'] ?? null;

 $buktiPath = '';
if (!empty($_FILES['bukti']['name'])) {
    // Validasi ukuran maksimal 2MB
    if ($_FILES['bukti']['size'] > 2 * 1024 * 1024) {
        $_SESSION['flash_message'] = "Ukuran file bukti tidak boleh lebih dari 2MB.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $uploadDir = 'uploads/bukti/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $buktiFile = time() . '_' . basename($_FILES['bukti']['name']);
    $buktiPath = $uploadDir . $buktiFile;

    move_uploaded_file($_FILES['bukti']['tmp_name'], $buktiPath);
}

    $stmt = $conn->prepare("INSERT INTO pengajuan (id_user, id_project, id_pm, id_pengeluaran, nominal, bukti, catatan, id_aksi) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiisssi", $id_user, $id_project, $id_pm, $id_pengeluaran, $nominal, $buktiPath, $catatan, $id_aksi);

 if (!$stmt->execute()) {
    $_SESSION['flash_message'] = "Gagal menambahkan pengajuan: " . $stmt->error;
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$id_pengajuan = $stmt->insert_id;
$stmt->close();

// Simpan ke log_pengajuan jika ada komentar/lampiran
$lampiranKomentarPath = '';
$komentar = $_POST['komentar'] ?? ''; // ← tambahkan ini jika komentar diperlukan

if (!empty($_FILES['lampiran_komentar']['name'])) {
    if ($_FILES['lampiran_komentar']['size'] > 2 * 1024 * 1024) {
        $_SESSION['flash_message'] = "Ukuran file lampiran komentar tidak boleh lebih dari 2MB.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $lampiranDir = 'uploads/komentar/';
    if (!is_dir($lampiranDir)) {
        mkdir($lampiranDir, 0777, true);
    }

    $lampiranKomentarFile = time() . '_' . basename($_FILES['lampiran_komentar']['name']);
    $lampiranKomentarPath = $lampiranDir . $lampiranKomentarFile;
    move_uploaded_file($_FILES['lampiran_komentar']['tmp_name'], $lampiranKomentarPath);
}

$id_aktifitas = 1;
$created_at = date("Y-m-d H:i:s");
$updated_at = $created_at;

$stmtLog = $conn->prepare("INSERT INTO log_pengajuan 
    (id_pengajuan, id_aksi, id_aktifitas, komentar, lampiran_komentar, created_by, created_at, updated_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmtLog->bind_param("iiississ", $id_pengajuan, $id_aksi, $id_aktifitas, $komentar, $lampiranKomentarPath, $id_user, $created_at, $updated_at);
$stmtLog->execute();
$stmtLog->close();

// Tambahkan log menunggu approval PM
$id_aktifitas_pm = 2;
$created_by_pm = $id_pm;
$created_at = date("Y-m-d H:i:s");
$stmtLog = $conn->prepare("INSERT INTO log_pengajuan 
    (id_pengajuan, id_aktifitas, created_by, created_at) 
    VALUES (?, ?, ?, ?)");
$stmtLog->bind_param("iiis", $id_pengajuan, $id_aktifitas_pm, $created_by_pm, $created_at);
$stmtLog->execute();
$stmtLog->close();

$_SESSION['flash_message'] = "Pengajuan berhasil ditambahkan.";
header("Location: " . $_SERVER['PHP_SELF']);
exit;
    }


?>


<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Pengajuan Reimbursement</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
   <!-- Tambahkan ini: Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
      flex-shrink: 0;j
    }

    .sidebar img {
      display: block;
      margin: 0 auto 10px;
      width: 80px;
      height: auto;
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

    .form-container {
      background-color: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
      max-width: 100%;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      font-weight: 600;
      color: #333;
      margin-bottom: 6px;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 10px;
      font-size: 14px;
      border: 1px solid #ccc;
      border-radius: 6px;
      transition: border 0.2s;
    }

    /* Tambahkan ini */
input[disabled],
select[disabled],
textarea[disabled] {
  background-color: #eee;
  cursor: not-allowed;
}

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
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

    @media (max-width: 1024px) {
      .form-container {
        max-width: 100%;
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
    }
  </style>
</head>
<body>

  <div class="sidebar">
    <img src="img/logo_adw.jpg" alt="Logo ADW" />
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

<?php if (!empty($message)): ?>
  <div id="notification" class="show success">
    <?= htmlspecialchars($message) ?>
  </div>
  <script>
    setTimeout(() => {
      document.getElementById('notification').classList.remove('show');
    }, 3000);
  </script>
<?php endif; ?>


    <div class="topbar">
      <h1>Pengajuan Reimbursement</h1>
      <div>
      <a href="ubah_password.php">Ubah Password</a>
      <a href="logout.php">Logout</a>
      </div>
    </div>

    <form class="form-container" method="POST" action=" " enctype="multipart/form-data">
          <h5 style="margin-bottom: 15px; font-weight: bold;">Detail Reimbursement</h5>
  <div class="form-group">
    <label>Nama</label>
    <input type="text" value="<?= htmlspecialchars($namaLengkap) ?>" disabled>
  </div>

  <div class="form-group">
    <label>Bank</label>
    <input type="text" value="<?= htmlspecialchars($user['bank'] ?? '') ?>" disabled>
  </div>

  <div class="form-group">
    <label>No. Rekening</label>
    <input type="text" value="<?= htmlspecialchars($user['no_rekening'] ?? '') ?>" disabled>
  </div>

  <div class="form-group">
    <label>Posisi</label>
    <input type="text" value="<?= htmlspecialchars($namaPosisi) ?>" disabled>
  </div>

  <div class="form-group">
    <label>Divisi</label>
    <input type="text" value="<?= htmlspecialchars($user['nama_divisi'] ?? '') ?>" disabled>
  </div>

  <div class="form-group">
  <label>Project</label>
  <select name="id_project" id="id_project" onchange="updatePM()" required>
    <option value="">Pilih Project</option>
    <?php foreach ($projects as $project): ?>
      <option value="<?= $project['id_project'] ?>" data-pm="<?= htmlspecialchars($project['nama_pm']) ?>" data-id-pm="<?= $project['id_pm'] ?>">
        <?= htmlspecialchars($project['nama_project']) ?>
      </option>
    <?php endforeach; ?>
  </select>
</div>

<div class="form-group">
  <label>Project Manager</label>
  <input type="text" id="project_manager" name="project_manager" disabled>
  <!-- Jika ingin dikirim ke server -->
  <input type="hidden" id="id_pm" name="id_pm">
</div>


  <div class="form-group">
  <label>Jenis Pengeluaran</label>
  <select name="id_pengeluaran" required>
    <option value="">Pilih Jenis Pengeluaran</option>
    <?php foreach ($pengeluarans as $p): ?>
      <option value="<?= $p['id_pengeluaran'] ?>"><?= htmlspecialchars($p['nama_pengeluaran']) ?></option>
    <?php endforeach; ?>
  </select>
</div>


  <div class="form-group">
    <label>Nominal</label>
<input type="text" name="nominal" id="nominal" placeholder="Nominal" required oninput="formatRupiah(this)">
  </div>

<div class="form-group">
  <label>Bukti</label>
  <input type="file" name="bukti" accept=".jpg,.jpeg,.png,.pdf" required>
  <small class="form-text text-muted">
    Hanya file dengan format <strong>JPG, JPEG, PNG, atau PDF</strong> yang diperbolehkan.
  </small>
</div>

  <div class="form-group">
    <label>Catatan</label>
    <textarea name="keterangan" rows="3" placeholder="Keterangan Tambahan"></textarea>
  </div> <br>

  <h5 style="margin-top: 30px; margin-bottom: 15px; font-weight: bold;">Komentar</h5>

<div class="form-group">
  <label>Pilih Aksi</label>
  <select name="id_aksi" required>
    <option value="">Pilih Aksi</option>
    <?php foreach ($aksiList as $aksi): ?>
      <option value="<?= $aksi['id_aksi'] ?>"><?= htmlspecialchars($aksi['nama_aksi']) ?></option>
    <?php endforeach; ?>
  </select>
</div>

<div class="form-group">
  <label>Komentar</label>
  <textarea name="komentar" rows="2" placeholder="Komentar untuk log pengajuan" required></textarea>
</div>

<div class="form-group">
  <label>Lampiran Komentar</label>
  <input type="file" name="lampiran_komentar">
</div>

<table class="table table-bordered table-striped">
  <thead class="table-light">
    <tr>
      <th>Tanggal Mulai</th>
      <th>Tanggal Selesai</th>
      <th>Aktifitas</th>
      <th>Aksi</th>
      <th>Komentar</th>
      <th>Lampiran</th>
    </tr>
  </thead>
  <tbody>
    <!-- Baris data -->
  </tbody>
</table>



  <div class="form-actions">
    <button type="button" onclick="window.history.back()">Kembali</button>
    <button type="submit">Simpan</button>
  </div>
</form>

  </div>

<script>
function updatePM() {
  const select = document.getElementById("id_project");
  const selectedOption = select.options[select.selectedIndex];

  const namaPM = selectedOption.getAttribute("data-pm") || "";
  const idPM = selectedOption.getAttribute("data-id-pm") || "";

  document.getElementById("project_manager").value = namaPM;
  document.getElementById("id_pm").value = idPM;
}
</script>

<script>
function formatRupiah(input) {
  let value = input.value.replace(/[^,\d]/g, '').toString();
  let split = value.split(',');
  let sisa = split[0].length % 3;
  let rupiah = split[0].substr(0, sisa);
  let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

  if (ribuan) {
    let separator = sisa ? '.' : '';
    rupiah += separator + ribuan.join('.');
  }

  rupiah = split[1] !== undefined ? rupiah + ',' + split[1] : rupiah;
  input.value = rupiah ? 'Rp ' + rupiah : '';
}
</script>

<script>
document.querySelector("form").addEventListener("submit", function(e) {
  const nominalInput = document.getElementById("nominal");
  const rawNominal = nominalInput.value.replace(/[^0-9]/g, ''); // hanya angka
  nominalInput.value = rawNominal;
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
