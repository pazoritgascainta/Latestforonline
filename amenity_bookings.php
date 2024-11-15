<?php
session_name('user_session'); 
session_start();

// Redirect to login if homeowner is not logged in
if (!isset($_SESSION['homeowner_id'])) {
    header('Location: login.php');
    exit();
}

// Retrieve the homeowner ID from the session
$homeowner_id = $_SESSION['homeowner_id'];

// Database connection
$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to sanitize input data
function sanitize_input($data) {
    global $conn;
    return $conn->real_escape_string(trim(htmlspecialchars($data)));
}

// Function to check if a timeslot is already booked by the same homeowner
function is_timeslot_booked($conn, $homeowner_id, $date, $timeslot_id, $amenity_id) {
    $sql_check = "SELECT COUNT(*) FROM appointments WHERE homeowner_id = ? AND date = ? AND timeslot_id = ? AND amenity_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("issi", $homeowner_id, $date, $timeslot_id, $amenity_id);
    $stmt_check->execute();
    $stmt_check->bind_result($count);
    $stmt_check->fetch();
    $stmt_check->close();
    return $count > 0;
}

// Notify admins about a new appointment
function notify_admins($conn, $homeowner_id, $user_name, $email, $date, $purpose, $timeslot_id, $amenity_id) {
    // Prepare the insert statement
    $sql_notify = "INSERT INTO admin_inbox (admin_id, message, date) VALUES (?, ? , NOW())";

    // Fetch all admins
    $admins_result = $conn->query("SELECT id FROM admin");
    while ($admin_row = $admins_result->fetch_assoc()) {
        $admin_id = $admin_row['id'];

        // Create the message
        $message = "New appointment request from homeowner ID $homeowner_id: $user_name ($email) on $date for purpose '$purpose' with timeslot ID $timeslot_id and amenity ID $amenity_id.";

        // Prepare the statement
        $stmt_notify = $conn->prepare($sql_notify);
        if (!$stmt_notify) {
            echo "Failed to prepare SQL statement: " . $conn->error;
            continue;
        }
        
        // Bind parameters and execute
        $stmt_notify->bind_param("is", $admin_id, $message);
        
        if (!$stmt_notify->execute()) {
            echo "Failed to notify admin: " . $stmt_notify->error;
        }
    }
}

// Handle form submission to book an appointment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_appointment'])) {
    $date = sanitize_input($_POST['date']);
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $purpose = sanitize_input($_POST['purpose']);
    $amenity_id = sanitize_input($_POST['amenity_id']);
    $timeslot_ids = isset($_POST['timeslot_ids']) ? $_POST['timeslot_ids'] : [];

    $homeowner_id = $_SESSION['homeowner_id'];
    $status = 'Pending';

    $errors = [];
    foreach ($timeslot_ids as $timeslot_id) {
        $timeslot_id = intval($timeslot_id);

        // Check if the timeslot is already booked
        if (is_timeslot_booked($conn, $homeowner_id, $date, $timeslot_id, $amenity_id)) {
            $errors[] = "You already booked timeslot ID $timeslot_id.";
            continue;
        }

        // Insert the new appointment
        $sql_insert = "INSERT INTO appointments (homeowner_id, date, name, email, purpose, status, timeslot_id, amenity_id) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        if (!$stmt_insert) {
            $errors[] = "Failed to prepare SQL statement: " . $conn->error;
            continue;
        }
        $stmt_insert->bind_param("isssssii", $homeowner_id, $date, $name, $email, $purpose, $status, $timeslot_id, $amenity_id);

        if ($stmt_insert->execute()) {
            // Notify admins about the new appointment
            notify_admins($conn, $homeowner_id, $name, $email, $date, $purpose, $timeslot_id, $amenity_id);
        } else {
            $errors[] = "Failed to execute SQL statement: " . $stmt_insert->error;
        }
    }

    // Prepare response and redirect
    if (empty($errors)) {
        $_SESSION['message'] = ['status' => 'success', 'message' => 'Appointment booked successfully.'];
    } else {
        $_SESSION['message'] = ['status' => 'error', 'message' => implode(', ', $errors)];
    }

    // Redirect to the booking page
    header('Location: amenity_booking.php');
    exit();
}

// Fetch amenities for the dropdown
$sql_amenities = "SELECT * FROM amenities";
$result_amenities = $conn->query($sql_amenities);
$amenities = $result_amenities->fetch_all(MYSQLI_ASSOC);

// Fetch available timeslots for the selected date and amenity
$timeslots = [];
$selected_date = "";
if (isset($_GET['date']) && isset($_GET['amenity_id'])) {
    $selected_date = sanitize_input($_GET['date']);
    $amenity_id = sanitize_input($_GET['amenity_id']);
    $sql_timeslots = "SELECT * FROM timeslots WHERE amenity_id = ? AND date = ?";
    $stmt_timeslots = $conn->prepare($sql_timeslots);
    $stmt_timeslots->bind_param("is", $amenity_id, $selected_date);
    $stmt_timeslots->execute();
    $result_timeslots = $stmt_timeslots->get_result();
    $timeslots = $result_timeslots->fetch_all(MYSQLI_ASSOC);
}

// Remove past and rejected appointments
$sql_remove_past_rejected = "DELETE FROM appointments WHERE date < CURDATE() OR status = 'Rejected'";
$conn->query($sql_remove_past_rejected);

// Pagination settings
$limit = 10; // Number of appointments per page
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($current_page - 1) * $limit;

// Fetch total number of appointments
$sql_count = "SELECT COUNT(*) as total FROM appointments WHERE homeowner_id = ? AND status = 'Pending' AND date >= CURDATE()";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->bind_param("i", $homeowner_id);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$row_count = $result_count->fetch_assoc();
$total_appointments = $row_count['total'];
$total_pages = max(ceil($total_appointments / $limit), 1);

// Fetch accepted appointments
$sql_accepted_appointments = "SELECT a.id, a.date, a.name, a.email, a.purpose, a.status, t.time_start, t.time_end, am.name AS amenity_name, a.amount
                              FROM accepted_appointments a
                              JOIN timeslots t ON a.timeslot_id = t.id
                              JOIN amenities am ON a.amenity_id = am.id
                              WHERE a.homeowner_id = ? AND a.date >= CURDATE()";
$stmt_accepted_appointments = $conn->prepare($sql_accepted_appointments);
$stmt_accepted_appointments->bind_param("i", $_SESSION['homeowner_id']);
$stmt_accepted_appointments->execute();
$result_accepted_appointments = $stmt_accepted_appointments->get_result();
$accepted_appointments = $result_accepted_appointments->fetch_all(MYSQLI_ASSOC);

// Fetch booked appointments with pagination
$sql_booked_appointments = "SELECT a.id, a.date, a.name, a.email, a.purpose, a.status, t.time_start, t.time_end, am.name AS amenity_name
                            FROM appointments a
                            JOIN timeslots t ON a.timeslot_id = t.id
                            JOIN amenities am ON a.amenity_id = am.id
                            WHERE a.homeowner_id = ? AND a.status = 'Pending' AND a.date >= CURDATE()
                            LIMIT ?, ?";
$stmt_booked_appointments = $conn->prepare($sql_booked_appointments);
$stmt_booked_appointments->bind_param("iii", $_SESSION['homeowner_id'], $offset, $limit);
$stmt_booked_appointments->execute();
$result_booked_appointments = $stmt_booked_appointments->get_result();
$booked_appointments = $result_booked_appointments->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Calendar</title>
    <link rel="stylesheet" href="styles.css"> <!-- Include your CSS file -->
    <style>
        /* Basic styles for calendar */
        #calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr); /* 7 days in a week */
            gap: 5px; /* Space between time slots */
            margin: 20px;
            user-select: none; /* Prevent text selection while dragging */
        }

        .calendar-cell {
            border: 1px solid #ccc;
            padding: 10px;
            background-color: #f4f4f4;
            position: relative; /* For positioning */
            cursor: pointer; /* Change cursor to indicate interaction */
        }

        .selected {
            background-color: #4CAF50; /* Color for selected time slots */
            color: white;
        }

        .calendar-header {
            font-weight: bold;
            text-align: center;
        }
    </style>
</head>
<body>

<h1>Appointment Calendar</h1>
<div id="calendar"></div>

<script>
    const calendarContainer = document.getElementById('calendar');
    let selectedTimeSlots = [];
    let isSelecting = false;

    // Sample data for demonstration (You would fetch this from your server)
    const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const timeSlots = Array.from({ length: 24 }, (_, i) => `${i}:00 - ${i + 1}:00`); // 24 hours

    // Generate the calendar
    function generateCalendar() {
        // Add day headers
        daysOfWeek.forEach(day => {
            const headerCell = document.createElement('div');
            headerCell.className = 'calendar-header';
            headerCell.innerText = day;
            calendarContainer.appendChild(headerCell);
        });

        // Add time slots (assuming each day has the same time slots)
        timeSlots.forEach(slot => {
            daysOfWeek.forEach(day => {
                const cell = document.createElement('div');
                cell.className = 'calendar-cell';
                cell.innerText = slot;

                // Mouse events for selecting time slots
                cell.addEventListener('mousedown', () => startSelecting(cell));
                cell.addEventListener('mouseenter', () => isSelecting && selectCell(cell));
                cell.addEventListener('mouseup', () => stopSelecting());

                calendarContainer.appendChild(cell);
            });
        });
    }

    function startSelecting(cell) {
        isSelecting = true; // start selecting
        selectCell(cell);
    }

    function selectCell(cell) {
        if (!selectedTimeSlots.includes(cell.innerText)) {
            selectedTimeSlots.push(cell.innerText); // Track selected time slot
            cell.classList.add('selected'); // Visually indicate selection
        } else {
            // Optional: allow deselection if already selected
            selectedTimeSlots = selectedTimeSlots.filter(slot => slot !== cell.innerText);
            cell.classList.remove('selected'); // Remove selection visually
        }
    }

    function stopSelecting() {
        isSelecting = false; // Stop selecting
    }

    // Capture mouse up on window to handle stopping dragging outside
    window.addEventListener('mouseup', stopSelecting);

    // Generate the calendar on load
    window.onload = generateCalendar;
</script>

</body>
</html>