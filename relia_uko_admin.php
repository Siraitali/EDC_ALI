<?php
// relia_uko_admin.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$host = "localhost";
$user = "root";
$pass = "";
$db   = "edc_login_db2";

$koneksi = new mysqli($host, $user, $pass, $db);
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

// ====== MODE AJAX (DataTables) ======
if (isset($_GET['ajax'])) {
    $result = $koneksi->query("SELECT * FROM detail_uko ORDER BY id DESC");
    $data = [];
    while ($r = $result->fetch_assoc()) {
        $data[] = [
            $r['id'],
            $r['mid'],
            $r['tid'],
            $r['regional_office'],
            $r['nama_merchant'],
            $r['unit_kerja_pengelola'],
            $r['peruntukan'],
            $r['jenis'],
            $r['jenis_peruntukan'],
            $r['nama_instansi_kerjasama'],
            $r['group_nama'],
            $r['status_perangkat'],
            $r['kondisi_perangkat'],
            $r['sn_edc'],
            $r['merk_edc'],
            $r['alamat_merchant'],
            $r['keterangan']
        ];
    }
    echo json_encode(["data" => $data]);
    exit;
}

// ====== PROSES IMPORT EXCEL ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_excel'])) {
    if ($_FILES['file_excel']['error'] === UPLOAD_ERR_OK) {
        $tmp  = $_FILES['file_excel']['tmp_name'];
        $ext  = strtolower(pathinfo($_FILES['file_excel']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['xls', 'xlsx'])) {
            try {
                $spreadsheet = IOFactory::load($tmp);
                $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

                $sql = "INSERT INTO detail_uko 
                    (mid, tid, regional_office, nama_merchant, unit_kerja_pengelola, peruntukan, jenis, jenis_peruntukan, nama_instansi_kerjasama, group_nama, status_perangkat, kondisi_perangkat, sn_edc, merk_edc, alamat_merchant, keterangan)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $koneksi->prepare($sql);

                $inserted = 0;
                $rownum = 0;
                $koneksi->begin_transaction();
                foreach ($sheetData as $row) {
                    $rownum++;
                    if ($rownum == 1) continue; // skip header

                    $stmt->bind_param(
                        "ssssssssssssssss",
                        $row['A'],
                        $row['B'],
                        $row['C'],
                        $row['D'],
                        $row['E'],
                        $row['F'],
                        $row['G'],
                        $row['H'],
                        $row['I'],
                        $row['J'],
                        $row['K'],
                        $row['L'],
                        $row['M'],
                        $row['N'],
                        $row['O'],
                        $row['P']
                    );
                    $stmt->execute();
                    if ($stmt->affected_rows > 0) $inserted++;
                }
                $stmt->close();
                $koneksi->commit();

                header("Location: " . $_SERVER['PHP_SELF'] . "?import=ok&n=" . $inserted);
                exit;
            } catch (Exception $e) {
                header("Location: " . $_SERVER['PHP_SELF'] . "?import=load_error");
                exit;
            }
        } else {
            header("Location: " . $_SERVER['PHP_SELF'] . "?import=bad_ext");
            exit;
        }
    } else {
        header("Location: " . $_SERVER['PHP_SELF'] . "?import=upload_error");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Relia UKO - Import & Tampil (Live)</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
</head>

<body class="container mt-4">

    <h3 class="mb-3">Relia UKO - Import Excel (Live Refresh)</h3>

    <?php if (isset($_GET['import'])): ?>
        <?php if ($_GET['import'] == 'ok'): ?>
            <div class="alert alert-success">Import berhasil. Baris ditambahkan: <?= (int)($_GET['n'] ?? 0) ?></div>
        <?php elseif ($_GET['import'] == 'bad_ext'): ?>
            <div class="alert alert-danger">Format file salah, hanya .xls / .xlsx</div>
        <?php elseif ($_GET['import'] == 'upload_error'): ?>
            <div class="alert alert-danger">Upload gagal</div>
        <?php else: ?>
            <div class="alert alert-danger">Error: <?= htmlspecialchars($_GET['import']) ?></div>
        <?php endif; ?>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="mb-3 d-flex gap-2">
        <input type="file" name="file_excel" accept=".xls,.xlsx" class="form-control" required>
        <button type="submit" class="btn btn-primary">Import Excel</button>
    </form>

    <table id="tbl" class="table table-striped table-bordered nowrap" style="width:100%">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>MID</th>
                <th>TID</th>
                <th>Regional</th>
                <th>Nama Merchant</th>
                <th>Unit Kerja</th>
                <th>Peruntukan</th>
                <th>Jenis</th>
                <th>Jenis Peruntukan</th>
                <th>Instansi</th>
                <th>Group</th>
                <th>Status</th>
                <th>Kondisi</th>
                <th>SN</th>
                <th>Merk</th>
                <th>Alamat</th>
                <th>Keterangan</th>
            </tr>
        </thead>
    </table>

    <script>
        $(document).ready(function() {
            var table = $('#tbl').DataTable({
                ajax: "relia_uko_admin.php?ajax=1",
                dom: 'Bfrtip',
                buttons: [{
                        extend: 'excelHtml5',
                        text: 'Export Excel'
                    },
                    {
                        extend: 'pdfHtml5',
                        text: 'Export PDF'
                    },
                    {
                        extend: 'print',
                        text: 'Print'
                    }
                ],
                scrollX: true,
                pageLength: 15
            });

            // auto refresh tiap 10 detik
            setInterval(function() {
                table.ajax.reload(null, false);
            }, 10000);
        });
    </script>
</body>

</html>