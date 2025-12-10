<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['level'] != 'user') {
    header("Location: login.php");
    exit;
}

$currentPage = basename($_SERVER['PHP_SELF']);

// Koneksi database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "edc_login_db2";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil tanggal terbaru
$tanggalData = '';
$tanggalQuery = "SELECT MAX(Tanggal_Input) AS LatestDate FROM wpe";
$resTanggal = $conn->query($tanggalQuery);
if ($resTanggal && $resTanggal->num_rows > 0) {
    $rowTgl = $resTanggal->fetch_assoc();
    if (!empty($rowTgl['LatestDate'])) {
        $tanggalData = date('d-m-Y H:i:s', strtotime($rowTgl['LatestDate']));
    }
}

/* ========== logic utama pada script ========== */
$sql = "
SELECT 
    TRIM(Mainbranch_Nama_Pemrakarsa) AS BranchOffice,
    COUNT(*) AS Total,
    
    SUM(CASE 
        WHEN Last_Availability IS NOT NULL 
             AND TIMESTAMPDIFF(MINUTE, Last_Availability, NOW()) < 60 
        THEN 1 ELSE 0 END) AS Less1Hour,
    
    SUM(CASE 
        WHEN Last_Availability IS NOT NULL 
             AND TIMESTAMPDIFF(HOUR, Last_Availability, NOW()) BETWEEN 1 AND 24 
        THEN 1 ELSE 0 END) AS Hour1to24,
    
    SUM(CASE 
        WHEN Last_Availability IS NOT NULL 
             AND DATE(Last_Availability) = CURDATE() 
        THEN 1 ELSE 0 END) AS TodayCount,
    
    SUM(CASE 
        WHEN Last_Availability IS NOT NULL 
             AND DATE(Last_Availability) BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND DATE_SUB(CURDATE(), INTERVAL 1 DAY)
        THEN 1 ELSE 0 END) AS Days1to7,
    
    SUM(CASE 
        WHEN Last_Availability IS NOT NULL 
             AND DATE(Last_Availability) < DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        THEN 1 ELSE 0 END) AS MoreThan7Days

FROM wpe
WHERE Mainbranch_Nama_Pemrakarsa IS NOT NULL
GROUP BY BranchOffice
ORDER BY BranchOffice
";

$result = $conn->query($sql);
$data = [];
$totals = ['Total' => 0, '<1Jam' => 0, '1_24Jam' => 0, 'Today' => 0, '1_7Hari' => 0, '>7Hari' => 0];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reliability = $row['Total'] > 0 ? (($row['Total'] - $row['MoreThan7Days']) / $row['Total']) * 100 : 0;
        $data[] = [
            'BranchOffice' => $row['BranchOffice'],
            'Total' => $row['Total'],
            '<1Jam' => $row['Less1Hour'],
            '1_24Jam' => $row['Hour1to24'],
            'Today' => $row['TodayCount'],
            '1_7Hari' => $row['Days1to7'],
            '>7Hari' => $row['MoreThan7Days'],
            'Reliability' => round($reliability, 2)
        ];
        $totals['Total'] += $row['Total'];
        $totals['<1Jam'] += $row['Less1Hour'];
        $totals['1_24Jam'] += $row['Hour1to24'];
        $totals['Today'] += $row['TodayCount'];
        $totals['1_7Hari'] += $row['Days1to7'];
        $totals['>7Hari'] += $row['MoreThan7Days'];
    }
}

// Hitung total reliability semua branch
$totalRel = $totals['Total'] > 0 ? (($totals['Total'] - $totals['>7Hari']) / $totals['Total']) * 100 : 0;


// ============================
// SIMPAN TOTAL RELIABILITY
// ============================

// Tanggal 1–31
$hari = date("j");

// Map nama bulan (Inggris → Indonesia)
$mapBulan = [
    "January" => "Januari",
    "February" => "Februari",
    "March" => "Maret",
    "April" => "April",
    "May" => "Mei",
    "June" => "Juni",
    "July" => "Juli",
    "August" => "Agustus",
    "September" => "September",
    "October" => "Oktober",
    "November" => "November",
    "December" => "Desember"
];
$bulanNama = $mapBulan[date("F")];

// Simpan ke kolom dayX hanya jika kosong
$sql = "
UPDATE uker_rata_rata
SET day$hari = ?
WHERE bulan = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ds", $totalRel, $bulanNama);
$stmt->execute();

// Update rata-rata bulan
$conn->query("
    UPDATE uker_rata_rata
    SET rata_rata = (
        (
         COALESCE(day1,0)+COALESCE(day2,0)+COALESCE(day3,0)+COALESCE(day4,0)+
         COALESCE(day5,0)+COALESCE(day6,0)+COALESCE(day7,0)+COALESCE(day8,0)+
         COALESCE(day9,0)+COALESCE(day10,0)+COALESCE(day11,0)+COALESCE(day12,0)+
         COALESCE(day13,0)+COALESCE(day14,0)+COALESCE(day15,0)+COALESCE(day16,0)+
         COALESCE(day17,0)+COALESCE(day18,0)+COALESCE(day19,0)+COALESCE(day20,0)+
         COALESCE(day21,0)+COALESCE(day22,0)+COALESCE(day23,0)+COALESCE(day24,0)+
         COALESCE(day25,0)+COALESCE(day26,0)+COALESCE(day27,0)+COALESCE(day28,0)+
         COALESCE(day29,0)+COALESCE(day30,0)+COALESCE(day31,0)
        )
        /
        NULLIF(
         (day1 IS NOT NULL)+(day2 IS NOT NULL)+(day3 IS NOT NULL)+(day4 IS NOT NULL)+
         (day5 IS NOT NULL)+(day6 IS NOT NULL)+(day7 IS NOT NULL)+(day8 IS NOT NULL)+
         (day9 IS NOT NULL)+(day10 IS NOT NULL)+(day11 IS NOT NULL)+(day12 IS NOT NULL)+
         (day13 IS NOT NULL)+(day14 IS NOT NULL)+(day15 IS NOT NULL)+(day16 IS NOT NULL)+
         (day17 IS NOT NULL)+(day18 IS NOT NULL)+(day19 IS NOT NULL)+(day20 IS NOT NULL)+
         (day21 IS NOT NULL)+(day22 IS NOT NULL)+(day23 IS NOT NULL)+(day24 IS NOT NULL)+
         (day25 IS NOT NULL)+(day26 IS NOT NULL)+(day27 IS NOT NULL)+(day28 IS NOT NULL)+
         (day29 IS NOT NULL)+(day30 IS NOT NULL)+(day31 IS NOT NULL)
        ,0)
    )
    WHERE bulan = '$bulanNama'
");

function reliabilityClass($v)
{
    if ($v >= 95) return "dark-green";
    if ($v >= 90) return "light-green";
    if ($v >= 85) return "yellow";
    if ($v >= 80) return "orange";
    return "red";
}
function clickableCell($v, $b, $c)
{
    if ($v > 0) {
        return "class='clickable' onclick=\"location.href='wpe_detail.php?branch=" . urlencode($b) . "&filter=$c'\"";
    }
    return "";
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Monitoring EDC Merchant</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <style>
        /* ========== Gaya sidebar  */
        :root {
            --sidebar-bg: #212529;
            --sidebar-hover: #495057;
            --topbar-bg: #007bff;
            --text-color: white;
            --body-bg: #f4f6f9;
        }

        body {
            background: var(--body-bg);
            margin: 0;
            font-family: Arial, sans-serif;
            color: var(--text-color);
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: var(--sidebar-bg);
            overflow-y: auto;
            padding-top: 10px;
            transition: width 0.4s ease, left 0.3s ease;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.2);
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
            transition: all 0.3s ease;
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

        /* ========== Style tabel monitoring ========== */
        table.dataTable {
            table-layout: auto;
            width: auto !important;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            color: black;
        }

        table.dataTable th,
        table.dataTable td {
            vertical-align: middle;
            padding: 5px 8px;
            transition: background-color 0.3s ease;
            white-space: nowrap;
            text-align: center;
        }

        table.dataTable th {
            background-color: #007bff;
            color: #fff;
            text-align: center;
        }

        table.dataTable tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tfoot td {
            font-weight: bold;
            background-color: #e9ecef;
        }

        table.dataTable td:first-child {
            text-align: left !important;
        }

        .dark-green {
            background-color: #006400;
            color: #fff;
        }

        .light-green {
            background-color: #90ee90;
            color: #000;
        }

        .yellow {
            background-color: #ffff66;
            color: #000;
        }

        .orange {
            background-color: #ff9900;
            color: #000;
        }

        .red {
            background-color: #ff4d4d;
            color: #fff;
        }

        .clickable {
            cursor: pointer;
            text-decoration: underline;
            color: #007bff;
        }

        .clickable:hover {
            color: #0056b3;
        }

        #saveImageBtn {
            position: absolute;
            right: 30px;
            margin-top: 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 20px;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            transition: background-color 0.3s;
        }

        #saveImageBtn:hover {
            background-color: #0056b3;
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
                    <li><a href="notfound.php" class="nav-link">NOP Berulang</a></li>
                    <li><a href="mpos_user.php" class="nav-link <?= $currentPage == 'mpos_user.php' ? 'active' : ''; ?>">Implementasi MPOS</a></li>
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

    <!-- Content -->
    <div class="content">
        <h2 style="color:black;text-align:center;">Monitoring EDC Merchant</h2>
        <div id="tableContainer">
            <table id="edcTable" class="display">
                <thead>
                    <tr>
                        <th colspan="8" style="text-align:left;"><strong>Data Tanggal:</strong> <?= $tanggalData ?: '-' ?></th>
                    </tr>
                    <tr>
                        <th>Branch Office</th>
                        <th>Total</th>
                        <th>&lt;1 Jam</th>
                        <th>1–24 Jam</th>
                        <th>Today</th>
                        <th>1–7 Hari</th>
                        <th>&gt;7 Hari</th>
                        <th>Reliability (%)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data)): foreach ($data as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['BranchOffice']) ?></td>
                                <?php foreach (['Total', '<1Jam', '1_24Jam', 'Today', '1_7Hari', '>7Hari'] as $col): $v = $r[$col]; ?>
                                    <td <?= clickableCell($v, $r['BranchOffice'], $col) ?>><?= htmlspecialchars($v) ?></td>
                                <?php endforeach; ?>
                                <td class="<?= reliabilityClass($r['Reliability']) ?>"><?= number_format($r['Reliability'], 2) ?>%</td>
                            </tr>
                        <?php endforeach;
                    else: ?><tr>
                            <td colspan="8">Data tidak ditemukan</td>
                        </tr><?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td>Total</td>
                        <?php foreach (['Total', '<1Jam', '1_24Jam', 'Today', '1_7Hari', '>7Hari'] as $col): $v = $totals[$col]; ?>
                            <td <?= clickableCell($v, 'all', $col) ?>><?= $v ?></td>
                        <?php endforeach; ?>
                        <?php $totalRel = $totals['Total'] > 0 ? (($totals['Total'] - $totals['>7Hari']) / $totals['Total']) * 100 : 0; ?>
                        <td class="<?= reliabilityClass($totalRel) ?>"><?= number_format($totalRel, 2) ?>%</td>
                        <?php

                        ?>

                    </tr>
                </tfoot>
            </table>
        </div>
        <button id="saveImageBtn" title="Simpan Gambar">💾</button>
    </div>

    <script>
        $('#edcTable').DataTable({
            paging: false,
            searching: false,
            info: false,
            autoWidth: true,
            columnDefs: [{
                className: "dt-center",
                targets: [1, 2, 3, 4, 5, 6, 7]
            }]
        });
        document.getElementById("saveImageBtn").addEventListener("click", () => {
            html2canvas(document.getElementById("tableContainer"), {
                scale: 2
            }).then(c => {
                const a = document.createElement("a");
                a.download = "monitoring_edc.png";
                a.href = c.toDataURL();
                a.click();
            });
        });
        document.querySelectorAll("[data-toggle='submenu']").forEach(t => {
            t.addEventListener("click", function(e) {
                e.preventDefault();
                const g = document.getElementById(this.dataset.target);
                const a = document.getElementById(this.dataset.arrow);
                document.querySelectorAll(".submenu").forEach(m => m !== g && m.classList.remove("show"));
                document.querySelectorAll(".right").forEach(i => i !== a && i.classList.replace("fa-angle-down", "fa-angle-left"));
                g.classList.toggle("show");
                a.classList.toggle("fa-angle-down");
                a.classList.toggle("fa-angle-left");
            });
        });
        const sidebar = document.querySelector('.sidebar'),
            content = document.querySelector('.content'),
            topbar = document.querySelector('.topbar'),
            sidebarToggle = document.getElementById('sidebarToggle');
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            sidebar.classList.add('collapsed');
            content.style.marginLeft = '70px';
            topbar.style.left = '70px';
        }
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            const c = sidebar.classList.contains('collapsed');
            content.style.marginLeft = c ? '70px' : '250px';
            topbar.style.left = c ? '70px' : '250px';
            localStorage.setItem('sidebar-collapsed', c);
        });
    </script>
</body>

</html>