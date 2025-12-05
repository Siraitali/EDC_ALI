<?php
$url = "https://brilinkbos.bri.co.id/brilink-bos/public/monitoring/getKanca?rcode=X&brand=semua";

$context = stream_context_create([
    "ssl" => ["verify_peer" => false, "verify_peer_name" => false]
]);
$json = file_get_contents($url, false, $context);

if ($json === false) {
    die("Gagal mengambil data dari API.");
}

$data = json_decode($json, true);

if (empty($data['list_data'])) {
    die("Format API tidak sesuai atau kosong.");
}

$rows = $data['list_data'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Data GetKanca (Live API)</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f7f9fb;
            padding: 20px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }

        th {
            background-color: #007BFF;
            color: white;
        }

        tr:nth-child(even) {
            background: #f2f2f2;
        }
    </style>
</head>

<body>

    <h2>Data dari API GetKanca (Tanpa Database)</h2>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Branch Code</th>
                <th>Branch Name</th>
                <th>Total Outlet</th>
                <th>Belum Aktivasi</th>
                <th>Today</th>
                <th>01 - 07</th>
                <th>08 - 15</th>
                <th>16 - 30</th>
                <th>> 30</th>
                <th>Telah Aktivasi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            foreach ($rows as $row) {
                // Pastikan key-nya aman
                $today   = $row['today'] ?? 0;
                $range1  = array_key_exists('01-07', $row) ? $row['01-07'] : 0;
                $range2  = array_key_exists('08-15', $row) ? $row['08-15'] : 0;
                $range3  = array_key_exists('16-30', $row) ? $row['16-30'] : 0;
                $last30  = $row['last30'] ?? 0;

                echo "<tr>";
                echo "<td>{$no}</td>";
                echo "<td>" . htmlspecialchars($row['branch_code'] ?? '-') . "</td>";
                echo "<td>" . htmlspecialchars($row['branch_name'] ?? '-') . "</td>";
                echo "<td>" . htmlspecialchars($row['total_outlet'] ?? '0') . "</td>";
                echo "<td>" . htmlspecialchars($row['activation_not_perfect'] ?? '0') . "</td>";
                echo "<td>" . htmlspecialchars($today) . "</td>";
                echo "<td>" . htmlspecialchars($range1) . "</td>";
                echo "<td>" . htmlspecialchars($range2) . "</td>";
                echo "<td>" . htmlspecialchars($range3) . "</td>";
                echo "<td>" . htmlspecialchars($last30) . "</td>";
                echo "<td>" . htmlspecialchars($row['activation_perfect'] ?? '0') . "</td>";
                echo "</tr>";
                $no++;
            }
            ?>
        </tbody>
    </table>

</body>

</html>