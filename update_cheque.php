<?php
session_start(); // Start the session
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit();
}

include 'db.php'; // Include database connection

// Check if the form data is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the form data
    $cheque_number = $_POST['ChequeNumber'];
    $cheque_date = $_POST['ChequeDate'];
    $payee_name_id = $_POST['ChequePayeeNameId'];
    $amount = $_POST['Amount'];
    $status = $_POST['Status'];

    // Update the cheque details in the database
    $sql = "UPDATE [PaymentDetail].[dbo].[Cheques] SET ChequeDate = ?, ChequePayeeNameId = ?, Amount = ?, Status = ? WHERE ChequeNumber = ?";
    $params = array($cheque_date, $payee_name_id, $amount, $status, $cheque_number);

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true)); // If there's an error with the query
    }

    // Redirect to the print cheque page or another page after successful update
    header("Location: printcheque.php?ChequeNumber=" . $cheque_number);
    exit();
}
?>
