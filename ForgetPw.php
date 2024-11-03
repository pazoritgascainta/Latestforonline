<?php
session_start(); // Start the session at the top of the file
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="forgetpw.css"> <!-- Your custom CSS file -->
    <title>St. Monique - Forgot Password</title>
</head>
<body>
    <div class="container" id="container">
        <div class="form-container forgetpw">
            <form id="emailForm" method="POST" action="request_otp.php"> <!-- Form action points to request_otp.php -->
                <h2>Forgot Password</h2>
                <input type="email" name="email" placeholder="Enter your email" required>
                <button type="submit">Request OTP</button>
            </form>
        </div>

        <div class="modal" id="otpModal" style="display:none;"> <!-- Modal for OTP entry -->
            <div class="modal-content">
                <h2>Enter OTP</h2>
                <form id="otpForm" method="POST" action="verify_otp.php"> <!-- Form for OTP verification -->
                    <input type="text" name="otp" placeholder="Enter OTP" required>
                    <input type="password" name="new_password" placeholder="Enter New Password" required>
                    <button type="submit">Verify and Reset Password</button>
                </form>
            </div>
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

    <script src="forgetpw.js"></script> <!-- Custom JavaScript file -->
    <script>
        // Handle the form submission for requesting the OTP
        document.getElementById('emailForm').onsubmit = function(event) {
            event.preventDefault(); // Prevent the default form submission
            
            const formData = new FormData(this); // Create FormData object from the form
            fetch('request_otp.php', { // Send the request to request_otp.php
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show the OTP modal if the OTP was sent successfully
                    document.getElementById('otpModal').style.display = 'block';
                } else {
                    // Show an alert if there was an error sending the OTP
                    alert(data.message || 'Error sending OTP.');
                }
            })
            .catch(error => console.error('Error:', error)); // Log any fetch errors
        };
    </script>
</body>
</html>
