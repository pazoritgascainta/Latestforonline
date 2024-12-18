<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure homeowner_id is set in the session
if (!isset($_SESSION['homeowner_id'])) {
    die("Homeowner ID not found in session.");
}

$homeowner_id = $_SESSION['homeowner_id'];

// Fetch the homeowner's current information
$sql = "SELECT * FROM homeowners WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Failed to prepare SQL statement: " . $conn->error);
}

$stmt->bind_param("i", $homeowner_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $homeowner = $result->fetch_assoc();
} else {
    die("Homeowner not found.");
}

// Count unseen messages in the inbox
$sql_count = "SELECT COUNT(*) as unread_messages FROM inbox WHERE homeowner_id = ? AND seen = 0";
$stmt_count = $conn->prepare($sql_count);

if ($stmt_count === false) {
    die("Failed to prepare SQL statement for counting unread messages: " . $conn->error);
}

$stmt_count->bind_param("i", $homeowner_id);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$row = $result_count->fetch_assoc();
$unread_messages = $row['unread_messages'];

$stmt->close();
$stmt_count->close();
$conn->close();

// Default profile image
$default_image = 'profile.png';
$profile_image = $homeowner['profile_image'] ? $homeowner['profile_image'] : $default_image;
?>

<link rel="stylesheet" href="usersidebar.css">
<div class="headnavbar">
    <nav>
        <a href="dashuser.php">
            <img src="monique logo.png" alt="logo" id="logo-img">
        </a>
        <div class="nav-links-wrapper">
            <ul>
                <li><a href="dashuser.php" class="nav-link home-link">Home</a></li>
                <li>
    <a href="#" class="nav-link notifications-link" style="position: relative;">Notifications 
        <?php if ($unread_messages > 0): ?>
            <span class="unread-count">
                <?php echo $unread_messages; ?>
            </span>
        <?php endif; ?>
    </a>
    <div class="sub-menu-wrap" id="notificationsMenu">
        <div class="sub-menu">
            <a href="userinbox.php" class="sub-menu-link">
                <img src="inbox.png" alt="">
                <p>Inbox</p>
                <span>></span>
            </a>
        </div>
    </div>
</li>
            </ul>
            <audio id="notificationSound" src="Notifationsound.mp3" preload="auto"></audio>
            
            <a href="#" class="nav-link user-profile-link" onclick="toggleProfileMenu()">
                <img src="<?php echo htmlspecialchars($profile_image); ?>" class="user-pic" alt="profile picture">
            </a>
            
            <div class="sub-menu-wrap" id="profileMenu">
                <div class="sub-menu">
                    <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Image" class="profile-img" id="sidebarProfileImage">
                    <p class="user-names"><?php echo htmlspecialchars($homeowner['name']); ?></p>

                    <a href="usersettings.php" class="sub-menu-link">
                        <img src="settings.png" alt="">
                        <p>Settings</p>
                        <span>></span>
                    </a>
                    <a href="userhelp.php" class="sub-menu-link">
                        <img src="help.png" alt="">
                        <p>Help</p>
                        <span>></span>
                    </a>
                    <a href="userlogout.php" class="sub-menu-link">
                        <img src="logawt.png" alt="">
                        <p>Logout</p>
                        <span>></span>
                    </a>
                </div>
            </div>
        </div>
    </nav>
</div>

<div class="sidebar">
    <div class="top">
        <div class="logo">
            <img src="monique logo.png" width="170" height="80" alt="monique" class="mnq-img">
        </div>
        <img src="menu.png" alt="menu" class="menu-img" id="btn">
    </div>
    <div>
        <div class="user">
            <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="<?php echo htmlspecialchars($homeowner['name']); ?>" class="profile-img" id="sidebarProfileImage">
            <div>
                <p class="user-names"><?php echo htmlspecialchars($homeowner['name']); ?></p>
                <p>User</p>
            </div>
        </div>
    </div>
    <hr>
    <ul>
        <li>
            <a href="dashuser.php">
                <img src="dashboard.png" alt="dashboard" class="sideimg">
                <span class="nav-item">Dashboard</span>
            </a>
            <span class="tooltip">Dashboard</span>
        </li>
        <li>
            <a href="view_complaints.php">
                <img src="complaint.png" alt="complaints" class="sideimg">
                <span class="nav-item">Complaints</span>
            </a>
            <span class="tooltip">Complaints</span>
        </li>
        <li>
            <a href="payment.php">
                <img src="bill.png" alt="billing" class="sideimg">
                <span class="nav-item">Payment</span>
            </a>
            <span class="tooltip">Payment</span>
        </li>
        <li>
            <a href="amenity_booking.php">
                <img src="schedule.png" alt="schedule" class="sideimg">
                <span class="nav-item">Appointment</span>
            </a>
            <span class="tooltip">Appointment</span>
        </li>
        <li>
            <a href="view_service_requests.php">
                <img src="service.png" alt="service" class="sideimg">
                <span class="nav-item">Service</span>
            </a>
            <span class="tooltip">Service Requests</span>
        </li>
    </ul>
</div>
<script src="usersidebar.js"></script>