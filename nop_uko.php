<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['level'] != 'user') {
    header("Location: login.php");
    exit;
}

$currentPage = basename($_SERVER['PHP_SELF']);
// ===== AUTO LOGOUT TIDAK ADA AKTIVITAS =====
$timeout = 1800; // 30 menit

if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > $timeout) {
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=1");
        exit;
    }
}
$_SESSION['last_activity'] = time();

// Koneksi database
$host = "localhost";
$user = "root";
$password = "";
$database = "edc_login_db2";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

// Buat tabel nop_uko jika belum ada
$conn->query("
CREATE TABLE IF NOT EXISTS nop_uko (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tid VARCHAR(50),
    nama_merchant VARCHAR(255),
    nama_kanca VARCHAR(255),
    last_available DATETIME,
    hari_tidak_aktif INT,
    tl DATETIME,
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Ambil data >7 hari tidak aktif dari edc_merchant_raw
$sql = "SELECT mid, nama_merchant, uker_nama_implementor, last_available,
        DATEDIFF(NOW(), last_available) AS hari_tidak_aktif
        FROM edc_merchant_raw
        WHERE DATEDIFF(NOW(), last_available) > 7
        ORDER BY last_available ASC";

$result = $conn->query($sql);
if (!$result) die("Query gagal: " . $conn->error);

// Simpan ke tabel nop_uko (snapshot) jika belum ada last_available ini
if ($result->num_rows > 0) {
    $insert_stmt = $conn->prepare("INSERT INTO nop_uko (tid, nama_merchant, nama_kanca, last_available, hari_tidak_aktif, tl) VALUES (?, ?, ?, ?, ?, ?)");
    while ($row = $result->fetch_assoc()) {
        $tid = $row['mid'];
        $nama_merchant = $row['nama_merchant'];
        $nama_kanca = $row['uker_nama_implementor'];
        $last_available = $row['last_available'];
        $hari_tidak_aktif = $row['hari_tidak_aktif'];

        // Cek apakah kombinasi tid + last_available sudah ada
        $check_sql = $conn->prepare("SELECT id FROM nop_uko WHERE tid=? AND last_available=?");
        $check_sql->bind_param("ss", $tid, $last_available);
        $check_sql->execute();
        $check_sql->store_result();

        if ($check_sql->num_rows == 0) {
            // Ambil TL terbaru untuk MID ini
            $tl_result = $conn->query("SELECT MAX(last_available) AS tl FROM edc_merchant_raw WHERE mid='$tid'");
            $tl_row = $tl_result ? $tl_result->fetch_assoc() : null;
            $tl = $tl_row['tl'] ?? null;

            $insert_stmt->bind_param("ssssis", $tid, $nama_merchant, $nama_kanca, $last_available, $hari_tidak_aktif, $tl);
            $insert_stmt->execute();
        }
        $check_sql->close();
    }
    $insert_stmt->close();
}

// Update TL untuk semua baris di nop_uko sesuai last_active terbaru
$conn->query("UPDATE nop_uko n
             JOIN (SELECT mid, MAX(last_available) AS latest FROM edc_merchant_raw GROUP BY mid) e
             ON n.tid = e.mid
             SET n.tl = e.latest");


// Mode tampilan NOP BERULANG
$showBackButton = false;
if (isset($_GET['berulang']) && $_GET['berulang'] == '1') {
    $sql_display = "SELECT * FROM nop_uko WHERE tid IN (
                        SELECT tid FROM nop_uko GROUP BY tid HAVING COUNT(*) > 1
                    ) ORDER BY tid, last_available DESC";
    $title = "NOP BERULANG";
    $showBackButton = true;
} else {
    $sql_display = "SELECT * FROM nop_uko ORDER BY last_available DESC";
    $title = "NOP UKO";
}

$display_result = $conn->query($sql_display);
if (!$display_result) die("Query display gagal: " . $conn->error);

?>

<?php include 'sidebaruser.php'; ?>

<div class="content-wrapper" style="display: flex; flex-direction: column; min-height: 100vh;">
    <div class="content" style="flex: 1 0 auto; margin-top: 0; padding-top: 10px;">
        <div class="container-fluid" style="padding-top: 0; margin-top: 0;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="text-dark fw-bold" style="margin-top: 0; padding-top: 0;"><?php echo $title; ?></h2>
                <div>
                    <?php if ($showBackButton): ?>
                        <a href="nop_uko.php" class="btn btn-secondary">Kembali</a>
                    <?php else: ?>
                        <a href="?berulang=1" class="btn btn-warning">NOP BERULANG</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Show Baris -->
            <div class="mb-2 d-flex align-items-center">
                <label for="showRows" class="me-2">Show Baris:</label>
                <select id="showRows" class="form-select form-select-sm" style="width: auto;">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>

            <hr style="margin-top: 5px; margin-bottom: 5px;">

            <div class="card shadow-sm" style="margin-top: 10px;">
                <div class="card-body text-dark p-0">
                    <div class="table-responsive">
                        <table id="dataTable" class="table table-hover table-bordered table-striped mb-0">
                            <thead class="table-dark text-center">
                                <tr>
                                    <th style="width:10%;">TID</th>
                                    <th style="width:25%;">Nama Merchant</th>
                                    <th style="width:20%;">Nama Kanca</th>
                                    <th style="width:15%;">Last Available</th>
                                    <th style="width:15%;">Hari Tidak Aktif</th>
                                    <th style="width:15%;">Tanggal Aktif</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($display_result->num_rows > 0) {
                                    while ($row = $display_result->fetch_assoc()) {
                                        echo "<tr>
                                                <td class='text-center'>{$row['tid']}</td>
                                                <td>{$row['nama_merchant']}</td>
                                                <td>{$row['nama_kanca']}</td>
                                                <td class='text-center'>{$row['last_available']}</td>
                                                <td class='text-center'>{$row['hari_tidak_aktif']} Hari</td>
                                                <td class='text-center'>{$row['tl']}</td>
                                              </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center'>Data tidak ditemukan</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <footer style="flex-shrink: 0; text-align: center; padding: 10px; background: #f8f9fa;">
        &copy; 2025 NOP UKO
    </footer>
</div>

<?php $conn->close(); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('showRows');
    const table = document.getElementById('dataTable');
    const rows = Array.from(table.querySelectorAll('tbody tr'));

    function updateRows() {
        const show = parseInt(select.value);
        rows.forEach((row, idx) => {
            row.style.display = (idx < show) ? '' : 'none';
        });
    }

    select.addEventListener('change', updateRows);
    updateRows();
});
</script>
