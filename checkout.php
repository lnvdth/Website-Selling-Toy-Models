<?php
session_start();

if (empty($_SESSION['checkout']) || empty($_SESSION['total_payment'])) {
    header("Location: index.php?page=cart");
    exit();
}

$checkout_items = $_SESSION['checkout'];
$total = $_SESSION['total_payment'];

$order_id = time();
$payment_content = "Fivex_$order_id";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['checkout_cancel'])) {
        unset($_SESSION['checkout']);
        unset($_SESSION['total_payment']);
        header("Location: index.php?page=cart");
        exit();
    }

    if (isset($_POST['checkout_confirm'])) {
        foreach ($checkout_items as $pid => $item) {
            unset($_SESSION['cart'][$pid]);
        }

        unset($_SESSION['checkout']);
        unset($_SESSION['total_payment']);

        header("Location: index.php?page=cart&success=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thanh Toán</title>
</head>
<body>

<div class="payment-container">
    <h2>🔒Xác nhận thanh toán</h2>

    <img src="images/qr.jpg" alt="QR Code chuyển khoản">

    <div class="payment-info">
        <p><strong>Số tiền: </strong> <?= number_format($total, 0, ',', '.') ?> VND</p>
        <p><strong>Ngân hàng: </strong>MB Bank</p>
        <p><strong>Chủ tài khoản: </strong>Hoàng Long Ngũ</p>
        <p><strong>Số tài khoản: </strong>8603042005</p>
        <p><strong>Nội dung chuyển khoản: </strong><span style="color: red"><?= $payment_content ?></span></p>
    </div>

    <p style="margin-top: 20px; color: #8c00ff;">Vui lòng chuyển khoản chính xác nội dung </br>để hệ thống ghi nhận đơn hàng.</p>

    <form method="POST" id="checkout-options">
        <button type="submit" name="checkout_confirm" class="btn-checkout-confirm">Xác nhận</button>
        <button type="submit" name="checkout_cancel" class="btn-checkout-cancel">Hủy</button>
    </form>
</div>

</body>
</html>
