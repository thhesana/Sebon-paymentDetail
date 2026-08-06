<?php
// Connect to SQL Server
$serverName = "DESKTOP-IGPDORH";  // Your SQL server name
$connectionOptions = array(
    "Database" => "PaymentDetail",  // Your database name
    "Uid" => "sa",        // Your SQL Server username
    "PWD" => "Lazy-Car92" // Your SQL Server password
);

try {
    $conn = new PDO("sqlsrv:server=$serverName;Database=PaymentDetail", $connectionOptions['Uid'], $connectionOptions['PWD']);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // SQL query to get cheque data
    $sql = "SELECT [ChequeNumber], [ChequeDate], [PayeeName], [Amount] FROM [dbo].[Cheques] WHERE ChequeID = 1";
    $stmt = $conn->query($sql);
    $cheque = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cheque) {
        $chequeNumber = $cheque['ChequeNumber'];
        $chequeDate   = $cheque['ChequeDate'];
        $payeeName    = $cheque['PayeeName'];
        $amount       = $cheque['Amount'];
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

/**
 * Function to format the amount using the Nepali (Indian) comma system.
 */
function formatNepaliComma($amount) {
    $amountStr = (string)$amount;
    $amountParts = explode('.', $amountStr);
    $integerPart = $amountParts[0];
    $decimalPart = isset($amountParts[1]) ? $amountParts[1] : '';
    
    $integerPart = str_replace(',', '', $integerPart);
    $len = strlen($integerPart);
    
    if ($len > 3) {
        $lastThree = substr($integerPart, -3);
        $restUnits = substr($integerPart, 0, $len - 3);
        $restUnits = preg_replace("/\B(?=(\d{2})+(?!\d))/", ",", $restUnits);
        $formattedInteger = $restUnits . "," . $lastThree;
    } else {
        $formattedInteger = $integerPart;
    }
    
    return $decimalPart === "00" || empty($decimalPart) ? $formattedInteger : $formattedInteger . '.' . $decimalPart;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cheque Print</title>
    <style>
        /* Cheque container with proper dimensions */
        .cheque-container {
            position: relative;
            width: 19cm; /* Cheque width */
            height: 9cm; /* Cheque height */
            border: 1px solid #000;
        }
        /* Define the font size to 8pt */
        .cheque-container div {
            font-size: 8pt;
            font-family: Arial, sans-serif;
            position: absolute;
        }
        /* Date: Positioned at 14cm from left, 1.2cm from top */
        .cheque-date {
            left: 14cm;
            top: 1.2cm;
        }
        /* Payee: Positioned at 3.5cm from left, 2.3cm from top */
        .cheque-payee {
            left: 3.5cm;
            top: 2.3cm;
        }
        /* Numeric Amount: Positioned at 14.5cm from left, 3.5cm from top */
        .cheque-amount-numeric {
            left: 14.5cm;
            top: 3.5cm;
        }
        /* Amount in Words: Positioned at 2.5cm from left, 3cm from top */
        .cheque-amount-words {
            left: 2.5cm;
            top: 3cm;
            width: 14cm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        /* Cheque Number: Positioned at 1cm from left, 7.5cm from top */
        .cheque-number {
            left: 1cm;
            top: 7.5cm;
        }
    </style>
</head>
<body>
    <div class="cheque-container">
        <div class="cheque-date">
            D <?php echo htmlspecialchars($chequeDate); ?>
        </div>
        <div class="cheque-payee">
            Pa <?php echo htmlspecialchars($payeeName); ?>
        </div>
        <div class="cheque-amount-numeric">
            Rs. <?php echo formatNepaliComma($amount); ?>
        </div>
        <div class="cheque-amount-words">
            Words: <?php echo formatNepaliComma($amount); ?> Only
        </div>
        <div class="cheque-number">
            Ch: <?php echo htmlspecialchars($chequeNumber); ?>
        </div>
    </div>
    
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
