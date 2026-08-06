<?php
session_start(); // Start the session
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username']; // Get logged-in username
include 'db.php'; // Include database connection

// Handle search query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT 
    c.[ChequeNumber],
    c.[ChequeDate],
    cp.[PayeeName],
    c.[Amount],
    cp.[PayeeEmail],
    pc.[payeeCategoryName], c.[Status],
    c.[Username] as 'PreparedBy'
FROM 
    [PaymentDetail].[dbo].[Cheques] c
INNER JOIN 
    [PaymentDetail].[dbo].[ChequePayeeName] cp 
    ON c.ChequePayeeNameId = cp.PayeeID
INNER JOIN 
    [PaymentDetail].[dbo].[payeeCategory] pc
    ON cp.payeeCategoryid = pc.payeeCategoryid
";

$params = [];
if (!empty($search)) {
    $sql .= " WHERE cp.PayeeName LIKE ? OR cp.PayeeEmail LIKE ? OR pc.payeeCategoryName LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}

// Prepare the statement
$stmt = sqlsrv_prepare($conn, $sql, $params);

// Check if the statement is valid
if ($stmt === false) {
    die("SQL Error: " . print_r(sqlsrv_errors(), true));
}
?>

<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: darkblue;
            color: white;
        }
        .search-form {
            text-align: right;
            margin-bottom: 10px;
        }
        .add-button, .edit-button {
            margin-top: 20px;
            display: inline-block;
            background-color: darkblue;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
        }
        .add-button:hover, .edit-button:hover {
            background-color: blue;
        }
        /* Print styles */
        @media print {
            body * {
                visibility: hidden;
            }
            table, table * {
                visibility: visible;
            }
            .add-button {
                display: none;
            }
        }
    </style>
</head>
<body>

    <h2>Report</h2>
    <form method="GET" class="search-form">
        <input type="text" name="search" placeholder="Search Payee" value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">Search</button>
    </form>
    
    <table>
        <tr>
            <th>Cheque Number</th>
            <th>Cheque Date</th>
            <th>Payee Name</th>
            <th>Amount</th>
            <th>Payee Email</th>
            <th>Category</th>
            <th>Status</th>
			<th>Prepared By</th>
			<th>Signature</th>
        </tr>
        <?php
        if (sqlsrv_execute($stmt)) {
            $found = false;
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $found = true;
                echo "<tr>
                    <td>{$row['ChequeNumber']}</td>
                    <td>" . date_format($row['ChequeDate'], 'Y-m-d') . "</td>
                    <td>{$row['PayeeName']}</td>
                    <td>{$row['Amount']}</td>
                    <td>{$row['PayeeEmail']}</td>
                    <td>{$row['payeeCategoryName']}</td>
                    <td>{$row['Status']}</td>
                    <td>{$row['PreparedBy']}</td>
					<td></td>

                </tr>";
            }
            if (!$found) {
                echo "<tr><td colspan='8'>No records found</td></tr>";
            }
        } else {
            echo "<tr><td colspan='8'>Query Execution Error: " . print_r(sqlsrv_errors(), true) . "</td></tr>";
        }
        ?>
    </table>

    <!-- Add New Payee Button -->
    <a href="chequePayeeInsert.php" class="add-button" onclick="window.print()">Print Report</a>

</body>
</html>
