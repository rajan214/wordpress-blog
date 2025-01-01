<?php
header('Content-Type: application/json');

function process_post_request($url, $id) {
    $wp_api_url = 'https://teraboxvideoplayer.one/wp-json/wp/v2/';
    
    
    $existing_posts = get_all_posts($id);
    if ($existing_posts === 'true') {
        return ['status' => 'error', 'message' => 'Post already exists'];
    }

    $og_data = extract_open_graph_data($url);

    if (!$og_data['ogImage']) {
        return ['status' => 'error', 'message' => 'No image found in Open Graph data'];
    }
    $image_url = upload_image_to_wordpress($og_data['ogImage'], $id);
    
    $post_data = [
        'title' => $og_data['ogTitle'],
        'content' => generate_post_content( $url, $image_url['image_url']),
        'slug' => $id,
        'status' => 'publish',
        'featured_media' => $image_url['id']
    ];
    

    $response = send_post_to_wordpress( $post_data);

    return ['status' => 'success', 'message' => 'Post added successfully', 'data' => $response];
}

function extract_open_graph_data($url) {
    $html = file_get_contents($url);
    $og_title = '';
    $og_image = '';

    if (preg_match('/<meta property="og:title" content="([^"]+)"/', $html, $matches)) {
        $og_title = $matches[1];
    }
    if (empty($og_title) && preg_match('/<meta name="og:title" content="([^"]+)"/', $html, $matches)) {
        $og_title = $matches[1];
    }
    if (preg_match('/<meta property="og:image" content="([^"]+)"/', $html, $matches)) {
        $og_image = $matches[1];
    }
    if ($og_title) {
        $og_title = preg_replace('/(.*\.(mkv|mp4|avi|mov|wmv|flv|webm|mpeg|mpg|3gp|ogg|mpeg4))[^a-zA-Z0-9]*.*/i', '$1', $og_title);
    }

    return ['ogTitle' => $og_title, 'ogImage' => $og_image];
}


function upload_image_to_wordpress($base64Image,$id) {

    $imageUrl = $base64Image;

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
        'file' => new CURLFile('data://text/plain;base64,' . $base64Image, 'image/jpeg', $id.'.jpg')
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
       
        $imageData = json_decode($response, true);

    }

    // Close cURL
    curl_close($ch);

    $image_data = array('id'=>$imageData['id'],'image_url'=>$imageData['source_url']);
    return $image_data; 

}



function send_post_to_wordpress($data) {

    $url = 'https://teraboxvideoplayer.one/wp-json/wp/v2/posts';
    $jsonData = json_encode($data);
    
    $headers = [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode('NewProfile:buymTndaSYu6GgBaC8UyFn32')
    ];

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url, 
        CURLOPT_RETURNTRANSFER => true, 
        CURLOPT_POST => true, 
        CURLOPT_POSTFIELDS => $jsonData,
        CURLOPT_HTTPHEADER => $headers,
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'cURL Error: ' . curl_error($ch);
    }

    curl_close($ch);

    return json_decode($response, true);
}


function generate_post_content($url, $image_url) {
    return "
        <img src='{$image_url}' alt='Image from the link' /><br />
        <p>📮 𝐅𝐮𝐥𝐥 𝐕𝐢𝐝𝐞𝐨 𝐋𝐢𝐧𝐤 📮</p>
        <p>
            <a href='{$url}' target='_blank'>
                Watch Full Video Online
            </a>
        </p>
    ";
}


function get_all_posts($id) {

    $ch = curl_init();

    $url = 'https://teraboxvideoplayer.one/wp-json/wp/v2/posts';

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true, 
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json', 
        ]
    ]);

    $response = curl_exec($ch);

    if(curl_errno($ch)) {
        echo 'cURL Error: ' . curl_error($ch);
        curl_close($ch);
        return;
    }

    curl_close($ch);

    
    $posts = json_decode($response, true);

    $slugs = [];

    
    foreach ($posts as $post) {
        if (isset($post['slug'])) {
            $slugs[] = $post['slug']; 
        }
    }
    $slugs_lower = array_map('strtolower', $slugs);

    if (in_array(strtolower($id), $slugs_lower))
    {
        $resp = 'true';
    }else
    {
        $resp = 'false';
    }

    return $resp;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['url']) && isset($input['id'])) {
        $response = process_post_request($input['url'], $input['id']);
        echo json_encode($response);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>
