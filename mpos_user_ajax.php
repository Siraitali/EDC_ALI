<?php
include 'koneksi.php';

$result = $conn->query("SELECT * FROM mpos ORDER BY id DESC");
$data = [];
while ($row = $result->fetch_assoc()) {
    $row_class = ($row['status_done'] == 1) ? 'row-done' : 'row-editable';
    $data[] = [
        'id' => $row['id'],
        'kc' => $row['kc'],
        'outlet_id' => $row['outlet_id'],
        'nama_outlet' => $row['nama_outlet'],
        'sn_mesin' => $row['sn_mesin'],
        'merek_mesin' => $row['merek_mesin'],
        'sn_simcard' => $row['sn_simcard'],
        'nama_pab' => $row['nama_pab'],
        'pengajuan' => $row['pengajuan'],
        'keterangan' => $row['keterangan'],
        'status_done' => $row['status_done'],
        'row_class' => $row_class
    ];
}
echo json_encode($data);
