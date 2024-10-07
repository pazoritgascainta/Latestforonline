<?php
session_name('admin_session');
require __DIR__ . "/vendor/autoload.php";
use Dotenv\Dotenv;
use Infobip\Configuration;
use Infobip\Api\SmsApi;
use Infobip\Model\SmsDestination;
use Infobip\Model\SmsTextualMessage;
use Infobip\Model\SmsAdvancedTextualRequest;

// Load environment variables from .env file
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Get API URL and API key from environment variables
$apiURL = $_ENV['API_URL'];
$apiKey = $_ENV['API_KEY'];

// Function to validate phone numbers using a basic E.164 format
function validatePhoneNumber($phoneNumber) {
    return preg_match('/^\+?[1-9]\d{1,14}$/', $phoneNumber);
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "homeowner";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch homeowners with overdue bills
    $stmt = $pdo->prepare("
        SELECT h.id AS homeowner_id, h.phone_number, b.status 
        FROM homeowners h 
        JOIN billing b ON h.id = b.homeowner_id 
        WHERE b.status = 'overdue'
    ");
    $stmt->execute();

    $homeowners = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare SMS API configuration
    $configuration = new Configuration(host: $apiURL, apiKey: $apiKey);
    $api = new SmsApi(config: $configuration);

    // Loop through homeowners with overdue bills
    foreach ($homeowners as $homeowner) {
        $phoneNumber = $homeowner['phone_number'];
        $homeownerId = $homeowner['homeowner_id'];

        // Check if the phone number is valid
        if (!validatePhoneNumber($phoneNumber)) {
            error_log("Invalid phone number: $phoneNumber");
            continue; // Skip invalid phone numbers
        }

        // Prepare the overdue message
        $message = "Your monthly due bill is overdue.";

        // Send SMS for overdue homeowners
        sendSms($pdo, $api, $homeownerId, $phoneNumber, $message);
    }

    // Display the HTML content for SMS history


} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo 'Failed to connect to the database.';
}

// Function to send SMS and log it to the database
function sendSms($pdo, $api, $homeownerId, $phoneNumber, $message) {
    // Prepare the SMS message
    $destination = new SmsDestination(to: $phoneNumber);
    $theMessage = new SmsTextualMessage(
        destinations: [$destination],
        text: $message,
        from: "ST.MoniqueVHOA"
    );
    $request = new SmsAdvancedTextualRequest(messages: [$theMessage]);

    // Try sending the message
    try {
        $response = $api->sendSmsMessage($request);

        // Log the sent SMS to the database
        $stmt = $pdo->prepare("
            INSERT INTO sms_history (homeowner_id, phone_number, message, status)
            VALUES (:homeowner_id, :phone_number, :message, :status)
        ");
        $stmt->execute([
            ':homeowner_id' => $homeownerId,
            ':phone_number' => $phoneNumber,
            ':message' => $message,
            ':status' => 'Sent'
        ]);

        echo "Message sent to $phoneNumber: $message\n";

    } catch (Exception $e) {
        // Log failed SMS attempt
        $stmt = $pdo->prepare("
            INSERT INTO sms_history (homeowner_id, phone_number, message, status)
            VALUES (:homeowner_id, :phone_number, :message, :status)
        ");
        $stmt->execute([
            ':homeowner_id' => $homeownerId,
            ':phone_number' => $phoneNumber,
            ':message' => $message,
            ':status' => 'Failed'
        ]);

        error_log('Error sending message: ' . $e->getMessage());
        echo 'Failed to send message to ' . $phoneNumber . "\n";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Notification History</title>
    <link rel="stylesheet" href="billcss.css">
</head>
<body>
    <!-- Sidebar -->
    <?php include "sidebar.php"; ?>

    <div class="main-content">
        <header>
            <h1>SMS Notification History</h1>
            <p>Here you can view the history of all SMS notifications sent to homeowners.</p>
        </header>

        <br>

        <div class="container">
            <!-- SMS History Section -->
            <section>
                <h2>SMS History Log</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Homeowner ID</th>
                            <th>Phone Number</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Sent At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch and display SMS history from the database
                        
                        $stmt = $pdo->prepare("SELECT * FROM sms_history ORDER BY sent_at DESC");
                        $stmt->execute();
                        $smsHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (count($smsHistory) > 0) {
                            foreach ($smsHistory as $sms) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($sms['homeowner_id']) . "</td>";
                                echo "<td>" . htmlspecialchars($sms['phone_number']) . "</td>";
                                echo "<td>" . htmlspecialchars($sms['message']) . "</td>";
                                echo "<td>" . htmlspecialchars($sms['status']) . "</td>";
                                echo "<td>" . htmlspecialchars($sms['sent_at']) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5'>No SMS history found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</body>
</html>
