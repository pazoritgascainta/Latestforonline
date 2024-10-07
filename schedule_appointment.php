<?php
// schedule_appointment.php
$servername = "localhost";
$username = "u780935822_homeowner";
$password = "Boot@o29";
$dbname = "u780935822_homeowner";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Sanitize input data
$date = $_POST['date'];
$time = $_POST['time'];
$name = $_POST['name'];
$email = $_POST['email'];
$purpose = $_POST['purpose'];

// Insert appointment into database
$sql = "INSERT INTO appointments (date, time, name, email, purpose, status) 
        VALUES ('$date', '$time', '$name', '$email', '$purpose', 'Pending')";

if ($conn->query($sql) === TRUE) {
    echo "<script>alert('Appointment scheduled successfully!'); window.location.href = 'index.php';</script>";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
