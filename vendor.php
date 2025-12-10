<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['level'] != 'user') {
    header("Location: login.php");
    exit;
}

// =================== KONEKSI DATABASE ===================
$host = "localhost";
$user = "root";
$pass = "";
$db   = "edc_login_db2";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// =================== CEK TAHUN DAN RESET DATA ===================
$tahunSekarang = date('Y');
$res = $conn->query("SELECT MAX(tahun) AS lastYear FROM vendor");
$lastYear = $res->fetch_assoc()['lastYear'] ?? $tahunSekarang;

if ($lastYear < $tahunSekarang) {
    $conn->query("DELETE FROM vendor WHERE tahun < $tahunSekarang");
}

// =================== QUERY DATA VENDOR ===================
$sql = "
SELECT 
    TRIM(Vendor) AS VendorName,
    COUNT(*) AS Total,
    SUM(CASE WHEN Last_Availability IS NOT NULL AND TIMESTAMPDIFF(MINUTE, Last_Availability, NOW()) < 60 THEN 1 ELSE 0 END) AS Less1Hour,
    SUM(CASE WHEN Last_Availability IS NOT NULL AND TIMESTAMPDIFF(HOUR, Last_Availability, NOW()) BETWEEN 1 AND 24 THEN 1 ELSE 0 END) AS Hour1to24,
    SUM(CASE WHEN Last_Availability IS NOT NULL AND DATE(Last_Availability) = CURDATE() THEN 1 ELSE 0 END) AS TodayCount,
    SUM(CASE WHEN Last_Availability IS NOT NULL AND DATE(Last_Availability) BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) AS Days1to7,
    SUM(CASE WHEN Last_Availability IS NOT NULL AND DATE(Last_Availability) < DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS MoreThan7Days
FROM wpe
WHERE Vendor IN (
    'PT.PRIMA VISTA SOLUSI', 
    'PT. PASIFIK CIPTA SOLUSI', 
    'PT. BRINGIN INTI TEKNOLOGI'
)
GROUP BY VendorName
ORDER BY VendorName
";

$result = $conn->query($sql);

// =================== PENGOLAHAN DATA ===================
$data = [];
$totals = [
    'Total' => 0,
    '<1Jam' => 0,
    '1_24Jam' => 0,
    'Today' => 0,
    '1_7Hari' => 0,
    '>7Hari' => 0,
    'Reliability' => 0
];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reliability = $row['Total'] > 0 ? (($row['Total'] - $row['MoreThan7Days']) / $row['Total']) * 100 : 0;

        // =================== SIMPAN KE TABEL vendor ===================
        $vendorCol = '';
        if ($row['VendorName'] == 'PT. BRINGIN INTI TEKNOLOGI') $vendorCol = 'bit';
        elseif ($row['VendorName'] == 'PT. PASIFIK CIPTA SOLUSI') $vendorCol = 'pcs';
        elseif ($row['VendorName'] == 'PT.PRIMA VISTA SOLUSI') $vendorCol = 'pvs';

        if ($vendorCol != '') {
            $tanggal = date('j');      
            $bulanIndex = date('n');   
            $namaBulan = [1=>"Januari",2=>"Februari",3=>"Maret",4=>"April",5=>"Mei",6=>"Juni",7=>"Juli",8=>"Agustus",9=>"September",10=>"Oktober",11=>"November",12=>"Desember"];
            $bulanNow = $namaBulan[$bulanIndex];
            $colName = "d" . $tanggal . "_" . $vendorCol;

            $sqlSave = "UPDATE vendor SET $colName = $reliability, tahun = $tahunSekarang WHERE bulan = '$bulanNow'";
            $conn->query($sqlSave);
        }

        $data[] = [
            'VendorName' => $row['VendorName'],
            'Total' => $row['Total'],
            '<1Jam' => $row['Less1Hour'],
            '1_24Jam' => $row['Hour1to24'],
            'Today' => $row['TodayCount'],
            '1_7Hari' => $row['Days1to7'],
            '>7Hari' => $row['MoreThan7Days'],
            'Reliability' => round($reliability,2)
        ];

        $totals['Total'] += $row['Total'];
        $totals['<1Jam'] += $row['Less1Hour'];
        $totals['1_24Jam'] += $row['Hour1to24'];
        $totals['Today'] += $row['TodayCount'];
        $totals['1_7Hari'] += $row['Days1to7'];
        $totals['>7Hari'] += $row['MoreThan7Days'];
        $totals['Reliability'] += $reliability;
    }
}

if(count($data)>0) $totals['Reliability'] = round($totals['Reliability']/count($data),2);

// =================== FUNGSI WARNA ===================
function reliabilityClass($v){
    if($v>=95) return "dark-green";
    if($v>=90) return "light-green";
    if($v>=85) return "yellow";
    if($v>=80) return "orange";
    return "red";
}

// =================== LAST UPDATE ===================
date_default_timezone_set('Asia/Jakarta');
$lastUpdate = date('d F Y H:i');

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Monitoring Group Vendor</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
<style>
/* === SIDEBAR === */
:root{
    --sidebar-bg:#212529;
    --sidebar-hover:#495057;
    --topbar-bg:#007bff;
    --text-color:white;
    --body-bg:#f4f6f9;
}
body { margin:0;font-family:Arial,sans-serif;color:var(--text-color); background: var(--body-bg);}
.sidebar {width:250px;height:100vh;position:fixed;top:0;left:0;background:var(--sidebar-bg);overflow-y:auto;padding-top:10px;transition:width 0.4s, left 0.3s; box-shadow:2px 0 8px rgba(0,0,0,0.2);}
.sidebar.collapsed{width:70px;}
.sidebar .nav-link{display:flex;align-items:center;padding:10px 20px;color:var(--text-color);text-decoration:none;font-size:14px;position:relative;transition:all 0.3s;}
.sidebar .nav-link:hover, .sidebar .nav-link.active{background:var(--sidebar-hover);}
.sidebar .nav-link i{width:25px;text-align:center;margin-right:10px;transition:transform 0.3s;}
.sidebar.collapsed .nav-link span{display:none;}
.sidebar.collapsed .nav-link i{margin-right:0;}
.sidebar.collapsed .nav-link[title]:hover::after{content:attr(title);position:absolute;left:70px;background:rgba(0,0,0,0.85);color:#fff;padding:5px 10px;border-radius:4px;white-space:nowrap;font-size:13px;z-index:999;}
.submenu{max-height:0;overflow:hidden;transition:max-height 0.4s;padding-left:30px;}
.submenu.show{max-height:500px;}
.topbar{background:var(--topbar-bg);color:white;height:60px;position:fixed;top:0;left:250px;right:0;z-index:1000;display:flex;justify-content:space-between;align-items:center;padding:0 20px;transition:left 0.4s;}
.topbar a{color:white !important;font-weight:bold;margin-left:15px;text-decoration:none;}
.topbar a:hover{color:#ffdd57 !important;text-decoration:underline;}
.content{margin-left:250px;margin-top:60px;padding:20px;transition:margin-left 0.4s; color:black;}

/* === DATA VENDOR === */
h2{color:#1e1e2f;margin-top:25px;font-weight:600;}
.card{background:#fff;border-radius:12px;box-shadow:0 3px 15px rgba(0,0,0,0.1);padding:25px;margin-top:30px;position:relative;}
.last-update{position:absolute;top:10px;left:25px;font-size:13px;color:#555;font-style:italic;}
table.dataTable{background:#fff;border-radius:8px;overflow:hidden;}
table.dataTable th{background:linear-gradient(90deg,#007bff,#0056b3);color:white;text-transform:uppercase;font-size:13px;}
table.dataTable td{vertical-align:middle;font-size:14px;}
table.dataTable tr:nth-child(even){background-color:#f9fafc;}
.dark-green{background-color:#006400 !important;color:white !important;}
.light-green{background-color:#90ee90 !important;color:black !important;}
.yellow{background-color:#ffff66 !important;color:black !important;}
.orange{background-color:#ff9900 !important;color:black !important;}
.red{background-color:#ff4d4d !important;color:white !important;}
tfoot td{font-weight:bold;background-color:#e9ecef !important;}
.legend-box{background:#fff;border-radius:10px;padding:15px 20px;box-shadow:0 2px 8px rgba(0,0,0,0.1);color:black;margin:25px auto;border-top:4px solid #007bff;max-width:420px;}
.legend-list{list-style:none;padding:0;margin:10px 0 0 0;}
.legend-list li{margin-bottom:6px;font-size:14px;}
</style>
<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
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
                    <li><a href="nop_merchant.php" class="nav-link">NOP Berulang</a></li>
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


<!-- TOPBAR -->
<div class="topbar">
    <div></div>
    <div>
        <span>👤 <?= $_SESSION['username']; ?></span>
        <a href="logout.php" title="Logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<!-- CONTENT -->
<div class="content">
    <div class="container text-center">
        <h2>📊 Monitoring Group Vendor</h2>
        <div class="card mx-auto" style="max-width: 1100px;">
            <div class="last-update">Last Update: <?= $lastUpdate ?> WIB</div>
            <div class="table-responsive mt-4">
                <table id="vendorTable" class="display table table-bordered text-center">
                    <thead>
                        <tr>
                            <th>Vendor</th>
                            <th>Total</th>
                            <th>&lt;1 Jam</th>
                            <th>1–24 Jam</th>
                            <th>Today</th>
                            <th>1–7 Hari</th>
                            <th>&gt;7 Hari</th>
                            <th>Reliability (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($data)): foreach($data as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['VendorName']) ?></td>
                            <td><?= $r['Total'] ?></td>
                            <td><?= $r['<1Jam'] ?></td>
                            <td><?= $r['1_24Jam'] ?></td>
                            <td><?= $r['Today'] ?></td>
                            <td><?= $r['1_7Hari'] ?></td>
                            <td><?= $r['>7Hari'] ?></td>
                            <td class="<?= reliabilityClass($r['Reliability']) ?>"><?= number_format($r['Reliability'],2) ?>%</td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="8">Data tidak ditemukan</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td><strong>TOTAL</strong></td>
                            <td><?= $totals['Total'] ?></td>
                            <td><?= $totals['<1Jam'] ?></td>
                            <td><?= $totals['1_24Jam'] ?></td>
                            <td><?= $totals['Today'] ?></td>
                            <td><?= $totals['1_7Hari'] ?></td>
                            <td><?= $totals['>7Hari'] ?></td>
                            <td class="<?= reliabilityClass($totals['Reliability']) ?>"><?= number_format($totals['Reliability'],2) ?>%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="legend-box">
            <h5 class="mb-2"><strong>INFO</strong></h5>
            <ul class="legend-list text-start">
                <li>≥ 95% → <span class="dark-green px-2 rounded">Hijau Tua</span></li>
                <li>&lt;95% – ≤90% → <span class="light-green px-2 rounded">Hijau Muda</span></li>
                <li>&lt;90% – ≤85% → <span class="yellow px-2 rounded">Kuning</span></li>
                <li>&lt;85% – ≤80% → <span class="orange px-2 rounded">Oranye</span></li>
                <li>&lt;80% → <span class="red px-2 rounded">Merah</span></li>
            </ul>
            <p class="mt-3"><strong>Ratas saldo CASA</strong> diupdate H-2</p>
        </div>
    </div>
</div>

<script>
document.querySelectorAll("[data-toggle='submenu']").forEach(toggle=>{
    toggle.addEventListener("click",function(e){
        e.preventDefault();
        const target=document.getElementById(this.dataset.target);
        const arrow=document.getElementById(this.dataset.arrow);
        document.querySelectorAll(".submenu").forEach(m=>m!==target&&m.classList.remove("show"));
        document.querySelectorAll(".right").forEach(i=>i!==arrow&&i.classList.replace("fa-angle-down","fa-angle-left"));
        target.classList.toggle("show");
        arrow.classList.toggle("fa-angle-down");
        arrow.classList.toggle("fa-angle-left");
    });
});

const sidebar=document.querySelector('.sidebar');
const content=document.querySelector('.content');
const topbar=document.querySelector('.topbar');
const sidebarToggle=document.getElementById('sidebarToggle');

if(localStorage.getItem('sidebar-collapsed')==='true'){
    sidebar.classList.add('collapsed');
    content.style.marginLeft='70px';
    topbar.style.left='70px';
}

sidebarToggle.addEventListener('click',()=>{
    sidebar.classList.toggle('collapsed');
    const collapsed=sidebar.classList.contains('collapsed');
    content.style.marginLeft=collapsed?'70px':'250px';
    topbar.style.left=collapsed?'70px':'250px';
    localStorage.setItem('sidebar-collapsed',collapsed);
});

$('#vendorTable').DataTable({
    paging:false, searching:false, info:false, autoWidth:true, ordering:false,
    columnDefs:[{className:"dt-center",targets:"_all"}]
});
</script>
</body>
</html>
