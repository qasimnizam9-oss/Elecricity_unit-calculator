<?php
include '../../config/db.php';
session_start();
$consumer_id = $_SESSION['consumer_id'];

$sql = "SELECT * FROM consumer WHERE id = $consumer_id";
$result = $conn->query($sql);
$user = $result->fetch_assoc();

echo "<h2>Your Profile</h2>";
echo "<p><strong>Name:</strong> {$user['name']}</p>";
echo "<p><strong>Email:</strong> {$user['email']}</p>";
echo "<p><strong>Address:</strong> {$user['address']}</p>";
?>
