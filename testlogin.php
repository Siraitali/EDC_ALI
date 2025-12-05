<?php
// ============================================
// TEST LOGIN SCRIPT WPE BRI
// ============================================

date_default_timezone_set('Asia/Jakarta');

// Konfigurasi akun login
$username = "90180919";
$password = "Sakamoto13!!@";

// URL login & cookie file
$loginUrl = "https://wpe.bri.co.id:8080/api/guest/login";
$cookieFile = __DIR__ . "/wpedetail_cookie.txt";

// Payload JSON
$payload = json_encode([
    "username" => $username,
    "password" => $password
]);

// Header untuk request
$headers = [
    'Accept: application/json, text/plain, */*',
    'Content-Type: application/json;charset=UTF-8',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Origin: https://wpe.bri.co.id:8080',
    'Referer: https://wpe.bri.co.id:8080/',
    'Connection: keep-alive'
];

// Inisialisasi CURL
$ch = curl_init($loginUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_COOKIEJAR => $cookieFile,
    CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT => 30,
]);

echo "[" . date('Y-m-d H:i:s') . "] Sending login request..." . PHP_EOL;

// Eksekusi CURL
$response = curl_exec($ch);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Tampilkan hasil
echo "HTTP Status: $httpCode" . PHP_EOL;
if ($error) {
    echo "CURL Error: $error" . PHP_EOL;
}
echo "Response Body:" . PHP_EOL;
echo $response . PHP_EOL;

// Coba parse JSON untuk lihat pesan sukses/gagal
$json = json_decode($response, true);
if (isset($json['message'])) {
    echo "Server message: " . $json['message'] . PHP_EOL;
}

if (isset($json['code']) && $json['code'] == 200) {
    echo "✅ Login sukses!" . PHP_EOL;
} else {
    echo "❌ Login gagal." . PHP_EOL;
}

// Bersihkan cookie file jika login gagal
if (!isset($json['code']) || $json['code'] != 200) {
    if (file_exists($cookieFile)) {
        @unlink($cookieFile);
        echo "Cookie file removed due to failed login." . PHP_EOL;
    }
}
