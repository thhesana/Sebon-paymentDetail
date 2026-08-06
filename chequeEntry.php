<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

include 'db.php';

// Fetch dropdown options
$payees = [];
$sql = "SELECT payeeDetail_id, payeeDetailName FROM [PaymentDetail].[dbo].[payeeDetail] ORDER BY payeeDetailName";
$stmt = sqlsrv_query($conn, $sql);
if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $payees[] = $row;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payeeDetail_id'])) {
    $payee_ids = $_POST['payeeDetail_id'];
    $amounts = $_POST['amount'];
    $remarks = $_POST['remarks'];

    $inserted_count = 0;

    // Step 1: Get next PaymentDetailGroupId
    $group_sql = "SELECT ISNULL(MAX(PaymentDetailGroupId),0)+1 AS NextGroupId FROM [PaymentDetail].[dbo].[PaytransactionDetail]";
    $group_stmt = sqlsrv_query($conn, $group_sql);
    $group_row = sqlsrv_fetch_array($group_stmt, SQLSRV_FETCH_ASSOC);
    $nextGroupId = $group_row['NextGroupId'];

    // Step 2: Insert all rows with the same group ID
    for ($i = 0; $i < count($payee_ids); $i++) {
        if (!empty($payee_ids[$i]) && !empty($amounts[$i])) {
            $sp_sql = "INSERT INTO [PaymentDetail].[dbo].[PaytransactionDetail] 
                        (PaymentDetailGroupId, payeeDetail_id, amount, remarks)
                       VALUES (?, ?, ?, ?)";
            $params = [
                $nextGroupId,
                (int)$payee_ids[$i],
                (float)$amounts[$i],
                trim($remarks[$i])
            ];

            $sp_stmt = sqlsrv_prepare($conn, $sp_sql, $params);
            if ($sp_stmt && sqlsrv_execute($sp_stmt)) {
                $inserted_count++;
            }
        }
    }

    if ($inserted_count > 0) {
        // Redirect to report.php with PaymentDetailGroupId
        header("Location: report.php?groupId=" . $nextGroupId);
        exit();
    } else {
        $error = "No transactions inserted.";
    }
}
?>

<?php include 'header.php'; ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<div class="container mt-4">
    <h2 class="mb-4">Transaction Detail</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <table class="table table-bordered" id="transactionTable">
            <thead class="table-dark">
                <tr>
                    <th>Payee Name</th>
                    <th>Amount</th>
                    <th>Remarks</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <select name="payeeDetail_id[]" class="form-control select2" required>
                            <option value="">Select Payee</option>
                            <?php foreach ($payees as $p): ?>
                                <option value="<?php echo $p['payeeDetail_id']; ?>">
                                    <?php echo htmlspecialchars($p['payeeDetailName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="number" step="0.01" name="amount[]" class="form-control" required></td>
                    <td><input type="text" name="remarks[]" class="form-control" required></td>
                    <td><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">Delete</button></td>
                </tr>
            </tbody>
        </table>

        <button type="button" class="btn btn-secondary mb-3" onclick="addRow()">+ Add Row</button>
        <br>
        <button type="submit" class="btn btn-success">Save Transactions</button>
        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2').select2({ width: '100%' });
});

function addRow() {
    let table = document.getElementById("transactionTable").getElementsByTagName("tbody")[0];
    let newRow = table.rows[0].cloneNode(true);

    // Clear values in cloned row
    newRow.querySelectorAll("input").forEach(input => input.value = "");
    
    let select = newRow.querySelector("select");
    
    // Remove Select2 completely from cloned element
    if ($(select).hasClass('select2-hidden-accessible')) {
        $(select).select2('destroy');
    }
    
    // Remove all Select2 related attributes and classes
    $(select).removeClass('select2-hidden-accessible');
    select.removeAttribute('data-select2-id');
    select.removeAttribute('aria-hidden');
    select.removeAttribute('tabindex');
    
    // Remove any Select2 container that might have been cloned
    let select2Container = newRow.querySelector('.select2-container');
    if (select2Container) {
        select2Container.remove();
    }
    
    // Reset select to default state
    select.selectedIndex = 0;
    select.value = "";
    
    table.appendChild(newRow);

    // Initialize Select2 for the new select element with a slight delay
    setTimeout(function() {
        $(select).select2({ 
            width: '100%',
            placeholder: 'Select Payee'
        });
    }, 10);
}

function removeRow(btn) {
    let table = document.getElementById("transactionTable").getElementsByTagName("tbody")[0];
    if (table.rows.length > 1) {
        let row = btn.closest("tr");
        let select = row.querySelector('select');
        
        // Destroy Select2 instance before removing the row
        if ($(select).hasClass('select2-hidden-accessible')) {
            $(select).select2('destroy');
        }
        
        row.remove();
    } else {
        alert("At least one row is required.");
    }
}
</script>