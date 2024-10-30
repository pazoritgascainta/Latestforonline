<?php
session_name('admin_session'); // Set a unique session name for admins
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Database connection
$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Pagination variables
$limit = 10; // Set the number of records per page
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $limit;

// Search query
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Sort order
$sort_order = isset($_GET['sort']) ? $_GET['sort'] : 'newest'; // Default sorting
$allowed_sort_orders = ['newest', 'oldest'];
if (!in_array($sort_order, $allowed_sort_orders)) {
    $sort_order = 'newest'; // Fallback to default if invalid
}

// Base SQL query
$sql = "
    SELECT 
        sr.service_req_id, 
        sr.homeowner_id, 
        sr.details, 
        sr.urgency, 
        sr.type, 
        sr.status,
        h.address,
        h.phone_number,
        h.name,
        h.email
    FROM 
        serreq sr
    JOIN 
        homeowners h ON sr.homeowner_id = h.id
";


// Add search condition to search by homeowner name
if (!empty($search_query)) {
    $sql .= " WHERE h.name LIKE ?"; // Searching by homeowner's name
}

// Prepare the statement
$stmt = $conn->prepare($sql);

// Bind the parameter if there is a search query
if (!empty($search_query)) {
    $search_term = '%' . $conn->real_escape_string($search_query) . '%';
    $stmt->bind_param('s', $search_term);
}

// Add sorting to the query
$order_by = ($sort_order === 'oldest') ? 'sr.created_at ASC' : 'sr.created_at DESC';
$sql .= " ORDER BY $order_by";

// Get the total number of records
$total_result = $stmt->execute();
$total_rows = $stmt->get_result()->num_rows;
$total_pages = ceil($total_rows / $limit);

// Add pagination to the query
$sql .= " LIMIT $limit OFFSET $offset";
$stmt = $conn->prepare($sql);
if (!empty($search_query)) {
    $stmt->bind_param('s', $search_term); // Bind the parameter for the actual query
}
$stmt->execute();
$result = $stmt->get_result();

// Store fetched data
$requests = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row; // Append each row to requests array
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Service Requests</title>
    <link rel="stylesheet" href="dashbcss.css">
    <link rel="stylesheet" href="Serviceadmin.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
 function fetchSuggestions() {
    let searchQuery = document.getElementById('search-input').value;
    
    if (searchQuery.length >= 1) {
        $.ajax({
            url: 'search_suggestions.php',
            method: 'GET',
            data: { search: searchQuery },
            success: function(data) {
                let suggestions = $('#suggestions');
                suggestions.empty(); // Clear previous suggestions
                
                if (data.length > 0) {
                    data.forEach(function(item) {
                        suggestions.append(`<div class="suggestion-item" data-email="${item.email}">${item.name}</div>`);
                    });
                }
            },
            error: function() {
                console.log('Error fetching suggestions');
            }
        });
    } else {
        $('#suggestions').empty(); // Clear suggestions if input is empty
    }
}

// Event delegation for suggestions click
$(document).on('click', '.suggestion-item', function() {
    $('#search-input').val($(this).text());
    $('#homeowner_id').val($(this).data('email')); // Set the hidden input value
    $('#suggestions').empty(); // Clear suggestions after selection
});

    </script>
</head>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <h1>St. Monique Service Requests</h1>

        <div class="container">


  <form id="search-form" class="search-form">
    <div class="form-group" style="position: relative;"> <!-- Set position relative here -->
        <input type="text" id="search-input" name="search" placeholder="Search by name or email" value="<?= htmlspecialchars($search_query); ?>" oninput="fetchSuggestions()">
        <input type="hidden" id="homeowner_id" name="homeowner_id">
        <div id="suggestions" class="suggestions"></div> <!-- Suggestions will be placed here -->
    </div>
    <button type="submit">Search</button> <!-- Search button added -->
</form>


            <!-- Sort Form -->
            <form method="GET" action="serviceadmin.php" class="sort-form">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
                <label for="sort">Sort by:</label>
                <select name="sort" id="sort" onchange="this.form.submit()">
                    <option value="newest" <?= ($sort_order === 'newest') ? 'selected' : '' ?>>Newest</option>
                    <option value="oldest" <?= ($sort_order === 'oldest') ? 'selected' : '' ?>>Oldest</option>
                </select>
            </form>

            <table id="requestsTable" class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone Number</th>
                        <th>Address</th>
                        <th>Details</th>
                        <th>Urgency</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($requests) > 0): ?>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['name']); ?></td>
                                <td><?php echo htmlspecialchars($request['email']); ?></td>
                                <td><?php echo htmlspecialchars($request['phone_number']); ?></td>
                                <td><?php echo htmlspecialchars($request['address']); ?></td>
                                <td><?php echo htmlspecialchars($request['details']); ?></td>
                                <td><?php echo htmlspecialchars($request['urgency']); ?></td>
                                <td><?php echo htmlspecialchars($request['type']); ?></td>
                                <td><?php echo htmlspecialchars($request['status']); ?></td>
                                <td>
                                    <form method="GET" action="view_admin_service.php" style="display:inline;">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($request['service_req_id']); ?>">
                                        <button type="submit" class="btn button-margin">View</button>
                                    </form>
                                    <form method="POST" action="delete_service.php" style="display:inline;">
                                        <input type="hidden" name="service_req_id" value="<?php echo htmlspecialchars($request['service_req_id']); ?>">
                                        <button type="submit" class="btn" onclick="return confirm('Are you sure you want to delete this request?');">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9">No service requests found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <div id="pagination">
                <?php if ($total_pages > 1): ?>
                    <!-- Previous button -->
                    <?php if ($current_page > 1): ?>
                        <form method="GET" action="serviceadmin.php" style="display: inline;">
                            <input type="hidden" name="page" value="<?= $current_page - 1 ?>">
                            <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
                            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort_order) ?>">
                            <button type="submit" class="btn">&lt;</button>
                        </form>
                    <?php endif; ?>

                    <!-- Page input for user to change the page -->
                    <form method="GET" action="serviceadmin.php" style="display: inline;">
                        <input type="number" name="page" value="<?= $current_page ?>" min="1" max="<?= $total_pages ?>" class="pagination-input">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
                        <input type="hidden" name="sort" value="<?= htmlspecialchars($sort_order) ?>">
                    </form>

                    <!-- "of" text and last page link -->
                    <?php if ($total_pages > 1): ?>
                        <span>of</span>
                        <a href="serviceadmin.php?page=<?= $total_pages ?>&search=<?= htmlspecialchars($search_query) ?>&sort=<?= htmlspecialchars($sort_order) ?>" class="page-link <?= ($current_page == $total_pages) ? 'active' : '' ?>"><?= $total_pages ?></a>
                    <?php endif; ?>

                    <!-- Next button -->
                    <?php if ($current_page < $total_pages): ?>
                        <form method="GET" action="serviceadmin.php" style="display: inline;">
                            <input type="hidden" name="page" value="<?= $current_page + 1 ?>">
                            <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
                            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort_order) ?>">
                            <button type="submit" class="btn">&gt;</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>
