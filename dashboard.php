<?php
session_start(); 
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

$username = $_SESSION['username']; // Get logged-in username
include 'header.php';
?>

<div class="container mt-5 text-center">
    <div class="card shadow-sm p-5">
        <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
      
    </div>
</div>

<style>
    .card {
        border-radius: 15px;
        background-color: #f8f9fa;
    }
</style>
