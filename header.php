<?php
$products_per_page = 5;
$page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
$offset = ($page - 1) * $products_per_page;
$search_term = isset($_GET['search']) ? mysqli_real_escape_string($ketnoi, $_GET['search']) : '';

if ($search_term) {
    $total_query = "SELECT COUNT(*) AS total FROM PRODUCTS WHERE productName LIKE '%$search_term%'";
    $query = "SELECT * FROM PRODUCTS WHERE productName LIKE '%$search_term%' LIMIT $offset, $products_per_page";
} else {
    $total_query = "SELECT COUNT(*) AS total FROM PRODUCTS";
    $query = "SELECT * FROM PRODUCTS LIMIT $offset, $products_per_page";
}

$total_result = mysqli_query($ketnoi, $total_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_products = $total_row['total'];
$total_pages = ceil($total_products / $products_per_page);
$result = mysqli_query($ketnoi, $query);
?>


<div id="header">
            <div class="logo">
                <img src="./images/logo.ico" width="64px" height="64px">
                <h1>Hoàng Long Ngũ - 2823230484</h1>
            </div>
            <div class="menu">
                <!-- <div>Hoàng Long Ngũ - TH28.14 - 2823230484</div> -->
                <a href="index.php?page=home">Trang chủ</a>
                <a href="index.php?page=products">Sản phẩm</a>
                <a href="index.php?page=cart">Giỏ hàng</a>
                <a href="#">Lịch sử</a>
                <a href="index.php?page=feeds">Tin Tức</a>
                <a href="index.php?page=contact">Liên Hệ</a>
                <!-- <div class="search_product">
                    
                    <input type="text" placeholder="Nhập sản phẩm cần tìm kiếm">
                </div> -->
                <form method="GET" action="index.php" class="search_product">
                    <div class="icon-search"></div>
                    <input type="hidden" name="page" value="products">
                    <input type="text" name="search" placeholder="Nhập sản phẩm cần tìm kiếm..." value="<?= htmlspecialchars($search_term) ?>">
                    <!-- <button type="submit" style="padding: 6px 12px;">Tìm</button> -->
                </form>

                <div class="account">
                    <?php if (isset($_SESSION['user'])): ?>
                        <span>👋 Xin chào, <?= htmlspecialchars($_SESSION['user']['name']) ?></span>

                        <?php if (!empty($_SESSION['user']['is_admin'])): ?>
                            <a href="admin.php" target="_blank" class="btn admin-btn">Quản lý</a>
                        <?php endif; ?>
                        <a href="index.php?page=logout" class="btn logout-btn">Đăng xuất</a>
                    <?php else: ?>
                        <a href="index.php?page=login" class="btn login-btn">Đăng nhập</a>
                        <a href="index.php?page=register" class="btn register-btn">Đăng ký</a>
                    <?php endif; ?>
                </div>


            </div>
            

        </div>
<div class="background"></div>