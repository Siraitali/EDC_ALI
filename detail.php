<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['level'] != 'user') {
    header("Location: login.php");
    exit;
}
include 'koneksi.php';
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

// Ambil parameter dari URL
$kanca    = isset($_GET['kanca']) && $_GET['kanca'] !== '' ? $_GET['kanca'] : 'N/A';
$kategori = isset($_GET['kategori']) && $_GET['kategori'] !== '' ? $_GET['kategori'] : 'N/A';
$start    = isset($_GET['start']) && $_GET['start'] !== '' ? $_GET['start'] : date('Y-m-d');
$end      = isset($_GET['end']) && $_GET['end'] !== '' ? $_GET['end'] : date('Y-m-d');

// Fungsi ambil data
function getDetailRows($conn, $kanca, $kategori, $start, $end)
{
    $whereKategori = '';
    switch ($kategori) {
        case 'today':
            $whereKategori = "status_available = 1";
            break;
        case '1-7':
            $whereKategori = "status_available = 2";
            break;
        case '8-15':
            $whereKategori = "status_available = 3";
            break;
        case '16-30':
            $whereKategori = "status_available = 4";
            break;
        case '>30':
            $whereKategori = "status_available = 5";
            break;
        default:
            $whereKategori = "1=1";
            break;
    }

    // Filter kanca
    if ($kanca === 'All') {
        $whereKanca = "TRIM(uker_nama_implementor) <> ''";
        $sql = "
            SELECT *
            FROM edc_merchant_raw
            WHERE $whereKanca
            AND $whereKategori
            AND date_input BETWEEN ? AND ?
            ORDER BY mid ASC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $start, $end);
    } else {
        $whereKanca = "uker_nama_implementor = ?";
        $sql = "
            SELECT *
            FROM edc_merchant_raw
            WHERE $whereKanca
            AND $whereKategori
            AND date_input BETWEEN ? AND ?
            ORDER BY mid ASC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $kanca, $start, $end);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $rowsHtml = '';
    $no = 1;
    while ($row = $result->fetch_assoc()) {
        $lastAvailable = $row['last_available'];
        $hariTidakAktif = $lastAvailable ? (new DateTime())->diff(new DateTime($lastAvailable))->days : 0;
        $highlightClass = $hariTidakAktif > 30 ? 'highlight' : '';
        $rowsHtml .= '<tr>
            <td>' . $no++ . '</td>
            <td><a href="detail_uko.php?mid=' . urlencode($row['mid']) . '">' . htmlspecialchars($row['mid']) . '</a></td>
            <td>' . htmlspecialchars($row['nama_merchant']) . '</td>
            <td>' . htmlspecialchars($row['uker_nama_implementor']) . '</td>
            <td>' . htmlspecialchars($row['uker_nama_implementor']) . '</td>
            <td class="' . $highlightClass . '">' . $hariTidakAktif . '</td>
            <td>' . htmlspecialchars($row['last_available']) . '</td>
        </tr>';
    }
    return $rowsHtml;
}


// Jika request AJAX untuk reload data
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    echo getDetailRows($conn, $kanca, $kategori, $start, $end);
    exit;
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Detail Data - <?= htmlspecialchars($kanca) ?> [<?= htmlspecialchars($kategori) ?>]</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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

        /* Tooltip */
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

        /* Submenu */
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

        /* Table styling */
        .table-wrapper {
            background: #fff;
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        thead th {
            background: linear-gradient(90deg, #4a90e2, #007bff);
            color: #fff;
            padding: 12px;
            text-align: center;
            border-bottom: 2px solid #0056b3;
        }

        tbody td {
            padding: 10px 8px;
            border-top: 1px solid #e6e6e6;
            text-align: center;
        }

        tbody td:first-child {
            text-align: left;
        }

        tbody tr:hover {
            background: #eaf4ff;
            transition: 0.2s;
        }

        .highlight {
            background: #f8d7da;
            color: #721c24;
            font-weight: bold;
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

    <!-- Content Detail -->
    <div class="content">
        <h2>Detail Data <?= htmlspecialchars($kanca) ?> (<?= htmlspecialchars($kategori) ?>)</h2>
        <a href="relia_uko.php?filterStart=<?= urlencode($start) ?>&filterEnd=<?= urlencode($end) ?>" class="btn btn-secondary btn-sm mb-2">← Kembali</a>

        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom:10px;">
            <div>Data Tanggal: <span id="realTime"></span></div>
            <a href="export_excel.php?kanca=<?= urlencode($kanca) ?>&kategori=<?= urlencode($kategori) ?>&start=<?= urlencode($start) ?>&end=<?= urlencode($end) ?>" class="btn btn-success btn-sm">Export Excel</a>
        </div>

        <div class="table-wrapper">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>TID</th>
                        <th>Nama</th>
                        <th>Kanca</th>
                        <th>Implementor</th>
                        <th>Hari Tidak Aktif</th>
                        <th>Last Available</th>
                    </tr>
                </thead>
                <tbody id="detailBody">
                    <?= getDetailRows($conn, $kanca, $kategori, $start, $end) ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Real-time tanggal
        function updateTime() {
            const now = new Date();
            document.getElementById('realTime').textContent =
                now.getDate().toString().padStart(2, '0') + '-' +
                (now.getMonth() + 1).toString().padStart(2, '0') + '-' +
                now.getFullYear() + ' ' +
                now.getHours().toString().padStart(2, '0') + ':' +
                now.getMinutes().toString().padStart(2, '0') + ':' +
                now.getSeconds().toString().padStart(2, '0');
        }
        setInterval(updateTime, 1000);
        updateTime();

        // Auto reload data terbaru setiap 5 detik
        function reloadData() {
            let url = new URL(window.location.href);
            url.searchParams.set('ajax', '1');

            fetch(url.toString())
                .then(response => response.text())
                .then(html => {
                    document.getElementById('detailBody').innerHTML = html;
                })
                .catch(err => console.error('Error reload data:', err));
        }
        setInterval(reloadData, 5000);

        // ========== Submenu toggle ==========
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

        // ========== Sidebar collapse toggle ==========
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
                if (arrow) {
                    arrow.classList.replace('fa-angle-left', 'fa-angle-down');
                }
            }
        });
    </script>

</body>

</html>