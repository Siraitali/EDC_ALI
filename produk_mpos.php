<?php
session_start();

// ====================== SESSION PROTECTION ======================
if (!isset($_SESSION['login']) || $_SESSION['level'] != 'admin') {
    header("Location: login.php");
    exit;
}

// ====================== CONFIG DATABASE ======================
$host = "localhost";
$user = "root";
$pass = "";
$db   = "edc_login_db2";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// ====================== FUNGSI TANGGAL INDONESIA ======================
function indoDate($date)
{
    if ($date == null || $date == "" || $date == "0000-00-00 00:00:00") return "-";

    $bulan = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];

    $tgl = date('j', strtotime($date));
    $bln = $bulan[(int)date('m', strtotime($date))];
    $thn = date('Y', strtotime($date));

    return "$tgl $bln $thn";
}

// ====================== IMPORT EXCEL ============================
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_POST['import'])) {
    if (isset($_FILES['file_excel']['name'])) {
        $file = $_FILES['file_excel']['tmp_name'];
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        // RESET TABEL
        $conn->query("TRUNCATE TABLE produk_merchant");
        $conn->query("ALTER TABLE produk_merchant AUTO_INCREMENT = 1");

        for ($i = 2; $i <= count($rows); $i++) {
            if (empty(array_filter($rows[$i]))) continue;

            $kode_kanca  = trim($rows[$i]['B']);
            $nama_kanca  = trim($rows[$i]['C']);
            $produktif   = (int)$rows[$i]['D'];
            $non_prod    = (int)$rows[$i]['E'];
            $total       = (int)$rows[$i]['F'];

            $pct_raw = trim($rows[$i]['G']);
            if (strpos($pct_raw, '%') !== false) {
                $pct = floatval(str_replace('%', '', $pct_raw));
            } elseif (is_numeric($pct_raw)) {
                $pct = ($pct_raw <= 1) ? $pct_raw * 100 : $pct_raw;
            } else {
                $pct = 0;
            }

            $conn->query("
                INSERT INTO produk_merchant
                (kode_kanca, nama_kanca, produktif, non_produktif, total, produktivitas_pct, created_at)
                VALUES
                ('$kode_kanca', '$nama_kanca', '$produktif', '$non_prod', '$total', '$pct', NOW())
            ");
        }

        echo "<script>alert('Import berhasil!');window.location='produk_mpos.php';</script>";
        exit;
    }
}

// ====================== GET DATA ===============================
$data = $conn->query("SELECT * FROM produk_merchant ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>EDC Admin Dashboard - Produk Merchant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: #f7f9fa;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: #2c3e50;
            color: #fff;
            overflow-y: auto;
            padding-top: 20px;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.15);
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 22px;
            font-weight: 600;
        }

        .sidebar a {
            display: block;
            padding: 12px 20px;
            color: #ecf0f1;
            text-decoration: none;
            font-size: 15px;
            margin: 2px 0;
            border-radius: 5px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #1abc9c;
            color: #fff;
        }

        .sidebar a i {
            width: 25px;
        }

        .submenu {
            display: none;
            padding-left: 15px;
        }

        .submenu a {
            padding-left: 40px;
            font-size: 14px;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: .3s;
        }

        .card {
            border-radius: 15px;
            background: white;
            padding: 20px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
            width: 97%;
            margin: auto;
        }

        .table-modern {
            border-collapse: separate !important;
            border-spacing: 0 8px !important;
            width: 100%;
        }

        .table-modern thead th {
            background: #0d6efd;
            color: white;
            padding: 12px;
            text-align: center;
        }

        .table-modern tbody tr {
            background: white;
            transition: .25s;
        }

        .table-modern tbody tr:hover {
            transform: scale(1.01);
            background: #f4f8ff !important;
        }

        .table-modern tbody td {
            padding: 12px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }

        .table-modern tbody tr td:first-child {
            border-radius: 10px 0 0 10px;
        }

        .table-modern tbody tr td:last-child {
            border-radius: 0 10px 10px 0;
        }

        h3 {
            color: #084298;
            font-weight: 700;
        }

        .logout-btn {
            background: #e74c3c;
            color: #fff;
            padding: 10px 15px;
            border-radius: 6px;
            text-align: center;
            display: block;
            margin: 15px;
        }
    </style>
</head>

<body>

    <!-- ===== SIDEBAR START ===== -->
    <div class="sidebar">
        <h2>Admin</h2>

        <a href="dashboard_admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>

        <a class="dropdown-btn"><i class="fas fa-box-open"></i> Stok <i class="fas fa-angle-down" style="float:right;"></i></a>
        <div class="submenu">
            <a href="edc_stok.php">EDC</a>
            <a href="termal.php">TERMAL</a>
            <a href="upload_mesin.php">Peta EDC</a>
        </div>

        <a class="dropdown-btn"><i class="fas fa-building"></i> UKO <i class="fas fa-angle-down" style="float:right;"></i></a>
        <div class="submenu">
            <a href="produk_uko_admin.php">Produktifitas</a>
            <a href="detaul_uko_admin.php">Input Detail UKO</a>
            <a href="#">NOP Berulang</a>
        </div>

        <a class="dropdown-btn"><i class="fas fa-store"></i> Merchant <i class="fas fa-angle-down" style="float:right;"></i></a>
        <div class="submenu">
            <a href="wpe_admin.php">Reliability</a>
            <a href="produk_mpos.php" class="active">Produktifitas</a>
            <a href="#">NOP Berulang</a>
        </div>

        <a class="dropdown-btn"><i class="fas fa-link"></i> BRILink <i class="fas fa-angle-down" style="float:right;"></i></a>
        <div class="submenu">
            <a href="produk_bri_admin.php">Produktifitas</a>
            <a href="mpos_admin.php">Pengajuan MPOS</a>
            <a href="#">NOP Berulang</a>
        </div>

        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
    <!-- ===== SIDEBAR END ===== -->

    <!-- ===== MAIN CONTENT ===== -->
    <div class="main-content">

        <div class="card">
            <h3>Data Produk Merchant</h3>

            <form action="" method="POST" enctype="multipart/form-data">
                <input type="file" name="file_excel" required>
                <button type="submit" name="import" class="btn btn-primary btn-sm">Import Excel</button>
            </form>

            <br>

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

    </div>

    <script>
        // Sidebar Dropdown
        let dropdown = document.getElementsByClassName("dropdown-btn");
        for (let i = 0; i < dropdown.length; i++) {
            dropdown[i].onclick = function() {
                let submenu = this.nextElementSibling;
                submenu.style.display = submenu.style.display === "block" ? "none" : "block";
            };
        }
    </script>

</body>

</html>