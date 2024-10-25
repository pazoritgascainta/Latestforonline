<?php
$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$query = isset($_GET['query']) ? $_GET['query'] : '';

$sql = "SELECT id, name, sqm FROM homeowners WHERE name LIKE ?";
$stmt = $conn->prepare($sql);
$likeQuery = '%' . $query . '%';
$stmt->bind_param("s", $likeQuery);
$stmt->execute();
$result = $stmt->get_result();

$homeowners = [];
while ($row = $result->fetch_assoc()) {
    $homeowners[] = $row;
}

echo json_encode($homeowners);

$stmt->close();
$conn->close();
?>
