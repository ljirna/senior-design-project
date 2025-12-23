<?php
require_once __DIR__ . '/../services/ProductService.php';

Flight::group('/products', function () {
    // --- PUBLIC ROUTES (no auth needed) ---

    // Get all products with pagination - PUBLIC
    Flight::route('GET /', function () {
        $limit = Flight::request()->query['limit'] ?? 20;
        $offset = Flight::request()->query['offset'] ?? 0;
        Flight::json(Flight::productService()->getAllProducts($limit, $offset));
    });

    // Get single product - PUBLIC
    Flight::route('GET /@product_id', function ($product_id) {
        $product = Flight::productService()->getProductById($product_id);
        if ($product) {
            Flight::json($product);
        } else {
            Flight::json(['error' => 'Product not found'], 404);
        }
    });

    // Get product with fees calculation - PUBLIC
    Flight::route('GET /@product_id/fees', function ($product_id) {
        $quantity = Flight::request()->query['quantity'] ?? 1;

        $total = Flight::productService()->calculateTotalPrice($product_id, $quantity);
        if ($total) {
            Flight::json($total);
        } else {
            Flight::json(['error' => 'Product not found'], 404);
        }
    });

    // Get product details with fees - PUBLIC
    Flight::route('GET /@product_id/details', function ($product_id) {
        $product = Flight::productService()->getProductWithFees($product_id);
        if ($product) {
            Flight::json($product);
        } else {
            Flight::json(['error' => 'Product not found'], 404);
        }
    });

    // Get featured products - PUBLIC
    Flight::route('GET /featured', function () {
        $limit = Flight::request()->query['limit'] ?? 8;
        Flight::json(Flight::productService()->getFeaturedProducts($limit));
    });

    // Get new arrivals - PUBLIC
    Flight::route('GET /new-arrivals', function () {
        $limit = Flight::request()->query['limit'] ?? 8;
        Flight::json(Flight::productService()->getNewArrivals($limit));
    });

    // Get products by category - PUBLIC
    Flight::route('GET /category/@category_id', function ($category_id) {
        $limit = Flight::request()->query['limit'] ?? 20;
        $offset = Flight::request()->query['offset'] ?? 0;

        Flight::json(Flight::productService()->getProductsByCategory($category_id, $limit, $offset));
    });

    // Get related products - PUBLIC
    Flight::route('GET /@product_id/related', function ($product_id) {
        $limit = Flight::request()->query['limit'] ?? 4;

        $product = Flight::productService()->getProductById($product_id);
        if (!$product) {
            Flight::json(['error' => 'Product not found'], 404);
            return;
        }

        $category_id = $product['category_id'] ?? null;
        if (!$category_id) {
            Flight::json([]);
            return;
        }

        $related = Flight::productService()->getRelatedProducts($product_id, $category_id, $limit);
        Flight::json($related);
    });

    // Search products - PUBLIC
    Flight::route('GET /search', function () {
        $search_term = Flight::request()->query['q'] ?? '';
        $limit = Flight::request()->query['limit'] ?? 20;
        $offset = Flight::request()->query['offset'] ?? 0;

        Flight::json(Flight::productService()->searchProducts($search_term, $limit, $offset));
    });

    // Get products by price range - PUBLIC
    Flight::route('GET /price-range', function () {
        $min_price = Flight::request()->query['min'] ?? 0;
        $max_price = Flight::request()->query['max'] ?? 10000;
        $category_id = Flight::request()->query['category'] ?? null;

        Flight::json(Flight::productService()->getProductsByPriceRange($min_price, $max_price, $category_id));
    });

    // Validate product stock - BOTH admin and customer
    Flight::route('POST /@product_id/validate-stock', function ($product_id) {
        Flight::auth_middleware()->authorizeRoles([Roles::ADMIN, Roles::CUSTOMER]);

        $data = Flight::request()->data->getData();
        $quantity = $data['quantity'] ?? 1;

        $validation = Flight::productService()->validateStock($product_id, $quantity);
        Flight::json($validation);
    });

    // --- ADMIN ONLY ROUTES BELOW ---

    // Create product - ADMIN ONLY
    Flight::route('POST /', function () {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        $data = Flight::request()->data->getData();

        // Basic validation
        if (empty($data['name'])) {
            Flight::json(['error' => 'Product name is required'], 400);
            return;
        }

        if (empty($data['price']) || $data['price'] < 0) {
            Flight::json(['error' => 'Valid price is required'], 400);
            return;
        }

        try {
            $product = Flight::productService()->add($data);
            Flight::json($product, 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // Update product - ADMIN ONLY
    Flight::route('PUT /@product_id', function ($product_id) {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        $data = Flight::request()->data->getData();

        // Basic validation
        if (isset($data['price']) && $data['price'] < 0) {
            Flight::json(['error' => 'Price cannot be negative'], 400);
            return;
        }

        try {
            $product = Flight::productService()->update($product_id, $data);
            Flight::json($product);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // Delete product - ADMIN ONLY
    Flight::route('DELETE /@product_id', function ($product_id) {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        try {
            $result = Flight::productService()->delete($product_id);
            Flight::json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // Update product stock - ADMIN ONLY
    Flight::route('PUT /@product_id/stock', function ($product_id) {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        $data = Flight::request()->data->getData();

        if (!isset($data['quantity'])) {
            Flight::json(['error' => 'Quantity is required'], 400);
            return;
        }

        try {
            // Get current product
            $product = Flight::productService()->getProductById($product_id);
            if (!$product) {
                Flight::json(['error' => 'Product not found'], 404);
                return;
            }

            // Update with new stock quantity
            $updated = Flight::productService()->update($product_id, ['stock_quantity' => $data['quantity']]);
            Flight::json([
                'success' => true,
                'message' => 'Stock updated successfully',
                'data' => $updated
            ]);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // Add product image - ADMIN ONLY
    Flight::route('POST /@product_id/images', function ($product_id) {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        $data = Flight::request()->data->getData();

        if (!isset($data['image_url'])) {
            Flight::json(['error' => 'Image URL is required'], 400);
            return;
        }

        // This would handle file upload and save to database
        Flight::json([
            'success' => true,
            'message' => 'Image added successfully',
            'image_url' => $data['image_url']
        ], 201);
    });

    // Delete product image - ADMIN ONLY
    Flight::route('DELETE /@product_id/images/@image_id', function ($product_id, $image_id) {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        // This would delete image from storage and database
        Flight::json([
            'success' => true,
            'message' => 'Image deleted successfully'
        ]);
    });

    // Get product statistics - ADMIN ONLY
    Flight::route('GET /statistics', function () {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        // This would return product statistics
        Flight::json([
            'total_products' => 0,
            'low_stock' => 0,
            'out_of_stock' => 0
        ]);
    });
});
