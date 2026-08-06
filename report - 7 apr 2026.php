<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}
include 'db.php';
include 'header.php';
// SQL: Group transactions by PaymentDetailGroupId
$sql = "
WITH PayeeConcat AS (
    SELECT
        t.PaymentDetailGroupId,
        p.payeeDetailName,
        t.amount,
        ROW_NUMBER() OVER (PARTITION BY t.PaymentDetailGroupId ORDER BY t.PaytransactionDetail_id) AS rn
    FROM [PaymentDetail].[dbo].[PaytransactionDetail] t
    JOIN [PaymentDetail].[dbo].[payeeDetail] p
        ON t.payeeDetail_id = p.payeeDetail_id
)
SELECT
    PaymentDetailGroupId,
    STRING_AGG(CASE WHEN rn <= 2 THEN payeeDetailName END, ', ') 
        WITHIN GROUP (ORDER BY rn) AS PayeeNames,
    SUM(amount) AS TotalAmount
FROM PayeeConcat
GROUP BY PaymentDetailGroupId
ORDER BY PaymentDetailGroupId DESC;
";
// Execute query
$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    die("SQL Error: " . print_r(sqlsrv_errors(), true));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Grouped Pay Transaction Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table th { font-weight: bold; }
        .table td { font-weight: normal; }
        @media print {
            body * { visibility: hidden; }
            #printGroup, #printGroup * { visibility: visible; }
            #printGroup { position: absolute; top: 0; left: 0; width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="container mt-4">
    <h2 class="text-center mb-4">PRINT SECTION</h2>
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Payment Grp ID</th>
                <th>Payee Names (First 2)</th>
                <th>Total Amount</th>
                <th class="no-print">Action</th>
            </tr>
        </thead>
        <tbody>
    <?php
    $found = false;
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $found = true;
        $formattedAmount = number_format($row['TotalAmount'], 2, '.', ',');
        echo "<tr>
            <td>{$row['PaymentDetailGroupId']}</td>
            <td>{$row['PayeeNames']}</td>
            <td>{$formattedAmount}</td>
            <td class='no-print'>
                <a href='edit_trnx.php?groupId={$row['PaymentDetailGroupId']}' class='btn btn-primary btn-sm me-1'>Edit</a>
                <button class='btn btn-secondary btn-sm me-1' onclick='printGroup({$row['PaymentDetailGroupId']})'>Print</button>
                <a href='download_excel.php?groupId={$row['PaymentDetailGroupId']}' class='btn btn-success btn-sm me-1'>Excel</a>
                <button class='btn btn-info btn-sm' onclick='sendMail({$row['PaymentDetailGroupId']})'>Send Mail to Laxmi Bank</button>
            </td>
        </tr>";
    }
    if (!$found) {
        echo "<tr><td colspan='4' class='text-center'>No records found</td></tr>";
    }
    ?>
</tbody>
    </table>
    <script>
    function printGroup(groupId) {
        let printWindow = window.open('', '', 'height=600,width=800');
        printWindow.document.write('<html><head><title>Print</title>');
        printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">');
        printWindow.document.write('<style>.table th{font-weight:bold;} .table td{font-weight:normal;}</style>');
        printWindow.document.write('</head><body>');
        fetch('print_group.php?groupId=' + groupId)
            .then(response => response.text())
            .then(html => {
                printWindow.document.write(html);
                printWindow.document.write('</body></html>');
                printWindow.document.close();
                printWindow.print();
            });
    }
    
    function sendMail(groupId) {
        if (!confirm('Send email report for Payment Group ID: ' + groupId + '?')) {
            return;
        }
        
        // Show loading indicator
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = 'Sending...';
        btn.disabled = true;
        
        fetch('send_mail.php?groupId=' + groupId)
            .then(response => response.json())
            .then(data => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                if (data.success) {
                    alert('Email sent successfully!');
                } else {
                    alert('Error sending email: ' + data.message);
                }
            })
            .catch(error => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                alert('Error: ' + error);
            });
    }
    </script>
</body>
</html>