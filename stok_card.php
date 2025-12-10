<?php
include 'koneksi.php';
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$msg = "";

// ===== IMPORT EXCEL =====
if (isset($_POST['import'])) {
    $file_mimes = array(
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    );

    if (isset($_FILES['excel']['name']) && in_array($_FILES['excel']['type'], $file_mimes)) {
        $arr_file = explode('.', $_FILES['excel']['name']);
        $extension = end($arr_file);

        if ('xlsx' == $extension) {
            $reader = IOFactory::createReader('Xlsx');
        } else {
            $reader = IOFactory::createReader('Xls');
        }

        $spreadsheet = $reader->load($_FILES['excel']['tmp_name']);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();

        // Mulai dari baris ke-2 (index 1)
        for ($i = 1; $i < count($sheetData); $i++) {
            $branch_office = mysqli_real_escape_string($conn, $sheetData[$i][1]);
            $sn_simcard    = mysqli_real_escape_string($conn, $sheetData[$i][2]);
            $status        = isset($sheetData[$i][3]) ? mysqli_real_escape_string($conn, $sheetData[$i][3]) : "tersedia";

            if ($branch_office != "" && $sn_simcard != "") {
                $conn->query("INSERT INTO sim_card (branch_office, sn_simcard, status) 
                              VALUES ('$branch_office','$sn_simcard','$status')");
            }
        }

        $msg = "✅ Data berhasil diimport!";
    } else {
        $msg = "❌ Silahkan upload file Excel yang valid!";
    }
}

// ===== HITUNG STATUS =====
$qTersedia = $conn->query("SELECT COUNT(*) AS total FROM sim_card WHERE status='tersedia'");
$qTerpakai = $conn->query("SELECT COUNT(*) AS total FROM sim_card WHERE status='terpakai'");

$tersedia = $qTersedia->fetch_assoc()['total'];
$terpakai = $qTerpakai->fetch_assoc()['total'];

// ===== AMBIL DATA SIM CARD =====
$result = $conn->query("SELECT * FROM sim_card ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stok Sim Card - Admin</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background: #f4f6f9; 
        }

        h2 { color: #333; }

        .msg { 
            margin-top: 15px; 
            color: green; 
            font-weight: bold; 
        }

        form { 
            margin-top: 20px; 
            background: #fff; 
            padding: 15px; 
            border-radius: 8px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
        }

        input[type=file] { padding: 5px; }

        input[type=submit] { 
            padding: 6px 12px; 
            background: #28a745; 
            color: #fff; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
        }

        input[type=submit]:hover { background: #218838; }

        table { 
            border-collapse: collapse; 
            width: 100%; 
            margin-top: 20px; 
            background: #fff; 
            border-radius: 8px; 
            overflow: hidden; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
        }

        th, td { 
            border-bottom: 1px solid #ddd; 
            padding: 12px; 
            text-align: left; 
        }

        th { 
            background-color: #007bff; 
            color: #fff; 
            text-transform: uppercase; 
        }

        tr:hover { background-color: #f1f1f1; }

        .status { 
            padding: 4px 8px; 
            border-radius: 4px; 
            font-weight: bold; 
            text-align: center; 
            display: inline-block;
            min-width: 90px;
        }

        .tersedia { background-color: #28a745; color: #fff; }
        .terpakai { background-color: #dc3545; color: #fff; }

        /* ===== CARD ===== */
        .card-wrapper{
            display:flex;
            gap:20px;
            margin:20px 0;
        }

        .card-box{
            flex:1;
            padding:20px;
            border-radius:10px;
            color:#fff;
            text-align:center;
            box-shadow:0 4px 8px rgba(0,0,0,0.2);
        }

        .card-tersedia{ background:#28a745; }
        .card-terpakai{ background:#dc3545; }

        .card-title{ font-size:16px; }
        .card-number{ font-size:36px; font-weight:bold; }
    </style>
</head>
<body>

<h2>Stok Sim Card - Admin</h2>

<!-- ✅ CARD STATUS -->
<div class="card-wrapper">
    <div class="card-box card-tersedia">
        <div class="card-title">SIM CARD TERSEDIA</div>
        <div class="card-number"><?= $tersedia ?></div>
    </div>

    <div class="card-box card-terpakai">
        <div class="card-title">SIM CARD TERPAKAI</div>
        <div class="card-number"><?= $terpakai ?></div>
    </div>
</div>

<?php if ($msg != "") { echo "<div class='msg'>$msg</div>"; } ?>

<form method="POST" enctype="multipart/form-data" action="">
    <label>Import Excel: </label>
    <input type="file" name="excel" required>
    <input type="submit" name="import" value="Import">
</form>

<table>
    <tr>
        <th>No</th>
        <th>Branch Office</th>
        <th>SN SIMCARD</th>
        <th>Status</th>
    </tr>
    <?php
    $no = 1;
    while ($row = $result->fetch_assoc()) {
        $status_class = strtolower($row['status']) == 'tersedia' ? 'tersedia' : 'terpakai';
        echo "<tr>
                <td>{$no}</td>
                <td>{$row['branch_office']}</td>
                <td>{$row['sn_simcard']}</td>
                <td><span class='status {$status_class}'>{$row['status']}</span></td>
              </tr>";
        $no++;
    }
    ?>
</table>

</body>
</html>
