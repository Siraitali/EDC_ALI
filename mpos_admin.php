<?php
include 'koneksi.php';
$msg = "";

// =================== HANDLE AJAX DONE (FIX + SINKRON STOK & SIM) ===================
if (isset($_POST['done_id'])) {
    $done_id = (int)$_POST['done_id'];

    // ✅ 1. SET DONE DI MPOS
    $stmt = $conn->prepare("UPDATE mpos SET status_done=1, notif_user=0 WHERE id=?");
    $stmt->bind_param("i", $done_id);

    if ($stmt->execute()) {

        // ✅ 2. AMBIL DATA MESIN, SIM & PENGAJUAN
        $q = $conn->prepare("SELECT sn_mesin, sn_simcard, pengajuan FROM mpos WHERE id=?");
        $q->bind_param("i", $done_id);
        $q->execute();
        $data = $q->get_result()->fetch_assoc();

        if ($data) {
            $sn_mesin   = $data['sn_mesin'];
            $sn_simcard = $data['sn_simcard'];
            $pengajuan  = strtoupper(trim($data['pengajuan']));

            // ✅ 3. TENTUKAN STATUS BARU
            if ($pengajuan === 'RETURN') {
                $status_stok = 'tersedia';
                $status_sim  = 'tersedia';
            } else {
                $status_stok = 'terpakai';
                $status_sim  = 'terpakai';
            }

            // ✅ 4. UPDATE EDC STOK
            $up1 = $conn->prepare("UPDATE edc_stok SET status=? WHERE sn_mesin=?");
            $up1->bind_param("ss", $status_stok, $sn_mesin);
            $up1->execute();

            // ✅ 5. UPDATE SIM CARD
            $up2 = $conn->prepare("UPDATE sim_card SET status=? WHERE sn_simcard=?");
            $up2->bind_param("ss", $status_sim, $sn_simcard);
            $up2->execute();
        }

        echo "success";
        exit;
    } else {
        echo "error: " . $stmt->error;
        exit;
    }
}

// =================== LIMIT / PAGE ===================
$default_limit = 10;
$limit_options = [10, 25, 50, 100];
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : $default_limit;
if (!in_array($limit, $limit_options)) $limit = $default_limit;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// =================== HITUNG TOTAL DATA ===================
$totalResult = $conn->query("SELECT COUNT(*) as total FROM mpos");
$totalRow = $totalResult->fetch_assoc();
$totalData = $totalRow['total'];
$totalPages = ceil($totalData / $limit);

// =================== UPDATE KETERANGAN / TANGGAL / PPBK ===================
if (isset($_POST['update_admin'])) {
    $id = $_POST['admin_id'];
    $keterangan = $_POST['keterangan'];
    $ket_ppbk = $_POST['ket_ppbk'] ?? '';
    $tanggal_mapping = $_POST['tanggal_mapping'];

    $stmt = $conn->prepare("UPDATE mpos SET keterangan=?, ket_ppbk=?, tanggal_mapping=?, notif_user=0 WHERE id=?");
    $stmt->bind_param("sssi", $keterangan, $ket_ppbk, $tanggal_mapping, $id);
    if ($stmt->execute()) {
        $msg = "Data berhasil diperbarui!";
    } else {
        $msg = "Gagal memperbarui data! Error: " . $stmt->error;
    }
}

// =================== AMBIL DATA ===================
$result = $conn->query("SELECT * FROM mpos ORDER BY id DESC LIMIT $limit OFFSET $offset");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>MPOS Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        /* ================= Sidebar (copy dari sidebar.php) ================= */
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
            padding: 20px;
            transition: all 0.3s ease;
        }

        .main-content.collapsed {
            margin-left: 70px;
        }

        /* Logout */
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

        /* ================= Konten tabel mpos ================= */
        .highlight {
            background-color: #ffff99 !important;
            transition: background-color 0.5s;
        }

        .done-row {
            background-color: #d4edda !important;
        }

        .keterangan-cell {
            font-weight: bold;
            text-align: center;
        }

        .keterangan-done {
            background-color: #d4edda !important;
        }

        .keterangan-pending {
            background-color: #f8d7da !important;
        }

        thead th {
            background-color: #007bff !important;
            color: white;
            text-align: center;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table-hover tbody tr:hover {
            background-color: #e2e6ea;
        }

        .btn-sm {
            padding: .25rem .5rem;
            font-size: .8rem;
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <h2>Admin</h2>
        <ul class="nav flex-column">
            <li class="nav-item"><a href="dashboard_admin.php" class="nav-link active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li class="nav-item">
                <a href="#" class="nav-link" data-toggle="submenu" data-target="stokMenu" data-arrow="stokArrow"><i class="fas fa-box-open"></i> Stok <i class="fas fa-angle-left right ms-auto" id="stokArrow"></i></a>
                <ul class="submenu" id="stokMenu">
                    <li><a href="edc_stok.php" class="nav-link">EDC</a></li>
                    <li><a href="#" class="nav-link">TERMAL</a></li>
                </ul>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" data-toggle="submenu" data-target="ukoMenu" data-arrow="ukoArrow"><i class="fas fa-building"></i> UKO <i class="fas fa-angle-left right ms-auto" id="ukoArrow"></i></a>
                <ul class="submenu" id="ukoMenu">
                    <li><a href="produk_uko_admin.php" class="nav-link">Produktifitas</a></li>
                    <li><a href="detail_uko.php" class="nav-link">Input Detail UKO</a></li>
                    <li><a href="#" class="nav-link">NOP Berulang</a></li>
                </ul>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" data-toggle="submenu" data-target="merchantMenu" data-arrow="merchantArrow"><i class="fas fa-store"></i> Merchant <i class="fas fa-angle-left right ms-auto" id="merchantArrow"></i></a>
                <ul class="submenu" id="merchantMenu">
                    <li><a href="#" class="nav-link">Reliability</a></li>
                    <li><a href="#" class="nav-link">Produktifitas</a></li>
                    <li><a href="#" class="nav-link">NOP Berulang</a></li>
                </ul>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link" data-toggle="submenu" data-target="brilinkMenu" data-arrow="brilinkArrow"><i class="fas fa-link"></i> BRILink <i class="fas fa-angle-left right ms-auto" id="brilinkArrow"></i></a>
                <ul class="submenu" id="brilinkMenu">
                    <li><a href="produk_bri_admin.php" class="nav-link">Produktifitas</a></li>
                    <li><a href="mpos_admin.php" class="nav-link">Pengajuan MPOS</a></li>
                    <li><a href="#" class="nav-link">NOP Berulang</a></li>
                </ul>
            </li>
            <li class="nav-item mt-3"><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="main">
        <span class="toggle-btn d-md-none" onclick="toggleSidebar()"><i class="fas fa-bars"></i></span>

        <!-- Konten MPOS -->
        <div class="container mt-4">
            <h2>MPOS Admin <small class="text-muted">Dashboard</small></h2>

            <!-- Notifikasi Dropdown -->
            <div class="dropdown mb-3 position-relative">
                <button class="btn btn-primary dropdown-toggle" type="button" id="notifBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    Notifikasi <span class="badge bg-danger" id="notifCount">0</span>
                </button>
                <ul class="dropdown-menu" aria-labelledby="notifBtn" id="notifList">
                    <li class="text-center"><span class="dropdown-item">Tidak ada notifikasi</span></li>
                </ul>
            </div>

            <!-- Limit + Search sejajar -->
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

            <!-- Tabel -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
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
                            <th>Tanggal Mapping</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php $no = $offset + 1;
                        if ($result->num_rows > 0): while ($row = $result->fetch_assoc()): ?>
                                <tr id="row<?= $row['id'] ?>" class="<?= ($row['status_done'] == 1) ? 'done-row' : '' ?>">
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($row['kc']) ?></td>
                                    <td><?= htmlspecialchars($row['outlet_id']) ?></td>
                                    <td><?= htmlspecialchars($row['nama_outlet']) ?></td>
                                    <td><?= htmlspecialchars($row['sn_mesin']) ?></td>
                                    <td><?= htmlspecialchars($row['merek_mesin']) ?></td>
                                    <td><?= htmlspecialchars($row['sn_simcard']) ?></td>
                                    <td><?= htmlspecialchars($row['nama_pab']) ?></td>
                                    <td><?= htmlspecialchars($row['pengajuan']) ?></td>
                                    <td class="keterangan-cell <?= ($row['status_done'] == 1) ? 'keterangan-done' : 'keterangan-pending' ?>"><?= htmlspecialchars($row['keterangan']) ?></td>
                                    <td><?= htmlspecialchars($row['ket_ppbk']) ?></td>
                                    <td><input type="date" class="form-control" readonly value="<?= !empty($row['tanggal_mapping']) ? htmlspecialchars(substr($row['tanggal_mapping'], 0, 10)) : '' ?>"></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#adminModal<?= $row['id'] ?>"><i class="fas fa-edit"></i> Edit</button>
                                        <button type="button" class="btn btn-success btn-sm done-btn" data-id="<?= $row['id'] ?>"><i class="fas fa-check"></i> Done</button>
                                    </td>
                                </tr>

                                <!-- Modal Admin -->
                                <div class="modal fade" id="adminModal<?= $row['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Update Keterangan, Keterangan PPBK & Tanggal Mapping</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="post">
                                                <div class="modal-body">
                                                    <input type="hidden" name="admin_id" value="<?= $row['id'] ?>">
                                                    <div class="mb-3"><label>Keterangan</label>
                                                        <input type="text" name="keterangan" class="form-control" value="<?= htmlspecialchars($row['keterangan']) ?>">
                                                    </div>
                                                    <div class="mb-3"><label>Keterangan PPBK</label>
                                                        <input type="text" name="ket_ppbk" class="form-control" value="<?= htmlspecialchars($row['ket_ppbk']) ?>">
                                                    </div>
                                                    <div class="mb-3"><label>Tanggal Mapping</label>
                                                        <input type="date" name="tanggal_mapping" class="form-control" value="<?= !empty($row['tanggal_mapping']) ? htmlspecialchars(substr($row['tanggal_mapping'], 0, 10)) : '' ?>">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="submit" name="update_admin" class="btn btn-success">Simpan</button>
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                            <?php endwhile;
                        else: ?>
                            <tr>
                                <td colspan="13" class="text-center">Tidak ada data</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <nav>
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= ($page > 1 ? $page - 1 : 1) ?>&limit=<?= $limit ?>">Prev</a>
                    </li>
                    <?php
                    $max_links = 5;
                    $start = max($page - 2, 1);
                    $end = min($start + $max_links - 1, $totalPages);
                    if ($end - $start + 1 < $max_links) $start = max($end - $max_links + 1, 1);
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&limit=<?= $limit ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page < $totalPages ? $page + 1 : $totalPages ?>&limit=<?= $limit ?>">Next</a>
                    </li>
                </ul>
            </nav>

            <!-- Toast -->
            <?php if ($msg != ""): ?>
                <div class="position-fixed bottom-0 end-0 p-3" style="z-index:11">
                    <div id="toastMsg" class="toast align-items-center text-white <?= strpos($msg, 'Gagal') !== false ? 'bg-danger' : 'bg-success' ?> border-0" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body"><?= $msg ?></div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Sidebar JS -->
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

    <!-- MPOS JS -->
    <script>
        // Live search
        document.getElementById('searchInput').addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            document.querySelectorAll('table tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
            });
        });

        // Toast show
        document.addEventListener('DOMContentLoaded', function() {
            var toastEl = document.getElementById('toastMsg');
            if (toastEl) {
                var toast = new bootstrap.Toast(toastEl);
                toast.show();
            }
        });

        // ==================== NOTIFIKASI ====================
        function cekNotif() {
            fetch('cek_notif.php')
                .then(res => res.json())
                .then(res => {
                    const notifCount = document.getElementById('notifCount');
                    const notifList = document.getElementById('notifList');
                    notifCount.textContent = res.count;
                    notifList.innerHTML = '';
                    if (res.count > 0) {
                        res.data.forEach(item => {
                            const li = document.createElement('li');
                            li.innerHTML = `<a class="dropdown-item" href="#" data-id="${item.id}">KC: ${item.kc} | ${item.nama_outlet} (${item.outlet_id}) - ${item.pengajuan}</a>`;
                            notifList.appendChild(li);
                        });
                        notifList.querySelectorAll('a').forEach(a => {
                            a.addEventListener('click', function(e) {
                                e.preventDefault();
                                const rowId = this.getAttribute('data-id');
                                const row = document.getElementById('row' + rowId);
                                if (row) {
                                    row.scrollIntoView({
                                        behavior: "smooth",
                                        block: "center"
                                    });
                                    row.classList.add('highlight');
                                    setTimeout(() => {
                                        row.classList.remove('highlight');
                                    }, 5000);
                                }
                                fetch('update_notif.php', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/x-www-form-urlencoded'
                                        },
                                        body: 'id=' + rowId
                                    })
                                    .then(() => {
                                        let count = parseInt(notifCount.textContent);
                                        notifCount.textContent = count > 0 ? count - 1 : 0;
                                    });
                            });
                        });
                    } else {
                        const li = document.createElement('li');
                        li.innerHTML = '<span class="dropdown-item text-center">Tidak ada notifikasi</span>';
                        notifList.appendChild(li);
                    }
                });
        }
        setInterval(cekNotif, 5000);

        // DONE BUTTON
        function bindDoneButtons() {
            document.querySelectorAll('.done-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const button = this;
                    fetch('mpos_admin.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'done_id=' + id
                        })
                        .then(res => res.text())
                        .then(res => {
                            if (res === 'success') {
                                button.classList.remove('btn-success');
                                button.classList.add('btn-secondary');
                                button.innerHTML = 'Done ✓';
                                const row = document.getElementById('row' + id);
                                if (row) {
                                    row.classList.add('done-row');
                                    const keteranganCell = row.querySelector('.keterangan-cell');
                                    if (keteranganCell) {
                                        keteranganCell.classList.remove('keterangan-pending');
                                        keteranganCell.classList.add('keterangan-done');
                                    }
                                }
                            } else {
                                alert('Gagal set Done: ' + res);
                            }
                        });
                });
            });
        }
        bindDoneButtons();

        // RE-NUMBER NO
        function renumberRows(startIndex = 1) {
            const tbody = document.getElementById('tableBody');
            if (!tbody) return;
            let no = parseInt(startIndex, 10);
            tbody.querySelectorAll('tr').forEach(row => {
                const firstCell = row.querySelector('td');
                if (firstCell) {
                    firstCell.textContent = no++;
                }
            });
        }

        // AUTO REFRESH
        function refreshTable() {
            const limit = parseInt(document.getElementById('limitSelect').value, 10);
            const page = <?= $page ?>;
            const startIndex = ((page - 1) * limit) + 1;
            fetch('fetch_mpos.php?limit=' + limit + '&page=' + page)
                .then(res => res.text())
                .then(html => {
                    const tbody = document.getElementById('tableBody');
                    tbody.innerHTML = html;
                    bindDoneButtons();
                    renumberRows(startIndex);
                    const filter = document.getElementById('searchInput').value.toLowerCase();
                    tbody.querySelectorAll('tr').forEach(row => {
                        row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
                    });
                });
        }
        setInterval(refreshTable, 5000);
    </script>

</body>

</html>