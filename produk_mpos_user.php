<?php
session_start();

// ====================== SESSION PROTECTION ======================
if (!isset($_SESSION['login']) || $_SESSION['level'] != 'user') {
    header("Location: login.php");
    exit;
}

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
        .table-modern tbody tr td:first-child { border-radius:10px 0 0 10px; }
        .table-modern tbody tr td:last-child  { border-radius:0 10px 10px 0; }

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
            <?php while ($row = $data->fetch_assoc()) { ?>
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
        </tbody>
    </table>
</div>
</body>
</html>
