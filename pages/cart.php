<?php
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
$cartItems = refresh_cart_from_database($ketnoi);
$cartError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove_product'])) {
        $removeId = filter_input(INPUT_POST, 'remove_id', FILTER_VALIDATE_INT);
        if ($removeId !== false && $removeId !== null) {
            unset($_SESSION['cart'][(string)$removeId]);
        }
    }

    if (isset($_POST['pay_all']) && $cartItems !== []) {
        $quantities = [];
        foreach ($cartItems as $productId => $item) {
            $quantities[$productId] = $item['quantity'];
        }
        if (begin_checkout($ketnoi, $quantities, true)) {
            header('Location: index.php?page=checkout');
            exit;
        }
        $cartError = 'Không thể bắt đầu thanh toán. Vui lòng tải lại giỏ hàng và thử lại.';
    }

    if (isset($_POST['pay_single'])) {
        $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        $cartKey = $productId === false || $productId === null ? '' : (string)$productId;
        if ($cartKey !== '' && isset($cartItems[$cartKey])) {
            if (begin_checkout($ketnoi, [$cartKey => $cartItems[$cartKey]['quantity']], true)) {
                header('Location: index.php?page=checkout');
                exit;
            }
            $cartError = 'Không thể bắt đầu thanh toán cho sản phẩm này.';
        } else {
            $cartError = 'Không tìm thấy sản phẩm trong giỏ hàng.';
        }
    }

    if (isset($_POST['remove_product'])) {
        $cartItems = refresh_cart_from_database($ketnoi);
    } elseif ($cartError === '') {
        $cartItems = refresh_cart_from_database($ketnoi);
    }
}
?>

<main class="cart-page">
    <h2><i class="fa-solid fa-cart-shopping" aria-hidden="true"></i> Giỏ hàng của bạn</h2>
    <p><a href="index.php?page=products" id="back-to-shop"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Quay lại cửa hàng</a></p>

    <?php if ($cartError !== ''): ?>
        <p class="error-message" role="alert"><?= e($cartError) ?></p>
    <?php endif; ?>

    <?php if ($cartItems !== []): ?>
        <?php $total = 0; ?>
        <div class="table-scroll">
            <table id="table-cart">
                <thead>
                    <tr>
                        <th scope="col">Tên sản phẩm</th>
                        <th scope="col">Giá</th>
                        <th scope="col">Số lượng</th>
                        <th scope="col">Thành tiền</th>
                        <th scope="col">Thanh toán</th>
                        <th scope="col">Xóa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartItems as $productId => $product): ?>
                        <?php
                        $price = (float)$product['productPrice'];
                        $quantity = max(1, (int)$product['quantity']);
                        $lineTotal = $price * $quantity;
                        $total += $lineTotal;
                        ?>
                        <tr>
                            <td class="cart-product">
                                <img src="<?= e($product['productImage']) ?>" alt="" width="64" height="64" loading="lazy">
                                <?= e($product['productName']) ?>
                            </td>
                            <td><?= number_format($price, 0, ',', '.') ?> VND</td>
                            <td><?= $quantity ?></td>
                            <td><?= number_format($lineTotal, 0, ',', '.') ?> VND</td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="product_id" value="<?= (int)$productId ?>">
                                    <button type="submit" name="pay_single"><i class="fa-solid fa-credit-card" aria-hidden="true"></i> Thanh toán</button>
                                </form>
                            </td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="remove_id" value="<?= (int)$productId ?>">
                                    <button type="submit" name="remove_product"><i class="fa-solid fa-trash" aria-hidden="true"></i> Xóa</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" scope="row">Tổng cộng</th>
                        <td colspan="3"><strong><?= number_format($total, 0, ',', '.') ?> VND</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <form method="POST" id="btn-pay-container">
            <button id="btn-pay-all" name="pay_all" type="submit"><i class="fa-solid fa-credit-card" aria-hidden="true"></i> Thanh toán toàn bộ</button>
        </form>
    <?php else: ?>
        <p id="cart-noti" class="empty-state">Giỏ hàng của bạn đang trống.</p>
    <?php endif; ?>
</main>
