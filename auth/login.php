<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) && is_string($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';

    $stmt = $ketnoi->prepare("SELECT * FROM users WHERE userEmail = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        $error = "Tài khoản chưa được đăng ký.";
    } else {
        $passwordMatches = password_verify($password, $user['userPassword']);
        if (!$passwordMatches && hash_equals((string)$user['userPassword'], $password)) {
            $passwordMatches = true;
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $upgrade = $ketnoi->prepare('UPDATE users SET userPassword = ? WHERE userId = ?');
            $upgrade->bind_param('si', $passwordHash, $user['userId']);
            $upgrade->execute();
            $upgrade->close();
        }

        if ($passwordMatches) {
            session_regenerate_id(true);

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





<main class="auth-page">
  <section class="form-container">
    <h2 class="form-title">Đăng nhập</h2>

  <?php if (!empty($error)): ?>
    <div class="error" role="alert">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form id="loginForm" method="POST">
    <label for="email" class="form-label">Email</label>
    <input type="email" class="form-control" id="email" name="email" required placeholder="Nhập email của bạn">

    <label for="password" class="form-label">Mật khẩu</label>
    <input type="password" class="form-control" id="password" name="password" required placeholder="Nhập mật khẩu">

    <button type="submit" class="btn">Đăng nhập</button>

    <div class="register-link">
      Bạn chưa có tài khoản?
      <a href="index.php?page=register">Đăng ký</a>
    </div>
  </form>
  </section>
</main>
