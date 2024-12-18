<?php
session_name('user_session'); 
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    $servername = "localhost";
    $username = "u780935822_homeowner";
    $password = "Boot@o29";
    $dbname = "u780935822_homeowner";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    

    $sql = "SELECT id, name, password, status FROM homeowners WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $name, $hashed_password, $status);

    if ($stmt->num_rows > 0) {
        $stmt->fetch();
        if ($status === 'archived') {
            $error = "Your account has been archived and cannot be accessed.";
        } elseif (password_verify($password, $hashed_password)) {
            $_SESSION['homeowner_id'] = $id;
            $_SESSION['homeowner_name'] = $name;
            header("Location: dashuser.php");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
    }

    $stmt->close();
    $conn->close();
}

// Display logout message if redirected from logout
$logout_message = isset($_GET['message']) && $_GET['message'] == 'loggedout' ? "You have been logged out successfully." : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
<div class="logout-container">
        <h2>For your security, you have been automatically logged out due to extended inactivity.</h2>
        <a href="index.php" class="back-button">Go Back to Home</a>
    </div>
</body>
</html>
