<?php
session_start();
include 'db.php';

$error = ''; // Inisialisasi agar tidak undefined

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $sql = "SELECT u.id_user, u.nama_lengkap, u.id_role, u.id_posisi, u.password, p.nama_posisi
            FROM users u
            LEFT JOIN positions p ON u.id_posisi = p.id_posisi
            WHERE u.email = ? AND u.deleted_at IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            // Simpan data ke session
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['nama'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['id_role'];
            $_SESSION['id_posisi'] = $user['id_posisi'];
            $_SESSION['nama_posisi'] = $user['nama_posisi'];

            // Redirect sesuai role
            if ($user['id_role'] == 1) {
                header("Location: dashboard_administrator.php");
            } elseif ($user['id_role'] == 2) {
                header("Location: dashboard_employee.php");
            } elseif ($user['id_role'] == 3) {
                header("Location: dashboard_pm.php");
            } elseif ($user['id_role'] == 4) {
                header("Location: dashboard_finance.php");
            } else {
                $error = "Role tidak dikenali.";
            }

            exit();
        } else {
            $error = "Password salah.";
        }
    } else {
        $error = "Email tidak ditemukan.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login - ADW Reimbursement</title>
  <style>
    * {
      box-sizing: border-box;
      font-family: 'Inter', sans-serif;
      margin: 0;
      padding: 0;
    }

    body {
      height: 100vh;
      background-color: #004080;
      display: flex;
      justify-content: center;
      align-items: center;
      color: #000;
    }

    .container {
      text-align: center;
    }

    .container h1 {
      color: white;
      margin-bottom: 30px;
      font-size: 24px;
    }

    .login-box {
      background-color: white;
      padding: 40px 30px;
      border-radius: 20px;
      width: 100%;
      max-width: 360px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .login-box img {
      width: 80px;
      margin-bottom: 20px;
    }

    .login-box h2 {
      margin-bottom: 20px;
      font-size: 22px;
      font-weight: bold;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group input {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #ccc;
      border-radius: 10px;
      font-size: 14px;
      font-family: 'Segoe UI'
    }

    .login-button {
      margin-top: 10px;
    }

    .login-button button {
      width: 100%;
      padding: 12px;
      background-color: #004080;
      color: white;
      border: none;
      border-radius: 10px;
      font-size: 16px;
      cursor: pointer;
    }

    .login-button button:hover {
      background-color: #003366;
    }

    .forgot-password {
      margin-top: 10px;
    }

    .forgot-password a {
      text-decoration: none;
      color: #007bff;
      font-size: 14px;
    }

    .forgot-password a:hover {
      text-decoration: underline;
    }

    @media (max-width: 400px) {
      .login-box {
        padding: 30px 20px;
      }
    }
  </style>
</head>

<body>
  <div class="container">
    <h1>ADW’s Reimbursement System</h1>
    <div class="login-box">
      <img src="img/logo_adw.jpg" alt="Logo ADW" />
      <h2>Login</h2>
      <form method="POST" action="">
      <div class="form-group">
        <input type="text" name="email" placeholder="Email" required />
      </div>
      <div class="form-group">
        <input type="password" name="password" placeholder="Password" required />
      </div>
      <div class="login-button">
        <button type="submit">Login</button>
      </div>
      
      <br>
        <?php if (!empty($error)): ?>
        <p style="color: red; font-size: 14px; font-family: 'Segoe UI'; font-weight: bold;">
          <?= htmlspecialchars($error) ?>
        </p>
      <?php endif; ?>

    </form>
      </div>
</body>

</html>