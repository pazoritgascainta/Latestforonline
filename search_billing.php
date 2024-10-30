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

// Get parameters from the AJAX request
$homeowner_id = isset($_GET['homeowner_id']) ? intval($_GET['homeowner_id']) : 0;
$search_date = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch billing history records
$sql_billing = "SELECT history_id, monthly_due, billing_date, due_date, total_amount, paid_date
                FROM billing_history
                WHERE homeowner_id = ?" . (!empty($search_date) ? " AND billing_date = ?" : "") . "
                ORDER BY billing_date DESC";

$stmt_billing = $conn->prepare($sql_billing);
if (!empty($search_date)) {
    $stmt_billing->bind_param("is", $homeowner_id, $search_date);
} else {
    $stmt_billing->bind_param("i", $homeowner_id);
}
$stmt_billing->execute();
$result_billing = $stmt_billing->get_result();

$billing_records = [];
while ($row = $result_billing->fetch_assoc()) {
    $billing_records[] = $row;
}

echo json_encode($billing_records);
$conn->close();
?>
