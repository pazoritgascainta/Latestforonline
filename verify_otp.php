<?php
session_start(); 
require __DIR__ . '/vendor/autoload.php'; 


$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

$conn = new mysqli($servername, $username, $password, $dbname);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $otp = $_POST['otp'];
    $new_password = $_POST['new_password'];


    if (isset($_SESSION['otp']) && $otp == $_SESSION['otp']) {
   
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $homeowner_id = $_SESSION['homeowner_id'];

  
        $sql = "UPDATE homeowners SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $hashed_password, $homeowner_id);
        if ($stmt->execute()) {
            echo "Password reset successfully."; 
        } else {
            echo "Error updating password: " . $conn->error; 
        }
        $stmt->close();
    } else {
        echo "Invalid OTP. Please try again."; 
    }
    

    $conn->close();
}
?>
