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

$sql = "SELECT cp.PayeeID, cp.PayeeName, cp.PayeeEmail, pg.gendername AS PayeeGender, 
               pc.payeeCategoryName AS PayeeCategory, ps.payeStatusName AS PayeeStatus, cp.CreatedAt 
        FROM [PaymentDetail].dbo.ChequePayeeName cp
        LEFT JOIN [PaymentDetail].dbo.payeegenderTBL pg ON cp.payeegenderTBLid = pg.payeegenderTBLid
        LEFT JOIN [PaymentDetail].dbo.payeeCategory pc ON cp.payeeCategoryid = pc.payeeCategoryid
        LEFT JOIN [PaymentDetail].dbo.payeStatusTBL ps ON cp.payeStatusTBLid = ps.payeStatusTBLid";

$params = [];
if (!empty($search)) {
    $sql .= " WHERE cp.PayeeName LIKE ? OR cp.PayeeEmail LIKE ? OR pg.gendername like ? OR pc.payeeCategoryName LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%" , "%$search%"];
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
    <title>Cheque Payee List</title>
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
    </style>
</head>
<body>

    <h2>Cheque Payee List</h2>
    <form method="GET" class="search-form">
        <input type="text" name="search" placeholder="Search Payee" value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">Search</button>
    </form>
    
    <table>
        <tr>
            <th>Payee ID</th>
            <th>Payee Name</th>
            <th>Email</th>
            <th>Gender</th>
            <th>Category</th>
            <th>Status</th>
            <th>Created At</th>
            <th>Action</th> <!-- New column for Edit button -->
        </tr>
        <?php
        if (sqlsrv_execute($stmt)) {
            $found = false;
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $found = true;
                echo "<tr>
                    <td>{$row['PayeeID']}</td>
                    <td>{$row['PayeeName']}</td>
                    <td>{$row['PayeeEmail']}</td>
                    <td>{$row['PayeeGender']}</td>
                    <td>{$row['PayeeCategory']}</td>
                    <td>{$row['PayeeStatus']}</td>
                    <td>" . (isset($row['CreatedAt']) ? $row['CreatedAt']->format('Y-m-d H:i:s') : 'N/A') . "</td>
                    <td><a href='chequePayeeIEdit.php?PayeeID={$row['PayeeID']}' class='edit-button'>Edit</a></td> <!-- Edit button -->
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
    <a href="chequePayeeInsert.php" class="add-button">Add New Payee</a>

</body>
</html>
