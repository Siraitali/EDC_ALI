<?php
include 'koneksi.php';

if (!isset($_POST['id'], $_POST['sn_mesin'], $_POST['sn_sim'], $_POST['type'])) {
    echo "invalid";
    exit;
}

$id       = $_POST['id'];
$sn_mesin = $_POST['sn_mesin'];
$sn_sim   = $_POST['sn_sim'];
$type     = $_POST['type'];

$conn->begin_transaction();

try {

    // ✅ RETURN MESIN
    if ($type == 'mesin' || $type == 'both') {
        $stmt1 = $conn->prepare("UPDATE edc_stok SET status='tersedia' WHERE sn_mesin=?");
        $stmt1->bind_param("s", $sn_mesin);
        $stmt1->execute();
    }

    // ✅ RETURN SIM
    if ($type == 'sim' || $type == 'both') {
        $stmt2 = $conn->prepare("UPDATE sim_card SET status='tersedia' WHERE sn_simcard=?");
        $stmt2->bind_param("s", $sn_sim);
        $stmt2->execute();
    }

    // ============================
    // ✅ CEK STATUS TERBARU
    // ============================

    // Cek status mesin
    $cekMesin = $conn->prepare("SELECT status FROM edc_stok WHERE sn_mesin=?");
    $cekMesin->bind_param("s", $sn_mesin);
    $cekMesin->execute();
    $resMesin = $cekMesin->get_result()->fetch_assoc();

    // Cek status sim
    $cekSim = $conn->prepare("SELECT status FROM sim_card WHERE sn_simcard=?");
    $cekSim->bind_param("s", $sn_sim);
    $cekSim->execute();
    $resSim = $cekSim->get_result()->fetch_assoc();

    // ============================
    // ✅ TENTUKAN status_done
    // ============================

    if ($resMesin['status'] == 'tersedia' && $resSim['status'] == 'tersedia') {
        // ✅ KEDUANYA SUDAH KEMBALI → BARU SELESAI
        $stmt3 = $conn->prepare("UPDATE mpos SET pengajuan='RETURN', status_done=0 WHERE id=?");
        $stmt3->bind_param("i", $id);
    } else {
        // ✅ SALAH SATU MASIH TERPAKAI → TETAP RETURN
        $stmt3 = $conn->prepare("UPDATE mpos SET pengajuan='RETURN', status_done=1 WHERE id=?");
        $stmt3->bind_param("i", $id);
    }

    $stmt3->execute();

    $conn->commit();
    echo "success";

} catch (Exception $e) {

    $conn->rollback();
    echo "error";
}
