<?php
// ==============================================
// WPE ADMIN - GABUNG DENGAN SIDEBAR
// ==============================================

error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

ini_set('memory_limit', '2048M');
ini_set('max_execution_time', '600');
ini_set('upload_max_filesize', '256M');
ini_set('post_max_size', '256M');
ini_set('max_input_time', '600');

// ====== KONEKSI DATABASE ======
$host = "localhost";
$user = "root";
$pass = "";
$db   = "edc_login_db2";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// ====== IMPORT EXCEL HANDLING ======
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$batchSize = 200;
$expectedColumns = 33;

if (isset($_POST['submit'])) {
    if (empty($_FILES['excel']['tmp_name'])) {
        $error = "⚠️ Pilih file Excel terlebih dahulu.";
    } else {
        $filePath = $_FILES['excel']['tmp_name'];

        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            $conn->begin_transaction();
            $conn->query("DELETE FROM wpe");
            $conn->commit();

            $inserted = 0;
            $batchValues = [];

            $columnsList = "MID, TID, Nama_Merchant, Peruntukkan, Jenis, Kanwil, 
                Domisili_Mainbranch, Branch_Domisili, Kanwil_Norek, Mainbranch_Norek, Branch_Norek, Norek,
                Ratas_Saldo, Kanwil_Nama_Pemrakarsa, Mainbranch_Nama_Pemrakarsa, Branch_Nama_Pemrakarsa, User_Pemrakarsa,
                Vendor, Last_Availability, Last_Utility, Last_Transactional, Longitude, Latitude, Alamat_Merchant,
                Kelurahan, Kecamatan, Kabupaten, Provinsi, Kodepos, PIC, Telp, Tanggal_Pasang, Tanggal_Input";

            $updateParts = [];
            $cols = array_map('trim', explode(',', $columnsList));
            foreach ($cols as $c) {
                if (in_array($c, ['MID', 'TID'])) continue;
                $updateParts[] = "$c = VALUES($c)";
            }
            $onDup = implode(', ', $updateParts);

            $numericRows = [];
            foreach ($rows as $rAssoc) {
                $tmp = [];
                foreach ($rAssoc as $cell) $tmp[] = $cell;
                $numericRows[] = $tmp;
            }

            for ($i = 1; $i < count($numericRows); $i++) {
                $r = $numericRows[$i];

                $isAllEmpty = true;
                for ($k = 0; $k < min(2, count($r)); $k++) {
                    if (isset($r[$k]) && trim($r[$k]) !== '') {
                        $isAllEmpty = false;
                        break;
                    }
                }
                if ($isAllEmpty) continue;

                for ($k = 0; $k < $expectedColumns; $k++) {
                    if (!isset($r[$k])) $r[$k] = '';
                }

                $valsEscaped = [];
                for ($c = 0; $c < $expectedColumns; $c++) {
                    $val = trim($r[$c]);

                    // DATETIME: Last_Availability, Last_Utility, Last_Transactional
                    if (in_array($c, [18, 19, 20])) {
                        if ($val === '') {
                            $valsEscaped[] = "NULL";
                        } else {
                            $ts = strtotime($val);
                            if ($ts === false && is_numeric($val)) {
                                $unix = ($val - 25569) * 86400;
                                $dateStr = gmdate('Y-m-d H:i:s', (int)$unix);
                                $valsEscaped[] = "'" . mysqli_real_escape_string($conn, $dateStr) . "'";
                            } elseif ($ts === false) {
                                $valsEscaped[] = "NULL";
                            } else {
                                $dateStr = date('Y-m-d H:i:s', $ts);
                                $valsEscaped[] = "'" . mysqli_real_escape_string($conn, $dateStr) . "'";
                            }
                        }
                    } elseif ($c === 32) { // Tanggal_Input
                        $valsEscaped[] = "NOW()";
                    } elseif ($c === 31) { // Tanggal_Pasang
                        if ($val === '') {
                            $valsEscaped[] = "NULL";
                        } else {
                            $ts = strtotime($val);
                            if ($ts === false && is_numeric($val)) {
                                $unix = ($val - 25569) * 86400;
                                $dateOnly = gmdate('Y-m-d', (int)$unix);
                                $valsEscaped[] = "'" . mysqli_real_escape_string($conn, $dateOnly) . "'";
                            } elseif ($ts === false) {
                                $valsEscaped[] = "NULL";
                            } else {
                                $dateOnly = date('Y-m-d', $ts);
                                $valsEscaped[] = "'" . mysqli_real_escape_string($conn, $dateOnly) . "'";
                            }
                        }
                    } else {
                        if ($val === '') {
                            $valsEscaped[] = "NULL";
                        } else {
                            $valsEscaped[] = "'" . mysqli_real_escape_string($conn, $val) . "'";
                        }
                    }
                }

                $batchValues[] = "(" . implode(",", $valsEscaped) . ")";
                if (count($batchValues) >= $batchSize) {
                    $sql = "INSERT INTO wpe ($columnsList) VALUES " . implode(",", $batchValues)
                        . " ON DUPLICATE KEY UPDATE $onDup";
                    $conn->query($sql);
                    $inserted += count($batchValues);
                    $batchValues = [];
                }
            }

            if (!empty($batchValues)) {
                $sql = "INSERT INTO wpe ($columnsList) VALUES " . implode(",", $batchValues)
                    . " ON DUPLICATE KEY UPDATE $onDup";
                $conn->query($sql);
                $inserted += count($batchValues);
            }

            $countRes = $conn->query("SELECT COUNT(*) AS total FROM wpe");
            $total = $countRes->fetch_assoc()['total'];
            $success = "✅ Import selesai: $inserted baris diproses. Total baris dalam tabel sekarang: $total.";
        } catch (Exception $e) {
            if ($conn->in_transaction) $conn->rollback();
            $error = "❌ Terjadi kesalahan saat import: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>WPE Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f7f9fa;
        }

        /* Sidebar (dari sidebar.php) */
        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            background: linear-gradient(180deg, #2c3e50, #1c2833);
            transition: all 0.3s ease;
            overflow-y: auto;
            padding-top: 10px;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.15);
            z-index: 1000;
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar h2 {
            color: #fff;
            text-align: center;
            margin: 20px 0;
            font-size: 1.5em;
            font-weight: 600;
        }

        .sidebar a,
        .sidebar .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #ecf0f1;
            text-decoration: none;
            font-size: 15px;
            transition: all 0.25s ease;
            border-radius: 6px;
            margin: 3px 8px;
            white-space: nowrap;
        }

        .sidebar a:hover,
        .sidebar .nav-link.active {
            background: #1abc9c;
            color: #fff;
            transform: translateX(4px);
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
            padding-left: 25px;
            list-style: none;
        }

        .submenu.show {
            max-height: 500px;
        }

        .submenu a {
            font-size: 14px;
            padding: 8px 20px;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .main-content.collapsed {
            margin-left: 70px;
        }

        .logout-btn {
            background: #e74c3c;
            color: #fff;
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            width: calc(100% - 20px);
            margin: 15px auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logout-btn:hover {
            background: #c0392b;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                height: 100%;
                width: 220px;
                left: -220px;
            }

            .sidebar.show {
                left: 0;
            }

            .main-content {
                margin-left: 0;
            }

            .toggle-btn {
                display: inline-block;
                font-size: 22px;
                color: #2c3e50;
                cursor: pointer;
                margin-bottom: 15px;
            }
        }
    </style>
</head>

<body>


    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <h2>Admin</h2>

        <a href="#" onclick="toggleSidebar()" class="d-md-none">
            <i class="fas fa-bars"></i> <span class="text">Menu</span>
        </a>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="dashboard_admin.php" class="nav-link active">
                    <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
                </a>
            </li>

            <!-- STOK -->
            <li class="nav-item">
                <a href="#" class="nav-link" data-toggle="submenu" data-target="stokMenu" data-arrow="stokArrow">
                    <i class="fas fa-box-open"></i><span>Stok</span>
                    <i class="fas fa-angle-left right ms-auto" id="stokArrow"></i>
                </a>
                <ul class="submenu" id="stokMenu">
                    <li><a href="edc_stok.php" class="nav-link">EDC</a></li>
                    <li><a href="#" class="nav-link">TERMAL</a></li>
                    <li><a href="upload_mesin.php" class="nav-link">Peta EDC</a></li>
                </ul>
            </li>

            <!-- UKO -->
            <li class="nav-item">
                <a href="#" class="nav-link" data-toggle="submenu" data-target="ukoMenu" data-arrow="ukoArrow">
                    <i class="fas fa-building"></i><span>UKO</span>
                    <i class="fas fa-angle-left right ms-auto" id="ukoArrow"></i>
                </a>
                <ul class="submenu" id="ukoMenu">

                    <li><a href="produk_mpos.php" class="nav-link">Produktifitas</a></li>
                    <li><a href="detaul_uko_admin.php" class="nav-link">Input Detail UKO</a></li>
                    <li><a href="#" class="nav-link">NOP Berulang</a></li>
                </ul>
            </li>

            <!-- Merchant -->
            <li class="nav-item">
                <a href="#" class="nav-link" data-toggle="submenu" data-target="merchantMenu" data-arrow="merchantArrow">
                    <i class="fas fa-store"></i><span>Merchant</span>
                    <i class="fas fa-angle-left right ms-auto" id="merchantArrow"></i>
                </a>
                <ul class="submenu" id="merchantMenu">
                    <li><a href="wpe_admin.php" class="nav-link">Reliability</a></li>
                    <li><a href="#" class="nav-link">Produktifitas</a></li>
                    <li><a href="#" class="nav-link">NOP Berulang</a></li>
                </ul>
            </li>

            <!-- BRILink -->
            <li class="nav-item">
                <a href="#" class="nav-link" data-toggle="submenu" data-target="brilinkMenu" data-arrow="brilinkArrow">
                    <i class="fas fa-link"></i><span>BRILink</span>
                    <i class="fas fa-angle-left right ms-auto" id="brilinkArrow"></i>
                </a>
                <ul class="submenu" id="brilinkMenu">
                    <li><a href="produk_bri_admin.php" class="nav-link">Produktifitas</a></li>
                    <li><a href="mpos_admin.php" class="nav-link">Pengajuan MPOS</a></li>
                    <li><a href="#" class="nav-link">NOP Berulang</a></li>
                </ul>
            </li>

            <!-- Logout -->
            <li class="nav-item mt-3">
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="main">
        <span class="toggle-btn d-md-none" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </span>

        <div class="container py-4">
            <div class="card p-4 shadow-sm">
                <h1 class="mb-3 text-center">WPE Import</h1>

                <?php if (isset($error)) : ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if (isset($success)) : ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pilih file Excel (.xlsx / .xls)</label>
                        <input class="form-control" type="file" name="excel" accept=".xlsx,.xls" required>
                        <div class="form-text">Pastikan urutan kolom sesuai template. Baris pertama akan di-skip (header).</div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="submit" class="btn btn-primary btn-lg">🚀 Upload & Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById("sidebar");
            const main = document.getElementById("main");
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle("show");
            } else {
                sidebar.classList.toggle("collapsed");
                main.classList.toggle("collapsed");
            }
        }
        document.querySelectorAll("[data-toggle='submenu']").forEach(toggle => {
            toggle.addEventListener("click", function(e) {
                e.preventDefault();
                let targetId = this.getAttribute("data-target");
                let arrowId = this.getAttribute("data-arrow");
                let targetMenu = document.getElementById(targetId);
                let arrow = document.getElementById(arrowId);
                document.querySelectorAll(".submenu").forEach(menu => {
                    if (menu.id !== targetId) menu.classList.remove("show");
                });
                document.querySelectorAll(".right").forEach(icon => {
                    if (icon.id !== arrowId) icon.classList.replace("fa-angle-down", "fa-angle-left");
                });
                targetMenu.classList.toggle("show");
                arrow.classList.toggle("fa-angle-down");
                arrow.classList.toggle("fa-angle-left");
            });
        });
    </script>

</body>

</html>