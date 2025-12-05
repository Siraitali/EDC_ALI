<?php
include 'koneksi.php';

$default_limit = 25;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : $default_limit;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$result = $conn->query("SELECT * FROM mpos ORDER BY id DESC LIMIT $limit OFFSET $offset");
$no = $offset + 1;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row_class = ($row['status_done'] == 1) ? 'row-done' : 'row-editable';
        $disabled = ($row['status_done'] == 1) ? 'disabled' : '';
        echo "<tr id='row{$row['id']}' class='{$row_class}'>
        <td class='text-center'>{$no}</td>
        <td>" . htmlspecialchars($row['kc']) . "</td>
        <td>" . htmlspecialchars($row['outlet_id']) . "</td>
        <td>" . htmlspecialchars($row['nama_outlet']) . "</td>
        <td>" . htmlspecialchars($row['sn_mesin']) . "</td>
        <td>" . htmlspecialchars($row['merek_mesin']) . "</td>
        <td>" . htmlspecialchars($row['sn_simcard']) . "</td>
        <td>" . htmlspecialchars($row['nama_pab']) . "</td>
        <td>" . htmlspecialchars($row['pengajuan']) . "</td>
        <td>" . htmlspecialchars($row['keterangan']) . "</td>
        <td class='text-center'>
        <button class='btn btn-warning-custom btn-sm' data-bs-toggle='modal' data-bs-target='#editModal{$row['id']}' {$disabled}>
        <i class='fas fa-edit'></i> Edit
        </button>
        </td>
        </tr>";
        $no++;
    }
} else {
    echo "<tr><td colspan='11' class='text-center'>Tidak ada data</td></tr>";
}
