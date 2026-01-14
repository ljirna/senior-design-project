<?php
require_once __DIR__ . '/../services/FavoriteService.php';

Flight::group('/favorites', function () {
    Flight::route('GET /', function () {
        try {
            Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

            $user = Flight::get('user');
            $limit = Flight::request()->query['limit'] ?? 20;
            $offset = Flight::request()->query['offset'] ?? 0;

            Flight::json(Flight::favoriteService()->getUserFavorites($user['user_id'], $limit, $offset));
        } catch (Exception $e) {
            error_log("Favorites GET error: " . $e->getMessage());
            Flight::json(['error' => $e->getMessage()], 500);
        }
    });

    Flight::route('GET /check/@product_id', function ($product_id) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $user = Flight::get('user');
        Flight::json([
            'is_favorited' => Flight::favoriteService()->isProductFavorited($user['user_id'], $product_id)
        ]);
    });

    Flight::route('POST /add', function () {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $user = Flight::get('user');
        $data = Flight::request()->data->getData();

        if (!isset($data['product_id'])) {
            Flight::json(['error' => 'Product ID is required'], 400);
            return;
        }

        try {
            $result = Flight::favoriteService()->addToFavorites($user['user_id'], $data['product_id']);
            Flight::json([
                'success' => true,
                'message' => 'Product added to favorites',
                'data' => $result
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    Flight::route('DELETE /remove/@product_id', function ($product_id) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $user = Flight::get('user');

        try {
            $result = Flight::favoriteService()->removeFromFavorites($user['user_id'], $product_id);
            Flight::json([
                'success' => true,
                'message' => 'Product removed from favorites'
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    Flight::route('POST /toggle', function () {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $user = Flight::get('user');
        $data = Flight::request()->data->getData();

        if (!isset($data['product_id'])) {
            Flight::json(['error' => 'Product ID is required'], 400);
            return;
        }

        try {
            $result = Flight::favoriteService()->toggleFavorite($user['user_id'], $data['product_id']);
            Flight::json([
                'success' => true,
                'message' => 'Favorite toggled successfully',
                'data' => $result
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    Flight::route('POST /toggle/@product_id', function ($product_id) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $user = Flight::get('user');

        try {
            $result = Flight::favoriteService()->toggleFavorite($user['user_id'], $product_id);
            Flight::json([
                'success' => true,
                'message' => 'Favorite toggled successfully',
                'data' => $result
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    Flight::route('GET /count', function () {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $user = Flight::get('user');
        Flight::json([
            'favorite_count' => Flight::favoriteService()->getUserFavoriteCount($user['user_id'])
        ]);
    });

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
