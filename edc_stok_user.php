<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['level'] != 'user') {
    header("Location: login.php");
    exit;
}
$currentPage = basename($_SERVER['PHP_SELF']);

include 'koneksi.php';

// Ambil data EDC
$result = $conn->query("SELECT * FROM edc_stok ORDER BY no DESC");

// Statistik
$qTersedia = $conn->query("SELECT COUNT(*) AS total FROM edc_stok WHERE status='Tersedia' OR status IS NULL OR status=''")->fetch_assoc()['total'] ?? 0;
$qTerpakai = $conn->query("SELECT COUNT(*) AS total FROM edc_stok WHERE status='Terpakai'")->fetch_assoc()['total'] ?? 0;

// List type & branch
$typeList = [];
$branchList = [];
$qType = $conn->query("SELECT DISTINCT type_mesin FROM edc_stok ORDER BY type_mesin ASC");
while ($r = $qType->fetch_assoc()) if (!empty($r['type_mesin'])) $typeList[] = $r['type_mesin'];

$qBranch = $conn->query("SELECT DISTINCT branch_office FROM edc_stok ORDER BY branch_office ASC");
while ($r = $qBranch->fetch_assoc()) if (!empty($r['branch_office'])) $branchList[] = $r['branch_office'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>📦 Data Stok EDC</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        /* ========== SIDEBAR & LAYOUT ========== */
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
            color: #111;
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

        /* CARD & TABLE */
        .card {
            border-radius: 14px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(90deg, #007bff, #00c6ff);
            color: #fff;
            border-radius: 14px 14px 0 0;
            padding: 15px 20px;
        }

        .stat-box {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            margin-bottom: 20px;
        }

        .stat-item {
            flex: 1 1 160px;
            background: #ffffff;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.08);
        }

        .stat-item h5 {
            margin: 0;
            font-size: 15px;
            color: #444;
            font-weight: 600;
        }

        .stat-item p {
            margin: 6px 0 0;
            font-size: 24px;
            font-weight: 800;
            color: #111;
        }

        .status-Tersedia {
            color: #198754;
        }

        .status-Terpakai {
            color: #0d6efd;
        }

        .filter-bar {
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            justify-content: flex-start;
        }

        .filter-bar label {
            font-weight: 600;
        }

        table thead {
            background-color: #007bff;
            color: #fff;
        }

        .status-badge {
            border-radius: 10px;
            padding: 3px 8px;
            font-weight: 600;
            color: #fff;
            display: inline-block;
            min-width: 70px;
            text-align: center;
        }

        .status-Tersedia {
            background: #198754;
        }

        .status-Terpakai {
            background: #0d6efd;
        }

        .status-Rusak {
            background: #dc3545;
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
                    <li><a href="notfound.php" class="nav-link">NOP Berulang</a></li>
                </ul>
            </li>

            <!-- Merchant -->
            <li class="nav-item">
                <a href="#" class="nav-link" data-toggle="submenu" data-target="merchantMenu" data-arrow="merchantArrow" title="Merchant">
                    <i class="fas fa-store"></i><span>Merchant</span><i class="fas fa-angle-left right" id="merchantArrow"></i>
                </a>
                <ul class="submenu" id="merchantMenu">
                    <li><a href="wpe_user.php" class="nav-link">Group Uker</a></li>
                    <li><a href="notfound.php" class="nav-link">Produktifitas</a></li>
                    <li><a href="notfound.php" class="nav-link">Reliability FMS</a></li>
                    <li><a href="notfound.php" class="nav-link">Time Series</a></li>
                    <li><a href="vendor.php" class="nav-link">Group Vendor</a></li>
                    <li><a href="notfound.php" class="nav-link">NOP Berulang</a></li>
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
                    <li><a href="edc_stok_user.php" class="nav-link <?= $currentPage == 'edc_stok.php' ? 'active' : ''; ?>">EDC EDC</a></li>
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
        <div class="card">
            <div class="card-header">
                <h4 class="m-0">📦 Data Stok EDC </h4>
            </div>
            <div class="card-body">
                <div class="stat-box">
                    <div class="stat-item">
                        <h5>📗 Jumlah Tersedia</h5>
                        <p class="status-Tersedia"><?= $qTersedia ?></p>
                    </div>
                    <div class="stat-item">
                        <h5>📘 Jumlah Terpakai</h5>
                        <p class="status-Terpakai"><?= $qTerpakai ?></p>
                    </div>
                </div>

                <div class="filter-bar">
                    <label for="filterBranch">🏢 Filter Branch Office:</label>
                    <select id="filterBranch" class="form-select form-select-sm" style="max-width:250px;">
                        <option value="">-- Semua Branch --</option>
                        <?php foreach ($branchList as $b): ?>
                            <option value="<?= htmlspecialchars($b) ?>"><?= htmlspecialchars($b) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="filterType" class="fw-bold ms-md-3">🔍 Filter Type Mesin:</label>
                    <select id="filterType" class="form-select form-select-sm" style="max-width:250px;">
                        <option value="">-- Semua Type --</option>
                        <?php foreach ($typeList as $t): ?>
                            <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="table-responsive">
                    <table id="stokTable" class="table table-striped table-bordered align-middle">
                        <thead>
                            <tr class="text-center">
                                <th>No</th>
                                <th>SN Mesin</th>
                                <th>Branch Code</th>
                                <th>Branch Office</th>
                                <th>Type Mesin</th>
                                <th>SN Simcard</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()):
                                $statusText = trim($row['status']) === '' ? 'Tersedia' : htmlspecialchars($row['status']);
                                $statusClass = 'status-' . str_replace(' ', '', $statusText);
                            ?>
                                <tr>
                                    <td class="text-center"><?= $row['no'] ?></td>
                                    <td><?= htmlspecialchars($row['sn_mesin']) ?></td>
                                    <td><?= htmlspecialchars($row['branch_code']) ?></td>
                                    <td><?= htmlspecialchars($row['branch_office']) ?></td>
                                    <td><?= htmlspecialchars($row['type_mesin']) ?></td>
                                    <td><?= htmlspecialchars($row['sn_simcard']) ?></td>
                                    <td class="text-center"><span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            var table = $('#stokTable').DataTable({
                "pageLength": 15
            });
            $('#filterType').on('change', function() {
                table.column(4).search($(this).val()).draw();
            });
            $('#filterBranch').on('change', function() {
                table.column(3).search($(this).val()).draw();
            });
        });

        // Sidebar toggle & submenu
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
                if (arrow) {
                    arrow.classList.replace('fa-angle-left', 'fa-angle-down');
                }
            }
        });
    </script>
</body>

</html>