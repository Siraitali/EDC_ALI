<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['level'] != 'user') {
    header("Location: login.php");
    exit;
}
$currentPage = basename($_SERVER['PHP_SELF']);
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

include 'koneksi.php';

// Query hanya Region 2 / Pekanbaru
$query = "SELECT kode_cabang, nama_cabang, nama_kanwil, jumlah_outlet, live_produktif_percent, get_date 
          FROM productivity 
          WHERE nama_kanwil = 'Region 2 / Pekanbaru'";
$result = mysqli_query($conn, $query);

$get_date = '';
if (mysqli_num_rows($result) > 0) {
    $firstRow = mysqli_fetch_assoc($result);
    $get_date = $firstRow['get_date'];
    mysqli_data_seek($result, 0);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Productivity - Region 2 / Pekanbaru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
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
            font-family: 'Segoe UI', sans-serif;
            background: var(--body-bg);
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
            z-index: 1001;
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar .nav-link {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            color: white;
            text-decoration: none;
            font-size: 14px;
            position: relative;
            transition: all 0.3s ease;
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

        /* Topbar */
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
    justify-content: flex-end;
    align-items: center; /* ini untuk vertical center */
    gap: 15px;
    padding: 0 20px; /* horizontal padding tetap */
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
            z-index: 0;
        }

        /* Table */
        .table-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            animation: fadeIn 0.8s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h2 {
            text-align: left;
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }

        .date-info {
            color: #6c757d;
            margin-bottom: 25px;
            font-size: 15px;
        }

        table th {
            background: linear-gradient(135deg, #007bff, #0056d6);
            color: white;
            text-align: center;
            vertical-align: middle;
            cursor: pointer;
        }

        table td {
            text-align: center;
            vertical-align: middle;
        }

        td.no-click {
            pointer-events: none;
            cursor: default;
            color: #333;
        }

        td.no-click a {
            pointer-events: none !important;
            color: inherit !important;
            text-decoration: none !important;
        }

        tbody tr:nth-child(even) {
            background-color: #f0f7ff;
        }

        tbody tr:hover {
            background-color: #e6f0ff;
            transition: 0.3s;
        }

        .fade-slide {
            opacity: 0;
            transform: translateY(15px);
            transition: all 0.6s ease;
        }

        .fade-slide.show {
            opacity: 1;
            transform: translateY(0);
        }

        /* Responsive */
        @media(max-width:768px) {
            table {
                font-size: 12px;
            }

            .content {
                margin-left: 0;
                margin-top: 120px;
            }

            .topbar {
                left: 0;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
        }

        @media(max-width:576px) {

            table th,
            table td {
                font-size: 11px;
                padding: 6px;
            }
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
    <!-- TOPBAR -->
    <div class="topbar">
        <span>👤 <?= $_SESSION['username']; ?></span>
        <a href="logout.php" title="Logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>


    <!-- Content -->
    <div class="content">
        <div class="container">
            <div class="table-container">
                <h2>Data Productivity - Region 2 / Pekanbaru</h2>
                <div class="date-info">Data Tanggal: <?= !empty($get_date) ? date('d F Y', strtotime($get_date)) : '-' ?></div>
                <div class="table-responsive">
                    <table id="dataTable" class="table table-bordered table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Kode Cabang</th>
                                <th>Nama Cabang</th>
                                <th>Nama Kanwil</th>
                                <th>Jumlah Outlet</th>
                                <th onclick="sortTable(4)">Live Produktif (%) <span class="sort-icon">⇅</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr class='fade-slide'>
                            <td>{$row['kode_cabang']}</td>
                            <td>{$row['nama_cabang']}</td>
                            <td>{$row['nama_kanwil']}</td>
                            <td class='no-click'>" . htmlspecialchars(strip_tags($row['jumlah_outlet'])) . "</td>
                            <td>{$row['live_produktif_percent']}%</td>
                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5'>Tidak ada data untuk Region 2 / Pekanbaru.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Submenu toggle
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

        // Sidebar collapse
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

        // Animasi tabel
        document.addEventListener("DOMContentLoaded", () => {
            document.querySelectorAll(".fade-slide").forEach((row, i) => setTimeout(() => row.classList.add("show"), i * 100));
        });

        // Sorting Live Produktif
        function sortTable(columnIndex) {
            const table = document.getElementById("dataTable");
            let rows = Array.from(table.rows).slice(1);
            const isAsc = table.getAttribute("data-sort") !== "asc";
            rows.sort((a, b) => {
                let valA = parseFloat(a.cells[columnIndex].innerText.replace('%', '')) || 0;
                let valB = parseFloat(b.cells[columnIndex].innerText.replace('%', '')) || 0;
                return isAsc ? valA - valB : valB - valA;
            });
            rows.forEach(row => table.tBodies[0].appendChild(row));
            table.setAttribute("data-sort", isAsc ? 'asc' : 'desc');
        }

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