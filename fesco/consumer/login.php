<?php
include '../db.php'; // Correct path

session_start();
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM consumers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['consumer_id'] = $user['id'];
            $_SESSION['consumer_name'] = $user['name'];
            header("Location: dashboard.php"); // consumer dashboard page
            exit();
        } else {
            $message = "Invalid password!";
        }
    } else {
        $message = "No user found with this email!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Consumer Login</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 30px; }
        .container { width: 400px; margin: auto; background: #fff; padding: 20px; border-radius: 10px; }
        input { width: 100%; padding: 10px; margin: 10px 0; }
        button { width: 100%; padding: 10px; background: #007bff; color: #fff; border: none; cursor: pointer; }
        button:hover { background: #0056b3; }
        .message { color: red; text-align: center; }
    </style>
</head>
<body>
<div class="container">
    <h2>Consumer Login</h2>
    <p class="message"><?= $message ?></p>
    <form method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
    <p style="text-align:center;">Don't have an account? <a href="register.php">Register</a></p>
</div>
</body>
</html>
