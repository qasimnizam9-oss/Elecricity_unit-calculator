<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FESCO</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="dashboard-container">
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <a href="#" onclick="loadPage('pages/manage_users.php')">Manage Users</a>
        <a href="#" onclick="loadPage('pages/manage_bills.php')">Manage Bills</a>
        <a href="admin_logout.php">Logout</a>
    </div>
    <div class="content-area" id="content-area">
        <h2>Welcome Admin</h2>
        <p>Select an option from the menu.</p>
    </div>
</div>
<script src="../assets/js/script.js"></script>
</body>
</html>
