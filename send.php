<?php
// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_name('admin_session');
require __DIR__ . "/vendor/autoload.php";

use Dotenv\Dotenv;
use Infobip\Configuration;
use Infobip\Api\SmsApi;
use Infobip\Model\SmsDestination;
use Infobip\Model\SmsTextualMessage;
use Infobip\Model\SmsAdvancedTextualRequest;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Infobip API credentials
$apiURL = $_ENV['API_URL'];
$apiKey = $_ENV['API_KEY'];

// Setup Infobip API configuration
$configuration = new Configuration(host: $apiURL, apiKey: $apiKey);
$api = new SmsApi(config: $configuration);

// Database connection details
$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

// Establish a database connection
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch homeowners with overdue bills
    $stmt = $pdo->prepare("
        SELECT h.id AS homeowner_id, h.phone_number, b.status, b.due_date 
        FROM homeowners h 
        JOIN billing b ON h.id = b.homeowner_id 
        WHERE b.status = 'overdue'
    ");
    $stmt->execute();
    $homeowners = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Loop through homeowners with overdue bills
    foreach ($homeowners as $homeowner) {
        $phoneNumber = $homeowner['phone_number'];
        $homeownerId = $homeowner['homeowner_id'];

        // Check if the phone number is valid
        if (!validatePhoneNumber($phoneNumber)) {
            error_log("Invalid phone number: $phoneNumber");
            continue;
        }

        // Prepare overdue message
        $message = "Your monthly payment is overdue. Please settle the amount to avoid inconvenience.";

        // Send SMS for overdue homeowners
        sendSms($pdo, $api, $homeownerId, $phoneNumber, $message);
    }

    // Fetch homeowners with pending bills (5 days before due date)
    $stmt = $pdo->prepare("
        SELECT h.id AS homeowner_id, h.phone_number, b.status, b.due_date 
        FROM homeowners h 
        JOIN billing b ON h.id = b.homeowner_id 
        WHERE b.status = 'pending' 
        AND b.due_date = DATE_ADD(CURDATE(), INTERVAL 5 DAY)
    ");
    $stmt->execute();
    $pendingHomeowners = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Loop through homeowners with pending bills
    foreach ($pendingHomeowners as $homeowner) {
        $phoneNumber = $homeowner['phone_number'];
        $homeownerId = $homeowner['homeowner_id'];

        // Check if the phone number is valid
        if (!validatePhoneNumber($phoneNumber)) {
            error_log("Invalid phone number: $phoneNumber");
            continue;
        }

        // Prepare the message for upcoming bills
        $message = "Your monthly payment is due soon. Please make the payment by the due date to avoid inconvenience.";

        // Send SMS for pending homeowners
        sendSms($pdo, $api, $homeownerId, $phoneNumber, $message);
    }

    // Fetch homeowners with paid bills
    $stmt = $pdo->prepare("
        SELECT h.id AS homeowner_id, h.phone_number, b.status 
        FROM homeowners h 
        JOIN billing b ON h.id = b.homeowner_id 
        WHERE b.status = 'paid'
    ");
    $stmt->execute();
    $paidHomeowners = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Loop through homeowners with paid bills
    foreach ($paidHomeowners as $homeowner) {
        $phoneNumber = $homeowner['phone_number'];
        $homeownerId = $homeowner['homeowner_id'];

        // Check if the phone number is valid
        if (!validatePhoneNumber($phoneNumber)) {
            error_log("Invalid phone number: $phoneNumber");
            continue;
        }

        // Prepare confirmation message
        $message = "Thank you for your payment. Your account is up to date.";

        // Send SMS for paid homeowners
        sendSms($pdo, $api, $homeownerId, $phoneNumber, $message);
    }

    echo "SMS notifications processed successfully.\n";

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo "Failed to connect to the database.\n";
}

// Function to validate phone numbers in E.164 format
function validatePhoneNumber($phoneNumber) {
    return preg_match('/^\+?[1-9]\d{1,14}$/', $phoneNumber);
}

// Function to send SMS and log it to the database
function sendSms($pdo, $api, $homeownerId, $phoneNumber, $message) {
    $destination = new SmsDestination(to: $phoneNumber);
    $theMessage = new SmsTextualMessage(
        destinations: [$destination],
        text: $message,
        from: "ST.MoniqueVHOA"
    );
    $request = new SmsAdvancedTextualRequest(messages: [$theMessage]);

    try {
        $api->sendSmsMessage($request);

        // Log the sent SMS to the database
        $stmt = $pdo->prepare("
            INSERT INTO sms_history (homeowner_id, phone_number, message, status)
            VALUES (:homeowner_id, :phone_number, :message, 'Sent')
        ");
        $stmt->execute([
            ':homeowner_id' => $homeownerId,
            ':phone_number' => $phoneNumber,
            ':message' => $message
        ]);

        echo "Message sent to $phoneNumber: $message\n";

    } catch (Exception $e) {
        // Log the failure
        $stmt = $pdo->prepare("
            INSERT INTO sms_history (homeowner_id, phone_number, message, status)
            VALUES (:homeowner_id, :phone_number, :message, 'Failed')
        ");
        $stmt->execute([
            ':homeowner_id' => $homeownerId,
            ':phone_number' => $phoneNumber,
            ':message' => $message
        ]);

        error_log("Failed to send message to $phoneNumber: " . $e->getMessage());
    }
}
?>
