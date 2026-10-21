<!DOCTYPE html>
<?php 
require_once __DIR__ . '/config/database.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
?>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hoàng Long Ngũ - TH28.14</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css">
    <link rel="shortcut icon" href="assets/images/logo.ico">
</head>
<body>
    <div class="container">
    <?php require __DIR__ . '/partials/header.php'; ?>
        <?php
            $allowedPages = [
                'home', 'products', 'cart', 'history', 'feeds', 'contact',
                'checkout', 'detail', 'login', 'logout', 'register',
            ];
            $requestedPage = isset($_GET['page']) && is_string($_GET['page'])
                ? $_GET['page']
                : 'home';
            $page = in_array($requestedPage, $allowedPages, true) ? $requestedPage : 'home';

            switch($page) {
                case 'home':
                    require __DIR__ . '/pages/home.php';
                    break;
                case 'products':
                    require __DIR__ . '/pages/products.php';
                    break;
                case 'cart':
                    require __DIR__ . '/pages/cart.php';
                    break;
                case 'history':
                    require __DIR__ . '/pages/history.php';
                    break;
                case 'feeds':
                    require __DIR__ . '/pages/feeds.php';
                    break;
                case 'contact':
                    require __DIR__ . '/pages/contact.php';
                    break;
                case 'checkout':
                    require __DIR__ . '/pages/checkout.php';
                    break;
                case 'detail':
                    require __DIR__ . '/pages/detail.php';
                    break;
                case 'login':
                    include 'auth/login.php';
                    break;
                case 'logout':
                    include 'auth/logout.php';
                    break;
                case 'register':
                    include 'auth/register.php';
                    break;
                default:
                    require __DIR__ . '/pages/home.php';
                    break;
            }
        ?>

        <?php require __DIR__ . '/partials/footer.php'; ?>
    </div>
</body>
</html>