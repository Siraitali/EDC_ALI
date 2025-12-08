<?php
// ================= SESSION & LOGIN =================
session_start();
if (!isset($_SESSION['login']) || $_SESSION['level'] != 'user') {
    header("Location: login.php");
    exit;
}

// ================= DATABASE DETAILBRI =================
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "edc_login_db2";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

$kanca = $_REQUEST['kanca'] ?? '';
$kolom  = $_REQUEST['kolom'] ?? '';
if (!$kanca || !$kolom) die("Parameter tidak lengkap.");

// Mapping kolom ke kondisi SQL
$where = "kanca_name='" . $conn->real_escape_string($kanca) . "'";
switch ($kolom) {
    case 'belum_aktivasi':
        $where .= " AND (last_login='0000-00-00 00:00:00' OR last_login='-')";
        break;
    case 'today':
        $where .= " AND last_login!='0000-00-00 00:00:00' AND last_login!='-' AND DATEDIFF(NOW(),last_login)=0";
        break;
    case 'day_01_07':
        $where .= " AND last_login!='0000-00-00 00:00:00' AND DATEDIFF(NOW(),last_login) BETWEEN 1 AND 7";
        break;
    case 'day_08_15':
        $where .= " AND last_login!='0000-00-00 00:00:00' AND DATEDIFF(NOW(),last_login) BETWEEN 8 AND 15";
        break;
    case 'day_16_30':
        $where .= " AND last_login!='0000-00-00 00:00:00' AND DATEDIFF(NOW(),last_login) BETWEEN 16 AND 30";
        break;
    case 'day_more_30':
        $where .= " AND last_login!='0000-00-00 00:00:00' AND DATEDIFF(NOW(),last_login) > 30";
        break;
    case 'telah_aktivasi':
        $where .= " AND last_login!='0000-00-00 00:00:00' AND last_login!='-'";
        break;
}

// ================= EXPORT EXCEL =================
if (isset($_POST['export'])) {
    $query = "SELECT brilink_web_code, outlet_code, outlet_name, jenis, region_name, kanca_name, branch_name, last_login, merek, f1 FROM detailbri WHERE $where ORDER BY last_login DESC";
    $result = $conn->query($query);

    $filename = "Detail_Kanca_" . preg_replace('/\s+/', '_', $kanca) . "_" . date('Ymd_His') . ".xls";
    $exportDir = __DIR__ . '/exports';
    if (!is_dir($exportDir)) mkdir($exportDir, 0777, true);

    ob_start();
    echo "<table border='1'><tr>
    <th>No</th><th>Merchant / MID</th><th>Kode Outlet</th><th>Nama Outlet</th>
    <th>Jenis</th><th>Kanwil</th><th>Kanca</th><th>Uker</th>
    <th>Last Login</th><th>Merek</th><th>Versi</th>
    </tr>";

    if ($result && $result->num_rows > 0) {
        $no = 1;
        while ($r = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $no++ . "</td>";
            echo "<td>" . $r['brilink_web_code'] . "</td>";
            echo "<td>" . htmlspecialchars($r['outlet_code']) . "</td>";
            echo "<td>" . $r['outlet_name'] . "</td>";
            echo "<td>" . $r['jenis'] . "</td>";
            echo "<td>" . $r['region_name'] . "</td>";
            echo "<td>" . $r['kanca_name'] . "</td>";
            echo "<td>" . $r['branch_name'] . "</td>";
            echo "<td>" . $r['last_login'] . "</td>";
            echo "<td>" . $r['merek'] . "</td>";
            echo "<td>" . $r['f1'] . "</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    $content = ob_get_clean();
    file_put_contents($exportDir . '/' . $filename, $content);

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    echo $content;
    exit;
}

// ================= PAGINATION =================
$limit = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Hitung total data
$totalQuery = "SELECT COUNT(*) AS total FROM detailbri WHERE $where";
$totalResult = $conn->query($totalQuery);
$totalData = $totalResult->fetch_assoc()['total'];

// Ambil data untuk tabel
$query = "SELECT * FROM detailbri WHERE $where ORDER BY last_login DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($query);
$totalPages = ceil($totalData / $limit);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Kanca <?= htmlspecialchars($kanca); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        /* SIDEBAR & TOPBAR */
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
            transition: width 0.4s ease;
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

        /* DETAILBRI TABLE */
        .table-responsive {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        table {
            border-radius: 10px;
            border-collapse: separate;
            border-spacing: 0;
        }

        thead th {
            position: sticky;
            top: 0;
            background-color: #0d6efd !important;
            color: white !important;
            font-weight: 700;
            z-index: 2;
            text-align: center;
            padding: 8px 12px;
            border-bottom: 2px solid #fff;
        }

        tbody tr:nth-child(even) {
            background: #f2f6ff;
        }

        tbody tr:hover {
            background: #d9eaff;
        }

        th,
        td {
            vertical-align: middle;
            text-align: center;
            padding: 8px 12px;
        }

        .sticky-col {
            position: sticky;
            left: 0;
            background: #fff;
            z-index: 3;
        }

        .pagination li a {
            color: #0d6efd;
        }

        #searchInput {
            max-width: 250px;
        }

        .totalData {
            font-weight: 600;
            color: #0d6efd;
        }

        .btn-sm {
            font-size: 0.8rem;
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
        <div></div>
        <div>
            <span>👤 <?= $_SESSION['username']; ?></span>
            <a href="logout.php" title="Logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- CONTENT DETAILBRI -->
    <div class="content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                <h4 class="text-primary mb-0">Detail Kanca: <?= htmlspecialchars($kanca); ?> (<?= htmlspecialchars($kolom); ?>)</h4>
                <form method="post" style="margin:0;">
                    <input type="hidden" name="kanca" value="<?= htmlspecialchars($kanca); ?>">
                    <input type="hidden" name="kolom" value="<?= htmlspecialchars($kolom); ?>">
                    <button type="submit" name="export" class="btn btn-success btn-sm">Export Excel</button>
                </form>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <div>
                    <a href="relia_bri.php" class="btn btn-sm btn-primary mb-1">← Kembali</a>
                    <span class="totalData ms-2">Total Data: <strong><?= number_format($totalData); ?></strong></span>
                </div>
                <input type="text" id="searchInput" class="form-control" placeholder="Cari Merchant / Nama Outlet...">
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm" id="detailTable">
                    <thead>
                        <tr>
                            <th class="sticky-col">No</th>
                            <th>Merchant / MID</th>
                            <th>Kode Outlet</th>
                            <th>Nama Outlet</th>
                            <th>Jenis</th>
                            <th>Kanwil</th>
                            <th>Kanca</th>
                            <th>Uker</th>
                            <th>Last Login</th>
                            <th>Merek</th>
                            <th>Versi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result && $result->num_rows > 0) {
                            $no = $offset + 1;
                            while ($r = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td class='sticky-col'>" . $no++ . "</td>";
                                echo "<td>" . htmlspecialchars($r['brilink_web_code']) . "</td>";
                                echo "<td><a href='detail_produktivitas.php?outlet_code=" . urlencode($r['outlet_code']) . "' class='text-decoration-none'>" . htmlspecialchars($r['outlet_code']) . "</a></td>";
                                echo "<td>" . htmlspecialchars($r['outlet_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($r['jenis']) . "</td>";
                                echo "<td>" . htmlspecialchars($r['region_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($r['kanca_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($r['branch_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($r['last_login']) . "</td>";
                                echo "<td>" . htmlspecialchars($r['merek']) . "</td>";
                                echo "<td>" . htmlspecialchars($r['f1']) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo '<tr><td colspan="11" class="text-center text-muted">Tidak ada data ditemukan.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <nav>
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item"><a class="page-link" href="?kanca=<?= urlencode($kanca); ?>&kolom=<?= $kolom; ?>&page=<?= $page - 1; ?>">Prev</a></li>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : ''; ?>"><a class="page-link" href="?kanca=<?= urlencode($kanca); ?>&kolom=<?= $kolom; ?>&page=<?= $i; ?>"><?= $i; ?></a></li>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item"><a class="page-link" href="?kanca=<?= urlencode($kanca); ?>&kolom=<?= $kolom; ?>&page=<?= $page + 1; ?>">Next</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </div>

    <script>
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

        // ========== Auto buka submenu aktif ==========
        document.querySelectorAll('.submenu').forEach(sub => {
            if (sub.querySelector('.active')) {
                sub.classList.add('show');
                const arrow = document.getElementById(sub.id.replace('Menu', 'Arrow'));
                if (arrow) arrow.classList.replace('fa-angle-left', 'fa-angle-down');
            }
        });

        // ========== Search filter ==========
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            document.querySelectorAll('#detailTable tbody tr').forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
            });
        });
    </script>

</body>

</html>