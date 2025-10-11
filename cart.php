<?php
session_start();
include 'db_connect.php';

if (isset($_POST['remove_product'])) {
    $remove_id = $_POST['remove_id'];
    if (isset($_SESSION['cart'][$remove_id])) {
        unset($_SESSION['cart'][$remove_id]);
    }
}

// Thanh toán toàn bộ
if (isset($_POST['pay_all'])) {
    $_SESSION['checkout'] = $_SESSION['cart'];
    $total = 0;
    foreach ($_SESSION['checkout'] as $sp) {
        $total += $sp['productPrice'] * $sp['quantity'];
    }
    $_SESSION['total_payment'] = $total;
    header("Location: index.php?page=checkout");
    exit();
}

// Thanh toán từng sản phẩm (mua lẻ)
if (isset($_POST['pay_single'])) {
    $product_id = $_POST['product_id'];
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['checkout'] = [
            $product_id => $_SESSION['cart'][$product_id]
        ];
        $_SESSION['total_payment'] = $_SESSION['cart'][$product_id]['productPrice'] * $_SESSION['cart'][$product_id]['quantity'];
        header("Location: index.php?page=checkout");
        exit();
    }
}

?>


<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Giỏ hàng của bạn</title>
</head>
<body>
    <h2>🛒 Giỏ hàng của bạn</h2>
    <p><a href="index.php?page=products" id="back-to-shop">Quay lại cửa hàng</a></p>
    <?php
    if (!empty($_SESSION['cart'])) {
        $tong = 0;
        echo '<table id="table-cart">';
        echo '<tr>
                <th>Tên sản phẩm</th>
                <th>Giá</th>
                <th>Số lượng</th>
                <th>Thành tiền</th>
                <th>Thanh Toán</th>
                <th>Xoá</th>
              </tr>';

        foreach ($_SESSION['cart'] as $product_id => $product) {

            $thanhtien = $product['productPrice'] * $product['quantity'];
            $tong += $thanhtien;
            echo "<tr>
                    <td style='display: flex; align-items: center; padding-left: 15px;'><img src='{$product['productImage']}' style='width: 64px; border-radius: 5px;vertical-align: middle; margin-right: 10px;'>{$product['productName']}</td>
                    <td>" . number_format($product['productPrice'], 0, ',', '.') . " VND</td>
                    <td>{$product['quantity']}</td>
                    <td>" . number_format($thanhtien, 0, ',', '.') . " VND</td>
                    <td>
                        <form method='POST'>
                            <input type='hidden' name='product_id' value='{$product_id}'>
                            <button id='btn-pay' type='submit' name='pay_single'>Thanh Toán</button>
                        </form>
                    </td>
                    <td>
                        <form method='POST' >
                            <input type='hidden' name='remove_id' value='{$product_id}'>
                            <button id='btn-remove' type='submit' name='remove_product'>Xoá</button>
                        </form>
                    </td>
                  </tr>";
        }

        echo "<tr>
                <td colspan='4'></td>
                <td colspan='2'><strong>Tổng cộng: " . number_format($tong, 0, ',', '.') . " VND</strong></td>
              </tr>";
        echo '</table>';
    } else {
        echo "<p id='cart-noti'>Giỏ hàng của bạn đang trống.</p>";
    }
    ?>
    
    <div id="btn-pay-container">
        <?php if (!empty($_SESSION['cart'])): ?>
            <form method="POST">
                <button id="btn-pay-all" name="pay_all" type="submit">Thanh Toán Toàn Bộ</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
