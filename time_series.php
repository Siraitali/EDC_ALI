<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "edc_login_db2";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$tahun = date('Y');
$vendors = [
    "PT. BRINGIN INTI TEKNOLOGI" => ["tabel" => "vendor", "kolom" => "rata_bit", "pakai_tahun" => true],
    "PT. PASIFIK CIPTA SOLUSI" => ["tabel" => "vendor", "kolom" => "rata_pcs", "pakai_tahun" => true],
    "PT. PRIMA VISTA SOLUSI" => ["tabel" => "vendor", "kolom" => "rata_pvs", "pakai_tahun" => true],
    "All Kantor Cabang" => ["tabel" => "uker_rata_rata", "kolom" => "rata_rata", "pakai_tahun" => false]
];

$bulan = [
    'Januari', 'Februari', 'Maret', 'April',
    'Mei', 'Juni', 'Juli', 'Agustus',
    'September', 'Oktober', 'November', 'Desember'
];

function getColor($value){
    if(!is_numeric($value)) return '';
    if($value >= 95) return '#28a745';         // hijau
    if($value >= 90) return '#8bc34a';         // hijau muda
    if($value >= 85) return '#ffeb3b';         // kuning
    if($value >= 80) return '#ff9800';         // oranye
    return '#f44336';                          // merah
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Time Series</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f9f9f9;
    padding: 20px;
}
h2 {
    text-align: center;
    margin-bottom: 20px;
}
.table-container {
    overflow-x: auto;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    background: #fff;
    border-radius: 5px;
}
table {
    border-collapse: collapse;
    width: 100%;
    table-layout: fixed;
}
th, td {
    border: 1px solid #ccc;
    text-align: center;
    padding: 10px 5px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
th {
    background-color: #007BFF;
    color: #fff;
    font-weight: bold;
}
tr:nth-child(even) {
    background-color: #f2f2f2;
}
tr:hover {
    background-color: #e0f0ff;
}
th:first-child, td:first-child {
    width: 20%;
}
th:not(:first-child), td:not(:first-child) {
    width: 6.66%;
}
@media screen and (max-width: 768px) {
    th, td {
        padding: 8px 4px;
        font-size: 12px;
    }
    th:first-child, td:first-child {
        width: 25%;
    }
    th:not(:first-child), td:not(:first-child) {
        width: 75% / 12;
    }
}
</style>
</head>
<body>
<h2>Time Series Tahun <?= $tahun ?></h2>
<div class="table-container">
<table>
<tr>
    <th>Vendor</th>
    <?php foreach($bulan as $b): ?>
        <th><?= $b ?></th>
    <?php endforeach; ?>
</tr>

<?php foreach($vendors as $vendorName => $info): ?>
<tr>
    <td><?= $vendorName ?></td>
    <?php
    foreach($bulan as $b){
        $tabel = $info['tabel'];
        $kolom = $info['kolom'];
        $pakaiTahun = $info['pakai_tahun'];

        if($pakaiTahun){
            $sql = "SELECT $kolom FROM $tabel WHERE bulan = '$b' AND tahun = '$tahun' LIMIT 1";
        } else {
            $sql = "SELECT $kolom FROM $tabel WHERE bulan = '$b' LIMIT 1";
        }

        $res = $conn->query($sql);
        $value = ($res && $res->num_rows > 0) ? $res->fetch_assoc()[$kolom] : '-';

        if($value !== '-' && is_numeric($value)){
            $color = getColor($value);
            $value = number_format($value, 2) . '%';
            echo "<td style='background-color:$color;color:#000;'>$value</td>";
        } else {
            echo "<td>$value</td>";
        }
    }
    ?>
</tr>
<?php endforeach; ?>
</table>
</div>
</body>
</html>
