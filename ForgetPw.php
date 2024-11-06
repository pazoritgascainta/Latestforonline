<?php
session_start(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="forgetpw.css">
    <title>St. Monique - Forgot Password</title>
</head>
<body>
    <div class="container" id="container">
        <div class="form-container forgetpw">
            <form id="emailForm" method="POST" action="request_otp.php"> 
                <h2>Forgot Password</h2>
                <input type="email" name="email" placeholder="Enter your email" required>
                <button type="submit">Request OTP</button>
            </form>
        </div>

        <!-- OTP Modal -->
        <div class="modal" id="otpModal" style="display:none;">
            <div class="modal-content">
                <a href="#" id="modalExitLink" class="exit-link" onclick="hideOtpModal()">X</a> 
                <h2>Enter OTP</h2>
                <div id="otpMessage" class="message" style="display: none;"></div> 
                <form id="otpForm" method="POST" action="verify_otp.php">
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

    <script src="forgetpw.js"></script> 
    <script>
      
        document.getElementById('emailForm').onsubmit = function(event) {
            event.preventDefault(); 
            
            const formData = new FormData(this); 
            fetch('request_otp.php', { 
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                   
                    document.getElementById('otpModal').style.display = 'block';
                } else {
                 
                    alert(data.message || 'Error sending OTP.');
                }
            })
            .catch(error => console.error('Error:', error)); 
        };

        document.getElementById('otpForm').onsubmit = function(event) {
    event.preventDefault();

    const newPassword = document.querySelector('input[name="new_password"]').value;
    const passwordCriteria = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

    // Check if the password meets the criteria
    if (!passwordCriteria.test(newPassword)) {
        alert("Password must be at least 8 characters long, include an uppercase letter, a lowercase letter, a number, and a special character.");
        return;
    }

    const formData = new FormData(this); 
    fetch('verify_otp.php', { 
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        const otpMessageDiv = document.getElementById('otpMessage');
        otpMessageDiv.innerHTML = data; 
        otpMessageDiv.style.display = 'block'; 

        if (data.includes("Password reset successfully.")) {
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 2000); 
        }
    })
    .catch(error => console.error('Error:', error)); 
};

document.getElementById('otpForm').onsubmit = function(event) {
    event.preventDefault();

    const formData = new FormData(this); 
    fetch('verify_otp.php', { 
        method: 'POST',
        body: formData
    })
    .then(response => response.text()) 
    .then(data => {
     
        const otpMessageDiv = document.getElementById('otpMessage');
        otpMessageDiv.innerHTML = data; 
        otpMessageDiv.style.display = 'block'; 
        
 
        if (data.includes("Password reset successfully.")) {
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 2000); 
        }
    })
    .catch(error => console.error('Error:', error)); 
};


    
        function hideOtpModal() {
            document.getElementById('otpModal').style.display = 'none';
        }
    </script>

    <style>
    
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
