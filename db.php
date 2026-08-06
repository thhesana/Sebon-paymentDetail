<?php
$serverName = "DESKTOP-IGPDORH"; // Your SQL Server name (could be 'localhost\SQLEXPRESS' if using SQL Express)
$connectionOptions = array(
    "Database" => "PaymentDetail", // Your database name
    "Uid" => "sa", // Your SQL Server username
    "PWD" => "Lazy-Car92" // Your SQL Server password
);

// Establishes the connection
$conn = sqlsrv_connect($serverName, $connectionOptions);

// Check if the connection is successful
if ($conn === false) {
    die(print_r(sqlsrv1_errors(), true));
}
?>
