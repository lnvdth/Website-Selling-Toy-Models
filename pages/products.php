<?php
$searchTerm = isset($_GET['search']) && is_string($_GET['search'])
    ? trim($_GET['search'])
    : '';
$pageInput = filter_input(INPUT_GET, 'p', FILTER_VALIDATE_INT);
$page = max(1, $pageInput === false || $pageInput === null ? 1 : $pageInput);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_to_cart']) || isset($_POST['buy_now']))) {
    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    if ($productId !== false && $productId !== null) {
        if (isset($_POST['buy_now']) && begin_checkout($ketnoi, [$productId => 1], false)) {
            header('Location: index.php?page=checkout');
            exit;
        }

        if (isset($_POST['add_to_cart']) && add_product_to_cart($ketnoi, $productId)) {
            $destination = 'index.php?page=products&p=' . $page . '&added=1';
            if ($searchTerm !== '') {
                $destination .= '&search=' . rawurlencode($searchTerm);
            }
            header('Location: ' . $destination);
            exit;
        }
    }

    $cartError = isset($_POST['buy_now'])
        ? 'Không thể bắt đầu thanh toán cho sản phẩm này.'
        : 'Không tìm thấy sản phẩm để thêm vào giỏ hàng.';
}

$productsPerPage = 8;

if ($searchTerm !== '') {
    $countStatement = $ketnoi->prepare('SELECT COUNT(*) AS total FROM products WHERE productName LIKE ?');
    $searchPattern = '%' . $searchTerm . '%';
    $countStatement->bind_param('s', $searchPattern);
} else {
    $countStatement = $ketnoi->prepare('SELECT COUNT(*) AS total FROM products');
}
$countStatement->execute();
$totalProducts = (int)$countStatement->get_result()->fetch_assoc()['total'];
$countStatement->close();

$totalPages = max(1, (int)ceil($totalProducts / $productsPerPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $productsPerPage;

if ($searchTerm !== '') {
    $statement = $ketnoi->prepare(
        'SELECT productId, productName, productImage, productPrice, productDiscount
         FROM products
         WHERE productName LIKE ?
         ORDER BY productId DESC
         LIMIT ? OFFSET ?'
    );
    $statement->bind_param('sii', $searchPattern, $productsPerPage, $offset);
} else {
    $statement = $ketnoi->prepare(
        'SELECT productId, productName, productImage, productPrice, productDiscount
         FROM products
         ORDER BY productId DESC
         LIMIT ? OFFSET ?'
    );
    $statement->bind_param('ii', $productsPerPage, $offset);
}
$statement->execute();
$products = $statement->get_result();
?>

<main id="products">
    <h2>Sản phẩm</h2>

    <?php if (!empty($cartError)): ?>
        <p class="error-message" role="alert"><?= e($cartError) ?></p>
    <?php endif; ?>

    <?php if (isset($_GET['added'])): ?>
        <p class="success-message" role="status">Đã thêm sản phẩm vào giỏ hàng.</p>
    <?php endif; ?>

    <?php if ($products->num_rows > 0): ?>
        <div class="row">
            <?php while ($product = $products->fetch_assoc()): ?>
                <?php
                $price = (float)$product['productPrice'];
                $discount = (float)$product['productDiscount'];
                $finalPrice = $price - ($price * $discount / 100);
                ?>
                <article class="product-card">
                    <a class="product-link" href="index.php?page=detail&amp;productId=<?= (int)$product['productId'] ?>">
                        <img src="<?= e($product['productImage']) ?>" width="150" height="150" alt="<?= e($product['productName']) ?>" loading="lazy">
                        <span class="productName"><?= e($product['productName']) ?></span>
                    </a>
                    <div class="column">
                        <b>Giá: <?= number_format($finalPrice, 0, ',', '.') ?> VND</b>
                        <?php if ($discount > 0): ?>
                            <span class="productDiscount"><?= number_format($price, 0, ',', '.') ?> VND</span>
                            <span class="DiscountP">-<?= e($discount) ?>%</span>
                        <?php endif; ?>
                        <form method="POST" action="index.php?page=products<?= $searchTerm !== '' ? '&amp;search=' . rawurlencode($searchTerm) : '' ?>&amp;p=<?= $page ?>">
                            <input type="hidden" name="product_id" value="<?= (int)$product['productId'] ?>">
                            <button type="submit" name="add_to_cart" class="btn-add-to-cart"><i class="fa-solid fa-cart-plus" aria-hidden="true"></i> Thêm vào giỏ hàng</button>
                            <button type="submit" name="buy_now" class="btn-buy"><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i> Mua ngay</button>
                        </form>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="pagination" aria-label="Phân trang sản phẩm">
                <?php
                $firstPage = max(1, $page - 2);
                $lastPage = min($totalPages, $page + 2);
                for ($pageNumber = $firstPage; $pageNumber <= $lastPage; $pageNumber++):
                ?>
                    <?php
                    $query = ['page' => 'products', 'p' => $pageNumber];
                    if ($searchTerm !== '') {
                        $query['search'] = $searchTerm;
                    }
                    ?>
                    <a href="index.php?<?= e(http_build_query($query)) ?>"
                       <?= $pageNumber === $page ? 'aria-current="page" class="active"' : '' ?>>
                        <?= $pageNumber ?>
                    </a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <p class="empty-state">Không tìm thấy sản phẩm phù hợp.</p>
    <?php endif; ?>
</main>
