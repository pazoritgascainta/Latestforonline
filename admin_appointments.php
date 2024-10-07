<?php
// admin_appointments.php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php?error=not_logged_in");
    exit;
}

$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$sql = "SELECT * FROM appointments";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['date']}</td>
                <td>{$row['time']}</td>
                <td>{$row['name']}</td>
                <td>{$row['email']}</td>
                <td>{$row['purpose']}</td>
                <td>{$row['status']}</td>
                <td>
                    <form method='POST' action='approve_appointment.php'>
                        <input type='hidden' name='id' value='{$row['id']}'>
                        <button type='submit'>Approve</button>
                    </form>
                </td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='8'>No appointments found</td></tr>";
}

$conn->close();
?>
