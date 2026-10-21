<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_to_cart']) || isset($_POST['buy_now']))) {
    $postedProductId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    if ($postedProductId !== false && $postedProductId !== null) {
        if (isset($_POST['buy_now']) && begin_checkout($ketnoi, [$postedProductId => 1], false)) {
            header('Location: index.php?page=checkout');
            exit;
        }

        if (isset($_POST['add_to_cart']) && add_product_to_cart($ketnoi, $postedProductId)) {
            $destination = 'index.php?page=detail&productId=' . $postedProductId . '&added=1';
            header('Location: ' . $destination);
            exit;
        }
    }

    $cartError = isset($_POST['buy_now'])
        ? 'Không thể bắt đầu thanh toán cho sản phẩm này.'
        : 'Không tìm thấy sản phẩm để thêm vào giỏ hàng.';
}

$productId = filter_input(INPUT_GET, 'productId', FILTER_VALIDATE_INT);
$productId = $productId === false || $productId === null ? 0 : $productId;
$statement = $ketnoi->prepare(
    'SELECT productId, productName, productImage, productPrice, productDiscount, productDetail
     FROM products
     WHERE productId = ?'
);
$statement->bind_param('i', $productId);
$statement->execute();
$product = $statement->get_result()->fetch_assoc();
$statement->close();

if (!$product):
?>
    <main class="product-detail">
        <p class="empty-state">Không tìm thấy sản phẩm.</p>
    </main>
<?php
    return;
endif;

$price = (float)$product['productPrice'];
$discount = (float)$product['productDiscount'];
$finalPrice = $price - ($price * $discount / 100);
?>

<main class="product-detail">
    <?php if (!empty($cartError)): ?>
        <p class="error-message" role="alert"><?= e($cartError) ?></p>
    <?php endif; ?>

    <?php if (isset($_GET['added'])): ?>
        <p class="success-message" role="status">Đã thêm sản phẩm vào giỏ hàng.</p>
    <?php endif; ?>

    <h2>Chi tiết sản phẩm</h2>
    <section class="product-detail-card">
        <div class="product-detail-image">
            <img src="<?= e($product['productImage']) ?>" alt="<?= e($product['productName']) ?>">
        </div>
        <div class="product-detail-info">
            <h1><?= e($product['productName']) ?></h1>
            <p><?= nl2br(e($product['productDetail'])) ?></p>
            <div class="product-price">
                <?= number_format($finalPrice, 0, ',', '.') ?> VND
                <?php if ($discount > 0): ?>
                    <span class="original-price"><?= number_format($price, 0, ',', '.') ?> VND</span>
                <?php endif; ?>
            </div>

            <form method="POST">
                <input type="hidden" name="product_id" value="<?= (int)$product['productId'] ?>">
                <button type="submit" name="add_to_cart" class="btn-add-to-cart">Thêm vào giỏ hàng</button>
                <button type="submit" name="buy_now" class="btn-buy">Mua ngay</button>
            </form>
        </div>
    </section>
</main>
