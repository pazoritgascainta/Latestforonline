<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_name('admin_session');
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

// Function to check if a history record exists for the homeowner_id and billing_date
function historyRecordExists($conn, $homeowner_id, $billing_date) {
    $sql_check = "SELECT COUNT(*) FROM billing_history WHERE homeowner_id = ? AND billing_date = ?";
    $stmt_check = $conn->prepare($sql_check);
    if ($stmt_check) {
        $stmt_check->bind_param("is", $homeowner_id, $billing_date);
        $stmt_check->execute();
        $stmt_check->bind_result($count);
        $stmt_check->fetch();
        $stmt_check->close();
        return $count > 0;
    } else {
        $_SESSION['message'] = "Prepare statement failed: " . $conn->error;
        return false;
    }
}

// Initialize homeowner ID from GET request or default to 0
$homeowner_id = isset($_GET['homeowner_id']) ? intval($_GET['homeowner_id']) : 0;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['homeowner_id'])) {
    $homeowner_id = intval($_POST['homeowner_id']);
    $billing_date = $_POST['billing_date'] . '-01';
    $paid_date = $_POST['paid_date'] . '-01';
    $status = 'Paid';

    $billingDate = new DateTime($billing_date);
    $paidDate = new DateTime($paid_date);

    while ($billingDate <= $paidDate) {
        $currentBillingDate = $billingDate->format('Y-m-d');

        if (!historyRecordExists($conn, $homeowner_id, $currentBillingDate)) {
            $sql = "SELECT sqm FROM homeowners WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $homeowner_id);
            $stmt->execute();
            $stmt->bind_result($sqm);
            $stmt->fetch();
            $stmt->close();

            $monthly_due = $sqm * 5;
            $due_date = (clone $billingDate)->modify('first day of next month')->format('Y-m-d');
            $total_amount = $monthly_due;

            $sql_insert = "INSERT INTO billing_history (homeowner_id, monthly_due, billing_date, due_date, status, total_amount, paid_date) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            if ($stmt_insert) {
                $stmt_insert->bind_param("issssds", $homeowner_id, $monthly_due, $currentBillingDate, $due_date, $status, $total_amount, $paid_date);
                if (!$stmt_insert->execute()) {
                    $_SESSION['message'] = "Failed to create billing history record for $currentBillingDate: " . $stmt_insert->error;
                    error_log("Insert error: " . $stmt_insert->error);
                }
                $stmt_insert->close();
            } else {
                $_SESSION['message'] = "Prepare statement failed: " . $conn->error;
                error_log("Prepare error: " . $conn->error);
            }
        } else {
            $_SESSION['message'] = "Billing record for $currentBillingDate already exists for this homeowner.";
        }

        $billingDate->modify('+1 month');
    }

    header("Location: input_billing.php?homeowner_id=" . $homeowner_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Create Billing History Record</title>
    <link rel="stylesheet" href="create_billing.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
    <button onclick="history.back()" class="back-button">Go Back</button>

        <div class="container">
            <h1>Create Billing History Record</h1>

            <form method="POST" action="">
    <div class="form-group" style="display: none;">
        <label for="homeowner_id">Homeowner ID:</label>
        <input type="hidden" id="homeowner_id" name="homeowner_id" value="<?php echo htmlspecialchars($homeowner_id); ?>" required>
    </div>

                <div class="form-group">
                    <label for="homeowner_name">Homeowner Name:</label>
                    <input type="text" id="homeowner_name" name="homeowner_name" readonly>
                </div>

                <div class="form-group">
                    <label for="sqm">Square Meters:</label>
                    <input type="text" id="sqm" name="sqm" readonly>
                </div>

                <div class="form-group">
                    <label for="billing_date">Billing Month:</label>
                    <input type="month" id="billing_date" name="billing_date" required>
                </div>

                <div class="form-group">
                    <label for="due_date">Due Date:</label>
                    <input type="text" id="due_date" name="due_date" readonly>
                </div>

                <div class="form-group">
                    <label for="paid_date">Paid Month:</label>
                    <input type="month" id="paid_date" name="paid_date" required>
                </div>

                <div class="form-group">
                    <label for="status">Status:</label>
                    <input type="text" id="status" name="status" value="Paid" readonly>
                </div>

                <div class="form-group">
                    <label for="monthly_due">Monthly Due:</label>
                    <input type="number" step="0.01" id="monthly_due" name="monthly_due" readonly>
                </div>

                <button type="submit" class="submit-btn" name="create_billing">Create Billing History Record</button>
            </form>
        </div>
    </div>

    <script>
        function fetchHomeownerData() {
            const homeownerId = document.getElementById('homeowner_id').value;
            if (homeownerId) {
                fetch(`get_homeowner.php?id=${homeownerId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data && data.name) {
                            document.getElementById('homeowner_name').value = data.name;
                            document.getElementById('sqm').value = data.sqm;
                            document.getElementById('monthly_due').value = (data.sqm * 5).toFixed(2); // Calculate monthly due
                            // Automatically set the due date based on current billing date if it exists
                            const billingDateInput = document.getElementById('billing_date').value;
                            if (billingDateInput) {
                                const billingDate = new Date(billingDateInput + '-01');
                                billingDate.setMonth(billingDate.getMonth() + 1);
                                document.getElementById('due_date').value = billingDate.toISOString().slice(0, 10);
                            }
                        } else {
                            // Clear fields if no valid homeowner data found
                            document.getElementById('homeowner_name').value = '';
                            document.getElementById('sqm').value = '';
                            document.getElementById('monthly_due').value = '';
                        }
                    })
                    .catch(error => console.error('Error fetching homeowner data:', error));
            } else {
                // Clear fields if no homeowner ID is entered
                document.getElementById('homeowner_name').value = '';
                document.getElementById('sqm').value = '';
                document.getElementById('monthly_due').value = '';
            }
        }

        // Automatically fetch homeowner data when the page loads if homeowner_id is set
        window.onload = function() {
            const homeownerId = document.getElementById('homeowner_id').value;
            if (homeownerId) {
                fetchHomeownerData();
            }
        };
    </script>
</body>
</html>
