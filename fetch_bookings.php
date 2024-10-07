<?php
header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$date = $_GET['date'] ?? '';

if (empty($date)) {
    echo json_encode(['success' => false, 'message' => 'No date provided.']);
    exit();
}

$sql = "SELECT id, name, email, purpose FROM appointments WHERE date = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare statement failed.']);
    exit();
}

$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

echo json_encode(['success' => true, 'bookings' => $bookings]);

$stmt->close();
$conn->close();
?>
