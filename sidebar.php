<?php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    // Redirect to the admin login page
    header("Location: admin_login.php");
    exit;  // Terminate script execution after the redirect
}
$admin_id = $_SESSION['admin_id'];

// Fetch the admin's current information
$sql = "SELECT * FROM admin WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    error_log("Failed to prepare SQL statement: " . $conn->error);
    return;  // Avoid using exit here to allow the rest of the page to render
}

$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch the admin data if it exists
if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();
} else {
    error_log("Admin not found for ID " . $admin_id);
    return;  // Avoid using exit here to allow the rest of the page to render
}

$stmt->close();

// Fetch the unread message count
$sql_count = "SELECT COUNT(*) as unread_count FROM admin_inbox WHERE admin_id = ? AND seen = 0";
$stmt_count = $conn->prepare($sql_count);

if ($stmt_count === false) {
    error_log("Failed to prepare SQL statement for unread count: " . $conn->error);
    return;
}

$stmt_count->bind_param("i", $admin_id);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$unread_message = $result_count->fetch_assoc();
$unread_messages = $unread_message['unread_count'];
$stmt_count->close();

// Default profile image handling
$default_image = 'profile.png';
$profile_image = isset($admin['profile_image']) && !empty($admin['profile_image']) ? $admin['profile_image'] : $default_image;

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="dashbcss.css">
    <link rel="stylesheet" href="sidebar.css">
    <style>
        .unread-count {
            color: white; /* Change this to your preferred color */
            background-color: red; /* Color of the badge */
            border-radius: 50%; /* Make the badge circular */
            padding: 5px; /* Padding inside the badge */
            position: absolute; /* Position it absolutely */
            top: -20px; /* Adjust this value to position the badge */
            right: -10px; /* Adjust this value to position the badge */
            min-width: 20px; /* Minimum width to make it look circular */
            text-align: center; /* Center the count text */
            font-size: 12px; /* Font size */
        }
    </style>
</head>
<body>

<div class="headnavbar">
    <nav>
        <a href="dashadmin.php">
            <img src="monique logo.png" alt="logo" id="logo-img">
        </a>
        <div class="nav-links-wrapper">
            <ul>
                <li><a href="dashadmin.php" class="nav-link home-link">Home</a></li>
                <li>
                    <a href="#" class="nav-link notifications-link" style="position: relative;">
                        Notifications 
                        <?php if ($unread_messages > 0): ?>
                            <span class="unread-count">
                                <?php echo htmlspecialchars($unread_messages); ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <div class="sub-menu-wrap" id="notificationsMenu">
                        <div class="sub-menu">
                            <a href="inbox.php" class="sub-menu-link">
                                <img src="inbox.png" alt="">
                                <p>Inbox</p>
                                <span>></span>
                            </a>
                        </div>
                    </div>
                </li>
                <audio id="notificationSound" src="Notificationsound.mp3" preload="auto"></audio>
            </ul>
            <img src="<?php echo htmlspecialchars($profile_image); ?>" class="user-pic" onclick="toggleProfileMenu()" alt="profile picture">
            <div class="sub-menu-wrap" id="profileMenu">
                <div class="sub-menu">
                    <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Image" class="profile-img" id="sidebarProfileImage">
                    <div>
                        <p class="user-names"><?php echo htmlspecialchars($admin['username'] ?? 'Admin Name'); ?></p>
                        <p>Admin</p>
                    </div>
                    <a href="settingsadmin.php" class="sub-menu-link">
                        <img src="settings.png" alt="">
                        <p>Settings</p>
                        <span>></span>
                    </a>
                    <a href="adminlogout.php" class="sub-menu-link">
                        <img src="logawt.png" alt="">
                        <p>Logout</p>
                        <span>></span>
                    </a>
                </div>
            </div>
        </div>
    </nav>
</div>

<!-- Sidebar -->
<div class="sidebar">
    <div class="top">
        <div class="logo">
            <img src="monique logo.png" width="170" height="80" alt="monique" class="mnq-img">
        </div>
        <img src="menu.png" alt="menu" class="menu-img" id="btn">
    </div>
    <div class="user">
        <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="profile picture" class="profile-img">
        <div>
            <p class="user-names"><?php echo htmlspecialchars($admin['username'] ?? 'Admin Name'); ?></p>
            <p>Admin</p>
        </div>
    </div>
    <hr>
    <ul>
        <li>
            <a href="dashadmin.php">
                <img src="dashboard.png" alt="dashboard" class="sideimg">
                <span class="nav-item">Dashboard</span>
            </a>
            <span class="tooltip">Dashboard</span>
        </li>
        <li>
            <a href="homeowneradmin.php">
                <img src="homeowner.png" alt="homeowner" class="sideimg">
                <span class="nav-item">Homeowner</span>
            </a>
            <span class="tooltip">Homeowner</span>
        </li>
        <li>
            <a href="admincomplaint.php">
                <img src="complaint.png" alt="complaints" class="sideimg">
                <span class="nav-item">Complaints</span>
            </a>
            <span class="tooltip">Complaints</span>
        </li>
        <li>
            <a href="billingadmin.php">
                <img src="bill.png" alt="billing" class="sideimg">
                <span class="nav-item">Billing</span>
            </a>
            <span class="tooltip">Billing</span>
        </li>
        <li>
            <a href="recordingadmin.php">
                <img src="record.png" alt="recording" class="sideimg">
                <span class="nav-item">Recording</span>
            </a>
            <span class="tooltip">Recording</span>
        </li>
        <li>
            <a href="admin_approval.php">
                <img src="schedule.png" alt="schedule" class="sideimg">
                <span class="nav-item">Book</span>
            </a>
            <span class="tooltip">Book</span>
        </li>
        <li>
            <a href="serviceadmin.php">
                <img src="service.png" alt="service" class="sideimg">
                <span class="nav-item">Service</span>
            </a>
            <span class="tooltip">Service Requests</span>
        </li>
        <li>
            <a href="reportadmin.php">
                <img src="report.png" alt="report" class="sideimg">
                <span class="nav-item">Report</span>
            </a>
            <span class="tooltip">Report</span>
        </li>
    </ul>
</div>

<script src="sidebar.js"></script>

</body>
</html>