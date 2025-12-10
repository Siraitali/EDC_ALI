<?php
// detail_uko_admin_full.php
include 'koneksi.php';
require 'vendor/autoload.php'; // pastikan sudah install phpoffice/phpspreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_POST['import'])) {
    $file = $_FILES['file']['tmp_name'];

    if ($_FILES['file']['error'] == 0) {
        try {
            $spreadsheet = IOFactory::load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            // hapus semua data lama dulu
            $conn->query("TRUNCATE TABLE detail_uko");

            $count = 0;
            foreach ($rows as $index => $row) {
                if ($index == 0) continue; // skip header
                if (empty($row[1])) continue;

                $mid                = $row[1];
                $tid                = $row[2];
                $nama_merchant      = $row[3];
                $kantor             = $row[4];
                $cabang             = $row[5];
                $posisi_edc         = $row[6];
                $nama_instansi      = $row[7];
                $merk_mesin         = $row[8];
                $sn_mesin           = $row[9];
                $kondisi_perangkat  = $row[10];
                $keterangan         = $row[11];
                $status_isi_merk    = $row[12];

                $sql = "INSERT INTO detail_uko 
                (mid, tid, nama_merchant, kantor, cabang, posisi_edc, nama_instansi, merk_mesin, sn_mesin, kondisi_perangkat, keterangan, status_isi_merk)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "ssssssssssss",
                    $mid, $tid, $nama_merchant, $kantor, $cabang, $posisi_edc,
                    $nama_instansi, $merk_mesin, $sn_mesin, $kondisi_perangkat, $keterangan, $status_isi_merk
                );
                $stmt->execute();
                $count++;
            }

            $msg = "✅ Import berhasil! Total $count data dimasukkan.";
        } catch (Exception $e) {
            $msg = "❌ Gagal membaca file: " . $e->getMessage();
        }
    } else {
        $msg = "❌ Gagal upload file.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Admin - Import Detail UKO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f7f9fa;
            transition: background 0.3s ease;
        }

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
            padding: 30px;
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
            transition: background 0.3s ease;
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
                z-index: 999;
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

                    <li><a href="produk_uko_admin.php" class="nav-link">Produktifitas</a></li>
                    <li><a href="detail_uko.php" class="nav-link">Input Detail UKO</a></li>
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
                    <li><a href="mpos_user.php" class="nav-link <?= $currentPage == 'mpos_user.php' ? 'active' : ''; ?>">Implementasi MPOS</a></li>
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
        <div class="container">
            <div class="card shadow border-0">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="m-0">📥 Import Data detail_uko (.xlsx)</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($msg)): ?>
                        <div class="alert alert-info"><?= $msg ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="file" class="form-label">Pilih File Excel (.xlsx)</label>
                            <input type="file" name="file" id="file" class="form-control" accept=".xlsx" required>
                            <small class="text-muted">Pastikan urutan kolom sesuai format tabel dan baris pertama adalah header.</small>
                        </div>
                        <button type="submit" name="import" class="btn btn-success w-100">
                            🚀 Import Sekarang
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sidebar toggle (desktop + mobile)
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
