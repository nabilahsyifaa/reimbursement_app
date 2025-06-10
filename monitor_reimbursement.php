<?php
include 'db.php';
date_default_timezone_set('Asia/Jakarta');
session_start();

$message = "";

// Redirect jika belum login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

// Ambil data session
$user_id = $_SESSION['user_id'];
$namaLengkap = $_SESSION['nama'] ?? '';
$namaPosisi = $_SESSION['nama_posisi'] ?? '';

// Ambil daftar divisi
$divisiList = $conn->query("SELECT id_divisi, nama_divisi FROM divisions where deleted_at is null");

// Ambil daftar project
$projectList = $conn->query("SELECT id_project, nama_project FROM projects where deleted_at is null");

// Ambil daftar status (aktifitas)
$statusList = $conn->query("SELECT id_aktifitas, nama_aktifitas FROM aktifitas");

// Ambil daftar posisi
$posisiList = $conn->query("SELECT id_posisi, nama_posisi FROM positions where deleted_at is null");


// Ambil filter dari URL
$filterNama = $_GET['nama'] ?? '';
$filterDivisi = $_GET['divisi'] ?? '';
$filterProject = $_GET['project'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterPosisi = $_GET['posisi'] ?? '';

// Query dasar
$sql = "SELECT 
    l.id_log,
    l.id_pengajuan,
    u.nama_lengkap,
    s.nama_posisi,
    d.nama_divisi,
    pr.nama_project,
    j.nama_pengeluaran,
    p.nominal,
    a.nama_aktifitas
FROM log_pengajuan l
JOIN pengajuan p ON l.id_pengajuan = p.id_pengajuan
JOIN users u ON p.id_user = u.id_user
LEFT JOIN positions s ON u.id_posisi = s.id_posisi
LEFT JOIN divisions d ON u.id_divisi = d.id_divisi
LEFT JOIN projects pr ON p.id_project = pr.id_project
LEFT JOIN jenis_pengeluaran j ON p.id_pengeluaran = j.id_pengeluaran
LEFT JOIN aktifitas a ON l.id_aktifitas = a.id_aktifitas
WHERE 
    ((l.id_aktifitas IN (5,6)) OR (l.updated_at IS NULL AND l.id_aktifitas NOT IN (5,6)))
";

$params = [];
$types = "";

if ($filterNama !== "") {
    $sql .= " AND u.nama_lengkap LIKE ?";
    $params[] = "%$filterNama%";
    $types .= "s";
}
if ($filterDivisi !== "") {
    $sql .= " AND d.id_divisi = ?";
    $params[] = $filterDivisi;
    $types .= "i";
}
if ($filterProject !== "") {
    $sql .= " AND pr.id_project = ?";
    $params[] = $filterProject;
    $types .= "i";
}
if ($filterStatus !== "") {
    $sql .= " AND a.id_aktifitas = ?";
    $params[] = $filterStatus;
    $types .= "i";
}
if ($filterPosisi !== "") {
    $sql .= " AND s.id_posisi = ?";
    $params[] = $filterPosisi;
    $types .= "i";
}

$sql .= " ORDER BY l.created_at DESC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// ==== MODE EKSPOR EXCEL ====
if (isset($_GET['export']) && $_GET['export'] === 'true') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=monitor_reimbursement_" . date('Ymd_His') . ".xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "<table border='1'>";
    echo "<tr>
        <th>ID Pengajuan</th>
        <th>Nama</th>
        <th>Posisi</th>
        <th>Divisi</th>
        <th>Project</th>
        <th>Jenis Pengeluaran</th>
        <th>Nominal</th>
        <th>Status</th>
    </tr>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id_pengajuan']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nama_lengkap']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nama_posisi']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nama_divisi']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nama_project']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nama_pengeluaran']) . "</td>";
        echo "<td>" . number_format($row['nominal'], 0, ',', '.') . "</td>";
        echo "<td>" . htmlspecialchars($row['nama_aktifitas'] ?? '-') . "</td>";
        echo "</tr>";
    }

    echo "</table>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Monitor Reimbursement</title>
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
      margin-top: 15px;
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

 .filter-form {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 20px;
  align-items: center;
}

.filter-form input,
.filter-form select {
  padding: 8px 12px;
  border: 1px solid #ccc;
  border-radius: 6px;
  font-size: 14px;
  width: auto;         /* Biarkan ukurannya fleksibel */
  flex: 1 1 auto;       /* Biarkan membesar atau mengecil */
  max-width: 200px;     /* Batasi agar tidak terlalu besar */
}

.filter-form .btn {
  padding: 8px 16px;
  font-size: 14px;
  white-space: nowrap; /* Hindari button terpotong atau turun baris */
  height: 38px;         /* Samakan tinggi dengan input/select jika perlu */
}


@media (min-width: 768px) {
  .filter-form {
    flex-wrap: nowrap;
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

  <div class="sidebar">
    <img src="img/logo_adw.jpg" alt="Logo ADW" style="display: block; margin: 0 auto 10px; width: 80px; height: auto;" />
  <p>
  <?= htmlspecialchars($namaLengkap) ?><br>
  <small><?= htmlspecialchars($namaPosisi) ?></small>
</p>

  </div>

  <div class="main">
  <div class="topbar">
    <h1>Monitor Reimbursement</h1>
    <div>
      <a href="ubah_password.php">Ubah Password</a>
      <a href="logout.php">Logout</a>
    </div>
  </div>

  <!-- Filter Form -->
  <!-- Filter Form -->
<form method="GET" class="filter-form">
  <input type="text" name="nama" placeholder="Nama..." value="<?= htmlspecialchars($filterNama) ?>" />
  
  <select name="divisi">
    <option value="">-- Semua Divisi --</option>
    <?php while ($d = $divisiList->fetch_assoc()): ?>
      <option value="<?= $d['id_divisi'] ?>" <?= $filterDivisi == $d['id_divisi'] ? 'selected' : '' ?>>
        <?= $d['nama_divisi'] ?>
      </option>
    <?php endwhile; ?>
  </select>

  <select name="project">
    <option value="">-- Semua Project --</option>
    <?php while ($p = $projectList->fetch_assoc()): ?>
      <option value="<?= $p['id_project'] ?>" <?= $filterProject == $p['id_project'] ? 'selected' : '' ?>>
        <?= $p['nama_project'] ?>
      </option>
    <?php endwhile; ?>
  </select>

  <select name="status">
    <option value="">-- Semua Status --</option>
    <?php while ($s = $statusList->fetch_assoc()): ?>
      <option value="<?= $s['id_aktifitas'] ?>" <?= $filterStatus == $s['id_aktifitas'] ? 'selected' : '' ?>>
        <?= $s['nama_aktifitas'] ?>
      </option>
    <?php endwhile; ?>
  </select>

  <select name="posisi">
    <option value="">-- Semua Posisi --</option>
    <?php while ($po = $posisiList->fetch_assoc()): ?>
      <option value="<?= $po['id_posisi'] ?>" <?= $filterPosisi == $po['id_posisi'] ? 'selected' : '' ?>>
        <?= $po['nama_posisi'] ?>
      </option>
    <?php endwhile; ?>
  </select>

  <br> <br>
  <button type="submit" class="btn">Filter</button>
   <a href="export_pekerjaan_excel.php?export=true&<?= http_build_query($_GET) ?>" class="btn" style="margin-left: 10px;">Export Excel</a>
</form>
</form>

  <!-- Tabel -->
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
      <?php while ($row = $result->fetch_assoc()): ?>
      <tr>
        <td><a href="detail_reimbursement.php?id=<?= $row['id_pengajuan'] ?>" class="btn" style="padding: 6px 12px; font-size: 13px;">Detail</a></td>
        <td style="text-align: center;"><?= htmlspecialchars($row['id_pengajuan']) ?></td>
        <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
        <td><?= htmlspecialchars($row['nama_posisi']) ?></td>
        <td><?= htmlspecialchars($row['nama_divisi']) ?></td>
        <td><?= htmlspecialchars($row['nama_project']) ?></td>
        <td><?= htmlspecialchars($row['nama_pengeluaran']) ?></td>
        <td>Rp<?= number_format($row['nominal'], 0, ',', '.') ?></td>
        <td><?= htmlspecialchars($row['nama_aktifitas'] ?? '-') ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>


</body>
</html>