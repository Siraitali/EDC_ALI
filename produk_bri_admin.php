<?php
// =======================================
// PRODUK BRI ADMIN - Import Excel (Fixed use + safer load)
// =======================================

ini_set('memory_limit', '2048M');
ini_set('max_execution_time', '600');
ini_set('upload_max_filesize', '20M');

include 'koneksi.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_POST['import'])) {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo "<script>alert('File belum dipilih atau terjadi kesalahan upload.');</script>";
    } else {
        $file = $_FILES['file']['tmp_name'];
        $tanggal_input = date('Y-m-d');

        mysqli_query($conn, "DELETE FROM produk_bri");
        mysqli_query($conn, "DELETE FROM productivity");

        $reader = IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true);

        // --- Sheet 1: detail_productivity
        try {
            $reader->setLoadSheetsOnly(['detail_productivity']);
            $spreadsheet = $reader->load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            for ($i = 1; $i < count($rows); $i++) {
                $r = $rows[$i];
                $outlet_code     = isset($r[0]) ? mysqli_real_escape_string($conn, trim($r[0])) : '';
                $outlet_name     = isset($r[1]) ? mysqli_real_escape_string($conn, trim($r[1])) : '';
                $created_date    = isset($r[2]) ? mysqli_real_escape_string($conn, trim($r[2])) : null;
                $umur_hari       = isset($r[3]) ? (int)$r[3] : null;
                $KODE_KANWIL     = isset($r[4]) ? mysqli_real_escape_string($conn, trim($r[4])) : '';
                $NAMA_KANWIL     = isset($r[5]) ? mysqli_real_escape_string($conn, trim($r[5])) : '';
                $branch_outlet   = isset($r[6]) ? mysqli_real_escape_string($conn, trim($r[6])) : '';
                $id_agen         = isset($r[7]) ? mysqli_real_escape_string($conn, trim($r[7])) : '';
                $check_agen      = isset($r[8]) ? mysqli_real_escape_string($conn, trim($r[8])) : '';
                $lokasi          = isset($r[9]) ? mysqli_real_escape_string($conn, trim($r[9])) : '';
                $status_live     = isset($r[10]) ? mysqli_real_escape_string($conn, trim($r[10])) : '';
                $total_transaksi = isset($r[11]) ? (int)$r[11] : null;
                $total_nominal   = isset($r[12]) ? (float)str_replace(',', '', $r[12]) : null;
                $total_fee       = isset($r[13]) ? (float)str_replace(',', '', $r[13]) : null;
                $produktif       = isset($r[14]) ? mysqli_real_escape_string($conn, trim($r[14])) : '';
                $LiveProduktif   = isset($r[15]) ? mysqli_real_escape_string($conn, trim($r[15])) : '';
                $PN_PPBK         = isset($r[16]) ? mysqli_real_escape_string($conn, trim($r[16])) : '';
                $Nama_PPBK       = isset($r[17]) ? mysqli_real_escape_string($conn, trim($r[17])) : '';

                if ($outlet_code === '') continue;

                $created_date_sql = "NULL";
                if (!empty($created_date)) {
                    $ts = strtotime($created_date);
                    if ($ts !== false) {
                        $created_date_sql = "'" . date('Y-m-d', $ts) . "'";
                    } else {
                        $created_date_sql = "'" . mysqli_real_escape_string($conn, $created_date) . "'";
                    }
                }

                $sql = "INSERT INTO produk_bri (
                            outlet_code, outlet_name, created_date, umur_hari, KODE_KANWIL, NAMA_KANWIL,
                            branch_outlet, id_agen, check_agen, lokasi, status_live,
                            total_transaksi, total_nominal, total_fee, produktif, LiveProduktif,
                            PN_PPBK, Nama_PPBK
                        ) VALUES (
                            '{$outlet_code}','{$outlet_name}', {$created_date_sql}, " . ($umur_hari !== null ? $umur_hari : "NULL") . ",
                            '{$KODE_KANWIL}','{$NAMA_KANWIL}','{$branch_outlet}','{$id_agen}','{$check_agen}','{$lokasi}','{$status_live}',
                            " . ($total_transaksi !== null ? $total_transaksi : "NULL") . ",
                            " . ($total_nominal !== null ? $total_nominal : "NULL") . ",
                            " . ($total_fee !== null ? $total_fee : "NULL") . ",
                            '{$produktif}','{$LiveProduktif}','{$PN_PPBK}','{$Nama_PPBK}'
                        )";
                mysqli_query($conn, $sql);
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        } catch (Exception $e) {
            echo "<script>alert('Error membaca sheet detail_productivity: " . addslashes($e->getMessage()) . "');</script>";
        }

        // --- Sheet 2: detail_cabang
        try {
            $reader->setLoadSheetsOnly(['detail_cabang']);
            $spreadsheet = $reader->load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            for ($i = 1; $i < count($rows); $i++) {
                $r = $rows[$i];
                $kode_cabang            = isset($r[0]) ? mysqli_real_escape_string($conn, trim($r[0])) : '';
                $nama_cabang            = isset($r[1]) ? mysqli_real_escape_string($conn, trim($r[1])) : '';
                $nama_kanwil            = isset($r[2]) ? mysqli_real_escape_string($conn, trim($r[2])) : '';
                $jumlah_outlet          = isset($r[3]) ? (int)$r[3] : null;
                $live_produktif         = isset($r[4]) ? (int)$r[4] : null;
                $live_produktif_percent = isset($r[5]) ? (float)str_replace(',', '', $r[5]) : null;

                if ($kode_cabang === '') continue;

                $sql = "INSERT INTO productivity (
                            kode_cabang, nama_cabang, nama_kanwil, jumlah_outlet, live_produktif, live_produktif_percent, get_date
                        ) VALUES (
                            '{$kode_cabang}', '{$nama_cabang}', '{$nama_kanwil}',
                            " . ($jumlah_outlet !== null ? $jumlah_outlet : "NULL") . ",
                            " . ($live_produktif !== null ? $live_produktif : "NULL") . ",
                            " . ($live_produktif_percent !== null ? $live_produktif_percent : "NULL") . ",
                            '{$tanggal_input}'
                        )";
                mysqli_query($conn, $sql);
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            echo "<script>alert('Import berhasil! Data lama dihapus dan diganti dengan data baru.');</script>";
        } catch (Exception $e) {
            echo "<script>alert('Error membaca sheet detail_cabang: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Import Data Produk BRI</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f9;
            margin: 0;
        }

        /* Sidebar Styles */
        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            background: linear-gradient(180deg, #2c3e50, #1c2833);
            overflow-y: auto;
            padding-top: 10px;
            box-shadow: 2px 0 8px rgba(0, 0, 0, .15);
            transition: all .3s ease;
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
            transition: max-height .4s ease;
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
            transition: all .3s ease;
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
            width: calc(100%-20px);
            margin: 15px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .3s ease;
        }

        .logout-btn:hover {
            background: #c0392b;
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <h2>Admin</h2>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="dashboard_admin.php" class="nav-link active"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
            </li>

            <!-- STOK -->
            <li class="nav-item">
                <a href="#" class="nav-link" data-toggle="submenu" data-target="stokMenu" data-arrow="stokArrow">
                    <i class="fas fa-box-open"></i><span>Stok</span><i class="fas fa-angle-left right ms-auto" id="stokArrow"></i>
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
                    <i class="fas fa-building"></i><span>UKO</span><i class="fas fa-angle-left right ms-auto" id="ukoArrow"></i>
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
                    <i class="fas fa-store"></i><span>Merchant</span><i class="fas fa-angle-left right ms-auto" id="merchantArrow"></i>
                </a>
                <ul class="submenu" id="merchantMenu">
                    <li><a href="#" class="nav-link">Reliability</a></li>
                    <li><a href="#" class="nav-link">Produktifitas</a></li>
                    <li><a href="#" class="nav-link">NOP Berulang</a></li>
                </ul>
            </li>

            <!-- BRILink -->
            <li class="nav-item">
                <a href="#" class="nav-link" data-toggle="submenu" data-target="brilinkMenu" data-arrow="brilinkArrow">
                    <i class="fas fa-link"></i><span>BRILink</span><i class="fas fa-angle-left right ms-auto" id="brilinkArrow"></i>
                </a>
                <ul class="submenu" id="brilinkMenu">
                    <li><a href="produk_bri_admin.php" class="nav-link">Produktifitas</a></li>
                    <li><a href="mpos_admin.php" class="nav-link">Pengajuan MPOS</a></li>
                    <li><a href="#" class="nav-link">NOP Berulang</a></li>
                </ul>
            </li>

            <!-- Logout -->
            <li class="nav-item mt-3">
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="main">
        <span class="toggle-btn d-md-none" onclick="toggleSidebar()"><i class="fas fa-bars"></i></span>

        <div class="container">
            <div class="card p-4">
                <h3 class="text-center mb-4">Import Data Produk BRI</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="file" class="form-label">Pilih File Excel (.xlsx)</label>
                        <input type="file" name="file" id="file" class="form-control" accept=".xlsx" required>
                    </div>
                    <button type="submit" name="import" class="btn btn-danger">🔥 Import Data (Hapus Lama)</button>
                </form>
                <hr class="my-3">
                <div class="alert alert-info small mb-0">
                    <strong>Catatan:</strong> File harus punya 2 sheet bernama <code>detail_productivity</code> dan <code>detail_cabang</code>.
                    Kolom kosong dalam file akan diabaikan. <br>
                    <em>get_date</em> pada tabel <code>productivity</code> akan otomatis diisi tanggal server saat import.
                </div>
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