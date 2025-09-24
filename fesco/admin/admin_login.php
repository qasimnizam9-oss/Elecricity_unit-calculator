<?php
session_start();
include '../db.php'; // Make sure this points to the correct db.php file

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Use prepared statements to prevent SQL Injection
    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows == 1) {
        $row = $result->fetch_assoc();
        // Set session variables
        $_SESSION['admin_id'] = $row['id'];
        $_SESSION['admin_username'] = $row['username'];


        // Redirect to dashboard
        header("Location: admin_dashboard.php"); // Adjust path: dashboard.php is in admin folder
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; }
        .container { max-width: 400px; margin: 100px auto; background: #fff; padding: 20px; border-radius: 10px; }
        h2 { text-align: center; }
        input[type=text], input[type=password] { width: 100%; padding: 10px; margin: 10px 0; }
        input[type=submit] { width: 100%; padding: 10px; background: #007bff; color: white; border: none; cursor: pointer; }
        input[type=submit]:hover { background: #0056b3; }
        p { color: red; text-align: center; }
    </style>
</head>
<body>
<div class="container">
    <h2>Admin Login</h2>
    <?php if(isset($error)) echo "<p>$error</p>"; ?>
    <form method="POST">
        <input type="text" name="username" placeholder="Enter Username" required>
        <input type="password" name="password" placeholder="Enter Password" required>
        <input type="submit" name="login" value="Login">
    </form>
</div>
</body>
</html>
