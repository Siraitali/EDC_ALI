<?php
// scrapDetailWPE_wepdetail_auto_log.php
$dbHost = '127.0.0.1';
$dbName = 'edc_login_db2';
$dbUser = 'root';
$dbPass = '';

$apiBase = 'https://wpe.bri.co.id:8080';
$loginUrl = $apiBase . '/api/guest/login';
$logoutUrl = $apiBase . '/api/auth/logout';

$apiUsername = '90180919';
$apiPassword = 'Sakamoto13!!@';
$cookieFile = __DIR__ . '/wpedetail_cookie.txt';
$logFile = __DIR__ . '/scrape_log.txt';
$curlTimeout = 60;
$retryLimit = 3;

// ---------------- LOG -----------------
function log_msg($msg)
{
    global $logFile;
    $t = "[" . date('Y-m-d H:i:s') . "] $msg";
    echo "$t\n";
    file_put_contents($logFile, $t . PHP_EOL, FILE_APPEND);
}

// ---------------- CURL -----------------
function curl_post($url, $postFields = null, $headers = array(), $cookieFile = null, $timeout = 60)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    if ($postFields !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    }
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    if (!empty($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return array('body' => $resp, 'error' => $err, 'http' => $httpcode);
}
function curl_get($url, $headers = array(), $cookieFile = null, $timeout = 60)
{
    return curl_post($url, null, $headers, $cookieFile, $timeout);
}

// ---------------- LOGIN -----------------
log_msg("Starting login...");
$loginPayload = json_encode(array('username' => $apiUsername, 'password' => $apiPassword));
$headers = array('Content-Type: application/json', 'Accept: application/json', 'User-Agent: Mozilla/5.0');

$loginResp = curl_post($loginUrl, $loginPayload, $headers, $cookieFile, $curlTimeout);
if ($loginResp['error']) {
    log_msg("cURL error login: " . $loginResp['error']);
    exit;
}
if ($loginResp['http'] != 200) {
    log_msg("Login HTTP " . $loginResp['http']);
    exit;
}
$j = json_decode($loginResp['body'], true);
if ($j['code'] != 200) {
    log_msg("Login gagal: " . $j['message']);
    exit;
}
log_msg("Login sukses! Cookie tersimpan di $cookieFile");

// ---------------- DATABASE -----------------
try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
} catch (PDOException $e) {
    log_msg("DB error: " . $e->getMessage());
    exit;
}

$columns = array(
    'mid',
    'tid',
    'merchant_name',
    'allocate',
    'merchant_type',
    'region_account_num',
    'main_branch_account_num',
    'branch_account_num',
    'account_num',
    'ratas_saldo',
    'region_code_init',
    'region_name_init',
    'main_branch_name_init',
    'branch_name_init',
    'region_name_domicile',
    'main_branch_name_domicile',
    'branch_name_domicile',
    'rm_init',
    'vendor_name',
    'last_availability',
    'last_utility',
    'last_transactional',
    'longitude',
    'latitude',
    'address',
    'village',
    'sub_district',
    'city',
    'province',
    'postal_code',
    'pic',
    'install_date'
);

$colList = implode(',', $columns);
$placeholders = implode(',', array_map(function ($c) {
    return ":$c";
}, $columns));
$updatePlaceholders = implode(',', array_map(function ($c) {
    return "$c = :u_$c";
}, $columns));
$insertSql = "INSERT INTO wepdetail ($colList) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updatePlaceholders";
$stmt = $pdo->prepare($insertSql);

// ---------------- AMBIL DATA TERAKHIR -----------------
$lastTrxTime = $pdo->query("SELECT MAX(last_transactional) AS last_time FROM wepdetail")->fetch(PDO::FETCH_ASSOC)['last_time'];
$offsetTrxTime = $lastTrxTime ? urlencode($lastTrxTime) : urlencode(date('Y-m-d H:i:s', strtotime('-7 days')));
log_msg("Offset trx time: $offsetTrxTime");

// ---------------- SCRAPE -----------------
$totalInserted = 0;
$totalUpdated = 0;
$page = 1;

do {
    $cursorPage = $page;
    $url = $apiBase . "/gateway/apiHeartbeatEDC/1.0/tid?period=gte7d&list=list_region&merchant_type=RITEL&code=region_code&value=X&page=$page&limit=1000&offset_trx_time=$offsetTrxTime&offset_id=0&cursor_page=$cursorPage&cache_key=black&count_last_record=0&date=" . date('Y-m-d') . "&is_domicile=false&is_new_merchant=false";

    log_msg("Scrape page $page...");
    $retry = 0;
    do {
        $resp = curl_get($url, array('Accept: application/json'), $cookieFile, $curlTimeout);
        $retry++;
        if ($resp['error'] || $resp['http'] < 200 || $resp['http'] >= 300) {
            log_msg("HTTP/cURL error, retry $retry/$retryLimit");
            sleep(1);
        } else break;
    } while ($retry < $retryLimit);

    if ($resp['error'] || $resp['http'] < 200 || $resp['http'] >= 300) {
        log_msg("Gagal scrape page $page setelah $retryLimit retry.");
        break;
    }

    $j = json_decode($resp['body'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        log_msg("JSON decode error");
        break;
    }

    $items = $j['data']['data'] ?? array();
    $lastPage = isset($j['data']['last_page']) ? intval($j['data']['last_page']) : $page;
    log_msg("Ditemukan " . count($items) . " item. Last page: $lastPage");

    if (count($items) > 0) {
        $pdo->beginTransaction();
        try {
            foreach ($items as $row) {
                $params = array();
                foreach ($columns as $col) {
                    $val = isset($row[$col]) ? $row[$col] : null;
                    if ($val === '') $val = null;
                    if ($col === 'ratas_saldo') $val = is_numeric($val) ? floatval($val) : null;
                    if (in_array($col, array('longitude', 'latitude'))) $val = $val !== null ? floatval($val) : null;
                    if (in_array($col, array('last_availability', 'last_utility', 'last_transactional', 'install_date'))) {
                        if (empty($val) || strpos($val, '0001-01-01') !== false) $val = null;
                        else {
                            $dt = strtotime($val);
                            $val = $dt === false ? $val : date('Y-m-d H:i:s', $dt);
                        }
                    }
                    if (is_string($val)) $val = trim($val);
                    $params[$col] = $val;
                }
                foreach ($columns as $c) {
                    $stmt->bindValue(":$c", $params[$c]);
                    $stmt->bindValue(":u_$c", $params[$c]);
                }
                $stmt->execute();
                $rc = $stmt->rowCount();
                if ($rc === 1) $totalInserted++;
                else $totalUpdated++;
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            log_msg("DB error: " . $e->getMessage());
            break;
        }
    }

    $page++;
} while ($page <= $lastPage);

log_msg("Scrape selesai. Inserted: $totalInserted, Updated: $totalUpdated");

// ---------------- LOGOUT & HAPUS COOKIE -----------------
log_msg("Logout...");
$logoutResp = curl_post($logoutUrl, null, array('Accept: application/json'), $cookieFile, $curlTimeout);
if ($logoutResp['error']) log_msg("Logout error: " . $logoutResp['error']);
else log_msg("Logout HTTP " . $logoutResp['http']);

if (file_exists($cookieFile)) {
    @unlink($cookieFile);
    log_msg("Cookie file dihapus.");
}

log_msg("Selesai. Summary -> Inserted: $totalInserted, Updated: $totalUpdated");
