<?php
$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: 'usbw';
$database = getenv('DB_DATABASE') ?: 'th28.14';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$ketnoi = new mysqli($host, $username, $password, $database);
if ($ketnoi->connect_errno) {
    throw new RuntimeException('Unable to connect to the configured MySQL database.');
}

if (!$ketnoi->set_charset('utf8mb4')) {
    throw new RuntimeException('Unable to configure the MySQL connection charset.');
}

require_once dirname(__DIR__) . '/includes/helpers.php';