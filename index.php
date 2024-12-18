<?php
session_name('user_session'); 
session_start();

// Database connection setup
$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to get all images from the announcement_images table
$sql = "SELECT file_path FROM announcement_images ORDER BY uploaded_at DESC"; // Get all images sorted by uploaded_at
$result = $conn->query($sql);

// Store images in an array
$images = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $images[] = htmlspecialchars($row['file_path']);  // Store each image file path in the $images array
    }
}

// Query to get latest announcements (optional if you want to display them too)
$announcementsQuery = "SELECT * FROM announcements ORDER BY date DESC LIMIT 5";
$announcementsResult = $conn->query($announcementsQuery);

// Handle login logic
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    $sql = "SELECT id, name, password, status FROM homeowners WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $name, $hashed_password, $status);

    if ($stmt->num_rows > 0) {
        $stmt->fetch();
        if ($status === 'archived') {
            $error = "Your account has been archived and cannot be accessed.";
        } elseif (password_verify($password, $hashed_password)) {
            $_SESSION['homeowner_id'] = $id;
            $_SESSION['homeowner_name'] = $name;
            header("Location: dashuser.php");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
    }

    $stmt->close();
}

$conn->close();

// Display logout message if redirected from logout
$logout_message = isset($_GET['message']) && $_GET['message'] == 'loggedout' ? "You have been logged out successfully." : '';
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="HomepageStyle.css">
    <link rel="stylesheet" href="HomepageImgSlider.css">
    <link rel="stylesheet" href="HomepageAnnouncement.css">
    <link rel="stylesheet" href="HomepageaboutUs.css">
    <link rel="stylesheet" href="HomepageHeader.css">
    <link rel="stylesheet" href="HomepageLoginForm.css">
    <link rel="stylesheet" href="HomepageAmenities.css">
    <link rel="stylesheet" href="HomepageLogo.css">
    <title>St. Monique</title>
</head>

<body>
    <?php session_start(); ?>
    
    <!-- Check for session message -->
    <script>
        <?php if (isset($_SESSION['message'])): ?>
            alert("<?php echo $_SESSION['message']; ?>");
            <?php unset($_SESSION['message']); // Clear the message after showing it ?>
        <?php endif; ?>
    </script>

    <div class="logo">
        <p>St. Monique Valais Homeowners' Association</p>
    </div>
    <section class="HomeLogo">
        <div class="logoo">
            <a href="index.php"><img src="monique logo.png" alt="Your Logo"></a>
        </div>
    </section>
    <header>
        <nav>
            <!-- Burger icon for mobile -->
            <div class="burger-menu" id="burgerMenu" onclick="toggleMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>

            <ul id="navLinks">
                <li><a href="index.php">Home</a></li>
                <li><a href="#" id="loginBtn">Login</a></li>
                <li><a href="ContacUS.php">Contact</a></li>
                <li><a href="Amenities.php">Amenities</a></li>
            </ul>
        </nav>
    </header>

    <div id="backdrop" style="display: none;"></div>

    <div class="container" id="container" style="display: none;">
        <div class="form-container sign-in">
            <form method="POST">
                <img id="mobileLogo" src="monique logo.png" alt="St Monique Valais Logo">
                <h1>Sign In</h1>
                <span>use your email & password provided by Admin</span>

                <!-- Email input field -->
                <input type="email" name="email" placeholder="Email" required>

                <!-- Password input field -->
                <input type="password" name="password" placeholder="Password" required>

                <!-- Display error messages, if any -->
                <?php if (!empty($error)): ?>
                    <div style="color: red;"><?= htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if (!empty($logout_message)): ?>
                    <div style="color: green;"><?= htmlspecialchars($logout_message); ?></div>
                <?php endif; ?>

                <a href="ForgetPw.php" class="forgetpw">Forgot Your Password?</a>

                <!-- Submit button -->
                <button type="submit">Login</button>
            </form>
        </div>
        <div class="toggle-container">
            <div class="toggle">
                <div class="toggle-panel toggle-left">
                </div>
                <div class="exit-btn-container">
                    <button id="exitBtn" class="exit-btn" style="display: none;">X</button> <!-- Exit button -->
                </div>
                <div class="toggle-panel toggle-right">
                    <h1>Welcome to St. Monique Valais!</h1>
                    <p>Discover the perfect blend of luxury and community living.</p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>

    <script>
    const loginBtn = document.getElementById("loginBtn");
    const toggleContainer = document.querySelector(".toggle-container");

    loginBtn.addEventListener("click", function(e) {
        e.preventDefault();
        
        // Ensure any previous animations are reset
        toggleContainer.classList.remove("shrink");
        
        // Show the container at full height immediately
        toggleContainer.classList.add("show");

        // Wait 2 seconds, then apply the shrink animation
        setTimeout(() => {
            toggleContainer.classList.add("shrink");
        }, 300); // 2 seconds delay
    });
</script>


    <script>
        document.getElementById("loginBtn").addEventListener("click", function() {
    document.getElementById("loginContainer").style.display = "block";
});

document.getElementById("closeBtn").addEventListener("click", function() {
    document.getElementById("loginContainer").style.display = "none";
});

// Optional: close the modal when clicking outside the form
window.addEventListener("click", function(event) {
    var container = document.getElementById("loginContainer");
    if (event.target === container) {
        container.style.display = "none";
    }
});
    </script>
    <!-- Img Slider Section -->
    <section class="ImageSlider1">
    <div class="ImageSlider">
        <input type="radio" id="trigger1" name="slider" checked autofocus>
        <label for="trigger1"><span class="sr-only"></span></label>
        <div class="slide bg1">
            <div class="description">
                <h3>St.Monique Landmark</h3>
            </div>
        </div>

        <input type="radio" id="trigger2" name="slider">
        <label for="trigger2"><span class="sr-only"></span></label>
        <div class="slide bg2">
            <div class="description">
                <h3>St.Monique School</h3>
            </div>
        </div>

        <input type="radio" id="trigger3" name="slider">
        <label for="trigger3"><span class="sr-only"></span></label>
        <div class="slide bg3">
            <div class="description">
                <h3>St.Monique Church</h3>
            </div>
        </div>

        <input type="radio" id="trigger4" name="slider">
        <label for="trigger4"><span class="sr-only"></span></label>
        <div class="slide bg4">
            <div class="description">
                <h3>Clubhouse</h3>
            </div>
        </div>

        <input type="radio" id="trigger5" name="slider">
        <label for="trigger5"><span class="sr-only"></span></label>
        <div class="slide bg5">
            <div class="description">
                <h3>St.Monique Valais</h3>
            </div>
        </div>
    </div>
</section>

     <!-- Images Highlights -->
    <section class="Amenities1">
        <div class="Amenities">
            <h2>St Monique Valais' Amenities</h2>
            <ul>
                <li>
                    <img src="SwimmingPool.jpg" alt="Swimming Pool">
                    <div class="text">
                        <h3>Swimming Pool</h3>
                    </div>
                </li>
                <li><img src="courtth1.jpg" alt="Basketball Court">
                    
                    <div class="text">
                        <h3>Basketball Court</h3>
                    </div>
                </li>
                <li><img src="Playground.jpg" alt="Playground">
                    <div class="text">
                        <h3>Playground</h3>
                    </div>
                </li>
                <li><img src="clubhouse.jpg" alt="Clubhouse">
                    <div class="text">
                        <h3>Cafe Delfino</h3>
                    </div>
                </li>                            
            </ul>
        </div>
    </section>
    <h1 style="text-align: center;">St.Monique Valais HOA Drone Fly Over</h1>
    <div class="video-container">
<video autoplay muted controls>
    <source src="StMoniqueValais2.mp4" type="video/mp4">
    Your browser does not support the video tag.
</video>
</div>


    <div class="announcement-container">
        <div class="announcement-main">
            <div class="carousel">
                <div class="carousel-inner">
    <?php
    if (count($images) > 0) {
        $first = true;
        foreach ($images as $image) {
            $activeClass = $first ? 'active' : '';
            echo '<div class="carousel-item ' . $activeClass . '">';
            echo '<img src="' . $image . '" alt="Announcement Image">';
            echo '</div>';
            $first = false;
        }
    }
    ?>
</div>
                
                <a class="carousel-control-prev" role="button">
                    <span class="carousel-control-prev-icon" aria-hidden="false">&lt;</span>
                </a>
                <a class="carousel-control-next" role="button">
                    <span class="carousel-control-next-icon" aria-hidden="true">&gt;</span>
                </a>
            </div>
        </div>

        <div class="announcement-news">
    <h3>Latest Announcements</h3>
    <ul>
        <?php if ($announcementsResult->num_rows > 0): ?>
            <?php while ($row = $announcementsResult->fetch_assoc()): ?>
                <li>
                    <strong><?php echo date('F d, Y', strtotime($row['date'])); ?></strong>: 
                    <?php echo htmlspecialchars($row['content']); ?>
                </li>
            <?php endwhile; ?>
        <?php else: ?>
            <li>No announcements at the moment.</li>
        <?php endif; ?>
    </ul>
        </div>
</div>
    <footer>
        <div class="footer-container">
            <div id="aboutUs">
                <h2>About Us</h2>
                <img src="monique logo.png" width="500" height="225" alt="St Monique Valais Logo">
                <h2>St Monique Valais Homeowners Association</h2>
                <p>Welcome to St. Monique Valais, a beacon of modern living nestled in the heart of our region.
                    Established in 2005 by a visionary in the real estate industry, our community stands as a testament to meticulous planning, upscale amenities, and well-designed homes.
                     Our residents don’t just live here they actively participate in shaping the community through decision-making processes and engaging activities.
                     Experience the hallmark of modern living at St. Monique Valais!.</p>
        </div>
            <div class="location-map">
                <h2>Our Location</h2>
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3860.2986102152097!2d121.18476209999999!3d14.509480000000001!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397c16a37b4ca23%3A0x747fd5298859a7a7!2sSaint%20Monique%20Valais!5e0!3m2!1sen!2suk!4v1631086053421!5m2!1sen!2suk" width="850" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            </div>
        </div>
    </footer>

    <div class="footer">
        <p>© 2024 St. Monique Valais Homeowners Association. All rights reserved.
        </p>
    </div>
    <script>     
    function toggleMenu() {
        // Toggle the active class for burger menu and navLinks
        const burgerMenu = document.getElementById('burgerMenu');
        const navLinks = document.getElementById('navLinks');
        burgerMenu.classList.toggle('active');
        navLinks.classList.toggle('active');
    } </script>
    <script src="HomepageJS.js"></script>
</body>

</html>

</body>
</html>
