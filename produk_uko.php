<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['level'] != 'user') {
    header("Location: login.php");
    exit;
}

// Koneksi database
$conn = new mysqli("localhost", "root", "", "edc_login_db2");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil waktu upload terbaru
$latest   = $conn->query("SELECT MAX(uploaded_at) AS waktu FROM produk_uko")->fetch_assoc();
$lastTime = $latest && !empty($latest['waktu']) ? $latest['waktu'] : null;

// Filter tanggal dari input GET
$tanggal = isset($_GET['tanggal']) && $_GET['tanggal'] !== '' ? $_GET['tanggal'] : null;

$produk = [];
if ($lastTime) {
    if ($tanggal) {
        $stmt = $conn->prepare("SELECT * FROM produk_uko WHERE DATE(uploaded_at) = ? ORDER BY id ASC");
        $stmt->bind_param("s", $tanggal);
    } else {
        $lastDate = date('Y-m-d', strtotime($lastTime));
        $stmt = $conn->prepare("SELECT * FROM produk_uko WHERE DATE(uploaded_at) = ? ORDER BY id ASC");
        $stmt->bind_param("s", $lastDate);
    }
    $stmt->execute();
    $produk = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Produktifitas UKO</title>
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
            transition: all 0.4s;
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
            transition: 0.3s;
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
            font-size: 13px;
            white-space: nowrap;
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
            justify-content: flex-end;
            align-items: center;
            padding: 0 20px;
            transition: left 0.4s ease;
            font-weight: bold;
        }

        .topbar span.username {
            margin-right: 10px;
        }

        .topbar a.logout-btn {
            color: white;
            text-decoration: none;
            margin-left: 10px;
        }

        .topbar a.logout-btn:hover {
            color: #ffd700;
        }

        /* Konten */
        .content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.4s;
            margin-top: 60px;
            display: flex;
            flex-direction: column;
        }

        /* Filter + Clock */
        #filterClockContainer {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        #tanggal {
            padding: 5px 10px;
        }

        /* Table */
        .table th,
        .table td {
            text-align: center;
            vertical-align: middle;
        }

        /* Save JPG button */
        .no-capture {
            display: inline-block;
        }

        @media(max-width:768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .content {
                margin-left: 0;
                margin-top: 60px;
                padding: 10px;
            }

            .topbar {
                left: 0;
                padding: 0 10px;
            }

            #filterClockContainer {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
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
        <div id="topRightContainer">
            <span class="username">👤 <?= htmlspecialchars($_SESSION['username']); ?></span>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Konten -->
    <div class="content">
        <h2>Produktifitas UKO</h2>
        <div id="filterClockContainer">
            <input type="date" name="tanggal" id="tanggal" value="<?= htmlspecialchars($tanggal ?? date('Y-m-d', strtotime($lastTime))) ?>" onchange="location.href='?tanggal='+this.value">
            <div id="clock" style="font-weight:bold;font-size:16px;color:#333;"></div>
        </div>

        <div id="tableCapture">
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>Kanca</th>
                        <th>Non-produktif</th>
                        <th>Produktif</th>
                        <th>Grand Total</th>
                        <th>Persentase</th>
                        <th>Target</th>
                        <th>%Pencapaian</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    $total_nonprod = $total_prod = $total_grand = 0;
                    foreach ($produk as $item):
                        $nonprod = floatval($item['nonproduk_produk']);
                        $prod = floatval($item['produktif']);
                        $grand = floatval($item['grand_total']);
                        $target = floatval($item['target']);
                        $persen = $grand != 0 ? ($prod / $grand) * 100 : 0;
                        $pencapaian = $target != 0 ? ($persen / $target) * 100 : 0;
                        $barColor = $pencapaian >= 100 ? '#2ecc71' : '#f1c40f';

                        $total_nonprod += $nonprod;
                        $total_prod += $prod;
                        $total_grand += $grand;
                    ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($item['kanca_uko']) ?></td>
                            <td><?= $nonprod ?></td>
                            <td><?= $prod ?></td>
                            <td><?= $grand ?></td>
                            <td><?= number_format($persen, 2) ?>%</td>
                            <td><?= $target ?></td>
                            <td>
                                <div style="background:#ecf0f1;border-radius:5px;">
                                    <div class="progress-bar" style="width:<?= $pencapaian ?>%;background:<?= $barColor ?>;color:#fff;text-align:center;">
                                        <?= number_format($pencapaian, 2) ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="background:#bdc3c7;font-weight:bold;">
                        <td colspan="2">Grand Total</td>
                        <td><?= $total_nonprod ?></td>
                        <td><?= $total_prod ?></td>
                        <td><?= $total_grand ?></td>
                        <td><?= number_format(($total_grand ? ($total_prod / $total_grand) * 100 : 0), 2) ?>%</td>
                        <td>95</td>
                        <td><?= number_format((($total_grand ? ($total_prod / $total_grand) * 100 : 0) / 95) * 100, 2) ?>%</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <button class="btn btn-success mt-2 no-capture" onclick="saveJPG()">Save as JPG</button>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        // Realtime clock
        function updateClock() {
            document.getElementById('clock').textContent = new Date().toLocaleString('id-ID', {
                hour12: false
            });
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Sidebar submenu toggle
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

        // Save as JPG
        function saveJPG() {
            html2canvas(document.getElementById('tableCapture')).then(function(canvas) {
                const link = document.createElement('a');
                link.download = 'produk_uko_' + Date.now() + '.jpg';
                link.href = canvas.toDataURL('image/jpeg', 1.0);
                link.click();
            });
        }
    </script>
</body>

</html>