<?php
session_start();
if (!isset($_SESSION['login']) || $_SESSION['level'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Koneksi database
$conn = new mysqli("localhost", "root", "", "edc_login_db2");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Proses simpan data
$success_msg = $error_msg = "";
if (isset($_POST['submit'])) {
    $sn_mpos   = $_POST['SN_MPOS'];
    $spk       = $_POST['SPK'];
    $type      = $_POST['Type'];
    $teruntuk  = $_POST['Teruntuk'];
    $sn_sim    = $_POST['SN_SimCard'];
    $lokasi    = $_POST['Lokasi'];
    $status    = $_POST['Status'];

    // Prepared statement untuk keamanan
    $stmt = $conn->prepare("INSERT INTO edc (SN_MPOS, SPK, Type, Teruntuk, SN_SimCard, Lokasi, Status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $sn_mpos, $spk, $type, $teruntuk, $sn_sim, $lokasi, $status);

    if ($stmt->execute()) {
        $success_msg = "Data EDC berhasil ditambahkan!";
    } else {
        $error_msg = "Error: " . $stmt->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Tambah EDC</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="bg-light">

    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Tambah Data EDC</h4>
            </div>
            <div class="card-body">

                <!-- Alert Success/Error -->
                <?php if ($success_msg): ?>
                    <div class="alert alert-success"><?= $success_msg ?></div>
                <?php endif; ?>
                <?php if ($error_msg): ?>
                    <div class="alert alert-danger"><?= $error_msg ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SN MPOS</label>
                            <input type="text" name="SN_MPOS" class="form-control" placeholder="Contoh: MPOS12345" required>
                            <small class="text-muted">Masukkan serial number MPOS sesuai label</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SPK</label>
                            <input type="text" name="SPK" class="form-control" placeholder="Contoh: SPK001" required>
                            <small class="text-muted">Masukkan nomor SPK</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Type</label>
                            <input type="text" name="Type" class="form-control" placeholder="Contoh: Ingenico ICT220" required>
                            <small class="text-muted">Tipe EDC</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teruntuk</label>
                            <select name="Teruntuk" class="form-select" required>
                                <option value="">-- Pilih Teruntuk --</option>
                                <option value="BRILink">BRILink</option>
                                <option value="UKo">UKo</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SN SimCard</label>
                            <input type="text" name="SN_SimCard" class="form-control" placeholder="Contoh: SIM12345">
                            <small class="text-muted">Nomor SIM Card jika ada</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Lokasi</label>
                            <input type="text" name="Lokasi" class="form-control" placeholder="Contoh: Kantor Cabang XYZ">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="Status" class="form-select" required>
                                <option value="Terpakai">Terpakai</option>
                                <option value="Stok">Stok</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" name="submit" class="btn btn-success me-2"><i class="fas fa-save me-1"></i> Simpan</button>
                    <a href="edc_admin.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
                </form>

            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>