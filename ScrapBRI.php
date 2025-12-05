<?php
$host = "localhost";
$user = "root";   // ganti sesuai database kamu
$pass = "";       // ganti sesuai database kamu
$db   = "edc_login_db2";

// koneksi database
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// URL scrap
$url = "https://brilinkbos.bri.co.id/brilink-bos/public/monitoring/getKanca?rcode=X&brand=semua";

// ambil data JSON
$response = file_get_contents($url);
if ($response === FALSE) {
    die("Gagal ambil data dari URL");
}

$data = json_decode($response, true);

// cek response
if ($data['response_code'] != '00') {
    die("Response gagal: " . $data['response_desc']);
}

// loop list_data
foreach ($data['list_data'] as $row) {
    $branch_name = $conn->real_escape_string($row['branch_name']);
    $branch_code = (int)$row['branch_code'];
    $total_outlet = (int)$row['total_outlet'];
    $total_mposs = (int)$row['total_mposs'];
    $activation_perfect = (int)$row['activation_perfect'];
    $activation_not_perfect = (int)$row['activation_not_perfect'];
    $perfect = (int)$row['perfect'];
    $not_perfect = (int)$row['not_perfect'];
    $belum_login = (int)$row['belum_login'];
    $today = (int)$row['today'];
    $d_01_07 = (int)$row['01-07'];
    $d_08_15 = (int)$row['08-15'];
    $d_16_30 = (int)$row['16-30'];
    $last30 = (int)$row['last30'];

    // Insert atau update
    $sql = "INSERT INTO bri_reli 
        (branch_name, branch_code, total_outlet, total_mposs, activation_perfect, activation_not_perfect, perfect, not_perfect, belum_login, today, `01_07`, `08_15`, `16_30`, last30)
        VALUES
        ('$branch_name', $branch_code, $total_outlet, $total_mposs, $activation_perfect, $activation_not_perfect, $perfect, $not_perfect, $belum_login, $today, $d_01_07, $d_08_15, $d_16_30, $last30)
        ON DUPLICATE KEY UPDATE
        branch_name='$branch_name',
        total_outlet=$total_outlet,
        total_mposs=$total_mposs,
        activation_perfect=$activation_perfect,
        activation_not_perfect=$activation_not_perfect,
        perfect=$perfect,
        not_perfect=$not_perfect,
        belum_login=$belum_login,
        today=$today,
        `01_07`=$d_01_07,
        `08_15`=$d_08_15,
        `16_30`=$d_16_30,
        last30=$last30";

    if (!$conn->query($sql)) {
        echo "Error: " . $conn->error . "\n";
    }
}

echo "Data berhasil diupdate/insert!";

$conn->close();
