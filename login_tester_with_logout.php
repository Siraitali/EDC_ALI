<?php
// login_tester_with_logout.php
$loginUrl = "https://wpe.bri.co.id:8080/api/guest/login";
$username = "90180919";
$password = "Sakamoto13!!@";
$cookieFileBase = __DIR__ . "/cookie_test"; // per-case akan pakai cookie_test_caseX.txt
$use_cookie = true; // set true kalau mau simpan cookie

$payloadVariants = [
    ['username' => $username, 'password' => $password],
    ['userId' => $username, 'password' => $password],
    ['user' => $username, 'password' => $password],
    ['email' => $username, 'password' => $password],
    ['username' => $username, 'password' => $password, 'rememberMe' => true],
    ['data' => ['username' => $username, 'password' => $password]],
];

$headerSets = [
    ['Content-Type: application/json', 'Accept: application/json'],
    ['Content-Type: application/json', 'Accept: application/json', 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)'],
    ['Content-Type: application/json', 'Accept: application/json', 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'Origin: https://wpe.bri.co.id', 'Referer: https://wpe.bri.co.id/'],
    ['Content-Type: application/json', 'Accept: application/json', 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'Origin: https://wpe.bri.co.id', 'Referer: https://wpe.bri.co.id/', 'X-Requested-With: XMLHttpRequest'],
];

function doRequestWithCookie($url, $jsonPayload, $headers, $cookieFile = null)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['http' => $http, 'err' => $err, 'resp' => $resp];
}

$case = 1;
foreach ($payloadVariants as $pv) {
    $json = json_encode($pv, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    foreach ($headerSets as $hs) {
        echo "==== CASE #{$case} ====\n";
        echo "Payload: $json\n";
        echo "Headers: " . implode(" | ", $hs) . "\n";
        $cookieFile = $use_cookie ? ($cookieFileBase . "_case{$case}.txt") : null;
        $result = doRequestWithCookie($loginUrl, $json, $hs, $cookieFile);
        echo "HTTP Code: " . $result['http'] . "\n";
        if ($result['err']) echo "cURL Error: " . $result['err'] . "\n";
        $body = $result['resp'] ?? '';
        $len = strlen($body);
        $preview = $len > 2000 ? substr($body, 0, 2000) . "...(truncated, length=$len)" : $body;
        echo "Response body (len=$len):\n" . $preview . "\n";
        if ($body && (stripos($body, '"token"') !== false || stripos($body, 'token') !== false)) {
            echo "[!] Token detected in response.\n";
            // optional: extract token and try authenticated API call here
        }
        echo "========================\n\n";
        $case++;
        usleep(200000);
        // cleanup cookie file per case (jika ingin auto-hapus setiap case uncomment baris di bawah)
        // if ($cookieFile && file_exists($cookieFile)) { unlink($cookieFile); echo "Cookie file {$cookieFile} dihapus (per-case).\n"; }
    }
}

// Jika mau hapus semua cookie yang dibuat di akhir:
for ($i = 1; $i < $case; $i++) {
    $f = $cookieFileBase . "_case{$i}.txt";
    if (file_exists($f)) {
        unlink($f);
        // echo "Hapus cookie: $f\n";
    }
}
echo "Selesai test. Semua file cookie sudah dihapus.\n";
