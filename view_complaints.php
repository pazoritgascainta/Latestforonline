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


function sanitize_input($data) {
    global $conn;
    return $conn->real_escape_string(trim(htmlspecialchars($data)));
}


$limit = 3; 
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($current_page - 1) * $limit;


$sql_count = "SELECT COUNT(*) as total FROM complaints WHERE homeowner_id = ?";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->bind_param("i", $homeowner_id);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$row_count = $result_count->fetch_assoc();
$total_complaints = $row_count['total'];
$total_pages = max(ceil($total_complaints / $limit), 1);


$sql_complaints = "SELECT * FROM complaints WHERE homeowner_id = ? LIMIT ?, ?";
$stmt_complaints = $conn->prepare($sql_complaints);
$stmt_complaints->bind_param("iii", $homeowner_id, $offset, $limit);
$stmt_complaints->execute();
$result_complaints = $stmt_complaints->get_result();
$complaints = $result_complaints->fetch_all(MYSQLI_ASSOC);

if (isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];

  
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="view_complaints.css">
</head>
<body>
<?php include 'usersidebar.php'; ?>

<div class="container">
    <h2>View Your Complaints</h2>

    <?php if (count($complaints) > 0) : ?>
        <?php foreach ($complaints as $complaint): ?>
            <div class="complaint-card">
                <div class="complaint-item">
                    <span class="complaint-label">Subject:</span>
                    <span class="complaint-value"><?php echo htmlspecialchars($complaint['subject']); ?></span>
                </div>
                <div class="complaint-item">
                    <span class="complaint-label">Description:</span>
                    <span class="complaint-value"><?php echo htmlspecialchars($complaint['description']); ?></span>
                </div>
                <div class="complaint-item">
                    <span class="complaint-label">Status:</span>
                    <span class="complaint-value"><?php echo htmlspecialchars($complaint['status']); ?></span>
                </div>
                <div class="complaint-item">
                    <span class="complaint-label">Created At:</span>
                    <span class="complaint-value"><?php echo htmlspecialchars($complaint['created_at']); ?></span>
                </div>
                <div class="complaint-item">
                    <span class="complaint-label">Updated At:</span>
                    <span class="complaint-value"><?php echo htmlspecialchars($complaint['updated_at']); ?></span>
                </div>
                <div class="complaint-item">
                    <span class="complaint-label">Action:</span>
                    <form method="POST" action="view_complaints.php" class="delete-form">
                        <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($complaint['complaint_id']); ?>">
                        <button type="button" onclick="confirmDone(event, this, <?php echo $complaint['complaint_id']; ?>)" class="done-btn">Done</button>
                        <button type="button" onclick="confirmDelete(event, this)" class="cancel-btn">Cancel</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No complaints found.</p>
    <?php endif; ?>
    <div class="submit-complaint">
        <a href="usercomplaint.php" id="openModalLink">Submit a Complaint</a>
    </div>
</div>
<script>
    function confirmDone(event, button, complaintId) {
    // Confirm the action
    if (confirm("Are you sure you want to mark this complaint as resolved?")) {
        // Create an AJAX request
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "update_complaint.php", true); // Use existing update script
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        
        // Prepare the data to send
        var params = "complaint_id=" + complaintId;

        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                // Handle the response
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    alert("Complaint marked as resolved!");
                    
                    // Update the status in the corresponding element
                    var statusElement = button.closest('.complaint-item').querySelector('.complaint-value');
                    if (statusElement) {
                        statusElement.textContent = 'Resolved'; // Update the status text
                    }
                    
                    // Optionally update the button style or remove the button
                    button.setAttribute('disabled', 'true'); // Disable the button
                    button.textContent = 'Resolved'; // Update button text (optional)
                } else {
                    alert("Error: " + response.message);
                }
            }
        };

        // Send the request
        xhr.send(params);
    }
}
</script>

  <!-- The Modal -->
  <div id="termsModal" class="modal">
        <div class="modal-content">
            <h2 class="modal-header">Terms and Conditions</h2>
            <div class="modal-body">
                <p>By using the Complaints Module, you agree to the following terms:</p>
                <ol>
                    <li>You are responsible for the accuracy of the complaint information submitted.</li>
                    <li>Do not submit any false, misleading, or inappropriate content.</li>
                    <li>Your information will be handled in accordance with our <a href="privacy_policy_page.php" target="_blank" class="privacy-policy">Privacy Policy</a>.</li>
                    <li>Complaints may be shared with relevant personnel for resolution.</li>
                    <li>Misuse of the Complaints Module may lead to account suspension.</li>
                </ol>
                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" id="checkbox1">
                        I acknowledge that the information provided is accurate.
                    </label>
                    <label>
                        <input type="checkbox" id="checkbox2">
                        I agree to the Terms and Conditions.
                    </label>
                </div>
            </div>
            <button id="acceptBtn" class="btn" disabled>Accept and Proceed</button>
            <button class="btn btn-close" id="closeModalBtn">Close</button>
        </div>
    </div>
    <script>
        // Get elements
        const modal = document.getElementById("termsModal");
        const openModalLink = document.getElementById("openModalLink");
        const closeModalBtn = document.getElementById("closeModalBtn");
        const acceptBtn = document.getElementById("acceptBtn");
        const checkbox1 = document.getElementById("checkbox1");
        const checkbox2 = document.getElementById("checkbox2");

        // Function to check if user has already accepted terms
        function hasUserAcceptedTerms() {
            return localStorage.getItem("termsAccepted") === "true";
        }

        // Open the modal only if user hasn't accepted terms
        openModalLink.addEventListener("click", function (event) {
            if (hasUserAcceptedTerms()) {
                // Redirect to complaint page if terms already accepted
                window.location.href = "usercomplaint.php";
            } else {
                event.preventDefault(); // Prevent link default behavior
                modal.style.display = "block";
            }
        });

        // Close the modal
        closeModalBtn.addEventListener("click", function () {
            modal.style.display = "none";
        });

        // Enable the Accept button only when both checkboxes are checked
        function toggleAcceptButton() {
            acceptBtn.disabled = !(checkbox1.checked && checkbox2.checked);
        }

        checkbox1.addEventListener("change", toggleAcceptButton);
        checkbox2.addEventListener("change", toggleAcceptButton);

        // Accept and Proceed button
        acceptBtn.addEventListener("click", function () {
            // Save user's acknowledgment in localStorage
            localStorage.setItem("termsAccepted", "true");
            // Redirect to complaint page
            window.location.href = "usercomplaint.php";
        });

        // Close the modal when clicking outside of it
        window.onclick = function (event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        };
    </script>



         
<div id="pagination">
    <?php
    $total_pages = max($total_pages, 1); 
    $input_page = $current_page; 


    if ($current_page > 1): ?>
        <form method="GET" action="view_complaints.php" style="display: inline;">
            <input type="hidden" name="search" value="<?= htmlspecialchars($search_query); ?>">
            <input type="hidden" name="page" value="<?= $current_page - 1 ?>">
            <button type="submit">Previous</button>
        </form>
    <?php endif; ?>


    <form method="GET" action="view_complaints.php" style="display: inline;">
        <input type="hidden" name="search" value="<?= htmlspecialchars($search_query); ?>">
        <input type="number" name="page" value="<?= $input_page ?>" min="1" max="<?= $total_pages ?>" style="width: 50px;">
    </form>


    <?php if ($total_pages > 1): ?>
        <span>of</span>
        <a href="?search=<?= urlencode($search_query); ?>&page=<?= $total_pages ?>" class="<?= ($current_page == $total_pages) ? 'active' : '' ?>"><?= $total_pages ?></a>
    <?php endif; ?>


    <?php if ($current_page < $total_pages): ?>
        <form method="GET" action="view_complaints.php" style="display: inline;">
            <input type="hidden" name="search" value="<?= htmlspecialchars($search_query); ?>">
            <input type="hidden" name="page" value="<?= $current_page + 1 ?>">
            <button type="submit">Next</button>
        </form>
    <?php endif; ?>

</div>
<script>
function confirmDelete(event, link) {
    event.preventDefault(); 

    var confirmation = confirm('Are you sure you want to delete this complaint?');
    if (confirmation) {
        var form = link.closest('form');
        form.submit();
    }
}
</script>
</body>
</html>
