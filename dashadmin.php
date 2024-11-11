
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_name('admin_session'); // Set a unique session name for admins
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Query to get the total number of homeowners
$sql = "SELECT COUNT(id) AS total_homeowners FROM homeowners";
$result = $conn->query($sql);
$total_homeowners = 0;

if ($result->num_rows > 0) {
    // Fetch the result
    $row = $result->fetch_assoc();
    $total_homeowners = $row['total_homeowners'];
}

$sql = "SELECT COUNT(*) AS total_complaints FROM complaints";
$result = $conn->query($sql);

$total_complaints = 0;
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total_complaints = $row['total_complaints'];
}

$sql = "SELECT COUNT(*) AS total_billing FROM billing";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Fetch the row
    $row = $result->fetch_assoc();
    $total_billing = $row['total_billing'];
} else {
    $total_billing = 0;
}

$sql = "SELECT COUNT(*) AS total FROM accepted_appointments";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $totalAppointments = $row['total'];
} else {
    $totalAppointments = 0;
}

$sql = "SELECT COUNT(*) AS total_servicereq FROM serreq";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total_service_requests = $row['total_servicereq'];
} else {
    $total_service_requests = 0;
}

// Calculate total earnings from billing, excluding 'Overdue' and 'Pending' statuses
// Get the current month and year
$currentMonth = date('m');
$currentYear = date('Y');

// Calculate total earnings from billing, excluding 'Overdue' and 'Pending' statuses
$totalEarningsQuery = "
    SELECT SUM(total_amount) AS total_earnings 
    FROM billing 
    WHERE status NOT IN ('Overdue', 'Pending')"; 
$totalEarningsResult = $conn->query($totalEarningsQuery);
$totalEarnings = ($totalEarningsResult->num_rows > 0) ? $totalEarningsResult->fetch_assoc()['total_earnings'] : 0.00; // Ensure this is defined before usage

// Calculate total earnings from accepted appointments, only including 'paid' status
$acceptedAppointmentsQuery = "
    SELECT SUM(amount) AS accepted_appointments_earnings 
    FROM accepted_appointments 
    WHERE status = 'paid'";
$acceptedAppointmentsResult = $conn->query($acceptedAppointmentsQuery);
$acceptedAppointmentsEarnings = ($acceptedAppointmentsResult->num_rows > 0) ? $acceptedAppointmentsResult->fetch_assoc()['accepted_appointments_earnings'] : 0.00;
$totalPaidQuery = "
    SELECT SUM(total_amount) AS total_paid 
    FROM billing 
    WHERE status = 'Paid' 
    AND MONTH(billing_date) = MONTH(CURRENT_DATE()) 
    AND YEAR(billing_date) = YEAR(CURRENT_DATE())";
$totalPaidResult = $conn->query($totalPaidQuery);
$totalPaid = ($totalPaidResult->num_rows > 0) ? $totalPaidResult->fetch_assoc()['total_paid'] : 0.00;

// Calculate total combined earnings for the current month
$totalCombinedEarnings = $totalPaid + $acceptedAppointmentsEarnings;

// Handle form submission for adding new announcements
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_announcement'])) {
    $content = $_POST['content'];
    // Limit to max 5 announcements
    $countQuery = "SELECT COUNT(*) AS total FROM announcements";
    $countResult = $conn->query($countQuery);
    $count = $countResult->fetch_assoc()['total'];
    
    if ($count < 5) {
        $stmt = $conn->prepare("INSERT INTO announcements (content) VALUES (?)");
        $stmt->bind_param("s", $content);
        $stmt->execute();
        $stmt->close();
    } else {
        $error = "You can only have a maximum of 5 announcements.";
    }
}

// Handle deletion of announcements
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $deleteStmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    $deleteStmt->bind_param("i", $id);
    $deleteStmt->execute();
    $deleteStmt->close();
}

// Fetch all announcements
$announcementsQuery = "SELECT * FROM announcements ORDER BY date DESC";
$announcementsResult = $conn->query($announcementsQuery);

// Handle form submission for updating announcement
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_announcement'])) {
    $id = $_POST['id'];
    $updatedContent = $_POST['content'];
    
    $updateStmt = $conn->prepare("UPDATE announcements SET content = ? WHERE id = ?");
    $updateStmt->bind_param("si", $updatedContent, $id);
    $updateStmt->execute();
    
    // Optionally check for success
    if ($updateStmt->affected_rows > 0) {
        echo "<script>alert('Announcement updated successfully');</script>";
    } else {
        echo "<script>alert('Failed to update announcement');</script>";
    }
    $updateStmt->close();
}


// Fetch announcements to display
$query = "SELECT * FROM announcements";
$announcementsResult = $conn->query($query);

$conn->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dash Admin</title>
    <link rel="stylesheet" href="dashadmincss.css">
    <link rel="stylesheet" href="dashboardadmincss.css">    
    <link rel="stylesheet" href="dashadmincalendar.css">


</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
    <div class="container">

            <h2>Welcome,  <?php echo htmlspecialchars($admin['username'] ?? 'Admin Name'); ?></h2>



           
    <div class="flex-container">
    <div class="chart-container"> <!-- Chart container -->
        <div class="report-section" id="combinedEarningsSection">
            <h2>Total Earnings for this month</h2>
            <p>₱<?php echo number_format($totalEarnings + $acceptedAppointmentsEarnings, 2); ?></p>
            <canvas id="combinedEarningsChart" ></canvas>
        </div>
    </div>
    <div id="calendar-box"> <!-- Calendar box -->
        <div id="calendar-container">
            <div id="calendar-nav">
                <button id="prev-month" type="button">&lt;</button>
                <span id="month-year"></span>
                <button id="next-month" type="button">&gt;</button>
            </div>
            <div id="calendar">
                <div class="calendar-header-cell">Sun</div>
                <div class="calendar-header-cell">Mon</div>
                <div class="calendar-header-cell">Tue</div>
                <div class="calendar-header-cell">Wed</div>
                <div class="calendar-header-cell">Thu</div>
                <div class="calendar-header-cell">Fri</div>
                <div class="calendar-header-cell">Sat</div>
                <!-- Days will be generated by JavaScript -->
            </div>
        </div>
    </div>


</div>



<div class="dashboard">
        <h2>St.Monique Monitoring</h2>
        <div class="tiles">
            <article class="tile">
                <div class="tile-header">
                    <i class="ph-lightning-light"></i>
                    <h3>
                        <span>Homeowners</span>
                        <span>Total Homeowners Account created</span>
                    </h3>
                </div>
                <div class="tile-content">
                    <span><?php echo $total_homeowners; ?></span>
                </div>
                <a href="homeowneradmin.php">
                    <span>Go to Homeowners</span>
                    <span class="icon-button">
                        <i class="ph-caret-right-bold"></i>
                    </span>
                </a>
            </article>

            <article class="tile">
                <div class="tile-header">
                    <i class="ph-fire-simple-light"></i>
                    <h3>
                        <span>Complaints</span>
                        <span>total Complaints Recieved</span>
                    </h3>
                </div>
                <div class="tile-content">
                    <span><?php echo $total_complaints; ?></span>
                </div>
                <a href="admincomplaint.php">
                    <span>Go to Complaints</span>
                    <span class="icon-button">
                        <i class="ph-caret-right-bold"></i>
                    </span>
                </a>
            </article>

            <article class="tile">
                <div class="tile-header">
                    <i class="ph-file-light"></i>
                    <h3>
                        <span>Billing</span>
                        <span>Total Billing created</span>
                    </h3>
                </div>
                <div class="tile-content">
                    <span><?php echo $total_billing; ?></span>
                </div>
                <a href="billingadmin.php">
                    <span>Go to Billing</span>
                    <span class="icon-button">
                        <i class="ph-caret-right-bold"></i>
                    </span>
                </a>
            </article>

            <article class="tile">
                <div class="tile-header">
                    <i class="ph-fire-simple-light"></i>
                    <h3>
                        <span>Appointment</span>
                        <span>total Appointments Recieved</span>
                    </h3>
                </div>
                <div class="tile-content">
                    <span><?php echo $totalAppointments; ?></span>
                </div>
                <a href="admin_approval.php">
                    <span>Go to Appointment</span>
                    <span class="icon-button">
                        <i class="ph-caret-right-bold"></i>
                    </span>
                </a>
            </article>

            <article class="tile">
                <div class="tile-header">
                    <i class="ph-file-light"></i>
                    <h3>
                        <span>Service Requests</span>
                        <span>total Service Requests Recieved</span>
                    </h3>
                </div>
                <div class="tile-content">
                    <span><?php echo $total_service_requests; ?></span>
                </div>
                <a href="serviceadmin.php">
                    <span>Go to Service Requests</span>
                    <span class="icon-button">
                        <i class="ph-caret-right-bold"></i>
                    </span>
                </a>
            </article>
        </div>
    </div>
   
    <div class="flex-container">
    <div class="announcement-widget">
        <h2>Manage Announcements</h2>

        <!-- Toggle Button to switch between views -->
        <button id="toggleViewBtn" onclick="toggleView()">Switch to Image Upload</button>

        <!-- Announcement Management Section -->
        <div id="announcementManagement">
            <!-- Form for adding a new announcement -->
            <form method="POST" action="dashadmin_add_announcement.php">
                <label for="content">New Announcement:</label><br>
                <textarea id="content" name="content" rows="4" cols="50" required></textarea><br><br>
                <button type="submit" name="add_announcement">Add Announcement</button>
            </form>

            <?php if (isset($error)): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>

            <h3>Current Announcements</h3>
            <table id="announcementTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Content</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($announcementsResult->num_rows > 0): ?>
                        <?php 
                        $count = 0; 
                        while ($row = $announcementsResult->fetch_assoc()): 
                            $count++; 
                        ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['content']); ?></td>
                            <td><?php echo date('F d, Y', strtotime($row['date'])); ?></td>
                            <td>
                                <a href="#" onclick="openEditModal('<?php echo $row['id']; ?>', '<?php echo htmlspecialchars($row['content']); ?>')">Edit</a>
                                <a href="?delete_id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this announcement?');">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No announcements found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Image Upload Section -->
        <div id="announcementImageUpload" style="display:none;">
    <h3>Upload Announcement Image</h3>
    <form method="POST" action="upload_announcement_image.php" enctype="multipart/form-data">
        <label for="imageFile">Select an image to upload:</label><br>
        <input type="file" id="imageFile" name="imageFile" required><br><br>
        <button type="submit" name="upload_image">Upload Image</button>
    </form>


<!-- Display Images Table -->
<h3>Uploaded Images</h3>
<table border="1">
    <tr>
        <th>ID</th>
        <th>File Path</th>
        <th>Image</th>
        <th>Action</th>
    </tr>
    <?php
    // Database connection to fetch images
    $servername = "localhost";
    $username = "u780935822_homeowner";
    $password = "Boot@o29";
    $dbname = "u780935822_homeowner";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Fetch images from the database
    $query = "SELECT * FROM announcement_images";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['file_path'] . "</td>";
            echo "<td><img src='" . $row['file_path'] . "' alt='Image' width='100'></td>";
            echo "<td><a href='delete_announcement_image.php?delete_id=" . $row['id'] . "' onclick='return confirm(\"Are you sure you want to delete this image?\")'>Delete</a></td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='4'>No images found</td></tr>";
    }
    ?>
</table>
</div>
    <!-- JavaScript for toggling views -->
    <script>
        function toggleView() {
            const announcementManagement = document.getElementById('announcementManagement');
            const announcementImageUpload = document.getElementById('announcementImageUpload');
            const toggleBtn = document.getElementById('toggleViewBtn');

            if (announcementManagement.style.display === 'none') {
                announcementManagement.style.display = 'block';
                announcementImageUpload.style.display = 'none';
                toggleBtn.textContent = 'Switch to Image Upload';
            } else {
                announcementManagement.style.display = 'none';
                announcementImageUpload.style.display = 'block';
                toggleBtn.textContent = 'Switch to Manage Announcements';
            }
        }
    </script>



<script>

 function openEditModal(id, content) {
    document.getElementById('announcementId').value = id;
    document.getElementById('editContent').value = content;
    document.getElementById('editAnnouncementModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('editAnnouncementModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target == document.getElementById('editAnnouncementModal')) {
        closeModal();
    }
}


</script>

</div>

<div class="recent-payments">
    <h2>Recent Payments</h2>
    <?php
    // Fetch the 5 most recent payments ordered by ID in descending order
    $result = mysqli_query($conn, "SELECT p.id, p.file_path, p.date, p.billing_reference, h.name AS homeowner_name, p.viewed
                                    FROM payments p 
                                    JOIN homeowners h ON p.homeowner_id = h.id 
                                    ORDER BY p.id DESC 
                                    LIMIT 5"); // Order by highest ID first

    $currentDate = new DateTime(); // Get the current date and time

    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $paymentDate = new DateTime($row['date']); // Convert payment date to DateTime object

            // Check if the payment is within the last 24 hours
            $interval = $currentDate->diff($paymentDate);
            $isNew = $interval->days == 0 && $interval->h < 24; // If less than 24 hours

            echo '<div class="payment" onclick="markAsViewed(' . $row['id'] . ', this)">';
            echo '<img src="' . htmlspecialchars($row['file_path']) . '" alt="Proof of Payment" class="zoomable">';
            echo '<div class="payment-details">';
            echo '<p>Homeowner: <span>' . htmlspecialchars($row['homeowner_name']) . '</span>';
            
            // Show red dot only if the payment is new and hasn't been viewed
            if ($isNew && !$row['viewed']) {
                echo ' <span class="red-dot"></span>';
            }

            echo '</p>';
            echo '<p>Date: <span>' . htmlspecialchars($row['date']) . '</span></p>';
            echo '</div>';
            echo '</div>';
        }
    } else {
        echo '<p>No recent payments found.</p>';
    }
    ?>
</div>




<div id="myModal" class="modal">
    <span class="close">&times;</span>
    <img class="modal-content" id="img01">
    <div id="caption"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Data for the chart
    const totalEarnings = <?php echo json_encode($totalEarnings); ?>;
    const acceptedAppointmentsEarnings = <?php echo json_encode($acceptedAppointmentsEarnings); ?>;

    // Create the chart
    const ctx = document.getElementById('combinedEarningsChart').getContext('2d');
    const combinedEarningsChart = new Chart(ctx, {
        type: 'bar', // You can change the type to 'line', 'pie', etc.
        data: {
            labels: ['Total Earnings', 'Accepted Appointments Earnings'],
            datasets: [{
                label: 'Earnings',
                data: [totalEarnings, acceptedAppointmentsEarnings],
                backgroundColor: [
                    'rgba(75, 192, 192, 0.2)', // Color for total earnings
                    'rgba(255, 99, 132, 0.2)' // Color for accepted appointments earnings
                ],
                borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 99, 132, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>

<script>
function markAsViewed(paymentId, paymentElement) {
    // AJAX request to mark the payment as viewed
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "mark_as_viewed.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            // Assuming the server returns a success message
            // Remove the red dot after marking as viewed
            var dot = paymentElement.querySelector('.red-dot');
            if (dot) {
                dot.remove();
            }
        }
    };
    xhr.send("id=" + paymentId);
}
</script>
<script>
    // JavaScript for Modal Image Zoom
    const modal = document.getElementById('myModal');
    const modalImg = document.getElementById('img01');
    const captionText = document.getElementById('caption');
    const zoomableImages = document.querySelectorAll('.zoomable');

    // Get viewed payment IDs from cookies
    let viewedPayments = getCookie('viewed_payments') ? getCookie('viewed_payments').split(',') : [];

    zoomableImages.forEach(img => {
        const paymentId = img.getAttribute('data-payment-id');

        img.onclick = function () {
            // Open modal logic
            modal.style.display = 'block';
            modalImg.src = this.src;
            captionText.innerHTML = this.alt;

            // Store viewed payment in cookies
            if (!viewedPayments.includes(paymentId)) {
                viewedPayments.push(paymentId);
                document.cookie = "viewed_payments=" + viewedPayments.join(',') + "; path=/; max-age=" + (365 * 24 * 60 * 60); // 1 year expiry
            }

            // Remove red dot
            const paymentDetails = this.parentElement.querySelector('.payment-details p');
            const redDot = paymentDetails.querySelector('.red-dot');
            if (redDot) {
                redDot.remove();
            }
        }
    });

    // Close the modal when the user clicks on <span> (x)
    const span = document.getElementsByClassName('close')[0];
    span.onclick = function () {
        modal.style.display = 'none';
    }

    // Also close the modal when the user clicks anywhere outside of the image
    modal.onclick = function (event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }

    // Function to get a cookie by name
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }
</script>





<script src="dashusercalendar.js"></script>

<script src="dashadmin.js"></script>

    

</body>
</html>