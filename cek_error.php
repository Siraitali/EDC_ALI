<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['level'] != 'user') {
    header("Location: login.php");
    exit;
}
$currentPage = basename($_SERVER['PHP_SELF']);

// Data Kode Error (sama seperti sebelumnya)
$errors = [
    ["kode" => "q4", "keterangan" => "Koneksi terputus", "jenis" => "All", "tindakan" => "Hubungi petugas cek koneksi(OSD)"],
    ["kode" => "q1", "keterangan" => "Koneksi terputus", "jenis" => "All", "tindakan" => "Hubungi petugas, cek koneksi(OSD)"],
    ["kode" => "p5", "keterangan" => "-", "jenis" => "ganti pin", "tindakan" => "jangan menggunakan pin standart atau pin yang pernah digunakan, jangan menggunakan tanggal lahir"],
    ["kode" => "NH", "keterangan" => "Passive Account", "jenis" => "All", "tindakan" => "Kartu tidak diijinkan bertransaksi. Untuk aktivasi hubungi unit terkait"],
    ["kode" => "ng", "keterangan" => "blocked account", "jenis" => "all", "tindakan" => "kartu tidak diijinkan bertransaksi"],
    ["kode" => "NF", "keterangan" => "Closed Account", "jenis" => "All", "tindakan" => "Kartu tidak diijinkan bertransaksi"],
    ["kode" => "n7", "keterangan" => "inactive account", "jenis" => "all", "tindakan" => "kartu tidak diijinkan bertransaksi. Untuk aktivasi hubungi kanca"],
    ["kode" => "n2", "keterangan" => "eod process in branch", "jenis" => "all", "tindakan" => "transaksi tidak dapat dilakukan"],
    ["kode" => "n1", "keterangan" => "eod process in branch", "jenis" => "all", "tindakan" => "transaksi tidak dapat dilakukan"],
    ["kode" => "DS", "keterangan" => "Kartu ATM Disable", "jenis" => "All", "tindakan" => "seluruh nasabah menghubungi unit kerja untuk penggantian kartu"],
    ["kode" => "AE", "keterangan" => "sudah absen", "jenis" => "card service", "tindakan" => "salah input pin kartu, pastikan input pin kartu benar"],
    ["kode" => "99", "keterangan" => "jaringan komunikasi down", "jenis" => "all", "tindakan" => "hubungi petugas, cek koneksi (osd)"],
    ["kode" => "98", "keterangan" => "Melebihi limit transaksi harian", "jenis" => "All", "tindakan" => "Transaksi tidak dapat dilakukan"],
    ["kode" => "97", "keterangan" => "cryptograpic error", "jenis" => "All", "tindakan" => "Hubungi petugas, cek koneksi(OSD) atau lakukan LOG"],
    ["kode" => "96", "keterangan" => "Switch down", "jenis" => "All", "tindakan" => "Hubungi petugas, cek koneksi(OSD)"],
    ["kode" => "93", "keterangan" => "Double sequence number", "jenis" => "All", "tindakan" => "Ulangi transaksi"],
    ["kode" => "92", "keterangan" => "invalid area kode or phone", "jenis" => "payment telkom", "tindakan" => "masukan kode area nomor telpon yang benar"],
    ["kode" => "91", "keterangan" => "Schedule down", "jenis" => "All", "tindakan" => "Hubungi petugas, cek koneksi(OSD)"],
    ["kode" => "82", "keterangan" => "amount transaction overlimit", "jenis" => "All", "tindakan" => "cek pada belakang kartu ATM posisi pojok kanan atas, pastikan tidak menggunakan kartu pengganti sejenis"],
    ["kode" => "81", "keterangan" => "NO Telepon Expired", "jenis" => "Payment Telkom", "tindakan" => "Selesaikan masalah pembayaran dengan Telkom"],
    ["kode" => "76", "keterangan" => "Nomor tidak ada di database pihak ketiga", "jenis" => "Payment", "tindakan" => "Masukkan nomor yang benar"],
    ["kode" => "75", "keterangan" => "Salah PIN 3 kali", "jenis" => "All", "tindakan" => "Kartu sudah terblokir dan tidak dapat digunakan. Hubungi kanca"],
    ["kode" => "69", "keterangan" => "Force PIN", "jenis" => "All", "tindakan" => "Silahkan Lakukan Penggantian PIN"],
    ["kode" => "68", "keterangan" => "time out", "jenis" => "all", "tindakan" => "hubungi petugas, cek koneksi (osd)"],
    ["kode" => "65", "keterangan" => "exceeds withdrawal frequency limit", "jenis" => "all", "tindakan" => "coba lagi di hari berikutnya"],
    ["kode" => "63", "keterangan" => "transaksi tidak dapat direversal", "jenis" => "all", "tindakan" => "lakukan clear reversal kemudian ulangi transaksi"],
    ["kode" => "62", "keterangan" => "kartu tidak diijinkan bertransaksi", "jenis" => "all", "tindakan" => "kartu tidak diijinkan bertransaksi"],
    ["kode" => "61", "keterangan" => "Transaksi melebihi limit trx harian", "jenis" => "All", "tindakan" => "Masukkan nominal yang lebih kecil dari limit"],
    ["kode" => "59", "keterangan" => "kartu tidak aktif", "jenis" => "all", "tindakan" => "hubungi customer service"],
    ["kode" => "58", "keterangan" => "Taransaksi tidak di Ijinkan", "jenis" => "All", "tindakan" => "Transaksi Tidak dapat dilakukan"],
    ["kode" => "57", "keterangan" => "No Rekening Tujuan Salah", "jenis" => "All", "tindakan" => "Masukkan no rekening yang benar"],
    ["kode" => "56", "keterangan" => "rekening tujuan pasif", "jenis" => "all", "tindakan" => "transaksi tidak dapat dilakukan"],
    ["kode" => "55", "keterangan" => "transfer to same account invalid", "jenis" => "transfer", "tindakan" => "masukkan no rekening yang benar"],
    ["kode" => "54", "keterangan" => "expired card", "jenis" => "all", "tindakan" => "transaksi tidak dapat dilakukan"],
    ["kode" => "53", "keterangan" => "no tujuan tidak ada", "jenis" => "transfer rekening bri, payment telkomsel, payment indosat, payment syb, purchase simpati, purchase m", "tindakan" => "masukkan no rekening yang benar"],
    ["kode" => "51", "keterangan" => "Saldo Tidak Cukup", "jenis" => "All", "tindakan" => "Transaksi Tidak Dapat Dilakukan"],
    ["kode" => "50", "keterangan" => "pin salah", "jenis" => "all", "tindakan" => "masukkan pin yang benar"],
    ["kode" => "41", "keterangan" => "Kartu dilaporkan hilang", "jenis" => "All", "tindakan" => "Kartu tidak diijinkan bertransaksi"],
    ["kode" => "38", "keterangan" => "Salah PIN 3 kali", "jenis" => "All", "tindakan" => "Kartu sudah terblokir dan tidak dapat digunakan Hubungi Kanca tempat membuka rekening"],
    ["kode" => "13", "keterangan" => "invalid amount karena field amount berisi 0 atau tidak sama", "jenis" => "pembayaran tagihan pembelian pulsa", "tindakan" => "masukkan amount atau pilihan nominal yang benar"],
    ["kode" => "12", "keterangan" => "transaksi dilarang karena flow transaksi tidak val", "jenis" => "All", "tindakan" => "Lakukan flow transaksi yang sesuai"],
    ["kode" => "11", "keterangan" => "kartu telah diaktifkan", "jenis" => "all", "tindakan" => "ulangi transaksi"],
    ["kode" => "10", "keterangan" => "status kartu closed", "jenis" => "all", "tindakan" => "kartu sudah terblokir dan tidak dapat digunakan. hubungi kanca"],
    ["kode" => "09", "keterangan" => "Transaksi dilarang", "jenis" => "All", "tindakan" => "Kartu tidak diijinkan transaksi"],
    ["kode" => "08", "keterangan" => "no rekening tujuan salah", "jenis" => "payment anz, payment", "tindakan" => "masukkan no rekening yang benar"],
    ["kode" => "07", "keterangan" => "kartu terblokir", "jenis" => "all", "tindakan" => "kartu tidak diijinkan transaksi"],
    ["kode" => "06", "keterangan" => "no kartu tidak terdaftar", "jenis" => "inquiry kk", "tindakan" => "masukkan no kartu yang benar"],
    ["kode" => "05", "keterangan" => "Merchant ID/Terminal ID salah", "jenis" => "-", "tindakan" => "Solusi cek fungsi f12 dan verifone cek setup kordinasi dengan MAC 021-7884 3113 - Batas trx transfer 10jt per transaksi"],
    ["kode" => "04", "keterangan" => "Kartu dicurigai", "jenis" => "All", "tindakan" => "Kartu tidak diijinkan transaksi (indikasi penipuan)"],
    ["kode" => "03", "keterangan" => "invalid merchant", "jenis" => "all", "tindakan" => "hubungi petugas"],
    ["kode" => "02", "keterangan" => "proses akhir hari", "jenis" => "All", "tindakan" => "transaksi tidak dapat dilakukan"],
    ["kode" => "01", "keterangan" => "proses akhir hari", "jenis" => "All", "tindakan" => "transaksi tidak dapat dilakukan"],
    ["kode" => "00", "keterangan" => "berhasil", "jenis" => "all", "tindakan" => "tidak ada"]
    // ... semua data error lainnya tetap sama ...
];

// Default entries per page
$entriesOptions = [10, 25, 50, 100];
$defaultEntries = 25;
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Kode Error EDC</title>
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

        /* Content */
        .content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.4s ease;
            margin-top: 60px;
        }

        /* Table */
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #007bff;
        }

        .table-responsive {
            background: white;
            padding: 10px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .table thead th {
            background: #007bff;
            color: white;
            text-align: center;
        }

        .table tbody td {
            text-align: left;
            vertical-align: middle;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .table-header div {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-header select,
        .table-header input {
            max-width: 200px;
        }

        .pagination-container {
            text-align: center;
            margin-top: 10px;
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

            .table-header {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }

            .table-header div {
                justify-content: space-between;
            }

            .table-header select,
            .table-header input {
                width: 48%;
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
                    <li><a href="notfound.php" class="nav-link">NOP Berulang</a></li>
                </ul>
            </li>

            <!-- Merchant -->
            <li class="nav-item">
                <a href="#" class="nav-link" data-toggle="submenu" data-target="merchantMenu" data-arrow="merchantArrow" title="Merchant">
                    <i class="fas fa-store"></i><span>Merchant</span><i class="fas fa-angle-left right" id="merchantArrow"></i>
                </a>
                <ul class="submenu" id="merchantMenu">
                    <li><a href="wpe_user.php" class="nav-link">Reliability</a></li>
                    <li><a href="notfound.php" class="nav-link">Produktifitas</a></li>
                    <li><a href="notfound.php" class="nav-link">Reliability FMS</a></li>
                    <li><a href="notfound.php" class="nav-link">Time Series</a></li>
                    <li><a href="notfound.php" class="nav-link">Group Uker</a></li>
                    <li><a href="notfound.php" class="nav-link">Group Vendor</a></li>
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
                    <li><a href="notfound.php" class="nav-link <?= $currentPage == 'produk_bri.php' ? 'active' : ''; ?>">Produktifitas</a></li>
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
            <a href="logout.php" style="color:white;" title="Logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Content -->
    <div class="content">
        <h2>Cek Kode Error EDC</h2>

        <div class="table-header">
            <div>
                Show
                <select id="entriesSelect" class="form-select d-inline-block">
                    <?php foreach ($entriesOptions as $opt): ?>
                        <option value="<?= $opt ?>" <?= $opt == $defaultEntries ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                </select>
                entries
            </div>
            <div>
                <input type="text" id="searchInput" class="form-control" placeholder="Search kode error...">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="errorTable">
                <thead>
                    <tr>
                        <th>Kode Error</th>
                        <th>Keterangan</th>
                        <th>Jenis Transaksi</th>
                        <th>Yang Harus Dilakukan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($errors as $e): ?>
                        <tr>
                            <td><?= $e['kode'] ?></td>
                            <td><?= $e['keterangan'] ?></td>
                            <td><?= $e['jenis'] ?></td>
                            <td><?= $e['tindakan'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination-container" id="pagination"></div>
    </div>

    <script>
        // Submenu toggle
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
        // Sidebar collapse
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
        // Auto buka submenu aktif
        document.querySelectorAll('.submenu').forEach(sub => {
            if (sub.querySelector('.active')) {
                sub.classList.add('show');
                const arrow = document.getElementById(sub.id.replace('Menu', 'Arrow'));
                if (arrow) {
                    arrow.classList.replace('fa-angle-left', 'fa-angle-down');
                }
            }
        });

        // Table filter & pagination
        const searchInput = document.getElementById('searchInput');
        const entriesSelect = document.getElementById('entriesSelect');
        const tableRows = Array.from(document.querySelectorAll('#errorTable tbody tr'));
        const paginationContainer = document.getElementById('pagination');
        let currentPage = 1;

        function displayTable() {
            const limit = parseInt(entriesSelect.value);
            const query = searchInput.value.toLowerCase();
            const filteredRows = tableRows.filter(row => Array.from(row.cells).some(cell => cell.textContent.toLowerCase().includes(query)));
            const totalPages = Math.ceil(filteredRows.length / limit);
            if (currentPage > totalPages) currentPage = totalPages || 1;
            tableRows.forEach(row => row.style.display = 'none');
            filteredRows.slice((currentPage - 1) * limit, currentPage * limit).forEach(row => row.style.display = 'table-row');

            // Pagination buttons
            paginationContainer.innerHTML = '';
            for (let i = 1; i <= totalPages; i++) {
                const btn = document.createElement('button');
                btn.textContent = i;
                btn.className = 'btn btn-sm mx-1 ' + (i === currentPage ? 'btn-primary' : 'btn-outline-primary');
                btn.addEventListener('click', () => {
                    currentPage = i;
                    displayTable();
                });
                paginationContainer.appendChild(btn);
            }
        }
        searchInput.addEventListener('input', () => {
            currentPage = 1;
            displayTable();
        });
        entriesSelect.addEventListener('change', () => {
            currentPage = 1;
            displayTable();
        });
        displayTable();
    </script>

</body>

</html>