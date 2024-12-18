<?php
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

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if homeowner_id is set (you can also use a session variable if needed)
$homeowner_id = isset($_GET['homeowner_id']) ? intval($_GET['homeowner_id']) : 0;

if ($homeowner_id <= 0) {
    die("Invalid homeowner ID.");
}

// Pagination settings
$images_per_page = 5; // Number of images per page
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($current_page - 1) * $images_per_page;

// Get the total number of images for the specific homeowner
$sql_total_images = "SELECT COUNT(*) AS total FROM payments WHERE homeowner_id = ?";
$stmt_total_images = $conn->prepare($sql_total_images);
$stmt_total_images->bind_param("i", $homeowner_id);
$stmt_total_images->execute();
$result_total_images = $stmt_total_images->get_result();
$row_total_images = $result_total_images->fetch_assoc();
$total_images = intval($row_total_images['total']);

// Calculate total pages
$total_pages = ceil($total_images / $images_per_page);

// Fetch images and their dates for the specific homeowner (with pagination)
$sql_images = "SELECT date, file_path FROM payments WHERE homeowner_id = ? LIMIT ? OFFSET ?";
$stmt_images = $conn->prepare($sql_images);
$stmt_images->bind_param("iii", $homeowner_id, $images_per_page, $offset);
$stmt_images->execute();
$result_images = $stmt_images->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uploaded Images - Admin</title>
    <link rel="stylesheet" href="uploaded_payment.css">
    <style>
        .back-button {
    padding: 10px 20px;
    font-size: 16px;
    color: #fff;
    background-color: #ff8b2c;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
    margin-left: 160vh;
    margin-bottom: 20px;
}
       .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1000; /* Sit on top */
            padding-top: 60px; /* Space for the top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgb(0, 0, 0); /* Fallback color */
            background-color: rgba(0, 0, 0, 0.9); /* Black w/ opacity */
        }

        .modal-content {
            margin: auto;
            display: block;
            width: 80%;
            max-width: 700px; /* Limit the maximum width */
        }

        .modal-content, #caption {
            animation-name: zoom; /* Use zoom animation */
            animation-duration: 0.6s; /* Animation duration */
        }

        @keyframes zoom {
            from {transform: scale(0)} /* Start at scale 0 */
            to {transform: scale(1)} /* End at scale 1 */
        }
        
        /* Additional styles for recent payments */
        .recent-payments {
            padding: 20px;
            background-color: peachpuff; /* Change background to pure white for cleaner look */
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            font-family: 'Arial', sans-serif; /* Ensure consistent font */
        }

        .recent-payments h2 {
            font-size: 24px;
            margin-bottom: 20px;
            text-align: center;
            color: #333;
            font-weight: bold; /* Bold text for headings */
        }

        .payment {
            display: flex;
            align-items: center;
            background-color: #fff;
            border-bottom: 1px solid #ddd;
            padding: 15px 0; /* Increase padding for better spacing */
        }

        .payment img {
            width: 120px; /* Increased size for a more prominent view */
            height: 120px; 
            object-fit: cover;
            border-radius: 8px;
            margin-left: 20px;
            margin-right: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Add subtle shadow for depth */
            cursor: pointer; /* Add cursor pointer for clickable effect */
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
    <h2>Uploaded Images of Homeowner ID: <?php echo htmlspecialchars($homeowner_id); ?></h2> <!-- Add this line -->
    <button onclick="history.back()" class="back-button">Go Back</button>


        <div class="recent-payments">
            <?php if ($result_images->num_rows > 0): ?>
                <?php while ($row = $result_images->fetch_assoc()): ?>
                    <div class="payment">
                        <div class="payment-details">
                            <span>Date: <?php echo htmlspecialchars($row['date']); ?></span>
                        </div>
                        <div class="payment-image">
                            <?php if (!empty($row['file_path'])): ?>
                                <img src="<?php echo htmlspecialchars($row['file_path']); ?>" alt="Image" class="zoomable">
                            <?php else: ?>
                                <p>No image available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No images found for this homeowner.</p>
            <?php endif; ?>
        </div>

        <!-- Pagination controls -->
        <div id="pagination">
            <?php if ($total_pages > 1): ?>
                <?php if ($current_page > 1): ?>
                    <form method="GET" action="admin_uploaded_payment.php" style="display: inline;">
                        <input type="hidden" name="homeowner_id" value="<?php echo $homeowner_id; ?>">
                        <input type="hidden" name="page" value="<?php echo $current_page - 1; ?>">
                        <button type="submit">&lt;</button>
                    </form>
                <?php endif; ?>

                <form method="GET" action="admin_uploaded_payment.php" style="display: inline;">
                    <input type="hidden" name="homeowner_id" value="<?php echo $homeowner_id; ?>">
                    <input type="number" name="page" value="<?php echo $current_page; ?>" min="1" max="<?php echo $total_pages; ?>" style="width: 50px;">
                </form>

                <?php if ($current_page < $total_pages): ?>
                    <form method="GET" action="admin_uploaded_payment.php" style="display: inline;">
                        <input type="hidden" name="homeowner_id" value="<?php echo $homeowner_id; ?>">
                        <input type="hidden" name="page" value="<?php echo $current_page + 1; ?>">
                        <button type="submit">&gt;</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="myModal" class="modal">
        <span class="close">&times;</span>
        <img class="modal-content" id="img01">
        <div id="caption"></div>
    </div>

    <script>      const modal = document.getElementById('myModal');
        const modalImg = document.getElementById('img01');
        const captionText = document.getElementById('caption');
        const zoomableImages = document.querySelectorAll('.zoomable');

        zoomableImages.forEach(img => {
            img.onclick = function () {
                modal.style.display = 'block';
                modalImg.src = this.src;
                captionText.innerHTML = this.alt;
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
    </script>

</body>
</html>
