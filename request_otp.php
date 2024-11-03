<?php
session_start();
require __DIR__ . '/vendor/autoload.php'; // Ensure you have composer autoload

// Database connection variables
$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    // Check if email exists
    $sql = "SELECT id, phone_number FROM homeowners WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($homeowner_id, $phone_number);
        $stmt->fetch();

        // Generate OTP and store in session
        $otp = rand(100000, 999999); // Generate a 6-digit OTP
        $_SESSION['otp'] = $otp;
        $_SESSION['homeowner_id'] = $homeowner_id;

        // Send OTP via Infobip
        $infobipApiKey = "60ae770b87b2a0cf4393376f9719d5b2-30188875-48f2-45f0-80e9-b1745e6fad4e"; // Your API Key
        $infobipBaseUrl = "https://kq2pdn.api.infobip.com"; // Your API URL
        $smsMessage = "Your OTP is: $otp";

        $payload = [
            "messages" => [
                [
                    "from" => "StMonique",
                    "destinations" => [["to" => $phone_number]],
                    "text" => $smsMessage,
                ]
            ]
        ];

        $ch = curl_init("$infobipBaseUrl/sms/2/text/advanced");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: App $infobipApiKey",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errorMessage = curl_error($ch); // Capture any cURL error
        curl_close($ch);

        // Log the response for debugging
        error_log("Infobip Response: " . $response);
        error_log("HTTP Code: " . $httpCode);
        if ($errorMessage) {
            error_log("cURL Error: " . $errorMessage);
        }

        // Check response for success or failure
        if ($httpCode == 200) {
            echo json_encode(["success" => true]);
        } else {
            // Log additional details if sending fails
            error_log("Failed to send OTP. HTTP Code: $httpCode, Response: $response");
            echo json_encode(["success" => false, "message" => "Failed to send OTP. HTTP Code: $httpCode", "response" => $response]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "No account found with that email."]);
    }
    $stmt->close();
}
$conn->close();
?>
