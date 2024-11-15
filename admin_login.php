<?php


session_name('admin_session'); 
session_start();


$error_message = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Database connection
    $servername = "localhost";
    $username = "u780935822_homeowner";
    $password = "Boot@o29";
    $dbname = "u780935822_homeowner";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
  
    $admin_username = $_POST['username'];
    $admin_password = $_POST['password'];

    
    $sql = "SELECT id, username, password FROM admin WHERE username = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die("Failed to prepare SQL statement: " . $conn->error);
    }

    $stmt->bind_param("s", $admin_username); 
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();

        if (password_verify($admin_password, $admin['password'])) {
          
            $_SESSION['admin_id'] = $admin['id'];

          
            if (isset($_GET['redirect'])) {
                
                $redirect_url = urldecode($_GET['redirect']);
                header("Location: " . $redirect_url);
            } else {
                
                header("Location: dashadmin.php");
            }
            exit();
        } else {
           
            $error_message = "Invalid password.";
        }
    } else {
      
        $error_message = "Invalid username.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link rel="stylesheet" href="admin_login.css">
    
    <script>
        function updateDateTime() {
            const now = new Date();
            const dateTimeString = now.toLocaleString();
            document.getElementById('date-time').textContent = dateTimeString;
        }

        window.onload = function() {
            updateDateTime();
            setInterval(updateDateTime, 1000); 
        }
    </script>

</head>
<body>
    <div class="date-time-container">
        <p id="date-time"></p>
    </div>

    <div class="login-container">
        <h2>Admin Login</h2>
        <h3>Welcome to St.Monique Management System</h3>
        <?php if (!empty($error_message)) { ?>
            <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
        <?php } ?>
        <form action="admin_login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" method="POST">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
            <br>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <br>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
