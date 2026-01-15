<?php
// Simple image serving handler
error_reporting(0);

$filename = basename($_SERVER['REQUEST_URI']);
$filename = explode('?', $filename)[0];
$filename = explode('#', $filename)[0];

// Only allow safe filenames
if (!preg_match('/^[a-zA-Z0-9._-]+$/', $filename)) {
    http_response_code(403);
    die('Invalid');
}

// Try to find the file in common locations
$searchPaths = [
    __DIR__ . '/../../../../uploads/products/' . $filename,
    '/backend/uploads/products/' . $filename,
    '/tmp/uploads/products/' . $filename,
    '/var/www/html/diplomski/backend/uploads/products/' . $filename,
    '/var/www/html/api/uploads/products/' . $filename,
];

$found = null;
foreach ($searchPaths as $p) {
    if (@file_exists($p)) {
        $found = $p;
        break;
    }
}

if (!$found) {
    http_response_code(404);
    die('Not found');
}

// Serve the file
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$types = ['jpg'=>'image/jpeg', 'jpeg'=>'image/jpeg', 'png'=>'image/png', 'gif'=>'image/gif', 'webp'=>'image/webp'];
$type = $types[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $type);
header('Content-Length: ' . filesize($found));
header('Cache-Control: public, max-age=31536000');
header('Access-Control-Allow-Origin: *');

readfile($found);
exit;



