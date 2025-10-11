<?php
session_start();
include 'db_connect.php';
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
            header("Location: index.php?page=products&p=$page&added=1");
        }
        exit();
    }
}


ini_set('display_errors', 1);
error_reporting(E_ALL);

$products_per_page = 5;

$page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
$offset = ($page - 1) * $products_per_page;


$total_query = "SELECT COUNT(*) AS total FROM PRODUCTS";
$total_result = mysqli_query($ketnoi, $total_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_products = $total_row['total'];
$total_pages = ceil($total_products / $products_per_page);

$query = "SELECT * FROM PRODUCTS LIMIT $offset, $products_per_page";
$result = mysqli_query($ketnoi, $query);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang chủ</title>
    
</head>
<body>


<div id="products">
    <div id="toast" class="toast">
        🎉 Đã thêm sản phẩm vào giỏ hàng!
        <div class="toast-progress"></div>
    </div>

    <div class="row" align="center">
    <?php while ($product = mysqli_fetch_assoc($result)) { ?>
        <div class="product-card">
            <a href="index.php?page=detail&productId=<?php echo $product['productId'];?> ">
            <img src="<?php echo $product['productImage']; ?>" width="150px" height="150px"><br>
            <div class="column">
                <b class="productName"><?php echo $product['productName']; ?></b><br>
                <b>
                    Giá: 
                    <?php 
                        $discount = ($product['productPrice'] * $product['productDiscount'] / 100);
                        echo number_format($product['productPrice'] - $discount, 0, ',', '.'); 
                    ?>₫
                    <span class="productDiscount"><?php echo number_format($product['productPrice'], 0, ',', '.'); ?>₫</span>
                </b>
                <span class="DiscountP"><?php echo "-".$product['productDiscount']."%"; ?></span>
                <form method="POST" id="product-options">
                    <input type="hidden" name="product_id" value="<?php echo $product['productId']; ?>">
                    <button type="submit" name="add_to_cart" class="btn-add-to-cart">Thêm vào giỏ hàng</button>
                    <button type="submit" name="buy_now" class="btn-buy">Mua ngay</button>
                </form>
            </a>
            </div>
        </div>
    <?php } ?>
    </div>
</div>
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



<div class="pagination" style="text-align:center; margin-top: 20px;">
    <?php if ($total_pages > 1): ?>

        <?php if ($page > 1): ?>
            <a href="index.php?page=products&p=1">&laquo; Trang đầu</a>
            <a href="index.php?page=products&p=<?= $page - 1 ?>">&lt; Trước</a>
        <?php endif; ?>

        <?php
            $range = 2;
            for ($i = max(1, $page - $range); $i <= min($total_pages, $page + $range); $i++):
        ?>
            <a href="index.php?page=products&p=<?= $i ?>" class="<?= ($i == $page) ? 'active' : '' ?>" style="<?= ($i == $page) ? 'font-weight:bold; text-decoration:underline;' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a href="index.php?page=products&p=<?= $page + 1 ?>">Tiếp &gt;</a>
            <a href="index.php?page=products&p=<?= $total_pages ?>">Trang cuối &raquo;</a>
        <?php endif; ?>

    <?php endif; ?>
</div>




<?php if (isset($_GET['added'])): ?>
<script>
    showToast('Đã thêm vào giỏ hàng!');
</script>
<?php endif; ?>

