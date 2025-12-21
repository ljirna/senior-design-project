<?php
require_once __DIR__ . '/../services/CategoryService.php';

// Get all categories with product count
Flight::route('GET /categories', function () {
    Flight::json(Flight::categoryService()->getAllCategoriesWithCount());
});

// Get single category
Flight::route('GET /categories/@category_id', function ($category_id) {
    Flight::json(Flight::categoryService()->getCategoryById($category_id));
});

// Get category with fees
Flight::route('GET /categories/@category_id/with-fees', function ($category_id) {
    $quantity = Flight::request()->query['quantity'] ?? 1;
    Flight::json(Flight::categoryService()->calculateCategoryFees($category_id, $quantity));
});

// Get popular categories (with products)
Flight::route('GET /categories/popular', function () {
    $limit = Flight::request()->query['limit'] ?? 5;
    Flight::json(Flight::categoryService()->getCategoriesWithProducts($limit));
});

// Search categories
Flight::route('GET /categories/search/@search_term', function ($search_term) {
    Flight::json(Flight::categoryService()->searchCategories($search_term));
});

// Create category (Admin only)
Flight::route('POST /categories', function () {
    $data = Flight::request()->data->getData();
    $validation = Flight::categoryService()->validateCategoryData($data);
    
    if (!$validation['valid']) {
        Flight::json(['error' => $validation['errors']], 400);
        return;
    }
    
    Flight::json(Flight::categoryService()->create($data), 201);
});

// Update category (Admin only)
Flight::route('PUT /categories/@category_id', function ($category_id) {
    $data = Flight::request()->data->getData();
    $validation = Flight::categoryService()->validateCategoryData($data);
    
    if (!$validation['valid']) {
        Flight::json(['error' => $validation['errors']], 400);
        return;
    }
    
    Flight::json(Flight::categoryService()->update($category_id, $data));
});

// Delete category (Admin only)
Flight::route('DELETE /categories/@category_id', function ($category_id) {
    try {
        Flight::categoryService()->delete($category_id);
        Flight::json(['message' => 'Category deleted successfully']);
    } catch (Exception $e) {
        Flight::json(['error' => $e->getMessage()], 400);
    }
});