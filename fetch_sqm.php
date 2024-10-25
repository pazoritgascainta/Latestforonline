<?php
$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['homeowner_id'])) {
    $homeowner_id = intval($_GET['homeowner_id']);
    $sql = "SELECT sqm FROM homeowners WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $homeowner_id);
    $stmt->execute();
    $stmt->bind_result($sqm);
    $stmt->fetch();
    $stmt->close();

    echo json_encode(['sqm' => $sqm]);
}

$conn->close();
?>
