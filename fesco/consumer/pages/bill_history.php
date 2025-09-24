<?php
include '../../config/db.php';
session_start();
$consumer_id = $_SESSION['consumer_id'];

$sql = "SELECT * FROM bills WHERE consumer_id = $consumer_id ORDER BY bill_date DESC";
$result = $conn->query($sql);

echo "<h2>Bill History</h2>";
if ($result->num_rows > 0) {
    echo "<table border='1' cellpadding='8'>
            <tr><th>Bill ID</th><th>Amount</th><th>Date</th><th>Status</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id']}</td>
                <td>Rs {$row['amount']}</td>
                <td>{$row['bill_date']}</td>
                <td>{$row['status']}</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p>No bills found.</p>";
}
?>
