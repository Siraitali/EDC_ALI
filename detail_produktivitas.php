<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['level'] != 'user') {
    header("Location: login.php");
    exit;
}

include 'koneksi.php';

$outlet_code = $_GET['outlet_code'] ?? '';
if (!$outlet_code) die("Outlet code tidak ditemukan.");

$query = $conn->prepare("SELECT * FROM produk_bri WHERE outlet_code=? LIMIT 1");
$query->bind_param("s", $outlet_code);
$query->execute();
$result = $query->get_result();
if ($result->num_rows == 0) die("Data tidak ditemukan.");

$data = $result->fetch_assoc();

// Badge warna
function badgeStatus($status)
{
    switch (strtolower($status)) {
        case 'live':
            return '<span class="badge bg-success">LIVE</span>';
        case 'offline':
            return '<span class="badge bg-danger">OFFLINE</span>';
        default:
            return '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>';
    }
}

function badgeProduktif($value)
{
    if ($value >= 90) return '<span class="badge bg-success">' . htmlspecialchars($value) . '%</span>';
    if ($value >= 70) return '<span class="badge bg-warning text-dark">' . htmlspecialchars($value) . '%</span>';
    return '<span class="badge bg-danger">' . htmlspecialchars($value) . '%</span>';
}

function formatRupiah($number)
{
    return 'Rp ' . number_format($number, 0, ',', '.');
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Produktivitas <?= htmlspecialchars($data['outlet_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: #212529;
            --sidebar-hover: #495057;
            --topbar-bg: #007bff;
            --text-color: white;
            --body-bg: #f5f7fa;
        }

        body {
            background: var(--body-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            color: #212529;
            animation: fadeIn 0.8s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        /* Card Detail */
        .card {
            max-width: 900px;
            margin: 40px auto;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            background: linear-gradient(90deg, #0d6efd, #6610f2);
            color: white;
            font-weight: 600;
            font-size: 1.3rem;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }

        .card-body {
            padding: 25px;
        }

        .btn-back {
            display: inline-block;
            margin-bottom: 20px;
            background: #0d6efd;
            color: white;
            border-radius: 8px;
            padding: 8px 16px;
            text-decoration: none;
            transition: 0.3s;
        }

        .btn-back:hover {
            background: #084298;
            color: white;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px 30px;
        }

        .detail-grid dt {
            font-weight: 500;
            margin-bottom: 0;
        }

        .detail-grid dd {
            margin-bottom: 10px;
        }

        .detail-grid dd i {
            margin-right: 5px;
        }

        @media(max-width:768px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }

            .topbar {
                left: 0;
            }

            .content {
                margin-left: 0;
                padding: 10px;
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
                    <li><a href="notfound.php" class="nav-link <?= $currentPage == 'produk_bri.php' ? 'active' : ''; ?>">Produktifitas</a></li>
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

    <!-- Konten Detail -->
    <div class="content">
        <div class="card">
            <div class="card-header">Detail Produktivitas - <?= htmlspecialchars($data['outlet_name']); ?></div>
            <div class="card-body">
                <dl class="detail-grid">
                    <dt>Outlet Code</dt>
                    <dd><?= htmlspecialchars($data['outlet_code']); ?></dd>
                    <dt>Outlet Name</dt>
                    <dd><?= htmlspecialchars($data['outlet_name']); ?></dd>
                    <dt>Created Date</dt>
                    <dd><?= htmlspecialchars($data['created_date']); ?></dd>
                    <dt>Umur Hari</dt>
                    <dd><?= htmlspecialchars($data['umur_hari']); ?></dd>
                    <dt>Kode Kanwil</dt>
                    <dd><?= htmlspecialchars($data['KODE_KANWIL']); ?></dd>
                    <dt>Nama Kanwil</dt>
                    <dd><?= htmlspecialchars($data['NAMA_KANWIL']); ?></dd>
                    <dt>Branch Outlet</dt>
                    <dd><?= htmlspecialchars($data['branch_outlet']); ?></dd>
                    <dt>ID Agen</dt>
                    <dd><?= htmlspecialchars($data['id_agen']); ?></dd>
                    <dt>Check Agen</dt>
                    <dd><?= htmlspecialchars($data['check_agen']); ?></dd>
                    <dt>Lokasi</dt>
                    <dd><?= htmlspecialchars($data['lokasi']); ?></dd>
                    <dt>Status Live</dt>
                    <dd><?= badgeStatus($data['status_live']); ?></dd>
                    <dt>Total Transaksi</dt>
                    <dd><i class="bi bi-receipt"></i> <?= formatRupiah($data['total_transaksi']); ?></dd>
                    <dt>Total Nominal</dt>
                    <dd><i class="bi bi-cash-stack"></i> <?= formatRupiah($data['total_nominal']); ?></dd>
                    <dt>Total Fee</dt>
                    <dd><i class="bi bi-wallet2"></i> <?= formatRupiah($data['total_fee']); ?></dd>
                    <dt>Produktif</dt>
                    <dd><?= badgeProduktif($data['produktif']); ?></dd>
                    <dt>Live Produktif</dt>
                    <dd><?= badgeProduktif($data['LiveProduktif']); ?></dd>
                    <dt>PN PPBK</dt>
                    <dd><?= htmlspecialchars($data['PN_PPBK']); ?></dd>
                    <dt>Nama PPBK</dt>
                    <dd><?= htmlspecialchars($data['Nama_PPBK']); ?></dd>
                </dl>
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