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
    

    // Collect form data
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    // Query to fetch admin from database
    $query = "SELECT * FROM admin WHERE username='$username'";
    $result = $conn->query($query);

    if ($result->num_rows == 1) {
        // Admin found, verify password
        $admin = $result->fetch_assoc();
        if (password_verify($password, $admin['password'])) {
            // Password is correct, set session variables
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;

            // Redirect to admin dashboard or desired page
            header("Location: dashadmin.php");
            exit;
        } else {
            // Password incorrect, redirect back to login with error
            header("Location: admin_login.php?error=invalid_credentials");
            exit;
        }
    } else {
        // Admin not found, redirect back to login with error
        header("Location: admin_login.php?error=invalid_credentials");
        exit;
    }

    // Close database connection
    $conn->close();
}
?>
