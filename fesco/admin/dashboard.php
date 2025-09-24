<?php
session_start();
include '../db.php'; // Adjust path if db.php is in root

// Redirect if no login
if (!isset($_SESSION['admin']) && !isset($_SESSION['consumer_id'])) {
    header("Location: ../admin/login.php");
    exit();
}

// Determine user type
$isAdmin = isset($_SESSION['admin']);
$isConsumer = isset($_SESSION['consumer_id']);
$consumer_name = $isConsumer ? $_SESSION['consumer_name'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FESCO Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; }
.sidebar { height: 100vh; background: #0d6efd; color: white; padding-top: 20px; position: fixed; width: 220px; }
.sidebar a { display: flex; align-items: center; color: white; padding: 12px 20px; text-decoration: none; border-radius: 6px; margin: 5px 10px; }
.sidebar a:hover { background: #0b5ed7; }
.sidebar a i { margin-right: 10px; }
.content { margin-left: 240px; padding: 30px; }
.section { display: none; }
.active-section { display: block; }
h3 { margin-bottom: 20px; }
.card { margin-bottom: 20px; }
.navbar { margin-left: 240px; background: #fff; }
</style>
<script>
function showSection(id){
    let sections = document.querySelectorAll('.section');
    sections.forEach(s => s.style.display = 'none');
    document.getElementById(id).style.display = 'block';
}
</script>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h4 class="text-center mb-4">FESCO</h4>

    <?php if($isAdmin): ?>
        <a href="#" onclick="showSection('manage_users')"><i class="bi bi-people"></i> Manage Users</a>
        <a href="#" onclick="showSection('manage_bills')"><i class="bi bi-file-earmark-text"></i> Manage Bills</a>
        <a href="#" onclick="showSection('admin_reports')"><i class="bi bi-bar-chart"></i> Reports</a>
    <?php endif; ?>

    <?php if($isConsumer): ?>
        <a href="#" onclick="showSection('calculate_bill')"><i class="bi bi-calculator"></i> Calculate Bill</a>
        <a href="#" onclick="showSection('view_bills')"><i class="bi bi-wallet2"></i> View Bills</a>
    <?php endif; ?>

    <a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>

<!-- Navbar -->
<nav class="navbar navbar-light shadow-sm">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1">
            Welcome <?php echo $isAdmin ? 'Admin' : htmlspecialchars($consumer_name); ?>
        </span>
    </div>
</nav>

<!-- Main Content -->
<div class="content">
    <!-- Admin Sections -->
    <?php if($isAdmin): ?>
        <div id="manage_users" class="section active-section">
            <h3>Manage Users</h3>
            <div class="row">
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Add User</h5>
                            <p class="card-text">Form to add new users.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Edit Users</h5>
                            <p class="card-text">Update or remove existing users.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="manage_bills" class="section">
            <h3>Manage Bills</h3>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">View Bills</h5>
                    <p class="card-text">View and update consumer bills.</p>
                </div>
            </div>
        </div>

        <div id="admin_reports" class="section">
            <h3>Reports</h3>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Reports</h5>
                    <p class="card-text">Summary of users and bills.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Consumer Sections -->
    <?php if($isConsumer): ?>
        <div id="calculate_bill" class="section active-section">
            <h3>Calculate Bill</h3>
            <div class="card shadow-sm p-3">
                <p>Form to calculate electricity bill for consumer.</p>
            </div>
        </div>

        <div id="view_bills" class="section">
            <h3>View Bills</h3>
            <div class="card shadow-sm p-3">
                <p>All your bills with date, units, and amount.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
