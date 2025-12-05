<?php
include 'koneksi.php';

// Ambil data notif user baru
$result = $conn->query("SELECT id, kc, nama_outlet, outlet_id, pengajuan FROM mpos WHERE notif_admin=1 ORDER BY id DESC");

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

$response = [
    'count' => count($data),
    'data' => $data
];

// Kirim JSON
header('Content-Type: application/json');
echo json_encode($response);
