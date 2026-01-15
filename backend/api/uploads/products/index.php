<?php
// Serve uploaded product images
// This file handles requests like /api/uploads/products/filename.jpg

$filename = basename($_SERVER['REQUEST_URI']);
// Extract just the filename part (remove query string)
$filename = explode('?', $filename)[0];
$filename = explode('#', $filename)[0];

// Security: only allow alphanumeric, dots, hyphens, underscores
if (!preg_match('/^[a-zA-Z0-9._-]+$/', $filename)) {
    http_response_code(403);
    die('Forbidden');
}

// The actual files are stored in /backend/uploads/products/
// From /backend/api/uploads/products/ go up 3 levels to get /backend
$uploadsDir = __DIR__ . '/../../../../uploads/products/';
$filepath = $uploadsDir . $filename;

// Make sure the file is within the uploads directory (prevent directory traversal)
$realUploadDir = realpath($uploadsDir);
$realFilePath = realpath(dirname($filepath)) . '/' . basename($filepath);

if (!$realUploadDir || strpos($realFilePath, $realUploadDir) !== 0) {
    http_response_code(403);
    die('Forbidden');
}

if (!file_exists($filepath)) {
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
header('Content-Length: ' . filesize($filepath));

readfile($filepath);
exit;

