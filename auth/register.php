<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'dbconnect.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $name = trim($_POST['name']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email không hợp lệ.";
    } elseif (strlen($password) < 6) {
        $error = "Mật khẩu phải có ít nhất 6 ký tự.";
    } elseif ($password !== $password_confirm) {
        $error = "Mật khẩu xác nhận không khớp.";
    } else {
        // Kiểm tra email tồn tại
        $stmt = mysqli_prepare($ketnoi, "SELECT * FROM users WHERE userEmail = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $error = "Email đã được đăng ký trước đó.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($ketnoi, "INSERT INTO users (userEmail, userName, userPassword) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $email, $name, $password_hash);

            if (mysqli_stmt_execute($stmt)) {
                $success = "Đăng ký thành công! Bạn có thể đăng nhập ngay.";
            } else {
                $error = "Lỗi khi đăng ký, vui lòng thử lại.";
            }
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <title>Đăng Ký</title>
    <style>

        .form-container {
      background-color: rgba(0, 0, 0, 0.5);
            max-width: 400px;
            margin: auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin: 25px auto;
        }
        .form-title {
      text-align: center;
      font-size: 26px;
      font-weight: bold;
      color: rgb(0, 255, 238);
      margin-bottom: 25px;
    }
        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }
        input[id="name"], input[type="email"], input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        button {
            margin-top: 25px;
            padding: 12px;
            width: 100%;
            border: none;
            background-color: #007bff;
            color: white;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }
        .error {
            color: red;
            margin-top: 15px;
            text-align: center;
        }
        .success {
            color: green;
            margin-top: 15px;
            text-align: center;
        }
        .login-link {
            margin-top: 20px;
            text-align: center;
        }
        .login-link a {
            color: #007bff;
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2 class="form-title">Đăng Ký Tài Khoản</h2>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" required value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">

        <label for="name">Họ và Tên</label>
        <input type="text" name="name" id="name" required value="<?= isset($name) ? htmlspecialchars($name) : '' ?>">

        <label for="password">Mật khẩu</label>
        <input type="password" name="password" id="password" required>

        <label for="password_confirm">Xác nhận mật khẩu</label>
        <input type="password" name="password_confirm" id="password_confirm" required>

        <button type="submit">Đăng Ký</button>
    </form>

    <div class="login-link">
        Bạn đã có tài khoản? <a href="index.php?page=login">Đăng nhập ngay</a>
    </div>
</div>

</body>
</html>
