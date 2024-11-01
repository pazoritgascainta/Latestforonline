<?php
session_name('user_session');
session_start();

if (!isset($_SESSION['homeowner_id'])) {
    header("Location: login.php");
    exit;
}

// Database connection
$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if file_path is provided in POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['file_path'])) {
    $file_path = $_POST['file_path'];
    $homeowner_id = $_SESSION['homeowner_id'];

    // Check if the image exists in the database for the logged-in user
    $sql_check = "SELECT file_path FROM payments WHERE file_path = ? AND homeowner_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("si", $file_path, $homeowner_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {
        // Delete image from file system if it exists
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        // Remove record from database
        $sql = "DELETE FROM payments WHERE file_path = ? AND homeowner_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $file_path, $homeowner_id);
        $stmt->execute();

        // Redirect back to uploaded_payment.php after successful deletion
        header("Location: uploaded_payment.php?status=deleted");
        exit;
    } else {
        header("Location: uploaded_payment.php?status=not_found");
        exit;
    }
} else {
    header("Location: uploaded_payment.php?status=invalid_request");
    exit;
}
?>
