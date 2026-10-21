<?php
if (empty($_SESSION['guest_order_token'])) {
    $_SESSION['guest_order_token'] = bin2hex(random_bytes(32));
}

$userId = filter_var($_SESSION['user']['id'] ?? 0, FILTER_VALIDATE_INT);
$userId = $userId === false || $userId === null || $userId < 1 ? 0 : $userId;
$guestToken = (string)$_SESSION['guest_order_token'];
$paymentSettings = require dirname(__DIR__) . '/config/payment.php';
$statement = $ketnoi->prepare(
    'SELECT o.orderId, o.orderCode, o.status, o.totalAmount, o.createdAt,
            oi.productName, oi.unitPrice, oi.quantity, oi.lineTotal
     FROM orders o
     LEFT JOIN order_items oi ON oi.orderId = o.orderId
     WHERE o.userId = ? OR o.guestToken = ?
     ORDER BY o.createdAt DESC, o.orderId DESC
     LIMIT 50'
);
$statement->bind_param('is', $userId, $guestToken);
$statement->execute();
$result = $statement->get_result();
$orders = [];

while ($row = $result->fetch_assoc()) {
    $orderId = (int)$row['orderId'];
    if (!isset($orders[$orderId])) {
        $orders[$orderId] = [
            'orderCode' => $row['orderCode'],
            'status' => $row['status'],
            'totalAmount' => (float)$row['totalAmount'],
            'createdAt' => $row['createdAt'],
            'items' => [],
        ];
    }

    if ($row['productName'] !== null) {
        $orders[$orderId]['items'][] = [
            'productName' => $row['productName'],
            'unitPrice' => (float)$row['unitPrice'],
            'quantity' => (int)$row['quantity'],
            'lineTotal' => (float)$row['lineTotal'],
        ];
    }
}
$statement->close();

$createdOrderCode = isset($_SESSION['order_created']) && is_string($_SESSION['order_created'])
    ? $_SESSION['order_created']
    : '';
unset($_SESSION['order_created']);
$orderStatusLabels = [
    'pending_payment' => 'Chờ thanh toán',
    'paid' => 'Đã thanh toán',
    'processing' => 'Đang chuẩn bị',
    'shipped' => 'Đang giao',
    'completed' => 'Hoàn tất',
    'cancelled' => 'Đã hủy',
];
?>

<main class="cart-page order-history-page">
    <h2><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Lịch sử đơn hàng</h2>

    <?php if ($createdOrderCode !== ''): ?>
        <p class="success-message" role="status">
            Đã lưu đơn hàng <?= e($createdOrderCode) ?>. Trạng thái hiện tại là chờ thanh toán, chưa được xác nhận đã thanh toán.
        </p>
    <?php endif; ?>

    <?php if ($orders === []): ?>
        <p class="empty-state">Bạn chưa có đơn hàng nào được lưu.</p>
    <?php else: ?>
        <div class="order-list">
            <?php foreach ($orders as $order): ?>
                <?php
                $isPending = $order['status'] === 'pending_payment';
                $statusLabel = $orderStatusLabels[$order['status']] ?? 'Trạng thái không xác định';
                ?>
                <section class="order-card">
                    <header class="order-card-header">
                        <div>
                            <h3>Đơn hàng <?= e($order['orderCode']) ?></h3>
                            <time datetime="<?= e(date(DATE_ATOM, strtotime($order['createdAt']))) ?>">
                                <?= e(date('d/m/Y H:i', strtotime($order['createdAt']))) ?>
                            </time>
                        </div>
                        <span class="order-status<?= $isPending ? ' is-pending' : ($order['status'] === 'cancelled' ? ' is-cancelled' : '') ?>"><?= e($statusLabel) ?></span>
                    </header>

                    <ul class="order-item-list">
                        <?php foreach ($order['items'] as $item): ?>
                            <li>
                                <span><?= e($item['productName']) ?> × <?= $item['quantity'] ?></span>
                                <span><?= number_format($item['lineTotal'], 0, ',', '.') ?> VND</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <p class="order-total">
                        <strong>Tổng cộng</strong>
                        <strong><?= number_format($order['totalAmount'], 0, ',', '.') ?> VND</strong>
                    </p>

                    <?php if ($isPending): ?>
                        <div class="order-payment-info">
                            <p>Chuyển khoản <?= e($paymentSettings['bank_name']) ?>, số tài khoản <strong><?= e($paymentSettings['account_number']) ?></strong>, chủ tài khoản <?= e($paymentSettings['account_name']) ?>.</p>
                            <p>Nội dung chuyển khoản: <strong><?= e(build_transfer_content($order['orderCode'])) ?></strong></p>
                            <p>Đơn vẫn chờ xác nhận cho đến khi giao dịch được người quản trị kiểm tra.</p>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
