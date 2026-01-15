<?php
// Simple image serving handler with debug info
error_reporting(0);
ini_set('display_errors', 0);

$filename = basename($_SERVER['REQUEST_URI']);
$filename = explode('?', $filename)[0];
$filename = explode('#', $filename)[0];

// Only allow safe filenames
if (!preg_match('/^[a-zA-Z0-9._-]+$/', $filename)) {
    http_response_code(403);
    die('Invalid');
}

// Try to find the file - search everywhere
$searchPaths = [
    __DIR__ . '/../../../../uploads/products/' . $filename,
    '/backend/uploads/products/' . $filename,
    '/tmp/uploads/products/' . $filename,
    '/var/www/html/diplomski/backend/uploads/products/' . $filename,
    '/var/www/html/api/uploads/products/' . $filename,
    '/app/uploads/products/' . $filename,
    '/home/uploads/products/' . $filename,
];

// Also try using glob to find the file anywhere under common roots
$globPaths = [];
foreach (['/backend', '/tmp', '/var/www/html', '/app', '/home'] as $root) {
    $globPaths = array_merge($globPaths, @glob($root . '/**/uploads/products/' . $filename, GLOB_BRACE));
}

$found = null;

// Check direct paths first
foreach ($searchPaths as $p) {
    if (@file_exists($p) && is_file($p)) {
        $found = $p;
        break;
    }
}

// Then check glob results
if (!$found && !empty($globPaths)) {
    foreach ($globPaths as $p) {
        if (is_file($p)) {
            $found = $p;
            break;
        }
    }
}

// Last resort - use find command to search
if (!$found) {
    $result = @shell_exec('find / -name "' . escapeshellarg($filename) . '" -type f 2>/dev/null | head -1');
    if ($result) {
        $found = trim($result);
    }
}

if (!$found) {
    http_response_code(404);
    header('Content-Type: application/json');
    die(json_encode(['error' => 'File not found', 'searched' => count($searchPaths) + count($globPaths)]));
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



