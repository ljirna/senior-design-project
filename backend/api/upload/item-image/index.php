<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Set headers FIRST, before any includes
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Start output buffering to catch any errors from includes
ob_start();
ini_set('display_errors', 0);

// Load dependencies
try {
    $baseDir = dirname(dirname(dirname(__DIR__)));  // Go up to backend directory
    $autoloadPath = $baseDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
    $configPath = $baseDir . DIRECTORY_SEPARATOR . 'config.php';

    if (!file_exists($autoloadPath)) {
        throw new Exception("Autoload file not found at: " . $autoloadPath);
    }
    if (!file_exists($configPath)) {
        throw new Exception("Config file not found at: " . $configPath);
    }

    require_once $autoloadPath;
    require_once $configPath;
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    die(json_encode(["success" => false, "message" => $e->getMessage()]));
}

// Clear any output from includes
ob_end_clean();

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
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
        die(json_encode(["success" => false, "message" => "No file"]));
    }

    $file = $_FILES["itemImage"];

    // Validate image using getimagesize (more universal than finfo)
    $imageInfo = @getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        http_response_code(400);
        die(json_encode(["success" => false, "message" => "Not a valid image"]));
    }

    // Check MIME type from getimagesize
    $mimeType = $imageInfo['mime'] ?? '';
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mimeType, $allowed)) {
        http_response_code(400);
        die(json_encode(["success" => false, "message" => "Image type not allowed"]));
    }

    if ($file['size'] > 5242880) {
        http_response_code(400);
        die(json_encode(["success" => false, "message" => "Too large"]));
    }

    // Save file
    $dir = __DIR__ . "/../../../../frontend/assets/img/products/";
    if (!file_exists($dir)) {
        if (!mkdir($dir, 0777, true)) {
            http_response_code(500);
            die(json_encode(["success" => false, "message" => "Cannot create directory"]));
        }
    }

    $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $name = uniqid() . "." . $ext;
    $path = $dir . $name;

    if (move_uploaded_file($file["tmp_name"], $path)) {
        die(json_encode([
            "success" => true,
            "filename" => $name,
            "path" => "assets/img/products/" . $name
        ]));
    }

    http_response_code(500);
    die(json_encode(["success" => false, "message" => "Save failed"]));
} catch (Throwable $e) {
    // Clear any buffered output
    ob_end_clean();

    // Log the actual error for debugging
    error_log("Upload endpoint error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    http_response_code(500);
    die(json_encode(["success" => false, "message" => $e->getMessage()]));
}
