<?php
// Simple image serving handler with debug info
error_reporting(0);
ini_set('display_errors', 0);

$filename = basename($_SERVER['REQUEST_URI']);
$filename = explode('?', $filename)[0];
$filename = explode('#', $filename)[0];

// Log the request with full context
$logEntry = date('Y-m-d H:i:s') . " - Request: " . $filename . " | CWD: " . getcwd() . " | __DIR__: " . __DIR__;
file_put_contents('/tmp/image_requests.log', $logEntry . "\n", FILE_APPEND);

// Only allow safe filenames
if (!preg_match('/^[a-zA-Z0-9._-]+$/', $filename)) {
    http_response_code(403);
    file_put_contents('/tmp/image_requests.log', date('Y-m-d H:i:s') . " - Invalid filename: " . $filename . "\n", FILE_APPEND);
    die('Invalid');
}

// Try to find the file - search everywhere
$searchPaths = [
    __DIR__ . '/../../uploads/products/' . $filename,
    __DIR__ . '/../../../../uploads/products/' . $filename,
    '/backend/uploads/products/' . $filename,
    '/tmp/uploads/products/' . $filename,
    '/var/www/html/diplomski/backend/uploads/products/' . $filename,
    '/var/www/html/api/uploads/products/' . $filename,
    '/app/uploads/products/' . $filename,
    '/home/uploads/products/' . $filename,
    getcwd() . '/uploads/products/' . $filename,
    dirname(getcwd()) . '/uploads/products/' . $filename,
];

$found = null;

// Check each path and log it
file_put_contents('/tmp/image_requests.log', date('Y-m-d H:i:s') . " - Searching paths:\n", FILE_APPEND);
foreach ($searchPaths as $p) {
    $exists = @file_exists($p);
    $isFile = $exists && is_file($p);
    $log = date('Y-m-d H:i:s') . "   - " . $p . " (exists=" . ($exists ? "YES" : "NO") . ", isFile=" . ($isFile ? "YES" : "NO");
    if ($exists && $isFile) {
        $log .= ", SIZE=" . filesize($p);
    }
    $log .= ")\n";
    file_put_contents('/tmp/image_requests.log', $log, FILE_APPEND);
    
    if ($isFile) {
        $found = $p;
        file_put_contents('/tmp/image_requests.log', date('Y-m-d H:i:s') . " - FOUND at: " . $p . "\n", FILE_APPEND);
        break;
    }
}

if (!$found) {
    http_response_code(404);
    header('Content-Type: application/json');
    file_put_contents('/tmp/image_requests.log', date('Y-m-d H:i:s') . " - FILE NOT FOUND after checking all paths\n", FILE_APPEND);
    
    // List directory contents for debugging
    $debugDir = __DIR__ . '/../../uploads/products/';
    if (is_dir($debugDir)) {
        $files = @scandir($debugDir);
        file_put_contents('/tmp/image_requests.log', date('Y-m-d H:i:s') . " - Directory listing of " . $debugDir . ": " . json_encode($files) . "\n", FILE_APPEND);
    }
    
    die(json_encode(['error' => 'File not found']));
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



