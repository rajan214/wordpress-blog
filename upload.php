<?php
header('Content-Type: application/json');

// Check if the file was uploaded
if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    // Set the upload directory
    $uploadDir = 'uploads/';

    // Make sure the uploads directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);  // Create the directory if it doesn't exist
    }

    // Get the original file extension
    $fileExtension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);

    // Create a unique file name using the current timestamp
    $uniqueFileName = uniqid() . '.jpg';  // Force it to be a .jpg file

    // Define the path where the file will be saved
    $uploadFile = $uploadDir . $uniqueFileName;

    // Move the uploaded file to the upload directory
    if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadFile)) {
        // Return the URL of the uploaded file
        $fileUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/wordpressdemo'.'/' . $uploadFile;

        echo json_encode(['fileUrl' => $fileUrl]);  // Send back the file URL
    } else {
        echo json_encode(['error' => 'File upload failed']);
    }
} else {
    echo json_encode(['error' => 'No file uploaded or there was an upload error']);
}
?>
