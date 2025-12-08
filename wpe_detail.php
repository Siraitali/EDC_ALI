<?php
// =======================
// wpe_detail_full.php
// =======================

// Session & autentikasi
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
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

// Ambil parameter filter
$branch = $_GET['branch'] ?? 'all';
$filter = $_GET['filter'] ?? 'all';

// =======================
// Kondisi filter waktu
// =======================
switch ($filter) {
    case '<1Jam':
        $whereTime = "TIMESTAMPDIFF(MINUTE, Last_Availability, NOW()) < 60";
        break;
    case '1_24Jam':
        $whereTime = "TIMESTAMPDIFF(HOUR, Last_Availability, NOW()) BETWEEN 1 AND 24";
        break;
    case 'Today':
        $whereTime = "DATE(Last_Availability) = CURDATE()";
        break;
    case '1_7Hari':
        $whereTime = "DATE(Last_Availability) BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        break;
    case '>7Hari':
        $whereTime = "DATE(Last_Availability) < DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    default:
        $whereTime = "1";
}

// Filter branch
$branchCondition = ($branch !== 'all') ? "AND TRIM(Mainbranch_Nama_Pemrakarsa) = '" . $conn->real_escape_string($branch) . "'" : "";

// Query data
$sql = "SELECT * FROM wpe WHERE Last_Availability IS NOT NULL AND $whereTime $branchCondition ORDER BY Last_Availability DESC";
$result = $conn->query($sql);

// Kolom yang akan ditampilkan
$cols = [
    'MID',
    'TID',
    'Nama_Merchant',
    'Peruntukkan',
    'Jenis',
    'Kanwil',
    'Domisili_Mainbranch',
    'Branch_Domisili',
    'Kanwil_Norek',
    'Mainbranch_Norek',
    'Branch_Norek',
    'Norek',
    'Ratas_Saldo',
    'Kanwil_Nama_Pemrakarsa',
    'Mainbranch_Nama_Pemrakarsa',
    'Branch_Nama_Pemrakarsa',
    'User_Pemrakarsa',
    'Vendor',
    'Last_Availability',
    'Last_Utility',
    'Last_Transactional',
    'Longitude',
    'Latitude',
    'Alamat_Merchant',
    'Kelurahan',
    'Kecamatan',
    'Kabupaten',
    'Provinsi',
    'Kodepos',
    'PIC',
    'Telp',
    'Tanggal_Pasang',
    'Tanggal_Input'
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail EDC - <?= htmlspecialchars($branch) ?> (<?= htmlspecialchars($filter) ?>)</title>

    <!-- Bootstrap & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <style>
        :root {
            --sidebar-bg: #212529;
            --sidebar-hover: #495057;
            --topbar-bg: #007bff;
            --text-color: white;
            --body-bg: #f4f6f9;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: var(--body-bg);
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
            transition: width .4s ease;
            box-shadow: 2px 0 8px rgba(0, 0, 0, .2);
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
            transition: all .3s ease;
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
            transition: transform .3s;
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
            background: rgba(0, 0, 0, .85);
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
            transition: max-height .4s ease;
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
            transition: left .4s ease;
        }

        .content {
            margin-left: 250px;
            padding: 20px;
            margin-top: 60px;
            transition: margin-left .4s ease;
            color: #000;
            background: #f4f6f9;
        }

        /* Responsive */
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

        /* DataTables & Highlight */
        table.dataTable {
            width: 100% !important;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .1);
        }

        table.dataTable th,
        table.dataTable td {
            padding: 5px 8px;
            white-space: nowrap;
            vertical-align: middle;
            color: #000;
        }

        table.dataTable th {
            background-color: #007bff;
            color: #fff;
            text-align: center;
        }

        table.dataTable tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr.more-than-7days td {
            background-color: #ffcccc;
        }

        tr.less-than-1hour td {
            background-color: #ccffcc;
        }

        a.back-btn {
            display: inline-block;
            margin-bottom: 10px;
            text-decoration: none;
            color: #007bff;
        }

        a.back-btn:hover {
            text-decoration: underline;
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
    <!-- ================= Topbar ================= -->
    <div class="topbar">
        <div></div>
        <div>
            <span>👤 <?= $_SESSION['username']; ?></span>
            <a href="logout.php" title="Logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- ================= Content ================= -->
    <div class="content">
        <h2>Detail EDC <?= htmlspecialchars($branch) ?> - Filter: <?= htmlspecialchars($filter) ?></h2>
        <a href="wpe_user.php" class="back-btn">&larr; Kembali ke Monitoring</a>

        <table id="detailTable" class="display nowrap">
            <thead>
                <tr>
                    <?php foreach ($cols as $col) echo "<th>$col</th>"; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $rowClass = '';
                        if (isset($row['Last_Availability'])) {
                            $diffMin = (strtotime(date('Y-m-d H:i:s')) - strtotime($row['Last_Availability'])) / 60;
                            $diffHour = $diffMin / 60;
                            if ($diffMin < 60) $rowClass = 'less-than-1hour';
                            elseif ($diffHour > 168) $rowClass = 'more-than-7days';
                        }
                        echo "<tr class='$rowClass'>";
                        foreach ($cols as $col) {
                            $val = $row[$col] ?? '';

                            // Kolom TID jadi hyperlink
                            if ($col === 'TID' && !empty($val)) {
                                $link = "detail_wpe.php?tid=" . urlencode($val);
                                echo "<td><a href='$link' style='color:#007bff;text-decoration:none;' target='_blank'>" . htmlspecialchars($val) . "</a></td>";
                            }
                            // Kolom Ratas_Saldo diformat ke Rupiah
                            elseif ($col === 'Ratas_Saldo' && is_numeric($val)) {
                                echo "<td>Rp " . number_format($val, 0, ',', '.') . "</td>";
                            }
                            // Kolom lain tampil biasa
                            else {
                                echo "<td>" . htmlspecialchars($val) . "</td>";
                            }
                        }

                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='" . count($cols) . "'>Data tidak ditemukan</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- ================= Scripts ================= -->
    <script>
        $(document).ready(function() {
            $('#detailTable').DataTable({
                paging: true,
                searching: true,
                info: true,
                autoWidth: false,
                scrollX: true,
                order: [
                    [18, 'desc']
                ]
            });
        });

        // Submenu toggle & Sidebar collapse
        document.querySelectorAll("[data-toggle='submenu']").forEach(toggle => {
            toggle.addEventListener("click", function(e) {
                e.preventDefault();
                const target = document.getElementById(this.dataset.target);
                const arrow = document.getElementById(this.dataset.arrow);
                document.querySelectorAll(".submenu").forEach(m => m !== target && m.classList.remove("show"));
                document.querySelectorAll(".right").forEach(i => i !== arrow && i.classList.replace("fa-angle-down", "fa-angle-left"));
                target.classList.toggle("show");
                arrow.classList.toggle("fa-angle-down");
                arrow.classList.toggle("fa-angle-left");
            });
        });
        const sidebar = document.querySelector('.sidebar');
        const content = document.querySelector('.content');
        const topbar = document.querySelector('.topbar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            sidebar.classList.add('collapsed');
            content.style.marginLeft = '70px';
            topbar.style.left = '70px';
        }
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            const collapsed = sidebar.classList.contains('collapsed');
            content.style.marginLeft = collapsed ? '70px' : '250px';
            topbar.style.left = collapsed ? '70px' : '250px';
            localStorage.setItem('sidebar-collapsed', collapsed);
        });

        // Auto buka submenu aktif
        document.querySelectorAll('.submenu').forEach(sub => {
            if (sub.querySelector('.active')) {
                sub.classList.add('show');
                const arrow = document.getElementById(sub.id.replace('Menu', 'Arrow'));
                if (arrow) arrow.classList.replace('fa-angle-left', 'fa-angle-down');
            }
        });
    </script>

</body>

</html>