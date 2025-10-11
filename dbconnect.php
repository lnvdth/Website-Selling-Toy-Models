<?php
$ketnoi = mysqli_connect("localhost", "root", "usbw", "th28.14");

if (!$ketnoi) {
    die('Could not connect: ' . mysqli_connect_error());
}

mysqli_set_charset($ketnoi, "utf8");
// return $ketnoi;
?>