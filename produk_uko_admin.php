<?php
set_time_limit(0);
session_start();
if (!isset($_SESSION['login']) || $_SESSION['level'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Koneksi database
$conn = new mysqli("localhost", "root", "", "edc_login_db2");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Upload Excel Aman
if (isset($_POST['upload_excel'])) {
    $fileName = $_FILES['excel_file']['name'];
    $fileTmp = $_FILES['excel_file']['tmp_name'];

    if (!is_dir('uploads')) mkdir('uploads', 0777, true);
    $uploadPath = 'uploads/' . $fileName;
    move_uploaded_file($fileTmp, $uploadPath);

    $spreadsheet = IOFactory::load($uploadPath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    if (!empty($rows)) {
        $conn->query("TRUNCATE TABLE produk_uko");

        // Ambil header Excel
        $header = array_map('strtolower', $rows[0]); // ['kanca','nonproduk','produktif','grand']

        foreach ($rows as $index => $row) {
            if ($index == 0 || empty(array_filter($row))) continue; // skip header & kosong

            // Map data ke kolom database
            $data = [];
            foreach ($header as $key => $colName) {
                $data[$colName] = $row[$key];
            }

            $kanca   = isset($data['kanca']) ? $conn->real_escape_string($data['kanca']) : '';
            $nonprod = isset($data['nonproduk']) && is_numeric($data['nonproduk']) ? floatval($data['nonproduk']) : 0;
            $prod    = isset($data['produktif']) && is_numeric($data['produktif']) ? floatval($data['produktif']) : 0;
            $grand   = isset($data['grand']) && is_numeric($data['grand']) ? floatval($data['grand']) : 0;
            $target  = 95;

            $sql = "INSERT INTO produk_uko (kanca_uko, nonproduk_produk, produktif, grand_total, target, uploaded_at)
                    VALUES ('$kanca', '$nonprod', '$prod', '$grand', '$target', NOW())";
            $conn->query($sql);
        }
    }
    header("Location: produk_uko_admin.php");
    exit;
}

// Ambil data
$produk = [];
$result = $conn->query("SELECT * FROM produk_uko ORDER BY id ASC");
if ($result) $produk = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Produk UKO Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f7f9fa;
            transition: background 0.3s;
        }

        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            background: linear-gradient(180deg, #2c3e50, #1c2833);
            overflow-y: auto;
            padding-top: 10px;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.15);
            transition: all 0.3s;
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
            border-radius: 6px;
            margin: 3px 8px;
            white-space: nowrap;
            transition: all 0.25s;
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
            transition: max-height 0.4s;
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
            transition: all 0.3s;
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
            width: calc(100% - 20px);
            margin: 15px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: #c0392b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
        }

        table th,
        table td {
            padding: 12px 15px;
            text-align: center;
        }

        table th {
            background: #2980b9;
            color: #fff;
        }

        table tr:nth-child(even) {
            background: #f2f2f2;
        }

        .upload-btn {
            background: #1abc9c;
            color: #fff;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 10px;
        }

        .progress-bar {
            height: 15px;
            border-radius: 5px;
            color: #fff;
            font-weight: bold;
            text-align: center;
            font-size: 12px;
            line-height: 15px;
        }
    </style>
</head>

<body>

    <div class="sidebar" id="sidebar">
        <h2>Admin</h2>
        <a href="#" onclick="toggleSidebar()" class="d-md-none"><i class="fas fa-bars"></i> <span class="text">Menu</span></a>
        <ul class="nav flex-column">
            <li class="nav-item"><a href="dashboard_admin.php" class="nav-link active"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>

            <li class="nav-item">
                <a href="#" class="nav-link" data-toggle="submenu" data-target="stokMenu" data-arrow="stokArrow"><i class="fas fa-box-open"></i><span>Stok</span><i class="fas fa-angle-left right ms-auto" id="stokArrow"></i></a>
                <ul class="submenu" id="stokMenu">
                    <li><a href="edc_stok.php" class="nav-link">EDC</a></li>
                    <li><a href="#" class="nav-link">TERMAL</a></li>
                    <li><a href="upload_mesin.php" class="nav-link">Peta EDC</a></li>
                </ul>
            </li>

            <li class="nav-item">
                <a href="#" class="nav-link" data-toggle="submenu" data-target="ukoMenu" data-arrow="ukoArrow"><i class="fas fa-building"></i><span>UKO</span><i class="fas fa-angle-left right ms-auto" id="ukoArrow"></i></a>
                <ul class="submenu" id="ukoMenu">
                    <li><a href="produk_uko_admin.php" class="nav-link">Produktifitas</a></li>
                    <li><a href="detaul_uko_admin.php" class="nav-link">Input Detail UKO</a></li>
                    <li><a href="#" class="nav-link">NOP Berulang</a></li>
                </ul>
            </li>

            <li class="nav-item">
                <a href="#" class="nav-link" data-toggle="submenu" data-target="merchantMenu" data-arrow="merchantArrow"><i class="fas fa-store"></i><span>Merchant</span><i class="fas fa-angle-left right ms-auto" id="merchantArrow"></i></a>
                <ul class="submenu" id="merchantMenu">
                    <li><a href="wpe_admin.php" class="nav-link">Reliability</a></li>
                    <li><a href="produk_mpos.php" class="nav-link">Produktifitas</a></li>
                    <li><a href="#" class="nav-link">NOP Berulang</a></li>
                </ul>
            </li>

            <li class="nav-item">
                <a href="#" class="nav-link" data-toggle="submenu" data-target="brilinkMenu" data-arrow="brilinkArrow"><i class="fas fa-link"></i><span>BRILink</span><i class="fas fa-angle-left right ms-auto" id="brilinkArrow"></i></a>
                <ul class="submenu" id="brilinkMenu">
                    <li><a href="produk_bri_admin.php" class="nav-link">Produktifitas</a></li>
                    <li><a href="mpos_admin.php" class="nav-link">Pengajuan MPOS</a></li>
                    <li><a href="#" class="nav-link">NOP Berulang</a></li>
                </ul>
            </li>

            <li class="nav-item mt-3"><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content" id="main">
        <div class="header">
            <h1>Produk UKO Admin</h1>
        </div>

        <form method="post" enctype="multipart/form-data">
            <input type="file" name="excel_file" required>
            <button type="submit" name="upload_excel" class="upload-btn">Upload Excel</button>
        </form>

        <table id="produkTable">
            <thead>
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
                $total_nonprod = 0;
                $total_prod = 0;
                $total_grand = 0;
                foreach ($produk as $item):
                    $produktif = floatval($item['produktif']);
                    $grand_total = floatval($item['grand_total']);
                    $nonprod = floatval($item['nonproduk_produk']);
                    $target = floatval($item['target']);

                    $persen = ($grand_total != 0) ? ($produktif / $grand_total) * 100 : 0;
                    $pencapaian = ($target != 0) ? ($persen / $target) * 100 : 0;
                    $barColor = ($pencapaian >= 100) ? '#2ecc71' : '#f1c40f';

                    $total_nonprod += $nonprod;
                    $total_prod += $produktif;
                    $total_grand += $grand_total;
                ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= htmlspecialchars($item['kanca_uko']); ?></td>
                        <td><?= $nonprod; ?></td>
                        <td><?= $produktif; ?></td>
                        <td><?= $grand_total; ?></td>
                        <td><?= number_format($persen, 2); ?>%</td>
                        <td><?= $target; ?></td>
                        <td>
                            <div style="background:#ecf0f1;border-radius:5px;">
                                <div class="progress-bar" style="width:<?= $pencapaian; ?>%;background:<?= $barColor; ?>;">
                                    <?= number_format($pencapaian, 2); ?>%
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <tr style="background:#bdc3c7;font-weight:bold;">
                    <td colspan="2">Grand Total</td>
                    <td><?= $total_nonprod; ?></td>
                    <td><?= $total_prod; ?></td>
                    <td><?= $total_grand; ?></td>
                    <td><?= number_format(($total_grand ? ($total_prod / $total_grand) * 100 : 0), 2); ?>%</td>
                    <td>95</td>
                    <td><?= number_format((($total_grand ? ($total_prod / $total_grand) * 100 : 0) / 95) * 100, 2); ?>%</td>
                </tr>
            </tbody>
        </table>
    </div>

    <script>
        // Sidebar toggle
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

        // Submenu toggle
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