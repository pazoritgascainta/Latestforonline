<?php
session_name('admin_session');
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login if not logged in
    header("Location: login.php");
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

// Check if 'id' parameter is present in URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Redirect back to service requests list or handle error
    header("Location: adminserreq.php?error=missing_id");
    exit;
}

// Get 'id' parameter from URL and sanitize it
$service_req_id = intval($_GET['id']);

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate status input
    $status = isset($_POST['status']) ? $_POST['status'] : '';

    if (!in_array($status, ['Pending', 'In Progress', 'Resolved'])) {
        // Invalid status, redirect with error
        header("Location: adminserreq.php?id=$service_req_id&error=invalid_status");
        exit;
    }

    // Prepare the SQL query to update the service request status
    $sql = "UPDATE serreq SET status = ? WHERE service_req_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $service_req_id);
    
    if ($stmt->execute()) {
        // Update successful, redirect back to service requests list
        header("Location: serviceadmin.php?success=status_updated");
    } else {
        // Update failed, redirect with error
        header("Location: serviceadmin.php?id=$service_req_id&error=update_failed");
    }

    // Close statement
    $stmt->close();
}

// Close database connection
$conn->close();
?>
