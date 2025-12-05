<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['level'] != 'admin') {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "edc_login_db2");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = $_GET['id'];
$conn->query("DELETE FROM edc_rusak WHERE id=$id");

header("Location: edc_rusak_admin.php");
exit;
