<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    exit('Unauthorized access');
}

include 'db.php';

// Get groupId from GET
$groupId = isset($_GET['groupId']) ? (int)$_GET['groupId'] : 0;
if ($groupId <= 0) {
    exit('Invalid Group ID');
}

// Fetch transactions for the group, including bank name
$sql = "
SELECT 
    t.PaytransactionDetail_id,
    t.PaymentDetailGroupId,
    t.amount,
    t.remarks,
    t.Trnxdate,
    p.payeeDetailName,
    p.payeeDetailNameAccountNum,
    b.payeeBankdetailName
FROM [PaymentDetail].[dbo].[PaytransactionDetail] t
JOIN [PaymentDetail].[dbo].[payeeDetail] p
    ON t.payeeDetail_id = p.payeeDetail_id
LEFT JOIN [PaymentDetail].[dbo].[payeeBankdetail] b
    ON p.payeeBankdetail_id = b.payeeBankdetail_id
WHERE t.PaymentDetailGroupId = ?
ORDER BY t.PaytransactionDetail_id
";

$params = [$groupId];
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    die("SQL Error: " . print_r(sqlsrv_errors(), true));
}

// Get Trnxdate from first row (fetch separately to get date before the while loop)
$sqlDate = "SELECT TOP 1 Trnxdate FROM [PaymentDetail].[dbo].[PaytransactionDetail] WHERE PaymentDetailGroupId = ?";
$stmtDate = sqlsrv_query($conn, $sqlDate, [$groupId]);
$trnxDate = '';
if ($stmtDate) {
    $dateRow = sqlsrv_fetch_array($stmtDate, SQLSRV_FETCH_ASSOC);
    if ($dateRow && $dateRow['Trnxdate']) {
        // sqlsrv returns DateTime object
        if ($dateRow['Trnxdate'] instanceof DateTime) {
            $trnxDate = $dateRow['Trnxdate']->format('d-M-Y'); // gives 06-Apr-2026
        } else {
            $trnxDate = date('d/m/Y', strtotime($dateRow['Trnxdate']));
        }
    }
}

// Nepali comma format
function formatNepaliComma($amount) {
    $amountStr = number_format((float)$amount, 2, '.', '');
    $amountParts = explode('.', $amountStr);
    $integerPart = $amountParts[0];
    $decimalPart = $amountParts[1];

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

    return $formattedInteger . '.' . $decimalPart;
}

// Convert numbers to words
function convertToWords($number) {
    $words = array(
        '0' => '', '1' => 'One', '2' => 'Two', '3' => 'Three', '4' => 'Four', '5' => 'Five', 
        '6' => 'Six', '7' => 'Seven', '8' => 'Eight', '9' => 'Nine', '10' => 'Ten', 
        '11' => 'Eleven', '12' => 'Twelve', '13' => 'Thirteen', '14' => 'Fourteen', '15' => 'Fifteen', 
        '16' => 'Sixteen', '17' => 'Seventeen', '18' => 'Eighteen', '19' => 'Nineteen', 
        '20' => 'Twenty', '30' => 'Thirty', '40' => 'Forty', '50' => 'Fifty', 
        '60' => 'Sixty', '70' => 'Seventy', '80' => 'Eighty', '90' => 'Ninety'
    );

    if ($number == 0) return '';
    elseif ($number < 20) return $words[$number];
    elseif ($number < 100) return $words[(floor($number / 10)) * 10] . " " . $words[$number % 10];
    elseif ($number < 1000) return $words[floor($number / 100)] . " Hundred " . convertToWords($number % 100);
    elseif ($number < 100000) return convertToWords(floor($number / 1000)) . " Thousand " . convertToWords($number % 1000);
    elseif ($number < 10000000) return convertToWords(floor($number / 100000)) . " Lakh " . convertToWords($number % 100000);
    elseif ($number < 1000000000) return convertToWords(floor($number / 10000000)) . " Crore " . convertToWords($number % 10000000);
    else return convertToWords(floor($number / 1000000000)) . " Arab " . convertToWords($number % 1000000000);
}

// Convert amount with paise
function convertAmountToWords($amount) {
    $amountParts = explode('.', number_format($amount, 2, '.', ''));
    $rupees = (int)$amountParts[0];
    $paise = (int)$amountParts[1];

    $rupeeWords = convertToWords($rupees);

    if ($paise > 0) {
        $paiseWords = convertToWords($paise) . " Paisa";
        return $rupeeWords . " and " . $paiseWords . " Only";
    } else {
        return $rupeeWords . " Only";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pay Transaction Detail</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4 text-center">
    <h3><u>Transaction Detail</u></h3>
</div>

<!-- ✅ Batch ID (left) and Transaction Entry Date (right) -->
<div class="container mt-2 mb-3">
    <div class="d-flex justify-content-between">
        <span><strong>Transaction Batch ID:</strong> <?php echo htmlspecialchars($groupId); ?></span>
        
    </div>
</div>

<div class="container mt-2">
    <table class="table table-bordered">
        <thead style="font-weight:bold;">
            <tr>
                <th>S.N</th>
                <th>Name</th>
                <th>Bank Name</th>
                <th>Account Number</th>
                <th>Amount (in Rs.)</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sn = 1;
            $totalAmount = 0;
            $found = false;

            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $found = true;
                $formattedAmount = formatNepaliComma($row['amount']);
                $totalAmount += $row['amount'];
                ?>
                <tr>
                    <td><?php echo $sn; ?></td>
                    <td><?php echo $row['payeeDetailName']; ?></td>
                    <td><?php echo $row['payeeBankdetailName']; ?></td>
                    <td><?php echo $row['payeeDetailNameAccountNum']; ?></td>
                    <td><?php echo $formattedAmount; ?></td>
                    <td><?php echo $row['remarks']; ?></td>
                </tr>
                <?php
                $sn++;
            }

            if ($found) {
                $formattedTotal = formatNepaliComma($totalAmount);
                $amountInWords = convertAmountToWords($totalAmount);
                ?>
                <tr style="font-weight:bold;">
                    <td colspan="4" class="text-end"><b>Total Amount</b></td>
                    <td colspan="2"><?php echo $formattedTotal; ?></td>
                </tr>
                <tr>
                    <td colspan="6"><strong>Amount (in Words): Rs. </strong><?php echo $amountInWords; ?>.</td>
                </tr>
                <?php
            } else {
                echo '<tr><td colspan="6" class="text-center">No transactions found.</td></tr>';
            }
            ?>
        </tbody>
    </table> <br><br>
<br>** This is generated from SEBON MIS Application.
</div>
</body>
</html>