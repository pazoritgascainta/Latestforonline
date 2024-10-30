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
// (keep the existing code here)

// Number of records to display per page
$records_per_page = 10;

// Get the current page number from the URL, default to page 1 if not set
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Calculate the offset for the SQL LIMIT clause
$offset = ($current_page - 1) * $records_per_page;

// Capture search query from GET request
$search_query = isset($_GET['search']) ? trim($_GET['search']) : "";

// Fetch passed appointments with pagination and search filter
if ($search_query) {
    $sql_passed_appointments = "
        SELECT pa.id, pa.date, pa.name, pa.email, pa.purpose, pa.homeowner_id, pa.amenity_id, ts.time_start, ts.time_end
        FROM passed_appointments pa
        JOIN timeslots ts ON pa.timeslot_id = ts.id
        WHERE pa.name LIKE ? OR pa.email LIKE ?
        LIMIT ? OFFSET ?";
    $stmt_passed_appointments = $conn->prepare($sql_passed_appointments);
    $search_term = "%" . $search_query . "%";
    $stmt_passed_appointments->bind_param("ssii", $search_term, $search_term, $records_per_page, $offset);
} else {
    $sql_passed_appointments = "
        SELECT pa.id, pa.date, pa.name, pa.email, pa.purpose, pa.homeowner_id, pa.amenity_id, ts.time_start, ts.time_end
        FROM passed_appointments pa
        JOIN timeslots ts ON pa.timeslot_id = ts.id
        LIMIT ? OFFSET ?";
    $stmt_passed_appointments = $conn->prepare($sql_passed_appointments);
    $stmt_passed_appointments->bind_param("ii", $records_per_page, $offset);
}

$stmt_passed_appointments->execute();
$result_passed_appointments = $stmt_passed_appointments->get_result();

$amenity_names = [
    '1' => 'Clubhouse Court',
    '2' => 'Townhouse Court',
    '3' => 'Clubhouse Swimming Pool',
    '4' => 'Townhouse Swimming Pool',
    '5' => 'Consultation',
    '6' => 'Bluehouse Court'
];

// Fetch total number of passed appointments for pagination (with or without search)
if ($search_query) {
    $sql_total_passed_appointments = "
        SELECT COUNT(*) AS total
        FROM passed_appointments pa
        JOIN timeslots ts ON pa.timeslot_id = ts.id
        WHERE pa.name LIKE ? OR pa.email LIKE ?";
    $stmt_total_passed_appointments = $conn->prepare($sql_total_passed_appointments);
    $stmt_total_passed_appointments->bind_param("ss", $search_term, $search_term);
} else {
    $sql_total_passed_appointments = "
        SELECT COUNT(*) AS total
        FROM passed_appointments pa
        JOIN timeslots ts ON pa.timeslot_id = ts.id";
    $stmt_total_passed_appointments = $conn->prepare($sql_total_passed_appointments);
}

$stmt_total_passed_appointments->execute();
$result_total_passed_appointments = $stmt_total_passed_appointments->get_result();
$total_passed_appointments = $result_total_passed_appointments->fetch_assoc()['total'];

// Calculate the total number of pages for passed appointments
$total_pages_passed = ceil($total_passed_appointments / $records_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Missed Appointments</title>
    <link rel="stylesheet" href="admin_approval.css">
    <link rel="stylesheet" href="accepted_appointments.css">
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <h1>Missed Appointments</h1>
    <div class="container">
        <div class="admin_approval">
            <a href="admin_approval.php" class="btn-admin-approval">Go Back to Admin Approval</a>
        </div>
        <br>
        <form id="search-form" class="search-form" onsubmit="return false;"> <!-- Prevent default form submission -->
    <div class="form-group" style="position: relative;">
        <input type="text" id="search-input" name="search" placeholder="Search by name or email" value="<?= htmlspecialchars($search_query); ?>" oninput="fetchSuggestions()">
        <input type="hidden" id="homeowner_id" name="homeowner_id"> <!-- Hidden field to store homeowner ID -->
        <div id="suggestions" class="suggestions"></div> <!-- Container for suggestions -->
    </div>
    <button type="submit" onclick="submitSearch()">Search</button> <!-- Trigger search on button click -->
</form>

        <?php if ($result_passed_appointments->num_rows > 0): ?>
            <table>
    <tr>
        <th>Date</th>
        <th>Name</th>
        <th>Email</th>
        <th>Purpose</th>
        <th>Amenity</th>
        <th>Time Slot</th>
    </tr>
    <?php while ($row = $result_passed_appointments->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['date']) ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['purpose']) ?></td>
            <td>
                <?php
                // Display the amenity name based on amenity_id
                $amenity_id = $row['amenity_id'];
                echo isset($amenity_names[$amenity_id]) ? $amenity_names[$amenity_id] : 'Unknown Amenity';
                ?>
            </td>
            <td><?= htmlspecialchars($row['time_start'] . ' - ' . $row['time_end']) ?></td>
        </tr>
    <?php endwhile; ?>
</table>

            <div id="pagination">
                <?php
                $total_pages = max($total_pages_passed, 1);
                $input_page = $current_page;
                
                if ($current_page > 1): ?>
                    <form method="GET" action="passed_appointments.php" style="display: inline;">
                        <input type="hidden" name="page" value="<?= $current_page - 1 ?>">
                        <button type="submit">&lt;</button>
                    </form>
                <?php endif; ?>
                
                <form method="GET" action="passed_appointments.php" style="display: inline;">
                    <input type="number" name="page" value="<?= $input_page ?>" min="1" max="<?= $total_pages ?>" style="width: 50px;">
                </form>

                <?php if ($total_pages > 1): ?>
                    <span>of</span>
                    <a href="?page=<?= $total_pages ?>" class="<?= ($current_page == $total_pages) ? 'active' : '' ?>"><?= $total_pages ?></a>
                <?php endif; ?>
                
                <?php if ($current_page < $total_pages): ?>
                    <form method="GET" action="passed_appointments.php" style="display: inline;">
                        <input type="hidden" name="page" value="<?= $current_page + 1 ?>">
                        <button type="submit">&gt;</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p>No passed appointments.</p>
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
