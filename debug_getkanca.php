<?php
// URL utama (semua data Kanca)
$url = "https://brilinkbos.bri.co.id/brilink-bos/public/monitoring/getKanca?rcode=X&brand=semua";

// Ambil data JSON
$context = stream_context_create([
    "ssl" => ["verify_peer" => false, "verify_peer_name" => false]
]);
$json = file_get_contents($url, false, $context);

if ($json === false) {
    die("❌ Gagal mengambil data dari API.");
}

$data = json_decode($json, true);

if (empty($data['list_data'])) {
    die("❌ Format API tidak sesuai atau kosong.");
}

$rows = $data['list_data'];

// Ubah ini sesuai branch_code yang mau dicek
$targetBranch = '1079';

$found = null;
foreach ($rows as $r) {
    if (($r['branch_code'] ?? '') == $targetBranch) {
        $found = $r;
        break;
    }
}

if (!$found) {
    die("❌ Branch code {$targetBranch} tidak ditemukan di API.");
}

header('Content-Type: application/json; charset=utf-8');

// Tampilkan JSON mentah untuk branch_code itu
echo json_encode($found, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
