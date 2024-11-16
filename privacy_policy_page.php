<?php
session_name('user_session'); 
session_start();


if (!isset($_SESSION['homeowner_id'])) {
    header("Location: login.php");
    exit;
}

$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$homeowner_id = $_SESSION['homeowner_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy</title>
    <link rel="stylesheet" href="privacy_policy.css">
</head>
<body>

    <!-- Privacy Policy Content -->
    <div class="privacy-policy-container">
        <div class="privacy-policy-header">
            <h1>Privacy Policy</h1>
        </div>
        <div class="privacy-policy-content">
            <p>Your privacy is very important to us. This Privacy Policy outlines the types of personal information we collect and how it is used, shared, and protected.</p>
            
            <h2>1. Information We Collect</h2>
            <p>We collect personal information that you provide directly to us, such as your name, email address, phone number, and payment details. We may also collect information automatically through the use of cookies or similar technologies.</p>
            
            <h2>2. How We Use Your Information</h2>
            <p>We use the information we collect to provide and improve our services, process payments, respond to inquiries, and send important updates regarding our services. We may also use your information for marketing purposes if you opt-in.</p>
            
            <h2>3. How We Share Your Information</h2>
            <p>We do not sell your personal information. We may share your information with trusted third parties that provide services on our behalf, such as payment processors, but they are required to maintain the confidentiality of your information.</p>
            
            <h2>4. Data Protection</h2>
            <p>We implement reasonable security measures to protect your personal data from unauthorized access, alteration, or destruction. However, no method of transmission over the internet is 100% secure.</p>
            
            <h2>5. The Data Privacy Act of 2012 (DPA)</h2>
            <p>The Data Privacy Act of 2012 (DPA) is a law in the Philippines that protects personal and sensitive information. This law ensures that individuals' data is properly collected, processed, and stored, giving them rights regarding their personal data, including the right to access, correct, and request the deletion of their personal information.</p>
            <p>We are committed to complying with the DPA to safeguard your data. We ensure that your personal information is protected and used only for legitimate purposes. If you have concerns about how your personal data is handled, you may contact us at any time for further clarification or to exercise your rights under the DPA.</p>
            
            <h2>6. Your Rights</h2>
            <p>You have the right to access, update, or delete your personal information. If you wish to exercise any of these rights, please contact us at [Your Contact Information].</p>
            
            <h2>7. Changes to This Privacy Policy</h2>
            <p>We may update this Privacy Policy from time to time. Any changes will be posted on this page, and the effective date will be updated accordingly.</p>
            <p>If you have any questions or concerns about this Privacy Policy, please contact us at Stmoniquevalais@gmail.com</p>
        </div>
</body>
</html>
