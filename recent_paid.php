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


// Set pagination variables
$records_per_page = 10; // Number of records to display per page
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Get current page from query string
$offset = ($current_page - 1) * $records_per_page;

// Fetch total number of records for pagination
$total_result = $conn->query("SELECT COUNT(*) as total FROM billing WHERE status = 'Paid'");
$total_row = $total_result->fetch_assoc();
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $records_per_page); // Calculate total pages

// Fetching paid records with pagination
function fetchPaidRecords($conn, $offset, $records_per_page) {
    $sql = "SELECT b.billing_id, b.homeowner_id, h.name AS homeowner_name, h.address, 
            b.monthly_due, b.billing_date, b.due_date, b.total_amount, b.paid_date, 
            MONTH(b.paid_date) AS payment_month, YEAR(b.paid_date) AS payment_year
            FROM billing b 
            JOIN homeowners h ON b.homeowner_id = h.id 
            WHERE b.status = 'Paid' 
            ORDER BY b.paid_date DESC 
            LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $offset, $records_per_page);
    $stmt->execute();
    return $stmt->get_result();
}

$result_paid = fetchPaidRecords($conn, $offset, $records_per_page);
 
// Handle payment form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['payment_submit'])) {
    // Extract values from the form
    $homeowner_id = intval($_POST['homeowner_id']); // Assuming you have this in the form
    $monthly_due = floatval($_POST['monthly_due']); // Monthly due amount
    $billing_date = $_POST['billing_date']; // Billing date
    $due_date = $_POST['due_date']; // Due date

    // Record the payment
    try {
        recordPayment($conn, $homeowner_id, $monthly_due, $billing_date, $due_date);
        $_SESSION['message'] = "Payment recorded successfully!";
    } catch (Exception $e) {
        $_SESSION['message'] = $e->getMessage();
    }

    header("Location: billingadmin.php"); // Redirect after recording payment
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Records</title>
    <link rel="stylesheet" href="dashbcss.css">
    <link rel="stylesheet" href="recordingadmin.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container">
            <section>
                <h2>Recently Paid</h2>
                <button onclick="history.back()" class="back-button">Go Back</button>
                <br>
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert">
                        <?php echo htmlspecialchars($_SESSION['message']); ?>
                        <?php unset($_SESSION['message']); // Clear message after displaying ?>
                    </div>
                <?php endif; ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Homeowner Name</th>
                            <th>Address</th>
                            <th>Monthly Due</th>
                            <th>Billing Date</th>
                            <th>Due Date</th>
                            <th>Total Amount</th>
                            <th>Paid Date</th>
                            <th>Payment Month</th>
                            <th>Payment Year</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result_paid->num_rows > 0): ?>
                        <?php while ($row = $result_paid->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['homeowner_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['address']); ?></td>
                                <td><?php echo number_format($row['monthly_due'], 2); ?></td>
                                <td><?php echo htmlspecialchars($row['billing_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                                <td><?php echo number_format($row['total_amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($row['paid_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['payment_month']); ?></td>
                                <td><?php echo htmlspecialchars($row['payment_year']); ?></td>
                                <td>
                                    <a href="input_billing.php?homeowner_id=<?php echo urlencode($row['homeowner_id']); ?>" class="btn">View</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="12">No paid records found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>

                <!-- Pagination controls -->
                <div id="pagination">
                    <?php if ($current_page > 1): ?>
                        <form method="GET" action="recent_paid.php" style="display: inline;">
                            <input type="hidden" name="search" value="<?= htmlspecialchars($search_query ?? ''); ?>">
                            <input type="hidden" name="page" value="<?= $current_page - 1 ?>">
                            <button type="submit">&lt;</button>
                        </form>
                    <?php endif; ?>

                    <!-- Page input for user to change the page -->
                    <form method="GET" action="recent_paid.php" style="display: inline;">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search_query ?? ''); ?>">
                        <input type="number" name="page" value="<?= $current_page ?>" min="1" max="<?= $total_pages ?>" style="width: 50px;">
                    </form>

                    <!-- "of" text and last page link -->
                    <?php if ($total_pages > 1): ?>
                        <span>of</span>
                        <a href="?search=<?= urlencode($search_query ?? ''); ?>&page=<?= $total_pages ?>" class="<?= ($current_page == $total_pages) ? 'active' : '' ?>"><?= $total_pages ?></a>
                    <?php endif; ?>

                    <!-- Next button -->
                    <?php if ($current_page < $total_pages): ?>
                        <form method="GET" action="recent_paid.php" style="display: inline;">
                            <input type="hidden" name="search" value="<?= htmlspecialchars($search_query ?? ''); ?>">
                            <input type="hidden" name="page" value="<?= $current_page + 1 ?>">
                            <button type="submit">&gt;</button>
                        </form>
                    <?php endif; ?>

                    <span> of <?= $total_pages ?></span>
                </div>
            </section>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>
