<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['level'] != 'user') {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "edc_login_db2");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Pagination setup
$rowsOptions = [10, 25, 50, 100];
$rowsPerPage = isset($_GET['rows']) && in_array((int)$_GET['rows'], $rowsOptions) ? (int)$_GET['rows'] : 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$searchQuery = $_GET['search'] ?? '';
$offset = ($page - 1) * $rowsPerPage;

// Fetch total data with search
$totalSql = "SELECT COUNT(*) as total FROM edc_rusak 
             WHERE SN LIKE ? OR MEREK LIKE ? OR SPK LIKE ?";
$stmtTotal = $conn->prepare($totalSql);
$likeSearch = "%$searchQuery%";
$stmtTotal->bind_param("sss", $likeSearch, $likeSearch, $likeSearch);
$stmtTotal->execute();
$totalResult = $stmtTotal->get_result()->fetch_assoc();
$totalEDC = $totalResult['total'];
$totalPages = ceil($totalEDC / $rowsPerPage);

// Fetch data with search + pagination
$dataSql = "SELECT * FROM edc_rusak 
            WHERE SN LIKE ? OR MEREK LIKE ? OR SPK LIKE ? 
            ORDER BY id ASC LIMIT ?,?";
$stmtData = $conn->prepare($dataSql);
$stmtData->bind_param("sssii", $likeSearch, $likeSearch, $likeSearch, $offset, $rowsPerPage);
$stmtData->execute();
$result = $stmtData->get_result();

// Current page for sidebar active menu
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>EDC Rusak</title>
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

        .card-total {
            margin-bottom: 20px;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .table-header .search-box {
            max-width: 300px;
            margin-left: auto;
        }

        .pagination li.disabled .page-link {
            pointer-events: none;
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

    <!-- Konten -->
    <div class="content">
        <h2>EDC Rusak</h2>
        <div class="card card-total bg-info text-white p-3 mb-3">
            <h4>Total Semua Data: <?= $totalEDC ?></h4>
        </div>

        <!-- Pilih jumlah baris & Search -->
        <div class="table-header">
            <form method="get" class="d-flex align-items-center gap-2">
                <label>Show :</label>
                <select name="rows" class="form-select" style="width:100px;" onchange="this.form.submit()">
                    <?php foreach ($rowsOptions as $opt): ?>
                        <option value="<?= $opt ?>" <?= $rowsPerPage == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="page" value="1">
                <input type="hidden" name="search" value="<?= htmlspecialchars($searchQuery) ?>">
            </form>
            <div class="search-box">
                <input type="text" id="searchInput" class="form-control" placeholder="Search SN / MEREK / SPK..." value="<?= htmlspecialchars($searchQuery) ?>">
            </div>
        </div>

        <div id="tableContainer">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>No</th>
                        <th>SN</th>
                        <th>MEREK</th>
                        <th>SPK</th>
                        <th>RO</th>
                        <th>KELENGKAPAN</th>
                        <th>KETERANGAN</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = $offset + 1;
                    while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td><?= $row['SN'] ?></td>
                            <td><?= $row['MEREK'] ?></td>
                            <td><?= $row['SPK'] ?></td>
                            <td><?= $row['RO'] ?></td>
                            <td><?= $row['KELENGKAPAN'] ?></td>
                            <td><?= $row['KETERANGAN'] ?></td>
                        </tr>
                    <?php $i++;
                    endwhile; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="7" class="text-end"><strong>Total Data: <?= $totalEDC ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Pagination -->
        <nav>
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&rows=<?= $rowsPerPage ?>&search=<?= urlencode($searchQuery) ?>">Previous</a>
                </li>
                <?php
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);

                if ($start > 1) {
                    echo '<li class="page-item"><a class="page-link" href="?page=1&rows=' . $rowsPerPage . '&search=' . urlencode($searchQuery) . '">1</a></li>';
                    if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                }

                for ($p = $start; $p <= $end; $p++) {
                    echo '<li class="page-item ' . ($p == $page ? 'active' : '') . '"><a class="page-link" href="?page=' . $p . '&rows=' . $rowsPerPage . '&search=' . urlencode($searchQuery) . '">' . $p . '</a></li>';
                }

                if ($end < $totalPages) {
                    if ($end < $totalPages - 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                    echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '&rows=' . $rowsPerPage . '&search=' . urlencode($searchQuery) . '">' . $totalPages . '</a></li>';
                }
                ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&rows=<?= $rowsPerPage ?>&search=<?= urlencode($searchQuery) ?>">Next</a>
                </li>
            </ul>
        </nav>
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

        // Sidebar collapse toggle
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

        // Live search
        const searchInput = document.getElementById('searchInput');
        const tableContainer = document.getElementById('tableContainer');

        searchInput.addEventListener('keyup', function() {
            const query = this.value;

            fetch('?search=' + encodeURIComponent(query) + '&rows=<?= $rowsPerPage ?>&page=1')
                .then(res => res.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newTable = doc.getElementById('tableContainer').innerHTML;
                    tableContainer.innerHTML = newTable;
                });
        });
    </script>
</body>

</html>