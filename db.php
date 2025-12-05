<?php
$host = 'localhost';
$user = 'root';      // sesuaikan username MySQL kamu
$pass = '';          // sesuaikan password MySQL kamu
$db   = 'edc_login_db2';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
