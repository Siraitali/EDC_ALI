<?php
// ==========================
// KONEKSI DATABASE
// ==========================
$host = "localhost";
$user = "root";
$pass = "";
$db   = "edc_login_db2";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// ==========================
// AMBIL MERCHANT NOP ≥ 8 HARI
// ==========================
$sql = "
    SELECT MID, TID, Nama_Merchant, Vendor, Last_Availability
    FROM wpe
    WHERE Last_Availability IS NOT NULL
    AND Last_Availability <> ''
    AND DATEDIFF(NOW(), Last_Availability) >= 8
    ORDER BY Last_Availability ASC
";
$result = $conn->query($sql);

// ==========================
// INSERT KE LOG JIKA BELUM ADA
// ==========================
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $MID  = $conn->real_escape_string($row['MID']);
        $TID  = $conn->real_escape_string($row['TID']);
        $Nama = $conn->real_escape_string($row['Nama_Merchant']);
        $Vendor = $conn->real_escape_string($row['Vendor']);
        $Last_Lama = $conn->real_escape_string($row['Last_Availability']);

        $cekInsert = $conn->query("
            SELECT id FROM nop_merchant_log
            WHERE TID='$TID'
            AND Last_Availability='$Last_Lama'
        ");

        if ($cekInsert->num_rows == 0) {
            $conn->query("
                INSERT INTO nop_merchant_log
                (MID, TID, Nama_Merchant, Vendor, Tanggal_Aktif, Last_Availability, Log_Date)
                VALUES ('$MID', '$TID', '$Nama', '$Vendor', NULL, '$Last_Lama', NOW())
            ");
        }
    }
}

// ==========================
// UPDATE JIKA MERCHANT AKTIF LAGI
// ==========================
$logs = $conn->query("
    SELECT id, TID, Last_Availability 
    FROM nop_merchant_log 
    WHERE Tanggal_Aktif IS NULL
");

if ($logs && $logs->num_rows > 0) {
    while ($log = $logs->fetch_assoc()) {
        $id = $log['id'];
        $TID = $conn->real_escape_string($log['TID']);
        $Last_Lama = $log['Last_Availability'];

        $wpe = $conn->query("SELECT Last_Availability FROM wpe WHERE TID='$TID' LIMIT 1");
        if ($wpe && $wpe->num_rows > 0) {
            $Last_Baru = $wpe->fetch_assoc()['Last_Availability'];
            if ($Last_Baru > $Last_Lama) {
                $conn->query("
                    UPDATE nop_merchant_log
                    SET Tanggal_Aktif='$Last_Baru'
                    WHERE id='$id'
                ");
            }
        }
    }
}

// REFRESH QUERY UTAMA
$result = $conn->query($sql);

// ==========================
// REKAP JUMLAH NOP
// ==========================
$rekapNOP = $conn->query("
    SELECT 
        MID,
        TID,
        Nama_Merchant,
        Vendor,
        COUNT(*) AS Jumlah_NOP
    FROM nop_merchant_log
    GROUP BY MID, TID, Nama_Merchant, Vendor
    HAVING COUNT(*) > 1
    ORDER BY Jumlah_NOP DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Merchant NOP</title>

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <!-- JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <style>
        body {
            background: #f4f6f9;
            font-family: 'Segoe UI', sans-serif;
        }

        h2 {
            text-align: center;
            margin: 10px 0; /* lebih rapat */
            color: #dc3545;
            font-weight: 600;
        }

        .card {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 20px; /* rapat antar card */
        }

        table.dataTable thead th {
            background-color: #dc3545;
            color: white;
            text-align: center;
            white-space: nowrap;
        }

        table.dataTable tbody td {
            text-align: center;
            vertical-align: middle;
        }

        .highlight-red {
            color: #dc3545;
            font-weight: bold;
        }

        /* MAIN CONTENT SMOOTH ANIMATION */
        .main-content {
            margin-left: 250px;
            padding: 15px; 
            width: calc(100% - 250px);
            transition: all 0.5s ease;
            margin-top: 20px; /* lebih dekat ke toolbar */
        }

        body.sidebar-collapsed .main-content {
            margin-left: 70px;
            width: calc(100% - 70px);
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            transition: all 0.5s ease;
        }

        table {
            width: 100% !important;
        }
    </style>
</head>
<body>

<!-- PANGGIL SIDEBAR -->
<?php include 'sidebaruser.php'; ?>

<!-- KONTEN UTAMA -->
<div class="main-content">

    <h2>NOP Terbaru</h2>
    <div class="card p-2">
        <div class="table-responsive">
            <table id="dataTable" class="table table-striped table-hover table-bordered w-100">
                <thead>
                    <tr>
                        <th>MID</th>
                        <th>TID</th>
                        <th>Nama Merchant</th>
                        <th>Vendor</th>
                        <th>Last Availability</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['MID']) ?></td>
                            <td><?= htmlspecialchars($row['TID']) ?></td>
                            <td><?= htmlspecialchars($row['Nama_Merchant']) ?></td>
                            <td><?= htmlspecialchars($row['Vendor']) ?></td>
                            <td class="highlight-red"><?= htmlspecialchars($row['Last_Availability']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5">Tidak ada data</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <h2>NOP Berulang</h2>
    <div class="card p-2">
        <div class="table-responsive">
            <table id="dataTableRekap" class="table table-striped table-hover table-bordered w-100">
                <thead>
                    <tr>
                        <th>MID</th>
                        <th>TID</th>
                        <th>Nama Merchant</th>
                        <th>Vendor</th>
                        <th>Jumlah NOP</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($rekapNOP && $rekapNOP->num_rows > 0): ?>
                    <?php while ($row = $rekapNOP->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['MID']) ?></td>
                            <td><?= htmlspecialchars($row['TID']) ?></td>
                            <td><?= htmlspecialchars($row['Nama_Merchant']) ?></td>
                            <td><?= htmlspecialchars($row['Vendor']) ?></td>
                            <td class="<?= ($row['Jumlah_NOP'] > 1) ? 'highlight-red' : '' ?>">
                                <?= $row['Jumlah_NOP'] ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5">Tidak ada data</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
$(document).ready(function () {

    var table1 = $('#dataTable').DataTable({ scrollX: true, autoWidth: false });
    var table2 = $('#dataTableRekap').DataTable({ scrollX: true, autoWidth: false });

    const adjustTables = () => {
        table1.columns.adjust().draw();
        table2.columns.adjust().draw();
    };

    // Smooth resize saat toggle sidebar
    const sidebarToggle = document.getElementById('sidebarToggle');
    sidebarToggle.addEventListener('click', function () {
        setTimeout(adjustTables, 500);
    });

    // Jika reload dan sidebar collapsed
    if (localStorage.getItem('sidebar-collapsed') === 'true') {
        setTimeout(adjustTables, 200);
    }

});
</script>

</body>
</html>
