<?php
require 'vendor/autoload.php'; // run autoloader

// Register services FIRST
require_once __DIR__ . '/rest/services/ProductService.php';
Flight::register('productService', 'ProductService');

// Define base route
Flight::route('/', function () {
    echo 'Welcome to Furniture E-commerce API!';
});

// Include route files AFTER service registration
require_once __DIR__ . '/rest/routes/ProductRoutes.php';

Flight::start(); // start FlightPHP