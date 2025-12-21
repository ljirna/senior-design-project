<?php
require_once __DIR__ . '/../services/FavoriteService.php';

// Get user's favorites
Flight::route('GET /users/@user_id/favorites', function ($user_id) {
    $limit = Flight::request()->query['limit'] ?? 20;
    $offset = Flight::request()->query['offset'] ?? 0;
    Flight::json(Flight::favoriteService()->getUserFavorites($user_id, $limit, $offset));
});

// Check if product is favorited
Flight::route('GET /users/@user_id/favorites/@product_id', function ($user_id, $product_id) {
    Flight::json([
        'is_favorited' => Flight::favoriteService()->isProductFavorited($user_id, $product_id)
    ]);
});

// Add to favorites
Flight::route('POST /users/@user_id/favorites', function ($user_id) {
    try {
        $data = Flight::request()->data->getData();
        Flight::favoriteService()->addToFavorites($user_id, $data['product_id']);
        Flight::json(['message' => 'Product added to favorites']);
    } catch (Exception $e) {
        Flight::json(['error' => $e->getMessage()], 400);
    }
});

// Remove from favorites
Flight::route('DELETE /users/@user_id/favorites/@product_id', function ($user_id, $product_id) {
    try {
        Flight::favoriteService()->removeFromFavorites($user_id, $product_id);
        Flight::json(['message' => 'Product removed from favorites']);
    } catch (Exception $e) {
        Flight::json(['error' => $e->getMessage()], 400);
    }
});

// Toggle favorite
Flight::route('POST /users/@user_id/favorites/toggle', function ($user_id) {
    try {
        $data = Flight::request()->data->getData();
        $result = Flight::favoriteService()->toggleFavorite($user_id, $data['product_id']);
        Flight::json($result);
    } catch (Exception $e) {
        Flight::json(['error' => $e->getMessage()], 400);
    }
});

// Get favorite count for product
Flight::route('GET /products/@product_id/favorite-count', function ($product_id) {
    Flight::json([
        'favorite_count' => Flight::favoriteService()->getFavoriteCount($product_id)
    ]);
});

// Get user's favorite count
Flight::route('GET /users/@user_id/favorites/count', function ($user_id) {
    Flight::json([
        'favorite_count' => Flight::favoriteService()->getUserFavoriteCount($user_id)
    ]);
});

// Get popular favorites
Flight::route('GET /favorites/popular', function () {
    $limit = Flight::request()->query['limit'] ?? 10;
    Flight::json(Flight::favoriteService()->getPopularFavorites($limit));
});
