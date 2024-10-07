<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Include database connection
    $servername = "localhost";
    $username = "u780935822_homeowner";
    $password = "Boot@o29";
    $dbname = "u780935822_homeowner";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    

    // Escape user inputs for security
    $username = $conn->real_escape_string($_POST['username']);
    $password = $conn->real_escape_string($_POST['password']);

    // Query to fetch admin from database
    $query = "SELECT * FROM admin WHERE username='$username' AND password='$password'";
    $result = $conn->query($query);

    if ($result->num_rows == 1) {
        // Admin found, set session variables
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        
        // Redirect to admin dashboard or desired page
        header("Location: admin_dashboard.php");
        exit;
    } else {
        // Admin not found, redirect back to login with error
        header("Location: admin_login.php?error=invalid_credentials");
        exit;
    }

    // Close database connection
    $conn->close();
}
?>
