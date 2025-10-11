<!DOCTYPE html>
<?php 
include("dbconnect.php");
session_start();
?>
<html>
<head>
    <meta charset="utf-8">
    <title>Hoàng Long Ngũ - TH28.14</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="shortcut icon" href="images/logo.ico">
</head>
<body>
    <div class="container">
    <?php include 'header.php'; ?>
        <?php
            $page = isset($_GET['page']) ? $_GET['page'] : 'home';

            switch($page) {
                case 'home':
                    include 'home.php';
                    break;
                case 'products':
                    include 'products.php';
                    break;
                case 'cart':
                    include 'cart.php';
                    break;
                case 'history':
                    include 'history.php';
                    break;
                case 'feeds':
                    include 'feeds.php';
                    break;
                case 'contact':
                    include 'contact.php';
                    break;
                case 'checkout':
                    include 'checkout.php';
                    break;
                case 'detail':
                    include 'detail.php';
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
                    include 'home.php';
                    break;
            }
        ?>

        <?php include 'footer.php'; ?>
    </div>
</body>
</html>