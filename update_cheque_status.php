<?php
// Database connection
$serverName = "DESKTOP-IGPDORH";
$databaseName = "PaymentDetail";
$username = "sa";
$password = "Lazy-Car92";

try {
    // Establishing PDO Connection
    $conn = new PDO("sqlsrv:Server=$serverName;Database=$databaseName", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get cheque number from POST
    if (isset($_POST['chequeNumber']) && !empty($_POST['chequeNumber'])) {
        $chequeNumber = $_POST['chequeNumber'];

        // SQL query to update the status
        $sql = "UPDATE [dbo].[Cheques]
                SET [Status] = 'PRINTED'
                WHERE [ChequeNumber] = :chequeNumber";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':chequeNumber', $chequeNumber, PDO::PARAM_STR);
        $stmt->execute();

        echo "Cheque status updated to PRINTED.";
    } else {
        echo "Cheque number not provided.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
