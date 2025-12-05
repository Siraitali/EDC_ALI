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

if (isset($_POST['submit'])) {
    $SN = $conn->real_escape_string($_POST['SN']);
    $MEREK = $conn->real_escape_string($_POST['MEREK']);
    $SPK = $conn->real_escape_string($_POST['SPK']);
    $RO = $conn->real_escape_string($_POST['RO']);
    $KELENGKAPAN = $conn->real_escape_string($_POST['KELENGKAPAN']);
    $KETERANGAN = $conn->real_escape_string($_POST['KETERANGAN']);

    $sql = "INSERT INTO edc_rusak (SN,MEREK,SPK,RO,KELENGKAPAN,KETERANGAN) 
            VALUES ('$SN','$MEREK','$SPK','$RO','$KELENGKAPAN','$KETERANGAN')";
    if ($conn->query($sql)) {
        header("Location: edc_rusak_admin.php");
        exit;
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add EDC Rusak</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h2>Add EDC Rusak</h2>
        <form method="post">
            <div class="mb-3">
                <label>SN</label>
                <input type="text" name="SN" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>MEREK</label>
                <input type="text" name="MEREK" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>SPK</label>
                <input type="text" name="SPK" class="form-control">
            </div>
            <div class="mb-3">
                <label>RO</label>
                <input type="text" name="RO" class="form-control">
            </div>
            <div class="mb-3">
                <label>KELENGKAPAN</label>
                <input type="text" name="KELENGKAPAN" class="form-control">
            </div>
            <div class="mb-3">
                <label>KETERANGAN</label>
                <textarea name="KETERANGAN" class="form-control"></textarea>
            </div>
            <button type="submit" name="submit" class="btn btn-primary">Add</button>
            <a href="edc_rusak_admin.php" class="btn btn-secondary">Back</a>
        </form>
    </div>
</body>

</html>