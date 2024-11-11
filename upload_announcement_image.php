<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define the target directory for image uploads
$targetDirectory = "uploads/"; // Make sure this folder exists and is writable
$imageFile = $_FILES["imageFile"];

// Get the file name and path
$fileName = basename($imageFile["name"]);
$targetFilePath = $targetDirectory . uniqid() . '_' . $fileName; // Make the file name unique
$imageFileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

// Check if the file is a valid image (optional)
if (getimagesize($imageFile["tmp_name"]) === false) {
    header("Location: dashadmin.php?error=invalid_image");
    exit;
}

// Check if the file already exists in the target directory
if (file_exists($targetFilePath)) {
    header("Location: dashadmin.php?error=file_exists");
    exit;
}

// Check file size (optional)
if ($imageFile["size"] > 5000000) { // 5MB max size
    header("Location: dashadmin.php?error=file_too_large");
    exit;
}

// Allow only specific file formats (optional)
if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
    header("Location: dashadmin.php?error=invalid_format");
    exit;
}

// Try to upload the file
if (move_uploaded_file($imageFile["tmp_name"], $targetFilePath)) {
    // Now insert the file path into the database
    $servername = "localhost";
    $username = "u780935822_homeowner";
    $password = "Boot@o29";
    $dbname = "u780935822_homeowner";

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Prepare and bind the statement to insert the file path into the database
    $stmt = $conn->prepare("INSERT INTO announcement_images (file_path) VALUES (?)");
    $stmt->bind_param("s", $targetFilePath); // Insert the file path into the database
    if ($stmt->execute()) {
        // Redirect to the dashboard without any message
        header("Location: dashadmin.php");
        exit;
    } else {
        // If there's an error in inserting, redirect with an error message
        header("Location: dashadmin.php?error=db_error");
        exit;
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
} else {
    // If the file upload failed, redirect with an error
    header("Location: dashadmin.php?error=upload_error");
    exit;
}
?>
