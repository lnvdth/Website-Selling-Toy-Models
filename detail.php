<?php


session_start();

$productId = isset($_GET['productId']) && is_numeric($_GET['productId']) ? (int)$_GET['productId'] : 0;

if (isset($_POST['add_to_cart']) || isset($_POST['buy_now'])) {
    $product_id = $_POST['product_id'];
    $query = "SELECT * FROM PRODUCTS WHERE productId = $product_id";
    $result = mysqli_query($ketnoi, $query);
    $product = mysqli_fetch_assoc($result);

    if ($product) {
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += 1;
        } else {
            $discount = ($product['productPrice'] * $product['productDiscount'] / 100);
            $_SESSION['cart'][$product_id] = [
                'productName' => $product['productName'],
                'productImage' => $product['productImage'],
                'productPrice' => $product['productPrice'] - $discount,
                'productDiscount' => $product['productDiscount'],
                'quantity' => 1
            ];
        }

        if (isset($_POST['buy_now'])) {
            header("Location: index.php?page=checkout");
        } else {
            header("Location: index.php?page=detail&productId=$product_id&added=1");
        }
        exit();
    }
}

$sql = "SELECT * FROM products WHERE productId = $productId";
$result = mysqli_query($ketnoi, $sql);

if ($product = mysqli_fetch_assoc($result)):
    $discount = $product['productPrice'] * $product['productDiscount'] / 100;
    $price_after_discount = $product['productPrice'] - $discount;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($product['productName']); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .product-container {
            max-width: 1000px;
            margin: auto;
            margin-bottom: 40px;
            background-color: rgba(0, 0, 0, 0.3);
            padding: 30px;
            border-radius: 8px;
            display: flex;
            gap: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .product-image img {
            max-width: 400px;
            border-radius: 8px;
        }
        .product-info {
            flex: 1;
        }
        .product-info h1 {
            margin: 0 0 10px;
        }
        .product-price {
            font-size: 22px;
            color: #e53935;
            margin: 10px 0;
        }
        .original-price {
            text-decoration: line-through;
            color: #777;
            font-size: 16px;
            margin-left: 10px;
        }
        form {
            margin-top: 20px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            background: #2196f3;
            color: white;
            cursor: pointer;
            margin-right: 10px;
            border-radius: 5px;
        }
        .btn-buy {
            background: #4caf50;
        }

    </style>
</head>
<body>

<main>
    <div id="toast" class="toast">
        🎉 Đã thêm sản phẩm vào giỏ hàng!
        <div class="toast-progress"></div>
    </div>
    <h2 style="text-align:center">Chi Tiết Sản Phẩm</h2>
    <section class="product-container">
        <div class="product-image">
            <img src="<?php echo htmlspecialchars($product['productImage']); ?>" alt="<?php echo htmlspecialchars($product['productName']); ?>">
        </div>
        <div class="product-info">
            <h1><?php echo htmlspecialchars($product['productName']); ?></h1>
            <p><?php echo nl2br(htmlspecialchars($product['productDetail'])); ?></p>
            <div class="product-price">
                <?php echo number_format($price_after_discount, 0, ',', '.'); ?>₫
                <?php if ($product['productDiscount'] > 0): ?>
                    <span class="original-price"><?php echo number_format($product['productPrice'], 0, ',', '.'); ?>₫</span>
                <?php endif; ?>
            </div>

            <form method="POST">
                <input type="hidden" name="product_id" value="<?php echo $product['productId']; ?>">
                <button type="submit" name="add_to_cart" class="btn">Thêm vào giỏ hàng</button>
                <button type="submit" name="buy_now" class="btn btn-buy">Mua ngay</button>
            </form>
        </div>
    </section>
</main>

<script>
function showToast(message = '🎉 Đã thêm sản phẩm vào giỏ hàng!') {
    const toast = document.getElementById('toast');
    toast.innerText = message;

    toast.innerHTML = message + '<div class="toast-progress"></div>';

    toast.classList.add('show');

    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}
</script>

<?php if (isset($_GET['added'])): ?>
<script>
    showToast();
    const url = new URL(window.location);
    url.searchParams.delete('added');
    window.history.replaceState({}, document.title, url);
</script>
<?php endif; ?>

</body>
</html>
<?php
else:
    echo "<p style='text-align:center;'>Không tìm thấy sản phẩm.</p>";
endif;


?>
<?php if (isset($_GET['added'])): ?>
<script>
    showToast('Đã thêm vào giỏ hàng!');
</script>
<?php endif; ?>