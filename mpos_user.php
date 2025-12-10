    <?php
    session_start();
    if (!isset($_SESSION['login']) || $_SESSION['level'] != 'user') {
        header("Location: login.php");
        exit;
    }
    include 'koneksi.php';
    $msg = "";
    // ====== AMBIL DATA SIM CARD ======
    $simCards = [];
    $qSim = $conn->query("SELECT sn_simcard FROM sim_card WHERE status = 'tersedia' ORDER BY sn_simcard ASC");
    while ($s = $qSim->fetch_assoc()) {
        $simCards[] = $s['sn_simcard'];
    }


    // =================== PAGINATION ===================

    // Opsi limit per halaman
    $limit_options = [10, 25, 50, 100, 250, 500];
    $default_limit = 10;

    // Tentukan limit sesuai opsi
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : $default_limit;
    if (!in_array($limit, $limit_options)) {
        $limit = $default_limit;
    }

    // Ambil page
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;

    // Hitung offset
    $offset = ($page - 1) * $limit;

    // Hitung total data
    $totalQuery = $conn->query("SELECT COUNT(*) AS total FROM mpos");
    $totalRow   = $totalQuery->fetch_assoc();
    $totalData  = $totalRow['total'];

    // Hitung total halaman
    $totalPage = ceil($totalData / $limit);



    // =================== LIMIT / PAGE ===================
    $default_limit = 10;
    $limit_options = [10, 25, 50, 100, 250, 500];
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : $default_limit;
    if (!in_array($limit, $limit_options)) $limit = $default_limit;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    // =================== HANDLE AJAX FETCH ===================
    if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
        $result = $conn->query("SELECT * FROM mpos ORDER BY id DESC LIMIT $limit OFFSET $offset");
        $no = $offset + 1;
        while ($row = $result->fetch_assoc()) {
            $row_class = ($row['status_done'] == 1) ? 'row-done' : 'row-editable';
            $disabled = ($row['status_done'] == 1) ? 'disabled' : '';
            echo "<tr id='row{$row['id']}' class='{$row_class}'>
            <td class='text-center'>{$no}</td>
            <td>" . htmlspecialchars($row['kc']) . "</td>
            <td>" . htmlspecialchars($row['outlet_id']) . "</td>
            <td>" . htmlspecialchars($row['nama_outlet']) . "</td>
            <td>" . htmlspecialchars($row['sn_mesin']) . "</td>
            <td>" . htmlspecialchars($row['merek_mesin']) . "</td>
            <td>" . htmlspecialchars($row['sn_simcard']) . "</td>
            <td>" . htmlspecialchars($row['nama_pab']) . "</td>
            <td>" . htmlspecialchars($row['pengajuan']) . "</td>
            <td>" . htmlspecialchars($row['keterangan']) . "</td>
            <td>" . htmlspecialchars($row['ket_ppbk']) . "</td>
            <td class='text-center'>
            <button class='btn btn-warning-custom btn-sm' data-bs-toggle='modal' data-bs-target='#editModal{$row['id']}' {$disabled}>
            <i class='fas fa-edit'></i> Edit
            </button>
            </td>
            </tr>";
            $no++;
        }
        exit;
    }

    // =================== INPUT / EDIT ===================
    if (isset($_POST['save_user'])) {
        $id          = $_POST['id'] ?? null;
        $kc          = trim($_POST['kc']);
        $nama_outlet = trim($_POST['nama_outlet']);
        $merek_mesin = trim($_POST['merek_mesin']);
        $sn_simcard  = trim($_POST['sn_simcard']);
        $nama_pab    = trim($_POST['nama_pab']);
        $outlet_id   = trim($_POST['outlet_id']);
        $sn_mesin    = trim($_POST['sn_mesin']);
        $pengajuan   = trim($_POST['pengajuan']);
        $ket_ppbk    = $_POST['ket_ppbk'] ?? '';
        $keterangan  = '';
        $notif_admin = 1;
        $status_done = 0;

        // ================== CEK STATUS SIM CARD ==================
        $cekSim = $conn->prepare("SELECT status FROM sim_card WHERE sn_simcard=?");
        $cekSim->bind_param("s", $sn_simcard);
        $cekSim->execute();
        $resSim = $cekSim->get_result()->fetch_assoc();

        if ($resSim && $resSim['status'] == 'terpakai' && $pengajuan != 'RETURN') {
            $msg = "SIM Card sudah TERPAKAI!";
        } else {



        

        // CEK UNIQUE
       if ($id) {
    $stmt = $conn->prepare("
        SELECT * FROM mpos 
        WHERE (outlet_id=? OR sn_mesin=?) 
        AND status_done = 1
        AND id <> ?
    ");
    $stmt->bind_param("ssi", $outlet_id, $sn_mesin, $id);
} else {
    $stmt = $conn->prepare("
        SELECT * FROM mpos 
        WHERE (outlet_id=? OR sn_mesin=?)
        AND status_done = 1
    ");
    $stmt->bind_param("ss", $outlet_id, $sn_mesin);
}
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $msg = "Outlet ID atau SN Mesin masih AKTIF terpakai!";
        } else {
            if ($id) {
                // EDIT
                $stmt2 = $conn->prepare("UPDATE mpos SET kc=?, nama_outlet=?, merek_mesin=?, sn_simcard=?, nama_pab=?, outlet_id=?, sn_mesin=?, pengajuan=?, ket_ppbk=? WHERE id=?");
                $stmt2->bind_param("sssssssssi", $kc, $nama_outlet, $merek_mesin, $sn_simcard, $nama_pab, $outlet_id, $sn_mesin, $pengajuan, $ket_ppbk, $id);
            } else {
                // INPUT BARU
                $stmt2 = $conn->prepare("INSERT INTO mpos
                (kc,nama_outlet,merek_mesin,sn_simcard,nama_pab,outlet_id,sn_mesin,pengajuan,keterangan,notif_admin,status_done)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)");
                $stmt2->bind_param(
                    "ssssssssiii",
                    $kc,
                    $nama_outlet,
                    $merek_mesin,
                    $sn_simcard,
                    $nama_pab,
                    $outlet_id,
                    $sn_mesin,
                    $pengajuan,
                    $keterangan,
                    $notif_admin,
                    $status_done
                );
            }

           

    if ($stmt2->execute()) {

        // ================== SET STATUS ==================
        $status_stok = '';
        $status_sim  = '';

        if ($pengajuan == 'IMPLEMENTASI' || $pengajuan == 'REPLACE') {
            $status_stok = 'terpakai';
            $status_sim  = 'terpakai';
        } elseif ($pengajuan == 'RETURN') {
            $status_stok = 'tersedia';
            $status_sim  = 'tersedia';
        }

        // ================== UPDATE EDC STOK ==================
        if ($status_stok != '') {
            $stmt3 = $conn->prepare("UPDATE edc_stok SET status=? WHERE sn_mesin=?");
            $stmt3->bind_param("ss", $status_stok, $sn_mesin);
            $stmt3->execute();
        }

        // ================== UPDATE SIM CARD ==================
        if ($status_sim != '') {
            $stmtSim = $conn->prepare("UPDATE sim_card SET status=? WHERE sn_simcard=?");
            $stmtSim->bind_param("ss", $status_sim, $sn_simcard);
            $stmtSim->execute();
        }

        $msg = $id
            ? "Data berhasil diupdate & SIM + Stok tersinkron!"
            : "Data berhasil disimpan & SIM + Stok tersinkron!";

            } else {
        $msg = "Gagal menyimpan data! Error: " . $stmt2->error;
    }
    }
        }
        }
    

    // =================== DATA UNTUK HALAMAN ===================
    $result = $conn->query("SELECT * FROM mpos ORDER BY id DESC LIMIT $limit OFFSET $offset");
    $totalData = $result->num_rows;
    $fields = ['kc' => 'KC', 'nama_outlet' => 'Nama Outlet', 'merek_mesin' => 'Merek Mesin', 'sn_simcard' => 'SN SIM Card', 'nama_pab' => 'Nama PAB'];

    // =================== CURRENT PAGE ===================
    $currentPage = basename($_SERVER['PHP_SELF']);
    ?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <title>Implementasi MPOS</title>
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
                margin-left: 10px;
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

            /* MPOS Table CSS */
            table thead th {
                background-color: #1e73be !important;
                color: white !important;
                text-align: center;
                border-bottom: 2px solid white;
            }

            .row-editable {
                background-color: #dc3545 !important;
                color: white !important;
            }

            .row-done {
                background-color: #28a745 !important;
                color: white !important;
            }

            .table-hover tbody tr.row-editable:hover {
                background-color: #c82333 !important;
            }

            .table-hover tbody tr.row-done:hover {
                background-color: #218838 !important;
            }

            .btn-primary-custom,
            .btn-warning-custom {
                background-color: #1e73be;
                border-color: #1e73be;
                color: white;
                transition: 0.3s;
            }

            .btn-primary-custom:hover,
            .btn-warning-custom:hover {
                background-color: #145a9e;
                border-color: #145a9e;
            }

            table td,
            table th {
                vertical-align: middle;
            }

            .modal-body label {
                font-weight: 500;
            }

            .modal-footer .btn {
                min-width: 100px;
            }

            .modal-body .form-control {
                border-radius: 6px;
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

        <!-- Main Content -->
        <div class="content">

            <h2 class="mb-3">Implementasi MPOS</h2>
            <button class="btn btn-primary-custom mb-3" data-bs-toggle="modal" data-bs-target="#inputModal">
                <i class="fas fa-plus-circle"></i> Input Baru
            </button>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center">
                    <strong class="me-3">Total Data: <?= $totalData ?></strong>
                    <form id="limitForm" method="get" class="d-flex align-items-center mb-0">
                        <label for="limitSelect" class="me-2 mb-0">Tampilkan:</label>
                        <select name="limit" id="limitSelect" class="form-select form-select-sm" onchange="document.getElementById('limitForm').submit()">
                            <?php foreach ($limit_options as $opt): ?>
                                <option value="<?= $opt ?>" <?= ($opt == $limit) ? 'selected' : '' ?>><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="page" value="1">
                    </form>
                </div>
                <div style="min-width:200px;">
                    <input type="text" id="searchInput" class="form-control" placeholder="Cari...">
                </div>
            </div>

            <!-- TABLE -->
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>KC</th>
                        <th>Outlet ID</th>
                        <th>Nama Outlet</th>
                        <th>SN Mesin</th>
                        <th>Merek Mesin</th>
                        <th>SN SIM Card</th>
                        <th>Nama PAB</th>
                        <th>Pengajuan</th>
                        <th>Keterangan</th>
                        <th>Keterangan PPBK</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php
                    $no = $offset + 1;
                    while ($row = $result->fetch_assoc()):
                        $row_class = ($row['status_done'] == 1) ? 'row-done' : 'row-editable';
                        $disabled = ($row['status_done'] == 1) ? 'disabled' : ''; ?>
                        <tr id="row<?= $row['id'] ?>" class="<?= $row_class ?>">
                            <td class="text-center"><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['kc']) ?></td>
                            <td><?= htmlspecialchars($row['outlet_id']) ?></td>
                            <td><?= htmlspecialchars($row['nama_outlet']) ?></td>
                            <td><?= htmlspecialchars($row['sn_mesin']) ?></td>
                            <td><?= htmlspecialchars($row['merek_mesin']) ?></td>
                            <td><?= htmlspecialchars($row['sn_simcard']) ?></td>
                            <td><?= htmlspecialchars($row['nama_pab']) ?></td>
                            <td><?= htmlspecialchars($row['pengajuan']) ?></td>
                            <td><?= htmlspecialchars($row['keterangan']) ?></td>
                            <td><?= htmlspecialchars($row['ket_ppbk']) ?></td>
                            <td class="text-center">
                                <?php if ($row['status_done'] == 1): ?>

    <button class="btn btn-warning btn-sm return-btn"
        data-id="<?= $row['id'] ?>"
        data-snmesin="<?= $row['sn_mesin'] ?>"
        data-snsim="<?= $row['sn_simcard'] ?>">
        🔁 Return
    </button>

<?php else: ?>

    <button class="btn btn-warning-custom btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">
        <i class="fas fa-edit"></i> Edit
    </button>

<?php endif; ?>

</td>

                        </tr>

                        <!-- MODAL EDIT -->
                        <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header" style="background-color:#1e73be;color:white;">
                                        <h5 class="modal-title">Edit Data</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="post">
                                        <div class="modal-body">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <?php
                                            foreach ($fields as $key => $label) {
                                                echo "<div class='mb-3'><label>$label</label>
        <input type='text' name='$key' class='form-control' value='" . htmlspecialchars($row[$key]) . "' " . ($disabled ? 'readonly' : '') . "></div>";
                                            }
                                            ?>
                                            <div class='mb-3'><label>Outlet ID</label>
                                                <input type='text' name='outlet_id' class='form-control' value='<?= htmlspecialchars($row['outlet_id']) ?>' <?= $disabled ?>>
                                            </div>
                                            <div class='mb-3'><label>SN Mesin</label>
                                                <input type='text' name='sn_mesin' class='form-control' value='<?= htmlspecialchars($row['sn_mesin']) ?>' <?= $disabled ?>>
                                            </div>
                                            <div class="mb-3"><label>Pengajuan</label>
                                                <select name="pengajuan" class="form-control" <?= $disabled ?>>
                                                    <option value="">--Pilih--</option>
                                                    <option value="IMPLEMENTASI" <?= $row['pengajuan'] == 'IMPLEMENTASI' ? 'selected' : '' ?>>IMPLEMENTASI</option>
                                                    <option value="REPLACE" <?= $row['pengajuan'] == 'REPLACE' ? 'selected' : '' ?>>REPLACE</option>
                                                    <option value="RETURN" <?= $row['pengajuan'] == 'RETURN' ? 'selected' : '' ?>>RETURN</option>
                                                </select>
                                            </div>
                                            <div class='mb-3'><label>Keterangan PPBK</label>
                                                <input type='text' name='ket_ppbk' class='form-control' value='<?= htmlspecialchars($row['ket_ppbk']) ?>' <?= $disabled ?>>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" name="save_user" class="btn btn-primary-custom" <?= $disabled ?>>Simpan</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                    <?php endwhile; ?>
                </tbody>
            </table>
            <nav>
                <ul class="pagination">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= ($page - 1) ?>&limit=<?= $limit ?>">Prev</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPage; $i++): ?>
                        <li class="page-item <?= ($i == $page ? 'active' : '') ?>">
                            <a class="page-link" href="?page=<?= $i ?>&limit=<?= $limit ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPage): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= ($page + 1) ?>&limit=<?= $limit ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>

            <!-- MODAL INPUT BARU -->
            <div class="modal fade" id="inputModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header" style="background-color:#1e73be;color:white;">
                            <h5 class="modal-title">Input Baru</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="post">
                            <div class="modal-body">
                                <?php
                                foreach ($fields as $key => $label) {
                                    echo "<div class='mb-3'><label>$label</label><input type='text' name='$key' class='form-control'></div>";
                                }
                                ?>
                                <div class='mb-3'><label>Outlet ID</label><input type='text' name='outlet_id' class='form-control'></div>
                                <div class='mb-3'><label>SN Mesin</label><input type='text' name='sn_mesin' class='form-control'></div>
                                <div class="mb-3"><label>Pengajuan</label>
                                    <select name="pengajuan" class="form-control">
                                        <option value="">--Pilih--</option>
                                        <option value="IMPLEMENTASI">IMPLEMENTASI</option>
                                        <option value="REPLACE">REPLACE</option>
                                        <option value="RETURN">RETURN</option>
                                    </select>
                                </div>
                                <div class='mb-3'><label>Keterangan PPBK</label><input type='text' name='ket_ppbk' class='form-control'></div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" name="save_user" class="btn btn-primary-custom">Simpan</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-info mt-2"><?= $msg ?></div>
            <?php endif; ?>

        </div> <!-- END CONTENT -->

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
                    if (arrow) {
                        arrow.classList.replace('fa-angle-left', 'fa-angle-down');
                    }
                }
            });

            // ========== Search Filter ==========
            document.getElementById('searchInput').addEventListener('keyup', function() {
                const filter = this.value.toLowerCase();
                document.querySelectorAll('#tableBody tr').forEach(row => {
                    row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
                });
            });
        </script>
       <script>
document.querySelectorAll('.return-btn').forEach(btn => {
    btn.addEventListener('click', function () {

        const id = this.dataset.id;
        const snMesin = this.dataset.snmesin;
        const snSim = this.dataset.snsim;

        const pilihan = prompt(
`Ketik angka:
1 = Return MESIN
2 = Return SIM
3 = Return KEDUANYA`
        );

        if (!pilihan) return;

        let type = '';
        if (pilihan == '1') type = 'mesin';
        else if (pilihan == '2') type = 'sim';
        else if (pilihan == '3') type = 'both';
        else {
            alert('Pilihan tidak valid!');
            return;
        }

        fetch('return_mpos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `id=${id}&sn_mesin=${snMesin}&sn_sim=${snSim}&type=${type}`
        })
        .then(res => res.text())
        .then(res => {
            if (res === 'success') {
                alert('✅ Return berhasil!');
                location.reload();
            } else {
                alert('❌ Gagal return: ' + res);
            }
        });

    });
});
</script>



    </body>

    </html>