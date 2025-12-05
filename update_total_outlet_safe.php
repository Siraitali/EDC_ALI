<?php
// Koneksi database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "edc_login_db2";

$koneksi = new mysqli($host, $user, $pass, $db);
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

// Ambil jumlah outlet per kanca dari detailbri
$sql = "SELECT kanca_name, COUNT(*) AS total_outlet
        FROM detailbri
        GROUP BY kanca_name";

$result = $koneksi->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Escape string dan pakai COLLATE untuk keamanan collation
        $kanca = $koneksi->real_escape_string($row['kanca_name']);
        $total = (int)$row['total_outlet'];

        $update = "UPDATE bri_reli 
                   SET Total_Outlet = $total 
                   WHERE Branch_Name COLLATE utf8mb4_unicode_ci = '$kanca'";
        if ($koneksi->query($update) === FALSE) {
            echo "Error update kanca '$kanca': " . $koneksi->error . "<br>";
        }
    }
    echo "Update Total_Outlet selesai dan aman dari collation error!";
} else {
    echo "Tidak ada data di detailbri.";
}

$koneksi->close();
