<?php
session_name('user_session');
session_start();


if (!isset($_SESSION['homeowner_id'])) {
    header('Location: login.php');
    exit();
}


$homeowner_id = $_SESSION['homeowner_id'];


$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$limit = 3; 
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($current_page - 1) * $limit;


$sql_count = "SELECT COUNT(*) as total FROM serreq WHERE homeowner_id = ?";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->bind_param("i", $homeowner_id);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$row_count = $result_count->fetch_assoc();
$total_service_requests = $row_count['total'];
$total_pages = max(ceil($total_service_requests / $limit), 1);


$sql_requests = "SELECT * FROM serreq WHERE homeowner_id = ? LIMIT ?, ?";
$stmt_requests = $conn->prepare($sql_requests);
$stmt_requests->bind_param("iii", $homeowner_id, $offset, $limit);
$stmt_requests->execute();
$result_requests = $stmt_requests->get_result();
$service_requests = $result_requests->fetch_all(MYSQLI_ASSOC);


$search_query = isset($_GET['search']) ? $_GET['search'] : '';


if (isset($_POST['cancel_request_id'])) {
    $request_id_to_cancel = $_POST['cancel_request_id'];


    $sql_cancel = "DELETE FROM serreq WHERE service_req_id = ? AND homeowner_id = ?";
    $stmt_cancel = $conn->prepare($sql_cancel);
    $stmt_cancel->bind_param("ii", $request_id_to_cancel, $homeowner_id);
    $stmt_cancel->execute();


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
        <a href="serviceuser.php" class="submit-link" id="openServiceModal">Submit a Service Request</a>
        </div>
    </div>
    <div id="serviceTermsModal" class="modal">
        <div class="modal-content">
            <h2 class="modal-header">Service Request Terms and Conditions</h2>
            <div class="modal-body">
                <p>By using the Service Request Module, you agree to the following terms:</p>
                <ol>
                    <li>You are responsible for the accuracy of the service request information submitted.</li>
                    <li>Do not submit any false, misleading, or inappropriate requests.</li>
                    <li>Your request details will be handled in accordance with our <a href="privacy_policy_page.php" target="_blank" class="privacy-policy">Privacy Policy</a>.</li>
                    <li>Service requests may be shared with relevant personnel for resolution.</li>
                    <li>Misuse of the Service Request Module may lead to account suspension.</li>
                </ol>
                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" id="serviceCheckbox1">
                        I acknowledge that the information provided is accurate.
                    </label>
                    <label>
                        <input type="checkbox" id="serviceCheckbox2">
                        I agree to the Terms and Conditions.
                    </label>
                </div>
            </div>
            <button id="serviceAcceptBtn" class="btn" disabled>Accept and Proceed</button>
            <button class="btn btn-close" id="closeServiceModal">Close</button>
        </div>
    <script>
        // Get elements
        const serviceModal = document.getElementById("serviceTermsModal");
        const openServiceModal = document.getElementById("openServiceModal");
        const closeServiceModal = document.getElementById("closeServiceModal");
        const serviceAcceptBtn = document.getElementById("serviceAcceptBtn");
        const serviceCheckbox1 = document.getElementById("serviceCheckbox1");
        const serviceCheckbox2 = document.getElementById("serviceCheckbox2");

        // Function to check if user has already accepted terms
        function hasUserAcceptedServiceTerms() {
            return localStorage.getItem("serviceTermsAccepted") === "true";
        }

        // Open the modal only if user hasn't accepted terms
        openServiceModal.addEventListener("click", function (event) {
            if (hasUserAcceptedServiceTerms()) {
                // Redirect to service request page if terms already accepted
                window.location.href = "serviceuser.php";
            } else {
                event.preventDefault(); // Prevent link default behavior
                serviceModal.style.display = "block";
            }
        });

        // Close the modal
        closeServiceModal.addEventListener("click", function () {
            serviceModal.style.display = "none";
        });

        // Enable the Accept button only when both checkboxes are checked
        function toggleServiceAcceptButton() {
            serviceAcceptBtn.disabled = !(serviceCheckbox1.checked && serviceCheckbox2.checked);
        }

        serviceCheckbox1.addEventListener("change", toggleServiceAcceptButton);
        serviceCheckbox2.addEventListener("change", toggleServiceAcceptButton);

        // Accept and Proceed button
        serviceAcceptBtn.addEventListener("click", function () {
            // Save user's acknowledgment in localStorage
            localStorage.setItem("serviceTermsAccepted", "true");
            // Redirect to service request page
            window.location.href = "serviceuser.php";
        });

        // Close the modal when clicking outside of it
        window.onclick = function (event) {
            if (event.target == serviceModal) {
                serviceModal.style.display = "none";
            }
        };
    </script>


         
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

              
                <?php if ($total_pages > 1): ?>
                    <span>of</span>
                    <a href="?search=<?= urlencode($search_query); ?>&page=<?= $total_pages ?>" class="<?= ($current_page == $total_pages) ? 'active' : '' ?>"><?= $total_pages ?></a>
                <?php endif; ?>
            </div>
    </div>
</div>
</body>
</html>
