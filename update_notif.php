<?php
include 'koneksi.php';

if (isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("UPDATE mpos SET notif_admin=0 WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}
