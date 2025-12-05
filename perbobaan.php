yang bisa berhasil scrap pada reli uko
<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['level'] != 'user') {
    header("Location: login.php");
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// =================== PERPANJANG WAKTU EKSEKUSI ===================
set_time_limit(600); // 10 menit

// =================== KONEKSI DATABASE ===================
$host = "localhost";
$user = "root";
$pass = "";
$db   = "edc_login_db2";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

// =================== KOLOM TABEL ===================
$columns = [
    "edc_mtr_kc_id",
    "edc_mtr_kc_date_input",
    "edc_mtr_kc_time_input",
    "edc_mtr_kc_name",
    "edc_mntr_kc_kode_kc",
    "edc_mtr_kc_merchant_staging",
    "edc_mtr_kc_merchant_today",
    "edc_mtr_kc_merchant_1_7",
    "edc_mtr_kc_merchant_4_7",
    "edc_mtr_kc_merchant_8_15",
    "edc_mtr_kc_merchant_16_30",
    "edc_mtr_kc_merchant_30",
    "merchant_8",
    "edc_mtr_kc_merchant_tot",
    "edc_mtr_kc_merchant_avail",
    "edc_mtr_kc_merchant_reli",
    "edc_mtr_kc_merchant_16_30_trx",
    "edc_mtr_kc_merchant_30_trx",
    "edc_mtr_kc_merchant_tot_trx",
    "edc_mtr_kc_merchant_avail_trx",
    "edc_mtr_kc_brilink_staging",
    "edc_mtr_kc_brilink_today",
    "edc_mtr_kc_brilink_1_7",
    "edc_mtr_kc_brilink_4_7",
    "edc_mtr_kc_brilink_8_15",
    "edc_mtr_kc_brilink_16_30",
    "edc_mtr_kc_brilink_30",
    "brilink_8",
    "edc_mtr_kc_brilink_tot",
    "edc_mtr_kc_brilink_avail",
    "edc_mtr_kc_brilink_reli",
    "brilink_trx_lebih_8",
    "brilink_reli_trx",
    "edc_mtr_kc_uko_staging",
    "edc_mtr_kc_uko_today",
    "edc_mtr_kc_uko_1_7",
    "edc_mtr_kc_uko_4_7",
    "edc_mtr_kc_uko_8_15",
    "edc_mtr_kc_uko_16_30",
    "edc_mtr_kc_uko_30",
    "uko_8",
    "edc_mtr_kc_uko_tot",
    "edc_mtr_kc_uko_avail",
    "edc_mtr_kc_uko_reli",
    "uko_trx_lebih_8",
    "uko_reli_trx",
    "edc_mntr_kc_rata_rata_avail",
    "edc_mntr_kc_rata_rata_reli",
    "edc_mntr_kc_rata_rata_avail_trx",
    "reli_kc_order"
];

// =================== SCRAPING CSV ===================
$url = "http://172.18.44.66/edcpro/index.php/detail/export?group_code=X";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 300);
$data = curl_exec($ch);
if (curl_errno($ch)) die("Error cURL: " . curl_error($ch));
curl_close($ch);

$rows = array_map('str_getcsv', explode("\n", $data));
$header = array_shift($rows); // buang header CSV

// =================== PERSIAPAN INSERT BATCH ===================
$valuesArr = [];
foreach ($rows as $row) {
    if (count($row) < 2) continue; // skip baris kosong
    while (count($row) < count($columns)) $row[] = ""; // tambahkan dummy jika kurang
    $escaped = array_map(function ($v) use ($conn) {
        return "'" . $conn->real_escape_string($v) . "'";
    }, $row);
    $valuesArr[] = "(" . implode(",", $escaped) . ")";
}

// =================== INSERT BATCH PER 100 BARIS ===================
$batchSize = 100;

// Buat string ON DUPLICATE KEY UPDATE
$updateCols = [];
foreach ($columns as $col) {
    if ($col != "edc_mtr_kc_id") { // primary key tidak diupdate
        $updateCols[] = "$col=VALUES($col)";
    }
}
$updateStr = implode(",", $updateCols);

for ($i = 0; $i < count($valuesArr); $i += $batchSize) {
    $batch = array_slice($valuesArr, $i, $batchSize);
    $sql = "INSERT INTO erm_reli_kc (" . implode(",", $columns) . ") VALUES " . implode(",", $batch) . " 
            ON DUPLICATE KEY UPDATE $updateStr";
    if (!$conn->query($sql)) {
        die("Insert/Update batch gagal: " . $conn->error);
    }
}

// =================== TAMPILKAN DATA ===================
$result = $conn->query("SELECT edc_mtr_kc_name, edc_mtr_kc_uko_staging, edc_mtr_kc_uko_today, edc_mtr_kc_uko_tot FROM erm_reli_kc");

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Reliability EDC UKO</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <h2>Data Reliability EDC UKO</h2>
    <table>
        <tr>
            <th>Nama KC</th>
            <th>UKO Staging</th>
            <th>UKO Today</th>
            <th>UKO Total</th>
        </tr>
        <?php
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
        <td>{$row['edc_mtr_kc_name']}</td>
        <td>{$row['edc_mtr_kc_uko_staging']}</td>
        <td>{$row['edc_mtr_kc_uko_today']}</td>
        <td>{$row['edc_mtr_kc_uko_tot']}</td>
    </tr>";
        }
        ?>
    </table>
</body>

</html>