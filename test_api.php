<?php
require 'src/Modules/MediaGatekeeper/DescreveAIClient.php';

// Shims for WP functions
function is_wp_error($thing) { return false; }
function wp_remote_retrieve_response_code($response) { return $response['response']['code']; }
function wp_remote_retrieve_body($response) { return $response['body']; }

function wp_remote_post($url, $args) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $args['body']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $headers = [];
    foreach ($args['headers'] as $k => $v) {
        $headers[] = $k . ': ' . $v;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, $args['timeout'] ?? 15);

    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'response' => ['code' => $code],
        'body' => $body
    ];
}

// Create a dummy image file
$imgPath = sys_get_temp_dir() . '/test_image.jpg';
file_put_contents($imgPath, base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA='));

$client = new \WpAcessivelJinc\Modules\MediaGatekeeper\DescreveAIClient();
$result = $client->analyze(
    $imgPath, 
    'http://127.0.0.1:3000/api/analyze', 
    'jinc_dev_token', 
    15
);

echo "Result:\n";
echo json_encode($result, JSON_PRETTY_PRINT);
echo "\n";
