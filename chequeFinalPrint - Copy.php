<?php
// Database connection
$serverName = "DESKTOP-IGPDORH";  // Your SQL server name
$databaseName = "PaymentDetail"; // Database name
$username = "sa";        // SQL Server username
$password = "Lazy-Car92"; // SQL Server password

try {
    // Establishing PDO Connection
    $conn = new PDO("sqlsrv:Server=$serverName;Database=$databaseName", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // SQL query to get cheque data with PayeeName
    $sql = "SELECT 
                c.[ChequeNumber],
                c.[ChequeDate],
                c.[Amount],
                c.[Status],
                p.[PayeeName] AS PayeeName
            FROM 
                [dbo].[Cheques] c
            JOIN 
                [dbo].[ChequePayeeName] p 
                ON c.[ChequePayeeNameId] = p.[PayeeID]
            WHERE 
                c.[ChequeID] = 1";

    $stmt = $conn->query($sql);
    $cheque = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cheque) {
        $chequeNumber = $cheque['ChequeNumber'];
        $chequeDate   = $cheque['ChequeDate'];
        $payeeName    = $cheque['PayeeName']; 
        $amount       = $cheque['Amount'];
        $status       = $cheque['Status'];
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
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

/**
 * Function to convert numbers to words in English.
 */
function convertToWords($number) {
    $words = array(
        '0' => '', '1' => 'One', '2' => 'Two', '3' => 'Three', '4' => 'Four', '5' => 'Five', 
        '6' => 'Six', '7' => 'Seven', '8' => 'Eight', '9' => 'Nine', '10' => 'Ten', 
        '11' => 'Eleven', '12' => 'Twelve', '13' => 'Thirteen', '14' => 'Fourteen', '15' => 'Fifteen', 
        '16' => 'Sixteen', '17' => 'Seventeen', '18' => 'Eighteen', '19' => 'Nineteen', 
        '20' => 'Twenty', '30' => 'Thirty', '40' => 'Forty', '50' => 'Fifty', 
        '60' => 'Sixty', '70' => 'Seventy', '80' => 'Eighty', '90' => 'Ninety'
    );

    if ($number < 20) {
        return $words[$number];
    } elseif ($number < 100) {
        return $words[(floor($number / 10)) * 10] . " " . $words[$number % 10];
    } elseif ($number < 1000) {
        return $words[floor($number / 100)] . " Hundred " . convertToWords($number % 100);
    } elseif ($number < 100000) {
        return convertToWords(floor($number / 1000)) . " Thousand " . convertToWords($number % 1000);
    } elseif ($number < 10000000) {
        return convertToWords(floor($number / 100000)) . " Lakh " . convertToWords($number % 100000);
    } else {
        return convertToWords(floor($number / 10000000)) . " Crore " . convertToWords($number % 10000000);
    }
}

$amountInWords = convertToWords($amount) . " Rupees Only";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cheque Print</title>
    <style>
        .cheque-container {
            position: relative;
            width: 19cm;
            height: 9cm;
            border: 1px solid #000;
        }
        .cheque-container div {
            font-size: 8pt;
            font-family: Arial, sans-serif;
            position: absolute;
        }
        .cheque-date {
            left: 14cm;
            top: 1.2cm;
        }
        .cheque-payee {
            left: 3.5cm;
            top: 2.3cm;
        }
        .cheque-amount-numeric {
            left: 14.5cm;
            top: 3.5cm;
        }
        .cheque-amount-words {
            left: 2.5cm;
            top: 3cm;
            width: 14cm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .cheque-number {
            left: 1cm;
            top: 7.5cm;
        }
        .cheque-status {
            left: 14.5cm;
            top: 7.5cm;
        }
    </style>
</head>
<body>
    <div class="cheque-container">
        <div class="cheque-date">
            D: <?php echo htmlspecialchars($chequeDate); ?>
        </div>
        <div class="cheque-payee">
            Payee: <?php echo htmlspecialchars($payeeName); ?>
        </div>
        <div class="cheque-amount-numeric">
            Rs. <?php echo formatNepaliComma($amount); ?>
        </div>
        <div class="cheque-amount-words">
            Amount in Words: <?php echo htmlspecialchars($amountInWords); ?>
        </div>
        <div class="cheque-number">
            Cheque No: <?php echo htmlspecialchars($chequeNumber); ?>
        </div>
        <div class="cheque-status">
            Status: <?php echo htmlspecialchars($status); ?>
        </div>
    </div>
</body>
</html>
