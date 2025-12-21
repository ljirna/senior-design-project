<?php
require 'vendor/autoload.php';

// Register ALL services
require_once __DIR__ . '/rest/services/ProductService.php';
require_once __DIR__ . '/rest/services/CategoryService.php';
require_once __DIR__ . '/rest/services/UserService.php';
require_once __DIR__ . '/rest/services/CartService.php';
require_once __DIR__ . '/rest/services/OrderService.php';
require_once __DIR__ . '/rest/services/FavoriteService.php';
require_once __DIR__ . '/rest/services/PaymentService.php';
require_once __DIR__ . '/rest/services/PaymentService.php';
require_once __DIR__ . '/rest/services/StripeService.php';


Flight::register('productService', 'ProductService');
Flight::register('categoryService', 'CategoryService');
Flight::register('userService', 'UserService');
Flight::register('cartService', 'CartService');
Flight::register('orderService', 'OrderService');
Flight::register('favoriteService', 'FavoriteService');
Flight::register('paymentService', 'PaymentService');
Flight::register('paymentService', 'PaymentService');
Flight::register('stripeService', 'StripeService');


// Define base route
Flight::route('/', function () {
    echo 'Welcome to Furniture E-commerce API!';
});

// Include ALL route files
require_once __DIR__ . '/rest/routes/ProductRoutes.php';
require_once __DIR__ . '/rest/routes/CategoryRoutes.php';
require_once __DIR__ . '/rest/routes/UserRoutes.php';
require_once __DIR__ . '/rest/routes/CartRoutes.php';
require_once __DIR__ . '/rest/routes/OrderRoutes.php';
require_once __DIR__ . '/rest/routes/FavoriteRoutes.php';
require_once __DIR__ . '/rest/routes/PaymentRoutes.php';
require_once __DIR__ . '/rest/routes/PaymentRoutes.php';


Flight::start();
