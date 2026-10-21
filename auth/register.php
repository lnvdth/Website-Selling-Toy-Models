<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) && is_string($_POST['email']) ? trim($_POST['email']) : '';
    $name = isset($_POST['name']) && is_string($_POST['name']) ? trim($_POST['name']) : '';
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
    $password_confirm = isset($_POST['password_confirm']) && is_string($_POST['password_confirm'])
        ? $_POST['password_confirm']
        : '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email không hợp lệ.";
    } elseif ($name === '') {
        $error = "Vui lòng nhập họ và tên.";
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

<main class="auth-page">
<section class="form-container">
    <h2 class="form-title">Đăng Ký Tài Khoản</h2>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="email">Email</label>
        <input type="email" class="form-control" name="email" id="email" required value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">

        <label for="name">Họ và Tên</label>
        <input type="text" class="form-control" name="name" id="name" required value="<?= isset($name) ? htmlspecialchars($name) : '' ?>">

        <label for="password">Mật khẩu</label>
        <input type="password" class="form-control" name="password" id="password" required>

        <label for="password_confirm">Xác nhận mật khẩu</label>
        <input type="password" class="form-control" name="password_confirm" id="password_confirm" required>

        <button type="submit" class="btn">Đăng Ký</button>
    </form>

    <div class="login-link">
        Bạn đã có tài khoản? <a href="index.php?page=login">Đăng nhập ngay</a>
    </div>
</section>
</main>
