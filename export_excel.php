<?php
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

include 'koneksi.php';

// Ambil filter dari URL
$kanca = $_GET['kanca'] ?? 'All';
$kategori = $_GET['kategori'] ?? 'all';
$start = $_GET['start'] ?? date('Y-m-d');
$end = $_GET['end'] ?? date('Y-m-d');

// Fungsi ambil data (bisa pakai getDetailRows versi HTML, tapi kita bikin simple untuk Excel)
function getExcelRows($conn, $kanca, $kategori, $start, $end) {
    $whereKategori = "1=1";
    switch ($kategori) {
        case 'today': $whereKategori = "status_available=1"; break;
        case '1-7': $whereKategori = "status_available=2"; break;
        case '8-15': $whereKategori = "status_available=3"; break;
        case '16-30': $whereKategori = "status_available=4"; break;
        case '>30': $whereKategori = "status_available=5"; break;
    }

    if ($kanca === 'All') {
        $whereKanca = "TRIM(uker_nama_implementor) <> ''";
        $sql = "SELECT * FROM edc_merchant_raw WHERE $whereKanca AND $whereKategori AND date_input BETWEEN ? AND ? ORDER BY mid ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $start, $end);
    } else {
        $whereKanca = "uker_nama_implementor = ?";
        $sql = "SELECT * FROM edc_merchant_raw WHERE $whereKanca AND $whereKategori AND date_input BETWEEN ? AND ? ORDER BY mid ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $kanca, $start, $end);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $rows = '';
    $no = 1;
    while ($row = $result->fetch_assoc()) {
        $lastAvailable = $row['last_available'];
        $hariTidakAktif = $lastAvailable ? (new DateTime())->diff(new DateTime($lastAvailable))->days : 0;

        $rows .= "<tr>
            <td>{$no}</td>
            <td>{$row['mid']}</td>
            <td>{$row['nama_merchant']}</td>
            <td>{$row['uker_nama_implementor']}</td>
            <td>{$row['uker_nama_implementor']}</td>
            <td>{$hariTidakAktif}</td>
            <td>{$row['last_available']}</td>
        </tr>";
        $no++;
    }
    return $rows;
}

// Header supaya langsung download
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=data_uko_" . date('Ymd_His') . ".xls");

// Buat tabel Excel
echo "<table border='1'>";
echo "<tr>
        <th>No</th>
        <th>TID</th>
        <th>Nama</th>
        <th>Kanca</th>
        <th>Implementor</th>
        <th>Hari Tidak Aktif</th>
        <th>Last Available</th>
      </tr>";

echo getExcelRows($conn, $kanca, $kategori, $start, $end);
echo "</table>";
exit;
