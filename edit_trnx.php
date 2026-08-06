<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

include 'db.php';
include 'header.php';

// Get Group ID
$groupId = isset($_GET['groupId']) ? (int)$_GET['groupId'] : 0;
if ($groupId <= 0) die("Invalid Payment Group ID");

// Handle deletion of existing transaction
if (isset($_GET['deleteId'])) {
    $deleteId = (int)$_GET['deleteId'];
    $sqlDelTrnx = "DELETE FROM [PaymentDetail].[dbo].[PaytransactionDetail] WHERE PaytransactionDetail_id = ?";
    $stmtDel = sqlsrv_query($conn, $sqlDelTrnx, [$deleteId]);
    if ($stmtDel === false) {
        echo "<div class='alert alert-danger'>Error deleting transaction: " . print_r(sqlsrv_errors(), true) . "</div>";
    } else {
        echo "<div class='alert alert-success'>Transaction deleted successfully.</div>";
    }
}

// Handle form submission for updates
if (isset($_POST['update'])) {
    $success = true;
    $errors = [];

    // Update existing transactions
    if (!empty($_POST['payee_id'])) {
        foreach ($_POST['payee_id'] as $index => $payeeId) {
            $payeeId = intval($payeeId);
            $amount = floatval($_POST['amount'][$index]);
            $remarks = trim($_POST['remarks'][$index]);
            $trnxId = intval($_POST['trnx_id'][$index]);

            $sqlUpdateTrnx = "UPDATE [PaymentDetail].[dbo].[PaytransactionDetail]
                              SET amount = ?, remarks = ?, payeeDetail_id = ?
                              WHERE PaytransactionDetail_id = ?";
            $paramsTrnx = [$amount, $remarks, $payeeId, $trnxId];
            $stmt = sqlsrv_query($conn, $sqlUpdateTrnx, $paramsTrnx);
            
            if ($stmt === false) {
                $success = false;
                $errors[] = "Error updating transaction ID $trnxId";
            }
        }
    }

    // Handle new transactions
    if (!empty($_POST['new_payee_id'])) {
        foreach ($_POST['new_payee_id'] as $index => $payeeId) {
            $newAmount = floatval($_POST['new_amount'][$index]);
            $newRemarks = trim($_POST['new_remarks'][$index]);

            if ($payeeId && $newAmount > 0) {
                // Check if it's a new payee (starts with 'new_')
                if (strpos($payeeId, 'new_') === 0) {
                    $newPayeeName = substr($payeeId, 4); // Remove 'new_' prefix
                    
                    // Insert new payee
                    $sqlInsertPayee = "INSERT INTO [PaymentDetail].[dbo].[payeeDetail] (payeeDetailName)
                                       OUTPUT INSERTED.payeeDetail_id VALUES (?)";
                    $stmtInsert = sqlsrv_query($conn, $sqlInsertPayee, [$newPayeeName]);
                    
                    if ($stmtInsert === false) {
                        $success = false;
                        $errors[] = "Error inserting payee: $newPayeeName";
                        continue;
                    }

                    $insertedPayee = sqlsrv_fetch_array($stmtInsert, SQLSRV_FETCH_ASSOC);
                    $finalPayeeId = $insertedPayee['payeeDetail_id'];
                } else {
                    $finalPayeeId = intval($payeeId);
                }

                // Insert transaction
                $sqlInsertTrnx = "INSERT INTO [PaymentDetail].[dbo].[PaytransactionDetail] 
                                  (PaymentDetailGroupId, payeeDetail_id, amount, remarks)
                                  VALUES (?, ?, ?, ?)";
                $paramsTrnx = [$groupId, $finalPayeeId, $newAmount, $newRemarks];
                $stmt = sqlsrv_query($conn, $sqlInsertTrnx, $paramsTrnx);
                
                if ($stmt === false) {
                    $success = false;
                    $errors[] = "Error inserting transaction";
                }
            }
        }
    }

    if ($success) {
        echo "<div class='alert alert-success'>Transactions updated successfully.</div>";
        // Redirect to refresh the page and show updated data
        header("Location: " . $_SERVER['PHP_SELF'] . "?groupId=" . $groupId);
        exit();
    } else {
        echo "<div class='alert alert-danger'>Error updating transactions:<br>" . implode('<br>', $errors) . "</div>";
    }
}

// Fetch all payees for dropdown
$payeesSql = "SELECT payeeDetail_id, payeeDetailName FROM [PaymentDetail].[dbo].[payeeDetail] ORDER BY payeeDetailName";
$payeeStmt = sqlsrv_query($conn, $payeesSql);
$allPayees = [];
if ($payeeStmt !== false) {
    while ($p = sqlsrv_fetch_array($payeeStmt, SQLSRV_FETCH_ASSOC)) {
        $allPayees[$p['payeeDetail_id']] = $p['payeeDetailName'];
    }
}

// Fetch existing transactions
$sql = "
SELECT t.PaytransactionDetail_id, t.payeeDetail_id, p.payeeDetailName, t.amount, t.remarks
FROM [PaymentDetail].[dbo].[PaytransactionDetail] t
JOIN [PaymentDetail].[dbo].[payeeDetail] p
ON t.payeeDetail_id = p.payeeDetail_id
WHERE t.PaymentDetailGroupId = ?
ORDER BY t.PaytransactionDetail_id
";
$params = [$groupId];
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) die("SQL Error: " . print_r(sqlsrv_errors(), true));
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Transactions</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet" />
<style>
.select2-container--default .select2-selection--single {
    height: 38px;
    padding: 6px 12px;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 24px;
    padding-left: 0;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
}
.select2-dropdown {
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    max-height: 300px;
    overflow-y: auto;
}
.select2-results__options {
    max-height: 200px;
    overflow-y: auto;
}
.select2-results__option--highlighted[data-selected] {
    background-color: #0d6efd;
    color: white;
}
.new-payee-option {
    background-color: #d1edff !important;
    border-left: 4px solid #0d6efd;
    font-weight: bold;
    color: #0d6efd !important;
}
.new-payee-option:hover {
    background-color: #b3d9ff !important;
}
.select2-search--dropdown .select2-search__field {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 100% !important;
}
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
</head>
<body class="container mt-4">
<h2 class="mb-4">Edit Transactions - Group ID: <?= $groupId ?></h2>

<form method="post">
    <table class="table table-bordered table-striped" id="trnxTable">
        <thead class="table-dark">
            <tr>
                <th width="40%">Payee Name</th>
                <th width="20%">Amount</th>
                <th width="25%">Remarks</th>
                <th width="15%">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): 
                $amount = number_format($row['amount'], 2, '.', '');
                $remarks = htmlspecialchars($row['remarks']);
            ?>
            <tr>
                <td>
                    <select name="payee_id[]" class="form-select payee-select" required>
                        <?php foreach ($allPayees as $id => $name): ?>
                            <option value="<?= $id ?>" <?= $id == $row['payeeDetail_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="trnx_id[]" value="<?= $row['PaytransactionDetail_id'] ?>">
                </td>
                <td>
                    <input type="number" name="amount[]" class="form-control" value="<?= $amount ?>" step="0.01" min="0" required>
                </td>
                <td>
                    <input type="text" name="remarks[]" class="form-control" value="<?= $remarks ?>">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $row['PaytransactionDetail_id'] ?>)">Delete</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <button type="button" class="btn btn-success mb-3" onclick="addTransaction()">Add Transaction</button>
    <br>
    <button type="submit" name="update" class="btn btn-primary">Update Transactions</button>
    <a href="report.php" class="btn btn-secondary">Back</a>
</form>

<script>
let payeeOptions = <?php echo json_encode($allPayees); ?>;

function initializeSelect2(selector = '.payee-select') {
    $(selector).select2({
        placeholder: 'Search or type payee name...',
        allowClear: true,
        tags: true,
        width: '100%',
        minimumInputLength: 0,
        dropdownParent: $('body'),
        dropdownAutoWidth: false,
        escapeMarkup: function (markup) {
            return markup;
        },
        createTag: function (params) {
            var term = $.trim(params.term);
            if (term === '' || term.length < 2) {
                return null;
            }
            
            // Check if the term already exists (case insensitive)
            var exists = false;
            for (let id in payeeOptions) {
                if (payeeOptions[id].toLowerCase() === term.toLowerCase()) {
                    exists = true;
                    break;
                }
            }
            
            if (exists) {
                return null;
            }
            
            return {
                id: 'new_' + term,
                text: term,
                newTag: true,
                isNew: true
            };
        },
        templateResult: function (data) {
            if (data.loading) {
                return 'Searching...';
            }
            
            if (data.newTag || data.isNew) {
                return '<div class="new-payee-option">➕ Add "' + data.text + '" as new payee</div>';
            }
            
            return data.text;
        },
        templateSelection: function (data) {
            if (data.newTag || data.isNew) {
                return data.text + ' (New Payee)';
            }
            return data.text;
        }
    });
}

function addTransaction() {
    let table = document.getElementById('trnxTable').getElementsByTagName('tbody')[0];
    let options = '<option value="">Select or type payee name...</option>';
    
    // Sort payees alphabetically for better UX
    let sortedPayees = Object.entries(payeeOptions).sort((a, b) => a[1].localeCompare(b[1]));
    
    for (let [id, name] of sortedPayees) {
        options += `<option value="${id}">${name}</option>`;
    }

    let row = table.insertRow();
    row.innerHTML = `
        <td>
            <select name="new_payee_id[]" class="form-select payee-select-new" required>
                ${options}
            </select>
        </td>
        <td><input type="number" name="new_amount[]" class="form-control" placeholder="0.00" step="0.01" min="0.01" required></td>
        <td><input type="text" name="new_remarks[]" class="form-control" placeholder="Optional remarks"></td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="removeRow(this)">Remove</button></td>
    `;
    
    // Initialize Select2 for the new row with enhanced configuration
    initializeSelect2($(row).find('.payee-select-new'));
    
    // Focus on the new select
    setTimeout(function() {
        $(row).find('.payee-select-new').select2('open');
    }, 100);
}

function removeRow(btn) {
    // Destroy Select2 before removing the row
    $(btn).closest('tr').find('select').select2('destroy');
    btn.closest('tr').remove();
}

function confirmDelete(id) {
    if(confirm("Are you sure you want to delete this transaction?")) {
        window.location.href = '?groupId=<?= $groupId ?>&deleteId=' + id;
    }
}

// Initialize Select2 for existing selects
$(document).ready(function() {
    initializeSelect2();
    
    // Improve mobile experience
    if (window.innerWidth < 768) {
        $('.select2-dropdown').css('max-height', '200px');
    }
    
    // Handle window resize
    $(window).resize(function() {
        $('.select2-container').css('width', '100%');
    });
});

// Prevent form submission on Enter in select2 search
$(document).on('keydown', '.select2-search__field', function(e) {
    if (e.keyCode === 13) {
        e.preventDefault();
        e.stopPropagation();
    }
});
</script>
</body>
</html>