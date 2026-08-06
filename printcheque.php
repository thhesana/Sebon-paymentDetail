<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

include 'db.php';

// Fetch cheque data
$sql = "SELECT ChequeNumber, ChequeDate, ChequePayeeNameId, Amount, Status, Detail FROM [PaymentDetail].[dbo].[Cheques] WHERE STATUS='PENDING' ORDER BY [CreatedAt] DESC";
$result = sqlsrv_query($conn, $sql);
?>

<?php include 'header.php'; ?>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container mt-4">
    <h2 class="text-center mb-4">PENDING CHEQUE LIST</h2>

    <div class="table-responsive">
        <table class="table table-striped table-hover text-center align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Cheque Number</th>
                    <th>Cheque Date</th>
                    <th>Payee Name</th>
                    <th>Details</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result) {
                    while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
                        // Fetch Payee Name
                        $payee_id = $row['ChequePayeeNameId'];
                        $payee_name_sql = "SELECT PayeeName FROM [PaymentDetail].[dbo].[ChequePayeeName] WHERE PayeeID = ?";
                        $payee_stmt = sqlsrv_query($conn, $payee_name_sql, array($payee_id));
                        $payee_row = sqlsrv_fetch_array($payee_stmt, SQLSRV_FETCH_ASSOC);
                        $payee_name = $payee_row ? $payee_row['PayeeName'] : 'Unknown';

                        // Format cheque date
                        $cheque_date = $row['ChequeDate'] ? $row['ChequeDate']->format('d-M-Y') : 'N/A';

                        // Status badge
                        $status_class = ($row['Status'] == 'Cleared') ? 'success' : (($row['Status'] == 'Pending') ? 'warning' : 'danger');

                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['ChequeNumber']) . "</td>";
                        echo "<td>" . htmlspecialchars($cheque_date) . "</td>";
                        echo "<td>" . htmlspecialchars($payee_name) . "</td>";
                        echo "<td>" . htmlspecialchars($row['Detail']) . "</td>";
                        echo "<td>Rs. " . number_format(htmlspecialchars($row['Amount']), 2) . "</td>";
                        echo "<td><span class='badge bg-$status_class'>" . htmlspecialchars($row['Status']) . "</span></td>";
                        echo "<td>
                                <a href='edit_cheque.php?ChequeNumber=" . urlencode($row['ChequeNumber']) . "' class='btn btn-sm btn-primary'>Edit</a>
                                <a href='chequeFinalPrint.php?chequeNumber=" . urlencode($row['ChequeNumber']) . "' target='_blank' class='btn btn-sm btn-success'>Print</a>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' class='text-danger'>No cheques found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Bootstrap JS (Optional for components) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
