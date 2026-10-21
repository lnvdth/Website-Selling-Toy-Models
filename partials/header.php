<header class="site-header">
    <div class="site-header-inner">
        <a class="brand" href="index.php?page=home">
            <img src="assets/images/logo.ico" width="48" height="48" alt="">
            <span>Hoàng Long Ngũ</span>
        </a>

        <nav class="main-nav" aria-label="Điều hướng chính">
            <a href="index.php?page=home"><i class="fa-solid fa-house" aria-hidden="true"></i> Trang chủ</a>
            <a href="index.php?page=products"><i class="fa-solid fa-cubes" aria-hidden="true"></i> Sản phẩm</a>
            <a href="index.php?page=cart"><i class="fa-solid fa-cart-shopping" aria-hidden="true"></i> Giỏ hàng</a>
            <a href="index.php?page=history"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Lịch sử</a>
            <a href="index.php?page=feeds"><i class="fa-solid fa-newspaper" aria-hidden="true"></i> Tin tức</a>
            <a href="index.php?page=contact"><i class="fa-solid fa-envelope" aria-hidden="true"></i> Liên hệ</a>
        </nav>

        <div class="header-actions">
            <form method="GET" action="index.php" class="search_product">
                <input type="hidden" name="page" value="products">
                <input type="search" name="search" placeholder="Tìm sản phẩm..." value="<?= e(isset($_GET['search']) && is_string($_GET['search']) ? $_GET['search'] : '') ?>" aria-label="Tìm sản phẩm">
                <button type="submit"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span class="visually-hidden">Tìm</span></button>
            </form>

            <div class="account">
                <?php if (isset($_SESSION['user'])): ?>
                    <div class="account-user">
                        <span class="account-greeting">
                            <i class="fa-solid fa-circle-user" aria-hidden="true"></i>
                            <span>Xin chào, <?= e($_SESSION['user']['name']) ?></span>
                        </span>
                        <div class="account-user-actions">
                            <?php if (!empty($_SESSION['user']['is_admin'])): ?>
                                <a href="admin.php" class="account-link"><i class="fa-solid fa-gear" aria-hidden="true"></i> Quản lý</a>
                            <?php endif; ?>
                            <a href="index.php?page=logout" class="account-link"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> Đăng xuất</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="index.php?page=login" class="account-link"><i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> Đăng nhập</a>
                    <a href="index.php?page=register" class="account-link account-link-primary"><i class="fa-solid fa-user-plus" aria-hidden="true"></i> Đăng ký</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>