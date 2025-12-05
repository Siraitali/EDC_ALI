<?php
include 'koneksi.php';

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$result = $conn->query("SELECT * FROM mpos ORDER BY id DESC LIMIT $limit OFFSET $offset");
$no = $offset + 1;

while ($row = $result->fetch_assoc()) {
    $row_class = ($row['status_done'] == 1) ? 'done-row' : '';
    echo "<tr id='row{$row['id']}' class='{$row_class}'>";
    echo "<td>{$no}</td>";
    echo "<td>" . htmlspecialchars($row['kc']) . "</td>";
    echo "<td>" . htmlspecialchars($row['outlet_id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['nama_outlet']) . "</td>";
    echo "<td>" . htmlspecialchars($row['sn_mesin']) . "</td>";
    echo "<td>" . htmlspecialchars($row['merek_mesin']) . "</td>";
    echo "<td>" . htmlspecialchars($row['sn_simcard']) . "</td>";
    echo "<td>" . htmlspecialchars($row['nama_pab']) . "</td>";
    echo "<td>" . htmlspecialchars($row['pengajuan']) . "</td>";
    echo "<td class='keterangan-cell " . ($row['status_done'] == 1 ? 'keterangan-done' : 'keterangan-pending') . "'>" . htmlspecialchars($row['keterangan']) . "</td>";
    echo "<td>" . htmlspecialchars($row['ket_ppbk']) . "</td>";
    echo "<td>" . (!empty($row['tanggal_mapping']) ? htmlspecialchars(substr($row['tanggal_mapping'], 0, 10)) : '') . "</td>";

    // Kolom Action tetap di posisi terakhir
    echo "<td>
        <button class='btn btn-warning btn-sm' data-bs-toggle='modal' data-bs-target='#adminModal{$row['id']}'><i class='fas fa-edit'></i> Edit</button>
        <button type='button' class='btn btn-success btn-sm done-btn' data-id='{$row['id']}'><i class='fas fa-check'></i> Done</button>
    </td>";
    echo "</tr>";
}
