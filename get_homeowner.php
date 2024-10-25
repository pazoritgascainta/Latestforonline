<?php
session_name('admin_session');
session_start();

$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['id'])) {
    $homeowner_id = intval($_GET['id']);
    $sql = "SELECT name, sqm FROM homeowners WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $homeowner_id);
    $stmt->execute();
    $stmt->bind_result($name, $sqm);
    $stmt->fetch();
    $stmt->close();

    // Return JSON response
    echo json_encode(['name' => $name, 'sqm' => $sqm]);
} else {
    echo json_encode(['name' => null, 'sqm' => null]);
}

$conn->close();
?>
