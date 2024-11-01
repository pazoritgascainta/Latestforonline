<?php
session_name('admin_session'); // Set a unique session name for admins
session_start();

// Database connection
$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Enable error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Initialize status message
$status_message = "";

// Handle approval or rejection of appointments
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['appointment_id']) && isset($_POST['new_status'])) {
    $appointment_id = intval($_POST['appointment_id']);
    $new_status = $_POST['new_status'];

    // Validate new status
    if (!in_array($new_status, ['Accepted', 'Rejected'])) {
        $status_message = "Invalid status value.";
    } else {
        if ($new_status == 'Accepted') {
            // Move the appointment to the accepted_appointments table
            $sql_move = "INSERT INTO accepted_appointments (date, name, email, purpose, homeowner_id, timeslot_id, amenity_id)
                         SELECT a.date, a.name, a.email, a.purpose, a.homeowner_id, a.timeslot_id, a.amenity_id
                         FROM appointments a
                         WHERE a.id = ?";
            $stmt_move = $conn->prepare($sql_move);
            if (!$stmt_move) {
                $status_message = "Prepare statement failed: " . $conn->error;
            } else {
                $stmt_move->bind_param("i", $appointment_id);
                if ($stmt_move->execute()) {
                    // Delete the appointment from the appointments table
                    $sql_delete = "DELETE FROM appointments WHERE id = ?";
                    $stmt_delete = $conn->prepare($sql_delete);
                    if ($stmt_delete) {
                        $stmt_delete->bind_param("i", $appointment_id);
                        $stmt_delete->execute();
                        $status_message = "Appointment accepted and moved successfully!";
                    } else {
                        $status_message = "Error: " . $stmt_delete->error;
                    }
                } else {
                    $status_message = "Error: " . $stmt_move->error;
                }
            }
        } elseif ($new_status == 'Rejected') {
            // Move the appointment to the rejected_appointments table
            $sql_move = "INSERT INTO rejected_appointments (date, name, email, purpose, homeowner_id, timeslot_id, amenity_id)
                         SELECT a.date, a.name, a.email, a.purpose, a.homeowner_id, a.timeslot_id, a.amenity_id
                         FROM appointments a
                         WHERE a.id = ?";
            $stmt_move = $conn->prepare($sql_move);
            if (!$stmt_move) {
                $status_message = "Prepare statement failed: " . $conn->error;
            } else {
                $stmt_move->bind_param("i", $appointment_id);
                if ($stmt_move->execute()) {
                    // Delete the appointment from the appointments table
                    $sql_delete = "DELETE FROM appointments WHERE id = ?";
                    $stmt_delete = $conn->prepare($sql_delete);
                    if ($stmt_delete) {
                        $stmt_delete->bind_param("i", $appointment_id);
                        $stmt_delete->execute();
                        $status_message = "Appointment rejected and moved successfully!";
                    } else {
                        $status_message = "Error: " . $stmt_delete->error;
                    }
                } else {
                    $status_message = "Error: " . $stmt_move->error;
                }
            }
        }
    }

    // Redirect to the same page to avoid resubmission
    header("Location: rejected_appointments.php");
    exit();
}

// Number of records to display per page
$records_per_page = 10;

// Get the current page number from the URL, default to page 1 if not set
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Calculate the offset for the SQL LIMIT clause
$offset = ($current_page - 1) * $records_per_page;

// Amenity name mapping based on amenity_id values
$amenity_names = [
    1 => 'Clubhouse Court',
    2 => 'Townhouse Court',
    3 => 'Clubhouse Swimming Pool',
    4 => 'Townhouse Swimming Pool',
    5 => 'Consultation',
    6 => 'Bluehouse Court'
];


// Search functionality
$search_query = "";
$search_term = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : "";

if ($search_term) {
    $search_query = " WHERE name LIKE '%$search_term%' OR email LIKE '%$search_term%' OR purpose LIKE '%$search_term%' ";
}

// Fetch rejected appointments with pagination and search
$sql_rejected_appointments = "
    SELECT ra.id, ra.date, ra.name, ra.email, ra.purpose, ra.homeowner_id, ra.amenity_id, ts.time_start, ts.time_end
    FROM rejected_appointments ra
    LEFT JOIN timeslots ts ON ra.timeslot_id = ts.id
    $search_query
    LIMIT $records_per_page OFFSET $offset
";


$result_rejected_appointments = $conn->query($sql_rejected_appointments);

// Check for SQL errors
if (!$result_rejected_appointments) {
    die("Query failed: " . $conn->error);
}

// Fetch total number of rejected appointments with search criteria
$sql_total_rejected_appointments = "
    SELECT COUNT(*) AS total 
    FROM rejected_appointments
    $search_query
";

$result_total_rejected_appointments = $conn->query($sql_total_rejected_appointments);

// Check for SQL errors
if (!$result_total_rejected_appointments) {
    die("Query failed: " . $conn->error);
}

$total_rejected_appointments = $result_total_rejected_appointments->fetch_assoc()['total'];

// Calculate the total number of pages for rejected appointments
$total_pages_rejected = ceil($total_rejected_appointments / $records_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
        <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejected Appointments</title>
    <link rel="stylesheet" href="admin_approval.css">
    <link rel="stylesheet" href="accepted_appointments.css">
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <h1>Rejected Appointments</h1>
    <div class="container">
        <div class="admin_approval">
            <a href="admin_approval.php" class="btn-admin-approval">Go Back to Admin Approval</a>
        </div>
        <br>
        <!-- Search Form -->
        <form id="search-form" class="search-form" onsubmit="return false;"> <!-- Prevent default form submission -->
    <div class="form-group" style="position: relative;">
        <input type="text" id="search-input" name="search" placeholder="Search by name, email, or purpose" value="<?= htmlspecialchars($search_term) ?>" oninput="fetchSuggestions()">
        <input type="hidden" id="homeowner_id" name="homeowner_id">
        <div id="suggestions" class="suggestions"></div>
    </div>
    <button type="submit" onclick="submitSearch()">Search</button>
</form>


        <?php if ($result_rejected_appointments->num_rows > 0): ?>
            <table>
    <tr>
        <th>Date</th>
        <th>Name</th>
        <th>Email</th>
        <th>Purpose</th>
        <th>Amenity</th>
        <th>Time Slot</th>
    </tr>
    <?php while ($row = $result_rejected_appointments->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['date']) ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['purpose']) ?></td>
            <td>
                <?= isset($amenity_names[$row['amenity_id']]) ? $amenity_names[$row['amenity_id']] : 'Unknown Amenity' ?>
            </td>
            <td><?= htmlspecialchars($row['time_start'] . ' - ' . $row['time_end']) ?></td>
        </tr>
    <?php endwhile; ?>
</table>

            <div id="pagination">
                <?php
                $total_pages = max($total_pages_rejected, 1);
                $input_page = $current_page;
                
                if ($current_page > 1): ?>
                    <form method="GET" action="rejected_appointments.php" style="display: inline;">
                        <input type="hidden" name="page" value="<?= $current_page - 1 ?>">
                        <button type="submit">&lt;</button>
                    </form>
                <?php endif; ?>
                
                <form method="GET" action="rejected_appointments.php" style="display: inline;">
                    <input type="number" name="page" value="<?= $input_page ?>" min="1" max="<?= $total_pages ?>" style="width: 50px;">
                </form>

                <?php if ($total_pages > 1): ?>
                    <span>of</span>
                    <a href="?page=<?= $total_pages ?>" class="<?= ($current_page == $total_pages) ? 'active' : '' ?>"><?= $total_pages ?></a>
                <?php endif; ?>

                <?php if ($current_page < $total_pages): ?>
                    <form method="GET" action="rejected_appointments.php" style="display: inline;">
                        <input type="hidden" name="page" value="<?= $current_page + 1 ?>">
                        <button type="submit">&gt;</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p>No rejected appointments.</p>
        <?php endif; ?>
    </div>
</div>
</body>
<script>
function fetchSuggestions() {
    const searchQuery = document.getElementById('search-input').value;

    // Clear previous suggestions
    const suggestionsContainer = document.getElementById('suggestions');
    suggestionsContainer.innerHTML = '';

    if (searchQuery.length < 1) {
        return; // Don't search for queries less than 2 characters
    }

    // Create an AJAX request
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'search_suggestions.php?search=' + encodeURIComponent(searchQuery), true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            const suggestions = JSON.parse(xhr.responseText);
            suggestions.forEach(function(suggestion) {
                const suggestionItem = document.createElement('div');
                suggestionItem.textContent = `${suggestion.name} (${suggestion.email})`;
                suggestionItem.classList.add('suggestion-item');

                // Add click event to fill the input with the suggestion
                suggestionItem.addEventListener('click', function() {
                    document.getElementById('search-input').value = suggestion.name; // Or use suggestion.email
                    suggestionsContainer.innerHTML = ''; // Clear suggestions after selection
                    document.getElementById('homeowner_id').value = suggestion.id; // Assuming you want to capture the ID
                });

                suggestionsContainer.appendChild(suggestionItem);
            });
        }
    };
    xhr.send();
}

function submitSearch() {
    const searchInput = document.getElementById('search-input').value;
    const form = document.getElementById('search-form');
    
    // Redirect to the same page with the search term
    window.location.href = `rejected_appointments.php?search=${encodeURIComponent(searchInput)}`;
}
</script>

</html>

<?php
$conn->close();
?>

