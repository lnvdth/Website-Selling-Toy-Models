<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'dbconnect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $ketnoi->prepare("SELECT * FROM users WHERE userEmail = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        $error = "Tài khoản chưa được đăng ký.";
    } else {
        if (password_verify($password, $user['userPassword'])) {

            $admin_check = $ketnoi->prepare("SELECT * FROM admins WHERE userId = ?");
            $admin_check->bind_param("i", $user['userId']);
            $admin_check->execute();
            $is_admin = $admin_check->get_result()->num_rows > 0;
            $admin_check->close();

            $_SESSION['user'] = [
                'id' => $user['userId'],
                'name' => $user['userName'],
                'email' => $user['userEmail'],
                'is_admin' => $is_admin
            ];
            header("Location: index.php");
            exit;
        } else {
            $error = "Mật khẩu không chính xác.";
        }
    }
}
?>





<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng Nhập - Fivex</title>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .form-container {
      background-color: rgba(0, 0, 0, 0.5);
      max-width: 400px;
      width: 100%;
      padding: 40px 30px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
      animation: fadeIn 0.5s ease;
      align-items: center;
      justify-content: center;
      margin: 25px auto;
    }

    .form-title {
      text-align: center;
      font-size: 26px;
      font-weight: bold;
      color: rgb(0, 255, 238);
      margin-bottom: 25px;
    }

    .form-label {
      font-weight: 600;
      color: rgb(185, 43, 221);
      display: block;
      margin-bottom: 6px;
    }

    .form-control {
      width: 100%;
      padding: 12px;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 16px;
      margin-bottom: 20px;
      transition: border-color 0.3s, box-shadow 0.3s;
    }

    .form-control:focus {
      border-color: #007bff;
      box-shadow: 0 0 6px rgba(0, 123, 255, 0.4);
      outline: none;
    }

    .d-grid {
      display: flex;
      justify-content: center;
    }

    .btn {
      background-color: #007bff;
      color: white;
      padding: 12px 20px;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .btn:hover {
      background-color: #0056b3;
    }

    .register-link {
      text-align: center;
      margin-top: 15px;
      font-size: 14px;
      color:rgb(202, 202, 202);
    }

    .register-link a {
      color: #007bff;
      text-decoration: none;
      margin-left: 5px;
    }

    .register-link a:hover {
      text-decoration: underline;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>
</head>
<body>

  <div class="form-container">
  <h2 class="form-title">Đăng Nhập</h2>

  <?php if (!empty($error)): ?>
    <div style="color: red; margin-bottom: 10px; text-align: center;">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form id="loginForm" method="POST">
    <label for="email" class="form-label">Email</label>
    <input type="email" class="form-control" id="email" name="email" required placeholder="Nhập email của bạn">

    <label for="password" class="form-label">Mật khẩu</label>
    <input type="password" class="form-control" id="password" name="password" required placeholder="Nhập mật khẩu">

    <div class="d-grid">
      <button type="submit" class="btn">Đăng Nhập</button>
    </div>

    <div class="register-link">
      Bạn chưa có tài khoản?
      <a href="index.php?page=register">Đăng ký</a>
    </div>
  </form>
</div>


</body>
</html>
