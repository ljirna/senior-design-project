<?php
// Handle upload endpoint before Flight processes anything
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$request_path = parse_url($request_uri, PHP_URL_PATH) ?? '';

// Check for upload endpoint in multiple ways
$is_upload = (
    strpos($request_path, '/upload/item-image') !== false ||
    strpos($request_path, 'upload/item-image') !== false ||
    strpos($request_uri, '/upload/item-image') !== false ||
    strpos($request_uri, 'upload/item-image') !== false
);

if ($is_upload && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/api/upload/item-image/index.php';
    exit;
}

// Check for image serving in uploads directory
$is_image_request = (
    strpos($request_path, '/uploads/products') !== false ||
    strpos($request_path, 'uploads/products') !== false ||
    strpos($request_uri, '/uploads/products') !== false ||
    strpos($request_uri, 'uploads/products') !== false
);

if ($is_image_request && $_SERVER['REQUEST_METHOD'] === 'GET') {
    require_once __DIR__ . '/api/uploads/products/index.php';
    exit;
}

require 'vendor/autoload.php';
require_once __DIR__ . '/config.php';

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

Flight::route('/*', function () {
    $url = Flight::request()->url;
    $method = Flight::request()->method;
    
    // Allow all GET requests (read-only operations)
    if ($method === 'GET') {
        return TRUE;
    }
    
    // For POST/PUT/DELETE, check auth and public routes
    $url_path = parse_url($url, PHP_URL_PATH);
    $public_post_routes = ['/auth/login', '/auth/register'];
    
    $is_public = FALSE;
    foreach ($public_post_routes as $route) {
        if (strpos($url_path, $route) === 0 || strpos($url, $route) === 0) {
            $is_public = TRUE;
            break;
        }
    }
    
    if ($is_public) {
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



Flight::route('/', function () {
    echo 'Welcome to Furniture E-commerce API!';
});

require_once __DIR__ . '/rest/routes/ProductRoutes.php';
require_once __DIR__ . '/rest/routes/CategoryRoutes.php';
require_once __DIR__ . '/rest/routes/UserRoutes.php';
require_once __DIR__ . '/rest/routes/CartRoutes.php';
require_once __DIR__ . '/rest/routes/OrderRoutes.php';
require_once __DIR__ . '/rest/routes/FavoriteRoutes.php';
require_once __DIR__ . '/rest/routes/PaymentRoutes.php';
require_once __DIR__ . '/rest/routes/AuthRoutes.php';

// Handle /api/ prefix by delegating to the non-api routes
Flight::group('/api', function () {
    require_once __DIR__ . '/rest/routes/ProductRoutes.php';
    require_once __DIR__ . '/rest/routes/CategoryRoutes.php';
    require_once __DIR__ . '/rest/routes/UserRoutes.php';
    require_once __DIR__ . '/rest/routes/CartRoutes.php';
    require_once __DIR__ . '/rest/routes/OrderRoutes.php';
    require_once __DIR__ . '/rest/routes/FavoriteRoutes.php';
    require_once __DIR__ . '/rest/routes/PaymentRoutes.php';
    require_once __DIR__ . '/rest/routes/AuthRoutes.php';
});

// Handle malformed paths from DigitalOcean (no slash after /api)
// /apicategories -> same as /categories, /apiproducts -> same as /products
Flight::route('GET /apicategories/@id?', function ($id = null) {
    if ($id) {
        $category = Flight::categoryService()->getCategoryById($id);
        if ($category) {
            Flight::json($category);
        } else {
            Flight::json(['error' => 'Category not found'], 404);
        }
    } else {
        Flight::json(Flight::categoryService()->getAllCategoriesWithCount());
    }
});

Flight::route('GET /apiproducts/@id?', function ($id = null) {
    if ($id) {
        $product = Flight::productService()->getProductById($id);
        if ($product) {
            Flight::json($product);
        } else {
            Flight::json(['error' => 'Product not found'], 404);
        }
    } else {
        Flight::json(Flight::productService()->getAllProducts());
    }
});

Flight::route('POST /apiauth/login', function () {
    // Call the actual login handler
    require_once __DIR__ . '/rest/routes/AuthRoutes.php';
    Flight::getMethod()['GET'](function () { Flight::notFound(); });
});

// Handle /api/ prefix by delegating to the non-api routes
Flight::group('/api', function () {
    require_once __DIR__ . '/rest/routes/ProductRoutes.php';
    require_once __DIR__ . '/rest/routes/CategoryRoutes.php';
    require_once __DIR__ . '/rest/routes/UserRoutes.php';
    require_once __DIR__ . '/rest/routes/CartRoutes.php';
    require_once __DIR__ . '/rest/routes/OrderRoutes.php';
    require_once __DIR__ . '/rest/routes/FavoriteRoutes.php';
    require_once __DIR__ . '/rest/routes/PaymentRoutes.php';
    require_once __DIR__ . '/rest/routes/AuthRoutes.php';
});

Flight::start();
