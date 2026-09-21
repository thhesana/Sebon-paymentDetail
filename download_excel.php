<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    exit('Unauthorized access');
}

include 'db.php';

$groupId = isset($_GET['groupId']) ? (int)$_GET['groupId'] : 0;
if ($groupId <= 0) {
    exit('Invalid Group ID');
}

// Fetch transactions
$sql = "
SELECT 
    t.PaytransactionDetail_id,
    t.PaymentDetailGroupId,
    t.amount,
    t.remarks,
    p.payeeDetailName,
    p.payeeDetailNameAccountNum,
    b.payeeBankdetailName
FROM [PaymentDetail].[dbo].[PaytransactionDetail] t
JOIN [PaymentDetail].[dbo].[payeeDetail] p
    ON t.payeeDetail_id = p.payeeDetail_id
LEFT JOIN [PaymentDetail].[dbo].[payeeBankdetail] b
    ON p.payeeBankdetail_id = b.payeeBankdetail_id
LEFT JOIN [Employee Insurance Detail ].[dbo].[empinfo_rank] r
    ON p.Empcode = r.emp_code
WHERE t.PaymentDetailGroupId = ?
ORDER BY
    CASE WHEN r.emp_rank IS NULL THEN 1 ELSE 0 END,
    r.emp_rank,
    t.PaytransactionDetail_id
";

$params = [$groupId];
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    die("SQL Error: " . print_r(sqlsrv_errors(), true));
}

// Nepali/Indian comma formatting with 2 decimals
function formatNepaliComma($amount) {
    $amount = number_format((float)$amount, 2, '.', ''); // always 2 decimals
    $parts = explode('.', $amount);
    $integerPart = $parts[0];
    $decimalPart = $parts[1];

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

// Number to words
function convertToWords($number) {
    $words = [
        '0' => '', '1' => 'One', '2' => 'Two', '3' => 'Three', '4' => 'Four', '5' => 'Five',
        '6' => 'Six', '7' => 'Seven', '8' => 'Eight', '9' => 'Nine', '10' => 'Ten',
        '11' => 'Eleven', '12' => 'Twelve', '13' => 'Thirteen', '14' => 'Fourteen', '15' => 'Fifteen',
        '16' => 'Sixteen', '17' => 'Seventeen', '18' => 'Eighteen', '19' => 'Nineteen',
        '20' => 'Twenty', '30' => 'Thirty', '40' => 'Forty', '50' => 'Fifty',
        '60' => 'Sixty', '70' => 'Seventy', '80' => 'Eighty', '90' => 'Ninety'
    ];

    if ($number == 0) return '';
    elseif ($number < 20) return $words[$number];
    elseif ($number < 100) return $words[(floor($number / 10)) * 10] . " " . $words[$number % 10];
    elseif ($number < 1000) return $words[floor($number / 100)] . " Hundred " . convertToWords($number % 100);
    elseif ($number < 100000) return convertToWords(floor($number / 1000)) . " Thousand " . convertToWords($number % 1000);
    elseif ($number < 10000000) return convertToWords(floor($number / 100000)) . " Lakh " . convertToWords($number % 100000);
    elseif ($number < 1000000000) return convertToWords(floor($number / 10000000)) . " Crore " . convertToWords($number % 10000000);
    else return convertToWords(floor($number / 1000000000)) . " Arab " . convertToWords($number % 1000000000);
}

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

// ============================
//  OUTPUT AS EXCEL
// ============================
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=PaymentGroup_$groupId.xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "<table border='1'>";
echo "<tr><th colspan='6'>Transaction Detail</th></tr>";
echo "<tr>
        <th>S.N</th>
        <th>Name</th>
        <th>Bank Name</th>
        <th>Account Number</th>
        <th>Amount (Rs.)</th>
        <th>Remarks</th>
      </tr>";

$sn = 1;
$totalAmount = 0;
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $formattedAmount = formatNepaliComma($row['amount']);
    $totalAmount += $row['amount'];

    echo "<tr>
            <td>{$sn}</td>
            <td>{$row['payeeDetailName']}</td>
            <td>{$row['payeeBankdetailName']}</td>
            <td style=\"mso-number-format:'\@'\">{$row['payeeDetailNameAccountNum']}</td>
            <td style=\"mso-number-format:'\@'\">{$formattedAmount}</td>
            <td>{$row['remarks']}</td>
          </tr>";
    $sn++;
}

if ($sn > 1) {
    $formattedTotal = formatNepaliComma($totalAmount);
    $amountInWords = convertAmountToWords($totalAmount);

    echo "<tr style='font-weight:bold;'>
            <td colspan='4' align='right'>Total Amount</td>
            <td style=\"mso-number-format:'\@'\">{$formattedTotal}</td>
            <td></td>
          </tr>";
    echo "<tr>
            <td colspan='6'><strong>Amount (in Words): Rs. </strong>{$amountInWords}.</td>
          </tr>";
} else {
    echo "<tr><td colspan='6' align='center'>No transactions found.</td></tr>";
}

echo "</table>";
