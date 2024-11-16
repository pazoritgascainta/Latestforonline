<?php
// Include database connection file
// Database connection
$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = intval($_POST['appointment_id']);

    // Start a transaction (optional but recommended for multiple operations)
    $conn->begin_transaction();

    try {
        // Fetch the timeslot ID before deleting the appointment
        $stmt = $conn->prepare("SELECT timeslot_id FROM appointments WHERE id = ?");
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $stmt->bind_result($timeslot_id);
        $stmt->fetch();
        $stmt->close();

        // Delete the appointment
        $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $stmt->close();

        // Update the timeslot availability
        $stmt = $conn->prepare("UPDATE timeslots SET is_available = 1 WHERE id = ?");
        $stmt->bind_param("i", $timeslot_id);
        $stmt->execute();
        $stmt->close();

        // Commit the transaction
        $conn->commit();

        // Send back a successful response
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        // Rollback the transaction if any errors occur
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>