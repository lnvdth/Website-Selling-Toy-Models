# Website bán mô hình đồ chơi

Website thương mại điện tử xây dựng bằng PHP và MySQL, gồm danh mục sản phẩm, giỏ hàng, đặt hàng, lịch sử mua hàng và trang quản trị.

## Cấu trúc chính

- `index.php`: điểm vào và bộ định tuyến trang bán hàng.
- `admin.php`: trang quản trị.
- `auth/`: đăng nhập, đăng ký và đăng xuất.
- `config/`: cấu hình kết nối cơ sở dữ liệu và thanh toán.
- `database/migrations/`: các migration cơ sở dữ liệu.
- `includes/`: hàm dùng chung.
- `pages/`: nội dung các trang.
- `partials/`: header và footer.
- `assets/`: stylesheet, hình ảnh và tài nguyên giao diện.

## Yêu cầu

- PHP 7 trở lên với extension `mysqli` và `mysqlnd`.
- MySQL hoặc MariaDB.
- Máy chủ web như Apache (có thể chạy qua USBWebServer).

## Cài đặt

1. Đặt dự án trong thư mục web của máy chủ.
2. Tạo cơ sở dữ liệu và các bảng nền tảng mà ứng dụng sử dụng: `products`, `users`, `feeds` và `admins`.
3. Cấu hình kết nối bằng các biến môi trường `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD` và `DB_DATABASE`. Nếu không đặt, ứng dụng dùng giá trị mặc định trong `config/database.php`.
4. Chạy `database/migrations/001_create_orders.sql` trên cơ sở dữ liệu để tạo các bảng đơn hàng.
5. Mở dự án qua máy chủ web tại `index.php`; không mở trực tiếp các tệp PHP trong `pages/`.

## Cấu hình thanh toán

Thiết lập `PAYMENT_BANK_BIN`, `PAYMENT_BANK_NAME`, `PAYMENT_ACCOUNT_NUMBER` và `PAYMENT_ACCOUNT_NAME` trong môi trường máy chủ. Kiểm tra thông tin ngân hàng trước khi nhận thanh toán thật. Mã QR được tạo qua dịch vụ VietQR và cần kết nối Internet.

Font Awesome và Swiper được tải từ CDN nên một số thành phần giao diện cần kết nối Internet.
