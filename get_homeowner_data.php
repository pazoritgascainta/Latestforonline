<?php
header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// Check if homeowner_id is provided
if (isset($_GET['id'])) {
    $homeowner_id = intval($_GET['id']);
    $sql = "SELECT name, sqm FROM homeowners WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $homeowner_id);
    $stmt->execute();
    $stmt->bind_result($name, $sqm);
    $stmt->fetch();
    $stmt->close();

    if ($name) {
        echo json_encode(['name' => $name, 'sqm' => $sqm]);
    } else {
        echo json_encode(['error' => 'No homeowner found with that ID']);
    }
} else {
    echo json_encode(['error' => 'Homeowner ID not provided']);
}

$conn->close();
?>
