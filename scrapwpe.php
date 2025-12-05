<?php

/**
 * scrap_wpe_safe.php (versi truncate)
 * Scrap + hapus data lama + logout otomatis + hapus cookie
 */

set_time_limit(0);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ---------- CONFIG ----------
$username = "90180919";
$password = "Sakamoto13!!@";

$dbHost = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "edc_login_db2";

$loginUrl = "https://wpe.bri.co.id:8080/api/guest/login";
$date = date('Y-m-d');
$mainBranchesUrl = "https://wpe.bri.co.id:8080/api/auth/edcpro/X/mainBranches?date={$date}";
$logoutUrl = "https://wpe.bri.co.id:8080/api/auth/logout";

$defaultHeaders = [
    "Content-Type: application/json",
    "Accept: application/json, text/plain, */*",
    "Origin: https://wpe.bri.co.id",
    "Referer: https://wpe.bri.co.id/",
    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)"
];

// ---------- COOKIE FILE ----------
$cookieJar = __DIR__ . '/wpe_cookies.txt';
if (file_exists($cookieJar)) unlink($cookieJar);

// ---------- HELPER ----------
function do_logout_and_clear_cookie($logoutUrl, $token, $headers, $cookieJar)
{
    $ch = curl_init($logoutUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($headers, [
        "Authorization: Bearer $token"
    ]));
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    curl_exec($ch);
    curl_close($ch);

    // hapus cookie setelah logout
    if (file_exists($cookieJar)) unlink($cookieJar);
    echo "Logout selesai + cookie dihapus. Akun siap dipakai lagi.\n";
}

// ---------- LOGIN ----------
$payload = json_encode([
    "username" => $username,
    "password" => $password
]);

$ch = curl_init($loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, $defaultHeaders);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
$loginResponse = curl_exec($ch);
curl_close($ch);

$loginData = json_decode($loginResponse, true);
$token = $loginData['token']
    ?? $loginData['data']['token']
    ?? $loginData['data']['access_token']
    ?? $loginData['access_token']
    ?? $loginData['data']['meta']['token']
    ?? null;

if (!$token) die("Login gagal. Response: $loginResponse\n");
echo "Login berhasil, token didapat.\n";

// ---------- FETCH mainBranches ----------
$ch = curl_init($mainBranchesUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($defaultHeaders, [
    "Authorization: Bearer $token"
]));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
$branchesResponse = curl_exec($ch);
curl_close($ch);

$branchesData = json_decode($branchesResponse, true);
$branches = $branchesData['data']['data'] ?? $branchesData['data'] ?? [];

echo "Jumlah branch diterima: " . count($branches) . "\n";

// ---------- DB ----------
$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_errno) die("MySQL connect error: " . $mysqli->connect_error);

// **TRUNCATE table supaya data lama hilang**
$mysqli->query("TRUNCATE TABLE wpe");
echo "Data lama dihapus, siap insert data baru.\n";

// Prepare statement untuk insert
$stmt = $mysqli->prepare("
INSERT INTO wpe (code, name, total, lte1h_ritel, gte1h_ritel, today_ritel, last_7d_ritel, gte7d_ritel)
VALUES (?, ?, ?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE
    name=VALUES(name),
    total=VALUES(total),
    lte1h_ritel=VALUES(lte1h_ritel),
    gte1h_ritel=VALUES(gte1h_ritel),
    today_ritel=VALUES(today_ritel),
    last_7d_ritel=VALUES(last_7d_ritel),
    gte7d_ritel=VALUES(gte7d_ritel)
");

$rowsInserted = 0;
foreach ($branches as $row) {
    $code = $row['code'] ?? null;
    $name = $row['name'] ?? null;
    $total = (int)($row['total'] ?? 0);
    $lte1h_ritel = (int)($row['lte1h_ritel'] ?? 0);
    $gte1h_ritel = (int)($row['gte1h_ritel'] ?? 0);
    $today_ritel = (int)($row['today_ritel'] ?? 0);
    $last_7d_ritel = (int)($row['last_7d_ritel'] ?? 0);
    $gte7d_ritel = (int)($row['gte7d_ritel'] ?? 0);

    $stmt->bind_param("ssiiiiii", $code, $name, $total, $lte1h_ritel, $gte1h_ritel, $today_ritel, $last_7d_ritel, $gte7d_ritel);
    $stmt->execute();
    $rowsInserted++;
}
$stmt->close();
$mysqli->close();

echo "Rows inserted/updated: $rowsInserted\n";

// ---------- LOGOUT + CLEAR COOKIE ----------
do_logout_and_clear_cookie($logoutUrl, $token, $defaultHeaders, $cookieJar);

echo "Script selesai bro, akun langsung bersih dan siap dipakai lagi.\n";
