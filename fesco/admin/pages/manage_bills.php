<?php
include '../../db.php'; // fixed path to database connection

// Fetch bills from database
$sql = "SELECT * FROM bills";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Bills</title>
    <style>
        table { border-collapse: collapse; width: 80%; margin: 20px auto; }
        th, td { border: 1px solid #333; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; }
        a { text-decoration: none; color: blue; }
    </style>
</head>
<body>
    <h2 style="text-align:center;">Manage Bills</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Customer Name</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>{$row['id']}</td>
                        <td>{$row['customer_name']}</td>
                        <td>{$row['amount']}</td>
                        <td>{$row['status']}</td>
                        <td>
                            <a href='edit_bill.php?id={$row['id']}'>Edit</a> |
                            <a href='delete_bill.php?id={$row['id']}'>Delete</a>
                        </td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='5'>No bills found</td></tr>";
        }
        ?>
    </table>
</body>
</html>
