<?php
// tidwpe.php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "edc_login_db2";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

$tid = isset($_GET['tid']) ? $_GET['tid'] : '';
$tid_safe = $conn->real_escape_string($tid);

$sql = "SELECT * FROM wepdetail WHERE tid='$tid_safe' LIMIT 1";
$result = $conn->query($sql);
$data = $result ? $result->fetch_assoc() : null;
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Detail TID - <?= htmlspecialchars($tid) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f3f4f6;
        }

        h2 {
            margin: 0 0 15px 0;
            text-align: left;
        }

        .back-btn {
            display: inline-block;
            text-decoration: none;
            background: #6c757d;
            color: white;
            padding: 8px 14px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .back-btn:hover {
            background: #5a6268;
        }

        .card-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            flex: 1 1 45%;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }

        .card h3 {
            margin-top: 0;
            margin-bottom: 10px;
            color: white;
            padding: 8px;
            border-radius: 4px;
        }

        .card-merchant h3 {
            background: #007bff;
        }

        .card-uker h3 {
            background: #28a745;
        }

        .card-perangkat h3 {
            background: #ffc107;
        }

        .card-lainnya h3 {
            background: #17a2b8;
        }

        .card p {
            margin: 5px 0;
            word-break: break-word;
            display: flex;
        }

        .label {
            display: inline-block;
            width: 150px;
            font-weight: bold;
        }

        .value {
            display: inline-block;
        }

        @media(max-width:768px) {
            .card {
                flex: 1 1 100%;
            }
        }
    </style>
</head>

<body>

    <a href="detailwpe.php" class="back-btn">← Kembali ke Detail WPE</a>

    <h2>Monitoring Merchant TID: <?= htmlspecialchars($tid) ?></h2>

    <?php if ($data): ?>
        <div class="card-container">

            <!-- Card 1: Data Merchant -->
            <div class="card card-merchant">
                <h3>Data Merchant</h3>
                <p><span class="label">MID</span>: <span class="value"><?= $data['mid'] ?></span></p>
                <p><span class="label">TID</span>: <span class="value"><?= $data['tid'] ?></span></p>
                <p><span class="label">Nama Merchant</span>: <span class="value"><?= $data['nama_merchant'] ?></span></p>
                <p><span class="label">Alamat</span>: <span class="value"><?= $data['alamat_merchant'] ?></span></p>
                <p><span class="label">Kode Pos</span>: <span class="value"><?= $data['kodepos'] ?></span></p>
                <p><span class="label">Kelurahan</span>: <span class="value"><?= $data['kelurahan'] ?></span></p>
                <p><span class="label">Kecamatan</span>: <span class="value"><?= $data['kecamatan'] ?></span></p>
                <p><span class="label">Kota</span>: <span class="value"><?= $data['kabupaten'] ?></span></p>
                <p><span class="label">Provinsi</span>: <span class="value"><?= $data['provinsi'] ?></span></p>
                <p><span class="label">PIC Merchant</span>: <span class="value"><?= $data['pic'] ?></span></p>
                <p><span class="label">Telpon</span>: <span class="value"><?= $data['telp'] ?></span></p>
                <p><span class="label">Email</span>: <span class="value"><?= $data['email'] ?? '-' ?></span></p>
                <p><span class="label">Tanggal Pasang</span>: <span class="value"><?= $data['tgl_pasang'] ?></span></p>
            </div>

            <!-- Card 2: Data Uker -->
            <div class="card card-uker">
                <h3>Data Uker</h3>
                <p><span class="label">Kanwil Domisili</span>: <span class="value"><?= $data['kanwil_nama_implementor'] ?></span></p>
                <p><span class="label">Kanwil Initiator</span>: <span class="value"><?= $data['kanwil_nama_pemrakarsa'] ?></span></p>
                <p><span class="label">Uker Initiator</span>: <span class="value"><?= $data['uker_pemrakarsa'] ?> - <?= $data['uker_nama_pemrakarsa'] ?></span></p>
                <p><span class="label">Vendor</span>: <span class="value"><?= $data['uker_nama_implementor'] ?></span></p>
                <p><span class="label">User Pemrakarsa</span>: <span class="value"><?= $data['user_pemrakarsa'] ?></span></p>
            </div>

            <!-- Card 3: Perangkat -->
            <div class="card card-perangkat">
                <h3>Perangkat</h3>
                <p><span class="label">SN EDC</span>: <span class="value"><?= $data['sn_edc'] ?? '-' ?></span></p>
                <p><span class="label">SN CLR</span>: <span class="value"><?= $data['sn_clr'] ?? '-' ?></span></p>
                <p><span class="label">SN SIMCARD</span>: <span class="value"><?= $data['sn_simcard'] ?? '-' ?></span></p>
            </div>

            <!-- Card 4: Lainnya -->
            <div class="card card-lainnya">
                <h3>Lainnya</h3>
                <p><span class="label">Peruntukan</span>: <span class="value"><?= $data['peruntukkan'] ?></span></p>
                <p><span class="label">Jenis Merchant</span>: <span class="value"><?= $data['jenis'] ?></span></p>
                <p><span class="label">Nomor Rekening</span>: <span class="value"><?= $data['no_rek'] ?></span></p>
                <p><span class="label">Pemilik Rekening</span>: <span class="value"><?= $data['user_pemrakarsa'] ?></span></p>
            </div>

        </div>
    <?php else: ?>
        <p>Data TID <?= htmlspecialchars($tid) ?> tidak ditemukan.</p>
    <?php endif; ?>

</body>

</html>

<?php $conn->close(); ?>