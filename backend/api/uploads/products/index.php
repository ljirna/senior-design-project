<?php
// Serve uploaded product images
// This file handles requests like /api/uploads/products/filename.jpg

$filename = basename($_SERVER['REQUEST_URI']);
// Extract just the filename part (remove query string)
$filename = explode('?', $filename)[0];
$filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename); // Sanitize

// The actual files are stored in /backend/uploads/products/
// From /backend/api/uploads/products/ go up 3 levels to get /backend
$filepath = __DIR__ . '/../../../../uploads/products/' . $filename;

// Resolve the real path
$realpath = realpath($filepath);
$uploadsDir = realpath(__DIR__ . '/../../../../uploads/products/');

// Security: don't allow path traversal
if (!$realpath || strpos($realpath, $uploadsDir) !== 0) {
    http_response_code(403);
    die('Forbidden');
}

if (!file_exists($realpath)) {
    http_response_code(404);
    header('Content-Type: application/json');
    die(json_encode(['error' => 'File not found: ' . $filename]));
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
header('Content-Length: ' . filesize($realpath));

readfile($realpath);
exit;
