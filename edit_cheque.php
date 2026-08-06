<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'];
include 'db.php';

$ChequeNumber = isset($_GET['ChequeNumber']) ? $_GET['ChequeNumber'] : '';

if (empty($ChequeNumber)) {
    echo "ChequeNumber not provided.";
    exit();
}

$fetch_sql = "
    SELECT TOP 1 
        [ChequeID], [ChequeNumber], [ChequeDate], [ChequePayeeNameId], [Amount], 
        [CreatedAt], [UpdatedAt], [Status], [Username], [Notification], [Detail]  
    FROM [PaymentDetail].[dbo].[Cheques]
    WHERE [ChequeNumber] = ?";
$fetch_result = sqlsrv_query($conn, $fetch_sql, array($ChequeNumber));

if ($fetch_result === false) {
    die(print_r(sqlsrv_errors(), true));
}

$cheque = sqlsrv_fetch_array($fetch_result, SQLSRV_FETCH_ASSOC);

if (!$cheque) {
    echo "Cheque not found.";
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $NewChequeNumber = $_POST['ChequeNumber'];
$ChequeDate = $_POST['ChequeDate'];
$ChequePayeeNameId = $_POST['ChequePayeeNameId'];
$Amount = $_POST['Amount'];
$Status = isset($_POST['Status']) ? $_POST['Status'] : $cheque['Status'];
$Detail = $_POST['Detail'];

$ChequeDateFormatted = date_create($ChequeDate); // convert string to DateTime

$update_sql = "
    UPDATE [PaymentDetail].[dbo].[Cheques] 
    SET ChequeNumber = ?, ChequeDate = ?, ChequePayeeNameId = ?, Amount = ?, Status = ?, Username = ?, Detail = ?  
    WHERE ChequeNumber = ?";
$params = array($NewChequeNumber, $ChequeDateFormatted, $ChequePayeeNameId, $Amount, $Status, $username, $Detail, $ChequeNumber);

    $stmt = sqlsrv_query($conn, $update_sql, $params);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    header("Location: printCheque.php?status=success");
    exit();
}

// Populate form values from DB
$ChequeDate = $cheque['ChequeDate']->format('Y-m-d'); // '2025-04-20'


$ChequePayeeNameId = $cheque['ChequePayeeNameId'];
$Amount = $cheque['Amount'];
$Status = $cheque['Status'];
$Detail = $cheque['Detail'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Cheque</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        body {
            background-color: #f1f1f1;
            font-family: 'Arial', sans-serif;
        }
        .container {
            max-width: 700px;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #343a40;
            font-weight: bold;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            font-weight: bold;
            color: #495057;
        }
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 16px;
            margin-top: 8px;
        }
        button {
            width: 100%;
            padding: 14px;
            background: #007bff;
            border: none;
            border-radius: 6px;
            color: white;
            font-size: 16px;
            font-weight: bold;
        }
        button:hover {
            background-color: #0056b3;
        }
        .select2-container .select2-selection--single {
            height: 45px;
            line-height: 45px;
            padding-left: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Update Cheque Detail</h2>

    <form action="edit_cheque.php?ChequeNumber=<?php echo htmlspecialchars($ChequeNumber); ?>" method="POST">

        <!-- Cheque Date -->
        <div class="form-group">
            <label for="ChequeDate">Cheque Date</label>
            <input type="date" name="ChequeDate" class="form-control" required value="<?php echo $ChequeDate; ?>">
        </div>

        <!-- Payee Dropdown -->
        <div class="form-group">
            <label for="ChequePayeeNameId">Payee Name</label>
            <select name="ChequePayeeNameId" id="ChequePayeeNameId" class="form-control select2" required>
                <option value="">Select Payee</option>
                <?php
                $payee_sql = "SELECT PayeeID, PayeeName FROM [PaymentDetail].[dbo].[ChequePayeeName] WHERE payeStatusTBLid = 1";
                $payee_result = sqlsrv_query($conn, $payee_sql);
                while ($row = sqlsrv_fetch_array($payee_result, SQLSRV_FETCH_ASSOC)) {
                    $selected = ($row['PayeeID'] == $ChequePayeeNameId) ? 'selected' : '';
                    echo "<option value='{$row['PayeeID']}' $selected>{$row['PayeeName']}</option>";
                }
                ?>
            </select>
        </div>

        <!-- Amount -->
        <div class="form-group">
            <label for="Amount">Amount</label>
            <input type="number" step="0.01" name="Amount" class="form-control" required value="<?php echo $Amount; ?>" oninput="limitDecimals(this)">
        </div>

					<!-- Cheque Number (editable) -->
			<div class="form-group">
				<label for="ChequeNumber">Cheque Number</label>
				<input type="text" class="form-control" name="ChequeNumber" required value="<?php echo $ChequeNumber; ?>">
			</div>


        <!-- Detail -->
        <div class="form-group">
            <label for="Detail">Detail</label>
            <textarea name="Detail" id="Detail" rows="4" class="form-control" required oninput="checkWordLimit()"><?php echo $Detail; ?></textarea>
            <small id="wordCountMessage"></small>
        </div>

        <button type="submit">Update Cheque Detail</button>
    </form>
</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2();
        checkWordLimit();
    });

    function limitDecimals(input) {
        let value = input.value;
        if (value.indexOf('.') !== -1) {
            let parts = value.split('.');
            if (parts[1].length > 2) {
                parts[1] = parts[1].slice(0, 2);
                input.value = parts.join('.');
            }
        }
    }

    function checkWordLimit() {
        let detailInput = document.getElementById('Detail');
        let words = detailInput.value.trim().split(/\s+/);
        let wordLimit = 50;
        if (words.length > wordLimit) {
            detailInput.value = words.slice(0, wordLimit).join(' ');
        }
        document.getElementById('wordCountMessage').textContent = `Word limit: ${words.length} / 50`;
    }
</script>

</body>
</html>
