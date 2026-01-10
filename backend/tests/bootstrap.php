<?php
// Bootstrap file for PHPUnit tests
define('BASE_PATH', __DIR__ . '/..');

// Load autoloader (this handles all class loading via Composer)
require BASE_PATH . '/vendor/autoload.php';

// Load configuration
require BASE_PATH . '/config.php';

// Suppress display_errors during tests
ini_set('display_errors', 0);
error_reporting(E_ALL);
