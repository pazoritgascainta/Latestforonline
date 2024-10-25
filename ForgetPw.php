<?php

session_start();
$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

$conn = new mysqli($servername, $username, $password, $dbname);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    // Check if email exists
    $sql = "SELECT id FROM homeowners WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Get homeowner ID
        $stmt->bind_result($homeowner_id);
        $stmt->fetch();

        // Generate a reset token
        $reset_token = bin2hex(random_bytes(16)); // Generates a random token
        
        // Insert the reset token into the password_reset_requests table
        $sqlInsert = "INSERT INTO password_reset_requests (homeowner_id, reset_token) VALUES (?, ?)";
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->bind_param("is", $homeowner_id, $reset_token);
        if ($stmtInsert->execute()) {
            // Email the reset link (for example)
      
            // Here, you'd typically use a mail function to send the email
            // mail($email, "Reset your password", "Click this link to reset your password: " . $reset_link);
            
            $_SESSION['reset_email'] = $email; // Store email for the reset password page
            header("Location: index.php"); // Redirect to reset password page
            exit();
        } else {
            $error = "Could not create reset request.";
        }
    } else {
        $error = "No account found with that email.";
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="forgetpw.css">
    <title>St. Monique</title>
</head>
<body>
    <div class="container" id="container">
        <div class="form-container forgetpw">
            <form method="POST" action="">
                <h2>Forgot Password</h2>
                <?php if (!empty($error)) { echo "<p style='color: red;'>$error</p>"; } ?>
                <input type="email" name="email" placeholder="Enter your email" required>
                <button type="submit">Reset Password</button>
            </form>
        </div>
        <div class="toggle-container">
            <div class="toggle">
                <div class="toggle-panel toggle-right">
                    <h1>Find your St. Monique account</h1>
                    <div class="back-btn-container">
                        <button id="exitBtn" class="exit-btn" onclick="window.location.href='index.php';">Back</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="forgetpw.js"></script>
</body>
</html>
