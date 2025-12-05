<?php
// ==========================
// Upload & Import Excel (.xlsx) ke MySQL
// ==========================
require __DIR__ . '/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

// Koneksi ke database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "edc_login_db2";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

$msg = ""; // untuk menampilkan pesan hasil upload

// Jika form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_excel'])) {
    $tmpName = $_FILES['file_excel']['tmp_name'];

    if ($_FILES['file_excel']['error'] !== UPLOAD_ERR_OK) {
        $msg = "<div class='alert alert-danger'>❌ Upload gagal. Kode error: " . $_FILES['file_excel']['error'] . "</div>";
    } else {
        // Load file Excel
        $spreadsheet = IOFactory::load($tmpName);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // Ambil header
        $header = array_map('trim', $rows[0]);

        // Header yang diharapkan
        $expectedHeader = ['Nama Merchant', 'Mainbranch Nama Pemrakarsa', 'Vendor', 'MID', 'TID', 'Longitude', 'Latitude'];

        if ($header !== $expectedHeader) {
            $msg = "<div class='alert alert-danger'>
                ❌ Struktur header tidak sesuai.<br>
                Harus urutannya seperti ini:<br>
                <b>" . implode(' | ', $expectedHeader) . "</b>
            </div>";
        } else {
            $count = 0;

            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $namaMerchant   = trim($row[0]);
                $mainbranch     = trim($row[1]);
                $vendor         = trim($row[2]);
                $mid            = trim($row[3]);
                $tid            = trim($row[4]);
                $longitude      = isset($row[5]) ? floatval($row[5]) : null;
                $latitude       = isset($row[6]) ? floatval($row[6]) : null;

                if (!empty($namaMerchant) && !empty($tid)) {
                    $stmt = $conn->prepare("
                        INSERT INTO mesin_lokasi 
                        (`Nama Merchant`, `Mainbranch Nama Pemrakarsa`, `Vendor`, `MID`, `TID`, `Longitude`, `Latitude`, `Tanggal_Input`)
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->bind_param("ssssddd", $namaMerchant, $mainbranch, $vendor, $mid, $tid, $longitude, $latitude);
                    $stmt->execute();
                    $count++;
                }
            }

            $msg = "<div class='alert alert-success'>
                ✅ Import selesai! <b>{$count}</b> data berhasil dimasukkan.
            </div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Upload Data Mesin | EDC Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f7f9fa;
        }

        /* Sidebar */
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

        .sidebar h2 {
            color: #fff;
            text-align: center;
            margin: 20px 0;
            font-size: 1.5em;
            font-weight: 600;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #ecf0f1;
            text-decoration: none;
            font-size: 15px;
            transition: all 0.25s ease;
            border-radius: 6px;
            margin: 3px 8px;
        }

        .sidebar a:hover,
        .sidebar .nav-link.active {
            background: #1abc9c;
            color: #fff;
            transform: translateX(4px);
        }

        .sidebar a i {
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

        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 40px;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                width: 220px;
                left: -220px;
            }

            .sidebar.show {
                left: 0;
            }

            .main-content {
                margin-left: 0;
            }
        }

        /* Upload Form */
        .upload-box {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: auto;
        }

        .upload-box h2 {
            color: #004085;
            text-align: center;
            margin-bottom: 20px;
        }

        button {
            background: #007bff;
            border: none;
            color: #fff;
            padding: 10px 25px;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #0056b3;
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

                    <li><a href="produk_uko_admin.php" class="nav-link">Produktifitas</a></li>
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
                    <li><a href="produk_mpos.php" class="nav-link">Produktifitas</a></li>
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
    <div class="main-content">
        <div class="upload-box">
            <h2>Upload File Excel (.xlsx)</h2>
            <?= $msg ?>
            <form action="" method="post" enctype="multipart/form-data">
                <input type="file" name="file_excel" accept=".xlsx" required class="form-control mb-3">
                <button type="submit" class="btn btn-primary w-100">Upload 🚀</button>
            </form>
        </div>
    </div>

    <script>
        // Toggle submenu animasi
        document.querySelectorAll("[data-toggle='submenu']").forEach(toggle => {
            toggle.addEventListener("click", function(e) {
                e.preventDefault();
                let target = document.getElementById(this.getAttribute("data-target"));
                target.classList.toggle("show");
            });
        });
    </script>
</body>
</html>
