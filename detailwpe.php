<?php
// --- Koneksi Database ---
$host = "localhost";
$user = "root";
$pass = "";
$db   = "edc_login_db2";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil parameter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'total';
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit  = 25;
$offset = ($page - 1) * $limit;
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
$branch = isset($_GET['branch']) ? urldecode($_GET['branch']) : '';

// Mapping status_available
$status_filter = [];
switch ($filter) {
    case 'gte1h_ritel':
        $status_filter = [0];
        break;
    case 'today_ritel':
        $status_filter = [1];
        break;
    case 'last_7d_ritel':
        $status_filter = [2];
        break;
    case 'gte7d_ritel':
        $status_filter = [3, 4, 5];
        break;
    default:
        $status_filter = [];
        break;
}

// Query dasar
$sqlBase = "FROM wepdetail WHERE 1=1";
if ($branch != '') $sqlBase .= " AND uker_nama_pemrakarsa = '" . $conn->real_escape_string($branch) . "'";
if (!empty($status_filter)) $sqlBase .= " AND status_available IN (" . implode(',', $status_filter) . ")";
if ($keyword != '') {
    $keyword_safe = $conn->real_escape_string($keyword);
    $sqlBase .= " AND (mid LIKE '%$keyword_safe%' OR tid LIKE '%$keyword_safe%' OR nama_merchant LIKE '%$keyword_safe%')";
}

// Total data
$countResult = $conn->query("SELECT COUNT(*) AS total " . $sqlBase);
$totalData = $countResult ? $countResult->fetch_assoc()['total'] : 0;
$totalPages = ceil($totalData / $limit);

// Ambil data per halaman
$sql = "SELECT mid, tid, nama_merchant, uker_nama_pemrakarsa AS branch_office,
        uker_nama_implementor AS vendor, last_available, alamat_merchant
        $sqlBase
        ORDER BY nama_merchant ASC
        LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Monitoring Merchant</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f3f4f6;
        }

        h2 {
            margin: 0 0 15px 0;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }

        th {
            background: #007bff;
            color: white;
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        tr:hover {
            background: #f1f1f1;
        }

        .total {
            font-weight: bold;
            display: inline-block;
            margin-left: 15px;
        }

        .pagination {
            text-align: center;
            margin-top: 20px;
        }

        .pagination a {
            padding: 8px 12px;
            border: 1px solid #007bff;
            margin: 0 3px;
            color: #007bff;
            text-decoration: none;
            border-radius: 4px;
        }

        .pagination a.active,
        .pagination a:hover {
            background: #007bff;
            color: white;
        }

        .back-btn {
            display: inline-block;
            text-decoration: none;
            background: #6c757d;
            color: white;
            padding: 8px 14px;
            border-radius: 4px;
            margin-right: 15px;
        }

        .back-btn:hover {
            background: #5a6268;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        #searchBox {
            padding: 6px 10px;
            width: 250px;
            border: 1px solid #007bff;
            border-radius: 4px;
        }

        a.tid-link {
            text-decoration: none;
            color: #007bff;
        }

        a.tid-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <h2>Monitoring Merchant</h2>

    <div class="top-bar">
        <div style="display:flex;align-items:center;">
            <a href="wpe.php" class="back-btn">← Kembali ke WPE</a>
            <span class="total">Total Data: <?= $totalData ?></span>
        </div>
        <div>
            <input type="text" id="searchBox" placeholder="Cari MID, TID, Nama Merchant..." value="<?= htmlspecialchars($keyword) ?>">
        </div>
    </div>

    <table id="merchantTable">
        <tr>
            <th>MID</th>
            <th>TID</th>
            <th>Nama Merchant</th>
            <th>Branch Office</th>
            <th>Vendor</th>
            <th>Last Available</th>
            <th>Alamat</th>
        </tr>
        <?php
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['mid'] . "</td>";
                echo "<td><a class='tid-link' href='tidwpe.php?tid=" . $row['tid'] . "'>" . $row['tid'] . "</a></td>";
                echo "<td>" . $row['nama_merchant'] . "</td>";
                echo "<td>" . $row['branch_office'] . "</td>";
                echo "<td>" . $row['vendor'] . "</td>";
                echo "<td>" . $row['last_available'] . "</td>";
                echo "<td>" . $row['alamat_merchant'] . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='7'>Tidak ada data ditemukan</td></tr>";
        }
        ?>
    </table>

    <!-- Pagination Dinamis -->
    <div class="pagination">
        <?php
        $range = 2;
        if ($page > 1) echo '<a href="?filter=' . urlencode($filter) . '&branch=' . urlencode($branch) . '&page=' . ($page - 1) . '&keyword=' . urlencode($keyword) . '">« Prev</a>';
        if ($page - $range > 1) {
            echo '<a href="?filter=' . urlencode($filter) . '&branch=' . urlencode($branch) . '&page=1&keyword=' . urlencode($keyword) . '">1</a>';
            if ($page - $range > 2) echo '...';
        }
        for ($i = max(1, $page - $range); $i <= min($totalPages, $page + $range); $i++) {
            $active = ($i == $page) ? 'active' : '';
            echo "<a class='$active' href='?filter=" . urlencode($filter) . "&branch=" . urlencode($branch) . "&page=$i&keyword=" . urlencode($keyword) . "'>$i</a>";
        }
        if ($page + $range < $totalPages) {
            if ($page + $range < $totalPages - 1) echo '...';
            echo '<a href="?filter=' . urlencode($filter) . '&branch=' . urlencode($branch) . '&page=' . $totalPages . '&keyword=' . urlencode($keyword) . '">' . $totalPages . '</a>';
        }
        if ($page < $totalPages) echo '<a href="?filter=' . urlencode($filter) . '&branch=' . urlencode($branch) . '&page=' . ($page + 1) . '&keyword=' . urlencode($keyword) . '">Next »</a>';
        ?>
    </div>

    <script>
        // Search otomatis dengan delay
        let timer;
        document.getElementById('searchBox').addEventListener('input', function() {
            clearTimeout(timer);
            const value = this.value;
            timer = setTimeout(() => {
                const params = new URLSearchParams(window.location.search);
                params.set('keyword', value);
                params.set('page', 1);
                window.location.href = window.location.pathname + '?' + params.toString();
            }, 300);
        });
    </script>

</body>

</html>

<?php $conn->close(); ?>