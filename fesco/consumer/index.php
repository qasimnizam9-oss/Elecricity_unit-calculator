<?php
include("../db.php"); // correct path
session_start();

$message = "";

// Function to calculate bill based on units
function calculateBill($units) {
    $rate = 15; // Example rate per unit
    return $units * $rate;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $consumer_no = trim($_POST['consumer_no']);
    $units = (int)$_POST['units'];

    if (!empty($consumer_no) && $units > 0) {
        $stmt = $conn->prepare("SELECT * FROM consumers WHERE consumer_number = ?");
        $stmt->bind_param("s", $consumer_no);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $amount = calculateBill($units);

            // Save bill in database without status
            $consumer_id = $user['id'];
            $bill_date = date("Y-m-d");

            $insert = $conn->prepare("INSERT INTO bills (consumer_id, units, amount, bill_date) VALUES (?, ?, ?, ?)");
            $insert->bind_param("iiis", $consumer_id, $units, $amount, $bill_date);
            $insert->execute();

            $message = "Bill Calculated: Rs. " . $amount;
        } else {
            $message = "Consumer not found! Please register first.";
        }
    } else {
        $message = "Please enter valid Consumer Number and Units.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>FESCO Bill Calculator</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <h2 class="text-center mb-4">Electricity Bill Calculator</h2>
        <div class="card p-4 shadow">
            <form method="POST" action="">
                <div class="mb-3">
                    <label>Consumer Number</label>
                    <input type="text" name="consumer_no" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Units Consumed</label>
                    <input type="number" name="units" class="form-control" required>
                </div>
                <button class="btn btn-primary w-100">Calculate Bill</button>
            </form>
            <?php if ($message != ""): ?>
                <div class="alert alert-info mt-3"><?php echo $message; ?></div>
            <?php endif; ?>
        </div>
        <div class="text-center mt-3">
            <a href="register.php" class="btn btn-success">Register</a>
            <a href="login.php" class="btn btn-secondary">Login</a>
        </div>
    </div>
</body>
</html>
