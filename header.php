<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

// Handle logout
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment  Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f9f9f9;
            font-family: 'Arial', sans-serif;
        }

        .header {
            background: #007bff;
            color: white;
            padding: 30px 15px;
            text-align: center;
        }

        .header h2 {
            margin-bottom: 20px;
        }

        .user-info {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .logout-btn {
            background: red;
            border: none;
            padding: 8px 15px;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .logout-btn:hover {
            background: darkred;
        }

        .nav-tabs .nav-link {
            color: white;
        }

        .nav-tabs .nav-link:hover,
        .nav-tabs .nav-link.active {
            background: #0056b3;
        }
    </style>
</head>
<body>

<div class="header">
    <h2>Payment  Management System</h2>
    <div class="user-info">
        <p class="mb-0">User: <strong><?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
        <form method="POST" style="display:inline;">
            <button type="submit" name="logout" class="logout-btn">Logout</button>
        </form>
    </div>
</div>

<ul class="nav nav-tabs bg-primary justify-content-center">
    <li class="nav-item"><a href="dashboard.php" class="nav-link text-white">DASHBOARD</a></li>
    <li class="nav-item"><a href="chequeEntry.php" class="nav-link text-white">PAYMENT ENTRY MODULE</a></li>
    
    <li class="nav-item"><a href="PayeeList.php" class="nav-link text-white">PAYEE LIST</a></li>
    <li class="nav-item"><a href="report.php" class="nav-link text-white">REPORT</a></li>
</ul>


</body>
</html>
