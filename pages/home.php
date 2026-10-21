<?php
$statement = $ketnoi->prepare(
    'SELECT productId, productName, productImage, productPrice, productDiscount
     FROM products
     ORDER BY productDiscount DESC, productId DESC
     LIMIT 10'
);
$statement->execute();
$featuredProducts = $statement->get_result();
?>

<main class="home-page">
    <section class="home-hero" aria-labelledby="home-title">
        <div class="home-hero-content">
            <span class="home-hero-kicker"><i class="fa-solid fa-cubes" aria-hidden="true"></i> Thế giới mô hình</span>
            <h1 id="home-title">Góc nhỏ cho <span>đam mê sưu tầm</span></h1>
            <p>Khám phá những mẫu mô hình thú vị, chọn món yêu thích và tạo nên bộ sưu tập mang phong cách của riêng bạn.</p>
            <div class="home-hero-actions">
                <a class="home-hero-primary" href="index.php?page=products"><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i> Khám phá sản phẩm</a>
                <a class="home-hero-secondary" href="index.php?page=feeds">Xem tin mới <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
        </div>
        <div class="home-hero-art" aria-hidden="true">
            <span class="home-hero-orbit home-hero-orbit-outer"></span>
            <span class="home-hero-orbit home-hero-orbit-inner"></span>
            <span class="home-hero-cube"><i class="fa-solid fa-cube"></i></span>
            <span class="home-hero-spark home-hero-spark-one"></span>
            <span class="home-hero-spark home-hero-spark-two"></span>
        </div>
    </section>

    <section class="featured-products" aria-labelledby="featured-title">
    <div class="featured-heading">
        <div>
            <span class="featured-kicker">ĐƯỢC YÊU THÍCH</span>
            <h2 class="featured-title" id="featured-title">Sản phẩm nổi bật</h2>
        </div>
        <a class="featured-all-link" href="index.php?page=products">Xem tất cả <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
    </div>

    <?php if ($featuredProducts->num_rows > 0): ?>
        <div class="swiper mySwiper">
            <div class="swiper-wrapper">
                <?php while ($product = $featuredProducts->fetch_assoc()): ?>
                    <?php
                    $price = (float)$product['productPrice'];
                    $discount = (float)$product['productDiscount'];
                    $finalPrice = $price - ($price * $discount / 100);
                    ?>
                    <article class="swiper-slide">
                        <a href="index.php?page=detail&amp;productId=<?= (int)$product['productId'] ?>">
                            <img src="<?= e($product['productImage']) ?>" alt="<?= e($product['productName']) ?>" loading="lazy">
                            <div class="product-name"><?= e($product['productName']) ?></div>
                        </a>
                        <div class="product-price"><?= number_format($finalPrice, 0, ',', '.') ?> VND</div>
                        <?php if ($discount > 0): ?>
                            <div class="product-old-price"><?= number_format($price, 0, ',', '.') ?> VND</div>
                            <div class="discount">Giảm <?= e($discount) ?>%</div>
                        <?php endif; ?>
                    </article>
                <?php endwhile; ?>
            </div>
            <button class="swiper-button-prev" type="button" aria-label="Sản phẩm trước"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
            <button class="swiper-button-next" type="button" aria-label="Sản phẩm tiếp theo"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
            <div class="swiper-pagination"></div>
        </div>
    <?php else: ?>
        <p class="empty-state">Chưa có sản phẩm nổi bật.</p>
    <?php endif; ?>

    <div id="buy-now"><a href="index.php?page=products"><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i> Xem toàn bộ sản phẩm</a></div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
<script>
if (document.querySelector('.mySwiper')) {
    new Swiper('.mySwiper', {
        effect: 'coverflow',
        grabCursor: true,
        centeredSlides: true,
        loop: true,
        slidesPerView: 'auto',
        autoplay: { delay: 3000, disableOnInteraction: false },
        coverflowEffect: { rotate: 50, stretch: 0, depth: 100, modifier: 1, slideShadows: true },
        pagination: { el: '.swiper-pagination', clickable: true },
        navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' }
    });
}
</script>
