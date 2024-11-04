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

        <!-- OTP Modal -->
        <div class="modal" id="otpModal" style="display:none;">
            <div class="modal-content">
                <a href="#" id="modalExitLink" class="exit-link" onclick="hideOtpModal()">X</a> <!-- Exit link -->
                <h2>Enter OTP</h2>
                <div id="otpMessage" class="message" style="display: none;"></div> <!-- Message display area -->
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

      // Handle the OTP form submission for verifying the OTP
document.getElementById('otpForm').onsubmit = function(event) {
    event.preventDefault(); // Prevent the default form submission

    const formData = new FormData(this); // Create FormData object from the OTP form
    fetch('verify_otp.php', { // Send the request to verify_otp.php
        method: 'POST',
        body: formData
    })
    .then(response => response.text()) // Change to text response
    .then(data => {
        // Display the response as a message inside the modal
        const otpMessageDiv = document.getElementById('otpMessage');
        otpMessageDiv.innerHTML = data; // Set the message
        otpMessageDiv.style.display = 'block'; // Make the message visible
        
        // If the response indicates success, redirect to index.php
        if (data.includes("Password reset successfully.")) {
            setTimeout(() => {
                window.location.href = 'index.php'; // Redirect after a delay
            }, 2000); // Delay to allow message to be read
        }
    })
    .catch(error => console.error('Error:', error)); // Log any fetch errors
};


        // Function to hide the OTP modal
        function hideOtpModal() {
            document.getElementById('otpModal').style.display = 'none';
        }
    </script>

    <style>
        /* Style for the modal exit link */
        .exit-link {
            position: relative;
            top: 0px;
            right: -340px;
            color: #f44336;
            text-decoration: none;
            font-weight: bold;
            font-size: 18px;
            padding: 5px;
            background-color: transparent;
            border: none;
            cursor: pointer;
        }

        .exit-link:hover {
            color: #d32f2f;
        }

        /* Style for the message display in the modal */
        .message {
            margin: 15px 0;
            padding: 10px;
            background-color: #f1f1f1;
            border: 1px solid #ccc;
            border-radius: 5px;
            color: #333;
        }
    </style>
</body>
</html>
