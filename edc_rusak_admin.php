<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['level'] != 'admin') {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "edc_login_db2");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

require 'vendor/autoload.php'; // PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\IOFactory;

// Fungsi generate SN
function generateSN($length = 5)
{
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[rand(0, strlen($chars) - 1)];
    }
    return 'EDC' . $str;
}

// Filter MEREK
$filterMerek = isset($_GET['merek']) ? trim($_GET['merek']) : '';
$where = $filterMerek ? "AND MEREK='" . $conn->real_escape_string($filterMerek) . "'" : '';

// Filter Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchSQL = $search ? "AND (SN LIKE '%" . $conn->real_escape_string($search) . "%' 
                         OR MEREK LIKE '%" . $conn->real_escape_string($search) . "%' 
                         OR SPK LIKE '%" . $conn->real_escape_string($search) . "%' 
                         OR RO LIKE '%" . $conn->real_escape_string($search) . "%' 
                         OR KELENGKAPAN LIKE '%" . $conn->real_escape_string($search) . "%' 
                         OR KETERANGAN LIKE '%" . $conn->real_escape_string($search) . "%')" : '';

// Jumlah baris
$rowsPerPage = isset($_GET['rows']) ? intval($_GET['rows']) : 10;

// Ambil data untuk tabel di halaman (pakai limit)
$result = $conn->query("SELECT * FROM edc_rusak WHERE 1 $where $searchSQL ORDER BY id ASC LIMIT $rowsPerPage");

// Total data
$totalResult = $conn->query("SELECT COUNT(*) as total FROM edc_rusak WHERE 1 $where $searchSQL");
$totalRow = $totalResult->fetch_assoc();
$totalEDC = $totalRow['total'];

// Daftar MEREK unik
$merekResult = $conn->query("SELECT DISTINCT MEREK FROM edc_rusak ORDER BY MEREK ASC");
$mereks = [];
while ($row = $merekResult->fetch_assoc()) {
    $mereks[] = $row['MEREK'];
}

// Handle Add Data
if (isset($_POST['add'])) {
    $SN = trim($_POST['SN']);
    if (!$SN) {
        $SN = generateSN();
    }
    $MEREK = $conn->real_escape_string($_POST['MEREK']);
    $SPK = $conn->real_escape_string($_POST['SPK']);
    $RO = $conn->real_escape_string($_POST['RO']);
    $KELENGKAPAN = $conn->real_escape_string($_POST['KELENGKAPAN']);
    $KETERANGAN = $conn->real_escape_string($_POST['KETERANGAN']);
    $conn->query("INSERT INTO edc_rusak (SN,MEREK,SPK,RO,KELENGKAPAN,KETERANGAN)
                  VALUES ('$SN','$MEREK','$SPK','$RO','$KELENGKAPAN','$KETERANGAN')");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM edc_rusak WHERE id=$id");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Import Excel
if (isset($_POST['import'])) {
    if ($_FILES['file_excel']['name']) {
        $fileName = $_FILES['file_excel']['tmp_name'];
        $spreadsheet = IOFactory::load($fileName);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();
        for ($i = 1; $i < count($sheetData); $i++) {
            $row = $sheetData[$i];
            $SN = trim($row[0]);
            if (!$SN) {
                $SN = generateSN();
            }
            $MEREK = $conn->real_escape_string($row[1]);
            $SPK = $conn->real_escape_string($row[2]);
            $RO = $conn->real_escape_string($row[3]);
            $KELENGKAPAN = $conn->real_escape_string($row[4]);
            $KETERANGAN = $conn->real_escape_string($row[5]);
            $conn->query("INSERT INTO edc_rusak (SN,MEREK,SPK,RO,KELENGKAPAN,KETERANGAN)
                          VALUES ('$SN','$MEREK','$SPK','$RO','$KELENGKAPAN','$KETERANGAN')");
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Download Excel (hapus LIMIT $rowsPerPage agar semua data ikut)
if (isset($_GET['download']) && $_GET['download'] == 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=edc_rusak_data.xls");
    echo "No\tSN\tMEREK\tSPK\tRO\tKELENGKAPAN\tKETERANGAN\n";

    $res = $conn->query("SELECT * FROM edc_rusak WHERE 1 $where $searchSQL ORDER BY id ASC"); // LIMIT dihapus
    $i = 1;
    while ($row = $res->fetch_assoc()) {
        echo $i . "\t" . $row['SN'] . "\t" . $row['MEREK'] . "\t" . $row['SPK'] . "\t" . $row['RO'] . "\t" . $row['KELENGKAPAN'] . "\t" . $row['KETERANGAN'] . "\n";
        $i++;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>EDC Rusak Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f4f4;
        }

        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            background: #2c3e50;
            transition: 0.3s;
            overflow-y: auto;
            padding-top: 10px;
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar h2 {
            color: #fff;
            text-align: center;
            margin: 20px 0;
            font-size: 1.4em;
        }

        .sidebar a,
        .sidebar .nav-link {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            white-space: nowrap;
        }

        .sidebar a:hover,
        .sidebar .nav-link.active {
            background: #1abc9c;
        }

        .sidebar a i,
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

        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: 0.3s;
        }

        .main-content.collapsed {
            margin-left: 70px;
        }

        .logout-btn {
            background: #e74c3c;
            color: #fff;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
        }

        .logout-btn:hover {
            background: #c0392b;
        }

        .table th,
        .table td {
            text-align: center;
        }

        .card-total {
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <h2>Admin</h2>
        <a href="#" onclick="toggleSidebar()"><i class="fas fa-bars"></i> <span class="text">Menu</span></a>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="dashboard_admin.php" class="nav-link"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link"><i class="fas fa-users"></i><span>Users</span></a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link d-flex justify-content-between align-items-center" data-toggle="submenu" data-target="stokMenu" data-arrow="stokArrow">
                    <i class="fas fa-box-open"></i><span>Stok</span><i class="fas fa-angle-left right" id="stokArrow"></i>
                </a>
                <ul class="submenu flex-column" id="stokMenu">
                    <li><a href="edc_admin.php" class="nav-link">EDC</a></li>
                    <li><a href="#" class="nav-link">TERMAL</a></li>
                </ul>
            </li>
            <li class="nav-item">
                <a href="edc_rusak_admin.php" class="nav-link active">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>EDC RUSAK</span>
                </a>
            </li>
            <li class="nav-item mt-3">
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="main">
        <h2>EDC Rusak Admin</h2>

        <div class="card card-total bg-info text-white p-3">
            <h4>Total Semua Data: <?= $totalEDC ?></h4>
        </div>

        <!-- Filter & Search Form -->
        <form method="get" class="mb-3 row g-2 align-items-center">
            <div class="col-md-3">
                <select name="merek" class="form-select">
                    <option value="">-- Filter MEREK --</option>
                    <?php foreach ($mereks as $m): ?>
                        <option value="<?= htmlspecialchars($m) ?>" <?= $filterMerek == $m ? 'selected' : '' ?>><?= htmlspecialchars($m) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Go</button>
            </div>
            <input type="hidden" name="rows" value="<?= $rowsPerPage ?>">
        </form>

        <!-- Form Add -->
        <form method="post" class="mb-3 row g-2">
            <div class="col-md-2"><input type="text" name="SN" class="form-control" placeholder="SN (kosong untuk generate otomatis)"></div>
            <div class="col-md-2"><input type="text" name="MEREK" class="form-control" placeholder="MEREK" required></div>
            <div class="col-md-1"><input type="text" name="SPK" class="form-control" placeholder="SPK"></div>
            <div class="col-md-1"><input type="text" name="RO" class="form-control" placeholder="RO"></div>
            <div class="col-md-3"><input type="text" name="KELENGKAPAN" class="form-control" placeholder="KELENGKAPAN"></div>
            <div class="col-md-2"><input type="text" name="KETERANGAN" class="form-control" placeholder="KETERANGAN"></div>
            <div class="col-md-1">
                <button type="submit" name="add" class="btn btn-primary w-100"><i class="fas fa-plus"></i></button>
            </div>
        </form>

        <!-- Import Excel -->
        <form method="post" enctype="multipart/form-data" class="mb-3 row g-2 align-items-center">
            <div class="col-md-3"><input type="file" name="file_excel" class="form-control" accept=".xlsx,.xls" required></div>
            <div class="col-md-1"><button type="submit" name="import" class="btn btn-success"><i class="fas fa-file-import"></i> Import</button></div>
        </form>

        <a href="?download=excel<?= $filterMerek ? '&merek=' . urlencode($filterMerek) : '' ?>&search=<?= urlencode($search) ?>&rows=<?= $rowsPerPage ?>" class="btn btn-success mb-3"><i class="fas fa-download"></i> Download Excel</a>

        <!-- Tabel -->
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
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1;
                while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i ?></td>
                        <td><?= $row['SN'] ?></td>
                        <td><?= $row['MEREK'] ?></td>
                        <td><?= $row['SPK'] ?></td>
                        <td><?= $row['RO'] ?></td>
                        <td><?= $row['KELENGKAPAN'] ?></td>
                        <td><?= $row['KETERANGAN'] ?></td>
                        <td>
                            <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus?')"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                <?php $i++;
                endwhile; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="8" class="text-end"><strong>Total Data: <?= $totalEDC ?></strong></td>
                </tr>
            </tfoot>
        </table>

        <!-- Pilihan jumlah baris -->
        <form method="get" class="mb-3 d-flex align-items-center">
            <label for="rows" class="me-2">Tampilkan:</label>
            <select name="rows" id="rows" class="form-select w-auto me-2" onchange="this.form.submit()">
                <option value="5" <?= ($rowsPerPage == 5) ? 'selected' : '' ?>>5</option>
                <option value="10" <?= ($rowsPerPage == 10) ? 'selected' : '' ?>>10</option>
                <option value="25" <?= ($rowsPerPage == 25) ? 'selected' : '' ?>>25</option>
                <option value="50" <?= ($rowsPerPage == 50) ? 'selected' : '' ?>>50</option>
                <option value="100" <?= ($rowsPerPage == 100) ? 'selected' : '' ?>>100</option>
            </select>
            <input type="hidden" name="merek" value="<?= htmlspecialchars($filterMerek) ?>">
            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
        </form>
    </div>

    <script>
        // Sidebar toggle
        function toggleSidebar() {
            document.getElementById("sidebar").classList.toggle("collapsed");
            document.getElementById("main").classList.toggle("collapsed");
        }

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
    </script>
</body>

</html>