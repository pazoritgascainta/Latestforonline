<?php
session_start();
require __DIR__ . '/vendor/autoload.php'; // Ensure you have composer autoload

// Database connection variables
$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $otp = $_POST['otp'];
    $new_password = $_POST['new_password'];

    // Check if the entered OTP matches the one in the session
    if (isset($_SESSION['otp']) && $otp == $_SESSION['otp']) {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $homeowner_id = $_SESSION['homeowner_id'];

        // Update the password in the database
        $sql = "UPDATE homeowners SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $hashed_password, $homeowner_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Password reset successfully."; // Store success message
        } else {
            $_SESSION['message'] = "Error updating password."; // Store error message
        }
        $stmt->close();
    } else {
        $_SESSION['message'] = "Invalid OTP. Please try again."; // Store invalid OTP message
    }
    
    // Redirect back to index.php
    header("Location: index.php");
    exit(); // Always call exit after header redirect
}
$conn->close();
?>
