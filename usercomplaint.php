<?php
session_name('user_session'); 
session_start();


if (!isset($_SESSION['homeowner_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta charset="UTF-8">
    <title>User - Submit Complaint</title>
    <link rel="stylesheet" href="usersidebar.css">
    <link rel="stylesheet" href="usercomplaint.css">
</head>
<body>
    <?php include 'usersidebar.php'; ?>
<div class="main-content">
<div>

            <div class="container">
        <h1>Submit a Complaint</h1>
        <form action="submit_complaint.php" method="POST">

    <!-- Button Group for Subject -->
    <div class="button-group">
        <button class="btn active" id="maintenance" value="Neighbour" type="button">Neighbour</button>
        <button class="btn" id="installation" value="Monique Officials" type="button">Monique Officials</button>
        <button class="btn" id="road-repair" value="Service Man Performance" type="button">Service Man Performance</button>
    </div>

    <!-- Text Input for Subject -->
    <label for="subject">Subject:</label><br>
    <input type="text" id="subject" name="subject" readonly required><br><br>

    <!-- Hidden input to store the selected service type -->
    <input type="hidden" id="type" name="service_type" value="Maintenance">

    <label for="description">Description:</label><br>
    <textarea id="description" name="description" required></textarea><br><br>

    <input type="hidden" name="homeowner_id" value="<?php echo $_SESSION['homeowner_id']; ?>">

    <button type="submit">Submit</button>
</form>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const buttons = document.querySelectorAll(".button-group .btn");
        const subjectInput = document.getElementById('subject');

        // Set the default subject based on the active button
        subjectInput.value = document.querySelector(".button-group .btn.active").value;

        buttons.forEach(button => {
            button.addEventListener("click", function() {
                // Set the subject input value to the clicked button's value
                subjectInput.value = this.value;

                // Remove active class from all buttons
                buttons.forEach(btn => btn.classList.remove("active"));
                
                // Add active class to the clicked button
                this.classList.add("active");
            });
        });
    });
</script>

        
        <?php
        if (isset($_GET['success']) && $_GET['success'] == 'true') {
            echo "<p>Complaint submitted successfully!</p>";
        }
        ?>
        
        <a href="view_complaints.php">View Your Complaints</a>
    </div>   </div>   </div>
</body>
</html>
