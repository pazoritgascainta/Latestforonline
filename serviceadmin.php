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
        sr.is_archived,
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

// Handle archive action
if (isset($_POST['archive'])) {
    $service_req_id = (int)$_POST['service_req_id'];

    // Archive the request by updating the 'is_archived' field
    $archive_sql = "UPDATE serreq SET is_archived = 1 WHERE service_req_id = ?";
    $archive_stmt = $conn->prepare($archive_sql);
    $archive_stmt->bind_param('i', $service_req_id);
    if ($archive_stmt->execute()) {
        echo "Service request archived successfully!";
    } else {
        echo "Failed to archive service request.";
    }
}

// Handle restore action
if (isset($_POST['restore'])) {
    $service_req_id = (int)$_POST['service_req_id'];

    // Restore the request by updating the 'is_archived' field to 0
    $restore_sql = "UPDATE serreq SET is_archived = 0 WHERE service_req_id = ?";
    $restore_stmt = $conn->prepare($restore_sql);
    $restore_stmt->bind_param('i', $service_req_id);
    if ($restore_stmt->execute()) {
        echo "Service request restored successfully!";
    } else {
        echo "Failed to restore service request.";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Service Requests</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

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
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <h1>St. Monique Service Requests</h1>

        <div class="container">
            <form id="search-form" class="search-form">
                <div class="form-group" style="position: relative;">
                    <input type="text" id="search-input" name="search" placeholder="Search by name or email" value="<?= htmlspecialchars($search_query); ?>" oninput="fetchSuggestions()">
                    <input type="hidden" id="homeowner_id" name="homeowner_id">
                    <div id="suggestions" class="suggestions"></div>
                </div>
                <button type="submit">Search</button>
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

            <!-- Button to switch between active and archived service requests -->
            <form method="GET" action="serviceadmin.php" style="margin-top: 10px;">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort_order) ?>">
                <button type="submit" name="view" value="active" class="btn <?= (!isset($_GET['view']) || $_GET['view'] == 'active') ? 'active' : '' ?>">Active Requests</button>
                <button type="submit" name="view" value="archived" class="btn <?= (isset($_GET['view']) && $_GET['view'] == 'archived') ? 'active' : '' ?>">Archived Requests</button>
            </form>

            <!-- Table for Service Requests -->
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
                    <?php
                    // Determine if the user wants to see active or archived requests
                    $view = isset($_GET['view']) && $_GET['view'] == 'archived' ? 1 : 0;

                    // Pagination logic
                    $limit = 10; // Number of records per page
                    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                    $offset = ($page - 1) * $limit;

                    // Modify the query to filter based on the view (active or archived) and apply pagination
                    $sql = "
                        SELECT 
                            sr.service_req_id, 
                            sr.homeowner_id, 
                            sr.details, 
                            sr.urgency, 
                            sr.type, 
                            sr.status, 
                            sr.is_archived,
                            h.address,
                            h.phone_number,
                            h.name,
                            h.email
                        FROM 
                            serreq sr
                        JOIN 
                            homeowners h ON sr.homeowner_id = h.id
                        WHERE
                            sr.is_archived = ?
                        LIMIT ?, ?";

                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('iii', $view, $offset, $limit);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    // Display service requests
                    if ($result->num_rows > 0):
                        while ($request = $result->fetch_assoc()):
                    ?>
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
                            <?php if ($view == 0): // Active requests ?>
                                <form method="POST" action="serviceadmin.php" style="display:inline;">
                                    <input type="hidden" name="service_req_id" value="<?php echo htmlspecialchars($request['service_req_id']); ?>">
                                    <button type="submit" name="archive" class="btn" onclick="return confirm('Are you sure you want to archive this request?');">Archive</button>
                                </form>
                            <?php else: // Archived requests ?>
                                <form method="POST" action="serviceadmin.php" style="display:inline;">
                                    <input type="hidden" name="service_req_id" value="<?php echo htmlspecialchars($request['service_req_id']); ?>">
                                    <button type="submit" name="restore" class="btn" onclick="return confirm('Are you sure you want to restore this request?');">Restore</button>
                                </form>
                            <?php endif; ?>

                            <form method="GET" action="view_admin_service.php" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($request['service_req_id']); ?>">
                                <button type="submit" class="btn button-margin">View</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="9">No service requests found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

          <!-- Pagination controls -->
<div id="pagination">
    <?php if ($total_pages > 1): ?>
    

        <!-- Current page input -->
        <form method="GET" action="serviceadmin.php" style="display: inline;">
            <input type="number" name="page" value="<?= $current_page ?>" min="1" max="<?= $total_pages ?>" class="pagination-input">
            <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort_order) ?>">
        </form>

    

        <!-- Total pages link -->
       

    <?php endif; ?>
</div>

        </div>
    </div>
</body>
</html>
