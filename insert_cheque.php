<?php
session_start(); // Start the session
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit();
}

include 'db.php'; // Ensure the correct path to the db file

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form values
    $chequeDate = $_POST['ChequeDate'];
    $payeeNameId = $_POST['ChequePayeeNameId'];
    $amount = $_POST['Amount'];
    $chequeNumber = $_POST['ChequeNumber'];
    $detail = $_POST['Detail'];
    $username = $_POST['Username']; // Retrieved from session earlier

    // Prepare the SQL query to insert the data into the database
    $sql = "INSERT INTO [PaymentDetail].[dbo].[Cheques] 
        (ChequeDate, ChequePayeeNameId, Amount, ChequeNumber, Detail, Username)
        VALUES (?, ?, ?, ?, ?, ?)";


    // Prepare and execute the query using SQL Server
    $params = array($chequeDate, $payeeNameId, $amount, $chequeNumber, $detail, $username);
    $stmt = sqlsrv_query($conn, $sql, $params);

    // Check if the query was executed successfully
    if ($stmt) {
        // Redirect to a success page or show a success message
        header('Location: printCheque.php'); // Adjust this to your needs
        exit();
    } else {
        // Output error message if the query failed
        echo "Error: " . print_r(sqlsrv_errors(), true);
    }
}
?>
