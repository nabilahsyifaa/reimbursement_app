<?php
include 'db.php';
session_start();
date_default_timezone_set('Asia/Jakarta');

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

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

// Output Excel
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
?>
