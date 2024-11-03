<?php
// Display errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database connection details
$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Task 1: Send reminders for appointments scheduled tomorrow
    $stmt = $pdo->prepare("
        SELECT * FROM appointments 
        WHERE DATE(appointment_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
    ");
    $stmt->execute();
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($appointments as $appointment) {
        // Replace with actual notification code, e.g., an SMS or email API
        echo "Sending reminder for appointment ID: " . $appointment['id'] . "\n";
    }

    // Task 2: Archive past appointments older than 1 year
    $stmt = $pdo->prepare("
        DELETE FROM appointments 
        WHERE appointment_date < DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
    ");
    $stmt->execute();
    echo "Archived old appointments.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
