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

    // Get ChequeNumber from GET
    if (!isset($_GET['chequeNumber']) || empty($_GET['chequeNumber'])) {
        die("Cheque Number is required.");
    }

    $chequeNumber = $_GET['chequeNumber'];

    // SQL query to get cheque data
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
                c.[ChequeNumber] = :chequeNumber";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':chequeNumber', $chequeNumber, PDO::PARAM_STR);
    $stmt->execute();
    $cheque = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cheque) {
        die("Cheque not found.");
    }

    $chequeDate   = $cheque['ChequeDate'];
    $payeeName    = $cheque['PayeeName']; 
    $amount       = $cheque['Amount'];
    $status       = $cheque['Status'];
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Function to format the amount using the Nepali (Indian) comma system.
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

// Function to convert numbers to words in English.
function convertToWords($number) {
    $words = array(
        '0' => '', '1' => 'One', '2' => 'Two', '3' => 'Three', '4' => 'Four', '5' => 'Five', 
        '6' => 'Six', '7' => 'Seven', '8' => 'Eight', '9' => 'Nine', '10' => 'Ten', 
        '11' => 'Eleven', '12' => 'Twelve', '13' => 'Thirteen', '14' => 'Fourteen', '15' => 'Fifteen', 
        '16' => 'Sixteen', '17' => 'Seventeen', '18' => 'Eighteen', '19' => 'Nineteen', 
        '20' => 'Twenty', '30' => 'Thirty', '40' => 'Forty', '50' => 'Fifty', 
        '60' => 'Sixty', '70' => 'Seventy', '80' => 'Eighty', '90' => 'Ninety'
    );

    if ($number == 0) {
        return '';
    } elseif ($number < 20) {
        return $words[$number];
    } elseif ($number < 100) {
        return $words[(floor($number / 10)) * 10] . " " . $words[$number % 10];
    } elseif ($number < 1000) {
        return $words[floor($number / 100)] . " Hundred " . convertToWords($number % 100);
    } elseif ($number < 100000) {
        return convertToWords(floor($number / 1000)) . " Thousand " . convertToWords($number % 1000);
    } elseif ($number < 10000000) {
        return convertToWords(floor($number / 100000)) . " Lakh " . convertToWords($number % 100000);
    } elseif ($number < 1000000000) {
        return convertToWords(floor($number / 10000000)) . " Crore " . convertToWords($number % 10000000);
    } elseif ($number < 100000000000) {
        return convertToWords(floor($number / 1000000000)) . " Arab " . convertToWords($number % 1000000000);
    } else {
        return convertToWords(floor($number / 100000000000)) . " Kharab " . convertToWords($number % 100000000000);
    }
}

// Function to convert amount with paise
function convertAmountToWords($amount) {
    $amountParts = explode('.', number_format($amount, 2, '.', ''));
    $rupees = (int)$amountParts[0];
    $paise = isset($amountParts[1]) ? (int)$amountParts[1] : 0;

    $rupeeWords = convertToWords($rupees) . " ";

    if ($paise > 0) {
        $paiseWords = convertToWords($paise) . " Paisa";
        return $rupeeWords . " and " . $paiseWords . " Only";
    } else {
        return $rupeeWords . " Only";
    }
}

$amountInWords = convertAmountToWords($amount);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cheque Print</title>
    <style>
        @page {
            size: 19cm 9cm; /* Landscape cheque size */
            margin: 0;
        }

        body {
            width: 19cm;
            height: 9cm;
            margin: 0;
            padding: 0;
        }

        .cheque-container {
            position: relative;
            width: 19cm;
            height: 9cm;
        }

        .cheque-container div {
            font-size: 14pt;
            font-family: Arial, sans-serif;
            position: absolute;
        }

        /* Adjusting positions based on a standard cheque format */
        .cheque-date {
            position: absolute;
            left: 13.9cm;
            top: 1.0cm;
            letter-spacing: 4px; /* Fine-tuned spacing between digits */
        }

        .cheque-payee { left: 4.2cm; top: 2cm; }
        .cheque-amount-numeric { left: 14cm; top: 3.2cm; }
        .cheque-amount-words { left: 2.8cm; top: 2.6cm; width: 10cm; white-space: normal; }

        @media print {
            .print-button { display: none; }
        }
    </style>
</head>
<body>
    <div class="cheque-container">
        <div class="cheque-date"><?php echo implode(' ', str_split(date('dmY', strtotime($chequeDate)))); ?></div>
        <div class="cheque-payee"><?php echo htmlspecialchars($payeeName); ?></div>
        <div class="cheque-amount-numeric">Rs. <?php echo formatNepaliComma($amount); ?></div>
        <div class="cheque-amount-words"><?php echo nl2br(htmlspecialchars($amountInWords)); ?></div>
    </div>
    <button class="print-button" onclick="printCheque();">Print</button>

    <script>
        function printCheque() {
            window.print();
            // Update the status of the cheque to 'PRINTED'
            var chequeNumber = "<?php echo $chequeNumber; ?>";

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "update_cheque_status.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                if (xhr.status == 200) {
                    console.log("Cheque status updated successfully.");
                } else {
                    console.log("Failed to update cheque status.");
                }
            };
            xhr.send("chequeNumber=" + chequeNumber);
        }
    </script>
</body>
</html>
