<?php
session_start();
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // cek apakah username sudah ada
    $check = $conn->query("SELECT * FROM users WHERE username='$username'");
    if ($check->num_rows > 0) {
        $_SESSION['error'] = "Username sudah digunakan!";
        header("Location: register.php");
        exit;
    }

    // insert user baru dengan level 'user'
    $conn->query("INSERT INTO users (username,password,level) VALUES ('$username','$password','user')");
    $_SESSION['success'] = "Registrasi berhasil! Silahkan login.";
    header("Location: login.php");
    exit;
}
