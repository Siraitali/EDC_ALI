<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['level'] != 'user') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>NOP Merchant</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6f9;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: #343a40;
            color: white;
            overflow-y: auto;
            padding-top: 10px;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
        }

        .sidebar.collapsed {
            width: 60px;
        }

        .sidebar a,
        .sidebar .nav-link {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            color: white;
            text-decoration: none;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sidebar a:hover,
        .sidebar .nav-link.active {
            background: #495057;
        }

        .sidebar .nav-link i {
            width: 25px;
            text-align: center;
            margin-right: 10px;
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

        .content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s;
            margin-top: 60px;
        }

        /* Topbar */
        .topbar {
            background: #007bff;
            color: white;
            height: 60px;
            position: fixed;
            top: 0;
            left: 250px;
            right: 0;
            z-index: 1000;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 0 20px;
            transition: left 0.3s;
            gap: 10px;
            font-weight: bold;
        }

        .topbar a {
            color: white;
            text-decoration: none;
        }

        @media(max-width: 768px) {
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
            <button id="sidebarToggle"><i class="fas fa-bars"></i></button>
        </div>

        <ul class="nav flex-column">
            <li class="nav-item"><a href="dashboard.php" class="nav-link d-flex align-items-center"><i class="fas fa-home"></i><span>Dashboard</span></a></li>

            <!-- UKO -->
            <li class="nav-item">
                <a href="#" class="nav-link d-flex justify-content-between align-items-center" data-toggle="submenu" data-target="ukoMenu" data-arrow="ukoArrow">
                    <i class="fas fa-user-tie"></i><span>UKO</span><i class="fas fa-angle-left right" id="ukoArrow"></i></a>
                <ul class="submenu flex-column" id="ukoMenu">
                    <li><a href="relia_uko.php" class="nav-link">Reliability</a></li>
                    <li><a href="produk_uko.php" class="nav-link">Produktifitas</a></li>
                    <li><a href="nop_uko.php" class="nav-link">NOP</a></li>
                </ul>
            </li>

            <!-- Merchant -->
            <li class="nav-item">
                <a href="#" class="nav-link d-flex justify-content-between align-items-center" data-toggle="submenu" data-target="merchantMenu" data-arrow="merchantArrow">
                    <i class="fas fa-store"></i><span>Merchant</span><i class="fas fa-angle-left right" id="merchantArrow"></i>
                </a>
                <ul class="submenu flex-column" id="merchantMenu">
                    <li><a href="nop_merchant.php" class="nav-link active">NOP Berulang</a></li>
                    <li><a href="relia_merchant.php" class="nav-link">Reliability</a></li>
                    <li><a href="produk_merchant.php" class="nav-link">Produktifitas</a></li>
                    <li><a href="fms_merchant.php" class="nav-link">Reliability FMS</a></li>
                    <li><a href="time_merchant.php" class="nav-link">Time Series</a></li>
                    <li><a href="#uker.php" class="nav-link">Group Uker</a></li>
                    <li><a href="vendor.php" class="nav-link">Group Vendor</a></li>
                    <li><a href="time.php" class="nav-link">Time Series</a></li>
                </ul>
            </li>

            <!-- BRILink -->
            <li class="nav-item">
                <a href="#" class="nav-link d-flex justify-content-between align-items-center" data-toggle="submenu" data-target="brilinkMenu" data-arrow="brilinkArrow">
                    <i class="fas fa-link"></i><span>BRILink</span><i class="fas fa-angle-left right" id="brilinkArrow"></i>
                </a>
                <ul class="submenu flex-column" id="brilinkMenu">
                    <li><a href="relia_bri.php" class="nav-link">Reliability</a></li>
                    <li><a href="produk_bri.php" class="nav-link">Produktifitas</a></li>
                </ul>
            </li>

            <!-- Monitoring -->
            <li class="nav-item">
                <a href="#" class="nav-link d-flex justify-content-between align-items-center" data-toggle="submenu" data-target="monitoringMenu" data-arrow="monitoringArrow">
                    <i class="fas fa-chart-line"></i><span>Monitoring</span><i class="fas fa-angle-left right" id="monitoringArrow"></i>
                </a>
                <ul class="submenu flex-column" id="monitoringMenu">
                    <li><a href="#" class="nav-link">Reliability</a></li>
                    <li><a href="#" class="nav-link">NOP Berulang</a></li>
                    <li><a href="#" class="nav-link">BRILink MPOS</a></li>
                    <li><a href="#" class="nav-link">Merchant All</a></li>
                    <li><a href="#" class="nav-link">Produktifitas UKO</a></li>
                    <li><a href="#" class="nav-link">Produktifitas Merchant</a></li>
                    <li><a href="#" class="nav-link">Pending BA MMS</a></li>
                </ul>
            </li>

            <!-- Static Menu -->
            <li class="nav-item static mt-3">
                <a href="#" class="nav-link d-flex align-items-center"><i class="fas fa-file-alt"></i><span>Lapor Pemasangan MPOS</span></a>
                <a href="#" class="nav-link d-flex align-items-center"><i class="fas fa-search"></i><span>Tracking EDC</span></a>
                <a href="edc_rusak_user.php" class="nav-link d-flex align-items-center"><i class="fas fa-exclamation-circle"></i><span>EDC RUSAK</span></a>
                <a href="#" class="nav-link d-flex align-items-center"><i class="fas fa-exclamation-circle"></i><span>Cek Kode Error</span></a>
                <a href="panduan_konfigurasi_edc.php" class="nav-link d-flex align-items-center"><i class="fas fa-book"></i><span>Panduan Konfigurasi EDC</span></a>
            </li>

            <!-- Stok -->
            <li class="nav-item stok mt-3">
                <a href="" class="nav-link d-flex justify-content-between align-items-center" data-toggle="submenu" data-target="stokMenu" data-arrow="stokArrow">
                    <i class="fas fa-box"></i><span>Stok</span><i class="fas fa-angle-left right" id="stokArrow"></i>
                </a>
                <ul class="submenu flex-column show" id="stokMenu">
                    <li><a href="edc.php" class="nav-link">EDC</a></li>
                    <li><a href="termal.php" class="nav-link">Termal</a></li>
                </ul>
            </li>
        </ul>
    </div>

    <!-- Topbar -->
    <div class="topbar">
        <span>👤 <?= $_SESSION['username']; ?></span>
        <a href="logout.php" title="Logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Konten NOP Merchant -->
    <div class="content">
        <h2>NOP Merchant</h2>
        <p>Konten masih kosong...</p>
    </div>

    <script>
        // Sidebar submenu toggle
        document.querySelectorAll("[data-toggle='submenu']").forEach(function(toggle) {
            toggle.addEventListener("click", function(e) {
                e.preventDefault();
                let targetId = this.getAttribute("data-target");
                let arrowId = this.getAttribute("data-arrow");
                let targetMenu = document.getElementById(targetId);
                let arrow = document.getElementById(arrowId);

                document.querySelectorAll(".submenu").forEach(function(menu) {
                    if (menu.id !== targetId) menu.classList.remove("show");
                });
                document.querySelectorAll(".right").forEach(function(icon) {
                    if (icon.id !== arrowId) {
                        icon.classList.replace("fa-angle-down", "fa-angle-left");
                    }
                });

                targetMenu.classList.toggle("show");
                arrow.classList.toggle("fa-angle-down");
                arrow.classList.toggle("fa-angle-left");
            });
        });

        // Sidebar collapse toggle
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('collapsed');
            const content = document.querySelector('.content');
            const topbar = document.querySelector('.topbar');
            if (sidebar.classList.contains('collapsed')) {
                content.style.marginLeft = '60px';
                topbar.style.left = '60px';
            } else {
                content.style.marginLeft = '250px';
                topbar.style.left = '250px';
            }
        });
    </script>
</body>

</html>