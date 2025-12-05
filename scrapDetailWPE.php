<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);

// ==========================
// KONFIGURASI
// ==========================
$baseUrl = "https://wpe.bri.co.id:8080";
$loginUrl = "$baseUrl/api/guest/login";
$dataUrlTemplate = "$baseUrl/gateway/apiHeartbeatEDC/1.0/tid?period=all&list=list_region&merchant_type=ALL&code=region_code&value=X&page=%d&limit=1000&offset_trx_time=%s&offset_id=%d&cursor_page=1&cache_key=black&count_last_record=%d&date=%s&is_domicile=false&is_new_merchant=false";
$logoutUrl = "$baseUrl/api/auth/logout";

$username = "90180919";
$password = "Sakamoto13!!@";

$dbHost = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "edc_login_db2";
$table = "coba";

$logFile = __DIR__ . "/scrap_log.txt";
$tokenFile = __DIR__ . "/token.txt"; // token disimpan sementara

$log = [];
$log[] = "=== SCRAP WPE START: " . date("Y-m-d H:i:s") . " ===";

// ==========================
// KONEKSI DATABASE
// ==========================
$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    $log[] = "Koneksi DB gagal: " . $conn->connect_error;
    file_put_contents($logFile, implode("\n", $log) . "\n", FILE_APPEND);
    die("Koneksi DB gagal.\n");
}

// ==========================
// AUTO LOGOUT JIKA MASIH ADA TOKEN LAMA
// ==========================
if (file_exists($tokenFile)) {
    $oldToken = trim(file_get_contents($tokenFile));
    if (!empty($oldToken)) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $logoutUrl,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer $oldToken"],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $logoutResponse = curl_exec($ch);
        curl_close($ch);
        unlink($tokenFile);
        $log[] = "Auto logout token lama berhasil dijalankan.";
    }
}

// ==========================
// LOGIN BARU
// ==========================
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $loginUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        "username" => $username,
        "password" => $password
    ]),
    CURLOPT_HTTPHEADER => [
        "Accept: application/json, text/plain, */*",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)",
        "Origin: https://wpe.bri.co.id:8080",
        "Referer: https://wpe.bri.co.id:8080/",
        "Content-Type: application/x-www-form-urlencoded"
    ],
    CURLOPT_SSL_VERIFYPEER => false,
]);

$loginResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($httpCode != 200) {
    $log[] = "Login gagal. HTTP Code: $httpCode | Response: $loginResponse";
    file_put_contents($logFile, implode("\n", $log) . "\n", FILE_APPEND);
    die("Login gagal. Cek log.\n");
}

$loginData = json_decode($loginResponse, true);
if (empty($loginData['data']['token'])) {
    $log[] = "Gagal ambil token login. Response: $loginResponse";
    file_put_contents($logFile, implode("\n", $log) . "\n", FILE_APPEND);
    die("Gagal ambil token login. Cek log.\n");
}

$token = $loginData['data']['token'];
file_put_contents($tokenFile, $token); // simpan token
$log[] = "Login sukses. Token baru diterima dan disimpan.";

// ==========================
// SCRAP DATA
// ==========================
$page = 1;
$offset_id = 0;
$count_last_record = 0;
$date = date("Y-m-d");
$offset_trx_time = urlencode(date("Y-m-d H:i:s"));
$totalInserted = 0;
$totalPages = 0;

while (true) {
    $url = sprintf($dataUrlTemplate, $page, $offset_trx_time, $offset_id, $count_last_record, $date);
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $token",
            "Accept: application/json",
            "User-Agent: Mozilla/5.0"
        ],
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($ch);
    if (!$response) {
        $log[] = "Gagal ambil page $page: " . curl_error($ch);
        break;
    }

    $data = json_decode($response, true);
    if (!isset($data['data']) || empty($data['data'])) {
        $log[] = "Tidak ada data lagi di page $page.";
        break;
    }

    foreach ($data['data'] as $row) {
        $stmt = $conn->prepare("INSERT INTO $table (
            mid, tid, merchant_name, allocate, merchant_type, region_account_num,
            main_branch_account_num, branch_account_num, account_num, ratas_saldo,
            region_code_init, region_name_init, main_branch_name_init, branch_name_init,
            region_name_domicile, main_branch_name_domicile, branch_name_domicile,
            rm_init, vendor_name, last_availability, last_utility, last_transactional,
            longitude, latitude, address, village, sub_district, city, province,
            postal_code, pic, telp, install_date
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

        $stmt->bind_param(
            "ssssssssissssssssssssddssssssssss",
            $row['mid'], $row['tid'], $row['merchant_name'], $row['allocate'], $row['merchant_type'],
            $row['region_account_num'], $row['main_branch_account_num'], $row['branch_account_num'],
            $row['account_num'], $row['ratas_saldo'], $row['region_code_init'], $row['region_name_init'],
            $row['main_branch_name_init'], $row['branch_name_init'], $row['region_name_domicile'],
            $row['main_branch_name_domicile'], $row['branch_name_domicile'], $row['rm_init'],
            $row['vendor_name'], $row['last_availability'], $row['last_utility'], $row['last_transactional'],
            $row['longitude'], $row['latitude'], $row['address'], $row['village'],
            $row['sub_district'], $row['city'], $row['province'], $row['postal_code'],
            $row['pic'], $row['telp'], $row['install_date']
        );
        $stmt->execute();
        $stmt->close();
        $totalInserted++;
    }

    $log[] = "Page $page berhasil: " . count($data['data']) . " data disimpan";
    $page++;
    $totalPages++;
    $offset_id += count($data['data']);
    $count_last_record += count($data['data']);
    sleep(1);
}

$log[] = "Total page: $totalPages | Total data tersimpan: $totalInserted";

// ==========================
// LOGOUT OTOMATIS (AMAN)
// ==========================
curl_setopt_array($ch, [
    CURLOPT_URL => $logoutUrl,
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $token"
    ],
]);
$logoutResponse = curl_exec($ch);
curl_close($ch);
unlink($tokenFile); // hapus token setelah logout

$log[] = "Logout berhasil. Token lama dihapus.";

// ==========================
// SIMPAN LOG KE FILE
// ==========================
file_put_contents($logFile, implode("\n", $log) . "\n\n", FILE_APPEND);
echo "Scraping selesai. Total data: $totalInserted | Cek log: scrap_log.txt\n";
$conn->close();
?>
