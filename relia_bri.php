<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['level'] != 'user') {
    header("Location: login.php");
    exit;
}

$currentPage = basename($_SERVER['PHP_SELF']);

// Database
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "edc_login_db2";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil data kanca
$query = "
SELECT 
    kanca_name AS kanca,
    COUNT(*) AS total_outlet,
    SUM(CASE WHEN last_login = '0000-00-00 00:00:00' OR last_login = '-' THEN 1 ELSE 0 END) AS belum_aktivasi,
    SUM(CASE WHEN last_login != '0000-00-00 00:00:00' AND last_login != '-' THEN 1 ELSE 0 END) AS telah_aktivasi,
    SUM(CASE WHEN last_login != '0000-00-00 00:00:00' 
              AND DATEDIFF(NOW(), last_login) = 0 THEN 1 ELSE 0 END) AS today,
    SUM(CASE WHEN last_login != '0000-00-00 00:00:00' 
              AND DATEDIFF(NOW(), last_login) BETWEEN 1 AND 7 THEN 1 ELSE 0 END) AS day_01_07,
    SUM(CASE WHEN last_login != '0000-00-00 00:00:00' 
              AND DATEDIFF(NOW(), last_login) BETWEEN 8 AND 15 THEN 1 ELSE 0 END) AS day_08_15,
    SUM(CASE WHEN last_login != '0000-00-00 00:00:00' 
              AND DATEDIFF(NOW(), last_login) BETWEEN 16 AND 30 THEN 1 ELSE 0 END) AS day_16_30,
    SUM(CASE WHEN last_login != '0000-00-00 00:00:00' 
              AND DATEDIFF(NOW(), last_login) > 30 THEN 1 ELSE 0 END) AS day_more_30
FROM detailbri
GROUP BY kanca_name
ORDER BY kanca_name ASC
";
$result = $conn->query($query);

// Ambil real scrap terbaru
$scrapQuery  = "SELECT scrap_at FROM detailbri ORDER BY scrap_at DESC LIMIT 1";
$scrapResult = $conn->query($scrapQuery);
$scrap_at    = ($scrapResult && $scrapResult->num_rows > 0) ? $scrapResult->fetch_assoc()['scrap_at'] : '-';

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monitoring BRILink Android (MPOS)</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- html2canvas -->
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

    <style>
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
            transition: width 0.35s ease, left 0.25s ease;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.18);
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
            transition: all 0.25s ease;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--sidebar-hover);
        }

        .sidebar .nav-link i {
            width: 25px;
            text-align: center;
            margin-right: 10px;
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
            transition: max-height 0.35s ease;
            padding-left: 28px;
        }

        .submenu.show {
            max-height: 800px;
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
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            transition: left 0.35s ease;
        }

        .topbar a {
            color: white;
            text-decoration: none;
        }

        /* Content area */
        .content {
            margin-left: 250px;
            padding: 20px;
            margin-top: 60px;
            transition: margin-left 0.35s ease;
        }

        /* Table */
        .table-wrap {
            background: transparent;
        }

        table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            width: 100%;
        }

        thead th {
            background: #0d6efd;
            color: white;
            text-align: center;
            padding: 10px;
            cursor: pointer;
            user-select: none;
        }

        tbody td {
            padding: 10px;
            text-align: center;
            vertical-align: middle;
        }

        tbody tr:nth-child(even) {
            background: #f8fbff;
        }

        tbody tr:hover {
            background: #e8f3ff;
            transition: 0.2s;
        }

        .grand-total {
            font-weight: 700;
            background: #e0e0e0;
        }

        /* Save JPG button positioned inside header of content */
        #save-jpg-btn {
            padding: 6px 12px;
            background: #0d6efd;
            color: #fff;
            border: 0;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
        }

        #save-jpg-btn:hover {
            background: #0056b3;
        }

        /* Responsive */
        @media(max-width:768px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .topbar {
                left: 0;
            }

            .content {
                margin-left: 0;
                padding: 12px;
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
    <div class="topbar" id="topbar">
        <div></div>
        <div>
            <span>👤 <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
            <a href="logout.php" class="ms-3" title="Logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Content -->
    <div class="content">
        <div class="container-fluid" id="capture-area">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h4 class="mb-0"><i class="bi bi-hdd-network"></i> Monitoring BRILink Android (MPOS)</h4>
                <div>
                    <button id="save-jpg-btn" class="btn btn-primary" type="button" onclick="saveAsJPG()">
                        <i class="bi bi-file-earmark-image"></i> Save JPG
                    </button>
                </div>
            </div>

            <div class="mb-3 text-muted">Last Update Data : <?= htmlspecialchars($scrap_at) ?></div>

            <div class="table-wrap">
                <table class="table table-bordered align-middle" id="eliaTable">
                    <thead>
                        <tr>
                            <th>Kanca</th>
                            <th>Total Outlet</th>
                            <th>Belum Aktivasi</th>
                            <th>Today</th>
                            <th>01 - 07</th>
                            <th>08 - 15</th>
                            <th>16 - 30</th>
                            <th>> 30</th>
                            <th>Telah Aktivasi</th>
                            <th>Relia (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result && $result->num_rows > 0) {
                            $total_outlet = $belum_aktivasi = $today = $day_01_07 = $day_08_15 = $day_16_30 = $day_more_30 = $telah_aktivasi = 0;

                            while ($r = $result->fetch_assoc()) {
                                // ensure numeric cast
                                $total_outlet_row = (int)$r['total_outlet'];
                                $telah_aktivasi_row = (int)$r['telah_aktivasi'];
                                $day_more_30_row = (int)$r['day_more_30'];

                                $row_relia = $total_outlet_row > 0 ? round((($telah_aktivasi_row - $day_more_30_row) / $total_outlet_row) * 100, 2) : 0;

                                echo "<tr>";
                                echo "<td><a href='detailbri.php?kanca=" . urlencode($r['kanca']) . "&kolom=total_outlet'>" . htmlspecialchars($r['kanca']) . "</a></td>";
                                echo "<td><a href='detailbri.php?kanca=" . urlencode($r['kanca']) . "&kolom=total_outlet'>" . number_format($total_outlet_row) . "</a></td>";
                                echo "<td><a href='detailbri.php?kanca=" . urlencode($r['kanca']) . "&kolom=belum_aktivasi'>" . number_format((int)$r['belum_aktivasi']) . "</a></td>";
                                echo "<td><a href='detailbri.php?kanca=" . urlencode($r['kanca']) . "&kolom=today'>" . number_format((int)$r['today']) . "</a></td>";
                                echo "<td><a href='detailbri.php?kanca=" . urlencode($r['kanca']) . "&kolom=day_01_07'>" . number_format((int)$r['day_01_07']) . "</a></td>";
                                echo "<td><a href='detailbri.php?kanca=" . urlencode($r['kanca']) . "&kolom=day_08_15'>" . number_format((int)$r['day_08_15']) . "</a></td>";
                                echo "<td><a href='detailbri.php?kanca=" . urlencode($r['kanca']) . "&kolom=day_16_30'>" . number_format((int)$r['day_16_30']) . "</a></td>";
                                echo "<td><a href='detailbri.php?kanca=" . urlencode($r['kanca']) . "&kolom=day_more_30'>" . number_format($day_more_30_row) . "</a></td>";
                                echo "<td><a href='detailbri.php?kanca=" . urlencode($r['kanca']) . "&kolom=telah_aktivasi'>" . number_format($telah_aktivasi_row) . "</a></td>";
                                echo "<td>" . $row_relia . "%</td>";
                                echo "</tr>";

                                $total_outlet     += $total_outlet_row;
                                $belum_aktivasi   += (int)$r['belum_aktivasi'];
                                $today            += (int)$r['today'];
                                $day_01_07        += (int)$r['day_01_07'];
                                $day_08_15        += (int)$r['day_08_15'];
                                $day_16_30        += (int)$r['day_16_30'];
                                $day_more_30      += $day_more_30_row;
                                $telah_aktivasi   += $telah_aktivasi_row;
                            }

                            $relia_total = $total_outlet > 0 ? round((($telah_aktivasi - $day_more_30) / $total_outlet) * 100, 2) : 0;

                            echo "<tr class='grand-total'>";
                            echo "<td>Grand Total</td>";
                            echo "<td>" . number_format($total_outlet) . "</td>";
                            echo "<td>" . number_format($belum_aktivasi) . "</td>";
                            echo "<td>" . number_format($today) . "</td>";
                            echo "<td>" . number_format($day_01_07) . "</td>";
                            echo "<td>" . number_format($day_08_15) . "</td>";
                            echo "<td>" . number_format($day_16_30) . "</td>";
                            echo "<td>" . number_format($day_more_30) . "</td>";
                            echo "<td>" . number_format($telah_aktivasi) . "</td>";
                            echo "<td>" . $relia_total . "%</td>";
                            echo "</tr>";
                        } else {
                            echo '<tr><td colspan="10" class="text-center text-muted">Tidak ada data ditemukan.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        /* Sidebar collapse & submenu */
        const sidebar = document.getElementById('sidebar');
        const content = document.querySelector('.content');
        const topbar = document.getElementById('topbar');
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

        document.querySelectorAll("[data-toggle='submenu']").forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.getElementById(this.dataset.target);
                const arrow = document.getElementById(this.dataset.arrow);
                if (!target) return;
                target.classList.toggle('show');
                if (arrow) {
                    arrow.classList.toggle('fa-angle-down');
                    arrow.classList.toggle('fa-angle-left');
                }
            });
        });

        /* Save as JPG */
        function saveAsJPG() {
            const el = document.getElementById('capture-area');
            if (!el) return alert('Area capture tidak ditemukan.');
            html2canvas(el, {
                scale: 2
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'Monitoring_BRILink_<?= date("Ymd_His") ?>.jpg';
                link.href = canvas.toDataURL('image/jpeg', 1.0);
                link.click();
            }).catch(err => {
                console.error(err);
                alert('Gagal membuat JPG. Cek console untuk detail.');
            });
        }

        /* Sorting */
        document.addEventListener('DOMContentLoaded', () => {
            const table = document.getElementById('eliaTable');
            if (!table) return;
            const headers = table.querySelectorAll('thead th');
            let dir = 1;
            headers.forEach((th, idx) => {
                th.style.cursor = 'pointer';
                th.addEventListener('click', () => {
                    const tbody = table.querySelector('tbody');
                    const rows = Array.from(tbody.querySelectorAll('tr:not(.grand-total)'));
                    if (rows.length === 0) return;
                    // determine numeric column
                    const isNumeric = rows.every(r => !isNaN(r.children[idx].innerText.replace(/[%,]/g, '').trim()));
                    dir = -dir;
                    rows.sort((a, b) => {
                        let A = a.children[idx].innerText.replace(/[,%]/g, '').trim();
                        let B = b.children[idx].innerText.replace(/[,%]/g, '').trim();
                        if (isNumeric) {
                            A = parseFloat(A) || 0;
                            B = parseFloat(B) || 0;
                            return (A - B) * dir;
                        } else {
                            A = A.toLowerCase();
                            B = B.toLowerCase();
                            if (A > B) return dir;
                            if (A < B) return -dir;
                            return 0;
                        }
                    });
                    // insert before grand-total row if exists
                    const grand = tbody.querySelector('.grand-total');
                    rows.forEach(r => tbody.insertBefore(r, grand || null));
                });
            });
        });
    </script>
</body>

</html>
<?php
// close connection
$conn->close();
?>