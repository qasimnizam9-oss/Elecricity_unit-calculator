<?php
include '../db.php'; // correct path from consumer/ to db.php

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $consumer_number = trim($_POST['consumer_number']);

    // Check if email or consumer number exists
    $stmt = $conn->prepare("SELECT * FROM consumers WHERE email = ? OR consumer_number = ?");
    $stmt->bind_param("ss", $email, $consumer_number);
    $stmt->execute();
    $check_result = $stmt->get_result();

    if ($check_result->num_rows > 0) {
        $message = "Email or Consumer Number already exists!";
    } else {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Insert into database
        $insert_stmt = $conn->prepare("INSERT INTO consumers (name, email, password, consumer_number) VALUES (?, ?, ?, ?)");
        $insert_stmt->bind_param("ssss", $name, $email, $hashed_password, $consumer_number);

        if ($insert_stmt->execute()) {
            $message = "Registration successful! <a href='login.php'>Login Here</a>";
        } else {
            $message = "Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Consumer Registration</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 30px; }
        .container { width: 400px; margin: auto; background: #fff; padding: 20px; border-radius: 10px; }
        input { width: 100%; padding: 10px; margin: 10px 0; }
        button { width: 100%; padding: 10px; background: #28a745; color: #fff; border: none; cursor: pointer; }
        button:hover { background: #218838; }
        .message { color: red; text-align: center; }
    </style>
</head>
<body>
<div class="container">
    <h2>Consumer Registration</h2>
    <p class="message"><?= $message ?></p>
    <form method="POST" action="">
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="consumer_number" placeholder="Consumer Number" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Register</button>
    </form>
    <p style="text-align:center;">Already have an account? <a href="login.php">Login</a></p>
</div>
</body>
</html>
