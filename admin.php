<?php
// admins.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    die("Bạn chưa đăng nhập!");
}

if (!$_SESSION['user']['is_admin']) {
    http_response_code(403);
    die("Bạn không có quyền truy cập trang này!");
}

$orderStatuses = [
    'pending_payment' => 'Chờ thanh toán',
    'paid' => 'Đã thanh toán',
    'processing' => 'Đang chuẩn bị',
    'shipped' => 'Đang giao',
    'completed' => 'Hoàn tất',
    'cancelled' => 'Đã hủy',
];
$orderStatusTransitions = [
    'pending_payment' => ['paid', 'cancelled'],
    'paid' => ['processing'],
    'processing' => ['shipped'],
    'shipped' => ['completed'],
    'completed' => [],
    'cancelled' => [],
];
if (empty($_SESSION['admin_order_csrf_token'])) {
    $_SESSION['admin_order_csrf_token'] = bin2hex(random_bytes(32));
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'update_order_status'
) {
    $feedback = ['type' => 'error', 'message' => 'Không thể cập nhật trạng thái đơn hàng.'];
    $submittedToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : '';
    $postedOrderId = isset($_POST['orderId']) && is_string($_POST['orderId'])
        ? filter_var($_POST['orderId'], FILTER_VALIDATE_INT)
        : false;
    $orderId = $postedOrderId === false || $postedOrderId === null ? 0 : $postedOrderId;
    $newStatus = isset($_POST['orderStatus']) && is_string($_POST['orderStatus'])
        ? $_POST['orderStatus']
        : '';

    if (!hash_equals((string)$_SESSION['admin_order_csrf_token'], $submittedToken)) {
        $feedback['message'] = 'Phiên cập nhật hết hạn. Vui lòng tải lại trang và thử lại.';
    } elseif ($orderId < 1 || !isset($orderStatuses[$newStatus])) {
        $feedback['message'] = 'Dữ liệu trạng thái đơn hàng không hợp lệ.';
    } else {
        try {
            $ketnoi->begin_transaction();
            $statusStatement = $ketnoi->prepare('SELECT status FROM orders WHERE orderId = ? FOR UPDATE');
            $statusStatement->bind_param('i', $orderId);
            $statusStatement->execute();
            $currentStatusRow = $statusStatement->get_result()->fetch_assoc();
            $currentStatus = $currentStatusRow['status'] ?? false;
            $statusStatement->close();

            if ($currentStatus === false) {
                throw new RuntimeException('Không tìm thấy đơn hàng.');
            }

            if ($currentStatus === $newStatus) {
                $ketnoi->commit();
                $feedback = ['type' => 'success', 'message' => 'Đơn hàng đã ở trạng thái này.'];
            } elseif (!in_array($newStatus, $orderStatusTransitions[$currentStatus] ?? [], true)) {
                throw new RuntimeException('Không thể chuyển đơn hàng sang trạng thái đã chọn.');
            } else {
                $updateStatement = $ketnoi->prepare('UPDATE orders SET status = ? WHERE orderId = ?');
                $updateStatement->bind_param('si', $newStatus, $orderId);
                $updateStatement->execute();
                $updateStatement->close();
                $ketnoi->commit();
                $feedback = [
                    'type' => 'success',
                    'message' => 'Đã cập nhật trạng thái đơn ' . $orderId . ' thành “' . $orderStatuses[$newStatus] . '”.',
                ];
            }
        } catch (RuntimeException $exception) {
            $ketnoi->rollback();
            $feedback['message'] = $exception->getMessage();
        } catch (mysqli_sql_exception $exception) {
            $ketnoi->rollback();
            error_log('Unable to update order status: ' . $exception->getMessage());
            $feedback['message'] = 'Không thể cập nhật đơn hàng lúc này. Vui lòng thử lại sau.';
        }
    }

    $_SESSION['admin_feedback'] = $feedback;
    $customerId = filter_input(INPUT_POST, 'customerId', FILTER_VALIDATE_INT);
    $redirectQuery = ['tab' => 'orders'];
    if ($customerId !== false && $customerId !== null && $customerId > 0) {
        $redirectQuery['customer_id'] = $customerId;
    }
    header('Location: admin.php?' . http_build_query($redirectQuery), true, 303);
    exit;
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
                $userId = filter_input(INPUT_POST, 'userId', FILTER_VALIDATE_INT);
                $name = isset($_POST['userName']) && is_string($_POST['userName']) ? trim($_POST['userName']) : '';
                $email = isset($_POST['userEmail']) && is_string($_POST['userEmail']) ? trim($_POST['userEmail']) : '';
                $newPassword = isset($_POST['userPassword']) && is_string($_POST['userPassword'])
                    ? $_POST['userPassword']
                    : '';
                if (!$userId || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    break;
                }

                if ($newPassword === '') {
                    $stmt = mysqli_prepare($ketnoi, "UPDATE users SET userName=?, userEmail=? WHERE userId=?");
                    mysqli_stmt_bind_param($stmt, "ssi", $name, $email, $userId);
                } else {
                    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = mysqli_prepare($ketnoi, "UPDATE users SET userName=?, userEmail=?, userPassword=? WHERE userId=?");
                    mysqli_stmt_bind_param($stmt, "sssi", $name, $email, $passwordHash, $userId);
                }
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                $isAdminChecked = isset($_POST['isAdmin']);
                $adminStmt = mysqli_prepare($ketnoi, "SELECT userId FROM admins WHERE userId = ?");
                mysqli_stmt_bind_param($adminStmt, "i", $userId);
                mysqli_stmt_execute($adminStmt);
                $hasAdminRow = mysqli_stmt_get_result($adminStmt)->num_rows > 0;
                mysqli_stmt_close($adminStmt);

                if ($isAdminChecked && !$hasAdminRow) {
                    $adminStmt = mysqli_prepare($ketnoi, "INSERT INTO admins (userId) VALUES (?)");
                    mysqli_stmt_bind_param($adminStmt, "i", $userId);
                    mysqli_stmt_execute($adminStmt);
                } elseif (!$isAdminChecked && $hasAdminRow) {
                    $adminStmt = mysqli_prepare($ketnoi, "DELETE FROM admins WHERE userId = ?");
                    mysqli_stmt_bind_param($adminStmt, "i", $userId);
                    mysqli_stmt_execute($adminStmt);
                }
                break;

            case 'delete_user':
                $userId = filter_input(INPUT_POST, 'userId', FILTER_VALIDATE_INT);
                if (!$userId) {
                    break;
                }
                $stmt = mysqli_prepare($ketnoi, "DELETE FROM admins WHERE userId=?");
                mysqli_stmt_bind_param($stmt, "i", $userId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $stmt = mysqli_prepare($ketnoi, "DELETE FROM users WHERE userId=?");
                mysqli_stmt_bind_param($stmt, "i", $userId);
                mysqli_stmt_execute($stmt);
                break;

            case 'add_user':
                $name = isset($_POST['userName']) && is_string($_POST['userName']) ? trim($_POST['userName']) : '';
                $email = isset($_POST['userEmail']) && is_string($_POST['userEmail']) ? trim($_POST['userEmail']) : '';
                $password = isset($_POST['userPassword']) && is_string($_POST['userPassword'])
                    ? $_POST['userPassword']
                    : '';
                if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
                    break;
                }
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($ketnoi, "INSERT INTO users (userName, userEmail, userPassword) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "sss", $name, $email, $passwordHash);
                mysqli_stmt_execute($stmt);
                break;
        }
    }
}

$products = mysqli_query($ketnoi, "SELECT * FROM products");
$feeds = mysqli_query($ketnoi, "SELECT * FROM feeds");
$users = mysqli_query($ketnoi, "SELECT * FROM users");
$orderTotal = (int)$ketnoi->query('SELECT COUNT(*) FROM orders')->fetch_row()[0];
$pendingOrderTotal = (int)$ketnoi->query("SELECT COUNT(*) FROM orders WHERE status = 'pending_payment'")->fetch_row()[0];
$customerOrderStats = [];
$customerStatsResult = $ketnoi->query(
    'SELECT userId, COUNT(*) AS orderCount, SUM(totalAmount) AS totalSpent
     FROM orders
     WHERE userId IS NOT NULL AND status <> \'cancelled\'
     GROUP BY userId'
);
while ($customerStats = $customerStatsResult->fetch_assoc()) {
    $customerOrderStats[(int)$customerStats['userId']] = [
        'orderCount' => (int)$customerStats['orderCount'],
        'totalSpent' => (float)$customerStats['totalSpent'],
    ];
}

$customerIdFilter = filter_input(INPUT_GET, 'customer_id', FILTER_VALIDATE_INT);
$customerIdFilter = $customerIdFilter === false || $customerIdFilter === null || $customerIdFilter < 1
    ? 0
    : $customerIdFilter;
if ($customerIdFilter > 0) {
    $orderStatement = $ketnoi->prepare(
        'SELECT o.orderId, o.orderCode, o.userId, o.status, o.totalAmount, o.createdAt,
                o.updatedAt, u.userName, u.userEmail
         FROM orders o
         LEFT JOIN users u ON u.userId = o.userId
         WHERE o.userId = ?
         ORDER BY o.createdAt DESC, o.orderId DESC
         LIMIT 100'
    );
    $orderStatement->bind_param('i', $customerIdFilter);
    $orderStatement->execute();
    $ordersResult = $orderStatement->get_result();
} else {
    $ordersResult = $ketnoi->query(
        'SELECT o.orderId, o.orderCode, o.userId, o.status, o.totalAmount, o.createdAt,
                o.updatedAt, u.userName, u.userEmail
         FROM orders o
         LEFT JOIN users u ON u.userId = o.userId
         ORDER BY o.createdAt DESC, o.orderId DESC
         LIMIT 100'
    );
}
$orders = [];
$orderIds = [];
while ($order = $ordersResult->fetch_assoc()) {
    $orderId = (int)$order['orderId'];
    $order['items'] = [];
    $orders[$orderId] = $order;
    $orderIds[] = $orderId;
}
if (isset($orderStatement)) {
    $orderStatement->close();
}
if ($orderIds !== []) {
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $itemStatement = $ketnoi->prepare(
        'SELECT orderId, productName, productImage, unitPrice, quantity, lineTotal
         FROM order_items
         WHERE orderId IN (' . $placeholders . ')
         ORDER BY orderId DESC, orderItemId ASC'
    );
    $itemTypes = str_repeat('i', count($orderIds));
    $itemStatement->bind_param($itemTypes, ...$orderIds);
    $itemStatement->execute();
    $orderItemsResult = $itemStatement->get_result();
    while ($item = $orderItemsResult->fetch_assoc()) {
        $orders[(int)$item['orderId']]['items'][] = $item;
    }
    $itemStatement->close();
}
$tabActions = [
    'add_product' => 'products',
    'edit_product' => 'products',
    'delete_product' => 'products',
    'add_feed' => 'feeds',
    'edit_feed' => 'feeds',
    'delete_feed' => 'feeds',
    'add_user' => 'users',
    'edit_user' => 'users',
    'delete_user' => 'users',
    'update_order_status' => 'orders',
];
$postedAction = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';
$requestedTab = isset($_GET['tab']) && is_string($_GET['tab']) ? $_GET['tab'] : '';
$activeAdminTab = $tabActions[$postedAction] ?? (
    in_array($requestedTab, ['products', 'feeds', 'users', 'orders'], true) ? $requestedTab : 'products'
);
$adminFeedback = $_SESSION['admin_feedback'] ?? null;
unset($_SESSION['admin_feedback']);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản trị hệ thống</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body class="admin-page">
<main class="admin-shell">
<header class="admin-header">
    <div>
        <span class="admin-eyebrow">HOÀNG LONG NGŨ / QUẢN TRỊ</span>
        <h1>Bảng điều khiển</h1>
        <p>Quản lý sản phẩm, bản tin và tài khoản trên cùng một nơi.</p>
    </div>
    <a class="admin-home-link" href="index.php?page=home"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>Xem cửa hàng</a>
</header>

<nav class="admin-nav" role="tablist" aria-label="Điều hướng quản trị">
    <button id="tab-products" type="button" role="tab" aria-controls="panel-products" aria-selected="<?= $activeAdminTab === 'products' ? 'true' : 'false' ?>" tabindex="<?= $activeAdminTab === 'products' ? '0' : '-1' ?>" class="<?= $activeAdminTab === 'products' ? 'is-active' : '' ?>" data-admin-tab="products"><i class="fa-solid fa-cubes" aria-hidden="true"></i>Sản phẩm</button>
    <button id="tab-feeds" type="button" role="tab" aria-controls="panel-feeds" aria-selected="<?= $activeAdminTab === 'feeds' ? 'true' : 'false' ?>" tabindex="<?= $activeAdminTab === 'feeds' ? '0' : '-1' ?>" class="<?= $activeAdminTab === 'feeds' ? 'is-active' : '' ?>" data-admin-tab="feeds"><i class="fa-solid fa-newspaper" aria-hidden="true"></i>Bản tin</button>
    <button id="tab-users" type="button" role="tab" aria-controls="panel-users" aria-selected="<?= $activeAdminTab === 'users' ? 'true' : 'false' ?>" tabindex="<?= $activeAdminTab === 'users' ? '0' : '-1' ?>" class="<?= $activeAdminTab === 'users' ? 'is-active' : '' ?>" data-admin-tab="users"><i class="fa-solid fa-users" aria-hidden="true"></i>Người dùng</button>
    <button id="tab-orders" type="button" role="tab" aria-controls="panel-orders" aria-selected="<?= $activeAdminTab === 'orders' ? 'true' : 'false' ?>" tabindex="<?= $activeAdminTab === 'orders' ? '0' : '-1' ?>" class="<?= $activeAdminTab === 'orders' ? 'is-active' : '' ?>" data-admin-tab="orders"><i class="fa-solid fa-receipt" aria-hidden="true"></i>Đơn hàng <span class="admin-nav-count"><?= $pendingOrderTotal ?></span></button>
</nav>

<?php if (is_array($adminFeedback)): ?>
    <p class="admin-feedback is-<?= e($adminFeedback['type'] ?? 'error') ?>" role="status">
        <?= e($adminFeedback['message'] ?? '') ?>
    </p>
<?php endif; ?>

<section class="admin-stats" aria-label="Tổng quan dữ liệu">
    <article class="admin-stat"><span class="admin-stat-icon"><i class="fa-solid fa-cubes" aria-hidden="true"></i></span><div><span>Sản phẩm</span><strong><?= (int)$products->num_rows ?></strong></div></article>
    <article class="admin-stat"><span class="admin-stat-icon"><i class="fa-solid fa-newspaper" aria-hidden="true"></i></span><div><span>Bản tin</span><strong><?= (int)$feeds->num_rows ?></strong></div></article>
    <article class="admin-stat"><span class="admin-stat-icon"><i class="fa-solid fa-users" aria-hidden="true"></i></span><div><span>Tài khoản</span><strong><?= (int)$users->num_rows ?></strong></div></article>
    <article class="admin-stat"><span class="admin-stat-icon"><i class="fa-solid fa-receipt" aria-hidden="true"></i></span><div><span>Đơn hàng / chờ thanh toán</span><strong><?= $orderTotal ?> / <?= $pendingOrderTotal ?></strong></div></article>
</section>

<section class="admin-tab-panel" id="panel-products" role="tabpanel" aria-labelledby="tab-products" <?= $activeAdminTab !== 'products' ? 'hidden' : '' ?>>
<h2 id="products"><i class="fa-solid fa-cubes" aria-hidden="true"></i>Sản phẩm <span><?= (int)$products->num_rows ?> mặt hàng</span></h2>
<!-- Form thêm sản phẩm -->
<form method="post" class="admin-create-form admin-create-product">
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
            <input type="hidden" name="productId" value="<?= (int)$row['productId'] ?>">
            <td><?= (int)$row['productId'] ?></td>
            <td><input name="productName" value="<?= e($row['productName']) ?>"></td>
            <td><input name="productPrice" value="<?= e($row['productPrice']) ?>"></td>
            <td><input name="productImage" value="<?= e($row['productImage']) ?>"></td>
            <td><input name="productCompany" value="<?= e($row['productCompany']) ?>"></td>
            <td><input name="productDetail" value="<?= e($row['productDetail']) ?>"></td>
            <td><input name="productDiscount" value="<?= e($row['productDiscount']) ?>"></td>
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

 </section>
<section class="admin-tab-panel" id="panel-feeds" role="tabpanel" aria-labelledby="tab-feeds" <?= $activeAdminTab !== 'feeds' ? 'hidden' : '' ?>>
<h2 id="feeds"><i class="fa-solid fa-newspaper" aria-hidden="true"></i>Bản tin <span><?= (int)$feeds->num_rows ?> bài viết</span></h2>
<form method="post" class="admin-create-form admin-create-feed">
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
            <input type="hidden" name="feedId" value="<?= (int)$row['feedId'] ?>">
            <td><?= (int)$row['feedId'] ?></td>
            <td><input name="feedTitle" value="<?= e($row['feedTitle']) ?>"></td>
            <td><input name="feedContent" value="<?= e($row['feedContent']) ?>"></td>
            <td><input name="feedImage" value="<?= e($row['feedImage']) ?>"></td>
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

 </section>
<section class="admin-tab-panel" id="panel-users" role="tabpanel" aria-labelledby="tab-users" <?= $activeAdminTab !== 'users' ? 'hidden' : '' ?>>
<h2 id="users"><i class="fa-solid fa-users" aria-hidden="true"></i>Người dùng <span><?= (int)$users->num_rows ?> tài khoản</span></h2>
<form method="post" class="admin-create-form admin-create-user">
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
    <tr><th>ID</th><th>Tên</th><th>Email</th><th>Mật khẩu</th><th>Đơn hàng</th><th>Tổng đã đặt</th><th>Quản trị</th><th>Thao tác</th></tr>
    <?php while($row = mysqli_fetch_assoc($users)): ?>
    <tr>
        <form method="post">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" name="userId" value="<?= (int)$row['userId'] ?>">
            <td><?= (int)$row['userId'] ?></td>
            <td><input name="userName" value="<?= e($row['userName']) ?>"></td>
            <td><input name="userEmail" type="email" value="<?= e($row['userEmail']) ?>"></td>
            <td><input name="userPassword" type="password" value="" placeholder="Để trống nếu không đổi"></td>
            <?php $customerStats = $customerOrderStats[(int)$row['userId']] ?? ['orderCount' => 0, 'totalSpent' => 0]; ?>
            <td>
                <?php if ($customerStats['orderCount'] > 0): ?>
                    <a class="admin-customer-orders" href="admin.php?tab=orders&amp;customer_id=<?= (int)$row['userId'] ?>">
                        <?= $customerStats['orderCount'] ?> đơn
                    </a>
                <?php else: ?>
                    <span class="admin-no-orders">Chưa mua</span>
                <?php endif; ?>
            </td>
            <td><?= number_format($customerStats['totalSpent'], 0, ',', '.') ?> VND</td>
            <td>
                <?php
                $adminStmt = mysqli_prepare($ketnoi, "SELECT userId FROM admins WHERE userId = ?");
                mysqli_stmt_bind_param($adminStmt, "i", $row['userId']);
                mysqli_stmt_execute($adminStmt);
                $checked = mysqli_stmt_get_result($adminStmt)->num_rows > 0 ? 'checked' : '';
                mysqli_stmt_close($adminStmt);
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
</section>

<section class="admin-tab-panel" id="panel-orders" role="tabpanel" aria-labelledby="tab-orders" <?= $activeAdminTab !== 'orders' ? 'hidden' : '' ?>>
    <h2><i class="fa-solid fa-receipt" aria-hidden="true"></i>Lịch sử mua hàng <span><?= count($orders) ?> đơn gần nhất</span></h2>

    <?php if ($customerIdFilter > 0): ?>
        <p class="admin-order-filter">
            Đang xem đơn hàng của tài khoản #<?= $customerIdFilter ?>.
            <a href="admin.php?tab=orders">Xem tất cả đơn hàng</a>
        </p>
    <?php endif; ?>
    <p class="admin-order-filter">
        Chỉ cập nhật thành “Đã thanh toán” sau khi đã đối chiếu giao dịch chuyển khoản thực tế.
    </p>

    <?php if ($orders === []): ?>
        <p class="admin-empty-state">Chưa có đơn hàng phù hợp.</p>
    <?php else: ?>
        <div class="admin-order-list">
            <?php foreach ($orders as $order): ?>
                <?php
                $status = (string)$order['status'];
                $statusLabel = $orderStatuses[$status] ?? 'Trạng thái không xác định';
                $nextStatuses = $orderStatusTransitions[$status] ?? [];
                $canUpdateOrder = $nextStatuses !== [];
                ?>
                <article class="admin-order-card">
                    <header class="admin-order-header">
                        <div>
                            <span class="admin-order-eyebrow">MÃ ĐƠN</span>
                            <h3><?= e($order['orderCode']) ?></h3>
                            <time datetime="<?= e(date(DATE_ATOM, strtotime($order['createdAt']))) ?>">
                                Đặt lúc <?= e(date('d/m/Y H:i', strtotime($order['createdAt']))) ?>
                            </time>
                        </div>
                        <span class="admin-order-status status-<?= e($status) ?>"><?= e($statusLabel) ?></span>
                    </header>

                    <div class="admin-order-customer">
                        <i class="fa-solid fa-user" aria-hidden="true"></i>
                        <div>
                            <?php if ($order['userId'] !== null): ?>
                                <strong><?= e($order['userName'] ?? 'Tài khoản đã xóa') ?></strong>
                                <span><?= e($order['userEmail'] ?? 'Không còn email tài khoản') ?></span>
                                <a href="admin.php?tab=orders&amp;customer_id=<?= (int)$order['userId'] ?>">Xem lịch sử mua của khách</a>
                            <?php else: ?>
                                <strong>Khách mua không đăng nhập</strong>
                                <span>Lịch sử được lưu theo phiên khách hàng.</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <ul class="admin-order-items">
                        <?php foreach ($order['items'] as $item): ?>
                            <li>
                                <img src="<?= e($item['productImage']) ?>" width="48" height="48" loading="lazy" alt="">
                                <span><?= e($item['productName']) ?> × <?= (int)$item['quantity'] ?></span>
                                <strong><?= number_format((float)$item['lineTotal'], 0, ',', '.') ?> VND</strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if ($status === 'pending_payment'): ?>
                        <p class="admin-order-transfer-content">
                            Nội dung chuyển khoản khách cần ghi:
                            <strong><?= e(build_transfer_content($order['orderCode'])) ?></strong>
                        </p>
                    <?php endif; ?>

                    <footer class="admin-order-footer">
                        <div>
                            <span>Tổng đơn hàng</span>
                            <strong><?= number_format((float)$order['totalAmount'], 0, ',', '.') ?> VND</strong>
                        </div>
                        <?php if ($canUpdateOrder): ?>
                            <form method="post" class="admin-order-status-form">
                                <input type="hidden" name="action" value="update_order_status">
                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['admin_order_csrf_token']) ?>">
                                <input type="hidden" name="orderId" value="<?= (int)$order['orderId'] ?>">
                                <?php if ($customerIdFilter > 0): ?>
                                    <input type="hidden" name="customerId" value="<?= $customerIdFilter ?>">
                                <?php endif; ?>
                                <label class="visually-hidden" for="order-status-<?= (int)$order['orderId'] ?>">Trạng thái đơn hàng <?= e($order['orderCode']) ?></label>
                                <select id="order-status-<?= (int)$order['orderId'] ?>" name="orderStatus" required>
                                    <?php foreach ($nextStatuses as $nextStatus): ?>
                                        <option value="<?= e($nextStatus) ?>"><?= e($orderStatuses[$nextStatus]) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>Cập nhật</button>
                            </form>
                        <?php else: ?>
                            <span class="admin-order-terminal">Đơn hàng đã kết thúc</span>
                        <?php endif; ?>
                    </footer>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
</main>
<script>
document.querySelectorAll('[data-admin-tab]').forEach((tab) => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('[data-admin-tab]').forEach((otherTab) => {
            const isActive = otherTab === tab;
            otherTab.setAttribute('aria-selected', String(isActive));
            otherTab.tabIndex = isActive ? 0 : -1;
            otherTab.classList.toggle('is-active', isActive);
            document.getElementById(otherTab.getAttribute('aria-controls')).hidden = !isActive;
        });
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('tab', tab.dataset.adminTab);
        window.history.replaceState({}, '', currentUrl);
    });

    tab.addEventListener('keydown', (event) => {
        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
            return;
        }

        event.preventDefault();
        const tabs = [...document.querySelectorAll('[data-admin-tab]')];
        const currentIndex = tabs.indexOf(tab);
        const direction = event.key === 'ArrowRight' ? 1 : -1;
        const nextTab = tabs[(currentIndex + direction + tabs.length) % tabs.length];
        nextTab.focus();
        nextTab.click();
    });
});
</script>
</body>
</html>
