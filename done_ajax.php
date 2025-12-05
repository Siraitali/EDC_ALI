<?php
include 'koneksi.php';

if (isset($_POST['done_id'])) {
    $id = (int)$_POST['done_id'];

    $stmt = $conn->prepare("UPDATE mpos SET status_done=1, notif_admin=0 WHERE id=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
} else {
    echo "invalid";
}
