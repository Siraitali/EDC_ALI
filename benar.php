<?php
// ==========================
// wpe_user.php (tanpa sidebar, login, dan toolbar — klik tetap aktif)
// ==========================

$host = "localhost";
$user = "root";
$pass = "";
$db   = "edc_login_db2";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil tanggal terakhir
$tanggalTerakhirResult = $conn->query("SELECT MAX(Tanggal_Input) AS terakhir FROM wpe");
$tanggalTerakhirRow = $tanggalTerakhirResult->fetch_assoc();
$tanggalTerakhir = $tanggalTerakhirRow['terakhir'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reliability EDC Merchant</title>

    <!-- Bootstrap & DataTables -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #eef2ff, #ffffff);
            font-family: "Poppins", sans-serif;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        th {
            background: linear-gradient(90deg, #0d6efd, #3a8bfd);
            color: white;
            text-align: center;
            vertical-align: middle;
        }

        td {
            vertical-align: middle;
            text-align: center;
        }

        td.clickable {
            color: #0d6efd;
            font-weight: 500;
        }

        td.clickable:hover {
            cursor: pointer;
            text-decoration: underline;
        }

        tfoot td {
            font-weight: bold;
            background-color: #f8f9fa;
            text-align: center;
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container" data-aos="fade-up">
        <h2>Reliability EDC Merchant</h2>

        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <span class="fw-bold">Data Tanggal:</span>
                    <span class="ms-2"><?= $tanggalTerakhir ? date('d-m-Y H:i:s', strtotime($tanggalTerakhir)) : '-' ?></span>
                </div>
                <div id="dataTableFilter"></div>
            </div>

            <div class="table-responsive">
                <table id="wpeUserTable" class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Branch Office</th>
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
                        <?php
                        $sql = "
                            SELECT 
                                Mainbranch_Nama_Pemrakarsa AS BranchOffice,
                                COUNT(*) AS Total,
                                SUM(CASE WHEN TIMESTAMPDIFF(HOUR, Last_Availability, NOW()) < 1 THEN 1 ELSE 0 END) AS Kurang1Jam,
                                SUM(CASE WHEN TIMESTAMPDIFF(HOUR, Last_Availability, NOW()) BETWEEN 1 AND 24 THEN 1 ELSE 0 END) AS Antara1dan24Jam,
                                SUM(CASE WHEN DATE(Last_Availability) = CURDATE() THEN 1 ELSE 0 END) AS Today,
                                SUM(CASE WHEN TIMESTAMPDIFF(DAY, Last_Availability, NOW()) BETWEEN 1 AND 7 THEN 1 ELSE 0 END) AS Antara1dan7Hari,
                                SUM(CASE WHEN TIMESTAMPDIFF(DAY, Last_Availability, NOW()) > 7 THEN 1 ELSE 0 END) AS Lebih7Hari
                            FROM wpe
                            WHERE Mainbranch_Nama_Pemrakarsa IS NOT NULL 
                              AND TRIM(Mainbranch_Nama_Pemrakarsa) <> ''
                            GROUP BY Mainbranch_Nama_Pemrakarsa
                            ORDER BY Mainbranch_Nama_Pemrakarsa ASC
                        ";
                        $result = $conn->query($sql);

                        $totalSemua = $kurang1JamSemua = $antara1dan24Semua = $todaySemua = $antara1dan7Semua = $lebih7HariSemua = 0;
                        $sumReliability = 0;
                        $branchCount = 0;

                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $reliability = $row['Total'] > 0 ? (($row['Total'] - $row['Lebih7Hari']) / $row['Total']) * 100 : 0;
                                $reliabilityFormatted = number_format($reliability, 2);

                                $sumReliability += $reliability;
                                $branchCount++;

                                if ($reliability >= 95) {
                                    $badge = '<span class="badge bg-success">' . $reliabilityFormatted . '%</span>';
                                } elseif ($reliability >= 90) {
                                    $badge = '<span class="badge bg-warning text-dark">' . $reliabilityFormatted . '%</span>';
                                } else {
                                    $badge = '<span class="badge bg-danger">' . $reliabilityFormatted . '%</span>';
                                }

                                echo "<tr>";
                                echo "<td class='clickable' onclick=\"window.location='wpe_detail.php?branch=" . urlencode($row['BranchOffice']) . "'\">" . htmlspecialchars($row['BranchOffice']) . "</td>";
                                echo "<td>{$row['Total']}</td>";
                                echo "<td>{$row['Kurang1Jam']}</td>";
                                echo "<td>{$row['Antara1dan24Jam']}</td>";
                                echo "<td>{$row['Today']}</td>";
                                echo "<td>{$row['Antara1dan7Hari']}</td>";
                                echo "<td>{$row['Lebih7Hari']}</td>";
                                echo "<td>$badge</td>";
                                echo "</tr>";

                                $totalSemua += $row['Total'];
                                $kurang1JamSemua += $row['Kurang1Jam'];
                                $antara1dan24Semua += $row['Antara1dan24Jam'];
                                $todaySemua += $row['Today'];
                                $antara1dan7Semua += $row['Antara1dan7Hari'];
                                $lebih7HariSemua += $row['Lebih7Hari'];
                            }
                        } else {
                            echo "<tr><td colspan='8'>Tidak ada data Branch Office yang valid.</td></tr>";
                        }

                        $avgReliability = $branchCount > 0 ? $sumReliability / $branchCount : 0;
                        $avgReliabilityFormatted = number_format($avgReliability, 2);
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td><strong>TOTAL</strong></td>
                            <td><?= $totalSemua ?></td>
                            <td><?= $kurang1JamSemua ?></td>
                            <td><?= $antara1dan24Semua ?></td>
                            <td><?= $todaySemua ?></td>
                            <td><?= $antara1dan7Semua ?></td>
                            <td><?= $lebih7HariSemua ?></td>
                            <td><?= $avgReliabilityFormatted ?>%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();
        $(document).ready(function() {
            var table = $('#wpeUserTable').DataTable({
                "pageLength": 24,
                "lengthChange": false,
                "order": [[0, "asc"]],
                "language": {
                    "search": "Cari:",
                    "zeroRecords": "Tidak ditemukan data yang sesuai",
                    "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    "infoEmpty": "Tidak ada data tersedia",
                    "paginate": {
                        "first": "Awal",
                        "last": "Akhir",
                        "next": "›",
                        "previous": "‹"
                    }
                }
            });
            $('#dataTableFilter').append($('.dataTables_filter'));
        });
    </script>
</body>
</html>
