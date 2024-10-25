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
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Pagination settings
$limit = 3; // Number of service requests per page
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($current_page - 1) * $limit;

// Fetch total number of service requests
$sql_count = "SELECT COUNT(*) as total FROM serreq WHERE homeowner_id = ?";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->bind_param("i", $homeowner_id);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$row_count = $result_count->fetch_assoc();
$total_service_requests = $row_count['total'];
$total_pages = max(ceil($total_service_requests / $limit), 1);

// Fetch service requests with pagination
$sql_requests = "SELECT * FROM serreq WHERE homeowner_id = ? LIMIT ?, ?";
$stmt_requests = $conn->prepare($sql_requests);
$stmt_requests->bind_param("iii", $homeowner_id, $offset, $limit);
$stmt_requests->execute();
$result_requests = $stmt_requests->get_result();
$service_requests = $result_requests->fetch_all(MYSQLI_ASSOC);

// Handle search query
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Handle cancellation
if (isset($_POST['cancel_request_id'])) {
    $request_id_to_cancel = $_POST['cancel_request_id'];

    // Prepare cancellation query
    $sql_cancel = "DELETE FROM serreq WHERE service_req_id = ? AND homeowner_id = ?";
    $stmt_cancel = $conn->prepare($sql_cancel);
    $stmt_cancel->bind_param("ii", $request_id_to_cancel, $homeowner_id);
    $stmt_cancel->execute();

    // Redirect to avoid resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Your Service Requests</title>
    <link rel="stylesheet" href="usersidebar.css">
    <link rel="stylesheet" href="view_service_requests.css">
</head>
<body>
<?php include 'usersidebar.php'; ?>

<div class="main-content">
<div class="container">
        <h2>Your Service Requests</h2>
        
        <?php if (count($service_requests) > 0): ?>
            <?php foreach ($service_requests as $request): ?>
                <div class="service-request-card">
                    <div class="service-request-item">
                        <span class="service-request-label">Details:</span>
                        <span class="service-request-value"><?php echo htmlspecialchars($request['details']); ?></span>
                    </div>
                    <div class="service-request-item">
                        <span class="service-request-label">Urgency:</span>
                        <span class="service-request-value"><?php echo htmlspecialchars($request['urgency']); ?></span>
                    </div>
                    <div class="service-request-item">
                        <span class="service-request-label">Type:</span>
                        <span class="service-request-value"><?php echo htmlspecialchars($request['type']); ?></span>
                    </div>
                    <div class="service-request-item">
                        <span class="service-request-label">Status:</span>
                        <span class="service-request-value"><?php echo htmlspecialchars($request['status']); ?></span>
                    </div>
                    <form method="POST" class="cancel-form">
                        <input type="hidden" name="cancel_request_id" value="<?php echo htmlspecialchars($request['service_req_id']); ?>">
                        <button type="submit" class="cancel-btn" onclick="return confirm('Are you sure you want to cancel this request?');">Cancel</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No service requests found.</p>
        <?php endif; ?>

        <div class="submit-service-request">
            <a href="serviceuser.php" class="submit-link">Submit a Service Request</a>
        </div>
    </div>


            <!-- Pagination controls -->
            <div id="pagination">
                <?php if ($current_page > 1): ?>
                    <form method="GET" action="view_service_requests.php" style="display: inline;">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search_query); ?>">
                        <input type="hidden" name="page" value="<?php echo $current_page - 1; ?>">
                        <button type="submit">Previous</button>
                    </form>
                <?php endif; ?>


                <form method="GET" action="view_service_requests.php" style="display: inline;">
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search_query); ?>">
                    <input type="number" name="page" value="<?= $current_page ?>" min="1" max="<?= $total_pages ?>" style="width: 50px; text-align: center;">
                </form>

                <?php if ($current_page < $total_pages): ?>
                    <form method="GET" action="view_service_requests.php" style="display: inline;">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search_query); ?>">
                        <input type="hidden" name="page" value="<?php echo $current_page + 1; ?>">
                        <button type="submit">Next</button>
                    </form>
                <?php endif; ?>

                <!-- Last page link -->
                <?php if ($total_pages > 1): ?>
                    <span>of</span>
                    <a href="?search=<?= urlencode($search_query); ?>&page=<?= $total_pages ?>" class="<?= ($current_page == $total_pages) ? 'active' : '' ?>"><?= $total_pages ?></a>
                <?php endif; ?>
            </div>
    </div>
</div>
</body>
</html>
