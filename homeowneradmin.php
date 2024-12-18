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

// Initialize status message
$status_message = "";

// Handle homeowner activation or deactivation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['homeowner_id']) && isset($_POST['new_status'])) {
    $homeowner_id = intval($_POST['homeowner_id']);
    $new_status = $_POST['new_status'];

    // Update the homeowner status to archived
    if (in_array($new_status, ['active', 'inactive', 'archived'])) {
        $sql_update_status = "UPDATE homeowners SET status = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update_status);
        if ($stmt_update) {
            $stmt_update->bind_param("si", $new_status, $homeowner_id);
            if ($stmt_update->execute()) {
                $status_message = "Homeowner status updated successfully!";
            } else {
                $status_message = "Failed to update homeowner status: " . $stmt_update->error;
            }
            $stmt_update->close();
        } else {
            $status_message = "Prepare statement failed: " . $conn->error;
        }
    } else {
        $status_message = "Invalid status value.";
    }

    $_SESSION['message'] = ['status' => 'success', 'message' => $status_message];
    header('Location: homeowneradmin.php');
    exit();
}

// Pagination settings
$records_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max($current_page, 1); // Ensure the page number is at least 1

// Calculate offset
$offset = ($current_page - 1) * $records_per_page;

// Handle search query
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = !empty($search_query) ? " AND (name LIKE ? OR email LIKE ?)" : '';

// Handle sorting
$sort_order = isset($_GET['sort']) && $_GET['sort'] === 'asc' ? 'ASC' : 'DESC';

// Fetch active homeowners with pagination, search, and sorting
$sql_homeowners = "
    SELECT id, name, email, phone_number, address, created_at, status, sqm 
    FROM homeowners 
    WHERE status != 'archived'
    $search_condition
    ORDER BY created_at $sort_order
    LIMIT $records_per_page OFFSET $offset
";

$stmt_homeowners = $conn->prepare($sql_homeowners);
if (!empty($search_query)) {
    $search_term = "%$search_query%";
    $stmt_homeowners->bind_param("ss", $search_term, $search_term);
}
$stmt_homeowners->execute();
$result_homeowners = $stmt_homeowners->get_result();

// Get total homeowners count with search
$sql_total_homeowners = "SELECT COUNT(*) AS total FROM homeowners WHERE status != 'archived' $search_condition";
$stmt_total_homeowners = $conn->prepare($sql_total_homeowners);
if (!empty($search_query)) {
    $stmt_total_homeowners->bind_param("ss", $search_term, $search_term);
}
$stmt_total_homeowners->execute();
$total_homeowners = $stmt_total_homeowners->get_result()->fetch_assoc()['total'];

// Calculate total pages
$total_pages = ceil($total_homeowners / $records_per_page);

// Ensure current page is within range
$current_page = min($current_page, $total_pages); // Ensure page is not greater than total pages
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homeowners</title>
    <link rel="stylesheet" href="dashbcss.css">
    <link rel="stylesheet" href="homeownercss.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <h2>List of Homeowners</h2>
        <br>
        <!-- Action Buttons -->
        <a class="btn btn-primary" href="create.php" role="button">New Homeowner</a>
        <a class="btn btn-primary" href="archive.php" role="button">Archived Homeowners</a>
        
        <div class="container">
            <!-- Search Form -->
            <form id="search-form" class="search-form" onsubmit="return false;">
    <div class="form-group" style="position: relative;"> <!-- Set position relative here -->
        <input type="text" id="search-input" name="search" placeholder="Search by name or email" value="<?= htmlspecialchars($search_query); ?>" oninput="fetchSuggestions()" autocomplete="off">
        <input type="hidden" id="homeowner_id" name="homeowner_id">
        <!-- Search button -->
    <button type="button" onclick="submitSearch()">Search</button>
        <!-- Suggestions box -->
        <div id="suggestions" class="suggestions"></div>
    </div>
    
  
</form>


            <!-- Sort by Date Dropdown -->
            <form method="GET" action="homeowneradmin.php" class="sort-form">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search_query); ?>">
                <select name="sort" onchange="this.form.submit()">
                    <option value="desc" <?= $sort_order === 'DESC' ? 'selected' : '' ?>>Newest First</option>
                    <option value="asc" <?= $sort_order === 'ASC' ? 'selected' : '' ?>>Oldest First</option>
                </select>
            </form>
<br> <br>
            <!-- Display message if no homeowners are found -->
            <?php if (!empty($_SESSION['message'])): ?>
                <p class="<?= $_SESSION['message']['status'] ?>">
                    <?= htmlspecialchars($_SESSION['message']['message']) ?>
                </p>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>

            <?php if ($result_homeowners->num_rows > 0): ?>
                <table class="table">
                <thead>
                    <tr>
                        <th>Homeowner Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Square Meters</th> <!-- New Column -->
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Loop through homeowners and display their info -->
                    <?php while ($row = $result_homeowners->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['address']); ?></td>
                            <td><?php echo htmlspecialchars($row['sqm']); ?></td> <!-- New Column -->
                            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                            <td>
                                <button class="btn btn-primary btn-sm" onclick="window.location.href='edit.php?id=<?= $row['id']; ?>'">Edit</button>
                                <form method="POST" action="homeowneradmin.php" style="display:inline;">
                                    <input type="hidden" name="homeowner_id" value="<?= $row['id']; ?>">
                                    <input type="hidden" name="new_status" value="archived">
                                    <button class="btn btn-primary btn-sm archive-btn" type="submit">Archive</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
                </table>

                <!-- Pagination controls -->
                <div id="pagination">
                    <?php
                    $total_pages = max($total_pages, 1); // Ensure there's at least 1 page
                    $input_page = $current_page; // Default to the current page for the input

                    // Previous button
                    if ($current_page > 1): ?>
                        <form method="GET" action="homeowneradmin.php" style="display: inline;">
                            <input type="hidden" name="search" value="<?= htmlspecialchars($search_query); ?>">
                            <input type="hidden" name="sort" value="<?= $sort_order; ?>">
                            <input type="hidden" name="page" value="<?= $current_page - 1 ?>">
                            <button type="submit">&lt;</button>
                        </form>
                    <?php endif; ?>

                    <!-- Page input for user to change the page -->
                    <form method="GET" action="homeowneradmin.php" style="display: inline;">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search_query); ?>">
                        <input type="hidden" name="sort" value="<?= $sort_order; ?>">
                        <input type="number" name="page" value="<?= $input_page ?>" min="1" max="<?= $total_pages ?>" style="width: 50px;">
                    </form>

                    <!-- "of" text and last page link -->
                    <?php if ($total_pages > 1): ?>
                        <span>of <?= $total_pages ?></span>
                    <?php endif; ?>

                    <!-- Next button -->
                    <?php if ($current_page < $total_pages): ?>
                        <form method="GET" action="homeowneradmin.php" style="display: inline;">
                            <input type="hidden" name="search" value="<?= htmlspecialchars($search_query); ?>">
                            <input type="hidden" name="sort" value="<?= $sort_order; ?>">
                            <input type="hidden" name="page" value="<?= $current_page + 1 ?>">
                            <button type="submit">&gt;</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p>No homeowners found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const searchInput = document.getElementById('search-input');
        const suggestionsBox = document.getElementById('suggestions');

        // Fetch suggestions based on input
        window.fetchSuggestions = function() {
            const query = searchInput.value;
            suggestionsBox.innerHTML = ''; // Clear previous suggestions

            if (query.length >= 1) {
                fetch(`search_suggestions.php?search=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(suggestions => {
                        if (suggestions.length > 0) {
                            suggestions.forEach(suggestion => {
                                const suggestionItem = document.createElement('div');
                                suggestionItem.classList.add('suggestion-item');
                                suggestionItem.textContent = `${suggestion.name} (${suggestion.email})`;
                                suggestionItem.onclick = function() {
                                    searchInput.value = suggestion.name; // Set input value to the suggestion
                                    suggestionsBox.innerHTML = ''; // Clear suggestions
                                    suggestionsBox.style.display = 'none'; // Hide suggestions
                                };
                                suggestionsBox.appendChild(suggestionItem);
                            });
                            suggestionsBox.style.display = 'block'; // Show suggestions
                        } else {
                            suggestionsBox.style.display = 'none'; // Hide if no suggestions
                        }
                    })
                    .catch(error => console.error('Error fetching suggestions:', error));
            } else {
                suggestionsBox.style.display = 'none'; // Hide suggestions if input is empty
            }
        };

        // Submit search form
        window.submitSearch = function() {
            const query = searchInput.value;
            if (query) {
                // Redirect to the search results page with the query
                window.location.href = `homeowneradmin.php?search=${encodeURIComponent(query)}`;
            }
        };
    });
</script>

</html>
