<?php
session_name('admin_session'); // Set a unique session name for admins
session_start();

// Include database connection
$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if this is an AJAX request from the user side
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complaint_id'])) {
    // Get the complaint ID from the AJAX request
    $complaintId = intval($_POST['complaint_id']);

    // Update the complaint status to 'Resolved'
    $update_sql = "UPDATE complaints SET status = 'Resolved' WHERE complaint_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    if ($update_stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Error preparing statement']);
        exit;
    }
    $update_stmt->bind_param("i", $complaintId);

    // Execute the update query
    if ($update_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Complaint marked as resolved.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating complaint status']);
    }
    $update_stmt->close();
    $conn->close();
    exit;
}

// Check if 'id' parameter is present in URL for the admin side
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admincomplaint.php?error=missing_id");
    exit;
}

// Get 'id' parameter from URL and sanitize it
$id = intval($_GET['id']);

// Fetch complaint details using prepared statements for the admin side
$sql = "SELECT * FROM complaints WHERE complaint_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $complaint = $result->fetch_assoc();
} else {
    header("Location: admincomplaint.php?error=complaint_not_found");
    exit;
}
$result->close();

// Handle form submission to update complaint status on the admin side
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['status'])) {
    $status = $_POST['status'];

    // Update complaint status using prepared statements
    $update_sql = "UPDATE complaints SET status = ? WHERE complaint_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    if ($update_stmt === false) {
        die("Error preparing update statement: " . $conn->error);
    }
    $update_stmt->bind_param("si", $status, $id);

    if ($update_stmt->execute()) {
        $_SESSION['update_success'] = "Complaint status updated successfully.";
        header("Location: admincomplaint.php");
        exit;
    } else {
        $_SESSION['update_error'] = "Error updating record: " . $update_stmt->error;
        header("Location: admincomplaint.php?error=update_failed");
        exit;
    }
} else {
    $_SESSION['update_error'] = "Error: Status not provided.";
    header("Location: admincomplaint.php?error=status_not_provided");
    exit;
}

$conn->close();
?>
