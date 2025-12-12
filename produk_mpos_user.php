<?php
session_start();

// ====================== SESSION PROTECTION ======================
if (!isset($_SESSION['login']) || $_SESSION['level'] != 'user') {
    header("Location: login.php");
    exit;
}
// ===== AUTO LOGOUT TIDAK ADA AKTIVITAS =====
$timeout = 1800; // 30 menit

if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > $timeout) {
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=1");
        exit;
    }
}
$_SESSION['last_activity'] = time();
// ====================== CONFIG DATABASE ======================
$host = "localhost";
$user = "root";
$pass = "";
$db   = "edc_login_db2";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Koneksi gagal: " . $conn->connect_error); }

// ====================== FUNGSI TANGGAL INDONESIA ======================
function indoDate($date) {
    if ($date == null || $date == "" || $date == "0000-00-00 00:00:00") return "-";

    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
             'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    $tgl = date('j', strtotime($date));
    $bln = $bulan[(int)date('m', strtotime($date))];
    $thn = date('Y', strtotime($date));

    return "$tgl $bln $thn";
}

// ====================== GET DATA ===============================
$data = $conn->query("SELECT * FROM produk_merchant ORDER BY id ASC");
$rows = [];

while ($r = $data->fetch_assoc()) {
    $rows[] = $r;
}

$lastIndex = count($rows) - 1; // index data terakhir
?>
<!DOCTYPE html>
<html>
<head>
    <title>Data Produk Merchant</title>
    <style>
        body { background: #eef2f7; font-family: 'Segoe UI', sans-serif; margin:0; padding:20px; }

        .card {
            border-radius: 15px;
            background:white;
            padding:20px;
            box-shadow:0 6px 18px rgba(0,0,0,0.08);
            margin:auto;
            width:95%;
        }

        .table-modern {
            border-collapse: separate !important;
            border-spacing: 0 8px !important;
            width:100%;
        }
        .table-modern thead th {
            background:#0d6efd;
            color:white;
            padding:12px;
            text-align:center;
            font-size:14px;
        }
        .table-modern tbody tr {
            background:white;
            transition:.25s;
        }
        .table-modern tbody tr:hover {
            transform:scale(1.01);
            background:#f4f8ff !important;
        }
        .table-modern tbody td {
            padding:12px;
            text-align:center;
            border-top:1px solid #e5e7eb;
            font-size:14px;
        }
        .table-modern tbody tr:last-child {
            background:#eaf0ff !important;
            font-weight:bold;
        }

        h3 { color:#084298; font-weight:700; }
    </style>
</head>

<body>
<div class="card">
    <h3>Data Produk Merchant</h3>
    <table class="table-modern">
        <thead>
            <tr>
                <th>ID</th>
                <th>KODE KANCA</th>
                <th>NAMA KANCA</th>
                <th>Produktif</th>
                <th>Non-Produktif</th>
                <th>Total</th>
                <th>% Produktivitas</th>
                <th>Waktu Input</th>
            </tr>
        </thead>
        <tbody>

            <?php foreach ($rows as $i => $row) { ?>

                <?php if ($i == $lastIndex) { ?>
                    <!-- ========== BARIS GRAND TOTAL (MODIFIKASI DATA TERAKHIR) ========== -->
                    <tr style="background:#eaf0ff; font-weight:bold;">
                        <td></td>
                        <td colspan="2" style="text-align:center;">Grand Total</td>
                        <td><?= $row['produktif']; ?></td>
                        <td><?= $row['non_produktif']; ?></td>
                        <td><?= $row['total']; ?></td>
                        <td><?= $row['produktivitas_pct']; ?>%</td>
                        <td></td>
                    </tr>
                <?php } else { ?>
                    <!-- DATA BIASA -->
                    <tr>
                        <td><?= $row['id']; ?></td>
                        <td><?= $row['kode_kanca']; ?></td>
                        <td><?= $row['nama_kanca']; ?></td>
                        <td><?= $row['produktif']; ?></td>
                        <td><?= $row['non_produktif']; ?></td>
                        <td><?= $row['total']; ?></td>
                        <td><?= $row['produktivitas_pct']; ?>%</td>
                        <td><?= indoDate($row['created_at']); ?></td>
                    </tr>
                <?php } ?>

            <?php } ?>

        </tbody>
    </table>
</div>
</body>
</html>
