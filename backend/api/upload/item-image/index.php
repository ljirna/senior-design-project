<?php
// CRITICAL: Set all headers and error handling FIRST
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authentication, Authorization');

// Prevent any HTML error output
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start output buffering BEFORE anything else that might output
ob_start();

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Load dependencies with try-catch
try {
    $baseDir = dirname(dirname(dirname(__DIR__)));  // Go up to backend directory
    $autoloadPath = $baseDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
    $configPath = $baseDir . DIRECTORY_SEPARATOR . 'config.php';

    if (!file_exists($autoloadPath)) {
        ob_end_clean();
        http_response_code(500);
        die(json_encode(["success" => false, "message" => "Autoload not found"]));
    }
    if (!file_exists($configPath)) {
        ob_end_clean();
        http_response_code(500);
        die(json_encode(["success" => false, "message" => "Config not found"]));
    }

    require_once $autoloadPath;
    require_once $configPath;
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    die(json_encode(["success" => false, "message" => "Error loading dependencies: " . $e->getMessage()]));
}

// Clear any output buffered during includes
ob_end_clean();

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Get token from headers
    $token = null;
    if (!empty($_SERVER['HTTP_AUTHENTICATION'])) {
        $token = $_SERVER['HTTP_AUTHENTICATION'];
    } elseif (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        if (strpos($authHeader, 'Bearer ') === 0) {
            $token = substr($authHeader, 7);
        } else {
            $token = $authHeader;
        }
    }

    if (!$token) {
        http_response_code(401);
        die(json_encode(["success" => false, "message" => "No token"]));
    }

    // Verify token
    $decoded = JWT::decode($token, new Key(Config::JWT_SECRET(), 'HS256'));

    // Check admin - handle different JWT structures
    $isAdmin = false;
    if (isset($decoded->user)) {
        // Check if role is in user object
        $user = $decoded->user;
        if (is_object($user) && isset($user->role)) {
            $isAdmin = ($user->role === 'admin');
        } elseif (is_array($user) && isset($user['role'])) {
            $isAdmin = ($user['role'] === 'admin');
        }
    } elseif (isset($decoded->role)) {
        // Check if role is at top level
        $isAdmin = ($decoded->role === 'admin');
    }

    if (!$isAdmin) {
        http_response_code(403);
        die(json_encode(["success" => false, "message" => "Admin only"]));
    }

    // Check file
    if (!isset($_FILES["itemImage"]) || $_FILES["itemImage"]["error"] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        die(json_encode(["success" => false, "message" => "No file or upload error"]));
    }

    $file = $_FILES["itemImage"];

    // Validate image using getimagesize
    $imageInfo = @getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        http_response_code(400);
        die(json_encode(["success" => false, "message" => "Not a valid image"]));
    }

    // Check MIME type
    $mimeType = $imageInfo['mime'] ?? '';
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mimeType, $allowed)) {
        http_response_code(400);
        die(json_encode(["success" => false, "message" => "Image type not allowed"]));
    }

    if ($file['size'] > 5242880) {
        http_response_code(400);
        die(json_encode(["success" => false, "message" => "File too large"]));
    }

    // Save file - use backend uploads directory
    $dir = __DIR__ . "/../../uploads/products/";
    if (!file_exists($dir)) {
        if (!@mkdir($dir, 0777, true)) {
            http_response_code(500);
            die(json_encode(["success" => false, "message" => "Cannot create upload directory"]));
        }
    }

    // Make sure directory is writable
    if (!is_writable($dir)) {
        @chmod($dir, 0777);
    }

    $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $name = uniqid() . "." . $ext;
    $path = $dir . $name;

    if (move_uploaded_file($file["tmp_name"], $path)) {
        // Determine the correct protocol - check multiple sources
        $protocol = 'http';
        
        // Check for HTTPS
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $protocol = 'https';
        }
        // Check X-Forwarded-Proto header (common with reverse proxies)
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
        }
        // Check X-Forwarded-SSL header
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
            $protocol = 'https';
        }
        
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // Check if this is production (no /diplomski in the path) or local
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($request_uri, '/diplomski/') !== false) {
            // Local development
            $imageUrl = $protocol . '://' . $host . '/diplomski/api/uploads/products/' . $name;
        } else {
            // Production
            $imageUrl = $protocol . '://' . $host . '/api/uploads/products/' . $name;
        }
        
        // Log successful upload
        error_log("Image uploaded successfully: " . $name . " to " . $path . " URL: " . $imageUrl);
        
        http_response_code(200);
        die(json_encode([
            "success" => true,
            "filename" => $name,
            "path" => $imageUrl
        ]));
    }

    // Log upload failure
    error_log("Failed to move uploaded file from " . $file["tmp_name"] . " to " . $path);
    http_response_code(500);
    die(json_encode(["success" => false, "message" => "Failed to save file"]));
} catch (Throwable $e) {
    http_response_code(500);
    die(json_encode(["success" => false, "message" => $e->getMessage()]));
}
