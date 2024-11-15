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

// Determine which complaints to display: active or archived
$view = isset($_GET['view']) ? $_GET['view'] : 'active';
$is_archived_condition = ($view === 'archived') ? 1 : 0;

// Handle complaint archiving
if (isset($_POST['archive_id'])) {
    $archive_id = $_POST['archive_id'];

    // Prepare and execute the archive query
    $archive_query = "UPDATE complaints SET is_archived = 1 WHERE complaint_id = ?";
    $stmt = $conn->prepare($archive_query);
    $stmt->bind_param("i", $archive_id);

    if ($stmt->execute()) {
        $redirect_view = ($view === 'archived') ? 'archived' : 'active';
        echo "<script>alert('Complaint archived successfully.'); window.location.href='admincomplaint.php?view=$redirect_view';</script>";
    } else {
        echo "<script>alert('Failed to archive the complaint.');</script>";
    }

    $stmt->close();
}

// Handle complaint restoring
if (isset($_POST['restore_id'])) {
    $restore_id = $_POST['restore_id'];

    // Prepare and execute the restore query
    $restore_query = "UPDATE complaints SET is_archived = 0 WHERE complaint_id = ?";
    $stmt = $conn->prepare($restore_query);
    $stmt->bind_param("i", $restore_id);

    if ($stmt->execute()) {
        echo "<script>alert('Complaint restored successfully.'); window.location.href='admincomplaint.php?view=archived';</script>";
    } else {
        echo "<script>alert('Failed to restore the complaint.');</script>";
    }

    $stmt->close();
}

// Get sort option
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'updated_at';
$order = ($sort_by === 'created_at') ? 'created_at' : 'updated_at';

// Validate sort option
$valid_sort_options = ['created_at', 'updated_at'];
if (!in_array($sort_by, $valid_sort_options)) {
    $sort_by = 'updated_at';
}

// Pagination variables
$results_per_page = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $results_per_page;

// Query to fetch complaints based on active/archived view and search input
$query = "
    SELECT complaints.*, homeowners.name 
    FROM complaints 
    JOIN homeowners ON complaints.homeowner_id = homeowners.id
    WHERE homeowners.name LIKE ? AND complaints.is_archived = ?
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

$search_param = '%' . $search_query . '%';
$stmt->bind_param("siii", $search_param, $is_archived_condition, $offset, $results_per_page);
$stmt->execute();
$result = $stmt->get_result();

// Fetch complaints
$complaints = [];
while ($row = $result->fetch_assoc()) {
    $complaints[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Handle input in the search field
    $('input[name="search"]').on('input', function() {
        let searchQuery = $(this).val();

        if (searchQuery.length >= 1) {
            $.ajax({
                url: 'search_suggestions.php', // Ensure this points to your suggestions script
                method: 'GET',
                data: { search: searchQuery },
                success: function(data) {
                    let suggestions = $('#suggestions');
                    suggestions.empty(); // Clear previous suggestions
                    
                    if (data.length > 0) {
                        data.forEach(function(item) {
                            suggestions.append(`<div class="suggestion-item" data-homeowner-id="${item.id}">${item.name}</div>`);
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
    });

    // Event delegation for suggestions click
    $('#suggestions').on('click', '.suggestion-item', function() {
        $('input[name="search"]').val($(this).text()); // Set the input value to the selected suggestion
        $('#suggestions').empty(); // Clear suggestions after selection
    });
});
</script>

</head>
<body>

<?php include 'sidebar.php'; ?>
<div class="main-content">
    <h1>Admin Complaints</h1>
    <div class="container">

        <!-- Search and Sort Form -->
        <!-- Search Form -->
        <form method="GET" action="admincomplaint.php" class="search-form">
            <div class="form-group" style="position: relative;"> <!-- Set position relative here -->
                <input type="text" name="search" value="<?= htmlspecialchars($search_query) ?>" placeholder="Search by Homeowner Name..." autocomplete="off">
                <div id="suggestions" class="suggestions"></div> <!-- Suggestions will be placed here -->
            </div>
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
        <br> <br>

        <!-- View Archived and Active Complaints Links -->
        <a href="admincomplaint.php?view=archived" class="btn">View Archived Complaints</a>
        <a href="admincomplaint.php?view=active" class="btn">View Active Complaints</a>

        <table class="table">
            <thead>
                <tr>
                    <th>Homeowner Name</th>
                    <th>Subject</th>
                    <th>Description</th>
                    <th>Address</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Set view to 'active' by default
                $view = isset($_GET['view']) ? $_GET['view'] : 'active';

                // Pagination settings
                $results_per_page = 10; // Number of results per page

                // Adjust total count query to handle search and view filter
                $query_total = "
                SELECT COUNT(*) AS total 
                FROM complaints 
                JOIN homeowners ON complaints.homeowner_id = homeowners.id
                WHERE homeowners.name LIKE '%$search_query%'";

                // Add the 'view' condition to the query
                if ($view === 'archived') {
                    $query_total .= " AND complaints.is_archived = 1";
                } else {
                    $query_total .= " AND complaints.is_archived = 0";
                }

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
                SELECT complaints.*, homeowners.name ,homeowners.address
                FROM complaints 
                JOIN homeowners ON complaints.homeowner_id = homeowners.id
                WHERE homeowners.name LIKE '%$search_query%'";

                // Add the 'view' condition to the query
                if ($view === 'archived') {
                    $query .= " AND complaints.is_archived = 1";
                } else {
                    $query .= " AND complaints.is_archived = 0";
                }

                $query .= " ORDER BY 
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
                        echo "<td>" . htmlspecialchars($row['address']) . "</td>";


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

                        // Actions for Restore or Archive based on the view status
                        if ($view === 'archived') {
                            echo "<form method='POST' action='admincomplaint.php' style='display:inline;'>";
                            echo "<input type='hidden' name='restore_id' value='" . htmlspecialchars($row['complaint_id']) . "'>";
                            echo "<button type='submit' class='btn btn-restore'>Restore</button>";
                            echo "</form>";
                        } else {
                            echo "<form method='POST' action='admincomplaint.php' class='delete-form' style='display:inline; margin-left:10px;'>";
                            echo "<input type='hidden' name='archive_id' value='" . htmlspecialchars($row['complaint_id']) . "'>";
                            echo "<a href='#' onclick='confirmArchive(event, this)' class='btn'>Archive</a>";
                            echo "<div class='loader' id='loader-" . htmlspecialchars($row['complaint_id']) . "'></div>";
                            echo "</form>";
                        }

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
</body>


<script>
function confirmArchive(event, element) {
    event.preventDefault(); // Prevent the default anchor behavior
    var loader = element.parentElement.querySelector('.loader');
    if (confirm('Are you sure you want to archive this complaint?')) {
        loader.style.display = 'block'; // Show the loader
        element.parentElement.submit(); // Submit the form to archive the complaint
    }
}

</script>
</body>
</html>
