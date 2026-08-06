<?php
session_start(); // Start the session
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username']; // Assuming you are storing the username in session
include 'db.php'; // Ensure this is the correct path to the file

// Fetch active payee names from the database
$sql = "SELECT PayeeID, PayeeName FROM [PaymentDetail].[dbo].[ChequePayeeName] WHERE status = 'ACTIVE'";
$result = sqlsrv_query($conn, $sql);

// Insert Payee details if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payeeName = $_POST['PayeeName'];
    $payeeEmail = $_POST['PayeeEmail'];
    $payeegenderTBLid = $_POST['payeegenderTBLid'];
    $payeeCategoryid = $_POST['payeeCategoryid'];
    $payeStatusTBLid = $_POST['payeStatusTBLid'];
    $createdAt = date('Y-m-d H:i:s');

    // Prepare SQL query to insert the data
    $sql = "INSERT INTO [PaymentDetail].dbo.ChequePayeeName 
            (PayeeName, PayeeEmail, payeegenderTBLid, payeeCategoryid, payeStatusTBLid, CreatedAt) 
            VALUES (?, ?, ?, ?, ?, ?)";

    // Prepare the statement
    $stmt = sqlsrv_prepare($conn, $sql, array($payeeName, $payeeEmail, $payeegenderTBLid, $payeeCategoryid, $payeStatusTBLid, $createdAt));

    // Execute the query and check if it was successful
    if (sqlsrv_execute($stmt)) {
        echo "<script>alert('Payee details inserted successfully!'); window.location.href='chequePayeeList.php';</script>";
    } else {
        echo "Error: " . print_r(sqlsrv_errors(), true);
    }
}

// Function to fetch options for dropdowns
function fetchOptions($conn, $table, $idColumn, $nameColumn) {
    $query = "SELECT $idColumn, $nameColumn FROM [PaymentDetail].dbo.$table";
    $result = sqlsrv_query($conn, $query);
    
    $options = "";
    while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        $options .= "<option value='" . $row[$idColumn] . "'>" . $row[$nameColumn] . "</option>";
    }
    return $options;
}
?>

<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Cheque Payee Details</title>
    <!-- Include Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEJ/4XR7Yy0fJ6lg0vFqbB9Vi/TI0A59b0B9F7EnfM7Os1STL2kIX2A0LFLqR" crossorigin="anonymous">
    <!-- Custom CSS -->
    <style>
        body {
            background-color: #f4f7fc;
            font-family: 'Arial', sans-serif;
        }

        .container {
            max-width: 600px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-top: 50px;
        }

        h2 {
            text-align: center;
            color: #007bff;
        }

        label {
            font-weight: bold;
            color: #333;
        }

        input[type="text"], input[type="email"], select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            width: 100%;
            font-size: 16px;
        }

        button:hover {
            background-color: #0056b3;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group select {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Add New Cheque Payee Details</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="PayeeName">Payee Name:</label>
                <input type="text" name="PayeeName" id="PayeeName" required>
            </div>

			<div class="form-group">
				<label for="PayeeEmail">Payee Email:</label>
				<input type="email" name="PayeeEmail" id="PayeeEmail">
			</div>


            <div class="form-group">
                <label for="payeegenderTBLid">Gender:</label>
                <select name="payeegenderTBLid" id="payeegenderTBLid" required>
                    <option value="">Select Gender</option>
                    <?php echo fetchOptions($conn, 'payeegenderTBL', 'payeegenderTBLid', 'gendername'); ?>
                </select>
            </div>

            <div class="form-group">
                <label for="payeeCategoryid">Category:</label>
                <select name="payeeCategoryid" id="payeeCategoryid" required>
                    <option value="">Select Category</option>
                    <?php echo fetchOptions($conn, 'payeeCategory', 'payeeCategoryid', 'payeeCategoryName'); ?>
                </select>
            </div>

            <div class="form-group">
                <label for="payeStatusTBLid">Status:</label>
                <select name="payeStatusTBLid" id="payeStatusTBLid" required>
                    <option value="">Select Status</option>
                    <?php echo fetchOptions($conn, 'payeStatusTBL', 'payeStatusTBLid', 'payeStatusName'); ?>
                </select>
            </div>

            <button type="submit">Insert</button>
        </form>
    </div>

    <!-- Include Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-7jJpXwTz0FvVfztzYrr2S3iIMFvH9Qz3YJ1dHTzR7K8sdoXMST3K2NxPCYx8cW9B" crossorigin="anonymous"></script>
</body>
</html>
