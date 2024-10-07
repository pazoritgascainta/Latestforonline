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
$username = "root";
$password = "";
$dbname = "homeowner";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to sanitize input data
function sanitize_input($data) {
    global $conn;
    return $conn->real_escape_string(trim(htmlspecialchars($data)));
}

// Pagination settings
$limit = 10; // Number of complaints per page
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($current_page - 1) * $limit;

// Fetch total number of complaints
$sql_count = "SELECT COUNT(*) as total FROM complaints WHERE homeowner_id = ?";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->bind_param("i", $homeowner_id);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$row_count = $result_count->fetch_assoc();
$total_complaints = $row_count['total'];
$total_pages = max(ceil($total_complaints / $limit), 1);

// Fetch complaints with pagination
$sql_complaints = "SELECT * FROM complaints WHERE homeowner_id = ? LIMIT ?, ?";
$stmt_complaints = $conn->prepare($sql_complaints);
$stmt_complaints->bind_param("iii", $homeowner_id, $offset, $limit);
$stmt_complaints->execute();
$result_complaints = $stmt_complaints->get_result();
$complaints = $result_complaints->fetch_all(MYSQLI_ASSOC);

if (isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];

    // Prepare and execute the delete query
    $delete_query = "DELETE FROM complaints WHERE complaint_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $delete_id);

    if ($stmt->execute()) {
        echo "<script>alert('Complaint deleted successfully.'); window.location.href='view_complaints.php';</script>";
    } else {
        echo "<script>alert('Failed to delete the complaint.');</script>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Complaints</title>

    <link rel="stylesheet" href="view_complaints.css">
</head>
<body>
<?php include 'usersidebar.php'; ?>

<div class="main-content">
    <div class="container">
        <h2>View Your Complaints</h2>

        <?php if (count($complaints) > 0) : ?>
            <table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Subject</th>
            <th>Description</th>
            <th>Status</th>
            <th>Created At</th>
            <th>Updated At</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($complaints as $complaint): ?>
            <tr>
                <td><?php echo htmlspecialchars($complaint['complaint_id']); ?></td>
                <td><?php echo htmlspecialchars($complaint['subject']); ?></td>
                <td><?php echo htmlspecialchars($complaint['description']); ?></td>
                <td><?php echo htmlspecialchars($complaint['status']); ?></td>
                <td><?php echo htmlspecialchars($complaint['created_at']); ?></td>
                <td><?php echo htmlspecialchars($complaint['updated_at']); ?></td>
                <td>
                    <form method="POST" action="view_complaints.php" class="delete-form" style="display:inline; margin-left:10px;">
                        <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($complaint['complaint_id']); ?>">
                        <button type="button" onclick="confirmDelete(event, this)" class="cancel-btn">Cancel</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>


          <!-- Pagination controls -->
<div id="pagination">
    <?php
    $total_pages = max($total_pages, 1); // Ensure there's at least 1 page
    $input_page = $current_page; // Default to the current page for the input

    // Previous button
    if ($current_page > 1): ?>
        <form method="GET" action="admincomplaint.php" style="display: inline;">
            <input type="hidden" name="search" value="<?= htmlspecialchars($search_query); ?>">
            <input type="hidden" name="page" value="<?= $current_page - 1 ?>">
            <button type="submit">Previous</button>
        </form>
    <?php endif; ?>

    <!-- Page input for user to change the page -->
    <form method="GET" action="admincomplaint.php" style="display: inline;">
        <input type="hidden" name="search" value="<?= htmlspecialchars($search_query); ?>">
        <input type="number" name="page" value="<?= $input_page ?>" min="1" max="<?= $total_pages ?>" style="width: 50px;">
    </form>

    <!-- "of" text and last page link -->
    <?php if ($total_pages > 1): ?>
        <span>of</span>
        <a href="?search=<?= urlencode($search_query); ?>&page=<?= $total_pages ?>" class="<?= ($current_page == $total_pages) ? 'active' : '' ?>"><?= $total_pages ?></a>
    <?php endif; ?>

    <!-- Next button -->
    <?php if ($current_page < $total_pages): ?>
        <form method="GET" action="admincomplaint.php" style="display: inline;">
            <input type="hidden" name="search" value="<?= htmlspecialchars($search_query); ?>">
            <input type="hidden" name="page" value="<?= $current_page + 1 ?>">
            <button type="submit">Next</button>
        </form>
    <?php endif; ?>
</div>

<?php else: ?>
    <p>No complaints found.</p>
<?php endif; ?>


        <a href="usercomplaint.php">Submit a Complaint</a>
    </div>
</div>
<script>
function confirmDelete(event, link) {
    event.preventDefault(); // Prevent the default link behavior

    var confirmation = confirm('Are you sure you want to delete this complaint?');
    if (confirmation) {
        var form = link.closest('form');
        form.submit(); // Submit the form if confirmed
    }
}
</script>
</body>
</html>
