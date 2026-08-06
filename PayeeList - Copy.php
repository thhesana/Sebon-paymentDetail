<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}
include 'db.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payeeDetailName = trim($_POST['payeeDetailName']);
    $payeeDetailNameAccountNum = trim($_POST['payeeDetailNameAccountNum']);

    if (!empty($payeeDetailName) && !empty($payeeDetailNameAccountNum)) {
        $sql = "INSERT INTO [PaymentDetail].[dbo].[payeeDetail] (payeeDetailName, payeeDetailNameAccountNum)
                VALUES (?, ?)";
        $params = [$payeeDetailName, $payeeDetailNameAccountNum];

        $stmt = sqlsrv_prepare($conn, $sql, $params);
        if ($stmt && sqlsrv_execute($stmt)) {
            header("Location: payeeDetailList.php?success=1");
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
        <button type="submit" class="btn btn-success">Add Payee</button>
        <a href="payeeDetailList.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
