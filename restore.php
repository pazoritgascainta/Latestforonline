<?php
if (isset($_GET["id"])) {
    $id = $_GET["id"];

    $servername = "localhost";
    $username = "u780935822_homeowner";
    $password = "Boot@o29";
    $dbname = "u780935822_homeowner";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $sql = "UPDATE homeowners SET status='active' WHERE id=$id";

    if ($conn->query($sql) === TRUE) {
        echo "Record restored successfully";
    } else {
        echo "Error restoring record: " . $conn->error;
    }

    $conn->close();
}
header("location: homeowneradmin.php");
exit;
?>
