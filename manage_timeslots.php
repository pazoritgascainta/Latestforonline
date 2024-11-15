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

// Function to sanitize input data
function sanitize_input($data) {
    global $conn;
    return $conn->real_escape_string(trim(htmlspecialchars($data)));
}

// Handle form submissions for generating timeslots
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'generate_timeslots') {
        // Sanitize the input date
        $date = sanitize_input($_POST['date']);
        
        // Get the selected amenity IDs from the form (make sure it's an array)
        $amenity_ids = isset($_POST['amenity_ids']) ? $_POST['amenity_ids'] : []; 
        
        // Check if any amenities are selected
        if (empty($amenity_ids)) {
            // Redirect back to the previous page with an error message
            header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=no_amenities_selected");
            exit;
        }

        // Define the user-specified start time and end time for generating slots (e.g., 9 AM to 10 PM)
        $start_time = strtotime("09:00"); // 9:00 AM
        $end_time = strtotime("22:00");  // 10:00 PM
        
        $timeslot_count = 0; // Counter to track number of timeslots created
        
        // Loop through each amenity ID and generate timeslots
        foreach ($amenity_ids as $amenity_id) {
            // Ensure the amenity_id is valid by checking it exists in the amenities table
            $sql = "SELECT id FROM amenities WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $amenity_id);
            $stmt->execute();
            $stmt->store_result();
            
            // If no matching amenity is found, skip to the next amenity
            if ($stmt->num_rows == 0) {
                continue; // Skip invalid amenity_id
            }
            
            // Generate timeslots between the start and end time (flexible range)
            while ($start_time < $end_time) {
                $next_time = strtotime("+30 minutes", $start_time); // Change interval as needed

                // Prepare and execute the SQL statement
                $sql = "INSERT INTO timeslots (date, time_start, time_end, amenity_id, is_available) VALUES (?, ?, ?, ?, TRUE)";
                $stmt = $conn->prepare($sql);

                // Bind parameters
                $start_time_formatted = date("H:i", $start_time);
                $next_time_formatted = date("H:i", $next_time);
                $stmt->bind_param("ssss", $date, $start_time_formatted, $next_time_formatted, $amenity_id);

                // Execute and check for success
                if ($stmt->execute()) {
                    $timeslot_count++;
                } else {
                    echo "<p>Error: " . $stmt->error . "</p>";
                }

                $start_time = $next_time; // Update the start time for the next slot
            }

            // Close the statement after each loop
            $stmt->close();
        }

        // Set the success message if timeslots were created
        if ($timeslot_count > 0) {
            $_SESSION['success_message'] = "{$timeslot_count} timeslot(s) created.";
        }

        // Redirect to the same page to clear POST data
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Pagination logic
$limit = 10; // Number of timeslots per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Query to fetch the total number of timeslots
$sql = "SELECT COUNT(*) as total FROM timeslots";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$total_timeslots = $row['total'];

// Calculate total pages
$total_pages = ceil($total_timeslots / $limit);

// Fetch the timeslots for the current page
$sql = "SELECT ts.id, ts.date, ts.time_start, ts.time_end, a.name AS amenity_name
        FROM timeslots ts
        JOIN amenities a ON ts.amenity_id = a.id
        LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $offset, $limit);
$stmt->execute();
$timeslots_result = $stmt->get_result();
$timeslots = $timeslots_result->fetch_all(MYSQLI_ASSOC);

// Fetch amenities for the form
$sql = "SELECT * FROM amenities";
$amenities_result = $conn->query($sql);
$amenities = $amenities_result->fetch_all(MYSQLI_ASSOC);

// Create an associative array for amenities
$amenity_options = [];
foreach ($amenities as $amenity) {
    $amenity_options[$amenity['id']] = $amenity['name'];
}

// Close the database connection
$stmt->close();
$conn->close();
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Timeslots</title>
    <link rel="stylesheet" href="manage_timeslots.css">
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <a href="admin_approval.php" class="btn-admin_approval">Back to Admin Approval</a>
    <br>
    <h1>Manage Timeslots</h1><br>
    <h2>Amenity ID# Color Legend</h2>
    <div class="id-legend-item">
        <div class="id-circle id-1">1</div>
        <span>Clubhouse Court</span>
    </div>
    <div class="id-legend-item">
        <div class="id-circle id-2">2</div>
        <span>Townhouse Court</span>
    </div>
    <div class="id-legend-item">
        <div class="id-circle id-3">3</div>
        <span>Clubhouse Swimming Pool</span>
    </div>
    <div class="id-legend-item">
        <div class="id-circle id-4">4</div>
        <span>Townhouse Swimming Pool</span>
    </div>
    <div class="id-legend-item">
        <div class="id-circle id-5">5</div>
        <span>Consultation</span>
    </div>
    <div class="id-legend-item">
        <div class="id-circle id-6">6</div>
        <span>Bluehouse Court</span>
    </div><br>

    <div class="container">
        <!-- Form for generating automatic timeslots -->
        <form id="auto_form" method="POST">
            <h2>Generate Timeslots Automatically</h2>
            <div>
                <label for="date">Date:</label>
                <input type="date" id="date" name="date" required>
            </div>

            <div class="id-legend-container">
    <label for="amenities">Amenities:</label>
    <select id="amenities" name="amenity_ids[]" multiple>
        <option value="1">Clubhouse Court</option>
        <option value="2">Townhouse Court</option>
        <option value="3">Clubhouse Swimming Pool</option>
        <option value="4">Townhouse Swimming Pool</option>
        <option value="5">Consultation</option>
        <option value="6">Bluehouse Court</option>
    </select>
</div>




            <div>
                <button type="submit" name="action" value="generate_timeslots">Generate Timeslots</button>
            </div>
        </form>

        <br>

        <h2>Sort Available Timeslots</h2>
        <div>
            <form method="GET" action="">
                <div>
                    <label for="filter_date">Select Date:</label>
                    <input type="date" id="filter_date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                    <br></br>
                    <button type="submit">Filter</button>
                </div>
            </form>
            <select onchange="window.location.href=this.value;">
                <option value="?date=<?php echo urlencode($date_filter); ?>">See All</option>
                <?php foreach ($amenity_options as $id => $name): ?>
                    <option value="?amenity_id=<?php echo $id; ?>&date=<?php echo urlencode($date_filter); ?>">
                        <?php echo htmlspecialchars($name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if (!empty($timeslots)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Amenity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($timeslots as $timeslot): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($timeslot['id']); ?></td>
                            <td><?php echo htmlspecialchars($timeslot['date']); ?></td>
                            <td><?php echo htmlspecialchars($timeslot['time_start']); ?></td>
                            <td><?php echo htmlspecialchars($timeslot['time_end']); ?></td>
                            <td><?php echo htmlspecialchars($timeslot['amenity_name']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No timeslots available.</p>
        <?php endif; ?>
<!-- Pagination -->
<div id="pagination">
    <!-- Previous button -->
    <?php if ($page > 1): ?>
        <form method="GET" action="manage_timeslots.php" style="display: inline;">
            <input type="hidden" name="page" value="<?= $page - 1 ?>">
            <button type="submit"><</button>
        </form>
    <?php endif; ?>

    <!-- Page input for user to change the page -->
    <form method="GET" action="manage_timeslots.php" style="display: inline;">
        <input type="number" name="page" value="<?= $page ?>" min="1" max="<?= $total_pages ?>" style="width: 50px;" onchange="this.form.submit()">
    </form>

    <!-- "of" text and last page link -->
    <?php if ($total_pages > 1): ?>
        <span>of</span>
        <a href="?page=<?= $total_pages ?>" class="<?= ($page == $total_pages) ? 'active' : '' ?>"><?= $total_pages ?></a>
    <?php endif; ?>

    <!-- Next button -->
    <?php if ($page < $total_pages): ?>
        <form method="GET" action="manage_timeslots.php" style="display: inline;">
            <input type="hidden" name="page" value="<?= $page + 1 ?>">
            <button type="submit">></button>
        </form>
    <?php endif; ?>
</div>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to show a specific form
        function showForm(formId) {
            document.getElementById('auto_form').style.display = formId === 'auto' ? 'block' : 'none';
        }

        // Initialize by showing the first form
        showForm('auto'); // or 'manual' based on your preference

        // Add event listeners to buttons
        document.getElementById('auto_btn').addEventListener('click', function() {
            showForm('auto');
        });

        // Display success message if set
        <?php if (isset($_SESSION['success_message'])): ?>
            alert("<?php echo addslashes($_SESSION['success_message']); ?>");
            <?php unset($_SESSION['success_message']); // Clear the message ?>
        <?php endif; ?>
    });
</script>
</body>
</html>