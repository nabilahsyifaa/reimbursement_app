<?php
include 'db.php';
session_start();
date_default_timezone_set('Asia/Jakarta');

$message = "";

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

$userId = $_SESSION['user_id'];
$id_pengajuan = $_GET['id'] ?? null;

if (!$id_pengajuan) {
    echo "ID pengajuan tidak ditemukan.";
    exit();
}

// Ambil detail pengajuan
$sql = "SELECT p.*, 
       u.nama_lengkap, 
       u.bank,
       u.no_rekening,
       j.nama_pengeluaran,
       d.nama_divisi,
       s.nama_posisi,
       pr.nama_project,
       pm.nama_lengkap AS nama_pm,
       pr.id_pm
FROM pengajuan p
JOIN users u ON p.id_user = u.id_user
LEFT JOIN jenis_pengeluaran j ON p.id_pengeluaran = j.id_pengeluaran
LEFT JOIN divisions d ON u.id_divisi = d.id_divisi
LEFT JOIN positions s ON u.id_posisi = s.id_posisi
LEFT JOIN projects pr ON p.id_project = pr.id_project
LEFT JOIN users pm ON pr.id_pm = pm.id_user
WHERE p.id_pengajuan = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_pengajuan);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Data pengajuan tidak ditemukan.";
    exit();
}

$data = $result->fetch_assoc();

// Variabel untuk digunakan di form
$namaLengkap = $data['nama_lengkap'];
$namaPosisi = $data['nama_posisi'];
$modeProses = true; // form ini hanya untuk persetujuan, bukan edit isian utama

// Ambil list project dan jenis pengeluaran (jika dibutuhkan di dropdown)
$projects = [];
$pengeluarans = [];
$aksiList = [];

// Project
$projRes = $conn->query("SELECT pr.id_project, pr.nama_project, u.nama_lengkap AS nama_pm, pr.id_pm
FROM projects pr
LEFT JOIN users u ON pr.id_pm = u.id_user");
while ($row = $projRes->fetch_assoc()) {
    $projects[] = $row;
}

// Jenis pengeluaran
$pengeluaranRes = $conn->query("SELECT id_pengeluaran, nama_pengeluaran FROM jenis_pengeluaran");
while ($row = $pengeluaranRes->fetch_assoc()) {
    $pengeluarans[] = $row;
}

// Aksi log
$aksiRes = $conn->query("SELECT id_aksi, nama_aksi FROM aksi WHERE id_role = 3");
while ($row = $aksiRes->fetch_assoc()) {
    $aksiList[] = $row;
}

// Proses form keputusan PM
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_aksi = $_POST['id_aksi'] ?? null;
    $komentar = $_POST['komentar'] ?? '';
    $lampiran = $_FILES['lampiran_komentar'] ?? null;
    $fileName = null;

    if ($lampiran && $lampiran['error'] == 0) {
        $ext = pathinfo($lampiran['name'], PATHINFO_EXTENSION);
        $fileName = 'uploads/lampiran_' . time() . '.' . $ext;
        move_uploaded_file($lampiran['tmp_name'], $fileName);
    }

    $waktu = date('Y-m-d H:i:s');

    // Update log pengajuan
// UPDATE log_pengajuan yang benar
$stmtUpdate = $conn->prepare("
    UPDATE log_pengajuan 
    SET id_aktifitas = 5, id_aksi = ?, komentar = ?, lampiran_komentar = ?, updated_at = ?
    WHERE id_pengajuan = ? AND id_aktifitas = 2 AND id_aksi IS NULL
");
$stmtUpdate->bind_param("isssi", $id_aksi, $komentar, $fileName, $waktu, $id_pengajuan);
$updateSuccess = $stmtUpdate->execute();
$stmtUpdate->close();


    if ($updateSuccess) {
        // Jika disetujui (Setuju / id_aksi = 2), tambahkan log untuk finance
        if ($id_aksi == 2) {
            $id_aktifitas_finance = 3;

            $sqlFinance = "SELECT id_user FROM users WHERE id_role = 4 AND deleted_at IS NULL";
            $resultFinance = $conn->query($sqlFinance);

            if ($resultFinance && $resultFinance->num_rows > 0) {
                $stmtLog = $conn->prepare("INSERT INTO log_pengajuan 
                    (id_pengajuan, id_aktifitas, created_by, created_at) 
                    VALUES (?, ?, ?, ?)");
                
                while ($rowFinance = $resultFinance->fetch_assoc()) {
                    $id_finance = $rowFinance['id_user'];
                    $created_at = date("Y-m-d H:i:s");
                    $stmtLog->bind_param("iiis", $id_pengajuan, $id_aktifitas_finance, $id_finance, $created_at);
                    $stmtLog->execute();
                }
                $stmtLog->close();
            }
        }

        $_SESSION['flash_message'] = "Persetujuan berhasil diproses.";
        header("Location: daftar_pekerjaan_pm.php");
        exit;
    } else {
        $_SESSION['flash_message'] = "Gagal memproses persetujuan.";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}


// Ambil data log_pengajuan untuk ditampilkan di tabel
$logList = [];

$stmtLogs = $conn->prepare("SELECT l.*, 
                                   u.nama_lengkap AS nama_user,
                                   s.nama_posisi,
                                   a.nama_aktifitas,
                                   ak.nama_aksi
                            FROM log_pengajuan l
                            LEFT JOIN users u ON l.created_by = u.id_user
                            LEFT JOIN positions s ON u.id_posisi = s.id_posisi
                            LEFT JOIN aktifitas a ON l.id_aktifitas = a.id_aktifitas
                            LEFT JOIN aksi ak ON l.id_aksi = ak.id_aksi
                            WHERE l.id_pengajuan = ?
                            ORDER BY (l.id_aktifitas = 2) ASC, l.created_at ASC");
$stmtLogs->bind_param("i", $id_pengajuan);
$stmtLogs->execute();
$resLogs = $stmtLogs->get_result();
while ($row = $resLogs->fetch_assoc()) {
    $logList[] = $row;
}

?>



<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Persetujuan Project Manager</title>
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
      flex-shrink: 0;
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

        /* Tambahkan ini */
input[disabled],
select[disabled],
textarea[disabled] {
  background-color: #eee;
  cursor: not-allowed;
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
  <a href="dashboard_employee.php">Dashboard</a>
  <a href="#">Reimbursement</a>
  <a href="#">Pengajuan</a>
  <a href="#">Daftar Pekerjaan</a>
  <a href="#">Monitor</a>
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
    <h1>Persetujuan Project Manager</h1>
    <div>
      <a href="#">Ubah Password</a>
      <a href="#">Logout</a>
    </div>
  </div>

 <form class="form-container" method="POST" action="" enctype="multipart/form-data">
  <h5 style="margin-bottom: 15px; font-weight: bold;">Detail Reimbursement</h5>

  <div class="form-group">
    <label>ID Pengajuan</label>
    <input type="text" value="<?= htmlspecialchars($id_pengajuan) ?>" disabled>
  </div>

  <div class="form-group">
    <label>Nama</label>
    <input type="text" value="<?= htmlspecialchars($namaLengkap) ?>" disabled>
  </div>

  <div class="form-group">
    <label>Bank</label>
    <input type="text" value="<?= htmlspecialchars($data['bank']) ?>" disabled>
  </div>

  <div class="form-group">
    <label>No. Rekening</label>
    <input type="text" value="<?= htmlspecialchars($data['no_rekening']) ?>" disabled>
  </div>

  <div class="form-group">
    <label>Posisi</label>
    <input type="text" value="<?= htmlspecialchars($namaPosisi) ?>" disabled>
  </div>

  <div class="form-group">
    <label>Divisi</label>
    <input type="text" value="<?= htmlspecialchars($data['nama_divisi']) ?>" disabled>
  </div>

  <div class="form-group">
    <label>Project</label>
    <select name="id_project" id="id_project" onchange="updatePM()" disabled>
      <option value="">Pilih Project</option>
      <?php foreach ($projects as $project): ?>
        <option value="<?= $project['id_project'] ?>"
                data-pm="<?= htmlspecialchars($project['nama_pm']) ?>"
                data-id-pm="<?= $project['id_pm'] ?>"
                <?= $data['id_project'] == $project['id_project'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($project['nama_project']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <input type="hidden" name="id_project" value="<?= htmlspecialchars($data['id_project']) ?>">
  </div>

  <div class="form-group">
    <label>Project Manager</label>
    <input type="text" id="project_manager" name="project_manager" disabled value="<?= htmlspecialchars($data['nama_pm']) ?>">
    <input type="hidden" id="id_pm" name="id_pm" value="<?= htmlspecialchars($data['id_pm']) ?>">
  </div>

  <div class="form-group">
    <label>Jenis Pengeluaran</label>
    <select name="id_pengeluaran" disabled>
      <option value="">Pilih Jenis Pengeluaran</option>
      <?php foreach ($pengeluarans as $p): ?>
        <option value="<?= $p['id_pengeluaran'] ?>" <?= $data['id_pengeluaran'] == $p['id_pengeluaran'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($p['nama_pengeluaran']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <input type="hidden" name="id_pengeluaran" value="<?= htmlspecialchars($data['id_pengeluaran']) ?>">
  </div>

  <div class="form-group">
    <label>Nominal</label>
    <input type="number" name="nominal" placeholder="Nominal" value="<?= htmlspecialchars($data['nominal']) ?>" disabled>
    <input type="hidden" name="nominal" value="<?= htmlspecialchars($data['nominal']) ?>">
  </div>

<div class="form-group">
  <label>Bukti</label>
  <?php if (!empty($data['bukti'])): ?>
    <a href="<?= htmlspecialchars($data['bukti']) ?>" target="_blank">Lihat Bukti</a>
  <?php else: ?>
    <span class="text-muted">Tidak ada bukti terlampir</span>
  <?php endif; ?>
</div>


  <div class="form-group">
    <label>Catatan</label>
    <textarea name="catatan" rows="3" placeholder="Keterangan Tambahan" disabled><?= htmlspecialchars($data['catatan']) ?></textarea>
    <input type="hidden" name="catatan" value="<?= htmlspecialchars($data['catatan']) ?>">
  </div>

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
        <th>Nama</th>
        <th>Posisi</th>
        <th>Aktifitas</th>
        <th>Aksi</th>
        <th>Komentar</th>
        <th>Lampiran</th>
      </tr>
    </thead>
<tbody>
  <?php if (count($logList) > 0): ?>
    <?php foreach ($logList as $log): ?>
<tr>
  <td><?= date('d-m-Y H:i', strtotime($log['created_at'])) ?></td>
  <td>
    <?= $log['nama_aksi'] === null ? '-' : date('d-m-Y H:i', strtotime($log['updated_at'])) ?>
  </td>
  <td><?= htmlspecialchars($log['nama_user']) ?></td>
  <td><?= htmlspecialchars($log['nama_posisi']) ?></td>
  <td><?= htmlspecialchars($log['nama_aktifitas'] ?? '-') ?></td>
  <td><?= htmlspecialchars($log['nama_aksi'] ?? '-') ?></td>
  <td><?= nl2br(htmlspecialchars($log['komentar'])) ?></td>
<td>
  <?php if (!empty($log['lampiran_komentar'])): ?>
    <a href="<?= htmlspecialchars($log['lampiran_komentar']) ?>" target="_blank">Lihat Lampiran</a>
  <?php else: ?>
    <span class="text-muted">-</span>
  <?php endif; ?>
</td>

</tr>

    <?php endforeach; ?>
  <?php else: ?>
    <tr>
      <td colspan="8" class="text-center">Belum ada log pengajuan.</td>
    </tr>
  <?php endif; ?>
</tbody>

  </table>

  <div class="form-actions">
    <button type="button" onclick="window.history.back()">Kembali</button>
    <button type="submit">Proses</button>
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


