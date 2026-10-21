<?php
if (
    !isset($_SESSION['checkout']) ||
    !is_array($_SESSION['checkout']) ||
    empty($_SESSION['checkout']['quantities'])
) {
    header('Location: index.php?page=cart');
    exit();
}

if (empty($_SESSION['guest_order_token'])) {
    $_SESSION['guest_order_token'] = bin2hex(random_bytes(32));
}

$checkout = $_SESSION['checkout'];
$checkoutError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['checkout_cancel'])) {
        unset($_SESSION['checkout']);
        header('Location: index.php?page=cart');
        exit();
    }

    $postedCsrfToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : '';
    if (
        !isset($checkout['csrf_token']) ||
        !hash_equals((string)$checkout['csrf_token'], $postedCsrfToken)
    ) {
        $checkoutError = 'Phiên xác nhận đã hết hạn. Vui lòng tải lại trang và thử lại.';
    } elseif (isset($_POST['checkout_confirm'])) {
        $sessionUserId = $_SESSION['user']['id'] ?? null;
        $userId = filter_var($sessionUserId, FILTER_VALIDATE_INT);
        $userId = $userId === false || $userId === null || $userId < 1 ? null : $userId;

        try {
            $order = create_pending_order(
                $ketnoi,
                $checkout,
                $userId,
                (string)$_SESSION['guest_order_token']
            );

            if (!empty($checkout['remove_from_cart'])) {
                foreach ($checkout['quantities'] as $productId => $quantity) {
                    $cartKey = (string)$productId;
                    if (!isset($_SESSION['cart'][$cartKey])) {
                        continue;
                    }

                    $remainingQuantity = (int)$_SESSION['cart'][$cartKey]['quantity'] - (int)$quantity;
                    if ($remainingQuantity > 0) {
                        $_SESSION['cart'][$cartKey]['quantity'] = $remainingQuantity;
                    } else {
                        unset($_SESSION['cart'][$cartKey]);
                    }
                }
            }

            $_SESSION['order_created'] = $order['order_code'];
            unset($_SESSION['checkout']);
            header('Location: index.php?page=history&order=' . rawurlencode($order['order_code']));
            exit();
        } catch (RuntimeException $exception) {
            $checkoutError = $exception->getMessage();
        } catch (mysqli_sql_exception $exception) {
            error_log('Unable to create order: ' . $exception->getMessage());
            $checkoutError = 'Không thể lưu đơn hàng lúc này. Vui lòng thử lại sau.';
        }
    } else {
        $checkoutError = 'Yêu cầu xác nhận không hợp lệ.';
    }
}

try {
    $checkoutItems = get_checkout_items($ketnoi);
} catch (RuntimeException $exception) {
    $checkoutItems = [];
    if ($checkoutError === '') {
        $checkoutError = $exception->getMessage();
    }
}

$total = array_sum(array_column($checkoutItems, 'lineTotal'));
$orderCode = (string)($checkout['order_code'] ?? '');
$transferContent = build_transfer_content($orderCode);
$paymentSettings = require dirname(__DIR__) . '/config/payment.php';
$vietQrImageUrl = build_vietqr_image_url(
    (string)$paymentSettings['bank_bin'],
    (string)$paymentSettings['account_number'],
    (string)$paymentSettings['account_name'],
    (float)$total,
    $transferContent
);
?>

<main class="payment-container">
    <h2><i class="fa-solid fa-lock" aria-hidden="true"></i> Thông tin thanh toán</h2>

    <?php if ($checkoutError !== ''): ?>
        <p class="error-message" role="alert"><?= e($checkoutError) ?></p>
    <?php endif; ?>

    <?php if ($checkoutItems !== []): ?>
        <div class="payment-layout">
            <section class="payment-order-summary" aria-labelledby="payment-order-heading">
                <h3 id="payment-order-heading"><i class="fa-solid fa-receipt" aria-hidden="true"></i> Chi tiết đơn hàng</h3>
                <div class="checkout-items">
                    <?php foreach ($checkoutItems as $item): ?>
                        <div class="checkout-item">
                            <span class="checkout-item-name"><?= e($item['productName']) ?></span>
                            <span class="checkout-item-quantity"><?= (int)$item['quantity'] ?> x <?= number_format($item['unitPrice'], 0, ',', '.') ?> VND</span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="payment-total">
                    <span>Tổng thanh toán</span>
                    <strong><?= number_format($total, 0, ',', '.') ?> VND</strong>
                </div>
                <p class="payment-order-code">Mã đơn <strong><?= e($orderCode) ?></strong></p>
            </section>

            <section class="payment-qr-panel" aria-labelledby="payment-qr-heading">
                <h3 id="payment-qr-heading"><i class="fa-solid fa-qrcode" aria-hidden="true"></i> Quét mã để chuyển khoản</h3>
                <img
                    src="<?= e($vietQrImageUrl) ?>"
                    alt="Mã VietQR chuyển <?= number_format($total, 0, ',', '.') ?> VND với nội dung <?= e($transferContent) ?>"
                    referrerpolicy="no-referrer"
                >
                <p class="payment-qr-caption">QR đã điền sẵn số tiền và nội dung chuyển khoản.</p>
            </section>

            <section class="payment-details" aria-labelledby="payment-details-heading">
                <h3 id="payment-details-heading"><i class="fa-solid fa-building-columns" aria-hidden="true"></i> Thông tin chuyển khoản</h3>
                <dl class="payment-info">
                    <div>
                        <dt>Ngân hàng</dt>
                        <dd><?= e($paymentSettings['bank_name']) ?></dd>
                    </div>
                    <div>
                        <dt>Chủ tài khoản</dt>
                        <dd><?= e($paymentSettings['account_name']) ?></dd>
                    </div>
                    <div>
                        <dt>Số tài khoản</dt>
                        <dd><?= e($paymentSettings['account_number']) ?></dd>
                    </div>
                    <div class="payment-transfer-row">
                        <dt>Nội dung chuyển khoản</dt>
                        <dd>
                            <code id="payment-transfer-content"><?= e($transferContent) ?></code>
                            <button type="button" class="payment-copy-button" data-copy-target="payment-transfer-content">
                                <i class="fa-regular fa-copy" aria-hidden="true"></i> Sao chép
                            </button>
                        </dd>
                    </div>
                </dl>
            </section>
        </div>

        <aside class="payment-note" role="note">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <p>Đơn được tạo ở trạng thái chờ thanh toán. Chỉ quản trị viên xác nhận sau khi đối soát giao dịch.</p>
        </aside>

        <form method="POST" id="checkout-options">
            <input type="hidden" name="csrf_token" value="<?= e($checkout['csrf_token'] ?? '') ?>">
            <button type="submit" name="checkout_confirm" class="btn-checkout-confirm">
                <i class="fa-solid fa-file-circle-check" aria-hidden="true"></i> Tạo đơn chờ thanh toán
            </button>
            <button type="submit" name="checkout_cancel" class="btn-checkout-cancel">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i> Hủy
            </button>
        </form>
    <?php else: ?>
        <p><a href="index.php?page=cart">Quay lại giỏ hàng</a></p>
    <?php endif; ?>
</main>

<script>
document.querySelectorAll('[data-copy-target]').forEach((button) => {
    button.addEventListener('click', async () => {
        const content = document.getElementById(button.dataset.copyTarget);
        if (!content) {
            return;
        }

        const originalLabel = button.innerHTML;
        try {
            await navigator.clipboard.writeText(content.textContent.trim());
            button.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i> Đã sao chép';
        } catch (error) {
            const selection = window.getSelection();
            const range = document.createRange();
            range.selectNodeContents(content);
            selection.removeAllRanges();
            selection.addRange(range);
            button.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i> Đã chọn nội dung';
        }
        window.setTimeout(() => {
            button.innerHTML = originalLabel;
        }, 1800);
    });
});
</script>
