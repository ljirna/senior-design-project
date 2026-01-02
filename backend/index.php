<?php
require 'vendor/autoload.php';

// Register ALL services
require_once __DIR__ . '/data/roles.php';
require_once __DIR__ . '/rest/services/ProductService.php';
require_once __DIR__ . '/rest/services/CategoryService.php';
require_once __DIR__ . '/rest/services/UserService.php';
require_once __DIR__ . '/rest/services/CartService.php';
require_once __DIR__ . '/rest/services/OrderService.php';
require_once __DIR__ . '/rest/services/FavoriteService.php';
require_once __DIR__ . '/rest/services/PaymentService.php';
require_once __DIR__ . '/rest/services/StripeService.php';
require_once __DIR__ . '/rest/services/AuthService.php';
require_once __DIR__ . '/middleware/AuthMiddleware.php';



use Firebase\JWT\JWT;
use Firebase\JWT\Key;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// This wildcard route intercepts all requests and applies authentication checks before proceeding.
Flight::route('/*', function () {
    $url = Flight::request()->url;

    // Public routes that don't require authentication
    if (
        strpos($url, '/auth/login') === 0 ||
        strpos($url, '/auth/register') === 0 ||
        strpos($url, '/products') === 0 ||
        strpos($url, '/categories') === 0
    ) {
        return TRUE;
    } else {
        try {
            $token = Flight::request()->getHeader("Authentication");
            if (!$token)
                Flight::halt(401, "Missing authentication header");
            $decoded_token = JWT::decode($token, new Key(
                Config::JWT_SECRET(),
                'HS256'
            ));
            $userPayload = $decoded_token->user;
            if (is_object($userPayload)) {
                $userPayload = (array)$userPayload;
            }
            Flight::set('user', $userPayload);
            Flight::set('jwt_token', $token);
            return TRUE;
        } catch (\Exception $e) {
            Flight::halt(401, $e->getMessage());
        }
    }
});

Flight::register('productService', 'ProductService');
Flight::register('categoryService', 'CategoryService');
Flight::register('userService', 'UserService');
Flight::register('cartService', 'CartService');
Flight::register('orderService', 'OrderService');
Flight::register('favoriteService', 'FavoriteService');
Flight::register('paymentService', 'PaymentService');
Flight::register('auth_middleware', 'AuthMiddleware');
Flight::register('stripeService', 'StripeService');
Flight::register('auth_service', 'AuthService');



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
require_once __DIR__ . '/rest/routes/AuthRoutes.php';
Flight::start();
