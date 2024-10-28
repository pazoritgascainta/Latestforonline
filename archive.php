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

// Handle homeowner restoration or deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['homeowner_id']) && isset($_POST['action'])) {
    $homeowner_id = intval($_POST['homeowner_id']);
    $action = $_POST['action'];

    // Restore homeowner
    if ($action == 'restore') {
        $sql_update_status = "UPDATE homeowners SET status = 'active' WHERE id = ?";
    } elseif ($action == 'delete') {
        $sql_update_status = "DELETE FROM homeowners WHERE id = ?";
    }

    if (isset($sql_update_status)) {
        $stmt_update = $conn->prepare($sql_update_status);
        if ($stmt_update) {
            $stmt_update->bind_param("i", $homeowner_id);
            if ($stmt_update->execute()) {
                $status_message = ($action == 'restore') ? "Homeowner restored successfully!" : "Homeowner deleted successfully!";
            } else {
                $status_message = "Failed to update homeowner status: " . $stmt_update->error;
            }
            $stmt_update->close();
        } else {
            $status_message = "Prepare statement failed: " . $conn->error;
        }
    }

    $_SESSION['message'] = ['status' => 'success', 'message' => $status_message];
    header('Location: archive.php');
    exit();
}

// Handle search and sort inputs
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$search_query = $conn->real_escape_string($search_query);
$sort_order = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Define the sorting order for SQL
$order_by = ($sort_order == 'oldest') ? "ASC" : "DESC";

// Pagination settings
$records_per_page = 5;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

// Fetch archived homeowners with search, pagination, and sorting
$sql_archived_homeowners = "
    SELECT id, name, email, phone_number, address, created_at, status, sqm 
    FROM homeowners 
    WHERE status = 'archived'
    AND (name LIKE '%$search_query%' OR email LIKE '%$search_query%')
    ORDER BY created_at $order_by
    LIMIT $records_per_page OFFSET $offset
";
$result_archived_homeowners = $conn->query($sql_archived_homeowners);

// Get total archived homeowners count
$sql_total_archived = "
    SELECT COUNT(*) AS total 
    FROM homeowners 
    WHERE status = 'archived'
    AND (name LIKE '%$search_query%' OR email LIKE '%$search_query%')
";
$result_total_archived = $conn->query($sql_total_archived);
$total_archived_homeowners = $result_total_archived->fetch_assoc()['total'];

// Calculate total pages
$total_pages = ceil($total_archived_homeowners / $records_per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Homeowners</title>
    <link rel="stylesheet" href="homeownercss.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h2>Archived Homeowners</h2>
        <a class="btn btn-primary" href="homeowneradmin.php" role="button">Back to Homeowners</a>
        <div class="container">

            <!-- Search Form -->
       <!-- Search Form -->
<form method="GET" action="archive.php" class="search-form" style="display: inline;">
    <input type="text" name="search" placeholder="Search by name or email" value="<?= htmlspecialchars($search_query); ?>">
    <button type="submit">Search</button>
</form>

<!-- Sort Form -->
<form method="GET" action="archive.php" class="sort-form" style="display: inline;">
    <input type="hidden" name="search" value="<?= htmlspecialchars($search_query); ?>">
    <select name="sort" onchange="this.form.submit()">
        <option value="newest" <?= $sort_order == 'newest' ? 'selected' : '' ?>>Newest</option>
        <option value="oldest" <?= $sort_order == 'oldest' ? 'selected' : '' ?>>Oldest</option>
    </select>
</form>


            <!-- Display status message -->
            <?php if (!empty($_SESSION['message'])): ?>
                <p class="<?= $_SESSION['message']['status'] ?>">
                    <?= htmlspecialchars($_SESSION['message']['message']) ?>
                </p>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>

            <?php if ($result_archived_homeowners->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Square Meters</th> <!-- Added sqm column -->
                            <th>Created At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result_archived_homeowners->fetch_assoc()): ?>
                            <tr>
                      
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['address']); ?></td>
                                <td><?php echo htmlspecialchars($row['sqm']); ?></td> <!-- Display sqm value -->
                                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                <td>
                                    <form method="POST" action="archive.php" style="display: inline;">
                                        <input type="hidden" name="homeowner_id" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="action" value="restore">
                                        <button class="btn btn-success" type="submit">Restore</button>
                                    </form>
                                    <form method="POST" action="archive.php" style="display: inline;">
                                        <input type="hidden" name="homeowner_id" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button class="btn btn-danger" type="submit" onclick="return confirm('Are you sure you want to delete this homeowner?');">Delete</button>
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
                        <form method="GET" action="archive.php" style="display: inline;">
                            <input type="hidden" name="page" value="<?= $current_page - 1 ?>">
                            <button type="submit">&lt;</button>
                        </form>
                    <?php endif; ?>

                    <!-- Page input for user to change the page -->
                    <form method="GET" action="archive.php" style="display: inline;">
                        <input type="number" name="page" value="<?= $input_page ?>" min="1" max="<?= $total_pages ?>" style="width: 50px;">
                    </form>

                    <!-- "of" text and last page link -->
                    <?php if ($total_pages > 1): ?>
                        <span>of</span>
                        <a href="?page=<?= $total_pages ?>" class="<?= ($current_page == $total_pages) ? 'active' : '' ?>"><?= $total_pages ?></a>
                    <?php endif; ?>

                    <!-- Next button -->
                    <?php if ($current_page < $total_pages): ?>
                        <form method="GET" action="archive.php" style="display: inline;">
                            <input type="hidden" name="page" value="<?= $current_page + 1 ?>">
                            <button type="submit">&gt;</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p>No archived homeowners found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>
