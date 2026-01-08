<?php
require_once __DIR__ . '/../services/CategoryService.php';

Flight::group('/categories', function () {
    // Get all categories with product count - PUBLIC
    Flight::route('GET /', function () {
        Flight::json(Flight::categoryService()->getAllCategoriesWithCount());
    });

    // Get single category - PUBLIC
    Flight::route('GET /@category_id', function ($category_id) {
        $category = Flight::categoryService()->getCategoryById($category_id);
        if ($category) {
            Flight::json($category);
        } else {
            Flight::json(['error' => 'Category not found'], 404);
        }
    });

    // Get popular categories (with products) - PUBLIC
    Flight::route('GET /popular', function () {
        $limit = Flight::request()->query['limit'] ?? 5;
        Flight::json(Flight::categoryService()->getCategoriesWithProducts($limit));
    });

    // Search categories - PUBLIC
    Flight::route('GET /search', function () {
        $search_term = Flight::request()->query['q'] ?? '';
        Flight::json(Flight::categoryService()->searchCategories($search_term));
    });

    // --- ADMIN ONLY ROUTES BELOW ---

    // Create category - ADMIN ONLY
    Flight::route('POST /', function () {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        $data = Flight::request()->data->getData();
        $validation = Flight::categoryService()->validateCategoryData($data);

        if (!$validation['valid']) {
            Flight::json(['error' => $validation['errors']], 400);
            return;
        }

        try {
            $category = Flight::categoryService()->add($data);
            Flight::json($category, 201);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // Update category - ADMIN ONLY
    Flight::route('PUT /@category_id', function ($category_id) {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        $data = Flight::request()->data->getData();
        $validation = Flight::categoryService()->validateCategoryData($data);

        if (!$validation['valid']) {
            Flight::json(['error' => $validation['errors']], 400);
            return;
        }

        try {
            $category = Flight::categoryService()->update($category_id, $data);
            Flight::json($category);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // Delete category - ADMIN ONLY
    Flight::route('DELETE /@category_id', function ($category_id) {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        try {
            Flight::categoryService()->delete($category_id);
            Flight::json(['success' => true, 'message' => 'Category deleted successfully']);
        } catch (Exception $e) {
            Flight::json(['error' => $e->getMessage()], 400);
        }
    });

    // Validate category data - ADMIN ONLY
    Flight::route('POST /validate', function () {
        Flight::auth_middleware()->authorizeRole(Roles::ADMIN);

        $data = Flight::request()->data->getData();
        $validation = Flight::categoryService()->validateCategoryData($data);
        Flight::json($validation);
    });
});
