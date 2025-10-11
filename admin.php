<?php
// admins.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'dbconnect.php';

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
if (!isset($_SESSION['user'])) {
    die("Bạn chưa đăng nhập!");
}

if (!$_SESSION['user']['is_admin']) {
    die("Bạn không có quyền truy cập trang này!");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_product':
                $stmt = mysqli_prepare($ketnoi, "INSERT INTO products (productName, productPrice, productImage, productCompany, productDetail, productDiscount) VALUES (?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "sdsssd", $_POST['productName'], $_POST['productPrice'], $_POST['productImage'], $_POST['productCompany'], $_POST['productDetail'], $_POST['productDiscount']);
                mysqli_stmt_execute($stmt);
                break;

            case 'edit_product':
                $stmt = mysqli_prepare($ketnoi, "UPDATE products SET productName=?, productPrice=?, productImage=?, productCompany=?, productDetail=?, productDiscount=? WHERE productId=?");
                mysqli_stmt_bind_param($stmt, "sdssssi", $_POST['productName'], $_POST['productPrice'], $_POST['productImage'], $_POST['productCompany'], $_POST['productDetail'], $_POST['productDiscount'], $_POST['productId']);
                mysqli_stmt_execute($stmt);
                break;

            case 'delete_product':
                $stmt = mysqli_prepare($ketnoi, "DELETE FROM products WHERE productId=?");
                mysqli_stmt_bind_param($stmt, "i", $_POST['productId']);
                mysqli_stmt_execute($stmt);
                break;

            case 'add_feed':
                $stmt = mysqli_prepare($ketnoi, "INSERT INTO feeds (feedTitle, feedContent, feedImage) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "sss", $_POST['feedTitle'], $_POST['feedContent'], $_POST['feedImage']);
                mysqli_stmt_execute($stmt);
                break;

            case 'edit_feed':
                $stmt = mysqli_prepare($ketnoi, "UPDATE feeds SET feedTitle=?, feedContent=?, feedImage=? WHERE feedId=?");
                mysqli_stmt_bind_param($stmt, "sssi", $_POST['feedTitle'], $_POST['feedContent'], $_POST['feedImage'], $_POST['feedId']);
                mysqli_stmt_execute($stmt);
                break;

            case 'delete_feed':
                $stmt = mysqli_prepare($ketnoi, "DELETE FROM feeds WHERE feedId=?");
                mysqli_stmt_bind_param($stmt, "i", $_POST['feedId']);
                mysqli_stmt_execute($stmt);
                break;

            case 'edit_user':
                $stmt = mysqli_prepare($ketnoi, "UPDATE users SET userName=?, userEmail=?, userPassword=? WHERE userId=?");
                mysqli_stmt_bind_param($stmt, "sssi", $_POST['userName'], $_POST['userEmail'], $_POST['userPassword'], $_POST['userId']);
                mysqli_stmt_execute($stmt);

                $userId = $_POST['userId'];
                $isAdminChecked = isset($_POST['isAdmin']);
                $adminCheck = mysqli_query($ketnoi, "SELECT * FROM admins WHERE userId = $userId");
                $hasAdminRow = mysqli_num_rows($adminCheck) > 0;

                if ($isAdminChecked && !$hasAdminRow) {
                    mysqli_query($ketnoi, "INSERT INTO admins (userId) VALUES ($userId)");
                } elseif (!$isAdminChecked && $hasAdminRow) {
                    mysqli_query($ketnoi, "DELETE FROM admins WHERE userId = $userId");
                }
                break;

            case 'delete_user':
                $stmt = mysqli_prepare($ketnoi, "DELETE FROM users WHERE userId=?");
                mysqli_stmt_bind_param($stmt, "i", $_POST['userId']);
                mysqli_stmt_execute($stmt);
                mysqli_query($ketnoi, "DELETE FROM admins WHERE userId = {$_POST['userId']}");
                break;

            case 'add_user':
                $stmt = mysqli_prepare($ketnoi, "INSERT INTO users (userName, userEmail, userPassword) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "sss", $_POST['userName'], $_POST['userEmail'], $_POST['userPassword']);
                mysqli_stmt_execute($stmt);
                break;
        }
    }
}

$products = mysqli_query($ketnoi, "SELECT * FROM products");
$feeds = mysqli_query($ketnoi, "SELECT * FROM feeds");
$users = mysqli_query($ketnoi, "SELECT * FROM users");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quản trị hệ thống</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 20px;
            background: linear-gradient(to bottom right, #ecf0f1, #dfe6e9);
            color: #2d3436;
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 40px;
        }
        h2 {
            background-color: #0984e3;
            color: white;
            padding: 10px;
            border-radius: 6px;
            margin-top: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background-color: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 6px;
            overflow: hidden;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #dfe6e9;
        }
        input[type="text"], input[type="number"], input[type="email"], input[type="password"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #00b894;
            color: white;
            border: none;
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 4px;
            font-weight: bold;
        }
        button:hover {
            background-color: #019875;
        }
        button.delete {
            background-color: #d63031;
        }
        button.delete:hover {
            background-color: #c0392b;
        }
        form {
            margin: 0;
        }
    </style>
</head>
<body>
<h1>Trang Quản Trị</h1>

<h2>Sản phẩm</h2>
<!-- Form thêm sản phẩm -->
<form method="post">
    <input type="hidden" name="action" value="add_product">
    <table>
        <tr><td colspan="7"><strong>Thêm sản phẩm mới:</strong></td></tr>
        <tr>
            <td><input name="productName" placeholder="Tên sản phẩm" required></td>
            <td><input name="productPrice" placeholder="Giá" type="number" required></td>
            <td><input name="productImage" placeholder="Hình ảnh"></td>
            <td><input name="productCompany" placeholder="Hãng"></td>
            <td><input name="productDetail" placeholder="Mô tả"></td>
            <td><input name="productDiscount" placeholder="Giảm giá" type="number"></td>
            <td><button type="submit">Thêm</button></td>
        </tr>
    </table>
</form>

<table>
    <tr><th>ID</th><th>Tên sản phẩm</th><th>Giá</th><th>Hình ảnh</th><th>Hãng</th><th>Mô tả</th><th>Giảm giá</th><th>Thao tác</th></tr>
    <?php while($row = mysqli_fetch_assoc($products)): ?>
    <tr>
        <form method="post">
            <input type="hidden" name="action" value="edit_product">
            <input type="hidden" name="productId" value="<?= $row['productId'] ?>">
            <td><?= $row['productId'] ?></td>
            <td><input name="productName" value="<?= $row['productName'] ?>"></td>
            <td><input name="productPrice" value="<?= $row['productPrice'] ?>"></td>
            <td><input name="productImage" value="<?= $row['productImage'] ?>"></td>
            <td><input name="productCompany" value="<?= $row['productCompany'] ?>"></td>
            <td><input name="productDetail" value="<?= $row['productDetail'] ?>"></td>
            <td><input name="productDiscount" value="<?= $row['productDiscount'] ?>"></td>
            <td>
                <button type="submit">Lưu</button>
        </form>
        <form method="post" style="display:inline">
            <input type="hidden" name="action" value="delete_product">
            <input type="hidden" name="productId" value="<?= $row['productId'] ?>">
            <button type="submit" style="background-color:#e74c3c;">Xoá</button>
        </form>
            </td>
    </tr>
    <?php endwhile; ?>
</table>

<h2>Bản tin</h2>
<form method="post">
    <input type="hidden" name="action" value="add_feed">
    <table>
        <tr><td colspan="4"><strong>Thêm bản tin mới:</strong></td></tr>
        <tr>
            <td><input name="feedTitle" placeholder="Tiêu đề" required></td>
            <td><input name="feedContent" placeholder="Nội dung"></td>
            <td><input name="feedImage" placeholder="Hình ảnh"></td>
            <td><button type="submit">Thêm</button></td>
        </tr>
    </table>
</form>

<table>
    <tr><th>ID</th><th>Tiêu đề</th><th>Nội dung</th><th>Hình ảnh</th><th>Thao tác</th></tr>
    <?php while($row = mysqli_fetch_assoc($feeds)): ?>
    <tr>
        <form method="post">
            <input type="hidden" name="action" value="edit_feed">
            <input type="hidden" name="feedId" value="<?= $row['feedId'] ?>">
            <td><?= $row['feedId'] ?></td>
            <td><input name="feedTitle" value="<?= $row['feedTitle'] ?>"></td>
            <td><input name="feedContent" value="<?= $row['feedContent'] ?>"></td>
            <td><input name="feedImage" value="<?= $row['feedImage'] ?>"></td>
            <td>
                <button type="submit">Lưu</button>
        </form>
        <form method="post" style="display:inline">
            <input type="hidden" name="action" value="delete_feed">
            <input type="hidden" name="feedId" value="<?= $row['feedId'] ?>">
            <button type="submit" style="background-color:#e74c3c;">Xoá</button>
        </form>
            </td>
    </tr>
    <?php endwhile; ?>
</table>

<h2>Người dùng</h2>
<form method="post">
    <input type="hidden" name="action" value="add_user">
    <table>
        <tr><td colspan="5"><strong>Thêm người dùng mới:</strong></td></tr>
        <tr>
            <td><input name="userName" placeholder="Tên người dùng" required></td>
            <td><input name="userEmail" type="email" placeholder="Email" required></td>
            <td><input name="userPassword" type="password" placeholder="Mật khẩu" required></td>
            <td colspan="2"><button type="submit">Thêm</button></td>
        </tr>
    </table>
</form>

<table>
    <tr><th>ID</th><th>Tên</th><th>Email</th><th>Mật khẩu</th><th>Quản trị</th><th>Thao tác</th></tr>
    <?php while($row = mysqli_fetch_assoc($users)): ?>
    <tr>
        <form method="post">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" name="userId" value="<?= $row['userId'] ?>">
            <td><?= $row['userId'] ?></td>
            <td><input name="userName" value="<?= $row['userName'] ?>"></td>
            <td><input name="userEmail" value="<?= $row['userEmail'] ?>"></td>
            <td><input name="userPassword" value="<?= $row['userPassword'] ?>"></td>
            <td>
                <?php
                $isAdmin = mysqli_query($ketnoi, "SELECT * FROM admins WHERE userId = {$row['userId']}");
                $checked = mysqli_num_rows($isAdmin) > 0 ? 'checked' : '';
                ?>
                <input type="checkbox" name="isAdmin" value="1" <?= $checked ?>>
            </td>
            <td>
                <button type="submit">Lưu</button>
        </form>
        <form method="post" style="display:inline">
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" name="userId" value="<?= $row['userId'] ?>">
            <button type="submit" style="background-color:#e74c3c;">Xoá</button>
        </form>
            </td>
    </tr>
    <?php endwhile; ?>
</table>
</body>
</html>
