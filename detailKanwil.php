<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['level'] != 'user') {
    header("Location: login.php");
    exit;
}

$host = "localhost";
$user = "root";
$pass = "";
$db = "edc_login_db2";
$koneksi = new mysqli($host, $user, $pass, $db);
if ($koneksi->connect_error) die("Koneksi gagal: " . $koneksi->connect_error);

// Ambil parameter dari URL
$kancaRaw = isset($_GET['kanca']) ? urldecode($_GET['kanca']) : '';
$kanca = $koneksi->real_escape_string($kancaRaw);
$kolomRaw = isset($_GET['kolom']) ? urldecode($_GET['kolom']) : 'All';
$kolom = $kolomRaw;
$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Ambil semua data latest per MerchantMID dan filter Kanca (trim+lower)
$query = "
SELECT d1.*
FROM detailbri d1
INNER JOIN (
    SELECT MerchantMID, MAX(created_at) AS latest
    FROM detailbri
    GROUP BY MerchantMID
) d2 ON d1.MerchantMID=d2.MerchantMID AND d1.created_at=d2.latest
WHERE LOWER(TRIM(d1.Kanca)) LIKE CONCAT('%', LOWER(TRIM(?)), '%')
ORDER BY MerchantMID ASC
";

$stmt = $koneksi->prepare($query);
$stmt->bind_param("s", $kanca);
$stmt->execute();
$result = $stmt->get_result();

$filtered = [];
$today_ts = strtotime(date('Y-m-d'));

while ($row = $result->fetch_assoc()) {
    $lastLoginRaw = trim($row['Last_Login'] ?? '');
    $isEmpty = ($lastLoginRaw == '' || $lastLoginRaw == '-' || $lastLoginRaw == '0000-00-00' || $lastLoginRaw == '0000-00-00 00:00:00' || is_null($lastLoginRaw));

    // filter kolom All atau Belum Aktivasi/Telah Aktivasi
    if ($kolom == 'All') $filtered[] = $row;
    elseif ($kolom == 'Belum Aktivasi' && $isEmpty) $filtered[] = $row;
    elseif ($kolom == 'Telah Aktivasi' && !$isEmpty) $filtered[] = $row;
}

$total_records = count($filtered);
$total_pages = ($total_records > 0) ? ceil($total_records / $limit) : 1;
$displayed = array_slice($filtered, $offset, $limit);
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Detail — <?= htmlspecialchars($kancaRaw) ?> — <?= htmlspecialchars($kolom) ?></title>
    <style>
        body {
            font-family: Arial;
            padding: 20px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background: #007bff;
            color: #fff;
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        tr:hover {
            background: #f1f1f1;
        }
    </style>
</head>

<body>
    <a href="relia_bri.php">« Kembali</a>
    <h2>Detail — <?= htmlspecialchars($kancaRaw) ?> — <?= htmlspecialchars($kolom) ?></h2>
    <p>Total data ditemukan: <?= $total_records ?></p>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Unit Kerja</th>
                <th>SN_Machine</th>
                <th>SN_Simcard</th>
                <th>MerchantMID</th>
                <th>Kode_Outlet</th>
                <th>Nama_Outlet</th>
                <th>Jenis</th>
                <th>Kanwil</th>
                <th>Kanca</th>
                <th>Uker</th>
                <th>Last_Login</th>
                <th>Merek</th>
                <th>Versi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = $offset + 1;
            if (!empty($displayed)) {
                foreach ($displayed as $row) {
                    echo "<tr>
        <td>" . ($no++) . "</td>
        <td>" . htmlspecialchars($row['Unit_Kerja'] ?? '') . "</td>
        <td>" . htmlspecialchars($row['SN_Machine'] ?? '') . "</td>
        <td>" . htmlspecialchars($row['SN_Simcard'] ?? '') . "</td>
        <td>" . htmlspecialchars($row['MerchantMID'] ?? '') . "</td>
        <td>" . htmlspecialchars($row['Kode_Outlet'] ?? '') . "</td>
        <td>" . htmlspecialchars($row['Nama_Outlet'] ?? '') . "</td>
        <td>" . htmlspecialchars($row['Jenis'] ?? '') . "</td>
        <td>" . htmlspecialchars($row['Kanwil'] ?? '') . "</td>
        <td>" . htmlspecialchars($row['Kanca'] ?? '') . "</td>
        <td>" . htmlspecialchars($row['Uker'] ?? '') . "</td>
        <td>" . htmlspecialchars($row['Last_Login'] ?? '') . "</td>
        <td>" . htmlspecialchars($row['Merek'] ?? '') . "</td>
        <td>" . htmlspecialchars($row['Versi'] ?? '') . "</td>
        </tr>";
                }
            } else {
                echo "<tr><td colspan='14'>Data kosong</td></tr>";
            }
            ?>
        </tbody>
    </table>
</body>

</html>
<?php $koneksi->close(); ?>