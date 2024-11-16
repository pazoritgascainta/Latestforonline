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

// Get the homeowner_id from the URL
$homeowner_id = isset($_GET['homeowner_id']) ? intval($_GET['homeowner_id']) : 0;

// Fetch the homeowner's name using homeowner_id
$sql_homeowner = "SELECT name FROM homeowners WHERE id = ?";
$stmt_homeowner = $conn->prepare($sql_homeowner);
$stmt_homeowner->bind_param("i", $homeowner_id);
$stmt_homeowner->execute();
$result_homeowner = $stmt_homeowner->get_result();
$homeowner = $result_homeowner->fetch_assoc();

// Check if the homeowner exists
if ($homeowner) {
    $homeowner_name = htmlspecialchars($homeowner['name']);
} else {
    $homeowner_name = "Unknown Homeowner";
}

// Search by billing date if provided
$search_date = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination variables
$results_per_page = 10;
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$start_from = ($current_page - 1) * $results_per_page;

// Fetch total records for pagination from billing history
$sql_total_billing = "SELECT COUNT(*) AS total FROM billing_history WHERE homeowner_id = ?";
if (!empty($search_date)) {
    $sql_total_billing .= " AND billing_date = ?";
}

$stmt_total_billing = $conn->prepare($sql_total_billing);
if (!empty($search_date)) {
    $stmt_total_billing->bind_param("is", $homeowner_id, $search_date);
} else {
    $stmt_total_billing->bind_param("i", $homeowner_id);
}
$stmt_total_billing->execute();
$result_total_billing = $stmt_total_billing->get_result();
$total_records_billing = $result_total_billing->fetch_assoc()['total'];
$total_pages_billing = ceil($total_records_billing / $results_per_page);

// Fetch billing history records
$sql_billing = "SELECT history_id, monthly_due, billing_date, due_date, total_amount, paid_date
                FROM billing_history
                WHERE homeowner_id = ?" . (!empty($search_date) ? " AND billing_date = ?" : "") . "
                ORDER BY billing_date DESC
                LIMIT ?, ?";

$stmt_billing = $conn->prepare($sql_billing);
if (!empty($search_date)) {
    $stmt_billing->bind_param("isii", $homeowner_id, $search_date, $start_from, $results_per_page);
} else {
    $stmt_billing->bind_param("iii", $homeowner_id, $start_from, $results_per_page);
}
$stmt_billing->execute();
$result_billing = $stmt_billing->get_result();

// Fetch distinct billing dates for suggestions
$sql_dates = "SELECT DISTINCT billing_date FROM billing_history WHERE homeowner_id = ?";
$stmt_dates = $conn->prepare($sql_dates);
$stmt_dates->bind_param("i", $homeowner_id);
$stmt_dates->execute();
$result_dates = $stmt_dates->get_result();

$billing_dates = [];
while ($row = $result_dates->fetch_assoc()) {
    $billing_dates[] = $row['billing_date'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History</title>
    <link rel="stylesheet" href="dashbcss.css">
    <link rel="stylesheet" href="recordingadmin.css">
    <style>
        .payment-row {
            background-color: #e7f3fe; /* Light blue for payment history */
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-content">
    <div class="container">
        <section>
            <h2>Payment History for Homeowner ID: <?php echo $homeowner_name; ?></h2>
            <a href="recordingadmin.php" class="btn">Back</a>
<a href="accepted_appointments_history.php?homeowner_id=<?= htmlspecialchars($homeowner_id); ?>" class="btn">Appointments</a>
<a href="payment_history_admin.php?homeowner_id=<?= htmlspecialchars($homeowner_id); ?>" class="btn">See Uploaded Images</a>
<a href="previous_records.php?homeowner_id=<?= htmlspecialchars($homeowner_id); ?>" class="btn">Add Previous Records</a>

           <!-- Search Form -->
           <form id="search-form" class="search-form">
    <input type="hidden" name="homeowner_id" value="<?= htmlspecialchars($homeowner_id); ?>">
    <div class="custom-dropdown">
        <input type="text" id="search-input" name="search" placeholder="Search by Month" value="<?= htmlspecialchars($search_date); ?>">
        <div id="dropdown-options" class="dropdown-options"> 
            <?php foreach ($billing_dates as $date): ?>
                <div class="dropdown-option" data-value="<?= htmlspecialchars($date); ?>"><?= htmlspecialchars($date); ?></div>
            <?php endforeach; ?>
        </div>
        <button type="button" id="search-button">Search</button>
    </div>
</form>
            <h3>Billing History</h3>
            <table id="billing-table" class="table">
                <thead>
                    <tr>
                        <th>History ID</th>
                        <th>Monthly Due</th>
                        <th>Billing Date</th>
                        <th>Due Date</th>
                        <th>Total Amount</th>
                        <th>Paid Date</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Billing records will be injected here -->
                    <?php if ($result_billing->num_rows > 0): ?>
                        <?php while ($row = $result_billing->fetch_assoc()): ?>
                            <tr class="payment-row">
                                <td><?php echo htmlspecialchars($row['history_id']); ?></td>
                                <td><?php echo number_format($row['monthly_due'], 2); ?></td>
                                <td><?php echo htmlspecialchars($row['billing_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                                <td><?php echo number_format($row['total_amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($row['paid_date']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6">No billing records found for this homeowner.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div id="pagination">
                <?php if ($total_pages_billing > 1): ?>
                    <!-- Previous Button for Billing -->
                    <?php if ($current_page > 1): ?>
                        <form method="GET" action="input_billing.php" style="display: inline;">
                            <input type="hidden" name="homeowner_id" value="<?= htmlspecialchars($homeowner_id); ?>">
                            <input type="hidden" name="search" value="<?= htmlspecialchars($search_date); ?>">
                            <input type="hidden" name="page" value="<?= $current_page - 1 ?>">
                            <button type="submit">&lt;</button>
                        </form>
                    <?php endif; ?>

                    <!-- Page Input Field for Billing -->
                    <form method="GET" action="input_billing.php" style="display: inline;">
                        <input type="hidden" name="homeowner_id" value="<?= htmlspecialchars($homeowner_id); ?>">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search_date); ?>">
                        <input type="number" name="page" value="<?= $current_page ?>" min="1" max="<?= $total_pages_billing ?>" style="width: 50px;">
                    </form>

                    <!-- Next Button for Billing -->
                    <?php if ($current_page < $total_pages_billing): ?>
                        <form method="GET" action="input_billing.php" style="display: inline;">
                            <input type="hidden" name="homeowner_id" value="<?= htmlspecialchars($homeowner_id); ?>">
                            <input type="hidden" name="search" value="<?= htmlspecialchars($search_date); ?>">
                            <input type="hidden" name="page" value="<?= $current_page + 1 ?>">
                            <button type="submit">&gt;</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<script>
    const input = document.getElementById('search-input');
    const options = document.getElementById('dropdown-options');

    // Show dropdown on input focus
    input.addEventListener('focus', function() {
        options.style.display = 'block'; // Show dropdown on focus
    });

    // Filter options based on user input
    input.addEventListener('input', function() {
        const filter = input.value.toLowerCase();
        const items = options.querySelectorAll('.dropdown-option');

        items.forEach(item => {
            if (item.textContent.toLowerCase().includes(filter)) {
                item.style.display = 'block'; // Show matching items
            } else {
                item.style.display = 'none'; // Hide non-matching items
            }
        });
    });

    // Handle clicking on an option
    options.addEventListener('click', function(event) {
        if (event.target.classList.contains('dropdown-option')) {
            input.value = event.target.dataset.value; // Set input value
            options.style.display = 'none'; // Hide dropdown
        }
    });

    // Hide dropdown if click outside
    document.addEventListener('click', function(event) {
        if (!input.contains(event.target) && !options.contains(event.target)) {
            options.style.display = 'none'; // Hide dropdown if click outside
        }
    });

    document.getElementById('search-button').addEventListener('click', function() {
        const homeownerId = document.querySelector('input[name="homeowner_id"]').value;
        const searchDate = document.getElementById('search-input').value;

        fetch(`search_billing.php?homeowner_id=${homeownerId}&search=${searchDate}`)
            .then(response => response.json())
            .then(data => {
                const tableBody = document.querySelector('#billing-table tbody');
                tableBody.innerHTML = ''; // Clear existing rows

                if (data.length > 0) {
                    data.forEach(record => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${record.history_id}</td>
                            <td>${parseFloat(record.monthly_due).toFixed(2)}</td>
                            <td>${record.billing_date}</td>
                            <td>${record.due_date}</td>
                            <td>${parseFloat(record.total_amount).toFixed(2)}</td>
                            <td>${record.paid_date}</td>
                        `;
                        tableBody.appendChild(row);
                    });
                } else {
                    const row = document.createElement('tr');
                    row.innerHTML = '<td colspan="6">No billing records found for this homeowner.</td>';
                    tableBody.appendChild(row);
                }
            })
            .catch(error => console.error('Error fetching billing data:', error));
    });
</script>
</body>
</html>
