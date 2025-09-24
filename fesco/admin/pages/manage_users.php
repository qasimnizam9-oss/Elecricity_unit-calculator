<?php
include '../../db.php'; // fixed path to database connection

// Fetch users from database
$sql = "SELECT * FROM consumers";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <style>
        table { border-collapse: collapse; width: 70%; margin: 20px auto; }
        th, td { border: 1px solid #333; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; }
        a { text-decoration: none; color: blue; }
    </style>
</head>
<body>
    <h2 style="text-align:center;">Manage Users</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Actions</th>
        </tr>
        <?php
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>{$row['id']}</td>
                        <td>{$row['username']}</td>
                        <td>{$row['email']}</td>
                        <td>{$row['role']}</td>
                        <td>
                            <a href='edit_user.php?id={$row['id']}'>Edit</a> |
                            <a href='delete_user.php?id={$row['id']}'>Delete</a>
                        </td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='5'>No users found</td></tr>";
        }
        ?>
    </table>
</body>
</html>
