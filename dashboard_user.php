<?php
session_start();
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

$currentPage = basename($_SERVER['PHP_SELF']);

// ==========================
// Koneksi DB
// ==========================
$host = "localhost";
$user = "root";
$pass = "";
$db   = "edc_login_db2";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

// ==========================
// UKO Data
// ==========================
$sql = "
SELECT 
  SUM(CASE WHEN status_available = 1 THEN 1 ELSE 0 END) AS today,
  SUM(CASE WHEN status_available = 2 THEN 1 ELSE 0 END) AS d1_7,
  SUM(CASE WHEN status_available = 3 THEN 1 ELSE 0 END) AS d8_15,
  SUM(CASE WHEN status_available = 4 THEN 1 ELSE 0 END) AS d16_30,
  SUM(CASE WHEN status_available = 5 THEN 1 ELSE 0 END) AS gt30
FROM edc_merchant_raw
";
$result = $conn->query($sql);
$data = $result ? $result->fetch_assoc() : ['today' => 0, 'd1_7' => 0, 'd8_15' => 0, 'd16_30' => 0, 'gt30' => 0];

$totalJumlah = ($data['today'] + $data['d1_7'] + $data['d8_15'] + $data['d16_30'] + $data['gt30']);
$totalNOP    = ($data['d8_15'] + $data['d16_30'] + $data['gt30']);

// Relia UKO
$sql_relia = "
SELECT AVG(relia) AS avg_relia FROM (
    SELECT 
        TRIM(uker_nama_implementor) AS nama_kanca,
        CASE WHEN SUM(
            CASE WHEN status_available IN (1,2,3,4,5) THEN 1 ELSE 0 END
        ) > 0 
        THEN ROUND(((SUM(CASE WHEN status_available = 1 THEN 1 ELSE 0 END) + 
                     SUM(CASE WHEN status_available = 2 THEN 1 ELSE 0 END)) /
                     SUM(CASE WHEN status_available IN (1,2,3,4,5) THEN 1 ELSE 0 END)) * 100, 2)
        ELSE 0 END AS relia
    FROM edc_merchant_raw
    WHERE TRIM(uker_nama_implementor) <> ''
    GROUP BY TRIM(uker_nama_implementor)
) AS t
";
$res_relia = $conn->query($sql_relia);
$reliaUKO = 0;
if ($res_relia && $row_relia = $res_relia->fetch_assoc()) {
    $reliaUKO = round($row_relia['avg_relia'], 2);
}
$reliaUKOColor = ($reliaUKO >= 98) ? '#28a745' : (($reliaUKO >= 90) ? '#ffc107' : '#dc3545');
$reliaUKOStatus = ($reliaUKO >= 98) ? 'Good' : (($reliaUKO >= 90) ? 'Medium' : 'Low');

// Produktivitas UKO
$sql2 = "SELECT SUM(produktif) AS total_prod, SUM(grand_total) AS total_grand FROM produk_uko";
$res2 = $conn->query($sql2);
$row2 = $res2 ? $res2->fetch_assoc() : ['total_prod' => 0, 'total_grand' => 0];
$total_prod = floatval($row2['total_prod']);
$total_grand = floatval($row2['total_grand']);
$target = 95;
$persen_prod = ($total_grand > 0) ? ($total_prod / $total_grand * 100) : 0;
$produktivitasUKO = round(($target > 0) ? ($persen_prod / $target * 100) : 0, 2);
$prodUKOColor = ($produktivitasUKO >= 100) ? '#28a745' : (($produktivitasUKO >= 80) ? '#ffc107' : '#dc3545');
$prodUKOStatus = ($produktivitasUKO >= 100) ? 'Good' : (($produktivitasUKO >= 80) ? 'Medium' : 'Low');



// WPE Merchant Data (sesuai wpe_user.php)
// ==========================
$sql_wpe = "
SELECT 
    TRIM(Mainbranch_Nama_Pemrakarsa) AS BranchOffice,
    COUNT(*) AS Total,
    SUM(CASE WHEN Last_Availability IS NOT NULL AND DATE(Last_Availability) < DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS MoreThan7Days
FROM wpe
WHERE Mainbranch_Nama_Pemrakarsa IS NOT NULL AND TRIM(Mainbranch_Nama_Pemrakarsa) <> ''
GROUP BY BranchOffice
";
$res_wpe = $conn->query($sql_wpe);

$totalJumlahWPE = 0;
$totalNOPWPE = 0;
$reliaSum = 0;
$branchCount = 0;

if ($res_wpe && $res_wpe->num_rows > 0) {
    while ($row = $res_wpe->fetch_assoc()) {
        $totalJumlahWPE += $row['Total'];
        $totalNOPWPE    += $row['MoreThan7Days'];

        $relia = $row['Total'] > 0 ? (($row['Total'] - $row['MoreThan7Days']) / $row['Total']) * 100 : 0;
        $reliaSum += $relia;
        $branchCount++;
    }
}

$reliaWPE = $totalJumlahWPE > 0 ? round((($totalJumlahWPE - $totalNOPWPE) / $totalJumlahWPE) * 100, 2) : 0;
$reliaWPEColor = ($reliaWPE >= 98) ? '#28a745' : (($reliaWPE >= 90) ? '#ffc107' : '#dc3545');
$reliaWPEStatus = ($reliaWPE >= 98) ? 'Good' : (($reliaWPE >= 90) ? 'Medium' : 'Low');





// ==========================
// BRILink Data
// ==========================
$sql_bri = "
SELECT 
    COUNT(*) AS total_outlet,
    SUM(CASE WHEN last_login = '0000-00-00 00:00:00' OR last_login = '-' THEN 1 ELSE 0 END) AS belum_aktivasi,
    SUM(CASE WHEN last_login != '0000-00-00 00:00:00' AND last_login != '-' THEN 1 ELSE 0 END) AS telah_aktivasi,
    SUM(CASE WHEN last_login != '0000-00-00 00:00:00' AND last_login != '-' AND DATEDIFF(NOW(), last_login) > 30 THEN 1 ELSE 0 END) AS day_more_30
FROM detailbri
";
$res_bri = $conn->query($sql_bri);
$bri = $res_bri ? $res_bri->fetch_assoc() : ['total_outlet' => 0, 'belum_aktivasi' => 0, 'telah_aktivasi' => 0, 'day_more_30' => 0];

$totalOutlet = (int)$bri['total_outlet'];
$belumAktivasi = (int)$bri['belum_aktivasi'];
$telahAktivasi = (int)$bri['telah_aktivasi'];
$dayMore30 = (int)$bri['day_more_30'];
$reliaBRI = ($totalOutlet > 0) ? round((($telahAktivasi - $dayMore30) / $totalOutlet) * 100, 2) : 0;
$reliaBRIColor = ($reliaBRI >= 98) ? '#28a745' : (($reliaBRI >= 90) ? '#ffc107' : '#dc3545');
$reliaBRIStatus = ($reliaBRI >= 98) ? 'Good' : (($reliaBRI >= 90) ? 'Medium' : 'Low');

$sql_prod = "SELECT live_produktif_percent FROM productivity WHERE nama_kanwil='Region 2 / Pekanbaru'";
$res_prod = $conn->query($sql_prod);
$totalProdPercent = 0;
$countProd = 0;
while ($row = $res_prod->fetch_assoc()) {
    $totalProdPercent += $row['live_produktif_percent'];
    $countProd++;
}
$rataProduktivitas = $countProd ? round($totalProdPercent / $countProd, 2) : 0;
// Tentukan warna dan status Produktivitas MPOS
$prodBRIColor = ($rataProduktivitas >= 100) ? '#28a745' : (($rataProduktivitas >= 80) ? '#ffc107' : '#dc3545');
$prodBRIStatus = ($rataProduktivitas >= 100) ? 'Good' : (($rataProduktivitas >= 80) ? 'Medium' : 'Low');


// ==========================
// Stok Mesin
// ==========================
$sql_stok = "
SELECT 
    type_mesin,
    COUNT(*) AS total,
    SUM(CASE WHEN status='Terpakai' THEN 1 ELSE 0 END) AS terpakai,
    SUM(CASE WHEN status='Tersedia' THEN 1 ELSE 0 END) AS tersedia
FROM edc_stok
WHERE type_mesin IS NOT NULL AND type_mesin<>''
GROUP BY type_mesin
";
$res_stok = $conn->query($sql_stok);

$sql_total_stok = "
SELECT 
    COUNT(*) AS total_all,
    SUM(CASE WHEN status='Terpakai' THEN 1 ELSE 0 END) AS terpakai_all,
    SUM(CASE WHEN status='Tersedia' THEN 1 ELSE 0 END) AS tersedia_all
FROM edc_stok
WHERE type_mesin IS NOT NULL AND type_mesin<>''
";
$res_total_stok = $conn->query($sql_total_stok);
$total_stok = $res_total_stok ? $res_total_stok->fetch_assoc() : ['total_all' => 0, 'terpakai_all' => 0, 'tersedia_all' => 0];

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Gabungan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: #212529;
            --sidebar-hover: #495057;
            --topbar-bg: #007bff;
            --text-color: white;
            --body-bg: #f4f6f9;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--body-bg);
            margin: 0;
            color: #333;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: var(--sidebar-bg);
            overflow-y: auto;
            padding-top: 10px;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.2);
            transition: width 0.4s ease, left 0.3s ease;
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar .nav-link {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            color: var(--text-color);
            text-decoration: none;
            font-size: 14px;
            position: relative;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--sidebar-hover);
        }

        .sidebar .nav-link i {
            width: 25px;
            text-align: center;
            margin-right: 10px;
            transition: transform 0.3s;
        }

        .sidebar.collapsed .nav-link span {
            display: none;
        }

        .sidebar.collapsed .nav-link i {
            margin-right: 0;
        }

        .sidebar.collapsed .nav-link[title]:hover::after {
            content: attr(title);
            position: absolute;
            left: 70px;
            background: rgba(0, 0, 0, 0.85);
            color: #fff;
            padding: 5px 10px;
            border-radius: 4px;
            white-space: nowrap;
            font-size: 13px;
            z-index: 999;
        }

        .submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease;
            padding-left: 30px;
        }

        .submenu.show {
            max-height: 500px;
        }

        .topbar {
            background: var(--topbar-bg);
            color: white;
            height: 60px;
            position: fixed;
            top: 0;
            left: 250px;
            right: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            transition: left 0.4s ease;
        }

        .topbar a {
            color: white;
            text-decoration: none;
        }

        .content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.4s ease;
            margin-top: 60px;
        }

        @media(max-width:768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .content {
                margin-left: 0;
                padding: 10px;
                margin-top: 60px;
            }

            .topbar {
                left: 0;
            }
        }

        /* Card grid */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .card-stat {
            background: #fff;
            border-radius: 14px;
            padding: 18px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
            position: relative;
            text-align: center;
            transition: all .25s ease;
        }

        .card-stat:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12);
        }

        .card-icon {
            font-size: 2.4rem;
            color: #007bff;
            margin-bottom: 8px;
            animation: floatIcon 3s ease-in-out infinite;
        }

        @keyframes floatIcon {

            0%,
            100% {
                transform: translateY(0)
            }

            50% {
                transform: translateY(-4px)
            }
        }

        .card-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 6px;
        }

        .card-value {
            font-size: 1.9rem;
            font-weight: 700;
            color: #111;
        }

        .badge-status {
            position: absolute;
            top: 10px;
            right: 12px;
            padding: 6px 9px;
            border-radius: 10px;
            color: #fff;
            font-size: .78rem;
        }

        /* Table Stok */
        table {
            width: 80%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
            margin: auto;
            margin-bottom: 50px;
        }

        th,
        td {
            padding: 8px 10px;
            text-align: center;
            font-size: 13px;
        }

        th {
            background-color: #007BFF;
            color: white;
            font-size: 14px;
        }

        tr:nth-child(even) {
            background: #f0f0f0;
        }

        tr.total-row {
            font-weight: bold;
            background: #28a745;
            color: white;
        }

        .toggle-icon {
            cursor: pointer;
            font-size: 20px;
            vertical-align: middle;
            color: #007BFF;
            transition: transform 0.3s;
        }

        .toggle-icon:hover {
            color: #0056b3;
        }

        .hidden {
            display: none;
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="d-flex justify-content-between align-items-center px-2 mb-2">
            <i class="fas fa-cogs"></i>
            <button id="sidebarToggle" class="btn btn-sm btn-outline-light"><i class="fas fa-bars"></i></button>
        </div>

        <ul class="nav flex-column">
            <!-- Dashboard -->
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?= $currentPage == 'dashboard.php' ? 'active' : ''; ?>" title="Dashboard">
                    <i class="fas fa-home"></i><span>Dashboard</span>
                </a>
            </li>

            <!-- UKO -->
            <li class="nav-item">
                <a href="#" class="nav-link" data-toggle="submenu" data-target="ukoMenu" data-arrow="ukoArrow" title="UKO">
                    <i class="fas fa-user-tie"></i><span>UKO</span><i class="fas fa-angle-left right" id="ukoArrow"></i>
                </a>
                <ul class="submenu" id="ukoMenu">
                    <li><a href="relia_uko.php" class="nav-link <?= $currentPage == 'relia_uko.php' ? 'active' : ''; ?>">Reliability</a></li>
                    <li><a href="produk_uko.php" class="nav-link <?= $currentPage == 'produk_uko.php' ? 'active' : ''; ?>">Produktifitas</a></li>
                    <li><a href="nop_uko.php" class="nav-link">NOP Berulang</a></li>
                </ul>
            </li>

            <!-- Merchant -->
            <li class="nav-item">
                <a href="#" class="nav-link" data-toggle="submenu" data-target="merchantMenu" data-arrow="merchantArrow" title="Merchant">
                    <i class="fas fa-store"></i><span>Merchant</span><i class="fas fa-angle-left right" id="merchantArrow"></i>
                </a>
                <ul class="submenu" id="merchantMenu">
                    <li><a href="wpe_user.php" class="nav-link">Group Uker</a></li>
                    <li><a href="produk_mpos_user.php" class="nav-link">Produktifitas</a></li>
                    <li><a href="notfound.php" class="nav-link">Reliability FMS</a></li>
                    <li><a href="notfound.php" class="nav-link">Time Series</a></li>
                    <li><a href="vendor.php" class="nav-link">Group Vendor</a></li>
                    <li><a href="nop_merchant.php" class="nav-link">NOP Berulang</a></li>
                    <a href="map_mesin.php" class="nav-link" title="Tracking EDC"><i class="fas fa-search"></i><span>Peta Mesin</span></a>
                </ul>
            </li>

            <!-- BRILink -->
            <li class="nav-item">
                <a href="#" class="nav-link" data-toggle="submenu" data-target="brilinkMenu" data-arrow="brilinkArrow" title="BRILink">
                    <i class="fas fa-link"></i><span>BRILink</span><i class="fas fa-angle-left right" id="brilinkArrow"></i>
                </a>
                <ul class="submenu" id="brilinkMenu">
                    <li><a href="relia_bri.php" class="nav-link <?= $currentPage == 'relia_bri.php' ? 'active' : ''; ?>">Reliability</a></li>
                    <li><a href="produk_bri_user.php" class="nav-link <?= $currentPage == 'produk_bri.php' ? 'active' : ''; ?>">Produktifitas</a></li>
                    <li><a href="mpos_user.php" class="nav-link <?= $currentPage == 'mpos_user.php' ? 'active' : ''; ?>">Implementasi MPOS</a></li>
                    <li><a href="notfound.php" class="nav-link">NOP Berulang</a></li>
                </ul>
            </li>

            <!-- Static Menu -->
            <li class="nav-item mt-3">
                <a href="notfound.php" class="nav-link" title="Lapor Pemasangan MPOS"><i class="fas fa-file-alt"></i><span>Lapor Pemasangan MPOS</span></a>
                <a href="edc_rusak_user.php" class="nav-link <?= $currentPage == 'edc_rusak_user.php' ? 'active' : ''; ?>" title="EDC Rusak"><i class="fas fa-exclamation-circle"></i><span>EDC RUSAK</span></a>
                <a href="cek_error.php" class="nav-link" title="Cek Kode Error"><i class="fas fa-bug"></i><span>Cek Kode Error</span></a>
                <a href="panduan_konfigurasi_edc.php" class="nav-link <?= $currentPage == 'panduan_konfigurasi_edc.php' ? 'active' : ''; ?>" title="Panduan Konfigurasi EDC"><i class="fas fa-book"></i><span>Panduan Konfigurasi EDC</span></a>
            </li>

            <!-- Stok -->
            <li class="nav-item stok mt-3">
                <a href="#" class="nav-link" data-toggle="submenu" data-target="stokMenu" data-arrow="stokArrow" title="Stok">
                    <i class="fas fa-box"></i><span>Stok</span><i class="fas fa-angle-left right" id="stokArrow"></i>
                </a>
                <ul class="submenu show" id="stokMenu">
                    <li><a href="edc_stok_user.php" class="nav-link <?= $currentPage == 'edc_stok.php' ? 'active' : ''; ?>">EDC MPOS</a></li>
                    <li><a href="notfound.php" class="nav-link">Termal</a></li>
                </ul>
            </li>
        </ul>
    </div>

    <!-- Topbar -->
    <div class="topbar">
        <div></div>
        <div>
            <span>👤 <?= $_SESSION['username']; ?></span>
            <a href="logout.php" title="Logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Konten Dashboard -->
    <div class="content">
        <div class="container">
            <h4 class="text-center mb-4 fw-bold text-primary">Monitoring EDC UKO</h4>
            <div class="stats">
                <div class="card-stat">
                    <i class="fa-solid fa-layer-group card-icon"></i>
                    <div class="card-title">Jumlah</div>
                    <div class="card-value" id="ukoJumlah">0</div>
                </div>
                <div class="card-stat">
                    <i class="fa-solid fa-users card-icon"></i>
                    <div class="card-title">Jumlah NOP</div>
                    <div class="card-value" id="ukoNOP">0</div>
                </div>
                <div class="card-stat">
                    <i class="fa-solid fa-gauge-high card-icon"></i>
                    <div class="card-title">Relia (%)</div>
                    <span class="badge-status" style="background:<?= $reliaUKOColor ?>;"><?= $reliaUKOStatus ?></span>
                    <div class="card-value" id="ukoRelia">0</div>
                </div>
                <div class="card-stat">
                    <i class="fa-solid fa-chart-line card-icon"></i>
                    <div class="card-title">Produktifitas (%)</div>
                    <span class="badge-status" style="background:<?= $prodUKOColor ?>;"><?= $prodUKOStatus ?></span>
                    <div class="card-value" id="ukoProd">0</div>
                </div>
            </div>
        </div>


        <div class="container">
            <h4 class="text-center mb-4 fw-bold text-primary">Monitoring EDC Merchant</h4>
            <div class="stats">
                <div class="card-stat">
                    <i class="fa-solid fa-layer-group card-icon"></i>
                    <div class="card-title">Jumlah Semua EDC</div>
                    <div class="card-value" id="wpeJumlah">0</div>
                </div>
                <div class="card-stat">
                    <i class="fa-solid fa-triangle-exclamation card-icon"></i>
                    <div class="card-title">NOP (&gt;7 Hari)</div>
                    <div class="card-value" id="wpeNOP">0</div>
                </div>
                <div class="card-stat">
                    <i class="fa-solid fa-gauge-high card-icon"></i>
                    <div class="card-title">Reliability (%)</div>
                    <span class="badge-status" style="background:<?= $reliaWPEColor ?>;"><?= $reliaWPEStatus ?></span>
                    <div class="card-value" id="wpeRelia">0</div>
                </div>
            </div>
        </div>



        <div class="container">
            <h4 class="text-center mb-4 fw-bold text-info">Monitoring MPOS</h4>
            <div class="stats">
                <div class="card-stat">
                    <i class="fa-solid fa-layer-group card-icon"></i>
                    <div class="card-title">Total Outlet</div>
                    <div class="card-value" id="briTotalOutlet">0</div>
                </div>
                <div class="card-stat">
                    <i class="fa-solid fa-users card-icon"></i>
                    <div class="card-title">Belum / Telah Aktivasi</div>
                    <div class="card-value">
                        <span id="briBelum">0</span> / <span id="briTelah">0</span>
                    </div>
                </div>
                <div class="card-stat">
                    <i class="fa-solid fa-gauge-high card-icon"></i>
                    <div class="card-title">Relia (%)</div>
                    <span class="badge-status" style="background:<?= $reliaBRIColor ?>;"><?= $reliaBRIStatus ?></span>
                    <div class="card-value" id="briRelia">0</div>
                </div>
                <div class="card-stat">
                    <i class="fa-solid fa-chart-line card-icon"></i>
                    <div class="card-title">Produktivitas (%)</div>
                    <span class="badge-status" style="background:<?= $prodBRIColor ?>;"><?= $prodBRIStatus ?></span>
                    <div class="card-value" id="briProd">0</div>
                </div>

            </div>
        </div>

        <div class="container">
            <h4 class="text-center mb-4 fw-bold text-success">Stok Mesin</h4>
            <span class="toggle-icon" id="toggleIcon" onclick="toggleTable()">👁️</span>
            <div id="dashboardTable">
                <table>
                    <tr>
                        <th>Type Mesin</th>
                        <th>Jumlah</th>
                        <th>Terpakai</th>
                        <th>Tersedia</th>
                    </tr>
                    <?php
                    if ($res_stok->num_rows > 0) {
                        while ($row = $res_stok->fetch_assoc()) {
                            echo "<tr>
                <td>{$row['type_mesin']}</td>
                <td>{$row['total']}</td>
                <td>{$row['terpakai']}</td>
                <td>{$row['tersedia']}</td>
              </tr>";
                        }
                        echo "<tr class='total-row'>
            <td>Total</td>
            <td>{$total_stok['total_all']}</td>
            <td>{$total_stok['terpakai_all']}</td>
            <td>{$total_stok['tersedia_all']}</td>
          </tr>";
                    } else {
                        echo "<tr><td colspan='4'>Data kosong</td></tr>";
                    }
                    ?>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-toggle="submenu"]').forEach(el => {
            el.addEventListener('click', function(e) {
                e.preventDefault();
                let target = document.getElementById(this.dataset.target);
                target.classList.toggle('show');
                let arrow = document.getElementById(this.dataset.arrow);
                arrow.classList.toggle('fa-rotate-90');
            });
        });
        document.getElementById('sidebarToggle').addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.topbar').style.left = document.querySelector('.sidebar').classList.contains('collapsed') ? '70px' : '250px';
            document.querySelector('.content').style.marginLeft = document.querySelector('.sidebar').classList.contains('collapsed') ? '70px' : '250px';
        });

        function animateValue(id, start, end, duration, decimals = 0) {
            let range = end - start;
            let current = start;
            let increment = range / (duration / 20);
            const obj = document.getElementById(id);
            let timer = setInterval(function() {
                current += increment;
                if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                    current = end;
                    clearInterval(timer);
                }
                obj.textContent = decimals > 0 ? current.toFixed(decimals) : Math.round(current);
            }, 20);
        }

        animateValue('ukoJumlah', 0, <?= $totalJumlah ?>, 1000, 0);
        animateValue('ukoNOP', 0, <?= $totalNOP ?>, 1000, 0);
        animateValue('ukoRelia', 0, <?= $reliaUKO ?>, 1500, 2);
        animateValue('ukoProd', 0, <?= $produktivitasUKO ?>, 1500, 2);

        animateValue('wpeJumlah', 0, <?= $totalJumlahWPE ?>, 1000, 0);
        animateValue('wpeNOP', 0, <?= $totalNOPWPE ?>, 1000, 0);
        animateValue('wpeRelia', 0, <?= $reliaWPE ?>, 1500, 2);


        animateValue('briTotalOutlet', 0, <?= $totalOutlet ?>, 1000, 0);
        animateValue('briBelum', 0, <?= $belumAktivasi ?>, 1000, 0);
        animateValue('briTelah', 0, <?= $telahAktivasi ?>, 1000, 0);
        animateValue('briRelia', 0, <?= $reliaBRI ?>, 1500, 2);
        animateValue('briProd', 0, <?= $rataProduktivitas ?>, 1500, 2);

        function toggleTable() {
            var tableDiv = document.getElementById('dashboardTable');
            var icon = document.getElementById('toggleIcon');
            if (tableDiv.classList.contains('hidden')) {
                tableDiv.classList.remove('hidden');
                icon.textContent = '👁️';
            } else {
                tableDiv.classList.add('hidden');
                icon.textContent = '🙈';
            }
        }
    </script>

</body>

</html>