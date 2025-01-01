

<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure 'url' is set in the form data
    if (isset($_POST['url'])) {
        // The URL from the form
        $imageUrl = $_POST['url'];

        // Fetch the image data from the provided URL
        $imageData = file_get_contents($imageUrl);
        if ($imageData === false) {
            die('Failed to retrieve image.');
        }

        // Encode the image to base64
        $base64Image = base64_encode($imageData);

        // WordPress REST API endpoint
        $url = 'https://teraboxvideoplayer.one/wp-json/wp/v2/media';

        // Headers including the correct authorization
        $headers = [
            'Authorization: Basic ' . base64_encode('NewProfile:buymTndaSYu6GgBaC8UyFn32')
        ];

        // Data for the POST request (multipart/form-data)
        $data = [
            'file' => new CURLFile('data://text/plain;base64,' . $base64Image, 'image/jpeg', 'image.jpg')
        ];

        // Initialize cURL
        $ch = curl_init($url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        // Execute cURL request
        $response = curl_exec($ch);

        // Check for errors
        if (curl_errno($ch)) {
            echo 'Error: ' . curl_error($ch);
        } else {
            // Output the response from the server
            echo $response;
        }

        // Close cURL
        curl_close($ch);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'URL not provided']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

?>
<?php

// Path to the file
// $filePath = 'C:/Users/Admin/Downloads/banner.jpg';

// // The API URL
// $url = 'https://teraboxvideoplayer.one/wp-json/wp/v2/media';

// // The Authorization header
// $headers = [
//     'Authorization: Basic bmV3MTIzOiE3c0skbWpVJUpKRWZAcnReWXpZIUtNeg=='  // Replace with your actual base64 credentials
// ];

// // Initialize cURL
// $ch = curl_init();

// // Prepare the file for upload
// $cfile = new CURLFile($filePath, 'image/jpeg', 'banner.jpg');

// // Set cURL options
// curl_setopt($ch, CURLOPT_URL, $url);
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
// curl_setopt($ch, CURLOPT_POST, true);
// curl_setopt($ch, CURLOPT_POSTFIELDS, [
//     'file' => $cfile,  // Attach the file
// ]);

// // Execute the cURL request
// $response = curl_exec($ch);

// // Check for errors
// if (curl_errno($ch)) {
//     echo 'Error: ' . curl_error($ch);
// } else {
//     // Output the response
//     echo $response;
// }

// // Close the cURL session
// curl_close($ch);

?>

