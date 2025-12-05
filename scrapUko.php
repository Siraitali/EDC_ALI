<?php
// =================== KONFIGURASI ===================
set_time_limit(0); // tanpa batas waktu eksekusi
include 'koneksi.php'; // koneksi database

// =================== HAPUS DATA LAMA ===================
$deleteSql = "TRUNCATE TABLE edc_merchant_raw"; // hapus semua isi tabel
if (!$conn->query($deleteSql)) {
    die("❌ Gagal menghapus data lama: " . $conn->error);
}

// =================== KOLOM TABEL ===================
$columns = [
    "tid",
    "mid",
    "nama_merchant",
    "peruntukkan",
    "jenis",
    "kanwil_no_rek",
    "uker_no_rek",
    "no_rek",
    "cur_balance",
    "kanwil_pemrakarsa",
    "kanwil_nama_pemrakarsa",
    "uker_pemrakarsa",
    "uker_nama_pemrakarsa",
    "user_pemrakarsa",
    "kanwil_implementor",
    "kanwil_nama_implementor",
    "uker_implementor",
    "uker_nama_implementor",
    "group_code",
    "group_name",
    "sub_group_code",
    "sub_group_name",
    "last_available",
    "status_available",
    "last_utility",
    "status_utility",
    "last_transactional",
    "status_transactional",
    "is_mms",
    "is_md_mms",
    "longitude",
    "latitude",
    "sn_edc",
    "merk_edc",
    "versi_app",
    "versi_app_mapped",
    "status_reliability_pemrakarsa",
    "status_reliability_implementor",
    "alamat_merchant",
    "kelurahan",
    "kecamatan",
    "kabupaten",
    "provinsi",
    "kodepos",
    "pic",
    "telp",
    "tgl_pasang",
    "last_pm_date",
    "amt",
    "date_input" // kolom tambahan tanggal scrap
];

// =================== URL SCRAP ===================
$url = "http://172.18.44.66/edcpro/index.php/detail/export?group_code=X&peruntukkan=UKER";

// =================== CEK WEBSITE ONLINE ===================
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$response = curl_exec($ch);
if (curl_errno($ch)) {
    die("cURL Error: " . curl_error($ch));
}
curl_close($ch);

if (empty($response)) {
    die("❌ Website target tidak merespon atau data kosong.");
}

// =================== PROSES CSV ===================
$rows = array_map('str_getcsv', explode("\n", $response));
$header = array_shift($rows); // buang header CSV
$valuesArr = [];
$dateInput = date('Y-m-d');

foreach ($rows as $row) {
    if (count($row) < 2) continue;

    $tid = isset($row[1]) ? $row[1] : '';
    $kanwilNama = isset($row[10]) ? $row[10] : '';

    // =================== FILTER KANWIL PEKANBARU ===================
    if (stripos($kanwilNama, "Pekanbaru") === false) continue;

    // =================== PREPARE DATA ===================
    $row[] = $dateInput; // tambah kolom date_input
    while (count($row) < count($columns)) $row[] = "";

    $escaped = array_map(function ($v) use ($conn) {
        return "'" . $conn->real_escape_string($v) . "'";
    }, $row);

    $valuesArr[] = "(" . implode(",", $escaped) . ")";
}

// =================== INSERT / UPDATE BATCH ===================
if (!empty($valuesArr)) {
    $batchSize = 100;
    $updateCols = [];
    foreach ($columns as $col) {
        if ($col != "tid" && $col != "date_input") $updateCols[] = "$col=VALUES($col)";
    }
    $updateStr = implode(",", $updateCols);

    for ($i = 0; $i < count($valuesArr); $i += $batchSize) {
        $batch = array_slice($valuesArr, $i, $batchSize);
        $sql = "INSERT INTO edc_merchant_raw (" . implode(",", $columns) . ")
                VALUES " . implode(",", $batch) . "
                ON DUPLICATE KEY UPDATE $updateStr";

        if (!$conn->query($sql)) {
            die("❌ Insert/Update batch gagal: " . $conn->error . "\nSQL: " . $sql);
        }
    }
    echo "✅ Scraping selesai! " . count($valuesArr) . " data tersimpan/diupdate ke database.";
} else {
    echo "⚠️ Tidak ada data baru yang masuk (mungkin semua sudah tersimpan).";
}
