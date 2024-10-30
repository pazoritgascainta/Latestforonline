<?php
$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$suggestions = [];

if (strlen($search_query) >= 1) {
    // Adjust the SQL query to fetch names and emails from the homeowners table
    $sql = "SELECT name, email FROM homeowners WHERE name LIKE ? OR email LIKE ?";
    $stmt = $conn->prepare($sql);
    $search_term = '%' . $search_query . '%';
    $stmt->bind_param('ss', $search_term, $search_term);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $suggestions[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($suggestions);
$conn->close();
?>
