<?php
session_start();
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $res = $conn->query("SELECT * FROM users WHERE username='$username'");
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['login'] = true;
            $_SESSION['username'] = $row['username'];
            $_SESSION['level'] = $row['level'];
            if ($row['level'] == 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        }
    }
    $_SESSION['error'] = "Username atau Password salah!";
    header("Location: login.php");
}
