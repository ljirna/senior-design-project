<?php
require_once __DIR__ . '/../services/ProductService.php';

// Single product
Flight::route('GET /products/@product_id', function ($product_id) {
    Flight::json(Flight::productService()->getProductById($product_id));
});

// All products with pagination
Flight::route('GET /products', function () {
    $limit = Flight::request()->query['limit'] ?? 20;
    $offset = Flight::request()->query['offset'] ?? 0;
    Flight::json(Flight::productService()->getAllProducts($limit, $offset));
});

// Products by category
Flight::route('GET /categories/@category_id/products', function ($category_id) {
    $limit = Flight::request()->query['limit'] ?? 20;
    $offset = Flight::request()->query['offset'] ?? 0;
    Flight::json(Flight::productService()->getProductsByCategory($category_id, $limit, $offset));
});

// Search products
Flight::route('GET /products/search/@search_term', function ($search_term) {
    $limit = Flight::request()->query['limit'] ?? 20;
    $offset = Flight::request()->query['offset'] ?? 0;
    Flight::json(Flight::productService()->searchProducts($search_term, $limit, $offset));
});

// Featured products
Flight::route('GET /products/featured', function () {
    $limit = Flight::request()->query['limit'] ?? 8;
    Flight::json(Flight::productService()->getFeaturedProducts($limit));
});

// New arrivals
Flight::route('GET /products/new', function () {
    $limit = Flight::request()->query['limit'] ?? 8;
    Flight::json(Flight::productService()->getNewArrivals($limit));
});

// Related products
Flight::route('GET /products/@product_id/related', function ($product_id) {
    $product = Flight::productService()->getProductById($product_id);
    $limit = Flight::request()->query['limit'] ?? 4;
    Flight::json(Flight::productService()->getRelatedProducts(
        $product_id,
        $product['category_id'],
        $limit
    ));
});

// Product with fees calculation
Flight::route('GET /products/@product_id/with-fees', function ($product_id) {
    $quantity = Flight::request()->query['quantity'] ?? 1;
    Flight::json(Flight::productService()->calculateTotalPrice($product_id, $quantity));
});

// Price filter
Flight::route('GET /products/filter/price', function () {
    $min = Flight::request()->query['min'] ?? 0;
    $max = Flight::request()->query['max'] ?? 10000;
    $category_id = Flight::request()->query['category_id'] ?? null;
    Flight::json(Flight::productService()->getProductsByPriceRange($min, $max, $category_id));
});
