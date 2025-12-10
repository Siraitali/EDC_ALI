<?php
include 'koneksi.php';
$msg = "";

// ====== PROSES IMPORT EXCEL ======
if (isset($_POST['import'])) {
    if (isset($_FILES['file_excel']['name']) && $_FILES['file_excel']['error'] == 0) {
        $allowed_ext = ['xls', 'xlsx'];
        $file_name = $_FILES['file_excel']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $file_size = $_FILES['file_excel']['size'];
        $file_tmp  = $_FILES['file_excel']['tmp_name'];

        if (in_array($file_ext, $allowed_ext) && $file_size <= 50 * 1024 * 1024) {
            require 'vendor/autoload.php';

            try {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file_tmp);
                $spreadsheet = $reader->load($file_tmp);
                $rows = $spreadsheet->getActiveSheet()->toArray();

                $first = true;
                foreach ($rows as $row) {
                    if ($first) {
                        $first = false;
                        continue;
                    }

                    $sn_mesin      = $conn->real_escape_string($row[1] ?? '');
                    $branch_code   = $conn->real_escape_string($row[2] ?? '');
                    $branch_office = $conn->real_escape_string($row[3] ?? '');
                    $type_mesin    = $conn->real_escape_string($row[4] ?? '');
                    $spk    = $conn->real_escape_string($row[5] ?? '');
                    $status_raw    = $row[6] ?? '';
                    $status        = trim($status_raw) === '' ? 'Tersedia' : $conn->real_escape_string($status_raw);

                    if ($sn_mesin == '') continue;

                    $qExist = $conn->query("SELECT no FROM edc_stok WHERE sn_mesin = '$sn_mesin' LIMIT 1");
                    if ($qExist && $qExist->num_rows > 0) {
                        $conn->query("UPDATE edc_stok SET branch_code='$branch_code', branch_office='$branch_office', type_mesin='$type_mesin', spk='$spk', status='$status' WHERE sn_mesin = '$sn_mesin'");
                    } else {
                        $conn->query("INSERT INTO edc_stok (sn_mesin, branch_code, branch_office, type_mesin, spk, status)
                                     VALUES ('$sn_mesin','$branch_code','$branch_office','$type_mesin','$spk','$status')");
                    }
                }
                header("Location: edc_stok.php?msg=success");
exit;
            } catch (Exception $e) {
                header("Location: edc_stok.php?msg=error&detail=" . urlencode($e->getMessage()));
exit;

            }
        } else {
            $msg = "<div class='alert alert-danger mt-3'>❌ Format file salah atau ukuran melebihi 50MB!</div>";
        }
    } else {
        $msg = "<div class='alert alert-warning mt-3'>⚠️ Tidak ada file yang diupload!</div>";
    }
}

// ====== AMBIL DATA ======
$result = $conn->query("SELECT * FROM edc_stok ORDER BY no DESC");

// ====== JUMLAH STATUS ======
$qTersedia = $conn->query("SELECT COUNT(*) AS total FROM edc_stok WHERE status='Tersedia' OR status IS NULL OR status=''")->fetch_assoc()['total'] ?? 0;
$qTerpakai = $conn->query("SELECT COUNT(*) AS total FROM edc_stok WHERE status='Terpakai'")->fetch_assoc()['total'] ?? 0;

// ====== LIST TYPE MESIN & BRANCH OFFICE ======
$typeList = [];
$branchList = [];
$qType = $conn->query("SELECT DISTINCT type_mesin FROM edc_stok ORDER BY type_mesin ASC");
while ($r = $qType->fetch_assoc()) {
    if (!empty($r['type_mesin'])) $typeList[] = $r['type_mesin'];
}
$qBranch = $conn->query("SELECT DISTINCT branch_office FROM edc_stok ORDER BY branch_office ASC");
while ($r = $qBranch->fetch_assoc()) {
    if (!empty($r['branch_office'])) $branchList[] = $r['branch_office'];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>📦 Data Stok EDC (Admin)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        /* ===== Sidebar ===== */
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            background: #f4f6f9;
        }

        .sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            background: linear-gradient(180deg, #2c3e50, #1c2833);
            overflow-y: auto;
            padding-top: 10px;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            transition: 0.3s;
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
            width: calc(100%-20px);
            margin: 15px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s ease;
        }

        .logout-btn:hover {
            background: #c0392b;
        }

        /* ===== STATISTIK BOX ===== */
        .stat-box {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            margin-bottom: 20px;
        }

        .stat-item {
            flex: 1 1 160px;
            background: #fff;
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

        /* ===== FILTER BAR ===== */
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

        @media (max-width:768px) {
            .filter-bar {
                flex-direction: column;
                align-items: flex-start;
            }

            .sidebar {
                left: -220px;
                width: 220px;
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
                    <li><a href="#" class="nav-link">Reliability</a></li>
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
                    <li><a href="produk_bri_user.php" class="nav-link">Produktifitas</a></li>
                    <li><a href="mpos_admin.php" class="nav-link">Pengajuan MPOS</a></li>
                    <li><a href="#" class="nav-link">NOP Berulang</a></li>
                </ul>
            </li>

            <!-- UKO, Merchant, BRILink, Logout sama seperti sidebar.php -->

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

        <div class="container">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                    <h4 class="m-0">📦 Data Stok EDC (Admin)</h4>
                    <form method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2 mt-2 mt-md-0">
                        <input type="file" name="file_excel" accept=".xls,.xlsx" class="form-control form-control-sm" required>
                        <button type="submit" name="import" class="btn btn-import">📥 Import Excel</button>
                    </form>
                </div>

                <div class="card-body">
                    <?php
if(isset($_GET['msg'])) {
    if($_GET['msg'] == 'success'){
        echo "<div class='alert alert-success mt-3'>✅ Import data berhasil!</div>";
    } elseif($_GET['msg'] == 'error' && isset($_GET['detail'])){
        echo "<div class='alert alert-danger mt-3'>❌ Gagal: " . htmlspecialchars($_GET['detail']) . "</div>";
    }
}
?>

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
                                    <th>SPK</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <?php
                                    $statusText = trim($row['status']) === '' ? 'Tersedia' : htmlspecialchars($row['status']);
                                    $statusClass = 'status-' . str_replace(' ', '', $statusText);
                                    ?>
                                    <tr>
                                        <td class="text-center"><?= $row['no'] ?></td>
                                        <td><?= htmlspecialchars($row['sn_mesin']) ?></td>
                                        <td><?= htmlspecialchars($row['branch_code']) ?></td>
                                        <td><?= htmlspecialchars($row['branch_office']) ?></td>
                                        <td><?= htmlspecialchars($row['type_mesin']) ?></td>
                                        <td><?= htmlspecialchars($row['spk']) ?></td>
                                        <td class="text-center">
                                            <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            var table = $('#stokTable').DataTable({
                "pageLength": 15,
                "language": {
                    "search": "Cari:",
                    "lengthMenu": "Tampilkan _MENU_ data",
                    "info": "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                    "paginate": {
                        "previous": "Sebelumnya",
                        "next": "Berikutnya"
                    }
                }
            });

            $('#filterType').on('change', function() {
                table.column(4).search($(this).val()).draw();
            });

            $('#filterBranch').on('change', function() {
                table.column(3).search($(this).val()).draw();
            });
        });

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