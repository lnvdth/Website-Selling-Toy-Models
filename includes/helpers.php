<?php

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function get_live_product(mysqli $connection, int $productId, bool $lockForUpdate = false): ?array
{
    if ($productId < 1) {
        return null;
    }

    $sql = 'SELECT productId, productName, productImage, productPrice, productDiscount
            FROM products
            WHERE productId = ?';
    if ($lockForUpdate) {
        $sql .= ' FOR UPDATE';
    }

    $statement = $connection->prepare($sql);
    $statement->bind_param('i', $productId);
    $statement->execute();
    $product = $statement->get_result()->fetch_assoc();
    $statement->close();

    return $product ?: null;
}

function get_discounted_price(array $product): float
{
    $price = (float)$product['productPrice'];
    $discount = (float)$product['productDiscount'];
    if ($price < 0 || $discount < 0 || $discount > 100) {
        throw new RuntimeException('Thông tin giá của một sản phẩm không hợp lệ.');
    }

    return round($price * (100 - $discount) / 100, 2);
}

function build_vietqr_image_url(
    string $bankBin,
    string $accountNumber,
    string $accountName,
    float $amount,
    string $transferContent
): string {
    $accountName = trim($accountName);
    $transferContent = trim($transferContent);
    if (
        !preg_match('/^[0-9]{6}$/', $bankBin) ||
        !preg_match('/^[0-9]{6,20}$/', $accountNumber) ||
        $accountName === '' ||
        $transferContent === '' ||
        !is_finite($amount) ||
        $amount <= 0
    ) {
        throw new RuntimeException('Thông tin tạo mã QR chuyển khoản không hợp lệ.');
    }

    $query = http_build_query(
        [
            'amount' => (string)(int)round($amount),
            'addInfo' => $transferContent,
            'accountName' => $accountName,
        ],
        '',
        '&',
        PHP_QUERY_RFC3986
    );

    return 'https://img.vietqr.io/image/' . $bankBin . '-' . $accountNumber . '-compact2.png?' . $query;
}

function build_transfer_content(string $orderCode): string
{
    if (!preg_match('/^HL[A-F0-9]{16}$/', $orderCode)) {
        throw new RuntimeException('Mã đơn hàng dùng cho nội dung chuyển khoản không hợp lệ.');
    }

    return 'THANHTOAN ' . $orderCode;
}

function add_product_to_cart(mysqli $connection, int $productId): bool
{
    $product = get_live_product($connection, $productId);
    if (!$product) {
        return false;
    }

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $cartKey = (string)$product['productId'];
    if (isset($_SESSION['cart'][$cartKey])) {
        $_SESSION['cart'][$cartKey]['quantity'] = max(1, (int)$_SESSION['cart'][$cartKey]['quantity']) + 1;
    } else {
        $_SESSION['cart'][$cartKey] = ['quantity' => 1];
    }

    $_SESSION['cart'][$cartKey] = array_merge(
        $_SESSION['cart'][$cartKey],
        [
            'productName' => $product['productName'],
            'productImage' => $product['productImage'],
            'productPrice' => get_discounted_price($product),
            'productDiscount' => (float)$product['productDiscount'],
        ]
    );

    return true;
}

function refresh_cart_from_database(mysqli $connection): array
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $refreshedCart = [];
    foreach ($_SESSION['cart'] as $productId => $cartItem) {
        $validProductId = filter_var((string)$productId, FILTER_VALIDATE_INT);
        if ($validProductId === false || $validProductId < 1 || !is_array($cartItem)) {
            continue;
        }

        $product = get_live_product($connection, $validProductId);
        if (!$product) {
            continue;
        }

        $refreshedCart[(string)$validProductId] = [
            'productName' => $product['productName'],
            'productImage' => $product['productImage'],
            'productPrice' => get_discounted_price($product),
            'productDiscount' => (float)$product['productDiscount'],
            'quantity' => max(1, (int)($cartItem['quantity'] ?? 1)),
        ];
    }

    $_SESSION['cart'] = $refreshedCart;
    return $refreshedCart;
}

function begin_checkout(mysqli $connection, array $quantities, bool $removeFromCart): bool
{
    $normalizedQuantities = [];
    foreach ($quantities as $productId => $quantity) {
        $validProductId = filter_var((string)$productId, FILTER_VALIDATE_INT);
        $validQuantity = filter_var($quantity, FILTER_VALIDATE_INT);
        if (
            $validProductId === false || $validProductId < 1 ||
            $validQuantity === false || $validQuantity < 1 ||
            !get_live_product($connection, $validProductId)
        ) {
            return false;
        }

        $normalizedQuantities[(string)$validProductId] = $validQuantity;
    }

    if ($normalizedQuantities === []) {
        return false;
    }

    $checkoutToken = bin2hex(random_bytes(32));
    $_SESSION['checkout'] = [
        'quantities' => $normalizedQuantities,
        'remove_from_cart' => $removeFromCart,
        'token' => $checkoutToken,
        'order_code' => 'HL' . strtoupper(substr($checkoutToken, 0, 16)),
        'csrf_token' => bin2hex(random_bytes(32)),
    ];

    return true;
}

function get_checkout_items(mysqli $connection): array
{
    $checkout = $_SESSION['checkout'] ?? null;
    if (!is_array($checkout) || empty($checkout['quantities']) || !is_array($checkout['quantities'])) {
        throw new RuntimeException('Phiên thanh toán không hợp lệ. Vui lòng bắt đầu lại từ giỏ hàng.');
    }

    $items = [];
    foreach ($checkout['quantities'] as $productId => $quantity) {
        $product = get_live_product($connection, (int)$productId);
        if (!$product) {
            throw new RuntimeException('Một sản phẩm trong đơn không còn tồn tại. Vui lòng kiểm tra lại giỏ hàng.');
        }

        $quantity = filter_var($quantity, FILTER_VALIDATE_INT);
        if ($quantity === false || $quantity < 1) {
            throw new RuntimeException('Số lượng sản phẩm không hợp lệ. Vui lòng bắt đầu lại.');
        }

        $unitPrice = get_discounted_price($product);
        $items[(string)$product['productId']] = [
            'productId' => (int)$product['productId'],
            'productName' => $product['productName'],
            'productImage' => $product['productImage'],
            'unitPrice' => $unitPrice,
            'quantity' => $quantity,
            'lineTotal' => round($unitPrice * $quantity, 2),
        ];
    }

    return $items;
}

function create_pending_order(
    mysqli $connection,
    array $checkout,
    ?int $userId,
    string $guestToken
): array {
    if (
        empty($checkout['token']) || empty($checkout['order_code']) ||
        empty($checkout['quantities']) || !is_array($checkout['quantities']) ||
        $guestToken === ''
    ) {
        throw new RuntimeException('Thông tin thanh toán không hợp lệ. Vui lòng bắt đầu lại.');
    }

    $connection->begin_transaction();
    try {
        $existingOrder = $connection->prepare(
            'SELECT orderId, orderCode, totalAmount
             FROM orders
             WHERE checkoutToken = ?
             LIMIT 1'
        );
        $existingOrder->bind_param('s', $checkout['token']);
        $existingOrder->execute();
        $existing = $existingOrder->get_result()->fetch_assoc();
        $existingOrder->close();

        if ($existing) {
            $connection->commit();
            return [
                'order_id' => (int)$existing['orderId'],
                'order_code' => $existing['orderCode'],
                'total_amount' => (float)$existing['totalAmount'],
            ];
        }

        $items = [];
        $totalAmount = 0.0;
        foreach ($checkout['quantities'] as $productId => $quantity) {
            $validProductId = filter_var((string)$productId, FILTER_VALIDATE_INT);
            $quantity = filter_var($quantity, FILTER_VALIDATE_INT);
            if ($validProductId === false || $validProductId < 1 || $quantity === false || $quantity < 1) {
                throw new RuntimeException('Số lượng sản phẩm không hợp lệ. Vui lòng bắt đầu lại.');
            }

            $product = get_live_product($connection, $validProductId, true);
            if (!$product) {
                throw new RuntimeException('Một sản phẩm trong đơn không còn tồn tại. Vui lòng kiểm tra lại giỏ hàng.');
            }

            $unitPrice = get_discounted_price($product);
            $lineTotal = round($unitPrice * $quantity, 2);
            $items[] = [
                'productId' => $validProductId,
                'productName' => $product['productName'],
                'productImage' => $product['productImage'],
                'unitPrice' => $unitPrice,
                'quantity' => $quantity,
                'lineTotal' => $lineTotal,
            ];
            $totalAmount += $lineTotal;
        }

        if ($items === []) {
            throw new RuntimeException('Đơn hàng không có sản phẩm.');
        }

        $orderCode = (string)$checkout['order_code'];
        $checkoutToken = (string)$checkout['token'];
        $guestOrderToken = $guestToken;
        $orderStatus = 'pending_payment';
        $amount = round($totalAmount, 2);
        $insertOrder = $connection->prepare(
            'INSERT INTO orders (orderCode, checkoutToken, userId, guestToken, status, totalAmount)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $insertOrder->bind_param(
            'ssissd',
            $orderCode,
            $checkoutToken,
            $userId,
            $guestOrderToken,
            $orderStatus,
            $amount
        );
        $insertOrder->execute();
        $orderId = (int)$connection->insert_id;
        $insertOrder->close();

        $insertItem = $connection->prepare(
            'INSERT INTO order_items
             (orderId, productId, productName, productImage, unitPrice, quantity, lineTotal)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($items as $item) {
            $productId = $item['productId'];
            $productName = $item['productName'];
            $productImage = $item['productImage'];
            $unitPrice = $item['unitPrice'];
            $quantity = $item['quantity'];
            $lineTotal = $item['lineTotal'];
            $insertItem->bind_param(
                'iissdid',
                $orderId,
                $productId,
                $productName,
                $productImage,
                $unitPrice,
                $quantity,
                $lineTotal
            );
            $insertItem->execute();
        }
        $insertItem->close();
        $connection->commit();

        return [
            'order_id' => $orderId,
            'order_code' => $orderCode,
            'total_amount' => $amount,
        ];
    } catch (Throwable $exception) {
        $connection->rollback();
        throw $exception;
    }
}
