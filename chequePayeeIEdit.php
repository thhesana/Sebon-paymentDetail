<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'];
include 'db.php';

// Check if the PayeeID is provided for editing
if (isset($_GET['PayeeID'])) {
    $payeeID = $_GET['PayeeID'];
    
    // Fetch payee data
    $sql = "SELECT cp.PayeeID, cp.PayeeName, cp.PayeeEmail, pg.gendername AS PayeeGender,
            pc.payeeCategoryName AS PayeeCategory, ps.payeStatusName AS PayeeStatus, 
            cp.payeegenderTBLid, cp.payeeCategoryid, cp.payeStatusTBLid, cp.CreatedAt
            FROM [PaymentDetail].dbo.ChequePayeeName cp
            LEFT JOIN [PaymentDetail].dbo.payeegenderTBL pg 
                ON cp.payeegenderTBLid = pg.payeegenderTBLid
            LEFT JOIN [PaymentDetail].dbo.payeeCategory pc 
                ON cp.payeeCategoryid = pc.payeeCategoryid
            LEFT JOIN [PaymentDetail].dbo.payeStatusTBL ps 
                ON cp.payeStatusTBLid = ps.payeStatusTBLid
            WHERE PayeeID = ?";
    
    $stmt = sqlsrv_prepare($conn, $sql, array($payeeID));
    sqlsrv_execute($stmt);
    $payeeData = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if ($payeeData === false) {
        echo "Payee not found.";
        exit();
    }
} else {
    echo "Invalid PayeeID.";
    exit();
}

// Handle form submission for updating the payee details
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payeeName = $_POST['PayeeName'];
    $payeeEmail = $_POST['PayeeEmail'];
    $payeegenderTBLid = $_POST['payeegenderTBLid'];
    $payeeCategoryid = $_POST['payeeCategoryid'];
    $payeStatusTBLid = $_POST['payeStatusTBLid'];
    $updatedAt = date('Y-m-d H:i:s');
    
    // Update payee details in the database
    $sql = "UPDATE [PaymentDetail].dbo.ChequePayeeName
            SET PayeeName = ?, PayeeEmail = ?, payeegenderTBLid = ?, payeeCategoryid = ?, payeStatusTBLid = ?, UpdatedAt = ?
            WHERE PayeeID = ?";
    
    $stmt = sqlsrv_prepare($conn, $sql, array($payeeName, $payeeEmail, $payeegenderTBLid, $payeeCategoryid, $payeStatusTBLid, $updatedAt, $payeeID));
    
    if (sqlsrv_execute($stmt)) {
        echo "<script>alert('Payee details updated successfully!'); window.location.href='chequePayeeList.php';</script>";
    } else {
        echo "Error: " . print_r(sqlsrv_errors(), true);
    }
}

// Fetch dropdown options for Gender, Category, and Status
$genderQuery = "SELECT payeegenderTBLid, gendername FROM [PaymentDetail].dbo.payeegenderTBL";
$genderResult = sqlsrv_query($conn, $genderQuery);
if (!$genderResult) {
    die("Error fetching gender data: " . print_r(sqlsrv_errors(), true));
}

$categoryQuery = "SELECT payeeCategoryid, payeeCategoryName FROM [PaymentDetail].dbo.payeeCategory";
$categoryResult = sqlsrv_query($conn, $categoryQuery);
if (!$categoryResult) {
    die("Error fetching category data: " . print_r(sqlsrv_errors(), true));
}

$statusQuery = "SELECT payeStatusTBLid, payeStatusName FROM [PaymentDetail].dbo.payeStatusTBL";
$statusResult = sqlsrv_query($conn, $statusQuery);
if (!$statusResult) {
    die("Error fetching status data: " . print_r(sqlsrv_errors(), true));
}
?>

<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Cheque Payee Details</title>
    <!-- Include Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEJ09XxL5M1woG2hZgFfR0ndHqa7Z2hM9c56+4V6G3FfDdlHqvN2t3SO7vDXA" crossorigin="anonymous">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4 text-center">Update Cheque Payee Details</h2>
        <form method="POST" action="" class="form-group">
            <div class="mb-3">
                <label for="PayeeName" class="form-label">Payee Name:</label>
                <input type="text" class="form-control" name="PayeeName" value="<?php echo htmlspecialchars($payeeData['PayeeName']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="PayeeEmail" class="form-label">Payee Email:</label>
                <input type="email" class="form-control" name="PayeeEmail" value="<?php echo htmlspecialchars($payeeData['PayeeEmail']); ?>" >
            </div>
            
            <!-- Display Gender Dropdown -->
            <div class="mb-3">
                <label for="payeegenderTBLid" class="form-label">Gender:</label>
                <select name="payeegenderTBLid" class="form-select" required>
                    <option value="">Select Gender</option>
                    <?php
                    while ($gender = sqlsrv_fetch_array($genderResult, SQLSRV_FETCH_ASSOC)) {
                        $selected = ($gender['payeegenderTBLid'] == $payeeData['payeegenderTBLid']) ? 'selected' : '';
                        echo "<option value='" . $gender['payeegenderTBLid'] . "' $selected>" . $gender['gendername'] . "</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="payeeCategoryid" class="form-label">Category:</label>
                <select name="payeeCategoryid" class="form-select" required>
                    <option value="">Select Category</option>
                    <?php
                    while ($category = sqlsrv_fetch_array($categoryResult, SQLSRV_FETCH_ASSOC)) {
                        $selected = ($category['payeeCategoryid'] == $payeeData['payeeCategoryid']) ? 'selected' : '';
                        echo "<option value='" . $category['payeeCategoryid'] . "' $selected>" . $category['payeeCategoryName'] . "</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="payeStatusTBLid" class="form-label">Status:</label>
                <select name="payeStatusTBLid" class="form-select" required>
                    <option value="">Select Status</option>
                    <?php
                    while ($status = sqlsrv_fetch_array($statusResult, SQLSRV_FETCH_ASSOC)) {
                        $selected = ($status['payeStatusTBLid'] == $payeeData['payeStatusTBLid']) ? 'selected' : '';
                        echo "<option value='" . $status['payeStatusTBLid'] . "' $selected>" . $status['payeStatusName'] . "</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="text-center">
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>

    <!-- Include Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz4fnFO9gybI7tDYeZmK9Un7p4YvoT2F4Rk6E0p4Mz3fDtn27UzV3v5V9M" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js" integrity="sha384-pzjw8f+ua7Kw1TIq0o6g3RkfjP8wYItw0A60vRyy+dz0g6E1PR7VHzbNAsy7k0e" crossorigin="anonymous"></script>
</body>
</html>
