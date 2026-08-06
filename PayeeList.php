<?php
session_start(); 
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

$username = $_SESSION['username']; 
include 'db.php'; 

$records_per_page = 25;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Join payeeDetail with payeeBankdetail
$sql = "SELECT pd.payeeDetail_id, pd.payeeDetailName, pd.payeeDetailNameAccountNum, pb.payeeBankdetailName
        FROM [PaymentDetail].[dbo].[payeeDetail] pd
        LEFT JOIN [PaymentDetail].[dbo].[payeeBankdetail] pb
        ON pd.payeeBankdetail_id = pb.payeeBankdetail_id";

$params = [];
if (!empty($search)) {
    $sql .= " WHERE pd.payeeDetailName LIKE ? OR pd.payeeDetailNameAccountNum LIKE ? OR pb.payeeBankdetailName LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}

// Count total records
$count_sql = "SELECT COUNT(*) AS total
              FROM [PaymentDetail].[dbo].[payeeDetail] pd
              LEFT JOIN [PaymentDetail].[dbo].[payeeBankdetail] pb
              ON pd.payeeBankdetail_id = pb.payeeBankdetail_id";

if (!empty($search)) {
    $count_sql .= " WHERE pd.payeeDetailName LIKE ? OR pd.payeeDetailNameAccountNum LIKE ? OR pb.payeeBankdetailName LIKE ?";
}

$count_stmt = sqlsrv_prepare($conn, $count_sql, $params);
if (!$count_stmt || !sqlsrv_execute($count_stmt)) {
    die("Count Query Error: " . print_r(sqlsrv_errors(), true));
}
$total_records = sqlsrv_fetch_array($count_stmt, SQLSRV_FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Add pagination
$sql .= " ORDER BY pd.payeeDetail_id  desc OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
$params[] = $offset;
$params[] = $records_per_page;

$stmt = sqlsrv_prepare($conn, $sql, $params);
if (!$stmt) {
    die("SQL Prepare Error: " . print_r(sqlsrv_errors(), true));
}
?>

<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payee Detail List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">

<h2 class="text-center mb-4">PAYEE DETAIL LIST</h2>

<form method="GET" class="d-flex justify-content-end mb-3">
    <input type="text" name="search" class="form-control w-25 me-2" placeholder="Search Payee or Bank" value="<?php echo htmlspecialchars($search); ?>">
    <button type="submit" class="btn btn-primary">Search</button>
</form>

<table class="table table-bordered">
    <thead class="table-dark">
        <tr>
            <th>SN</th>
            <th>Payee Name</th>
            <th>Bank Name</th>
            <th>Account Number</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $sn = ($current_page - 1) * $records_per_page + 1;
        if (sqlsrv_execute($stmt)) {
            $found = false;
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $found = true;
                echo "<tr>
                    <td>{$sn}</td>
                    <td>{$row['payeeDetailName']}</td>
                    <td>{$row['payeeBankdetailName']}</td>
                    <td>{$row['payeeDetailNameAccountNum']}</td>
                    <td>
                        <a href='payeeDetailEdit.php?payeeDetail_id={$row['payeeDetail_id']}' class='btn btn-warning btn-sm'>Edit</a>
                    </td>
                </tr>";
                $sn++;
            }
            if (!$found) {
                echo "<tr><td colspan='5' class='text-center'>No records found</td></tr>";
            }
        } else {
            echo "<tr><td colspan='5' class='text-center'>Query Execution Error: " . print_r(sqlsrv_errors(), true) . "</td></tr>";
        }
        ?>
    </tbody>
</table>

<nav class="d-flex justify-content-center">
    <ul class="pagination">
        <?php if ($current_page > 1): ?>
            <li class="page-item"><a class="page-link" href="?page=1&search=<?php echo urlencode($search); ?>">First</a></li>
            <li class="page-item"><a class="page-link" href="?page=<?php echo $current_page - 1; ?>&search=<?php echo urlencode($search); ?>">Prev</a></li>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>

        <?php if ($current_page < $total_pages): ?>
            <li class="page-item"><a class="page-link" href="?page=<?php echo $current_page + 1; ?>&search=<?php echo urlencode($search); ?>">Next</a></li>
            <li class="page-item"><a class="page-link" href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>">Last</a></li>
        <?php endif; ?>
    </ul>
</nav>

<div class="text-center mt-3">
    <a href="payeeDetailInsert.php" class="btn btn-success">Add New Payee</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
