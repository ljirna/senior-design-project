<?php
require_once __DIR__ . '/../services/FavoriteService.php';

Flight::group('/favorites', function () {
    // Get user's favorites - BOTH admin and customer (their own)
    Flight::route('GET /', function () {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);
        
        $user = Flight::get('user');
        $limit = Flight::request()->query['limit'] ?? 20;
        $offset = Flight::request()->query['offset'] ?? 0;
        
        Flight::json(Flight::favoriteService()->getUserFavorites($user['id'], $limit, $offset));
    });

    // Check if product is favorited - BOTH admin and customer
    Flight::route('GET /check/@product_id', function ($product_id) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);
        
        $user = Flight::get('user');
        Flight::json([
            'is_favorited' => Flight::favoriteService()->isProductFavorited($user['id'], $product_id)
        ]);
    });

    // Add to favorites - BOTH admin and customer
    Flight::route('POST /add', function () {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);
        
        $user = Flight::get('user');
        $data = Flight::request()->data->getData();
        
        if (!isset($data['product_id'])) {
            Flight::json(['error' => 'Product ID is required'], 400);
            return;
        }
        
        try {
            $result = Flight::favoriteService()->addToFavorites($user['id'], $data['product_id']);
            Flight::json([
                'success' => true,
                'message' => 'Product added to favorites',
                'data' => $result
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // Remove from favorites - BOTH admin and customer
    Flight::route('DELETE /remove/@product_id', function ($product_id) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);
        
        $user = Flight::get('user');
        
        try {
            $result = Flight::favoriteService()->removeFromFavorites($user['id'], $product_id);
            Flight::json([
                'success' => true,
                'message' => 'Product removed from favorites'
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // Toggle favorite - BOTH admin and customer
    Flight::route('POST /toggle', function () {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);
        
        $user = Flight::get('user');
        $data = Flight::request()->data->getData();
        
        if (!isset($data['product_id'])) {
            Flight::json(['error' => 'Product ID is required'], 400);
            return;
        }
        
        try {
            $result = Flight::favoriteService()->toggleFavorite($user['id'], $data['product_id']);
            Flight::json([
                'success' => true,
                'message' => 'Favorite toggled successfully',
                'data' => $result
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // Toggle favorite by ID - BOTH admin and customer
    Flight::route('POST /toggle/@product_id', function ($product_id) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);
        
        $user = Flight::get('user');
        
        try {
            $result = Flight::favoriteService()->toggleFavorite($user['id'], $product_id);
            Flight::json([
                'success' => true,
                'message' => 'Favorite toggled successfully',
                'data' => $result
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // Get user's favorite count - BOTH admin and customer
    Flight::route('GET /count', function () {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);
        
        $user = Flight::get('user');
        Flight::json([
            'favorite_count' => Flight::favoriteService()->getUserFavoriteCount($user['id'])
        ]);
    });

    // Validate product for favorite - BOTH admin and customer
    Flight::route('POST /validate/@product_id', function ($product_id) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);
        
        $validation = Flight::favoriteService()->validateProductForFavorite($product_id);
        Flight::json($validation);
    });

    // --- PUBLIC ROUTES (no auth needed) ---

    // Get favorite count for product - PUBLIC
    Flight::route('GET /product/@product_id/count', function ($product_id) {
        Flight::json([
            'favorite_count' => Flight::favoriteService()->getFavoriteCount($product_id)
        ]);
    });

    // Get popular favorites - PUBLIC
    Flight::route('GET /popular', function () {
        $limit = Flight::request()->query['limit'] ?? 10;
        Flight::json(Flight::favoriteService()->getPopularFavorites($limit));
    });
});