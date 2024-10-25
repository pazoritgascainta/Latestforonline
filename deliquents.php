<?php
session_name('admin_session');
session_start();

$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Define how many results you want per page
$results_per_page = 10;

// Find out the number of results stored in the database
$sql = "SELECT COUNT(*) AS total FROM billing WHERE status = 'Overdue'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$total_results = $row['total'];

// Determine the total number of pages available
$total_pages = ceil($total_results / $results_per_page);

// Determine which page number visitor is currently on
if (!isset($_GET['page'])) {
    $current_page = 1;
} else {
    $current_page = (int)$_GET['page'];
}

// Determine the SQL LIMIT starting number for the results on the current page
$start_from = ($current_page - 1) * $results_per_page;

// Fetching overdue records with pagination
function fetchOverdueRecords($conn, $start_from, $results_per_page) {
    $sql = "SELECT billing_id, homeowner_id, total_amount, billing_date, due_date, status, monthly_due, paid_date
            FROM billing 
            WHERE status = 'Overdue'
            ORDER BY due_date DESC
            LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $start_from, $results_per_page);
    $stmt->execute();
    return $stmt->get_result();
}

$result_overdue = fetchOverdueRecords($conn, $start_from, $results_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overdue Billing Records</title>
    <link rel="stylesheet" href="dashbcss.css">
    <link rel="stylesheet" href="recordingadmin.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container">
            <section>
                <h2>Overdue Billing Records</h2>
                <button onclick="history.back()" class="back-button">Go Back</button>
                <br>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Total Amount</th>
                            <th>Billing Date</th>
                            <th>Due Date</th>
                            <th>Status</th> <!-- Status Column -->
                            <th>Monthly Due</th>
                            <th>Paid Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result_overdue->num_rows > 0): ?>
                        <?php while ($row = $result_overdue->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo number_format($row['total_amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($row['billing_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['status']); ?></td> <!-- Displaying Status -->
                                <td><?php echo number_format($row['monthly_due'], 2); ?></td>
                                <td><?php echo htmlspecialchars($row['paid_date']); ?></td>
                                <td>
                                    <a href="input_billing.php?homeowner_id=<?php echo urlencode($row['homeowner_id']); ?>" class="btn">View</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="9">No overdue records found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>

                <!-- Pagination controls -->
                <div id="pagination">
                    <?php
                    // Previous button
                    if ($current_page > 1): ?>
                        <form method="GET" action="deliquents.php" style="display: inline;">
                            <input type="hidden" name="page" value="<?= $current_page - 1 ?>">
                            <button type="submit">&lt;</button>
                        </form>
                    <?php endif; ?>

                    <!-- Page input for user to change the page -->
                    <form method="GET" action="deliquents.php" style="display: inline;">
                        <input type="number" name="page" value="<?= $current_page ?>" min="1" max="<?= $total_pages ?>" style="width: 50px;">
                    </form>

                    <!-- "of" text and last page link -->
                    <?php if ($total_pages > 1): ?>
                        <span>of</span>
                        <a href="?page=<?= $total_pages ?>" class="<?= ($current_page == $total_pages) ? 'active' : '' ?>"><?= $total_pages ?></a>
                    <?php endif; ?>

                    <!-- Next button -->
                    <?php if ($current_page < $total_pages): ?>
                        <form method="GET" action="deliquents.php" style="display: inline;">
                            <input type="hidden" name="page" value="<?= $current_page + 1 ?>">
                            <button type="submit">&gt;</button>
                        </form>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>
