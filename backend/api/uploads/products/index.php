<?php
// Serve uploaded product images
// This file handles requests like /api/uploads/products/filename.jpg

$filename = basename($_SERVER['REQUEST_URI']);
$filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename); // Sanitize

// The actual files are stored in /backend/uploads/products/
$baseDir = dirname(dirname(dirname(dirname(__DIR__))));
$filepath = $baseDir . '/backend/uploads/products/' . $filename;

// Security: don't allow path traversal
if (strpos(realpath($filepath) ?: '', realpath(dirname($filepath))) !== 0) {
    http_response_code(403);
    die('Forbidden');
}

if (!file_exists($filepath)) {
    http_response_code(404);
    header('Content-Type: application/json');
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
