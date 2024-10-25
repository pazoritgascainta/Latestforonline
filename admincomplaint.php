<?php
session_name('admin_session'); // Set a unique session name for admins
session_start();
$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle search input for homeowner_id
$search_query = "";
if (isset($_GET['search'])) {
    $search_query = trim($_GET['search']);
}

// Handle delete request
if (isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];

    // Prepare and execute the delete query
    $delete_query = "DELETE FROM complaints WHERE complaint_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $delete_id);

    if ($stmt->execute()) {
        echo "<script>alert('Complaint deleted successfully.'); window.location.href='admincomplaint.php';</script>";
    } else {
        echo "<script>alert('Failed to delete the complaint.');</script>";
    }

    $stmt->close();
}

// Check if any complaints have been edited, and delete all complaints if so
if (isset($_POST['edit_action'])) {
    // Assuming this action is triggered when a complaint is edited
    $delete_all_query = "DELETE FROM complaints";
    $conn->query($delete_all_query);
}

// Get sort option
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'updated_at';
$order = ($sort_by === 'created_at') ? 'created_at' : 'updated_at';

// Validate sort option
$valid_sort_options = ['created_at', 'updated_at'];
if (!in_array($sort_by, $valid_sort_options)) {
    $sort_by = 'updated_at';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Complaints</title>
    <link rel="stylesheet" href="admincomplaint.css">
    <style>
        .loader {
            display: none;
            border: 4px solid #f3f3f3; /* Light grey */
            border-top: 4px solid #3498db; /* Blue */
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>
<div class="main-content">
    <h1>Admin Complaints</h1>
    <div class="container">

        <!-- Search and Sort Form -->
 <!-- Search Form -->
<form method="GET" action="admincomplaint.php" class="search-form">
    <input type="number" name="search" value="<?= htmlspecialchars($search_query) ?>" placeholder="Search by Homeowner ID...">
    <button type="submit">Search</button>
</form>

<!-- Sort Form -->
<form method="GET" action="admincomplaint.php" class="sort-form" style="display:inline;">
    <select name="sort_by" onchange="this.form.submit()">
        <option value="updated_at" <?= $sort_by === 'updated_at' ? 'selected' : '' ?>>Sort by Updated Date</option>
        <option value="created_at" <?= $sort_by === 'created_at' ? 'selected' : '' ?>>Sort by Created Date</option>
    </select>
    <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>"> <!-- Keep the search query when sorting -->
</form>


        <table class="table">
            <thead>
                <tr>
                    <th>Homeowner Name</th>
                    <th>Subject</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Pagination settings
                $results_per_page = 10; // Number of results per page

                // Adjust total count query to handle search
                $query_total = "SELECT COUNT(*) AS total FROM complaints WHERE homeowner_id LIKE '%$search_query%'";
                $result_total = mysqli_query($conn, $query_total);
                $row_total = mysqli_fetch_assoc($result_total);
                $total_results = $row_total['total'];
                $total_pages = ceil($total_results / $results_per_page);

                // Get current page from URL or default to 1
                $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $current_page = max($current_page, 1); // Ensure current page is at least 1
                $current_page = min($current_page, $total_pages); // Ensure current page does not exceed total pages

                // Calculate the offset for the query
                $offset = ($current_page - 1) * $results_per_page;

                // Query to fetch complaints filtered by homeowner_id with pagination and sorting by status
                $query = "
                SELECT complaints.*, homeowners.name 
                FROM complaints 
                JOIN homeowners ON complaints.homeowner_id = homeowners.id
                WHERE complaints.homeowner_id LIKE '%$search_query%' 
                ORDER BY 
                    CASE 
                        WHEN complaints.status = 'Pending' THEN 1
                        WHEN complaints.status = 'In Progress' THEN 2
                        WHEN complaints.status = 'Resolved' THEN 3
                        ELSE 4
                    END,
                    $order DESC
                LIMIT ?, ?";
                

                $stmt = $conn->prepare($query);
                $stmt->bind_param("ii", $offset, $results_per_page);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['name']) . "</td>"; // Display the homeowner name
                        echo "<td>" . htmlspecialchars($row['subject']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                
                        // Set status color
                        $status_color = "";
                        if ($row['status'] === 'In Progress') {
                            $status_color = "style='color: red;'";
                        } elseif ($row['status'] === 'Resolved') {
                            $status_color = "style='color: green;'";
                        } elseif ($row['status'] === 'Pending') {
                            $status_color = "style='color: orange;'";
                        }
                
                        echo "<td $status_color>" . htmlspecialchars($row['status']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['updated_at']) . "</td>";
                        echo "<td>";
                        echo "<a class='btn btn-edit' href='admin_view_complaints.php?id=" . htmlspecialchars($row['complaint_id']) . "'>View</a>";
                        echo "<form method='POST' action='admincomplaint.php' class='delete-form' style='display:inline; margin-left:10px;'>";
                
                        // Add loader element
                        echo "<input type='hidden' name='delete_id' value='" . htmlspecialchars($row['complaint_id']) . "'>";
                        echo "<a href='#' onclick='confirmDelete(event, this)' class='btn'>Delete</a>";
                        echo "<div class='loader' id='loader-". htmlspecialchars($row['complaint_id']) ."'></div>";
                        echo "</form>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>No complaints found for this Homeowner ID.</td></tr>";
                }
                
                ?>
            </tbody>
        </table>

        <!-- Pagination controls -->
        <div id="pagination">
            <?php if ($total_pages > 1): ?>
                <!-- Previous button -->
                <?php if ($current_page > 1): ?>
                    <form method="GET" action="admincomplaint.php" style="display: inline;">
                        <input type="hidden" name="page" value="<?= $current_page - 1 ?>">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
                        <input type="hidden" name="sort_by" value="<?= htmlspecialchars($sort_by) ?>">
                        <button type="submit" class="btn">&lt;</button>
                    </form>
                <?php endif; ?>

                <form method="GET" action="admincomplaint.php" style="display: inline;">
                    <input type="number" name="page" value="<?= $current_page ?>" min="1" max="<?= $total_pages ?>" class="pagination-input">
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
                    <input type="hidden" name="sort_by" value="<?= htmlspecialchars($sort_by) ?>">
                </form>

                <span>of</span>
                <a href="admincomplaint.php?page=<?= $total_pages ?>&search=<?= htmlspecialchars($search_query) ?>&sort_by=<?= htmlspecialchars($sort_by) ?>" class="page-link <?= ($current_page == $total_pages) ? 'disabled' : '' ?>"><?= $total_pages ?></a>

                <!-- Next button -->
                <?php if ($current_page < $total_pages): ?>
                    <form method="GET" action="admincomplaint.php" style="display: inline;">
                        <input type="hidden" name="page" value="<?= $current_page + 1 ?>">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search_query) ?>">
                        <input type="hidden" name="sort_by" value="<?= htmlspecialchars($sort_by) ?>">
                        <button type="submit" class="btn">&gt;</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function confirmDelete(event, element) {
    event.preventDefault(); // Prevent the default anchor behavior
    var loader = element.parentElement.querySelector('.loader');
    if (confirm('Are you sure you want to delete this complaint?')) {
        loader.style.display = 'block'; // Show the loader
        element.parentElement.submit(); // Submit the form to delete the complaint
    }
}
</script>
</body>
</html>
