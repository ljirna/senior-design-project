<?php
// Allow access to subdirectories like /products/
// This file only blocks direct access to /api/uploads/ without a specific file
$request_uri = $_SERVER['REQUEST_URI'] ?? '';

// If accessing a subdirectory or file, allow it through to the subdirectory handler
if (preg_match('/\/uploads\/\w+\//', $request_uri)) {
    // Let the subdirectory handle it
    return;
}

// Otherwise block direct access to /uploads/
http_response_code(403);
die('Forbidden');
