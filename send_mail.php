<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

include 'db.php';

// Azure AD Configuration
define('TENANT_ID', '4ee6b0fa-3bdc-4fa2-8cd8-9ece2266c058');
define('CLIENT_ID', '45f45c02-2b3e-432d-a1fd-0980f50f6d59');
define('CLIENT_SECRET', '');
define('SENDER_EMAIL', 'accounts@sebon.gov.np');

$groupId = isset($_GET['groupId']) ? intval($_GET['groupId']) : 0;

if ($groupId <= 0) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid group ID']);
    exit();
}

// Nepali/Indian comma formatting with 2 decimals
function formatNepaliComma($amount) {
    $amount = number_format((float)$amount, 2, '.', '');
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

// SQL query with bank details
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
WHERE t.PaymentDetailGroupId = ?
ORDER BY t.PaytransactionDetail_id
";

$params = array($groupId);
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

// Build table rows with inline styles for email compatibility
$tableRows = '';
$sn = 1;
$totalAmount = 0;

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $formattedAmount = formatNepaliComma($row['amount']);
    $totalAmount += $row['amount'];
    
    $payeeName = isset($row['payeeDetailName']) ? htmlspecialchars($row['payeeDetailName']) : '';
    $bankName = isset($row['payeeBankdetailName']) ? htmlspecialchars($row['payeeBankdetailName']) : '';
    $accountNum = isset($row['payeeDetailNameAccountNum']) ? htmlspecialchars($row['payeeDetailNameAccountNum']) : '';
    $remarks = isset($row['remarks']) ? htmlspecialchars($row['remarks']) : '';
    
    // Alternating row colors
    $bgColor = ($sn % 2 == 0) ? '#f8f9fa' : '#ffffff';
    
    $tableRows .= "<tr style='background-color: {$bgColor};'>
        <td style='border: 1px solid #bdc3c7; padding: 10px 8px; text-align: center; color: #2c3e50; font-size: 13px;'>{$sn}</td>
        <td style='border: 1px solid #bdc3c7; padding: 10px 8px; color: #2c3e50; font-size: 13px;'>{$payeeName}</td>
        <td style='border: 1px solid #bdc3c7; padding: 10px 8px; color: #2c3e50; font-size: 13px;'>{$bankName}</td>
        <td style='border: 1px solid #bdc3c7; padding: 10px 8px; color: #2c3e50; font-size: 13px;'>{$accountNum}</td>
        <td style='border: 1px solid #bdc3c7; padding: 10px 8px; text-align: right; color: #2c3e50; font-size: 13px;'>{$formattedAmount}</td>
        <td style='border: 1px solid #bdc3c7; padding: 10px 8px; color: #2c3e50; font-size: 13px;'>{$remarks}</td>
    </tr>";
    $sn++;
}

if ($sn == 1) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No transactions found']);
    exit();
}

// Add total and amount in words with inline styles
$formattedTotal = formatNepaliComma($totalAmount);
$amountInWords = convertAmountToWords($totalAmount);

$tableRows .= "<tr style='background-color: #d5dbdb; font-weight: bold;'>
    <td colspan='4' style='border: 1px solid #95a5a6; padding: 12px 8px; text-align: right; font-size: 14px; color: #1a252f;'><strong>TOTAL AMOUNT</strong></td>
    <td style='border: 1px solid #95a5a6; padding: 12px 8px; text-align: right; font-size: 14px; color: #1a252f;'><strong>{$formattedTotal}</strong></td>
    <td style='border: 1px solid #95a5a6; padding: 12px 8px; font-size: 14px;'></td>
</tr>";

$tableRows .= "<tr style='background-color: #ebf5fb;'>
    <td colspan='6' style='border: 1px solid #aed6f1; padding: 12px 8px; color: #1a252f; font-size: 13px;'><strong>Amount in Words:</strong> Rs. {$amountInWords}</td>
</tr>";

// Get current date in proper format
$currentDate = date('F d, Y');

// Create professional and official email HTML body
$emailBody = "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Payment Transaction Report</title>
    <style>
        body { 
            font-family: 'Calibri', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            -webkit-font-smoothing: antialiased;
        }
        .email-wrapper {
            background-color: #f5f5f5;
            padding: 20px 0;
        }
        .email-container {
            max-width: 850px;
            margin: 0 auto;
            background-color: #ffffff;
            border: 1px solid #d0d0d0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .letterhead {
            background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 100%);
            color: white;
            padding: 35px 40px;
            text-align: center;
            border-bottom: 4px solid #c9a961;
        }
        .letterhead h1 {
            margin: 0 0 8px 0;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .letterhead p {
            margin: 0;
            font-size: 14px;
            opacity: 0.95;
            font-weight: 400;
        }
        .document-body {
            padding: 40px 45px;
        }
        .reference-number {
            text-align: right;
            margin-bottom: 25px;
            font-size: 12px;
            color: #555;
        }
        .reference-number strong {
            color: #1e3a5f;
        }
        .date-line {
            text-align: right;
            margin-bottom: 30px;
            font-size: 13px;
            color: #2c3e50;
        }
        .recipient-block {
            margin-bottom: 30px;
            line-height: 1.7;
        }
        .recipient-block p {
            margin: 3px 0;
            color: #2c3e50;
            font-size: 14px;
        }
        .recipient-block .recipient-title {
            font-weight: 700;
            font-size: 15px;
            color: #1a252f;
        }
        .subject-line {
            background-color: #f0f4f8;
            border-left: 5px solid #2c5282;
            padding: 15px 20px;
            margin: 25px 0;
        }
        .subject-line p {
            margin: 0;
            color: #1a252f;
            font-size: 14px;
            line-height: 1.6;
        }
        .subject-line strong {
            font-weight: 700;
            color: #1e3a5f;
        }
        .salutation {
            margin: 25px 0 20px 0;
            font-size: 14px;
            color: #2c3e50;
        }
        .body-text {
            line-height: 1.8;
            color: #2c3e50;
            font-size: 14px;
            margin-bottom: 25px;
            text-align: justify;
        }
        table { 
            border-collapse: collapse; 
            width: 100%; 
            margin: 25px 0;
            background-color: white;
            border: 2px solid #2c3e50;
        }
        .signature-section {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 2px solid #d0d0d0;
        }
        .signature-block {
            margin-top: 20px;
        }
        .signature-block p {
            margin: 6px 0;
            color: #2c3e50;
            line-height: 1.6;
            font-size: 14px;
        }
        .signature-line {
            margin-top: 60px;
            margin-bottom: 8px;
            border-bottom: 2px solid #1a252f;
            width: 250px;
        }
        .signature-name {
            font-weight: 700;
            color: #1a252f;
            font-size: 15px;
        }
        .signature-designation {
            font-weight: 600;
            color: #2c5282;
            font-size: 13px;
        }
        .signature-organization {
            font-weight: 600;
            color: #2c3e50;
            font-size: 13px;
        }
        .footer-section { 
            background: linear-gradient(to bottom, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px 40px; 
            border-top: 4px solid #2c5282;
            text-align: center;
        }
        .footer-warning {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
            padding: 12px;
            margin-bottom: 15px;
        }
        .footer-warning p {
            margin: 0;
            color: #856404;
            font-weight: 600;
            font-size: 13px;
        }
        .footer-info p {
            margin: 6px 0;
            font-size: 12px;
            color: #6c757d;
        }
        .footer-app-name {
            color: #1e3a5f;
            font-weight: 700;
        }
        .footer-copyright {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #d0d0d0;
            font-size: 11px;
            color: #868e96;
        }
    </style>
</head>
<body>
    <div class='email-wrapper'>
        <div class='email-container'>
            <!-- Official Letterhead -->
            <div class='letterhead'>
                <h1>Securities Board of Nepal</h1>
                <p>Account and Finance Section</p>
            </div>
            
            <!-- Document Body -->
            <div class='document-body'>
                
                
                <!-- Date -->
                <div class='date-line'>
                    <strong>Date:</strong> {$currentDate}
                </div>
                
                <!-- Recipient Information -->
                <div class='recipient-block'>
                    <p><strong>To,</strong></p>
                    <p class='recipient-title'>The Bank Manager</p>
                    <p>Laxmi Sunrise Bank Ltd.</p>
                    <p>SEBON Extension Counter</p>
                    
                </div>
                
                <!-- Subject Line -->
                <div class='subject-line'>
                    <p><strong>Subject:</strong> Payment Transaction Details</strong></p>
                </div>
                
                <!-- Salutation -->
                <p class='salutation'><strong>Dear Sir/Madam,</strong></p>
                
                <!-- Body Text -->
                <p class='body-text'>
                    We hereby submit the payment transaction details 
                    for your kind perusal and necessary action. The details of the beneficiaries, along with their 
                    respective bank account information and payment amounts, are provided in the table below.
                </p>
                
                <!-- Transaction Table -->
                <table border='1' cellpadding='0' cellspacing='0' style='border-collapse: collapse; width: 100%; margin: 25px 0; background-color: white; border: 2px solid #2c3e50;'>
                    <thead>
                        <tr>
                            <th colspan='6' style='background-color: #1e3a5f; color: white; padding: 14px; text-align: center; border: 1px solid #2c3e50; font-weight: 700; font-size: 15px; letter-spacing: 0.5px;'>PAYMENT TRANSACTION DETAILS</th>
                        </tr>
                        <tr>
                            <th style='width: 50px; background-color: #2c5282; color: white; padding: 12px 8px; text-align: center; border: 1px solid #1e3a5f; font-weight: 600; font-size: 13px;'>S.N</th>
                            <th style='width: 180px; background-color: #2c5282; color: white; padding: 12px 8px; text-align: center; border: 1px solid #1e3a5f; font-weight: 600; font-size: 13px;'>Payee Name</th>
                            <th style='width: 140px; background-color: #2c5282; color: white; padding: 12px 8px; text-align: center; border: 1px solid #1e3a5f; font-weight: 600; font-size: 13px;'>Bank Name</th>
                            <th style='width: 130px; background-color: #2c5282; color: white; padding: 12px 8px; text-align: center; border: 1px solid #1e3a5f; font-weight: 600; font-size: 13px;'>Account Number</th>
                            <th style='width: 110px; background-color: #2c5282; color: white; padding: 12px 8px; text-align: center; border: 1px solid #1e3a5f; font-weight: 600; font-size: 13px;'>Amount (Rs.)</th>
                            <th style='background-color: #2c5282; color: white; padding: 12px 8px; text-align: center; border: 1px solid #1e3a5f; font-weight: 600; font-size: 13px;'>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$tableRows}
                    </tbody>
                </table>
                
                <!-- Closing Text -->
                
                
                <p class='body-text'>
                    Thank you for your cooperation.
                </p>
                
                <!-- Signature Section -->
                <div class='signature-section'>
                    <div class='signature-block'>
                        <p style='margin-bottom: 15px;'><strong>Regards ,</strong></p>
                        <div class='signature-line'></div>
                        
                        <p class='signature-designation'>Account and Finance Section</p>
                        <p class='signature-organization'>Securities Board of Nepal</p>
                    </div>
                </div>
            </div>
            
            <!-- Footer Section -->
            <div class='footer-section'>
                <div class='footer-warning'>
                    <p>⚠ IMPORTANT: This is an automated email. Please do not reply to this message.</p>
                </div>
                <div class='footer-info'>
                    <p>This email was generated automatically by <span class='footer-app-name'>SEBON MIS Application</span></p>
                    <p>For inquiries, please contact: Account and Finance Section, Securities Board of Nepal</p>
                    
                </div>
                
            </div>
        </div>
    </div>
</body>
</html>
";

// Get access token and send email
function getAccessToken() {
    $tokenUrl = "https://login.microsoftonline.com/" . TENANT_ID . "/oauth2/v2.0/token";
    
    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'client_id' => CLIENT_ID,
        'client_secret' => CLIENT_SECRET,
        'scope' => 'https://graph.microsoft.com/.default',
        'grant_type' => 'client_credentials'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode != 200) {
        return null;
    }
    
    $tokenData = json_decode($response, true);
    return isset($tokenData['access_token']) ? $tokenData['access_token'] : null;
}

function sendEmail($accessToken, $emailBody, $groupId) {
    $mailUrl = "https://graph.microsoft.com/v1.0/users/" . SENDER_EMAIL . "/sendMail";
    
    $mailData = json_encode([
        'message' => [
            'subject' => "Payment Transaction Details",
            'body' => [
                'contentType' => 'HTML', 
                'content' => $emailBody
            ],
            'toRecipients' => [
                [
                    'emailAddress' => [
                        'address' => 'shristi.shakya@laxmisunrise.com'
                    ]
                ]
            ],
            'importance' => 'high'
        ],
        'saveToSentItems' => false
    ]);
    
    $ch = curl_init($mailUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $mailData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $mailResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'success' => ($httpCode == 202),
        'code' => $httpCode,
        'response' => $mailResponse
    ];
}

// Execute
$accessToken = getAccessToken();

if (!$accessToken) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Failed to obtain access token. Please verify Azure AD credentials.']);
    exit();
}

$result = sendEmail($accessToken, $emailBody, $groupId);

ob_end_clean();
header('Content-Type: application/json');

if ($result['success']) {
    echo json_encode([
        'success' => true, 
        'message' => 'Official payment transaction report sent successfully to sajan.napit@sebon.gov.np'
    ]);
} else {
    $error = json_decode($result['response'], true);
    $msg = isset($error['error']['message']) ? $error['error']['message'] : 'Failed to send email (HTTP ' . $result['code'] . ')';
    echo json_encode(['success' => false, 'message' => $msg]);
}
?>