<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}
include 'db.php';

// Fetch all banks for dropdown
$bank_sql = "SELECT payeeBankdetail_id, payeeBankdetailName FROM [PaymentDetail].[dbo].[payeeBankdetail] ORDER BY payeeBankdetailName";
$bank_stmt = sqlsrv_query($conn, $bank_sql);
$banks = [];
while ($row = sqlsrv_fetch_array($bank_stmt, SQLSRV_FETCH_ASSOC)) {
    $banks[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payeeDetailName = trim($_POST['payeeDetailName']);
    $payeeDetailNameAccountNum = trim($_POST['payeeDetailNameAccountNum']);
    $payeeBankdetail_id = (int)$_POST['payeeBankdetail_id'];

    if (!empty($payeeDetailName) && !empty($payeeDetailNameAccountNum) && $payeeBankdetail_id > 0) {
        $sql = "INSERT INTO [PaymentDetail].[dbo].[payeeDetail] (payeeDetailName, payeeDetailNameAccountNum, payeeBankdetail_id)
                VALUES (?, ?, ?)";
        $params = [$payeeDetailName, $payeeDetailNameAccountNum, $payeeBankdetail_id];

        $stmt = sqlsrv_prepare($conn, $sql, $params);
        if ($stmt && sqlsrv_execute($stmt)) {
            header("Location: PayeeList.php?success=1");
            exit();
        } else {
            $error = "Insert Failed: " . print_r(sqlsrv_errors(), true);
        }
    } else {
        $error = "All fields are required.";
    }
}
?>

<?php include 'header.php'; ?>
<div class="container mt-4">
    <h2 class="mb-4">Add New Payee Detail</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Payee Name</label>
            <input type="text" name="payeeDetailName" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Account Number</label>
            <input type="text" name="payeeDetailNameAccountNum" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Bank Name</label>
            <select name="payeeBankdetail_id" class="form-select select2" required>
                <option value="">-- Select Bank --</option>
                <?php foreach ($banks as $bank): ?>
                    <option value="<?php echo $bank['payeeBankdetail_id']; ?>">
                        <?php echo htmlspecialchars($bank['payeeBankdetailName']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-success">Add Payee</button>
        <a href="payeeDetailList.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<!-- Include jQuery and Select2 CSS/JS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('.select2').select2({
        placeholder: "-- Select Bank --",
        allowClear: true,
        width: '100%'
    });
});
</script>
