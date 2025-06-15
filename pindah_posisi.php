<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data user dan posisi
$sql = "SELECT 
    u.id_posisi, u.id_posisi2, u.id_divisi, u.id_divisi2, u.id_role, u.id_role2,
    p1.nama_posisi AS posisi1,
    p2.nama_posisi AS posisi2
FROM users u
LEFT JOIN positions p1 ON u.id_posisi = p1.id_posisi
LEFT JOIN positions p2 ON u.id_posisi2 = p2.id_posisi
WHERE u.id_user = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "User tidak ditemukan.";
    exit();
}

$redirect = 'index.php'; // fallback default

// Ambil posisi saat ini
$isPosisiUtama = $_SESSION['is_posisi_utama'] ?? true;

if ($isPosisiUtama) {
    // Cek apakah memiliki posisi kedua
    if ($user['id_posisi2']) {
        // Pindah ke posisi kedua
        $_SESSION['id_posisi'] = $user['id_posisi2'];
        $_SESSION['id_divisi'] = $user['id_divisi2'];
        $_SESSION['id_role']   = $user['id_role2'];
        $_SESSION['nama_posisi'] = $user['posisi2'];
        $_SESSION['is_posisi_utama'] = false;

        $_SESSION['message'] = "Posisi berhasil diubah ke posisi {$user['posisi2']}.";

        // Tentukan dashboard sesuai role posisi kedua
        switch ($user['id_role2']) {
            case 1: $redirect = 'dashboard_administrator.php'; break;
            case 2: $redirect = 'dashboard_employee.php'; break;
            case 3: $redirect = 'dashboard_pm.php'; break;
            case 4: $redirect = 'dashboard_finance.php'; break;
        }
    } else {
        // Tidak punya posisi kedua
        $_SESSION['message'] = "Anda tidak memiliki posisi lainnya.";
        
        // Tetap redirect ke dashboard saat ini (utama)
        switch ($user['id_role']) {
            case 1: $redirect = 'dashboard_administrator.php'; break;
            case 2: $redirect = 'dashboard_employee.php'; break;
            case 3: $redirect = 'dashboard_pm.php'; break;
            case 4: $redirect = 'dashboard_finance.php'; break;
        }
    }

} else {
 // Kembali ke posisi utama
    $_SESSION['id_posisi'] = $user['id_posisi'];
    $_SESSION['id_divisi'] = $user['id_divisi'];
    $_SESSION['id_role']   = $user['id_role'];
    $_SESSION['nama_posisi'] = $user['posisi1'];
    $_SESSION['is_posisi_utama'] = true;

    // Tentukan dashboard sesuai role posisi utama
    switch ($user['id_role']) {
        case 1: $redirect = 'dashboard_administrator.php'; break;
        case 2: $redirect = 'dashboard_employee.php'; break;
        case 3: $redirect = 'dashboard_pm.php'; break;
        case 4: $redirect = 'dashboard_finance.php'; break;
    }

    // Flash message posisi utama
    $_SESSION['message'] = 'Berhasil kembali ke posisi utama ' . $user['posisi1'];
}

header("Location: $redirect");
exit();