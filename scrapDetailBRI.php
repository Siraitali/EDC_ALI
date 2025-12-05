<?php
set_time_limit(0); // supaya tidak timeout

// Koneksi database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "edc_login_db2";

$koneksi = new mysqli($host, $user, $pass, $db);
if ($koneksi->connect_error) die("Koneksi gagal: " . $koneksi->connect_error);

// =================== HAPUS DATA LAMA ===================
$deleteSql = "TRUNCATE TABLE detailbri"; // hapus semua isi tabel
if (!$koneksi->query($deleteSql)) {
    die("❌ Gagal menghapus data lama: " . $koneksi->error);
}

// File log error
$logFile = __DIR__ . "/scrap_errors.log";
file_put_contents($logFile, "=== START SCRAP " . date("Y-m-d H:i:s") . " ===\n", FILE_APPEND);

// URL base
$base_url = "https://brilinkbos.bri.co.id/brilink-bos/public/monitoring/getTotalOutlet?bcode=0&ucode=0&rcode=X&page=%3Fpage%3D";

// Ambil total halaman
$data = file_get_contents($base_url . "1&order=%26order%3D&brand=semua");
$json = json_decode($data, true);
$total_page = $json['pagination']['total_page'];

echo "Total halaman: $total_page\n";

// Loop semua halaman
for ($page = 1; $page <= $total_page; $page++) {
    echo "Processing page $page\n";
    $data = file_get_contents($base_url . $page . "&order=%26order%3D&brand=semua");
    $json = json_decode($data, true);

    if (!isset($json['list_data'])) {
        file_put_contents($logFile, "Page $page tidak ada list_data\n", FILE_APPEND);
        continue;
    }

    foreach ($json['list_data'] as $item) {
        // Ambil semua kolom
        $cols = [
            'brilink_web_code',
            'outlet_code',
            'jenis',
            'tid',
            'branch_code',
            'main_branch',
            'region_code',
            'outlet_name',
            'kanca_name',
            'branch_name',
            'region_name',
            'last_login',
            'f1',
            'sn',
            'sn_simcard',
            'merek'
        ];

        $values = [];
        foreach ($cols as $col) {
            $values[] = "'" . $koneksi->real_escape_string($item[$col] ?? '') . "'";
        }

        // INSERT IGNORE agar tetap masuk walau duplikat
        $sql = "INSERT IGNORE INTO detailbri (" . implode(',', $cols) . ") VALUES (" . implode(',', $values) . ")";
        if (!$koneksi->query($sql)) {
            $errorMsg = "Error insert (TID: " . ($item['tid'] ?? '') . ", Outlet: " . ($item['outlet_name'] ?? '') . "): " . $koneksi->error . "\n";
            echo $errorMsg;
            file_put_contents($logFile, $errorMsg, FILE_APPEND);
        }
    }
}

echo "Selesai scraping semua halaman!\n";
file_put_contents($logFile, "=== END SCRAP " . date("Y-m-d H:i:s") . " ===\n", FILE_APPEND);
$koneksi->close();
