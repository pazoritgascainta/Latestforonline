<?php
session_start(); // Start the session at the top of the file
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
            echo "Password reset successfully."; // Success message
        } else {
            echo "Error updating password: " . $conn->error; // Error message
        }
        $stmt->close();
    } else {
        echo "Invalid OTP. Please try again."; // Invalid OTP message
    }
    
    // Close the database connection
    $conn->close();
}
?>
