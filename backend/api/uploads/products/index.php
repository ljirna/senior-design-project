<?php
// Serve uploaded product images
error_reporting(0);
ini_set('log_errors', 1);

// Get the requested filename from the URL
$filename = basename($_SERVER['REQUEST_URI']);
$filename = explode('?', $filename)[0];
$filename = explode('#', $filename)[0];

// Log the request
error_log("Image request: " . $_SERVER['REQUEST_URI'] . " | filename: " . $filename);

// Security: only allow safe filenames (no path traversal)
if (!preg_match('/^[a-zA-Z0-9._-]+$/', $filename)) {
    http_response_code(403);
    error_log("Invalid filename: " . $filename);
    header('Content-Type: text/plain');
    die('Invalid filename');
}

// Try multiple possible locations
$possiblePaths = [
    __DIR__ . '/../../../../uploads/products/' . $filename,  // From /backend/api/uploads/products/
    dirname(__DIR__) . '/uploads/products/' . $filename,      // From /backend/api/uploads/
    dirname(dirname(__DIR__)) . '/uploads/products/' . $filename,  // From /backend/api/
];

error_log("Checking paths: " . json_encode($possiblePaths));

$filepath = null;
foreach ($possiblePaths as $path) {
    error_log("Checking: " . $path . " exists=" . (file_exists($path) ? 'YES' : 'NO'));
    if (file_exists($path)) {
        $filepath = $path;
        error_log("Found file at: " . $filepath);
        break;
    }
}

if (!$filepath) {
    http_response_code(404);
    header('Content-Type: application/json');
    error_log("File not found for: " . $filename);
    die(json_encode(['error' => 'File not found']));
}

// Set appropriate content type
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$contentTypes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp'
];

$contentType = $contentTypes[$ext] ?? 'application/octet-stream';
header('Content-Type: ' . $contentType);
header('Cache-Control: public, max-age=31536000');
header('Access-Control-Allow-Origin: *');
header('Content-Length: ' . filesize($filepath));

readfile($filepath);
exit;


