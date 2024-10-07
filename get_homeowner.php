<?php
$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$id = intval($_GET['id']);

// Fetch homeowner name and sqm by ID
$sql = "SELECT name, sqm FROM homeowners WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$homeowner = $result->fetch_assoc();

$stmt->close(); // Close the statement
$conn->close(); // Close the connection

header('Content-Type: application/json');
echo json_encode($homeowner);
?>
