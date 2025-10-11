<?php
session_start();
include 'db_connect.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Trang chủ - Sản phẩm nổi bật</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />

    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 40px 20px;
        }

        h2 {
            text-align: center;
            margin: 30px 0;
            font-size: 28px;
            color: #222;
        }

        .swiper {
            width: 100%;
            max-width: 1100px;
            padding-top: 50px;
            padding-bottom: 50px;
            margin: 0 auto;
        }

        .swiper-slide {
            background: #fff;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 300px;
            padding: 20px;
        }

        .swiper-slide img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }

        .product-name {
            color: black;
            margin-top: 15px;
            font-weight: bold;
            font-size: 18px;
        }

        .product-price {
            color: red;
            font-size: 16px;
        }

        .product-old-price {
            color: #999;
            font-size: 14px;
            text-decoration: line-through;
        }

        .discount {
            color: green;
            font-size: 14px;
        }

        @media screen and (max-width: 768px) {
            .swiper-slide {
                width: 250px;
            }
        }
    </style>
</head>
<body>

    <h2>🔥SẢN PHẨM NỔI BẬT</h2>

    <div class="swiper mySwiper">
        <div class="swiper-wrapper">
            <?php
            $query = "SELECT * FROM PRODUCTS ORDER BY productDiscount DESC LIMIT 10";
            $result = mysqli_query($ketnoi, $query);
            while ($product = mysqli_fetch_assoc($result)) {
                $discountAmount = $product['productPrice'] * $product['productDiscount'] / 100;
                $finalPrice = $product['productPrice'] - $discountAmount;
            ?>
                <div class="swiper-slide">
                    <img src="<?php echo $product['productImage']; ?>" alt="<?php echo $product['productName']; ?>">
                    <div class="product-name"><?php echo $product['productName']; ?></div>
                    <div class="product-price"><?php echo number_format($finalPrice, 0, ',', '.'); ?>₫</div>
                    <div class="product-old-price"><?php echo number_format($product['productPrice'], 0, ',', '.'); ?>₫</div>
                    <div class="discount">Giảm <?php echo $product['productDiscount']; ?>%</div>
                </div>
            <?php } ?>
        </div>

        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-pagination"></div>
    </div>
    
    <div id="buy-now"><a href="index.php?page=products">Mua Ngay</a></div>

    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <script>
        const swiper = new Swiper('.mySwiper', {
            effect: 'coverflow',
            grabCursor: true,
            centeredSlides: true,
            loop: true,
            slidesPerView: 'auto',
            autoplay: {
                delay: 1000,
                disableOnInteraction: false,
            },
            coverflowEffect: {
                rotate: 50,
                stretch: 0,
                depth: 100,
                modifier: 1,
                slideShadows: true,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
        });
    </script>

</body>
</html>
