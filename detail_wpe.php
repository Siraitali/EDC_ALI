<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "edc_login_db2";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

$tid = $_GET['tid'] ?? '';
if (empty($tid)) die("<h3 style='color:red;text-align:center;'>TID tidak ditemukan!</h3>");

$sql = "SELECT * FROM wpe WHERE TID = '" . $conn->real_escape_string($tid) . "' LIMIT 1";
$result = $conn->query($sql);
if (!$result || $result->num_rows == 0) die("<h3 style='color:red;text-align:center;'>Data tidak ditemukan untuk TID: " . htmlspecialchars($tid) . "</h3>");
$data = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Detail WPE - <?= htmlspecialchars($tid) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', sans-serif;
            padding: 30px;
        }

        .container {
            max-width: 1000px;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
        }

        .card-header {
            background-color: #007bff;
            color: white;
            font-weight: 600;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }

        .table-detail td {
            padding: 6px 10px;
            vertical-align: top;
            border-bottom: 1px solid #eee;
        }

        .table-detail td:first-child {
            font-weight: 600;
            width: 40%;
            color: #333;
        }

        a.back-btn {
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            color: #007bff;
            font-weight: 500;
        }

        a.back-btn:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="container">

        <!-- Card 1: Info EDC -->
        <div class="card">
            <div class="card-header text-center">📟 Informasi EDC</div>
            <div class="card-body">
                <table class="table-detail w-100">
                    <tr>
                        <td>TID</td>
                        <td><?= htmlspecialchars($data['TID'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td>MID</td>
                        <td><?= htmlspecialchars($data['MID'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td>Nama Merchant</td>
                        <td><?= htmlspecialchars($data['Nama_Merchant'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td>Peruntukkan</td>
                        <td><?= htmlspecialchars($data['Peruntukkan'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td>Jenis</td>
                        <td><?= htmlspecialchars($data['Jenis'] ?? '-') ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Card 2: Lokasi -->
        <div class="card">
            <div class="card-header text-center">📍 Lokasi Merchant</div>
            <div class="card-body">
                <table class="table-detail w-100">
                    <tr>
                        <td>Alamat</td>
                        <td><?= htmlspecialchars($data['Alamat_Merchant'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td>Kelurahan</td>
                        <td><?= htmlspecialchars($data['Kelurahan'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td>Kecamatan</td>
                        <td><?= htmlspecialchars($data['Kecamatan'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td>Kabupaten</td>
                        <td><?= htmlspecialchars($data['Kabupaten'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td>Provinsi</td>
                        <td><?= htmlspecialchars($data['Provinsi'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td>Longitude</td>
                        <td><?= htmlspecialchars($data['Longitude'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td>Latitude</td>
                        <td><?= htmlspecialchars($data['Latitude'] ?? '-') ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Card 3: Status & Aktivitas -->
        <div class="card">
            <div class="card-header text-center">🕒 Status & Aktivitas</div>
            <div class="card-body">
                <table class="table-detail w-100">
                    <tr>
                        <td>Last Availability</td>
                        <td><?= !empty($data['Last_Availability']) ? date('d M Y H:i:s', strtotime($data['Last_Availability'])) : '-' ?></td>
                    </tr>
                    <tr>
                        <td>Last Utility</td>
                        <td><?= !empty($data['Last_Utility']) ? date('d M Y H:i:s', strtotime($data['Last_Utility'])) : '-' ?></td>
                    </tr>
                    <tr>
                        <td>Last Transactional</td>
                        <td><?= !empty($data['Last_Transactional']) ? date('d M Y H:i:s', strtotime($data['Last_Transactional'])) : '-' ?></td>
                    </tr>
                    <tr>
                        <td>Ratas Saldo</td>
                        <td><?= isset($data['Ratas_Saldo']) ? number_format($data['Ratas_Saldo'], 0, ',', '.') : '-' ?></td>
                    </tr>
                    <tr>
                        <td>Vendor</td>
                        <td><?= htmlspecialchars($data['Vendor'] ?? '-') ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Card 4: Pemrakarsa -->
        <div class="card">
            <div class="card-header text-center">🏢 Informasi Pemrakarsa</div>
            <div class="card-body">
                <table class="table-detail w-100">
                    <tr>
                        <td>Kanwil</td>
                        <td><?= htmlspecialchars($data['Kanwil'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td>Mainbranch</td>
                        <td><?= htmlspecialchars($data['Mainbranch_Nama_Pemrakarsa'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td>Branch</td>
                        <td><?= htmlspecialchars($data['Branch_Nama_Pemrakarsa'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td>User Pemrakarsa</td>
                        <td><?= htmlspecialchars($data['User_Pemrakarsa'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td>Domisili Mainbranch</td>
                        <td><?= htmlspecialchars($data['Domisili_Mainbranch'] ?? '-') ?></td>
                    </tr>
                </table>
            </div>
        </div>

    </div>
</body>

</html>