<?php
// Delete image from the database and the server

$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['delete_id'])) {
    $deleteId = $_GET['delete_id'];

    // Fetch file path for the image to be deleted
    $getFilePathQuery = "SELECT file_path FROM announcement_images WHERE id = ?";
    $stmt = $conn->prepare($getFilePathQuery);
    $stmt->bind_param("i", $deleteId);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($filePath);
    $stmt->fetch();
    
    // If file path exists, delete the file from the server
    if ($filePath && file_exists($filePath)) {
        unlink($filePath);  // Delete the file from the server
    }
    
    // Delete the image from the database
    $deleteQuery = "DELETE FROM announcement_images WHERE id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $deleteId);
    $stmt->execute();
    
    // Close the connection and redirect back to the main page
    $stmt->close();
    $conn->close();
    
    header("Location: dashadmin.php");  // Redirect to your page after deletion
    exit;
} else {
    // If no delete_id is set, redirect to the main page
    header("Location: dashadmin.php");
    exit;
}
?>
