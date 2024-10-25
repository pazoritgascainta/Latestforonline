<?php
session_name('admin_session'); 
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection variables
$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure admin_id is set in the session
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}
$admin_id = $_SESSION['admin_id'];

// Fetch the admin's current information
$sql = "SELECT * FROM admin WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    error_log("Failed to prepare SQL statement: " . $conn->error);
    return;
}

$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();
} else {
    error_log("Admin not found for ID " . $admin_id);
    return;
}

$stmt->close();

// Default profile image handling
$default_image = 'profile.png';
$profile_image = isset($admin['profile_image']) && !empty($admin['profile_image']) ? $admin['profile_image'] : $default_image;

// Pagination settings
$limit = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max($current_page, 1);

// Calculate offset
$offset = ($current_page - 1) * $limit;

// Search functionality
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';
if ($search_query) {
    $search_condition = "WHERE h.email LIKE '%" . $conn->real_escape_string($search_query) . "%'";
}

// Sort functionality
$sort_order = isset($_GET['sort']) && $_GET['sort'] === 'ASC' ? 'ASC' : 'DESC';

// Count total requests
$count_sql = "SELECT COUNT(*) as total 
              FROM password_reset_requests p 
              JOIN homeowners h ON p.homeowner_id = h.id 
              $search_condition";
$count_result = $conn->query($count_sql);
$total_requests = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_requests / $limit);

// Fetch requests with pagination, search, and sorting
$sql = "SELECT p.id, h.id AS homeowner_id, h.email, p.created_at 
        FROM password_reset_requests p 
        JOIN homeowners h ON p.homeowner_id = h.id 
        $search_condition
        ORDER BY p.created_at $sort_order
        LIMIT $limit OFFSET $offset";

$result = $conn->query($sql);

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Requests</title>
    <link rel="stylesheet" href="homeownercss.css">
</head>

<body>
    <div class="main-content">
        <h2>Password Reset Requests</h2>
        <br>
        
        <a class="btn btn-primary" href="homeowneradmin.php" role="button">Back to Homeowners</a>

        <div class="container">
            <!-- Search Form -->
            <form method="GET" action="pass_reqs.php" class="search-form">
                <input type="text" name="search" placeholder="Search by email" value="<?= htmlspecialchars($search_query); ?>">
                <button type="submit">Search</button>
            </form>

            <!-- Sort Form -->
            <form method="GET" action="pass_reqs.php" class="sort-form">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search_query); ?>">
                <label for="sort">Sort by Date:</label>
                <select name="sort" onchange="this.form.submit()">
                    <option value="DESC" <?= $sort_order === 'DESC' ? 'selected' : '' ?>>Newest First</option>
                    <option value="ASC" <?= $sort_order === 'ASC' ? 'selected' : '' ?>>Oldest First</option>
                </select>
            </form>

            <?php if ($result && $result->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Created At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                <td>
                                <button class="btn btn-primary btn-sm" onclick="window.location.href='edit.php?id=<?= $row['homeowner_id']; ?>'">Edit</button>

                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No password reset requests found.</p>
            <?php endif; ?>

            <!-- Pagination controls -->
            <div id="pagination">
                <?php
                $total_pages = max($total_pages, 1);
                $input_page = $current_page;

                if ($current_page > 1): ?>
                    <form method="GET" action="pass_reqs.php" style="display: inline;">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search_query); ?>">
                        <input type="hidden" name="page" value="<?= $current_page - 1 ?>">
                        <button type="submit"><</button>
                    </form>
                <?php endif; ?>

                <form method="GET" action="pass_reqs.php" style="display: inline;">
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search_query); ?>">
                    <input type="number" name="page" value="<?= $input_page ?>" min="1" max="<?= $total_pages ?>" style="width: 50px;">
                </form>

                <?php if ($total_pages > 1): ?>
                    <span>of</span>
                    <a href="?search=<?= urlencode($search_query); ?>&page=<?= $total_pages ?>" class="<?= ($current_page == $total_pages) ? 'active' : '' ?>"><?= $total_pages ?></a>
                <?php endif; ?>

                <?php if ($current_page < $total_pages): ?>
                    <form method="GET" action="pass_reqs.php" style="display: inline;">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search_query); ?>">
                        <input type="hidden" name="page" value="<?= $current_page + 1 ?>">
                        <button type="submit">></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include 'sidebar.php'; ?>
</body>
</html>
