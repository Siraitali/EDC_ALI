<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['level'] != 'user') {
    header("Location: login.php");
    exit;
}

// Database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "edc_login_db2";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

// Ambil filter dari form
$filterStart = $_GET['filterStart'] ?? '';
$filterEnd   = $_GET['filterEnd'] ?? '';

if ($filterStart === '' || $filterEnd === '') {
    $lastDateResult = $conn->query("SELECT MAX(date_input) AS last_date FROM edc_merchant_raw");
    $lastDateRow = $lastDateResult->fetch_assoc();
    $filterStart = $filterStart ?: $lastDateRow['last_date'] ?: date('Y-m-d');
    $filterEnd   = $filterEnd ?: $lastDateRow['last_date'] ?: date('Y-m-d');
}

$sql = "
SELECT 
  TRIM(uker_nama_implementor) AS nama_kanca,
  SUM(CASE WHEN status_available = 1 THEN 1 ELSE 0 END) AS today,
  SUM(CASE WHEN status_available = 2 THEN 1 ELSE 0 END) AS d1_7,
  SUM(CASE WHEN status_available = 3 THEN 1 ELSE 0 END) AS d8_15,
  SUM(CASE WHEN status_available = 4 THEN 1 ELSE 0 END) AS d16_30,
  SUM(CASE WHEN status_available = 5 THEN 1 ELSE 0 END) AS gt30
FROM edc_merchant_raw
WHERE TRIM(uker_nama_implementor) <> ''
  AND date_input BETWEEN '" . $conn->real_escape_string($filterStart) . "' AND '" . $conn->real_escape_string($filterEnd) . "'
GROUP BY TRIM(uker_nama_implementor)
ORDER BY TRIM(uker_nama_implementor)
";
$result = $conn->query($sql);

$rows = [];
if ($result && $result->num_rows > 0) {
    while ($r = $result->fetch_assoc()) {
        $total = $r['today'] + $r['d1_7'] + $r['d8_15'] + $r['d16_30'] + $r['gt30'];
        $relia = $total > 0 ? round((($r['today'] + $r['d1_7']) / $total) * 100, 2) : 0;
        $r['total'] = $total;
        $r['relia'] = $relia;
        $rows[] = $r;
    }
}
$conn->close();

function reliaClass($value)
{
    if ($value >= 98) return 'relia-high';
    elseif ($value >= 90) return 'relia-medium';
    else return 'relia-low';
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Reliability EDC UKO</title>
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
            color: #212529;
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
            background: var(--body-bg);
            border-radius: 8px;
        }

        .table-wrapper {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 1rem;
        }

        thead th {
            background: linear-gradient(90deg, #4a90e2, #007bff);
            color: #fff;
            padding: 12px;
            text-align: center;
            border-bottom: 2px solid #0056b3;
            cursor: pointer;
            user-select: none;
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
        }

        .total-row {
            font-weight: bold;
            background: #dcdcdc;
        }

        .relia-high {
            background: #c6f6d5;
            color: #155724;
            font-weight: bold;
        }

        .relia-medium {
            background: #fff3cd;
            color: #856404;
            font-weight: bold;
        }

        .relia-low {
            background: #f8d7da;
            color: #721c24;
            font-weight: bold;
        }

        #filterForm {
            margin-bottom: 15px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            background: #f1f3f5;
            padding: 10px 15px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        #saveJpgBtn {
            margin-bottom: 15px;
            padding: 8px 12px;
            font-size: 0.95rem;
            cursor: pointer;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 6px;
        }

        #saveJpgBtn:hover {
            background: #0056b3;
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
                    <li><a href="notfound.php" class="nav-link">Produktifitas</a></li>
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

    <!-- Konten -->
    <div class="content" id="captureArea">
        <button id="saveJpgBtn">Save as JPG</button>

        <form id="filterForm" method="get">
            <label for="filterStart">Dari:</label>
            <input type="date" id="filterStart" name="filterStart" value="<?= htmlspecialchars($filterStart) ?>">
            <label for="filterEnd">Sampai:</label>
            <input type="date" id="filterEnd" name="filterEnd" value="<?= htmlspecialchars($filterEnd) ?>">
            <button type="submit">Filter</button>
        </form>

        <h2>RELIABILITY EDC UKO</h2>
        <!-- Tambahan Real-Time Clock -->
        <div id="realTimeClock" style="font-size:1rem; color:#555; margin-bottom:10px;"></div>

        <div class="table-wrapper">
            <table id="reliTable">
                <thead>
                    <thead>
                        <tr>
                            <th onclick="sortTable(0,'str',this)">Nama Kanca</th>
                            <th onclick="sortTable(1,'num',this)">Today</th>
                            <th onclick="sortTable(2,'num',this)">1-7</th>
                            <th onclick="sortTable(3,'num',this)">8-15</th>
                            <th onclick="sortTable(4,'num',this)">16-30</th>
                            <th onclick="sortTable(5,'num',this)">&gt;30</th>
                            <th onclick="sortTable(6,'num',this)">Σ</th>
                            <th onclick="sortTable(7,'num',this)">Relia(%)</th>
                        </tr>
                    </thead>

                </thead>
                <tbody>
                    <?php
                    $tToday = $t17 = $t815 = $t1630 = $tgt30 = $gt = 0;
                    $sumRelia = 0;
                    $cnt = count($rows);
                    if (!empty($rows)):
                        foreach ($rows as $r):
                            $tToday += $r['today'];
                            $t17 += $r['d1_7'];
                            $t815 += $r['d8_15'];
                            $t1630 += $r['d16_30'];
                            $tgt30 += $r['gt30'];
                            $gt += $r['total'];
                            $sumRelia += $r['relia'];
                    ?>
                            <tr>
                                <td><?= htmlspecialchars($r['nama_kanca']) ?></td>
                                <td><?= $r['today'] > 0 ? '<a href="detail.php?kanca=' . urlencode($r['nama_kanca']) . '&kategori=' . urlencode('today') . '&start=' . $filterStart . '&end=' . $filterEnd . '">' . $r['today'] . '</a>' : $r['today'] ?></td>
                                <td><?= $r['d1_7'] > 0 ? '<a href="detail.php?kanca=' . urlencode($r['nama_kanca']) . '&kategori=' . urlencode('1-7') . '&start=' . $filterStart . '&end=' . $filterEnd . '">' . $r['d1_7'] . '</a>' : $r['d1_7'] ?></td>
                                <td><?= $r['d8_15'] > 0 ? '<a href="detail.php?kanca=' . urlencode($r['nama_kanca']) . '&kategori=' . urlencode('8-15') . '&start=' . $filterStart . '&end=' . $filterEnd . '">' . $r['d8_15'] . '</a>' : $r['d8_15'] ?></td>
                                <td><?= $r['d16_30'] > 0 ? '<a href="detail.php?kanca=' . urlencode($r['nama_kanca']) . '&kategori=' . urlencode('16-30') . '&start=' . $filterStart . '&end=' . $filterEnd . '">' . $r['d16_30'] . '</a>' : $r['d16_30'] ?></td>
                                <td><?= $r['gt30'] > 0 ? '<a href="detail.php?kanca=' . urlencode($r['nama_kanca']) . '&kategori=' . urlencode('>30') . '&start=' . $filterStart . '&end=' . $filterEnd . '">' . $r['gt30'] . '</a>' : $r['gt30'] ?></td>
                                <td><?= $r['total'] > 0 ? '<a href="detail.php?kanca=' . urlencode($r['nama_kanca']) . '&kategori=' . urlencode('total') . '&start=' . $filterStart . '&end=' . $filterEnd . '">' . $r['total'] . '</a>' : $r['total'] ?></td>
                                <td class="<?= reliaClass($r['relia']) ?>"><?= $r['relia'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td>Total</td>
                            <td>
                                <a href="detail.php?kanca=All&kategori=today&start=<?= $filterStart ?>&end=<?= $filterEnd ?>">
                                    <?= $tToday ?>
                                </a>
                            </td>
                            <td>
                                <a href="detail.php?kanca=All&kategori=1-7&start=<?= $filterStart ?>&end=<?= $filterEnd ?>">
                                    <?= $t17 ?>
                                </a>
                            </td>
                            <td>
                                <a href="detail.php?kanca=All&kategori=8-15&start=<?= $filterStart ?>&end=<?= $filterEnd ?>">
                                    <?= $t815 ?>
                                </a>
                            </td>
                            <td>
                                <a href="detail.php?kanca=All&kategori=16-30&start=<?= $filterStart ?>&end=<?= $filterEnd ?>">
                                    <?= $t1630 ?>
                                </a>
                            </td>
                            <td>
                                <a href="detail.php?kanca=All&kategori=>30&start=<?= $filterStart ?>&end=<?= $filterEnd ?>">
                                    <?= $tgt30 ?>
                                </a>
                            </td>
                            <td>
                                <a href="detail.php?kanca=All&kategori=total&start=<?= $filterStart ?>&end=<?= $filterEnd ?>">
                                    <?= $gt ?>
                                </a>
                            </td>
                            <td><?= $cnt > 0 ? round($sumRelia / $cnt, 2) : 0 ?></td>
                        </tr>


                        </td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="8">Tidak ada data</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
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

        // ========== Save as JPG ==========
        document.getElementById('saveJpgBtn').addEventListener('click', () => {
            html2canvas(document.querySelector('.table-wrapper')).then(canvas => {
                const link = document.createElement('a');
                link.download = 'reliability_uko.jpg';
                link.href = canvas.toDataURL('image/jpeg', 0.9);
                link.click();
            });
        });

        // ========== Sort table ==========

        function sortTable(colIndex, type, th) {
            const table = document.getElementById("reliTable");
            const tbody = table.tBodies[0];
            const rows = Array.from(tbody.rows).filter(r => !r.classList.contains('total-row'));
            let asc = !th.asc; // toggle sort direction
            th.asc = asc;

            rows.sort((a, b) => {
                let aText = a.cells[colIndex].textContent.trim();
                let bText = b.cells[colIndex].textContent.trim();

                // Hapus % kalau ada (Relia)
                if (type === 'num') {
                    aText = aText.replace('%', '');
                    bText = bText.replace('%', '');
                    return asc ? parseFloat(aText) - parseFloat(bText) : parseFloat(bText) - parseFloat(aText);
                } else { // string
                    return asc ? aText.localeCompare(bText) : bText.localeCompare(aText);
                }
            });

            // Hapus semua row lama (kecuali total-row)
            rows.forEach(r => tbody.appendChild(r));

            // Total-row tetap di bawah
            const totalRow = tbody.querySelector('.total-row');
            if (totalRow) tbody.appendChild(totalRow);
        }


        // ========== Real Time Clock ==========
        function updateClock() {
            const now = new Date();
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            const dateStr = now.toLocaleDateString('id-ID', options);
            const timeStr = now.toLocaleTimeString('id-ID', {
                hour12: false
            });
            document.getElementById('realTimeClock').textContent = dateStr + ' | ' + timeStr;
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>

</body>

</html>