<?php
header('Content-Type: application/json');

$result = [
    'curl_loaded' => extension_loaded('curl'),
    'curl_version_exists' => function_exists('curl_version')
];

if (function_exists('curl_version')) {
    $v = curl_version();
    $result['curl_version'] = $v['version'];
} else {
    $result['error'] = 'curl_version function does not exist';
}

echo json_encode($result, JSON_PRETTY_PRINT);
